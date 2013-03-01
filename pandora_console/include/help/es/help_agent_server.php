<?php
/**
 * @package Include/help/es
 */
?>
<h1>Campo servidor</h1>

En el campo “server” hay un combo donde se elige el servidor que realizará los chequeos.
Configuración en los servidores
<br><br>
En los servidores existen dos modos de trabajo:
<br><br>
<ul>
<blockquote>
<li>Modo maestro.
<li>Modo no-maestro. 
</ul>

<br>
La diferencia entre ellos, y la importancia que tienen para trabajar en modo HA consiste en que cuando existen varios servidores del mismo tipo, p.e: Network Servers, cuando un servidor cae, el primer servidor maestro que pueda, se hará cargo de los módulos de red pendientes de ejecutar del servidor caído. Los servidores no-maestros no realizan esta acción.
<br><br>
Esta opcion se configura en el fichero /etc/pandora/pandora_server.conf por medio del token de configuracion
<br><br><i>
master 1
<br><br></i>
Teniendo el valor 1 para activarlo y 0 para desactivarlo. 
