<?php
/* Include package help/es
*/
?>

<p> Cuando la replicación de eventos está activada, los eventos recibidos se copiarán a la base de datos remota de una metaconsola.
<br><br>
Es necesario configurar las credenciales de la base de datos de la metaconsola, así como el modo de replicación (todos los eventos o solo los validados) y el intervalo de replicación en segundos.
<br><br>
<b>NOTAS:</b>
<br><br>
El visor de eventos se deshabilita cuando se activa esta opción.
<br><br>
Para que los cambios en la configuración de replicación de eventos se hagan efectivos será necesario reiniciar el servidor.
<br><br>
El fichero de configuración del servidor deberá tener el token:

<i>event_replication 1</i>

</p>

