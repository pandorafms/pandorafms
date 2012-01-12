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

require_once ($config["homedir"] . '/include/functions_graph.php'); 

check_login ();

$enterprise_include = enterprise_include_once('godmode/admin_access_logs.php');

if (! check_acl ($config['id_user'], 0, "PM")) {
	db_pandora_audit( "ACL Violation",
		"Trying to access event viewer");
	require ("general/noaccess.php");
	exit;
}

ui_print_page_header (__('Pandora audit')." &raquo; ".__('Review Logs'), "", false, "", true );

$offset = get_parameter ("offset", 0);
$tipo_log = get_parameter ("tipo_log", 'all');
$user_filter = get_parameter('user_filter', 'all');
$filter_text = get_parameter('filter_text', '');
$filter_hours_old = get_parameter('filter_hours_old', 24);
$filter_ip = get_parameter('filter_ip', '');

echo "<table width='98%' border='0' cellspacing='4' cellpadding='4' class='databox'>";
echo '<tr><td class="datost">';
echo '<div style="float: left; width: 400px;">';
echo '<b>'.__('Filter').'</b><br><br>';

$rows = db_get_all_rows_sql ("SELECT DISTINCT(accion) FROM tsesion");
if (empty ($rows)) {
	$rows = array ();
}
$actions = array ();

foreach ($rows as $row) {
	$actions[$row["accion"]] = $row["accion"]; 
}
echo '<form name="query_sel" method="post" action="index.php?sec=godmode&sec2=godmode/admin_access_logs">';
$table = null;
$table->width = '100%';
$table->data = array();
$table->data[0][0] = __('Action');
$table->data[0][1] = html_print_select ($actions, 'tipo_log', $tipo_log, '', __('All'), 'all', true);
$table->data[1][0] = __('User');
$table->data[1][1] = html_print_select_from_sql('SELECT id_user, id_user AS text FROM tusuario', 'user_filter', $user_filter, '', __('All'), 'all',  true);
$table->data[2][0] = __('Free text for search (*)');
$table->data[2][1] = html_print_input_text('filter_text', $filter_text, __('Free text for search (*)'), 20, 40, true);
$table->data[3][0] = __('Max. hours old');
$table->data[3][1] = html_print_input_text('filter_hours_old', $filter_hours_old, __('Max. hours old'), 3, 6, true);
$table->data[4][0] = __('IP');
$table->data[4][1] = html_print_input_text('filter_ip', $filter_ip, __('IP'), 15, 15, true);
$table->data[5][0] = '';
$table->data[5][1] = html_print_submit_button(__('Filter'), 'filter', false, 'class="sub search" style="float: right;"', true);
html_print_table($table);
echo '</form>';
echo '</div>';
echo '<div style="float: right; width: 300px;">';

echo graphic_user_activity(300, 140);

echo '</div>';
echo '<div style="clear:both;">&nbsp;</div>';
echo '</td></tr></table>';



$filter = 'WHERE 1 = 1';

if ($tipo_log != 'all') {
	$filter .= sprintf (" AND accion = '%s'", $tipo_log);
}
switch ($config['dbtype']) {
	case "mysql":
		if ($user_filter != 'all') {
			$filter .= sprintf(' AND id_usuario = "%s"', $user_filter);
		}

		$filter .= ' AND (accion LIKE "%' . $filter_text . '%" OR descripcion LIKE "%' . $filter_text . '%")';

		if ($filter_ip != '') {
			$filter .= sprintf(' AND ip_origen LIKE "%s"', $filter_ip);
		}
		break;
	case "postgresql":
	case "oracle":
		if ($user_filter != 'all') {
			$filter .= sprintf(' AND id_usuario = \'%s\'', $user_filter);
		}

		$filter .= ' AND (accion LIKE \'%' . $filter_text . '%\' OR descripcion LIKE \'%' . $filter_text . '%\')';

		if ($filter_ip != '') {
			$filter .= sprintf(' AND ip_origen LIKE \'%s\'', $filter_ip);
		}
		break;
}

if ($filter_hours_old != 0) {
	switch ($config["dbtype"]) {
		case "mysql":
			$filter .= ' AND fecha >= DATE_ADD(NOW(), INTERVAL -' . $filter_hours_old . ' HOUR)';
			break;
		case "postgresql":
			$filter .= ' AND fecha >= DATE_ADD(NOW(), INTERVAL - \'' . $filter_hours_old . ' HOUR \')';
			break;
		case "oracle":
			$filter .= ' AND fecha >= (SYSTIMESTAMP - INTERVAL \'' . $filter_hours_old . '\' HOUR)';
			break;
	}
}

$sql = "SELECT COUNT(*) FROM tsesion " . $filter;
$count = db_get_sql ($sql);
$url = "index.php?sec=godmode&sec2=godmode/admin_access_logs&tipo_log=".$tipo_log."&user_filter=".$user_filter."&filter_text=".$filter_text."&filter_hours_old=".$filter_hours_old."&filter_ip=".$filter_ip;

ui_pagination ($count, $url);

switch ($config["dbtype"]) {
	case "mysql":
		$sql = sprintf ("SELECT * FROM tsesion %s ORDER BY fecha DESC LIMIT %d, %d", $filter, $offset, $config["block_size"]);
		break;
	case "postgresql":
		$sql = sprintf ("SELECT * FROM tsesion %s ORDER BY fecha DESC LIMIT %d OFFSET %d", $filter, $config["block_size"], $offset);
		break;
	case "oracle":
		$set = array();
		$set['limit'] = $config["block_size"];
		$set['offset'] = $offset;
		$sql = sprintf ("SELECT * FROM tsesion %s ORDER BY fecha DESC", $filter);
		$result = oracle_recode_query ($sql, $set);
		break;
}

$result = db_get_all_rows_sql ($sql);

// Delete rnum row generated by oracle_recode_query() function
if (($config["dbtype"] == 'oracle') && ($result !== false)){
	for ($i=0; $i < count($result); $i++) {
		unset($result[$i]['rnum']);		
	}
}

if (empty ($result)) {
	$result = array ();
}

$table->cellpadding = 4;
$table->cellspacing = 4;
$table->width = '98%';
$table->class = "databox";
$table->size = array ();
$table->data = array ();
$table->head = array ();
$table->align = array();
$table->rowclass = array();

$table->head[0] = __('User');
$table->head[1] = __('Action');
$table->head[2] = __('Date');
$table->head[3] = __('Source IP');
$table->head[4] = __('Comments');
if ($enterprise_include !== ENTERPRISE_NOT_HOOK) {
	$table->head[5] = enterprise_hook('tableHeadEnterpriseAudit', array('title1'));
}

if ($enterprise_include !== ENTERPRISE_NOT_HOOK) {
	$table->head[6] = enterprise_hook('tableHeadEnterpriseAudit', array('title2'));
}

$table->size[0] = 80;
$table->size[2] = 130;
$table->size[3] = 100;
$table->size[4] = 200;
if ($enterprise_include !== ENTERPRISE_NOT_HOOK) {
	$table->size[5] = enterprise_hook('tableHeadEnterpriseAudit', array('size1'));
}
if ($enterprise_include !== ENTERPRISE_NOT_HOOK) {
	$table->size[6] = enterprise_hook('tableHeadEnterpriseAudit', array('size2'));
}


if ($enterprise_include !== ENTERPRISE_NOT_HOOK) {
	$table->align[5] = enterprise_hook('tableHeadEnterpriseAudit', array('align'));
}
if ($enterprise_include !== ENTERPRISE_NOT_HOOK) {
	$table->align[6] = enterprise_hook('tableHeadEnterpriseAudit', array('align2'));
}

$table->colspan = array();
$table->rowstyle = array();


$rowPair = true;
$iterator = 0;

// Get data
foreach ($result as $row) {
	if ($rowPair)
		$table->rowclass[$iterator] = 'rowPair';
	else
		$table->rowclass[$iterator] = 'rowOdd';
	$rowPair = !$rowPair;
	$iterator++;

	$data = array ();
	switch ($config['dbtype']) {
		case "mysql":
		case "postgresql":
			$data[0] = $row["id_usuario"];
			break;
		case "oracle":
			$data[0] = $row["id_usuario"];
			break;
	}
	$data[1] = $row["accion"];
	$data[2] = $row["fecha"];
	switch ($config['dbtype']) {
		case "mysql":
		case "postgresql":
			$data[3] = $row["ip_origen"];
			break;
		case "oracle":
			$data[3] = $row["ip_origen"];
			break;
	}
	$data[4] = io_safe_output($row["descripcion"]);
	if ($enterprise_include !== ENTERPRISE_NOT_HOOK) {
		switch ($config['dbtype']) {
			case "mysql":
			case "postgresql":
				$data[5] = enterprise_hook('cell1EntepriseAudit', array($row['id_sesion']));
				break;
			case "oracle":
				$data[5] = enterprise_hook('cell1EntepriseAudit', array($row['id_sesion']));
				break;
		}
	}
	if ($enterprise_include !== ENTERPRISE_NOT_HOOK) {
		switch ($config['dbtype']) {
			case "mysql":
			case "postgresql":
				$data[6] = enterprise_hook('cell2EntepriseAudit', array($row['id_sesion']));
				break;
			case "oracle":
				$data[6] = enterprise_hook('cell2EntepriseAudit', array($row['id_sesion']));
				break;
		}
	}
	array_push ($table->data, $data);
	
	
	if ($enterprise_include !== ENTERPRISE_NOT_HOOK) {
		switch ($config['dbtype']) {
			case "mysql":
			case "postgresql":
				enterprise_hook('rowEnterpriseAudit', array($table, &$iterator, $row['id_sesion']));
				break;
			case "oracle":
				enterprise_hook('rowEnterpriseAudit', array($table, &$iterator, $row['id_sesion']));
				break;
		}
	}
}

html_print_table ($table);

if ($enterprise_include !== ENTERPRISE_NOT_HOOK) {
	enterprise_hook('enterpriseAuditFooter');
}
?>
