/*

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

#ifndef	__PANDORA_MODULE_LIST_H__
#define	__PANDORA_MODULE_LIST_H__

#include "../pandora.h"
#include "pandora_module.h"
#include <string>
#include <list>

using namespace std;
using namespace Pandora;

namespace Pandora_Modules {
	/**
	 * Class to handle a list of Pandora_Module objects.
	 *
	 * It provides a set of methods to iterate through the list
	 * by using a internal "current module" pointer.
	 */
	class Pandora_Module_List {
	private:
		list<Pandora_Module *>           *modules;
		list<Pandora_Module *>::iterator *current;
		void             parseModuleDefinition (string definition);
	public:
		Pandora_Module_List                    (string filename);
		Pandora_Module_List                    ();
		
		~Pandora_Module_List                   ();
		
		Pandora_Module * getCurrentValue       ();
		
		/* Add a module to the list */
		void             addModule             (Pandora_Module *module);
		
		/* Move to the first element of the list */
		void             goFirst               ();
		
		/* Move to the last element of the list */
		void             goLast                ();
		void             goNext                ();
		void             goPrev                ();
		
		bool             isLast                ();
		bool             isFirst               ();
	};
}
#endif /* __PANDORA_MODULE_LIST_H__ */
