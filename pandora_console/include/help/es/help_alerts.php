<?php
/**
 * @package Include/help/es
 */
?>
<h1>Alertas</h1>

<i>Asignar alertas a módulos</i><br>
<i>Añadir una alerta nueva a un módulo</i><br>
<i>Editar alertas de un módulo</i><br>

<p>El siguiente paso después de añadir un agente, habiendo configurado sus módulos y definido las alertas, es asignar esas alertas al agente. Este paso es necesario para establecer las condiciones de la alerta en los casos deseados. Se realiza pulsando en el agente que se quiere configurar en la opción &laquo;Gestionar agentes&raquo; del menú &laquo;Administración&raquo;, o usando el modo de edición y seleccionando la solapa &laquo;Alertas&raquo;, desde la vista del agennte.</p>
<p>Se deben rellenar los siguientes campos para asignar una alerta:</p><br>

    <li><b>Tipo de alerta:</b> Éste puede seleccionarse de la lista de alertas que hayan sido previamente generadas.</li>
    <li><b>Valor máximo:</b> Define el valor máximo para un módulo. Cualquier valor por encima de este umbral lanzará la alerta.</li>
    <li><b>Valor mínimo:</b> Define el valor mínimo para un módulo. Cualquier valor por debajo de este umbral disparará la alerta. La pareja de &laquo;maximo&raquo; y &laquo;minimo&raquo; son los valores clave en la definición de una alerta, ya que definen en qué rango de valores se ha de disparar una alerta. Los valores de máximo y mínimo definen «lo aceptable», valores que <?php echo get_product_name(); ?> considera «válidos», fuera de estos valores, <?php echo get_product_name(); ?> lo considerará como alerta candidata a ser disparada.</li>
    <li><b>Texto de la alerta:</b> En caso de módulos string, se puede definir una expresión regular o una subcadena para hacer disparar una alerta.</li>
    <li><b>Hora desde / Hora hasta:</b> Esto define un rango de tiempo &laquo;válido&raquo; para lanzar alertas.</li>
    <li><b>Descripción:</b> Describe la función de la alerta, y resulta útil para identificar la alerta entre otras en la vista general de alertas.</li>
    <li><b>Campo #1 (Alias, nombre):</b> Define el valor para la variable "_ﬁeld1_".</li>
    <li><b>Campo #2 (Línea sencilla):</b> Define el valor para la variable "_ﬁeld2_".</li>
    <li><b>Campo #3 (Texto completo)):</b> Define el valor para la variable "_ﬁeld3_".</li>
    <li><b>Umbral de tiempo:</b> Define el intervalo de tiempo en el cual se garantiza que una alerta no se va a disparar más veces del número establecido en Numero máximo de alertas</i>. Pasado el intervalo definido, una alerta se recupera si llega un valor correcto, salvo que esté activado el valor <i>Recuperación de alerta</i>, en cuyo caso se recupera inmediatamente después de recibir un valor correcto independientemente del umbral.</li>
    <li><b>Número mínimo de alertas:</b> Número mínimo de alertas que se necesitan para empezar a disparar una alerta. Funciona como un filtro, necesario para eliminar falsos positivos.</li>
    <li><b>Número máximo de alertas:</b> Máximo número de alertas que se pueden enviar consecutivamente en el mismo intervalo de tiempo.</li>
    <li><b>Módulo asignado:</b> Módulo que debe monitorizar la alerta.</li>
<p>Se pueden ver todas las alertas de un agente usando la solapa &laquo;Alertas&raquo;. A continuación se muestra un ejemplo:<br>

&laquo;Quiero disparar una alerta cuando se caiga XXX, y no quiero que me moleste de nuevo durante, al menos, una hora. Después de ese tiempo, si sigue caído, que se dispare otra alerta y que espere otra hora&raquo;.</p>

<p>Debe establecer:</p>
<ul>
    <li>Umbral de tiempo 3600 (1 hora).</li>
    <li>Número mínimo de alertas = 1.</li>
    <li>Número máximo de alertas = 1.</li>
</ul>
