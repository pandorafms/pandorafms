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

int
Pandora_Wmi::isProcessRunning (string process_name) {
        CDhInitialize init;
	CDispPtr      wmi_svc, quickfixes;
	string        name;
	int           result = 0;
	
        struct QFix {
		CDhStringA name, description, state;
	};
                
	try {	
		dhCheck (dhGetObject (getWmiStr (L"."), NULL, &wmi_svc));
		dhCheck (dhGetValue (L"%o", &quickfixes, wmi_svc,
                                     L".ExecQuery(%S)",
                                     L"SELECT * FROM Win32_Process"));
                
                FOR_EACH (quickfix, quickfixes, NULL) {
			QFix fix = { 0 };

			dhGetValue (L"%s", &fix.name, quickfix,
                                    L".Name");
                        
                        name = fix.name;
                        transform (name.begin (), name.end (), name.begin (), 
                                   (int (*) (int)) tolower);
			
                        if (process_name == name) {
            			result++;
                        }
		} NEXT_THROW (quickfix);
	} catch (string errstr) {
		pandoraLog ("isProcessRunning error. %s", errstr.c_str ());
	}
        
	return result;
}

int
Pandora_Wmi::isServiceRunning (string service_name) {
        CDhInitialize init;
	CDispPtr      wmi_svc, quickfixes;
	string        name, state;
	
        struct QFix {
		CDhStringA name, state;
	};
                
	try {	
		dhCheck (dhGetObject (getWmiStr (L"."), NULL, &wmi_svc));
		dhCheck (dhGetValue (L"%o", &quickfixes, wmi_svc,
                                     L".ExecQuery(%S)",
                                     L"SELECT * FROM Win32_Service"));
                
                FOR_EACH (quickfix, quickfixes, NULL) {
			QFix fix = { 0 };

			dhGetValue (L"%s", &fix.name, quickfix,
                                    L".Name");
                        
                        name = fix.name;
                        transform (name.begin (), name.end (), name.begin (), 
                                   (int (*) (int)) tolower);
                        
                        if (service_name == name) {
				dhGetValue (L"%s", &fix.state, quickfix,
					    L".State");
				state = fix.state;
                                
				if (state == "Running") {
                                        return 1;
                                } else {
                                        return 0;
                                }
                        }
		} NEXT_THROW (quickfix);
	} catch (string errstr) {
		pandoraLog ("isServiceRunning error. %s", errstr.c_str ());
	}
        
	return 0;
}

long
Pandora_Wmi::getDiskFreeSpace (string disk_id) {
	CDhInitialize init;
	CDispPtr      wmi_svc, quickfixes;
	string        id, space_str;
	int           space;
	
        struct QFix {
		CDhStringA id, free_space;
	};
        
	try {
                dhCheck (dhGetObject (getWmiStr (L"."), NULL, &wmi_svc));
		dhCheck (dhGetValue (L"%o", &quickfixes, wmi_svc,
                                     L".ExecQuery(%S)",
                                     L"SELECT DeviceID, FreeSpace FROM Win32_LogicalDisk "));

		FOR_EACH (quickfix, quickfixes, NULL) {
			QFix fix = { 0 };

			dhGetValue (L"%s", &fix.id, quickfix,
                                    L".DeviceID");

			id = fix.id;
			
			if (disk_id == id) {
				dhGetValue (L"%s", &fix.free_space, quickfix,
					    L".FreeSpace");
				
				space_str = fix.free_space;
				
				try {
					space = Pandora_Strutils::strtoint (space_str);
				} catch (Pandora_Exception e) {
					throw Pandora_Wmi_Error ();
				}
				
				return space / 1024 / 1024;
			}

		} NEXT_THROW (quickfix);
	} catch (string errstr) {
		pandoraLog ("getDiskFreeSpace error. %s", errstr.c_str ());
	}
        
	throw Pandora_Wmi_Error ();
}

int
Pandora_Wmi::getCpuUsagePercentage (int cpu_id) {
	CDhInitialize init;
	CDispPtr      wmi_svc, quickfixes;
	string        id, cpu_id_str;
	
	cpu_id_str = "CPU";
        cpu_id_str += Pandora_Strutils::inttostr (cpu_id);
	
        struct QFix {
		CDhStringA id;
		long       load_percentage;
	};
        
	try {
                dhCheck (dhGetObject (getWmiStr (L"."), NULL, &wmi_svc));
		dhCheck (dhGetValue (L"%o", &quickfixes, wmi_svc,
                                     L".ExecQuery(%S)",
                                     L"SELECT * FROM Win32_Processor "));

		FOR_EACH (quickfix, quickfixes, NULL) {
			QFix fix = { 0 };

			dhGetValue (L"%s", &fix.id, quickfix,
                                    L".DeviceID");

			id = fix.id;
			
			if (cpu_id_str == id) {
				dhGetValue (L"%d", &fix.load_percentage, quickfix,
					    L".LoadPercentage");
                                
				return fix.load_percentage;
			}

		} NEXT_THROW (quickfix);
	} catch (string errstr) {
		pandoraLog ("getCpuUsagePercentage error. %s", errstr.c_str ());
	}
        
	throw Pandora_Wmi_Error ();
}


long
Pandora_Wmi::getFreememory () {
	CDhInitialize init;
	CDispPtr      wmi_svc, quickfixes;

        struct QFix {
		long free_memory;
	};
        
	try {
                dhCheck (dhGetObject (getWmiStr (L"."), NULL, &wmi_svc));
		dhCheck (dhGetValue (L"%o", &quickfixes, wmi_svc,
                                     L".ExecQuery(%S)",
                                     L"SELECT * FROM Win32_PerfRawData_PerfOS_Memory "));

		FOR_EACH (quickfix, quickfixes, NULL) {
			QFix fix = { 0 };
			
			dhGetValue (L"%d", &fix.free_memory, quickfix,
                                    L".AvailableMBytes");
			
			return fix.free_memory;
		} NEXT_THROW (quickfix);
	} catch (string errstr) {
		pandoraLog ("getFreememory error. %s", errstr.c_str ());
	}
        
	throw Pandora_Wmi_Error ();	
}

string
Pandora_Wmi::getOSName () {
        CDhInitialize init;
	CDispPtr      wmi_svc, quickfixes;
	string        ret;
	
        struct QFix {
		CDhStringA name, state, description;
	};
        
	try {
                dhCheck (dhGetObject (getWmiStr (L"."), NULL, &wmi_svc));
		dhCheck (dhGetValue (L"%o", &quickfixes, wmi_svc,
                                     L".ExecQuery(%S)",
                                     L"SELECT * FROM Win32_OperatingSystem "));

		FOR_EACH (quickfix, quickfixes, NULL) {
			QFix fix = { 0 };

			dhGetValue (L"%s", &fix.name, quickfix,
                                    L".Caption");
                        
                        ret = fix.name;

		} NEXT_THROW (quickfix);
	} catch (string errstr) {
		pandoraLog ("getOSName error. %s", errstr.c_str ());
	}
        
	return ret;
}

string
Pandora_Wmi::getOSVersion () {
        CDhInitialize init;
	CDispPtr      wmi_svc, quickfixes;
	string        ret;
	
        struct QFix {
		CDhStringA name, state, description;
	};
        
	try {
                dhCheck (dhGetObject (getWmiStr (L"."), NULL, &wmi_svc));
		dhCheck (dhGetValue (L"%o", &quickfixes, wmi_svc,
                                     L".ExecQuery(%S)",
                                     L"SELECT * FROM Win32_OperatingSystem "));

		FOR_EACH (quickfix, quickfixes, NULL) {
			QFix fix = { 0 };

			dhGetValue (L"%s", &fix.name, quickfix,
                                    L".CSDVersion");
                        
                        ret = fix.name;

		} NEXT_THROW (quickfix);
	} catch (string errstr) {
		pandoraLog ("getOSVersion error. %s", errstr.c_str ());
	}
        
	return ret;
}

string
Pandora_Wmi::getOSBuild () {
        CDhInitialize init;
	CDispPtr      wmi_svc, quickfixes;
	string        ret;
		
        struct QFix {
		CDhStringA name, state, description;
	};
        
	try {
                dhCheck (dhGetObject (getWmiStr (L"."), NULL, &wmi_svc));
		dhCheck (dhGetValue (L"%o", &quickfixes, wmi_svc,
                                     L".ExecQuery(%S)",
                                     L"SELECT * FROM Win32_OperatingSystem "));

		FOR_EACH (quickfix, quickfixes, NULL) {
			QFix fix = { 0 };

			dhGetValue (L"%s", &fix.name, quickfix,
                                    L".Version");
                        
                        ret = fix.name;

		} NEXT_THROW (quickfix);
	} catch (string errstr) {
		pandoraLog ("getOSBuild error. %s", errstr.c_str ());
	}
        
	return ret;
}

string
Pandora_Wmi::getSystemName () {
        CDhInitialize init;
	CDispPtr      wmi_svc, quickfixes;
	string        ret;
	
        struct QFix {
		CDhStringA name, state, description;
	};
        
	try {
                dhCheck (dhGetObject (getWmiStr (L"."), NULL, &wmi_svc));
		dhCheck (dhGetValue (L"%o", &quickfixes, wmi_svc,
                                     L".ExecQuery(%S)",
                                     L"SELECT * FROM Win32_OperatingSystem "));

		FOR_EACH (quickfix, quickfixes, NULL) {
			QFix fix = { 0 };

			dhGetValue (L"%s", &fix.name, quickfix,
                                    L".CSName");
                        
                        ret = fix.name;

		} NEXT_THROW (quickfix);
	} catch (string errstr) {
		pandoraLog ("getSystemName error. %s", errstr.c_str ());
	}
        
	return ret;
}
