#!/usr/bin/perl

###############################################################################
# Pandora FMS DB Management
###############################################################################
# Copyright (c) 2005-2023 Pandora FMS
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

use List::Util qw(min);
use List::Util qw(sum);

# Default lib dir for RPM and DEB packages
BEGIN { push @INC, '/usr/lib/perl5'; }

use PandoraFMS::Core;
use PandoraFMS::Tools;
use PandoraFMS::Config;
use PandoraFMS::DB;

# version: define current version
my $version = "7.0NG.776 Build 240326";

# Pandora server configuration
my %conf;

# History DB configuration
my $h_conf;

# Long operations are divided in XX steps for performance
my $BIG_OPERATION_STEP = 100;	# 100 is default

# Each long operations has a LIMIT of SMALL_OPERATION_STEP to avoid locks. 
#Increate to 3000~5000 in fast systems decrease to 500 or 250 on systems with locks
my $SMALL_OPERATION_STEP = 1000;	# 1000 is default

# Timeout for lock acquisition.
my $LOCK_TIMEOUT = 60;

# Cron cookies file.
my $COOKIES_FILE = '/tmp/cron-session-cookies';

# FLUSH in each IO 
$| = 1;

########################################################################
# Print the given message with a preceding timestamp.
########################################################################
sub log_message ($$;$) {
	my ($source, $message, $eol) = @_;
	
	# Set a default end of line
	$eol = "\n" unless defined ($eol);
	
	if ($source eq '') {
		print $message;
	}
	else {
		print strftime("%H:%M:%S", localtime()) . ' [' . $source . '] ' . $message . $eol;
	}
}

########################################################################
# Print the given message and writes on error log.
########################################################################
sub log_error_message ($$) {
	my ($conf, $message) = @_;
	log_message('',,"$message\n\n");
	log_error_writter($conf, "$message\n");
}

########################################################################
# Delete old data from the database.
########################################################################
sub pandora_purgedb ($$$) {
	my ($conf, $dbh, $h_conf) = @_;
	
	# 1) Obtain last value for date limit
	# 2) Delete all elements below date limit
	# 3) Insert last value in date_limit position
	
	# Calculate limit for deletion, today - $conf->{'_days_purge'}
	
	my $timestamp = strftime ("%Y-%m-%d %H:%M:%S", localtime());
	my $ulimit_access_timestamp = time() - 86400;
	my $ulimit_timestamp = time() - (86400 * $conf->{'_days_purge'});
	my $first_mark;
	my $total_time;
	my $purge_steps;
	my $purge_count;
	
	# Delete extended session data
	if (enterprise_load (\%conf) != 0) {
		db_do ($dbh, "DELETE FROM tsesion_extended
			WHERE id_sesion NOT IN ( SELECT id_sesion FROM tsesion )");
		log_message ('PURGE', 'Deleting old extended session data.');
	}

	# Delete old inventory data
	if (defined ($conf->{'_inventory_purge'}) && $conf->{'_inventory_purge'} > 0) {
		if (enterprise_load (\%conf) != 0) {
			my $ulimit_timestamp_inventory = time() - (86400 * $conf->{'_inventory_purge'});

		    log_message ('PURGE', 'Deleting old inventory data.');

			# This could be very timing consuming, so make 
		    # this operation in $BIG_OPERATION_STEP 
			# steps (100 fixed by default)
			# Starting from the oldest record on the table

			$first_mark =  get_db_value_limit ($dbh, 'SELECT utimestamp FROM tagente_datos_inventory ORDER BY utimestamp ASC', 1);
			if (defined ($first_mark)) {
				$total_time = $ulimit_timestamp_inventory - $first_mark;
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
	}

	# Delete manually disabled  agents after some period
	if (defined ($conf->{'_delete_disabled_agents'}) && $conf->{'_delete_disabled_agents'} > 0) {
		log_message('PURGE', "Deleting old disabled agents (More than " . $conf->{'_delete_disabled_agents'} . " days).");
		my @agents_to_delete = get_db_rows (
			$dbh,
			"SELECT id_agente FROM tagente 
				WHERE UNIX_TIMESTAMP(ultimo_contacto) + ? < UNIX_TIMESTAMP(NOW())
				AND disabled = 1
				AND modo != 2",
			$conf->{'_delete_disabled_agents'} * 8600
		);

		foreach my $agent_to_delete (@agents_to_delete) {
			pandora_delete_agent($dbh, $agent_to_delete->{'id_agente'}, $conf);
		}
	}

	# Delete old data
	if (!defined($conf->{'_days_purge'})){
		log_message ('PURGE', 'days_purge is not defined. Old data will not be deleted.');
	}
	elsif ($conf->{'_days_purge'} > 0) {

		# Delete old numeric data
		pandora_delete_old_module_data ($dbh, 'tagente_datos', $ulimit_access_timestamp, $ulimit_timestamp);

		# Delete old export data
		pandora_delete_old_export_data ($dbh, $ulimit_timestamp);
		
		# Delete sessions data
		pandora_delete_old_session_data (\%conf, $dbh, $ulimit_timestamp);
	}
	else {
		log_message ('PURGE', 'days_purge is set to 0. Old data will not be deleted.');
	}

	pandora_delete_old_tplanned_downtime(\%conf, $dbh, $h_conf);

	# String data deletion
	$conf->{'_string_purge'} //= 7;

	if ($conf->{'_string_purge'} > 0) {
		$ulimit_access_timestamp = time() - 86400;
		$ulimit_timestamp = time() - (86400 * $conf->{'_string_purge'});
		pandora_delete_old_module_data ($dbh, 'tagente_datos_string', $ulimit_access_timestamp, $ulimit_timestamp);
	}
	else {
		log_message ('PURGE', 'string_purge is set to 0. Old string data will not be deleted.');
	}

	# Delete event data
	$conf->{'_event_purge'} //= 10;

	if ($conf->{'_event_purge'} > 0) {
		my $event_limit = time() - 86400 * $conf->{'_event_purge'};
		
		log_message ('PURGE', "Deleting old event data from tevento (More than " . $conf->{'_event_purge'} . " days).", '');

		# Delete with buffer to avoid problems with performance
		my $events_to_delete = get_db_value ($dbh, "SELECT COUNT(*) FROM tevento WHERE utimestamp < ?", $event_limit);
		while($events_to_delete > 0) {
			db_delete_limit($dbh, 'tevento', "utimestamp < ?", $BIG_OPERATION_STEP, $event_limit);
			$events_to_delete = $events_to_delete - $BIG_OPERATION_STEP;
			
			# Mark the progress
			log_message ('', ".");
			
			# Do not overload the MySQL server
			usleep (10000);
		}
		log_message ('', "\n");
	}
	else {
		log_message ('PURGE', 'event_purge is set to 0. Old events will not be deleted.');
	}

	# Delete audit data
	$conf->{'_audit_purge'} //= 7;
	if ($conf->{'_audit_purge'} > 0) {
		log_message ('PURGE', "Deleting old audit data (More than " . $conf->{'_audit_purge'} . " days).");
		my $audit_limit = time() - 86400 * $conf->{'_audit_purge'};
		db_do($dbh, "DELETE FROM tsesion WHERE utimestamp < $audit_limit");
	}
	else {
		log_message ('PURGE', 'audit_purge is set to 0. Old audit data will not be deleted.');
	}

	# Delete SNMP trap data
	$conf->{'_trap_purge'} //= 7;
	
	if ($conf->{'_trap_purge'} > 0) {
		log_message ('PURGE', "Deleting old SNMP traps (More than " . $conf->{'_trap_purge'} . " days).");

		my $trap_limit = strftime ("%Y-%m-%d %H:%M:%S", localtime(time() - 86400 * $conf->{'_trap_purge'}));
		db_do($dbh, "DELETE FROM ttrap WHERE timestamp < '$trap_limit'");
	}
	else {
		log_message ('PURGE', 'trap_purge is set to 0. Old SNMP traps will not be deleted.');
	}

	# Cleanup policy groups pointing to non-existing policies.
	enterprise_hook('pandora_policy_group_cleanup', [$dbh]);
	
	# Delete policy queue data
	enterprise_hook("pandora_purge_policy_queue", [$dbh, $conf]);

	# Delete policy queue data
	enterprise_hook("pandora_purge_service_elements", [$dbh, $conf]);

	# Delete GIS  data
	$conf->{'_gis_purge'} //= 15;
	
	if ($conf->{'_gis_purge'} > 0) {
		log_message ('PURGE', "Deleting old GIS data (More than " . $conf->{'_gis_purge'} . " days).");
		my $gis_limit = strftime ("%Y-%m-%d %H:%M:%S", localtime(time() - 86400 * $conf->{'_gis_purge'}));
		db_do($dbh, "DELETE FROM tgis_data_history WHERE end_timestamp < '$gis_limit'");
	}
	else {
		log_message ('PURGE', 'gis_purge is set to 0. Old GIS data will not be deleted.');
	}

	# Delete pending modules
	log_message ('PURGE', "Deleting pending delete modules (data table).", '');
	my @deleted_modules = get_db_rows ($dbh, 'SELECT id_agente_modulo FROM tagente_modulo WHERE delete_pending = 1');
	foreach my $module (@deleted_modules) {
	    
	    my $buffer = 1000;
	    my $id_module = $module->{'id_agente_modulo'};

		db_do ($dbh, 'UPDATE tagente_modulo SET parent_module_id=0 WHERE parent_module_id=?', $id_module);

		log_message ('', ".");
		
		while(1) {
			my $nstate = get_db_value ($dbh, 'SELECT count(id_agente_modulo) FROM tagente_estado WHERE id_agente_modulo=?', $id_module);			
			last if($nstate == 0);
			
			db_delete_limit ($dbh, 'tagente_estado', 'id_agente_modulo=?', $buffer, $id_module);
		}
	}
	log_message ('', "\n");

	log_message ('PURGE', "Deleting pending delete modules (status, module table).");
	db_do ($dbh, "DELETE FROM tagente_estado WHERE id_agente_modulo IN (SELECT id_agente_modulo FROM tagente_modulo WHERE delete_pending = 1)");
	db_do ($dbh, "DELETE FROM tagente_modulo WHERE delete_pending = 1");

	log_message ('PURGE', "Deleting old access data (More than 24hr)");


	# Purge the reports
   	if (defined($conf->{'_enterprise_installed'}) && $conf->{'_enterprise_installed'} eq '1' &&
		defined($conf->{'_metaconsole'}) && $conf->{'_metaconsole'} eq '1'){
		log_message ('PURGE', "Metaconsole enabled, ignoring reports.");
	} else {
		my @blacklist_types = ("'SLA_services'", "'custom_graph'", "'sql_graph_vbar'", "'sql_graph_hbar'",
			"'sql_graph_pie'", "'database_serialized'", "'sql'", "'inventory'", "'inventory_changes'",
			"'netflow_area'", "'netflow_data'", "'netflow_summary'", "'netflow_top_N'");
		my $blacklist_types_str = join(',', @blacklist_types);
		
		# Deleted modules
		log_message ('PURGE', "Delete contents in report that have some deleted modules.");
		db_do ($dbh, "DELETE FROM treport_content
					  WHERE id_agent_module != 0
						AND id_agent_module NOT IN (SELECT id_agente_modulo FROM tagente_modulo)
						AND ${RDBMS_QUOTE}type${RDBMS_QUOTE} NOT IN ($blacklist_types_str)");
		db_do ($dbh, "DELETE FROM treport_content_item
					  WHERE id_agent_module != 0
						AND id_agent_module NOT IN (SELECT id_agente_modulo FROM tagente_modulo)
						AND id_report_content NOT IN (SELECT id_rc FROM treport_content WHERE ${RDBMS_QUOTE}type${RDBMS_QUOTE} IN ($blacklist_types_str))");
		db_do ($dbh, "DELETE FROM treport_content_sla_combined
					  WHERE id_agent_module != 0
						AND id_agent_module NOT IN (SELECT id_agente_modulo FROM tagente_modulo)
						AND id_report_content NOT IN (SELECT id_rc FROM treport_content WHERE ${RDBMS_QUOTE}type${RDBMS_QUOTE} = 'SLA_services')");
		
		# Deleted agents
		log_message ('PURGE', "Delete contents in report that have some deleted agents.");
		db_do ($dbh, "DELETE FROM treport_content
					  WHERE id_agent != 0
						AND id_agent NOT IN (SELECT id_agente FROM tagente)
						AND ${RDBMS_QUOTE}type${RDBMS_QUOTE} NOT IN ($blacklist_types_str)");
		
		# Empty contents
		log_message ('PURGE', "Delete empty contents in report (like SLA or Exception).");
		db_do ($dbh, "DELETE FROM treport_content
					  WHERE ${RDBMS_QUOTE}type${RDBMS_QUOTE} LIKE 'exception'
						AND id_rc NOT IN (SELECT id_report_content FROM treport_content_item)");
		db_do ($dbh, "DELETE FROM treport_content
					  WHERE ${RDBMS_QUOTE}type${RDBMS_QUOTE} IN ('SLA', 'SLA_monthly', 'SLA_services')
						AND id_rc NOT IN (SELECT id_report_content FROM treport_content_sla_combined)");
	}
	
	# Delete disabled autodisable agents after some period
	if (defined ($conf->{'_days_autodisable_deletion'}) && $conf->{'_days_autodisable_deletion'} > 0) {
		log_message ('PURGE', 'Delete autodisabled agents where last contact is bigger than ' . $conf->{'_days_autodisable_deletion'} . ' days.');
		my @agents_autodisable_to_delete = get_db_rows (
			$dbh,
			"SELECT id_agente FROM tagente 
				WHERE UNIX_TIMESTAMP(ultimo_contacto) + ? < UNIX_TIMESTAMP(NOW())
				AND disabled = 1
				AND modo = 2",
			$conf->{'_days_autodisable_deletion'} * 8600
		);

		foreach my $agent_autodisable_to_delete (@agents_autodisable_to_delete) {
			pandora_delete_agent($dbh, $agent_autodisable_to_delete->{'id_agente'}, $conf);
		}
	}
	
	# Delete old netflow data
	if (!defined($conf->{'_netflow_max_lifetime'})){
		log_message ('PURGE', 'netflow_max_lifetime is not defined. Old netflow data will not be deleted.');
	}
	elsif ($conf->{'_netflow_max_lifetime'} > 0) {
		log_message ('PURGE', "Deleting old netflow data.");
		if (! defined ($conf->{'_netflow_path'}) || ! -d $conf->{'_netflow_path'}) {
			log_message ('!', "Netflow data directory does not exist, skipping.");
		}
		elsif (! -x $conf->{'_netflow_nfexpire'}) {
			log_message ('!', "Cannot execute " . $conf->{'_netflow_nfexpire'} . ", skipping.");
		}
		else {
			# Update stats file with max lifetime.
			`yes 2>/dev/null | $conf->{'_netflow_nfexpire'} -u "$conf->{'_netflow_path'}" -t $conf->{'_netflow_max_lifetime'}d -w 100`;
			# Rescan directory.
			`yes 2>/dev/null | $conf->{'_netflow_nfexpire'} -r "$conf->{'_netflow_path'}"`;
			# Expire files
			`yes 2>/dev/null | $conf->{'_netflow_nfexpire'} -e "$conf->{'_netflow_path'}"`;
		}
	}
	else {
		log_message ('PURGE', 'netflow_max_lifetime is set to 0. Old netflow data will not be deleted.');
	}

	# Delete old sflow data
	if (!defined($conf->{'_sflow_max_lifetime'})){
		log_message ('PURGE', 'sflow_max_lifetime is not defined. Old sflow data will not be deleted.');
	}
	elsif ($conf->{'_sflow_max_lifetime'} > 0) {
		log_message ('PURGE', "Deleting old sflow data.");
		if (! defined ($conf->{'_sflow_path'}) || ! -d $conf->{'_sflow_path'}) {
			log_message ('!', "sflow data directory does not exist, skipping.");
		}
		elsif (! -x $conf->{'_sflow_nfexpire'}) {
			log_message ('!', "Cannot execute " . $conf->{'_sflow_nfexpire'} . ", skipping.");
		}
		else {
			`yes 2>/dev/null | $conf->{'_sflow_nfexpire'} -r "$conf->{'_sflow_path'}" -t $conf->{'_sflow_max_lifetime'}d`;
		}
	}
	else {
		log_message ('PURGE', 'sflow_max_lifetime is set to 0. Old sflow data will not be deleted.');
	}
	
	# Delete old log data
	log_message ('PURGE', "Deleting old log data.");
	if (defined($conf->{'_days_purge_old_information'}) && $conf->{'_days_purge_old_information'} > 0) {
		log_message ('PURGE', 'Deleting log data older than ' . $conf->{'_days_purge_old_information'} . ' days.');
    	enterprise_hook ('pandora_purge_logs', [$dbh, $conf]);
	}
	else {
		log_message ('PURGE', 'days_purge_old_data is set to 0. Old log data will not be deleted.');
	}

	# Delete old log data
	log_message ('PURGE', "Deleting old network configuration manager data.");
	if (defined($conf->{'_days_purge'}) && $conf->{'_days_purge'} > 0) {
		log_message ('PURGE', 'Deleting NCM data older than ' . $conf->{'_days_purge'} . ' days.');
    enterprise_hook ('pandora_purge_ncm', [$dbh, \&log_message, $conf->{'_days_purge'}, $conf->{'_history_db_step'}, $conf->{'_history_db_delay'}]);
	} else {
		log_message ('PURGE', '_days_purge is set to 0. Old network configuration manager data will not be deleted.');
	}

	# Delete old special days
	log_message ('PURGE', "Deleting old special days.");
	if (defined($conf->{'_num_past_special_days'}) && $conf->{'_num_past_special_days'} > 0) {
		log_message ('PURGE', 'Deleting special days older than ' . $conf->{'_num_past_special_days'} . ' days.');
		if (${RDBMS} eq 'oracle') {
			db_do ($dbh, "DELETE FROM talert_special_days
				WHERE \"date\" < SYSDATE - $conf->{'_num_past_special_days'} AND \"date\" > '0001-01-01'");
		}
		elsif (${RDBMS} eq 'mysql') { 
			db_do ($dbh, "DELETE FROM talert_special_days
				WHERE date < CURDATE() - $conf->{'_num_past_special_days'} AND date > '0001-01-01'");
		}
	}

	# Delete old tgraph_source data
	log_message ('PURGE', 'Deleting old tgraph_source data.');
	db_do ($dbh,"DELETE FROM tgraph_source WHERE id_graph NOT IN (SELECT id_graph FROM tgraph)");

	# Delete old messages
	log_message ('PURGE', "Deleting old messages.");
	if (defined($conf->{'_delete_old_messages'}) && $conf->{'_delete_old_messages'} > 0) {
		my $message_limit = time() - 86400 * $conf->{'_delete_old_messages'};
		db_do ($dbh, "DELETE FROM tmensajes WHERE timestamp < ?", $message_limit);
	}

	# Delete old cache data
	log_message ('PURGE', "Deleting old cache data.");
	db_do ($dbh, "DELETE FROM `tvisual_console_elements_cache` WHERE (UNIX_TIMESTAMP(`created_at`) + `expiration`) < UNIX_TIMESTAMP()");
}

###############################################################################
# Compact agent data.
###############################################################################
sub pandora_compactdb {
    my ($conf, $dbh, $dbh_conf) = @_;

    my $total_modules = get_db_value($dbh, "SELECT COUNT(id_agente_modulo) FROM tagente_modulo");

    # Interval in hours to compact.
    my $compaction_interval = 24;
    my $compaction_factor = (3600 * $compaction_interval) / 300;

	# Number of agents to be proceced on this execution
    my $agents_limit = int($total_modules / $compaction_factor);

    my $last_compact_offset = pandora_get_config_value($dbh, "last_compact_offset");

    if ($last_compact_offset eq "") {
        db_do($dbh, "INSERT INTO tconfig (token, value) VALUES ('last_compact_offset', '0')");
        $last_compact_offset = 0;
    }

	# Obtain a group of modules to compact.
    my @module_groups = get_db_rows(
		$dbh, 
		'SELECT id_agente_modulo, id_tipo_modulo, UNIX_TIMESTAMP(last_compact) as last_compact FROM tagente_modulo WHERE id_agente_modulo > ? LIMIT ?',
        $last_compact_offset, 
		$agents_limit
	);

	# Compact the group of modules.
	my $starting_time = time();
	pandora_compact_modules($dbh, $conf, @module_groups);
	my $ending_time = time();
	log_message('COMPACT', "Time taken: " . ($ending_time - $starting_time) . " seconds.");

	# Add the offset.
    $last_compact_offset += $agents_limit;

	# If the offset is higher than the max module start again
    $last_compact_offset = 0 if ($last_compact_offset >= $total_modules);

	# Save the new offset
    db_do($dbh, "UPDATE tconfig SET value = ? WHERE token = 'last_compact_offset'", $last_compact_offset);
}

sub pandora_compact_modules {
	my ($dbh, $conf, @module_groups) = @_;

	# Obtain the minimun last compact from the block
	my $min_compact = min(map { $_->{"last_compact"} } @module_groups);

	# if the min has not been setted
	if($min_compact == 0){
		$min_compact = get_db_value(
			$dbh, 
			'SELECT MIN(td.utimestamp) 
		 	 FROM tagente_datos td, tagente_modulo tm 
		     WHERE td.id_agente_modulo = tm.id_agente_modulo
		     AND tm.id_tipo_modulo not in (2, 6, 9, 18, 21, 31, 35, 100)
		 	 AND td.id_agente_modulo BETWEEN ? AND ?', 
			$module_groups[0]->{"id_agente_modulo"}, $module_groups[-1]->{"id_agente_modulo"}
		);		
	}

	# One week of data of data as max from the last min compact.
	my $max_compact = $min_compact + (60 * 60 * 24 * 7);

	# If the last compact is on the future set the current time as max.
	$max_compact = time() if($max_compact > time());

	log_message('COMPACT', "Compacting data of agents agents $module_groups[0]->{'id_agente_modulo'} to $module_groups[-1]->{'id_agente_modulo'}, data between " . strftime('%Y-%m-%d %H:%M:%S', localtime($min_compact)) . " and " . strftime('%Y-%m-%d %H:%M:%S', localtime($max_compact)));

	# Obtain all the data from the interval to avoid multiple requests
	# Avoid pull data from modules that should be skipped.
	my @data = get_db_rows (
		$dbh, 
		'SELECT td.id_agente_modulo, td.datos, td.utimestamp 
		 FROM tagente_datos td, tagente_modulo tm 
		 WHERE td.id_agente_modulo = tm.id_agente_modulo
		 AND tm.id_tipo_modulo not in (2, 6, 9, 18, 21, 31, 35, 100)
		 AND td.utimestamp < ? AND td.utimestamp >= ? 
		 AND td.id_agente_modulo BETWEEN ? AND ?
		', 
		$max_compact, $min_compact, $module_groups[0]->{"id_agente_modulo"}, $module_groups[-1]->{"id_agente_modulo"}
	);

	my $total_data = scalar(@data);
	my $proceced_total_data = 0;
	my $progress = 0;
	my $compactations = 0;

	# Convert compact interval length from hours to seconds
	my $step = $conf->{'_step_compact'} * 3600;

	# Max. 168 steps or 7 days in one hour steps. 
    # This avoids blocking of old modules without last compact
    my $step_limit = 24 * 7; 

	# Compact the modules in this block
	foreach my $module (@module_groups) {
		$progress = $total_data == 0 ? 0 : ($proceced_total_data / $total_data) * 100;
		printf(strftime("\r" . "%H:%M:%S", localtime()) . ' [COMPACT] ' . "Progress: %.2f%%", $progress);
		
		my $id = $module->{"id_agente_modulo"};  
		my $module_type = $module->{"id_tipo_modulo"}; 

        next unless defined ($module_type);
        next if ($module_type == 2 || $module_type == 6 || $module_type == 9 || $module_type == 18 || $module_type == 21 || $module_type == 31 || $module_type == 35 || $module_type == 100);

		# Obtain the data just for this module
		my @module_data = grep { $_->{"id_agente_modulo"} == $id } @data;
		my $total_elements = scalar(@module_data);

		# No data for this module.
		next if ($total_elements == 0);

		# Obtain the last compact of this module to avoid compact something previously compacted
		my $last_compact = $module ->{"last_compact"};

		# In case that the last compact has not been defined take the minimun data as last compact
		$last_compact = min(map { $_->{"utimestamp"} } @module_data)-1 if($last_compact == 0);

		# Create an array to store INSERT commands
		my @insert_commands;
	 	my $insert_command = 'INSERT INTO tagente_datos (id_agente_modulo, datos, utimestamp) VALUES (?, ?, ?)';
		
		my $step_number = 0;
		my $first_compact = $last_compact;
		
		# Compact using the steps
		while($step_number < $step_limit){
            
			my $next_compact = $last_compact + $step;
			
			last if($next_compact > time());

			# Obtain the data between the last compact and the next step.
			my @data_in_range = grep { $_->{"utimestamp"} > $last_compact && $_->{"utimestamp"} <= $next_compact } @module_data;

            my $total_range_elements = scalar(@data_in_range);

            # Nothing to compress, skip this step.
			if($total_range_elements == 0){
				$last_compact = $next_compact;
				next;
			}

			my $total_data = sum(map { $_->{"datos"} } @data_in_range);

			my $avg = $total_data / $total_range_elements;

			$proceced_total_data += $total_range_elements;
			
    		push @insert_commands, [$insert_command, $id, $avg, int($last_compact + ($step / 2))];

			$last_compact = $next_compact;
			
            $step_number +=1;
			# Small sleep to don't burn the DB
			usleep (1000); 
 
		}

		$dbh->begin_work;

		db_do ($dbh, 'DELETE FROM tagente_datos WHERE utimestamp > ? AND utimestamp <= ? AND id_agente_modulo = ?', $first_compact, $last_compact, $id);

		# Execute the INSERT commands
		foreach my $command (@insert_commands) {
			my ($sql, @params) = @$command;
			db_do($dbh, $sql, @params);
		}

		$dbh->commit;
		# Update the last compacted timestamp.
		db_do($dbh, "UPDATE tagente_modulo SET last_compact = FROM_UNIXTIME(?) WHERE id_agente_modulo = ?", $last_compact, $module ->{"id_agente_modulo"});
		$compactations += $step_number;
	}

	printf(strftime("\r" . "%H:%M:%S", localtime()) . ' [COMPACT] ' . "Progress: %.2f%%", 100);
	print("\n");
	log_message('COMPACT', "A total of $proceced_total_data elements has been compacted into $compactations elements");

}

########################################################################
# Check command line parameters.
########################################################################
sub pandora_init_pdb ($) {
	my $conf = shift;
	
	log_message ('', "Pandora FMS DB Tool v$version\n\n");
	log_message ('', "This program is Free Software, licensed under the terms of GPL License v2\n");
	log_message ('', "You can download latest versions and documentation at official web\n\n");
	
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

########################################################################
# Prepares conf read from historical database settings.
########################################################################
sub pandoradb_load_history_conf($) {
	my $dbh = shift;

	my @options = get_db_rows($dbh, 'SELECT * FROM `tconfig`');

	my %options = map 
	{
		'_' . $_->{'token'} => $_->{'value'}
	} @options;

	$options{'_days_autodisable_deletion'} = 0 unless defined ($options{'_days_autodisable_deletion'});
	$options{'_num_past_special_days'} = 0 unless defined($options{'_num_past_special_days'});
	$options{'_delete_old_messages'} = 0 unless defined($options{'_delete_old_messages'});
	$options{'_netflow_max_lifetime'} = 0 unless defined($options{'_netflow_max_lifetime'});
	$options{'claim_back_snmp_modules'} = 0 unless defined($options{'claim_back_snmp_modules'});

	return \%options;
}

########################################################################
# Read external configuration file.
########################################################################
sub pandora_load_config_pdb ($) {
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
	$conf->{'dbengine'} = 'mysql' unless defined ($conf->{'dbengine'});
	$conf->{'dbport'} = '3306' unless defined ($conf->{'dbport'});
	$conf->{'claim_back_snmp_modules'} = '1' unless defined ($conf->{'claim_back_snmp_modules'});
    $conf->{'verbosity'} = '3' unless defined ($conf->{'verbosity'});

	# Configure SSL.
	set_ssl_opts($conf);

    # Dynamic interval configuration.                                                                                                                             
	$conf->{"dynamic_constant"} = 0.10 unless defined($conf->{"dynamic_constant"});
	$conf->{"dynamic_warning"} = 0.10 unless defined($conf->{"dynamic_warning"});
	$conf->{"dynamic_updates"} = 5 unless defined($conf->{"dynamic_updates"});

	$conf->{'servername'} = $conf->{'servername'};
    $conf->{'servername'} = `hostname` unless defined ($conf->{'servername'});
	$conf->{"servername"} =~ s/\s//g;

	# workaround for name unconsistency (corresponding entry at pandora_server.conf is 'errorlog_file')
	$conf->{'errorlogfile'} = $conf->{'errorlog_file'};
	$conf->{'errorlogfile'} = "/var/log/pandora_server.error" unless defined ($conf->{'errorlogfile'});

	# The DB host was overridden by pandora_ha.
	$conf->{'ha_hosts_file'} = '/var/spool/pandora/data_in/conf/pandora_ha_hosts.conf' unless defined($conf->{'ha_hosts_file'});
	if (-f $conf->{'ha_hosts_file'}) {
		eval {
			open(my $fh, '<', $conf->{'ha_hosts_file'}) or return;
			my $dbhost = <$fh>;
			chomp($dbhost);
			if (defined($dbhost) && $dbhost ne '') {
				$conf->{'dbhost'} = $dbhost;
			}
			close($fh);
		};
	}
	print " [*] DB Host is " . $conf->{'dbhost'} . "\n";

	# Read additional tokens from the DB
	my $dbh = db_connect ($conf->{'dbengine'}, $conf->{'dbname'}, $conf->{'dbhost'}, $conf->{'dbport'}, $conf->{'dbuser'}, $conf->{'dbpass'});

	$conf->{'_event_purge'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'event_purge'");
	$conf->{'_trap_purge'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'trap_purge'");
	$conf->{'_trap_history_purge'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'trap_purge'");
	$conf->{'_audit_purge'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'audit_purge'");
	$conf->{'_string_purge'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'string_purge'");
	$conf->{'_gis_purge'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'gis_purge'");

	$conf->{'_days_purge'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'days_purge'");
	$conf->{'_days_compact'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'days_compact'");
	$conf->{'_last_compact'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'last_compact'");
	$conf->{'_step_compact'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'step_compact'");
	$conf->{'_history_db_enabled'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'history_db_enabled'");
	$conf->{'_history_event_enabled'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'history_event_enabled'");
	$conf->{'_history_trap_enabled'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'history_trap_enabled'");
	$conf->{'_history_db_host'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'history_db_host'");
	$conf->{'_history_db_port'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'history_db_port'");
	$conf->{'_history_db_name'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'history_db_name'");
	$conf->{'_history_db_user'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'history_db_user'");
	$conf->{'_history_db_pass'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'history_db_pass'");
	$conf->{'_history_db_days'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'history_db_days'");
	$conf->{'_history_db_adv'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'history_db_adv'");
	$conf->{'_history_db_string_days'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'history_db_string_days'");
	$conf->{'_history_event_days'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'history_event_days'");
	$conf->{'_history_trap_days'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'history_trap_days'");
	$conf->{'_history_db_step'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'history_db_step'");
	$conf->{'_history_db_delay'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'history_db_delay'");
	$conf->{'_days_delete_unknown'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'days_delete_unknown'");
	$conf->{'_days_delete_not_initialized'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'days_delete_not_initialized'");
	$conf->{'_delete_notinit'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'delete_notinit'");
	$conf->{'_inventory_purge'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'inventory_purge'");
	$conf->{'_delete_disabled_agents'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'delete_disabled_agents'");
	$conf->{'_delete_old_messages'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'delete_old_messages'");
	$conf->{'_enterprise_installed'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'enterprise_installed'");
	$conf->{'_metaconsole'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'metaconsole'");
	$conf->{'_netflow_max_lifetime'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'netflow_max_lifetime'");
	$conf->{'_netflow_nfexpire'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'netflow_nfexpire'");
 	$conf->{'_netflow_path'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'netflow_path'");
	$conf->{'_delete_notinit'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'delete_notinit'");
	$conf->{'_session_timeout'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'session_timeout'");

	$conf->{'_big_operation_step_datos_purge'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'big_operation_step_datos_purge'");
	$conf->{'_small_operation_step_datos_purge'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'small_operation_step_datos_purge'");
	$conf->{'_days_autodisable_deletion'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'days_autodisable_deletion'");
	$conf->{'_days_purge_old_information'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'Days_purge_old_information'");
	$conf->{'_elasticsearch_ip'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'elasticsearch_ip'");
	$conf->{'_elasticsearch_port'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'elasticsearch_port'");
	$conf->{'_server_unique_identifier'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'server_unique_identifier'");

	$BIG_OPERATION_STEP = $conf->{'_big_operation_step_datos_purge'}
					if ( $conf->{'_big_operation_step_datos_purge'} );
	$SMALL_OPERATION_STEP = $conf->{'_small_operation_step_datos_purge'}
					if ( $conf->{'_small_operation_step_datos_purge'} );

	$conf->{'_num_past_special_days'} = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = 'num_past_special_days'");
   	
	db_disconnect ($dbh);

	log_message ('', "DB Tool now initialized and running (PURGE=" . $conf->{'_days_purge'} . " days, COMPACT=$conf->{'_days_compact'} days, STEP=" . $conf->{'_step_compact'} . ") . \n\n");
}


###############################################################################
# Check database integrity
###############################################################################

sub pandora_checkdb_integrity {
	my ($conf, $dbh) = @_;

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
     while (defined (get_db_value ($dbh, 'SELECT id_agente FROM tagente_estado WHERE id_agente NOT IN (SELECT id_agente FROM tagente)'))) {
		db_delete_limit ($dbh, 'tagente_estado', 'id_agente NOT IN (SELECT id_agente FROM tagente)', $BIG_OPERATION_STEP);
	}

    # Delete orphan data_inc reference records
    db_do ($dbh, 'DELETE FROM tagente_datos_inc WHERE id_agente_modulo NOT IN (SELECT id_agente_modulo FROM tagente_modulo)');

    # Check enterprise tables
    enterprise_hook ('pandora_checkdb_integrity_enterprise', [$conf, $dbh]);
}

###############################################################################
# Check database consistency.
###############################################################################
sub pandora_checkdb_consistency {
	my ($conf, $dbh) = @_;
	
	#-------------------------------------------------------------------
	# 1. Check for modules that do not have tagente_estado but have
	#    tagente_module
	#-------------------------------------------------------------------
	if (defined($conf->{'_delete_notinit'}) && $conf->{'_delete_notinit'} ne "" && $conf->{'_delete_notinit'} eq "1") {
		log_message ('CHECKDB', "Deleting not-init data.");
		my @modules = get_db_rows ($dbh,
			'SELECT id_agente_modulo, id_agente
			FROM tagente_estado
			WHERE estado = 4');
		
		foreach my $module (@modules) {
			my $id_agente_modulo = $module->{'id_agente_modulo'};
			my $id_agente = $module->{'id_agente'};
			
			# Skip policy modules
			my $is_policy_module = enterprise_hook('is_policy_module',
				[$dbh, $id_agente_modulo]);
			next if (defined($is_policy_module) && $is_policy_module);
			
			# Skip if agent is disabled
			my $is_agent_disabled = get_db_value ($dbh,
				'SELECT disabled
				FROM tagente
				WHERE id_agente = ?', $module->{'id_agente'});
			next if (defined($is_agent_disabled) && $is_agent_disabled);
			
			# Skip if module is disabled
			my $is_module_disabled = get_db_value ($dbh,
				'SELECT disabled
				FROM tagente_modulo
				WHERE id_agente_modulo = ?', $module->{'id_agente_modulo'});
			next if (defined($is_module_disabled) && $is_module_disabled);
			
			
			#---------------------------------------------------------------
			# Delete the module
			#---------------------------------------------------------------
			# Mark the agent for module and alert counters update
			db_do ($dbh,
				'UPDATE tagente
				SET update_module_count = 1, update_alert_count = 1
				WHERE id_agente = ?', $id_agente);
			
			# Delete the module
			db_do ($dbh,
				'DELETE FROM tagente_modulo
				WHERE id_agente_modulo = ?', $id_agente_modulo);
			
			# Do a nanosleep here for 0,001 sec
			usleep (100000);
			
			# Delete any alerts associated to the module
			db_do ($dbh,
				'DELETE FROM talert_template_modules
				WHERE id_agent_module = ?', $id_agente_modulo);
		}
	} else {
		log_message ('CHECKDB', "Ignoring not-init data.");
	}
	
	if (defined($conf{'_days_delete_unknown'}) && $conf{'_days_delete_unknown'} > 0) {
	    log_message ('CHECKDB',
		    "Deleting unknown data (More than " . $conf{'_days_delete_unknown'} . " days).");
	
		my @modules = get_db_rows($dbh,
			'SELECT tagente_modulo.id_agente_modulo, tagente_modulo.id_agente
			FROM tagente_modulo, tagente_estado
			WHERE tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo
				AND estado = 3
				AND utimestamp < UNIX_TIMESTAMP() - ?',
			86400 * $conf{'_days_delete_unknown'});
		
		foreach my $module (@modules) {
			my $id_agente = $module->{'id_agente'};
			my $id_agente_modulo = $module->{'id_agente_modulo'};
			
			# Skip policy modules
			my $is_policy_module = enterprise_hook('is_policy_module',
				[$dbh, $id_agente_modulo]);
			next if (defined($is_policy_module) && $is_policy_module);
			
			# Mark the agent for module and alert counters update
			db_do ($dbh,
				'UPDATE tagente
				SET update_module_count = 1, update_alert_count = 1
				WHERE id_agente = ?', $id_agente);

			# Delete the module
			db_do ($dbh,
				'DELETE FROM tagente_modulo
				WHERE disabled = 0
					AND id_agente_modulo = ?', $id_agente_modulo);
			
			# Do a nanosleep here for 0,001 sec
			usleep (100000);
			
			# Delete any alerts associated to the module
			db_do ($dbh, 'DELETE FROM talert_template_modules
				WHERE id_agent_module = ?
					AND NOT EXISTS (SELECT id_agente_modulo
						FROM tagente_modulo
						WHERE id_agente_modulo = ?)',
				$id_agente_modulo, $id_agente_modulo);
			
			# Do a nanosleep here for 0,001 sec
			usleep (100000);
		}
	}

	# Perform a clean of not initialized modules.
	if (defined($conf{'_days_delete_not_initialized'}) && $conf{'_days_delete_not_initialized'} > 0 && $conf->{'_delete_notinit'} eq "1") {
	    log_message ('CHECKDB',
		    "Deleting not initialized modules (More than " . $conf{'_days_delete_not_initialized'} . " days).");

		my @modules = get_db_rows($dbh,
			'SELECT tagente_modulo.id_agente_modulo, tagente_modulo.id_agente
			FROM tagente_modulo, tagente_estado
			WHERE tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo
				AND (estado = 4 OR estado = 5)
				AND utimestamp < UNIX_TIMESTAMP() - ?',
			86400 * $conf{'_days_delete_not_initialized'});

		foreach my $module (@modules) {
			my $id_agente = $module->{'id_agente'};
			my $id_agente_modulo = $module->{'id_agente_modulo'};

			# Skip policy modules
			my $is_policy_module = enterprise_hook('is_policy_module',
				[$dbh, $id_agente_modulo]);
			next if (defined($is_policy_module) && $is_policy_module);

			# Mark the agent for module and alert counters update
			db_do ($dbh,
				'UPDATE tagente
				SET update_module_count = 1, update_alert_count = 1
				WHERE id_agente = ?', $id_agente);

			# Delete the module
			db_do ($dbh,
				'DELETE FROM tagente_modulo
				WHERE disabled = 0
					AND id_agente_modulo = ?', $id_agente_modulo);

			# Do a nanosleep here for 0,001 sec
			usleep (100000);

			# Delete any alerts associated to the module
			db_do ($dbh, 'DELETE FROM talert_template_modules
				WHERE id_agent_module = ?
					AND NOT EXISTS (SELECT id_agente_modulo
						FROM tagente_modulo
						WHERE id_agente_modulo = ?)',
				$id_agente_modulo, $id_agente_modulo);

			# Do a nanosleep here for 0,001 sec
			usleep (100000);
		}
	}


	log_message ('CHECKDB',
		"Checking database consistency (Missing status).");
	
	my @modules = get_db_rows ($dbh, 'SELECT m.id_agente, m.id_agente_modulo, e.id_agente_estado FROM tagente_modulo AS m LEFT JOIN tagente_estado AS e ON m.id_agente_modulo = e.id_agente_modulo WHERE e.id_agente_estado IS NULL');
	foreach my $module (@modules) {
		my $id_agente_modulo = $module->{'id_agente_modulo'};
		my $id_agente = $module->{'id_agente'};
		
		db_do ($dbh,
			'INSERT INTO tagente_estado (id_agente_modulo, datos, timestamp, estado, id_agente, last_try, utimestamp, current_interval, running_by, last_execution_try) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', $id_agente_modulo, 0, '1970-01-01 00:00:00', 1, $id_agente, '1970-01-01 00:00:00', 0, 0, 0, 0);
		log_message ('CHECKDB',
			"Inserting module $id_agente_modulo in state table.");
	}
	
	log_message ('CHECKDB',
		"Checking database consistency (Missing module).");
	
	#-------------------------------------------------------------------
	# 2. Check for modules in tagente_estado that do not have
	#    tagente_modulo, if there is any, delete it
	#-------------------------------------------------------------------
	
	@modules = get_db_rows ($dbh, 'SELECT e.id_agente_modulo, m.id_agente FROM tagente_estado AS e LEFT JOIN tagente_modulo AS m ON e.id_agente_modulo = m.id_agente_modulo WHERE m.id_agente IS NULL');
	foreach my $module (@modules) {
		my $id_agente_modulo = $module->{'id_agente_modulo'};
		
		db_do ($dbh, 'DELETE FROM tagente_estado
			WHERE id_agente_modulo = ?', $id_agente_modulo);
		
		# Do a nanosleep here for 0,001 sec
		usleep (100000);
		
		log_message ('CHECKDB',
			"Deleting non-existing module $id_agente_modulo in state table.");
	}

	#-------------------------------------------------------------------
	# 3. Update empty aliases.
	#-------------------------------------------------------------------
	log_message ('CHECKDB', "Updating empty aliases.");
	db_do ($dbh, "UPDATE tagente SET alias=nombre WHERE alias=''");
}

##############################################################################
# Print a help screen and exit.
##############################################################################
sub help_screen{
	log_message ('', "Usage: $0 <path to configuration file> [options]\n\n");
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

	$first_mark =  get_db_value_limit ($dbh, "SELECT utimestamp FROM $table ORDER BY utimestamp ASC", 1);
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
					db_delete_limit ($dbh, $table,  'utimestamp < ? AND utimestamp >= ?', $SMALL_OPERATION_STEP, $mark1, $mark2);
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

##############################################################################
# Delete old export data.
##############################################################################
sub pandora_delete_old_export_data {
	my ($dbh, $ulimit_timestamp) = @_;

	log_message ('PURGE', "Deleting old export data from tserver_export_data");
	while((my $rc = db_delete_limit ($dbh, 'tserver_export_data', 'UNIX_TIMESTAMP(timestamp) < ?', $SMALL_OPERATION_STEP, $ulimit_timestamp)) ne '0E0') {
		print "RC:$rc\n";
		usleep (10000);
	};
}

##############################################################################
# Delete old data from tplanned_downtime.
##############################################################################
sub pandora_delete_old_tplanned_downtime {
	my ($conf, $dbh, $h_conf) = @_;

	# Use the configuration from the history DB if available, which should be
	# less restrictive.
	my $days_purge = $conf->{'_days_purge'};
	if (defined($h_conf) &&
	    defined($h_conf->{'_days_purge'}) &&
		$h_conf->{'_days_purge'} > 0) {
		$days_purge = $h_conf->{'_days_purge'};
	}

	# _days_purge was not configured.
	return unless $days_purge > 0;

	my $ulimit_timestamp = time() - (86400 * $days_purge);

	log_message ('PURGE', "Deleting data older than $days_purge days from tplanned_downtime.");

	db_do($dbh, "DELETE FROM tplanned_downtime
	             WHERE type_execution = 'once'
	             AND date_to < ?", $ulimit_timestamp);
}

##############################################################################
# Delete old session data.
##############################################################################
sub pandora_delete_old_session_data {
	my ($conf, $dbh, $ulimit_timestamp) = @_;

	my $session_timeout = $conf->{'_session_timeout'};

	# DO not erase anything if session_timeout is not set.
	return unless (defined($session_timeout) && $session_timeout ne '');

	if ($session_timeout == 0) {
		# As defined in console.
		$session_timeout = 90;
	}

	if ($session_timeout == -1) {
		# The session expires in 10 years
		$session_timeout = 315576000;
	} else {
		$session_timeout *= 60;
	}

	$ulimit_timestamp = time() - $session_timeout;

	log_message ('PURGE', "Deleting old session data from tsessions_php");
	while(db_delete_limit ($dbh, 'tsessions_php', 'last_active < ?', $SMALL_OPERATION_STEP, $ulimit_timestamp) ne '0E0') {
		usleep (10000);
	};

	db_do ($dbh, "DELETE FROM tsessions_php WHERE data IS NULL OR id_session REGEXP '^cron-'");

	# Delete cron cookies file
	log_message ('PURGE', "Deleting cron session file");
	unlink($COOKIES_FILE);
 
}

###############################################################################
# Delete old data from the history database.
###############################################################################
sub pandoradb_history ($$) {
	my ($conf, $dbh) = @_;
	my $timestamp = strftime ("%Y-%m-%d %H:%M:%S", localtime());
	my $ulimit_access_timestamp = time() - 86400;
	my $ulimit_timestamp = time() - (86400 * $conf->{'_days_purge'});

	log_message ('', "Starting at ". strftime ("%Y-%m-%d %H:%M:%S", localtime()) . "\n");

	# Delete old numeric data.
	if ($conf->{'_days_purge'} > 0) {
		pandora_delete_old_module_data ($dbh, 'tagente_datos', $ulimit_access_timestamp, $ulimit_timestamp);
	} else {
		log_message ('PURGE', 'days_purge is set to 0. Old data will not be deleted.');
	}

	# Delete old string data.
	$conf->{'_string_purge'} = 7 unless defined($conf->{'_string_purge'});
	if ($conf->{'_string_purge'} > 0) {
		$ulimit_access_timestamp = time() - 86400;
		$ulimit_timestamp = time() - (86400 * $conf->{'_string_purge'});
		pandora_delete_old_module_data ($dbh, 'tagente_datos_string', $ulimit_access_timestamp, $ulimit_timestamp);
	} else {
		log_message ('PURGE', 'string_purge is set to 0. Old string data will not be deleted.');
	}

	# Delete old events.
	if ($conf->{'_event_purge'} > 0) {
		log_message ('PURGE', "Deleting events older than " . $conf->{'_event_purge'} . " days from tevento.", '');

		my $event_limit = time() - 86400 * $conf->{'_event_purge'};
		my $events_to_delete = get_db_value ($dbh, "SELECT COUNT(*) FROM tevento WHERE utimestamp < ?", $event_limit);
		while($events_to_delete > 0) {
			db_delete_limit($dbh, 'tevento', "utimestamp < ?", $BIG_OPERATION_STEP, $event_limit);
			$events_to_delete = $events_to_delete - $BIG_OPERATION_STEP;
				
			# Mark the progress.
			log_message ('', ".");
				
			# Do not overload the MySQL server.
			usleep (10000);
		}
		log_message ('', "\n");
	}

	# Delete old traps.
	if ($conf->{'_trap_history_purge'} > 0) {
		log_message ('PURGE', "Deleting traps older than " . $conf->{'_trap_history_purge'} . " days from ttrap (history).", '');

		my $trap_limit = strftime ("%Y-%m-%d %H:%M:%S", localtime(time() - 86400 * $conf->{'_trap_history_purge'}));

		my $traps_to_delete = get_db_value ($dbh, "SELECT COUNT(*) FROM ttrap WHERE timestamp < ?", $trap_limit);
		while($traps_to_delete > 0) {
			db_delete_limit($dbh, 'ttrap', "timestamp < ?", $BIG_OPERATION_STEP, $trap_limit);
			$traps_to_delete = $traps_to_delete - $BIG_OPERATION_STEP;

			# Mark the progress.
			log_message ('', ".");
				
			# Do not overload the MySQL server.
			usleep (10000);
		}
		log_message ('', "\n");
	}

	# Update tconfig with last time of database maintance time (now)
	db_do ($dbh, "DELETE FROM tconfig WHERE token = 'db_maintance'");
	db_do ($dbh, "INSERT INTO tconfig (token, value) VALUES ('db_maintance', '".time()."')");

	log_message ('', "Ending at ". strftime ("%Y-%m-%d %H:%M:%S", localtime()) . "\n");
}

###############################################################################
# Main
###############################################################################
sub pandoradb_main {
	my ($conf, $dbh, $h_conf, $history_dbh) = @_;

	log_message ('', "Starting at ". strftime ("%Y-%m-%d %H:%M:%S", localtime()) . "\n");

	# Purge
	pandora_purgedb ($conf, $dbh, $h_conf);

	# Consistency check
	pandora_checkdb_consistency ($conf, $dbh);

	# Maintain Referential integrity and other stuff
	pandora_checkdb_integrity ($conf, $dbh);

	# Move old data to the history DB
	if (defined ($history_dbh)) {
		undef ($history_dbh) unless defined (enterprise_hook ('pandora_historydb', [$dbh, $history_dbh, $conf->{'_history_db_days'}, $conf->{'_history_db_step'}, $conf->{'_history_db_delay'}, $conf->{'_history_db_string_days'}, $conf->{'_history_db_adv'}]));
		if (defined($conf{'_history_event_enabled'}) && $conf->{'_history_event_enabled'} ne "" && $conf->{'_history_event_enabled'} == 1) {
			undef ($history_dbh) unless defined (enterprise_hook ('pandora_history_event', [$dbh, $history_dbh, $conf->{'_history_event_days'}, $conf->{'_history_db_step'}, $conf->{'_history_db_delay'}]));
		}
		if (defined($conf{'_history_trap_enabled'}) && $conf->{'_history_trap_enabled'} ne "" && $conf->{'_history_trap_enabled'} == 1) {
			undef ($history_dbh) unless defined (enterprise_hook ('pandora_history_trap', [$dbh, $history_dbh, $conf->{'_history_trap_days'}, $conf->{'_history_db_step'}, $conf->{'_history_db_delay'}]));
		}
	}

	# Only active database should be compacted. Disabled for historical database.
	# Compact on if enable and DaysCompact are below DaysPurge 
	if (($conf->{'_onlypurge'} == 0)
	) {
		pandora_compactdb ($conf, $dbh, $dbh);
	}

	# Update tconfig with last time of database maintance time (now)
	db_do ($dbh, "DELETE FROM tconfig WHERE token = 'db_maintance'");
	db_do ($dbh, "INSERT INTO tconfig (token, value) VALUES ('db_maintance', '".time()."')");

	# Move SNMP modules back to the Enterprise server
	enterprise_hook("claim_back_snmp_modules", [$dbh, $conf]);

	# Check if there are discovery tasks with wrong id_recon_server
	pandora_check_forgotten_discovery_tasks ($conf, $dbh);

	# Recalculating dynamic intervals.
	enterprise_hook("update_min_max", [$dbh, $conf]);

	# Metaconsole database cleanup.
	enterprise_hook("metaconsole_database_cleanup", [$dbh, $conf]);

	# NCM cleanup.
	enterprise_hook("ncm_database_cleanup", [$dbh, $conf]);

	log_message ('', "Ending at ". strftime ("%Y-%m-%d %H:%M:%S", localtime()) . "\n");
}

###############################################################################
# Check for discovery tasks configured with servers down
###############################################################################

sub pandora_check_forgotten_discovery_tasks {
	my ($conf, $dbh) = @_;

    log_message ('FORGOTTEN DISCOVERY TASKS', "Check for discovery tasks bound to inactive servers.");

		my @discovery_tasks = get_db_rows ($dbh, 'SELECT id_rt, id_recon_server, name FROM trecon_task');
		my $discovery_tasks_count = @discovery_tasks;

		# End of the check (this server has not discovery tasks!).
		if ($discovery_tasks_count eq 0) {
			log_message('FORGOTTEN DISCOVERY TASKS', 'There are not defined discovery tasks. Skipping.');
			return;
		}

		my $master_server = get_db_value ($dbh, 'SELECT id_server FROM tserver WHERE server_type = ? AND status != -1', DISCOVERYSERVER);

		# Goes through all the tasks to check if any have the server down.
		foreach my $task (@discovery_tasks) {
			if ($task->{'id_recon_server'} ne $master_server) {
				my $this_server_status = get_db_value ($dbh, 'SELECT status FROM tserver WHERE id_server = ?', $task->{'id_recon_server'});
				if (!defined($this_server_status) || $this_server_status eq -1) {
					my $updated_task = db_process_update ($dbh, 'trecon_task', { 'id_recon_server' => $master_server }, { 'id_rt' => $task->{'id_rt'} });
					log_message('FORGOTTEN DISCOVERY TASKS', 'Updated discovery task '.$task->{'name'});
				}
			}
		}

		log_message('FORGOTTEN DISCOVERY TASKS', 'Step ended');
}

###############################################################################
# Opening and closing of error log blocks 
###############################################################################
sub handle_error_log_block {
    my ($conf, $dbh) = @_;
	my $is_open = get_db_value ($dbh,"SELECT `value` FROM `tconfig` WHERE `token` = 'open_error_log'");
	
	if (!defined ($is_open)) {
		db_do($dbh, "INSERT INTO `tconfig`(`token`, `value`) VALUES ('open_error_log', 1)");
	} elsif ($is_open eq 1){
		log_error_writter($conf, strftime ("%Y-%m-%d %H:%M:%S", localtime()) . ' - ' . $conf->{'servername'} . " pandora_db: pandora_db maintenance tasks ends\n");
	}

	log_error_writter($conf, strftime ("%Y-%m-%d %H:%M:%S", localtime()) . ' - ' . $conf->{'servername'} . " pandora_db: pandora_db maintenance tasks starts\n");
}

###############################################################################
# Print messages on error log file
###############################################################################
sub log_error_writter($$){
	my ($conf, $message) = @_;
	open (STDERR, ">> " . $conf->{'errorlog_file'}) or die " [ERROR] " . pandora_get_initial_product_name() . " can't write to Errorlog. Aborting : \n $! \n";
	print STDERR $message;
	close (STDERR);
}

# Init
pandora_init_pdb(\%conf);

# Read config file
pandora_load_config_pdb (\%conf);

# Load enterprise module
if (enterprise_load (\%conf) == 0) {
	log_message ('', " [*] " . pandora_get_initial_product_name() . " Enterprise module not available.\n\n");
}
else {
	log_message ('', " [*] " . pandora_get_initial_product_name() . " Enterprise module loaded.\n\n");
}

# Connect to the DB
my $dbh = db_connect ($conf{'dbengine'}, $conf{'dbname'}, $conf{'dbhost'}, $conf{'dbport'}, $conf{'dbuser'}, $conf{'dbpass'});
my $history_dbh = undef;
is_metaconsole(\%conf);
if (defined($conf{'_history_db_enabled'}) && $conf{'_history_db_enabled'} eq '1') {
	eval {
		$conf{'encryption_key'} = enterprise_hook('pandora_get_encryption_key', [\%conf, $conf{'encryption_passphrase'}]);
		$history_dbh = db_connect ($conf{'dbengine'}, $conf{'_history_db_name'}, $conf{'_history_db_host'}, $conf{'_history_db_port'}, $conf{'_history_db_user'}, pandora_output_password(\%conf, $conf{'_history_db_pass'}));
	 	$h_conf = pandoradb_load_history_conf($history_dbh);
	};
	if ($@) {
		if (is_offline(\%conf)) {
			log_message ('!', "Cannot connect to the history database. Skipping.");
		} else {
			die ("$@\n");
		}
	}
}

# Close and open error log blocks
handle_error_log_block(\%conf, $dbh);

# Only run on master servers.
pandora_set_master(\%conf, $dbh);
if ($conf{'_force'} == 0 && pandora_is_master(\%conf) == 0) { 
	log_error_message (\%conf, " [*] Not a master server.");
	exit 1;
}

# Get a lock on the main database.
my $db_lock = db_get_lock ($dbh, $conf{'dbname'} . '_pandora_db', $LOCK_TIMEOUT, 1);
if ($db_lock == 0) { 
	log_error_message (\%conf, " [*] Another instance of DB Tool seems to be running on the main database.");
	exit 1;
}

# Get a lock on the history database.
if (defined($history_dbh)) {
	my $history_lock = db_get_lock ($history_dbh, $conf{'_history_db_name'} . '_pandora_db', $LOCK_TIMEOUT, 1);
	if ($history_lock == 0) { 
		log_error_message (\%conf, " [*] Another instance of DB Tool seems to be running on the history database.");
		exit 1;
	}
}

# Get a lock merging.
my $lock_merge = db_get_lock ($dbh, 'merge-working', $LOCK_TIMEOUT, 1);
if ($lock_merge == 0) { 
	log_error_message (\%conf, " [*] Merge is running.");
	exit 1;
}

# Get a lock on merging events.
my $lock_merge_events = db_get_lock ($dbh, 'merging-events', $LOCK_TIMEOUT, 1);
if ($lock_merge_events == 0) { 
	log_error_message (\%conf, " [*] Merge events is running.");
	exit 1;
}

# Main
pandoradb_main(\%conf, $dbh, $h_conf, $history_dbh);

# history_dbh is unset in pandoradb_main if not in use.
if (defined($history_dbh)) {
	log_message('', " [>] DB Tool running on historical database.\n");

	# Keep base settings.
	$h_conf->{'_onlypurge'} = $conf{'_onlypurge'};

	# Launch maintenance process for historical database.
	pandoradb_history($h_conf, $history_dbh);

	# Handle partitions.
	enterprise_hook('handle_partitions', [$h_conf, $history_dbh]);
	
}

# Cleanup and exit
db_disconnect ($history_dbh) if defined ($history_dbh);
db_disconnect ($dbh);

exit 0;
