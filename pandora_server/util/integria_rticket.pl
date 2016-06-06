#!/usr/bin/perl

########################################################################
# Integria IMS - Remote Ticket Tool (via WEB API) 
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

	$0 -p <path_to_integria_console_API> -c <credentials> -create_ticket <options> 

Where options:

	-u <credentials>
	-create_ticket 
	-name <ticket_name>		: Free text
	-group <id_group>		: Group ID (use 0 for 'all')
	
Optional parameters:
	
	[-priority <priority>]		: 10 Maintance, 0 Informative, 1 Low, 2 Medium, 3 Serious, 4 Very serious
	[-desc <description>]		: Free text
	[-type <ticket_type>]		: Type ID (must exist in Integria IMS)
	[-inventory <inventory_id>]	: Inventory ID (must exist in Integria IMS)
	[-email <email_copy>]		: 1 or 0\n\n";
	
	print "Credential/API syntax: \n\n";
	print "<credentials>: API credentials separated by comma: <api_pass>,<user>,<user_pass>\n\n";
	
	print "Example of ticket generation:\n\n";
	
	print "\t$0 -p http://localhost/integria/include/api.php -u 1234,admin,1234 \
	\t-create_ticket -name \"SampleTicket\" -group 1 -priority 2 -desc \"This is a sample\" \
	\t-type 4 -inventory 6 -email 1";
	print "\n\n\n";
	exit;
}

##############################################################################
# Init screen
##############################################################################
sub tool_api_init () {
	
	print "\nIntegria IMS Remote Ticket Tool Copyright (c) 2013-2015 Artica ST\n";
	print "This program is Free Software, licensed under the terms of GPL License v2\n";
	print "You can download latest versions and documentation at http://www.integriaims.com\n\n";
	
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
	my $credentials;
	my $api_pass;
	my $db_user;
	my @db_info;

	my $ticket_name = "";
	my $group_id = -1;
	my $ticket_priority = 0;
	my $ticket_description = '';
	my $ticket_type = '';
	my $ticket_inventory = '';
	my $email_copy = 0;

	my $option = $ARGV[4];
	my $data_ticket;
	my $call_api;
	
	#~ help or api path (required)
	if ($ARGV[0] eq '-h') {
		print "HELP!\n";
		help_screen();
	}
	elsif ($ARGV[0] ne '-p') {
		print "[ERROR] Missing API path! Read help info:\n\n";
		help_screen();
		exit;
	}
	else {
		$api_path = $ARGV[1];
	}
	
	#~ credentials of database
	if ($ARGV[2] eq '-u') {
		$credentials = $ARGV[3];
		@db_info = split(',', $credentials);
		
		if ($#db_info < 1) {
			print "[ERROR] Invalid database credentials! Read help info:\n\n";
			help_screen();
		}
		else {
			$api_pass = $db_info[0];
			$db_user = $db_info[1];
			$db_user_pass = $db_info[2];
		}
	}
	else {
		print "[ERROR] Missing database credentials! Read help info:\n\n";
		help_screen();
		exit;
	}
	
	if ($option eq '-create_ticket') {
		my $i = 0;
		foreach (@ARGV) {
			my $line = $_;
			
			#-------------------DEBUG--------------------
			#print("i " . $i .  " line " . $line . "\n");

			if ($line eq '-name') {
				$ticket_name = $ARGV[$i + 1];
			}
			if ($line eq '-group') {
				$group_id = $ARGV[$i + 1];
			}
			if ($line eq '-priority') {
				$ticket_priority = $ARGV[$i + 1];
			}
			if ($line eq '-desc') {
				$ticket_description = $ARGV[$i + 1];
			}
			if ($line eq '-type') {
				$ticket_type = $ARGV[$i + 1];
			}
			if ($line eq '-inventory') {
				$ticket_inventory = $ARGV[$i + 1];
			}
			if ($line eq '-email') {
				$email_copy = $ARGV[$i + 1];
			}
			
			$i++;
		}
		
		if ($ticket_name eq "") {
			print "[ERROR] Missing ticket name! Read help info:\n\n";
			help_screen();
			exit;
		}
		if ($group_id == -1) {
			print "[ERROR] Missing ticket group! Read help info:\n\n";
			help_screen();
			exit;
		}
		
		#~ $data_ticket = $ticket_name .
			#~ "|;|" . $group_id .
			#~ "|;|" . $ticket_priority .
			#~ "|;|" . $ticket_description .
			#~ "|;|" . $ticket_inventory .
			#~ "|;|" . $ticket_type .
			#~ "|;|" . $email_copy;
		
		$data_ticket = $ticket_name .
				"|;|" . $group_id .
				"|;|" . $ticket_priority .
				"|;|" . $ticket_description .
				"|;|" . $ticket_inventory .
				"|;|" . $ticket_type .
				"|;|" . $email_copy .
				"|;|" . $email_copy .
				"|;|" . 
				"|;|" . '1' .
				"|;|" . 
				"|;|";
		$call_api = $api_path . '?' .
			'user=' . $integria_user . '&' .
			'user_pass=' . $user_pass . '&' .
			'pass=' . $api_pass . '&' .
			'op=create_incident&' .
			'params=' . $data_ticket .'&' .
			'token=|;|';
		
	}
	else {
		print "[ERROR] No valid option selected! Read help info:\n\n";
		help_screen();
		exit;
	} 
	
	my @args = @ARGV;
	my $ltotal = $#args; 
	
	if ($ltotal < 0) {
		print "[ERROR] No valid arguments. Read help info:\n\n";
		help_screen();
		exit;
 	}
	else {
		#-----------DEBUG------------
		#print($call_api . "\n\n\n");
		
		my $content = get($call_api);
		
		#-----------DEBUG-----------
		#print($content . "\n\n\n");
		
		if ($option eq '-create_ticket') {
			if ($content eq undef) {
				print "[ERROR] Not respond or bad syntax. Read help info:\n\n";
				help_screen();
				exit;
			}
			else {
				print "Ticket ID: $content";
			}
		}
		else {
			print "[ERROR] No valid option selected!";
		}
	}
	
	print "\nExiting!\n\n";
	
	exit;
}
