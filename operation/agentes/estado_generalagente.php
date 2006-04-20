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

	if (isset($_GET["id_agente"])){
		$id_agente = $_GET["id_agente"];
	// Connect BBDD
		$sql1='SELECT * FROM tagente WHERE id_agente = '.$id_agente;
		$result=mysql_query($sql1);
		if ($row=mysql_fetch_array($result)){
			$intervalo = $row["intervalo"]; // Interval in seconds to receive data
			$nombre_agente = $row["nombre"];
			$direccion_agente =$row["direccion"];
			$ultima_act = $row["ultimo_contacto"];
			$ultima_act_remota =$row["ultimo_contacto_remoto"];
			$comentarios = $row["comentarios"];
			$id_grupo = $row["id_grupo"];
			$id_os= $row["id_os"];
			$os_version = $row["os_version"];
			$agent_version = $row["agent_version"];
			$disabled= $row["disabled"];
			$agent_type= $row["agent_type"];
			$server = $row["id_server"];
		} else
			{
			echo "<h3 class='error'>".$lang_label["agent_error"]."</h3>";
			echo "</table>";
				include ("general/footer.php");
				exit;
			}
	}
	
	// Load icon index from tgrupos
	$iconindex_g[]="";

	$sql_tg='SELECT id_grupo, icon FROM tgrupo';
	$result_tg=mysql_query($sql_tg);
	while ($row_tg=mysql_fetch_array($result_tg)){
		$iconindex_g[$row_tg["id_grupo"]] = $row_tg["icon"];
	}
	
	echo "<h2>".$lang_label["ag_title"]."</h2>";
	echo "<h3>".$lang_label["view_agent_general_data"]."<a href='help/".substr($language_code,0,2)."/chap3.php#3321' target='_help'><img src='images/ayuda.gif' border='0' class='help'></a></h3>";
	echo '<table cellspacing=3 cellpadding=3 border=0 width=750>';	
	echo '<tr><td class="datos"><b>'.$lang_label["agent_name"].'</b> <td class="datos">'.salida_limpia($nombre_agente);

	echo " &nbsp;<a href='index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=".$id_agente."&refr=60'><img src='images/refresh.gif' class='top' border=0></a>";
	if (dame_admin($_SESSION['id_usuario'])==1 )
		echo "&nbsp; <a href='index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&id_agente=".$id_agente."'><img src='images/setup.gif' border=0 width=19 class='top' ></a>";
	// Data base access graph

	echo "<td class='datost' rowspan=4><b>".$lang_label["agent_access_rate"]."</b><br><br>
	<img src='reporting/fgraph.php?id=".$id_agente."&tipo=agentaccess&periodo=1440' border=0>";

	echo '<tr><td class="datos"><b>'.$lang_label["ip_address"].'</b> <td class="datos">'.salida_limpia($direccion_agente);
	if ($agent_type == 0) {
		echo '<tr><td class="datos"><b>'.$lang_label["os"].'</b> <td class="datos"><img border=0 src="images/'.dame_so_icon($id_os).'"> - '.dame_so_name($id_os);
	} elseif ($agent_type == 1) {
		echo '<tr><td class="datos"><b>'.$lang_label["agent_type"].'</b> <td class="datos"><img border=0 src="images/network.gif"';
	}
	if ($os_version != "") echo ' v'.salida_limpia($os_version);
	echo '<tr><td class="datos"><b>'.$lang_label["interval"].'</b> <td class="datos">'.$intervalo;
	echo '<tr><td class="datos"><b>'.salida_limpia($lang_label["description"]).'</b> <td class="datos">'.$comentarios;	

	echo "<td datost' rowspan=6><b>".$lang_label["agent_module_shareout"]."</b><br><br>";
	echo "<img src='reporting/fgraph.php?id=".$id_agente."&tipo=agentmodules' border=0>";

	echo '<tr><td class="datos"><b>'.salida_limpia($lang_label["group"]).'</b> <td class="datos"> <img src="images/g_'.$iconindex_g[$row["id_grupo"]].'.gif" border="0"> ( '.dame_grupo($id_grupo).' )';
	if ($agent_type == 0) {	
		echo '<tr><td class="datos"><b>'.$lang_label["agentversion"].'</b> <td class="datos">'.salida_limpia($agent_version);
	}	

	

	echo '<tr><td class="datos"><b>'.$lang_label["total_packets"].'</b> <td class="datos">';
	
	$total_paketes= 0;
	$id_agente = dame_agente_id($nombre_agente);
	$sql_2='SELECT * FROM tagente_modulo WHERE id_agente = '.$id_agente;
	$result_t=mysql_query($sql_2);
	while ($row=mysql_fetch_array($result_t)){	
		$sql_3='SELECT COUNT(*) FROM tagente_datos WHERE id_agente_modulo = '.$row["id_agente_modulo"];
		$result_3=mysql_query($sql_3);
		$row3=mysql_fetch_array($result_3);
		$total_paketes = $total_paketes + $row3[0];	
	}	
	
	echo $total_paketes;

	echo '<tr><td class="datos"><b>'.$lang_label["last_contact"]." / ".$lang_label["remote"].'</b> <td class="datosf9">';
	echo $ultima_act." / ".$ultima_act_remota;
	echo '<tr><td class="datos"><b>'.$lang_label["default_server"].'</b> <td class="datos">';
	if ($server == ""){ // default server
		echo $lang_label["server_asigned"];
	} else {
		echo give_server_name($server);
	}
	echo "</td></tr></table>";
	
}

?>
