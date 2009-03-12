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

check_login ();

if (! give_acl ($config['id_user'], 0, "AR") && ! give_acl ($config['id_user'], 0, "AW")) {
	audit_db ($config["id_user"], $REMOTE_ADDR, "ACL Violation",
		"Trying to access Server view");
	require ("general/noaccess.php");
	return;
}

$modules_server = 0;
$total_modules_network = 0;
$total_modules_data = 0;


echo "<h2>".__('Pandora servers')." &gt; ";
echo __('Configuration detail')."</h2>";

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
$table->head[0] = __('Name');
$table->head[1] = __('Status');
$table->head[2] = __('Load');
$table->head[3] = __('Modules');
$table->head[4] = __('LAG');
$table->head[5] = __('Type');
$table->head[6] = __('Version');
// This will have a column of data such as "6 hours"
$table->head[7] = __('Updated');
$table->data = array ();

if (!$servers){
	echo "<div class='nf'>".__('There are no servers configured into the database')."</div>";
	return;
}

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
	$data[2] = '<img src="reporting/fgraph.php?tipo=progress&percent='.$load_percent.'&height=20&width=80">';
	$data[3] = $serverinfo["modules"] . " ".__('of')." ". $total_modules;
	$data[4] = human_time_description_raw ($serverinfo["lag"]) . " / ". $serverinfo["module_lag"];
	$data[5] = '';
	if ($server['network_server'] == 1) {
		$data[5] .= ' <img src="images/network.png" title="'.__('Network Server').'">';
	}
	if ($server['data_server'] == 1) {
		$data[5] .= ' <img src="images/data.png" title="'.__('Data Server').'">';
	}
	if ($server['snmp_server'] == 1) {
		$data[5] .= ' <img src="images/snmp.png" title="'.__('SNMP server').'">';
	}
	if ($server['recon_server'] == 1) {
		$data[5] .= ' <img src="images/recon.png" title="'.__('Recon Server').'">';
	}
	if ($server['export_server'] == 1) {
		$data[5] .= ' <img src="images/database_refresh.png" title="'.__('Export server').'">';
	}
	if ($server['wmi_server'] == 1) {
		$data[5] .= ' <img src="images/wmi.png" title="'.__('WMI Server').'">';
	}
	if ($server['prediction_server'] == 1) {
		$data[5] .= ' <img src="images/chart_bar.png" title="'.__('Prediction Server').'">';
	}
	if ($server['plugin_server'] == 1) {
		$data[5] .= ' <img src="images/plugin.png" title="'.__('Plugin Server').'">';
	}
	if ($server['master'] == 1) {
		$data[5] .= ' <img src="images/master.png" title="'.__('Master server').'">';
	}
	if ($server['checksum'] == 1){
		$data[5] .= ' <img src="images/binary.png" title="'.__('MD5 check').'">';
	}
	$data[6] = $server['version'];
	$data[7] = human_time_comparation ($server['keepalive']) . "</td>";
	
	array_push ($table->data, $data);
}

print_table ($table);
?>
