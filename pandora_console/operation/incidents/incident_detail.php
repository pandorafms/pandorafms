<?php
// Pandora - The Free Monitoring System
// This code is protected by GPL license.
// Este codigo esta protegido por la licencia GPL.
// Sancho Lerena <slerena@gmail.com>, 2003-2006
// Raul Mateos <raulofpandora@gmail.com>, 2005-2006

// Load global vars
require("include/config.php");
//require("include/functions.php");
//require("include/functions_db.php");
if (comprueba_login() == 0) {
$id_grupo = "";
$creacion_incidente = "";
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
		
		// Has access to this page ???
		if (give_acl($iduser_temp, $id_grupo, "IR")==1){
			// Note add
			if (isset($_GET["insertar_nota"])){
				
				$id_inc = entrada_limpia($_POST["id_inc"]);
				$timestamp = entrada_limpia($_POST["timestamp"]);
				$nota = entrada_limpia($_POST["nota"]);
				$id_usuario=$_SESSION["id_usuario"];
				
				$sql1 = "INSERT INTO tnota (id_usuario,timestamp,nota) VALUES ('".$id_usuario."','".$timestamp."','".$nota."')";
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
		
			// Modify incident
			if (isset($_POST["accion"])){
				$id_inc = $_POST["id_inc"];
				if ((give_acl($iduser_temp, $id_grupo, "IM")==1) OR ($usuario == $iduser_temp)) { // Only admins (manage incident) or owners can modify incidents
					// Edicion !!
					if ($_POST["accion"] == $lang_label["in_modinc"]){ // Modify Incident
						$id_author_inc = give_incident_author($id_inc);
						$titulo = entrada_limpia($_POST["titulo"]);
						$descripcion = entrada_limpia($_POST['descripcion']);
						$origen = entrada_limpia($_POST['origen']);
						$prioridad = entrada_limpia($_POST['prioridad']);	
						$grupo = entrada_limpia($_POST['grupo']);
						$usuario= entrada_limpia($_POST["usuario"]);
						$estado = entrada_limpia($_POST["estado"]);
						$ahora=date("Y/m/d H:i:s");
						$sql = "UPDATE tincidencia SET actualizacion = '".$ahora."', titulo = '".$titulo."', origen= '".$origen."', estado = '".$estado."', id_grupo = '".$grupo."', id_usuario = '".$usuario."', prioridad = '".$prioridad."', descripcion = '".$descripcion."' WHERE id_incidencia = ".$id_inc;
						$result=mysql_query($sql);
						if ($result) echo "<h3 class='suc'>".$lang_label["upd_incid_ok"]."</h3>"; 
						// Re-read data for correct presentation
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
						$grupo = dame_nombre_grupo($id_grupo);
					}
				} else {
					audit_db($id_author_inc,$REMOTE_ADDR,"ACL Forbidden","User ".$_SESSION["id_usuario"]." try to update incident");
					echo "<h3 class='error'>".$lang_label["upd_incid_no"]."</h3>";
					no_permission();
				}
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
					if (mysql_query($query)) echo "<h3 class='suc'>".$lang_label["del_note_ok"]; 
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
				unlink ($attachment_store."attachment/pand".$file_id."_".$filename);
			}
			
			// Upload file
			if ((give_acl($iduser_temp, $id_grupo, "IW")==1) AND isset($_GET["upload_file"])) {
				if (( $_FILES['userfile']['name'] != "" ) && ($userfile != "none")){ //if file
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
				$nombre_archivo = $attachment_store."attachment/pand".$id_attachment."_".$filename;

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
		}
	} else { // Not given id
		// Insert data !
		if (isset($_POST["accion"]) and ($_POST["accion"] == $lang_label["create"])) {
			$iduser_temp=$_SESSION['id_usuario'];		
			// Read input variables
			$titulo = entrada_limpia($_POST['titulo']);
			$inicio = date("Y/m/d H:i:s");
			$descripcion = entrada_limpia($_POST['descripcion']);
			$texto = $descripcion; // to view in textarea after insert
			$origen = entrada_limpia($_POST['origen']);
			$prioridad = entrada_limpia($_POST['prioridad']);
			$grupo = entrada_limpia($_POST['grupo']);
			$usuario= entrada_limpia($_SESSION["id_usuario"]);
			$actualizacion = $inicio;
			$id_creator = $iduser_temp;
			$estado = 0; // if the indicent is new, state (estado) is 0
			$sql = " INSERT INTO tincidencia (inicio,actualizacion,titulo,descripcion,id_usuario,origen,estado,prioridad,id_grupo, id_creator) VALUES ('".$inicio."','".$actualizacion."','".$titulo."','".$descripcion."','".$usuario."','".$origen."','".$estado."','".$prioridad."','".$grupo."','".$id_creator."') ";
			if (give_acl($iduser_temp, $grupo, "IW")==1){
				if (mysql_query($sql)) echo "<h3 class='suc'>".$lang_label["create_incid_ok"]."</h3>";
				$id_inc=mysql_insert_id();
			} else 
				no_permission();
		} elseif (isset($_GET["insert_form"])){ // Create from to insert
			$iduser_temp=$_SESSION['id_usuario'];
			$titulo = "";
			$descripcion = "";
			$origen = "";
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
			no_permission();
		}
	}
	
	// Has access to this page ???
	if (give_acl($iduser_temp, $id_grupo, "IR")==1){
		// ********************************************************************************************************	
		// ********************************************************************************************************
		// Show the form
		// ********************************************************************************************************
		
		if ($creacion_incidente == 0)
			echo "<form name='accion_form' method='POST' action='index.php?sec=incidencias&sec2=operation/incidents/incident_detail&id=".$id_inc."'>";
		else 
			echo "<form name='accion_form' method='POST' action='index.php?sec=incidencias&sec2=operation/incidents/incident_detail'>";
			
		if (isset($id_inc)) {echo "<input type='hidden' name='id_inc' value='".$id_inc."'>";}
		echo "<h2>".$lang_label["incident_manag"]."</h2>";
		if (isset($id_inc)) {echo "<h3>".$lang_label["rev_incident"]." # ".$id_inc." <a href='help/<?php echo $help_code;?>/chap4.php#42' target='_help' class='help'>&nbsp;<span>".$lang_label["help"]."</span></a></h3>";} 
			else {echo "<h3>".$lang_label["create_incident"]."<a href='help/".$help_code."/chap4.php#41' target='_help' class='help'>&nbsp;<span>".$lang_label["help"]."</span></a></h3>";}
		echo '<table cellpadding=3 cellspacing=3 border=0 width=600>';
		if ((give_acl($iduser_temp, $id_grupo, "IM")==1) OR ($usuario == $iduser_temp)) 
			echo '<tr><td class="lb" rowspan="6" width="5"><td class="datos"><b>'.$lang_label["incident"].'</b><td colspan=3 class="datos"><input type="text" name="titulo" size=70 value="'.$titulo.'">';
		else
			echo '<tr><td class="datos"><b>'.$lang_label["incident"].'</b><td colspan=3 class="datos"><input type="text" name="titulo" size=70 value="'.$titulo.'" readonly>';
		echo '<tr><td class="datos"><b>'.$lang_label["in_openedwhen"].'</b>';
		echo "<td class='datos' <i>".$inicio."</i>";
		echo '<td class="datos"><b>'.$lang_label["updated_at"].'</b>';
		echo "<td class='datos'><i>".$actualizacion."</i>";
		echo '<tr><td class="datos"><b>'.$lang_label["in_openedby"].'</b><td class="datos">';
		if ((give_acl($iduser_temp, $id_grupo, "IM")==1) OR ($usuario == $iduser_temp)) {
			echo "<select name='usuario' class='w200'>";
			echo "<option value='".$usuario."'>".$usuario." - ".dame_nombre_real($usuario);
			$sql1='SELECT * FROM tusuario ORDER BY id_usuario';
			$result=mysql_query($sql1);
			while ($row2=mysql_fetch_array($result)){
				echo "<option value='".$row2["id_usuario"]."'>".$row2["id_usuario"]." - ".$row2["nombre_real"];
			}
			echo "</select>";
		}
		else {
			echo "<input type=hidden name='usuario' value='".$usuario."'>";
			echo $usuario." - (<i><a href='index.php?sec=usuario&sec2=operation/users/user_edit&ver=".$usuario."'>".$nombre_real."</a></i>)";
		}
		// Tipo de estado 
		// 0 - Abierta / Sin notas - Open, without notes
		// 1 - Abierta / Notas aniadidas - Open, with notes
		// 2 - Descartada / Not valid
		// 3 - Caducada / Outdated
		// 13 - Cerrada / Closed

		if ((give_acl($iduser_temp, $id_grupo, "IM")==1) OR ($usuario == $iduser_temp)) 
			echo '<td class="datos"><b>'.$lang_label["status"].'</b><td class="datos"><select name="estado" class="w135">';
		else 
			echo '<td class="datos"><b>'.$lang_label["status"].'</b><td class="datos"><select disabled name="estado" class="w135">';
			
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
		echo '</select>';

		if ((give_acl($iduser_temp, $id_grupo, "IM")==1) OR ($usuario == $iduser_temp)) 
			echo '<tr><td class="datos"><b>'.$lang_label["source"].'</b><td class="datos"><select name="origen" class="w135">';
		else
			echo '<tr><td class="datos"><b>'.$lang_label["source"].'</b><td class="datos"><select disabled name="origen" class="w135">';
		
		// Fill combobox with source (origen)
		if ($origen != "")
			echo "<option value='".$origen."'>".$origen;
		$sql1='SELECT * FROM torigen order by origen';
		$result=mysql_query($sql1);
		while ($row2=mysql_fetch_array($result)){
			echo "<option value='".$row2["origen"]."'>".$row2["origen"];
		}
		echo "</select>";	
		
		// Group combo
		if ((give_acl($iduser_temp, $id_grupo, "IM")==1) OR ($usuario == $iduser_temp)) 
			echo '<td class="datos"><b>'.$lang_label["group"].'</b><td class="datos"><select name="grupo" class="w135">';
		else 
			echo '<td class="datos"><b>'.$lang_label["group"].'</b><td class="datos"><select disabled name="grupo" class="w135">';
		if ($id_grupo != 0)
			echo "<option value='".$id_grupo."'>".$grupo;
		$sql1='SELECT * FROM tgrupo ORDER BY nombre';
		$result=mysql_query($sql1);
		while ($row=mysql_fetch_array($result)){
			if (give_acl($iduser_temp, $row["id_grupo"], "IR")==1)
				echo "<option value='".$row["id_grupo"]."'>".$row["nombre"];
		}
		
		echo '</select><tr>';
		if ((give_acl($iduser_temp, $id_grupo, "IM")==1) OR ($usuario == $iduser_temp)) 
			echo '<td class="datos"><b>'.$lang_label["priority"].'</b><td class="datos"><select name="prioridad" class="w135">';
		else
			echo '<td class="datos"><b>'.$lang_label["priority"].'</b><td class="datos"><select disabled name="prioridad" class="w135">';
		
		switch ( $prioridad ){
			case 0: echo '<option value="0">'.$lang_label["informative"]; break;
			case 1: echo '<option value="1">'.$lang_label["low"]; break;
			case 2: echo '<option value="2">'.$lang_label["medium"]; break;
			case 3: echo '<option value="3">'.$lang_label["serious"]; break;
			case 4: echo '<option value="4">'.$lang_label["very_serious"]; break;
			case 10: echo '<option value="10">'.$lang_label["maintenance"]; break;
		}
		
		echo '<option value="0">'.$lang_label["informative"]; 
		echo '<option value="1">'.$lang_label["low"]; 
		echo '<option value="2">'.$lang_label["medium"];
		echo '<option value="3">'.$lang_label["serious"]; 
		echo '<option value="4">'.$lang_label["very_serious"]; 
		echo '<option value="10">'.$lang_label["maintenance"]; 
		
		echo "<td class=datos><b>Creator</b><td class='datos'>".$id_creator." ( <i>".dame_nombre_real($id_creator)." </i>)";
		
		if ((give_acl($iduser_temp, $id_grupo, "IM")==1) OR ($usuario == $iduser_temp)) 
			echo '</select><tr><td class="datos" colspan="4"><textarea name="descripcion" rows="15" cols="95">';
		else
			echo '</select><tr><td class="datos" colspan="4"><textarea readonly name="descripcion" rows="15" cols="95">';
		if (isset($texto)) {echo $texto;}
		echo "</textarea>";
		
		echo "<tr><td colspan='2' style='padding-left: 18px;'>";
		// Only if user is the used who opened incident or (s)he is admin
		
		$iduser_temp=$_SESSION['id_usuario'];
	
		if ($creacion_incidente == 0){
			if ((give_acl($iduser_temp, $id_grupo, "IM")==1) OR ($usuario == $iduser_temp)){
				echo '<input type="submit" class="sub" name="accion" value="'.$lang_label["in_modinc"].'" border="0">';
			}
		}
		else
			if (give_acl($iduser_temp, $id_grupo, "IW")) {
				echo '<input type="submit" class="sub" name="accion" value="'.$lang_label["create"].'" border="0">';
			}
		
		echo "</form>";
		
		echo "<td colspan=2>";
		if ($creacion_incidente == 0){
			echo "<td style='text-align: right; padding-right: 40px;'>";
			echo '<form method="post" action="index.php?sec=incidencias&sec2=operation/incidents/incident_note&id_inc='.$id_inc.'"><input type="hidden" name="nota" value="add"><input align=right name="addnote" type="submit" class="sub" value="'.$lang_label["add_note"].'"></form>';
		}
		echo "</table><br>";

		if ($creacion_incidente == 0){
		// Upload control
			if (give_acl($iduser_temp, $id_grupo, "IW")==1){
				echo "<table cellpadding=3 cellspacing=3 border=0 width='400'>";
				echo "<tr><td colspan='3'><b>".$lang_label["attachfile"]."</b>";
				echo "<tr><td class='lb' rowspan='2' width='5'><td class=datos>";
				echo $lang_label["filename"].'<td class=datos colspan=3><form method="post" action="index.php?sec=incidencias&sec2=operation/incidents/incident_detail&id='.$id_inc.'&upload_file=1" enctype="multipart/form-data">';
				echo '<input type="file" name="userfile" value="userfile" class="sub" size="27">';			
				echo '<tr><td class=datos>'.$lang_label["description"].'<td class=datos colspan=3><input type="text" name="file_description" size=34>';
				echo '<tr><td colspan="4" style="text-align: right; padding-right: 55px;"><input type="submit" name="upload" value="'.$lang_label["upload"].'" class="sub">';
				echo '</td></tr></table><br>';
			}
			// ************************************************************
			// Files attached to this incident
			// ************************************************************

			// Attach head if there's attach for this incident
			$att_fil=mysql_query("SELECT * FROM tattachment WHERE id_incidencia = ".$id_inc);
			
			if (mysql_num_rows($att_fil))
			{
				echo "<table cellpadding='3' cellspacing='3' border='0' width='650'>";
				echo "<tr><td>";
				echo "<h3>".$lang_label["attached_files"]."</h3>";
				echo "</td></tr><td>";
				echo "<table width='650'><tr><th class=datos>".$lang_label["filename"];
				echo "<th class=datos>".$lang_label["description"];
				echo "<th class=datos>".$lang_label["size"];
				echo "<th class=datos>".$lang_label["delete"];
			
				while ($row=mysql_fetch_array($att_fil)){
					echo "<tr><td class=datos><a target='_new' href='attachment/pand".$row["id_attachment"]."_".$row["filename"]."'><img src='images/file.gif' border=0 align='middle'> ".$row["filename"]."</a>";
					echo "<td class=datos>".$row["description"];
					echo "<td class=datos>".$row["size"];
					
					if (give_acl($iduser_temp, $id_grupo, "IM")==1){ // Delete attachment
						echo '<td class=datos align="center"><a href="index.php?sec=incidencias&sec2=operation/incidents/incident_detail&id='.$id_inc.'&delete_file='.$row["id_attachment"].'"><img src="images/delete.gif" border=0>';
					}
					
				}
				echo "<tr><td colspan='4'><div class='raya'></div></td></tr></table></table><br>";
			}
			// ********************************************************************
			// Notes
			// ********************************************************************
			$cabecera=0;
			$sql4='SELECT * FROM tnota_inc WHERE id_incidencia = '.$id_inc;
			$res4=mysql_query($sql4);
			while ($row2=mysql_fetch_array($res4)){
				if ($cabecera == 0) { // Show head only one time
					echo "<table cellpadding='3' cellspacing='3' border='0' class='w550'>";
					echo "<tr><td>";
					echo "<h3>".$lang_label["in_notas_t1"]."</h3>";
					echo "<table cellpadding='3' cellspacing='3' border='0'>";
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
					echo '<tr><td rowspan="3" class="top"><img src="images/nota.gif"></td><td class="datos" width=40><b>'.$lang_label["author"].': </b><td class="datos">';
					$usuario = $id_usuario_nota;
					$nombre_real = dame_nombre_real($usuario);
					echo $usuario." - (<i><a href='index.php?sec=usuario&sec2=operation/users/user_edit&ver=".$usuario."'>".$nombre_real."</a></i>)";
					
					// Delete comment, only for admins
					if ((give_acl($iduser_temp, $id_grupo, "IM")==1) OR ($usuario == $iduser_temp)) {
						$myurl="index.php?sec=incidencias&sec2=operation/incidents/incident_detail&id=".$id_inc."&id_nota=".$row2["id_nota"]."&id_nota_inc=".$row2["id_nota_inc"];
						echo '<td rowspan="3" class="top" width="60" align="center"><a href="'.$myurl.'"><img src="images/delete.gif" align="middle" border="0"> '.$lang_label["delete"].'</a>';
					}
					echo '<tr><td class="datos"><b>'.$lang_label["date"].': </b><td class="datos"><i>'.$timestamp.'</i></td></tr>';
					echo '<tr><td colspan="2" class="datos"> ';
					echo '<table border="0" cellpadding="5" cellspacing="5" style="width: 450px"><tr><td class="f9" align="justify">';
					echo salida_limpia($nota);
					echo '</table>';
					echo '<tr><td colspan="3"><div class="sep"></div></td></tr>';
				}
			}
			if ($cabecera == 1){
				echo "</table>"; // note table
			}
			echo "</form></table>";
		} // create mode
	}
	else { // Doesn't have access to this page
		audit_db($id_usuario,$REMOTE_ADDR, "ACL Violation","Trying to access to incident ".$id_inc." '".$titulo."'");
		include ("general/noaccess.php");
	}
	
} // fin pagina - end page

?>