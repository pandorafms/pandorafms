<?php
/**
 * @package Include/help/es
 */
?>
<h1>Definición de módulo</h1>

<p>
Existen tres modos para un agente:
</p>
<ul>
    <li><i>Modo aprendizaje:</i>Si el XML recibido del agente software contiene nuevos módulos, éstos serán automáticamente creados. Este es el comportamiento por defecto.</li>
<br>
    <li><i>Modo normal:</i>No se crearán nuevos módulos que lleguen en el XML si no han sido declarados previamente en la consola.</li>
<br>
    <li><i>Modo auto deshabilitado:</i>Similar al modo aprendizaje, en este modo, además, si todos los módulos pasan a estado desconocido el agente se deshabilitará automáticamente, pasando a habilitarse de nuevo si recibe nueva información.</li>
</ul>
