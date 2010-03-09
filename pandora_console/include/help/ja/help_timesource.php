<?php
/**
 * @package Include/help/ja
 */
?>
<h1>日時データソース</h1>

<p>
日時データソースに、システムの日時データかデータベースに記録されているデータかどちらをを利用するかを定義します。<br /><br />
</p>
<p>
これは、データベースがウェブサーバや Pandora FMS サーバと異なるサーバで動作している場合で、時刻にずれが生じている場合に便利です。
すべての Pandora サーバと MySQL サーバの時刻は NTP により同期すべきですが、この設定を利用することにより、必ずしもそうする必要がなくなります。
</p>
<p>
注: データベースではクエリがキャッシュされますが、内部関数が呼ばれるかどうかに関わらずその時点のシステムタイムが返されますので、日時データは若干異なる場合があります。
</p>
<p>
以下の例は、現在の日時を異なるデータソースから表示しています。
<script type="text/javascript">
var date = new Date; // Generic JS date object
var unixtime_ms = date.getTime(); // Returns milliseconds since the epoch
var unixtime = parseInt(unixtime_ms / 1000);
</script>
</p>
<p>
<?php
$option = array ("prominent" => "timestamp");
?>
<b>現在のシステムの時刻:</b> <?php print_timestamp (time (), false, $option); ?>
<br />
<b>現在のデータベースの時刻:</b> <?php print_timestamp (get_db_sql ("SELECT UNIX_TIMESTAMP()"), false, $option); ?>
<br />
<b>あなたのブラウザの時刻:</b> <script type="text/javascript">document.write (date);</script>
</p>
