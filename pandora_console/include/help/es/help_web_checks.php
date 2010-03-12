<?php
/**
 * @package Include/help/en
 */
?>
<h1>Monitorización WEB </h1>

<p>
La monitorización WEB avanzada es una funcionalidad que realiza el Servidor WEB de Goliat en la versión Enterprise de Pandora FMS.

<br><br>
Este es un ejemplo del modulo Webcheck de GOLIAT:
<br>
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
get http://galaga.artica.es/pandora/index.php?sec=messages&sec2=operation/messages/message
cookie 1
resource 1
check_string Read messages
task_end


</pre>
</p>
