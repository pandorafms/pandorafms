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

if (give_acl($id_user, 0, "AW") != 1) {
	audit_db($id_user,$REMOTE_ADDR, "ACL Violation",
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
	if (give_acl($id_user, $id_grupo, "AW")==1){
		// Firts delete from agents table
		$sql_delete= "DELETE FROM tagente
		WHERE id_agente = ".$id_agente;
		$result=mysql_query($sql_delete);
		if (! $result)
			echo "<h3 class='error'>".$lang_label["delete_agent_no"]."</h3>";
		else
			echo "<h3 class='suc'>".$lang_label["delete_agent_ok"]."</h3>";
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
		audit_db($id_user,$REMOTE_ADDR, "Agent '$agent_name' deleted", "Agent Management");
	} else { // NO permissions.
		audit_db($id_user,$REMOTE_ADDR, "ACL Violation",
		"Trying to delete agent '$agent_name'");
		require ("general/noaccess.php");
		exit;
	}
}
echo "<h2>".$lang_label["agent_conf"]." &gt; ".$lang_label["agent_defined2"]."</h2>";

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
echo "<td valign='top'>".$lang_label["group"]."</td>";
echo "<td valign='top'>";
echo "<select name='ag_group' onChange='javascript:this.form.submit();'
class='w130'>";

if ( $ag_group > 1 ){
	echo "<option value='".$ag_group."'>".dame_nombre_grupo($ag_group).
	"</option>";
}
echo "<option value=1>".dame_nombre_grupo(1)."</option>"; // Group all is always active
$mis_grupos = list_group ($id_user); //Print combo for groups and set an array with all groups
echo "</select>";
echo "<td valign='top'>
<noscript>
<input name='uptbutton' type='submit' class='sub upd'
value='".$lang_label["show"]."'>
</noscript>
</td>
</form>
<td valign='top'>";

echo $lang_label["free_text_search"];
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
value='".$lang_label["search"]."'>";
echo "</form>";
echo "</td></table>";

if ($search != ""){
        $search_sql = " AND nombre LIKE '%$search%' ";
} else {
        $search_sql = "";
}

// Show only selected groups    
if ($ag_group > 1){
        $sql1="SELECT * FROM tagente WHERE id_grupo=$ag_group
        AND disabled = 0 $search_sql ORDER BY nombre LIMIT $offset, ".$config["block_size"];
        $sql2="SELECT COUNT(id_agente) FROM tagente WHERE id_grupo = $ag_group 
        AND disabled = 0 $search_sql ORDER BY nombre";
} else {
        // Is admin user ??
        if (get_db_sql ("SELECT * FROM tusuario WHERE id_usuario ='$id_user'", "nivel") == 1){
                $sql1 = "SELECT * FROM tagente WHERE disabled = 0 $search_sql ORDER BY nombre, id_grupo LIMIT $offset, ".$config["block_size"];
                $sql2="SELECT COUNT(id_agente) FROM tagente WHERE disabled = 0 $search_sql ORDER BY nombre, id_grupo";
        } else {
                $sql1="SELECT * FROM tagente WHERE disabled = 0 $search_sql AND id_grupo IN (SELECT id_grupo FROM tusuario_perfil WHERE id_usuario='$id_user')
                ORDER BY nombre, id_grupo LIMIT $offset, ".$config["block_size"];
                $sql2="SELECT COUNT(id_agente) FROM tagente WHERE disabled = 0 $search_sql AND id_grupo IN (SELECT id_grupo FROM tusuario_perfil WHERE id_usuario='$id_user') ORDER BY nombre, id_grupo";
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
	echo "<th>".$lang_label["agent_name"]."</th>";
	echo "<th>".$lang_label["os"]."</th>";
	echo "<th>".$lang_label["group"]."</th>";
	echo "<th>".$lang_label["description"]."</th>";
	echo "<th>".$lang_label["delete"]."</th>";
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
		if (give_acl($id_user, $id_grupo, "AW")==1){
			// Agent name
			echo "<tr><td class='$tdcolor'>
			<b><a href='index.php?sec=gagente&
			sec2=godmode/agentes/configurar_agente&tab=main&
			id_agente=".$row["id_agente"]."'>".substr(strtoupper($row["nombre"]),0,20)."</a></b></td>";
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
			echo ' onClick="if (!confirm(\' '.$lang_label["are_you_sure"].'\')) return false;">';
			echo "<img border='0' src='images/cross.png'></a></td>";
		}
	}
	echo "</table>";
	echo "<table width='750'><tr><td align='right'>";
} else {
	echo "<div class='nf'>".$lang_label["no_agent_def"]."</div>";
	echo "&nbsp;</td></tr><tr><td>";
}

	// Create agent button
	echo "<form method='post' action='index.php?sec=gagente&
	sec2=godmode/agentes/configurar_agente&create_agent=1'>";
	echo "<input type='submit' class='sub next' name='crt'
	value='".$lang_label["create_agent"]."'>";
	echo "</form></td></tr></table>";
?>
