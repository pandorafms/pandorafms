<?php
/* Include package help/es
*/
?>

<h1>El intervalo de módulo como factor</h1>
<p> En los <b>módulos de tipo data</b>, el intervalo <b>no se define en segundos</b>.<br><br>

intervalo se calcula como un <b>factor multiplicador</b> para el intervalo del agente.<br><br>

Por ejemplo, si el agente tiene intervalo 300 (5 minutos), y se quiere un módulo que 
sea procesado sólo cada 15 minutos, se debe establecer un intervalo de módulo 3<br><br>

This module will be preocessed every 300sec x 3 = 900sec (15 minutes).
</p>
