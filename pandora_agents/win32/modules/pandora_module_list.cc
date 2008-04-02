/*

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
#include "pandora_module_list.h"
#include "pandora_module_exec.h"
#include "pandora_module_proc.h"
#include "pandora_module_service.h"
#include "pandora_module_freedisk.h"
#include "pandora_module_freememory.h"
#include "pandora_module_cpuusage.h"
#include "pandora_module_odbc.h"
#include <fstream>

using namespace std;

/** 
 * Read and set a key-value set from a file.
 *
 * It parses the file and create a Pandora_Module object with
 *
 * @param filename Path to the configuration file that includes the
 *        module definitions.
 */
Pandora_Modules::Pandora_Module_List::Pandora_Module_List (string filename) {
        ifstream     file (filename.c_str ());
        string       buffer;
        unsigned int pos;

        this->modules = new list<Pandora_Module *> ();
        
        if (!file.is_open ()) {
                return;
        }
        
        /* Read and set the file */
        while (!file.eof ()) {
                /* Set the value from each line */
                getline (file, buffer);
                
                /* Ignore blank or commented lines */
                if (buffer[0] != '#' && buffer[0] != '\n' && buffer[0] != '\0') {
                        pos = buffer.find ("module_begin");  
                        if (pos != string::npos) {
                                string str_module = buffer + "\n";
                                bool   module_end = false;
                                
                                while (!module_end) {
                                        if (file.eof ()) {
                                                break;
                                        }
                                        getline (file, buffer);
                                        pos = buffer.find ("module_end");
                                        module_end = (pos != string::npos);
                                        str_module += buffer + "\n";
                                }
                                
                                this->parseModuleDefinition (str_module);
                        }
                }
        }
        file.close ();
        
        current = new std::list<Pandora_Module *>::iterator ();
        *current = modules->begin ();
}

/** 
 * Destroy the list.
 *
 * Note it also deletes all modules from the list.
 */
Pandora_Modules::Pandora_Module_List::~Pandora_Module_List () {
	Pandora_Module                       *module;
	std::list<Pandora_Module *>::iterator iter;
	
        if (modules->size () > 0) {
                iter = modules->begin ();
                do {
                        module = *iter;
			delete module;
                        iter++;
                } while (iter != modules->end ());
        }
	delete modules;
	delete current;
	modules = NULL;
	current = NULL;
}

void
Pandora_Modules::Pandora_Module_List::parseModuleDefinition (string definition) {
        Pandora_Module            *module;
        Pandora_Module_Exec       *module_exec;
        Pandora_Module_Proc       *module_proc;
	Pandora_Module_Service    *module_service;
	Pandora_Module_Freedisk   *module_freedisk;
	Pandora_Module_Cpuusage   *module_cpuusage;
	Pandora_Module_Freememory *module_freememory;
	Pandora_Module_Odbc       *module_odbc;
        
        module = Pandora_Module_Factory::getModuleFromDefinition (definition);
        
        if (module != NULL) {
		cout << module->getModuleKind () << endl;
                switch (module->getModuleKind ()) {
                case MODULE_EXEC:
                        module_exec = (Pandora_Module_Exec *) module;
                        modules->push_back (module_exec);
                        
                        break;
                case MODULE_PROC:
                        module_proc = (Pandora_Module_Proc *) module;
                        modules->push_back (module_proc);
                        
                        break;

		case MODULE_SERVICE:
                        module_service = (Pandora_Module_Service *) module;
                        modules->push_back (module_service);
                        
                        break;

		case MODULE_FREEDISK:
			module_freedisk = (Pandora_Module_Freedisk *) module;
                        modules->push_back (module_freedisk);
                        
                        break;
		case MODULE_FREEMEMORY:
			module_freememory = (Pandora_Module_Freememory *) module;
                        modules->push_back (module_freememory);
                        
                        break;
		case MODULE_CPUUSAGE:
			module_cpuusage = (Pandora_Module_Cpuusage *) module;
                        modules->push_back (module_cpuusage);
                        
                        break;
		case MODULE_ODBC:
			module_odbc = (Pandora_Module_Odbc *) module;
                        modules->push_back (module_odbc);
                        cout << "ASDS" << endl;
                        break;
                default:
			cout << "NONE" << endl;
                        break;
                }
        }
}


/** 
 * Get the Pandora_Module that is pointed by the internal current pointer.
 * 
 * @return The current Pandora_Module.
 */
Pandora_Module *
Pandora_Modules::Pandora_Module_List::getCurrentValue () { 
        return *(*current);
}

/** 
 * Move the current pointer to the first element of the list.
 */
void
Pandora_Modules::Pandora_Module_List::goFirst () {
        if (modules != NULL) {
                *current = modules->begin ();
        }
}

/** 
 * Move the current pointer to the last element of the list.
 */
void
Pandora_Modules::Pandora_Module_List::goLast () {
        if (modules != NULL) {
                *current = modules->end ();
        }
}

/** 
 * Move the current pointer to the next element of the list.
 */
void
Pandora_Modules::Pandora_Module_List::goNext () {
        if (current != NULL && !isLast ()) {
                (*current)++;
        }
}

/** 
 * Move the current pointer to the previous element of the list.
 */
void
Pandora_Modules::Pandora_Module_List::goPrev () {
        if (current != NULL && !isFirst ()) {
                (*current)--;
        }
}

/** 
 * Check if the current pointer is the last one of the list.
 */
bool
Pandora_Modules::Pandora_Module_List::isLast () {
        if (current == NULL || modules == NULL) {
                return true;
        }
        return *current == modules->end ();
}

/** 
 * Check if the current pointer is the first one of the list.
 */
bool
Pandora_Modules::Pandora_Module_List::isFirst () {
        if (current == NULL || modules == NULL) {
                return true;
        }
        return *current == modules->begin ();
}
