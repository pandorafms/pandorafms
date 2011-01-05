<?php
/**
 * @package Include/help/en
 */
?>
<h1>Alerts</h1>

<p>
Alerts in Pandora FMS react to an "out of range" module value. The alert can consist of sending an e-mail or an SMS to the administrator, sending a SNMP trap, write the incident into the system log or into Pandora FMS log ﬁle, etc. Basically, an alert can be anything that can be triggered by a script conﬁgured in the Operating System where Pandora FMS Servers run.
</p>

<p>
When a new alert is created the following fields must be filled in:
</p>

<ul>
	<li>Agent name: The name of the agent associated with the alert.</li>
	<li>Module: Alert get module value and test if it is "out of range". In afirmative case it will raise an event (seding e-mail, etc.).</li>
	<li>Template: Alerts width all parameters defined. They are used to do the administrator management easier.</li>
	<li>Action: Allows to choose between all the alerts that have been configured. The selected action will be added to the action that is defined in the template.</li>
</ul>
