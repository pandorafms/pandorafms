<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.


// Load global vars
global $config;

require_once ($config["homedir"] . '/include/functions_graph.php'); 

check_login ();

if (! check_acl ($config['id_user'], 0, "IR") == 1) {
	db_pandora_audit("ACL Violation", "Trying to access Incident section");
	require ("general/noaccess.php");
	exit;
}
ui_print_page_header (__('Statistics'), "images/book_edit.png", false, "", false, "");

$integria_api = $config['integria_url']."/include/api.php?user=".$config['id_user']."&pass=".$config['integria_api_password'];
$op = 'get_stats';
$url = "$integria_api&op=$op";

$curlObj = curl_init();
curl_setopt($curlObj, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($curlObj, CURLOPT_URL, $url . "&params=opened");
$opened_tickets = curl_exec($curlObj);
curl_setopt($curlObj, CURLOPT_URL, $url . "&params=closed");
$closed_tickets = curl_exec($curlObj);
curl_close($curlObj);

$opened_tickets = trim($opened_tickets);
$closed_tickets = trim($closed_tickets);

if (!is_numeric($opened_tickets))
	$opened_tickets = 0;
if (!is_numeric($closed_tickets))
	$closed_tickets = 0;

$data = array();
$data[__('Opened tickets')] = $opened_tickets;
$data[__('Closed tickets')] = $closed_tickets;

echo '<table width="90%">
	<tr><td valign="top"><h3>'.__('Incidents by status').'</h3>';
echo pie3d_graph($config['flash_charts'], $data, 370, 180,
	__('Other'), '', $config['homedir'] .  "/images/logo_vertical_water.png",
	$config['fontpath'], $config['font_size']);

echo '</table>';
?>