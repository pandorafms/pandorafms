<?php

// Pandora - the Free monitoring system
// ====================================
// Copyright (c) 2004-2006 Sancho Lerena, slerena@gmail.com
// Copyright (c) 2005-2006 Artica Soluciones Tecnologicas S.L, info@artica.es
// Copyright (c) 2004-2006 Raul Mateos Martin, raulofpandora@gmail.com
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
	audit_db("Noauth",$REMOTE_ADDR, "No authenticated acces","Trying to access incident viewer");
	require ("general/noaccess.php");
	exit;
}
$id_usuario =$_SESSION["id_usuario"];
$accion = "";
if (give_acl($id_usuario, 0, "IR")!=1) {
	audit_db($id_usuario,$REMOTE_ADDR, "ACL Violation","Trying to access incident viewer");
	require ("general/noaccess.php");
	exit;
}

if (isset($_GET["quick_delete"])){
	$id_inc = $_GET["quick_delete"];
	$sql2="SELECT * FROM tincidencia WHERE id_incidencia=".$id_inc;
	$result2=mysql_query($sql2);
	$row2=mysql_fetch_array($result2);
	if ($row2) {
		$id_author_inc = $row2["id_usuario"];
		if ((give_acl($id_usuario, $row2["id_grupo"], "IM") ==1) OR ($_SESSION["id_usuario"] == $id_author_inc) ){
			borrar_incidencia($id_inc);
			echo "<h3 class='suc'>".$lang_label["del_incid_ok"]."</h3>";
			}
		else {
			audit_db($id_author_inc,$REMOTE_ADDR,"ACL Forbidden","User ".$_SESSION["id_usuario"]." try to delete incident");
			echo "<h3 class='error'>".$lang_label["del_incid_no"]."</h3>";
			no_permission();
		}
	}
}

// Search
$busqueda="";
if (isset($_POST["texto"]) OR (isset($_GET["texto"]))){
	if (isset($_POST["texto"])){
		$texto_form = $_POST["texto"];
		$_GET["texto"]=$texto_form; // Update GET vars if data comes from POST
	} else	// GET
		$texto_form = $_GET["texto"];

	$busqueda = "( titulo LIKE '%".$texto_form."%' OR descripcion LIKE '%".$texto_form."%' )";
}

if (isset($_POST["usuario"]) OR (isset($_GET["usuario"]))){
	if (isset($_POST["usuario"])){
		$usuario_form = $_POST["usuario"];
		$_GET["usuario"]=$usuario_form;
	} else // GET
		$usuario_form=$_GET["usuario"];

	if ($usuario_form != ""){
		if (isset($_GET["texto"]))
			$busqueda = $busqueda." and ";
		$busqueda= $busqueda." id_usuario = '".$_GET["usuario"]."' ";
	}
}

// Filter
if ($busqueda != "")
	$sql1= "WHERE ".$busqueda;
else
	$sql1="";

if (isset($_GET["estado"]) and (!isset($_POST["estado"])))
	$_POST["estado"]=$_GET["estado"];
if (isset($_GET["grupo"]) and (!isset($_POST["grupo"])))
		$_POST["grupo"]=$_GET["grupo"];
if (isset($_GET["prioridad"]) and (!isset($_POST["prioridad"])))
		$_POST["prioridad"]=$_GET["prioridad"];


if (isset($_POST['estado']) OR (isset($_POST['grupo'])) OR (isset($_POST['prioridad']) ) ) {
		if ((isset($_POST["estado"])) AND ($_POST["estado"] != -1)){
	$_GET["estado"] = $_POST["estado"];
			if ($sql1 == "")
					$sql1='WHERE estado='.$_POST["estado"];
			else
					$sql1 =$sql1.' AND estado='.$_POST["estado"];
		}

		if ((isset($_POST["prioridad"])) AND ($_POST["prioridad"] != -1)) {
	$_GET["prioridad"]=$_POST["prioridad"];
				if ($sql1 == "")
						$sql1='WHERE prioridad='.$_POST["prioridad"];
				else
						$sql1 =$sql1.' and prioridad='.$_POST["prioridad"];
		}

		if ((isset($_POST["grupo"])) AND ($_POST["grupo"] != -1)) {
	$_GET["grupo"] = $_POST["grupo"];
				if ($sql1 == "")
						$sql1='WHERE id_grupo='.$_POST["grupo"];
				else
						$sql1 =$sql1.' AND id_grupo='.$_POST["grupo"];
		}
	}


$sql0="SELECT * FROM tincidencia ".$sql1." ORDER BY actualizacion DESC";
$sql1_count="SELECT COUNT(id_incidencia) FROM tincidencia ".$sql1;
$sql1=$sql0;
echo "<h2>".$lang_label["incident_manag"]."</h2>";
echo "<h3>".$lang_label["manage_incidents"]."<a href='help/".$help_code."/chap4.php#4' target='_help' class='help'>&nbsp;<span>".$lang_label["help"]."</span></a></h3>";
if (isset($_POST['operacion'])){
	echo "<h3>".$lang_label["incident_view_filter"]." - ".$_POST['operacion']."</h3>";
}

?>
<form name='visualizacion' method='POST' action='index.php?sec=incidencias&sec2=operation/incidents/incident'>
<table border="0" cellpadding=3 cellspacing=3>
<tr>
<td valign="middle">
<h3><?php echo $lang_label["filter"]; ?></h3>
<select name="estado" onChange="javascript:this.form.submit();" class="w155"> 
<?php
	// Tipo de estado (Type)
	// 0 - Abierta / Sin notas (Open without notes)
	// 1 - Abierta / Notas aniadidas  (Open with notes)
	// 2 - Descartada (Not valid)
	// 3 - Caducada (out of date)
	// 13 - Cerrada (closed)

	if (isset($_GET["estado"])){
		echo "<option value='".$_GET["estado"]."'>";
		switch ($_GET["estado"]){
			case -1: echo $lang_label["all_inc"]; break;
			case 0: echo $lang_label["opened_inc"]; break;
			case 13: echo $lang_label["closed_inc"]; break;
			case 2: echo $lang_label["rej_inc"]; break;
			case 3: echo $lang_label["exp_inc"]; break;
		}
	}

	echo "<option value='-1'>".$lang_label["all_inc"];
	echo "<option value='0'>".$lang_label["opened_inc"];
	echo "<option value='13'>".$lang_label["closed_inc"];
	echo "<option value='2'>".$lang_label["rej_inc"];
	echo "<option value='3'>".$lang_label["exp_inc"];
?>
	</select>
	</td>
	<td valign="middle"><noscript><input type="submit" class="sub" value="<?php echo $lang_label["show"] ?>" border="0"></noscript>
	</td>
	<td rowspan="5" class="f9l30t">
	<h3><?php echo $lang_label["status"] ?></h3>
	<img src='images/dot_red.gif'> - <?php echo $lang_label["opened_inc"] ?><br>
	<img src='images/dot_yellow.gif'> - <?php echo $lang_label["openedcom_inc"] ?><br>
	<img src='images/dot_blue.gif'> - <?php echo $lang_label["rej_inc"] ?><br>
	<img src='images/dot_green.gif'> - <?php echo $lang_label["closed_inc"] ?><br>
	<img src='images/dot_white.gif'> - <?php echo $lang_label["exp_inc"] ?></td>

	<td rowspan="5" class="f9l30t">
	<h3><?php echo $lang_label["priority"] ?></h3>
	<img src='images/dot_red.gif'><img src='images/dot_red.gif'><img src='images/dot_red.gif'> - <?php echo $lang_label["very_serious"] ?><br>
	<img src='images/dot_yellow.gif'><img src='images/dot_red.gif'><img src='images/dot_red.gif'> - <?php echo $lang_label["serious"] ?><br>
	<img src='images/dot_yellow.gif'><img src='images/dot_yellow.gif'><img src='images/dot_red.gif'> - <?php echo $lang_label["medium"] ?><br>
	<img src='images/dot_green.gif'><img src='images/dot_yellow.gif'><img src='images/dot_yellow.gif'> - <?php echo $lang_label["low"] ?><br>
	<img src='images/dot_green.gif'><img src='images/dot_green.gif'><img src='images/dot_yellow.gif'> - <?php echo $lang_label["informative"] ?><br>
	<img src='images/dot_green.gif'><img src='images/dot_green.gif'><img src='images/dot_green.gif'> - <?php echo $lang_label["maintenance"] ?><br>
	<tr><td>
	<select name="prioridad" onChange="javascript:this.form.submit();" class="w155">
<?php 

if (isset($_GET["prioridad"])){
	echo "<option value=".$_GET["prioridad"].">";
	switch ($_GET["prioridad"]){
		case -1: echo $lang_label["all"]." ".$lang_label["priority"]; break;
		case 0: echo $lang_label["informative"]; break;
		case 1: echo $lang_label["low"]; break;
		case 2: echo $lang_label["medium"]; break;
		case 3: echo $lang_label["serious"]; break;
		case 4: echo $lang_label["very_serious"]; break;
		case 10: echo $lang_label["maintenance"]; break;
	}
}
echo "<option value='-1'>".$lang_label["all"]." ".$lang_label["priority"]; // al priorities (default)
echo '<option value="0">'.$lang_label["informative"];
echo '<option value="1">'.$lang_label["low"];
echo '<option value="2">'.$lang_label["medium"];
echo '<option value="3">'.$lang_label["serious"];
echo '<option value="4">'.$lang_label["very_serious"];
echo '<option value="10">'.$lang_label["maintenance"];
echo "</select></td><td valign='middle¡><noscript>";
echo "<input type='submit' class='sub' value='".$lang_label["show"]."' border='0'></noscript>";
echo "</td>";
echo '<tr><td><select name="grupo" onChange="javascript:this.form.submit();" class="w155">';

if (isset($_GET["grupo"])){
echo "<option value=".$_GET["grupo"].">";
if ($_GET["grupo"] == -1)
	echo $lang_label["all"]." ".$lang_label["groups"]; // all groups (default)
else
	echo dame_nombre_grupo($_GET["grupo"]);
}
echo "<option value='-1'>".$lang_label["all"]." ".$lang_label["groups"]; // all groups (default)
$sql2="SELECT * FROM tgrupo";
$result2=mysql_query($sql2);
while ($row2=mysql_fetch_array($result2)){
	echo "<option value=".$row2["id_grupo"].">".$row2["nombre"];
}

echo "</select></td><td valign='middle'><noscript><input type='submit' class='sub' value='".$lang_label["show"]."' border='0'></noscript></td>";

// Pass search parameters for possible future filter searching by user
if (isset($_GET["usuario"]))
	echo "<input type='hidden' name='usuario' value='".$_GET["usuario"]."'>";
if (isset($_GET["texto"]))
	echo "<input type='hidden' name='texto' value='".$_GET["texto"]."'>";

echo "
	</table>
	</form>
	<br><br>
	<table>";

// Offset adjustment
if (isset($_GET["offset"]))
	$offset=$_GET["offset"];
else
	$offset=0;
$offset_counter=0;
// Prepare index for pagination
$incident_list[]="";
$result2=mysql_query($sql1);

if (!mysql_num_rows($result2)) {
	echo '<div class="nf">'.$lang_label["no_incidents"].'</div><br></table>';
} else {
	while ($row2=mysql_fetch_array($result2)){ // Jump offset records
		$id_group = $row2["id_grupo"];
		if (give_acl($id_usuario, $id_group, "IR") ==1){
		// Only incident read access to view data !
			$incident_list[]=$row2["id_incidencia"];
		}
	}
	// Fill array with data

	// TOTAL incidents
	$total_incidentes = sizeof($incident_list);

	$url = "index.php?sec=incidencias&sec2=operation/incidents/incident";

	// add form filter values for group, priority, state, and search fields: user and text
	if (isset($_GET["grupo"]))
		$url = $url."&grupo=".$_GET["grupo"];
	if (isset($_GET["prioridad"]))
		$url = $url."&prioridad=".$_GET["prioridad"];
	if (isset($_GET["estado"]))
		$url = $url."&estado=".$_GET["estado"];
	if (isset($_GET["usuario"]))
		$url = $url."&usuario=".$_GET["usuario"];
	if (isset($_GET["texto"]))
		$url = $url."&texto=".$_GET["texto"];

	// Show pagination
	pagination ($total_incidentes, $url, $offset);
	echo '<br>';
	// Show headers
	
	echo "<table cellpadding='3' cellspacing='3' width='770'>";
	echo "<tr>";
	echo "<th width='43'>ID";
	echo "<th>".$lang_label["status"];
	echo "<th width='165'>".$lang_label["incident"];
	echo "<th width='50'>".$lang_label["priority"];
	echo "<th>".$lang_label["group"];
	echo "<th width='150'>".$lang_label["updated_at"];
	echo "<th>".$lang_label["source"];
	echo "<th width='75'>".$lang_label["in_openedby"];
	echo "<th>".$lang_label["delete"];
	$color = 1;

	// Skip offset records and begin show data
	if ($offset !=0)
		$offset_begin = $offset+1;
	else
		$offset_begin = $offset;

	for ($a=$offset_begin; $a < ($offset + $block_size +1);$a++){
		if (isset($incident_list[$a])){
			$id_incidente = $incident_list[$a];
		} else {
			$id_incidente ="";
		}
		if ($id_incidente != ""){
			$sql="SELECT * FROM tincidencia WHERE id_incidencia = $id_incidente";
			$result=mysql_query($sql);
			$row=mysql_fetch_array($result);
			$id_group = $row["id_grupo"];
				if ($color == 1){
					$tdcolor = "datos";
					$color = 0;
				}
				else {
					$tdcolor = "datos2";
					$color = 1;
				}
			if (give_acl($id_usuario, $id_group, "IR") ==1){ // Only incident read access to view data !
				$offset_counter++;
				$note_number = dame_numero_notas($row["id_incidencia"]);
				echo "<tr>";
				echo "<td class='$tdcolor' align='center'><a href='index.php?sec=incidencias&sec2=operation/incidents/incident_detail&id=".$row["id_incidencia"]."'>".$row["id_incidencia"]."</a>";

				// Check for attachments in this incident
				$result3=mysql_query("SELECT * FROM tattachment WHERE id_incidencia = ".$row["id_incidencia"]);
				mysql_fetch_array($result3);
				if (mysql_affected_rows() > 0)
				echo '&nbsp;&nbsp;<img src="images/file.gif" align="middle">';

				// Tipo de estado  (Type)
				// 0 - Abierta / Sin notas (Open, no notes)
				// 1 - Abierta / Notas anyadidas (Open with notes)
				// 2 - Descartada (not valid)
				// 3 - Caducada (out of date)
				// 13 - Cerrada (closed)

				// Verify if the status changes
				if (($row["estado"] == 0) && ($note_number >0 )){
					$row["estado"] = 1;
				}
				echo "<td class='$tdcolor' align='center'>";
				switch ($row["estado"]) {
				case 0: echo "<img src='images/dot_red.gif'>";
							break;
				case 1: echo "<img src='images/dot_yellow.gif'>";
							break;
				case 2: echo "<img src='images/dot_blue.gif'>";
							break;
				case 3: echo "<img src='images/dot_white.gif'>";
							break;
				case 13: echo "<img src='images/dot_green.gif'>";
							break;
				}
				echo "<td class='$tdcolor'><a href='index.php?sec=incidencias&sec2=operation/incidents/incident_detail&id=".$row["id_incidencia"]."'>".substr(salida_limpia($row["titulo"]),0,27);
				echo "<td class='$tdcolor'>";
				switch ( $row["prioridad"] ){
					case 0: echo "<img src='images/dot_green.gif'>"."<img src='images/dot_green.gif'>"."<img src='images/dot_yellow.gif'>"; break;
					case 1: echo "<img src='images/dot_green.gif'>"."<img src='images/dot_yellow.gif'>"."<img src='images/dot_yellow.gif'>"; break;
					case 2: echo "<img src='images/dot_yellow.gif'>"."<img src='images/dot_yellow.gif'>"."<img src='images/dot_red.gif'>"; break;
					case 3: echo "<img src='images/dot_yellow.gif'>"."<img src='images/dot_red.gif'>"."<img src='images/dot_red.gif'>"; break;
					case 4: echo "<img src='images/dot_red.gif'>"."<img src='images/dot_red.gif'>"."<img src='images/dot_red.gif'>"; break;
					case 10: echo "<img src='images/dot_green.gif'>"."<img src='images/dot_green.gif'>"."<img src='images/dot_green.gif'>"; break;
				}
				/*
				case 0: echo $lang_label["informative"]; break;
				case 1: echo $lang_label["low"]; break;
				case 2: echo $lang_label["medium"]; break;
				case 3: echo $lang_label["serious"]; break;
				case 4: echo $lang_label["very_serious"]; break;
				case 10: echo $lang_label["maintenance"]; break;
				*/
				echo "<td class='$tdcolor'>".dame_nombre_grupo($row["id_grupo"]);
				echo "<td class='$tdcolor'>".$row["actualizacion"];
				echo "<td class='$tdcolor'>".$row["origen"];
				echo "<td class='$tdcolor'><a href='index.php?sec=usuario&sec2=operation/users/user_edit&ver=".$row["id_usuario"]."'><a href='#' class='tip'>&nbsp;<span>".dame_nombre_real($row["id_usuario"])."</span></a>".substr($row["id_usuario"], 0, 8)."</a></td>";
				$id_author_inc = $row["id_usuario"];
				if ((give_acl($id_usuario, $id_group, "IM") ==1) OR
				($_SESSION["id_usuario"] == $id_author_inc) ){
				// Only incident owners or incident manager
				// from this group can delete incidents
					echo "<td class='$tdcolor' align='center'><a href='index.php?sec=incidencias&sec2=operation/incidents/incident&quick_delete=".$row["id_incidencia"]."' onClick='if (!confirm(\' ".$lang_label["are_you_sure"]."\')) return false;'><img src='images/cancel.gif' border='0'></a></td>";
				}
			} // if ACL is correct
		}
	}
	echo "<tr><td colspan='9'><div class='raya'></div>"	;
}

if (give_acl($_SESSION["id_usuario"], 0, "IW")==1) {
	echo "<tr><td align='right' colspan='9'>";
	echo "<form method='post' action='index.php?sec=incidencias&sec2=operation/incidents/incident_detail&insert_form'>";
	echo "<input type='submit' class='sub' name='crt' value='".$lang_label["create_incident"]."'></form>";
}
echo "</td></tr></table>";

?>