/* Common functions to any pandora program.
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

#ifndef	__PANDORA_H__
#define	__PANDORA_H__

#include <list>
#include <string>
#include <windows.h>
#include "windows_service.h"

using namespace std;

#define	PANDORA_DEBUG 1

namespace Pandora {
          
        class Key_Value {
        protected:
                string key;
                string value;
        public:
                void   parseLine (string str);
                string getKey    ();
                string getValue  ();
        };
        
        static const HKEY  hkey          = HKEY_LOCAL_MACHINE;
        const char * const name          = "PandoraService";
        const char * const display_name  = "Pandora service";
        const char * const description   = "The Pandora agents service";
        
        void   setPandoraInstallDir  (string dir);
        string getPandoraInstallDir  ();
        void   setPandoraInstallPath (string path);
        string getPandoraInstallPath ();
        void   setPandoraDebug       (bool dbg);
             
        void   pandoraDebug (char *format, ...);
        void   pandoraLog   (char *format, ...);
        void   pandoraFree  (void * e);
        
        class Pandora_Exception { };
}

#endif /* __BABEL_H__ */
