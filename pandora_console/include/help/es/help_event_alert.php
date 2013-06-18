<?php
/*
* @package Include/help/es
*/
?>

<h1>Alerta de evento</h1>

Desde la versión 4.0 de Pandora FMS se pueden definir alertas sobre los eventos, lo que permite trabajar desde una perspectiva completamente nueva y mucho más flexible. Esta es una característica Enterprise.<br><br>

Las Alertas de evento nuevas se crean pinchando en el botón Create en el menú Event alerts en el menú de Administración.
<br><br>


<?php html_print_image ("images/help/event01.png", false, array('width' => '250px')); ?>


<br>
Una alerta de eventos está compuesta por distintas reglas, relacionadas entre sí por operadores lógicos (and, or, xor, nand, nor, nxor).
<br><br>

<?php html_print_image ("images/help/event02.png", false, array('width' => '550px')); ?>


Para hacer más fácil trabajar con ellas, los parámetros de configuración de una alerta de eventos son idénticos a los de una alerta de módulo. Aquí se puede encontrar una explicación detallada de cada uno de ellos. Únicamente existen dos parámetros específicos de las alertas de eventos:
<br><br>
    <b>Rule evaluation mode:</b> Hay dos opciones Pass y Drop. Pass significa que en caso de que un evento coincida con una alerta se sigan evaluando el resto de alertas. Drop significa que en caso de que un evento coincida con una alerta no se evaluen el resto de alertas. 
<br><br>
    <b>Group by:</b> Permite agrupar las reglas por agente, módulo, alerta o grupo. Por ejemplo, si se configura una regla para que salte cuando se reciban dos eventos críticos y se agrupa por agente, deberán llegar dos eventos críticos de un mismo agente. Se puede desactivar. 
<br><br>
Cada regla se configura para saltar ante un determinado tipo de evento, cuando se cumple la ecuación lógica definida por las reglas y sus operadores, la alerta se dispara.
<br><br>


<?php html_print_image ("images/help/event03.png", false, array('width' => '550px')); ?>



Los posibles parámetros de configuración de una regla son:
<br><br>
    <b>Name:</b> Nombre de la regla.<br>
    <b>User comment:</b> Comentario libre.<br>
    <b>Event:</b> Expresión regular que casa con el texto del evento.<br>
    <b>Window: </b>Los eventos que se hayan generado fuera de la ventana de tiempo serán descartados.<br>
    <b>Count:</b> Número de eventos que tienen que casar con la regla para que ésta se dispare.<br>
    <b>Agent: </b>Expresión regular que casa con el nombre del agente que generó el evento.<br>
    <b>Module:</b> Expresión regular que casa con el nombre del módulo que generó el evento.<br>
    <b>Module alerts:</b> Expresión regular que casa con el nombre de la alerta que generó el evento.<br>
    <b>Group: </b>Grupo al que pertenece el Agente.<br>
    <b>Criticity:</b> Criticidad del evento.<br>
    <b>Tag:</b> Tags asociados al evento.<br>
    <b>User:</b> Usuario asociado al evento.<br>
    <b>Event type:</b> Tipo de evento. <br><br>

Por ejemplo, podríamos configurar una regla que case con los eventos generados por cualquier módulo que se llame cpu_load de cualquier agente del grupo Servers que lleve asociado el tag System cuando el módulo pasa al estado crítico:
<br><br>


<?php html_print_image ("images/help/event04.png", false, array('width' => '550px')); ?>


<p>Dado el elevado número de eventos que puede llegar a albergar la base de datos de Pandora FMS, el servidor trabaja sobre una ventana de eventos que se define en el fichero de configuración pandora_server.conf mediante el parámetro event_window. Los eventos que se hayan generado fuera de esta ventana de tiempo no serán procesados por el servidor, de modo que no tiene sentido especificar en una regla una ventana de tiempo superior a la configurada en el servidor </p>


