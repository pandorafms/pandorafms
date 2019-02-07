<?php
/**
 * @package Include/help/es
 */
?>
<h1>Timeout de Flip Flop del módulo</h1>

<br><br>

Si el umbral de FF es mayor que 0, se necesitan varios valores consecutivos para cambiar el estado del módulo. Pero si desea que las subsiguientes comprobaciones se ejecuten con un intervalo diferente lo puede especificar mediante el intervalo FF. Esto funciona bien para módulos síncronos, pero como los módulos asíncronos no envían datos en intervalos regulares, comprobar valores consecutivos puede no resultar muy útil si están muy separados en el tiempo. Así, si el timeout de FF del módulo es mayor que 0, los valores consecutivos deben ocurrir dentro del intervalo de tiempo especificado.

Por ejemplo, un módulo asíncrono de tipo proc con un umbral de FF de 1 y un timeout de FF de 600 (10 minutos) se comportaría de la siguiente forma:

<br><br>
<table>
<th>Time</th>
<th>Data</th>
<th>Status change</th>
<tr>
    <td>12:00</td>
    <td>1</td>
    <td>No</td>
</tr>
<tr>
    <td>12:05</td>
    <td>0</td>
    <td>No</td>
</tr>
<tr>
    <td><b>12:20</b></td>
    <td>0</td>
    <td>No</td>
</tr>
<tr>
    <td>12:25</b></td>
    <td>1</td>
    <td>No</td>
</tr>
<tr>
    <td>12:45</td>
    <td>0</td>
    <td>No</td>
</tr>
<tr>
    <td><b>12:50</b></td>
    <td>0</td>
    <td><b>Yes</b></td>
</tr>
</table>

<br><br>

