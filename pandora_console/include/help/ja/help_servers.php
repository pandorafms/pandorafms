<?php
/*
 * @package Include/help/ja
 */
?>

<h1>サーバ管理</h1>

<p><?php echo get_product_name(); ?> サーバは、定義された監視を実行する要素です。監視をし結果に応じて状態を変更します。また、データの状態に応じてアラートを生成します。</p>

<p><?php echo get_product_name(); ?> のデータサーバは、冗長化および負荷分散が可能です。大きなシステムでは、大量の機能を扱ったり物理的に分散した情報を扱うために、さまざまな Pandora FMS サーバを同時に利用可能です。</p>

<p><?php echo get_product_name(); ?> サーバは、各要素に問題が無いか、また、アラートが定義されているかを常に確認します。何かあると、アラームに定義された SMS 送信、e-mail 送信、スクリプトの実行など、アクションを実行します。l</p>

<ul>
<li type="circle">データサーバ(Data Server)</li>
<li type="circle">ネットワークサーバ(Network Server)</li>
<li type="circle">SNMPサーバ(SNMP Server)</li>
<li type="circle">WMIサーバ(WMI Server)</li>
<li type="circle">自動検出サーバ(Recognition Server)</li>
<li type="circle">プラグインサーバ(Plugins Server)</li>
<li type="circle">予測サーバ(Prediction Server)</li>
<li type="circle">WEBテストサーバ(WEB Test Server)</li>
<li type="circle">エクスポートサーバ(Export Server)</li>
<li type="circle">インベントリサーバ(Inventory Server)</li>
</ul>
