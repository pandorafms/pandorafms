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
<title>Pandora - The Free Monitoring System Help - III. Agents</title>
<link rel="stylesheet" type="text/css" href="../../include/styles/pandora.css">
<style>
.ml15 {margin-left: 15px;}
.ml25 {margin-left: 25px;}
.ml35 {margin-left: 35px;}
.ml75 {margin-left: 75px;}
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
<h1><a href="chap2.php">2. Users</a> « <a href="toc.php">Table of Contents</a> » <a href="chap4.php">4. Incident Management</a></h1>

</div>
<div class="rayah">
<p align='right'>Pandora is a GPL Software Project. &copy; Sancho Lerena 2003-2006, David villanueva 2004-2005, Alex Arnal 2005, Ra&uacute;l Mateos 2004-2006.</p>
</div>

<a name="3"><h1>3. Agents</h1></a>

<p>The agents collect information. The public key
of the machine to be monitored needs to be copied onto Pandora and the agent
executed. Pandora's server starts now receiving and processing the data
collected by the agent. The data collected from the agents are called "modules".</p>

<p>The value of each module it is the value of one
monitored variable. The agent must be activated in Pandora's server and a group
assigned to the agent. The data starts then been consolidated in the database
and can be accessed.</p>

<p>The user can:</p>

<ul>
<li>View the agent status</li>
<li>Access to the collected information</li>
<li>Access the monitored values and its evolution in time</li>
<li>View graphic reports</li>
<li>Configure Alerts</li>
</ul>

<h2><a name="31">3.1. Group Manager</a></h2>

<p>Groups are added in "Manage Profiles" &gt; "Manage Groups", Administration menu.</p>

<p class="center"><img src="images/image007.png"></p>

<p>There are nine default groups on this screen.</p>

<ul>
<li><b>Applications</b></li>
<li><b>Comms</b></li>
<li><b>Databases</b></li>
<li><b>Firewall</b></li>
<li><b>IDS</b></li>
<li><b>Others</b></li>
<li><b>Servers</b></li>
<li><b>Workstations</b></li>
</ul>

<p>
A group is added by clicking "Create group" and assigning a name to it.</p>
<p>

A group is deleted by clicking the delete icon <img src="../../images/cancel.gif"> in the right hand side of each group.</p>

<h2><a name="32">3.2. Adding an agent</a></h2>

<p>Before an agent is added, the public key of the
machine to be monitored needs to be copied. The agent is then executed, and
added through the web console. The data starts now being consolidated in the
Database and can be accessed.</p>

<p>An agent is added in "Manage Agents" &gt; "Create agent" in the Administration menu.</p>

<p class="center"><img src="images/image008.png"></p>

<p>To add a new agent the following parameters must be configured:</p>

<ul>
<li><b>Agent Name:</b> Name of the agent. This and the "agent name" parameter in Pandora's agent.conf file <b>must have the same value</b>. If this variable is commented out in the code, the name used will be the name of the Host (to obtain this, execute the <i>hostname</i> command).</li>
<li><b>IP Address:</b> IP address of an agent. An agent can share its IP address with other agents.</li>
<li><b>Group:</b> Pandora's group the agent belongs.</li>
<li><b>Interval:</b> Execution interval of an agent. It is the time elapsed between two executions.</li>
<li><b>OS:</b> The Operating System to be monitored. The supported Operating Systems are: AIX, BeOS,
BSD, Cisco, HPUX, Linux, MacOS, Other, Solaris, Windows.</li>
<li><b>Description:</b> Brief description of an agent.</li>
<li><b>Module definition:</b> There are two modes for a module:</li>
<p class='ml15'>- <i><b>Learning mode:</b></i> All the modules sent by the agent are accepted. They are automatically defined by the system. It is recommended to activate the agents in this mode and change it once the user is familiar with the system.</p>
<p class='ml15'>- <i><b>Normal mode:</b></i> The modules in this mode must be configured manually. The self – definition of the modules is not allowed in this mode.</p>
<li><b>Disabled:</b> This parameter shows if the agent is activated and
ready to send data or deactivated. The deactivated agents don't appear in the
user views.</li>
</ul>

<h3><a name="321">3.2.1. Assigning modules</a></h3>

<p>Pandora's agents use the operating system own commands to monitor a device. Pandora's server will store and process the output generated by those commands. The commandos are called "modules".</p>

<p>If the agent had been added in "normal mode", the modules to be monitored should have been assigned. Those modules must be configured in the agent configuration file.</p>

<p>The modules to be processed by Pandora's server are assigned in the "Manage Agents" option, Administration menu. A list with all the agents in Pandora will be shown here.</p>

<p>You'll get a form with all the agent's settings when the agent name is clicked. In the same screen there is a section to assign modules.</p>

<p class="center"><img src="images/image009.png"></p>

<p>The following fields must be filled to create a module:</p>

<ul>
<li><b>Module type:</b> This is the type of data the module will process. There are five types of data:</li>
<p class='ml15'>
- <b><code>generic_data</code></b>, Integer data type<br>
- <b><code>generic_data_inc</code></b>, Incremental integer data type<br>
- <b><code>generic_data_proc</code></b>, Boolean data type: 0 False, &gt;0 True.<br>
- <b><code>generic_data_string</code></b>, Alphanumeric data type (text string, max. 255 characters).
</p>
<li><b>Module name:</b> The name of the module</li>
<li><b>Maximum:</b> Upper threshold for the value in the module. Any value above this threshold will be taken as invalid and the whole module will be discarded.</li>
<li><b>Maximum:</b> Lower threshold for the value in the module. Any value below this threshold will be taken as invalid and the whole module will be discarded.</li>
<li><b>Comments:</b> Comments added to the module.</li>
</ul>

<p>All the modules to be monitored by an agent can be reviewed by accessing the agent in the "Manage Agents" option, Administration menu.</p>

<p class="center"><img src="images/image010.png"></p>

<p>In this screen the modules can be:</p>
<ul>
<li>Deleted by clicking <img src="../../images/cancel.gif"></li>
<li>Edited by clicking <img src="../../images/config.gif"></li>
</ul>

<p>However, the type of data of the module can't be modified.</p>

<h3><a name="322">3.2.2. Alerts</a></h3>

<p>An alert is Pandora's reaction to an out of range module value. The Alert can
consist in sending and e-mail or SMS to the administrator, sending a SNMP trap,
write the incident into the system syslog or Pandora log file, etc. And
basically anything that can be triggered by a script configured in Pandora's
Operating System.</p>

<h4><a name="3221">3.2.2.1. Adding an Alert</a></h4>

<p>The existing Alerts are accessed by clicking on the "Manage Alerts" option, Administration menu.</p>
<p>There are 6 default types of Alerts:</p>

<ul>
<li><b>eMail</b>. Sends an e-mail from Pandora's Server</li>
<li><b>Internal audit</b>. Writes the incident in Pandora's internal audit system</li>
<li><b>LogFile</b>. Writes the incident in the log file</li>
<li><b>SMS Text</b>. Sends an SMS to a given mobile phone</li>
<li><b>SNMP Trap</b>. Sends a SNMP Trap</li>
<li><b>Syslog</b>. Sends an alert to the Syslog</p>
</ul>

<p class="center"><img src="images/image011.png"></p>

<p>An Alert is deleted by clicking on the delete icon <img src="../../images/cancel.gif"> placed on the right hand side of the Alert. A new customised Alert can be created clicking in "Create Alert".</p>

<p>The values "<code>_field1_</code>", "<code>_field2_</code>" and "<code>_field3_</code>" in the customised Alerts are used to build the command line that the machine where Pandora resides will execute – if there were several servers, the one in Master mode.</p>

<p class="center"><img src="images/image012.png"></p>

<p>When a new Alert is created the following field must be filled in:</p>
<ul>
<li><b>Alert name:</b> The name of the Alert</li>
<li><b>Command:</b> Command the Alert will trigger</li>
<li><b>Description:</b> Description of the Alert</li>
</ul>

<p>In 'Command' data field these variables are used to build the command line that the machine where Pandora resides will execute – if there were several servers, the one in Master mode, replacing at runtime:</p>

<ul>
<li><code><b>_field1_</b></code>: Field #1, usually assigned as username, e-mail destination or single identification for this event</li>
<li><code><b>_field2_</b></code>: Field #2, usually assigned as short description of events, as subject line in e-mail</li>
<li><code><b>_field3_</b></code>: Field #3, a full text explanation for the event</li>
<li><code><b>_agent_</b></code>: Agent name</li>
<li><code><b>_timestamp_</b></code>: A standard representation of date and time. Replaced automatically when the event has been fired</li>
<li><code><b>_data_</b></code>: The data value that triggered the alert</li>
</ul>

<h4><a name="3222">3.2.2.2. Assigning Alerts</a></h4>

<p>The next step after an Agent has been added, its modules have been configurated and the alerts have been defined, it is time to assign those Alerts to the agent.</p>

<p>This is done by clicking on the Agent to be configured on the "Manage Agents" option, Administration menu. The Alert Assignation form is placed at the bottom of that page.</p>

<p><img src="images/image013.png"></p>

<p>To assign an Alert the next fields must be filled in:</p>

<ul>
<li><b>Alert type:</b> This can be selected from the list of alerts that have been previously generated.</li>
<li><b>Maximum Value:</b> Defines the maximum value for a module. Any value above that threshold will trigger the Alert.</li>
<li><b>Minimum Value:</b> Defines the minimum value for a module. Any value below that will trigger the Alert.</li>
<li><b>Description:</b> Describes the function of the Alert, and it is useful to identify the Alert amongst the others in the Alert General View.</li>
<li><b>Field #1 (Alias, name):</b> Define the used value for the "_field1_" variable.</li>
<li><b>Field #2 (Single Line):</b> Define the used value for the "_field2_" variable.</li>
<li><b>Field #3 (Full Text):</b> Define the used value for the "_field3_" variable.</li>
<li><b>Time threshold:</b> Minimum duration between the firing of two consecutive alerts, in seconds.</li>
<li><b>Max Alerts Fired:</b> Maximun number of alerts that can be sent consecutively.</li>
<li><b>Assigned module:</b> Module to be motitorized by the alert.</li>
</ul>

<p>All the alerts of an agent can be seen through "Manage Agents" in the Adminitration menu and selecting the agent.</p>

<h3><a name="323">3.2.3. Agent module and agent's alert management</a></h3>

<p>It might happen that the user finds that modules and alerts configured for an agent would be repeated in a new agent.</p>

<p>In order to simplify the administrator's job Pandora offers the option of copying modules and alerts defined in an agent to be assigned to another.</p>

<p>The screen is accessed through "Manage Agents"&gt;"Manage Config.", in the Administration menu:</p>

<p class="center"><img src="images/image014.png"></p>

<p>The Source Agent menu permits the selection of the agent where the needed modules and/or alerts reside. The "Get Info" button shows the modules for that agent in the Modules list box.</p>

<p><b><i>The copy process</i></b> is performed to copy the module and/or alert configuration from the selected source agents to the selected destination agents. Several agents can be selected, pressing CTRL and the mouse right button simultaneously. The two tick boxes at the top of the form will be used to specify if the configuration
to copy is from modules and/or from alerts.</p>

<p><b><i>The delete process</i></b> is performed to delete the configuration of the destination agents, in the multiple selection list box. Several agents can be selected at a time, and the tick boxes at the top of the
form indicate whether it is the modules or the alerts configuration what is to
be deleted. The application will prompt to confirm the deletion, as once
deletion is performed, the data associated to them will also be deleted.</p>

<h3><a name="323">3.2.4. Agents group detail</a></h3>

<p>Once you have configured your groups and agents, you can see the status of the groups of agents through "View Agents", in the Operation Menu.</p>

<p>If you pass the mouse over any group image, you'll see the number of agents of that group as well the number of monitors, organized by status.</p>

<p>By pressing the icon <img src="../../images/target.gif"> at the right of any group image, you will update the info of that group.</p>

<h2><a name="33">3.3. Agent monitoring </a></h2>

<p>When the agents start the data transmission to the server, and it is added in the Web console, Pandora processes and inserts the data in the Database. The data are consolidated and can be accessed from the Web console, either as row data or as graphs.</p>

<h3><a name="331">3.3.1. Agent view</a></h3>

<p>All the Agents can be accessed from the Operation menu. From here the status of the agents can be quickly reviewed thanks to a simple system of bulbs and coloured circles.</p>

<p class="center"><img src="images/image015.png"></p>

<p>The list of agents shows all the relevant the information in the following columns:</p>

<p><b>Agent:</b> Shows the agent's name.</p>
<p><b>SO:</b> Displays an icon that represents the Operating System.</p>
<p><b>Interval:</b> Shows the time interval (seconds) in which the agent sends data to the server.</p>
<p><b>Group:</b> This is the group the agent belongs to.</p>
<p><b>Modules:</b> Under normal circumstances this field shows the values representing the
number of modules and the number of monitors, both in black. If the status of a
monitor changes to "incorrect", one additional number is shown: the number of
modules, the number of monitors and the number of monitors with "incorrect" status, all in black save the last one.</p>
<p><b>Status:</b> Shows the "general" status of the agent through the following icons:</p>
	<div class='ml35'>
		<p><img src="../../images/b_green.gif"> All the monitors OK. It's the ideal status.</p>
		<p><img src="../../images/b_white.gif"> No defined monitors. Sometimes nothing is monitored
		that could be right or wrong, and only numeric or text data is reported.</p>
		<p><img src="../../images/b_red.gif"> At least one of the monitors is failing. Usually we
		want to avoid this, and keep our systems in a healthy green colour.</p>
		<p><img src="../../images/b_blue.gif"> The agent doesn't have <u>any</u> data. New agents with an empty data
		package can have this status.</p>
		<p><img src="../../images/b_yellow.gif"> Colour shifting from green to red. This icon indicates
		that the agent has just changed its status, from 'All OK' to 'we have a problem'.</p>
		<img src="../../images/b_down.gif"> When an agent is down or there is no news from it for 2 times the Interval value in seconds. Usually it is due to a communication issue or a crashed remote system.</p>
	</div>
<p><b>Alerts:</b> Shows if any alerts have been sent through the following icons:</p>
	<div class='ml35'>
		<p><img src="../../images/dot_green.gif"> No alerts have been sent.</p>
		<p><img src="../../images/dot_red.gif"> When at least one alert has been sent within the time threshold of the alert.</p>
	</div>
<p><b>Last contact:</b> Shows the time and date of the last data package sent by the agent, using a progress bar, according to value of the interval. If you see the image <img src="../../images/outof.gif">, the agent has not send data during the interval. Passing the mouse over the image will show you the last contact in time and date format.</p>

<p><b><u>Note:</u></b> The icon <img src="../../images/setup.gif" width="15"> is only visible if you're and administrator and it's a link to the "Manage Agents" &gt; "Update Agent" option in the Administration menu.</p>
<h3><a name="332">3.3.2. Accessing the data of an agent</a></h3>

<p>When an agent is accessed, by clicking on its name, all the information related to that agent is displayed.</p>

<h4><a name="3321">3.3.2.1. Agent general info</a></h4>

<p>This shows the data introduced when the agent was created and the total number a data packages the agent has sent.</p>

<p class="center"><img src="images/image016.png"></p>

<h4><a name="3322">3.3.2.2. Last data received</a></h4>

<p>This is the description of all the agent modules been monitored.</p>

<p><img src="images/image017.png"></p>

<p>In this list the module information is shown in the following columns:</p>

<p><b>Module name:</b> Name given to the module in the agent's config file.</p>
<p><b>Module type:</b> Type of module as described in <a href="#321">section 3.2.1</a>.</p>
<p><b>Description:</b> Description given to the module in the agent's config file.</p>
<p><b>Data:</b> Last data sent by the agent.</p>
<p><b>Graph:</b> Monthly(M), Weekly(W), Daily(D) and Hourly(H) graphs are generated with
the data sent by the agent against time.</p>

<p>On the left hand side of the graph the newst data is represent, and on the right had side the oldest.</p>

<p>The generated graphs are:
<p class="ml75"> - <b>Hourly graph</b> (<img src="../../images/grafica_h.gif">) covers a 60 minute interval</p>
<p class="center"><img src="images/image018.png"></p>

<p class="ml75"> - <b>Daily graph</b> (<img src="../../images/grafica_d.gif">) covers a 24 hour interval</p>
<p class="center"><img src="images/image019.png"></p>

<p class="ml75"> - <b>Weekly graph</b> (<img src="../../images/grafica_w.gif">) covers a 7 day interval</p>
<p class="center"><img src="images/image020.png"></p>

<p class="ml75"> - <b>Mothly graph</b> (<img src="../../images/grafica_m.gif">) covers a 30 day interval</p>
<p class="center"><img src="images/image021.png"></p>

<p><b>Raw Data:</b> This is the raw data sent by the agent</p>

<p class="ml25"> - <img src="../../images/data_m.gif"> Last month</p>
<p class="ml25"> - <img src="../../images/data_w.gif"> Last week</p>
<p class="ml25"> - <img src="../../images/data_d.gif"> Last day</p>

<h4><a name="3323">3.3.2.3. Complete list of monitors</a></h4>

<p>This is the description of all the monitors defined by the agent</p>

<p class="center"><img src="images/image022.png"></p>

<p>The list shows the information about the monitors in the following columns:</p>

<p><b>Agent:</b> Agent where the monitor is defined.</p>
<p><b>Type:</b> Data type of the monitor. For a monitor this value is always of the generic_proc type.</p>
<p><b>Module name:</b> Name given to the module when it was created.</p>
<p><b>Description:</b> Description given to the module in the agent's config file.</p>
<p><b>Status:</b> The table shows the agent status through the following icons:</p>

<p class="ml25"><img src="../../images/b_green.gif"> The monitor is OK</p>
<p class="ml25"><img src="../../images/b_red.gif"> The monitor is failing</p>

<p><b>Last contact:</b> Shows the time and date of the last data packaged received from the agent</p>

<h4><a name="3324">3.3.2.4. Complete list of alerts</a></h4>

<p>This is the description of all the alarms defined in the agent</p>

<p class="center"><img src="images/image023.png"></p>

<p>The monitor information is shown in the list divided in the following fields:</p>

<p><b>ID:</b> Agent were the alert has been defined.</p>
<p><b>Type:</b> Type of alert.</p>
<p><b>Description:</b> Description given to the alert when it was created.</p>
<p><b>Last fired:</b> The last time the alert was executed.</p>
<p><b>Times Fired:</b> Number of times the alert was launched.</p>
<p><b>Status:</b> Shows if the alert has been sent through the following icon:</p>

<p class="ml25"><img src="../../images/dot_green.gif"> No alerts have been sent</p>
<p class="ml25"><img src="../../images/dot_red.gif"> At least one alert has been sent</p>

<h3><a name="333">3.3.3. Group details</a></h3>

<p>The groups configured in Pandora can be
accessed through "View Agents"&gt;"Group detail" in the Operation menu. The group
details can be reviewed quikly thanks to a system of coloured bulbs.</p>

<p class="center"><img src="images/image025.png"></p>

The groups are displayed ordered by the following columns:</p>

<p><b>Groups:</b> Name of the group</p>
<p><b>Agents:</b> Number of agents configured in the group.</p>
<p><b>Monitors:</b> Number of monitors configured in the group.</p>
<p><b>Status:</b> The status is described through the following icons:</p>

<p class="ml25"><img src="../../images/b_green.gif"> All monitors are OK.</p>
<p class="ml25"><img src="../../images/b_red.gif"> At least one monitor has failed.</p>
<p class="ml25"><img src="../../images/b_down.gif"> At least one monitor is down and there is no contact with it.</p>
<p class="ml25"><img src="../../images/b_white.gif"> This Agent doesn't have any monitor defined.</p>

<p><b>OK:</b> Number of monitors that are OK.</p>
<p><b>Failed:</b> Number of failing monitors.</p>
<p><b>Down:</b> Number of down monitors.</p>

<h3><a name="334">3.3.4. Monitors view</a></h3>

<p>The description of all the monitors defined in the server can be viewed from the "View Agents"&gt;"Monitor detail" option in the Operation menu.</p>

<p class="center"><img src="images/image026.png"></p>

<p>In this list all the monitors appear in a similar way as in the individual view, but now they are shown all together. This allows a deeper analisys of each monitor.</p>

<h3><a name="335">3.3.5. Alert details</a></h3>

<p>The description of all the alerts defined in the server can be viewed from the "View Agents"&gt;"Alert Details" option in Operation menu.</p>

<p class="center"><img src="images/image027.png"></p>

<p>In this list all the alerts appear in a similar way as in the individual view, but now they are shown all together. This allows a deeper analisys of each alert.</p>

<h3><a name="336">3.3.6. Data Export</a></h3>

<p>The Data Export tool can be found in the "View Agents"&gt;"Export data" option in the Operation Menu.</p>

<p>Three parameters need to be configured for exporting data: the agent where data resides, the modules to be exported and the date interval of the data to be exported:</p>

<p class="center"><img src="images/image028.png"></p>

<p>The fields in the results of Exporting data are:</p>

<p><b>Module:</b> Module name.</p>
<p><b>Data:</b> Data contained by the module.</p>
<p><b>Timestamp:</b> Date and time of the the package was sent by the agent.</p>

<p class="center"><img src="images/image029.png"></p>

<p>Selecting the CSV format for the output, a text
file with extension <b>.csv</b> is be created. The data is qualified by single quotes
and the fields separated by commas:</p>

<p class="center"><img src="images/image030.png"></p>

<h3><a name="337">3.3.7. Statistics</a></h3>

<p>Two kinds of graphical statistics are displayed from the "View Agents"&gt;"Statistics" option, in the Operation menu:</p>

<ul>
<li>A graph with the number of modules configurated for each agents,</li>
<li>A graph with number of packages sent by each Agent. A package is the number of
values from the modules the agent sends after each time interval.</li>
</ul>
<p class="center"><img src="images/image031.png"></p>
<p class="center"><img src="images/image032.png"></p>
<p class="center"><img src="images/image033.png"></p>

<h2><a name="34">3.4. SNMP Console</a></h2>

<h3><a name="341">3.4.1. SNMP Alerts</a></h3>
</body>
</html>