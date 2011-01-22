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
parse_config (struct pandora_setup* pandorasetup, char *config_file)
{
	char *s, buff[MAXLEN];

	FILE *fp = fopen (config_file, "r");
  	if (fp == NULL){
    		return -1;
  	}
  	/* Read next line */
	while ((s = fgets (buff, sizeof buff, fp) )){
		/* Skip blank lines and comments */
    		if (buff[0] == '\n' || buff[0] == '#')
      			continue;

		/* Parse name/value pair from line */
    		char name[MAXLEN], value[MAXLEN];
    		s = strtok (buff, " ");

    		if (s==NULL){
      			continue;
		} else {
			strncpy (name, s, MAXLEN);
		} 
		trim (name);	// Purge blank spaces
		s = strtok (NULL, "=");

		if (s==NULL) {
      			continue;
		} else {
			strncpy (value, s, MAXLEN);
		}
    		trim (value);	// Purge blank spaces

    		/* Copy into correct entry in parameters struct */
    		if (strcmp(name, "verbosity")==0){
			pandorasetup->verbosity = atoi(value);
		}
		else if (strcmp(name, "debug")==0){
			pandorasetup->debug = atoi(value);
		}
		else if (strcmp(name, "interval")==0){
			pandorasetup->interval = atoi(value);
		}
		else if (strcmp(name, "autotime")==0){
			pandorasetup->autotime = atoi(value);
		}
		else if (strcmp(name, "remote_config")==0){
			pandorasetup->remote_config = atoi(value);
		}
		else if (strcmp(name, "server_port")==0){
			pandorasetup->server_port = atoi(value);
		}
    		else if (strcmp(name, "agent_name")==0){
			free(pandorasetup->agent_name);
			asprintf(&pandorasetup->agent_name, value);
		}
		else if (strcmp(name, "temporal")==0){
			free(pandorasetup->temporal);
			asprintf(&pandorasetup->temporal, value);
		}
		else if (strcmp(name, "server_ip")==0){
			free(pandorasetup->server_ip);
			asprintf(&pandorasetup->server_ip, value);
		}
		else if (strcmp(name, "logfile")==0){
			free(pandorasetup->logfile);
			asprintf(&pandorasetup->logfile, value);
		}

		// (TODO) do here a real config parsing.
		// This code is just a concept to read the first module_plugin entry and doesnt support blank spaces

		else if (strcmp(name, "module_plugin")==0){
			free(pandorasetup->sancho_test);
			asprintf(&pandorasetup->sancho_test, value);
		}

  	}
  	fclose (fp);
	return 0;
}

