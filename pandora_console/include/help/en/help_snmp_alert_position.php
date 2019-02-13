<?php
/*
 * @package Include /help/en
 */
?>

<h1>Alert SNMP position</h1>

<p>The alerts with a lower position are evaluated first. If several alerts match with a trap, all matched alerts with same position will be thrown. Although lower position alerts match with the trap, they will not be thrown.</p>

<p>As a general rule, you have to set more restrictive alerts with lower positions.</p>
