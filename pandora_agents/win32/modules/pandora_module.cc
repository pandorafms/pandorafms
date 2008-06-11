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
        this->max             = 0;
        this->min             = 0;
        this->has_limits      = false;
        this->data_list       = NULL;
}

/** 
 * Virtual destructor of Pandora_Module.
 *
 * Should be redefined by child classes.
 */
Pandora_Module::~Pandora_Module () {
	this->cleanDataList ();
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
        if (type == module_generic_data_str) {
                return TYPE_GENERIC_DATA;
        } else if (type == module_generic_data_inc_str) {
                return TYPE_GENERIC_DATA_INC;
        } else if (type == module_generic_data_string_str) {
                return TYPE_GENERIC_DATA_STRING;
        } else if (type == module_generic_proc_str) {
                return TYPE_GENERIC_PROC;
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
        } else if (kind == module_freememory_str) {
                return MODULE_FREEMEMORY;
        } else if (kind == module_cpuusage_str) {
                return MODULE_CPUUSAGE;
	} else if (kind == module_odbc_str) {
                return MODULE_ODBC;
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
	
        if (this->module_type == TYPE_GENERIC_DATA_STRING) {
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
	
	return data->getValue ();
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
	data = new Pandora_Data (output);
	this->data_list->push_back (data);
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
TiXmlElement *
Pandora_Module::getXml () {
	TiXmlElement *root;
	TiXmlElement *element;
	TiXmlElement *data_list_element;
	TiXmlElement *data_element;
	TiXmlText    *text;
	string        item_clean, data_clean, desc_clean;
	Pandora_Data *data;
	
	pandoraDebug ("%s getXML begin", module_name.c_str ());
	
	if (!this->has_output || (this->data_list && this->data_list->size () < 1)) {
                return NULL;
        }
        
        root = new TiXmlElement ("module");
        
        element = new TiXmlElement ("name");
        text = new TiXmlText (this->module_name);
        element->InsertEndChild (*text);
        root->InsertEndChild (*element);
        delete element;
        delete text;
        
        element = new TiXmlElement ("type");
        text = new TiXmlText (this->module_type_str);
        element->InsertEndChild (*text);
        root->InsertEndChild (*element);
        delete element;
        delete text;
	
	if (this->data_list && this->data_list->size () > 1) {
		list<Pandora_Data *>::iterator iter;

		data_list_element = new TiXmlElement ("data_list");
		
		iter = this->data_list->begin ();
		for (iter = this->data_list->begin ();
		     iter != this->data_list->end ();
		     iter++) {
			data = *iter;
			data_element = new TiXmlElement ("data");
			element = new TiXmlElement ("value");
			data_clean = strreplace (this->getDataOutput (data), "%", "%%" );
			text = new TiXmlText (data_clean);
			element->InsertEndChild (*text);
			data_element->InsertEndChild (*element);
			delete text;
			delete element;
			
			element = new TiXmlElement ("timestamp");
			text = new TiXmlText (data->getTimestamp ());
			element->InsertEndChild (*text);
			data_element->InsertEndChild (*element);
			delete text;
			delete element;

			data_list_element->InsertEndChild (*data_element);
		}

		root->InsertEndChild (*data_list_element);
		delete data_list_element;
	} else {
		data = data_list->front ();
		element = new TiXmlElement ("data");
		data_clean = strreplace (this->getDataOutput (data), "%", "%%" );
		text = new TiXmlText (data_clean);
		element->InsertEndChild (*text);
		root->InsertEndChild (*element);
		delete text;
		delete element;
	}
		
        element = new TiXmlElement ("description");
        text = new TiXmlText (this->module_description);
        element->InsertEndChild (*text);
        root->InsertEndChild (*element);
        delete text;
        delete element;

	this->cleanDataList ();
        pandoraDebug ("%s getXML end", module_name.c_str ());
        return root;
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
        this->min        = value;
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
 * Set the module description.
 * 
 * @param description Description of the module.
 */
void
Pandora_Module::setDescription (string description) {
        this->module_description = description;
}
