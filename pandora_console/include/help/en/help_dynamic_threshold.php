<?php
/**
 * @package Include/help/en
 */
?>
<h1>Dynamic threshold</h1>

<h2>Dynamic threshold Interval</h2>

<p>
	Introduce a time period in the Dynamic Threshold Interval field and the module will return the data obtained during the interval. This allows <?php echo get_product_name();?> to establish minimum thresholds of critical and warning status according to the server configuration.
	<br><br>
	The default setting will <b>only</b> give minimums, so if maximum = 0 it will read from the configured minimum to infinite.
	<br><br>
	<b>Example:</b><br>
	warning status min = 5 and max = 0<br>
	critical status min = 10 and max = 0<br>
	With these parameters the module will record the following states:<br> 
	  - Status normal from -infinite to 4.<br>
	  - Status warning from 5 to 9.<br>
	  - Status critical from 10 to infinite.
	<br><br>
	<b>Example 2:</b><br>
	warning status min = 5 and max = 0 with inverse interval checked<br>
	critical status min = 10 and max = 0 with inverse interval checked<br>
	With these parameters the module will record the following states:<br> 
	  - Status normal from 10 to infinite.<br>
	  - Status warning disabled.<br>
	  - Status critical from 10 to -infinite.
	<br><br>
		In these examples if the critical threshold coincides with the <b>warning threshold then status will always read as critical.</b>
</p>

<h2>Advanced options dynamic threshold</h2>
<b>Dynamic threshold Min. / Max.</b>
<p>
	These fields permit percentage adjustments if it is necessary to extend or reduce dynamically-generated thresholds.
	To reduce dynamically-generated thresholds introduce a negative percentage and to extend them introduce a positive percentage. This enables fine-tuning of threshold levels.
</p>

<b>Dynamic threshold Two Tailed:</b>
<p>
	This field permits the use of both minimum and maximum ranges, given that the default setting only shows minimums.
</p>
<b>Example:</b><br>
warning status min = 5 and max = 10<br>
critical status min = 10 and max = 15<br>
These parameters mean the module will record the following states:<br> 
	- Status normal from -infinite to 4 and from 16 to infinite.<br>
	- Status warning from 5 to 9.<br>
	- Status critical from 10 to 15.
<br><br>
<b>Example 2:</b><br>
warning status min = 40 and max = 80 with inverse interval checked<br>
critical status min = 20 and max = 100 with inverse interval checked<br>
These parameters mean the module will record the following states:<br> 
	- Status normal from 41 to 79.<br>
	- Status warning from 21 to 40 and from 80 to 99.<br>
	- Status critical from -infinite to 20 and from 100 to infinite.
<br><br>
<b>For more information, please see the graph showing status according to the values which are introduced.</b>