<h1>Prediction date</h1>


<p>Prediction date return a date in the future where a module reach an interval. It uses least squares method.</p>

<p>
<b>Period</b>: Period of time to make the estimation.
</p>
<p>
<b>Data Range</b>: Interval that the module needs to reach to return the associated date. 
</p>

<p>For example, for the module disk_temp_free and choosing 2 months and searching for the date where the module reach the interval [5-0] the result will be 04 Dec 2011 18:36:23.</p>
<p>This is a graphic vision of the explanation: </p>

<?php html_print_image('images/help/prediction_date.png', false, ['height' => '210']);
