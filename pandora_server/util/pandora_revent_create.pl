#!/usr/bin/perl

########################################################################
# Pandora FMS - Remote Event Tool (via WEB API) 
########################################################################
# Copyright (c) 2013 Artica Soluciones Tecnologicas S.L
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License version 2
########################################################################

# Includes list
use strict;
use LWP::Simple;

# Init
tool_api_init();

# Main
tool_api_main();

########################################################################
# Print a help screen and exit.
########################################################################
sub help_screen{

	print "Options to create event: 

\t$0 -p <path_to_consoleAPI> -u <credentials> -create_event <options> 

Where options:\n
	-u <credentials>			: API credentials separated by comma: <api_pass>,<user>,<pass>
	-name <event_name>			: Free text
	-group <id_group>			: Group ID (use 0 for 'all') 
	-agent					: Agent ID
	
Optional parameters:
	
	[-status <status>]			: 0 New, 1 Validated, 2 In process
	[-user <id_user>]			: User comment (use in combination with -comment option)
	[-type <event_type>]			: unknown, alert_fired, alert_recovered, alert_ceased
								  alert_manual_validation, system, error, new_agent
								  configuration_change, going_unknown, going_down_critical,
								  going_down_warning, going_up_normal
	[-severity <severity>] 					: 0 Maintance,
								  1 Informative,
								  2 Normal,
								  3 Warning,
								  4 Crit,
								  5 Minor,
								  6 Major
	[-am <id_agent_module>]		: ID Agent Module linked to event
	[-alert <id_alert_am>]		: ID Alert Module linked to event
	[-c_instructions <critical_instructions>]
	[-w_instructions <warning_instructions>]
	[-u_instructions <unknown_instructions>]
	[-user_comment <comment>]
	[-owner_user <owner event>]	: Use the login name, not the descriptive
	[-source <source>]			: (By default 'Pandora')
	[-tag <tags>]				: Tag (must exist in the system to be imported)
	[-custom_data <custom_data>]: Custom data should be a base 64 encoded JSON document
	[-server_id <server_id>]	: The pandora node server_id\n\n";
	
	print "Example of event generation:\n\n";
	
	print "\t./pandora_revent_create.pl -p http://localhost/pandora_console/include/api.php -u 1234,admin,pandora \
	\t-create_event -name \"SampleEvent\" -group 2 -agent 189 -status 0 -user \"admin\" -type \"system\" \
	\t-severity 3 -am 0 -alert 9 -c_instructions \"Critical instructions\" -w_instructions \"Warning instructions\" \
	\t-u_instructions \"Unknown instructions\" -source \"Commandline\" -tag \"Tags\"\n\n";

	exit;
}

##############################################################################
# Init screen
##############################################################################
sub tool_api_init () {
	
	print "\nPandora FMS Remote Event Tool Copyright (c) 2013-2015 Artica ST\n";
	print "This program is Free Software, licensed under the terms of GPL License v2\n";
	print "You can download latest versions and documentation at http://www.pandorafms.org\n\n";
	
	if ($#ARGV < 0) {
		help_screen();
	}
	
	if (($ARGV[0] eq '-h') || ($ARGV[0] eq '-help')) {
		help_screen();
	}
	
}

########################################################################
########################################################################
# MAIN
########################################################################
########################################################################

sub tool_api_main () {
	
	my $api_path;
	my $event_name;
	my $id_group;
	my $event_type;
	my $data_event;
	my $credentials;
	my $api_pass;
	my $db_user;
	my $db_pass;
	my @db_info;
	my $id_agent;
	my $id_user = '';
	my $status = '';
	my $id_agent_module = '';
	my $id_alert_am = '';
	my $severity = '';
	my $user_comment = '';
	my $tags = '';
	my $source = '';
	my $critical_instructions = '';
	my $warning_instructions = '';
	my $unknown_instructions = '';
	my $owner_user = '';
	my $id_event;
	my $option = $ARGV[4];
	my $call_api;
	my $custom_data = "";
	my $server_id = 0;
	
	#~ help or api path (required)
	if ($ARGV[0] eq '-h') {
		print "HELP!\n";
		help_screen();
	}
	elsif ($ARGV[0] ne '-p') {
		print "[ERROR] Missing API path! Read help info:\n\n";
		help_screen ();
	}
	else {
		$api_path = $ARGV[1];
	}
	
	#~ credentials of database
	if ($ARGV[2] eq '-u') {
		$credentials = $ARGV[3];
		@db_info = split(',', $credentials);
		
		if ($#db_info < 2) {
			print "[ERROR] Invalid database credentials! Read help info:\n\n";
			help_screen();
		}
		else {
			$api_pass = $db_info[0];
			$db_user = $db_info[1];
			$db_pass = $db_info[2];
		}
	}
	else {
		print "[ERROR] Missing database credentials! Read help info:\n\n";
		help_screen ();
	}
	
	if ($ARGV[4] eq '-create_event') {
		my $i = 0;
		foreach (@ARGV) {
			my $line = $_;
			
			#-----------DEBUG----------------------------
			#print("i " . $i .  " line " . $line . "\n");
			
			if ($line eq '-agent') {
				$id_agent = $ARGV[$i + 1];
			}
			if ($line eq '-group') {
				$id_group = $ARGV[$i + 1];
			}
			if ($line eq '-name') {
				$event_name = $ARGV[$i + 1];
			}
			if ($line eq '-type') {
				$event_type = $ARGV[$i + 1];
			}
			if ($line eq '-user') {
				$id_user = $ARGV[$i + 1];
			}
			if ($line eq '-status') {
				$status = $ARGV[$i + 1];
			}
			if ($line eq '-am') {
				$id_agent_module = $ARGV[$i + 1];
			}
			if ($line eq '-alert') {
				$id_alert_am = $ARGV[$i + 1];
			}
			if ($line eq '-severity') {
				$severity = $ARGV[$i + 1];
			}
			if ($line eq '-tag') {
				$tags = $ARGV[$i + 1];
			}
			if ($line eq '-source') {
				$source = $ARGV[$i + 1];
			}
			if ($line eq '-c_instructions') {
				$critical_instructions = $ARGV[$i + 1];
			}
			if ($line eq '-w_instructions') {
				$warning_instructions = $ARGV[$i + 1];
			}
			if ($line eq '-u_instructions') {
				$unknown_instructions = $ARGV[$i + 1];
			}
			if ($line eq '-user_comment') {
				$user_comment = $ARGV[$i + 1];
			}
			if ($line eq '-owner_user') {
				$owner_user = $ARGV[$i + 1];
			}
			if ($line eq '-custom_data') {
				$custom_data = $ARGV[$i + 1];
			}
			if ($line eq '-server_id') {
				$server_id = $ARGV[$i + 1];
			}
			
			$i++;
		}
		
		if ($event_name eq "") {
			print "[ERROR] Missing id agent! Read help info:\n\n";
			help_screen ();
		}
		if ($id_group eq "") {
			print "[ERROR] Missing event group! Read help info:\n\n";
			help_screen ();
		}
		if ($id_agent eq "") {
			print "[ERROR] Missing id agent! Read help info:\n\n";
			help_screen ();
		}
		
		$data_event = $event_name .
			"|" . $id_group .
			"|" . $id_agent .
			"|" . $status .
			"|" . $id_user .
			"|" . $event_type .
			"|" . $severity .
			"|" . $id_agent_module .
			"|" . $id_alert_am . 
			"|" . $critical_instructions .
			"|" . $warning_instructions .
			"|" . $unknown_instructions .
			"|" . $user_comment .
			"|" . $owner_user .
			"|" . $source .
			"|" . $tags .
			"|" . $custom_data .
			"|" . $server_id;
		
		$call_api = $api_path . '?' .
			'op=set&' .
			'op2=create_event&' .
			'other=' . $data_event .'&' .
			'other_mode=url_encode_separator_|&' .
			'apipass=' . $api_pass . '&' .
			'user=' . $db_user . '&' .
			'pass=' . $db_pass;
		
	}
		
	my @args = @ARGV;
	my $ltotal=$#args; 
	
	if ($ltotal < 0) {
		print "[ERROR] No valid arguments. Read help info:\n\n";
		help_screen ();
		exit;
 	}
	else {
		#-----------DEBUG----------------------------
		#print($call_api . "\n\n\n");
		
		my $content = get($call_api);
		
		#-----------DEBUG----------------------------
		#print($content . "\n\n\n");
		
		if ($content eq undef) {
			print "[ERROR] Not respond or bad syntax. Read help info:\n\n";
			help_screen();
		}
		else {
			print "Event ID: $content";
		}
	}
	
	print "\nExiting!\n\n";
	
	exit;
}
