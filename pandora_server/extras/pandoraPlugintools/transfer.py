from datetime import datetime
from subprocess import *
import shutil
import subprocess
import os
import sys

####
# Define global variables dict, used in functions as default values.
# Its values can be changed.
#########################################################################################

_GLOBAL_VARIABLES = {
    'transfer_mode'       : 'tentacle',
    'temporal'            : '/tmp',
    'data_dir'            : '/var/spool/pandora/data_in/',
    'tentacle_client'     : 'tentacle_client',
    'tentacle_ip'         : '127.0.0.1',
    'tentacle_port'       :  41121,
    'tentacle_extra_opts' : '',
    'tentacle_retries'    : 1
}

####
# Internal: Alias for output.print_debug function
#########################################################################################

def _print_debug(
        var = "",
        print_errors: bool = False
    ):
    """
    Prints any list, dict, string, float or integer as a json

    Args:
        var: The variable to be printed.
        print_errors (bool): Whether to print errors.
    """
    from .output import print_debug
    print_debug(var, print_errors)

####
# Set a global variable with the specified name and assigns a value to it.
#########################################################################################
def set_global_variable(
        variable_name: str = "", 
        value = None
    )-> None:
    """
    Sets the value of a global variable in the '_GLOBAL_VARIABLES' dictionary.

    Args:
        variable_name (str): Name of the variable to set.
        value (any): Value to assign to the variable.

    Returns:
        None
    """
    from .general import set_dict_key_value

    set_dict_key_value(_GLOBAL_VARIABLES, variable_name, value)

####
# Get a global variable with the specified name.
#########################################################################################
def get_global_variable(
        variable_name: str = ""
    )-> None:
    """
    Gets the value of a global variable in the '_GLOBAL_VARIABLES' dictionary.

    Args:
        variable_name (str): Name of the variable to set.

    Returns:
        None
    """
    from .general import get_dict_key_value

    get_dict_key_value(_GLOBAL_VARIABLES, variable_name)

####
# Sends file using tentacle protocol
#########################################################################################
def tentacle_xml(
        data_file: str = "", 
        tentacle_ops: dict = {},
        tentacle_path: str = _GLOBAL_VARIABLES['tentacle_client'], 
        retry: bool = False,
        debug: int = 0,
        print_errors: bool = True
    ) -> bool:
    """
    Sends file using tentacle protocol
    
    Args:
        data_file (str): Path to the data file to be sent.
        tentacle_ops (dict): Tentacle options as a dictionary (address [password] [port]).
        tentacle_path (str): Custom path for the tentacle client executable.
        retry (bool): Whether to retry sending the file if it fails.
        debug (int): Debug mode flag. If enabled (1), the data file will not be removed after sending.
        print_errors (bool): Whether to print error messages.

    Returns:
        bool: True for success, False for errors.
    """
    from .output import print_stderr

    if data_file is not None :
    
        if not 'address' in tentacle_ops:
            tentacle_ops['address'] = _GLOBAL_VARIABLES['tentacle_ip']
        if not 'port' in tentacle_ops:
            tentacle_ops['port'] = _GLOBAL_VARIABLES['tentacle_port']
        if not 'extra_opts' in tentacle_ops:
            tentacle_ops['extra_opts'] = _GLOBAL_VARIABLES['tentacle_extra_opts']            

        if tentacle_ops['address'] is None :
            if print_errors:
                print_stderr("Tentacle error: No address defined")
            return False
        
        try :
            with open(data_file.strip(), 'r') as data:
                data.read()
            data.close()
        except Exception as e :
            if print_errors:
                print_stderr(f"Tentacle error: {type(e).__name__} {e}")
            return False

        tentacle_cmd = f"{tentacle_path} -v -a {tentacle_ops['address']} -p {tentacle_ops['port']} {tentacle_ops['extra_opts']} {data_file.strip()}"
        tentacle_exe=subprocess.Popen(tentacle_cmd, stdout=subprocess.PIPE,stderr=subprocess.PIPE, shell=True)
        rc=tentacle_exe.wait()

        result = True
            
        if rc != 0 :

            if retry:

                tentacle_retries = _GLOBAL_VARIABLES['tentacle_retries']

                if tentacle_retries < 1:
                    tentacle_retries = 1

                retry_count = 0

                while retry_count < tentacle_retries  :

                    tentacle_exe=subprocess.Popen(tentacle_cmd, stdout=subprocess.PIPE,stderr=subprocess.PIPE, shell=True)
                    rc=tentacle_exe.wait()

                    if rc == 0:
                        break  

                    if print_errors:
                        stderr = tentacle_exe.stderr.read().decode()
                        msg = f"Tentacle error (Retry {retry_count + 1}/{tentacle_retries}): {stderr}"
                        print_stderr(str(datetime.today().strftime('%Y-%m-%d %H:%M')) + msg)
                    
                    retry_count += 1

                if retry_count >= tentacle_retries:
                    result = False
            else:
                
                if print_errors:
                    stderr = tentacle_exe.stderr.read().decode()
                    msg="Tentacle error:" + str(stderr)
                    print_stderr(str(datetime.today().strftime('%Y-%m-%d %H:%M')) + msg)
                result = False  

        if debug == 0 : 
            os.remove(data_file.strip())  

        return result
    
    else:
        if print_errors:
            print_stderr("Tentacle error: file path is required.")
        return False

####
# Detect transfer mode and send XML.
#########################################################################################
def transfer_xml(
        file: str = "",
        transfer_mode: str = _GLOBAL_VARIABLES['transfer_mode'],
        tentacle_ip: str = _GLOBAL_VARIABLES['tentacle_ip'],
        tentacle_port: int = _GLOBAL_VARIABLES['tentacle_port'],
        tentacle_extra_opts: str = _GLOBAL_VARIABLES['tentacle_extra_opts'],
        data_dir: str = _GLOBAL_VARIABLES['data_dir']
    )-> None:

    """
    Detects the transfer mode and calls the agentplugin() function to perform the transfer.

    Args:
        file (str): Path to file to send.
        transfer_mode (str, optional): Transfer mode. Default is _GLOBAL_VARIABLES['transfer_mode'].
        tentacle_ip (str, optional): IP address for Tentacle. Default is _GLOBAL_VARIABLES['tentacle_ip'].
        tentacle_port (str, optional): Port for Tentacle. Default is _GLOBAL_VARIABLES['tentacle_port'].
        data_dir (str, optional): Path to data dir with local transfer mode. Default is _GLOBAL_VARIABLES['data_dir'].

    Returns:
        None
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
        data_dir: str = _GLOBAL_VARIABLES['temporal'],
        print_errors: bool = False
    ) -> str:
    """
    Creates an agent .data file in the specified data_dir folder

    Args:
        xml (str): XML string to be written in the file.
        agent_name (str): Agent name for the XML and file name.
        data_dir (str): Folder in which the file will be created.
        print_errors (bool): Whether to print error messages.

    Returns:
        str: Path to the created .data file.
    """
    from .general import generate_md5
    from .output import print_stderr

    Utime = datetime.now().strftime('%s')
    agent_name_md5 = generate_md5(agent_name)
    data_file = "%s/%s.%s.data" %(str(data_dir),agent_name_md5,str(Utime))
    
    try:
        with open(data_file, 'x') as data:
            data.write(xml)
    except OSError as o:
        if print_errors:
            print_stderr(f"ERROR - Could not write file: {o}, please check directory permissions")
    except Exception as e:
        if print_errors:
            print_stderr(f"{type(e).__name__}: {e}")
    
    return data_file
