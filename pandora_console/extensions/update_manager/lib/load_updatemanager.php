<?php
// Open Update Manager
// ===================
// Copyright (c) 2008 Artica Soluciones Tecnologicas, http://www.artica.es
// Copyright (c) 2008 Esteban Sanchez Muñoz <estebans@artica.es>

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


/* Change to E_ALL for development/debugging */
error_reporting (E_ALL);

/* Database backend, not really tested with other backends, so it's 
 not functional right now */
define ('DB_BACKEND', 'mysql');

if (! extension_loaded ('mysql'))
	die ('Your PHP installation appears to be missing the MySQL extension which is required.');

require_once ('libupdate_manager.php');

$db =& um_db_connect (DB_BACKEND, DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
flush ();
?>
