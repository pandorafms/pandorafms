/* Pandora agents service for Win32.
   
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
        execution_number = 0;
        this->modules    = NULL;
        this->conf       = NULL;
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
        int    interval_ms = 60000;
        string conf_file, interval, debug;
        
        setPandoraDebug (true);
        
        conf_file = Pandora::getPandoraInstallDir ();
        conf_file += "pandora_agent.conf";
        
        this->conf = new Pandora::Pandora_Agent_Conf (conf_file);
        this->modules = new Pandora_Module_List (conf_file);

        /* Get the interval value (in minutes) and set it to the service */
        interval = conf->getValue ("interval");

	debug = conf->getValue ("debug");
        setPandoraDebug (is_enabled (debug));
	
        if (interval != "") {
		try {
			/* miliseconds */
			interval_ms = strtoint (interval) * 1000;
		} catch (Invalid_Conversion e) {
		}
        }
	
        srand ((unsigned) time (0));
        this->setSleepTime (interval_ms);
        
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
	int                     rc;
	string                  var, filepath;
	string                  pubkey_file, privkey_file;
	string			        tentacle_cmd;

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

	password = conf->getValue ("ftp_password");
	
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
Pandora_Windows_Service::pandora_run () {
        TiXmlDocument *doc;
        TiXmlElement  *local_xml, *agent;
        string         xml_filename, random_integer;
	string         tmp_filename, tmp_filepath, interval;
        bool           saved;
        
        pandoraDebug ("Run begin");
        
        agent = getXmlHeader ();
	
        execution_number++;
        
        if (this->modules != NULL) {
                this->modules->goFirst ();
                
                while (! this->modules->isLast ()) {
                        Pandora_Module *module;
                        string          result;
                        
                        module = this->modules->getCurrentValue ();
                        
                        pandoraDebug ("Run %s", module->getName ().c_str ());
                        module->run ();
                        
                        local_xml = module->getXml ();
                        if (local_xml != NULL) {
                                agent->InsertEndChild (*local_xml);
        			
                                delete local_xml;
                        }
                        this->modules->goNext ();
                }
        }
	
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

        /* Copy the XML to a temporal file */
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

	if (getPandoraDebug () == false) {
		this->copyDataFile (tmp_filename);

		try {
	                Pandora_File::removeFile (tmp_filepath);
	        } catch (Pandora_File::Delete_Error e) {
	        }
	}

	/* Get the interval value (in minutes) */
        interval = conf->getValue ("interval");
        pandoraDebug ("Next execution on %s seconds", interval.c_str ());
        
        return;
}
