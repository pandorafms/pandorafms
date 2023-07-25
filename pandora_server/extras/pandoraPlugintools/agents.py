from datetime import datetime
from subprocess import *
import hashlib
import sys

global_variables = {
    'temporal'         : '/tmp',
    'agents_group_name': '',
    'interval'         : 300
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

