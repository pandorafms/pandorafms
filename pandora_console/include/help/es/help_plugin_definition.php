<?php
/**
 * @package Include/help/es
 */
?>
<h1>Registro de complementos</h1>

La herramienta de registro de Pandora FMS se usa para definir los parámetros que el servidor de complementos de Pandora FMS necestia usar con cada complemento, y qué tipo de datos se le pasarán posteriormente después del parámetro.
<br><br>
Por ejemplo, si tiene un complemento para comprobar la <i>tablespace</i> de Informix llamada &laquo;Aleph&raquo;, bajo la dirección IP &laquo;192.168.1.2&raquo; con el nombre de usuario &laquo;Calabria&raquo; y la contraseña &laquo;malcolm45&raquo;. Este complemento podría devolver si el <i>tablespace</i> está correcto, el número de consultas por segundo, la carga, el nivel de fragmentación, y el uso de memoria.
<br><br>
Este complemento tiene la siguiente interfaz:
<br>
<pre>
	informix_plugin_pandora -H dirección_ip -U usuario -P contraseña -T tablespace -O operación
</pre>
<br>
La operación podría ser &laquo;status&raquo;, &laquo;qps&raquo;, &laquo;load&raquo;, &laquo;fragment&raquo; y &laquo;memory&raquo;. Devuelve un solo valor, que usa Pandora FMS. Para definir este complemento en Pandora FMS, debe rellenar los campos como sigue:
<br><br>

<table cellpadding=4 cellspacing=4 class=databox width=80%>
<tr>
<td valign='top'>Comando del complemento<td>/usr/share/pandora/util/plugins/informix_plugin_pandora (ubicación predeterminada para los complementos)
</tr>
<tr>
<td>Máx. tiempo de expiración<td> 15 (por ejemplo).
<tr>
<td>Dirección IP<td> -H
</tr>

<tr>
<td>Opción de puerto<td> Déjela en blanco.
</tr>

<tr>
<td>Opción de usuario<td> -U
</tr>

<tr>
<td>Opción de contraseña<td> -P
</tr>

</table>
<br>

Si necesita crear un módulo que use este complemento, deberá elegir el complemento (este nuevo complemento aparecerá en la caja de combinación para poder seleccionarse). Después, sólo debe rellenar los campos necesarios IP destino, Usuario y Contraseña. Pandora FMS pondrá estos datos en los campos apropiados al ejecutar el complemento externo.
<br><br>
Siempre hay algunos parámetros que no pueden ser &laquo;genéricos&raquo;, en este caso está el parámetro &laquo;tablespace&raquo;. Éste es muy particular para Informix, pero cada ejemplo puede tener su propia excepción. Podría tener un campo llamado &raquo;Parámetros del complemento&raquo; usado para pasar parámetros &laquo;tal cual&raquo; al complemento. En este caso en particular podría poner &laquo;-T tablespace&raquo; en él.
<br><br>
Si quiere usar otra tabla distinta, simplemente cree otro módulo con una cadena diferente después de &laquo;-T&raquo;.
<br><br>
Por supuesto, en el campo &laquo;Parámetros del complemento&raquo; puede introducir más de un parámetro todos los datos introducidos en el campo se pasan al complemento &laquo;tal cual&raquo;.