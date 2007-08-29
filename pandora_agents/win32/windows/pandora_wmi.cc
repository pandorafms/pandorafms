/* Class to manage the Windows Management Instrumentation(WMI).
   It depends on disphelper library (http://disphelper.sourceforge.net)

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
   Inc., 59 Temple Place - Suite 330, Boston, MAB02111-1307, USA.
*/

#include "pandora_wmi.h"
#include "../pandora_strutils.h"
#include <iostream>
#include <algorithm>
#include <cctype>
#include <sstream>

using namespace std;
using namespace Pandora_Wmi;

static LPWSTR
getWmiStr (LPCWSTR computer) {
	static WCHAR wmi_str [256];

	wcscpy (wmi_str, L"winmgmts:{impersonationLevel=impersonate}!\\\\");

	if (computer) {
		wcsncat (wmi_str, computer, 128);
	} else {
		wcscat (wmi_str, L".");
	}

	wcscat (wmi_str, L"\\root\\cimv2");

	return wmi_str;
}

/** 
 * Check if a process is running.
 * 
 * @param process_name Name of the process with extension.
 * 
 * @return Number of instances of the process running.
 */
int
Pandora_Wmi::isProcessRunning (string process_name) {
	CDhInitialize init;
	CDispPtr      wmi_svc, quickfixes;
	string        name;
	int           result = 0;
	string        query;

	query = "SELECT * FROM Win32_Process WHERE Name=\"" + process_name + "\"";
	cout << "Query: " << query << endl;
	try {	
		dhCheck (dhGetObject (getWmiStr (L"."), NULL, &wmi_svc));
		dhCheck (dhGetValue (L"%o", &quickfixes, wmi_svc,
				     L".ExecQuery(%T)",
				     query.c_str ()));
	
		FOR_EACH (quickfix, quickfixes, NULL) {
			result++;
		} NEXT_THROW (quickfix);
	} catch (string errstr) {
		pandoraLog ("isProcessRunning error. %s", errstr.c_str ());
	}

	return result;
}

/** 
 * Check if a Windows service is running.
 * 
 * @param service_name Internal name of the service to check.
 * 
 * @retval 1 The service is running
 * @retval 0 The service is stopped
 */
int
Pandora_Wmi::isServiceRunning (string service_name) {
	CDhInitialize init;
	CDispPtr      wmi_svc, quickfixes;
	string        query;
	char         *state;
	int           retval;

	query = "SELECT * FROM Win32_Service WHERE Name = \"" + service_name + "\"";

	try {	
		dhCheck (dhGetObject (getWmiStr (L"."), NULL, &wmi_svc));
		dhCheck (dhGetValue (L"%o", &quickfixes, wmi_svc,
				     L".ExecQuery(%T)",
				     query.c_str ()));
	
		FOR_EACH (quickfix, quickfixes, NULL) {
			dhGetValue (L"%s", &state, quickfix,
				    L".State");
		
			retval = (state == "Running") ? 1 : 0;
			dhFreeString (state);
		
			return retval;
		} NEXT_THROW (quickfix);
	} catch (string errstr) {
		pandoraLog ("isServiceRunning error. %s", errstr.c_str ());
	}

	return 0;
}

/** 
 * Get the free space in a logical disk drive.
 * 
 * @param disk_id Disk drive letter (C: for example).
 * 
 * @return Free space amount in MB.
 *
 * @exception Pandora_Wmi_Exception Throwd if an error occured when reading
 *            from WMI database.
 */
unsigned long
Pandora_Wmi::getDiskFreeSpace (string disk_id) {
	CDhInitialize      init;
	CDispPtr           wmi_svc, quickfixes;
	string             id, space_str;
	unsigned long long space = 0;
	string             query;

	query = "SELECT DeviceID, FreeSpace FROM Win32_LogicalDisk WHERE DeviceID = \"" + disk_id + "\"";

	try {
		dhCheck (dhGetObject (getWmiStr (L"."), NULL, &wmi_svc));
		dhCheck (dhGetValue (L"%o", &quickfixes, wmi_svc,
				     L".ExecQuery(%T)",
				     query.c_str ()));
	
		FOR_EACH (quickfix, quickfixes, NULL) {
			dhGetValue (L"%d", &space, quickfix,
				    L".FreeSpace");
		
			return space / 1024 / 1024;
		} NEXT_THROW (quickfix);
	} catch (string errstr) {
		pandoraLog ("getDiskFreeSpace error. %s", errstr.c_str ());
	}

	throw Pandora_Wmi_Exception ();
}

/** 
 * Get the CPU usage percentage in the last minutes.
 * 
 * @param cpu_id CPU identifier.
 * 
 * @return The usage percentage of the CPU.
 *
 * @exception Pandora_Wmi_Exception Throwd if an error occured when reading
 *            from WMI database.
 */
int
Pandora_Wmi::getCpuUsagePercentage (int cpu_id) {
	CDhInitialize init;
	CDispPtr      wmi_svc, quickfixes;
	string        query;
	long          load_percentage;
	std::ostringstream stm;

	stm << cpu_id;
	query = "SELECT * FROM Win32_Processor WHERE DeviceID = \"CPU" + stm.str () + "\"";

	try {
		dhCheck (dhGetObject (getWmiStr (L"."), NULL, &wmi_svc));
		dhCheck (dhGetValue (L"%o", &quickfixes, wmi_svc,
				     L".ExecQuery(%T)",
				     query.c_str ()));
	
		FOR_EACH (quickfix, quickfixes, NULL) {
			dhGetValue (L"%d", &load_percentage, quickfix,
				    L".LoadPercentage");
		
			return load_percentage;
		} NEXT_THROW (quickfix);
	} catch (string errstr) {
		cout << query << endl;
		cout << errstr << endl;
		pandoraLog ("getCpuUsagePercentage error. %s", errstr.c_str ());
	}

	throw Pandora_Wmi_Exception ();
}

/** 
 * Get the amount of free memory in the system
 *
 * @return The amount of free memory in MB.
 * @exception Pandora_Wmi_Exception Throwd if an error occured when reading
 *            from WMI database.
 */
long
Pandora_Wmi::getFreememory () {
	CDhInitialize init;
	CDispPtr      wmi_svc, quickfixes;
	long          free_memory;

	try {
		dhCheck (dhGetObject (getWmiStr (L"."), NULL, &wmi_svc));
		dhCheck (dhGetValue (L"%o", &quickfixes, wmi_svc,
				     L".ExecQuery(%S)",
				     L"SELECT * FROM Win32_PerfRawData_PerfOS_Memory "));
	
		FOR_EACH (quickfix, quickfixes, NULL) {
			dhGetValue (L"%d", &free_memory, quickfix,
				    L".AvailableMBytes");
		
			return free_memory;
		} NEXT_THROW (quickfix);
	} catch (string errstr) {
		pandoraLog ("getFreememory error. %s", errstr.c_str ());
	}

	throw Pandora_Wmi_Exception ();	
}

/**
 * Get the name of the operating system.
 * 
 * @return The name of the operating system.
 */
string
Pandora_Wmi::getOSName () {
	CDhInitialize init;
	CDispPtr      wmi_svc, quickfixes;
	char         *name = NULL;
	string        ret;

	try {
		dhCheck (dhGetObject (getWmiStr (L"."), NULL, &wmi_svc));
		dhCheck (dhGetValue (L"%o", &quickfixes, wmi_svc,
				     L".ExecQuery(%S)",
				     L"SELECT * FROM Win32_OperatingSystem "));
	
		FOR_EACH (quickfix, quickfixes, NULL) {
			dhGetValue (L"%s", &name, quickfix,
				    L".Caption");
		
			ret = name;
			dhFreeString (name);
		
		} NEXT_THROW (quickfix);
	} catch (string errstr) {
		pandoraLog ("getOSName error. %s", errstr.c_str ());
	}

	return ret;
}

/** 
 * Get the version of the operating system.
 * 
 * @return The version of the operaing system.
 */
string
Pandora_Wmi::getOSVersion () {
	CDhInitialize init;
	CDispPtr      wmi_svc, quickfixes;
	char         *version = NULL;
	string        ret;

	try {
		dhCheck (dhGetObject (getWmiStr (L"."), NULL, &wmi_svc));
		dhCheck (dhGetValue (L"%o", &quickfixes, wmi_svc,
				     L".ExecQuery(%S)",
				     L"SELECT * FROM Win32_OperatingSystem "));
	
		FOR_EACH (quickfix, quickfixes, NULL) {
			dhGetValue (L"%s", &version, quickfix,
				    L".CSDVersion");
		
			ret = version;
			dhFreeString (version);
		
		} NEXT_THROW (quickfix);
	} catch (string errstr) {
		pandoraLog ("getOSVersion error. %s", errstr.c_str ());
	}

	return ret;
}

/** 
 * Get the build of the operating system.
 * 
 * @return The build of the operating system.
 */
string
Pandora_Wmi::getOSBuild () {
	CDhInitialize init;
	CDispPtr      wmi_svc, quickfixes;
	char         *build = NULL;
	string        ret;
	
	try {
		dhCheck (dhGetObject (getWmiStr (L"."), NULL, &wmi_svc));
		dhCheck (dhGetValue (L"%o", &quickfixes, wmi_svc,
				     L".ExecQuery(%S)",
				     L"SELECT * FROM Win32_OperatingSystem "));

		FOR_EACH (quickfix, quickfixes, NULL) {
			dhGetValue (L"%s", &build, quickfix,
				    L".Version");
                        
			ret = build;
			dhFreeString (build);
			
		} NEXT_THROW (quickfix);
	} catch (string errstr) {
		pandoraLog ("getOSBuild error. %s", errstr.c_str ());
	}
        
	return ret;
}

/** 
 * Get the system name of the operating system.
 * 
 * @return The system name of the operating system.
 */
string
Pandora_Wmi::getSystemName () {
	CDhInitialize init;
	CDispPtr      wmi_svc, quickfixes;
	char         *name = NULL;
	string        ret;
	
	try {
		dhCheck (dhGetObject (getWmiStr (L"."), NULL, &wmi_svc));
		dhCheck (dhGetValue (L"%o", &quickfixes, wmi_svc,
				     L".ExecQuery(%S)",
				     L"SELECT * FROM Win32_OperatingSystem "));

		FOR_EACH (quickfix, quickfixes, NULL) {
			dhGetValue (L"%s", &name, quickfix,
				    L".CSName");
                        
			ret = name;
			dhFreeString (name);
			
		} NEXT_THROW (quickfix);
	} catch (string errstr) {
		pandoraLog ("getSystemName error. %s", errstr.c_str ());
	}
        
	return ret;
}
