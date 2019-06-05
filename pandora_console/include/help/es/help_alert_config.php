<?php
/**
 * @package Include/help/es
 */
?>
<h1>Configurar Acción de Alerta</h1>
<br>Las acciones son los componentes de las alertas en los que se relaciona un comando, descrito en el apartado anterior, con las variables genéricas Field 1, Field 2, ..., Field 10. Dichas acciones se usaran más adelante en las plantillas de alertas que son las que asocian una condición sobre un dato a una acción concreta. <br>
A continuación se detallan los campos que hay que rellenar:<br><br>

    <b>Name:</b> El nombre de la acción.<br>
    <b>Group:</b> El grupo de la acción.<br>
    <b>Command:</b> En este campo se define el comando que se usará en el caso de que se ejecute la alerta. Se puede elegir entre los diferntes Comandos que hay definidos en  <?php echo get_product_name(); ?>. Dependiendo del comando elegido nos aparecerán unos campos a rellenar u otros.<br>
    <b>Threshold:</b> El umbral de ejecución de la acción.<br>
    <b>Command Preview:</b> En este campo, no editable, aparecerá automáticamente el comando que se va a ejecutar en el sistema.<br>
    <b>Field X:</b> En estos campos se define el valor de las macros _field1_ a _field10_, que se usarán en el comando, en caso de ser necesario. Estos campos pueden ser un campo de texto o un combo de selección si se configura. Dependiendo del comando seleccionado apareceran un numero de campos a rellenar según sea necesario o no. Por ejemplo:<br><br>

Para el comando de los emails únicamente esta configurado el _field1_ (Destination address), _field2_ (Subject) y _field3_ (Mensaje)<br><br>

A la hora de crear la acción podemos definir únicamente estos 3 campos. Dentro de esos campos podemos configurar las macros que abajo se indican.
<br><br>
<?php html_print_image('images/help/actions.png', false, ['width' => '550px']); ?>
<br><br>
<br>

<p>
Además de las macros de módulo definidas, las siguientes macros están disponibles:
</p>
<ul>
  <li>_address_: Dirección del agente que disparó la alerta.</li>
  <li>_address_n_ : La dirección del agente que corresponde a la posicion indicada en "n". Ejemplo: address_1_ , address_2_</li>
  <li>_agent_: Alias del agente que disparó la alerta. Si no tiene asignado alias, se usa el nombre del agente.</li>
  <li>_agentalias_: Alias del agente que disparó la alerta.</li>
  <li>_agentcustomfield_n_: Campo personalizado número <i>n</i> del agente (eg. _agentcustomfield_9_).</li>
  <li>_agentcustomid_: ID personalizado del agente.</li>
  <li>_agentdescription_: Descripción del agente que disparó la alerta.</li>
  <li>_agentgroup_ : Nombre del grupo del agente.</li>
  <li>_agentname_: Nombre del agente que disparó la alerta.</li>
  <li>_agentos_: Sistema operativo del agente.</li>
  <li>_agentstatus_ : Estado actual del agente.</li>
  <li>_alert_critical_instructions_: Instrucciones contenidas en el módulo para un estado CRITICAL.</li>
  <li>_alert_description_: Descripción de la alerta.</li>
  <li>_alert_name_: Nombre de la alerta.</li>
  <li>_alert_priority_: Prioridad numérica de la alerta.</li>
  <li>_alert_text_severity_: Prioridad en texto de la alerta (Maintenance, Informational, Normal Minor, Warning, Major, Critical).</li>
  <li>_alert_threshold_: Umbral de la alerta.</li>
  <li>_alert_times_fired_: Número de veces que se ha disparado la alerta.</li>
  <li>_alert_unknown_instructions_: Instrucciones contenidas en el módulo para un estado UNKNOWN.</li>
  <li>_alert_warning_instructions_: Instrucciones contenidas en el módulo para un estado WARNING.</li>
  <li>_all_address_ : Todas las direcciones del agente que disparó la alerta.</li>
  <li>_data_: Dato que hizo que la alerta se disparase.</li>
  <li>_email_tag_: Emails asociados a los tags de módulos.</li>
  <li>_event_cfX_: (Solo alertas de evento) Clave del campo personalizado del evento que disparó la alerta. Por ejemplo, si hay un campo personalizado cuya clave es IPAM, se puede obtener su valor usando la macro _event_cfIPAM_.</li>
  <li>_event_description_ : (Solo alertas de evento) Descripción textual del evento de  <?php echo get_product_name(); ?>.</li>
  <li>_event_extra_id_ : (Solo alertas de evento) Id extra.</li>
  <li>_event_id_: (Solo alertas de evento) Id del evento que disparó la alerta.</li>
  <li>_event_text_severity_: (Solo alertas de evento) Prioridad en texto de el evento que dispara la alerta (Maintenance, Informational, Normal Minor, Warning, Major, Critical).</li>
  <li>_eventTimestamp_: Timestamp en el que se creo el evento.</li>
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
  <li>_field11_: Campo 11 definido por el usuario.</li>
  <li>_field12_: Campo 12 definido por el usuario.</li>
  <li>_field13_: Campo 13 definido por el usuario.</li>
  <li>_field14_: Campo 14 definido por el usuario.</li>
  <li>_field15_: Campo 15 definido por el usuario.</li>
  <li>_groupcontact_: Información de contacto del grupo. Se configura al crear el grupo.</li>
  <li>_groupcustomid_: ID personalizado del grupo.</li>
  <li>_groupother_: Otra información sobre el grupo. Se configura al crear el grupo.</li>
  <li>_homeurl_: Es un enlace de la URL pública que debe configurarse en las opciones generales de la configuración.</li>
  <li>_id_agent_: ID del agente, util para construir URL de acceso a la consola de  <?php echo get_product_name(); ?>.</li>
  <li>_id_alert_: ID de la alerta, util para correlar la alerta en herramientas de terceros.</li>
  <li>_id_group_ : ID del grupo de agente.</li>
  <li>_id_module_: ID del módulo.</li>
  <li>_interval_: Intervalo de la ejecución del módulo.</li>
  <li>_module_: Nombre del módulo.</li>
  <li>_modulecustomid_: ID personalizado del módulo.</li>
  <li>_moduledata_X_: Usando esta macro ("X" es el nombre del módulo en cuestión) recogemos el último dato de este módulo y si es numérico lo devuelve formateado con los decimales especificados en la configuración de la consola y con su unidad (si la tiene). Serviría para, por ejemplo, al enviar un correo al saltar una alerta de módulo, enviar también información adicional (y quizás muy relevante) de otros módulos del mismo agente.</li>
  <li>_moduledescription_: Descripción del módulo.</li>
  <li>_modulegraph_nh_: (Solo para alertas que usen el comando <i>eMail</i>) Devuelve una imagen codificada en base64 de una gráfica del módulo con un período de <i>n</i> horas (eg. _modulegraph_24h_). Requiere de una configuración correcta de la conexión del servidor a la consola vía api, la cual se realiza en el fichero de configuración del servidor.</li>
  <li>_modulegraphth_nh_: (Solo para alertas que usen el comando <i>eMail</i>) Misma operación que la macro anterior pero sólo con los umbrales crítico y de advertencia del módulo, en caso de que estén definidos.</li>
  <li>_modulegroup_: Nombre del grupo del módulo.</li>
  <li>_modulestatus_: Estado del módulo.</li>
  <li>_moduletags_: URLs asociadas a los tags de módulos.</li>
  <li>_name_tag_: Nombre de los tags asociados al módulo.</li>
  <li>_phone_tag_: Teléfonos asociados a los tags de módulos.</li>
  <li>_plugin_parameters_: Parámetros del plugin del módulo.</li>
  <li>_policy_: Nombre de la política a la que pertenece el módulo (si aplica).</li>
  <li>_prevdata_: Dato previo antes de disparase la alerta.</li>
  <li>_rca_: Cadena de análasis de causa raíz (sólo para servicios).</li>
  <li>_server_ip_: Ip del servidor al que el agente está asignado. </li>
  <li>_server_name_: Nombre del servidor al que el agente está asignado. </li>
  <li>_target_ip_: Dirección IP del objetivo del módulo.</li>
  <li>_target_port_: Puerto del objetivo del módulo.</li>
  <li>_timestamp_: Hora y fecha en que se disparó la alerta.</li>
  <li>_timezone_: Zona horaria que se representa en _timestamp_.</li>  
</ul>
<p>
Ejemplo: Error en el agente _agent_: _alert_description_ 
</p>

