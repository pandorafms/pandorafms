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

#include "pandora_data.h"

using namespace Pandora;

/** 
 * Pandora_Data constructor.
 *
 * Creates a Pandora_Data object with the value received, the current time
 * as timestamp and "unknown_source" as data_origin.
 * 
 * @param value Data value.
 */
Pandora_Data::Pandora_Data (string value) {
	this->value = value;
	GetSystemTime (&(this->timestamp));
	this->data_origin = pandora_data_unknown_source;
}

/** 
 * Pandora_Data constructor.
 *
 * Creates a Pandora_Data object with the value received, the received
 * system_time as timestamp and "unknown_source" as data_origin.
 * 
 * 
 * @param value Data value.
 * @param system_time Timestamp.
 */
Pandora_Data::Pandora_Data (string value, SYSTEMTIME *system_time) {
    this->value = value;
    this->timestamp.wYear = system_time->wYear;
    this->timestamp.wMonth = system_time->wMonth;
    this->timestamp.wDay = system_time->wDay;
    this->timestamp.wHour = system_time->wHour;
    this->timestamp.wMinute = system_time->wMinute;
    this->timestamp.wSecond = system_time->wSecond;
    this->data_origin = pandora_data_unknown_source;
}

/** 
 * Pandora_Data constructor.
 *
 * Creates a Pandora_Data object with the value and data_origin received and the
 * current time as timestamp 
 * 
 * @param value Data value.
 * @param data_origin Data origin
 */
Pandora_Data::Pandora_Data (string value, string data_origin) {
    this->value = value;
	GetSystemTime (&(this->timestamp));
    this->data_origin = data_origin;
}

/** 
 * Pandora_Data constructor.
 *
 * Set all attributes
 * 
 * @param value Data value.
 * @param system_time Timestamp.
 * @param data_origin Data origin
 */
Pandora_Data::Pandora_Data (string value, SYSTEMTIME *system_time, string data_origin) {
    this->value = value;
    this->timestamp.wYear = system_time->wYear;
    this->timestamp.wMonth = system_time->wMonth;
    this->timestamp.wDay = system_time->wDay;
    this->timestamp.wHour = system_time->wHour;
    this->timestamp.wMinute = system_time->wMinute;
    this->timestamp.wSecond = system_time->wSecond;
    this->data_origin = data_origin;
}

/** 
 * Pandora_Data default constructor
 * 
 * Set all parameters to blank
 */
Pandora_Data::Pandora_Data () {
	this->value = "";
	GetSystemTime (&(this->timestamp));
	this->data_origin = "";
}

/** 
 * Destructor of Pandora_Data.
 */
Pandora_Data::~Pandora_Data () {
}

/** 
 * Get value property of Pandora_Data object 
 * 
 * @return Value property.
 */
string
Pandora_Data::getValue () const {
	return this->value;
}

/** 
 * Get timestamp property of Pandora_Data object in a human readable format.
 * 
 * @return Timestamp formatted.
 */
string
Pandora_Data::getTimestamp () const {
	char   strtime[20];
	string retval;
	
	sprintf (strtime, "%d-%02d-%02d %02d:%02d:%02d", this->timestamp.wYear, this->timestamp.wMonth, this->timestamp.wDay,
		 this->timestamp.wHour, this->timestamp.wMinute, this->timestamp.wSecond);
	retval = strtime;
	return retval;
}

/** 
 * Set value property of Pandora_Data object
 * 
 * @param value Value to set.
 */
void
Pandora_Data::setValue (string value) {
	this->value = value;
}

/** 
 * Get data_origin property of Pandora_Data object 
 * 
 * @return data_origin property.
 */
string	   
Pandora_Data::getDataOrigin() const {
	return this->data_origin;	   
}

/** 
 * Set data_origin property of Pandora_Data object
 * 
 * @param data_origin Data Oring to set.
 */

void
Pandora_Data::setDataOrigin(string data_origin) {
	this->data_origin = data_origin;
}
