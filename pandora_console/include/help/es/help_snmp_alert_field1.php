<?php
/**
 * @package Include/help/en
 */
?>
<h1>SNMP Trap - Macros de datos para las alertas </h1>
<p>

Una vez que ha parseado los campos de datos mediante regexp, puede usar esos campos de datos, para sustituir sus valores en las alertas. Para ello, se pueden usar las macros especiales _snmp_fX_. Estas macros no tienen valor o sentido fuera del contexto de las alertas de traps SNMP.
<br><br>
Para contruir un mensaje, podríamos usar la siguiente cadena en el "campo1":
<br><br>
&nbsp;Alerta de Chasis: _snmp_f2_ en dispositivo _snmp_f1_
<br><br>
Puede usar esas macros en los campos FieldX (1-10) de cualquier alerta.
<br><br>
<b>_DATA_</b>: Trap entero <br>
<b>_AGENT_</b>: Nombre del Agente <br>
<b>_IP_</b>: Dirección IP<br>
<b>_TIMESTAMP_</b>: Fecha trap<br>
<b>_SNMP_OID_</b>: OID del trap<br> 
<b>_SNMP_VALUE_</b>: Valor del OID del trap<br> 
</p>
