<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

global $config;

check_login ();

if (! check_acl ($config['id_user'], 0, "LM")) {
	db_pandora_audit("ACL Violation",
		"Trying to access Alert Management");
	require ("general/noaccess.php");
	exit;
}

require_once ('include/functions_alerts.php');
require_once($config['homedir'] . "/include/functions_agents.php");
require_once($config['homedir'] . '/include/functions_users.php');

$id_group = (int) get_parameter ('id_group');
$id_agent = (int) get_parameter ('id_agent');
$search = (string) get_parameter ('search');

$url = 'index.php?galertas&sec2=godmode/alerts/alert_compounds';
if ($id_group)
	$url .= '&id_group='.$id_group;
if ($id_agent)
	$url .= '&id_agent='.$id_agent;
if ($search != '');
	$url .= '&search='.$search;

$groups = users_get_groups (0, 'LM');
if ($id_group > 0 && isset ($groups[$id_group]))
	$agents = agents_get_group_agents ($id_group, false, "none");
else
	$agents = agents_get_group_agents (array_keys ($groups), false, "none");

$update_compound = (bool) get_parameter ('update_compound');
$delete_alert = (int) get_parameter ('delete_alert');
$enable_alert = (int) get_parameter ('enable_alert');
$disable_alert = (int) get_parameter ('disable_alert');

// Header
ui_print_page_header (__('Alerts').' &raquo; '.__('Correlated alerts'), "images/god2.png", false, "alert_compound", true);

if ($update_compound) {
	$id = (int) get_parameter ('id');
        
	$recovery_notify = (bool) get_parameter ('recovery_notify');
	$field2_recovery = (string) get_parameter ('field2_recovery');
	$field3_recovery = (string) get_parameter ('field3_recovery');

	$result = alerts_update_alert_compound ($id,
		array ('recovery_notify' => $recovery_notify,
			'field2_recovery' => $field2_recovery,
			'field3_recovery' => $field3_recovery));

	ui_print_result_message ($result,
		__('Successfully updated'),
		__('Could not be updated'));
}

if ($delete_alert) {
	$id = (int) get_parameter ('id');
	$result = alerts_delete_alert_compound ($id);
	ui_print_result_message ($result,
		__('Successfully deleted'),
		__('Could not be deleted'));
	if (is_ajax ())
		return;
}

if ($enable_alert) {
	$id = (int) get_parameter ('id');
	$result = alerts_set_alerts_compound_disable ($id, false);
	ui_print_result_message ($result,
		__('Successfully enabled'),
		__('Could not be enabled'));
	if (is_ajax ())
		return;
}

if ($disable_alert) {
	$id = (int) get_parameter ('id');
	$result = alerts_set_alerts_compound_disable ($id, true);
	ui_print_result_message ($result, 
		__('Successfully disabled'),
		__('Could not be disabled'));
	if (is_ajax ())
		return;
}

$table->id = 'filter_compound_table';
$table->width = '98%';
$table->data = array ();
$table->style = array ();
$table->style[0] = 'font-weight: bold; vertical-align:top';
$table->style[2] = 'font-weight: bold';
$table->size = array ();
$table->colspan = array ();
$table->size[0] = '15%';
$table->size[1] = '35%';
$table->size[2] = '15%';
$table->size[3] = '35%';

$table->data[0][0] = __('Group');
$table->data[0][1] = html_print_select_groups(0, "LM", true, 'id_group', $id_group, false, '',
	'', true);
$table->data[0][2] = __('Agent');
$table->data[0][2] .= ' <span id="agent_loading" class="invisible">';
$table->data[0][2] .= html_print_image("images/spinner.png", true);
$table->data[0][2] .= '</span>';
$table->data[0][3] = html_print_select ($agents, 'id_agent', $id_agent, false,
	__('All'), 0, true);

$table->data[1][0] = __('Free search');
$table->data[1][1] = html_print_input_text ('search', $search, '', 20, 40, true);
$table->colspan[1][1] = 3;

echo '<form id="filter_form" method="post" action="index.php?galertas&sec2=godmode/alerts/alert_compounds">';
html_print_table ($table);
echo '<div class="action-buttons" style="width: 90%">';
html_print_input_hidden ('do_search', 1);
html_print_submit_button (__('Search'), 'search_btn', false, 'class="sub search"');
echo '</div>';
echo '</form>';
unset ($table);

$where = '';
if ($search != '') {
	switch ($config["dbtype"]) {
		case "mysql":
		case "postgresql":
			$where = sprintf (' AND (description LIKE "%%%s%%" OR name LIKE "%%%s%%")',
				$search, $search);
			break;
		case "oracle":
			$where = sprintf (' AND (description LIKE \'%%%s%%\' OR name LIKE \'%%%s%%\')',
				$search, $search);
			break;
	}
}
if ($id_agent)
	$agents = array ($id_agent => $id_agent);

$total = 0;
if (count($agents) > 0) {
	$sql = sprintf ('SELECT COUNT(*) FROM talert_compound
		WHERE id_agent in (%s)%s',
		implode (',', array_keys ($agents)), $where);
	$total = (int) db_get_sql ($sql);
}
ui_pagination ($total, $url);

$table->id = 'alert_list';
$table->class = 'alert_list databox';
$table->width = '98%';
$table->data = array ();
$table->head = array ();
$table->style = array ();
$table->style[1] = 'font-weight: bold';
$table->align = array ();
$table->align[3] = 'center';
$table->size = array ();
$table->size[0] = '20px';
$table->size[3] = '20px';
$table->head[0] = '';
$table->head[1] = __('Name');
$table->head[2] = __('Agent');
$table->head[3] = __('Delete');

$id_alerts = false;
if (count($agents)) {
	switch ($config["dbtype"]) {
		case "mysql":
		case "postgresql":
			$sql = sprintf ('SELECT id FROM talert_compound
				WHERE id_agent in (%s)%s LIMIT %d OFFSET %d',
				implode (',', array_keys ($agents)), $where,
				$config['block_size'], get_parameter ('offset'));
			break;
		case "oracle":
			$set = array();
			$set['offset'] = get_parameter ('offset');
			$set['limit'] = $config['block_size'];			
			$sql = sprintf ('SELECT id FROM talert_compound
				WHERE id_agent in (%s)%s',
				implode (',', array_keys ($agents)), $where);
			$sql = oracle_recode_query($sql, $set);
			break;
	}
	$id_alerts = db_get_all_rows_sql ($sql);

	if (($config["dbtype"] == 'oracle') && ($id_alerts !== false)) {
		for ($i=0; $i < count($id_alerts); $i++) {
			unset($id_alerts[$i]['rnum']);		
		}
	}		
}

if ($id_alerts === false)
	$id_alerts = array ();

foreach ($id_alerts as $alert) {
	$alert = alerts_get_alert_compound ($alert['id']);
	if ($alert === false)
		continue;
	
	$data = array ();
	
	$data[0] = '<form class="disable_alert_form" action="'.$url.'" method="post" style="display: inline;">';
	if ($alert['disabled']) {
		$data[0] .= html_print_input_image ('enable', 'images/lightbulb_off.png', 1, '', true);
		$data[0] .= html_print_input_hidden ('enable_alert', 1, true);
	} else {
		$data[0] .= html_print_input_image ('disable', 'images/lightbulb.png', 1, '', true);
		$data[0] .= html_print_input_hidden ('disable_alert', 1, true);
	}
	$data[0] .= html_print_input_hidden ('id', $alert['id'], true);
	$data[0] .= '</form>';
	
	$data[1] = '<a href="index.php?sec=galertas&sec2=godmode/alerts/configure_alert_compound&id='.$alert['id'].'">';
	$data[1] .= $alert['name'];
	$data[1] .= '</a>';
	$data[2] = agents_get_name ($alert['id_agent']);
	$data[3] = '<a href="'.$url.'&delete_alert=1&id='.$alert['id'].'"
		onClick="javascript:confirm(\''.__('Are you sure?').'\')">';
	$data[3] .= html_print_image("images/cross.png", true, array("title" => __('Delete'))); 
	$data[3] .= '</a>';
	
	array_push ($table->data, $data);
}

if (isset($data)){
	html_print_table ($table);
}
else {
	echo "<div class='nf'>".__('No alerts found')."</div>";
}

echo '<div class="action-buttons" style="width: '.$table->width.'">';
echo '<form method="post" action="index.php?sec=galertas&sec2=godmode/alerts/configure_alert_compound">';
html_print_submit_button (__('Create'), 'crtbtn', false, 'class="sub next"');
html_print_input_hidden ('new_compound', 1);
echo '</form>';
echo '</div>';

ui_require_jquery_file ('pandora.controls');
?>

<script type="text/javascript">
$(document).ready (function () {
	$("#id_group").pandoraSelectGroupAgent ();
});
</script>
