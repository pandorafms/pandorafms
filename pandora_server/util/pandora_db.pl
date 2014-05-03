#!/usr/bin/perl

###############################################################################
# Pandora FMS DB Management
###############################################################################
# Copyright (c) 2005-2013 Artica Soluciones Tecnologicas S.L
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
use warnings;
use Time::Local;		# DateTime basic manipulation
use DBI;				# DB interface with MySQL
use POSIX qw(strftime);
use File::Path qw(rmtree);
use Time::HiRes qw(usleep);

# Default lib dir for RPM and DEB packages
use lib '/usr/lib/perl5';

use PandoraFMS::Tools;
use PandoraFMS::DB;

# version: define current version
my $version = "4.1.1 PS140504";

# Pandora server configuration
my %conf;

my $BIG_OPERATION_STEP = 100; # Long operations are divided in XX steps for performance
my $SMALL_OPERATION_STEP = 1000; # Each long operations has a LIMIT of SMALL_OPERATION_STEP to avoid locks. 
                                 #Increate to 3000~5000 in fast systems decrease to 500 or 250 on systems with locks
# FLUSH in each IO 
$| = 1;

###############################################################################
# Print the given message with a preceding timestamp.
###############################################################################
sub log_message ($$;$) {
	my ($source, $message, $eol) = @_;
	
	# Set a default end of line
	$eol = "\n" unless defined ($eol);
	
	if ($source eq '') {
		print $message;
	} else {
		print strftime("%H:%M:%S", localtime()) . ' [' . $source . '] ' . $message . $eol;
	}
}

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

	# Delete old numeric data
	pandora_delete_old_module_data ($dbh, 'tagente_datos', $ulimit_access_timestamp, $ulimit_timestamp);

    # Delete extended session data
    if (enterprise_load (\%conf) != 0) {
        db_do ($dbh, "DELETE FROM tsesion_extended WHERE id_sesion NOT IN ( SELECT id_sesion FROM tsesion );");
        log_message ('PURGE', 'Deleting old extended session data.');
    }

    # Delete inventory data, only if enterprise is enabled
    # We use the same value than regular data purge interval
	my $first_mark;
	my $total_time;
	my $purge_steps;
	my $purge_count;

    if (enterprise_load (\%conf) != 0) {

        log_message ('PURGE', 'Deleting old inventory data.');

	    # This could be very timing consuming, so make 
        # this operation in $BIG_OPERATION_STEP 
	    # steps (100 fixed by default)
	    # Starting from the oldest record on the table

	    $first_mark =  get_db_value ($dbh, 'SELECT utimestamp FROM tagente_datos_inventory ORDER BY utimestamp ASC LIMIT 1');
	    if (defined ($first_mark)) {
		    $total_time = $ulimit_timestamp - $first_mark;
		    $purge_steps = int($total_time / $BIG_OPERATION_STEP);
		    if ($purge_steps > 0) {
			    for (my $ax = 1; $ax <= $BIG_OPERATION_STEP; $ax++) {
				    db_do ($dbh, "DELETE FROM tagente_datos_inventory WHERE utimestamp < ". ($first_mark + ($purge_steps * $ax)) . " AND utimestamp >= ". $first_mark );
				    log_message ('PURGE', "Inventory data deletion Progress %$ax\r");
				    # Do a nanosleep here for 0,01 sec
					usleep (10000);
			    }
		        log_message ('', "\n");
			} else {
				log_message ('PURGE', 'No data to purge in tagente_datos_inventory.');
			}
	    } else {
		    log_message ('PURGE', 'No data in tagente_datos_inventory.');
	    }
    }


	#
	# Now the log4x data
	#
	$first_mark =  get_db_value ($dbh, 'SELECT utimestamp FROM tagente_datos_log4x ORDER BY utimestamp ASC LIMIT 1');
	if (defined ($first_mark)) {
		$total_time = $ulimit_timestamp - $first_mark;
		$purge_steps = int($total_time / $BIG_OPERATION_STEP);
		if ($purge_steps > 0) {
			for (my $ax = 1; $ax <= $BIG_OPERATION_STEP; $ax++){
				db_do ($dbh, "DELETE FROM tagente_datos_log4x WHERE utimestamp < ". ($first_mark + ($purge_steps * $ax)) . " AND utimestamp >= ". $first_mark );
				log_message ('PURGE', "Log4x data deletion progress %$ax\r");
				# Do a nanosleep here for 0,01 sec
				usleep (10000);
			}
			log_message ('', "\n");
		} else {
			log_message ('PURGE', 'No data to purge in tagente_datos_log4x.');
		}
	} else {
		log_message ('PURGE', 'No data in tagente_datos_log4x.');
	}

    # String data deletion
    if (!defined($conf->{'_string_purge'})){
        $conf->{'_string_purge'} = 7;
    }
	$ulimit_access_timestamp = time() - 86400;
	$ulimit_timestamp = time() - (86400 * $conf->{'_days_purge'});
	pandora_delete_old_module_data ($dbh, 'tagente_datos_string', $ulimit_access_timestamp, $ulimit_timestamp);

    # Delete event data
    if (!defined($conf->{'_event_purge'})){
        $conf->{'_event_purge'}= 10;
    }

    my $event_limit = time() - 86400 * $conf->{'_event_purge'};
    my $events_table = 'tevento';
    
	# If is installed enterprise version and enabled metaconsole, 
	# check the events history copy and set the name of the metaconsole events table
    if (defined($conf->{'_enterprise_installed'}) && $conf->{'_enterprise_installed'} eq '1' &&
		defined($conf->{'_metaconsole'}) && $conf->{'_metaconsole'} eq '1'){
	
		# If events history is enabled, save the new events (not validated or in process) to history database
		if(defined($conf->{'_metaconsole_events_history'}) && $conf->{'_metaconsole_events_history'} eq '1') {
			log_message ('PURGE', "Moving old not validated events to history table (More than " . $conf->{'_event_purge'} . " days).");

			my @events = get_db_rows ($dbh, 'SELECT * FROM tmetaconsole_event WHERE estado = 0 AND utimestamp < ?', $event_limit);
			foreach my $event (@events) {
				db_process_insert($dbh, 'id_evento', 'tmetaconsole_event_history', $event);
			}
		}
		
		$events_table = 'tmetaconsole_event';
	}
	
	log_message ('PURGE', "Deleting old event data at $events_table table (More than " . $conf->{'_event_purge'} . " days).", '');

	# Delete with buffer to avoid problems with performance
	my $events_to_delete = get_db_value ($dbh, "SELECT COUNT(*) FROM $events_table WHERE utimestamp < ?", $event_limit);
	while($events_to_delete > 0) {
		db_do($dbh, "DELETE FROM $events_table WHERE utimestamp < ? LIMIT ?", $event_limit, $BIG_OPERATION_STEP);
		$events_to_delete = $events_to_delete - $BIG_OPERATION_STEP;
		
		# Mark the progress
		log_message ('', ".");
		
		# Do not overload the MySQL server
		usleep (10000);
	}
	log_message ('', "\n");

    # Delete audit data
    $conf->{'_audit_purge'}= 7 if (!defined($conf->{'_audit_purge'}));
	log_message ('PURGE', "Deleting old audit data (More than " . $conf->{'_audit_purge'} . " days).");

    my $audit_limit = time() - 86400 * $conf->{'_audit_purge'};
	db_do($dbh, "DELETE FROM tsesion WHERE utimestamp < $audit_limit");

    # Delete SNMP trap data
    $conf->{'_trap_purge'}= 7 if (!defined($conf->{'_trap_purge'}));
    log_message ('PURGE', "Deleting old SNMP traps data (More than " . $conf->{'_trap_purge'} . " days).");

    my $trap_limit = strftime ("%Y-%m-%d %H:%M:%S", localtime(time() - 86400 * $conf->{'_trap_purge'}));
	db_do($dbh, "DELETE FROM ttrap WHERE timestamp < '$trap_limit'");
	
    # Delete policy queue data
	enterprise_hook("pandora_purge_policy_queue", [$dbh, $conf]);

    # Delete GIS  data
    $conf->{'_gis_purge'}= 15 if (!defined($conf->{'_gis_purge'}));
    log_message ('PURGE', "Deleting old GID data (More than " . $conf->{'_gis_purge'} . " days).");

    my $gis_limit = strftime ("%Y-%m-%d %H:%M:%S", localtime(time() - 86400 * $conf->{'_gis_purge'}));
	db_do($dbh, "DELETE FROM tgis_data_history WHERE end_timestamp < '$gis_limit'");

    # Delete pending modules
	log_message ('PURGE', "Deleting pending delete modules (data table).", '');
	my @deleted_modules = get_db_rows ($dbh, 'SELECT id_agente_modulo FROM tagente_modulo WHERE delete_pending = 1');
	foreach my $module (@deleted_modules) {
        
        my $buffer = 1000;
        my $id_module = $module->{'id_agente_modulo'};
        
		log_message ('', ".");
		
		while(1) {
			my $nstate = get_db_value ($dbh, 'SELECT count(id_agente_modulo) FROM tagente_estado WHERE id_agente_modulo=?', $id_module);			
			last if($nstate == 0);
			
			db_do ($dbh, 'DELETE FROM tagente_estado WHERE id_agente_modulo=? LIMIT ?', $id_module, $buffer);
		}
	}
	log_message ('', "\n");

	log_message ('PURGE', "Deleting pending delete modules (status, module table).");
	db_do ($dbh, "DELETE FROM tagente_estado WHERE id_agente_modulo IN (SELECT id_agente_modulo FROM tagente_modulo WHERE delete_pending = 1)");
	db_do ($dbh, "DELETE FROM tagente_modulo WHERE delete_pending = 1");

	log_message ('PURGE', "Deleting old access data (More than 24hr)");

	$first_mark =  get_db_value ($dbh, 'SELECT utimestamp FROM tagent_access ORDER BY utimestamp ASC LIMIT 1');
	if (defined ($first_mark)) {
		$total_time = $ulimit_access_timestamp - $first_mark;
		$purge_steps = int( $total_time / $BIG_OPERATION_STEP);
		if ($purge_steps > 0) {
			for (my $ax = 1; $ax <= $BIG_OPERATION_STEP; $ax++){ 
				db_do ($dbh, "DELETE FROM tagent_access WHERE utimestamp < ". ( $first_mark + ($purge_steps * $ax)) . " AND utimestamp >= ". $first_mark);
				log_message ('PURGE', "Agent access deletion progress %$ax", "\r");
				# Do a nanosleep here for 0,01 sec
				usleep (10000);
			}
		    log_message ('', "\n");
		} else {
			log_message ('PURGE', "No agent access data to purge.");
		}
	} else {
		log_message ('PURGE', "No agent access data.");
	}
	
	# Purge the reports
   	if (defined($conf->{'_enterprise_installed'}) && $conf->{'_enterprise_installed'} eq '1' &&
	    defined($conf->{'_metaconsole'}) && $conf->{'_metaconsole'} eq '1'){
		log_message ('PURGE', "Metaconsole enabled, ignoring reports.");
	} else {
		log_message ('PURGE', "Delete contents in report that have some deleted modules.");
		db_do ($dbh, "DELETE FROM treport_content WHERE id_agent_module NOT IN (SELECT id_agente_modulo FROM tagente_modulo) AND id_agent_module != 0;");
		db_do ($dbh, "DELETE FROM treport_content_item WHERE id_agent_module NOT IN (SELECT id_agente_modulo FROM tagente_modulo) AND id_agent_module != 0;");
		db_do ($dbh, "DELETE FROM treport_content_sla_combined WHERE id_agent_module NOT IN (SELECT id_agente_modulo FROM tagente_modulo) AND id_agent_module != 0;");
		
		log_message ('PURGE', "Delete contents in report that have some deleted agents.");
		db_do ($dbh, "DELETE FROM treport_content WHERE id_agent NOT IN (SELECT id_agente FROM tagente) AND id_agent != 0;");
		
		log_message ('PURGE', "Delete empty contents in report (like SLA or Exception).");
		db_do ($dbh, "DELETE FROM treport_content WHERE type LIKE 'exception' AND id_rc NOT IN (SELECT id_report_content FROM treport_content_item);");
		db_do ($dbh, "DELETE FROM treport_content WHERE type LIKE 'sla' AND id_rc NOT IN (SELECT id_report_content FROM treport_content_sla_combined);");
	}
	
	# Delete old netflow data
	log_message ('PURGE', "Deleting old netflow data.");
	if (! defined ($conf->{'_netflow_path'}) || ! -d $conf->{'_netflow_path'}) {
		log_message ('!', "Netflow data directory does not exist, skipping.");
	} elsif (! -x $conf->{'_netflow_nfexpire'}) {
		log_message ('!', "Cannot execute " . $conf->{'_netflow_nfexpire'} . ", skipping.");
	} else {
		`yes 2>/dev/null | $conf->{'_netflow_nfexpire'} -e "$conf->{'_netflow_path'}" -t $conf->{'_netflow_max_lifetime'}d`;
	}

	# Delete old log data
	log_message ('PURGE', "Deleting old log data.");
	if (! defined ($conf->{'_log_dir'}) || ! -d $conf->{'_log_dir'}) {
		log_message ('!', "Log data directory does not exist, skipping.");
	} elsif ($conf->{'_log_max_lifetime'} != 0) {

		# Calculate the limit date
		my ($sec,$min,$hour,$mday,$mon,$year) = localtime(time() - $conf->{'_log_max_lifetime'} * 86400); 
		
		# Fix the year
		$year += 1900;
		
		# Fix the month
		$mon += 1;
		$mon = sprintf("%02d", $mon);
		
		# Fix the day
		$mday = sprintf("%02d", $mday);
		
		# Fix the hour
		$hour = sprintf("%02d", $hour);
		
		# Set the per-depth limits
		my $limits = [$year, $mon, $mday, $hour];

		# Purge the log dir
		pandora_purge_log_dir ($conf->{'_log_dir'}, $limits);
	}
}

###############################################################################
# Recursively delete old log files by sub directory.
###############################################################################
sub pandora_purge_log_dir ($$;$) {
	my ($dir, $limits, $depth) = @_;
	
	# Initial call
	if (! defined ($depth)) {
		$depth = 0;
	}

	# No limit for this depth
	if (! defined ($limits->[$depth])) {
		return;
	}

	# Open the dir
	my $dir_dh;
	if (! opendir($dir_dh, $dir)) {
		return;
	}

	# Purge sub dirs
	while (my $sub_dir = readdir ($dir_dh)) {

		next if ($sub_dir eq '.' || $sub_dir eq '..' || ! -d $dir . '/' . $sub_dir);
				
		# Sub dirs have names that represent a year, month, day or hour
		if ($sub_dir < $limits->[$depth]) {
			rmtree ($dir . '/' . $sub_dir);
		} elsif ($sub_dir == $limits->[$depth]) {
			&pandora_purge_log_dir ($dir . '/' . $sub_dir, $limits, $depth + 1)
		}
	}
	
	# Close the dir
	closedir ($dir_dh);
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
	
	# Convert compact interval length from hours to seconds
	my $step = $conf->{'_step_compact'} * 3600;

	# The oldest timestamp will be the lower limit
	my $limit_utime = get_db_value ($dbh, 'SELECT min(utimestamp) as min FROM tagente_datos');
	return unless (defined ($limit_utime) && $limit_utime > 0);

	# Calculate the start date
	my $start_utime = time() - $conf->{'_days_compact'} * 24 * 60 * 60;
	my $last_compact = $start_utime;
	my $stop_utime;

	# Do not compact the same data twice!
	if (defined ($conf->{'_last_compact'}) && $conf->{'_last_compact'} > $limit_utime) {
		$limit_utime  = $conf->{'_last_compact'};
	}
	
	if ($start_utime <= $limit_utime) {
		log_message ('COMPACT', "Data already compacted.");
		return;
	}
	
	log_message ('COMPACT', "Compacting data from " . strftime ("%Y-%m-%d %H:%M:%S", localtime($limit_utime)) . " to " . strftime ("%Y-%m-%d %H:%M:%S", localtime($start_utime)) . '.', '');

	# Prepare the query to retrieve data from an interval
	while (1) {

			# Calculate the stop date for the interval
			$stop_utime = $start_utime - $step;

			# Out of limits
			last if ($start_utime < $limit_utime);

			# Mark the progress
			log_message ('', ".");
			
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

			usleep (1000); # Very small usleep, just to don't burn the DB
			# Move to the next interval
			$start_utime = $stop_utime;
	}
	log_message ('', "\n");

	# Mark the last compact date
	if (defined ($conf->{'_last_compact'})) {
		db_do ($dbh, 'UPDATE tconfig SET value=? WHERE token=?', $last_compact, 'last_compact');
	} else {
		db_do ($dbh, 'INSERT INTO tconfig (value, token) VALUES (?, ?)', $last_compact, 'last_compact');
	}
}

##############################################################################
# Check command line parameters.
##############################################################################
sub pandora_init ($) {
	my $conf = shift;

	log_message ('', "\nPandora FMS DB Tool $version Copyright (c) 2004-2009 Artica ST\n");
	log_message ('', "This program is Free Software, licensed under the terms of GPL License v2\n");
	log_message ('', "You can download latest versions and documentation at http://www.pandorafms.org\n\n");

	# Load config file from command line
	help_screen () if ($#ARGV < 0);
	
	$conf->{'_pandora_path'} = shift(@ARGV);
	$conf->{'_onlypurge'} = 0;
	$conf->{'_force'} = 0;
	
	# If there are valid parameters store it
	foreach my $param (@ARGV) {	
		# help!
		help_screen () if ($param =~ m/--*h\w*\z/i );
		if ($param =~ m/-p\z/i) {
			$conf->{'_onlypurge'} = 1;
		}
		elsif ($param =~ m/-v\z/i) {
			$conf->{'_verbose'} = 1;
		}
		elsif ($param =~ m/-q\z/i) {
			$conf->{'_quiet'} = 1;
		}
		elsif ($param =~ m/-d\z/i) {
			$conf->{'_debug'} = 1;
		}
		elsif ($param =~ m/-f\z/i) {
			$conf->{'_force'} = 1;
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

	# Read additional tokens from the DB
	my $dbh = db_connect ('mysql', $conf->{'dbname'}, $conf->{'dbhost'}, $conf->{'dbport'}, $conf->{'dbuser'}, $conf->{'dbpass'});

	$conf->{'_event_purge'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'event_purge'");
	$conf->{'_trap_purge'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'trap_purge'");
	$conf->{'_audit_purge'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'audit_purge'");
	$conf->{'_string_purge'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'string_purge'");
	$conf->{'_gis_purge'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'gis_purge'");

	$conf->{'_days_purge'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'days_purge'");
	$conf->{'_days_compact'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'days_compact'");
	$conf->{'_last_compact'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'last_compact'");
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
	$conf->{'_enterprise_installed'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'enterprise_installed'");
	$conf->{'_metaconsole'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'metaconsole'");
	$conf->{'_metaconsole_events_history'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'metaconsole_events_history'");
	$conf->{'_netflow_max_lifetime'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'netflow_max_lifetime'");
	$conf->{'_netflow_nfexpire'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'netflow_nfexpire'");
   	$conf->{'_netflow_path'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'netflow_path'");
   	$conf->{'_log_dir'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'log_dir'");
   	$conf->{'_log_max_lifetime'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'log_max_lifetime'");
   	
	db_disconnect ($dbh);

	log_message ('', "Pandora DB now initialized and running (PURGE=" . $conf->{'_days_purge'} . " days, COMPACT=$conf->{'_days_compact'} days, STEP=" . $conf->{'_step_compact'} . ") . \n\n");
}


###############################################################################
# Check database integrity
###############################################################################

sub pandora_checkdb_integrity {
	my $dbh = shift;

    log_message ('INTEGRITY', "Cleaning up group stats.");

    # Delete all records on tgroup_stats
    db_do ($dbh, 'DELETE FROM tgroup_stat');

	
    #print "[INTEGRITY] Deleting non-used IP addresses \n";
    # DISABLED - Takes too much time and benefits of this are unclear..
    # Delete all non-used IP addresses from taddress
    #db_do ($dbh, 'DELETE FROM taddress WHERE id_a NOT IN (SELECT id_a FROM taddress_agent)');

    log_message ('INTEGRITY', "Deleting orphan alerts.");

    # Delete alerts assigned to inexistant modules
    db_do ($dbh, 'DELETE FROM talert_template_modules WHERE id_agent_module NOT IN (SELECT id_agente_modulo FROM tagente_modulo)');

    log_message ('INTEGRITY', "Deleting orphan modules.");
    
    # Delete orphan modules in tagente_modulo
    db_do ($dbh, 'DELETE FROM tagente_modulo WHERE id_agente NOT IN (SELECT id_agente FROM tagente)');

    # Delete orphan modules in tagente_estado
    db_do ($dbh, 'DELETE FROM tagente_estado WHERE id_agente NOT IN (SELECT id_agente FROM tagente)');

    # Delete orphan data_inc reference records
    db_do ($dbh, 'DELETE FROM tagente_datos_inc WHERE id_agente_modulo NOT IN (SELECT id_agente_modulo FROM tagente_modulo)');
    
    # Check enterprise tables
    enterprise_hook ('pandora_checkdb_integrity_enterprise', [$dbh]);
}

###############################################################################
# Check database consistency.
###############################################################################
sub pandora_checkdb_consistency {
	my $dbh = shift;

	# 1. Check for modules that do not have tagente_estado but have tagente_module

	log_message ('CHECKDB', "Deleting non-init data.");
	my @modules = get_db_rows ($dbh, 'SELECT id_agente_modulo,id_agente FROM tagente_estado WHERE estado = 4');
	foreach my $module (@modules) {
		my $id_agente_modulo = $module->{'id_agente_modulo'};

		# Skip policy modules
		my $is_policy_module = enterprise_hook ('is_policy_module', [$dbh, $id_agente_modulo]);
		next if (defined($is_policy_module) && $is_policy_module);

		# Skip if agent is disabled
		my $is_agent_disabled = get_db_value ($dbh, 'SELECT disabled FROM tagente WHERE id_agente = ?', $module->{'id_agente'});
		next if (defined($is_agent_disabled) && $is_agent_disabled);

		# Skip if module is disabled
		my $is_module_disabled = get_db_value ($dbh, 'SELECT disabled FROM tagente_modulo WHERE id_agente_modulo = ?', $module->{'id_agente_modulo'});
		next if (defined($is_module_disabled) && $is_module_disabled);

		# Delete the module
		db_do ($dbh, 'DELETE FROM tagente_modulo WHERE id_agente_modulo = ?', $id_agente_modulo);

		# Do a nanosleep here for 0,001 sec
		usleep (100000);

		# Delete any alerts associated to the module
		db_do ($dbh, 'DELETE FROM talert_template_modules WHERE id_agent_module = ?', $id_agente_modulo);
	}

	log_message ('CHECKDB', "Deleting unknown data (More than " . $conf{'_days_delete_unknown'} . " days).");
	if (defined ($conf{'_days_delete_unknown'}) && $conf{'_days_delete_unknown'} > 0) {
		my @modules = get_db_rows ($dbh, 'SELECT tagente_modulo.id_agente_modulo, tagente_modulo.id_agente FROM tagente_modulo, tagente_estado WHERE tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo AND estado = 3 AND utimestamp < UNIX_TIMESTAMP() - ?', 86400 * $conf{'_days_delete_unknown'});
		foreach my $module (@modules) {
			my $id_agente_modulo = $module->{'id_agente_modulo'};
	
			# Skip policy modules
			my $is_policy_module = enterprise_hook ('is_policy_module', [$dbh, $id_agente_modulo]);
			next if (defined($is_policy_module) && $is_policy_module);
	
			# Delete the module
			db_do ($dbh, 'DELETE FROM tagente_modulo WHERE disabled = 0 AND id_agente_modulo = ?', $id_agente_modulo);
			
			# Delete any alerts associated to the module
			db_do ($dbh, 'DELETE FROM talert_template_modules WHERE id_agent_module = ? AND NOT EXISTS (SELECT id_agente_modulo FROM tagente_modulo WHERE id_agente_modulo = ?)', $id_agente_modulo, $id_agente_modulo);

			# Do a nanosleep here for 0,001 sec
			usleep (100000);
		}
	}
	log_message ('CHECKDB', "Checking database consistency (Missing status).");

	@modules = get_db_rows ($dbh, 'SELECT * FROM tagente_modulo');
	foreach my $module (@modules) {
		my $id_agente_modulo = $module->{'id_agente_modulo'};
		my $id_agente = $module->{'id_agente'};

		# check if exist in tagente_estado and create if not
		my $count = get_db_value ($dbh, 'SELECT COUNT(*) FROM tagente_estado WHERE id_agente_modulo = ?', $id_agente_modulo);
		next if (defined ($count) && $count > 0);

		db_do ($dbh, 'INSERT INTO tagente_estado (id_agente_modulo, datos, timestamp, estado, id_agente, last_try, utimestamp, current_interval, running_by, last_execution_try) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', $id_agente_modulo, 0, '1970-01-01 00:00:00', 1, $id_agente, '1970-01-01 00:00:00', 0, 0, 0, 0);
		log_message ('CHECKDB', "Inserting module $id_agente_modulo in state table.");
	}

	log_message ('CHECKDB', "Checking database consistency (Missing module).");
	# 2. Check for modules in tagente_estado that do not have tagente_modulo, if there is any, delete it

	@modules = get_db_rows ($dbh, 'SELECT * FROM tagente_estado');
	foreach my $module (@modules) {
		my $id_agente_modulo = $module->{'id_agente_modulo'};

		# check if exist in tagente_estado and create if not
		my $count = get_db_value ($dbh, 'SELECT COUNT(*) FROM tagente_modulo WHERE id_agente_modulo = ?', $id_agente_modulo);
		next if (defined ($count) && $count > 0);
	
		db_do ($dbh, 'DELETE FROM tagente_estado WHERE id_agente_modulo = ?', $id_agente_modulo);

		# Do a nanosleep here for 0,001 sec
		usleep (100000);

		log_message ('CHECKDB', "Deleting non-existing module $id_agente_modulo in state table.");
	}
}

##############################################################################
# Print a help screen and exit.
##############################################################################
sub help_screen{
	log_message ('', "Usage: $0 <path to pandora_server.conf> [options]\n\n");
	log_message ('', "\t\t-p   Only purge and consistency check, skip compact.\n");
	log_message ('', "\t\t-f   Force execution event if another instance of $0 is running.\n\n");
	exit -1;
}

##############################################################################
# Delete old module data.
##############################################################################
sub pandora_delete_old_module_data {
	my ($dbh, $table, $ulimit_access_timestamp, $ulimit_timestamp) = @_;
	
	my $first_mark;
	my $total_time;
	my $purge_steps;
	my $purge_count;

	my $mark1;
	my $mark2;

	# This could be very timing consuming, so make this operation in $BIG_OPERATION_STEP 
	# steps (100 fixed by default)
	# Starting from the oldest record on the table

	# WARNING. This code is EXTREMELLY important. This block (data deletion) could KILL a database if 
	# you alter code and you don't know exactly what are you doing. Please take in mind this code executes each hour
	# and has been patches MANY times. Before altering anything, think twice !

	$first_mark =  get_db_value ($dbh, "SELECT utimestamp FROM $table ORDER BY utimestamp ASC LIMIT 1");
	if (defined ($first_mark)) {
		$total_time = $ulimit_timestamp - $first_mark;
		$purge_steps = int($total_time / $BIG_OPERATION_STEP);
		if ($purge_steps > 0) {
			for (my $ax = 1; $ax <= $BIG_OPERATION_STEP; $ax++){
	
				$mark1 = $first_mark + ($purge_steps * $ax);
				$mark2 = $first_mark + ($purge_steps * ($ax -1));	

				# Let's split the intervals in $SMALL_OPERATION_STEP deletes each
				$purge_count = get_db_value ($dbh, "SELECT COUNT(id_agente_modulo) FROM $table WHERE utimestamp < $mark1 AND utimestamp >= $mark2");
				while ($purge_count > 0){
					db_do ($dbh, "DELETE FROM $table WHERE utimestamp < $mark1 AND utimestamp >= $mark2 LIMIT $SMALL_OPERATION_STEP");
					# Do a nanosleep here for 0,001 sec
					usleep (10000);
					$purge_count = $purge_count - $SMALL_OPERATION_STEP;
				}
				
				log_message ('PURGE', "Deleting old data from $table. $ax%", "\r");
			}
			log_message ('', "\n");
		} else {
			log_message ('PURGE', "No data to purge in $table.");
		}
	} else {
		log_message ('PURGE', "No data in $table.");
	}
}

###############################################################################
# Main
###############################################################################
sub pandoradb_main ($$$) {
	my ($conf, $dbh, $history_dbh) = @_;

	log_message ('', "Starting at ". strftime ("%Y-%m-%d %H:%M:%S", localtime()) . "\n");

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

	log_message ('', "Ending at ". strftime ("%Y-%m-%d %H:%M:%S", localtime()) . "\n");
}

# Init
pandora_init(\%conf);

# Read config file
pandora_load_config (\%conf);

# Load enterprise module
if (enterprise_load (\%conf) == 0) {
	log_message ('', " [*] Pandora FMS Enterprise module not available.\n\n");
} else {
	log_message ('', " [*] Pandora FMS Enterprise module loaded.\n\n");
}

# Connect to the DB
my $dbh = db_connect ('mysql', $conf{'dbname'}, $conf{'dbhost'}, $conf{'dbport'}, $conf{'dbuser'}, $conf{'dbpass'});
my $history_dbh = ($conf{'_history_db_enabled'} eq '1') ? db_connect ('mysql', $conf{'_history_db_name'},
		$conf{'_history_db_host'}, '3306', $conf{'_history_db_user'}, $conf{'_history_db_pass'}) : undef;

# Get a lock
my $lock = db_get_lock ($dbh, 'pandora_db');
if ($lock == 0 && $conf{'_force'} == 0) { 
	log_message ('', " [*] Another instance of pandora_db seems to be running.\n\n");
	exit 1;
}

# Main
pandoradb_main(\%conf, $dbh, $history_dbh);

# Cleanup and exit
db_disconnect ($history_dbh) if defined ($history_dbh);
db_disconnect ($dbh);

# Release the lock
if ($lock == 1) {
	db_release_lock ($dbh, 'pandora_db');
}

exit 0;
