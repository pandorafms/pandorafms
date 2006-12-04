<?php

// Pandora - the Free monitoring system
// ====================================
// Copyright (c) 2004-2006 Sancho Lerena, slerena@gmail.com
// Copyright (c) 2005-2006 Artica Soluciones Tecnológicas S.L, info@artica.es
// Copyright (c) 2004-2006 Raul Mateos Martin, raulofpandora@gmail.com
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.


?>
<html>
<head>
<title>Pandora - The Free Monitoring System Help - VII. Server Configuration</title>
<link rel="stylesheet" href="../../include/styles/pandora.css" type="text/css">
<style>
div.logo {float:left;}
div.toc {padding-left: 200px;}
div.rayah {clear:both; border-top: 1px solid #708090; width: 100%;}
</style>
</head>

<body>
<div class='logo'>
<img src="../../images/logo_menu.gif" alt='logo'><h1>Pandora Help v1.2</h1>
</div>
<div class="toc">
<h1><a href="chap6.php">6. System audit</a> « <a href="toc.php">Table of Contents</a> » <a href="chap8.php">8. Database maintenance</a></h1>

</div>
<div class="rayah">
<p align='right'>Pandora is a GPL Software Project. &copy; Sancho Lerena 2003-2006, David villanueva 2004-2005, Alex Arnal 2005, Ra&uacute;l Mateos 2004-2006.</p>
</div>

<a name="7"><h1>7. Pandora Servers</h1></a>

<p>In Pandora 1.2 there are three different type of servers, Network Server,
Data Server and SNMP Server.</p>

<p>It is possible to manage the Pandora Servers from "Pandora Servers" option 
in the Operation menu.</p>

<p class="center"><img src="images/servers1.png"></p>
<p>The following fields are displayed:</p>
  <ul>
  <li>
   <b>Name:</b> Name of the server.
  </li>
  <li>
    <b>Status:</b> Status of the server. Green OK and Red FAIL.
  </li>
  <li>
    <b>IP address:</b> IP of the Server.
  </li>
  <li>
    <b>Description:</b> Server description.
  </li>
  <li>
    <b>Network:</b> Mark for Network Server.
  </li>
  <li>
    <b>Data:</b> Mark for Data Server.
  </li>
  <li>
    <b>SNMP:</b> Mark for SNMP Server.
  </li>
  <li>
    <b>Master:</b> Marked when the server is Master and not 
    marked when the master is backup.
  </li>
  <li>
    <b>Check:</b>
  </li>
  <li>
    <b>Started at:</b> Date when the Server started.
  </li>
  <li>
    <b>Updated at:</b> The date of the last update.
  </li>
  <li>
     <b>Action:</b> Icons to modify server properties or
     to delete a server (only in Administration menu).
  </li>
  </ul>
  <p>From "Manage Servers", Administration menu you can configure and manage
  servers.</p>
  <p>
   It is possible delete a server using the icon
   <img src="../../images/cancel.gif">
   </p>
   <p>
    It is possible to change the server properties using the icon 
	<img src="../../images/config.gif"> 
   </p>
   <p>
    In a Server it is possible to modify: Name, IP Address and Description.
   </p>
<p class="center"><img src="images/servers2.png"></p>
</body>
</html>