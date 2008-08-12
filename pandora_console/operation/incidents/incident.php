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

$accion = "";
require ("include/config.php");

check_login ();

if (! give_acl ($config['id_user'], 0, "IR")) {
	audit_db($config['id_user'],$REMOTE_ADDR, "ACL Violation","Trying to access incident viewer");
	require ("general/noaccess.php");
	exit;
}

// Take input parameters

// Offset adjustment
if (isset($_GET["offset"]))
	$offset=$_GET["offset"];
else
	$offset=0;

// Delete incident
if (isset($_GET["quick_delete"])){
	$id_inc = $_GET["quick_delete"];
	$sql2="SELECT * FROM tincidencia WHERE id_incidencia=".$id_inc;
	$result2=mysql_query($sql2);
	$row2=mysql_fetch_array($result2);
	if ($row2) {
		$id_author_inc = $row2["id_usuario"];
		if (give_acl ($config['id_user'], $row2["id_grupo"], "IM") || $config["id_user"] == $id_author_inc) {
			borrar_incidencia($id_inc);
			echo "<h3 class='suc'>".__('Incident successfully deleted')."</h3>";
			audit_db($id_author_inc,$REMOTE_ADDR,"Incident deleted","User ".$config['id_user']." deleted incident #".$id_inc);
		} else {
			audit_db($id_author_inc,$REMOTE_ADDR,"ACL Forbidden","User ".$_SESSION["id_usuario"]." try to delete incident");
			echo "<h3 class='error'>".__('There was a problem deleting incident')."</h3>";
			no_permission();
		}
	}
}

// UPDATE incident
if ((isset($_GET["action"])) AND ($_GET["action"]=="update")){
	$id_inc = $_POST["id_inc"];
 	$grupo = entrada_limpia($_POST['grupo_form']);
	$usuario= entrada_limpia($_POST["usuario_form"]);
	if (give_acl ($config['id_user'], $grupo, "IM") || $usuario == $config['id_user']) { // Only admins (manage incident) or owners can modify incidents
		$id_author_inc = give_incident_author($id_inc);
		$titulo = entrada_limpia($_POST["titulo"]);
		$descripcion = entrada_limpia($_POST['descripcion']);
		$origen = entrada_limpia($_POST['origen_form']);
		$prioridad = entrada_limpia($_POST['prioridad_form']);
		$estado = entrada_limpia($_POST["estado_form"]);
		$ahora=date("Y/m/d H:i:s");
		$sql = "UPDATE tincidencia SET actualizacion = '".$ahora."', titulo = '".$titulo."', origen= '".$origen."', estado = '".$estado."', id_grupo = '".$grupo."', id_usuario = '".$usuario."', prioridad = '".$prioridad."', descripcion = '".$descripcion."' WHERE id_incidencia = ".$id_inc;
		$result=mysql_query($sql);
		audit_db($id_author_inc,$REMOTE_ADDR,"Incident updated","User ".$config['id_user']." deleted updated #".$id_inc);
		if ($result)
			echo "<h3 class='suc'>".__('Incident successfully updated')."</h3>";
		else
			echo "<h3 class='suc'>".__('There was a problem updating incident')."</h3>";
	} else {
		audit_db($config['id_user'],$REMOTE_ADDR,"ACL Forbidden","User ".$_SESSION["id_usuario"]." try to update incident");
		echo "<h3 class='error'>".__('There was a problem updating incident')."</h3>";
		no_permission();
	}
}
// INSERT incident
if ((isset($_GET["action"])) AND ($_GET["action"]=="insert")){
	$grupo = entrada_limpia($_POST['grupo_form']);
	$usuario= entrada_limpia($_POST["usuario_form"]);
	if (give_acl ($config['id_user'], $grupo, "IM") || $usuario == $config['id_user']) { // Only admins (manage
		// Read input variables
		$titulo = entrada_limpia($_POST['titulo']);
		$inicio = date("Y/m/d H:i:s");
		$descripcion = entrada_limpia($_POST['descripcion']);
		$texto = $descripcion; // to view in textarea after insert
		$origen = entrada_limpia($_POST['origen_form']);
		$prioridad = entrada_limpia($_POST['prioridad_form']);
		$actualizacion = $inicio;
		$id_creator = $config['id_user'];
		$estado = entrada_limpia($_POST["estado_form"]);
		$sql = " INSERT INTO tincidencia (inicio,actualizacion,titulo,descripcion,id_usuario,origen,estado,prioridad,id_grupo, id_creator) VALUES ('".$inicio."','".$actualizacion."','".$titulo."','".$descripcion."','".$usuario."','".$origen."','".$estado."','".$prioridad."','".$grupo."','".$id_creator."') ";
		if (mysql_query($sql)){
			echo "<h3 class='suc'>".__('Incident successfully created')."</h3>";
			$id_inc=mysql_insert_id();
			audit_db($usuario,$REMOTE_ADDR,"Incident created","User ".$config['id_user']." created incident #".$id_inc);
		}
	} else {
		audit_db($config['id_user'],$REMOTE_ADDR,"ACL Forbidden","User ".$_SESSION["id_usuario"]." try to create incident");
		no_permission();
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
$sql1=$sql1." LIMIT $offset, ".$config["block_size"];

echo "<h2>".__('Incident management')." &gt; ";
echo __('Manage incidents')."</h2>";
if (isset($_POST['operacion'])){
	echo __('Viewing incidents')." - ".$_POST['operacion']."</h2>";
}

?>
<form name='visualizacion' method='POST' action='index.php?sec=incidencias&sec2=operation/incidents/incident'>
<table class="databox" cellpadding="4" cellspacing="4">
<tr>
<td valign="middle">
<h3><?php echo __('Filter'); ?></h3>
<select name="estado" onChange="javascript:this.form.submit();" class="w155"> 
<?php
	// Tipo de estado (Type)
	// 0 - Abierta / Sin notas (Open without notes)
	// 1 - Abierta / Notas aniadidas  (Open with notes)
	// 2 - Descartada (Not valid)
	// 3 - Caducada (out of date)
	// 13 - Cerrada (closed)

	if ((isset($_GET["estado"])) OR (isset($_GET["estado"]))){ 
		if (isset($_GET["estado"]))
			$estado = $_GET["estado"];
		if (isset($_POST["estado"]))
			$estado = $_POST["estado"];
		echo "<option value='".$estado."'>";
		switch ($estado){
			case -1: echo __('All incidents')."</option>"; break;
			case 0: echo __('Active incidents')."</option>"; break;
			case 13: echo __('Closed incidents')."</option>"; break;
			case 2: echo __('Rejected incidents')."</option>"; break;
			case 3: echo __('Expired incidents')."</option>"; break;
		}
	}

	echo "<option value='-1'>".__('All incidents')."</option>";
	echo "<option value='0'>".__('Active incidents')."</option>";
	echo "<option value='13'>".__('Closed incidents')."</option>";
	echo "<option value='2'>".__('Rejected incidents')."</option>";
	echo "<option value='3'>".__('Expired incidents')."</option>";
?>
	</select>
	</td>
	<td valign="middle">
	<noscript><input type="submit" class="sub" value="<?php echo __('Show') ?>" border="0"></noscript>
	</td>
	<td rowspan="5" class="f9" style="padding-left: 30px; vertical-align: top;">
	<h3><?php echo __('Status') ?></h3>
	<img src='images/dot_red.png'> - <?php echo __('Active incidents') ?><br>
	<img src='images/dot_yellow.png'> - <?php echo __('Active incidents, with comments') ?><br>
	<img src='images/dot_blue.png'> - <?php echo __('Rejected incidents') ?><br>
	<img src='images/dot_green.png'> - <?php echo __('Closed incidents') ?><br>
	<img src='images/dot_white.png'> - <?php echo __('Expired incidents') ?></td>

	<td rowspan="5" class="f9" style="padding-left: 30px; vertical-align: top;">
	<h3><?php echo __('Priority') ?></h3>
	<img src='images/dot_red.png'><img src='images/dot_red.png'><img src='images/dot_red.png'> - <?php echo __('Very Serious') ?><br>
	<img src='images/dot_yellow.png'><img src='images/dot_red.png'><img src='images/dot_red.png'> - <?php echo __('Serious') ?><br>
	<img src='images/dot_yellow.png'><img src='images/dot_yellow.png'><img src='images/dot_red.png'> - <?php echo __('Medium') ?><br>
	<img src='images/dot_green.png'><img src='images/dot_yellow.png'><img src='images/dot_yellow.png'> - <?php echo __('Low') ?><br>
	<img src='images/dot_green.png'><img src='images/dot_green.png'><img src='images/dot_yellow.png'> - <?php echo __('Informative') ?><br>
	<img src='images/dot_green.png'><img src='images/dot_green.png'><img src='images/dot_green.png'> - <?php echo __('Maintenance') ?><br>
	<tr><td>
	<select name="prioridad" onChange="javascript:this.form.submit();" class="w155">
<?php 

if ((isset($_GET["prioridad"])) OR (isset($_GET["prioridad"]))){ 
	if (isset($_GET["prioridad"]))
		$prioridad = $_GET["prioridad"];
	if (isset($_POST["prioridad"]))
		$prioridad = $_POST["prioridad"];
	echo "<option value=".$prioridad.">";
	switch ($prioridad){
	case -1:
		echo __('All')." ".__('Priority');
		break;
	case 0:
		echo __('Informative');
		break;
	case 1:
		echo __('Low');
		break;
	case 2:
		echo __('Medium');
		break;
	case 3:
		echo __('Serious');
		break;
	case 4:
		echo __('Very Serious');
		break;
	case 10:
		echo __('Maintenance');
		break;
	}
}
echo "<option value='-1'>".__('All')." ".__('Priority')."</option>"; // al priorities (default)
echo '<option value="0">'.__('Informative')."</option>";
echo '<option value="1">'.__('Low')."</option>";
echo '<option value="2">'.__('Medium')."</option>";
echo '<option value="3">'.__('Serious')."</option>";
echo '<option value="4">'.__('Very Serious')."</option>";
echo '<option value="10">'.__('Maintenance')."</option>";
echo "</select></td>
<td valign='middle>
<noscript>
<input type='submit' class='sub' value='".__('Show')."' border='0'>
</noscript>";
echo "</td>";
echo '<tr><td><select name="grupo" onChange="javascript:this.form.submit();" class="w155">';

if ((isset($_GET["grupo"])) OR (isset($_GET["grupo"]))){
	if (isset($_GET["grupo"]))
		$grupo = $_GET["grupo"];
	if (isset($_POST["grupo"]))
		$grupo = $_POST["grupo"];
	echo "<option value=".$grupo.">";
	if ($grupo == -1) {
		echo __('All')." ".__('groups'); // all groups (default)
	} else {
		echo dame_nombre_grupo($grupo);
	}
	echo "</option>";
}
echo "<option value='-1'>".__('All')." ".__('groups')."</option>"; // all groups (default)
$sql2="SELECT * FROM tgrupo";
$result2=mysql_query($sql2);
while ($row2=mysql_fetch_array($result2)){
	echo "<option value=".$row2["id_grupo"].">".$row2["nombre"]."</option>";
}

echo "</select></td>
<td valign='middle'>
<noscript><input type='submit' class='sub' value='".__('Show')."' border='0'></noscript>
</td>";

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

$offset_counter=0;
// Prepare index for pagination
$incident_list[]="";
$result2=mysql_query($sql1);
$result2_count=mysql_query($sql1_count);
$row2_count = mysql_fetch_array($result2_count);

if ($row2_count[0] <= 0 ) {
	echo '<div class="nf">'.__('No incident matches your search filter').'</div><br></table>';
	echo "<table>";
	echo "<tr><td>";
	echo "<form method='post' action='index.php?sec=incidencias&sec2=operation/incidents/incident_detail&insert_form'>";
	echo "<input type='submit' class='sub next' name='crt' value='".__('Create incident')."'></form>";
	echo "</td></tr></table>";
} else {
	// TOTAL incidents
	$total_incidentes = $row2_count[0];
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
	if (isset($_GET["offset"] ))
		$url = $url."&offset=".$_GET["offset"];

	// Show pagination
	pagination ($total_incidentes, $url, $offset);
	echo '<br>';
	// Show headers
	
	echo "<table cellpadding='4' cellspacing='4' width='750' class='databox'>";
	echo "<tr>";
	echo "<th width='43'>ID</th>";
	echo "<th>".__('Status')."</th>";
	echo "<th >".__('Incident')."</th>";
	echo "<th >".__('Priority')."</th>";
	echo "<th>".__('Group')."</th>";
	echo "<th>".__('Updated at')."</th>";
	echo "<th>".__('Source')."</th>";
	echo "<th width='50'>".__('Owner')."</th>";
	echo "<th>".__('Delete')."</th>";
	$color = 1;

	while ($row2=mysql_fetch_array($result2)){ 
		$id_group = $row2["id_grupo"];
		if (give_acl ($config['id_user'], $id_group, "IR")) {
			if ($color == 1){
				$tdcolor = "datos";
				$color = 0;
			}
			else {
				$tdcolor = "datos2";
				$color = 1;
			}
			$note_number = dame_numero_notas($row2["id_incidencia"]);
			echo "<tr>";
			echo "<td class='$tdcolor' align='center'>
			<a href='index.php?sec=incidencias&sec2=operation/incidents/incident_detail&id=".$row2["id_incidencia"]."'>".$row2["id_incidencia"]."</a>";

			// Check for attachments in this incident
			$result3=mysql_query("SELECT * FROM tattachment WHERE id_incidencia = ".$row2["id_incidencia"]);
			mysql_fetch_array($result3);
			if (mysql_affected_rows() > 0)
				echo '&nbsp;&nbsp;<img src="images/file.png" align="middle">';

				// Tipo de estado  (Type)
				// 0 - Abierta / Sin notas (Open, no notes)
				// 1 - Abierta / Notas anyadidas (Open with notes)
				// 2 - Descartada (not valid)
				// 3 - Caducada (out of date)
				// 13 - Cerrada (closed)

			// Verify if the status changes
			if (($row2["estado"] == 0) && ($note_number >0 )){
				$row2["estado"] = 1;
			}
			echo "</td><td class='$tdcolor' align='center'>";
			switch ($row2["estado"]) {
				case 0: echo "<img src='images/dot_red.png'>";
							break;
				case 1: echo "<img src='images/dot_yellow.png'>";
							break;
				case 2: echo "<img src='images/dot_blue.png'>";
							break;
				case 3: echo "<img src='images/dot_white.png'>";
							break;
				case 13: echo "<img src='images/dot_green.png'>";
							break;
			}
			echo "</td><td class='$tdcolor'><a href='index.php?sec=incidencias&sec2=operation/incidents/incident_detail&id=".$row2["id_incidencia"]."'>".substr(salida_limpia($row2["titulo"]),0,45);
			echo "<td class='$tdcolor' align='center'>";
			switch ( $row2["prioridad"] ){
				case 0: echo "<img src='images/dot_green.png'>"."<img src='images/dot_green.png'>"."<img src='images/dot_yellow.png'>"; break;
				case 1: echo "<img src='images/dot_green.png'>"."<img src='images/dot_yellow.png'>"."<img src='images/dot_yellow.png'>"; break;
				case 2: echo "<img src='images/dot_yellow.png'>"."<img src='images/dot_yellow.png'>"."<img src='images/dot_red.png'>"; break;
				case 3: echo "<img src='images/dot_yellow.png'>"."<img src='images/dot_red.png'>"."<img src='images/dot_red.png'>"; break;
				case 4: echo "<img src='images/dot_red.png'>"."<img src='images/dot_red.png'>"."<img src='images/dot_red.png'>"; break;
				case 10: echo "<img src='images/dot_green.png'>"."<img src='images/dot_green.png'>"."<img src='images/dot_green.png'>"; break;
			}
			/*
			case 0: echo __('Informative'); break;
			case 1: echo __('Low'); break;
			case 2: echo __('Medium'); break;
			case 3: echo __('Serious'); break;
			case 4: echo __('Very Serious'); break;
			case 10: echo __('Maintenance'); break;
			*/
			echo "<td class='$tdcolor' align='center'>";
			$id_grupo = $row2["id_grupo"];
			echo '<img src="images/groups_small/'.show_icon_group($id_grupo).'.png" title="'.dame_grupo($id_grupo).'">';
			
			
			echo "<td class='$tdcolor'>".human_time_comparation($row2["actualizacion"]);
			echo "<td class='$tdcolor'>".$row2["origen"];
			echo "<td class='$tdcolor'><a href='index.php?sec=usuario&sec2=operation/users/user_edit&ver=".$row2["id_usuario"]."'>".$row2["id_usuario"]."</td>";
			$id_author_inc = $row2["id_usuario"];
			if (give_acl ($config['id_user'], $id_group, "IM") || $config["id_user"] == $id_author_inc) {
			// Only incident owners or incident manager
			// from this group can delete incidents
				echo "<td class='$tdcolor' align='center'><a href='index.php?sec=incidencias&sec2=operation/incidents/incident&quick_delete=".$row2["id_incidencia"]."' onClick='if (!confirm(\' ".__('Are you sure?')."\')) return false;'><img src='images/cross.png' border='0'></a></td>";
			}
		}
	}
	echo "</tr></table>";
	if (give_acl ($config["id_user"], 0, "IW")) {
		echo "<table width='750px'>";
		echo "<tr><td align='right'>";
		echo "<form method='post' action='index.php?sec=incidencias&sec2=operation/incidents/incident_detail&insert_form'>";
		echo "<input type='submit' class='sub next' name='crt' value='".__('Create incident')."'></form>";
}
	echo "</td></tr></table>";	

}

?>
