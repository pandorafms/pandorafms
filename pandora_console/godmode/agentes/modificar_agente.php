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

if (! check_acl ($config["id_user"], 0, "AW")) {
	db_pandora_audit("ACL Violation",
		"Trying to access agent manager");
	require ("general/noaccess.php");
	exit;
}

enterprise_include_once('include/functions_policies.php');
require_once ('include/functions_agents.php');
require_once ('include/functions_users.php');

//Add enterprise function to add other enterprise ACL.
$enterprise_acl = false;
if (ENTERPRISE_NOT_HOOK !== enterprise_include_once('include/functions_policies.php')) {
	$enterprise_acl = true;
}

$search = get_parameter ("search", "");

$agent_to_delete = (int)get_parameter('borrar_agente');

if (!empty($agent_to_delete)) {
	$id_agente = $agent_to_delete;
	$agent_name = agents_get_name ($id_agente);
	$id_grupo = agents_get_agent_group($id_agente);
	if (check_acl ($config["id_user"], $id_grupo, "AW")==1) {
		$id_agentes[0] = $id_agente;
		agents_delete_agent($id_agentes);
		db_pandora_audit("Agent management", "Delete Agent " . $agent_name);
	}
	else {
		// NO permissions.
		db_pandora_audit("ACL Violation",
			"Trying to delete agent \'$agent_name\'");
		require ("general/noaccess.php");
		exit;
	}
}

// Header
ui_print_page_header (__('Agent configuration')." &raquo; ".__('Agents defined in Pandora'), "", false, "", true);

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

html_print_select_groups(false, "AR", true, "ag_group", $ag_group, 'this.form.submit();', '', 0);

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
	html_print_input_hidden ('new_agent', 1);
	html_print_submit_button (__('Create agent'), 'crt', false, 'class="sub next"');
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
	$total_agents = db_get_sql ($sql);
	
	switch ($config["dbtype"]) {
		case "mysql":
			$sql = sprintf ('SELECT *
				FROM tagente
				WHERE id_grupo = %d
				%s
				ORDER BY %s %s LIMIT %d, %d',
				$ag_group, $search_sql, $order['field'], $order['order'], $offset, $config["block_size"]);
			break;
		case "postgresql":
			$sql = sprintf ('SELECT *
				FROM tagente
				WHERE id_grupo = %d
				%s
				ORDER BY %s %s LIMIT %d OFFSET %d',
				$ag_group, $search_sql, $order['field'], $order['order'], $config["block_size"], $offset);
			break;
		case "oracle":
			$set = array ();
			$set['limit'] = $config["block_size"];
			$set['offset'] = $offset;
			$sql = sprintf ('SELECT *
				FROM tagente
				WHERE id_grupo = %d
				%s
				ORDER BY %s %s',
				$ag_group, $search_sql, $order['field'], $order['order']);
			$sql = oracle_recode_query ($sql, $set);
			break;
	}
}
else {

    // Admin user get ANY group, even if they doesnt exist
    if (check_acl ($config['id_user'], 0, "PM")){
	    $sql = sprintf ('SELECT COUNT(*) FROM tagente WHERE 1=1 %s', $search_sql);
	    $total_agents = db_get_sql ($sql);
    	switch ($config["dbtype"]) {
			case "mysql":
				$sql = sprintf ('SELECT *
					FROM tagente WHERE 1=1 %s
					ORDER BY %s %s LIMIT %d, %d', $search_sql, $order['field'], $order['order'], $offset, $config["block_size"]);
				break;
			case "postgresql":
				$sql = sprintf ('SELECT *
					FROM tagente WHERE 1=1 %s
					ORDER BY %s %s LIMIT %d OFFSET %d', $search_sql, $order['field'], $order['order'], $config["block_size"], $offset);
				break;
			case "oracle":
				$set = array ();
				$set['limit'] = $config["block_size"];
				$set['offset'] = $offset;
				$sql = sprintf ('SELECT *
					FROM tagente WHERE 1=1 %s
					ORDER BY %s %s', $search_sql, $order['field'], $order['order']);
				$sql = oracle_recode_query ($sql, $set);
				break;
		}
    }
    else {
		if (!$enterprise_acl) {
		    $sql = sprintf ('SELECT COUNT(*)
			    FROM tagente
			    WHERE id_grupo IN (%s)
			    %s',
			    implode (',', array_keys (get_user_groups ())),
			    $search_sql);    
			    
		    $total_agents = db_get_sql ($sql);
		    
	        switch ($config["dbtype"]) {
				case "mysql":
				    $sql = sprintf ('SELECT *
					    FROM tagente
					    WHERE id_grupo IN (%s)
					    %s
					    ORDER BY %s %s LIMIT %d, %d',
					    implode (',', array_keys (get_user_groups ())),
					    $search_sql, $order['field'], $order['order'], $offset, $config["block_size"]);
					break;
				case "postgresql":
				    $sql = sprintf ('SELECT *
					    FROM tagente
					    WHERE id_grupo IN (%s)
					    %s
					    ORDER BY %s %s LIMIT %d OFFSET %d',
					    implode (',', array_keys (get_user_groups ())),
					    $search_sql, $order['field'], $order['order'], $config["block_size"], $offset);
					break;
				case "oracle":
				    $set = array ();
				    $set['limit'] = $config["block_size"];
				    $set['offset'] = $offset;
				    $sql = sprintf ('SELECT *
					    FROM tagente
					    WHERE id_grupo IN (%s)
					    %s
					    ORDER BY %s %s',
					    implode (',', array_keys (get_user_groups ())),
					    $search_sql, $order['field'], $order['order']);
				    $sql = oracle_recode_query ($sql, $set);
					break;
			}
		}
		else {
			$total_agents = enterprise_count_agents_manage_agents($search_sql);
			
			$sql = enterprise_sql_manage_agents($search_sql, $order, $offset);
		}	
   }
}

$agents = db_get_all_rows_sql ($sql);

// Delete rnum row generated by oracle_recode_query() function
if (($config['dbtype'] == 'oracle') && ($agents !== false)) {
	for ($i=0; $i < count($agents); $i++) {
		unset($agents[$i]['rnum']);		
	}
}

// Prepare pagination
ui_pagination ($total_agents, "index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&group_id=$ag_group&search=$search&sort_field=$sortField&sort=$sort", $offset);
echo "<div style='height: 20px'> </div>";

if ($agents !== false) {
	
	echo "<table cellpadding='4' id='agent_list' cellspacing='4' width='95%' class='databox'>";
	echo "<th>".__('Agent name') . ' ' .
		'<a href="index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&group_id='.$ag_group.'&search='.$search .'&offset='.$offset.'&sort_field=name&sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectNameUp)) . '</a>' .
		'<a href="index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&group_id='.$ag_group.'&search='.$search .'&offset='.$offset.'&sort_field=name&sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectNameDown)) . '</a>';
	echo "</th>";
	echo "<th title='".__('Remote agent configuration')."'>".__('R')."</th>";
	echo "<th>".__('OS'). ' ' .
		'<a href="index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&group_id='.$ag_group.'&search='.$search .'&offset='.$offset.'&sort_field=os&sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectOsUp)) . '</a>' .
		'<a href="index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&group_id='.$ag_group.'&search='.$search .'&offset='.$offset.'&sort_field=os&sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectOsDown)) . '</a>';
	echo "</th>";
	echo "<th>".__('Group'). ' ' .
			'<a href="index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&group_id='.$ag_group.'&search='.$search .'&offset='.$offset.'&sort_field=group&sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectGroupUp)) . '</a>' .
			'<a href="index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&group_id='.$ag_group.'&search='.$search .'&offset='.$offset.'&sort_field=group&sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectGroupDown)) . '</a>';
		echo "</th>";
	echo "<th>".__('Description')."</th>";
	echo "<th>".__('Delete')."</th>";
	$color=1;
	
	$rowPair = true;
	$iterator = 0;
	foreach ($agents as $agent) {
		$id_grupo = $agent["id_grupo"];
		if (! check_acl ($config["id_user"], $id_grupo, "AW"))
			continue;
		if ($color == 1) {
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
		if ($agent["disabled"]) {
			echo "<em>";
		}
		echo '<span class="left">';
		echo "<strong><a href='index.php?sec=gagente&
		sec2=godmode/agentes/configurar_agente&tab=main&
		id_agente=".$agent["id_agente"]."'>" . ui_print_truncate_text($agent["nombre"], 30, true)."</a></strong>";
		if ($agent["disabled"]) {
			ui_print_help_tip(__('Disabled'));
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
			echo html_print_image("images/application_edit.png", true, array("align" => 'middle', "title" => __('Edit remote config')));		
			echo "</a>";
		}
		echo "</td>";


		// Operating System icon
		echo "<td class='$tdcolor' align='center' valign='middle'>";
		ui_print_os_icon ($agent["id_os"], false);
		echo "</td>";
		// Group icon and name
		echo "<td class='$tdcolor' align='center' valign='middle'>" . ui_print_group_icon ($id_grupo, true)."</td>";
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
		echo html_print_image('images/cross.png', true, array("border" => '0')) . "</a></td>";
	}
	echo "</table>";
	ui_pagination ($total_agents, "index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&group_id=$ag_group&search=$search&sort_field=$sortField&sort=$sort", $offset);
	echo "<table width='95%'><tr><td align='right'>";
}
else {
	echo "<div class='nf'>".__('There are no defined agents')."</div>";
	echo "&nbsp;</td></tr><tr><td>";
}

// Create agent button
echo '<a name="bottom">';
echo '<form method="post" action="index.php?sec=gagente&amp;sec2=godmode/agentes/configurar_agente">';
html_print_input_hidden ('new_agent', 1);
html_print_submit_button (__('Create agent'), 'crt', false, 'class="sub next"');
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
