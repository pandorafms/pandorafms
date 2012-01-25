<?php
/**
 * @package Include/help/en
 */
?>
<h1>Action Threshold</h1>

<p>
An alert action will not be executed more than once every action_threshold seconds, regardless of the number of times the alert is fired.

For example, if you have configured an action that sends you an email when the alert fires and you don't want to receive more than one email per hour, you can set the action_threshold to 3600.

Bear in mind that the individual action_threshold of an action overrides the global action_threshold of the alert.  

</p>
