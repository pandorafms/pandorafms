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
<title>Pandora - The Free Monitoring System Help - V. Events</title>
<link rel="stylesheet" href="../../include/styles/pandora.css" type="text/css">
<style>
.ml25 {margin-left: 25px;}
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
<h1><a href="chap4.php">4. Incident Management</a> « <a href="toc.php">Table of Contents</a> » <a href="chap6.php">6. System Audit</a></h1>

</div>
<div class="rayah">
<p align='right'>Pandora is a GPL Software Project. &copy; Sancho Lerena 2003-2005, David villanueva 2004-2005, Alex Arnal 2005, Ra&uacute;l Mateos 2004-2005.</p>
</div>

<a name="5"><h1>5. Events</h1></a>

<p>An event in Pandora is any unusual change happend in an agent.</p>

<p>An event is registered when an agent is down or starts up, when a monitor fails or changes its status, or when an alarm is sent.</p>

<p>An event is usually preceded by an issue with
the system being monitored. A validation and deletion system has been created
to avoid leaving unanalised issues, so they can be easily validated or deleted
if the problem can be ignored or it's been already solved.</p>

<p>The events appear ordered chronologically as they enter the system, and can be viewed by clicking the "View Events" option in the Operation menu. The newer events are placed at the top of the table.</p>

<p class="center"><img src="images/image045.png"></p>
<br>
<p>The event information list shows the data in the following columns:</p>

<p><b>Status:</b> The event status is represented by the icon bellow:</p>
<p class="ml25"><img src="../../images/dot_green.gif"> The event has been validated</p>
<p class="ml25"><img src="../../images/dot_red.gif"> The event hasn't been validated</p>
<p><b>Event name:</b> Name assigned to the event by Pandora.</p>
<p><b>Agent name:</b> Agent where the event happend.</p>
<p><b>Group name:</b> Group of the agent where the event has happened.</p>
<p><b>User ID: </b>User that validated the event.</p>
<p><b>Timestamp:</b> Date and time when the event was raised or validated- if it has been validated.</p>
<p><b>Action:</b> Action that can be executed over the event.</p>
<p class="ml25"><img src="../../images/ok.gif"> This icon will validate the event, disappearing the icon</p>
<p class="ml25"><img src="../../images/cancel.gif"> This icon will delete the event</p>

<p>The events can be also validated or deleted in groups by selecting the tick boxes
on the last column of the event, and pressing "Validate" or "Delete" at the
bottom of the list.</p>

<h2><a name="51">5.1. Statistics</a></h2>

<p>Three different kinds of graphical statistic representation can be choosen from the "View Events"&gt;"Statistics" option in the Operation menu:</p>

<ul>
<li>Total number of events divided by revised and not revised
<p class="center"><img src="images/image046.png"></p>
</li>
<li>Total events divided by the users who validated the events
<p class="center"><img src="images/image047.png"></p>
</li>
<li>Total events divided by the group the agent raising the event belongs to
<p class="center"><img src="images/image048.png"></p>
</li>
</ul>

</body>
</html>