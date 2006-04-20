<?php
// Pandora - The Free Monitoring System
// This code is protected by GPL license.
// Este codigo esta protegido por la licencia GPL.
// Sancho Lerena <slerena@gmail.com>, 2003-2006
// Raul Mateos <raulofpandora@gmail.com>, 2005-2006

// Load global var
require("include/config.php");
if (comprueba_login() == 0) 
	$id_user = $_SESSION["id_usuario"];
	if ( (give_acl($id_user, 0, "LM")==1) OR (give_acl($id_user, 0, "AW")==1) ){
		
		if ((isset($_GET["operacion"])) AND (! isset($_POST["update_agent"]))){
			// DATA COPY
			if (isset($_POST["copiar"])) {
				echo "<h2>".$lang_label["datacopy"]."</h2>";
				// Initial checkings
				
				// if selected more than 0 agents
				$destino = $_POST["destino"];
				if (count($destino) <= 0){
					echo "<h3 class='error'>ERROR: ".$lang_label["noagents_cp"]."</h3>";
					echo "</table>";
					include ("general/footer.php");
					exit;
				}
				$origen_modulo = $_POST["origen_modulo"];
					if (count($origen_modulo) <= 0){
						echo "<h3 class='error'>ERROR: ".$lang_label["nomodules_selected"]."</h3>";
						echo "</table>";
						include ("general/footer.php");
						exit;
					}

				$multiple=1;

				// Source
				$id_origen = $_POST["origen"];

				// If selected modules or alerts
				if (isset($_POST["modules"]))
					$modulos = 1;
				else
					$modulos = 0;
				if (isset($_POST["alerts"]))
					$alertas = 1;
				else
					$alertas = 0;
				if (($alertas + $modulos) == 0){
					echo "<h3 class='error'>ERROR: ".$lang_label["you_must_select_modules"]."</h3>";;
					echo "</table>";
					include ("general/footer.php");
					exit;
				}
				
				// Copy
				for ($a=0;$a <count($destino); $a++){ // For every agent in destination
					$id_agente = $destino[$a];
					echo "<br><br>".$lang_label["copyage"]."<b> [".dame_nombre_agente($id_origen)."] -> [".dame_nombre_agente($id_agente)."]</b>";
 					if ($multiple == 0)
							$b=-1;
					else
							$b=0;
					if ($modulos ==1){
				 		for ($b=$b; $b < count($origen_modulo); $b++){
							if ($multiple == 0)	
	 						 	$sql1='SELECT * FROM tagente_modulo WHERE id_agente = '.$id_origen;
							else
								$sql1='SELECT * FROM tagente_modulo WHERE id_agente_modulo = '.$origen_modulo[$b];
							$result1=mysql_query($sql1);
							while ($row=mysql_fetch_array($result1)){
								$o_id_agente_modulo = $row["id_agente_modulo"];
								$o_id_tipo_modulo = $row["id_tipo_modulo"];
								$o_nombre = $row["nombre"];
								$d_id_agente = $id_agente; // Rapelace with destination agent id
								
								// Read every module in source agent				
								$o_descripcion = $row["descripcion"];
								// Write every module in destination agent
								if ($o_nombre != "agent_keepalive") {
									$sql = "INSERT INTO tagente_modulo (id_agente,id_tipo_modulo,descripcion,nombre) VALUES
									(".$d_id_agente.",'".$o_id_tipo_modulo."','".$o_descripcion."','".$o_nombre."')";
									$result2=mysql_query($sql);
									//echo "DEBUG INSERT $sql <br>";
									echo "<br>&nbsp;&nbsp;".$lang_label["copymod"]." ->".$o_nombre;
								}
							}
						} 
				 	}
					if ($alertas == 1){
						for ($b=$b; $b < count($origen_modulo); $b++){
							if ($multiple == 0)
								$sql1='SELECT * FROM tagente_modulo WHERE id_agente = '.$id_origen;
							else
								$sql1='SELECT * FROM tagente_modulo WHERE id_agente_modulo = '.$origen_modulo[$b];
							$result1=mysql_query($sql1);
							while ($row=mysql_fetch_array($result1)){
								$o_id_agente_modulo = $row["id_agente_modulo"];
								$o_id_tipo_modulo = $row["id_tipo_modulo"];
								$o_nombre = $row["nombre"];
								$d_id_agente = $id_agente; // destination agent id
								$o_descripcion = $row["descripcion"];

								// For each agent module, given as $o_id_agente_modulo:
								// Searching if destination agent has a agente_modulo with same type and name that source
								$sqlp="SELECT * FROM tagente_modulo WHERE id_agente = ".$d_id_agente." AND nombre = '".$o_nombre."' AND id_tipo_modulo = ".$o_id_tipo_modulo;
								$resultp=mysql_query($sqlp);
							 	if ( $rowp=mysql_fetch_array($resultp)){
									// If rowp success get  ID
									$d_id_agente_modulo = $rowp["id_agente_modulo"];
								
									// Read every alert from source agent
									$sql2='SELECT * FROM talerta_agente_modulo WHERE id_agente_modulo = '.$o_id_agente_modulo;
									$result3=mysql_query($sql2);
									while ($row3=mysql_fetch_array($result3)){
										$o_id_alerta = $row3["id_alerta"];
										$o_al_campo1 = $row3["al_campo1"];
										$o_al_campo2 = $row3["al_campo2"];
										$o_al_campo3 = $row3["al_campo3"];
										$o_descripcion = $row3["descripcion"];
										$o_dis_max = $row3["dis_max"];
										$o_dis_min = $row3["dis_min"];
										$o_time_threshold = $row3["time_threshold"];
										$o_last_fired = "2001-01-01 00:00:00";
										$o_max_alerts = $row3["max_alerts"];
										$o_times_fired = 0;
									
										// Insert
										$sql_al="INSERT INTO talerta_agente_modulo (id_agente_modulo, id_alerta, al_campo1, al_campo2, al_campo3, descripcion, dis_max, dis_min, time_threshold, last_fired, max_alerts, times_fired) VALUES ( ".$d_id_agente_modulo.", 
										".$o_id_alerta.",
										'".$o_al_campo1."',
										'".$o_al_campo2."',
										'".$o_al_campo3."',
										'".$o_descripcion."',
										".$o_dis_max.",
										".$o_dis_min.",
										".$o_time_threshold.",
										'".$o_last_fired."',
										".$o_max_alerts.",
										".$o_times_fired.")";
										$result_al=mysql_query($sql_al);
										//echo "DEBUG SQL: $sql_al <br>";
										echo "<br>&nbsp;&nbsp;".$lang_label["copyale"]." ->".$o_descripcion;
									}
								} else 
									echo "<br><h3 class='error'>ERROR: ".$lang_label["notfoundmod"].$o_nombre.$lang_label["inagent"].dame_nombre_agente($d_id_agente)."</h3>";
							} //while
						} // for
					} 
				} 
			} //end if copy modules or alerts

			// DELETE DATA
			elseif (isset($_POST["eliminar"])) {
				echo "<h2>".$lang_label["deletedata"]."</h2>";
				// Initial checkings
				
				//  if selected more than 0 agents
				$destino = $_POST["destino"];
				if (count($destino) <= 0){
					echo "<h3 class='error'>ERROR: ".$lang_label["noagents_del"]."</h3>";
					break;
				}
				
				// If selected modules or alerts
				if (isset($_POST["modules"]))
					$modulos = 1;
				else
					$modulos = 0;
				if (isset($_POST["alerts"]))
					$alertas = 1;
				else
					$alertas = 0;			
				if (($alertas + $modulos) == 0){
					echo "<h3 class='error'>ERROR: ".$lang_label["del_sel_err"]."</h3>";
					break;
				}		
				
				// Delete
				for ($a=0;$a <count($destino); $a++){ // for each agent
					$id_agente = $destino[$a];
					if ($modulos == 1){
						echo "<br>".$lang_label["deletingdata"]." -> ".dame_nombre_agente($id_agente);
					
						// Deleting data
						$sql1='SELECT * FROM tagente_modulo WHERE id_agente = '.$id_agente;
						$result1=mysql_query($sql1);
						while ($row=mysql_fetch_array($result1)){
							$sql_delete1="DELETE FROM tagente_datos WHERE id_agente_modulo=".$row["id_agente_modulo"];
							$sql_delete2="DELETE FROM tagente_datos_inc WHERE id_agente_modulo=".$row["id_agente_modulo"];
							$sql_delete3="DELETE FROM tagente_datos_string WHERE id_agente_modulo=".$row["id_agente_modulo"];
							$sql_delete4 ="DELETE FROM talerta_agente_modulo WHERE id_agente_modulo = ".$row["id_agente_modulo"];
							$result=mysql_query($sql_delete1);
							$result=mysql_query($sql_delete2);
							$result=mysql_query($sql_delete3);
							$result=mysql_query($sql_delete4);
						}
					
					// Delete conf
					$sql_delete5 ="DELETE FROM tagente_modulo WHERE id_agente = ".$id_agente; // delete from table tagente_modulo
					$sql_delete6 ="DELETE FROM tagente_estado WHERE id_agente = ".$id_agente; // detele from table tagente_estado
					$result=mysql_query($sql_delete5);
					$result=mysql_query($sql_delete6);		
					}
					// delete alerts definitions
					if ($alertas == 1){ 
						echo "<br>".$lang_label["deletingdata"]." -> ".dame_nombre_agente($id_agente);
					
						// delete data
						$sql1='SELECT * FROM tagente_modulo WHERE id_agente = '.$id_agente;
						$result1=mysql_query($sql1);
						while ($row=mysql_fetch_array($result1)){
							$sql_delete1="DELETE FROM talerta_agente_modulo WHERE id_agente_modulo=".$row["id_agente_modulo"];
							$result = mysql_query($sql_delete1);
						} // while			
					}//if 
				}// for
			}//delete
				
		} else { // Form view
			?>
							
			<h2><?php echo $lang_label["agent_conf"] ?><a href="help/<?php echo substr($language_code,0,2);?>/chap3.php#323" target="_help"><img src="images/ayuda.gif" border="0" class="help"></a></h2>
			<h3><?php echo $lang_label["config_manage"]; ?></h3>
			<form method="post" action="index.php?sec=gagente&sec2=godmode/agentes/config&operacion=1">
			<table width=450 border=0 cellspacing=3 cellpadding=5>
			<tr>
			<td class="datost"><b><?php echo $lang_label["source_agent"]; ?></b><br><br>
			<select name="origen" class="w130">
			<?php
			if ( (isset($_POST["update_agent"])) AND (isset($_POST["origen"])) ) {
				echo "<option value=".$_POST["origen"].">".dame_nombre_agente($_POST["origen"]);
			}
			// Show combo with agents
			$sql1='SELECT * FROM tagente';
			$result=mysql_query($sql1);
			while ($row=mysql_fetch_array($result)){
				if ( (isset($_POST["update_agent"])) AND (isset($_POST["origen"])) ){
					if ( $_POST["origen"] != $row["id_agente"])
						echo "<option value=".$row["id_agente"].">".$row["nombre"];
				}
				else
					echo "<option value=".$row["id_agente"].">".$row["nombre"];
			}
			echo '</select>&nbsp;&nbsp;<input type=submit name="update_agent" class=sub value="'.$lang_label["get_info"].'"><br><br>';
			echo "<b>".$lang_label["modules"]."</b><br><br>";
			echo "<select name='origen_modulo[]' size=3 multiple=yes class='w130'>";
			if ( (isset($_POST["update_agent"])) AND (isset($_POST["origen"])) ) {
		        	// Populate Module/Agent combo
				$agente_modulo = $_POST["origen"];
				$sql1="SELECT * FROM tagente_modulo WHERE id_agente = ".$agente_modulo;	
				$result = mysql_query($sql1);
				while ($row=mysql_fetch_array($result)){
			 		echo "<option value=".$row["id_agente_modulo"].">".$row["nombre"];	
				}
			}
			echo "</select>";
			?>
			<td class="datost"><b><?php echo $lang_label["copy_conf"]; ?></b><br><br>
			<table>
			<tr class=datos><td><?php echo $lang_label["modules"]; ?><td><input type="checkbox" name="modules" value="1" class="chk"><br>
			<tr class=datos><td><?php echo $lang_label["alerts"]; ?><td><input type="checkbox" name="alerts" value="1" class="chk"><br>
			</table>
			
			<tr><td class="datost">
			<b><?php echo $lang_label["toagent"]; ?></b><br><br>
			<select name=destino[] multiple=yes size=10 class="w130">
			<?php
			// Show combo with agents
			$sql1='SELECT * FROM tagente';
			$result=mysql_query($sql1);
			while ($row=mysql_fetch_array($result)){
				echo "<option value=".$row["id_agente"].">".$row["nombre"];
			}
			?>
			</select>
			
			<td align="right" class="datosb">
			<input type="submit" name="copiar" class="sub" value="<?php echo $lang_label["copy"].'" onClick="if (!confirm("'.$lang_label["are_you_sure"].'")) return false;>'; ?>
			<input type="submit" name="eliminar" class="sub" value="<?php echo $lang_label["delete"].'" onClick="if (!confirm("'.$lang_label["are_you_sure"].'")) return false;>'; ?>
			<tr><td colspan=2>
			</div></td></tr>
			</table>
			
<?php
		}
	} else {
		audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access Agent Config Management Admin section");
		require ("general/noaccess.php");
	}
?>