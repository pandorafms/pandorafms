import sys
import json

####
# Set fixed value to summary key
###########################################
def set_summary_value(
        key="",
        value=""
    ):
    """
    Sets a fixed value for a key in the 'summary' dictionary.

    Args:
        key (str): Key to set the value for.
        value (any): Value to assign to the key.
    """
    global summary

    summary[key] = value

####
# Add value to summary key
###########################################
def add_summary_value(
        key="",
        value=""
    ):
    """
    Adds a value to a key in the 'summary' dictionary.

    If the key already exists, the value will be incremented. Otherwise, a new key will be created.

    Args:
        key (str): Key to add the value to.
        value (any): Value to add to the key.
    """
    global summary

    if key in summary:
        summary[key] += value
    else:
        set_summary_value(key, value)

####
# Set error level to value
###########################################
def set_error_level(
        value=0
    ):
    """
    Sets the error level to the specified value.

    Args:
        value (int, optional): The error level value. Default is 0.
    """
    global error_level

    error_level = value

####
# Add data to info
###########################################
def add_info_value(
        data=""
    ):
    """
    Adds data to the 'info' variable.

    Args:
        data (str, optional): The data to add to the 'info' variable. Default is an empty string.
    """
    global info

    info += data

####
# Set fixed value to info
###########################################
def set_info_value(
        data=""
    ):
    """
    Sets a fixed value to the 'info' variable.

    Args:
        data (str, optional): The value to set in the 'info' variable. Default is an empty string.
    """
    global info

    info = data

####
# Parse parameters from configuration file
###########################################
def parse_parameter(
        config=None,
        default="",
        key=""
    ):
    """
    Parses a parameter from the configuration file.

    Args:
        config (ConfigParser, optional): The ConfigParser object representing the configuration file. Default is None.
        default (any, optional): The default value to return if the parameter is not found. Default is an empty string.
        key (str): The key of the parameter to parse.

    Returns:
        any: The parsed value of the parameter, or the default value if the parameter is not found.
    """

    try:
        return config.get("CONF", key)
    except Exception as e:
        return default
    
####
# Parse configuration file credentials
###########################################
def parse_conf_entities(
        entities=""
    ):
    """
    Parses the configuration file credentials.

    Args:
        entities (str): A JSON string representing the entities.

    Returns:
        list: A list of entities parsed from the JSON string. If parsing fails, an empty list is returned.
    """
    entities_list = []

    try:
        parsed_entities = json.loads(entities)
        if isinstance(parsed_entities, list):
            entities_list = parsed_entities
    
    except Exception as e:
        set_error_level(1)
        add_info_value("Error while parsing configuration zones or instances: "+str(e)+"\n")

    return entities_list

    
####
# Parse parameter input (int)
###########################################
def param_int(
        param=""
    ):
    """
    Parses a parameter as an integer.

    Args:
        param (any): The parameter to be parsed as an integer.

    Returns:
        int: The parsed integer value. If parsing fails, returns 0.
    """
    try:
        return int(param)
    except:
        return 0

####
# Print JSON output and exit script
###########################################
def print_output():
    """
    Prints the JSON output and exits the script.

    The function uses the global variables 'output', 'error_level', 'summary', and 'info'
    to create the JSON output. It then prints the JSON string and exits the script with
    the 'error_level' as the exit code.
    """

    global output
    global error_level
    global summary
    global info

    output={}
    if summary:
        output["summary"] = summary

    if info:
        output["info"] = info
    
    json_string = json.dumps(output)

    print(json_string)
    sys.exit(error_level)
