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
require_once ("include/config.php");

if (give_acl($config["id_user"], 0, "AW") != 1) {
	audit_db($config["id_user"],$REMOTE_ADDR, "ACL Violation",
	"Trying to access Agent Management");
	require ("general/noaccess.php");
	exit;
}

// Take some parameters (GET)
$offset = get_parameter ("offset", 0);
$group_id = get_parameter ("group_id", 0);
$ag_group = get_parameter ("ag_group", -1);
if (($ag_group == -1) && ($group_id != 0)) {
	$ag_group = $group_id;
}
if (isset ($_GET["ag_group_refresh"])){
	$ag_group = get_parameter_get ("ag_group_refresh", -1);
}
$search = get_parameter ("search", "");

if (isset ($_GET["borrar_agente"])) { // if delete agent
	$id_agente = get_parameter_get ("borrar_agente");
	$agent_name = get_agent_name ($id_agente);
	$id_grupo = dame_id_grupo ($id_agente);
	if (give_acl ($config["id_user"], $id_grupo, "AW")==1) {
		//Start transaction - this improves consistency
		process_sql ("SET AUTOCOMMIT=0;");
		process_sql ("START TRANSACTION;");
		$del_error = 0; //Delete error count. At the end it will be used to rollback or commit
		
		// Firts delete from agents table
		$sql_delete = "DELETE FROM tagente WHERE id_agente = ".$id_agente;
		if (process_sql ($sql_delete) === false) 
			$del_error++; //in case process_sql returns false, increase error count
		
		// Delete agent access table
		$sql_delete = "DELETE FROM tagent_access WHERE id_agent = ".$id_agente;
		if (process_sql ($sql_delete) === false) 
			$del_error++;
		
		// Delete tagente_datos data
		$sql_delete = "DELETE FROM tagente_datos WHERE id_agente = ".$id_agente;
		if (process_sql ($sql_delete) === false)
			$del_error++;
			
		// Delete tagente_datos_string data
		$sql_delete = "DELETE FROM tagente_datos_string WHERE id_agente = ".$id_agente;
		if (process_sql ($sql_delete) === false)
			$del_error++;	
		
		// Delete from tagente_datos - relies on id_agente_modulo
		$sql_delete = "DELETE FROM tagente_datos_inc WHERE 
		id_agente_modulo = ANY(SELECT id_agente_modulo FROM tagente_modulo WHERE id_agente = ".$id_agente.")";
		if (process_sql ($sql_delete) === false)
			$del_error++;
		
		// Delete alerts from talerta_agente_modulo - relies on
		// id_agente_modulo	
		$sql_delete = "DELETE FROM talerta_agente_modulo WHERE
		id_agente_modulo = ANY(SELECT id_agente_modulo FROM tagente_modulo WHERE id_agente = ".$id_agente.")";
		if (process_sql ($sql_delete) === false)
			$del_error++;
		
		// Delete from tagente_modulo	
		$sql_delete ="DELETE FROM tagente_modulo WHERE id_agente = ".$id_agente;
		if (process_sql ($sql_delete) === false)
			$del_error++;	
		
		// Delete from tagente_estado
		$sql_delete ="DELETE FROM tagente_estado WHERE id_agente = ".$id_agente;
		if (process_sql ($sql_delete) === false)
			$del_error++;	
		
		// Delete IP's from taddress table using taddress_agent
		$sql_delete = "DELETE FROM taddress WHERE 
		id_a = ANY(SELECT id_a FROM taddress_agent WHERE id_agent = ".$id_agente.")";
		if (process_sql ($sql_delete) === false)
			$del_error++;
		
		// Delete IPs from taddress_agent table
		$sql_delete = "DELETE FROM taddress_agent WHERE id_agent = ".$id_agente;
		if (process_sql ($sql_delete) === false)
			$del_error++;
						
		if ($del_error > 0) {
			process_sql ("ROLLBACK;");
			echo "<h3 class='error'>".__('There was a problem deleting agent')."</h3>";
		} else {
			process_sql ("COMMIT;");
			echo "<h3 class='suc'>".__('Agent deleted successfully')."</h3>";
		}
		unset ($sql_delete, $del_error); //Clean up
		process_sql ("SET AUTOCOMMIT=1;");	
		audit_db($config["id_user"],$REMOTE_ADDR, "Agent \'$agent_name\' deleted", "Agent Management");

		// Delete remote configuration
		$agent_md5 = md5($agent_name, FALSE);
		if (file_exists($config["remote_config"] . "/" . $agent_md5 . ".md5")) {
		// Agent remote configuration editor
			$file_name = $config["remote_config"] . "/" . $agent_md5 . ".conf";
			unlink ($file_name);
			$file_name = $config["remote_config"] . "/" . $agent_md5 . ".md5";
			unlink ($file_name);
		}
	} else { // NO permissions.
		audit_db ($config["id_user"],$REMOTE_ADDR, "ACL Violation",
			"Trying to delete agent \'$agent_name\'");
		require ("general/noaccess.php");
		exit;
	}
}
echo "<h2>".__('Agent configuration')." &gt; ".__('Agents defined in Pandora')."</h2>";

// Show group selector
if (isset($_POST["ag_group"])){
	$ag_group = get_parameter_post ("ag_group");
	echo "<form method='post'
	action='index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&ag_group_refresh=".$ag_group."'>";
} else {
	echo "<form method='post'
	action='index.php?sec=gagente&sec2=godmode/agentes/modificar_agente'>";
}

echo "<table cellpadding='4' cellspacing='4' class='databox' width=700><tr>";
echo "<td valign='top'>".__('Group')."</td>";
echo "<td valign='top'>";
echo "<select name='ag_group' onChange='javascript:this.form.submit();'
class='w130'>";

if ( $ag_group > 1 ){
	echo "<option value='".$ag_group."'>".get_group_name ($ag_group).
	"</option>";
}
echo "<option value=1>".get_group_name (1)."</option>"; // Group all is always active
$mis_grupos = list_group ($config["id_user"]); //Print combo for groups and set an array with all groups
echo "</select>";
echo "<td valign='top'>
<noscript>
<input name='uptbutton' type='submit' class='sub upd'
value='".__('Show')."'>
</noscript>
</td>
</form>
<td valign='top'>";

echo __('Free text for search (*)');
echo "</td><td>";

// Show group selector
if (isset($_POST["ag_group"])){
        $group_mod = "&ag_group_refresh=".get_parameter_post ("ag_group");
} else {
        $group_mod ="";
}

echo "<form method='post' action='index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&refr=60$group_mod'>";
echo "<input type=text name='search' size='15' >";
echo "</td><td valign='top'>";
echo "<input name='srcbutton' type='submit' class='sub' 
value='".__('Search')."'>";
echo "</form>";
echo "</td></table>";

if ($search != ""){
        $search_sql = " nombre LIKE '%$search%' ";
} else {
        $search_sql = " 1 = 1";
}

// Show only selected groups    
if ($ag_group > 1){
        $sql1="SELECT * FROM tagente WHERE id_grupo = $ag_group
        AND $search_sql ORDER BY nombre LIMIT $offset, ".$config["block_size"];
        $sql2="SELECT COUNT(id_agente) FROM tagente WHERE id_grupo = $ag_group 
        AND $search_sql ORDER BY nombre";
} else {
        // Is admin user ??
        if (get_db_sql ("SELECT * FROM tusuario WHERE id_usuario ='".$config["id_user"]."'", "nivel") == 1){
                $sql1 = "SELECT * FROM tagente WHERE $search_sql ORDER BY nombre, id_grupo LIMIT $offset, ".$config["block_size"];
                $sql2="SELECT COUNT(id_agente) FROM tagente WHERE $search_sql ORDER BY nombre, id_grupo";
        } else {
                $sql1="SELECT * FROM tagente WHERE $search_sql AND id_grupo IN (SELECT id_grupo FROM tusuario_perfil WHERE id_usuario='".$config["id_user"]."')  
                ORDER BY nombre, id_grupo LIMIT $offset, ".$config["block_size"];
                $sql2="SELECT COUNT(id_agente) FROM tagente WHERE $search_sql AND id_grupo IN (SELECT id_grupo FROM tusuario_perfil WHERE id_usuario='".$config["id_user"]."') ORDER BY nombre, id_grupo";
        }
}

$result=mysql_query($sql1);
$result2=mysql_query($sql2);
$row2=mysql_fetch_array($result2);
$total_events = $row2[0];

// Prepare pagination
pagination ($total_events, "index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&group_id=$ag_group", $offset);
echo "<div style='height: 20px'> </div>";

if (mysql_num_rows($result)){
	echo "<table cellpadding='4' cellspacing='4' width='750' class='databox'>";
	echo "<th>".__('Agent name')."</th>";
	echo "<th title='".__('Remote agent configuration')."'>".__('R')."</th>";
	echo "<th>".__('OS')."</th>";
	echo "<th>".__('Group')."</th>";
	echo "<th>".__('Description')."</th>";
	echo "<th>".__('Delete')."</th>";
	$color=1;
	while ($row=mysql_fetch_array($result)){
		$id_grupo = $row["id_grupo"];
		if ($color == 1){
			$tdcolor = "datos";
			$color = 0;
			}
		else {
			$tdcolor = "datos2";
			$color = 1;
		}
		if (! give_acl($config["id_user"], $id_grupo, "AW"))
			continue;
	
		// Agent name
		echo "<tr class='$tdcolor' >";
		echo "<td>";
		if ($row["disabled"] == 1){
			echo "<i>";
		}
		echo "<b><a href='index.php?sec=gagente&
		sec2=godmode/agentes/configurar_agente&tab=main&
		id_agente=".$row["id_agente"]."'>".substr(strtoupper($row["nombre"]),0,20)."</a></b>";
		if ($row["disabled"] == 1){
			echo "<i>";
		}
		echo "</td>";

		echo "<td align='center'>";
		// Has remote configuration ?
		$agent_md5 = md5 ($row["nombre"], false);
		if (file_exists ($config["remote_config"]."/".$agent_md5.".md5")) {

			echo "<a href='index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=main&id_agente=".$row["id_agente"]."&disk_conf=" . $agent_md5 . "'>";
			echo "<img src='images/application_edit.png' border='0' align='middle' title='".__('Edit remote config')."'>";
			echo "</A>";
		}
		echo "</td>";
		
		// Operating System icon
		echo "<td align='center'>";
		print_os_icon ($row["id_os"], false);
		echo "</td>";
		// Group icon and name
		echo "<td>";
		print_group_icon ($id_grupo);
		echo "&nbsp; ".get_group_name ($id_grupo)."</td>";
		// Description
		echo "<td class='".$tdcolor."f9'>".$row["comentarios"]."</td>";
		// Action
		echo "<td align='center'><a href='index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&
		borrar_agente=".$row["id_agente"]."'";
		echo ' onClick="if (!confirm(\' '.__('Are you sure?').'\')) return false;">';
		echo "<img src='images/cross.png'></a></td>";
	}
	echo "</table>";
	echo "<table width='750'><tr><td align='right'>";
} else {
	echo "<div class='nf'>".__('There are no defined agents')."</div>";
	echo "&nbsp;</td></tr><tr><td>";
}

// Create agent button
echo "<form method='post' action='index.php?sec=gagente&
sec2=godmode/agentes/configurar_agente&create_agent=1'>";
echo "<input type='submit' class='sub next' name='crt'
value='".__('Create agent')."'>";
echo "</form></td></tr></table>";
?>
