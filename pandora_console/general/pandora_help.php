<?PHP
// Pandora FMS - the Free Monitoring System
// ========================================
// Copyright (c) 2004-2008 Sancho Lerena, slerena@gmail.com
// Main PHP/SQL code development, project architecture and management.
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.

include "../include/config.php";
include "../include/languages/language_".$config["language"].".php";
echo '<link rel="stylesheet" href="../include/styles/'.$config['style'].'.css" type="text/css">';

if (isset($_GET["id"])) {
    $id = $_GET["id"];
    $protofile = $config["homedir"]."/include/help/".$config["language"]."/help_".$id.".php";
    if (file_exists ($protofile)){
		echo "<div>";
        echo "<p align=right>";
        echo "<b>Pandora FMS Help System</b></p>";
		echo "</div>";

        echo "<hr width=100% size=1><div style='font-family: verdana, arial; font-size: 11px; text-align:left'>";
		echo "<div style='font-size: 12px; margin-left: 30px; margin-right:25px;'>";
        include $protofile;
		echo "</div>";
		echo "<br><br><hr width=100% size=1><div style='font-family: verdana, arial; font-size: 11px;'>";
		include "footer.php";
		
    }
    else
        show_help_error();
} else {
    show_help_error();
}

function show_help_error(){
    global $config;
    global $lang_label;
    echo "<div class='databox' id='login'><div id='login_f' class='databox'>";
    echo '<h1 id="log_f" style="margin-top: 0px;" class="error">';
    echo $lang_label['help_error'];
    echo "</h1><div id='noa' style='width:120px' >";
    echo "<img src='../images/help.jpg' alt='No help section'></div>";
    echo "<div style='width: 350px'>";
    echo '<a href="index.php"><img src="../images/pandora_logo.png" border="0"></a><br>';
    echo "</div>";
echo '<div class="msg">'.$lang_label["help_error_msg"].'</div></div></div>';
}

?>