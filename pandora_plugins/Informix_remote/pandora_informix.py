import ibm_db
import re
import argparse,sys,re,json,os,traceback,hashlib
from datetime import datetime
import subprocess

__author__ = "Alejandro Sánchez Carrion"
__copyright__ = "Copyright 2022, PandoraFMS"
__maintainer__ = "Operations department"
__status__ = "Production"
__version__= '1.0'

info = f"""
Pandora FMS DB2
Version = {__version__}

Manual execution

./pandora_informix --hostname <host> --port <port> --uid <user> --database <database> --password <password> --conf <path conf> --as_agent_plugin 1

"""

parser = argparse.ArgumentParser(description= info, formatter_class=argparse.RawTextHelpFormatter)

parser.add_argument('--database', help="database name",default="sysmaster")
parser.add_argument('--hostname', help="IP")
parser.add_argument('--port', help="default:9089")
parser.add_argument('--uid', help="user")
parser.add_argument('--password', help="password")

parser.add_argument('--default_metrics', help="",type=int,default=1)
parser.add_argument('--conf', help='path for the file with the queries')

parser.add_argument('--agent_name', help='agent name', default= "Informix")
parser.add_argument('-a','--agent_alias', help='Name of the agent to store monitoring, default=p: informix', default= "Informix")
parser.add_argument('-A', '--use_alias_as_name', help='Use Agent Alias as Agent Name name', action='store_true')
parser.add_argument('-m', '--module_prefix', help='PandoraFMS module prefix', default='informix')

parser.add_argument('-g', '--group', help='PandoraFMS destination group (default informix)', default='informix')
parser.add_argument('--data_dir', help='PandoraFMS data dir (default: /var/spool/pandora/data_in/)', default='/var/spool/pandora/data_in/')
parser.add_argument('--as_agent_plugin', help='mode plugin', default=0,type=int)
parser.add_argument('--tentacle_port', help='tentacle port', default=41121)
parser.add_argument('--tentacle_address', help='tentacle adress', default=None)

args = parser.parse_args()

conn_str=f'database={args.database};hostname={args.hostname};port={args.port};protocol=tcpip;uid={args.uid};pwd={args.password}'

def connect(conn_str):
    try:
        ibm_db_conn = ibm_db.connect(conn_str,'','')
        return ibm_db_conn
    except:
        print("no connection:", ibm_db.conn_errormsg())
        sys.exit(1)

def executeQuery(con, query):
    try:
        stmt = ibm_db.exec_immediate(con, query)
        result = ibm_db.fetch_both(stmt)
        return result

    except Exception as message:
        pass

### Pandora Tools ###-------------------------------------------------------------------------------------------------------
modules = []


#########################################################################################
# print_agent
#########################################################################################
def print_agent(agent, modules, data_dir="/var/spool/pandora/data_in/", log_modules= None, print_flag = None):
    """Prints agent XML. Requires agent conf (dict) and modules (list) as arguments.
    - Use print_flag to show modules' XML in STDOUT.
    - Returns a tuple (xml, data_file).
    """
    data_file=None

    header = "<?xml version='1.0' encoding='UTF-8'?>\n"
    header += "<agent_data"
    for dato in agent:
        header += " " + str(dato) + "='" + str(agent[dato]) + "'"
    header += ">\n"
    xml = header
    if modules :
        for module in modules:
            modules_xml = print_module(module)
            xml += str(modules_xml)
    xml += "</agent_data>"
    if not print_flag:
        data_file = write_xml(xml, agent["agent_name"], data_dir)
    else:
        print(xml)
    
    return (xml,data_file)

#########################################################################################
# print_module
#########################################################################################
def print_module(module, print_flag=None):
    """Returns module in XML format. Accepts only {dict}.\n
    - Only works with one module at a time: otherwise iteration is needed.
    - Module "value" field accepts str type or [list] for datalists.
    - Use print_flag to show modules' XML in STDOUT.
    """
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
    if "global_alerts" in data:
        for alert in data["alert"]:
            module_xml += "\t<alert_template><![CDATA[" + alert + "]]></alert_template>\n"
    module_xml += "</module>\n"

    if print_flag:
        print (module_xml)

    return (module_xml)

#########################################################################################
# write_xml
#########################################################################################

def write_xml(xml, agent_name, data_dir="/var/spool/pandora/data_in/"):
    """Creates a agent .data file in the specified data_dir folder\n
    Args:
    - xml (str): XML string to be written in the file.
    - agent_name (str): agent name for the xml and file name.
    - data_dir (str): folder in which the file will be created."""
    Utime = datetime.now().strftime('%s')
    data_file = "%s/%s.%s.data" %(str(data_dir),agent_name,str(Utime))
    try:
        with open(data_file, 'x') as data:
            data.write(xml)
    except OSError as o:
        sys.exit(f"ERROR - Could not write file: {o}, please check directory permissions")
    except Exception as e:
        sys.exit(f"{type(e).__name__}: {e}")
    return (data_file)

# # default agent
def clean_agent() :
    global agent
    agent = {
        "agent_name"  : "",
        "agent_alias" : "",
        "parent_agent_name" : "",
        "description" : "",
        "version"     : "",
        "os_name"     : "",
        "os_version"  : "",
        "timestamp"   : datetime.today().strftime('%Y/%m/%d %H:%M:%S'),
        #"utimestamp"  : int(datetime.timestamp(datetime.today())),
        "address"     : "",
        "group"       : args.group,
        "interval"    : "",
        "agent_mode"  : "1",
        }
    return agent

# default module
def clean_module() :
    global modulo
    modulo = {
        "name"   : "",
        "type"   : "generic_data_string",
        "desc"   : "",
        "value"  : "",
    }
    return modulo

#########################################################################################
# tentacle_xml
#########################################################################################
def tentacle_xml(file, tentacle_ops,tentacle_path='', debug=0):
    """Sends file using tentacle protocol\n
    - Only works with one file at time.
    - file variable needs full file path.
    - tentacle_opts should be a dict with tentacle options (address [password] [port]).
    - tentacle_path allows to define a custom path for tentacle client in case is not in sys path).
    - if debug is enabled, the data file will not be removed after being sent.

    Returns 0 for OK and 1 for errors.
    """

    if file is None :
        sys.stderr.write("Tentacle error: file path is required.")
    else :
        data_file = file
    
    if tentacle_ops['address'] is None :
        sys.stderr.write("Tentacle error: No address defined")
        return 1
    
    try :
        with open(data_file, 'r') as data:
            data.read()
        data.close()
    except Exception as e :
        sys.stderr.write(f"Tentacle error: {type(e).__name__} {e}")
        return 1

    tentacle_cmd = f"{tentacle_path}tentacle_client -v -a {tentacle_ops['address']} "
    if "port" in tentacle_ops:
        tentacle_cmd += f"-p {tentacle_ops['port']} "
    if "password" in tentacle_ops:
        tentacle_cmd += f"-x {tentacle_ops['password']} "
    tentacle_cmd += f"{data_file} "

    tentacle_exe=subprocess.Popen(tentacle_cmd, stdout=subprocess.PIPE, shell=True)
    rc=tentacle_exe.wait()

    if rc != 0 :
        sys.stderr.write("Tentacle error")
        return 1
    elif debug == 0 : 
        os.remove(file)
 
    return 0

## funcion agent
def agentplugin(modules,agent,plugin_type="server",data_dir="/var/spool/pandora/data_in/",tentacle=False,tentacle_conf=None) :
    if plugin_type == "server":
        for modulo in modules:
            print_module(modulo,1)
        
    elif tentacle == True and tentacle_conf is not None:
        agent_file=print_agent(agent, modules,data_dir)
        if agent_file[1] is not None:
            tentacle_xml(agent_file[1],tentacle_conf)
            print ("1")        
    else:
        print_agent(agent, modules,data_dir)
        print ("1")  


### Pandora Tools end ###-------------------------------------------------------------------------------------------------------

# hash md5 agent name
if args.use_alias_as_name is not True:
    agent_name_md5 = (hashlib.md5(args.agent_alias.encode()).hexdigest())
else:
    agent_name_md5 = args.agent_alias

ibm_db_conn=connect(conn_str)


if args.default_metrics == 1:

    clean_agent()
    agent.update(
        agent_name = args.agent_name +"_metrics",
        agent_alias =agent_name_md5 , 
        description ="Agent generated by pandora_db2"  
    ) 

    #Dbspace I/O

    dbspaceIO = executeQuery(ibm_db_conn, 'SELECT d.name[1,18] dbspace,fname [1,22], sum(pagesread) dreads, sum(pageswritten) dwrites FROM syschkio c, syschunks k, sysdbspaces d WHERE d.dbsnum = k.dbsnum AND k.chknum = c.chunknum GROUP BY 1, 2 ORDER BY 3 desc;')
    
    for dato,value in dbspaceIO.items():
        if 'dbspace' in str(dato):

            name=value
            
        if 'fname' in str(dato):
            clean_module()
            modulo.update(
                name = f'{name.strip()}.fname',
                type = "generic_data_string",
                desc = "",
                value = value
            )
            modules.append(modulo)

        if 'dreads' in str(dato):
            clean_module()
            modulo.update(
                name = f'{name.strip()}.dreads',
                type = "generic_data",
                desc = "",
                value = value
            )
            modules.append(modulo)
        if 'dwrites' in str(dato):
            clean_module()
            modulo.update(
                name = f'{name.strip()}.dwrites',
                type = "generic_data",
                desc = "",
                value = value
            )
            modules.append(modulo)

    #Dbspace usage

    dbspaceusage = executeQuery(ibm_db_conn, 'SELECT sysdbspaces.name[1,18] name, nchunks, format_units(sum(syschunks.chksize * (SELECT sh_pagesize FROM sysshmvals)))::CHAR(12) total, format_units(sum(syschunks.chksize * (SELECT sh_pagesize FROM sysshmvals)) - sum(syschunks.nfree   * (SELECT sh_pagesize FROM sysshmvals)))::CHAR(12) used, round (100 - ((sum(syschunks.nfree)) / (sum(syschunks.chksize)) * 100), 2) pct_used FROM sysdbspaces,syschunks WHERE sysdbspaces.dbsnum = syschunks.dbsnum AND sysdbspaces.is_sbspace = 0 GROUP BY 1,2 UNION SELECT sysdbspaces.name[1,18] name, nchunks, format_units(sum(syschunks.chksize * (SELECT sh_pagesize FROM sysshmvals)))::CHAR(12) total, format_units(sum(syschunks.chksize * (SELECT sh_pagesize FROM sysshmvals)) - sum(syschunks.nfree   * (SELECT sh_pagesize FROM sysshmvals)))::CHAR(12) used, round (100 - ((sum(syschunks.nfree)) / (sum(syschunks.chksize)) * 100), 2) pct_used FROM sysdbspaces,syschunks WHERE sysdbspaces.dbsnum = syschunks.dbsnum AND sysdbspaces.is_sbspace = 1 GROUP BY 1,2 ORDER BY pct_used DESC;')
    patron='[+-]?\d*\.?\d+'
    for dato,value in dbspaceusage.items():
        if 'name' in str(dato):
            name=value
        if 'nchunks' in str(dato):
            clean_module()
            modulo.update(
                name = f'{name.strip()}.nchunks',
                type = "generic_data",
                desc = "",
                value = value
            )
            modules.append(modulo)
        if 'total' in str(dato):
            value_module=re.findall(patron,str(value))
            clean_module()
            modulo.update(
                name = f'{name.strip()}.total',
                type = "generic_data",
                desc = "",
                value = value_module[0],
                unit="MB"
            )
            modules.append(modulo)
        if 'used' in str(dato) and 'pct_used' not in str(dato):
            value_module=re.findall(patron,str(value))

            clean_module()
            modulo.update(
                name = f'{name.strip()}.used',
                type = "generic_data",
                desc = "",
                value = value_module[0],
                unit="MB"
            )
            modules.append(modulo)
        if 'pct_used' in str(dato):

            clean_module()
            modulo.update(
                name = f'{name.strip()}.pct_used',
                type = "generic_data",
                desc = "",
                value = value,
                unit="%"
            )
            modules.append(modulo)
    

    # #Checking tables I/O

    tablesIO = executeQuery(ibm_db_conn, 'SELECT dbsname[1,18], tabname[1,18], (isreads + pagreads) diskreads,(iswrites + pagwrites) diskwrites FROM sysptprof ORDER BY 3 desc, 4 desc;')

    for dato,value in tablesIO.items():

        if 'dbsname' in str(dato):
            name=value
        if 'tabname' in str(dato):
            clean_module()
            modulo.update(
                name = f'{name.strip()}.tabname',
                type = "generic_data_string",
                desc = "",
                value = value.strip()
            )
            modules.append(modulo)
        if 'diskreads' in str(dato):
            clean_module()
            modulo.update(
                name = f'{name.strip()}.diskreads',
                type = "generic_data",
                desc = "",
                value = value
            )
            modules.append(modulo)
        if 'diskwrites' in str(dato):
            clean_module()
            modulo.update(
                name = f'{name.strip()}.diskwrites',
                type = "generic_data",
                desc = "",
                value = value
            )
            modules.append(modulo)


    # #Session statistics

    sessionstatics = executeQuery(ibm_db_conn, 'select sid, username[1,20], hostname[1,20], connected logint_time, hex(state) s_state from syssessions order by logint_time')

    for dato,value in sessionstatics.items():
        if 'sid' in str(dato):
            name=value

        if 'hostname' in str(dato):
            clean_module()
            modulo.update(
                name = f'sid->{name}.hostname',
                type = "generic_data",
                desc = "",
                value = value
            )
            modules.append(modulo)
        if 'logint_time' in str(dato):
            clean_module()
            modulo.update(
                name = f'sid->{name}.diskwrites',
                type = "generic_data",
                desc = "",
                value = value
            )
            modules.append(modulo)
        if 's_state' in str(dato):
            clean_module()
            modulo.update(
                name = f'sid->{name}.diskwrites',
                type = "generic_data",
                desc = "",
                value = value
            )
            modules.append(modulo)

    ##Session profile

    sessionprofile = executeQuery(ibm_db_conn, 'select syssessions.sid, username[1,20],(isreads+bufreads+bufwrites+pagreads+pagwrites) access,locksheld, seqscans,total_sorts,dsksorts from syssesprof, syssessions where syssesprof.sid = syssessions.sid')

    for dato,value in sessionprofile.items():
        if 'sid' in str(dato):
            name=value

        if 'access' in str(dato):
            clean_module()
            modulo.update(
                name = f'sid->{name}.access',
                type = "generic_data",
                desc = "",
                value = value
            )
            modules.append(modulo)
        if 'locksheld' in str(dato):
            clean_module()
            modulo.update(
                name = f'sid->{name}.locksheld',
                type = "generic_data",
                desc = "",
                value = value
            )
            modules.append(modulo)
        if 'seqscans' in str(dato):
            clean_module()
            modulo.update(
                name = f'sid->{name}.seqscans',
                type = "generic_data",
                desc = "",
                value = value
            )
            modules.append(modulo)
        if 'total_sorts' in str(dato):
            clean_module()
            modulo.update(
                name = f'sid->{name}.total_sorts',
                type = "generic_data",
                desc = "",
                value = value
            )
            modules.append(modulo)
        if 'dsksorts' in str(dato):
            clean_module()
            modulo.update(
                name = f'sid->{name}.dsksorts',
                type = "generic_data",
                desc = "",
                value = value
            )
            modules.append(modulo)

if args.tentacle_address is not None:
    tentacle_conf={"address":args.tentacle_address,"port":args.tentacle_port}
    agentplugin(modules,agent,"agent",config["data_in"],True,tentacle_conf)
elif args.as_agent_plugin!=1:
    agentplugin(modules,agent,"agent",config["data_in"]) 
else:
    agentplugin(modules,agent)
