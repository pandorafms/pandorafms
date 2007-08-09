<?PHP

if (! file_exists("include/config.php")){
	echo "<h1>Cannot find config.php!!!. FATAL ERROR UPGRADING</h1>";
	exit;
}

include "include/config.php";

// tagente_datos upgrade

echo "<h1>Updating tagente_datos table...</h1>";
$sql1="SELECT * FROM tagente_datos WHERE utimestamp =0 ";
$result1=mysql_query($sql1);
while ($row1=mysql_fetch_array($result1)){
	$id = $row1["id_agente_datos"];
	$timestamp = $row1["timestamp"];
	$utimestamp = strtotime($timestamp);
	$sql2="UPDATE tagente_datos SET utimestamp = '$utimestamp' WHERE id_agente_datos = $id";
	mysql_query($sql2);
}

echo "<h1>Updating tagente_datos_string table...</h1>";
$sql1="SELECT * FROM tagente_datos_string WHERE utimestamp =0 ";
$result1=mysql_query($sql1);
while ($row1=mysql_fetch_array($result1)){
	$id = $row1["id_tagente_datos_string"];
	$timestamp = $row1["timestamp"];
	$utimestamp = strtotime($timestamp);
	$sql2="UPDATE tagente_datos SET utimestamp = '$utimestamp' WHERE id_tagente_datos_string = $id";
	mysql_query($sql2);
}

echo "<h1>Updating tagente_estado table...</h1>";
$sql1="SELECT * FROM tagente_estado WHERE utimestamp =0 ";
$result1=mysql_query($sql1);
while ($row1=mysql_fetch_array($result1)){
	$id = $row1["id_agente_estado"];
	$timestamp = $row1["timestamp"];
	$utimestamp = strtotime($timestamp);
	$sql2="UPDATE tagente_estado SET utimestamp = '$utimestamp', last_execution_try = '$utimestamp' WHERE id_agente_estado = $id";
	mysql_query($sql2);
}

?>