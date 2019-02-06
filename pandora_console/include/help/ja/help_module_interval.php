<?php
/*
    Include package help/ja
*/
?>

<p>モジュールインターバル(module interval)は、モジュールがデータを返す間隔を定義します。この設定値の 2倍の時間が経過しても新たなデータがない場合は、以下のいずれかの状態となります。
<ol>
<li>非同期(asynchronous)モジュールの場合は、状態が正常にリセットされます。</li>
<li>同期(synchronous)モジュールの場合は、状態が不明になります。</li>
</ol>
</p>
