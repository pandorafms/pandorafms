<?php
/**
 * @package Include/help/es
 */
?>
<h1>Posprocesado</h1>

El posprocesado es un valor numérico usado después de obtener el dato para posprocesar dicho dato de forma numérica en una multiplicación. Por ejemplo, datos con un valor de 1000 con un valor de Posprocesado de 1024 darán como resultado un valor final de 1024000. Esto es útil para normalizar los datos, convertir entre unidades, etc. Esto también se puede usar para dividir, usando un valor de multiplicador inferior a 1, como, por ejemplo, 0.001 que dividirá el valor actual por 1000.

Algunos ejemplos interesantes, son por ejemplo:<br>
<li>Convertir timeticks (SNMP) a Dias: 0.000000115740741
<li>Convertir bytes en MegaBytes: 0,00000095367432
<li>Convertir bytes en GigaBytes: 0,00000000093132
<li>Convertir megabits en megabytes: 0,125


<br /><br />
Un valor vacío o &laquo;0&raquo; desactivará el uso del posprocesado (predeterminado).
