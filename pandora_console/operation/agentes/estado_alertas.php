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

check_login ();

if (! give_acl ($config["id_user"], 0, "AR") && ! give_acl ($config["id_user"], 0, "AW")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access alert view");
	include ("general/noaccess.php");
	exit;
}

// Show alerts for specific agent
if (isset($_GET["id_agente"])){
	$id_agente = get_parameter_get ("id_agente");

	$id_grupo_alerta = dame_id_grupo ($id_agente);
	if (give_acl ($config["id_user"], $id_grupo_alerta, "AR") == 0) {
		audit_db ($config["id_user"], $REMOTE_ADDR, "ACL Violation","Trying to access alert view");
		include ("general/noaccess.php");
		exit;
	}

	if (isset ($_GET["tab"])){
		echo "<h2>".__('Pandora Agents')." &gt; ".__('Full list of Alerts')."</h2>";
	}
	
	$query = sprintf ("SELECT talerta_agente_modulo.* FROM talerta_agente_modulo, tagente_modulo WHERE 
			talerta_agente_modulo.id_agente_modulo = tagente_modulo.id_agente_modulo AND 
			tagente_modulo.id_agente = %d",$id_agente);
	$result = get_db_all_rows_sql ($query);
	if ($result !== false) {
	
		if (!isset ($_GET["tab"])) {
			echo "<h3>".__('Full list of Alerts')."</h3>";
		}
	
		echo '<table cellpadding="4" cellspacing="4" width="750" border="0" class="databox">';
		echo "<tr><th>".__('Type')."</th>
			<th>".__('Name')."</th>
			<th>".__('Description')."</th>
			<th>".__('Info')."</th>
			<th>".__('Min.')."</th>
			<th>".__('Max.')."</th>
			<th>".__('Time threshold')."</th>
			<th>".__('Last fired')."</th>
			<th>".__('Times Fired')."</th>
			<th>".__('Status')."</th>
			<th>".__('Validate')."</th></tr>";
		$color = 1;
		foreach ($result as $data) {
			if ($color == 1){
				$tdcolor = "datos";
				$color = 0;
			} else {
				$tdcolor = "datos2";
				$color = 1;
			}
			echo "<tr>";
			show_alert_show_view ($data, $tdcolor, 0);
			echo "</tr>";
		}

	// Show combined alerts for this agent
	$sql = sprintf ("SELECT * FROM talerta_agente_modulo WHERE id_agent = %d",$id_agente);
	$result = get_db_all_rows_sql ($sql);
	
	if ($result !== false) {
		echo '<tr><td colspan="11" class="datos3"><center>'.__('Combined alerts').'</center>';
		foreach ($result as $data) {
			//$color comes from the previous one
			if ($color == 1) {
				$tdcolor = "datos";
				$color = 0;
			} else {
				$tdcolor = "datos2";
				$color = 1;
			}
			echo "<tr>";
			show_alert_show_view ($data, $tdcolor, 1);
			echo "</tr>";
		}
	}
	
	echo '</table>';

	} else {
		echo '<div class="nf">'.__('This agent doesn\'t have any alert').'</div>';
	}

// Show alert for no defined agent 
} else {
	// -------------------------------
	// SHOW ALL ALERTS (GENERAL PAGE)
	// -------------------------------

	echo "<h2>".__('Pandora Agents')." &gt; ".__('Full list of Alerts')."</h2>";
	
	$iduser_temp = $config["id_user"];

	$ag_group = get_parameter ("ag_group", -1);

	if (give_acl ($config["id_user"], $ag_group, "AR") == 0) {
		audit_db ($config["id_user"], $REMOTE_ADDR, "ACL Violation","Trying to access alert view");
		require ("general/noaccess.php");
		exit;
	}

	if ($ag_group != -1) {
		echo '<form method="post" action="index.php?sec=estado&sec2=operation/agentes/estado_alertas&refr=60&ag_group='.$ag_group.'">';
	} else {
		echo '<form method="post" action="index.php?sec=estado&sec2=operation/agentes/estado_alertas&refr=60">';
	}
	
	echo '<table cellpadding="4" cellspacing="4" class="databox">';
	echo '<tr><td>'.__('Group').'</td><td valign="middle">';
	//Select box
	$fields = get_user_groups ($iduser_temp);	
	print_select ($fields, "ag_group", $ag_group, 'javascript:this.form.submit();" class="w150','');
	//And submit button
	echo '</td><td valign="middle"><noscript><input name="uptbutton" type="submit" class="sub" value="'.__('Show').'"></noscript></td>';
	//And finish the table here
	echo '<td class="f9" style="padding-left:30px;"><img src="images/pixel_red.png" width="18" height="18">&nbsp;'.__('Alert fired').'</td>';
	echo '<td class="f9" style="padding-left:30px;"><img src="images/pixel_green.png" width="18" height="18">&nbsp;'.__('Alert not fired').'</td>';
        echo '<td class="f9" style="padding-left:30px; vertical-align:bottom;">(*) '.__('Combined alert').'</tr></table></form>';
	
	// Agent group selector
	if ($ag_group > 1) {
		$result = get_agents_in_group ($ag_group);
	} else {
		//Fields is an array with all the groups the user has access to
		$result = get_agents_in_group (array_keys ($fields));
	}
	
	$color = 1; 
	$string = '';

	if ($result === false) {
		$result = array();
	} else {
		$table->head = array(); //Reset table head
		$table->head[0] = __('Agent');
		$table->head[1] = __('Status');
		$table->head[2] = __('Type');
		$table->head[3] = __('Description');
		$table->head[4] = __('Last fired');
		$table->head[5] = __('Times Fired');
		$table->align = array();
		$table->align[1] = "center";
		$table->cellpadding = 4;
		$table->cellspacing = 4;
		$table->width = 700;
		$table->class = "databox";
		$table->data = array(); //Reset table data
		$idx = 0; //row index
	}
	
	//This result is the array with agents
	foreach ($result as $row) {
		$id_agente = $row["id_agente"];
		$nombre_agente = strtoupper ($row["nombre"]);
		$result_alerts = get_alerts_in_agent ($id_agente);
		
		if ($result_alerts === false)
			$result_alerts = array();
			
		foreach ($result_alerts as $data) {
			$table->data[$idx] = array(); //init array
			$table->data[$idx][0] = '<a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$id_agente.'"><b>'.$nombre_agente.'</b>';
					
			if ($data["times_fired"] != 0) {
				$table->data[$idx][1] = '<img src="images/pixel_red.png" width="40" height="18" title="'.__('Alert fired').'">';
			} else {
				$table->data[$idx][1] = '<img src="images/pixel_green.png" width="40" height="18" title="'.__('Alert not fired').'">';
			}
				
			$table->data[$idx][2] = dame_nombre_alerta ($data["id_alerta"]);
			$table->data[$idx][3] = $data["descripcion"];
		
			if ($data["last_fired"] == "0000-00-00 00:00:00") {
				$table->data[$idx][4] = __('Never');
			} else {
				$table->data[$idx][4] = human_time_comparation ($data["last_fired"]);
			}
			$table->data[$idx][5] = $data["times_fired"];
			$idx++; //increment the index counter
		} //end foreach (data)
	} //end foreach (agent)
	if (!empty ($result) && !empty ($table->data)) {
		print_table ($table);
	} else {
		echo '<div class="nf">'.__('No agent included in this group has any assigned alert').'</div>';
	}
	
	unset ($table); //throw away table
} // Main alert view
?>
