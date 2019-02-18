<h1>Prediction date</h1>


<p>Prediction date permite utilizando una proyeccion de los datos de un modulo a futuro, devolver la fecha en la cual el modulo tomara un valor entre un rango. Para ello se utiliza el metodo de los minimos cuadrados.</p>

<p>
<b>Period</b>: Periodo utilizado para hacer la estimacion.
</p>
<p>
<b>Data Range</b>: Intervalo que necesita alcanzar el módulo para que la fecha asociada sea devuelta.
</p>

<p>Por ejemplo, para el modulo disk_temp_free y eligiendo un periodo de 2 meses si se busca la fecha en la que el modulo alcanzará el intervalo [5-0] el resultado sera <i>04 Dec 2011 18:36:23</i>.</p> 
<p>Visto de una manera grafica seria:</p>     
    
<?php html_print_image('images/help/prediction_date.png', false, ['height' => '210']);
