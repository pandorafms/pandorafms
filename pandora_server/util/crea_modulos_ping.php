<?PHP

// (c) 2007, Sancho Lerena <slerena@openideas.info>
//
// Generador de modulos de tipo ICMP: Proc y Data para Pandora FMS 1.3
// Toma como parametros (definidos en el codigo), el id_agente y el fichero de
// entrada con una IP por linea

$id_agente = 1; // id del agente sobre los que colgar los modulos de PING
$id_tipo_proc = 6; // Icmp proc
$id_tipo_data = 7; // Icmp latency
$filename = "lista_ip.txt";

$dbname="pandora";	// MySQL DataBase
$dbuser="pandora";	// DB User
$dbpassword="pandora";	// Password
$dbhost="localhost";	// MySQL Host
if (! mysql_connect($dbhost,$dbuser,$dbpassword)){
	exit ('No conecto al MySQL');
}
mysql_select_db("pandora");

if (! file_exists($filename))
	exit ( "No encuentro $filename");
	
$fichero = fopen($filename,"r");

$ip = "";
echo "Let's go: ";

while (!feof($fichero)){
	$ip = fgets($fichero); // Strip \n
	$ip = substr($ip,0,strlen($ip)-1);
	$sql2="INSERT INTO tagente_modulo (id_agente, nombre, descripcion, id_tipo_modulo, ip_target) values ( $id_agente, '$ip (D)','ICMP Alive', $id_tipo_proc , '$ip')";
	mysql_query($sql2);
	
	$sql2="INSERT INTO tagente_modulo (id_agente, nombre, descripcion, id_tipo_modulo. ip_target) values ( $id_agente, '$ip (P)','ICMP Latency', $id_tipo_data , '$ip')";
	mysql_query($sql2);
	echo ".";
}

?>
