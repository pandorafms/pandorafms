/* Pandora freedisk module. These modules check the free space in a
   logical drive.

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

#include "pandora_module_freedisk.h"
#include "../windows/pandora_wmi.h"
#include "../pandora_strutils.h"
#include <algorithm>
#include <cctype>

using namespace Pandora;
using namespace Pandora_Modules;
using namespace Pandora_Strutils;

Pandora_Module_Freedisk::Pandora_Module_Freedisk (string name, string disk_id)
	: Pandora_Module (name) {
        
        this->disk_id = disk_id;
        
        transform (disk_id.begin (), disk_id.end (),
                   this->disk_id.begin (), (int (*) (int)) toupper);
	
        this->module_kind_str = module_freedisk_str;
        this->module_kind     = MODULE_FREEDISK;
}

void
Pandora_Module_Freedisk::run () {
	long res;
	
	try {
                Pandora_Module::run ();
        } catch (Interval_Not_Fulfilled e) {
                return;
        }

	try {
		res = Pandora_Wmi::getDiskFreeSpace (this->disk_id);
			
		output = longtostr (res);
		pandoraDebug ("%s", output.c_str ());
	} catch (Pandora_Wmi::Pandora_Wmi_Error e) {
		this->has_output = false;
	}
}
