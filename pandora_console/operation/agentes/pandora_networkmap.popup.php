<?php
// ______                 __                     _______ _______ _______
//|   __ \.---.-.-----.--|  |.-----.----.---.-. |    ___|   |   |     __|
//|    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
//|___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
//
// ============================================================================
// Copyright (c) 2007-2010 Artica Soluciones Tecnologicas, http://www.artica.es
// This code is NOT free software. This code is NOT licenced under GPL2 licence
// You cannnot redistribute it without written permission of copyright holder.
// ============================================================================



if (! isset($_SESSION["id_usuario"])) {
	session_start();
}

// Global & session management
require_once ('../../../include/config.php');
require_once ('../../../include/auth/mysql.php');
require_once ($config['homedir'] . '/include/functions.php');
require_once ($config['homedir'] . '/include/functions_db.php');
require_once ($config['homedir'] . '/include/functions_reporting.php');
require_once ($config['homedir'] . '/include/functions_graph.php');
require_once ($config['homedir'] . '/include/functions_modules.php');
require_once ($config['homedir'] . '/include/functions_ui.php');
require_once ($config['homedir'] . '/include/functions_pandora_networkmap.php');

//error_reporting(E_ALL);
//ini_set("display_errors", 1);

check_login ();

$id_node = (int)get_parameter('id', 0);
$row = db_get_row('tnetworkmap_enterprise_nodes', 'id', $id_node);
$networkmap = db_get_row('tnetworkmap_enterprise', 'id', $row['id_networkmap_enterprise']);

// ACL for the network map
$networkmap_read = check_acl ($config['id_user'], $networkmap['id_group'], "MR");
$networkmap_write = check_acl ($config['id_user'], $networkmap['id_group'], "MW");
$networkmap_manage = check_acl ($config['id_user'], $networkmap['id_group'], "MM");

if (!$networkmap_read && !$networkmap_write && !$networkmap_manage) {
	db_pandora_audit("ACL Violation",
		"Trying to access networkmap enterprise");
	require ("general/noaccess.php");
	return;
}

$user_readonly = !$networkmap_write && !$networkmap_manage;

$refresh_state = (int)get_parameter('refresh_state', 0);

$options = db_get_value('options', 'tnetworkmap_enterprise_nodes', 'id', $id_node);
$options = json_decode($options, true);

//The next line "<!DOCTYPE...." it is necesary for the fucking IE9 because
//this crap browser doesn't execute correcly the getContext without this line.
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
		<title><?php echo __('Details of node:') . ' ' . $options['text'];?></title>
		<script type="text/javascript" src="../../include/javascript/jquery-1.6.1.min.js"></script>
		<script type="text/javascript" src="../../../include/javascript/jquery.colorpicker.js"></script>
		<!-- ui_require_jquery_file('colorpicker'); -->
	</head>
	<body>
		<style type="text/css">
		* {
			margin: 0;
			padding: 0;
		}
		</style>
		<?php
		show_node_info($id_node, $refresh_state, $user_readonly);
		?>
	</body>
</html>
