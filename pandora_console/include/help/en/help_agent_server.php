<?php
/**
 * @package Include/help/en
 */
?>

<h1>Server field</h1>

On the “server” field there is a combination where the server for check ups is chosen.
Setup is found on the servers.
<br><br>
For servers there are two work methods:
<br><br>
<ul>
<blockquote>
<li>Master Mode.
<li>Non-Master Mode. 
</ul>

<br>
The difference between them, and the importance that they carry when working in HA mode, is based on the fact that there are several servers of the same kind( e.g: Network Servers).When a server is down, the first master server available will be in charge of the network modules that are on the downed server and that are waiting to be run. Non-master servers cannot do this.
<br><br>
This option can be set in the file /etc/pandora/pandora_server.conf using the master 1 token.
<br><br><i>
master 1
<br><br></i>
Set the value to 1 to active it and 0 to deactivate it.
