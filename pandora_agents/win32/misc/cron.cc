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
#include <sstream>
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
	this->cronInterval = 0;
    this->isSet = true;
    // Months in cron are from 1 to 12. For date, are required from 0 to 11.
	for (int i = 0; i < 5; i++) {
		
		// Wildcard
		if (cron_params[i][0] == '*') {
			this->params[i][CRDOWN] = CR_WILDCARD_VALUE;
			this->params[i][1] = CR_WILDCARD_VALUE;

		// Interval
		} else if (sscanf (cron_params[i], "%255[^-]-%255s", bottom, top) == 2) {
            // Check if there is an interval with two equal values 
            if (strcmp(bottom, top) == 0) {
                this->params[i][CRDOWN] = (i != 3)
                    ? atoi (cron_params[i])
                    : atoi (cron_params[i]) - 1;
                this->params[i][CRUP] = CR_WILDCARD_VALUE;    
            } else {
                this->params[i][CRDOWN] = (i != 3) ? atoi (bottom) : atoi (bottom) - 1;
                this->params[i][CRUP] = (i != 3) ? atoi (top) : atoi (top) -1;
            }
		
		// Single value
		} else {
			this->params[i][CRDOWN] = (i != 3)
                ? atoi (cron_params[i])
                : atoi (cron_params[i]) - 1;
			this->params[i][CRUP] = CR_WILDCARD_VALUE;
		}
	}
}

/**
 * @brief Getter of isSet property
 * 
 */
bool Cron::getIsSet () { return this->isSet; }

/**
 * @brief Getter of cronString property
 * 
 */
string Cron::getCronString() { return this->cronString; }

/**
 * @brief Getter of cronInterval property casting in string
 *
 */
string Cron::getCronIntervalStr() {
    stringstream ss;
    ss << this->cronInterval;
    return ss.str();
}


/**
 * @brief Set utimestamp (private set)
 * 
 * @param date when module will be executed next time
 * @param now current timestamp. Required to update interval
 */
void Cron::setUtimestamp(time_t date, time_t now) {
    this->utimestamp = date;
    this->cronInterval = date - now;
    Pandora::pandoraDebug(
        "Module with cron %s will be executed at timestamp: %d.",
        this->cronString.c_str(),
        this->utimestamp
    );
}

/**
 * @brief Given a date, return if is inside a cron string or not
 * 
 * @param date Date to check
 * @return true If is inside cron
 * @return false If is outside cron
 */
bool Cron::isInCron(time_t date) {
    struct tm * timeinfo = localtime(&date);

    // Convert the tm struct to an array
    int date_array[4] = {
        timeinfo->tm_min,
        timeinfo->tm_hour,
        timeinfo->tm_mday,
        timeinfo->tm_mon
    };

    // Check all positions faliures
    for (int i = 0; i < 4; i++) {
        if (!isWildCard(i)) {
            if (isSingleValue(i)) {
                if (this->params[i][CRDOWN] != date_array[i]) return false;
            } else {
                if (isNormalInterval(i)) {
                    if (
                        date_array[i] < this->params[i][CRDOWN] ||
                        date_array[i] > this->params[i][CRUP]
                    ) {
                        return false;
                    }
                } else {
                    if (
                        date_array[i] < this->params[i][CRDOWN] &&
                        date_array[i] > this->params[i][CRUP]
                    ) {
                        return false;
                    }
                }
            }
        }
    }

    // If no failures, date is inside cron.
    return true;
}

/**
 * @brief Check if a cron position is a wildcard
 * 
 * @param position Position inside the param array
 * @return true if is a wildcard
 */
bool Cron::isWildCard(int position) {
    return this->params[position][CRDOWN] == CR_WILDCARD_VALUE;
}

/**
 * @brief Check if a cron position is a single value
 * 
 * @param position Position inside the param array
 * @return true if is a single value
 */
bool Cron::isSingleValue(int position) {
    return this->params[position][CRUP] == CR_WILDCARD_VALUE;
}

/**
 * @brief Check if a cron position is an interval with down lower than up
 * 
 * @param position Position inside the param array
 * @return true if is a normal interval
 * @return false if is an inverse interval
 */
bool Cron::isNormalInterval (int position) {
    // Wildcard and single value will be treated like normal interval
    if (
        this->params[position][CRDOWN] == CR_WILDCARD_VALUE ||
        this->params[position][CRUP] == CR_WILDCARD_VALUE
    ) {
        return true;
    }
    return (this->params[position][CRUP] >= this->params[position][CRDOWN]);
}

/**
 * @brief Get the reset value on a cron position
 * 
 * @param position 
 * @return int Reset value
 */
int Cron::getResetValue (int position) {
    int default_value = 0;
    // Days start in 1
    if (position == 2) default_value = 1;
    return isWildCard(position)
        ? default_value
        : this->params[position][CRDOWN];
}

/**
 * @brief Check if cron module should be executed at a given time
 * 
 * @param date 
 * @return true if should execute
 * @return false if should not execute
 */
bool Cron::shouldExecuteAt (time_t date) {
    return this->utimestamp < date;
}

/**
 * @brief Check if a module should be executed when utimestamp is not calculated yet
 * 
 * @param date Current date
 * @return true It is not first time and current date fit in cron
 * @return false Don't execute first time
 */
bool Cron::shouldExecuteAtFirst (time_t date) {

    // Return true if it is not first
    if (this->utimestamp != 0) return true;

    // Check current date in cron
    return isInCron(date);
}

/**
 * @brief Update the cron utimestamp
 * 
 * @param date Timestamp "from" to update cron utimestamp
 * @param interval Module interval
 */
void Cron::update (time_t date, int interval) {

    time_t nex_time = date + interval;
    if (isInCron(nex_time)) {
        setUtimestamp(nex_time, date);
        return;
    }

    // Copy tm struct values to an empty struct to avoid conflicts
    struct tm * timeinfo_first = localtime(&nex_time);
    struct tm * timeinfo = new tm();
    timeinfo->tm_sec = 0;
    timeinfo->tm_min = timeinfo_first->tm_min;
    timeinfo->tm_hour = timeinfo_first->tm_hour;
    timeinfo->tm_mday = timeinfo_first->tm_mday;
    timeinfo->tm_mon = timeinfo_first->tm_mon;
    timeinfo->tm_year = timeinfo_first->tm_year;

    // Update minutes
    timeinfo->tm_min = getResetValue(0);
    nex_time = mktime(timeinfo);
    if (nex_time >= date && isInCron(nex_time)) {
        setUtimestamp(nex_time, date);
        return;
    }

    if (nex_time == CRINVALID_DATE) {
        // Update the month day if overflow
        timeinfo->tm_hour = 0;
        timeinfo->tm_mday++;
        nex_time = mktime(timeinfo);
        if (nex_time == CRINVALID_DATE) {
            // Update the month if overflow
            timeinfo->tm_mday = 1;
            timeinfo->tm_mon++;
            nex_time = mktime(timeinfo);
            if (nex_time == CRINVALID_DATE) {
                // Update the year if overflow
                timeinfo->tm_mon = 0;
                timeinfo->tm_year++;
                nex_time = mktime(timeinfo);
            }
        }
    }

    // Check the hour
    if (isInCron(nex_time)) {
        setUtimestamp(nex_time, date);
    }

    // Update hour if fails
    timeinfo->tm_hour = getResetValue(1);
    
    // When an overflow is passed check the hour update again
    nex_time = mktime(timeinfo);
    if (nex_time >= date && isInCron(nex_time)) {
        setUtimestamp(nex_time, date);
        return;
    }

    // Check if next day is in cron
    timeinfo->tm_mday++;
    nex_time = mktime(timeinfo);
    if (nex_time == CRINVALID_DATE) {
        // Update the month if overflow
        timeinfo->tm_mday = 1;
        timeinfo->tm_mon++;
        nex_time = mktime(timeinfo);
        if (nex_time == CRINVALID_DATE) {
            // Update the year if overflow
            timeinfo->tm_mon = 0;
            timeinfo->tm_year++;
            nex_time = mktime(timeinfo);
        }
    }

    // Check the day
    if (isInCron(nex_time)){
        setUtimestamp(nex_time, date);
        return;
    }

    // Update the day if fails
    timeinfo->tm_mday = getResetValue(2);

    // When an overflow is passed check the day update in the next execution
    nex_time = mktime(timeinfo);
    if (nex_time >= date && isInCron(nex_time)) {
        setUtimestamp(nex_time, date);
        return;
    }

    // Check if next month is in cron
    timeinfo->tm_mon++;
    nex_time = mktime(timeinfo);
    if (nex_time == CRINVALID_DATE) {
        // Update the year if overflow
        timeinfo->tm_mon = 0;
        timeinfo->tm_year++;
        nex_time = mktime(timeinfo);
    }

    // Check the month
    if (isInCron(nex_time)) {
        setUtimestamp(nex_time, date);
        return;
    }

    // Update the month if fails
    timeinfo->tm_mon = getResetValue(3);

    // When an overflow is passed check the month update in the next execution
    nex_time = mktime(timeinfo);
    if (nex_time >= date) {
        setUtimestamp(nex_time, date);
        return;
    }

    // Update the year if fails
    timeinfo->tm_year++;
    nex_time = mktime(timeinfo);

    setUtimestamp(nex_time, date);
}