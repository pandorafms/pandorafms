<?php

// Pandora FMS - the Free Monitoring System
// ========================================
// Copyright (c) 2008 Artica Soluciones Tecnológicas, http://www.artica.es
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

echo "<h2>".$lang_label["agent_conf"]." &gt; ".$lang_label["alerts"]."</h2>";
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

// ==========================
// Simple alerts view
// ==========================

$sql1='SELECT * FROM tagente_modulo WHERE id_agente = "'.$id_agente.'"';
$result=mysql_query($sql1);
	if ($row=mysql_num_rows($result)){

		echo "<h3>".lang_string ("Simple alerts")."</h3>";

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
				
				$string .= show_alert_row_edit ($row3, $tdcolor, $row["id_tipo_modulo"],0);
				$string = $string."</td><td class='$tdcolor'>";
			 	$id_grupo = dame_id_grupo($id_agente);
				if (give_acl($id_user, $id_grupo, "LW")==1){
					$string = $string."<a href='index.php?sec=gagente&
					sec2=godmode/agentes/configurar_agente&tab=alert&
					id_agente=".$id_agente."&delete_alert=".$row3["id_aam"]."'>
					<img src='images/cross.png' border=0 alt='".$lang_label["delete"]."'></a>  &nbsp; ";
					$string = $string."<a href='index.php?sec=gagente&
					sec2=godmode/agentes/configurar_agente&tab=alert&
					id_agente=".$id_agente."&update_alert=".$row3["id_aam"]."'>
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
        <th>".lang_string ("info")."</th>
		<th width='50'>".$lang_label["action"]."</th></tr>";
		echo $string;
		echo "</table>";
		} else {
			echo "<div class='nf'>".$lang_label["no_alerts"]."</div>";
		}
	} else {
		echo "<div class='nf'>".$lang_label["no_modules"]."</div>";
	}

// ==========================
// Combined alerts view
// ==========================

echo "<h3>".lang_string("combined alerts")."</h3>";

$sql1='SELECT * FROM talerta_agente_modulo WHERE id_agent = '.$id_agente;
$result=mysql_query($sql1);
    if ($row=mysql_num_rows($result)){
        $color = 1;
        $string = '';
        while ($row=mysql_fetch_array($result)){  // All modules of this agent
            // Show data for this combined alert
            $string = "<tr><td class='datos3'>";
            $string .= lang_string("Combined")." #".$row["id_aam"];
            $string .= show_alert_row_edit ($row, "datos3", 0, 1);
            $string .= '<td class="datos3">'; // action
            if (give_acl($id_user, $id_grupo, "LW")==1){
                $string .= "<a href='index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=alert&id_agente=".$id_agente."&delete_alert=".$row["id_aam"]."'> <img src='images/cross.png' border=0 alt='".$lang_label["delete"]."'></a>  &nbsp; ";
                $string .= "<a href='index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=alert&id_agente=".$id_agente."&form_alerttype=combined&update_alert=".$row["id_aam"]."'>
                <img src='images/config.png' border=0 alt='".$lang_label["update"]."'></a>";
            }
            $id_aam = $row["id_aam"];
            $sql2 = "SELECT * FROM tcompound_alert, talerta_agente_modulo WHERE tcompound_alert.id = $id_aam AND talerta_agente_modulo.id_aam = tcompound_alert.id_aam";
            $result2=mysql_query($sql2);
            while ($row2=mysql_fetch_array($result2)){  
                // Show data for each component of this combined alert
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
                $string = $string."<tr style='color: #666;'><td class='$tdcolor'><a href='index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=alert&id_agente=".$module["id_agente"]."'><b>".$id_agente_name." </b>- ".substr($module["nombre"],0,15)."</A>";
                
                $string .= show_alert_row_edit ($row2, $tdcolor, $module["id_tipo_modulo"],1);

                $string = $string."</td><td class='$tdcolor'>";
                $id_grupo = dame_id_grupo($id_agente);
                if (give_acl($id_user, $id_grupo, "LW")==1){
                    $string = $string."<a href='index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=alert&id_agente=".$id_agente."&delete_alert_comp=".$row2["id_aam"]."'> <img src='images/cross.png' border=0 alt='".$lang_label["delete"]."'></a>  &nbsp; ";
                    $string = $string."<a href='index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=alert&id_agente=".$id_agente."&update_alert=".$row2["id_aam"]."'>
                    <img src='images/config.png' border=0 alt='".$lang_label["update"]."'></a>";        
                }
                $string = $string."</td>";
            }
        }
        if (isset($string) & $string!='') {
        echo "<table cellpadding='4' cellspacing='4' width='750' class='databox'>
        <tr><th>".$lang_label["name"]."</th>
        <th>".$lang_label["type"]."</th>
        <th>".lang_string ("Oper")."</th>
        <th>".$lang_label["threshold"]."</th>
        <th>".$lang_label["min."]."</th>
        <th>".$lang_label["max."]."</th>
        <th>".$lang_label["time"]."</th>
        <th>".$lang_label["description"]."</th>
        <th>".lang_string ("info")."</th>
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

