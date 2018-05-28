<?php
/**
 * @package Include/help/es
 */
?>
<h1>Validación de alerta</h1>
<p>
Validar una alerta solo cambia su bit de estado y limpia el estado &laquo;disparado&raquo;, de tal forma que si la alerta se dispara de nuevo el proceso continua. Está orientado a alertas con grandes umbrales, por ejemplo, 1 día. Si obtiene una alarma y la revisa y la marca como vista, probablemente quiera establecer su estado a verde y no quiere esperar 1 día a que se vuelva a poner verde.
</p>
