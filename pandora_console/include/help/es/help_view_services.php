<?php
/**
 * @package Include/help/es
 */
?> 

<h1> Creando un nuevo servicio</h1>

Los servicios representan la asociación de módulos de agentes y sus valores son calculados en tiempo de ejecución. Por ello antes de nada es necesario tener todos los dispositivos que forman el servicio monitorizados y los valores de sus módulos normalizados a tres estados: Normal, Advertencia o Critico. 
<br><br>
Una vez que tiene todos los dispositivos monitorizados puede crear un grupo con ellos dentro de un servicio. Dentro de cada servicio puede añadir todos los módulos que necesite para monitorizar el servicio. Por ejemplo si quiere monitorizar el servicio de la Tienda Online necesita un modulo para el contenido, otro que monitorice el estado de las comunicaciones y así los demás.

<br><br>

Para crear una nuevo servicio simplemente haga click en el boton Create, y entonces puede crear el servicio rellenando el formulario que aparece en la imagen de abajo.  
<br><br>

<?php html_print_image('images/help/service2.png', false, ['width' => '550px']); ?>
<br><br>
En este punto hemos creado un servidor sin items, así que tenemos que añadir los items que componen el servicio. Para añadir un nuevo item pulse en la herramienta naranja de la esquina superior derecha del tab Gestión de Servicio y luego en el botón Crear. Aparecerá el siguiente formulario. En este formulario debe elegir el módulo de agente que quiere añadir. Además debe rellenar los campos pesos, que dictan los pesos que tiene el módulo dentro del servicio para los estados Normal, Advertencia y Crítico. Cuanto más peso tenga el módulo más importante es dentro del servicio.  
<br><br>

<?php html_print_image('images/help/service1.png', false, ['width' => '550px']); ?>
<br><br>
Cuando todos los campos están rellenos pulse en el botón Crear y aparecerá una imagen parecida a la inferior mostrando que el módulo se añadió con éxito.
<br><br>
<?php html_print_image('images/help/service3.png', false, ['width' => '550px']); ?>
<br><br>
Puede añadir todos los elementos que necesite para monitorizar sus servicios. En este ejemplo hemos añadido todos los elementos necesarios para monitorizar el servicio con los pesos correspondientes, y el resultado queda como puede ver en la siguiente imagen. 
<br><br>
<?php html_print_image('images/help/service4.png', false, ['width' => '550px']); ?>
<br><br>
Aparecerá la lista con todos los servicios en el modo operación, parecida a la imagen inferior. Estos datos son calculados en tiempo real mostrando los siguientes parámetros:
<br><br>
<ul type=”disc”>
<li>    *<i>Name:</i> nombre del servicio. <br></li>
<li>    *<i>Description:</i> descripción del servicio.<br></li>
<li>    *<i>Group:</i> Grupo al que pertenece el servicio. <br></li>
<li>    *<i>Critical:</i> Valor límite a partir del cual el servicio está en estado crítico.<br></li>
<li>    *<i>Warning:</i> Valor límite a partir del cual el servicio está en estado warning. <br></li>
<li>    *<i>Value:</i> Valor del servicio. Se calcula en tiempo real<br></li>
<li>    *<i>Status:</i> Estado del servicio en función del valor y los límites critical y warning. . <br></li>
</ul>
<br><br>
<?php html_print_image('images/help/service5.png', false, ['width' => '550px']); ?>
<br><br>
Si hace click en el nombre de un servicio verá la vista específica de ese servicio. Como sabe el estado del servicio se calcula como la suma de los pesos asociados a cada módulo. Los servicios, al igual que los módulos, tienen asociado un estado dependiendo de su valor. Esta vista muestra el estado de cada item del servicio con los siguientes parámetros: 
<br><br>
<ol>
<li />    <i>AgentName:</i> nombre del agente al que pertenece el módulo. <br>
<li />    <i>Module Name:</i>  nombre del módulo.<br>
<li />    <i>Description:</i> descripción libre.<br>
<li />    <i>Weight Critical:</i> peso cuando el módulo está en estado crítico.<br>
<li />    <i>Weight Warning:</i> peso cuando el módulo está en estado warning.<br>
<li />    <i>Weight Ok:</i> peso cuando el módulo está en estado normal. <br>
<li />    <i>Data:</i> valor del módulo.<br>
<li />    <i>Status:</i> estado del módulo.<br>
</ol>

<br><br>
<?php html_print_image('images/help/service6.png', false, ['width' => '550px']); ?>
<br><br>
También se pueden crear módulos asociados a servicios con las ventajas que esto implica (periodicidad de cálculo, integración con el sistema de alertas etc.) La forma de asociar un módulo a un servicio es seguir los siguientes pasos: 
<br><br>
<ol>
<li />    Crear los monitores individuales que componen el servicio y asegurarse de que funcionan correctamente. <br><br>
<li />    Establecer los umbrales individuales para cada monitor para definir estados CRITICAL y/o WARNING. <br><br>
<li />    Crear un servicio con aquellos monitorres que consideremos, y definir umbrales tanto para el servicio como pesos para cada monitor incluido en el servicio.<br><br>
<li />    Ir al agente donde queremos "ubicar" el monitor asociado al servicio. <br><br>
<li />    Crear un nuevo modulo de tipo "prediction" asociado a ese agente, utilizando el editor de modulos del servidor Prediction, para asociarlo a uno de los servicios de la lista. <br><br>
<li />    Si queremos asociar alertas al servicio, debemos hacerlo sobre el modulo asociado al servicio. El servicio como tal no tiene posibilidad de agregar alertas, ni gráficas ni informes, todo debe ser hecho a través del monitor vinculado al servicio, tal y como se ha descrito.  <br><br>
</ol>
<br><br>
<?php html_print_image('images/help/service7.png', false, ['width' => '550px']); ?>
<br><br>



