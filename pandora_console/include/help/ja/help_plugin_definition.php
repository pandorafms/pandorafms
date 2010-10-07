<?php
/**
 * @package Include/help/ja
 */
?>
<h1>プラグインの作成</h1>

プラグイン作成インタフェースは、それぞれのプラグインに対して渡す必要があるオプションを Pandora FMS プラグインサーバに設定するためのものです。
設定したオプションと共にそれに対応するデータが渡されます。
<br><br>
たとえば、Informix の表空間をチェックするための "Aleph" というプラグインがあり、IP アドレスが "192.168.50.2"、ユーザ名が "Calabria"、そしてパスワードが "malcolm45" であったとします。
このプラグインは、表空間の問題有無、1秒あたりのクエリ数、負荷、断片化およびメモリ使用量を返します。
<br><br>
このプラグインは、次のようなインタフェースを持っています。
<br>
<pre>
	informix_plugin_pandora -H ip_address -U user -P password -T tablespace -O operation
</pre>
<br>
"operation" は、"status", "qps", "load", "fragment" そして "memory" です。
Pandora FMS で利用するために、このプラグインは単一の値を返します。
このプラグインを Pandora で定義するには、次のように各フィールドを定義します。
<br><br>

<table cellpadding=4 cellspacing=4 class=databox width=80%>
<tr>
<td valign='top'>プラグインコマンド</td><td>/usr/share/pandora/util/plugins/informix_plugin_pandora (プラグインのデフォルト配置ディレクトリ)</td>
<tr>
<td>最大タイムアウト</td><td> 15 (例)</td>
</tr>
<tr>
<td>IPアドレスオプション</td><td> -H</td>

<tr>
<td>Port オプション</td><td> 未記入</td>
</tr>

<tr>
<td>User オプション</td><td> -U</td>
</tr>

<tr>
<td>Password オプション</td><td> -P</td>
</tr>

</table>
<br>

このプラグインを使うモジュールを作成する必要がある場合は、「モジュール管理」にてプラグインを選択する必要があります(新しいプラグインは、選択メニューに表示されます)。
その後、IPアドレス、ユーザ名、パスワードを設定します。
Pandora FMS は、外部プラグインを実行するときに、こられを引数として渡します。
<br><br>
引数の入れ替えは常にあり得ますが、一般化することはできません。
上記のシナリオでは、"tablespace" パラメータを利用しています。
これは、Informix 特有のものですが、それ以外のツールでもさまざまな特別なオプションがあります。
こういった任意のオプションを渡す場合には、「プラグインパラメータ」フィールドを利用します。
上記の例であれば、"-T tablespace" を設定します。
<br><br>
もし他の表空間を扱いたい場合は、"-T" オプションの後を異なる設定にした別のモジュールを作成します。
<br><br>
もちろん、「プラグインパラメータ」には、複数の引数を定義できます。
設定されたすべての引数がプラグインに渡されます。
