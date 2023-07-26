from datetime import datetime
from subprocess import *
import hashlib
import sys
import os
from .modules import print_module,print_log_module

global_variables = {
    'temporal'         : '/tmp',
    'agents_group_name': '',
    'interval'         : 300
}
#########################################################################################
# OS check
#########################################################################################

POSIX = os.name == "posix"
WINDOWS = os.name == "nt"
LINUX = sys.platform.startswith("linux")
MACOS = sys.platform.startswith("darwin")
OSX = MACOS  # deprecated alias
FREEBSD = sys.platform.startswith("freebsd")
OPENBSD = sys.platform.startswith("openbsd")
NETBSD = sys.platform.startswith("netbsd")
BSD = FREEBSD or OPENBSD or NETBSD
SUNOS = sys.platform.startswith(("sunos", "solaris"))
AIX = sys.platform.startswith("aix")

####
# Set a global variable with the specified name and assigns a value to it.
###########################################
def set_global_variable(
        variable_name, 
        value
    ):
    """
    Sets the value of a global variable in the 'global_variables' dictionary.

    Args:
        variable_name (str): Name of the variable to set.
        value (any): Value to assign to the variable.
    """
    
    global_variables[variable_name] = value

####
# Prints agent XML. Requires agent conf 
# (dict) and modules (list) as arguments.
###########################################
def print_agent(
        agent, 
        modules,
        temp_dir=global_variables['temporal'],
        log_modules= None, 
        print_flag = None
    ):
    """Prints agent XML. Requires agent conf (dict) and modules (list) as arguments.
    - Use print_flag to show modules' XML in STDOUT.
    - Returns a tuple (xml, data_file).
    """
    data_file=None

    header = "<?xml version='1.0' encoding='UTF-8'?>\n"
    header += "<agent_data"
    for dato in agent:
        header += " " + str(dato) + "='" + str(agent[dato]) + "'"
    header += ">\n"
    xml = header
    if modules :
        for module in modules:
            modules_xml = print_module(module)
            xml += str(modules_xml)
    if log_modules :
        for log_module in log_modules:
            modules_xml = print_log_module(log_module)
            xml += str(modules_xml)
    xml += "</agent_data>"
    if not print_flag:
        data_file = write_xml(xml, agent["agent_name"], temp_dir)
    else:
        print(xml)
    
    return (xml,data_file)

####
# Creates a agent .data file in the 
# specified data_dir folder
###########################################
def write_xml(
        xml,
        agent_name,
        data_dir=global_variables['temporal']
    ):
    """Creates a agent .data file in the specified data_dir folder\n
    Args:
    - xml (str): XML string to be written in the file.
    - agent_name (str): agent name for the xml and file name.
    - data_dir (str): folder in which the file will be created."""

    Utime = datetime.now().strftime('%s')
    agent_name_md5 = (hashlib.md5(agent_name.encode()).hexdigest())
    data_file = "%s/%s.%s.data" %(str(data_dir),agent_name_md5,str(Utime))
    try:
        with open(data_file, 'x') as data:
            data.write(xml)
    except OSError as o:
        print(f"ERROR - Could not write file: {o}, please check directory permissions", file=sys.stderr)
    except Exception as e:
        print(f"{type(e).__name__}: {e}", file=sys.stderr)
    return (data_file)

####
# Init agent template
###########################################
def init_agent() :
    """
    Initializes an agent template with default values.

    Returns:
        dict: Dictionary representing the agent template with default values.
    """
    agent = {
        "agent_name"  : "",
        "agent_alias" : "",
        "parent_agent_name" : "",
        "description" : "",
        "version"     : "",
        "os_name"     : "",
        "os_version"  : "",
        "timestamp"   : datetime.today().strftime('%Y/%m/%d %H:%M:%S'),
        "address"     : "",
        "group"       : global_variables['agents_group_name'],
        "interval"    : global_variables['interval'],
        "agent_mode"  : "1",
        }
    return agent


#########################################################################################
# Agent class
#########################################################################################

class Agent:
    """Basic agent class. Requires agent parameters (config {dictionary})
    and module definition (modules_def [list of dictionaries]) """
    def __init__(
            self,
            config,
            modules_def
        ):
        self.config = config
        self.modules_def = modules_def
