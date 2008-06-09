<?php

// Pandora FMS - the Free monitoring system
// ========================================
// Copyright (c) 2004-2007 Sancho Lerena, slerena@openideas.info
// Copyright (c) 2005-2007 Artica Soluciones Tecnologicas
// Copyright (c) 2004-2007 Raul Mateos Martin, raulofpandora@gmail.com
// Copyright (c) 2006-2007 Jose Navarro jose@jnavarro.net
// Copyright (c) 2006-2007 Jonathan Barajas, jonathan.barajas[AT]gmail[DOT]com

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
// Load global vars
?>
<script language="javascript">
	/* Function to hide/unhide a specific Div id */
	function toggleDiv (divid){
		if (document.getElementById(divid).style.display == 'none'){
			document.getElementById(divid).style.display = 'block';
		} else {
			document.getElementById(divid).style.display = 'none';
		}
	}
</script>
<?PHP

require("include/config.php");

if (comprueba_login() != 0) {
 	audit_db("Noauth",$REMOTE_ADDR, "No authenticated acces","Trying to access event viewer");
	require ("general/noaccess.php");
	exit;
}

if (isset($_GET["id_grupo"]))
	$id_grupo = $_GET["id_grupo"];
else
	$id_grupo = 0;

$id_user=$_SESSION['id_usuario'];
if (give_acl($id_user, $id_grupo, "IR") != 1){
 	// Doesn't have access to this page
	audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access to incident ".$id_inc." '".$titulo."'");
	include ("general/noaccess.php");
	exit;
}

$id_grupo = "";
$creacion_incidente = "";

// EDITION MODE
if (isset($_GET["id"])){
	$creacion_incidente = 0;
	$id_inc = $_GET["id"];
	$iduser_temp=$_SESSION['id_usuario'];
	// Obtain group of this incident
	$sql1='SELECT * FROM tincidencia WHERE id_incidencia = '.$id_inc;
	$result=mysql_query($sql1);
	$row=mysql_fetch_array($result);
	// Get values
	$titulo = $row["titulo"];
	$texto = $row["descripcion"];
	$inicio = $row["inicio"];
	$actualizacion = $row["actualizacion"];
	$estado = $row["estado"];
	$prioridad = $row["prioridad"];
	$origen = $row["origen"];
	$usuario = $row["id_usuario"];
	$nombre_real = dame_nombre_real($usuario);
	$id_grupo = $row["id_grupo"];
	$id_creator = $row["id_creator"];
	$grupo = dame_nombre_grupo($id_grupo);

	// Note add
	if (isset($_GET["insertar_nota"])){
		$id_inc = entrada_limpia($_POST["id_inc"]);
		$timestamp = entrada_limpia($_POST["timestamp"]);
		$nota = entrada_limpia($_POST["nota"]);
		$id_usuario=$_SESSION["id_usuario"];

		$sql1 = "INSERT INTO tnota (id_usuario,timestamp,nota) 
		VALUES ('".$id_usuario."','".$timestamp."','".$nota."')";
		$res1=mysql_query($sql1);
		if ($res1) { echo "<h3 class='suc'>".$lang_label["create_note_ok"]."</h3>"; }

		$sql2 = "SELECT * FROM tnota WHERE id_usuario = '".$id_usuario."' AND timestamp = '".$timestamp."'";
		$res2=mysql_query($sql2);
		$row2=mysql_fetch_array($res2);
		$id_nota = $row2["id_nota"];

		$sql3 = "INSERT INTO tnota_inc (id_incidencia, id_nota) VALUES (".$id_inc.",".$id_nota.")";
		$res3=mysql_query($sql3);

		$sql4 = "UPDATE tincidencia SET actualizacion = '".$timestamp."' WHERE id_incidencia = ".$id_inc;
		$res4 = mysql_query($sql4);
	}

	// Delete note
	if (isset($_GET["id_nota"])){
		$note_user = give_note_author ($_GET["id_nota"]);
		if (((give_acl($iduser_temp, $id_grupo, "IM")==1) OR ($note_user == $iduser_temp)) OR ($usuario = $iduser_temp) ) { // Only admins (manage incident) or owners can modify incidents, including their notes
		// But note authors was able to delete this own notes
			$id_nota = $_GET["id_nota"];
			$id_nota_inc = $_GET["id_nota_inc"];
			$query ="DELETE FROM tnota WHERE id_nota = ".$id_nota;
			$query2 = "DELETE FROM tnota_inc WHERE id_nota_inc = ".$id_nota_inc;
			//echo "DEBUG: DELETING NOTE: ".$query."(----)".$query2;
			mysql_query($query);
			mysql_query($query2);
			if (mysql_query($query)) {
				echo "<h3 class='suc'>".$lang_label["del_note_ok"];
			}
		}
	}

	// Delete file
	if (((give_acl($iduser_temp, $id_grupo, "IM")==1) OR ($usuario == $iduser_temp)) AND isset($_GET["delete_file"])){
		$file_id = $_GET["delete_file"];
		$sql2 = "SELECT * FROM tattachment WHERE id_attachment = ".$file_id;
		$res2=mysql_query($sql2);
		$row2=mysql_fetch_array($res2);
		$filename = $row2["filename"];
		$sql2 = "DELETE FROM tattachment WHERE id_attachment = ".$file_id;
		$res2=mysql_query($sql2);
		unlink ($config["attachment_store"]."attachment/pand".$file_id."_".$filename);
	}

	// Upload file
	if ((give_acl($iduser_temp, $id_grupo, "IW")==1) AND isset($_GET["upload_file"])) {
		if (( $_FILES['userfile']['name'] != "" )){ //if file
			$tipo = $_FILES['userfile']['type'];
			if (isset($_POST["file_description"]))
				$description = $_POST["file_description"];
			else
				$description = "No description available";
			// Insert into database
			$filename= $_FILES['userfile']['name'];
			$filesize = $_FILES['userfile']['size'];

			$sql = " INSERT INTO tattachment (id_incidencia, id_usuario, filename, description, size ) VALUES (".$id_inc.", '".$iduser_temp." ','".$filename."','".$description."',".$filesize.") ";

			mysql_query($sql);
			$id_attachment=mysql_insert_id();

			// Copy file to directory and change name
		$nombre_archivo = $config["attachment_store"]."attachment/pand".$id_attachment."_".$filename;

			if (!(copy($_FILES['userfile']['tmp_name'], $nombre_archivo ))){
					echo "<h3 class=error>".$lang_label["attach_error"]."</h3>";
				$sql = " DELETE FROM tattachment WHERE id_attachment =".$id_attachment;
				mysql_query($sql);
			} else {
				// Delete temporal file
				unlink ($_FILES['userfile']['tmp_name']);
			}
		}
	}
} // else Not given id
// Create incident from event... read event data
elseif (isset($_GET["insert_form"])){

		$iduser_temp=$_SESSION['id_usuario'];
		$titulo = "";
		if (isset($_GET["from_event"])){
			$titulo = return_event_description($_GET["from_event"]);
			$descripcion = "";
			$origen = "Pandora FMS event";
		} else {
			$titulo = "";
			$descripcion = "";
			$origen = "";
		}
		$prioridad = 0;
		$id_grupo = 0;
		$grupo = dame_nombre_grupo(1);

		$usuario= $_SESSION["id_usuario"];
		$estado = 0;
		$actualizacion=date("Y/m/d H:i:s");
		$inicio = $actualizacion;
		$id_creator = $iduser_temp;
		$creacion_incidente = 1;
} else {
	audit_db($id_user,$REMOTE_ADDR, "HACK","Trying to create incident in a unusual way");
	no_permission();

}



// ********************************************************************************************************
// ********************************************************************************************************
// Show the form
// ********************************************************************************************************

if ($creacion_incidente == 0)
	echo "<form name='accion_form' method='POST' action='index.php?sec=incidencias&sec2=operation/incidents/incident&action=update'>";
else
	echo "<form name='accion_form' method='POST' action='index.php?sec=incidencias&sec2=operation/incidents/incident&action=insert'>";

if (isset($id_inc)) {
	echo "<input type='hidden' name='id_inc' value='".$id_inc."'>";
}
echo "<h2>".$lang_label["incident_manag"]." &gt; ";
if (isset($id_inc)) {
	echo $lang_label["rev_incident"]." # ".$id_inc;
} else {
	echo $lang_label["create_incident"];
}
echo "</h2>";
echo '<table cellpadding="4" cellspacing="4" class="databox" width="600">';
if ((give_acl($iduser_temp, $id_grupo, "IM")==1) OR ($usuario == $iduser_temp)) {
	echo '<tr><td class="datos"><b>'.$lang_label["incident"].'</b></td>
	<td colspan=3 class="datos"><input type="text" name="titulo" size=70 value="'.$titulo.'">';
} else {
	echo '<tr><td class="datos"><b>'.$lang_label["incident"].'</b><td colspan=3 class="datos"><input type="text" name="titulo" size=70 value="'.$titulo.'" readonly>';
	}
echo '<tr><td class="datos2"><b>'.$lang_label["in_openedwhen"].'</b>';
echo "<td class='datos2' <i>".$inicio."</i>";
echo '<td class="datos2"><b>'.$lang_label["updated_at"].'</b>';
echo "<td class='datos2'><i>".$actualizacion."</i>";
echo '<tr><td class="datos"><b>'.$lang_label["in_openedby"].'</b><td class="datos">';
if ((give_acl($id_user, $id_grupo, "IM")==1) OR ($usuario == $id_user)) {
	echo "<select name='usuario_form' width='200px'>";
	echo "<option value='".$usuario."'>".$usuario." - ".dame_nombre_real($usuario)."</option>";
	$sql1='SELECT * FROM tusuario ORDER BY id_usuario';
	$result=mysql_query($sql1);
	while ($row2=mysql_fetch_array($result)){
		echo "<option value='".$row2["id_usuario"]."'>".$row2["id_usuario"]." - ".$row2["nombre_real"]."</option>";
	}
	echo "</select>";
}
else {
	echo "<input type=hidden name='usuario_form2' value='".$usuario."'>";
	echo $usuario." - (<i><a href='index.php?sec=usuario&sec2=operation/users/user_edit&ver=".$usuario."'>".$nombre_real."</a></i>)";
}
// Tipo de estado
// 0 - Abierta / Sin notas - Open, without notes
// 1 - Abierta / Notas aniadidas - Open, with notes
// 2 - Descartada / Not valid
// 3 - Caducada / Outdated
// 13 - Cerrada / Closed

if ((give_acl($iduser_temp, $id_grupo, "IM")==1) OR ($usuario == $iduser_temp)) {
	echo '<td class="datos"><b>'.$lang_label["status"].'</b>
	<td class="datos">
	<select name="estado_form" class="w135">';
} else {
	echo '<td class="datos"><b>'.$lang_label["status"].'</b>
	<td class="datos">
	<select disabled name="estado_form" class="w135">';
}

switch ( $estado ){
	case 0: echo '<option value="0">'.$lang_label["in_state_0"]; break;
	//case 1: echo '<option value="2">'.$lang_label["in_state_1"]; break;
	case 2: echo '<option value="2">'.$lang_label["in_state_2"]; break;
	case 3: echo '<option value="3">'.$lang_label["in_state_3"]; break;
	case 13: echo '<option value="13">'.$lang_label["in_state_13"]; break;
}

echo '<option value="0">'.$lang_label["in_state_0"];
//echo '<option value="1">'.$lang_label["in_state_1"];
echo '<option value="2">'.$lang_label["in_state_2"];
echo '<option value="3">'.$lang_label["in_state_3"];
echo '<option value="13">'.$lang_label["in_state_13"];
echo '</select></td>';

// Only owner could change source or user with Incident management privileges
if ((give_acl($iduser_temp, $id_grupo, "IM")==1) OR ($usuario == $iduser_temp)) {
	echo '<tr><td class="datos2"><b>'.$lang_label["source"].'</b></td>
	<td class="datos2">
	<select name="origen_form" class="w135">';
} else {
	echo '<tr><td class="datos2"><b>'.$lang_label["source"].'</b></td>
	<td class="datos2">
	<select disabled name="origen_form" class="w135">';
}
// Fill combobox with source (origen)
if ($origen != "")
	echo "<option value='".$origen."'>".$origen;
$sql1='SELECT * FROM torigen ORDER BY origen';
$result=mysql_query($sql1);
while ($row2=mysql_fetch_array($result)){
	echo "<option value='".$row2["origen"]."'>".$row2["origen"]."</option>";
}
echo "</select></td>";

// Group combo
if ((give_acl($iduser_temp, $id_grupo, "IM")==1) OR ($usuario == $iduser_temp)) {
	echo '<td class="datos2"><b>'.$lang_label["group"].'</b></td>
	<td class="datos2">
	<select name="grupo_form" class="w135">';
} else {
	echo '<td class="datos2"><b>'.$lang_label["group"].'</b></td>
	<td class="datos2">
	<select disabled name="grupo_form" class="w135">';
}
if ($id_grupo != 0)
	echo "<option value='".$id_grupo."'>".$grupo;
$sql1='SELECT * FROM tgrupo ORDER BY nombre';
$result=mysql_query($sql1);
while ($row=mysql_fetch_array($result)){
	if (give_acl($iduser_temp, $row["id_grupo"], "IR")==1)
		echo "<option value='".$row["id_grupo"]."'>".$row["nombre"]."</option>";
}

echo '</select></td></tr><tr>';
if ((give_acl($iduser_temp, $id_grupo, "IM")==1) OR ($usuario == $iduser_temp)) {
	echo '<td class="datos"><b>'.$lang_label["priority"].'</b></td>
	<td class="datos"><select name="prioridad_form" class="w135">';
} else {
	echo '<td class="datos"><b>'.$lang_label["priority"].'</b></td>
	<td class="datos"><select disabled name="prioridad_form" class="w135">';
}

switch ( $prioridad ){
	case 0: echo '<option value="0">'.$lang_label["informative"].'</option>'; break;
	case 1: echo '<option value="1">'.$lang_label["low"].'</option>'; break;
	case 2: echo '<option value="2">'.$lang_label["medium"].'</option>'; break;
	case 3: echo '<option value="3">'.$lang_label["serious"].'</option>'; break;
	case 4: echo '<option value="4">'.$lang_label["very_serious"].'</option>'; break;
	case 10: echo '<option value="10">'.$lang_label["maintenance"].'</option>'; break;
}

echo '<option value="0">'.$lang_label["informative"].'</option>';
echo '<option value="1">'.$lang_label["low"].'</option>';
echo '<option value="2">'.$lang_label["medium"].'</option>';
echo '<option value="3">'.$lang_label["serious"].'</option>';
echo '<option value="4">'.$lang_label["very_serious"].'</option>';
echo '<option value="10">'.$lang_label["maintenance"].'</option>';

echo "<td class='datos'><b>Creator</b>
<td class='datos'>".$id_creator." ( <i>".dame_nombre_real($id_creator)." </i>)";

if ((give_acl($iduser_temp, $id_grupo, "IM")==1) OR ($usuario == $iduser_temp)) {
	echo '</select>
	<tr><td class="datos2" colspan="4">
	<textarea name="descripcion" rows="15" cols="85" style="height: 300px;">';
} else {
	echo '</select>
	<tr><td class="datos2" colspan="4">
	<textarea readonly name="descripcion" rows="15" cols="85" style="height: 300px;">';
}
if (isset($texto)) {
	echo $texto;
}
echo "</textarea></td></tr>";

echo '</table><table width="650px">';
echo "<tr><td align='right'>";
// Only if user is the used who opened incident or (s)he is admin

$iduser_temp=$_SESSION['id_usuario'];

if ($creacion_incidente == 0){
	if ((give_acl($iduser_temp, $id_grupo, "IM")==1) OR ($usuario == $iduser_temp)){
		echo '<input type="submit" class="sub upd" name="accion" value="'.$lang_label["in_modinc"].'" border="0">';
	}
} else {
	if (give_acl($iduser_temp, $id_grupo, "IW")) {
		echo '<input type="submit" class="sub wand" name="accion" value="'.$lang_label["create"].'" border="0">';
	}
}
echo "</form>";

if ($creacion_incidente == 0){
	echo "<tr><td align='right'>";
	echo '
	<form method="post" action="index.php?sec=incidencias&sec2=operation/incidents/incident_note&id_inc='.$id_inc.'">
	<input type="hidden" name="nota" value="add">
	<input align=right name="addnote" type="submit" class="sub next" value="'.$lang_label["add_note"].'">
	</form>';
}
echo "</tr></table><br>";

if ($creacion_incidente == 0){

	// ********************************************************************
	// Notes
	// ********************************************************************
	$cabecera=0;
	$sql4='SELECT * FROM tnota_inc WHERE id_incidencia = '.$id_inc;
	$res4=mysql_query($sql4);
	while ($row2=mysql_fetch_array($res4)){
		if ($cabecera == 0) { // Show head only one time
			echo "<h3>".$lang_label["in_notas_t1"]."</h3>";
			echo "<table cellpadding='4' cellspacing='4' class='databox' width='650'>";
			echo "<tr><td>";
			$cabecera = 1;
		}

		$sql3='SELECT * FROM tnota WHERE id_nota = '.$row2["id_nota"].' ORDER BY timestamp DESC';
		$res3=mysql_query($sql3);
		while ($row3=mysql_fetch_array($res3)){
			$timestamp = $row3["timestamp"];
			$nota = $row3["nota"];
			$id_usuario_nota = $row3["id_usuario"];
			// Show data
			echo '<tr><td rowspan="3" class="top"><img src="images/page_white_text.png"></td><td class="datos" width=40><b>'.$lang_label["author"].': </b><td class="datos">';
			$usuario = $id_usuario_nota;
			$nombre_real = dame_nombre_real($usuario);
			echo $usuario." - (<i><a href='index.php?sec=usuario&sec2=operation/users/user_edit&ver=".$usuario."'>".$nombre_real."</a></i>)";

			// Delete comment, only for admins
			if ((give_acl($iduser_temp, $id_grupo, "IM")==1) OR ($usuario == $iduser_temp)) {
				$myurl="index.php?sec=incidencias&sec2=operation/incidents/incident_detail&id=".$id_inc."&id_nota=".$row2["id_nota"]."&id_nota_inc=".$row2["id_nota_inc"];
				echo '<td rowspan="3" class="top" width="60" align="center"><a href="'.$myurl.'"><img src="images/cross.png" align="middle" border="0"></a>';
			}
			echo '<tr><td class="datos"><b>'.$lang_label["date"].': </b><td class="datos"><i>'.$timestamp.'</i></td></tr>';
			echo '<tr><td colspan="2" class="datos"> ';
			echo '<table border="0" cellpadding="4" cellspacing="4" style="width: 580px">';
			echo '<tr><td class="datos2" align="justify">';
			echo salida_limpia ($nota);
			echo "</td></tr>";
			echo '</table>';
		}
	}
	if ($cabecera == 1){
		echo "</table>"; // note table
	}
	echo "</form></table>";

	// ************************************************************
	// Files attached to this incident
	// ************************************************************

	// Attach head if there's attach for this incident
	$att_fil=mysql_query("SELECT * FROM tattachment WHERE id_incidencia = ".$id_inc);

	if (mysql_num_rows($att_fil)){
		echo "<h3>".$lang_label["attached_files"]."</h3>";
		echo "<table cellpadding='4' cellspacing='4' class='databox' width='650'>";
		echo "<tr>
			<th class=datos>".$lang_label["filename"]."</th>
			<th class=datos>".$lang_label["description"]."</th>
			<th class=datos>".$lang_label["size"]."</th>
			<th class=datos>".$lang_label["delete"]."</th></tr>";

		while ($row=mysql_fetch_array($att_fil)){
			echo "<tr><td class=datos><img src='images/disk.png' border=0 align='top'> &nbsp;&nbsp;<a target='_new' href='attachment/pand".$row["id_attachment"]."_".$row["filename"]."'><b>".$row["filename"]."</b></a>";
			echo "<td class=datos>".$row["description"];
			echo "<td class=datos>".$row["size"];

			if (give_acl($iduser_temp, $id_grupo, "IM")==1){ // Delete attachment
				echo '<td class=datos align="center"><a href="index.php?sec=incidencias&sec2=operation/incidents/incident_detail&id='.$id_inc.'&delete_file='.$row["id_attachment"].'"><img src="images/cross.png" border=0>';
			}

		}
		echo "</td></tr></table>";
	}
	// ************************************************************
	// Upload control
	// ************************************************************

	// Upload control
	if (give_acl($iduser_temp, $id_grupo, "IW")==1){
		echo "<h3>".$lang_label["attachfile"];
		?>
		<A HREF="javascript:;" onmousedown="toggleDiv('file_control');">
		<?PHP
		echo "<img src='images/disk.png'>";
		echo "</a></h3>";
		echo "<div id='file_control' style='display:none'>";
		
		echo '<table cellpadding="4" cellspacing="3" class="databox" width="400">
		<tr>
		<td class="datos">'.$lang_label["filename"].'</td>
		<td class="datos"><form method="post" action="index.php?sec=incidencias&sec2=operation/incidents/incident_detail&id='.$id_inc.'&upload_file=1" enctype="multipart/form-data">
		<input type="file" name="userfile" value="userfile" class="sub" size="40">
		</td></tr>
		<tr><td class="datos2">'.$lang_label["description"].'</td>
		<td class="datos2" colspan="3">
		<input type="text" name="file_description" size="47">
		</td></tr>
		</table>
		<table width="400px">
		<tr><td style="text-align: right;">
		<input type="submit" name="upload" value="'.$lang_label["upload"].'" class="sub wand">
		</td></tr></table><br>';
		echo "</div>";
	}

	
} // create mode

?>
