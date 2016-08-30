<h1>Cascade protection</h1>

<?php html_print_image("images/help/cascade_protection_ilustration.png", false); ?>
<br>
<p>
This option should be assigned to avoid an “alert storm” that can come in because a group of agents are unreachable. This type of behaviour occurs when an intermediary device, like for example a router, is down and all devices behind it, therefore, cannot be reached. Probably these devices aren’t down and chances indicate that they’re working with another router in HA mode. But, if nothing is done, it’s likely for Pandora FMS to think that they’re down since they can’t be tested using a Remote ICMP Proc test (a Ping check).
<br><br>
When you enable <i>cascade protection</i> for an agent, this means that if any of its parents has a CRITICAL alert fired, then the agent’s alerts WILL NOT BE fired. If the agent's parent has a module in CRITICAL or several alerts with less criticality than CRITICAL, alerts from the agent will be fired as normal if needed. Cascade protection checks parent alerts with CRITICAL priority, including the correlated alerts assigned to the parent.
<br><br>
If you want to use an advanced cascade protection system, just use correlation among successive parents, and just enable Cascade Protection for the children.
</p>
