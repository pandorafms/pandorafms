<?php
/**
 * @package Include/help/ja
 */
?>
<h1>エクスポートサーバ</h1>

<p><?php echo get_product_name(); ?> エンタープライズ版の実装では、さまざまな情報をプロファイルに分割するように設計すれば、データスケーリング機能のエクスポートサーバで仮想的に無限のモニタリング情報を配布することができます。</p>

<ul>
<li type="circle">名前: <?php echo get_product_name(); ?> サーバ名。</li>
<li type="circle">エクスポートサーバ: データをエクスポートするのに使うエクスポートサーバを選択します。</li>
<li type="circle">プレフィックス: 送信するデータのエージェント名に追加するプレフィックスです。例えば、"Farscape" という名前のエージェントがあり、エクスポートサーバのプレフィックスが "EU04" であれば、送信先のサーバでのエージェント名は、EU01-Farscape となります。</li>
<li type="circle">間隔: 時間間隔および未解決のデータを送信する頻度(秒)を定義します。</li>
<li type="circle">対象ディレクトリ: リモートでデータを置く対象のディレクトリ(SSH または FTP のみ)です。
<li type="circle">アドレス: データを受信するデータサーバのアドレスです。</li>
<li type="circle">転送モード: ファイル転送モードです。Local、SSH、FTP および Tentacle が選択できます。</li>
<li type="circle">ユーザ: FTP のユーザです。</li>
<li type="circle">パスワード: FTP のパスワードです。</li>
<li type="circle">ポート: ファイル転送に使うポートです。Tentacle であれば、41121 が標準ポートです。</li>
<li type="circle">拡張オプション: Tentacle で認証をするときなどに必要となる、追加オプションフィールドです。</li>
</ul>
