import sys
import json

####
# Set fixed value to summary key
###########################################
def set_summary_value(
        key="",
        value=""
    ):
    global summary

    summary[key] = value

####
# Add value to summary key
###########################################
def add_summary_value(
        key="",
        value=""
    ):
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
    global error_level

    error_level = value

####
# Add data to info
###########################################
def add_info_value(
        data=""
    ):
    global info

    info += data

####
# Set fixed value to info
###########################################
def set_info_value(
        data=""
    ):
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
    try:
        return int(param)
    except:
        return 0

####
# Print JSON output and exit script
###########################################
def print_output():

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
