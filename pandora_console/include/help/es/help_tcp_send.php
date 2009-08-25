<?php
/**
 * @package Include/help/es
 */
?>
<h1>Comprobación TCP</h1>

<p>
Este módulo simplemente envía cadenas de caracteres a la IP / puerto de destino, espera la respuesta y, opcionalmente, compara la respuesta con una predefinida. Si los campos &laquo;Enviar TCP&raquo; / &laquo;Recibir TCP&raquo; están vacíos, simplemente comprueba si el puerto de destino está abierto.
</p>
<p>
Puede usar la cadena &laquo;^M&raquo; para enviar un retorno de carro, y también usar multi respuesta / multi pregunta para establecer una conversación. Las diversas consultas y respuestas se separan con el carácter &laquo;|&raquo;.
</p>


<h2>Ejemplo #1. Comprobar un servicio Web</h2>

<p>
Imagine que quiere comprobar que www.yahoo.com responde correctamente a una consulta HTTP. Ponga esto en el campo &laquo;Enviar TCP&raquo;:<br /><br />
GET / HTTP/1.0^M^M
<br /><br />
Y esto en el campo &laquo;Recibir TCP&raquo;:
<br /><br />
200 OK
<br /><br />
Esto debería proporcionar &laquo;OK&raquo; si la petición HTTP fue posible. 
</p>


<h2>Ejemplo #2. Comprobar un servicio SSH</h2>

<p>
Si hace un <i>telnet</i> al puerto 22 de un servicio estándar verá que después de conectar se le mostrará un <i>banner</i> así:
<br /><br />
SSH-2.0xxxxxxxxxx
<br /><br />
Si escribe algo, como &laquo;none&raquo; y pulsa intro, se le responderá con la siguiente cadena y cerrará el socket:
<br /><br />
Protocol mismatch
<br /><br />
De tal forma que para &laquo;codificar&raquo; esta conversación en un módulo TCP de Pandora FMS, deberá poner en el campo &laquo;Envar TCP&raquo;:
<br /><br />
|none^M
<br /><br />
Y poner esto en el campo &laquo;Recibir TCP&raquo;:
<br /><br />
SSH-2.0|Protocol mismatch
</p>

<h3>Ejemplo #3. Comprobar un servicio SMTP</h3>

<p>
Este es un ejemplo de una conversacion SMTP:
<pre>
R: 220 mail.supersmtp.com Bla bla bla
S: HELO myhostname.com
R: 250 myhostname.com
S: MAIL FROM: <pepito@myhostname.com>
R: 250 OK
S: RCPT TO: <Jones@supersmtp.com>
R: 250 OK
S: DATA
R: 354 Start mail input; end with <CRLF>.<CRLF>
S: .......aquí su correo-e........
S: .
R: 250 OK
S: QUIT
R: 221 mail.supersmtp.com Service closing bla bla bla
</pre>
<br />
De tal forma que si quiere comprar los primeros pasos de la conversación, los campos deberían ser:
<br /><br />
<b>TCP SEND </b>: HELO myhostname.com^M|MAIL FROM: <pepito@myhostname.com>^M| RCPT TO: <Jones@supersmtp.com>^M
<br /><br />
<b>TCP SEND </b>: 250|250|250
<br /><br />
Si los tres primeros pasos de la conexión son &laquo;OK&raquo;, entonces parece que el SMTP está correcto, no necesita enviar un correo de verdad (no obstante, se podría hacer). Éste es un potente comprobador de servicios TCP que se puede usar para verificar cualquier servicio TCP de texto plano.
</p>