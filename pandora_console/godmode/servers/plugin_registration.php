<?php
/**
 * Plugin registration.
 *
 * @category   Plugins
 * @package    Pandora FMS
 * @subpackage Community
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
 * Please see http://pandorafms.org for full contribution list
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation for version 2.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * ============================================================================
 */

// Load global vars.
global $config;

// Check ACL and Login.
check_login();

if ((bool) check_acl($config['id_user'], 0, 'PM') === false
    && (bool) check_acl($config['id_user'], 0, 'AW') === false
) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access Plugin Management'
    );
    include 'general/noaccess.php';
    return;
}

ui_require_css_file('first_task');

if (is_metaconsole() === true) {
    enterprise_include_once('meta/include/functions_components_meta.php');
    enterprise_hook('open_meta_frame');
    components_meta_print_header();
    $sec = 'advanced';
    $management_allowed = is_management_allowed();
    if ($management_allowed === false) {
        ui_print_warning_message(
            __('To manage plugin you must activate centralized management')
        );
        return;
    }
} else {
    ui_print_page_header(
        __('PLUGIN REGISTRATION'),
        'images/gm_servers.png',
        false,
        '',
        true
    );

    $management_allowed = is_management_allowed();
    if ($management_allowed === false) {
        ui_print_warning_message(
            __(
                'This console is not manager of this environment,
            please manage this feature from centralized manager console (Metaconsole).'
            )
        );
        return;
    }
}

$str = 'This extension makes registering server plugins an easier task.';
$str .= ' Here you can upload a server plugin in .pspz zipped format.';
$str .= ' Please refer to the official documentation on how to obtain and use Server Plugins.';

$output = '<div class="new_task">';
$output .= '<div class="image_task">';
$output .= html_print_image(
    'images/first_task/icono_grande_import.png',
    true,
    ['title' => __('Plugin Registration') ]
);
$output .= '</div>';
$output .= '<div class="text_task">';
$output .= '<h3>'.__('Plugin registration').'</h3>';
$output .= '<p id="description_task">';
$output .= __($str);
$output .= '<br><br>';
$output .= __('You can get more plugins in our');
$output .= '<a href="http://pandorafms.com/Library/Library/">';
$output .= ' '.__('Public Resource Library');
$output .= '</a>';
$output .= '</p>';

// Upload form.
$output .= "<form name='submit_plugin' method='post' enctype='multipart/form-data'>";
$output .= '<table class="" id="table1" width="100%" border="0" cellpadding="4" cellspacing="4">';
$output .= "<tr><td class='datos'><input type='file' name='plugin_upload' />";
$output .= "<td class='datos'><input type='submit' class='sub next' value='".__('Upload')."' />";
$output .= '</form></table>';
$output .= '</div>';
$output .= '</div>';

echo $output;

$zip = null;
$upload = false;
if (isset($_FILES['plugin_upload']) === true) {
    $config['plugin_store'] = $config['attachment_store'].'/plugin';

    $name_file = $_FILES['plugin_upload']['name'];

    $zip = zip_open($_FILES['plugin_upload']['tmp_name']);
    $upload = true;
}

if (isset($zip) === true && empty($zip) === false) {
    while ($zip_entry = zip_read($zip)) {
        if (zip_entry_open($zip, $zip_entry, 'r')) {
            if (zip_entry_name($zip_entry) == 'plugin_definition.ini') {
                $basepath = $config['attachment_store'];
            } else {
                $basepath = $config['plugin_store'];
            }

            $filename = $basepath.'/'.zip_entry_name($zip_entry);
            $fp = fopen($filename, 'w');
            $buf = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
                fwrite($fp, $buf);
                fclose($fp);
                chmod($filename, 0755);
            zip_entry_close($zip_entry);
        }
    }

    zip_close($zip);
}

if ($upload === true) {
    $ini_array = parse_ini_file($config['attachment_store'].'/plugin_definition.ini', true);
    // Parse with sections.
    if ($ini_array === false) {
        ui_print_error_message(__('Cannot load INI file'));
    } else {
        $version = preg_replace('/.*[.]/', '', $name_file);

        $exec_path = $config['plugin_store'].'/'.$ini_array['plugin_definition']['filename'];

        $file_exec_path = $exec_path;

        if (isset($ini_array['plugin_definition']['execution_command']) === true
            && empty($ini_array['plugin_definition']['execution_command']) === false
        ) {
            $exec_path = $ini_array['plugin_definition']['execution_command'];
            $exec_path .= ' '.$config['plugin_store'].'/';
            $exec_path .= $ini_array['plugin_definition']['filename'];
        }

        if (isset($ini_array['plugin_definition']['execution_postcommand']) === true
            && empty($ini_array['plugin_definition']['execution_postcommand']) === false
        ) {
            $exec_path .= ' '.$ini_array['plugin_definition']['execution_postcommand'];
        }

        if (file_exists($file_exec_path) === false) {
            ui_print_error_message(__('Plugin exec not found. Aborting!'));
            unlink($config['attachment_store'].'/plugin_definition.ini');
        } else {
            // Verify if a plugin with the same name is already registered.
            $sql = sprintf(
                'SELECT COUNT(*)
                FROM tplugin
                WHERE name = "%s"',
                io_safe_input($ini_array['plugin_definition']['name'])
            );
            $result = db_get_sql($sql);

            if ($result > 0) {
                ui_print_error_message(__('Plugin already registered. Aborting!'));
                unlink($config['attachment_store'].'/plugin_definition.ini');
            } else {
                $values = [
                    'name'         => io_safe_input($ini_array['plugin_definition']['name']),
                    'description'  => io_safe_input($ini_array['plugin_definition']['description']),
                    'max_timeout'  => $ini_array['plugin_definition']['timeout'],
                    'execute'      => io_safe_input($exec_path),
                    'net_dst_opt'  => $ini_array['plugin_definition']['ip_opt'],
                    'net_port_opt' => $ini_array['plugin_definition']['port_opt'],
                    'user_opt'     => $ini_array['plugin_definition']['user_opt'],
                    'pass_opt'     => $ini_array['plugin_definition']['pass_opt'],
                    'parameters'   => $ini_array['plugin_definition']['parameters'],
                    'plugin_type'  => $ini_array['plugin_definition']['plugin_type'],
                ];

                switch ($version) {
                    case 'pspz':
                        // Fixed the static parameters
                        // for
                        // the dinamic parameters of pandoras 5.
                        $total_macros = 0;
                        $macros = [];

                        if (isset($values['parameters']) === false) {
                            $values['parameters'] = '';
                        }

                        if (empty($values['net_dst_opt']) === false) {
                            $total_macros++;

                            $macro = [];
                            $macro['macro'] = '_field'.$total_macros.'_';
                            $macro['desc'] = 'Target IP from net';
                            $macro['help'] = '';
                            $macro['value'] = '';

                            $values['parameters'] .= $values['net_dst_opt'].' _field'.$total_macros.'_ ';

                            $macros[(string) $total_macros] = $macro;
                        }

                        if (empty($values['ip_opt']) === false) {
                            $total_macros++;

                            $macro = [];
                            $macro['macro'] = '_field'.$total_macros.'_';
                            $macro['desc'] = 'Target IP';
                            $macro['help'] = '';
                            $macro['value'] = '';

                            $values['parameters'] .= $values['ip_opt'].' _field'.$total_macros.'_ ';

                            $macros[(string) $total_macros] = $macro;
                        }

                        if (empty($values['net_port_opt']) === false) {
                            $total_macros++;

                            $macro = [];
                            $macro['macro'] = '_field'.$total_macros.'_';
                            $macro['desc'] = 'Port from net';
                            $macro['help'] = '';
                            $macro['value'] = '';

                            $values['parameters'] .= $values['net_port_opt'].' _field'.$total_macros.'_ ';

                            $macros[(string) $total_macros] = $macro;
                        }

                        if (empty($values['port_opt']) === false) {
                            $total_macros++;

                            $macro = [];
                            $macro['macro'] = '_field'.$total_macros.'_';
                            $macro['desc'] = 'Port';
                            $macro['help'] = '';
                            $macro['value'] = '';

                            $values['parameters'] .= $values['port_opt'].' _field'.$total_macros.'_ ';

                            $macros[(string) $total_macros] = $macro;
                        }

                        if (empty($values['user_opt']) === false) {
                            $total_macros++;

                            $macro = [];
                            $macro['macro'] = '_field'.$total_macros.'_';
                            $macro['desc'] = 'Username';
                            $macro['help'] = '';
                            $macro['value'] = '';

                            $values['parameters'] .= $values['user_opt'].' _field'.$total_macros.'_ ';

                            $macros[(string) $total_macros] = $macro;
                        }

                        if (empty($values['pass_opt']) === false) {
                            $total_macros++;

                            $macro = [];
                            $macro['macro'] = '_field'.$total_macros.'_';
                            $macro['desc'] = 'Password';
                            $macro['help'] = '';
                            $macro['value'] = '';

                            $values['parameters'] .= $values['pass_opt'].' _field'.$total_macros.'_ ';

                            $macros[(string) $total_macros] = $macro;
                        }

                        // A last parameter is defined always to
                        // add the old "Plug-in parameters" in the
                        // side of the module.
                        $total_macros++;

                        $macro = [];
                        $macro['macro'] = '_field'.$total_macros.'_';
                        $macro['desc'] = 'Plug-in Parameters';
                        $macro['help'] = '';
                        $macro['value'] = '';

                        $values['parameters'] .= ' _field'.$total_macros.'_';

                        $macros[(string) $total_macros] = $macro;
                    break;

                    case 'pspz2':
                        // Fill the macros field.
                        $total_macros = $ini_array['plugin_definition']['total_macros_provided'];

                        $macros = [];
                        for ($it_macros = 1; $it_macros <= $total_macros; $it_macros++) {
                            $label = 'macro_'.$it_macros;

                            $macro = [];

                            $macro['macro'] = '_field'.$it_macros.'_';
                            $macro['hide'] = $ini_array[$label]['hide'];
                            $macro['desc'] = io_safe_input(
                                $ini_array[$label]['description']
                            );
                            $macro['help'] = io_safe_input(
                                $ini_array[$label]['help']
                            );
                            $macro['value'] = io_safe_input(
                                $ini_array[$label]['value']
                            );

                            $macros[(string) $it_macros] = $macro;
                        }
                    break;

                    default:
                        // Not possible.
                    break;
                }

                if (empty($macros) === false) {
                    $values['macros'] = json_encode($macros);
                }

                $create_id = db_process_sql_insert('tplugin', $values);

                if (empty($create_id) === true) {
                    ui_print_error_message(
                        __('Plug-in Remote Registered unsuccessfull')
                    );
                    ui_print_info_message(
                        __('Please check the syntax of file "plugin_definition.ini"')
                    );
                } else {
                    for ($ax = 1; $ax <= $ini_array['plugin_definition']['total_modules_provided']; $ax++) {
                        $label = 'module'.$ax;

                        $plugin_user = '';
                        if (isset($ini_array[$label]['plugin_user']) === true) {
                            $plugin_user = $ini_array[$label]['plugin_user'];
                        }

                        $plugin_pass = '';
                        if (isset($ini_array[$label]['plugin_pass']) === true) {
                            $plugin_pass = $ini_array[$label]['plugin_pass'];
                        }

                        $plugin_parameter = '';
                        if (isset($ini_array[$label]['plugin_parameter']) === true) {
                            $plugin_parameter = $ini_array[$label]['plugin_parameter'];
                        }

                        $unit = '';
                        if (isset($ini_array[$label]['unit']) === true) {
                            $unit = $ini_array[$label]['unit'];
                        }

                        $values = [
                            'name'               => io_safe_input($ini_array[$label]['name']),
                            'description'        => io_safe_input($ini_array[$label]['description']),
                            'id_group'           => $ini_array[$label]['id_group'],
                            'type'               => $ini_array[$label]['type'],
                            'max'                => ($ini_array[$label]['max'] ?? ''),
                            'min'                => ($ini_array[$label]['min'] ?? ''),
                            'module_interval'    => ($ini_array[$label]['module_interval'] ?? ''),
                            'id_module_group'    => $ini_array[$label]['id_module_group'],
                            'id_modulo'          => $ini_array[$label]['id_modulo'],
                            'plugin_user'        => io_safe_input($plugin_user),
                            'plugin_pass'        => io_safe_input($plugin_pass),
                            'plugin_parameter'   => io_safe_input($plugin_parameter),
                            'unit'               => io_safe_input($unit),
                            'max_timeout'        => ($ini_array[$label]['max_timeout'] ?? ''),
                            'history_data'       => ($ini_array[$label]['history_data'] ?? ''),
                            'dynamic_interval'   => ($ini_array[$label]['dynamic_interval'] ?? ''),
                            'dynamic_min'        => ($ini_array[$label]['dynamic_min'] ?? ''),
                            'dynamic_max'        => ($ini_array[$label]['dynamic_max'] ?? ''),
                            'dynamic_two_tailed' => ($ini_array[$label]['dynamic_two_tailed'] ?? ''),
                            'min_warning'        => ($ini_array[$label]['min_warning'] ?? ''),
                            'max_warning'        => ($ini_array[$label]['max_warning'] ?? ''),
                            'str_warning'        => ($ini_array[$label]['str_warning'] ?? ''),
                            'min_critical'       => ($ini_array[$label]['min_critical'] ?? ''),
                            'max_critical'       => ($ini_array[$label]['max_critical'] ?? ''),
                            'str_critical'       => ($ini_array[$label]['str_critical'] ?? ''),
                            'min_ff_event'       => ($ini_array[$label]['min_ff_event'] ?? ''),
                            'tcp_port'           => ($ini_array[$label]['tcp_port'] ?? ''),
                            'id_plugin'          => $create_id,
                        ];

                        $macros_component = $macros;

                        switch ($version) {
                            case 'pspz':
                                // Fixed the static parameters
                                // for
                                // the dinamic parameters of pandoras 5.
                                foreach ($macros_component as $key => $macro) {
                                    if ($macro['desc'] === 'Target IP from net') {
                                        if (empty($values['ip_target']) === false) {
                                            $macros_component[$key]['value'] = io_safe_input(
                                                $values['ip_target']
                                            );
                                        }
                                    }

                                    if ($macro['desc'] === 'Target IP') {
                                        if (empty($values['ip_target']) === false) {
                                            $macros_component[$key]['value'] = io_safe_input(
                                                $values['ip_target']
                                            );
                                        }
                                    } else if ($macro['desc'] === 'Port from net') {
                                        if (empty($values['tcp_port']) === false) {
                                            $macros_component[$key]['value'] = io_safe_input(
                                                $values['tcp_port']
                                            );
                                        }
                                    } else if ($macro['desc'] === 'Port') {
                                        if (empty($values['tcp_port']) === false) {
                                            $macros_component[$key]['value'] = io_safe_input(
                                                $values['tcp_port']
                                            );
                                        }
                                    } else if ($macro['desc'] === 'Username') {
                                        if (empty($values['plugin_user']) === false) {
                                            $macros_component[$key]['value'] = io_safe_input(
                                                $values['plugin_user']
                                            );
                                        }
                                    } else if ($macro['desc'] === 'Password') {
                                        if (empty($values['plugin_pass']) === false) {
                                            $macros_component[$key]['value'] = io_safe_input(
                                                $values['plugin_pass']
                                            );
                                        }
                                    } else if ($macro['desc'] === 'Plug-in Parameters') {
                                        if (empty($values['plugin_parameter']) === false) {
                                            $macros_component[$key]['value'] = io_safe_input(
                                                $values['plugin_parameter']
                                            );
                                        }
                                    }
                                }
                            break;

                            case 'pspz2':
                                if ($total_macros > 0) {
                                    for ($it_macros = 1; $it_macros <= $total_macros; $it_macros++) {
                                        $macro = 'macro_'.$it_macros.'_value';

                                        // Set the value or use the default.
                                        if (isset($ini_array[$label][$macro]) === true) {
                                            $macros_component[(string) $it_macros]['value'] = io_safe_input(
                                                $ini_array[$label][$macro]
                                            );
                                        }
                                    }
                                }
                            break;

                            default:
                                // Not possible.
                            break;
                        }

                        if (empty($macros_component) === false) {
                            $values['macros'] = json_encode($macros_component);
                        }

                        db_process_sql_insert('tnetwork_component', $values);

                        ui_print_success_message(
                            __('Module plugin registered').' : '.$ini_array[$label]['name']
                        );
                    }

                    ui_print_success_message(
                        __('Plugin').' '.$ini_array['plugin_definition']['name'].' '.__('Registered successfully')
                    );
                }

                unlink($config['attachment_store'].'/plugin_definition.ini');
            }
        }
    }
}

if (is_metaconsole() === true) {
    enterprise_hook('close_meta_frame');
}
