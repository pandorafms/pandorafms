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


require_once ("../include/config.php");
require_once ("../include/functions.php");
require_once ("../include/functions_html.php");
?>
<html style="height:100%; margin-top: 25px; margin-left: 15px; margin-right: 15px;"><head><title>
<?php
	echo __('Pandora FMS help system');
?>
</title>
<meta http-equiv="content-type" content="text/html; charset=utf-8">
</head>
<?php echo '<link rel="stylesheet" href="../include/styles/'.$config['style'].'.css" type="text/css">'; ?>
<body style="background-color: #555555; height: 100%; ">
<?php

$id = get_parameter ('id');

if (! isset($_SESSION['id_usuario'])) {
	session_start();
	session_write_close();
}

$user_language = get_user_language ($_SESSION['id_usuario']);
if (file_exists ('../include/languages/'.$user_language.'.mo')) {
	$l10n = new gettext_reader (new CachedFileReader ('../include/languages/'.$user_language.'.mo'));
	$l10n->load_tables();
}

/* Possible file locations */
$safe_language = safe_url_extraclean ($user_language, "en");
$safe_id = safe_url_extraclean ($id, "");
$files = array ($config["homedir"]."/include/help/".$safe_language."/help_".$safe_id.".php",
	$config["homedir"].ENTERPRISE_DIR."/include/help/".$safe_language."/help_".$safe_id.".php",
	$config["homedir"].ENTERPRISE_DIR."/include/help/en/help_".$safe_id.".php",
	$config["homedir"]."/include/help/en/help_".$safe_id.".php");
$help_file = '';
foreach ($files as $file) {
	if (file_exists ($file)) {
		$help_file = $file;
		break;
	}
}

if (! $id || ! file_exists ($help_file)) {
	echo '<div id="main_help">';
	echo '<span style="float:left; margin: 20px 20px 0px 20px; padding: 0px">';
	echo html_print_image('images/pandora_tinylogo.png', true, array("border" => '0'));
	echo "</span>";
	echo '<p style="text-align: right"><strong style="float: right; padding-top: 12px; padding-right: 8px;">'. html_print_image('images/interrogant.png', true, array('title' => __('Pandora FMS help system'))) . '</strong></p>';
	echo '<div style="clear:both;"></div>';
	echo '<div style="font-family: verdana, arial; font-size: 11px; text-align:left">';
	echo '<div style="font-size: 12px; margin-left: 20px; margin-right:20px; " class="databox">';
	echo '<h1>';
	echo __('Help system error');
	echo "</h1><HR><br>";
	echo "<div style='text-align: center;'>";
	echo html_print_image("images/pandora_logo.png", array("border" => '0')) . '<br>';
	echo "</div>";
	echo '<div class="msg">'.__('Pandora FMS help system has been called with a help reference that currently don\'t exist. There is no help content to show.').'</div></div></div>';
	echo '<br /><br />';
	echo '<div style="text-align: center; padding: 15px; font-family: verdana, arial; font-size: 11px;">';
	include ('footer.php');
	return;
}

/* Show help */
echo '<div id="main_help">';
echo '<span style="float:left; margin: 20px 20px 0px 20px; padding: 0px">';
echo html_print_image('images/pandora_tinylogo.png', true, array("border" => '0'));
echo "</span>";
echo '<p style="text-align: right"><strong style="float: right; padding-top: 12px; padding-right: 8px;">'. html_print_image('images/interrogant.png', true, array('title' => __('Pandora FMS help system'))) . '</strong></p>';
echo '<div style="clear:both;"></div>';
echo '<div style="font-family: verdana, arial; font-size: 11px; text-align:left">';
echo '<div style="font-size: 12px; margin-left: 20px; margin-right:20px;" class="databox">';
ob_start();
require_once ($help_file);
$help = ob_get_contents();
ob_end_clean();

// Add a line after H1 tags
$help = str_replace('</H1>', '</H1><HR>', $help);
$help = str_replace('</h1>', '</h1><hr>', $help);
echo $help;
echo '<br /><br /></div>';
echo '<div style="text-align: center; padding: 15px; font-family: verdana, arial; font-size: 11px;">';
include ('footer.php');
?>
</body>
</html>
