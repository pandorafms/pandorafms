<?php
/**
 * @package Include/help/es
 */
?>
<h1>Ways to calculate the status of the linked visual console</h1>

<h3>By Default</h3>
<p>
    It calculates the status based on the status of all elements, as an agent would do.
</p>

<h3>By weight</h3>
<p>
    It calculates the status of the elements that have a visual console, a module or an agent assigned in relation to a percentage of elements configured by the user. This percentage is the one that has to exceed the number of elements of a non-normal status with respect to the number of elements taken into account in the calculation for that status to change.
</p>

<p>
    For example, given an element with a percentage of 50% and a visual console linked with 5 elements:
</p>
<ul>
    <li>1 <i>critical</i>, 1 <i>warning</i> y 3 <i>normal</i> -> Status <i>normal</i>.</li>
    <li>2 <i>critical</i>, 2 <i>warning</i> y 1 <i>normal</i> -> Status  <i>normal</i>.</li>
    <li>1 <i>critical</i>, 3 <i>warning</i> y 1 <i>normal</i> -> Status  <i>warning</i>.</li>
    <li>3 <i>critical</i>, 1 <i>warning</i> y 1 <i>normal</i> -> Status  <i>critical.</i></li>
    <li>1 <i>critical</i>, 1 <i>warning</i> y 3 <i>unknown</i> -> Status  <i>unknown</i>.</li>
</ul>

<p>
    If several statuses exceed the weight, the priority is the same as in the rest of the status calculation (<i>critical</i> > <i>warning</i> > <i>unknown</i>). If there are no elements to perform the calculation, the status becomes <i>unknown</i>.
</p>

<h3>By critical elements</h3>
<p>
    It calculates the status using the elements in <i>critical</i> status and the percentages of the thresholds defined by the user. If the number of elements in <i>critical</i> status with respect to the number of elements taken into account in the calculation exceeds the percentage assigned as <i>warning</i>, the status becomes <i>warning</i>. The same applies to the percentage assigned as <i>critical</i>, which also has preference.
</p>
