/* Pandora agent service for Win32.
   
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

#ifndef	__PANDORA_WINDOWS_SERVICE_H__
#define	__PANDORA_WINDOWS_SERVICE_H__

#include <list>
#include "windows_service.h"
#include "tinyxml/tinyxml.h"
#include "pandora_agent_conf.h"
#include "modules/pandora_module_list.h"
#include "ssh/pandora_ssh_client.h"

using namespace std;
using namespace Pandora_Modules;

namespace Pandora {
	/**
	 * Class to implement the Pandora Windows service.
	 */
	class Pandora_Windows_Service : public Windows_Service {
	private:
		Pandora_Agent_Conf  *conf;
		Pandora_Module_List *modules;
		long                 execution_number;
		string               agent_name;
		long                 interval;
		long                 elapsed_transfer_time;
		long                 transfer_interval;
		bool                 started;
		
		TiXmlElement  *getXmlHeader    ();
		void           copyDataFile    (string filename);
		void           copyTentacleDataFile (string host,
						     string filename);
		void           copyScpDataFile (string host,
						string remote_path,
						string filename);
		void           copyFtpDataFile (string host,
						string remote_path,
						string filename);
		void           recvDataFile (string filename);
		void           recvTentacleDataFile (string host,
						     string filename);
		void           checkConfig ();
		
		Pandora_Windows_Service     ();
	public:
		void           pandora_run  ();
		void           pandora_init ();
	public:
		static Pandora_Windows_Service *getInstance ();
		
		~Pandora_Windows_Service    ();
		
		void           setValues    (const char *svc_name,
					     const char *svc_display_name,
					     const char *svc_description);
		
		void           start        ();
		void           sendXml      (Pandora_Module_List *modules);
	};
}

#endif
