<?php
/**
 * @package Include/help/en
 */
?>
<h1>Umbral de acción</h1>

<p>
Una acción de una alerta no se ejecutará más de una vez cada
action_threshold segundos, independientemenete del número de veces que
se dispare la alerta.
</p>
<p>
Por ejemplo, si ha configurado una acción que le envía un email cuando
la alerta se dispara y no quiere recibir más de un email por hora, puede
configurar un action_threshold de 3600.
</p>
<p>
Tenga en cuenta que el action_threshold individual de una acción tiene
precedencia sobre el action_threshold global de una alerta.
</p>
