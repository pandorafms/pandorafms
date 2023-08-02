import sys
import os
from .general import debug_dict,now,set_dict_key_value,generate_md5
from .modules import init_module,init_log_module,print_module,print_log_module

####
# Define global variables dict, used in functions as default values.
# Its values can be changed.
#########################################################################################

GLOBAL_VARIABLES = {
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
        value = None
    ):
    """
    Sets the value of a global variable in the 'GLOBAL_VARIABLES' dictionary.

    Args:
        variable_name (str): Name of the variable to set.
        value (any): Value to assign to the variable.
    """
    set_dict_key_value(GLOBAL_VARIABLES, variable_name, value)

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
            modules_def: list = [],
            log_modules_def: list = []
        ):

        if config is None:
            config = init_agent()

        self.config = config
        self.modules_def = modules_def
        self.log_modules_def = log_modules_def
        self.added_modules = []

    '''
    TODO: Add commnets
    '''
    def update_config(
            self,
            config: dict = {}
        ):

        for key, value in config.items():
            if key in self.config:
                self.config[key] = value

    '''
    TODO: Add commnets
    '''
    def get_config(
            self
        ) -> dict:

        return self.config

    '''
    TODO: Add commnets
    '''
    def add_module(
            self,
            module: dict = {}
        ):

        if "name" in module and type(module["name"]) == str and len(module["name"].strip()) > 0:
            self.modules_def.append(init_module(module))
            self.added_modules.append(generate_md5(module["name"]))

    '''
    TODO: Add commnets
    '''
    def del_module(
            self,
            module_name: str = ""
        ):

        if len(module_name.strip()) > 0:
            try:
                module_id = self.added_modules.index(generate_md5(module_name))
            except:
                module_id = None

            if module_id is not None:            
                self.added_modules.pop(module_id)
                self.modules_def.pop(module_id)

    '''
    TODO: Add commnets
    '''
    def update_module(
            self,
            module_name: str = "",
            module: dict = {}
        ):

        module_def = self.get_module(module_name)
        
        if module_def:
            if "name" not in module:
                module["name"] = module_name

            module_def.update(module)

            self.del_module(module_name)
            self.add_module(module_def)

    '''
    TODO: Add commnets
    '''
    def get_module(
            self,
            module_name: str = ""
        ) -> dict:

        if len(module_name.strip()) > 0:
            try:
                module_id = self.added_modules.index(generate_md5(module_name))
            except:
                module_id = None

            if module_id is not None:            
                return self.modules_def[module_id]
            else:
                return {}

    '''
    TODO: Add commnets
    '''
    def get_modules_def(
            self
        ) -> dict:

        return self.modules_def

    '''
    TODO: Add commnets
    '''
    def add_log_module(
            self,
            log_module: dict = {}
        ):

        if "source" in module and type(module["source"]) == str and len(module["source"].strip()) > 0:
            self.log_modules_def.append(init_log_module(log_module))

    '''
    TODO: Add commnets
    '''
    def get_log_modules_def(
            self
        ) -> dict:

        return self.log_modules_def

    '''
    TODO: Add commnets
    '''
    def print_xml(
            self,
            print_flag: bool = False
        ) -> str:

        return print_agent(self.get_config(), self.get_modules_def(), self.get_log_modules_def(), print_flag)

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
        "group"             : GLOBAL_VARIABLES['agents_group_name'],
        "interval"          : GLOBAL_VARIABLES['interval'],
        "agent_mode"        : "1"
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
