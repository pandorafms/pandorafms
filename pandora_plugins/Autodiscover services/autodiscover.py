#!/usr/bin/env python3
###################################################
#
# Pandora FMS Autodiscovery plugin.
# Checks the status of the services in list and monitors CPU and Memory for each of them.
#
# (c) A. Kevin Rojas <kevin.rojas@pandorafms.com>
#
# TO DO LIST:
# - Enable child services detection (Windows)
# - Make CPU/Memory usage available for child services (Windows)
#
###################################################

from sys import argv, path, stderr, exit
import psutil
from subprocess import *

global module_list
module_list = []


#########################################################################################
# Powershell class
#########################################################################################
class PSCheck:
    @staticmethod 
    def check_service(servicename, option=False, memcpu=False):
        """Check services with powershell by parsing their DisplayName. Returns a dict\
        list with the name of the service and a boolean with its status.\n 
        Requires service name (case insensitive)."""
        pscall = Popen(["powershell", "Get-Service", "-Name", "'*"+ str(servicename) + "*'",
                        "|", "Select-Object", "-ExpandProperty", "Name"], 
                        stdout=PIPE, stdin=DEVNULL, stderr=DEVNULL, universal_newlines=True)
        result = pscall.communicate()
        result = str(result[0]).strip().split("\n")
        procname = ''
        if result != '':
            output = []
            for element in result:
                if element != '':
                    # Get process name
                    procname = PSCheck.get_serviceprocess(element)
                    # Get process status
                    parstatus = PSCheck.getstatus(element)
                    if memcpu == True and parstatus == 1:
                        usage = get_memcpu(str(procname), str(element))
                        output += usage
                    # Generate module with name and status
                    parent = service_module(str(element), parstatus)
                    output += parent
                    if option == True:
                        children = PSCheck.getchildren(element, memcpu)
                        if type(children) == list and len(children) > 1:
                            for child in children:
                                output += child
                        else:
                            output += children
                else:
                    next

            #if output != '':
            if output and element and procname:
                return ({"name" : element, "process" : procname, "modules": output})
            else:
                return (None)

    @staticmethod
    def getchildren(servicename, memcpu=False):
        """Gets Dependent services of a given Windows service"""
        pschild = Popen(["powershell", "Get-Service", "-Name '" + str(servicename) +
                        "' -DS", "|", "Select-Object", "-ExpandProperty", "Name"], 
                        stdout=PIPE, stdin=DEVNULL, stderr=DEVNULL, universal_newlines=True)
        children = pschild.communicate()[0].strip()
        kids = []
        for child in (children.split("\n") if children != "" else []):
            status = PSCheck.getstatus(child)
            kids += service_module(str(child), status, "Service " + str(servicename) + " - Status")
            if status:
                if memcpu == True:
                    kidsusage = get_memcpu(str(child))
                    for usage in kidsusage:
                        kids += usage
            else:
                next
        return (kids)

    @staticmethod
    def getstatus(servicename):
        """Gets the status of a given Windows service"""
        running = Popen(["powershell", "Get-Service", "-Name '" + str(servicename) +
                    "' |", "Select-Object", "-ExpandProperty", "Status"],
                    stdout=PIPE, stdin=DEVNULL, stderr=DEVNULL, universal_newlines=True)
        status = running.communicate()[0].strip()
        return (int(status == "Running"))

    @staticmethod
    def get_serviceprocess(servicename):
        """Gets name of the process of the service"""
        service = psutil.win_service_get(servicename)
        srv_pid = service.pid()
        process = psutil.Process(srv_pid)
        proc_name = process.name()
        return (proc_name)


#########################################################################################
# Services creation
#########################################################################################

def service_module(name, value, parent=None):
    #print ("service_module BEGIN "+str(now(0,1)))
    module = [{
            "name"              :   "Service "+ name + " - Status",
            "type"              :   "generic_proc",
            "value"             :   value,
            "module_parent"     :   parent,
        }]
    #print ("service_module END "+str(now(0,1)))        
    return (module)

def get_memcpu (process, servicename):
    """Creates a module for Memory and CPU for a given process. Returns a list of dictionaries."""
    modules = []
    if process:
        if servicename != None:
            parentname = servicename
        else:
            parentname = process
        modules += [{
                "name"         :    "Service "+ process + " - Memory usage",
                "type"          :    "generic_data",
                "value"         :     proc_percentbyname(process)[0],
                "unit"          :     "%",
                "module_parent" :    "Service "+ parentname + " - Status",
                },
                {"name"         :    "Service "+ process + " - CPU usage",
                "type"          :    "generic_data",
                "value"         :     proc_percentbyname(process)[1],
                "unit"          :     "%",
                "module_parent" :    "Service "+ parentname + " - Status",
                }]
    return (modules)

def proc_percentbyname(procname): ############# 03/03/2020
    """Gets Memory and CPU usage for a given process. Returns a list."""
    #print ("proc_percentbyname BEGIN "+str(now(0,1)))
    procs = [p for p in psutil.process_iter() if procname in p.name().lower()]
    memory = []
    cpu = []
    try:
        for proc in procs:
            if proc.name() == procname:
                cpu.append(proc.cpu_percent(interval=0.5))
                memory.append(proc.memory_percent())
            else:
                next
    except psutil.NoSuchProcess:
        next
    #print ("proc_percentbyname END "+str(now(0,1)))
    return ([sum(memory),sum(cpu)])

def win_service(servicelist, option=False, memcpu=False):
    """Creates modules for Windows servers."""
    modules = []
    for srvc in servicelist:
        if srvc and len(srvc) > 2:
            output = PSCheck.check_service(srvc, option, memcpu)
            if output != None and output["modules"]:
                modules += PSCheck.check_service(srvc.strip(), option, memcpu)["modules"]
                module_list.append(srvc)
                winprocess = output["name"]
                #if memcpu == True:
                #    modules += get_memcpu(winprocess) ## Only available for parent service ATM.
            else:
                next
        else:
            next
    for module in modules:
        print_module(module, 1)


def lnx_service(services_list, memcpu=False):
    """Creates modules for Linux servers"""
    modules = []
    sysctl = getstatusoutput("command -v systemctl")[0]
    servic = getstatusoutput("command -v service")[0]
    for srvc in services_list:
        status = None
        if sysctl == 0: 
            ### Systemd available
            syscall = Popen(["systemctl", "is-active", srvc], stdout=PIPE,
                                stdin=DEVNULL, universal_newlines=True)
            result = syscall.communicate()
            result = result[0].strip().lower()
            if result == "active":
                modules += service_module(srvc, 1)
                status = 1
            elif result == "inactive":
                modules += service_module(srvc, 0)
                status = 0
            elif result == "unknown":
                next
        elif sysctl != 0 and servic == 0: 
            ### Systemd not available, switch to service command
            syscall = Popen(["service", srvc, "status"], stdout=PIPE,
                                stdin=DEVNULL, stderr=DEVNULL, universal_newlines=True)
            result = syscall.communicate()[0].lower()
            if "is running" in result:
                modules += service_module(srvc, 1)
                status = 1
            elif "is stopped" in result:
                modules += service_module(srvc, 0)
                status = 0
            else:
                next
        else:
            print ("No systemd or service commands available. Exiting...", file=stderr)
            exit()
        if status:
            module_list.append(srvc)
            if memcpu == True:
                modules += get_memcpu(srvc, None)
    
    for m in modules:
        print_module (m, 1)


#########################################################################################
# print_module function
#########################################################################################
def print_module(module, str_flag=False):
    """Returns module in XML format. Accepts only {dict}.\n
    + Only works with one module at a time: otherwise iteration is needed.
    + Module "value" field accepts str type or [list] for datalists.
    + Use not_print_flag to avoid printing the XML (only populates variables).
    """
    data = dict(module)
    module_xml = ("<module>\n"
                  "\t<name><![CDATA[" + str(data["name"]) + "]]></name>\n"
                  "\t<type>" + str(data["type"]) + "</type>\n"
                  )
    #### Strip spaces if module not generic_data_string    
    if type(data["type"]) is not str and "string" not in data["type"]:
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
    if "module_parent" in data and data["module_parent"] != None:
        module_xml += "\t<module_parent>" + str(data["module_parent"]) + "</module_parent>\n"
    if "min_warning" in data:
        module_xml += "\t<min_warning><![CDATA[" + str(data["min_warning"]) + "]]></min_warning>\n"
    if "max_warning" in data:
        module_xml += "\t<max_warning><![CDATA[" + str(data["max_warning"]) + "]]></max_warning>\n"
    if "min_critical" in data:
        module_xml += "\t<min_critical><![CDATA[" + str(data["min_critical"]) + "]]></min_critical>\n"
    if "max_critical" in data:
        module_xml += "\t<max_critical><![CDATA[" + str(data["max_critical"]) + "]]></max_critical>\n"
    if "str_warning" in data:
        module_xml += "\t<str_warning><![CDATA[" + str(data["str_warning"]) + "]]></str_warning>\n"
    if "str_critical" in data:
        module_xml += "\t<str_critical><![CDATA[" + str(data["str_critical"]) + "]]></str_critical>\n"
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
    if "global_alerts" in data:
        for alert in data["alert"]:
            module_xml += "\t<alert_template><![CDATA[" + alert + "]]></alert_template>\n"
    module_xml += "</module>\n"

    #### Print flag
    if str_flag is not False:
        print (module_xml)

    return (module_xml)


#########################################################################################
# MAIN
#########################################################################################

def main():
    """Checks OS and calls the discover function."""
    if psutil.WINDOWS:
        OS = "Windows"
        service_list = ["MySQL", "postgresql", "pgsql", "oracle", "MSSQL", "IISADMIN",
                        "W3svc", "NTDS", "Netlogon", "DNS", "MSExchangeADTopology",
                        "MSExchangeServiceHost", "MSExchangeSA", "MSExchangeTransport"]
        discover(OS, service_list)
    elif psutil.LINUX:
        OS = "Linux"
        service_list = ["httpd", "apache2", "nginx", "ldap", "docker",
                        "postfix", "mysqld", "postgres", "oracle", "mongod"]
        discover(OS, service_list)
    else:
        print ("OS not recognized. Exiting...", file=stderr)
        exit()

def discover(osyst, servicelist):
    """Shows help and triggers the creation of service modules"""
    if "--usage" in argv:
        memcpu = True
    else:
        memcpu = False
    if len(argv) > 2 and argv[1] == "--list":
        servicelist = argv[2].split(",")
        if osyst == "Windows":
            win_service(servicelist, False, memcpu) ## False won't get children
        elif osyst == "Linux":
            lnx_service(servicelist, memcpu)
    elif len(argv) > 1 and argv[1] == "--default":
        if osyst == "Windows":
            win_service(servicelist, False, memcpu) ## False won't get children
        elif osyst == "Linux":
            lnx_service(servicelist, memcpu)
    else:
        print ("\nPandora FMS Autodiscovery plugin.")
        print ("Checks the status of the services in list and monitors CPU and Memory for each of them.\n")
        print ("Usage:")
        print ("{} [options] [--usage]".format(argv[0]))
        print ("--help")
        print ("\tPrints this help screen")
        print ("--default")
        print ("\tRuns this tool with default monitoring.".format(argv[0]))
        print ("\tServices monitored by default for {}:".format(osyst))
        print ("\t",", ".join(servicelist))
        print ("--list \"<srvc1,srvc2,srvc3>\"")
        print ("\tReplaces default services for a given list (comma-separated)")
        if osyst == "Windows":
            print ("\tEach element of the list will be treated as a regexp, but they must be over 2 characters.")
            print ("\tElements under 2 characters will be discarded.")
        print ("--usage")
        print ("\tAdds modules for CPU and Memory usage per service/process (optional, can take some time).\n")


##### RUN ####
main()