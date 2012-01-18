<?php
/**
 * @package Include/help/ja
 */
?>
<h1>Nfdump フィルターの書式</h1>

フィルターの書式は、tcpdump ととても似ています。例えば次の通りです。

<ul>
<li>192.168.0.1 発または宛の通信をキャプチャする場合:</li>
<pre>
host 192.168.0.1
</pre>

<ul>
<li>192.168.0.1 宛の通信をキャプチャする場合:</li>
<pre>
dst host 192.168.0.1
</pre>

<li>192.168.0.0/24 発の通信をキャプチャする場合:</li>
<pre>
src net 192.168.0.0/24
</pre>

<li>HTTP および HTTPS の通信をキャプチャする場合:</li>
<pre>
(port 80) or (port 443)
</pre>

<li>DNS 以外の全通信をキャプチャする場合:</li>
<pre>
port not 53
</pre>

<li>192.168.0.1 宛の SSH 通信をキャプチャする場合:</li>
<pre>
(port 22) and (dst host 192.168.0.1)
</pre>
