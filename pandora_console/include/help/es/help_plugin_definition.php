<?php
/**
 * @package Include/help/es
 */
?>
<h1>Registro de complementos</h1>

A diferencia del resto de componentes, de forma predeterminada <?php echo get_product_name(); ?> no incluye ningún complemento pre-configurado, por lo tanto primero se deberá crear y configurar un complemento, para después añadírselo al módulo de un agente. No obstante <?php echo get_product_name(); ?> sí incluye complementos en los directorios de instalación, pero como ya se ha dicho no están configurados en la base de datos. 
<br><br>
Para añadir un complemento existente a <?php echo get_product_name(); ?>, ir a la sección de administración de la consola, y en ella, pulsar sobre Manage servers; después pulsar Manage plugins: 
<br><br>
Una vez en la pantalla de gestión de los complementos, pulsar el botón Create para crear un nuevo complemento, ya que no habrá ninguno.  
<br><br>
Rellenar el formulario de creación de complementos con los siguientes datos: 
<br><br>
<?php html_print_image('images/help/plugin1.png', false, ['width' => '550px']); ?>
<br><br>
<b>Name</b><br>
Nombre del plugin, en este caso Nmap.
<br><br>
<b>Plugin type </b><br>
Hay dos tipos de complementos, los estándar (standard) y los de tipo Nagios. Los complementos estándar son scripts que ejecutan acciones y admiten parámetros. Los complementos de Nagios son, como su nombre indica, complementos de Nagios que se pueden usar en <?php echo get_product_name(); ?>. La diferencia estriba principalmente en que los plugins de nagios devuelven un error level para indicar si la prueba ha tenido éxito o no.
<br><br>
Si quiere usar un plugin de tipo nagios y quiere obtener un dato, no un estado (Bien/Mal), puede utilizar un plugin de tipo nagios en el modo "Standard". 
<br><br>
En este caso (para el plugin de ejemplo, NMAP), seleccionaremos Standard. 
<br><br>
<b>Max. timeout</b><br>

Es el tiempo de expiración del complemento. Si no se recibe una respuesta en ese tiempo, se marcará el módulo como desconocido y no se actualizará su valor. Este es un factor muy importante a la hora de implementar monitorización con plugins, ya que si el tiempo que tarda en ejecutar el plugin es mayor que este numero, nunca podremos obtener valores con él. Este valor siempre debe ser mayor que el tiempo que tarde normalmente en devolver un valor el script/ejecutable usado como plugin. Si no se indica nada, se utilizará el valor indicado en la configuracion como plugin_timeout. 
<br><br>
En este caso, escribimos 15.
<br><br>
<b>Description</b><br>

Descripción del complemento. Escribir una breve descripción, como por ejemplo: Test # UDP open ports y si es posible especificar la interfaz completa de parámetros como ayuda para que alguien que revise posteriormente la definición del plugin sepa que parámetros acepta. 
<br><br>
<b>Plug-in command</b><br>

Es la ruta a donde está el comando del complemento. De forma predeterminada, si la instalación ha sido estándar, estarán en el directorio /usr/share/pandora_server/util/plugin/. Aunque puede ser cualquier ruta del sistema. Para este caso, escribir /usr/share/pandora_server/util/plugin/udp_nmap_plugin.sh en el campo.
<br><br>
El servidor de <?php echo get_product_name(); ?> ejecutará ese script, por lo que éste debe tener permisos de acceso y de ejecución sobre él. 
<br><br>
<b>Plug-in parameters</b><br>

Una cadena con los parámetros del plugin, que irán tras el comando y un espacio en blanco. Este campo acepta macros tales como _field1_ _field2_ ... _fieldN_. 
<br><br>
<b>Parameters macros</b><br>

Es posible agregar macros ilimitadas para usarlas en el campo de los parámetros del plugin. Estas macros aparecerán como campos de texto en la configuración del módulo. 
<br><br>
Cada macro tiene 3 campos: 
<br><br>
    <i>Description:</i> Una cadena corta descriptiva de la macro. Será la etiqueta que aparecerá junto al campo en el formulario. <br>
    <i>Default value:</i> Valor asignado al campo por defecto. <br>
    <i>Help:</i> Un texto explicativo de la macro.  <br>
<br><br>
Ejemplo de la configuración de macros:  
<br><br>
<?php html_print_image('images/help/plugin2.png', false, ['width' => '550px']); ?>
<br><br>
Ejemplo de esta misma macro en el editor del módulo: 
<br><br>
<?php
html_print_image('images/help/plugin3.png', false, ['width' => '550px']);
