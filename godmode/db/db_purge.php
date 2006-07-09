<?php 

// Pandora - The Free Monitoring System
// This code is protected by GPL license.
// Este codigo esta protegido por la licencia GPL.
// Sancho Lerena <slerena@gmail.com>, 2003-2006
// Raul Mateos <raulofpandor@gmail.com>, 2006

// Load global vars
require("include/config.php");
//require("include/functions.php");
//require("include/functions_db.php");
if (comprueba_login() == 0) 
   $id_usuario= $_SESSION["id_usuario"];
   if (give_acl($id_usuario, 0, "DM")==1){
 	// Todo for a good DB maintenance 
 	/* 
 		
 		- A function to "compress" data, and interpolate big chunks of data (1 month - 60000 registers) 
 		  onto a small chunk of interpolated data (1 month - 600 registers)
 		
 		- A more powerful selection (by Agent, by Module, etc).
 	*/
	 ?>	
	<h2><?php echo $lang_label["dbmain_title"] ?></h2>
	<h3><?php echo $lang_label["db_purge"] ?><a href='help/<?php echo $help_code ?>/chap8.php#8' target='_help' class='help'>&nbsp;<span><?php echo $lang_label["help"] ?></span></a></h3>
	<img src="reporting/fgraph.php?tipo=db_agente_purge&id=-1"><br><br>
	<h3><?php echo $lang_label["get_data_agent"] ?></h3>
	<?php
	// All data (now)
	$purge_all=date("Y-m-d H:i:s",time());
		
	// 1 day ago
	$d1_year = date("Y", time()-28800);
	$d1_month = date("m", time()-28800);
	$d1_day = date ("d", time()-28800);
	$d1_hour = date ("H", time()-28800);
	$minuto = date("i",time());
	$segundo = date("s",time());
	$d1 = $d1_year."-".$d1_month."-".$d1_day." ".$d1_hour.":".$minuto.":".$segundo."";
	
	// 3 days ago
	$d3_year = date("Y", time()-86400);
	$d3_month = date("m", time()-86400);
	$d3_day = date ("d", time()-86400);
	$d3_hour = date ("H", time()-86400);
	$d3 = $d3_year."-".$d3_month."-".$d3_day." ".$d3_hour.":".$minuto.":".$segundo."";
	
	// Date 24x7 Hours ago (a week)
	$week_year = date("Y", time()-604800);
	$week_month = date("m", time()-604800);
	$week_day = date ("d", time()-604800);
	$week_hour = date ("H", time()-604800);
	$week = $week_year."-".$week_month."-".$week_day." ".$week_hour.":".$minuto.":".$segundo."";
	
	// Date 24x7x2 Hours ago (two weeks)
	$week2_year = date("Y", time()-1209600);
	$week2_month = date("m", time()-1209600);
	$week2_day = date ("d", time()-1209600);
	$week2_hour = date ("H", time()-1209600);
	$week2 = $week2_year."-".$week2_month."-".$week2_day." ".$week2_hour.":".$minuto.":".$segundo."";
		
	// Date 24x7x30 Hours ago (one month)
	$month_year = date("Y", time()-2592000);
	$month_month = date("m", time()-2592000);
	$month_day = date ("d", time()-2592000);
	$month_hour = date ("H", time()-2592000);
	$month = $month_year."-".$month_month."-".$month_day." ".$month_hour.":".$minuto.":".$segundo."";
	
	// Three months
	$month3_year = date("Y", time()-7257600);
	$month3_month = date("m", time()-7257600);
	$month3_day = date ("d", time()-7257600);
	$month3_hour = date ("H", time()-7257600);
	$month3 = $month3_year."-".$month3_month."-".$month3_day." ".$month3_hour.":".$minuto.":".$segundo."";
	$datos_rango3=0;$datos_rango2=0;$datos_rango1=0;$datos_rango0=0; $datos_rango00=0; $datos_rango11=0; $datos_total=0;

	# ADQUIRE DATA PASSED AS FORM PARAMETERS
	# ======================================
	# Purge data using dates
		
	if (isset($_POST["agent"])){
		$id_agent =$_POST["agent"];
	} 
	# Purge data using dates
	if (isset($_POST["purgedb"])){
		$from_date =$_POST["date_purge"];
		if (isset($id_agent)){
			if ($id_agent != -1) {
			echo $lang_label["purge_task"].$id_agent." / ".$from_date;
				echo "<h3>".$lang_label["please_wait"]."<br>",$lang_label["while_delete_data"].$lang_label["agent"]."</h3>";
				$sql_2='SELECT * FROM tagente_modulo WHERE id_agente = '.$id_agent;
				$result_t=mysql_query($sql_2);
				while ($row=mysql_fetch_array($result_t)){
					echo $lang_label["deleting_records"].dame_nombre_modulo_agentemodulo($row["id_agente_modulo"]);
					flush();
					ob_flush();
					echo "<br>";
					$query = "DELETE FROM tagente_datos WHERE id_agente_modulo = ".$row["id_agente_modulo"]." and timestamp < '".$from_date."'";
					mysql_query($query);
					$query = "DELETE FROM tagente_datos_inc WHERE id_agente_modulo = ".$row["id_agente_modulo"]." and timestamp < '".$from_date."'";
					mysql_query($query);
					$query = "DELETE FROM tagente_datos_string WHERE id_agente_modulo = ".$row["id_agente_modulo"]." and timestamp < '".$from_date."'";
					mysql_query($query);		
				}
			}
			else {
				echo $lang_label["deleting_records"].$lang_label["all_agents"];
				flush();
				ob_flush();
				$query = "DELETE FROM tagente_datos WHERE timestamp < '".$from_date."'";
				mysql_query($query);
				$query = "DELETE FROM tagente_datos_inc WHERE timestamp < '".$from_date."'";
				mysql_query($query);
				$query = "DELETE FROM tagente_datos_string WHERE timestamp < '".$from_date."'";
				mysql_query($query);
			}
		echo "<br><br>";
		}
	mysql_close();
	}
	
	# Select Agent for further operations.
	?>
	<form action='index.php?sec=gdbman&sec2=godmode/db/db_purge' method='post'>
	<table><tr><td class='datos'>
	<select name='agent' class='w130'>
	
	<?php
	if (isset($_POST["agent"]) and ($id_agent !=-1))
		echo "<option value='".$_POST["agent"]."'>".dame_nombre_agente($_POST["agent"]);
	echo "<option value=-1>".$lang_label["choose_agent"];
	$result_t=mysql_query("SELECT * FROM tagente");
	while ($row=mysql_fetch_array($result_t)){	
		echo "<option value='".$row["id_agente"]."'>".$row["nombre"];
	}
	?>
	</select>
	<a href="#" class="tip">&nbsp;<span><?php echo $help_label["db_purge0"] ?></span></a>
	<td><input class='sub' type='submit' name='purgedb_ag' value='<?php echo $lang_label["get_data"] ?>'>
	<a href="#" class="tip">&nbsp;<span><?php echo $help_label["db_purge1"] ?></span></a>
	</table><br>
	
	<?php	
	# End of get parameters block
	
	if (isset($_POST["agent"]) and ($id_agent !=-1)){
		echo "<h3>".$lang_label["db_agent_bra"].dame_nombre_agente($id_agent).$lang_label["db_agent_ket"]."</h3>";
		$sql_2='SELECT * FROM tagente_modulo WHERE id_agente = '.$id_agent;		
		$result_t=mysql_query($sql_2);
		while ($row=mysql_fetch_array($result_t)){	
			flush();
   			ob_flush();
			$rango00=mysql_query('SELECT COUNT(*) FROM tagente_datos WHERE id_agente_modulo = '.$row["id_agente_modulo"].' and  timestamp > "'.$d1.'"');
			$rango0=mysql_query('SELECT COUNT(*) FROM tagente_datos WHERE id_agente_modulo = '.$row["id_agente_modulo"].' and  timestamp > "'.$d3.'"');
			$rango1=mysql_query('SELECT COUNT(*) FROM tagente_datos WHERE id_agente_modulo = '.$row["id_agente_modulo"].' and  timestamp > "'.$week.'"');
			$rango11=mysql_query('SELECT COUNT(*) FROM tagente_datos WHERE id_agente_modulo = '.$row["id_agente_modulo"].' and  timestamp > "'.$week2.'"');
			$rango2=mysql_query('SELECT COUNT(*) FROM tagente_datos WHERE id_agente_modulo = '.$row["id_agente_modulo"].' and  timestamp > "'.$month.'"');		
			$rango3=mysql_query('SELECT COUNT(*) FROM tagente_datos WHERE id_agente_modulo = '.$row["id_agente_modulo"].' and timestamp > "'.$month3.'"');
			$rango4=mysql_query('SELECT COUNT(*) FROM tagente_datos WHERE id_agente_modulo = '.$row["id_agente_modulo"]);
			$row00=mysql_fetch_array($rango00);
			$row3=mysql_fetch_array($rango3);		$row1=mysql_fetch_array($rango1);
			$row2=mysql_fetch_array($rango2); 		$row11=mysql_fetch_array($rango11);
			$row0=mysql_fetch_array($rango0);
			$row4=mysql_fetch_array($rango4);
			$datos_rango00=$datos_rango00+$row00[0];
			$datos_rango0=$datos_rango0+$row0[0];
			$datos_rango3=$datos_rango3+$row3[0];
			$datos_rango2=$datos_rango2+$row2[0];
			$datos_rango1=$datos_rango1+$row1[0];
			$datos_rango11=$datos_rango11+$row11[0];
			$datos_total=$datos_total+$row4[0];
		}	
	}
	
?>

	<table width=300 border=0>
	<tr><td class=datos>
	<?php echo $lang_label["rango3"]?>
	</td>
	<td class=datos>
	<?php echo $datos_rango3 ?>
	</td>
	
	<tr><td class=datos2>
	<?php echo $lang_label["rango2"]?>
	</td>
	<td class=datos2>
	<?php echo $datos_rango2 ?>
	</td>
	
	<tr><td class=datos>
	<?php echo $lang_label["rango11"]?>
	</td>
	<td class=datos>
	<?php echo $datos_rango11 ?>
	</td>
	
	<tr><td class=datos2>
	<?php echo $lang_label["rango1"]?>
	</td>
	<td class=datos2>
	<?php echo $datos_rango1 ?>
	</td>
	
	<tr><td class=datos>
	<?php echo $lang_label["rango0"]?>
	</td>
	<td class=datos>
	<?php echo $datos_rango0 ?>
	</td>
	
	<tr><td class=datos2>
	<?php echo $lang_label["rango00"]?>
	</td>
	<td class=datos2>
	<?php echo $datos_rango00 ?>
	</td>	
	<tr><td class=datos>
	<b><?php echo $lang_label["total_packets"]?></b>
	</td>
	<td class=datos>
	<b><?php echo $datos_total ?></b>
	</td>
	</tr>
	</table>
	<br>
	<h3><?php echo $lang_label["purge_data"] ?></h3>
	<table width=300 border=0>
	<tr><td>
	<select name="date_purge" class="w255">
	<option value="<?php echo $month3 ?>"><?php echo $lang_label["purge_90day"] ?>
	<option value="<?php echo $month ?>"><?php echo $lang_label["purge_30day"] ?>
	<option value="<?php echo $week2 ?>"><?php echo $lang_label["purge_14day"] ?>
	<option value="<?php echo $week ?>"><?php echo $lang_label["purge_7day"] ?>
	<option value="<?php echo $d3 ?>"><?php echo $lang_label["purge_3day"] ?>
	<option value="<?php echo $d1 ?>"><?php echo $lang_label["purge_1day"] ?>
	</select>
	
	<td><input class="sub" type="submit" name="purgedb" value="<?php echo $lang_label["doit"] ?>" onClick="if (!confirm('<?php  echo $lang_label["are_you_sure"] ?>')) return false;">
	</table>
	</form>
	
<?php
   } else {
   	audit_db($id_usuario,$REMOTE_ADDR, "ACL Violation","Trying to access to Database Purge Section");
	include ("general/noaccess.php");
   }
?>