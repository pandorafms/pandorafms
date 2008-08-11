<?php
// Pandora FMS - the Free Monitoring System
// ========================================
// Copyright (c) 2004-2008 Sancho Lerena, slerena@gmail.com
// Copyright (c) 2008 Jorge Gonzalez <jorge.gonzalez@artica.es>
// Main PHP/SQL code development, project architecture and management.
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation, version 2.

// Load global vars
require("include/config.php");
check_login();

if (give_acl($config["id_user"], 0, "AW")!=1) {
    audit_db($config["id_user"], $REMOTE_ADDR, "ACL Violation","Trying to access agent manager");
    require ($config["homedir"]."/general/noaccess.php");
    exit;
};


echo "<h2>".__('agent_conf')." &gt; ".__('modules')."</h2>"; 

// ==========================
// Create module/type combo
// ==========================

echo '<table width="300" cellpadding="4" cellspacing="4" class="databox">';
echo '<form name="modulo" method="post" action="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=module&id_agente='.$id_agente.'">';
echo "<tr><td class='datos'>";
echo '<select name="form_moduletype">';

// Check if there is at least one server of each type available to assign that
// kind of modules. If not, do not show server type in combo
$network_available = get_db_value ("network_server", "tserver", "network_server", "1");
$wmi_available = get_db_value ("wmi_server", "tserver", "wmi_server", "1");
$plugin_available = get_db_value ("plugin_server", "tserver", "plugin_server", "1");
$prediction_available = get_db_value ("prediction_server", "tserver", "prediction_server", "1");

// Development mode to use all servers
if (1 == $develop_bypass) {
    $network_available = 1;
    $wmi_available = 1;
    $plugin_available = 1;
    $prediction_available = 1;
}

echo "<option value='dataserver'>".__('Create a new data server module');
if ($network_available == 1)
    echo "<option value='networkserver'>".__('Create a new network server module');
if ($plugin_available == 1)
    echo "<option value='pluginserver'>".__('Create a new plugin Server module');
if ($wmi_available == 1)
    echo "<option value='wmiserver'>".__('Create a new WMI Server module');
if ($prediction_available == 1)
    echo "<option value='predictionserver'>".__('Create a new prediction Server module');
echo "</select></td>";
echo '<td class="datos">';
echo '<input align="right" name="updbutton" type="submit" class="sub wand" value="'.__('create').'">';
echo "</form>";
echo "</table>";

// ==========================
// MODULE VISUALIZATION TABLE
// ==========================

echo "<h3>".__('assigned_modules')."</h3>";
$sql1='SELECT * FROM tagente_modulo WHERE id_agente = "'.$id_agente.'"
ORDER BY id_module_group, nombre ';
$result=mysql_query($sql1);
if ($row=mysql_num_rows($result)){
    echo '<table width="750" cellpadding="4" cellspacing="4" class="databox">';
    echo '<tr>';
    echo "<th>".__('module_name')."</th>";
    echo '<th>'.__('S').'</th>';
    echo '<th>'.__('type').'</th>';
    echo "<th>".__('interval')."</th>";
    echo "<th>".__('description')."</th>";
    echo "<th>".__('max_min')."</th>";
    echo "<th width=65>".__('action')."</th>";
    $color=1; $last_modulegroup = "0";
    while ($row = mysql_fetch_array($result)){
        if ($color == 1){
            $tdcolor = "datos";
            $color = 0;
        } else {
            $tdcolor = "datos2";
            $color = 1;
        }
        $id_tipo = $row["id_tipo_modulo"];
        $id_module  = $row["id_modulo"];
        $nombre_modulo = $row["nombre"];
        $descripcion = $row["descripcion"];
        $module_max = $row["max"];
        $module_min = $row["min"];
        $module_interval2 = $row["module_interval"];
        $module_group2 = $row["id_module_group"];
        if ($module_group2 != $last_modulegroup ){
            // Render module group names  (fixed code)
            $nombre_grupomodulo = dame_nombre_grupomodulo ($module_group2);
            $last_modulegroup = $module_group2;
            echo "<tr><td class='datos3' align='center' colspan='9'><b>".$nombre_grupomodulo."</b></td></tr>";
        }
	
	if ($row["disabled"] == 0)
        	echo "<tr><td class='".$tdcolor."_id'>".$nombre_modulo."</td>";
	else
		echo "<tr><td class='$tdcolor'><i>$nombre_modulo</i></td>";
        
        // Module type (by server type )
        echo "<td class='".$tdcolor."f9'>";
        if ($id_module > 0) {
            echo show_server_type ($id_module);
            echo '&nbsp;';
        }

        // Module type (by data type)
        echo "<td class='".$tdcolor."f9'>";
        if ($id_tipo > 0) {
            echo "<img src='images/".show_icon_type($id_tipo)."' border=0>";
        }
        echo "</td>";

        // Module interval
        if ($module_interval2!=0){
            echo "<td class='$tdcolor'>".$module_interval2."</td>";
        } else {
            echo "<td class='$tdcolor'> N/A </td>";
        }
        echo "<td class='$tdcolor' title='$descripcion'>".substr($descripcion,0,30)."</td>";
        
        // MAX / MIN values
        echo "<td class='$tdcolor'>";
            if ($module_max == $module_min) {
                $module_max = "N/A";
                $module_min = "N/A";
            }
            echo $module_max." / ".$module_min;
        echo "</td>";

        // Delete module
        echo "<td class='$tdcolor'>";
        echo "<a href='index.php?sec=gagente&tab=module&sec2=godmode/agentes/configurar_agente&id_agente=$id_agente&delete_module=".$row["id_agente_modulo"]."'".' onClick="if (!confirm(\' '.__('are_you_sure').'\')) return false;">';
        echo "<img src='images/cross.png' border=0 title='".__('delete')."'>";
        echo "</b></a>&nbsp;";
	// Update module
        echo "<a href='index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&id_agente=$id_agente&tab=module&update_module=".$row["id_agente_modulo"]."&moduletype=$id_module#modules'>";
        echo "<img src='images/config.png' border=0 title='".__('update')."' onLoad='type_change()'></b></a>";
        
        // Make a data normalization
        if (($id_tipo == 22 ) OR ($id_tipo == 1 ) OR ($id_tipo == 4 ) OR ($id_tipo == 7 ) OR
        ($id_tipo == 8 ) OR ($id_tipo == 11 ) OR ($id_tipo == 16) OR ($id_tipo == 22 )) {
            echo "&nbsp;";
            echo "<a href='index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&id_agente=$id_agente&tab=module&fix_module=".$row["id_agente_modulo"]."'".' onClick="if (!confirm(\' '.__('are_you_sure').'\')) return false;">';
            echo "<img src='images/chart_curve.png' border=0 title='Normalize'></b></a>";
        }
    }
    echo "</table>";
	
} else
    echo "<div class='nf'>".__('No available data to show')."</div>";
