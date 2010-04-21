/* Pandora exec module. These modules exec a command.

   Copyright (C) 2010 Artica ST.

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

#include "pandora_module_plugin.h"
#include "../pandora_strutils.h"
#include <windows.h> 

using namespace Pandora;
using namespace Pandora_Strutils;
using namespace Pandora_Modules;

/** 
 * Creates a Pandora_Module_Plugin object.
 * 
 * @param name Module name
 * @param plugin Command to be executed.
 */
Pandora_Module_Plugin::Pandora_Module_Plugin (string name, string plugin)
					 : Pandora_Module_Exec ("plugin", plugin) {
	this->setKind (module_plugin_str);
}

/** 
 * Get the plugin output.
 *
 * @return A pointer to the TiXmlElement if successful which has to be
 *         freed by the caller. NULL if the XML could not be created.
 */
string
Pandora_Module_Plugin::getXml () {
 	string        value;
	Pandora_Data *data = NULL;
	
	pandoraDebug ("%s getXML begin", module_name.c_str ());
	
	if (this->data_list) {
		data = data_list->front ();
		if (data != NULL) {
			value = data->getValue ();
		}
	}
	this->cleanDataList ();

	pandoraDebug ("%s getXML end", module_name.c_str ());
	return value;
}
