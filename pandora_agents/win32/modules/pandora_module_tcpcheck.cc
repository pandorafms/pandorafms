/* Pandora tcpcheck module. This module checks whether a tcp port is open.

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

#include <winsock.h>
#include "pandora_module_tcpcheck.h"

using namespace Pandora;
using namespace Pandora_Modules;

/** 
 * Creates a Pandora_Module_Tcpcheck object.
 * 
 * @param name Module name.
 * @param address Target address.
 * @param port Target port.
 * @param timeout Connection timeout.
 */
Pandora_Module_Tcpcheck::Pandora_Module_Tcpcheck (string name, string address, string port, string timeout)
	: Pandora_Module (name) {
    int rc;
 
    // Initialize Winsock
    
    this->address = address;
    this->port = atoi (port.c_str ());
    this->timeout = atoi (timeout.c_str ());
    
    // Set a default timeout
    if (this->timeout < 1) {
        this->timeout = DEFAULT_TIMEOUT;
    }

	this->setKind (module_tcpcheck_str);
}

/** 
 * Pandora_Module_Tcpcheck destructor.
 */
Pandora_Module_Tcpcheck::~Pandora_Module_Tcpcheck () {
}

void
Pandora_Module_Tcpcheck::run () {
	int rc, sock, port;
    unsigned long mode = 1;
	struct sockaddr_in server;
	struct hostent *host = NULL;
	fd_set socket_set;
	timeval timer;

	WSADATA wsa_data;
 
    // Initialize Winsock
    WSAStartup (MAKEWORD(2,2), &wsa_data);



	// Run
	try {
		Pandora_Module::run ();
	} catch (Interval_Not_Fulfilled e) {
		return;
	}

	if (this->address.empty ()) {
		WSACleanup ();
		return;
	}

	// Create the TCP socket
	sock = socket (PF_INET, SOCK_STREAM, IPPROTO_TCP);
	if (sock == SOCKET_ERROR) {
		pandoraLog ("Error %d creating socket", WSAGetLastError ());
		WSACleanup ();
		return;
	}

	memset(&server, 0, sizeof(server));
	server.sin_family = AF_INET;
	server.sin_port = htons(this->port);
	server.sin_addr.s_addr = inet_addr(this->address.c_str ());
	if (server.sin_addr.s_addr == -1) {

		// Try to resolve the address
		host = gethostbyname(this->address.c_str ());
		if (host == NULL) {
			pandoraLog ("Could not resolve address for %s", this->address.c_str ());
			WSACleanup ();
			return;
		}
		
		memcpy(&server.sin_addr, host->h_addr_list[0], host->h_length);
	}

     // Use non-blocking sockets to implement a timeout
	ioctlsocket (sock, FIONBIO, &mode);

	// Connection request
	rc = connect(sock, (struct sockaddr *) &server, sizeof(server));
    if (rc == SOCKET_ERROR) {
        rc = WSAGetLastError ();
        if (rc != WSAEWOULDBLOCK) {
		    pandoraLog ("connect error %d", rc);
		    WSACleanup ();
		    return;
        }
	}

    // Determine the completion of the connection request by checking to see if
    // the socket is writeable
	socket_set.fd_array[0] = sock;
	socket_set.fd_count = 1;
	timer.tv_sec = this->timeout;

	rc = select(0, NULL, &socket_set, NULL, &timer);
	if (rc == 0) {
        this->setOutput ("0");

	WSACleanup ();
        return;
    }
    WSACleanup ();
    this->setOutput ("1");
}
