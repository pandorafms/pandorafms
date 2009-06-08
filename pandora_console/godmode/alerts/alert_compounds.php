<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

check_login ();

if (! give_acl ($config['id_user'], 0, "LM")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access Alert Management");
	require ("general/noaccess.php");
	exit;
}

require_once ('include/functions_alerts.php');

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

$groups = get_user_groups (0, 'LM');
if ($id_group != 1 && isset ($groups[$id_group]))
	$agents = get_group_agents ($id_group, false, "none");
else
	$agents = get_group_agents (array_keys ($groups), false, "none");

$delete_alert = (int) get_parameter ('delete_alert');
$enable_alert = (int) get_parameter ('enable_alert');
$disable_alert = (int) get_parameter ('disable_alert');

if ($delete_alert) {
	$id = (int) get_parameter ('id');
	$result = delete_alert_compound ($id);
	print_result_message ($result,
		__('Successfully deleted'),
		__('Could not be deleted'));
	if (is_ajax ())
		return;
}

if ($enable_alert) {
	$id = (int) get_parameter ('id');
	$result = set_alerts_compound_disable ($id, false);
	print_result_message ($result,
		__('Successfully enabled'),
		__('Could not be enabled'));
	if (is_ajax ())
		return;
}

if ($disable_alert) {
	$id = (int) get_parameter ('id');
	$result = set_alerts_compound_disable ($id, true);
	print_result_message ($result, 
		__('Successfully disabled'),
		__('Could not be disabled'));
	if (is_ajax ())
		return;
}

echo '<h1>'.__('Correlated alerts').'</h1>';

$table->id = 'filter_compound_table';
$table->width = '90%';
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
$table->data[0][1] = print_select ($groups, 'id_group', $id_group, false, '',
	'', true);
$table->data[0][2] = __('Agent');
$table->data[0][2] .= ' <span id="agent_loading" class="invisible">';
$table->data[0][2] .= '<img src="images/spinner.gif" />';
$table->data[0][2] .= '</span>';
$table->data[0][3] = print_select ($agents, 'id_agent', $id_agent, false,
	__('All'), 0, true);

$table->data[1][0] = __('Free search');
$table->data[1][1] = print_input_text ('search', $search, '', 20, 40, true);
$table->colspan[1][1] = 3;

echo '<form id="filter_form" method="post" action="index.php?galertas&sec2=godmode/alerts/alert_compounds">';
print_table ($table);
echo '<div class="action-buttons" style="width: 90%">';
print_input_hidden ('do_search', 1);
print_submit_button (__('Search'), 'search_btn', false, 'class="sub search"');
echo '</div>';
echo '</form>';
unset ($table);

$where = '';
if ($search != '')
	$where = sprintf (' AND (description LIKE "%%%s%%" OR name LIKE "%%%s%%")',
		$search, $search);
if ($id_agent)
	$agents = array ($id_agent => $id_agent);
$sql = sprintf ('SELECT COUNT(*) FROM talert_compound
	WHERE id_agent in (%s)%s',
	implode (',', array_keys ($agents)), $where);
$total = (int) get_db_sql ($sql);
pagination ($total, $url);

$table->id = 'alert_list';
$table->class = 'alert_list databox';
$table->width = '90%';
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

$sql = sprintf ('SELECT id FROM talert_compound
	WHERE id_agent in (%s)%s LIMIT %d OFFSET %d',
	implode (',', array_keys ($agents)), $where,
	$config['block_size'], get_parameter ('offset'));
$id_alerts = get_db_all_rows_sql ($sql);

if ($id_alerts === false)
	$id_alerts = array ();

foreach ($id_alerts as $alert) {
	$alert = get_alert_compound ($alert['id']);
	if ($alert === false)
		continue;
	
	$data = array ();
	
	$data[0] = '<form class="disable_alert_form" action="'.$url.'" method="post" style="display: inline;">';
	if ($alert['disabled']) {
		$data[0] .= print_input_image ('enable', 'images/lightbulb_off.png', 1, '', true);
		$data[0] .= print_input_hidden ('enable_alert', 1, true);
	} else {
		$data[0] .= print_input_image ('disable', 'images/lightbulb.png', 1, '', true);
		$data[0] .= print_input_hidden ('disable_alert', 1, true);
	}
	$data[0] .= print_input_hidden ('id', $alert['id'], true);
	$data[0] .= '</form>';
	
	$data[1] = '<a href="index.php?sec=galertas&sec2=godmode/alerts/configure_alert_compound&id='.$alert['id'].'">';
	$data[1] .= $alert['name'];
	$data[1] .= '</a>';
	$data[2] = get_agent_name ($alert['id_agent']);
	$data[3] = '<a href="'.$url.'&delete_alert=1&id='.$alert['id'].'"
		onClick="javascript:confirm(\''.__('Are you sure').'\')">';
	$data[3] .= '<img src="images/cross.png" title="'.__('Delete').'" />';
	$data[3] .= '</a>';
	
	array_push ($table->data, $data);
}

print_table ($table);

echo '<div class="action-buttons" style="width: '.$table->width.'">';
echo '<form method="post" action="index.php?sec=galertas&sec2=godmode/alerts/configure_alert_compound">';
print_submit_button (__('Create'), 'crtbtn', false, 'class="sub next"');
print_input_hidden ('new_compound', 1);
echo '</form>';
echo '</div>';

require_jquery_file ('pandora.controls');
?>

<script type="text/javascript">
$(document).ready (function () {
	$("#id_group").pandoraSelectGroupAgent ();
});
</script>
