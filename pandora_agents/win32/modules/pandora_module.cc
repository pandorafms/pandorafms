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

Pandora_Module::Pandora_Module (string name) {
        this->module_name     = name;
        this->executions      = 0;
        this->module_interval = 1;
        this->output          = "";
        this->max             = 0;
        this->min             = 0;
        this->has_limits      = false;
}

Pandora_Module::~Pandora_Module () {
}

int
Pandora_Module::getModuleType (string type) {
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

string
Pandora_Module::getName () const {
        return this->module_name;
}

string
Pandora_Module::getTypeString () const {
        return this->module_type_str;
}

int
Pandora_Module::getTypeInt () const {
        return this->module_type;
}

int
Pandora_Module::getModuleKind () const {
        return this->module_kind;
}

string
Pandora_Module::getOutput () const {
        switch (this->module_type) {
        case TYPE_GENERIC_DATA:
        case TYPE_GENERIC_DATA_INC:
        case TYPE_GENERIC_PROC:
                int value;
                
                try {
                        value = Pandora_Strutils::strtoint (this->output);
                } catch (Pandora_Strutils::Invalid_Conversion e) {
                        throw Output_Error ();
                }
                
                if (this->has_limits) {
                        if (value >= this->max || value <= this->min) {
                                throw Interval_Error ();
                        }
                }
                
                return Pandora_Strutils::inttostr (value);
                break;
        default:
                return this->output;
        }
}

void
Pandora_Module::run () {
                    
        this->output = "";
        
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

TiXmlElement *
Pandora_Module::getXml () {
        string        data;
	TiXmlElement *root;
	TiXmlElement *element;
	TiXmlText    *text;
	string        item_str, data_str, desc_str;
	
	pandoraDebug ("%s getXML begin", module_name.c_str ());
	
	if (!this->has_output) {
                return NULL;
        }
        
        try {
                data = this->getOutput ();
        } catch (Output_Error e) {
                pandoraLog ("Output error");
                return NULL;
        } catch (Interval_Error e) {
                pandoraLog ("The returned value was not in the interval");
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
        
        element = new TiXmlElement ("data");
        data_str = strreplace (this->output,
                               "%", "%%" );
        text = new TiXmlText (data_str);
        element->InsertEndChild (*text);
        root->InsertEndChild (*element);
        delete text;
        delete element;
        
        element = new TiXmlElement ("description");
        text = new TiXmlText (this->module_description);
        element->InsertEndChild (*text);
        root->InsertEndChild (*element);
        delete text;
        delete element;
        
        pandoraDebug ("%s getXML end", module_name.c_str ());
        return root;
}

void
Pandora_Module::setMax (int value) {
        this->has_limits = true;
        this->max        = value;
}

void
Pandora_Module::setMin (int value) {
        this->has_limits = true;
        this->min        = value;
}

void
Pandora_Module::setType (string type) {
        this->module_type_str = type;
        this->module_type     = getModuleType (type);
}

void
Pandora_Module::setInterval (int interval) {
        this->module_interval = interval;
}

void
Pandora_Module::setDescription (string description) {
        this->module_description = description;
}
