<?php
/**
 * @package Include/help/en
 */
?>
<h1>Monitorización WEB </h1>

<p>
La monitorización WEB avanzada es una funcionalidad que realiza el Servidor WEB de Goliat en la versión Enterprise de <?php echo get_product_name(); ?>.

<br><br>
Este es un ejemplo del modulo Webcheck de GOLIAT:
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
Las siguientes macros están disponibles:
</p>
<ul>
<li>_agentdescription_ : Descripción del agente que disparó la alerta.</li>
<li>_agentgroup_ : Nombre del grupo del agente.</li>
<li>_agentstatus_ : Estado actual del agente.</li>
<li>_address_: Dirección del agente que disparó la alerta.</li>
<li>_module_: Nombre del módulo</li>
<li>_modulegroup_ : Nombre del grupo del módulo.</li>
<li>_moduledescription_: Descripcion del modulo.</li>
<li>_modulestatus_ : Estado del módulo.</li>
<li>_moduletags_ : Etiquetas asociadas al módulo.</li>
<li>_id_agent_: ID del agente, util para construir URL de acceso a la consola de Pandora.</li>
<li>_policy_: Nombre de la política a la que pertenece el módulo (si aplica).</li>
<li>_interval_ : Intervalo de la ejecución del módulo. </li>
<li>_target_ip_ : Dirección IP del objetivo del módulo.</li>
<li>_target_port_ : Puerto del objetivo del módulo.</li>
<li>_plugin_parameters_ : Parámetros del Plug-in del módulo.</li>
<li>_email_tag_ : Emails asociados a los tags de módulos.</li>

</ul>
<p>
Por ejemplo:
<pre>
task_begin
get http://_address_
check_string _module_
task_end
</pre>
</p>
