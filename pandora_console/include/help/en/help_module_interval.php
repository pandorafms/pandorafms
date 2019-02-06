<?php
/*
    Include package help/en
*/
?>

<p> The module interval defines the periodicity with which the module should return data. If twice the module interval has elapsed and there is no new data, one of two things can happen:
<ol>
<li>If the module is asynchronous it's status is reset to normal.</li>
<li>If the module is synchronous it's status is set to unknown.</li>
</ol>
</p>

