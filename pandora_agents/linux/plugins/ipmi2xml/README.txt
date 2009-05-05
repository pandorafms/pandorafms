This is a simple script that could be called from within pandora_user.conf
It will call an IPMI-capable host (IP-address) and acquire it's sensors, 
then parse them into an understandable XML file for Pandora FMS.

Make sure you set up a the correct name for the agent configuration if the 
monitoring is done from another host than the one the IPMI chip is located at.

This script might not work and has only been tested so far against an 
Intel-based Apple XServe and XServe Nehalem but the script is built up so it
should acquire any other sensors.

ipmitool and php (tested 5, 4 should work too) is required on the machine the 
agent is running on. This incarnation of ipmi2xml has been tested with 
ipmitool 2.1.8 (which is part of Apple Server Admin Tools 1.7). Previous 
versions might not work (check SVN history for older versions.

Check guruevi's blog post on http://blog.pandorafms.org for more information 
on adapting this tool.