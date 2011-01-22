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


#include <stdio.h>
#include <stdlib.h> 
#include <time.h>
#include <string.h>
#include "module_type.h"
#include "pandora_util.h"


// ==========================================================================
// pandora_free: Free pointers
// ==========================================================================

void
pandora_free (void *pointer){
	if (pointer != NULL){
		free(pointer);
	}
}


int
pandora_return_unixtime () {

	char outstr[200];
	int value;

	time_t t;
	struct tm *tmp;
	t = time(NULL);
	tmp = localtime(&t);
	strftime(outstr, sizeof(outstr), "%s", tmp);
	value = atoi (outstr);
	return value;
}


/*
 * trim: get rid of trailing and leading whitespace...
 *       ...including the annoying "\n" from fgets()
 */

// (TODO) I think is function is a memory hole (leak), check it out !

char *
trim (char * s)
{
	/* Initialize start, end pointers */
	char *s1 = s, *s2 = &s[strlen (s) - 1];
	
	/* Trim and delimit right side */
	while ( (isspace (*s2)) && (s2 >= s1) )
	s2--;
	*(s2+1) = '\0';
	
	/* Trim left side */
	while ( (isspace (*s1)) && (s1 < s2) )
	s1++;
	
	/* Copy finished string */
	strcpy (s, s1);
	return s;
}

// ==========================================================================
// ==========================================================================

char *
pandora_exec (char *commandline) {

	/* Output buffer */
	char *data = NULL;
	
	/* File descriptor */
	FILE *fc = NULL;

	int MAXBUF = 8192; // I will only read the first 8192 bytes of output.

	char buffer[MAXBUF]; 

   	/* Open output of execution as a readonline file handle */
	/* if NULL is a problem in the execution or empty exec output */

	fc = popen (commandline, "r");

	if (fc == NULL)
	{
		return NULL;
	}

	// With popen I sometimes cannot get the file size, so I need to read until find the EOF
	// Don't try to use the usual methods to get filesize, it doesnt work on all cases, so I 
	// use a fixed buffer to avoid problems, 8K should be enough for most pandora data results
	
	data = malloc ((MAXBUF + 1) * sizeof(char)) ;

	fread (data, sizeof(char), MAXBUF, fc); /* Read the entire file, buffers are for weaks :-) */

	pclose (fc);

	return data;
}


// ==========================================================================
// Copy the XML using tentacle to the server
// ==========================================================================

void
tentacle_copy (char *filename, struct pandora_setup *pandorasetup){
	
	char * cmd;

	asprintf (&cmd, "tentacle_client -a %s -p %d %s", pandorasetup->server_ip, pandorasetup->server_port, filename);
	printf ("DEBUG CMD: %s", cmd);

	pandora_exec (cmd);
	pandora_free (cmd);

}

// ==========================================================================
// ==========================================================================

char *
pandora_write_xml_header (struct pandora_setup *pandorasetup) {

	char *os_version;
	char *buffer;
	char *buffer2;
	char *buffer3;

	os_version = trim(pandora_exec ("uname -m"));

	asprintf (&buffer, "<?xml version='1.0' encoding='ISO-8859-1'?>\n");
	asprintf (&buffer2, "<agent_data os_name='embedded' os_version='%s' interval='%d' version='4.0dev' timestamp='AUTO' agent_name='%s' >\n", os_version, pandorasetup->interval, pandorasetup->agent_name);
	asprintf (&buffer3, "%s%s",buffer, buffer2);

	pandora_free (os_version);
	pandora_free (buffer2);
	pandora_free (buffer);
	return buffer3;
}

// ==========================================================================
// ==========================================================================

char *
pandora_write_xml_footer () {

	char *buffer;
	asprintf (&buffer, "</agent_data>\n");
	return buffer;
}

// ==========================================================================
// ==========================================================================

char * 
pandora_write_xml_disk (struct pandora_setup *pandorasetup){

	int fileseed;
	char *filename;
	char *header;
	char *footer;
	FILE *pandora_xml;

	// Set pseudorandom number
	fileseed =  pandora_return_unixtime ();

	// Set XML filename
	asprintf (&filename, "%s/%s.%d.data", pandorasetup->temporal, pandorasetup->agent_name, fileseed);

	// (DEBUG)
	if (pandorasetup->debug == 1){
		printf ("[DEBUG] XML Filename is %s \n", filename);
	}

	pandora_xml = fopen (filename, "w");

	if (pandora_xml == NULL){
		printf ("ERROR: Cannot open xmlile at %s for writing. ABORTING\n", filename);
		exit (-1);
	}

 	header = pandora_write_xml_header (pandorasetup);

 	fprintf (pandora_xml, header);

	// (TODO): Write here each module output

	// This is a just a concept to execute and put the results of a single plugin execution

	char *sancho_test_buffer;
	sancho_test_buffer = pandora_exec (pandorasetup->sancho_test);
	fprintf (pandora_xml, sancho_test_buffer);
	pandora_free (sancho_test_buffer);	

	// End of crap code :-)
	
  	footer = pandora_write_xml_footer ();

	fprintf (pandora_xml, footer);

	fclose (pandora_xml);


	pandora_free (header);
	pandora_free (footer);
	return (filename);	
}


// ==========================================================================
// pandora_log
// --------------------------------------------------------------------------
// Desc: Create an entry in text logfile, based on verbosity and inserting 
//       date and time values.
// Return: void
// Param: level of message, message and pandorasetup struct
// ==========================================================================

void 
pandora_log (int level, char *message, struct pandora_setup *pandorasetup ){
	// Level of messages
	// 0 - Critical error (FAILURE)
	// 1 - User error (ERROR)
	// 2 - Warning
	// 3 - Notice
	// 4 - Info
	// 5 - Verbose
	// 6 - 10 - Different levels of debug message
	if (level <= pandorasetup->verbosity) { // Only for my verbose level or lower.
		FILE *pandora_log;
		char *buff_timedate;
		char *buff_timedate2;
		char *buff_level;
		time_t now;
		struct tm *gmtime;
		
		// Assign NULL to this pointers
		buff_timedate = NULL;
		buff_timedate2 = NULL;
		buff_level=NULL;
		now = time(NULL);
		gmtime = localtime(&now);
		
		switch (level){
			case 0: asprintf (&buff_level,"[F]"); break; 
			case 1: asprintf (&buff_level,"[E]"); break; 
			case 2: asprintf (&buff_level,"[W]"); break;	
			case 3: asprintf (&buff_level,"[N]"); break;	
			case 4: asprintf (&buff_level,"[I]"); break;	
			case 5: asprintf (&buff_level,"[V]"); break;	
			default: asprintf (&buff_level,"[D]");
		};
		buff_timedate = malloc(256);
		strftime (buff_timedate, 256, "%m-%d-%y %H:%M:%S", gmtime);

		asprintf (&buff_timedate2, "%s %s %s\n", buff_timedate, buff_level,message);

		pandora_log = fopen (pandorasetup->logfile, "a");

		if (pandora_log == NULL){
			printf ("ERROR: Cannot open logfile at %s. ABORTING\n", pandorasetup->logfile);
			exit(-1);
		}

		fprintf (pandora_log, buff_timedate2);

		// Free mem

		fclose (pandora_log);
		pandora_free (buff_timedate);
		buff_timedate = NULL;
		pandora_free (buff_timedate2);
		buff_timedate2 = NULL;
		pandora_free (buff_level);
		buff_level = NULL;
	}
}

// ==========================================================================
// Check for a filename end in ".data" string
// BEWARE of UPPERCASE filenames.
// ==========================================================================

int
isdatafile (char *filename){
        int valid;
        char *token; // reference to a position in *filename memory
        valid = -1;
        token = strtok(filename,".");
        while (token != NULL){
                if (strcmp(token,"data")==0)
                        valid=0;
                else
                        valid=-1;
                token = strtok(NULL,".");
        }
        return valid;
}




