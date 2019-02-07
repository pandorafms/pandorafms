<?php
/**
 * @package Include/help/es
 */
?>
<h1>Desconexiones programadas. Configuración de tiempo y fecha</h1>

<h2>Ejecución única</h2>

<p>
    El formato de la fecha debe ser año/mes/día y el del tiempo hora:minuto:segundo.
    Se pueden crear paradas planificadas en fechas pasadas, siempre que el administrador de <?php echo get_product_name(); ?> no haya deshabilitado esa opción.
</p>

<h2>Ejecución periódica</h2>

<h3>Mensual</h3>

<p>
    La parada se ejecutará cada mes, desde el día de inicio a la hora de inicio, hasta el día final a la hora final indicados.
    El formato del tiempo debe ser hora:minuto:segundo y el día de inicio no puede ser menor al día final.
    Para reflejar una parada que va más allá del último día del mes, habría que crear dos paradas, una que terminase el día 31 a las 23:59:59 y otra que empezase el día 1 a las 00:00:00.
</p>

<h3>Semanal</h3>

<p>
    La parada se ejecutará cada día seleccionado, desde la hora de inicio a la hora final indicadas.
    La hora de inicio no puede ser superior a la hora final.
    Para reflejar una parada que va más allá de la última hora del día, habría que crear dos paradas, una que terminase a las 23:59:59 y otra que empezase a las 00:00:00 del día siguiente.
</p>