<?php
/**
 * @package Include/help/en
 */
?> 

<h1> Creación de una Plantilla de Alertas</h1>

El primer paso para crear una plantilla de alertas son las condiciones.  
<br><br>
A continuación se detallan los campos que hay que rellenar: 
<br><br>
<ul type=”disc”>
<li>    <b>Name:</b> El nombre del template. <br></li><br>
<li>    <b>Description:</b> Describe la función de la plantilla, y resulta útil para identificar la plantilla entre otras en la vista general de alertas. <br></li><br>
<li>    <b>Priority:</b> Campo informativo acerca de la alerta. Sirve para filtrar a la hora de buscar alertas. Se puede elegir entre las siguientes prioridades:  <br><br></li><br>
* Maintenance<br>
* Informational<br>
* Normal<br>
* Minor<br>
* Warning<br>
* Major<br>
* Critical<br>
* Warning/Critical<br>
* Not normal<br><br>

<li>    <b>Condition Type:</b> Campo donde se define el tipo de condición que se aplicará a la alerta. Se añadirán los combos necesarios según el tipo elegido, existen los siguientes tipos:  <br></li></ul>
<li>    <i>Regular Expression:</i> Se usa una expresión regular. La alerta saltara cuando el valor del módulo cumpla una condición establecida. <br> 
Al elegir la condición regular aparece la posibilidad de marcar la casilla Trigger when matches the value. En caso de marcar, la la alerta se lanzará cuando coincida el valor y, en caso de no marcarla, la alerta se lanzará cuando no coincida el valor.  </li><br>
<li>    <i>Max and Min:</i> Se usa un valor máximo y otro mínimo. <br>
Al alegir la condición regular aparece la posibilidad de marcar la casilla Trigger when matches the value. En caso de marcarla, la alerta se lanzará cuando el valor este fuera del rango marcado entre el máximo y el mínimo y, en caso de no marcarla, la alerta se lanzará cuando el valor este dentro del rango marcado entre el máximo y el mínimo.  </li><br>
<li>    <i>Max:</i> Se usa un valor máximo. La alerta saltara cuando el valor del módulo sea mayor que el valor máximo marcado.  <br></li><br>
<li>    <i>Min:</i> Se usa un valor mínimo. La alerta saltara cuando el valor del módulo sea menor que el valor mínimo marcado.   </li><br>
<li>    <i>Equal to:</i> Usado para disparar la alerta cuando se proporciona un valor de debe ser igual al dato recibido. Esta condición, igual que las de max/min se usa sólo para valores numéricos, p.e: 234 o 124.35.  </li><br>
<li>    <i>Not Equal to:</i> Igual que el anterior pero negando la condición (operador lógico NOT).  </li><br>
<li>    <i>Warning Status:</i> Se usa el estado del módulo. La alerta saltará cuando dicho estado sea Warning.  </li><br>
<li>    <i>Critical Status:</i> Se usa el estado del módulo. La alerta saltará cuando dicho estado sea Critical.  </li><br>
<li>    <i>Unknown Status:</i> La alerta saltará cuando el modulo esté en estado desconocido  </li><br>
<li>    <i>On Change</i> La alerta saltará cuando el modulo cambie de valor  </li><br>
<li>    <i>Always</i> La alerta se disparará siempre </li><br>
<br><br>

Una vez se han rellenado los campos se pincha en el botón “Next” y se accede a la siguiente pantalla .

<?php html_print_image('images/help/alert1.png', false, ['width' => '550px']); ?>
<br><br>
A continuación se detallan los campos que hay que rellenar: 
<br><br>

<b>Days of Week</b><br><br>

Establece los días en los que la alerta podrá dispararse. <br><br>

<b>Use special days list</b><br><br>

Habilitar/deshabilitar el uso de la lista de días especiales (festivos y días laborables especiales). <br><br>

<b>Time From</b><br><br>

Hora a partir de la cual se ejecuta la acción de la alerta. <br>
<br>
<b>Time To</b><br><br>

Hora hasta la que se ejecuta la acción de la alerta. <br><br>

<b>Time Threshold</b><br><br>

Define el intervalo de tiempo en el cual se garantiza que una alerta no se va a disparar más veces del número establecido en Numero máximo de alertas. Pasado el intervalo definido, una alerta se recupera si llega un valor correcto, salvo que esté activado el valor Recuperación de alerta, en cuyo caso se recupera inmediatamente después de recibir un valor correcto independientemente del umbral. <br><br>

<b>Min number of alerts</b><br><br>

Número mínimo de veces que tiene que llegar un valor fuera de rango (contando siempre a partir del número definido en el parámetro FlipFlop del módulo) para empezar a disparar una alerta. El valor por defecto es 0, lo que significa que la alerta se disparará cuando llegue el primer valor que cumpla la condición. Funciona como un filtro, necesario para eliminar falsos positivos. <br><br>

<b>Max number of alerts</b><br><br>

Máximo número de alertas que se pueden enviar consecutivamente en el mismo intervalo de tiempo (Time Threshold).<br><br>

<b>Advanced fields management</b><br><br>

Define el valor para la variable "_ﬁeldX_". Aqui se pueden utilizar una serie de macros que se describen en la ayuda de las macros.<br><br>

<b>Default Action</b><br><br>

En este combo se define la acción por defecto que va a tener el template. Esta es la accion que se creará automáticamente cuando asigne la plantilla al módulo. Puede no poner ninguna o poner una, pero no se pueden poner varias acciones por defecto.<br><br>

Una vez se han rellenado los campos se pincha en el botón “Next” y se accede a la siguiente pantalla . <br><br>

<?php html_print_image('images/help/alert2.png', false, ['width' => '550px']); ?>

A continuación se detallan los campos que hay que rellenar: <br><br>

<b>Alert Recovery</b><br><br>

Combo donde se puede definir si esta habilitado o no la recuperación de alertas. En el caso de que esté habilitada la recuperación de alertas, cuando el módulo vuelve a tener valores fuera del rango de alerta, se ejecutará la acción correspondiente con el campo Field 1 que se ha definido en la alerta y los campos Field 2 y Field 3 que se definen a continuación. <br><br>

<b>Field 2</b><br><br>

Define el valor para la variable "_ﬁeld2_". en la recuperación de la alerta. <br><br>

<b>Field 3</b><br><br>

Define el valor para la variable "_ﬁeld3_". en la recuperación de la alerta.<br>

Una vez se han rellenado los campos se pincha en el botón “Finish”. 
















