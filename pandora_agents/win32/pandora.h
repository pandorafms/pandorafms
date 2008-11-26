/* Common functions to any pandora program.
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

#ifndef	__PANDORA__
#define	__PANDORA__

#include <list>
#include <string>

#undef WINVER
#define WINVER 0x0500
#include <windows.h>
#include "windows_service.h"

using namespace std;

#define	PANDORA_DEBUG 1

/**
 * Main application.
 */
namespace Pandora {
	/**
	 * A Key_Value object holds a relation between a value and a
	 * unique key value.
	 */
	class Key_Value {
	private:
		string key;
		string value;
	public:
		void   parseLine (string str);
		string getKey    ();
		string getValue  ();
	};
	
	static const HKEY  hkey          = HKEY_LOCAL_MACHINE;
	const char * const name          = "PandoraFMSAgent";
	const char * const display_name  = "Pandora FMS agent";
	const char * const description   = "The Pandora FMS Agent service";

	void   setPandoraInstallDir   (string dir);
	string getPandoraInstallDir   ();
	void   setPandoraInstallPath  (string path);
	string getPandoraInstallPath  ();
	void   setPandoraDebug        (bool dbg);
	bool   getPandoraDebug        ();
	string getPandoraAgentVersion ();
	
	void   pandoraDebug           (char *format, ...);
	void   pandoraLog             (char *format, ...);
	void   pandoraFree            (void * e);

	/**
	 * Super-class exception.
	 *
	 * Other exceptions generated in the application should inherate from
	 * this class. This allow a easier handling on throw and catch blocks.
	 */
	class Pandora_Exception { };
}

#endif /* __PANDORA_H__ */
