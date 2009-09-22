/* 
   
   Copyright (C) 2009 Artica ST.
   Written by Ramon Novoa
  
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


#ifndef __UDP_SERVER_H__
#define __UDP_SERVER_H__

#define MAX_PACKET_SIZE	1024

#include "../pandora_windows_service.h"

namespace Pandora {

	/**
	 * UDP Server class.
	 */
	class UDP_Server {
		public:
			UDP_Server (Pandora_Windows_Service *service, string address, string auth_address, unsigned int port);
			virtual ~UDP_Server ();
			unsigned long getAddress ();
			unsigned long getAuthAddress ();
			unsigned long getPort ();
			Pandora_Windows_Service *getService ();
			unsigned char isRunning ();

			int start ();
			int stop ();

		private:
			unsigned long address;
			unsigned long auth_address;
			unsigned long port;
			unsigned char running;
			Pandora_Windows_Service *service;
	};

	void listen (UDP_Server *server);
	int process_command (Pandora_Windows_Service *service, char *command);
}

#endif
