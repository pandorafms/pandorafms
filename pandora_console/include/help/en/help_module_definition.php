<?php
/**
 * @package Include/help/en
 */
?>
<h1>Module definition</h1>
<p>
There are three modes for an agent:
</p>
<ul>
    <li><i>Learning mode:</i> All the modules sent by the agent are accepted. If modules are not defined, they will be automatically defined by the system. It is recommended to activate the agents in this mode and change it once the user is familiar with Pandora FMS.<br>From version 4.0.3, in this mode,  Pandora console collect all the configuration specified by the agent configuration file the first time and thereafter any changes should be made through console, will not catch changes in config file.</li>
<br>
    <li><i>Normal mode:</i> The modules in this mode must be conﬁgured manually. The self definition of the modules is not allowed in this mode.</li>
<br>
    <li><i>Autodisable mode:</i> It behaves exactly the same as an agent in learning mode: when the first XML reaches it, the first agent is created and, on each report, if there are new modules they can also be added automatically. Nevertheless, when all modules from an agent that are in autodisable mode are also marked as unknown, the agent is automatically disabled. In any case, if the agent reports again, it gets enabled again on its own.</li>
</ul>
