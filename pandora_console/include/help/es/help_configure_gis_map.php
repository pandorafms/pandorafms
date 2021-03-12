<?php
/**
 * @package Include/help/en
 */
?>
<h1>Configuración del Mapa GIS </h1>

<p>
Esta página es el lugar para configurar un Mapa GIS.
</p>
<h2>Nombre del Mapa</h2>
<p>
Cada mapa tiene un nombre descriptivo que se utiliza para reconocer el mapa dentro de <?php echo get_product_name(); ?>.

</p>
<h2>Seleccionar Conexiones</h2>
<p>
El primer paso es seleccionar la principal <strong>conexión </strong> empleada en este Mapa GIS. Al menos una conexión debe ser seleccionada para configurar el MAPA GIS, pero es posible añadir más presionando el icono(Add) <?php html_print_image('images/add.png', false, ['alt' => 'Add']); ?>

</p>
<p>
Cuando se configura la primera conexión, <?php echo get_product_name(); ?> te pregunta si quiere utilizar los valores por defecto de la conexión para el mapa, para evitar tener que escribir de nuevo toda la información. También, si la conexión por defecto del mapa se ha cambiado (utilizando el radio button), <?php echo get_product_name(); ?> te preguntará de nuevo si quiere usar los valores de la nueva conexión por defecto.

</p>
<h2>Parámetros del Mapa</h2>
<p>
Una vez hecha la selección de la conexión (o conexiones), existe la posibilidad de cambiar los parámetros que fueron fijados para la conexión y personalizar este mapa. Es posible configurar el  <strong> centro </strong> del mapa (el lugar donde aparecerá cuando se abra el mapa), el nivel <strong> de zoom </strong> por defecto (el nivel de zoom a fijar cuando se abra el mapa), y la <strong> posición por defecto</strong>(el lugar donde colocar los agentes que no tienen información de posición).
 
</p>
<p>
<strong>Opciones</strong>
</p>
<div>
<dl>
<dt>Nombre del Mapa</dt>
<dd>Pon <strong>el nombre del mapa</strong>. Usa nombres cortos y descriptivos</dd>
<dt>Grupo</dt>
<dd>Fija <strong>el grupo </strong> que tiene el mapa para propósitos ACL </dd>
<dt>Zoom por defecto </dt>
<dd>Configura <strong>el zoom por defecto</strong> del mapa, cuando el mapa esté desplegado este es el nivel zoom que está configurado...</dd>
<dt>Centrar longitud</dt>
<dt>Centrar latitud</dt>
<dt>Centrar altitud</dt>
<dd>Configurar <strong>Longitud</strong>, <strong>Latitud</strong> y <strong>Altitud</strong> para el  <strong>centro</strong> del mapa. Cuando el mapa está desplegado, esta la vista central </dd>
<dt>Longitud por defecto</dt>
<dt>Latitud por defecto</dt>
<dt>Altitud por defecto</dt>
<dd>Fija la<strong>Longitud</strong>, <strong>Latitud</strong> y <strong>Altitud</strong> para la<strong>posición por defecto</strong> del mapa. Este es el lugar donde se colocan todos los agentes <strong>sin</strong> información sobre posición.</dd>
</dl>
</div>
<h2>Configuración de capas</h2>
<p>

Cada mapa tiene una o más capas <sup><span class="font_75p">1</span></sup> para mostrar los agentes. Cada capa puede mostrar los agentes de un
 <strong>grupo</strong> y/o una <strong>lista de agentes</strong>. De este modo resulta sencillo fijar los agentes que se mostrarán en cada nivel.

</p>
<hr/>
<sup><span class="font_75p">1</span></sup> <span class="font_85p">El mapa por defecto puede tener 0 capas y será el utilizado en la vista GIS del agente y sólo usa una capa con el nombre del agente.</span>



