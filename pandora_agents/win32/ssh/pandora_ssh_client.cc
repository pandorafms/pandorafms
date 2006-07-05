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

#include "pandora_ssh_client.h"
#include "../misc/pandora_file.h"
#include "../pandora_strutils.h"
#include "libssh2/libssh2_sftp.h"
#include <winsock2.h>
#include <fstream>
#include <fcntl.h>
#include <sys/types.h>
#include <errno.h>
#include <stdio.h>
#include <string.h>
#include <ctype.h>
#include <iostream>

using namespace std;
using namespace SSH;
using namespace Pandora;

Connection_Failed::Connection_Failed (int e) {
        err_number = e;
}

int
Connection_Failed::getError () {
        return err_number;
}
Scp_Failed::Scp_Failed (char *e) {
        errmsg = strdup (e);
}

Pandora_Ssh_Client::Pandora_Ssh_Client () {
        sock        = 0;
        fingerprint = "";
        session     = NULL;
        channel     = NULL;
        return;
}

Pandora_Ssh_Client::~Pandora_Ssh_Client () {
        if (session != NULL) {
                disconnect ();
        }
        
        return;
}

/* Disconnects from remote host. It will close all open connections and channels. */
void
Pandora_Ssh_Client::disconnect () {
        if (channel != NULL) {
                libssh2_channel_send_eof (channel);
                libssh2_channel_close (channel);
                libssh2_channel_wait_closed (channel);
                libssh2_channel_free (channel);
                channel = NULL;
        }
        
        if (session != NULL) {
                libssh2_session_disconnect (session, "");
        	libssh2_session_free (session);
                session = NULL;
        }
        
        if (sock != 0) {
                closesocket (sock);
                sock  = 0;
        }
}

void
Pandora_Ssh_Client::newConnection (const string host, const int port) {
        struct sockaddr_in sin;
        struct hostent    *resolv = NULL;
        WSADATA            wsadata;
        string             finger_aux;
        char               char_aux[3];
        
        if (session != NULL) {
                throw Session_Already_Opened ();
        }
        
        WSAStartup (2, &wsadata);
        
        sock = socket (AF_INET, SOCK_STREAM, 0);
        if (sock == -1) {
                throw Socket_Error ();
        } 
        
        resolv = (struct hostent *) gethostbyname (host.c_str ());
        
        if (resolv == NULL) {
                disconnect ();
                throw Resolv_Failed ();
        }
        
        sin.sin_family = AF_INET;
        sin.sin_port = htons (port);
        sin.sin_addr = *((struct in_addr *)resolv->h_addr);
        
        if (connect (sock, (struct sockaddr*) (&sin),
                sizeof (struct sockaddr_in)) == -1) {
                disconnect ();
                throw Connection_Failed (WSAGetLastError ());
        }
        
        session = libssh2_session_init();
        if (libssh2_session_startup (session, sock) != 0) {
                disconnect ();
                throw Session_Error ();
        }
        
        /* Get the fingerprint and transform it to a hexadecimal readable
           string */
        finger_aux = libssh2_hostkey_hash (session,
                                           LIBSSH2_HOSTKEY_HASH_MD5);
        fingerprint = "";
        for (int i = 0; i < 16; i++) {
                sprintf (char_aux, "%02X:", (unsigned char) finger_aux[i]);
                fingerprint += (char *) char_aux;
        }
        
        fingerprint.erase (fingerprint.length () - 1, 2);
}

/* Connects to specified host and port using a username and a password */
void
Pandora_Ssh_Client::connectWithUserPass (const string host, const int port,
                                       const string username, const string passwd) {
        try {
                newConnection (host, port);
        } catch (Session_Already_Opened e) {
        }
        
        if (session != NULL) {
                if (libssh2_userauth_password (session, username.c_str (),
                                               passwd.c_str ())) {
                        disconnect ();
                        throw Authentication_Failed ();
        	}
        }
	return;
}

/* Connects to specified host and port using a username and a public/private key.
 * The keys are the filename that contains the public and the private keys.
 * The passphrase is the password for these keys. */
void
Pandora_Ssh_Client::connectWithPublicKey (const string host, const int port,
                                        const string username, const string filename_pubkey,
                                        const string filename_privkey, const string passphrase) {
        try {
                newConnection (host, port);
        } catch (Session_Already_Opened e) {
        }
        
        if (session != NULL) {
                if (libssh2_userauth_publickey_fromfile (session,
                                                         username.c_str (),
                                                         filename_pubkey.c_str (),
                                                         filename_privkey.c_str (),
                                                         passphrase.c_str ())) {
                        disconnect ();
                        throw Authentication_Failed ();
        	}
        }
	return;
}
                             
/* Copy a file using a SSH connection (scp).
 * The function receives a filename in the local filesystem and copies all
 * its content to the remote host. The remote filename will be the 
 * basename of the local file and will be copied in the remote actual
 * directory. */
void
Pandora_Ssh_Client::scpFileFilename (const string remote_filename, const string filename) {
        LIBSSH2_CHANNEL *scp_channel;
        size_t           to_send, sent;
        char            *errmsg;
        int              errmsg_len;
        string           buffer;
        
        if (session == NULL) {
                throw Session_Not_Opened ();
        }
        try {
                buffer = Pandora_File::readFile (filename);
        } catch (Pandora_File::File_Not_Found e) {
                pandoraLog ("Pandora_Ssh_Client: File %s not found",
                          filename.c_str());
                return;
        }
        
        to_send = buffer.length ();
        
        scp_channel = libssh2_scp_send (session, remote_filename.c_str (), 0666,
                                        to_send);
        if (scp_channel == NULL) {
                throw Channel_Error ();
        }
        
        libssh2_channel_set_blocking (scp_channel, 1);
        
        /* FIXME: It may crash if the scp fails, maybe because of a libssh2 bug */
        sent = libssh2_channel_write (scp_channel, buffer.c_str (), to_send);
        
        if (sent < 0) {
                Scp_Failed *e;
                errmsg = (char *) malloc (sizeof (char) * 1000);
                libssh2_session_last_error (session, &errmsg, &errmsg_len, 1);
                pandoraLog ("Error %d on SCP %s", sent, errmsg);
                e = new Scp_Failed (errmsg);
                        
                libssh2_channel_close (scp_channel);
                libssh2_channel_wait_closed (scp_channel);
                libssh2_channel_free (scp_channel);
                Pandora::pandoraFree (errmsg);
                throw *e;
        }
        libssh2_channel_send_eof (scp_channel);
        
        libssh2_channel_close (scp_channel);
        libssh2_channel_wait_closed (scp_channel);
        libssh2_channel_free (scp_channel);
}

string
Pandora_Ssh_Client::getFingerprint () {
        return this->fingerprint;    
}
