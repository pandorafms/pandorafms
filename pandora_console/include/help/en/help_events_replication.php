<?php
/*
    Include package help/en
*/
?>

<p> If the event replication is activated, all the events will be copied to the remote metaconsole's database.
<br><br>
It is necessary to configure the credentials of the metaconsole's database, the replication mode (all events or only validated events) and the replication interval, specified in seconds.
<br><br>
<b>NOTES:</b>
<br><br>
The event viewer will be disabled if this option is enabled.
<br><br>
To apply the changes made on the event replication setup, it is necessary to restart the server.
<br><br>
The token "event_replication" must be set to 1 in the server's configuration file.
</p>
