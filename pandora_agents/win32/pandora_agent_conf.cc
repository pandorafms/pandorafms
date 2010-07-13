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
#include "pandora_strutils.h"
#include <iostream>
#include "pandora.h"

using namespace std;
using namespace Pandora;
using namespace Pandora_Strutils;

#define MAX_KEYS 100

Pandora::Pandora_Agent_Conf::Pandora_Agent_Conf () {
	this->key_values = NULL;
	this->collection_list = NULL;
}

/**
 * Destroy a Pandora_Agent_Conf object.
 */
Pandora::Pandora_Agent_Conf::~Pandora_Agent_Conf () {
	delete key_values; 
	delete collection_list;
}

Pandora_Agent_Conf *
Pandora::Pandora_Agent_Conf::getInstance () {
	static Pandora_Agent_Conf *conf = NULL;

	if (conf)
		return conf;
	conf = new Pandora_Agent_Conf ();
	return conf;
}

/**
 * Sets configuration file to Pandora_Agent_Conf object instance.
 * 
 * It parses the filename and initialize the internal structures
 * of configuration values. The configuration file consist of a number of
 * lines of some of these forms:
 *  - <code>name value</code>
 *  - <code>name "value with blankspaces"</code>
 *
 * @param filename Configuration file to open.
 */
void
Pandora::Pandora_Agent_Conf::setFile (string filename) {
	ifstream     file (filename.c_str ());
	string       buffer;
	unsigned int pos;

	if (this->key_values)
		delete this->key_values;
	this->key_values = new list<Key_Value> ();

	if (this->collection_list)
		delete this->collection_list;
	this->collection_list = new list<string> ();
	
	if (!file.is_open ()) {
		return;
	}
	
	/* Read and set the file */
	while (!file.eof ()) {
		/* Set the value from each line */
		getline (file, buffer);
		
		/* Ignore blank or commented lines */
		if (buffer[0] != '#' && buffer[0] != '\n' && buffer[0] != '\0') {
			/*Check if is a collection*/
			pos = buffer.find("file_collection");
			if(pos != string::npos) {
				string collection_name, trimmed_str;
				
				/*Add collection to collection_list*/
				/*The number 15 is the number of character of string file_collection*/
				collection_name = buffer.substr(pos+15);
				trimmed_str = trim (collection_name);
				collection_list->push_back (trimmed_str);
				continue;
			}
			/*Check if is a module*/
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

/**
 * Queries for a configuration value.
 * 
 * This method search in the key_values attribute for a
 * configuration value which match the key supplied.
 *
 * @param key Key to look for.
 *
 * @return The value of the configuration key looked for.
 *         If it could not be found then an empty string is returned.
 */
string
Pandora::Pandora_Agent_Conf::getValue (const string key)
{
	std::list<Key_Value>::iterator i;
	
	for (i = key_values->begin (); i != key_values->end (); i++) {
		if ((*i).getKey () == key) {
			return (*i).getValue ();
		}
	}
	
	return "";
}

