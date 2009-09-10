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
   Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
*/

#ifndef	__PANDORA_WMI_H__
#define	__PANDORA_WMI_H__

#define MAX_KEY_LENGTH 255

#include "../pandora.h"
#include "wmi/disphelper.h"
#include <list>

using namespace Pandora;
using namespace std;

/**
 * Operations with the Windows Management Instrumentation (WMI)
 */
namespace Pandora_Wmi {
	/**
	 * Exception super-class when doing a WMI operation.
	 */
	class Pandora_Wmi_Exception : public Pandora_Exception { };
	
	const string inventory_field_separator = "#$|$#";
	int           isProcessRunning      (string process_name);
	int           isServiceRunning      (string service_name);
	unsigned long getDiskFreeSpace      (string disk_id);
	unsigned long getDiskFreeSpacePercent      (string disk_id);
	int           getCpuUsagePercentage (int cpu_id);
	long          getFreememory         ();
	long          getFreememoryPercent  ();
	string        getOSName             ();
	string        getOSVersion          ();
	string        getOSBuild            ();
	string        getSystemName         ();	
	bool          runProgram            (string command, DWORD flags = 0);
	bool          startService          (string service_name);
	bool          stopService           (string service_name);
 	void          runWMIQuery       (string wmi_query,
					     string var,
					     list<string> &rows);
   	int           getSoftware       		(list<string> &rows);
   	int       	  getCdRomInfo           (list<string> &rows);
   	int       	  getVideoInfo           (list<string> &rows);
    int       	  getHDsInfo             (list<string> &rows); 
    int       	  getCPUsInfo            (list<string> &rows); 
    int       	  getNICsInfo            (list<string> &rows); 
    int	       	  getPatchInfo           (list<string> &rows); 
	int 		  getRAMInfo 			 (list<string> &rows); 
    int           getServices            (list<string> &rows);
    
};

#endif
