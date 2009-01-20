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
require_once ("include/config.php");
check_login ();

if (! give_acl ($config['id_user'], 0, "AR")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access agent main list view");
	require ("general/noaccess.php");
	exit;
}

// Take some parameters (GET)
$offset = get_parameter ("offset", 0);
$group_id = get_parameter ("group_id", 0);
$ag_group = get_parameter ("ag_group", $group_id);
$ag_group = get_parameter_get ("ag_group_refresh", $ag_group); //if it isn't set, defaults to prev. value

$search = get_parameter ("search");

echo "<h2>".__('Pandora Agents')." &gt; ".__('Summary')."</h2>";

// Show group selector (POST)
if (isset($_POST["ag_group"])){
	$ag_group = $_POST["ag_group"];
	echo "<form method='post' 
	action='index.php?sec=estado&sec2=operation/agentes/estado_agente
	&refr=60&ag_group_refresh=".$ag_group."'>";
} else {
	echo "<form method='post'
	action='index.php?sec=estado&sec2=operation/agentes/estado_agente
	&refr=60'>";
}

echo "<table cellpadding='4' cellspacing='4' class='databox'><tr>";
echo "<td valign='top'>".__('Group')."</td>";
echo "<td valign='top'>";

$groups = get_user_groups ();
print_select ($groups, 'ag_group', $ag_group, 'this.form.submit()', '', '');

echo "<td valign='top'>
<noscript>
<input name='uptbutton' type='submit' class='sub' 
value='".__('Show')."'>
</noscript>
</td></form><td valign='top'>";

echo __('Free text for search (*)');
echo "</td><td valign='top'>";
echo "<form method='post' action='index.php?sec=estado&sec2=operation/agentes/estado_agente&refr=60'>";
echo "<input type=text name='search' size='15'>";
echo "</td><td valign='top'>";
echo "<input name='srcbutton' type='submit' class='sub' 
value='".__('Search')."'>";
echo "</form>";
echo "</td></table>";


if ($search != ""){
	$search_sql = " AND nombre LIKE '%$search%' OR direccion LIKE '%$search%' ";
} else {
	$search_sql = "";
}

// Show only selected groups	
if ($ag_group > 1){
	$sql="SELECT * FROM tagente WHERE id_grupo=$ag_group
	AND disabled = 0 $search_sql ORDER BY nombre LIMIT $offset, ".$config["block_size"];
	$sql2="SELECT COUNT(id_agente) FROM tagente WHERE id_grupo=$ag_group 
	AND disabled = 0 $search_sql ORDER BY nombre";
// Not selected any specific group
} else {
	// Is admin user ??
	if (is_user_admin ($config["id_user"])) {
		$sql = "SELECT * FROM tagente WHERE disabled = 0 $search_sql ORDER BY nombre, id_grupo LIMIT $offset, ".$config["block_size"];
		$sql2 = "SELECT COUNT(id_agente) FROM tagente WHERE disabled = 0 $search_sql ORDER BY nombre, id_grupo";
	// standard user
	} else {
		// User has explicit permission on group 1 ?
		$sql = sprintf ("SELECT COUNT(id_grupo) FROM tusuario_perfil WHERE id_usuario='%s' AND id_grupo = 1", $config['id_user']);
		$all_group = get_db_sql ($sql);

		if ($all_group > 0) {
			$sql = sprintf ("SELECT * FROM tagente
					WHERE disabled = 0 %s
					ORDER BY nombre, id_grupo LIMIT %d,%d",
					$search_sql, $offset,
					$config["block_size"]);
			$sql2 = sprintf ("SELECT COUNT(id_agente)
					FROM tagente WHERE disabled = 0 %s
					ORDER BY nombre, id_grupo",
					$search_sql);
		} else {
			$sql = sprintf ("SELECT * FROM tagente
					WHERE disabled = 0 %s
					AND id_grupo IN (SELECT id_grupo
						FROM tusuario_perfil
						WHERE id_usuario='%s')
					ORDER BY nombre, id_grupo LIMIT %d,%d",
					$search_sql, $config['id_user'], $offset,
					$config["block_size"]);
			$sql2 = sprintf ("SELECT COUNT(id_agente)
					FROM tagente
					WHERE disabled = 0 %s
					AND id_grupo IN (SELECT id_grupo 
						FROM tusuario_perfil
						WHERE id_usuario='%s')
						ORDER BY nombre, id_grupo",
					$search_sql, $config['id_user']);
		}

	}
}


$result2 = mysql_query ($sql2);
$row2 = mysql_fetch_array ($result2);
$total_events = $row2[0];
// Prepare pagination

pagination ($total_events, 
	"index.php?sec=estado&sec2=operation/agentes/estado_agente&group_id=$ag_group&refr=60",
	$offset);
// Show data.
$agents = get_db_all_rows_sql ($sql);

if ($agents !== false) {
	echo "<table cellpadding='4' cellspacing='4' width='700' class='databox' style='margin-top: 10px'>";
	echo "<th>".__('Agent')."</th>";
	echo "<th>".__('OS')."</th>";
	echo "<th>".__('Interval')."</th>";
	echo "<th>".__('Group')."</th>";
	echo "<th>".__('Modules')."</th>";
	echo "<th>".__('Status')."</th>";
	echo "<th>".__('Alerts')."</th>";
	echo "<th>".__('Last contact')."</th>";
	// For every agent defined in the agent table
	$color = 1;
	foreach ($agents as $agent) {
		$intervalo = $agent["intervalo"]; // Interval in seconds
		$id_agente = $agent['id_agente'];	
		$nombre_agente = substr (strtoupper ($agent["nombre"]), 0, 18);
		$direccion_agente = $agent["direccion"];
		$id_grupo = $agent["id_grupo"];
		$id_os = $agent["id_os"];
		$ultimo_contacto = $agent["ultimo_contacto"];
		$biginterval = $intervalo;
		$pertenece = 0;
		foreach ($groups as $migrupo) { //Verifiy if the group this agent begins is one of the user groups
			if ($migrupo || $id_grupo == $migrupo) {
				$pertenece = 1;
				break;
			}
		}
		if (! $pertenece == 1)
			continue;
	
		// Obtenemos la lista de todos los modulos de cada agente
		$sql = "SELECT * FROM tagente_estado, tagente_modulo 
			WHERE tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo AND 
			tagente_modulo.disabled = 0 
			AND tagente_modulo.id_agente=".$id_agente;
		$modules = get_db_all_rows_sql ($sql);
		if ($modules === false)
			$modules = array ();
		$numero_modulos = 0; 
		$est_timestamp = ""; 
		$monitor_normal = 0; 
		$monitor_warning = 0;
		$monitor_critical = 0; 
		$monitor_down = 0; 
		$agent_down = 0;
		$now = get_system_time ();
		
		// Calculate module/monitor totals  for this agent
		foreach ($modules as $module) {
			$numero_modulos ++;
			$ultimo_contacto_modulo = $module["timestamp"];
			$module_interval = $module["module_interval"];
			$module_type = $module["id_tipo_modulo"];
			
			if ($module_interval > $biginterval)
				$biginterval = $module_interval;
			if ($module_interval != 0)
				$intervalo_comp = $module_interval;
			else
				$intervalo_comp = $intervalo;
			if ($ultimo_contacto != "")
				$seconds = $now - strtotime ($ultimo_contacto_modulo);
			else 
				$seconds = -1;
			if ($module_type < 21 || $module_type == 100) {
				$async = 0;
			} else {
				$async = 1;
			}
			// Defines if Agent is down (interval x 2 > time last contact	
			if ($seconds >= ($intervalo_comp * 2)) { // If (intervalx2) secs. ago we don't get anything, show alert
				$agent_down = 1;
				if ($async == 0)
					$monitor_down++;
			} else {
				if ($module["estado"] == 2) 
					$monitor_warning++;
				elseif ($module["estado"]== 1) 
					$monitor_critical++;
				else 
					$monitor_normal++;
			}
		}
		// Color change for each line (1.2 beta2)
		if ($color == 1){
			$tdcolor = "datos";
			$color = 0;
		}
		else {
			$tdcolor = "datos2";
			$color = 1;
		}
		echo "<tr>";
		echo "<td class='$tdcolor'>";
		if (give_acl ($config['id_user'], $id_grupo, "AW")) {
			echo "<a href='index.php?sec=gagente&amp;
			sec2=godmode/agentes/configurar_agente&amp;
			id_agente=".$id_agente."'>
			<img src='images/setup.png' border=0 width=16></a>&nbsp;";
		}
		echo '<a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$id_agente.'"><strong>';
		echo $nombre_agente;
		echo "</strong></a></td>";

		// Show SO icon
		echo "<td class='$tdcolor' align='center'>";
		print_os_icon ($id_os, false);
		echo "</td>";
		// If there are a module interval bigger than agent interval
		if ($biginterval > $intervalo) {
			echo "<td class='$tdcolor'>
			<span class='green'>".$biginterval."</span></td>";
		} else {
			echo "<td class='$tdcolor'>".$intervalo."</td>";
		}

		// Show GROUP icon
		echo '<td class="'.$tdcolor.'" align="center">';
		echo "<a  href='index.php?sec=estado&sec2=operation/agentes/estado_agente&refr=60&group_id=$id_grupo'>";
		echo print_group_icon ($id_grupo);
		//echo '&nbsp;(<b>';
		//echo get_group_name ($id_grupo);
		//echo "</b>)";
		echo "</a>";


		echo "<td class='$tdcolor'><b>".$numero_modulos." : ";
		if ($monitor_normal >  0)
			echo " <span class='green'>".$monitor_normal."</span>";
		if ($monitor_warning >  0)
			echo " <span class='yellow'>".$monitor_warning."</span>";
		if ($monitor_critical >  0)
			echo " <span class='red'>".$monitor_critical."</span>";
		if ($monitor_down >  0)
			echo " <span class='grey'>".$monitor_down."</span>";
		echo "</td>";
		
		echo "<td class='$tdcolor' align='center'>";
		if ($numero_modulos > 0){
			if ($agent_down > 0) {
				echo '<img src="images/pixel_fucsia.png" width="40" height="18" title="'.__('Agent down').'" />';
			} elseif ($monitor_critical > 0){
					echo '<img src="images/pixel_red.png" width="40" height="18" title="'.__('At least one module in CRITICAL status').'" />';
			} elseif ($monitor_warning > 0) {
					echo '<img src="images/pixel_yellow.png" width="40" height="18" title="'.__('At least one module in WARNING status').'" />';
			} else {
				echo '<img src="images/pixel_green.png" width="40" height="18" title="'.__('All Monitors OK').'" />';
			} 
		} else {
			  echo '<img src="images/pixel_blue.png" width="40" height="18" title="'.__('Agent without data').'" />';
		}
		
		// checks if an alert was fired recently
		echo "<td class='$tdcolor' align='center'>";
		if (give_disabled_group ($id_grupo)) {
			echo "<img src='images/pixel_gray.png' width=20 height=9>";
		} else {
			if (check_alert_fired ($id_agente) == 1) 
				echo '<img src="images/pixel_red.png" width="20" height="9" title="'.__('Alert fired').'" />';
			else
				echo '<img src="images/pixel_green.png" width="20" height="9" title="'.__('Alert not fired').'" />';
		}				
		echo "</td>";
		echo "<td class='$tdcolor'>";
		print_timestamp ($ultimo_contacto);
		echo "</td>";
	}
	echo "<tr>";
	echo "</table><br>";
	require ("bulbs.php");
} else {
	echo '</table><br><div class="nf">'.__('There are no agents included in this group').'</div>';
	if (give_acl ($config['id_user'], 0, "LM")
		|| give_acl ($config['id_user'], 0, "AW")
		|| give_acl ($config['id_user'], 0, "PM")
		|| give_acl ($config['id_user'], 0, "DM")
		|| give_acl ($config['id_user'], 0, "UM")) {
		
		echo "&nbsp;<form method='post' action='index.php?sec=gagente&
			sec2=godmode/agentes/configurar_agente&create_agent=1'><input type='submit' class='sub next' name='crt'
			value='".__('Create agent')."'></form>";
	}
}

?>
