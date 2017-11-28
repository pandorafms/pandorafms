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
    <li>
        <b>Normal mode:</b> No new modules will be created that arrive in XML if they have not been previously declared in the console.
    </li>
<br>
    <li>
        <b>Autodisable mode:</b> Similar to learning mode, in this mode, also, if all modules pass to unknown state the agent will be automatically disabled, going to be enabled again if it receives new information.
    </li>
</ul>
