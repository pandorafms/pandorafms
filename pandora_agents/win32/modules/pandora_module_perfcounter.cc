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

#include <iostream>
#include <sstream>
#include <string>

#include "pandora_module_perfcounter.h"

using namespace Pandora;
using namespace Pandora_Modules;

// Pointers to pdh.dll functions
static HINSTANCE PDH = NULL;
static PdhOpenQueryT PdhOpenQueryF = NULL;
static PdhAddCounterT PdhAddCounterF = NULL;
static PdhCollectQueryDataT PdhCollectQueryDataF = NULL;
static PdhGetRawCounterValueT PdhGetRawCounterValueF = NULL;
static PdhGetFormattedCounterValueT PdhGetFormattedCounterValueF = NULL;
static PdhCloseQueryT PdhCloseQueryF = NULL;

/** 
 * Creates a Pandora_Module_Perfcounter object.
 * 
 * @param name Module name.
 * @param service_name Service internal name to check.
 */
Pandora_Module_Perfcounter::Pandora_Module_Perfcounter (string name, string source, string cooked)
	: Pandora_Module (name) {

	this->source = source;
	this->setKind (module_perfcounter_str);

    // Load pdh.dll and some functions   	
	if (PDH == NULL) {
        PDH = LoadLibrary("pdh.dll");
      	if (PDH == NULL) {
            pandoraLog ("Error loading library pdh.dll");
            return;
        }

        PdhOpenQueryF = (PdhOpenQueryT) GetProcAddress (PDH, "PdhOpenQueryA");
        if (PdhOpenQueryF == NULL) {
            pandoraLog ("Error loading function PdhOpenQueryA");
            FreeLibrary (PDH);
            PDH = NULL;
            return;
        }

        PdhAddCounterF = (PdhAddCounterT) GetProcAddress (PDH, "PdhAddCounterA");
        if (PdhAddCounterF == NULL) {
            pandoraLog ("Error loading function PdhAddCounterA");
            FreeLibrary (PDH);
            PDH = NULL;
            return;
        }

        PdhCollectQueryDataF = (PdhCollectQueryDataT) GetProcAddress (PDH, "PdhCollectQueryData");
        if (PdhCollectQueryDataF == NULL) {
            pandoraLog ("Error loading function PdhCollectQueryData");
            FreeLibrary (PDH);
            PDH = NULL;
            return;
        }

        PdhGetRawCounterValueF = (PdhGetRawCounterValueT) GetProcAddress (PDH, "PdhGetRawCounterValue"); 
        if (PdhGetRawCounterValueF == NULL) {
            pandoraLog ("Error loading function PdhGetRawCounterValue");
            FreeLibrary (PDH);
            PDH = NULL;
            return;
        }
        
        PdhGetFormattedCounterValueF = (PdhGetFormattedCounterValueT) GetProcAddress (PDH, "PdhGetFormattedCounterValue"); 
        if (PdhGetFormattedCounterValueF == NULL) {
            pandoraLog ("Error loading function PdhGetFormattedCounterValue");
            FreeLibrary (PDH);
            PDH = NULL;
            return;
        }
        
        PdhCloseQueryF = (PdhCloseQueryT) GetProcAddress (PDH, "PdhCloseQuery"); 
        if (PdhCloseQueryF == NULL) {
            pandoraLog ("Error loading function PdhCloseQuery");
            FreeLibrary (PDH);
            PDH = NULL;
            return;
        }
    }
    
    if (cooked[0] == '1') {
		this->cooked = 1;
	} else {
		this->cooked = 0;
	}
}

/** 
 * Pandora_Module_Perfcounter destructor.
 */
Pandora_Module_Perfcounter::~Pandora_Module_Perfcounter () {
    //FreeLibrary (PDH);
}

void
Pandora_Module_Perfcounter::run () {
    WCHAR source [MAX_PATH];
    HQUERY query;
    PDH_STATUS status;
    HCOUNTER counter;
    PDH_RAW_COUNTER raw_value;
    PDH_FMT_COUNTERVALUE fmt_value;
    ostringstream string_value;

	// Run
	try {
		Pandora_Module::run ();
	} catch (Interval_Not_Fulfilled e) {
		return;
	}

    if(PDH == NULL) {
	    pandoraLog ("pdh.dll not found.");
	    return;
	}

    // Open a query object
    status = PdhOpenQueryF (NULL, 0, &query);
    if (status != ERROR_SUCCESS) {
        pandoraLog ("PdhOpenQuery failed with error %lX", status);
        return;
    }

    // Add the counter that will provide the data
    status = PdhAddCounterF (query, this->source.c_str (), 0, &counter);
    if (status != ERROR_SUCCESS) {
        pandoraLog ("PdhAddCounter failed with error %lX", status);
		PdhCloseQueryF (query);
        return;
    }

    // Collect the data
    status = PdhCollectQueryDataF (query);
    if (status != ERROR_SUCCESS) {
        // No data
		PdhCloseQueryF (query);
        return;
    }

    // Retrieve the counter value
	if (this->cooked == 1) {
			
	    // Some counters require to samples
	    Sleep (100);
	    status = PdhCollectQueryDataF (query);
	    if (status != ERROR_SUCCESS) {
	        // No data
			PdhCloseQueryF (query);
	        return;
	    }

		status = PdhGetFormattedCounterValueF(counter, PDH_FMT_LONG, NULL, &fmt_value);
	} else {
		status = PdhGetRawCounterValueF(counter, NULL, &raw_value);
	}

    // Close the query object
    PdhCloseQueryF (query);

	if (cooked == 1) {
		string_value << fmt_value.longValue;
	} else {
		string_value << raw_value.FirstValue;
	}
		
    this->setOutput (string_value.str ());
}
