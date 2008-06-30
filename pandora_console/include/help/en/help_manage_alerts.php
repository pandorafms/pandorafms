<h1>Alerts</h1>

Alerts in Pandora FMS react to an "out of range" module value. The alert can consist of sending an e-mail or an SMS to the administrator, sending a SNMP trap, write the incident into the system log or into Pandora FMS log ﬁle, etc. Basically, an alert can be anything that can be triggered by a script conﬁgured in the Operating System where Pandora FMS Servers run.
<br><br>
The values "_ﬁeld1_", "_ﬁeld2_" and "_ﬁeld3_" of the customized alerts are used to build the command line that will be executed.
<br><br>
When a new alert is created the following fields must be filled in:

<ul>
	<li>Alert name: The name of the alert. It is important to describe correctly its function, but briefly, for example: "Comm. log".</li>
	<li>Command: Command that the alert will trigger, tis he most important field while defining an alert. Note that the macros _field1, _field2_, and _field3_ are used to replace the configured parameters at the alert definition. That way the execution of the command fired by the alert is built. While defining an alert, you should test the correct execution of the alert, and that the result is the expected (send an email, generate an entry in a log, etc) at the command line.</li>
	<li>Description: Long description of the alert, optional.</li>
</ul>

The complete set of macros that can used within an alert is the following:

<ul>
	<li>_field1_: Usually used as username, phone number, file to send or e-mail destination.</li>
	<li>_field2_: Usually used as short description of events, or subject line for e-mails.</li>
	<li>_field3_: A full text explanation for the event, can be used as the text field for an email or SMS.</li>
	<li>_agent_: Full agent name.</li>
	<li>_timestamp_: A standard representation of date and time. Automatically replaced when the alert is executed.</li>
	<li>_data_: The data value that triggered the alert.</li>
</ul>
