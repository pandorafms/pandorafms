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
require ("include/config.php");

check_login();

if (! give_acl ($config['id_user'], 0, "AR")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access Agent Data view");
	require ("general/noaccess.php");
	exit;
}

function datos_raw ($id_agente_modulo, $periodo) {
	global $config;
	
	$periodo_label = $periodo;
	switch ($periodo) {
	case "mes":
		$periodo = 2592000;
		$et=__('One month');
		break;
	case "semana":
		$periodo = 604800;
		$et=__('One week');
		break;
	case "dia":
		$periodo = 86400;
		$et=__('Last 24 Hours');
		break;
	}
	$periodo = time () - $periodo;
	$id_agent = give_agent_id_from_module_id ($id_agente_modulo);
	$id_group = get_db_value ("id_grupo", "tagente", "id_agente", $id_agent);
	// Different query for string data type
	$id_tipo_modulo = dame_id_tipo_modulo_agentemodulo($id_agente_modulo);
	if ( (dame_nombre_tipo_modulo ($id_tipo_modulo) == "generic_data_string" ) ||
	     (dame_nombre_tipo_modulo ($id_tipo_modulo) == "remote_tcp_string" ) ||
 	     (dame_nombre_tipo_modulo ($id_tipo_modulo) == "remote_snmp_string" )) {
		$sql1="SELECT * FROM tagente_datos_string WHERE id_agente_modulo = ".
		$id_agente_modulo." AND id_agente = $id_agent AND utimestamp > '".$periodo."' 
		ORDER BY timestamp DESC"; 
		$string_type = 1;
	}
	else {
		$sql1 = "SELECT * FROM tagente_datos WHERE id_agente_modulo = ".
			$id_agente_modulo." AND id_agente = $id_agent AND utimestamp > '".$periodo."' 
			ORDER BY timestamp DESC";
		$string_type = 0;
	}
	
	$result = mysql_query ($sql1);
	$nombre_agente = dame_nombre_agente_agentemodulo ($id_agente_modulo);
	$nombre_modulo = dame_nombre_modulo_agentemodulo ($id_agente_modulo);
	
	echo "<h2>".__('Received data from')." 
	'$nombre_agente' / '$nombre_modulo' </h2>";
	echo "<h3>". $et ."</h3>";
	if (mysql_num_rows ($result)) {
		echo "<table cellpadding='3' cellspacing='3' width='600' class='databox'>";
		$color=1;
		echo "<th>".__('Delete')."</th>";
		echo "<th>".__('Timestamp')."</th>";
		echo "<th width='400'>".__('Data')."</th>";
		while ($row=mysql_fetch_array($result)){
			if ($color == 1){
				$tdcolor = "datos";
				$color = 0;
			} else {
				$tdcolor = "datos2";
				$color = 1;
			}
			echo "<tr>";
			if ((give_acl ($config['id_user'], $id_group, "AW") ==1) && ($string_type == 0)) {
				echo "<td class='".$tdcolor."' width=20>";
				echo "<a href='index.php?sec=estado&sec2=operation/agentes/datos_agente&tipo=$periodo_label&id=$id_agente_modulo&delete=".$row["id_agente_datos"]."'><img src='images/cross.png' border=0>";
			} else {
				echo "<td class='".$tdcolor."'>";
			}
			echo "<td class='".$tdcolor."' style='width:150px'>".$row["timestamp"]."</td>";
			echo "<td class='".$tdcolor."'>";
			if (is_numeric ($row["datos"])) {
				echo format_for_graph ($row["datos"]);
			} else {
				echo salida_limpia ($row["datos"]);
			}
			echo "</td></tr>";
		}
		echo "</table>";
	} else {
		echo "<div class='nf'>no_data</div>";
	}
}	

// ---------------
// Page begin
// ---------------

if (isset ($_GET["tipo"]) && isset ($_GET["id"])) {
	$id = get_parameter ("id");
	$tipo= get_parameter ("tipo");
} else {
	echo "<h3 class='error'>".__('There was a problem locating the source of the graph')."</h3>";
	exit;
}

if (isset($_GET["delete"])) {
	$delete =$_GET["delete"];
	$sql = "DELETE FROM tagente_datos WHERE id_agente_datos = $delete";
	$result = process_sql ($sql);
}

datos_raw ($id, $tipo);

?>
