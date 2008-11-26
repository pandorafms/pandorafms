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

/** 
 * Creates a Pandora_SSH_Test object.
 *
 * It will read the configuration file and prepares
 * all the information to perform a SSH test.
 */
Pandora_SSH_Test::Pandora_SSH_Test () {
	string conf_file;
	
	conf_file = Pandora::getPandoraInstallDir ();
	conf_file += "pandora_agent.conf";
	conf = Pandora::Pandora_Agent_Conf::getInstance ();
	conf->setFile (conf_file);
	
	ssh_client = new SSH::Pandora_Ssh_Client ();
}

/** 
 * Deletes a Pandora_SSH_Test object.
 */
Pandora_SSH_Test::~Pandora_SSH_Test () {
	delete conf;
	delete ssh_client;
}

/** 
 * Executes a SSH test.
 *
 * It will generate a lot of output to the stdout.
 *
 * @exception Authentication_Failed Throwed if the authentication process
 *            failed when connecting to the host.
 * @exception Socket_Error Throwed when something goes bad with the sockets.
 * @exception Resolv_Failed Throwed when the remote host could not be resolved
 *            to a valid IP.
 * @exception Connection_Failed Throwed if the TCP/IP connection to the host
 *            failed or could not be done. It includes timeouts, route failures,
 *            etc
 * @exception Session_Error Throwed if there was problem with the SSH session.
 * @exception Pandora::Pandora_Exception Throwed if there was an unespecified
 *            error.
 */
void
Pandora_SSH_Test::test () {
	string            pubkey_file, privkey_file, tmp_filename;
	string            remote_host, remote_filepath, tmp_filepath;
	TiXmlDocument    *doc;
	TiXmlDeclaration *decl;
	bool              saved;
	
	pubkey_file  = Pandora::getPandoraInstallDir ();
	pubkey_file += "key\\id_dsa.pub";
	if (! Pandora_File::fileExists (pubkey_file)) {
		cout << "Public key file " << pubkey_file << " not found."
		     << endl;
		return;
	}
	cout << "Public key file " << pubkey_file << " exists." << endl;
	
	privkey_file = Pandora::getPandoraInstallDir ();
	privkey_file += "key\\id_dsa";
	if (! Pandora_File::fileExists (privkey_file)) {
		cout << "Private key file " << privkey_file << " not found."
		     << endl;
		return;
	}
	cout << "Private key file: " << privkey_file << " exists." << endl;
	
	remote_host = this->conf->getValue ("server_ip");
	cout << "Connecting with " << remote_host << "." << endl;
	
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
		Pandora::Pandora_Exception e;
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
		} catch (Pandora::Pandora_Exception e) {
		}
		throw e;
	} catch (Scp_Failed e) {
		ssh_client->disconnect();
		cout << "The copying operation could not finished." << endl;
		cout << "Check the network configuration." << endl;
		try {
			Pandora_File::removeFile (tmp_filepath);
		} catch (Pandora::Pandora_Exception e) {
		}
		throw e;
	} catch (Pandora::Pandora_Exception e) {
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
