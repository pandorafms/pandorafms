<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas, http://www.artica.es
// Please see http://pandora.sourceforge.net for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
// Load global vars

require_once ("include/config.php");

check_login ();

if (! give_acl ($config['id_user'], 0, "PM")) {
	audit_db($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access event viewer");
	require ("general/noaccess.php");
	exit;
}

echo "<h2>".__('Pandora audit')." &gt ".__('Review Logs')."</h2>";
$offset = get_parameter ("offset", 0);
$tipo_log = get_parameter ("tipo_log", 'all');

echo '<div style="width:450px; float:left;">';
echo '<h3>'.__('Filter').'</h3>';

// generate select

$rows = get_db_all_rows_sql ("SELECT DISTINCT(accion) FROM tsesion");
if (empty ($rows)) {
	$rows = array ();
}

$actions = array ();

foreach ($rows as $row) {
	$actions[$row["accion"]] = $row["accion"]; 
}
	
echo '<form name="query_sel" method="post" action="index.php?sec=godmode&sec2=godmode/admin_access_logs">';
echo __('Action').': ';
print_select ($actions, 'tipo_log', $tipo_log, 'this.form.submit();', __('All'), 'all');
echo '<br /><noscript><input name="uptbutton" type="submit" class="sub" value="'.__('Show').'"></noscript>';
echo '</form></div>';

echo '<div style="width:300px; height:140px; float:left;">';
echo '<img src="reporting/fgraph.php?tipo=user_activity&width=300&height=140" />';
echo '</div><div style="clear:both;">&nbsp;</div>';

$filter = '';
if ($tipo_log != 'all') {
	$filter = sprintf (" WHERE accion = '%s'", $tipo_log);
}

$sql = "SELECT COUNT(*) FROM tsesion".$filter;
$count = get_db_sql ($sql);
$url = "index.php?sec=godmode&sec2=godmode/admin_access_logs&tipo_log=".$tipo_log;

pagination ($count, $url, $offset);


$sql = sprintf ("SELECT * FROM tsesion%s ORDER BY fecha DESC LIMIT %d, %d", $filter, $offset, $config["block_size"]);
$result = get_db_all_rows_sql ($sql);

if (empty ($result)) {
	$result = array ();
}

$table->cellpadding = 4;
$table->cellspacing = 4;
$table->width = 700;
$table->class = "databox";
$table->size = array ();
$table->data = array ();
$table->head = array ();

$table->head[0] = __('User');
$table->head[1] = __('Action');
$table->head[2] = __('Date');
$table->head[3] = __('Source IP');
$table->head[4] = __('Comments');

$table->size[0] = 80;
$table->size[2] = 130;
$table->size[3] = 100;
$table->size[4] = 200;

// Get data
foreach ($result as $row) {
	$data = array ();
	$data[0] = $row["ID_usuario"];
	$data[1] = $row["accion"];
	$data[2] = $row["fecha"];
	$data[3] = $row["IP_origen"];
	$data[4] = $row["descripcion"];
	array_push ($table->data, $data);
}

print_table ($table);

?>
