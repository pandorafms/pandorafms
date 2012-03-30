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

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Pandora FMS - The Flexible Monitoring System - Console error</title>
<meta http-equiv="expires" content="0">
<meta http-equiv="content-type" content="text/html; charset=utf8">
<meta name="resource-type" content="document">
<meta name="distribution" content="global">
<meta name="author" content="Sancho Lerena">
<meta name="copyright" content="This is GPL software. Created by Sancho Lerena and others">
<meta name="keywords" content="pandora, monitoring, system, GPL, software">
<meta name="robots" content="index, follow">
<link rel="icon" href="images/pandora.ico" type="image/ico">
<link rel="stylesheet" href="include/styles/pandora.css" type="text/css">
</head>
<body>

<img src="images/login_background.png" id="login_body">
<div class="databox_error" id="login">
	<br>
	<h1 id="log">Empty configuration table</h1>
	<br>
	<div style="width: 440px; margin: 0 auto auto;">
		<table cellpadding="4" cellspacing="1" width="440">
		<tr><td align="left">
			<a href="index.php"><img src= <?php echo '"'.$relative_link . 'images/pandora_login.png"' ?> border="0" height="100px" alt="Pandora FMS"></a>
		</td><td valign="bottom">
			<br><br>
			Cannot load configuration variables from database. Please check your database setup in the
			<b>include/config.php</b> file or read the documentation on how to setup Pandora FMS.<i><br><br>
			Most likely your database schema has been created but there are is no data in it, you have a problem with the database access credentials or your schema is out of date.
			<br><br>Pandora FMS Console cannot find <i>include/config.php</i> or this file has invalid
			permissions and HTTP server cannot read it. Please read documentation to fix this problem.</i>
		</td></tr>
		</table>
	</div>
	<br>

</div>

<div id="bottom_logo">
	<img src='images/bottom_logo.png' "alt" = "logo"  "border" = "0">
</div>

</body>
</html>
