<?php
/**
 * @package Include/help/ja
 */
?>
<h1>アラートマクロ</h1>

<p>
定義したモジュールマクロ以外に、次のマクロが利用できます:
</p>
<ul>
<li>_field1_ : ユーザ定義フィールド1</li>
<li>_field2_ : ユーザ定義フィールド2</li>
<li>_field3_ : ユーザ定義フィールド3</li>
<li>_field4_ : ユーザ定義フィールド4</li>
<li>_field5_ : ユーザ定義フィールド5</li>
<li>_field6_ : ユーザ定義フィールド6</li>
<li>_field7_ : ユーザ定義フィールド7</li>
<li>_field8_ : ユーザ定義フィールド8</li>
<li>_field9_ : ユーザ定義フィールド9</li>
<li>_field10_ : ユーザ定義フィールド10</li>
<li>_agent_ : アラートが発生したエージェント</li>
<li>_agentcustomfield_<i>n</i>_ : エージェントカスタムフィールド番号<i>n</i> (例: _agentcustomfield_9_). </li>
<li>_agentcustomid_: エージェントカスタムID</li>
<li>_agentdescription_ : 発生したアラートの説明</li>
<li>_agentgroup_ : エージェントグループ名</li>
<li>_agentstatus_ : エージェントの現在の状態</li>
<li>_agentos_: エージェントの OS</li>
<li>_address_ : アラートが発生したエージェントのアドレス</li>
<li>_timestamp_ : アラートが発生した日時 (yy-mm-dd hh:mm:ss).</li>
<li>_timezone_: _timestamp_ で使用されるタイムゾーン名.</li>
<li>_data_ : アラート発生時のモジュールのデータ(値)</li>
<li>_alert_description_ : アラートの説明</li>
<li>_alert_threshold_ : アラートのしきい値</li>
<li>_alert_times_fired_ : アラートが発生した回数</li>
<li>_module_ : モジュール名</li>
<li>_modulecustomid_: モジュールカスタムID</li>
<li>_modulegroup_ : モジュールグループ名</li>
<li>_moduledescription_ : アラートが発生したモジュールの説明</li>
<li>_modulestatus_ : モジュールの状態</li>
<li>_alert_name_ : アラート名</li>
<li>_alert_priority_ : アラート優先順位(数値)</li>
<li>_alert_text_severity_ : テキストでのアラートの重要度 (Maintenance, Informational, Normal Minor, Warning, Major, Critical)</li>
<li>_event_text_severity_ : (イベントアラートのみ) イベント(アラートの発生元)のテキストでの重要度 (Maintenance, Informational, Normal Minor, Warning, Major, Critical)</li>
<li>_event_id_ : (イベントアラートのみ) アラート発生元のイベントID</li>
<li>_id_agent_ : エージェントのID / Webコンソールへのリンクを生成するのに便利です</li>
<li>_id_group_ : エージェントグループのID</li>
<li>_id_alert_ : アラートの(ユニークな)ID / 他のソフトウエアパッケージとの連携に利用できます</li>
<li>_policy_ : モジュールが属するポリシー名 (存在する場合)</li>
<li>_interval_ : モジュールの実行間隔</li>
<li>_target_ip_ : モジュールの対象IPアドレス</li>
<li>_target_port_ : モジュールの対象ポート</li>
<li>_plugin_parameters_ : モジュールのプラグインパラメータ</li>
<li>_groupcontact_ : グループコンタクト情報。グループの作成時に設定されます。</li>
<li>_groupcustomid_: グループカスタムID</li>
<li>_groupother_ : グループに関するその他情報。グループの作成時に設定されます。</li>
<li>_name_tag_ : モジュールに関連付けられたタグの名前。</li>
<li>_email_tag_ : モジュールタグに関連付けられた Email。</li>
<li>_phone_tag_ : モジュールタグに関連付けられた電話番号。</li>
<li>_moduletags_ : モジュールタグに関連付けられた URL。</li>
<li>_alert_critical_instructions_: モジュールの障害状態時手順。</li>
<li>_alert_warning_instructions_: モジュールの警告状態時手順。</li>
<li>_alert_unknown_instructions_: モジュールの不明状態時手順。</li>
<li>_modulegraph_<i>n</i>h_: (>=6.0) (<i>eMail</i>コマンドを使うアラートのみ)
n で示す期間の base64 でエンコードされたモジュールグラフを返します。(例: _modulegraph_24h_) サーバとコンソールの API の設定が正しくできている必要があります。この設定は、サーバの設定ファイルで行います。 </li>
</ul>

<p>
例: Agent _agent_ has fired alert _alert_ with data _data_
</p>
