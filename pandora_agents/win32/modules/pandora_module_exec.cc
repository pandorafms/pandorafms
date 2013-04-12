/* Pandora exec module. These modules exec a command.

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

#include "pandora_module_exec.h"
#include "../pandora_strutils.h"
#include <windows.h> 

#define BUFSIZE 4096 

using namespace Pandora;
using namespace Pandora_Strutils;
using namespace Pandora_Modules;

/** 
 * Creates a Pandora_Module_Exec object.
 * 
 * @param name Module name
 * @param exec Command to be executed.
 */
Pandora_Module_Exec::Pandora_Module_Exec (string name, string exec)
					 : Pandora_Module (name) {
	this->module_exec = "cmd.exe /c \"" + exec + "\"";
	this->proc = 0;
	this->setKind (module_exec_str);
}

void
Pandora_Module_Exec::run () {
	STARTUPINFO         si;
	PROCESS_INFORMATION pi;
	DWORD               retval, dwRet;
	SECURITY_ATTRIBUTES attributes;
	HANDLE              out, new_stdout, out_read, job;
	string              working_dir;

	try {
		Pandora_Module::run ();
	} catch (Interval_Not_Fulfilled e) {
		this->has_output = false;
		return;
	}

	/* Set the bInheritHandle flag so pipe handles are inherited. */
	attributes.nLength = sizeof (SECURITY_ATTRIBUTES); 
	attributes.bInheritHandle = TRUE; 
	attributes.lpSecurityDescriptor = NULL; 

	/* Create a job to kill the child tree if it become zombie */
	/* CAUTION: In order to compile this, WINVER should be defined to 0x0500.
	   This may need no change, since it was redefined by the 
	   program, but if needed, the macro is defined 
	   in <windef.h> */
	job = CreateJobObject (&attributes, this->module_name.c_str ());
	if (job == NULL) {
		pandoraLog ("CreateJobObject bad. Err: %d", GetLastError ());
		this->has_output = false;
		return;
	}

	/* Get the handle to the current STDOUT. */
	out = GetStdHandle (STD_OUTPUT_HANDLE); 

	if (! CreatePipe (&out_read, &new_stdout, &attributes, 0)) {
		pandoraLog ("CreatePipe failed. Err: %d", GetLastError ());
		this->has_output = false;

		CloseHandle (job);
		return;
	}

	/* Ensure the read handle to the pipe for STDOUT is not inherited */
	SetHandleInformation (out_read, HANDLE_FLAG_INHERIT, 0);

	/* Set up members of the STARTUPINFO structure. */
	ZeroMemory (&si, sizeof (si));
	GetStartupInfo (&si);

	si.cb = sizeof (si);
	si.dwFlags     = STARTF_USESTDHANDLES | STARTF_USESHOWWINDOW;
	si.wShowWindow = SW_HIDE;
	si.hStdError   = new_stdout;
	si.hStdOutput  = new_stdout;

	/* Set up members of the PROCESS_INFORMATION structure. */
	ZeroMemory (&pi, sizeof (pi));
	pandoraDebug ("Executing: %s", this->module_exec.c_str ());

	/* Set the working directory of the process. It's "utils" directory
	   to find the GNU W32 tools */
	working_dir = getPandoraInstallDir () + "util\\";

	/* Create the child process. */
	if (! CreateProcess (NULL, (CHAR *) this->module_exec.c_str (), NULL,
			     NULL, TRUE, CREATE_SUSPENDED | CREATE_NO_WINDOW, NULL,
			     working_dir.c_str (), &si, &pi)) {
		pandoraLog ("Pandora_Module_Exec: %s CreateProcess failed. Err: %d",
			    this->module_name.c_str (), GetLastError ());
		this->has_output = false;

		/* Close job */
		CloseHandle (job);
	} else {
		char          buffer[BUFSIZE + 1];
		unsigned long read, avail;
	
		if (! AssignProcessToJobObject (job, pi.hProcess)) {
			pandoraLog ("Could not assigned proccess to job (error %d)",
				    GetLastError ());
		}
		ResumeThread (pi.hThread);
	
		string output;
		int tickbase = GetTickCount();
		while ( (dwRet = WaitForSingleObject (pi.hProcess, 500)) != WAIT_ABANDONED ) {
			PeekNamedPipe (out_read, buffer, BUFSIZE, &read, &avail, NULL);
			if (avail > 0) {
				ReadFile (out_read, buffer, BUFSIZE, &read, NULL);
				buffer[read] = '\0';
				output += (char *) buffer;
			}

			if (dwRet == WAIT_OBJECT_0) { 
				break;
			} else if(this->getTimeout() < GetTickCount() - tickbase) {
				/* STILL_ACTIVE */
				TerminateProcess(pi.hProcess, STILL_ACTIVE);
				pandoraLog ("Pandora_Module_Exec: %s timed out (retcode: %d)", this->module_name.c_str (), STILL_ACTIVE);
				break;
			}
		}

		GetExitCodeProcess (pi.hProcess, &retval);

		if (retval != 0) {
			if (! TerminateJobObject (job, 0)) {
				pandoraLog ("TerminateJobObject failed. (error %d)",
					    GetLastError ());
			}
			if (retval != STILL_ACTIVE && this->proc == 0) {
				pandoraLog ("Pandora_Module_Exec: %s did not executed well (retcode: %d)",
				this->module_name.c_str (), retval);
			}
			this->has_output = false;
		}

		// Proc mode
		if (this->proc == 1) {
			if (retval == 0) {
				this->setOutput ("1");
			} else {
				this->setOutput ("0");
				this->has_output = true;
			}
		}
		// Command output mode
		else if (!output.empty()) {
			this->setOutput (output);
		} else {
			this->setOutput ("");
		}
	
		/* Close job, process and thread handles. */
		CloseHandle (job);
		CloseHandle (pi.hProcess);
		CloseHandle (pi.hThread);
	}

	CloseHandle (new_stdout);
	CloseHandle (out_read);
}

