<?php
/**
 * @package Include/help/en
 */
?>
<h1>Alerts</h1>

<i>Assigning Alerts to modules</i><br>
<i>Adding new alerts to a module</i><br>
<i>Editing a module’s alert</i><br>
<p>The next step after adding an agent, having configured its modules, and defined the alerts, is assigning those alerts to the agent. This step is needed to establish alert conditions in case we want to do so. This is done by clicking on the agent we wish to configure in the "Manage agents" option, from the Administration menu, or using the editing mode and selecting the “alerts” tab from the agent view.</p>

<p>The following fields must be completed in order to assign an alert:<br><br></p>

    <li><b>Alert type:</b> This can be selected from the previously generated alert list.</li>
    <li><b>Max. Value:</b> Defines the maximum value for a module. Any value above that threshold will trigger the alert.</li>
    <li><b>Min. Value:</b> Defines the minimum value for a module. Any value below that will trigger the alert. The ”max." &amp; "min." couple are key values when defining an alert, since they define the range for normal values. Outside that range <?php echo get_product_name(); ?> will trigger the alert.</li>
    <li><b>Alert text:</b> In the case of string modules you can define a regular expression or a substring to match the contents of a data module in order to trigger the alert.</li>
    <li><b>Time from / Time to:</b> This defines a “valid” timespan to trigger alert.</li>
    <li><b>Description:</b> Describes the function of the alert, and it is useful to identify the alert among the others in the general view of alerts.</li>
    <li><b>Field #1 (Alias, name):</b> Defines the value used for the "_ﬁeld1_" variable.</li>
    <li><b>Field #2 (Single Line):</b> Defines the value used for the "_ﬁeld2_" variable.</li>
    <li><b>Field #3 (Full Text):</b> Deﬁnes the value used for the "_ﬁeld3_" variable.</li>
    <li><b>Time threshold:</b> defines the timespan during which its guaranteed that an alert will not fire more times than the set Maximum number of alerts</i> Once this timespan is surpassed, an alert is recovered if it reaches a correct value, except if the <i> Alert Recovery</i> value is enabled, in which case the alert will recover immediately after receiving a correct value, regardless of the threshold.</li>.
    <li><b>Min. number of alerts:</b> Minimum number of alerts needed to start triggering an alert. Works as a necessary filter to remove false positives.</li>
    <li><b>Max. number of alerts:</b> Maximum number of alerts that can be sent consecutively during the same time period.</li>
    <li><b>Assigned module:</b> Module that needs to be monitored by the alert.</li><br>

<p>All the alerts of an agent can be seen using the &laquo;Alerts&raquo; tab. An example is shown here:<br>
"I want to ﬁre an alert when XXX goes down, yet I don’t wish to be bothered again during, at least, one hour. After this time has gone by, if it’s still down, another alert should go off and another hour should go by”.</p>
<p>You need to set:</p>
<ul>
    <li>Time threshold 3600 (1 hour).</li>
    <li>Min. number of alerts = 1.</li>
    <li>Max. number of alerts = 1.</li>
</ul>
