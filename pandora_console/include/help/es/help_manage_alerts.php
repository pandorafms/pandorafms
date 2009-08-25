<?php
/**
 * @package Include/help/es
 */
?>
<h1>Alertas</h1>

Las alertas en Pandora FMS reaccionan ante un valor &laquo;fuera de rango&raquo;. La alerta puede consistir del envío de un correo-e o un SMS al administrador, enviar un trap SNMP, escribir un incidente en el fichero <i>log</i> del sistema o en el <i>log</i> de Pandora FMS, etc. Básicamente, una alerta puede ser cualquier cosa que se pueda dispoarar desde un <i>script</i> configurado en el Sistema Operativo donde Pandora FMS se ejecuta.
<br /><br />
Los valores &laquo;_field1_&raquo;, &laquo;_field2_&raquo; y &laquo;_field3_&raquo; de las alertas personalizadsa se usan para construir el comando de línea que se ejecutará.
<br /><br />
Cuando se crea una nueva alerta, se deben rellenar los siguientes campos:

<ul>
	<li>Nombre de la alerta: El nombre de la alerta. Es importante describir correctamente su funcionalidad, pero brevemente, por ejemplo: &laquo;Log de comm.&raquo;".</li>
	<li>Comando: Comando que la alerta ejecutará, es el campo más importante al definir la alerta. Note que se usan las macros _field1, _field2_, y _field3_ para reemplazar los parámetros configurados en la definición de la alerta. De esta manera se construye la ejecución del comando disparado por la alerta. Al definir una alerta, debería comprobar la ejecución correcta de la misma, y también que el resultado es el esperado (envío de correo-e, generación de una entrada en el fichero log, etc) en la línea de comandos.</li>
	<li>Descripción: Descripción detallada de la alerta, opcional.</li>
</ul>

El conjunto completo de macros que se pueden usar en las alertas es el siguiente:

<ul>
	<li>_field1_: Generalmente usado como el nombre de usuario, el número de teléfono, campo para el destinatario del correo-e.</li>
	<li>_field2_: Generalmente usado como una breve descripción de eventos, o el asunto para un correo-e.</li>
	<li>_field3_: Una explicación completa para el evento, se puede usar como el campo de texto para un correo-e o un SMS.</li>
	<li>_agent_: Nombre completo del agente.</li>
	<li>_timestamp_: Una representación estándar de la fecha y hora. Automáticamente reemplazado cuando se ejecuta la alerta.</li>
	<li>_data_: El valor del dato que dispara la alerta.</li>
</ul>
