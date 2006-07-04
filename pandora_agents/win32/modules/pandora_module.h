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

#ifndef	__PANDORA_MODULE_H__
#define	__PANDORA_MODULE_H__

#include "../pandora.h"
#include "../tinyxml/tinyxml.h"
#include <list>
#include <string>

namespace Pandora_Modules {

        enum {
                TYPE_0,
                TYPE_GENERIC_DATA,
                TYPE_GENERIC_DATA_INC,
                TYPE_GENERIC_PROC,
                TYPE_GENERIC_DATA_STRING
        };

        const string module_generic_data_str        = "generic_data";
        const string module_generic_data_inc_str    = "generic_data_inc";
        const string module_generic_proc_str        = "generic_proc";
        const string module_generic_data_string_str = "generic_data_string";

        enum {
                MODULE_0,
                MODULE_EXEC,
                MODULE_PROC,
		MODULE_SERVICE
        };
        
        const string module_exec_str                = "module_exec";
        const string module_proc_str                = "module_proc";
	const string module_service_str             = "module_service";

        class Output_Error   : public Pandora::Pandora_Exception { };
        class Interval_Error : public Pandora::Pandora_Exception { };
        
        class Pandora_Module {
        protected:
                string module_name;
                string module_type_str;
                int    module_type;
                string module_kind_str;
                string module_description;
                int    module_kind;
                int    module_interval;
                int    executions;
                string output;
                int    max, min;
                bool   has_limits;
        public:
                Pandora_Module           (string name);
                virtual ~Pandora_Module  ();
                
                static int getModuleType (string type);
                
                void   setInterval       (int interval);
                
                /* Get the XML output of the agent. */
                TiXmlElement *getXml     ();
                
                /* Execute the agent */
                virtual void   run       ();
                
                virtual string getOutput () const;
                
                string getName           () const;
                string getTypeString     () const;
                int    getTypeInt        () const;
                int    getModuleKind     () const;
                
                void   setType           (string type);
                void   setDescription    (string description);
                void   setMax            (int value);
                void   setMin            (int value);
        };
}

#endif
