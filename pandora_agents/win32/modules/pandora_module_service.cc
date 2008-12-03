/* Pandora service module. These modules check if a service is running in the
   system.

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

#include "pandora_module_service.h"
#include "pandora_module_list.h"
#include "../windows/pandora_wmi.h"
#include "../pandora_strutils.h"
#include "../pandora_windows_service.h"
#include <algorithm>
#include <cctype>

using namespace Pandora;
using namespace Pandora_Modules;
using namespace Pandora_Strutils;

/** 
 * Creates a Pandora_Module_Service object.
 * 
 * @param name Module name.
 * @param service_name Service internal name to check.
 */
Pandora_Module_Service::Pandora_Module_Service (string name, string service_name)
	: Pandora_Module (name) {
	
	this->service_name = service_name;
	
	transform (service_name.begin (), service_name.end (),
		   this->service_name.begin (), (int (*) (int)) tolower);
	
	this->setKind (module_service_str);
	this->thread = 0;
	this->watchdog = false;
}

string
Pandora_Module_Service::getServiceName () const {
	return this->service_name;
}


bool
Pandora_Module_Service::isWatchdog () const {
	return this->watchdog;
}

void
Pandora_Module_Service::setWatchdog (bool watchdog) {
	this->watchdog = watchdog;
}

#define BUFFER_SIZE (16384)

void
async_run (Pandora_Module_Service *module) {
	HANDLE               event_log;
	HANDLE               event;
	DWORD                result;
	int                  res;
	string               str_res;
	BYTE                 buffer[BUFFER_SIZE];
	EVENTLOGRECORD      *record;
	DWORD                read;
	DWORD                needed;
	int                  event_id;
	bool                 service_event;
	string               prev_res;
	Pandora_Module_List *modules;
	
	prev_res = module->getLatestOutput ();
	modules = new Pandora_Module_List ();
	modules->addModule (module);
	
	while (1) {
		event_log = OpenEventLog (NULL, "Service Control Manager");
		if (event_log == NULL) {
			pandoraLog ("Could not open event log for %s.",
				    module->getServiceName ().c_str ());
			return;
		}
		event = CreateEvent (NULL, FALSE, FALSE, NULL);
		NotifyChangeEventLog (event_log, event);
		result = WaitForSingleObject (event, 10000);
		
		/* No event happened */
		if (result != WAIT_OBJECT_0) {
			CloseHandle (event);
			CloseEventLog (event_log);
			continue;
		}
		
		/* An event happened */
		service_event = false;
		record = (EVENTLOGRECORD *) buffer;
		
		/* Read events and check if any was relative to service */
		while (ReadEventLog (event_log,	
			EVENTLOG_FORWARDS_READ | EVENTLOG_SEQUENTIAL_READ,
			0, record, BUFFER_SIZE, &read, &needed)) {
			
			if (record->EventType != EVENTLOG_INFORMATION_TYPE)
				continue;
			event_id = record->EventID & 0x0000ffff;
			
			/* This number is the code for service start/stopping */
			if (event_id == 7036) {
				service_event = true;
				break;
			}
		}
		
		/* A start/stop action was thrown */
		if (service_event) {
			res = Pandora_Wmi::isServiceRunning (module->getServiceName ());
			str_res = inttostr (res);
			if (str_res != prev_res) {
				module->setOutput (str_res);
				prev_res = str_res;
				Pandora_Windows_Service::getInstance ()->sendXml (modules);
			}
			
			if (res == 0 && module->isWatchdog ()) {
				Pandora_Wmi::startService (module->getServiceName ());
			}
		}
		CloseHandle (event);
		CloseEventLog (event_log);
	}
	delete modules;
}

void
Pandora_Module_Service::run () {
	int res;
	
	try {
		Pandora_Module::run ();
	} catch (Interval_Not_Fulfilled e) {
		return;
	}
	
	res = Pandora_Wmi::isServiceRunning (this->service_name);
	this->setOutput (inttostr (res));
	
	/* Launch thread if it's asynchronous */
	if (this->async) {
		this->thread = CreateThread (NULL, 0,
					     (LPTHREAD_START_ROUTINE) async_run,
					     this, 0, NULL);
		this->async = false;
	}
}
