<?php
/**
 * @package Include/help/en
 */
?>
<h1>Alert matches</h1>

<p>
This defines the number of alerts that must go off before triggering the set action. This is a fine tuning parameter.<br><br>

This allows "redefining” alert behavior a little more, so that if we’ve set a maximum of 5 times you can fire a warning, and we just want to receive an email notification, then we will add the values 0 and 1, to say that we only receive an email when the alert is fired 0 to 1 times (once). When an alert recovers, all the actions that have been executed up to that point will be executed again.<br> <br>

Now we see that we can add more actions to the same alert, defining with these "Number of alerts match from" fields the alert behavior depending on how often it’s triggered. <br> <br>

For example, we may want the alert action to send an email to XXXXX the first time it happens, and if the monitor remains down, send an email to ZZZZ. In order for this to happen, after linking the alert, on the assigned alert chart, more actions can be added to an already defined alert by changing this parameter.
</p>
