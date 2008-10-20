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

if (! give_acl ($config['id_user'], 0, "AR")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access agent main list view");
	require ("general/noaccess.php");
	exit;
}

// Take some parameters (GET)
$offset = get_parameter ("offset", 0);
$group_id = get_parameter ("group_id", 0);
$ag_group = get_parameter ("ag_group", -1);
if (($ag_group == -1) && ($group_id != 0))
	$ag_group = $group_id;
if (isset($_GET["ag_group_refresh"])){
	$ag_group = $_GET["ag_group_refresh"];
}
$search = get_parameter ("search", "");

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
echo "<select name='ag_group' onChange='javascript:this.form.submit();' 
class='w130'>";

if ($ag_group > 1){
	echo "<option value='".$ag_group."'>".dame_nombre_grupo($ag_group).
	"</option>";
}
$mis_grupos = list_group ($config['id_user']); //Print combo for groups and set an array with all groups

echo "</select>";
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
	$sql = sprintf ("SELECT * FROM tusuario WHERE id_usuario ='%s'", $config['id_user']);
	if (get_db_sql ($sql, "nivel") == 1) {
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
$result=mysql_query($sql);
if (mysql_num_rows($result)){
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
	while ($row=mysql_fetch_array($result)){
		$intervalo = $row["intervalo"]; // Interval in seconds
		$id_agente = $row['id_agente'];	
		$nombre_agente = substr(strtoupper($row["nombre"]),0,18);
		$direccion_agente =$row["direccion"];
		$id_grupo=$row["id_grupo"];
		$id_os = $row["id_os"];
		$ultimo_contacto = $row["ultimo_contacto"];
		$biginterval=$intervalo;
		foreach ($mis_grupos as $migrupo){ //Verifiy if the group this agent begins is one of the user groups
			if (($migrupo ==1) || ($id_grupo==$migrupo)){
				$pertenece = 1;
				break;
			}
			else
				$pertenece = 0;
		}
		if ($pertenece == 1) { // Si el agente pertenece a uno de los grupos que el usuario puede visualizar
			// Obtenemos la lista de todos los modulos de cada agente
			$sql_t="SELECT * FROM tagente_estado, tagente_modulo 
			WHERE tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo AND 
			tagente_modulo.disabled = 0 
			AND tagente_modulo.id_agente=".$id_agente;
			$result_t=mysql_query($sql_t);
			$estado_general = 0; 
			$numero_modulos = 0; 
			$numero_monitor = 0; 
			$est_timestamp = ""; 
			$monitor_bad=0; 
			$monitor_ok = 0; 
			$monitor_down=0; 
			$numero_datamodules=0;
			$estado_cambio=0;
			$ahora=date("Y/m/d H:i:s");
			// Calculate module/monitor totals  for this agent
			while ($row_t=mysql_fetch_array($result_t)){
				$est_modulo = $row_t["estado"]; 
				$ultimo_contacto_modulo = $row_t["timestamp"];
				$module_interval = $row_t["module_interval"];
				$module_type = $row_t["id_tipo_modulo"];

				if ($module_interval > $biginterval)
					$biginterval = $module_interval;
				if ($module_interval !=0)
					$intervalo_comp = $module_interval;
				else
					$intervalo_comp = $intervalo;
				if ($ultimo_contacto <> "")
					$seconds = strtotime($ahora) - strtotime($ultimo_contacto_modulo);
				else 
					$seconds = -1;
				if (($module_type < 21) OR ($module_type == 100)){
					$async = 0;
				} else {
					$async = 1;
				}
				# Defines if Agent is down (interval x 2 > time last contact	
				if ($seconds >= ($intervalo_comp*2)){ // If (intervalx2) secs. ago we don't get anything, show alert
					if ($est_modulo != 100)
						$numero_monitor++;
					if ($async == 0)
						$monitor_down++;
				}
				elseif ($est_modulo != 100) { // estado=100 are data modules
					$estado_general = $estado_general + $est_modulo;
					$estado_cambio = $estado_cambio + $row_t["cambio"]; 
					$numero_monitor ++;
					if ($est_modulo != 0)
						$monitor_bad++;			
					else
						$monitor_ok++;
				} elseif ($est_modulo == 100){ // Data modules
					$numero_datamodules++;
				}
				$numero_modulos++;
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
				<img src='images/setup.png' border=0 width=16></a>";
			}
			echo "&nbsp;<a href='index.php?sec=estado&amp;
			sec2=operation/agentes/ver_agente&amp;id_agente=".$id_agente."'>
			<b>".$nombre_agente."</b></a></td>";

			// Show SO icon :)
			echo "<td class='$tdcolor' align='center'>
			<img border=0 src='images/".dame_so_icon($id_os)."' 
			alt='".dame_so_name($id_os)."'></td>";
			// If there are a module interval bigger than agent interval
			if ($biginterval > $intervalo) {
				echo "<td class='$tdcolor'>
				<span class='green'>".$biginterval."</span></td>";
			} else {
				echo "<td class='$tdcolor'>".$intervalo."</td>";
			}

			// Show GROUP icon
			echo '<td class="'.$tdcolor.'" align="center">';

			echo "<a href='index.php?sec=estado&sec2=operation/agentes/estado_agente&refr=60&group_id=$id_grupo'>";
echo '<img class="bot" src="images/groups_small/'.show_icon_group($id_grupo).'.png" title="'. dame_grupo($id_grupo).'"></A></td>';

			echo "<td class='$tdcolor'> ".
			$numero_modulos." <b>/</b> ".$numero_monitor;
			if ($monitor_bad <> 0) {
				echo " <b>/</b> <span class='red'>".$monitor_bad."</span>";
			}
			if ($monitor_down <> 0){
				echo " <b>/</b> <span class='grey'>".$monitor_down."</span>";
			}
			echo "</td>";
			/*
			if ($numero_monitor <> 0){
                                if ($estado_general <> 0){
                                        if ($estado_cambio == 0){
                                                // RED
                                                echo "<td class='$tdcolor' align='center' style='background: #ff1d21'>";
                                        } else {
                                                // Yellow
                                                echo "<td class='$tdcolor' align='center' style='background: #ffe100'>";
                                        }
                                } elseif ($monitor_ok > 0) {
                                        // Green
                                        echo "<td class='$tdcolor' align='center' style='background: #5fff1b'>";
                                }
                                elseif ($numero_datamodules > 0) {
                                        // Grey #1
                                        echo "<td class='$tdcolor' align='center' style='background: #d5d5d5'>";
                                }
                                elseif ($monitor_down > 0) {
                                        // Grey - Red
                                        echo "<td class='$tdcolor' align='center' style='background: #d5b3b3'>";
                                }
                        } else {
                                // Blue
                                echo "<td class='$tdcolor' align='center' style='background: #4485d5'>";
                        }
			*/
			
			echo "<td class='$tdcolor' align='center'>";	
			if ($numero_monitor <> 0){
				if ($estado_general <> 0){
					if ($estado_cambio == 0){
						echo "<img src='images/pixel_red.png' width=40 height=18 title='".__('At least one monitor fails')."'>";
					} else {
						echo "<img src='images/pixel_yellow.png' width=40 height=18 title='".__('Change between Green/Red state')."'>";
					}
				} elseif ($monitor_ok > 0) {
					echo "<img src='images/pixel_green.png' width=40 height=18 title='".__('All Monitors OK')."'>";
				}
				elseif ($numero_datamodules > 0) {
					echo "<img src='images/pixel_blue.png' width=40 height=18 title='".__('Agent without monitors')."'>";
				}
				elseif ($monitor_down > 0) {
					echo "<img src='images/pixel_fucsia.png' width=40 height=18 title='".__('Agent down')."'>"; 
				}
			} else 
				echo "<img src='images/pixel_gray.png' width=40 height=18 title='".__('Agent without data')."'>";
			
			// checks if an alert was fired recently
			echo "<td class='$tdcolor' align='center'>";
			if (give_disabled_group($id_grupo) == 1)
				echo "<img src='images/pixel_gray.png' width=20 height=9>";
			else {
				if (check_alert_fired($id_agente) == 1) 
					echo "<img src='images/pixel_red.png' width=20 height=9 title='".__('Alert fired')."'>";
				else
					echo "<img src='images/pixel_green.png' width=20 height=9 title='".__('Alert not fired')."'>";
			}				
			echo "</td>";
			echo "<td class='$tdcolor'>";
			if ( $ultimo_contacto == "0000-00-00 00:00:00"){
				echo __('Never');
			} else {
				$ultima = strtotime($ultimo_contacto);
				$ahora = strtotime("now");
				$diferencia = $ahora - $ultima;
				if ( $diferencia > ($biginterval*2))
					echo "<font color='#ff0000'>";
		
            echo human_time_comparation($ultimo_contacto);
/*
				if ($biginterval > 0){
					$percentil = round($diferencia/(($biginterval*2) / 100));	
				} else {
					$percentil = -1;
				}
				echo "<a href='#' class='info2'>
				<img src='reporting/fgraph.php?tipo=progress&amp;percent=".
				$percentil."&amp;height=18&amp;width=80' border='0'>
				&nbsp;<span>$ultimo_contacto</span></a>";*/
			}
			
		} // If pertenece/belongs to group
	}
	echo "<tr>";
	echo "</table><br>";
	require "bulbs.php";
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
