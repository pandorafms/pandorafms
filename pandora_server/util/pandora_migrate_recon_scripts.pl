#!/usr/bin/perl

###############################################################################
# Pandora FMS Plugins migrate tool
###############################################################################
# Copyright (c) 2010 Artica Soluciones Tecnologicas S.L
#
# This program is Free Software, licensed under the terms of GPL License v2
###############################################################################

# Includes list
use strict;
use DBI;				# DB interface with MySQL
use POSIX qw(strftime);
use POSIX;
use HTML::Entities;		# Encode or decode strings with HTML entities
use Data::Dumper;
use JSON qw(encode_json);

# Default lib dir for RPM and DEB packages
use lib '/usr/lib/perl5';

use PandoraFMS::Tools;
use PandoraFMS::DB;
use PandoraFMS::Core;
use PandoraFMS::Config;

# migrate plugin tool version
my $version = "1.0";

# Names of the Description fields on the migrated macros
my $ip_desc = 'Target IP';
my $port_desc = 'Port';
my $user_desc = 'Username';
my $pass_desc = 'Password';
my $parameters_desc = 'Plug-in Parameters';

# Pandora server configuration
my %conf;

# Read databases credentials
pandora_load_credentials (\%conf);

# FLUSH in each IO
$| = 0;

# Connect to the DBs
my $dbh = db_connect ('mysql', $conf{'dbname'}, $conf{'dbhost'}, $conf{'dbport'}, $conf{'dbuser'}, $conf{'dbpass'});

# Main
pandora_migrate_recon_main($dbh);

# Cleanup and exit
db_disconnect ($dbh);
exit;

###############################################################################
###############################################################################
# GENERAL FUNCTIONS
###############################################################################
###############################################################################

##############################################################################
# Init screen
##############################################################################
sub pandora_load_credentials ($) {
    my $conf = shift; 
    
    $conf->{"verbosity"}=0;	# Verbose 1 by default
	$conf->{"daemon"}=0;	# Daemon 0 by default
	$conf->{'PID'}="";	# PID file not exist by default
	$conf->{"quiet"}=0;	# Daemon 0 by default


	print "\nPandora FMS Plugins migrate tool $version Copyright (c) 2010-2015 Artica ST\n";
	print "This program is Free Software, licensed under the terms of GPL License v2\n";

	# Load config file from command line
	help_screen () if ($#ARGV < 3);
   
    $conf{'dbname'} = $ARGV[0];
    $conf{'dbhost'} = $ARGV[1];
    $conf{'dbuser'} = $ARGV[2];
    $conf{'dbpass'} = $ARGV[3];
    $conf{'dbport'} = ($#ARGV >= 4 ? $ARGV[4] : 0);
}

##############################################################################
# Print a help screen and exit.
##############################################################################
sub help_screen{
	print "\n[ERROR] No valid arguments\n\n";

	print "Usage: \n\n$0 <dbname> <dbhost> <dbuser> <dbpass> [<dbport>]\n\n";
		
	exit;
}

###############################################################################
###############################################################################
# MAIN
###############################################################################
###############################################################################

sub pandora_migrate_recon_main ($) {
	my ($dbh) = @_;
	
	$|++;
	
	my $migrated_reconscripts = 0;
	my $migrated_recontasks = 0;
	
	print "\n[*] Migrating recon scripts and associated recon tasks.\n\n";
	
	my @recon_scripts = get_db_rows ($dbh, "SELECT * FROM trecon_script WHERE macros = '' OR macros IS NULL");
	
	my $macros_base;

	for (my $i=1; $i <= 4; $i++) {
		$macros_base->{$i}{'macro'} = '_field' . $i . '_';
		$macros_base->{$i}{'desc'} = 'Script field #' . $i;
		$macros_base->{$i}{'help'} = '';
		$macros_base->{$i}{'hide'} = '';
		$macros_base->{$i}{'value'} = '';
	}
	
	my $macros_base_json = encode_json($macros_base);
	
	foreach my $recon_script (@recon_scripts) {
		# Insert macros and parameters in the plugin
		db_update ($dbh, "UPDATE trecon_script SET `macros` = '$macros_base_json' WHERE `id_recon_script` = '".$recon_script->{'id_recon_script'}."'");
		$migrated_reconscripts ++;
		
		# Get the recon tasks created with each recon script
		my @recon_tasks = get_db_rows ($dbh, "SELECT * FROM trecon_task WHERE id_recon_script = '".$recon_script->{'id_recon_script'}."' AND (macros = '' OR macros IS NULL)");
		my $macros_rt = $macros_base;

		foreach my $recon_task (@recon_tasks) {
			for (my $i=1; $i <= 4; $i++) {
				$macros_rt->{$i}{'value'} = $recon_task->{'field' . $i};
			}
			
			my $macros_rt_json = encode_json($macros_rt);
			
			db_update ($dbh, "UPDATE trecon_task SET `macros` = '$macros_rt_json' WHERE `id_rt` = '".$recon_task->{'id_rt'}."'");
			$migrated_recontasks ++;

		}
	}
	
	print "\n[*] $migrated_reconscripts recon scripts migrated.\n";
	print "\n[*] $migrated_recontasks recon tasks migrated.\n";
	
    exit;
}
