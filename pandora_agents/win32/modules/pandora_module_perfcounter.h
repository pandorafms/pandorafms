/* Pandora perfcounter module. This module retrieves information from
   performance counters.

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

#ifndef	__PANDORA_MODULE_PERFCOUNTER_H__
#define	__PANDORA_MODULE_PERFCOUNTER_H__

#include "pandora_module.h"

#define BUFFER_SIZE 1024

// Some definitions to use pdh.dll
typedef LONG PDH_STATUS;
typedef HANDLE HCOUNTER;
typedef HANDLE HQUERY;
typedef struct _PDH_RAW_COUNTER {
    DWORD       CStatus;
    FILETIME    TimeStamp;
    LONGLONG    FirstValue;
    LONGLONG    SecondValue;
    DWORD       MultiCount;
} PDH_RAW_COUNTER, *PPDH_RAW_COUNTER;

#define PDH_FUNCTION    PDH_STATUS __stdcall
typedef PDH_FUNCTION (*PdhOpenQueryT) (IN LPCSTR szDataSource, IN DWORD_PTR dwUserData, IN HQUERY *phQuery);
typedef PDH_FUNCTION (*PdhAddCounterT) (IN HQUERY hQuery, IN LPCSTR szFullCounterPath, IN DWORD_PTR dwUserData, IN HCOUNTER *phCounter);
typedef PDH_FUNCTION (*PdhCollectQueryDataT) (IN HQUERY hQuery);
typedef PDH_FUNCTION (*PdhGetRawCounterValueT) (IN HCOUNTER, IN LPDWORD lpdwType, IN PPDH_RAW_COUNTER pValue);
typedef PDH_FUNCTION (*PdhCloseQueryT) (IN HQUERY hQuery);

namespace Pandora_Modules {
    
	/**
	 * This module retrieves information from performance counters.
	 */

	class Pandora_Module_Perfcounter : public Pandora_Module {
	private:
		string source;

	public:
		Pandora_Module_Perfcounter (string name, string source);
		virtual ~Pandora_Module_Perfcounter ();
		void run ();
	};
}

#endif
