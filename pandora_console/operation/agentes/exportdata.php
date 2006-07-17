<?php

// Pandora - the Free monitoring system
// ====================================
// Copyright (c) 2004-2006 Sancho Lerena, slerena@gmail.com
// Copyright (c) 2005-2006 Artica Soluciones Tecnologicas S.L, info@artica.es
// Copyright (c) 2004-2006 Raul Mateos Martin, raulofpandora@gmail.com
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

// Load global vars
require("include/config.php");

if (comprueba_login() == 0)
	$id_user = $_SESSION["id_usuario"];
	if ( (give_acl($id_user, 0, "AR")==1) OR (give_acl($id_user, 0, "AW")==1) ){
		if ((isset($_GET["operacion"])) AND (! isset($_POST["update_agent"]))){
 			if (isset ($_POST["copiar"])){ // Export main button
				echo "<h2>".$lang_label["ag_title"]."</h2>";
				echo "<h3>".$lang_label["export_title"]."<a href='help/".$help_code."/chap3.php#336' target='_help' class='help'>&nbsp;<span>".$lang_label["help"]."</span></a></h3>";
				if (isset ($_POST["origen_modulo"])){
				if (isset ($_POST["csv"])) // CSV version
					$csv = 1;
				else
					$csv = 0;
				$origen = $_POST["origen"];
				if (give_acl($id_user,dame_id_grupo($origen),"AR")!=1) {
					audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access Agent Export Data");
					require ("general/noaccess.php");
				}
				$origen_modulo = $_POST["origen_modulo"];
				$from_date =$_POST["from_date"];
				$to_date=$_POST["to_date"];
				echo $lang_label["db_agent_bra"]."<b>".dame_nombre_agente($origen)."</b>".$lang_label["from2"]."<b>".$from_date."</b>".$lang_label["to2"]."<b>".$to_date."</b><br>";
				if ($csv == 0){
					echo "<br><table cellpadding='3' cellspacing='3' width='600'><tr><th class='datos'>".$lang_label["module"]."</th><th class=datos>".$lang_label["data"]."<th class='datos'>Timestamp</th>";
					$separador_1 = "<tr><td class='datos_id'>"; 
					$separador_2 = "<td class='datos'>";
					$separador_3= "";
				} else {
					$separador_1 = "<br>'";
					$separador_2 = "','";
					$separador_3 = "'";
				}

			 	for ($a=0;$a <count($origen_modulo); $a++){ // For each module
					$id_modulo = $origen_modulo[$a];
					$sql1='SELECT * FROM tdatos WHERE id_agente = '.$origen;
					$tipo = dame_nombre_tipo_modulo(dame_id_tipo_modulo_agentemodulo($id_modulo));
					if ($tipo == "generic_data_string")
						$sql1='SELECT * FROM tagente_datos_string WHERE timestamp > "'.$from_date.'" AND timestamp < "'.$to_date.'" AND id_agente_modulo ='.$id_modulo.' ORDER BY timestamp DESC';
					else
						$sql1='SELECT * FROM tagente_datos WHERE timestamp > "'.$from_date.'" AND timestamp < "'.$to_date.'" AND id_agente_modulo ='.$id_modulo.' ORDER BY timestamp DESC';
						$result1=mysql_query($sql1);
						while ($row=mysql_fetch_array($result1)){
							echo $separador_1;
							echo dame_nombre_modulo_agentemodulo($id_modulo);
							echo $separador_2;
							echo $row["datos"];
							echo $separador_2;
							echo $row["timestamp"];
							echo $separador_3;
						}
				}
				if ($csv == 0) {
					echo "<tr><td colspan='3'><div class='raya'></div></td></tr>";
				}
				echo "</table>";
				
				} else echo "<b class='error'>".$lang_label["no_sel_mod"]."</b>";
			}
		} else { // Form view
			?>
			<h2><?php echo $lang_label["ag_title"] ?></h2>
			<h3><?php echo $lang_label["export_data"] ?><a href='help/<?php echo $help_code; ?>/chap3.php#336' target='_help' class='help'>&nbsp;<span><?php echo $lang_label["help"] ?></span></a></h3>
			<form method="post" action="index.php?sec=estado&sec2=operation/agentes/exportdata&operacion=1" name="export_form">
			<table width=550 border=0 cellspacing=3 cellpadding=5 class=fon>
			<tr>
			<td class='datost'><b><?php echo $lang_label["source_agent"] ?> </b><br><br>

			<select name="origen" class="w130">
			<?php
			if ( (isset($_POST["update_agent"])) AND (isset($_POST["origen"])) ) {
				echo "<option value=".$_POST["origen"].">".dame_nombre_agente($_POST["origen"]);
			}
			// Show combo with agents
			$sql1='SELECT * FROM tagente';
			$result=mysql_query($sql1);
			while ($row=mysql_fetch_array($result)){
				if ( (isset($_POST["update_agent"])) AND (isset($_POST["origen"])) ){
					if (give_acl($id_user, $row["id_grupo"], "AR")==1)
						if ( $_POST["origen"] != $row["id_agente"])
							echo "<option value=".$row["id_agente"].">".$row["nombre"];
				}
				else
					if (give_acl($id_user, $row["id_grupo"], "AR")==1)
						echo "<option value=".$row["id_agente"].">".$row["nombre"];
			}
			echo "</select>&nbsp;&nbsp;<input type=submit name='update_agent' class=sub value='".$lang_label["get_info"]."'><br><br>";

			echo "<b>".$lang_label["modules"]."</b><br><br>";
			echo "<select name='origen_modulo[]' size=8 multiple=yes class='w130'>";
			if ( (isset($_POST["update_agent"])) AND (isset($_POST["origen"])) ) {
				// Populate Module/Agent combo
				$agente_modulo = $_POST["origen"];
				$sql1="SELECT * FROM tagente_modulo WHERE id_agente = ".$agente_modulo;
				$result = mysql_query($sql1);
				while ($row=mysql_fetch_array($result)){
					if (give_acl($id_user, $row["id_grupo"], "AR")==1) 
						echo "<option value=".$row["id_agente_modulo"].">".$row["nombre"];	
				}
			}
			?>
			<td class='datos2t'><b><?php echo $lang_label["date_range"]; ?></b><br><br>
			<table cellspacing=3 cellpadding=3 border=0>
			<?php 
			$mh = "10080"; // a week, una semana
			$m_year = date("Y", time()-$mh*60);
			$m_month = date("m", time()-$mh*60);
			$m_day = date("d", time()-$mh*60);
			$m_hour = date("H", time()-$mh*60);
			$m_min = date("i", time()-$mh*60);
			$m = $m_year."-".$m_month."-".$m_day." ".$m_hour.":".$m_min.":"."00"; //one week ago
			$ahora = date("Y-m-d H:i:s"); //Now
			
			require ("include/calendar.php"); //Including calendar
			$result=mysql_query("SELECT token, value FROM tconfig");
			$row=mysql_fetch_array($result);
			if ($row["token"]=="language_code") $locale=$row["value"]; //language of calendar.
			
			$time = time();
			
			if (isset($_GET["month"]))	$month = $_GET["month"];
			else $month = date('n', $time);
			if (isset($_GET["year"]))	$year = $_GET["year"];
			else $year = date('Y', $time);
			
			//preparate months (the next month to december is january and back)
			$first_of_month = gmmktime(0,0,0,$month,1,$year);
			list($month, $year) = explode(',',gmstrftime('%m,%Y',$first_of_month));
			$month_a = ($month-1);
			$month_d = ($month+1);
			if ($month_d==13) {$year_d = $year; $year_a = $year;}
			else {
				if ($month==12) $year_d = $year+1;
				else $year_d = $year;
				if ($month==0) $year_a = $year-1;
				else $year_a = $year;
			}	

			if (isset($_GET["date_from"])) $date_from=$_GET["date_from"];
				else
				{if (isset($_POST["from_date"])) $date_from=$_POST["from_date"];
				else $date_from=$m;
				}

			if (isset($_GET["date_to"])) $date_to=$_GET["date_to"];
				else 
				{if (isset($_POST["to_date"])) $date_to=$_POST["to_date"];
				else $date_to=$ahora;
				}

			$days_f = array();
			$days_t = array();
			$days_in_month=gmdate('t',$first_of_month);
			//create links for days in every calendar:
			for ($day_f=1; $day_f<=$days_in_month; $day_f++){
				$days_f[$day_f]=array('index.php?sec=estado&sec2=operation/agentes/exportdata&date_from='.$year.'-'.$month.'-'.date('d',mktime(0, 0, 0, $month, $day_f, $year)).' 00:00:00&date_to='.$date_to.'&year='.$year.'&month='.$month);
			}
			for ($day_t=1; $day_t<=$days_in_month; $day_t++){
				$days_t[$day_t]=array('index.php?sec=estado&sec2=operation/agentes/exportdata&date_from='.$date_from.'&date_to='.$year.'-'.$month.'-'.date('d',mktime(0, 0, 0, $month, $day_t, $year)).' 00:00:00'.'&year='.$year.'&month='.$month);
			}
			if (isset($date_from) && isset($date_to))
			{$date="&date_from=".$date_from."&date_to=".$date_to;}
			?>
			
			<tr><td class='datost'><?php echo $lang_label["from"] ?>
&nbsp;&nbsp;<input type="text" class="sub" name="from_date" value="<?php echo $date_from; ?>" size="21"><br><br>
			<?php 
			//print calendar and links for next and previous months
			echo "
			<table><tr>
				<td class='datost'><a href='index.php?sec=estado&sec2=operation/agentes/exportdata&month=".$month_a."&year=".$year_a.$date."'><font size =+1>&laquo;</font></a></td>
				<td>".generate_calendar($year, $month, $days_f, 3, NULL, $locale)."</td><td class='datost'><a href='index.php?sec=estado&sec2=operation/agentes/exportdata&month=".$month_d."&year=".$year_d.$date."'><font size =+1>&raquo;</font></a></td>
			</tr></table>
			"; ?>
			<tr><td class='datost'><br><?php echo $lang_label["to"] ?>&nbsp;&nbsp;&nbsp;&nbsp;<input type="text" class="sub" name="to_date" value="<?php echo $date_to; ?>" size="21"><br><br>
			<?php
			//print calendar and links for next and previous months
			echo "
			<table><tr>
			<td class='datost'><a href='index.php?sec=estado&sec2=operation/agentes/exportdata&month=".$month_a."&year=".$year_a.$date."'><font size =+1>&laquo;</font></a></td>
			<td>".generate_calendar($year, $month, $days_t, 3, NULL, $locale)."</td><td class='datost'><a href='index.php?sec=estado&sec2=operation/agentes/exportdata&month=".$month_d."&year=".$year_d.$date."'><font size =+1>&raquo;</font></a></td>
			</tr></table>
			"; ?>
		 	<tr class=datos><td><?php echo $lang_label["csv"] ?><td><input class="chk" type="checkbox" name="csv" value=0><br>
			
		 	<tr><td></td></tr>
			<tr><td colspan=2 class="datos2b" align="right">
			<input type=submit name="copiar" class=sub value="<?php echo $lang_label["export"] ?>">
			</table></td></tr>
			</table>

<?php
		}
	} else {
		audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access Agent Export Data");
		require ("general/noaccess.php");
	}
?>