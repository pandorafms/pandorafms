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

Pandora_Windows_Service::Pandora_Windows_Service (const char * svc_name,
                                              const char * svc_display_name,
                                              const char * svc_description)
        : Windows_Service (svc_name, svc_display_name, svc_description) {

        this->setInitFunction ((void (Windows_Service::*) ()) &Pandora_Windows_Service::pandora_init);
        this->setRunFunction ((void (Windows_Service::*) ()) &Pandora_Windows_Service::pandora_run);
        execution_number = 0;
        this->modules    = NULL;
        this->conf       = NULL;
}

Pandora_Windows_Service::~Pandora_Windows_Service () {
        if (this->conf != NULL) {
                delete this->conf;
        }
	
        if (this->modules != NULL) {
                delete this->modules;
        }
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
        this->conf = new Pandora_Agent_Conf (conf_file);
        this->modules = new Pandora_Module_List (conf_file);

        /* Get the interval value (in minutes) and set it to the service */
        interval = conf->getValue ("interval");

	debug = conf->getValue ("pandora_debug");
        setPandoraDebug (is_enabled (debug));
	
        if (interval != "") {
		try {
			interval_ms = strtoint (interval)
				* 1000 /* miliseconds */;
		} catch (Invalid_Conversion e) {
		}
        }
	
        srand ((unsigned) time (0));
        this->setSleepTime (interval_ms);
        
        pandoraDebug ("Init end");
}

TiXmlElement *
Pandora_Windows_Service::getXmlHeader () {
        TiXmlElement *agent;
        SYSTEMTIME    st;
        char          timestamp[20];
        string        value;
        
        agent = new TiXmlElement ("agent_data");

        value = conf->getValue ("agent_name");
        agent->SetAttribute ("agent_name", value);
        if (value == "") {
                value = Pandora_Windows_Info::getSystemName ();
        }
        
        agent->SetAttribute ("version", getPandoraAgentVersion ());

        GetSystemTime(&st);
        sprintf (timestamp, "%d-%d-%d %d:%d:%d", st.wYear, st.wMonth, st.wDay,
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
Pandora_Windows_Service::pandora_run () {
        TiXmlDocument *doc;
        TiXmlElement  *local_xml, *agent;
        string         xml_filename, remote_host;
        string         remote_filepath, random_integer;
        string         tmp_filename, tmp_filepath, interval;
        string         pubkey_file, privkey_file;
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
        
        remote_host = conf->getValue ("server_ip");
        ssh_client = new SSH::Pandora_Ssh_Client ();
        pandoraDebug ("Connecting with %s", remote_host.c_str ());
        
        try {
                pubkey_file  = Pandora::getPandoraInstallDir ();
                pubkey_file += "key\\id_dsa.pub";
                privkey_file = Pandora::getPandoraInstallDir ();
                privkey_file += "key\\id_dsa";
		
                ssh_client->connectWithPublicKey (remote_host.c_str (), 22, "pandora",
                                                  pubkey_file, privkey_file, "");
        } catch (SSH::Authentication_Failed e) {
                delete ssh_client;
                pandoraLog ("Pandora Agent: Authentication Failed when connecting to %s",
                            remote_host.c_str ());
                try {
                        Pandora_File::removeFile (tmp_filepath);
                } catch (Pandora_File::Delete_Error e) {
                }
                return;
        } catch (Pandora_Exception e) {
                delete ssh_client;
                pandoraLog ("Pandora Agent: Failed when copying to %s",
                            remote_host.c_str ());
                try {
                        Pandora_File::removeFile (tmp_filepath);
                } catch (Pandora_File::Delete_Error e) {
                }
                return;
        }
        
        remote_filepath = conf->getValue ("server_path");
        if (remote_filepath[remote_filepath.length () - 1] != '/') {
                remote_filepath += "/";
        }
        
        pandoraDebug ("Remote copying XML %s on server %s at %s%s",
                      tmp_filepath.c_str (), remote_host.c_str (),
                      remote_filepath.c_str (), tmp_filename.c_str ());
        try {
                ssh_client->scpFileFilename (remote_filepath + tmp_filename,
                                             tmp_filepath);
        } catch (Pandora_Exception e) {
                pandoraLog ("Unable to copy at %s%s", remote_filepath.c_str (),
                            tmp_filename.c_str ());
                ssh_client->disconnect();
                delete ssh_client;
                try {
                        Pandora_File::removeFile (tmp_filepath);
                } catch (Pandora_File::Delete_Error e) {
                }
                return;
        }
        
        ssh_client->disconnect();
        delete ssh_client;
        
        try {
                Pandora_File::removeFile (tmp_filepath);
        } catch (Pandora_File::Delete_Error e) {
        }
                
        pandoraDebug ("Execution number %d", execution_number);
        
        return;
}
