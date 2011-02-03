//     Pandora FMS Embedded Agent
//     (c) Artica Soluciones Tecnológicas S.L 2011
//     (c) Sancho Lerena <slerena@artica.es>

//     This program is free software; you can redistribute it and/or modify
//     it under the terms of the GNU General Public License as published by
//     the Free Software Foundation; either version 2 of the License.
//
//     This program is distributed in the hope that it will be useful,
//     but WITHOUT ANY WARRANTY; without even the implied warranty of
//     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//     GNU General Public License for more details.

//
// Config file parser module. 


#include <stdio.h>
#include <stdlib.h> 
#include <string.h>
#include <ctype.h>
#include "pandora_type.h"
#include "pandora_util.h"

#define MAXLEN 1024

// External reference to GNU asprintf, warning messages could be so nasty...:->
extern int asprintf (char **__restrict __ptr, __const char *__restrict __fmt, ...);

/*
 * initialize data to default values
 */

void
init_parameters (struct pandora_setup* pandorasetup)
{
	asprintf (&pandorasetup->logfile,"/tmp/pandora_agent.log");
	asprintf (&pandorasetup->agent_name, "localhost");
	asprintf (&pandorasetup->server_ip, "localhost");
	pandorasetup->verbosity=5;
}

int
fill_pandora_setup (struct pandora_setup *ps, char *field, char *value)
{
	if (!field)
		return 0;
	if (!*field)
		return 0;
	
	if (strcmp(field, "logfile")==0) {
		pandora_free(ps->logfile);
		asprintf(&ps->logfile, value);
		return 1;
	}
	else if (strcmp(field, "debug")==0) {
		ps->debug=atoi(value);
		return 1;
	}
	else if (strcmp(field, "interval")==0) {
		ps->interval=atoi(value);
		return 1;
	}
	else if (strcmp(field, "autotime")==0) {
		ps->autotime=atoi(value);
		return 1;
	}
	else if (strcmp(field, "verbosity")==0) {
		ps->verbosity=atoi(value);
		return 1;
	}
	else if (strcmp(field, "agent_name")==0) {
		pandora_free(ps->agent_name);
		asprintf(&ps->agent_name, value);
		return 1;
	}
	else if (strcmp(field, "server_ip")==0) {
		pandora_free(ps->server_ip);
		asprintf(&ps->server_ip, value);
		return 1;
	}
	else if (strcmp(field, "temporal")==0) {
		pandora_free(ps->temporal);
		asprintf(&ps->temporal, value);
		return 1;
	}
	else if (strcmp(field, "server_port")==0) {
		ps->server_port=atoi(value);
		return 1;
	}
	else if (strcmp(field, "remote_config")==0) {
		ps->remote_config=atoi(value);
		return 1;
	}
	else
		return 0;
}

int
fill_pandora_module (struct pandora_module *pm, char *field, char *value)
{
	if (!field)
			return 0;
	if (!*field)
		return 0;
	
	if (strcmp(field, "module_name")==0)
	{
		pandora_free(pm->module_name);
		asprintf(&pm->module_name, value);
		return 1;
	}
	else if (strcmp(field, "module_type")==0)
	{
	pandora_free(pm->module_type);
	asprintf(&pm->module_type, value);
	return 1;
	}
	else if (strcmp(field, "module_description")==0)
	{
		pandora_free(pm->module_description);
		asprintf(&pm->module_description, value);
		return 1;
	}
	else if (strcmp(field, "module_exec")==0)
	{
		pandora_free(pm->module_exec);
		asprintf(&pm->module_exec, value);
		return 1;
	}
	else if (strcmp(field, "module_data")==0)
	{
		pandora_free(pm->module_data);
		asprintf(&pm->module_data, value);
		return 1;
	}
	else if (strcmp(field, "module_plugin")==0)
	{
		pandora_free(pm->module_plugin);
		asprintf(&pm->module_plugin, value);
		return 1;
	}
	else
		return 0;
}

int
parse_config (struct pandora_setup *pandorasetup, struct pandora_module **list , char *config_file)
{
	char *line=NULL;
	char *auxline=NULL;
	char buff[MAXLEN];
	char *field=NULL;
	char *value=NULL;
	struct pandora_module *module;
	FILE *fileconfig;

	//Open .conf file in read-only mode
	fileconfig = fopen (config_file, "r");
	//If there is an error opening the config file
	if (fileconfig == NULL)
	{
		printf ("Error opening 'pandora_agent.conf'\n");
		exit(-1);
	}

	//Get full line
	line = (char*) calloc(MAXLEN, sizeof(char));
	if (line == NULL)
	{
		printf ("Error on calloc'\n");
		exit(-1);
	}
	line = fgets (buff, sizeof(buff), fileconfig);

	while (!feof(fileconfig))
	{
		if (buff[0] != '#' && !isspace(buff[0])) //Skip commented and blank lines
		{
			asprintf(&auxline, line);
			asprintf (&field, strtok (auxline, " \t\r\v\f"));
			trim(field);
			if (strchr (line, ' ')!=NULL)
			{
				asprintf(&value, strchr (line, ' '));
				trim(value);
			}
			//START TO GET MODULE LINES
			if (strcmp (field, "module_begin")==0)
			{
				module = (struct pandora_module*) calloc (1, sizeof(struct pandora_module));
				if (module == NULL)
				{
					printf ("Error on calloc'\n");
					exit(-1);
				}
				line = fgets (buff, sizeof(buff), fileconfig); //Get next full line
				asprintf(&auxline, line);
				asprintf (&field, strtok (auxline, " \t\r\v\f"));
				trim(field);
				while (strcmp(field, "module_end")!=0)
				{
					if (strchr (line, ' ')!=NULL)
					{
						asprintf(&value, strchr (line, ' '));
						trim(value);
					}
					fill_pandora_module (module, field, value);
					line = fgets (buff, sizeof(buff), fileconfig);
					asprintf(&auxline, line);
					asprintf (&field, strtok (auxline, " \t\r\v\f"));
					trim(field);
				}
				//LINKED LIST
				if (*list==NULL)
				{
					*list=module;
					module->next=NULL;
				}
				else
				{
					struct pandora_module *ptaux=*list;
					while (ptaux->next!=NULL)
						ptaux=ptaux->next;
					ptaux->next=module;
					module->next=NULL;
				}
			} //END OF GETTING MODULE LINES
			else if (strcmp(field, "module_plugin")==0)
			{
				module = (struct pandora_module*) calloc (1, sizeof(struct pandora_module));
				if (module == NULL)
				{
					printf ("Error on calloc'\n");
					exit(-1);
				}
				fill_pandora_module(module, field, value);
				//LINKED LIST
				if (*list==NULL)
				{
					*list=module;
					module->next=NULL;
				}
				else
				{
					struct pandora_module *ptaux=*list;
					while (ptaux->next!=NULL)
						ptaux=ptaux->next;
					ptaux->next=module;
					module->next=NULL;
				}
			}
			else
			{
				fill_pandora_setup(pandorasetup, field, value);
			}
		}
		line = fgets (buff, sizeof(buff), fileconfig);
	}
	pandora_free(line);
	pandora_free(auxline);
	pandora_free(field);
	pandora_free(value);
	//END READING .CONF FILE
	
	fclose(fileconfig);
	return 0;
}
