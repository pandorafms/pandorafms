/* Pandora exec module. These modules exec a powershell command

   Copyright (c) 2006-2023 Pandora FMS.

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

#ifndef	__PANDORA_MODULE_EXEC_POWERWSHELL_H__
#define	__PANDORA_MODULE_EXEC_POWERWSHELL_H__

#include "pandora_module.h"

namespace Pandora_Modules {
	/**
	 * Module to execute a powershell command.
	 *
	 * Any custom order that want to be executed can be put in
	 * the <code>util</code> directory into the Pandora agent path.
	 */
	class Pandora_Module_Exec_Powershell : public Pandora_Module {

	private:
		string module_exec;

	public:
		Pandora_Module_Exec_Powershell	(string name, string exec);
		void run ();
	};
}

#endif
