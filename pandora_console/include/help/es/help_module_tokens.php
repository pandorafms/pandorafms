<?php
/*
    Include package help/es
*/
?>
 <h1>Definición de los módulos</h1>
<br><br>
Cada fragmento de información que se recopile debe definirse con precisión en cada módulo, empleando la sintaxis exacta. Se pueden implementar tantos valores como sea preciso para ser recogidos, añadiendo, al final de los parámetros generales, tantos módulos como el número de valores para compilar. Cada módulo está compuesto de varias directivas. La lista de abajo es una lista descriptiva de todas las señales de módulos disponibles para agentes UNIX (casi todas ellas también se pueden aplicar al agente Windows).
<br><br>
La sintaxis general es la siguiente:
<br><br>
module_begin<br>
module_name NombreDelMódulo<br>
module_type generic_data<br>
.<br>
.<br>
.<br>
module_description Ejecución del comando<br>
module_interval Número<br>
module_end <br>
<br>
Existen diferentes tipos de módulos, con diferentes subopciones, pero todos los módulos tienen una estructura similar a esta. Los parámetros module_interval y module_description son opcionales, y el resto completamente obligatorios. 
<br><br>
<h2>Elementos comunes de todos los módulos</h2>
<br><br>    
<b>module_begin</b>
<br><br>
Define el inicio del módulo.
<br><br>
<b>module_name 'nombre'</b>
<br><br>
Nombre del módulo.
<br><br>
<b>module_type 'tipo'</b>
<br><br>
El tipo de datos que manejará el módulo. Hay varios tipos de datos para los agentes:
<br><br>
    <i>Numérico (generic_data).</i> Datos numéricos sencillos, en coma flotante o enteros.
<br><br>
    <i>Incremental (generic_data_inc).</i> Los datos numéricos enteros iguales al diferencial existente entre el valor actual y el anterior. Cuando este diferencial es negativo el valor se fija a 0. 
<br><br>
    <i>Alfanumérico (generic_data_string).</i> Recoge cadenas de texto alfanuméricas. 
<br><br>
    <i>Monitores (generic_proc).</i> Útil para medir el estado de un proceso o servicio.
<br><br>
    <i>Alfanumérico asíncrono (async_string).</i> Recoge cadenas de texto alfanuméricas que pueden llegar en cualquier momento, sin una periodidicad fija. 
<br><br>
    <i>Monitor asíncrono (async_proc).</i> Similar a generic_proc pero asíncrono. 
<br><br>
    <i>Numérico asíncrono (async_data).</i> Similar a generic_data pero asíncrono. 
<br><br>


<b>module_min 'valor'</b>
<br><br>
Este el valor mínimo válido para los datos generados en este módulo. 
<br><br>
<b>module_max 'valor'</b>
<br><br>
Este es el valor máximo válido para los datos generados en este módulo. 
<br><br>
<b>module_min_warning 'valor'</b>
<br><br>
Este es el valor mínimo para que el módulo pase a estado de advertencia. 
<br><br>
<b>module_max_warning 'valor'</b>
<br><br>
Este es el valor máximo para que el módulo esté en estado de advertencia. 
<br><br>
<b>module_min_critical 'valor'</b>
<br><br>
Este es el valor mínimo para que el módulo pase a estado crítico. 
<br><br>
<b>module_max_critical 'valor'</b>
<br><br>
Este es el valor máximo para que el módulo esté en estado crítico. 
<br><br>
<b>module_disabled 'valor'</b>
<br><br>
Indica si el módulo esta habilitado (0) o deshabilitado (1).
<br><br>
<b>module_min_ff_event 'valor'</b>
<br><br>
Este es el intervalo en el que se filtrarán nuevos cambios de estado en el módulo evitando fluctuaciones excesivas en los estados del módulo. 
<br><br>
<b>module_description 'texto'</b>
<br><br>
Esta directiva se emplea para añadir un comentario al módulo.
<br><br>
<b>module_interval 'factor'</b>
<br><br>
Este intervalo se calcula como un factor multiplicador para el intervalo del agente. 
<br><br>
<b>module_timeout 'secs'</b>
<br><br>
Cuantos segundos esperará a la ejecución del módulo, de forma que si tarda más de XX segundos, aborta la ejecución del módulo.
<br><br>
<b>module_save 'nombre de variable'</b>
<br><br>
Desde la versión 3.2 es posible guardar el valor devuelto por un módulo en una variable de entorno de modo que pueda ser utilizado más adelante en otros módulos.
<br><br>
<b>module_crontab 'minuto' 'hora' 'día' 'mes' 'día de la semana'</b>
<br><br>
Programación de los módulos para que se ejecuten en determinadas fechas.
<br><br>
<b>module_condition 'operación' 'comando'</b>
<br><br>
Es posible definir comandos que se ejecutarán cuando el módulo devuelva determinados valores. ( >, < , =, !=, =~,(valor, valor)
<br><br>
Ej.<br>
module_begin<br>
module_name condition_test<br>
module_type generic_data<br>
module_exec echo 5<br>
module_condition (2, 8) cmd.exe /c script.bat<br>
module_end<br>
<br>
<b>module_precondition 'operación' 'comando'</b>
<br><br>
Se ejecutará el módulo si se cumple la precondición. 
<br><br>
Ej.<br>

module_begin<br>
module_name Precondition_test1<br>
module_type generic_data<br>
module_precondition (2, 8) echo 5<br>
module_exec monitoring_variable.bat<br>
module_end<br>
<br>
<b>module_unit 'value'</b>
<br><br>
Esta directiva es la unidad del valor devuelto por el módulo.
<br><br>
<b>module_group 'value'</b>
<br><br>
Esta directiva es el nombre del grupo del módulo. Si el grupo no existe el módulo se creará sin grupo asignado.
<br><br>
<b>module_custom_id 'value'</b>
<br><br>
Esta directiva es un identificador personalizado del módulo.
<br><br>
<b>module_str_warning 'value'</b>
<br><br>
Esta directiva es una expresión regular para definir el estado Warning en los módulos de tipo string.
<br><br>
<b>module_str_critical 'value'</b>
<br><br>
Esta directiva es una expresión regular para definir el estado Critical en los módulos de tipo string.
<br><br>
<b>module_warning_instructions 'value'</b>
<br><br>
Esta directiva son instrucciones para el operador cuando el módulo pase a estado Warning.
<br><br>
<b>module_critical_instructions 'value'</b>
<br><br>
Esta directiva son instrucciones para el operador cuando el módulo pase a estado Critical.
<br><br>
<b>module_unknown_instructions 'value'</b>
<br><br>
Esta directiva son instrucciones para el operador cuando el módulo pase a estado Unknown.
<br><br>
<b>module_tags 'value'</b>
<br><br>
Esta directiva son las tags que se desean asignar al módulo separadas por comas. Solo se asignarán si existen en el sistema.
<br><br>
<b>module_warning_inverse 'value'</b>
<br><br>
Esta directiva es un flag (0/1) que cuando está activado indica que el umbral de Warning es el inverso al definido.
<br><br>
<b>module_critical_inverse 'value'</b>
<br><br>
Esta directiva es un flag (0/1) que cuando está activado indica que el umbral de Critical es el inverso al definido
<br><br>
<b>module_quiet 'value'</b>
<br><br>
Esta directiva es un flag (0/1) que cuando está activado indica que el módulo está en modo silencioso (no genera eventos ni alertas)
<br><br>
<b>module_ff_event 'value'</b>
<br><br>
Esta directiva es el umbral flip flop de ejecución del módulo (en segundos)
<br><br>
<b>module_macro'macro' 'value'</b>
<br><br>
Esta es una macro generada por la consola con el sistema de macros de los componentes. Establecer este parámetero en el fichero de configuración es inútil porque es solamente para módulos creados con componentes locales.
<br><br>

<b>module_end</b>
<br><br>
Define el final del módulo.
<br><br>
<h2>Directivas específicas para obtener información</h2>
<br><br>
<b>module_exec 'comando'</b>
<br><br>
Este es la directiva general de «comando a ejecutar». 
<br><br>
<b>module_service 'servicio'</b>
<br><br>
Comprueba si un determinado servicio se está ejecutando en la máquina. 
<br><br>
<b>module_watchdog</b>
<br><br>
Existe un modo watchdog para los servicios, de tal forma que el agente puede iniciarlos de nuevo si estos se paran. 
<br><br>
Ej.<br>
module_begin<br>
module_name ServiceSched<br>
module_type generic_proc<br>
module_service Schedule<br>
module_description Service Task scheduler<br>
module_async yes<br>
module_watchdog yes<br>
module_end<br>
<br>
El modo watchdog y la detección asíncrona no son posibles en el agente de Unix.
<br><br>
<b>module_proc 'proceso'</b>
<br><br>
Comprueba si un determinado nombre de proceso está operando en esta máquina.
<br><br>
Ej.<br>
module_begin<br>
module_name CMDProcess<br>
module_type generic_proc<br>
module_proc cmd.exe<br>
module_description Process Command line<br>
module_end<br>
<br>

<b>Modo asíncrono<br><br></b>

En este caso, el agente notifica inmediatamente cuando el proceso cambia de estado.
<br><br>
module_begin<br>
module_name Notepad<br>
module_type generic_data<br>
module_proc notepad.exe<br>
module_description Notepad<br>
module_async yes<br>
module_end<br>
<br>
<b>Watchdog de procesos</b>
<br><br>
Un Watchdog es un sistema que permite actuar inmediatamente ante la caída de un proceso, generalmente, levantando el proceso que se ha caído. El agente de Windows de <?php echo get_product_name(); ?>, puede actuar como Watchdog ante la caída de un proceso, a esto le llamamos modo watchdog para procesos:
<br><br>
Dado que ejecutar un proceso puede requerir algunos parámetros, hay algunas opciones adicionales de configuración para este tipo de módulos. Es importante destacar que el modo watchdog solo funciona cuando el tipo de módulo es asíncrono. Veamos un ejemplo de configuración de un module_proc con watchdog:
<br><br>
module_begin<br>
module_name Notepad<br>
module_type generic_data<br>
module_proc notepad.exe<br>
module_description Notepad<br>
module_async yes<br>
module_watchdog yes<br>
module_start_command c:\windows\notepad.exe<br>
module_startdelay 3000<br>
module_retrydelay 2000<br>
module_retries 5<br>
module_end<br>
<br>
Esta es la definición de los parámetros adicionales para module_proc con watchdog:
<br><br>
    <i>module_retries:</i> Número de intentos consecutivos que el módulo intentará lanzar el proceso antes de desactivar el watchdog. Si el limite es alcanzado, el mecanismo del watchdog para este módulo se desactivará y nunca más intentará lanzar el proceso, incluso si el proceso es recuperado por el usuario. (al menos hasta que se reinicie el agente). Por defecto no hay límite para el nº de reintentos del watchdog. 
<br><br>
    <i>module_startdelay:</i> Número de milisegundos que el módulo esperará antes de lanzar el proceso por primera vez. Si el proceso tarda mucho en arrancar, es bueno decirle al agente por medio de este parámetro que "espere" antes de empezar a comprobar de nuevo si el proceso se ha levantado. En este ejemplo espera 3 segundos. 
<br><br>
    <i>module_retrydelay:</i> Similar al anterior pero para caídas/reintentos posteriores, después de detectar una caída. Cuando <?php echo get_product_name(); ?> detecta una caída, relanza el proceso, espera el nº de milisegundos indicados en este parámetro y vuelve a comprobar si el proceso ya esta levantado. 
<br><br>
<b>module_cpuproc 'process'</b>
<br><br>
Devuelve el uso de CPU específico de un proceso.(Unix)
<br><br>
<b>module_memproc 'process'</b>
<br><br>
Devuelve el consumo de memoria específico de un proceso.(Unix)
<br><br>
<b>module_freedisk 'letra_de_la_unidad:'|'volumen'</b>
<br><br>
Comprueba el espacio libre en la unidad 
<br><br>
<b>module_freepercentdisk 'letra_de_la_unidad:'|'volumen'</b>
<br><br>
Este módulo devuelve el porcentaje de disco libre en una unidad lógica (C:) o un volumen Unix (p.e: /var)
<br><br>
<b>module_occupiedpercentdisk 'volumen'</b>
<br><br>
Este módulo devuelve el porcentaje de disco ocupado en un volumen Unix (p.e: /var)
<br><br>
<b>module_cpuusage ['cpu id']</b>
<br><br>
Devuelve el uso de CPU en un número de CPU. Si sólo existe una CPU no establezca ningun valor o utilice el valor "all". 
<br><br>
<b>module_freememory</b>
<br><br>
Devuelve la memoria libre en todo el sistema.
<br><br>
<b>module_freepercentmemory</b>
<br><br>
Este módulo devuelve el porcentaje de memoria libre en un sistema:
<br><br>
<b>module_tcpcheck</b>
<br><br>
Este módulo intenta conectarse con la dirección IP y puerto especificados. Devuelve 1 si tuvo éxito y 0 de otra forma. Se debe especificar un tiempo de expiración.(module_timeout)(Win)
<br><br>
<b>module_regexp</b>
<br><br>
Este módulo monitoriza un fichero de registro (log) buscando coincidencias usando expresiones regulares, descartando las líneas ya existentes al iniciar la monitorización. 
<br><br>
    <i>generic_data_string, async_string:</i> Devuelve todas las líneas que coincidan con la expresión regular.<br>
    <i>generic_data:</i> Devuelve el número de líneas que coincidan con la expresión regular.<br>
    <i>generic_proc:</i> Devuelve 1 si existe alguna coincidencia, 0 de otra forma.<br>
    <i>module_noseekeof:</i> Por defecto a 0, con este token de configuración activo, en cada ejecución, independientemente de las modificaciones en el fichero del log, el módulo reinicia su comprobación sin buscar el flag EOF del archivo, con lo que siempre sacará en el XML todas aquellas líneas que coincidan con nuestro patrón de búsqueda. 
<br><br>
<b>module_wmiquery</b>
<br><br>
Los módulos WMI permiten ejecutar localmente cualquier query WMI sin utilizar una herramienta externa. 
<br><br>
    <i>module_wmiquery:</i> WQL query empleada. Se pueden obtener varias lineas como resultado, que serán insertados como varios datos.<br>
    <i>module_wmicolumn:</i> Nombre de la columna que se va a usar como fuente de datos. 
<br><br>
<b>module_perfcounter</b>
<br><br>
Obtiene los datos del contador de rendimiento (Win)
<br><br>
<b>module_inventory</b>
<br><br>
Este módulo obtiene información acerca de los diferentes aspectos de una máquina, desde software hasta hardware.(Win)
<br><br>
Ej<br>
module_begin<br>
module_name Inventory<br>
module_interval 7 (dias)<br>
module_type generic_data_string<br>
module_inventory RAM Patches Software Services Cpu CDROM Video NICs <br>
module_description Inventory<br>
module_end<br>
<br>
<b>module_logevent</b>
<br><br>
Permite obtener información del archivo log de eventos de Windows. 
<br><br>
module_begin<br>
module_name MyEvent<br>
module_type async_string<br>
module_logevent<br>
module_source 'logName'<br>
module_eventtype 'event_type/level'<br>
module_eventcode 'event_id'<br>
module_application 'source'<br>
module_pattern 'text substring to match'<br>
module_description<br>
module_end<br>
<br>
<b>module_plugin</b>
<br><br>
Es un parámetro para definir que el dato se obtiene como salida de un plugin de agente. 
<br><br>
<b>module_ping 'host'</b>
<br><br>
Este módulo hace un ping al host dado y devuelve 1 si está arriba, 0 en cualquier otro caso.(Win)
<br><br>
Ej.<br>

module_begin<br>
module_name Ping<br>
module_type generic_proc<br>
module_ping 192.168.1.1<br>
module_ping_count 2 (Número de paquetes ECHO_REQUEST a enviar)<br>
module_ping_timeout 500 (Timeout en milisegundos de espera)<br>
module_end<br>
<br>
<b>module_snmpget<br></b>
<br>
Este módulo ejecuta una consulta SNMP get y devuelve el valor solicitado.<br>
<br>
Ej.<br>
module_begin<br>
module_name SNMP get<br>
module_type generic_data<br>
module_snmpget<br>
module_snmpversion 1<br>
module_snmp_community public<br>
module_snmp_agent 192.168.1.1<br>
module_snmp_oid .1.3.6.1.2.1.2.2.1.1.148<br>
module_end<br>




