/* Functions to get information about Windows.

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

#include "pandora_windows_info.h"
#include "../pandora_strutils.h"

#define MAX_KEY_LENGTH 255

using namespace Pandora_Strutils;
using namespace Pandora_Windows_Info;

/** 
 * Get the name of the running operating system.
 * 
 * @return The name of the operating system.
 */
string
Pandora_Windows_Info::getOSName () {
	return Pandora_Wmi::getOSName ();
}

/** 
 * Get the versioof the running operating system.
 * 
 * @return The version of the operating system.
 */
string
Pandora_Windows_Info::getOSVersion () {
	return Pandora_Wmi::getOSVersion ();
}

/** 
 * Get the build of the running operating system.
 * 
 * @return The build of the operating system.
 */
string
Pandora_Windows_Info::getOSBuild () {
	return Pandora_Wmi::getOSBuild();
}

/** 
 * Get the system name of the running operating system.
 * 
 * @return The system name of the operating system.
 */
string
Pandora_Windows_Info::getSystemName () {
	return Pandora_Wmi::getSystemName ();
}

/** 
 * Get the system path of the running operating system.
 * 
 * @return The system path of the operating system.
 */
string
Pandora_Windows_Info::getSystemPath () {
	char buffer[MAX_PATH];
	
	::GetWindowsDirectory (buffer, MAX_PATH+1);
	
	string str_path = buffer;
	str_path = trim (str_path);
	return str_path;
}
