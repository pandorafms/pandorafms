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
global $config;

check_login();

$origen = get_parameter ("origen", -1);
$id_group = get_parameter ("id_group", -1);
$update_agent = get_parameter ("update_agent", -1);
$update_group = get_parameter ("update_group", -1);

if (! check_acl ($config['id_user'], 0, "AW")) {
	db_pandora_audit("ACL Violation",
		"Trying to access remote config copy tool");
	require ("general/noaccess.php");
	exit;
}

require_once ($config['homedir'].'/include/functions_agents.php');
require_once ($config['homedir'].'/include/functions_users.php');

// Operations
if ((isset($_GET["operacion"])) AND ($update_group == -1) ) {

	// DATA COPY
	// ---------
	if (isset($_POST["copy"])) {
		// Header
		ui_print_page_header (__("Data Copy"), "images/god1.png", false, "", true, "");

		// Initial checkings

		// if selected more than 0 agents

		if (isset($_POST["destino"])) {
			$destino = $_POST["destino"];
			if (count($destino) <= 0){
				echo "<h3 class='error'>ERROR: ".__('No selected agents to copy')."</h3>";
				echo "</table>";
				echo '</div>';
			        echo '<div style="clear:both">&nbsp;</div>';
        			echo '</div>';			
			        echo '<div id="foot">';
				require ("general/footer.php");
				echo '</div>';
     				echo '</div>';
				exit;
			}
		}
		else {
			echo "<h3 class='error'>ERROR: ".__('No source agent selected')."</h3>";
                        echo "</table>";
			echo '</div>';
		        echo '<div style="clear:both">&nbsp;</div>';
        		echo '</div>';
		        echo '<div id="foot">';
        		require ("general/footer.php");
        		echo '</div>';
        		echo '</div>';
                        exit;
                }

		// Source
		$id_origen = get_parameter ("origen");

		// Security check here
		if (!users_access_to_agent ($id_origen)) {
			db_pandora_audit("ACL Violation", "Trying to forge a source agent in remote config tool");
			require ("general/noaccess.php");
			exit;
		}		

		// Copy files
		for ($a=0;$a <count($destino); $a++){ 
			// For every agent in destination

			//Security check here
			$id_agente = $destino[$a];
			
			// Security check here
			if (!users_access_to_agent ($id_agente)){
				db_pandora_audit("ACL Violation", "Trying to forge a source agent in remote config tool");
				require ("general/noaccess.php");
				exit;
			}			

			$agent_name_src = agents_get_name($id_origen, "");
			$agent_name_dst = agents_get_name($id_agente, "");
			echo "<br><br>".__('Making copy of configuration file for')." [<b>".$agent_name_src."</b>] ".__('to')." [<b>".$agent_name_dst."</b>]";
			
			$agent_md5_src = md5($agent_name_src);
			$agent_md5_dst = md5($agent_name_dst);
	
			$copy_md5 = copy  ( $config["remote_config"]."/md5/".$agent_md5_src.".md5", $config["remote_config"]."/md5/".$agent_md5_dst.".md5" );
			$copy_conf = copy  ( $config["remote_config"]."/conf/".$agent_md5_src.".conf", $config["remote_config"]."/conf/".$agent_md5_dst.".conf" );			
			
			if (!$copy_md5) {
				ui_print_error_message(__('Error copying md5 file ').$agent_name_src.__(' md5 file'));
			} else {
				ui_print_success_message(__('Copied ').$agent_name_src.__(' md5 file'));
			}
			if (!$copy_conf) {
				ui_print_error_message(__('Error copying ').$agent_name_src.__(' config file'));
			} else {
				ui_print_success_message(__('Copied ').$agent_name_src.__(' config file'));
			}
		} // for each destination agent
	} //end if copy modules or alerts


	// ============	
	// Form view
	// ============
	} else { 
		
		// title
		// Header
		ui_print_page_header (__("Remote configuration management"), "images/god1.png", false, "", true, "");
		echo '<form method="post" action="index.php?sec=gagente&sec2=godmode/agentes/manage_config_remote&operacion=1">';
		echo "<table width='98%' border='0' cellspacing='4' cellpadding='4' class='databox'>";
		
		// Source group
		echo '<tr><td class="datost"><b>'. __('Source group'). '</b><br><br>';

		$group_select = users_get_groups ($config['id_user']);
		$grouplist = implode (',', array_keys ($group_select));
		
		echo html_print_select_groups($config['id_user'], "AR", true, 'id_group', $id_group, '', '', '', true, false, true, '', false, 'width:180px;');
		echo '&nbsp;&nbsp;';
		echo '<input type=submit name="update_group" class="sub upd"  value="'.__('Filter').'">';
		echo '<br><br>';

		// Source agent
		echo '<b>'. __('Source agent').'</b>';
		ui_print_help_icon ('duplicateconfig');
		echo '<br><br>';

		// Show combo with SOURCE agents
		if ($id_group > 0)
			$sql1 = "SELECT * FROM tagente WHERE id_grupo = $id_group ORDER BY nombre ";
		else
			$sql1 = "SELECT * FROM tagente WHERE id_grupo IN ($grouplist) ORDER BY nombre";
		echo '<select name="origen" style="width:200px">';
		
		$rows = db_get_all_rows_sql($sql1);
		
		if ($rows === false) {
			$rows = array();
		}
		
		foreach ($rows as $row) {
			if (check_acl ($config["id_user"], $row["id_grupo"], "AR")){
				$source = $config["remote_config"]."/conf/". md5($row["nombre"]).".conf";
				if (file_exists($source)){
					echo "<option value=".$row["id_agente"].">".$row["nombre"]."</option>";
				}
			}
		}
		echo '</select>';
		echo '</td></tr>';		

		// Destination agent
		echo '<tr><td class="datost">';
		echo '<b>'.__('To agent(s):').'</b><br><br>';
		echo "<select name=destino[] size='10' multiple='multiple' style='width: 350px;'>";
		if ($id_group > 0)
			$sql1 = "SELECT * FROM tagente WHERE id_grupo = $id_group ORDER BY nombre ";
		else
			$sql1 = "SELECT * FROM tagente WHERE id_grupo IN ($grouplist) ORDER BY nombre";

		$rows = db_get_all_rows_sql($sql1);
		
		if ($rows === false) {
			$rows = array();
		}
		
		foreach ($rows as $row) {
			if (check_acl ($config["id_user"], $row["id_grupo"], "AW"))
				echo "<option value=".$row["id_agente"].">".$row["nombre"]."</option>";
		}
		echo '</select>';
		echo '</td>';
		// Form buttons
		echo '<td align="right" class="datosb">';
		echo '<input type="submit" name="copy" class="sub next" value="'.__('Replicate configuration').'" onClick="if (!confirm("'.__('Are you sure?').'")) return false;>';
		echo '</td></tr>';
		echo '</table>';
	}

?>

<script type="text/javascript">
$(document).ready (function () {
		
	$("#id_group").click (
	function () {
		$(this).css ("width", "auto"); 
	});
			
	$("#id_group").blur (function () {
		$(this).css ("width", "180px"); 
	});
		
});
</script>
