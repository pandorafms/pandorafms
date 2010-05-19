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
use Time::Local;		# DateTime basic manipulation
use DBI;				# DB interface with MySQL
use PandoraFMS::Tools;
use PandoraFMS::DB;
use POSIX qw(strftime);

# version: define current version
my $version = "3.1 PS100519";

# Pandora server configuration
my %conf;

# FLUSH in each IO
$| = 0;

# Init
pandora_init(\%conf);

# Read config file
pandora_load_config (\%conf);

# Load enterprise module
if (enterprise_load (\%conf) == 0) {
	print "[*] Pandora FMS Enterprise module not available.\n\n";
} else {
	print "[*] Pandora FMS Enterprise module loaded.\n\n";
}

# Connect to the DB
my $dbh = db_connect ('mysql', $conf{'dbname'}, $conf{'dbhost'}, '3306', $conf{'dbuser'}, $conf{'dbpass'});
my $history_dbh = ($conf{'_history_db_enabled'} eq '1') ? db_connect ('mysql', $conf{'_history_db_name'},
		$conf{'_history_db_host'}, '3306', $conf{'_history_db_user'}, $conf{'_history_db_pass'}) : undef;

# Main
pandora_manage_main(\%conf, $dbh, $history_dbh);

# Cleanup and exit
db_disconnect ($history_dbh) if defined ($history_dbh);
db_disconnect ($dbh);
exit;

###############################################################################
# Disable / Enable alert system globally
###############################################################################
sub pandora_disable_alerts ($$$) {
	my ($conf, $dbh, $alert_mode) = @_;

    # Alert_mode can be 0 (disable all) or 1 (enable all).

	# This works by disabling alerts in each defined group
    # If you have previously a group with alert disabled, and you disable 
    # alerts globally, when enabled it again, it will enabled also !
    
    if (!is_numeric($alert_mode)){
        print "[ERR] Invalid alert_mode syntax \n\n";
        exit;
    }

    if ($alert_mode == 0){
    	print "[INFO] Disabling all alerts \n\n";
		db_do ($dbh, "UPDATE tgrupo SET disabled = 1");
    }
    else {
    	print "[INFO] Enabling all alerts \n\n";
		db_do ($dbh, "UPDATE tgrupo SET disabled = 0");
    }

    exit;
}

###############################################################################
# Disable enterprise ACL
###############################################################################
sub pandora_disable_eacl ($$$) {
	my ($conf, $dbh, $mode) = @_;

    if ($mode == 0){
       	print "[INFO] Disabling Enterprise ACL system (system wide)\n\n";
    	db_do ($dbh, "UPDATE tconfig SET `value` ='0' WHERE `token` = 'acl_enterprise'");
    } else {
       	print "[INFO] Enabling Enterprise ACL system (system wide)\n\n";
    	db_do ($dbh, "UPDATE tconfig SET `value` ='1' WHERE `token` = 'acl_enterprise'");
    }
    exit;
}

# Disable a entire group
###############################################################################
sub pandora_disable_group ($$$$) {
        my ($conf, $dbh, $mode, $group) = @_;

	
    if ($mode == 0){ # Disable
        print "[INFO] Disabling group $group\n\n";
	if ($group == 1){
		db_do ($dbh, "UPDATE tagente SET disabled = 1");
	}
	else {
		db_do ($dbh, "UPDATE tagente SET disabled = 1 WHERE id_grupo = $group");
	}
    } else {
        print "[INFO] Enabling group $group\n\n";
        if ($group == 1){
                db_do ($dbh, "UPDATE tagente SET disabled = 0");
        }
        else {  
                db_do ($dbh, "UPDATE tagente SET disabled = 0 WHERE id_grupo = $group");
        }
    }
    exit;
}


##############################################################################
# Read external configuration file.
##############################################################################
sub pandora_load_config ($) {
	my $conf = shift;

	# Read conf file
	open (CFG, '< ' . $conf->{'_pandora_path'}) or die ("[ERROR] Could not open configuration file: $!\n");
	while (my $line = <CFG>){
		next unless ($line =~ m/([\w-_\.]+)\s([0-9\w-_\.\/\?\&\=\)\(\_\-\\*\@\#\%\$\~\"\']+)/);
		$conf->{$1} = $2;
	}
 	close (CFG);

	# Check conf tokens
 	foreach my $param ('dbuser', 'dbpass', 'dbname', 'dbhost', 'log_file') {
		die ("[ERROR] Bad config values. Make sure " . $conf->{'_pandora_path'} . " is a valid config file.\n\n") unless defined ($conf->{$param});
 	}

	# Read additional tokens from the DB
	my $dbh = db_connect ('mysql', $conf->{'dbname'}, $conf->{'dbhost'}, '3306', $conf->{'dbuser'}, $conf->{'dbpass'});
	$conf->{'_event_purge'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'event_purge'");
	db_disconnect ($dbh);
}


##############################################################################
# Init screen
##############################################################################
sub pandora_init ($) {
    my $conf = shift;    

	print "\nPandora FMS Manage tool $version Copyright (c) 2010 Artica ST\n";
	print "This program is Free Software, licensed under the terms of GPL License v2\n";
	print "You can download latest versions and documentation at http://www.pandorafms.org\n\n";

	# Load config file from command line
	help_screen () if ($#ARGV < 0);
   
        $conf->{'_pandora_path'} = $ARGV[0];

	help_screen () if ($conf->{'_pandora_path'} eq '');
}


##############################################################################
# Print a help screen and exit.
##############################################################################
sub help_screen{
	print "Usage: $0 <path to pandora_server.conf> [options] \n\n";
	print "Available options:\n\n";
	print "\t--disable_alerts      Disable alerts in all groups.\n";
	print "\t--enable_alerts       Enable alerts in all groups\n";
	print "\t--disable_eacl        Disable enterprise ACL system\n";
	print "\t--enable_eacl         Enable enterprise ACL system\n";
        print "\t--disable_group <id>  Disable agents from an entire group (Use group 1 for all)\n";
        print "\t--enable_group <id>   Enable agents from an entire group (1 for all) \n";
        print "\n";
	exit;
}

###############################################################################
# Main
###############################################################################
sub pandora_manage_main ($$$) {
	my ($conf, $dbh, $history_dbh) = @_;

	my @args = @ARGV;
 	my $param;
 	my $ltotal=$#args; 
	my $ax;

 	# Has read setup file ok ?
 	if ( $ltotal == 0 ) {
		print "[ERROR] No valid arguments";
		help_screen();
		exit;
 	}
 
 	for ($ax=0;$ax<=$ltotal;$ax++){
		$param = $args[$ax];

		# help!
		help_screen () if ($param =~ m/--*h\w*\z/i );

		if ($param =~ m/--disable_alerts\z/i) {
	            pandora_disable_alerts ($conf, $dbh, 0);
	        }
		elsif ($param =~ m/--enable_alerts\z/i) {
	            pandora_disable_alerts ($conf, $dbh, 1);
		} 
		elsif ($param =~ m/--disable_eacl\z/i) {
	            pandora_disable_eacl ($conf, $dbh, 0);
		} 
		elsif ($param =~ m/--enable_eacl\z/i) {
                    pandora_disable_eacl ($conf, $dbh, 1);
		} 
                elsif ($param =~ m/--disable_group/i) {
			pandora_disable_group ($conf, $dbh, 0, $args[$ax+1]);
		}
		elsif ($param =~ m/--enable_group/i) {
                        pandora_disable_group ($conf, $dbh, 1, $args[$ax+1]);
		}
	}

     print "[W] Nothing to do. Exiting !\n\n";

    exit;
}

