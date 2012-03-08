#!/usr/bin/perl

###############################################################################
# Pandora FMS DB Management
###############################################################################
# Copyright (c) 2005-2009 Artica Soluciones Tecnologicas S.L
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

# Default lib dir for RPM and DEB packages
use lib '/usr/lib/perl5';

use PandoraFMS::Tools;
use PandoraFMS::DB;

# version: define current version
my $version = "4.0.1 PS111213";

# Pandora server configuration
my %conf;

my $BIG_OPERATION_STEP = 100; # Long operations are divided in XX steps for performance

# FLUSH in each IO
$| = 0;

# Init
pandora_init(\%conf);

# Read config file
pandora_load_config (\%conf);

# Load enterprise module
if (enterprise_load (\%conf) == 0) {
	print " [*] Pandora FMS Enterprise module not available.\n\n";
} else {
	print " [*] Pandora FMS Enterprise module loaded.\n\n";
}

# Connect to the DB
my $dbh = db_connect ('mysql', $conf{'dbname'}, $conf{'dbhost'}, '3306', $conf{'dbuser'}, $conf{'dbpass'});
my $history_dbh = ($conf{'_history_db_enabled'} eq '1') ? db_connect ('mysql', $conf{'_history_db_name'},
		$conf{'_history_db_host'}, '3306', $conf{'_history_db_user'}, $conf{'_history_db_pass'}) : undef;

# Main
pandoradb_main(\%conf, $dbh, $history_dbh);

# Cleanup and exit
db_disconnect ($history_dbh) if defined ($history_dbh);
db_disconnect ($dbh);
exit;

###############################################################################
# Delete old data from the database.
###############################################################################
sub pandora_purgedb ($$) {
	my ($conf, $dbh) = @_;

	# 1) Obtain last value for date limit
	# 2) Delete all elements below date limit
	# 3) Insert last value in date_limit position

 	# Calculate limit for deletion, today - $conf->{'_days_purge'}

	my $timestamp = strftime ("%Y-%m-%d %H:%M:%S", localtime());

	my $ulimit_access_timestamp = time() - 86400;
	my $ulimit_timestamp = time() - (86400 * $conf->{'_days_purge'});
	my $limit_timestamp = strftime ("%Y-%m-%d %H:%M:%S", localtime($ulimit_timestamp));

	my $first_mark;
	my $total_time;
	my $purge_steps;

    # Numeric data deletion

	print "[PURGE] Deleting old data... \n";

	# This could be very timing consuming, so make this operation in $BIG_OPERATION_STEP 
	# steps (100 fixed by default)
	# Starting from the oldest record on the table

	$first_mark =  get_db_value ($dbh, 'SELECT utimestamp FROM tagente_datos ORDER BY utimestamp ASC LIMIT 1');
	if (defined ($first_mark)) {
		$total_time = $ulimit_timestamp - $first_mark;
		$purge_steps = int($total_time / $BIG_OPERATION_STEP);
	
		for (my $ax = 1; $ax <= $BIG_OPERATION_STEP; $ax++){
			db_do ($dbh, "DELETE FROM tagente_datos WHERE utimestamp < ". ($first_mark + ($purge_steps * $ax)) . " AND utimestamp > ". $first_mark );
			print "[PURGE] Data deletion Progress %$ax .. \r";
		}
	    print "\n";
	} else {
		print "[PURGE] No data in tagente_datos\n";
	}

	#
	# Now the log4x data
	#
	$first_mark =  get_db_value ($dbh, 'SELECT utimestamp FROM tagente_datos_log4x ORDER BY utimestamp ASC LIMIT 1');
	if (defined ($first_mark)) {
		$total_time = $ulimit_timestamp - $first_mark;
		$purge_steps = int($total_time / $BIG_OPERATION_STEP);

		for (my $ax = 1; $ax <= $BIG_OPERATION_STEP; $ax++){
			db_do ($dbh, "DELETE FROM tagente_datos_log4x WHERE utimestamp < ". ($first_mark + ($purge_steps * $ax)) . " AND utimestamp > ". $first_mark );
			print "[PURGE] Log4x data deletion progress %$ax .. \r";
		}
		print "\n";
	} else {
		print "[PURGE] No data in tagente_datos_log4x\n";
	}

    # String data deletion
    print "[PURGE] Deleting old string data... \n";
    if (!defined($conf->{'_string_purge'})){
        $conf->{'_string_purge'} = 7;
    }

    my $string_limit = time() - 86400 * $conf->{'_string_purge'};
	$first_mark =  get_db_value ($dbh, 'SELECT utimestamp FROM tagente_datos_string ORDER BY utimestamp ASC LIMIT 1');
	if (defined ($first_mark)) {
		$total_time = $string_limit - $first_mark;
		$purge_steps = int($total_time / $BIG_OPERATION_STEP);
	
		for (my $ax = 1; $ax <= $BIG_OPERATION_STEP; $ax++){
			db_do ($dbh, "DELETE FROM tagente_datos_string WHERE utimestamp < ". ($first_mark + ($purge_steps * $ax)) . " AND utimestamp > ". $first_mark );
			print "[PURGE] String deletion Progress %$ax .. \r";
		}
	    print "\n";
	} else {
		print "[PURGE] No data in tagente_datos_string\n";
	}

    # Delete event data
    if (!defined($conf->{'_event_purge'})){
        $conf->{'_event_purge'}= 10;
    }

	print "[PURGE] Deleting old event data (More than " . $conf->{'_event_purge'} . " days)... \n";

    my $event_limit = time() - 86400 * $conf->{'_event_purge'};
	db_do($dbh, "DELETE FROM tevento WHERE utimestamp < $event_limit");

    # Delete audit data
    if (!defined($conf->{'_audit_purge'})){
        $conf->{'_audit_purge'}= 7;
    }
	print "[PURGE] Deleting old audit data (More than " . $conf->{'_audit_purge'} . " days)... \n";

    my $audit_limit = time() - 86400 * $conf->{'_audit_purge'};
	db_do($dbh, "DELETE FROM tsesion WHERE utimestamp < $audit_limit");


    # Delete SNMP trap data
    if (!defined($conf->{'_trap_purge'})){
        $conf->{'_trap_purge'}= 7;
    }
	
    my $trap_limit = time() - 86400 * $conf->{'_trap_purge'};
    $trap_limit = strftime ("%Y-%m-%d %H:%M:%S", localtime($trap_limit));
	db_do($dbh, "DELETE FROM ttrap WHERE timestamp < '$trap_limit'");
	
    # Delete policy queue data
	enterprise_hook("pandora_purge_policy_queue", [$dbh, $conf]);

    # Delete GIS  data
    if (!defined($conf->{'_gis_purge'})){
        $conf->{'_gis_purge'}= 15;
    }

    my $gis_limit = time() - 86400 * $conf->{'_gis_purge'};
    $gis_limit = strftime ("%Y-%m-%d %H:%M:%S", localtime($gis_limit));
	db_do($dbh, "DELETE FROM tgis_data_history WHERE end_timestamp < '$gis_limit'");

    # Delete pending modules
	print "[PURGE] Delete pending deleted modules (data table)...\n";
	my @deleted_modules = get_db_rows ($dbh, 'SELECT id_agente_modulo FROM tagente_modulo WHERE delete_pending = 1');

	foreach my $module (@deleted_modules) {
        
        my $buffer = 1000;
        my $id_module = $module->{'id_agente_modulo'};
        
		print " Deleting data for module " . $id_module . "\n";
		
		while(1) {
			my $nstate = get_db_value ($dbh, 'SELECT count(id_agente_modulo) FROM tagente_estado WHERE id_agente_modulo=?', $id_module);
			
			if($nstate == 0) {
				last;
			}
			
			if($nstate > 0) {
				db_do ($dbh, 'DELETE FROM tagente_estado WHERE id_agente_modulo=? LIMIT ?', $id_module, $buffer);
			}
		}
	}

	print "[PURGE] Delete pending deleted modules (status, module table)...\n";
	db_do ($dbh, "DELETE FROM tagente_estado WHERE id_agente_modulo IN (SELECT id_agente_modulo FROM tagente_modulo WHERE delete_pending = 1)");
	db_do ($dbh, "DELETE FROM tagente_modulo WHERE delete_pending = 1");

	print "[PURGE] Deleting old access data (More than 24hr) \n";

	$first_mark =  get_db_value ($dbh, 'SELECT utimestamp FROM tagent_access ORDER BY utimestamp ASC LIMIT 1');
	if (defined ($first_mark)) {
		$total_time = $ulimit_access_timestamp - $first_mark;
		$purge_steps = int( $total_time / $BIG_OPERATION_STEP);
	
		for (my $ax = 1; $ax <= $BIG_OPERATION_STEP; $ax++){ 
			db_do ($dbh, "DELETE FROM tagent_access WHERE utimestamp < ". ( $first_mark + ($purge_steps * $ax)) . " AND utimestamp > ". $first_mark);
			print "[PURGE] Agent access deletion progress %$ax .. \r";
		}
	    print "\n";
	} else {
		print "[PURGE] No data in tagente_access\n";
	}
}

###############################################################################
# Compact agent data.
###############################################################################
sub pandora_compactdb ($$) {
	my ($conf, $dbh) = @_;

	my %count_hash;
	my %id_agent_hash;
	my %value_hash;

	return if ($conf->{'_days_compact'} == 0 || $conf->{'_step_compact'} < 1);

	# Compact interval length in seconds
	# $conf->{'_step_compact'} varies between 1 (36 samples/day) and
	# 20 (1.8 samples/day)
	my $step = $conf->{'_step_compact'} * 2400;

	# The oldest timestamp will be the lower limit
	my $limit_utime = get_db_value ($dbh, 'SELECT min(utimestamp) as min FROM tagente_datos');
	return unless (defined ($limit_utime) && $limit_utime > 0);

	# Calculate the start date
	my $start_utime = time() - $conf->{'_days_compact'} * 24 * 60 * 60;
	my $stop_utime;

	print "[COMPACT] Compacting data until " . strftime ("%Y-%m-%d %H:%M:%S", localtime($start_utime)) ."\n";

	# Prepare the query to retrieve data from an interval
	while (1) {

			# Calculate the stop date for the interval
			$stop_utime = $start_utime - $step;

			# Out of limits
			return if ($start_utime < $limit_utime);

			my @data = get_db_rows ($dbh, 'SELECT * FROM tagente_datos WHERE utimestamp < ? AND utimestamp >= ?', $start_utime, $stop_utime);
			# No data, move to the next interval
			if ($#data == 0) {
				$start_utime = $stop_utime;
				next;
			}

			# Get interval data
			foreach my $data (@data) {
				my $id_module = $data->{'id_agente_modulo'};

				if (! defined($value_hash{$id_module})) {
					$value_hash{$id_module} = 0;
					$count_hash{$id_module} = 0;

					if (! defined($id_agent_hash{$id_module})) {
						$id_agent_hash{$id_module} = $data->{'id_agente'};
					}
				}

				$value_hash{$id_module} += $data->{'datos'};
				$count_hash{$id_module}++;
			}

			# Delete interval from the database
			db_do ($dbh, 'DELETE FROM tagente_datos WHERE utimestamp < ? AND utimestamp >= ?', $start_utime, $stop_utime);

			# Insert interval average value
			foreach my $key (keys(%value_hash)) {
				$value_hash{$key} /= $count_hash{$key};
				db_do ($dbh, 'INSERT INTO tagente_datos (id_agente_modulo, datos, utimestamp) VALUES (?, ?, ?)', $key, $value_hash{$key}, $stop_utime);
				delete($value_hash{$key});
				delete($count_hash{$key});
			}

			# Move to the next interval
			$start_utime = $stop_utime;
	}
}

##############################################################################
# Check command line parameters.
##############################################################################
sub pandora_init ($) {
	my $conf = shift;

	print "\nPandora FMS DB Tool $version Copyright (c) 2004-2009 Artica ST\n";
	print "This program is Free Software, licensed under the terms of GPL License v2\n";
	print "You can download latest versions and documentation at http://www.pandorafms.org\n\n";

	# Load config file from command line
	help_screen () if ($#ARGV < 0);

	# If there are not valid parameters
	foreach my $param (@ARGV) {
		
		# help!
		help_screen () if ($param =~ m/--*h\w*\z/i );
		if ($param =~ m/-p\z/i) {
			$conf->{'_onlypurge'} = 1;
		} else {
			$conf->{'_pandora_path'} = $param;
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

	# Read additional tokens from the DB
	my $dbh = db_connect ('mysql', $conf->{'dbname'}, $conf->{'dbhost'}, '3306', $conf->{'dbuser'}, $conf->{'dbpass'});

	$conf->{'_event_purge'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'event_purge'");
	$conf->{'_trap_purge'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'trap_purge'");
	$conf->{'_audit_purge'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'audit_purge'");
	$conf->{'_string_purge'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'audit_purge'");
	$conf->{'_gis_purge'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'gis_purge'");

	$conf->{'_days_purge'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'days_purge'");
	$conf->{'_days_compact'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'days_compact'");
	$conf->{'_step_compact'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'step_compact'");
	$conf->{'_history_db_enabled'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'history_db_enabled'");
	$conf->{'_history_db_host'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'history_db_host'");
	$conf->{'_history_db_port'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'history_db_port'");
	$conf->{'_history_db_name'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'history_db_name'");
	$conf->{'_history_db_user'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'history_db_user'");
	$conf->{'_history_db_pass'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'history_db_pass'");
	$conf->{'_history_db_days'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'history_db_days'");
	$conf->{'_history_db_step'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'history_db_step'");
	$conf->{'_history_db_delay'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'history_db_delay'");
	$conf->{'_days_delete_unknown'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'days_delete_unknown'");
	db_disconnect ($dbh);

	printf "Pandora DB now initialized and running (PURGE=" . $conf->{'_days_purge'} . " days, COMPACT=$conf->{'_days_compact'} days, STEP=" . $conf->{'_step_compact'} . ") ... \n\n";
}


###############################################################################
# Check database integrity
###############################################################################

sub pandora_checkdb_integrity {
	my $dbh = shift;

    print "[INTEGRITY] Cleaning up group stats \n";

    # Delete all records on tgroup_stats
    db_do ($dbh, 'DELETE FROM tgroup_stat');

    print "[INTEGRITY] Deleting non-used IP addresses \n";

    # Delete all non-used IP addresses from taddress
    db_do ($dbh, 'DELETE FROM taddress WHERE id_a NOT IN (SELECT id_a FROM taddress_agent)');

    print "[INTEGRITY] Deleting orphan alerts \n";

    # Delete alerts assigned to inexistant modules
    db_do ($dbh, 'DELETE FROM talert_template_modules WHERE id_agent_module NOT IN (SELECT id_agente_modulo FROM tagente_modulo)');

    print "[INTEGRITY] Deleting orphan modules \n";

    # Delete orphan modules in tagente_modulo
    db_do ($dbh, 'DELETE FROM tagente_modulo WHERE id_agente NOT IN (SELECT id_agente FROM tagente)');

    # Delete orphan modules in tagente_estado
    db_do ($dbh, 'DELETE FROM tagente_estado WHERE id_agente NOT IN (SELECT id_agente FROM tagente)');

    # Delete orphan data_inc reference records
    db_do ($dbh, 'DELETE FROM tagente_datos_inc WHERE id_agente_modulo NOT IN (SELECT id_agente_modulo FROM tagente_modulo)');
    
    # Delete all non-used IP addresses from taddress
    db_do ($dbh, 'DELETE FROM taddress WHERE id_a NOT IN (SELECT id_a FROM taddress_agent)');
    
    # Check enterprise tables
    enterprise_hook ('pandora_checkdb_integrity_enterprise', [$dbh]);
}

###############################################################################
# Check database consistency.
###############################################################################
sub pandora_checkdb_consistency {
	my $dbh = shift;

	# 1. Check for modules that do not have tagente_estado but have tagente_module

	print "[CHECKDB] Deleting non-init data... \n";
	my @modules = get_db_rows ($dbh, 'SELECT id_agente_modulo FROM tagente_estado WHERE utimestamp = 0');
	foreach my $module (@modules) {
		my $id_agente_modulo = $module->{'id_agente_modulo'};

		# Skip policy modules
		my $is_policy_module = enterprise_hook ('is_policy_module', [$dbh, $id_agente_modulo]);
		next if (defined($is_policy_module) && $is_policy_module);

		# Delete the module
		db_do ($dbh, 'DELETE FROM tagente_modulo WHERE disabled = 0 AND id_agente_modulo = ?', $id_agente_modulo);;

		# Delete any alerts associated to the module
		db_do ($dbh, 'DELETE FROM talert_template_modules WHERE id_agent_module = ? AND NOT EXISTS (SELECT id_agente_modulo FROM tagente_modulo WHERE id_agente_modulo = ?)', $id_agente_modulo, $id_agente_modulo);
	}

	print "[CHECKDB] Deleting unknown data (More than " . $conf{'_days_delete_unknown'} . " days)... \n";
	if ($conf{'_days_delete_unknown'} > 0) {
		my @modules = get_db_rows ($dbh, 'SELECT id_agente_modulo FROM tagente_estado WHERE estado = 3 AND utimestamp < UNIX_TIMESTAMP() - ?', 86400 * $conf{'_days_delete_unknown'});
		foreach my $module (@modules) {
			my $id_agente_modulo = $module->{'id_agente_modulo'};
	
			# Skip policy modules
			my $is_policy_module = enterprise_hook ('is_policy_module', [$dbh, $id_agente_modulo]);
			next if (defined($is_policy_module) && $is_policy_module);
	
			# Delete the module
			db_do ($dbh, 'DELETE FROM tagente_modulo WHERE disabled = 0 AND id_agente_modulo = ?', $id_agente_modulo);;
	
			# Delete any alerts associated to the module
			db_do ($dbh, 'DELETE FROM talert_template_modules WHERE id_agent_module = ? AND NOT EXISTS (SELECT id_agente_modulo FROM tagente_modulo WHERE id_agente_modulo', $id_agente_modulo);
		}
	}
	print "[CHECKDB] Checking database consistency (Missing status)... \n";

	@modules = get_db_rows ($dbh, 'SELECT * FROM tagente_modulo');
	foreach my $module (@modules) {
		my $id_agente_modulo = $module->{'id_agente_modulo'};
		my $id_agente = $module->{'id_agente'};

		# check if exist in tagente_estado and create if not
		my $count = get_db_value ($dbh, 'SELECT COUNT(*) FROM tagente_estado WHERE id_agente_modulo = ?', $id_agente_modulo);
		next if (defined ($count) && $count > 0);

		db_do ($dbh, 'INSERT INTO tagente_estado (id_agente_modulo, datos, timestamp, estado, id_agente, last_try, utimestamp, current_interval, running_by, last_execution_try) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', $id_agente_modulo, 0, '1970-01-01 00:00:00', 1, $id_agente, '1970-01-01 00:00:00', 0, 0, 0, 0);
		print "[CHECKDB] Inserting module $id_agente_modulo in state table \n";
	}

	print "[CHECKDB] Checking database consistency (Missing module)... \n";
	# 2. Check for modules in tagente_estado that do not have tagente_modulo, if there is any, delete it

	@modules = get_db_rows ($dbh, 'SELECT * FROM tagente_estado');
	foreach my $module (@modules) {
		my $id_agente_modulo = $module->{'id_agente_modulo'};

		# check if exist in tagente_estado and create if not
		my $count = get_db_value ($dbh, 'SELECT COUNT(*) FROM tagente_modulo WHERE id_agente_modulo = ?', $id_agente_modulo);
		next if (defined ($count) && $count > 0);
	
		db_do ($dbh, 'DELETE FROM tagente_estado WHERE id_agente_modulo = ?', $id_agente_modulo);
		print "[CHECKDB] Deleting non-existing module $id_agente_modulo in state table \n";
	}
}

##############################################################################
# Print a help screen and exit.
##############################################################################
sub help_screen{
	print "Usage: $0 <path to pandora_server.conf> [options]\n\n";
	print "\n\tAvailable options:\n\t\t-d  Debug output (very verbose).\n";
	print "\t\t-v   Verbose output.\n";
	print "\t\t-q   Quiet output.\n";
	print "\t\t-p   Only purge and consistency check, skip compact.\n\n";
	exit;
}

###############################################################################
# Main
###############################################################################
sub pandoradb_main ($$$) {
	my ($conf, $dbh, $history_dbh) = @_;

	print "Starting at ". strftime ("%Y-%m-%d %H:%M:%S", localtime()) . "\n";	

	# Purge
	pandora_purgedb ($conf, $dbh);

	# Consistency check
	pandora_checkdb_consistency ($dbh);

	# Maintain Referential integrity and other stuff
	pandora_checkdb_integrity ($dbh);

	# Move old data to the history DB
	if (defined ($history_dbh)) {
		undef ($history_dbh) unless defined (enterprise_hook ('pandora_historydb', [$dbh, $history_dbh, $conf->{'_history_db_days'}, $conf->{'_history_db_step'}, $conf->{'_history_db_delay'}]));
	}

	# Compact on if enable and DaysCompact are below DaysPurge 
	if (($conf->{'_onlypurge'} == 0) && ($conf->{'_days_compact'} < $conf->{'_days_purge'})) {
		pandora_compactdb ($conf, defined ($history_dbh) ? $history_dbh : $dbh);
	}

	# Update tconfig with last time of database maintance time (now)

	db_do ($dbh, "DELETE FROM tconfig WHERE token = 'db_maintance'");
	db_do ($dbh, "INSERT INTO tconfig (token, value) VALUES ('db_maintance', '".time()."')");

	print "Ending at ". strftime ("%Y-%m-%d %H:%M:%S", localtime()) . "\n";
}
