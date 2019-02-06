<?php
/**
 * @package Include/help/en
 */
?>
<h1>Module Flip Flop Timeout</h1>

<br><br>

If FF threshold is greater than 0, several consecutive values are required to change the status of a module. This works well for synchronous modules, but since asynchronous modules do not send data at regular intervals, checking for consecutive values may not be that useful if they are far away in time. Thus, if FF timeout is greater than 0, consecutive values must occur within the configured time interval.

For example, an asynchronous proc module with a FF threshold of 1 and a FF timeout of 600 (10 minutes) would behave in the following way:

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

