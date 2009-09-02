/* Pandora Inventory module. These module makes an inventory of the machine where
the agent is instaled.

   Copyright (C) 2009 Artica ST.
   Written by Pablo de la Concepción

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

#ifndef	__PANDORA_MODULE_INVENTORY_H__
#define	__PANDORA_MODULE_INVENTORY_H__

#include "pandora_module.h"

namespace Pandora_Modules {
		  
  	const string module_invenory_cpu_str        = "CPU";
  	const string module_invenory_cdrom_str      = "CDROM";
  	const string module_invenory_video_str      = "Video";
  	const string module_invenory_hds_str        = "HD";
  	const string module_invenory_nics_str       = "NIC";
  	const string module_invenory_patches_str    = "Patches";
  	const string module_invenory_software_str   = "Software";
  	const string module_invenory_ram_str   		= "RAM";  	
  	
	/**
	 * Module to retrieve the Inventory of the machine.
	 */
	class Pandora_Module_Inventory : public Pandora_Module {
 	private:
        string options;
	public:
		Pandora_Module_Inventory (string name, string options);
		
		void   run                 ();
		TiXmlElement *getXml       ();
		void setOutput             (string output, string data_origin);
		void setOutput             (string output);
	};
}

#endif
