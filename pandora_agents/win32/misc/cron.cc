/* Pandora cron manager for Win32.
   
   Copyright (C) 2018 Artica ST.
   Written by Fermin Hernandez.
  
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
#include <stdio.h>
#include <string.h>
#include "cron.h"
#include "../pandora.h"

/**
 * @brief Constructor of cron class
 * 
 * @param cron_string String with cron format (https://en.wikipedia.org/wiki/Cron)
 */
Cron::Cron (string cron_string) {
	char cron_params[5][256], bottom[256], top[256];
	
	// Parse the cron string
	if (sscanf (cron_string.c_str (), "%255s %255s %255s %255s %255s", cron_params[0], cron_params[1], cron_params[2], cron_params[3], cron_params[4]) != 5) {
		Pandora::pandoraDebug ("Invalid cron string: %s", cron_string.c_str ());
        this->isSet = false;
        this->cronString = CRON_DEFAULT_STRING;
		return;
	}

    this->cronString = cron_string;

    // Check if cron string is the default
    if (cron_string.compare(CRON_DEFAULT_STRING) == 0) {
        this->isSet = false;
		return;
    }
	
	// Fill the cron structure
	this->utimestamp = 0;
    this->isSet = true;
    // Months in cron are from 1 to 12. For date, are required from 0 to 11.
	for (int i = 0; i < 5; i++) {
		
		// Wildcard
		if (cron_params[i][0] == '*') {
			this->params[i][0] = -1;
			this->params[i][1] = -1;

		// Interval
		} else if (sscanf (cron_params[i], "%255[^-]-%255s", bottom, top) == 2) {
			this->params[i][0] = (i != 2) ? atoi (bottom) : atoi (bottom) - 1;
			this->params[i][1] = (i != 2) ? atoi (top) : atoi (top) -1;
		
		// Single value
		} else {
			this->params[i][0] = (i != 2)
                ? atoi (cron_params[i])
                : atoi (cron_params[i]) - 1;
			this->params[i][1] = -1;
		}
	}
}
