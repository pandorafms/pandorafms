import sys
import os

####
# Define global variables dict, used in functions as default values.
# Its values can be changed.
#########################################################################################

_GLOBAL_VARIABLES = {
    'agents_group_name' : '',
    'interval'          : 300
}

####
# Define some global variables
#########################################################################################

_WINDOWS = os.name == "nt" or os.name == "ce"
_LINUX = sys.platform.startswith("linux")
_MACOS = sys.platform.startswith("darwin")
_OSX = _MACOS  # deprecated alias
_FREEBSD = sys.platform.startswith("freebsd")
_OPENBSD = sys.platform.startswith("openbsd")
_NETBSD = sys.platform.startswith("netbsd")
_BSD = _FREEBSD or _OPENBSD or _NETBSD
_SUNOS = sys.platform.startswith(("sunos", "solaris"))
_AIX = sys.platform.startswith("aix")

####
# Internal: Alias for output.print_debug function
#########################################################################################

def _print_debug(
        var = "",
        print_errors: bool = False
    ):
    """
    Prints any list, dict, string, float or integer as a json
    """
    from .output import print_debug
    print_debug(var, print_errors)

####
# Set a global variable with the specified name and assigns a value to it.
#########################################################################################
def set_global_variable(
        variable_name: str = "", 
        value = None
    ):
    """
    Sets the value of a global variable in the '_GLOBAL_VARIABLES' dictionary.

    Args:
        variable_name (str): Name of the variable to set.
        value (any): Value to assign to the variable.
    """
    from .general import set_dict_key_value

    set_dict_key_value(_GLOBAL_VARIABLES, variable_name, value)

####
# Get a global variable with the specified name.
#########################################################################################
def get_global_variable(
        variable_name: str = ""
    ):
    """
    Gets the value of a global variable in the '_GLOBAL_VARIABLES' dictionary.

    Args:
        variable_name (str): Name of the variable to set.
    """
    from .general import get_dict_key_value

    get_dict_key_value(_GLOBAL_VARIABLES, variable_name)

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
            config: dict = {},
            modules_def: list = [],
            log_modules_def: list = []
        ):

        self.modules_def = []
        self.added_modules = []
        self.log_modules_def = []

        self.config = init_agent(config)
        
        for module in modules_def:
            self.add_module(module)

        for log_module in log_modules_def:
            self.add_log_module(log_module)

    def update_config(
            self,
            config: dict = {}
        ):
        '''
        TODO: Add commnets
        '''
        for key, value in config.items():
            if key in self.config:
                self.config[key] = value

    def get_config(
            self
        ) -> dict:
        '''
        TODO: Add commnets
        '''
        return self.config

    def add_module(
            self,
            module: dict = {}
        ):
        '''
        TODO: Add commnets
        '''
        from .general import generate_md5
        from .modules import init_module

        if "name" in module and type(module["name"]) == str and len(module["name"].strip()) > 0:
            self.modules_def.append(init_module(module))
            self.added_modules.append(generate_md5(module["name"]))

    def del_module(
            self,
            module_name: str = ""
        ):
        '''
        TODO: Add commnets
        '''
        from .general import generate_md5

        if len(module_name.strip()) > 0:
            try:
                module_id = self.added_modules.index(generate_md5(module_name))
            except:
                module_id = None

            if module_id is not None:            
                self.added_modules.pop(module_id)
                self.modules_def.pop(module_id)

    def update_module(
            self,
            module_name: str = "",
            module: dict = {}
        ):
        '''
        TODO: Add commnets
        '''
        module_def = self.get_module(module_name)
        
        if module_def:
            if "name" not in module:
                module["name"] = module_name

            module_def.update(module)

            self.del_module(module_name)
            self.add_module(module_def)

    def get_module(
            self,
            module_name: str = ""
        ) -> dict:
        '''
        TODO: Add commnets
        '''
        from .general import generate_md5

        if len(module_name.strip()) > 0:
            try:
                module_id = self.added_modules.index(generate_md5(module_name))
            except:
                module_id = None

            if module_id is not None:            
                return self.modules_def[module_id]
            else:
                return {}

    def get_modules_def(
            self
        ) -> dict:
        '''
        TODO: Add commnets
        '''
        return self.modules_def

    def add_log_module(
            self,
            log_module: dict = {}
        ):
        '''
        TODO: Add commnets
        '''
        from .modules import init_log_module

        if "source" in module and type(module["source"]) == str and len(module["source"].strip()) > 0:
            self.log_modules_def.append(init_log_module(log_module))

    def get_log_modules_def(
            self
        ) -> dict:
        '''
        TODO: Add commnets
        '''
        return self.log_modules_def

    def print_xml(
            self,
            print_flag: bool = False
        ) -> str:
        '''
        TODO: Add commnets
        '''
        return print_agent(self.get_config(), self.get_modules_def(), self.get_log_modules_def(), print_flag)

####
# Gets system OS name
#########################################################################################
def get_os() -> str:
    """
    Gets system OS name

    Returns:
        str: OS name.
    """
    os = "Other"

    if _WINDOWS:
        os = "Windows"

    if _LINUX:
        os = "Linux"

    if _MACOS or _OSX:
        os = "MacOS"

    if _FREEBSD or _OPENBSD or _NETBSD or _BSD:
        os = "BSD"

    if _SUNOS:
        os = "Solaris"

    if _AIX:
        os = "AIX"

    return os

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
    from .general import now

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
        "group"             : _GLOBAL_VARIABLES['agents_group_name'],
        "interval"          : _GLOBAL_VARIABLES['interval'],
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
    from .output import print_stdout
    from .modules import print_module,print_log_module

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
        print_stdout(xml)

    return xml
