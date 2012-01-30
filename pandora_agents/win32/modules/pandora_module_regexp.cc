/* Pandora regexp module. This module searches a file for matches of
   a regular expression.

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

#include "pandora_module_regexp.h"
#include "../pandora_windows_service.h"

using namespace Pandora;
using namespace Pandora_Modules;

/** 
 * Creates a Pandora_Module_Regexp object.
 * 
 * @param name Module name.
 * @param source File to search.
 * @param pattern Regular expression to match.
 */
Pandora_Module_Regexp::Pandora_Module_Regexp (string name, string source, string pattern, unsigned char no_seek_eof)
	: Pandora_Module (name) {
 
    this->source = source;
	this->no_seek_eof = no_seek_eof;
	
    // Compile the regular expression
    if (regcomp (&this->regexp, pattern.c_str (), REG_EXTENDED) != 0) {
       pandoraLog ("Invalid regular expression %s", pattern.c_str ());
    }
 
    // Check whether the file can be opened
    this->file.open (source.c_str ());
    if (this->file.is_open ()) {
		this->file.close ();
    } else {
        pandoraLog ("Error opening file %s", source.c_str ());
    }
 
	// Set file size to 0
	this->size = 0;
	
	this->setKind (module_regexp_str);
}

/** 
 * Pandora_Module_Regexp destructor.
 */
Pandora_Module_Regexp::~Pandora_Module_Regexp () {
	regfree (&this->regexp);
	if (this->file.is_open ()) {
		this->file.close();
	}
}

void
Pandora_Module_Regexp::run () {
    int count;
	string line;
	ostringstream output;
    Module_Type type;
	struct stat file_stat; 
   
    type = this->getTypeInt ();

	// Run
	try {
		Pandora_Module::run ();
	} catch (Interval_Not_Fulfilled e) {
		return;
	}

    // Check if the file could be opened
    if (! file.is_open () || ! file.good ()) {
        this->restart (this->no_seek_eof);
    }
	
	// Check if the file was truncated
	if (this->no_seek_eof == 0 && stat (this->source.c_str (), &file_stat ) == 0) {
		if (file_stat.st_size < this->size) {
			this->restart (1);
		}
		
		// Save current file size
		this->size = file_stat.st_size;
	}

	// Check again, in case an open or a restart failed
    if (! file.is_open () || ! file.good ()) {
		return;
	}

    // Read new lines
    count = 0;
    while (! file.eof ()) {
        getline (file, line);
        
        // Discard empty lines
        if (line.empty ()) {
            continue;
        }

        // Try to match the line with the regexp
        if (regexec (&this->regexp, line.c_str (), 0, NULL, 0) == 0) {
            if (type == TYPE_GENERIC_DATA_STRING || type == TYPE_ASYNC_STRING) {
                this->setOutput (line);
            }
            count++;
        }
    }
    
    // Set output according to the module type
    if (type == TYPE_GENERIC_DATA_STRING || type == TYPE_ASYNC_STRING) {
        // Already set
    }
    else if (type == TYPE_GENERIC_PROC || type == TYPE_ASYNC_PROC) {
        this->setOutput (count > 0 ? "1" : "0");
    } else {
        output << count;
        this->setOutput (output.str ());
    }

    // Clear the EOF flag
    file.clear ();
    
    // Next time the file will be opened again and read from the start
    if (this->no_seek_eof == 1) {
		this->file.close();
	}
}

/**
 * Closes, re-opens and seeks to the end of the current file.
 */
void
Pandora_Module_Regexp::restart (unsigned char no_seek_eof) {
	if (this->file.is_open ()) {
		this->file.close ();
	}
    this->file.open (this->source.c_str ());
    if (this->file.is_open ()) {
		if (no_seek_eof == 0) {
			this->file.seekg (0, ios_base::end);
		}
        return;
    }
    
    pandoraLog ("Error opening file %s", this->source.c_str ());
}
