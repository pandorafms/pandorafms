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

check_login ();

if (! give_acl ($config['id_user'], 0, "PM")) {
	audit_db($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access event viewer");
	require ("general/noaccess.php");
	exit;
}

echo "<h2>".__('audit_title')." &gt ".__('logs')."</h2>";
if (isset ($_GET["offset"]))
	$offset=$_GET["offset"];
else
	$offset=0;

echo "<table width=100%>";
echo "<tr><td>";
echo "<table cellpadding='4' cellspacing='4' class='databox'>";
echo "<tr><td colspan='2' valign='top'>";
echo "<h3>".__('filter')."</h3></td></tr>";
// Manage GET/POST parameter for subselect on action type. POST parameter are proccessed before GET parameter (if passed)
if (isset ($_GET["tipo_log"])) {
	$tipo_log = $_GET["tipo_log"];
	$tipo_log_select = " WHERE accion='".$tipo_log."' ";
} elseif (isset ($_POST["tipo_log"])) {
	$tipo_log = $_POST["tipo_log"];
	if ($tipo_log == "-1"){
		$tipo_log_select = "";
		unset($tipo_log);
	} else {
		$tipo_log_select = " WHERE accion='".$tipo_log."' ";
	}
} else {
	$tipo_log_select= "";
}
// generate select 

echo "<form name='query_sel' method='post' action='index.php?sec=godmode&sec2=godmode/admin_access_logs'>";
echo "<tr><td>".__('action')."</td><td valign='middle'>";
echo "<select name='tipo_log' onChange='javascript:this.form.submit();'>";
if (isset($tipo_log)) {
	echo "<option>".$tipo_log."</option>";
}
echo "<option value='-1'>".__('all')."</option>";
$sql3="SELECT DISTINCT (accion) FROM `tsesion`"; 
// Prepare index for pagination
$result3=mysql_query($sql3);
while ($row3=mysql_fetch_array($result3)){
	if (isset($tipo_log)) {
		if ($tipo_log != $row3[0]) {
			echo "<option value='".$row3[0]."'>".$row3[0]."</option>";
		}
	} else {
		echo "<option value='".$row3[0]."'>".$row3[0]."</option>";
	}
}
echo "</select>";
echo "<td valign='middle'><noscript><input name='uptbutton' type='submit' class='sub' value='".__('show')."'></noscript>";
echo "</table></form>";

echo "</td><td align='right'>";
echo "<img src='reporting/fgraph.php?tipo=user_activity&width=300&height=140'>";
echo "</table>";

$sql2="SELECT COUNT(*) FROM tsesion ".$tipo_log_select." ORDER BY fecha DESC";
$result2=mysql_query($sql2);
$row2=mysql_fetch_array($result2);
$counter = $row2[0];
if (isset ($tipo_log))
	$url = "index.php?sec=godmode&sec2=godmode/admin_access_logs&tipo_log=".$tipo_log;
else
	$url = "index.php?sec=godmode&sec2=godmode/admin_access_logs";

// Prepare query and pagination
$query1 = "SELECT * FROM tsesion " . $tipo_log_select." ORDER BY fecha DESC"; 
if ( $counter > $config["block_size"]) {
	pagination ($counter, $url, $offset);
	$query1 .= " LIMIT $offset , ".$config["block_size"];
}
$result=mysql_query($query1);

// table header
echo '<table cellpadding="4" cellspacing="4" width="700" class="databox">';
echo '<tr>';
echo '<th width="80px">'.__('user').'</th>';
echo '<th>'.__('action').'</th>';
echo '<th width="130px">'.__('date').'</th>';
echo '<th width="100px">'.__('src_address').'</th>';
echo '<th width="200px">'.__('comments').'</th>';

$color=1;
// Get data
while ($row=mysql_fetch_array($result)) {
	if ($color == 1){
		$tdcolor = "datos";
		$color = 0;
	}
	else {
		$tdcolor = "datos2";
		$color = 1;
	}
	echo '<tr><td class="'.$tdcolor.'_id">'.$row["ID_usuario"];
	echo '<td class="'.$tdcolor.'">'.$row["accion"];
	echo '<td class="'.$tdcolor.'f9">'.$row["fecha"];
	echo '<td class="'.$tdcolor.'f9">'.$row["IP_origen"];
	echo '<td class="'.$tdcolor.'">'.$row["descripcion"];
	echo '</tr>';
}

// end table
echo "</table>"; 

?>
