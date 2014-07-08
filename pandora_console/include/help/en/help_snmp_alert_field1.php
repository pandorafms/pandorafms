<?php
/**
 * @package Include/help/en
 */
?>
<h1>SNMP Trap - Alert field macros</h1>
<p>

Once we've got the data fields, we must use them in the alert. With this purpose, the special macros _snmp_fX_ are used. Using these macros doesn't have any sense out of SNMP trap alerts.
<br><br>
To build the message, we would use the following string in Field1.
<br><br>
&nbsp;Chassis Alert: _snmp_f2_ in device _snmp_f1_
<br><br>
You can use these macros in FieldX (1-10) of any alert
<br><br>
<b>_data_</b>: Full trap<br>
<b>_agent_</b>: Agent name<br>
<b>_address_</b>: IP Address<br>
<b>_timestamp_</b>: Trap date<br> 
<b>_snmp_oid_</b>: Trap OID<br> 
<b>_snmp_value_</b>: Trap OID value<br> 
</p>
