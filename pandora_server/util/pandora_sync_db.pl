#!/usr/bin/perl

###############################################################################
# Pandora FMS Database Synchronization Tool
###############################################################################
# Copyright (c) 2010 Artica Soluciones Tecnologicas S.L
#
# This code is not free or OpenSource. Please don't redistribute.
###############################################################################

# Includes list
use strict;
use DBI;				# DB interface with MySQL
use POSIX qw(strftime);
use POSIX;
use HTML::Entities;		# Encode or decode strings with HTML entities

# Default lib dir for RPM and DEB packages
use lib '/usr/lib/perl5';

use PandoraFMS::Tools;
use PandoraFMS::DB;
use PandoraFMS::Core;
use PandoraFMS::Config;

# Pandora server configuration
my %conf;

# Errors counters
my $errors_agents;
my $errors_modules;
my $errors_servers;
my $errors_exportservers;

# Read databases credentials
pandora_load_credentials (\%conf);

# Synchronize data flag
my $sync_data;
if(defined $ARGV[8]) {
	$sync_data = $ARGV[8];
}
else {
	$sync_data = 0;
}

# Pandora database tables
my $tables_data = enterprise_hook('sync_store_tables', [$sync_data]);
my @tables_data = @{$tables_data};

# FLUSH in each IO
$| = 0;

# Connect to the DBs
my $dbh_source = db_connect ('mysql', $conf{'dbname_source'}, $conf{'dbhost_source'}, '3306', $conf{'dbuser_source'}, $conf{'dbpass_source'});
my $dbh_dest = db_connect ('mysql', $conf{'dbname_dest'}, $conf{'dbhost_dest'}, '3306', $conf{'dbuser_dest'}, $conf{'dbpass_dest'});

my $history_dbh = ($conf{'_history_db_enabled'} eq '1') ? db_connect ('mysql', $conf{'_history_db_name'},
		$conf{'_history_db_host'}, '3306', $conf{'_history_db_user'}, $conf{'_history_db_pass'}) : undef;

# Build the comparation arrays (the id_agents must be compare first, doesnt touch)
my $id_agent_comparation = enterprise_hook('sync_compare_id_agents', [$dbh_source, $dbh_dest, \$errors_agents]);
my @id_agent_comparation = @{$id_agent_comparation};
my $id_agentmodule_comparation = enterprise_hook('sync_compare_id_agent_modules', [$dbh_source, $dbh_dest, $id_agent_comparation, \$errors_modules]);
my @id_agentmodule_comparation = @{$id_agentmodule_comparation};
my $id_server_export_comparation = enterprise_hook('sync_compare_id_server_export', [$dbh_source, $dbh_dest, \$errors_exportservers]);
my @id_server_export_comparation = @{$id_server_export_comparation};
my $id_server_comparation = enterprise_hook('sync_compare_id_server', [$dbh_source, $dbh_dest, \$errors_servers]);
my @id_server_comparation = @{$id_server_comparation};

# Main
pandora_sync_main($dbh_source, $dbh_dest, $history_dbh);

# Cleanup and exit
db_disconnect ($history_dbh) if defined ($history_dbh);
db_disconnect ($dbh_source);
db_disconnect ($dbh_dest);
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


	print "\nCopyright (c) 2010 Artica Soluciones Tecnologicas S.L\n";
	print "This code is not free or OpenSource. Please don't redistribute.\n\n";
	
	# Load enterprise module
	if (enterprise_load (\%conf) == 0) {
		print "[*] Pandora FMS Enterprise module not available. The execution must be finish \n\n";
		exit;
	} else {
		print "[*] Pandora FMS Enterprise module loaded.\n\n";
	}

	# Load config file from command line
	help_screen () if ($#ARGV < 7);
   
    $conf{'dbname_source'} = $ARGV[0];
    $conf{'dbhost_source'} = $ARGV[1];
    $conf{'dbuser_source'} = $ARGV[2];
    $conf{'dbpass_source'} = $ARGV[3];
    $conf{'dbname_dest'} = $ARGV[4];
    $conf{'dbhost_dest'} = $ARGV[5];
    $conf{'dbuser_dest'} = $ARGV[6];
    $conf{'dbpass_dest'} = $ARGV[7];
    
}

##########################################################################
## Delete all the values of a table.
##########################################################################
sub empty_table ($$) {
	my ($dbh, $table_name) = @_;

	return db_do ($dbh, "DELETE FROM $table_name");
}

##############################################################################
# Print a help screen and exit.
##############################################################################
sub help_screen{
	print "\n[ERROR] No valid arguments\n\n";

	print "Usage: \n\n$0 <dbname_source> <dbhost_source> <dbuser_source> <dbpass_source> <dbname_destination> <dbhost_destination> <dbuser_destination> <dbpass_destination> [<sync_data>*]\n\n";
	
	print "* Set sync_data parameter to 0 (or not set) to synchronize structures only and set to 1 to synchronize strutures and data\n\n";
	
	exit;
}

###############################################################################
###############################################################################
# MAIN
###############################################################################
###############################################################################

sub pandora_sync_main ($$$) {
	my ($dbh_source, $dbh_dest, $history_dbh) = @_;
	
	$|++;
	my $success = 0;
	my $percent;
	my @columns;
	my @types;
	
	print "\n[*] Cleaning destination database.\n";

	for(my $i = 0; $i <= $#{$tables_data[0]}; $i++) {
		empty_table($dbh_dest, $tables_data[0]->[$i]) unless !defined $tables_data[0]->[$i];
		
		$|++;
		$success++;
		$percent = int(($success / $#{$tables_data[0]}) * 100);

		if($percent > 9) {
			print "\b";
		}
		if($percent > 99) {
			print "\b";
		}
		
		print "\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b\b[*] $percent % Completed" unless $percent > 100;
	}
		
	# Mixed cases	
	empty_table($dbh_dest, 'tlayout_data');
	empty_table($dbh_dest, 'tevento');
	
	print "\n\n";
	
	for(my $i = 0; $i <= $#{$tables_data[0]}; $i++) {
		enterprise_hook('sync_clone_table', [$dbh_source, $dbh_dest, $tables_data[0]->[$i], $tables_data[1]->[$i], $tables_data[2]->[$i], @id_agent_comparation, @id_agentmodule_comparation, @id_server_export_comparation, @id_server_comparation]) unless !defined $tables_data[0]->[$i];
	}
	
	# Mixed cases	
	@columns = ('id_agente_modulo', 'id_agent');
	@types = ('module', 'agent');
	enterprise_hook('sync_clone_table', [$dbh_source, $dbh_dest, 'tlayout_data', \@columns, \@types, @id_agent_comparation, @id_agentmodule_comparation, @id_server_export_comparation, @id_server_comparation]);
	@columns = ('id_agentmodule', 'id_agente');
	@types = ('module', 'agent');
	enterprise_hook('sync_clone_table', [$dbh_source, $dbh_dest, 'tevento', \@columns, \@types, @id_agent_comparation, @id_agentmodule_comparation, @id_server_export_comparation, @id_server_comparation]);

	my $errors = $errors_agents + $errors_modules + $errors_servers + $errors_exportservers;

	if($errors == 0) {
		print "\n[*] Nothing to do. Exiting !\n\n";
	}
	else {
		print "\n[W] $errors errors in synchronization.\n\n";
	
		print "Summary: \n";
		if($errors_agents > 0) {
			print "- $errors_agents Agents unsynchronized.\n";
		}
		if($errors_modules > 0) {
			print "- $errors_modules Modules unsynchronized.\n";
		}
		if($errors_servers > 0) {
			print "- $errors_servers Servers unsynchronized.\n";
		}
		if($errors_exportservers > 0) {
			print "- $errors_exportservers Export servers unsynchronized.\n";
		}
		print "\nTo more information view the log file /var/log/pandora/pandora_sync.error\n\n";
	}

    exit;
}
