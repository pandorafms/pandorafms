<?php
/**
 * @package Include/help/en
 */
?> 

<h1> Creating an Alert Template</h1>

The first step to create an alert template are Conditions.  
<br><br>
Here are detailed the fields to fill in Conditions:
<br><br>
<ul type=”disc”>
<li>    <b>Name:</b> The name of the template.<br></li><br>
<li>    <b>Description:</b> Describes the template function and is useful to identify the template from others in the alert general view. <br></li><br>
<li>    <b>Priority:</b> Field that gives information about the alert. It is useful to search alerts. You can choose between the following priorities: <br><br></li><br>
* Maintenance<br>
* Informational<br>
* Normal<br>
* Minor<br>
* Warning<br>
* Major<br>
* Critical<br>
* Warning/Critical<br>
* Not normal<br><br>

<li>    <b>Condition Type:</b> Field where the kind of condition that will be applied on the alert is defined.The required combos will be added according to the chosen kind.There are the following fields: <br></li></ul>
<li>    <i>Regular Expression:</i> The regular expression is used. The alert will be fired when the module value perform a fixed condition expresed using a regular expression, this is the condition used to fire on string/text data. The other conditions are for status or numerical data.<br> 
By choosing the regular condition it appears the possibility to select the Trigger box when matches the value. In case of select, the alert will be fired when the value matches, and in case of not selecting it, the alert will be fired when the value does not match. </li><br>
<li>    <i>Max and Min:</i> limit value from which the service is in warning state.<br>
By choosing the regular condition the possibility to select the Trigger box when matches the value will appear.In case of selecting it, the alert will be fired when the value is out of the range selected between the maximum an the minimum.In case of not selecting it, the alert will be launched when the value would be between the range selected betweeb the maximum and the minimum. </li><br>
<li>    <i>Max:</i> A maximum and a minimum value are used. <br></li><br>
<li>    <i>Min:</i> A minimum value is used. The alert will be fired when the module value would be lower than the minimum value selected.  </li><br>
<li>    <i>Equal to:</i> The value Equal to is used. The alert will be fired when the module value would be the same as the selected one. It is used ONLY for numerical values (for example 0 or 0.124). </li><br>
<li>    <i>Not Equal to:</i> Similar to previous but adding a logical NOT. </li><br>
<li>    <i>Warning Status:</i> The module state is used.The alert will be fired when this state would be Warning. </li><br>
<li>    <i>Critical Status:</i> The module state is used.The alert will be fired when this state would be Critical.  </li><br>
<li>    <i>Unknown Status:</i> The alert would fire when the module is in unknown status  </li><br>
<li>    <i>On Change</i> The alert would fire when the module value changes  </li><br>
<li>    <i>Always</i> The alert always fire  </li><br>
<br><br>

Once the fields have been filled, press on the "Next" button and this way you will have access to the following screen.

<?php html_print_image('images/help/alert1.png', false, ['width' => '550px']); ?>
<br><br>
Next we are going to detail the fields to fill in: 
<br><br>

<b>Days of Week</b><br><br>

Days when the alert could be fired.<br><br>

<b>Use special days list</b><br><br>

Enable/disable use of special days (holidays and special working days) list.<br><br>

<b>Time From</b><br><br>

Time from which the action of the alert will be executed.<br>
<br>
<b>Time To</b><br><br>

Time until the action of the alert will be executed.<br><br>

<b>Time Threshold</b><br><br>

Defines the time interval in which it is guaranteed that an alert is not going to be fired more times than the number fixed in Maximum number of alerts. If the defined interval is exceeded, an alert will not recover if it comes to an specific value, except if the alert Recover value would be activated. In this case it is recovered inmediatelly after receiving an specific value,regardless the threshold.<br><br>

<b>Min number of alerts</b><br><br>

Minimum number of times that the data has to be out of range (always counting from the number defined in FlipFlop parameter of the module) to start firing an alert. Default is 0, which means that the alert will be fired when the first value satisfies the condition. It works as a filter, necessary to eliminate false positives.<br><br>

<b>Max number of alerts</b><br><br>

Maximum number of alerts that could be sent consecutively in the same time interval (Time Threshold). <br><br>

<b>Advanced fields management</b><br><br>

Defines the value for the "_ﬁeldX_" variable. Here could be used the list of macros that is described in the fields help.<br><br>

<b>Default Action</b><br><br>

In this combo is defined the action by default that the template is going to have. This is the action that will be automatically created when the template would be assigned to the module. You can put none or one, but you can not put several actions by default.

<?php html_print_image('images/help/alert2.png', false, ['width' => '550px']); ?>

Next are the fields that you should fill in:<br><br>

<b>Alert Recovery</b><br><br>

Combo where you can define if the alert recovery is enabled or not.In case that the alert recovery is enabled, when the module would have again values out of the alert range, the alert that matches with the Field 1 defined in the alert and with the Field 2 and 3 that are defined next, will be executed.<br><br>

<b>Field 2</b><br><br>

Defines the value for the "_ﬁeld2_" variable in the alert recovery.<br><br>

<b>Field 3</b><br><br>

Defines the value for the "_ﬁeld3_" variable in the alert recovery.<br>

Once the fields have been filled in, press on the "Finish" button. 
















