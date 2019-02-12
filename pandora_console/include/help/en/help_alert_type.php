<?php
/**
 * @package Include/help/en
 */
?>
<h1>Alert types</h1>

There are some preset alerts, on which it’s very likely you will have to make adjustments, in case your system does not provide the internal commands needed to execute those alerts. The development team has tested these alerts with Red Hat Enterprise Linux (RHEL), CentOS, Debian and Ubuntu Server.
<ul>
    <li><b>eMail</b>: Sends an e-mail from <?php echo get_product_name(); ?> Server. It uses your local sendmail. If you have installed another kind of local mailer or do not have one, you should install and configure sendmail or any equivalent (and check the syntax) to be able to use this service. <?php echo get_product_name(); ?> relies on system tools to execute almost every alert, it will be necessary to check that those commands work properly on your system.</li>
    <li><b>Internal audit</b>: This is the only "internal" alert, it writes the incident in <?php echo get_product_name(); ?>'s' internal auditing system. This is stored on <?php echo get_product_name(); ?>'s Database and can be reviewed from the web console with the <?php echo get_product_name(); ?> audit viewer.</li>
    <li><b><?php echo get_product_name(); ?> Alertlog</b>: Saves information about alerts inside a text file (.log). Use this type of alert to generate log files using the format you need. To do so, you will need to modify the command so that it will use the format and file you want. Note that <?php echo get_product_name(); ?> does not handle file rotation, and that <?php echo get_product_name(); ?>'s' Server process that executes the alert will need access to the log file in order to write on it.</li>
    <li><b><?php echo get_product_name(); ?> Events</b>: This alert creates an special event on the <?php echo get_product_name(); ?> event manager.</li> 
</ul>
These alerts are predefined and cannot be deleted, however the user can define new ones that can use custom commands, and add them using Alert management.
