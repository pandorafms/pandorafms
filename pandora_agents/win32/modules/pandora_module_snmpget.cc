/* Pandora SNMPGet module. These modules SNMPGet a command.

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

#include "pandora_module_snmpget.h"

using namespace Pandora;
using namespace Pandora_Modules;

/** 
 * Creates a Pandora_Module_SNMPGet object.
 * 
 * @param name Module name
 * @param host Host to be SNMPGeted.
 */
Pandora_Module_SNMPGet::Pandora_Module_SNMPGet (string name, string version, string community, string agent, string oid, string advanced_options)
					 : Pandora_Module_Exec (name, "snmpget.exe -OUevqt -v " + version + " -c " + community + " " + advanced_options + " " + agent + " " + oid + " 2>nul | tr.exe -d \\\"\\n") {
	this->setKind (module_snmpget_str);
}

