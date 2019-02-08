<?php
/**
 * @package Include/help/es
 */
?> 
<h1><?php echo get_product_name(); ?>'s Quick Guide to Alert Configuration </h1>
<br>
<b>Introduction to the Current Alert System</b><br>
<br>
People usually complain about the complexity of defining alerts on
<?php echo get_product_name(); ?>. Before, until version 2.0, alerts were easier to
configure. For each alert the condition and what it did when the action was not completed was defined for each case. It was a more "intuitive"
thing (but it also had fields such as the "threshold" alert that caused
many headaches to more than one person!). It was very simple, but, was
it worth it ?<br>
<br>
One of our “star” users (we mention this because he had A LOT of agents installed, and also knew the inner workings of <?php echo get_product_name(); ?> quite well) mentioned that creating an alert for 2000 modules was tremendously complicated, especially when it was necessary to modify something for all of them. Due to this, and other issues, we decided to modify the alert system so that it could be modular, so that the alert’s definition and its triggering condition (template) could be separated from the action that has to be executed when the alert is triggered (alert action) and from the command that is run within the action (Alert command). The combination of an alert template with a module triggers the alert itself.<br><br>

This way, if I have 1000 devices with a module named “Host Alive” and all of them have a related alert template named “Host Down” that when triggered executes an action named “Warn the Operator”, and I wish to change the minimum number of alerts that must be fired before the Operator is warned, I only have to change the definition on the template instead of going one by one over the 1000 alerts to modify that specific condition.<br><br>

Many users only manage a few dozen devices, but there are many users with hundreds—even thousands—of systems monitored with <?php echo get_product_name(); ?>, and we have to try and make it so that with <?php echo get_product_name(); ?> all types of environments can be managed.<br>
<br>
<br>
<br>
<b>Alert structure </b><br>
<br>
<?php
html_print_image(
    'images/help/alert01.png',
    false,
    ['width' => '550px']
);
?>
<br>
An alert is composed of:<br>
<br>
<i>Commands</i><br>
<i>Actions</i><br>
<i>Templates </i><br>
<br>
A command defines the operation that will take place when an alert is fired. Examples of commands can be: creating a registry on a log, sending an email, running a script or program, etc.<br><br>

An action links a command to a template and allows the
command execution to be customised using three generic parameters: Field 1, Field 2 and
Field 3. These parameters allow you to tweak the command’s execution
because they are passed as input parameters at the time of said execution.<br><br>
On the template generic alert parameters are defined. These are: Triggering conditions, firing actions, and alert recovery conditions.<br><br>
<i>Triggering conditions:</i> the conditions under which the alert will be triggered,
for example: when the amount of data surpasses a set threshold, when a status is
critical, etc.<br>
<i>Firing actions:</i> configuration for the action that will be
performed when the alert is triggered.<br>
<i>Alert recovery</i> settings for actions that will be performed when the system recovers from the alert.<br>
<br>
<b>Information flow on the alert system</b><br>
<br>
When the actions and templates are defined we have some generic fields available named Field1, Field2 and Field3 that are the ones that will be passed on as input parameters for the command’s execution. The values for these parameters are propagated from the template onto the action, and lastly to the command. The template to action transition only takes place if the field corresponding to the action doesn’t have an assigned value, if the action has an assigned value, it’s kept.<br><br>
<?php
html_print_image(
    'images/help/alert02.png',
    false,
    ['width' => '550px']
);
?>
<br>
This is an example of how template values are overwritten by action
values.<br>
<br>
<?php
html_print_image(
    'images/help/alert03.png',
    false,
    ['width' => '550px']
);
?>
<br>
For example we can create a template that fires an alert and sends and
email that includes the following fields:<br>
<br>
<b>Template:</b><br>
Field1: <i>myemail@domain.com</i><br>
Field2: <i>[Alert] The alert has been triggered</i><br>
Field3: <i>The alert has been triggered!!! SOS!!!</i><br>
<br>
<b>Action:</b><br>
Field1: <i>myboss@domain.com</i><br>
Field2:<br>
Field3:<br>
<br>
The values passed on to the command would be:<br>
<br>
<b>Command:</b><br>
Field1: myboss@domain.com<br>
Field2: [Alert] The alert has been triggered<br>
Field3: The alert has been triggered!!! SOS!!!<br><br>

For fields “Field2” and “Field3” the values set in the template are kept, but for “Field1” the value defined in the action is used.<br><br>

<b>Defining an Alert</b><br><br>

Now we’re going to play ourselves in the prior situation. We have one necessity: to monitor a module that contains numeric values. In our case, it’s a module that measures the system’s CPU, in another case it can be a temperature sensor that retrieves value in degrees celsius. First, let’s make sure our module receives data correctly:<br><br>
<?php
html_print_image(
    'images/help/alert04.png',
    false,
    ['width' => '550px']
);
?>
<br>
So, on this screenshot we can see that we have a module named sys_cpu with a current value of 7. In our case we want an alert to go off when it goes over 20. For this we’re going to set the module so that it enters CRITICAL status when it reaches that 20 mark. For this to happen, we click on the wrench icon in order to access the monitor behaviour settings, and modify it from there:<br>
<br>
<?php
html_print_image(
    'images/help/alert05.png',
    false,
    ['width' => '550px']
);
?>
<br>
In this case we modify the value marked in red, shown on the following screenshot:<br>
<br>
<br>
<br>
<?php
html_print_image(
    'images/help/alert06.png',
    false,
    ['width' => '550px']
);
?>
<br>
We agree and save the change. Now when the CPU module’s value is 20 or more, it’ll change its status to CRITICAL and it will be marked in red, like what is shown on the screenshot below.<br>
<br>
<?php
html_print_image(
    'images/help/alert07.png',
    false,
    ['width' => '550px']
);
?>
<br>
We’ve now made it so that the system can discriminate when something is right (OK status, marked in GREEN) and when something is wrong (CRITICAL status, marked in RED). Now what we have to do is make the system send us an email when the module reaches that status. For this we’ll use <?php echo get_product_name(); ?>'s alert system.<br>
<br>
For this we need to make sure there is a command available that can do what we need it to (in this case, send an email). This example is easy because there is a predefined command on <?php echo get_product_name(); ?> that is meant to automate email sending, meaning this is already done.<br>
<br>
<b>Configuring the Alert</b><br>
<br>
Now, we have to create an action called "Send an email to the operator".
To do this, navigate to: Menu -> Alerts -> Actions, and click on the button in order to create a
new action:<br>
<br>
<?php
html_print_image(
    'images/help/alert08.png',
    false,
    ['width' => '550px']
);
?>
<br>
This action uses the “send email” command, and is really simple, since only one field from the form needs to be filled out (Field1) leaving the other two empty. This is one of the most confusing parts of the alert system on <?php echo get_product_name(); ?>: what are field1, field2 and field3?<br>
<br>
These fields are the ones used to “pass” the information on from the alert template to the command, and at the same time from that command to the next. This way both the template and the command can provide different information to the command. In this case, the command only establishes field1 and leaves field2 and field3 to be filled by the template, like what is shown next.<br>
<br>
Field1 is the one used to define te operator’s email address. In this case, a supposed email to “sancho.lerena@notexist.com”<br><br>

<b>Configuring the Template (Alert template)</b><br>
<br>
Now we hace to create the most generic alert template possible (so it can be reused in the future) that is “This is wrong, because there is a module in critical status” and that sends an email to the operator as a default action. To do this we head over to the management menu and navigate to: Alerts -> Templates, and from there we click the button that creates a new alert template:<br><br>
<?php
html_print_image(
    'images/help/alert09.png',
    false,
    ['width' => '550px']
);
?>
<br>
What defines the condition is the “Condition” field, which in this case is marked to “Critical Status”. This way,the template, once linked to a module, will be triggered when the related module is in critical status. Before this we have already configured the “cpu_sys” module so that it enters critical status when the value is 20 or more.<br>
<br>
The “Critical” priority defined here is the alert’s priority, which has nothing to do with the module’s “Critical” status. The criticality of alerts are meant to be viewed later, in other displays, like the event view, with different levels of criticality.<br>
<br>
We can proceed to step 2 by clicking on the "next" button:<br>
<br>
<?php
html_print_image(
    'images/help/alert10.png',
    false,
    ['width' => '550px']
);
?>
<br>
Step 2 defines all the “fine tuning” configuration “values” for the alert template’s triggering condition. Some of them, the first, are very simple: they restrict the acting moment for this alert to certain days in a certain range of hours.<br><br>
The most critical parameters here are the following:<br>
<br>
<i>Time threshold:</i> Set to one day by default. If a module is constantly down during, for example, one day and and we have set a value of 5 minutes here, it means that alerts would be sent every 5 minutes.  If we leave it at one day (24hrs.), it’ll only send the alert once, when it goes down. If the module recovers, and drops again, it’ll send another alert, but if it remains down from the second drop, it won’t send more alerts until 24hrs have gone by.<br>
<br>
<i>Min. Number of alerts:</i> The minimum number of times that a condition will have to be met (in this case, that the module is in CRITICAL status) before <?php echo get_product_name(); ?> runs the actions linked to the alert template. It’s a way to avoid false positives “flooding” you with alerts, or so that an erratic behaviour doesn’t lead to multiply alerts going off. If we place a ‘1’ here it means that until this doesn’t happen at least once, it won’t be taken into account. If i set a value of ‘0’ the first time the module returns an error, the alert will go off.<br>
<br>
<i>Max. Number of alerts:</i> A value of 1 means that it’ll only execute the action once. If we have ’10’ set here, it’ll run the action 10 times. This is a way to limit the number of times an alert can go off.<br>
<br>
Again, we can see the fields “field1, field2, field3”. Now we can see that field1 is blank, which is precisely the one we’ve defined when configuring the action. Field2 and Field3 are used for the “send mail” action to define the subject and the message’s body, whilst Field1 is used to define the recipient(s) of said message (addresses must be separated by commas). Therefore the template, combined with the use of some macros, is defining the subject and alert message in a way that, in our case, we would receive a message like the following (supposing the agent where the module is placed is named “Farscape”):<br>
<br>
<i>To: sancho.lerena@notexist.ocm<br>
Subject: [MONITORING] Farscape cpu_sys is in CRITICAL status with a value
of 20<br>
Message body:<br>
<br>
This is an automated alert generated by <?php echo get_product_name(); ?><br>
Please contact your <?php echo get_product_name(); ?> operator for more information. *DO NOT* reply to
this email.<br>
</i><br>
Given that the default action is the one we have defined previously, all
the alerts that use this template will use this predefined action by
default, unless it were to be modified.<br>
<br>
In the third situation, we’ll see that this alert system can be set to notify when the alert has stopped.<br>
<br>
<?php
html_print_image(
    'images/help/alert11.png',
    false,
    ['width' => '550px']
);
?>
<br>
It’s nearly the same, but Field1 isn’t defined, because the same one that was preset on the previously executed action will be used (when the alert was triggered). In this case it’ll send an email with the subject informing that the condition for the cpu_sys module has recovered itself.<br>
<br>
Alert recovery is optional. It’s important to note that if there are fields (Field2 and Field3) defined, these will ignore and overwrite the action’s fields. This means that they have priority over them. The only field that can’t be modified is Field1.<br>
<br>
<b>Associating the Alert to the Module</b><br>
<br>
Now that we have all we needed, we only need to link the alert template to the module. For this we need to navigate to the “Alerts” tab on the agent where the module is:<br>
<br>
<?php
html_print_image(
    'images/help/alert12.png',
    false,
    ['width' => '550px']
);
?>
<br>
It’s simple, in this screenshot we can see an alert that is already configured for a module named “Last_Backup_Unixtime” linked to the same template named “Module critical” that we previously defined. Now, in the underlying controls, we’ll create a link between the “cpu_sys” module and the alert template “Module critical”. By default the action defined on that template (“send email to Sancho Lerena”) will be shown.<br>
<br>
<b>Alert scaling</b><br>
<br>
The values found in the “Number of alerts match from” option are meant to define the alert scaling. This allows “redefining” the alert’s behaviour a bit more, this way, if we’ve defined a maximum of 5 times for an alert to go off, and we only want it to send an email, we’ll set a ‘0’ and a ‘1’ here, to tell it to only send an email when the alert goes off one time (so the message is sent only once).<br>
<br>
Now we see that we can add more actions to a single alert, defining with these “number of alerts match from” fields the alert’s behaviour based on how many times it’s fired.<br>
<br>
For example, we may want the action to send an email to XXXXX the first time that it happens, and if the monitor is still down, we may want it to send a second email to ZZZZZ. For this, after liking the alert, in the assigned alerts chart, we can add more actions to an alert that’s already been defined, like what can be see in the following screenshot:<br>
<br>
<?php
html_print_image(
    'images/help/alert13.png',
    false,
    ['width' => '550px']
);
?>
<?php
html_print_image(
    'images/help/alert14.png',
    false,
    ['width' => '550px']
);
?>
<br>
<b>Alerts on standby</b><br>
<br>
Alerts can be enabled, disabled, or on standby. The difference between enabled, disabled and standby, is that disabled alerts simply won’t work and therefore will not be shown in the alert view. On the other hand, alerts on standby will always appear on the alert view and will work, but only on a visualisation level. This means that it can be seen whether they’re triggered or not, but they won’t perform their set actions nor will they generate events.<br>
<br>
Alerts in standby are useful because they can be viewed without interfering with other aspects.<br>
<br>
<b>Using Alert Commands other than the “send email” command</b><br>
<br>
The email, as a command is internal to <?php echo get_product_name(); ?> and can’t be configured, this means Field1, Field2 and Field3 are fields that are preset to be used as the recipient, subject and body for the email alert. But, what happens when we want to execute a different, more customised alert?<br>
<br>
We’ll define a new, totally customised command. Imagine that we want to generate a log file with each alert we find. The format for that log file has to be something like:<br>
<br>
<i>DATE_ HOUR - NAME_AGENT - NAME_MODULE - VALUE - PROBLEM
DESCRIPTION</i><br>
<br>
Where VALUE is the module’s value at that time. There will be multiple log files, depending on the action that calls on the command. The action will define the description and the file the events will be stored in.<br>
<br>
For this, first we’ll create a command like the one shown below:<br>
<br>
<?php
html_print_image(
    'images/help/alert15.png',
    false,
    ['width' => '550px']
);
?>
<br>
And we're going to define an action:<br>
<br>
<?php
html_print_image(
    'images/help/alert16.png',
    false,
    ['width' => '550px']
);
?>
<br>
If we take a look at the log that we've created:<br>
<br>
<i>2010-05-25 18:17:10 - farscape - cpu_sys - 23.00 - Custom alert for
LOG#1</i><br>
<br>
<br>
We can see that the alert was fired at 18:17:10 because of the " farscape"
agent, in the "cpu_sys" module, with a data value of "23.00" and with the
description that we chose when we defined the action.<br>
<br>
Since the command’s execution, the field order and other affairs can make it so we don’t really understand how it’s executed at the end of the command, the easiest thing to do is to activate the <?php echo get_product_name(); ?> server debug traces (verbose 10) in the configuration file for the <?php echo get_product_name(); ?> server ‘/etc/pandora/pandora_server.conf’, and then reset the server
(/etc/init.d/pandora_server restart). After, we take a look at the file
/var/log/pandora/pandora_server.log and look for the exact line with the
alert command execution that we've defined, to see how the <?php echo get_product_name(); ?>
server is launching and executing the command.<br>
<br>
