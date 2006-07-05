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
	echo "<h3>".$lang_label["monitor_listing"]."<a href='help/".substr($language_code,0,2)."/chap3.php#3323' target='_help' class='help'>&nbsp;<span>".$lang_label["help"]."</span></a></h3>";
	// Get all module from agent
	$sql_t='SELECT * FROM tagente_estado, tagente_modulo WHERE tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo AND tagente_modulo.id_agente='.$id_agente.' order by tagente_modulo.nombre';
	$result_t=mysql_query($sql_t);
	if (mysql_num_rows ($result_t)) {
		$color=0;
		$string='';
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
				if ($color == 1){
					$tdcolor = "datos";
					$color = 0;
				}
				else {
					$tdcolor = "datos2";
					$color = 1;
				}
				$ahora=date("Y/m/d H:i:s");
				$seconds = strtotime($ahora) - strtotime($row_t["timestamp"]);
				if ($seconds >= ($temp_interval*2)) // If every interval x 2 secs. we get nothing, there's and alert
					$agent_down = 1;
				else
					$agent_down = 0;
				
				if (!isset($string)) {$string='';}
				$string=$string."<tr><td class='".$tdcolor."'>".$est_tipo;
				$string=$string."<td class='".$tdcolor."'>".$est_modulo;
				$string=$string."<td class='".$tdcolor."f9'>".substr($est_description,0,32);
				// echo "<td class='datos'>".$row3["datos"];
				if ($agent_down == 1)
					$string=$string."<td class='".$tdcolor."' align='center'><img src='images/b_down.gif'>";
				else	
					if ($est_estado == 1)
						if ($est_cambio ==1)
							$string=$string."<td class='".$tdcolor."' align='center'><img src='images/b_yellow.gif'>";
						else
							$string=$string."<td class='".$tdcolor."' align='center'><img src='images/b_red.gif'>";
					else
						$string=$string."<td class='".$tdcolor."' align='center'><img src='images/b_green.gif'>";
				$string=$string."<td class='".$tdcolor."'>".$temp_interval;
				$string=$string."<td class='".$tdcolor."f9'>";
				if ($agent_down == 1) // Si el agente esta down, lo mostramos en negrita y en rojo
					$string=$string."<b><font color='red'>";
					$string=$string.$row_t["timestamp"]."</font></b>";
			}
			else unset($string);
		}
	if (isset($string)) {
	echo "<table width='750' cellpadding=3 cellspacing=3><tr></th><th>".$lang_label["type"]."<th>".$lang_label["module_name"]."</th><th>".$lang_label["description"]."</th><th>".$lang_label["status"]."</th><th>".$lang_label["interval"]."</th><th>".$lang_label["last_contact"]."</th></tr>";
	echo $string;
	echo '<tr><td colspan="7"><div class="raya"></div></td></tr></table>';
	}
	else {
		echo "<font class='red'>".$lang_label["no_monitors"]."</font>";
		}
	}
	else 
		echo "<font class='red'>".$lang_label["no_monitors"]."</font>";
}
?>