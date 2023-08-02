import sys
import json

####
# Define some global variables
#########################################################################################

ERROR_LEVEL = 0
SUMMARY = {}
INFO = ""
MONITORING_DATA = []

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
    global ERROR_LEVEL

    ERROR_LEVEL = value

####
# Set fixed value to summary key
#########################################################################################
def set_summary_value(
        key: str = "",
        value = ""
    ):
    """
    Sets a fixed value for a key in the 'SUMMARY' dictionary.

    Args:
        key (str): Key to set the value for.
        value (any): Value to assign to the key.
    """
    global SUMMARY

    SUMMARY[key] = value

####
# Add value to summary key
#########################################################################################
def add_summary_value(
        key: str = "",
        value = ""
    ):
    """
    Adds a value to a key in the 'SUMMARY' dictionary.

    If the key already exists, the value will be incremented. Otherwise, a new key will be created.

    Args:
        key (str): Key to add the value to.
        value (any): Value to add to the key.
    """
    global SUMMARY

    if key in SUMMARY:
        SUMMARY[key] += value
    else:
        set_summary_value(key, value)

####
# Set fixed value to info
#########################################################################################
def set_info_value(
        value: str = ""
    ):
    """
    Sets a fixed value to the 'INFO' variable.

    Args:
        data (str, optional): The value to set in the 'INFO' variable. Default is an empty string.
    """
    global INFO

    INFO = value

####
# Add data to info
#########################################################################################
def add_info_value(
        value: str = ""
    ):
    """
    Adds data to the 'INFO' variable.

    Args:
        data (str, optional): The data to add to the 'INFO' variable. Default is an empty string.
    """
    global INFO

    INFO += value

####
# Set fixed value to monitoring data
#########################################################################################
def set_monitoring_data(
        data: list = []
    ):
    """
    TODO: Add comments
    """
    global MONITORING_DATA

    MONITORING_DATA = data

####
# Add value to monitoring data
#########################################################################################
def add_monitoring_data(
        data: dict = {}
    ):
    """
    TODO: Add comments
    """
    global MONITORING_DATA

    MONITORING_DATA.append(data)

####
# Print JSON output and exit script
#########################################################################################
def print_output():
    """
    Prints the JSON output and exits the script.

    The function uses the global variables 'ERROR_LEVEL', 'SUMMARY', and 'info'
    to create the JSON output. It then prints the JSON string and exits the script with
    the 'ERROR_LEVEL' as the exit code.
    """

    global ERROR_LEVEL
    global SUMMARY
    global INFO
    global MONITORING_DATA

    OUTPUT={}
    if SUMMARY:
        OUTPUT["summary"] = SUMMARY

    if INFO:
        OUTPUT["info"] = INFO

    if MONITORING_DATA:
        OUTPUT["monitoring_data"] = MONITORING_DATA
    
    json_string = json.dumps(OUTPUT)

    print(json_string)
    sys.exit(ERROR_LEVEL)
