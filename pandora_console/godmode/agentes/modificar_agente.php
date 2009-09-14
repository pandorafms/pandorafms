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
check_login ();

// Take some parameters (GET)
$offset = (int) get_parameter ("offset");
$group_id = (int) get_parameter ("group_id");
$ag_group = get_parameter ("ag_group_refresh", -1);

if ($ag_group == -1 )
	$ag_group = (int) get_parameter ("ag_group", -1);

if (($ag_group == -1) && ($group_id != 0))
	$ag_group = $group_id;

if (! give_acl ($config["id_user"], 0, "AW")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access agent manager");
	require ("general/noaccess.php");
	exit;
}

$search = get_parameter ("search", "");

if (isset ($_GET["borrar_agente"])) { // if delete agent
	$id_agente = get_parameter_get ("borrar_agente");
	$agent_name = get_agent_name ($id_agente);
	$id_grupo = dame_id_grupo ($id_agente);
	if (give_acl ($config["id_user"], $id_grupo, "AW")==1) {
		$id_agentes[0] = $id_agente;
		if (delete_agent ($id_agentes))
		  audit_db($config["id_user"],$REMOTE_ADDR, "Agent \'$agent_name\' deleted", "Agent Management");
	} else { // NO permissions.
		audit_db ($config["id_user"],$REMOTE_ADDR, "ACL Violation",
			"Trying to delete agent \'$agent_name\'");
		require ("general/noaccess.php");
		exit;
	}
}
echo "<h2>".__('Agent configuration')." &raquo; ".__('Agents defined in Pandora')."</h2>";

// Show group selector
if (isset($_POST["ag_group"])){
	$ag_group = get_parameter_post ("ag_group");
	echo "<form method='post'
	action='index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&ag_group_refresh=".$ag_group."'>";
} else {
	echo "<form method='post'
	action='index.php?sec=gagente&sec2=godmode/agentes/modificar_agente'>";
}

echo "<table cellpadding='4' cellspacing='4' class='databox' width=770><tr>";
echo "<td valign='top'>".__('Group')."</td>";
echo "<td valign='top'>";
echo "<select name='ag_group' onChange='javascript:this.form.submit();'
class='w130'>";

if ($ag_group > 1) {
	echo "<option value='".$ag_group."'>".get_group_name ($ag_group).
	"</option>";
}
//echo "<option value=1>".get_group_name (1)."</option>"; // Group all is always active
$mis_grupos = list_group ($config["id_user"]); //Print combo for groups and set an array with all groups
echo "</select>";
echo "<td valign='top'>
<noscript>
<input name='uptbutton' type='submit' class='sub upd' value='".__('Show')."'>
</noscript>
</td>
</form>
<td valign='top'>";

echo __('Free text for search (*)');
echo "</td><td>";

// Show group selector
if (isset($_POST["ag_group"])){
	$group_mod = "&ag_group_refresh=".get_parameter_post ("ag_group");
} else {
	$group_mod ="";
}

echo "<form method='post' action='index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&refr=60$group_mod'>";
echo "<input type=text name='search' size='15' value='$search' >";
echo "</td><td valign='top'>";
echo "<input name='srcbutton' type='submit' class='sub' value='".__('Search')."'>";
echo "</form>";
echo "<td>";
echo '<form method="post" action="index.php?sec=gagente&amp;sec2=godmode/agentes/configurar_agente">';
	print_input_hidden ('new_agent', 1);
	print_submit_button (__('Create agent'), 'crt', false, 'class="sub next"');
	echo "</form>";

echo "</td></table>";

$search_sql = '';
if ($search != ""){
	$search_sql = " AND ( nombre LIKE '%$search%' OR direccion LIKE '%$search%') ";
} else {
}

// Show only selected groups    
if ($ag_group > 1) {
	$sql = sprintf ('SELECT COUNT(*)
		FROM tagente
		WHERE id_grupo = %d
		%s',
		$ag_group, $search_sql);
	$total_agents = get_db_sql ($sql);
	
	$sql = sprintf ('SELECT *
		FROM tagente
		WHERE id_grupo = %d
		%s
		ORDER BY nombre LIMIT %d, %d',
		$ag_group, $search_sql, $offset, $config["block_size"]);
} else {
	$sql = sprintf ('SELECT COUNT(*)
		FROM tagente
		WHERE id_grupo IN (%s)
		%s',
		implode (',', array_keys (get_user_groups ())),
		$search_sql);
	$total_agents = get_db_sql ($sql);
	
	$sql = sprintf ('SELECT *
		FROM tagente
		WHERE id_grupo IN (%s)
		%s
		ORDER BY nombre LIMIT %d, %d',
		implode (',', array_keys (get_user_groups ())),
		$search_sql, $offset, $config["block_size"]);
}

$agents = get_db_all_rows_sql ($sql);

// Prepare pagination
pagination ($total_agents, "index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&group_id=$ag_group&search=$search", $offset);
echo "<div style='height: 20px'> </div>";

if ($agents !== false) {
	
	echo "<table cellpadding='4' id='agent_list' cellspacing='4' width='95%' class='databox'>";
	echo "<th>".__('Agent name')."</th>";
	echo "<th title='".__('Remote agent configuration')."'>".__('R')."</th>";
	echo "<th>".__('OS')."</th>";
	echo "<th>".__('Group')."</th>";
	echo "<th>".__('Description')."</th>";
	echo "<th>".__('Delete')."</th>";
	$color=1;
	
	$rowPair = true;
	$iterator = 0;
	foreach ($agents as $agent) {
		$id_grupo = $agent["id_grupo"];
		if (! give_acl ($config["id_user"], $id_grupo, "AW"))
			continue;
		if ($color == 1){
			$tdcolor = "datos";
			$color = 0;
			}
		else {
			$tdcolor = "datos2";
			$color = 1;
		}
		
		
		if ($rowPair)
			$rowclass = 'rowPair';
		else
			$rowclass = 'rowOdd';
		$rowPair = !$rowPair;
		$iterator++;
		// Agent name
		echo "<tr class='$rowclass'><td class='$tdcolor' width='40%'>";
		if ($agent["disabled"]){
			echo "<em>";
		}
		echo '<span class="left">';
		echo "<strong><a href='index.php?sec=gagente&
		sec2=godmode/agentes/configurar_agente&tab=main&
		id_agente=".$agent["id_agente"]."'>".substr(salida_limpia($agent["nombre"]),0,20)."</a></strong>";
		if ($agent["disabled"]) {
			echo "</em>";
		}
		echo '</span><div class="left actions" style="visibility: hidden; clear: left">';
		echo '<a href="index.php?sec=gagente&
		sec2=godmode/agentes/configurar_agente&tab=main&
		id_agente='.$agent["id_agente"].'">'.__('Edit').'</a>';
		echo ' | ';
		echo '<a href="index.php?sec=gagente&
			sec2=godmode/agentes/configurar_agente&tab=module&
			id_agente='.$agent["id_agente"].'">'.__('Modules').'</a>';
		echo ' | ';
		echo '<a href="index.php?sec=gagente&
			sec2=godmode/agentes/configurar_agente&tab=alert&
			id_agente='.$agent["id_agente"].'">'.__('Alerts').'</a>';
		echo ' | ';
		echo '<a href="index.php?sec=estado
			&sec2=operation/agentes/ver_agente
			&id_agente='.$agent["id_agente"].'">'.__('View').'</a>';
		
		echo '</div>';
		echo "</td>";

		echo "<td align='center' class='$tdcolor'>";
		// Has remote configuration ?
		$agent_md5 = md5 ($agent["nombre"], false);
		if (file_exists ($config["remote_config"]."/md5/".$agent_md5.".md5")) {
			echo "<a href='index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=main&id_agente=".$agent["id_agente"]."&disk_conf=1'>";
			echo "<img src='images/application_edit.png' align='middle' title='".__('Edit remote config')."'>";
			echo "</a>";
		}
		echo "</td>";


		// Operating System icon
		echo "<td class='$tdcolor' align='center'>";
		print_os_icon ($agent["id_os"], false);
		echo "</td>";
		// Group icon and name
		echo "<td class='$tdcolor' align='center'>".print_group_icon ($id_grupo, true)."</td>";
		// Description
		echo "<td class='".$tdcolor."f9'>".$agent["comentarios"]."</td>";
		// Action
		//When there is only one element in page it's necesary go back page.
		if ((count($agents) == 1) && ($offset >= $config["block_size"]))
			$offsetArg = $offset - $config["block_size"];
		else
			$offsetArg = $offset;
		
		echo "<td class='$tdcolor' align='center'><a href='index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&
		borrar_agente=".$agent["id_agente"]."&search=$search&offset=$offsetArg'";
		echo ' onClick="if (!confirm(\' '.__('Are you sure?').'\')) return false;">';
		echo "<img border='0' src='images/cross.png'></a></td>";
	}
	echo "</table>";
	echo "<table width='95%'><tr><td align='right'>";
} else {
	echo "<div class='nf'>".__('There are no defined agents')."</div>";
	echo "&nbsp;</td></tr><tr><td>";
}

// Create agent button
echo '<a name="bottom">';
echo '<form method="post" action="index.php?sec=gagente&amp;sec2=godmode/agentes/configurar_agente">';
print_input_hidden ('new_agent', 1);
print_submit_button (__('Create agent'), 'crt', false, 'class="sub next"');
echo "</form></td></tr></table>";
?>

<script type="text/javascript">
$(document).ready (function () {
	$("table#agent_list tr").hover (function () {
			$(".actions", this).css ("visibility", "");
		},
		function () {
			$(".actions", this).css ("visibility", "hidden");
		});
});
</script>
