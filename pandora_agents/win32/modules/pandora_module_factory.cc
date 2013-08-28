/* Defines a factory of Pandora modules based on the module definition

   Copyright (C) 2006 Artica ST.
   Written by Esteban Sanchez.

   This program is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 2, or (at your option)
   any later version.

   This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License along
   with this program; if not, write to the Free Software Foundation,
   Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
*/

#include "pandora_module_factory.h"
#include "pandora_module.h"
#include "pandora_module_exec.h"
#include "pandora_module_proc.h"
#include "pandora_module_service.h"
#include "pandora_module_freedisk.h"
#include "pandora_module_freedisk_percent.h"
#include "pandora_module_freememory.h"
#include "pandora_module_freememory_percent.h"
#include "pandora_module_cpuusage.h"
#include "pandora_module_inventory.h"
#include "pandora_module_odbc.h"
#include "pandora_module_logevent.h"
#include "pandora_module_wmiquery.h"
#include "pandora_module_perfcounter.h"
#include "pandora_module_tcpcheck.h"
#include "pandora_module_regexp.h"
#include "pandora_module_plugin.h"
#include "pandora_module_ping.h"
#include "pandora_module_snmpget.h"
#include "../pandora_strutils.h"
#include <list>

using namespace Pandora;
using namespace Pandora_Modules;
using namespace Pandora_Strutils;

#define TOKEN_NAME          ("module_name ")
#define TOKEN_TYPE          ("module_type ")
#define TOKEN_INTERVAL      ("module_interval ")
#define TOKEN_EXEC          ("module_exec ")
#define TOKEN_PROC          ("module_proc ")
#define TOKEN_SERVICE       ("module_service ")
#define TOKEN_FREEDISK      ("module_freedisk ")
#define TOKEN_FREEDISK_PERCENT      ("module_freepercentdisk ")
#define TOKEN_FREEMEMORY    ("module_freememory")
#define TOKEN_FREEMEMORY_PERCENT    ("module_freepercentmemory")
#define TOKEN_CPUUSAGE      ("module_cpuusage ")
#define TOKEN_INVENTORY     ("module_inventory")
#define TOKEN_ODBC          ("module_odbc ")
#define TOKEN_MAX           ("module_max ")
#define TOKEN_MIN           ("module_min ")
#define TOKEN_POST_PROCESS  ("module_postprocess ")
#define TOKEN_MIN_CRITICAL  ("module_min_critical ")
#define TOKEN_MAX_CRITICAL  ("module_max_critical ")
#define TOKEN_MIN_WARNING   ("module_min_warning ")
#define TOKEN_MAX_WARNING   ("module_max_warning ")
#define TOKEN_DISABLED      ("module_disabled ")
#define TOKEN_MIN_FF_EVENT  ("module_min_ff_event ")
#define TOKEN_DESCRIPTION   ("module_description ")
#define TOKEN_ODBC_QUERY    ("module_odbc_query ")
#define TOKEN_LOGEVENT      ("module_logevent")
#define TOKEN_SOURCE        ("module_source ")
#define TOKEN_EVENTTYPE     ("module_eventtype ")
#define TOKEN_EVENTCODE     ("module_eventcode ")
#define TOKEN_PATTERN       ("module_pattern ")
#define TOKEN_APPLICATION   ("module_application ")
#define TOKEN_ASYNC         ("module_async")
#define TOKEN_WATCHDOG      ("module_watchdog ")
#define TOKEN_START_COMMAND ("module_start_command ")
#define TOKEN_WMIQUERY      ("module_wmiquery ")
#define TOKEN_WMICOLUMN     ("module_wmicolumn ")
#define TOKEN_RETRIES       ("module_retries ")
#define TOKEN_STARTDELAY    ("module_startdelay ")
#define TOKEN_RETRYDELAY    ("module_retrydelay ")
#define TOKEN_PERFCOUNTER   ("module_perfcounter ")
#define TOKEN_COOKED        ("module_cooked ")
#define TOKEN_TCPCHECK      ("module_tcpcheck ")
#define TOKEN_PORT          ("module_port ")
#define TOKEN_TIMEOUT       ("module_timeout ")
#define TOKEN_REGEXP        ("module_regexp ")
#define TOKEN_PLUGIN        ("module_plugin ")
#define TOKEN_SAVE          ("module_save ")
#define TOKEN_CONDITION     ("module_condition ")
#define TOKEN_CRONTAB       ("module_crontab ")
#define TOKEN_CRONINTERVAL  ("module_cron_interval ")
#define TOKEN_PRECONDITION  ("module_precondition ")
#define TOKEN_NOSEEKEOF     ("module_noseekeof ")
#define TOKEN_PING          ("module_ping ")
#define TOKEN_PING_COUNT    ("module_ping_count ")
#define TOKEN_PING_TIMEOUT  ("module_ping_timeout ")
#define TOKEN_SNMPGET       ("module_snmpget")
#define TOKEN_SNMPVERSION   ("module_snmp_version ")
#define TOKEN_SNMPCOMMUNITY ("module_snmp_community ")
#define TOKEN_SNMPAGENT     ("module_snmp_agent ")
#define TOKEN_SNMPOID       ("module_snmp_oid ")
#define TOKEN_ADVANCEDOPTIONS ("module_advanced_options ")
#define TOKEN_MODULE_GROUP ("module_group ")

string
parseLine (string line, string token) {
	unsigned int pos;
	string retstr = "";
	
	pos = line.find (token);
	if (pos == 0) {
		retstr = line.substr (token.length ());
		if (retstr == "") {
			retstr = " ";
		}
	}

	return retstr;
}

/** 
 * Creates a Pandora_Module object based on a string definition.
 *
 * @param definition Module definition readed from the configuration file.
 * 
 * @return A new Pandora_Module object. NULL if the definition is
 *         incorrect.
 */
Pandora_Module *
Pandora_Module_Factory::getModuleFromDefinition (string definition) {
	list<string>           tokens;
	list<string>::iterator iter;
	string                 module_name, module_type, module_exec;
	string                 module_min, module_max, module_description;
	string                 module_interval, module_proc, module_service;
	string                 module_freedisk, module_cpuusage, module_inventory, module_odbc;
	string                 module_freedisk_percent, module_freememory_percent;
	string                 module_odbc_query, module_dsn, module_freememory;
	string                 module_logevent, module_source, module_eventtype, module_eventcode;
	string                 module_pattern, module_application, module_async;
	string                 module_watchdog, module_start_command;
	string                 module_wmiquery, module_wmicolumn;
	string                 module_retries, module_startdelay, module_retrydelay;
	string                 module_perfcounter, module_tcpcheck;
	string                 module_port, module_timeout, module_regexp;
	string                 module_plugin, module_save, module_condition, module_precondition;
	string                 module_crontab, module_cron_interval, module_post_process;
	string                 module_min_critical, module_max_critical, module_min_warning, module_max_warning;
	string                 module_disabled, module_min_ff_event, module_noseekeof;
	string                 module_ping, module_ping_count, module_ping_timeout;
	string                 module_snmpget, module_snmp_version, module_snmp_community, module_snmp_agent, module_snmp_oid;
	string                 module_advanced_options, module_cooked, module_group;
	Pandora_Module        *module;
	bool                   numeric;
	Module_Type            type;
	long                    agent_interval;
	list<string>           condition_list;
	list<string>           precondition_list;
	list<string>::iterator condition_iter;
	list<string>::iterator precondition_iter;

	module_name          = "";
	module_type          = "";
	module_min           = "";
	module_max           = "";
	module_description   = "";
	module_interval      = "";
	module_exec          = "";
	module_proc          = "";
	module_service       = "";
	module_odbc          = "";
	module_odbc_query    = "";
	module_logevent      = "";
	module_source        = "";
	module_eventtype     = "";
	module_eventcode     = "";
	module_pattern       = "";
	module_application   = "";
	module_async         = "";
	module_watchdog      = "";
	module_start_command = "";
	module_wmiquery      = "";
	module_wmicolumn     = "";
	module_retries       = "";
	module_startdelay    = "";
	module_retrydelay    = "";
	module_perfcounter   = "";
	module_tcpcheck      = "";
	module_port          = "";
	module_timeout       = "";
	module_regexp        = "";
	module_plugin        = "";
	module_save          = "";
	module_condition     = "";
	module_crontab       = "";
	module_cron_interval = "";
	module_post_process  = "";
	module_precondition  = "";
	module_min_critical  = "";
	module_max_critical  = "";
	module_min_warning   = "";
	module_max_warning   = "";
	module_disabled      = "";
	module_min_ff_event  = "";
	module_noseekeof     = "";
	module_ping          = "";
	module_ping_count    = "";
	module_ping_timeout  = "";
	module_snmpget       = "";
    module_snmp_version  = "";
    module_snmp_community = "";
    module_snmp_agent    = "";
    module_snmp_oid      = "";
    module_advanced_options = "";
    module_cooked        = "";
	module_group       = "";
    
	stringtok (tokens, definition, "\n");
	
	/* Pick the first and the last value of the token list */
	iter = tokens.begin ();
	while (iter != tokens.end()) {
		string line;
		
		line = trim (*iter);

		if (module_name == "") {
			module_name = parseLine (line, TOKEN_NAME);
		}
		if (module_type == "") {
			module_type = parseLine (line, TOKEN_TYPE);
		}
		if (module_precondition == "") {
			module_precondition = parseLine (line, TOKEN_PRECONDITION);
			
			/* Queue the precondition and keep looking for more */
			if (module_precondition != "") {
				precondition_list.push_back (module_precondition);
				module_precondition = "";
			}
		}
		if (module_interval == "") {
			module_interval = parseLine (line, TOKEN_INTERVAL);
		}
		if (module_exec == "") {
			module_exec = parseLine (line, TOKEN_EXEC);
		}
		if (module_proc == "") {
			module_proc = parseLine (line, TOKEN_PROC);
		}
		if (module_service == "") {
			module_service = parseLine (line, TOKEN_SERVICE);
		}
		if (module_freedisk == "") {
			module_freedisk = parseLine (line, TOKEN_FREEDISK);
		}
		if (module_freedisk_percent == "") {
			module_freedisk_percent = parseLine (line, TOKEN_FREEDISK_PERCENT);
		}
		if (module_freememory == "") {
			module_freememory = parseLine (line, TOKEN_FREEMEMORY);
		}
		if (module_freememory_percent == "") {
			module_freememory_percent = parseLine (line, TOKEN_FREEMEMORY_PERCENT);
		}
		if (module_cpuusage == "") {
			module_cpuusage = parseLine (line, TOKEN_CPUUSAGE);
		}
		if (module_inventory == "") {
			module_inventory = parseLine (line, TOKEN_INVENTORY);
		}
		if (module_odbc == "") {
			module_odbc = parseLine (line, TOKEN_ODBC);
		}
		if (module_max == "") {
			module_max = parseLine (line, TOKEN_MAX);
		}
		if (module_min == "") {
			module_min = parseLine (line, TOKEN_MIN);
		}
		if (module_post_process == "") {
			module_post_process = parseLine (line, TOKEN_POST_PROCESS);
		}
		if (module_min_critical == "") {
			module_min_critical = parseLine (line, TOKEN_MIN_CRITICAL);
		}
		if (module_max_critical == "") {
			module_max_critical = parseLine (line, TOKEN_MAX_CRITICAL);
		}
		if (module_min_warning == "") {
			module_min_warning = parseLine (line, TOKEN_MIN_WARNING);
		}
		if (module_max_warning == "") {
			module_max_warning = parseLine (line, TOKEN_MAX_WARNING);
		}
		if (module_disabled == "") {
			module_disabled = parseLine (line, TOKEN_DISABLED);
		}
		if (module_min_ff_event == "") {
			module_min_ff_event = parseLine (line, TOKEN_MIN_FF_EVENT);
		}
		if (module_description == "") {
			module_description = parseLine (line, TOKEN_DESCRIPTION);
		}
		if (module_odbc_query == "") {
			module_odbc_query = parseLine (line, TOKEN_ODBC_QUERY);
		}
		if (module_logevent == "") {
			module_logevent = parseLine (line, TOKEN_LOGEVENT);
		}
		if (module_source == "") {
			module_source = parseLine (line, TOKEN_SOURCE);
		}
		if (module_eventtype == "") {
			module_eventtype = parseLine (line, TOKEN_EVENTTYPE);
		}
		if (module_eventcode == "") {
			module_eventcode = parseLine (line, TOKEN_EVENTCODE);
		}
		if (module_pattern == "") {
			module_pattern = parseLine (line, TOKEN_PATTERN);
		}
		if (module_application == "") {
			module_application = parseLine (line, TOKEN_APPLICATION);
		}
		if (module_async == "") {
			module_async = parseLine (line, TOKEN_ASYNC);
		}
		if (module_start_command == "") {
			module_start_command = parseLine (line, TOKEN_START_COMMAND);
		}
		if (module_watchdog == "") {
			module_watchdog = parseLine (line, TOKEN_WATCHDOG);
		}
		if (module_wmiquery == "") {
			module_wmiquery = parseLine (line, TOKEN_WMIQUERY);
		}
		if (module_wmicolumn == "") {
			module_wmicolumn = parseLine (line, TOKEN_WMICOLUMN);
		}
		if (module_retries == "") {
			module_retries = parseLine (line, TOKEN_RETRIES);
		}
		if (module_startdelay == "") {
			module_startdelay = parseLine (line, TOKEN_STARTDELAY);
		}
		if (module_retrydelay == "") {
			module_retrydelay = parseLine (line, TOKEN_RETRYDELAY);
		}
		if (module_perfcounter == "") {
			module_perfcounter = parseLine (line, TOKEN_PERFCOUNTER);
		}
		if (module_tcpcheck == "") {
			module_tcpcheck = parseLine (line, TOKEN_TCPCHECK);
		}
		if (module_port == "") {
			module_port = parseLine (line, TOKEN_PORT);
		}
		if (module_timeout == "") {
			module_timeout = parseLine (line, TOKEN_TIMEOUT);
		}
		if (module_regexp == "") {
			module_regexp = parseLine (line, TOKEN_REGEXP);
		}
		if (module_plugin == "") {
			module_plugin = parseLine (line, TOKEN_PLUGIN);
		}
		if (module_save == "") {
			module_save = parseLine (line, TOKEN_SAVE);
		}
		if (module_condition == "") {
			module_condition = parseLine (line, TOKEN_CONDITION);
			
			/* Queue the condition and keep looking for more */
			if (module_condition != "") {
				condition_list.push_back (module_condition);
				module_condition = "";
			}
		}
		if (module_crontab == "") {
			module_crontab = parseLine (line, TOKEN_CRONTAB);
		}
		if (module_cron_interval == "") {
			module_cron_interval = parseLine (line, TOKEN_CRONINTERVAL);
		}
		if (module_noseekeof == "") {
			module_noseekeof = parseLine (line, TOKEN_NOSEEKEOF);
		}
		if (module_ping == "") {
			module_ping = parseLine (line, TOKEN_PING);
		}
		if (module_ping_count == "") {
			module_ping_count = parseLine (line, TOKEN_PING_COUNT);
		}
		if (module_ping_timeout == "") {
			module_ping_timeout = parseLine (line, TOKEN_PING_TIMEOUT);
		}
		if (module_snmpget == "") {
			module_snmpget = parseLine (line, TOKEN_SNMPGET);
		}
		if (module_snmp_version == "") {
			module_snmp_version = parseLine (line, TOKEN_SNMPVERSION);
		}
		if (module_snmp_community == "") {
			module_snmp_community = parseLine (line, TOKEN_SNMPCOMMUNITY);
		}
		if (module_snmp_agent == "") {
			module_snmp_agent = parseLine (line, TOKEN_SNMPAGENT);
		}
		if (module_snmp_oid == "") {
			module_snmp_oid = parseLine (line, TOKEN_SNMPOID);
		}
		if (module_advanced_options == "") {
			module_advanced_options = parseLine (line, TOKEN_ADVANCEDOPTIONS);
		}
		if (module_cooked == "") {
			module_cooked = parseLine (line, TOKEN_COOKED);
		}
		if (module_group == "") {
			module_group = parseLine (line, TOKEN_MODULE_GROUP);
		}
		iter++;
	}

	/* Create module objects */
	if (module_exec != "") {
		module = new Pandora_Module_Exec (module_name,
						  module_exec);
		if (module_timeout != "") {
			module->setTimeout (atoi (module_timeout.c_str ()));
		}
		
	} else if (module_proc != "") {
		module = new Pandora_Module_Proc (module_name,
						  module_proc);
		if (module_watchdog != "") {
			bool                 enabled;
			
			enabled = is_enabled (module_watchdog);
			if (enabled) {
				if (module_start_command == "") {
					pandoraLog ("Module \"%s\" is marked to be watchdog but no recover command was set. "
						    "Please add a new token 'module_start_command c:\\command_to_recover.exe'",
						    module_name.c_str ());
					delete module;
					return NULL;
				}
				
				Pandora_Module_Proc *module_proc;
				
				module_proc = (Pandora_Module_Proc *) module;
				module_proc->setWatchdog (true);
				module_proc->setStartCommand (module_start_command);
				module_proc->setRetries (atoi(module_retries.c_str ()));
				module_proc->setStartDelay (atoi(module_startdelay.c_str ()));
				module_proc->setRetryDelay (atoi(module_retrydelay.c_str ()));
			}
		}
	} else if (module_service != "") {
		module = new Pandora_Module_Service (module_name,
						     module_service);
		if (module_watchdog != "") {
			Pandora_Module_Service *module_service;
			
			module_service = (Pandora_Module_Service *) module;
			module_service->setWatchdog (is_enabled (module_watchdog));
		}
	} else if (module_freedisk != "") {
		module = new Pandora_Module_Freedisk (module_name,
						      module_freedisk);
	} else if (module_freedisk_percent != "") {
		module = new Pandora_Module_Freedisk_Percent (module_name,
						      module_freedisk_percent);
	} else if (module_freememory != "") {
		module = new Pandora_Module_Freememory (module_name);
	} else if (module_freememory_percent != "") {
		module = new Pandora_Module_Freememory_Percent (module_name);
	} else if (module_cpuusage != "") {
		int cpu_id;

		try {
			cpu_id = strtoint (module_cpuusage);
		} catch (Invalid_Conversion e) {
			cpu_id = -1;
		}
		
		module = new Pandora_Module_Cpuusage (module_name,
						      cpu_id);

	} else if (module_inventory != "") {
		module = new Pandora_Module_Inventory (module_name, module_inventory);
	} else if (module_odbc != "") {
		module = new Pandora_Module_Odbc (module_name, module_odbc, module_odbc_query);
	} else if (module_logevent != "") {
		module = new Pandora_Module_Logevent (module_name,
						      module_source,
						      module_eventtype,
						      module_eventcode,
						      module_pattern,
						      module_application);
	} else if (module_wmiquery != "") {
		module = new Pandora_Module_WMIQuery (module_name,
						      module_wmiquery, module_wmicolumn);
	} else if (module_perfcounter != "") {
		module = new Pandora_Module_Perfcounter (module_name, module_perfcounter, module_cooked);
	} else if (module_tcpcheck != "") {
		module = new Pandora_Module_Tcpcheck (module_name, module_tcpcheck, module_port, module_timeout);
	} else if (module_regexp != "") {
		module = new Pandora_Module_Regexp (module_name, module_regexp, module_pattern, (unsigned char) atoi (module_noseekeof.c_str ()));
	} else if (module_plugin != "") {
		module = new Pandora_Module_Plugin (module_name, module_plugin);
	} else if (module_ping != "") {
		if (module_ping_count == "") {
			module_ping_count = "1";
		}
		if (module_ping_timeout == "") {
			module_ping_timeout = "1000";
		}

		module = new Pandora_Module_Ping (module_name, module_ping, module_ping_count, module_ping_timeout, module_advanced_options);
		if (module_timeout != "") {
			module->setTimeout (atoi (module_timeout.c_str ()));
		}
	} else if (module_snmpget != "") {
		if (module_snmp_version == "") {
			module_snmp_version = "1";
		}
		if (module_snmp_community == "") {
			module_snmp_community = "public";
		}
		if (module_snmp_agent == "") {
			module_snmp_agent = "localhost";
		}

		module = new Pandora_Module_SNMPGet (module_name, module_snmp_version, module_snmp_community, module_snmp_agent, module_snmp_oid, module_advanced_options);
		if (module_timeout != "") {
			module->setTimeout (atoi (module_timeout.c_str ()));
		}

	} else {
		return NULL;
	}

	/* Set module description */
	if (module_description != "") {
		module->setDescription (module_description);
	}

	/* Save module data as an environment variable */
	if (module_save != "") {
		module->setSave (module_save);
	}
	
	/* Async module */
	if (module_async != "") {
		module->setAsync (true);
	}

/* Module precondition */
	if (precondition_list.size () > 0) {
		precondition_iter = precondition_list.begin ();
		for (precondition_iter = precondition_list.begin ();
		     precondition_iter != precondition_list.end ();
		     precondition_iter++) {
			module->addPrecondition (*precondition_iter);
		}
	}
	
	/* Module condition */
	if (condition_list.size () > 0) {
		condition_iter = condition_list.begin ();
		for (condition_iter = condition_list.begin ();
		     condition_iter != condition_list.end ();
		     condition_iter++) {
			module->addCondition (*condition_iter);
		}
	}

	/* Module cron */
	if (module_crontab != "") {
		module->setCron (module_crontab);
		
		/* Set the cron interval */
		if (module_cron_interval != "") {
			module->setCronInterval (atoi (module_cron_interval.c_str ()));
		}
	}

	/* Plugins do not have a module type */
	if (module_plugin == "") {
		type = Pandora_Module::parseModuleTypeFromString (module_type);
		switch (type) {
		case TYPE_GENERIC_DATA:
		case TYPE_GENERIC_DATA_INC:
		case TYPE_GENERIC_PROC:
		case TYPE_ASYNC_DATA:
		case TYPE_ASYNC_PROC:
			module->setType (module_type);
			numeric = true;
			
			break;
		case TYPE_GENERIC_DATA_STRING:
		case TYPE_ASYNC_STRING:
			module->setType (module_type);
			numeric = false;
			
			break;
		default:
			pandoraDebug ("Bad module type \"%s\" while parsing %s module",
				      module_type.c_str (), module_name.c_str ());
			
			delete module;
			
			return NULL;
		}
	} else {
		module->setType	("generic_data_string");
		numeric = false;
	}

	if (numeric) {
		if (module_max != "") {
			try {
				int value = strtoint (module_max);
				
				module->setMax (value);
			} catch (Invalid_Conversion e) {
				pandoraLog ("Invalid max value %s for module %s",
					   module_max.c_str (),
					   module_name.c_str ());
			}
		}
		if (module_min != "") {
			try {
				int value = strtoint (module_min);
				
				module->setMin (value);
			} catch (Invalid_Conversion e) {
				pandoraLog ("Invalid min value %s for module %s",
					   module_min.c_str (),
					   module_name.c_str ());
			}
		}
	}
	
	if (module_interval != "") {
		int interval;
		
		try {
			interval = strtoint (module_interval);
			module->setInterval (interval);
		} catch (Invalid_Conversion e) {
			pandoraLog ("Invalid interval value \"%s\" for module %s",
				    module_interval.c_str (),
				    module_name.c_str ());
		}
	}

	if (module_post_process != "") {
		module->setPostProcess (module_post_process);
	}

	if (module_min_critical != "") {
		module->setMinCritical (module_min_critical);
	}

	if (module_max_critical != "") {
		module->setMaxCritical (module_max_critical);
	}

	if (module_min_warning != "") {
		module->setMinWarning (module_min_warning);
	}

	if (module_max_warning != "") {
		module->setMaxWarning (module_max_warning);
	}

	if (module_disabled != "") {
		module->setDisabled (module_disabled);
	}

	if (module_min_ff_event != "") {
		module->setMinFFEvent (module_min_ff_event);
	}

	if (module_group != "") {
		module->setModuleGroup (module_group);
	}

	return module;
}
