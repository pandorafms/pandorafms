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
	
	// Load icon index array from ttipo_modulo
	$iconindex[]="";
	$sql_tm='SELECT id_tipo, icon FROM ttipo_modulo';
	$result_tm=mysql_query($sql_tm);
	while ($row_tm=mysql_fetch_array($result_tm)){
		$iconindex[$row_tm["id_tipo"]] = $row_tm["icon"];
	}

	echo "<h2>".$lang_label["ag_title"]."</h2>";
	echo "<h3>".$lang_label["monitor_listing"]."<a href='help/chap3_en.php#334' target='_help'><img src='images/ayuda.gif' border='0' class='help'></a></h3>";
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
		echo "<form method='post' action='index.php?sec=estado&sec2=operation/agentes/status_monitor&refr=60&ag_group_refresh=".$ag_group."'>";
	} else {
		echo "<form method='post' action='index.php?sec=estado&sec2=operation/agentes/status_monitor&refr=60'>";
	}
	echo "<table border='0' cellspacing=3 cellpadding=3><tr><td valign='middle'>".$lang_label["group_name"];
	echo "<td valign='middle'>";
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

	// Module name selector
	// This code thanks for an idea from Nikum, nikun_h@hotmail.com
	if (isset($_POST["ag_modulename"])){
		$ag_modulename = $_POST["ag_modulename"];
		echo "<form method='post' action='index.php?sec=estado&sec2=operation/agentes/status_monitor&refr=60&ag_modulename=".$ag_modulename."'>";
	} else {
		echo "<form method='post' action='index.php?sec=estado&sec2=operation/agentes/status_monitor&refr=60'>";
	}
	echo "<tr><td valign='middle'>";
	echo $lang_label["module_name"]."<td valign='middle'> <select name='ag_modulename' onChange='javascript:this.form.submit();'>";
	if ( isset($ag_modulename)){
		echo "<option>".$ag_modulename;
	} 
	echo "<option>---";
	$sql='SELECT DISTINCT nombre FROM tagente_modulo where (id_tipo_modulo = 2) OR (id_tipo_modulo = 9) OR (id_tipo_modulo = 12) OR (id_tipo_modulo = 18) OR (id_tipo_modulo = 6) ';
	$result=mysql_query($sql);
	while ($row=mysql_fetch_array($result)){
		echo "<option>".$row[0];
	}
	echo "</select>";
	echo "<td valign='middle'><noscript><input name='uptbutton' type='submit' class='sub' value='".$lang_label["show"]."'></noscript></form>";
	

	// Show only selected names & groups
	if ($ag_group > 1) 
		$sql='SELECT * FROM tagente WHERE id_grupo='.$ag_group.' ORDER BY nombre';
	else 
		$sql='SELECT * FROM tagente ORDER BY id_grupo, nombre';
		
	echo "</table>";
	echo "<br>";
	
	$result=mysql_query($sql);
	if (mysql_num_rows($result)){
		echo "<table cellpadding='3' cellspacing='3' width='750'>";
		echo "<tr><th>".$lang_label["agent"]."</th><th>".$lang_label["type"]."</th><th>".$lang_label["name"]."</th><th>".$lang_label["description"]."</th><th>".$lang_label["max_min"]."</th><th>".$lang_label["interval"]."</th><th>".$lang_label["status"]."</th><th>".$lang_label["timestamp"]."</th>";
		while ($row=mysql_fetch_array($result)){ //while there are agents
			if ($row["disabled"] == 0) {
				if ((isset($ag_modulename)) && ($ag_modulename != "---"))
					$query_gen='SELECT * FROM tagente_modulo WHERE id_agente = '.$row["id_agente"].' AND nombre = "'.entrada_limpia($_POST["ag_modulename"]).'" AND ( (id_tipo_modulo = 2) OR (id_tipo_modulo = 9) OR (id_tipo_modulo = 12) OR (id_tipo_modulo = 18) OR (id_tipo_modulo = 6))';
				else
					$query_gen='SELECT * FROM tagente_modulo WHERE id_agente = '.$row["id_agente"].' AND ( (id_tipo_modulo = 2) OR (id_tipo_modulo = 9) OR (id_tipo_modulo = 12) OR (id_tipo_modulo = 18) OR (id_tipo_modulo = 6))';
				$result_gen=mysql_query($query_gen);
				if (mysql_num_rows ($result_gen)) {
					while ($data=mysql_fetch_array($result_gen)){
						echo "<tr>";
						echo "<td class='datos'><b><a href='index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=".$data["id_agente"]."'>".dame_nombre_agente($data["id_agente"])."</A></B>";
						echo "<td class='datos'>";
						echo "<img src='images/".$iconindex[$data["id_tipo_modulo"]]."' border=0>";
						echo "<td class='datos'>".$data["nombre"];
						echo "<td class='datosf9'>".substr($data["descripcion"],0,30);
						echo "<td class='datos' width=25>".$data["max"]."/".$data["min"];
						echo "<td class='datos' width=25>";
						if ($data["module_interval"] == 0){
							echo give_agentinterval($data["id_agente"]);
						} else {
							echo $data["module_interval"];
						}
						$query_gen2='SELECT * FROM tagente_estado WHERE id_agente_modulo = '.$data["id_agente_modulo"];
						$result_gen2=mysql_query($query_gen2);
						$data2=mysql_fetch_array($result_gen2);
						echo "<td class='datos' width=20>";
						if ($data2["datos"] > 0){
							echo "<img src='images/b_green.gif'>";
						} else {
							echo "<img src='images/b_red.gif'>";
						}
						echo "<td class='datosf9' width='140'>".$data2["timestamp"];
					}
				}
			}
		}
		echo "<tr><td colspan='8'><div class='raya'></div></td></tr></table>";
	} else {
		echo "<font class='red'>".$lang_label["no_agent"]."</font>";
	}

} //end acl
} //end login

?>