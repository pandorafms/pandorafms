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

string
Pandora_File::readFile (const string filename) {
        string   line, result;
        ifstream myfile (filename.c_str ());
        
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

void
Pandora_File::removeFile (const string filename) {
        if (remove (filename.c_str ()) == -1) {
                 throw Delete_Error ();
        }
}

void
Pandora_File::writeFile (const string filename, const string data) {
        ofstream  file (filename.c_str ());
        
        if (! file.is_open ()) {
                throw File_Not_Found ();
        }
        file.write (data.c_str (), data.length ());
        file.close ();   
}
