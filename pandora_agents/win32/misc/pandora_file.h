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

#ifndef	__PANDORA_FILE_H__
#define	__PANDORA_FILE_H__

#include <string>
#include "../pandora.h"

using namespace std;

namespace Pandora_File {
        class File_Not_Found : Pandora::Pandora_Exception {
        };
        
        class Delete_Error : Pandora::Pandora_Exception {
        };
        
        string readFile   (const string filename);
        void   removeFile (const string filename);
        void   writeFile  (const string filename, const string data);
}

#endif
