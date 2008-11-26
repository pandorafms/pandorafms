/* Defines a factory of Pandora modules based on the module definition

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

#ifndef	__PANDORA_MODULE_FACTORY_H__
#define	__PANDORA_MODULE_FACTORY_H__

#include "../pandora.h"
#include "pandora_module.h"
#include <string>

using namespace std;
using namespace Pandora_Modules;

/**
 * Factoy to create Pandora_Module objects by parsing a definition.
 */
namespace Pandora_Module_Factory {
	Pandora_Module * getModuleFromDefinition (string definition);
}

#endif
