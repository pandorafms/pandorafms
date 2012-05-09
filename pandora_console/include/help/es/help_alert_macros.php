<?php
/**
 * @package Include/help/en
 */
?>
<h1>Macros de alertas</h1>

<p>
Las siguientes macros están disponibles:
</p>
<ul>
<li>_field1_: Campo 1 definido por el usuario.</li>
<li>_field2_: Campo 2 definido por el usuario.</li>
<li>_field3_: Campo 3 definido por el usuario.</li>
<li>_agent_: Nombre del agente que disparó la alerta.</li>
<li>_address_: Dirección del agente que disparó la alerta.</li>
<li>_timestamp_: Hora y fecha en que se disparó la alerta.</li>
<li>_data_: Dato que hizo que la alerta se disparase.</li>
<li>_alert_description_: Descripción de la alerta.</li>
<li>_alert_threshold_: Umbral de la alerta.</li>
<li>_alert_times_fired_: Número de veces que se ha disparado la alerta.</li>
<li>_module_: Nombre del módulo</li>
<li>_modulegroup_ : Nombre del grupo del módulo.</li>
<li>_moduledescription_: Descripcion del modulo.</li>
<li>_alert_name_: Nombre de la alerta.</li>
<li>_alert_priority_: Prioridad numérica de la alerta.</li>
<li>_id_agent_: ID del agente, util para construir URL de acceso a la consola de Pandora.</li>
<li>_id_alert_: ID de la alerta, util para correlar la alerta en herramientas de terceros.</li>
<li>_policy_: Nombre de la política a la que pertenece el módulo (si aplica).</li>
<li>_interval_ : Intervalo de la ejecución del módulo. </li>
<li>_target_ip_ : Dirección IP del objetivo del módulo.</li>
<li>_target_port_ : Puerto del objetivo del módulo.</li>
<li>_plugin_parameters_ : Parámetros del Plug-in del módulo.</li>

</ul>
<p>
Ejemplo: Error en el agente _agent_: _alert_description_ 
</p>

