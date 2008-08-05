<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnológicas, http://www.artica.es
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
require("include/config.php");

$modules_server = 0;
$total_modules_network = 0;
$total_modules_data = 0;

if (comprueba_login() != 0) {
	audit_db($config["id_user"],$REMOTE_ADDR, "ACL Violation","Trying to access Agent view");
	require ($config["homeurl"]."/general/noaccess.php");
}

if ((give_acl($id_user, 0, "AR")==0) AND (give_acl($id_user,0,"AW") == 0) AND (dame_admin($id_user) == 0) ){
	audit_db($config["id_user"],$REMOTE_ADDR, "ACL Violation","Trying to access Agent view");
	require ($config["homeurl"]."/general/noaccess.php");
}

echo "<h2>".lang_string ("view_servers")." &gt; ";
echo lang_string ("server_detail")."</h2>";

$total_modules = (int) get_db_sql ("SELECT COUNT(*)
				FROM tagente_modulo
				WHERE tagente_modulo.disabled = 0");
$servers = get_db_all_rows_in_table ('tserver');
if (sizeof ($servers) == 0)
	return;

$table->width = '98%';
$table->size = array ();
$table->size[6] = '60';
$table->align = array ();
$table->align[1] = 'center';
$table->align[6] = 'center';
$table->head = array ();
$table->head[0] = lang_string ("name");
$table->head[1] = lang_string ('status');
$table->head[2] = lang_string ('load');
$table->head[3] = lang_string ('modules');
$table->head[4] = lang_string ('lag');
$table->head[5] = lang_string ('description');
$table->head[6] = lang_string ('type');
$table->head[7] = lang_string ('version');
$table->head[8] = lang_string ('lastupdate');
$table->data = array ();

foreach ($servers as $server) {
	$data = array ();
	$serverinfo = server_status ($server['id_server']);
	if ($server["recon_server"]==1)
		$data[0] = "<b><a href='index.php?sec=estado_server&sec2=operation/servers/view_server_detail&server_id=".$server["id_server"]."'>".$server['name']."</A></b>";
	else
		$data[0] = "<b>".$server['name']."</b>";

	if ($server['status'] == 0){
		$data[1] = '<img src="images/pixel_red.png" width="20" height="20">';
	} else {
		$data[1] = '<img src="images/pixel_green.png" width="20" height="20">';
	}
	// Load
	if ($total_modules > 0)
		$load_percent = $serverinfo["modules"] / ($total_modules / 100);
	else
		$load_percent = 0;
	if ($load_percent > 100)
		$load_percent = 100;
	$data[2] = '<img src="reporting/fgraph.php?tipo=progress&percent='.$load_percent.'&height=18&width=80">';
	$data[3] = $serverinfo["modules"] . " ".lang_string("of")." ". $total_modules;
	$data[4] = human_time_description_raw ($serverinfo["lag"]) . " / ". $serverinfo["module_lag"];
	$data[5] = '<span title="'.$server['description'].'">'.substr ($server['description'], 0, 25).'</span>';
	$data[6] = '';
	if ($server['network_server'] == 1) {
		$data[6] .= ' <img src="images/network.png" title="'.lang_string ('network_server').'">';
	}
	if ($server['data_server'] == 1) {
		$data[6] .= ' <img src="images/data.png" title="'.lang_string ('data_server').'">';
	}
	if ($server['snmp_server'] == 1) {
		$data[6] .= ' <img src="images/snmp.png" title="'.lang_string ('snmp_server').'">';
	}
	if ($server['recon_server'] == 1) {
		$data[6] .= ' <img src="images/recon.png" title="'.lang_string ('recon_server').'">';
	}
	if ($server['export_server'] == 1) {
		$data[6] .= ' <img src="images/database_refresh.png" title="'.lang_string ('export_server').'">';
	}
	if ($server['wmi_server'] == 1) {
		$data[6] .= ' <img src="images/wmi.png" title="'.lang_string ('wmi_server').'">';
	}
	if ($server['prediction_server'] == 1) {
		$data[6] .= ' <img src="images/chart_bar.png" title="'.lang_string ('prediction_server').'">';
	}
	if ($server['plugin_server'] == 1) {
		$data[6] .= ' <img src="images/plugin.png" title="'.lang_string ('plugin_server').'">';
	}
	if ($server['master'] == 1) {
		$data[6] .= ' <img src="images/master.png" title="'.lang_string ('master_server').'">';
	}
	if ($server['checksum'] == 1){
		$data[6] .= ' <img src="images/binary.png" title="'.lang_string ('checksum').'">';
	}
	$data[7] = $server['version'];
	$data[8] = human_time_comparation ($server['keepalive']) . "</td>";
	
	array_push ($table->data, $data);
}

print_table ($table);
?>
