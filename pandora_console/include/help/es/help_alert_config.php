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
    <b>Command:</b> En este campo se define el comando que se usará en el caso de que se ejecute la alerta. Se puede elegir entre los diferntes Comandos que hay definidos en Pandora. Dependiendo del comando elegido nos aparecerán unos campos a rellenar u otros.<br>
    <b>Threshold:</b> El umbral de ejecución de la acción.<br>
    <b>Command Preview:</b> En este campo, no editable, aparecerá automáticamente el comando que se va a ejecutar en el sistema.<br>
    <b>Field X:</b> En estos campos se define el valor de las macros _field1_ a _field10_, que se usarán en el comando, en caso de ser necesario. Estos campos pueden ser un campo de texto o un combo de selección si se configura. Dependiendo del comando seleccionado apareceran un numero de campos a rellenar según sea necesario o no. Por ejemplo:<br><br>

Para el comando de los emails únicamente esta configurado el _field1_ (Destination address), _field2_ (Subject) y _field3_ (Mensaje)<br><br>

A la hora de crear la acción podemos definir únicamente estos 3 campos. Dentro de esos campos podemos configurar las macros que abajo se indican.
<br><br>
<?php html_print_image ("images/help/actions.png", false, array('width' => '550px')); ?>
<br><br>
<br>

<p>
Además de las macros de módulo definidas, las siguientes macros están disponibles:
</p>
<ul>
<li>_field1_: Campo 1 definido por el usuario.</li>
<li>_field2_: Campo 2 definido por el usuario.</li>
<li>_field3_: Campo 3 definido por el usuario.</li>
<li>_agent_: Nombre del agente que disparó la alerta.</li>
<li>_agentdescription_ : Descripción del agente que disparó la alerta.</li>
<li>_agentgroup_ : Nombre del grupo del agente.</li>
<li>_agentstatus_ : Estado actual del agente.</li>
<li>_address_: Dirección del agente que disparó la alerta.</li>
<li>_all_address_ : Todas las direcciones del agente que disparo la alerta.</li>
<li>_address_n_ : La dirección del agente que corresponde a la posicion indicada en "n" ejemplo: address_1_ , address_2_</li>
<li>_agentos_: Sistema operativo del agente.</li>
<li>_timestamp_: Hora y fecha en que se disparó la alerta.</li>
<li>_timezone_: Area Nombre _timestamp_ que representa en.</li>
<li>_data_: Dato que hizo que la alerta se disparase.</li>
<li>_prevdata_: Dato previo antes de disparase la alerta.</li>
<li>_alert_description_: Descripción de la alerta.</li>
<li>_alert_threshold_: Umbral de la alerta.</li>
<li>_alert_times_fired_: Número de veces que se ha disparado la alerta.</li>
<li>_module_: Nombre del módulo</li>
<li>_modulegroup_ : Nombre del grupo del módulo.</li>
<li>_moduledescription_: Descripcion del modulo.</li>
<li>_modulestatus_ : Estado del módulo.</li>
<li>_moduletags_ : Etiquetas asociadas al módulo.</li>
<li>_alert_name_: Nombre de la alerta.</li>
<li>_alert_priority_: Prioridad numérica de la alerta.</li>
<li>_alert_text_severity_: Prioridad en texto de la alerta. (Maintenance, Informational, Normal Minor, Warning, Major, Critical)</li>
<li>_eventt_text_severity_: (Solo alertas de evento) Prioridad en texto de el evento que dispara la alerta. (Maintenance, Informational, Normal Minor, Warning, Major, Critical)</li>
<li>_event_id_ : (Solo alertas de evento) Id del evento que disparó la alerta.</li>
<li>_id_agent_: ID del agente, util para construir URL de acceso a la consola de Pandora.</li>
<li>_id_group_ : Id del grupo de agente.</li>
<li>_id_alert_: ID de la alerta, util para correlar la alerta en herramientas de terceros.</li>
<li>_policy_: Nombre de la política a la que pertenece el módulo (si aplica).</li>
<li>_interval_ : Intervalo de la ejecución del módulo. </li>
<li>_target_ip_ : Dirección IP del objetivo del módulo.</li>
<li>_target_port_ : Puerto del objetivo del módulo.</li>
<li>_plugin_parameters_ : Parámetros del Plug-in del módulo.</li>
<li>_groupcontact_ : Información de contacto del grupo.	Se configura al crear el grupo.</li>
<li>_groupother_ : Otra información sobre el grupo. Se configura al crear el grupo.</li>
<li>_email_tag_ : Emails asociados a los tags de módulos.</li>
<li>_modulegraph_nh_: (Sólo para alertas que usen el comando eMail) Devuelve una imagen codificada en base64 de una gráfica del módulo con un período de n horas (eg. _modulegraph_24h_). Requiere de una configuración correcta de la conexión del servidor a la consola vía api, la cual se realiza en el fichero de configuración del servidor.</li>
<li>_homeurl_ : Es un link de la URL pública esta debe de estar configurada en las opciones generales del setup.</li>
</ul>
<p>
Ejemplo: Error en el agente _agent_: _alert_description_ 
</p>

