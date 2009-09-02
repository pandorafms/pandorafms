/* Pandora Inventory module. These module makes an inventory of the machine where
the agent is instaled.

   Copyright (C) 2009 Artica ST.
   Written by Pablo de la Concepción.

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

#include "pandora_module_inventory.h"
#include "../windows/pandora_wmi.h"
#include "../pandora_strutils.h"

using namespace Pandora;
using namespace Pandora_Modules;
using namespace Pandora_Strutils;

/** 
 * Creates a Pandora_Module_Inventory object.
 * 
 * @param name Module name.
 */
Pandora_Module_Inventory::Pandora_Module_Inventory (string name, string options)
	: Pandora_Module (name) {	
	this->setKind (module_inventory_str);
	this->options = options;
}
/** 
 * Run the module and generates the output.
 * Depending on the options string makes different querys and gets more or
 * less ressults, the possible options are:
 *	* CPU -> Gets information about the CPUs
 *  * CDROM -> Gets information about the CDROMs
 *  * Video -> Gets information about the video cards
 *  * HDs -> Gets information about the Hard Drives
 *  * NICs -> Gets information about the Network Interface Controlers
 *  * Patches -> Gets informaton about the patches installed
 *  * Software -> Gets information about the MSI packages installed
 */
void
Pandora_Module_Inventory::run () {

	list<string> rows;
	list<string>::iterator row;
	int num_results = 0;
	string res;
	size_t found;

	// Until no data data is gathered there will be no output
	this->has_output = false; 
	pandoraLog("At Inventory - Run\n");
	try {
		Pandora_Module::run ();
	} catch (Interval_Not_Fulfilled e) {
		return;
	}
	found = this->options.find(module_invenory_cdrom_str);
	if (found != string::npos){
		pandoraDebug("	Launching CDRom Query WMI ... \n");
		
		num_results = Pandora_Wmi::getCdRomInfo(rows);
		pandoraLog("Setting Outuput: found %d items\n",num_results);
		   		
		try {		
			for (row = rows.begin (); row != rows.end(); ++row) {
			    this->setOutput (*row,module_invenory_cdrom_str);
			    this->has_output = true;
		    }	    
		} catch (Pandora_Wmi::Pandora_Wmi_Exception e) {
		    pandoraLog("Output Error at Moudle Inventory\n");
			this->has_output = false;
		}
		
		rows.clear();
	}
	found = this->options.find(module_invenory_video_str);
	if (found != string::npos){
		pandoraDebug("	Launching Video Query WMI\n");
		
		num_results = Pandora_Wmi::getVideoInfo(rows);
		pandoraLog("Setting Outuput: found %d items\n",num_results);
				
		try {		
			for (row = rows.begin (); row != rows.end(); ++row) {
			    this->setOutput (*row,module_invenory_video_str);
			    this->has_output = true;
		    }	    
		} catch (Pandora_Wmi::Pandora_Wmi_Exception e) {
		    pandoraLog("Output Error at Moudle Inventory\n");
			this->has_output = (this->has_output || false);
		}
		
		rows.clear();
	}
	found = this->options.find(module_invenory_hds_str);
	if (found != string::npos){
		pandoraDebug("	Launching HD Query WMI\n");
	    
	    num_results = Pandora_Wmi::getHDsInfo(rows);
	    pandoraLog("Setting Outuput: found %d items\n",num_results);
	   		
		try {		
			for (row = rows.begin (); row != rows.end(); ++row) {
			    this->setOutput (*row,module_invenory_hds_str);
			    this->has_output = true;
		    }	    
		} catch (Pandora_Wmi::Pandora_Wmi_Exception e) {
	        pandoraLog("Output Error at Moudle Inventory\n");
			this->has_output = (this->has_output || false);
		}
	   
		rows.clear();
	}
	found = this->options.find(module_invenory_cpu_str);
	if (found != string::npos){
		pandoraDebug("	Launching CPUs Query WMI\n");
	    
	    num_results = Pandora_Wmi::getCPUsInfo(rows);
	    pandoraLog("Setting Outuput: found %d items\n",num_results);

		
		try {		
			for (row = rows.begin (); row != rows.end(); ++row) {
			    this->setOutput (*row,module_invenory_cpu_str);
			    this->has_output = true;
		    }	    
		} catch (Pandora_Wmi::Pandora_Wmi_Exception e) {
	        pandoraLog("Output Error at Moudle Inventory\n");
			this->has_output = (this->has_output || false);
		}
		rows.clear();
	}
	found = this->options.find(module_invenory_nics_str);
	if (found != string::npos){
		pandoraDebug("	Launching NICs Query WMI\n");
	    
	    num_results = Pandora_Wmi::getNICsInfo(rows);
	    pandoraLog("Setting Outuput: found %d items\n",num_results);
		
		try {		
			for (row = rows.begin (); row != rows.end(); ++row) {
			    this->setOutput (*row,module_invenory_nics_str);
			    this->has_output = true;
		    }	    
		} catch (Pandora_Wmi::Pandora_Wmi_Exception e) {
	        pandoraLog("Output Error at Moudle Inventory\n");
			this->has_output = (this->has_output || false);
		}
	    rows.clear();
    }
	found = this->options.find(module_invenory_patches_str);
	if (found != string::npos){
		pandoraDebug("	Launching Patch Query WMI\n");
		
		num_results = Pandora_Wmi::getPatchInfo(rows);
		pandoraLog("Setting Outuput: found %d items\n",num_results);
	
		try {		
			for (row = rows.begin (); row != rows.end(); ++row) {
			    this->setOutput (*row,module_invenory_patches_str);
			    this->has_output = true;
		    }	    
		} catch (Pandora_Wmi::Pandora_Wmi_Exception e) {
		    pandoraLog("Output Error at Moudle Inventory\n");
			this->has_output = (this->has_output || false);
		}
		rows.clear();
    }
    found = this->options.find(module_invenory_ram_str);
	if (found != string::npos){
		pandoraDebug("	Launching RAM Query WMI\n");
		
		num_results = Pandora_Wmi::getRAMInfo(rows);
		pandoraLog("Setting Outuput: found %d items\n",num_results);
		
		try {		
			for (row = rows.begin (); row != rows.end(); ++row) {
			    this->setOutput (*row,module_invenory_ram_str);
			    this->has_output = true;
		    }	    
		} catch (Pandora_Wmi::Pandora_Wmi_Exception e) {
		    pandoraLog("Output Error at Moudle Inventory\n");
			this->has_output = (this->has_output || false);
		}
		rows.clear();
    }
	found = this->options.find(module_invenory_software_str);
	if (found != string::npos){
	    pandoraDebug("	Launching Software Query WMI\n");
	    num_results = Pandora_Wmi::getSoftware(rows);
	
	    pandoraLog("Setting Outuput: found %d items\n",num_results);

		try {		
			for (row = rows.begin (); row != rows.end(); ++row) {
			    this->setOutput (*row,module_invenory_software_str);
		    }
		} catch (Pandora_Wmi::Pandora_Wmi_Exception e) {
	        pandoraLog("Output Error at Moudle Inventory\n");
			this->has_output = false;
		}
    }
	//pandoraLog("Inventory - Run finish\n");
}



/** 
 * Set the output of the module.
 *
 * If the function is called more than once before calling getXML, the
 * output will be accumulated and added to a <datalist> tag.
 *
 * @param output Output to add.
 * @overrides Pandora_Module::setOutput (string output)
 */
void
Pandora_Module_Inventory::setOutput (string output) {
	Pandora_Data *data;

	if (this->inventory_list == NULL)
		this->inventory_list = new list<Pandora_Data *> ();
	data = new Pandora_Data (output);
	this->inventory_list->push_back (data);
	this->latest_output = output;
}


/** 
 * Set the output of the module.
 *
 * If the function is called more than once before calling getXML, the
 * output will be accumulated and added to a <datalist> tag.
 *
 * @param output Output to add.
 * @param data_origin Origin of the data.
 */
void
Pandora_Module_Inventory::setOutput (string output, string data_origin) {
	Pandora_Data *data;
	if (this->inventory_list == NULL)
		this->inventory_list = new list<Pandora_Data *> ();
	data = new Pandora_Data (output, data_origin);
	this->inventory_list->push_back (data);	 
}

/** 
 * Get the XML output of the inventory.
 *
 * The output is a element of the TinyXML library. A sample output of
 * a module is:
 * @verbatim
 <inventory>
	 <inventory_module>
	   <name>Conexiones abiertas</name>
	   <type>generic_data</type>
	   <data>5</data>
	   <description>Conexiones abiertas</description>
	 </inventory_module>
	 ...
 </inventory>
   @endverbatim
 * The output has one <inventory_module> tag for each submodule with information
 * (i.e. CPU, CDROM, Video, ...)
 * @return A pointer to the TiXmlElement if successful which has to be
 *         freed by the caller. NULL if the XML could not be created.
 * @overrides TiXmlElement* Pandora_Module::getXml()
 */

TiXmlElement *
Pandora_Module_Inventory::getXml() {
	TiXmlElement *root;
	TiXmlElement *element, *inventory_module;
	TiXmlElement *inventory_list_element = NULL;
	TiXmlElement *data_element;
	TiXmlText    *text;
	string        item_clean, data_clean, desc_clean, submodule;
	Pandora_Data *data;
	pandoraDebug ("Pandora_Module_Inventory::getXML begin\n");
	
	if (!this->has_output || this->inventory_list == NULL) {
		return NULL;
	}
	
	root = new TiXmlElement ("inventory");
	inventory_module = new TiXmlElement("inventory_module");
  
	if (this->inventory_list && this->inventory_list->size () > 1) {
		list<Pandora_Data *>::iterator iter;		

		for (iter = this->inventory_list->begin ();
		     iter != this->inventory_list->end ();
		     iter++) {
			data = *iter;
			if (submodule != data->getDataOrigin()){
                if (inventory_list_element != NULL)
                {
					inventory_module->InsertEndChild (*inventory_list_element);
		 			delete inventory_list_element;
		 			inventory_list_element = NULL;
		 			root->InsertEndChild(*inventory_module);
		 			delete inventory_module;
		 			inventory_module = new TiXmlElement("inventory_module");
		        }
		        
				submodule = data->getDataOrigin();
		        element = new TiXmlElement ("name");
				text = new TiXmlText (submodule);
				element->InsertEndChild (*text);
				inventory_module->InsertEndChild (*element);
				delete element;
				delete text;
				
				element = new TiXmlElement ("type");
				text = new TiXmlText (this->module_type_str);
				element->InsertEndChild (*text);
				inventory_module->InsertEndChild (*element);
				delete element;
				delete text;
		
				inventory_list_element = new TiXmlElement ("datalist");
		     }
			data_element = new TiXmlElement ("data");
			element = new TiXmlElement ("value");
			try {
				data_clean = strreplace (this->getDataOutput (data),
							 "%", "%%" );
			} catch (Output_Error e) {
		 		delete element;
				continue;
			}
			
			text = new TiXmlText (data_clean);
			element->InsertEndChild (*text);
			data_element->InsertEndChild (*element);
			delete text;
			delete element;
			
			element = new TiXmlElement ("timestamp");
			text = new TiXmlText (data->getTimestamp ());
			element->InsertEndChild (*text);
			data_element->InsertEndChild (*element);
			delete text;
			delete element;

			inventory_list_element->InsertEndChild (*data_element);
		}
		if (inventory_list_element != NULL)
        {
			inventory_module->InsertEndChild (*inventory_list_element);
 			delete inventory_list_element;
 			inventory_list_element = NULL;
        }
	} else {
		data = inventory_list->front ();
		element = new TiXmlElement ("data");
		try {
		data_clean = strreplace (this->getDataOutput (data), "%", "%%" );
		text = new TiXmlText (data_clean);
		element->InsertEndChild (*text);
		inventory_module->InsertEndChild (*element);
		delete text;
	} catch (Output_Error e) {
	}
		delete element;
	}
		
	element = new TiXmlElement ("description");
	text = new TiXmlText (this->module_description);
	element->InsertEndChild (*text);
	inventory_module->InsertEndChild (*element);
	delete text;
	delete element;

	root->InsertEndChild(*inventory_module);
	delete inventory_module;
	
	this->cleanDataList ();
	pandoraDebug ("%s Pandora_Module_Inventory::getXML end", module_name.c_str ());
	return root;
}

