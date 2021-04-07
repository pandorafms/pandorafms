<?php
/*
 * @package Include/help/ja/
 */
?>

<h1>プロファイル</h1>

<p>Pandora FMS は、複数のエージェントグループで複数のユーザが異なる権限で作業できるウェブ管理ツールです。ユーザのパーミッションは、プロファイルで定義できます。</p>

<p>以下にコンソールでそれぞれの ACL でどのような権限があるかの一覧を示します。</p>

<table cellpadding=4 cellspacing=0 class='bg_f0'>
<tr><th class='bg_caca'>機能<th class='bg_caca'>ACL

<tr><td>エージェントデータ参照 (全てのタブ)<td>AR
<tr><td>概要表示<td>AR
<tr><td>グループ表示<td>AR
<tr><td>ビジュアルコンソール編集<td>RW
<tr><td>レポート作成<td>RW
<tr><td>ユーザ定義グラフ作成<td>RW
<tr><td>レポート、ビジュアルマップおよび、カスタムグラフ表示<td>RR
<tr><td>レポートテンプレートの適用<td>RR
<tr><td>レポートテンプレート作成<td>RM
<tr><td>インシデント作成<td>IW
<tr><td>インシデント参照<td>IR
<tr><td>インシデント削除<td>IW
<tr><td>他のユーザのインシデントの所有者になる<td>IM
<tr><td>他のユーザのインシデント削除<td>IM
<tr><td>イベント参照<td>ER
<tr><td>イベントの承諾とコメント<td>EW
<tr><td>イベント削除<td>EM
<tr><td>応答の実行<td>EW
<tr><td>イベントからインシデントを作成 (応答)<td>EW&IW
<tr><td>応答管理<td>PM
<tr><td>フィルタ管理<td>EW
<tr><td>イベントカラムのカスタマイズ<td>PM
<tr><td>イベントの所有者変更 / 再オープン<td>EM
<tr><td>ユーザ参照<td>AR
<tr><td>SNMP コンソール参照<td>AR
<tr><td>トラップの承諾<td>IW
<tr><td>メッセージ<td>IW
<tr><td>Cron ジョブ<td>PM
<tr><td>ツリー表示<td>AR
<tr><td>アップデートマネージャ (操作と管理)   <td>PM
<tr><td>拡張モジュールグループ<td>AR
<tr><td>エージェント管理<td>AW
<tr><td>リモートエージェント設定管理 <td>AW
<tr><td>エージェントへのアラート割り当て<td>LW
<tr><td>アラートテンプレート、アラート、コマンドの定義、アラート設定、削除<td>LM
<tr><td>グループ管理<td>PM
<tr><td>インベントリモジュール作成<td>PM
<tr><td>モジュール管理 (全てのサブオプションを含む)<td>PM
<tr><td>一括操作管理<td>AW
<tr><td>エージェント作成<td>AW
<tr><td>リモート設定の複製<td>AW
<tr><td>計画停止管理<td>AW
<tr><td>アラート管理<td>LW
<tr><td>ユーザ管理<td>UM
<tr><td>SNMP コンソール管理 (アラートと MIB ロード)<td>PM
<tr><td>プロファイル管理<td>PM
<tr><td>サーバ管理<td>PM
<tr><td>システム監査<td>PM
<tr><td>設定<td>PM
<tr><td>データベースメンテナンス<td>DM
<tr><td>管理拡張メニュー<td>PM
<tr><td>検索バー<td>AR
<tr><td>ポリシー管理<td>AW
<tr><td>エージェント / モジュール / アラートの無効化<td>AD
<tr><td>アラートの承諾<td>LM&AR または AW&LW
<tr><td>ネットワークマップ表示<td>MR
<tr><td>ネットワークマップ編集<td>MW
<tr><td>自分のネットワークマップ削除<td>MW
<tr><td>任意のネットワークマップ削除<td>MM
<tr><td>ビジュアルコンソール表示<td>VR
<tr><td>ビジュアルコンソール編集<td>VW
<tr><td>自分のビジュアルコンソール削除<td>VW
<tr><td>任意のビジュアルコンソール削除<td>VM

</table>
