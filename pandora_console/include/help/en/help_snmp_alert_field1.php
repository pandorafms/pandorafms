<?php
/**
 * @package Include/help/en
 */
?>
<h1>SNMP Trap - Alert field macros</h1>
<p>

Once we've got the data fields, we must use them in the alert. With this purpose, the special macros _snmp_f1_, _snmp_f2_ and _snmp_f3_ are used. Using these macros doesn't have any sense out of SNMP trap alerts.

To build the message, we would use the following string in Field1.

Chassis Alert: _snmp_f2_ in device _snmp_f1_

You can use these macros in field1, field2 and field3
</p>
