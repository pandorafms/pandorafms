<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas, http://www.artica.es
// Please see http://pandora.sourceforge.net for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

check_login ();

if (! give_acl ($config['id_user'], 0, "PM")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access extensions list");
	include ("general/noaccess.php");
	exit;
}

if (sizeof ($config['extensions']) == 0) {
	echo '<h3>'.__('There are no extensions defined').'</h3>';
	return;
}

echo '<h2>'.__('Defined extensions').'</h2>';
$table->width = '95%';
$table->head = array ();
$table->head[0] = __('Name');
$table->data = array ();

foreach ($config['extensions'] as $extension) {
	if ($extension['godmode_function'] == '')
		continue;
	$data = array ();
	$data[0] = '<a href="index.php?sec=gextensions&sec2='.$menu['sec2'].'" class="mn">'.$menu['name'];
	array_push ($table->data, $data);
}

print_table ($table);
?>
