<?php
/**
 * @package Include/help/es
 */
?>
<h1>Alertas</h1>

Asignar alertas a módulos
Añadir una alerta nueva a un módulo
Editar alertas de un módulo
<br /><br />
El siguiente paso después de añadir un agente, habiendo configurado sus módulos y definido las alertas, es asignar esas alertas al agente. Este paso es necesario para establecer las condiciones de la alerta en los casos deseados. Se realiza pulsando en el agente que se quiere configurar en la opción &laquo;Gestionar agentes&raquo; del menú &laquo;Administración&raquo;, o usando el modo de edición y seleccionando la solapa &laquo;Alertas&raquo;, desde la vista del agennte.
<br /><br />
Se deben rellenar los siguientes campos para asignar una alerta:
<ul>
	<li>Tipo de alerta: Éste puede seleccionarse de la lista de alertas que hayan sido previamente generadas.</li>
	<li>Valor máximo: Define el valor máximo para un módulo. Cualquier valor por encima de este umbral lanzará la alerta.</li>
	<li>Valor mínimo: Define el valor mínimo para un módulo. Cualquier valor por debajo de este umbral disparará la alerta. La pareja de &laquo;maximo&raquo; y &laquo;minimo&raquo; son los valores clave en la definición de una alerta, ya que definen en qué rango de valores se ha de disparar una alerta. Los valores de máximo y mínimo definen «lo aceptable», valores que Pandora FMS considera «válidos», fuera de estos valores, Pandora FMS lo considerará como alerta candidata a ser disparada.</li>
	<li>Texto de la alerta: En caso de módulos string, se puede definir una expresión regular o una subcadena para hacer disparar una alerta.</li>
	<li>Hora desde / Hora hasta: Esto define un rango de tiempo &laquo;válido&raquo; para lanzar alertas.</li>
	<li>Descripción: Describe la función de la alerta, y resulta útil para identificar la alerta entre otras en la vista general de alertas.</li>
	<li>Campo #1 (Alias, nombre): Define el valor para la variable "_ﬁeld1_".</li>
	<li>Campo #2 (Línea sencilla): Define el valor para la variable "_ﬁeld2_".</li>
	<li>Campo #3 (Texto completo)): Define el valor para la variable "_ﬁeld3_".</li>
	<li>Umbral de tiempo: Define el intervalo de tiempo en el cual se garantiza que una alerta no se va a disparar más veces del número establecido en <i>Numero máximo de alertas</i>. Pasado el intervalo definido, una alerta se recupera si llega un valor correcto, salvo que esté activado el valor <i>Recuperación de alerta</i>, en cuyo caso se recupera inmediatamente después de recibir un valor correcto independientemente del umbral.</li>
	<li>Número mínimo de alertas: Número mínimo de alertas que se necesitan para empezar a disparar una alerta. Funciona como un filtro, necesario para eliminar falsos positivos.</li>
	<li>Número máximo de alertas: Máximo número de alertas que se pueden enviar consecutivamente en el mismo intervalo de tiempo.</li>
	<li>Módulo asignado: Módulo que debe monitorizar la alerta.</li>
</ul>
Se pueden ver todas las alertas de un agente usando la solapa &laquo;Alertas&raquo;. A continuación se muestra un ejemplo:
<br /><br />
&laquo;Quiero disparar una alerta cuando se caiga XXX, y no quiero que me moleste de nuevo durante, al menos, una hora. Después de ese tiempo, si sigue caído, que se dispare otra alerta y que espere otra hora&raquo;.
<br /><br />
Debe establecer:
<ul>
	<li>Umbral de tiempo 3600 (1 hora).</li>
	<li>Número mínimo de alertas = 1.</li>
	<li>Número máximo de alertas = 1.</li>
</ul>
