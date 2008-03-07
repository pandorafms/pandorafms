<?PHP
// Pandora FMS - the Free Monitoring System
// ========================================
// Copyright (c) 2004-2008 Sancho Lerena, slerena@gmail.com
// Main PHP/SQL code development, project architecture and management.
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.

global $config;

echo "<center>";
echo '<a class="white_bold" target="_new" href="general/license/pandora_info_'.$config["language"].'.html">
Pandora FMS '.$pandora_version.' Build '.$build_version.'<br>'.
lang_string ("gpl_notice").'</a><br>';
	if (isset($_SERVER['REQUEST_TIME'])) {
		$time = $_SERVER['REQUEST_TIME'];
	} else {
		$time = time();
	}
	echo "<a class='white'>".$lang_label["gen_date"]." ".date("D F d, Y H:i:s", $time)."</a><br>";
echo "</center>";
?>
