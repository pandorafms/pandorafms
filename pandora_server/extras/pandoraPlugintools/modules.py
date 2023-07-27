####
# Returns module in XML format.  Accepts only {dict}
#########################################################################################
def print_module(
        module: dict = None, 
        print_flag: bool = False
    ) -> str:
    """
    Returns module in XML format. Accepts only {dict}.
    - Only works with one module at a time: otherwise iteration is needed.
    - Module "value" field accepts str type or [list] for datalists.
    - Use print_flag to show modules' XML in STDOUT.
    """
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
        
        if "desc" in data:
            module_xml += "\t<description><![CDATA[" + str(data["desc"]) + "]]></description>\n"
        if "unit" in data:
            module_xml += "\t<unit><![CDATA[" + str(data["unit"]) + "]]></unit>\n"
        if "interval" in data:
            module_xml += "\t<module_interval><![CDATA[" + str(data["interval"]) + "]]></module_interval>\n"
        if "tags" in data:
            module_xml += "\t<tags>" + str(data["tags"]) + "</tags>\n"
        if "module_group" in data:
            module_xml += "\t<module_group>" + str(data["module_group"]) + "</module_group>\n"
        if "module_parent" in data:
            module_xml += "\t<module_parent>" + str(data["module_parent"]) + "</module_parent>\n"
        if "min_warning" in data:
            module_xml += "\t<min_warning><![CDATA[" + str(data["min_warning"]) + "]]></min_warning>\n"
        if "min_warning_forced" in data:
            module_xml += "\t<min_warning_forced><![CDATA[" + str(data["min_warning_forced"]) + "]]></min_warning_forced>\n"
        if "max_warning" in data:
            module_xml += "\t<max_warning><![CDATA[" + str(data["max_warning"]) + "]]></max_warning>\n"
        if "max_warning_forced" in data:
            module_xml += "\t<max_warning_forced><![CDATA[" + str(data["max_warning_forced"]) + "]]></max_warning_forced>\n"
        if "min_critical" in data:
            module_xml += "\t<min_critical><![CDATA[" + str(data["min_critical"]) + "]]></min_critical>\n"
        if "min_critical_forced" in data:
            module_xml += "\t<min_critical_forced><![CDATA[" + str(data["min_critical_forced"]) + "]]></min_critical_forced>\n"
        if "max_critical" in data:
            module_xml += "\t<max_critical><![CDATA[" + str(data["max_critical"]) + "]]></max_critical>\n"
        if "max_critical_forced" in data:
            module_xml += "\t<max_critical_forced><![CDATA[" + str(data["max_critical_forced"]) + "]]></max_critical_forced>\n"
        if "str_warning" in data:
            module_xml += "\t<str_warning><![CDATA[" + str(data["str_warning"]) + "]]></str_warning>\n"
        if "str_warning_forced" in data:
            module_xml += "\t<str_warning_forced><![CDATA[" + str(data["str_warning_forced"]) + "]]></str_warning_forced>\n"
        if "str_critical" in data:
            module_xml += "\t<str_critical><![CDATA[" + str(data["str_critical"]) + "]]></str_critical>\n"
        if "str_critical_forced" in data:
            module_xml += "\t<str_critical_forced><![CDATA[" + str(data["str_critical_forced"]) + "]]></str_critical_forced>\n"
        if "critical_inverse" in data:
            module_xml += "\t<critical_inverse><![CDATA[" + str(data["critical_inverse"]) + "]]></critical_inverse>\n"
        if "warning_inverse" in data:
            module_xml += "\t<warning_inverse><![CDATA[" + str(data["warning_inverse"]) + "]]></warning_inverse>\n"
        if "max" in data:
            module_xml += "\t<max><![CDATA[" + str(data["max"]) + "]]></max>\n"
        if "min" in data:
            module_xml += "\t<min><![CDATA[" + str(data["min"]) + "]]></min>\n"
        if "post_process" in data:
            module_xml += "\t<post_process><![CDATA[" + str(data["post_process"]) + "]]></post_process>\n"
        if "disabled" in data:
            module_xml += "\t<disabled><![CDATA[" + str(data["disabled"]) + "]]></disabled>\n"
        if "min_ff_event" in data:
            module_xml += "\t<min_ff_event><![CDATA[" + str(data["min_ff_event"]) + "]]></min_ff_event>\n"
        if "status" in data:
            module_xml += "\t<status><![CDATA[" + str(data["status"]) + "]]></status>\n"
        if "timestamp" in data:
            module_xml += "\t<timestamp><![CDATA[" + str(data["timestamp"]) + "]]></timestamp>\n"
        if "custom_id" in data:
            module_xml += "\t<custom_id><![CDATA[" + str(data["custom_id"]) + "]]></custom_id>\n"
        if "critical_instructions" in data:
            module_xml += "\t<critical_instructions><![CDATA[" + str(data["critical_instructions"]) + "]]></critical_instructions>\n"
        if "warning_instructions" in data:
            module_xml += "\t<warning_instructions><![CDATA[" + str(data["warning_instructions"]) + "]]></warning_instructions>\n"
        if "unknown_instructions" in data:
            module_xml += "\t<unknown_instructions><![CDATA[" + str(data["unknown_instructions"]) + "]]></unknown_instructions>\n"
        if "quiet" in data:
            module_xml += "\t<quiet><![CDATA[" + str(data["quiet"]) + "]]></quiet>\n"
        if "module_ff_interval" in data:
            module_xml += "\t<module_ff_interval><![CDATA[" + str(data["module_ff_interval"]) + "]]></module_ff_interval>\n"
        if "crontab" in data:
            module_xml += "\t<crontab><![CDATA[" + str(data["crontab"]) + "]]></crontab>\n"
        if "min_ff_event_normal" in data:
            module_xml += "\t<min_ff_event_normal><![CDATA[" + str(data["min_ff_event_normal"]) + "]]></min_ff_event_normal>\n"
        if "min_ff_event_warning" in data:
            module_xml += "\t<min_ff_event_warning><![CDATA[" + str(data["min_ff_event_warning"]) + "]]></min_ff_event_warning>\n"
        if "min_ff_event_critical" in data:
            module_xml += "\t<min_ff_event_critical><![CDATA[" + str(data["min_ff_event_critical"]) + "]]></min_ff_event_critical>\n"
        if "ff_type" in data:
            module_xml += "\t<ff_type><![CDATA[" + str(data["ff_type"]) + "]]></ff_type>\n"
        if "ff_timeout" in data:
            module_xml += "\t<ff_timeout><![CDATA[" + str(data["ff_timeout"]) + "]]></ff_timeout>\n"
        if "each_ff" in data:
            module_xml += "\t<each_ff><![CDATA[" + str(data["each_ff"]) + "]]></each_ff>\n"
        if "module_parent_unlink" in data:
            module_xml += "\t<module_parent_unlink><![CDATA[" + str(data["parent_unlink"]) + "]]></module_parent_unlink>\n"
        if "alert" in data:
            for alert in data["alert"]:
                module_xml += "\t<alert_template><![CDATA[" + alert + "]]></alert_template>\n"
        module_xml += "</module>\n"

    if print_flag:
        print(module_xml)

    return module_xml


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
    """
    module_xml = ""

    if module is not None:
        data = dict(module)
        module_xml = ("<log_module>\n"
                      "\t<source><![CDATA[" + str(data["source"]) + "]]></source>\n"
                      "\t<data>\"" + str(data["value"]) + "\"</data>\n"
                     )
        
        module_xml += "</log_module>\n"

    if print_flag:
        print(module_xml)

    return module_xml
