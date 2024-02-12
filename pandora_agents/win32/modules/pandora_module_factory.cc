/* Defines a factory of Pandora modules based on the module definition

   Copyright (c) 2006-2023 Pandora FMS.
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

#include "pandora_windows_service.h"
#include "pandora_module_factory.h"
#include "pandora_module.h"
#include "pandora_module_exec.h"
#include "pandora_module_exec_powershell.h"
#include "pandora_module_proc.h"
#include "pandora_module_service.h"
#include "pandora_module_freedisk.h"
#include "pandora_module_freedisk_percent.h"
#include "pandora_module_freememory.h"
#include "pandora_module_freememory_percent.h"
#include "pandora_module_cpuusage.h"
#include "pandora_module_inventory.h"
#include "pandora_module_logevent.h"
#include "pandora_module_logchannel.h"
#include "pandora_module_wmiquery.h"
#include "pandora_module_perfcounter.h"
#include "pandora_module_tcpcheck.h"
#include "pandora_module_regexp.h"
#include "pandora_module_plugin.h"
#include "pandora_module_ping.h"
#include "pandora_module_snmpget.h"
#include "../windows/pandora_wmi.h"
#include "../pandora_strutils.h"
#include "../misc/pandora_file.h"
#include "../pandora.h"
#include <list>
#include <cmath>

using namespace Pandora;
using namespace Pandora_Modules;
using namespace Pandora_Strutils;

#define TOKEN_NAME          ("module_name ")
#define TOKEN_TYPE          ("module_type ")
#define TOKEN_INTERVAL      ("module_interval ")
#define TOKEN_ABSOLUTEINTERVAL ("module_absoluteinterval ")
#define TOKEN_EXEC          ("module_exec ")
#define TOKEN_PROC          ("module_proc ")
#define TOKEN_SERVICE       ("module_service ")
#define TOKEN_FREEDISK      ("module_freedisk ")
#define TOKEN_FREEDISK_PERCENT      ("module_freepercentdisk ")
#define TOKEN_FREEMEMORY    ("module_freememory")
#define TOKEN_FREEMEMORY_PERCENT    ("module_freepercentmemory")
#define TOKEN_CPUUSAGE      ("module_cpuusage ")
#define TOKEN_INVENTORY     ("module_inventory")
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
#define TOKEN_LOGEVENT      ("module_logevent")
#define TOKEN_LOGCHANNEL    ("module_logchannel")
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
#define TOKEN_INTENSIVECONDITION ("module_intensive_condition ")
#define TOKEN_UNIT ("module_unit ")
#define TOKEN_MODULE_GROUP ("module_group ")
#define TOKEN_CUSTOM_ID ("module_custom_id ")
#define TOKEN_STR_WARNING ("module_str_warning ")
#define TOKEN_STR_CRITICAL ("module_str_critical ")
#define TOKEN_CRITICAL_INSTRUCTIONS ("module_critical_instructions ")
#define TOKEN_WARNING_INSTRUCTIONS ("module_warning_instructions ")
#define TOKEN_UNKNOWN_INSTRUCTIONS ("module_unknown_instructions ")
#define TOKEN_TAGS ("module_tags ")
#define TOKEN_CRITICAL_INVERSE ("module_critical_inverse ")
#define TOKEN_WARNING_INVERSE ("module_warning_inverse ")
#define TOKEN_QUIET ("module_quiet ")
#define TOKEN_MODULE_FF_INTERVAL ("module_ff_interval ")
#define TOKEN_MODULE_FF_TYPE ("module_ff_type ")
#define TOKEN_MACRO ("module_macro")
#define TOKEN_NATIVE_ENCODING ("module_native_encoding")
#define TOKEN_ALERT_TEMPLATE ("module_alert_template")
#define TOKEN_USER_SESSION ("module_user_session ")
#define TOKEN_WAIT_TIMEOUT ("module_wait_timeout ")
#define TOKEN_EXEC_POWERSHELL ("module_exec_powershell ")
	
string
parseLine (string line, string token) {
	int pos;
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
	string                 module_name, module_type, module_exec, module_exec_powershell;
	string                 module_min, module_max, module_description;
	string                 module_interval, module_absoluteinterval;
	string                 module_proc, module_service;
	string                 module_freedisk, module_cpuusage, module_inventory;
	string                 module_freedisk_percent, module_freememory_percent;
	string                 module_dsn, module_freememory;
	string                 module_logevent, module_source, module_eventtype, module_eventcode;
	string                 module_logchannel;
	string                 module_pattern, module_application, module_async;
	string                 module_watchdog, module_start_command, module_user_session;
	string                 module_wmiquery, module_wmicolumn;
	string                 module_retries, module_startdelay, module_retrydelay;
	string                 module_perfcounter, module_tcpcheck;
	string                 module_port, module_timeout, module_regexp;
	string                 module_plugin, module_save, module_condition, module_precondition;
	string                 module_crontab, module_post_process;
	string                 module_min_critical, module_max_critical, module_min_warning, module_max_warning;
	string                 module_disabled, module_min_ff_event, module_noseekeof;
	string                 module_ping, module_ping_count, module_ping_timeout;
	string                 module_snmpget, module_snmp_version, module_snmp_community, module_snmp_agent, module_snmp_oid;
	string                 module_advanced_options, module_cooked, module_intensive_condition;
	string                 module_unit, module_group, module_custom_id, module_str_warning, module_str_critical;
	string                 module_critical_instructions, module_warning_instructions, module_unknown_instructions, module_tags;
	string                 module_critical_inverse, module_warning_inverse, module_quiet, module_ff_interval;
	string                 module_native_encoding, module_alert_template, module_ff_type;
	string                 macro, module_wait_timeout;
	Pandora_Module        *module;
	bool                   numeric;
	Module_Type            type;
	long                    agent_interval;
	list<string>           macro_list;
	list<string>::iterator macro_iter;
	list<string>           condition_list, precondition_list, intensive_condition_list;
	list<string>::iterator condition_iter, precondition_iter, intensive_condition_iter;
	Pandora_Windows_Service *service = NULL;

	module_name          = "";
	module_type          = "";
	module_min           = "";
	module_max           = "";
	module_description   = "";
	module_interval      = "";
	module_absoluteinterval = "";
	module_exec          = "";
	module_proc          = "";
	module_service       = "";
	module_logevent      = "";
	module_logchannel    = "";
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
    module_intensive_condition = "";
	module_unit          = "";
	module_group         = "";
	module_custom_id     = "";
	module_str_warning   = "";
	module_str_critical  = "";
	module_critical_instructions = "";
	module_warning_instructions = "";
	module_unknown_instructions = "";
	module_tags          = "";
	module_critical_inverse = "";
	module_warning_inverse = "";
	module_quiet         = "";
	module_ff_interval   = "";
	module_ff_type   = "";
	module_native_encoding	 = "";
	module_alert_template	 = "";
	module_user_session	 = "";
	macro   = "";
	module_wait_timeout = "";
	module_exec_powershell = "";
    
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
		if (module_absoluteinterval == "") {
			module_absoluteinterval = parseLine (line, TOKEN_ABSOLUTEINTERVAL);
		}
		if (module_exec == "") {
			module_exec = parseLine (line, TOKEN_EXEC);
		}
		if (module_exec_powershell == "") {
			module_exec_powershell = parseLine (line, TOKEN_EXEC_POWERSHELL);
		}
		if (module_wait_timeout == "") {
			module_wait_timeout = parseLine (line, TOKEN_WAIT_TIMEOUT);
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
		if (module_logevent == "") {
			module_logevent = parseLine (line, TOKEN_LOGEVENT);
		}
		if (module_logchannel == "") {
			module_logchannel = parseLine (line, TOKEN_LOGCHANNEL);
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
		if (module_intensive_condition == "") {
			module_intensive_condition = parseLine (line, TOKEN_INTENSIVECONDITION);

			/* Queue the condition and keep looking for more */
			if (module_intensive_condition != "") {
				intensive_condition_list.push_back (module_intensive_condition);
				module_intensive_condition = "";
			}
		}
		if (module_unit == "") {
			module_unit = parseLine (line, TOKEN_UNIT);
		}
		if (module_group == "") {
			module_group = parseLine (line, TOKEN_MODULE_GROUP);
		}
		if (module_custom_id == "") {
			module_custom_id = parseLine (line, TOKEN_CUSTOM_ID);
		}
		if (module_str_warning == "") {
			module_str_warning = parseLine (line, TOKEN_STR_WARNING);
		}
		if (module_str_critical == "") {
			module_str_critical = parseLine (line, TOKEN_STR_CRITICAL);
		}
		if (module_critical_instructions == "") {
			module_critical_instructions = parseLine (line, TOKEN_CRITICAL_INSTRUCTIONS);
		}
		if (module_warning_instructions == "") {
			module_warning_instructions = parseLine (line, TOKEN_WARNING_INSTRUCTIONS);
		}
		if (module_unknown_instructions == "") {
			module_unknown_instructions = parseLine (line, TOKEN_UNKNOWN_INSTRUCTIONS);
		}
		if (module_tags == "") {
			module_tags = parseLine (line, TOKEN_TAGS);
		}
		if (module_critical_inverse == "") {
			module_critical_inverse = parseLine (line, TOKEN_CRITICAL_INVERSE);
		}
		if (module_warning_inverse == "") {
			module_warning_inverse = parseLine (line, TOKEN_WARNING_INVERSE);
		}
		if (module_quiet == "") {
			module_quiet = parseLine (line, TOKEN_QUIET);
		}
		
		if (module_native_encoding == "") {
			module_native_encoding = parseLine (line, TOKEN_NATIVE_ENCODING);
		}
		
		if (module_ff_interval == "") {
			module_ff_interval = parseLine (line, TOKEN_MODULE_FF_INTERVAL);
		}

		if (module_ff_type == "") {
			module_ff_type = parseLine (line, TOKEN_MODULE_FF_TYPE);
		}
		
		if (module_alert_template == "") {
			module_alert_template = parseLine (line, TOKEN_ALERT_TEMPLATE);
			module_alert_template.erase (0,1);
		}		
		
		if (module_user_session == "") {
			module_user_session = parseLine (line, TOKEN_USER_SESSION);
		}
		
		if (macro == "") {
			macro = parseLine (line, TOKEN_MACRO);
			
			/* Queue the macro and keep looking for more */
			if (macro != "") {
				macro_list.push_back (macro);
				macro = "";
			}
		}
	
		iter++;
	}
	
	/* Subst macros */
	int pos, pos_macro;
	string macro_name, macro_value;
	
	if (macro_list.size () > 0) {
		macro_iter = macro_list.begin ();
		for (macro_iter = macro_list.begin ();
		     macro_iter != macro_list.end ();
		     macro_iter++) {
				
			macro_name = *macro_iter;
			
			// At this point macro_name is "macro_name macro_value"
			pos = macro_name.find (" ");
			if(pos != string::npos) {
				// Split name of the macro y value
				macro_value = macro_name.substr (pos + 1);
				macro_name.erase(pos, macro_name.size () - pos);
				
				// Replace macros
				if (module_name != "") {
					pos_macro = module_name.find(macro_name);
					if (pos_macro != string::npos){
						module_name.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_type != "") {
					pos_macro = module_type.find(macro_name);
					if (pos_macro != string::npos){
						module_type.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (precondition_list.size () > 0) {
					precondition_iter = precondition_list.begin ();
					for (precondition_iter = precondition_list.begin ();
					     precondition_iter != precondition_list.end ();
					     precondition_iter++) {

						pos_macro = precondition_iter->find(macro_name);
						if (pos_macro != string::npos){
							precondition_iter->replace(pos_macro, macro_name.size(), macro_value);
						}

					}
				}

				if (module_precondition != "") {
					pos_macro = module_precondition.find(macro_name);
					if (pos_macro != string::npos){
						module_precondition.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_interval != "") {
					pos_macro = module_interval.find(macro_name);
					if (pos_macro != string::npos){
						module_interval.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_absoluteinterval != "") {
					pos_macro = module_absoluteinterval.find(macro_name);
					if (pos_macro != string::npos){
						module_absoluteinterval.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_exec != "") {
					pos_macro = module_exec.find(macro_name);
					if (pos_macro != string::npos){
						module_exec.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_exec_powershell != "") {
					pos_macro = module_exec_powershell.find(macro_name);
					if (pos_macro != string::npos){
						module_exec_powershell.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_proc != "") {
					pos_macro = module_proc.find(macro_name);
					if (pos_macro != string::npos){
						module_proc.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_service != "") {
					pos_macro = module_service.find(macro_name);
					if (pos_macro != string::npos){
						module_service.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_freedisk != "") {
					pos_macro = module_freedisk.find(macro_name);
					if (pos_macro != string::npos){
						module_freedisk.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_freedisk_percent != "") {
					pos_macro = module_freedisk_percent.find(macro_name);
					if (pos_macro != string::npos){
						module_freedisk_percent.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_freememory != "") {
					pos_macro = module_freememory.find(macro_name);
					if (pos_macro != string::npos){
						module_freememory.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_freememory_percent != "") {
					pos_macro = module_freememory_percent.find(macro_name);
					if (pos_macro != string::npos){
						module_freememory_percent.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_cpuusage != "") {
					pos_macro = module_cpuusage.find(macro_name);
					if (pos_macro != string::npos){
						module_cpuusage.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_inventory != "") {
					pos_macro = module_inventory.find(macro_name);
					if (pos_macro != string::npos){
						module_inventory.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_max != "") {
					pos_macro = module_max.find(macro_name);
					if (pos_macro != string::npos){
						module_max.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_min != "") {
					pos_macro = module_min.find(macro_name);
					if (pos_macro != string::npos){
						module_min.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_post_process != "") {
					pos_macro = module_post_process.find(macro_name);
					if (pos_macro != string::npos){
						module_post_process.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_min_critical != "") {
					pos_macro = module_min_critical.find(macro_name);
					if (pos_macro != string::npos){
						module_min_critical.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_max_critical != "") {
					pos_macro = module_max_critical.find(macro_name);
					if (pos_macro != string::npos){
						module_max_critical.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_min_warning != "") {
					pos_macro = module_min_warning.find(macro_name);
					if (pos_macro != string::npos){
						module_min_warning.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_max_warning != "") {
					pos_macro = module_max_warning.find(macro_name);
					if (pos_macro != string::npos){
						module_max_warning.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_disabled != "") {
					pos_macro = module_disabled.find(macro_name);
					if (pos_macro != string::npos){
						module_disabled.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_min_ff_event != "") {
					pos_macro = module_min_ff_event.find(macro_name);
					if (pos_macro != string::npos){
						module_min_ff_event.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_description != "") {
					pos_macro = module_description.find(macro_name);
					if (pos_macro != string::npos){
						module_description.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_logevent != "") {
					pos_macro = module_logevent.find(macro_name);
					if (pos_macro != string::npos){
						module_logevent.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_logchannel != "") {
					pos_macro = module_logchannel.find(macro_name);
					if (pos_macro != string::npos){
						module_logchannel.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_source != "") {
					pos_macro = module_source.find(macro_name);
					if (pos_macro != string::npos){
						module_source.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_eventtype != "") {
					pos_macro = module_eventtype.find(macro_name);
					if (pos_macro != string::npos){
						module_eventtype.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_eventcode != "") {
					pos_macro = module_eventcode.find(macro_name);
					if (pos_macro != string::npos){
						module_eventcode.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_pattern != "") {
					pos_macro = module_pattern.find(macro_name);
					if (pos_macro != string::npos){
						module_pattern.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_application != "") {
					pos_macro = module_application.find(macro_name);
					if (pos_macro != string::npos){
						module_application.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_async != "") {
					pos_macro = module_async.find(macro_name);
					if (pos_macro != string::npos){
						module_async.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_start_command != "") {
					pos_macro = module_start_command.find(macro_name);
					if (pos_macro != string::npos){
						module_start_command.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_watchdog != "") {
					pos_macro = module_watchdog.find(macro_name);
					if (pos_macro != string::npos){
						module_watchdog.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_wmiquery != "") {
					pos_macro = module_wmiquery.find(macro_name);
					if (pos_macro != string::npos){
						module_wmiquery.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_wmicolumn != "") {
					pos_macro = module_wmicolumn.find(macro_name);
					if (pos_macro != string::npos){
						module_wmicolumn.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_retries != "") {
					pos_macro = module_retries.find(macro_name);
					if (pos_macro != string::npos){
						module_retries.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_startdelay != "") {
					pos_macro = module_startdelay.find(macro_name);
					if (pos_macro != string::npos){
						module_startdelay.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_retrydelay != "") {
					pos_macro = module_retrydelay.find(macro_name);
					if (pos_macro != string::npos){
						module_retrydelay.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_perfcounter != "") {
					pos_macro = module_perfcounter.find(macro_name);
					if (pos_macro != string::npos){
						module_perfcounter.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_tcpcheck != "") {
					pos_macro = module_tcpcheck.find(macro_name);
					if (pos_macro != string::npos){
						module_tcpcheck.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_port != "") {
					pos_macro = module_port.find(macro_name);
					if (pos_macro != string::npos){
						module_port.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_timeout != "") {
					pos_macro = module_timeout.find(macro_name);
					if (pos_macro != string::npos){
						module_timeout.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_regexp != "") {
					pos_macro = module_regexp.find(macro_name);
					if (pos_macro != string::npos){
						module_regexp.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_plugin != "") {
					pos_macro = module_plugin.find(macro_name);
					if (pos_macro != string::npos){
						module_plugin.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_save != "") {
					pos_macro = module_save.find(macro_name);
					if (pos_macro != string::npos){
						module_save.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (condition_list.size () > 0) {
					condition_iter = condition_list.begin ();
					for (condition_iter = condition_list.begin ();
						condition_iter != condition_list.end ();
						condition_iter++) {

						pos_macro = condition_iter->find(macro_name);
						if (pos_macro != string::npos){
							condition_iter->replace(pos_macro, macro_name.size(), macro_value);
						}
					}
				}

				if (module_crontab != "") {
					pos_macro = module_crontab.find(macro_name);
					if (pos_macro != string::npos){
						module_crontab.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_noseekeof != "") {
					pos_macro = module_noseekeof.find(macro_name);
					if (pos_macro != string::npos){
						module_noseekeof.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_ping != "") {
					pos_macro = module_ping.find(macro_name);
					if (pos_macro != string::npos){
						module_ping.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_ping_count != "") {
					pos_macro = module_ping_count.find(macro_name);
					if (pos_macro != string::npos){
						module_ping_count.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_ping_timeout != "") {
					pos_macro = module_ping_timeout.find(macro_name);
					if (pos_macro != string::npos){
						module_ping_timeout.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_snmpget != "") {
					pos_macro = module_snmpget.find(macro_name);
					if (pos_macro != string::npos){
						module_snmpget.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_snmp_version != "") {
					pos_macro = module_snmp_version.find(macro_name);
					if (pos_macro != string::npos){
						module_snmp_version.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_snmp_community != "") {
					pos_macro = module_snmp_community.find(macro_name);
					if (pos_macro != string::npos){
						module_snmp_community.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_snmp_agent != "") {
					pos_macro = module_snmp_agent.find(macro_name);
					if (pos_macro != string::npos){
						module_snmp_agent.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_snmp_oid != "") {
					pos_macro = module_snmp_oid.find(macro_name);
					if (pos_macro != string::npos){
						module_snmp_oid.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_advanced_options != "") {
					pos_macro = module_advanced_options.find(macro_name);
					if (pos_macro != string::npos){
						module_advanced_options.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_cooked != "") {
					pos_macro = module_cooked.find(macro_name);
					if (pos_macro != string::npos){
						module_cooked.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (intensive_condition_list.size () > 0) {
					intensive_condition_iter = intensive_condition_list.begin ();
					for (intensive_condition_iter = intensive_condition_list.begin ();
						intensive_condition_iter != intensive_condition_list.end ();
						intensive_condition_iter++) {
							
						pos_macro = intensive_condition_iter->find(macro_name);
						if (pos_macro != string::npos){
							intensive_condition_iter->replace(pos_macro, macro_name.size(), macro_value);
						}
					}
				}

				if (module_unit != "") {
					pos_macro = module_unit.find(macro_name);
					if (pos_macro != string::npos){
						module_unit.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_group != "") {
					pos_macro = module_group.find(macro_name);
					if (pos_macro != string::npos){
						module_group.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_custom_id != "") {
					pos_macro = module_custom_id.find(macro_name);
					if (pos_macro != string::npos){
						module_custom_id.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_str_warning != "") {
					pos_macro = module_str_warning.find(macro_name);
					if (pos_macro != string::npos){
						module_str_warning.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_str_critical != "") {
					pos_macro = module_str_critical.find(macro_name);
					if (pos_macro != string::npos){
						module_str_critical.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_critical_instructions != "") {
					pos_macro = module_critical_instructions.find(macro_name);
					if (pos_macro != string::npos){
						module_critical_instructions.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_warning_instructions != "") {
					pos_macro = module_warning_instructions.find(macro_name);
					if (pos_macro != string::npos){
						module_warning_instructions.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_unknown_instructions != "") {
					pos_macro = module_unknown_instructions.find(macro_name);
					if (pos_macro != string::npos){
						module_unknown_instructions.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_tags != "") {
					pos_macro = module_tags.find(macro_name);
					if (pos_macro != string::npos){
						module_tags.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_critical_inverse != "") {
					pos_macro = module_critical_inverse.find(macro_name);
					if (pos_macro != string::npos){
						module_critical_inverse.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_warning_inverse != "") {
					pos_macro = module_warning_inverse.find(macro_name);
					if (pos_macro != string::npos){
						module_warning_inverse.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_quiet != "") {
					pos_macro = module_quiet.find(macro_name);
					if (pos_macro != string::npos){
						module_quiet.replace(pos_macro, macro_name.size(), macro_value);
					}
				}
				
				if (module_native_encoding != "") {
					pos_macro = module_native_encoding.find(macro_name);
					if (pos_macro != string::npos){
						module_native_encoding.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_ff_interval != "") {
					pos_macro = module_ff_interval.find(macro_name);
					if (pos_macro != string::npos){
						module_ff_interval.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_ff_type != "") {
					pos_macro = module_ff_type.find(macro_name);
					if (pos_macro != string::npos){
						module_ff_type.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_alert_template != "") {
					pos_macro = module_alert_template.find(macro_name);
					if (pos_macro != string::npos){
						module_alert_template.replace(pos_macro, macro_name.size(), macro_value);
					}
				}

				if (module_user_session != "") {
					pos_macro = module_user_session.find(macro_name);
					if (pos_macro != string::npos){
						module_user_session.replace(pos_macro, macro_name.size(), macro_value);
					}
				}
			}
		}
	}

	/* Skip disabled modules */
	if (module_disabled == "1") {
		pandoraLog ("Skipping disabled module \"%s\"", module_name.c_str ());
		return NULL;
	}

	/* Create module objects */
	if (module_exec != "") {
		module = new Pandora_Module_Exec (module_name,
						  module_exec, module_native_encoding);
		if (module_timeout != "") {
			module->setTimeout (atoi (module_timeout.c_str ()));
		}
		if (module_wait_timeout != "") {
			module->setWaitTimeout (atoi (module_wait_timeout.c_str ()));
		}
		
	} else if (module_exec_powershell != "") {
		module = new Pandora_Module_Exec_Powershell (module_name, module_exec_powershell);
		
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
				module_proc->setUserSession (is_enabled(module_user_session));
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
		// Added a description with the memory free
		try {
			char buffer[100];
			unsigned long memory = Pandora_Wmi::getDiskFreeSpace(module_freedisk_percent);
			if (sprintf(buffer, "Free space on drive %s %dMB",
				module_freedisk_percent.c_str(), memory) > 0) {
				module->setDescription(buffer);
			}
		} catch (Pandora_Wmi::Pandora_Wmi_Exception e) {
		}
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
	} else if (module_logevent != "") {
		module = new Pandora_Module_Logevent (module_name,
						      module_source,
						      module_eventtype,
						      module_eventcode,
						      module_pattern,
						      module_application);
	}
	else if (module_logchannel != "") {
		module = new Pandora_Module_Logchannel (module_name,
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
		if (module_timeout != ""){
			module->setTimeout(atoi(module_timeout.c_str()));
		}
		if (module_wait_timeout != "") {
			module->setWaitTimeout (atoi (module_wait_timeout.c_str ()));
		}
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
			module->addPreCondition (*precondition_iter);
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

	/* Set the module interval */
	if (module_interval != "") {
		int interval;
		
		try {
			interval = strtoint (module_interval);
			module->setInterval (interval);
			module->setIntensiveInterval (interval);
		} catch (Invalid_Conversion e) {
			pandoraLog ("Invalid interval value \"%s\" for module %s",
			module_interval.c_str (),
			module_name.c_str ());
		}
	}

	/* Set the module absolute interval */
	if (module_absoluteinterval != "") {
		int interval;
		
		try {
			service = Pandora_Windows_Service::getInstance();

			// Run once.
			if (module_absoluteinterval == "once") {
				interval = 0;
			}
			// Seconds.
			else if (module_absoluteinterval.back() == 's') {
				interval = strtoint (module_absoluteinterval.substr(0, module_absoluteinterval.size() - 1));
			}
			// Minutes.
			else if (module_absoluteinterval.back() == 'm') {
				interval = strtoint (module_absoluteinterval.substr(0, module_absoluteinterval.size() - 1)) * 60;
			}
			// Hours.
			else if (module_absoluteinterval.back() == 'h') {
				interval = strtoint (module_absoluteinterval.substr(0, module_absoluteinterval.size() - 1)) * 3600;
			}
			// Days.
			else if (module_absoluteinterval.back() == 'd') {
				interval = strtoint (module_absoluteinterval.substr(0, module_absoluteinterval.size() - 1)) * 86400;
			}
			// Number of agent intervals.
			else {
				interval = strtoint(module_absoluteinterval) * (service->getIntervalSec());
			}

			// Convert from seconds to agent executions.
			interval = ceil(interval / double(service->getIntervalSec()));

			// Set the module interval.
			module->setInterval (interval);
			module->setIntensiveInterval (interval);

			// Compute the MD5 hash of the module's name.
			char module_name_md5[Pandora_File::MD5_BUF_SIZE];
			Pandora_File::md5(module_name.c_str(), module_name.size(), module_name_md5);

			// Set the timestamp file.
			module->setTimestampFile(Pandora::getPandoraInstallDir().append("/ref/").append(module_name_md5).append(".ref"));
		} catch (Invalid_Conversion e) {
			pandoraLog ("Invalid absolute interval value \"%s\" for module %s",
			module_absoluteinterval.c_str (),
			module_name.c_str ());
		}
		catch (...) {
			// Should not happen. Ignore errors.
		}
	}

	/* Module intensive condition */
	if (intensive_condition_list.size () > 0) {
		intensive_condition_iter = intensive_condition_list.begin ();
		for (intensive_condition_iter = intensive_condition_list.begin ();
		     intensive_condition_iter != intensive_condition_list.end ();
		     intensive_condition_iter++) {
			module->addIntensiveCondition (*intensive_condition_iter);
		}
	/* Adjust the module interval for non-intensive modules */
	} else {
		service = Pandora_Windows_Service::getInstance ();
		module->setIntensiveInterval (module->getInterval () * (service->getInterval () / service->getIntensiveInterval ()));
	}

	/* Initialize the module's execution counter. */
	module->initExecutions ();

	/* Module cron */
	module->setCron (module_crontab);

	/* Plugins do not have a module type */
	if (module_plugin == "") {
		type = Pandora_Module::parseModuleTypeFromString (module_type);
		switch (type) {
		case TYPE_GENERIC_DATA:
		case TYPE_GENERIC_DATA_INC:
		case TYPE_GENERIC_DATA_INC_ABS:
		case TYPE_GENERIC_PROC:
		case TYPE_ASYNC_DATA:
		case TYPE_ASYNC_PROC:
			module->setType (module_type);
			numeric = true;
			
			break;
		case TYPE_GENERIC_DATA_STRING:
		case TYPE_ASYNC_STRING:
		case TYPE_LOG:
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

	// Make sure modules that run once are asynchronous.
	if (module->getInterval() == 0) {
		type = module->getTypeInt();
		if (type == TYPE_GENERIC_DATA) {
			module->setType("async_data");
		} else if (type == TYPE_GENERIC_PROC) {
			module->setType("async_proc");
		} else if (type == TYPE_GENERIC_DATA_STRING) {
			module->setType("async_string");
		}
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
	
	if (module_unit != "") {
		module->setUnit (module_unit);
	}
	
	if (module_group != "") {
		module->setModuleGroup (module_group);
	}
	
	if (module_custom_id != "") {
		module->setCustomId (module_custom_id);
	}
	
	if (module_str_warning != "") {
		module->setStrWarning (module_str_warning);
	}
	
	if (module_str_critical != "") {
		module->setStrCritical (module_str_critical);
	}
	
	if (module_critical_instructions != "") {
		module->setCriticalInstructions (module_critical_instructions);
	}
	
	if (module_warning_instructions != "") {
		module->setWarningInstructions (module_warning_instructions);
	}
	
	if (module_unknown_instructions != "") {
		module->setUnknownInstructions (module_unknown_instructions);
	}
	
	if (module_tags != "") {
		module->setTags (module_tags);
	}
	
	if (module_critical_inverse != "") {
		module->setCriticalInverse (module_critical_inverse);
	}
	
	if (module_warning_inverse != "") {
		module->setWarningInverse (module_warning_inverse);
	}
	
	if (module_quiet != "") {
		module->setQuiet (module_quiet);
	}
	
	if (module_ff_interval != "") {
		module->setModuleFFInterval (module_ff_interval);
	}

	if (module_ff_type != "") {
		module->setModuleFFType (module_ff_type);
	}
	
	if (module_alert_template != "") {
		module->setModuleAlertTemplate (module_alert_template);
	}
	
	if (module_crontab != "") {
		module->setModuleCrontab (module_crontab);
	}
	
	return module;
}
