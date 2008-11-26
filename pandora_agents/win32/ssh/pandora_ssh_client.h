/* Class to abstract an SSH client. It uses libssh2.

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

#ifndef	__PANDORA_SSH_CLIENT__
#define	__PANDORA_SSH_CLIENT__

#include <string>
#include "../pandora.h"
#include "libssh2/libssh2.h"

using namespace std;

/**
 * SSH connection classes.
 */
namespace SSH {
	/**
	 * A SSH super-class exception.
	 */
	class SSH_Exception          : public Pandora::Pandora_Exception {
	};
	
	/**
	 * A SSH session has already be opened and a new
	 * connection is attemped without closing it.
	 */
	class Session_Already_Opened : public SSH::SSH_Exception {
	};

	/**
	 * A SSH operations is tried and a session
	 * has still not be opened.
	 */
	class Session_Not_Opened     : public SSH::SSH_Exception {
	};

	/**
	 * There were unknown problems with the SSH session.
	 */
	class Session_Error          : public SSH::SSH_Exception {
	};
	
	/**
	 * The SSH authentication fails when connecting.
	 */
	class Authentication_Failed  : public SSH::SSH_Exception {
	};

	/**
	 * The host could not be resolved.	   
	 */
	class Resolv_Failed          : public SSH::SSH_Exception {
	};

	/**
	 * Unknown socket error.
	 */
	class Socket_Error           : public SSH::SSH_Exception {
	};

	/**
	 * An error happened with a file.
	 */
	class File_Error             : public SSH::SSH_Exception {
	};

	/**
	 * An error occured with the SSH channel.
	 */
	class Channel_Error          : public SSH::SSH_Exception {
	};

	/**
	 * Connection failed with the host.
	 */
	class Connection_Failed      : public SSH::SSH_Exception {
	private:
		int err_number;
	public:
		Connection_Failed (int e);
		int getError ();
	};

	/**
	 * The scp operation failed due to some unknow error.
	 */	
	class Scp_Failed             : public SSH::SSH_Exception {
	private:
		char *errmsg;
	public:
		Scp_Failed  (char *e);
		~Scp_Failed () { Pandora::pandoraFree (errmsg); };
	};
	
	/**
	 * Client to perform a SSH connection to a host.
	 */
	class Pandora_Ssh_Client {
	private:
		int              sock;
		string           fingerprint;
		LIBSSH2_SESSION *session;
		LIBSSH2_CHANNEL *channel;
		
		void newConnection (const string host, const int port);
	public:
		Pandora_Ssh_Client          ();
		~Pandora_Ssh_Client         ();
	    
		void connectWithPublicKey (const string host, const int port,
					   const string username, const string filename_pubkey,
					   const string filename_privkey, const string passphrase);
		
	    
		void disconnect           ();
					     
		void scpFileFilename      (const string remote_filename, 
					   const string filename);
					   
		string getFingerprint     ();
	};
}
#endif
