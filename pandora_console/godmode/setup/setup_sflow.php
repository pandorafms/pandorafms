<?php
/**
 * Setup view for Netflow
 *
 * @category   Setup
 * @package    Pandora FMS
 * @subpackage Configuration
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2022 Artica Soluciones Tecnologicas
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

require_once 'include/functions_ui.php';

check_login();

$update = (bool) get_parameter('update');

$table = new stdClass();
$table->width = '100%';
$table->border = 0;
$table->cellspacing = 3;
$table->cellpadding = 5;
$table->class = 'databox filters';

$table->data = [];

$table->data[0][0] = '<b>'.__('Data storage path').'</b>';
$table->data[0][1] = html_print_input_text('sflow_name_dir', $config['sflow_name_dir'], false, 50, 200, true);
$table->data[0][1] .= '<script>$("input[name=sflow_name_dir]").on("input", function() {$(this).val($(this).val().replace(/[^a-z0-9]/gi, ""));});</script>';


$table->data[1][0] = '<b>'.__('Daemon interval').'</b>';
$table->data[1][1] = html_print_input_text('sflow_interval', $config['sflow_interval'], false, 50, 200, true);

$table->data[2][0] = '<b>'.__('Daemon binary path').'</b>';
$table->data[2][1] = html_print_input_text('sflow_daemon', $config['sflow_daemon'], false, 50, 200, true);

$table->data[3][0] = '<b>'.__('Nfdump binary path').'</b>';
$table->data[3][1] = html_print_input_text('sflow_nfdump', $config['sflow_nfdump'], false, 50, 200, true);

$table->data[4][0] = '<b>'.__('Nfexpire binary path').'</b>';
$table->data[4][1] = html_print_input_text('sflow_nfexpire', $config['sflow_nfexpire'], false, 50, 200, true);

$table->data[5][0] = '<b>'.__('Maximum chart resolution').'</b>';
$table->data[5][1] = html_print_input_text('sflow_max_resolution', $config['sflow_max_resolution'], false, 50, 200, true);

$table->data[6][0] = '<b>'.__('Disable custom live view filters').'</b>';
$table->data[6][1] = html_print_checkbox_switch('sflow_disable_custom_lvfilters', 1, $config['sflow_disable_custom_lvfilters'], true);
$table->data[7][0] = '<b>'.__('Max. sflow lifetime').'</b>';
$table->data[7][1] = html_print_input_text('sflow_max_lifetime', $config['sflow_max_lifetime'], false, 50, 200, true);

$table->data[8][0] = '<b>'.__('Name resolution for IP address').'</b>';
$onclick = "if (!confirm('".__('Warning').'. '.__('IP address resolution can take a lot of time')."')) return false;";
$table->data[8][1] = html_print_checkbox_switch_extended('sflow_get_ip_hostname', 1, $config['sflow_get_ip_hostname'], false, $onclick, '', true);

echo '<form id="netflow_setup" method="post">';

html_print_table($table);

// Update button.
echo '<div class="action-buttons w100p">';
    html_print_input_hidden('update_config', 1);
    html_print_submit_button(__('Update'), 'upd_button', false, 'class="sub upd"');
echo '</div></form>';
