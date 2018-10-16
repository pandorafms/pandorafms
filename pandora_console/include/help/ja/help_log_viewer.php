<?php
/**
 * @package Include/help/en
 */
?>
<h1>Visualización y búsqueda avanzadas</h1>
<p>
Se pueden realizar graficas extrayendo información 
de los logs, clasificando la información en base a modelos de captura de datos.
</p>
<p>
Estos modelos de captura de datos son básicamente expresiones regulares e 
identificadores, que nos permitirán analizar los orígenes de datos y mostrarlos como un gráfico.
</p>
<p>
Podemos seleccionar el modelo de captura.
El modelo por defecto, Apache log model, ofrece la posibilidad de parsear logs de Apache 
en formato estándar (access_log), pudiendo extraer gráficas comparativas de tiempo de respuesta, 
agrupando por página visitada y código de respuesta.
</p>
<p>
Al pulsar en el botón de editar editaremos el modelo de captura seleccionado. 
Con el botón de crear agregaremos un nuevo modelo de captura.
En el formulario que aparece, podremos elegir:
</p>
<p><h4>Título</h4>
Un nombre para el modelo de captura
</p>
<p><h4>Expresión regular</h4>
Cada campo a extraer se identifica con la subexpresión entre los paréntesis (expresión a capturar).
</p>
<p><h4>Los campos</h4>
En el orden en que los hemos capturado con la expresión regular. 
Los resultados se agruparán por la concatenación de los campos clave, que son aquellos 
cuyo nombre no esté entre guiones bajos:
</p>
<p>clave, _valor_</p>
<p><em>Observación:</em> Si no especificamos un campo valor, será automáticamente el conteo de apariciones 
que coinciden con la expresión regular.</p>

<p><em>Observación 2:</em> Si especificamos una columna valor podremos elegir entre representar el valor acumulado 
(comportamiento por defecto) o marcar el checkbox para representar el promedio.</p>

<h3>Ejemplo</h3>
<p>Si quisiéramos extraer entradas de un log con el siguiente formato:</p>
<p><b>Sep 19 12:05:01 nova systemd: Starting Session 6132 of user root.</b></p>

<p>Para contar el número de veces que se ha iniciado sesión, agrupando por usuario, usaremos la expresion regular:<p>
<p><b>Starting Session \d+ of user (.*?)\.</b></p>

<p>y de campo:</p>
<p><b>username</b></p>

<p>Este modelo de captura nos devolverá una grafica con el número de inicios de sesión por usuario 
en el intervalo de tiempo que seleccionemos.</p>