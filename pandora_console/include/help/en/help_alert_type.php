<?php
/**
 * @package Include/help/en
 */
?>
<h1>Alert type</h1>

There are some predefined alerts, in which is very likely you will have to adjust, in case your system does not provide the internal commands needed to execute those alerts. The development team has tested these alerts with Red Hat Enterprise Linux (RHEL), CentOS, Debian and Ubuntu Server.
<ul>
	<li><b>Compound only</b>: This alert will not be executed individually. This will just be part of a combined alert, and is needed to trigger the combined alert depending on its status and other compound alerts, if exist.</li>
	<li><b>eMail</b>: Sends an e-mail from Pandora FMS Server. It uses your local sendmail. If you installed other kind of local mailer or do not have one, you should install and configure sendmail or any equivalent (and check the syntax) to be able to use this service. Pandora FMS rely on system tools to execute almost every alert, it will be necessary to check that those commands work properly on your system.</li>
	<li><b>Internal audit</b>: This is the only "internal" alert, it writes the incident in Pandora FMS internal audit system. This is stored in Pandora FMS Database and can be reviewed with Pandora FMS audit viewer from the Web console.</li>
	<li><b>Pandora FMS Alertlog</b>: Saves information about the alert inside a text file (.log). Use this type of alert to generate log files using the format you need. To do so, you will need to modify the command so that it will use the format and file you want. Note that Pandora FMS does not handle file rotation, and that Pandora FMS Server process that executes the alert will need acess to the log file to write on it.</li>
	<li><b>Pandora FMS Event</b>: This alert create an special event into Pandora FMS event manager.</li> 
</ul>
This alerts are predefined and cannot be deleted, however the user can define new ones that use custom commands and add with the Alert management.
