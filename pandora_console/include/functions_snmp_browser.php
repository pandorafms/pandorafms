<?php
/**
 * Extension to manage a list of gateways and the node address where they should
 * point to.
 *
 * @category   Extensions
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

require_once $config['homedir'].'/include/functions_config.php';
enterprise_include_once(
    $config['homedir'].'/enterprise/include/pdf_translator.php'
);
enterprise_include_once(
    $config['homedir'].'/enterprise/include/functions_metaconsole.php'
);

// Date format for nfdump.
global $nfdump_date_format;
$nfdump_date_format = 'Y/m/d.H:i:s';/**
                                     * Generates a Tree with given $tree information.
                                     *
                                     * Selects all netflow filters (array (id_name => id_name)) or filters filtered
                                     * Used also in Cloud Wizard.
                                     *
                                     * @param string  $tree            SNMP tree returned by snmp_broser_get_tree.
                                     * @param string  $id              Level ID. Do not set, used for recursion.
                                     * @param string  $depth           Branch depth. Do not set, used for recursion.
                                     * @param integer $last            Last.
                                     * @param array   $last_array      Last_array.
                                     * @param string  $sufix           Sufix.
                                     * @param array   $checked         Checked.
                                     * @param boolean $descriptive_ids Descriptive_ids.
                                     * @param string  $previous_id     Previous_id.
                                     *
                                     * @return string HTML code with complete tree.
                                     */


function snmp_browser_get_html_tree(
    $tree,
    $id=0,
    $depth=0,
    $last=0,
    $last_array=[],
    $sufix=false,
    $checked=[],
    $descriptive_ids=false,
    $previous_id=''
) {
    static $url = false;

    $output = '';

    // Get the base URL for images.
    if ($url === false) {
        $url = ui_get_full_url('operation/tree', false, false, false);
    }

    // Leaf.
    if (empty($tree['__LEAVES__'])) {
        return '';
    }

    $count = 0;
    $total = (count(array_keys($tree['__LEAVES__'])) - 1);
    $last_array[$depth] = $last;
    $class = 'item_'.$depth;

    if ($depth > 0) {
        $output .= '<ul id="ul_'.$id.'" class="mrgn_0px pdd_0px invisible">';
    } else {
        $output .= '<ul id="ul_'.$id.'" class="mrgn_0px pdd_0px">';
    }

    foreach ($tree['__LEAVES__'] as $level => $sub_level) {
        // Id used to expand leafs.
        $sub_id = time().rand(0, getrandmax());
        // Display the branch.
        $output .= '<li id="li_'.$sub_id.'" class="'.$class.' mrgn_0px pdd_0px flex_center">';

        // Indent sub branches.
        for ($i = 1; $i <= $depth; $i++) {
            if ($last_array[$i] == 1) {
                $output .= '<img src="'.$url.'/no_branch.png" class="vertical_middle">';
            } else {
                $output .= '<img src="'.$url.'/branch.png" class="vertical_middle">';
            }
        }

        // Branch.
        if (! empty($sub_level['__LEAVES__'])) {
            $output .= "<a id='anchor_$sub_id' onfocus='javascript: this.blur();' href='javascript: toggleTreeNode(\"$sub_id\", \"$id\");'>";
            if ($depth == 0 && $count == 0) {
                if ($count == $total) {
                    $output .= '<img src="'.$url.'/one_closed.png" class="vertical_middle">';
                } else {
                    $output .= '<img src="'.$url.'/first_closed.png" class="vertical_middle">';
                }
            } else if ($count == $total) {
                $output .= '<img src="'.$url.'/last_closed.png" class="vertical_middle">';
            } else {
                $output .= '<img src="'.$url.'/closed.png" class="vertical_middle">';
            }

            $output .= '</a>';
        }

        // Leave.
        else {
            if ($depth == 0 && $count == 0) {
                if ($count == $total) {
                    $output .= '<img src="'.$url.'/no_branch.png" class="vertical_middle">';
                } else {
                    $output .= '<img src="'.$url.'/first_leaf.png" class="vertical_middle">';
                }
            } else if ($count == $total) {
                $output .= '<img src="'.$url.'/last_leaf.png" class="vertical_middle">';
            } else {
                $output .= '<img src="'.$url.'/leaf.png" class="vertical_middle">';
            }
        }

        // Branch or leave with branches!
        if (isset($sub_level['__OID__'])) {
            $output .= "<a onfocus='javascript: this.blur();' href='javascript: snmpGet(\"".addslashes($sub_level['__OID__'])."\");'>";
            $output .= '<img src="'.$url.'/../../images/details.svg" class="main_menu_icon invert_filter vertical_middle">';
            $output .= '</a>';
        }

        $checkbox_name_sufix = ($sufix === true) ? '_'.$level : '';
        if ($descriptive_ids === true) {
            $checkbox_name = 'create_'.$sub_id.$previous_id.$checkbox_name_sufix;
        } else {
            $checkbox_name = 'create_'.$sub_id.$checkbox_name_sufix;
        }

        $previous_id = $checkbox_name_sufix;
        $status = (!empty($checked) && isset($checked[$level]));
        $output .= html_print_checkbox($checkbox_name, 0, $status, true, false, '').'&nbsp;<span>'.$level.'</span>';
        if (isset($sub_level['__VALUE__'])) {
            $output .= '<span class="value invisible" class="invisible" >&nbsp;=&nbsp;'.io_safe_input($sub_level['__VALUE__']).'</span>';
        }

        $output .= '</li>';

        // Recursively print sub levels.
        $output .= snmp_browser_get_html_tree(
            $sub_level,
            $sub_id,
            ($depth + 1),
            (($count == $total) ? 1 : 0),
            $last_array,
            $sufix,
            $checked,
            $descriptive_ids,
            $previous_id
        );

        $count++;
    }

    $output .= '</ul>';

    return $output;
}


/**
 * Selects all netflow filters (array (id_name => id_name)) or filters filtered
 * This function is also being used while painting instances in AWS Cloud wiz.
 *
 * @param string  $tree            SNMP tree returned by snmp_broser_get_tree.
 * @param string  $id              Level ID. Do not set, used for recursion.
 * @param string  $depth           Branch depth. Do not set, used for recursion.
 * @param integer $last            Last.
 * @param array   $last_array      Last_array.
 * @param string  $sufix           Sufix.
 * @param array   $checked         Checked.
 * @param boolean $return          Return.
 * @param boolean $descriptive_ids Descriptive_ids.
 * @param string  $previous_id     Previous_id.
 *
 * @return string HTML code with complete tree.
 */
function snmp_browser_print_tree(
    $tree,
    $id=0,
    $depth=0,
    $last=0,
    $last_array=[],
    $sufix=false,
    $checked=[],
    $return=false,
    $descriptive_ids=false,
    $previous_id=''
) {
    $str = snmp_browser_get_html_tree(
        $tree,
        $id,
        $depth,
        $last,
        $last_array,
        $sufix,
        $checked,
        $descriptive_ids,
        $previous_id
    );

    if ($return === false) {
        echo $str;
    }

    return $str;
}


/**
 * Build the SNMP tree for the given SNMP agent.
 *
 * @param string      $target_ip               Target_ip.
 * @param string      $community               Community.
 * @param string      $starting_oid            Starting_oid.
 * @param string      $version                 Version.
 * @param string      $snmp3_auth_user         Snmp3_auth_user.
 * @param string      $snmp3_security_level    Snmp3_security_level.
 * @param string      $snmp3_auth_method       Snmp3_auth_method.
 * @param string      $snmp3_auth_pass         Snmp3_auth_pass.
 * @param string      $snmp3_privacy_method    Snmp3_privacy_method.
 * @param string      $snmp3_privacy_pass      Snmp3_privacy_pass.
 * @param string|null $snmp3_context_engine_id Snmp3_context_engine_id.
 *
 * @return array The SNMP tree.
 */
function snmp_browser_get_tree(
    $target_ip,
    $community,
    $starting_oid='.',
    $version='2c',
    $snmp3_auth_user='',
    $snmp3_security_level='',
    $snmp3_auth_method='',
    $snmp3_auth_pass='',
    $snmp3_privacy_method='',
    $snmp3_privacy_pass='',
    $snmp3_context_engine_id=null,
    $server_to_exec=0,
    $target_port=''
) {
    global $config;

    $output = get_snmpwalk(
        // Ip_target.
        $target_ip,
        // Snmp_version.
        $version,
        // Snmp_community.
        $community,
        // Snmp3_auth_user.
        $snmp3_auth_user,
        // Snmp3_security_level.
        $snmp3_security_level,
        // Snmp3_auth_method.
        $snmp3_auth_method,
        // Snmp3_auth_pass.
        $snmp3_auth_pass,
        // Snmp3_privacy_method.
        $snmp3_privacy_method,
        // Snmp3_privacy_pass.
        $snmp3_privacy_pass,
        // Quick_print.
        0,
        // Base_oid.
        $starting_oid,
        // Snmp_port.
        $target_port,
        // Server_to_exec.
        $server_to_exec,
        // Extra_arguments.
        '',
        // Format.
        ''
    );

    // Build the tree if output comes filled.
    if (empty($output) === false) {
        $oid_tree = ['__LEAVES__' => []];
        foreach ($output as $oid => $value) {
            // Parse the OID.
            $oid_len = strlen($oid);
            $group = 0;
            $sub_oid = '';
            $ptr = &$oid_tree['__LEAVES__'];

            for ($i = 0; $i < $oid_len; $i++) {
                // "X.Y.Z"
                if ($oid[$i] == '"') {
                    $group = ($group ^ 1);
                }

                // Move to the next element of the OID.
                if ($group == 0 && ($oid[$i] == '.' || ($oid[$i] == ':' && $oid[($i + 1)] == ':'))) {
                    // Skip the next ":".
                    if ($oid[$i] == ':') {
                        $i++;
                    }

                    // Starting dot.
                    if ($sub_oid == '') {
                        continue;
                    }

                    if (! isset($ptr[$sub_oid]) || ! isset($ptr[$sub_oid]['__LEAVES__'])) {
                        $ptr[$sub_oid]['__LEAVES__'] = [];
                    }

                    $ptr = &$ptr[$sub_oid]['__LEAVES__'];
                    $sub_oid = '';
                } else {
                    if ($oid[$i] != '"') {
                        $sub_oid .= $oid[$i];
                    }
                }
            }

            // The last element will contain the full OID.
            $ptr[$sub_oid] = [
                '__OID__'   => $oid,
                '__VALUE__' => $value,
            ];
            $ptr = &$ptr[$sub_oid];
            $sub_oid = '';
        }
    } else {
        $oid_tree = __('The server did not return any response.');
        error_log($oid_tree);
    }

    return $oid_tree;
}


/**
 * Retrieve data for the specified OID.
 *
 * @param string       $target_ip            IP of the SNMP agent.
 * @param string       $community            SNMP community to use.
 * @param string       $target_oid           SNMP OID to query.
 * @param string       $version              Version SNMP.
 * @param string       $snmp3_auth_user      User snmp3.
 * @param string       $snmp3_security_level Security level snmp3.
 * @param string       $snmp3_auth_method    Method snmp3.
 * @param string       $snmp3_auth_pass      Pass snmp3.
 * @param string       $snmp3_privacy_method Privicy method snmp3.
 * @param string       $snmp3_privacy_pass   Pass Method snmp3.
 * @param integer      $server_to_exec       Execute with other server.
 * @param integer|null $target_port          Target port.
 *
 * @return mixed OID data.
 */
function snmp_browser_get_oid(
    $target_ip,
    $community,
    $target_oid,
    $version='2c',
    $snmp3_auth_user='',
    $snmp3_security_level='',
    $snmp3_auth_method='',
    $snmp3_auth_pass='',
    $snmp3_privacy_method='',
    $snmp3_privacy_pass='',
    $server_to_exec=0,
    $target_port=''
) {
    global $config;

    if ($target_oid == '') {
        return;
    }

    if ($version == '2') {
        $version = '2c';
    }

    $output = get_snmpwalk(
        // Ip_target.
        $target_ip,
        // Snmp_version.
        $version,
        // Snmp_community.
        $community,
        // Snmp3_auth_user.
        $snmp3_auth_user,
        // Snmp3_security_level.
        $snmp3_security_level,
        // Snmp3_auth_method.
        $snmp3_auth_method,
        // Snmp3_auth_pass.
        $snmp3_auth_pass,
        // Snmp3_privacy_method.
        $snmp3_privacy_method,
        // Snmp3_privacy_pass.
        $snmp3_privacy_pass,
        // Quick_print.
        0,
        // Base_oid.
        $target_oid,
        // Snmp_port.
        $target_port,
        // Server_to_exec.
        $server_to_exec,
        // Extra_arguments.
        '',
        // Format.
        '-On'
    );

    $oid_data['oid'] = $target_oid;

    foreach ($output as $oid => $value) {
        $oid = trim($oid);
        $oid_data['numeric_oid'] = $oid;

        // Translate the OID.
        if (empty($config['snmptranslate'])) {
            switch (PHP_OS) {
                case 'FreeBSD':
                    $snmptranslate_bin = '/usr/local/bin/snmptranslate';
                break;

                case 'NetBSD':
                    $snmptranslate_bin = '/usr/pkg/bin/snmptranslate';
                break;

                default:
                    $snmptranslate_bin = 'snmptranslate';
                break;
            }
        } else {
            $snmptranslate_bin = $config['snmptranslate'];
        }

        if (empty($server_to_exec) === false && enterprise_installed()) {
            $server_data = db_get_row('tserver', 'id_server', $server_to_exec);
            $command_output = $snmptranslate_bin.' -m ALL -M +'.escapeshellarg($config['homedir'].'/attachment/mibs').' -Td '.escapeshellarg($oid);

            if (empty($server_data['port'])) {
                exec(
                    'ssh pandora_exec_proxy@'.$server_data['ip_address'].' "'.$command_output.'"',
                    $translate_output,
                    $rc
                );
            } else {
                exec(
                    'ssh -p '.$server_data['port'].' pandora_exec_proxy@'.$server_data['ip_address'].' "'.$command_output.'"',
                    $translate_output,
                    $rc
                );
            }
        } else {
            exec(
                $snmptranslate_bin.' -m ALL -M +'.escapeshellarg($config['homedir'].'/attachment/mibs').' -Td '.escapeshellarg($oid),
                $translate_output
            );
        }

        foreach ($translate_output as $line) {
            if (preg_match('/SYNTAX\s+(.*)/', $line, $matches) == 1) {
                $oid_data['syntax'] = $matches[1];
            } else if (preg_match('/MAX-ACCESS\s+(.*)/', $line, $matches) == 1) {
                $oid_data['max_access'] = $matches[1];
            } else if (preg_match('/STATUS\s+(.*)/', $line, $matches) == 1) {
                $oid_data['status'] = $matches[1];
            } else if (preg_match('/DISPLAY\-HINT\s+(.*)/', $line, $matches) == 1) {
                $oid_data['display_hint'] = $matches[1];
            }
        }

        // Parse the description. First search for it in custom values.
        $custom_data = db_get_row('ttrap_custom_values', 'oid', $oid);
        if ($custom_data === false) {
            $translate_output = implode('', $translate_output);
            if (preg_match('/DESCRIPTION\s+\"(.*)\"/', $translate_output, $matches) == 1) {
                $oid_data['description'] = $matches[1];
            }
        } else {
            $oid_data['description'] = $custom_data['description'];
        }

        $full_value = explode(':', trim($value));
        if (! isset($full_value[1])) {
            $oid_data['value'] = trim($value);
        } else {
            $oid_data['type'] = trim($full_value[0]);
            $oid_data['value'] = trim($full_value[1]);
        }

        // There should only be one OID.
        break;
    }

    return $oid_data;
}


/**
 * Print the given OID data.
 *
 * @param oid array OID data.
 * @param custom_action string A custom action added to next to the close button.
 * @param bool return The result is printed if set to true or returned if set to false.
 *
 * @return string The OID data.
 */
function snmp_browser_print_oid(
    $oid=[],
    $custom_action='',
    $return=false,
    $community='',
    $snmp_version=1
) {
    $output = '';

    // OID information table.
    $table = new StdClass();
    $table->width = '100%';
    $table->size = [];
    $table->data = [];

    foreach (['oid', 'numeric_oid', 'value'] as $key) {
        if (! isset($oid[$key])) {
            $oid[$key] = '';
        }
    }

    $table->data[0][0] = '<strong>'.__('OID').'</strong>';
    $table->data[0][1] = $oid['oid'];
    $table->data[1][0] = '<strong>'.__('Numeric OID').'</strong>';
    $table->data[1][1] = '<span id="snmp_selected_oid">'.$oid['numeric_oid'].'</span>';
    $table->data[2][0] = '<strong>'.__('Value').'</strong>';
    $table->data[2][1] = $oid['value'];
    $i = 3;
    if (isset($oid['type'])) {
        $table->data[$i][0] = '<strong>'.__('Type').'</strong>';
        $table->data[$i][1] = $oid['type'];
        $i++;
    }

    if (isset($oid['description'])) {
        $table->data[$i][0] = '<strong>'.__('Description').'</strong>';
        $table->data[$i][1] = $oid['description'];
        $i++;
    }

    if (isset($oid['syntax'])) {
        $table->data[$i][0] = '<strong>'.__('Syntax').'</strong>';
        $table->data[$i][1] = $oid['syntax'];
        $i++;
    }

    if (isset($oid['display_hint'])) {
        $table->data[$i][0] = '<strong>'.__('Display hint').'</strong>';
        $table->data[$i][1] = $oid['display_hint'];
        $i++;
    }

    if (isset($oid['max_access'])) {
        $table->data[$i][0] = '<strong>'.__('Max access').'</strong>';
        $table->data[$i][1] = $oid['max_access'];
        $i++;
    }

    if (isset($oid['status'])) {
        $table->data[$i][0] = '<strong>'.__('Status').'</strong>';
        $table->data[$i][1] = $oid['status'];
        $i++;
    }

    $closer = '<a href="javascript:" onClick="hideOIDData();">';
    $closer .= html_print_image('images/blade.png', true, ['title' => __('Close'), 'style' => 'vertical-align: middle;'], false);
    $closer .= '</a>';

    // Add a span for custom actions
    if ($custom_action != '') {
        $table->head[0] = '<span id="snmp_custom_action">'.$closer.$custom_action.'</span>';
    } else {
        $table->headstyle[0] = 'text-align: left';
        $table->head[0] = $closer;
    }

    $table->headstyle[1] = 'text-align: left';
    $table->head[1] = __('OID Information');
    $output .= html_print_table($table, true);

    $url = 'index.php?sec=gmodules&sec2=godmode/modules/manage_network_components';
    $output .= '<form id="snmp_create_module" class="center mrgn_10px flex" target="_blank" method="post" action="'.$url.'">';
    $output .= html_print_input_hidden('create_network_from_snmp_browser', 1, true);
    $output .= html_print_input_hidden('id_component_type', 2, true);
    $output .= html_print_input_hidden('type', 17, true);
    $name = '';
    if (!empty($oid['oid'])) {
        $name = $oid['oid'];
    }

    $output .= html_print_input_hidden('name', $name, true);
    $description = '';
    if (!empty($oid['description'])) {
        $description = $oid['description'];
        // Remove extra whitespaces.
        $description = preg_replace('/\s+/', ' ', $description);
    }

    $output .= html_print_input_hidden('description', $description, true);
    $output .= html_print_input_hidden('snmp_oid', $oid['numeric_oid'], true);

    // Create module buttons.
    $output .= html_print_submit_button(
        __('Create network component'),
        'create_network_component',
        false,
        'class="buttonButton mrgn_right_20px"',
        true
    );

    if (isset($_POST['print_create_agent_module'])) {
        // Hidden by default.
        $output .= html_print_button(
            __('Create agent module'),
            'create_module_agent_single',
            false,
            'show_add_module()',
            'class="sub add invisible"',
            true
        );
    }

    if (isset($_POST['print_copy_oid'])) {
        // Hidden by default.
        $output .= html_print_button(
            __('Use this OID'),
            'use_iod',
            false,
            'use_oid()',
            'class="sub add invisible"',
            true
        );
    }

    // Select agent modal.
    $output .= snmp_browser_print_create_modules(true);

    $output .= '</form>';

    if ($return) {
        return $output;
    }

    echo $output;
}


/**
 * Print browser container.
 *
 * @param boolean $return               The result is printed
 * if set to true or returned if set to false.
 * @param string  $width                Width of the SNMP browser.
 * Units must be specified.
 * @param string  $height               Height of the SNMP browser.
 * Units must be specified.
 * @param string  $display              CSS display value for the
 * container div. Set to none to hide the div.
 * @param boolean $show_massive_buttons Massive buttons.
 *
 * @return string html.
 */
function snmp_browser_print_container(
    $return=false,
    $width='100%',
    $height='60%',
    $display='',
    $show_massive_buttons=false,
    $toggle=false
) {
    global $config;

    $snmp_version = get_parameter('snmp_browser_version', '2c');

    // Target selection.
    $table = new stdClass();
    $table->width = '100%';
    $table->class = 'filter-table-adv';
    $table->size = [];
    $table->data = [];

    $table->size[0] = '30%';
    $table->size[1] = '30%';
    $table->size[2] = '30%';

    $table->data[0][0] = html_print_label_input_block(
        __('Target IP'),
        html_print_input(
            [
                'type'      => 'text',
                'name'      => 'target_ip',
                'value'     => get_parameter('target_ip', ''),
                'required'  => true,
                'size'      => 25,
                'maxlength' => 0,
                'return'    => true,
            ]
        )
    );

    $table->data[0][1] .= html_print_label_input_block(
        __('Port'),
        html_print_input(
            [
                'type'     => 'number',
                'name'     => 'target_port',
                'id'       => 'target_port',
                'value'    => get_parameter('target_port', 161),
                'required' => true,
                'return'   => true,
            ]
        )
    );

    $table->data[0][2] = html_print_label_input_block(
        __('Community'),
        html_print_input_text(
            'community',
            get_parameter('community', ''),
            '',
            25,
            0,
            true
        )
    );

    $table->data[1][0] = html_print_label_input_block(
        __('Starting OID'),
        html_print_input_text(
            'starting_oid',
            get_parameter('starting_oid', '.1.3.6.1.2.1.2.2'),
            '',
            25,
            0,
            true
        )
    );

    $table->data[1][1] = html_print_label_input_block(
        __('Version'),
        html_print_select(
            [
                '1'  => 'v. 1',
                '2'  => 'v. 2',
                '2c' => 'v. 2c',
                '3'  => 'v. 3',
            ],
            'snmp_browser_version',
            get_parameter('snmp_browser_version', '2c'),
            'checkSNMPVersion();',
            '',
            '',
            true,
            false,
            false,
            '',
            false,
            'width: 100%',
        )
    );

    $servers_to_exec = [];
    $servers_to_exec[0] = __('Local console');

    if (enterprise_installed()) {
        enterprise_include_once('include/functions_satellite.php');

        $rows = get_proxy_servers();
        if ($rows !== false) {
            foreach ($rows as $row) {
                if ($row['server_type'] != 13) {
                    $s_type = ' (Standard)';
                } else {
                    $s_type = ' (Satellite)';
                }

                $servers_to_exec[$row['id_server']] = $row['name'].$s_type;
            }
        }
    }

    $table->data[1][2] = html_print_label_input_block(
        __('Server to execute'),
        html_print_select(
            $servers_to_exec,
            'server_to_exec',
            '',
            '',
            '',
            '',
            true,
            false,
            false,
            '',
            false,
            'width: 100%',
        )
    );

    // SNMP v3 options.
    $snmp3_auth_user = get_parameter('snmp3_auth_user', '');
    $snmp3_security_level = get_parameter('snmp3_security_level', 'authNoPriv');
    $snmp3_auth_method = get_parameter('snmp3_auth_method', 'MD5');
    $snmp3_auth_pass = get_parameter('snmp3_auth_pass', '');
    $snmp3_privacy_method = get_parameter('snmp3_privacy_method', 'AES');
    $snmp3_privacy_pass = get_parameter('snmp3_privacy_pass', '');

    $table3 = new stdClass();
    $table3->width = '100%';
    $table3->class = 'filter-table-adv';

    $table3->size[0] = '30%';
    $table3->size[1] = '30%';
    $table3->size[2] = '30%';

    $table3->data[0][0] = html_print_label_input_block(
        __('Auth user'),
        html_print_input_text(
            'snmp3_browser_auth_user',
            $snmp3_auth_user,
            '',
            15,
            60,
            true
        )
    );

    $table3->data[0][1] = html_print_label_input_block(
        __('Auth password'),
        '<div>'.html_print_input_password(
            'snmp3_browser_auth_pass',
            $snmp3_auth_pass,
            '',
            15,
            60,
            true
        ).'</div>'
    );

    $table3->data[0][1] .= html_print_input_hidden_extended(
        'active_snmp_v3',
        0,
        'active_snmp_v3_fsb',
        true
    );

    $table3->data[0][2] = html_print_label_input_block(
        __('Privacy method'),
        html_print_select(
            [
                'DES' => __('DES'),
                'AES' => __('AES'),
            ],
            'snmp3_browser_privacy_method',
            $snmp3_privacy_method,
            '',
            '',
            '',
            true
        )
    );

    $table3->data[1][0] = html_print_label_input_block(
        __('Privacy pass'),
        '<div>'.html_print_input_password(
            'snmp3_browser_privacy_pass',
            $snmp3_privacy_pass,
            '',
            15,
            60,
            true
        ).'</div>'
    );

    $table3->data[1][1] = html_print_label_input_block(
        __('Auth method'),
        html_print_select(
            [
                'MD5' => __('MD5'),
                'SHA' => __('SHA'),
            ],
            'snmp3_browser_auth_method',
            $snmp3_auth_method,
            '',
            '',
            '',
            true
        )
    );

    $table3->data[1][2] = html_print_label_input_block(
        __('Security level'),
        html_print_select(
            [
                'noAuthNoPriv' => __('Not auth and not privacy method'),
                'authNoPriv'   => __('Auth and not privacy method'),
                'authPriv'     => __('Auth and privacy method'),
            ],
            'snmp3_browser_security_level',
            $snmp3_security_level,
            '',
            '',
            '',
            true
        )
    );

    if (isset($snmp_version) === false) {
        $snmp_version = null;
    }

    if ($snmp_version == 3) {
        $table->data[2] = '<div id="snmp3_browser_options">';
    } else {
        $table->data[2] = '<div id="snmp3_browser_options" style="display: none;">';
    }

    $table->colspan[2][0] = 3;
    $table->data[2] .= ui_toggle(
        html_print_table(
            $table3,
            true
        ),
        __('SNMP v3 settings'),
        '',
        '',
        true,
        true
    );
    $table->data[2] .= '</div>';

    if ($toggle == true) {
        $print_create_agent_module = 1;
    } else {
        $print_create_agent_module = 0;
    }

    $searchForm = '<form onsubmit="snmpBrowse(); return false;">';
    $searchForm .= html_print_table($table, true);
    $searchForm .= html_print_input_hidden(
        'print_create_agent_module',
        $print_create_agent_module,
        true,
        false,
        false,
        'print_create_agent_module'
    );
    $searchForm .= html_print_div(
        [
            'class'   => 'action-buttons',
            'content' => html_print_submit_button(
                __('Execute'),
                'srcbutton',
                false,
                [
                    'mode' => 'mini',
                    'icon' => 'cog',
                ],
                true
            ),
        ],
        true
    );

    $searchForm .= '</form>';

    if ($toggle == true) {
        ui_toggle(
            $searchForm,
            '<span class="subsection_header_title">'.__('Filters').'</span>',
            'filter_form',
            '',
            false,
            false,
            '',
            'white-box-content',
            'box-flat white_table_graph fixed_filter_bar'
        );
    }

    // Search tools.
    $table2 = new stdClass();
    $table2->width = '100%';
    $table2->class = 'databox filters';
    $table2->size = [];
    $table2->data = [];

    $table2->data[0][0] = html_print_input_text(
        'search_text',
        '',
        '',
        25,
        0,
        true
    );
    $table2->data[0][0] .= '<a href="javascript:">';
    $table2->data[0][0] .= html_print_image(
        'images/zoom.png',
        true,
        [
            'title'   => __('Search'),
            'style'   => 'vertical-align: middle;',
            'onclick' => 'searchText();',
            'class'   => 'invert_filter',
        ]
    );
    $table2->data[0][0] .= '</a>';

    $table2->data[0][1] = '&nbsp;';
    $table2->data[0][1] .= '<a href="javascript:">';
    $table2->data[0][1] .= html_print_image(
        'images/go_first.png',
        true,
        [
            'title'   => __('First match'),
            'style'   => 'vertical-align: middle;',
            'onclick' => 'searchFirstMatch();',
            'class'   => 'invert_filter',
        ]
    );
    $table2->data[0][1] .= '</a>';
    $table2->data[0][1] .= '&nbsp;';
    $table2->data[0][1] .= '<a href="javascript:">';
    $table2->data[0][1] .= html_print_image(
        'images/go_previous.png',
        true,
        [
            'title'   => __('Previous match'),
            'style'   => 'vertical-align: middle;',
            'onclick' => 'searchPrevMatch();',
            'class'   => 'invert_filter',
        ]
    );
    $table2->data[0][1] .= '</a>';
    $table2->data[0][1] .= '&nbsp;';
    $table2->data[0][1] .= '<a href="javascript:">';
    $table2->data[0][1] .= html_print_image(
        'images/go_next.png',
        true,
        [
            'title'   => __('Next match'),
            'style'   => 'vertical-align: middle;',
            'onclick' => 'searchNextMatch();',
            'class'   => 'invert_filter',
        ]
    );
    $table2->data[0][1] .= '</a>';
    $table2->data[0][1] .= '&nbsp;';
    $table2->data[0][1] .= '<a href="javascript:">';
    $table2->data[0][1] .= html_print_image(
        'images/go_last.png',
        true,
        [
            'title'   => __('Last match'),
            'style'   => 'vertical-align: middle;',
            'onclick' => 'searchLastMatch();',
            'class'   => 'invert_filter',
        ]
    );
    $table2->data[0][1] .= '</a>';
    $table2->cellstyle[0][1] = 'text-align:center;';

    $table2->data[0][2] = '&nbsp;';
    $table2->data[0][2] .= '<a href="javascript:">'.html_print_image(
        'images/expand.png',
        true,
        [
            'title'   => __('Expand the tree (can be slow)'),
            'style'   => 'vertical-align: middle;',
            'onclick' => 'expandAll();',
            'class'   => 'invert_filter',
        ]
    );
    $table2->data[0][2] .= '</a>';
    $table2->data[0][2] .= '&nbsp;';
    $table2->data[0][2] .= '<a href="javascript:">';
    $table2->data[0][2] .= html_print_image(
        'images/collapse.png',
        true,
        [
            'title'   => __('Collapse the tree'),
            'style'   => 'vertical-align: middle;',
            'onclick' => 'collapseAll();',
            'class'   => 'invert_filter',
        ]
    );
    $table2->data[0][2] .= '</a>';
    $table2->cellstyle[0][2] = 'text-align:center;';

    $output = '<div class="search_options" id="search_options" style="display:none">';
    $output .= ui_toggle(
        html_print_table($table2, true),
        __('Search options'),
        '',
        '',
        true,
        true
    );
    $output .= '</div>';

    if ($toggle === false) {
        // This extra div that can be handled by jquery's dialog.
        $output .= '<div id="snmp_browser_container" style="'.$display.'">';
        $output .= '<div style="text-align: left; width: '.$width.'; height: '.$height.';">';
        $output .= '<div class="w100p">';
        $output .= '<form onsubmit="snmpBrowse(); return false;">';
        $output .= html_print_input_hidden(
            'id_agent_module',
            0,
            true,
            false,
            false,
            'id_agent_module'
        );
        $output .= html_print_input_hidden(
            'is_policy_agent',
            1,
            true,
            false,
            false,
            'is_policy_agent'
        );
        $output .= html_print_table($table, true);
        $output .= html_print_div(
            [
                'class'   => 'action-buttons',
                'content' => html_print_submit_button(
                    __('Execute'),
                    'srcbutton',
                    false,
                    [
                        'mode' => 'mini',
                        'icon' => 'cog',
                    ],
                    true
                ),
            ],
            true
        );
        $output .= '</form></div>';

        if (isset($snmp_version) === false) {
            $snmp_version = null;
        }

        if ($snmp_version == 3) {
            $output .= '<div id="snmp3_browser_options">';
        } else {
            $output .= '<div id="snmp3_browser_options" style="display: none;">';
        }

        $output .= ui_toggle(
            html_print_table($table3, true),
            __('SNMP v3 options'),
            '',
            '',
            true,
            true
        );
        $output .= '</div>';
        $output .= '<div class="search_options">';
        $output .= ui_toggle(
            html_print_table($table2, true),
            __('Search options'),
            '',
            '',
            true,
            true
        );
        $output .= '</div>';
    }

    // SNMP tree container.
    $output .= '<div class="snmp_tree_container" id="snmp_tree_container" style="display:none">';
    $output .= html_print_input_hidden('search_count', 0, true);
    $output .= html_print_input_hidden('search_index', -1, true);

    // Save some variables for javascript functions.
    $output .= html_print_input_hidden(
        'ajax_url',
        ui_get_full_url('ajax.php'),
        true
    );
    $output .= html_print_input_hidden(
        'search_matches_translation',
        __('Search matches'),
        true
    );

    $output .= '<div id="search_results" class="search_results"></div>';
    $output .= '<div id="spinner" class="spinner_none_padding" style="display:none">'.html_print_image('images/spinner.gif', true).'</div>';
    $output .= '<div id="snmp_browser">';
    $output .= '</div>';
    $output .= '<div class="databox" id="snmp_data"></div>';
    $output .= '</div>';
    $output .= '</div>';
    $output .= '</div>';

    if ($show_massive_buttons) {
        $output .= '<div id="snmp_create_buttons" style="display:none">';
        $output .= html_print_submit_button(
            __('Create agent modules'),
            'create_modules_agent',
            false,
            ['class' => 'sub add'],
            true
        );

        if (is_management_allowed() === true && enterprise_installed()) {
            $output .= html_print_submit_button(
                __('Create policy modules'),
                'create_modules_policy',
                false,
                ['class' => 'sub add'],
                true
            );
        }

        $output .= html_print_submit_button(
            __('Create network components'),
            'create_modules_network_component',
            false,
            ['class' => 'sub add'],
            true
        );

        $output .= '</div>';
    }

    if ($return) {
        return $output;
    }

    echo $output;
}


/**
 * Create selected oids as modules on selected target.
 *
 * @param string      $module_target  Target where modules will be created (network componen, agent or policy).
 * @param array       $targets_oids   Modules oids.
 * @param array       $values         SNMP conf values.
 * @param array|null  $id_target      (Optional) Id target where modules will be created.
 * @param string|null $server_to_exec Remote server to execute command.
 *
 * @return array Failed modules.
 */
function snmp_browser_create_modules_snmp(
    string $module_target,
    array $snmp_values,
    ?array $id_target,
    ?string $server_to_exec=null
) {
    $target_ip = null;
    $target_port = null;
    $community = null;
    $target_oid = null;
    $snmp_version = null;
    $snmp3_auth_user = null;
    $snmp3_security_level = null;
    $snmp3_auth_method = null;
    $snmp3_auth_pass = null;
    $snmp3_privacy_method = null;
    $snmp3_privacy_pass = null;

    if (is_array($snmp_values)) {
        if (isset($snmp_values['snmp_browser_version']) === true) {
            $snmp_version = $snmp_values['snmp_browser_version'];
        }

        if (isset($snmp_values['community']) === true) {
            $community = $snmp_values['community'];
        }

        if (isset($snmp_values['target_ip']) === true) {
            $target_ip = $snmp_values['target_ip'];
        }

        if (isset($snmp_values['target_port']) === true) {
            $target_port = $snmp_values['target_port'];
        }

        if (isset($snmp_values['snmp3_browser_auth_user']) === true) {
            $snmp3_auth_user = $snmp_values['snmp3_browser_auth_user'];
        }

        if (isset($snmp_values['snmp3_browser_security_level']) === true) {
            $snmp3_security_level = $snmp_values['snmp3_browser_security_level'];
        };
        if (isset($snmp_values['snmp3_browser_auth_method']) === true) {
            $snmp3_auth_method = $snmp_values['snmp3_browser_auth_method'];
        }

        if (isset($snmp_values['snmp3_browser_auth_pass']) === true) {
            $snmp3_auth_pass = $snmp_values['snmp3_browser_auth_pass'];
        }

        if (isset($snmp_values['snmp3_browser_privacy_method']) === true) {
            $snmp3_privacy_method = $snmp_values['snmp3_browser_privacy_method'];
        };

        if (isset($snmp_values['snmp3_browser_privacy_pass']) === true) {
            $snmp3_privacy_pass = $snmp_values['snmp3_browser_privacy_pass'];
        }

        if (isset($snmp_values['oids']) === true) {
            $targets_oids = $snmp_values['oids'];
        }
    }

    $fail_modules = [];

    foreach ($targets_oids as $key => $target_oid) {
        $oid = snmp_browser_get_oid(
            $target_ip,
            $community,
            htmlspecialchars_decode($target_oid),
            $snmp_version,
            $snmp3_auth_user,
            $snmp3_security_level,
            $snmp3_auth_method,
            $snmp3_auth_pass,
            $snmp3_privacy_method,
            $snmp3_privacy_pass,
            $server_to_exec,
            $target_port
        );

        if (isset($oid['numeric_oid']) === false) {
            $fail_modules[] = $target_oid;
            continue;
        }

        if (empty($oid['description'])) {
            $description = '';
        } else {
            // Delete extra spaces.
            $description = io_safe_input(preg_replace('/\s+/', ' ', $oid['description']));
        }

        if (!empty($oid['type'])) {
            $module_type = snmp_module_get_type($oid['type']);
        } else {
            $module_type = 17;
        }

        if ($module_target == 'network_component') {
            $name_check = db_get_value(
                'name',
                'tnetwork_component',
                'name',
                $oid['oid']
            );

            if (!$name_check) {
                $id = network_components_create_network_component(
                    $oid['oid'],
                    $module_type,
                    1,
                    [
                        'description'           => $description,
                        'module_interval'       => 300,
                        'max'                   => 0,
                        'min'                   => 0,
                        'tcp_send'              => $snmp_version,
                        'tcp_rcv'               => '',
                        'tcp_port'              => $target_port,
                        'snmp_oid'              => $oid['numeric_oid'],
                        'snmp_community'        => $community,
                        'id_module_group'       => 3,
                        'id_modulo'             => 2,
                        'id_plugin'             => 0,
                        'plugin_user'           => $snmp3_auth_user,
                        'plugin_pass'           => $snmp3_auth_pass,
                        'plugin_parameter'      => $snmp3_auth_method,
                        'macros'                => '',
                        'max_timeout'           => 0,
                        'max_retries'           => 0,
                        'history_data'          => '',
                        'dynamic_interval'      => 0,
                        'dynamic_max'           => 0,
                        'dynamic_min'           => 0,
                        'dynamic_two_tailed'    => 0,
                        'min_warning'           => 0,
                        'max_warning'           => 0,
                        'str_warning'           => '',
                        'min_critical'          => 0,
                        'max_critical'          => 0,
                        'str_critical'          => '',
                        'min_ff_event'          => 0,
                        'custom_string_1'       => $snmp3_privacy_method,
                        'custom_string_2'       => $snmp3_privacy_pass,
                        'custom_string_3'       => $snmp3_security_level,
                        'post_process'          => 0,
                        'unit'                  => '',
                        'wizard_level'          => 'nowizard',
                        'macros'                => '',
                        'critical_instructions' => '',
                        'warning_instructions'  => '',
                        'unknown_instructions'  => '',
                        'critical_inverse'      => 0,
                        'warning_inverse'       => 0,
                        'percentage_warning'    => 0,
                        'percentage_critical'   => 0,
                        'id_category'           => 0,
                        'tags'                  => '',
                        'disabled_types_event'  => '{"going_unknown":1}',
                        'min_ff_event_normal'   => 0,
                        'min_ff_event_warning'  => 0,
                        'min_ff_event_critical' => 0,
                        'ff_type'               => 0,
                        'each_ff'               => 0,
                        'history_data'          => 1,
                    ]
                );
            }
        } else if ($module_target == 'agent') {
                $values = [
                    'id_tipo_modulo'        => $module_type,
                    'descripcion'           => $description,
                    'module_interval'       => 300,
                    'max'                   => 0,
                    'min'                   => 0,
                    'tcp_send'              => $snmp_version,
                    'tcp_rcv'               => '',
                    'tcp_port'              => $target_port,
                    'snmp_oid'              => $oid['numeric_oid'],
                    'snmp_community'        => $community,
                    'id_module_group'       => 3,
                    'id_modulo'             => 2,
                    'id_plugin'             => 0,
                    'plugin_user'           => $snmp3_auth_user,
                    'plugin_pass'           => $snmp3_auth_pass,
                    'plugin_parameter'      => $snmp3_auth_method,
                    'macros'                => '',
                    'max_timeout'           => 0,
                    'max_retries'           => 0,
                    'history_data'          => '',
                    'dynamic_interval'      => 0,
                    'dynamic_max'           => 0,
                    'dynamic_min'           => 0,
                    'dynamic_two_tailed'    => 0,
                    'min_warning'           => 0,
                    'max_warning'           => 0,
                    'str_warning'           => '',
                    'min_critical'          => 0,
                    'max_critical'          => 0,
                    'str_critical'          => '',
                    'min_ff_event'          => 0,
                    'custom_string_1'       => $snmp3_privacy_method,
                    'custom_string_2'       => $snmp3_privacy_pass,
                    'custom_string_3'       => $snmp3_security_level,
                    'post_process'          => 0,
                    'unit'                  => '',
                    'wizard_level'          => 'nowizard',
                    'macros'                => '',
                    'critical_instructions' => '',
                    'warning_instructions'  => '',
                    'unknown_instructions'  => '',
                    'critical_inverse'      => 0,
                    'warning_inverse'       => 0,
                    'percentage_warning'    => 0,
                    'percentage_critical'   => 0,
                    'id_category'           => 0,
                    'disabled_types_event'  => '{"going_unknown":1}',
                    'min_ff_event_normal'   => 0,
                    'min_ff_event_warning'  => 0,
                    'min_ff_event_critical' => 0,
                    'ff_type'               => 0,
                    'each_ff'               => 0,
                    'ip_target'             => $target_ip,
                    'history_data'          => 1,
                ];
                foreach ($id_target as $agent) {
                    $ids[] = modules_create_agent_module($agent, $oid['oid'], $values);
                }
        } else if ($module_target == 'policy') {
            // Policies only in enterprise version.
            if (enterprise_installed()) {
                $values = [
                    'id_tipo_modulo'        => $module_type,
                    'description'           => $description,
                    'module_interval'       => 300,
                    'max'                   => 0,
                    'min'                   => 0,
                    'tcp_send'              => $snmp_version,
                    'tcp_rcv'               => '',
                    'tcp_port'              => $target_port,
                    'snmp_oid'              => $oid['numeric_oid'],
                    'snmp_community'        => $community,
                    'id_module_group'       => 3,
                    'id_plugin'             => 0,
                    'plugin_user'           => $snmp3_auth_user,
                    'plugin_pass'           => $snmp3_auth_pass,
                    'plugin_parameter'      => $snmp3_auth_method,
                    'macros'                => '',
                    'max_timeout'           => 0,
                    'max_retries'           => 0,
                    'history_data'          => 1,
                    'dynamic_interval'      => 0,
                    'dynamic_max'           => 0,
                    'dynamic_min'           => 0,
                    'dynamic_two_tailed'    => 0,
                    'min_warning'           => 0,
                    'max_warning'           => 0,
                    'str_warning'           => '',
                    'min_critical'          => 0,
                    'max_critical'          => 0,
                    'str_critical'          => '',
                    'min_ff_event'          => 0,
                    'custom_string_1'       => $snmp3_privacy_method,
                    'custom_string_2'       => $snmp3_privacy_pass,
                    'custom_string_3'       => $snmp3_security_level,
                    'post_process'          => 0,
                    'unit'                  => '',
                    'macros'                => '',
                    'critical_instructions' => '',
                    'warning_instructions'  => '',
                    'unknown_instructions'  => '',
                    'critical_inverse'      => 0,
                    'warning_inverse'       => 0,
                    'percentage_warning'    => 0,
                    'percentage_critical'   => 0,
                    'id_category'           => 0,
                    'disabled_types_event'  => '{"going_unknown":1}',
                    'min_ff_event_normal'   => 0,
                    'min_ff_event_warning'  => 0,
                    'min_ff_event_critical' => 0,
                    'ff_type'               => 0,
                    'each_ff'               => 0,
                    'ip_target'             => '',
                    'configuration_data'    => '',
                    'history_data'          => 1,
                ];

                enterprise_include_once('include/functions_policies.php');
                foreach ($id_target as $policy) {
                    $ids[] = policies_create_module($oid['oid'], $policy, 2, $values);
                }
            }
        }

        if (isset($ids) === true && is_array($ids) === true) {
            foreach ($ids as $id) {
                // Id < 0 for error codes.
                if (!$id || $id < 0) {
                    array_push($fail_modules, $oid['oid']);
                }
            }
        } else {
            if (empty($id)) {
                array_push($fail_modules, $oid['oid']);
            }
        }
    }

     return $fail_modules;
}


/**
 * Prints html for create module from snmp massive dialog
 *
 * @param string  $target    Target.
 * @param string  $snmp_conf Conf.
 * @param boolean $return    Type return.
 *
 * @return string Output html.
 */
function snmp_browser_print_create_module_massive(
    $target='agent',
    $snmp_conf='',
    $return=false
) {
    global $config;

    // String for labels.
    switch ($target) {
        case 'agent':
            $target_item = 'Agents';
        break;

        case 'policy':
            $target_item = 'Policies';
        break;

        default:
            // Not possible.
        break;
    }

    $output = "<form target='_blank' id='create_module_massive' action='#' method='post'>";

    $strict_user = db_get_value(
        'strict_acl',
        'tusuario',
        'id_user',
        $config['id_user']
    );

    $keys_field = 'id_grupo';

    $table = new stdClass();
    $table->width = '100%';
    $table->data = [];
    $table->class = 'filter-table-adv databox';
    $table->size[0] = '50%';
    $table->size[1] = '50%';

    $table->data[0][0] = html_print_label_input_block(
        __('Filter group')."<div id='loading_group' class='loading_div invisible left'><img src='images/spinner.gif'></div>",
        html_print_select_groups(
            false,
            'RR',
            users_can_manage_group_all('RR'),
            'group',
            '',
            '',
            '',
            0,
            true,
            false,
            false,
            '',
            false,
            false,
            false,
            false,
            $keys_field,
            $strict_user
        )
    );

    $table->data[0][1] = html_print_label_input_block(
        __('Search')."<div id='loading_filter' class='loading_div invisible left'><img src='images/spinner.gif'></div>",
        html_print_input_text(
            'filter',
            '',
            '',
            false,
            150,
            true
        )
    );
    $attr = [
        'id'    => 'image-select_all_available',
        'title' => __('Select all'),
        'style' => 'cursor: pointer;',
    ];
    $table->data[1][0] = '<b>'.__($target_item.' available').'</b>&nbsp;&nbsp;'.html_print_image('images/tick.png', true, $attr, false, true);

    $attr = [
        'id'    => 'image-select_all_apply',
        'title' => __('Select all'),
        'style' => 'cursor: pointer;',
    ];
    $table->data[1][1] = '<b>'.__($target_item.' to apply').'</b>&nbsp;&nbsp;'.html_print_image('images/tick.png', true, $attr, false, true);

    if ($target == 'policy') {
        if (enterprise_installed()) {
            $table->data[2][0] = html_print_button(
                __('Create new policy'),
                'snmp_browser_create_policy',
                false,
                '',
                'class="sub add mrgn_lft_0"',
                true
            );
        }

        $table->data[2][1] = html_print_div(
            [
                'style' => 'display:none',
                'id'    => 'policy_modal',
            ],
            true
        );
    }

    // Container with all agents list.
    $AgentsFullList = html_print_div(
        [
            'content' => html_print_select(
                [],
                'id_item[]',
                0,
                false,
                '',
                '',
                true,
                true,
                true,
                '',
                false,
                'width: 100%;'
            ),
            'style'   => 'width:45% !important',
        ],
        true
    );

    $controls[] = html_print_image(
        'images/plus.svg',
        true,
        [
            'id'    => 'right',
            'title' => __('Add'),
            'class' => 'invert_filter main_menu_icon',
        ]
    );

    $controls[] = html_print_image(
        'images/minus.svg',
        true,
        [
            'id'    => 'left',
            'title' => __('Undo'),
            'class' => 'invert_filter main_menu_icon',
        ]
    );

    // Container with controls.
    $AgentsControls = html_print_div(
        [
            'content' => implode('', $controls),
            'style'   => 'width:10% !important',
            'class'   => 'flex-colum-center',
        ],
        true
    );

    // Container with selected agents list.
    $AgentsSelectedList = html_print_div(
        [
            'content' => html_print_select(
                [],
                'id_item2[]',
                0,
                false,
                '',
                '',
                true,
                true,
                true,
                '',
                false,
                'width: 100%;'
            ),
            'style'   => 'width:45% !important',
        ],
        true
    );

    $table->colspan[3][0] = 2;
    $table->data[3][0] = html_print_div(
        [
            'id'      => 'agent_controls',
            'content' => $AgentsFullList.$AgentsControls.$AgentsSelectedList,
            'style'   => 'width:100% !important',
        ],
        true
    );

    $output .= html_print_table($table, true);

    // SNMP extradata.
    $output .= html_print_input_hidden('snmp_extradata', $snmp_conf, true);

    $output .= '</form>';

    $output .= '</div>';

    $script = 'add_module_massive_controller("'.$target.'")';

    // Add script to output.
    $output .= '<script>'.$script.'</script>';

    if ($return) {
        return $output;
    } else {
        echo $output;
    }

}


/**
 * Prints form from create snmp module dialog
 *
 * @param  boolean $return
 * @return void
 */
function snmp_browser_print_create_modules($return=false)
{
    $output = "<div id='dialog_create_module' class='invisible' title='Select agent'>";

    $table = new stdClass();
    $table->width = '100%';
    $table->class = 'databox filters';
    $table->style = [];
    $table->style[0] = 'font-weight: bolder;';

    $table->data[0][0] = __('Agent');

    $params['return'] = true;
    $params['show_helptip'] = true;
    $params['input_name'] = 'id_agent';
    $params['selectbox_id'] = 'id_agent_module';
    $params['metaconsole_enabled'] = false;
    $params['hidden_input_idagent_name'] = 'id_agent_module';
    $params['print_hidden_input_idagent'] = true;

    $table->data[1][1] = ui_print_agent_autocomplete_input($params);

    $output .= html_print_table($table, true);

    $output .= '</div>';

    if ($return) {
        return $output;
    }

    echo $output;

}


function snmp_browser_print_create_policy()
{
    $table = new stdClass();

    $name = get_parameter('name');
    $id_group = get_parameter('id_group');
    $description = get_parameter('description');

    $table->width = '100%';
    $table->class = 'filter-table-adv databox';
    $table->style = [];
    $table->data = [];
    $table->size[0] = '100%';
    $table->size[1] = '100%';
    $table->size[2] = '100%';

    $table->data[0][0] = html_print_label_input_block(
        __('Name'),
        html_print_input_text(
            'name',
            $name,
            '',
            '60%',
            150,
            true
        )
    );

    $table->data[1][0] = html_print_label_input_block(
        __('Group'),
        '<div class="flex flex-row"><div class="w90p">'.html_print_select_groups(
            false,
            'AW',
            false,
            'id_group',
            $id_group,
            '',
            '',
            '',
            true
        ).'</div><span id="group_preview">'.ui_print_group_icon(
            $id_group,
            true,
            'groups_small',
            '',
            false
        ).'</span></div>'
    );

    $table->data[2][0] = html_print_label_input_block(
        __('Description'),
        html_print_textarea('description', 3, 30, $description, '', true)
    );

    $output = '<form method="post" id="snmp_browser_add_policy_form">';
    $output .= html_print_table($table, true);
    $output .= '</form>';

    return $output;

}
