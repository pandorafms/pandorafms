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
	
	dhToggleExceptions (TRUE);
	
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
                        pandoraDebug ("name %s", name.c_str ());
                        
                        if (process_name == name) {
            			result++;
                        }
		} NEXT_THROW (quickfix);
	} catch (string errstr) {
		cerr << "Fatal error details:" << endl << errstr << endl;
	}
        
	return result;
}

int
Pandora_Wmi::isServiceRunning (string service_name) {
        CDhInitialize init;
	CDispPtr      wmi_svc, quickfixes;
	string        name, state;
	int           result = 0;
	
	dhToggleExceptions (TRUE);
	
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
                        pandoraDebug ("name %s", name.c_str ());
                        
                        if (service_name == name) {
				dhGetValue (L"%s", &fix.state, quickfix,
					    L".State");
				state = fix.state;
				pandoraDebug ("state %s", state.c_str ());
                        }
		} NEXT_THROW (quickfix);
	} catch (string errstr) {
		cerr << "Fatal error details:" << endl << errstr << endl;
	}
        
	return result;
}

string
Pandora_Wmi::getOSName () {
        CDhInitialize init;
	CDispPtr      wmi_svc, quickfixes;
	string        ret;
	
	dhToggleExceptions (TRUE);
	
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
		cerr << "Fatal error details:" << endl << errstr << endl;
	}
        
	return ret;
}

string
Pandora_Wmi::getOSVersion () {
        CDhInitialize init;
	CDispPtr      wmi_svc, quickfixes;
	string        ret;
	
	dhToggleExceptions (TRUE);
	
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
		cerr << "Fatal error details:" << endl << errstr << endl;
	}
        
	return ret;
}

string
Pandora_Wmi::getOSBuild () {
        CDhInitialize init;
	CDispPtr      wmi_svc, quickfixes;
	string        ret;
	
	dhToggleExceptions (TRUE);
	
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
		cerr << "Fatal error details:" << endl << errstr << endl;
	}
        
	return ret;
}

string
Pandora_Wmi::getSystemName () {
        CDhInitialize init;
	CDispPtr      wmi_svc, quickfixes;
	string        ret;
	
	dhToggleExceptions (TRUE);
	
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
		cerr << "Fatal error details:" << endl << errstr << endl;
	}
        
	return ret;
}
