## Purpose of this toolkit

This directory contains configuration files and small scripts to generate an environment with simulated data for testing purposes.

### Quik start
To add monitoring data, inventory, groups and users to a pandorafms environment automatically Just execute: 
```
/usr/share/pandora_server/util/load/install_load_data.sh
```

This command will creates:
- 30 Agents with inventory linux
- 30 Agents whith inventory Windows
- 300 Agents
- 44 Groups
- 34 Users

Will randonly add users to groups and move agents to groups.
Creates a cronjob to generate agent data each 5 min and inventory data once a day.

_note: make sure you have a licence with at least 300 agents or modify the /usr/share/pandora_server/util/load/pandora_xml_stress.agents to use max your licence limit agent amount before execute_

## Toolset description.
Here we describe the specific use for all the toolset the `install_load_data.sh` used automatically, to be used individualy if its needed 

### Generation of inventory agents
The script will use th file pandora_xml_stress.agents take by default the first 30 listed agents and add linux inventory and the last 30 listed agents and add windows inventory.
This will generate xml data using the templates on `templates` folder.

to run it, execute  
```
cd pandorafms/pandora_server/util/load 
./generate_inventory_data.sh
```

### Generation of XML files to simulate agent load

There is a tool that comes configured with Pandora FMS to generate test data (pandora_xml_stress) and that generates XML. It has different options and in this directory is provided a configuration file and all the dictionaries and additional files to generate data of 300 agents, with pseudo-random names (like for example "7fb8a1a734c24cc22a5c75eb").

These agents are defined in the "pandora_xml_stress.agents" file. If you want less agents, you can delete elements in this file.

To execute the XML generation manually from the code repository:
```
 cd pandorafms/pandora_server/util/load
 perl ../pandora_xml_stress.pl pandora_xml_stress.conf 
```
This will generate 300 XML in the /var/spool/pandora/data_in directory.

If you create a scheduled execution of this command every 5 minutes (e.g. through cron), keep in mind that if the PandoraFMS server stops, it could have hundreds of thousands of XML files pending to be processed.

Create /etc/cron.d/pandora_stress with this content:
```
  */5 * * * * root <putyourscripthere>
```

### Generation of groups and users

The script 'create_usersandgroups.sh' will take a list of names from the file 'usernames.txt' and through CLI will create those users in the local Pandora FMS.

On the other hand, it will also create a series of groups, taking as source the names of the file 'groupnames.txt'. It will associate to each user a group of the existing ones with the profile "Operator (Read").

Finally, it will take all the agents available in Pandora FMS and it will distribute them in an equal and random way among the available groups.

You should only run it once:
```
  cd pandorafms/pandora_server/util/load
  ./create_userandgroups.sh
```