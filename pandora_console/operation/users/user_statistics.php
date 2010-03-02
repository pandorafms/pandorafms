<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.




// Load global vars
global $config;
require_once ("include/fgraph.php");

check_login ();

print_page_header (__('User activity statistics'), "images/group.png", false, "", false, "");

if ($config['flash_charts']) {
	echo graphic_user_activity ();
} else {
	print_image ("include/fgraph.php?tipo=user_activity", false, array ("border" => 0));
}

echo '<div id="activity" style="width:700px;">';
// Show last activity from this user
echo "<h2>" . __('This is your last activity in Pandora FMS console') . "</h2>";

$table->width = 650; //Don't specify px
$table->data = array ();
$table->size = array ();
$table->size[2] = '130px';
$table->size[4] = '200px';
$table->head = array ();
$table->head[0] = __('User');
$table->head[1] = __('Category');
$table->head[2] = __('Date');
$table->head[3] = __('Source IP');
$table->head[4] = __('Comments');

$sql = sprintf ("SELECT id_usuario,accion,fecha,ip_origen,descripcion
				FROM tsesion
				WHERE (`utimestamp` > UNIX_TIMESTAMP(NOW()) - 604800) 
				AND `id_usuario` = '%s' ORDER BY `fecha` DESC LIMIT 50", $config["id_user"]);
$sessions = get_db_all_rows_sql ($sql);

if ($sessions === false)
	$sessions = array (); 

foreach ($sessions as $session) {
	$data = array ();
	
	$data[0] = '<strong>'.$session['id_usuario'].'</strong>';
	$data[1] = $session['accion'];
	$data[2] = $session['fecha'];
	$data[3] = $session['ip_origen'];
	$data[4] = $session['descripcion'];
	
	array_push ($table->data, $data);
}
print_table ($table);
echo "</div>"; // activity

?>
