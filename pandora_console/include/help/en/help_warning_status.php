<?php
/**
 * @package Include/help/en
 */
?>
<h1>Warning Status</h1>

<p>
These field have two values, minimum and maximum. Configuring them correctly you will get that some values will show a module as warning status.
</p>

<p>
To understand better these options is better to see an example. The CPU module will be always on green in the agent status , so it simply informs of a value between 0% and 100%. If we want that the module of CPU usage will be shown in yellow (warning) when they reached the 70% of its use. We should configure:
</p>

<li>Warning status:70.</li>

<p>
With this, if its value is greater 70 it will be in yellow (WARNING), and under 70 in green (NORMAL). 
</p>

