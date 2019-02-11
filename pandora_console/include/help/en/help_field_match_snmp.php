<?php
/*
 * @package Include /help/en
 */
?>

<h1>Variable bindings/Data #1-20</h1>

<p>These are regular expressions that try to match varbinds 1 to 20. If there is a match, the alert is fired. The value of the variable is stored in the corresponding _snmp_fx_ macro (_snmp_f1_, _snmp_f2_, ...). Even though only twenty variables can be searched for matches, _snmp_fx_ macros are set for all of them (_snmp_f11_, _snmp_f12_, ...).</p>


<table width=100%>
<tr>
    <p>You can use up to 20 variables for doing the filtering (and reusing later for macros). But they doesnt need to follow a specific order. The position of the variable can be defined in the field preceding value.
That is, if we want to make an alert seeking values ​​"Uno" in the first variable received at the trap, "Tres" in the third variable received by the trap and the same for “Cinco” and “Siete”, is configured as you can see below:</p>
</tr>
<tr>
    <img src="../images/help/custom_oid.png" width='520' height='180'>
</tr>
<tr>
    <p>We can use the value of the variables in macros _snmp_f1_ coincidence .. so _snmp_f7_ to define the alert, the alert action allows us to use these macros:</p>
</tr>
<tr>
    <img src="../images/help/custom_oid2.png" width='520' height='60'>
</tr>
<tr>
    <p>Here's an example of SNMP trap that will trigger the alert:</p>
</tr>
<tr>
    <img src="../images/help/trap.png" width='520' height='220'>
</tr>
<tr>
    <p>Alert generated (an internal audit) will have this text:</p>
 
<p>SNMP Alert of 192.168.5.2 with OID .1.3.6.1.4.1.2789.2005
Varbind 100: “cien” Varbind 3: “tres” Varbind 20: “veinte” Varbind 60: “sesenta”</p>

<p>Thus, if the trap has 200 variables, you can use up to 20 filters variables (varbinds) and take the value of up to 20 variables, regardless if they are in the 10, 50, or 170 position.</p>
</tr>
</table>
