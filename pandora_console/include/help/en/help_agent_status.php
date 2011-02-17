<?php
/**
 * @package Include/help/en
 */
?>
<h1>Agent status view </h1>

Possible color values of <b>modules</b> are:
<br><br>
<b>
Number of modules
: <span class="red">Number of critical modules</span>
: <span class="yellow">Number of warning modules</span>
: <span class="green">Number of normal modules</span>
: <span class="grey">Number of down modules</span>
</b>
<br><br>
Possible values of an <b>agent status</b> are:

<br><br>

<table width="750px">
<tr>
	<td class="f9i"><?php print_image("images/status_sets/default/module_critical.png", false, array("title" => "At least one monitor fails", "alt" => "At least one monitor fails")); ?><?php print_image("images/status_sets/faces/module_critical.png", false, array("title" => "At least one monitor fails", "alt" => "At least one monitor fails")); ?></td><td>At least one monitor fails</td>
	<td class="f9i"><?php print_image("images/status_sets/default/module_warning.png", false, array("title" => "Change between Green/Red state", "alt" => "Change between Green/Red state")); ?><?php print_image("images/status_sets/faces/module_warning.png", false, array("title" => "Change between Green/Red state", "alt" => "Change between Green/Red state")); ?></td><td>Change between Green/Red state</td>
	<td class="f9i"><?php print_image("images/status_sets/default/agent_ok.png", false, array("title" => "All Monitors OK", "alt" => "All Monitors OK")); ?><?php print_image("images/status_sets/faces/agent_ok.png", false, array("title" => "All Monitors OK", "alt" => "All Monitors OK")); ?></td><td>All Monitors OK</td>

</tr><tr>
	<td class="f9i"><?php print_image("images/status_sets/default/agent_no_data.png", false, array("title" => "Agent without data", "alt" => "Agent without data")); ?><?php print_image("images/status_sets/faces/agent_no_data.png", false, array("title" => "Agent without data", "alt" => "Agent without data")); ?></td><td>Agent without data</td>
	<td class="f9i"><?php print_image("images/status_sets/default/agent_down.png", false, array("title" => "Agent down", "alt" => "Agent down")); ?><?php print_image("images/status_sets/faces/agent_down.png", false, array("title" => "Agent down", "alt" => "Agent down")); ?></td><td>Agent down</td>
</tr>
</table>

<br><br>
Possible values of <b>alert status</b> are:

<br><br>
<table width="450px">
<tr>
	<td class="f9i"><?php print_image("images/status_sets/default/alert_fired.png", false, array("title" => "Alert fired", "alt" => "Alert fired")); ?><?php print_image("images/status_sets/faces/alert_fired.png", false, array("title" => "Alert fired", "alt" => "Alert fired")); ?></td><td>Alert fired</td>
	<td class="f9i"><?php print_image("images/status_sets/default/alert_disabled.png", false, array("title" => "Alert disabled", "alt" => "Alert disabled")); ?><?php print_image("images/status_sets/faces/alert_disabled.png", false, array("title" => "Alert disabled", "alt" => "Alert disabled")); ?></td><td>Alerts disabled</td>
	<td class="f9i"><?php print_image("images/status_sets/default/alert_not_fired.png", false, array("title" => "Alert not fired", "alt" => "Alert not fired")); ?><?php print_image("images/status_sets/faces/alert_not_fired.png", false, array("title" => "Alert not fired", "alt" => "Alert not fired")); ?></td><td>Alert not fired</td>

</tr>
</table>
