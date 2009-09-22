/* Library to create a Windows service for Win32.

   Copyright (C) 2006 Artica ST.
   Written by Esteban Sanchez.
   Based on Snort code.
  
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

#include "windows_service.h"
#include <stdio.h>
#include <iostream>

using namespace std;

#define READ_TIMEOUT  500

static Windows_Service        *current_service = NULL;

static SERVICE_STATUS_HANDLE   service_status_handle; 

static void  WINAPI windows_service_start        (DWORD argc, LPTSTR *argv);
static VOID  WINAPI windows_service_ctrl_handler (DWORD dwOpcode);
static VOID  svc_format_message                  (LPSTR szString, int iCount);
static void  SetWindowsServiceStatus             (DWORD dwCurrentState,
						  DWORD dwWin32ExitCode,
						  DWORD dwCheckPoint,
						  DWORD dwWaitHint);
static void  ErrorStopService                    (LPTSTR lpszAPI);

/**
 * Set the values of the service to run.
 *
 * All the attributes are set to NULL.
 */
Windows_Service::Windows_Service () {
	service_name          = NULL;
	service_display_name  = NULL;
	service_description   = NULL;
}
/** 
 * Set the values of the service to run.
 *
 * @param svc_name Internal service name.
 * @param svc_display_name Service name to display in the Windows
 *        service administration utility.
 * @param svc_description Long description of the service.
 */
Windows_Service::Windows_Service (const char * svc_name,
				  const char * svc_display_name,
				  const char * svc_description) {
	sleep_time            = 0;
	run_function          = NULL;
	init_function         = NULL;
	stop_event            = CreateEvent (NULL, TRUE, FALSE, NULL);
	service_name          = (char *) svc_name;
	service_display_name  = (char *) svc_display_name;
	service_description   = (char *) svc_description;
	current_service       = this;
}

/** 
 * Destroy the service
 */
Windows_Service::~Windows_Service () {
}

/**
 * Set the function to run on service execution.
 *
 * @param f Pointer to execution function.
 */
void
Windows_Service::setRunFunction (void (Windows_Service::*f) ()) {
	run_function = f;
	current_service->run_function = f;
}

/**
 * Set the function to initialize the service.
 *
 * This functions is executed before the run function.
 *
 * @param f Pointer to init function.
 */
void
Windows_Service::setInitFunction (void (Windows_Service::*f) ()) {
	init_function = f;
	current_service->init_function = f;
}

/** 
 * Exec the run function.
 * 
 * If the sleep_time is set to a greater value than 0, then the
 * function will execute infinitely.
 * Notice: This function does not have to be called from the main
 * function
 */
void
Windows_Service::execRunFunction () {
	if (run_function != NULL) {
		(this->*run_function) ();
		if (sleep_time > 0) {
			while (WaitForSingleObject (stop_event, sleep_time) != WAIT_OBJECT_0) {
				(this->*run_function) ();
			}
		}
	}
}

/** 
 * Exec the init function.
 */
void
Windows_Service::execInitFunction () {
	if (init_function != NULL) {
		(this->*init_function) ();
	}
}

/** 
 * Get the internal service name.
 * 
 * @return The internal service name.
 */
LPSTR
Windows_Service::getServiceName () {
	return service_name;
}

/** 
 * Set the time between executions.
 *
 * If it's set to 0 (default value), the service will execute
 * the run function once. Else it's executed infinitely every
 * s seconds
 *
 * @param s Seconds between executions.
 */
void
Windows_Service::setSleepTime (unsigned int s) {
	sleep_time = s;
	current_service->sleep_time = sleep_time;
}

/** 
 * Install the service in the Windows services system.
 * 
 * @param application_binary_path Path to binary file.
 */
void
Windows_Service::install (LPCTSTR application_binary_path) { 
	SC_HANDLE           sc_manager;
	SERVICE_DESCRIPTION sd_buf;
	
	cout << " [SERVICE] Attempting to install the service.\n";
	cout << " [SERVICE] The full path to the binary is: " << application_binary_path << endl;
	
	/* Add program to the Services database */
	sc_manager = OpenSCManager (NULL,                 /* local machine                        */
				    NULL,                 /* defaults to SERVICES_ACTIVE_DATABASE */
				    SC_MANAGER_ALL_ACCESS /* full access rights                   */);
	
	if (sc_manager == NULL) {
		DWORD   err = GetLastError();
		LPCTSTR basic_message = "Unable to open a connection to the Services database."; 
		TCHAR   msg[1000];
		
		svc_format_message (msg, sizeof (msg));
		
		switch (err) {
		case ERROR_ACCESS_DENIED: 
			cout << " [SERVICE] " << basic_message << ". Access is denied. " << msg << endl;
			break;

		case ERROR_DATABASE_DOES_NOT_EXIST: 
			cout << " [SERVICE] " << basic_message << " Services database does not exist. " << msg << endl;
			break;
		
		case ERROR_INVALID_PARAMETER: 
			cout << " [SERVICE] Invalid parameter. " << msg << endl;
			break;
		
		default: 
			cout << " [SERVICE] " << basic_message;
			cout << " Unrecognized error (" << err << ") " << msg << endl;
			break;
		}
	}
	
	/* Crerate the service */
	sc_service = CreateService (sc_manager,                  /* SCManager database        */
				    service_name,                /* name of service           */
				    service_display_name,        /* service name to display   */
				    SERVICE_ALL_ACCESS,          /* desired access            */
				    SERVICE_WIN32_OWN_PROCESS,   /* service type, interactive */
				    SERVICE_AUTO_START,          /* start type                */
				    SERVICE_ERROR_NORMAL,        /* error control type        */
				    application_binary_path,     /* service's binary          */
				    NULL,                        /* no load ordering group    */
				    NULL,                        /* no tag identifier         */
				    NULL,                        /* no dependencies           */
				    NULL,                        /* LocalSystem account       */
				    NULL                         /* no password               */ );
	
	if (sc_service == NULL) {
		DWORD   err = GetLastError();
		LPCTSTR basic_message = "Error while adding the service to the Services database."; 
		TCHAR   msg[1000];
		
		svc_format_message (msg, sizeof (msg));
		
		switch (err) {
		case ERROR_ACCESS_DENIED: 
			cout << " [SERVICE] " << basic_message << " Access is denied. " << msg << endl;
			break;
		    
		case ERROR_CIRCULAR_DEPENDENCY:
			cout << " [SERVICE] " << basic_message << " Circular dependency. " << msg << endl;
			break;
		
		case ERROR_DUP_NAME: 
			cout << " [SERVICE] " << basic_message << " The display name (\"" << service_display_name;
			cout << "\") is already in use. " << msg << endl;
			break;
		
		case ERROR_INVALID_HANDLE: 
			cout << " [SERVICE] " << basic_message << " Invalid handle. " << msg << endl;
			break;
		
		case ERROR_INVALID_NAME: 
			cout << " [SERVICE] " << basic_message << " Invalid service name. " << msg << endl;
			break;
		
		case ERROR_INVALID_PARAMETER: 
			cout << " [SERVICE] " << basic_message << " Invalid parameter. " << msg << endl;
			break;
		
		case ERROR_INVALID_SERVICE_ACCOUNT: 
			cout << " [SERVICE] " << basic_message << " Invalid service account. " << msg << endl;
			break;
		
		case ERROR_SERVICE_EXISTS: 
			cout << " [SERVICE] " << basic_message << " Service already exists. " << msg << endl;
			break;
		
		default: 
			cout << " [SERVICE] " << basic_message;
			cout << " Unrecognized error (" << err << ") " << msg << endl;
			break;
		}
	}
	
	/* Apparently, the call to ChangeServiceConfig2() only works on Windows >= 2000 */
	sd_buf.lpDescription = service_description;
	
	if (!ChangeServiceConfig2 (sc_service,                 /* handle to service      */
				   SERVICE_CONFIG_DESCRIPTION, /* change: description    */
				   &sd_buf))                   /* value: new description */ {
		TCHAR msg[1000];
		
		svc_format_message (msg, sizeof (msg));
		cout << " [SERVICE] Unable to add a description to the service. " << msg << endl;
	}        
	
	cout << " [SERVICE] Successfully added the service to the Services database." << endl; 
	
	CloseServiceHandle (sc_service);
	CloseServiceHandle (sc_manager);
} 

/** 
 * Uninstall the service from the system.
 */
void
Windows_Service::uninstall () { 
	SC_HANDLE sc_manager, sc_service;
	
	cout << " [SERVICE] Attempting to uninstall the service." << endl;
	
	/* Remove from the Services database */
	sc_manager = OpenSCManager (NULL,                    /* local machine            */
				    NULL,                    /* ServicesActive database  */
				    SC_MANAGER_ALL_ACCESS);  /* full access rights       */
	
	if (sc_manager == NULL) {
		DWORD   err = GetLastError();
		LPCTSTR basic_message = "Unable to open a connection to the Services database."; 
		TCHAR   msg[1000];
		
		svc_format_message (msg, sizeof (msg));
		
		switch(err) {
		case ERROR_ACCESS_DENIED: 
			cout << " [SERVICE] " << basic_message << " Access is denied. " << msg << endl;
			break;
		
		case ERROR_DATABASE_DOES_NOT_EXIST: 
			cout << " [SERVICE] " << basic_message << " Services database does not exist. " << msg << endl;
			break;
		
		case ERROR_INVALID_PARAMETER: 
			cout << " [SERVICE] " << basic_message << " Invalid parameter. " << msg << endl;
			break;
		
		default: 
			cout << " [SERVICE] " << basic_message;
			cout << " Unrecognized error (" << err << "). " << msg << endl;
			break;
		}
	}
	
	/* Open the service with DELETE access */
	sc_service = OpenService (sc_manager,   /* SCManager database       */
				  service_name, /* name of service          */
				  DELETE);      /* only need DELETE access  */
	
	if (sc_service == NULL) {
		DWORD   err = GetLastError();
		LPCTSTR basic_message = "Unable to locate in the Services database."; 
		TCHAR   msg[1000];
		
		svc_format_message (msg, sizeof (msg));
		
		switch (err) {
		case ERROR_ACCESS_DENIED: 
		    cout << " [SERVICE] " << basic_message << " Access is denied. " << msg << endl;
		    break;
		
		case ERROR_INVALID_HANDLE: 
		    cout << " [SERVICE] " << basic_message << " Invalid handle. " << msg << endl;
		    break;
		
		case ERROR_INVALID_NAME: 
		    cout << " [SERVICE] " << basic_message << " Invalid name. " << msg << endl;
		    break;
		
		case ERROR_SERVICE_DOES_NOT_EXIST: 
		    cout << " [SERVICE] " << basic_message << " Service does not exist. " << msg << endl;
		    break;
		
		default: 
		    cout << " [SERVICE] " << basic_message;
		    cout << "Unrecognized error (" << err << "). " << msg << endl;
		    break;
		}
		 
		CloseServiceHandle (sc_manager);
		return;
	}
	
	if (!DeleteService (sc_service)) {
		DWORD   err = GetLastError();
		LPCTSTR basic_message = "Unable to remove from the Services database."; 
		TCHAR   msg[1000];
		
		svc_format_message (msg, sizeof (msg));
		
		switch(err) {
		case ERROR_ACCESS_DENIED: 
			cout << " [SERVICE] " << basic_message << " Access is denied. " << msg << endl;
			break;
		
		case ERROR_INVALID_HANDLE: 
			cout << " [SERVICE] " << basic_message << " Invalid handle. " << msg << endl;
			break;
		
		case ERROR_SERVICE_MARKED_FOR_DELETE: 
			cout << " [SERVICE] " << basic_message << " Service already marked for delete. " << msg << endl;
			break;
		
		default: 
			cout << " [SERVICE] " << basic_message;
			cout << " Unrecognized error (" << err << "). " << msg << endl;
			break;
		}
	}
	
	cout << " [SERVICE] Successfully removed the service from the Services database."; 
	
	CloseServiceHandle (sc_service); 
	CloseServiceHandle (sc_manager);
} 


/** 
 * Run the service.
 *
 * This function must be called from main function to
 * start the service when started by Windows services system.
 */
void
Windows_Service::run () {
	SERVICE_TABLE_ENTRY ste_dispatch_table[] = 
	{ 
		{ service_name, windows_service_start }, 
		{ NULL,         NULL                  } 
	};
	int err = StartServiceCtrlDispatcher (ste_dispatch_table);
	 
	/* Start up the Win32 Service */
	if (!err) {
		char msg[1024];
		
		memset (msg, sizeof (msg), '\0');
		svc_format_message (msg, sizeof (msg));
	}
} 

static void WINAPI
windows_service_start (DWORD argc, LPTSTR *argv) {

	service_status_handle = RegisterServiceCtrlHandler (current_service->getServiceName (),
							    windows_service_ctrl_handler); 
 
	if (service_status_handle == (SERVICE_STATUS_HANDLE) 0) { 
		TCHAR msg[1000];
		
		svc_format_message (msg, sizeof (msg));
		return; 
	}
	
	/* Initialization code should go here. */
	current_service->execInitFunction ();

	/* Initialization complete - report running status. */
	SetWindowsServiceStatus (SERVICE_RUNNING, 0, 0, 0);
	
	/* This is where the service should do its work. */
	current_service->execRunFunction ();

	return; 
} 

static VOID WINAPI
windows_service_ctrl_handler (DWORD opcode) { 
	switch (opcode) { 
	case SERVICE_CONTROL_PAUSE: 
		SetWindowsServiceStatus (SERVICE_CONTROL_PAUSE, 0, 0, 0);
		break; 
	
	case SERVICE_CONTROL_CONTINUE: 
		SetWindowsServiceStatus (SERVICE_CONTROL_CONTINUE, 0, 0, 0);
		break; 
	
	case SERVICE_CONTROL_STOP: 
		Sleep (READ_TIMEOUT * 2); /* wait for 2x the timeout, just to ensure that things
					   * the service has processed any last packets
					   */
		
		SetWindowsServiceStatus (SERVICE_STOPPED, 0, 0, 0);
		
		return; 
	
	case SERVICE_CONTROL_INTERROGATE: 
		/* Fall through to send current status. */
		break; 
	
	default:
		break;
	} 
	
	return; 
}

static void
SetWindowsServiceStatus (DWORD dwCurrentState, DWORD dwWin32ExitCode,
			 DWORD dwCheckPoint,   DWORD dwWaitHint) {
	SERVICE_STATUS ss;  /* Current status of the service. */
	
	/* Disable control requests until the service is started.*/
	if (dwCurrentState == SERVICE_START_PENDING)
		ss.dwControlsAccepted = 0;
	else
	    ss.dwControlsAccepted = SERVICE_ACCEPT_STOP | SERVICE_ACCEPT_SHUTDOWN;
	
	/* Initialize ss structure. */
	ss.dwServiceType             = SERVICE_WIN32_OWN_PROCESS;
	ss.dwServiceSpecificExitCode = 0;
	ss.dwCurrentState            = dwCurrentState;
	ss.dwWin32ExitCode           = dwWin32ExitCode;
	ss.dwCheckPoint              = dwCheckPoint;
	ss.dwWaitHint                = dwWaitHint;
	
	/* Send status of the service to the Service Controller. */
	if (!SetServiceStatus (service_status_handle, &ss))
	ErrorStopService (TEXT ("SetServiceStatus"));
}

static void
ErrorStopService (LPTSTR lpszAPI)
{
	TCHAR   buffer[256]  = TEXT("");
	TCHAR   error[1024]  = TEXT("");
	LPVOID  lpvMessageBuffer;
	
	wsprintf (buffer, TEXT("API = %s, "), lpszAPI);
	lstrcat (error, buffer);
	
	ZeroMemory(buffer, sizeof(buffer));
	wsprintf(buffer,TEXT("error code = %d, "), GetLastError());
	lstrcat(error, buffer);
	
	// Obtain the error string.
	FormatMessage(
	FORMAT_MESSAGE_ALLOCATE_BUFFER|FORMAT_MESSAGE_FROM_SYSTEM,
	NULL, GetLastError(),
	MAKELANGID(LANG_NEUTRAL, SUBLANG_DEFAULT),
	(LPTSTR)&lpvMessageBuffer, 0, NULL);
	
	ZeroMemory((LPVOID)buffer, (DWORD)sizeof(buffer));
	wsprintf(buffer,TEXT("message = %s"), (TCHAR *)lpvMessageBuffer);
	lstrcat(error, buffer);
	
	// Free the buffer allocated by the system.
	LocalFree (lpvMessageBuffer);
	
	// Write the error string to the debugger.
	
	// If you have threads running, tell them to stop. Something went
	// wrong, and you need to stop them so you can inform the SCM.
//        SetEvent (g_stop_event);
	
	// Stop the service.
	SetWindowsServiceStatus (SERVICE_STOPPED, GetLastError(), 0, 0);
}

static VOID
svc_format_message (LPSTR msg, int count)
{
    LPVOID msg_buf;
    
    if (msg != NULL && count > 0) {
	memset (msg, 0, count);
	FormatMessage (FORMAT_MESSAGE_ALLOCATE_BUFFER | FORMAT_MESSAGE_FROM_SYSTEM | FORMAT_MESSAGE_IGNORE_INSERTS,
		       NULL, GetLastError(),
		       MAKELANGID(LANG_NEUTRAL, SUBLANG_DEFAULT), /* Default language */
		       (LPTSTR) &msg_buf, 0, NULL);

	strncpy (msg, (LPCTSTR) msg_buf, count);
	/* Free the buffer. */
	LocalFree (msg_buf);
	msg_buf = NULL;
    }
}
