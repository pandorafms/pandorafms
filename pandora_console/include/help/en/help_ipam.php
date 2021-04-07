<?php
/**
 * @package Include/help/en
 */
?>
<h1>IP Address Management (IPAM)</h1>
<br>
Using IPAM extension, we can manage, discover and get event on changes on hosts in a given network. We can know if a given IP address (IPv4 or IPv6) change it's availability (answer to a ping) or hostname (using dns resolution). We also can detect its OS and link a IP address to a current <?php echo get_product_name(); ?>  agent, adding the IP address to their currently assigned addresses. IPAM extension uses the recon server and a recon script on the low level, but you don't need to configure nothing, IPAM extension do everything for you.
<br><br>
IP Management works in parallel to the monitoring you currently manage with <?php echo get_product_name(); ?>  agents, you can associate a IP address managed with IPAM extension or not, it depends on you. Managed IP addresses can optionally generate event on change.

<h2>IPs Detection</h2>
We can setup a network (using a bit mask or a prefix), and this network will be automatically sweeped or setup to have a on-request manual execution. This will execute a recon script task, searching for active IP (using nmap for IPv4 and ping for IPv6). You see the progress on network sweep in the status view and also in the recon server view.
<br><br>

<h2>Views</h2>
Network IP addresses administration and operation are splitted in two views: icon views and edition view.

<h3>Icon view</h3>
This view reports information on the network, including stats on the percentage and number of occupied IP addresses (only for 'managed' addresses). We can also export to Excel/CSV the filtered list.<br><br>
Addresses will be shown as icons, large or small. This icons will render the following information:<br><br>
<table width=100%>
<tr>
<th colspan=3>Managed</th>
</tr>
<tr>
<th>Setup</th>
<th>Alive host</th>
<th>Unresponsive host</th>
</tr>
<tr>
<td>No assigned agent<br><br>Disabled events</td>
<td class="center"><img src="../enterprise/images/ipam/green_host.png"></td>
<td class="center"><img src="../enterprise/images/ipam/red_host.png"></td>
</tr>
<tr>
<td>With assigned agent<br><br>Disabled events</td>
<td class="center"><img src="../enterprise/images/ipam/green_host_agent.png"></td>
<td class="center"><img src="../enterprise/images/ipam/red_host_agent.png"></td>
</tr>
<tr>
<td>No assigned agent<br><br>Activated events</td>
<td class="center"><img src="../enterprise/images/ipam/green_host_alert.png"></td>
<td class="center"><img src="../enterprise/images/ipam/red_host_alert.png"></td>
</tr>
<tr>
<td>With assigned agent<br><br>Activated events</td>
<td class="center"><img src="../enterprise/images/ipam/green_host_agent_alert.png"></td>
<td class="center"><img src="../enterprise/images/ipam/red_host_agent_alert.png"></td>
</tr>
<tr>
<th colspan=3>Not managed</th>
</tr>
<tr>
<th>Setup</th>
<th>Alive host</th>
<th>Unresponsive host</th>
</tr>
<tr>
<td class="w100px">If an IP address is not managed, you can only view if is responding or not.</td>
<td class="center"><img src="../enterprise/images/ipam/green_host_dotted.png"></td>
<td class="center"><img src="../enterprise/images/ipam/not_host.png"></td>
</tr>
<tr>
<th colspan=3>Not assigned</th>
</tr>
<tr>
<td colspan=3>The icon have a soft blue color when is unassigned.</td>
</tr>
</table>
<br><br>
Each IP address have in the bottom right position a link to edit it (with adminitration rights). In the bottom left position, there is a small icon showing the OS detected. On disabled addresses, instead the OS icon, you will see this icon:<br><br><img src="../images/delete.png" class="w18px"><br><br>

When you click on the main icon, a modal window will be opened showing all the IP information, including associated agent and OS, setup for that IP and other information, like creation date, last user edition or last time it was checked by server. In this view you can also do a manual, realtime check to see if that IP respond to ping. Note that this ping is done by the console, instead the regular check, done by the recon server.

<h3>Edition view</h3>
If you have enought permission, you will have access to setup view, where IP address are shown as a list. You can filter to show only the IP's you are interested into, make changes and update all at once.<br><br>

Some fields, are automatically filled by the recon script, like hostname, if it have a <?php echo get_product_name(); ?>  agent and the operating system. You can mark that fields as "manual" and edit them.<br><br>

<table width=100%>
<tr>
<th colspan=2>Changing between manual and automated</th>
</tr>
<tr>
<td class="center w25px"><img src="../images/manual.png"></td>
<td><b>Manual mode</b>: With this symbol, the field will not be updated by the recon system and you can edit manually. By clicking on it, you will switch to automated mode.
</td>
</tr>
<tr>
<td class="center w25px"><img src="../images/automatic.png"></td>
<td><b>Automated mode</b>:With this icon, the field will be updated automatically from the recon script. By clicking on it, it will switch to manual mode..</td>
</tr>
</table>
<br><br>
<b>*Manua marked as "manual" will not be updated by the recon script.</b><br><br>

Other fields you can modify are:
<ul>
<li>- Activate events on an IP address. When availability on this address change (answer or stop to answer) or the hostname change, a new event will be generated. <br><br>
<b>When an address is created, it always will generate an event.</b><br><br></li>
<li>- Mark as <i>managed</i> an IP Address. This address are those we will acknowledge as assigned in our network and managed in the system. You can filter to show only managed addresses.<br><br></li>
<li>- Disable. Disabled IP addresses are not checked by the recon script.<br><br></li>
<li>- Comments. A free field to add comments on each address.</li>
</ul>

<h2>Filters</h2>
On both views you can sort by IP, Hostname and by the last update.<br><br>
You can filter by a text substring, which will match in IP, hostname or comments of each IP in the system. Enabling the checkbox near to search box, it will force an exact match by IP.<br><br>

By default, not responding hosts are not shown, but you can change the filter.<br><br>
You can also show only the managed IP addresses.

<h2>Subnetwork calculator</h2>

IPAM includes a tool to calculate IPv4 and IPv6 subnetworks.<br><br>
In this tool, you can, using an IP address and a netmask, obtain the information of that network: network address, broadcast address, the first and the last valid IP in that subnet and the total number of hosts, between other information. You can also show the binary representation to understand better the subnetting mask.
