<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.

global $config;

check_login ();

if (! check_acl ($config['id_user'], 0, "PM") && ! is_user_admin ($config['id_user'])) {
	db_pandora_audit("ACL Violation", "Trying to access Setup Management");
	require ("general/noaccess.php");
	return;
}

ui_require_css_file('update_manager', 'godmode/update_manager/');
require_once("include/functions_update_manager.php");
enterprise_include_once("include/functions_update_manager.php");

$current_package = update_manager_get_current_package();

if(!enterprise_installed()){
	$open=true; 
}

echo "<p><b>" . sprintf(__("The last version of package installed is: %d"),
	$current_package) . "</b></p>";


$memory_limit = ini_get("memory_limit");
$memory_limit = str_replace("M", "", $memory_limit);
$memory_limit = (int)$memory_limit;
if ($memory_limit < 500) {
	ui_print_error_message(
		sprintf(__('Your PHP has set memory limit in %s. For avoid problems with big updates please set to 500M'), ini_get("memory_limit"))
	);
}
$post_max_size = ini_get("post_max_size");
$post_max_size = str_replace("M", "", $post_max_size);
if ($memory_limit < 100) {
	ui_print_error_message(
		sprintf(__('Your PHP has set post parameter max size limit in %s. For avoid problems with big updates please set to 100M'), ini_get("post_max_size"))
	);
}
$upload_max_filesize = ini_get("upload_max_filesize");
$upload_max_filesize = str_replace("M", "", $upload_max_filesize);
if ($memory_limit < 100) {
	ui_print_error_message(
		sprintf(__('Your PHP has set maximum allowed size for uploaded files limit in %s. For avoid problems with big updates please set to 100M'), ini_get("upload_max_filesize"))
	);
}


/* Translators: Do not translade Update Manager, it's the name of the program */




echo "<div id='box_online' class='cargatextodialogo'>";
	echo "<span class='loading' style='font-size:18pt;'>";
	echo "<img src='images/wait.gif' />";
	echo "</span>";
	
	echo "<div class='checking_package' style='font-size:18pt;width:100%; text-align: center; display: none;'>";
	echo __('Checking for the newest package.');
	echo "</div>";
	
	echo "<div class='downloading_package' style='font-size:18pt;width:100%; text-align: center; display: none;'>";
	echo __('Downloading for the newest package.');
	echo "</div>";
	
	echo "<div class='content'></div>";
	
	echo "<div class='progressbar' style='display: none;'><img class='progressbar_img' src='' /></div>";
	
	if($open){
		echo "<div id='updatemodal' class='publienterprise' title='Community version' style=''><img data-title='Enterprise version' class='img_help forced_title' data-use_title_for_force_title='1' src='images/alert_enterprise.png'></div>
		";
	}
	

$enterprise = enterprise_hook('update_manager_enterprise_main');
if ($enterprise == ENTERPRISE_NOT_HOOK) {
	//Open view
	update_manager_main();
}
?>

<script>
$(document).ready(function() {
$('body').append( "<div id='opacidad' style='position:fixed;background:black;opacity:0.6;z-index:1'></div>" );
jQuery.get ("ajax.php",
	{
"page": "general/alert_enterprise",
"message":"infomodal"},
	function (data, status) {
		$("#alert_messages").hide ()
			.empty ()
			.append (data)
			.show ();
	},
	"html"
);

return false;

});
</script>