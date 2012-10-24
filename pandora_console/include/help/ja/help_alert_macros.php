<?php
/**
 * @package Include/help/ja
 */
?>
<h1>アラートマクロ</h1>

<p>
次のマクロが利用できます:
</p>
<ul>
<li>_field1_ : ユーザ定義フィールド1</li>
<li>_field2_ : ユーザ定義フィールド2</li>
<li>_field3_ : ユーザ定義フィールド3</li>
<li>_agent_ : アラートが発生したエージェント</li>
<li>_agentdescription_ : 発生したアラートの説明</li>
<li>_agentgroup_ : エージェントグループ名</li>
<li>_agentstatus : Current status of the agent.</li>
<li>_address_ : アラートが発生したエージェントのアドレス</li>
<li>_timestamp_ : アラートが発生した日時 (yy-mm-dd hh:mm:ss).</li>
<li>_data_ : アラート発生時のモジュールのデータ(値)</li>
<li>_alert_description_ : アラートの説明</li>
<li>_alert_threshold_ : アラートのしきい値</li>
<li>_alert_times_fired_ : アラートが発生した回数</li>
<li>_module_ : モジュール名</li>
<li>_modulegroup_ : モジュールグループ名</li>
<li>_moduledescription_ : アラートが発生したモジュールの説明</li>
<li>_modulestatus_ : Status of the module.</li>
<li>_moduletags_ : Tags associated to the module.</li>
<li>_alert_name_ : アラート名</li>
<li>_alert_priority_ : アラート優先順位(数値)</li>
<li>_alert_text_severity_ : テキストでのアラートの重要度 (Maintenance, Informational, Normal Minor, Warning, Major, Critical)</li>
<li>_event_text_severity_ : (イベントアラートのみ) イベント(アラートの発生元)のテキストでの重要度 (Maintenance, Informational, Normal Minor, Warning, Major, Critical)</li>
<li>_id_agent_ : エージェントのID / Webコンソールへのリンクを生成するのに便利です</li>
<li>_id_alert_ : アラートの(ユニークな)ID / 他のソフトウエアパッケージとの連携に利用できます</li>
<li>_policy_ : モジュールが属するポリシー名 (存在する場合)</li>
<li>_interval_ : モジュールの実行間隔</li>
<li>_target_ip_ : モジュールの対象IPアドレス</li>
<li>_target_port_ : モジュールの対象ポート</li>
<li>_plugin_parameters_ : モジュールのプラグインパラメータ</li>
<li>_groupcontact_ : Group contact information.	Configured when the group is created.</li>
<li>_groupother_ : Other information about the group. Configured when the group is created.</li>

</ul>
<p>
例: Agent _agent_ has fired alert _alert_ with data _data_
</p>
