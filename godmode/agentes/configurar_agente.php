<script language="JavaScript" type="text/javascript">
<!--
function type_change()
{
	// tipo 1-4 - Generic_xxxxxx
	if ((document.modulo.tipo.value > 0) && (document.modulo.tipo.value < 5)){
		document.modulo.snmp_oid.style.background="#ddd";
		document.modulo.snmp_oid.disabled=true;
		document.modulo.snmp_community.style.background="#ddd";
		document.modulo.snmp_community.disabled=true;
		document.modulo.tcp_send.style.background="#ddd";
		document.modulo.tcp_send.disabled=true;
		document.modulo.tcp_rcv.style.background="#ddd";
		document.modulo.tcp_rcv.disabled=true;
		document.modulo.tcp_port.style.background="#ddd";
		document.modulo.tcp_port.disabled=true;
		document.modulo.ip_target.style.background="#ddd";
		document.modulo.ip_target.disabled=true;
		document.modulo.modulo_max.style.background="#fff";
		document.modulo.modulo_max.disabled=false;
		document.modulo.modulo_min.style.background="#fff";
		document.modulo.modulo_min.disabled=false;
	}
	// tipo 15-18- SNMP
	if ((document.modulo.tipo.value > 14) && (document.modulo.tipo.value < 19 )){
		document.modulo.snmp_oid.style.background="#fff";
		document.modulo.snmp_oid.style.disabled=false;
		document.modulo.snmp_community.style.background="#fff";
		document.modulo.snmp_community.disabled=false;
		document.modulo.tcp_send.style.background="#ddd";
		document.modulo.tcp_send.disabled=true;
		document.modulo.tcp_rcv.style.background="#ddd";
		document.modulo.tcp_rcv.disabled=true;
		document.modulo.tcp_port.style.background="#ddd";
		document.modulo.tcp_port.disabled=true;
		document.modulo.ip_target.style.background="#fff";
		document.modulo.ip_target.disabled=false;
		if (document.modulo.tipo.value == 18) {
			document.modulo.modulo_max.style.background="#ddd";
			document.modulo.modulo_max.disabled=true;
			document.modulo.modulo_min.style.background="#ddd";
			document.modulo.modulo_min.disabled=true;
		} else {
			document.modulo.modulo_max.style.background="#fff";
			document.modulo.modulo_max.disabled=false;
			document.modulo.modulo_min.style.background="#fff";
			document.modulo.modulo_min.disabled=false;
		}
	}
	// tipo 6-7 - ICMP
	if ((document.modulo.tipo.value == 6) || (document.modulo.tipo.value == 7)){
		document.modulo.snmp_oid.style.background="#ddd";
		document.modulo.snmp_oid.disabled=true;
		document.modulo.snmp_community.style.background="#ddd";
		document.modulo.snmp_community.disabled=true;
		document.modulo.tcp_send.style.background="#ddd";
		document.modulo.tcp_send.disabled=true;
		document.modulo.tcp_rcv.style.background="#ddd";
		document.modulo.tcp_rcv.disabled=true;
		document.modulo.tcp_port.style.background="#ddd";
		document.modulo.tcp_port.disabled=true;
		document.modulo.ip_target.style.background="#fff";
		document.modulo.ip_target.disabled=false;
		document.modulo.modulo_max.style.background="#ddd";
		document.modulo.modulo_max.disabled=true;
		document.modulo.modulo_min.style.background="#ddd";
		document.modulo.modulo_min.disabled=true;
	}
	// tipo 8-11 - TCP
	if ((document.modulo.tipo.value > 7) && (document.modulo.tipo.value < 12)){
		document.modulo.snmp_oid.style.background="#ddd";
		document.modulo.snmp_oid.disabled=true;
		document.modulo.snmp_community.style.background="#ddd";
		document.modulo.snmp_community.disabled=true;
		document.modulo.tcp_send.style.background="#fff";
		document.modulo.tcp_send.disabled=false;
		document.modulo.tcp_rcv.style.background="#fff";
		document.modulo.tcp_rcv.disabled=false;
		document.modulo.tcp_port.style.background="#fff";
		document.modulo.tcp_port.disabled=false;
		document.modulo.ip_target.style.background="#fff";
		document.modulo.ip_target.disabled=false;
		document.modulo.modulo_max.style.background="#ddd";
		document.modulo.modulo_max.disabled=true;
		document.modulo.modulo_min.style.background="#ddd";
		document.modulo.modulo_min.disabled=true;
	}
	
	// tipo 12 - UDP
	if (document.modulo.tipo.value == 12){
		document.modulo.snmp_oid.style.background="#ddd";
		document.modulo.snmp_oid.disabled=true;
		document.modulo.snmp_community.style.background="#ddd";
		document.modulo.snmp_community.disabled=true;
		document.modulo.tcp_send.style.background="#fff";
		document.modulo.tcp_rcv.style.background="#fff";
		document.modulo.tcp_port.style.background="#fff";
		document.modulo.ip_target.style.background="#fff";
		document.modulo.modulo_max.style.background="#ddd";
		document.modulo.modulo_max.disabled=true;
		document.modulo.modulo_min.style.background="#ddd";
		document.modulo.modulo_min.disabled=true;
	}
}

//-->
</script>

<?php
// Pandora - The Free Monitoring System
// This code is protected by GPL license.
// Este codigo esta protegido por la licencia GPL.
// Sancho Lerena <slerena@gmail.com>, 2003-2006
// Raul Mateos <raulofpandora@gmail.com>, 2005-2006

// Load global vars
require("include/config.php");
if (give_acl($id_user, 0, "AW")==1) {
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
	$id_agente = "";
	$intervalo = "300";
	$id_agente = "";
	$id_server = "";
	$max_alerts = 0;
	$modo = 0;
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
	$alerta_max_alerts = "";
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
	$creacion_agente = 0;
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
		$time_threshold = entrada_limpia($_POST["time_threshold"]);
		$max_alerts = entrada_limpia($_POST["max_alerts"]);
		$min_alerts = entrada_limpia($_POST["min_alerts"]);
		$other = entrada_limpia($_POST["other"]);
		if ($time_threshold == -1) {
			$time_threshold = $other;
		}
		$sql_insert="INSERT INTO talerta_agente_modulo (id_agente_modulo,id_alerta,al_campo1,al_campo2,al_campo3,descripcion,dis_max,dis_min,time_threshold,max_alerts, min_alerts) VALUES ('".$id_agente_modulo."','".$tipo_alerta."','".$campo_1."','".$campo_2."','".$campo_3."','".$descripcion."','".$maximo."','".$minimo."','".$time_threshold."','".$max_alerts."','".$min_alerts."')";
		$result=mysql_query($sql_insert);	
		if (! $result)
			echo "<h3 class='error'>".$lang_label["create_alert_no"]."</h3>";
		else 
			$id_agente = mysql_insert_id();
			echo "<h3 class='suc'>".$lang_label["create_alert_ok"]."</h3>";
		
	}
	// Update Alert
	// =============
	if (isset($_POST["update_alert"])){ // Update an existing alert
		$id_aam = entrada_limpia($_POST["id_aam"]);
		$tipo_alerta = entrada_limpia($_POST["tipo_alerta"]);
		$id_agente_modulo = entrada_limpia($_POST["agente_modulo"]);
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
		if ($time_threshold == -1) {
			$time_threshold = $other;
		}

		$sql_insert="UPDATE talerta_agente_modulo SET id_alerta = ".$tipo_alerta.", max_alerts = '".$max_alerts."', min_alerts = '".$min_alerts."' ,time_threshold = '".$time_threshold."' ,dis_min = '".$minimo."' ,dis_max = '".$maximo."' ,al_campo3 = '".$campo_3."' ,al_campo2 = '".$campo_2."' ,al_campo1 = '".$campo_1."' , descripcion = '".$descripcion."' WHERE id_aam = ".$id_aam;
		$result=mysql_query($sql_insert);	
		if (! $result) {
			echo "<h3 class='error'>".$lang_label["update_alert_no"]."</h3>";
			// echo "SQL DEBUG  ".$sql_insert;
		}
		else 
			echo "<h3 class='suc'>".$lang_label["update_agent_ok"]."</h3>";

			$id_agente = mysql_insert_id();
	}
	
	// Create Agent
	// =============
	if (isset($_POST["create_agent"])){ // Create a new and shining agent
		$nombre_agente =  entrada_limpia($_POST["agente"]);
		$direccion_agente =  entrada_limpia($_POST["direccion"]);
		$grupo =  entrada_limpia($_POST["grupo"]);
		$intervalo =  entrada_limpia($_POST["intervalo"]);
		$comentarios =  entrada_limpia($_POST["comentarios"]);
		$modo = entrada_limpia($_POST["modo"]);
		$id_server = entrada_limpia($_POST["id_server"]);
		$os_name = entrada_limpia($_POST["os_name"]);
		$disabled = entrada_limpia($_POST["disabled"]);

		// Check if agent exists (BUG WC-50518-2 )
		$sql1='SELECT * FROM tagente WHERE nombre = "'.$nombre_agente.'"';
		$result=mysql_query($sql1);
		if ($row=mysql_fetch_array($result)){
			echo "<h3 class='error'>".$lang_label["agent_exists"]."</h3>";
			$creacion_agente = 1;
		} else { 
			if ($id_server != ""){
				$sql_insert ="INSERT INTO tagente (nombre, direccion, id_grupo, intervalo, comentarios,modo, id_os, disabled, id_server) VALUES ('".$nombre_agente."', '".$direccion_agente."', '".$grupo."', '".$intervalo."', '".$comentarios."',".$modo.", ".$os_name.", '".$disabled."',$id_server)";
			} else {
				$sql_insert ="INSERT INTO tagente (nombre, direccion, id_grupo, intervalo, comentarios,modo, id_os, disabled) VALUES ('".$nombre_agente."', '".$direccion_agente."', '".$grupo."', '".$intervalo."', '".$comentarios."',".$modo.", ".$os_name.", '".$disabled."')";
			}
			$result=mysql_query($sql_insert);	
			if (! $result)
				echo "<h3 class='error'>".$lang_label["create_agent_no"]."</h3>";
			else 
				echo "<h3 class='suc'>".$lang_label["create_agent_ok"]."</h3>";
				$id_agente = mysql_insert_id();
			// Create special MODULE agent_keepalive
			$sql_insert ="INSERT INTO tagente_modulo (nombre, id_agente, id_tipo_modulo, descripcion) VALUES ('agent_keepalive', ".$id_agente.",-1,'Agent Keepalive monitor')";		
			$result=mysql_query($sql_insert);	
			if (! $result) {
				echo "<h3 class='error'>".$lang_label["create_keep_no"]."</h3>";
			}
		}
	}
	// Update Agente
	// ================
	if (isset($_POST["update_agent"])){ // if modified some agent paramenter
		$id_agente = entrada_limpia($_POST["id_agente"]);
		$nombre_agente =  entrada_limpia($_POST["agente"]);
		$direccion_agente =  entrada_limpia($_POST["direccion"]);
		$grupo =  entrada_limpia($_POST["grupo"]);
		$intervalo =  entrada_limpia($_POST["intervalo"]);
		$comentarios =  entrada_limpia($_POST["comentarios"]);
		$modo = entrada_limpia($_POST["modo"]);
		$os_name = entrada_limpia($_POST["os_name"]);
		$id_os = $os_name; // Bug reported by Azabel, Feb/2006 :-)
		$id_server = entrada_limpia($_POST["id_server"]);
		$disabled = entrada_limpia($_POST["disabled"]);
		$sql_update ="UPDATE tagente SET disabled = ".$disabled." , id_os = ".$os_name." , modo = ".$modo." , nombre = '".$nombre_agente."', direccion = '".$direccion_agente."', id_grupo = '".$grupo."', intervalo = '".$intervalo."', comentarios = '".$comentarios."', id_server = ".$id_server." WHERE id_agente = '".$id_agente."'";
		$result=mysql_query($sql_update);
		if (! $result)
			echo "<h3 class='error'>".$lang_label["update_agent_no"]."</h3>";
		else
			echo "<h3 class='suc'>".$lang_label["update_agent_ok"]."</h3>";
	}

	// Read agent data
	// This should be at the end of all operation checks, to read the changes
	if (isset($_GET["id_agente"])){
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
				$id_server = $row["id_server"];
				$modo = $row["modo"];
				$id_os = $row["id_os"];
				$disabled=$row["disabled"];
			} else {
				echo "<h3 class='error'>".$lang_label["agent_error"]."</h3>";
					echo "</table>";
					include ("general/footer.php");
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
	if (isset($_GET["creacion"]))
		$creacion_agente = 1;

	// Read data module if editing module
	if (isset($_GET["update_module"])){
		$update_module = 1;
		$id_agente_modulo = $_GET["update_module"];

		$sql_update = "SELECT * FROM tagente_modulo WHERE id_agente_modulo = ".$id_agente_modulo;
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
			$alerta_max_alerts = $row["max_alerts"];
			$alerta_min_alerts = $row["min_alerts"];
			$alerta_time_threshold = $row["time_threshold"];
			$alerta_descripcion = $row["descripcion"];
		}

	}
	// MODULE UPDATE
	// =================
	if (isset($_POST["update_module"])){ // if modified something
		$id_grupo = dame_id_grupo($id_agente);
			if (give_acl($id_user, $id_grupo, "AW")==0){
				audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to update a module without admin rights");
				require ("general/noaccess.php");
				echo "</table>";
				require ("general/footer.php");
				exit;
			}
		if (isset($_POST["id_agente_modulo"]))
			$id_agente_modulo = entrada_limpia($HTTP_POST_VARS["id_agente_modulo"]);
		if (isset($_POST["tipo"]))
			$id_tipo_modulo = entrada_limpia($_POST["tipo"]);
		if (isset($_POST["nombre"]))
			$nombre =  entrada_limpia($_POST["nombre"]);
		if (isset($_POST["descripcion"]))
			$descripcion = entrada_limpia($_POST["descripcion"]);
		if (isset($_POST["modulo_max"]))
			$modulo_max = entrada_limpia($_POST["modulo_max"]);
		if (isset($_POST["modulo_min"]))
			$modulo_min = entrada_limpia($_POST["modulo_min"]);
		
		// Pandora 1.2 new module data:
		if (isset($_POST["tcp_send"]))
			$tcp_send = entrada_limpia($_POST["tcp_send"]);
		if (isset($_POST["tcp_rcv"]))
			$tcp_rcv = entrada_limpia($_POST["tcp_rcv"]);
		if (isset($_POST["tcp_port"]))
			$tcp_port = entrada_limpia($_POST["tcp_port"]);
		if (isset($_POST["ip_target"]))
			$ip_target = entrada_limpia($_POST["ip_target"]);
		if (isset($_POST["snmp_oid"]))
			$snmp_oid = entrada_limpia($_POST["snmp_oid"]);
		if (isset($_POST["snmp_community"]))
			$snmp_community = entrada_limpia($_POST["snmp_community"]);
		if (isset($_POST["id_module_group"]))
			$id_module_group = entrada_limpia($_POST["id_module_group"]);
		if (isset($_POST["module_interval"]))
			$module_interval = entrada_limpia($_POST["module_interval"]);

		$sql_update = "UPDATE tagente_modulo SET max ='".$modulo_max."', min = '".$modulo_min."', nombre='".$nombre."', descripcion='".$descripcion."', tcp_send = '$tcp_send', tcp_rcv = '$tcp_rcv', tcp_port = '$tcp_port', ip_target = '$ip_target', snmp_oid = '$snmp_oid', snmp_community = '$snmp_community', id_module_group = '$id_module_group', module_interval = '$module_interval'  WHERE id_agente_modulo = ".$id_agente_modulo;
		$result=mysql_query($sql_update);		
		if (! $result)
			echo "<h3 class='error'>".$lang_label["update_module_no"]."</h3>";
		else
			echo "<h3 class='suc'>".$lang_label["update_module_ok"]."</h3>";

		// Init vars to null to avoid trash in forms 
		$id_tipo_modulo = "";
		$nombre =  "";
		$descripcion = "";
		$modulo_max = "";
		$modulo_min = "";
		// Pandora 1.2 new module data:
		$tcp_send = "";
		$tcp_rcv = "";
		$tcp_port = "";
		$ip_target = "";
		$snmp_oid = "";
		$snmp_community = "";
		$id_module_group = "";
		$module_interval = "";
		
	}
	// MODULE INSERT
	// =================
	if (isset($_POST["insert_module"])){
		$id_grupo = dame_id_grupo($id_agente);
			if (give_acl($id_user, $id_grupo, "AW")==0){
				audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to create a module without admin rights");
				require ("general/noaccess.php");
				echo "</table>";
				require ("general/footer.php");
				exit;
			}
		if (isset($_POST["tipo"]))
			$id_tipo_modulo = entrada_limpia($_POST["tipo"]);
		if (isset($_POST["nombre"]))
			$nombre =  entrada_limpia($_POST["nombre"]);
		if (isset($_POST["descripcion"]))
			$descripcion = entrada_limpia($_POST["descripcion"]);
		if (isset($_POST["modulo_max"]))
			$modulo_max = entrada_limpia($_POST["modulo_max"]);
		if (isset($_POST["modulo_min"]))
			$modulo_min = entrada_limpia($_POST["modulo_min"]);
		
		// Pandora 1.2 new module data:
		if (isset($_POST["tcp_send"]))
			$tcp_send = entrada_limpia($_POST["tcp_send"]);
		if (isset($_POST["tcp_rcv"]))
			$tcp_rcv = entrada_limpia($_POST["tcp_rcv"]);
		if (isset($_POST["tcp_port"]))
			$tcp_port = entrada_limpia($_POST["tcp_port"]);
		if (isset($_POST["ip_target"]))
			$ip_target = entrada_limpia($_POST["ip_target"]);
		if (isset($_POST["snmp_oid"]))
			$snmp_oid = entrada_limpia($_POST["snmp_oid"]);
		if (isset($_POST["snmp_community"]))
			$snmp_community = entrada_limpia($_POST["snmp_community"]);
		if (isset($_POST["id_module_group"]))
			$id_module_group = entrada_limpia($_POST["id_module_group"]);
		if (isset($_POST["module_interval"]))
			$module_interval = entrada_limpia($_POST["module_interval"]);

		$sql_insert = "INSERT INTO tagente_modulo (id_agente,id_tipo_modulo,nombre,descripcion,max,min,snmp_oid,snmp_community,id_module_group,module_interval,ip_target,tcp_port,tcp_rcv,tcp_send) VALUES (".$id_agente.",".$id_tipo_modulo.",'".$nombre."','".$descripcion."','".$modulo_max."','".$modulo_min."', '$snmp_oid', '$snmp_community', '$id_module_group', '$module_interval', '$ip_target', '$tcp_port', '$tcp_rcv', '$tcp_send' )";

		// Init vars to null to avoid trash in forms 
		$id_tipo_modulo = "";
		$nombre =  "";
		$descripcion = "";
		$modulo_max = "";
		$modulo_min = "";
		// Pandora 1.2 new module data:
		$tcp_send = "";
		$tcp_rcv = "";
		$tcp_port = "";
		$ip_target = "";
		$snmp_oid = "";
		$snmp_community = "";
		$id_module_group = "";
		$module_interval = "";

		// echo "DEBUG ".$sql_insert;
		$result=mysql_query($sql_insert);
		$id_agente_modulo = mysql_insert_id();
		// Create with different estado if proc type or data type
		if (($id_tipo_modulo == 2) || ($id_tipo_modulo == 6) || ($id_tipo_modulo == 9) || ($id_tipo_modulo == 12) || ($id_tipo_modulo == 18)  ){ 
			$sql_insert = "INSERT INTO tagente_estado (id_agente_modulo,datos,timestamp,cambio,estado,id_agente) VALUES ($id_agente_modulo, 0,'2000-00-00 00:00:00',0,0,'".$id_agente."')"; }
		else { 
			$sql_insert = "INSERT INTO tagente_estado (id_agente_modulo,datos,timestamp,cambio,estado,id_agente) VALUES ($id_agente_modulo, 0,'2000-00-00 00:00:00',0,100,'".$id_agente."')";
		}
		$result2=mysql_query($sql_insert);

		if ((! $result) && (! $result2))
			echo "<h3 class='error'>".$lang_label["add_module_no"]."</h3>";
		else
			echo "<h3 class='suc'>".$lang_label["add_module_ok"]."</h3>";
	}
	// MODULE DELETION
	// =================
	if (isset($_GET["delete_module"])){ // DELETE agent module !
		$id_borrar_modulo = $_GET["delete_module"];
			$id_grupo = dame_id_grupo($id_agente);
				if (give_acl($id_user, $id_grupo, "AW")==0){
					audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to delete a module without admin rights");
					require ("general/noaccess.php");
					echo "</table>";
					require ("general/footer.php");
					exit;
				}	
		// Get information of the module to be deleted
		$sql1='SELECT * FROM tagente_modulo WHERE id_agente_modulo = '.$id_borrar_modulo;
		$result=mysql_query($sql1);
		$row=mysql_fetch_array($result);
		$id_agente = $row["id_agente"];
		$nombre_modulo = $row["nombre"];
		$id_tipo_modulo = $row["id_tipo_modulo"];
		// Primero borramos de la tabla de tagente_modulo
		$sql_delete= "DELETE FROM tagente_modulo WHERE id_agente_modulo = ".$id_borrar_modulo;
		$result=mysql_query($sql_delete);
		if (! $result)
			echo "<h3 class='error'>".$lang_label["delete_module_no"]."</h3>"; 
		else
			echo "<h3 class='suc'>".$lang_label["delete_module_ok"]."</h3>";
		// Luego borramos de la tabla de estados, las entradas que haya de este modulo
		$sql_delete = "DELETE FROM tagente_estado WHERE id_agente_modulo = ".$id_borrar_modulo;
		//echo "DEBUG SQL_DELETE $sql_delete <br>";
		$result=mysql_query($sql_delete);
		// And finally, delete all data of this module
		$sql_delete = "DELETE FROM tagente_datos WHERE id_agente_modulo = ".$id_borrar_modulo;
		//echo "DEBUG SQL_DELETE $sql_delete <br>";
		$result=mysql_query($sql_delete);
		$sql_delete = "DELETE FROM tagente_datos_string WHERE id_agente_modulo = ".$id_borrar_modulo;
		//echo "DEBUG SQL_DELETE $sql_delete <br>";
		$result=mysql_query($sql_delete);
		$sql_delete = "DELETE FROM tagente_datos_inc WHERE id_agente_modulo = ".$id_borrar_modulo;
		//echo "DEBUG SQL_DELETE $sql_delete <br>";
		$result=mysql_query($sql_delete);
		
	}
?>

<h2><?php echo $lang_label["agent_conf"]; ?><a href="help/chap3_en.php#32" target="_help"><img src="images/ayuda.gif" border="0" class="help"></a></h2>
<?php
if (isset($_GET["creacion"])){echo "<h3>".$lang_label["create_agent"]."</h3>";}
else {echo "<h3>".$lang_label["update_agent"]."</h3>";}
?>
<form name="agente" method="post" action="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente">
<?php
	if ($creacion_agente == 1)
		echo "<input type='hidden' name='create_agent' value='1'>";
	else {
		echo "<input type='hidden' name='update_agent' value='1'>";
		echo "<input type='hidden' name='id_agente' value='".$id_agente."'>";
	}
?>
<table width="650" cellpadding="3" cellspacing="3" class="fon">
<tr><th rowspan=10 width=5>
<tr><td class="datos"><b><? echo $lang_label["agent_name"]?></b><td class="datos"><input type="text" name="agente" size=30 value="<?php echo $nombre_agente ?>">
<?php 
if (isset($_GET["creacion"])){echo "&nbsp;";}
else {echo "&nbsp;&nbsp;<a href='index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=".$id_agente."'><img src='images/lupa.gif' border='0' align='middle'></a>";} 
?>
<tr><td class="datos"><b><? echo $lang_label["ip_address"]?></b><td class="datos"><input type="text" name="direccion" size=30 value="<?php echo $direccion_agente ?> ">
<!-- Desplegable para el grupo -->
<tr><td class="datos"><b><? echo $lang_label["group"]?></b><td class="datos"><select name="grupo" class="w130"> 
<?php
if (isset($grupo)){
echo "<option value='".$grupo."'>".dame_grupo($grupo);
}
$sql1='SELECT * FROM tgrupo ORDER BY nombre';
$result=mysql_query($sql1);
while ($row=mysql_fetch_array($result)){
	// Group ALL cannot be selected
	if ($row["id_grupo"] != 1){
		echo "<option value='".$row["id_grupo"]."'>".$row["nombre"];
	}
}
?>
</select>
<tr><td class="datos"><b><? echo $lang_label["interval"]?></b></td><td class="datos"><input type="text" name="intervalo" size="15" value="<?php echo $intervalo?>"></td>
<tr><td class="datos"><b><? echo $lang_label["os"]?></b></td><td class="datos">
<select name="os_name" class="w130">
<?php
if (isset($id_os)){
	echo "<option value='".$id_os."'>".dame_so_name($id_os);
}
$sql1='SELECT * FROM tconfig_os ORDER BY name';
$result=mysql_query($sql1);
while ($row=mysql_fetch_array($result)){
	echo "<option value='".$row["id_os"]."'>".$row["name"];
}
?>
</select>

<tr><td class="datos"><b><? echo $lang_label["server"]?></b></td><td class="datos">
<select name="id_server" class="w130">
<?php
echo "<option value='".$id_server."'>".give_server_name($id_server);
$sql1='SELECT * FROM tserver ORDER BY name';
$result=mysql_query($sql1);
while ($row=mysql_fetch_array($result)){
	echo "<option value='".$row["id_server"]."'>".$row["name"];
}

?>
</select>


<tr><td class="datos"><b><? echo $lang_label["description"]?></b><td class="datos"><input type="text" name="comentarios" size="55" value="<?php echo $comentarios ?>"></td>
<tr><td class="datos"><b><? echo $lang_label["module_definition"]?></b>
<td class="datos">
	<?php if ($modo == "1"){
		echo $lang_label["learning_mode"].'<input type="radio" class="chk" name="modo" value="1" checked >';
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$lang_label["normal_mode"].' <input type="radio" class="chk" name="modo" value="0">';
	} else {
		echo $lang_label["learning_mode"].'<input type="radio" class="chk" name="modo" value="1">';
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$lang_label["normal_mode"].'<input type="radio" name="modo" class="chk" value="0" checked>';
	}
	?>
<tr><td class="datos"><b><? echo $lang_label["status"]?></b>
<td class="datos">
<?php if ($disabled == "1"){
		echo $lang_label["disabled"].'<input type="radio" class="chk"  name="disabled" value="1" checked>';
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$lang_label["active"].' <input class="chk" type="radio" name="disabled" value="0">';
	} else {
		echo $lang_label["disabled"].'<input type="radio" class="chk" name="disabled" value="1">';
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$lang_label["active"].'<input type="radio" name="disabled" class="chk" value="0" checked>';
	}
?>
<!-- <tr><td colspan='3'><div class='raya'></div></td></tr> -->
<tr><td colspan="3" align="right">
<?php if (isset($_GET["creacion"])){echo "<input name='crtbutton' type='submit' class='sub' value='".$lang_label["create"]."'>";}
else {echo "<input name='uptbutton' type='submit' class='sub' value='".$lang_label["update"]."'>";} ?></td>
</form>
</table>

<?php 
// Visualizacion de modulos y otras especies !!!, solo cuando no estamos en modo creacion
if ( $creacion_agente != 1) {
// MODULE VISUALIZACION
// ======================
// Load icon index for ttipo_modulo
	$iconindex[]="";

	$sql_t='SELECT * FROM ttipo_modulo';
	$result_t=mysql_query($sql_t);
	while ($row_t=mysql_fetch_array($result_t)){
		$iconindex[$row_t["id_tipo"]] = $row_t["icon"];
	}
$sql1='SELECT * FROM tagente_modulo WHERE id_agente = "'.$id_agente.'"';
$result=mysql_query($sql1);
if ($row=mysql_num_rows($result)){
	?>
	<h3><?php echo $lang_label["assigned_modules"]?> </h3>
	<table width="700" cellpadding="3" cellspacing="3" class="fon">
	<tr>
	<th><?php echo $lang_label["module_name"]?>
	<th><?php echo $lang_label["type"]?>
	<th><?php echo $lang_label["interval"]?>
	<th><?php echo $lang_label["description"]?>
	<th><?php echo $lang_label["module_group"]?>
	<th><?php echo $lang_label["max_min"]?>
	<th width="50"><?php echo $lang_label["action"]?>
	<?php
while ($row=mysql_fetch_array($result)){

	$id_tipo = $row["id_tipo_modulo"];
	$nombre_modulo =$row["nombre"];
	$descripcion = $row["descripcion"];
	$module_max = $row["max"];
	$module_min = $row["min"];
	$module_interval2 = $row["module_interval"];
	$module_group2 = $row["id_module_group"];
	
	echo "<tr><td class='datos_id'>".$nombre_modulo;
	echo "<td class='datosf9'>";
	if ($id_tipo > 0) {
		echo "<img src='images/".$iconindex[$id_tipo]."' border=0>";
	}
	echo "<td class='datos'>".$module_interval2;
	echo "<td class='datos'>".substr($descripcion,0,30);
	echo "<td class='datos'>".substr(dame_nombre_grupomodulo($module_group2),0,15);
	echo "<td class='datos'>";
	if ($module_max == $module_min) {
		$module_max = "N/A";
		$module_min = "N/A";
	}
	echo $module_max." / ".$module_min;
	echo "<td class='datos'>";
	if ($id_tipo != -1)
		echo "<a href='index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&id_agente=".$id_agente."&delete_module=".$row["id_agente_modulo"]."'><img src='images/cancel.gif' border=0 alt='".$lang_label["delete"]."'></b></a> &nbsp; ";
	echo "<a href='index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&id_agente=".$id_agente."&update_module=".$row["id_agente_modulo"]."#modules'><img src='images/config.gif' border=0 alt='".$lang_label["update"]."'></b></a>";
}
	echo "<tr><td colspan='7'><div class='raya'></div></td></tr>";
}else echo "<font class='red'>No modules</font>";
?>
</table>

<?php
// ====================================================================================
// View alerts
// ====================================================================================<br>


$sql1='SELECT * FROM tagente_modulo WHERE id_agente = "'.$id_agente.'"';
$result=mysql_query($sql1);
if ($row=mysql_num_rows($result)){
?>
<h3><?php echo $lang_label["assigned_alerts"]?></h3>

<table cellpadding="3" cellspacing="3" width="700" class="fon">
<tr>
<th><?php echo $lang_label["name_type"] ?>
<th><?php echo $lang_label["alert"] ?>
<th><?php echo $lang_label["time_threshold"] ?>
<th><?php echo $lang_label["min_max"] ?>
<th><?php echo $lang_label["description"] ?>
<th width="50"><?php echo $lang_label["action"] ?>
<?php
while ($row=mysql_fetch_array($result)){  // All modules of this agent
	$id_tipo = $row["id_tipo_modulo"];
	$nombre_modulo =$row["nombre"];
	$sql2='SELECT * FROM ttipo_modulo WHERE id_tipo = "'.$id_tipo.'"';
	$result2=mysql_query($sql2);
	$row2=mysql_fetch_array($result2);
	//module type modulo is $row2["nombre"];
	
	$sql3='SELECT * FROM talerta_agente_modulo WHERE id_agente_modulo = '.$row["id_agente_modulo"];  // From all the alerts give me which are to my agent
	$result3=mysql_query($sql3);
	while ($row3=mysql_fetch_array($result3)){
		$sql4='SELECT * FROM talerta WHERE id_alerta = '.$row3["id_alerta"];
		$result4=mysql_query($sql4);
		$row4=mysql_fetch_array($result4);
		// Alert name defined by  $row4["nombre"]; 
		$tipo_modulo = $row2["nombre"];
		$nombre_alerta = $row4["nombre"];
		echo "<tr><td class=datos>";
		echo $nombre_modulo."/".$tipo_modulo;
		echo "<td class=datos>";
		echo $nombre_alerta;
		echo "<td class=datos>";
		echo $row3["time_threshold"];
		echo "<td class=datos>";
		echo $row3["dis_min"]."/".$row3["dis_max"];
		echo "<td class=datos>";
		echo salida_limpia($row3["descripcion"]);
		echo "<td class=datos>";
	 	$id_grupo = dame_id_grupo($id_agente);
		if (give_acl($id_user, $id_grupo, "LW")==1){
			echo "<a href='index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&id_agente=".$id_agente."&delete_alert=".$row3["id_aam"]."'><img src='images/cancel.gif' border=0 alt='".$lang_label["delete"]."'></a>  &nbsp; ";
			echo "<a href='index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&id_agente=".$id_agente."&update_alert=".$row3["id_aam"]."#alerts'><img src='images/config.gif' border=0 alt='".$lang_label["update"]."'></a>";		
		}
	}
}
echo "<tr><td colspan='6'><div class='raya'></div></td></tr>";
}else echo "<font class='red'>No modules</font>";
?>
</table>
<br>
<?php
echo '<form name="modulo" method="post" action="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&id_agente='.$id_agente.'">';
// ====================================================================================
// Module Creation / Update form
// ====================================================================================

if ($update_module == "1"){
	echo '<input type="hidden" name="update_module" value=1>';
	echo '<input type="hidden" name="id_agente_modulo" value="'.$id_agente_modulo.'" ';
}
else
	echo '<input type="hidden" name="insert_module" value=1>';
?>
<h3><?php echo $lang_label["module_asociation_form"] ?></h3>
<a name="modules"> <!-- Don't Delete !! -->
<table width="650" cellpadding="3" cellspacing="3" class="fon">
<tr><th rowspan=8 width=5>
<!-- Module type combobox -->
<tr>
<td class="datos"><?php echo $lang_label["module_type"] ?>
<td class="datos">
<?php
if ($update_module == "1")
	echo "<i class='red'>".$lang_label["no_change_field"]."</i>";
else {
	echo '<select name="tipo" onChange="type_change()">';
	$sql1='SELECT * FROM ttipo_modulo ORDER BY nombre';
	$result=mysql_query($sql1);
	echo "<option>--</option>";
	while ($row=mysql_fetch_array($result)){
		echo "<option value='".$row["id_tipo"]."'>".$row["nombre"];
	}
	echo "</select>";
}
?>
</select>

<!-- Module group selection -->
<td class="datos"><?php echo $lang_label["module_group"] ?>
<td class="datos">
<?php
	echo '<select name="id_module_group">';
	if ($update_module == "1"){
		echo "<option value='".$id_module_group."'>".dame_nombre_grupomodulo($id_module_group);
	}
	$sql1='SELECT * FROM tmodule_group';
	$result=mysql_query($sql1);
	while ($row=mysql_fetch_array($result)){
		echo "<option value='".$row["id_mg"]."'>".$row["name"];
	}
	echo "</select>";
?>
</select>

<tr><td class="datos"><?php echo $lang_label["module_name"] ?>
<td class="datos"><input type="text" name="nombre" size="20" value="<?php echo $modulo_nombre ?>"> 
<td class="datos"><?php echo $lang_label["module_interval"] ?><td class="datos">
<input type="text" name="module_interval" size="5" value="<?php echo $module_interval ?>"> 

<tr><td class="datos"><?php echo $lang_label["ip_target"] ?>
<td class="datos"><input type="text" name="ip_target" size="20" value="<?php echo $ip_target ?>"> 
<td class="datos"><?php echo $lang_label["tcp_port"] ?>
<td class="datos"><input type="text" name="tcp_port" size="5" value="<?php echo $tcp_port ?>"> 

<tr><td class="datos"><?php echo $lang_label["snmp_oid"] ?>
<td class="datos"><input type="text" name="snmp_oid" size="20" value="<?php echo $snmp_oid ?>"> 
<td class="datos"><?php echo $lang_label["snmp_community"] ?>
<td class="datos"><input type="text" name="snmp_community" size="20" value="<?php echo $snmp_community ?>"> 


<tr><td class="datost"><?php echo $lang_label["tcp_send"] ?>
<td class="datos"><textarea name="tcp_send" cols="17" rows="3"><?php echo $tcp_send ?></textarea>

<td class="datost"><?php echo $lang_label["tcp_rcv"] ?>
<td class="datos"><textarea name="tcp_rcv" cols="17" rows="3"><?php echo $tcp_rcv ?></textarea>

<tr><td class="datos"><?php echo $lang_label["mindata"] ?>
<td class="datos"><input type="text" name="modulo_min" size="5" value="<?php echo $modulo_min ?>"> 
<td class="datos"><?php echo $lang_label["maxdata"] ?>
<td class="datos"><input type="text" name="modulo_max" size="5" value="<?php echo $modulo_max ?>">

<tr><td class="datost"><?php echo $lang_label["comments"] ?>
<td class="datos" colspan=3>
<textarea name="descripcion" cols=52 rows=2>
<?php echo $modulo_descripcion ?>
</textarea>
<!--<tr><td colspan='5'><div class='raya'></div></td></tr>-->
<tr><td colspan="5" align="right">
<?php 
	if ($update_module == "1"){
		echo '<input name="updbutton" type="submit" class="sub" value="'.$lang_label["update"].'">';
	} else {
		echo '<input name="crtbutton" type="submit" class="sub" value="'.$lang_label["add"].'">';
	}
?>
</form>
</table>
<br>
<?php
	$id_grupo = dame_id_grupo($id_agente);
	if (give_acl($id_user, $id_grupo, "LW")==1){
?>

<!-- Alert Configuration -->
<h3><?php echo $lang_label["alert_asociation_form"] ?></h3>


<?php
// ==================================================================================
// Add alerts
// ==================================================================================
echo '<form name="agente" method="post" action="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&id_agente='.$id_agente.'">';
if (! isset($update_alert))
	$update_alert = -1;
if ($update_alert != 1)
	echo '<input type="hidden" name="insert_alert" value=1>';
else {
	echo '<input type="hidden" name="update_alert" value=1>';
	echo '<input type="hidden" name="id_aam" value="'.$alerta_id_aam.'"';
}
?>
<input type="hidden" name="id_agente" value="<?php echo $id_agente ?>">
<table width=650 cellpadding="3" cellspacing="3" class="fon" border=0>
<tr><th rowspan=10 width=5>
<tr>
<td class="datos"><?php echo $lang_label["alert_type"]?>
<td class="datos">
<select name="tipo_alerta"> 
<?php
$sql1='SELECT * FROM talerta ORDER BY nombre';
if (isset($tipo_alerta)){
	echo "<option value='".$tipo_alerta."'>".dame_nombre_alerta($tipo_alerta);
}
$result=mysql_query($sql1);
while ($row=mysql_fetch_array($result)){
	echo "<option value='".$row["id_alerta"]."'>".$row["nombre"];
}
?>
</select>
<a name="alerts"> <!-- Don't Delete !! -->

<tr><td class="datos"><?php echo $lang_label["min_value"] ?>
<td class="datos"><input type="text" name="minimo" size="5" value="<?php echo $alerta_dis_max ?>">
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $lang_label["max_value"] ?> &nbsp;&nbsp;&nbsp;
<input type="text" name="maximo" size="5" value="<?php echo $alerta_dis_min ?>">
<tr><td class="datos"><?php echo $lang_label["description"] ?>
<td class="datos"><input type="text" name="descripcion" size="39" value ="<?php echo $alerta_descripcion ?>">
<tr><td class="datos"><?php echo $lang_label["field1"] ?>
<td class="datos"><input type="text" name="campo_1" size="39" value="<?php echo $alerta_campo1 ?>">
<tr><td class="datos"><?php echo $lang_label["field2"] ?>
<td class="datos"><input type="text" name="campo_2" size="39" value="<?php echo $alerta_campo2 ?>">
<tr><td class="datos"><?php echo $lang_label["field3"] ?>
<td class="datos"><textarea name="campo_3" cols="36" rows="3"><?php echo $alerta_campo3 ?></textarea>
<tr><td class="datos"><?php echo $lang_label["time_threshold"] ?>
<td class="datos"><select name="time_threshold">
<?PHP
	if ($alerta_time_threshold != ""){ 
		echo "<option value='".$alerta_time_threshold."'>".give_human_time($alerta_time_threshold)."</option>";
	}
?>
<option value=300>5 Min.
<option value=600>10 Min
<option value=900>15 min
<option value=1800>30 Min
<option value=3600>1 Hour
<option value=7200>2 Hour
<option value=18000>5 Hour
<option value=43200>12 Hour
<option value=86400>1 Day
<option value=604800>1 Week
<option value=-1>Other value
</select>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<?php echo $lang_label["other"] ?>
&nbsp;&nbsp;&nbsp;
<input type="text" name="other" size="5">

<tr><td class="datos"><?php echo $lang_label["min"]." ".$lang_label["alerts"] ?>
<td class="datos">
<input type="text" name="mix_alerts" size="5" value="<?php  if (isset($alerta_min_alerts)) {echo$alerta_min_alerts;} ?>">
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<?php echo $lang_label["max"]." ".$lang_label["alerts"] ?>
&nbsp;&nbsp;&nbsp;
<input type="text" name="max_alerts" size="5" value="<?php if (isset($alerta_max_alerts)) {echo $alerta_max_alerts;} ?>">


<tr><td class="datos"><?php echo $lang_label["assigned_module"] ?>
<td class="datos">
<?php if ($update_alert != 1) {
	echo '<select name="agente_modulo"> ';
	$sql2='SELECT * FROM tagente_modulo WHERE id_agente = '.$id_agente;
	$result2=mysql_query($sql2);
	while ($row2=mysql_fetch_array($result2)){
		if ($row2["id_tipo_modulo"] != -1) {
			$sql1='SELECT * FROM ttipo_modulo WHERE id_tipo = '.$row2["id_tipo_modulo"];
			$result=mysql_query($sql1);
			while ($row=mysql_fetch_array($result)){
				echo "<option value='".$row2["id_agente_modulo"]."'>".$row["nombre"]."/".$row2["nombre"];
			}
		} else // for -1, is a special module, keep alive monitor !!
			echo "<option value='".$row2["id_agente_modulo"]."'>".$row2["nombre"];
	}
	echo "</select>";
} else {
	echo "<i class='red'>".$lang_label["no_change_field"]."</i>";
}

 // End block only if $creacion_agente != 1;

// echo '<tr><td colspan="3"><div class="raya"></div></td></tr>';
echo '<tr><td colspan="3" align="right">';
	if ($update_alert== "1"){
		echo '<input name="updbutton" type="submit" class="sub" value="'.$lang_label["update"].'">';
	} else {
		echo '<input name="crtbutton" type="submit" class="sub" value="'.$lang_label["add"].'">';
	}
echo '</td></tr></table></form>';
}
}
	} // end page
else {
		audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access Agent Management");
		require ("general/noaccess.php");
	}
?>