<?php
/**
 * @package Include/help/es
 */
?>
<h1>Coincidencias de la alerta</h1>

<p>
Define el número de alertas que deben ocurrir antes de ejecutar la acción. Es un parámetro de ajuste fino.<br><br>
Esto permite "redefinir" un poco más el comportamiento de la alerta, de forma que si hemos definido un máximo de 5 veces las veces que se puede disparar una alerta, y sólo queremos que nos envie un email, pondremos aquí un 0 y un 1, para decirle que sólo nos envie un email desde la vez 0 a la 1 (osea, una vez). Cuando una alerta se recupera, todas las acciones que se hayan ejecutado hasta ese momento se volverán a ejecutar.<br><br>

Ahora veremos que podemos añadir más acciones a la misma alerta, definiendo con estos campos "Number of alerts match from" el comportamiento de la alerta en función de cuantas veces se dispare.<br><br>

Por ejemplo, podemos querer que mande un email a XXXXX la primera vez que ocurra, y si sigue caído el monitor, envíe un email a ZZZZ. Para ello, despues de asociar la alerta, en la tabla de alertas asignadas, puedo añadir mas acciones a una alerta ya definida cambiando este parámetro. 
</p>
