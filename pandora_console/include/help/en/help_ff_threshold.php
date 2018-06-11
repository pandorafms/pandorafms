<?php
/**
 * @package Include/help/en
 */
?>
<h1>Module Flip Flop Threshold</h1>

<br>
<br>

The Threshold parameter FF (FF = FlipFlop) is used to "filter" the continuous changes of state in the generation of events / states, so that you can tell <?php echo get_product_name();?> until an element is not at least X times in the same state after changing from an original state, not considered to have changed. 
<br><br>
Take a classic example: A ping to a host where there is packet loss. In an environment like this, might give results as:
<br>
<pre>
 1  
 1  
 0  
 1  
 1  
 0  
 1  
 1  
 1 
</pre>
<br>
However, the host is alive in all cases. What we really want is to tell <?php echo get_product_name();?> that until the host does not say you are at least three times down, not marked as such, so that in the previous case and would never be dropped, and only in this case it would be:
<pre>
 1  
 1  
 0  
 1  
 0  
 0  
 0  
 </pre>
<br>
From this point you would see as down, but not before.
<br>
Protection anti Flip-Flop is used to avoid those annoying fluctuations, all modules implement it and use it to avoid the change of state (defined by their defined limits or boundaries machines, as is the case with modules * proc) . 

<br><br>

