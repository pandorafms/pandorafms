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
#include <iostream>
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
 * Reads a binary file and returns its content.
 *
 * @param filepath Path of the file to read.
 *
 * @exception File_Not_Found throwed if the path is incorrect or the
 *            file does not exists or could not be opened.
 *
 * @note Memory allocated by this function must be freed at some point.
 **/
int
Pandora_File::readBinFile (const string filepath, char **buffer) {
  int length;
  ifstream file;

  if (buffer == NULL) {
    throw File_Exception ();
  }

  file.open (filepath.c_str(), ios::binary );  
  if (! file.is_open ()) {
    throw File_Not_Found ();
  }

  /* Get file length */
  file.seekg (0, ios::end);
  length = file.tellg ();
  if (length < 1) {
     throw File_Exception ();
  }

  file.seekg (0, ios::beg);

  *buffer = new char [length];
  if (*buffer == NULL) {
    throw File_Exception ();
  }

  /* Read data */
  file.read (*buffer, length);
  file.close ();

  return length;
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

/**
 * Write binary data into a file.
 *
 * @param filepath Path of the file to write in.
 * @param data Data to be written.
 * @param size Data size in bytes.
 *
 * @exception File_Not_Found throwed if the path is incorrect or the
 *            file does not exists or could not be opened.
 */
void
Pandora_File::writeBinFile (const string filepath, const char *buffer, int size) {
	ofstream  file;
	
	if (buffer == NULL) {
	    throw File_Exception ();
	}
	
	file.open(filepath.c_str (), ios_base::binary | ios_base::trunc);
	if (! file.is_open ()) {
		throw File_Not_Found ();
	}
	file.write (buffer, size);
	file.close ();
}

/** 
 * Returns the filename of a complete filepath.
 * 
 * @param filepath 
 */
string
Pandora_File::fileName (const string filepath)
{
	string filename;
	int    pos;
	
	pos = filepath.find_last_of ("\\");

	if (pos != string::npos) {
		filename = filepath.substr (pos + 1);
	} else {
		filename = filepath;
	}

	return filename;
}

/** 
 * Returns the 32 digit hexadecimal representation of the md5 hash
 * of the given data.
 * 
 * @param data Data.
 * @param data Data size.
 * @param buffer Buffer where the 32 digit hex md5 will be stored.
 *               Must be big enough to hold it!
 */
void
Pandora_File::md5 (const char *data, int size, char *buffer)
{
	int i;
	md5_state_t pms;
	md5_byte_t digest[16];

	if (buffer == NULL) {
		throw File_Exception ();
	}
	
	/* md5 hash */
	md5_init (&pms);
	md5_append (&pms, (unsigned char *)data, size);
	md5_finish (&pms, digest);

	/* 32 digit hexadecimal representation */
	for (i = 0; i < 16; i++) {
		snprintf (buffer + (i << 1), 3, "%.2x", (unsigned int)(digest[i]));
	}
}
