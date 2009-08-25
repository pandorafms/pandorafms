<?php
/**
 * @package Include/help/en
 */
?>
<h1>Configuration management</h1>

This tool is used to several purposes:<br><br>

<ul>
<li> Copy module and/or alert configuration from one agent to one or several destination agents.</li>
<li> Delete module and/or alert configuration from a group of agents.</li>
<li> Full deletion of agents selecting several at once.</li>
</ul>

<h2> Copy module / alert configuration</h2>

<ol>
<li> Select source group.</li>
<li> Select source agent.</li>
<li> Select one or more modules in source agent.</li>
<li> Select destination agents for copy operation.</li>
<li> Select targets: Module for only copy modules, Alert for only copy alerts (if destination agents don't have a module with the same name defined in source agent, tool  cannot replicate alert). You could select both to first create module and after replicate alert (if defined).</li>
<li> Click on "Copy Modules/Alerts" button.</li>
</ol>

<h2> Delete module / alert configuration</h2>

<p>
This will delete all destination modules/alerts with the same name that has been selected in source agent / modules. All alerts associated to source modules will be deleted in destination agents if they have a module with the same name and alerts associated to them.
</p>

<ol>
<li> Select source group.</li>
<li> Select source agent.</li>
<li> Select one or more modules in source agent.</li>
<li> Select destination agents for delete operation.</li>
<li> Select targets: Modules, Alerts or both.</li>
<li> Click on "Delete Modules/Alerts" button.
</ol>

<h2> Delete agents</h2>

<p>
This will delete all agent information (modules, alerts, events...) from the list of agents selected in the listbox on the bottom.
</p>

<ol>
<li> Select destination agents for delete operation in the listbox on the bottom.</li>
<li> Click on "Delete agents" button.</li>
</ol>
