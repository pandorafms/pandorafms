#!/usr/bin/perl

###############################################################################
# Pandora FMS General Management Tool
###############################################################################
# Copyright (c) 2010 Artica Soluciones Tecnologicas S.L
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License version 2
###############################################################################

# Includes list
use strict;
use LWP::Simple;

# Init
tool_api_init();

# Main
tool_api_main();

##############################################################################
# Print a help screen and exit.
##############################################################################
sub help_screen{
	print "Usage: perl $0 -p <path to pandora console API> -n <event_name> [-o <event_fields>] -u <credentials>\n\n";
   	print "Call syntax create_event: \n\t";
	print "<event_name>: Name of event (required)\n\t";
	print "<event_fields>: Serialized parameters separated by comma (optional). In this order: \n\t\t";
	print "<id_agente> \n\t\t";
	print "<id_usuario> \n\t\t";
	print "<id_grupo> \n\t\t";
	print "<estado> \n\t\t";
	print "<evento> \n\t\t";
	print "<event_type> \n\t\t";
	print "<id_agentmodule> \n\t\t";
	print "<id_alert_am> \n\t\t";
	print "<criticity> \n\t\t";
	print "<user_comment> \n\t\t";
	print "<tags> \n\t\t";
	print "<source> \n\t\t";
	print "<id_extra> \n\t\t";
	print "<critical_instructions> \n\t\t";
	print "<warning_instructions> \n\t\t";
	print "<unknown_instructions> \n\t";
	print "<credentials>: Credentials of API and database separated by comma (required). In this order\n\t\t";
	print "<api_pass>,<db_user>,<db_pass>\n\t";

	print "Example: \n\t";
	print "perl ./api.pl -p http://127.0.0.1/pandora_console/include/api.php -n name_event_create_api -o 1,admin,0,0 -u 1234,admin,pandora\n";
	
    print "\n";
	exit;
}

##############################################################################
# Init screen
##############################################################################
sub tool_api_init () {
    
	print "\nPandora FMS API tool Copyright (c) 2010 Artica ST\n";
	print "This program is Free Software, licensed under the terms of GPL License v2\n";
	print "You can download latest versions and documentation at http://www.pandorafms.org\n\n";

	# Load config file from command line
	help_screen () if ($#ARGV < 0);
	
	help_screen () if (($ARGV[0] eq '-h') || ($ARGV[0] eq '-help'));
   
}

###############################################################################
###############################################################################
# MAIN
###############################################################################
###############################################################################

sub tool_api_main () {
	
	my $api_path;
	my $event_name; 
	my $data_event;
	my $credentials;
	my $api_pass;
	my $db_user;
	my $db_pass;
	my @db_info;
	
	#~ help or api path (required)
	if ($ARGV[0] eq '-h') {
		print "HELP!\n";
		help_screen ();
	} elsif ($ARGV[0] ne '-p') {
		print "Missing API path! See help info...\n";
		help_screen ();
	} else {
		$api_path = $ARGV[1];
	}
	
	#~ event name (required)	
	if ($ARGV[2] ne '-n') {
		print "Missing event name! See help info...\n";
		help_screen ();
	} else {
		$event_name = $ARGV[3];
	}
	
	#~ other event fields (optional)	
	if ($ARGV[4] eq '-o') {
		$data_event = $ARGV[5];
		
		#~ credentials of database
		if ($ARGV[6] eq '-u') {
			$credentials = $ARGV[7];
			@db_info = split(',', $credentials);

			if ($#db_info < 2) {
				print "Invalid database credentials! See help info...\n";
				help_screen ();
			} else {
				$api_pass = $db_info[0];
				$db_user = $db_info[1];
				$db_pass = $db_info[2];

			}
		} else {
			print "Missing database credentials! See help info...\n";
			help_screen ();
		}
	} elsif ($ARGV[4] eq '-u') { #~ credentials of database
		$credentials = $ARGV[5];
	} else {
		print "Missing database credentials! See help info...\n";
		help_screen ();
	}
	
	my @args = @ARGV;
 	my $ltotal=$#args; 

	if ($ltotal < 0) {
		print "[ERROR] No valid arguments\n\n";
		help_screen ();
		exit;
 	}
	else {
		my $call_api = $api_path.'?op=set&op2=create_event&id='.$event_name.'&other='.$data_event.'&other_mode=url_encode_separator_,&apipass='.$api_pass.'&user='.$db_user.'&pass='.$db_pass;
		my $content = get($call_api);
		
		if ($content == undef) {
			print "[ERROR] Not respond or bad syntax.\n\n";
			help_screen();
		} else {
			print "Event ID: $content";
		}
	}

    print "\nExiting!\n\n";

    exit;
}
