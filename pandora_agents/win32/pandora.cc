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
#define PANDORA_VERSION ("2.0(Build 080610)")

string pandora_path;
string pandora_dir;
bool   pandora_debug;
string pandora_version = PANDORA_VERSION;

/**
 * Parses a string and initialize the key and the value.
 * 
 * The string should be in the format:
 *  - <code>key value</code>
 *  - <code>key "value with blankspaces"</code>
 */
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

/**
 * Get the key of the object.
 * 
 * @return The key
 */
string
Key_Value::getKey () {
	return key;
}

/**
 * Get the value of the object.
 * 
 * @return The value
 */
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
	SYSTEMTIME st;
		
	GetSystemTime(&st);
	sprintf (str_time, "%d-%02d-%02d %02d:%02d:%02d ", st.wYear, st.wMonth, st.wDay,
		st.wHour, st.wMinute, st.wSecond);

	buffer = (char *) str_time;
	buffer += line;
	
	filepath = pandora_dir + filename;
	
	file = fopen (filepath.c_str (), "a+");
	if (file != NULL) {
		fprintf (file, "%s\n", buffer.c_str ());
		fclose (file);
	}
}

/**
 * Write a message in the log file.
 *
 * The log file is used to write the output of errors and problems of the
 * agent.
 *
 * @param format String output format (like printf).
 * @param ... Variable argument list
 */
void
Pandora::pandoraLog (char *format, ...) {
	va_list    args;
	char       msg[5000];
	
	va_start (args, format);
	vsprintf (msg, format, args);
	va_end (args);
	
	pandoraWriteLog ("pandora_agent.log", (char *) msg);
}

/**
 * Write a message in the debug file.
 *
 * The log file is used to write the output of debugging information of the
 * agent.
 *
 * @param format String output format.
 * @param ... Variable argument list
 */
void
Pandora::pandoraDebug (char *format, ...) {
	if (pandora_debug) {
		va_list    args;
		char       msg[5000];
		
		va_start (args, format);
		vsprintf (msg, format, args);
		va_end (args);
		
		pandoraWriteLog ("pandora_debug.log", (char *) msg);
	}
	return;
}

/**
 * Secure free of a pointer.
 *
 * @param pointer pointer to free.
 */
void
Pandora::pandoraFree (void * pointer) {
	if (pointer != NULL)
		free (pointer);
	return;       
}

/**
 * Set the installation directory of the application.
 *
 * This directory is the path to the directory which holds
 * the binary file.
 *
 * @param dir The path to the directory. 
 *
 * @see getPandoraInstallDir
 */
void
Pandora::setPandoraInstallDir (string dir) {
	pandora_dir = dir;
}

/**
 * Get the installation directory of the application.
 *
 * This directory is the path to the directory which holds
 * the binary file.
 *
 * @return The path to the directory.
 *
 * @see setPandoraInstallDir
 */
string
Pandora::getPandoraInstallDir () {
	return pandora_dir;
}

/**
 * Set the installation path of the application.
 *
 * This the complete path to the binary file.
 *
 * @param path The path to the binary file. 
 *
 * @see getPandoraInstallPath
 */
void
Pandora::setPandoraInstallPath (string path) {
	pandora_path = path;
}

/**
 * Get the installation path of the application.
 *
 * This the complete path to the binary file.
 *
 * @return The path.
 *
 * @see setPandoraInstallPath
 */
string
Pandora::getPandoraInstallPath () {
	return pandora_path;
}

/**
 * Set the debug flag.
 *
 * If the flag is true output wil be generated and XML files will not be deleted.
 *
 * @param dbg Turns the debug flag on/off.
 * 
 * @see getPandoraDebug
 * @see pandoraDebug
 */
void
Pandora::setPandoraDebug  (bool dbg) {
	pandora_debug = dbg;
}

/**
 * Get the debug flag value.
 *
 * If the flag is truee output wil be generated and XML files will not be deleted.
 *
 * @see setPandoraDebug
 * @see pandoraDebug
 */
bool
Pandora::getPandoraDebug  () {
	return pandora_debug;
}


/**
 * Get the version of the agent.
 *
 * @return The version.
 */
string
Pandora::getPandoraAgentVersion () {
	return pandora_version;
}


bool
Pandora::is_enabled (string value) {
	static string enabled_values[] = {"enabled", "1", "on", "yes", "si", "sí", "ok", "true", ""};
	int i = 0;
	
	if (value == "") {
		return false;
	}
	
	while (enabled_values[i] != "") {
		if (enabled_values[i] == value) {
			return true;
		}
		i++;
	}
	return false;
}
