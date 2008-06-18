/* Pandora logevent module. This module checks for log events that match a given
   pattern.

   Copyright (C) 2008 Artica ST.
   Written by Ramon Novoa.

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

#include "pandora_module_logevent.h"
#include "../windows/pandora_wmi.h"

using namespace Pandora;
using namespace Pandora_Modules;

/** 
 * Creates a Pandora_Module_Logevent object.
 * 
 * @param name Module name.
 * @param service_name Service internal name to check.
 */
Pandora_Module_Logevent::Pandora_Module_Logevent (string name, string source, string type, string pattern)
	: Pandora_Module (name) {

        this->source = source;
        this->type = type;
        this->pattern = pattern;
        this->setKind (module_logevent_str);
}

void
Pandora_Module_Logevent::run () {
    list<string> event_list;
    list<string>::iterator event;

	try {
        Pandora_Module::run ();
    } catch (Interval_Not_Fulfilled e) {
        return;
    }
        
    Pandora_Wmi::getEventList (this->source, this->type, this->pattern, this->getInterval (), event_list);

    if (event_list.size () < 1) {
        this->setOutput ("");
        return;
    }

    for(event = event_list.begin (); event != event_list.end(); ++event) {
        this->setOutput (*event);        
    }
}
