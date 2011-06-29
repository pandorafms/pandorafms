<?php
/**
 * @package Include/help/es
 */
?>
<h1>Trap types</h1>
<ul>
    <li>Cold start (0): Indicates that the agent has been started/restarted;</li>
    <li>Warm start (1): Indicates that the agent configuration has been changed;</li>
    <li>Link down (2): Indicates that the communication interface is out of service (inactive);</li>
    <li>Link up (3): Indicates that a communication interface is in service(active);</li>
    <li>Authentication failure (4): Indicates that the agent has been received a request from a not authorized NMS (normally controlled by a community);</li>
    <li>EGP neighbor loss (5): Indicates that in systems that routers are using EGP protocol, a near host is out of service;</li>
    <li>Enterprise (6): In this category are all the new traps, including by the vendors.</li>
</ul>
