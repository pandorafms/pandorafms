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
#include "windows_service.h"
#include "modules/pandora_module_factory.h"

#include "modules/pandora_module_generic_data.h"
#include "modules/pandora_module_generic_data_inc.h"
#include "modules/pandora_module_generic_data_string.h"
#include "modules/pandora_module_generic_proc.h"
#include <iostream>
#include <cstdlib>
#include <ctime>

using namespace std;
using namespace Pandora;
using namespace Pandora_Modules;

Pandora_Windows_Service::Pandora_Windows_Service (const char * svc_name,
                                              const char * svc_display_name,
                                              const char * svc_description)
        : Windows_Service (svc_name, svc_display_name, svc_description)
        {

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

void
Pandora_Windows_Service::pandora_init () {
        int    interval_ms = 10000;
        string conf_file;
        
        setPandoraDebug (true);
        pandoraDebug ("Init begin");
        
        conf_file = Pandora::getPandoraInstallDir ();
        conf_file += "pandora_agent.conf";
        this->conf = new Pandora_Agent_Conf (conf_file);
        this->modules = new Pandora_Module_List (conf_file);
        
        srand ((unsigned) time (0));
        this->setSleepTime (interval_ms);
        
        pandoraDebug ("Init end");
        return;
}

void
Pandora_Windows_Service::addXMLHeader (TiXmlElement *root) {
        TiXmlElement *agent;
        TiXmlElement *element;
        TiXmlText    *text;
        SYSTEMTIME    st;
        char          timestamp[20];
        
        agent = new TiXmlElement ("agent");
        
        element = new TiXmlElement ("name");
        /* TODO: Get the name of the machine if there is no agent_name*/
        text = new TiXmlText ("agent_name");
        element->InsertEndChild (*text);
        agent->InsertEndChild (*element);
        delete text;
        delete element;
        
        element = new TiXmlElement ("version");
        /* TODO: Get the real version of the agent */
        text = new TiXmlText ("1.0Beta");
        element->InsertEndChild (*text);
        agent->InsertEndChild (*element);
        delete text;
        delete element;
        
        element = new TiXmlElement ("timestamp");
        GetSystemTime(&st);
        sprintf (timestamp, "%d/%d/%d %d:%d:%d", st.wDay, st.wMonth, 
                 st.wYear, st.wHour, st.wMinute, st.wSecond);
        text = new TiXmlText (timestamp);
        element->InsertEndChild (*text);
        agent->InsertEndChild (*element);
        delete text;
        delete element;
        
        element = new TiXmlElement ("interval");
        text = new TiXmlText ("interval");
        element->InsertEndChild (*text);
        agent->InsertEndChild (*element);
        delete text;
        delete element;
        
        element = new TiXmlElement ("os");
        /* TODO */
        text = new TiXmlText ("Windows");
        element->InsertEndChild (*text);
        agent->InsertEndChild (*element);
        delete text;
        delete element;
        
        element = new TiXmlElement ("os_version");
        /* TODO */
        text = new TiXmlText ("XP");
        element->InsertEndChild (*text);
        agent->InsertEndChild (*element);
        delete text;
        delete element;
        
        element = new TiXmlElement ("os_build");
        /* TODO */
        text = new TiXmlText ("1");
        element->InsertEndChild (*text);
        agent->InsertEndChild (*element);
        delete text;
        delete element;
        
        root->InsertEndChild (*agent);
        delete agent;
}

void
Pandora_Windows_Service::pandora_run () {

        pandoraDebug ("Run begin");
        
        execution_number++;
        
        if (this->modules != NULL) {
                this->modules->goFirst ();
                
                while (! this->modules->isLast ()) {
                        Pandora_Module *module;
                        string          result;
                        
                        module = this->modules->getCurrentValue ();
                        
                        pandoraDebug ("Run %s", module->getName ().c_str ());
                        module->run ();
                        
                        try {
                                result = module->getOutput ();
                                
                                pandoraDebug ("Result: %s", result.c_str ());
                        } catch (Output_Error e) {
                                pandoraLog ("Output error");
                        } catch (Interval_Error e) {
                                pandoraLog ("The returned value was not in the interval");
                        }
                        this->modules->goNext ();
                }
        }
        
        pandoraDebug ("Execution number %d", execution_number);
        
        return;
}
