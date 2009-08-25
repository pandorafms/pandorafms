<?php
/**
 * @package Include/help/en
 */
?>
<h1>Alerts</h1>

Assigning Alerts to modules
Adding new alert to a module
Editar an alert from a module
<br /><br />
The next step after adding an agent, having configured its modules, and defined the alerts, is assigning those alerts to the agent. This step is necessary to establish alert conditions in those desired cases. This is done by clicking on the agent to be conﬁgured in the "Manage agents" option, from Administration menu, or using the edition mode and selecting the tab "Alerts", from the agent view.
<br /><br />
The next fields must be filled to assign an alert:
<ul>
	<li>Alert type: This can be selected from the alert list previously generated.</li>
	<li>Max. Value: Deﬁnes the maximum value for a module. Any value above that threshold will trigger the alert.</li>
	<li>Min. Value: Deﬁnes the minimum value for a module. Any value below that will trigger the alert. "max." & "min." couple are the key values while defining an alert, since they define the range of normal values, out of that range Pandora FMS will trigger the alert.</li>
	<li>Alert text: In case of string modules, you can define a regular expression or a single string to match contents of data module to trigger the alert.</li>
	<li>Time from / Time to: This defines a range of "valid" time range to fire alerts.</li>
	<li>Description: Describes the function of the alert, and it is useful to identify the alert among the others in the general view of alerts.</li>
	<li>Field #1 (Alias, name): Deﬁne the used value for the "_ﬁeld1_" variable.</li>
	<li>Field #2 (Single Line): Deﬁne the used value for the "_ﬁeld2_" variable.</li>
	<li>Field #3 (Full Text): Deﬁne the used value for the "_ﬁeld3_" variable.</li>
	<li>Time threshold: Time counter since the first alarm was triggered (or condition to trigger it) . During that time, the alerts are handled with the rest of the parameters (Min. number of alerts, Max. number of alerts). You can choose between the interval conﬁgured or deﬁne other interval.</li>
	<li>Min. number of alerts: Minimum number of alerts needed to start triggering an alert. Works as a filter, needed to remove false positives.</li>
	<li>Max. number of alerts: Maximum number of alerts that can be sent consecutively during the same time threshold.</li>
	<li>Assigned module: Module to be monitored by the alert.</li>
</ul>
All the alerts of an agent can be seen using the "Alerts" tab. Let's see an example:
<br /><br />
"I want to ﬁre an alert when XXX goes down, and please, dont't disturb me again at least for one hour. After that time, if it is still down, ﬁre another alert and wait another hour".
<br /><br />
You need to setup:
<ul>
	<li>Time threshold 3600 (1 hour).</li>
	<li>Min. number of alerts = 1.</li>
	<li>Max. number of alerts = 1.</li>
</ul>
