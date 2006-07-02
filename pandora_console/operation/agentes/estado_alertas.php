<?php
// Pandora - The Free Monitoring System
// This code is protected by GPL license.
// Este codigo esta protegido por la licencia GPL.
// Sancho Lerena <slerena@gmail.com>, 2003-2006
// Raul Mateos <raulofpandora@gmail.com>, 2005-2006

// Load global vars
require("include/config.php");
if (comprueba_login() == 0) {
 	if ((give_acl($id_user, 0, "AR")==1) or (give_acl($id_user,0,"AW")) or (dame_admin($id_user)==1)) {

	if (isset($_GET["id_agente"])){
		$id_agente = $_GET["id_agente"];
		$query_gen='SELECT talerta_agente_modulo.id_alerta, talerta_agente_modulo.descripcion, talerta_agente_modulo.last_fired, talerta_agente_modulo.times_fired, tagente_modulo.nombre, talerta_agente_modulo.dis_max, talerta_agente_modulo.dis_min, talerta_agente_modulo.max_alerts, talerta_agente_modulo.time_threshold, talerta_agente_modulo.min_alerts, talerta_agente_modulo.id_agente_modulo, tagente_modulo.id_agente_modulo FROM tagente_modulo, talerta_agente_modulo WHERE tagente_modulo.id_agente = '.$id_agente.' AND tagente_modulo.id_agente_modulo = talerta_agente_modulo.id_agente_modulo order by tagente_modulo.nombre';
		$result_gen=mysql_query($query_gen);
		if (mysql_num_rows ($result_gen)) {
			echo "<h3>".$lang_label["alert_listing"]."<a href='help/".substr($language_code,0,2)."/chap3.php#3324' target='_help'><img src='images/help.gif' border='0' class='help'></a></h3>";
			echo "<table cellpadding='3' cellspacing='3' width=750 border=0>";
			echo "<tr><th>".$lang_label["type"]."<th>".$lang_label["name"]."</th><th>".$lang_label["description"]."</th><th>".$lang_label["min_max"]."</th><th>".$lang_label["time_threshold"]."</th><th>".$lang_label["last_fired"]."</th><th>".$lang_label["times_fired"]."</th><th>".$lang_label["status"]."</th>";
			$color=0;
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
				echo "<td class='".$tdcolor."'>".dame_nombre_alerta($data["id_alerta"]);
				echo "<td class='".$tdcolor."'>".$data["nombre"];
				echo "<td class='".$tdcolor."'>".$data["descripcion"];
				echo "<td class='".$tdcolor."'>".$data["dis_max"]."/".$data["dis_min"];
				echo "<td class='".$tdcolor."'>".$data["time_threshold"];
				echo "<td class='".$tdcolor."f9'>".$data["last_fired"];
				echo "<td class='".$tdcolor."'>".$data["times_fired"];
				if ($data["times_fired"] <> 0)
					echo "<td class='".$tdcolor."' align='center'><img src='images/dot_red.gif'>";
				else
					echo "<td class='".$tdcolor."' align='center'><img src='images/dot_green.gif'>";
			}
			echo '<tr><td colspan="9"><div class="raya"></div></td></tr></table>';
		}
		else echo "- <font class='red'>".$lang_label["no_alerts"]."</font>";
	}
	else 
	{
		echo "<h2>".$lang_label["ag_title"]."</h2>";
		echo "<h3>".$lang_label["alert_listing"]."<a href='help/".substr($language_code,0,2)."/chap3.php#335' target='_help'><img src='images/help.gif' border='0' class='help'></a></h3>";
		$iduser_temp=$_SESSION['id_usuario'];
		if (isset($_POST["ag_group"]))
			$ag_group = $_POST["ag_group"];
		elseif (isset($_GET["group_id"]))
			$ag_group = $_GET["group_id"];
		else
			$ag_group = -1;
		if (isset($_GET["ag_group_refresh"])){
			$ag_group = $_GET["ag_group_refresh"];
		}
		
		if (isset($_POST["ag_group"])){
			$ag_group = $_POST["ag_group"];
			echo "<form method='post' action='index.php?sec=estado&sec2=operation/agentes/estado_alertas&refr=60&ag_group_refresh=".$ag_group."'>";
		} else {
			echo "<form method='post' action='index.php?sec=estado&sec2=operation/agentes/estado_alertas&refr=60'>";
		}
		echo "<table border='0'><tr><td valign='middle'>";
		echo "<select name='ag_group' onChange='javascript:this.form.submit();'>";
	
		if ( $ag_group > 1 ){
			echo "<option value='".$ag_group."'>".dame_nombre_grupo($ag_group);
		}
		echo "<option value=1>".dame_nombre_grupo(1);
	
		$mis_grupos[]=""; // Define array mis_grupos to put here all groups with Agent Read permission
		
		$sql='SELECT * FROM tgrupo';
		$result=mysql_query($sql);
		while ($row=mysql_fetch_array($result)){
		if ($row["id_grupo"] != 1){
			if (give_acl($iduser_temp,$row["id_grupo"], "AR") == 1){
				echo "<option value='".$row["id_grupo"]."'>".dame_nombre_grupo($row["id_grupo"]);
				$mis_grupos[]=$row["id_grupo"]; //Put in  an array all the groups the user belongs
			}
		}
		}
		echo "</select>";
		echo "<td valign='middle'><noscript><input name='uptbutton' type='submit' class='sub' value='".$lang_label["show"]."'></noscript></form>";
		// Show only selected groups	
	
		if ($ag_group > 1)
			$sql='SELECT * FROM tagente WHERE id_grupo='.$ag_group.' order by nombre';
		else 
			$sql='SELECT * FROM tagente order by id_grupo, nombre';

		$result=mysql_query($sql);
		if (mysql_num_rows($result)){
			echo "<td class='f9l30'>";
			echo "<img src='images/dot_red.gif'> - ".$lang_label["fired"];
			echo "&nbsp;&nbsp;</td>";
			echo "<td class='f9'>";
			echo "<img src='images/dot_green.gif'> - ".$lang_label["not_fired"];
			echo "</td></tr></table>";
			echo "<br>";
			echo "<table cellpadding='3' cellspacing='3'>";
			echo "<tr><th>ID</th><th>".$lang_label["type"]."</th><th>".$lang_label["description"]."</th><th>".$lang_label["last_fired"]."</th><th>".$lang_label["times_fired"]."</th><th>".$lang_label["status"]."</th>";
			$color=1;
			while ($row=mysql_fetch_array($result)){ //while there are agents
				if ($row["disabled"] == 0) {
					$id_agente = $row['id_agente'];
					$nombre_agente = $row["nombre"];
					$query_gen='SELECT talerta_agente_modulo.id_alerta, talerta_agente_modulo.descripcion, talerta_agente_modulo.last_fired, talerta_agente_modulo.times_fired, talerta_agente_modulo.id_agente_modulo, tagente_modulo.id_agente_modulo FROM tagente_modulo, talerta_agente_modulo WHERE tagente_modulo.id_agente = '.$id_agente.' AND tagente_modulo.id_agente_modulo = talerta_agente_modulo.id_agente_modulo';
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
							echo "<tr><td class='".$tdcolor."'><a href='index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=".$id_agente."'><b>".$nombre_agente."</b>";
							echo "<td class='".$tdcolor."'>".dame_nombre_alerta($data["id_alerta"]);
							echo "<td class='".$tdcolor."'>".$data["descripcion"];
							echo "<td class='".$tdcolor."'>".$data["last_fired"];
							echo "<td class='".$tdcolor."'>".$data["times_fired"];
							if ($data["times_fired"] <> 0)
								echo "<td class='".$tdcolor."' align='center'><img src='images/dot_red.gif'>";
							else
								echo "<td class='".$tdcolor."' align='center'><img src='images/dot_green.gif'>";
						}
					} //end result
				} //end disabled=0
			} //end while
			echo "<tr><td colspan='6'><div class='raya'></div></td></tr></table>";
		} else echo "<tr><td></td></tr><tr><td><font class='red'>". $lang_label["no_agent"].$lang_label["no_agent_alert"]."</table>";
	}
} //end acl
} //end login

?>