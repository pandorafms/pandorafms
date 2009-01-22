<h1>Gestión de la configuración</h1>

Ésta herramienta se usa para diversos propoósitos:<br><br>

<ul>
<li> Copiar módulos y/o configuración de alertas de un agente a varios agentes destino. </li>
<li> Borrar módulos y/o configuración de alertas de un grupo de agentes. </li>
<li> Borrado completo de agentes seleccionándolos a la vez. </li>
</ul>

<h2>Copiar módulos / configuración de alertas</h2>

<ol>
<li> Seleccione el grupo de origen.</li>
<li> Seleccione el agente de origen.</li>
<li> Seleccione uno o más módulos del agente origen.</li>
<li> Seleccione los agentes de destino para la operación de copiado.</li>
<li> Seleccione los objetivos: Módulos para copiar sólo los módulos, Alertas para copiar sólo las alertas (si los agentes de destino no tienen un módulo con el mimsmo nombre definido en el agente origen, la herramienta no puede replicar la alerta). Podría seleccionar ambos para crear primero el módulo y replicar más tarde la alerta (si está definida).</li>
<li> Pulse el botón &laquo;Copiar módulos/alertas&raquo;.</li>
</ol>

<h2>Borrar módulos / configuración de alertas</h2>

<p>
Esto borrará todos los módulos/alertas de destino con el mismo nombre que el seleccionado en el agente origen o los módulos de origen. Todas las alertas asociadas a los módulos de origen se borrarán en el agente de destino si no tienen un módulo con el mismo nombre y alertas asociadas a ellos.
</p>

<ol>
<li> Seleccione el grupo de origen.</li>
<li> Seleccione el agente de origen.</li>
<li> Seleccione uno o más módulos del agente origen.</li>
<li> Seleccione los agentes de destino para la operación de borrado.</li>
<li> Seleccione los objetivos: módulos, alertas o ambos.</li>
<li> Pulse el botón &laquo;Borrar módulos/alertas&raquo;.</li>
</ol>

<h2>Borrar agentes</h2>

<p>
Esto borrará toda la ingormación del agente (módulos, alertas, eventos...)  de la lista de agentes seleccionads en la lista inferior.
</p>

<ol>
<li> Seleccione de la lista inferior los agentes destino para los que quiera aplicar la operación de borrado.</li>
<li> Pulse el botón &laquo;Eliminar agentes&raquo;.</li>
</ol>
