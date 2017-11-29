<?php
/**
 * @package Include/help/es
 */
?>
<h1>Associated map status weight</h1>

<p>
Only "static image" type elements can have this option. Through it we establish a percentage of elements in a status other than the normal one so that our element linked to that map picks up its status.
</p>
<p>
For example:
</p>
<p>
If we have a map 1 with a "static image" type element that points to map 2 and has its weight set at 50%, you will only see its status modified if at least half of the elements (items that must return a status from an agent, module or other linked console) of map 2 are not in normal status.
</p>
<p>
We can also have an element linked to a console with 10 elements that return a status, which in turn can have elements linked to other consoles. If our element from the first console has a weight of 20%, it is because we want it to show a critical (or warning) status if at least 2 of the 10 elements of the second console have a critical (or warning) status. Similarly, if the weight is 80%, 8 out of 10 elements must be in a non-normal status.
</p>