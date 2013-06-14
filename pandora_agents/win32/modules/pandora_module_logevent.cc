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

	this->id = strtoul (id.c_str (), NULL, 0);
	this->source = source;
	this->pattern = pattern;
	if (! pattern.empty ()) {
		// Compile the regular expression
		if (regcomp (&this->regexp, pattern.c_str (), REG_EXTENDED) != 0) {
			pandoraLog ("Invalid regular expression %s", pattern.c_str ());
		}
	}
	this->application = application;
	this->log_event = NULL;
	this->first_run = 1;
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
	this->getLogEvents (event_list, 0);

	// No data
	if (event_list.size () < 1) {
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
    list<string> event_list;

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

    // Discard existing events the first time the module is executed
    if (this->first_run == 1) {
        this->getLogEvents (event_list, 1);
	this->first_run = 0;
    }

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
 * Reads available events from the event log.
 */
int
Pandora_Module_Logevent::getLogEvents (list<string> &event_list, unsigned char discard) {
	char description[BUFFER_SIZE], timestamp[TIMESTAMP_LEN + 1];
	struct tm *time_info = NULL;
	time_t epoch;
	string event;
	BYTE *buffer = NULL, *new_buffer = NULL;
	DWORD to_read, read, needed;
	EVENTLOGRECORD *pevlr = NULL;
	LPCTSTR source_name;
	bool rc = false;
	DWORD last_error;

	if (this->log_event == NULL) {
	    return -1;
	}
	
	// Initialize the event record buffer
	to_read = BUFFER_SIZE;
	buffer = (BYTE *) malloc (sizeof (BYTE) * BUFFER_SIZE);
	if (buffer == NULL) {
	    	return -1;
	}
	pevlr = (EVENTLOGRECORD *) buffer;
	
	// Read events
	while (1) {
		rc = ReadEventLog (this->log_event, EVENTLOG_FORWARDS_READ | EVENTLOG_SEQUENTIAL_READ, 0, pevlr, to_read, &read, &needed);
		if (!rc) {

			// Get error details
			last_error = GetLastError();

			// Not enough space in the buffer
			if(last_error == ERROR_INSUFFICIENT_BUFFER) {

				// Initialize the new event record buffer
				to_read = needed;
				new_buffer = (BYTE *) realloc (buffer, sizeof (BYTE) * needed);
				if (new_buffer == NULL) {
					free ((void *) buffer);
					return -1;
				}
				
				buffer = new_buffer;
				pevlr = (EVENTLOGRECORD *) buffer;

				// Try to read the event again
				continue;
			// File corrupted or cleared
			} else if (last_error == ERROR_EVENTLOG_FILE_CORRUPT || last_error == ERROR_EVENTLOG_FILE_CHANGED) {
				closeLogEvent ();
				free ((void *) buffer);
				return -1;
			}
			// Unknown error
			else {
				free ((void *) buffer);
				return -1;
			}
		}
		
		// No more events
		if (read == 0) {
			free ((void *) buffer);
			return 0;
		}
		
		// Discard existing events
		if (discard == 1) {
			continue;
		}

		// Process read events
		while (read > 0) {           
	    
			// Retrieve the event description (LOAD_LIBRARY_AS_IMAGE_RESOURCE | LOAD_LIBRARY_AS_DATAFILE)
			getEventDescription (pevlr, description, 0x20 | 0x02);
			if (description[0] == '\0') {
				// Retrieve the event description (DONT_RESOLVE_DLL_REFERENCES)
				getEventDescription (pevlr, description, DONT_RESOLVE_DLL_REFERENCES);
				if (description[0] == '\0') {
					strcpy (description, "N/A");
				}
			}

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

		pevlr = (EVENTLOGRECORD *) buffer;
	}

	free ((void *) buffer);
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
Pandora_Module_Logevent::getEventDescription (PEVENTLOGRECORD pevlr, char *message, DWORD flags) {
    int i, j, len, offset;
    LPBYTE data = 0;
    HMODULE module = 0;
    TCHAR exe_file[_MAX_PATH + 1], exe_file_path[_MAX_PATH + 1];
    HKEY hk = (HKEY)0;
    TCHAR key_name[_MAX_PATH + 1];
    DWORD max_path, type;
    LPCSTR source_name;
    TCHAR **strings = NULL;
    char *dll_start = NULL, *dll_end = NULL, *exe_file_path_end = NULL;

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

    // Move to the first DLL
    dll_start = (char *) exe_file_path;
    dll_end = strchr (exe_file_path, ';');
    if (dll_end != NULL) {
	*dll_end = '\0';
    }
    exe_file_path_end = ((char *) exe_file_path) + _MAX_PATH * sizeof (TCHAR);

    while (1) {
        // Load the DLL
        module = LoadLibraryEx (dll_start, 0, flags);
        if(module == NULL) {
            pandoraDebug("LoadLibraryEx error %d. Exe file path %s.", GetLastError(), exe_file_path);
        } else {
            // Get the description
            FormatMessage (FORMAT_MESSAGE_FROM_HMODULE | FORMAT_MESSAGE_ARGUMENT_ARRAY, module, pevlr->EventID, 0, (LPTSTR)message, BUFFER_SIZE, strings);
	}

	// No more DLLs
	if (dll_end == NULL || dll_end >= exe_file_path_end) {
		break;
	}

    	// Move to the next DLL
	dll_start = dll_end + sizeof (TCHAR);
    	dll_end = strchr (dll_start, ';');
    	if (dll_end != NULL) {
			*dll_end = '\0';
		}
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
    if (this->id > 0 && this->id != (pevlr->EventID & 0x3FFFFFFF)) {
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
    if (! this->pattern.empty () && regexec (&this->regexp, description.c_str (), 0, NULL, 0) != 0) {
        return -1;
    }
    
    return 0;
}
