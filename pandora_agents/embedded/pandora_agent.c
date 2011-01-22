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


#include <sys/types.h>
#include <string.h>
#include <stdlib.h>
#include <stdio.h>
#include <signal.h>
#include <errno.h>
#include <dirent.h> 
#include <unistd.h>
#include "module_type.h"
#include "pandora_util.h"
#include "pandora_config.h"

#ifdef HAVE_CONFIG_H
#include <config.h>
#endif



int 
main(int argc, char **argv) {
   	DIR 			*pDIR;
   	struct dirent 		*pDirEnt;
	struct pandora_setup 	*pandorasetup;
	char			*config_file;
	char  			*fullpath;
	char			*buffer;
	long int 		 id_audit;
	char                     c;
	char			*xml_filename;


	printf ("Pandora FMS Embedded Agent v%s (c) 2011 http://pandorafms.org\n", VERSION);

	config_file = NULL;

	if (argc < 2 && argc > 3){
		printf ("Syntax is:\n\n    pandora_agent <path_to_pandora_agent.conf> \n\n");
		exit (0);
	}
	
        char *cmd = *argv++;
	config_file = *argv++;

	if (config_file == NULL) {
		printf ("Cannot load configuration file. Exitting \n");
		return -1;
	}
	
	pandorasetup = malloc(sizeof(struct pandora_setup));

	// Initialize to default parameters
	init_parameters (pandorasetup);

	// Load config file using first parameter
  	parse_config (pandorasetup, config_file);
	
	asprintf (&buffer,"Starting %s v%s", PACKAGE_NAME, VERSION);
	pandora_log (3, buffer, pandorasetup);
	pandora_free (buffer);

	asprintf (&buffer,"Agent name: %s", pandorasetup->agent_name);
	pandora_log (3, buffer, pandorasetup);
	pandora_free (buffer);

	asprintf (&buffer,"Server IP: %s", pandorasetup->server_ip);
	pandora_log (3, buffer, pandorasetup);
	pandora_free (buffer);

	asprintf (&buffer,"Temporal: %s", pandorasetup->temporal);
	pandora_log (3, buffer, pandorasetup);
	pandora_free (buffer);


	while (1){  // Main loop
		xml_filename = pandora_write_xml_disk (pandorasetup);
		if (pandorasetup->debug == 1){
			printf ("Debug mode activated. Exiting now! \n");
			exit (0);
		}

	 	tentacle_copy (xml_filename, pandorasetup);
		pandora_free(xml_filename);
  		sleep(pandorasetup->interval);
	}

	pandora_free(config_file);
	return (0);
}
