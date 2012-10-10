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
<li><b>Id del evento:</b> _event_id_</li>
</ul> 

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
