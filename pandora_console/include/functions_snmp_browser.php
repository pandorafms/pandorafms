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
 * Copyright (c) 2005-2019 Artica Soluciones Tecnologicas
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
$nfdump_date_format = 'Y/m/d.H:i:s';


/**
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

    if ($depth > 0) {
        $output .= '<ul id="ul_'.$id.'" style="margin: 0; padding: 0; display: none;">';
    } else {
        $output .= '<ul id="ul_'.$id.'" style="margin: 0; padding: 0;">';
    }

    foreach ($tree['__LEAVES__'] as $level => $sub_level) {
        // Id used to expand leafs.
        $sub_id = time().rand(0, getrandmax());
        // Display the branch.
        $output .= '<li id="li_'.$sub_id.'" style="margin: 0; padding: 0;">';

        // Indent sub branches.
        for ($i = 1; $i <= $depth; $i++) {
            if ($last_array[$i] == 1) {
                $output .= '<img src="'.$url.'/no_branch.png" style="vertical-align: middle;">';
            } else {
                $output .= '<img src="'.$url.'/branch.png" style="vertical-align: middle;">';
            }
        }

        // Branch.
        if (! empty($sub_level['__LEAVES__'])) {
            $output .= "<a id='anchor_$sub_id' onfocus='javascript: this.blur();' href='javascript: toggleTreeNode(\"$sub_id\", \"$id\");'>";
            if ($depth == 0 && $count == 0) {
                if ($count == $total) {
                    $output .= '<img src="'.$url.'/one_closed.png" style="vertical-align: middle;">';
                } else {
                    $output .= '<img src="'.$url.'/first_closed.png" style="vertical-align: middle;">';
                }
            } else if ($count == $total) {
                $output .= '<img src="'.$url.'/last_closed.png" style="vertical-align: middle;">';
            } else {
                $output .= '<img src="'.$url.'/closed.png" style="vertical-align: middle;">';
            }

            $output .= '</a>';
        }

        // Leave.
        else {
            if ($depth == 0 && $count == 0) {
                if ($count == $total) {
                    $output .= '<img src="'.$url.'/no_branch.png" style="vertical-align: middle;">';
                } else {
                    $output .= '<img src="'.$url.'/first_leaf.png" style="vertical-align: middle;">';
                }
            } else if ($count == $total) {
                $output .= '<img src="'.$url.'/last_leaf.png" style="vertical-align: middle;">';
            } else {
                $output .= '<img src="'.$url.'/leaf.png" style="vertical-align: middle;">';
            }
        }

        // Branch or leave with branches!
        if (isset($sub_level['__OID__'])) {
            $output .= "<a onfocus='javascript: this.blur();' href='javascript: snmpGet(\"".addslashes($sub_level['__OID__'])."\");'>";
            $output .= '<img src="'.$url.'/../../images/eye.png" style="vertical-align: middle;">';
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
            $output .= '<span class="value" style="display: none;">&nbsp;=&nbsp;'.$sub_level['__VALUE__'].'</span>';
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
    $snmp3_context_engine_id=null
) {
    global $config;

    switch ($version) {
        case '1':
            $snmp_version = SNMP::VERSION_1;
        break;

        case '2':
            $snmp_version = SNMP::VERSION_2c;
        break;

        case '2c':
            $snmp_version = SNMP::VERSION_2C;
        break;

        case '3':
            $snmp_version = SNMP::VERSION_3;
            $community = $snmp3_auth_user;
        break;

        default:
            $snmp_version = SNMP::VERSION_2c;
        break;
    }

    $snmp_session = new SNMP($snmp_version, $target_ip, $community);
    $snmp_session->oid_output_format = SNMP_OID_OUTPUT_MODULE;

      // Set security if SNMP Version is 3.
    if ($snmp_version == SNMP::VERSION_3) {
        $snmp_session->setSecurity(
            $snmp3_security_level,
            $snmp3_auth_method,
            $snmp3_auth_pass,
            $snmp3_privacy_method,
            $snmp3_privacy_pass,
            $community,
            $snmp3_context_engine_id
        );
    }

    $mibs_dir = $config['homedir'].'/attachment/mibs';
    $_dir = opendir($mibs_dir);

    // Future. Recomemended: Use a global config limit of MIBs loaded.
    while (($mib_file = readdir($_dir)) !== false) {
        if ($mib_file == '..' || $mib_file == '.') {
            continue;
        }

        $rs = snmp_read_mib($mibs_dir.'/'.$mib_file);
        if ($rs !== true) {
            error_log('Failed while reading MIB file: '.$mib_file);
        }
    }

    closedir($_dir);

    $output = $snmp_session->walk($starting_oid);
    if ($output == false) {
        $output = $snmp_session->getError();
        $snmp_session->close();
        return $output;
    }

    $snmp_session->close();

    // Build the tree.
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

    return $oid_tree;
}


/**
 * Retrieve data for the specified OID.
 *
 * @param string  $target_ip            IP of the SNMP agent.
 * @param string  $community            SNMP community to use.
 * @param string  $target_oid           SNMP OID to query.
 * @param string  $version              Version SNMP.
 * @param string  $snmp3_auth_user      User snmp3.
 * @param string  $snmp3_security_level Security level snmp3.
 * @param string  $snmp3_auth_method    Method snmp3.
 * @param string  $snmp3_auth_pass      Pass snmp3.
 * @param string  $snmp3_privacy_method Privicy method snmp3.
 * @param string  $snmp3_privacy_pass   Pass Method snmp3.
 * @param integer $server_to_exec       Execute with other server.
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
    $server_to_exec=0
) {
    global $config;

    if ($target_oid == '') {
        return;
    }

    $output = get_snmpwalk(
        $target_ip,
        $version,
        $community,
        $snmp3_auth_user,
        $snmp3_security_level,
        $snmp3_auth_method,
        $snmp3_auth_pass,
        $snmp3_privacy_method,
        $snmp3_privacy_pass,
        0,
        $target_oid,
        '',
        $server_to_exec,
        '',
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

        if ($server_to_exec != 0) {
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

    // OID information table
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
        $table->head[0] = $closer;
    }

    $table->head[1] = __('OID Information');
    $output .= html_print_table($table, true);

    $url = 'index.php?'.'sec=gmodules&'.'sec2=godmode/modules/manage_network_components';
    $output .= '<form id="snmp_create_module" style="text-align: center; margin: 10px" method="post" action="'.$url.'">';
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
        'class="sub add"',
        true
    );

    // Hidden by default.
    $output .= html_print_button(
        __('Create agent module'),
        'create_module_agent_single',
        false,
        'show_add_module()',
        'class="sub add" style="display:none"',
        true
    );

    // Select agent modal.
    $output .= snmp_browser_print_create_modules(true);

    $output .= '</form>';

    if ($return) {
        return $output;
    }

    echo $output;
}


/**
 * Print the div that contains the SNMP browser.
 *
 * @param bool return The result is printed if set to true or returned if set to false.
 * @param string width Width of the SNMP browser. Units must be specified.
 * @param string height Height of the SNMP browser. Units must be specified.
 * @param string display CSS display value for the container div. Set to none to hide the div.
 *
 * @return string The container div.
 */
function snmp_browser_print_container($return=false, $width='100%', $height='60%', $display='', $show_massive_buttons=false)
{
    // Target selection
    $table = new stdClass();
    $table->width = '100%';
    $table->class = 'databox filters';
    $table->size = [];
    $table->data = [];

    $table->data[0][0] = '<strong>'.__('Target IP').'</strong> &nbsp;&nbsp;';
    $table->data[0][0] .= html_print_input_text(
        'target_ip',
        get_parameter('target_ip', ''),
        '',
        25,
        0,
        true
    );
    $table->data[0][1] = '<strong>'.__('Community').'</strong> &nbsp;&nbsp;';
    $table->data[0][1] .= html_print_input_text(
        'community',
        get_parameter('community', ''),
        '',
        25,
        0,
        true
    );
    $table->data[0][2] = '<strong>'.__('Starting OID').'</strong> &nbsp;&nbsp;';
    $table->data[0][2] .= html_print_input_text(
        'starting_oid',
        get_parameter('starting_oid', '.1.3.6.1.2'),
        '',
        25,
        0,
        true
    );

    $table->data[1][0] = '<strong>'.__('Version').'</strong> &nbsp;&nbsp;';
    $table->data[1][0] .= html_print_select(
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
        ''
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

    $table->data[1][1] = '<strong>'.__('Server to execute').'</strong> &nbsp;&nbsp;';
    $table->data[1][1] .= html_print_select($servers_to_exec, 'server_to_exec', '', '', '', '', true);

    $table->data[1][2] = html_print_button(__('Browse'), 'browse', false, 'snmpBrowse()', 'class="sub search" style="margin-top:0px;"', true);

    // SNMP v3 options
    $table3 = new stdClass();
    $table3->width = '100%';

    $table3->valign[0] = '';
    $table3->valign[1] = '';

    $table3->data[2][1] = '<b>'.__('Auth user').'</b>';
    $table3->data[2][2] = html_print_input_text('snmp3_browser_auth_user', '', '', 15, 60, true);
    $table3->data[2][3] = '<b>'.__('Auth password').'</b>';
    $table3->data[2][4] = html_print_input_password('snmp3_browser_auth_pass', '', '', 15, 60, true);
    $table3->data[2][4] .= html_print_input_hidden_extended('active_snmp_v3', 0, 'active_snmp_v3_fsb', true);

    $table3->data[5][0] = '<b>'.__('Privacy method').'</b>';
    $table3->data[5][1] = html_print_select(['DES' => __('DES'), 'AES' => __('AES')], 'snmp3_browser_privacy_method', '', '', '', '', true);
    $table3->data[5][2] = '<b>'.__('Privacy pass').'</b>';
    $table3->data[5][3] = html_print_input_password('snmp3_browser_privacy_pass', '', '', 15, 60, true);

    $table3->data[6][0] = '<b>'.__('Auth method').'</b>';
    $table3->data[6][1] = html_print_select(['MD5' => __('MD5'), 'SHA' => __('SHA')], 'snmp3_browser_auth_method', '', '', '', '', true);
    $table3->data[6][2] = '<b>'.__('Security level').'</b>';
    $table3->data[6][3] = html_print_select(
        [
            'noAuthNoPriv' => __('Not auth and not privacy method'),
            'authNoPriv'   => __('Auth and not privacy method'),
            'authPriv'     => __('Auth and privacy method'),
        ],
        'snmp3_browser_security_level',
        '',
        '',
        '',
        '',
        true
    );

    // Search tools
    $table2 = new stdClass();
    $table2->width = '100%';
    $table2->class = 'databox filters';
    $table2->size = [];
    $table2->data = [];

    $table2->data[0][0] = html_print_input_text('search_text', '', '', 25, 0, true);
    $table2->data[0][0] .= '<a href="javascript:">'.html_print_image('images/zoom.png', true, ['title' => __('Search'), 'style' => 'vertical-align: middle;', 'onclick' => 'searchText();']).'</a>';
    $table2->data[0][1] = '&nbsp;'.'<a href="javascript:">'.html_print_image('images/go_first.png', true, ['title' => __('First match'), 'style' => 'vertical-align: middle;', 'onclick' => 'searchFirstMatch();']).'</a>';
    $table2->data[0][1] .= '&nbsp;'.'<a href="javascript:">'.html_print_image('images/go_previous.png', true, ['title' => __('Previous match'), 'style' => 'vertical-align: middle;', 'onclick' => 'searchPrevMatch();']).'</a>';
    $table2->data[0][1] .= '&nbsp;'.'<a href="javascript:">'.html_print_image('images/go_next.png', true, ['title' => __('Next match'), 'style' => 'vertical-align: middle;', 'onclick' => 'searchNextMatch();']).'</a>';
    $table2->data[0][1] .= '&nbsp;'.'<a href="javascript:">'.html_print_image('images/go_last.png', true, ['title' => __('Last match'), 'style' => 'vertical-align: middle;', 'onclick' => 'searchLastMatch();']).'</a>';
    $table2->cellstyle[0][1] = 'text-align:center;';

    $table2->data[0][2] = '&nbsp;'.'<a href="javascript:">'.html_print_image(
        'images/expand.png',
        true,
        [
            'title'   => __('Expand the tree (can be slow)'),
            'style'   => 'vertical-align: middle;',
            'onclick' => 'expandAll();',
        ]
    ).'</a>';
    $table2->data[0][2] .= '&nbsp;'.'<a href="javascript:">'.html_print_image('images/collapse.png', true, ['title' => __('Collapse the tree'), 'style' => 'vertical-align: middle;', 'onclick' => 'collapseAll();']).'</a>';
    $table2->cellstyle[0][2] = 'text-align:center;';

    // This extra div that can be handled by jquery's dialog.
    $output = '<div id="snmp_browser_container" style="'.$display.'">';
    $output .= '<div style="text-align: left; width: '.$width.'; height: '.$height.';">';
    $output .= '<div style="width: 100%">';
    $output .= html_print_table($table, true);
    $output .= '</div>';

    if (!isset($snmp_version)) {
        $snmp_version = null;
    }

    if ($snmp_version == 3) {
        $output .= '<div id="snmp3_browser_options">';
    } else {
        $output .= '<div id="snmp3_browser_options" style="display: none;">';
    }

    $output .= ui_toggle(html_print_table($table3, true), __('SNMP v3 options'), '', '', true, true);
    $output .= '</div>';
    $output .= '<div style="width: 100%; padding-top: 10px;">';
    $output .= ui_toggle(html_print_table($table2, true), __('Search options'), '', '', true, true);
    $output .= '</div>';

    // SNMP tree container
    $output .= '<div style="width: 100%; height: 100%; margin-top: 5px; position: relative;">';
    $output .= html_print_input_hidden('search_count', 0, true);
    $output .= html_print_input_hidden('search_index', -1, true);

    // Save some variables for javascript functions
    $output .= html_print_input_hidden('ajax_url', ui_get_full_url('ajax.php'), true);
    $output .= html_print_input_hidden('search_matches_translation', __('Search matches'), true);

    $output .= '<div id="search_results" style="display: none; padding: 5px; background-color: #EAEAEA; border: 1px solid #E2E2E2; border-radius: 4px;"></div>';
    $output .= '<div id="spinner" style="position: absolute; top:0; left:0px; display:none; padding: 5px;">'.html_print_image('images/spinner.gif', true).'</div>';
    $output .= '<div id="snmp_browser">';
    $output .= '</div>';
    $output .= '<div class="databox" id="snmp_data" style="margin: 5px; display:    none"></div>';
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

        if (enterprise_installed()) {
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
 * @param  string     $module_target Target where modules will be created (network componen, agent or policy).
 * @param  array      $targets_oids  Modules oids.
 * @param  array      $values        SNMP conf values.
 * @param  array|null $id_target     (Optional) Id target where modules will be created.
 * @return array        $fail_modules
 */
function snmp_browser_create_modules_snmp(string $module_target, array $snmp_values, ?array $id_target)
{
    if (is_array($snmp_values)) {
        $snmp_version = $snmp_values['snmp_browser_version'];
        $community = $snmp_values['community'];
        $target_ip = $snmp_values['target_ip'];
        $snmp3_auth_user = $snmp_values['snmp3_browser_auth_user'];
        $snmp3_security_level = $snmp_values['snmp3_browser_security_level'];
        $snmp3_auth_method = $snmp_values['snmp3_browser_auth_method'];
        $snmp3_auth_pass = $snmp_values['snmp3_browser_auth_pass'];
        $snmp3_privacy_method = $snmp_values['snmp3_privacy_method'];
        $snmp3_privacy_pass = $snmp_values['snmp3_browser_privacy_pass'];
        $targets_oids = $snmp_values['oids'];
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
            $snmp3_privacy_pass
        );

        if (empty($oid['description'])) {
            $description = '';
        } else {
            // Delete extra spaces.
            $description = io_safe_input(preg_replace('/\s+/', ' ', $oid['description']));
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
                    17,
                    1,
                    [
                        'description'           => $description,
                        'module_interval'       => 300,
                        'max'                   => 0,
                        'min'                   => 0,
                        'tcp_send'              => $snmp_version,
                        'tcp_rcv'               => '',
                        'tcp_port'              => 0,
                        'snmp_oid'              => $oid['numeric_oid'],
                        'snmp_community'        => $community,
                        'id_module_group'       => 3,
                        'id_modulo'             => 2,
                        'id_plugin'             => 0,
                        'plugin_user'           => '',
                        'plugin_pass'           => '',
                        'plugin_parameter'      => '',
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
                        'custom_string_1'       => '',
                        'custom_string_2'       => '',
                        'custom_string_3'       => '',
                        'post_process'          => 0,
                        'unit'                  => '',
                        'wizard_level'          => 'nowizard',
                        'macros'                => '',
                        'critical_instructions' => '',
                        'warning_instructions'  => '',
                        'unknown_instructions'  => '',
                        'critical_inverse'      => 0,
                        'warning_inverse'       => 0,
                        'id_category'           => 0,
                        'tags'                  => '',
                        'disabled_types_event'  => '{"going_unknown":1}',
                        'min_ff_event_normal'   => 0,
                        'min_ff_event_warning'  => 0,
                        'min_ff_event_critical' => 0,
                        'ff_type'               => 0,
                        'each_ff'               => 0,
                    ]
                );
            }
        } else if ($module_target == 'agent') {
                $values = [
                    'id_tipo_modulo'        => 17,
                    'descripcion'           => $description,
                    'module_interval'       => 300,
                    'max'                   => 0,
                    'min'                   => 0,
                    'tcp_send'              => $snmp_version,
                    'tcp_rcv'               => '',
                    'tcp_port'              => 0,
                    'snmp_oid'              => $oid['numeric_oid'],
                    'snmp_community'        => $community,
                    'id_module_group'       => 3,
                    'id_modulo'             => 2,
                    'id_plugin'             => 0,
                    'plugin_user'           => '',
                    'plugin_pass'           => '',
                    'plugin_parameter'      => '',
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
                    'custom_string_1'       => '',
                    'custom_string_2'       => '',
                    'custom_string_3'       => '',
                    'post_process'          => 0,
                    'unit'                  => '',
                    'wizard_level'          => 'nowizard',
                    'macros'                => '',
                    'critical_instructions' => '',
                    'warning_instructions'  => '',
                    'unknown_instructions'  => '',
                    'critical_inverse'      => 0,
                    'warning_inverse'       => 0,
                    'id_category'           => 0,
                    'disabled_types_event'  => '{"going_unknown":1}',
                    'min_ff_event_normal'   => 0,
                    'min_ff_event_warning'  => 0,
                    'min_ff_event_critical' => 0,
                    'ff_type'               => 0,
                    'each_ff'               => 0,
                    'ip_target'             => $target_ip,
                ];
                foreach ($id_target as $agent) {
                    $ids[] = modules_create_agent_module($agent, $oid['oid'], $values);
                }
        } else if ($module_target == 'policy') {
            // Policies only in enterprise version.
            if (enterprise_installed()) {
                $values = [
                    'id_tipo_modulo'        => 17,
                    'description'           => $description,
                    'module_interval'       => 300,
                    'max'                   => 0,
                    'min'                   => 0,
                    'tcp_send'              => $snmp_version,
                    'tcp_rcv'               => '',
                    'tcp_port'              => 0,
                    'snmp_oid'              => $oid['numeric_oid'],
                    'snmp_community'        => $community,
                    'id_module_group'       => 3,
                    'id_plugin'             => 0,
                    'plugin_user'           => '',
                    'plugin_pass'           => '',
                    'plugin_parameter'      => '',
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
                    'custom_string_1'       => '',
                    'custom_string_2'       => '',
                    'custom_string_3'       => '',
                    'post_process'          => 0,
                    'unit'                  => '',
                    'macros'                => '',
                    'critical_instructions' => '',
                    'warning_instructions'  => '',
                    'unknown_instructions'  => '',
                    'critical_inverse'      => 0,
                    'warning_inverse'       => 0,
                    'id_category'           => 0,
                    'disabled_types_event'  => '{"going_unknown":1}',
                    'min_ff_event_normal'   => 0,
                    'min_ff_event_warning'  => 0,
                    'min_ff_event_critical' => 0,
                    'ff_type'               => 0,
                    'each_ff'               => 0,
                    'ip_target'             => $target_ip,
                    'configuration_data'    => '',
                ];

                enterprise_include_once('include/functions_policies.php');
                foreach ($id_target as $policy) {
                    $ids[] = policies_create_module($oid['oid'], $policy, 2, $values);
                }
            }
        }

        if (is_array($ids)) {
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
 * @param  string  $url_form
 * @param  string  $title
 * @param  boolean $return
 * @return void
 */
function snmp_browser_print_create_module_massive($target='agent', $snmp_conf, $return=false)
{
    // String for labels.
    switch ($target) {
        case 'agent':
            $target_item = 'Agents';
        break;

        case 'policy':
            $target_item = 'Policies';
        break;
    }

    $output .= "<form target='_blank' id='create_module_massive' action='#' method='post'>";

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

    $table->data[0][0] = __('Filter group')."<div id='loading_group' class='loading_div' style='display:none; float:left;'><img src='images/spinner.gif'></div>";

    $table->data[0][1] = html_print_select_groups(
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
    );

    $table->data[1][0] = __('Search')."<div id='loading_filter' class='loading_div' style='display:none; float:left;'><img src='images/spinner.gif'></div>";
    $table->data[1][1] = html_print_input_text(
        'filter',
        '',
        '',
        20,
        150,
        true
    );

    $table->data[2][0] = __($target_item.' available').html_print_input_image('select_all_left', 'images/tick.png', 1, '', true, ['title' => __('Select all')]);
    $table->data[2][1] = '';
    $table->data[2][2] = __($target_item.' to apply').html_print_input_image('select_all_right', 'images/tick.png', 1, '', true, ['title' => __('Select all')]);

    $table->data[3][0] = html_print_select(
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
        'width: 100%;',
        []
    );

    if ($target == 'policy') {
        if (enterprise_installed()) {
            $table->data[4][0] = html_print_button(
                __('Create new policy'),
                'snmp_browser_create_policy',
                false,
                '',
                'class="sub add" style="margin-left:0px"',
                true
            );
        }

        $table->data[4][1] = html_print_div(
            [
                'style' => 'display:none',
                'id'    => 'policy_modal',
            ],
            true
        );
    }

    $table->cellstyle[3][1] = 'text-align: center';
    $table->data[3][1] = html_print_image(
        'images/darrowright.png',
        true,
        [
            'id'    => 'right',
            'title' => __('Add'),
        ]
    ).'<br /><br /><br /><br />'.html_print_image(
        'images/darrowleft.png',
        true,
        [
            'id'    => 'left',
            'title' => __('Undo'),
        ]
    );
    $table->data[3][2] = html_print_select(
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
        'width: 100%;',
        []
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
    $output = "<div id='dialog_create_module' style='display: none;' title='Select agent'>";

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

    $table->width = '100%';
    $table->class = 'databox filters';
    $table->style = [];
    $table->style[0] = 'font-weight: bold; vertical-align: top';
    $table->data = [];

    $table->data[0][0] = __('Name');
    $table->data[0][1] = html_print_input_text('name', $name, '', '60%', 150, true);

    $table->data[1][0] = __('Group');
    $table->data[1][1] = html_print_select_groups(
        false,
        'AW',
        false,
        'id_group',
        $id_group,
        '',
        '',
        '',
        true
    );
    $table->data[1][1] .= ' <span id="group_preview">';
    $table->data[1][1] .= ui_print_group_icon($id_group, true, 'groups_small', '', false);
    $table->data[1][1] .= '</span>';

    $table->data[2][0] = __('Description');
    $table->data[2][1] = html_print_textarea('description', 3, 30, $description, '', true);

    $output = '<form method="post" id="snmp_browser_add_policy_form">';
    $output .= html_print_table($table, true);
    $output .= '</form>';

    return $output;

}
