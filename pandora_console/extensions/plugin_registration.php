<?php

//Pandora FMS- http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.


function pluginreg_extension_main () {
	global $config;

	echo "<h2>Plugin registration</h2>";

	echo "<div class=notify>";
	echo __("This extension makes registration of server plugins more easy. Here you can upload a server plugin in Pandora FMS 3.x zipped format (.pspz). Please refer to documentation on how to obtain and use Pandora FMS Server Plugins.<br><br>You can get more plugins in our <a href='http://pandorafms.org/index.php?sec=community&sec2=repository&lng=en'>Public Resource Library</A>");
	echo "</div>";
	
	echo "<br><br>";

	if (!isset ($_FILES['plugin_upload']['tmp_name'])){
		// Upload form
		echo "<form name='submit_plugin' method='post' enctype='multipart/form-data'>";
		echo '<table class="databox" id="table1" width="50%" border="0" cellpadding="4" cellspacing="4">';
		echo "<tr><td class='datos'><input type='file' name='plugin_upload'>";
		echo "<td class='datos'><input type=submit class='sub next' value='".__('Upload')."'>";
		echo "</form></table>";
		return;
	} 	
	
	$config["plugin_store"] = $config["attachment_store"] . "/plugin";
	$zip = zip_open($_FILES['plugin_upload']['tmp_name']);

	if ($zip) {
		while ($zip_entry = zip_read($zip)) {
		    if (zip_entry_open($zip, $zip_entry, "r")) {
		    	if (zip_entry_name($zip_entry) == "plugin_definition.ini"){
		    		$basepath = $config["attachment_store"];
		    	} else {
		    		$basepath = $config["plugin_store"];
		    	}
		    	$filename = $basepath . "/". zip_entry_name($zip_entry);
		    	$fp = fopen($filename, 'w');
		    	$buf = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
				fwrite($fp, $buf);
				fclose($fp);
				chmod ($filename, 0755);
		        zip_entry_close($zip_entry);
		    }
		}
		zip_close($zip);
	}

	// Parse with sections
	if (! $ini_array = parse_ini_file($config["attachment_store"] . "/plugin_definition.ini", true)){
		echo "<h2 class=error>".__("Cannot load INI file")."</h2>";
		return;
	}

	$exec_path = $config["plugin_store"] . "/" . $ini_array["plugin_definition"]["filename"];
	if (!file_exists($exec_path)){
		echo "<h2 class=error>".__("Plugin exec not found. Aborting!")."</h2>";
		unlink ($config["attachment_store"] . "/plugin_definition.ini");
		return;
	}

	// Verify if a plugin with the same name is already registered

	$sql0 = "SELECT COUNT(*) FROM tplugin WHERE name = '" . mysql_escape_string ($ini_array["plugin_definition"]["name"]) . "'";
	$result = get_db_sql ($sql0);
	
	
	if ($result> 0) {
		echo "<h2 class=error>".__("Plugin already registered. Aborting!")."</h2>";
		unlink ($config["attachment_store"] . "/plugin_definition.ini");
		return;;
	}

	$sql1 = "INSERT INTO tplugin (name, description, max_timeout, execute, net_dst_opt, net_port_opt, user_opt, pass_opt, plugin_type) VALUES (
	'" . mysql_escape_string ($ini_array["plugin_definition"]["name"]) . "' ,
	'" . mysql_escape_string ($ini_array["plugin_definition"]["description"]) . "' ,
	'" . $ini_array["plugin_definition"]["timeout"] . "' ,
	'" . mysql_escape_string ($exec_path) . "' ,
	'" . $ini_array["plugin_definition"]["ip_opt"] . "' ,
	'" . $ini_array["plugin_definition"]["port_opt"] . "' ,
	'" . $ini_array["plugin_definition"]["user_opt"] . "' ,
	'" . $ini_array["plugin_definition"]["pass_opt"] . "' ,
	'" . $ini_array["plugin_definition"]["plugin_type"] . 
	"')";

	$create_id = process_sql($sql1, "insert_id");

	for ($ax=1; $ax <= $ini_array["plugin_definition"]["total_modules_provided"]; $ax++){
		$label = "module".$ax;

		$sql2 = "INSERT INTO tnetwork_component (name, description, id_group, type, max, min, module_interval, id_module_group, id_modulo, plugin_user, plugin_pass, plugin_parameter, max_timeout, history_data, min_warning, min_critical, min_ff_event, tcp_port, id_plugin) VALUES (

		'".mysql_escape_string ($ini_array[$label]["name"])."', 
		'".mysql_escape_string ($ini_array[$label]["description"]) ."', 
		'".$ini_array[$label]["id_group"]."', 
		'".$ini_array[$label]["type"]."', 
		'".$ini_array[$label]["max"]."', 
		'".$ini_array[$label]["min"]."', 
		'".$ini_array[$label]["module_interval"]."', 
		'".$ini_array[$label]["id_module_group"]."', 
		'".$ini_array[$label]["id_modulo"]."', 
		'".mysql_escape_string ($ini_array[$label]["plugin_user"])."', 
		'".mysql_escape_string ($ini_array[$label]["plugin_pass"])."', 
		'".mysql_escape_string ($ini_array[$label]["plugin_parameter"])."', 
		'".$ini_array[$label]["max_timeout"]."', 
		'".$ini_array[$label]["history_data"]."', 
		'".$ini_array[$label]["min_warning"]."', 
		'".$ini_array[$label]["min_critical"]."', 
		'".$ini_array[$label]["min_ff_event"]."', 
		'".$ini_array[$label]["tcp_port"]."', 
		'".$create_id."')";
	
		process_sql($sql2);
		
		echo "<h3 class=suc>".__("Module plugin registered"). " : ". $ini_array[$label]["name"] ."</h2>";
	}
	
	echo "<h2 class=suc>".__("Plugin"). " ". $ini_array["plugin_definition"]["name"] . " ". __("Registered successfully")."</h2>";
	unlink ($config["attachment_store"] . "/plugin_definition.ini");	

}

add_godmode_menu_option (__('Plugin register'), 'PM','gservers','');
add_extension_godmode_function('pluginreg_extension_main');

?>
