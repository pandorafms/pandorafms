<?PHP
// Pandora FMS - the Free Monitoring System
// ========================================
// Copyright (c) 2004-2008 Sancho Lerena, slerena@gmail.com
// Copyright (c) 2008 Esteban Sanchez, estebans@artica.es
// Main PHP/SQL code development, project architecture and management.
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.

require_once ("../include/config.php");
require_once ("../include/functions.php");

echo '<html><head><title>'.__('Pandora FMS help system').'</title></head>';
echo '<link rel="stylesheet" href="../include/styles/'.$config['style'].'.css" type="text/css">';
echo '<body>';

$id = get_parameter ('id');
$help_file = $config["homedir"]."/include/help/".$config["language"]."/help_".$id.".php";

if (! $id || ! file_exists ($help_file)) {
	echo "<div class='databox' id='login'><div id='login_f' class='databox'>";
	echo '<h1 id="log_f" style="margin-top: 0px;" class="error">';
	echo __('Help system error');
	echo "</h1><div id='noa' style='width:120px' >";
	echo "<img src='../images/help.jpg' alt='No help section'></div>";
	echo "<div style='width: 350px'>";
	echo '<a href="index.php"><img src="../images/pandora_logo.png" border="0"></a><br>';
	echo "</div>";
	echo '<div class="msg">'.__('Pandora FMS help system has been called with a help reference that currently don\'t exist. There is no help content to show.').'</div></div></div>';
	return;
}

/* Show help */
echo '<div>';
echo '<p style="text-align: right"><strong>'.__('Pandora FMS Help System').'</strong></p>';
echo '</div>';
echo '<hr width="100%" size="1" />';
echo '<div style="font-family: verdana, arial; font-size: 11px; text-align:left">';
echo '<div style="font-size: 12px; margin-left: 30px; margin-right:25px;">';
require_once ($help_file);
echo '</div>';
echo '<br /><br /><hr width="100%" size="1" />';
echo '<div style="font-family: verdana, arial; font-size: 11px;">';
include ('footer.php');
?>
</body>
</html>
