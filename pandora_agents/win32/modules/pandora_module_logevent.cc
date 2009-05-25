/* Pandora logevent module. This module checks for log events that match a given
   pattern.

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

#include <time.h>

#include "pandora_module_logevent.h"
#include "../windows/pandora_wmi.h"
#include "../pandora_windows_service.h"

using namespace Pandora;
using namespace Pandora_Modules;

/** 
 * Creates a Pandora_Module_Logevent object.
 * 
 * @param name Module name.
 * @param service_name Service internal name to check.
 */
Pandora_Module_Logevent::Pandora_Module_Logevent (string name, string source, string type, string id, string pattern, string application)
	: Pandora_Module (name) {
    int i;
    string upper_type = type;

    // Convert the type string to uppercase
    for (i = 0; i < type.length(); i++) {
        upper_type[i] = toupper(type[i]);
    }

    // Set the type filter 
	if (upper_type.compare("ERROR") == 0) {
        this->type = EVENTLOG_ERROR_TYPE;
	} else if (upper_type.compare("WARNING") == 0) {
        this->type = EVENTLOG_WARNING_TYPE;
	} else if (upper_type.compare("INFORMATION") == 0) {
        this->type = EVENTLOG_INFORMATION_TYPE;
	} else if (upper_type.compare("AUDIT SUCCESS") == 0) {
        this->type = EVENTLOG_AUDIT_SUCCESS;
	} else if (upper_type.compare("AUDIT FAILURE") == 0) {
        this->type = EVENTLOG_AUDIT_FAILURE;
    } else {
        this->type = -1;
    }

	this->id = atoi (id.c_str ());
	this->source = source;
	this->pattern = pattern;
	this->application = application;
	this->log_event = NULL;
	this->setKind (module_logevent_str);
}

void
Pandora_Module_Logevent::run () {
	string value;
	list<string> event_list;
	list<string>::iterator event;
	SYSTEMTIME system_time;
	
	// Run
	try {
		Pandora_Module::run ();
	} catch (Interval_Not_Fulfilled e) {
		return;
	}

    // Open log event	
	this->openLogEvent();
    
    // Read events
    this->getLogEvents (event_list);
	
	// No data
	if (event_list.size () < 1) {
		this->setOutput ("");
		return;
	}
	
	for (event = event_list.begin (); event != event_list.end(); ++event) {

		// No timestamp? Should not happen
		if (event->size () < TIMESTAMP_LEN) {
			continue;
		}
		
		timestampToSystemtime (event->substr (0, TIMESTAMP_LEN), &system_time);

		// Store the data
		this->setOutput (event->substr (TIMESTAMP_LEN), &system_time);
	}
}

/** 
 * Opens a handle to the module event log.
 *
 * @return A handle to the event log.
 */
HANDLE
Pandora_Module_Logevent::openLogEvent () {

    // Check whether the event log is already open
    if (this->log_event != NULL) {
       return NULL;
    }

    // Open the event log
    this->log_event = OpenEventLog (NULL, this->source.c_str ());
    if (this->log_event == NULL) {
        pandoraLog ("Could not open event log file '%s'", this->source.c_str ());
        return NULL;
    }

    // Discard existing events
    this->discardLogEvents ();

    return this->log_event;
}

/** 
 * Closes the current event log.
 */
void
Pandora_Module_Logevent::closeLogEvent () {
    
    if (this->log_event == NULL) {
       return;
    }

    // Close the event log
    CloseEventLog (this->log_event);
    this->log_event = NULL;
}

/** 
 * Discards existing log events.
 */
void
Pandora_Module_Logevent::discardLogEvents () {
    int rc;
    BYTE bBuffer[BUFFER_SIZE];
    DWORD read, needed;
    DWORD oldest_event, newest_event, num_events;
    EVENTLOGRECORD *pevlr;

    if (this->log_event == NULL) {
        return;
    }

    // Get the offset of the newest event
    GetOldestEventLogRecord (this->log_event, &oldest_event);
    GetNumberOfEventLogRecords (this->log_event, &num_events);
    newest_event = oldest_event + num_events;

    // Initialize the event record buffer
    pevlr = (EVENTLOGRECORD *)&bBuffer;

    // Read the newest event, subsequent calls to ReadEventLog will read from here
    rc = ReadEventLog(this->log_event, EVENTLOG_FORWARDS_READ | EVENTLOG_SEEK_READ,
                      newest_event, pevlr, BUFFER_SIZE, &read, &needed);
    
    // Something went wrong
    if (rc != 0) {
        pandoraLog ("ReadEventLog error %d", GetLastError ());
    }
}

/** 
 * Reads available events from the event log.
 */
int
Pandora_Module_Logevent::getLogEvents (list<string> &event_list) {
    char description[BUFFER_SIZE], timestamp[TIMESTAMP_LEN + 1];
    struct tm *time_info = NULL;
    time_t epoch;
    string event;
    BYTE buffer[BUFFER_SIZE];
    DWORD read, needed;
    EVENTLOGRECORD *pevlr = NULL;
    LPCTSTR source_name;

    if (this->log_event == NULL) {
        return -1;
    }

    // Initialize the event record buffer
    pevlr = (EVENTLOGRECORD *) &buffer;

    // Read events
    while (ReadEventLog(this->log_event, EVENTLOG_FORWARDS_READ | EVENTLOG_SEQUENTIAL_READ,
                        0, pevlr, BUFFER_SIZE, &read, &needed)) {
        while (read > 0) {           

            // Retrieve the event description
            getEventDescription (pevlr, description);

            // Filter the event
            if (filterEvent (pevlr, description) == 0) {

                 // Generate a timestamp for the event
                 epoch = pevlr->TimeGenerated;
                 time_info = localtime (&epoch);
                 strftime (timestamp, TIMESTAMP_LEN + 1, "%Y-%m-%d %H:%M:%S", time_info);
                 
                 // Add the event to the list
                 event = timestamp;
                 event.append (description);
                 event_list.push_back (event);
            }

            // Move to the next event
            read -= pevlr->Length;
            pevlr = (EVENTLOGRECORD *) ((LPBYTE) pevlr + pevlr->Length);
        }

        pevlr = (EVENTLOGRECORD *) &buffer;
    }

	return 0;
}

/**
 * Converts a timestamp in the format "%Y-%m-%d %H:%M:%S"
 * to SYSTEMTIME format.
 *
 * @param wmi_date Timestamp.
 * @param system_time Output SYSTEMTIME variable.
 */
void
Pandora_Module_Logevent::timestampToSystemtime (string timestamp, SYSTEMTIME *system_time) {
    system_time->wYear = atoi (timestamp.substr (0, 4).c_str());
    system_time->wMonth = atoi (timestamp.substr (5, 2).c_str());
    system_time->wDay = atoi (timestamp.substr (8, 2).c_str());
    system_time->wHour = atoi (timestamp.substr (11, 2).c_str());
    system_time->wMinute = atoi (timestamp.substr (14, 2).c_str());
    system_time->wSecond = atoi (timestamp.substr (17, 2).c_str());
}

/**
 * Retrieves the description of the given event.
 *
 * @param event Event log record.
 * @param message Buffer to store the description (at least _MAX_PATH + 1).
 * @return 0 if the description could be retrieved, -1 otherwise.
 */
void
Pandora_Module_Logevent::getEventDescription (PEVENTLOGRECORD pevlr, char *message) {
    int i, j, len, offset;
    LPBYTE data = 0;
    HMODULE module = 0;
    TCHAR exe_file[_MAX_PATH + 1], exe_file_path[_MAX_PATH + 1];
    HKEY hk = (HKEY)0;
    TCHAR key_name[_MAX_PATH + 1];
    DWORD max_path, type;
    LPCSTR source_name;
    TCHAR **strings = NULL;

    message[0] = 0;

    // Read the source name
    source_name = (LPCTSTR) ((LPBYTE) pevlr + sizeof(EVENTLOGRECORD));

    // Read the key that points to the message file
    wsprintf (key_name, "SYSTEM\\CurrentControlSet\\Services\\EventLog\\%s\\%s", this->source.c_str (), source_name);
    if (RegOpenKeyEx (HKEY_LOCAL_MACHINE, key_name, 0L, KEY_READ, &hk) != NOERROR) {
       return;
    }
    max_path = _MAX_PATH + 1;
    if (RegQueryValueEx (hk, "EventMessageFile", 0, &type, (LPBYTE)exe_file, &max_path) != NOERROR) {
        RegCloseKey(hk);
        return;
    }
    if (ExpandEnvironmentStrings (exe_file, exe_file_path, _MAX_PATH + 1) == 0) {
        strncpy(exe_file_path, exe_file, _MAX_PATH + 1);
    }

    // Load the DLL
    module = LoadLibraryEx (exe_file_path, 0, DONT_RESOLVE_DLL_REFERENCES);
    if(module == NULL) {
        RegCloseKey(hk);
        return;
    }

    // Get the event strings
    strings = (TCHAR**)malloc (pevlr->NumStrings * sizeof(TCHAR *));
    if (strings == NULL) {
        RegCloseKey(hk);
        return;
    }

    offset = pevlr->StringOffset;
    for (i = 0; i < pevlr->NumStrings; i++) {
        len = strlen ((TCHAR *)pevlr + offset);
        strings[i] = (TCHAR *) malloc ((len + 1) * sizeof(TCHAR));
        if (strings[i] == NULL) {
           for (j = 0; j < i; j++) {
               if (strings[j] != NULL) {
                   free ((void *)strings[j]);
               }
           }
        }
		strcpy(strings[i], (TCHAR *)pevlr + offset);
		offset += len + 1;
    }
  
    // Get the description
    if (FormatMessage (FORMAT_MESSAGE_FROM_HMODULE | FORMAT_MESSAGE_ARGUMENT_ARRAY, module, pevlr->EventID, 0, (LPTSTR)message, BUFFER_SIZE, strings) == 0) {
        message[0] = 0;
    }

    // Clean up 
    for (i = 0; i < pevlr->NumStrings; i++) {
        if (strings[i] != NULL) {
            free ((void *)strings[i]);
        }
    }
    free ((void *)strings);
    FreeLibrary(module);
    RegCloseKey(hk);
}

/**
 * Filters the given event according to the module parameters.
 *
 * @param event Event log record.
 * @param event Event description.22
 * @return Returns 0 if the event matches the filters, -1 otherwise.
 */
int
Pandora_Module_Logevent::filterEvent (PEVENTLOGRECORD pevlr, string description) {
    LPCSTR source_name;

    // Event ID filter
    if (this->id > 0 && this->id != pevlr->EventID) {
        return -1;
    }

    // Type filter
    if (this->type != -1 && this->type != pevlr->EventType) {
        return -1;
    }

    // Application filter
    source_name = (LPCTSTR) ((LPBYTE) pevlr + sizeof(EVENTLOGRECORD));
    if (! this->application.empty () && this->application.compare (source_name) != 0) {
        return -1;
    }

    // Pattern filter
    if (! this->pattern.empty () && description.find(this->pattern) == string::npos) {
        return -1;
    }
    
    return 0;
}
