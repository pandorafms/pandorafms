<?php
// Pandora - The Free Monitoring System
// This code is protected by GPL license.
// Este codigo esta protegido por la licencia GPL.
// Sancho Lerena <slerena@gmail.com>, 2003-2006
// Raul Mateos <raulofpandora@gmail.com>, 2005-2006

// Load globar vars
require("include/config.php");
if (comprueba_login() == 0) {

	// $id_agente can be obtained as global variable or GET param.
	if (isset($_GET["id_agente"])){
		$id_agente = $_GET["id_agente"];
	}

	// Get all module from agent
	$sql_t='SELECT * FROM tagente_estado, tagente_modulo WHERE tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo AND tagente_modulo.id_agente='.$id_agente;
	$result_t=mysql_query($sql_t);
	
	if (mysql_num_rows ($result_t)) {

		echo "<h3>".$lang_label["monitor_listing"]."<a href='help/chap3_en.php#3323' target='_help'><img src='images/ayuda.gif' border='0' class='help'></a></h3>";
		echo "<table width='750' cellpadding=3 cellspacing=3>";
		echo "<tr><th>".$lang_label["type"]."<th>".$lang_label["module_name"]."<th>".$lang_label["description"]."<th>".$lang_label["status"]."<th>".$lang_label["interval"]."<th>".$lang_label["last_contact"];
	while ($row_t=mysql_fetch_array($result_t)){

		# For evey module in the status table
		$est_modulo = $row_t["nombre"];
		$est_tipo = dame_nombre_tipo_modulo($row_t["id_tipo_modulo"]);
		$est_description = $row_t["descripcion"];
		$est_timestamp = $row_t["timestamp"];
		$est_estado = $row_t["estado"];
		$est_datos = $row_t["datos"];
		$est_cambio = $row_t["cambio"];
		$est_interval = $row_t["module_interval"];

		if (($est_interval != $intervalo) && ($est_interval > 0)) {
			$temp_interval = $est_interval;
		} else {
			$temp_interval = $intervalo;
		}
		
		if ($est_estado <>100){ # si no es un modulo de tipo datos
			# Determinamos si se ha caido el agente (tiempo de intervalo * 2 superado)
		
			$ahora=date("Y/m/d H:i:s");
			$seconds = strtotime($ahora) - strtotime($row_t["timestamp"]);
			if ($seconds >= ($temp_interval*2)) // If every interval x 2 secs. we get nothing, there's and alert
				$agent_down = 1;
			else
				$agent_down = 0;
			
			echo "<tr>";
			echo "<td class='datos'>".$est_tipo;
			echo "<td class='datos'>".$est_modulo;
			echo "<td class='f9'>".substr($est_description,0,32);
			// echo "<td class='datos'>".$row3["datos"];
			if ($agent_down == 1)
				echo "<td class='datos' align='center'><img src='images/b_down.gif'>";
			else	
				if ($est_estado == 1)
					if ($est_cambio ==1)
						echo "<td class='datos' align='center'><img src='images/b_yellow.gif'>";
					else
						echo "<td class='datos' align='center'><img src='images/b_red.gif'>";
				else
					echo "<td class='datos' align='center'><img src='images/b_green.gif'>";
			echo "<td class='datos'>";
			echo $temp_interval;
			echo "<td class='f9'>";
			if ($agent_down == 1) // Si el agente esta down, lo mostramos en negrita y en rojo
				echo "<b><font color='red'>";

			echo $row_t["timestamp"];
		}
	}
echo '<tr><td colspan="7"><div class="raya"></div></td></tr></table>';
}
else echo "- <font class='red'>".$lang_label["no_monitors"]."</font>";
}
?>