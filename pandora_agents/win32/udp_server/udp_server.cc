#include <stdlib.h>
#include <stdio.h>
#include <string.h>
#include <WinSock2.h>

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
 * Get the address authorized to send commands to
 * the server.
 * 
 * @return Authorized address.
 */
unsigned long UDP_Server::getAuthAddress () {
	return this->auth_address;
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
		this->auth_address = INADDR_ANY;
	} else {
	   this->auth_address = inet_addr (auth_address.c_str ());
	}
	this->port = port;
	this->running = 0;
	this->service = service;
}

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

	/* Get authorised address */
	auth_addr = server->getAuthAddress ();

	while (server->isRunning () == 1) {
		len = sizeof(cliaddr);
		n = recvfrom(sockfd, mesg, MAX_PACKET_SIZE, 0, (struct sockaddr *)&cliaddr, &len);
		if (n == SOCKET_ERROR) {
			pandoraLog ("UDP Server: Error %d", WSAGetLastError ());
			break;
		}

		/* Authenticate client */
		if (auth_addr != INADDR_ANY && auth_addr != cliaddr.sin_addr.s_addr) {
			pandoraLog ("UDP Server: Unauthorised access from %s", inet_ntoa (cliaddr.sin_addr));
			continue;
		}

		mesg[n] = 0;
		process_command (server->getService (), mesg);
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
		service->pandora_run ();
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
