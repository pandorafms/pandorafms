<h1>Cascade protection</h1>


<img src='../images/help/cascade_protection_ilustration.png'>
<br>
<p>
This option is designed to avoid a "storm" of alerts coming because a group of agents are unreachable. This kind of behaviour happen when an intermediate device, as for example a router, is down, and all devices behind it are just not reachable, probably that devices are not down and even that devices are working behind another router, in HA mode, but if you don't do nothing probably Pandora FMS thinks they are down because cannot remotely test it with a Remote ICMP Proc test (a ping).
<br><br>
When you enable <i>cascade protection</i> in an agent, this means that if it's parent has a CRITICAL alert fired, then the agent alerts WILL NOT BE fired. If agent's parent has a module in CRITICAL or several alerts with less criticity than CRITICAL, alerts from the agent will be fired if should be. Cascade protection checks parents alerts with CRITICAL criticity, including the correlation alerts assigned to the parent.
<br><br>
If you want to use an advanced cascade protection system, just use correlation between sucesive parents, and just enable the Cascade Protection in the children.
</p>
