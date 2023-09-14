# Python: module plugintools for PandoraFMS Developers

pandoraPluginTools is a library that aims to help the creation of scripts and their integration in Pandora FMS.

[PluginTools Reference Documentation](https://pandorafms.com/guides/public/books/plugintools)

The package includes the following modules. Each one has different functions that facilitate and automate the data integration in Pandora FMS:

**general**
Module containing general purpose functions, useful in the creation of plugins for PandoraFMS.

**threads**
Module containing threading purpose functions, useful to run parallel functions.

**agents**
Module that contains functions oriented to the creation of Pandora FMS agents

**modules**
Module that contains functions oriented to the creation of Pandora FMS modules.

**transfer**
Module containing functions oriented to file transfer and data sending to Pandora FMS server.

**discovery**
Module containing functions oriented to the creation of Pandora FMS discovery plugins.

**http**
Module containing functions oriented to HTTP API calls.

## Example

```python
import pandoraPluginTools as ppt

## Define agent
server_name = "WIN-SERV"

agent=ppt.init_agent({
    "agent_name"  : ppt.generate_md5(server_name),
    "agent_alias" : server_name,
    "description" : "Default Windows server"
})

## Define modules
modules=[]

data = 10
modules.append({
    "name" : "CPU usage",
    "type" : "generic_data",
    "value": data,
    "desc" : "Percentage of CPU utilization",
    "unit" : "%"
})

## Generate and transfer XML
xml_content = ppt.print_agent(agent, modules)
xml_file = ppt.write_xml(xml_content, agent["agent_name"])
ppt.transfer_xml(
    xml_file,
    transfer_mode="tentacle",
    tentacle_ip="192.168.1.20",
    tentacle_port="41121",
)
```

The package has the following dependencies:

- Hashlib
- datetime.datetime
- hashlib
- json
- os
- print_agent
- print_log_module
- print_module
- queue.Queue
- requests.auth.HTTPBasicAuth
- requests.auth.HTTPDigestAuth
- requests.sessions.Session
- requests_ntlm.HttpNtlmAuth
- shutil
- subprocess.Popen
- sys
- threading.Thread
