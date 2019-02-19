<?php
/*
 * @package Include/help/es/
 */
?>
<h1>Macros en Respuestas de eventos</h1>

<p>
El target de la respuesta (comando o URL) acepta macros para personalizarlo.
<br><br>
Las macros aceptadas son las siguientes:

<ul>
<li><b>Dirección del agente:</b> _agent_address_</li>
<li><b>Id del agente:</b> _agent_id_</li>
<li><b>Id de la alerta asociada al evento:</b> _alert_id_</li>
<li><b>Fecha en la que se produjo el evento:</b> _event_date_</li>
<li><b>Id extra:</b> _event_extra_id_</li>
<li><b>Id del evento:</b> _event_id_</li>
<li><b>Instrucciones del evento:</b> _event_instruction_</li>
<li><b>Id de la criticidad del evento:</b> _event_severity_id_</li>
<li><b>Gravedad del evento (traducido por la consola de <?php echo get_product_name(); ?>):</b> _event_severity_text_</li>
<li><b>Procedencia del evento:</b> _event_source_</li>
<li><b>Estado del evento (Nuevo, validado o evento en proceso):</b> _event_status_</li>
<li><b>Etiquetas del evento separadas por comas:</b> _event_tags_</li>
<li><b>Texto completo del evento:</b> _event_text_</li>
<li><b>Tipo del evento (Sistema, Cambiando a estado desconocido...):</b> _event_type_</li>
<li><b>Fecha en la que se produjo el evento en formato utimestamp:</b> _event_utimestamp_</li>
<li><b>Id del grupo:</b> _group_id_</li>
<li><b>Nombre del grupo en base de datos:</b> _group_name_</li>
<li><b>Dirección del módulo asociado al evento:</b> _module_address_</li>
<li><b>Id del módulo asociado al evento:</b> _module_id_</li>
<li><b>Nombre del módulo asociado al evento:</b> _module_name_</li>
<li><b>Usuario propietario del evento:</b> _owner_user_</li>
<li><b>Id del usuario:</b> _user_id_</li>
<li><b>Id del usuario que ejecuta la respuesta:</b> _current_user_</li>
</ul> 

<h4>Campos personalizados</h4>
Los campos personalizados del evento también están disponibles en las macros de
respuestas de eventos. Tendrían la forma de <b>_customdata_*_</b> donde habría
que sustituir el asterisco (*) por la clave del campo personalizado que se desee
utilizar.

<h3>Uso b&aacute;sico</h3>
Por ejemplo, para hacer un ping al agente asociado al evento:
<br><br>
Configurar el comando as&iacute;: <i>ping -c 5 _agent_address_</i>
<br><br>
Si hay par&aacute;metros configurados, es posible usarlos como macros también. 
<h3>Macros de par&aacute;metros</h3>
Por ejemplo, para personalizar una URL con parámetros:
<br><br>
Configurar los par&aacute;metros as&iacute;: <i>User,Section</i>
<br><br>
Y configurar la URL as&iacute;: <i>http://example.com/index.php?user=_User_&amp;section=_Section_</i>
</p>
