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
これは、データベースがウェブサーバや <?php echo get_product_name(); ?> サーバと異なるサーバで動作している場合で、時刻にずれが生じている場合に便利です。
すべての <?php echo get_product_name(); ?> サーバと MySQL サーバの時刻は NTP により同期すべきですが、この設定を利用することにより、必ずしもそうする必要がなくなります。
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
$option = ['prominent' => 'timestamp'];
?>
<b>現在のシステムの時刻:</b> <?php ui_print_timestamp(time(), false, $option); ?>
<br />
<b>現在のデータベースの時刻:</b>
<?php
global $config;

switch ($config['dbtype']) {
    case 'mysql':
        $timestamp = db_process_sql('SELECT UNIX_TIMESTAMP();');
        $timestamp = $timestamp[0]['UNIX_TIMESTAMP()'];
    break;

    case 'postgresql':
        $timestamp = db_get_value_sql("SELECT ceil(date_part('epoch', CURRENT_TIMESTAMP));");
    break;

    case 'oracle':
        $timestamp = db_process_sql("SELECT ceil((sysdate - to_date('19700101000000','YYYYMMDDHH24MISS')) * (86400)) as dt FROM dual");
        $timestamp = $timestamp[0]['dt'];
    break;
}

ui_print_timestamp($timestamp, false, $option);
?>
<br />
<b>あなたのブラウザの時刻:</b> <script type="text/javascript">document.write (date);</script>
</p>
