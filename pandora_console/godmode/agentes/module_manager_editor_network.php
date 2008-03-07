<?PHP
// Pandora FMS - the Free Monitoring System
// ========================================
// Copyright (c) 2004-2008 Sancho Lerena, slerena@gmail.com
// Main PHP/SQL code development, project architecture and management.
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation, version 2.


// General startup for established session
global $config;
check_login();

// Specific ACL check
if (give_acl($config["id_user"], 0, "AW")!=1) {
    audit_db($config["id_user"], $REMOTE_ADDR, "ACL Violation","Trying to access agent manager");
    require ($config["homedir"]."/general/noaccess.php");
    exit;
}

echo "<h3>".lang_string ("module_assigment")." - ".lang_string("network server module")."</h3>";
echo '<table width="680" cellpadding="4" cellspacing="4" class="databox_color">';
// Create from Network Component
echo '<form name="modulo" method="post" action="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=module&id_agente='.$id_agente.'">';
echo '<input type="hidden" name="insert_module" value=1>';
// id_modulo 2 - Network 
echo "<input type='hidden' name='form_id_modulo' value='2'>";

// Network component usage
echo "<tr><td class='datos3'>";
echo lang_string ("using_network_component");
echo "</td><td class='datos3' colspan=2>";
echo '<select name="form_network_component">';
$sql1='SELECT * FROM tnetwork_component ORDER BY name';
$result=mysql_query($sql1);
while ($row=mysql_fetch_array($result)){
    echo "<option value='".$row["id_nc"]."'>";
    echo substr($row["name"],0,30);
    echo " / ";
    echo substr($row["description"],0,15);
    echo "</option>";
}
echo "</select></td>";
echo '<td class="datos3">';
echo '<input align="right" name="updbutton" type="submit" class="sub next" value="'.$lang_label["get_data"].'">';

// Name / IP_target
echo '<tr>';
echo '<td class="datos2">'.lang_string ("module_name")."</td>";
echo '<td class="datos2"><input type="text" name="form_name" size="20" value="'.$form_name.'"></td>';
echo '<td class="datos2">'.lang_string ("disabled")."</td>";
echo '<td class="datos2"><input type="checkbox" name="form_disabled" value=1></td>';
echo "</tr>";

// Ip target, tcp port
echo "<tr>";
echo '<td class="datos">'.lang_string ("ip_target")."</td>";
echo '<td class="datos"><input type="text" name="form_ip_target" size="25" value="'.$form_ip_target.'"></td>';
echo '<td class="datos">'.lang_string ("tcp_port")."</td>";
echo '<td class="datos"><input type="text" name="form_tcp_port" size="5" value="'.$form_tcp_port.'"></td>';
echo '</tr>';

// module type / max timeout
echo '</tr><tr>';
echo '<td class="datos2">'.lang_string ("module_type")."</td>";
echo '<td class="datos2">';
echo '<select name="form_id_tipo_modulo">';
$sql1='SELECT id_tipo, nombre FROM ttipo_modulo WHERE categoria IN (3,4,5) ORDER BY nombre;';
$result=mysql_query($sql1);
while ($row=mysql_fetch_array($result)){
    echo "<option value='".$row["id_tipo"]."'>".$row["nombre"]."</option>";
}
echo "</select>";
echo '<td class="datos2">'.lang_string ("max_timeout")."</td>";
echo '<td class="datos2"><input type="text" name="form_max_timeout" size="4" value="'.$form_max_timeout.'"></td></tr>';

// Interval & id_module_group
echo '<tr>';
echo '<td class="datos">'.lang_string ("interval")."</td>";
echo '<td class="datos"><input type="text" name="form_interval" size="5" value="'.$form_interval.'"></td>';
echo '<td class="datos">'.lang_string ("module_group")."</td>";
echo '<td class="datos">';
echo '<select name="form_id_module_group">';
if ($form_id_module_group != 0){
    echo "<option value='".$form_id_module_group."'>".dame_nombre_grupomodulo($form_id_module_group)."</option>";
}
$sql1='SELECT * FROM tmodule_group';
$result=mysql_query($sql1);
while ($row=mysql_fetch_array($result)){
    echo "<option value='".$row["id_mg"]."'>".$row["name"]."</option>";
}
echo '</select>';

// Snmp walk
echo '<tr>';
echo '<td class="datos2">'.lang_string ("snmp_walk")."</td>";
echo '<td class="datos2" colspan=2>';
echo '<select name="form_combo_snmp_oid">';
// FILL OID Combobox
if (isset($_POST["oid"])){
    for (reset($snmpwalk); $i = key($snmpwalk); next($snmpwalk)) {
        // OJO, al indice tengo que restarle uno, el SNMP funciona con indices a partir de 0
        // y el cabron de PHP me devuelve indices a partir de 1 !!!!!!!
        //echo "$i: $a[$i]<br />\n";
        $snmp_output = substr($i,0,35)." - ".substr($snmpwalk[$i],0,20);
        echo "<option value=$i>".salida_limpia(substr($snmp_output,0,55))."</option>";
    }
} 
echo "</select>";
echo '<td class="datos2">';
echo '<input type="submit" class="sub next" name="oid" value="SNMP Walk">';

// Snmp Oid / community
echo '<tr>';
echo '<td class="datos">'.lang_string ("snmp_oid")."</td>";
echo '<td class="datos"><input type="text" name="form_snmp_oid" size="25" value="'.$form_snmp_oid.'"></td>';
echo '<td class="datos">'.lang_string ("snmp_community")."</td>";
echo '<td class="datos"><input type="text" name="form_snmp_community" size="12" value="'.$form_snmp_community.'"></td>';
echo '</tr>';

// Max / min value
echo '<tr>';
echo '<td class="datos2">'.lang_string ("min_value")."</td>";
echo '<td class="datos2"><input type="text" name="form_minvalue" size="5" value="'.$form_minvalue.'"></td>';
echo '<td class="datos2">'.lang_string ("max_value")."</td>";
echo '<td class="datos2"><input type="text" name="form_maxvalue" size="5" value="'.$form_maxvalue.'"></td>';
echo '</tr>';

// Post process / Export server
echo "<tr>";
echo '<td class="datos">'.lang_string ("post_process")."</td>";
echo '<td class="datos"><input type="text" name="form_post_process" size="5" value="'.$form_post_process.'">';
pandora_help("postprocess");
echo "</td>";
echo '<td class="datos">'.lang_string ("export_server")."</td>";
echo '<td class="datos"><select name="form_id_export">';
echo "<option value='0'>".lang_string("None")."</option>";
$sql1='SELECT id, name FROM tserver_export ORDER BY name;';
$result=mysql_query($sql1);
while ($row=mysql_fetch_array($result)){
    echo "<option value='".$row["id"]."'>".$row["name"]."</option>";
}
echo "</select>";
echo '</tr>';

// tcp send / rcv value
echo '<tr>';
echo '<td class="datos2">'.lang_string ("tcp_send")."</td>";
echo '<td class="datos2"><textarea cols=20 style="height:40px;" name="form_tcp_send">'.$form_tcp_send.'</textarea>';
echo '<td class="datos2">'.lang_string ("tcp_rcv")."</td>";
echo '<td class="datos2"><textarea cols=20 style="height:40px;" name="form_tcp_rcv">'.$form_tcp_rcv.'</textarea>';
echo '</tr>';

// Description
echo '</tr><tr>';
echo '<td valign="top" class="datos">'.lang_string ("description")."</td>";
echo '<td valign="top" class="datos" colspan=3><textarea name="form_description" cols=65 rows=2>'.$form_interval.'</textarea>';
echo "</table>";

// SUbmit
echo '<table width="680" cellpadding="4" cellspacing="4">';
echo '<td valign="top" align="right">';
echo '<input name="crtbutton" type="submit" class="sub wand" value="'.lang_string ("create").'">';
echo "</table>";

?>