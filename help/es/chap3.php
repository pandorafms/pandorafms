<?php
// Pandora - The Free Monitoring System
// This code is protected by GPL license.
// Este codigo esta protegido por la licencia GPL.
// Sancho Lerena <slerena@gmail.com>, 2003-2006
// Raul Mateos <raulofpandora@gmail.com>, 2004-2006
?>
<html>
<head>
<title>Pandora - Sistema de monitorizaci&oacute;n de Software Libre - Ayuda - III. Agentes</title>
<link rel="stylesheet" href="../../include/styles/pandora.css" type="text/css">
<style>
.ml15 {margin-left: 15px;}
.ml25 {margin-left: 25px;}
.ml35 {margin-left: 35px;}
.ml75 {margin-left: 75px;}
div.logo {float:left;}
div.toc {padding-left: 200px;}
div.rayah {clear:both; border-top: 1px solid #708090; width: 100%;}
</style>

<div class='logo'>
<img src="../../images/logo_menu.gif" alt='logo'><h1>Ayuda de Pandora v1.2</h1>
</div>
<div class="toc">
<h1><a href="chap2.php">2. Usuarios</a> « <a href="toc.php">&Iacute;ndice</a> » <a href="chap4.php">4. Gesti&oacute;n de incidentes</a></h1>

</div>
<div class="rayah">
<p align='right'>Pandora es un proyecto de software GPL. &copy; Sancho Lerena 2003-2006, David Villanueva 2004-2006 y Ra&uacute;l Mateos 2004-2006.</p>
</div>

<h1><a name="3">3. Agentes</a></h1>

<p>Los agentes son los recolectores
de informaci&oacute;n, una vez se han instalado en la m&aacute;quina que se va a monitorizar
y se ha copiado la clave publica en el Servidor de Pandora &eacute;ste empezara a
recibir y procesar los datos que el agente recoge. Estos datos se denominan m&oacute;dulos.</p>

<p>Cada m&oacute;dulo tiene el valor de la
variable que monitoriza. Para que estos datos se consoliden en la base de
datos y se puedan tratar, hay que dar de alta el Agente en el Servidor de
Pandora y asignarle a un grupo definido en Pandora.</p>

<p>Para cada agente se permite:</p>

<ul>
<li>Ver el estado</li>
<li>Acceder a la informaci&oacute;n reportada</li>
<li>Acceder a cada caracter&iacute;stica monitorizada y ver su evoluci&oacute;n a lo largo del tiempo</li>
<li>Ver informes gr&aacute;ficos</li>
<li>Configurar alertas</li>
</ul>

<h2><a name="31">3.1. Gesti&oacute;n de grupos</a></h2>

<p>La creaci&oacute;n de grupos en Pandora se realiza desde «Gesti&oacute;n de perfiles» &gt; «Gesti&oacute;n de grupos», en el men&uacute;
de administraci&oacute;n.</p>

<p class="center"><img src="images/image007.png"></p>

<p>En esta pantalla se encuentran todos los grupos existentes, hay nueve grupos creados por defecto:</p>

<ul>
<li><b>All</b> (todos los grupos)</li>
<li><b>Applications</b></li>
<li><b>Comms</b></li>
<li><b>Databases</b></li>
<li><b>Firewall</b></li>
<li><b>IDS</b></li>
<li><b>Others</b></li>
<li><b>Servers</b></li>
<li><b>Workstations</b></li>
</ul>

<p>Se pueden crear todos los grupos que se necesiten pulsando en «Crear grupo» y asign&aacute;ndole un nombre.</p>

<p>Para borrar un grupo se pulsa en
el icono <img src="../../images/cancel.gif"> que cada grupo tienen a su derecha. No se recomienda borrar el grupo «<i>All</i>».</p>

<h2><a name="32">3.2. A&ntilde;adir un agente</a></h2>

<p>Una vez se han copiado en el
servidor de Pandora la clave p&uacute;blica de la m&aacute;quina que se quiere monitorizar y
se ha ejecutado el agente de Pandora, para que los datos empiecen a
consolidarse en la Base de Datos y se pueda acceder a los mismos, se necesita
a&ntilde;adir el agente a trav&eacute;s de la consola Web.</p>

<p>Para a&ntilde;adir un agente accedemos a «Gesti&oacute;n de agentes» &gt; «Crear agente», desde el men&uacute; de administraci&oacute;n.</p>

<p class="center"><img src="images/image008.png"></p>

<p>Para crear un agente se deben de configurar los siguientes datos:</p>

<ul>
<li><b>Nombre del agente:</b> <b>Tiene que coincidir</b> con el nombre que se ha configurado en la
variable «<code>agent name</code>» en el archivo <code>agent.conf</code> que hay en cada agente.
En el caso en el que esta variable est&eacute; comentada se
utilizara el nombre de Host de la m&aacute;quina donde se est&aacute; ejecutando el agente
(&eacute;ste se obtiene ejecutando el comando <i>hostname</i>).
<li><b>Direcci&oacute;n IP:</b> Muestra la IP del agente, es un dato a t&iacute;tulo informativo y puede coincidir en varios Agentes.</li>
<li><b>Grupo:</b> Define el grupo al que pertenece el agente, dentro de los grupos definidos en Pandora.</li>
<li><b>Intervalo:</b> Intervalo de ejecuci&oacute;n que tiene el agente. Es el tiempo que pasa desde que se ejecuta.</li>
<li><b>SO:</b> Define el Sistema Operativo que se quiere monitorizar
dentro de las siguientes opciones: AIX, BeOS, BSD, Cisco, HPUX, GNU/Linux, MacOS, Other, Solaris o Windows.</li>
<li><b>Descripci&oacute;n:</b> Breve descripci&oacute;n del agente</li>
<li><b>Definici&oacute;n de m&oacute;dulos:</b> Existen dos modos de definir un m&oacute;dulo:</li>
<p class="ml15">- <i><b>Modo aprendizaje:</b></i> Se admiten todos los m&oacute;dulos que env&iacute;e el agente 
y se definen autom&aacute;ticamente en el sistema.
Al principio es m&aacute;s c&oacute;modo dar de alta los agentes en este modo y posteriormente desactivar el modo aprendizaje.</p>
<p class="ml15">- <i><b>Modo normal:</b></i> Se deben
configurar los m&oacute;dulos que se aceptaran de forma manual. No permite la
autodefinici&oacute;n de ning&uacute;n m&oacute;dulo.</li>

<li><b>Estado:</b> Define si el agente est&aacute; activado y listo para enviar
datos o si est&aacute; desactivado. Los agentes desactivados no se ven en las vistas
de usuario.</li>
</ul>

<h3><a name="321">3.2.1. Asignaci&oacute;n de m&oacute;dulos</a></h3>

<p>Los agentes de Pandora utilizan
comandos propios del sistema operativo para monitorizar un dispositivo. Estos
comandos producen una salida que el servidor de Pandora procesa e inserta en la
Base de Datos. Estos comandos utilizados en los agentes se denominan m&oacute;dulos.</p>

<p>Si el agente se ha a&ntilde;adido en <i>modo normal</i>, se deben de asignar los m&oacute;dulos que queremos monitorizar. Estos
m&oacute;dulos deben de estar configurados en el archivo de configuraci&oacute;n del agente.</p>

<p>Para asignarle los m&oacute;dulos, que
el servidor de Pandora procesar&aacute;, accedemos a «Gesti&oacute;n de agentes» en el men&uacute; de administraci&oacute;n.
En est&aacute; p&aacute;gina aparece una lista con todos los agentes que hay en Pandora.

<p>Al pulsar sobre el agente que se
quiere modificar aparecen los datos que se han configurado al crear el agente.
Desde aqu&iacute; se crear&aacute;n los m&oacute;dulos desde el formulario de asociaci&oacute;n de m&oacute;dulos.</p>

<p class="center"><img src="images/image009.png"></p>

<p>Para crear un m&oacute;dulo se deben completar los siguientes campos:</p>

<ul>
<li><b>Tipo de m&oacute;dulo:</b> En este campo se definen el tipo del dato que Pandora va a procesar. Hay 5 tipos de datos:</li>
<p class="ml15">
- <b><code>generic_data</code></b>, tipo num&eacute;rico de datos enteros.<br>
- <b><code>generic_data_inc</code></b>, tipo num&eacute;rico de datos enteros incrementales.<br>
- <b><code>generic_data_proc</code></b>, tipo num&eacute;rico booleano: 0 Falso, &gt;0 Verdadero.<br>
- <b><code>generic_data_string</code></b>, tipo alfanum&eacute;rico de datos (cadena de texto, m&aacute;ximo 255 caracteres).
</p>
<li><b>Nombre del m&oacute;dulo:</b> Define el nombre del m&oacute;dulo.</li>
<li><b>M&aacute;ximo</b>: Define el valor m&aacute;ximo que se aceptara para este dato.
Cualquier valor por encima del maximo definido se interpretar&aacute; como un dato no v&aacute;lido y se descartar&aacute; el m&oacute;dulo completo.</li>
<li><b>M&iacute;nimo</b>: Define el valor m&iacute;nimo que se aceptara para este dato.
Cualquier valor por debajo del m&iacute;nimo definido se interpretar&aacute; como un dato no v&aacute;lido y se descartar&aacute; el m&oacute;dulo completo.</li>
<li><b>Comentario:</b> Campo para incluir un comentario al m&oacute;dulo.</li>
</ul>

<p>Todos los m&oacute;dulos monitorizados por un agente se pueden ver accediendo al agente en «Gesti&oacute;n de agentes», men&uacute; de administraci&oacute;n.</p>

<p class="center"><img src="images/image010.png"></p>

<p>Desde aqu&iacute; se puede:</p>
<ul>
<li>borrar un m&oacute;dulo pinchando en el icono <img src="../../images/cancel.gif"></li>
<li>o editarlo, pinchando en el icono <img src="../../images/config.gif"></li>
</ul>

<p>No ser&aacute; posible modificar el tipo de dato del m&oacute;dulo.</p>

<h3><a name="322">3.2.2. Alertas</a></h3>

<p>Una alerta es la acci&oacute;n que tomar&aacute; Pandora cuando el valor de un m&oacute;dulo se encuentre fuera de un rango configurado previamente.</p>

<p>Esta acci&oacute;n puede ser desde
enviar un mail o un SMS, enviar un <i>trap</i> de SNMP, escribir el suceso en
el <i>syslog</i> del sistema, o en archivo de <i>log</i> de Pandora, etc.
En definitiva, se podr&aacute; ejecutar cualquier script configurado en el Sistema
Operativo de Pandora.</p>

<h4><a name="3221">3.2.2.1. Creaci&oacute;n de alertas</a></h4>

<p>Para acceder a las alertas creadas lo hacemos desde la opci&oacute;n de «Gesti&oacute;n de alertas» dentro del men&uacute; de administraci&oacute;n.</p>
<p>Por defecto hay definidos 6 ejemplos de alertas:</p>

<ul>
<li><b>eMail</b>. Env&iacute;a un email desde el servidor de Pandora</li>
<li><b>Internal audit</b>. Escribe en el log en el sistema de auditor&iacute;a interna de Pandora</li>
<li><b>LogFile</b>. Escribe en el log </code>/var/log/pandora_alert.log</code>.</p></li>
<li><b>SMS Text</b>. Env&iacute;a un SMS al n&uacute;mero m&oacute;vil elegido</li>
<li><b>SNMP Trap</b>. Env&iacute;a un Trap SNMP</li>
<li><b>Syslog</b>. Env&iacute;a la alerta a un Syslog</p>
</ul>

<p class="center"><img src="images/image011.png"></p>

<p>Para borrar una alerta pulsamos en <img src="../../images/cancel.gif"> que se encuentra a la derecha de la alerta que se quiere borrar.</p>

<p>Para crear una alerta personalizada se accede a «Gesti&oacute;n de alertas» &gt; «Crear alertas», en el men&uacute; de administraci&oacute;n</p>

<p>En las alertas ya configuradas los valores «<code>_field1_»</code>, «<code>_field2_</code>» y «<code>_field3_</code>»
se utilizan como variables para construir el comando que ejecutar&aacute; la m&aacute;quina
donde est&eacute; el servidor de Pandora (si hay varios, en el servidor en modo Master).</p>
<p class="center"><img src="images/image012.png"></p>

<p>Al crear una alerta, hay que rellenar los siguientes campos:</p>
<ul>
<li><b>Nombre de la alerta:</b> Un nombre descriptivo para la alerta</li>
<li><b>Comando:</b> Comando que ejecutar&aacute; la alerta</li>
<li><b>Descripc&oacute;n:</b> Descripci&oacute;n de la alerta</li>
</ul>

<h4><a name="3222">3.2.2.2. Asignaci&oacute;n de alertas</a></h4>
<p>Una vez se ha a&ntilde;adido el agente,
se han configurado los m&oacute;dulos y se han creado las alertas que se quieren
generar, se asignan estas alertas al agente correspondiente.</p>
<p>Desde «Gesti&oacute;n de agentes», desde el men&uacute; de
administraci&oacute;n, seleccionamos el agente que se quiere configurar. Al final de
la p&aacute;gina de configuraci&oacute;n est&aacute; la secci&oacute;n «Formulario de asociaci&oacute;n de alerta».</p>

<p class="center"><img src="images/image013.png"></p>

<p>Para asignar una alerta hay que rellenar los siguientes campos:</p>

<ul>
<li><b>Tipo de Alerta:</b> Se elige la alerta que se generar&aacute; dentro de las alertas que hay definidas.</li>
<li><b>Valor M&aacute;ximo: </b>Define el valor m&aacute;ximo v&aacute;lido para un m&oacute;dulo. Cualquier valor por encima de &eacute;l, disparar&aacute; la alerta.</li>
<li><b>Valor M&iacute;nimo: </b>Define el valor m&iacute;nimo v&aacute;lido para un m&oacute;dulo. Cualquier valor por debajo de &eacute;l, disparar&aacute; la alerta.</li>
<li><b>Descripci&oacute;n: </b>Describe la funci&oacute;n de la alerta, es &uacute;til para poder discriminarlo en la vista general de alertas.</li>
<li><b>Campo #1 (Alias nombre):</b> Define el valor utilizado para la variable «<code>_field1_</code>».</li>
<li><b>Campo #2 (L&iacute;nea sencilla):</b> Define el valor utilizado para la variable «<code>_field2_</code>».</li>
<li><b>Campo #3 (Texto completo):</b> Define el valor utilizado para la variable «<code>_field3_</code>».</li>
<li><b>Umbral de tiempo</b>: Tiempo que ha de pasar desde que se dispara una alerta hasta que se puede disparar otra.</li>
<li><b>Max Alerts Fired</b>: Define el n&uacute;mero m&aacute;ximo de alertas que se env&iacute;an de forma continua.</li>
<li><b>M&oacute;dulo Asignado</b>: Define el m&oacute;dulo que ser&aacute; monitorizado por la alerta.</li>
</ul>

<p>Todas las alertas creadas para un agente se pueden ver accediendo al agente, en «Gesti&oacute;n de agentes» desde el men&uacute; de administraci&oacute;n.</p>

<h3><a name="323">3.2.3. Gesti&oacute;n de m&oacute;dulos y alertas de agentes</a></h3>

<p>En muchos casos un usuario se
encontrara con el caso de que m&oacute;dulos y alertas configuradas en un agente se
repiten en otro agente que se quiere a&ntilde;adir. </p>
<p>Con el fin de simplificar el
trabajo de un administrador, dentro de Pandora se pueden copiar m&oacute;dulos y
alertas definidas en un agente existente sobre otro agente.</p>
<p>Accedemos a «Gesti&oacute;n de agentes» &gt; «Gestionar configuraci&oacute;n», men&uacute; de administraci&oacute;n:</p>

<p class="center"><img src="images/image014.png"></p>

<p>Desde Agente origen se accede al
agente del cual se quieren copiar los m&oacute;dulos y/o alertas. Pinchando en
«Obtener info.» se obtienen todos los m&oacute;dulos que tiene el agente elegido.</p>

<p><b><i>El proceso de copia</i></b> se realiza para copiar la configuraci&oacute;n de los
m&oacute;dulos y/o la configuraci&oacute;n de las alertas desde el agente seleccionado como
origen hacia los agentes seleccionados como destino. Pueden seleccionarse
varios agentes, utilizando el CTRL y el rat&oacute;n. Hay que marcar las casillas de «alertas» y/o «m&oacute;dulos» para realizar una copia de los elementos seleccionados.</p>

<p><b><i>El proceso de borrado</i></b> se realiza sobre los agentes seleccionados
como «destino», en la casilla de selecci&oacute;n m&uacute;ltiple. Se pueden seleccionar
varios agentes, indicando si queremos eliminar la configuraci&oacute;n de los m&oacute;dulos,
de las alertas o todo el conjunto. Se pedir&aacute; confirmaci&oacute;n antes de ejecutar
dado que al eliminar los m&oacute;dulos asignados a un agente se eliminar&aacute; tambi&eacute;n
todos los datos asociados a &eacute;stos.</p>

<h2><a name="33">3.3. Monitorizaci&oacute;n de agentes</a></h2>

<p>Una vez el agente empieza a
enviar datos al servidor de Pandora, y es a&ntilde;adido en la consola Web, &eacute;ste los
procesa e inserta los datos en la Base de Datos. Estos datos se consolidan y
son accesibles desde la consola Web ya sea el dato en bruto o mediante
gr&aacute;ficas.</p>

<h3><a name="331">3.3.1. Ver agentes</a></h3>

<p>Desde la opci&oacute;n «Ver agentes» en el
men&uacute; de operaci&oacute;n se tiene acceso a todos los agentes. Desde aqu&iacute; se ve
r&aacute;pidamente estado en que esta cada uno de los agentes simplemente con una
r&aacute;pida mirada, gracias a un sistema de bombillas y c&iacute;rculos coloreados.</p>

<p class="center"><img src="images/image015.png"></p>

<p>En la lista, los agentes aparecen ordenados con las siguientes columnas:</p>

<p><b>Agente</b>: Muestra el nombre del agente.</p>
<p><b>SO</b>: Muestra un icono representante del Sistema Operativo.</p>
<p><b>Intervalo</b>: Muestra el intervalo de tiempo (segundos) en el que el agente env&iacute;a datos.</p>
<p><b>Grupo</b>: Muestra el grupo al que pertenece el agente.</p>
<p><b>M&oacute;dulos</b>: En estado normal aparecen dos valores el n&uacute;mero de
m&oacute;dulos y el n&uacute;mero de monitores, ambos de color negro. Si hay alg&uacute;n monitor en
estado incorrecto, aparecen tres valores: el n&uacute;mero de m&oacute;dulos, el n&uacute;mero de
monitores y el n&uacute;mero de monitores con estado incorrecto, &eacute;ste &uacute;ltimo en color rojo, el resto de color negro.</p>
<p><b>Estado</b>: Muestra el estado «general» del agente mediante los siguientes iconos:</p>
	<div class="ml35">
		<p><img src="../../images/b_green.gif"> Cuando todos los monitores est&aacute;n OK. El estado ideal.</p>
		<p><img src="../../images/b_white.gif"> Cuando no hay monitores definidos. A veces no monitorizamos nada que pueda estar «bien» o «mal», y simplemente reporta datos
num&eacute;ricos o de tipo texto.</p>
		<p><img src="../../images/b_red.gif"> Cuando al menos un monitor falla. Generalmente queremos evitar esto y que todos nuestros sistemas tengan un saludable color verde.</p>
		<p><img src="../../images/b_blue.gif"> Cuando el agente no tiene datos. Los agentes nuevos con un paquete vac&iacute;o pueden tener este estado.</p>
		<p><img src="../../images/b_yellow.gif"> Cuando hay un cambio entre verde y rojo. Esto indica que un agente acaba de cambiar de estado, de «todo bien» a «tenemos un problema».</p>
		<img src="../../images/b_down.gif"> Cuando el agente est&aacute; ca&iacute;do o no se ha recibido informaci&oacute;n de &eacute;l en el doble del Intervalo en segundos. Generalmente se debe a un
problema de comunicaci&oacute;n o a un «cuelgue» del sistema remoto.</p>
	</div>
<p><b>Alertas:</b> Muestra si se han enviado alertas mediante los siguientes iconos:</p>
		<div class="ml35">
		<p><img src="../../images/dot_green.gif"> Cuando no se ha enviado ninguna alerta.</p>
		<p><img src="../../images/dot_red.gif"> Cuando se ha enviado al menos una alerta en el per&iacute;odo definido como «time threshold» o «umbral de tiempo» en la alerta.</p>
		</div>
<p><b>&Uacute;ltimo contacto</b>: Muestra la fecha y hora en que se recibieron los &uacute;ltimos datos del agente.</p>

<p><b><u>Nota:</u></b> El icono <img src="../../images/setup.gif" width="15"> s&oacute;lo es visible si es usuario es administrador y es un enlace a la opci&oacute;n «Gestionar agentes» &gt; «Actualizar agente» del men&uacute; de administraci&oacute;n.</p>

<h3><a name="332">3.3.2. Acceso a los datos de un agente concreto</a></h3>

<p>Al acceder a un agente concreto, pinchando en el nombre del agente, se ven todos los datos relacionados con dicho agente.</p>

<h4><a name="3321">3.3.2.1. Informaci&oacute;n general de un agente</a></h4>

<p>Informa sobre los datos facilitados al crear el agente y el n&uacute;mero de paquetes totales que ha enviado el agente.</p>

<p class="center"><img src="images/image016.png"></p>

<h4><a name="3322">3.3.2.2. Muestra de &uacute;ltimos datos obtenidos</a></h4>

<p>Descripci&oacute;n de todos los m&oacute;dulos monitorizados por el agente.</p>

<p class="center"><img src="images/image017.png"></p>

<p>En la lista, los m&oacute;dulos aparecen ordenados con las siguientes columnas:</p>

<p><b>Nombre de m&oacute;dulo</b>: Nombre asignado al m&oacute;dulo en el archivo de configuraci&oacute;n del agente.</p>
<p><b>Tipo de m&oacute;dulo</b>: Tipo de m&oacute;dulo, seg&uacute;n los valores definidos <a href="#321"> en el punto
3.2.1.</a></p>
<p><b>Descripci&oacute;n</b>: Descripci&oacute;n del m&oacute;dulo configurado en el archivo de configuraci&oacute;n del agente.</p>
<p><b>Datos</b>: &Uacute;ltimo dato enviado por el agente.</p>
<p><b>Gr&aacute;fico</b>: A partir de los datos envidados por el agente a lo
largo del tiempo se generan una serie de gr&aacute;ficas mensuales, semanales, diarias
y horarias (M, W, D y H, respectivamente).</p>

<p>En la parte izquierda de la gr&aacute;fica se sit&uacute;an los datos m&aacute;s nuevos, mientras que en la parte derecha est&aacute;n
los datos m&aacute;s antiguos.</p>

<p>La gr&aacute;ficas que se generan son:</p>
<p class="ml75"> - <b>Gr&aacute;fico horario</b> (<img src="../../images/grafica_h.gif">) con un rango total de 60 minutos</p>
<p class="center"><img src="images/image018.png"></p>

<p class="ml75"> - <b>Gr&aacute;fico diario</b> (<img src="../../images/grafica_d.gif">) con un rango total de 24 horas</p>
<p class="center"><img src="images/image019.png"></p>

<p class="ml75"> - <b>Gr&aacute;fico semanal</b> (<img src="../../images/grafica_w.gif">) con un rango total de 7 d&iacute;as</p>
<p class="center"><img src="images/image020.png"></p>

<p class="ml75"> - <b>Gr&aacute;fico mensual</b> (<img src="../../images/grafica_m.gif">) con un rango total de 30 d&iacute;as</p>
<p class="center"><img src="images/image021.png"></p>

<p><b>Datos</b>: Son, en bruto, los datos enviados por el agente:</p>

<p class="ml25"> - <img src="../../images/data_m.gif"> El &uacute;ltimo mes</p>
<p class="ml25"> - <img src="../../images/data_w.gif"> La &uacute;ltima semana</p>
<p class="ml25"> - <img src="../../images/data_d.gif"> El &uacute;ltimo d&iacute;a</p>

<h4><a name="3323">3.3.2.3. Lista completa de monitores</a></h4>

<p>Descripci&oacute;n de todos los monitores definidos en el agente</p>

<p class="center"><img src="images/image022.png"></p>

<p>En la lista, los monitores aparecen ordenados con las siguientes columnas:</p>

<p><b>Agente;</b> Agente donde est&aacute; definido el monitor.</p>
<p><b>Tipo:</b> Tipo de dato del monitor. Al ser un monitor est&eacute; ser&aacute; siempre de tipo.</p>
<p><b>Nombre del m&oacute;dulo:</b> Nombre definido al crear el m&oacute;dulo.</p>
<p><b>Descripci&oacute;n:</b> Descripci&oacute;n del m&oacute;dulo configurado en el archivo de configuraci&oacute;n del agente.</p>
<p><b>Estado:</b> Aparece el estado del agente mediante los siguientes iconos:</p>

<p class="ml25"><img src="../../images/b_green.gif"> Cuando el monitor est&aacute; correcto</p>
<p class="ml25"><img src="../../images/b_red.gif"> Cuando el monitor falla</p>

<p><b>&Uacute;ltimo contacto</b>: Aparece la fecha y hora en que se recibieron los &uacute;ltimos datos del agente.</p>

<h4><a name="3324">3.3.2.4. Lista completa de alertas</a></h4>

<p>Nos muestra la descripci&oacute;n de todas las alarmas definidas en el agente.</p>

<p class="center"><img src="images/image023.png"></p>

<p>En la lista, los monitores aparecen ordenados con las siguientes columnas:</p>

<p><b>ID:</b> Agente donde est&aacute; definida la alerta.</p>
<p><b>Tipo:</b> Tipo de la alerta.</p>
<p><b>Descripci&oacute;n:</b> Descripci&oacute;n de la alerta definida cuando se cre&oacute;.</p>
<p><b>Lanzada por &uacute;ltimo vez:</b> &Uacute;ltima vez que se ejecut&oacute; la alerta.</p>
<p><b>N&uacute;mero de veces lanzada:</b> N&uacute;mero de veces que se ha lanzado la alerta.</p>
<p><b>Estado:</b> Muestra si se han enviado alertas mediante los siguientes iconos:</p>

<p class="ml25"><img src="../../images/dot_green.gif"> Cuando no se ha enviado ninguna alerta</p>
<p class="ml25"><img src="../../images/dot_red.gif"> Cuando se ha enviado al menos una alerta</p>

<h3><a name="333">3.3.3. Detalle de grupos</a></h3>

<p>Desde «Ver agentes» &gt; «Detalle de grupos» en el men&uacute; de operaci&oacute;n se accede a los grupos configurados en Pandora.
Desde aqu&iacute; se ve r&aacute;pidamente el estado en que est&aacute;n cada uno de los grupos
simplemente con una r&aacute;pida mirada, gracias a un sistema de bombillas de distintos colores.</p>

<p class="center"><img src="images/image025.png"></p>

<p>En la lista, los monitores aparecen ordenados con las siguientes columnas:</p>

<p><b>Grupos:</b> Nombre del grupo.</p>
<p><b>Agente:</b> N&uacute;mero de agentes configurados en el grupo.</p>
<p><b>Monitores:</b> N&uacute;mero de monitores configurados en el grupo.</p>
<p><b>Estado:</b> Estado del agente, mediante los siguientes iconos:</p>

<p class="ml25"><img src="../../images/b_green.gif"> Aparece cuando todos los monitores est&aacute;n correctos.</p>
<p class="ml25"><img src="../../images/b_red.gif"> Aparece cuando al menos un monitor tiene alg&uacute;n fallo.</p>
<p class="ml25"><img src="../../images/b_down.gif"> Aparece cuando al menos un monitor est&aacute; ca&iacute;do o no hay contacto.</p>
<p class="ml25"><img src="../../images/b_white.gif"> Aparece cuando el agente no tiene ning&uacute;n monitor definido.</p>

<p><b>Ok:</b> N&uacute;mero de monitores que est&aacute;n correctos.</p>
<p><b>Fallo:</b> N&uacute;mero de monitores que tienen alg&uacute;n fallo</p>
<p><b>Ca&iacute;dos:</b> N&uacute;mero de monitores que est&aacute;n ca&iacute;dos.</p>

<h3><a name="334">3.3.4. Ver monitores</a></h3>

<p>Desde «Ver agentes» &gt; «Detalle monitores» en el men&uacute; de operaci&oacute;n, se puede ver la descripci&oacute;n de todos los monitores definidos en el servidor.</p>

<p class="center"><img src="images/image026.png"></p>

<p>En la lista, los monitores aparecen ordenados de forma similar a la vista individual de cada agente,
excepto que ahora aparecen todas juntas, esto permite un an&aacute;lisis m&aacute;s pormenorizado de cada monitor.</p>

<h3><a name="335">3.3.5. Detalle de alertas</a></h3>

<p>Desde «Ver agentes» &gt; «Detalle alertas» en el men&uacute; de operaci&oacute;n, se puede ver la descripci&oacute;n de todas las alarmas definidas en el servidor.</p>
<p class="center"><img src="images/image027.png"></p>

<p>En la lista, las alertas aparecen ordenadas de forma similar a la vista individual de cada agente, excepto que
ahora aparecen todas juntas, esto permite un an&aacute;lisis m&aacute;s pormenorizado de cada alerta.</p>

<h3><a name="336">3.3.6. Exportar datos</a></h3>

<p>Desde «Ver agentes» &gt; «Exportar Datos» en el men&uacute; de operaci&oacute;n, se puede acceder a una herramienta que permite exportar datos de un agente.</p>
<p>Se puede configurar el agente desde el que se van a exportar los datos, los m&oacute;dulos dentro de dicho agente y
el rango de fechas del que exportaremos los datos.</p>

<p class="center"><img src="images/image028.png"></p>

<p>Estos datos pueden exportarse ordenados en tres columnas:</p>

<p><b>M&oacute;dulo:</b> Nombre del m&oacute;dulo.</p>
<p><b>Datos:</b> Informaci&oacute;n obtenida por el m&oacute;dulo.</p>
<p><b>Timestamp:</b> Fecha y hora en que el agente remoto de Pandora emiti&oacute; el paquete.</p>

<p class="center"><img src="images/image029.png"></p>

<p>Si se selecciona el formato CVS se obtendr&aacute;n los datos separados por comas, para poder exportar a un fichero de texto.</p>

<p class="center"><img src="images/image030.png"></p>

<h3><a name="337">3.3.7. Estad&iacute;sticas</a></h3>

<p>Desde «Ver agentes» &gt; «Estad&iacute;sticas» en el men&uacute; de operaci&oacute;n se accede a dos tipos de estad&iacute;sticas gr&aacute;ficas de los agentes:</p>

<ul>
<li>Una gr&aacute;fica con el n&uacute;mero de m&oacute;dulos que tiene configurado cada uno de los agentes.</li>
<li>Una gr&aacute;fica con el n&uacute;mero de paquetes enviado por cada
uno de los agentes, siendo un paquete el conjunto de datos relacionados con los
m&oacute;dulos que env&iacute;a un agente cada intervalo de tiempo.</li>
</ul>
<p class="center"><img src="images/image031.png"></p>
<p class="center"><img src="images/image032.png"></p>
<p class="center"><img src="images/image033.png"></p>

</body>
</html>