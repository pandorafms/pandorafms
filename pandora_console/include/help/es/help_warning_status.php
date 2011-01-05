<?php
/**
 * @package Include/help/es
 */
?>
<h1>Warning Status</h1>

<p>
Estos campos tiene dos valores, mínimo y máximo. Configurandolos correctamente se conseguirá que algunos valores se muestren en estado de alerta.
</p>

<p>
Para entender mejor estas opciones mejor se puede usar un ejemplo. El módulo de CPU estará siempre en verde en el estado del agente, asi que simplemente informará de un valor entre 0% y 100%. Si se quiere que el módulo de uso de la CPU se muestre como amarillo (alerta) cuando se llegue el 70% de uso. Se debería configurar:
</p>

<li>Warning status:70.</li>

<p>
Con esto, si el valor es mayor que 70 el indicador estatá en amarillo (ALERTA), y por debajo de 70 estatá en verde (NORMAL).
</p>
