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
#include <ctime>
#include <winuser.h>
#include <stdio.h>  

#define INVENTORY_FIELD_SEPARATOR 

using namespace std;
using namespace Pandora_Wmi;

static LPWSTR
getWmiStr (LPCWSTR computer) {
	static WCHAR wmi_str[256];

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
	string        str_state;
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
			str_state = state;
			retval = (str_state == "Running") ? 1 : 0;
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
	double             free_space = 0;
	string             query;

	query = "SELECT FreeSpace FROM Win32_LogicalDisk WHERE DeviceID = \"" + disk_id + "\"";
	
	struct QFix {
		CDhStringA free_space; 	 
	};
	
	try {
		dhCheck (dhGetObject (getWmiStr (L"."), NULL, &wmi_svc));
		dhCheck (dhGetValue (L"%o", &quickfixes, wmi_svc,
				     L".ExecQuery(%T)",
				     query.c_str ()));
	
		FOR_EACH (quickfix, quickfixes, NULL) {
			dhGetValue (L"%e", &free_space, quickfix,
				    L".FreeSpace");
			
			// 1048576 = 1024 * 1024
			return (unsigned long) free_space / 1048576;
		} NEXT_THROW (quickfix);
	} catch (string errstr) {
		pandoraLog ("getDiskFreeSpace error. %s", errstr.c_str ());
	}

	throw Pandora_Wmi_Exception ();
}

/** 
 * Get the free space in a logical disk drive.
 * 
 * @param disk_id Disk drive letter (C: for example).
 * 
 * @return Free space percentage.
 *
 * @exception Pandora_Wmi_Exception Throwd if an error occured when reading
 *            from WMI database.
 */
unsigned long
Pandora_Wmi::getDiskFreeSpacePercent (string disk_id) {
	CDhInitialize      init;
	CDispPtr           wmi_svc, quickfixes;
	double      free_space = 0, size = 0;
	double      total_free_space = 0, total_size = 0;
	string             query, free_str, size_str;

	query = "SELECT Size, FreeSpace FROM Win32_LogicalDisk WHERE DeviceID = \"" + disk_id + "\"";

	try {
		dhCheck (dhGetObject (getWmiStr (L"."), NULL, &wmi_svc));
		dhCheck (dhGetValue (L"%o", &quickfixes, wmi_svc,
				     L".ExecQuery(%T)",
				     query.c_str ()));
	
		FOR_EACH (quickfix, quickfixes, NULL) {
			dhGetValue (L"%e", &free_space, quickfix,
				    L".FreeSpace");
			dhGetValue (L"%e", &size, quickfix,
				    L".Size");

			free_space = Pandora_Strutils::strtoulong (free_str);
			size = Pandora_Strutils::strtoulong (size_str);
            total_free_space += free_space;
            total_size += size;
		} NEXT_THROW (quickfix);
		
		if (total_size == 0) {
            return 0;
        }
        
        return (unsigned long) (total_free_space * 100 / total_size);
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
 * @exception Pandora_Wmi_Exception Throwed if an error occured when reading
 *            from WMI database.
 */
int
Pandora_Wmi::getCpuUsagePercentage (int cpu_id) {
	CDhInitialize init;
	CDispPtr      wmi_svc, quickfixes;
	string        query;
	long          load_percentage, total_load;
	int           total_cpus;
	std::ostringstream stm;

    // Select all CPUs
    if (cpu_id < 0) {
	    query = "SELECT * FROM Win32_Processor";
    // Select a single CPUs
    } else {
	    stm << cpu_id;
	    query = "SELECT * FROM Win32_Processor WHERE DeviceID = \"CPU" + stm.str () + "\"";
    }

	try {
		dhCheck (dhGetObject (getWmiStr (L"."), NULL, &wmi_svc));
		dhCheck (dhGetValue (L"%o", &quickfixes, wmi_svc,
				     L".ExecQuery(%T)",
				     query.c_str ()));

        total_cpus = 0;
        total_load = 0;
		FOR_EACH (quickfix, quickfixes, NULL) {
			dhGetValue (L"%d", &load_percentage, quickfix,
				    L".LoadPercentage");

			total_cpus++;
			total_load += load_percentage;
		} NEXT_THROW (quickfix);

		if (total_cpus == 0) {
            return 0;
        }

		return total_load / total_cpus;
	} catch (string errstr) {
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
 * Get the percentage of free memory in the system
 *
 * @return The percentage of free memory.
 * @exception Pandora_Wmi_Exception Throwd if an error occured when reading
 *            from WMI database.
 */
long
Pandora_Wmi::getFreememoryPercent () {
	CDhInitialize init;
	CDispPtr      wmi_svc, quickfixes;
	long          free_memory, total_memory;

	try {
		dhCheck (dhGetObject (getWmiStr (L"."), NULL, &wmi_svc));
		dhCheck (dhGetValue (L"%o", &quickfixes, wmi_svc,
				     L".ExecQuery(%S)",
				     L"SELECT FreePhysicalMemory, TotalVisibleMemorySize FROM Win32_OperatingSystem "));
	
		FOR_EACH (quickfix, quickfixes, NULL) {
			dhGetValue (L"%d", &free_memory, quickfix,
				    L".FreePhysicalMemory");

			dhGetValue (L"%d", &total_memory, quickfix,
				    L".TotalVisibleMemorySize");

			if (total_memory == 0) {
                return 0;
            }
            
            return free_memory * 100 / total_memory;
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

/**
 * Runs a program in a new process.
 *
 * @param command Command to run, with parameters
 * @param flags Process creation flags
 */
bool
Pandora_Wmi::runProgram (string command, DWORD flags) {
	PROCESS_INFORMATION process_info;
	STARTUPINFO         startup_info;
	bool                success;
	char               *cmd;
	
	if (command == "")
		return false;
	
	ZeroMemory (&startup_info, sizeof (startup_info));
	startup_info.cb = sizeof (startup_info);
	ZeroMemory (&process_info, sizeof (process_info));
	
	pandoraDebug ("Start process \"%s\".", command.c_str ());
	cmd = strdup (command.c_str ());
	success = CreateProcess (NULL, cmd, NULL, NULL, FALSE, flags,
				 NULL, NULL, &startup_info, &process_info);
	pandoraFree (cmd);
	
	if (success) {
		pandoraDebug ("The process \"%s\" was started.", command.c_str ());
		return true;
	}
	pandoraLog ("Could not start process \"%s\". Error %d", command.c_str (),
		    GetLastError());
	return false;
}

/**
 * Start a Windows service.
 *
 * @param service_name Service internal name to start.
 *
 * @retval true If the service started.
 * @retval false If the service could not start. A log message is created.
 */
bool
Pandora_Wmi::startService (string service_name) {
	SC_HANDLE manager, service;
	bool      success;
	
	manager = OpenSCManager (NULL, NULL, SC_MANAGER_ALL_ACCESS);
	if (manager == NULL) {
		pandoraLog ("Could not access to service \"%s\" to start.",
			    service_name.c_str ());
		return false;
	}
	
	service = OpenService (manager, service_name.c_str (), GENERIC_EXECUTE);
	if (service == NULL) {
		pandoraLog ("Could not access to service \"%s\" to start.",
			    service_name.c_str ());
		CloseServiceHandle (manager);
		return false;
	}
	
	success = StartService (service, 0, NULL);
	
	CloseServiceHandle (service);
	CloseServiceHandle (manager);
	
	if (! success) {
		pandoraLog ("Could not start service \"%s\". (Error %d)",
			    service_name.c_str (), GetLastError ());
	}
	
	return success;
}

/**
 * Stop a Windows service.
 *
 * @param service_name Service internal name to stop.
 *
 * @retval true If the service started.
 * @retval false If the service could not stop. A log message is created.
 */
bool
Pandora_Wmi::stopService (string service_name) {
	SC_HANDLE manager, service;
	bool      success;
	SERVICE_STATUS ssStatus; 

	manager = OpenSCManager (NULL, NULL, SC_MANAGER_ALL_ACCESS);
	if (manager == NULL) {
		pandoraLog ("Could not access to service \"%s\" to stop.",
			    service_name.c_str ());
		return false;
	}
	
	service = OpenService (manager, service_name.c_str (), SERVICE_STOP);
	if (service == NULL) {
		pandoraLog ("Could not access to service \"%s\" to stop.",
			    service_name.c_str ());
		CloseServiceHandle (manager);
		return false;
	}
	
	success = ControlService (service, SERVICE_CONTROL_STOP, &ssStatus);
	
	CloseServiceHandle (service);
	CloseServiceHandle (manager);
	
	if (! success) {
		pandoraLog ("Could not stop service \"%s\". (Error %d)",
			    service_name.c_str (), GetLastError ());
	}
	
	return success;
}

/**
 * Runs a generic WQL query.
 * 
 * @param wmi_query WQL query.
 * @param column Column to retrieve from the query result.
 * @param rows List where the query result will be placed.
 */
void
Pandora_Wmi::runWMIQuery (string wmi_query, string column, list<string> &rows) {
    CDhInitialize init;
	CDispPtr      wmi_svc, quickfixes;
	char         *value = NULL;
    wstring column_w(column.length(), L' ');
    wstring wmi_query_w(wmi_query.length(), L' ');

    // Copy string to wstring.
    std::copy(column.begin(), column.end(), column_w.begin());
    std::copy(wmi_query.begin(), wmi_query.end(), wmi_query_w.begin());

	try {
		dhCheck (dhGetObject (getWmiStr (L"."), NULL, &wmi_svc));
		dhCheck (dhGetValue (L"%o", &quickfixes, wmi_svc,
				     L".ExecQuery(%S)",
				     wmi_query_w.c_str ()));
		FOR_EACH (quickfix, quickfixes, NULL) {
			dhGetValue (L"%s", &value, quickfix,
				    column_w.c_str ());
			if (value != NULL) {
		  	   rows.push_back (value);
		  	}
			dhFreeString (value);		
		} NEXT_THROW (quickfix);
	} catch (string errstr) {
		pandoraLog ("runWMIQuery error. %s", errstr.c_str ());
	}
}



/**
 * Gets all the sofware installed 
 * 
 * @param rows List where the query result will be placed.
 * @return An int with the number of Results found
 */
int
Pandora_Wmi::getSoftware (list<string> &rows) {
    CDhInitialize init;
	CDispPtr      wmi_svc =  NULL, software_list = NULL;
	char         *name  = NULL, *version = NULL;
    int          num_objects = 0;
	try {
		dhCheck (dhGetObject (getWmiStr (L"."), NULL, &wmi_svc));
		if (wmi_svc == NULL) {
           pandoraLog("Error getting wmi_svc\n");
        }
        else {
           pandoraLog("wmi_svc is ok\n"); 
        }
        dhCheck (dhGetValue (L"%o", &software_list, wmi_svc,
				     L".ExecQuery(%S)",
				     L"SELECT * FROM Win32_Product "));
        
		FOR_EACH (software_item, software_list, NULL) {
            num_objects++;
			dhGetValue (L"%s", &name, software_item,
				    L".Name");
   			if (name != NULL) {
		  	   rows.push_back (name);
            }
            dhFreeString (name);
			dhGetValue (L"%s", &version, software_item,
				    L".Version");            
			if (version != NULL) {
		  	   rows.push_back (version);
		  	}
			dhFreeString (version);		
		} NEXT_THROW (software_item);
	} catch (string errstr) {
		pandoraLog ("runWMIQuery error. %s", errstr.c_str ());
	}
	return num_objects;
}

/**
 * Gets the information about the CDRom
 * 
 * @param rows List where the query result will be placed.
 * @return An int with the number of Results found
 */
int
Pandora_Wmi::getCdRomInfo (list<string> &rows) {
    CDhInitialize init;
	CDispPtr      wmi_svc =  NULL, cd_info = NULL;
	char         *name  = NULL, *description = NULL, *drive = NULL;
	string        ret = "";
    int          num_objects = 0;
 	try {
		dhCheck (dhGetObject (getWmiStr (L"."), NULL, &wmi_svc));
		if (wmi_svc == NULL) {
           pandoraLog("Error getting wmi_svc\n");
        }
        else {
           pandoraLog("wmi_svc is ok\n"); 
        }
        dhCheck (dhGetValue (L"%o", &cd_info, wmi_svc,
				     L".ExecQuery(%S)",
				     L"SELECT  Name, Description, Drive FROM Win32_CDROMDrive "));
        
		FOR_EACH (cd_info_item, cd_info, NULL) {
            num_objects++;
			dhGetValue (L"%s", &name, cd_info_item,
				    L".Name");
   			if (name != NULL) {
               ret +=  name;		  	 
            }
            ret += inventory_field_separator;
            dhFreeString(name);
			dhGetValue (L"%s", &description, cd_info_item,
				    L".Description");
			if (description != NULL) {
               ret += " ";
		  	   ret += description;   		  	  
		  	}
            ret += inventory_field_separator;
            dhFreeString (description);		
			dhGetValue (L"%s", &drive, cd_info_item,
				    L".Drive");
   			if (drive != NULL) {
               ret += " (";
               ret += drive;
               ret += ")"; 
            }
            rows.push_back(ret);
            ret.clear();
            dhFreeString(drive);
		} NEXT_THROW (cd_info_item);
	} catch (string errstr) {
		pandoraLog ("runWMIQuery error. %s", errstr.c_str ());
	}
	return num_objects;
}


/**
 * Gets the information about the Video Card
 * 
 * @param rows List where the query result will be placed.
 * @return An int with the number of Results found
 */
int
Pandora_Wmi::getVideoInfo (list<string> &rows){
    CDhInitialize init;
	CDispPtr      wmi_svc =  NULL, video_info = NULL;
	char         *caption  = NULL, *adapter_RAM = NULL, *video_processor = NULL;
	string        ret = "";
    int          num_objects = 0;
 	try {
		dhCheck (dhGetObject (getWmiStr (L"."), NULL, &wmi_svc));
		if (wmi_svc == NULL) {
           pandoraLog("Error getting wmi_svc\n");
        }
        else {
           pandoraLog("wmi_svc is ok\n"); 
        }
        dhCheck (dhGetValue (L"%o", &video_info, wmi_svc,
				     L".ExecQuery(%S)",
				     L"SELECT Caption, AdapterRAM, VideoProcessor FROM Win32_VideoController "));
        
		FOR_EACH (video_info_item, video_info, NULL) {
            num_objects++;
			dhGetValue (L"%s", &caption, video_info_item,
				    L".Caption");
   			if (caption != NULL) {
               ret +=  caption;		  	 
            }
            ret += inventory_field_separator;
            dhFreeString(caption);
			dhGetValue (L"%s", &adapter_RAM, video_info_item,
				    L".AdapterRAM");
			if (adapter_RAM != NULL) {
               double ram_in_mb = atof(adapter_RAM) / 1048576; 
               ostringstream converter;
               converter << ram_in_mb;
               pandoraDebug("f:%f s:%s\n",ram_in_mb,adapter_RAM);            
		  	   ret += " " + converter.str() + " MBytes";
		  	}
            ret += inventory_field_separator;
            dhFreeString (adapter_RAM);		
			dhGetValue (L"%s", &video_processor, video_info_item,
				    L".VideoProcessor");
   			if (video_processor != NULL) {
               ret += video_processor; 
            }
            rows.push_back(ret);
            ret.clear();
            dhFreeString(video_processor);
		} NEXT_THROW (video_info_item);
	} catch (string errstr) {
		pandoraLog ("runWMIQuery error. %s", errstr.c_str ());
	}
	return num_objects;
}


/**
 * Gets the information about the Hard Drives
 * 
 * @param rows List where the query result will be placed.
 * @return An int with the number of Results found
 */
int
Pandora_Wmi::getHDsInfo (list<string> &rows) {
    CDhInitialize init;
	CDispPtr      wmi_svc =  NULL, hd_info = NULL;
	char         *model  = NULL, *system_name = NULL;
    //long          size = 0;
    char * size = NULL;
	string        ret = "";
    int          num_objects = 0;
 	try {
		dhCheck (dhGetObject (getWmiStr (L"."), NULL, &wmi_svc));
		if (wmi_svc == NULL) {
           pandoraLog("Error getting wmi_svc\n");
        }
        else {
           pandoraLog("wmi_svc is ok\n"); 
        }
        dhCheck (dhGetValue (L"%o", &hd_info, wmi_svc,
				     L".ExecQuery(%S)",
				     L"SELECT Model, Size, SystemName FROM Win32_DiskDrive "));
        
		FOR_EACH (hd_info_item, hd_info, NULL) {
            num_objects++;
			dhGetValue (L"%s", &model, hd_info_item,
				    L".Model");
   			if (model != NULL) {
               ret +=  model;		  	 
            }
            ret += inventory_field_separator;
            dhFreeString(model);
			dhGetValue (L"%s", &size, hd_info_item,
				    L".Size");
			if (size != NULL) {
               double fsize = atof(size) / 1073741824; 
               ostringstream converter;
               converter << fsize;
               pandoraDebug("f:%f s:%s\n",fsize,size);
		  	   ret += converter.str() + " GBs";   		  	  
		  	}
  		  	else {
                 pandoraDebug("Size unknown\n");
            }
            ret += inventory_field_separator;
            dhFreeString (size);		
			dhGetValue (L"%s", &system_name, hd_info_item,
				    L".SystemName");
   			if (system_name != NULL) {
               ret += " (";
               ret += system_name;
               ret += ")"; 
            }
            rows.push_back(ret);
            ret.clear();
            dhFreeString(system_name);
		} NEXT_THROW (hd_info_item);
	} catch (string errstr) {
		pandoraLog ("runWMIQuery error. %s", errstr.c_str ());
	}
	return num_objects;
}

/**
 * Gets the information about the CPUs
 * 
 * @param rows List where the query result will be placed.
 * @return An int with the number of Results found
 */
int
Pandora_Wmi::getCPUsInfo (list<string> &rows){
    CDhInitialize init;
	CDispPtr      wmi_svc =  NULL, cpu_info = NULL;
	char         *name  = NULL, *speed = NULL, *description = NULL;
	// Note speed is an uint32 but it works ok as char *
	string        ret = "", mhz =" MHz";
    int          num_objects = 0;
 	try {
		dhCheck (dhGetObject (getWmiStr (L"."), NULL, &wmi_svc));
		if (wmi_svc == NULL) {
           pandoraLog("Error getting wmi_svc\n");
        }
        else {
           pandoraLog("wmi_svc is ok\n"); 
        }
        dhCheck (dhGetValue (L"%o", &cpu_info, wmi_svc,
				     L".ExecQuery(%S)",
				     L"SELECT Name, MaxClockSpeed, Description FROM Win32_Processor "));
        
		FOR_EACH (cpu_info_item, cpu_info, NULL) {
            num_objects++;
			dhGetValue (L"%s", &name, cpu_info_item,
				    L".Name");
   			if (name != NULL) {
               ret +=  name;		  	 
            }
            ret += inventory_field_separator;
            dhFreeString(name);
			dhGetValue (L"%s", &speed, cpu_info_item,
				    L".MaxClockSpeed");
			if (speed != NULL) {
		  	   ret += speed + mhz;   		  	  
		  	}
            ret += inventory_field_separator;
            dhFreeString (speed);		
			dhGetValue (L"%s", &description, cpu_info_item,
				    L".Description");
   			if (description != NULL) {
               ret += description; 
            }
            rows.push_back(ret);
            ret.clear();
            dhFreeString(description);
		} NEXT_THROW (cpu_info_item);
	} catch (string errstr) {
		pandoraLog ("runWMIQuery error. %s", errstr.c_str ());
	}
	return num_objects;
}
/**
 * Gets a string with the IPs from an array of string of IPs 
 * 
 * This is a helper to extract the IPs from the result of the WMI query at 
 * getNICsInfo, that returns a **string with the IPs so this function gets this
 * parameter and retunrs a string with all  the IPs separated by ' , '
 * @param ip_array array of Strings of IPs as returned by the query on getNICsInfo
 * @return A string with the IPs separated by ' , ' 
 */
string 
getIPs(VARIANT *ip_array){
    UINT i; 
    VARIANT *pvArray; 
    string ret = "";
    if (V_VT(ip_array) == (VT_ARRAY | VT_VARIANT)) {
       if (FAILED(SafeArrayAccessData(V_ARRAY(ip_array), (void **) &pvArray))) {
          ret += "";
       }
       int num_ips  = V_ARRAY(ip_array)->rgsabound[0].cElements;
       pandoraDebug("Num IPs: %d\n",num_ips);
       for (i = 0;i < num_ips;i++) { 
	   	   if ((i > 0) && (i < num_ips - 1 ))
	   	   {
		   	  	 ret += " , ";
	   	   }
           if (V_VT(&pvArray[i]) == VT_BSTR) { 
             LPSTR szStringA;                     
             pandoraDebug("String[%u] (original): %S\n", i, V_BSTR(&pvArray[i])); 
             ret +=  Pandora_Strutils::strUnicodeToAnsi( V_BSTR(&pvArray[i]));             
           } 
       } 
       SafeArrayUnaccessData(V_ARRAY(ip_array));                                  
    }
	return ret;
}
/**
 * Gets the information about the Network Adapters
 * 
 * @param rows List where the query result will be placed. each row has the
 * 		  Caption, MACAddress and IPAddress separated by ; 
 * @return An int with the number of Results found
 */
int
Pandora_Wmi::getNICsInfo (list<string> &rows) {
    CDhInitialize init;
	CDispPtr      wmi_svc =  NULL, nic_info = NULL;
    VARIANT ip_addresses;
	char         *caption  = NULL, *mac_address = NULL;
	string        ret = "";
    int          num_objects = 0;
 	try {
		dhCheck (dhGetObject (getWmiStr (L"."), NULL, &wmi_svc));
		if (wmi_svc == NULL) {
           pandoraLog("Error getting wmi_svc\n");
        }
        else {
           pandoraLog("wmi_svc is ok\n"); 
        }
        dhCheck (dhGetValue (L"%o", &nic_info, wmi_svc,
				     L".ExecQuery(%S)",
				     L"SELECT Caption, MACAddress, IPAddress FROM Win32_NetworkAdapterConfiguration "));
        
		FOR_EACH (nic_info_item, nic_info, NULL) {
            num_objects++;
			dhGetValue (L"%s", &caption, nic_info_item,
				    L".Caption");
   			if (caption != NULL) {
               ret +=  caption;		  	 
            }
            dhFreeString(caption);
            ret += inventory_field_separator + " MAC: ";
			dhGetValue (L"%s", &mac_address, nic_info_item,
				    L".MACAddress");
			if (mac_address != NULL) {
		  	   ret += mac_address;   		  	  
		  	}            
            dhFreeString (mac_address);		
            ret += inventory_field_separator + " IP: ";
		    dhGetValue (L"%v", &ip_addresses, nic_info_item,
				    L".IPAddress");
		    if (&ip_addresses != NULL)
		    {
               ret += getIPs(&ip_addresses);
            }
            VariantClear(&ip_addresses);
            rows.push_back(ret);
            ret.clear();
		} NEXT_THROW (nic_info_item);
	} catch (string errstr) {
		pandoraLog ("runWMIQuery error. %s", errstr.c_str ());
	}
	return num_objects;
}

/**
 * Gets the information about the Patch Information
 * 
 * @param rows List where the query result will be placed. each row has the
 * 		  HotFixID, Description and FixComments separated by ;
 * @return An int with the number of Results found
 */
int
Pandora_Wmi::getPatchInfo (list<string> &rows) {
    CDhInitialize init;
	CDispPtr      wmi_svc =  NULL, patch_info = NULL;
	char         *hot_fix_id  = NULL, *description = NULL, *comments = NULL;
	string        ret = "";
    int          num_objects = 0;
 	try {
		dhCheck (dhGetObject (getWmiStr (L"."), NULL, &wmi_svc));
		if (wmi_svc == NULL) {
           pandoraLog("Error getting wmi_svc\n");
        }
        else {
           pandoraLog("wmi_svc is ok\n"); 
        }
        dhCheck (dhGetValue (L"%o", &patch_info, wmi_svc,
				     L".ExecQuery(%S)",
				     L"SELECT HotFixID, Description, FixComments FROM Win32_QuickFixEngineering "));
        
		FOR_EACH (patch_info_item, patch_info, NULL) {
            num_objects++;
			dhGetValue (L"%s", &hot_fix_id, patch_info_item,
				    L".HotFixID");
   			if (hot_fix_id != NULL) {
               ret +=  hot_fix_id;		  	 
            }
            ret += inventory_field_separator;
            dhFreeString(hot_fix_id);
			dhGetValue (L"%s", &description, patch_info_item,
				    L".Description");
			if (description != NULL) {
		  	   ret += description;   		  	  
		  	}
            ret += inventory_field_separator;
            dhFreeString (description);		
			dhGetValue (L"%s", &comments, patch_info_item,
				    L".FixComments");
   			if (comments != NULL) {
               ret += comments; 
            }
            dhFreeString(comments);
            rows.push_back(ret);
            ret.clear();
		} NEXT_THROW (patch_info_item);
	} catch (string errstr) {
		pandoraLog ("runWMIQuery error. %s", errstr.c_str ());
	}
	return num_objects;
}

/**
 * Gets the information about the RAM
 * 
 * @param rows List where the query result will be placed. each row has the
 * 		   Tag, Capacity and Name  separated by ;
 * @return An int with the number of Results found
 */
int
Pandora_Wmi::getRAMInfo (list<string> &rows) {
    CDhInitialize init;
	CDispPtr      wmi_svc =  NULL, ram_info = NULL;
	char         *tag  = NULL, *name = NULL, *capacity = NULL;
	string        ret = "";
    int          num_objects = 0;
 	try {
		dhCheck (dhGetObject (getWmiStr (L"."), NULL, &wmi_svc));
		if (wmi_svc == NULL) {
           pandoraLog("Error getting wmi_svc\n");
        }
        else {
           pandoraLog("wmi_svc is ok\n"); 
        }
        dhCheck (dhGetValue (L"%o", &ram_info, wmi_svc,
				     L".ExecQuery(%S)",
				     L"SELECT Tag, Capacity, Name FROM Win32_PhysicalMemory "));
        
		FOR_EACH (ram_info_item, ram_info, NULL) {
            num_objects++;
			dhGetValue (L"%s", &tag, ram_info_item,
				    L".Tag");
   			if (tag != NULL) {
               ret +=  tag;		  	 
            }
            ret += inventory_field_separator;
            dhFreeString(tag);
			dhGetValue (L"%s", &capacity, ram_info_item,
				    L".Capacity");
			if (capacity != NULL) {
               double fcapacity = atof(capacity) / 1048576; 
               ostringstream converter;
               converter << fcapacity;
               pandoraDebug("f:%f s:%s\n",fcapacity,capacity);
		  	   ret += converter.str() + " MBs";   		  	  
		  	}
  		  	else {
                 pandoraDebug("Capacity unknown\n");
            }
            ret += inventory_field_separator;
            dhFreeString (capacity);		
			dhGetValue (L"%s", &name, ram_info_item,
				    L".Name");
   			if (name != NULL) {             
               ret += name;
            }
            rows.push_back(ret);
            ret.clear();
            dhFreeString(name);
		} NEXT_THROW (ram_info_item);
	} catch (string errstr) {
		pandoraLog ("runWMIQuery error. %s", errstr.c_str ());
	}
	return num_objects;
}

/**
 * Get a list of running system services
 * 
 * @param rows Result list.
 * @return Result list length.
 */
int
Pandora_Wmi::getServices (list<string> &rows) {
    CDhInitialize init;
	CDispPtr      wmi_svc =  NULL, services = NULL;
	char         *name  = NULL, *path_name = NULL, *state = NULL;
	string        ret = "";
    int          num_objects = 0;

 	try {
		dhCheck (dhGetObject (getWmiStr (L"."), NULL, &wmi_svc));
        dhCheck (dhGetValue (L"%o", &services, wmi_svc,
				     L".ExecQuery(%S)",
				     L"SELECT Name, PathName, State FROM Win32_Service"));
        
		FOR_EACH (service, services, NULL) {
            num_objects++;
			dhGetValue (L"%s", &name, service, L".Name");
   			if (name != NULL) {
               ret += name;
            }
            ret += inventory_field_separator;
            dhFreeString(name);
			dhGetValue (L"%s", &path_name, service, L".PathName");
			if (path_name != NULL) {
		  	   ret += path_name;
		  	}
            ret += inventory_field_separator;
            dhFreeString (path_name);		
			dhGetValue (L"%s", &state, service, L".State");
   			if (state != NULL) {
               ret += state;
            }
            dhFreeString(state);
            rows.push_back(ret);
            ret.clear();
		} NEXT_THROW (service_item);
	} catch (string errstr) {
		pandoraLog ("runWMIQuery error. %s", errstr.c_str ());
	}
	return num_objects;
}
