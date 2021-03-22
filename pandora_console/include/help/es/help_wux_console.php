<h1>Monitorizaci&oacute;n de Experiencia de Usuario Web (WUX)</h1>
<h2>Introducci&oacute;n</h2>

<p>
    <?php echo get_product_name(); ?> WUX es un componente interno de <?php echo get_product_name(); ?> que permite a los usuarios automatizar sus sesiones de navegaci&oacute;n web. Genera en <?php echo get_product_name(); ?> un informe con los resultados de las ejecuciones, tiempos empleados, y capturas con los posibles errores encontrados. Es capaz de dividir las sesiones de navegaci&oacute;n en fases para simplificar la vista y depurar posibles cuellos de botella.
</p>

<p>
    <?php echo get_product_name(); ?> WUX utiliza el robot de navegaci&oacute;n de <?php echo get_product_name(); ?> (PWR - <?php echo get_product_name(); ?> Web Robot) para automatizar las sesiones de navegaci&oacute;n
</p>

<h2>Grabar una sesi&oacute;n de navegaci&oacute;n web</h2>
<h4>Grabar una sesi&oacute;n PWR</h4>

<p>
    Antes de monitorizar una experiencia de usuario debemos hacer la grabaci&oacute;n. Dependiendo del tipo de tecnolog&iacute;a que hayamos elegido utilizaremos un sistema de grabaci&oacute;n u otro.
</p>

<p>
    <b class="lato_bolder font_12pt">
        Para realizar la grabaci&oacute;n de una navegaci&oacute;n con PWR necesitaremos:
    </b>
</p>

<ol>
    <li class="lato_bolder font_12pt"> 
        Navegador web Firefox versi&oacute;n 47.0.1 (descargable en: 
        <a rel="nofollow" class="external free" href="https://ftp.mozilla.org/pub/firefox/releases/47.0.1/">https://ftp.mozilla.org/pub/firefox/releases/47.0.1/</a>).
    .</li>
    <li class="lato_bolder font_12pt"> 
        Extensi&oacute;n Selenium IDE (descargable en: 
        <a rel="nofollow" class="external free" href="https://addons.mozilla.org/es/firefox/addon/selenium-ide/">https://addons.mozilla.org/es/firefox/addon/selenium-ide/</a>).
    </li>
</ol>

<p>
    Para instalar correctamente la versi&oacute;n 47.0.1 de Firefox hay que descargarla desde la URL proporcionada anteriormente. En sistemas Windows habr&aacute; que a&ntilde;adir el ejecutable al PATH del sistema.
</p>

<p class="center">
    <?php
    html_print_image('images/help/Ux6.JPG', false, ['style' => 'width: 90%;']);
    ?>
</p>

<p>
    Una vez descargado mostraremos el icono del entorno de grabaci&oacute;n mediante las opciones de personalizaci&oacute;n de Firefox:
</p>

<p class="center">
    <?php
    html_print_image('images/help/Ux7.JPG', false, ['style' => 'width:295px;']);
    ?>
</p>

<p class="center">
    <?php
    html_print_image('images/help/Ux9.JPG', false, ['style' => 'width: 90%;']);
    ?>
</p>

<p>
    Una vez colocado el acceso iniciamos el grabador:
</p>

<p class="center">
    <?php
    html_print_image('images/help/Ux10.JPG', false, ['style' => 'width: 90%;']);
    ?>
</p>

<p>
    Desde este momento podremos navegar por el sitio web que queramos monitorizar y las diferentes acciones de cada paso que avancemos ir&aacute;n apareciendo en el grabador.
</p>

<p>
    Para detener la grabaci&oacute;n utilizaremos el siguiente bot&oacute;n, situado en la parte superior derecha del grabador:
</p>

<p class="center">
    <?php
    html_print_image('images/help/Ux11.JPG', false, ['style' => 'width:33px;']);
    ?>
</p>

<p>
    Una vez completadas las acciones, podemos realizar comprobaciones sobre la p&aacute;gina, por ejemplo verificar la existencia de un texto determinado para asegurarnos de que la p&aacute;gina cargada es la correcta. Para ello haremos click derecho sobre una secci&oacute;n de texto en la ventana del navegador mientras continuamos grabando, y seleccionamos la opci&oacute;n <i>verifyText</i>:
</p>

<p class="center">
    <?php
    html_print_image('images/help/Ux13.JPG', false, ['style' => 'width:90%;']);
    ?>
</p>

<p>
    Aparecer&aacute; un nuevo paso en el grabador indicando la acci&oacute;n de comprobaci&oacute;n de texto indicada:
</p>

<p class="center">
    <?php
    html_print_image('images/help/Ux14.JPG', false, ['style' => 'width:90%;']);
    ?>
</p>

<p>
    Podemos reproducir la secuencia completa mediante el bot&oacute;n <i>Play entire test suite</i> y comprobar que finaliza correctamente:
</p>

<p class="center">
    <?php
    html_print_image('images/help/Ux15.JPG', false, ['style' => 'width:90%;']);
    ?>
</p>

<p>
    Una vez verificada la validez de la secuencia de navegaci&oacute;n, la guardaremos (Archivo -&gt; Save Test Case) para ejecutarla posteriormente con <?php echo get_product_name(); ?> WUX. El fichero resultante ser&aacute; un documento HTML que <?php echo get_product_name(); ?> WUX interpretar&aacute;.
</p>

<h4>Grabar una sesi&oacute;n transaccional con <?php echo get_product_name(); ?> WUX PWR</h4>

<p>
    <?php echo get_product_name(); ?> WUX en modo PWR (<?php echo get_product_name(); ?> Web Robot) permite dividir la monitorizaci&oacute;n de la navegaci&oacute;n de un sitio web en m&uacute;ltiples m&oacute;dulos, que representar&aacute;n cada uno de los pasos realizados.
</p>

<p>
    Para insertar un nuevo punto de control y generar los m&oacute;dulos de fase (hasta ese punto) haga clic derecho en el punto donde desea identificar el comienzo de fase.
</p>

<p class="center">
    <?php
    html_print_image('images/help/Ux16.JPG', false, ['style' => 'width:436;']);
    ?>
</p>

<p>
    Como comentario pondremos el siguiente texto:
</p>

<pre>
    phase_start:nombre_de_fase
</pre>

<p>
    La fase englobar&aacute; el tiempo y resultado de todos los comandos que se encuentren hasta el siguiente comentario:
</p>

<pre>
    phase_end:nombre_de_fase
</pre>

<p>
    Todos los comandos que se ejecuten entre una etiqueta phase_start y phase_end se englobar&aacute;n dentro de esa fase.
</p>

<h2>Visualizaci&oacute;n de los datos</h2>

<p>
    WUX provee al usuario un conjunto de interfaces para recibir informaci&oacute;n en todo momento de los resultados de las ejecuciones de las sesiones de navegaci&oacute;n:
</p>

<h3>Vista de m&oacute;dulos</h3>

<p>
    Cada m&oacute;dulo de tipo an&aacute;lisis web, generar&aacute; una serie de sub-m&oacute;dulos, estos pueden visualizarse de manera m&aacute;s clara pulsando "mostar en modo jer&aacute;quico".
</p>

<p class="center">
    <?php
    html_print_image('images/help/WUX_v1.png', false, ['style' => 'width:90%;']);
    ?>
</p>

<p>
    En la vista de &aacute;rbol los elementos apararecen directamente jerarquizados, simplificando la vista.
</p>

<p class="center">
    <?php
    html_print_image('images/help/WUX_v2.png', false, ['style' => 'width:90%;']);
    ?>
</p>

<h3>Vista de consola WUX</h3>
<p>
    En esta vista podemos encontrar toda la infomaci&oacute;n que el sistema WUX ha obtenido de la sesi&oacute;n de navegaci&oacute;n configurada: 
</p>
<p>
    <b><u>Nota</u>:</b> Si hemos definido fases en nuestra sesi&oacute;n de navegaci&oacute;n, se mostrar&aacute;n en esta vista de una forma sencilla y clara (ver apartado de grabaci&oacute;n <i>sesi&oacute;n transaccional con <?php echo get_product_name(); ?> WUX PWR)</i>.
</p>

<p class="center">
    <?php
    html_print_image('images/help/WUX_v3.png', false, ['style' => 'width:90%;']);
    ?>
</p>

<h4> Secci&oacute;n Resultado Global: </h4>
<p>
    Muestra el estado general de nuestra transacci&oacute;n:
</p>
<ol class="lato_bolder font_12pt">
    <li class="lato_bolder font_12pt">
        Esta puede tener tres estados:
        <ol class="lato_bolder font_12pt">
        
            <li class="lato_bolder font_12pt">
                <i class="lato_bolder font_12pt">Correcto:</i> Cuando todas las fases de la transacci&oacute;n sean correctas.
            </li>
            <li class="lato_bolder font_12pt">
                <i class="lato_bolder font_12pt">Incorrecto:</i> Si alguna de las fases de la transacci&oacute;n ha fallado. En ese caso, se mostrara una icono de una lupa que enlaza a la captura de pantalla del punto de la sesi&oacute;n de navegaci&oacute;n en que se ha producido el fallo.
            </li>
            <li class="lato_bolder font_12pt">
                <i class="lato_bolder font_12pt">Desconocido:</i> Si el servidor encuentra problemas para procesar la sesi&oacute;n o hay fallos de configuraci&oacute;n.
            </li>
        </ol>
    </li>
    <li class="lato_bolder font_12pt">
        Muestra el tiempo transcurrido desde la &uacute;ltima ejecuci&oacute;n de la sesi&oacute;n de navegaci&oacute;n.
    </li>
    <li class="lato_bolder font_12pt">
        Muestra el tiempo total que ha tardado en realizarse dicha sesi&oacute;n de navegaci&oacute;n, independientemente de su estado.    
    </li>
</ol>    

<h4>Secci&oacute;n Resultados de la ejecuci&oacute;n de la transacci&oacute;n:</h4>

<ol class="lato_bolder font_12pt">
    <li class="lato_bolder font_12pt">
        Muestra el estado y el tiempo empleado en ejecutar la sesi&oacute;n de navegaci&oacute;n.
    </li>
    <li class="lato_bolder font_12pt">
        En caso de fallo, se mostrar&aacute; el icono que enlaza con la captura del momento del error.
    </li>
    <li class="lato_bolder font_12pt">
        Si hemos definido fases en nuestra sesi&oacute;n, entonces se mostrar&aacute; el estado de cada una de las fases, asi como el tiempo que tarda en realizarse cada una de ellas y su contribuci&oacute;n al tiempo global.
    </li>
</ol>

<p>
    Gr&aacute;fica que muestra el tiempo que tarda en realizar cada fase de la transacci&oacute;n y el estado de dicha fase, si no tenemos fases definidas, se mostrar&aacute; en bloque el tiempo global empleado en la sesi&oacute;n.
</p>

<p>
    Gr&aacute;fica de estadisticas aparecera siempre y cuando hayamos especificado en la creaci&oacute;n del m&oacute;dulo, la opci&oacute;n <i class="lato_bolder font_12pt">ejecutar pruebas de rendimiento</i> <b class="lato_bolder font_12pt">y se ha definido un objetivo para las mismas</b> (campo <i class="lato_bolder font_12pt">sitio web objetivo</i>).
</p>
<p>
    Las estad&iacute;sticas a mostrar son:
</p>
    <ol>
        <li class="lato_bolder font_12pt"> 
            <b class="lato_bolder font_12pt">
                Stats_TT:
            </b> 
            Tiempo total en obtener el sitio web. 
        </li>
        <li class="lato_bolder font_12pt"> 
            <b class="lato_bolder font_12pt">
                Stats_TDNS:
            </b> 
            Tiempo total en resolver la direcci&oacute;n IP del objetivo.
        </li>
        <li class="lato_bolder font_12pt"> 
            <b class="lato_bolder font_12pt">
                Stats_TTCP:
            </b> 
            Tiempo empleado en conectar v&iacute;a TCP.
        </li>
        <li class="lato_bolder font_12pt"> 
            <b class="lato_bolder font_12pt">
                Stats_TSSL:
            </b> 
            Tiempo empleado en establecer comunicaci&oacute;n SSL.
        </li>
        <li class="lato_bolder font_12pt"> 
            <b class="lato_bolder font_12pt">
                Stats_TST :
            </b> 
            Tiempo hasta que inici&oacute; la transferencia de datos.
        </li>
        <li class="lato_bolder font_12pt"> 
            <b class="lato_bolder font_12pt">
                Stats_TTC :
            </b> 
            Tiempo transfiriendo datos, agrupar&aacute; todos los tiempos de transferencia de recursos.
        </li>
        <li class="lato_bolder font_12pt"> 
            <b class="lato_bolder font_12pt">
                Stats_TTR :
            </b> 
            Tiempo empleado en transferir el recurso X, agrupando todas las im&aacute;genes en “image”.
        </li>
    </ol>
</p>

<h4>Historial de trasacci&oacute;n:</h4>    
<ol>
    <li class="lato_bolder font_12pt">
        Muestra el hist&oacute;rico de las ejecuciones de la sesi&oacute;n de navegaci&oacute;n web.
    </li>
    <li class="lato_bolder font_12pt">
        En el caso de haber ejecuciones fallidas, se mostrar&aacute; un enlace donde consultar la imagen del error.
    </li>
</ol>