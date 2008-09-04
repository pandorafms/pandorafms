This is a plugin that can be called from within the Pandora configuration file.

It will call an IPMI-capable host (IP-address) and acquire it's sensors, then parse them into an understandable XML file for Pandora FMS. 
Make sure you set up a the correct name for the agent configuration if the monitoring is done from another host than the one the IPMI chip is located at.

This script might not work and has only been tested so far against an Intel-based Apple XServe but the script is built up so it should acquire any.

Requires ipmitool and php (4 or 5) on the machine the agent is running on.
