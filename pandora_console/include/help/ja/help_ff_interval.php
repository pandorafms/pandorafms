<?php
/**
 * @package Include/help/ja
 */
?>
<h1>連続抑制時の間隔</h1>

<br><br>

連続抑制回数が 0 より大きい場合、モジュールの状態変化が発生するには複数回値が変化した状態になる必要があります。このときモジュールの値を通常の間隔と異なる間隔でチェックしたい場合は、連続抑制時の間隔で設定できます。

たとえば、5分間隔の ping モジュールにおいて、連続抑制回数が 1 で連続抑制時の間隔が 60秒の場合、次のような動作をします。

<br><br>
<table>
<th>時間</th>
<th>データ</th>
<th>状態変化</th>
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

