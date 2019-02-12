<?php
/*
    Include package help/es
*/
?>

<h1>El intervalo de módulo como factor</h1>
<h2>Dónde se cambia el intervalo</h2>
<p>
El intervalo de los módulos de tipo data <b>se cambia en la definición del módulo en 
el fichero de configuración del agente</b>.<br><br>
El token de configuración del intervalo es <b>module_interval</b>.<br><br>
Por ejemplo:<br><br>
<i>
module_begin<br>
module_name Ejemplo de modulo<br>
module_type generic_data<br>
module_exec echo 100<br>
module_interval 2<br>
module_description Este modulo devuelve siempre 100<br>
module_end<br>
</i>
</p>
<h2>Cómo se define el intervalo en este tipo de módulos</h2>
<p> En los <b>módulos de tipo data</b>, el intervalo <b>no se define en segundos</b>.<br><br>

intervalo se calcula como un <b>factor multiplicador</b> para el intervalo del agente.<br><br>

Por ejemplo, si el agente tiene intervalo 300 (5 minutos), y se quiere un módulo que 
sea procesado sólo cada 15 minutos, se debe establecer un intervalo de módulo 3<br><br>

Este módulo será procesado cada 300seg x 3 = 900seg (15 minutos).
</p>
