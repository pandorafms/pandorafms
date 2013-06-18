<?php
/**
 * @package Include/help/es 
 */
?> 
 <h1>Pandora FMS Alert Configuration Quick Guide </h1>
<br>

<b>Introduction to the Current Alert System </b><br><br>

People usually complains about the complexity of defining alerts in Pandora FMS. Before, until version 2.0, alerts were more simple to configure. For each alert, it was defined the condition and what it did when the action was not done, for each case. It was a more "intuitive" thing ( but it had also fields such as the "threshold" alert that caused lot of headaches to more than one people!). It was very simple, but, was it worth it ? <br><br>

One of our best users ( because he had lots of agents installed and managed very well Pandora FMS too), mention us that for creating an alert in 2000 modules it was very difficult, specially when you have to modify something in all of them. Due to this and other problems, we had to modify the alert system to it would be a modular one and to it could separate the definition of the alert firing condition (Alter template) from the action to execute when it is fired (Alert action) and from the command that is executed in the action (Alert command). The combination of an alert template with a module triggers the alert.<br><br>

This way, if I have 1000 systems with a module called "Host alive" and all of them have associated an alert template called "Host down", then an alert called " Call to the operator" will be executed by default, and if I want to change the minimum number of alerts that should be fired before notifying it to the operator, I will only need to make a change in the definition of the template, not doing it one by one, in the 1000 alerts to modify this condition. <br><br>

Several users only manage a few tens of machines, but there are users with hundreds, even thousands of systems monitored with Pandora FMS, and we have to try making possible that with Pandora FMS it would be possible to manage all kind of environments.<br><br><br><br>
<b>Alert structure </b><br><br>

<?php html_print_image ("images/help/alert01.png", false, array('width' => '550px')); ?>

An alert is compound by:<br><br>

    <i>Commands</i><br>
    <i>Actions</i><br>
    <i>Templates </i> <br><br>

A command defines the operation to perform when the alert is fired. Some examples of command coudl be: write in a log, send an email or SMS, execute a script or a program, etc. <br><br>

An action links a command with a template and allow you to customize the command execution using three generic parameters: Field 1, Field 2 and Field 3. These parameters allow you to customize the command execution because they are passed as input parameters in command execution.<br><br>

In the template you defined the alert generic parameters which are: firing conditions, firing actions and alert recovery. <br><br>

    <i>Firing conditions:</i> the conditions when the alert will be fired, for example: when the data is above a threshold, when the status is critical, etc. <br>
    <i>Firing actions:</i> configuration for the action that will be performed when the alert is fired. <br>
    <i>Alert recovery</i> configuration for actions performed when the system is recovered after the alert was fired.  <br><br>

<b>Alert system information flow </b><br><br>

When you define the actions and the templates you have some generic fields called: Field1, Field2 and Field3. They are the parameters passed as input parameters in command execution. The values of this parameters are propagated from template to action and then to the command. The value propagation from template to action will only be peformed if the field defined in the action hasn't got any value, otherwise the value is used. <br><br>

<?php html_print_image ("images/help/alert02.png", false, array('width' => '550px')); ?>

This is an example of how template values are ovewritten by the action values. <br><br>

<?php html_print_image ("images/help/alert03.png", false, array('width' => '550px')); ?>

For example we can create a template that fires an alert and sends and email with the following fields:<br><br>

    <b>Template:</b><br>
        Field1: <i>myemail@domain.com</i><br>
        Field2: <i>[Alert] The alert was fired</i><br>
        Field3: <i>The alert was fired!!! SOS!!! </i><br><br>

    <b>Action:</b><br>
        Field1: <i>myboss@domain.com</i><br>
        Field2:<br>
        Field3: <br><br>

The value that will be passed to the command are: <br><br>

    <b>Command: </b><br>
        Field1: myboss@domain.com<br>
        Field2: [Alert] The alert was fired<br>
        Field3: The alert was fired!!! SOS!!! <br><br>

<b>Defining one Alert </b><br><br>

Now, supposing we are in the previous case, we have one need: to monitor one module that has numerical values. In our case, it's a module that evaluates the system CPU, in other case, it could be a temperature sensor that puts the value in degrees Celsius. Let's see first that our module receives the data correctly: <br><br>

<?php html_print_image ("images/help/alert04.png", false, array('width' => '550px')); ?>

In this screenshot, we can see that we have a module called sys_cpu with a current value of 7. In our case, we want that it fires an alert when it would be higher than 20. For it, we're going to configure the module to it goes on CRITICAl status when it gets higher than 20. For it, we should do click in the adjustable wrench to configure the monitor performance:<br><br>

<?php html_print_image ("images/help/alert05.png", false, array('width' => '550px')); ?>

For it, we modify the value selected in red in the following screenshot: <br><br>
<br><br>

<?php html_print_image ("images/help/alert06.png", false, array('width' => '550px')); ?>

Agree and record the change. Now, when the value of the CPU module would be 20 or higher, it will change its status to CRITICAL and it will be seen in red color, as we can see here. <br><br>


<?php html_print_image ("images/help/alert07.png", false, array('width' => '550px')); ?>

We have already done that the system knows how to recognize when something is right (OK, green color) and when is wrong (CRITICAL, red color). Now, what we should do is that it send us an email when the module changes to this status. For it, we will use the Pandora FMS alert system.<br><br>

To do this, the first thing we should do is to make sure that there is one command that does what we need (to send an email). This example is easy because it's a predefined command in Pandora FMS to send mails. <br><br>
<b>Configuring the Alert</b><br><br>

Now, we have to create an action called "Send an email to the operator". Let's do it: go to the menu -> Alerts -> Actions and click to create a new action:<br><br>

<?php html_print_image ("images/help/alert08.png", false, array('width' => '550px')); ?>

This action uses the command "Send email" and it's really simple, so I only need to fill in one field (Field 1) and leave the other two empties. This is one of the most confused parts of the Pandora FMS alert system: What are the fields:field1, field2 and field3?.<br><br>

These fields are the ones that are used to "pass" the information of the alert template to the command, and also from it to the command, so both the Template and the Command can give different information to the command. In this case, the command only fix the field 1, and we leave the field2 and the field 3 to the template, as we see next. <br><br>

The field 1 is the one we use to define the operator email, in this case, a false mail to "sancho.lerena@notexist.com". <br><br>

<b>Configuring the Template (Alert template)</b><br><br>

Now, we have to create an alert template, as generic as possible, in order to could use it later. That would be "This is wrong, because I have a module in Critical status" and that by default, send an email to the operator. Let's go to the administration menu-> Alerts-> Templates and click on the button to create a new alert template: <br><br>

<?php html_print_image ("images/help/alert09.png", false, array('width' => '550px')); ?>

The element that defines the condition is the field "Condition". In this case, it is selected to "Critical status" so this template, when it would be associated to a module, will be fired when the associated module would be in critical status. We have configured the "cpu_sys" module before in order it becomes to critical status when it would be 20 or more. <br><br>

The priority defined here as "Critical" is the priority of the alert, that has nothing to do with the "Critical" status of the module. The criticity of alerts is to could visualize them after, in other views, such as the event view, with different criticities.<br><br>

Go to step 2, clicking on the "next" button:<br><br>

<?php html_print_image ("images/help/alert10.png", false, array('width' => '550px')); ?>

The step 2 defines all the "fines" configuration "values" of the alert template in the trigger condition. Some of them, the first ones, are quite simple, and they limit the moment of the action of this alert to some specific days between different hours. <br><br>

The most critical parameters here are these:
<br><br>

    <i>Time threshold:</i> It's one day by default. If one module is always down, during, for example one day, and we have here a value of 5 minutes, then, it means that it would be sending us alerts every 5 minutes. If we adjust it for one day (24 hours), it will only send us the alert once, when it downs. If the module recovers and get down again, it will send us an alert again, but if it continues down from the second down, then it won't send us alerts any more until 24 hours.  <br><br>

    <i>Min. Number of alerts:</i> Minimum number of times that the condition should be ( in this case, that the module would be in CRITICAL status) before Pandora FMS executes the actions associated to the alert template. Is a way to avoid that false positives "overflow" me with alerts, or that an erratic performance (now well, now wrong) does that many alerts would be fired. If we put here 1, it means that until it happens at least once, I won't consider it. If we put 0, the first time the module would be wrong, then it will fired the alert.  <br><br>

    <i>Max. Number of alerts:</i> 1 means that it will execute the action only once. If we have here 10, it will execute the action 10 times. It's a way to limit the number of times an alert could be executed.  <br><br>

Now we have fields "field1, field2 and field3" again. Now we can see that the field1 is blank, that is exactly the one that we've defined when we configured the action. The field2 and the field3 are used in the action of sending an email to define the subject and the message text, whereas the field1 is used to define the receivers (separated by commas). So the template, using some macros, is defining the subject and the message alert as in our case we'll receive a message as the one that follows (supposing that the agent where it's the module is called "Farscape"): <br><br>

<i>To: sancho.lerena@notexist.ocm<br>
Subject: [PANDORA] Farscape cpu_sys is in CRITICAL status with value 20<br>
Texto email:<br><br>

This is an automated alert generated by Pandora FMS<br>
Please contact your Pandora FMS for more information. *DO NOT* reply this email.<br></i><br>

Given that the default action is the one we have defined previously, all the alerts that use this template will use this predefined action by default, unless it would be modified. <br><br>

In case 3, we'll see that it's possible to configure the alert system in order to it notify when the alert has stopped. <br><br>

<?php html_print_image ("images/help/alert11.png", false, array('width' => '550px')); ?>

It's almost the same, but in field1 it's not defined, because it'll be used the same that comes defined in the action that has been executed previously (when firing the alert). In this case it'll send only an email when a subject that says that the condition in the cpu-syst module has been recovered) <br><br>

The alert recovery is optional. It's important to say that if in the alert recovery data are fields (field2 and field3) that are defined, these "ignore and overwrite the action fields, that's to say, that they have preference over them. The only valid field that can't be modified is the field1.<br><br>
<b>Associating the Alert to the Command </b><br><br>

Now, we have all that we need, we only have to associate the alert template to the module. For it, go to the alert tab in the agent where the module is:<br><br>

<?php html_print_image ("images/help/alert12.png", false, array('width' => '550px')); ?>

It's easy. In this screenshot we can see an alert already configured for a module named "Last_Backup_Unixtime" to the same template that we have defined before as "Module critical". Now, in the controls that are below, we are going to create an association between the module "cpu-sys" and the alert template "Module critical". By default it'll show the action that we've defined in this template "Send email to Sancho Lerena". <br><br>
<b>Scaling Alerts</b><br><br>

The values that are in the "Number of alerts match from" are to define the alert scaling. This allows to "redefine" a little more the alert performance, so if we have defined a maximum of 5 times the times that an alert could be fired, and we only want that it send us an email, then we should put here one 0 and one 1, to order it that only send us an email from time 0 to 1 (that is, once). <br><br>

Now we see that we can add more actions to the same alert, defining with this fields "Number of alerts match from" the alert performance depending on how many times it would be fired. <br><br>

For example: we want that it sends an email to XXXXX the first time it happens, and if the monitor continues being down, it sends an email to ZZZZ. For it, after associating the alert, in the assigned alerts table, I can add more actions to a previously defined alert, as we can see in the following screenshot: <br><br>

<?php html_print_image ("images/help/alert13.png", false, array('width' => '550px')); ?>
<?php html_print_image ("images/help/alert14.png", false, array('width' => '550px')); ?>



<b>Standby alerts</b><br><br>

Alerts can be enable, disable or in standby mode. The difference between the disabled and standby alerts is that the disable alerts just do not work and therefore will not showed in the alerts view. Standby alerts will be showed in the alerts view and work, but only at display level. It will show if are fired or not but will do not engage in configured actions and will do not generate events. <br><br>

Stanby alerts are useful for viewing them without bothering other aspects <br><br>
<b>Using Alert Commands different from the email</b><br><br>

The email, as a command is internal to Pandora FMS and can't be configured, that is, field1, field2 and field3 are fields that are defined that are used as receiver, subject and text of the message. But, what happens if I want a different action that is defined by me? <br><br>

We're going to define a new command, something completely defined by us. Imagine that we want to create a lof file with each alert that we find. The format of this log file should be something like: <br><br>

<i>DATE_ HOUR - NAME_AGENT - NAME_MODULE - VALUE - PROBLEM DESCRIPTION
</i><br><br>

Where VALUE is the value of the module at this moment. It'll be several log files, depending on the action that calls to the command. The action will define the description and the file to which the events go to. <br><br>

For it, first we are going to create a command as follows: <br><br>

<?php html_print_image ("images/help/alert15.png", false, array('width' => '550px')); ?>

And we're going to define an action: <br><br>

<?php html_print_image ("images/help/alert16.png", false, array('width' => '550px')); ?>

If we take a look at the log that we've created: <br><br>

<i>2010-05-25 18:17:10 - farscape - cpu_sys - 23.00 - Custom alert for LOG#1</i><br><br>

We can see that the alert was fired at 18:17:10 in the " farscape" agent, in the "cpu_sys" module, with a data of "23.00" and with the description that we chose when we defined the action. <br><br>

As the command execution, the field order and other things could do that we don't understand well how the command is finally executed, the easiest thing is to activate the debug traces of the pandora server (verbose 10) in the pandora server configuration file /etc/pandora/pandora_server.conf, and restart the server (/etc/init.d/pandora_server restart) and we take a look to the file /var/log/pandora/pandora_server.log looking for the exact line with the alert command execution that we've defined, to see how the Pandora FMS server is firing the command.  <br><br>


