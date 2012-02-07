/* Pandora agents service for Win32.
   
   Copyright (C) 2006 Artica ST.
   Written by Esteban Sanchez, Ramon Novoa.
  
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

#include "pandora_windows_service.h"
#include "pandora.h"
#include "pandora_strutils.h"
#include "windows_service.h"
#include "modules/pandora_module_factory.h"
#include "ssh/pandora_ssh_client.h"
#include "ftp/pandora_ftp_client.h"
#include "misc/pandora_file.h"
#include "windows/pandora_windows_info.h"
#include "udp_server/udp_server.h"

#include <iostream>
#include <cstdlib>
#include <ctime>
#include <direct.h>
#include <sys/types.h>
#include <dirent.h>
#include <sys/stat.h>
#include <pandora_agent_conf.h>

using namespace std;
using namespace Pandora;
using namespace Pandora_Modules;
using namespace Pandora_Strutils;

Pandora_Windows_Service::Pandora_Windows_Service ()
  : Windows_Service (NULL, NULL, NULL) {
	this->setInitFunction ((void (Windows_Service::*) ())
			       &Pandora_Windows_Service::pandora_init);
	this->setRunFunction ((void (Windows_Service::*) ())
			      &Pandora_Windows_Service::pandora_run);
	this->started = false;
}

/** 
 * Set Pandora service Windows properties.
 * 
 * @param svc_name Internal service name
 * @param svc_display_name Service name that will appear in the
 *        Windows service administration tool.
 * @param svc_description Long description of the service.
 */
void
Pandora_Windows_Service::setValues (const char * svc_name,
				    const char * svc_display_name,
				    const char * svc_description) {
	this->service_name          = (char *) svc_name;
	this->service_display_name  = (char *) svc_display_name;
	this->service_description   = (char *) svc_description;
	execution_number            = 0;
	this->modules               = NULL;
	this->conf                  = NULL;
	this->interval              = 60000;
	this->transfer_interval     = this->interval;
	this->elapsed_transfer_time = 0;
	this->udp_server            = NULL;
}

/** 
 * Destroys a Pandora_Windows_Service object.
 */
Pandora_Windows_Service::~Pandora_Windows_Service () {

	if (this->conf != NULL) {
		delete this->conf;
	}
	
	if (this->udp_server != NULL) {
		((UDP_Server *)udp_server)->stop ();
		delete (UDP_Server *)udp_server;
	}

	if (this->modules != NULL) {
		delete this->modules;
	}
	pandoraLog ("Pandora agent stopped");
}

Pandora_Windows_Service *
Pandora_Windows_Service::getInstance () {
	static Pandora_Windows_Service *service = NULL;
	
	if (service != NULL)
		return service;
	
	service = new Pandora_Windows_Service ();
	
	return service;
}

void
Pandora_Windows_Service::start () {
	this->started = true;
}

void
Pandora_Windows_Service::pandora_init () {
	string conf_file, interval, debug, transfer_interval, util_dir, path, env;
	string udp_server_enabled, udp_server_port, udp_server_addr, udp_server_auth_addr;

	setPandoraDebug (true);
	
	// Add the util subdirectory to the PATH
	util_dir = Pandora::getPandoraInstallDir ();
	util_dir += "util";
	path = getenv ("PATH");
	env = "PATH=" + path + ";" + util_dir;
	putenv (env.c_str ());
	
	conf_file = Pandora::getPandoraInstallDir ();
	conf_file += "pandora_agent.conf";
	
	this->conf = Pandora::Pandora_Agent_Conf::getInstance ();
	this->conf->setFile (conf_file);
	this->modules = new Pandora_Module_List (conf_file);

	/* Get the interval value (in seconds) and set it to the service */
	interval = conf->getValue ("interval");
	transfer_interval = conf->getValue ("transfer_interval");
	
	debug = conf->getValue ("debug");
	setPandoraDebug (is_enabled (debug));
	
	if (interval != "") {
		try {
			/* miliseconds */
			this->interval = strtoint (interval) * 1000;
		} catch (Invalid_Conversion e) {
		}
	}
	
	if (transfer_interval == "") {
		this->transfer_interval = this->interval;
	} else {
		try {
			/* miliseconds */
			this->transfer_interval = strtoint (transfer_interval) * 1000;
		} catch (Invalid_Conversion e) {
			this->transfer_interval = this->interval;
		}
	}
	
	srand ((unsigned) time (0));
	this->setSleepTime (this->interval);

	pandoraLog ("Pandora agent started");
	
	/* Launch UDP Server */
	udp_server_enabled = conf->getValue ("udp_server");
	if (udp_server_enabled.compare ("1") == 0 && this->udp_server == NULL) {
		udp_server_port = conf->getValue ("udp_server_port");
		udp_server_addr = conf->getValue ("udp_server_address");
		udp_server_auth_addr = conf->getValue ("udp_server_auth_address");
		this->udp_server = new UDP_Server (this, udp_server_addr, udp_server_auth_addr, atoi (udp_server_port.c_str ()));
		((UDP_Server *)this->udp_server)->start ();
	}
}

string
Pandora_Windows_Service::getXmlHeader () {
	char          timestamp[20];
	string        agent_name, os_name, os_version, encoding, value, xml;
	time_t        ctime;
	struct tm     *ctime_tm = NULL;
	
	// Get agent name
	agent_name = conf->getValue ("agent_name");
	if (agent_name == "") {
		agent_name = Pandora_Windows_Info::getSystemName ();
	}
	
	// Get timestamp
	ctime = time(0);
	ctime_tm = localtime(&ctime);
	value = conf->getValue ("autotime");
	timestamp[0] = '\0';
	if (value != "1") {
		sprintf (timestamp, "%d-%02d-%02d %02d:%02d:%02d", ctime_tm->tm_year + 1900,
			ctime_tm->tm_mon + 1,	ctime_tm->tm_mday, ctime_tm->tm_hour,
			ctime_tm->tm_min, ctime_tm->tm_sec);
	}
	
	// Get OS name and version
	os_name = Pandora_Windows_Info::getOSName ();
	os_version = os_name + Pandora_Windows_Info::getOSVersion ();

	// Get encoding
	encoding = conf->getValue ("encoding");
	if (encoding == "") {
		encoding = "ISO-8859-1";
	}

	xml = "<?xml version=\"1.0\" encoding=\"" + encoding + "\" ?>\n" +
	      "<agent_data agent_name=\"" + agent_name +
	      "\" description=\"" + conf->getValue ("description") +
	      "\" version=\"" + getPandoraAgentVersion ();

	/* Skip the timestamp if autotime was enabled */
	if (timestamp[0] != '\0') {
		xml += "\" timestamp=\"";
		xml += timestamp; 
	}

	xml += "\" interval=\"" + conf->getValue ("interval") +
	       "\" os_name=\"" + os_name +
	       "\" os_version=\"" + os_version +
	       "\" group=\"" + conf->getValue ("group") + "\">\n";
	return xml;
}

int
Pandora_Windows_Service::copyTentacleDataFile (string host,
					       string filename,
					       string port,
					       string ssl,
					       string pass,
					       string opts)
{
	DWORD    rc;
	string  var, filepath;
	string	tentacle_cmd, working_dir;
	PROCESS_INFORMATION pi;
	STARTUPINFO         si;
	int tentacle_timeout = 0;

	var = conf->getValue ("temporal");
	if (var[var.length () - 1] != '\\') {
		var += "\\";
	}

	filepath = var + filename;
	
	/* Build the command to launch the Tentacle client */
	tentacle_cmd = "tentacle_client.exe -a " + host;

	if (port != "") {
		tentacle_cmd += " -p " + port;
	}

	if (ssl == "1") {
		tentacle_cmd += " -c";
	}

	if (pass != "") {
		tentacle_cmd += " -x " + pass;
	}

	if (opts != "") {
		tentacle_cmd += " " + opts;
	}

	tentacle_cmd += " \"" +  filepath + "\"";
	
	/* Copy the file */
	pandoraDebug ("Remote copying XML %s on server %s",
		      filepath.c_str (), host.c_str ());
	pandoraDebug ("Command %s", tentacle_cmd.c_str());

	ZeroMemory (&si, sizeof (si));
	ZeroMemory (&pi, sizeof (pi));
	if (CreateProcess (NULL , (CHAR *)tentacle_cmd.c_str (), NULL, NULL, FALSE,
	    CREATE_NO_WINDOW, NULL, NULL, &si, &pi) == 0) {
		return -1;
	}
	
	/* Timeout */
	tentacle_timeout = atoi (conf->getValue ("tentacle_timeout").c_str ());
	if (tentacle_timeout <= 0) {
		tentacle_timeout = INFINITE;
	} else {
		/* Convert to milliseconds */
		tentacle_timeout *= 1000;
	}

    if (WaitForSingleObject(pi.hProcess, tentacle_timeout) == WAIT_TIMEOUT) {
		TerminateProcess(pi.hThread, STILL_ACTIVE);
		CloseHandle (pi.hProcess);
		return -1;
	}

	/* Get the return code of the tentacle client*/	
    GetExitCodeProcess (pi.hProcess, &rc);
	if (rc != 0) {
		CloseHandle (pi.hProcess);
		return -1;
	}

	CloseHandle (pi.hProcess);
	return 0;
}

int
Pandora_Windows_Service::copyScpDataFile (string host,
					  string remote_path,
					  string filename)
{
	int rc = 0;
	SSH::Pandora_Ssh_Client ssh_client;
	string                  tmp_dir, filepath,port_str;
	string                  pubkey_file, privkey_file;
	int port;

	tmp_dir = conf->getValue ("temporal");
	if (tmp_dir[tmp_dir.length () - 1] != '\\') {
		tmp_dir += "\\";
	}
	filepath = tmp_dir + filename;

	pandoraDebug ("Connecting with %s", host.c_str ());

	pubkey_file   = Pandora::getPandoraInstallDir ();
	pubkey_file  += "key\\id_dsa.pub";
	privkey_file  = Pandora::getPandoraInstallDir ();
	privkey_file += "key\\id_dsa";
	
	port_str = conf->getValue ("server_port");
	if (port_str.length () == 0) {
		port = SSH_DEFAULT_PORT;
	} else {
		port = strtoint(port_str);
	}

	rc = ssh_client.connectWithPublicKey (host.c_str (), port, "pandora",
						 pubkey_file, privkey_file, "");
	if (rc == AUTHENTICATION_FAILED) {
		pandoraLog ("Pandora Agent: Authentication Failed "
			    "when connecting to %s",
			    host.c_str ());
		return rc;
	} else if (rc == PANDORA_EXCEPTION) {
		pandoraLog ("Pandora Agent: Failed when copying to %s",
			    host.c_str ());
		return rc;
	}

	pandoraDebug ("Remote copying XML %s on server %s at %s%s",
		      filepath.c_str (), host.c_str (),
		      remote_path.c_str (), filename.c_str ());
	
	rc = ssh_client.scpFileFilename (remote_path + filename,
					    filepath);
	if (rc == PANDORA_EXCEPTION) {
		pandoraLog ("Unable to copy at %s%s", remote_path.c_str (),
			    filename.c_str ());
		ssh_client.disconnect();
		return rc;
	}

	ssh_client.disconnect();
	return rc;
}

int
Pandora_Windows_Service::copyFtpDataFile (string host,
					  string remote_path,
					  string filename,
					  string password)
{
	int rc = 0;
	FTP::Pandora_Ftp_Client ftp_client;
	string                  filepath, port_str;
	int port;

	filepath = conf->getValue ("temporal");
	if (filepath[filepath.length () - 1] != '\\') {
		filepath += "\\";
	}
	filepath += filename;

	port_str = conf->getValue ("server_port");
	if (port_str.length () == 0) {
		port = FTP_DEFAULT_PORT;
	} else {
		port = strtoint(port_str);
	}

	ftp_client.connect (host,
			    port,
			    "pandora",
			    password);

	rc = ftp_client.ftpFileFilename (remote_path + filename,
					    filepath);
	if (rc == UNKNOWN_HOST) {
		pandoraLog ("Pandora Agent: Failed when copying to %s (%s)",
			    host.c_str (), ftp_client.getError ().c_str ());
		ftp_client.disconnect ();
		return rc;
	} else if (rc == AUTHENTICATION_FAILED) {
		pandoraLog ("Pandora Agent: Authentication Failed "
			    "when connecting to %s (%s)",
			    host.c_str (), ftp_client.getError ().c_str ());
		ftp_client.disconnect ();
		return rc;
	} else if (rc == FTP_EXCEPTION) {
		pandoraLog ("Pandora Agent: Failed when copying to %s (%s)",
			    host.c_str (), ftp_client.getError ().c_str ());
		ftp_client.disconnect ();
		return rc;
	}

	ftp_client.disconnect ();
	return rc;
}

int
Pandora_Windows_Service::copyDataFile (string filename)
{
	int rc = 0;
	unsigned char copy_to_secondary = 0;
	string mode, host, remote_path;

	mode = conf->getValue ("transfer_mode");
	host = conf->getValue ("server_ip");
	remote_path = conf->getValue ("server_path");
	// Fix remote path
	if (mode != "local" && remote_path[remote_path.length () - 1] != '/') {
		remote_path += "/";
	} else if (mode == "local" && remote_path[remote_path.length () - 1] != '\\') {
		remote_path += "\\";
	}

	if (mode == "ftp") {
		rc = copyFtpDataFile (host, remote_path, filename, conf->getValue ("server_pwd"));
	} else if (mode == "tentacle" || mode == "") {
		rc = copyTentacleDataFile (host, filename, conf->getValue ("server_port"),
			                      conf->getValue ("server_ssl"), conf->getValue ("server_pwd"),
			                      conf->getValue ("server_opts"));
	} else if (mode == "ssh") {
		rc =copyScpDataFile (host, remote_path, filename);
	} else if (mode == "local") {
		rc = copyLocalDataFile (remote_path, filename);
	} else {
		rc = PANDORA_EXCEPTION;
		pandoraLog ("Invalid transfer mode: %s."
			    "Please recheck transfer_mode option "
			    "in configuration file.");
	}

	if (rc == 0) {
		pandoraDebug ("Successfuly copied XML file to server.");
	} else if (conf->getValue ("secondary_mode") == "on_error") {
		copy_to_secondary = 1;
	}
	
	if (conf->getValue ("secondary_mode") == "always") {
		copy_to_secondary = 1;	
	}

	// Exit unless we have to send the file to a secondary server 
	if (copy_to_secondary == 0) {
		return rc;
	}
	
	// Read secondary server configuration
	mode = conf->getValue ("secondary_transfer_mode");
	host = conf->getValue ("secondary_server_ip");
	remote_path = conf->getValue ("secondary_server_path");

	// Fix remote path
	if (mode != "local" && remote_path[remote_path.length () - 1] != '/') {
		remote_path += "/";
	} else if (mode == "local" && remote_path[remote_path.length () - 1] != '\\') {
		remote_path += "\\";
	}

	// Send the file to the secondary server
	if (mode == "ftp") {
		rc = copyFtpDataFile (host, remote_path, filename, conf->getValue ("secondary_server_pwd"));
	} else if (mode == "tentacle" || mode == "") {
		rc = copyTentacleDataFile (host, filename, conf->getValue ("secondary_server_port"),
			                      conf->getValue ("secondary_server_ssl"), conf->getValue ("secondary_server_pwd"),
			                      conf->getValue ("secondary_server_opts"));
	} else if (mode == "ssh") {
		rc = copyScpDataFile (host, remote_path, filename);
	} else {
		rc = PANDORA_EXCEPTION;
		pandoraLog ("Invalid transfer mode: %s."
			    "Please recheck transfer_mode option "
			    "in configuration file.");
	}
	
	if (rc == 0) {
		pandoraDebug ("Successfuly copied XML file to secondary server.");
	}
	
	return rc;
}

void
Pandora_Windows_Service::recvTentacleDataFile (string host,
					       string filename)
{
	int     rc;
	string  var;
	string	tentacle_cmd;

	/* Change directory to "temporal" */
	var = conf->getValue ("temporal");
	if (_chdir(var.c_str()) != 0) {
		pandoraDebug ("Error changing directory to %s", var.c_str());
		throw Pandora_Exception ();
	}

	/* Build the command to launch the Tentacle client */
	tentacle_cmd = "tentacle_client.exe -g -a " + host;

	var = conf->getValue ("server_port");
	if (var != "") {
		tentacle_cmd += " -p " + var;
	}

	var = conf->getValue ("server_ssl");	
	if (var == "1") {
		tentacle_cmd += " -c";
	}

	var = conf->getValue ("server_pwd");
	if (var != "") {
		tentacle_cmd += " -x " + var;
	}

	var = conf->getValue ("server_opts");
	if (var != "") {
		tentacle_cmd += " " + var;
	}

	tentacle_cmd += " " +  filename;

	/* Copy the file */
	pandoraDebug ("Requesting file %s from server %s",
		      filename.c_str (), host.c_str ());
	pandoraDebug ("Command %s", tentacle_cmd.c_str());

	rc = system (tentacle_cmd.c_str());
	switch (rc) {
	
		/* system() error */
	case -1:
		pandoraLog ("Unable to receive file %s", filename.c_str ());
		throw Pandora_Exception ();
	
		/* tentacle_client.exe returned OK */
	case 0:
		break;
	
		/* tentacle_client.exe error */
	default:
		pandoraDebug ("Tentacle client was unable to receive file %s",
			      filename.c_str ());
		throw Pandora_Exception ();
	}

	return;
}

void
Pandora_Windows_Service::recvDataFile (string filename) {
	string mode, host, remote_path;

	mode = conf->getValue ("transfer_mode");
	host = conf->getValue ("server_ip");
	remote_path = conf->getValue ("server_path");
	if (remote_path[remote_path.length () - 1] != '/') {
		remote_path += "/";
	}

	try {
		if (mode == "tentacle") {
			recvTentacleDataFile (host, filename);
		} else {
			pandoraLog ("Transfer mode %s does not support file retrieval.", mode.c_str () );
			throw Pandora_Exception ();
		}
	}
	catch (Pandora_Exception e) {
		throw e;
	}
}

int
Pandora_Windows_Service::copyLocalDataFile (string remote_path,
					  string filename)
{
	string local_path, local_file, remote_file;
	local_path = conf->getValue ("temporal");
	if (local_path[local_path.length () - 1] != '\\') {
		local_path += "\\";
	}

	local_file = local_path + filename;
	remote_file = remote_path + filename;
	if (!CopyFile (local_file.c_str (), remote_file.c_str (), TRUE)) {
        return PANDORA_EXCEPTION;
    }
}

int
Pandora_Windows_Service::unzipCollection(string zip_path, string dest_dir) {
	string	unzip_cmd, dest_cmd;
	PROCESS_INFORMATION pi;
	STARTUPINFO         si;
	mode_t mode;
	DWORD rc;
	
	/*Delete dest directory*/
	Pandora_File::removeDir(dest_dir);

	/* Build the command to create destination diectory*/
	rc = mkdir (dest_dir.c_str());
	
	if (rc != 0) {
		pandoraLog ("Pandora_Windows_Service::unzipCollection: Can not create dir %s", dest_dir.c_str());
		return -1;
	}
	
	/* Build the command to launch the Tentacle client */
	unzip_cmd = "unzip.exe \"" + zip_path + "\" -d \"" + dest_dir + "\"";
	
	ZeroMemory (&si, sizeof (si));
	ZeroMemory (&pi, sizeof (pi));
	if (CreateProcess (NULL , (CHAR *)unzip_cmd.c_str (), NULL, NULL, FALSE,
	    CREATE_NO_WINDOW, NULL, NULL, &si, &pi) == 0) {
		return -1;
	}

	/* Get the return code of the tentacle client*/
    WaitForSingleObject(pi.hProcess, INFINITE);
    GetExitCodeProcess (pi.hProcess, &rc);
    
	if (rc != 0) {
		CloseHandle (pi.hProcess);
		pandoraLog ("Pandora_Windows_Service::unzipCollection: Can not unzip file %s", zip_path.c_str());
		return -1;
	}

	CloseHandle (pi.hProcess);
	return 0;	
}
/*
 * Check the disk for collections installed
 */

void
Pandora_Windows_Service::purgeDiskCollections () {
	
	DIR *dir;
	struct dirent *dir_content;
	struct stat file;
	string tmp, filepath;
	
	filepath = Pandora::getPandoraInstallDir() +"collections\\";
	/*Open the directory*/
	dir = opendir (filepath.c_str ());

	/*Read the directory looking for files and folders*/
	dir_content = readdir(dir);
	
	while (dir_content != NULL) {
				
		stat(tmp.c_str(),&file);
		
		/*If is a folder, check for . and .. */
		if ( (strcmp(dir_content->d_name,".") != 0) && (strcmp(dir_content->d_name,"..") != 0) ) {
			/*If the file is not in collection list, delete the file*/
			if(! conf->isInCollectionList(dir_content->d_name) ) {
				tmp = filepath+dir_content->d_name;
				Pandora_File::removeDir(tmp);
			}
		}

		/*Next item*/
		dir_content = readdir(dir);	
	}
	
	/*Close dir oppened*/
	closedir(dir);	
}

/*
 * Check collections to sync it between server and agent
 */
void
Pandora_Windows_Service::checkCollections () {
	
	int flag, i;
	char *coll_md5 = NULL, *server_coll_md5 = NULL;
	string collection_name, collections_dir, collection_md5, tmp;
	string collection_zip, install_dir, temp_dir, dest_dir, path, env;

	/*Get collections directory*/
	install_dir = Pandora::getPandoraInstallDir ();
	collections_dir = install_dir+"collections\\";
	
	/* Get temporal directory */
	temp_dir = conf->getValue ("temporal");
	if (temp_dir[temp_dir.length () - 1] != '\\') {
		temp_dir += "\\";
	}

	/*Set iterator in the firs collection*/
	conf->goFirstCollection();

	while (! conf->isLastCollection()) {
		
		collection_name = conf->getCurrentCollectionName();	

		if(! conf->getCurrentCollectionVerify() ) {	
			/*Add the collection directory to the path*/
			tmp = collections_dir + collection_name;
			path = getenv ("PATH");
			env = "PATH=" + path + ";" + tmp;
			putenv (env.c_str ());
			conf->setCurrentCollectionVerify();

		}
		
		collection_zip = collection_name+".zip";
		collection_md5 = collection_name + ".md5";
		tmp = collections_dir+collection_md5;
			
		/*Reading local collection md5*/
		try {
			if (Pandora_File::readBinFile (tmp, &coll_md5) < 32) {
				pandoraDebug ("Pandora_Windows_Service::checkCollection: Invalid local md5", tmp.c_str());
				if (coll_md5 != NULL) {
					delete[] coll_md5;
				}		
				/*Go to next collection*/		
				conf->goNextCollection();
				continue;
			}
		} catch (...) {
			/*Getting new md5*/
			try {				
				/*Downloading md5 file*/
				recvDataFile (collection_md5);
				
				/*Reading new md5 file*/
				tmp = temp_dir + collection_md5;
				
				if (Pandora_File::readBinFile (tmp, &coll_md5) < 32) {
					pandoraDebug ("Pandora_Windows_Service::checkCollection: Invalid remote md5", tmp.c_str());
					if (coll_md5 != NULL) {
						delete[] coll_md5;
					}
							
					Pandora_File::removeFile (tmp);
					/*Go to next collection*/		
					conf->goNextCollection();
					continue;
				}
				
				Pandora_File::removeFile (tmp);
				
				/* Save new md5 file */
				tmp = collections_dir + collection_md5;
				Pandora_File::writeBinFile (tmp, coll_md5, 32);
				
			} catch(...) {
				pandoraDebug ("Pandora_Windows_Service::checkCollection: Can not download %s", collection_md5.c_str());
				/*Go to next collection*/		
				conf->goNextCollection();
				continue;
			}
			
			/*Getting new zipped collection*/
			try {
				/*Downloading zipped collection*/
				recvDataFile (collection_zip);
				
				/*Uncompress zipped collection*/
				tmp = temp_dir + collection_zip;
				dest_dir = collections_dir + collection_name;
				
				try {
					unzipCollection(tmp,dest_dir);
				} catch (...) {
					Pandora_File::removeFile (tmp);	
					/*Go to next collection*/		
					conf->goNextCollection();
					continue;					
				}
				
				Pandora_File::removeFile (tmp);	
			} catch (...) {
				pandoraDebug ("Pandora_Windows_Service::checkCollection: Can not download %s", collection_zip.c_str());
								
				/*Go to next collection*/		
				conf->goNextCollection();
				continue;
			}
			
			conf->goNextCollection();		
			continue;
		}
		
		/*Reading server collection md5*/
		try {
			
			recvDataFile(collection_md5);
			tmp = temp_dir+collection_md5;
			if (Pandora_File::readBinFile (tmp, &server_coll_md5) < 32) {
				pandoraDebug ("Pandora_Windows_Service::checkCollection: Invalid remote md5", tmp.c_str());
				if (server_coll_md5 != NULL) {
					delete[] server_coll_md5;
				}		
				Pandora_File::removeFile (tmp);	
				/*Go to next collection*/		
				conf->goNextCollection();
				continue;		
			}
			Pandora_File::removeFile (tmp);	
			
		} catch (...) {
			pandoraDebug ("Pandora_Windows_Service::checkCollection: Can not download %s", collection_md5.c_str());
			/*Go to next collection*/		
			conf->goNextCollection();
			continue;		
		}
		
		/*Check both md5*/
		flag = 0;
		for (i = 0; i < 32; i++) {
			if (coll_md5[i] != server_coll_md5[i]) {
				flag = 1;
				break;
			}
		}
		
		/*If the two md5 are equals, exit*/
		if (flag == 0) {
			/*Go to next collection*/		
			conf->goNextCollection();
			continue;
		}
		
		pandoraDebug ("Pandora_Windows_Service::checkCollections: Collection %s has changed", collection_md5.c_str ());
					
		/*Getting new zipped collection*/
		try {
			/*Downloading zipped collection*/
			recvDataFile (collection_zip);
			
			/*Uncompress zipped collection*/
			tmp = temp_dir + collection_zip;
			dest_dir = collections_dir + collection_name;
			
			try {
				unzipCollection(tmp,dest_dir);
			} catch (...) {
				Pandora_File::removeFile (tmp);	
				/*Go to next collection*/		
				conf->goNextCollection();
				continue;					
			}
			
			Pandora_File::removeFile (tmp);	
				
		} catch (...) {
			pandoraDebug ("Pandora_Windows_Service::checkCollection: Can not download %s", collection_zip.c_str());
			
			/*Go to next collection*/		
			conf->goNextCollection();
			continue;	
		}
		
		/* Save new md5 file */
		tmp = collections_dir + collection_md5;
		Pandora_File::writeBinFile (tmp, server_coll_md5, 32);
		
		/*Free coll_md5*/
		if (coll_md5 != NULL) {
			delete[] coll_md5;
		}
		
		/*Free server_coll_md5*/
		if (server_coll_md5 != NULL) {
			delete[] server_coll_md5;
		}
		
		/*Go to next collection*/		
		conf->goNextCollection();
	}
	purgeDiskCollections ();
}

void
Pandora_Windows_Service::checkConfig () {
	int i, conf_size;
	char *conf_str = NULL, *remote_conf_str = NULL, *remote_conf_md5 = NULL;
	char agent_md5[33], conf_md5[33], flag;
	string agent_name, conf_file, conf_tmp_file, md5_tmp_file, temp_dir, tmp;

	tmp = conf->getValue ("remote_config");
	if (tmp != "1") {
		pandoraDebug ("Pandora_Windows_Service::checkConfig: Remote configuration disabled");
		return;
	}

	/* Get temporal directory */
	temp_dir = conf->getValue ("temporal");
	if (temp_dir[temp_dir.length () - 1] != '\\') {
		temp_dir += "\\";
	}

	/* Get base install directory */
	conf_file = Pandora::getPandoraInstallDir ();
	conf_file += "pandora_agent.conf";

	/* Get agent name */
	tmp = conf->getValue ("agent_name");
	if (tmp.empty ()) {
		tmp = Pandora_Windows_Info::getSystemName ();
	}
	agent_name = tmp;
	
	/* Error getting agent name */
	if (tmp.empty ()) {
		pandoraDebug ("Pandora_Windows_Service::checkConfig: Error getting agent name");
		return;
	}

	Pandora_File::md5 (tmp.c_str(), tmp.size(), agent_md5);

	/* Calculate md5 hashes */
	try {
		conf_size = Pandora_File::readBinFile (conf_file, &conf_str);
		Pandora_File::md5 (conf_str, conf_size, conf_md5);
	} catch (...) {
		pandoraDebug ("Pandora_Windows_Service::checkConfig: Error calculating configuration md5");
		if (conf_str != NULL) {
			delete[] conf_str;
		}
		return;
	}

	/* Compose file names from the agent name hash */
	conf_tmp_file = agent_md5;
	conf_tmp_file += ".conf";
	md5_tmp_file = agent_md5;
	md5_tmp_file += ".md5";

	/* Get md5 file from server */
	try {
		recvDataFile (md5_tmp_file);
	} catch (...) {
		/* Not found, upload the configuration */
		try {
			tmp = temp_dir;
			tmp += conf_tmp_file;
			Pandora_File::writeBinFile (tmp, conf_str, conf_size);
			copyDataFile (conf_tmp_file);
			Pandora_File::removeFile (tmp);
		
			tmp = temp_dir;
			tmp += md5_tmp_file;
			Pandora_File::writeBinFile (tmp, conf_md5, 32);
			copyDataFile (md5_tmp_file);
			Pandora_File::removeFile (tmp);
		} catch (...) {
			pandoraDebug ("Pandora_Windows_Service::checkConfig: Error uploading configuration to server");
		}
	
		delete[] conf_str;
		return;
	}

	delete[] conf_str;
	conf_str = NULL;

	/* Read remote configuration file md5 */
	try {
		tmp = temp_dir;
		tmp += md5_tmp_file;
		if (Pandora_File::readBinFile (tmp, &remote_conf_md5) < 32) {
			pandoraDebug ("Pandora_Windows_Service::checkConfig: Invalid remote md5", tmp.c_str());
			if (remote_conf_md5 != NULL) {
				delete[] remote_conf_md5;
			}		
			return;		   	
		}
		Pandora_File::removeFile (tmp);
	} catch (...) {
		pandoraDebug ("Pandora_Windows_Service::checkConfig: Error checking remote configuration md5", tmp.c_str());
		return;
	}

	/* Check for configuration changes */
	flag = 0;
	for (i = 0; i < 32; i++) {
		if (remote_conf_md5[i] != conf_md5[i]) {
			flag = 1;
			break;
		}
	}

	delete[] remote_conf_md5;

	/* Configuration has not changed */
	if (flag == 0) {
		return;
	}

	pandoraLog("Pandora_Windows_Service::checkConfig: Configuration for agent %s has changed", agent_name.c_str ());

	/* Get configuration file from server */
	try {
		recvDataFile (conf_tmp_file);
		tmp = temp_dir;
		tmp += conf_tmp_file;
		conf_size = Pandora_File::readBinFile (tmp, &conf_str);
		Pandora_File::removeFile (tmp);
		/* Save new configuration */
		Pandora_File::writeBinFile (conf_file, conf_str, conf_size);
	} catch (...) {
		pandoraDebug("Pandora_Windows_Service::checkConfig: Error retrieving configuration file from server");
		if (conf_str != NULL) {
			delete[] conf_str;
		}
		return;
	}

	delete[] conf_str;

	/* Reload configuration */
	this->pandora_init ();
}

int
Pandora_Windows_Service::sendXml (Pandora_Module_List *modules) {
    int rc = 0, xml_buffer;
    string            data_xml;
	string            xml_filename, random_integer;
	string            tmp_filename, tmp_filepath;
	string            encoding;
	static HANDLE     mutex = 0; 
    ULARGE_INTEGER    free_bytes;
    double            min_free_bytes = 0;
	Pandora_Agent_Conf *conf = NULL;
	FILE              *conf_fh = NULL;

	conf = this->getConf ();
	min_free_bytes = 1024 * atoi (conf->getValue ("temporal_min_size").c_str ());
	xml_buffer = atoi (conf->getValue ("xml_buffer").c_str ());
	
	if (mutex == 0) {
		mutex = CreateMutex (NULL, FALSE, NULL);
	}
	
	/* Wait for the mutex to be opened */
	WaitForSingleObject (mutex, INFINITE);
	
	data_xml = getXmlHeader ();
	
	/* Write module data */
	if (modules != NULL) {
		modules->goFirst ();
	
		while (! modules->isLast ()) {
			Pandora_Module *module;
			
			module = modules->getCurrentValue ();			
			data_xml += module->getXml ();
			modules->goNext ();
		}
	}
	
	/* Close the XML header */
	data_xml += "</agent_data>";
	
	/* Generate temporal filename */
	random_integer = inttostr (rand());
	tmp_filename = conf->getValue ("agent_name");
	if (tmp_filename == "") {
		tmp_filename = Pandora_Windows_Info::getSystemName ();
	}
	tmp_filename += "." + random_integer + ".data";

	xml_filename = conf->getValue ("temporal");
	if (xml_filename[xml_filename.length () - 1] != '\\') {
		xml_filename += "\\";
	}
	tmp_filepath = xml_filename + tmp_filename;

	/* Copy the XML to temporal file */
	pandoraDebug ("Copying XML on %s", tmp_filepath.c_str ());
	conf_fh = fopen (tmp_filepath.c_str (), "w");
	if (conf_fh == NULL) {
		pandoraLog ("Error when saving the XML in %s",
			    tmp_filepath.c_str ());
		ReleaseMutex (mutex);
		return PANDORA_EXCEPTION;
	}
	fprintf (conf_fh, "%s", data_xml.c_str ());
	fclose (conf_fh);

	/* Only send if debug is not activated */
	if (getPandoraDebug () == false) {
		rc = this->copyDataFile (tmp_filename);
        
		/* Delete the file if successfully copied, buffer disabled or not enough space available */
		if (rc == 0 || xml_buffer == 0 || (GetDiskFreeSpaceEx (tmp_filepath.c_str (), &free_bytes, NULL, NULL) != 0 && free_bytes.QuadPart < min_free_bytes)) {
			Pandora_File::removeFile (tmp_filepath);
		}

		/* Send any buffered data files */
		if (xml_buffer == 1) {
			this->sendBufferedXml (conf->getValue ("temporal"));
		}
	}

	ReleaseMutex (mutex);
}

void
Pandora_Windows_Service::sendBufferedXml (string path) {
    string base_path = path, file_path;
    WIN32_FIND_DATA file_data;
    HANDLE find;

	if (base_path[base_path.length () - 1] != '\\') {
		base_path += "\\";
	}
    file_path = base_path + "*.data";
    
    /* Search for buffered data files */
    find = FindFirstFile(file_path.c_str (), &file_data);
    if (find == INVALID_HANDLE_VALUE) {
        return;
    }

    /* Send data files as long as there are no errors */
    if (this->copyDataFile (file_data.cFileName) != 0) {
        FindClose(find);
        return;
    }
    Pandora_File::removeFile (base_path + file_data.cFileName);

    while (FindNextFile(find, &file_data) != 0) {
        if (this->copyDataFile (file_data.cFileName) != 0) {
            FindClose(find);
            return;
        }
        Pandora_File::removeFile (base_path + file_data.cFileName);
    }

    FindClose(find);
}

void
Pandora_Windows_Service::pandora_run () {
	Pandora_Agent_Conf  *conf = NULL;
	string server_addr;
    	int startup_delay = 0;
    	static unsigned char delayed = 0;

	pandoraDebug ("Run begin");
	
	conf = this->getConf ();

 	/* Sleep if a startup delay was specified */
 	startup_delay = atoi (conf->getValue ("startup_delay").c_str ()) * 1000;
 	if (startup_delay > 0 && delayed == 0) {
		delayed = 1;
        	pandoraLog ("Delaying startup %d miliseconds", startup_delay);
        	Sleep (startup_delay);
    	}

	/* Check for configuration changes */
	if (getPandoraDebug () == false) {
		this->checkConfig ();
		this->checkCollections ();
	}

	server_addr = conf->getValue ("server_ip");

	execution_number++;

	if (this->modules != NULL) {
		this->modules->goFirst ();
	
		while (! this->modules->isLast ()) {
			Pandora_Module *module;
		
			module = this->modules->getCurrentValue ();
		
			pandoraDebug ("Run %s", module->getName ().c_str ());

			if (module->checkCron () == 1) {
				module->run ();
			}
			
			/* Save module data to an environment variable */
			if (!module->getSave().empty ()) {
				module->exportDataOutput ();
			}

			/* Evaluate module conditions */
			module->evaluateConditions ();

			this->modules->goNext ();
		}
	}


	this->elapsed_transfer_time += this->interval;
	
	if (this->elapsed_transfer_time >= this->transfer_interval) {
		this->elapsed_transfer_time = 0;
		if (!server_addr.empty ()) {
		  this->sendXml (this->modules);
		}
	}
	
	/* Get the interval value (in minutes) */
	pandoraDebug ("Next execution on %d seconds", this->interval / 1000);

	return;
}

Pandora_Agent_Conf  *
Pandora_Windows_Service::getConf () {
	return this->conf;
}
