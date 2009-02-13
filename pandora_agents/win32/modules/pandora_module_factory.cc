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
#include "pandora_module_freememory.h"
#include "pandora_module_cpuusage.h"
#include "pandora_module_odbc.h"
#include "pandora_module_logevent.h"
#include "pandora_module_wmiquery.h"
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
#define TOKEN_FREEMEMORY    ("module_freememory")
#define TOKEN_CPUUSAGE      ("module_cpuusage ")
#define TOKEN_ODBC          ("module_odbc ")
#define TOKEN_MAX           ("module_max ")
#define TOKEN_MIN           ("module_min ")
#define TOKEN_DESCRIPTION   ("module_description ")
#define TOKEN_ODBC_QUERY    ("module_odbc_query ")
#define TOKEN_LOGEVENT      ("module_logevent")
#define TOKEN_SOURCE        ("module_source ")
#define TOKEN_EVENTTYPE     ("module_eventtype ")
#define TOKEN_EVENTCODE     ("module_eventcode ")
#define TOKEN_PATTERN       ("module_pattern ")
#define TOKEN_ASYNC         ("module_async")
#define TOKEN_WATCHDOG      ("module_watchdog ")
#define TOKEN_START_COMMAND ("module_start_command ")
#define TOKEN_WMIQUERY      ("module_wmiquery ")
#define TOKEN_WMICOLUMN     ("module_wmicolumn ")
#define TOKEN_RETRIES       ("module_retries ")
#define TOKEN_STARTDELAY    ("module_startdelay ")
#define TOKEN_RETRYDELAY    ("module_retrydelay ")

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
	string                 module_freedisk, module_cpuusage, module_odbc;
	string                 module_odbc_query, module_dsn, module_freememory;
	string                 module_logevent, module_source, module_eventtype, module_eventcode;
	string                 module_pattern, module_async;
	string                 module_watchdog, module_start_command;
	string                 module_wmiquery, module_wmicolumn;
	Pandora_Module        *module;
	bool                   numeric;
	Module_Type            type;
	
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
	module_odbc          = "";
	module_logevent      = "";
	module_source        = "";
	module_eventtype     = "";
	module_eventcode   = "";
	module_pattern       = "";
	module_async         = "";
	module_watchdog      = "";
	module_start_command = "";
	module_wmiquery      = "";
	module_wmicolumn     = "";
	module_retries       = "";
	module_startdelay    = "";
	module_retrydelay    = "";

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
		if (module_freememory == "") {
			module_freememory = parseLine (line, TOKEN_FREEMEMORY);
		}
		if (module_cpuusage == "") {
			module_cpuusage = parseLine (line, TOKEN_CPUUSAGE);
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

		iter++;
	}

	/* Create module objects */
	if (module_exec != "") {
		module = new Pandora_Module_Exec (module_name,
						  module_exec);
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
				module_proc->setRetries (atoi(module_retries));
				module_proc->setStartDelay (atoi(module_startdelay));
				module_proc->setRetryDelay (atoi(module_retrydelay));
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
	} else if (module_freememory != "") {
		module = new Pandora_Module_Freememory (module_name);
	} else if (module_cpuusage != "") {
		int cpu_id;

		try {
			cpu_id = strtoint (module_cpuusage);
		} catch (Invalid_Conversion e) {
			pandoraLog ("Invalid CPU id '%s' on module_cpuusage %s",
				    module_name.c_str ());
			return NULL;
		}
		
		module = new Pandora_Module_Cpuusage (module_name,
						      cpu_id);

	} else if (module_odbc != "") {
		module = new Pandora_Module_Odbc (module_name,
						  module_odbc,
						  module_odbc_query);
	} else if (module_logevent != "") {
		module = new Pandora_Module_Logevent (module_name,
						      module_source,
						      module_eventtype,
						      module_eventcode,
						      module_pattern);
	} else if (module_wmiquery != "") {
		module = new Pandora_Module_WMIQuery (module_name,
						      module_wmiquery, module_wmicolumn);
	} else {
		return NULL;
	}

	if (module_description != "") {
		module->setDescription (module_description);
	}
	
	if (module_async != "") {
		module->setAsync (true);
	}
	
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
	
	return module;
}
