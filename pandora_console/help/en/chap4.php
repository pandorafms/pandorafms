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
<title>Pandora - The Free Monitoring System Help - IV. Incident Management</title>
<link rel="stylesheet" href="../../include/styles/pandora.css" type="text/css">
<style>
.ml25 {margin-left: 25px;}
div.logo {float:left;}
div.toc {padding-left: 200px;}
div.rayah {border-top: 1px solid #708090; width: 100%;}
div.rayah2 {clear:both; border-top: 1px solid #708090; width: 100%; padding-bottom: 35px;}
</style>
</head>

<body>
<div class='logo'>
<img src="../../images/logo_menu.gif" alt='logo'><h1>Pandora Help v1.2</h1>
</div>
<div class="toc">
<h1><a href="chap3.php">3. Agents</a> « <a href="toc.php">Table of Contents</a> » <a href="chap5.php">5. Events</a></h1>

</div>
<div class="rayah2"></div>

<a name="4"><h1>4. Incident management</h1></a>

<p>The system monitoring process needs to follow up the incidents arising in the system besides receiving and processing the data to be monitored in each time interval</p>

<p>Pandora uses a tool called Incident Manager for this task, where each user can open an incident, where a description of what happened in the network is shown. This can be completed with comments and files
when necessary.</p>

<p>This system is designed for group work. Different roles and workflow systems permit to move incidents from one group to another. The system allows different groups and different users to work on
the same incident, sharing information and files.</p>

<p>Clicking on "Manage Incidents", in the Operation menu, a list showing all the incidents is displayed, ordered by the date-time they were last updated. Filters can be applied to display only those
incidents the user is interested on.</p>

<p class="center"><img src="images/image034.png"></p>

<p>The filters that can be applied are:</p>

<ul>
<li><b>Incident status filter</b>. The user can display:
<p class="ml25">- All incidents</p>
<p class="ml25">- Active incidents</p>
<p class="ml25">- Closed incidents</p>
<p class="ml25">- Rejected incidents</p>
<p class="ml25">- Expired incidents</p>
</li>
<li>
<b>Property filter</b>. The incidents are shown by:
<p class="ml25">- All priorities</p>
<p class="ml25">- Informative priority</p>
<p class="ml25">- Low priority</p>
<p class="ml25">- Medium priority</p>
<p class="ml25">- High priority</p>
<p class="ml25">- Very high priority</p>
<p class="ml25">- Maintenance</p>
</li>
<li><b>Group filter</b>. It can be selected to display just the incidents of a given Pandora group.</li>
</ul>
<br>
<p>The incident list is displayed showing information in the following columns:</p>

<p><b>ID:</b> ID of the incident.</p>
<p><b>Status:</b> The incident status is represented by the following icons:</p>

<p class="ml25"><img src="../../images/dot_red.gif"> Active incident</p>
<p class="ml25"><img src="../../images/dot_yellow.gif"> Active incident with comments</p>
<p class="ml25"><img src="../../images/dot_blue.gif"> Rejected incident</p>
<p class="ml25"><img src="../../images/dot_green.gif"> Closed incident</p>
<p class="ml25"><img src="../../images/dot_white.gif"> Expired incident</p>

<p><b>Incident name:</b> Name given to the incident</p>
<p><b>Priority:</b> The incident assigned priority is represented by the following icons:</p>

<p class="ml25"><img src="../../images/dot_red.gif"><img src="../../images/dot_red.gif"><img src="../../images/dot_red.gif"> Very high priority</p>
<p class="ml25"><img src="../../images/dot_yellow.gif"><img src="../../images/dot_red.gif"><img src="../../images/dot_red.gif"> High priority</p>
<p class="ml25"><img src="../../images/dot_yellow.gif"><img src="../../images/dot_yellow.gif"><img src="../../images/dot_red.gif"> Medium priority</p>
<p class="ml25"><img src="../../images/dot_green.gif"><img src="../../images/dot_yellow.gif"><img src="../../images/dot_yellow.gif"> Low priority</p>
<p class="ml25"><img src="../../images/dot_green.gif"><img src="../../images/dot_green.gif"><img src="../../images/dot_yellow.gif"> Informative priority</p>
<p class="ml25"><img src="../../images/dot_green.gif"><img src="../../images/dot_green.gif"><img src="../../images/dot_green.gif"> Maintenance priority</p>

<p><b>Group:</b> The name of the group the incident has been assigned to. One incident can only belong to a single group.</p>
<p><b>Updated at:</b> This is the date/time the incident was updated for the last time.</p>
<p><b>Source:</b> The source of the incident. The source is selected from a list stored
in the data base. This list can only be modified by the database base
administrator.</p>
<p><b>Owner:</b> User to whom the incident has been assigned to. It doesn't coinced
with the creator of the incident, as the incident may have been moved from one
user to another. The incident can be assigned to another user by its owner, or
by a user with management privileges over the group the incidents belong to.</p>

<h2><a name="41">4.1. Adding an incident</a></h2>

<p>The creation of incidents is performed by clicking on "Manage Incidents" &gt; "New incident", in the Operation menu</p>

<p class="center"><img src="images/image035.png"></p>

<p>The "Create Incident" form will come up, containing the necessary fields to define the incident. The process is completed by clicking on the 'Create' button.</p>

<h2><a name="42">4.2. Incident follow up</a></h2>

<p>All the open incidents can be followed up. The tool is reached by clicking on the "Manage Incidents" option, in the Operation menu.</p>

<p>The indicent is selected by clicking on its name in the "Incident name" column.</p>

<p>The screen coming up shows us the configuration variables of the incident, its comments and attached files.</p>

<p>The first part of the screen contains the Incident configuration.</p>

<p class="center"><img src="images/image036.png"></p>

<p>From this form the following values can be updated:</p>
<ul>
<li><b>Incident name</b></li>
<li><b>Incident owner</b></li>
<li><b>Incident status</b></li>
<li><b>Incident source</b></li>
<li><b>Group the indicent will belong to</b></li>
<li><b>Indicent priority</b></li>
</ul>
<p>The indicent is updated by clicking on the "Update incident" button.</p>

<h3><a name="421">4.2.1. Adding comments to an incident</a></h3>

<p> Comments about the incident can added clicking on "Add note". This will open up a screen with a text box in it.</p>

<p class="center"><img src="images/image037.png"></p>

<p>The comment is written in this box. The Comment will appear in the "Notes attached to incident" section after the button "Add" is pressed.</p>

<p class="center"><img src="images/image038.png"></p>

<p>Only users with writting privilieges can add a comment, and only the owners of the incident or of the notes can delete them.</p>

<h3><a name="422">4.2.2. Attaching files to an incident</a></h3>

<p>Sometimes it is necessary to link an incident with an image, a configuration file, or any kind of file.</p>

<p>The files are attached in the "Attach file" section. Here the file can be searched for in the local machine and attached when the "Upload" button is pressed.</p>

<p>Only a user with writing privileges can attach a file, and only the owner of the incident or of the file can delete it.</p>

<p class="center"><img src="images/image039.png"></p>

<p>The incident follow up screen shows all the files attached to the incident in the "Attached files" section of the screen.</p>

<p class="center"><img src="images/image040.png"></p>

<h2><a name="43">4.3. Searching for an incident</a></h2>

<p>A specific incident can be found amongst the
incidents created in Pandora by either using a filter – as explained in the
first section of this chapter - or by making a query using the "Manage Incidents"&gt;"Searh Incident" tool, in the Operation menu.</p>

<p class="center"><img src="images/image041.png"></p>

<p>Any text string included as a sub-string in the
incident can be searched for using this tool. This search engine looks for the
string in the Incident title as well as in the text contained by the incident.
The search engine will not search neither the Comments added to the agent nor
the attached files. The search can be performed in addition to group, priority
or status filters.</p>

<h2><a name="44">4.4. Statistics</a></h2>

<p>The incident statisticts are shown in the "Manage Incidents"&gt;"Statistics" option of the Operation menu. They can be of five different types:</p>

<ul>
<li><b>Incident status</b></li>
<li><b>Incident priority</b></li>
<li><b>Users with the incident opened</b></li>
<li><b>Incidents by group</b></li>
<li><b>Incident source</b></li>
</ul>

<p class="center">
<img src="images/image042.png"><br>
<img src="images/image043.png"><br>
<img src="images/image044.png"><br>
</p>

<div class="rayah">
<p align='right'>Pandora FMS is Free Software Project, licensed under GPL terms.<br> &copy; Sancho Lerena 2003-2006, David villanueva 2004-2006, Alex Arnal 2005, Ra&uacute;l Mateos 2004-2006.</p>
</div>
</body>
</html>