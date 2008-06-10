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

#ifndef	__PANDORA_FILE__
#define	__PANDORA_FILE__

#include <string>
#include "../pandora.h"
#include "md5.h"

using namespace std;

/**
 * File operations.
 */
namespace Pandora_File {
	/**
	 * File super-class exception.
	 */
        class File_Exception : Pandora::Pandora_Exception {
        };
	
	/**
	 * Exception throwed when a file could not be found when doing
	 * a file operation.
	 */
        class File_Not_Found : Pandora_File::File_Exception {
        };

        /**
	 * Exception throwed when a file could not be deleted on a delete
	 * operation.
	 */
        class Delete_Error : Pandora_File::File_Exception {
        };

	bool   fileExists (const string filename);
        string readFile   (const string filename);	
        int    readBinFile (const string filepath, char **buffer);
        void   removeFile (const string filename);
        void   writeFile  (const string filename, const string data);
        void   writeBinFile (const string filepath, const char *buffer, int size);
        
	string fileName   (const string filepath);
    void   md5 (const char *data, int size, char *buffer);
}

#endif
