<?php

// Pandora FMS - the Free monitoring system
// ========================================
// Copyright (c) 2004-2007 Sancho Lerena, slerena@gmail.com
// Main PHP/SQL code development and project architecture and management
// Copyright (c) 2004-2007 Raul Mateos Martin, raulofpandora@gmail.com
// CSS and some PHP additions
// Copyright (c) 2006-2007 Jonathan Barajas, jonathan.barajas[AT]gmail[DOT]com
// Javascript Active Console code.
// Copyright (c) 2006 Jose Navarro <contacto@indiseg.net>
// Additions to Pandora FMS 1.2 graph code and new XML reporting template management
// Copyright (c) 2005-2007 Artica Soluciones Tecnologicas, info@artica.es
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

// ====================================================================================
// VIEW ALERTS
// ====================================================================================


// Load global vars
require("include/config.php");
check_login();

if (give_acl($config["id_user"], 0, "AW")!=1) {
    audit_db($config["id_user"], $REMOTE_ADDR, "ACL Violation","Trying to access agent manager");
    require ($config["homedir"]."/general/noaccess.php");
    exit;
};


echo "<h2>".$lang_label["agent_conf"]." &gt; ".$lang_label["modules"]."</h2>"; 

// ==========================
// Create module/type combo
// ==========================

echo '<table width="300" cellpadding="4" cellspacing="4" class="databox">';
echo '<form name="modulo" method="post" action="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=alert&id_agente='.$id_agente.'">';
echo "<tr><td class='datos'>";
echo '<select name="form_alerttype">';

echo "<option value='simple'>".lang_string("Create a simple alert");
echo "<option value='combined'>".lang_string("Create a new combined alert");
echo "</select></td>";
echo '<td class="datos">';
echo '<input align="right" name="updbutton" type="submit" class="sub wand" value="'.$lang_label["create"].'">';
echo "</form>";
echo "</table>";


echo "<h2>".$lang_label["agent_conf"]." &gt; ".$lang_label["alerts"]."</h2>";

$sql1='SELECT * FROM tagente_modulo WHERE id_agente = "'.$id_agente.'"';
$result=mysql_query($sql1);
	if ($row=mysql_num_rows($result)){

		echo "<h3>".$lang_label["assigned_alerts"]."</h3>";

		$color=1;
		$string='';
		while ($row=mysql_fetch_array($result)){  // All modules of this agent
			$id_tipo = $row["id_tipo_modulo"];
			$nombre_modulo = substr($row["nombre"],0,21);
			//module type modulo is $row2["nombre"];
			
			$sql3='SELECT * 
				FROM talerta_agente_modulo 
				WHERE id_agente_modulo = '.$row["id_agente_modulo"];
				// From all the alerts give me which are from my agent
			$result3=mysql_query($sql3);
			while ($row3=mysql_fetch_array($result3)){
				if ($color == 1){
					$tdcolor="datos";
					$color =0;
				} else {
					$tdcolor="datos2";
					$color =1;
				}
				$sql4='SELECT nombre FROM talerta WHERE id_alerta = '.$row3["id_alerta"];
				$result4=mysql_query($sql4);
				$row4=mysql_fetch_array($result4);
				// Alert name defined by  $row4["nombre"]; 
				$nombre_alerta = $row4["nombre"];
				$string = $string."<tr style='color: #666;'><td class='$tdcolor'>".$nombre_modulo;
				
				if ($row3["disable"] == 1){
					$string .= "<td class='$tdcolor'><b><i>".$lang_label["disabled"]."</b></i>";
				} else {
					if ($id_tipo > 0) {
						$string .= "<td class='$tdcolor'><img src='images/".show_icon_type($id_tipo)."' border=0>";
					} else 
						$string .= "<td class='$tdcolor'><img src='images/".show_icon_type(2)."' border=0>";
				}
				$string = $string."<td class=$tdcolor>".$nombre_alerta;
				
				$string = $string."<td class='$tdcolor'>".human_time_description($row3["time_threshold"]);
		
				$mytempdata = fmod($row3["dis_min"], 1);
				if ($mytempdata == 0)
					$mymin = intval($row3["dis_min"]);
				else
					$mymin = $row3["dis_min"];
				$mymin = format_for_graph($mymin );

				$mytempdata = fmod($row3["dis_max"], 1);
				if ($mytempdata == 0)
					$mymax = intval($row3["dis_max"]);
				else
					$mymax = $row3["dis_max"];
				$mymax =  format_for_graph($mymax );
				
				// We have alert text ?
				if ($row3["alert_text"] != "")
					$string = $string."<td colspan=2 class='$tdcolor'>".$lang_label["text"]."</td>";
				else {
					$string = $string."<td class='$tdcolor'>".$mymin."</td>";
					$string = $string."<td class='$tdcolor'>".$mymax."</td>";
				}
				$time_from_table =$row3["time_from"];
				$time_to_table =$row3["time_to"];
				$string = $string."<td class='$tdcolor'>";
				if ($time_to_table == $time_from_table)
					$string .= $lang_label["N/A"];
				else
					$string .= substr($time_from_table,0,5)." - ".substr($time_to_table,0,5);
				
				$string = $string."</td><td class='$tdcolor'>".salida_limpia($row3["descripcion"]);
				$string = $string."</td><td class='$tdcolor'>";
			 	$id_grupo = dame_id_grupo($id_agente);
				if (give_acl($id_user, $id_grupo, "LW")==1){
					$string = $string."<a href='index.php?sec=gagente&
					sec2=godmode/agentes/configurar_agente&tab=alert&
					id_agente=".$id_agente."&delete_alert=".$row3["id_aam"]."'>
					<img src='images/cross.png' border=0 alt='".$lang_label["delete"]."'></a>  &nbsp; ";
					$string = $string."<a href='index.php?sec=gagente&
					sec2=godmode/agentes/configurar_agente&tab=alert&
					id_agente=".$id_agente."&update_alert=".$row3["id_aam"]."#alerts'>
					<img src='images/config.png' border=0 alt='".$lang_label["update"]."'></a>";		
				}
				$string = $string."</td>";
			}
		}
		if (isset($string) & $string!='') {
		echo "<table cellpadding='4' cellspacing='4' width='750' class='databox'>
		<tr><th>".$lang_label["name"]."</th>
		<th>".$lang_label["type"]."</th>
		<th>".$lang_label["alert"]."</th>
		<th>".$lang_label["threshold"]."</th>
		<th>".$lang_label["min."]."</th>
		<th>".$lang_label["max."]."</th>
		<th>".$lang_label["time"]."</th>
		<th>".$lang_label["description"]."</th>
		<th width='50'>".$lang_label["action"]."</th></tr>";
		echo $string;
		echo "</table>";
		} else {
			echo "<div class='nf'>".$lang_label["no_alerts"]."</div>";
		}
	} else {
		echo "<div class='nf'>".$lang_label["no_modules"]."</div>";
	}

// Combined alerts

echo "<h3>".lang_string("combined alerts")."</h3>";

$sql1='SELECT * FROM talerta_agente_modulo WHERE id_agent = '.$id_agente;
$result=mysql_query($sql1);
    if ($row=mysql_num_rows($result)){
        $color=1;
        $string='';
        while ($row=mysql_fetch_array($result)){  // All modules of this agent
            $id_aam = $row["id_aam"];
            

            $sql2 = "SELECT * FROM tcompound_alert, talerta_agente_modulo WHERE tcompound_alert.id = $id_aam AND talerta_agente_modulo.id_aam = tcompound_alert.id_aam";
            $result2=mysql_query($sql2);
            while ($row2=mysql_fetch_array($result2)){  // All modules of this agent
                if ($color == 1){
                    $tdcolor="datos";
                    $color =0;
                } else {
                    $tdcolor="datos2";
                    $color =1;
                }
                $module = get_db_row ("tagente_modulo", "id_agente_modulo", $row2["id_agente_modulo"]);

                $description = $row2["descripcion"];
                $alert_mode = $row2["operation"];
                $id_agente_name = get_db_value ("nombre", "tagente", "id_agente", $module["id_agente"]);
                $string = $string."<tr style='color: #666;'><td class='$tdcolor'>".$module["nombre"]."/".$id_agente_name;
                
                if ($row2["disable"] == 1){
                    $string .= "<td class='$tdcolor'><b><i>".$lang_label["disabled"]."</b></i>";
                } else {
                    $string .= "<td class='$tdcolor'><img src='images/".show_icon_type($module["id_tipo_modulo"])."' border=0>";
                }
                $string = $string."<td class=$tdcolor>".$row2["operation"];
                
                $string = $string."<td class='$tdcolor'>".human_time_description($module["time_threshold"]);
        
                $mytempdata = fmod($module["dis_min"], 1);
                if ($mytempdata == 0)
                    $mymin = intval($module["dis_min"]);
                else
                    $mymin = $module["dis_min"];
                $mymin = format_for_graph($mymin );

                $mytempdata = fmod($module["dis_max"], 1);
                if ($mytempdata == 0)
                    $mymax = intval($module["dis_max"]);
                else
                    $mymax = $module["dis_max"];
                $mymax =  format_for_graph($mymax );
                
                // We have alert text ?
                if ($module["alert_text"] != "")
                    $string = $string."<td colspan=2 class='$tdcolor'>".$lang_label["text"]."</td>";
                else {
                    $string = $string."<td class='$tdcolor'>".$mymin."</td>";
                    $string = $string."<td class='$tdcolor'>".$mymax."</td>";
                }
                $time_from_table =$$module["time_from"];
                $time_to_table =$module["time_to"];
                $string = $string."<td class='$tdcolor'>";
                if ($time_to_table == $time_from_table)
                    $string .= $lang_label["N/A"];
                else
                    $string .= substr($time_from_table,0,5)." - ".substr($time_to_table,0,5);
                
                $string = $string."</td><td class='$tdcolor'>".salida_limpia ($module["descripcion"]);
                $string = $string."</td><td class='$tdcolor'>";
                $id_grupo = dame_id_grupo($id_agente);
                if (give_acl($id_user, $id_grupo, "LW")==1){
                    $string = $string."<a href='index.php?sec=gagente&
                    sec2=godmode/agentes/configurar_agente&tab=alert&
                    id_agente=".$id_agente."&delete_alert=".$row3["id_aam"]."'>
                    <img src='images/cross.png' border=0 alt='".$lang_label["delete"]."'></a>  &nbsp; ";
                    $string = $string."<a href='index.php?sec=gagente&
                    sec2=godmode/agentes/configurar_agente&tab=alert&
                    id_agente=".$id_agente."&update_alert=".$row3["id_aam"]."#alerts'>
                    <img src='images/config.png' border=0 alt='".$lang_label["update"]."'></a>";        
                }
                $string = $string."</td>";
            }
        }
        if (isset($string) & $string!='') {
        echo "<table cellpadding='4' cellspacing='4' width='750' class='databox'>
        <tr><th>".$lang_label["name"]."</th>
        <th>".$lang_label["type"]."</th>
        <th>".$lang_label["alert"]."</th>
        <th>".$lang_label["threshold"]."</th>
        <th>".$lang_label["min."]."</th>
        <th>".$lang_label["max."]."</th>
        <th>".$lang_label["time"]."</th>
        <th>".$lang_label["description"]."</th>
        <th width='50'>".$lang_label["action"]."</th></tr>";
        echo $string;
        echo "</table>";
        } else {
            echo "<div class='nf'>".$lang_label["no_alerts"]."</div>";
        }
    } else {
        echo "<div class='nf'>".$lang_label["no_modules"]."</div>";
    }

?>

