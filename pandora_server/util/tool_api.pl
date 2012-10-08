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

	print "CREATE EVENT:\n\n";
	print "Usage: perl $0 -p <path to pandora console API> -u <credentials> -create_event -name <event_name> -group <id_group> -type <event_type> [-agent <id_agent>] [-user <id_user>] [-status <status>] [-am <id_agent_module>] [-alert <id_alert_am>] [-criticity <criticity>] [-comment <user_comment>] [-tag <tags>] [-source <source>] [-extra <id_extra>] [-c_instructions <critical_instructions>] [-w_instructions <warning_instructions>] [-u_instructions <unknown_instructions>] [-owner <owner_user>] \n\n";
    print "Call syntax create_event: \n\t";
	print "<credentials>: Credentials of API and database separated by comma (required). In this order\n\t\t";
	print "<api_pass>,<db_user>,<db_pass>\n\n";

	print "EXAMPLE: \n\t";
	print "perl tool_api.pl -p http://localhost/pandora_console/include/api.php -u 1234,admin,pandora -create_event -name \"Event name\" -group 2 -type \"system\" -agent 2 -user \"admin\" -status 0 -am 0 -alert 9 -criticity 3 -comment \"User comments\" -tag \"tags\" -source \"Pandora\" -extra 3 -c_instructions \"Critical instructions\" -w_instructions \"Warning instructions\" -u_instructions \"Unknown instructions\" -owner \"other\" ";
    print "\n\n\n";
    
    print "VALIDATE EVENT\n\n";
	print "Usage: perl $0 -p <path to pandora console API> -u <credentials> -validate_event -id <id_event>\n\n";
    print "Call syntax validate_event: \n\t";
	print "<credentials>: Credentials of API and database separated by comma (required). In this order\n\t\t";
	print "<api_pass>,<db_user>,<db_pass>\n\n";

	print "EXAMPLE: \n\t";
	print "perl tool_api.pl -p http://localhost/pandora_console/include/api.php -u 1234,admin,pandora -validate_event -id 234";
    print "\n\n\n";
	exit;
}

##############################################################################
# Init screen
##############################################################################
sub tool_api_init () {
    
	print "\nPandora FMS API tool Copyright (c) 2010 Artica ST\n";
	print "This program is Free Software, licensed under the terms of GPL License v2\n";
	print "You can download latest versions and documentation at http://www.pandorafms.org\n\n";

	if ($#ARGV < 0) {
		help_screen();
	}
	
	if (($ARGV[0] eq '-h') || ($ARGV[0] eq '-help')) {
		help_screen();
	}
   
}

###############################################################################
###############################################################################
# MAIN
###############################################################################
###############################################################################

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
	my $criticity = '';
	my $user_comment = '';
	my $tags = '';
	my $source = '';
	my $id_extra = '';
	my $critical_instructions = '';
	my $warning_instructions = '';
	my $unknown_instructions = '';
	my $owner_user = '';
	my $id_event;
	my $option = $ARGV[4];
	my $call_api;

	#~ help or api path (required)
	if ($ARGV[0] eq '-h') {
		print "HELP!\n";
		help_screen();
	} elsif ($ARGV[0] ne '-p') {
		print "[ERROR] Missing API path! Read help info:\n\n";
		help_screen ();
	} else {
		$api_path = $ARGV[1];
	}
	
	#~ credentials of database
	if ($ARGV[2] eq '-u') {
		$credentials = $ARGV[3];
		@db_info = split(',', $credentials);
		
		if ($#db_info < 2) {
			print "[ERROR] Invalid database credentials! Read help info:\n\n";
			help_screen();
		} else {
			$api_pass = $db_info[0];
			$db_user = $db_info[1];
			$db_pass = $db_info[2];
		}
	} else {
		print "[ERROR] Missing database credentials! Read help info:\n\n";
		help_screen ();
	}
	
	if ($ARGV[4] eq '-create_event') {
		#~ event name (required)	
		if ($ARGV[5] ne '-name') {
			print "[ERROR] Missing event name! Read help info:\n\n";
			help_screen ();
		} else {
			$event_name = $ARGV[6];
		}
		
		#~ id group (required)	
		if ($ARGV[7] ne '-group') {
			print "[ERROR] Missing event group! Read help info:\n\n";
			help_screen ();
		} else {
			$id_group = $ARGV[8];
			$data_event = $id_group;
		}
		
		#~ id group (required)
		if ($ARGV[9] ne '-type') {
			print "[ERROR] Missing event type! Read help info:\n\n";
			help_screen ();
		} else {
			$event_type = $ARGV[10];
			$data_event .= ",".$event_type;
		}

		my $i = 0;
		foreach (@ARGV) {
			my $line = $_;
			if ($line eq '-agent') {
				$id_agent = $ARGV[$i+1];
			}
			if ($line eq '-user') {
				$id_user = $ARGV[$i+1];
			}
			if ($line eq '-status') {
				$status = $ARGV[$i+1];
			}
			if ($line eq '-am') {
				$id_agent_module = $ARGV[$i+1];
			}
			if ($line eq '-alert') {
				$id_alert_am = $ARGV[$i+1];
			}
			if ($line eq '-criticity') {
				$criticity = $ARGV[$i+1];
			}
			if ($line eq '-comment') {
				$user_comment = $ARGV[$i+1];
			}
			if ($line eq '-tag') {
				$tags = $ARGV[$i+1];
			}
			if ($line eq '-source') {
				$source = $ARGV[$i+1];
			}
			if ($line eq '-extra') {
				$id_extra = $ARGV[$i+1];
			}
			if ($line eq '-c_instructions') {
				$critical_instructions = $ARGV[$i+1];
			}
			if ($line eq '-w_instructions') {
				$warning_instructions = $ARGV[$i+1];
			}
			if ($line eq '-u_instructions') {
				$unknown_instructions = $ARGV[$i+1];
			}
			if ($line eq '-owner') {
				$owner_user = $ARGV[$i+1];
			}
			$i++;
		}

		$data_event .= ",".$id_agent.",".$id_user.",".$status.",".$id_agent_module.",".$id_alert_am.",".$criticity.",".$user_comment.",".$tags.",".$source.",".$id_extra.",".$critical_instructions.",".$warning_instructions.",".$unknown_instructions.",".$owner_user;
		$call_api = $api_path.'?op=set&op2=create_event&id='.$event_name.'&other='.$data_event.'&other_mode=url_encode_separator_,&apipass='.$api_pass.'&user='.$db_user.'&pass='.$db_pass;
	
	} elsif ($ARGV[4] eq '-validate_event') {
		#~ id event(required)	
		if ($ARGV[5] ne '-id') {
			print "[ERROR] Missing id event! Read help info:\n\n";
			help_screen ();
		} else {
			$id_event = $ARGV[6];
		}
		
		$call_api = $api_path.'?op=set&op2=validate_event_by_id&id='.$id_event.'&apipass='.$api_pass.'&user='.$db_user.'&pass='.$db_pass;
	} 
	
	my @args = @ARGV;
 	my $ltotal=$#args; 

	if ($ltotal < 0) {
		print "[ERROR] No valid arguments. Read help info:\n\n";
		help_screen ();
		exit;
 	}
	else {
		my $content = get($call_api);
		
		if ($option eq '-create_event') {
			if ($content eq undef) {
				print "[ERROR] Not respond or bad syntax. Read help info:\n\n";
				help_screen();
			} else {
				print "Event ID: $content";
			}
		} elsif ($option eq '-validate_event') {
			print "[RESULT] $content";
		}
	}

    print "\nExiting!\n\n";

    exit;
}
