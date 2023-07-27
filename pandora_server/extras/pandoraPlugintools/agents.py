from datetime import datetime
from subprocess import *
import hashlib
import sys
import os
from .general import now,set_dict_key_value
from .modules import print_module,print_log_module
from .transfer import write_xml

####
# Define global variables dict, used in functions as default values.
# Its values can be changed.
#########################################################################################

global_variables = {
    'agents_group_name' : '',
    'interval'          : 300
}

####
# Define some global variables
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
# Agent class
#########################################################################################

class Agent:
    """
    Basic agent class. Requires agent parameters (config {dictionary})
    and module definition (modules_def [list of dictionaries])
    """
    def __init__(
            self,
            config: dict = None,
            modules_def: list = []
        ):

        if config is None:
            config = init_agent()

        self.config = config
        self.modules_def = modules_def

####
# Init agent template
#########################################################################################
def init_agent(
        default_values: dict = {}
    ) -> dict:
    """
    Initializes an agent template with default values.

    Returns:
        dict: Dictionary representing the agent template with default values.
    """
    agent = {
        "agent_name"        : "",
        "agent_alias"       : "",
        "parent_agent_name" : "",
        "description"       : "",
        "version"           : "",
        "os_name"           : "",
        "os_version"        : "",
        "timestamp"         : now(),
        "address"           : "",
        "group"             : global_variables['agents_group_name'],
        "interval"          : global_variables['interval'],
        "agent_mode"        : "1",
    }

    for key, value in default_values.items():
        if key in agent:
            agent[key] = value

    return agent

####
# Prints agent XML. Requires agent conf (dict) and modules (list) as arguments.
#########################################################################################
def print_agent(
        agent: dict = None, 
        modules: list = [],
        log_modules: list = [], 
        print_flag: bool = False
    ) -> str:
    """
    Prints agent XML. Requires agent conf (dict) and modules (list) as arguments.
    - Use print_flag to show modules' XML in STDOUT.
    - Returns xml (str).
    """
    xml = ""
    data_file = None

    if agent is not None:
        header = "<?xml version='1.0' encoding='UTF-8'?>\n"
        header += "<agent_data"
        for dato in agent:
            header += " " + str(dato) + "='" + str(agent[dato]) + "'"
        header += ">\n"
        xml = header
        
        for module in modules:
            modules_xml = print_module(module)
            xml += str(modules_xml)
        
        for log_module in log_modules:
            modules_xml = print_log_module(log_module)
            xml += str(modules_xml)
        
        xml += "</agent_data>"
    
    if print_flag:
        print(xml)

    return xml
