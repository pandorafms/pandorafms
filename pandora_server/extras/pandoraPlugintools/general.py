import sys
import json
from datetime import datetime
import hashlib


####
# Prints dictionary in formatted json string.
#########################################################################################

def debug_dict(
        jsontxt = ""
    ):
    """
    Prints any list, dict, string, float or integer as a json
    """
    try:
        debug_json = json.dumps(jsontxt, indent=4)
        print (debug_json)
    except json.JSONDecodeError as e:
        print(f"debug_dict: Failed to dump. Error: {e}")
    except Exception as e:
        print(f"debug_dict: Unexpected error: {e}")

####
# Assign to a key in a dict a given value.
#########################################################################################

def set_dict_key_value(
        input_dict: dict = {},
        input_key: str = "",
        input_value = None
    ):
    """
    Assign to a key in a dict a given value
    """
    key = input_key.strip()

    if len(key) > 0:
        input_dict[key] = input_value

####
# Return MD5 hash string.
#########################################################################################

def generate_md5(
        input_string: str = ""
    ) -> str:
    """
    Generates an MD5 hash for the given input string.

    Args:
        input_string (str): The string for which the MD5 hash will be generated.

    Returns:
        str: The MD5 hash of the input string as a hexadecimal string.
    """
    try:
        md5_hash = hashlib.md5(input_string.encode()).hexdigest()
    except:
        md5_hash = ""

    return md5_hash

####
# Returns or print current time in date format or utimestamp.
#########################################################################################

def now(
        print_flag: int = 0,
        utimestamp: int = 0
    ) -> str:
    """
    Returns time in yyyy/mm/dd HH:MM:SS format by default. Use 1 as an argument
    to get epoch time (utimestamp)
    """
    today = datetime.today()
    
    if utimestamp:
        time = datetime.timestamp(today)
    else:
        time = today.strftime('%Y/%m/%d %H:%M:%S')

    if print_flag:
        print(time)

    return time

####
# Translate macros in string from a dict.
#########################################################################################
def translate_macros(
        macro_dic: dict = {},
        data: str = ""
    ) -> str:
    """
    Expects a macro dictionary key:value (macro_name:macro_value) 
    and a string to replace macro.
    
    It will replace the macro_name for the macro_value in any string.
    """
    for macro_name, macro_value in macro_dic.items():
        data = data.replace(macro_name, macro_value) 

    return data


####
# Parse configuration file line by line based on separator and return dict.
#########################################################################################

def parse_configuration(
        file: str = "/etc/pandora/pandora_server.conf",
        separator: str = " ",
        default_values: dict = {}
    ) -> dict:
    """
    Parse configuration. Reads configuration file and stores its data as dict.

    Args:
    - file (str): configuration file path. Defaults to "/etc/pandora/pandora_server.conf". \n
    - separator (str, optional): Separator for option and value. Defaults to " ".

    Returns:
    - dict: containing all keys and values from file.
    """
    config = {}
    
    try:
        with open (file, "r") as conf:
            lines = conf.read().splitlines()
            for line in lines:
                if line.strip().startswith("#") or len(line.strip()) < 1 :
                    continue
                else:
                    option, value = line.strip().split(separator, maxsplit=1)
                    config[option.strip()] = value.strip()

    except Exception as e:
        print (f"{type(e).__name__}: {e}")
    
    for option, value in default_values.items():
        if option.strip() not in config:
            config[option.strip()] = value.strip()

    return config

####
# Parse csv file line by line and return list.
#########################################################################################

def parse_csv_file(
        file: str = "",
        separator: str = ';',
        count_parameters: int = 0,
        debug: bool = False
    ) -> list:
    """
    Parse csv configuration. Reads configuration file and stores its data in a list.

    Args:
    - file (str): configuration csv file path. \n
    - separator (str, optional): Separator for option and value. Defaults to ";".
    - coun_parameters (int): min number of parameters each line shold have. Default None
    - debug (bool): print errors on lines

    Returns:
    - List: containing a list for of values for each csv line.
    """
    csv_arr = []
    
    try:
        with open (file, "r") as csv:
            lines = csv.read().splitlines()
            for line in lines:
                if line.strip().startswith("#") or len(line.strip()) < 1 :
                    continue
                else:
                    value = line.strip().split(separator)
                    if len(value) >= count_parameters:
                        csv_arr.append(value)
                    elif debug==True: 
                        print(f'Csv line: {line} does not match minimun parameter defined: {count_parameters}',file=sys.stderr)

    except Exception as e:
        print (f"{type(e).__name__}: {e}")

    return csv_arr

####
# Parse given variable to integer.
#########################################################################################

def parse_int(
        var = None
    ) -> int:
    """
    Parse given variable to integer.

    Args:
        var (any): The variable to be parsed as an integer.

    Returns:
        int: The parsed integer value. If parsing fails, returns 0.
    """
    try:
        return int(var)
    except:
        return 0

####
# Parse given variable to float.
#########################################################################################

def parse_float(
        var = None
    ) -> float:
    """
    Parse given variable to float.

    Args:
        var (any): The variable to be parsed as an float.

    Returns:
        float: The parsed float value. If parsing fails, returns 0.
    """
    try:
        return float(var)
    except:
        return 0

####
# Parse given variable to string.
#########################################################################################

def parse_str(
        var = None
    ) -> str:
    """
    Parse given variable to string.

    Args:
        var (any): The variable to be parsed as an string.

    Returns:
        str: The parsed string value. If parsing fails, returns "".
    """
    try:
        return str(var)
    except:
        return ""

####
# Parse given variable to bool.
#########################################################################################

def parse_bool(
        var = None
    ) -> bool:
    """
    Parse given variable to bool.

    Args:
        var (any): The variable to be parsed as an bool.

    Returns:
        bool: The parsed bool value. If parsing fails, returns False.
    """
    try:
        return bool(var)
    except:
        return False