<?php
/**
 * @package Include/help/en
 */
?>
<h1>Alert macros</h1>

<p>
Besides the defined module macros, the following macros are available:
</p>
<ul>
<li>_field1_: User defined field 1.</li>
<li>_field2_: User defined field 2.</li>
<li>_field3_: User defined field 3.</li>
<li>_field4_: User defined field 4.</li>
<li>_field5_: User defined field 5.</li>
<li>_field6_: User defined field 6.</li>
<li>_field7_: User defined field 7.</li>
<li>_field8_: User defined field 8.</li>
<li>_field9_: User defined field 9.</li>
<li>_field10_: User defined field 10.</li>
<li>_agent_: Name of the agent that triggered the alert.</li>
<li>_agentcustomfield_<i>n</i>_: Agent custom field number <i>n</i> (eg. _agentcustomfield_9_).</li>
<li>_agentcustomid_: Agent custom ID.</li>
<li>_agentdescription_: Description of the agent that triggered the alert.</li>
<li>_agentgroup_: Agent’s group name.</li>
<li>_agentstatus_: Current agent status.</li>
<li>_agentos_: Agent's operative system.</li>
<li>_address_: Address of the agent that triggered the alert.</li>
<li>_timestamp_: Time and date on which the alert was triggered (yy-mm-dd hh:mm:ss).</li>
<li>_timezone_: Timezone that is represented on _timestamp_.</li>
<li>_data_: Module data that caused the alert to fire.</li>
<li>_prevdata_ : Module data previus the alert to fire.</li>
<li>_alert_description_: Alert description.</li>
<li>_alert_threshold_: Alert threshold.</li>
<li>_alert_times_fired_: Number of times the alert has been triggered.</li>
<li>_module_: Module name.</li>
<li>_modulecustomid_: Module custom ID.</li>
<li>_modulegroup_: Module’s group name.</li>
<li>_moduledescription_: Description of the module that triggered the alert.</li>
<li>_modulestatus_: Module status.</li>
<li>_moduledata_X_: Last data of module X (module name, cannot have white spaces).</li>
<li>_alert_name_: Alert name.</li>
<li>_alert_priority_: Alert’s numeric priority.</li>
<li>_alert_text_severity_: Priority level, in text, for the alert (Maintenance, Informational, Normal Minor, Major, Critical).</li>
<li>_event_text_severity_: (Only event alerts) Text event (which triggered the alert) severity (Maintenance, Informational, Normal Minor, Warning, Major, Critical).</li>
<li>_event_id_: (Only event alerts) ID of the event that triggered the alert.</li>
<li>_id_agent_: Agent’s ID, useful for building a direct URL that redirects to a Pandora FMS console webpage.</li>
<li>_id_group_ : Agent group ID.</li>
<li>_id_alert_: Alert’s numeric ID (unique), used to correlate the alert with third party software.</li>
<li>_policy_: Name of the policy that the module belongs to (if applies).</li>
<li>_interval_: Module’s execution interval </li>
<li>_target_ip_: IP address for the module’s target.</li>
<li>_target_port_: Port number for the module’s target.</li>
<li>_plugin_parameters_: Module’s Plugin parameters.</li>
<li>_groupcontact_: Group’s contact information. Configured when the group is created.</li>
<li>_groupcustomid_: Group’s custom ID.</li>
<li>_groupother_: Other information about the group. Configured when the group is created.</li>
<li>_name_tag_: Names of the tags related to the module.</li>
<li>_email_tag_: Emails associated to the module’s tags.</li>
<li>_phone_tag_: Phone numbers related to the module’s tags.</li>
<li>_moduletags_: URLs associated to the module’s tags.</li>
<li>_alert_critical_instructions_: Instructions for CRITICAL status contained in the module.</li>
<li>_alert_warning_instructions_: Instructions for WARNING status contained in the module.</li>
<li>_alert_unknown_instructions_: Instructions for UNKNOWN status contained in the module.</li>
<li>_modulegraph_<i>n</i>h_: (>=6.0) (Only for alerts that use the command <i>eMail</i>)
Returns an image encoded in base64 of a module’s graph with a period of <i>n</i> hours (eg. _modulegraph_24h_).
A correct setup of the connection between the server and the console's API is required.
This setup is done on the server's configuration file.</li>
</ul>

<p>
Example: Agent _agent_ has fired alert _alert_ with data _data_
</p>

