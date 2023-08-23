####
# Internal: Alias for output.print_debug function
#########################################################################################

def _print_debug(
        var = "",
        print_errors: bool = False
    ):
    """
    Print the provided variable as JSON format, supporting various data types.

    Args:
        var (any, optional): The variable to be printed as JSON. Defaults to an empty string.
        print_errors (bool, optional): Set to True to print errors encountered during printing. Defaults to False.
    """
    from .output import print_debug
    print_debug(var, print_errors)

####
# Init module template
#########################################################################################
def init_module(
        default_values: dict = {}
    ) -> dict:
    """
    Initializes a module template with default values.

    Args:
        default_values (dict, optional): Dictionary containing default values to override template values. Defaults to an empty dictionary.

    Returns:
        dict: Dictionary representing the module template with default values.
    """
    module = {
        "name"                  : None,
        "type"                  : "generic_data_string",
        "value"                 : "0",
        "desc"                  : "",
        "unit"                  : "",
        "interval"              : "",
        "tags"                  : "",
        "module_group"          : "",
        "module_parent"         : "",
        "min_warning"           : "",
        "min_warning_forced"    : "",
        "max_warning"           : "",
        "max_warning_forced"    : "",
        "min_critical"          : "",
        "min_critical_forced"   : "",
        "max_critical"          : "",
        "max_critical_forced"   : "",
        "str_warning"           : "",
        "str_warning_forced"    : "",
        "str_critical"          : "",
        "str_critical_forced"   : "",
        "critical_inverse"      : "",
        "warning_inverse"       : "",
        "max"                   : "",
        "min"                   : "",
        "post_process"          : "",
        "disabled"              : "",
        "min_ff_event"          : "",
        "status"                : "",
        "timestamp"             : "",
        "custom_id"             : "",
        "critical_instructions" : "",
        "warning_instructions"  : "",
        "unknown_instructions"  : "",
        "quiet"                 : "",
        "module_ff_interval"    : "",
        "crontab"               : "",
        "min_ff_event_normal"   : "",
        "min_ff_event_warning"  : "",
        "min_ff_event_critical" : "",
        "ff_type"               : "",
        "ff_timeout"            : "",
        "each_ff"               : "",
        "module_parent_unlink"  : "",
        "alert"                 : []
    }

    for key, value in default_values.items():
        if key in module:
            module[key] = value

    return module

####
# Returns module in XML format.  Accepts only {dict}
#########################################################################################
def print_module(
        module: dict = None, 
        print_flag: bool = False
    ) -> str:
    """
    Returns module in XML format. Accepts only {dict}.
    
    Args:
        module (dict, optional): Dictionary containing module data. Defaults to None.
        print_flag (bool, optional): Flag to print the module XML to STDOUT. Defaults to False.
    
    Returns:
        str: Module data in XML format.
    """
    from .output import print_stdout

    module_xml = ""

    if module is not None:
        data = dict(module)
        module_xml = ("<module>\n"
                      "\t<name><![CDATA[" + str(data["name"]) + "]]></name>\n"
                      "\t<type>" + str(data["type"]) + "</type>\n"
                     )
        
        if type(data["type"]) is not str and "string" not in data["type"]: #### Strip spaces if module not generic_data_string
            data["value"] = data["value"].strip()
        
        if isinstance(data["value"], list): # Checks if value is a list
            module_xml += "\t<datalist>\n"
            for value in data["value"]:
                if type(value) is dict and "value" in value:
                    module_xml += "\t<data>\n"
                    module_xml += "\t\t<value><![CDATA[" + str(value["value"]) + "]]></value>\n"
                    if "timestamp" in value:
                        module_xml += "\t\t<timestamp><![CDATA[" + str(value["timestamp"]) + "]]></timestamp>\n"
                module_xml += "\t</data>\n"
            module_xml += "\t</datalist>\n"
        else:
            module_xml += "\t<data><![CDATA[" + str(data["value"]) + "]]></data>\n"
        
        if "desc" in data and len(str(data["desc"]).strip()) > 0:
            module_xml += "\t<description><![CDATA[" + str(data["desc"]) + "]]></description>\n"
        
        if "unit" in data and len(str(data["unit"]).strip()) > 0:
            module_xml += "\t<unit><![CDATA[" + str(data["unit"]) + "]]></unit>\n"
        
        if "interval" in data and len(str(data["interval"]).strip()) > 0:
            module_xml += "\t<module_interval><![CDATA[" + str(data["interval"]) + "]]></module_interval>\n"
        
        if "tags" in data and len(str(data["tags"]).strip()) > 0:
            module_xml += "\t<tags>" + str(data["tags"]) + "</tags>\n"
        
        if "module_group" in data and len(str(data["module_group"]).strip()) > 0:
            module_xml += "\t<module_group>" + str(data["module_group"]) + "</module_group>\n"
        
        if "module_parent" in data and len(str(data["module_parent"]).strip()) > 0:
            module_xml += "\t<module_parent>" + str(data["module_parent"]) + "</module_parent>\n"
        
        if "min_warning" in data and len(str(data["min_warning"]).strip()) > 0:
            module_xml += "\t<min_warning><![CDATA[" + str(data["min_warning"]) + "]]></min_warning>\n"
        
        if "min_warning_forced" in data and len(str(data["min_warning_forced"]).strip()) > 0:
            module_xml += "\t<min_warning_forced><![CDATA[" + str(data["min_warning_forced"]) + "]]></min_warning_forced>\n"
        
        if "max_warning" in data and len(str(data["max_warning"]).strip()) > 0:
            module_xml += "\t<max_warning><![CDATA[" + str(data["max_warning"]) + "]]></max_warning>\n"
        
        if "max_warning_forced" in data and len(str(data["max_warning_forced"]).strip()) > 0:
            module_xml += "\t<max_warning_forced><![CDATA[" + str(data["max_warning_forced"]) + "]]></max_warning_forced>\n"
        
        if "min_critical" in data and len(str(data["min_critical"]).strip()) > 0:
            module_xml += "\t<min_critical><![CDATA[" + str(data["min_critical"]) + "]]></min_critical>\n"
        
        if "min_critical_forced" in data and len(str(data["min_critical_forced"]).strip()) > 0:
            module_xml += "\t<min_critical_forced><![CDATA[" + str(data["min_critical_forced"]) + "]]></min_critical_forced>\n"
        
        if "max_critical" in data and len(str(data["max_critical"]).strip()) > 0:
            module_xml += "\t<max_critical><![CDATA[" + str(data["max_critical"]) + "]]></max_critical>\n"
        
        if "max_critical_forced" in data and len(str(data["max_critical_forced"]).strip()) > 0:
            module_xml += "\t<max_critical_forced><![CDATA[" + str(data["max_critical_forced"]) + "]]></max_critical_forced>\n"
        
        if "str_warning" in data and len(str(data["str_warning"]).strip()) > 0:
            module_xml += "\t<str_warning><![CDATA[" + str(data["str_warning"]) + "]]></str_warning>\n"
        
        if "str_warning_forced" in data and len(str(data["str_warning_forced"]).strip()) > 0:
            module_xml += "\t<str_warning_forced><![CDATA[" + str(data["str_warning_forced"]) + "]]></str_warning_forced>\n"
        
        if "str_critical" in data and len(str(data["str_critical"]).strip()) > 0:
            module_xml += "\t<str_critical><![CDATA[" + str(data["str_critical"]) + "]]></str_critical>\n"
        
        if "str_critical_forced" in data and len(str(data["str_critical_forced"]).strip()) > 0:
            module_xml += "\t<str_critical_forced><![CDATA[" + str(data["str_critical_forced"]) + "]]></str_critical_forced>\n"
        
        if "critical_inverse" in data and len(str(data["critical_inverse"]).strip()) > 0:
            module_xml += "\t<critical_inverse><![CDATA[" + str(data["critical_inverse"]) + "]]></critical_inverse>\n"
        
        if "warning_inverse" in data and len(str(data["warning_inverse"]).strip()) > 0:
            module_xml += "\t<warning_inverse><![CDATA[" + str(data["warning_inverse"]) + "]]></warning_inverse>\n"
        
        if "max" in data and len(str(data["max"]).strip()) > 0:
            module_xml += "\t<max><![CDATA[" + str(data["max"]) + "]]></max>\n"
        
        if "min" in data and len(str(data["min"]).strip()) > 0:
            module_xml += "\t<min><![CDATA[" + str(data["min"]) + "]]></min>\n"
        
        if "post_process" in data and len(str(data["post_process"]).strip()) > 0:
            module_xml += "\t<post_process><![CDATA[" + str(data["post_process"]) + "]]></post_process>\n"
        
        if "disabled" in data and len(str(data["disabled"]).strip()) > 0:
            module_xml += "\t<disabled><![CDATA[" + str(data["disabled"]) + "]]></disabled>\n"
        
        if "min_ff_event" in data and len(str(data["min_ff_event"]).strip()) > 0:
            module_xml += "\t<min_ff_event><![CDATA[" + str(data["min_ff_event"]) + "]]></min_ff_event>\n"
        
        if "status" in data and len(str(data["status"]).strip()) > 0:
            module_xml += "\t<status><![CDATA[" + str(data["status"]) + "]]></status>\n"
        
        if "timestamp" in data and len(str(data["timestamp"]).strip()) > 0:
            module_xml += "\t<timestamp><![CDATA[" + str(data["timestamp"]) + "]]></timestamp>\n"
        
        if "custom_id" in data and len(str(data["custom_id"]).strip()) > 0:
            module_xml += "\t<custom_id><![CDATA[" + str(data["custom_id"]) + "]]></custom_id>\n"
        
        if "critical_instructions" in data and len(str(data["critical_instructions"]).strip()) > 0:
            module_xml += "\t<critical_instructions><![CDATA[" + str(data["critical_instructions"]) + "]]></critical_instructions>\n"
        
        if "warning_instructions" in data and len(str(data["warning_instructions"]).strip()) > 0:
            module_xml += "\t<warning_instructions><![CDATA[" + str(data["warning_instructions"]) + "]]></warning_instructions>\n"
        
        if "unknown_instructions" in data and len(str(data["unknown_instructions"]).strip()) > 0:
            module_xml += "\t<unknown_instructions><![CDATA[" + str(data["unknown_instructions"]) + "]]></unknown_instructions>\n"
        
        if "quiet" in data and len(str(data["quiet"]).strip()) > 0:
            module_xml += "\t<quiet><![CDATA[" + str(data["quiet"]) + "]]></quiet>\n"
        
        if "module_ff_interval" in data and len(str(data["module_ff_interval"]).strip()) > 0:
            module_xml += "\t<module_ff_interval><![CDATA[" + str(data["module_ff_interval"]) + "]]></module_ff_interval>\n"
        
        if "crontab" in data and len(str(data["crontab"]).strip()) > 0:
            module_xml += "\t<crontab><![CDATA[" + str(data["crontab"]) + "]]></crontab>\n"
        
        if "min_ff_event_normal" in data and len(str(data["min_ff_event_normal"]).strip()) > 0:
            module_xml += "\t<min_ff_event_normal><![CDATA[" + str(data["min_ff_event_normal"]) + "]]></min_ff_event_normal>\n"
        
        if "min_ff_event_warning" in data and len(str(data["min_ff_event_warning"]).strip()) > 0:
            module_xml += "\t<min_ff_event_warning><![CDATA[" + str(data["min_ff_event_warning"]) + "]]></min_ff_event_warning>\n"
        
        if "min_ff_event_critical" in data and len(str(data["min_ff_event_critical"]).strip()) > 0:
            module_xml += "\t<min_ff_event_critical><![CDATA[" + str(data["min_ff_event_critical"]) + "]]></min_ff_event_critical>\n"
        
        if "ff_type" in data and len(str(data["ff_type"]).strip()) > 0:
            module_xml += "\t<ff_type><![CDATA[" + str(data["ff_type"]) + "]]></ff_type>\n"
        
        if "ff_timeout" in data and len(str(data["ff_timeout"]).strip()) > 0:
            module_xml += "\t<ff_timeout><![CDATA[" + str(data["ff_timeout"]) + "]]></ff_timeout>\n"
        
        if "each_ff" in data and len(str(data["each_ff"]).strip()) > 0:
            module_xml += "\t<each_ff><![CDATA[" + str(data["each_ff"]) + "]]></each_ff>\n"
        
        if "module_parent_unlink" in data and len(str(data["module_parent_unlink"]).strip()) > 0:
            module_xml += "\t<module_parent_unlink><![CDATA[" + str(data["module_parent_unlink"]) + "]]></module_parent_unlink>\n"
        
        if "alert" in data:
            for alert in data["alert"]:
                if len(str(alert).strip()) > 0:
                    module_xml += "\t<alert_template><![CDATA[" + str(alert) + "]]></alert_template>\n"
        module_xml += "</module>\n"

    if print_flag:
        print_stdout(module_xml)

    return module_xml

####
# Init log module template
#########################################################################################
def init_log_module(
        default_values: dict = {}
    ) -> dict:
    """
    Initializes a log module template with default values.

    Args:
        default_values (dict, optional): Default values to initialize the log module with. Defaults to an empty dictionary.

    Returns:
        dict: Dictionary representing the log module template with default values.
    """
    module = {
        "source" : None,
        "value"  : ""
    }

    for key, value in default_values.items():
        if key in module:
            module[key] = value

    return module

####
# Returns log module in XML format.  Accepts only {dict}
#########################################################################################

def print_log_module(
        module: dict = None,
        print_flag: bool = False
    ) -> str:
    """
    Returns log module in XML format. Accepts only {dict}.
    - Only works with one module at a time: otherwise iteration is needed.
    - Module "value" field accepts str type.
    - Use not_print_flag to avoid printing the XML (only populates variables).

    Args:
        module (dict, optional): Dictionary representing the log module. Defaults to None.
        print_flag (bool, optional): Flag to indicate whether to print the XML. Defaults to False.

    Returns:
        str: XML representation of the log module.
    """

    from .output import print_stdout

    module_xml = ""

    if module is not None:
        data = dict(module)
        module_xml = ("<log_module>\n"
                      "\t<source><![CDATA[" + str(data["source"]) + "]]></source>\n"
                      "\t<data>\"" + str(data["value"]) + "\"</data>\n"
                     )
        
        module_xml += "</log_module>\n"

    if print_flag:
        print_stdout(module_xml)

    return module_xml
