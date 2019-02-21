<?php
/**
 * @package Include/help/en
 */
?>Event responses macros</h1>

<p>
The response target (command or URL) accepts macros to customize it.
<br><br>
The accepted macros are:

<ul>
<li><b>Agent address:</b> _agent_address_</li>
<li><b>Agent ID:</b> _agent_id_</li>
<li><b>Event related alert ID:</b> _alert_id_</li>
<li><b>Date on which the event occurred:</b> _event_date_</li>
<li><b>Extra ID:</b> _event_extra_id_</li>
<li><b>Event ID:</b> _event_id_</li>
<li><b>Event instructions:</b> _event_instruction_</li>
<li><b>Event severity ID:</b> _event_severity_id_</li>
<li><b>Event severity (translated by <?php echo get_product_name(); ?> console):</b> _event_severity_text_</li>
<li><b>Event source:</b> _event_source_</li>
<li><b>Event status (new, validated or event in process):</b> _event_status_</li>
<li><b>Event tags separated by commas:</b> _event_tags_</li>
<li><b>Full text of the event:</b> _event_text_</li>
<li><b>Event type (System, going into Unknown Status...):</b> _event_type_</li>
<li><b>Date on which the event occurred in utimestamp format:</b> _event_utimestamp_</li>
<li><b>Group ID:</b> _group_id_</li>
<li><b>Group name in database:</b> _group_name_</li>
<li><b>Event associated module address:</b> _module_address_</li>
<li><b>Event associated module ID:</b> _module_id_</li>
<li><b>Event associated module name:</b> _module_name_</li>
<li><b>Event owner user:</b> _owner_user_</li>
<li><b>User ID:</b> _user_id_</li>
<li><b>Id of the user who fires the response:</b> _current_user_</li>
</ul>

<h4>Custom fields</h4>

Custom event fields are also available in event response macros. They would
have <b>_customdata_*_</b> form where the asterisk (*) would have to be 
replaced by the custom field key you want to use.

<h3>Basic use</h3>
For example, to ping the agent associated with the event:
<br><br>
Configure the command as follows: <i>ping -c 5 _agent_address_</i>
<br><br>
If there are configured parameters, it is possible to use them as macros, too.
 
<h3>Parameters macros</h3>
For example, to customize a URL with parameters:
<br><br>
Configure the parameters as follows: <i>User,Section</i>
<br><br>
And configure the URL like this: <i>http://example.com/index.php?user=_User_&amp;section=_Section_</i>
</p>
