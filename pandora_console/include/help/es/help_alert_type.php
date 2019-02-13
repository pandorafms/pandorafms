<?php
/**
 * @package Include/help/es
 */
?>
<h1>Tipo de alerta</h1>

Existen algunas alertas predefinidas, las cuales es muy probable que tenga que configurar en caso de que su sistema no proporcione los comandos necesarios para ejecutarlas. El equipo de desarrollo ha probado estas alertas con Red Hat Enterprise Linux (RHEL), CentOS, Debian y Ubuntu Server.
<ul>
    <li><b>eMail</b>: Envía un correo-e desde el servidor de <?php echo get_product_name(); ?>. Usa el sendmail local. Si instaló otro tipo de servidor de correo o no tiene uno, debería instalar y configurar sendmail o cualquiera equivalente (y comprobar la sintaxis) para poder usar este servicio. <?php echo get_product_name(); ?> depende de las herramientas del sistema para ejecutar prácticamente cada alerta, será necesario comprobar que esos comandos funcionan correctamente en su sistema.</li>
    <li><b>Internal audit</b>: Es la única alerta &laquo;interna&raquo;, escribe un incidente en el sistema de auditoría interno de <?php echo get_product_name(); ?>. Esto se almacena en la base de datos de <?php echo get_product_name(); ?> y se puede revisar con el visor de auditoría de <?php echo get_product_name(); ?> desde la consola Web.</li>
    <li><b><?php echo get_product_name(); ?> Alertlog</b>: Guarda información acerca de la alerta en un fichero de texto (.log). Use este tipo de alerta para generar ficheros log usando el formato que necesite. Para ello, deberá modificar el comando para que use el formato y fichero que usted quierd.a Note que <?php echo get_product_name(); ?> no gestiona rotación de ficheros, y que el proceso del servidor de <?php echo get_product_name(); ?> que ejecuta la alerta deberá poder acceder al fichero log para escribir en él.</li>
    <li><b><?php echo get_product_name(); ?> Event</b>: Esta alerta crea un evento especial en el gestor de eventos de <?php echo get_product_name(); ?>.</li> 
</ul>
Estas alertas son predefinidas y no se pueden borrar, no obstante el usuario puede definir alertas nuevas que usen comandos personalizados y añadirlas al Gestor de alertas.