<?php
// Pandora FMS - the Free Monitoring System
// ========================================
// Copyright (c) 2004-2008 Sancho Lerena, slerena@gmail.com
// Main PHP/SQL code development, project architecture and management.
// Copyright (c) 2004-2007 Raul Mateos Martin, raulofpandora@gmail.com
// CSS and some PHP code additions
// Please see http://pandora.sourceforge.net for full contribution list

// Load global vars
require("include/config.php");
if (give_acl($id_user, 0, "AW")!=1) {
	audit_db($id_usuario,$REMOTE_ADDR, "ACL Violation","Trying to access agent manager");
	require ("general/noaccess.php");
	exit;
};

if (isset($_GET["id_agente"]))
	$id_agente = $_GET["id_agente"];
else {
	$id_agente = -1;
	if (isset($_POST["update_agent"])) { // if modified some agent paramenter
		$id_agente = entrada_limpia($_POST["id_agente"]);
	}
}

// Get passed variables
$tab = get_parameter_get("tab","main");
$form_moduletype = get_parameter_post ("form_moduletype");

// Init vars
$descripcion = "";
$comentarios = "";
$campo_1 = "";
$campo_2 = "";
$campo_3 = "";
$maximo = "0";
$minimo = "0";
$nombre_agente = "";
$direccion_agente = "";
$intervalo = "300";
$id_server = "";
$max_alerts = 0;
$modo = 1;
$update_module = 0;
$modulo_id_agente = "";
$modulo_id_tipo_modulo = "";
$modulo_nombre = "";
$modulo_descripcion = "";
$alerta_id_aam = "";
$alerta_campo1 = "";
$alerta_campo2 = "";
$alerta_campo3 ="";
$alerta_dis_max = "";
$alerta_dis_min = "";
$alerta_min_alerts = "0";
$alerta_max_alerts = "1";
$alerta_time_threshold = "";
$alerta_descripcion = "";
$disabled="";
$modulo_max="";
$modulo_min='';
$module_interval = "";
$tcp_port = "";
$tcp_send = "";
$tcp_rcv = "";
$snmp_oid= "";
$ip_target ="";
$snmp_community="";
$combo_snmp_oid="";
$agent_created_ok = 0;
$create_agent = 0;
$alert_text = "";
$time_from= "";
$time_to = "";

// ================================
// Create AGENT
// ================================
// We need to create agent BEFORE show tabs, because we need to get agent_id
// This is not very clean, but...
if ( isset ($_POST["create_agent"])) { // Create a new and shining agent
	$nombre_agente =  entrada_limpia ($_POST["agente"]);
	$direccion_agente =  entrada_limpia ($_POST["direccion"]);
	$grupo =  entrada_limpia ($_POST["grupo"]);
	$intervalo =  entrada_limpia ($_POST["intervalo"]);
	$comentarios =  entrada_limpia ($_POST["comentarios"]);
	$modo = entrada_limpia ($_POST["modo"]);
	$id_network_server = get_parameter_post ($_POST["network_server"], 0);
    $id_plugin_server = get_parameter_post ($_POST["plugin_server"], 0);
    $id_prediction_server = get_parameter_post ($_POST["prediction_server"], 0);
    $id_wmi_server = get_parameter_post ($_POST["wmi_server"], 0);
	$id_os = entrada_limpia ($_POST["id_os"]);
	$disabled = entrada_limpia ($_POST["disabled"]);

	// Check if agent exists (BUG WC-50518-2 )
	$sql1='SELECT nombre FROM tagente WHERE nombre = "'.$nombre_agente.'"';
	$result=mysql_query($sql1);
	if ($row=mysql_fetch_array($result)){
        $agent_creation_error  =  lang_string("agent_exists");
        $agent_created_ok = 0;
    } else {
		$sql_insert ="INSERT INTO tagente (nombre, direccion, id_grupo, intervalo, comentarios,modo, id_os, disabled, id_network_server, id_plugin_server, id_wmi_server, id_prediction_server) VALUES ('$nombre_agente', '$direccion_agente', $grupo , $intervalo , '$comentarios',$modo, $id_os, $disabled, $id_network_server, $id_plugin_server, $id_wmi_server, $id_prediction_server)";
		$result = mysql_query($sql_insert);
		if ($result) {
            $agent_created_ok = 1;
            $agent_creation_error = "";
            $id_agente = mysql_insert_id ();
            // Create special MODULE agent_keepalive
            $sql_insert = "INSERT INTO tagente_modulo (nombre, id_agente, id_tipo_modulo, descripcion)
                         VALUES ('agent_keepalive', ".$id_agente.",100,'Agent Keepalive monitor')";
            $result=mysql_query($sql_insert);
            $id_agent_module = mysql_insert_id();
            // And create MODULE agent_keepalive in tagente_estado table 
            $sql_insert2 ="INSERT INTO tagente_estado (id_agente_modulo, datos, timestamp, cambio, estado, id_agente, last_try, utimestamp, current_interval, running_by, last_execution_try) VALUES ($id_agent_module, 1, '', 0, 0, $id_agente, 0,0,0,0,0)";
            $result = mysql_query ($sql_insert2);
            if (!$result){
                $agent_created_ok = 0;
                $agent_creation_error = "Problem creating record on tagente_estado table";
            }
            // Create address for this agent in taddress
            agent_add_address ($id_agente, $direccion_agente);
        } else {
            $agent_created_ok = 0;
            $agent_creation_error = "Cannot create agent <br><i>$sql_insert</i>";
        }
    }
}

// Show tabs
// -----------------
echo "<div id='menu_tab_frame'>";
echo "<div id='menu_tab_left'>
<ul class='mn'>";
echo "<li class='nomn'>";
echo "<a href='index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&id_agente=$id_agente'>
<img src='images/setup.png' class='top' border='0'>&nbsp; ".substr(dame_nombre_agente($id_agente),0,15)." - ".$lang_label["setup_mode"]."</a>";
echo "</li>";
echo "</ul></div>";

echo "<div id='menu_tab'><ul class='mn'>";

echo "<li class='nomn'>";
echo "<a href='index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=$id_agente'><img src='images/zoom.png' width='16' class='top' border='0'>&nbsp;".$lang_label["view"]."</a>";
echo "</li>";

if ($tab == "main")
    echo "<li class='nomn_high'>";
else
    echo "<li class='nomn'>";
echo "<a href='index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=main&id_agente=$id_agente'><img src='images/cog.png' width='16' class='top' border='0'>&nbsp; ".$lang_label["setup_agent"]."</a>";
echo "</li>";

if ($tab == "module")
    echo "<li class='nomn_high'>";
else
    echo "<li class='nomn'>";
echo "<a href='index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=module&id_agente=$id_agente'><img src='images/lightbulb.png' width='16' class='top' border='0'>&nbsp;".$lang_label["modules"]."</a>";
echo "</li>";

if ($tab == "alert")
    echo "<li class='nomn_high'>";
else
    echo "<li class='nomn'>";
echo "<a href='index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=alert&id_agente=$id_agente'><img src='images/bell.png' width='16' class='top' border='0'>&nbsp;". $lang_label["Alerts"]."</a>";
echo "</li>";

if ($tab == "template")
    echo "<li class='nomn_high'>";
else
    echo "<li class='nomn'>";
echo "<a href='index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=template&id_agente=$id_agente'><img src='images/network.png' width='16' class='top' border=0>&nbsp;".$lang_label["ntemplates"]."</a>";
echo "</li>";

echo "</ul>";
echo "</div>";
echo "</div>"; // menu_tab_frame

// Make some space between tabs and title
echo "<div style='height: 25px'> </div>";


// Show agent creation results
if (isset($_POST["create_agent"])){
	if ($agent_created_ok == 0){
		echo "<h3 class='error'>".$lang_label["create_agent_no"]."</h3>";
        echo $agent_creation_error;
    } else {
		echo "<h3 class='suc'>".$lang_label["create_agent_ok"]."</h3>";
    }
}


// Fix / Normalize module data
// ===========================
if (isset($_GET["fix_module"])){ 
        $id_module = $_GET["fix_module"];
	$id_agent = $_GET["id_agente"];
        // get info about this module
        $sql1 = "SELECT AVG(datos) FROM tagente_datos WHERE id_agente_modulo = $id_module AND id_agente = $id_agent";
        $result=mysql_query($sql1);
        if ($row=mysql_fetch_array($result)){
		$media = $row[0];
		$media = $media * 1.3;
		$sql1 = "DELETE FROM tagente_datos WHERE datos > $media AND id_agente_modulo = $id_module AND id_agente = $id_agent";
	        $result=mysql_query($sql1);
		echo "<h3 class='suc'>".$lang_label["delete_data_above"]." $media</h3>";
	}
}


// Delete Alert
// =============
if (isset($_GET["delete_alert"])){ // if modified some parameter
	$id_borrar_modulo = $_GET["delete_alert"];
	// get info about agent
	$sql1='DELETE FROM talerta_agente_modulo WHERE id_aam = '.$id_borrar_modulo;
	$result=mysql_query($sql1);
		if (! $result)
			echo "<h3 class='error'>".$lang_label["delete_alert_no"]."</h3>";
		else
			echo "<h3 class='suc'>".$lang_label["delete_alert_ok"]."</h3>";

}

// Create alert
// =============
if (isset($_POST["insert_alert"])){ // if created alert
	//$id_agente = $_POST["id_agente"];
	$id_agente_modulo = entrada_limpia($_POST["agente_modulo"]);
	$descripcion= entrada_limpia($_POST["descripcion"]);
	$campo_1 = entrada_limpia($_POST["campo_1"]);
	$campo_2 = entrada_limpia($_POST["campo_2"]);
	$campo_3 = entrada_limpia($_POST["campo_3"]);
	$maximo = entrada_limpia($_POST["maximo"]);
	$minimo = entrada_limpia($_POST["minimo"]);
	$tipo_alerta = entrada_limpia($_POST["tipo_alerta"]);
	$alert_text = entrada_limpia($_POST["alert_text"]);
	$time_threshold = entrada_limpia($_POST["time_threshold"]);
	$max_alerts = entrada_limpia($_POST["max_alerts"]);
	$min_alerts = entrada_limpia($_POST["min_alerts"]);
	$time_from = entrada_limpia($_POST["time_from"]);
	$time_to= entrada_limpia($_POST["time_to"]);
	$other = entrada_limpia($_POST["other"]);
	$disable_alert = entrada_limpia($_POST["disable_alert"]);
	if ($time_threshold == -1) {
		$time_threshold = $other;
	}
	$sql_insert="INSERT INTO talerta_agente_modulo
			(id_agente_modulo,id_alerta,al_campo1,al_campo2,al_campo3,descripcion,dis_max,dis_min,time_threshold,max_alerts, min_alerts, alert_text, disable, time_from, time_to) VALUES
			('$id_agente_modulo',
			'$tipo_alerta',
			'$campo_1',
			'$campo_2',
			'$campo_3',
			'$descripcion',
			'$maximo',
			'$minimo',
			'$time_threshold',
			'$max_alerts',
			'$min_alerts',
			'$alert_text',
			'$disable_alert',
			'$time_from',
			'$time_to' )";
	$result=mysql_query($sql_insert);	
	if (! $result) {
		echo "<h3 class='error'>".$lang_label["create_alert_no"]."</h3>";
	} else {
		$id_alerta_agente_modulo = mysql_insert_id();
		echo "<h3 class='suc'>".$lang_label["create_alert_ok"]."</h3>";
	}
	
}
// Update ALERT
// =============
if (isset($_POST["update_alert"])){ // Update an existing alert
	$id_aam = entrada_limpia($_POST["id_aam"]);
	$tipo_alerta = entrada_limpia($_POST["tipo_alerta"]);
	if (isset($_POST["agente_modulo"])) {
		$id_agente_modulo = entrada_limpia($_POST["agente_modulo"]);
	}
	$descripcion= entrada_limpia($_POST["descripcion"]);
	$campo_1 = entrada_limpia($_POST["campo_1"]);
	$campo_2 = entrada_limpia($_POST["campo_2"]);
	$campo_3 = entrada_limpia($_POST["campo_3"]);
	$maximo = entrada_limpia($_POST["maximo"]);
	$minimo = entrada_limpia($_POST["minimo"]);
	$time_threshold = entrada_limpia($_POST["time_threshold"]);
	$max_alerts = entrada_limpia($_POST["max_alerts"]);
	$min_alerts = entrada_limpia($_POST["min_alerts"]);
	$other = entrada_limpia($_POST["other"]);
	$alert_text = entrada_limpia($_POST["alert_text"]);
	$time_from = entrada_limpia($_POST["time_from"]);
	$time_to= entrada_limpia($_POST["time_to"]);
	$disable_alert = entrada_limpia($_POST["disable_alert"]);
	if ($time_threshold == -1) {
		$time_threshold = $other;
	}

	$sql_insert="UPDATE talerta_agente_modulo SET
		id_alerta = $tipo_alerta,
		max_alerts = '$max_alerts',
		min_alerts = '$min_alerts' ,
		time_threshold = '$time_threshold',
		dis_min = '$minimo' ,
		dis_max = '$maximo' ,
		al_campo3 = '$campo_3' ,
		al_campo2 = '$campo_2' ,
		al_campo1 = '$campo_1' ,
		descripcion = '$descripcion',
		alert_text = '$alert_text',
		time_to = '$time_to',
		time_from = '$time_from',
		disable = '$disable_alert'
		WHERE id_aam = ".$id_aam;
	$result=mysql_query($sql_insert);	
	if (! $result) {
		echo "<h3 class='error'>".$lang_label["update_alert_no"]."</h3>";
		// echo "SQL DEBUG  ".$sql_insert;
	}
	else 
		echo "<h3 class='suc'>".$lang_label["update_agent_ok"]."</h3>";
}

// ================
// Update AGENT
// ================
if (isset($_POST["update_agent"])) { // if modified some agent paramenter
	$id_agente = entrada_limpia($_POST["id_agente"]);
	$nombre_agente =  entrada_limpia($_POST["agente"]);
	$direccion_agente =  entrada_limpia($_POST["direccion"]);
	$old_agent_address = give_agent_address ($id_agente);
	$grupo =  entrada_limpia($_POST["grupo"]);
	$intervalo =  entrada_limpia($_POST["intervalo"]);
	$comentarios =  entrada_limpia($_POST["comentarios"]);
	$modo = entrada_limpia($_POST["modo"]);
	$id_os = entrada_limpia($_POST["id_os"]);
	$disabled = entrada_limpia($_POST["disabled"]);
    $id_network_server = get_parameter ("network_server", 0);
    $id_plugin_server = get_parameter ("plugin_server", 0);
    $id_wmi_server = get_parameter ("wmi_server", 0);
    $id_prediction_server = get_parameter ("prediction_server", 0);

	if ($direccion_agente != $old_agent_address){
		agent_add_address ($id_agente, $direccion_agente);
	}
	$sql_update ="UPDATE tagente 
		SET disabled = ".$disabled." , id_os = ".$id_os." , modo = ".$modo." , nombre = '".$nombre_agente."', direccion = '".$direccion_agente."', id_grupo = '".$grupo."', intervalo = '".$intervalo."', comentarios = '".$comentarios."', id_network_server = '$id_network_server', id_plugin_server = $id_plugin_server, id_wmi_server = $id_wmi_server,
        id_prediction_server = $id_prediction_server 
		WHERE id_agente = '".$id_agente."'";

	// Delete one of associateds IP's ?
	if (isset($_POST["delete_ip"])) {
		$delete_ip = $_POST["address_list"];
		agent_delete_address ($id_agente, $delete_ip);
	}
	
	$result=mysql_query($sql_update);
	if (! $result) {
		echo "<h3 class='error'>".$lang_label["update_agent_no"]."</h3>";
	} else {
		echo "<h3 class='suc'>".$lang_label["update_agent_ok"]."</h3>";
	}
}

if ((isset($agent_created_ok)) && ($agent_created_ok == 1)){
	$_GET["id_agente"]= $id_agente;
}

// Read agent data
// This should be at the end of all operation checks, to read the changes
if (isset($_GET["id_agente"])) {
	$id_agente = $_GET["id_agente"];
	$id_grupo = dame_id_grupo($id_agente);
	if (give_acl($id_user, $id_grupo, "AW")==1){
		$sql1='SELECT * FROM tagente WHERE id_agente = '.$id_agente;
		$result=mysql_query($sql1);
		if ($row=mysql_fetch_array($result)){
			$intervalo = $row["intervalo"]; // Define interval in seconds
			$nombre_agente = $row["nombre"];
			$direccion_agente =$row["direccion"];
			$grupo = $row["id_grupo"];
			$ultima_act = $row["ultimo_contacto"];
			$comentarios = $row["comentarios"];
            $id_plugin_server = $row["id_plugin_server"];
			$id_network_server = $row["id_network_server"];
            $id_prediction_server = $row["id_prediction_server"];
            $id_wmi_server = $row["id_wmi_server"];
			$modo = $row["modo"];
			$id_os = $row["id_os"];
			$disabled=$row["disabled"];
		} else {
			echo "<h3 class='error'>".$lang_label["agent_error"]."</h3>";
			echo "</table>";
			echo "</div><div id='foot'>";
				include ("general/footer.php");
			echo "</div>";
			exit;
		}
	} else {
		audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to admin an Agent out of admin groups!");
		require ("general/noaccess.php");
		echo "</table>";
		require ("general/footer.php");
		exit;
		}
}

// Read data module if editing module
// ==================================
if ((isset($_GET["update_module"])) && (!isset($_POST["oid"])) && (!isset($_POST["update_module"]))) {
	$update_module = 1;
	$id_agente_modulo = $_GET["update_module"];

	$sql_update = "SELECT * FROM tagente_modulo 
	WHERE id_agente_modulo = ".$id_agente_modulo;
	$result=mysql_query($sql_update);		
	while ($row=mysql_fetch_array($result)){
		$modulo_id_agente = $row["id_agente"];
		$modulo_id_tipo_modulo = $row["id_tipo_modulo"];
		$modulo_nombre = $row["nombre"];
		$modulo_descripcion = $row["descripcion"];		
		$tcp_send = $row["tcp_send"];
		$tcp_rcv = $row["tcp_rcv"];
		$tcp_port = $row["tcp_port"];
		$ip_target = $row["ip_target"];
		$snmp_community = $row["snmp_community"];
		$snmp_oid = $row["snmp_oid"];
		$id_module_group = $row["id_module_group"];
		$module_interval = $row["module_interval"];
		$modulo_max = $row["max"];
		if (! isset($modulo_max))
			$modulo_max ="N/A";
		$modulo_min = $row["min"];
		if (! isset($modulo_min))
			$modulo_min ="N/A";
	}
}
// Read alert data if editing alert
if (isset($_GET["update_alert"])){
	$id_grupo = dame_id_grupo($id_agente);
	if (give_acl($id_user, $id_grupo, "LW")==0){
		audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to update an alert without admin rights");
		require ("general/noaccess.php");
		echo "</table>";
		require ("general/footer.php");
		exit;
	}
	$update_alert = 1;
	$id_aam = $_GET["update_alert"];
	$sql_update = "SELECT * FROM talerta_agente_modulo WHERE id_aam = ".$id_aam;
	$result=mysql_query($sql_update);		
	while ($row=mysql_fetch_array($result)){
		$alerta_id_aam = $id_aam;
		$alerta_campo1 = $row["al_campo1"];
		$alerta_campo2 = $row["al_campo2"];
		$alerta_campo3 = $row["al_campo3"];
		$alerta_dis_max = $row["dis_max"];
		$alerta_dis_min = $row["dis_min"];
		$tipo_alerta = $row["id_alerta"];
		$alert_text = $row["alert_text"];
		$alerta_max_alerts = $row["max_alerts"];
		$alerta_min_alerts = $row["min_alerts"];
		$alerta_time_threshold = $row["time_threshold"];
		$alerta_descripcion = $row["descripcion"];
		$alerta_disable = $row["disable"];
		$time_from = $row["time_from"];
		$time_to = $row["time_to"];
		$alerta_id_agentemodulo = $row["id_agente_modulo"]; // Only to show, cannot be changed
	}

}

// GET DATA for MODULE UPDATE OR MODULE INSERT
// ===========================================
if ((isset($_POST["update_module"])) || (isset($_POST["insert_module"]))) {
	if (isset($_POST["update_module"])){
		$update_module = 1;
		$id_agente_modulo = $_POST["id_agente_modulo"];
	}
	$id_grupo = dame_id_grupo($id_agente);
	if (give_acl($id_user, $id_grupo, "AW")==0){
		audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to create a module without admin rights");
		require ("general/noaccess.php");
		echo "</table>";
		require ("general/footer.php");
		exit;
	}

	$form_id_tipo_modulo = get_parameter ("form_id_tipo_modulo");
    $form_name = get_parameter ("form_name");
    $form_description = get_parameter ("form_description");
    $form_id_module_group = get_parameter ("form_id_module_group",0);
    $form_id_tipo_modulo = get_parameter ("form_id_tipo_modulo");
    $form_post_process = get_parameter ("form_post_process",0);
    $form_max_timeout = get_parameter ("form_max_timeout",0);
    $form_minvalue = get_parameter_post ("form_minvalue",0);
    $form_maxvalue = get_parameter ("form_maxvalue",0);
    $form_interval = get_parameter ("form_interval",345345345);
    $form_id_prediction_module = get_parameter ("form_id_prediction_module",0);
    $form_id_plugin = get_parameter ("form_id_plugin",0);
    $form_id_export = get_parameter ("form_id_export",0);
    $form_disabled = get_parameter ("form_disabled",0);
    $form_tcp_send = get_parameter ("form_tcp_send","");
    $form_tcp_rcv = get_parameter ("form_tcp_rcv","");
    $form_tcp_port = get_parameter ("form_tcp_port",0);
    $form_snmp_community = get_parameter ("form_snmp_community","public");
    $form_snmp_oid = get_parameter ("form_snmp_oid","");
    $form_ip_target = get_parameter ("form_ip_target","");
    $form_plugin_user = get_parameter ("form_plugin_user","");
    $form_plugin_pass = get_parameter ("form_plugin_pass","");
    $form_plugin_parameter = get_parameter ("form_plugin_parameter","");
    $form_id_modulo = get_parameter ("form_id_modulo");
}

// MODULE UPDATE
// =================
if ((isset($_POST["update_module"])) && (!isset($_POST["oid"]))){ // if modified something
	if (isset($_POST["form_combo_snmp_oid"])){
		$form_combo_snmp_oid = entrada_limpia($_POST["form_combo_snmp_oid"]);
		if ($snmp_oid == ""){
			$snmp_oid = $form_combo_snmp_oid;
		}
	}
	$sql_update = "UPDATE tagente_modulo 
	SET max ='".$modulo_max."', 
	min = '".$modulo_min."', 
	nombre='".$nombre."', 
	descripcion='".$descripcion."', 
	tcp_send = '$tcp_send', 
	tcp_rcv = '$tcp_rcv', 
	tcp_port = '$tcp_port', 
	ip_target = '$ip_target', 
	snmp_oid = '$snmp_oid', 
	snmp_community = '$snmp_community', 
	id_module_group = '$id_module_group', 
	module_interval = '$module_interval' 
	WHERE id_agente_modulo = ".$id_agente_modulo;
	$result=mysql_query($sql_update);	
	if (! $result) {
		echo "<h3 class='error'>".$lang_label["update_module_no"]."</h3>";
	} else {
		echo "<h3 class='suc'>".$lang_label["update_module_ok"]."</h3>";
	}
	// Init vars to null to avoid trash in forms 
	$id_tipo_modulo = "";$nombre =  "";$descripcion = "";$modulo_max = "";
	$modulo_min = "";// Pandora 1.2 new module data:
	$tcp_send = "";$tcp_rcv = "";$tcp_port = "";$ip_target = "";
	$snmp_oid = "";$snmp_community = "";$id_module_group = "";
	$module_interval = ""; $modulo_nombre = ""; $modulo_descripcion = "";
	$update_module = 0;
}
// =========================================================
// OID Refresh button to get SNMPWALK from data in form
// This code is also applied when submitting a new module (insert_module = 1)
// =========================================================
if (isset($_POST["oid"])){
	snmp_set_quick_print(1);
	if (! ($snmpwalk = snmprealwalk($ip_target, $snmp_community, ""))) {
		echo "<h3 class='error'>".$lang_label["cannot_read_snmp"]."</h3>";
	} else {
		echo "<h3 class='suc'>".$lang_label["ok_read_snmp"]."</h3>";
	}
}


// =========================================================
// MODULE INSERT
// =========================================================

if (((!isset($_POST["nc"]) OR ($_POST["nc"]==-1)) ) &&
			(!isset($_POST["oid"]))		&&
			(isset($_POST["insert_module"]))){

	if (isset($_POST["form_combo_snmp_oid"])) {
		$combo_snmp_oid = entrada_limpia($_POST["form_combo_snmp_oid"]);
	}
	if ($form_snmp_oid == ""){
		$form_snmp_oid = $combo_snmp_oid;
	}
	if ($form_tcp_port == "") {
		$form_tcp_port= "0";
	}
	$sql_insert = "INSERT INTO tagente_modulo 
            (id_agente, id_tipo_modulo, nombre, descripcion, max, min, snmp_oid, snmp_community,
            id_module_group, module_interval, ip_target, tcp_port, tcp_rcv, tcp_send, id_export, 
            plugin_user, plugin_pass, plugin_parameter, id_plugin, post_process, prediction_module,
            max_timeout, disabled, id_modulo) 
            VALUES ($id_agente, $form_id_tipo_modulo, '$form_name', '$form_description', $form_maxvalue, $form_minvalue, '$form_snmp_oid', '$form_snmp_community', $form_id_module_group, $form_interval, '$form_ip_target', $form_tcp_port, '$form_tcp_rcv', '$form_tcp_send', $form_id_export, '$form_plugin_user', '$form_plugin_pass', '$form_plugin_parameter', $form_id_plugin, $form_post_process, $form_id_prediction_module, $form_max_timeout, $form_disabled, $form_id_modulo)";
	$result=mysql_query($sql_insert);
    if (! $result){
        echo "<h3 class='error'>".$lang_label["add_module_no"]."</h3>";
        echo "<i>DEBUG: $sql_insert</i>";
    } else {
        echo "<h3 class='suc'>".$lang_label["add_module_ok"]."</h3>";
	    $id_agente_modulo = mysql_insert_id();
	    // Create with different estado if proc type or data type
	    if ( 
	    ($form_id_tipo_modulo == 2) ||   // data_proc
	    ($form_id_tipo_modulo == 6) ||   // icmp_proc
	    ($form_id_tipo_modulo == 9) ||   // tcp_proc
	    ($form_id_tipo_modulo == 18) ||  //snmp proc
        ($form_id_tipo_modulo == 21) ||  // async proc
        ($form_id_tipo_modulo == 100)  // Keepalive
	    ){ 
		    $sql_insert2 = "INSERT INTO tagente_estado 
		    (id_agente_modulo,datos,timestamp,cambio,estado,id_agente, utimestamp) 
		    VALUES (
		    $id_agente_modulo, 0,'0000-00-00 00:00:00',0,0,'".$id_agente."',0
		    )";
	    } else { 
		    $sql_insert2 = "INSERT INTO tagente_estado 
		    (id_agente_modulo,datos,timestamp,cambio,estado,id_agente, utimestamp) 
		    VALUES (
		    $id_agente_modulo, 0,'0000-00-00 00:00:00',0,100,'".$id_agente."',0
		    )";
	    }
        $result=mysql_query($sql_insert2);
    }
}

// MODULE DELETION
// =================
if (isset($_GET["delete_module"])){ // DELETE agent module !
	$id_borrar_modulo = $_GET["delete_module"];
	$id_grupo = dame_id_grupo($id_agente);	if (give_acl($id_user, $id_grupo, "AW")==0){
		audit_db($id_user,$REMOTE_ADDR, "ACL Violation",
		"Trying to delete a module without admin rights");
		require ("general/noaccess.php");
		exit;
	}
	// First detele from tagente_modulo
	$sql_delete= "DELETE FROM tagente_modulo 
	WHERE id_agente_modulo = ".$id_borrar_modulo;
	$result=mysql_query($sql_delete);
	if (! $result) {
		echo "<h3 class='error'>".$lang_label["delete_module_no"]."</h3>"; 
	} else {
		echo "<h3 class='suc'>".$lang_label["delete_module_ok"]."</h3>";
	}
	// Then delete all staus
	$sql_delete = "DELETE FROM tagente_estado 
	WHERE id_agente_modulo = ".$id_borrar_modulo;
	$result=mysql_query($sql_delete);
	
	$sql_delete = "DELETE FROM tagente_datos 
	WHERE id_agente_modulo = ".$id_borrar_modulo;
	$result=mysql_query($sql_delete);
	
	$sql_delete = "DELETE FROM tagente_datos_string 
	WHERE id_agente_modulo = ".$id_borrar_modulo;
	$result=mysql_query($sql_delete);
	
	$sql_delete = "DELETE FROM tagente_datos_inc 
	WHERE id_agente_modulo = ".$id_borrar_modulo;
	$result=mysql_query($sql_delete);
}

// -----------------------------------
// Load page depending on tab selected
// -----------------------------------

switch ($tab) {
case "main":	require "agent_manager.php";
		break;
case "module": 	
        if ($form_moduletype == "")
            require "module_manager.php";
        else 
            require "module_manager_editor.php";
		break;
case "alert": 	require "alert_manager.php";
		break;
case "template":require "agent_template.php";
		break;
}


?>
