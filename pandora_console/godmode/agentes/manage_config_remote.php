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

check_login();

$origen = get_parameter ("origen", -1);
$id_group = get_parameter ("id_group", -1);
$update_agent = get_parameter ("update_agent", -1);
$update_group = get_parameter ("update_group", -1);

if (! give_acl ($config['id_user'], 0, "AW")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access remote config copy tool");
	require ("general/noaccess.php");
	exit;
}

// Operations
if ((isset($_GET["operacion"])) AND ($update_group == -1) ) {

	// DATA COPY
	// ---------
	if (isset($_POST["copy"])) {
		echo "<h2>".__('Data Copy')."</h2>";
		// Initial checkings

		// if selected more than 0 agents
		$destino = $_POST["destino"];
		if (count($destino) <= 0){
			echo "<h3 class='error'>ERROR: ".__('No selected agents to copy')."</h3>";
			echo "</table>";
			include ("general/footer.php");
			exit;
		}

		// Source
		$id_origen = get_parameter ("origen");

		// Security check here
		if (!user_access_to_agent ($id_origen)) {
			audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation", "Trying to forge a source agent in remote config tool");
			require ("general/noaccess.php");
			exit;
		}		

		// Copy files
		for ($a=0;$a <count($destino); $a++){ 
			// For every agent in destination

			//Security check here
			$id_agente = $destino[$a];
			
			// Security check here
			if (!user_access_to_agent ($id_agente)){
				audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation", "Trying to forge a source agent in remote config tool");
				require ("general/noaccess.php");
				exit;
			}			

			$agent_name_src = get_agent_name($id_origen, "");
			$agent_name_dst = get_agent_name($id_agente, "");
			echo "<br><br>".__('Making copy of configuration file for')." [<b>".$agent_name_src."</b>] ".__('to')." [<b>".$agent_name_dst."</b>]";
			
			$source = $config["remote_config"]."/".md5($agent_name_src);
			$destination = $config["remote_config"]."/".md5($agent_name_dst);

			copy  ( $source.".md5", $destination.".md5" );
			copy  ( $source.".conf", $destination.".conf" );			
		} // for each destination agent
	} //end if copy modules or alerts


	// ============	
	// Form view
	// ============
	} else { 
		
		// title
		echo '<h2>'.__('Agent configuration'). ' &raquo; '. __('Remote configuration management').'</h2>';
		echo '<form method="post" action="index.php?sec=gagente&sec2=godmode/agentes/manage_config_remote&operacion=1">';
		echo "<table width='650' border='0' cellspacing='4' cellpadding='4' class='databox'>";
		
		// Source group
		echo '<tr><td class="datost"><b>'. __('Source group'). '</b><br><br>';

		$group_select = get_user_groups ($config['id_user']);
		$grouplist = implode (',', array_keys ($group_select));
		
		echo print_select ($group_select, 'id_group', $id_group, '', '', '', true);
		echo '&nbsp;&nbsp;';
		echo '<input type=submit name="update_group" class="sub upd"  value="'.__('Filter').'">';
		echo '<br><br>';

		// Source agent
		echo '<b>'. __('Source agent').'</b>';
		print_help_icon ('duplicateconfig');
		echo '<br><br>';
	
		// Show combo with SOURCE agents
		if ($id_group > 1)
			$sql1 = "SELECT * FROM tagente WHERE id_grupo = $id_group ORDER BY nombre ";
		else
			$sql1 = "SELECT * FROM tagente WHERE id_group IN ($grouplist) ORDER BY nombre";
		echo '<select name="origen" style="width:200px">';			
				$result=mysql_query($sql1);
		while ($row=mysql_fetch_array($result)){
			if (give_acl ($config["id_user"], $row["id_grupo"], "AR")){
				$source = $config["remote_config"]."/". md5($row["nombre"]).".conf";
				if (file_exists($source)){
					echo "<option value=".$row["id_agente"].">".$row["nombre"]."</option>";
				}
			}
		}
		echo '</select>';

		// Destination agent
		echo '<tr><td class="datost">';
		echo '<b>'.__('To agent(s):').'</b><br><br>';
		echo "<select name=destino[] size=10 multiple=yes style='width: 250px;'>";
		if ($id_group > 1)
			$sql1 = "SELECT * FROM tagente WHERE id_grupo = $id_group ORDER BY nombre ";
		else
			$sql1 = "SELECT * FROM tagente WHERE id_group IN ($grouplist) ORDER BY nombre";

		$result=mysql_query($sql1);
		while ($row=mysql_fetch_array($result)){
			if (give_acl ($config["id_user"], $row["id_grupo"], "AW"))
				echo "<option value=".$row["id_agente"].">".$row["nombre"]."</option>";
		}
		echo '</select>';
		
		// Form buttons
		echo '<td align="right" class="datosb">';
		echo '<input type="submit" name="copy" class="sub next" value="'.__('Replicate configuration').'" onClick="if (!confirm("'.__('Are you sure?').'")) return false;>';
		echo '<tr><td colspan=2>';
		echo '</div></td></tr>';
		echo '</table>';
	}

?>
