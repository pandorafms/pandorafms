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
if (!is_metaconsole()) {
	require_once("include/functions_update_manager.php");
}
else {
	require_once("../../include/functions_update_manager.php");
}

enterprise_include_once("include/functions_update_manager.php");

$current_package = update_manager_get_current_package();

if(!enterprise_installed()){
	$open=true; 
}

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

if (is_metaconsole()) {
	echo "<style type='text/css' media='screen'>
  		@import 'styles/meta_pandora.css';
	</style>";
}

if (is_metaconsole()) {
	echo "<div id='box_online' style='float:right;padding-right:400px;padding-top:40px;padding-bottom:40px;' class='cargatextodialogo'>";
}
else {
	echo "<div id='box_online' style='padding-top:40px;padding-bottom:40px;' class='cargatextodialogo'>";
}

echo "<span class='loading' style='font-size:18pt;'>";
echo "<img src='images/wait.gif' />";
echo "</span><br><br>";

echo "<div><b>" . __('The last version of package installed is:') . "</b></div><br>";
echo "<div id='pkg_version' style='color:#82b92e;font-size:40pt;font-weight:bold;'>" . $current_package . "</div>";

	echo "<div class='checking_package' style='font-size:18pt;width:100%; text-align: center; display: none;'>";
		echo __('Checking for the newest package.');
	echo "</div>";
	
	echo "<div class='downloading_package' style='font-size:18pt;width:100%; text-align: center; display: none;'>";
		echo __('Downloading for the newest package.');
	echo "</div>";
	
	echo "<div class='content'></div>";
	
	echo "<div class='progressbar' style='display: none;'><img class='progressbar_img' src='' /></div>";
	
	
	/* -------------------------------------------------------------------------
	
	Hello there! :)

	We added some of what seems to be "buggy" messages to the openSource version 
	recently. This is not to force open-source users to move to the enterprise 
	version, this is just to inform people using Pandora FMS open source that it 
	requires skilled people to maintain and keep it running smoothly without 
	professional support. This does not imply open-source version is limited 
	in any way. If you check the recently added code, it contains only warnings 
	and messages, no limitations except one: we removed the option to add custom 
	logo in header. In the Update Manager section, it warns about the 'danger’ 
	of applying automated updates without a proper backup, remembering in the 
	process that the Enterprise version comes with a human-tested package. 
	Maintaining an OpenSource version with more than 500 agents is not so 
	easy, that's why someone using a Pandora with 8000 agents should consider 
	asking for support. It's not a joke, we know of many setups with a huge 
	number of agents, and we hate to hear that “its becoming unstable and slow” :(

	You can of course remove the warnings, that's why we include the source and 
	do not use any kind of trick. And that's why we added here this comment, to 
	let you know this does not reflect any change in our opensource mentality of 
	does the last 14 years.

	------------------------------------------------------------------------- */

	if($open){
		echo "
			<br><br>
			<div id='updatemodal' class='publienterprisehide' title='Community version' style=''>
				<img data-title='Enterprise version' class='img_help forced_title' data-use_title_for_force_title='1' src='images/icono_exclamacion_2.png'>
			</div>
			<br>";
	}
	

$enterprise = enterprise_hook('update_manager_enterprise_main');

if ($enterprise == ENTERPRISE_NOT_HOOK) {
	//Open view
	update_manager_main();
}
?>

<script>
var open = "<?php echo $open;?>";
if(open){
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
}
</script>