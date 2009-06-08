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
require("include/config.php");

check_login ();

if (! give_acl ($config['id_user'], 0, "PM")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation","Trying to access module management");
	require ("general/noaccess.php");
	exit;
}

$update_module = (bool) get_parameter_post ('update_module');

// Update
if ($update_module) {
	$name = get_parameter_post ("name");
	$id_type = get_parameter_post ("id_type");
	$description = get_parameter_post ("description");
	$icon = get_parameter_post ("icon");
	$category = get_parameter_post ("category");
	
	$sql_update ="UPDATE ttipo_modulo
		SET descripcion = '".$description."', categoria = '".$category."',
		nombre = '".$name."', icon = '".$icon."'
		WHERE id_tipo = '".$id_type."'";
	$result = mysql_query($sql_update);
	if (! $result)
		echo "<h3 class='error'>".__('Problem modifying module')."</h3>";
	else
		echo "<h3 class='suc'>".__('Module updated successfully')."</h3>";
}

echo "<h2>".__('Module management')." &raquo; ";
echo __('Defined modules')."</h2>";

echo "<table cellpadding='4' cellspacing='4' width='750' class='databox'>";
echo "<th>".__('Icon')."</th>";
echo "<th>".__('Name')."</th>";
echo "<th>".__('Description')."</th>";
$sql = 'SELECT * FROM ttipo_modulo ORDER BY nombre';
$result = mysql_query ($sql);
$color = 0;
while ($row = mysql_fetch_array ($result)){
	if ($color == 1) {
		$tdcolor = "datos";
		$color = 0;
	} else {
		$tdcolor = "datos2";
		$color = 1;
	}
	echo "
	<tr>
		<td class='$tdcolor' align='center'>
		<img src='images/".$row["icon"]."' 
		border='0'>
		</td>
		<td class='$tdcolor'>
		<b>".$row["nombre"]."
		</b></td>
		<td class='$tdcolor'>
		".$row["descripcion"]."
		</td>
	</tr>";
}
echo "</table>";
?>
