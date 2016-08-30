<?php
/**
 * @package Include/help/en
 */
?>
<h1>Alert types</h1>

There are some preset alerts, on which it’s very likely you will have to make adjustments, in case your system does not provide the internal commands needed to execute those alerts. The development team has tested these alerts with Red Hat Enterprise Linux (RHEL), CentOS, Debian and Ubuntu Server.
<ul>
	<li><b>eMail</b>: Sends an e-mail from Pandora FMS’ Server. It uses your local sendmail. If you have installed another kind of local mailer or do not have one, you should install and configure sendmail or any equivalent (and check the syntax) to be able to use this service. Pandora FMS relies on system tools to execute almost every alert, it will be necessary to check that those commands work properly on your system.</li>
	<li><b>Internal audit</b>: This is the only "internal" alert, it writes the incident in Pandora FMS’ internal auditing system. This is stored on Pandora FMS’ Database and can be reviewed from the web console with the Pandora FMS audit viewer.</li>
	<li><b>Pandora FMS’ Alertlog</b>: Saves information about alerts inside a text file (.log). Use this type of alert to generate log files using the format you need. To do so, you will need to modify the command so that it will use the format and file you want. Note that Pandora FMS does not handle file rotation, and that Pandora FMS’ Server process that executes the alert will need access to the log file in order to write on it.</li>
	<li><b>Pandora FMS Events</b>: This alert creates an special event on the Pandora FMS event manager.</li> 
</ul>
These alerts are predefined and cannot be deleted, however the user can define new ones that can use custom commands, and add them using Alert management.
