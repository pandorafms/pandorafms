<?php
/**
 * Manage plugins.
 *
 * @category   Utility
 * @package    Pandora FMS
 * @subpackage Plugins
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2023 Artica Soluciones Tecnologicas
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

// Begin.
global $config;

require_once $config['homedir'].'/include/functions_plugins.php';

if (is_ajax()) {
    $get_plugin_description = get_parameter('get_plugin_description');
    $get_list_modules_and_component_locked_plugin = (bool) get_parameter('get_list_modules_and_component_locked_plugin', 0);

    if ($get_plugin_description) {
        $id_plugin = get_parameter('id_plugin');

        $description = db_get_value_filter('description', 'tplugin', ['id' => $id_plugin]);
        $preload = io_safe_output($description);
        $preload = str_replace("\n", '<br>', $preload);

        echo $preload;
        return;
    }

    if ($get_list_modules_and_component_locked_plugin) {
        $id_plugin = (int) get_parameter('id_plugin', 0);

        $network_components = db_get_all_rows_filter(
            'tnetwork_component',
            ['id_plugin' => $id_plugin]
        );
        if (empty($network_components)) {
            $network_components = [];
        }

        $modules = db_get_all_rows_filter(
            'tagente_modulo',
            [
                'delete_pending' => 0,
                'id_plugin'      => $id_plugin,
            ]
        );
        if (empty($modules)) {
            $modules = [];
        }

        $table = new stdClass();
        $table->width = '100%';
        $table->head[0] = __('Network Components');
        // $table->data = [];
        foreach ($network_components as $net_comp) {
            $table->data[] = [$net_comp['name']];
        }

        if (empty($table->data) === false) {
            html_print_table($table);
        }

        $table = new stdClass();
        $table->width = '100%';
        $table->head[0] = __('Agent');
        $table->head[1] = __('Module');
        foreach ($modules as $mod) {
            $agent_name = '<a href="'.ui_get_full_url('index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$mod['id_agente']).'">'.modules_get_agentmodule_agent_alias(
                $mod['id_agente_modulo']
            ).'</a>';


            $table->data[] = [
                $agent_name,
                $mod['nombre'],
            ];
        }

        if (!empty($table->data)) {
            html_print_table($table);
        }

        return;
    }
}


require_once $config['homedir'].'/include/functions_filemanager.php';

check_login();

if (! check_acl($config['id_user'], 0, 'PM') && ! check_acl($config['id_user'], 0, 'AW')) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Plugin Management'
    );
    include 'general/noaccess.php';
    return;
}

enterprise_include_once('meta/include/functions_components_meta.php');

$view = get_parameter('view', '');
$create = get_parameter('create', '');
$filemanager = (bool) get_parameter('filemanager', false);
$edit_file = get_parameter('edit_file', false);
$update_file = get_parameter('update_file', false);
$plugin_command = get_parameter('plugin_command', '');
$tab = get_parameter('tab', '');

if ($view != '') {
    $form_id = $view;
    $plugin = db_get_row('tplugin', 'id', $form_id);
    $form_name = $plugin['name'];
    $form_description = $plugin['description'];
    $form_max_timeout = $plugin['max_timeout'];
    $form_max_retries = $plugin['max_retries'];
    if (empty($plugin_command)) {
        $form_execute = $plugin['execute'];
    } else {
        $form_execute = $plugin_command;
    }

    $form_plugin_type = $plugin['plugin_type'];
    $macros = $plugin['macros'];
    $parameters = $plugin['parameters'];
}

if ($create != '') {
    $form_id = 0;
    $form_name = '';
    $form_description = '';
    $form_max_timeout = 15;
    $form_max_retries = 1;
    $form_execute = $plugin_command;
    $form_plugin_type = 0;
    $form_parameters = '';
    $macros = '';
    $parameters = '';
}

// END LOAD VALUES
// =====================================================================
// INIT FILEMANAGER
// =====================================================================
if ($filemanager) {
    if ($edit_file) {
        $location_file = get_parameter('location_file', '');
        $filename = array_pop(explode('/', $location_file));
        $file = file_get_contents($location_file);
        echo '<h4>'.__('Edit file').' '.$filename.'</h4>';
        // echo "<a href='index.php?sec=gagente&sec2=enterprise/godmode/agentes/collections&action=file&id=" . $collection['id'] . "&directory=" . $relative_dir . "&hash2=" . $hash2 . "'>" . __('Back to file explorer') . "</a>";
        echo "<form method='post' action='index.php?sec=gservers&sec2=godmode/servers/plugin&filemanager=1"."&update_file=1'>";
        // html_print_input_hidden('location_file', $locationFile);
        echo "<table class='w98p'>";
        echo '<tr>';
        echo '<th>'.__('Edit').'</th>';
        echo '</tr>';
        echo '<tr>';
        echo '<td>';
        echo "<textarea name='content_file' class='w100p height_400px' >";
        echo $file;
        echo '</textarea>';
        echo '</td>';
        echo '</tr>';
        echo "<tr align='right'>";
        echo '<td>';
        html_print_input_hidden('location_file', $location_file);

        echo __('Compatibility mode').':';
        $options = [
            'unix'    => 'Unix',
            'windows' => 'Windows',
        ];
        html_print_select($options, 'compatibility', $compatibility);
        echo " <input type='submit' name='submit' value='".__('Update')."' class='sub upd' />";
        echo '</td>';
        echo '</tr>';
        echo '</table>';
        echo '</form>';
    } else {
        if ($update_file) {
            $location_file = get_parameter('location_file', '');
            $contentFile = io_safe_output(get_parameter('content_file', ''));
            $compatibility = get_parameter('compatibility', 'unix');
            $is_win_compatible = strpos($contentFile, "\r\n");
            // If is win compatible and the compatibility must be unix
            if ($is_win_compatible !== false && $compatibility == 'unix') {
                $contentFile = str_replace("\r\n", "\n", $contentFile);
            } else if ($is_win_compatible === false && $compatibility == 'windows') {
                // If is unix compatible and the compatibility must be win
                $contentFile = str_replace("\n", "\r\n", $contentFile);
            }

            $result = file_put_contents($location_file, $contentFile);
        }

        $id_plugin = (int) get_parameter('id_plugin', 0);

        // Add custom directories here.
        $fallback_directory = 'attachment/plugin';
        // Get directory.
        $directory = (string) get_parameter('directory');
        if (empty($directory) === true) {
            $directory = $fallback_directory;
        } else {
            $directory = str_replace('\\', '/', $directory);
            $directory = filemanager_safe_directory($directory, $fallback_directory);
        }

        $base_url = 'index.php?sec=gservers&sec2=godmode/servers/plugin';
        $setup_url = $base_url.'&filemanager=1&tab=Attachments';
        $tab = get_parameter('tab', null);
        $tabs = [
            'list'    => [
                'text'   => '<a href="'.$base_url.'">'.html_print_image(
                    'images/see-details@svg.svg',
                    true,
                    [
                        'title' => __('Plugins'),
                        'class' => 'invert_filter main_menu_icon',
                    ]
                ).'</a>',
                'active' => (bool) ($tab != 'Attachments'),
            ],
            'options' => [
                'text'   => '<a href="'.$setup_url.'">'.html_print_image(
                    'images/file-collection@svg.svg',
                    true,
                    [
                        'title' => __('Attachments'),
                        'class' => 'invert_filter main_menu_icon',
                    ]
                ).'</a>',
                'active' => (bool) ($tab == 'Attachments'),
            ],
        ];

        if ($tab === 'Attachments') {
            $helpHeader  = '';
            $titleHeader = __('Index of attachment/plugin');
        } else {
            $helpHeader  = 'servers_ha_clusters_tab';
            $titleHeader = __('Plug-ins registered on %s', get_product_name());
        }

        // Header.
        ui_print_standard_header(
            $titleHeader,
            'images/gm_servers.png',
            false,
            $helpHeader,
            false,
            $tabs,
            [
                [
                    'link'  => '',
                    'label' => __('Servers'),
                ],
                [
                    'link'  => '',
                    'label' => __('Plugins'),
                ],
            ]
        );

        $real_directory = realpath($config['homedir'].'/'.$directory);


        $chunck_url = '&view='.$id_plugin;
        if ($id_plugin == 0) {
            $chunck_url = '&create=1';
        }

        $upload_file_or_zip = (bool) get_parameter('upload_file_or_zip');
        $create_text_file = (bool) get_parameter('create_text_file');

        $default_real_directory = realpath($config['homedir'].'/'.$fallback_directory);

        if ($upload_file_or_zip === true) {
            upload_file($upload_file_or_zip, $default_real_directory, $real_directory);
        }

        if ($create_text_file === true) {
            create_text_file($default_real_directory, $real_directory);
        }

        filemanager_file_explorer(
            $real_directory,
            $directory,
            'index.php?sec=gservers&sec2=godmode/servers/plugin&filemanager=1&id_plugin='.$id_plugin.'&tab=Attachments',
            $fallback_directory,
            true,
            false,
            'index.php?sec=gservers&sec2=godmode/servers/plugin'.$chunck_url.'&plugin_command=[FILE_FULLPATH]&id_plugin='.$id_plugin,
            true,
            0775,
            false,
            ['all' => true]
        );
    }

    return;
}

// =====================================================================
// END FILEMANAGER
// =====================================================================
// =====================================================================
// SHOW THE FORM
// =====================================================================
$sec = 'gservers';

if (empty($create) === false || empty($view) === false) {
    $management_allowed = is_management_allowed();

    if (is_metaconsole() === true) {
        components_meta_print_header();
        $sec = 'advanced';
        if ($management_allowed === false) {
            ui_print_warning_message(__('To manage plugin you must activate centralized management'));
        }
    } else {
        // Header.
        ui_print_standard_header(
            (empty($create) === false) ? __('Plugin registration') : __('Plugin update'),
            'images/gm_servers.png',
            false,
            '',
            true,
            [],
            [
                [
                    'link'  => '',
                    'label' => __('Servers'),
                ],
                [
                    'link'  => 'index.php?sec=gservers&sec2=godmode/servers/plugin',
                    'label' => __('Plugins'),
                ],
            ]
        );

        if ($management_allowed === false) {
            ui_print_warning_message(
                __(
                    'This console is not manager of this environment,
        		please manage this feature from centralized manager console (Metaconsole).'
                )
            );
        }
    }

    $plugin_id = (int) get_parameter('view');
    $locked = true;

    // If we have plugin id (update mode) and this plugin used by any module or component
    // The command configuration will be locked
    if ($plugin_id > 0) {
        $modules_using_plugin = db_get_value_filter('count(*)', 'tagente_modulo', ['delete_pending' => 0, 'id_plugin' => $plugin_id]);
        $components_using_plugin = db_get_value_filter('count(*)', 'tnetwork_component', ['id_plugin' => $plugin_id]);
        if (($components_using_plugin + $modules_using_plugin) == 0) {
            $locked = false;
        }
    } else {
        $locked = false;
    }

    $disabled = ($locked === true) ? 'readonly="readonly"' : '';

    if (empty($create) === true) {
        $formAction = 'index.php?sec=gservers&sec2=godmode/servers/plugin&tab='.$tab.'&update_plugin='.$plugin_id.'&pure='.$config['pure'];
    } else {
        $formAction = 'index.php?sec=gservers&sec2=godmode/servers/plugin&tab='.$tab.'&create_plugin=1&pure='.$config['pure'];
    }

    $formPluginType = [
        0 => __('Standard'),
        1 => __('Nagios'),
    ];

    echo '<form class="max_floating_element_size" name="plugin" method="post" action="'.$formAction.'">';

    $table = new stdClass();
    $table->id = 'table-form';
    $table->class = 'databox filter-table-adv';
    $table->style = [];
    $table->data['plugin_name_captions'] = $data;
    $table->size[0] = '50%';
    $table->size[1] = '50%';

    $table->data = [];
    // General title.
    $data[0] = html_print_div([ 'class' => 'section_table_title', 'content' => __('General')], true);
    $table->data['general_title'] = $data;

    $data = [];
    $data[0] = html_print_label_input_block(
        __('Name'),
        html_print_input_text('form_name', $form_name, '', 100, 255, true, false, false, '')
    );
    $table->data['plugin_name_captions'] = $data;

    $data = [];

    $data[0] = html_print_label_input_block(
        __('Plugin type'),
        html_print_select($formPluginType, 'form_plugin_type', $form_plugin_type, '', '', 0, true, false, true, '', false, 'width: 100%')
    );

    $timeoutContent = [];
    $timeoutContent[] = '<div>'.html_print_extended_select_for_time('form_max_timeout', $form_max_timeout, '', '', '0', false, true).'</div>';
    $timeoutContent[] = ui_print_input_placeholder(__('This value only will be applied if is minor than the server general configuration plugin timeout').'<br>'.__('If you set a 0 seconds timeout, the server plugin timeout will be used'), true);
    $data[1] = html_print_label_input_block(
        __('Max. timeout'),
        html_print_div(
            [
                'class'   => 'flex flex_column',
                'content' => implode('', $timeoutContent),
            ],
            true
        )
    );

    $table->data['plugin_type_timeout'] = $data;

    $data = [];
    $data[0] = html_print_label_input_block(
        __('Description'),
        html_print_textarea('form_description', 4, 50, $form_description, '', true)
    );

    $table->colspan['plugin_desc_inputs'][0] = 2;
    $table->data['plugin_desc_inputs'] = $data;


    // Command title.
    $data = [];
    $data[0] = html_print_div([ 'class' => 'section_table_title', 'content' => __('Command')], true);
    $table->data['command_title'] = $data;

    $data = [];
    $formExecuteContent = [];
    $formExecuteContent[] = html_print_input_text('form_execute', $form_execute, '', 50, 255, true, false, false, '', 'command_component command_advanced_conf w100p');
    $formExecuteContent[] = html_print_anchor(
        [
            'title'   => __('Save changes'),
            'href'    => 'index.php?sec=gservers&sec2=godmode/servers/plugin&filemanager=1&tab=Attachments&id_plugin='.$form_id,
            'class'   => 'mrgn_lft_5px',
            'content' => html_print_image('images/validate.svg', true, ['class' => 'invert_filter main_menu_icon'], false, true),
        ],
        true
    );

    $data[0] = html_print_label_input_block(
        __('Plugin command'),
        html_print_div(['class' => 'flex-row-center', 'content' => implode('', $formExecuteContent)], true).ui_print_input_placeholder(
            __('Specify interpreter and plugin path. The server needs permissions to run it.'),
            true
        )
    );

    // $data[0] = html_print_div(['class' => 'flex-row-center', 'content' => implode('', $formExecuteContent)], true);
    $table->data['plugin_command_inputs'] = $data;
    $table->colspan['plugin_command_inputs'][0] = 2;

    $data = [];
    $data[0] = html_print_label_input_block(
        __('Plug-in parameters'),
        html_print_input_text(
            'form_parameters',
            $parameters,
            '',
            100,
            255,
            true,
            false,
            false,
            '',
            'command_component command_advanced_conf text_input'
        )
    );

    $table->data['plugin_parameters_inputs'] = $data;
    $table->colspan['plugin_parameters_inputs'][0] = 2;

    $data = [];
    // $data[0] = __('Command preview');
    // $table->data['plugin_preview_captions'] = $data;
    // $data = [];
    // $data[0] = html_print_div(['id' => 'command_preview', 'class' => 'mono'], true);
    $data[0] = html_print_label_input_block(
        __('Command preview'),
        html_print_div(['id' => 'command_preview', 'class' => 'mono'], true)
    );
    $table->data['plugin_preview_inputs'] = $data;
    $table->colspan['plugin_preview_inputs'][0] = 2;

    // Parameters macros title.
    $data = [];
    $data[0] = html_print_div([ 'class' => 'section_table_title', 'content' => __('Parameters macros')], true);
    $table->data['parameters_macros_title'] = $data;

    $macros = json_decode($macros, true);

    // The next row number is plugin_9
    $next_name_number = 9;
    $i = 1;
    while (1) {
        // Always print at least one macro.
        if ((isset($macros[$i]) === false || $macros[$i]['desc'] == '') && $i > 1) {
            break;
        }

        $macro_desc_name = 'field'.$i.'_desc';
        $macro_desc_value = '';
        $macro_help_name = 'field'.$i.'_help';
        $macro_help_value = '';
        $macro_value_name = 'field'.$i.'_value';
        $macro_value_value = '';
        $macro_name_name = 'field'.$i.'_macro';
        $macro_name = '_field'.$i.'_';
        $macro_hide_value_name = 'field'.$i.'_hide';
        $macro_hide_value_value = 0;

        if (isset($macros[$i]['desc'])) {
            $macro_desc_value = $macros[$i]['desc'];
        }

        if (isset($macros[$i]['help'])) {
            $macro_help_value = $macros[$i]['help'];
        }

        if (isset($macros[$i]['value'])) {
            $macro_value_value = $macros[$i]['value'];
        }

        if (isset($macros[$i]['hide'])) {
            $macro_hide_value_value = $macros[$i]['hide'];

            // Decrypt hidden macros.
            $macro_value_value = io_output_password($macro_value_value);
        }

        $datam = [];
        $datam[0] = html_print_label_input_block(
            __('Description').'<span class="normal_weight">('.$macro_name.')</span>',
            html_print_input_text_extended($macro_desc_name, $macro_desc_value, 'text-'.$macro_desc_name, '', 30, 255, false, '', "class='command_macro text_input'", true)
        );
        $datam[0] .= html_print_input_hidden($macro_name_name, $macro_name, true);

        $datam[1] = html_print_label_input_block(
            __('Default value').'<span class="normal_weight">('.$macro_name.')</span>',
            html_print_input_text_extended($macro_value_name, $macro_value_value, 'text-'.$macro_value_name, '', 30, 255, false, '', "class='command_component command_macro text_input'", true)
        );

        $table->data['plugin_'.$next_name_number] = $datam;

        $next_name_number++;

        $table->colspan['plugin_'.$next_name_number][1] = 2;

        $datam = [];
        $datam = html_print_label_input_block(
            __('Hide value'),
            html_print_checkbox_switch(
                $macro_hide_value_name,
                1,
                $macro_hide_value_value,
                0,
                '',
                ['class' => 'command_macro'],
                true,
                'checkbox-'.$macro_hide_value_name
            ).ui_print_input_placeholder(
                __('This field will show up as dots like a password'),
                true
            )
        );

        $table->data['plugin_'.$next_name_number] = $datam;
        $next_name_number++;

        // $table->colspan['plugin_'.$next_name_number][1] = 3;
        $datam = [];
        $datam[0] = html_print_label_input_block(
            __('Help').'<span class="normal_weight"> ('.$macro_name.')</span>',
            html_print_textarea(
                $macro_help_name,
                6,
                100,
                $macro_help_value,
                'class="command_macro" class="w97p"',
                true
            )
        );

        $table->colspan['plugin_'.$next_name_number][0] = 2;
        $table->data['plugin_'.$next_name_number] = $datam;
        $next_name_number++;
        $i++;
    }

    // Add/Delete buttons
    $datam = [];
    $buttons = '';
    if (!$locked) {
        $buttons = html_print_anchor(
            [
                'id'      => 'add_macro_btn',
                'href'    => 'javascript:;',
                'content' => '<span>'.__('Add macro').'</span>'.html_print_image(
                    'images/plus@svg.svg',
                    true,
                    ['class' => 'invert_filter main_menu_icon']
                ),
            ],
            true
        );

        $buttons .= html_print_div(['id' => 'next_macro', 'class' => 'invisible', 'content' => $i], true);
        $buttons .= html_print_div(['id' => 'next_row', 'class' => 'invisible', 'content' => $next_name_number], true);

        $delete_macro_style = '';
        if ($i <= 2) {
            $delete_macro_style = 'display:none;';
        }

        // $datam[1] = '<div id="delete_macro_button" style="'.$delete_macro_style.'">'.'<a href="javascript:;">'.'<span class="bolder">'.__('Delete macro').'</span>'.'&nbsp;'.html_print_image('images/delete.svg', true, ['class' => 'main_menu_icon invert_filter']).'</a>'.'</div>';
        $buttons .= html_print_anchor(
            [
                'id'      => 'delete_macro_button',
                'style'   => $delete_macro_style,
                'href'    => 'javascript:;',
                'content' => '<span>'.__('Remove macro').'</span>'.html_print_image(
                    'images/delete.svg',
                    true,
                    ['class' => 'main_menu_icon invert_filter mrgn_right_10px']
                ),
            ],
            true
        );

        $datam[0] = html_print_div(
            [
                'style'   => 'flex-direction: row-reverse;justify-content: flex-start;',
                'content' => $buttons,
            ],
            true
        );
        $table->colspan['plugin_action'][0] = 2;
    } else {
        // $table->colspan['plugin_action'][0] = 4;
    }

    // $table->rowstyle['plugin_action'] = 'text-align:center';
    $table->data['plugin_action'] = $datam;


    if (defined('METACONSOLE')) {
        $table->head[0] = __('Parameters macros');
        $table->head_colspan[0] = 4;
        $table->headstyle[0] = 'text-align: center';
        html_print_table($table);
    } else {
        // echo '<fieldset>'.'<legend>'.__('Parameters macros').'</legend>';
        html_print_div(
            [
                'class'   => 'info_table',
                'content' => html_print_table($table, true),
            ]
        );

        // echo '</fieldset>';
    }

    echo '<table width="100%">';

    echo '<tr><td align="right">';

    $buttons = '';

    if (empty($create) === false) {
        $buttons .= html_print_submit_button(
            __('Create'),
            'crtbutton',
            false,
            [ 'icon' => 'wand' ],
            true
        );
    } else {
        $buttons .= html_print_submit_button(
            __('Update'),
            'uptbutton',
            false,
            [ 'icon' => 'upd' ],
            true
        );
    }

    $buttons .= html_print_go_back_button(
        'index.php?sec=gservers&sec2=godmode/servers/plugin',
        ['button_class' => ''],
        true
    );

    html_print_action_buttons(
        $buttons
    );

    echo '</form></table>';

    if (defined('METACONSOLE')) {
        echo '</td></tr>';
    }
} else {
    if (defined('METACONSOLE')) {
        components_meta_print_header();
        $sec = 'advanced';
        $management_allowed = is_management_allowed();
        if (!$management_allowed) {
            ui_print_warning_message(
                __('To manage plugin you must activate centralized management')
            );
        }
    } else {
        $base_url = 'index.php?sec=gservers&sec2=godmode/servers/plugin';
        $setup_url = $base_url.'&filemanager=1&tab=Attachments';
        $tab = get_parameter('tab', null);
        $tabs = [
            'list'    => [
                'text'   => '<a href="'.$base_url.'">'.html_print_image(
                    'images/see-details@svg.svg',
                    true,
                    [
                        'title' => __('Plugins'),
                        'class' => 'invert_filter main_menu_icon',
                    ]
                ).'</a>',
                'active' => (bool) ($tab != 'Attachments'),
            ],
            'options' => [
                'text'   => '<a href="'.$setup_url.'">'.html_print_image(
                    'images/file-collection@svg.svg',
                    true,
                    [
                        'title' => __('Attachments'),
                        'class' => 'invert_filter main_menu_icon',
                    ]
                ).'</a>',
                'active' => (bool) ($tab == 'Attachments'),
            ],
        ];

        if ($tab === 'Attachments') {
            $helpHeader  = '';
            $titleHeader = __('Index of attachment/plugin');
        } else {
            $helpHeader  = 'servers_ha_clusters_tab';
            $titleHeader = __('Plug-ins registered on %s', get_product_name());
        }

        // Header.
        ui_print_standard_header(
            $titleHeader,
            'images/gm_servers.png',
            false,
            $helpHeader,
            false,
            $tabs,
            [
                [
                    'link'  => '',
                    'label' => __('Servers'),
                ],
                [
                    'link'  => '',
                    'label' => __('Plugins'),
                ],
            ]
        );

        $management_allowed = is_management_allowed();
        if ($management_allowed === false) {
            ui_print_warning_message(
                __(
                    'This console is not manager of this environment,
        		please manage this feature from centralized manager console (Metaconsole).'
                )
            );
        }

        $is_windows = strtoupper(substr(PHP_OS, 0, 3)) == 'WIN';
        if ($is_windows) {
            echo '<div class="notify">';
            echo __('You need to create your own plugins with Windows compatibility');
            echo '</div>';
        }
    }


    // Update plugin.
    if (isset($_GET['update_plugin'])) {
        $plugin_id = get_parameter('update_plugin', 0);
        $plugin_name = get_parameter('form_name', '');
        $plugin_description = get_parameter('form_description', '');
        $plugin_max_timeout = get_parameter('form_max_timeout', '');
        $plugin_execute = get_parameter('form_execute', '');
        $plugin_plugin_type = get_parameter('form_plugin_type', '0');
        $parameters = get_parameter('form_parameters', '');

        // Get macros
        $i = 1;
        $macros = [];
        while (1) {
            $macro = (string) get_parameter('field'.$i.'_macro');
            if ($macro == '') {
                break;
            }

            $desc = (string) get_parameter('field'.$i.'_desc');
            $help = (string) get_parameter('field'.$i.'_help');
            $value = (string) get_parameter('field'.$i.'_value');
            $hide = get_parameter('field'.$i.'_hide');

            $macros[$i]['macro'] = $macro;
            $macros[$i]['desc'] = $desc;
            $macros[$i]['help'] = $help;
            if ($hide == 1) {
                $macros[$i]['value'] = io_input_password($value);
            } else {
                $macros[$i]['value'] = $value;
            }

            $macros[$i]['hide'] = $hide;

            $i++;
        }

        $macros = io_json_mb_encode($macros);

        $values = [
            'name'        => $plugin_name,
            'description' => $plugin_description,
            'max_timeout' => $plugin_max_timeout,
            'execute'     => $plugin_execute,
            'plugin_type' => $plugin_plugin_type,
            'parameters'  => $parameters,
            'macros'      => $macros,
        ];

        $result = false;
        if ($values['name'] != '' && $values['execute'] != '') {
            $result = db_process_sql_update(
                'tplugin',
                $values,
                ['id' => $plugin_id]
            );
        }

        if (! $result) {
            ui_print_error_message(__('Problem updating plugin'));
        } else {
            ui_print_success_message(__('Plugin updated successfully'));
        }
    }

    // Create plugin
    if (isset($_GET['create_plugin'])) {
        $plugin_name = get_parameter('form_name', '');
        $plugin_description = get_parameter('form_description', '');
        $plugin_max_timeout = get_parameter('form_max_timeout', '');
        $plugin_execute = get_parameter('form_execute', '');
        $plugin_plugin_type = get_parameter('form_plugin_type', '0');
        $plugin_parameters = get_parameter('form_parameters', '');

        // Get macros
        $i = 1;
        $macros = [];
        while (1) {
            $macro = (string) get_parameter('field'.$i.'_macro');
            if ($macro == '') {
                break;
            }

            $desc = (string) get_parameter('field'.$i.'_desc');
            $help = (string) get_parameter('field'.$i.'_help');
            $value = (string) get_parameter('field'.$i.'_value');
            $hide = get_parameter('field'.$i.'_hide');

            $macros[$i]['macro'] = $macro;
            $macros[$i]['desc'] = $desc;
            $macros[$i]['help'] = $help;
            if ($hide == 1) {
                $macros[$i]['value'] = io_input_password($value);
            } else {
                $macros[$i]['value'] = $value;
            }

            $macros[$i]['hide'] = $hide;
            $i++;
        }

        $macros = io_json_mb_encode($macros);

        $values = [
            'name'        => $plugin_name,
            'description' => $plugin_description,
            'max_timeout' => $plugin_max_timeout,
            'execute'     => $plugin_execute,
            'plugin_type' => $plugin_plugin_type,
            'parameters'  => $plugin_parameters,
            'macros'      => $macros,
        ];

        $result = false;
        if ($values['name'] != '' && $values['execute'] != '') {
            $result = db_process_sql_insert('tplugin', $values);
        }

        if (! $result) {
            ui_print_error_message(__('Problem creating plugin'));
        } else {
            ui_print_success_message(__('Plugin created successfully'));
        }
    }

    if (isset($_GET['kill_plugin'])) {
        // if delete alert
        $plugin_id = get_parameter('kill_plugin', 0);

        $result = db_process_sql_delete('tplugin', ['id' => $plugin_id]);

        if (!is_metaconsole()) {
            if (!$result) {
                ui_print_error_message(__('Problem deleting plugin'));
            } else {
                ui_print_success_message(__('Plugin deleted successfully'));
            }
        }

        if ((int) $plugin_id > 0) {
            // Delete all iformation related with this plugin.
            $result = plugins_delete_plugin($plugin_id);
            if (empty($result) === false) {
                ui_print_error_message(
                    implode('<br>', $result)
                );
            } else {
                ui_print_success_message(__('Plugin deleted successfully'));
            }
        }
    }

    // If not edition or insert, then list available plugins.
    $rows = db_get_all_rows_sql('SELECT * FROM tplugin ORDER BY name');

    if ($rows !== false) {
        $pluginTable = new stdClass();
        $pluginTable->id = 'plugin_table';
        $pluginTable->class = 'info_table';

        $pluginTable->head = [];
        $pluginTable->head[0] = __('Name');
        $pluginTable->head[1] = __('Type');
        $pluginTable->head[2] = __('Command');
        if ($management_allowed === true) {
            $pluginTable->head[3] = __('Operations');
        }

        $pluginTable->data = [];

        foreach ($rows as $k => $row) {
            if ($management_allowed === true) {
                $tableActionButtons = [];
                $pluginNameContent = html_print_anchor(
                    [
                        'href'    => 'index.php?sec=$sec&sec2=godmode/servers/plugin&view='.$row['id'].'&tab=plugins&pure='.$config['pure'],
                        'content' => $row['name'],
                    ],
                    true
                );

                // Show it is locket.
                $modules_using_plugin = db_get_value_filter(
                    'count(*)',
                    'tagente_modulo',
                    [
                        'delete_pending' => 0,
                        'id_plugin'      => $row['id'],
                    ]
                );
                $components_using_plugin = db_get_value_filter(
                    'count(*)',
                    'tnetwork_component',
                    ['id_plugin' => $row['id']]
                );
                if (($components_using_plugin + $modules_using_plugin) > 0) {
                    $tableActionButtons[] = html_print_anchor(
                        [
                            'href'    => 'javascript: show_locked_dialog('.$row['id'].', \''.$row['name'].'\');',
                            'content' => html_print_image(
                                'images/policy@svg.svg',
                                true,
                                [
                                    'title' => __('Lock'),
                                    'class' => 'invert_filter main_menu_icon',
                                ]
                            ),
                        ],
                        true
                    );
                }

                $tableActionButtons[] = html_print_anchor(
                    [
                        'href'    => 'index.php?sec=$sec&sec2=godmode/servers/plugin&tab=$tab&view='.$row['id'].'&tab=plugins&pure='.$config['pure'],
                        'content' => html_print_image(
                            'images/edit.svg',
                            true,
                            [
                                'title' => __('Edit'),
                                'class' => 'invert_filter main_menu_icon ',
                            ]
                        ),
                    ],
                    true
                );

                if ((bool) $row['no_delete'] === false) {
                    $tableActionButtons[] = html_print_anchor(
                        [
                            'href'    => 'index.php?sec=$sec&sec2=godmode/servers/plugin&tab=$tab&kill_plugin='.$row['id'].'&tab=plugins&pure='.$config['pure'],
                            'onClick' => 'javascript: if (!confirm(\''.__('All the modules that are using this plugin will be deleted').'. '.__('Are you sure?').'\')) return false;',
                            'content' => html_print_image(
                                'images/delete.svg',
                                true,
                                [
                                    'title' => __('Delete'),
                                    'class' => 'invert_filter main_menu_icon',
                                ]
                            ),
                        ],
                        true
                    );
                }
            } else {
                $pluginNameContent = $row['name'];
            }

            $pluginTable->data[$k][0] = $pluginNameContent;
            $pluginTable->data[$k][1] = ((int) $row['plugin_type'] === 0) ? __('Standard') : __('Nagios');
            $pluginTable->data[$k][2] = $row['execute'];

            if ($management_allowed === true) {
                $pluginTable->data[$k][3] = html_print_div(
                    [
                        'class'   => 'table_action_buttons',
                        'content' => implode('', $tableActionButtons),
                    ],
                    true
                );
            }
        }

        html_print_table($pluginTable);
    } else {
        ui_print_info_message(['no_close' => true, 'message' => __('There are no plugins in the system') ]);
    }

    if ($management_allowed === true) {
        echo '<form name="plugin" method="POST" action="index.php?sec=gservers&sec2=godmode/servers/plugin&tab='.$tab.'&create=1&pure='.$config['pure'].'">';

        html_print_action_buttons(
            html_print_submit_button(
                __('Add plugin'),
                'crtbutton',
                false,
                [ 'icon' => 'wand' ],
                true
            ),
            ['type' => 'form_action']
        );

        echo '</form>';
    }

    // The '%s' will be replaced in the javascript code of the function 'show_locked_dialog'.
    echo "<div id='dialog_locked' title='".__('List of modules and components created by "%s" ')."' class='invisible left'>";
    echo '</div>';
}

ui_require_javascript_file('pandora_modules');
?>

<script type="text/javascript">

    var locked = <?php echo (int) json_encode((int) $locked); ?>;

    function update_preview() {
        var command = $('#text-form_execute').val();
        var parameters = $('#text-form_parameters').val();
        var i = 1;

        while (1) {
            if ($('#text-field' + i + '_value').val() == undefined) {
                break;
            }

            if ($('#text-field'+i+'_value').val() != '') {
                parameters = parameters
                    .replace('_field' + i + '_',
                        $('#text-field' + i + '_value').val());
            }

            i++;
        }

        $('#command_preview').html(_.escape(command) + ' ' + _.escape(parameters));
    }

    function show_locked_dialog(id_plugin, plugin_name) {
        var parameters = {};
        parameters['page'] = "godmode/servers/plugin";
        parameters["get_list_modules_and_component_locked_plugin"] = 1;
        parameters["id_plugin"] = id_plugin;

        $.ajax({
            type: "POST",
            url: "<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
            data: parameters,
            dataType: "html",
            success: function(data) {
                var title = 'List of modules and components created by "'+ plugin_name +'"';
                $("#dialog_locked")
                    .html(data)
                    .dialog ({
                        title: title,
                        resizable: true,
                        draggable: true,
                        modal: true,
                        overlay: {
                            opacity: 0.5,
                            background: "black"
                        },
                        width: 650,
                        height: 500
                    })
                    .show ();
            }
        });
    }

    $(document).ready(function() {
        // Add macro
        var add_macro_click_event = function (event) {
            new_macro('table-form-plugin_', function () {
                // Remove the locked images and enable the inputs
                if (arguments.length > 0) {
                    var rows = _.toArray(arguments);
                    _.each(rows, function(row, index) {
                        row.find('input.command_macro, textarea.command_macro')
                            .prop('readonly', false)
                            .prop('disabled', false)
                            .siblings('img.command_macro.lock')
                                .remove();
                    });
                }
            });
            update_preview();
        }

        if (locked === 0) {
            $('a#add_macro_btn').click(add_macro_click_event);
        }

        // Delete macro
        var delete_macro_click_event = function (event) {
            delete_macro_form('table-form-plugin_');
            update_preview();
        }
        $('a#delete_macro_button').click(delete_macro_click_event);

        update_preview();

        $('.command_component').keyup(function() {
            update_preview();
        });
    });


    var add_macro_click_locked_event = function (event) {
        var message = '<?php echo __('Some modules or components are using the plugin'); ?>.'
                    + '\n' + '<?php echo __('The modules or components should be updated manually or using the bulk operations for plugins after this change'); ?>.'
                    + '\n'
                    + '\n' + '<?php echo __('Are you sure you want to perform this action?'); ?>';

        if (!confirm(message)) {
            event.stopImmediatePropagation();
            event.preventDefault();
        }
    }

    var macros_click_locked_event = function (event) {
        alert("<?php echo __('The plugin macros cannot be updated because some modules or components are using the plugin'); ?>");
    }

    if (locked) {
        $('a#add_macro_btn').click(add_macro_click_locked_event);
    }


</script>
