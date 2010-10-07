<?php
/**
 * @package Include/help/en
 */
?>
<h1>WEB Monitoring</h1>

<p>
Advanced WEB Monitoring is a feature done by the Goliat/WEB Server in Pandora FMS Enterprise version.
<br /><br />
This is a sample of GOLIAT Webcheck module:
<br />
</p>
<pre>


task_begin
post http://galaga.artica.es/pandora/index.php?login=1
variable_name nick
variable_value demo
variable_name pass
variable_value demo
cookie 1
resource 1
task_end

task_begin
get http://galaga.artica.es/pandora/index.php?sec=messages&amp;sec2=operation/messages/message
cookie 1
resource 1
check_string Read messages
task_end


</pre>