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

#include <pdh.h>
#include "pandora_module.h"

#define BUFFER_SIZE 1024

typedef PDH_FUNCTION (*PdhOpenQueryT) (LPCSTR szDataSource,DWORD_PTR dwUserData,PDH_HQUERY *phQuery);
typedef PDH_FUNCTION (*PdhAddCounterT) (PDH_HQUERY hQuery,LPCSTR szFullCounterPath,DWORD_PTR dwUserData,PDH_HCOUNTER *phCounter);
typedef PDH_FUNCTION (*PdhCollectQueryDataT) (PDH_HQUERY hQuery);
typedef PDH_FUNCTION (*PdhGetRawCounterValueT) (PDH_HCOUNTER hCounter,LPDWORD lpdwType,PPDH_RAW_COUNTER pValue);
typedef PDH_FUNCTION (*PdhGetFormattedCounterValueT) (PDH_HCOUNTER hCounter,DWORD dwFormat,LPDWORD lpdwType,PPDH_FMT_COUNTERVALUE pValue);
typedef PDH_FUNCTION (*PdhCloseQueryT) (PDH_HQUERY hQuery);

namespace Pandora_Modules {
    
	/**
	 * This module retrieves information from performance counters.
	 */

	class Pandora_Module_Perfcounter : public Pandora_Module {
	private:
		string source;
		unsigned char cooked;

	public:
		Pandora_Module_Perfcounter (string name, string source, string cooked);
		virtual ~Pandora_Module_Perfcounter ();
		void run ();
	};
}

#endif
