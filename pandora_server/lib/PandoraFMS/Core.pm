package PandoraFMS::Core;
##########################################################################
# Core Pandora FMS functions.
# Pandora FMS. the Flexible Monitoring System. http://www.pandorafms.org
##########################################################################
# Copyright (c) 2009 Ramon Novoa, rnovoa@artica.es
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU Lesser General Public License
# as published by the Free Software Foundation; version 2
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
##########################################################################

use strict;
use warnings;

use DBI;
use XML::Simple;
use HTML::Entities;
use Time::Local;
use POSIX qw(strftime);

use PandoraFMS::DB;
use PandoraFMS::Config;
use PandoraFMS::Tools;

require Exporter;

our @ISA = ("Exporter");
our %EXPORT_TAGS = ( 'all' => [ qw( ) ] );
our @EXPORT_OK = ( @{ $EXPORT_TAGS{'all'} } );
our @EXPORT = qw( 	
	pandora_audit
	pandora_create_agent
	pandora_create_incident
	pandora_create_module
	pandora_evaluate_alert
	pandora_evaluate_compound_alert
	pandora_evaluate_snmp_alerts
	pandora_event
	pandora_event_status
	pandora_execute_alert
	pandora_execute_action
	pandora_exec_forced_alerts
	pandora_generate_alerts
	pandora_generate_compound_alerts
	pandora_module_keep_alive
	pandora_module_keep_alive_nd
	pandora_ping
	pandora_ping_latency
	pandora_planned_downtime
	pandora_process_alert
	pandora_process_module
	pandora_reset_server
	pandora_server_keep_alive
	pandora_update_agent
	pandora_update_module_on_error
	pandora_update_server

	@ServerTypes
	);

# Some global variables
our @DayNames = qw(monday tuesday wednesday thursday friday saturday sunday);
our @ServerTypes = qw (dataserver networkserver snmpconsole reconserver pluginserver predictionserver wmiserver exportserver inventoryserver webserver);

##########################################################################
# Generate alerts for a given module.
##########################################################################
sub pandora_generate_alerts ($$$$$$$) {
	my ($pa_config, $data, $status, $agent, $module, $utimestamp, $dbh) = @_;

	# Do not generate alerts for disabled groups
	if (is_group_disabled ($dbh, $agent->{'id_grupo'})) {
		return;
	}

	# Get enabled alerts associated with this module
	my @alerts = get_db_rows ($dbh, 'SELECT talert_template_modules.id as id_template_module,
	                               talert_template_modules.*, talert_templates.*
	                               FROM talert_template_modules, talert_templates
	                               WHERE talert_template_modules.id_alert_template = talert_templates.id
	                               AND id_agent_module = ? AND disabled = 0', $module->{'id_agente_modulo'});

	foreach my $alert (@alerts) {
		my $rc = pandora_evaluate_alert($pa_config, $data, $status, $alert,
		                                $utimestamp, $dbh);

		pandora_process_alert ($pa_config, $data, $agent, $module,
		                       $alert, $rc, $dbh);
		                       
		# Evaluate compound alerts even if the alert status did not change in
		# case the compound alert does not recover
		pandora_generate_compound_alerts ($pa_config, $data, $agent, $module,
		                                  $alert, $utimestamp, $dbh)
	}
}

##########################################################################
# Evaluate trigger conditions for a given alert. Returns:
# 0 Execute the alert.
# 1 Do not execute the alert.
# 2 Do not execute the alert, but increment its internal counter.
# 3 Cease the alert.
# 4 Recover the alert.
# 5 Reset internal counter (alert not fired, interval elapsed).
##########################################################################
sub pandora_evaluate_alert ($$$$$$) {
    my ($pa_config, $data, $last_status, $alert, $utimestamp, $dbh) = @_;

	# Value returned on valid data
	my $status = 1;

	# Get current time
	my ($sec, $min, $hour, $mday, $mon, $year, $wday, $yday, $isdst) = localtime(time());

	# Check weekday
	return 1 if ($alert->{$DayNames[$wday]} != 1);

	# Check time slot
	my $time = sprintf ("%.2d:%.2d:%.2d", $hour, $min, $sec);
	return 1 if (($alert->{'time_to'} ne $alert->{'time_from'}) &&
	             (($time ge $alert->{'time_to'}) || ($time le $alert->{'time_from'})));

	# Check time threshold
	my $limit_utimestamp = $alert->{'last_reference'} + $alert->{'time_threshold'};

	if ($alert->{'times_fired'} > 0) {

		# Reset fired alerts
		if ($utimestamp > $limit_utimestamp) {

			# Cease on valid data
			$status = 3;

			# Always reset
			($alert->{'internal_counter'}, $alert->{'times_fired'}) = (0, 0);
		}

		# Recover takes precedence over cease
		$status = 4 if ($alert->{'recovery_notify'} == 1);

	} elsif ($utimestamp > $limit_utimestamp) {
		$status = 5;
	}

	# Check for valid data
	# Simple alert
	if (defined ($alert->{'id_template_module'})) {
		return $status if ($alert->{'type'} eq "min" && $data >= $alert->{'min_value'});
		return $status if ($alert->{'type'} eq "max" && $data <= $alert->{'max_value'});

		if ($alert->{'type'} eq "max_min") {
			return $status if ($alert->{'matches_value'} == 1 &&
				               $data <= $alert->{'min_value'} &&
				               $data >= $alert->{'max_value'});

			return $status if ($data >= $alert->{'min_value'} &&
				               $data <= $alert->{'max_value'});
		}
		
		return $status if ($alert->{'type'} eq "equal" && $data != $alert->{'value'});
		return $status if ($alert->{'type'} eq "not_equal" && $data == $alert->{'value'});
		if ($alert->{'type'} eq "regex") {
			return $status if ($alert->{'matches_value'} == 1 && $data =~ m/$alert->{'value'}/i);

			return $status if ($data !~ m/$alert->{'value'}/i);
		}

		return $status if ($last_status == 1 && $alert->{'type'} eq 'critical');
		return $status if ($last_status == 2 && $alert->{'type'} eq 'warning');
	}
	# Compound alert
	elsif (pandora_evaluate_compound_alert($pa_config, $alert->{'id'}, $dbh) == 0) {
		return $status
	}

	# Check min and max alert limits
	return 2 if (($alert->{'internal_counter'} < $alert->{'min_alerts'}) ||
		         ($alert->{'times_fired'}  >= $alert->{'max_alerts'}));

	return 0;
}

##########################################################################
# Process an alert given the status returned by pandora_evaluate_alert.
##########################################################################
sub pandora_process_alert ($$$$$$$) {
	my ($pa_config, $data, $agent, $module, $alert, $rc, $dbh) = @_;

	# Do not execute
	return if ($rc == 1);

	# Cease
	if ($rc == 3) {

		# Update alert status
		db_do($dbh, 'UPDATE talert_template_modules SET times_fired = 0,
				 internal_counter = 0 WHERE id = ?', $alert->{'id_template_module'});

		# Generate an event
		pandora_event ($pa_config, "Alert ceased (" .
					   $alert->{'descripcion'} . ")", $agent->{'id_grupo'},
					   $agent->{'id_agente'}, $alert->{'priority'}, $alert->{'id_template_module'}, $alert->{'id_agent_module'}, 
					   "alert_recovered", $dbh);

		return;
	}

	# Recover
	if ($rc == 4) {

		# Update alert status
		db_do($dbh, 'UPDATE talert_template_modules SET times_fired = 0,
				 internal_counter = 0 WHERE id = ?', $alert->{'id_template_module'});

		pandora_execute_alert ($pa_config, $data, $agent, $module, $alert, 0, $dbh);
		return;
	}

	# Reset internal counter
	if ($rc == 5) {
		db_do($dbh, 'UPDATE talert_template_modules SET internal_counter = 0 WHERE id = ?', 
		      $alert->{'id_template_module'});
		return;
	}

	# Get current date
	my $utimestamp = time ();

	# Do we have to start a new interval?
	my $new_interval = ($alert->{'internal_counter'} == 0) ?
	                    ', last_reference = ' . $utimestamp : '';

	# Increment internal counter
	if ($rc == 2) {

		# Update alert status
		$alert->{'internal_counter'} += 1;

		# Do not increment times_fired, but set it in case the alert was reset
		db_do($dbh, 'UPDATE talert_template_modules SET times_fired = ?,
				     internal_counter = ? ' . $new_interval . ' WHERE id = ?',
		      $alert->{'times_fired'}, $alert->{'internal_counter'}, $alert->{'id_template_module'});

		return;
	}

	# Execute
	if ($rc == 0) {

		# Update alert status
		$alert->{'times_fired'} += 1;
		$alert->{'internal_counter'} += 1;

		db_do($dbh, 'UPDATE  talert_template_modules  SET times_fired = ?,
				     last_fired = ?, internal_counter = ? ' . $new_interval . ' WHERE id = ?',
		      $alert->{'times_fired'}, $utimestamp, $alert->{'internal_counter'}, $alert->{'id_template_module'});

		pandora_execute_alert ($pa_config, $data, $agent, $module, $alert, 1, $dbh);
		return;
	}
}

##########################################################################
# Evaluate the given compound alert. Returns 1 if the alert should be
# fired, 0 if not.
##########################################################################
sub pandora_evaluate_compound_alert ($$$) {
	my ($pa_config, $id, $dbh) = @_;

	# Return value
	my $status = 0;

	# Get all the alerts associated with this compound alert
	my @compound_alerts = get_db_rows ($dbh, 'SELECT id_alert_template_module, operation FROM talert_compound_elements
						  WHERE id_alert_compound = ? ORDER BY order', $id);

	foreach my $compound_alert (@compound_alerts) {

		# Get alert data if enabled
		my $times_fired = get_db_value ($dbh, "SELECT times_fired FROM talert_template_modules WHERE id = ?
		                                       AND disabled = 0", $compound_alert->{'id_alert_template_module'});
		next unless defined ($times_fired);

		# Check whether the alert was fired
		my $fired = ($times_fired > 0) ? 1 : 0;
		my $operation = $compound_alert->{'operation'};

		# Operate...
		if ($operation eq "AND") {
			$status &= $fired;
		}
		elsif ($operation eq "OR") {
			$status |= $fired;
		}
		elsif ($operation eq "XOR") {
			$status ^= $fired;
		}
		elsif ($operation eq "NAND") {
			$status &= ! $fired;
		}
		elsif ($operation eq "NOR") {
			$status |= ! $fired;
		}
		elsif ($operation eq "NXOR") {
			$status ^= ! $fired;
		}
		elsif ($operation eq "NOP") {
			$status = $fired;
		}
	}

	return $status;
}

##########################################################################
# Generate compound alerts that depend on a given alert.
##########################################################################
sub pandora_generate_compound_alerts ($$$$$$$) {
	my ($pa_config, $data, $agent, $module, $alert, $utimestamp, $dbh) = @_;

	# Get all compound alerts that depend on this alert
	my @elements = get_db_rows ($dbh, 'SELECT id_alert_compound FROM talert_compound_elements
	                             WHERE id_alert_template_module = ?',
						         $alert->{'id_template_module'});

	foreach my $element (@elements) {

		# Get compound alert parameters
		my $compound_alert = get_db_single_row ($dbh, 'SELECT * FROM talert_compound WHERE id = ?', $element->{'id_alert_compound'});
		next unless defined ($compound_alert);

		# Evaluate the alert
		my $rc = pandora_evaluate_alert ($pa_config, $data, '', $alert,
		                                 $utimestamp, $dbh);

		pandora_process_alert ($pa_config, $data, $agent, $module,
		                       $compound_alert, $rc, $dbh);
	}
}

##########################################################################
# Execute the given alert.
##########################################################################
sub pandora_execute_alert ($$$$$$$) {
	my ($pa_config, $data, $agent, $module,
	    $alert, $alert_mode, $dbh) = @_;
	
	# Get active actions/commands
	my @actions;

	# Simple alert
	if (defined ($alert->{'id_template_module'})) {
		@actions = get_db_rows ($dbh, 'SELECT * FROM talert_template_module_actions, talert_actions, talert_commands
	    	                     WHERE talert_template_module_actions.id_alert_action = talert_actions.id
	        	                 AND talert_actions.id_alert_command = talert_commands.id
	            	             AND talert_template_module_actions.id_alert_template_module = ?
	                    	     AND ((fires_min = 0 AND fires_max = 0)
	                             OR (? >= fires_min AND ? <= fires_max))', 
	                	         $alert->{'id_template_module'}, $alert->{'times_fired'}, $alert->{'times_fired'});	

		# Get default action
		if ($#actions < 0) {
			@actions = get_db_rows ($dbh, 'SELECT * FROM talert_actions, talert_commands
	                                    WHERE talert_actions.id = ?
	                                    AND talert_actions.id_alert_command = talert_commands.id',
	                                    $alert->{'id_alert_action'}, );
		}
	}
	# Compound alert
	else {
		@actions = get_db_rows ($dbh, 'SELECT * FROM talert_compound_actions, talert_actions, talert_commands
	    	                            WHERE talert_compound_actions.id_alert_action = talert_actions.id
	        	                        AND talert_actions.id_alert_command = talert_commands.id
	            	                    AND talert_compound_actions.id_alert_compound = ?
	            	                    AND ((fires_min = 0 AND fires_max = 0)
	                                      OR (? >= fires_min AND ? <= fires_max))', $alert->{'id'}, $alert->{'times_fired'}, $alert->{'times_fired'});
	}

	# No actions defined
	return if ($#actions < 0);

	# Execute actions
	foreach my $action (@actions) {
		logger($pa_config, "Alert (" . $alert->{'name'} . ") executed for agent " . $agent->{'nombre'}, 2);
		pandora_execute_action ($pa_config, $data, $agent, $alert, $alert_mode, $action, $dbh);
	}

	# Generate an event
	my ($text, $event) = ($alert_mode == 0) ? ('recovered', 'alert_recovered') : ('fired', 'alert_fired');

	pandora_event ($pa_config, "Alert $text (" . $alert->{'description'} . ")",
		           $agent->{'id_grupo'}, $agent->{'id_agente'}, $alert->{'priority'}, (defined ($alert->{'id_template_module'})) ? $alert->{'id_template_module'} : 0,
		           $alert->{'id_agent_module'}, $event,  $dbh);
}

##########################################################################
# Execute the given action.
##########################################################################
sub pandora_execute_action ($$$$$$$) {
	my ($pa_config, $data, $agent, $alert,
	    $alert_mode, $action, $dbh) = @_;

	my $field1 =  $action->{'field1'} ne "" ? $action->{'field1'} : $alert->{'field1'};
	my $field2 =  $action->{'field2'} ne "" ? $action->{'field2'} : $alert->{'field2'};
	my $field3 =  $action->{'field3'} ne "" ? $action->{'field3'} : $alert->{'field3'};		

	# Recovery fields, thanks to Kato Atsushi
	if ($alert_mode == 0){
		$field2 = $alert->{'field2_recovery'} ne "" ? $alert->{'field2_recovery'} : "[RECOVER]" . $field2;
		$field3 = $alert->{'field3_recovery'} ne "" ? $alert->{'field3_recovery'} : "[RECOVER]" . $field3;
	}

	# Alert macros
	my %macros = (_field1_ => $field1,
				  _field2_ => $field2,
				  _field3_ => $field3,
				  _agent_ => (defined ($agent)) ? $agent->{'nombre'} : '',
				  _address_ => (defined ($agent)) ? $agent->{'direccion'} : '',
				  _timestamp_ => localtime(),
				  _data_ => $data,
				  _alert_description_ => $alert->{'description'},
				  _alert_threshold_ => $alert->{'time_threshold'},
				  _alert_times_fired_ => $alert->{'times_fired'},
				 );


	# User defined alerts
	if ($action->{'internal'} == 0) {
		my $command = decode_entities(subst_alert_macros ($action->{'command'}, \%macros));

		eval {
			system ($command);
			if ($? != 0) {
				logger($pa_config, 'Executed command for alert ' . $alert->{'name'} . ' returned with errorlevel ' . ($? >> 8), 1);
			}
		};

		if ($@){
			logger($pa_config, "Error $@ executing command $command", 1);
		}

	# Internal Audit
	} elsif ($action->{'name'} eq "Internal Audit") {
		$field1 = subst_alert_macros ($field1, \%macros);
		pandora_audit ($pa_config, $field1, defined ($agent) ? $agent->{'nombre'} : 'N/A', 'Alert (' . $alert->{'description'} . ')', $dbh);

	# Email		
	} elsif ($action->{'name'} eq "eMail") {
		$field2 = subst_alert_macros ($field2, \%macros);
		$field3 = subst_alert_macros ($field3, \%macros);
		pandora_sendmail ($pa_config, $field1, $field2, $field3);

	# Internal event
	} elsif ($action->{'name'} eq "Pandora FMS Event") {

	# Unknown
	} else {
		logger($pa_config, "Unknown action " . $action->{'name'}, 1);
	}
}

##########################################################################
# Update agent access table.
##########################################################################
sub pandora_access_update ($$$) {
	my ($pa_config, $agent_id, $dbh) = @_;

	return if ($agent_id < 0);

	db_insert ($dbh, "INSERT INTO tagent_access (`id_agent`, `utimestamp`) VALUES (?, ?)", $agent_id, time ());
}

##########################################################################
# Process Pandora module.
##########################################################################
sub pandora_process_module ($$$$$$$$$) {
	my ($pa_config, $data, $agent, $module, $module_type,
	    $timestamp, $utimestamp, $server_id, $dbh) = @_;

	# Get module type
	if ($module_type eq '') {
		$module_type = get_db_value ($dbh, 'SELECT nombre FROM ttipo_modulo WHERE id_tipo = ?', $module->{'id_tipo_modulo'});
		return unless defined ($module_type);
	}

	# Process data
 	$data = process_data ($data, $module, $module_type, $utimestamp, $dbh);
 	if (! defined ($data)) {
		logger($pa_config, "Received invalid data from module '" . $module->{'nombre'} . "'", 3);
		return;
	}

	# Get agent information
	if ($agent eq '') {
		$agent = get_db_single_row ($dbh, 'SELECT * FROM tagente WHERE id_agente = ?', $module->{'id_agente'});
		return unless defined ($agent);
	}

	if ($timestamp eq '') {
		$timestamp = strftime ("%Y-%m-%d %H:%M:%S", localtime($utimestamp));
	}

	# Export data
	export_module_data ($data, $agent, $module, $module_type, $timestamp, $dbh);

	# Get previous status
	my $agent_status = get_db_single_row ($dbh, 'SELECT * FROM tagente_estado WHERE id_agente_modulo = ?', $module->{'id_agente_modulo'});
	return unless defined ($agent_status);

	# Get current status
	my $status = get_module_status ($data, $module, $module_type);

	# Generate alerts
	pandora_generate_alerts ($pa_config, $data, $status, $agent, $module, $utimestamp, $dbh);

	#Update module status
	my $current_utimestamp = time ();
	my ($status_changes, $last_status) = ($status != $agent_status->{'estado'}) ?
	                                     (0, $agent_status->{'estado'}) :
	                                     ($agent_status->{'status_changes'} + 1, $agent_status->{'last_status'});

	# Generate events
	if ($status_changes == $module->{'min_ff_event'} + 1) {
		generate_status_event ($pa_config, $data, $agent, $module, $status, $last_status, $dbh);
	}
	
	# tagente_estado.last_try defaults to NULL, should default to '0000-00-00 00:00:00'
	$agent_status->{'last_try'} = '0000-00-00 00:00:00' unless defined ($agent_status->{'last_try'});

	# Do we have to save module data?
	return unless ($agent_status->{'last_try'} =~ /(\d+)\-(\d+)\-(\d+) +(\d+):(\d+):(\d+)/);
	my $last_try  = ($1 == 0) ? 0 : timelocal($6, $5, $4, $3, $2 - 1, $1 - 1900);
	my $save = ($module->{'history_data'} == 1 && ($agent_status->{'datos'} ne $data || $last_try < (time() - 86400))) ? 1 : 0;
		
	db_do ($dbh, 'UPDATE tagente_estado SET datos = ?, estado = ?, last_status = ?, status_changes = ?, utimestamp = ?, timestamp = ?,
	              id_agente = ?, current_interval = ?, running_by = ?, last_execution_try = ?, last_try = ?
	              WHERE id_agente_modulo = ?', $data, $status, $last_status, $status_changes,
	              $current_utimestamp, $timestamp, $module->{'id_agente'}, $module->{'module_interval'}, $server_id,
	              $utimestamp,  ($save == 1) ? $timestamp : $agent_status->{'last_try'}, $module->{'id_agente_modulo'});

	# Save module data
	if ($save == 1) {
		save_module_data ($data, $module, $module_type, $utimestamp, $dbh);
	}
}

##########################################################################
# Update planned downtimes.
##########################################################################
sub pandora_planned_downtime ($$) {
	my ($pa_config, $dbh) = @_;
	
	my $utimestamp = time();

	# Start pending downtimes (disable agents)
	my @downtimes = get_db_rows($dbh, 'SELECT * FROM tplanned_downtime WHERE executed = 0 AND date_from <= ? AND date_to >= ?', $utimestamp, $utimestamp);

	foreach my $downtime (@downtimes) {

		db_do($dbh, 'UPDATE tplanned_downtime SET executed = 1 WHERE id = ?', 	$downtime->{'id'});
		pandora_event ($pa_config, "Server ".$pa_config->{'servername'}." started planned downtime: ".$downtime->{'description'}, 0, 0, 1, 0, 0, 'system', $dbh);
		
		my @downtime_agents = db_do($dbh, 'SELECT * FROM tplanned_downtime_agents WHERE id_downtime = ' . $downtime->{'id'});
		
		foreach my $downtime_agent (@downtime_agents) {
			db_do ($dbh, 'UPDATE tagente SET disabled = 1 WHERE id_agente = ?', $downtime_agent->{'id_agent'});
		}
	}

	# Stop executed downtimes (enable agents)
	@downtimes = get_db_rows($dbh, 'SELECT * FROM tplanned_downtime WHERE executed = 1 AND date_to <= ?', $utimestamp);
	foreach my $downtime (@downtimes) {

		db_do($dbh, 'UPDATE tplanned_downtime SET executed = 0 WHERE id = ?', $downtime->{'id'});
		pandora_event ($pa_config, 'Server ' . $pa_config->{'servername'} . ' stopped planned downtime: ' . $downtime->{'description'}, 0, 0, 1, 0, 0, 'system', $dbh);

		my @downtime_agents = get_db_rows($dbh, 'SELECT * FROM tplanned_downtime_agents WHERE id_downtime = ' . $downtime->{'id'});

		foreach my $downtime_agent (@downtime_agents) {
			db_do ($dbh, 'UPDATE tagente SET disabled = 0 WHERE id_agente = ?', $downtime_agent->{'id_agent'});
		}
	}
}

##########################################################################
# Reset the status of all server types for the current server.
##########################################################################
sub pandora_reset_server ($$) {
	my ($pa_config, $dbh) = @_;
	    
	db_do ($dbh, 'UPDATE tserver SET status = 0, threads = 0, queued_modules = 0 WHERE name = ?', $pa_config->{'servername'});
}

##########################################################################
# Update server status: 0 dataserver, 1 network server, 2 snmp console, 
# 3 recon, 4 plugin, 5 prediction, 6 wmi.
##########################################################################
sub pandora_update_server ($$$$$;$$) {
	my ($pa_config, $dbh, $server_name, $status,
	    $server_type, $num_threads, $queue_size) = @_;
	    
	$num_threads = 0 unless defined ($num_threads);
	$queue_size = 0 unless defined ($queue_size);

	my $server = get_db_single_row ($dbh, 'SELECT * FROM tserver WHERE name = ? AND server_type = ?', $server_name, $server_type);
	my $timestamp = strftime ("%Y-%m-%d %H:%M:%S", localtime());

	# Create an entry in tserver
	if (! defined ($server)){ 
		my $server_id = db_insert ($dbh, 'INSERT INTO tserver (`name`, `server_type`, `description`, `version`, `threads`, `queued_modules`)
		                                  VALUES (?, ?, ?, ?, ?, ?)', $server_name, $server_type,
		                                  'Autocreated at startup', $pa_config->{'version'} . ' (P) ' . $pa_config->{'build'}, $num_threads, $queue_size);
		$server = get_db_single_row ($dbh, 'SELECT * FROM tserver
		                                    WHERE id_server = ?',
		                             $server_id);
		return unless defined ($server);
	}

	# Server going up
	if ($server->{'status'} == 0) {
		my $version = $pa_config->{'version'} . ' (P) ' . $pa_config->{'build'};

		db_do ($dbh, 'UPDATE tserver SET status = ?, keepalive = ?, master = ?, laststart = ?, version = ?, threads = ?, queued_modules = ?
		              WHERE id_server = ?',
		       $status, $timestamp, $pa_config->{'pandora_master'}, $timestamp, $version, $num_threads, $queue_size, $server->{'id_server'});
		return;
	}

	db_do ($dbh, 'UPDATE tserver SET status = ?, keepalive = ?, master = ?, threads = ?, queued_modules = ?
	              WHERE id_server = ?', $status, $timestamp, $pa_config->{'pandora_master'}, $num_threads, $queue_size, $server->{'id_server'});
}

##########################################################################
# Update last contact field in agent table
##########################################################################
sub pandora_update_agent ($$$$$$$) {
	my ($pa_config, $agent_timestamp, $agent_id, $os_version,
	    $agent_version, $agent_interval, $dbh) = @_;

	my $timestamp = strftime ("%Y-%m-%d %H:%M:%S", localtime());
	
	pandora_access_update ($pa_config, $agent_id, $dbh);

	# No update for interval field (some old agents don't support it)
	if ($agent_interval == -1){
		db_do($dbh, 'UPDATE tagente SET agent_version = ?, ultimo_contacto_remoto = ?, ultimo_contacto = ?, os_version = ? WHERE id_agente = ?',
		      $agent_version, $agent_timestamp, $timestamp, $os_version, $agent_id);
		return;
	}
	
	db_do ($dbh, 'UPDATE tagente SET intervalo = ?, agent_version = ?, ultimo_contacto_remoto = ?, ultimo_contacto = ?, os_version = ? WHERE id_agente = ?',
	       $agent_interval, $agent_version, $agent_timestamp, $timestamp, $os_version, $agent_id);
}

##########################################################################
# Updates the keep_alive module for the given agent.
##########################################################################
sub pandora_module_keep_alive ($$$$$) {
	my ($pa_config, $id_agent, $agent_name, $server_id, $dbh) = @_;
	
	# Update keepalive module 
	my $module = get_db_single_row ($dbh, 'SELECT * FROM tagente_modulo WHERE id_agente = ? AND id_tipo_modulo = 100', $id_agent);
	return unless defined ($module);

	pandora_process_module ($pa_config, 1, '', $module, 'keep_alive', '', time(), $server_id, $dbh);
}

##########################################################################
# Create an internal Pandora incident.
##########################################################################
sub pandora_create_incident ($$$$$$$$) {
	my ($pa_config, $dbh, $title, $text,
	    $priority, $status, $origin, $id_group) = @_;

	db_do($dbh, 'INSERT INTO tincidencia (`inicio`, `titulo`, `descripcion`, `origen`, `estado`, `prioridad`, `id_grupo`)
	             VALUES (NOW(), ?, ?, ?, ?, ?, ?)', $title, $text, $origin, $status, $priority, $id_group);
}


##########################################################################
# Create an internal audit entry.
##########################################################################
sub pandora_audit ($$$$$) {
	my ($pa_config, $description, $name, $action, $dbh) = @_;
	my $disconnect = 0;

	my $utimestamp = time();
	my $timestamp = strftime ("%Y-%m-%d %H:%M:%S", localtime($utimestamp));

	db_insert($dbh, 'INSERT INTO tsesion (`ID_usuario`, `IP_origen`, `accion`, `fecha`, `descripcion`, `utimestamp`) 
	                 VALUES (?, ?, ?, ?, ?, ?)', 
	          'SYSTEM', $name, $action , $timestamp , $description , $utimestamp);

	db_disconnect($dbh) if ($disconnect == 1);
}

##########################################################################
# Create a new entry in tagente_modulo and the corresponding entry in
# tagente_estado.
##########################################################################
sub pandora_create_module ($$$$$$$$) {
	my ($agent_id, $module_type_id, $module_name, $max,
	    $min, $description, $interval, $dbh) = @_;
 
	# Provide some default values	
	$max = 0 if ($max eq '');
	$min = 0 if ($min eq '');
	$description = 'N/A' if ($description eq '');

	my $module_id = db_insert($dbh, 'INSERT INTO tagente_modulo (`id_agente`, `id_tipo_modulo`, `nombre`, `max`, `min`, `descripcion`, `module_interval`, `id_modulo`)
	                        VALUES (?, ?, ?, ?, ?, ?, ?, 1)', $agent_id, $module_type_id, $module_name, $max, $min, $description, $interval);
	db_do ($dbh, 'INSERT INTO tagente_estado (`id_agente_modulo`, `last_try`) VALUES (?, \'0000-00-00 00:00:00\')', $module_id);
	return $module_id;
}

##########################################################################
# Create a new entry in tagente.
##########################################################################
sub pandora_create_agent ($$$$$$$$$) {
	my ($pa_config, $server_name, $agent_name, $address,
	    $address_id, $group_id, $parent_id, $os_id, $dbh) = @_;

	logger ($pa_config, "$server_name: Creating agent $agent_name ($address)", 1);

	my $agent_id = db_insert ($dbh, 'INSERT INTO tagente (`nombre`, `direccion`, `comentarios`, `id_grupo`, `id_os`, `server_name`, `intervalo`, `id_parent`, `modo`)
	                              VALUES  (?, ?, ?, ?, ?, ?, 300, ?, 1)', $agent_name, $address, "Created by $server_name", $group_id, $os_id, $server_name, $parent_id);

	pandora_event ($pa_config, "Agent '$agent_name' created by $server_name", $pa_config->{'autocreate_group'}, $agent_id, 2, 0, 0, 'new_agent', $dbh);
	return $agent_id;
}

##########################################################################
# Generate an event.
##########################################################################
sub pandora_event (%$$$$$$$$) {
	my ($pa_config, $evento, $id_grupo, $id_agente, $severity,
		$id_alert_am, $id_agentmodule, $event_type, $dbh) = @_;

	my $utimestamp = time ();
	my $timestamp = strftime ("%Y-%m-%d %H:%M:%S", localtime ($utimestamp));

	db_do ($dbh, 'INSERT INTO tevento (`id_agente`, `id_grupo`, `evento`, `timestamp`, `estado`, `utimestamp`, `event_type`, `id_agentmodule`, `id_alert_am`, `criticity`)
	              VALUES (?, ?, ?, ?, 0, ?, ?, ?, ?, ?)', $id_agente, $id_grupo, $evento, $timestamp, $utimestamp, $event_type, $id_agentmodule, $id_alert_am, $severity);
}

##########################################################################
# Generate an event with the given status. TODO: Merge with pandora_event
##########################################################################
sub pandora_event_status ($$$$$$$$$$) {
	my ($pa_config, $evento, $id_grupo, $id_agente, $severity,
		$id_alert_am, $id_agentmodule, $event_type, $status, $dbh) = @_;

	my $utimestamp = time();
	my $timestamp = strftime ("%Y-%m-%d %H:%M:%S", localtime($utimestamp));

	db_do ($dbh, 'INSERT INTO tevento (`id_agente`, `id_grupo`, `evento`, `timestamp`, `estado`, `utimestamp`, `event_type`, `id_agentmodule`, `id_alert_am`, `criticity`)
	              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', $id_agente, $id_grupo, $evento, $timestamp, $status, $utimestamp, $event_type, $id_agentmodule, $id_alert_am, $severity);
}

##########################################################################
# Update module status on error.
##########################################################################
sub pandora_update_module_on_error ($$$) {
	my ($pa_config, $id_agent_module, $dbh) = @_;

	# Update last_execution_try
	db_do ($dbh, 'UPDATE tagente_estado SET last_execution_try = ?
				  WHERE id_agente_modulo = ?', time (), $id_agent_module);
}

##########################################################################
# Execute forced alerts.
##########################################################################
sub pandora_exec_forced_alerts {
	my ($pa_config, $dbh) = @_;

	# Get alerts marked for forced execution (even disabled alerts)
	my @alerts = get_db_rows ($dbh, 'SELECT talert_template_modules.id as id_template_module, talert_template_modules.*, talert_templates.*, tagente.*
	                               FROM talert_template_modules, talert_templates, tagente, tagente_modulo
	                               WHERE talert_template_modules.id_alert_template = talert_templates.id
	                               AND talert_template_modules.id_agent_module = tagente_modulo.id_agente_modulo
	                               AND tagente_modulo.id_agente = tagente.id_agente
	                               AND force_execution = 1');

	foreach my $alert (@alerts) {

		# $alert already contains agent data!
		pandora_execute_alert ($pa_config, 'N/A', $alert, undef, $alert, 1, $dbh);

		# Reset the force_execution flag, even if the alert could not be executed
		db_do ($dbh, "UPDATE talert_template_modules SET force_execution = 0 WHERE id = " . $alert->{'id_template_module'});
	}
}

##########################################################################
# Update keep_alive modules for agents without data.
##########################################################################
sub pandora_module_keep_alive_nd {
	my ($pa_config, $dbh) = @_;

	my @modules = get_db_rows ($dbh, 'SELECT tagente_modulo.*
									FROM tagente_modulo, tagente_estado, tagente 
									WHERE tagente.id_agente = tagente_estado.id_agente 
									AND tagente.disabled = 0 
									AND tagente_modulo.id_tipo_modulo = 100 
									AND tagente_modulo.disabled = 0 
									AND tagente_estado.datos = 1 
									AND tagente_estado.estado = 0 
									AND tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo 
									AND ( tagente_estado.utimestamp + (tagente.intervalo * 2) < UNIX_TIMESTAMP())');

	foreach my $module (@modules) {
		pandora_process_module ($pa_config, 1, '', $module, 'keep_alive', '', time (), 0, $dbh);
	}
}

##########################################################################
# Execute alerts that apply to the given SNMP trap.
##########################################################################
sub pandora_evaluate_snmp_alerts ($$$$$$$$) {
	my ($pa_config, $trap_id, $trap_agent, $trap_oid,
	    $trap_oid_text, $trap_custom_oid, $trap_custom_value, $dbh) = @_;
	
	# Get all SNMP alerts
	my @snmp_alerts = get_db_rows ($dbh, 'SELECT * FROM talert_snmp');

	# Find those that apply to the given SNMP trap
	foreach my $alert (@snmp_alerts) {
		my ($fire_alert, $alert_data) = (0, '');		
		my ($times_fired, $internal_counter, $alert_type) =
		   ($alert->{'times_fired'}, $alert->{'internal_counter'}, $alert->{'alert_type'});

		# OID only
		if ($alert->{'alert_type'} == 0) {
			my $oid = $alert->{'oid'};
			($fire_alert, $alert_data) = (1, 'SNMP/OID:' . $oid) if ($trap_oid =~ m/$oid/i ||
			                                                         $trap_oid_text =~ m/$oid/i);
		# Custom OID/value
		} elsif ($alert_type == 1){ # type 1 is custom value 
			my $custom_oid = $alert->{'custom_oid'};
			($fire_alert, $alert_data) = (1, 'SNMP/VALUE:' . $custom_oid) if ($trap_custom_value =~ m/$custom_oid/i ||		                                                                  $trap_custom_oid =~ m/$custom_oid/i);
		# Agent IP
		} else {
			my $agent = $alert->{'agent'};
			($fire_alert, $alert_data) = (1, 'SNMP/SOURCE:' . $agent) if ($trap_agent =~ m/$agent/i );
		}

		next unless ($fire_alert == 1);
		
		# Check time threshold
		$alert->{'last_fired'} = '0000-00-00 00:00:00' unless defined ($alert->{'last_fired'});
		return unless ($alert->{'last_fired'} =~ /(\d+)\-(\d+)\-(\d+) +(\d+):(\d+):(\d+)/);
		my $last_fired = ($1 > 0) ? timelocal($6, $5, $4, $3, $2 - 1, $1 - 1900) : 0;

		my $utimestamp = time ();
		my $timestamp = strftime ("%Y-%m-%d %H:%M:%S", localtime($utimestamp));

		# Out of limits, start a new interval
		($times_fired, $internal_counter) = (0, 0) if ($utimestamp >= ($last_fired + $alert->{'time_threshold'}));

		# Execute the alert
		my ($min_alerts, $max_alerts) = ($alert->{'min_alerts'}, $alert->{'max_alerts'});
		if (($internal_counter + 1 >= $min_alerts) && ($times_fired + 1 <= $max_alerts)) {
			($times_fired++, $internal_counter++);

			my %alert = (
				'name' => '',
				'agent' => 'N/A',
				'alert_data' => 'N/A',
				'id_agent_module' => 0,
				'id_template_module' => 0,
				'field1' => $alert->{'al_field1'},
				'field2' => $alert->{'al_field2'},
				'field3' => $alert->{'al_field3'},
				'description' => $alert->{'description'},
				'times_fired' => $times_fired,
				'time_threshold' => 0,
				'id_alert_action' => $alert->{'id_alert'},
				'priority' => $alert->{'priority'},
			);

			# Execute alert
			my $action = get_db_single_row ($dbh, 'SELECT *
			                                       FROM talert_actions, talert_commands
			                                       WHERE talert_actions.id_alert_command = talert_commands.id
			                                         AND talert_actions.id = ?', $alert->{'id_alert'});

			pandora_execute_action ($pa_config, '', undef, \%alert, 1, $action, $dbh) if (defined ($action));

			# Generate an event
			pandora_event ($pa_config, "SNMP alert fired (" . $alert->{'description'} . ")",
				           0, 0, $alert->{'priority'}, 0, 0, 'alert_fired',  $dbh);

			# Update alert status
			db_do ($dbh, 'UPDATE talert_snmp SET times_fired = ?, last_fired = ?, internal_counter = ? WHERE id_as = ?',
				   $times_fired, $timestamp, $internal_counter, $alert->{'id_as'});

			db_do ($dbh, 'UPDATE ttrap SET alerted = 1, priority = ? WHERE id_trap = ?',
				   $alert->{'priority'}, $trap_id);
		} else {
			$internal_counter++;
			if ($internal_counter < $min_alerts){
				# Now update the new value for times_fired & last_fired if we are below min limit for triggering this alert
				db_do ($dbh, 'UPDATE talert_snmp SET internal_counter = ?, times_fired = ?, last_fired = ? WHERE id_as = ?',
				       $internal_counter, $times_fired, $timestamp, $alert->{'id_as'});
			} else {
				db_do ($dbh, 'UPDATE talert_snmp SET times_fired = ?, internal_counter = ? WHERE id_as = ?',
				       $times_fired, $internal_counter, $alert->{'id_as'});
			}
		}
	}
}

##############################################################################
# Ping the given host. Returns 1 if the host is alive, 0 otherwise.
##############################################################################
sub pandora_ping ($$) { 
	my ($pa_config, $host) = @_;

	# Ping the host
	`ping -q -W $pa_config->{'networktimeout'} -n -c $pa_config->{'icmp_checks'} $host >/dev/null 2>&1`;

	return ($? == 0) ? 1 : 0;
}

##############################################################################
# Ping the given host. Returns the average round-trip time.
##############################################################################
sub pandora_ping_latency ($$) {
	my ($pa_config, $host) = @_;

	# Ping the host
	my @output = `ping -q -W $pa_config->{'networktimeout'} -n -c $pa_config->{'icmp_checks'} $host 2>/dev/null`;
	
	# Something went wrong
	return 0 if ($? != 0);
	
	# Parse the output
	my $stats = pop (@output);
	return 0 unless ($stats =~ m/([\d\.]+)\/([\d\.]+)\/([\d\.]+)\/([\d\.]+) +ms/);
	return $2;
}

##########################################################################
# Utility functions, not to be exported.
##########################################################################

##########################################################################
# Search string for macros and substitutes them with their values.
##########################################################################
sub subst_alert_macros ($$) {
        my ($string, $macros) = @_;

        while ((my $macro, my $value) = each (%{$macros})) {
                $string =~ s/($macro)/$value/ig;
        }

        return $string;
}

##########################################################################
# Process module data.
##########################################################################
sub process_data ($$$$$) {
	my ($data, $module, $module_type, $utimestamp, $dbh) = @_;

	# String data
	if ($module_type =~ m/_string$/) {

		# Empty strings are not allowed
		return undef if ($data eq '');

		return $data;
	}

	# Not a number
	if (! is_numeric ($data)) {
		return undef;
	}

	# Out of bounds
	return undef if (($module->{'max'} != $module->{'min'}) &&
	                 ($data > $module->{'max'} || $data < $module->{'min'}));
	
	# Process INC modules
	if ($module_type =~ m/_inc$/) {
		$data = process_inc_data ($data, $module, $utimestamp, $dbh);
		
		# Not an error, no previous data
		return 0 unless defined ($data);
	}

	# Post process
	if (is_numeric ($module->{'post_process'}) && $module->{'post_process'} != 0) {
		$data = $data * $module->{'post_process'};
	}

	# Format data
	$data = sprintf("%.2f", $data);

	return $data;
}

##########################################################################
# Process data of type *_inc.
##########################################################################
sub process_inc_data ($$$$) {
	my ($data, $module, $utimestamp, $dbh) = @_;

	my $data_inc = get_db_single_row ($dbh, 'SELECT * FROM tagente_datos_inc WHERE id_agente_modulo = ?', $module->{'id_agente_modulo'});

	# No previous data
	if (! defined ($data_inc)) {
		db_insert ($dbh, 'INSERT INTO tagente_datos_inc
		              (`id_agente_modulo`, `datos`, `utimestamp`)
		              VALUES (?, ?, ?)', $module->{'id_agente_modulo'}, $data, $utimestamp);
		return undef;
	}

	# Negative increment, reset inc data
	if ($data < $data_inc->{'datos'}) {
		db_do ($dbh, 'DELETE FROM tagente_datos_inc WHERE id_agente_modulo = ?', $module->{'id_agente_modulo'});		
		db_insert ($dbh, 'INSERT INTO tagente_datos_inc
		              (`id_agente_modulo`, `datos`, `utimestamp`)
		              VALUES (?, ?, ?)', $module->{'id_agente_modulo'}, $data, $utimestamp);
		return undef;
	}

	# Should not happen
	return 0 if ($utimestamp == $data_inc->{'utimestamp'});

	# Update inc data
	db_do ($dbh, 'UPDATE tagente_datos_inc SET datos = ?, utimestamp = ? WHERE id_agente_modulo = ?', $data, $utimestamp, $module->{'id_agente_modulo'});

	return ($data - $data_inc->{'datos'}) / ($utimestamp - $data_inc->{'utimestamp'});
}

##########################################################################
# Returns the status of the module: 0 (NORMAL), 1 (CRITICAL), 2 (WARNING).
##########################################################################
sub get_module_status ($$) {
	my ($data, $module, $module_type) = @_;
	my ($critical_min, $critical_max, $warning_min, $warning_max) =
	   ($module->{'min_critical'}, $module->{'max_critical'}, $module->{'min_warning'}, $module->{'max_warning'});

	# Set default critical max/min for *proc modules
	if ($module_type =~ m/_proc$/ && ($critical_min eq $critical_max)) {
		($critical_min, $critical_max) = (0, 1);
	}

	# Critical
	if ($critical_min ne $critical_max) {
		return 1 if ($data >= $critical_min  && $data < $critical_max);
		return 1 if ($data >= $critical_min && $critical_max < $critical_min);
	}

	# Warning
	if ($warning_min ne $warning_max) {
		return 2 if ($data >= $warning_min  && $data < $warning_max);
		return 2 if ($data >= $warning_min  && $warning_max < $warning_min);
	}

	# Normal
	return 0;
}

##########################################################################
# Generates an event according to the change of status of a module.
##########################################################################
sub generate_status_event ($$$$$$$) {
	my ($pa_config, $data, $agent, $module, $status, $last_status, $dbh) = @_;
	my ($event_type, $severity);

	my $description = "Module " . $module->{'nombre'} . " ($data) is ";

	# Normal
	if ($status == 0) {
		($event_type, $severity) = ('going_down_normal', 2);
		$description .= "going down to NORMAL";
		
	# Critical
	} elsif ($status == 1) {
		($event_type, $severity) = ('going_up_critical', 4);
		$description .= "going up to CRITICAL";
		
	# Warning
	} elsif ($status == 2) {
		
		# From normal
		if ($last_status == 0) {
			($event_type, $severity) = ('going_up_warning', 3);
			$description .= "going up to WARNING";
			
		# From critical
		} elsif ($last_status == 1) {
			($event_type, $severity) = ('going_down_warning', 3);
			$description .= "going down to WARNING";
		} else {
			# Unknown last_status
			return;
		}
	} else {
		# Unknown status
		return;
	}

	# Generate the event
	pandora_event_status ($pa_config, $description, $agent->{'id_grupo'}, $module->{'id_agente'},
	               $severity, 0, $module->{'id_agente_modulo'}, $event_type, $status, $dbh);
}

##########################################################################
# Saves module data to the DB.
##########################################################################
sub save_module_data ($$$$$) {
	my ($data, $module, $module_type, $utimestamp, $dbh) = @_;

	my $table = ($module_type =~ m/_string/) ? 'tagente_datos_string' : 'tagente_datos';

	db_do($dbh, 'INSERT INTO ' . $table . ' (id_agente_modulo, datos, utimestamp)
	             VALUES (?, ?, ?)', $module->{'id_agente_modulo'}, $data, $utimestamp);	
}

##########################################################################
# Export module data.
##########################################################################
sub export_module_data ($$$$$$) {
	my ($data, $agent, $module, $module_type, $timestamp, $dbh) = @_;

	# Data export is disabled
 	return if ($module->{'id_export'} < 1);

	db_do($dbh, 'INSERT INTO tserver_export_data 
	         (`id_export_server`, `agent_name` , `module_name`, `module_type`, `data`, `timestamp`) VALUES
	         (?, ?, ?, ?, ?, ?)', $module->{'id_export'}, $agent->{'nombre'}, $module->{'nombre'}, $module_type, $data, $timestamp);
}

# End of function declaration
# End of defined Code

1;
__END__
