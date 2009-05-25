/* Pandora tcpcheck module. This module checks whether a tcp port is open.

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

#ifndef	__PANDORA_MODULE_TCPCHECK_H__
#define	__PANDORA_MODULE_TCPCHECK_H__

#include "pandora_module.h"

#define DEFAULT_TIMEOUT 3

namespace Pandora_Modules {
    
	/**
	 * This module checks whether a tcp port is open.
	 */

	class Pandora_Module_Tcpcheck : public Pandora_Module {
	private:
		string address;
		int port;
		int timeout;

	public:
		Pandora_Module_Tcpcheck (string name, string address, string port, string timeout);
		virtual ~Pandora_Module_Tcpcheck ();
		void run ();
	};
}

#endif
