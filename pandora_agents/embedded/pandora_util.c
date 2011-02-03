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
#include "pandora_type.h"
#include "pandora_util.h"


// ==========================================================================
// pandora_free: Free pointers
// ==========================================================================

void
pandora_free (void *pointer){
	if (pointer != NULL)
	{
		free(pointer);
	}
}

char *
return_time (char *formatstring) {

	char 	buffer[256];
	char 	*output;
	time_t 	curtime;
	struct tm *loctime;

	curtime = time (NULL);
	loctime = localtime (&curtime);
	strftime (buffer, 256, formatstring, loctime);
	asprintf (&output, buffer);

	return output;
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


char* rtrim(char* string, char junk)
{
    char* original = string + strlen(string);
    while(original != string && *--original == junk);
    *(original + 1) = '\0';
    return string;
}

char* ltrim(char *string, char junk)
{
    char* original = string;
    char *p = original;
    int trimmed = 0;
    do
    {
        if (*original != junk || trimmed)
        {
            trimmed = 1;
            *p++ = *original;
        }
    }
    while (*original++ != '\0');
    return string;
}

char *
trim (char * s)
{
	s=rtrim(s, ' ');
	s=rtrim(s, '\t');
	s=rtrim(s, '\n');
	s=ltrim(s, ' ');
	s=ltrim(s, '\t');
	s=ltrim(s, '\n');
}

// ==========================================================================
// ==========================================================================

char *
pandora_exec (char *commandline) {
	//printf("CommandLine :%s:\n", commandline);
	/* Output buffer */
	char *data = NULL;
	
	int read;
	
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

	read = fread (data, sizeof(char), MAXBUF, fc); /* Read the entire file, buffers are for weaks :-) */
	
	data[read]='\0';

	pclose (fc);

	return data;
}


// ==========================================================================
// Copy the XML using tentacle to the server
// ==========================================================================

void
tentacle_copy (char *filename, struct pandora_setup *pandorasetup){
	
	char * cmd=NULL;
	char * aux=NULL;

	asprintf (&cmd, "tentacle_client -a %s -p %d %s", pandorasetup->server_ip, pandorasetup->server_port, filename);

	aux=pandora_exec (cmd);
	pandora_free(aux);
	pandora_free (cmd);

}

// ==========================================================================
// ==========================================================================

char *
pandora_write_xml_header (struct pandora_setup *pandorasetup) {

	char *os_version=NULL;
	char *date=NULL;
	char *buffer=NULL;
	char *buffer2=NULL;
	char *buffer3=NULL;
	os_version=pandora_exec ("uname -m");
	trim(os_version);
	if (pandorasetup->autotime == 1)
	{
		asprintf (&date, "AUTO");
	}
	else
	{
		date = return_time("%Y/%m/%d %H:%M:%S");
	}
	
	asprintf (&buffer, "<?xml version='1.0' encoding='ISO-8859-1'?>\n");
	asprintf (&buffer2, "<agent_data os_name='embedded' os_version='%s' interval='%d' version='4.0dev' timestamp='%s' agent_name='%s'>\n", os_version, pandorasetup->interval, date, pandorasetup->agent_name);
	asprintf (&buffer3, "%s%s",buffer, buffer2);

	pandora_free (os_version);
	pandora_free (buffer2);
	pandora_free (buffer);
	pandora_free (date);
	return buffer3;
}

// ==========================================================================
// ==========================================================================

char *
pandora_write_xml_footer () {

	char *buffer=NULL;
	asprintf (&buffer, "</agent_data>\n");
	return buffer;
}

// ==========================================================================
// ==========================================================================

char *
pandora_write_xml_module (struct pandora_module *ptmodule)
{
	char *data=NULL;
	char *buffer=NULL;
	char *name=NULL;
	char *type=NULL;
	char *desc=NULL;
		
	if (ptmodule->module_name==NULL)
		asprintf(&name, "");
	else
		asprintf(&name, ptmodule->module_name);
	
	if (ptmodule->module_type==NULL)
		asprintf(&type, "");
	else
		asprintf(&type, ptmodule->module_type);
	
	if (ptmodule->module_description==NULL)
		asprintf(&desc, "");
	else
		asprintf(&desc, ptmodule->module_description);
		
	data=pandora_exec(ptmodule->module_exec);
	trim(data);
		
	asprintf (&buffer, "\t<module>\n\t<name><![CDATA[%s]]></name>\n\t<description><![CDATA[%s]]></description>\n\t<type>%s</type>\n\t<data><![CDATA[%s]]></data>\n\t</module>\n", name, desc, type, data);
	pandora_free(data);
	pandora_free(name);
	pandora_free(type);
	pandora_free(desc);
	return buffer;
}

char *
pandora_write_xml_module_plugin (struct pandora_module *ptmodule)
{
	char *buffer=pandora_exec(ptmodule->module_plugin);
	return buffer;
}

char * 
pandora_write_xml_disk (struct pandora_setup *pandorasetup, struct pandora_module *list){

	int fileseed;
	char *filename=NULL;
	char *header=NULL;
	char *buffer=NULL;
	char *footer=NULL;
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
		printf ("ERROR: Cannot open xmlfile at %s for writing. ABORTING\n", filename);
		exit (-1);
	}
	
 	header = pandora_write_xml_header (pandorasetup);
	
 	fprintf (pandora_xml, "%s", header);
	struct pandora_module *ptaux=list;
	while (ptaux!=NULL)
	{
		if (ptaux->module_type!=NULL)
		{
			buffer = pandora_write_xml_module(ptaux);
			fprintf(pandora_xml, "%s", buffer);
		}
		else if (ptaux->module_plugin!=NULL)
		{
			buffer = pandora_write_xml_module_plugin(ptaux);
			fprintf(pandora_xml, "%s", buffer);
		}
		ptaux=ptaux->next;
		pandora_free(buffer);
	}
	
  	footer = pandora_write_xml_footer ();

	fprintf (pandora_xml, "%s", footer);

	fclose (pandora_xml);

	pandora_free (header);
	pandora_free (footer);
	return filename;
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

		fprintf (pandora_log, "%s", buff_timedate2);

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




