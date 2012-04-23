/* Defines a parent class for a Pandora module.

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

#include "pandora_module.h"
#include "../pandora_strutils.h"
#include "../pandora.h"

#include <iostream>
#include <sstream>

#define BUFSIZE 4096 

using namespace Pandora;
using namespace Pandora_Modules;
using namespace Pandora_Strutils;

/** 
 * Creates a Pandora_Module.
 *
 * Initializes all attributes. The default interval is set to 1 loop.
 * 
 * @param name Module name.
 */
Pandora_Module::Pandora_Module (string name) {
	this->module_name     = name;
	this->executions      = 0;
	this->module_interval = 1;
	this->module_timeout  = 15000;
	this->max             = 0;
	this->min             = 0;
	this->post_process    = "";
	this->has_limits      = false;
	this->has_min         = false;
	this->has_max         = false;
	this->async           = false;
	this->data_list       = NULL;
    this->inventory_list  = NULL;
    this->precondition_list  = NULL;
    this->condition_list  = NULL;
	this->cron            = NULL;
	this->min_critical    = "";
	this->max_critical    = "";
	this->min_warning     = "";
	this->max_warning     = "";
	this->disabled        = "";
	this->min_ff_event    = "";
}

/** 
 * Virtual destructor of Pandora_Module.
 *
 * Should be redefined by child classes.
 */
Pandora_Module::~Pandora_Module () {
	Condition *cond = NULL;
	Precondition *precond = NULL;
	list<Condition *>::iterator iter;
	list<Precondition *>::iterator iter_pre;

	/* Clean data lists */
	this->cleanDataList ();

	/* Clean precondition list */
	if (this->precondition_list != NULL && this->precondition_list->size () > 0) {
		iter_pre = this->precondition_list->begin ();
		for (iter_pre = this->precondition_list->begin ();
		     iter_pre != this->precondition_list->end ();
		     iter_pre++) {
			/* Free regular expressions */
			precond = *iter_pre;
			if (precond->string_value != "") {
				regfree (&(precond->regexp));
			}
			delete (*iter_pre);
		}
		delete (this->precondition_list);
		this->precondition_list = NULL;
	}
	
	/* Clean condition list */
	if (this->condition_list != NULL && this->condition_list->size () > 0) {
		iter = this->condition_list->begin ();
		for (iter = this->condition_list->begin ();
		     iter != this->condition_list->end ();
		     iter++) {
			/* Free regular expressions */
			cond = *iter;
			if (cond->string_value != "") {
				regfree (&(cond->regexp));
			}
			delete (*iter);
		}
		delete (this->condition_list);
		this->condition_list = NULL;
	}
	
	/* Clean the module cron */
	if (this->cron != NULL) {
		delete (this->cron);
		this->cron = NULL;
	}
}


void
Pandora_Module::cleanDataList () {
	Pandora_Data                  *data;
	list<Pandora_Data *>::iterator iter;
	
	if (this->data_list) {
		if (this->data_list->size () > 0) {
			iter = this->data_list->begin ();
			for (iter = this->data_list->begin ();
			     iter != this->data_list->end ();
			     iter++) {
				data = *iter;
				delete data;
			}
		}
		delete this->data_list;
		this->data_list = NULL;
	}
	if (this->inventory_list) {
		if (this->inventory_list->size () > 0) {
			iter = this->inventory_list->begin ();
			for (iter = this->inventory_list->begin ();
			     iter != this->inventory_list->end ();
			     iter++) {
				data = *iter;
				delete data;
			}
		}
		delete this->inventory_list;
		this->inventory_list = NULL;
	}
}

/** 
 * Get the Module_Type from a string type.
 * 
 * @param type String type.
 * 
 * @return The Module_Type which represents the type.
 */
Module_Type
Pandora_Module::parseModuleTypeFromString (string type) {
	if (type == module_generic_data_str || type == "") {
		return TYPE_GENERIC_DATA;
	} else if (type == module_generic_data_inc_str) {
		return TYPE_GENERIC_DATA_INC;
	} else if (type == module_generic_data_string_str) {
		return TYPE_GENERIC_DATA_STRING;
	} else if (type == module_generic_proc_str) {
		return TYPE_GENERIC_PROC;
	} else if (type == module_async_data_str) {
		return TYPE_ASYNC_DATA;
	} else if (type == module_async_proc_str) {
		return TYPE_ASYNC_PROC;
	} else if (type == module_async_string_str) {
		return TYPE_ASYNC_STRING;
	} else {
		return TYPE_0;
	}
}

/** 
 * Get the Module_Kind from a string Kind.
 * 
 * @param kind String Kind.
 * 
 * @return The Module_Kind which represents the Kind.
 */
Module_Kind
Pandora_Module::parseModuleKindFromString (string kind) {
	if (kind == module_exec_str) {
		return MODULE_EXEC;
	} else if (kind == module_proc_str) {
		return MODULE_PROC;
	} else if (kind == module_service_str) {
		return MODULE_SERVICE;
	} else if (kind == module_freedisk_str) {
		return MODULE_FREEDISK;
	} else if (kind == module_freedisk_percent_str) {
		return MODULE_FREEDISK_PERCENT;
	} else if (kind == module_freememory_str) {
		return MODULE_FREEMEMORY;
	} else if (kind == module_freememory_percent_str) {
		return MODULE_FREEMEMORY_PERCENT;
	} else if (kind == module_cpuusage_str) {
		return MODULE_CPUUSAGE;
	} else if (kind == module_inventory_str) {
		return MODULE_INVENTORY;
	} else if (kind == module_odbc_str) {
		return MODULE_ODBC;
	} else if (kind == module_logevent_str) {
		return MODULE_LOGEVENT;  
	} else if (kind == module_wmiquery_str) {
		return MODULE_WMIQUERY;               
	} else if (kind == module_perfcounter_str) {
		return MODULE_PERFCOUNTER;
	} else if (kind == module_tcpcheck_str) {
		return MODULE_TCPCHECK;
	} else if (kind == module_regexp_str) {
		return MODULE_REGEXP;
	} else if (kind == module_plugin_str) {
		return MODULE_PLUGIN;
	} else if (kind == module_ping_str) {
		return MODULE_PING;
	} else if (kind == module_snmpget_str) {
		return MODULE_SNMPGET;
	} else {
		return MODULE_0;
	}
}

/** 
 * Get the name of the module.
 * 
 * @return The name of the module.
 */
string
Pandora_Module::getName () const {
	return this->module_name;
}

/** 
 * Get the description of the module.
 * 
 * @return The module description.
 */
string
Pandora_Module::getDescription () const {
	return this->module_description;
}

/** 
 * Get the module type in a human readable string.
 * 
 * @return The module type..
 */
string
Pandora_Module::getTypeString () const {
	return this->module_type_str;
}

/** 
 * Get the module type in a integer value.
 * 
 * @return The module type in a integer value.
 */
Module_Type
Pandora_Module::getTypeInt () const {
	return this->module_type;
}

/** 
 * Get the kind of the module in a integer_value.
 * 
 * @return The module kind in a integer value.
 */
Module_Kind
Pandora_Module::getModuleKind () const {
	return this->module_kind;
}

/** 
 * Get the output of the module.
 * 
 * @return The module output in a string value.
 */
string
Pandora_Module::getLatestOutput () const {
	return this->latest_output;
}

/** 
 * Get the type of the module in a integer_value.
 * 
 * @return The module type in a integer value.
 */
Module_Type
Pandora_Module::getModuleType () const {
	return this->module_type;
}

/** 
 * Get the module output.
 *
 * After running the module, this function will return the output,
 * based on the module_type and the interval.
 *
 * @return The output in a string.
 *
 * @exception Output_Error Throwed if the module_type is not correct.
 * @exception Value_Error Throwed when the output is not in
 *            the interval range.
 */
string
Pandora_Module::getDataOutput (Pandora_Data *data) {
	double value;
	
	if (this->module_type == TYPE_GENERIC_DATA_STRING || 
        this->module_type == TYPE_ASYNC_STRING) {
		return data->getValue ();
	}
	
	try {
		value = Pandora_Strutils::strtodouble (data->getValue ());
	} catch (Pandora_Strutils::Invalid_Conversion e) {
		pandoraLog ("Output error on module %s",
			    this->module_name.c_str ());
		throw Output_Error ();
	}
	
	if (this->has_limits) {
		if (value >= this->max || value <= this->min) {
			pandoraLog ("The returned value was not in the interval on module %s",
				    this->module_name.c_str ());
			throw Value_Error ();
		}
	}
	
	return trim (data->getValue ());
}

/** 
 * Export the module output to en environment variable.
 */
void
Pandora_Module::exportDataOutput () {
	Pandora_Data *pandora_data = NULL;
	string putenv_str, module_data;

	/* putenv expects a string of the form name=value */
	putenv_str = this->save + "=";
	
	/* No data */
	if ( (!this->has_output) || this->data_list == NULL) {
		putenv (putenv_str.c_str ());
		return;
	}

	/* Get the module data */
	pandora_data = data_list->front ();
	if (pandora_data == NULL) {
		putenv (putenv_str.c_str ());
		return;
	}
	module_data = pandora_data->getValue ();
	putenv_str += module_data;

	/* Save it as an environment variable */
	putenv (putenv_str.c_str ());
}

/** 
 * Set the output of the module.
 *
 * If the function is called more than once before calling getXML, the
 * output will be accumulated and added to a <datalist> tag.
 *
 * @param output Output to add.
 */
void
Pandora_Module::setOutput (string output) {
	Pandora_Data *data;

	if (this->data_list == NULL)
		this->data_list = new list<Pandora_Data *> ();
	data = new Pandora_Data (output, this->module_name);
	this->data_list->push_back (data);
	this->latest_output = output;
}


/** 
 * Set the output of the module.
 *
 * If the function is called more than once before calling getXML, the
 * output will be accumulated and added to a <datalist> tag.
 *
 * @param output Output to add.
 * @param system_time Timestamp. 
 */
void
Pandora_Module::setOutput (string output, SYSTEMTIME *system_time) {
	Pandora_Data *data;

	if (this->data_list == NULL)
		this->data_list = new list<Pandora_Data *> ();
	data = new Pandora_Data (output, system_time, this->module_name);
	this->data_list->push_back (data);
}

/** 
 * Set no output for the module.
 */
void
Pandora_Module::setNoOutput () {
	this->has_output = false;
}

/** 
 * Run the module and generates the output.
 *
 * It is used by the child classes to check the execution interval
 * value and increment the executions variable.
 * 
 * @exception Interval_Not_Fulfilled Throwed when the execution
 *            interval value indicates that the module doesn't have
 *            to execute.
 */
void
Pandora_Module::run () {
	/* Check the interval */
	if (this->executions % this->module_interval != 0) {
		pandoraDebug ("%s: Interval is not fulfilled",
			      this->module_name.c_str ());
		this->executions++;
		has_output = false;
		throw Interval_Not_Fulfilled ();
	} 
	
	/* Increment the executions after check. This is done to execute the
	   first time */
	this->executions++;
	has_output = true;
}

/** 
 * Get the XML output of the value.
 *
 * The output is a element of the TinyXML library. A sample output of
 * a module is:
 * @verbatim
 <module>
   <name>Conexiones abiertas</name>
   <type>generic_data</type>
   <data>5</data>
   <description>Conexiones abiertas</description>
 </module>
   @endverbatim
 *
 * @return A pointer to the TiXmlElement if successful which has to be
 *         freed by the caller. NULL if the XML could not be created.
 */
string
Pandora_Module::getXml () {
	ostringstream module_interval, min, max;
 	string        module_xml, data_clean, interval_str;
	Pandora_Data *data;
	
	pandoraDebug ("%s getXML begin", module_name.c_str ());
	
	/* No data */
	if (!this->has_output || this->data_list == NULL) {
		return "";
	}

	/* Compose the module XML */
    module_xml = "<module>\n\t<name><![CDATA[";
    module_xml += this->module_name;
    module_xml += "]]></name>\n\t<type><![CDATA[";
    module_xml += this->module_type_str;
    module_xml += "]]></type>\n";
    
    /* Description */
    if (this->module_description != "") {
		module_xml += "\t<description><![CDATA[";
		module_xml += this->module_description;
		module_xml += "]]></description>\n";
	}
	
	/* Interval */
    if (this->module_interval > 1) {
		module_interval << this->module_interval;
		module_xml += "\t<module_interval><![CDATA[";
		module_xml += module_interval.str ();
		module_xml += "]]></module_interval>\n";
	}
	
	/* Min */
    if (this->has_min) {
		min << this->min;
		module_xml += "\t<min><![CDATA[";
		module_xml += min.str ();
		module_xml += "]]></min>\n";
	}
	
	/* Max */
	if (this->has_max) {
		max << this->max;
		module_xml += "\t<max><![CDATA[";
		module_xml += max.str ();
		module_xml += "]]></max>\n";
	}
	
	/* Post process */
	if (this->post_process != "") {
		module_xml += "\t<post_process><![CDATA[";
		module_xml += this->post_process;
		module_xml += "]]></post_process>\n";
	}

	/* Min critical */
	if (this->min_critical != "") {
		module_xml += "\t<min_critical><![CDATA[";
		module_xml += this->min_critical;
		module_xml += "]]></min_critical>\n";
	}

	/* Max critical */
	if (this->max_critical != "") {
		module_xml += "\t<max_critical><![CDATA[";
		module_xml += this->max_critical;
		module_xml += "]]></max_critical>\n";
	}

	/* Min warning */
	if (this->min_warning != "") {
		module_xml += "\t<min_warning><![CDATA[";
		module_xml += this->min_warning;
		module_xml += "]]></min_warning>\n";
	}

	/* Max warning */
	if (this->max_warning != "") {
		module_xml += "\t<max_warning><![CDATA[";
		module_xml += this->max_warning;
		module_xml += "]]></max_warning>\n";
	}

	/* Disabled */
	if (this->disabled != "") {
		module_xml += "\t<disabled><![CDATA[";
		module_xml += this->disabled;
		module_xml += "]]></disabled>\n";
	}

	/* Min ff event */
	if (this->min_ff_event != "") {
		module_xml += "\t<min_ff_event><![CDATA[";
		module_xml += this->min_ff_event;
		module_xml += "]]></min_ff_event>\n";
	}

    /* Write module data */
	if (this->data_list && this->data_list->size () > 1) {
		list<Pandora_Data *>::iterator iter;

		module_xml += "\t<datalist>\n";
		
		iter = this->data_list->begin ();
		for (iter = this->data_list->begin ();
		     iter != this->data_list->end ();
		     iter++) {
			data = *iter;
			
			try {
				data_clean = strreplace (this->getDataOutput (data),
							 "%", "%%" );
			} catch (Output_Error e) {
				continue;
			}
			
			module_xml += "\t\t<data>\n\t\t\t<value><![CDATA[";
			module_xml += data_clean;
			module_xml += "]]></value>\n\t\t\t<timestamp><![CDATA[";
			module_xml += data->getTimestamp ();
			module_xml += "]]></timestamp>\n\t\t</data>\n";
		}
		
		module_xml += "\t</datalist>\n";
	} else {
		data = data_list->front ();
		try {
			data_clean = strreplace (this->getDataOutput (data), "%", "%%" );
			module_xml += "\t<data><![CDATA[";
			module_xml += data_clean;
			module_xml += "]]></data>\n";
		} catch (Output_Error e) {
		}
	}
		
	/* Close the module tag */
	module_xml += "</module>\n";
	
	/* Clean up */
	this->cleanDataList ();
	
	pandoraDebug ("%s getXML end", module_name.c_str ());
	return module_xml;
}

/** 
 * Set the max value the module can have.
 *
 * The range is closed, so the value is included.
 *
 * @param value Max value to set.
 */
void
Pandora_Module::setMax (int value) {
	this->has_limits = true;
	this->has_max = true;
	this->max        = value;
}

/** 
 * Set the min value the module can have.
 *
 * The range is closed, so the value is included.
 *
 * @param value Min value to set.
 */
void
Pandora_Module::setMin (int value) {
	this->has_limits = true;
	this->has_min = true;
	this->min        = value;
}

/** 
 * Set the post process value for the module.
 *
 * @param value Post process value .
 */
void
Pandora_Module::setPostProcess (string value) {
	this->post_process = value;
}

/** 
 * Set the min critical value for the module.
 *
 * @param value Min critical value .
 */
void
Pandora_Module::setMinCritical (string value) {
	this->min_critical = value;
}

/** 
 * Set the max critical value for the module.
 *
 * @param value Max critical value .
 */
void
Pandora_Module::setMaxCritical (string value) {
	this->max_critical = value;
}

/** 
 * Set the min warning value for the module.
 *
 * @param value Min warning value .
 */
void
Pandora_Module::setMinWarning (string value) {
	this->min_warning = value;
}

/** 
 * Set the max warning value for the module.
 *
 * @param value Max warning value .
 */
void
Pandora_Module::setMaxWarning (string value) {
	this->max_warning = value;
}

/** 
 * Set the disabled value for the module.
 *
 * @param value Disabled value .
 */
void
Pandora_Module::setDisabled (string value) {
	this->disabled = value;
}

/** 
 * Set the min ff event value for the module.
 *
 * @param value Min ff event value .
 */
void
Pandora_Module::setMinFFEvent (string value) {
	this->min_ff_event = value;
}

/** 
 * Set the async flag to the module.
 *
 * If a module is set to be async, it would try to works only when the
 * events happen. Note that not all the modules can work in async mode.
 *
 * @param async Flag to set.
 */
void
Pandora_Module::setAsync (bool async) {
	this->async = async;
}

/** 
 * Set the module type from a string type.
 * 
 * @param type String type.
 */
void
Pandora_Module::setType (string type) {
	this->module_type_str = type;
	this->module_type     = parseModuleTypeFromString (type);
}

/** 
 * Set the module kind from a string kind.
 * 
 * @param kind String kind.
 */
void
Pandora_Module::setKind (string kind) {
	this->module_kind_str = kind;
	this->module_kind     = parseModuleKindFromString (kind);
}

/** 
 * Set the interval execution.
 * 
 * @param interval Interval between executions.
 */
void
Pandora_Module::setInterval (int interval) {
	this->module_interval = interval;
}

/** 
 * Set the execution timeout.
 * 
 * @param timeout Execution timeout.
 */
void
Pandora_Module::setTimeout (int timeout) {

	if (timeout < 0) {
		return;
	}
	
	/* WaitForSingleObject expects milliseconds */
	this->module_timeout = timeout * 1000;
}

/** 
 * Get the execution interval.
 * 
 * @return The execution interval.
 */
int
Pandora_Module::getInterval () {
	return this->module_interval;
}

/** 
 * Get the execution timeout.
 * 
 * @return The execution timeout.
 */
int
Pandora_Module::getTimeout () {
	return this->module_timeout;
}

/** 
 * Set the module description.
 * 
 * @param description Description of the module.
 */
void
Pandora_Module::setDescription (string description) {
	this->module_description = description;
}

/** 
 * Set the name of the environment variable where the module data will be saved.
 * 
 * @param save Name of the environment variable.
 */
void
Pandora_Module::setSave (string save) {
	this->save = save;
}

/** 
 * Get the name of the environment variable where the module data will be saved.
 * 
 * @return The name of the environment variable.
 */
string
Pandora_Module::getSave () {
	return this->save;
}

/** 
 * Adds a new precondition to the module.
 * 
 * @param precondition string.
 */
void
Pandora_Module::addPrecondition (string precondition) {
	Precondition *precond;
	char operation[256], string_value[1024], command[1024];

	/* Create the precondition list if it does not exist */
	if (this->precondition_list == NULL) {
		this->precondition_list = new list<Precondition *> ();
	}

	/* Create the new precondition */
	precond = new Precondition;
	if (precond == NULL) {
		return;
	}

	precond->value_1 = 0;
	precond->value_2 = 0;

	/* Numeric comparison */
	if (sscanf (precondition.c_str (), "%255s %lf %1023[^\n]s", operation, &(precond->value_1), command) == 3) {
		precond->operation = operation;
		precond->command = command;
		precond->command = "cmd.exe /c \"" + precond->command + "\"";
		this->precondition_list->push_back (precond);
		return;
		
	/* Regular expression */
	} else if (sscanf (precondition.c_str (), "=~ %1023s %1023[^\n]s", string_value, command) == 2) {
		precond->operation = "=~";
		precond->string_value = string_value;
		precond->command = command;
		precond->command = "cmd.exe /c \"" + precond->command + "\"";
		if (regcomp (&(precond->regexp), string_value, 0) != 0) {
			pandoraDebug ("Invalid regular expression %s", string_value);
			delete (precond);
			return;
		}
		this->precondition_list->push_back (precond);
		
	/* Interval */
	} else if (sscanf (precondition.c_str (), "(%lf , %lf) %1023[^\n]s", &(precond->value_1), &(precond->value_2), command) == 3) {
		precond->operation = "()";
		precond->command = command;
		precond->command = "cmd.exe /c \"" + precond->command + "\"";
		this->precondition_list->push_back (precond);
	} else {
		pandoraDebug ("Invalid module condition: %s", precondition.c_str ());
		delete (precond);
		return;
	}
return;
}

/** 
 * Adds a new condition to the module.
 * 
 * @param condition Condition string.
 */
void
Pandora_Module::addCondition (string condition) {
	Condition *cond;
	char operation[256], string_value[1024], command[1024];

	/* Create the condition list if it does not exist */
	if (this->condition_list == NULL) {
		this->condition_list = new list<Condition *> ();
	}

	/* Create the new condition */
	cond = new Condition;
	if (cond == NULL) {
		return;
	}
	cond->value_1 = 0;
	cond->value_2 = 0;

	/* Numeric comparison */
	if (sscanf (condition.c_str (), "%255s %lf %1023[^\n]s", operation, &(cond->value_1), command) == 3) {
		cond->operation = operation;
		cond->command = command;
		this->condition_list->push_back (cond);		
	/* Regular expression */
	} else if (sscanf (condition.c_str (), "=~ %1023s %1023[^\n]s", string_value, command) == 2) {
		cond->operation = "=~";
		cond->string_value = string_value;
		cond->command = command;
		if (regcomp (&(cond->regexp), string_value, 0) != 0) {
			pandoraDebug ("Invalid regular expression %s", string_value);
			delete (cond);
			return;
		}
		this->condition_list->push_back (cond);		
	/* Interval */
	} else if (sscanf (condition.c_str (), "(%lf , %lf) %1023[^\n]s", &(cond->value_1), &(cond->value_2), command) == 3) {
		cond->operation = "()";
		cond->command = command;
		this->condition_list->push_back (cond);
	} else {
		pandoraDebug ("Invalid module condition: %s", condition.c_str ());
		delete (cond);
		return;
	}
}

/** 
 * Evaluates and executes module preconditions.
 */
int
Pandora_Module::evaluatePreconditions () {
	STARTUPINFO         si;
	PROCESS_INFORMATION pi;
	DWORD               retval, dwRet;
	SECURITY_ATTRIBUTES attributes;
	HANDLE              out, new_stdout, out_read, job;
	string              working_dir;
	Precondition *precond = NULL;
	float float_output;
	list<Precondition *>::iterator iter;
	unsigned char run;
	int exe = 1;
	string output;
	
	if (this->precondition_list != NULL && this->precondition_list->size () > 0) {

		iter = this->precondition_list->begin ();
		for (iter = this->precondition_list->begin ();
		     iter != this->precondition_list->end ();
		     iter++) {
				
			precond = *iter;
			run = 0;	
			
	
			/* Set the bInheritHandle flag so pipe handles are inherited. */
			attributes.nLength = sizeof (SECURITY_ATTRIBUTES); 
			attributes.bInheritHandle = TRUE; 
			attributes.lpSecurityDescriptor = NULL; 

			/* Create a job to kill the child tree if it become zombie */
			/* CAUTION: In order to compile this, WINVER should be defined to 0x0500.
			This may need no change, since it was redefined by the 
			program, but if needed, the macro is defined 
			in <windef.h> */
			job = CreateJobObject (&attributes, this->module_name.c_str ());
			if (job == NULL) {
				pandoraLog ("evaluatePreconditions: CreateJobObject failed. Err: %d", GetLastError ());
				return 0;
			}

			/* Get the handle to the current STDOUT. */
			out = GetStdHandle (STD_OUTPUT_HANDLE); 

			if (! CreatePipe (&out_read, &new_stdout, &attributes, 0)) {
				pandoraLog ("evaluatePreconditions: CreatePipe failed. Err: %d", GetLastError ());
				return 0;
			}

			/* Ensure the read handle to the pipe for STDOUT is not inherited */
			SetHandleInformation (out_read, HANDLE_FLAG_INHERIT, 0);

			/* Set up members of the STARTUPINFO structure. */
			ZeroMemory (&si, sizeof (si));
			GetStartupInfo (&si);

			si.cb = sizeof (si);
			si.dwFlags     = STARTF_USESTDHANDLES | STARTF_USESHOWWINDOW;
			si.wShowWindow = SW_HIDE;
			si.hStdError   = new_stdout;
			si.hStdOutput  = new_stdout;

			/* Set up members of the PROCESS_INFORMATION structure. */
			ZeroMemory (&pi, sizeof (pi));
			pandoraDebug ("Executing pre-condition: %s", precond->command.c_str ());

			/* Set the working directory of the process. It's "utils" directory
			to find the GNU W32 tools */
			working_dir = getPandoraInstallDir () + "util\\";

			/* Create the child process. */
			if (! CreateProcess (NULL, (CHAR *) precond->command.c_str (), NULL,
			     NULL, TRUE, CREATE_SUSPENDED | CREATE_NO_WINDOW, NULL,
			     working_dir.c_str (), &si, &pi)) {
				pandoraLog ("evaluatePreconditions: %s CreateProcess failed. Err: %d",
			    this->module_name.c_str (), GetLastError ());
			} else {
				char          buffer[BUFSIZE + 1];
				unsigned long read, avail;
	
				if (! AssignProcessToJobObject (job, pi.hProcess)) {
					pandoraLog ("evaluatePreconditions: could not assign proccess to job (error %d)",
				    GetLastError ());
				}
				ResumeThread (pi.hThread);
	
				/*string output;*/
				output = "";
				int tickbase = GetTickCount();
				while ( (dwRet = WaitForSingleObject (pi.hProcess, 500)) != WAIT_ABANDONED ) {
					PeekNamedPipe (out_read, buffer, BUFSIZE, &read, &avail, NULL);
					if (avail > 0) {
						ReadFile (out_read, buffer, BUFSIZE, &read, NULL);
						buffer[read] = '\0';
						output += (char *) buffer;
					}
			
					float_output = atof(output.c_str());

					if (dwRet == WAIT_OBJECT_0) { 
						break;
					} else if(this->getTimeout() < GetTickCount() - tickbase) {
						/* STILL_ACTIVE */
						TerminateProcess(pi.hThread, STILL_ACTIVE);
						pandoraLog ("evaluatePreconditions: %s timed out (retcode: %d)", this->module_name.c_str (), STILL_ACTIVE);
						break;
					}
				}

				GetExitCodeProcess (pi.hProcess, &retval);

				if (retval != 0) {
					if (! TerminateJobObject (job, 0)) {
						pandoraLog ("evaluatePreconditions: TerminateJobObject failed. (error %d)",
						GetLastError ());
					}
            
					if (retval != STILL_ACTIVE) {
						pandoraLog ("evaluatePreconditions: %s did not executed well (retcode: %d)",
						this->module_name.c_str (), retval);
					}
				
					/* Close job, process and thread handles. */
					CloseHandle (job);
					CloseHandle (pi.hProcess);
					CloseHandle (pi.hThread);
					CloseHandle (new_stdout);
					CloseHandle (out_read);
					return 0;
				}
	
				/* Close job, process and thread handles. */
				CloseHandle (job);
				CloseHandle (pi.hProcess);
				CloseHandle (pi.hThread);
			}

			CloseHandle (new_stdout);
			CloseHandle (out_read);

	if ((precond->operation == ">" && float_output > precond->value_1) ||
			    (precond->operation == "<" && float_output < precond->value_1) ||
			    (precond->operation == "=" && float_output == precond->value_1) ||
			    (precond->operation == "!=" && float_output != precond->value_1) ||
			    (precond->operation == "=~" && regexec (&(precond->regexp), output.c_str(), 0, NULL, 0) == 0) ||
				(precond->operation == "()" && float_output > precond->value_1 && float_output < precond->value_2)){
						exe = 1;
			} else {
				exe = 0;
				return exe;
			}
			
			CloseHandle (pi.hProcess);			
		} 
	}
	
	return exe;
}
			
	



/** 
 * Evaluates and executes module conditions.
 */
void
Pandora_Module::evaluateConditions () {
	unsigned char run;
	double double_value;
	string string_value;
	Condition *cond = NULL;
	list<Condition *>::iterator iter;
	PROCESS_INFORMATION pi;
	STARTUPINFO         si;
	Pandora_Data *pandora_data = NULL;
	regex_t regex;

	/* No data */
	if ( (!this->has_output) || this->data_list == NULL) {
		return;
	}

	/* Get the module data */
	pandora_data = data_list->front ();

	/* Get the string value of the data */
	string_value = pandora_data->getValue ();

	/* Get the double value of the data */
	try {
		double_value = Pandora_Strutils::strtodouble (string_value);
	} catch (Pandora_Strutils::Invalid_Conversion e) {
		double_value = 0;
	}

	if (this->condition_list != NULL && this->condition_list->size () > 0) {
		iter = this->condition_list->begin ();
		for (iter = this->condition_list->begin ();
		     iter != this->condition_list->end ();
		     iter++) {
			cond = *iter;
			run = 0;
			if ((cond->operation == ">" && double_value > cond->value_1) ||
			    (cond->operation == "<" && double_value < cond->value_1) ||
			    (cond->operation == "=" && double_value == cond->value_1) ||
			    (cond->operation == "!=" && double_value != cond->value_1) ||
			    (cond->operation == "=~" && regexec (&(cond->regexp), string_value.c_str (), 0, NULL, 0) == 0) ||
				(cond->operation == "()" && double_value > cond->value_1 && double_value < cond->value_2)) {
	
				/* Run the condition command */
				ZeroMemory (&si, sizeof (si));
				ZeroMemory (&pi, sizeof (pi));
				if (CreateProcess (NULL , (CHAR *)cond->command.c_str (), NULL, NULL, FALSE,
				    CREATE_NO_WINDOW, NULL, NULL, &si, &pi) == 0) {
				    return;
				}
				WaitForSingleObject(pi.hProcess, this->module_timeout);
				CloseHandle (pi.hProcess);
			}
		}
	}
}

/** 
 * Checks the module cron. Returns 1 if the module should run, 0 if not.
 * 
 * @return 1 if the module should run, 0 if not.
 */
int
Pandora_Module::checkCron () {
	int i, time_params[5];
	time_t current_time, offset;
	struct tm *time_struct;
	Cron *cron = this->cron;

	// No cron
	if (cron == NULL) {
		return 1;
	}

	// Get current time
	current_time = time (NULL);

	// Check if the module was already executed
	if (current_time <= cron->utimestamp) {
		return 0;
	}

	// Break current time
	time_struct = localtime(&current_time);
	if (time_struct == NULL) {
		return 1;
	}

	time_params[0] = time_struct->tm_min;
	time_params[1] = time_struct->tm_hour;
	time_params[2] = time_struct->tm_mday;
	time_params[3] = time_struct->tm_mon;
	time_params[4] = time_struct->tm_wday;
	
	// Fix month (localtime retuns 0..11 and we need 1..12)
	time_params[3] += 1;
	
	// Check cron parameters	
	for (i = 0; i < 5; i++) {
		
		// Wildcard
		if (cron->params[i][0] < 0) {
			continue;
		}
		
		// Check interval
		if (cron->params[i][0] <= cron->params[i][1]) {
			if (time_params[i] < cron->params[i][0] || time_params[i] > cron->params[i][1]) {
				return 0;
			}
		} else {
			if (time_params[i] < cron->params[i][0] && time_params[i] > cron->params[i][1]) {
				return 0;
			}
		}
	}

	// Do not check in the next minute, hour, day or month.
	offset = 0; 
	if (cron->interval >= 0) {
		offset = cron->interval;
	} else if(cron->params[0][0] >= 0) {
		// 1 minute
		offset = 60;
	} else if(cron->params[1][0] >= 0) {
		// 1 hour
		offset = 3600;
	} else if(cron->params[2][0] >=0 || cron->params[4][0] >= 0) {
		// 1 day
		offset = 86400;
	} else if(cron->params[3][0] >= 0) {
		// 31 days
		offset = 2678400;
	}

	cron->utimestamp = current_time + offset;
	return 1;
}

/** 
 * Sets the module cron from a string.
 * 
 * @return 1 if the module should run, 0 if not.
 */
void
Pandora_Module::setCron (string cron_string) {
	int i, value;
	char cron_params[5][256], bottom[256], top[256];
	
	/* Create the new cron if necessary */
	if (this->cron == NULL) {
		this->cron = new Cron ();
	}
	
	/* Parse the cron string */
	if (sscanf (cron_string.c_str (), "%255s %255s %255s %255s %255s", cron_params[0], cron_params[1], cron_params[2], cron_params[3], cron_params[4]) != 5) {
		pandoraDebug ("Invalid cron string: %s", cron_string.c_str ());
		return;
	}
	
	/* Fill the cron structure */
	this->cron->utimestamp = 0;
	this->cron->interval = -1;
	for (i = 0; i < 5; i++) {
		
		/* Wildcard */
		if (cron_params[i][0] == '*') {
			this->cron->params[i][0] = -1;

		/* Interval */
		} else if (sscanf (cron_params[i], "%255[^-]-%255s", bottom, top) == 2) {
			value = atoi (bottom);
			this->cron->params[i][0] = value;
			value = atoi (top);
			this->cron->params[i][1] = value;
		
		/* Single value */
		} else {
			value = atoi (cron_params[i]);
			this->cron->params[i][0] = value;
			this->cron->params[i][1] = value;
		}
	}	
}

/** 
 * Sets the interval of the module cron.
 */
void
Pandora_Module::setCronInterval (int interval) {
	if (this->cron == NULL) {
		this->cron = new Cron ();
	}
	
	this->cron->interval = interval;
}
