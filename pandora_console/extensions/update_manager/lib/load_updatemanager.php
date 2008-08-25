<?php


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
