<?php
/**
 * @package Include/help/en
 */
?>
<h1>Unknown modules in <?php echo get_product_name(); ?></h1>
<p>
You may have unknown modules for many reasons. Unknown module is a special status for a module/monitor which means “I dont have recent data for this monitor and I should have data”. A monitor goes to unknown status when doesnt receive nothing in at least its interval (for example, 300 seconds) multiplied by two, in this case, if you doesn't receive nothing in ten minutes, monitor goes to unknown.
</p>
<p>
These are a few cases where you can get unknown modules:
</p>
<ul class="list-type-disc mrgn_lft_30px">
    <li>Your <?php echo get_product_name(); ?> server is down. Restart it, dont forget to check /var/log/pandora/pandora_server.log to see why was down.</li>
    <li>Your tentacle server is down, and cannot get data from your <?php echo get_product_name(); ?> agents installed in your remote servers.</li>
    <li>You have a network problem between your agents and your server.</li>
    <li>Your <?php echo get_product_name(); ?> agent is stopped and is not sending information to your server.</li>
    <li>Your network is down, or the remote device you are trying to ask is down or changed it's IP address (for example for numerical SNMP remote queries).</li>
    <li>Your agent is reporting a badly synchronized date. Means reports a timedate in the past and that messup everything.</li>
    <li>The script / module before works now doesn't, that can be because something is happing in the agent, check it out.</li>
</ul>
<p>
Sometimes UNKNOWN status can be useful to monitor, so you can setup alerts on UNKNOWN status to warn you about that.
</p>