<?php
/**
 * Setup view for sflow
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
$table->class = 'databox filter-table-adv';

$table->data = [];

$table->data[0][] = html_print_label_input_block(
    __('Data storage path'),
    html_print_input_text('sflow_name_dir', $config['sflow_name_dir'], false, 50, 200, true)
);

$table->data[0][] = html_print_label_input_block(
    __('Daemon interval'),
    html_print_input_text('sflow_interval', $config['sflow_interval'], false, 50, 200, true)
);

$table->data[1][] = html_print_label_input_block(
    __('Daemon binary path'),
    html_print_input_text('sflow_daemon', $config['sflow_daemon'], false, 50, 200, true)
);

$table->data[1][] = html_print_label_input_block(
    __('Nfdump binary path'),
    html_print_input_text('sflow_nfdump', $config['sflow_nfdump'], false, 50, 200, true)
);

$table->data[2][] = html_print_label_input_block(
    __('Nfexpire binary path'),
    html_print_input_text('sflow_nfexpire', $config['sflow_nfexpire'], false, 50, 200, true)
);

$table->data[2][] = html_print_label_input_block(
    __('Maximum chart resolution'),
    html_print_input_text('sflow_max_resolution', $config['sflow_max_resolution'], false, 50, 200, true)
);

$table->data[3][] = html_print_label_input_block(
    __('Disable custom live view filters'),
    html_print_checkbox_switch('sflow_disable_custom_lvfilters', 1, $config['sflow_disable_custom_lvfilters'], true)
);

$table->data[3][] = html_print_label_input_block(
    __('Sflow max lifetime'),
    html_print_input_text('sflow_max_lifetime', $config['sflow_max_lifetime'], false, 50, 200, true)
);

$onclick = "if (!confirm('".__('Warning').'. '.__('IP address resolution can take a lot of time')."')) return false;";
$table->data[4][] = html_print_label_input_block(
    __('Name resolution for IP address'),
    html_print_checkbox_switch_extended('sflow_get_ip_hostname', 1, $config['sflow_get_ip_hostname'], false, $onclick, '', true)
);

echo '<form class="max_floating_element_size" id="sflow_setup" method="post">';
html_print_table($table);
html_print_input_hidden('update_config', 1);
html_print_action_buttons(
    html_print_submit_button(
        __('Update'),
        'upd_button',
        false,
        ['icon' => 'update'],
        true
    )
);
echo '</form>';
?>
<script>
    $("input[name=sflow_name_dir]").on("input", function() {
        $(this).val($(this).val().replace(/[^a-z0-9]/gi, ""));
    });
</script>