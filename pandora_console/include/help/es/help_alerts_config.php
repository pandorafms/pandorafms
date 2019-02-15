<?php
/**
 * @package Include/help/es
 */
?> 
 <h1>Guía rápida de configuración de alertas para <?php echo get_product_name(); ?></h1>
<br>

<b>Introducción al sistema de alertas actual</b><br><br>

Uno de los problemas más frecuentes y que ocasiona mayor número de quejas por parte de los usuarios es la complejidad de definir alertas en <?php echo get_product_name(); ?>. Antes, hasta la version 2.0, las alertas eran bastante más sencillas de configurar.Para cada alerta, se definía la condición y lo que hacía cuando la acción no se cumplía, para cada caso. Era más intuitivo (aun así habia campos como el alert "threshold" que daban dolores de cabeza a más de uno). Era sencillo, pero ¿merecía la pena?.<br><br>

Uno de nuestros mejores usuarios (cuando digo mejor, es porque tenía muchísimos agentes instalados, y además conocía muy bien el funcionamiento de <?php echo get_product_name(); ?>), nos comentó que crear una alerta en 2000 modulos, era enormemente complicado, especialmente cuando habia que modificar algo en todas ellas. Debido principalmente a este y otros problemas, modificamos el sistema de alertas para que fuera modular, para que se pudiera separar la definición de la condición de disparo de la alerta (Alert template), de la acción a ejecutar cuando esta se dispara (Alert action) y del comando que se ejecuta dentro de la acción (Alert comnmand). La combinación de una plantilla de alerta (Alert template) con un módulo desencadena la alerta en sí.<br><br>

De esta forma, si yo tengo 1000 máquinas con un modulo llamado "Host alive" y todos ellas tienen asociada una plantilla de alerta llamada "Host down" que ejecuta por defecto una acción llamada "Avisar al operador", y quiero cambiar el número mínimo de alertas que se deben disparar antes de avisar al operador, sólo tengo que hacer un cambio en la definicion de la plantilla, no ir una por una, en las 1000 alertas para modificar esa condición.<br><br>

Muchos usuarios sólo gestionan algunas decenas de máquinas, pero existen usuarios con cientos, incluso miles de sistemas monitorizados con <?php echo get_product_name(); ?>, y tenemos que intentar hacer posible que con <?php echo get_product_name(); ?> se puedan gestionar todo tipo de entornos.<br><br><br><br>
<b>Estructura de una alerta</b><br><br>

<?php html_print_image('images/help/alert01.png', false, ['width' => '550px']); ?>
<br>
Las alertas se componen de:<br><br>

    <i>Comandos</i><br>
    <i>Acciones</i><br>
    <i>Plantillas</i> <br><br>

Un comando defined la operación a realizar cuando se dispara la alerta. Ejemplos de comandos pueden ser: escribir en un log, envíar un email o SMS, ejecutar un script o programa, etc.<br><br>

Una acción relaciona un comando con una plantilla y permite personalizar la ejecución del comando usando tres parámetros genéricos Field 1, Field 2 y Field 3. Estos parámetros permiten personalizar la ejecución del comando ya que son los que se pasarán en el momento de la ejecución como parámetros de entrada.<br><br>

En la plantilla se definen parámetros genéricos de la alertas que son: las condiciones de disparo, acciones de disparo y recuperación de la alerta.<br><br>

    <i>Condiciones de disparo:</i> son las condiciones bajos las que se disparará la alerta, por ejemplo: superar cierto umbral, estar en estado crítico, etc.<br>
    <i>Acciones de disparo:</i> es la configuración de las acciones que se realizarán al disparar la alerta.<br>
    <i>Recuperación de alerta:</i> es la configuración de las acciones que se realizarán cuando el sistema se recupere de la alerta. <br><br>

<b>Flujo de información en el sistema de alertas</b><br><br>

Al definir las acciones y las plantillas disponemos de unos campos genéricos llamados Field1, Field2 y Field3 que son los que se pasarán como parámetros de entrada en la ejecución del comando. Los valores de estos parámetros se propagan de plantilla a la acción y por último al comando. La propagación de la plantilla a la acción sólo se realiza si el campo correspondiente de la acción no tiene un valor asignado, si la acción tiene un valor asignado se conserva.<br><br>

<?php html_print_image('images/help/alert02.png', false, ['width' => '550px']); ?>
<br>
Este seria un ejemplo de como se sobreescriben los valores de la plantilla usando los de la acción.<br><br>

<?php html_print_image('images/help/alert03.png', false, ['width' => '550px']); ?>
<br>
Por ejemplo creamos una plantilla que dispara la alerta y envía un email con los siguientes campos:<br><br>

    <b>Plantilla:</b><br>
        Field1: <i>myemail@domain.com</i><br>
        Field2: <i>[Alert] The alert was fired</i><br>
        Field3: <i>The alert was fired!!! SOS!!! </i><br><br>

    <b>Acción:</b><br>
        Field1: <i>myboss@domain.com</i><br>
        Field2:<br>
        Field3: <br><br>

Los valores que llegarían al comando serían:<br><br>

    <b>Comando:</b><br>
        Field1: myboss@domain.com<br>
        Field2: [Alert] The alert was fired<br>
        Field3: The alert was fired!!! SOS!!! <br><br>

Para los campos Field2 y Field3 se conservan los valores definidos en la plantilla, pero para el campo Field1 usa el valor definido en la acción.<br><br>
<b>Definiendo una Alerta</b><br><br>

Bien, ahora vamos a ponernos en el caso anterior. Tenemos una necesidad: monitorizar un módulo que contiene valores numéricos. En nuestro caso, es un modulo que mide la CPU del sistema, en otro caso puede ser un sensor de temperatura, que engrega el valor en grados centígrados. Veamos primero que nuestro módulo recibe datos correctamente:<br><br>

<?php html_print_image('images/help/alert04.png', false, ['width' => '550px']); ?>
<br>
Bien. En esta captura vemos que tenemos un modulo llamado sys_cpu con un valor actual de 7. En nuestro caso queremos que salte una alerta cuando supere los 20. Para ello vamos a configurar el módulo para que se ponga en estado CRITICAL cuando supere los 20. Para ello hacemos click en la llave inglesa para configurar el comportamiento del monitor:<br><br>

<?php html_print_image('images/help/alert05.png', false, ['width' => '550px']); ?>
<br>
Para ello, modificamos el valor marcado en rojo en la captura siguiente:<br><br>
<br><br>

<?php html_print_image('images/help/alert06.png', false, ['width' => '550px']); ?>
<br>
Aceptamos y grabamos la modificación. Ahora cuando el valor del módulo CPU sea 20 o mayor, cambiará su estado a CRITICAL y se verá en color rojo, tal y como vemos aquí.<br><br>


<?php html_print_image('images/help/alert07.png', false, ['width' => '550px']); ?>
<br>
Ya hemos hecho que el sistema sepa discriminar cuando algo está bien (OK, color VERDE) y cuando está mal (CRITICAL, color rojo). Ahora lo que debemos hacer es que nos envíe un email cuando el modulo se ponga en este estado. Para ello utilizaremos el sistema de alertas de <?php echo get_product_name(); ?>.<br><br>

Para esto, lo primero que debemos hacer es asegurarnos de que existe un comando que hace lo que necesitamos (enviar un email). Este ejemplo es fácil porque existe un comando predefinido en <?php echo get_product_name(); ?> para enviar mails. Asi que ya lo tenemos.<br><br>
<b>Configurando la acción</b><br><br>

Ahora tenemos que crear una acción que sea "Enviar un email al operador". Vamos a ello: Vamos al menu de administracion -> Alertas -> Acciones y le damos al botón para crear una nueva acción:<br><br>

<?php html_print_image('images/help/alert08.png', false, ['width' => '550px']); ?>
<br>
Esta acción utiliza el comando "Enviar email", y es realmente sencillo, ya que sólo relleno un campo (Field 1) dejando los otros dos vacíos. Esta es una de las partes más confusas del sistema de alertas de <?php echo get_product_name(); ?>: ¿Qué son los campos field1, field2, y field3?.<br><br>

Esos campos son los que se usan para "pasar" la información de la plantilla de alerta al comando, y a su vez, de éste al comando. De forma que tanto Plantilla como Comando, puedan aportar diferente información al comando. En este caso el comando sólo establece el campo 1, y dejaremos el campo 2 y el campo 3 a la plantilla, como veremos a continuación.<br><br>

El campo 1 es el que usamos para definir el email del operador, en este caso un supuesto mail a "sancho.lerena@notexist.com".<br><br>

<b>Configurando la plantilla (Alert template)</b><br><br>

Ahora tenemos que crear una plantilla de alerta lo más genérica posible (para poderla reutilizar más adelante, como veremos) que sea "Esto está mal, porque tengo un módulo en estado Crítico" y que por defecto, envíe un email al operador. Vamos a ello: Vamos al menu de administracion -> Alertas -> Templates y le damos al botón para crear una nueva plantilla (template) de alerta:<br><br>

<?php html_print_image('images/help/alert09.png', false, ['width' => '550px']); ?>
<br>
Lo que define la condición es el campo "Condition", en este caso está marcado a "Estado crítico", de forma que esta plantilla, cuando se asocie a un módulo, se disparará cuando el modulo asociado esté en estado crítico. Antes hemos configurado el modulo "cpu_sys" para que entre en estado crítico cuando valga 20 o más.<br><br>

La prioridad definida aqui "Critical" es la prioridad de la alerta, que no tiene que ver con el estado "Critico" del módulo. Las criticidades de las alertas son para visualizarlas luego, en otras vistas, como la vista de eventos, con diferentes criticidades.<br><br>

Pasemos al paso 2, pulsando el boton "next":<br><br>

<?php html_print_image('images/help/alert10.png', false, ['width' => '550px']); ?>
<br>
El paso 2 define todos los "valores" de configuración "finos" de la plantilla de alerta, de la condición de disparo. Algunos de ellos, los primeros, son bastante sencillos, restringen el momento de actuación de esta alerta a ciertos días entre diferentes horas.<br><br>

Los parámetros más críticos aquí son los siguientes:<br><br>

    <i>Time threshold:</i> Por defecto es un día. Si un módulo permanece todo el rato caído, durante, por ejemplo un día, y tenemos aquí un valor de 5 minutos, significa que nos estaría mandando alertas cada 5 minutos. Si lo dejamos en un día (24 horas), sólo nos enviará la alerta una vez, cuando se caiga. Si el modulo se recupera, y luego se vuelve a caer, nos enviará una alerta de nuevo, pero si sigue caída desde la 2º caida, no enviará mas alertas hasta dentro de 24 horas. <br><br>

    <i>Min. Número de alertas:</i> El nº mínimo de veces que se tendrá que dar la condición (en este caso, que el modulo esté en estado CRITICAL) antes de que <?php echo get_product_name(); ?> me ejecute las acciones asociadas a la plantilla de alerta. Es una forma de evitar que falsos positivos me "inunden" a alertas, o que un comportamiento errático (ahora bien, ahora mal) haga que se disparen muchas alertas. Si ponemos aquí 1, significa que hasta que no ocurra al menos una vez, no lo tendré en cuenta. Si pongo 0, la primera vez que el modulo esté mal, disparará la alerta. <br><br>

    <i>Max. Numero de alertas:</i> 1 significa que sólo ejecutará la acción una vez. Si tenemos aquí 10, ejecutará 10 veces la acción. Es una forma de limitar el número de veces que una alerta se puede ejecutar. <br><br>

De nuevo volvemos a ver los campos: "campo1, campo2 y campo3". Ahora podemos ver que el campo1 está en blanco, que es justamente el que hemos definido al configurar la acción. El campo2 y el campo3 se usan en la acción de enviar un mail para definir el subject y el texto del mensaje, mientras que el campo1 se usa para definir el o los destinatarios (separados por comas). Asi que la plantilla, usando algunas macros, está definiendo el subject y el mensaje de alerta de forma que en nuestro caso nos llegaría un mensaje como el que sigue (suponiendo que el agente donde está el modulo se llama "Farscape":<br><br>

<i>To: sancho.lerena@notexist.ocm<br>
Subject: [MONITORING] Farscape cpu_sys is in CRITICAL status with value 20<br>
Texto email:<br><br>

This is an automated alert generated by <?php echo get_product_name(); ?><br>
Please contact your <?php echo get_product_name(); ?> for more information. *DO NOT* reply this email.<br></i><br>

Dado que la acción por defecto es la que he definido previamente, todas las alertas que usen esta plantilla, usarán esa acción predeterminada por defecto, a no ser que la modifique.<br><br>

En el caso 3, veremos que se puede configurar el sistema de alertas para que notifique cuando la alerta ha cesado.<br><br>

<?php html_print_image('images/help/alert11.png', false, ['width' => '550px']); ?>
<br>
Es casi igual, pero el campo1 no está definido, porque se usará el mismo que venga definido en la acción ejecutada previamente (al disparar la alerta). En este caso solo enviará un mail con un subject que informa que la condición en el modulo cpu_sys se ha recuperado.<br><br>

La recuperación de alertas es opcional. Es importante destacar que si en los datos de recuperación de la alerta, hay campos (field2 y field3) definidos, estos ignoran y sobreescriben los campos de la acción, es decir, tienen preferencia sobre ellos. El único campo válido que no se puede modificar es el campo1.<br><br>
<b>Asociando la alerta al módulo</b><br><br>

Ya tenemos todo lo que necesitábamos, ahora sólo tenemos que asociar la plantilla de alerta al módulo. Para ello vamos a la solapa de alertas dentro del agente donde está el módulo:<br><br>

<?php html_print_image('images/help/alert12.png', false, ['width' => '550px']); ?>
<br>
Es sencillo, en esta captura vemos una alerta ya configurada para un módulo llamado "Last_Backup_Unixtime" asociado al mismo template que hemos definido antes "Module critical". Ahora en los controles que hay debajo, vamos a crear una asociación entre el modulo "cpu_sys" y la plantilla de alerta "Module critical". Por defecto mostrará la acción que tenemos definida en esa pantilla "Enviar email a Sancho Lerena".<br><br>
<b>Escalado de alertas</b><br><br>

Los valores que hay en la opcion de "Number of alerts match from" son para definir el escalado de alertas. Esto permite "redefinir" un poco más el comportamiento de la alerta, de forma que si hemos definido un máximo de 5 veces las veces que se puede disparar una alerta, y sólo queremos que nos envie un email, pondremos aquí un 0 y un 1, para decirle que sólo nos envie un email desde la vez 0 a la 1 (osea, una vez).<br><br>

Ahora veremos que podemos añadir más acciones a la misma alerta, definiendo con estos campos "Number of alerts match from" el comportamiento de la alerta en función de cuantas veces se dispare.<br><br>

Por ejemplo, podemos querer que mande un email a XXXXX la primera vez que ocurra, y si sigue caído el monitor, envíe un email a ZZZZ. Para ello, despues de asociar la alerta, en la tabla de alertas asignadas, puedo añadir mas acciones a una alerta ya definida, tal y como podemos ver en la siguiente captura:<br><br>

<?php html_print_image('images/help/alert13.png', false, ['width' => '550px']); ?>
<?php html_print_image('images/help/alert14.png', false, ['width' => '550px']); ?>

<br>

<b>Alertas en Standby</b><br><br>

Las alertas pueden estar activadas, desactivadas o en standby. La diferencia entra las alertas desactivadas y las que están en standby es que las desactivadas simplemente no funcionarán y por lo tanto no se mostrarán en la vista de alertas. En cambio, las alertas en standby se mostrarán en la vista de alertas y funcionarán pero solamente a nivel de visualización. Esto es, se mostrará si están o no disparadas pero no realizarán las acciones que tengan programadas ni generarán eventos.<br><br>

Las alertas en standby son útiles para poder visualizarlas sin que molesten en otros aspectos.<br><br>
<b>Utilizando comandos de alertas distintos del email</b><br><br>

El email, como comando es interno a <?php echo get_product_name(); ?> y no se puede configurar, es decir, field1, field2 y field3 son campos que están definidos que se usan como destinatario, subject y texto del mensaje. Pero, ¿que ocurre si yo quiero ejecutar una acción diferente, definida por mi?.<br><br>

Vamos a definir un nuevo comando, algo totalmente definido por nosotros. Imaginemos que queremos generar un fichero log con cada alerta que encontremos. El formato de ese fichero log tiene que ser algo como:<br><br>

<i>FECHA_HORA - NOMBRE_AGENTE - NOMBRE_MODULO - VALOR - DESCRIPCION DEL PROBLEMA</i><br><br>

Donde VALOR es el valor del modulo en ese momento. Habrá varios ficheros log, dependiendo de la acción que llame al comando. La acción definirá la descripcion y el fichero al que van los eventos.<br><br>

Para ello, primero vamos a crear un comando como sigue:<br><br>

<?php html_print_image('images/help/alert15.png', false, ['width' => '550px']); ?>
<br>
Y vamos a definir una acción:<br><br>

<?php html_print_image('images/help/alert16.png', false, ['width' => '550px']); ?>
<br>
Si vemos el fichero de log que hemos creado:<br><br>

<i>2010-05-25 18:17:10 - farscape - cpu_sys - 23.00 - Custom alert for LOG#1</i><br><br>

La alerta se disparó a las 18:17:10 en el agente "farscape", en el modulo "cpu_sys" con un dato de "23.00" y con la descripción que pusimos al definir la acción.<br><br>

Dado que la ejecución del comando, el orden de los campos y otros asuntos pueden hacer que no entendamos bien cómo se ejecuta al final el comando, lo más sencillo es activar las trazas de debug del servidor de <?php echo get_product_name(); ?> (verbose 10) en el fichero de configuración de <?php echo get_product_name(); ?> server en /etc/pandora/pandora_server.conf, reiniciemos el servidor (/etc/init.d/pandora_server restart) y que miremos el fichero /var/log/pandora/pandora_server.log buscando la línea exacta con la ejecución del comando de la alerta que hemos definido, para ver como el servidor de <?php echo get_product_name(); ?> está lanzando el comando. <br><br>


