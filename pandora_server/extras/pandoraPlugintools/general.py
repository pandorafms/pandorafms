import sys
import os
import json
from datetime import datetime
import hashlib


#########################################################################################
# Debug_dict: prints dictionary in formatted json string.
#########################################################################################

class debug_dict:
    def __init__ (
            self,
            jsontxt
        ):
        self.debug_json = json.dumps (jsontxt, indent=4)
        print (self.debug_json)

#########################################################################################
# Timedate class
#########################################################################################

#class Timedate:
def now(
        print_flag=None,
        utimestamp=None
    ):
    """Returns time in yyyy/mm/dd HH:MM:SS format by default. Use 1 as an argument
    to get epoch time (utimestamp)"""
    if utimestamp:
        time = datetime.timestamp(datetime.today())
    else:
        time = datetime.today().strftime('%Y/%m/%d %H:%M:%S')
    if print_flag:
        print (time)
    else:
        return (time)

#########################################################################################
# Translate macro
#########################################################################################
def translate_macros(
        macro_dic: dict,
        data: str
    )  -> str:
    """Expects a macro dictionary key:value (macro_name:macro_value) 
    and a string to replace macro. \n
    It will replace the macro_name for the macro_value in any string.
    """
    for macro_name, macro_value in macro_dic.items():
        data = data.replace(macro_name, macro_value) 

    return data


#########################################################################################
# Configuration file parser
#########################################################################################

def parse_configuration(
        file="/etc/pandora/pandora_server.conf",
        separator=" "
    ):
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
                if line.startswith("#") or len(line) < 1 :
                    pass
                else:
                    option, value = line.strip().split(separator)
                    config[option.strip()] = value.strip()

        return config
    except Exception as e:
        print (f"{type(e).__name__}: {e}")

#########################################################################################
# csv file parser
#########################################################################################
def parse_csv_file(
        file, separator=';',
        count_parameters=None,
        debug=False
    ) -> list:
    """
    Parse csv configuration. Reads configuration file and stores its data in an array.

    Args:
    - file (str): configuration csv file path. \n
    - separator (str, optional): Separator for option and value. Defaults to ";".
    - coun_parameters (int): min number of parameters each line shold have. Default None
    - debug: print errors on lines

    Returns:
    - List: containing a list for of values for each csv line.
    """
    csv_arr = []
    try:
        with open (file, "r") as conf:
            lines = conf.read().splitlines()
            for line in lines:
                if line.startswith("#") or len(line) < 1 :
                    continue
                else:
                    value = line.strip().split(separator)
                    if count_parameters is None or len(value) >= count_parameters:
                        csv_arr.append(value)
                    elif debug==True: 
                        print(f'Csv line: {line} doesnt match minimun parameter defined: {count_parameters}',file=sys.stderr)

        return csv_arr
    except Exception as e:
        print (f"{type(e).__name__}: {e}")
        return 1


#########################################################################################
# md5 generator
#########################################################################################
def generate_md5(input_string):
    """
    Generates an MD5 hash for the given input string.

    Args:
        input_string (str): The string for which the MD5 hash will be generated.

    Returns:
        str: The MD5 hash of the input string as a hexadecimal string.
    """
    md5_hash = hashlib.md5(input_string.encode()).hexdigest()
    return md5_hash