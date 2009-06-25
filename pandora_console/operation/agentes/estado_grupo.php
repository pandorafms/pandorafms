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




// Load global vars
require_once ("include/config.php");

if (! isset($config["show_lastalerts"]))
	$config["show_lastalerts"] = 1;

if (! give_acl ($config['id_user'], 0, "AR")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation", 
	"Trying to access Agent view (Grouped)");
	require ("general/noaccess.php");
	exit;
}
echo "<h2>".__('Pandora agents')." &raquo; ".__('Group view')."</h2>";

// Update network modules for this group
// Check for Network FLAG change request
// Made it a subquery, much faster on both the database and server side
if (isset ($_GET["update_netgroup"])) {
	$group = get_parameter_get ("update_netgroup", 0);
	if (give_acl ($config['id_user'], $group, "AW")) {
		$sql = sprintf ("UPDATE tagente_modulo SET `flag` = 1 WHERE `id_agente` = ANY(SELECT id_agente FROM tagente WHERE `id_grupo` = %d)",$group);
		process_sql ($sql);
	} else {
		audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation", "Trying to set flag for groups");
		require ("general/noaccess.php");
		exit;
	}
}

// Get group list that user has access
$groups = get_user_groups ($config['id_user']);

$groups_info = array ();
$total_agents = 0;
$now = get_system_time ();
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
			'normal' => 0,
			'critical' => 0,
			'warning' => 0,
			'alerts' => 0,
			'down' => 0,
			'icon' => get_group_icon ($id_group),
			'id_group' => $id_group,
			'name' => $group_name);
	
	// SQL Join to get monitor status for agents belong this group
	$sql = sprintf ("SELECT tagente_estado.estado, tagente_modulo.module_interval,
			tagente_estado.utimestamp, tagente_modulo.id_tipo_modulo 
			FROM tagente, tagente_estado, tagente_modulo 
			WHERE tagente.disabled = 0 
			AND tagente.id_grupo = %d 
			AND tagente.id_agente = tagente_estado.id_agente 
			AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
			AND tagente_modulo.disabled = 0
			AND tagente_estado.utimestamp != 0",
			$id_group);
	$modules = get_db_all_rows_sql ($sql);
	if ($modules === false)
		$modules = array ();
	foreach ($modules as $module) {
		$seconds = $now - $module['utimestamp'];
		if ($seconds >= ($module['module_interval'] * 2)) {
			if ($module['id_tipo_modulo'] < 21) // Avoiding ASYNC and Keepalive
				$group_info['down']++;
		} elseif ($module['estado'] == 2) {
			$group_info['warning']++;
		} elseif ($module['estado'] == 1) {
			$group_info['critical']++;
		} else {
			$group_info['normal']++;
		}
	}

	if ($config["show_lastalerts"] == 1) {
		// How many alerts has been fired recently for this group:
		// SQL Join to get alert status for agents belong this group
		$sql = sprintf ("SELECT SUM(talert_template_modules.times_fired)
				FROM tagente_modulo, talert_template_modules, tagente
				WHERE tagente.disabled = 0
				AND tagente.id_grupo = %d
				AND tagente.id_agente = tagente_modulo.id_agente
				AND talert_template_modules.id_agent_module = tagente_modulo.id_agente_modulo",
				$id_group);
		$group_info["alerts"] = (int) get_db_sql ($sql);
	}
	array_push ($groups_info, $group_info);
}

if ($total_agents == 0) {
	echo '<div class="nf">'.__('There are no defined groups').'</div>';
	if (give_acl ($config['id_user'], 0, "LM")
		|| give_acl ($config['id_user'], 0, "AW")
		|| give_acl ($config['id_user'], 0, "PM")
		|| give_acl ($config['id_user'], 0, "DM")
		|| give_acl ($config['id_user'], 0, "UM")) {
		
		echo '&nbsp;<form method="post" action="index.php?sec=gagente&sec2=godmode/groups/configure_group&create_g=1">';
		print_submit_button (__('Create group'), 'crt', false, 'class="sub next"');
		echo '</form>';
	}
	return;
}

$group_size = sizeof ($groups_info);
$ancho = ceil (sqrt ($group_size + 1));
$cells_across = $ancho;
if ($ancho > 5) { //If the cells would cross the line (more than 5)
	$over = $ancho - 5; //Calculate how much we are over
	$cells_across -= $over; //Take what we're over off cells_across
	$ancho += $over; //And add them to ancho (which holds depth)
}
$real_count = 0;

echo '<table cellpadding="10" cellspacing="10" border="0">';
for ($table = 0; $table < $ancho; $table++) {
	if ($real_count >= $group_size) {
		continue;
	}
	echo '<tr class="bot">';
	
	//foreach ($groups_info as $group) {
	for ($table_row = 0; $table_row < $cells_across; $table_row++) {
		if ($real_count >= $group_size) {
			continue;
		}
		$group_info = $groups_info[$real_count];
		
		$group_name  = $group_info["name"];
		$icono_grupo = $group_info["icon"];
		$icono_type  = "";
		if ($group_info['critical'] > 0) {
			$icono_type .= '<img src="images/dot_red.png" title="'.__('Modules critical').'" />';
		}
		
		if ($group_info["normal"] > 0) {
			$icono_type .= '<img src="images/dot_green.png" title="'.__('Modules normal').'" />';
		}
		
		if ($group_info["warning"] > 0) {
			$icono_type .= '<img src="images/dot_yellow.png" title="'.__('Modules warning').'" />';
		}
			
		// Show yellow light if there are recent alerts fired for this group
		if ($group_info["alerts"] > 0 ) {
			$icono_type .= '<img src="images/dot_magenta.png" title="'.__('Alerts fired').'" />';
		}
		
		// Show grey light if there are agent down for this group
		if ($group_info["down"] > 0 ) {
			$icono_type .= '<img src="images/dot_white.png"	title="'.__('Agents down').'" />';
		}
		
		// Show red flag is group has disabled alert system
		if (give_disabled_group ($group_info["id_group"])) {
			$icono_type .= '<img src="images/flag_red.png" title="'.__('Disabled alerts').'" />';
		}
		
		// By default green border
		$celda = '<td class="top" style="border: 5px solid #aeff21;" width="100">';
		
		// Grey border if agent down
		if ($group_info["down"] > 0)
			$celda = '<td class="top" style="border: 5px solid #aabbaa;" width="100">';
		
		// Yellow border if agents WARNING
		if ($group_info["warning"] > 0)
			$celda = '<td class="top" style="border: 5px solid #FFD800;" width="100">';
		
		// Red border if agents CRITICAL
		if ($group_info["critical"] > 0)
			$celda = '<td class="top" style="border: 5px solid #FF0000;" width="100">';
		
		// Magenta border if agents with alerts
		if ($group_info["alerts"] > 0)
			$celda = '<td class="top" style="border: 5px solid #F200FF;" width="100">';
		
		// Black if alerts and down modules
		if (($group_info["critical"] > 0) && ($group_info["alerts"] > 0))
			$celda = '<td class="top" style="border: 5px solid #000000;" width="100">';
		
		$celda .= '<a href="index.php?sec=estado&amp;sec2=operation/agentes/estado_agente&amp;refr=60&amp;group_id='.$group_info["id_group"].'"	class="info">';
		
		// Add group icon
		$celda .= '<img class="top" src="images/groups_small/'.$icono_grupo.'.png" height="32" width="32" alt="" />';
		
		// Add float info table
		$celda .= '<span><table cellspacing="0" cellpadding="0" style="margin-left:2px; background: #ffffff;">
					<tr><th colspan="2" width="140">'.__('Agents').':</th></tr>
					<tr><td colspan="2" class="datos" align="center"><b>'.$group_info["agent"].'</b></td></tr>
				</table>
				<table cellspacing="0" cellpadding="2" style="margin-left:2px">
					
					<tr><td class="datos"><img src="images/b_green.png" align="top" alt="" />'.__('Normal').'</td>
					<td class="datos"><b>'.format_for_graph ($group_info["normal"] , 1).'</b></td></tr>
					<tr><td class="datos"><img src="images/b_yellow.png" align="top" alt="" />'.__('Warning').'</td>
					<td class="datos"><b>'.format_for_graph ($group_info["warning"] , 1).'</b></td></tr>
					<tr><td class="datos"><img src="images/b_red.png" align="top" alt="" />'.__('Critical').'</td>
					<td class="datos"><b>'.format_for_graph ($group_info["critical"] , 1).'</b></td></tr>';
		
			$celda .= '<tr><td class="datos"><img src="images/b_white.png" align="top" alt="" />'.__('Down').'</td>
				<td class="datos"><b>'.format_for_graph ($group_info["down"] , 1).'</b></td></tr>';
		
			$celda .= '<tr><td class="datos"><img src="images/b_magenta.png" align="top" alt="" />'.__('Alerts').'</td>
				<td class="datos"><b>'.$group_info["alerts"].'</b></td></tr>';
		
		$celda .= "</table></span></a>";
		
		// Render network exec module button, only when this group is writtable by user
		if (give_acl ($config['id_user'], $group_info["id_group"], "AW")) {
			$celda .= '&nbsp;<a href="index.php?sec=estado&sec2=operation/agentes/estado_grupo&update_netgroup='.$group_info["id_group"].'"><img src="images/target.png" /></a>';
		}
		
		$celda .= '<br /><br />'.$icono_type.'<br /><br /><span class="gr">'.$group_name.'</span>';
		
		echo $celda;
		$real_count++;
	}
	echo "</tr>";
}
echo "</table>";

?>
