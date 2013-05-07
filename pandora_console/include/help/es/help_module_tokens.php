<?php
/* Include package help/es
*/
?>

<p> Cuando un módulo de tipo data es creado desde la consola, la Configuración 
de datos es copiada en el fichero de configuración del agente para ser usada 
<b>en la actualización del módulo. No en la creación.</b><br><br>

La mayoría de los tokens de la Configuración de datos es usada solo en 
la creación. Una excepción es la descripción, que será actualizada si cambia. 
De este modo, si definimos una descripción en el formulario y otra distinta 
en la Configuración de datos, el módulo tendrá la primera desde su creación
hasta la primera actualización.<br><br>

De este modo, utilizaremos la Configuración de datos para los tokens 
obligatorios (nombre, tipo...), los tokens que pueden ser actualizados cuando 
el agente se ejecute (descripción) y los tokens usados para obtener información
(module_exec, module_proc...).<br><br>

El resto de los campos deberán ser definidos en el formulario como cualquier otro
tipo de módulo.<br><br>

Ejemplo:<br><br>

<i>
Para crear un módulo con un intervalo 2 (el doble del intervalo del agente) 
necesitaremos definirlo en el formulario, no en la Configuración de datos.<br><br>

La Configuración de datos podrá ser tan simple como:<br><br>

module_begin<br>
module_name test_module<br>
module_type generic_data_string<br>
module_exec echo "TEST"<br>
module_end<br><br>

Y el campo Intervalo deberá ser rellenado con un "2".
</i>
</p>

