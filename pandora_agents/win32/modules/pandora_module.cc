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

using namespace Pandora_Modules;

Pandora_Module::Pandora_Module (string name) {
        this->module_name = name;
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
