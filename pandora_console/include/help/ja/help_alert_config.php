<?php
/**
 * @package Include/help/ja
 */
?>
<h1>アラートアクションの設定</h1>
<br>アクションは、一般的な変数、フィールド1、フィールド2、... フィールド10 と共に(前の節で説明した)コマンドにリンクしたアラートのコンポーネントです。これらのアクションは、データの状態と関連づけるアラートテンプレートで利用します。<br>
以下に、設定するフィールドを説明します。<br><br>

    <b>名前(Name):</b> アクションの名前。<br>
    <b>グループ(Group):</b> アクションのグループ。<br>
    <br>コマンド(Command):</b> アラートが実行された時に利用されるコマンドの定義です。Pandora で定義されたコマンドを選択することができます。選択するコマンドによって、入力が必要なフィールドが異なります。<br>
    <b>閾値(Threshold):</b> アクション実行の閾値。<br>
    <b>コマンドプレビュー(Command Preview):</b> このフィールドには、システムが実行するコマンドが表示されます。編集はできません。<br>
    <b>フィールドX(Field X):</b> このフィールドでは、マクロ _field1_ から _field10_ までのマクロの値を定義します。これらは、必要に応じてコマンドで利用されます。これらのフィールドは、設定によりテキスト入力または選択となります。選択したコマンドに応じて、表示されるフィールドの数が変化します。以下に例を示します。<br><br>

email アクションを設定するには、_field1_ (送信先アドレス)、_field2_ (件名)、および _filed3_ (本文) のみを設定します。<br><br>

これらのフィールド内では、以下のマクロを利用できます。
<br><br>
<?php html_print_image ("images/help/actions.png", false, array('width' => '550px')); ?>
<br><br>
<br>

<p>
定義したモジュールマクロ以外に、次のマクロが利用できます:
<ul>
<li>_field2_ : ユーザ定義フィールド2</li>
<li>_field3_ : ユーザ定義フィールド3</li>
<li>_agent_ : アラートが発生したエージェント</li>
<li>_agentdescription_ : 発生したアラートの説明</li>
<li>_agentgroup_ : エージェントグループ名</li>
<li>_agentstatus : エージェントの現在の状態</li>
<li>_agentos_: Agent's operative system</li>
<li>_address_ : アラートが発生したエージェントのアドレス</li>
<li>_timestamp_ : アラートが発生した日時 (yy-mm-dd hh:mm:ss).</li>
<li>_timezone_: _timestamp_ で使用されるタイムゾーン名.</li>
<li>_data_ : アラート発生時のモジュールのデータ(値)</li>
<li>_alert_description_ : アラートの説明</li>
<li>_alert_threshold_ : アラートのしきい値</li>
<li>_alert_times_fired_ : アラートが発生した回数</li>
<li>_module_ : モジュール名</li>
<li>_modulegroup_ : モジュールグループ名</li>
<li>_moduledescription_ : アラートが発生したモジュールの説明</li>
<li>_modulestatus_ : モジュールの状態</li>
<li>_moduletags_ : モジュールに関連付けられたタグ</li>
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
<li>_groupother_ : グループに関するその他情報。グループの作成時に設定されます。</li>
<li>_email_tag_ : モジュールタグに関連付けられた Email。</li>

</ul>
<p>
</p>
<p>
例: Agent _agent_ has fired alert _alert_ with data _data_
</p>
