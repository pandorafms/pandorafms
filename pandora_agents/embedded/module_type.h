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

// (TODO) Que coño es esto ??
#define MOD_FILE 1
#define MOD_ITEM 2

// Structs for Pandora Agent

struct pandora_agent {
	char *name;
	char *version;
	char *timestamp;
	char *os;
	char *os_version;
	char *os_build;
	long unsigned int interval;
};

struct pandora_module {
	char *module_name;
	char *module_type;
	char *module_description;
	char *module_exec;
	char *module_data;
	char *module_plugin;
};

struct pandora_setup {
	char *logfile;
	int  debug;
	int  interval;
	int  autotime;
	int  verbosity;
	char *agent_name;
	char *server_ip;
	char *temporal;
	int server_port;

	char *sancho_test;
};


