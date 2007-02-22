<?PHP

// Babel - The Free Distributed Auditing System
// (c) Artica Soluciones Tecnologicas S.L, 2005-2006
// (c) Sancho Lerena 2006
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.

// NOT FUNCTIONAL YET

$target_agents = 500;
$target_modules = 20;


$dbname="pandora";	// MySQL DataBase
$dbuser="pandora";	// DB User
$dbpassword="pandora";	// Password
$dbhost="localhost";	// MySQL Host
if (! mysql_connect($dbhost,$dbuser,$dbpassword)){
	exit ('ERROR');
}

mysql_select_db("pandora");	
for ($a=0; $a < $target_agents; $a++){
	$id = $a+100;
	$sql1="INSERT INTO tagente (id_agente, nombre) values ( $id, '$a' )";
	echo $sql1;
	mysql_query($sql1);
	for ($b=0; $b < $target_modules; $b++){
		if ($b > 5)
			$sql2="INSERT INTO tagente_modulo (id_agente, nombre, descripcion, id_tipo_modulo) values ( $id, 'random_$b','random_$bdesc', 1 )";
		else
			$sql2="INSERT INTO tagente_modulo (id_agente, nombre, descripcion, id_tipo_modulo) values ( $id, 'curve_$b','curve_$bdesc', 1 )";
		echo $sql2;
		mysql_query($sql2);
	}
}


?>
