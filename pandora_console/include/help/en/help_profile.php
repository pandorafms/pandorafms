<?php
/*
 * @package Include/help/en/
 */
?>

<h1>Profile</h1>

<p><?php echo get_product_name(); ?> is a Web management tool that allows multiple users to work with different permissions in multiple defined agent groups. The permissions an user can have are defined in profiles.</p>

<p>The following list defines what ACL control allows in each feature at the console:</p>

<table cellpadding=4 cellspacing=0 class='gb_f0'>
<tr><th class='bg_caca'>Feature<th class='bg_caca'>ACL Control 

<tr><td>View the agent's data (all tabs)<td>AR
<tr><td>Tactical View<td>AR
<tr><td>Group View<td>AR
<tr><td>Visual console editing<td>RW
<tr><td>Creating reports<td>RW
<tr><td>Creating user-defined graphs<td>RW
<tr><td>Viewing reports, visual maps and custom graphs<td>RR
<tr><td>Applying report templates<td>RR
<tr><td>Creating report templates<td>RM
<tr><td>Creating incidents<td>IW
<tr><td>Reading incidents<td>IR
<tr><td>Deleting incidents<td>IW
<tr><td>Becoming the owner of another user's incidents<td>IM
<tr><td>Deleting another user's incidents<td>IM
<tr><td>Viewing events<td>ER
<tr><td>Validating and commenting events<td>EW
<tr><td>Deleting events<td>EM
<tr><td>Executing responses<td>EW
<tr><td>Creating incidents from events (response)<td>EW&IW
<tr><td>Managing responses<td>PM
<tr><td>Managing filters<td>EW
<tr><td>Customizing event columns<td>PM
<tr><td>Changing owners / reopen event<td>EM
<tr><td>Viewing users<td>AR
<tr><td>SNMP Console viewing<td>AR
<tr><td>Validating traps<td>IW
<tr><td>Messages<td>IW
<tr><td>Cron jobs    <td>PM
<tr><td>Tree view    <td>AR
<tr><td>Update Manager (operation and administration)    <td>PM
<tr><td>Extension Module Group<td>AR
<tr><td>Agent Management<td>AW
<tr><td>Remote Agent Configuration Management <td>AW
<tr><td>Assigning alerts to agents<td>LW
<tr><td>Defining, altering and deleting alert templates, actions and commands<td>LM
<tr><td>Group Management<td>PM
<tr><td>Creating inventory modules<td>PM
<tr><td>Module Management (includes all suboptions)<td>PM
<tr><td>Bulk Management Operations    <td>AW
<tr><td>Creating agents<td>AW
<tr><td>Duplicating remote configurations<td>AW
<tr><td>Downtime Management<td>AW
<tr><td>Alert Management<td>LW
<tr><td>User Management<td>UM
<tr><td>SNMP Console Management (alerts and MIB loading)<td>PM
<tr><td>Profile Management<td>PM
<tr><td>Server Management<td>PM
<tr><td>System Audit<td>PM
<tr><td>Setup<td>PM
<tr><td>Database Maintenance<td>DM
<tr><td>Administrator Extension Menu<td>PM
<tr><td>Search Bar<td>AR
<tr><td>Policy Management<td>AW
<tr><td>Disabling agents / modules / alerts<td>AD
<tr><td>Alerts validation<td>LM&AR or AW&LW
<tr><td>Network-map view<td>MR
<tr><td>Network-map edition<td>MW
<tr><td>Deletion of owned network-map<td>MW
<tr><td>Deletion of any network-map<td>MM
<tr><td>Visual console view<td>VR
<tr><td>Visual console edition<td>VW
<tr><td>Deletion of owned visual console<td>VW
<tr><td>Deletion of any visual console<td>VM

</table>
