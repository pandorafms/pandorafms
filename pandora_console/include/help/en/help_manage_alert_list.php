<?php
/**
 * @package Include/help/en
 */
?>
<h1>Alerts</h1>

<p>
Alerts in <?php echo get_product_name(); ?> react to an "out of range" module value. The alert can consist of sending an e-mail or an SMS to the administrator, sending a SNMP trap, write the incident into the system log or into <?php echo get_product_name(); ?> log ﬁle, etc. Basically, an alert can be anything that can be triggered by a script conﬁgured in the Operating System where <?php echo get_product_name(); ?> Servers run.
</p>

<p>
When a new alert is created the following fields must be filled in:
</p>

<ul>
    <li><b>Agent name:</b> The name of the agent associated with the alert.</li>
    <li><b>Module:</b> Alert get module value and test if it is "out of range". In afirmative case it will raise an event (seding e-mail, etc.).</li>
    <li><b>Template:</b> Alerts width all parameters defined. They are used to do the administrator management easier.</li>
    <li><b>Action:</b> Allows to choose between all the alerts that have been configured. The selected action will be added to the action that is defined in the template.</li>
    <li><b>Threshold:</b> Defines the time interval in which it is guaranteed that an alert is not going to be fired more times than the number fixed in Maximum number of alerts. 
</ul>
