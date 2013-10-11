<?php
/**
 * @package Include/help/es
 */
?>
<h1>Critical Status</h1>

<h2>Módulos de tipo numérico</h2>

<p>
Estos campos tiene cuatro valores: mínimo, máximo, cadena e intervalo inverso. Configurandolos correctamente se conseguirá que algunos valores se muestren en estado crítico.
</p>

<p>
Para entender mejor estas opciones mejor se puede usar un ejemplo. El módulo de CPU estará siempre en verde en el estado del agente, asi que simplemente informará de un valor entre 0% y 100%. Si se quiere que el módulo de uso de la CPU se muestre como rojo (crítico) cuando se llegue al 90% de uso. Se debería configurar:
</p>

<li>Critical Min:90</li>
<li>Critical Max:0 (sin límite)</li>

<p>
Con esto, si el valor es mayor que 90 el indicador estatá en rojo (CRÍTICO), y por debajo de 70 estatá en verde (NORMAL).
</p>

<p>
Si activamos la casilla de intervalo inverso el módulo cambiará a estado Crítico cuando no se encuentre en el intervalo indicado. En el caso del ejemplo cuando esté por debajo de 90.
</p>

<h2>Módulos de tipo cadena</h2>

<p>
Estos campos tiene dos valores: Cadena e intervalo inverso. Configurandolos correctamente se conseguirá que algunos valores se muestren en estado de alerta.
</p>

<p>
En el campo Cadena podremos poner una expresión regular para que cuando el dato del módulo coincida con ella, el módulo pase a estado Crítico.
</p>

<li>String:(urgent|serious)</li>

<p>
Con esto, si en la cadena existe la palabra urgent o serious el módulo aparecerá en estado warning.
</p>

<p>
Si activamos la casilla de intervalo inverso el módulo cambiará a estado Crítico cuando no coincida con la expresión regular.
</p>
