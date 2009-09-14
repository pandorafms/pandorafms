<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
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
<div id="container">
<div id="main">
<div align='center'>
<div id='login_f'>
	<h1 id="log_f" class="error">Problem with Pandora FMS database</h1>
	<div>
		<img src="images/pandora_logo.png" border="0"></a>
	</div>
	<div class="msg">
	Cannot connect to the database, please check your database setup in the <b>include/config.php</b> file or read the documentation on how to setup Pandora FMS.<i><br /><br />
	Probably one or more of your user, database or hostname values are incorrect or 
	the database server is not running.</i><br /><br /><span class="error">
	<b>MySQL ERROR:</b> '. mysql_error().'</span>
	<br />&nbsp;
	</div>
</div>
</div>
</div>
</div>

</body>
</html>
