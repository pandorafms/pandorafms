<?php
/**
 * @package Include/help/en
 */
?>
<h1>Alerts</h1>

<i>Assigning Alerts to modules</i><br>
<i>Adding new alert to a module</i><br>
<i>Editar an alert from a module</i><br>
<p>The next step after adding an agent, having configured its modules, and defined the alerts, is assigning those alerts to the agent. This step is necessary to establish alert conditions in those desired cases. This is done by clicking on the agent to be conﬁgured in the "Manage agents" option, from Administration menu, or using the edition mode and selecting the tab "Alerts", from the agent view.</p>

<p>The next fields must be filled to assign an alert:<br><br></p>

	<li><b>Alert type:</b> This can be selected from the alert list previously generated.</li>
	<li><b>Max. Value:</b> Deﬁnes the maximum value for a module. Any value above that threshold will trigger the alert.</li>
	<li><b>Min. Value:</b> Deﬁnes the minimum value for a module. Any value below that will trigger the alert. "max." &amp; "min." couple are the key values while defining an alert, since they define the range of normal values, out of that range Pandora FMS will trigger the alert.</li>
	<li><b>Alert text:</b> In case of string modules, you can define a regular expression or a single string to match contents of data module to trigger the alert.</li>
	<li><b>Time from / Time to:</b> This defines a range of "valid" time range to fire alerts.</li>
	<li><b>Description:</b> Describes the function of the alert, and it is useful to identify the alert among the others in the general view of alerts.</li>
	<li><b>Field #1 (Alias, name):</b> Deﬁne the used value for the "_ﬁeld1_" variable.</li>
	<li><b>Field #2 (Single Line):</b> Deﬁne the used value for the "_ﬁeld2_" variable.</li>
	<li><b>Field #3 (Full Text):</b> Deﬁne the used value for the "_ﬁeld3_" variable.</li>
	<li><b>Time threshold:</b> Time counter since the first alarm was triggered (or condition to trigger it) . During that time, the alerts are handled with the rest of the parameters (Min. number of alerts, Max. number of alerts). You can choose between the interval conﬁgured or deﬁne other interval.</li>
	<li><b>Min. number of alerts:</b> Minimum number of alerts needed to start triggering an alert. Works as a filter, needed to remove false positives.</li>
	<li><b>Max. number of alerts:</b> Maximum number of alerts that can be sent consecutively during the same time threshold.</li>
	<li><b>Assigned module:</b> Module to be monitored by the alert.</li><br>

<p>All the alerts of an agent can be seen using the "Alerts" tab. Let's see an example:<br>
"I want to ﬁre an alert when XXX goes down, and please, dont't disturb me again at least for one hour. After that time, if it is still down, ﬁre another alert and wait another hour".</p>
<p>You need to setup:</p>
<ul>
	<li>Time threshold 3600 (1 hour).</li>
	<li>Min. number of alerts = 1.</li>
	<li>Max. number of alerts = 1.</li>
</ul>
