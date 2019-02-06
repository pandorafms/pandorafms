<?php
/*
 * @package Include/help/es
 */
?>

<h1>Gestion de servidores</h1>

<p>Los servidores de <?php echo get_product_name(); ?> son los elementos encargados de realizar las comprobaciones existentes. Ellos las verifican y cambian el estado de las mismas en funcion de los resultados obtenidos. Tambien son los encargados de disparar las alertas que se establezcan para controlar el estado de los datos.</p>

<p>El servidor de datos de <?php echo get_product_name(); ?> puede trabajar con alta disponibilidad y/o balanceo de carga. En una arquitectura muy grande, se pueden usar varios servidores de <?php echo get_product_name(); ?> a la vez, para poder manejar grandes volumenes de informacion distribuida por zonas geograficas o funcionales.</p>

<p>Los servidores de <?php echo get_product_name(); ?> están siempre en funcionamiento y verifican permanentemente si algún elemento tiene algún problema. Si existe alguna alerta asociada al problema, esta ejecuta la acción definida, como por ejemplo enviar un SMS, un correo electrónico, o activar la ejecución de un script.</p>
<ul>
<li type="circle">Servidor datos</li>
<li type="circle">Servidor de red</li>
<li type="circle">Servidor SNMP</li>
<li type="circle">Servidor WMI</li>
<li type="circle">Servidor reconocimiento</li>
<li type="circle">Servidor complementos</li>
<li type="circle">Servidor de prediccion</li>
<li type="circle">Servidor de pruebas WEB</li>
<li type="circle">Servidor de exportacion</li>
<li type="circle">Servidor de inventario</li> 
</ul>
