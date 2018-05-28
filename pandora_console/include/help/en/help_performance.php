<?php
/**
 * @package Include/help/en
 */
?>
<h1>Performance Configuration</h1>


<b>Max. days before delete events</b>
<br><br>
Maximum number of days before delete events.
<br><br>
<b>Max. days before delete traps</b>
<br><br>
Maximum number of days before delete traps.
<br><br>
<b>Max. days before delete audit events</b>
<br><br>
Maximum number of days before delete audited evetns.
<br><br>
<b>Max. days before delete string data</b>
<br><br>
Maximum number of days before delete string data.
<br><br>
<b>Max. days before delete GIS data</b>
<br><br>
Maximum number of days before delete GIS data.
<br><br>
<b>Max. days before purge</b>
<br><br>
Maximum number of days before purge database. This parameter is also used to specify max. number of days before deleting inventory data. If you have installed a history database, this number must be higher than the number of days before data is transferred to history database. Remember that in the history database never deleted data.
<br><br>
<b>Max. days before compact data</b>
<br><br>
Maximum number of days before compact data.
<br><br>
<b>Compact interpolation in hours (1 Fine-20 bad)</b>
<br><br>
Interpolation range. 1 is the best and 20 the worst. It is recommended to use 1 or values near to 1.
<br><br>
<b>SLA period (seconds)</b>
<br><br>
Default time, in seconds, to calculate SLA in agents SLA tab. Calculates the SLA automatically in modules defined in an agent based on Critical or Normal values.
<br><br>
<b>Default hours for event view</b>
<br><br>
Default number of hours for event filter. If the value is 24 hours, the event views will only show the events which happened in the last 24 hours.
<br><br>
<b>Use realtime statistics</b>
<br><br>
Enabled/Disabled real time statistics.
<br><br>
<b>Batch statistics period (secs)</b>
<br><br>
If realtime statistics are disaabled, here you define the refresh time for batch statistics.
<br><br>
<b>Use agent access graph</b>
<br><br>
Agent access graph, renders the number of agent contacts per hour in a graph with a daily scale (24h). This is use to know the frecuency of contact for each agent. It could take a long time to processs the date, so if you have low resources its recommended to disable it.
<br><br>
<b>Max. days before delete unknown modules</b>
<br><br>
Maximum number of days before delete unknown modules. 
<br><br>
<i>**All these parameters are made when running the DB Tool</i>

