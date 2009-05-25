/* Pandora logevent module. This module checks for log events that match a given
   pattern.

   Copyright (C) 2008 Artica ST.
   Written by Ramon Novoa.

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

#ifndef	__PANDORA_MODULE_LOGEVENT_H__
#define	__PANDORA_MODULE_LOGEVENT_H__

#include "pandora_module.h"

// Log event read buffer size
#define	BUFFER_SIZE 1024

// Length of a timestamp string YYYY-MM-DD HH:MM:SS
#define	TIMESTAMP_LEN 19

namespace Pandora_Modules {
    
	/**
	 * This module checks for log events that match a given
     * pattern. Events can be filtered by source and type.
	 */

	class Pandora_Module_Logevent : public Pandora_Module {
	private:
        int id;
		int type;
		string source;
		string application;
		string pattern;
		HANDLE log_event;
		HANDLE messages_dll;

        HANDLE openLogEvent ();
        void closeLogEvent ();
        void discardLogEvents ();
        int getLogEvents (list<string> &event_list);
        void timestampToSystemtime (string timestamp, SYSTEMTIME *system_time);
        void getEventDescription (PEVENTLOGRECORD pevlr, char *message);
        int filterEvent (PEVENTLOGRECORD pevlr, string description);

	public:
		Pandora_Module_Logevent (string name, string source, string type, string id, string pattern, string application);
		void run ();
	};
}

#endif
