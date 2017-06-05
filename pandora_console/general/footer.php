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

if (isset($_SERVER['REQUEST_TIME'])) {
	$time = $_SERVER['REQUEST_TIME'];
}
else {
	$time = get_system_time ();
}

$license_file = 'general/license/pandora_info_'.$config["language"].'.html';
if (! file_exists ($config["homedir"] . $license_file)) {
	$license_file = 'general/license/pandora_info_en.html';
}

if (!$config["MR"]) {
	$config["MR"] = 0;
}

echo '<a class="white_bold footer" target="_blank" href="' . $config["homeurl"] . $license_file. '">';

if($current_package == 0){
$build_package_version =	$build_version;
}
else{
$build_package_version =	$current_package;
}


echo sprintf(__('Pandora FMS %s - Build %s - MR %s', $pandora_version, $build_package_version, $config["MR"]));

echo '</a><br />';
echo '<a class="white footer">'. __('Page generated at') . ' '. date('F j, Y h:i a'); //Always use timestamp here
echo '</a><br /><span style="color:#eff">'.__("&reg; Ártica ST").'</span>';

if (isset ($config['debug'])) {
	$cache_info = array();
	$cache_info = db_get_cached_queries();
	echo ' - Saved '.$cache_info[0].' Queries';
}

?>
