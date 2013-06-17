<?php
/**
 * @package Include/help/en
 */
?>

<h1>Server field</h1>

In the field "server" there is a combo where you can choose the server that will do the checking.
Configuration at Servers
<br><br>
In Servers there are two modes of work:
<br><br>
<ul>
<blockquote>
<li>Master Mode.
<li>Non-Master Mode. 
</ul>

<br>
The differences between them, and the importance that they have to work in HA mode, consist on that when there are several servers from the same kind( e.g: Network Servers).When a server falls, the first master server that could, will be in charge of the network modules of the down server that are waiting to be executed. The non-master servers does not do this.
<br><br>
This option is configured in the file /etc/pandora/pandora_server.conf through the master 1 token.
<br><br><i>
master 1
<br><br></i>
Being the value 1 to active it and 0 to deactivate it.
