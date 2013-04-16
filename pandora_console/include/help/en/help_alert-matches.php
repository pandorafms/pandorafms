<?php
/**
 * @package Include/help/en
 */
?>
<h1>Matches of the alert</h1>

<p>
Defines the number of alerts that must occur before executing the action. It is a fine-tunning parameter.<br><br>

This allows "redefine" a little more the alert behavior, so that if we set a maximum of 5 times the times you can fire a warning, and we just want to send us an email, we will set here 0 and 1 , to say that we only send an email from time 0 to 1 (so, once). <br> <br>

Now we see that we can add more actions to the same alert, defining these fields "Number of alerts match from" alert behavior depending on how often you shoot. <br> <br>

For example, we may want to send an email to XXXXX the first time it happens, and if it continues down the monitor, send an email to ZZZZ. To do this, associate after the alert, the alert table assigned, I can add more actions to an alert defined as changing this parameter.
</p>
