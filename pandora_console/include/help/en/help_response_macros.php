<?php
/**
 * @package Include/help/en
 */
?>Event responses macros</h1>

<p>
The response target (command or URL) accepts macros to custom it.
<br><br>
The macros accepted are the following:

<ul>
<li><b>Agent address:</b> _agent_address_</li>
<li><b>Agent id:</b> _agent_id_</li>
<li><b>Event id:</b> _event_id_</li>
</ul> 

<h3>Basic use</h3>
In example, to ping the agent associated to the event:
<br><br>
Configure command like: <i>ping -c 5 _agent_address_</i>
<br><br>
If there are configured parameters, is possible use it as macros too.
 
<h3>Parameters macros</h3>
In example, to custom a URL with parameters:
<br><br>
Configure parameters like: <i>User,Section</i>
<br><br>
And configure the URL like: <i>http://example.com/index.php?user=_User_&amp;section=_Section_</i>
</p>
