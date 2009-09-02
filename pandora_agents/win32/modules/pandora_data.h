/* Pandora data class to represent a value and a timestamp.
   
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


#ifndef	__PANDORA_DATA_H__
#define	__PANDORA_DATA_H__

#include <string>
#include <windows.h>

using namespace std;

namespace Pandora {
    const string pandora_data_unknown_source = "unknown_source";
	/**
	 * Class to implement the Pandora Windows service.
	 */
	class Pandora_Data {
	private:
		string     value;
		SYSTEMTIME timestamp;
		string 	   data_origin;
	public:
		Pandora_Data            ();
		Pandora_Data            (string value);
		Pandora_Data            (string value, SYSTEMTIME *system_time);
		Pandora_Data            (string value, string data_orign);
		Pandora_Data            (string value, SYSTEMTIME *system_time, string data_orign);
		~Pandora_Data           ();

		string     getValue     () const;
		string     getTimestamp () const;
		string	   getDataOrigin() const;
		
		void       setValue     (string value);
		void	   setDataOrigin(string data_origin);
	};
}

#endif

