/* Test module to prove SSH connection.

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

#include "pandora_ssh_test.h"
#include "../tinyxml/tinyxml.h"
#include "../misc/pandora_file.h"
#include <iostream>

using namespace std;
using namespace SSH;

Pandora_SSH_Test::Pandora_SSH_Test () {
        string conf_file;
        
        conf_file = Pandora::getPandoraInstallDir ();
        conf_file += "pandora_agent.conf";
        conf = new Pandora_Agent_Conf (conf_file);
        
        ssh_client = new SSH::Pandora_Ssh_Client ();
}

Pandora_SSH_Test::~Pandora_SSH_Test () {
        delete conf;
        delete ssh_client;
}

void
Pandora_SSH_Test::test () {
        string            pubkey_file, privkey_file, tmp_filename;
        string            remote_host, remote_filepath, tmp_filepath;
        TiXmlDocument    *doc;
        TiXmlDeclaration *decl;
        bool              saved;
        
        remote_host = this->conf->getValue ("server_ip");
        
        pubkey_file  = Pandora::getPandoraInstallDir ();
        pubkey_file += "key\\id_dsa.pub";
        privkey_file = Pandora::getPandoraInstallDir ();
        privkey_file += "key\\id_dsa";
        
        cout << "Public key file: " << pubkey_file << endl;
        cout << "Private key file: " << privkey_file << endl;
        cout << "Connecting with " << remote_host << "..." << endl;
        
        try {
                this->ssh_client->connectWithPublicKey (remote_host.c_str (), 22,
                                                        "pandora", pubkey_file,
                                                        privkey_file, "");
        } catch (Authentication_Failed e) {
                cout << "Authentication Failed when connecting to "
                     << remote_host << endl;
                cout << "Check the remote host configuration and the public/private key files."
                     << endl;
                throw e;
        } catch (Socket_Error e) {
                cout << "Socket error when connecting to "
                     << remote_host << endl;
                cout << "Check the network configuration." << endl;
                throw e;
        } catch (Resolv_Failed e) {
                cout << "Could not resolv "
                     << remote_host << endl;
                cout << "Check the network configuration." << endl;
                throw e;
        } catch (Connection_Failed e) {
                cout << "Connection error number " << e.getError () << endl;
                cout << "Check the network configuration." << endl;
                throw e;
        } catch (Session_Error e) {
                cout << "Error while opening SSH session." << endl;
                cout << "Check the network configuration." << endl;
                throw e;
        }
        
        cout << "Authentication successful." << endl;
        cout << "Host fingerprint: " << this->ssh_client->getFingerprint ()
             << endl;
        
        tmp_filename = "ssh.test";
        tmp_filepath = conf->getValue ("temporal");
        if (tmp_filepath[tmp_filepath.length () - 1] != '\\') {
                tmp_filepath += "\\";
        }
        tmp_filepath += tmp_filename;
        
        decl = new TiXmlDeclaration( "1.0", "ISO-8859-1", "" );
        doc = new TiXmlDocument (tmp_filepath);
        doc->InsertEndChild (*decl);
        saved = doc->SaveFile();
        delete doc;
        if (!saved) {
                Pandora_Exception e;
                cout << "Error when saving the XML in " << tmp_filepath << endl;
                cout << "Check the configuration file" << endl;
                throw e;
        }
        
        cout << "Created a blank XML file in " << tmp_filepath<< endl;
        
        remote_filepath = conf->getValue ("server_path");
        if (remote_filepath[remote_filepath.length () - 1] != '/') {
                remote_filepath += "/";
        }
        
        cout << "Remote copying " << tmp_filepath << " on server " << remote_host
             <<  " at " << remote_filepath << tmp_filename << endl;
        try {
                ssh_client->scpFileFilename (remote_filepath + tmp_filename,
                                             tmp_filepath);
        } catch (Session_Not_Opened e) {
                ssh_client->disconnect();
                cout << "The SSH session could not be created." << endl;
                cout << "Check the network configuration." << endl;
                try {
                        Pandora_File::removeFile (tmp_filepath);
                } catch (Pandora_Exception e) {
                }
                throw e;
        } catch (Scp_Failed e) {
                ssh_client->disconnect();
                cout << "The copying operation could not finished." << endl;
                cout << "Check the network configuration." << endl;
                try {
                        Pandora_File::removeFile (tmp_filepath);
                } catch (Pandora_Exception e) {
                }
                throw e;
        } catch (Pandora_Exception e) {
                ssh_client->disconnect();
                cout << "An unhandled exception happened." << endl;
                throw e;
        }
        
        cout << "Successfuly file copied to remote host " << endl;
        ssh_client->disconnect();
        cout << "Successfuly disconnected from remote host " << endl;
        try {
                Pandora_File::removeFile (tmp_filepath);
        } catch (Pandora_File::Delete_Error e) {
        }
        cout << "The SSH test was successful!" << endl;
}
