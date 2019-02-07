<?php
/**
 * @package Include/help/en
 */
?>
<h1>Export server</h1>

<p><?php echo get_product_name(); ?> Enterprise Version implements, through the export server, a data scaling device that allows you to do a virtually distributed implementation able to monitor an unlimited number of information, as long as you design it properly and break it up into different information profiles.</p>

<ul>
<li type="circle">Name: <?php echo get_product_name(); ?> server name.</li>
<li type="circle">Export server: Combo to choose the server petition of export server that will be used to export the data.</li>
<li type="circle">Prefix: Prefix that is used to add to the agent name that send the data. For example, when the data of an agent named "Farscape" is resent, and its prefix in the export server is "EU01",the resent agent data will be seen in the destination server with the agent name EUO1-Farscape.</li>
<li type="circle">Interval: Define the time interval, and how often (in seconds) you want to send the data that is unresolved.</li>
<li type="circle">Target directory: It will be the target directory (used for SSH or FTP only), where it will leave remotely the data.</li>
<li type="circle">Address: Data server address that is going to receive the data.</li>
<li type="circle">Transfer Mode: files transfer mode. You can choose between: Local, SSH, FTP and Tentacle.</li>
<li type="circle">User: User for FTP.</li>
<li type="circle">Password: FTP user password.</li>
<li type="circle">Port: Port used in the files transfer. For Tentacle it is the 41121 standard port.</li>
<li type="circle">Extra options: Field for additional options such as the ones that Tentacle needs to work with certificates.</li>
</ul>
