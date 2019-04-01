<?php
/**
 * @package Include/help/ja
 */
?>イベント応答マクロ</h1>

<p>
応答対象(コマンドやURL)には、カスタマイズするためにマクロを利用できます。
<br><br>
次のマクロを利用できます。

<ul>
<li><b>エージェントアドレス:</b> _agent_address_</li>
<li><b>エージェントID:</b> _agent_id_</li>
<li><b>Event related alert ID:</b> _alert_id_</li>
<li><b>Date on which the event occurred:</b> _event_date_</li>
<li><b>Extra ID:</b> _event_extra_id_</li>
<li><b>イベントID:</b> _event_id_</li>
<li><b>Event instructions:</b> _event_instruction_</li>
<li><b>Event severity ID:</b> _event_severity_id_</li>
<li><b>Event severity (translated by <?php echo get_product_name(); ?> console):</b> _event_severity_text_</li>
<li><b>Event source:</b> _event_source_</li>
<li><b>Event status (new, validated or event in process):</b> _event_status_</li>
<li><b>Event tags separated by commas:</b> _event_tags_</li>
<li><b>Full text of the event:</b> _event_text_</li>
<li><b>Event type (System, going into Unknown Status...):</b> _event_type_</li>
<li><b>Date on which the event occurred in utimestamp format:</b> _event_utimestamp_</li>
<li><b>Group ID:</b> _group_id_</li>
<li><b>Group name in database:</b> _group_name_</li>
<li><b>Event associated module address:</b> _module_address_</li>
<li><b>Event associated module ID:</b> _module_id_</li>
<li><b>Event associated module name:</b> _module_name_</li>
<li><b>Event owner user:</b> _owner_user_</li>
<li><b>User ID:</b> _user_id_</li>
<li><b>Id of the user who fires the response:</b> _current_user_</li>
</ul> 

<h4>Custom fields</h4>

Custom event fields are also available in event response macros. They would
have <b>_customdata_*_</b> form where the asterisk (*) would have to be 
replaced by the custom field key you want to use.

<h3>基本的な利用方法</h3>
例として、イベントに関連付けしたエージェントへの ping を示します。
<br><br>
コマンドを次のように設定します: <i>ping -c 5 _agent_address_</i>
<br><br>
設定パラメータがある場合は、それもマクロとして利用できます。
 
<h3>パラメータマクロ</h3>
例として、URL のパラメータをカスタマイズします。
<br><br>
パラメータを次のように設定します: <i>User,Section</i>
<br><br>
そして、URL を次のように設定します: <i>http://example.com/index.php?user=_User_&amp;section=_Section_</i>
</p>
