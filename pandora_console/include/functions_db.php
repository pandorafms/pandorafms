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

function check_login() { 
    global $config;
    if (!isset($config["homedir"])){
        // No exists $config. Exit inmediatly
        include ("general/noaccess.php");
        exit;
    }
    if ((isset($_SESSION["id_usuario"])) AND ($_SESSION["id_usuario"] != "")) { 
        $id = $_SESSION["id_usuario"];
        $query1="SELECT id_usuario FROM tusuario WHERE id_usuario= '$id'";
        $resq1 = mysql_query($query1);
        $rowdup = mysql_fetch_array($resq1);
        $nombre = $rowdup[0];
        if ( $id == $nombre ){
                return 0 ;
        }
    }
    audit_db("N/A", getenv("REMOTE_ADDR"), "No session", "Trying to access without a valid session");
    include ($config["homedir"]."/general/noaccess.php");
    exit;
}

// --------------------------------------------------------------- 
// give_acl ()
// Main Function to get access to resources
// Return 0 if no access, > 0  if access
// --------------------------------------------------------------- 

function give_acl($id_user, $id_group, $access){	
	// IF user is level = 1 then always return 1
	// Access can be:
	/*	
		IR - Incident Read
		IW - Incident Write
		IM - Incident Management
		AR - Agent Read
		AW - Agent Write
		LW - Alert Write
		UM - User Management
		DM - DB Management
		LM - Alert Management
		PM - Pandora Management
	*/
	
	// Conexion con la base Datos 
	require("config.php");
	$query1="SELECT * FROM tusuario WHERE id_usuario = '".$id_user."'";
	$res=mysql_query($query1);
	$row=mysql_fetch_array($res);
	if ($row["nivel"] == 1)
		$result = 1;
	else {
		if ($id_group == 0) // Group doesnt matter, any group, for check permission to do at least an action in a group
			$query1="SELECT * FROM tusuario_perfil WHERE id_usuario = '".$id_user."'";	// GroupID = 0, group doesnt matter (use with caution!)
		else
			$query1="SELECT * FROM tusuario_perfil WHERE id_usuario = '".$id_user."' and ( id_grupo =".$id_group." OR id_grupo = 1)";	// GroupID = 1 ALL groups      
		$resq1=mysql_query($query1);  
		$result = 0; 
		while ($rowdup=mysql_fetch_array($resq1)){
			$id_perfil=$rowdup["id_perfil"];
			// For each profile for this pair of group and user do...
			$query2="SELECT * FROM tperfil WHERE id_perfil = ".$id_perfil;    
			$resq2=mysql_query($query2);  
			if ($rowq2=mysql_fetch_array($resq2)){
				switch ($access) {
					case "IR": $result = $result + $rowq2["incident_view"]; break;
					case "IW": $result = $result + $rowq2["incident_edit"]; break;
					case "IM": $result = $result + $rowq2["incident_management"]; break;
					case "AR": $result = $result + $rowq2["agent_view"]; break;
					case "AW": $result = $result + $rowq2["agent_edit"]; break;
					case "LW": $result = $result + $rowq2["alert_edit"]; break;
					case "LM": $result = $result + $rowq2["alert_management"]; break;
					case "PM": $result = $result + $rowq2["pandora_management"]; break;
					case "DM": $result = $result + $rowq2["db_management"]; break;
					case "UM": $result = $result + $rowq2["user_management"]; break;
				}
			} 
		}
	} // else
	if ($result > 1)
		$result = 1;
        return $result; 
} 

// --------------------------------------------------------------- 
// audit_db, update audit log
// --------------------------------------------------------------- 

function audit_db ($id, $ip, $accion, $descripcion){
	require("config.php");
	$today=date('Y-m-d H:i:s');
	$utimestamp = time();
	$sql1='INSERT INTO tsesion (ID_usuario, accion, fecha, IP_origen,descripcion, utimestamp) VALUES ("'.$id.'","'.$accion.'","'.$today.'","'.$ip.'","'.$descripcion.'", '.$utimestamp.')';
	$result=mysql_query($sql1);
}


// --------------------------------------------------------------- 
// logon_db, update entry in logon audit
// --------------------------------------------------------------- 

function logon_db($id,$ip){
	require("config.php");
	audit_db($id,$ip,"Logon","Logged in");
	// Update last registry of user to get last logon
	$sql2='UPDATE tusuario fecha_registro = $today WHERE id_usuario = "$id"';
	$result=mysql_query($sql2);
}

// --------------------------------------------------------------- 
// logoff_db, also adds audit log
// --------------------------------------------------------------- 

function logoff_db($id,$ip){
	require("config.php");
	audit_db($id,$ip,"Logoff","Logged out");
}

// --------------------------------------------------------------- 
// Returns profile given ID
// --------------------------------------------------------------- 

function dame_perfil($id){ 
	require("config.php");
	$query1="SELECT * FROM tperfil WHERE id_perfil =".$id;
	$resq1=mysql_query($query1);  
	if ($rowdup=mysql_fetch_array($resq1)){
		$cat=$rowdup["name"]; 
	}
		else $cat = "";
	return $cat; 
}

// ---------------------------------------------------------------
// Returns disabled from a given group_id
// ---------------------------------------------------------------

function give_disabled_group($id){
        require("config.php");
        $query1="SELECT * FROM tgrupo WHERE id_grupo =".$id;
        $resq1=mysql_query($query1);
        if ($rowdup=mysql_fetch_array($resq1)){
                $cat=$rowdup["disabled"];
        }
                else $cat = "";
        return $cat;
}



// --------------------------------------------------------------- 
// Returns group given ID
// --------------------------------------------------------------- 

function dame_grupo($id){ 
	require("config.php");
	$query1="SELECT * FROM tgrupo WHERE id_grupo =".$id;
	$resq1=mysql_query($query1);
	if ($rowdup=mysql_fetch_array($resq1)){
		$cat=$rowdup["nombre"];
	}
		else $cat = "";
	return $cat; 
}

// --------------------------------------------------------------- 
// Returns icon name given group ID
// --------------------------------------------------------------- 

function dame_grupo_icono($id){
	require("config.php");
	$query1="SELECT * FROM tgrupo WHERE id_grupo =".$id;
	$resq1=mysql_query($query1);
	if ($rowdup=mysql_fetch_array($resq1)){
		$cat=$rowdup["icon"];
	}
		else $cat = "";
	return $cat;
}

// --------------------------------------------------------------- 
// Return agent id given name of agent
// --------------------------------------------------------------- 

function dame_agente_id($nombre){
	require("config.php");
	$query1="SELECT * FROM tagente WHERE nombre = '".$nombre."'";
	$resq1=mysql_query($query1);
	if ($rowdup=mysql_fetch_array($resq1))
		$pro=$rowdup["id_agente"];
	else
		$pro = "";
	return $pro;
}


// --------------------------------------------------------------- 
// Returns userid given name an note id
// --------------------------------------------------------------- 

function give_note_author ($id_note){ 
	require("config.php");
	$query1="SELECT * FROM tnota WHERE id_nota = ".$id_note;
	$resq1=mysql_query($query1);
	if ($rowdup=mysql_fetch_array($resq1))
		$pro=$rowdup["id_usuario"];
	else
		$pro = "";
	return $pro;
}


// --------------------------------------------------------------- 
// Returns agent id given name of agent
// --------------------------------------------------------------- 

function dame_agente_modulo_id($id_agente, $id_tipomodulo, $nombre){
	require("config.php");
	$query1="SELECT * FROM tagente_modulo WHERE id_agente = ".$id_agente." and id_tipo_modulo = ".$id_tipomodulo." and nombre = '".$nombre."'";
	$resq1=mysql_query($query1);
	if ($rowdup=mysql_fetch_array($resq1))
		$pro=$rowdup["id_agente_modulo"];
	else
		$pro = "";
	return $pro;
}


// --------------------------------------------------------------- 
// Returns event description given it's id
// --------------------------------------------------------------- 

function return_event_description ($id_event){
	require("config.php");
	$query1="SELECT evento FROM tevento WHERE id_evento = $id_event";
	$resq1=mysql_query($query1);
	if ($rowdup=mysql_fetch_array($resq1))
		$pro=$rowdup[0];
	else
		$pro = "";
	return $pro;
}

// --------------------------------------------------------------- 
// Return ID_Group from an event given as id_event
// --------------------------------------------------------------- 

function gime_idgroup_from_idevent($id_event){
	require("config.php");
	$query1="SELECT * FROM tevento WHERE id_evento = ".$id_event;
	$pro = -1;
	if ($resq1=mysql_query($query1))
		if ($rowdup=mysql_fetch_array($resq1))
			$pro=$rowdup["id_grupo"]; 
	return $pro;
}


// --------------------------------------------------------------- 
// Return module id given name of module type
// --------------------------------------------------------------- 

function dame_module_id($nombre){
	require("config.php"); 
	$query1="SELECT * FROM ttipo_modulo WHERE nombre = '".$nombre."'"; 
	$resq1=mysql_query($query1);  
	if ($rowdup=mysql_fetch_array($resq1))
		$pro=$rowdup["id_tipo"]; 
	else
		$pro = "";
	return $pro; 
}


// --------------------------------------------------------------- 
// Returns agent name when given its ID
// --------------------------------------------------------------- 

function dame_nombre_agente ($id){
	require("config.php"); 
	$query1="SELECT * FROM tagente WHERE id_agente = ".$id; 
	$resq1=mysql_query($query1);  
	if ($rowdup=mysql_fetch_array($resq1))
		$pro=$rowdup["nombre"]; 
	else
		$pro = "";
	return $pro;
}

// --------------------------------------------------------------- 
// Returns password (HASH) given user_id
// --------------------------------------------------------------- 

function dame_password($id_usuario){
	require("config.php"); 
	$query1="SELECT * FROM tusuario WHERE id_usuario= '".$id_usuario."'"; 
	$resq1=mysql_query($query1);  
	if ($rowdup=mysql_fetch_array($resq1))
		$pro=$rowdup["password"]; 
	else
		$pro = "";
	return $pro; 
}


// --------------------------------------------------------------- 
// Returns name of an alert given ID
// --------------------------------------------------------------- 

function dame_nombre_alerta($id){
	require("config.php");
	$query1="SELECT * FROM talerta WHERE id_alerta = ".$id;
	$resq1=mysql_query($query1);
	if ($rowdup=mysql_fetch_array($resq1))
		$pro=$rowdup["nombre"]; 
	else
		$pro = "";
	return $pro;
}

// --------------------------------------------------------------- 
// Returns name of a modules group
// --------------------------------------------------------------- 

function dame_nombre_grupomodulo($id){
	require("config.php");
	$query1="SELECT * FROM tmodule_group WHERE id_mg = ".$id; 
	$resq1=mysql_query($query1);
	if ($rowdup=mysql_fetch_array($resq1))
		$pro=$rowdup["name"]; 
	else
		$pro = "";
	return $pro;
}

// --------------------------------------------------------------- 
// Returns name of a export server
// --------------------------------------------------------------- 

function dame_nombre_servidorexportacion($id){
	require("config.php");
	$query1="SELECT id, name FROM tserver_export WHERE id = ".$id; 
	$resq1=mysql_query($query1);
	if ($rowdup=mysql_fetch_array($resq1))
		$pro=$rowdup["name"]; 
	else
		$pro = "";
	return $pro;
}

// --------------------------------------------------------------- 
// Returns name of a plugin module
// --------------------------------------------------------------- 

function dame_nombre_pluginid($id){
	require("config.php");
	$query1="SELECT id, name FROM tplugin WHERE id = ".$id; 
	$resq1=mysql_query($query1);
	if ($rowdup=mysql_fetch_array($resq1))
		$pro=$rowdup["name"]; 
	else
		$pro = "";
	return $pro;
}

// --------------------------------------------------------------- 
// Returns id of a moduletype
// --------------------------------------------------------------- 

function giveme_module_type($id){
	require("config.php");
	$query1="SELECT id_tipo, nombre FROM ttipo_modulo WHERE id_tipo = ".$id; 
	$resq1=mysql_query($query1);
	if ($rowdup=mysql_fetch_array($resq1))
		$pro=$rowdup["nombre"]; 
	else
		$pro = "";
	return $pro;
}

// --------------------------------------------------------------- 
// Returns agent name, given a ID of agente_module table
// --------------------------------------------------------------- 

function dame_nombre_agente_agentemodulo($id_agente_modulo){
	require("config.php");
	$query1="SELECT * FROM tagente_modulo WHERE id_agente_modulo = ".$id_agente_modulo;
	$resq1=mysql_query($query1);
	if ($rowdup=mysql_fetch_array($resq1))
		$pro = dame_nombre_agente($rowdup["id_agente"]);
	else
		$pro = "";
	return $pro;
}

// --------------------------------------------------------------- 
// Return agent module name, given a ID of agente_module table
// --------------------------------------------------------------- 

function dame_nombre_modulo_agentemodulo($id_agente_modulo){
	require("config.php"); 
	$query1="SELECT * FROM tagente_modulo WHERE id_agente_modulo = ".$id_agente_modulo; 
	$resq1=mysql_query($query1);
	if ($rowdup=mysql_fetch_array($resq1))
		$pro = $rowdup["nombre"];
	else
		$pro = "";
	return $pro;
}


// --------------------------------------------------------------- 
// Return agent module, given a ID of agente_module table
// --------------------------------------------------------------- 

function dame_id_tipo_modulo_agentemodulo($id_agente_modulo){
	require("config.php"); 
	$query1="SELECT * FROM tagente_modulo WHERE id_agente_modulo = ".$id_agente_modulo; 
	$resq1=mysql_query($query1);
	if ($rowdup=mysql_fetch_array($resq1))
		$pro = $rowdup["id_tipo_modulo"];
	else
		$pro = "";
	return $pro;
}

// --------------------------------------------------------------- 
// Returns name of the user when given ID
// --------------------------------------------------------------- 

function dame_nombre_real($id){
	require("config.php");
	$query1="SELECT * FROM tusuario WHERE id_usuario = '".$id."'";
	$resq1=mysql_query($query1);
	if ($rowdup=mysql_fetch_array($resq1))
		$pro=$rowdup["nombre_real"];
	else
		$pro = "";
	return $pro;
}


// --------------------------------------------------------------- 
// This function returns ID of user who has created incident
// --------------------------------------------------------------- 

function give_incident_author($id){
	require("include/config.php");
	$query1="SELECT * FROM tincidencia WHERE id_incidencia = '".$id."'";
	$resq1=mysql_query($query1);
	if ($rowdup=mysql_fetch_array($resq1))
		$pro=$rowdup["id_usuario"];
	else
		$pro = "";
	return $pro;
}

// --------------------------------------------------------------- 
// This function returns name of server
// --------------------------------------------------------------- 

function give_server_name($id_server){
	require("include/config.php");
	$query1="SELECT * FROM tserver WHERE id_server  = '".$id_server."'";
	$resq1=mysql_query($query1);  
	if ($rowdup=mysql_fetch_array($resq1))
		$pro=$rowdup["name"];
	else
		$pro = "";
	return $pro;
}

// --------------------------------------------------------------- 
// Return name of a module type when given ID
// --------------------------------------------------------------- 

function dame_nombre_tipo_modulo ($id){
	require("config.php");
	$query1="SELECT * FROM ttipo_modulo WHERE id_tipo =".$id;
	$resq1=mysql_query($query1);
	if ($rowdup=mysql_fetch_array($resq1)){
		$pro=$rowdup["nombre"];
	}
	else $pro = "";
	return $pro;
} 

// --------------------------------------------------------------- 
// Return name of a group when given ID
// --------------------------------------------------------------- 

function dame_nombre_grupo ($id){
	require ("config.php");
	$query1 = "SELECT * FROM tgrupo WHERE id_grupo = ".$id;
	$resq1 = mysql_query($query1);
	if ($rowdup = mysql_fetch_array ($resq1))
		$pro = $rowdup["nombre"];
	else
		$pro = "";
	return $pro;
} 

// --------------------------------------------------------------- 
// This function return group_id given an agent_id
// --------------------------------------------------------------- 

function dame_id_grupo($id_agente){
	require("config.php");
	$query1="SELECT * FROM tagente WHERE id_agente =".$id_agente;
	$resq1=mysql_query($query1);
	if ($rowdup=mysql_fetch_array($resq1)){
		$pro=$rowdup["id_grupo"];
	}
	else $pro = "";
	return $pro;
} 


// --------------------------------------------------------------- 
// Returns number of notes from a given incident
// --------------------------------------------------------------- 

function dame_numero_notas($id){
	require("config.php"); 
	$query1="select COUNT(*) from tnota_inc WHERE id_incidencia =".$id;
	$resq1=mysql_query($query1);
	if ($rowdup=mysql_fetch_array($resq1)){
		$pro=$rowdup["COUNT(*)"]; 
	}
	else $pro = "0";
	return $pro;
}


// --------------------------------------------------------------- 
// Returns number of registries from table of data agents
// --------------------------------------------------------------- 

function dame_numero_datos(){
	require("config.php");
	$query1="select COUNT(*) from tagente_datos";
	$resq1=mysql_query($query1);
	if ($rowdup=mysql_fetch_array($resq1)){
		$pro=$rowdup["COUNT(*)"];
	}
	else $pro = "0";
	return $pro; 
}


// --------------------------------------------------------------- 
// Returns string packet type given ID
// --------------------------------------------------------------- 

function dame_generic_string_data($id){ 
	// Conexion con la base Datos 
	require("config.php");
	$query1="SELECT * FROM tagente_datos_string WHERE id_tagente_datos_string = ".$id;
	$resq1=mysql_query($query1);
	if ($rowdup=mysql_fetch_array($resq1)){
		$pro=$rowdup["datos"];
	}
	return $pro;
}

// --------------------------------------------------------------- 
// Delete incident given its id and all its notes
// --------------------------------------------------------------- 


function borrar_incidencia($id_inc){
	require("config.php");
	$sql1="DELETE FROM tincidencia WHERE id_incidencia = ".$id_inc;
	$result=mysql_query($sql1);
	$sql3="SELECT * FROM tnota_inc WHERE id_incidencia = ".$id_inc;
	$res2=mysql_query($sql3);
	while ($row2=mysql_fetch_array($res2)){
		// Delete all note ID related in table
		$sql4 = "DELETE FROM tnota WHERE id_nota = ".$row2["id_nota"];
		$result4 = mysql_query($sql4);
	}
	$sql6="DELETE FROM tnota_inc WHERE id_incidencia = ".$id_inc;
	$result6=mysql_query($sql6);
	// Delete attachments
	$sql1="SELECT * FROM tattachment WHERE id_incidencia = ".$id_inc;
	$result=mysql_query($sql1);
	while ($row=mysql_fetch_array($result)){
		// Unlink all attached files for this incident
		$file_id = $row["id_attachment"];
		$filename = $row["filename"];
		unlink ($attachment_store."attachment/pand".$file_id."_".$filename);
	}
	$sql1="DELETE FROM tattachment WHERE id_incidencia = ".$id_inc;
	$result=mysql_query($sql1);
}

// --------------------------------------------------------------- 
// Return SO name given its ID
// --------------------------------------------------------------- 

function dame_so_name($id){
	require("config.php");
	$query1="SELECT * FROM tconfig_os WHERE id_os = ".$id;
	$resq1=mysql_query($query1);  
	if ($rowdup=mysql_fetch_array($resq1))
		$pro=$rowdup["name"];
	else
		$pro = "";
	return $pro;
}
// --------------------------------------------------------------- 
//  Update "contact" field in User table for username $nick
// --------------------------------------------------------------- 

function update_user_contact($nick){	// Sophus simply insist too much in this function... ;)
	require("config.php");
	$today=date("Y-m-d H:i:s",time());
	$query1="UPDATE tusuario set fecha_registro ='".$today."' WHERE id_usuario = '".$nick."'";
	$resq1=mysql_query($query1);
}

// --------------------------------------------------------------- 
// Return SO iconname given its ID
// --------------------------------------------------------------- 

function dame_so_icon($id){ 
	require("config.php");
	$query1="SELECT * FROM tconfig_os WHERE id_os = ".$id;
	$resq1=mysql_query($query1);
	if ($rowdup=mysql_fetch_array($resq1))
		$pro=$rowdup["icon_name"];
	else
		$pro = "";
	return $pro;
}


// --------------------------------------------------------------- 
// Return email of a user given ID 
// --------------------------------------------------------------- 

function dame_email($id){ 
	require("config.php");
	$query1="SELECT * FROM tusuario WHERE id_usuario =".$id;
	$resq1=mysql_query($query1);
	$rowdup=mysql_fetch_array($resq1);
	$nombre=$rowdup["direccion"];
	return $nombre;
} 


// ---------------------------------------------------------------
// Returns Admin value (0 no admin, 1 admin)
// ---------------------------------------------------------------

function dame_admin($id){
        $admin = get_db_sql ("SELECT * FROM tusuario WHERE id_usuario ='$id'", "nivel");
	return $admin;
}

// --------------------------------------------------------------- 
// Gives error message and stops execution if user 
//doesn't have an open session and this session is from an valid user
// --------------------------------------------------------------- 

function comprueba_login() { 
	if (isset($_SESSION["id_usuario"])){
		$id = $_SESSION["id_usuario"];
		require("config.php");
		$query1="SELECT * FROM tusuario WHERE id_usuario = '".$id."'";
		$resq1=mysql_query($query1);
		$rowdup=mysql_fetch_array($resq1);
		$nombre=$rowdup["id_usuario"];
		if ( $id == $nombre ){
			return 0 ;	
		}
	}
	return 1;	
}

// --------------------------------------------------------------- 
// Gives error message and stops execution if user 
//doesn't have an open session and this session is from an administrator
// --------------------------------------------------------------- 

function comprueba_admin() {
	if (isset($_SESSION["id_usuario"])){
		$iduser=$_SESSION['id_usuario'];
		if (dame_admin($iduser)==1){
			$id = $_SESSION["id_usuario"];
			require("config.php");
			$query1="SELECT * FROM tusuario WHERE id_usuario = '".$id."'";
			$resq1=mysql_query($query1);
			$rowdup=mysql_fetch_array($resq1);
			$nombre=$rowdup["id_usuario"];
			$nivel=$rowdup["nivel"];
			if (( $id == $nombre) and ($nivel ==1))
				return 0;
		}
	}
	require("../general/no_access.php");
	return 1;
}


// ---------------------------------------------------------------
// Returns number of alerts fired by this agent
// ---------------------------------------------------------------

function check_alert_fired($id_agente){
	require("config.php");
	$query1="SELECT * FROM tagente_modulo WHERE id_agente ='".$id_agente."'";   
	$rowdup=mysql_query($query1);
	while ($data=mysql_fetch_array($rowdup)){
		$query2="SELECT COUNT(*) FROM talerta_agente_modulo WHERE times_fired > 0 AND id_agente_modulo =".$data["id_agente_modulo"];
		$rowdup2=mysql_query($query2);
		$data2=mysql_fetch_array($rowdup2);
		if ($data2[0] > 0)
			return 1;
	}
	return 0;
}

// ---------------------------------------------------------------
// 0 if it doesn't exist, 1 if it does, when given email
// ---------------------------------------------------------------

function existe($id){
	require("config.php");
	$query1="SELECT * FROM tusuario WHERE id_usuario = '".$id."'";   
	$resq1=mysql_query($query1);
	if ($resq1 != 0) {
		if ($rowdup=mysql_fetch_array($resq1)){ 
			return 1; 
		}
		else {
			return 0; 
		}
	} else { return 0 ; }
}

// --------------------------------------------------------------- 
// event_insert - Insert event in eventable, using Id_grupo, Id_agente and Evento
// --------------------------------------------------------------- 

function event_insert($evento, $id_grupo, $id_agente, $status=0, $id_usuario=''){
	require("config.php");
	$today=date('Y-m-d H:i:s');
	$utimestamp = time();
	
	$sql1='INSERT INTO tevento (id_agente, id_grupo, evento, timestamp, estado, utimestamp, id_usuario) VALUES ('.$id_agente.','.$id_grupo.',"'.$evento.'","'.$today.'",'.$status.', '.$utimestamp.', "'.$id_usuario.'")';
	$result=mysql_query($sql1);

}

// --------------------------------------------------------------- 
// Return module interval or agent interval if first not defined
// ---------------------------------------------------------------

function give_moduleinterval($id_agentmodule){ 
	require("config.php");
	$query1="SELECT * FROM tagente_modulo WHERE id_agente_modulo = ".$id_agentmodule;
	$resq1=mysql_query($query1);
	if ($rowdup=mysql_fetch_array($resq1)){
		if ($rowdup["module_interval"] == 0){ // no module interval defined
			$query2="SELECT * FROM tagente WHERE id_agente = ".$rowdup["id_agente"];
			$resq2=mysql_query($query2);
			if ($rowdup2=mysql_fetch_array($resq2)){
				$interval=$rowdup2["intervalo"];
			}
		} else {
			$interval=$rowdup["module_interval"];
		}
	}
	return $interval;
}

// --------------------------------------------------------------- 
// Return agent interval 
// ---------------------------------------------------------------

function give_agentinterval($id_agent){ 
	require("config.php");
	$query1="SELECT * FROM tagente WHERE id_agente = ".$id_agent;
	$resq1=mysql_query($query1);
	if ($rowdup=mysql_fetch_array($resq1)){
		$interval=$rowdup["intervalo"];
	}
	return $interval;
}

// --------------------------------------------------------------- 
// Return agent_module flag (for network push modules)
// ---------------------------------------------------------------

function give_agentmodule_flag($id_agent_module){ 
	require("config.php");
	$query1="SELECT * FROM tagente_modulo WHERE id_agente_modulo = ".$id_agent_module;
	$resq1=mysql_query($query1);
	if ($rowdup=mysql_fetch_array($resq1)){
		$interval=$rowdup["flag"];
	}
	return $interval;
}

// ---------------------------------------------------------------------- 
// Returns a combo with the groups and defines an array 
// to put all groups with Agent Read permission
// ----------------------------------------------------------------------
function list_group ($id_user){
	$mis_grupos=array (); // Define array mis_grupos to put here all groups with Agent Read permission
	$sql='SELECT id_grupo FROM tgrupo order by nombre';
	$result=mysql_query($sql);
	while ($row=mysql_fetch_array($result)){
		if ($row["id_grupo"] != 0){
			if (give_acl($id_user,$row["id_grupo"], "AR") == 1){
				array_push ($mis_grupos, $row["id_grupo"]); //Put in  an array all the groups the user belongs
				echo "<option value='".$row["id_grupo"]."'>".
				dame_nombre_grupo($row["id_grupo"])."</option>";
			}
		}
	}
	return ($mis_grupos);
}

// ---------------------------------------------------------------------- 
// Defines an array 
// to put all groups with Agent Read permission
// ----------------------------------------------------------------------

function list_group2 ($id_user){
	$mis_grupos[]=""; // Define array mis_grupos to put here all groups with Agent Read permission
	$sql='SELECT id_grupo FROM tgrupo';
	$result=mysql_query($sql);
	while ($row=mysql_fetch_array($result)){
		if (give_acl($id_user,$row["id_grupo"], "AR") == 1){
			$mis_grupos[]=$row["id_grupo"]; //Put in  an array all the groups the user belongs
		}
	}
	return ($mis_grupos);
}

// --------------------------------------------------------------- 
// Return Group iconname given its name
// --------------------------------------------------------------- 

function show_icon_group($id_group){ 
	$sql="SELECT icon FROM tgrupo WHERE id_grupo='$id_group'";
	$result=mysql_query($sql);
	if ($row=mysql_fetch_array($result))
		$pro=$row["icon"];
	else
		$pro = "";
	return $pro;
}

// --------------------------------------------------------------- 
// Return Type iconname given its name
// --------------------------------------------------------------- 

function show_icon_type($id_tipo){ 
	$sql="SELECT id_tipo, icon FROM ttipo_modulo WHERE id_tipo='$id_tipo'";
	$result=mysql_query($sql);
	if ($row=mysql_fetch_array($result))
		$pro=$row["icon"];
	else
		$pro = "";
	return $pro;
}

/**
 * Return a string containing image tag for a given target id (server)
 *
 * @param int Server type id
 * @return string Fully formatted  IMG HTML tag with icon
 */

function show_server_type ($id){ 
    global $config;
    switch ($id) {
        case 1: return '<img src="'.$config["homeurl"].'/images/data.png" title="Pandora FMS Data server">';
                break;
        case 2: return '<img src="'.$config["homeurl"].'/images/network.png" title="Pandora FMS Network server">';
                break;
        case 4: return '<img src="'.$config["homeurl"].'/images/plugin.png" title="Pandora FMS Plugin server">';
                break;
        case 5: return '<img src="'.$config["homeurl"].'/images/chart_bar.png" title="Pandora FMS Prediction server">';
                break;
        case 6: return '<img src="'.$config["homeurl"].'/images/wmi.png" title="Pandora FMS WMI server">';
                break;
        default: return "--";
    }
}

// ---------------------------------------------------------------
// Return all childs groups of a given id_group inside array $child
// ---------------------------------------------------------------

function give_groupchild($id_group, &$child){
        // Conexion con la base Datos 
        $query1="select * from tgrupo where parent = ".$id_group;
        $resq1=mysql_query($query1);  
        while ($resq1 != NULL && $rowdup=mysql_fetch_array($resq1)){
        	$child[]=$rowdup["id_grupo"];
        }
}

// ---------------------------------------------------------------
// Return true (1) if agent belongs to given group or one of this childs
// ---------------------------------------------------------------

function agent_belong_group($id_agent, $id_group){ 
        // Conexion con la base Datos 
	$child[] = "";
	$child[] = $id_group;
	give_groupchild($id_group,$child);
	$id_agent_group = give_group_id($id_agent);
	if (array_in($child,$id_agent_group)==1){
		return 1; 
	} else {
		return 0;
	}
}

// ---------------------------------------------------------------
// Return true (1) if given group (a) belongs to given groupset
// ---------------------------------------------------------------

function group_belong_group($id_group_a, $id_groupset){
        // Conexion con la base Datos 
	$childgroup[] = "";
	if ($id_group_a == $id_groupset)
		return 1;
	give_groupchild($id_groupset, $childgroup);
	foreach ($childgroup as $key => $value){
		if (($value != $id_groupset) AND
		    (group_belong_group($id_group_a, $value) == 1))
			return 1;
  	}
	if (array_in ($childgroup, $id_group_a) == 1)
		return 1; 
	else 
		return 0;
}

// ---------------------------------------------------------------
// Return category name
// ---------------------------------------------------------------
function give_modulecategory_name ($value) {
	require("config.php");
	require ("include/languages/language_".$config["language"].".php");
	switch ($value) {
	   case 0: return $lang_label["cat_0"];
	   	break;
	   case 1: return $lang_label["cat_1"];
	   	break;
	   case 2: return $lang_label["cat_2"];
	   	break;
	   case 3: return $lang_label["cat_3"];
	   	break;
	}
	return $lang_label["unknown"]; 
}

// --------------------------------------------------------------- 
// Return network component group name given its ID
// --------------------------------------------------------------- 

function give_network_component_group_name ($id){
	require("config.php");
	$query1="SELECT * FROM tnetwork_component_group WHERE id_sg= ".$id;
	$resq1=mysql_query($query1);
	if ($rowdup=mysql_fetch_array($resq1))
		$pro=$rowdup["name"];
	else
		$pro = "";
	return $pro;
}

// --------------------------------------------------------------- 
// Return network profile name name given its ID
// --------------------------------------------------------------- 

function give_network_profile_name ($id_np){
	require("config.php");
	$query1="SELECT * FROM tnetwork_profile WHERE id_np= ".$id_np;
	$resq1=mysql_query($query1);
	if ($rowdup=mysql_fetch_array($resq1))
		$pro=$rowdup["name"];
	else
		$pro = "";
	return $pro;
}

// --------------------------------------------------------------- 
// Associate IP address to an agent
// --------------------------------------------------------------- 

function agent_add_address ($id_agent, $ip_address) {
	require("config.php");
	$address_exist = 0;
	$id_address =-1;
	$address_attached = 0;

	// Check if already is attached to agent
	$query1="SELECT * FROM taddress_agent, taddress
		WHERE taddress_agent.id_a = taddress.id_a
			AND ip = '$ip_address'
			AND id_agent = $id_agent";
	if ($resq1=mysql_query($query1)){
		if ($rowdup=mysql_fetch_array($resq1)){
			$address_attached = 1;
		}
	}
	if ($address_attached == 1)
		return;
	// Look for a record with this IP Address
	$query1="SELECT * FROM taddress WHERE ip = '$ip_address'";
	if ($resq1=mysql_query($query1)){
		if ($rowdup=mysql_fetch_array($resq1)){
			$id_address = $rowdup["id_a"];
			$address_exist = 1;
		}
	}

	if ($address_exist == 0){
		// Create IP address in tadress table
		$query = "INSERT INTO taddress
			  	(ip) VALUES
			  	('$ip_address')";
		$res = mysql_query ($query);
		$id_address = mysql_insert_id ();
	}
	// Add address to agent
	$query = "INSERT INTO taddress_agent
			(id_a, id_agent) VALUES
			($id_address,$id_agent)";
	$res = mysql_query ($query);
	
	// Change main address in agent to whis one
	/* Not needed, configurar_agente does automatically on every update
	$query = "UPDATE tagente 
		  	(direccion) VALUES
			($ip_address)
			WHERE id_agente = $id_agent ";
	$res = mysql_query ($query);
	*/
}

// --------------------------------------------------------------- 
// De-associate IP address to an agent (delete)
// --------------------------------------------------------------- 

function agent_delete_address ($id_agent, $ip_address) {
	$address_exist = 0;
	$id_address =-1;
	$query1 = "SELECT * FROM taddress_agent, taddress
            WHERE taddress_agent.id_a = taddress.id_a
            AND ip = '$ip_address'
            AND id_agent = $id_agent";
	if ($resq1 = mysql_query($query1)){
		$rowdup = mysql_fetch_array($resq1);
		$id_ag = $rowdup["id_ag"];
		$id_a = $rowdup["id_a"];
		$sql_3 = "DELETE FROM taddress_agent WHERE id_ag = $id_ag";	
		$result_3 = mysql_query($sql_3);
	}
	// Need to change main address ? 
	if (give_agent_address ($id_agent) == $ip_address){
		$new_ip = give_agent_address_from_list ($id_agent);
		// Change main address in agent to whis one
		$query = "UPDATE tagente 
				(direccion) VALUES
				($new_ip)
				WHERE id_agente = $id_agent ";
		$res = mysql_query ($query);
	}

}

// --------------------------------------------------------------- 
// Returns (main) agent address given id
// --------------------------------------------------------------- 

function give_agent_address ($id_agent){
	$query1 = "SELECT * FROM tagente WHERE id_agente = $id_agent";
	$resq1 = mysql_query($query1);
	if ($rowdup = mysql_fetch_array($resq1))
		$pro = $rowdup["direccion"];
	else
		$pro = "";
	return $pro;
}

// --------------------------------------------------------------- 
// Returns the first agent address given id taken from associated addresses
// --------------------------------------------------------------- 

function give_agent_address_from_list ($id_agent){
	$query1="SELECT * FROM taddress_agent, taddress
		WHERE taddress_agent.id_a = taddress.id_a
		AND id_agent = $id_agent";
	if ($resq1=mysql_query($query1)){
		$rowdup=mysql_fetch_array($resq1);
		$pro=$rowdup["ip"];
	}
	else
		$pro = "";
	return $pro;
}

// --------------------------------------------------------------- 
// Returns agent id given name of agent
// --------------------------------------------------------------- 

function give_agent_id_from_module_id ($id_module){
	$query1="SELECT * FROM tagente_modulo WHERE id_agente_modulo = $id_module";
	$resq1=mysql_query($query1);
	if ($rowdup=mysql_fetch_array($resq1))
		$pro=$rowdup["id_agente"];
	else
		$pro = "";
	return $pro;
}

// --------------------------------------------------------------- 
// Generic access to a field ($field) given a table
// --------------------------------------------------------------- 

function get_db_value ($field, $table, $field_search, $condition_value){
    $query = "SELECT $field FROM $table WHERE $field_search = '$condition_value' ";
    $resq1 = mysql_query($query);
    if ($rowdup = mysql_fetch_array($resq1))
        $pro = $rowdup[0];
    else
        $pro = "";
    return $pro;
}

function give_db_value ($field, $table, $field_search, $condition_value){
    get_db_value ($field, $table, $field_search, $condition_value);
}

// --------------------------------------------------------------- 
// Wrapper for old function name. Should be upgraded/renamed in next versions
// --------------------------------------------------------------- 

function get_db_row ($table, $field_search, $condition_value){
    $query = "SELECT * FROM $table WHERE $field_search = '$condition_value' ";
    $resq1 = mysql_query($query);
    if ($rowdup = mysql_fetch_array($resq1))
        return $rowdup;
    else    
        return 0;
}

// --------------------------------------------------------------- 
// Generic access to single field using a free SQL sentence
// --------------------------------------------------------------- 

function get_db_sql ($sentence, $field = 0){
        if ($rowdup = mysql_fetch_array(mysql_query($sentence)))
                return $rowdup[$field];
        else
                return "";
}

// ---------------------------------------------------------------
// Return current status from a given agent module (1 alive, 0 down)
// ---------------------------------------------------------------

function return_status_agent_module ($id_agentmodule = 0){
	$query1 = "SELECT estado FROM tagente_estado WHERE id_agente_modulo = " . $id_agentmodule; 
	$resq1 = mysql_query ($query1);
	if ($resq1 != 0) {
		$rowdup = mysql_fetch_array($resq1);
		if ($rowdup[0] == 100){
			// We need to check if there are any alert on this item
			$query2 = "SELECT SUM(times_fired) FROM talerta_agente_modulo WHERE id_agente_modulo = " . $id_agentmodule;
			$resq2 = mysql_query($query2);
			if ($resq2 != 0) {
		                $rowdup2 = mysql_fetch_array ($resq2);
				if ($rowdup2[0] > 0){
					return 0;
				}
			}
			// No alerts fired for this agent module
			return 1;
		} elseif ($rowdup[0] == 0) // 0 is ok for estado field
			return 1;
		else 
			return 0;
	} else // asking for unknown module ?
		return 0; 
}

// ---------------------------------------------------------------
// Return current status from a given layout
// ---------------------------------------------------------------

// This get's all data from it contained elements (including recursive calls to another nested 
// layouts, and makes and AND to be sure that ALL items are OK. If any of them is down, then
// result is down (0)

function return_status_layout ($id_layout = 0){
	$temp_status = 0;
	$temp_total = 0;
	$sql="SELECT * FROM tlayout_data WHERE id_layout = $id_layout";
	$res=mysql_query($sql);
	while ($row = mysql_fetch_array($res)){
	        $id_agentmodule = $row["id_agente_modulo"];
	        $type = $row["type"];
        	$parent_item = $row["parent_item"];
	        $link_layout = $row["id_layout_linked"];
		if (($link_layout != 0) && ($id_agentmodule == 0)) {
                	$temp_status += return_status_layout ($link_layout);
			$temp_total++;
	        } else {
	                $temp_status += return_status_agent_module ($id_agentmodule);
			$temp_total++;
		}
	}
	if ($temp_status == $temp_total)
		return 1;
	else
		return 0;
}


// ---------------------------------------------------------------
// Return current value from a given agent module 
// ---------------------------------------------------------------

function return_value_agent_module ($id_agentmodule = 0){
	$query1="SELECT datos FROM tagente_estado WHERE id_agente_modulo = ".$id_agentmodule; 
	$resq1=mysql_query($query1);
	if ($resq1 != 0) {
		$rowdup=mysql_fetch_array($resq1);
			return format_numeric($rowdup[0]);
		
	} else 
		return 0; 
}

// ---------------------------------------------------------------
// Return coordinate X from a layout item
// ---------------------------------------------------------------

function return_coordinate_X_layoutdata ($id_layoutdata){
	$query1="SELECT pos_x FROM tlayout_data WHERE id = ".$id_layoutdata; 
	$resq1=mysql_query($query1);
	if ($resq1 != 0) {
		$rowdup=mysql_fetch_array($resq1);
			return ($rowdup[0]);
	} else 
		return (0);
}

// ---------------------------------------------------------------
// Return coordinate X from a layout item
// ---------------------------------------------------------------

function return_coordinate_y_layoutdata ($id_layoutdata){
	$query1="SELECT pos_y FROM tlayout_data WHERE id = ".$id_layoutdata; 
	$resq1=mysql_query($query1);
	if ($resq1 != 0) {
		$rowdup=mysql_fetch_array($resq1);
			return ($rowdup[0]);
	} else 
		return (0);
}

function return_moduledata_avg_value ($id_agent_module, $period){
	$datelimit = time() - $period; // limit date
	$id_agent = get_db_value ("id_agente", "tagente_modulo", "id_agente_modulo", $id_agent_module);
	$query1="SELECT AVG(datos) FROM tagente_datos WHERE id_agente = $id_agent AND id_agente_modulo = $id_agent_module AND utimestamp > $datelimit";
	$resq1=mysql_query($query1);
	if ($resq1 != 0) {
		$rowdup=mysql_fetch_array($resq1);
			return ($rowdup[0]);
	} else 
		return (0);
}


function return_moduledata_max_value ($id_agent_module, $period){
	$datelimit = time() - $period; // limit date
	$id_agent = get_db_value ("id_agente", "tagente_modulo", "id_agente_modulo", $id_agent_module);
	$query1="SELECT MAX(datos) FROM tagente_datos WHERE id_agente = $id_agent AND id_agente_modulo = $id_agent_module AND utimestamp > $datelimit";
	$resq1=mysql_query($query1);
	if ($resq1 != 0) {
		$rowdup=mysql_fetch_array($resq1);
			return ($rowdup[0]);
	} else 
		return (0);
}


function return_moduledata_min_value ($id_agent_module, $period){
	$datelimit = time() - $period; // limit date
	$id_agent = get_db_value ("id_agente", "tagente_modulo", "id_agente_modulo", $id_agent_module);
	$query1="SELECT MIN(datos) FROM tagente_datos WHERE id_agente = $id_agent AND id_agente_modulo = $id_agent_module AND utimestamp > $datelimit";
	$resq1=mysql_query($query1);
	if ($resq1 != 0) {
		$rowdup=mysql_fetch_array($resq1);
			return ($rowdup[0]);
	} else 
		return (0);
}

function lang_string ($string){
    global $config;
    require ($config["homedir"]."/include/languages/language_".$config["language"].".php");
    if (isset ($lang_label[$string]))
        return $lang_label[$string];
    else
        return "[".$string."]";
}

function check_server_status (){
    global $config;
    // This check assumes that server_keepalive should be AT LEAST 15 MIN
    $sql = "SELECT COUNT(id_server) FROM tserver WHERE status = 1 AND keepalive > NOW() - INTERVAL 15 MINUTE";
    $res = get_db_sql ($sql);
    // Set servers to down
    if ($res == 0){ 
        $res2 = mysql_query("UPDATE tserver SET status = 0");
    }
    return $res;
}

function show_alert_row_mini ($id_combined_alert){
    global $config;
    global $lang_label;
    
    $color=1;
    $sql_com = "SELECT talerta_agente_modulo.*, tcompound_alert.operation FROM talerta_agente_modulo, tcompound_alert WHERE tcompound_alert.id_aam = talerta_agente_modulo.id_aam AND tcompound_alert.id = ".$id_combined_alert;
    $result_com = mysql_query ($sql_com);
    echo "<table width=400 cellpadding=2 cellspacing=2 class='databox'>";
    echo "<th>".lang_string("Name");
    echo "<th>".lang_string("Oper");
    echo "<th>".lang_string("Tt");
    echo "<th>".lang_string("Firing");
    echo "<th>".lang_string("Time");
    echo "<th>".lang_string("Desc");
    echo "<th>".lang_string("Recovery");
    echo "<th>".lang_string("MinMax.Al");
    echo "<th>".lang_string("Days");
    echo "<th>".lang_string("Fired");
    while ($row2=mysql_fetch_array($result_com)){

        if ($color == 1){
            $tdcolor = "datos";
            $color = 0;
        }
        else {
            $tdcolor = "datos2";
            $color = 1;
        }
        echo "<tr>";    
    
        if ($row2["disable"] == 1){
            $tdcolor = "datos3";
        }
        echo "<td class=$tdcolor>".get_db_sql("SELECT nombre FROM tagente_modulo WHERE id_agente_modulo =".$row2["id_agente_modulo"]);
        echo "<td class=$tdcolor>".$row2["operation"];
        
        echo "<td class='$tdcolor'>".human_time_description($row2["time_threshold"]);
    
        if ($row2["dis_min"]!=0){
            $mytempdata = fmod($row2["dis_min"], 1);
            if ($mytempdata == 0)
                $mymin = intval($row2["dis_min"]);
            else
                $mymin = $row2["dis_min"];
            $mymin = format_for_graph($mymin );
        } else {
            $mymin = 0;
        }
    
        if ($row2["dis_max"]!=0){
            $mytempdata = fmod($row2["dis_max"], 1);
            if ($mytempdata == 0)
                $mymax = intval($row2["dis_max"]);
            else
                $mymax = $row2["dis_max"];
            $mymax =  format_for_graph($mymax );
        } else {
            $mymax = 0;
        }
    
        if (($mymin == 0) && ($mymax == 0)){
            $mymin = lang_string ("N/A");
            $mymax = $mymin;
        }
    
        // We have alert text ?
        if ($row2["alert_text"]!= "") {
            echo "<td colspan=2 class='$tdcolor'>".$lang_label["text"]."</td>";
        } else {
            echo "<td class='$tdcolor'>".$mymin."/".$mymax."</td>";
        }
    
        // Alert times
        echo "<td class='$tdcolor'>";
        echo get_alert_times ($row2);
    
        // Description
        echo "</td><td class='$tdcolor'>".substr($row2["descripcion"],0,20);
    
        // Has recovery notify activated ?
        if ($row2["recovery_notify"] > 0)
            $recovery_notify = lang_string("Yes");
        else
            $recovery_notify = lang_string("No");
    
        echo "</td><td class='$tdcolor'>".$recovery_notify;
    
        // calculare firing conditions
        if ($row2["alert_text"] != ""){
            $firing_cond = lang_string("text")."(".substr($row2["alert_text"],0,8).")";
        } else {
            $firing_cond = $row2["min_alerts"]." / ".$row2["max_alerts"];
        }
        echo "</td><td class='$tdcolor'>".$firing_cond;

        // calculate days
        $firing_days = get_alert_days ( $row2 );
        echo "</td><td class='$tdcolor'>".$firing_days;

        // Fired ?
        if ($row2["times_fired"]>0)
            echo "<td class='".$tdcolor."' align='center'><img width='20' height='9' src='images/pixel_red.png' title='".lang_string("fired")."'></td>";
        else
            echo "<td class='".$tdcolor."' align='center'><img width='20' height='9' src='images/pixel_green.png' title='".$lang_label["not_fired"]."'></td>";

    }
    echo "</table>";
}
?>
