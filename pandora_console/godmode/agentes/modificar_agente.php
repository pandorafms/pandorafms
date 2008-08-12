<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
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
// Load global vars
require("include/config.php");

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
if (($ag_group == -1) && ($group_id != 0))
        $ag_group = $group_id;
if (isset($_GET["ag_group_refresh"])){
        $ag_group = $_GET["ag_group_refresh"];
}
$search = get_parameter ("search", "");

if (isset($_GET["borrar_agente"])){ // if delete agent
	$id_agente = entrada_limpia($_GET["borrar_agente"]);
	$agent_name = dame_nombre_agente($id_agente);
	$id_grupo = dame_id_grupo($id_agente);
	if (give_acl($config["id_user"], $id_grupo, "AW")==1){
		// Firts delete from agents table
		$sql_delete= "DELETE FROM tagente
		WHERE id_agente = ".$id_agente;
		$result=mysql_query($sql_delete);
		if (! $result)
			echo "<h3 class='error'>".__('There was a problem deleting agent')."</h3>";
		else
			echo "<h3 class='suc'>".__('Agent deleted successfully')."</h3>";
		// Delete agent access table
		$sql_delete = "DELETE FROM tagent_access
		WHERE id_agent = ".$id_agente;
		// Delete tagente_datos data
		$result=mysql_query($sql_delete);
		$sql_delete4="DELETE FROM tagente_datos
		WHERE id_agente=".$id_agente;
		$result=mysql_query($sql_delete4);
		// Delete tagente_datos_string data
		$result=mysql_query($sql_delete);
		$sql_delete4="DELETE FROM tagente_datos_string
		WHERE id_agente=".$id_agente;
		$result=mysql_query($sql_delete4);
		// Delete from tagente_datos
		$sql1='SELECT * FROM tagente_modulo
		WHERE id_agente = '.$id_agente;
		$result1=mysql_query($sql1);
		while ($row=mysql_fetch_array($result1)){
			$sql_delete4="DELETE FROM tagente_datos_inc
			WHERE id_agente_modulo=".$row["id_agente_modulo"];
			$result=mysql_query($sql_delete4);
		}
		$sql_delete2 ="DELETE FROM tagente_modulo
		WHERE id_agente = ".$id_agente;
		$sql_delete3 ="DELETE FROM tagente_estado
		WHERE id_agente = ".$id_agente;
		$result=mysql_query($sql_delete2);
		$result=mysql_query($sql_delete3);
		
		// Delete IPs from tadress table and taddress_agent
		$sql = "SELECT * FROM taddress_agent where id_agent = $id_agente";
		$result=mysql_query($sql);
		while ($row=mysql_fetch_array($result)){
			$sql2="DELETE FROM taddress where id_a = ".$row["id_a"];
			$result2=mysql_query($sql2);
		}
		$sql = "DELETE FROM taddress_agent  where id_agent = $id_agente";
		$result=mysql_query($sql);
		audit_db($config["id_user"],$REMOTE_ADDR, "Agent '$agent_name' deleted", "Agent Management");

		// Delete remote configuration
		$agent_md5 = md5($agent_name, FALSE);
		if (file_exists($config["remote_config"] . "/" . $agent_md5 . ".md5")){
		// Agent remote configuration editor
			$file_name = $config["remote_config"] . "/" . $agent_md5 . ".conf";
			unlink ($file_name);
			$file_name = $config["remote_config"] . "/" . $agent_md5 . ".md5";
			unlink ($file_name);
		}
	} else { // NO permissions.
		audit_db($config["id_user"],$REMOTE_ADDR, "ACL Violation",
		"Trying to delete agent '$agent_name'");
		require ("general/noaccess.php");
		exit;
	}
}
echo "<h2>".__('Agent configuration')." &gt; ".__('Agents defined in Pandora')."</h2>";

// Show group selector
if (isset($_POST["ag_group"])){
	$ag_group = $_POST["ag_group"];
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
	echo "<option value='".$ag_group."'>".dame_nombre_grupo($ag_group).
	"</option>";
}
echo "<option value=1>".dame_nombre_grupo(1)."</option>"; // Group all is always active
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
        $group_mod = "&ag_group_refresh=".$_POST["ag_group"];
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
		if (give_acl($config["id_user"], $id_grupo, "AW")==1){
			// Agent name
			echo "<tr><td class='$tdcolor'>";
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

			echo "<td align='center' class='$tdcolor'>";
			// Has remote configuration ?
			$agent_md5 = md5($row["nombre"], FALSE);
			if (file_exists($config["remote_config"] . "/" . $agent_md5 . ".md5")){

				echo "<a href='index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=main&id_agente=".$row["id_agente"]."&disk_conf=" . $agent_md5 . "'>";
				echo "<img src='images/application_edit.png' border='0' align='middle' title='".__('Edit remote config')."'>";
				echo "</A>";
			}
			echo "</td>";


			// Operating System icon
			echo "<td class='$tdcolor' align='center'>
			<img src='images/".dame_so_icon($row["id_os"])."'></td>";
			// Group icon and name
			echo "<td class='$tdcolor'>
			<img src='images/groups_small/".show_icon_group($id_grupo).".png' class='bot' border='0'>
			&nbsp; ".dame_grupo($id_grupo)."</td>";
			// Description
			echo "<td class='".$tdcolor."f9'>".$row["comentarios"]."</td>";
			// Action
			echo "<td class='$tdcolor' align='center'><a href='index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&
			borrar_agente=".$row["id_agente"]."'";
			echo ' onClick="if (!confirm(\' '.__('Are you sure?').'\')) return false;">';
			echo "<img border='0' src='images/cross.png'></a></td>";
		}
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
