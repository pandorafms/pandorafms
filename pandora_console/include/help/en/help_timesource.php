<h1>Origen de tiempo</h1>

<p>
What source to use for the time. This can be (for now) either the local system (System) or database (Database).<br /><br />
</p>
<p>
This is useful when your database is not on the same system as your webserver or your Pandora FMS servers. 
In that case any time difference will miscalculate the time differences and timestamps.
You should use NTP to sync all your pandora servers and your MySQL server. 
By using these preferences you don't have to sync your webserver but it's still recommended.
</p>
<p>
Feel free to implement more sources (eg: ntp, ldap, $_SERVER...).
</p>
<p>
Note: The database query will be cached the first time it's called so the time will always be the same on a page load throughout, while System time is returned whenever the function is called, which might differ slightly (especially near the ending of a second).
</p>
<p>
These examples are all returning Unixtime
<script type="text/javascript">
var date = new Date; // Generic JS date object
var unixtime_ms = date.getTime(); // Returns milliseconds since the epoch
var unixtime = parseInt(unixtime_ms / 1000);
</script>
</p>
<p>
<?php
$option = array ("prominent" => "timestamp");
?>
<b>Current System time:</b> <?php print_timestamp (time (), false, $option); ?>
<br />
<b>Current Database time:</b> <?php print_timestamp (get_db_sql ("SELECT UNIX_TIMESTAMP()"), false, $option); ?>
<br />
<b>Your browser time:</b> <script type="text/javascript">document.write (date);</script>
</p>