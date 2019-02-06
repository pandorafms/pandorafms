<?php
/*
    Include package help/en
*/
?>

<h1>The module interval as factor</h1>
<h2>Where to change the interval</h2>
<p>
The module interval in the data type modules will be changed in the module 
definition in agent configuration file.<br><br>
The interval configuration token is <b>module_interval</b>.<br><br>
For example:<br><br>
<i>
module_begin<br>
module_name Module example<br>
module_type generic_data<br>
module_exec echo 100<br>
module_interval 2<br>
module_description This module will always return 100<br>
module_end<br>
</i>
</p>
<h2>How to set the interval on this type of modules</h2>
<p> In the <b>data type modules</b>, the interval is <b>not setted in seconds</b>.<br><br>

The interval is calculated as a <b>multiplier factor</b> for the agent interval.<br><br>

For example, if the agent has interval 300 (5 minutes), and you want a module that 
will be processed only every 15 minutes, then you should set a module interval 3<br><br>

This module will be preocessed every 300sec x 3 = 900sec (15 minutes).
</p>

