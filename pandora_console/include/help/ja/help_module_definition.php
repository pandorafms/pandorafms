<?php
/**
 * @package Include/help/ja
 */
?>
<h1>モジュール定義</h1>
<p>Agents can be configured from the console in three working modes:</p>
<ul>
    <li>
        <b>Learning mode:</b> If the XML received from the software agent contains new modules, they will be automatically created. This is the default behavior.
    </li>
<br>
<<<<<<< HEAD
    <li>
        <b>Normal mode:</b> No new modules will be created that arrive in XML if they have not been previously declared in the console.
    </li>
<br>
    <li>
        <b>Autodisable mode:</b> Similar to learning mode, in this mode, also, if all modules pass to unknown state the agent will be automatically disabled, going to be enabled again if it receives new information.
    </li>
=======
    <li><i>通常モード:</i> このモードでは、モジュール設定を手動で実施する必要があります。自動設定は行われません。</li>
<br>
    <li><i>Autodisable mode:</i> In terms of creating agents and modules it behaves exactly the same as an agent in learning mode: when the first XML reaches it, the first agent is created and, on each report, if there are new modules they can also be added automatically. Nevertheless, when all modules from an agent that are in autodisable mode are also marked as unknown, the agent is automatically disabled. In any case, if the agent reports again, it gets enabled again on its own.</li>
>>>>>>> develop
</ul>
