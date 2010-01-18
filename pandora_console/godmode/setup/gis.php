<?php
/**
 * Pandora FMS- http://pandorafms.com
 * ==================================================
 * Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation for version 2.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

// Load global vars
require_once ("include/config.php");

check_login ();

if (! give_acl ($config['id_user'], 0, "PM") && ! is_user_admin ($config['id_user'])) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation", "Trying to access Visual Setup Management");
	require ("general/noaccess.php");
	return;
}

require_once ('include/functions_gis.php');

//printMap('map', 16, 19, 40.42056, -3.708187, array('OSM' => 'http://192.168.50.65/tiles/${z}/${x}/${y}.png'));
printMap('map', 16, 19, 40.42056, -3.708187, array('OSM' => 'http://tile.openstreetmap.org/${z}/${x}/${y}.png', array('Navigation','PanZoomBar','ScaleLine')));
makeLayer("layer");
addPoint('layer', __("center"), 40.42056, -3.708187);

echo "<h2>".__('Pandora Setup')." &raquo; ";
echo __('Map GIS')."</h2>";

$table->width = '90%';
$table->data = array ();

$table->style[0] = 'vertical-align: top;';

$table->data[1][0] = __('Coordenades and zoom by default:');
$table->data[1][1] = "<div id='map' style='width: 300px; height: 300px; border: 1px solid black;' ></div>";

print_table ($table);
?>
<a href="javascript: addPoint('layer', 'Pepito', -3.709, 40.423);">prueba punto</a>