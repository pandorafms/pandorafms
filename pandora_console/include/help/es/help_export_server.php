<?php
/**
 * @package Include/help/es
 */
?>
<h1>Servidor de exportación</h1>

<p>La versión Enterprise de <?php echo get_product_name(); ?> implementa, mediante el export server, un mecanismo de escalado de datos que permite virtualmente una implantación distribuida capaz de monitorizar un número ilimitado de información, siempre que se diseñe adecuadamente y se disgregue en diferentes perfiles de información.</p>

<ul>
<li type="circle">Nombre: El nombre del servidor de <?php echo get_product_name(); ?>.</li>
<li type="circle">Servidor de exportacion: Combo donde se elige la instancia del servidor de export server que se usara para exporta los datos.</li>
<li type="circle">Prefijo: Prefijo que se usa para a&ntilde;adir al nombre del agente que envía los datos. Cuando se reenvían datos de un agente llamado &#34;Farscape&#34;, por ejemplo y su prefijo en el servidor de exportación es &#34;EU01&#34;, los datos del agente reenviado seran vistos en el servidor de destino con el nombre de agente EU01-Farscape.</li>
<li type="circle">Interval: Se define el intervalo de tiempo cada cuantos segundos se quieren enviar los datos que haya pendientes.</li> 
<li type="circle">Directorio destino: Sera el directorio de destino (usado para SSH o FTP unicamente) donde dejara los datos remotamente.</li>
<li type="circle">Dirección: Dirección del servidor de datos que va a recibir los datos.</li>
<li type="circle">Modo de transferencia: Modo de transferencia. Puedes elegir entre: Local, SSH, FTP y Tentacle.</li>
<li type="circle">Usuario: Usuario de FTP.</li>
<li type="circle">Password: Password  de usuario FTP.</li>
<li type="circle">Puerto: Puerto usado en la transferencia de ficheros. Para Tentacle es el puerto 41121 el puerto por defecto.</li>
<li type="circle">Opciones extra: Campo para opciones adicionales como las usadas por Tentacle necesitadas para utilizar certificados.</li>
</ul>

