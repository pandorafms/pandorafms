/* Manage a list of key-value options.

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

#ifndef	__PANDORA_AGENT_CONF_H__
#define	__PANDORA_AGENT_CONF_H__

#include "pandora.h"
#include <string>
#include <list>

using namespace std;

namespace Pandora {
	/**
	 * Agent main configuration class.
	 *
	 * Stores a list of Key_Value objects with the agent configuration.
	 * It parses a configuration file and supplies a function to get the
	 * configuration values.
	 */
	class Pandora_Agent_Conf {
	private:
		list<Key_Value> *key_values;

		Pandora_Agent_Conf             ();
	public:
		static Pandora_Agent_Conf *getInstance ();
		
		~Pandora_Agent_Conf            ();
		void               setFile     (string filename);
		string             getValue    (const string key);
	};
}

#endif /* __PANDORA_AGENT_CONF_H__ */
