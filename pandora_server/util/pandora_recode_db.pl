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

# Recode tool version
my $version = "1.0";

# Pandora server configuration
my %conf;

# Errors counters
my $errors_agents;
my $errors_modules;
my $errors_servers;
my $errors_exportservers;

# Read databases credentials
pandora_load_credentials (\%conf);

# Pandora database tables
my $tables_data = recode_store_tables();
my @tables_data = @{$tables_data};

# FLUSH in each IO
$| = 0;

# Connect to the DBs
my $dbh = db_connect ('mysql', $conf{'dbname'}, $conf{'dbhost'}, $conf{'dbport'}, $conf{'dbuser'}, $conf{'dbpass'});

my $history_dbh = ($conf{'_history_db_enabled'} eq '1') ? db_connect ('mysql', $conf{'_history_db_name'},
		$conf{'_history_db_host'}, '3306', $conf{'_history_db_user'}, $conf{'_history_db_pass'}) : undef;

# Main
pandora_recode_main($dbh, $dbh, $history_dbh);

# Cleanup and exit
db_disconnect ($history_dbh) if defined ($history_dbh);
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


	print "\nPandora FMS Recode tool $version Copyright (c) 2010 Artica ST\n";
	print "This program is Free Software, licensed under the terms of GPL License v2\n";
	print "You can download latest versions and documentation at http://www.pandorafms.org\n\n";

	# Load config file from command line
	help_screen () if ($#ARGV < 3);
   
    $conf{'dbname'} = $ARGV[0];
    $conf{'dbhost'} = $ARGV[1];
    $conf{'dbuser'} = $ARGV[2];
    $conf{'dbpass'} = $ARGV[3];
    
}

sub recode_store_tables() {
	# Storing tables names			
	my @tables = ('tagente', 'tagente_modulo', 'tserver', 'tmodule', 'tperfil', 'tgrupo', 'tplugin', 'treport', 'tpolicies', 'talert_templates',
					'talert_actions', 'ttipo_modulo', 'tconfig_os', 'tpolicy_modules');
						
	my @columns = ('nombre', 'nombre', 'name', 'name', 'name', 'nombre', 'name', 'name', 'name', 'name',
					'name', 'nombre', 'name', 'name');

	my @data = (\@tables, \@columns);

	return \@data;
}

##########################################################################
## Recode specific value of a table.
##########################################################################
sub recode_table ($$$) {
	my ($dbh, $table, $column) = @_;
	my $encoded_values = 0;
	
	my @tablestatus = get_db_rows ($dbh, "SHOW TABLE STATUS WHERE name = '$table'");
		
	if($#tablestatus == -1) {
		return 0;
	}
	
	my @rows = get_db_rows ($dbh, "SELECT $column FROM $table");
	
	foreach my $row (@rows) {
		my $coded_column = safe_input(safe_output($row->{$column}));
		if($row->{$column} ne $coded_column) {
			$encoded_values ++;
			if($encoded_values == 1) {
				print "Recoding Column '$column' of Table '$table'.";
			}
			else {
				print ".";
			}
			db_update ($dbh, "UPDATE $table SET `$column` = '$coded_column' WHERE `$column` = '$row->{$column}'");
		}
	}
	if($encoded_values > 0) {
		print "\n";
	}
	
	return $encoded_values;
}

##############################################################################
# Print a help screen and exit.
##############################################################################
sub help_screen{
	print "\n[ERROR] No valid arguments\n\n";

	print "Usage: \n\n$0 <dbname> <dbhost> <dbuser> <dbpass> \n\n";
		
	exit;
}


##########################################################################
# SUB ascii_to_html (string)
# Convert an ascii string to hexadecimal
##########################################################################

sub ascii_to_html($) {
        my $ascii = shift;

        return "&#x".substr(unpack("H*", pack("N", $ascii)),6,3).";";
}

###############################################################################
###############################################################################
# MAIN
###############################################################################
###############################################################################

sub pandora_recode_main ($$$) {
	my ($dbh_source, $dbh_dest, $history_dbh) = @_;
	my $encoded_values;
	
	$|++;
	my $success = 0;
	my $percent;
	my @columns;
	my @types;
	
	print "\n[*] Recoding destination database.\n\n";

	for(my $i = 0; $i <= $#{$tables_data[0]}; $i++) {
		$encoded_values = $encoded_values + recode_table($dbh,$tables_data[0]->[$i],$tables_data[1]->[$i]);
	}
	
	print "\n$encoded_values values recoded. \n\n";

    exit;
}
