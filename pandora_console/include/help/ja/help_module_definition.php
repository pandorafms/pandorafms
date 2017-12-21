<?php
/**
 * @package Include/help/ja
 */
?>
<h1>モジュール定義</h1>

エージェントには、次の2つのモードがあります:
<ul>
    <li><i>学習モード:</i> エージェントから通知されるモジュール情報を受け取ります。モジュールが定義されていない場合は自動的にそれが追加されます。エージェントの設定でこのモードを有効にすることをお勧めします。Pandora FMS が使いやすいです。<br>バージョン 4.0.3 からは、このモードの場合、コンソールが初回はエージェント設定ファイルの全設定内容を読み込みますが、その後はコンソールから変更可能で設定ファイル側の変更は反映されません。
</li>
<br>
    <li><i>通常モード:</i> このモードでは、モジュール設定を手動で実施する必要があります。自動設定は行われません。</li>
<br>
    <li><i>Autodisable mode:</i> In terms of creating agents and modules it behaves exactly the same as an agent in learning mode: when the first XML reaches it, the first agent is created and, on each report, if there are new modules they can also be added automatically. Nevertheless, when all modules from an agent that are in autodisable mode are also marked as unknown, the agent is automatically disabled. In any case, if the agent reports again, it gets enabled again on its own.</li>
</ul>
