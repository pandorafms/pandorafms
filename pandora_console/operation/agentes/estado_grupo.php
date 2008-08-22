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

if (! isset($config["show_lastalerts"]))
	$config["show_lastalerts"] = 1;

if (! give_acl ($config['id_user'], 0, "AR")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation", 
	"Trying to access Agent view (Grouped)");
	require ("general/noaccess.php");
	exit;
}
echo "<h2>".__('Pandora Agents')." &gt; ".__('Group view')."</h2>";

// Update network modules for this group
// Check for Network FLAG change request
// Made it a subquery, much faster on both the database and server side
if (isset ($_GET["update_netgroup"])) {
	if (give_acl ($config['id_user'], $_GET["update_netgroup"], "AW")) {
		$sql = sprintf ("UPDATE tagente_modulo SET `flag` = '1' WHERE `id_agente` = ANY(SELECT id_agente FROM tagente WHERE `id_grupo` =  '%d')",$_GET["update_netgroup"]);
		mysql_query ($sql);
	}
}

// Get group list that user has access
$groups = get_user_groups ($config['id_user']);
$groups_info = array ();
$total_agents = 0;
$now = time ();
// Prepare data to show
// For each valid group for this user, take data from agent and modules
foreach ($groups as $id_group => $group_name) {
	$sql = sprintf ("SELECT COUNT(id_agente)
			FROM tagente
			WHERE id_grupo = %d AND disabled = 0",
			$id_group);
	$agents = get_db_sql ($sql);
	if ($agents == 0)
		continue;
	
	$total_agents += $agents;
	$group_info = array ('agent' => $agents,
			'ok' => 0,
			'bad' => 0,
			'alerts' => 0,
			'down' => 0,
			'icon' => dame_grupo_icono ($id_group),
			'id_group' => $id_group,
			'name' => $group_name);
	
	// SQL Join to get monitor status for agents belong this group
	$sql = sprintf ("SELECT tagente_estado.datos, tagente_estado.current_interval,
			tagente_estado.utimestamp
			FROM tagente, tagente_estado, tagente_modulo 
			WHERE tagente.disabled = 0 
			AND tagente.id_grupo = %d 
			AND tagente.id_agente = tagente_estado.id_agente 
			AND tagente_estado.estado != 100
			AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
			AND tagente_modulo.disabled = 0
			AND tagente_estado.utimestamp != 0",
			$id_group);
	$result = mysql_query ($sql);
	while ($module = mysql_fetch_assoc ($result)) {
		//if ($config["show_unknown"] > 0) {
		//this needs to be filled out somehow, but this was a serious bug. If that config var is set, it would short circuit both ok++ and bad++ returning empty for everything
		//}
		$seconds = $now - $module['utimestamp'];
		// Down = module/agent down (as in it didn't monitor in time)
		// Bad  = module bad  (as in it did monitor but it returned 0)
		if ($seconds >= ($module['current_interval'] * 2)) {
			$group_info["down"]++;
		} elseif ($module['datos'] != 0) {
			$group_info["ok"]++;
		} else {
			$group_info["bad"]++;
		}
	}

	if ($config["show_lastalerts"] == 1) {
		// How many alerts has been fired recently for this group:
		// SQL Join to get alert status for agents belong this group
		$sql = sprintf ("SELECT SUM(talerta_agente_modulo.times_fired)
				FROM tagente_modulo, talerta_agente_modulo, tagente
				WHERE tagente.disabled = 0
				AND tagente.id_grupo = %d
				AND tagente.id_agente = tagente_modulo.id_agente
				AND talerta_agente_modulo.id_agente_modulo = tagente_modulo.id_agente_modulo",
				$id_group);
		$group_info["alerts"] = get_db_sql ($sql);
	}
	array_push ($groups_info, $group_info);
}

if ($total_agents == 0) {
	echo "<div class='nf'>".__('There are no defined groups')."</div>";
	if (give_acl ($config['id_user'], 0, "LM")
		|| give_acl ($config['id_user'], 0, "AW")
		|| give_acl ($config['id_user'], 0, "PM")
		|| give_acl ($config['id_user'], 0, "DM")
		|| give_acl ($config['id_user'], 0, "UM")) {
		
		echo "&nbsp;<form method='post' action='index.php?sec=gagente&sec2=godmode/groups/configure_group&create_g=1'><input type='submit' class='sub next' name='crt'
	value='".__('Create group')."'></form>";
	}
	return;
}

$group_size = sizeof ($groups_info);
$ancho = ceil (sqrt ($group_size + 1));
$real_count = 0;

echo "<table cellpadding=10 cellspacing=10 border=0>";
for ($table = 0; $table < $ancho; $table++) {
	if ($real_count >= $group_size) {
		continue;
	}
	echo "<tr class='bot'>";
	
	//foreach ($groups_info as $group) {
	for ($table_row = 0; $table_row < $ancho; $table_row++) {
		if ($real_count >= $group_size) {
			continue;
		}
		$group_info = $groups_info[$real_count];
		
		$group_name  = $group_info["name"];
		$icono_grupo = $group_info["icon"];
		$icono_type  = "";
		if ($group_info["bad"] > 0) {
			$icono_type .= '<img src="images/dot_red.png"
					title="'.__('Modules bad').'">';
		}
		
		if ($group_info["ok"] > 0) {
			$icono_type .= '<img src="images/dot_green.png" 
					title="'.__('Modules OK').'">';
		}
			
		// Show yellow light if there are recent alerts fired for this group
		if ($group_info["alerts"] > 0 ) {
			$icono_type .= '<img src="images/dot_yellow.png"
					title="'.__('Alerts fired').'">';
		}
		
		// Show grey light if there are agent down for this group
		if ($group_info["down"] > 0 ) {
			$icono_type .= '<img src="images/dot_white.png"
					title="'.__('Agents down').'">';
		}
		
		// Show red flag is group has disabled alert system
		if (give_disabled_group ($group_info["id_group"])) {
			$icono_type .= '<img src="images/flag_red.png"
					title="'.__('Disabled alerts').'">';
		}
		
		// By default green border
		$celda = "<td class='top' style='border: 5px solid #aeff21;' width='100'>";
		
		// Grey border if agent down
		if ($config["show_unknown"] > 0) {
			if ($group_info["down"] > 0)
				$celda = "<td class='top' style='border: 5px solid #aabbaa;' width='100'>";
		}
		
		// Yellow border if agents with alerts
		if ($group_info["alerts"] > 0)
			$celda = "<td class='top' style='border: 5px solid #ffea00;' width='100'>";
		
		// Red border if agents bad
		if ($group_info["bad"] > 0)
			$celda = "<td class='top' style='border: 5px solid #ff0000;' width='100'>";
		
		// Orange if alerts and down modules
		if (($group_info["bad"] > 0) && ($group_info["alerts"] > 0))
			$celda = "<td class='top' style='border: 5px solid #ffbb00;' width='100'>";
		
		$celda .= "<a href='index.php?sec=estado&amp;
		sec2=operation/agentes/estado_agente&amp;
		refr=60&amp;
		group_id=".$group_info["id_group"]."'
		class='info'>";
		
		// Add group icon
		$celda .= "<img class='top'
			src='images/groups_small/".$icono_grupo.".png' height='32'  width='32' alt=''>";
		
		// Add float info table
		$celda .= "
			<span>
			<table cellspacing='2' cellpadding='0'
			style='margin-left:2px;'>
				<tr><th colspan='2' width='91'>".
				__('Agents').": </th></tr>
				<tr><td colspan='2' class='datos' align='center'><b>".
				$group_info["agent"]."</b></td></tr>
			</table>
			<table cellspacing='2' cellpadding='0'
			style='margin-left:2px'>
				<tr>
				<th colspan='2' width='90'>".
				ucfirst (__('Monitors')).":</th>
				</tr>
				<tr>
				<td class='datos'>
				<img src='images/b_green.png' align='top' alt='' >
				".__('ok').": </td>
				<td class='datos'>
				<font class='greenb'>".$group_info["ok"]."</font>
				</td>
				</tr>
				<tr>
				<td class='datos'>
				<img src='images/b_red.png' align='top' alt=''>
				".__('Fail').": </td>
				<td class='datos'><font class='redb'>".
				$group_info["bad"]."</font></td>
				</tr>";
		if ($config["show_unknown"] > 0) {
			$celda .= "
			<tr>
			<td class='datos'>
			<img src='images/b_white.png' align='top' alt=''>
			".__('Down').": </td>
			<td class='datos'><font class='redb'>".
			$group_info["down"]."</font></td></tr>";
		}
		if ($config["show_lastalerts"] == 1)
			$celda .= "<tr>
			<td class='datos'>
			<img src='images/b_yellow.png' align='top' alt=''>
			".__('Alerts').": </td>
			<td class='datos'><font class='grey'>".
			$group_info["alerts"]."</font></td>
			</tr>";
		$celda .= "</table></span></a>";
		
		// Render network exec module button, only when this group is writtable by user
		if (give_acl ($config['id_user'], $group_info["id_group"], "AW")) {
			$celda .= "&nbsp;<a href='index.php?
			sec=estado&
			sec2=operation/agentes/estado_grupo&
			update_netgroup=".$group_info["id_group"]."'>
			<img src='images/target.png'></a>";
		}
		$celda .= "<br><br>".
		$icono_type."<br><br>
		<span class='gr'>".$group_name."</span>";
		echo $celda;
		$real_count++;
	}
	echo "</tr>";
}
echo "</table>";

?>
