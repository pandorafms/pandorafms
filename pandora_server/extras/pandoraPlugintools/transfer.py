from datetime import datetime
from subprocess import *
import shutil
import subprocess
import os
import sys
from .general import generate_md5,set_dict_key_value
from .agents import print_agent

####
# Define global variables dict, used in functions as default values.
# Its values can be changed.
#########################################################################################

global_variables = {
    'transfer_mode'       : 'tentacle',
    'temporal'            : '/tmp',
    'data_dir'            : '/var/spool/pandora/data_in/',
    'tentacle_client'     : 'tentacle_client',
    'tentacle_ip'         : '127.0.0.1',
    'tentacle_port'       :  41121,
    'tentacle_extra_opts' : ''
}

####
# Set a global variable with the specified name and assigns a value to it.
#########################################################################################
def set_global_variable(
        variable_name: str = "", 
        value
    ):
    """
    Sets the value of a global variable in the 'global_variables' dictionary.

    Args:
        variable_name (str): Name of the variable to set.
        value (any): Value to assign to the variable.
    """
    set_dict_key_value(global_variables, variable_name, value)

####
# Sends file using tentacle protocol
#########################################################################################
def tentacle_xml(
        data_file: str = "", 
        tentacle_ops: dict = {},
        tentacle_path: str = global_variables['tentacle_client'], 
        debug: int = 0,
        print_errors: bool = True
    ) -> bool:
    """
    Sends file using tentacle protocol
    - Only works with one file at time.
    - file variable needs full file path.
    - tentacle_opts should be a dict with tentacle options (address [password] [port]).
    - tentacle_path allows to define a custom path for tentacle client in case is not in sys path).
    - if debug is enabled, the data file will not be removed after being sent.
    - if print_errors is enabled, function will print all error messages

    Returns True for OK and False for errors.
    """

    if data_file is not None :
    
        if not 'address' in tentacle_ops:
            tentacle_ops['address'] = global_variables['tentacle_ip']
        if not 'port' in tentacle_ops:
            tentacle_ops['port'] = global_variables['tentacle_port']
        if not 'extra_opts' in tentacle_ops:
            tentacle_ops['extra_opts'] = global_variables['tentacle_extra_opts']            

        if tentacle_ops['address'] is None :
            if print_errors:
                sys.stderr.write("Tentacle error: No address defined")
            return False
        
        try :
            with open(data_file.strip(), 'r') as data:
                data.read()
            data.close()
        except Exception as e :
            if print_errors:
                sys.stderr.write(f"Tentacle error: {type(e).__name__} {e}")
            return False

        tentacle_cmd = f"{tentacle_path} -v -a {tentacle_ops['address']} -p {tentacle_ops['port']} {tentacle_ops['extra_opts']} {data_file.strip()}"

        tentacle_exe=Popen(tentacle_cmd, stdout=subprocess.PIPE,stderr=subprocess.PIPE, shell=True)
        rc=tentacle_exe.wait()
        
        if debug == 0 : 
            os.remove(data_file.strip())

        if rc != 0 :
            if print_errors:
                stderr = tentacle_exe.stderr.read().decode()
                msg="Tentacle error:" + str(stderr)
                print(str(datetime.today().strftime('%Y-%m-%d %H:%M')) + msg , file=sys.stderr)
            return False
    
    else:
        if print_errors:
            sys.stderr.write("Tentacle error: file path is required.")
        return False

####
# Detect transfer mode and send XML.
#########################################################################################
def transfer_xml(
        file: str = "",
        transfer_mode: str = global_variables['transfer_mode'],
        tentacle_ip: str = global_variables['tentacle_ip'],
        tentacle_port: int = global_variables['tentacle_port'],
        tentacle_extra_opts: str = global_variables['tentacle_extra_opts'],
        data_dir: str = global_variables['data_dir']
    ):

    """
    Detects the transfer mode and calls the agentplugin() function to perform the transfer.

    Args:
        file (str): Path to file to send.
        transfer_mode (str, optional): Transfer mode. Default is global_variables['transfer_mode'].
        tentacle_ip (str, optional): IP address for Tentacle. Default is global_variables['tentacle_ip'].
        tentacle_port (str, optional): Port for Tentacle. Default is global_variables['tentacle_port'].
        data_dir (str, optional): Path to data dir with local transfer mode. Default is global_variables['data_dir'].
    """
    if file is not None:
        if transfer_mode != "local":
            tentacle_conf = {
                'address'    : tentacle_ip,
                'port'       : tentacle_port,
                'extra_opts' : tentacle_extra_opts
            }
            tentacle_xml(file, tentacle_conf)
        else:
            shutil.move(file, data_dir)

####
# Creates a agent .data file in the specified data_dir folder
#########################################################################################
def write_xml(
        xml: str = "",
        agent_name: str = "",
        data_dir: str = global_variables['temporal']
    ) -> str:
    """
    Creates a agent .data file in the specified data_dir folder
    Args:
    - xml (str): XML string to be written in the file.
    - agent_name (str): agent name for the xml and file name.
    - data_dir (str): folder in which the file will be created.
    """
    Utime = datetime.now().strftime('%s')
    agent_name_md5 = generate_md5(agent_name)
    data_file = "%s/%s.%s.data" %(str(data_dir),agent_name_md5,str(Utime))
    
    try:
        with open(data_file, 'x') as data:
            data.write(xml)
    except OSError as o:
        print(f"ERROR - Could not write file: {o}, please check directory permissions", file=sys.stderr)
    except Exception as e:
        print(f"{type(e).__name__}: {e}", file=sys.stderr)
    
    return data_file
