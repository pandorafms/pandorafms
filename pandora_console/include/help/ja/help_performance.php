<?php
/**
 * @package Include/help/ja
 */
?>
<h1>パフォーマンス設定</h1>


<b>イベントデータ保持日数(Max. days before delete events)</b>
<br><br>
イベントデータを削除せずに保持する日数。
<br><br>
<b>トラップデータ保持日数(Max. days before delete traps)</b>
<br><br>
トラップデータを削除せずに保持する日数。
<br><br>
<b>監査イベントデータ保持日数(Max. days before delete audit events)</b>
<br><br>
監査イベントデータを削除せずに保持する日数。
<br><br>
<b>文字列データ保持日数(Max. days before delete string data)</b>
<br><br>
文字列データを削除せずに保持する日数。
<br><br>
<b>GIS データ保持日数(Max. days before delete GIS data)</b>
<br><br>
GIS データを削除せずに保持する日数。
<br><br>
<b>データ保持日数(Max. days before purge)</b>
<br><br>
データを削除せずに保持する最大日数。このパラメータは、インベントリデータを削除せずに保持する最大日数としても利用されます。ヒストリーデータベースを設定している場合は、ヒストリーデータベースへ転送する対象の日数よりも大きい値を設定する必要があります。ヒストリーデータベースはデータを削除しません。
<br><br>
<b>データ保持日数(丸め込みなし)(Max. days before compact data)</b>
<br><br>
データを丸め込みせずに保持する日数。
<br><br>
<b>データ縮小時の丸め込み単位時間(1〜20)(Compact interpolation in hours (1 Fine-20 bad))</b>
<br><br>
単位時間。1が最もよく 20がその逆です。1もしくは、1に近い値をお勧めします。
<br><br>
<b>SLA計算対象期間(秒)(SLA period (seconds))</b>
<br><br>
エージェントの SLA タブで SLA を計算するときのデフォルトの時間(秒単位)。SLA は、エージェントに定義されたモジュールの障害または正常値を元に、自動的に計算されます。
<br><br>
<b>イベント表示期間(時間)(Default hours for event view)</b>
<br><br>
イベントフィルタのデフォルトの時間。値が 24時間の場合、直近の 24時間で発生したイベントのみ表示されます。
<br><br>
<b>リアルタイム更新の利用(Use realtime statistics)</b>
<br><br>
リアルタイム更新を利用するかどうかの設定です。
<br><br>
<b>バッチ更新間隔(秒)(Batch statistics period (secs))</b>
<br><br>
リアルタイム更新が無効の場合、バッチ更新間隔を指定します。
<br><br>
<b>エージェントアクセスグラフの利用(Use agent access graph)</b>
<br><br>
エージェントアクセスグラフは、1日(24時間)に、1時間あたりエージェントが何回接続したかを表示するものです。これは、それぞれのエージェントがどのくらいの頻度でアクセスしているかを知るのに便利です。日付の処理には時間がかかる場合があるため、リソースが少ない場合は無効化することをお勧めします。
<br><br>
<b>不明モジュール保持日数(Max. days before delete unknown modules)</b>
<br><br>
不明モジュールを削除せずに保持する日数。
<br><br>
<i>**これらの全てのパラメータは、DB Tool というツールを実行したときに利用されます。</i>
