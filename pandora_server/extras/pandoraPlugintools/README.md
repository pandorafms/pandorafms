# Python: module plugintools for PandoraFMS Developers

pandoraPluginTools is a library that aims to help the creation of scripts and their integration in PandoraFMS.

[PluginTools Reference Documentation](https://pandorafms.com/guides/public/books/plugintools)

The package includes the following modules: agents, modules, transfer, general, discovery and http. Each one has different requirements and functions that facilitate and automate the data integration in PandoraFMS. They have the following dependencies : 

**agents**
Module that contains functions oriented to the creation of agents.
- datetime.datetime
- subprocess.Popen
- Hashlib
- sys
- os
- print_module
- print_log_module

**modules**
Module that contains functions oriented to the creation of modules.

**transfer**
Module containing functions oriented to file transfer and data sending.
- datetime.datetime
- subprocess.Popen
- shutil
- sys
- os
- print_agent

**general**
Module containing general purpose functions, useful in the creation of plugins for PandoraFMS.
- datetime.datetime
- hashlib
- json
- sys

**discovery**
Module that contains general purpose functions, useful in the creation of plugins for PandoraFMS discovery.
- json
- sys

**http**
Module that contains useful functions for making http requests.
- requests_ntlm.HttpNtlmAuth
- requests.auth.HTTPBasicAuth
- requests.auth.HTTPDigestAuth
- requests.sessions.Session


## Example 

``` python
import pandoraPluginTools as ppt

## Define agent
server_name = "WIN-SERV"

agent=ppt.agents.init_agent()
agent.update(
    agent_name  = ppt.generate_md5(server_name),
    agent_alias = server_name,
    description = "Default Windows server",
)

## Define modules
modules=[]

data = 10
modules.append({
    "name" : "CPU usage",
    "type" : "generic_data",
    "value": data,
    "desc" : "percentage of cpu utilization",
    "unit" : "%"
})

## Transfer XML
ppt.transfer_xml(
    agent,
    modules,
    transfer_mode="tentacle",
    tentacle_address="192.168.1.20",
    tentacle_port="41121",
    temporal="/tmp"
)
```

