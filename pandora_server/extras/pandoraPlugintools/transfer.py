from datetime import datetime
from subprocess import *
import shutil
import subprocess
import os
import sys

global_variables = {
    'transfer_mode'    : 'tentacle',
    'temporal'         : '/tmp',
    'data_dir'         : '/var/spool/pandora/data_in/',
    'tentacle_client'  : 'tentacle_client',
    'tentacle_ip'      : '127.0.0.1',
    'tentacle_port'    :  41121
}

####
# Set a global variable with the specified name and assigns a value to it.
###########################################
def set_global_variable(
        variable_name,
        value
    ):
    
    global_variables[variable_name] = value

####
# Sends file using tentacle protocol
###########################################
def tentacle_xml(
        file, 
        tentacle_ops,
        tentacle_path='', 
        debug=0
    ):
    """Sends file using tentacle protocol\n
    - Only works with one file at time.
    - file variable needs full file path.
    - tentacle_opts should be a dict with tentacle options (address [password] [port]).
    - tentacle_path allows to define a custom path for tentacle client in case is not in sys path).
    - if debug is enabled, the data file will not be removed after being sent.

    Returns 0 for OK and 1 for errors.
    """

    if file is None :
        msg="Tentacle error: file path is required."
        print(str(datetime.today().strftime('%Y-%m-%d %H:%M')) + msg, file=sys.stderr)
    else :
        data_file = file
    
    if tentacle_ops['address'] is None :
        msg="Tentacle error: No address defined"
        print(str(datetime.today().strftime('%Y-%m-%d %H:%M')) + msg, file=sys.stderr)
        return 1
    
    try :
        with open(data_file, 'r') as data:
            data.read()
        data.close()
    except Exception as e :
        msg=f"Tentacle error: {type(e).__name__} {e}"
        print(str(datetime.today().strftime('%Y-%m-%d %H:%M')) + msg , file=sys.stderr)
        return 1

    tentacle_cmd = f"{tentacle_path}{global_variables['tentacle_client']} -v -a {tentacle_ops['address']} {global_variables['tentacle_opts']}"
    if "port" in tentacle_ops:
        tentacle_cmd += f"-p {tentacle_ops['port']} "
    if "password" in tentacle_ops:
        tentacle_cmd += f"-x {tentacle_ops['password']} "
    tentacle_cmd += f"{data_file.strip()} "

    tentacle_exe=Popen(tentacle_cmd, stdout=subprocess.PIPE,stderr=subprocess.PIPE, shell=True)
    rc=tentacle_exe.wait()

    if rc != 0 :
        stderr = tentacle_exe.stderr.read().decode()
        msg="Tentacle error:" + str(stderr)
        print(str(datetime.today().strftime('%Y-%m-%d %H:%M')) + msg , file=sys.stderr)
        next
        return 1
    elif debug == 0 : 
        os.remove(file)
 
    return 0

####
# Detect transfer mode and execute
###########################################
def agentplugin(
        modules,
        agent,
        temp_dir=global_variables['temporal'],
        tentacle=False,
        tentacle_conf=None
    ):
    agent_file=print_agent(agent,modules,temp_dir)

    if agent_file[1] is not None:
        if tentacle == True and tentacle_conf is not None:
            tentacle_xml(agent_file[1],tentacle_conf)
        else:
            shutil.move(agent_file[1], global_variables['data_dir'])

####
# Detect transfer mode and execute (call agentplugin())
###########################################
def transfer_xml(
        agent,
        modules,
        transfer_mode=global_variables['transfer_mode'],
        tentacle_ip=global_variables['tentacle_ip'],
        tentacle_port=global_variables['tentacle_port'],
        temporal=global_variables['temporal']
    ):
    
    if transfer_mode != "local" and tentacle_ip is not None:
        tentacle_conf={"address":tentacle_ip,"port":tentacle_port}
        agentplugin(modules,agent,temporal,True,tentacle_conf)
    else:
        agentplugin(modules,agent,temporal) 
