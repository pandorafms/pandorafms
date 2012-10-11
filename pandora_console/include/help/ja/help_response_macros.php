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
<li><b>イベントID:</b> _event_id_</li>
</ul> 

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
