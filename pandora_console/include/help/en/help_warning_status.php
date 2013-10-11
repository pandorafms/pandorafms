<?php
/**
 * @package Include/help/en
 */
?>
<h1>Warning Status</h1>

<h2>Numeric type modules</h2>

<p>
These field have three values: minimum, maximum and Inverse interval. Configuring them correctly you will get that some values will show a module as warning status.
</p>

<p>
To understand better these options is better to see an example. The CPU module will be always on green in the agent status , so it simply informs of a value between 0% and 100%. If we want that the module of CPU usage will be shown in yellow (warning) when they reached the 70% of its use. We should configure:
</p>

<li>Warning Min:70</li>
<li>Warning Max:0 (limitless)</li>

<p>
With this, if its value is greater 70 it will be in yellow (WARNING), and under 70 in green (NORMAL). 
</p>

<p>
If we enable the Inverse interval checkbox, the module will change to Warning status when the value doesnt be between the setted interval. In the example when were below 70.
</p>

<h2>String type modules</h2>

<p>
These field have two values: String and Inverse interval. Configuring them correctly you will get that some values will show a module as Warning status.
</p>

<p>
In the field String we will set a regular expression. When the data matchs with it, the module will turn into Warning status.
</p>

<li>String:(error|fail)</li>

<p>
With this, if there is a word chain error or fail the module appear in warning state.
</p>

<p>
If we enable the Inverse interval checkbox, the module will change to the Warning status when the value doesnt match with the regular expression.
</p>
