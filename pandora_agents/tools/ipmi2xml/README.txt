This is a simple script that could be called from within pandora_user.conf
It will call an IPMI-capable host (IP-address) and acquire it's sensors, then parse them into an understandable XML file for Pandora FMS.

Make sure you set up a the correct name for the agent configuration if the monitoring is done from another host than the one the IPMI chip is located at.

This script might not work and has only been tested so far against an Intel-based Apple XServe but the script is built up so it should acquire any.

ipmitool and php (tested 5, 4 should work too) is required on the machine the agent is running on.