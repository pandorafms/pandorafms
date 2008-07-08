Pandora XServe RAID agent configuration

This only contains the files and tools required to monitor the status of an XServe RAID. It uses a package I found on alienRAID for this purpose. 

The monitoring packages (xserve-raid-tools-1.2.*) also include Nagios plugins.

The pandora_agent.conf has all the configuration modules for each part of an XServe RAID (every single drive module can be monitored). 

For the agent itself, you'll have to use or clone a Mac, Unix or Linux client and overwrite the configuration with this one. I tested this and it's running on Mac OS X 10.4 and 10.5 in my environment.