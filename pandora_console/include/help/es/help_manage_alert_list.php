<?php
/**
 * @package Include/help/es
 */
?>
<h1>Alerts</h1>

<p>
Las Alertas en <?php echo get_product_name(); ?> reacionan a un valor "fuera de rango" de un módulo. La alerta consiste en enviar un e-mail o un SMS al administrador, enviando un trap SNMP, escribir el indcidenete en el log del sistema en el fichero de log de <?php echo get_product_name(); ?>, etc. Basicamente, una alerta puede ser cualquier cosa que pueda ser disparada por un script configurado en el Sistema Operativo donde los servidores de <?php echo get_product_name(); ?> se ejecutan.
</p>

<p>
Cuando una alerta es creada los siguiente campos deben de rellenarse:
</p>

<ul>
    <li><b>Agent name:</b> El nombre del agente asociado a la alarma.</li>
    <li><b>Module:</b> La alerta recogerá el valor del módulo y comprobará si está "fuera de rango". En caso afirmativo creará un evento (sending, e-mail, etc.).</li>
    <li><b>Template:</b> Alertas con todos los parámetros predefinidos. Son usadas para hacer más sencilla la gestión de las alertas porel administrador.</li>
    <li><b>Action:</b> Permite elegir entre todas las alertas que están configuradas. La acción seleccionada será añadida a la acción definida por el template.</li>
    <li><b>Threshold:</b> Define el intervalo de tiempo en el que se garantiza que una alerta no va a ser disparada más veces que el número fijado en el número máximo de alertas.
    
</ul>
