<?php
/**
 * @package Include/help/en
 */
?>
<h1>Nfdump filter syntax</h1>

The filter syntax is very similar to that of tcpdump. For example:

<ul>
<li>Capture traffic to or from 192.168.0.1:</li>
<pre>
host 192.168.0.1
</pre>

<ul>
<li>Capture traffic to 192.168.0.1:</li>
<pre>
dst host 192.168.0.1
</pre>

<li>Avoid traffic with origin at 192.168.1.240:</li>
<pre>
not src host 192.168.1.240
</pre>

<li>Capture traffic from 192.168.0.0/24:</li>
<pre>
src net 192.168.0.0/24
</pre>

<li>Capture HTTP and HTTPS traffic:</li>
<pre>
(port 80) or (port 443)
</pre>

<li>Capture all traffic except DNS:</li>
<pre>
port not 53
</pre>

<li>Capture SSH traffic to 192.168.0.1:</li>
<pre>
(port 22) and (dst host 192.168.0.1)
</pre>
