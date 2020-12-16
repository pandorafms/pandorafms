<?php
/**
 * Net Tools view Class.
 *
 * @category   Class
 * @package    Pandora FMS
 * @subpackage Setup
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

// Get global data.
global $config;

// Necessary classes for extends.
require_once $config['homedir'].'/include/class/HTML.class.php';

/**
 * NetTools class
 */
class NetTools extends HTML
{


    /**
     * Class constructor
     */
    public function __construct()
    {

    }


    /**
     * Add option.
     *
     * @return void
     */
    function godmode_net_tools()
    {
        global $config;

        check_login();

        if (! check_acl($config['id_user'], 0, 'PM')) {
            db_pandora_audit(
                'ACL Violation',
                'Trying to access Profile Management'
            );
            include 'general/noaccess.php';
            return;
        }

        ui_print_page_header(
            __('Config Network Tools'),
            '',
            false,
            'network_tools_tab'
        );

        $update_traceroute = (bool) get_parameter('update_traceroute', 0);

        $traceroute_path = (string) get_parameter('traceroute_path', '');
        $ping_path = (string) get_parameter('ping_path', '');
        $nmap_path = (string) get_parameter('nmap_path', '');
        $dig_path = (string) get_parameter('dig_path', '');
        $snmpget_path = (string) get_parameter('snmpget_path', '');

        if ($update_traceroute) {
            $network_tools_config = [];
            $network_tools_config['traceroute_path'] = $traceroute_path;
            $network_tools_config['ping_path'] = $ping_path;
            $network_tools_config['nmap_path'] = $nmap_path;
            $network_tools_config['dig_path'] = $dig_path;
            $network_tools_config['snmpget_path'] = $snmpget_path;

            $result = config_update_value('network_tools_config', json_encode($network_tools_config));

            ui_print_result_message(
                $result,
                __('Set the paths.'),
                __('Set the paths.')
            );
        } else {
            if (isset($config['network_tools_config'])) {
                $network_tools_config_output = io_safe_output($config['network_tools_config']);
                $network_tools_config = json_decode($network_tools_config_output, true);
                $traceroute_path = $network_tools_config['traceroute_path'];
                $ping_path = $network_tools_config['ping_path'];
                $nmap_path = $network_tools_config['nmap_path'];
                $dig_path = $network_tools_config['dig_path'];
                $snmpget_path = $network_tools_config['snmpget_path'];
            }
        }

        $table = new stdClass();
        $table->width = '100%';

        $table->data = [];

        $table->data[0][0] = __('Traceroute path');
        $table->data[0][1] = html_print_input_text('traceroute_path', $traceroute_path, '', 40, 255, true);

        $table->data[1][0] = __('Ping path');
        $table->data[1][1] = html_print_input_text('ping_path', $ping_path, '', 40, 255, true);

        $table->data[2][0] = __('Nmap path');
        $table->data[2][1] = html_print_input_text('nmap_path', $nmap_path, '', 40, 255, true);

        $table->data[3][0] = __('Dig path');
        $table->data[3][1] = html_print_input_text('dig_path', $dig_path, '', 40, 255, true);

        $table->data[4][0] = __('Snmpget path');
        $table->data[4][1] = html_print_input_text('snmpget_path', $snmpget_path, '', 40, 255, true);

        echo '<form id="form_setup" method="post" >';
        echo '<fieldset>';
        echo '<legend>'.__('Options').'</legend>';
        html_print_input_hidden('update_traceroute', 1);
        html_print_table($table);
        echo '</fieldset>';

        echo '<div class="action-buttons" style="width: '.$table->width.'">';
        html_print_submit_button(__('Update'), 'update_button', false, 'class="sub upd"');
        echo '</div>';
        echo '</form>';
    }


}
