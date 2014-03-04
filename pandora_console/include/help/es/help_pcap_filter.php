<?php
/**
 * @package Include/help/en
 */
?>
<h1>Sintaxis de filtros de Nfdump</h1>

La sintaxis de filtros es muy parecida a la de tcpdump. Por ejemplo:

<ul>
<li>Capturar tráfico hacia o desde 192.168.0.1:</li>
<pre>
host 192.168.0.1
</pre>

<ul>
<li>Capturar tráfico hacia 192.168.0.1:</li>
<pre>
dst host 192.168.0.1
</pre>

<li>Evitar tráfico con origen en 192.168.1.240:</li>
<pre>
not src host 192.168.1.240
</pre>

<li>Capturar tráfico desde 192.168.0.0/24:</li>
<pre>
src net 192.168.0.0/24
</pre>

<li>Capturar tráfico HTTP y HTTPS:</li>
<pre>
(port 80) or (port 443)
</pre>

<li>Capturar todo el tráfico excepto DNS:</li>
<pre>
port not 53
</pre>

<li>Capturar el tráfico SSH hacia 192.168.0.1:</li>
<pre>
(port 22) and (dst host 192.168.0.1)
</pre>
