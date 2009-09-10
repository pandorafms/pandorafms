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
#include <psapi.h>

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

HANDLE *
Pandora_Windows_Info::getProcessHandles (string name) {
	HANDLE  handle;
	HANDLE  handles[128];
	HANDLE *retval;
	DWORD   pids[1024], needed, npids;
	int     i;
	int     count;
	HMODULE modules;
	bool    success;
	TCHAR   process_name[MAX_PATH];

	if (! EnumProcesses (pids, sizeof (pids), &needed))
		return NULL;
	
	count = 0;
	npids = needed / sizeof (DWORD);
	for (i = 0; i < npids; i++) {
		if (pids[i] == 0)
			continue;
		
		/* Open process handle and find module base name (which is
		 supposed to be process name) */
		handle = OpenProcess (PROCESS_ALL_ACCESS, FALSE, pids[i]);
		if (handle == NULL)
			continue;
		success = EnumProcessModules (handle, &modules, sizeof (modules), &needed);
		if (! success) {
			CloseHandle (handle);
			continue;
		}
		GetModuleBaseName (handle, modules, process_name, sizeof (process_name) / sizeof (TCHAR));
		
		if (stricmp (process_name, name.c_str ()) == 0) {
			/* Process found */
			handles[count++] = handle;
		}
	}
	
	if (count == 0)
		return NULL;
	retval = (HANDLE *) malloc (count * sizeof (HANDLE));
	for (i = 0; i < count; i++)
		retval[i] = handles[i];
	
	return retval;
}

/**
 * Get the value of the given registry key
 *
 * @return The system name of the operating system.
 */
string
Pandora_Windows_Info::getRegistryValue (HKEY root, const string treepath,
                                        const string keyname) {
    long            reg_result;
    list<string>    tokens;
    HKEY            prev, current;
    DWORD           data_type;
    DWORD           buffer_size = 1024;
    BYTE            *buffer;
    string          result = "";
       
    stringtok (tokens, treepath, "\\");
 
    // Go through the registry path
    prev = root;
    list<string>::const_iterator i = tokens.begin();
    while (i != tokens.end()) {            
        reg_result = RegOpenKeyEx (prev, (*i).c_str (), 0, KEY_READ, &current);
        if (reg_result != ERROR_SUCCESS) {
            RegCloseKey (prev);
            return "";
        }

        reg_result = RegCloseKey (prev);
        if (reg_result != ERROR_SUCCESS) {
            RegCloseKey (current);
            return "";
        }

        prev = current;
        i++;
    }

    buffer = (BYTE *) malloc (buffer_size * sizeof (BYTE));
    if (buffer == NULL) {
        RegCloseKey (current);
        return "";
    }

    reg_result = RegQueryValueEx (current, keyname.c_str (), NULL, 
                                  &data_type, buffer, &buffer_size);

    // The buffer is not large enough
    while (reg_result == ERROR_MORE_DATA) {
        buffer_size *= 2;
        buffer = (BYTE *) realloc (buffer, buffer_size * sizeof (BYTE));
        if (buffer == NULL) {
            RegCloseKey (current);
            return "";
        }
        reg_result = RegQueryValueEx (current, keyname.c_str (), NULL, 
                                      &data_type, buffer, &buffer_size);
    }

    // The key could not be read
    if (reg_result != ERROR_SUCCESS) {
        RegCloseKey (current);
        free ((void *)buffer);
        return "";
    }

    switch (data_type) {

        /* String value */
        case REG_EXPAND_SZ:
        case REG_MULTI_SZ:
        case REG_SZ:
            result = (char *) buffer;
            break;

        /* Numeric value */
         default:
            for (int i = 0; i < (int) buffer_size; i++) {
                string hex = longtohex (buffer[i]);
                if (hex == "0") {
                    hex = "0" + hex;
                }
                result = hex + result;
            }

            int result_int = strtoint (result);
            result = inttostr (result_int);
            break;
    }

    RegCloseKey (current);
    free ((void *)buffer);
    return result;
}

/**
 * Get a list of installed software
 * 
 * @param rows Result list.
 * @param rows Field separator.
 * @return Result list length.
 */
int
Pandora_Windows_Info::getSoftware (list<string> &rows, string separator) {
    int    num_objects = 0;
    string tree;
    HKEY   key;
    DWORD  sub_keys, ret, name_len;
    TCHAR  sub_key_name[MAX_KEY_LENGTH];
    string app_name, system, version, reg_path;
     
    tree = "SOFTWARE\\Microsoft\\Windows\\CurrentVersion\\Uninstall";
    ret = RegOpenKeyEx (HKEY_LOCAL_MACHINE, TEXT (tree.c_str ()), 0, KEY_READ, &key);
    if (ret != ERROR_SUCCESS) {
       return 0;
    }
    
    RegQueryInfoKey (key, NULL, NULL, NULL, &sub_keys, NULL, NULL,
                     NULL, NULL, NULL, NULL,NULL);
          
    for (int i = 0; i < (int) sub_keys; i++) {
        name_len = MAX_KEY_LENGTH;
        ret = RegEnumKeyEx (key, i, sub_key_name, &name_len, 
                            NULL, NULL, NULL, NULL); 
             
        if (ret != ERROR_SUCCESS) {
           continue;
        }
        
        /* Get application name */
        reg_path = tree + "\\" +  sub_key_name;
        app_name = getRegistryValue (HKEY_LOCAL_MACHINE, reg_path, "DisplayName");
        if (app_name == "") {
            continue;
        }
     
        /* Skip system components */
        system = getRegistryValue (HKEY_LOCAL_MACHINE, reg_path, "SystemComponent");
        if (system != "") {
           continue;
        }
        
        /* Get application version */
        version = getRegistryValue (HKEY_LOCAL_MACHINE, reg_path, "DisplayVersion");
        if (version != "") {
            app_name += separator + version;
        }
        
        rows.push_back (app_name);
        num_objects++;
    }
    
    RegCloseKey (key);
	return num_objects;
}
