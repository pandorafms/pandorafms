import sys
import json

####
# Define some global variables
#########################################################################################

output = {}
error_level = 0
summary = {}
info = ""
monitoring_data = []

####
# Set fixed value to summary key
#########################################################################################
def set_summary_value(
        key: str = "",
        value = ""
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
#########################################################################################
def add_summary_value(
        key: str = "",
        value = ""
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
#########################################################################################
def set_error_level(
        value: int = 0
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
#########################################################################################
def add_info_value(
        data: str = ""
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
#########################################################################################
def set_info_value(
        data: str = ""
    ):
    """
    Sets a fixed value to the 'info' variable.

    Args:
        data (str, optional): The value to set in the 'info' variable. Default is an empty string.
    """
    global info

    info = data

####
# Set fixed value to info
#########################################################################################
def add_monitoring_data(
        data: dict = {}
    ):
    """
    TODO: Add comments
    """
    global monitoring_data

    monitoring_data.append(data)

####
# Print JSON output and exit script
#########################################################################################
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
    global monitoring_data

    output={}
    if summary:
        output["summary"] = summary

    if info:
        output["info"] = info

    if monitoring_data:
        output["monitoring_data"] = monitoring_data
    
    json_string = json.dumps(output)

    print(json_string)
    sys.exit(error_level)
