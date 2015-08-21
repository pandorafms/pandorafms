<?php
/**
 * @package Include/help/en
 */
?>
<h1>Critical Status</h1>

<h2>Numeric type modules</h2>

<p>These field have three values: Minimum, Maximum and Inverse interval.
Configuring them correctly you will get that some values will show a
module as critical status.</p>

<p>To understand better these options is better to see an example. The
CPU module will be always on green in the agent status , so it simply
informs of a value between 0% and 100%. If we want that the module of
CPU usage will be shown in red (critical) when they pass the 90% of its
use. We should configure:</p>

<li>Critical Min:90</li>
<li>Critical Max:0 (limitless)</li>

<p>With this, if its value is higher 90 it will be in yellow (CRITICAL),
and under 90 in green (NORMAL). </p>

<p>If we enable the Inverse interval checkbox, the module will change to
Critical status when the value doesnt be between the setted interval. In
the example when were below 90.</p>

<h2>String type modules</h2>

<p>These field have two values: String and Inverse interval. Configuring
them correctly you will get that some values will show a module as
Critical status.</p>

<p>In the field String we will set a regular expression. When the data
matchs with it, the module will turn into Critical status.</p>

<li>String:(urgent|serious)</li>

<p>With this, if there is a word chain urgent or serious the module
appear in warning state.</p>

<p>If we enable the Inverse interval checkbox, the module will change to
the Critical status when the value doesnt match with the regular
expression.</p>
