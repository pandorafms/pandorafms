<?php
/**
 * @package Include/help/en
 */
?>
<h1>SNMP Trap - Macros de datos para las alertas </h1>
<p>

Una vez que ha parseado los campos de datos mediante regexp, puede usar esos campos de datos, para sustituir sus valores en las alertas. Para ello, se pueden usar las macros especiales _snmp_f1_, _snmp_f2_ y _snmp_f3_. Estas macros no tienen valor o sentido fuera del contexto de las alertas de traps SNMP.

Para contruir un mensaje, podríamos usar la siguiente cadena en el "campo1":

 Alerta de Chasis: _snmp_f2_ en dispositivo _snmp_f1_

Puede usar esas macros en el campo1, campo2 y campo3 de cualquier alerta.
</p>
