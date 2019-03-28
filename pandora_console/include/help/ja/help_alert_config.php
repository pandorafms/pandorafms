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
    <br>コマンド(Command):</b> アラートが実行された時に利用されるコマンドの定義です。<?php echo get_product_name(); ?> で定義されたコマンドを選択することができます。選択するコマンドによって、入力が必要なフィールドが異なります。<br>
    <b>閾値(Threshold):</b> アクション実行の閾値。<br>
    <b>コマンドプレビュー(Command Preview):</b> このフィールドには、システムが実行するコマンドが表示されます。編集はできません。<br>
    <b>フィールドX(Field X):</b> このフィールドでは、マクロ _field1_ から _field10_ までのマクロの値を定義します。これらは、必要に応じてコマンドで利用されます。これらのフィールドは、設定によりテキスト入力または選択となります。選択したコマンドに応じて、表示されるフィールドの数が変化します。以下に例を示します。<br><br>

email アクションを設定するには、_field1_ (送信先アドレス)、_field2_ (件名)、および _filed3_ (本文) のみを設定します。<br><br>

アクション作成時には 3つのフィールドのみ設定できます。これらのフィールド内では、以下のマクロを利用できます。
<br><br>
<?php html_print_image('images/help/actions.png', false, ['width' => '550px']); ?>
<br><br>
<br>

<p>
定義したモジュールマクロ以外に、次のマクロが利用できます:
<ul>
    <li>_address_ : アラートが発生したエージェントのアドレス</li>
    <li>_address_n_ : "n" で示される位置に対応するエージェントのアドレス。例: address_1_ , address_2_</li>
    <li>_agent_: アラートが発生したエージェントの別名。別名が無い場合は、エージェント名が利用されます。</li>
    <li>_agentalias_: アラートが発生したエージェントの別名</li>
    <li>_agentcustomfield_<i>n</i>_ : エージェントカスタムフィールド番号<i>n</i> (例: _agentcustomfield_9_). </li>
    <li>_agentcustomid_: エージェントカスタムID</li>
    <li>_agentdescription_ : 発生したアラートの説明</li>
    <li>_agentgroup_ : エージェントグループ名</li>
    <li>_agentname_: アラートが発生したエージェント.</li>
    <li>_agentos_: エージェントの OS</li>
    <li>_agentstatus_ : エージェントの現在の状態</li>
    <li>_alert_critical_instructions_: モジュールが障害状態の場合の手順</li>
    <li>_alert_description_ : アラートの説明</li>
    <li>_alert_name_ : アラート名</li>
    <li>_alert_priority_ : アラート優先順位(数値)</li>
    <li>_alert_text_severity_ : テキストでのアラートの重要度 (Maintenance, Informational, Normal Minor, Warning, Major, Critical)</li>
    <li>_alert_threshold_ : アラートのしきい値</li>
    <li>_alert_times_fired_ : アラートが発生した回数</li>
    <li>_alert_unknown_instructions_: モジュールが不明状態の場合の手順</li>
    <li>_alert_warning_instructions_: モジュールが警告状態の場合の手順</li>
    <li>_all_address_ : アラートを発報した全エージェントのアドレス</li>
    <li>_data_ : アラート発生時のモジュールのデータ(値)</li>
    <li>_email_tag_ : モジュールタグに関連付けられた Email。</li>
    <li>_event_cfX_ : (イベントアラートのみ) アラートを発報したイベントのカスタムフィールドのキー。 例えば、キーが IPAM のカスタムフィールドがある場合、_event_cfIPAM_ マクロにて値を得ることができます。</li>
    <li>_event_description_ :  (イベントアラートのみ) <?php echo get_product_name(); ?> イベントの説明 です</li>
    <li>_event_extra_id_: (イベントアラートのみ) 拡張 ID</li>
    <li>_event_id_ : (イベントアラートのみ) アラート発生元のイベントID</li>
    <li>_event_text_severity_ : (イベントアラートのみ) イベント(アラートの発生元)のテキストでの重要度 (Maintenance, Informational, Normal Minor, Warning, Major, Critical)</li>
    <li>_eventTimestamp_: Timestamp in which the event was created.</li>
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
    <li>_field11_ : ユーザ定義フィールド11</li>
    <li>_field12_ : ユーザ定義フィールド12</li>
    <li>_field13_ : ユーザ定義フィールド13</li>
    <li>_field14_ : ユーザ定義フィールド14</li>
    <li>_field15_ : ユーザ定義フィールド15</li>
    <li>_groupcontact_ : グループコンタクト情報。グループの作成時に設定されます。</li>
    <li>_groupcustomid_: グループカスタムID</li>
    <li>_groupother_ : グループに関するその他情報。グループの作成時に設定されます。</li>
    <li>_homeurl_ : 公開 URL へのリンク。URLは、基本設定メニューで設定されている必要があります。</li>
    <li>_id_agent_ : エージェントのID / Webコンソールへのリンクを生成するのに便利です</li>
    <li>_id_alert_ : アラートの(ユニークな)ID / 他のソフトウエアパッケージとの連携に利用できます</li>
    <li>_id_group_ : エージェントグループのID</li>
    <li>_id_module_ : モジュールID</li>
    <li>_interval_ : モジュールの実行間隔</li>
    <li>_module_ : モジュール名</li>
    <li>_modulecustomid_: モジュールカスタムID</li>
    <li>_moduledata_X_: モジュール X の最新データ (モジュール名にスペースを含めることはできません)</li>
    <li>_moduledescription_ : アラートが発生したモジュールの説明</li>
    <li>_modulegraph_nh_: (eMail コマンドを用いるアラートの場合のみ) n時間の期間のモジュールグラフのイメージを返します(例: _modulegraph_24h_)。 サーバとコンソールの API の接続を正しく設定する必要があります。これはサーバの設定で行います。</li>
    <li>_modulegraphth_<i>n</i>h_:上記のマクロと同じですが、モジュールに設定された障害および警告閾値を含みます。</li>
    <li>_modulegroup_ : モジュールグループ名</li>
    <li>_modulestatus_ : モジュールの状態</li>
    <li>_moduletags_ : モジュールに関連付けられたタグ</li>
    <li>_name_tag_ : モジュールに関連付けられたタグの名前。</li>
    <li>_phone_tag_ : モジュールタグに関連付けられた電話番号。</li>
    <li>_plugin_parameters_ : モジュールのプラグインパラメータ</li>
    <li>_policy_ : モジュールが属するポリシー名 (存在する場合)</li>
    <li>_prevdata_ : アラートを発報する前のモジュールデータ</li>
    <li>_rca_: 根本原因分析 (サービスのみ).</li>
    <li>_server_ip_ : エージェントが割り当てられているサーバ IP。</li>
    <li>_server_name_ : エージェントが割り当てられているサーバ名。 </li>
    <li>_target_ip_ : モジュールの対象IPアドレス</li>
    <li>_target_port_ : モジュールの対象ポート</li>
    <li>_timestamp_ : アラートが発生した日時 (yy-mm-dd hh:mm:ss).</li>
    <li>_timezone_: _timestamp_ で使用されるタイムゾーン名.</li>
</ul>
<p>
</p>
<p>
例: Agent _agent_ has fired alert _alert_ with data _data_
</p>
