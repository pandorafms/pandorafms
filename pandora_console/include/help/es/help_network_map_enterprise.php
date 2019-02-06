<?php
/*
 * @package Include/es
 */
?>

<h1>Mapas de red</h1>

<p>Con la version Enterprise puede crear mapas de red editables de una manera mas interactiva comparado con la version Open que no cuenta con muchas de las opciones de manipulación de elementos.</p>

<p>El mapa de red proprociona algunas funcionalidades como:</p>

<li>Monitorizar en tiempo real toda la topologia de la red con sus sistemas.</li>
<li>Diferentes vistas de la topologia de red, definidas de una forma manual o generadas automaticamente con grupos de agentes.</li>
<li>Enlazar diferentes vistas mediante el uso de puntos ficticios.</li>
<li>A&ntilde;adir nuevos nodos, uno por uno o de forma masiva.</li>
<li>Editar las caracteristicas de los nodos.</li>
<li>Organizar dentro de la vista:<br>
            - La posición de los nodos.<br>
            - Las relaciones entre los nodos.</li>

<p>Los mapas de red pueden contener:</p>

    <p><b>Nodos reales</b>, los cuales representan de forma única los agentes allí añadidos en el mapa. Estos nodos tienen un icono que representa el sistema operativo del agente, y una aureola (con la forma circular por defecto, pero puede escogerse entre otras distintas formas) de estado del agente que puede ser:</p>
        <li>Verde, esta en estado correcto.</li>
        <li>Rojo, esta en estado crítico alguno de sus módulos.</li>
        <li>Amarillo, esta en estado warning alguno de sus módulos.</li>
        <li>Naranja, en el agente ha sido disparada alguna de las alarmas.</li>
        <li>Gris, el agente esta en estado desconocido.</li>
    <p><b>Nodos ficticios</b>, los cuales representan un enlace a otro mapa de red o simplemente un punto para uso personal dentro del mapa, puede tener cualquier forma de las disponibles (circulo, rombo, cuadrado), cualquier tamaño y el texto por supuesto. Y si es un enlace a otro mapa el color sigue la siguientes reglas, si no se le puede personalizar el color:</p>
        <li>Verde, si todos los nodos del mapa enlazado están correctos.</li>
        <li>Rojo, si alguno de los nodos del mapa enlazado esta en estado critico.</li>
        <li>Amarillo, si alguno de los nodos del mapa esta en estado warning y no hay ninguno en estado critico.</li>
        <li>Naranja, Gris siguiendo la misma regla que los otros colores. </li>

<h2>Minimapa</h2>

<p>El minimapa nos provee de una vista global que muestra toda la extensión del mapa, pero en una vista mucho mas pequeña, además que frente a la vista del mapa se muestra completamente todos los nodos pero sin estado y sin las relaciones. Excepto el punto ficticio de <?php echo get_product_name(); ?> que se muestra en verde. Y además se muestra un recuadro rojo de la parte del mapa que se esta mostrando.</p>

<p>Se encuentra en la esquina superior izquierda, y se puede ocultar pulsando en el icono de la flecha.
</p>


<h2>Menú del mapa</h2>

<p>Desde el menú contextual del mapa de red puedes realizar tareas mas complejas sobre el.</p>

<p>Se muestra si hacemos click derecho en alguna sección del mapa vacía.</p>
<?php html_print_image('images/help/mapa vista menu mapa.png', false, ['width' => '550px']); ?>

<p>Y las opciones disponibles son:</p>

    <li>Añadir agente, por medio del control inteligente que permite buscar de forma rápida el agente y añadirlo (tanto ficticio como añadido masivo de agentes), el nuevo nodo aparece en el punto donde hiciste click en el mapa.</li>
    <li>Escoger el centro del mapa de red.</li>
    <li>Forzar el refresco.</li>
    <li>Refrescar la zona de espera para buscar nuevos nodos.</li>

<h2>Menú del nodo</h2>

<p>Desde el menú contextual del nodo puedes realizar tareas mas complejas sobre el.</p>

<p>Se muestra si hacemos click derecho en algún nodo.</p>
<?php html_print_image('images/help/mapa vista menu nodo.png', false, ['width' => '550px']); ?>

<p>Y las opciones disponibles son:</p>

    <li>Mostrar una vista en detalle con datos del nodo. Además en esta vista podremos consultar sus interfaces (si las tiene), editar su nombre y su forma o ver que enlaces tiene disponibles para editarlos o borrarlos.</li>
    <?php html_print_image('images/help/informacion de nodo.png', false, ['width' => '550px']); ?>
    <li>Añadir un enlace entre interfaces. Con esto podremos enlazar dos módulos de interfaz entre si, o uno de ellos con otro agente. Primero seleccionamos el hijo de la unión y posteriormente el padre</li>
    <?php html_print_image('images/help/crear enlace de interfaz hijo.png', false, ['width' => '550px']); ?>
    <?php html_print_image('images/help/crear enlace de interfaz padre.png', false, ['width' => '550px']); ?>
    <?php html_print_image('images/help/crear enlace de interfaz tabla.png', false, ['width' => '550px']); ?>
    <li>Definir un enlace padre-hijo entre agentes, siguiendo la misma metodología que con los enlaces de interfaz, primero se seleccionaría el hijo, y posteriormente, el padre.</li>
    <?php html_print_image('images/help/crear enlace agente agente hijo.png', false, ['width' => '550px']); ?>
    <?php html_print_image('images/help/crear enlace agente agente padre.png', false, ['width' => '550px']); ?>
    <li>Eliminar el nodo seleccionado (y todos sus enlaces).</li>

<h2>Creación de un mapa de red</h2>

<p>Para la creación de un mapa de red puedes hacerlo como:</p>

    <li>Despliegue de todos los agentes contenidos en un grupo.</li>
    <?php html_print_image('images/help/creacion mapa normal.png', false, ['width' => '550px']); ?>
    <li>Creación de un mapa de red en blanco. </li>
    <?php html_print_image('images/help/creacion mapa vacio.png', false, ['width' => '550px']); ?>

<br><br>Vamos a hacer una vista rápida de los campos que tiene el formulario de creación (habrá muchos menos valores para la creación de un mapa vacío):<br><br>

    <li><b>Nombre:</b> nombre del mapa de red.</li>
    <li><b>Grupo:</b> el grupo al que pertenece el mapa de red para las ACL, y además el grupo del que generar el mapa a partir de los agentes que hay contenidos en ese grupo.</li>
    <li><b>Radio de los nodos:</b> Opción para establecer un tamaño de radio para los nodos.</li>
    <li><b>Descripción:</b> Descripción para el mapa de red.</li>
    <li><b>Desplazamiento en X:</b> Desplazamiento en el eje x para establecer una vision por defecto al gusto.</li>
    <li><b>Desplazamiento en Y:</b> Desplazamiento en el eje y para establecer una vision por defecto al gusto.</li>
    <li><b>Nivel de zoom:</b> Desplazamiento en el zoom para establecer una vision por defecto de escala al gusto.</li>
    <li><b>Origen:</b> Establece si el mapa se genera a partir de un grupo, de una tarea de reconocimiento o de una mascara ip.</li>
    <li><b>No mostrar subgrupos:</b> No mostrará subgrupos si el origen es por grupo.</li>
    <li><b>Tarea de reconocimiento de origen:</b> Nos permite seleccionar la tarea de reconocimiento para generar el mapa.</li>
    <li><b>IP:</b> Nos permite seleccionar la IP generar el mapa (solo generación por máscara ip).</li>
    <li><b>Método de generación del mapa de red:</b> el método de distribución de los nodos que formarán el mapa de red, por defecto es spring2, pero existen los siguientes:</li>
        <p>- <i>Radial:</i> en el cual todos los nodos se dispondrán alrededor del nodo ficticio que simboliza el <?php echo get_product_name(); ?>.<br>
        - <i>Circular:</i> en el cual se dispondrá los nodos en círculos concentricos.<br>
        - <i>Flat:</i> en el cual se dispondrá los nodos de forma arborescente.<br>
        - <i>spring1, spring2:</i> son variaciones del Flat. <br>
        - <i>Radial dinámico:</i> Mapa circular con la jerarquía de grupos y agentes dentro de ellos (y módulos en última instancia). <br>
    <li><b>Separación entre nodos:</b> Separación entre los nodos del mapa.</li>
    <li><b>Distancia minima entre nodos (solo circular):</b> Establece la distancia minima entre los nodos del mapa.</li>
    <li><b>separación entre flechas (solo flat y radial):</b> Separación entre las flechas del mapa.</li>
    <li><b>Separación por defecto para nodos (solo spring2):</b> Establece la distancia minima entre los nodos del mapa.</li><br><br>
