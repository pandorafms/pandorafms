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

#include <stdlib.h>
#include <stdio.h>
#include <string.h>
#include <winsock2.h>

#include "udp_server.h"
#include "../pandora.h"
#include "../windows/pandora_wmi.h"

using namespace Pandora;

/** 
 * Get the address of the server.
 * 
 * @return Server address.
 */
unsigned long UDP_Server::getAddress () {
	return this->address;
}

/** 
 * Get the port of the server.
 * 
 * @return Server port.
 */
unsigned long UDP_Server::getPort () {
	return this->port;
}

/** 
 * Get the windows service associated to the server.
 * 
 * @return Windows service associated to the server.
 */
Pandora_Windows_Service *UDP_Server::getService () {
	return this->service;
}

/** 
 * Returns the state of the server.
 * 
 * @return 1 if the server is running, 0 if not.
 */
unsigned char UDP_Server::isRunning () {
	return this->running;
}

/** 
 * UDP_Server constructor.
 * 
 * @param service Service associated to the server.
 * @param address Server address.
 * @param auth_address Authorized address.
 * @param port Server port.
 */
UDP_Server::UDP_Server (Pandora_Windows_Service *service, string address, string auth_address, unsigned int port) {
	if (address.empty ()) {
	   this->address = INADDR_ANY;
	} else {
		this->address = inet_addr (address.c_str ());
	}
	if (auth_address.empty ()) {
		this->auth_address.push_front(INADDR_ANY);
	} else {
	   splitAuthAddress (auth_address);
	}
	this->port = port;
	this->running = 0;
	this->service = service;
}

/** 
 * UDP_Server destructor.
 */
UDP_Server::~UDP_Server () {};

/** 
 * Starts the server.
 * 
 * @return 1 on error, 0 otherwise.
 */
int UDP_Server::start () {
	if (this->running != 0) {
		return 1;
	}
	
	/* Run in a new thread */
	this->running = 1;
	if (CreateThread (NULL, 0, (LPTHREAD_START_ROUTINE) listen, this, 0, NULL) == NULL) {
		this->running = 0;
		pandoraLog ("UDP Server: Error starting UDP Server thread");
		return 1;
	}

	pandoraLog ("UDP Server: UDP Server started on port %d", this->port);
	return 0;
}

/** 
 * Stops the server.
 * 
 * @return 1 on error, 0 otherwise.
 */
int UDP_Server::stop () {
	if (this->running != 0) {
		return 1;
	}
	
	this->running = 0;
	pandoraLog ("UDP Server: UDP Server going down");
	return 0;
}

/** 
 * Listens for incoming packets.
 * 
 * @param server UDP Server.
 */
void Pandora::listen (UDP_Server *server) {
	int sockfd,n;
	struct sockaddr_in servaddr, cliaddr;
	int len, err;
	char mesg[MAX_PACKET_SIZE];  
	unsigned long auth_addr;
	WSADATA wsa;

	err = WSAStartup (MAKEWORD (2,0), &wsa);
	if (err != 0) {
		/* Could not find a usable Winsock DLL */
		printf("UDP Server: WSAStartup failed with error: %d\n", err);
		return;
	}

	sockfd = socket (AF_INET, SOCK_DGRAM, 0);

	memset (&servaddr, 0, sizeof(servaddr));
	servaddr.sin_family = AF_INET;
	servaddr.sin_addr.s_addr = htonl (server->getAddress ());
	servaddr.sin_port = htons (server->getPort ());
	bind(sockfd, (struct sockaddr *)&servaddr, sizeof (servaddr));

	while (server->isRunning () == 1) {
		len = sizeof(cliaddr);
		n = recvfrom(sockfd, mesg, MAX_PACKET_SIZE, 0, (struct sockaddr *)&cliaddr, &len);		
		if (n == SOCKET_ERROR) {
			pandoraLog ("UDP Server: Error %d", WSAGetLastError ());
			break;
		}

		/* Authenticate client */
		if (server->isAddressAuth (cliaddr.sin_addr.s_addr)) {
			mesg[n] = 0;
			process_command (server->getService (), mesg);
		} else {
			pandoraLog ("UDP Server: Unauthorised access from %s", inet_ntoa (cliaddr.sin_addr));
		}
	}

	WSACleanup ();
}

/** 
 * Processes and executes server commands.
 * 
 * @param service Windows service associated to the server.
 * @param command Server command.
 * 
 * @return 1 on error, 0 otherwise.
 */
int Pandora::process_command (Pandora_Windows_Service *service, char *command) {
	int rc;
	char operation[MAX_PACKET_SIZE], action[MAX_PACKET_SIZE], target[MAX_PACKET_SIZE];
	string var, value;
	Pandora_Agent_Conf  *conf = NULL;

	rc = sscanf (command, "%s %s %s", operation, action, target);
	if (rc < 3) {
		pandoraLog ("UDP Server: Received invalid data: %s", command);
		return 1;
	}

	/* Re-run */
	if (strcmp (operation, "REFRESH") == 0) {
		service->pandora_run (1);
		return 0;
	}
	
	conf = service->getConf();
	
	/* Service management */
	if (strcmp (action, "SERVICE") == 0) {
		var = "service_";
		var.append (target);
		std::transform(var.begin(), var.end(), var.begin(), ::tolower);
		value = conf->getValue (var);
		if (atoi (value.c_str ()) != 1) {
			pandoraLog ("UDP Server: Unauthorised access to service %s", target);
			return 1;
		}

		if (strcmp (operation, "START") == 0) {
			Pandora_Wmi::startService (target);
		} else if (strcmp (operation, "STOP") == 0) {
			Pandora_Wmi::stopService (target);
		}
	}

	/* Process management */
	if (strcmp (action, "PROCESS") == 0) {
		var = "process_";
		var.append (target);
		std::transform(var.begin(), var.end(), var.begin(), ::tolower);

		if (strcmp (operation, "START") == 0) {
			var.append ("_start");
		} else if (strcmp (operation, "STOP") == 0) {
			var.append ("_stop");
		} else {
			return 1;
		}

		value = conf->getValue (var);
		if (value.empty ()) {
			pandoraLog ("UDP Server: Unauthorised access to process %s", target);
			return 1;
		}
		Pandora_Wmi::runProgram (value.c_str());
	}

	return 0;
}

void UDP_Server::splitAuthAddress (string all_address) {	
	this->auth_address.clear();
	size_t comma_pos;
	string single_ip;
	do {
		single_ip.clear();
		/*Splits ips with comma*/
		comma_pos = all_address.find_first_of (',', 0);
		if (comma_pos != string::npos){
			single_ip = all_address.substr (0, comma_pos);
		} else {
			single_ip = all_address;
		}
		unsigned long single_ip_num = inet_addr (single_ip.c_str ());
		if (single_ip_num != INADDR_NONE) {
			this->auth_address.push_back (single_ip_num);
		} else {
			pandoraDebug ("Invalid UDP Server Auth Address: %s", single_ip.c_str ());
		}
		all_address = all_address.substr (comma_pos + 1, all_address.length ());
	} while (comma_pos != string::npos);
}

bool UDP_Server::isAddressAuth (unsigned long ip){	
	for (this->it=(this->auth_address).begin(); this->it != (this->auth_address).end(); ++it) {
		if (*it == ip || *it == INADDR_ANY) {
			return true;
		}
	}
	return false;
}
