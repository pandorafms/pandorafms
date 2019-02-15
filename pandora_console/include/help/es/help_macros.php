<?php
/**
 * @package Include/help/es
 */
?>
<h1>Macros</h1>

Se pueden configurar macros en la ejecución del módulo (module_exec) o los parámetros de un plugin.
<br /><br />
Cada macro tiene 3 parámetros:
<ul>
    <li>Descripción</li>
    <li>Valor por defecto (opcional)</li>
    <li>Ayuda (opcional)</li>
</ul>

Por ejemplo, para configurar un módulo que devuelva el número de procesos 
de apache corriendo en una máquina configuraremos el siguiente comando:
<br /><br />
ps -A | grep apache2 | wc -l
<br /><br />
Podemos sustituir el nombre del proceso por una macro:
<br /><br />
ps -A | grep _field1_ | wc -l
<br /><br />
Y configurar los parámetros de la macro de la siguiente manera:

<ul>
    <li>Descripción: Proceso</li>
    <li>Valor por defecto: apache2</li>
    <li>Ayuda: Nombre o subcadena de los procesos en ejecución contados por el módulo</li>
</ul>

Cuando configuremos el módulo a partir de este componente, aparecerá un campo de texto "Proceso" 
con un valor por defecto "apache2" que podremos modificar, y una ayuda que indicará más información
al usuario.
<br /><br />
Se pueden configurar tantas macros como se quiera.
