/* Library to manage a list of key-value options.

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

#include <fstream>
#include "pandora_agent_conf.h"
#include "pandora.h"

using namespace std;

#define MAX_KEYS 100

Pandora_Agent_Conf::Pandora_Agent_Conf (string filename) {
        ifstream     file (filename.c_str ());
        string       buffer;
        unsigned int pos;
        
        this->key_values = new list<Key_Value> ();
        
        if (!file.is_open ()) {
                pandoraDebug ("No hay conf");
                return;
        }
        
        /* Read and set the file */
        while (!file.eof ()) {
                /* Set the value from each line */
                getline (file, buffer);
                
                /* Ignore blank or commented lines */
                if (buffer[0] != '#' && buffer[0] != '\n' && buffer[0] != '\0') {
                        pos = buffer.find ("module_");
                        if (pos == string::npos) {
                                Key_Value kv;
                                
                                kv.parseLine (buffer);
                                key_values->push_back (kv);
                        }
                }
        }
        file.close ();
}

Pandora_Agent_Conf::~Pandora_Agent_Conf () {
        delete key_values;
}

string
Pandora_Agent_Conf::getValue (const string key) {
        std::list<Key_Value>::iterator i = key_values->begin ();
        
        while (i != key_values->end ()) {
                if ((*i).getKey () == key) {
                        return (*i).getValue ();
                }
                i++;
        }
        
        return "";
}

