<?php
/**
 * @package Include/help/es
 */
?>
<h1>Configuración de las conexiones GIS</h1>

<p>
En esta sección, es donde el administrador puede configurar <strong>una conexión a un servidor de mapas GIS</strong>.
</p>

<h2>Tipos de conexión</h2>
<p>
Ahora mismo, <?php echo get_product_name(); ?> soporta tres tipos de conexiones:
OpenStreetMap, Google Maps e imágenes estáticas.
</p>
<h3>Open Street Maps</h3>
<p>
Para usar el conector de Open Street maps, puede montar su propio servidor  (lea más sobre esto en <a href="http://wiki.openstreetmap.org/wiki/Main_Page">http://wiki.openstreetmap.org/wiki/Main_Page</a> y <a href="http://wiki.openstreetmap.org/wiki/Mapnik">http://wiki.openstreetmap.org/wiki/Mapnik</a> como ejemplo de como hacer el render de sus propios "tiles"), tambien puede acceder a un servidor de mapas publico online de Openstreet maps de esta manera:<br />
</p>
<pre>
http://tile.openstreetmap.org/${z}/${x}/${y}.png
</pre>
<p>
Verifique los términos de <a href="http://wiki.openstreetmap.org/wiki/Licence">Licencia</a> de Openstreet maps antes de usarlo.
</p>
<h3>Google MAPS</h3>
<p>
Primero, necesta registrar y obtener una API KEY. Puede leer acerca de este proceso en <br/>
<a href="http://code.google.com/intl/en/apis/maps/signup.html">http://code.google.com/intl/en/apis/maps/signup.html</a></p>

<p>Una API de google maps es similar a:</p>
<pre>
ABQIAAAAZuJY-VSG4gOH73b6mcUw1hTfSvFQRXGUGjHx8f036YCF-UKjgxT9lUhqOJx7KDHSnFnt46qnj89SOQ
</pre>
<h3>Imagénes estáticas</h3>
<p>
También es posible usar una imagen estática (un PNG, por ejemplo) como el único origen del mapa. Para usarla, debe especificar la URL, la información de posición de la imagen y el alto y ancho de la imagen.
</p>
