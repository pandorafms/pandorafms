<?php
/**
 * @package Include/help/en
 */
?>
<h1>Macros de alertas</h1>

<p>
Además de las macros de módulo definidas, las siguientes macros están disponibles:
</p>
<ul>
<li>_field1_: Campo 1 definido por el usuario.</li>
<li>_field2_: Campo 2 definido por el usuario.</li>
<li>_field3_: Campo 3 definido por el usuario.</li>
<li>_field4_: Campo 4 definido por el usuario.</li>
<li>_field5_: Campo 5 definido por el usuario.</li>
<li>_field6_: Campo 6 definido por el usuario.</li>
<li>_field7_: Campo 7 definido por el usuario.</li>
<li>_field8_: Campo 8 definido por el usuario.</li>
<li>_field9_: Campo 9 definido por el usuario.</li>
<li>_field10_: Campo 10 definido por el usuario.</li>
<li>_agent_: Nombre del agente que disparó la alerta.</li>
<li>_agentcustomfield_<i>n</i>_: Campo personalizado número <i>n</i> del agente (eg. _agentcustomfield_9_). </li>
<li>_agentcustomid_: ID personalizado del agente. </li>
<li>_agentdescription_: Descripción del agente que disparó la alerta.</li>
<li>_agentgroup_: Nombre del grupo del agente.</li>
<li>_agentstatus_: Estado actual del agente.</li>
<li>_address_: Dirección del agente que disparó la alerta.</li>
<li>_timestamp_: Hora y fecha en que se disparó la alerta.</li>
<li>_timezone_: Area Nombre _timestamp_ que representa en.</li>
<li>_data_: Dato que hizo que la alerta se disparase.</li>
<li>_prevdata_: Dato previo antes de disparase la alerta.</li>
<li>_alert_description_: Descripción de la alerta.</li>
<li>_alert_threshold_: Umbral de la alerta.</li>
<li>_alert_times_fired_: Número de veces que se ha disparado la alerta.</li>
<li>_module_: Nombre del módulo.</li>
<li>_modulecustomid_: ID personalizado del módulo.</li>
<li>_modulegroup_: Nombre del grupo del módulo.</li>
<li>_moduledescription_: Descripcion del modulo.</li>
<li>_modulestatus_: Estado del módulo.</li>
<li>_alert_name_: Nombre de la alerta.</li>
<li>_alert_priority_: Prioridad numérica de la alerta.</li>
<li>_alert_text_severity_: Prioridad en texto de la alerta (Maintenance, Informational, Normal Minor, Warning, Major, Critical).</li>
<li>_eventt_text_severity_: (Solo alertas de evento) Prioridad en texto de el evento que dispara la alerta (Maintenance, Informational, Normal Minor, Warning, Major, Critical).</li>
<li>_event_id_: (Solo alertas de evento) Id del evento que disparó la alerta.</li>
<li>_id_agent_: ID del agente, util para construir URL de acceso a la consola de Pandora.</li>
<li>_id_group_ : Id del grupo de agente.</li>
<li>_id_alert_: ID de la alerta, util para correlar la alerta en herramientas de terceros.</li>
<li>_policy_: Nombre de la política a la que pertenece el módulo (si aplica).</li>
<li>_interval_: Intervalo de la ejecución del módulo. </li>
<li>_target_ip_: Dirección IP del objetivo del módulo.</li>
<li>_target_port_: Puerto del objetivo del módulo.</li>
<li>_plugin_parameters_: Parámetros del plugin del módulo.</li>
<li>_groupcontact_: Información de contacto del grupo. Se configura al crear el grupo.</li>
<li>_groupcustomid_: ID personalizado del grupo.</li>
<li>_groupother_: Otra información sobre el grupo. Se configura al crear el grupo.</li>
<li>_name_tag_: Nombre de los tags asociados al módulo.</li>
<li>_email_tag_: Emails asociados a los tags de módulos.</li>
<li>_phone_tag_: Teléfonos asociados a los tags de módulos.</li>
<li>_moduletags_: URLs asociadas a los tags de módulos.</li>
<li>_alert_critical_instructions_: Instrucciones contenidas en el módulo para un estado CRITICAL.</li>
<li>_alert_warning_instructions_: Instrucciones contenidas en el módulo para un estado WARNING.</li>
<li>_alert_unknown_instructions_: Instrucciones contenidas en el módulo para un estado UNKNOWN.</li>
<li>_modulegraph_<i>n</i>h_: (>=6.0) (Solo para alertas que usen el comando <i>eMail</i>)
Devuelve una imagen codificada en base64 de una gráfica del módulo con un período de <i>n</i> horas (eg. _modulegraph_24h_).
Requiere de una configuración correcta de la conexión del servidor a la consola vía api,
la cual se realiza en el fichero de configuración del servidor.</li>
</ul>

<p>
Ejemplo: Error en el agente _agent_: _alert_description_ 
</p>

