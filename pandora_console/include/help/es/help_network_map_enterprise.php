<?php
/*
* @package Include/es
*/
?>

<h1>Networkmap console</h1>

<p>Con la version Enterprise puede crear mapas de red editables de una manera mas interactiva comparado con la version Open que esta actualmente en la subseccion &#34;Ver agentes&#34;.</p>

<p>En contraste con la version Open, el mapa de red proprociona mas funcionalidades como:</p>


<li>Mapa de red mas grande, con mas de 1000 agentes para monitorizar.</li>
<li>Monitorizar en tiempo real toda la topologia de la red con sus sistemas.</li>
<li>Diferentes vistas de la topologia de red, definidas de una forma manual o generadas automaticamente con grupos de agentes.</li>
<li>Enlazar diferentes vistas mediante el uso de puntos ficticios.</li>
<li>Manipular la topologia representada en cada una de las vistas.</li>
<li>A&ntilde;adir nuevos nodos, uno por uno o de forma masiva.</li>
<li>Editando las caracteristicas de los nodos.</li>
<li>Organizandolos dentro de la vista:<br>
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

<p>El minimapa nos provee de una vista global que muestra toda la extensión del mapa, pero en una vista mucho mas pequeña, además que frente a la vista del mapa se muestra completamente todos los nodos pero sin estado y sin las relaciones. Excepto el punto ficticio de Pandora que se muestra en verde. Y además se muestra un recuadro rojo de la parte del mapa que se esta mostrando.</p>

<p>Se encuentra en la esquina superior izquierda, y se puede ocultar pulsando en el icono de la flecha.
</p>


<h2>Panel de control</h2>

<p>Desde el panel de control puedes realizar tareas mas complejas sobre el mapa de red.</p>

<p>Se encuentra oculto en la esquina superior derecha, como en el minimapa se puede mostrar pulsando en la flecha.</p>
<?php html_print_image ("images/help/netmap1.png", false, array('width' => '550px')); ?>

<p>Y las opciones disponibles son:</p>

    <li>Cambiar la frecuencia de refresco de los estado de los nodos.</li>
    <li>Forzar el refresco.</li>
    <li>Añadir agente, por medio del control inteligente que permite buscar de forma rápida el agente y añadirlo, el nuevo nodo aparece en el punto (0, 0) del mapa que esta en la parte superior izquierda del mapa.</li>
    <li>Añadir varios agentes, por medio del filtrado por grupo que mostrará los agentes de ese grupo en una lista de selección múltiple y que no estén ya en el mapa.</li>
    <li>Hacer una captura de la parte visible del mapa.</li>
    <li>Añadir un punto ficticio, donde puedes elegir, el texto como nombre de este punto, el tamaño definido por el radio, la forma del punto, color por defecto y si quieres que el punto ficticio sea un link a un mapa.</li>
    <li>Buscar agente, por medio de también de un control inteligente, una vez elegido el mapa va automáticamente al punto donde esta el nodo del agente.</li>
    <li>Zoom, cambiar el nivel de zoom del mapa de red. </li>

<h2>Ventana vista detalle</h2>

<p>La ventana vista detalle es una vista visual de un agente, la cual se refresca a la misma velocidad que el mapa del que se abrió, y las ventanas son totalmente independientes por lo que puedes tener varias ventanas de estas abiertas.</p>


<?php html_print_image ("images/help/netmap2.png", false, array('width' => '550px')); ?><br><br>



    <p>Muestra una caja que el borde se pone del color del estado del agente.<br>
    El nombre del agente es un link a la página del agente de Pandora.<br>
    Dentro de la caja aparecen todos los módulos que no están en estado desconocido, los cuales según si el estado del modulo estará verde o rojo.<br>
        Estos módulos son clickables y muestran un tooltip con los datos principales del módulo. <br>
    En el borde de la caja aparece los módulos de tipo SNMP Proc, que suelen corresponder a interfaces de red cuando se monitoriza un agente relacionado con sistemas de red. <br></p>

<h2>Paleta de punto ficticio
</h2>
<p>Si seleccionas ver detalles sobre un punto ficticio, este te mostrara una ventana emergente con una paleta de opciones para modificar el punto ficticio.</p>

<?php html_print_image ("images/help/netmap3.png", false, array('width' => '550px')); ?><br><br>


<p>Disponemos de un formulario con las opciones de:</p>

    <li>Nombre del punto ficticio.</li>
    <li>Radio del punto ficticio.</li>
    <li>Forma del punto ficticio.</li>
    <li>Color del punto ficticio.</li>
    <li>Mapa que linka el punto ficticio. </li>

<h2>Creación de un mapa de red</h2>

<p>Para la creación de un mapa de red puedes hacerlo como:</p>

    <li>Despliegue de todos los agentes contenidos en un grupo.</li>
    <li>Creación de un mapa de red en blanco. </li>




<br><br>Vamos a hacer una vista rápida de los campos que tiene el formulario de creación:<br><br>

    <li><b>Nombre:</b> nombre del mapa de red.</li>
    <li><b>Grupo:</b> el grupo al que pertenece el mapa de red para las ACL, y además el grupo del que generar el mapa a partir de los agentes que hay contenidos en ese grupo.</li>
    <li><b>Generación del mapa de red desde:</b> opción solo disponible en la creación y es la forma de crear el mapa de red, si a partir de los agentes que existen en el grupo elegido previamente o por el contrario queremos un mapa de red vacío.</li>
    <li><b>Tamaño del mapa de red:</b> en el cual se puede definir el tamaño del mapa de red, por defecto es 3000 pixeles de ancho por 3000 pixeles alto.</li>
    <li><b>Método de generación del mapa de red:</b> el método de distribución de los nodos que formarán el mapa de red, por defecto es radial, pero existen los siguientes:</li>
        <p>- <i>Radial:</i> en el cual todos los nodos se dispondrán alrededor del nodo ficticio que simboliza el Pandora.<br>
        - <i>Circular:</i> en el cual se dispondrá los nodos en círculos concentricos.<br>
        - <i>Flat:</i> en el cual se dispondrá los nodos de forma arborescente.<br>
        - <i>spring1, spring2:</i> son variaciones del Flat. <br>
    <li><b>Refresco del networkmap:</b> la velocidad de refresco de los estados de los nodos contenidos en el networkmap, por defecto es cada 5 minutos. </p>

El resto de los campos que estén deshabilitados como por ejemplo "redimensionar mapa" es porque solo están activos en la edición de un mapa ya creado.<br><br>


Para más información sobre la edición de mapas consulte http://openideas.info/wiki/index.php?title=Pandora:Documentation_es:Presentacion_datos#Consola_Network_Enteprise
