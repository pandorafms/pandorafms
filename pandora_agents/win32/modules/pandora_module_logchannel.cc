/* Pandora logchannel module. This module checks for log events that match a given
   pattern using XML functions provided by wevtapi.

   Copyright (C) 2017 Artica ST.
   Written by Fermin Hernandez.

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

#include <string>
#include <sstream>
#include <iostream>
#include <time.h>

#include "pandora_module_logchannel.h"
#include "../windows/pandora_wmi.h"
#include "../pandora_windows_service.h"
#include "pandora_module_logchannel.h"
#include "pandora_strutils.h"

using namespace Pandora;
using namespace Pandora_Modules;
using namespace Pandora_Strutils;

// Pointers to Wevtapi.dll functions
static HINSTANCE WINEVENT = NULL;
static EvtQueryT EvtQueryF = NULL;
static EvtNextT EvtNextF = NULL;
static EvtSeekT EvtSeekF = NULL;
static EvtCreateRenderContextT EvtCreateRenderContextF = NULL;
static EvtRenderT EvtRenderF = NULL;
static EvtCloseT EvtCloseF = NULL;
static EvtFormatMessageT EvtFormatMessageF = NULL;
static EvtOpenPublisherMetadataT EvtOpenPublisherMetadataF = NULL;
static EvtCreateBookmarkT EvtCreateBookmarkF = NULL;
static EvtUpdateBookmarkT EvtUpdateBookmarkF = NULL;

/** 
 * Creates a Pandora_Module_Logchannel object.
 * 
 * @param name Module name.
 * @param service_name Service internal name to check.
 */
Pandora_Module_Logchannel::Pandora_Module_Logchannel (string name, string source, string type, string id, string pattern, string application)
	: Pandora_Module (name) {
    int i;
	vector<wstring> query;
	vector<wstring>::iterator query_it;
    string upper_type = type;

    // Convert the type string to uppercase
    for (i = 0; i < type.length(); i++) {
        upper_type[i] = toupper(type[i]);
    }

    // Set the type filter 
	int type_number = -1;
	if (upper_type.compare("CRITICAL") == 0) {
        type_number = WINEVENT_LEVEL_CRITICAL;
    } else if (upper_type.compare("ERROR") == 0) {
        type_number = WINEVENT_LEVEL_ERROR;
	} else if (upper_type.compare("WARNING") == 0) {
        type_number = WINEVENT_LEVEL_WARNING;
	} else if (upper_type.compare("INFO") == 0) {
        type_number = WINEVENT_LEVEL_INFO;
	} else if (upper_type.compare("VERBOSE") == 0) {
        type_number = WINEVENT_LEVEL_VERBOSE;
    }
	// Append type to log query
	if (type_number != -1) {
		wstringstream ss;
		ss << L"*[System[Level='" << type_number << L"']]";
		query.push_back(ss.str());
	}

	// Set the id
	int id_number = strtoul (id.c_str (), NULL, 0);
	if (id_number != 0) {
		wstringstream ss;
		ss << L"*[System[EventID='" << id_number << L"']]";
		query.push_back(ss.str());
	}

	// Set the application
	if (application != "") {
		wstringstream ss;
		ss << L"*[System/Provider[@Name='" << application.c_str() << L"']]";
		query.push_back(ss.str());
	}

	// Fill the filter
	if (query.size() == 0) {
		this->filter = L"*";
	} else {
		int i = 0;
		// Add filters with and
		wstring item_query;
		while (query.size() > 1) {
			item_query = query.back();
			query.pop_back();
			this->filter += item_query + L" and ";
		}
		// Append the last value without the and
		item_query = query.back();
		this->filter += item_query;
	}

	this->source = source;
	this->pattern = pattern;
	if (! pattern.empty ()) {
		// Compile the regular expression
		if (regcomp (&this->regexp, pattern.c_str (), REG_EXTENDED) != 0) {
			pandoraLog ("Invalid regular expression %s", pattern.c_str ());
		}
	}
	this->bookmark_xml = L"";
	this->setKind (module_logchannel_str);

    // Load Wevtapi.dll and some functions   	
	if (WINEVENT == NULL) {
        WINEVENT = LoadLibrary("Wevtapi.dll");
      	if (WINEVENT == NULL) {
			
			// Log to the bedug log, since this is not an error
            pandoraLog ("Library Wevtapi.dll not available");
            return;
        }

		EvtQueryF = (EvtQueryT) GetProcAddress (WINEVENT, "EvtQuery");
		if (EvtQueryF == NULL) {
			pandoraLog ("Error loading function EvtQuery from Wevtapi.dll");
			FreeLibrary (WINEVENT);
			WINEVENT = NULL;
			return;
		}
		EvtNextF = (EvtNextT) GetProcAddress (WINEVENT, "EvtNext");
		if (EvtNextF == NULL) {
			pandoraLog ("Error loading function EvtNext from Wevtapi.dll");
			FreeLibrary (WINEVENT);
			WINEVENT = NULL;
			return;
		}
		EvtSeekF = (EvtSeekT) GetProcAddress (WINEVENT, "EvtSeek");
		if (EvtSeekF == NULL) {
			pandoraLog ("Error loading function EvtSeek from Wevtapi.dll");
			FreeLibrary (WINEVENT);
			WINEVENT = NULL;
			return;
		}
		EvtCreateRenderContextF = (EvtCreateRenderContextT) GetProcAddress (WINEVENT, "EvtCreateRenderContext");
		if (EvtCreateRenderContextF == NULL) {
			pandoraLog ("Error loading function EvtCreateRenderContext from Wevtapi.dll");
			FreeLibrary (WINEVENT);
			WINEVENT = NULL;
			return;
		}
		EvtRenderF = (EvtRenderT) GetProcAddress (WINEVENT, "EvtRender");
		if (EvtRenderF == NULL) {
			pandoraLog ("Error loading function EvtRender from Wevtapi.dll");
			FreeLibrary (WINEVENT);
			WINEVENT = NULL;
			return;
		}
		EvtCloseF = (EvtCloseT) GetProcAddress (WINEVENT, "EvtClose");
		if (EvtCloseF == NULL) {
			pandoraLog ("Error loading function EvtClose from Wevtapi.dll");
			FreeLibrary (WINEVENT);
			WINEVENT = NULL;
			return;
		}
		EvtFormatMessageF = (EvtFormatMessageT) GetProcAddress (WINEVENT, "EvtFormatMessage");
		if (EvtFormatMessageF == NULL) {
			pandoraLog ("Error loading function EvtFormatMessage from Wevtapi.dll");
			FreeLibrary (WINEVENT);
			WINEVENT = NULL;
			return;
		}
		EvtOpenPublisherMetadataF = (EvtOpenPublisherMetadataT) GetProcAddress (WINEVENT, "EvtOpenPublisherMetadata");
		if (EvtOpenPublisherMetadataF == NULL) {
			pandoraLog ("Error loading function EvtOpenPublisherMetadata from Wevtapi.dll");
			FreeLibrary (WINEVENT);
			WINEVENT = NULL;
			return;
		}
		EvtCreateBookmarkF = (EvtCreateBookmarkT) GetProcAddress (WINEVENT, "EvtCreateBookmark");
		if (EvtCreateBookmarkF == NULL) {
			pandoraLog ("Error loading function EvtCreateBookmark from Wevtapi.dll");
			FreeLibrary (WINEVENT);
			WINEVENT = NULL;
			return;
		}
		EvtUpdateBookmarkF = (EvtUpdateBookmarkT) GetProcAddress (WINEVENT, "EvtUpdateBookmark");
		if (EvtUpdateBookmarkF == NULL) {
			pandoraLog ("Error loading function EvtUpdateBookmark from Wevtapi.dll");
			FreeLibrary (WINEVENT);
			WINEVENT = NULL;
			return;
		}
    }
}

void
Pandora_Module_Logchannel::run () {
	list<LogChannelList> event_list;
	list<LogChannelList>::iterator event;
	SYSTEMTIME system_time;
	
	// Run
	try {
		Pandora_Module::run ();
	} catch (Interval_Not_Fulfilled e) {
		return;
	}

	// Initialize log event query
	this->initializeLogChannel();
    
	// Read events on a list
	this->getLogEvents (event_list);

	// Return if no data stored on list
	if (event_list.size () < 1) return;

	for (event = event_list.begin (); event != event_list.end(); ++event) {
		// Store the data
		this->setOutput (event->message, &(event->timestamp));
	}
}

/** 
 * Fill the first bookmark of events.
 */
void
Pandora_Module_Logchannel::initializeLogChannel () {
	EVT_HANDLE hEvents[1];
	EVT_HANDLE hResults;
	EVT_HANDLE hBookmark;
	DWORD dwReturned = 0;

    // Check whether the first bookmark is set
    if (!this->bookmark_xml.empty()) return;

    // Open the event log with a query
	hResults = EvtQueryF (
		NULL,
		strAnsiToUnicode (this->source.c_str()).c_str(),
		this->filter.c_str(),
		EvtOpenChannelPath | EvtQueryForwardDirection
	);
    if (hResults == NULL) {
        pandoraDebug ("Could not open event log channel. Error: '%d'", GetLastError());
        return;
    }
	
	// Put the events on the last event
	if (!EvtSeekF(hResults, 0, NULL, 0, EvtSeekRelativeToLast)) {
		pandoraDebug("Cannot positionate the event at first. 'Error %d'.", GetLastError());
		EvtCloseF(hResults);
		return;
	}
	// Read next event to positionate the bookmark
	if (!EvtNextF(hResults, 1, hEvents, INFINITE, 0, &dwReturned)) {
		if (GetLastError() != ERROR_NO_MORE_ITEMS) {
			pandoraDebug ("EvtNext (initializeLogChannel) error: %d", GetLastError());
			EvtCloseF(hResults);
			return;
		}
	}
	// If no events read, do not use bookmark to read all events
	if (dwReturned == 0) {
		pandoraDebug("No events found positionating bookmark.");
		EvtCloseF(hResults);
		return;
	}
	// Create the bookmar
	pandoraDebug("Creating bookmark to channel %s", this->source.c_str());
	hBookmark = EvtCreateBookmarkF(NULL);
	if (hBookmark == NULL) {
		pandoraDebug("EvtCreateBookmark (initializeLogChannel) failed %d", GetLastError());
		EvtCloseF(hResults);
		EvtCloseF(hEvents[0]);
		return;
	}
	if (!EvtUpdateBookmarkF(hBookmark, hEvents[0])) {
		pandoraDebug("EvtUpdateBookmarkF (initializeLogChannel) failed %d", GetLastError());
		EvtCloseF(hResults);
		EvtCloseF(hEvents[0]);
		EvtCloseF(hBookmark);
		return;
	}
	 // Save the bookmark like an XML.
	this->updateBookmarkXML(hBookmark);

	// Clean tasks
	EvtCloseF(hResults);
	EvtCloseF(hBookmark);
	EvtCloseF(hEvents[0]);
}

/** 
 * Update the bookmark XML. Returns false if fails
 */
bool
Pandora_Module_Logchannel::updateBookmarkXML (EVT_HANDLE hBookmark) {
    LPWSTR pBookmarkXml = NULL;
	DWORD dwBufferSize = 0;
    DWORD dwBufferUsed = 0;
    DWORD dwPropertyCount = 0;
	DWORD status = 0;

    if (!EvtRenderF(NULL, hBookmark, EvtRenderBookmark, dwBufferSize, pBookmarkXml, &dwBufferUsed, &dwPropertyCount)){
        if (ERROR_INSUFFICIENT_BUFFER == (status = GetLastError())){
            dwBufferSize = dwBufferUsed;
            pBookmarkXml = (LPWSTR)malloc(dwBufferSize);
            if (pBookmarkXml){
                EvtRenderF(NULL, hBookmark, EvtRenderBookmark, dwBufferSize, pBookmarkXml, &dwBufferUsed, &dwPropertyCount);
            }
            else{
                pandoraDebug("Error loading the bookmark. Cannot load enough memory");
				this->cleanBookmark();
				free(pBookmarkXml);
				return false;
            }
        }
        if (ERROR_SUCCESS != (status = GetLastError())){
            pandoraDebug("EvtRender (updateBookmarkXML) failed with %d\n", GetLastError());
			this->cleanBookmark();
			free(pBookmarkXml);
            return false;
        }
    }
	this->bookmark_xml = pBookmarkXml;
	free(pBookmarkXml);
	return true;
}

/**
 * Clean the bookmark XML.
 */
void
Pandora_Module_Logchannel::cleanBookmark () {
	this->bookmark_xml = L"";
}

/** 
 * Reads available events from the event log.
 */
void
Pandora_Module_Logchannel::getLogEvents (list<LogChannelList> &event_list) {
	EVT_HANDLE hResults = NULL;
	EVT_HANDLE hBookmark = NULL;
	EVT_HANDLE hEvents[1];
	EVT_HANDLE hContext = NULL;
	PEVT_VARIANT pRenderedValues = NULL;
	EVT_HANDLE hProviderMetadata = NULL;
	LPWSTR pwsMessage = NULL;
	LPWSTR ppValues[] = {L"Event/System/Provider/@Name", L"Event/System/TimeCreated/@SystemTime"};
	DWORD count = sizeof(ppValues)/sizeof(LPWSTR);
	DWORD dwReturned = 0;
	DWORD dwBufferSize = 0;
	DWORD dwBufferUsed = 0;
	DWORD dwPropertyCount = 0;
	DWORD status = ERROR_SUCCESS;
	SYSTEMTIME eventTime;
	FILETIME lft, ft;
	bool update_bookmark = false;
	
	// An empty bookmark XML means that log cannot be open 
	if (this->bookmark_xml.empty()) return;

	// Open the event log with a query
	hResults = EvtQueryF (
		NULL,
		strAnsiToUnicode (this->source.c_str()).c_str(),
		this->filter.c_str(),
		EvtOpenChannelPath | EvtQueryForwardDirection
	);
    if (hResults == NULL) {
        pandoraDebug ("Could not open event log channel '%s'. Error: '%d'", this->source.c_str(), GetLastError());
		EvtCloseF(hResults);
		this->cleanBookmark();
        return;
    }
	
	// Seek on the bookmark
	hBookmark = EvtCreateBookmarkF(this->bookmark_xml.c_str());
	if (hBookmark == NULL) {
		pandoraDebug("Cannot read the string bookmark. Error: %d.", GetLastError());
		EvtCloseF(hResults);
		this->cleanBookmark();
		return;
	}
	if (!EvtSeekF(hResults, 1, hBookmark, 0, EvtSeekRelativeToBookmark)) {
		pandoraDebug("Cannot positionate the event at bookmark. Error %d.", GetLastError());
		EvtCloseF(hResults);
		EvtCloseF(hBookmark);
		this->cleanBookmark();
		return;
	}
	
	// Read events one by one
	hEvents[0] = NULL;
	while (EvtNextF(hResults, 1, hEvents, INFINITE, 0, &dwReturned)) {
		hContext = EvtCreateRenderContextF(count, (LPCWSTR*)ppValues, EvtRenderContextValues);
		if (NULL == hContext) {
			pandoraDebug ("EvtCreateRenderContext error: %d", GetLastError());
			EvtCloseF(hResults);
			EvtCloseF(hBookmark);
			EvtCloseF(hEvents[0]);
			this->cleanBookmark();
			return;
		}
		
		// Reinitialize the buffers
		dwBufferSize = 0;
		dwBufferUsed = 0;
		if (! EvtRenderF(hContext, hEvents[0], EvtRenderEventValues, dwBufferSize, pRenderedValues, &dwBufferUsed, &dwPropertyCount)) {
			if ((status = GetLastError()) == ERROR_INSUFFICIENT_BUFFER) {
				dwBufferSize = dwBufferUsed;
				pRenderedValues = (PEVT_VARIANT)malloc(dwBufferSize);
				if (pRenderedValues) {
					EvtRenderF(hContext, hEvents[0], EvtRenderEventValues, dwBufferSize, pRenderedValues, &dwBufferUsed, &dwPropertyCount);
				}
				else {
					pandoraDebug ("EvtRender error: %d", status);
					EvtCloseF(hResults);
					EvtCloseF(hBookmark);
					EvtCloseF(hEvents[0]);
					EvtCloseF(hContext);
					this->cleanBookmark();
					return;
				}
			}

			if ((status = GetLastError()) != ERROR_SUCCESS) {
				pandoraDebug ("EvtRender error getting buffer size: %d", status);
				EvtCloseF(hResults);
				EvtCloseF(hBookmark);
				EvtCloseF(hEvents[0]);
				EvtCloseF(hContext);
				this->cleanBookmark();
				return;
			}
		}

		// Get the SYSTEMTIME of log
		ULONGLONG ullTimeStamp = pRenderedValues[1].FileTimeVal;
		ft.dwHighDateTime = (DWORD)((ullTimeStamp >> 32) & 0xFFFFFFFF);
		ft.dwLowDateTime  = (DWORD)(ullTimeStamp & 0xFFFFFFFF);
		// Time format conversions
		if (!FileTimeToLocalFileTime(&ft, &lft)){
			pandoraDebug("UTC FILETIME to LOCAL FILETIME error: %d.", GetLastError());
		} else if (!FileTimeToSystemTime(&lft, &eventTime)){
			pandoraDebug("FILETIME to SYSTEMTIME error: %d.", GetLastError());
		}

		// Get the handle to the provider's metadata that contains the message strings
		hProviderMetadata = EvtOpenPublisherMetadataF(NULL, pRenderedValues[0].StringVal, NULL, 0, 0);
		if (hProviderMetadata == NULL) {
			pandoraDebug ("EvtOpenPublisherMetadata error: %d", GetLastError());
			EvtCloseF(hResults);
			EvtCloseF(hBookmark);
			EvtCloseF(hEvents[0]);
			EvtCloseF(hContext);
			free(pRenderedValues);
			this->cleanBookmark();
			return;
		}

		// Read the event message
		pwsMessage = GetMessageString(hProviderMetadata, hEvents[0], EvtFormatMessageEvent);
		if (pwsMessage == NULL) {
			EvtCloseF(hResults);
			EvtCloseF(hBookmark);
			EvtCloseF(hEvents[0]);
			EvtCloseF(hContext);
			free(pRenderedValues);
			EvtCloseF(hProviderMetadata);
			this->cleanBookmark();
			return;
		}

		// Check the regex and save the message if pass the regex
		if (this->pattern.empty () || regexec (&this->regexp, strUnicodeToAnsi(pwsMessage).c_str (), 0, NULL, 0) == 0){
			// Save the event message
			LogChannelList event_item;
			event_item.message = strUnicodeToAnsi(pwsMessage);
			event_item.timestamp= eventTime;
			event_list.push_back (event_item);
		}
		
		// Clean up some used vars
		EvtCloseF(hContext);
		free(pRenderedValues);
		EvtCloseF(hProviderMetadata);
		free(pwsMessage);

		// Update the bookmark
		if (!EvtUpdateBookmarkF(hBookmark, hEvents[0])) {
			pandoraDebug("EvtUpdateBookmarkF (getLogEvents) failed %d", GetLastError());
			EvtCloseF(hResults);
			EvtCloseF(hBookmark);
			EvtCloseF(hEvents[0]);
			this->cleanBookmark();
			return;
		}

		// Cleanup current event and read the next log
		EvtCloseF(hEvents[0]);
		hEvents[0] = NULL;

		// Information token to update bookmark
		update_bookmark = true;
	}
	status = GetLastError();
	if (status != ERROR_NO_MORE_ITEMS) {
		pandoraDebug ("EvtNext getLogEvents error: %d", GetLastError());
		EvtCloseF(hResults);
		EvtCloseF(hBookmark);
		this->cleanBookmark();
		return;
	}

	// Update bookmark if there is new events
	if (update_bookmark) this->updateBookmarkXML(hBookmark);

	// Clean handlers
	EvtCloseF(hResults);
	EvtCloseF(hBookmark);
}

// Gets the specified message string from the event. If the event does not
// contain the specified message, the function returns NULL.
// See http://msdn.microsoft.com/en-us/library/windows/desktop/dd996923(v=vs.85).aspx
LPWSTR
Pandora_Module_Logchannel::GetMessageString(EVT_HANDLE hMetadata, EVT_HANDLE hEvent, EVT_FORMAT_MESSAGE_FLAGS FormatId) {
	LPWSTR pBuffer = NULL;
	DWORD dwBufferSize = 0;
	DWORD dwBufferUsed = 0;
	DWORD status = 0;

	if (!EvtFormatMessageF(hMetadata, hEvent, 0, 0, NULL, FormatId, dwBufferSize, pBuffer, &dwBufferUsed)) {
		status = GetLastError();
		if (ERROR_INSUFFICIENT_BUFFER == status) {
			// An event can contain one or more keywords. The function returns keywords
			// as a list of keyword strings. To process the list, you need to know the
			// size of the buffer, so you know when you have read the last string, or you
			// can terminate the list of strings with a second null terminator character 
			// as this example does.
			if ((EvtFormatMessageKeyword == FormatId)) {
				pBuffer[dwBufferSize-1] = L'\0';
			}
			else {
				dwBufferSize = dwBufferUsed;
			}
			pBuffer = (LPWSTR)malloc(dwBufferSize * sizeof(WCHAR));

			if (pBuffer) {
				EvtFormatMessageF(hMetadata, hEvent, 0, 0, NULL, FormatId, dwBufferSize, pBuffer, &dwBufferUsed);

				// Add the second null terminator character.
				if ((EvtFormatMessageKeyword == FormatId)) {
					pBuffer[dwBufferUsed-1] = L'\0';
				}
			}
			else {
				return NULL;
			}
		}
		else {
			pandoraDebug ("EvtFormatMessage error: %d", status);
			return NULL;
		}
	}

	return pBuffer;
}
