<?php
/**
 * @package Include/help/es
 */
?>
<h1>Intervalo de Flip Flop del Módulo</h1>

<br><br>

Si el umbral de FF es mayor que 0, se necesitan varios valores consecutivos para cambiar el estado del módulo. Pero si desea que las subsiguientes comprobaciones se ejecuten con un intervalo diferente lo puede especificar mediante el intervalo de FF.

Por ejemplo, un módulo de ping con un intervalo de 5 minutos, un umbral de FF de 1 y un intervalo de FF de 60 segundos se comportaría de la siguiente forma:

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
    <td>1</td>
    <td>No</td>
</tr>
<tr>
    <td>12:10</td>
    <td>0</td>
    <td>No</td>
</tr>
<tr>
    <td><b>12:11</b></td>
    <td>1</td>
    <td>No</td>
</tr>
<tr>
    <td>12:16</td>
    <td>1</td>
    <td>No</td>
</tr>
<tr>
    <td>12:21</td>
    <td>0</td>
    <td>No</td>
</tr>
<tr>
    <td><b>12:22</b></td>
    <td>0</td>
    <td><b>Yes</b></td>
</tr>
</table>

<br><br>

