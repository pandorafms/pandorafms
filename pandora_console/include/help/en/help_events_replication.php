<?php
/* Include package help/en
*/
?>

<p> When the events replication is activated, the received events will be copied to the remote database of a metaconsole.
<br><br>
Is necessary configurate the credentials of the metaconsole database, the replication mode (all events or only validated ones) and the replication interval in seconds.
<br><br>
<b>NOTES:</b>
<br><br>
The event viewer will be disabled when this option is activated.
<br><br>
To be effective the changes on the events replication configuration will be necessary restart the server.
<br><br>
The server configuration file must has token:

<i>event_replication 1</i>

</p>

