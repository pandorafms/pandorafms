<?php
/*
 * @package Include/help/en
 */
?>

<h1>Server management</h1>

<p>The <?php echo get_product_name(); ?> servers are the elements in charge of performing the existing checks. They verify them and change their status depending on the results. They are also in charge of triggering the alerts established to control the status of data.</p>

<p><?php echo get_product_name(); ?>'s data server can work in high availability and/or load balancing modes. In a very large architecture, various <?php echo get_product_name(); ?> servers can be used at the same time, in order to handle large volumes of functionally or geographically distributed information.</p>

<p><?php echo get_product_name(); ?> servers are always on and permanently verify if any element has any problem. If there is any alert linked to the problem, then it'll run the pre-set action such as sending an SMS, an email, or activating a script execution.</p>

<ul>
<li type="circle">Data Server</li>
<li type="circle">Network Server</li>
<li type="circle">SNMP Server</li>
<li type="circle">WMI Server</li>
<li type="circle">Recognition Server</li>
<li type="circle">Plugins Server</li>
<li type="circle">Prediction Server</li>
<li type="circle">WEB Test Server</li>
<li type="circle">Export Server</li>
<li type="circle">Inventory Server</li>
</ul>

