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
#include "pandora_module.h"
#include "../pandora_strutils.h"
#include <windows.h> 

#define BUFSIZE 4096 

using namespace Pandora;
using namespace Pandora_Strutils;
using namespace Pandora_Modules;

Pandora_Module_Exec::Pandora_Module_Exec (string name, string exec)
                                         : Pandora_Module (name) {
        this->module_exec = "cmd.exe /c " + exec;
        
        this->module_kind_str = module_exec_str;
        this->module_kind     = MODULE_EXEC;
}

void
Pandora_Module_Exec::run () {
        STARTUPINFO         si;
        PROCESS_INFORMATION pi;
        DWORD               retval;
        SECURITY_ATTRIBUTES attributes;
        HANDLE              out, new_stdout, out_read;
        string              working_dir;
        
        this->output = "";
        
        /* Set the bInheritHandle flag so pipe handles are inherited. */
        attributes.nLength = sizeof (SECURITY_ATTRIBUTES); 
        attributes.bInheritHandle = TRUE; 
        attributes.lpSecurityDescriptor = NULL; 
        
        /* Get the handle to the current STDOUT. */
        out = GetStdHandle (STD_OUTPUT_HANDLE); 
        
        if (! CreatePipe (&out_read, &new_stdout, &attributes, 0)) {
                pandoraLog ("CreatePipe failed");
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
        if (! CreateProcess (NULL, (CHAR *) this->module_exec.c_str (), NULL, NULL, TRUE,
                             CREATE_NEW_CONSOLE, NULL, working_dir.c_str (),
                             &si, &pi)) {
                pandoraLog ("Babel_Windows_Password: %s CreateProcess failed (%d)",
                            this->module_name.c_str (), GetLastError ());
        } else {
                char          buffer[BUFSIZE + 1];
                unsigned long read, avail;
                
                /* Wait until process exits. */
                /* TODO: This should not be infinite, but just a few seconds */
                WaitForSingleObject (pi.hProcess, INFINITE);
                
                GetExitCodeProcess (pi.hProcess, &retval);
                if (retval != 0) {
                        pandoraLog ("Pandora_Module: %s did not executed well (retcode: %d)",
                                    this->module_name.c_str (), retval);
                }
                
                PeekNamedPipe (out_read, buffer, BUFSIZE, &read, &avail, NULL);
                /* Read from the stdout */
                
                if (read != 0) {
                        do {
                                ReadFile (out_read, buffer, BUFSIZE, &read,
                                          NULL);
                                this->output += (char *) buffer;
                                memset (buffer, 0, BUFSIZE);
                        } while (read >= BUFSIZE);
                }
                
                /* Close process and thread handles. */
                CloseHandle (pi.hProcess);
                CloseHandle (pi.hThread);
        }
        
        CloseHandle (new_stdout);
        CloseHandle (out_read);
}

