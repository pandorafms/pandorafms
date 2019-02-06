<?php
/**
 * @package Include/help/en
 */
?>
<h1>Module Flip Flop Interval</h1>

<br><br>

If FF threshold is greater than 0, several consecutive values are required to change the status of a module. But if you want subsequent cheks to be performed at a different interval you can specify it with FF interval.

For example, a ping module with a 5 minute interval, a FF threshold of 1 and a FF interval of 60 seconds would behave in the following way:

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

