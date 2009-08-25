<?php
/**
 * @package Include/help/es
 */
?>
<h1>Desconexiones programadas</h1>

<p>
Esta herramienta se usa para planear periodos de desconexión de la monitorización. Es útil si sabe, por ejemplo, que un grupo de sistemas se desconectará a una hora específica. Esto ayuda a evitar falsas alarmas.
</p>
<p>
Es muy fácil de configurar, especifique la fecha y hora de inicio de la desconexión programada y la fecha hora del final. Después de rellenar los primeros campos, debe guardar la Desconexión programada y editarla, para establecer los agentes que se van a desconectar. También pued eeditar el resto de campos al editar la Desconexión programada.
</p>
<p>
Cuando una desconexión programada se inicia, Pandora FMS automáticamente desactiva todos los agentes asignados a esa desconexión y no se procesa ningún dato ni alerta. Cuando la desconexión finaliza, Pandora FMS activará todos los agentes asignados a la desconexión.No puede borrar o modificar una instancia de desconexión cuando está activada, debe esperar a que finalice anes de hacer cualquier otra cosa en esa instancia. Por supuesto puede activar manualmente cualquier agente usando el diálogo de configuración del agente.
</p>
