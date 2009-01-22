<h1>TCP Check</h1>

<p>
This module just send character strings to destination IP / port, wait for response and optionally match it's response with a predefined response. If TCP SEND / TCP RCV. fields are emply just check for an open port in destination.
</p>
<p>
You can use ^M string to send a carriage return, and also can use a multi request / multi response conversation. Several request and responses are separated with | character.
</p>


<h2>Example #1. Checking WEB service</h2>

<p>
Just imagine that you want to check that www.yahoo.com reply correctly to a HTTP request. Put this in TCP Send:<br><BR>
GET / HTTP/1.0^M^M
<br /><br />
And this on TCP receive:
<br /><br />
200 OK
<br /><br />
This should give OK if a correct HTTP request is possible. 
</p>


<h2>Example #2. Checking SSH service</h2>

<p>
If you make a telnet to port 22 of a standard service you will see that after connecting they present you a banner like
<br /><br />
SSH-2.0xxxxxxxxxx
<br /><br />
If you type something, like "none" and press enter, they reply you the following string and close socket:
<br /><br />
Protocol mismatch
<br /><br />
So to "code" this conversation in a Pandora FMS TCP module, you need to put in TCP Send:
<br /><br />
|none^M
<br /><br />
And put in TCP Receive:
<br /><br />
SSH-2.0|Protocol mismatch
</p>

<h3>Example #3. Checking a SMTP service</h3>

<p>
This is sample SMTP conversation:
<pre>
R: 220 mail.supersmtp.com Blah blah blah
S: HELO myhostname.com
R: 250 myhostname.com
S: MAIL FROM: <pepito@myhostname.com>
R: 250 OK
S: RCPT TO: <Jones@supersmtp.com>
R: 250 OK
S: DATA
R: 354 Start mail input; end with <CRLF>.<CRLF>
S: .......your mail here........
S: .
R: 250 OK
S: QUIT
R: 221 mail.supersmtp.com Service closing blah blah blah
</pre>
<br />
So if you want to check the first steps of conversation, the fields will be:
<br /><br />
<b>TCP SEND </b>: HELLO myhostname.com^M|MAIL FROM: <pepito@myhostname.com>^M| RCPT TO: <Jones@supersmtp.com>^M
<br /><br />
<b>TCP SEND </b>: 250|250|250
<br /><br />
If the first three steps of connections are "OK" then the SMTP seems to be ok, don't need to send a real mail (however, it could be done!). This is a powerful TCP service checker that can be used to verify any TCP plain text service.
</p>
