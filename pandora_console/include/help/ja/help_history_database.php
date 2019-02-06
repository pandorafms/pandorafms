<?php
/**
 * @package Include/help/ja
 */
?>
<h1>ヒストリデータベース</h1>

ヒストリデータベースは、メインの <?php echo get_product_name(); ?> データベースの応答速度を確保するために、古いモジュールデータを毎日移動させるためのデータベースです。データは、<?php echo get_product_name(); ?> コンソールでレポートやグラフを表示するときにシームレスに参照できます。
<br><br>
<b>ヒストリデータベースの設定</b>
<br><br>
ヒストリデータベースを設定するには、次の手順を行います。
<br><br>
<ol>
<li>新たなヒストリデータベースの作成
<br><br>
<li>新たなデータベースに必要なテーブルを作成します。<?php echo get_product_name(); ?> コンソールと共に提供されている DB Tool スクリプトが利用できます。
<br><br>
<i>cat pandoradb.sql | mysql -u user -p -D history_db</i>
<br><br>
<li>pandora ユーザにヒストリデータベースへアクセスできる権限を設定します。
<br><br>
<i>Mysql Example: GRANT ALL PRIVILEGES ON pandora.* TO 'pandora'@'IP' IDENTIFIED BY 'password'</i>
<br><br>
<li><?php echo get_product_name(); ?> コンソールの 設定(Setup) -> ヒストリデータベース(History database) のメニューへ行き、ホスト名、ポート番号、データベース名および、新たなデータベースのユーザ名とパスワードを入力します。
</ol>
<br><br>
<?php html_print_image('images/help/historyddbb.png', false, ['width' => '550px']); ?>
<br><br>
"日間"に指定した日数より古いデータが、"ステップ"に指定したブロックサイズでヒストリデータベースに移動されます。負荷上昇を避けるために、"遅延"に指定した秒数だけブロック間の転送を待ちます。
<br><br>
それぞれのフィールドの意味は次の通りです。
<br><br>
<ol>
   <b>ヒストリデータベースの有効化(Enable history database):</b> ヒストリデータベース機能の有効・無効設定。
<br><br>
   <b>ホスト(Host):</b> ヒストリデータベースのホスト名.

<br><br>
    <b>ポート(Port):</b> ヒストリデータベースのポート番号。
<br><br>
   <b>データベース名(Database name):</b> ヒストリデータベースのデータベース名。
<br><br>
   <b>データベースユーザ(Database user):</b> ヒストリデータベースへアクセスするユーザ名。
<br><br>
   <b>データベースパスワード(Database password):</b> ヒストリデータベースへアクセスするパスワード。
<br><br>
   <b>日間(Days):</b> ヒストリデータベースへデータを転送しない日数。
<br><br>
   <b>ステップ(Step):</b> データ転送バッファサイズ。値が小さいとデータ転送が遅くなりますが、メインのデータベースのパフォーマンス影響は少なくなります。
<br><br>
   <b>遅延(Delay):</b> メインデータベースからヒストリデータベースへのデータ転送待ち時間。
<br><br>
</ol> 
