<?php
/**
 * @package Include/help/en
 */
?>
<h1>Alert macros</h1>

<p>
The following macros are available:
<ul>
<li>_field1_ : User defined field 1.</li>
<li>_field2_ : User defined field 2.</li>
<li>_field3_ : User defined field 3.</li>
<li>_agent_ : Name of the agent that fired the alert.</li>
<li>_agentdescription_ : Description of the agent who fired alert</li>
<li>_agentgroup_ : Agent group name </li>
<li>_address_ : Address of the agent that fired the alert.</li>
<li>_timestamp_ : Time when the alert was fired (yy-mm-dd hh:mm:ss).</li>
<li>_data_ : Module data that caused the alert to fire.</li>
<li>_alert_description_ : Alert description.</li>
<li>_alert_threshold_ : Alert threshold.</li>
<li>_alert_times_fired_ : Number of times the alert has been fired.</li>
<li>_module_ : Module name</li>
<li>_modulegroup_ : Module group name.</li>
<li>_moduledescription_ : Description of the module who fired the alert </li>
<li>_alert_name_ : Alert name </li>
<li>_alert_priority_ : Numerical alert priority </li>
<li>_id_agent_ : Id of agent, useful to build direct URL to redirect to a Pandora FMS console webpage.</li>
<li>_id_alert_ : Numerical ID of the alert (unique), used to correlate on third party software</li>
<li>_policy_ : Name of the policy the module belongs to (if applies).</li>
<li>_interval_ : Execution interval of the module. </li>
<li>_target_ip_ : IP address of the target of the module.</li>
<li>_target_port_ : Port number of the target of the module.</li>
<li>_plugin_parameters_ : Plug-in Parameters of the module.</li>

</ul>
<p>
</p>
<p>
Example: Agent _agent_ has fired alert _alert_ with data _data_
</p>


