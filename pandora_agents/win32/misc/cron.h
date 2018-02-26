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

#ifndef _CRON_PANDORA_H_
#define _CRON_PANDORA_H_

#include <string>
#include <ctime>
#include <stdio.h>
#include <time.h>

using namespace std;

const string CRON_DEFAULT_STRING = "* * * * *";
const int CR_WILDCARD_VALUE = -1;
const int CRDOWN = 0;
const int CRUP = 1;
const int CRINVALID_DATE = -1;
const int CR_SECONDS_ONE_DAY = 86400;
const int CR_MAX_ITERS = 60;

class Cron {
    private:
        // Properties
        time_t                  utimestamp;
        /**
        * @brief Stored cron values array
        * 
        * First index: minutes, hours, months, days, month
        * Second index: bottom, top
        * 
        * Wildcard (*): Bottom and top are -1
        * Single value: Bottom is set and top is -1
        * Interval: Bottom and top are set
        */
        int                     params[5][2];
        bool                    isSet;
        string                  cronString;
        time_t                  cronInterval;

        // Methods
        time_t                  getNextExecutionFrom(time_t date, int interval);
        bool                    isInCron(time_t date);
        bool                    isBetweenParams(int value, int position);
        bool                    isWildCard(int position);
        bool                    isSingleValue(int position);
        bool                    isNormalInterval(int position);
        int                     getResetValue(int position);

    public:
        // Constructor
                                Cron(string cron_string);
        // Getter & setters
        bool                    getIsSet();
        string                  getCronString();
        string                  getCronIntervalStr();

        // Other methods
        void                    update(time_t date, int interval);
        bool                    shouldExecuteAt(time_t date);
        bool                    shouldExecuteAtFirst(time_t date);
};

#endif
