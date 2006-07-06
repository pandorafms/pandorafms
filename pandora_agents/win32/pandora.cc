/* Common functions to any Pandora program.
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

#include <stdio.h>
#include <iostream>
#include <cctype>
#include <string>
#include <algorithm>
#include "pandora.h"
#include "pandora_strutils.h"

using namespace std;
using namespace Pandora;
using namespace Pandora_Strutils;

#define PATH_SIZE    _MAX_PATH+1
#define PANDORA_VERSION ("1.2Beta")

string pandora_path;
string pandora_dir;
bool   pandora_debug;
string pandora_version = PANDORA_VERSION;

void
Key_Value::parseLine (string str) {
        unsigned int           pos;
        list<string>           tokens;
        list<string>::iterator iter;
        string                 trimmedstr;
        
        trimmedstr = trim (str);
        
        /* Check if the string has " */
        pos = trimmedstr.find ("\"");
        if (pos == string::npos) {
                stringtok (tokens, trimmedstr, " \t");
        } else {
                stringtok (tokens, trimmedstr, "\"");
        }
        
        /* Pick the first and the last value of the token list */
        iter = tokens.begin ();
        key = trim (*iter);
        transform (key.begin(), key.end(), key.begin(), (int(*)(int)) tolower);
        iter = tokens.end ();
        iter--;
        /* Check if the line has only one token */
        if (iter != tokens.begin ()) {
                value = trim (*iter);
        } else {
                value = "";
        }
}

string
Key_Value::getKey () {
        return key;
}

string
Key_Value::getValue () {
        return value;
}

void
pandoraWriteLog (string filename, string line) {
        string     buffer; 
        char       str_time[25];
        FILE      *file;
        string     filepath;
        time_t     now;
        struct tm *gmtime;
        
        now = time (0);
        gmtime = localtime (&now);
        strftime (str_time, 25, "%m-%d-%y %H:%M:%S: ", gmtime);
        
        buffer = (char *) str_time;
        buffer += line;
        
        filepath = pandora_dir + filename;
        
        file = fopen (filepath.c_str (), "a+");
        if (file != NULL) {
                fprintf (file, "%s\n", buffer.c_str ());
                fclose (file);
        }
        cout << line << endl;
}

void
Pandora::pandoraLog (char *format, ...) {
        va_list    args;
        char       msg[5000];
        
        va_start (args, format);
        vsprintf (msg, format, args);
        va_end (args);
        
        pandoraWriteLog ("pandora-log.log", (char *) msg);
}

void
Pandora::pandoraDebug (char *format, ...) {
        if (pandora_debug) {
                va_list    args;
                char       msg[5000];
                
                va_start (args, format);
                vsprintf (msg, format, args);
                va_end (args);
                
                pandoraWriteLog ("pandora-debug.dbg", (char *) msg);
        }
        return;
}

void
Pandora::pandoraFree (void * e) {
        if (e != NULL)
                free (e);
        return;       
}

void
Pandora::setPandoraInstallDir (string dir) {
        pandora_dir = dir;
}

string
Pandora::getPandoraInstallDir () {
        return pandora_dir;
}

void
Pandora::setPandoraInstallPath (string path) {
        pandora_path = path;
}

string
Pandora::getPandoraInstallPath () {
        return pandora_path;
}

void
Pandora::setPandoraDebug  (bool dbg) {
        pandora_debug = dbg;
}

string
Pandora::getPandoraAgentVersion () {
        return pandora_version;
}
