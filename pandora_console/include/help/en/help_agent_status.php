<?php
/**
 * @package Include/help/en
 */
?>
<h1>Agent’s status view </h1>

Possible value colors for <b>modules</b> are:
<br><br>
<b>
Total number of modules
: <span class="red">Number of modules in critical status </span>
: <span class="yellow">Number of modules in warning status </span>
: <span class="green">Number of modules in normal status </span>
: <span class="grey">Number of downed modules</span>
</b>
<br><br>
Possible values for an <b>agent’s status</b> are:

<br><br>

<table width="750px" class="inline_line">
<tr>
    <td class="f9i"><?php html_print_image('images/status_sets/default/module_critical.png', false, ['title' => 'At least one monitor fails', 'alt' => 'At least one monitor fails']); ?><?php html_print_image('images/status_sets/faces/module_critical.png', false, ['title' => 'At least one monitor fails', 'alt' => 'At least one monitor fails']); ?></td><td>At least one monitor fails</td>
    <td class="f9i"><?php html_print_image('images/status_sets/default/module_warning.png', false, ['title' => 'Change between Green/Red state', 'alt' => 'Change between Green/Red state']); ?><?php html_print_image('images/status_sets/faces/module_warning.png', false, ['title' => 'Change between Green/Red state', 'alt' => 'Change between Green/Red state']); ?></td><td>Change between Green/Red state</td>
    <td class="f9i"><?php html_print_image('images/status_sets/default/agent_ok.png', false, ['title' => 'All Monitors OK', 'alt' => 'All Monitors OK']); ?><?php html_print_image('images/status_sets/faces/agent_ok.png', false, ['title' => 'All Monitors OK', 'alt' => 'All Monitors OK']); ?></td><td>All Monitors OK</td>

</tr><tr>
    <td class="f9i"><?php html_print_image('images/status_sets/default/agent_no_data.png', false, ['title' => 'Agent without data', 'alt' => 'Agent without data']); ?><?php html_print_image('images/status_sets/faces/agent_no_data.png', false, ['title' => 'Agent without data', 'alt' => 'Agent without data']); ?></td><td>Agent without data</td>
    <td class="f9i"><?php html_print_image('images/status_sets/default/agent_down.png', false, ['title' => 'Agent down', 'alt' => 'Agent down']); ?><?php html_print_image('images/status_sets/faces/agent_down.png', false, ['title' => 'Agent down', 'alt' => 'Agent down']); ?></td><td>Agent down</td>
</tr>
</table>

<br><br>
Possible values for an <b>alert’s status</b> are:

<br><br>
<table width="450px">
<tr>
    <td class="f9i"><?php html_print_image('images/status_sets/default/alert_fired.png', false, ['title' => 'Alert fired', 'alt' => 'Alert fired']); ?><?php html_print_image('images/status_sets/faces/alert_fired.png', false, ['title' => 'Alert fired', 'alt' => 'Alert fired']); ?></td><td>Alert fired</td>
    <td class="f9i"><?php html_print_image('images/status_sets/default/alert_disabled.png', false, ['title' => 'Alert disabled', 'alt' => 'Alert disabled']); ?><?php html_print_image('images/status_sets/faces/alert_disabled.png', false, ['title' => 'Alert disabled', 'alt' => 'Alert disabled']); ?></td><td>Alerts disabled</td>
    <td class="f9i"><?php html_print_image('images/status_sets/default/alert_not_fired.png', false, ['title' => 'Alert not fired', 'alt' => 'Alert not fired']); ?><?php html_print_image('images/status_sets/faces/alert_not_fired.png', false, ['title' => 'Alert not fired', 'alt' => 'Alert not fired']); ?></td><td>Alert not fired</td>

</tr>
</table>
