<?php

// Pandora - the Free monitoring system
// ====================================
// Copyright (c) 2004-2007 Sancho Lerena, slerena@gmail.com
// Copyright (c) 2005-2007 Artica Soluciones Tecnoloicas S.L, info@artica.es
// Copyright (c) 2004-2007 Raul Mateos Martin, raulofpandora@gmail.com
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

	// Load global vars
	require("include/config.php");

	if (comprueba_login() != 0) {
		audit_db("Noauth",$REMOTE_ADDR, "No authenticated acces","Trying to access event viewer");
		require ("general/noaccess.php");
		exit;
	}

	$id_usuario =$_SESSION["id_usuario"];
	if (give_acl($id_usuario, 0, "PM")!=1) {
		audit_db($id_usuario,$REMOTE_ADDR, "ACL Violation","Trying to access event viewer");
		require ("general/noaccess.php");
		exit;
	}

	echo "<h2>".$lang_label["audit_title"]."</h2>";
	if (isset($_GET["offset"]))
		$offset=$_GET["offset"];
	else
		$offset=0;

	// Manage GET/POST parameter for subselect on action type. POST parameter are proccessed before GET parameter (if passed)
	if (isset($_GET["tipo_log"])){
		$tipo_log = $_GET["tipo_log"];
		$tipo_log_select = " WHERE accion='".$tipo_log."' ";
	} elseif (isset($_POST["tipo_log"])) {
		$tipo_log = $_POST["tipo_log"];
		if ($tipo_log == "-1"){
			$tipo_log_select = "";
			unset($tipo_log);
		} else
			$tipo_log_select = " WHERE accion='".$tipo_log."' ";
	}
	else
		$tipo_log_select= "";

	// generate select 

	echo "<h3>".$lang_label["filter"];
	echo "<a href='help/".$help_code."/chap6.php#6' target='_help' class='help'>
	&nbsp;<span>".$lang_label["help"]."</span></a></h3>";
	echo "<form name='query_sel' method='post' action='index.php?sec=godmode&sec2=godmode/admin_access_logs'>";
	echo "<table border='0'><tr><td valign='middle'>";
	echo "<select name='tipo_log' onChange='javascript:this.form.submit();'>";
	if (isset($tipo_log))
		echo "<option>".$tipo_log;
	echo "<option value='-1'>".$lang_label["all"];
	$sql3="SELECT DISTINCT (accion) FROM `tsesion`"; 
	// Prepare index for pagination
	$result3=mysql_query($sql3);
	while ($row3=mysql_fetch_array($result3)){
		if (isset($tipo_log)) {
			if ($tipo_log != $row3[0])
				echo "<option value='".$row3[0]."'>".$row3[0];
		} else
		echo "<option value='".$row3[0]."'>".$row3[0];
	}
	echo "</select>";
	echo "<td valign='middle'><noscript><input name='uptbutton' type='submit' class='sub' value='".$lang_label["show"]."'></noscript>";
	echo "</table></form>";

	$sql2="SELECT COUNT(*) FROM tsesion ".$tipo_log_select." ORDER BY fecha DESC";
	$result2=mysql_query($sql2);
	$row2=mysql_fetch_array($result2);
	$counter = $row2[0];
	if (isset ($tipo_log))
		$url = "index.php?sec=godmode&sec2=godmode/admin_access_logs&tipo_log=".$tipo_log;
	else
		$url = "index.php?sec=godmode&sec2=godmode/admin_access_logs";

	//echo "URLTipolog  $tipo_log";
	pagination ($counter, $url, $offset);
	echo '<br>';
	// table header
	echo '<table cellpadding="3" cellspacing="3" width=700>';
	echo '<tr>';
	echo '<th class="w70">'.$lang_label["user"].'</th>';
	echo '<th>'.$lang_label["action"].'</th>';
	echo '<th class="w130">'.$lang_label["date"].'</th>';
	echo '<th class="w100">'.$lang_label["src_address"].'</th>';
	echo '<th class="w200">'.$lang_label["comments"].'</th>';

	// Skip offset records
	$query1="SELECT * FROM tsesion ".$tipo_log_select." ORDER BY fecha DESC";
	$result=mysql_query($query1);
	$offset_counter = 0;
	while ($offset_counter < $offset){
		if ($row=mysql_fetch_array($result))
			$offset_counter++;
		else
			$offset_counter = $offset; //exit condition
	}

	$offset_counter = 0;
	$color=1;
	// Get data
	while ($row=mysql_fetch_array($result) and ($offset_counter < $block_size) ){
		if ($color == 1){
			$tdcolor = "datos";
			$color = 0;
		}
		else {
			$tdcolor = "datos2";
			$color = 1;
		}
		$usuario=$row["ID_usuario"];
		echo '<tr><td class="'.$tdcolor.'_id">'.$usuario;
		echo '<td class="'.$tdcolor.'">'.$row["accion"];
		echo '<td class="'.$tdcolor.'f9">'.$row["fecha"];
		echo '<td class="'.$tdcolor.'f9">'.$row["IP_origen"];
		echo '<td class="'.$tdcolor.'">'.$row["descripcion"];
		echo '</tr>';
		$offset_counter++;
	}

	// end table
	echo "<tr><td colspan='5'><div class='raya'></div></td></tr></table>"; 

?>