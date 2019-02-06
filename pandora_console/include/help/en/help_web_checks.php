<?php
/**
 * @package Include/help/en
 */
?>
<h1>WEB Monitoring</h1>

<p>
Advanced WEB Monitoring is a feature done by the Goliat/WEB Server in <?php echo get_product_name(); ?> Enterprise version.
<br /><br />
This is a sample of GOLIAT Webcheck module:
<br />
</p>
<pre>
task_begin
post http://galaga.artica.es/monitoring/index.php?login=1
variable_name nick
variable_value demo
variable_name pass
variable_value demo
cookie 1
resource 1
task_end

task_begin
get http://galaga.artica.es/monitoring/index.php?sec=messages&amp;sec2=operation/messages/message
cookie 1
resource 1
check_string Read messages
task_end
</pre>

<p>
The following macros are available:
<ul>
<li>_agent_ : Name of the agent that fired the alert.</li>
<li>_agentdescription_ : Description of the agent who fired alert.</li>
<li>_agentgroup_ : Agent group name.</li>
<li>_agentstatus_ : Current status of the agent.</li>
<li>_address_ : Address of the agent that fired the alert.</li>
<li>_module_ : Module name.</li>
<li>_modulegroup_ : Module group name.</li>
<li>_moduledescription_ : Description of the module who fired the alert.</li>
<li>_modulestatus_ : Status of the module.</li>
<li>_moduletags_ : Tags associated to the module.</li>
<li>_id_agent_ : Id of agent, useful to build direct URL to redirect to a <?php echo get_product_name(); ?> console webpage.</li>
<li>_policy_ : Name of the policy the module belongs to (if applies).</li>
<li>_interval_ : Execution interval of the module. </li>
<li>_target_ip_ : IP address of the target of the module.</li>
<li>_target_port_ : Port number of the target of the module.</li>
<li>_plugin_parameters_ : Plug-in Parameters of the module.</li>    
<li>_email_tag_ : Emails associated to the module tags.</li>

</ul>
<p>
</p>
<p>
For example:
<pre>
task_begin
get http://_address_
check_string _module_
task_end

</pre>
</p>
