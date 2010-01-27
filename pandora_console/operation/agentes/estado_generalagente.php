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

require_once ("include/functions_agents.php");

if ($config['flash_charts']) {
	require_once ("include/fgraph.php");
}

check_login ();

$id_agente = get_parameter_get ("id_agente", -1);

$agent = get_db_row ("tagente", "id_agente", $id_agente);

if ($agent === false) {
	echo '<h3 class="error">'.__('There was a problem loading agent').'</h3>';
	return;
}

if (! give_acl ($config["id_user"], $agent["id_grupo"], "AR")) {
	audit_db ($config["id_user"], $REMOTE_ADDR, "ACL Violation", 
			  "Trying to access Agent General Information");
	require_once ("general/noaccess.php");
	return;
}

echo "<h2>".__('Pandora agents')." &raquo; ".__('Agent general information')."</h2>";

// Blank space below title
echo '<div style="height: 10px">&nbsp;</div>';	
	
//Floating div
echo '<div style="float:right; width:320px; padding-top:11px;">';
echo '<b>'.__('Agent access rate (24h)').'</b><br />';
if ($config['flash_charts']) {
	echo graphic_agentaccess ($id_agente, 280, 110, 86400);
} else {
	echo '<img border="1" src="include/fgraph.php?id='.$id_agente.'&tipo=agentaccess&height=90&width=290" />';
}
echo '<div style="height:25px">&nbsp;</div>';
echo '<b>'.__('Events generated -by module-').'</b><br />';
if ($config['flash_charts']) {
	echo graphic_agentevents ($id_agente, 290, 60, 86400);
	echo graph_event_module (290, 120, $id_agente);
} else {
	echo '<img border="1" src="include/fgraph.php?tipo=event_module&width=290&height=180&id_agent='.$id_agente.'" />';
}
echo '</div>';
	
echo '<div width="450px">';
echo '<table cellspacing="4" cellpadding="4" border="0" class="databox">';
//Agent name
echo '<tr><td class="datos"><b>'.__('Agent name').'</b></td>';
echo '<td class="datos"><b>'.$agent["nombre"].'</b></td>';
echo '<td class="datos" width="40"><a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$id_agente.'&refr=60"><img src="images/refresh.png" border="0" title="'.__('Refresh data').'" /></a>&nbsp;';
echo '<a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&flag_agent=1&id_agente='.$id_agente.'"><img src="images/target.png" border="0" title="'.__('Flag').'" /></a></td></tr>';

//Addresses
echo '<tr><td class="datos2"><b>'.__('IP Address').'</b></td>';
echo '<td class="datos2" colspan="2">';
print_select (get_agent_addresses ($id_agente), "not_used", get_agent_address ($id_agente));
echo '</td></tr>';

//OS
echo '<tr><td class="datos"><b>'.__('OS').'</b></td>';
echo '<td class="datos" colspan="2">'.print_os_icon ($agent["id_os"], true, true);

// Want to print last 15 characters of OS version, or start from 0 if smaller
$osversion_offset = strlen($agent["os_version"]);
if ($osversion_offset > 15)
	$osversion_offset = $osversion_offset - 15;
else
	$osversion_offset = 0;


echo '&nbsp;<i><span title="'.$agent["os_version"].'">'.substr($agent["os_version"],$osversion_offset,15).' </span></i></td></tr>';

// Parent
echo '<tr><td class="datos2"><b>'.__('Parent').'</b></td>';
echo '<td class="datos2" colspan="2"><a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$agent["id_parent"].'">'.get_agent_name ($agent["id_parent"]).'</a></td></tr>';

// Agent Interval
echo '<tr><td class="datos"><b>'.__('Interval').'</b></td>';
echo '<td class="datos" colspan="2">'.human_time_description_raw ($agent["intervalo"]).'</td></tr>';
	
// Comments
echo '<tr><td class="datos2"><b>'.__('Description').'</b></td>';
echo '<td class="datos2" colspan="2">'.$agent["comentarios"].'</td></tr>';

// Group
echo '<tr><td class="datos"><b>'.__('Group').'</b></td>';
echo '<td class="datos" colspan="2">';
echo print_group_icon ($agent["id_grupo"], true);
echo '&nbsp;(<b>';
echo get_group_name ($agent["id_grupo"]);
echo '</b>)</td></tr>';

// Agent version
echo '<tr><td class="datos2"><b>'.__('Agent Version'). '</b></td>';
echo '<td class="datos2" colspan="2">'.$agent["agent_version"].'</td></tr>';

// Position Information
if ($config['activate_gis']) {
	echo '<tr><td class="datos2"><b>'.__('Position (Long, Lat)'). '</b></td>';
    echo '<td class="datos2" colspan="2"> <a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&tab=gis&id_agente='.$id_agente.'">'.$agent["last_longitude"].', '.$agent["last_latitude"].'</a></td></tr>';
}
// Last contact
echo '<tr><td class="datos2"><b>'.__('Last contact')." / ".__('Remote').'</b></td><td class="datos2 f9" colspan="2">';
print_timestamp ($agent["ultimo_contacto"]);

echo " / ";

if ($agent["ultimo_contacto_remoto"] == "0000-00-00 00:00:00") { 
	echo __('Never');
} else {
	echo $agent["ultimo_contacto_remoto"];
}
echo '</td></tr>';

// Timezone Offset
if ($agent['timezone_offset'] != 0) {
	echo '<tr><td class="datos2"><b>'.__('Timezone Offset'). '</b></td>';
	echo '<td class="datos2" colspan="2">'.$agent["timezone_offset"].'</td></tr>';
}
// Next contact (agent)
$progress = getNextAgentContact($id_agente);

echo '<tr><td class="datos"><b>'.__('Next agent contact').'</b></td>';
echo '<td class="datos f9" colspan="2"><img src="include/fgraph.php?tipo=progress&percent='.$progress.'&height=20&width=200"></td></tr>';

//End of table
echo '</table></div>';
?>
