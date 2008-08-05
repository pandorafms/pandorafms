<?php
// Pandora FMS - the Free Monitoring System
// ========================================
// Copyright (c) 2008 Artica Soluciones Tecnológicas, http://www.artica.es
// Copyright (c) 2008 Jorge Gonzalez <jorge.gonzalez@artica.es>
// Copyright (c) 2008 Sancho Lerena <sancho.lerena@artica.es>
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
require("include/config.php");
if (give_acl($config["id_user"], 0, "AW")!=1) {
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
$tab = get_parameter_get ("tab","main");
$form_moduletype = get_parameter_post ("form_moduletype");
$form_alerttype = get_parameter ("form_alerttype");
$moduletype = get_parameter_get ("moduletype");

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
$id_parent="0";
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
$alerta_campo2_rec = "";
$alerta_campo3_rec = "";
$alert_id_agent = "";
$alert_d1 = "1";
$alert_d2 = "1";
$alert_d3 = "1";
$alert_d4 = "1";
$alert_d5 = "1";
$alert_d6 = "1";
$alert_d7 = "1";
$alert_recovery = "0";
$alert_priority = "0";
$id_network_server = 0;
$id_plugin_server = 0;
$id_prediction_server = 0;
$id_wmi_server = 0;
$grupo = 0;
$id_os = 0;

// ================================
// Create AGENT
// ================================
// We need to create agent BEFORE showing tabs, because we need to get agent_id
// This is not very clean, but...
if ( isset ($_POST["create_agent"])) { // Create a new and shining agent
	$nombre_agente =  entrada_limpia ($_POST["agente"]);
	$direccion_agente =  entrada_limpia ($_POST["direccion"]);
	$grupo =  entrada_limpia ($_POST["grupo"]);
	$intervalo =  entrada_limpia ($_POST["intervalo"]);
	$comentarios =  entrada_limpia ($_POST["comentarios"]);
	$modo = entrada_limpia ($_POST["modo"]);
	$id_parent = get_parameter_post ("id_parent", 0);
	$id_network_server = get_parameter_post ("network_server", 0);
	$id_plugin_server = get_parameter_post ("plugin_server", 0);
	$id_prediction_server = get_parameter_post ("prediction_server", 0);
	$id_wmi_server = get_parameter_post ("wmi_server", 0);

	$id_os = entrada_limpia ($_POST["id_os"]);
	$disabled = entrada_limpia ($_POST["disabled"]);

	// Check if agent exists (BUG WC-50518-2)
	$sql1='SELECT nombre FROM tagente WHERE nombre = "'.$nombre_agente.'"';
	$result=mysql_query($sql1);
	if ($row=mysql_fetch_array($result)){
		$agent_creation_error  =  lang_string("agent_exists");
		$agent_created_ok = 0;
	} else {
		$sql_insert ="INSERT INTO tagente (nombre, direccion, id_grupo, intervalo, comentarios,modo, id_os, disabled, id_network_server, id_plugin_server, id_wmi_server, id_prediction_server, id_parent) VALUES ('$nombre_agente', '$direccion_agente', $grupo , $intervalo , '$comentarios',$modo, $id_os, $disabled, $id_network_server, $id_plugin_server, $id_wmi_server, $id_prediction_server, $id_parent)";
		$result = mysql_query($sql_insert);
		if ($result) {
            $agent_created_ok = 1;
            $agent_creation_error = "";
            $id_agente = mysql_insert_id ();
            // Create special MODULE agent_keepalive
            $sql_insert = "INSERT INTO tagente_modulo (nombre, id_agente, id_tipo_modulo, descripcion, id_modulo) VALUES ('agent_keepalive', ".$id_agente.",100,'Agent Keepalive monitor',1)";
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

// Delete Alert component (from a combined)
// ==========================================
if (isset($_GET["delete_alert_comp"])){ // if modified some parameter
	$id_borrar_modulo = $_GET["delete_alert_comp"];
	// get info about agent
	$sql1='DELETE FROM tcompound_alert WHERE id_aam = '.$id_borrar_modulo;
	$result=mysql_query($sql1);
	if (! $result)
		echo "<h3 class='error'>".$lang_label["delete_alert_no"]."</h3>";
	else
		echo "<h3 class='suc'>".$lang_label["delete_alert_ok"]."</h3>";
}

// Create alert
// =============
if (isset($_POST["insert_alert"])){ // if created alert
	$combined = get_parameter ("combined",0);
	$id_agente_modulo = get_parameter ("agente_modulo",0);
	$maximo = get_parameter ("maximo",0);
	$minimo = get_parameter ("minimo",0);
	$descripcion= get_parameter ('descripcion', '');
	$campo_1 = get_parameter ('campo_1', '');
	$campo_2 = get_parameter ('campo_2', '');
	$campo_3 = get_parameter ('campo_3', '');
	$tipo_alerta = get_parameter ("tipo_alerta",3);
	$alert_text = get_parameter ("alert_text",'');
	$time_threshold = get_parameter ("time_threshold",900);
	$max_alerts = get_parameter ("max_alerts",1);
	$min_alerts = get_parameter ("min_alerts",0);
	$time_from = get_parameter ("time_from");
	$time_to= get_parameter ("time_to");
	$other = get_parameter ("other",0);
	$disable_alert = get_parameter ("disable_alert");
	$alert_d1 = get_parameter("alert_d1",0);
	$alert_d2 = get_parameter("alert_d2",0);
	$alert_d3 = get_parameter("alert_d3",0);
	$alert_d4 = get_parameter("alert_d4",0);
	$alert_d5 = get_parameter("alert_d5",0);
	$alert_d6 = get_parameter("alert_d6",0);
	$alert_d7 = get_parameter("alert_d7",0);
	$alert_recovery = get_parameter("alert_recovery", 1);
	$alert_priority = get_parameter("alert_priority", 0);
	$campo2_rec = get_parameter ("campo_2_rec","");
	$campo3_rec = get_parameter ("campo_3_rec","");

	if ($combined == 1)
		$alert_id_agent = $id_agente;
	else
		$alert_id_agent = 0;
	if ($time_threshold == -1) {
		$time_threshold = $other;
	}
	$sql_insert="INSERT INTO talerta_agente_modulo
			(id_agente_modulo,id_alerta,al_campo1,al_campo2,al_campo3,descripcion,dis_max,dis_min,time_threshold,max_alerts, min_alerts, alert_text, disable, time_from, time_to, id_agent, monday, tuesday, wednesday, thursday, friday, saturday, sunday, recovery_notify, priority, al_f2_recovery, al_f3_recovery) VALUES
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
			'$time_to',
			$alert_id_agent,
			$alert_d1,
			$alert_d2,
			$alert_d3,
			$alert_d4,
			$alert_d5,
			$alert_d6,
			$alert_d7,
			$alert_recovery,
			$alert_priority,
			'$campo2_rec',
			'$campo2_rec' )";
	$result=mysql_query($sql_insert);	
	if (! $result) {
		echo "<h3 class='error'>".$lang_label["create_alert_no"]."</h3>";
	} else {
		$id_alerta_agente_modulo = mysql_insert_id();
		echo "<h3 class='suc'>".$lang_label["create_alert_ok"]."</h3>";
	}
	
}

// Combined ALERT - Add component
// ================================
if (isset($_POST["add_alert_combined"])){ // Update an existing alert
	$alerta_id_aam = get_parameter ("update_alert",-1);
	$component_item = get_parameter ("component_item",-1);
	$component_operation = get_parameter ("component_operation","AND");
	$sql_insert = "INSERT INTO tcompound_alert (id, id_aam, operation) 
	VALUES ($alerta_id_aam, $component_item, '$component_operation')";
	$result=mysql_query($sql_insert);
	if (! $result) {
		echo "<h3 class='error'>".lang_string ("Problem adding component alert")."</h3>";
	} else {
		echo "<h3 class='suc'>".lang_string ("Alert component added ok")."</h3>";
	}

}

// Update ALERT
// =============
if (isset($_POST["update_alert"])){ // Update an existing alert
	$id_agente_modulo = get_parameter ("agente_modulo",0);
	$id_aam = get_parameter ("id_aam",0);
	$maximo = get_parameter ("maximo",0);
	$minimo = get_parameter ("minimo",0);
	$descripcion= get_parameter ('descripcion', '');
	$campo_1 = get_parameter ('campo_1', '');
	$campo_2 = get_parameter ('campo_2', '');
	$campo_3 = get_parameter ('campo_3', '');
	$tipo_alerta = get_parameter ("tipo_alerta",3);
	$alert_text = get_parameter ("alert_text",'');
	$time_threshold = get_parameter ("time_threshold",900);
	$max_alerts = get_parameter ("max_alerts",1);
	$min_alerts = get_parameter ("min_alerts",0);
	$time_from = get_parameter ("time_from");
	$time_to= get_parameter ("time_to");
	$other = get_parameter ("other",0);
	$disable_alert = get_parameter ("disable_alert");
	$alert_d1 = get_parameter("alert_d1",0);
	$alert_d2 = get_parameter("alert_d2",0);
	$alert_d3 = get_parameter("alert_d3",0);
	$alert_d4 = get_parameter("alert_d4",0);
	$alert_d5 = get_parameter("alert_d5",0);
	$alert_d6 = get_parameter("alert_d6",0);
	$alert_d7 = get_parameter("alert_d7",0);
	$alert_recovery = get_parameter("alert_recovery", 1);
	$alert_priority = get_parameter("alert_priority", 0);
	$campo2_rec = get_parameter ("campo_2_rec","");
	$campo3_rec = get_parameter ("campo_3_rec",""); 
	$alert_d1 = get_parameter("alert_d1",0);
	$alert_d2 = get_parameter("alert_d2",0);
	$alert_d3 = get_parameter("alert_d3",0);
	$alert_d4 = get_parameter("alert_d4",0);
	$alert_d5 = get_parameter("alert_d5",0);
	$alert_d6 = get_parameter("alert_d6",0);
	$alert_d7 = get_parameter("alert_d7",0);
	$alert_recovery = get_parameter("alert_recovery", 1);
	$alert_priority = get_parameter("alert_priority", 0);
	$campo2_rec = get_parameter ("campo_2_rec","");
	$campo3_rec = get_parameter ("campo_3_rec","");
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
		disable = '$disable_alert',
		monday = '$alert_d1',
		tuesday = '$alert_d2',
		wednesday = '$alert_d3',
		thursday = '$alert_d4',
		friday = '$alert_d5',
		saturday = '$alert_d6',
		sunday = '$alert_d7',
		recovery_notify = $alert_recovery,
		priority = $alert_priority,
		al_f2_recovery = '$campo2_rec',
		al_f3_recovery = '$campo3_rec',
		id_alerta = $tipo_alerta 
		WHERE id_aam = ".$id_aam;
	$result=mysql_query($sql_insert);	
	if (! $result) {
		echo "<h3 class='error'>".$lang_label["update_alert_no"]."</h3>";
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
	$id_prediction_server = get_parameter_post ("prediction_server", 0);
	$id_parent = get_parameter_post ("id_parent", 0);

	if ($direccion_agente != $old_agent_address){
		agent_add_address ($id_agente, $direccion_agente);
	}
	$sql_update ="UPDATE tagente 
		SET disabled = ".$disabled.", id_parent = $id_parent, id_os = ".$id_os." , modo = ".$modo." , nombre = '".$nombre_agente."', direccion = '".$direccion_agente."', id_grupo = '".$grupo."', intervalo = '".$intervalo."', comentarios = '".$comentarios."', id_network_server = '$id_network_server', id_plugin_server = $id_plugin_server, id_wmi_server = $id_wmi_server,
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
	if (give_acl($config["id_user"], $id_grupo, "AW")==1){
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
			$id_parent = $row["id_parent"];
		} else {
			echo "<h3 class='error'>".$lang_label["agent_error"]."</h3>";
			echo "</table>";
			echo "</div><div id='foot'>";
				include ("general/footer.php");
			echo "</div>";
			exit;
		}
	} else {
		audit_db($config["id_user"],$REMOTE_ADDR, "ACL Violation","Trying to admin an Agent out of admin groups!");
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
// Read ALERT data if editing alert
// ==================================

if (isset($_GET["update_alert"])){
	$id_grupo = dame_id_grupo($id_agente);
	if (give_acl($config["id_user"], $id_grupo, "LW")==0){
		audit_db($config["id_user"],$REMOTE_ADDR, "ACL Violation","Trying to update an alert without admin rights");
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
		$alerta_campo2_rec = $row["al_f2_recovery"];
		$alerta_campo3_rec = $row["al_f3_recovery"];
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
		$alert_id_agent = $row["id_agent"];
		$alert_d1 = $row["monday"];
		$alert_d2 = $row["tuesday"];
		$alert_d3 = $row["wednesday"];
		$alert_d4 = $row["thursday"];
		$alert_d5 = $row["friday"];
		$alert_d6 = $row["saturday"];
		$alert_d7 = $row["sunday"];
		$alert_recovery = $row["recovery_notify"];
		$alert_priority = $row["priority"];
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
	if (give_acl($config["id_user"], $id_grupo, "AW")==0){
		audit_db($config["id_user"],$REMOTE_ADDR, "ACL Violation","Trying to create a module without admin rights");
		require ("general/noaccess.php");
		echo "</table>";
		require ("general/footer.php");
		exit;
	}
	$form_id_tipo_modulo = get_parameter ("form_id_tipo_modulo");
	$form_name = get_parameter ("form_name");
	$form_description = get_parameter ("form_description");
	$form_id_module_group = get_parameter ("form_id_module_group",0);
	$form_flag = get_parameter ("form_flag",0);
	$form_post_process = get_parameter ("form_post_process",0);
	$form_prediction_module = get_parameter ("form_prediction_module",0);
	$form_max_timeout = get_parameter ("form_max_timeout",0);
	$form_minvalue = get_parameter_post ("form_minvalue",0);
	$form_maxvalue = get_parameter ("form_maxvalue",0);
	$form_interval = get_parameter ("form_interval",300);
	$form_id_prediction_module = get_parameter ("form_id_prediction_module",0);
	$form_id_plugin = get_parameter ("form_id_plugin",0);
	$form_id_export = get_parameter ("form_id_export",0);
	$form_disabled = get_parameter ("form_disabled",0);
	$form_tcp_send = get_parameter ("form_tcp_send","");
	$form_tcp_rcv = get_parameter ("form_tcp_rcv","");
	$form_tcp_port = get_parameter ("form_tcp_port",0);
	$form_snmp_community = get_parameter ("form_snmp_community","");
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
	SET descripcion = '$form_description', 
	nombre = '$form_name', 
	max = '$form_maxvalue', 
	min = '$form_minvalue', 
	module_interval = '$form_interval', 
	tcp_port = '$form_tcp_port', 
	tcp_send = '$form_tcp_send', 
	tcp_rcv = '$form_tcp_rcv', 
	snmp_community = '$form_snmp_community', 
	snmp_oid = '$form_snmp_oid', 
	ip_target = '$form_ip_target', 
	id_module_group = '$form_id_module_group', 
	flag = '$form_flag', 
	id_modulo = '$form_id_modulo', 
	disabled = '$form_disabled', 
	id_export = '$form_id_export', 
	plugin_user = '$form_plugin_user', 
	plugin_pass = '$form_plugin_pass', 
	plugin_parameter = '$form_plugin_parameter', 
	id_plugin = '$form_id_plugin', 
	post_process = '$form_post_process', 
	prediction_module = '$form_prediction_module', 
	max_timeout = '$form_max_timeout' 
	WHERE id_agente_modulo = '$id_agente_modulo'";
	$result=mysql_query($sql_update);
	if (! $result) {
		echo "<h3 class='error'>".$lang_label["update_module_no"]."</h3>";
	} else {
		echo "<h3 class='suc'>".$lang_label["update_module_ok"]."</h3>";
	}

}
// =========================================================
// OID Refresh button to get SNMPWALK from data in form
// This code is also applied when submitting a new module (insert_module = 1)
// =========================================================
if (isset($_POST["oid"])){
	snmp_set_quick_print(1);	
	if (! ($snmpwalk = snmprealwalk($form_ip_target, $form_snmp_community, ""))) {
		echo "<h3 class='error'>".$lang_label["cannot_read_snmp"]."</h3>";
	} else {
		echo "<h3 class='suc'>".$lang_label["ok_read_snmp"]."</h3>";
	}
}


// =========================================================
// MODULE INSERT
// =========================================================

if (((!isset($_POST["nc"]) OR ($_POST["nc"]==-1))) && (!isset($_POST["oid"])) && (isset($_POST["insert_module"])) && (isset($_POST['crtbutton']))){

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
		if (($form_id_tipo_modulo == 2) ||   // data_proc
			($form_id_tipo_modulo == 6) ||   // icmp_proc
			($form_id_tipo_modulo == 9) ||   // tcp_proc
			($form_id_tipo_modulo == 18) ||  //snmp proc
			($form_id_tipo_modulo == 21) ||  // async proc
			($form_id_tipo_modulo == 100)  // Keepalive
			) { 
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
	$id_grupo = dame_id_grupo($id_agente);	if (give_acl($config["id_user"], $id_grupo, "AW")==0){
		audit_db($config["id_user"],$REMOTE_ADDR, "ACL Violation",
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
	case "main":
		require "agent_manager.php";
		break;
	case "module":
		if (($form_moduletype == "") && ($moduletype == ""))
			require "module_manager.php";
		else
			require "module_manager_editor.php";
		break;
	case "alert":
		if (($form_alerttype == "") && (!isset($_GET["update_alert"])))
			require "alert_manager.php";
		else
			require "alert_manager_editor.php";
		break;
	case "template":
		require "agent_template.php";
		break;
}
?>
