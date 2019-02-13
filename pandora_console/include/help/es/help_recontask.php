<?php
/**
 * @package Include/help/es
 */
?>
<h1>Tarea de reconocimiento (<i>recon</i>)</h1>

Para crear una tarea <i>recon</i> debe rellenar los campos necesarios para que la tarea se procese adecuadamente.<br><br>

<b>Nombre de Tarea</b><br>

Nombre de la tarea de descubrimiento, es puramente un valor descriptivo para diferenciar la tarea por si tiene varias con diferentes valores de filtrado o plantilla.<br><br>

<b>Servidor de exploración de red</b><br>

Servidor de reconocimiento asignado a la tarea. Si tiene varios servidores recon, aqui debe asignar cual de ellos quiere que realice la tarea de reconocimiento.<br><br>

<b>Modo</b><br>

Modo de la tarea a escoger entre "Barrido de red" y "Script personalizado". El primer modo es el modo convencional de tarea de reconocimiento de red, y la segunda es el modo en el que se asocia a la tarea un script personalizado.<br><br>

<b>Red</b><br>

Red sobre la que realizar la exploración. Utiliza el formato de red / mascara de bits <b>CIDR</b>. Por ejemplo, 192.168.1.0/24 se refiere a <i>toda la clase C 192.168.1.0</i>, que incluye todas las direcciones en el rango: 192.168.1.0 - 192.168.1.255.<br><br>

<b>Intervalo</b>
<br>
Intervalo de repetición de la búsqueda de equipos. No utilice intervalos muy cortos ya que recon explora una red enviando un Ping a cada dirección, si utiliza redes de exploracion muy amplias (por ejemplo una clase A) combinado con intervalos muy cortos (6 horas) estará provocando que <?php echo get_product_name(); ?> esté constantemente bombardeando la red con pings, cargandola e innecesariamente sobre cargando <?php echo get_product_name(); ?>.<br><br>

<b>Plantilla de módulos</b><br>

Plantilla de componentes que añadir a los equipos descubiertos. Cuando detecte un sistema que encaje con la espeficiación de esta tarea (OS, puertos) lo dará de alta y le asignará todos los módulos incluidos en la plantilla de componentes definida.<br><br>

<b>SO</b><br>

Sistema operativo para reconocer. Si se selecciona uno en lugar de cualquiera (Any) sólo se añadirán los equipos con ese sistema operativo. Piense que en determiandas situaciones <?php echo get_product_name(); ?> puede equivocarse a la hora de detectar sistemas, ya que este tipo de "adivinación" se realiza con patrones estadísticos que en función de algunos factores ajenos pueden fallar (redes con filtrados, software de seguridad, versiones modificadas de los sistemas). Para poder utilizar con seguridad este método debe tener instalado Xprobe2 en su sistema.<br><br>

<b>Puertos</b><br>

Define unos puertos específicos o un rango determinado, p.e: 22,23,21,80-90,443,8080. Si utiliza este campo, solo aquellos hosts detectados que tengan al menos uno de los puertos aqui enumerados, será detectado y añadido al sistema. Si se detecta un host pero no tiene al menos uno de los puertos abiertos, será ignorado. Esto en combinacion con el filtrado por tipo de OS permite detectar aquellos sistemas que nos interesan exclusivamente, p.e: detectando que es un router porque tiene los puertos 23 y 57 abiertos y el sistema lo detecta como de tipo "BSD".<br><br>

<b>Grupos</b><br>

Es el grupo donde añadir los equipos descubiertos. Obligatoriamente deberá asignar los nuevos equipos a un grupo. Si ya dispone de un grupo especial para ubicar a los agentes no clasificados, puede ser una buena idea asignarlo ahí.<br><br>

<b>Incidente</b><br>

Indica si al descubrir equipos nuevos crea un incidente o no. Creará un incidente por tarea, no uno por máquina detectada, haciendo un resumen de todos los sistemas nuevos detectados, y automáticamente lo creará dentro del grupo definido anteriormente.<br><br>

<b>Comunidad SNMP por defecto</b><br>

Comunidad SNMP por defecto que se usará para describir los equipos.<br><br>

<b>Comentarios</b><br>

Comentarios acerca de la tarea de descubrimiento de red.<br><br>

<b>Detección de SO</b><br>

Al elegir esta opción la exploración detectará el SO.<br><br>

<b>Resolución de nombres</b><br>

Al elegir esta opción el agente se creará con el nombre del equipo, siempre y cuando esté configurado en el propio equipo, si no creará el agente con el nombre de la IP.<br><br>

<b>Detección de padres</b><br>

Al seleccionar esta opción detectará en la exploración si hay equipos conectados a otros y los creará como hijos.<br><br>

<b>Recursión del padre</b><br>

Indica el numero máximo de recursión con la que se podran generar los agentes como padres e hijos, después de realizar la exploración.<br><br>


