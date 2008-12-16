/* Pandora wmiquery module. This module runs WQL queries.

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

#include "pandora_module_wmiquery.h"
#include "../windows/pandora_wmi.h"
#include "../pandora_windows_service.h"

using namespace Pandora;
using namespace Pandora_Modules;

/** 
 * Creates a Pandora_Module_WMIQuery object.
 * 
 * @param name Module name.
 */
Pandora_Module_WMIQuery::Pandora_Module_WMIQuery (string name, string query, string column)
	: Pandora_Module (name) {

	this->query = query;
	this->column = column;
	this->setKind (module_wmiquery_str);
}

void
Pandora_Module_WMIQuery::run () {
	string value;
	list<string> rows;
	list<string>::iterator row;

	// Run
	try {
		Pandora_Module::run ();
	} catch (Interval_Not_Fulfilled e) {
		return;
	}

	if (this->query.empty () || this->column.empty ()) {
		return;
	}

	Pandora_Wmi::runWMIQuery (this->query, this->column, rows);

	// No data
	if (rows.size () < 1) {
		this->setOutput ("");
		return;
	}

	for (row = rows.begin (); row != rows.end(); ++row) {
		this->setOutput (*row);
	}
}
