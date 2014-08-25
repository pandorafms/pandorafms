<?php
/**
 * @package Include/help/en
 */
?>

<h1>ACL propagation</h1>

ACL propagation was designed to do not have to include in the ACL definition each subgroup pending of a main group. Let's see an example:
<br><br>
Suppose you we have this hierarchy of group/subgroups:
<br><br>
<pre> 
   Customers
     + Customer A
     + Customer B
     + Customer C
     + Customer D
</pre>
<br><br>
If we want to give access to an operator to customer A,B,C and D and future customers depending on Customer group, click on "propagate ACL" in the "Customer" group. This means that any user who have access to group "Customers" will have the same access to subgroups inside the main group.

<h3>Propagation and tags</h3>

Propagation is compatible with the tag subsystem. This means the propagation affects to all subgroups checking tags.

