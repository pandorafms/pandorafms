<?php
// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2018 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// Global & session manageme
session_id($_REQUEST["session_id"]);
if (file_exists(session_save_path() . "/pansess_" . session_id()) ) {
    $user = file_get_contents(session_save_path() . "/pansess_" . session_id());
}
session_start();
if (isset($user)) {
    $_SESSION["id_usuario"] = $user;
}
session_write_close();



require_once ('config.php');
require_once ($config['homedir'] . '/include/auth/mysql.php');
require_once ($config['homedir'] . '/include/functions.php');
require_once ($config['homedir'] . '/include/functions_db.php');
require_once ($config['homedir'] . '/include/functions_reporting.php');
require_once ($config['homedir'] . '/include/functions_graph.php');
require_once ($config['homedir'] . '/include/functions_custom_graphs.php');
require_once ($config['homedir'] . '/include/functions_modules.php');
require_once ($config['homedir'] . '/include/functions_agents.php');
require_once ($config['homedir'] . '/include/functions_tags.php');

check_login();

global $config;
//get_parameter(array)('data', '');

$params = json_decode($_REQUEST['data'], true);

// Metaconsole connection to the node
$server_id = $params['server_id'];

if ($config["metaconsole"] && !empty($server_id)) {
	$server = metaconsole_get_connection_by_id($server_id);
	// Error connecting
	if (metaconsole_connect($server) !== NOERR) {
		echo "<html>";
			echo "<body>";
				ui_print_error_message(__('There was a problem connecting with the node'));
			echo "</body>";
		echo "</html>";
		exit;
	}
}


$user_language = get_user_language($config['id_user']);
if (file_exists ('languages/'.$user_language.'.mo')) {
	$l10n = new gettext_reader (new CachedFileReader ('languages/'.$user_language.'.mo'));
	$l10n->load_tables();
}

?>
<!DOCTYPE>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>Pandora FMS Graph (<?php echo agents_get_alias($agent_id) . ' - ' . $interface_name; ?>)</title>
        <link rel="stylesheet" href="styles/pandora.css" type="text/css" />
		<link rel="stylesheet" href="styles/pandora_minimal.css" type="text/css" />
		<link rel="stylesheet" href="styles/jquery-ui-1.10.0.custom.css" type="text/css" />
		<script language="javascript" type='text/javascript' src='javascript/pandora.js'></script>
		<script language="javascript" type='text/javascript' src='javascript/jquery-1.9.0.js'></script>
		<script language="javascript" type='text/javascript' src='javascript/jquery.pandora.js'></script>
		<script language="javascript" type='text/javascript' src='javascript/jquery.jquery-ui-1.10.0.custom.js'></script>
		<script language="javascript" type="text/javascript" src="graphs/flot/jquery.flot.js"></script>
		<script language="javascript" type="text/javascript" src="graphs/flot/jquery.flot.min.js"></script>
		<script language="javascript" type="text/javascript" src="graphs/flot/jquery.flot.time.js"></script>
		<script language="javascript" type="text/javascript" src="graphs/flot/jquery.flot.pie.js"></script>
		<script language="javascript" type="text/javascript" src="graphs/flot/jquery.flot.crosshair.min.js"></script>
		<script language="javascript" type="text/javascript" src="graphs/flot/jquery.flot.stack.min.js"></script>
		<script language="javascript" type="text/javascript" src="graphs/flot/jquery.flot.selection.min.js"></script>
		<script language="javascript" type="text/javascript" src="graphs/flot/jquery.flot.resize.min.js"></script>
		<script language="javascript" type="text/javascript" src="graphs/flot/jquery.flot.threshold.js"></script>
		<script language="javascript" type="text/javascript" src="graphs/flot/jquery.flot.threshold.multiple.js"></script>
		<script language="javascript" type="text/javascript" src="graphs/flot/jquery.flot.symbol.min.js"></script>
		<script language="javascript" type="text/javascript" src="graphs/flot/jquery.flot.exportdata.pandora.js"></script>
		<script language="javascript" type="text/javascript" src="graphs/flot/jquery.flot.axislabels.js"></script>
		<script language="javascript" type="text/javascript" src="graphs/flot/pandora.flot.js"></script>
	</head>
	<body bgcolor="#ffffff" style='background:#ffffff;'>
<?php

    	$params['only_image'] = false;
		$params['width']      = (int) $_REQUEST['viewport_width'];
		$params['menu']       = false;

		if((!isset($params['width']) || ($params['width'] <= 0))) {
			$params['width'] = 1048;
		}

		$params_combined = json_decode($_REQUEST['data_combined'], true);
		$module_list     = json_decode($_REQUEST['data_module_list'], true);
		$type_graph_pdf  = $_REQUEST['type_graph_pdf'];

		$aux_font_size = $config['font_size'];
		$config['font_size'] = $config['font_size'] + 3;

		if($type_graph_pdf == 'combined'){
			echo '<div>';
				echo graphic_combined_module(
					$module_list,
					$params,
					$params_combined
				);
			echo '</div>';
		}
		elseif($type_graph_pdf == 'sparse'){
			echo '<div>';
				echo grafico_modulo_sparse($params);
			echo '</div>';
		}

		$config['font_size'] = $aux_font_size;
?>
	</body>

</html>
