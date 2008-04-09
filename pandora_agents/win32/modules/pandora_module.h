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
#include "pandora_data.h"
#include "../tinyxml/tinyxml.h"
#include <list>
#include <string>

using namespace Pandora;

/**
 * Definition of Pandora modules.
 */
namespace Pandora_Modules {

	/**
	 * Defines the type of the module.
	 *
	 * The type of a module is the value type the module can have.
	 */
        typedef enum {
                TYPE_0,                  /**< Invalid value               */
                TYPE_GENERIC_DATA,       /**< The value is an integer     */
                TYPE_GENERIC_DATA_INC,   /**< The value is an integer with
					  *  incremental diferences       */
                TYPE_GENERIC_PROC,       /**< The value is a 0 or a 1     */
                TYPE_GENERIC_DATA_STRING /**< The value is a string       */
        } Module_Type;

        const string module_generic_data_str        = "generic_data";
        const string module_generic_data_inc_str    = "generic_data_inc";
        const string module_generic_proc_str        = "generic_proc";
        const string module_generic_data_string_str = "generic_data_string";

	/**
	 * Defines the kind of the module.
	 *
	 * The kind of a module is the work the module does.
	 */
        typedef enum {
                MODULE_0,         /**< Invalid kind                    */
                MODULE_EXEC,      /**< The module run a custom command */
                MODULE_PROC,      /**< The module checks for a running
				   *   process                         */
		MODULE_SERVICE,   /**< The module checks for a running
				   *   service                         */
		MODULE_FREEDISK,  /**< The module checks the free      */
		MODULE_CPUUSAGE,  /**< The module checks the CPU usage */
		MODULE_FREEMEMORY, /**< The module checks the amount of 
				   *   freememory in the system        */
		MODULE_ODBC       /**< The module performs a SQL query via ODBC */
        } Module_Kind;
        
        const string module_exec_str       = "module_exec";
        const string module_proc_str       = "module_proc";
	const string module_service_str    = "module_service";
	const string module_freedisk_str   = "module_freedisk";
	const string module_freememory_str = "module_freememory";
	const string module_cpuusage_str   = "module_cpuusage";
	const string module_odbc_str       = "module_odbc";

	/**
	 * Pandora module super-class exception.
	 */
        class Module_Exception : public Pandora::Pandora_Exception    { };
	
	/**
	 * An error happened with the module output.
	 */
        class Output_Error : public Pandora_Modules::Module_Exception { };
	
	/**
	 * The module value is not correct, usually beacause of the limits.
	 */
        class Value_Error : public Pandora_Modules::Module_Exception  { };
	
	/**
	 * The module does not satisfy its interval.
	 */
        class Interval_Not_Fulfilled : public Pandora_Modules::Module_Exception { };

        /**
	 * Pandora module super-class.
	 *
	 * Every defined module must inherit of this class.
	 */
        class Pandora_Module {
	private:
		int                   module_interval;
                int                   executions;
                int                   max, min;
                bool                  has_limits;
		string                module_type_str;
                Module_Type           module_type;
		string                module_kind_str;
                Module_Kind           module_kind;
		list<Pandora_Data *> *data_list;

		string getDataOutput (Pandora_Data *data);
		void   cleanDataList ();
        protected:
		/**
		 * Indicates if the module generated output in
		 * his last execution.
		 */
		bool        has_output;
		/**
		 * The name of the module.
		 */
                string      module_name;
		/**
		 * The description of the module.
		 */
                string      module_description;
        public:
                Pandora_Module                    (string name);
                virtual ~Pandora_Module           ();

                static Module_Type
			parseModuleTypeFromString (string type);
		
		static Module_Kind
			parseModuleKindFromString (string kind);
		
                void               setInterval    (int interval);
                
                TiXmlElement      *getXml         ();
                
                virtual void       run            ();
                
		virtual void       setOutput      (string output);
                
                string             getName        () const;
		string             getDescription () const;
                string             getTypeString  () const;
                Module_Type        getTypeInt     () const;
                Module_Type        getModuleType  () const;
		Module_Kind        getModuleKind  () const;
                
                void               setType        (string type);
		void               setKind        (string kind);
                void               setDescription (string description);
                void               setMax         (int value);
                void               setMin         (int value);
        };
}

#endif
