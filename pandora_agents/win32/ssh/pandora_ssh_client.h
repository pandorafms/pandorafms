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

#ifndef	__PANDORA_SSH_CLIENT_H__
#define	__PANDORA_SSH_CLIENT_H__

#include <string>
#include "../pandora.h"
#include "libssh2/libssh2.h"

using namespace std;

namespace SSH {
        /* SSH Client exceptions */
        class Session_Already_Opened : public Pandora::Pandora_Exception {
        };

        class Session_Not_Opened     : public Pandora::Pandora_Exception {
        };

        class Session_Error          : public Pandora::Pandora_Exception {
        };
                        
        class Authentication_Failed  : public Pandora::Pandora_Exception {
        };

        class Resolv_Failed          : public Pandora::Pandora_Exception {
        };

        class Socket_Error           : public Pandora::Pandora_Exception {
        };
        
        class File_Error             : public Pandora::Pandora_Exception {
        };
        
        class Channel_Error          : public Pandora::Pandora_Exception {
        };
        
        class Connection_Failed      : public Pandora::Pandora_Exception {
        private:
                int err_number;
        public:
                Connection_Failed (int e);
                int getError ();
        };
        
        class Scp_Failed             : public Pandora::Pandora_Exception {
        private:
                char *errmsg;
        public:
                Scp_Failed  (char *e);
                ~Scp_Failed () { Pandora::pandoraFree (errmsg); };
        };
        
        /* SSH Client class */
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
            
                /* Connects to specified host and port using a username and a password 
                 *
                 * Throws:  Authentication_Failed */
                void connectWithUserPass  (const string host, const int port,
                                           const string username, const string passwd);
                /* Connects to specified host and port using a username and a public/private key.
                 * The keys are the filename that contains the public and the private keys.
                 * The passphrase is the password for these keys.
                 *
                 * Throws:  Authentication_Failed */
                void connectWithPublicKey (const string host, const int port,
                                           const string username, const string filename_pubkey,
                                           const string filename_privkey, const string passphrase);
                
                /* Disconnects from remote host. It will close all open connections and channels. */
                void disconnect           ();
                                             
                /* Copy a file using a SSH connection (scp).
                 * The function receives a filename in the local filesystem and copies all
                 * its content to the remote host. The remote filename will be the 
                 * first argument. */
                void scpFileFilename      (const string remote_filename, 
                                           const string filename);
                                           
                string getFingerprint     ();
        };
}
#endif
