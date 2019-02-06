<?php
/**
 * @package Include/help/ja
 */
?>
<h1>ウェブモニタリング</h1>

<p>
拡張ウェブモニタリングは、<?php echo get_product_name(); ?> エンタープライズ版の Goliat で動作する機能です。
<br><br>
以下に、GOLIAT ウェブチェックモジュールのサンプルを示します。
<br>
</p>
<pre>
task_begin
post http://galaga.artica.es/monitoring/index.php?login=1
variable_name nick
variable_value demo
variable_name pass
variable_value demo
cookie 1
resource 1
task_end

task_begin
get http://galaga.artica.es/monitoring/index.php?sec=messages&amp;sec2=operation/messages/message
cookie 1
resource 1
check_string Read messages
task_end
</pre>
<p>
次のマクロが利用できます:
</p>
<ul>
<li>_agent_ : アラートが発生したエージェント</li>
<li>_agentdescription_ : 発生したアラートの説明</li>
<li>_agentgroup_ : エージェントグループ名</li>
<li>_agentstatus : エージェントの現在の状態</li>
<li>_address_ : アラートが発生したエージェントのアドレス</li>
<li>_module_ : モジュール名</li>
<li>_modulegroup_ : モジュールグループ名</li>
<li>_moduledescription_ : アラートが発生したモジュールの説明</li>
<li>_modulestatus_ : モジュールの状態</li>
<li>_moduletags_ : モジュールに関連付けられたタグ</li>
<li>_id_agent_ : エージェントのID / Webコンソールへのリンクを生成するのに便利です</li>
<li>_policy_ : モジュールが属するポリシー名 (存在する場合)</li>
<li>_interval_ : モジュールの実行間隔</li>
<li>_target_ip_ : モジュールの対象IPアドレス</li>
<li>_target_port_ : モジュールの対象ポート</li>
<li>_plugin_parameters_ : モジュールのプラグインパラメータ</li>
<li>_email_tag_ : モジュールタグに関連付けられた Email。</li>

</ul>
<p>
例:
<pre>
task_begin
get http://_address_
check_string _module_
task_end
</pre>
</p>
