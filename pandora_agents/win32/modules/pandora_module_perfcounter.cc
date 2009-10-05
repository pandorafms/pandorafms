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
static PdhOpenQueryT PdhOpenQuery = NULL;
static PdhAddCounterT PdhAddCounter = NULL;
static PdhCollectQueryDataT PdhCollectQueryData = NULL;
static PdhGetRawCounterValueT PdhGetRawCounterValue = NULL;
static PdhCloseQueryT PdhCloseQuery = NULL;

/** 
 * Creates a Pandora_Module_Perfcounter object.
 * 
 * @param name Module name.
 * @param service_name Service internal name to check.
 */
Pandora_Module_Perfcounter::Pandora_Module_Perfcounter (string name, string source)
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

        PdhOpenQuery = (PdhOpenQueryT) GetProcAddress (PDH, "PdhOpenQueryA");
        if (PdhOpenQuery == NULL) {
            pandoraLog ("Error loading function PdhOpenQueryA");
            FreeLibrary (PDH);
            PDH = NULL;
            return;
        }

        PdhAddCounter = (PdhAddCounterT) GetProcAddress (PDH, "PdhAddCounterA");
        if (PdhAddCounter == NULL) {
            pandoraLog ("Error loading function PdhAddCounterA");
            FreeLibrary (PDH);
            PDH = NULL;
            return;
        }

        PdhCollectQueryData = (PdhCollectQueryDataT) GetProcAddress (PDH, "PdhCollectQueryData");
        if (PdhCollectQueryData == NULL) {
            pandoraLog ("Error loading function PdhCollectQueryData");
            FreeLibrary (PDH);
            PDH = NULL;
            return;
        }

        PdhGetRawCounterValue = (PdhGetRawCounterValueT) GetProcAddress (PDH, "PdhGetRawCounterValue"); 
        if (PdhGetRawCounterValue == NULL) {
            pandoraLog ("Error loading function PdhGetRawCounterValue");
            FreeLibrary (PDH);
            PDH = NULL;
            return;
        }

        PdhCloseQuery = (PdhCloseQueryT) GetProcAddress (PDH, "PdhCloseQuery"); 
        if (PdhCloseQuery == NULL) {
            pandoraLog ("Error loading function PdhCloseQuery");
            FreeLibrary (PDH);
            PDH = NULL;
            return;
        }
    }
}

/** 
 * Pandora_Module_Perfcounter destructor.
 */
Pandora_Module_Perfcounter::~Pandora_Module_Perfcounter () {
    FreeLibrary (PDH);
}

void
Pandora_Module_Perfcounter::run () {
    WCHAR source [MAX_PATH];
    HQUERY query;
    PDH_STATUS status;
    HCOUNTER counter;
    PDH_RAW_COUNTER value;
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
    status = PdhOpenQuery (NULL, 0, &query);
    if (status != ERROR_SUCCESS) {
        pandoraLog ("PdhOpenQuery failed with error %d", status);
        return;
    }

    // Add the counter that will provide the data
    status = PdhAddCounter (query, this->source.c_str (), 0, &counter);
    if (status != ERROR_SUCCESS) {
        pandoraLog ("PdhAddCounter failed with error %d", status);
		PdhCloseQuery (query);
        return;
    }

    // Collect the data
    status = PdhCollectQueryData (query);
    if (status != ERROR_SUCCESS) {
        // No data
		PdhCloseQuery (query);
        return;
    }

    // Retrieve the counter value
    status = PdhGetRawCounterValue(counter, NULL, &value);

    // Close the query object
    PdhCloseQuery (query);

    string_value << value.FirstValue;
    this->setOutput (string_value.str ());
}
