/* Pandora exec module. These modules exec a powershell command.

   Copyright (c) 2006-2023 Pandora FMS.

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

#include "pandora_module_exec_powershell.h"
#include "../pandora_strutils.h"

#include <iostream>
#include <stdexcept>
#include <sstream>
#include <string>
#include <vector>
#include <cstdio>

#define EXEC_OK 0
#define EXEC_ERR -1
#define BUFSIZE 4096

using namespace Pandora;
using namespace Pandora_Strutils;
using namespace Pandora_Modules;

/** 
 * Creates a Pandora_Module_Exec_Powershell object.
 * 
 * @param name Module name
 * @param exec Command to be executed.
 */
Pandora_Module_Exec_Powershell::Pandora_Module_Exec_Powershell(string name, string exec) 
                                : Pandora_Module (name) {

	string escaped_exec;

    for (char c : exec) {
        if (c == '"' || c == '\\') {
            escaped_exec += '\\';  
        }
        escaped_exec += c;
    }

    this->module_exec = "powershell -C \"" + escaped_exec + "\"";

    this->setKind (module_exec_powershell_str);
}

void Pandora_Module_Exec_Powershell::run() {
	string output_result;

    this->has_output = false;
    
    FILE* pipe = popen(this->module_exec.c_str(), "r");
    if (!pipe) {
        pandoraLog ("Error while executing command.", GetLastError ());
        return;
    }

    char buffer[BUFSIZE];
    while (fgets(buffer, BUFSIZE, pipe) != NULL) {
        output_result += buffer;
    }

    int result = pclose(pipe);

    if (result == EXEC_ERR) {
        pandoraLog ("Error while closing command process.", GetLastError ());
        return;
    } 
    
    if (result != EXEC_OK) {
        pandoraLog ("Error invalid powershell command.", GetLastError ());
        return;
    }

	this->has_output = true;
    this->setOutput (output_result);
}