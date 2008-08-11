<?php
// Pandora FMS - the Free Monitoring System
// ========================================
// Copyright (c) 2004-2008 Sancho Lerena, slerena@gmail.com
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
require ("include/config.php");


check_login ();

if (! give_acl ($config["id_user"], 0, "AR") && ! give_acl ($config["id_user"], 0, "AW")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access alert view");
	include ("general/noaccess.php");
	exit;
}

// Show alerts for specific agent
if (isset($_GET["id_agente"])){
	$id_agente = $_GET["id_agente"];

	$id_grupo_alerta = get_db_value ("id_grupo", "tagente", "id_agente", $id_agente);
	if (give_acl($config["id_user"], $id_grupo_alerta, "AR") == 0) {
		audit_db($config["id_user"], $REMOTE_ADDR, "ACL Violation","Trying to access alert view");
		include ("general/noaccess.php");
		exit;
	}

	if (isset($_GET["tab"])){
		echo "<h2>".__('ag_title')." &gt; ".__('alert_listing')."</h2>";
	}
	
	$query_gen='SELECT talerta_agente_modulo.* FROM talerta_agente_modulo, tagente_modulo WHERE talerta_agente_modulo.id_agente_modulo = tagente_modulo.id_agente_modulo AND tagente_modulo.id_agente ='.$id_agente;
	$result_gen=mysql_query($query_gen);
	if (mysql_num_rows ($result_gen)) {
	
		if (!isset($_GET["tab"])) {
			echo "<h3>".__('alert_listing')."</h3>";
		}
	
		echo "<table cellpadding='4' cellspacing='4' width=750 border=0 class='databox'>";
		echo "<tr>
		<th>".__('type')."<th>".__('name')."</th>
		<th>".__('description')."</th>
			<th>".__('Info')."</th>
		<th>".__('min.')."</th>
		<th>".__('max.')."</th>
		<th>".__('time_threshold')."</th>
		<th>".__('last_fired')."</th>
		<th>".__('times_fired')."</th>
		<th>".__('status')."</th>
		<th>".__('validate')."</th>";
		$color=1;
		while ($data=mysql_fetch_array($result_gen)){
			if ($color == 1){
				$tdcolor = "datos";
				$color = 0;
			}
			else {
				$tdcolor = "datos2";
				$color = 1;
			}
			echo "<tr>";
			show_alert_show_view ($data, $tdcolor, 0);
		}

	// Show combined alerts for this agent
	$result_com = mysql_query("SELECT * FROM talerta_agente_modulo WHERE id_agent = $id_agente");
	if (mysql_num_rows ($result_com)) {
		echo "<tr><td colspan=11 class='datos3'><center>".__('Combined alerts')."</center>";
	}
	while ($data_com=mysql_fetch_array($result_com)){
		if ($color == 1){
			$tdcolor = "datos";
			$color = 0;
		} else {
			$tdcolor = "datos2";
			$color = 1;
		}
		echo "<tr>";
		show_alert_show_view ($data_com, $tdcolor, 1);
	}

		echo '</table>';

	} else {
		echo "<div class='nf'>".__('no_alerts')."</div>";
	}

// Show alert for no defined agent 
} else {
	// -------------------------------
	// SHOW ALL ALERTS (GENERAL PAGE)
	// -------------------------------

	echo "<h2>".__('ag_title')." &gt; ";
	echo __('alert_listing')."</h2>";
	$iduser_temp=$_SESSION['id_usuario'];

	$ag_group = get_parameter ("ag_group", -1);

	if ($ag_group != -1)
		echo "<form method='post' action='index.php?sec=estado&sec2=operation/agentes/estado_alertas&refr=60&ag_group=".$ag_group."'>";
	else
		echo "<form method='post' action='index.php?sec=estado&sec2=operation/agentes/estado_alertas&refr=60'>";
	
	echo "<table cellpadding='4' cellspacing='4' class='databox'>";
	echo "<tr>";
	echo "<td>".__('group')."</td>";
	echo "<td valign='middle'>";
	echo "<select name='ag_group' onChange='javascript:this.form.submit();' class='w150'>";

	if ( $ag_group > 1 ){
		echo "<option value='".$ag_group."'>".dame_nombre_grupo($ag_group).
		"</option>";
	}
	echo "<option value=1>".dame_nombre_grupo(1)."</option>";
	list_group ($config["id_user"]);
	echo "</select></td>";
	echo "<td valign='middle'>
	<noscript>
	<input name='uptbutton' type='submit' class='sub' value='".__('show')."'>
	</noscript></td></form>";

	// Display single alerts
	if ($ag_group > 1)
		$sql='SELECT id_agente, nombre, disabled FROM tagente WHERE id_grupo='.$ag_group.' ORDER BY nombre';
	else
		$sql='SELECT id_agente, nombre, disabled FROM tagente ORDER BY id_grupo, nombre';

	$sql = "SELECT id_agente, nombre, disabled FROM tagente WHERE tagente.disabled = 0 ";
	// Agent group selector
	if ($ag_group > 1)
		$sql .=" AND tagente.id_grupo = ".$ag_group;
	else {
		// User has explicit permission on group 1 ?
		$all_group = get_db_sql ("SELECT COUNT(id_grupo) FROM tusuario_perfil WHERE id_usuario='".$config["id_user"]."' AND id_grupo = 1");
		if ($all_group == 0)
			$sql .=" AND tagente.id_grupo IN (SELECT id_grupo FROM tusuario_perfil WHERE id_usuario='".$config["id_user"]."')";
	}

	$color=1; $string = '';
	$result=mysql_query($sql);	
	if ($result)
	while ($row=mysql_fetch_array($result)) { //while there are agents
		$id_agente = $row['id_agente'];
		$nombre_agente = strtoupper($row["nombre"]);
		$query_gen='SELECT talerta_agente_modulo.id_alerta,
		talerta_agente_modulo.descripcion,
		talerta_agente_modulo.last_fired,
		talerta_agente_modulo.times_fired,
		talerta_agente_modulo.id_agente_modulo,
		tagente_modulo.id_agente_modulo
		FROM tagente_modulo, talerta_agente_modulo
		WHERE tagente_modulo.id_agente = '.$id_agente.'
		AND tagente_modulo.id_agente_modulo = talerta_agente_modulo.id_agente_modulo
		AND talerta_agente_modulo.disable = 0 ';
		$result_gen=mysql_query($query_gen);
		while ($data=mysql_fetch_array($result_gen)){
			if ($color == 1){
				$tdcolor = "datos";
				$color = 0;
			}
			else {
				$tdcolor = "datos2";
				$color = 1;
			}
			$string .= "<tr><td class='".$tdcolor."'>
			<a href='index.php?sec=estado&
			sec2=operation/agentes/ver_agente&
			id_agente=".$id_agente."'>
			<b>".$nombre_agente."</b>";
			$string .= "<td class='$tdcolor' align='center'>";
			if ($data["times_fired"] <> 0)
				$string .= "<img src='images/pixel_red.png' width=40 height=18 title='".__('fired')."'>";
			else
				$string .= "<img src='images/pixel_green.png' width=40 height=18 title='".__('not_fired')."'>";
				
			$string = $string."<td class='".$tdcolor."'>"
			.dame_nombre_alerta($data["id_alerta"])."</td>";
			$string=$string."<td class='".$tdcolor."'>".
			$data["descripcion"]."</td>";
			if ($data["last_fired"] == "0000-00-00 00:00:00") {
				$string=$string."<td class='".$tdcolor."'>".
				__('never')."</td>";
			} else {
				$string=$string."<td class='".$tdcolor."'>".
				human_time_comparation($data["last_fired"])."</td>";
			}
			$string=$string."<td class='".$tdcolor."'>".
			$data["times_fired"]."</td>";
		}
	} //end while

	// Display combined alerts
	// =======================
	$sql = "SELECT id_agente, nombre, disabled FROM tagente WHERE tagente.disabled = 0 ";
	// Agent group selector
	if ($ag_group > 1)
		$sql .=" AND tagente.id_grupo = ".$ag_group;
	else {
		 // User has explicit permission on group 1 ?
		$all_group = get_db_sql ("SELECT COUNT(id_grupo) FROM tusuario_perfil WHERE id_usuario='".$config["id_user"]."' AND id_grupo = 1");
		if ($all_group == 0)
			$sql .=" AND tagente.id_grupo IN (SELECT id_grupo FROM tusuario_perfil WHERE id_usuario='".$config["id_user"]."')";
	}

	$result=mysql_query($sql);
	$color=1;
	if ($result)
	while ($row=mysql_fetch_array($result)){ //while there are agents
		$id_agente = $row['id_agente'];
		$nombre_agente = strtoupper($row["nombre"]);
		$query_gen='SELECT talerta_agente_modulo.id_alerta,
		talerta_agente_modulo.descripcion,
		talerta_agente_modulo.last_fired,
		talerta_agente_modulo.times_fired,
		talerta_agente_modulo.id_agent
		FROM talerta_agente_modulo 
		WHERE talerta_agente_modulo.id_agent = '.$id_agente.' AND talerta_agente_modulo.disable = 0 ';
		$result_gen=mysql_query($query_gen);
		if (mysql_num_rows ($result_gen)) {
			while ($data=mysql_fetch_array($result_gen)){
				if ($color == 1){
					$tdcolor = "datos";
					$color = 0;
				}
				else {
					$tdcolor = "datos2";
					$color = 1;
				}
				if (!isset($string)) {
					$string='';
				}
				$string = $string."<tr><td class='".$tdcolor."'>
				<a href='index.php?sec=estado&
				sec2=operation/agentes/ver_agente&
				id_agente=".$id_agente."'>
				<b>".$nombre_agente."</b> (*)";
				$string .= "<td class='$tdcolor' align='center'>";
				if ($data["times_fired"] <> 0)
					$string .= "<img src='images/pixel_red.png' width=40 height=18 title='".__('fired')."'>";
				else
					$string .= "<img src='images/pixel_green.png' width=40 height=18 title='".__('not_fired')."'>";
					
				$string = $string."<td class='".$tdcolor."'>"
				.dame_nombre_alerta($data["id_alerta"])."</td>";
				$string=$string."<td class='".$tdcolor."'>".
				$data["descripcion"]."</td>";
				if ($data["last_fired"] == "0000-00-00 00:00:00") {
					$string=$string."<td class='".$tdcolor."'>".
					__('never')."</td>";
				} else {
					$string=$string."<td class='".$tdcolor."'>".
					human_time_comparation($data["last_fired"])."</td>";
				}
				$string=$string."<td class='".$tdcolor."'>".
				$data["times_fired"]."</td>";
			}
		}
	} //end while

	if ($string != "") {
		echo "<td class='f9' style='padding-left: 30px;'>";
		echo "<img src='images/pixel_red.png' width=18 height=18> ".__('fired')."</td>";
		echo "<td class='f9' style='padding-left: 30px;'>";
		echo "<img src='images/pixel_green.png' width=18 height=18> ".__('not_fired');
		echo "</td><td class='f9' valign='bottom' style='padding-left: 10px;'>(*) ".__('Combined alert')."</tr></table>";
		echo "<br>";
		echo "<table cellpadding='4' cellspacing='4' width='700' class='databox'>";
		echo "<tr>
		<th>".__('agent')."</th>
		<th>".__('status')."</th>
		<th>".__('type')."</th>
		<th>".__('description')."</th>
		<th>".__('last_fired')."</th>
		<th>".__('times_fired')."</th>";
		echo $string; //built table of alerts
		echo "</table>";
	}
	else {
		echo "</table><br><div class='nf'>".
		__('no_alert')."</div>";
	}
} // Main alert view
?>
