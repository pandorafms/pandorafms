<?php
/**
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

global $config;

require_once 'include/functions_ui.php';

check_login();

if (! check_acl($config['id_user'], 0, 'IR')) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access netflow setup'
    );
    include 'general/noaccess.php';
    return;
}

$update = (bool) get_parameter('update');

$table->width = '100%';
$table->border = 0;
$table->cellspacing = 3;
$table->cellpadding = 5;
$table->class = 'databox filters';

$table->data = [];

$table->data[0][0] = '<b>'.__('Data storage path').'</b>'.ui_print_help_tip(__('Directory where netflow data will be stored.'), true);
$table->data[0][1] = html_print_input_text('netflow_path', $config['netflow_path'], false, 50, 200, true);

$table->data[1][0] = '<b>'.__('Daemon interval').'</b>'.ui_print_help_tip(__('Specifies the time interval in seconds to rotate netflow data files.'), true);
$table->data[1][1] = html_print_input_text('netflow_interval', $config['netflow_interval'], false, 50, 200, true);

$table->data[2][0] = '<b>'.__('Daemon binary path').'</b>';
$table->data[2][1] = html_print_input_text('netflow_daemon', $config['netflow_daemon'], false, 50, 200, true);

$table->data[3][0] = '<b>'.__('Nfdump binary path').'</b>';
$table->data[3][1] = html_print_input_text('netflow_nfdump', $config['netflow_nfdump'], false, 50, 200, true);

$table->data[4][0] = '<b>'.__('Nfexpire binary path').'</b>';
$table->data[4][1] = html_print_input_text('netflow_nfexpire', $config['netflow_nfexpire'], false, 50, 200, true);

$table->data[5][0] = '<b>'.__('Maximum chart resolution').'</b>'.ui_print_help_tip(__('Maximum number of points that a netflow area chart will display. The higher the resolution the performance. Values between 50 and 100 are recommended.'), true);
$table->data[5][1] = html_print_input_text('netflow_max_resolution', $config['netflow_max_resolution'], false, 50, 200, true);

$table->data[6][0] = '<b>'.__('Disable custom live view filters').'</b>'.ui_print_help_tip(__('Disable the definition of custom filters in the live view. Only existing filters can be used.'), true);
$table->data[6][1] = html_print_checkbox_switch('netflow_disable_custom_lvfilters', 1, $config['netflow_disable_custom_lvfilters'], true);
$table->data[7][0] = '<b>'.__('Netflow max lifetime').'</b>'.ui_print_help_tip(__('Sets the maximum lifetime for netflow data in days.'), true);
$table->data[7][1] = html_print_input_text('netflow_max_lifetime', $config['netflow_max_lifetime'], false, 50, 200, true);

$table->data[8][0] = '<b>'.__('Name resolution for IP address').'</b>'.ui_print_help_tip(__('Resolve the IP addresses to get their hostnames.'), true);
$onclick = "if (!confirm('".__('Warning').'. '.__('IP address resolution can take a lot of time')."')) return false;";
$table->data[8][1] = html_print_checkbox_switch_extended('netflow_get_ip_hostname', 1, $config['netflow_get_ip_hostname'], false, $onclick, '', true);

echo '<form id="netflow_setup" method="post">';

html_print_table($table);

// Update button.
echo '<div class="action-buttons" style="width:100%;">';
    html_print_input_hidden('update_config', 1);
    html_print_submit_button(__('Update'), 'upd_button', false, 'class="sub upd"');
echo '</div></form>';
