<?php
/**
 * @package Include/help/en
 */
?>
<h1>Action Threshold</h1>

<p>
An alert action will not be executed more than once every
‘action_threshold’ time value, regardless of the number of times the alert is triggered.
</p>
<p>
For example, if you have configured an action that sends you an email
when the alert is activated and you don't want to receive more than one email per hour, you can set the ‘action_threshold’ value to 3600.
</p>
<p>
Bear in mind that the individual ‘action_threshold’ value of an action overrides the global ‘action_threshold’ value of the alert.  
</p>
