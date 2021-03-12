<?php
/**
 * @package Include/help/en
 */
?>
<h1>Cron for server modules</h1>

Using the configuration parameter sets <b>Cron from</b> and <b>Cron to</b> makes
it possible for a module to run only for certain periods of time. 
The way in which it is configured is similar to the syntax of 
<a class="font_14px" href="https://en.wikipedia.org/wiki/Cron">cron</a>. 
Just as they appear in the <?php echo get_product_name(); ?> console, each one of the parameters  
has three options.

<h4>Cron from: any</h4>

The module will not have restrictions in that parameter. Whatever the value is 
will be executed, and it is equivalent to the asterisk (*) in the cron nomenclature. In this 
case <b>Cron to</b> is ignored.

<h4>Cron from: different from any. Cron to: any</h4>

The module will run only during the time in which the date matches that  
parameter. It is equivalent to writingjust one number in cron nomenclature.

<h4>Cron from: different from any. Cron to: different from any</h4>

The module will run only during the time specified between <b>Cron from</b> and <b>Cron to</b>. 
It is equivalent to writing  number dash number (n-n) in cron nomenclature.

<h2>Agent interval</h2>

As long as cron conditions are met, the agent will run following 
its execution interval.

<h2>Examples</h2>

<ul>
    <li><i>* * * * *</i>: No cron configured.</li>
    <li><i>15 20 * * *</i>: It will run every day at 20:15.</li>
    <li><i>* 20 * * *</i>: It will run every day during the hour 20, that is, from 20:00 to 20:59.</li>
    <li><i>* 8-19 * * *</i>: It will run everyday from 8:00 to 19:59.</li>
    <li><i>15-45 * 1-16 * *</i>: It will run every first 16 days of the month every hour, from quarter past to quarter to.</li>
    <li><i>* * * 5 *</i>: It will run only in May.</li>
<ul>
