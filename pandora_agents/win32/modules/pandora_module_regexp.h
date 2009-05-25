/* Pandora regexp module. This module searches a file for matches of
   a regular expression.

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

#ifndef	__PANDORA_MODULE_REGEXP_H__
#define	__PANDORA_MODULE_REGEXP_H__

#include <iostream>
#include <fstream>
#include <string>

#include "pandora_module.h"
#include "boost/regex.h"

namespace Pandora_Modules {
    
	/**
	 * This module searches a file for matches of a regular expression.
	 */

	class Pandora_Module_Regexp : public Pandora_Module {
	private:
        string source;
        ifstream file;
        regex_t regexp;
        void restart ();

	public:
		Pandora_Module_Regexp (string name, string source, string pattern);
		virtual ~Pandora_Module_Regexp ();
		void run ();
	};
}

#endif
