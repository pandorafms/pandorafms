<?php
/**
 * @package Include/help/es
 */
?>
<h1>Base de datos de histórico</h1>

Una base de datos de histórico es una base de datos a la que se mueven datos antiguos de módulos para mejorar la respuesta de la base de datos principal de <?php echo get_product_name(); ?>. Estos datos seguirán estando disponibles para la consola de <?php echo get_product_name(); ?> de forma transparente al ver informes, gráficas de módulos etc. 
<br><br>
<b>CONFIGURANDO UNA BASE DE DATOS DE HISTÓRICO</b>
<br><br>
Para configurar una base de datos de histórico siga los siguientes pasos: 
<br><br>
<ol>
<li>Cree la nueva base de datos de histórico.  
<br><br>
<li>Cree las tablas necesarias en la nueva base de datos. Puede utilizar el script DB Tool incluido en la consola de <?php echo get_product_name(); ?>:  
<br><br>
<i>cat pandoradb.sql | mysql -u user -p -D history_db</i>
<br><br>
<li>Dar los permisos necesarios para que el usuario de <?php echo get_product_name(); ?> tenga acceso a la base de datos de histórico
<br><br>
<i>Mysql Example: GRANT ALL PRIVILEGES ON pandora.* TO 'pandora'@'IP' IDENTIFIED BY 'password'</i>
<br><br>
<li>En la consola de <?php echo get_product_name(); ?> vaya a Setup->History database y configure el host, port, database name, user y password de la nueva base de datos.
</ol>
<br><br>
<?php html_print_image('images/help/historyddbb.png', false, ['width' => '550px']); ?>
<br><br>
Los datos con más días de antigüedad se moverán a la base de datos de histórico en bloques de Step filas, esperando Delay segundos entre un bloque y el siguiente para evitar sobrecargas. 
<br><br>
Aquí se detallan los campos a rellenar: 
<br><br>
<ol>
   <b>Enable history database:</b> Permite utilizar la funcionalidad de base de datos histórica. 
<br><br>
   <b>Host:</b> Nombre de host de la base de datos histórica.  
<br><br>
    <b>Port:</b> Puerto de conexión de la base de datos histórica.  
<br><br>
   <b>Database name:</b> Nombre de la base de datos histórica.  
<br><br>
   <b>Database user:</b> Usuario de la base de datos histórica. 
<br><br>
   <b>Database password:</b> Password de la base de datos histórica. 
<br><br>
   <b>Days:</b> A partir de cuantos días los datos serán transferidos a la base de datos histórica.  
<br><br>
   <b>Step:</b> Mecanismo para la transferencia de datos (similar un buffer de datos) a la base de datos histórica. Cuanto menor sea menos eficiente será la transferencia pero afectará menos al rendimiento de la base de datos principal. 
<br><br>
   <b>Delay:</b>  tiempo de espera entre transferencias de datos entre la base de datos principal y la histórica. 
<br><br>
</ol> 
