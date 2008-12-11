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
			~UDP_Server ();
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
