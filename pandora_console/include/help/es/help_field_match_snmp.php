<?php
/*
 * @package Include /help/es
 */
?>

<h1>Variable bindings/Data #1-20</h1>

<p>Son expresiones regulares que intentan casar con las variables 1 a 20. Si hay un acierto, se dispara la alerta. El valor de la variable se guarda en la macro _snmp_fx_ correspondiente (_snmp_f1_, _snmp_f2_, ...). Aunque sólo se puede especificar una expresión regular para veinte variables, las macros _snmp_fx_ macros están disponibles para todas ellas (_snmp_f11_, _snmp_f12_, ...).</p>

<table width=100%>
<tr>
    <p>Como hemos visto, se pueden almacenar hasta 20 variables. Estas variables no tienen por qué seguir un órden consecutivo. La posición que ocupa la variable se puede definir en el campo que precede su valor. Es decir, si nosotros queremos hacer una alerta que busque los valores “Uno” en la primera variable recibida en el trap, “tres” en la tercera variable recibida por el trap y lo mismo para Cinco y Siete, se configuraría como se puede ver más abajo:</p>
</tr>
<tr>
    <img src="../images/help/custom_oid.png" width='520' height='180'>
</tr>
<tr>
    <p>Podemos hacer uso del valor de las variables con coincidencia en las macros _snmp_f1_ .. _snmp_f7_ de forma que al definir la alerta, la acción nos permite usar esas macros:</p>
</tr>
<tr>
    <img src="../images/help/custom_oid2.png" width='520' height='180'>
</tr>
<tr>
    <p>Aquí tenemos un ejemplo de trap con que el se disparará la alerta:</p>
</tr>
<tr>
    <img src="../images/help/trap.png" width='520' height='220'>
</tr>
<tr>
    <p>La alerta generada, un evento de auditoria tiene este texto:</p>

<p>SNMP Alert of 192.168.5.2 with OID .1.3.6.1.4.1.2789.2005
Varbind 100: “cien” Varbind 3: “tres” Varbind 20: “veinte” Varbind 60: “sesenta”</p>

<p>De esta manera, si el trap tiene 200 variables, se pueden usar hasta 20 filtros de variables (Varbinds) y tomar el valor de hasta 20 variables, independientemente si están en la posición 10, 50, o 170.</p>
</tr>
</table>
