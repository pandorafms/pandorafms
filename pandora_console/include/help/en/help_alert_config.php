<?php
/**
 * @package Include/help/en
 */
?>
<h1>Configure Alert Action</h1>
<br>Actions are the components of alerts where a command (which is described in the previous section) is linked with the generic variables Field 1, Field 2, ..., Field 10. These actions will be used later in the alert templates that are the ones that associate a data condition with an specific action. <br>
Next are the fields that you should fill in:<br><br>

    <b>Name:</b> Name of the action.<br>
    <b>Group:</b> Group of the action. <br>
    <b>Command:</b> In this field is defined the command that will be used in case the alert will be executed . You can choose between the different commands that are defined in Pandora.Depending of the command chosen we will have some or others fields to fill. <br>
    <b>Threshold:</b> The action execution threshold. <br>
    <b>Command Preview:</b>  In this field, not editable, will automatically appear the command that will be executed in the system. <br>
    <b>Field X:</b> In this fields are defined the values of the macros _field1_ to _field10_, that will be used in the command if necessary. These fields can be a text field or combo box if configured. Depending on the selected command will appear a number of fields to fill in as needed or not. For example:<br><br>

To configure email action, you can only fill _field1_ (Destination address), _field2_ (Subject) y _field3_ (Message)<br><br>

Within these fields we can configure the macros listed below.
<br><br>
<?php html_print_image ("images/help/actions.png", false, array('width' => '550px')); ?>
<br><br>
<br>

<p>
Besides the defined module macros, the following macros are available:
<ul>
<li>_field1_ : User defined field 1.</li>
<li>_field2_ : User defined field 2.</li>
<li>_field3_ : User defined field 3.</li>
<li>_agent_ : Name of the agent that fired the alert.</li>
<li>_agentdescription_ : Description of the agent who fired alert.</li>
<li>_agentgroup_ : Agent group name.</li>
<li>_agentstatus_ : Current status of the agent.</li>
<li>_address_ : Address of the agent that fired the alert.</li>
<li>_timestamp_ : Time when the alert was fired (yy-mm-dd hh:mm:ss).</li>
<li>_timezone_ : Timezone name that _timestamp_ represents in.</li>
<li>_data_ : Module data that caused the alert to fire.</li>
<li>_alert_description_ : Alert description.</li>
<li>_alert_threshold_ : Alert threshold.</li>
<li>_alert_times_fired_ : Number of times the alert has been fired.</li>
<li>_module_ : Module name.</li>
<li>_modulegroup_ : Module group name.</li>
<li>_moduledescription_ : Description of the module who fired the alert.</li>
<li>_modulestatus_ : Status of the module.</li>
<li>_moduletags_ : Tags associated to the module.</li>
<li>_alert_name_ : Alert name.</li>
<li>_alert_priority_ : Numerical alert priority.</li>
<li>_alert_text_severity_ : Text alert severity (Maintenance, Informational, Normal Minor, Warning, Major, Critical).</li>
<li>_event_text_severity_ : (Only event alerts) Text event (who fire the alert) severity (Maintenance, Informational, Normal Minor, Warning, Major, Critical).</li>
<li>_event_id_ : (Only event alerts) Id of the event that fired the alert.</li>
<li>_id_agent_ : Id of agent, useful to build direct URL to redirect to a Pandora FMS console webpage.</li>
<li>_id_group_ : Id of agent group.</li>
<li>_id_alert_ : Numerical ID of the alert (unique), used to correlate on third party software</li>
<li>_policy_ : Name of the policy the module belongs to (if applies).</li>
<li>_interval_ : Execution interval of the module. </li>
<li>_target_ip_ : IP address of the target of the module.</li>
<li>_target_port_ : Port number of the target of the module.</li>
<li>_plugin_parameters_ : Plug-in Parameters of the module.</li>
<li>_groupcontact_ : Group contact information.	Configured when the group is created.</li>
<li>_groupother_ : Other information about the group. Configured when the group is created.</li>
<li>_email_tag_ : Emails associated to the module tags.</li>
<li>_modulegraph_nh_: (Only for alerts that use the command eMail) Returns an image of a module graph with a period of n hours (eg. _modulegraph_24h_). A correct setup of the connection between the server and the console's api is required. This setup is done into the server's configuration file.</li>
</ul>
<p>
</p>
<p>
Example: Agent _agent_ has fired alert _alert_ with data _data_
</p>

