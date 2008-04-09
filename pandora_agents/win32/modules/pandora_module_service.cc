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
#include "../windows/pandora_wmi.h"
#include "../pandora_strutils.h"
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
}
