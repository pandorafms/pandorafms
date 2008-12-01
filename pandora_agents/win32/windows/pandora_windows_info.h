/* Functions to get information about Windows operating system.

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

#ifndef	__PANDORA_WINDOWS_INFO_H__
#define	__PANDORA_WINDOWS_INFO_H__

#include <windows.h>
#include "../pandora.h"
#include <list>
#include <string>
#include "pandora_wmi.h"

using namespace Pandora;
using namespace std;

/**
 * Windows information functions.
 */
namespace Pandora_Windows_Info {
	string  getOSName         ();
	string  getOSVersion      ();
	string  getOSBuild        ();
	string  getSystemName     ();
	string  getSystemPath     ();
	HANDLE *getProcessHandles (string name);
}
#endif
