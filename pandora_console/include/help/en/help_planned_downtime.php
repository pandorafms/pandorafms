<h1>Planned downtimes</h1>

<p>
This tool is used to plan non-monitoring periods of time. This is useful if you know, for example, that a group of systems will be disconnected in a specific time. This helps to avoid false alarms. 
</p>
<p>
It's very easy to setup, you specify start date/time of planned downtime and an end date/time. You can include in that downtime a list of agents.
</p>
<p>
When planned downtime starts, Pandora FMS automatically disable all agents assigned to this downtime and no alerts or data are processed. When downtime ends, Pandora FMS will be enable all agents assigned to this downtime. You cannot delete or modify a downtime instance when it's fired, you need to wait for ending before doing anything in this downtime instance. Of course you can manually, enable an agent using the agent configuration dialog.
</p>
