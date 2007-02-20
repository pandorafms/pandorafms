<?php
// Pandora FMS - the Free monitoring system
// ========================================
// Copyright (c) 2004-2007 Sancho Lerena, slerena@openideas.info
// Copyright (c) 2005-2007 Artica Soluciones Tecnologicas
// Copyright (c) 2004-2007 Raul Mateos Martin, raulofpandora@gmail.com
// Copyright (c) 2006-2007 Jose Navarro jose@jnavarro.net
// Copyright (c) 2006-2007 Jonathan Barajas, jonathan.barajas[AT]gmail[DOT]com

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
include "include/config.php";
if (file_exists("include/languages/language_".$language_code.".php")) {
	 include "include/languages/language_".$language_code.".php";
} else {
	include "include/languages/language_en.php";
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Pandora FMS - <?php echo $lang_label["header_title"]; ?></title>
<meta http-equiv="expires" content="0">
<meta http-equiv="content-type" content="text/html; charset=ISO-8859-15">
<meta name="resource-type" content="document">
<meta name="distribution" content="global">
<meta name="author" content="Sancho Lerena, Raul Mateos">
<meta name="copyright" content="This is GPL software. Created by Sancho Lerena and others">
<meta name="keywords" content="pandora, monitoring, system, GPL, software">
<meta name="robots" content="index, follow">
<link rel="icon" href="images/pandora.ico" type="image/ico">
<link rel="stylesheet" href="include/styles/pandora.css" type="text/css">
</head>
<body background='images/backgrounds/<?php echo $config_bgimage; ?>'>
<div align='center'>
<div id='login_f'>
	<h1 id="log_f" class="error">Installer active</h1>
	<div>
		<img src="images/logo_login.gif" border="0"></a><br><font size="1">
		<?php echo $pandora_version; ?>
		</font>
	</div>
	<div class="msg"><br><br>For security reasons, normal operation is not possible until you delete installer file. Please delete <b>./install.php</b> file before running Pandora FMS Console.</div>
</div>
</div>
</body>
</html>