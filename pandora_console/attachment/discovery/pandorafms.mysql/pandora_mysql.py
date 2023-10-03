#!/usr/bin/env python
# -*- encoding: utf-8 -*-

__author__ = ["Enrique Martin Garcia", "Alejandro Sanchez Carrion"]
__copyright__ = "Copyright 2023, PandoraFMS"
__maintainer__ = "Operations department"
__status__ = "Production"
__version__= '1.0'

import sys,os
lib_dir = os.path.join(os.path.dirname(sys.argv[0]), 'lib')
sys.path.insert(0, lib_dir)

import signal

# Define a function to handle the SIGTERM signal
def sigterm_handler(signum, frame):
    print("Received SIGTERM signal. Cleaning up...")
    sys.exit(0)
signal.signal(signal.SIGTERM, sigterm_handler)

import argparse
import json
import re
import configparser
from queue import Queue
from threading import Thread

import pymysql

###############
## VARIABLES ##
###############

# Global variables
output = {}
error_level = 0
summary = {}
info = ""
monitoring_data = []
db_targets = []
target_agents = []
custom_queries = []

# Parameters default values
threads = 1
agents_group_id = 10
interval = 300
user = ""
password = ""
modules_prefix = ""
execute_custom_queries = 1
analyze_connections = 1
scan_databases = 0
agent_per_database = 0
db_agent_prefix = ""
innodb_stats = 1
engine_uptime = 1
query_stats = 1
cache_stats = 1

######################
## GLOBAL FUNCTIONS ##
######################

####
# Parse parameter input
###########################################
def param_int(param=""):
    try:
        return int(param)
    except:
        return 0

####
# Get module name with prefix
###########################################
def get_module_name(name=""):
    global modules_prefix

    return modules_prefix+name

####
# Set error level to value
###########################################
def set_error_level(value=0):
    global error_level

    error_level = value

####
# Set fixed value to summary key
###########################################
def set_summary_value(key="", value=""):
    global summary

    summary[key] = value

####
# Add value to summary key
###########################################
def add_summary_value(key="", value=""):
    global summary

    if key in summary:
        summary[key] += value
    else:
        set_summary_value(key, value)

####
# Set fixed value to info
###########################################
def set_info_value(data=""):
    global info

    info = data

####
# Add data to info
###########################################
def add_info_value(data=""):
    global info

    info += data

####
# Add a new agent and modules to JSON
###########################################
def add_monitoring_data(data={}):
    global monitoring_data

    monitoring_data.append(data)

####
# Print JSON output and exit script
###########################################
def print_output():
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

def parse_parameter(config=None, default="", key=""):
    try:
        return config.get("CONF", key)
    except Exception as e:
        return default

########################
## SPECIFIC FUNCTIONS ##
########################

####
# Format uptime to human readable
###########################################
def format_uptime(seconds):
    # Calculate the days, hours, minutes, and seconds
    minutes, seconds = divmod(seconds, 60)
    hours, minutes = divmod(minutes, 60)
    days, hours = divmod(hours, 24)

    # Create the formatted string
    uptime_string = ""
    if days > 0:
        uptime_string += f"{days} days "
    if hours > 0:
        uptime_string += f"{hours} hours "
    if minutes > 0:
        uptime_string += f"{minutes} minutes "
    uptime_string += f"{seconds} seconds"

    return uptime_string

####
# Scan engine databases
###########################################
def get_databases_modules(db_object=None):
    global interval
    global agents_group_id
    global modules_prefix
    global agent_per_database
    global db_agent_prefix

    # Initialize modules
    modules = []

    if db_object:
        # Get all databases
        databases = db_object.run_query(f"SHOW DATABASES")[0]
        
        for db in databases:
            # Get database name
            db_name = db["Database"]

            # Skip core databases.
            if db_name == "mysql":
                continue
            if db_name == "information_schema":
                continue
            if db_name == "performance_schema":
                continue
            if db_name == "sys":
                continue

            # Add modules
            modules.append({
                "name": get_module_name(db_name+" availability"),
                "type": "generic_proc",
                "data": 1,
                "description": "Database available"
            })

            modules.append({
                "name": get_module_name(db_name+" fragmentation ratio"),
                "type": "generic_data",
                "data": db_object.run_query(f"SELECT AVG(DATA_FREE/(DATA_LENGTH + INDEX_LENGTH)) AS average_fragmentation_ratio FROM information_schema.tables WHERE table_schema = '{db_name}' GROUP BY table_schema", single_value=True)[0],
                "unit": "%",
                "description": "Database fragmentation"
            })

            modules.append({
                "name": get_module_name(db_name+" size"),
                "type": "generic_data",
                "data": db_object.run_query(f"SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size FROM information_schema.tables WHERE table_schema = '{db_name}' GROUP BY table_schema", single_value=True)[0],
                "unit": "MB",
                "description": "Database size"
            })

            modules += db_object.get_custom_queries(db_name)

            # Add a new agent if agent per database and reset modules
            if agent_per_database == 1:
                add_monitoring_data({
                    "agent_data"  : {
                        "agent_name"        : db_agent_prefix+db_object.agent+" "+db_name,
                        "agent_alias"       : db_agent_prefix+db_object.agent+" "+db_name,
                        "os"                : db_object.type,
                        "os_version"        : db_object.version,
                        "interval"          : interval,
                        "id_group"          : agents_group_id,
                        "address"           : db_object.host,
                        "description"       : "",
                        "parent_agent_name" : db_object.agent,
                    },
                    "module_data" : modules
                })
                add_summary_value("Total agents", 1)
                add_summary_value("Databases agents", 1)
                modules = []

    return modules

#############
## CLASSES ##
#############

####
# Remote database object
###########################################
class RemoteDB:
    def __init__(self, target="", agent=""):
        self.type = "MySQL"
        self.target = target
        self.agent = self.get_agent(agent)
        self.parse_target()
        self.connected = 0
        self.connector = None

        self.connect()

        self.version = self.get_version()

    def connect(self):
        global user
        global password

        # Try to connect and capture errors
        error = ""
        try:
            connection = pymysql.connect(
                host=self.host,
                port=self.port,
                user=user,
                password=password,
                connect_timeout=5
            )

            # Execute a test query
            with connection.cursor() as cursor:
                cursor.execute("SELECT 1")
                result = cursor.fetchone()
                if result:
                    self.connector = connection
                    self.connected = 1
                else:
                    error = "Connection failed"

        except pymysql.Error as e:
            error = str(e)

        # Add error to info if cna't connect
        if self.connector is None:
            add_info_value("["+self.target+"]"+error+"\n")
            add_summary_value("Targets down", 1)
        else:
            add_summary_value("Targets up", 1)

    def disconnect(self):
        if self.connected == 1:
            self.connector.close()

    def run_query(self, query="", single_row=False, single_value=False, db=""):
        if self.connected == 1 and query:
            try:
                with self.connector.cursor() as cursor:
                    if db:
                        cursor.execute(f"USE {db}")

                    # Execute your query
                    cursor.execute(query)

                    # Get query fields
                    fields = [field[0] for field in cursor.description]
                    
                    if single_row or single_value:
                        # Fetch first row
                        row = cursor.fetchone()

                        if single_value:
                            if row:
                                # Get first field value
                                result = str(row[0])
                            else:
                                result = ""

                        else:
                            # Read row
                            row_item = {}
                            if row:
                                i = 0
                                # Assign each value for each field in row item
                                for field in fields:
                                    row_item[field] = str(row[i])
                                    i += 1

                            result =  row_item

                    else:
                        # Fetch all rows
                        row_items = []
                        rows = cursor.fetchall()
                        
                        # Read each row
                        for row in rows:
                            row_item = {}
                            i = 0
                            # Assign each value for each field in row item
                            for field in fields:
                                row_item[field] = str(row[i])
                                i += 1
                            
                            # Add row item to row items
                            row_items.append(row_item)

                        result = row_items

                    # Close cursor and return result
                    cursor.close()
                    return result, True, ""

            except pymysql.Error as e:
                add_info_value("["+self.target+"]"+str(e)+"\n")
                return None, False, str(e)
        else:
            return None, False, "Not connected to database"

    def get_agent(self, agent=""):
        if agent:
            return agent
        else:
            return self.target.split(":")[0]

    def get_version(self):
        version = self.run_query(f"SELECT @@VERSION", single_value=True)[0]
        if version is None:
            version = "Discovery"
        return version

    def parse_target(self):
        # Default values
        self.port = 3306

        if ":" in self.target:
            target = self.target.split(":")
            self.host = target[0]
            self.port = int(target[1])

        else:
            self.host = self.target

    def get_statistics(self):
        global interval
        global modules_prefix
        global engine_uptime
        global query_stats
        global analyze_connections
        global innodb_stats
        global cache_stats

        # Initialize modules
        modules = []

        # Get status values
        status = {}
        for var in self.run_query(f"SHOW GLOBAL STATUS")[0]:
            status[var["Variable_name"]] = var["Value"]

        # Get svariables values
        variables = {}
        for var in self.run_query(f"SHOW VARIABLES")[0]:
            variables[var["Variable_name"]] = var["Value"]

        # Get modules
        if engine_uptime == 1:
            modules.append({
                "name": get_module_name("restart detection"),
                "type": "generic_proc",
                "data": 1 if int(status["Uptime"]) < 2 * interval else 0,
                "description": f"Running for {format_uptime(int(status['Uptime']))} (value is 0 if restart detected)"
            })

        if query_stats == 1:
            modules.append({
                "name": get_module_name("queries"),
                "type": "generic_data_inc_abs",
                "data": status["Queries"],
                "description": ""
            })

            modules.append({
                "name": get_module_name("query rate"),
                "type": "generic_data_inc",
                "data": status["Queries"],
            })

            modules.append({
                "name": get_module_name("query select"),
                "type": "generic_data_inc_abs",
                "data": status["Com_select"],
            })

            modules.append({
                "name": get_module_name("query update"),
                "type": "generic_data_inc_abs",
                "data": status["Com_update"]
            })

            modules.append({
                "name": get_module_name("query delete"),
                "type": "generic_data_inc_abs",
                "data": status["Com_delete"]
            })

            modules.append({
                "name": get_module_name("query insert"),
                "type": "generic_data_inc_abs",
                "data": status["Com_insert"]
            })

        if analyze_connections == 1:
            modules.append({
                "name": get_module_name("current connections"),
                "type": "generic_data",
                "data": status["Threads_connected"],
                "description": "Current connections to MySQL engine (global)",
                "min_warning": int(variables["max_connections"]) * 0.90,
                "min_critical": int(variables["max_connections"]) * 0.98
            })

            modules.append({
                "name": get_module_name("connections ratio"),
                "type": "generic_data",
                "data": (int(status["Max_used_connections"]) / int(variables["max_connections"])) * 100,
                "description": "This metric indicates if you could run out soon of connection slots.",
                "unit": "%",
                "min_warning": 85,
                "min_critical": 90
            })

            modules.append({
                "name": get_module_name("aborted connections"),
                "type": "generic_data_inc_abs",
                "data": status["Aborted_connects"],
                "description": "This metric indicates if the ammount of aborted connections in the last interval."
            })

        if innodb_stats == 1:
            modules.append({
                "name": get_module_name("Innodb buffer pool pages total"),
                "type": "generic_data",
                "data": status["Innodb_data_written"],
                "description": "Total number of pages in the buffer pool (utilization)."
            })

            modules.append({
                "name": get_module_name("Innodb buffer pool read requests"),
                "type": "generic_data_inc_abs",
                "data": status["Innodb_buffer_pool_read_requests"],
                "description": "Reads from innodb buffer pool."
            })

            modules.append({
                "name": get_module_name("Innodb buffer pool write requests"),
                "type": "generic_data_inc_abs",
                "data": status["Innodb_buffer_pool_write_requests"],
                "description": "Writes in innodb buffer pool."
            })

            modules.append({
                "name": get_module_name("Innodb disk reads"),
                "type": "generic_data_inc_abs",
                "data": status["Innodb_data_reads"],
                "description": "Amount of read operations."
            })

            modules.append({
                "name": get_module_name("Innodb disk writes"),
                "type": "generic_data_inc_abs",
                "data": status["Innodb_data_writes"],
                "description": "Amount of write operations."
            })

            modules.append({
                "name": get_module_name("Innodb disk data read"),
                "type": "generic_data_inc_abs",
                "data": int(status["Innodb_data_read"])/(1024*1024),
                "description": "Amount of data read from disk.",
                "unit": "MB"
            })

            modules.append({
                "name": get_module_name("Innodb disk data written"),
                "type": "generic_data_inc_abs",
                "data": int(status["Innodb_data_written"])/(1024*1024),
                "description": "Amount of data written to disk.",
                "unit": "MB"
            })

        if cache_stats == 1:
            modules.append({
                "name": get_module_name("query cache enabled"),
                "type": "generic_proc",
                "data": 1 if variables["have_query_cache"] == "YES" else 0,
                "description": "Query cache enabled." if variables["have_query_cache"] == "YES" else "Query cache not found, check query_cache_type in your my.cnf"
            })

            if variables["have_query_cache"] == "YES":
                if int(status["Qcache_hits"]) + int(status["Qcache_inserts"]) + int(status["Qcache_not_cached"]) != 0:
                    ratio = 100 * int(status["Qcache_hits"]) / int(status["Qcache_inserts"]) + int(status["Qcache_inserts"]) + int(status["Qcache_not_cached"])
                    
                    modules.append({
                        "name": get_module_name("query hit ratio"),
                        "type": "generic_data",
                        "data": ratio,
                        "unit": "%"
                    })

        return modules

    def get_custom_queries(self, db=""):
        global modules_prefix
        global execute_custom_queries
        global custom_queries

        # Initialize modules
        modules = []

        # Run if enabled execute custom queries
        if execute_custom_queries == 1:

            # Run each custom query
            for custom_query in custom_queries:

                # Run if target database match
                if "all" in custom_query["target_databases"] or len(custom_query["target_databases"]) == 0 or db in custom_query["target_databases"]:
                    
                    # Skipt if empty required parameters
                    if "target" not in custom_query or not custom_query["target"]:
                        continue
                    if "name" not in custom_query or not custom_query["name"]:
                        continue

                    # Reset error
                    error = ""
                    # Reset result
                    data = ""

                    # Prepare parameters
                    sql = custom_query["target"]
                    sql = re.sub(r'\$__self_dbname', db, sql)
                    
                    module_name = custom_query["name"]
                    module_name = re.sub(r'\$__self_dbname', db, module_name)
                    
                    desc = custom_query["description"] if "description" in custom_query else ""
                    datatype = custom_query["datatype"] if "datatype" in custom_query else "generic_data_string"

                    # Set single query
                    if "operation" not in custom_query or not custom_query["operation"]:
                        single_value=False
                    elif custom_query["operation"] == "value":
                        single_value=True
                    else:
                        single_value=False

                    # Adjust module type if needed
                    if(single_value == False):
                        datatype = "generic_data_string"

                    # Run query
                    rows, status, err = self.run_query(sql, single_value=single_value, db=db)

                    # Get query data
                    if rows is not None:
                        if isinstance(rows, list):
                            for row in rows:
                                for key, value in row.items():
                                    data += str(value) + "|"
                                # Remove last pipe
                                data = data[:-1]
                                data += "\n"
                        else:
                            data = rows

                    # Adjust data
                    if data == "" and "string" in datatype:
                        data = "No output."

                    # Verify query status and set description
                    if status == False:
                        desc = "Failed to execute query: " + err;
                    elif desc == "":
                        desc = "Execution OK"

                    modules.append({
                        "name": get_module_name(module_name),
                        "type": datatype,
                        "data": data,
                        "description": desc,
                        "min_critical": custom_query["min_critical"] if "min_critical" in custom_query else "",
                        "max_critical": custom_query["max_critical"] if "max_critical" in custom_query else "",
                        "min_warning": custom_query["min_warning"] if "min_warning" in custom_query else "",
                        "max_warning": custom_query["max_warning"] if "max_warning" in custom_query else "",
                        "critical_inverse": custom_query["critical_inverse"] if "critical_inverse" in custom_query else "",
                        "warning_inverse": custom_query["warning_inverse"] if "warning_inverse" in custom_query else "",
                        "str_warning": custom_query["str_warning"] if "str_warning" in custom_query else "",
                        "str_critical": custom_query["str_critical"] if "str_critical" in custom_query else "",
                        "module_interval":custom_query["module_interval"] if "module_interval" in custom_query else ""
                    })

        return modules

    def get_modules(self):
        # Initialize modules
        modules = []
        
        # Get statistics modules
        modules += self.get_statistics()
        # Get custom queries modules
        modules += self.get_custom_queries()

        return modules

#############
## THREADS ##
#############

####
# Function per agent
###########################################
def monitor_items(thread_agents=[]):
    global target_agents
    global interval
    global agents_group_id

    for thread_agent in thread_agents:
        # Get target agent
        agent = ""
        if 0 <= thread_agent["id"] < len(target_agents):
            agent = target_agents[thread_agent["id"]]
    
        # Initialize modules
        modules=[]
    
        # Get DB object
        db_object = RemoteDB(thread_agent["db_target"], agent)
    
        # Add connection module
        modules.append({
            "name"        : get_module_name(db_object.type+" connection"),
            "type"        : "generic_proc",
            "description" : db_object.type+" availability",
            "data"        : db_object.connected
        })
    
        # Get global connection modules if connected
        if(db_object.connected == 1):
            modules += db_object.get_modules()
    
            # Get engine databases modules
            if scan_databases == 1:
                modules += get_databases_modules(db_object)
    
        # Add new monitoring data
        add_monitoring_data({
            "agent_data"  : {
                "agent_name"  : db_object.agent,
                "agent_alias" : db_object.agent,
                "os"          : db_object.type,
                "os_version"  : db_object.version,
                "interval"    : interval,
                "id_group"    : agents_group_id,
                "address"     : db_object.host,
                "description" : "",
            },
            "module_data" : modules
        })
        add_summary_value("Total agents", 1)
        add_summary_value("Target agents", 1)
    
        # Disconnect from target
        db_object.disconnect()


####
# Function per thread
###########################################
def monitor_threads():
    global q

    thread_agents=q.get()
    try:
        monitor_items(thread_agents)
        q.task_done()
    except Exception as e:
        q.task_done()
        set_error_level(1)
        add_info_value("Error while runing single thread: "+str(e)+"\n")


##########
## MAIN ##
##########

# Parse arguments
parser = argparse.ArgumentParser(description= "", formatter_class=argparse.RawTextHelpFormatter)
parser.add_argument('--conf', help='Path to configuration file', metavar='<conf_file>', required=True)
parser.add_argument('--target_databases', help='Path to target databases file', metavar='<databases_file>', required=True)
parser.add_argument('--target_agents', help='Path to target agents file', metavar='<agents_file>', required=False)
parser.add_argument('--custom_queries', help='Path to custom queries file', metavar='<custom_queries_file>', required=False)
args = parser.parse_args()

# Parse configuration file
config = configparser.ConfigParser()
try:
    config.read_string('[CONF]\n' + open(args.conf).read())
except Exception as e:
    set_error_level(1)
    set_info_value("Error while reading configuration file file: "+str(e)+"\n")
    print_output()

agents_group_id        = param_int(parse_parameter(config, agents_group_id, "agents_group_id"))
interval               = param_int(parse_parameter(config, interval, "interval"))
user                   = parse_parameter(config, user, "user")
password               = parse_parameter(config, password, "password")
threads                = param_int(parse_parameter(config, threads, "threads"))
modules_prefix         = parse_parameter(config, modules_prefix, "modules_prefix")
execute_custom_queries = param_int(parse_parameter(config, execute_custom_queries, "execute_custom_queries"))
analyze_connections    = param_int(parse_parameter(config, analyze_connections, "analyze_connections"))
scan_databases         = param_int(parse_parameter(config, scan_databases, "scan_databases"))
agent_per_database     = param_int(parse_parameter(config, agent_per_database, "agent_per_database"))
db_agent_prefix        = parse_parameter(config, db_agent_prefix, "db_agent_prefix")
innodb_stats           = param_int(parse_parameter(config, innodb_stats, "innodb_stats"))
engine_uptime          = param_int(parse_parameter(config, engine_uptime, "engine_uptime"))
query_stats            = param_int(parse_parameter(config, query_stats, "query_stats"))
cache_stats            = param_int(parse_parameter(config, cache_stats, "cache_stats"))

# Parse rest of files
t_file = args.target_databases
a_file = args.target_agents
cq_file = args.custom_queries

# Parse DB targets
if t_file:
    try:
        lines = open(t_file, "r")
        for line in lines:
            line = line.strip()
            # SKIP EMPTY AND COMMENTED LINES
            if not line:
                continue
            if line == "\n":
                continue
            if line[0] == '#':
                continue

            db_targets += [element.strip() for element in line.split(",")]
        lines = []

    except Exception as e:
        set_error_level(1)
        add_info_value("Error while reading DB targets file: "+str(e)+"\n")

# Parse target agents
if a_file:
    try:
        lines = open(a_file, "r")
        for line in lines:
            line = line.strip()
            # SKIP EMPTY AND COMMENTED LINES
            if not line:
                continue
            if line == "\n":
                continue
            if line[0] == '#':
                continue

            target_agents += [element.strip() for element in line.split(",")]
        lines = []

    except Exception as e:
        set_error_level(1)
        add_info_value("Error while reading target agents file: "+str(e)+"\n")

# Parse custom queries
if cq_file:
    try:
        custom_query = {}
        save = False

        lines = open(cq_file, "r")
        for line in lines:
            line = line.strip()
            # SKIP EMPTY AND COMMENTED LINES
            if not line:
                continue
            if line == "\n":
                continue
            if line[0] == '#':
                continue

            # Start parsing module
            if line == "check_begin":
                save = True
                continue

            # Read next line until new module
            if save == False:
                continue

            # End parsing module
            if line == "check_end":
                if "target_databases" not in custom_query:
                    custom_query["target_databases"] = ["all"]
                custom_queries.append(custom_query)
                custom_query = {}
                save = False
                continue

            # Get line key value pair
            key, value = [element.strip() for element in line.split(maxsplit=1)]

            # Add target databases to query
            if key == "target_databases":
                custom_query["target_databases"] = [element.strip() for element in value.split(",")]
                continue

            # Skip not select queries
            if key == "target" and not re.search(r'^select', value, re.IGNORECASE):
                add_info_value("Removed ["+value+"] from custom queries, only select queries are allowed.\n")
                continue

            # Add other parameters to query
            custom_query[key] = value
        lines = []

    except Exception as e:
        set_error_level(1)
        add_info_value("Error while reading custom queries file: "+str(e)+"\n")

# Verify required arguments
required_params = True
if not user:
    add_info_value("Parameter [user] not defined\n")
    required_params = False
if not password:
    add_info_value("Parameter [password] not defined\n")
    required_params = False
if not db_targets:
    add_info_value("Database targets not defined\n")
    required_params = False

if required_params == False:
    set_error_level(1)
    print_output()

# Initialize summary
set_summary_value("Total agents", 0)
set_summary_value("Target agents", 0)
set_summary_value("Databases agents", 0)
set_summary_value("Targets up", 0)
set_summary_value("Targets down", 0)

# Assign threads
if threads > len(db_targets):
    threads = len(db_targets)

if threads < 1:
    threads = 1

# Distribute agents per thread
agents_per_thread = []
thread = 0
i = 0
for db_target in db_targets:
    if not 0 <= thread < len(agents_per_thread):
        agents_per_thread.append([])
    
    agents_per_thread[thread].append({
            "id": i,
            "db_target": db_target
        })

    thread += 1
    if thread >= threads:
        thread=0

    i += 1

# Run threads
try:
    q=Queue()
    for n_thread in range(threads) :
        q.put(agents_per_thread[n_thread])

    run_threads = []
    for n_thread in range(threads):
        t = Thread(target=monitor_threads)
        t.daemon=True
        t.start()
        run_threads.append(t)

    for t in run_threads:
        t.join()

    q.join()

except Exception as e:
    add_info_value("Error while running threads: "+str(e)+"\n")
    set_error_level(1)

# Print output and exit script
print_output()