<?php

//Pandora FMS- http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

function view_logfile ($file_name) {
	global $config;

	if (!file_exists($file_name)){
		echo "<h2 class=error>".__("Cannot find file"). "(".$file_name;
		echo ")</h1>";
	}  else {
		if (filesize ($file_name) > 512000) {
			echo "<h2 class=error>".__("File is too large (> 500KB)"). "(".$file_name;
			echo ")</h1>";
		} else {
			$data = file_get_contents ($file_name);			
			echo "<h2>$file_name (".format_numeric(filesize ($file_name)/1024)." KB) </h2>";
			echo "<textarea style='width: 95%; height: 200px;' name='$file_name'>";
			echo $data;
			echo "</textarea><br><br>";
		}
	}
}


function pandoralogs_extension_main () {
	global $config;

    global $config;
    if (! give_acl ($config['id_user'], 0, "PM") && ! is_user_admin ($config['id_user'])) {
	    audit_db ($config['id_user'], $_SERVER['REMOTE_ADDR'], "ACL Violation", "Trying to access Setup Management");
	    require ("general/noaccess.php");
	    return;
    }


	print_page_header (__("System logfile viewer"), "images/extensions.png", false, "", true, "" );

	echo "<p>This tool is used just to view your Pandora FMS system logfiles directly from console</p>";

	view_logfile ($config["homedir"]."/pandora_console.log");
	view_logfile ("/var/log/pandora/pandora_server.log");
	view_logfile ("/var/log/pandora/pandora_server.error");
}

add_godmode_menu_option (__('System logfiles'), 'PM','glog',"");
add_extension_godmode_function('pandoralogs_extension_main');

?>
