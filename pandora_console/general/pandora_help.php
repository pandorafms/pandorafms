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
	echo "<div class='databox' id='login'><div id='login_f' class='databox'>";
	echo '<h1 id="log_f" style="margin-top: 0px;" class="error">';
	echo __('Help system error');
	echo "</h1>";
	echo "<div class='noa'>";
	echo '<a href="../index.php">' . html_print_image("images/pandora_logo.png", array("border" => '0')) . '</a><br>';
	echo "</div>";
	echo '<div class="msg">'.__('Pandora FMS help system has been called with a help reference that currently don\'t exist. There is no help content to show.').'</div></div></div>';
	return;
}

/* Show help */
echo '<div id="main_help">';
echo '<div>';
echo '<span style="float:left; margin: 20px; padding: 0px">';
echo html_print_image('images/pandora_textlogo.png', true, array("border" => '0'));
echo "</span>";
echo '<p style="padding-right: 20px; padding-top: 20px; text-align: right"><strong>'.__('Pandora FMS help system').'</strong></p>';
echo '</div><br>';
echo '<hr width="100%" size="1" />';
echo '<div style="font-family: verdana, arial; font-size: 11px; text-align:left">';
echo '<div style="font-size: 12px; margin-left: 30px; margin-right:25px;">';
require_once ($help_file);
echo '</div>';
echo '<br /><br /><hr width="100%" size="1" />';
echo '<div style="text-align: center; padding: 15px; background-color: #6E6E6E; font-family: verdana, arial; font-size: 11px;">';
include ('footer.php');
echo '</div>';
?>
</body>
</html>
