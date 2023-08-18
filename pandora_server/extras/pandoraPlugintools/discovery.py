import sys
import json

####
# Define some global variables
#########################################################################################

_ERROR_LEVEL = 0
_SUMMARY = {}
_INFO = ""
_MONITORING_DATA = []

####
# Internal: Alias for output.print_debug function
#########################################################################################

def _print_debug(
        var = "",
        print_errors: bool = False
    ):
    """
    Print the variable as a JSON-like representation for debugging purposes.

    Args:
        var (any): The variable to be printed.
        print_errors (bool): A flag indicating whether to print errors during debugging.
    """
    from .output import print_debug
    print_debug(var, print_errors)

####
# Set error level to value
#########################################################################################
def set_disco_error_level(
        value: int = 0
    )-> None:
    """
    Sets the error level to the specified value.

    Args:
        value (int, optional): The error level value. Default is 0.
    """
    global _ERROR_LEVEL

    _ERROR_LEVEL = value

####
# Set fixed value to summary dict
#########################################################################################
def set_disco_summary(
        data: dict = {}
    )-> None:
    """
    Sets the disk summary data in the internal summary dictionary.

    This function updates the summary dictionary with the provided disk summary data.
    
    Args:
        data (dict): A dictionary containing disk summary data.

    Returns:
        None
    """
    global _SUMMARY

    _SUMMARY = {}

####
# Set fixed value to summary key
#########################################################################################
def set_disco_summary_value(
        key: str = "",
        value = None
    )-> None:
    """
    Sets a fixed value for a key in the '_SUMMARY' dictionary.

    Args:
        key (str): Key to set the value for.
        value (any): Value to assign to the key.

    Returns:
        None
    """
    global _SUMMARY

    _SUMMARY[key] = value

####
# Add value to summary key
#########################################################################################
def add_disco_summary_value(
        key: str = "",
        value = None
    )-> None:
    """
    Adds a value to a key in the 'SUMMARY' dictionary.

    If the key already exists, the value will be incremented. Otherwise, a new key will be created.

    Args:
        key (str): Key to add the value to.
        value (any): Value to add to the key.

    Returns:
        None
    """
    global _SUMMARY

    if key in _SUMMARY:
        _SUMMARY[key] += value
    else:
        set_disco_summary_value(key, value)

####
# Set fixed value to info
#########################################################################################
def set_disco_info_value(
        value: str = ""
    )-> None:
    """
    Sets a fixed value to the '_INFO' variable.

    Args:
        data (str, optional): The value to set in the '_INFO' variable. Default is an empty string.

    Returns:
        None
    """
    global _INFO

    _INFO = value

####
# Add data to info
#########################################################################################
def add_disco_info_value(
        value: str = ""
    )-> None:
    """
    Adds data to the '_INFO' variable.

    Args:
        data (str, optional): The data to add to the '_INFO' variable. Default is an empty string.
    
    Returns:
        None
    """
    global _INFO

    _INFO += value

####
# Set fixed value to monitoring data
#########################################################################################
def set_disco_monitoring_data(
        data: list = []
    )-> None:
    """
    Set the monitoring data for disk usage.

    Args:
        data (list): A list containing disk monitoring data.

    Returns:
        None
    """
    global _MONITORING_DATA

    _MONITORING_DATA = data

####
# Add value to monitoring data
#########################################################################################
def add_disco_monitoring_data(
        data: dict = {}
    )-> None:
    """
    Add disk monitoring data to the global monitoring dataset.

    Args:
        data (dict): A dictionary containing disk monitoring data.

    Returns:
        None
    """
    global _MONITORING_DATA

    _MONITORING_DATA.append(data)

####
# Print JSON output and exit script
#########################################################################################
def disco_output()-> None:
    """
    Prints the JSON output and exits the script.

    The function uses the global variables '_ERROR_LEVEL', '_SUMMARY', '_INFO' and '_MONITORING_DATA'
    to create the JSON output. It then prints the JSON string and exits the script with
    the '_ERROR_LEVEL' as the exit code.

    Returns:
        None
    """
    from .output import print_stdout

    global _ERROR_LEVEL
    global _SUMMARY
    global _INFO
    global _MONITORING_DATA

    output={}
    if _SUMMARY:
        output["summary"] = _SUMMARY

    if _INFO:
        output["info"] = _INFO

    if _MONITORING_DATA:
        output["monitoring_data"] = _MONITORING_DATA
    
    json_string = json.dumps(output)

    print_stdout(json_string)
    sys.exit(_ERROR_LEVEL)
