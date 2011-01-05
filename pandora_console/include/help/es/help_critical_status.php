<?php
/**
 * @package Include/help/es
 */
?>
<h1>Critical Status</h1>

<p>
Estos campos tiene dos valores, mínimo y máximo. Configurandolos correctamente se conseguirá que algunos valores se muestren en estado crítico.
</p>

<p>
Para entender mejor estas opciones mejor se puede usar un ejemplo. El módulo de CPU estará siempre en verde en el estado del agente, asi que simplemente informará de un valor entre 0% y 100%. Si se quiere que el módulo de uso de la CPU se muestre como rojo (crítico) cuando se llegue al 90% de uso. Se debería configurar:
</p>

<li>Critical status:90.</li>

<p>
Con esto, si el valor es mayor que 90 el indicador estatá en rojo (CRÍTICO), y por debajo de 70 estatá en verde (NORMAL).
</p>

