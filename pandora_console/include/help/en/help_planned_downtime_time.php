<?php
/**
 * @package Include/help/en
 */
?>
<h1>Scheduled downtimes. Time and date configuration</h1>

<h2>Once execution</h2>

<p>
    The date format must be year/month/day and the time format must be hour:minute:second.
    It's possible to create a scheduled downtime with a past date, if that option aren't disabled by the admin of <?php echo get_product_name(); ?>.
</p>

<h2>Periodically execution</h2>

<h3>Monthly</h3>

<p>
    The downtime will be executed every month, from the start day at the start time, to the end date at the end time selected.
    The time format must be hour:minute:second and the start day can't be lower than the end day.
    To reflect a downtime which ends away than the last day of the month, it's necessary to create two scheduled downtimes, the first should ends the day 31 at 23:59:59 and the other should start the day 1 at 00:00:00.
</p>

<h3>Weekly</h3>

<p>
    The downtime will be executed every selected day, from the start time, to the end time selected.
    The start time can't be lower than the end time.
    To reflect a downtime which ends away than the last day time, it's necessary to create two scheduled downtimes, the first should end at 23:59:59 and the other should start at 00:00:00 on the next day.
</p>