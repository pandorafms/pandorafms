<?php
/**
 * @package Include/help/es
 */
?>
<h1>Warning Status</h1>

<h2>Módulos de tipo numérico</h2>

<p>
Estos campos tiene tres valores: Mínimo, Máximo e intervalo inverso. Configurandolos correctamente se conseguirá que algunos valores se muestren en estado de alerta.
</p>

<p>
Para entender mejor estas opciones mejor se puede usar un ejemplo. El módulo de CPU estará siempre en verde en el estado del agente, asi que simplemente informará de un valor entre 0% y 100%. Si se quiere que el módulo de uso de la CPU se muestre como amarillo (alerta) cuando se llegue el 70% de uso. Se debería configurar:
</p>

<li>Warning Min:70</li>
<li>Warning Max:0 (sin límite)</li>

<p>
Con esto, si el valor es mayor que 70 el indicador estatá en amarillo (ALERTA), y por debajo de 70 estatá en verde (NORMAL).
</p>

<p>
Si activamos la casilla de intervalo inverso el módulo cambiará a estado Warning cuando no se encuentre en el intervalo indicado. En el caso del ejemplo cuando esté por debajo de 70.
</p>

<h2>Módulos de tipo cadena</h2>

<p>
Estos campos tiene dos valores: Cadena e Intervalo inverso. Configurandolos correctamente se conseguirá que algunos valores se muestren en estado de alerta.
</p>

<p>
En el campo Cadena podremos poner una expresión regular para que cuando el dato del módulo coincida con ella, el módulo pase a estado Warning.
</p>

<li>String:(error|fail)</li>

<p>
Con esto, si en la cadena existe la palabra error o fail el módulo aparecerá en estado warning.
</p>

<p>
Si activamos la casilla de intervalo inverso el módulo cambiará a estado Warning cuando no coincida con la expresión regular.
</p>
