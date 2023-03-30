<?php
/**
 * @package Include/help/en
 */
?>
<h1>GIS Map builder</h1>

<p>

Esta página muestra una lista de los mapas definidos, y le permite editar, borrar o ver cualquiera de ellos. También está instalado en está página el <strong>mapa por defecto</strong> de <?php echo get_product_name(); ?>.

</p>

Para crear una conexión de mapa se necesita una conexión a un servidor de mapas. Las conexiones las crea el Administrador en el menú <strong>Setup</strong>

<p>
</p>
<p>
Opciones:
</p>
<div>
<dl>
<dt>Nombre del Mapa</dt>
<dd>Haga click en el<strong>Nombre del Mapa</strong> que se corresponda con el mapa que quiere editar </dd>
<dt><?php html_print_image('images/eye.png', false, ['alt' => 'View']); ?>Vista</dt>
<dd>Haga click en icono de visualizar para <strong>visualizar</strong> el mapa.</dd>
<dt>Botón radio por defecto</dt>
<dd>Haga click en el <strong> botón radio </strong> que se corresponda con el mapa que quiere por defecto para instalar <strong>mapa por defecto</strong> </dd>
<dt><?php html_print_image('images/delete.svg', false, ['alt' => 'Delete']); ?> Eliminar</dt>
<dd>Haga click en el botón de eliminar para <strong>eliminar</strong> el mapa</dd>
<dt>Crear botón </dt>
<dd>Haga click en el Botón de Crear para <strong>crear</strong> un mapa nuevo</dd>
</dl>
</div>
