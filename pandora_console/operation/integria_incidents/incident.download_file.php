<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

require_once ("../../include/functions_incidents.php");
require_once ("../../include/config.php");

$id_file = $_GET["id_file"];
$filename = $_GET["filename"];
$id_user = $_GET["id_user"];

$integria_api = $config['integria_url']."/include/api.php?return_type=csv&user=".$config['id_user']."&pass=".io_output_password($config['integria_api_password']);

$url = $integria_api."&op=download_file&params=".$id_file;

// Call the integria API
$file = incidents_call_api($url);
	
header("Content-type: binary");
header("Content-Disposition: attachment; filename=\"".$filename."\"");
header("Pragma: no-cache");
header("Expires: 0");

echo base64_decode($file);
?>
