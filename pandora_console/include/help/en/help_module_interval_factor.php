<?php
/* Include package help/en
*/
?>

<h1>The module interval as factor</h1>
<p> In the <b>data type modules</b>, the interval is <b>not setted in seconds</b>.<br><br>

The interval is calculated as a <b>multiplier factor</b> for the agent interval.<br><br>

For example, if the agent has interval 300 (5 minutes), and you want a module that 
will be processed only every 15 minutes, then you should set a module interval 3<br><br>

Así, este módulo será procesado cada 300sec x 3 = 900sec (15 minutos).
</p>

