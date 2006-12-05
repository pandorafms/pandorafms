/* Misc utils for files.

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

#include "pandora_file.h"
#include <fstream>
#include <stdio.h>
#include <windows.h>

using namespace std;

/**
 * Checks if a file exists.
 *
 * @param filepath Path of the file to check.
 *
 * @retval True if the file exists.
 **/
bool
Pandora_File::fileExists (const string filepath) {
        ifstream myfile (filepath.c_str ());
        
        if (! myfile.is_open ()) {
                return false;
        }

	myfile.close();
        return true;
}

/**
 * Reads a file and returns its content.
 *
 * @param filepath Path of the file to read.
 *
 * @return File content.
 *
 * @exception File_Not_Found throwed if the path is incorrect or the
 *            file does not exists or could not be opened.
 **/
string
Pandora_File::readFile (const string filepath) {
        string   line, result;
        ifstream myfile (filepath.c_str ());
        
        if (! myfile.is_open ()) {
                throw File_Not_Found ();
        }
        
        if (myfile.is_open()) {
                while (! myfile.eof()) {
                        getline (myfile,line);
                        result += line + '\n';
                }
                myfile.close();
        }
        return result;
}

/**
 * Delete a file from a directory.
 *
 * @param filepath Path of the file to delete.
 *
 * @exception Delete_Error if the file could not be deleted.
 */
void
Pandora_File::removeFile (const string filepath) {
        if (remove (filepath.c_str ()) == -1) {
                 throw Delete_Error ();
        }
}

/**
 * Write data into a text file.
 *
 * @param filepath Path of the file to write in.
 * @param data Data to be written.
 *
 * @exception File_Not_Found throwed if the path is incorrect or the
 *            file does not exists or could not be opened.
 */
void
Pandora_File::writeFile (const string filepath, const string data) {
        ofstream  file (filepath.c_str ());
        
        if (! file.is_open ()) {
                throw File_Not_Found ();
        }
        file.write (data.c_str (), data.length ());
        file.close ();   
}
