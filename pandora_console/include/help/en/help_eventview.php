<?php
/**
 * @package Include/help/en
 */
?>
<h1>Event viewer</h1>

<br>
<br>

<div style="padding-left: 30px; width: 150px; float: left; line-height: 17px;">
	<h3>Validate</h3>
	<?php html_print_image("images/tick.png", false, array("title" => "Validated event", "alt" => "Validated event", "width" => '10', "height" => '10')); ?> - Validated event<br>
	<?php //html_print_image("images/cross.png", false, array("title" => "Event not validated", "alt" => "Event not validated", "width" => '10', "height" => '10')); ?><div style="width: 10px;height: 10px; display: inline-block;"></div> - Event not validated
</div>

<div style="padding-left: 30px; width: 150px; float: left; line-height: 17px;">
	<h3>Severity</h3>
	<?php html_print_image("images/status_sets/default/severity_maintenance.png", false, array("title" => "Maintenance event", "alt" => "Maintenance event")); ?> - Maintenance event<br>
	<?php html_print_image("images/status_sets/default/severity_informational.png", false, array("title" => "Informational event", "alt" => "Informational event")); ?> - Informational event<br>
	<?php html_print_image("images/status_sets/default/severity_normal.png", false, array("title" => "Normal event", "alt" => "Normal event")); ?> - Normal event<br>
	<?php html_print_image("images/status_sets/default/severity_warning.png", false, array("title" => "Warning event", "alt" => "Warning event")); ?> - Warning event<br>
	<?php html_print_image("images/status_sets/default/severity_critical.png", false, array("title" => "Critical event", "alt" => "Critical event")); ?> - Critical event<br>
</div>

<div style="padding-left: 30px; width: 150px; float: left; line-height: 17px;">
	<h3>Actions</h3>
	<?php html_print_image("images/ok.png", false, array("title" => "Validate event", "alt" => "Validate event")); ?> - Validate event<br>
	<?php html_print_image("images/cross.png", false, array("title" => "Delete event", "alt" => "Delete event")); ?> - Delete event<br>
	<?php html_print_image("images/eye.png", false, array("title" => "Show more", "alt" => "Show more")); ?> - Show more<br>
	<?php html_print_image("images/hourglass.png", false, array("title" => "In progress", "alt" => "In progress")); ?> - In progress	
</div>

<div style="clear: both;">&nbsp;</div>

