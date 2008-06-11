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

#include <iostream>
#include <cstdlib>
#include <ctime>
#include <direct.h>

using namespace std;
using namespace Pandora;
using namespace Pandora_Modules;
using namespace Pandora_Strutils;

string enabled_values[] = {"enabled", "1", "on", "yes", "si", "sí", "ok", ""};

/** 
 * Creates a new Pandora_Windows_Service.
 * 
 * @param svc_name Internal service name
 * @param svc_display_name Service name that will appear in the
 *        Windows service administration tool.
 * @param svc_description Long description of the service.
 */
Pandora_Windows_Service::Pandora_Windows_Service (const char * svc_name,
						  const char * svc_display_name,
						  const char * svc_description)
        : Windows_Service (svc_name, svc_display_name, svc_description) {

        this->setInitFunction ((void (Windows_Service::*) ())
			       &Pandora_Windows_Service::pandora_init);
        this->setRunFunction ((void (Windows_Service::*) ())
			      &Pandora_Windows_Service::pandora_run);
	
        execution_number            = 0;
        this->modules               = NULL;
        this->conf                  = NULL;
	this->interval              = 60000;
	this->transfer_interval     = this->interval;
	this->elapsed_transfer_time = 0;
}

/** 
 * Destroys a Pandora_Windows_Service object.
 */
Pandora_Windows_Service::~Pandora_Windows_Service () {
        if (this->conf != NULL) {
                delete this->conf;
        }
	
        if (this->modules != NULL) {
                delete this->modules;
        }
	pandoraLog ("Pandora agent stopped");
}

bool
is_enabled (string value) {
        int i = 0;

        if (value == "") {
                return false;
        }

        while (enabled_values[i] != "") {
                if (enabled_values[i] == value) {
                        return true;
                }
                i++;
        }
        return false;
}

void
Pandora_Windows_Service::pandora_init () {
        string conf_file, interval, debug, transfer_interval;
        
        setPandoraDebug (true);
        
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
}

TiXmlElement *
Pandora_Windows_Service::getXmlHeader () {
        TiXmlElement *agent;
        SYSTEMTIME    st;
        char          timestamp[20];
        string        value;
        
        agent = new TiXmlElement ("agent_data");

        value = conf->getValue ("agent_name");
        if (value == "") {
                value = Pandora_Windows_Info::getSystemName ();
        }
        agent->SetAttribute ("agent_name", value);
        
        agent->SetAttribute ("version", getPandoraAgentVersion ());

        GetSystemTime(&st);
        sprintf (timestamp, "%d-%02d-%02d %02d:%02d:%02d", st.wYear, st.wMonth, st.wDay,
                 st.wHour, st.wMinute, st.wSecond);
	
        agent->SetAttribute ("timestamp", timestamp);

	value = conf->getValue ("interval");
        agent->SetAttribute ("interval", value);
	
	value = Pandora_Windows_Info::getOSName ();
        agent->SetAttribute ("os", value);
	
	value = Pandora_Windows_Info::getOSVersion ();
        agent->SetAttribute ("os_version", value);

        return agent;
}

void
Pandora_Windows_Service::copyTentacleDataFile (string host,
					  string filename)
{
	int     rc;
	string  var, filepath;
	string	tentacle_cmd;

	var = conf->getValue ("temporal");
	if (var[var.length () - 1] != '\\') {
		var += "\\";
	}

        filepath = var + filename;

	/* Build the command to launch the Tentacle client */
	tentacle_cmd = "tentacle_client.exe -a " + host;
    
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

	tentacle_cmd += " " +  filepath;

	/* Copy the file */
	pandoraDebug ("Remote copying XML %s on server %s",
	               filepath.c_str (), host.c_str ());
	pandoraDebug ("Command %s", tentacle_cmd.c_str());
	
	rc = system (tentacle_cmd.c_str());
	switch (rc) {

		/* system() error */
		case -1:
			pandoraLog ("Unable to copy %s", filename.c_str ());
			throw Pandora_Exception ();

		/* tentacle_client.exe returned OK */
		case 0:
			break;

		/* tentacle_client.exe error */
		default:
			pandoraLog ("Tentacle client was unable to copy %s",
			             filename.c_str ());
			throw Pandora_Exception ();
	}

	return;
}

void
Pandora_Windows_Service::copyScpDataFile (string host,
					  string remote_path,
					  string filename)
{
	SSH::Pandora_Ssh_Client ssh_client;
	string                  tmp_dir, filepath;
	string                  pubkey_file, privkey_file;

	tmp_dir = conf->getValue ("temporal");
        if (tmp_dir[tmp_dir.length () - 1] != '\\') {
                tmp_dir += "\\";
        }
        filepath = tmp_dir + filename;
	
	pandoraDebug ("Connecting with %s", host.c_str ());
        
        try {
                pubkey_file   = Pandora::getPandoraInstallDir ();
                pubkey_file  += "key\\id_dsa.pub";
                privkey_file  = Pandora::getPandoraInstallDir ();
                privkey_file += "key\\id_dsa";
		
                ssh_client.connectWithPublicKey (host.c_str (), 22, "pandora",
						 pubkey_file, privkey_file, "");
        } catch (SSH::Authentication_Failed e) {
                pandoraLog ("Pandora Agent: Authentication Failed "
			    "when connecting to %s",
                            host.c_str ());
		throw e;
        } catch (Pandora_Exception e) {
                pandoraLog ("Pandora Agent: Failed when copying to %s",
                            host.c_str ());
		throw e;
        }
        
        pandoraDebug ("Remote copying XML %s on server %s at %s%s",
                      filepath.c_str (), host.c_str (),
                      remote_path.c_str (), filename.c_str ());
        try {
                ssh_client.scpFileFilename (remote_path + filename,
					    filepath);
        } catch (Pandora_Exception e) {
                pandoraLog ("Unable to copy at %s%s", remote_path.c_str (),
                            filename.c_str ());
                ssh_client.disconnect();

		throw e;
        }
        
        ssh_client.disconnect();
}

void
Pandora_Windows_Service::copyFtpDataFile (string host,
					  string remote_path,
					  string filename)
{
	FTP::Pandora_Ftp_Client ftp_client;
	string                  filepath;
	string                  password;
	
	filepath = conf->getValue ("temporal");
        if (filepath[filepath.length () - 1] != '\\') {
                filepath += "\\";
        }
        filepath += filename;

	password = conf->getValue ("server_pwd");
	
	ftp_client.connect (host,
			    22,
			    "pandora",
			    password);

	try {
		ftp_client.ftpFileFilename (remote_path + filename,
					    filepath);
	} catch (FTP::Unknown_Host e) {
		pandoraLog ("Pandora Agent: Failed when copying to %s (%s)",
			    host.c_str (), ftp_client.getError ().c_str ());
		ftp_client.disconnect ();
		throw e;
	} catch (FTP::Authentication_Failed e) {
		pandoraLog ("Pandora Agent: Authentication Failed "
			    "when connecting to %s (%s)",
                            host.c_str (), ftp_client.getError ().c_str ());
		ftp_client.disconnect ();
		throw e;
	} catch (FTP::FTP_Exception e) {
		pandoraLog ("Pandora Agent: Failed when copying to %s (%s)",
                            host.c_str (), ftp_client.getError ().c_str ());
		ftp_client.disconnect ();
		throw e;
	}
	
	ftp_client.disconnect ();
}

void
Pandora_Windows_Service::copyDataFile (string filename)
{
	string mode, host, remote_path;
	
	mode = conf->getValue ("transfer_mode");
	host = conf->getValue ("server_ip");
	remote_path = conf->getValue ("server_path");
        if (remote_path[remote_path.length () - 1] != '/') {
                remote_path += "/";
        }
        
	try {
		if (mode == "ftp") {
			copyFtpDataFile (host, remote_path, filename);
		} else if (mode == "tentacle") {
			copyTentacleDataFile (host, filename);
		} else if (mode == "ssh" || mode == "") {
			copyScpDataFile (host, remote_path, filename);
		} else {
			pandoraLog ("Invalid transfer mode: %s."
				    "Please recheck transfer_mode option "
				    "in configuration file.");
		}

		pandoraDebug ("Successfuly copied XML file to server.");
	} catch (Pandora_Exception e) {
	}
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
			pandoraLog ("Tentacle client was unable to receive file %s",
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
			pandoraLog ("Transfer mode %s does not support file retrieval.");
			throw Pandora_Exception ();
		}
	}
	catch (Pandora_Exception e) {
		throw e;
	}
}

void
Pandora_Windows_Service::checkConfig () {
	int i, conf_size;
	char *conf_str = NULL, *remote_conf_str = NULL, *remote_conf_md5 = NULL;
    char agent_md5[33], conf_md5[33], flag;
	string conf_file, conf_tmp_file, md5_tmp_file, temp_dir, tmp;

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
	if (tmp == "") {
		tmp = Pandora_Windows_Info::getSystemName ();
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
	
	pandoraLog("Pandora_Windows_Service::checkConfig: Configuration has changed");
		
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

void
Pandora_Windows_Service::pandora_run () {
        TiXmlDocument *doc;
        TiXmlElement  *local_xml, *agent;
        string         xml_filename, random_integer;
	string         tmp_filename, tmp_filepath;
        bool           saved;
        
        pandoraDebug ("Run begin");
        
        /* Check for configuration changes */
        this->checkConfig ();
        
        execution_number++;
	
        if (this->modules != NULL) {
                this->modules->goFirst ();
                
                while (! this->modules->isLast ()) {
                        Pandora_Module *module;
                        
                        module = this->modules->getCurrentValue ();
                        
                        pandoraDebug ("Run %s", module->getName ().c_str ());
                        module->run ();
			
                        this->modules->goNext ();
                }
        }

        this->elapsed_transfer_time += interval;

	if (this->elapsed_transfer_time >= this->transfer_interval) {
		agent = getXmlHeader ();
		
		if (this->modules != NULL) {
			this->modules->goFirst ();
			
			while (! this->modules->isLast ()) {
				Pandora_Module *module;
				
				module = this->modules->getCurrentValue ();
				
				local_xml = module->getXml ();
				if (local_xml != NULL) {
					agent->InsertEndChild (*local_xml);
					
					delete local_xml;
				}
				this->modules->goNext ();
			}
		}
		
		this->elapsed_transfer_time = 0;
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
		doc = new TiXmlDocument (tmp_filepath);
		doc->InsertEndChild (*agent);
		saved = doc->SaveFile();
		delete doc;
		delete agent;
	
		if (!saved) {
			pandoraLog ("Error when saving the XML in %s",
				    tmp_filepath.c_str ());
			return;
		}

		/* Only send if debug is not activated */
		if (getPandoraDebug () == false) {
			this->copyDataFile (tmp_filename);

			try {
				Pandora_File::removeFile (tmp_filepath);
			} catch (Pandora_File::Delete_Error e) {
			}
		}
	}

	/* Get the interval value (in minutes) */
        pandoraDebug ("Next execution on %d seconds", this->interval / 1000);
        
        return;
}
