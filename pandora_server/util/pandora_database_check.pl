#!/usr/bin/perl

###############################################################################
# Pandora FMS DB Speed test
###############################################################################
# Copyright (c) 2012 Artica Soluciones Tecnologicas S.L
# Copyright (c) 2012 Sancho Lerena
# This is a small tool to check specific performance of Pandora database
# return the total time needed in microseconds to perform a specific Pandora
# tests on database to measure speed in database access from "typical" and
# usual operations.
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation;  version 2
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301,USA
###############################################################################

# Includes list
use strict;
use Time::Local;		# DateTime basic manipulation
use DBI;				# DB interface with MySQL
use POSIX qw(strftime);

use Time::HiRes qw( clock_gettime clock ) ;

# Default lib dir for RPM and DEB packages
use lib '/usr/lib/perl5';

use PandoraFMS::Tools;
use PandoraFMS::DB;
use PandoraFMS::Core;

# version: define current version
my $version = "4.0.2 PS121120";

# Pandora server configuration
my %conf;

# FLUSH in each IO
$| = 0;

# Init
pandora_init(\%conf);

# Read config file
pandora_load_config (\%conf);

# Connect to the DB
my $dbh = db_connect ('mysql', $conf{'dbname'}, $conf{'dbhost'}, $conf{'dbport'}, $conf{'dbuser'}, $conf{'dbpass'});
my $history_dbh = ($conf{'_history_db_enabled'} eq '1') ? db_connect ('mysql', $conf{'_history_db_name'},
		$conf{'_history_db_host'}, '3306', $conf{'_history_db_user'}, $conf{'_history_db_pass'}) : undef;

$conf{'activate_gis'}=0;

pandora_speedtest_main (\%conf, $dbh, $history_dbh);

##############################################################################
# Check command line parameters.
##############################################################################
sub pandora_init ($) {
	my $conf = shift;

	# Load config file from command line
	help_screen () if ($#ARGV < 0);
	
	$conf->{'_pandora_path'} = shift(@ARGV);
	
	# If there are valid parameters store it
	foreach my $param (@ARGV) {	
		# help!
		help_screen () if ($param =~ m/--*h\w*\z/i );
		if ($param =~ m/-v\z/i) {
			$conf->{'_verbose'} = 1;
		}
	}

	help_screen () if ($conf->{'_pandora_path'} eq '');
}

##############################################################################
# Read external configuration file.
##############################################################################
sub pandora_load_config ($) {
	my $conf = shift;

	# Read conf file
	open (CFG, '< ' . $conf->{'_pandora_path'}) or die ("[ERROR] Could not open configuration file: $!\n");
	while (my $line = <CFG>){
		next unless ($line =~ /^(\S+)\s+(.*)\s+$/);
		$conf->{$1} =  clean_blank($2);
	}
 	close (CFG);

	# Check conf tokens
 	foreach my $param ('dbuser', 'dbpass', 'dbname', 'dbhost', 'log_file') {
		die ("[ERROR] Bad config values. Make sure " . $conf->{'_pandora_path'} . " is a valid config file.\n\n") unless defined ($conf->{$param});
 	}
	$conf->{'dbport'} = '3306' unless defined ($conf->{'dbport'});
}

##########################################################################
# Process module data, creating module if necessary.
##########################################################################
sub process_module_data ($$$$$$$$$) {
	my ($pa_config, $data, $server_id, $agent_name,
		$module_name, $module_type, $interval, $timestamp,
		$dbh) = @_;

	# Get agent data
	my $agent = get_db_single_row ($dbh, 'SELECT * FROM tagente WHERE nombre = ?', safe_input($agent_name));
	if (! defined ($agent)) {
		logger($pa_config, "Invalid agent '$agent_name' for module '$module_name'.", 3);
		return;
	}

	# Get module parameters, matching column names in tagente_modulo
	my $module_conf;
	
	# Supported tags
	my $tags = {'name' => 0, 'data' => 0, 'type' => 0, 'description' => 0, 'max' => 0,
	            'min' => 0, 'descripcion' => 0, 'post_process' => 0, 'module_interval' => 0, 'min_critical' => 0,
	            'max_critical' => 0, 'min_warning' => 0, 'max_warning' => 0, 'disabled' => 0, 'min_ff_event' => 0,
	            'datalist' => 0, 'status' => 0, 'unit' => 0, 'timestamp' => 0};

	# Other tags will be saved here
	$module_conf->{'extended_info'} = '';
	
	# Read tags
	while (my ($tag, $value) = each (%{$data})) {
		if (defined ($tags->{$tag})) {
			$module_conf->{$tag} = get_tag_value ($data, $tag, '');
		} else {
			$module_conf->{'extended_info'} .= "$tag: " . get_tag_value ($data, $tag, '') . '<br/>';
		}
	}
	
	# Description XML tag and column name don't match
	$module_conf->{'descripcion'} = $module_conf->{'description'};
	
	# Calculate the module interval in seconds
	$module_conf->{'module_interval'} *= $interval if (defined ($module_conf->{'module_interval'}));

	# Allow , as a decimal separator
	$module_conf->{'post_process'} =~ s/,/./ if (defined ($module_conf->{'post_process'}));

	# Get module data or create it if it does not exist
	my $module = get_db_single_row ($dbh, 'SELECT * FROM tagente_modulo WHERE id_agente = ? AND ' . db_text ('nombre') . ' = ?', $agent->{'id_agente'}, safe_input($module_name));
	if (! defined ($module)) {

		# Set default values
		$module_conf->{'max'} = 0 unless defined ($module_conf->{'max'});
		$module_conf->{'min'} = 0 unless defined ($module_conf->{'min'});
		$module_conf->{'descripcion'} = '' unless defined ($module_conf->{'descripcion'});
		$module_conf->{'post_process'} = 0 unless defined ($module_conf->{'post_process'});
		$module_conf->{'module_interval'} = $interval unless defined ($module_conf->{'module_interval'}); # 1 * $interval
		$module_conf->{'min_critical'} = 0 unless defined ($module_conf->{'min_critical'});
		$module_conf->{'max_critical'} = 0 unless defined ($module_conf->{'max_critical'});
		$module_conf->{'min_warning'} = 0 unless defined ($module_conf->{'min_warning'});
		$module_conf->{'max_warning'} = 0 unless defined ($module_conf->{'max_warning'});
		$module_conf->{'disabled'} = 0 unless defined ($module_conf->{'disabled'});
		$module_conf->{'min_ff_event'} = 0 unless defined ($module_conf->{'min_ff_event'});
		$module_conf->{'extended_info'} = '' unless defined ($module_conf->{'extended_info'});
		$module_conf->{'unit'} = '' unless defined ($module_conf->{'unit'});

		# Get the module type
		my $module_id = get_module_id ($dbh, $module_type);

		# Create the module
		pandora_create_module ($pa_config, $agent->{'id_agente'}, $module_id, $module_name,
			$module_conf->{'max'}, $module_conf->{'min'}, $module_conf->{'post_process'},
			$module_conf->{'descripcion'}, $module_conf->{'module_interval'}, $dbh);
		$module = get_db_single_row ($dbh, 'SELECT * FROM tagente_modulo WHERE id_agente = ? AND ' . db_text('nombre') . ' = ?', $agent->{'id_agente'}, safe_input($module_name));
	} else {
		
		# Control NULL columns
		$module->{'descripcion'} = '' unless defined ($module->{'descripcion'});
		$module->{'extended_info'} = '' unless defined ($module->{'extended_info'});
		$module->{'unit'} = ''  unless defined ($module->{'unit'});
		
		# Set default values
		$module_conf->{'max'} = $module->{'max'} unless defined ($module_conf->{'max'});
		$module_conf->{'min'} = $module->{'min'} unless defined ($module_conf->{'min'});
		$module_conf->{'descripcion'} = $module->{'descripcion'} unless defined ($module_conf->{'descripcion'});
		$module_conf->{'unit'} = $module->{'unit'} unless defined ($module_conf->{'unit'});
		$module_conf->{'post_process'} = $module->{'post_process'} unless defined ($module_conf->{'post_process'});
		$module_conf->{'module_interval'} = $module->{'module_interval'} unless defined ($module_conf->{'module_interval'});
		$module_conf->{'min_critical'} = $module->{'min_critical'} unless defined ($module_conf->{'min_critical'});
		$module_conf->{'max_critical'} = $module->{'max_critical'} unless defined ($module_conf->{'max_critical'});
		$module_conf->{'min_warning'} = $module->{'min_warning'} unless defined ($module_conf->{'min_warning'});
		$module_conf->{'max_warning'} = $module->{'max_warning'} unless defined ($module_conf->{'max_warning'});
		$module_conf->{'disabled'} = $module->{'disabled'} unless defined ($module_conf->{'disabled'});
		$module_conf->{'min_ff_event'} = $module->{'min_ff_event'} unless defined ($module_conf->{'min_ff_event'});
		$module_conf->{'extended_info'} = $module->{'extended_info'} unless defined ($module_conf->{'extended_info'});

		# The group name has to be translated to a group ID
		my $conf_group_id = -1;
		if (defined $module_conf->{'group'}) {
			my $conf_group_id = get_group_id ($dbh, $module_conf->{'group'});
		}
		$module_conf->{'id_module_group'} = ($conf_group_id == -1) ? $module->{'id_module_group'} : $conf_group_id;
	}

	# Parse the timestamp and process the module
	if ($timestamp !~ /(\d+)\/(\d+)\/(\d+) +(\d+):(\d+):(\d+)/ &&
		$timestamp !~ /(\d+)\-(\d+)\-(\d+) +(\d+):(\d+):(\d+)/) {
	}
	my $utimestamp;
	$utimestamp = timelocal($6, $5, $4, $3, $2 - 1, $1 - 1900);

	my %data_object;
 
	$data_object{'data'} = 1;
	my %extra_macros;
	
	# Get module status from XML data file if available
	$module->{'status'} = 1;
	
	pandora_process_module ($pa_config, \%data_object, $agent, $module, $module_type, $timestamp, $utimestamp, $server_id, $dbh, \%extra_macros);
}


###############################################################################
# Check main data table speed
###############################################################################
sub pandora_data_speed {
	my $dbh = shift;

	#1. Take a random id module (generic_data) with valid data in last 24hr and history enabled.
	#2. Read last 24hr of data for that module

	my $candidate = get_db_value ($dbh, 'SELECT tagente_modulo.id_agente_modulo from tagente_modulo, tagente_estado WHERE tagente_modulo.id_tipo_modulo = 1 AND tagente_modulo.history_data = 1 AND tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo  AND utimestamp > UNIX_TIMESTAMP() -  86400 ORDER BY RAND()  LIMIT 1;');

	if (defined($candidate)){
	        my @random_data = get_db_rows ($dbh, 'SELECT * FROM tagente_datos WHERE id_agente_modulo = '.$candidate.' AND utimestamp > UNIX_TIMESTAMP() -  86400');
	} else {
		# No enough data for measuring. Aborting
		print "0";
		exit -1;	
	}

	#1. Take a random id module (generic_data_string) with valid data in last 24hr and history enabled.
        #2. Read last 24hr of data for that module

        my $candidate_str = get_db_value ($dbh, 'SELECT tagente_modulo.id_agente_modulo from tagente_modulo, tagente_estado WHERE tagente_modulo.id_tipo_modulo = 3 AND tagente_modulo.history_data = 1 AND tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo  AND utimestamp > UNIX_TIMESTAMP() -  86400 ORDER BY RAND()  LIMIT 1;');

        if (defined($candidate_str)){
                my @random_data_str = get_db_rows ($dbh, 'SELECT * FROM tagente_datos_string WHERE id_agente_modulo = '.$candidate.' AND utimestamp > UNIX_TIMESTAMP() -  86400');
        }
}

sub pandora_event_test ($$) {
	my ($pa_config, $dbh) = @_;

	return db_insert ($dbh, 'id_evento', 'INSERT INTO tevento (id_agente, id_grupo, evento, timestamp, estado, utimestamp, event_type, id_agentmodule, id_alert_am, criticity, user_comment, tags) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', "0", "0", "Performance test", "1970-01-01 00:00:00", 1, 1, "system", 0,0, 1, 'Note: This is an internal performance test. Ignore it','test,performance');
}

###############################################################################
# Check processing time for an agent creation + fill with data in one module
###############################################################################
sub pandora_agent_process {
	my $pa_config = shift;
        my $dbh = shift;

	my %data; my $agent_id; my $module_id; my %agent;

	# Create 10 agents in group zero (invisible)
	for (my $ax=0; $ax<10; $ax++){
		
		pandora_create_agent ($pa_config, "performance", "perf_$ax", "127.0.0.1", "0", "", 1, "Performance test agent", 300, $dbh, "", "", "" ,"", "");

		%data = (data => $ax, agent_name => "perf_$ax", version => 1, timestamp => '1970-01-01 00:00:01', interval => 300, os_version => 'n/a');
		
		# Insert a single module (new) data

		process_module_data ($pa_config, \%data, 0, "perf_$ax", "perf_test_$ax", "generic_data", 666, "1970-01-01 00:00:01", $dbh);

		# Get the Module ID and Agent ID
		$agent_id = get_agent_id ($dbh, "perf_$ax");
		$module_id = get_db_value ($dbh, "SELECT id_agente_modulo FROM tagente_modulo WHERE id_agente = '$agent_id' AND nombre = 'perf_test_$ax'");
		db_do ($dbh, "DELETE FROM tagente WHERE nombre = 'perf_$ax'");
		db_do ($dbh, "DELETE FROM tagente_datos WHERE id_agente_modulo = $module_id");
		db_do ($dbh, "DELETE FROM tagente_estado WHERE id_agente_modulo = $module_id");
		db_do ($dbh, "DELETE FROM tagente_modulo WHERE id_agente_modulo = $module_id");
	}	
			
}

###############################################################################
# Check event table. Look for main data table speed
###############################################################################
sub pandora_event_speed {
        my $dbh = shift;
	my $pa_config = shift;

	# Choose a random module with at least an event in last 10 days, 100 times

	my $candidate;
	my @random_data;

	for  (my $a=0; $a < 100; $a++){
		$candidate = get_db_value ($dbh, 'SELECT id_agentmodule FROM tevento WHERE id_agentmodule !=0 AND utimestamp > UNIX_TIMESTAMP() -  864000 ORDER BY RAND()  LIMIT 1;');
	       
	 
	        if (defined($candidate)){
                	@random_data = get_db_rows ($dbh, 'SELECT * FROM tevento WHERE id_agentmodule = '.$candidate.' AND utimestamp > UNIX_TIMESTAMP() -  864000');
	        }
	}

	# Create 100 events, modify user_comment and delete it. With date of 1970
	# to avoid showing them in the console

	my $temp_event;
	for (my $a=0; $a < 100; $a++){
		$temp_event = pandora_event_test ($pa_config, $dbh);
	}

	db_do ($dbh, "UPDATE tevento SET user_comment = 'Performance FOR DELETE' WHERE user_comment LIKE '%This is an internal performance test%'");
	db_do ($dbh, "DELETE FROM tevento WHERE user_comment LIKE '%Performance FOR DELET%' AND utimestamp = 1");

}

##############################################################################
# Print a help screen and exit.
##############################################################################
sub help_screen{

	print "\nPandora FMS Database Speed Test $version Copyright (c) 2012 Artica ST\n";
        print "This program is Free Software, licensed under the terms of GPL License v2\n";
        print "You can download latest versions and documentation at http://www.pandorafms.org\n\n";

	print "Usage: $0 <path to pandora_server.conf> [options]\n\n";
	print "\n\tAvailable options:\n\t\t-d  Debug output (very verbose).\n";
	print "\t\t-v   Verbose output (more info on screen, not valid for use in a module).\n";
	exit;
}

###############################################################################
# Main
###############################################################################
sub pandora_speedtest_main ($$$) {
	my ($conf, $dbh, $history_dbh) = @_;

	my $start_time;
	my $end_time;
	my $total_time;
	my $partial_time;

	# DATA TABLE TEST
	# -------------------

	# Set to counter ON	
	$start_time   = clock_gettime();

	pandora_data_speed($dbh);

	# Stop the counter
	$end_time   = clock_gettime();

	$partial_time = $end_time - $start_time;
	if ($conf->{'_verbose'} == 1){
		print "Data access time: $partial_time (seconds) \n";
	}

	$total_time = $total_time + $partial_time;


	# EVENT TEST	
	# ----------------
	# Set to counter ON
        $start_time   = clock_gettime();

	pandora_event_speed ($dbh, $conf);

	# Stop the counter
        $end_time   = clock_gettime();

        $partial_time = $end_time - $start_time;
        if ($conf->{'_verbose'} == 1){
                print "Event process time: $partial_time (seconds) \n";
        }

        $total_time = $total_time + $partial_time;

	# GROUP STATS
	# ---------------
	# Set to counter ON
        $start_time   = clock_gettime();

	pandora_group_statistics ($conf, $dbh);
        # Stop the counter
        $end_time   = clock_gettime();

        $partial_time = $end_time - $start_time;
        if ($conf->{'_verbose'} == 1){
                print "Get group stats: $partial_time (seconds) \n";
        }

        $total_time = $total_time + $partial_time;

	# AGENT PROCESSING
        # ---------------
        # Set to counter ON
        $start_time   = clock_gettime();

        pandora_agent_process ($conf, $dbh);
        # Stop the counter
        $end_time   = clock_gettime();

        $partial_time = $end_time - $start_time;
        if ($conf->{'_verbose'} == 1){
                print "Pandora agent processing: $partial_time (seconds) \n";
        }

        $total_time = $total_time + $partial_time;


	# EPILOG
	# -----------------
	if ($conf->{'_verbose'} == 1){
                print "Total time: $total_time (seconds) \n";
        } else {
		print $total_time;
	}
	exit 0;
}
