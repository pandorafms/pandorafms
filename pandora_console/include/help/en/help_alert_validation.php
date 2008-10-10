<h1>Alert validation</h1>
<p>
ACK an alert only changes it's current bit and clear the "fired", so if alert fired again, the process continues. It's oriented to alerts with a long threshold, for example 1 day. If you get an alarm, and you review and fix it, you probably want to set to green status and don't wait 1 day to get green again.
</p>