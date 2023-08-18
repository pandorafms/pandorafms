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
        )-> None:
        '''
        Update the configuration settings with new values.

        Args:
            config (dict): A dictionary containing configuration keys and their new values.

        Returns:
            None
        '''
        for key, value in config.items():
            if key in self.config:
                self.config[key] = value

    def get_config(
            self
        ) -> dict:
        '''
        Retrieve the current configuration settings.

        Returns:
            dict: A dictionary containing the current configuration settings.
        '''
        return self.config

    def add_module(
            self,
            module: dict = {}
        )-> None:
        '''
        Add a new module to the list of modules.

        Args:
            module (dict): A dictionary containing module information.

        Returns:
            None
        '''
        from .general import generate_md5
        from .modules import init_module

        if "name" in module and type(module["name"]) == str and len(module["name"].strip()) > 0:
            self.modules_def.append(init_module(module))
            self.added_modules.append(generate_md5(module["name"]))

    def del_module(
            self,
            module_name: str = ""
        )-> None:
        '''
        Delete a module based on its name.

        Args:
            module_name (str): The name of the module to be deleted.

        Returns:
            None
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
        )-> None:
        '''
        Update a module based on its name.

        Args:
            module_name (str): The name of the module to be updated.
            module (dict): A dictionary containing updated module information.
        
        Returns:
            None
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
        Retrieve module information based on its name.

        Args:
            module_name (str): The name of the module to retrieve.

        Returns:
            dict: A dictionary containing module information if found, otherwise an empty dictionary.
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
        Retrieve the definitions of all added modules.

        Returns:
            dict: A dictionary containing the definitions of all added modules.
        '''
        return self.modules_def

    def add_log_module(
            self,
            log_module: dict = {}
        )-> None:
        '''
        Add a new log module to the list of log modules.

        Args:
            log_module (dict): A dictionary containing log module information.

        Returns:
            None
        '''
        from .modules import init_log_module

        if "source" in log_module and type(log_module["source"]) == str and len(log_module["source"].strip()) > 0:
            self.log_modules_def.append(init_log_module(log_module))

    def get_log_modules_def(
            self
        ) -> dict:
        '''
        Retrieve the definitions of all added log modules.

        Returns:
            dict: A dictionary containing the definitions of all added log modules.
        '''
        return self.log_modules_def

    def print_xml(
            self,
            print_flag: bool = False
        ) -> str:
        '''
        Generate and optionally print the XML representation of the agent.

        Args:
            print_flag (bool): A flag indicating whether to print the XML representation.

        Returns:
            str: The XML representation of the agent.
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
    Initialize an agent template with default values.

    Args:
        default_values (dict): A dictionary containing custom default values for the agent template.

    Returns:
        dict: A dictionary representing the agent template with default and custom values.
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
    Print the XML representation of an agent.

    Args:
        agent (dict): A dictionary containing agent configuration.
        modules (list): A list of dictionaries representing modules.
        log_modules (list): A list of dictionaries representing log modules.
        print_flag (bool): A flag indicating whether to print the XML representation.

    Returns:
        str: The XML representation of the agent.
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
