<?php
/**
 * @package Include/help/en
 */
?>
<h1>Vista del estado del Agente</h1>

Los colores posibles de los valores de los <b>módulos</b> son:
<br><br>
<b>
Número de módulos

: <span class="red">Número de módulos críticos</span>
: <span class="yellow">Número de modulos de alerta</span>
: <span class="green">Número de módulos normales</span>
: <span class="grey">Número de módulos caídos</span>
</b>
<br><br>

Los valores posibles del <b>estado de un agente </b> son:

<br><br>

<table width="750px">
<tr>
	<td class="f9i"><img src="../images/status_sets/default/module_critical.png" title="At least one monitor fails" alt="At least one monitor fails"><img src="../images/status_sets/faces/module_critical.png" title="At least one monitor fails" alt="At least one monitor fails"></td><td>Al menos un monitor falla</td>
	<td class="f9i"><img src="../images/status_sets/default/module_warning.png" title="Change between Green/Red state" alt="Change between Green/Red state"><img src="../images/status_sets/faces/module_warning.png" title="Change between Green/Red state" alt="Change between Green/Red state"></td><td>Cambia entre el estado Verde/Rojo</td>
	<td class="f9i"><img src="../images/status_sets/default/agent_ok.png" title="All Monitors OK" alt="All Monitors OK"><img src="../images/status_sets/faces/agent_ok.png" title="All Monitors OK" alt="All Monitors OK"></td><td>Todos los monitores están OK</td>

</tr><tr>
	<td class="f9i"><img src="../images/status_sets/default/agent_no_monitors.png" title="Agent without monitors" alt="Agent without monitors"><img src="../images/status_sets/faces/agent_no_monitors.png" title="Agent without monitors" alt="Agent without monitors"></td><td>Agente sin monitores</td>
	<td class="f9i"><img src="../images/status_sets/default/agent_no_data.png" title="Agent without data" alt="Agent without data"><img src="../images/status_sets/faces/agent_no_data.png" title="Agent without data" alt="Agent without data"></td><td>Agente sin datos</td>
	<td class="f9i"><img src="../images/status_sets/default/agent_down.png" title="Agent down" alt="Agent down"><img src="../images/status_sets/faces/agent_down.png" title="Agent down" alt="Agent down"></td><td>Agente caído</td>
</tr>
</table>

<br><br>
Los valores posibles del <b>estado de alerta </b> son:

<br><br>
<table width="450px">
<tr>
	<td class="f9i"><img src="../images/status_sets/default/alert_fired.png" title="Alert fired" alt="Alert fired"><img src="../images/status_sets/faces/alert_fired.png" title="Alert fired" alt="Alert fired"></td><td>Alerta disparada</td>
	<td class="f9i"><img src="../images/status_sets/default/alert_disabled.png" title="Alert disabled" alt="Alert disabled"><img src="../images/status_sets/faces/alert_disabled.png" title="Alert disabled" alt="Alert disabled"></td><td>Alerta desactivada</td>
	<td class="f9i"><img src="../images/status_sets/default/alert_not_fired.png" title="Alert not fired" alt="Alert not fired"><img src="../images/status_sets/faces/alert_not_fired.png" title="Alert not fired" alt="Alert not fired"></td><td>Alerta no disparada</td>

</tr>
</table>
