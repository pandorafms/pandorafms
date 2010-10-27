<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
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
$sortField = get_parameter('sort_field');
$sort = get_parameter('sort', 'none');

if ($ag_group == -1 )
	$ag_group = (int) get_parameter ("ag_group", -1);

if (($ag_group == -1) && ($group_id != 0))
	$ag_group = $group_id;

if (! give_acl ($config["id_user"], 0, "AW")) {
	pandora_audit("ACL Violation",
		"Trying to access agent manager");
	require ("general/noaccess.php");
	exit;
}

enterprise_include_once('include/functions_policies.php');

$search = get_parameter ("search", "");

$agent_to_delete = (int)get_parameter('borrar_agente');

if (!empty($agent_to_delete)) {
	$id_agente = $agent_to_delete;
	$agent_name = get_agent_name ($id_agente);
	$id_grupo = dame_id_grupo ($id_agente);
	if (give_acl ($config["id_user"], $id_grupo, "AW")==1) {
		$id_agentes[0] = $id_agente;
		delete_agent($id_agentes);
		pandora_audit("Agent management", "Delete Agent " . $agent_name);
	}
	else {
		// NO permissions.
		pandora_audit("ACL Violation",
			"Trying to delete agent \'$agent_name\'");
		require ("general/noaccess.php");
		exit;
	}
}

// Header
print_page_header (__('Agent configuration')." &raquo; ".__('Agents defined in Pandora'), "", false, "", true);

// Show group selector
if (isset($_POST["ag_group"])) {
	$ag_group = get_parameter_post ("ag_group");
	echo "<form method='post'
	action='index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&ag_group_refresh=".$ag_group."'>";
}
else {
	echo "<form method='post'
	action='index.php?sec=gagente&sec2=godmode/agentes/modificar_agente'>";
}

echo "<table cellpadding='4' cellspacing='4' class='databox' width='770'><tr>";
echo "<td valign='top'>".__('Group')."</td>";
echo "<td valign='top'>";

print_select_groups(false, "AR", true, "ag_group", $ag_group, 'this.form.submit();', '', 0);

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
if (isset($_POST["ag_group"])) {
	$group_mod = "&ag_group_refresh=".get_parameter_post ("ag_group");
}
else {
	$group_mod ="";
}

echo "<form method='post' action='index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&refr=60$group_mod'>";
echo "<input type=text name='search' size='15' value='$search' >";
echo "</td><td valign='top'>";
echo "<input name='srcbutton' type='submit' class='sub search' value='".__('Search')."'>";
echo "</form>";
echo "<td>";

echo '<form method="post" action="index.php?sec=gagente&amp;sec2=godmode/agentes/configurar_agente">';
	print_input_hidden ('new_agent', 1);
	print_submit_button (__('Create agent'), 'crt', false, 'class="sub next"');
echo "</form>";

echo "</td></tr></table>";

$selected = 'border: 1px solid black;';
$selectNameUp = '';
$selectNameDown = '';
$selectOsUp = '';
$selectOsDown = '';
$selectGroupUp = '';
$selectGroupDown = '';
switch ($sortField) {
	case 'name':
		switch ($sort) {
			case 'up':
				$selectNameUp = $selected;
				$order = array('field' => 'nombre', 'order' => 'ASC');
				break;
			case 'down':
				$selectNameDown = $selected;
				$order = array('field' => 'nombre', 'order' => 'DESC');
				break;
		}
		break;
	case 'os':
		switch ($sort) {
			case 'up':
				$selectOsUp = $selected;
				$order = array('field' => 'id_os', 'order' => 'ASC');
				break;
			case 'down':
				$selectOsDown = $selected;
				$order = array('field' => 'id_os', 'order' => 'DESC');
				break;
		}
		break;
	case 'group':
		switch ($sort) {
			case 'up':
				$selectGroupUp = $selected;
				$order = array('field' => 'id_grupo', 'order' => 'ASC');
				break;
			case 'down':
				$selectGroupDown = $selected;
				$order = array('field' => 'id_grupo', 'order' => 'DESC');
				break;
		}
		break;
	default:
		$selectNameUp = $selected;
		$selectNameDown = '';
		$selectOsUp = '';
		$selectOsDown = '';
		$selectGroupUp = '';
		$selectGroupDown = '';
		$order = array('field' => 'nombre', 'order' => 'ASC');
		break;
}

$search_sql = '';
if ($search != ""){
	$search_sql = " AND ( nombre COLLATE utf8_general_ci LIKE '%$search%' OR direccion LIKE '%$search%') ";
}

// Show only selected groups    
if ($ag_group > 0) {
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
		ORDER BY %s %s LIMIT %d, %d',
		$ag_group, $search_sql, $order['field'], $order['order'], $offset, $config["block_size"]);
} else {

    // Admin user get ANY group, even if they doesnt exist
    if (check_acl ($config['id_user'], 0, "PM")){
	    $sql = sprintf ('SELECT COUNT(*) FROM tagente WHERE 1=1 %s', $search_sql);
	    $total_agents = get_db_sql ($sql);
	    $sql = sprintf ('SELECT * FROM tagente WHERE 1=1 %s ORDER BY %s %s LIMIT %d, %d', $search_sql, $order['field'], $order['order'], $offset, $config["block_size"]);
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
		    ORDER BY %s %s LIMIT %d, %d',
		    implode (',', array_keys (get_user_groups ())),
		    $search_sql, $order['field'], $order['order'], $offset, $config["block_size"]);
   }
}

$agents = get_db_all_rows_sql ($sql);

// Prepare pagination
pagination ($total_agents, "index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&group_id=$ag_group&search=$search&sort_field=$sortField&sort=$sort", $offset);
echo "<div style='height: 20px'> </div>";

if ($agents !== false) {
	
	echo "<table cellpadding='4' id='agent_list' cellspacing='4' width='95%' class='databox'>";
	echo "<th>".__('Agent name') . ' ' .
		'<a href="index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&group_id='.$ag_group.'&search='.$search .'&offset='.$offset.'&sort_field=name&sort=up"><img src="images/sort_up.png" style="' . $selectNameUp . '" /></a>' .
		'<a href="index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&group_id='.$ag_group.'&search='.$search .'&offset='.$offset.'&sort_field=name&sort=down"><img src="images/sort_down.png" style="' . $selectNameDown . '" /></a>';
	echo "</th>";
	echo "<th title='".__('Remote agent configuration')."'>".__('R')."</th>";
	echo "<th>".__('OS'). ' ' .
		'<a href="index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&group_id='.$ag_group.'&search='.$search .'&offset='.$offset.'&sort_field=os&sort=up"><img src="images/sort_up.png" style="' . $selectOsUp . '" /></a>' .
		'<a href="index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&group_id='.$ag_group.'&search='.$search .'&offset='.$offset.'&sort_field=os&sort=down"><img src="images/sort_down.png" style="' . $selectOsDown . '" /></a>';
	echo "</th>";
	echo "<th>".__('Group'). ' ' .
			'<a href="index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&group_id='.$ag_group.'&search='.$search .'&offset='.$offset.'&sort_field=group&sort=up"><img src="images/sort_up.png" style="' . $selectGroupUp . '" /></a>' .
			'<a href="index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&group_id='.$ag_group.'&search='.$search .'&offset='.$offset.'&sort_field=group&sort=down"><img src="images/sort_down.png" style="' . $selectGroupDown . '" /></a>';
		echo "</th>";
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
		id_agente=".$agent["id_agente"]."'>".$agent["nombre"]."</a></strong>";
		if ($agent["disabled"]) {
			print_help_tip(__('Disabled'));
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
		echo "<td class='$tdcolor' align='center' valign='middle'>";
		print_os_icon ($agent["id_os"], false);
		echo "</td>";
		// Group icon and name
		echo "<td class='$tdcolor' align='center' valign='middle'>".print_group_icon ($id_grupo, true)."</td>";
		// Description
		echo "<td class='".$tdcolor."f9'>".$agent["comentarios"]."</td>";
		// Action
		//When there is only one element in page it's necesary go back page.
		if ((count($agents) == 1) && ($offset >= $config["block_size"]))
			$offsetArg = $offset - $config["block_size"];
		else
			$offsetArg = $offset;
		
		echo "<td class='$tdcolor' align='center' valign='middle'><a href='index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&
		borrar_agente=".$agent["id_agente"]."&search=$search&offset=$offsetArg&sort_field=$sortField&sort=$sort'";
		echo ' onClick="if (!confirm(\' '.__('Are you sure?').'\')) return false;">';
		echo "<img border='0' src='images/cross.png'></a></td>";
	}
	echo "</table>";
	pagination ($total_agents, "index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&group_id=$ag_group&search=$search&sort_field=$sortField&sort=$sort", $offset);
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
