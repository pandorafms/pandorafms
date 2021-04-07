<?php
/**
 * @package Include/help/en
 */
?>
<h1>Gestión de asignación de direcciones IP (IPAM)</h1>
<br>
Con la extensión IPAM podremos descubrir los hosts de una subred y detectar sus cambios de disponibilidad (si responden a ping o no) o nombre de host (obtenido mediante dns). Además podremos detectar su sistema operativo. La extensión de IPAM utiliza un recon script (dependiente del recon server) para realizar toda la lógica que hay por debajo. La gestión de IP's es <i>independiente</i> de que tenga o no agentes instalados en esas máquinas o un agente con monitores remotos sobre esa IP. Opcionalmente puede "asociar" un agente a la IP y gestionar esa IP, pero no afecta a la monitorización que esté realizando.

<h2>Detección de IP's</h2>
Podemos configurar una red (mediante una red y una máscara de red) para que se ejecute cada cierto tiempo el reconocimiento de sus direcciones o bien que únicamente se haga manualmente. Este mecanismo utiliza por debajo el recon server, pero lo gestiona automáticamente. <br><br>

En ambos casos podremos forzar el reconocimiento y observar el porcentaje completado en la barra de progreso.

<h2>Vistas</h2>
La operación y administración de las direcciones de una subred están separadas en dos tipos de vistas: Vistas de iconos y vista de edición.

<h3>Vista de iconos</h3>
Con esta vista veremos información de la subred, incluyendo estadísticas del porcentaje y número de direcciones usadas (marcadas como administradas).<br><br>
Además podremos exportar la lista a formato Excel (CSV)<br><br>
Las direcciones se mostrarán en forma de icono, pudiendo elegir entre dos tamaños: Pequeños (por defecto) y Grandes.<br><br>
Cada dirección tendrá un icono grande que nos aportará información:<br><br>
<table width=100%>
<tr>
<th colspan=3>Administrado</th>
</tr>
<tr>
<th>Configuración</th>
<th>Host vivo</th>
<th>Host no responde</th>
</tr>
<tr>
<td>Sin agente asignado<br><br>Eventos desactivados</td>
<td class="center"><img src="../enterprise/images/ipam/green_host.png"></td>
<td class="center"><img src="../enterprise/images/ipam/red_host.png"></td>
</tr>
<tr>
<td>Con agente asignado<br><br>Eventos desactivados</td>
<td class="center"><img src="../enterprise/images/ipam/green_host_agent.png"></td>
<td class="center"><img src="../enterprise/images/ipam/red_host_agent.png"></td>
</tr>
<tr>
<td>Sin agente asignado<br><br>Eventos activados</td>
<td class="center"><img src="../enterprise/images/ipam/green_host_alert.png"></td>
<td class="center"><img src="../enterprise/images/ipam/red_host_alert.png"></td>
</tr>
<tr>
<td>Con agente asignado<br><br>Eventos activados</td>
<td class="center"><img src="../enterprise/images/ipam/green_host_agent_alert.png"></td>
<td class="center"><img src="../enterprise/images/ipam/red_host_agent_alert.png"></td>
</tr>
<tr>
<th colspan=3>Sin administrar</th>
</tr>
<tr>
<th>Configuración</th>
<th>Host vivo</th>
<th>Host no responde</th>
</tr>
<tr>
<td class="w100px">Independientemente de la configuración, si el host no está administrado solo se diferenciará entre si está vivo y no responde</td>
<td class="center"><img src="../enterprise/images/ipam/green_host_dotted.png"></td>
<td class="center"><img src="../enterprise/images/ipam/not_host.png"></td>
</tr>
<tr>
<th colspan=3>No asignado</th>
</tr>
<tr>
<td colspan=3>El icono tiene de color de fondo un azul claro cuando no esta asignado.</td>
</tr>
</table>
<br><br>
Cada dirección tendrá en la parte inferior derecha un enlace para editarla, si disponemos de privilegios suficientes. Así mismo, en la parte inferior izquierda, tendrá un pequeño icono indicando el sistema operativo asociado. 
En el caso de las direcciones desactivadas, el icono del sistema operativo se verá sustituido por el siguiente icono:<br><br><img src="../images/delete.png" class="w18px"><br><br>
Si hacemos click en el icono principal, se abrirá una ventana modal con toda la información de la IP, incluyendo agente y sistema operativo asociados, configuración y el seguimiento de cuando se creó, editó por el usuario o fue chequeado por el servidor por última vez. En esta vista también se podrá hacer un ping a dicha dirección*.<br><br>
<b>* El ping se realiza desde la máquina donde esté instalada la consola de <?php echo get_product_name(); ?>.</b>

<h3>Vista de edición</h3>
Si se tienen los permisos suficientes se podrá acceder a la vista de edición, donde las IPs aparecerán mostradas en forma de lista. Se podrá filtrar para mostrar las direcciones deseadas, hacer cambios 
en ellas y actualizar todas a la vez.<br><br>

Algunos campos, se rellenan automáticamente por el script de reconocimiento, como el nombre de host, el agente de <?php echo get_product_name(); ?> asociado y el sistema operativo. Podemos definir estos campos como manuales* y editarlos.<br><br>

<table width=100%>
<tr>
<th colspan=2>Cambio entre manual y automático</th>
</tr>
<tr>
<td class="tcenter w25px"><img src="../images/manual.png"></td>
<td><b>Modo manual</b>: Con este símbolo el campo no se actualizará desde el script de reconocimiento y podremos editarlo a mano. Al hacer click cambiaremos a modo automático.</td>
</tr>
<tr>
<td class="center w25px"><img src="../images/automatic.png"></td>
<td><b>Modo automático</b>:Con este símbolo el campo se actualizará desde el script de reconocimiento. Al hacer click cambiaremos a modo manual.</td>
</tr>
</table>
<br><br>
<b>*Los campos marcados como manuales no serán actualizados por el script de reconocimiento.</b><br><br>

Otros campos que podemos modificar son:
<ul>
<li>- Activar los eventos de una dirección. Cuando la disponibilidad de estas direcciones cambie (deje de responder o vuelva a hacerlo) o su nombre cambie, se generará un evento. <br><br>
<b>Cuando una dirección se crea la primera vez, siempre generará un evento.</b><br><br></li>
<li>- Marcar como <i>administrada</i> una dirección. Estas direcciónes serán las que reconocemos como asignadas en nuestra red. Podremos filtrar las IPs para solamente mostrar las que tengamos marcadas como administradas.<br><br></li>
<li>- Deshabilitar. Las IPs deshabilitadas no serán chequeadas por el script de reconocimiento.<br><br></li>
<li>- Comentarios. Un campo libre para añadir los comentarios que deseemos a cada dirección.</li>
</ul>

<h2>Filtros</h2>
En ambas vistas se podrán orenar por IP, Hostname y por la última vez que fueron chequeadas.<br><br>
Se podrá filtrar por una cadena libre que buscará subcadenas en la IP, Hostname o Comentarios. Activando el checkbox junto a la caja de búsqueda se hará una búsqueda exacta por IP.<br><br>
Por defecto los hosts que no responden no se muestran, pero se puede activar.<br><br>
También se pueden mostrar solamente las IPs que hayamos marcadas como administradas.

<h2>Calculadora de subredes</h2>
IPAM incluye una herramienta para calcular subredes IPV4 e IPv6.<br><br>
En dicha herramienta podremos, a partir de una dirección IP y la máscara de la red a la que pertenece, obtener información de dicha subred.<br><br>
La dirección de red y broadcasting, la primera y última IPs válidas de la subred y el número de hosts entre otras cosas, además de la posibilidad de verlas en formato binario para su mejor comprensión.
