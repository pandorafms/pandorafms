<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.

global $config;

check_login ();

if (! check_acl($config['id_user'], 0, "PM")) {
	db_pandora_audit("ACL Violation",
		"Trying to access Group Management");
	require ("general/noaccess.php");
	return;
}

// Header
ui_print_page_header (__('Custom events'), "", false, "", true);

$update = get_parameter('update', 0);
$event = array();

$table->width = '90%';

$table->size = array();
$table->size[0] = '20%';
$table->size[2] = '20%';

$table->data = array();
$table->data[0][0] = '<h3>'.__('Show event fields').'</h3>';

$table->data[1][0] = '<b>'.__('Event ID').'</b>';
$table->data[1][1] = __('Yes').'&nbsp;'.html_print_radio_button('show_id_evento', 1, '', $config['show_id_evento'], true).'&nbsp;';
$table->data[1][1] .= __('No').'&nbsp;'.html_print_radio_button('show_id_evento', 0, '', $config['show_id_evento'], true);

$table->data[2][0] = '<b>'.__('Event name').'</b>';
$table->data[2][1] = __('Yes').'&nbsp;'.html_print_radio_button('show_evento', 1, '', $config['show_evento'], true);
$table->data[2][1] .= __('No').'&nbsp;'.html_print_radio_button('show_evento', 0, '', $config['show_evento'], true);

$table->data[3][0] = '<b>'.__('Agent ID').'</b>';
$table->data[3][1] = __('Yes').'&nbsp;'.html_print_radio_button('show_id_agente', 1, '', $config['show_id_agente'], true);
$table->data[3][1] .=__('No').'&nbsp;'. html_print_radio_button('show_id_agente', 0, '', $config['show_id_agente'], true);

$table->data[4][0] = '<b>'.__('User ID').'</b>';
$table->data[4][1] = __('Yes').'&nbsp;'.html_print_radio_button('show_id_usuario', 1, '', $config['show_id_usuario'], true);
$table->data[4][1] .= __('No').'&nbsp;'.html_print_radio_button('show_id_usuario', 0, '', $config['show_id_usuario'], true);

$table->data[5][0] = '<b>'.__('Group ID').'</b>';
$table->data[5][1] = __('Yes').'&nbsp;'.html_print_radio_button('show_id_grupo', 1, '', $config['show_id_grupo'], true);
$table->data[5][1] .= __('No').'&nbsp;'.html_print_radio_button('show_id_grupo', 0, '', $config['show_id_grupo'], true);

$table->data[6][0] ='<b>'. __('Status').'</b>';
$table->data[6][1] = __('Yes').'&nbsp;'.html_print_radio_button('show_estado', 1, '', $config['show_estado'], true);
$table->data[6][1] .= __('No').'&nbsp;'.html_print_radio_button('show_estado', 0, '', $config['show_estado'], true);

$table->data[7][0] = '<b>'.__('Timestamp').'</b>';
$table->data[7][1] = __('Yes').'&nbsp;'.html_print_radio_button('show_timestamp', 1, '', $config['show_timestamp'], true);
$table->data[7][1] .= __('No').'&nbsp;'.html_print_radio_button('show_timestamp', 0, '', $config['show_timestamp'], true);

$table->data[8][0] = '<b>'.__('Event type');
$table->data[8][1] = __('Yes').'&nbsp;'.html_print_radio_button('show_event_type', 1, '', $config['show_event_type'], true);
$table->data[8][1] .= __('No').'&nbsp;'.html_print_radio_button('show_event_type', 0, '', $config['show_event_type'], true);

$table->data[9][0] = '<b>'.__('Agent Module').'</b>';
$table->data[9][1] = __('Yes').'&nbsp;'.html_print_radio_button('show_id_agentmodule', 1, '', $config['show_id_agentmodule'], true);
$table->data[9][1] .= __('No').'&nbsp;'.html_print_radio_button('show_id_agentmodule', 0, '', $config['show_id_agentmodule'], true);

$table->data[10][0] = '<b>'.__('Alert ID').'</b>';
$table->data[10][1] = __('Yes').'&nbsp;'.html_print_radio_button('show_id_alert_am', 1, '', $config['show_id_alert_am'], true);
$table->data[10][1] .= __('No').'&nbsp;'.html_print_radio_button('show_id_alert_am', 0, '', $config['show_id_alert_am'], true);

$table->data[11][0] = '<b>'.__('Criticity').'</b>';
$table->data[11][1] = __('Yes').'&nbsp;'.html_print_radio_button('show_criticity', 1, '', $config['show_criticity'], true);
$table->data[11][1] .= __('No').'&nbsp;'.html_print_radio_button('show_criticity', 0, '', $config['show_criticity'], true);

$table->data[12][0] = '<b>'.__('Comment').'</b>';
$table->data[12][1] = __('Yes').'&nbsp;'.html_print_radio_button('show_user_comment', 1, '', $config['show_user_comment'], true);
$table->data[12][1] .= __('No').'&nbsp;'.html_print_radio_button('show_user_comment', 0, '', $config['show_user_comment'], true);

$table->data[13][0] = '<b>'.__('Tags').'</b>';
$table->data[13][1] = __('Yes').'&nbsp;'.html_print_radio_button('show_tags', 1, '', $config['show_tags'], true);
$table->data[13][1] .= __('No').'&nbsp;'.html_print_radio_button('show_tags', 0, '', $config['show_tags'], true);

$table->data[14][0] = '<b>'.__('Source').'</b>';
$table->data[14][1] = __('Yes').'&nbsp;'.html_print_radio_button('show_source', 1, '', $config['show_source'], true);
$table->data[14][1] .= __('No').'&nbsp;'.html_print_radio_button('show_source', 0, '', $config['show_source'], true);

$table->data[15][0] = '<b>'.__('Extra ID').'</b>';
$table->data[15][1] = __('Yes').'&nbsp;'.html_print_radio_button('show_id_extra', 1, '', $config['show_id_extra'], true);
$table->data[15][1] .= __('No').'&nbsp;'.html_print_radio_button('show_id_extra', 0, '', $config['show_id_extra'], true);

echo '<form id="custom_events" method="post">';

html_print_table($table);

echo '<div class="action-buttons" style="width: '.$table->width.'">';
		html_print_input_hidden ('update_config', 1);
		html_print_submit_button (__('Update'), 'upd_button', false, 'class="sub upd"');
	echo '</form>';
echo '</div>';
?>
<script>

/*
$('#radiobtn0002').css('display', 'none');
*/


</script>

