<?php
/*
 * @package Include/help/es/
 */
?>


<style type="text/css">

* {
    font-size: 1em;
}

img.hlp_graphs {
    width: 80%;
    max-width: 800px;
    min-width: 400px;
    margin: 15px auto;
    display: block;
}

ul.clean {
    list-style-type: none;
}

b {
    font-size: 0.90em!important;
}
dl dt {
    margin-top: 1em;
    font-weight: bold;
}
dl {
    margin-bottom: 2em;
}

div.img_title {
    text-align: center;
    font-size: 0.8em;
    font-style: italic;
    width: 100%;
    margin-top: 4em;
}
</style>

<body class="hlp_graphs">
<h1>Interpretar las gr&aacute;ficas en <?php echo get_product_name(); ?></h1>


<p>Las gr&aacute;ficas en <?php echo get_product_name(); ?> representan los valores que un m&oacute;dulo ha tenido a lo largo de un período.</p>
<p>Debido a la gran cantidad de datos que <?php echo get_product_name(); ?> almacena, se ofrecen dos tipos diferentes de funcionalidad:</p>


<h2>Gr&aacute;ficas Normales</h2>

<img class="hlp_graphs" src="<?php echo $config['homeurl']; ?>images/help/chart_normal_sample.png" alt="regular chart sample" />

<h4>Caracter&iacute;sticas generales</h4>
<p>Son gr&aacute;ficas que representan la informaci&oacute;n almacenada por el m&oacute;dulo a un nivel b&aacute;sico.</p>
<p>Nos permite ver una aproximaci&oacute;n de los valores en los que oscila nuestro m&oacute;dulo.</p>
<p>Dividen los datos del m&oacute;dulo en <i>cajas</i> de tal manera que se representa una muestra de los valores del m&oacute;dulo, <b>no se pintan todos los valores</b>. Esta carencia se complementa dividiendo la vista en tres gr&aacute;ficas, <b>Max</b> (valores m&aacute;ximos), <b>min</b> (valores m&iacute;nimos) y <b>avg</b> (valores promedios)</p>

<ul class="clean">
<li><b>Ventajas</b>: Se generan muy r&aacute;pidamente sin consumir apenas recursos.</li>
<li><b>Inconvenientes</b>: La informaci&oacute;n que proveen es aproximada. Los estados de los monitores que representan se calculan en base a eventos.</li>



<h4>Opciones de visualizaci&oacute;n</h4>

<dl>
<dt>Tiempo de refresco</dt>
<dd>Tiempo en que se pintar&aacute; la gr&aacute;fica de nuevo.</dd>

<dt>Avg. Only</dt>
<dd>Solo se pintar&aacute; la gr&aacute;fica de promedios.</dd>

<dt>Fecha de inicio</dt>
<dd>Fecha hasta la que se pintar&aacute; la gr&aacute;fica.</dd>

<dt>Tiempo de inicio</dt>
<dd>Hora minutos y segundos hasta los que se pintar&aacute; la gr&aacute;fica.</dd>

<dt>Factor de zoom</dt>
<dd>Tama&ntilde;o del visor de la gr&aacute;fica, multiplicativo.</dd>

<dt>Rango de tiempo</dt>
<dd>Establece el per&iacute;odo de tiempo desde el que se recoger&aacute;n los datos.</dd>

<dt>Mostrar eventos</dt>
<dd>Muestra puntos indicadores con la informaci&oacute;n de eventos en la parte superior.</dd>

<dt>Mostrar alertas</dt>
<dd>Muestra puntos indicadores con la informaci&oacute;n de alertas disparadas en la parte superior.</dd>

<dt>Mostrar percentil</dt>
<dd>Agrega una gr&aacute;fica que indica la l&iacute;nea del percentil (configurable en opciones visuales generales de <?php echo get_product_name(); ?>).</dd>

<dt>Comparaci&oacute;n de tiempo (superpuesto)</dt>
<dd>Muestra superpuesta la misma gr&aacute;fica, pero en el per&iacute;odo anterior al seleccionado. Por ejemplo, si solicitamos un per&iacute;odo de una semana y activamos esta opci&oacute;n, la semana anterior a la elegida tambi&eacute;n se mostrar&aacute; superpuesta.</dd>

<dt>Comparaci&oacute;n de tiempo (independiente)</dt>
<dd>Muestra la misma gr&aacute;fica, pero en el per&iacute;odo anterior al seleccionado, en un area independiente. Por ejemplo, si solicitamos un per&iacute;odo de una semana y activamos esta opci&oacute;n, la semana anterior a la elegida tambi&eacute;n se mostrar&aacute;.</dd>

<dt>Mostrar gr&aacute;fica de desconocidos</dt>
<dd>Muestra cajas en sombreado gris cubriendo los per&iacute;odos en que <?php echo get_product_name(); ?> no puede garantizar el estado del m&oacute;dulo, ya sea por p&eacute;rdida de datos, desconexi&oacute;n de un agente software, etc.</dd>

<dt>Mostrar gr&aacute;fica de escala completa (TIP)</dt>
<dd>Cambia el modo de pintado de "normal" a "TIP". En este modo, las gr&aacute;ficas mostrar&aacute;n datos reales en vez de aproximaciones, por lo que el tiempo que emplear&aacute;n para su generaci&oacute;n ser&aacute; mayor. Podr&aacute; encontrar informaci&oacute;n m&aacute;s detallada de este tipo de gr&aacute;ficas en el siguiente apartado.</dd>

</dl>




<br />
<br />


<h2>Gr&aacute;ficas TIP</h2>
<img class="hlp_graphs "src="<?php echo $config['homeurl']; ?>images/help/chart_tip_sample.png" alt="TIP chart sample" />

<h4>Caracter&iacute;sticas generales</h4>
<p>Son gr&aacute;ficas que representan <b>datos reales</b>.</p>
<p>Nos muestra una representaci&oacute;n veraz de los datos reportados por nuestro m&oacute;dulo.</p>
<p>Al ser datos reales no ser&aacute; necesario complementar la informaci&oacute;n con gr&aacute;ficas extra (avg,min,max).</p>
<p>El c&aacute;lculo de per&iacute;odos en estado desconocido se apoya en eventos, tal y como funcionan las gr&aacute;ficas normales, pero se complementa con una detecci&oacute;n extra en caso de haberlos.</p>
<p>Ejemplos de resoluci&oacute;n ofrecidas por metodos normal y TIP:</p>

<div class="img_title">Ejemplo de gr&aacute;fica normal en intervalo desconocido</div>
<img class="hlp_graphs "src="<?php echo $config['homeurl']; ?>images/help/chart_normal_detail.png" alt="TIP chart detail" />

<div class="img_title">Ejemplo de gr&aacute;fica TIP en intervalo desconocido</div>
<img class="hlp_graphs "src="<?php echo $config['homeurl']; ?>images/help/chart_tip_detail.png" alt="TIP chart detail" />

<br />

<ul class="clean">
<li><b>Ventajas</b>: Los datos representados son datos reales. Es la forma m&aacute;s realista de revisar los datos de un m&oacute;dulo.</li>
<li><b>Inconvenientes</b>: Su procesado es m&aacute;s lento que en las gr&aacute;ficas normales. Dependiendo del rango de tiempo y el volumen de datos a mostrar es posible que su visualizaci&oacute;n sea menos flu&iacute;da.</li>
</ul>

</body>

