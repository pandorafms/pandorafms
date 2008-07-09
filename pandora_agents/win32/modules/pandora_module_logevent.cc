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
#include "../pandora_windows_service.h"

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
	int interval, module_interval;
	string value;
	list<string> event_list;
	list<string>::iterator event;
	Pandora_Agent_Conf::Pandora_Agent_Conf *conf;
	SYSTEMTIME system_time;
	
	conf = Pandora_Agent_Conf::getInstance ();
	
	// Get execution interval
	value = conf->getValue ("interval");
	interval = atoi(value.c_str ());
	
	module_interval = this->getInterval ();    
	if (module_interval > 0) {
		interval *= module_interval;
	}
	
	// Run
	try {
		Pandora_Module::run ();
	} catch (Interval_Not_Fulfilled e) {
		return;
	}
	
	Pandora_Wmi::getEventList (this->source, this->type, this->pattern, interval, event_list);
	
	// No data
	if (event_list.size () < 1) {
		this->setOutput ("");
		return;
	}
	
	for (event = event_list.begin (); event != event_list.end(); ++event) {
		// No WMI timestamp?
		if (event->size () < 26) {
			this->setOutput (*event);
			continue;
		}
		
		// Get the timestamp
		Pandora_Wmi::convertWMIDate (event->substr (0, 26), &system_time);
		
		// Store the data
		this->setOutput (event->substr (26), &system_time);
	}
}
