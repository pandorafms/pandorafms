<?php
/**
 * @package Include/help/ja
 */
?>
<h1>TCP チェック</h1>

<p>
このモジュールは、対象の IP およびポート番号に対し文字列を送信し、応答を待ちます。また、あらかじめ定義した期待する応答であるかどうかのチェックもできます。
もし、「TCP 送信文字列」および「TCP 受信文字列」フィールドに何も定義されていない場合は、対象ポートへの接続確認のみを行います。
</p>
<p>
リターンコードを送信するためには、^M を利用します。
また、複数の要求および応答の組み合わせも可能です。
複数の要求と応答の区切りには | を利用します。
</p>


<h2>例1) WEB サービスの確認</h2>

<p>
www.yahoo.com の HTTP 応答を確認したいと考えた場合、「TCP 送信文字列」に以下の設定を行います。
<br><br>
GET / HTTP/1.0^M^M
<br /><br />
そして、「TCP 受信文字列」に以下の設定を行います。
<br /><br />
200 OK
<br /><br />
正しい HTTP リクエストであれば、OK が返ります。
</p>


<h2>例2) SSH サービスの確認</h2>

<p>
22番ポートに telnet をした場合、次のようなバナーが表示されます。
<br /><br />
SSH-2.0xxxxxxxxxx
<br /><br />
"none" など何か入力したりエンターキーを押すと、次のような応答が返されソケットがクローズされます。
<br /><br />
Protocol mismatch
<br /><br />
そのため、Pandora FMS の TCP モジュールでこの通信を確認します。
この場合、「TCP 送信文字列」に次の設定を行います。
<br /><br />
|none^M
<br /><br />
そして、「TCP 受信文字列」に次の設定を行います。
<br /><br />
SSH-2.0|Protocol mismatch
</p>

<h3>例3) SMTP サービスの確認</h3>

<p>
これは、SMTP 通信の例です。</p>
<pre>
R: 220 mail.supersmtp.com Blah blah blah
S: HELO myhostname.com
R: 250 myhostname.com
S: MAIL FROM: &lt;pepito@myhostname.com&gt;
R: 250 OK
S: RCPT TO: &lt;Jones@supersmtp.com&gt;
R: 250 OK
S: DATA
R: 354 Start mail input; end with &lt;CRLF&gt;.&lt;CRLF&gt;
S: .......your mail here........
S: .
R: 250 OK
S: QUIT
R: 221 mail.supersmtp.com Service closing blah blah blah
</pre>
<p>
<br />
最初の通信ステップをチェックしたい場合、各フィールドへ設定する値は次のようになります。
<br /><br />
<b>「TCP 送信文字列」</b>: HELLO myhostname.com^M|MAIL FROM: &lt;pepito@myhostname.com&gt;^M| RCPT TO: &lt;Jones@supersmtp.com&gt;^M
<br /><br />
<b>「TCP 受信文字列」</b>: 250|250|250
<br /><br />
最初の 3ステップの通信が "OK" であれば、SMTP は問題なしと判断できます。
実際のメールを送信する必要はありません(設定は可能です)。

これは、プレーンテキストで通信するプロトコルの、強力な TCP サービスチェック機能です。
</p>
