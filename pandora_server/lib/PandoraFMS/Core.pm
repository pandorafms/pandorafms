package PandoraFMS::Core;
##########################################################################
# Core Pandora FMS functions.
# Pandora FMS. the Flexible Monitoring System. http://www.pandorafms.org
##########################################################################
# Copyright (c) 2005-2011 Artica Soluciones Tecnologicas S.L
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

=head1 NAME 

PandoraFMS::Core - Core functions of Pandora FMS

=head1 VERSION

Version 5.0

=head1 SYNOPSIS

 use PandoraFMS::Core;

=head1 DESCRIPTION

This module contains all the base functions of B<Pandora FMS>, the most basic operations of the system are done here.

=head2 Interface
Exported Functions:

=over

=item * C<pandora_audit>

=item * C<pandora_create_agent>

=item * C<pandora_create_group>

=item * C<pandora_create_incident>

=item * C<pandora_create_module>

=item * C<pandora_evaluate_alert>

=item * C<pandora_evaluate_snmp_alerts>

=item * C<pandora_event>

=item * C<pandora_execute_alert>

=item * C<pandora_execute_action>

=item * C<pandora_exec_forced_alerts>

=item * C<pandora_generate_alerts>

=item * C<pandora_module_keep_alive>

=item * C<pandora_module_keep_alive_nd>

=item * C<pandora_planned_downtime>

=item * C<pandora_process_alert>

=item * C<pandora_process_module>

=item * C<pandora_reset_server>

=item * C<pandora_server_keep_alive>

=item * C<pandora_update_agent>

=item * C<pandora_update_agent_address>

=item * C<pandora_update_module_on_error>

=item * C<pandora_update_table_from_hash>

=item * C<pandora_update_server>

=item * C<pandora_group_statistics>

=item * C<pandora_server_statistics>

=item * C<pandora_self_monitoring>

=back

=head1 METHODS

=cut

use strict;
use warnings;

use DBI;
use XML::Simple;
use HTML::Entities;
use Time::Local;
use POSIX qw(strftime);
use threads;
use threads::shared;
use JSON qw(decode_json encode_json);
use MIME::Base64;
use Text::ParseWords;

# Debugging
#use Data::Dumper;

# Force XML::Simple to use XML::Parser instead SAX to manage XML
# due a bug processing some XML with blank spaces.
# See http://www.perlmonks.org/?node_id=706838

$XML::Simple::PREFERRED_PARSER='XML::Parser';

# Default lib dir for RPM and DEB packages
use lib '/usr/lib/perl5';

use PandoraFMS::DB;
use PandoraFMS::Config;
use PandoraFMS::Tools;
use PandoraFMS::GIS qw(distance_moved);

# For Reverse Geocoding
use LWP::Simple;

require Exporter;

our @ISA = ("Exporter");
our %EXPORT_TAGS = ( 'all' => [ qw( ) ] );
our @EXPORT_OK = ( @{ $EXPORT_TAGS{'all'} } );
our @EXPORT = qw( 	
	pandora_add_agent_address
	pandora_audit
	pandora_create_agent
	pandora_create_group
	pandora_create_incident
	pandora_create_module
	pandora_create_module_from_hash
	pandora_create_module_from_network_component
	pandora_create_module_tags
	pandora_create_template_module
	pandora_create_template_module_action
	pandora_delete_agent
	pandora_delete_all_template_module_actions
	pandora_delete_module
	pandora_evaluate_alert
	pandora_evaluate_snmp_alerts
	pandora_event
	pandora_execute_alert
	pandora_execute_action
	pandora_exec_forced_alerts
	pandora_generate_alerts
	pandora_get_config_value
	pandora_get_module_tags
	pandora_get_module_url_tags
	pandora_get_module_phone_tags
	pandora_get_module_email_tags
	pandora_get_os
	pandora_is_master
	pandora_mark_agent_for_alert_update
	pandora_mark_agent_for_module_update
	pandora_module_keep_alive
	pandora_module_keep_alive_nd
	pandora_module_unknown
	pandora_planned_downtime
	pandora_planned_downtime_set_quiet_elements
	pandora_planned_downtime_unset_quiet_elements
	pandora_planned_downtime_set_disabled_elements
	pandora_planned_downtime_unset_disabled_elements
	pandora_planned_downtime_quiet_once_start
	pandora_planned_downtime_quiet_once_stop
	pandora_planned_downtime_disabled_once_start
	pandora_planned_downtime_disabled_once_stop
	pandora_planned_downtime_monthly_start
	pandora_planned_downtime_monthly_stop
	pandora_planned_downtime_weekly_start
	pandora_planned_downtime_weekly_stop
	pandora_process_alert
	pandora_process_event_replication
	pandora_process_module
	pandora_reset_server
	pandora_server_keep_alive
	pandora_set_event_storm_protection
	pandora_set_master
	pandora_update_agent
	pandora_update_agent_address
	pandora_update_agent_alert_count
	pandora_update_agent_module_count
	pandora_update_config_token
	pandora_update_gis_data
	pandora_update_module_on_error
	pandora_update_module_from_hash
	pandora_update_server
	pandora_update_table_from_hash
	pandora_update_template_module
	pandora_group_statistics
	pandora_server_statistics
	pandora_self_monitoring
	pandora_process_policy_queue
	subst_alert_macros
	get_agent_from_addr
	get_agent_from_name
	load_module_macros
	@ServerTypes
	$EventStormProtection
	pandora_create_custom_graph
	pandora_insert_graph_source
	pandora_delete_graph_source
	pandora_delete_custom_graph
	pandora_edit_custom_graph
	);

# Some global variables
our @DayNames = qw(sunday monday tuesday wednesday thursday friday saturday);
our @ServerTypes = qw (dataserver networkserver snmpconsole reconserver pluginserver predictionserver wmiserver exportserver inventoryserver webserver eventserver icmpserver snmpserver);
our @AlertStatus = ('Execute the alert', 'Do not execute the alert', 'Do not execute the alert, but increment its internal counter', 'Cease the alert', 'Recover the alert', 'Reset internal counter');

# Event storm protection (no alerts or events)
our $EventStormProtection :shared = 0;

# Current master server
my $Master :shared = 0;

##########################################################################
# Return the agent given the IP address.
##########################################################################
sub get_agent_from_addr ($$) {
	my ($dbh, $ip_address) = @_;
	
	return 0 if (! defined ($ip_address) || $ip_address eq '');
	
	my $agent = get_db_single_row ($dbh, 'SELECT * FROM taddress, taddress_agent, tagente
	                                    WHERE tagente.id_agente = taddress_agent.id_agent
	                                    AND taddress_agent.id_a = taddress.id_a
	                                    AND ip = ?', $ip_address);
	return $agent;
}

##########################################################################
# Return the agent given the agent name.
##########################################################################
sub get_agent_from_name ($$) {
	my ($dbh, $name) = @_;
	
	return undef if (! defined ($name) || $name eq '');
	
	return get_db_single_row ($dbh, 'SELECT * FROM tagente WHERE tagente.nombre = ?', $name);
}

##########################################################################
=head2 C<< pandora_generate_alerts (I<$pa_config> I<$data> I<$status> I<$agent> I<$module> I<$utimestamp> I<$dbh>  I<$timestamp> I<$extra_macros> I<$last_data_value>) >>

Generate alerts for a given I<$module>.

=cut
##########################################################################
sub pandora_generate_alerts ($$$$$$$$;$$$) {
	my ($pa_config, $data, $status, $agent, $module, $utimestamp, $dbh, $timestamp, $extra_macros, $last_data_value, $alert_type) = @_;

	# No alerts when event storm protection is enabled
	if ($EventStormProtection == 1) {
		return;
	}
	
	if ($agent->{'quiet'} == 1) {
		logger($pa_config, "Generate Alert. The agent '" . $agent->{'nombre'} . "' is in quiet mode.", 10);
		
		return;
	}
	if ($module->{'quiet'} == 1) {
		logger($pa_config, "Generate Alert. The module '" . $module->{'nombre'} . "' is in quiet mode.", 10);
		
		return;
	}
	
	# Do not generate alerts for disabled groups
	if (is_group_disabled ($dbh, $agent->{'id_grupo'})) {
		return;
	}
	
	# Get enabled alerts associated with this module
	my $alert_type_filter = defined ($alert_type) ? " AND type = '$alert_type'" : '';
	my @alerts = get_db_rows ($dbh, '
		SELECT talert_template_modules.id as id_template_module,
			talert_template_modules.*, talert_templates.*
		FROM talert_template_modules, talert_templates
		WHERE talert_template_modules.id_alert_template = talert_templates.id
			AND id_agent_module = ?
			AND disabled = 0' . $alert_type_filter, $module->{'id_agente_modulo'});
	
	foreach my $alert (@alerts) {
		my $rc = pandora_evaluate_alert($pa_config, $agent, $data,
			$status, $alert, $utimestamp, $dbh, $last_data_value);
		
		pandora_process_alert ($pa_config, $data, $agent, $module,
			$alert, $rc, $dbh, $timestamp, $extra_macros);
	}
}

##########################################################################
=head2 C<< pandora_evaluate_alert (I<$pa_config>, I<$agent>, I<$data>, I<$last_status>, I<$alert>, I<$utimestamp>, I<$dbh>) >>

Evaluate trigger conditions for a given alert.

B<Returns>:
 0 Execute the alert.
 1 Do not execute the alert.
 2 Do not execute the alert, but increment its internal counter.
 3 Cease the alert.
 4 Recover the alert.
 5 Reset internal counter (alert not fired, interval elapsed).

=cut
##########################################################################
sub pandora_evaluate_alert ($$$$$$$;$$$) {
	my ($pa_config, $agent, $data, $last_status, $alert, $utimestamp, $dbh, $last_data_value, $events, $event) = @_;
	
	if (defined ($agent)) {
		logger ($pa_config, "Evaluating alert '" . safe_output($alert->{'name'}) . "' for agent '" . safe_output ($agent->{'nombre'}) . "'.", 10);
	}
	else {
		logger ($pa_config, "Evaluating alert '" . safe_output($alert->{'name'}) . "'.", 10);
	}
	
	# Value returned on valid data
	my $status = 1;
	
	# Get current time
	my ($sec, $min, $hour, $mday, $mon, $year, $wday, $yday, $isdst) = localtime(time());
	
	# Check weekday
	if ($alert->{'special_day'}) {
		logger ($pa_config, "Checking special days '" . $alert->{'name'} . "'.", 10);
		my $date = sprintf("%4d%02d%02d", $year + 1900, $mon + 1, $mday);
		# '0001' means every year.
		my $date_every_year = sprintf("0001%02d%02d", $mon + 1, $mday);
		my $special_day = get_db_value ($dbh, 'SELECT same_day FROM talert_special_days WHERE (date = ? OR date = ?) AND (id_group = 0 OR id_group = ?) ORDER BY date DESC', $date, $date_every_year, $alert->{'id_group'});
		
		if (!defined($special_day)) {
			$special_day = '';
		}
		
		if ($special_day ne '') {
			logger ($pa_config, $date . " is a special day for " . $alert->{'name'} . ". (as a " . $special_day . ")", 10);
			return 1 if ($alert->{$special_day} != 1);
		}
		else {
			logger ($pa_config, $date . " is *NOT* a special day for " . $alert->{'name'}, 10);
			return 1 if ($alert->{$DayNames[$wday]} != 1);
		}
	}
	else {
		return 1 if ($alert->{$DayNames[$wday]} != 1);
	}
	
	# Check time slot
	my $time = sprintf ("%.2d:%.2d:%.2d", $hour, $min, $sec);
	if (($alert->{'time_from'} ne $alert->{'time_to'})) {
		if ($alert->{'time_from'} lt $alert->{'time_to'}) {
			return 1 if (($time le $alert->{'time_from'}) || ($time ge $alert->{'time_to'}));
		} else {
			return 1 if (($time le $alert->{'time_from'}) && ($time ge $alert->{'time_to'}));
		}
	}
	
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
		
	}
	elsif ($utimestamp > $limit_utimestamp && $alert->{'internal_counter'} > 0) {
		$status = 5;
	}
	
	# Update fired alert when cesead or recover
	if(defined ($agent) && ($status == 3 || $status == 4)) {
		pandora_mark_agent_for_alert_update ($dbh, $agent->{'id_agente'});
	}
	
	# Check for valid data
	# Simple alert
	if (defined ($alert->{'id_template_module'})) {
		return $status if ($alert->{'type'} eq "min" && $data >= $alert->{'min_value'});
		return $status if ($alert->{'type'} eq "max" && $data <= $alert->{'max_value'});
		
		if ($alert->{'type'} eq "max_min") {
			if ($alert->{'matches_value'} == 1) {
				return $status if ($data <= $alert->{'min_value'} ||
					$data >= $alert->{'max_value'});
			}
			else {
				return $status if ($data >= $alert->{'min_value'} &&
					$data <= $alert->{'max_value'});
			}
		}
		
		if ($alert->{'type'} eq "onchange") {
			if ($alert->{'matches_value'} == 1) {
				if (is_numeric($last_data_value)) {
					return $status if ($last_data_value == $data);
				}
				else {
					return $status if ($last_data_value eq $data);
				}
			}
			else {
				if (is_numeric($last_data_value)) {
					return $status if ($last_data_value != $data);
				}
				else {
					return $status if ($last_data_value ne $data);
				}
			}
		}
		
		return $status if ($alert->{'type'} eq "equal" && $data != $alert->{'value'});
		return $status if ($alert->{'type'} eq "not_equal" && $data == $alert->{'value'});
		if ($alert->{'type'} eq "regex") {
			
			# Make sure the regexp is valid
			if (valid_regex ($alert->{'value'}) == 0) {
				logger ($pa_config, "Error evaluating alert '" .
					safe_output($alert->{'name'}) . "' for agent '" .
					safe_output($agent->{'nombre'}) . "': '" . $alert->{'value'} . "' is not a valid regular expression.", 10);
				return $status;
			}
			
			if ($alert->{'matches_value'} == 1) {
				return $status if (valid_regex ($alert->{'value'}) == 1 && $data !~ m/$alert->{'value'}/i);
			}
			else {
				return $status if (valid_regex ($alert->{'value'}) == 1 && $data =~ m/$alert->{'value'}/i);
			}
		}
		
		return $status if ($last_status != 1 && $alert->{'type'} eq 'critical');
		return $status if ($last_status != 2 && $alert->{'type'} eq 'warning');
		return $status if ($last_status != 3 && $alert->{'type'} eq 'unknown');
	}
	# Event alert
	else {
		my $rc = enterprise_hook ('evaluate_event_alert', [$pa_config, $dbh, $alert, $events, $event]);
		return $status unless (defined ($rc) && $rc == 1);
	}
	
	# Check min and max alert limits
	return 2 if (($alert->{'internal_counter'} < $alert->{'min_alerts'}) ||
		($alert->{'times_fired'} >= $alert->{'max_alerts'}));
		
	# Update fired alert first time 
	# (if is fist time after ceased it was decreased previously and will be compensated)
	if(defined ($agent)) {
		pandora_mark_agent_for_alert_update ($dbh, $agent->{'id_agente'});
	}
	
	return 0; #Launch the alert
}

##########################################################################
=head2 C<< pandora_process_alert (I<$pa_config>, I<$data>, I<$agent>, I<$module>, I<$alert>, I<$rc>, I<$dbh> I<$timestamp>) >> 

Process an alert given the status returned by pandora_evaluate_alert.

=cut
##########################################################################
sub pandora_process_alert ($$$$$$$$;$) {
	my ($pa_config, $data, $agent, $module, $alert, $rc, $dbh, $timestamp, $extra_macros) = @_;
	
	if (defined ($agent)) {
		logger ($pa_config, "Processing alert '" . safe_output($alert->{'name'}) . "' for agent '" . safe_output($agent->{'nombre'}) . "': " . (defined ($AlertStatus[$rc]) ? $AlertStatus[$rc] : 'Unknown status') . ".", 10);
	}
	else {
		logger ($pa_config, "Processing alert '" . safe_output($alert->{'name'}) . "': " . (defined ($AlertStatus[$rc]) ? $AlertStatus[$rc] : 'Unknown status') . ".", 10);
	}
	
	# Simple or event alert?
	my ($id, $table) = (undef, undef);
	if (defined ($alert->{'id_template_module'})) {
		$id = $alert->{'id_template_module'};
		$table = 'talert_template_modules';
	} else {
		$id = $alert->{'id'};
		$table = 'tevent_alert';
	}
	
	# Do not execute
	return if ($rc == 1);
	
	# Cease
	if ($rc == 3) {
		
		# Update alert status
		db_do($dbh, 'UPDATE ' . $table . ' SET times_fired = 0,
			internal_counter = 0 WHERE id = ?', $id);
		
		# Critical_instructions, warning_instructions, unknown_instructions
		my $critical_instructions = get_db_value ($dbh, 'SELECT critical_instructions FROM tagente_modulo WHERE id_agente_modulo = ?', $alert->{'id_agent_module'});
		my $warning_instructions = get_db_value ($dbh, 'SELECT warning_instructions FROM tagente_modulo WHERE id_agente_modulo = ?', $alert->{'id_agent_module'});
		my $unknown_instructions = get_db_value ($dbh, 'SELECT unknown_instructions FROM tagente_modulo WHERE id_agente_modulo = ?', $alert->{'id_agent_module'});

		$alert->{'critical_instructions'} = $critical_instructions;
		$alert->{'warning_instructions'} = $warning_instructions;
		
		# Generate an event
		if ($table eq 'tevent_alert') {
			pandora_event ($pa_config, "Alert ceased (" .
				$alert->{'name'} . ")", 0, 0, $alert->{'priority'}, $id,
				(defined ($alert->{'id_agent_module'}) ? $alert->{'id_agent_module'} : 0), 
				"alert_ceased", 0, $dbh, 'Pandora', '', '', '', '', $critical_instructions, $warning_instructions, $unknown_instructions);
		}  else {
			pandora_event ($pa_config, "Alert ceased (" .
					$alert->{'name'} . ")", $agent->{'id_grupo'},
					$agent->{'id_agente'}, $alert->{'priority'}, $id,
					(defined ($alert->{'id_agent_module'}) ? $alert->{'id_agent_module'} : 0),
					"alert_ceased", 0, $dbh, 'Pandora', '', '', '', '', $critical_instructions, $warning_instructions, $unknown_instructions);
		}
		return;
	}

	# Recover
	if ($rc == 4) {

		# Update alert status
		db_do($dbh, 'UPDATE ' . $table . ' SET times_fired = 0,
				 internal_counter = 0 WHERE id = ?', $id);

		# Reset action thresholds
		if (defined ($alert->{'id_template_module'})) {
			db_do($dbh, 'UPDATE talert_template_module_actions SET last_execution = 0 WHERE id_alert_template_module = ?', $id);
		}

		pandora_execute_alert ($pa_config, $data, $agent, $module, $alert, 0, $dbh, $timestamp, $extra_macros);
		return;
	}

	# Reset internal counter
	if ($rc == 5) {
		db_do($dbh, 'UPDATE ' . $table . ' SET internal_counter = 0 WHERE id = ?', $id);
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
		db_do($dbh, 'UPDATE ' . $table . ' SET times_fired = ?,
			internal_counter = ? ' . $new_interval . ' WHERE id = ?',
			$alert->{'times_fired'}, $alert->{'internal_counter'}, $id);
		
		return;
	}
	
	# Execute
	if ($rc == 0) {
		# Update alert status
		$alert->{'times_fired'} += 1;
		$alert->{'internal_counter'} += 1;
		
		db_do($dbh, 'UPDATE ' . $table . ' SET times_fired = ?,
				last_fired = ?, internal_counter = ? ' . $new_interval . ' WHERE id = ?',
			$alert->{'times_fired'}, $utimestamp, $alert->{'internal_counter'}, $id);
		
		pandora_execute_alert ($pa_config, $data, $agent, $module, $alert, 1, $dbh, $timestamp, $extra_macros);
		return;
	}
}

##########################################################################
=head2 C<< pandora_execute_alert (I<$pa_config>, I<$data>, I<$agent>, I<$module>, I<$alert>, I<$alert_mode>, I<$dbh>, I<$timestamp>) >> 

Execute the given alert.

=cut
##########################################################################
sub pandora_execute_alert ($$$$$$$$;$) {
	my ($pa_config, $data, $agent, $module,
		$alert, $alert_mode, $dbh, $timestamp, $extra_macros) = @_;
	
	# Alerts in stand-by are not executed
	if ($alert->{'standby'} == 1) {
		if (defined ($module)) {
			logger ($pa_config, "Alert '" . safe_output($alert->{'name'}) . "' for module '" . safe_output($module->{'nombre'}) . "' is in stand-by. Not executing.", 10);
		} else {
			logger ($pa_config, "Alert '" . safe_output($alert->{'name'}) . "' is in stand-by. Not executing.", 10);
		}
		return;
	}
	
	if (defined ($module)) {
		logger ($pa_config, "Executing alert '" . safe_output($alert->{'name'}) . "' for module '" . safe_output($module->{'nombre'}) . "'.", 10);
	}
	else {
		logger ($pa_config, "Executing alert '" . safe_output($alert->{'name'}) . "'.", 10);
	}
	
	# Get active actions/commands
	my @actions;
	
	# Simple alert
	if (defined ($alert->{'id_template_module'})) {
		@actions = get_db_rows ($dbh, 'SELECT *, talert_template_module_actions.id AS id_alert_template_module_actions
					FROM talert_template_module_actions, talert_actions, talert_commands
					WHERE talert_template_module_actions.id_alert_action = talert_actions.id
					AND talert_actions.id_alert_command = talert_commands.id
					AND talert_template_module_actions.id_alert_template_module = ?
					AND ((fires_min = 0 AND fires_max = 0)
					OR (fires_min <= fires_max AND ? >= fires_min AND ? <= fires_max)
					OR (fires_min > fires_max AND ? >= fires_min))', 
					$alert->{'id_template_module'}, $alert->{'times_fired'}, $alert->{'times_fired'}, $alert->{'times_fired'});	

		# Get default action
		if ($#actions < 0) {
			@actions = get_db_rows ($dbh, 'SELECT * FROM talert_actions, talert_commands
						WHERE talert_actions.id = ?
						AND talert_actions.id_alert_command = talert_commands.id',
						$alert->{'id_alert_action'});
		}
	}
	# Event alert
	else {
		@actions = get_db_rows ($dbh, 'SELECT * FROM tevent_alert_action, talert_actions, talert_commands
					WHERE tevent_alert_action.id_alert_action = talert_actions.id
					AND talert_actions.id_alert_command = talert_commands.id
					AND tevent_alert_action.id_event_alert = ?
					AND ((fires_min = 0 AND fires_max = 0)
					OR (fires_min <= fires_max AND ? >= fires_min AND ? <= fires_max)
					OR (fires_min > fires_max AND ? >= fires_min))', 
					$alert->{'id'}, $alert->{'times_fired'}, $alert->{'times_fired'}, $alert->{'times_fired'});
					
		# Get default action
		if ($#actions < 0) {
			@actions = get_db_rows ($dbh, 'SELECT * FROM talert_actions, talert_commands
						WHERE talert_actions.id = ?
						AND talert_actions.id_alert_command = talert_commands.id',
						$alert->{'id_alert_action'});
		}
	}

	# No actions defined
	if ($#actions < 0) {
		if (defined ($module)) {
			logger ($pa_config, "No actions defined for alert '" . safe_output($alert->{'name'}) . "' module '" . safe_output($module->{'nombre'}) . "'.", 10);
		} else {
			logger ($pa_config, "No actions defined for alert '" . safe_output($alert->{'name'}) . "'.", 10);
		}
		return;
	}

	# Critical_instructions, warning_instructions, unknown_instructions
	my $critical_instructions = get_db_value ($dbh, 'SELECT critical_instructions FROM tagente_modulo WHERE id_agente_modulo = ?', $alert->{'id_agent_module'});
	my $warning_instructions = get_db_value ($dbh, 'SELECT warning_instructions FROM tagente_modulo WHERE id_agente_modulo = ?', $alert->{'id_agent_module'});
	my $unknown_instructions = get_db_value ($dbh, 'SELECT unknown_instructions FROM tagente_modulo WHERE id_agente_modulo = ?', $alert->{'id_agent_module'});

	$alert->{'critical_instructions'} = $critical_instructions;
	$alert->{'warning_instructions'} = $warning_instructions;

	# Execute actions
	my $event_generated = 0;
	foreach my $action (@actions) {
		
		# Check the action threshold (template_action_threshold takes precedence over action_threshold)
		my $threshold = 0;
		$action->{'last_execution'} = 0 unless defined ($action->{'last_execution'});
		$threshold = $action->{'action_threshold'} if (defined ($action->{'action_threshold'}) && $action->{'action_threshold'} > 0);
		$threshold = $action->{'module_action_threshold'} if (defined ($action->{'module_action_threshold'}) && $action->{'module_action_threshold'} > 0);
		if (time () >= ($action->{'last_execution'} + $threshold)) {
			
			# Does the action generate an event?
			if (safe_output($action->{'name'}) eq "Pandora FMS Event") {
				$event_generated = 1;
			}
			
			pandora_execute_action ($pa_config, $data, $agent, $alert, $alert_mode, $action, $module, $dbh, $timestamp, $extra_macros);
		} else {
			if (defined ($module)) {
				logger ($pa_config, "Skipping action " . safe_output($action->{'name'}) . " for alert '" . safe_output($alert->{'name'}) . "' module '" . safe_output($module->{'nombre'}) . "'.", 10);
			} else {
				logger ($pa_config, "Skipping action " . safe_output($action->{'name'}) . " for alert '" . safe_output($alert->{'name'}) . "'.", 10);
			}
		}
	}
	
	# Generate an event	only if an event has not already been generated by an alert action
	if ($event_generated == 0) {

		#If we've spotted an alert recovered, we set the new event's severity to 2 (NORMAL), otherwise the original value is maintained.
		my ($text, $event, $severity) = ($alert_mode == 0) ? ('recovered', 'alert_recovered', 2) : ('fired', 'alert_fired', $alert->{'priority'});

		pandora_event ($pa_config, "Alert $text (" . safe_output($alert->{'name'}) . ") " . (defined ($module) ? 'assigned to ('. safe_output($module->{'nombre'}) . ")" : ""),
 			(defined ($agent) ? $agent->{'id_grupo'} : 0), (defined ($agent) ? $agent->{'id_agente'} : 0), $severity, (defined ($alert->{'id_template_module'}) ? $alert->{'id_template_module'} : 0),
			(defined ($alert->{'id_agent_module'}) ? $alert->{'id_agent_module'} : 0), $event, 0, $dbh, 'Pandora', '', '', '', '', $critical_instructions, $warning_instructions, $unknown_instructions);
	}
}

##########################################################################
=head2 C<< pandora_execute_action (I<$pa_config>, I<$data>, I<$agent>, I<$alert>, I<$alert_mode>, I<$action>, I<$module>, I<$dbh>, I<$timestamp>) >> 

Execute the given action.

=cut
##########################################################################
sub pandora_execute_action ($$$$$$$$$;$) {
	my ($pa_config, $data, $agent, $alert,
		$alert_mode, $action, $module, $dbh, $timestamp, $extra_macros) = @_;

	logger($pa_config, "Executing action '" . safe_output($action->{'name'}) . "' for alert '". safe_output($alert->{'name'}) . "' agent '" . (defined ($agent) ? safe_output($agent->{'nombre'}) : 'N/A') . "'.", 10);

	my $clean_name = safe_output($action->{'name'});

	my ($field1, $field2, $field3, $field4, $field5, $field6, $field7, $field8, $field9, $field10);

	if (!defined($alert->{'snmp_alert'})) {
		# Regular alerts
		$field1 = $action->{'field1'} ? $action->{'field1'} : $alert->{'field1'};
		$field2 = $action->{'field2'} ? $action->{'field2'} : $alert->{'field2'};
		$field3 = $action->{'field3'} ? $action->{'field3'} : $alert->{'field3'};
		$field4 = $action->{'field4'} ? $action->{'field4'} : $alert->{'field4'};
		$field5 = $action->{'field5'} ? $action->{'field5'} : $alert->{'field5'};
		$field6 = $action->{'field6'} ? $action->{'field6'} : $alert->{'field6'};
		$field7 = $action->{'field7'} ? $action->{'field7'} : $alert->{'field7'};
		$field8 = $action->{'field8'} ? $action->{'field8'} : $alert->{'field8'};
		$field9 = $action->{'field9'} ? $action->{'field9'} : $alert->{'field9'};
		$field10 = $action->{'field10'} ? $action->{'field10'} : $alert->{'field10'};
	}
	else {
		$field1 = $alert->{'field1'} ? $alert->{'field1'} : $action->{'field1'};
		$field2 = $alert->{'field2'} ? $alert->{'field2'} : $action->{'field2'};
		$field3 = $alert->{'field3'} ? $alert->{'field3'} : $action->{'field3'};
		$field4 = $action->{'field4'} ? $action->{'field4'} : $alert->{'field4'};
		$field5 = $action->{'field5'} ? $action->{'field5'} : $alert->{'field5'};
		$field6 = $action->{'field6'} ? $action->{'field6'} : $alert->{'field6'};
		$field7 = $action->{'field7'} ? $action->{'field7'} : $alert->{'field7'};
		$field8 = $action->{'field8'} ? $action->{'field8'} : $alert->{'field8'};
		$field9 = $action->{'field9'} ? $action->{'field9'} : $alert->{'field9'};
		$field10 = $action->{'field10'} ? $action->{'field10'} : $alert->{'field10'};
	}
	
	# Recovery fields, thanks to Kato Atsushi
	if ($alert_mode == 0) {
		# Field 1 is a special case where [RECOVER] prefix is not added even when it is defined
		$field1 = $alert->{'field1_recovery'} ? $alert->{'field1_recovery'} : $field1;
		$field1 = $action->{'field1_recovery'} ? $action->{'field1_recovery'} : $field1;

		$field2 = $field2 ? "[RECOVER]" . $field2 : "";
		$field2 = $alert->{'field2_recovery'} ? $alert->{'field2_recovery'} : $field2;
		$field2 = $action->{'field2_recovery'} ? $action->{'field2_recovery'} : $field2;

		$field3 = $field3 ? "[RECOVER]" . $field3 : "";
		$field3 = $alert->{'field3_recovery'} ? $alert->{'field3_recovery'} : $field3;
		$field3 = $action->{'field3_recovery'} ? $action->{'field3_recovery'} : $field3;

		$field4 = $field4 ? "[RECOVER]" . $field4 : "";
		$field4 = $alert->{'field4_recovery'} ? $alert->{'field4_recovery'} : $field4;
		$field4 = $action->{'field4_recovery'} ? $action->{'field4_recovery'} : $field4;

		$field5 = $field5 ? "[RECOVER]" . $field5 : "";
		$field5 = $alert->{'field5_recovery'} ? $alert->{'field5_recovery'} : $field5;
		$field5 = $action->{'field5_recovery'} ? $action->{'field5_recovery'} : $field5;

		$field6 = $field6 ? "[RECOVER]" . $field6 : "";
		$field6 = $alert->{'field6_recovery'} ? $alert->{'field6_recovery'} : $field6;
		$field6 = $action->{'field6_recovery'} ? $action->{'field6_recovery'} : $field6;

		$field7 = $field7 ? "[RECOVER]" . $field7 : "";
		$field7 = $alert->{'field7_recovery'} ? $alert->{'field7_recovery'} : $field7;
		$field7 = $action->{'field7_recovery'} ? $action->{'field7_recovery'} : $field7;

		$field8 = $field8 ? "[RECOVER]" . $field8 : "";
		$field8 = $alert->{'field8_recovery'} ? $alert->{'field8_recovery'} : $field8;
		$field8 = $action->{'field8_recovery'} ? $action->{'field8_recovery'} : $field8;

		$field9 = $field9 ? "[RECOVER]" . $field9 : "";
		$field9 = $alert->{'field9_recovery'} ? $alert->{'field9_recovery'} : $field9;
		$field9 = $action->{'field9_recovery'} ? $action->{'field9_recovery'} : $field9;

		$field10 = $field10 ? "[RECOVER]" . $field10 : "";
		$field10 = $alert->{'field10_recovery'} ? $alert->{'field10_recovery'} : $field10;
		$field10 = $action->{'field10_recovery'} ? $action->{'field10_recovery'} : $field10;
	}

	$field1 = $field1 ? decode_entities($field1) : "";
	$field2 = $field2 ? decode_entities($field2) : "";
	$field3 = $field3 ? decode_entities($field3) : "";
	$field4 = $field4 ? decode_entities($field4) : "";
	$field5 = $field5 ? decode_entities($field5) : "";
	$field6 = $field6 ? decode_entities($field6) : "";
	$field7 = $field7 ? decode_entities($field7) : "";
	$field8 = $field8 ? decode_entities($field8) : "";
	$field9 = $field9 ? decode_entities($field9) : "";
	$field10 = $field10 ? decode_entities($field10) : "";

	# Get group info
	my $group = undef;
	if (defined ($agent)) {
		$group = get_db_single_row ($dbh, 'SELECT * FROM tgrupo WHERE id_grupo = ?', $agent->{'id_grupo'});
	}

	# Thanks to people of Cordoba univ. for the patch for adding module and 
	# id_agent macros to the alert.
	
	# TODO: Reuse queries. For example, tag data can be extracted with a single query.
	# Alert macros
	my %macros = (_field1_ => $field1,
				_field2_ => $field2,
				_field3_ => $field3,
				_field4_ => $field4,
				_field5_ => $field5,
				_field6_ => $field6,
				_field7_ => $field7,
				_field8_ => $field8,
				_field9_ => $field9,
				_field10_ => $field10,
				_agent_ => (defined ($agent)) ? $agent->{'nombre'} : '',
				_agentcustomid_ => (defined ($agent)) ? $agent->{'custom_id'} : '',
				'_agentcustomfield_\d+_'  => undef,
				_agentdescription_ => (defined ($agent)) ? $agent->{'comentarios'} : '',
				_agentgroup_ => (defined ($group)) ? $group->{'nombre'} : '',
				_agentstatus_ => undef,
				_address_ => (defined ($agent)) ? $agent->{'direccion'} : '',
				_timestamp_ => (defined($timestamp)) ? $timestamp : strftime ("%Y-%m-%d %H:%M:%S", localtime()),
				_timezone_ => strftime ("%Z", localtime()),
				_data_ => $data,
				_alert_name_ => $alert->{'name'},
				_alert_description_ => $alert->{'description'},
				_alert_threshold_ => $alert->{'time_threshold'},
				_alert_times_fired_ => $alert->{'times_fired'},
				_alert_priority_ => $alert->{'priority'},
				_alert_text_severity_ => get_priority_name($alert->{'priority'}),
				_alert_critical_instructions_ => $alert->{'critical_instructions'},
				_alert_warning_instructions_ => $alert->{'warning_instructions'},
				_groupcontact_ => (defined ($group)) ? $group->{'contact'} : '',
				_groupcustomid_ => (defined ($group)) ? $group->{'custom_id'} : '',
				_groupother_ => (defined ($group)) ? $group->{'other'} : '',
				_module_ => (defined ($module)) ? $module->{'nombre'} : '',
				_modulecustomid_ => (defined ($module)) ? $module->{'custom_id'} : '',
				_modulegroup_ => undef,
				_moduledescription_ => (defined ($module)) ? $module->{'descripcion'} : '',
				_modulestatus_ => undef,
				_moduletags_ => undef,
				_id_agent_ => (defined ($module)) ? $module->{'id_agente'} : '', 
				_id_alert_ => (defined ($alert->{'id_template_module'})) ? $alert->{'id_template_module'} : '',
				_interval_ => (defined ($module) && $module->{'module_interval'} != 0) ? $module->{'module_interval'} : (defined ($agent)) ? $agent->{'intervalo'} : '',
				_target_ip_ => (defined ($module)) ? $module->{'ip_target'} : '', 
				_target_port_ => (defined ($module)) ? $module->{'tcp_port'} : '', 
				_policy_ => undef,
				_plugin_parameters_ => (defined ($module)) ? $module->{'plugin_parameter'} : '',
				_email_tag_ => undef,
				_phone_tag_ => undef,
				_name_tag_ => undef,
				 );
	
	if ((defined ($extra_macros)) && (ref($extra_macros) eq "HASH")) {
		while ((my $macro, my $value) = each (%{$extra_macros})) {
			$macros{$macro} = $value;
		}
	}
	
	if (defined ($module)) {
		load_module_macros ($module->{'module_macros'}, \%macros);
	}
	
	# User defined alerts
	if ($action->{'internal'} == 0) {
		$macros{_field1_} = subst_alert_macros ($field1, \%macros, $pa_config, $dbh, $agent, $module);
		$macros{_field2_} = subst_alert_macros ($field2, \%macros, $pa_config, $dbh, $agent, $module);
		$macros{_field3_} = subst_alert_macros ($field3, \%macros, $pa_config, $dbh, $agent, $module);
		$macros{_field4_} = subst_alert_macros ($field4, \%macros, $pa_config, $dbh, $agent, $module);
		$macros{_field5_} = subst_alert_macros ($field5, \%macros, $pa_config, $dbh, $agent, $module);
		$macros{_field6_} = subst_alert_macros ($field6, \%macros, $pa_config, $dbh, $agent, $module);
		$macros{_field7_} = subst_alert_macros ($field7, \%macros, $pa_config, $dbh, $agent, $module);
		$macros{_field8_} = subst_alert_macros ($field8, \%macros, $pa_config, $dbh, $agent, $module);
		$macros{_field9_} = subst_alert_macros ($field9, \%macros, $pa_config, $dbh, $agent, $module);
		$macros{_field10_} = subst_alert_macros ($field10, \%macros, $pa_config, $dbh, $agent, $module);
		
		my @command_args = ();
		# divide command into words based on quotes and whitespaces
		foreach my $word (quotewords('\s+', 1, (decode_entities($action->{'command'})))) {
			push @command_args, subst_alert_macros($word, \%macros, $pa_config, $dbh, $agent, $module);
		}
		my $command = join(' ', @command_args);
		logger($pa_config, "Executing command '$command' for action '" . safe_output($action->{'name'}) . "' alert '". safe_output($alert->{'name'}) . "' agent '" . (defined ($agent) ? safe_output($agent->{'nombre'}) : 'N/A') . "'.", 8);
		
		eval {
			system ($command);
			logger($pa_config, "Command '$command' for action '" . safe_output($action->{'name'}) . "' alert '". safe_output($alert->{'name'}) . "' agent '" . (defined ($agent) ? safe_output($agent->{'nombre'}) : 'N/A') . "' returned with errorlevel " . ($? >> 8), 8);
		};
		
		if ($@){
			logger($pa_config, "Error $@ executing command '$command' for action '" . safe_output($action->{'name'}) . "' alert '". safe_output($alert->{'name'}) . "' agent '" . (defined ($agent) ? safe_output($agent->{'nombre'}) : 'N/A') ."'.", 8);
		}
	
	# Internal Audit
	} elsif ($clean_name eq "Internal Audit") {
		$field1 = subst_alert_macros ($field1, \%macros, $pa_config, $dbh, $agent, $module);
		pandora_audit ($pa_config, $field1, defined ($agent) ? safe_output($agent->{'nombre'}) : 'N/A', 'Alert (' . safe_output($alert->{'description'}) . ')', $dbh);
	
	# Email
	} elsif ($clean_name eq "eMail") {
		$field1 = subst_alert_macros ($field1, \%macros, $pa_config, $dbh, $agent, $module);
		$field2 = subst_alert_macros ($field2, \%macros, $pa_config, $dbh, $agent, $module);
		$field3 = subst_alert_macros ($field3, \%macros, $pa_config, $dbh, $agent, $module);
		if ($pa_config->{"mail_in_separate"} != 0){
			foreach my $address (split (',', $field1)) {
				# Remove blanks
				$address =~ s/ +//g;
				pandora_sendmail ($pa_config, $address, $field2, $field3);
			}
		}
		else {
			pandora_sendmail ($pa_config, $field1, $field2, $field3);
		}
	
	# Pandora FMS Event
	} elsif ($clean_name eq "Pandora FMS Event") {
		$field1 = subst_alert_macros ($field1, \%macros, $pa_config, $dbh, $agent, $module);
		$field3 = subst_alert_macros ($field3, \%macros, $pa_config, $dbh, $agent, $module);
		$field4 = subst_alert_macros ($field4, \%macros, $pa_config, $dbh, $agent, $module);
		$field6 = subst_alert_macros ($field6, \%macros, $pa_config, $dbh, $agent, $module);
		$field7 = subst_alert_macros ($field7, \%macros, $pa_config, $dbh, $agent, $module);
		$field8 = subst_alert_macros ($field8, \%macros, $pa_config, $dbh, $agent, $module);
		
		# Field 1 (event text)
		my $event_text = $field1;
		
		# Field 2 (event type)
		my $event_type = $field2;
		if ($event_type eq "") {
			$event_type = "alert_fired";
		}
		
		# Field 3 (source)
		my $source = $field3;
		
		# Field 4 (agent name)
		my $agent_name = $field4;
		if($agent_name eq "") {
			$agent_name = "_agent_";
		}
		$agent_name = subst_alert_macros ($agent_name, \%macros, $pa_config, $dbh, $agent, $module);
		my $fullagent = get_agent_from_name ($dbh, $agent_name);
		
		# Field 5 (priority)
		my $priority = $field5;
		if($priority eq '') {
			$priority = $alert->{'priority'};
		}
		
		# Field 6 (id extra);
		my $id_extra = $field6;
		
		# Field 7 (tags);
		my $tags = $field7;
		
		# Field 8 (comments);
		my $comment = $field8;
		
		pandora_event(
			$pa_config,
			$event_text,
			(defined ($agent) ? $agent->{'id_grupo'} : 0),
			(defined ($fullagent) ? $fullagent->{'id_agente'} : 0),
			$priority,
			(defined($alert) ? $alert->{'id'} : 0),
			(defined($alert) ? $alert->{'id_agent_module'} : 0),
			$event_type,
			0,
			$dbh,
			$source,
			'',
			$comment,
			$id_extra,
			$tags);
	# Validate event (field1: agent name; field2: module name)
	} elsif ($clean_name eq "Validate Event") {
		my $agent_id = -1;
		my $module_id = -1;
		if($field1 ne '') {
			$agent_id = get_agent_id ($dbh, $field1);
			if($field2 ne '' && $agent_id != -1) {
				$module_id = get_agent_module_id ($dbh, $field2, $agent_id);
				if($module_id != -1) {
					pandora_validate_event ($pa_config, $module_id, $dbh);
				}
			}
		}
	
	# Integria IMS Ticket
	} elsif ($clean_name eq "Integria IMS Ticket") {
		$field1 = subst_alert_macros ($field1, \%macros, $pa_config, $dbh, $agent, $module);
		$field3 = subst_alert_macros ($field3, \%macros, $pa_config, $dbh, $agent, $module);
		$field4 = subst_alert_macros ($field4, \%macros, $pa_config, $dbh, $agent, $module);
		$field6 = subst_alert_macros ($field6, \%macros, $pa_config, $dbh, $agent, $module);
		$field7 = subst_alert_macros ($field7, \%macros, $pa_config, $dbh, $agent, $module);

		# Field 1 (Integria IMS API path)
		my $api_path = $field1;
		
		# Field 2 (Integria IMS API pass)
		my $api_pass = $field2;
		
		# Field 3 (Integria IMS user)
		my $integria_user = $field3;
		
		# Field 4 (Ticket name)
		my $ticket_name = $field4;
		if ($ticket_name eq "") {
			$ticket_name = "Pandora FMS alert action created by API";
		}
		
		# Field 5 (Ticket group ID)
		my $ticket_group_id = $field5;
		if ($ticket_group_id eq '') {
			$ticket_group_id = 0;
		}
		
		# Field 6 (Ticket priority);
		my $ticket_priority = $field6;
		if ($ticket_priority eq '') {
			$ticket_priority = 0;
		}
		
		# Field 7 (Ticket description);
		my $ticket_description = $field7;

		pandora_create_integria_ticket($pa_config, $api_path, $api_pass, $integria_user, $ticket_name, $ticket_group_id, $ticket_priority, $ticket_description);

	# Unknown
	} else {
		logger($pa_config, "Unknown action '" . $action->{'name'} . "' for alert '". $alert->{'name'} . "' agent '" . (defined ($agent) ? $agent->{'nombre'} : 'N/A') . "'.", 3);
	}
	
	# Update action last execution date
	if (defined ($action->{'last_execution'}) && defined ($action->{'id_alert_template_module_actions'})) {
		db_do ($dbh, 'UPDATE talert_template_module_actions SET last_execution = ?
 WHERE id = ?', time (), $action->{'id_alert_template_module_actions'});
	}
}

##########################################################################
=head2 C<< pandora_access_update (I<$pa_config>, I<$agent_id>, I<$dbh>) >> 

Update agent access table.

=cut
##########################################################################
sub pandora_access_update ($$$) {
	my ($pa_config, $agent_id, $dbh) = @_;
	
	return if ($agent_id < 0);
	
	if ($pa_config->{"agentaccess"} == 0){
		return;
	}
	db_do ($dbh, "INSERT INTO tagent_access (id_agent, utimestamp) VALUES (?, ?)", $agent_id, time ());
}

##########################################################################
=head2 C<< pandora_process_module (I<$pa_config>, I<$data>, I<$agent>, I<$module>, I<$module_type>, I<$timestamp>, I<$utimestamp>, I<$server_id>, I<$dbh>) >> 

Process Pandora module.

=cut
##########################################################################
sub pandora_process_module ($$$$$$$$$;$) {
	my ($pa_config, $data_object, $agent, $module, $module_type,
		$timestamp, $utimestamp, $server_id, $dbh, $extra_macros) = @_;
	
	logger($pa_config,
		"Processing module '" . safe_output($module->{'nombre'}) .
		"' for agent " .
		(defined ($agent) && $agent ne '' ? "'" . safe_output($agent->{'nombre'}) . "'" : 'ID ' . $module->{'id_agente'}) . ".",
		10);
	
	# Get agent information
	if (! defined ($agent) || $agent eq '') {
		$agent = get_db_single_row ($dbh, 'SELECT * FROM tagente WHERE id_agente = ?', $module->{'id_agente'});
		if (! defined ($agent)) {
			logger($pa_config, "Agent ID " . $module->{'id_agente'} . " not found while processing module '" . safe_output($module->{'nombre'}) . "'.", 3);
			pandora_update_module_on_error ($pa_config, $module, $dbh);
			return;
		}
	}
	
	# Get module type
	if (! defined ($module_type) || $module_type eq '') {
		$module_type = get_db_value ($dbh, 'SELECT nombre FROM ttipo_modulo WHERE id_tipo = ?', $module->{'id_tipo_modulo'});
		if (! defined ($module_type)) {
			logger($pa_config, "Invalid module type ID " . $module->{'id_tipo_modulo'} . " module '" . $module->{'nombre'} . "' agent " . (defined ($agent) ? "'" . $agent->{'nombre'} . "'" : 'ID ' . $module->{'id_agente'}) . ".", 10);
			pandora_update_module_on_error ($pa_config, $module, $dbh);
			return;
		}
	}
	
	# Process data
	my $processed_data = process_data ($pa_config, $data_object, $agent, $module, $module_type, $utimestamp, $dbh);
	if (! defined ($processed_data)) {
		pandora_update_module_on_error ($pa_config, $module, $dbh);
		return;
	}
	
	$timestamp = strftime ("%Y-%m-%d %H:%M:%S", localtime($utimestamp)) if (! defined ($timestamp) || $timestamp eq '');
	
	# Export data
	export_module_data ($pa_config, $processed_data, $agent, $module, $module_type, $timestamp, $dbh);
	
	# Get previous status
	my $agent_status = get_db_single_row ($dbh, 'SELECT * FROM tagente_estado WHERE id_agente_modulo = ?', $module->{'id_agente_modulo'});
	if (! defined ($agent_status)) {
		logger($pa_config, "Status for agent '" . $agent->{'nombre'} . "' not found while processing module " . $module->{'nombre'} . ".", 3);
		pandora_update_module_on_error ($pa_config, $module, $dbh);
		return;
	}
	my $last_status = $agent_status->{'last_status'};
	my $status = $agent_status->{'estado'};
	my $status_changes = $agent_status->{'status_changes'};
	my $last_data_value = $agent_status->{'datos'};
	my $last_known_status = $agent_status->{'last_known_status'};
	my $last_error = defined ($module->{'last_error'}) ? $module->{'last_error'} : $agent_status->{'last_error'};
	my $ff_start_utimestamp = $agent_status->{'ff_start_utimestamp'};
	my $mark_for_update = 0;
	
	# Get new status
	my $new_status = get_module_status ($processed_data, $module, $module_type);
	
	# Calculate the current interval
	my $current_interval;
	if (defined ($module->{'cron_interval'}) && $module->{'cron_interval'} ne '' && $module->{'cron_interval'} ne '* * * * *') {
		$current_interval = cron_next_execution ($module->{'cron_interval'});
	}
	elsif ($module->{'module_interval'} == 0) {
		$current_interval = $agent->{'intervalo'};
	}
	else {
		$current_interval = $module->{'module_interval'};
	}

	#Update module status
	my $min_ff_event = $module->{'min_ff_event'};
	my $current_utimestamp = time ();
	my $ff_timeout = $module->{'ff_timeout'};

	if ($module->{'each_ff'}) {
		$min_ff_event = $module->{'min_ff_event_normal'} if ($new_status == 0);
		$min_ff_event = $module->{'min_ff_event_critical'} if ($new_status == 1);
		$min_ff_event = $module->{'min_ff_event_warning'} if ($new_status == 2);
	}
	
	if ($last_status == $new_status) {
		
		# Avoid overflows
		$status_changes = $min_ff_event if ($status_changes > $min_ff_event);
		
		$status_changes++;
		if ($module_type =~ m/async/ && $min_ff_event != 0 && $ff_timeout != 0 && ($utimestamp - $ff_start_utimestamp) > $ff_timeout) {
			$status_changes = 0;
			$ff_start_utimestamp = $utimestamp;
		}
	}
	else {
		$status_changes = 0;
		$ff_start_utimestamp = $utimestamp if ($module_type =~ m/async/);
	}
	
	# Active ff interval
	if ($module->{'module_ff_interval'} != 0 && $status_changes < $min_ff_event) {
		$current_interval = $module->{'module_ff_interval'};
	}
	
	# Change status
	if ($status_changes >= $min_ff_event && $status != $new_status) {
		generate_status_event ($pa_config, $processed_data, $agent, $module, $new_status, $status, $last_known_status, $dbh);
		$status = $new_status;
		$last_status = $new_status;

		# Update module status count.
		$mark_for_update = 1;
	}
	# Set not-init modules to normal status even if min_ff_event is not matched the first time they receive data.
	# if critical or warning status, just pass through here and wait the time min_ff_event will be matched.
	elsif ($status == 4) {
		generate_status_event ($pa_config, $processed_data, $agent, $module, 0, $status, $last_known_status, $dbh);
		$status = 0;
		$last_status = $new_status;

		# Update module status count.
		$mark_for_update = 1;
	}
	# If unknown modules receive data, restore status even if min_ff_event is not matched.
	elsif ($status == 3) {
		$last_status = $new_status; # Set last_status before forcing the module's new status to its last known status.
		$new_status = $last_known_status; # Set the module to its last known status.
		generate_status_event ($pa_config, $processed_data, $agent, $module, $new_status, $status, $last_known_status, $dbh);
		$status = $new_status;

		# Update module status count.
		$mark_for_update = 1;
	} else {
		$last_status = $new_status;
	}
		
	# tagente_estado.last_try defaults to NULL, should default to '1970-01-01 00:00:00'
	$agent_status->{'last_try'} = '1970-01-01 00:00:00' unless defined ($agent_status->{'last_try'});
	
	# Do we have to save module data?
	if ($agent_status->{'last_try'} !~ /(\d+)\-(\d+)\-(\d+) +(\d+):(\d+):(\d+)/) {
		logger($pa_config, "Invalid last try timestamp '" . $agent_status->{'last_try'} . "' for agent '" . $agent->{'nombre'} . "' not found while processing module '" . $module->{'nombre'} . "'.", 3);
		pandora_update_module_on_error ($pa_config, $module, $dbh);
		return;
	}
	
	my $last_try = ($1 == 0) ? 0 : timelocal($6, $5, $4, $3, $2 - 1, $1 - 1900);

	if (!defined($agent_status->{'datos'})){
		$agent_status->{'datos'} = "";
	}

	my $save = ($module->{'quiet'} == 0 && $module->{'history_data'} == 1 && ($agent_status->{'datos'} ne $processed_data || $last_try < ($utimestamp - 86400))) ? 1 : 0;
	
	db_do ($dbh, 'UPDATE tagente_estado
		SET datos = ?, estado = ?, last_status = ?, last_known_status = ?,
			status_changes = ?, utimestamp = ?, timestamp = ?,
			id_agente = ?, current_interval = ?, running_by = ?,
			last_execution_try = ?, last_try = ?, last_error = ?,
			ff_start_utimestamp = ?
		WHERE id_agente_modulo = ?', $processed_data, $status, $last_status, $last_status, $status_changes,
		$current_utimestamp, $timestamp, $module->{'id_agente'}, $current_interval, $server_id,
		$utimestamp, ($save == 1) ? $timestamp : $agent_status->{'last_try'}, $last_error, $ff_start_utimestamp, $module->{'id_agente_modulo'});
	
	# Save module data. Async and log4x modules are not compressed.
	if ($module_type =~ m/(async)|(log4x)/ || $save == 1) {
		save_module_data ($data_object, $module, $module_type, $utimestamp, $dbh);
	}

	# Generate alerts
	if (pandora_inhibit_alerts ($pa_config, $agent, $dbh, 0) == 0) {
		pandora_generate_alerts ($pa_config, $processed_data, $status, $agent, $module, $utimestamp, $dbh, $timestamp, $extra_macros, $last_data_value);
	}
	else {
		logger($pa_config, "Alerts inhibited for agent '" . $agent->{'nombre'} . "'.", 10);
	}

	# Update module status count
	if ($mark_for_update == 1) {
		pandora_mark_agent_for_module_update ($dbh, $agent->{'id_agente'});
	}
}

########################################################################
=head2 C<< pandora_planned_downtime_disabled_once_stop (I<$pa_config>, I<$dbh>) >> 

Stop the planned downtime, the once type. 

=cut
########################################################################
sub pandora_planned_downtime_disabled_once_stop($$) {
	my ($pa_config, $dbh) = @_;
	my $utimestamp = time();
	
	# Stop executed downtimes (enable agents and disable_agents_alerts)
	my @downtimes = get_db_rows($dbh, 'SELECT *
		FROM tplanned_downtime
		WHERE type_downtime != ' . $RDBMS_QUOTE_STRING. 'quiet' . $RDBMS_QUOTE_STRING. '
			AND type_execution = ' . $RDBMS_QUOTE_STRING. 'once' . $RDBMS_QUOTE_STRING. '
			AND executed = 1
			AND date_to <= ?', $utimestamp);
	
	foreach my $downtime (@downtimes) {
		
		logger($pa_config, "Ending planned downtime '" . $downtime->{'name'} . "'.", 10);
		
		db_do($dbh, 'UPDATE tplanned_downtime
			SET executed = 0
			WHERE id = ?', $downtime->{'id'});
		
		pandora_event ($pa_config,
			'(Created by ' . $downtime->{'id_user'} . ') Server ' . $pa_config->{'servername'} . ' stopped planned downtime: ' . $downtime->{'name'}, 0, 0, 1, 0, 0, 'system', 0, $dbh);
		
		pandora_planned_downtime_unset_disabled_elements($pa_config,
			$dbh, $downtime);
	}
}

########################################################################
=head2 C<< pandora_planned_downtime_disabled_once_start (I<$pa_config>, I<$dbh>) >> 

Start the planned downtime, the once type. 

=cut
########################################################################
sub pandora_planned_downtime_disabled_once_start($$) {
	my ($pa_config, $dbh) = @_;
	my $utimestamp = time();
	
	# Start pending downtimes (disable agents and disable_agents_alerts)
	my @downtimes = get_db_rows($dbh, 'SELECT *
		FROM tplanned_downtime
		WHERE type_downtime != ' . $RDBMS_QUOTE_STRING . 'quiet' . $RDBMS_QUOTE_STRING . '
			AND type_execution = ' . $RDBMS_QUOTE_STRING . 'once' . $RDBMS_QUOTE_STRING . '
			AND executed = 0 AND date_from <= ?
			AND date_to >= ?', $utimestamp, $utimestamp);
	
	foreach my $downtime (@downtimes) {
		if (!defined($downtime->{'description'})) {
			$downtime->{'description'} = "N/A";
		}
		
		if (!defined($downtime->{'name'})) {
			$downtime->{'name'} = "N/A";
		}
		
		logger($pa_config, "[PLANNED_DOWNTIME] " .
			"Starting planned downtime '" . $downtime->{'name'} . "'.", 10);
		
		logger($pa_config, "[PLANNED_DOWNTIME] " .
			"Starting planned downtime ID " . $downtime->{'id'} . ".", 10);
		
		db_do($dbh, 'UPDATE tplanned_downtime
			SET executed = 1
			WHERE id = ?', $downtime->{'id'});
		
		pandora_event ($pa_config,
			"(Created by " . $downtime->{'id_user'} . ") Server ".$pa_config->{'servername'}." started planned downtime: ".$downtime->{'name'}, 0, 0, 1, 0, 0, 'system', 0, $dbh);
		
		pandora_planned_downtime_set_disabled_elements($pa_config,
			$dbh, $downtime);
	}
}

########################################################################
=head2 C<< pandora_planned_downtime_set_disabled_elements (I<$pa_config>, I<$dbh>, <$id_downtime>) >> 

Start the planned downtime, the once type. 

=cut
########################################################################
sub pandora_planned_downtime_set_disabled_elements($$$) {
	my ($pa_config, $dbh, $downtime) = @_;
	
	my @downtime_agents = get_db_rows($dbh, 'SELECT *
		FROM tplanned_downtime_agents
		WHERE id_downtime = ' . $downtime->{'id'});
	
	foreach my $downtime_agent (@downtime_agents) {
		my $only_alerts = 0;
		
		if ($downtime->{'only_alerts'} == 0) {
			if ($downtime->{'type_downtime'} eq 'disable_agents_alerts') {
				$only_alerts = 1;
			}
		}
		
		if ($only_alerts == 0) {
			db_do ($dbh, 'UPDATE tagente
				SET disabled = 1
				WHERE id_agente = ?', $downtime_agent->{'id_agent'});
		}
		else {
			db_do ($dbh, 'UPDATE talert_template_modules
				SET disabled = 1
				WHERE id_agent_module IN (
					SELECT id_agente_modulo
					FROM tagente_modulo
					WHERE id_agente = ?)', $downtime_agent->{'id_agent'});
		}
	
	}
}

########################################################################
=head2 C<< pandora_planned_downtime_set_quiet_elements (I<$pa_config>, I<$dbh>, <$id_downtime>) >> 

Start the planned downtime, the once type. 

=cut
########################################################################
sub pandora_planned_downtime_unset_disabled_elements($$$) {
	my ($pa_config, $dbh, $downtime) = @_;
	
	my @downtime_agents = get_db_rows($dbh, 'SELECT *
		FROM tplanned_downtime_agents
		WHERE id_downtime = ' . $downtime->{'id'});
	
	foreach my $downtime_agent (@downtime_agents) {
		my $only_alerts = 0;
		
		if ($downtime->{'only_alerts'} == 0) {
			if ($downtime->{'type_downtime'} eq 'disable_agents_alerts') {
				$only_alerts = 1;
			}
		}
		
		if ($only_alerts == 0) {
			db_do ($dbh, 'UPDATE tagente
				SET disabled = 0
				WHERE id_agente = ?', $downtime_agent->{'id_agent'});
		}
		else {
			db_do ($dbh, 'UPDATE talert_template_modules
				SET disabled = 0
				WHERE id_agent_module IN (
					SELECT id_agente_modulo
					FROM tagente_modulo
					WHERE id_agente = ?)', $downtime_agent->{'id_agent'});
		}
	
	}
}

########################################################################
=head2 C<< pandora_planned_downtime_set_quiet_elements (I<$pa_config>, I<$dbh>, <$id_downtime>) >> 

Start the planned downtime, the once type. 

=cut
########################################################################
sub pandora_planned_downtime_set_quiet_elements($$$) {
	my ($pa_config, $dbh, $downtime_id) = @_;
	
	my @downtime_agents = get_db_rows($dbh, 'SELECT *
		FROM tplanned_downtime_agents
		WHERE id_downtime = ' . $downtime_id);
	
	foreach my $downtime_agent (@downtime_agents) {
		if ($downtime_agent->{'all_modules'}) {
			db_do ($dbh, 'UPDATE tagente
				SET quiet = 1
				WHERE id_agente = ?', $downtime_agent->{'id_agent'});
		}
		else {
			my @downtime_modules = get_db_rows($dbh, 'SELECT *
				FROM tplanned_downtime_modules
				WHERE id_agent = ' . $downtime_agent->{'id_agent'} . '
					AND id_downtime = ' . $downtime_id);
			
			foreach my $downtime_module (@downtime_modules) {
				db_do ($dbh, 'UPDATE tagente_modulo
					SET quiet = 1
					WHERE id_agente_modulo = ?',
					$downtime_module->{'id_agent_module'});
			}
		}
	}
}

########################################################################
=head2 C<< pandora_planned_downtime_unset_quiet_elements (I<$pa_config>, I<$dbh>, <$id_downtime>) >> 

Start the planned downtime, the once type. 

=cut
########################################################################
sub pandora_planned_downtime_unset_quiet_elements($$$) {
	my ($pa_config, $dbh, $downtime_id) = @_;
	
	my @downtime_agents = get_db_rows($dbh, 'SELECT *
		FROM tplanned_downtime_agents
		WHERE id_downtime = ' . $downtime_id);
	
	foreach my $downtime_agent (@downtime_agents) {
		if ($downtime_agent->{'all_modules'}) {
			db_do ($dbh, 'UPDATE tagente
				SET quiet = 0
				WHERE id_agente = ?', $downtime_agent->{'id_agent'});
		}
		else {
			my @downtime_modules = get_db_rows($dbh, 'SELECT *
				FROM tplanned_downtime_modules
				WHERE id_agent = ' . $downtime_agent->{'id_agent'} . '
					AND id_downtime = ' . $downtime_id);
			
			foreach my $downtime_module (@downtime_modules) {
				db_do ($dbh, 'UPDATE tagente_modulo
					SET quiet = 0
					WHERE id_agente_modulo = ?',
					$downtime_module->{'id_agent_module'});
			}
		}
	}
}

########################################################################
=head2 C<< pandora_planned_downtime_quiet_once_stop (I<$pa_config>, I<$dbh>) >> 

Start the planned downtime, the once type. 

=cut
########################################################################
sub pandora_planned_downtime_quiet_once_stop($$) {
	my ($pa_config, $dbh) = @_;
	my $utimestamp = time();
	
	# Stop pending downtimes
	my @downtimes = get_db_rows($dbh, 'SELECT *
		FROM tplanned_downtime
		WHERE type_downtime = ' . $RDBMS_QUOTE_STRING . 'quiet' . $RDBMS_QUOTE_STRING . '
			AND type_execution = ' . $RDBMS_QUOTE_STRING. 'once' . $RDBMS_QUOTE_STRING . '
			AND executed = 1 AND date_to <= ?', $utimestamp);
	
	foreach my $downtime (@downtimes) {
		if (!defined($downtime->{'description'})) {
			$downtime->{'description'} = "N/A";
		}
		
		if (!defined($downtime->{'name'})) {
			$downtime->{'name'} = "N/A";
		}
		
		logger($pa_config, "[PLANNED_DOWNTIME] " .
			"Starting planned downtime '" . $downtime->{'name'} . "'.", 10);
		
		db_do($dbh, 'UPDATE tplanned_downtime
			SET executed = 0
			WHERE id = ?', $downtime->{'id'});
		pandora_event ($pa_config,
			"(Created by " . $downtime->{'id_user'} . ") Server ".$pa_config->{'servername'}." stopped planned downtime: ".$downtime->{'name'}, 0, 0, 1, 0, 0, 'system', 0, $dbh);
		
		pandora_planned_downtime_unset_quiet_elements($pa_config,
			$dbh, $downtime->{'id'});
	}
}

########################################################################
=head2 C<< pandora_planned_downtime_quiet_once_start (I<$pa_config>, I<$dbh>) >> 

Start the planned downtime, the once type. 

=cut
########################################################################
sub pandora_planned_downtime_quiet_once_start($$) {
	my ($pa_config, $dbh) = @_;
	my $utimestamp = time();
	
	# Start pending downtimes
	my @downtimes = get_db_rows($dbh, 'SELECT *
		FROM tplanned_downtime
		WHERE type_downtime = ' . $RDBMS_QUOTE_STRING . 'quiet' . $RDBMS_QUOTE_STRING . '
			AND type_execution = ' . $RDBMS_QUOTE_STRING . 'once' . $RDBMS_QUOTE_STRING . '
			AND executed = 0 AND date_from <= ?
			AND date_to >= ?', $utimestamp, $utimestamp);
	
	foreach my $downtime (@downtimes) {
		if (!defined($downtime->{'description'})) {
			$downtime->{'description'} = "N/A";
		}
		
		if (!defined($downtime->{'name'})) {
			$downtime->{'name'} = "N/A";
		}
		
		logger($pa_config, "[PLANNED_DOWNTIME] " .
			"Starting planned downtime '" . $downtime->{'name'} . "'.", 10);
		
		db_do($dbh, 'UPDATE tplanned_downtime
			SET executed = 1
			WHERE id = ?', $downtime->{'id'});
		pandora_event ($pa_config,
			"(Created by " . $downtime->{'id_user'} . ") Server ".$pa_config->{'servername'}." started planned downtime: ".$downtime->{'name'}, 0, 0, 1, 0, 0, 'system', 0, $dbh);
		
		pandora_planned_downtime_set_quiet_elements($pa_config,
			$dbh, $downtime->{'id'});
	}
}

########################################################################
=head2 C<< pandora_planned_downtime_monthly_start (I<$pa_config>, I<$dbh>) >> 

Start the planned downtime, the monthly type. 

=cut
########################################################################
sub pandora_planned_downtime_monthly_start($$) {
	my ($pa_config, $dbh) = @_;
	
	my @var_localtime = localtime(time);
	my $year = $var_localtime[5]  + 1900;
	my $month = $var_localtime[4];
	
	my $number_day_month = $var_localtime[3];
	
	my $number_last_day_month = month_have_days($month, $year);
	
	my $time = sprintf("%02d:%02d:%02d", $var_localtime[2], $var_localtime[1], $var_localtime[0]);
	
	# Start pending downtimes
	my @downtimes = get_db_rows($dbh, 'SELECT *
		FROM tplanned_downtime
		WHERE type_periodicity = ' . $RDBMS_QUOTE_STRING . 'monthly' . $RDBMS_QUOTE_STRING . '
			AND executed = 0
			AND ((periodically_day_from = ? AND periodically_time_from <= ?) OR (periodically_day_from < ?))
			AND ((periodically_day_to = ? AND periodically_time_to >= ?) OR (periodically_day_to > ?))',
			$number_day_month, $time, $number_day_month,
			$number_day_month, $time, $number_day_month);
	
	foreach my $downtime (@downtimes) {	
		if (!defined($downtime->{'description'})) {
			$downtime->{'description'} = "N/A";
		}
		
		if (!defined($downtime->{'name'})) {
			$downtime->{'name'} = "N/A";
		}
		
		logger($pa_config, "Starting planned monthly downtime '" . $downtime->{'name'} . "'.", 10);
		
		db_do($dbh, 'UPDATE tplanned_downtime
					SET executed = 1
					WHERE id = ?', $downtime->{'id'});
		pandora_event ($pa_config,
			"Server ".$pa_config->{'servername'}." started planned downtime: ".$downtime->{'name'}, 0, 0, 1, 0, 0, 'system', 0, $dbh);
		
		
		if ($downtime->{'type_downtime'} eq "quiet") {
			pandora_planned_downtime_set_quiet_elements($pa_config, $dbh, $downtime->{'id'});
		}
		elsif (($downtime->{'type_downtime'} eq "disable_agents")
			|| ($downtime->{'type_downtime'} eq "disable_agents_alerts")) {
				
			pandora_planned_downtime_set_disabled_elements($pa_config, $dbh, $downtime);
		}
	}
}


########################################################################
=head2 C<< pandora_planned_downtime_monthly_stop (I<$pa_config>, I<$dbh>) >> 

Start the planned downtime, the montly type. 

=cut
########################################################################
sub pandora_planned_downtime_monthly_stop($$) {
	my ($pa_config, $dbh) = @_;
	
	my @var_localtime = localtime(time);
	my $year = $var_localtime[5]  + 1900;
	my $month = $var_localtime[4];
	
	my $number_day_month = $var_localtime[3];
	
	my $number_last_day_month = month_have_days($month, $year);
	
	my $time = sprintf("%02d:%02d:%02d", $var_localtime[2], $var_localtime[1], $var_localtime[0]);
	
	#With this stop the planned downtime for 31 (or 30) day in months
	#  with less days.
	#For to avoid the problems with february
	if (($number_last_day_month == 28) &&
		($number_day_month >= 28)) {
		$number_day_month = 31;
	}
	
	#For to avoid the problems with months with 30 days
	if (($number_last_day_month == 30) &&
		($number_day_month >= 30)) {
		$number_day_month = 31;
	}
	
	# Start pending downtimes
	my @downtimes = get_db_rows($dbh, 'SELECT *
		FROM tplanned_downtime
		WHERE type_periodicity = ' . $RDBMS_QUOTE_STRING . 'monthly' . $RDBMS_QUOTE_STRING . '
			AND executed = 1
			AND type_execution <> ' . $RDBMS_QUOTE_STRING . 'once' . $RDBMS_QUOTE_STRING . '
			AND (((periodically_day_from = ? AND periodically_time_from > ?) OR (periodically_day_from > ?))
				OR ((periodically_day_to = ? AND periodically_time_to < ?) OR (periodically_day_to < ?)))',
			$number_day_month, $time, $number_day_month,
			$number_day_month, $time, $number_day_month);
	
	foreach my $downtime (@downtimes) {
		if (!defined($downtime->{'description'})) {
			$downtime->{'description'} = "N/A";
		}
		
		if (!defined($downtime->{'name'})) {
			$downtime->{'name'} = "N/A";
		}
		
		logger($pa_config, "Stopping planned monthly downtime '" . $downtime->{'name'} . "'.", 10);
		
		db_do($dbh, 'UPDATE tplanned_downtime
					SET executed = 0
					WHERE id = ?', $downtime->{'id'});
		pandora_event ($pa_config,
			"Server ".$pa_config->{'servername'}." stopped planned downtime: ".$downtime->{'name'}, 0, 0, 1, 0, 0, 'system', 0, $dbh);
		
		if ($downtime->{'type_downtime'} eq "quiet") {
			pandora_planned_downtime_unset_quiet_elements($pa_config,
				$dbh, $downtime->{'id'});
		}
		elsif (($downtime->{'type_downtime'} eq "disable_agents")
			|| ($downtime->{'type_downtime'} eq "disable_agents_alerts")) {
				
			pandora_planned_downtime_unset_disabled_elements($pa_config,
				$dbh, $downtime);
		}
	}
}

########################################################################
=head2 C<< pandora_planned_downtime_weekly_start (I<$pa_config>, I<$dbh>) >> 

Start the planned downtime, the montly type. 

=cut
########################################################################
sub pandora_planned_downtime_weekly_start($$) {
	my ($pa_config, $dbh) = @_;
	
	my @var_localtime = localtime(time);
	
	my $number_day_week = $var_localtime[6];
	
	my $time = sprintf("%02d:%02d:%02d", $var_localtime[2], $var_localtime[1], $var_localtime[0]);
	
	my $found = 0;
	
	# Start pending downtimes
	my @downtimes = get_db_rows($dbh, 'SELECT *
		FROM tplanned_downtime
		WHERE type_periodicity = ' . $RDBMS_QUOTE_STRING . 'weekly' . $RDBMS_QUOTE_STRING . '
			AND executed = 0');
	
	foreach my $downtime (@downtimes) {
		my $across_date = $downtime->{'periodically_time_from'} gt $downtime->{'periodically_time_to'} ? 1 : 0 ;
		$found = 0;
		
		$number_day_week = $var_localtime[6];
		if ($across_date && ($time lt $downtime->{'periodically_time_to'})) {
			$number_day_week--;
			$number_day_week = 6 if ($number_day_week == -1);
		}
		
		if (($number_day_week == 1) &&
			($downtime->{'monday'})) {
				$found = 1;
		}
		if (($number_day_week == 2) &&
			($downtime->{'tuesday'})) {
				$found = 1;
		}
		if (($number_day_week == 3) &&
			($downtime->{'wednesday'})) {
				$found = 1;
		}
		if (($number_day_week == 4) &&
			($downtime->{'thursday'})) {
				$found = 1;
		}
		if (($number_day_week == 5) &&
			($downtime->{'friday'})) {
				$found = 1;
		}
		if (($number_day_week == 6) &&
			($downtime->{'saturday'})) {
				$found = 1;
		}
		if (($number_day_week == 0) &&
			($downtime->{'sunday'})) {
				$found = 1;
		}
		
		my $start_downtime = 0;
		if ($found) {
			$start_downtime = 1 if (($across_date == 0)
				&& ((($time gt $downtime->{'periodically_time_from'})
				|| ($time eq $downtime->{'periodically_time_from'}))
				&& (($time lt $downtime->{'periodically_time_to'})
				|| ($time eq $downtime->{'periodically_time_to'}))));
				
			$start_downtime = 1 if (($across_date == 1)
				&& ((($time gt $downtime->{'periodically_time_from'})
				|| ($time eq $downtime->{'periodically_time_from'}))
				|| (($time lt $downtime->{'periodically_time_to'})
				|| ($time eq $downtime->{'periodically_time_to'}))));
		}

		if ($start_downtime) {
			if (!defined($downtime->{'description'})) {
				$downtime->{'description'} = "N/A";
			}

			if (!defined($downtime->{'name'})) {
				$downtime->{'name'} = "N/A";
			}
				
			logger($pa_config, "Starting planned weekly downtime '" . $downtime->{'name'} . "'.", 10);

			db_do($dbh, 'UPDATE tplanned_downtime
				SET executed = 1
				WHERE id = ?', $downtime->{'id'});
			pandora_event ($pa_config,
				"Server ".$pa_config->{'servername'}." started planned downtime: ".$downtime->{'name'}, 0, 0, 1, 0, 0, 'system', 0, $dbh);
				
			if ($downtime->{'type_downtime'} eq "quiet") {
				pandora_planned_downtime_set_quiet_elements($pa_config,
				$dbh, $downtime->{'id'});
			}
			elsif (($downtime->{'type_downtime'} eq "disable_agents")
				|| ($downtime->{'type_downtime'} eq "disable_agents_alerts")) {
				pandora_planned_downtime_set_disabled_elements($pa_config,
				$dbh, $downtime);
			}
		}
	}
}

########################################################################
=head2 C<< pandora_planned_downtime_weekly_stop (I<$pa_config>, I<$dbh>) >> 

Stop the planned downtime, the montly type. 

=cut
########################################################################
sub pandora_planned_downtime_weekly_stop($$) {
	my ($pa_config, $dbh) = @_;
	
	my @var_localtime = localtime(time);
	
	my $number_day_week = $var_localtime[6];
	
	my $time = sprintf("%02d:%02d:%02d", $var_localtime[2], $var_localtime[1], $var_localtime[0]);
	
	my $found = 0;
	my $stop_downtime = 0;
	
	# Start pending downtimes
	my @downtimes = get_db_rows($dbh, 'SELECT *
		FROM tplanned_downtime
		WHERE type_periodicity = ' . $RDBMS_QUOTE_STRING . 'weekly' . $RDBMS_QUOTE_STRING . '
			AND type_execution <> ' . $RDBMS_QUOTE_STRING . 'once' . $RDBMS_QUOTE_STRING . '
			AND executed = 1');
	
	foreach my $downtime (@downtimes) {
		my $across_date = $downtime->{'periodically_time_from'} gt $downtime->{'periodically_time_to'} ? 1 : 0;

		$found = 0;
		$number_day_week = $var_localtime[6];
		if ($across_date && ($time lt $downtime->{'periodically_time_from'})) {
			$number_day_week--;
			$number_day_week = 6 if ($number_day_week == -1);
		}

		if (($number_day_week == 1) &&
			($downtime->{'monday'})) {
				$found = 1;
		}
		if (($number_day_week == 2) &&
			($downtime->{'tuesday'})) {
				$found = 1;
		}
		if (($number_day_week == 3) &&
			($downtime->{'wednesday'})) {
				$found = 1;
		}
		if (($number_day_week == 4) &&
			($downtime->{'thursday'})) {
				$found = 1;
		}
		if (($number_day_week == 5) &&
			($downtime->{'friday'})) {
				$found = 1;
		}
		if (($number_day_week == 6) &&
			($downtime->{'saturday'})) {
				$found = 1;
		}
		if (($number_day_week == 0) &&
			($downtime->{'sunday'})) {
				$found = 1;
		}
		
		$stop_downtime = 0;
		if ($found) {
			$stop_downtime = 1 if (($across_date == 0)
				&& ((($time lt $downtime->{'periodically_time_from'})
				|| ($time eq $downtime->{'periodically_time_from'}))
				|| (($time gt $downtime->{'periodically_time_to'})
				|| ($time eq $downtime->{'periodically_time_to'}))));

			$stop_downtime = 1 if (($across_date == 1)
				&& ((($time lt $downtime->{'periodically_time_from'})
				|| ($time eq $downtime->{'periodically_time_from'}))
				&& (($time gt $downtime->{'periodically_time_to'})
				|| ($time eq $downtime->{'periodically_time_to'}))));

		}
		else {
			$stop_downtime = 1;
		}

		if ($stop_downtime) {
			if (!defined($downtime->{'description'})) {
				$downtime->{'description'} = "N/A";
			}
			
			if (!defined($downtime->{'name'})) {
				$downtime->{'name'} = "N/A";
			}
			
			logger($pa_config, "Stopping planned weekly downtime '" . $downtime->{'name'} . "'.", 10);
			
			db_do($dbh, 'UPDATE tplanned_downtime
				SET executed = 0
				WHERE id = ?', $downtime->{'id'});
			pandora_event ($pa_config,
				"Server ".$pa_config->{'servername'}." stopped planned downtime: ".$downtime->{'name'}, 0, 0, 1, 0, 0, 'system', 0, $dbh);
			
			if ($downtime->{'type_downtime'} eq "quiet") {
				pandora_planned_downtime_unset_quiet_elements($pa_config,
					$dbh, $downtime->{'id'});
			}
			elsif (($downtime->{'type_downtime'} eq "disable_agents")
				|| ($downtime->{'type_downtime'} eq "disable_agents_alerts")) {
					pandora_planned_downtime_unset_disabled_elements($pa_config,
						$dbh, $downtime);
			}
		}
	}
}

########################################################################
=head2 C<< pandora_planned_downtime (I<$pa_config>, I<$dbh>) >> 

Update planned downtimes.

=cut
########################################################################
sub pandora_planned_downtime ($$) {
	my ($pa_config, $dbh) = @_;
	
	pandora_planned_downtime_disabled_once_stop($pa_config, $dbh);
	pandora_planned_downtime_disabled_once_start($pa_config, $dbh);
	
	pandora_planned_downtime_quiet_once_stop($pa_config, $dbh);
	pandora_planned_downtime_quiet_once_start($pa_config, $dbh);
	
	pandora_planned_downtime_monthly_stop($pa_config, $dbh);
	pandora_planned_downtime_monthly_start($pa_config, $dbh);
	
	pandora_planned_downtime_weekly_stop($pa_config, $dbh);
	pandora_planned_downtime_weekly_start($pa_config, $dbh);
}

########################################################################
=head2 C<< pandora_reset_server (I<$pa_config>, I<$dbh>) >> 

Reset the status of all server types for the current server.

=cut
########################################################################
sub pandora_reset_server ($$) {
	my ($pa_config, $dbh) = @_;
	
	db_do ($dbh, 'UPDATE tserver
		SET status = 0, threads = 0, queued_modules = 0
		WHERE name = ?', $pa_config->{'servername'});
}

##########################################################################
=head2 C<< pandora_update_server (I<$pa_config>, I<$dbh>, I<$server_name>, I<$server_id>, I<$status>, I<$server_type>, I<$num_threads>, I<$queue_size>) >> 

Update server status: 
 0 dataserver
 1 network server
 2 snmp console, 
 3 recon
 4 plugin
 5 prediction
 6 wmi.

=cut
##########################################################################
sub pandora_update_server ($$$$$$;$$) {
	my ($pa_config, $dbh, $server_name, $server_id, $status,
		$server_type, $num_threads, $queue_size) = @_;
	
	$num_threads = 0 unless defined ($num_threads);
	$queue_size = 0 unless defined ($queue_size);

	my $timestamp = strftime ("%Y-%m-%d %H:%M:%S", localtime());
	my $version = $pa_config->{'version'} . ' (P) ' . $pa_config->{'build'};
	
	# First run
	if ($server_id == 0) { 
		
		# Create an entry in tserver if needed
		my $server = get_db_single_row ($dbh, 'SELECT id_server FROM tserver WHERE name = ? AND server_type = ?', $server_name, $server_type);
		if (! defined ($server)) {
			$server_id = db_insert ($dbh, 'id_server', 'INSERT INTO tserver (name, server_type, description, version, threads, queued_modules)
						VALUES (?, ?, ?, ?, ?, ?)', $server_name, $server_type,
						'Autocreated at startup', $version, $num_threads, $queue_size);
		
			$server = get_db_single_row ($dbh, 'SELECT status FROM tserver WHERE id_server = ?', $server_id);
			if (! defined ($server)) {
				logger($pa_config, "Server '" . $pa_config->{'servername'} . "' not found.", 3);
				return;
			}
		}
		
		db_do ($dbh, 'UPDATE tserver SET status = ?, keepalive = ?, master = ?, laststart = ?, version = ?, threads = ?, queued_modules = ?
				WHERE id_server = ?',
				1, $timestamp, $pa_config->{'pandora_master'}, $timestamp, $version, $num_threads, $queue_size, $server_id);
		return;
	}
	
	db_do ($dbh, 'UPDATE tserver SET status = ?, keepalive = ?, master = ?, version = ?, threads = ?, queued_modules = ?
			WHERE id_server = ?', $status, $timestamp, $pa_config->{'pandora_master'}, $version, $num_threads, $queue_size, $server_id);
}

##########################################################################
=head2 C<< pandora_update_agent (I<$pa_config>, I<$agent_timestamp>, I<$agent_id>, I<$os_version>, I<$agent_version>, I<$agent_interval>, I<$dbh>, [I<$timezone_offset>], [I<$longitude>], [I<$latitude>], [I<$altitude>], [I<$position_description>], [I<$parent_agent_name>]) >>

Update last contact, timezone fields in B<tagente> and current position (this
can affect B<tgis_data_status> and B<tgis_data_history>). If the I<$parent_agent_id> is 
defined also the parent is updated.

=cut
##########################################################################
sub pandora_update_agent ($$$$$$$;$$) {
	my ($pa_config, $agent_timestamp, $agent_id, $os_version,
		$agent_version, $agent_interval, $dbh, $timezone_offset,
		$parent_agent_id) = @_;
	
	# No access update for data without interval.
	# Single modules from network server, for example. This could be very Heavy for Pandora FMS
	if ($agent_interval != -1){
		pandora_access_update ($pa_config, $agent_id, $dbh);
	} else {
		
		# Do not update the agent interval
		$agent_interval = undef;
	}
	
	# Update tagente
	my $timestamp = strftime ("%Y-%m-%d %H:%M:%S", localtime());
	my ($set, $values) = db_update_get_values ({'agent_version' => $agent_version,
	                                         'intervalo' => $agent_interval,
	                                         'ultimo_contacto_remoto' => $agent_timestamp,
	                                         'ultimo_contacto' => $timestamp,
	                                         'os_version' => $os_version,
	                                         'timezone_offset' => $timezone_offset,
	                                         'id_parent' => $parent_agent_id,
	                                        });
	
	db_do ($dbh, "UPDATE tagente SET $set WHERE id_agente = ?", @{$values}, $agent_id);
}

##########################################################################
=head2 C<< pandora_update_gis_data (I<$pa_config>, I<$dbh>, I<$agent_id>, I<$longitude>, I<$latitude>, I<$altitude>) >>

Update agent GIS information.

=cut
##########################################################################
sub pandora_update_gis_data ($$$$$$$$$) {
	my ($pa_config, $dbh, $agent_id, $agent_name, $longitude, $latitude, $altitude, $position_description, $timestamp) = @_;

	# Check for valid longitude and latitude
	if (!defined($longitude) || $longitude !~ /[-+]?[0-9,11,12]/ ||
	    !defined($latitude) || $latitude !~ /[-+]?[0-9,11,12]/) {
		return;
	}

	# Altitude is optional
	if (!defined($altitude) || $altitude !~ /[-+]?[0-9,11,12]/) {
		$altitude = '';
	}

	logger($pa_config, "Updating GIS data for agent $agent_name (long: $longitude lat: $latitude alt: $altitude)", 10);
	
	# Get position description
	if ((!defined($position_description))) {

		# This code gets description (Reverse Geocoding) from a current GPS coordinates using Google maps API
		# This requires a connection to internet and could be very slow and have a huge impact in performance.
		# Other methods for reverse geocoding are OpenStreetmaps, in nternet or in a local server

		if ($pa_config->{'google_maps_description'}){
			my $content = get ('http://maps.google.com/maps/geo?q='.$latitude.','.$longitude.'&output=csv&sensor=false');
			my @address = split (/\"/,$content);
			$position_description = $address[1];
		}
		elsif ($pa_config->{'openstreetmaps_description'}){
			# Sample Query: http://nominatim.openstreetmap.org/reverse?format=csv&lat=40.43197&lon=-3.6993818&zoom=18&addressdetails=1&email=info@pandorafms.org
			# Email address is sent by courtesy to OpenStreetmaps people. 
			# I read the API :-), thanks guys for your work.
			# Change here URL to make request to a local openstreetmap server
			my $content = get ('http://nominatim.openstreetmap.org/reverse?format=csv&lat='.$latitude.'&lon='.$longitude.'&zoom=18&addressdetails=1&email=info@pandorafms.org');

			if ((defined($content)) && ($content ne "")){ 
			
				# Yep, I need to parse the XML output.
				my $xs1 = XML::Simple->new();
				my $doc = $xs1->XMLin($content);
				$position_description = safe_input ($doc->{result}{content});
            } else {
				$position_description = "";
            }

		}

        if (!defined($position_description)){
            $position_description = "";
        }

		logger($pa_config, "Getting GIS Data=longitude=$longitude latitude=$latitude altitude=$altitude position_description=$position_description", 10);
	}
	
	# Get the last position to see if it has moved.
	my $last_agent_position= get_db_single_row ($dbh, 'SELECT * FROM tgis_data_status WHERE tagente_id_agente = ?', $agent_id);
	if(defined($last_agent_position)) {
			
		logger($pa_config, "Old Agent data: current_longitude=". $last_agent_position->{'current_longitude'}. " current_latitude=".$last_agent_position->{'current_latitude'}. " current_altitude=". $last_agent_position->{'current_altitude'}. " ID: $agent_id ", 10);
			
		# If the agent has moved outside the range stablised as location error
		if (distance_moved($pa_config, $last_agent_position->{'stored_longitude'}, $last_agent_position->{'stored_latitude'}, $last_agent_position->{'stored_altitude'}, $longitude, $latitude, $altitude) > $pa_config->{'location_error'}) {

			#Archive the old position and save new one as status
			archive_agent_position($pa_config, $last_agent_position->{'start_timestamp'}, $timestamp,$last_agent_position->{'stored_longitude'}, $last_agent_position->{'stored_latitude'}, $last_agent_position->{'stored_altitude'},$last_agent_position->{'description'}, $last_agent_position->{'number_of_packages'},$agent_id, $dbh);
				
			$altitude = 0 if (!defined($altitude));

			# Save the agent position in the tgis_data_status table
			update_agent_position($pa_config, $longitude, $latitude, $altitude, $agent_id, $dbh, $longitude, $latitude, $altitude, $timestamp, $position_description);
		}
		# The agent has not moved enougth so just update the status table
		else { 
			update_agent_position ($pa_config, $longitude, $latitude, $altitude, $agent_id, $dbh);
		}
	}
	else {
		logger($pa_config, "There was not previous positional data, storing first positioal status", 10);
		save_agent_position($pa_config, $longitude, $latitude, $altitude, $agent_id, $dbh, $timestamp, $position_description);
	}
}

##########################################################################
=head2 C<< pandora_create_template_module(I<$pa_config>, I<$dbh>, I<$id_agent_module>, I<$id_alert_template>, I<$id_policy_alerts>, I<$disabled>, I<$standby>) >>

Create a template module.

=cut
########################################################################
sub pandora_create_template_module ($$$$;$$$) {
	my ($pa_config, $dbh, $id_agent_module, $id_alert_template, $id_policy_alerts, $disabled, $standby) = @_;
	
	$id_policy_alerts = 0 unless defined $id_policy_alerts;
	$disabled = 0 unless defined $disabled;
	$standby = 0 unless defined $standby;
	
	my $module_name = get_module_name($dbh, $id_agent_module);
	
	return db_insert ($dbh,
		'id',
		"INSERT INTO talert_template_modules(
			" . $RDBMS_QUOTE . "id_agent_module" . $RDBMS_QUOTE . ",
			" . $RDBMS_QUOTE . "id_alert_template" . $RDBMS_QUOTE . ",
			" . $RDBMS_QUOTE . "id_policy_alerts" . $RDBMS_QUOTE . ",
			" . $RDBMS_QUOTE . "disabled" . $RDBMS_QUOTE . ",
			" . $RDBMS_QUOTE . "standby" . $RDBMS_QUOTE . ",
			" . $RDBMS_QUOTE . "last_reference" . $RDBMS_QUOTE . ")
		VALUES (?, ?, ?, ?, ?, ?)",
		$id_agent_module, $id_alert_template, $id_policy_alerts, $disabled, $standby, time);
}

########################################################################
=head2 C<< pandora_update_template_module(I<$pa_config>, I<$dbh>, I<$id_alert>, I<$id_policy_alerts>, I<$disabled>, I<$standby>) >>

Update a template module.

=cut
########################################################################

sub pandora_update_template_module ($$$;$$$) {
	my ($pa_config, $dbh, $id_alert, $id_policy_alerts, $disabled, $standby) = @_;
	
	$id_policy_alerts = 0 unless defined $id_policy_alerts;
	$disabled = 0 unless defined $disabled;
	$standby = 0 unless defined $standby;
	
	db_do ($dbh,
		"UPDATE talert_template_modules
		SET " . $RDBMS_QUOTE . "id_policy_alerts" . $RDBMS_QUOTE . " = ?,
			" . $RDBMS_QUOTE . "disabled" . $RDBMS_QUOTE . " =  ?,
			" . $RDBMS_QUOTE . "standby" . $RDBMS_QUOTE . " = ?
		WHERE id = ?",
		$id_policy_alerts, $disabled, $standby, $id_alert);
}

########################################################################
=head2 C<< pandora_create_template_module_action(I<$pa_config>, I<$parameters>, I<$dbh>) >>

Create a template action.

=cut
########################################################################
sub pandora_create_template_module_action ($$$) {
	my ($pa_config, $parameters, $dbh) = @_;
	
	logger($pa_config, "Creating module alert action to alert '$parameters->{'id_alert_template_module'}'.", 10);
	
	my $action_id = db_process_insert($dbh, 'id', 'talert_template_module_actions', $parameters);
	
	return $action_id;
}

########################################################################
=head2 C<< pandora_delete_all_template_module_actions(I<$dbh>, I<$template_module_id>) >>

Delete all actions of policy template module.

=cut
########################################################################
sub pandora_delete_all_template_module_actions ($$) {
	my ($dbh, $template_module_id) = @_;
	
	return db_do ($dbh, 'DELETE FROM talert_template_module_actions WHERE id_alert_template_module = ?', $template_module_id);
}

########################################################################
=head2 C<< pandora_update_agent_address(I<$pa_config>, I<$agent_id>, I<$address>, I<$dbh>) >>

Update the address of an agent.

=cut
########################################################################
sub pandora_update_agent_address ($$$$$) {
	my ($pa_config, $agent_id, $agent_name, $address, $dbh) = @_;
	
	logger($pa_config, 'Updating address for agent ' . $agent_name . ' (' . $address . ')', 10);
	db_do ($dbh, 'UPDATE tagente SET direccion = ? WHERE id_agente = ?', $address, $agent_id);
}

##########################################################################
=head2 C<< pandora_module_keep_alive (I<$pa_config>, I<$id_agent>, I<$agent_name>, I<$server_id>, I<$dbh>) >> 

Updates the keep_alive module for the given agent.

=cut
##########################################################################
sub pandora_module_keep_alive ($$$$$) {
	my ($pa_config, $id_agent, $agent_name, $server_id, $dbh) = @_;
	
	logger($pa_config, "Updating keep_alive module for agent '" . safe_output($agent_name) . "'.", 10);
	
	# Update keepalive module 
	my $module = get_db_single_row ($dbh, 'SELECT * FROM tagente_modulo WHERE id_agente = ? AND delete_pending = 0 AND id_tipo_modulo = 100', $id_agent);
	if (defined ($module)) {
		my %data = ('data' => 1);
		pandora_process_module ($pa_config, \%data, '', $module, 'keep_alive', '', time(), $server_id, $dbh);
	}
}

##########################################################################
=head2 C<< pandora_create_incident (I<$pa_config>, I<$dbh>, I<$title>, I<$text>, I<$priority>, I<$status>, I<$origin>, I<$id_group>) >> 

Create an internal Pandora incident.

=cut
##########################################################################
sub pandora_create_incident ($$$$$$$$;$) {
	my ($pa_config, $dbh, $title, $text,
		$priority, $status, $origin, $id_group, $owner) = @_;
	
	logger($pa_config, "Creating incident '$text' source '$origin'.", 8);
	
	# Initialize default parameters
	$owner = '' unless defined ($owner);
	
	db_do($dbh, 'INSERT INTO tincidencia (inicio, titulo, descripcion, origen, estado, prioridad, id_grupo, id_usuario)
			VALUES (NOW(), ?, ?, ?, ?, ?, ?, ?)', $title, $text, $origin, $status, $priority, $id_group, $owner);
}


##########################################################################
=head2 C<< pandora_audit (I<$pa_config>, I<$description>, I<$name>, I<$action>, I<$dbh>) >> 

Create an internal audit entry.

=cut
##########################################################################
sub pandora_audit ($$$$$) {
	my ($pa_config, $description, $name, $action, $dbh) = @_;
	my $disconnect = 0;
	
	logger($pa_config, "Creating audit entry '$description' name '$name' action '$action'.", 10);
	
	my $utimestamp = time();
	my $timestamp = strftime ("%Y-%m-%d %H:%M:%S", localtime($utimestamp));
	
	db_do($dbh, 'INSERT INTO tsesion (id_usuario, ip_origen, accion, fecha, descripcion, utimestamp) 
			VALUES (?, ?, ?, ?, ?, ?)', 'SYSTEM', $name, $action , $timestamp , $description , $utimestamp);

	db_disconnect($dbh) if ($disconnect == 1);
}

##########################################################################
=head2 C<< pandora_create_module (I<$pa_config>, I<$agent_id>, I<$module_type_id>, I<$module_name>, I<$max>, I<$min>, I<$post_process>, I<$description>, I<$interval>, I<$dbh>) >> 

Create a new entry in tagente_modulo and the corresponding entry in B<tagente_estado>.

=cut
##########################################################################
sub pandora_create_module ($$$$$$$$$$) {
	my ($pa_config, $agent_id, $module_type_id, $module_name, $max,
		$min, $post_process, $description, $interval, $dbh) = @_;
	
	logger($pa_config, "Creating module '$module_name' for agent ID $agent_id.", 10);
	
	# Provide some default values
	$max = 0 if ($max eq '');
	$min = 0 if ($min eq '');
	$post_process = 0 if ($post_process eq '');
	
	# Set the initial status of the module
	my $status = 4;
	if ($module_type_id == 21 || $module_type_id == 22 || $module_type_id == 23) {
		$status = 0;
	}
	
	my $module_id = db_insert($dbh, 'id_agente_modulo',
		'INSERT INTO tagente_modulo (id_agente, id_tipo_modulo, nombre, max, min, post_process, descripcion, module_interval, id_modulo, critical_instructions, warning_instructions, unknown_instructions, disabled_types_event, module_macros)
		VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, \'\', \'\', \'\', \'\', \'\')',
		$agent_id, $module_type_id, safe_input($module_name), $max, $min, $post_process, $description, $interval);
	db_do ($dbh, 'INSERT INTO tagente_estado (id_agente_modulo, id_agente, estado, last_status, last_known_status, last_try, datos)
		VALUES (?, ?, ?, ?, ?, \'1970-01-01 00:00:00\', \'\')',
		$module_id, $agent_id, $status, $status, $status);
	
	# Update the module status count. When the module is created disabled dont do it
	pandora_mark_agent_for_module_update ($dbh, $agent_id);
	
	return $module_id;
}

##########################################################################
## Delete a module given its id.
##########################################################################
sub pandora_delete_module ($$;$) {
	my ($dbh, $module_id, $conf) = @_;
	
	# Get module data
	my $module = get_db_single_row ($dbh, 'SELECT * FROM tagente_modulo, tagente_estado WHERE tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo AND tagente_modulo.id_agente_modulo=?', $module_id);
	return unless defined ($module);
	
	# Delete Graphs, layouts & reports
	db_do ($dbh, 'DELETE FROM tgraph_source WHERE id_agent_module = ?', $module_id);
	db_do ($dbh, 'DELETE FROM tlayout_data WHERE id_agente_modulo = ?', $module_id);
	db_do ($dbh, 'DELETE FROM treport_content WHERE id_agent_module = ?', $module_id);
	
	# Delete the module state
	db_do ($dbh, 'DELETE FROM tagente_estado WHERE id_agente_modulo = ?', $module_id);
	
	# Delete templates asociated to the module
	db_do ($dbh, 'DELETE FROM talert_template_modules WHERE id_agent_module = ?', $module_id);
	
	# Delete events asociated to the module
	db_do ($dbh, 'DELETE FROM tevento WHERE id_agentmodule = ?', $module_id);
	
	# Delete tags asociated to the module
	db_do ($dbh, 'DELETE FROM ttag_module WHERE id_agente_modulo = ?', $module_id);
	
	# Set pending delete the module
	db_do ($dbh, 'UPDATE tagente_modulo SET disabled = 1, delete_pending = 1, nombre = "delete_pending" WHERE id_agente_modulo = ?', $module_id);
	
	my $agent_name = get_agent_name($dbh, $module->{'id_agente'});
	
	if ((defined($conf)) && (-e $conf->{incomingdir}.'/conf/'.md5($agent_name).'.conf')) {
		enterprise_hook('pandora_delete_module_from_conf', [$conf,$agent_name,$module->{'nombre'}]);
	}
	
	# Update module status count
	pandora_mark_agent_for_module_update ($dbh, $module->{'id_agente'});
}

##########################################################################
## Create an agent module from network component
##########################################################################
sub pandora_create_module_from_network_component ($$$$) {
	my ($pa_config, $component, $id_agent, $dbh) = @_;
	
	my $addr = get_agent_address($dbh, $id_agent);
	
	logger($pa_config, "Processing network component '" . safe_output ($component->{'name'}) . "' for agent $addr.", 10);
	
	# The modules are created enabled and with the flag activated to force first execution
	$component->{'flag'} = 1;
	$component->{'disabled'} = 0;
	
	# Set the agent id
	$component->{'id_agente'} = $id_agent;
	
	# Delete the fields that will not be inserted in the modules table
	delete $component->{'id_nc'};
	$component->{'nombre'} = $component->{'name'};
	delete $component->{'name'};
	$component->{'descripcion'} = $component->{'description'};
	delete $component->{'description'};
	delete $component->{'id_group'};
	my $component_tags = $component->{'tags'};
	delete $component->{'tags'};
	$component->{'id_tipo_modulo'} = $component->{'type'};
	delete $component->{'type'};
	$component->{'ip_target'} = $addr;
	
	my $module_id = pandora_create_module_from_hash($pa_config, $component, $dbh); 
	
	# Propagate the tags to the module
	pandora_create_module_tags ($pa_config, $dbh, $module_id, $component_tags);
	
	logger($pa_config, 'Creating module ' . safe_output ($component->{'nombre'}) . " (ID $module_id) for agent $addr from network component.", 10);
}

##########################################################################
## Create an agent module from hash
##########################################################################
sub pandora_create_module_from_hash ($$$) {
	my ($pa_config, $parameters, $dbh) = @_;
	
	logger($pa_config,
		"Creating module '$parameters->{'nombre'}' for agent ID $parameters->{'id_agente'}.", 10);
	
	# Delete tags that will not be stored in tagente_modulo
	delete $parameters->{'data'};
	delete $parameters->{'type'};
	delete $parameters->{'datalist'};
	delete $parameters->{'status'};
	if (defined $parameters->{'id_os'}) {
		delete $parameters->{'id_os'};
	}
	if (defined $parameters->{'os_version'}) {
		delete $parameters->{'os_version'};
	}
	if (defined $parameters->{'id_os'}) {
		delete $parameters->{'id'};
	}
	if (defined $parameters->{'id_network_component_group'}) {
		delete $parameters->{'id_network_component_group'};
	}
	my $module_id = db_process_insert($dbh, 'id_agente_modulo',
		'tagente_modulo', $parameters);
	
	my $status = 4;
	if (defined ($parameters->{'id_tipo_modulo'}) && ($parameters->{'id_tipo_modulo'} == 21 || $parameters->{'id_tipo_modulo'} == 22 || $parameters->{'id_tipo_modulo'} == 23)) {
		$status = 0;
	}
	
	db_do ($dbh, 'INSERT INTO tagente_estado (id_agente_modulo, id_agente, estado, last_status, last_known_status, last_try, datos) VALUES (?, ?, ?, ?, ?, \'1970-01-01 00:00:00\', \'\')', $module_id, $parameters->{'id_agente'}, $status, $status, $status);
	
	# Update the module status count. When the module is created disabled dont do it
	pandora_mark_agent_for_module_update ($dbh, $parameters->{'id_agente'});
	
	return $module_id;
}

##########################################################################
## Update an agent module from hash
##########################################################################
sub pandora_update_module_from_hash ($$$$$) {
	my ($pa_config, $parameters, $where_column, $where_value, $dbh) = @_;
	
	my $module_id = db_process_update($dbh, 'tagente_modulo', $parameters, $where_column, $where_value);
	return $module_id;
}

##########################################################################
## Update a table from hash
##########################################################################
sub pandora_update_table_from_hash ($$$$$$) {
	my ($pa_config, $parameters, $where_column, $where_value, $table, $dbh) = @_;
	
	my $module_id = db_process_update($dbh, $table, $parameters, $where_column, $where_value);
	return $module_id;
}

##########################################################################
## Create a group
##########################################################################
sub pandora_create_group ($$$$$$$$$) {
	my ($name, $icon, $parent, $propagate, $disabled, $custom_id, $id_skin, $description, $dbh) = @_;
	
	my $group_id = db_insert ($dbh, 'id_grupo', 'INSERT INTO tgrupo (nombre, icon, parent, propagate, disabled, custom_id, id_skin, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)', safe_input($name), $icon, 
		$parent, $propagate, $disabled, $custom_id, $id_skin, $description);
	 
	return $group_id;
}

##########################################################################
## Create or update a token of tconfig table
##########################################################################
sub pandora_update_config_token ($$$) {
	my ($dbh, $token, $value) = @_;
	
	my $config_value = pandora_get_config_value($dbh, $token);
	
	my $result = undef;
	if($config_value ne '') {
		$result = db_update ($dbh, 'UPDATE tconfig SET value = ? WHERE token = ?', $value, $token);
	}
	else {
		$result = db_insert ($dbh, 'id_config', 'INSERT INTO tconfig (token, value) VALUES (?, ?)', $token, $value);
	}
	
	return $result;
}

##########################################################################
## Get value of  a token of tconfig table
##########################################################################
sub pandora_get_config_value ($$) {
	my ($dbh, $token) = @_;
	
	my $config_value = get_db_value($dbh, 'SELECT value FROM tconfig WHERE token = ?',$token);
	
	return (defined ($config_value) ? $config_value : "");
}

##########################################################################
=head2 C<< pandora_create_module_tags (I<$pa_config>, I<$dbh>, I<$id_agent_module>, I<$serialized_tags>) >>

Associate tags in a module. The tags are passed separated by commas

=cut
##########################################################################

sub pandora_create_module_tags ($$$$) {
	my ($pa_config, $dbh, $id_agent_module, $serialized_tags) = @_;
	
	if($serialized_tags eq '') {
		return 0;
	}
	
	foreach my $tag_name (split (',', $serialized_tags)) {
		my $tag_id = get_db_value ($dbh,
			"SELECT id_tag FROM ttag WHERE name = ?", $tag_name);
		
		db_insert ($dbh,
			'id_tag',
			"INSERT INTO ttag_module(
				" . $RDBMS_QUOTE . "id_tag" . $RDBMS_QUOTE . ",
				" . $RDBMS_QUOTE . "id_agente_modulo" . $RDBMS_QUOTE . ")
			VALUES (?, ?)",
			$tag_id, $id_agent_module);
	}
}

##########################################################################
=head2 C<< pandora_create_agent (I<$pa_config>, I<$server_name>, I<$agent_name>, I<$address>, I<$group_id>, I<$parent_id>, I<$os_id>, I<$description>, I<$interval>, I<$dbh>, [I<$timezone_offset>], [I<$longitude>], [I<$latitude>], [I<$altitude>], [I<$position_description>], [I<$custom_id>], [I<$url_address>]) >>

Create a new entry in B<tagente> optionaly with position information

=cut
##########################################################################
sub pandora_create_agent ($$$$$$$$$$;$$$$$$$) {
	my ($pa_config, $server_name, $agent_name, $address,
		$group_id, $parent_id, $os_id,
		$description, $interval, $dbh, $timezone_offset,
		$longitude, $latitude, $altitude, $position_description,
		$custom_id, $url_address) = @_;
	
	logger ($pa_config, "Server '$server_name' creating agent '$agent_name' address '$address'.", 10);
	
	if (!defined($group_id)) {
		$group_id = $pa_config->{'autocreate_group'};
		if (! defined (get_group_name ($dbh, $group_id))) {
			logger($pa_config, "Group id $group_id does not exist (check autocreate_group config token)", 3);
			return;
		}
	}

	$description = "Created by $server_name" unless ($description ne '');	
	my ($columns, $values) = db_insert_get_values ({ 'nombre' => safe_input($agent_name),
	                                                 'direccion' => $address,
	                                                 'comentarios' => $description,
	                                                 'id_grupo' => $group_id,
	                                                 'id_os' => $os_id,
	                                                 'server_name' => $server_name,
	                                                 'intervalo' => $interval,
	                                                 'id_parent' => $parent_id,
	                                                 'modo' => 1,
	                                                 'custom_id' => $custom_id,
	                                                 'url_address' => $url_address,
	                                                 'timezone_offset' => $timezone_offset
	                                                });                           
	                                                
	my $agent_id = db_insert ($dbh, 'id_agente', "INSERT INTO tagente $columns", @{$values});

	# Save GIS data
	if (defined ($longitude) && defined ($latitude ) && $pa_config->{'activate_gis'} == 1 ) {

		# Save the first position
		my $timestamp = strftime ("%Y-%m-%d %H:%M:%S", localtime (time ()));
		save_agent_position($pa_config, $longitude, $latitude, $altitude, $agent_id, $dbh, $timestamp, $position_description) ;
	}
	
	logger ($pa_config, "Server '$server_name' CREATED agent '$agent_name' address '$address'.", 10);
	pandora_event ($pa_config, "Agent [$agent_name] created by $server_name", $group_id, $agent_id, 2, 0, 0, 'new_agent', 0, $dbh);
	return $agent_id;
}

##########################################################################
# Add an address if not exists and add this address to taddress_agent if not exists
##########################################################################
sub pandora_add_agent_address ($$$$$) {
	my ($pa_config, $agent_id, $agent_name, $addr, $dbh) = @_;
	
	# Add the new address if it does not exist
	my $addr_id = get_addr_id ($dbh, $addr);
	
	if($addr_id <= 0) {
		logger($pa_config, 'Adding address ' . $addr . ' to the address list', 10);
		$addr_id = add_address ($dbh, $addr);
	}
	
	if ($addr_id <= 0) {
		logger($pa_config, "Could not add address '$addr' for host '$agent_name'", 3);
	}
	
	my $agent_address = is_agent_address($dbh, $agent_id, $addr_id);
	if($agent_address == 0) {
		logger($pa_config, 'Updating address for agent ' . $agent_name . ' (' . $addr . ') in his address list', 10);
		add_new_address_agent ($dbh, $addr_id, $agent_id)
	}
}

##########################################################################
## Delete an agent given its id.
##########################################################################
sub pandora_delete_agent ($$;$) {
	my ($dbh, $agent_id, $conf) = @_;
	my $agent_name = get_agent_name($dbh, $agent_id);
	
	# Delete from all their policies
	enterprise_hook('pandora_delete_agent_from_policies', [$agent_id, $dbh]);
	
	# Delete the agent
	db_do ($dbh, 'DELETE FROM tagente WHERE id_agente = ?', $agent_id);
	
	# Delete agent access data
	db_do ($dbh, 'DELETE FROM tagent_access WHERE id_agent = ?', $agent_id);
	
	# Delete addresses
	db_do ($dbh, 'DELETE FROM taddress_agent WHERE id_ag = ?', $agent_id);
	
	my @modules = get_db_rows ($dbh, 'SELECT * FROM tagente_modulo WHERE id_agente = ?', $agent_id);
	
	if (defined $conf) {
		# Delete the conf files
		if (-e $conf->{incomingdir}.'/conf/'.md5($agent_name).'.conf') {
			unlink($conf->{incomingdir}.'/conf/'.md5($agent_name).'.conf');
		}
		if (-e $conf->{incomingdir}.'/md5/'.md5($agent_name).'.md5') {
			unlink($conf->{incomingdir}.'/md5/'.md5($agent_name).'.md5');
		}
	}

	foreach my $module (@modules) {
			pandora_delete_module ($dbh, $module->{'id_agente_modulo'});
	}
	
	# Delete all the associated nodes of networkmap enterprise, if exist
	enterprise_hook('pandora_delete_networkmap_enterprise_agents', [$dbh,$agent_id]);
}

##########################################################################
=head2 C<< pandora_event (I<$pa_config>, I<$evento>, I<$id_grupo>, I<$id_agente>, I<$severity>, I<$id_alert_am>, I<$id_agentmodule>, I<$event_type>, I<$event_status>, I<$dbh>) >> 

Generate an event.

=cut
##########################################################################
sub pandora_event ($$$$$$$$$$;$$$$$$$$$) {
	my ($pa_config, $evento, $id_grupo, $id_agente, $severity,
		$id_alert_am, $id_agentmodule, $event_type, $event_status, $dbh,
		$source, $user_name, $comment, $id_extra, $tags,
		$critical_instructions, $warning_instructions, $unknown_instructions, $custom_data) = @_;
	
	my $agent = undef;
	if ($id_agente != 0) {
		$agent = get_db_single_row ($dbh, 'SELECT *	FROM tagente WHERE id_agente = ?', $id_agente);
		if (defined ($agent) && $agent->{'quiet'} == 1) {
			logger($pa_config, "Generate Event. The agent '" . $agent->{'nombre'} . "' is in quiet mode.", 10);
			return;
		}
	}
	
	my $module = undef;
	if ($id_agentmodule != 0) {
		$module = get_db_single_row ($dbh, 'SELECT * FROM tagente_modulo WHERE id_agente_modulo = ?', $id_agentmodule);
		if (defined ($module) && $module->{'quiet'} == 1) {
			logger($pa_config, "Generate Event. The module '" . $module->{'nombre'} . "' is in quiet mode.", 10);
			return;
		}
	}
		
	# Get module tags
	my $module_tags = '';
	if (defined ($tags) && ($tags ne '')) {
		$module_tags = $tags
	}
	else {
		if (defined ($id_agentmodule) && $id_agentmodule > 0) {
			$module_tags = pandora_get_module_tags ($pa_config, $dbh, $id_agentmodule);
		}
	}
	
	
	# Set default values for optional parameters
	$source = 'Pandora' unless defined ($source);
	$comment = '' unless defined ($comment);
	$id_extra = '' unless defined ($id_extra);
	$user_name = '' unless defined ($user_name);
	$critical_instructions = '' unless defined ($critical_instructions);
	$warning_instructions = '' unless defined ($warning_instructions);
	$unknown_instructions = '' unless defined ($unknown_instructions);
	$custom_data = '' unless defined ($custom_data);
	
	# If the event is created with validated status, assign ack_utimestamp
	my $ack_utimestamp = $event_status == 1 ? time() : 0;
	
	my $utimestamp = time ();
	my $timestamp = strftime ("%Y-%m-%d %H:%M:%S", localtime ($utimestamp));
	$id_agentmodule = 0 unless defined ($id_agentmodule);
	
	if($comment ne '') {
		my @comment_data = ({ comment => $comment, action => "Added comment", id_user => "an alert", utimestamp => $utimestamp});
		$comment = encode_json \@comment_data;
	}
	
	# Validate events with the same event id
	if (defined ($id_extra) && $id_extra ne '') {
		logger($pa_config, "Updating events with extended id '$id_extra'.", 10);
		db_do ($dbh, 'UPDATE tevento SET estado = 1, ack_utimestamp = ? WHERE estado = 0 AND id_extra=?', $utimestamp, $id_extra);
	}
	
	# Create the event
	logger($pa_config, "Generating event '$evento' for agent ID $id_agente module ID $id_agentmodule.", 10);
	db_do ($dbh, 'INSERT INTO tevento (id_agente, id_grupo, evento, timestamp, estado, utimestamp, event_type, id_agentmodule, id_alert_am, criticity, user_comment, tags, source, id_extra, id_usuario, critical_instructions, warning_instructions, unknown_instructions, ack_utimestamp, custom_data)
	              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', $id_agente, $id_grupo, safe_input ($evento), $timestamp, $event_status, $utimestamp, $event_type, $id_agentmodule, $id_alert_am, $severity, $comment, $module_tags, $source, $id_extra, $user_name, $critical_instructions, $warning_instructions, $unknown_instructions, $ack_utimestamp, $custom_data);
	
	# Do not write to the event file
	return if ($pa_config->{'event_file'} eq '');

	# Add a header when the event file is created
	my $header = undef;
	if (! -f $pa_config->{'event_file'}) {
		$header = "agent_name,group_name,evento,timestamp,estado,utimestamp,event_type,module_name,alert_name,criticity,user_comment,tags,source,id_extra,id_usuario,critical_instructions,warning_instructions,unknown_instructions,ack_utimestamp";
	}
	
	# Open the event file for writing
	if (! open (EVENT_FILE, '>>' . $pa_config->{'event_file'})) {
		logger($pa_config, "Error opening event file " . $pa_config->{'event_file'} . ": $!", 10);
		return;
	}
	
	# Resolve ids
	my $group_name = get_group_name ($dbh, $id_grupo);
	$group_name = '' unless defined ($group_name);
	my $agent_name = defined ($agent) ? safe_output ($agent->{'nombre'}) : '';
	my $module_name = defined ($module) ? safe_output ($module->{'nombre'}) : '';
	my $alert_name = get_db_value ($dbh, 'SELECT name FROM talert_templates, talert_template_modules WHERE talert_templates.id = talert_template_modules.id_alert_template AND talert_template_modules.id = ?', $id_alert_am);
	if (defined ($alert_name)) {
		$alert_name = safe_output ($alert_name);
	} else {
		$alert_name = '';
	}
	
	# Get an exclusive lock on the file (LOCK_EX)
	flock (EVENT_FILE, 2);
	
	# Write the event
	print EVENT_FILE "$header\n" if (defined ($header));
	print EVENT_FILE  "$agent_name,".safe_output($group_name)."," . safe_output ($evento) . ",$timestamp,$event_status,$utimestamp,$event_type,".safe_output($module_name).",".safe_output($alert_name).",$severity,".safe_output($comment).",".safe_output($module_tags).",$source,$id_extra,$user_name,".safe_output($critical_instructions).",".safe_output($warning_instructions).",".safe_output($unknown_instructions).",$ack_utimestamp\n";
	
	close (EVENT_FILE);
}

##########################################################################
=head2 C<< pandora_update_module_on_error (I<$pa_config>, I<$id_agent_module>, I<$dbh>) >> 

Update module status on error.

=cut
##########################################################################
sub pandora_update_module_on_error ($$$) {
	my ($pa_config, $module, $dbh) = @_;

	# Set tagente_estado.current_interval to make sure it is not 0
	my $current_interval;
	if ($module->{'cron_interval'} ne '' && $module->{'cron_interval'} ne '* * * * *') {
		$current_interval = cron_next_execution ($module->{'cron_interval'});
	}
	elsif ($module->{'module_interval'} == 0) {
		$current_interval = 300;
	}
	else {
		$current_interval = $module->{'module_interval'};
	}

	logger($pa_config, "Updating module " . safe_output($module->{'nombre'}) . " (ID " . $module->{'id_agente_modulo'} . ") on error.", 10);

	# Update last_execution_try
	db_do ($dbh, 'UPDATE tagente_estado SET last_execution_try = ?, current_interval = ?
		WHERE id_agente_modulo = ?', time (), $current_interval, $module->{'id_agente_modulo'});
}

##########################################################################
=head2 C<< pandora_exec_forced_alerts (I<$pa_config>, I<$dbh>) >>

Execute forced alerts.

=cut
##########################################################################
sub pandora_exec_forced_alerts {
	my ($pa_config, $dbh) = @_;

	# Get alerts marked for forced execution (even disabled alerts)
	my @alerts = get_db_rows ($dbh, 'SELECT talert_template_modules.id as id_template_module,
				talert_template_modules.*, talert_templates.*
				FROM talert_template_modules, talert_templates
				WHERE talert_template_modules.id_alert_template = talert_templates.id
				AND force_execution = 1');
	foreach my $alert (@alerts) {
		
		# Get the agent and module associated with the alert
		my $module = get_db_single_row ($dbh, 'SELECT * FROM tagente_modulo WHERE id_agente_modulo = ?', $alert->{'id_agent_module'});
		if (! defined ($module)) {
			logger($pa_config, "Module ID " . $alert->{'id_agent_module'} . " not found for alert ID " . $alert->{'id_template_module'} . ".", 10);
			next;
		}
		my $agent = get_db_single_row ($dbh, 'SELECT * FROM tagente WHERE id_agente = ?', $module->{'id_agente'});
		if (! defined ($agent)) {
			logger($pa_config, "Agent ID " . $module->{'id_agente'} . " not found for module ID " . $module->{'id_agente_modulo'} . " alert ID " . $alert->{'id_template_module'} . ".", 10);
			next;
		}

		pandora_execute_alert ($pa_config, 'N/A', $agent, $module, $alert, 1, $dbh, undef);

		# Reset the force_execution flag, even if the alert could not be executed
		db_do ($dbh, "UPDATE talert_template_modules SET force_execution = 0 WHERE id = " . $alert->{'id_template_module'});
	}
}

##########################################################################
=head2 C<< pandora_module_keep_alive_nd (I<$pa_config>, I<$dbh>) >> 

Update keep_alive modules for agents without data.

=cut
##########################################################################
sub pandora_module_keep_alive_nd {
	my ($pa_config, $dbh) = @_;

	my @modules = get_db_rows ($dbh, 'SELECT tagente_modulo.*
					FROM tagente_modulo, tagente_estado, tagente 
					WHERE tagente.id_agente = tagente_estado.id_agente 
					AND tagente.disabled = 0 
					AND tagente_modulo.id_tipo_modulo = 100 
					AND tagente_modulo.disabled = 0 
					AND (tagente_modulo.flag = 1 OR ((tagente_estado.last_execution_try + tagente_estado.current_interval) < UNIX_TIMESTAMP()))
					AND tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo 
					AND ( tagente_estado.utimestamp + (tagente.intervalo * 2) < UNIX_TIMESTAMP())');

	my %data = ('data' => 0);
	foreach my $module (@modules) {
		logger($pa_config, "Updating keep_alive module for module '" . $module->{'nombre'} . "' agent ID " . $module->{'id_agente'} . " (agent without data).", 10);
		pandora_process_module ($pa_config, \%data, '', $module, 'keep_alive', '', time (), 0, $dbh);
	}
}

##########################################################################
=head2 C<< pandora_evaluate_snmp_alerts (I<$pa_config>, I<$trap_id>, I<$trap_agent>, I<$trap_oid>, I<$trap_oid_text>, I<$value>, I<$trap_custom_oid>, I<$dbh>) >> 

Execute alerts that apply to the given SNMP trap.

=cut
##########################################################################
sub pandora_evaluate_snmp_alerts ($$$$$$$$$) {
	my ($pa_config, $trap_id, $trap_agent, $trap_oid, $trap_type,
		$trap_oid_text, $trap_value, $trap_custom_oid, $dbh) = @_;

	# Get all SNMP alerts
	my @snmp_alerts = get_db_rows ($dbh, 'SELECT * FROM talert_snmp ORDER BY position ASC');

	# Find those that apply to the given SNMP trap
	foreach my $alert (@snmp_alerts) {

		my $alert_data = '';
		my ($times_fired, $internal_counter, $alert_type) =
			($alert->{'times_fired'}, $alert->{'internal_counter'}, $alert->{'alert_type'});

		# OID
		# Decode first, could be a complex regexp !
		$alert->{'oid'} = decode_entities($alert->{'oid'});
		my $oid = $alert->{'oid'};
		if ($oid ne '') {
			next if (index ($trap_oid, $oid) == -1 && index ($trap_oid_text, $oid) == -1);
			$alert_data .= "OID: $oid ";
		}

		# Trap type
		if ($alert->{'trap_type'} >= 0) {
			# 1-4
			if ($alert->{'trap_type'} < 5) {
				next  if ($trap_type != $alert->{'trap_type'});
			# Other
			} else {
				next  if ($trap_type < 5);
			}
			$alert_data .= "Type: $trap_type ";
		}

		# Trap value
		my $single_value = decode_entities($alert->{'single_value'});
		if ($single_value ne '') {

			# No match
			next if (valid_regex ($single_value) == 0 || $trap_value !~ m/^$single_value$/i);
			$alert_data .= "Value: $trap_value ";
		}

		# Agent IP
		my $agent = decode_entities($alert->{'agent'});
		if ($agent ne '') {
			
			# No match
			next if (valid_regex ($agent) == 0 || $trap_agent !~ m/^$agent$/i );
			$alert_data .= "Agent: $agent";
		}
		
		# Specific SNMP Trap alert macros for regexp selectors in trap info
		my %macros;
		$macros{'_snmp_oid_'} = $trap_oid_text;
		$macros{'_snmp_value_'} = $trap_value;
		
		# Custom OID/value
		# Decode first, this could be a complex regexp !
		my $custom_oid = decode_entities($alert->{'custom_oid'});
		if ($custom_oid ne '') {
			
			# No match
			next if (valid_regex ($custom_oid) == 0 || $trap_custom_oid !~ m/^$custom_oid$/i);
			$alert_data .= " Custom: $trap_custom_oid";
		}

		# Assign default values to the _snmp_fx_ macros from variable bindings
		my $count;
		my @custom_values = split ("\t", $trap_custom_oid);
		for ($count = 1; defined ($custom_values[$count-1]); $count++) {
			my $macro_name = '_snmp_f' . $count . '_';
			my $order_field = $alert->{'order_'.$count};
			#~ my $order_field = $order_field - 1;
			
			if ($custom_values[($order_field-1)] =~ m/= \S+: (.*)/) {
				my $value = $1;
			
				# Strip leading and trailing double quotes
				$value =~ s/^"//;
				$value =~ s/"$//;
				
				$macros{$macro_name} = $value;
			}
		}
		$count--;
		
		# Number of variables
		$macros{'_snmp_argc_'} = $count;

		# All variables
		$macros{'_snmp_argv_'} = $trap_custom_oid;

		# Evaluate _snmp_fx_ filters
		my $filter_match = 1;
		for (my $i = 1; $i <= 10; $i++) {
			my $filter_name = '_snmp_f' . $i . '_';
			my $filter_value = safe_output ($alert->{$filter_name});

			# No filter for the current binding var
			next if ($filter_value eq '');
			
			# The referenced binding var does not exist
			if (! defined ($macros{$filter_name})) {
				$filter_match = 0;
				last;
			}
			
			# Evaluate the filter
			eval {
				if ($macros{$filter_name} !~ m/$filter_value/) {
					$filter_match = 0;
				}
			};
			
			# Probably an invalid regexp
			if ($@) {
				last;
			}
			
			# The filter did not match
			last if ($filter_match == 0);
		}
		
		# A filter did not match
		next if ($filter_match == 0);
		
		# Replace macros
		$alert->{'al_field1'} = subst_alert_macros ($alert->{'al_field1'}, \%macros);
		$alert->{'al_field2'} = subst_alert_macros ($alert->{'al_field2'}, \%macros);
		$alert->{'al_field3'} = subst_alert_macros ($alert->{'al_field3'}, \%macros);
		$alert->{'al_field4'} = subst_alert_macros ($alert->{'al_field4'}, \%macros);
		$alert->{'al_field5'} = subst_alert_macros ($alert->{'al_field5'}, \%macros);
		$alert->{'al_field6'} = subst_alert_macros ($alert->{'al_field6'}, \%macros);
		$alert->{'al_field7'} = subst_alert_macros ($alert->{'al_field7'}, \%macros);
		$alert->{'al_field8'} = subst_alert_macros ($alert->{'al_field8'}, \%macros);
		$alert->{'al_field9'} = subst_alert_macros ($alert->{'al_field9'}, \%macros);
		$alert->{'al_field10'} = subst_alert_macros ($alert->{'al_field10'}, \%macros);

		# Check time threshold
		$alert->{'last_fired'} = '1970-01-01 00:00:00' unless defined ($alert->{'last_fired'});
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
				'snmp_alert' => 1,
				'name' => '',
				'agent' => 'N/A',
				'alert_data' => 'N/A',
				'id_agent_module' => 0,
				'id_template_module' => 0,
				'field1' => $alert->{'al_field1'},
				'field2' => $alert->{'al_field2'},
				'field3' => $alert->{'al_field3'},
				'field4' => $alert->{'al_field4'},
				'field5' => $alert->{'al_field5'},
				'field6' => $alert->{'al_field6'},
				'field7' => $alert->{'al_field7'},
				'field8' => $alert->{'al_field8'},
				'field9' => $alert->{'al_field9'},
				'field10' => $alert->{'al_field10'},
				'description' => $alert->{'description'},
				'times_fired' => $times_fired,
				'time_threshold' => 0,
				'id' => $alert->{'id_alert'},
				'priority' => $alert->{'priority'},
			);

			my %agent;

			my $this_agent = get_agent_from_addr ($dbh, $trap_agent);
			if (defined($this_agent)){
				%agent = ( 
					'nombre' => $this_agent->{'nombre'},
					'id_agente' => $this_agent->{'id_agente'},
					'direccion' => $trap_agent,
					'id_grupo' => $this_agent->{'id_grupo'},
					'comentarios' => ''
				);
			} else {
				%agent = (
					'nombre' => $trap_agent,
					'direccion' => $trap_agent,
					'comentarios' => '',
					'id_agente' =>  0,
					'id_grupo' => 0
				);
			}
			
			# Execute alert
			my $action = get_db_single_row ($dbh, 'SELECT *
							FROM talert_actions, talert_commands
							WHERE talert_actions.id_alert_command = talert_commands.id
							AND talert_actions.id = ?', $alert->{'id_alert'});

			my $trap_rcv_full = $trap_oid . " " . $trap_value. " ". $trap_type. " " . $trap_custom_oid;

			pandora_execute_action ($pa_config, $trap_rcv_full, \%agent, \%alert, 1, $action, undef, $dbh, $timestamp, \%macros) if (defined ($action));

			# Generate an event, ONLY if our alert action is different from generate an event.
			if ($action->{'id_alert_command'} != 3){
				pandora_event ($pa_config, "SNMP alert fired (" . $alert->{'description'} . ")",
					0, 0, $alert->{'priority'}, 0, 0, 'alert_fired', 0, $dbh);
		   }

			# Update alert status
			db_do ($dbh, 'UPDATE talert_snmp SET times_fired = ?, last_fired = ?, internal_counter = ? WHERE id_as = ?',
				$times_fired, $timestamp, $internal_counter, $alert->{'id_as'});

			db_do ($dbh, 'UPDATE ttrap SET alerted = 1, priority = ? WHERE id_trap = ?',
				$alert->{'priority'}, $trap_id);
				
			# MORE ACTIONS
			my @more_actions_snmp;
			@more_actions_snmp = get_db_rows ($dbh,'SELECT * FROM talert_snmp_action WHERE id_alert_snmp = ?',
					$alert->{'id_as'});
					
			foreach my $other_alert (@more_actions_snmp) {
				my $other_action = get_db_single_row ($dbh, 'SELECT *
					FROM talert_actions, talert_commands
					WHERE talert_actions.id_alert_command = talert_commands.id
					AND talert_actions.id = ?', $other_alert->{'alert_type'});
				my %alert_action = (
					'snmp_alert' => 1,
					'name' => '',
					'agent' => 'N/A',
					'alert_data' => 'N/A',
					'id_agent_module' => 0,
					'id_template_module' => 0,
					'field1' => $other_alert->{'al_field1'},
					'field2' => $other_alert->{'al_field2'},
					'field3' => $other_alert->{'al_field3'},
					'field4' => $other_alert->{'al_field4'},
					'field5' => $other_alert->{'al_field5'},
					'field6' => $other_alert->{'al_field6'},
					'field7' => $other_alert->{'al_field7'},
					'field8' => $other_action->{'al_field8'},
					'field9' => $other_alert->{'al_field9'},
					'field10' => $other_alert->{'al_field10'},
					'description' => '',
					'times_fired' => $times_fired,
					'time_threshold' => 0,
					'id' => $other_alert->{'alert_type'},
					'priority' => $alert->{'priority'},
				);

				pandora_execute_action ($pa_config, $trap_rcv_full, \%agent, \%alert_action, 1, $other_action, undef, $dbh, $timestamp, \%macros) if (defined ($other_action));
					
				# Generate an event, ONLY if our alert action is different from generate an event.
				if ($other_action->{'id_alert_command'} != 3){
					pandora_event ($pa_config, "SNMP alert fired (" . $alert->{'description'} . ")",
						0, 0, $alert->{'priority'}, 0, 0, 'alert_fired', 0, $dbh);
				}

				# Update alert status
				db_do ($dbh, 'UPDATE talert_snmp SET times_fired = ?, last_fired = ?, internal_counter = ? WHERE id_as = ?',
					$times_fired, $timestamp, $internal_counter, $alert->{'id_as'});

				db_do ($dbh, 'UPDATE ttrap SET alerted = 1, priority = ? WHERE id_trap = ?',
					$alert->{'priority'}, $trap_id);
			}
			#~ END MORE ACTIONS

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
		
		# Do not execute more than one alert per trap
		last;
	}
}


##########################################################################
# Utility functions, not to be exported.
##########################################################################

##########################################################################
# Search string for macros and substitutes them with their values.
##########################################################################
sub subst_alert_macros ($$;$$$$) {
	my ($string, $macros, $pa_config, $dbh, $agent, $module) = @_;

	my $macro_regexp = join('|', keys %{$macros});

	my $subst_func;
	if ($string =~ m/^(?:(")(?:.*)"|(')(?:.*)')$/) {
		my $quote = $1 ? $1 : $2;
		$subst_func = sub {
			my $macro = on_demand_macro($pa_config, $dbh, shift, $macros, $agent, $module);
			$macro =~ s/'/'\\''/g; # close, escape, open
			return decode_entities($quote . "'" . $macro . "'" . $quote); # close, quote, open
		};
	}
	else {
		$subst_func = sub {
			my $macro = on_demand_macro($pa_config, $dbh, shift, $macros, $agent, $module);
			return decode_entities($macro);
		};
	}

	# Macro data may contain HTML entities
	eval {
		no warnings;
		local $SIG{__DIE__};
		$string =~ s/($macro_regexp)/$subst_func->($1)/ige;
	};

	return $string;
}

##########################################################################
# Load macros that access the database on demand.
##########################################################################
sub on_demand_macro($$$$$$) {
	my ($pa_config, $dbh, $macro, $macros, $agent, $module) = @_;

	# Static macro.
	return $macros->{$macro} if (defined($macros->{$macro}));

	# Load on-demand macros.
	return '' unless defined($pa_config) and defined($dbh);
	if ($macro eq '_agentstatus_') {
		return (defined ($agent)) ? get_agent_status ($pa_config, $dbh, $agent->{'id_agente'}) : '';
	} elsif ($macro eq '_modulegroup_') {
		return (defined ($module)) ? (get_module_group_name ($dbh, $module->{'id_module_group'}) || '') : '';
	} elsif ($macro eq '_modulestatus_') {
		return (defined ($module)) ? get_agentmodule_status_str($pa_config, $dbh, $module->{'id_agente_modulo'}) : '';
	} elsif ($macro eq '_moduletags_') {
		return (defined ($module)) ? pandora_get_module_url_tags ($pa_config, $dbh, $module->{'id_agente_modulo'}) : '';
	} elsif ($macro eq '_policy_') {
		return (defined ($module)) ? enterprise_hook('get_policy_name', [$dbh, $module->{'id_policy_module'}]) : '';
	} elsif ($macro eq '_email_tag_') {
		return (defined ($module)) ? pandora_get_module_email_tags ($pa_config, $dbh, $module->{'id_agente_modulo'}) : '';
	} elsif ($macro eq '_phone_tag_') {
		return (defined ($module)) ? pandora_get_module_phone_tags ($pa_config, $dbh, $module->{'id_agente_modulo'}) : '';
	} elsif ($macro eq '_name_tag_') {
		return (defined ($module)) ? pandora_get_module_tags ($pa_config, $dbh, $module->{'id_agente_modulo'}) : '';
	} elsif ($macro =~ /_agentcustomfield_(\d+)_/) {
		return '' unless defined ($agent);
		my $field_number = $1;
		my $field_value = get_db_value($dbh, 'SELECT description FROM tagent_custom_data WHERE id_field=? AND id_agent=?', $field_number, $agent->{'id_agente'});
		return (defined($field_value)) ? $field_value : '';
		
	}
}

##########################################################################
# Process module data.
##########################################################################
sub process_data ($$$$$$$) {
	my ($pa_config, $data_object, $agent, $module,
	    $module_type, $utimestamp, $dbh) = @_;

	if ($module_type eq "log4x") {
		return log4x_get_severity_num($data_object);
	}
	
	my $data = $data_object->{'data'};
	
	# String data
	if ($module_type =~ m/_string$/) {

		# Empty strings are not allowed
		if ($data eq '') {
			logger($pa_config, "Received invalid data '" . $data_object->{'data'} . "' from agent '" . $agent->{'nombre'} . "' module '" . $module->{'nombre'} . "' agent " . (defined ($agent) ? "'" . $agent->{'nombre'} . "'" : 'ID ' . $module->{'id_agente'}) . ".", 3);
			return undef;
		}

		return $data;
	}

	# Not a number
	if (! is_numeric ($data)) {
		logger($pa_config, "Received invalid data '" . $data_object->{'data'} . "' from agent '" . $agent->{'nombre'} . "' module '" . $module->{'nombre'} . "' agent " . (defined ($agent) ? "'" . $agent->{'nombre'} . "'" : 'ID ' . $module->{'id_agente'}) . ".", 3);
		return undef;
	}

	# If is a number, we need to replace "," for "."
	$data =~ s/\,/\./;

	# Out of bounds
	if (($module->{'max'} != $module->{'min'}) && ($data > $module->{'max'} || $data < $module->{'min'})) {
		logger($pa_config, "Received invalid data '" . $data_object->{'data'} . "' from agent '" . $agent->{'nombre'} . "' module '" . $module->{'nombre'} . "' agent " . (defined ($agent) ? "'" . $agent->{'nombre'} . "'" : 'ID ' . $module->{'id_agente'}) . ".", 3);
		return undef;
	}

	# Process INC modules
	if ($module_type =~ m/_inc$/) {
		$data = process_inc_data ($pa_config, $data, $module, $utimestamp, $dbh);
		
		# No previous data or error.
		return undef unless defined ($data);
	}
	# Process absolute INC modules
	elsif ($module_type =~ m/_inc_abs$/) {
		$data = process_inc_abs_data ($pa_config, $data, $module, $utimestamp, $dbh);
		
		# No previous data or error.
		return undef unless defined ($data);
	}

	# Post process
	if (is_numeric ($module->{'post_process'}) && $module->{'post_process'} != 0) {
		$data = $data * $module->{'post_process'};
	}

	# TODO: Float precission should be adjusted here in the future with a global
	# config parameter
	# Format data
	$data = sprintf("%.2f", $data);

	$data_object->{'data'} = $data;
	return $data;
}

##########################################################################
# Process data of type *_inc.
##########################################################################
sub process_inc_data ($$$$$) {
	my ($pa_config, $data, $module, $utimestamp, $dbh) = @_;

	my $data_inc = get_db_single_row ($dbh, 'SELECT * FROM tagente_datos_inc WHERE id_agente_modulo = ?', $module->{'id_agente_modulo'});

	# No previous data
	if (! defined ($data_inc)) {
		db_do ($dbh, 'INSERT INTO tagente_datos_inc
				(id_agente_modulo, datos, utimestamp)
				VALUES (?, ?, ?)', $module->{'id_agente_modulo'}, $data, $utimestamp);
		logger($pa_config, "Discarding first data for incremental module " . $module->{'nombre'} . "(module id " . $module->{'id_agente_modulo'} . ").", 10);
		return undef;
	}

	# Negative increment, reset inc data
	if ($data < $data_inc->{'datos'}) {
		db_do ($dbh, 'UPDATE tagente_datos_inc SET datos = ?, utimestamp = ? WHERE id_agente_modulo = ?', $data, $utimestamp, $module->{'id_agente_modulo'});
		logger($pa_config, "Discarding data and resetting counter for incremental module " . $module->{'nombre'} . "(module id " . $module->{'id_agente_modulo'} . ").", 10);

		# Prevent the module from becoming unknown!
		db_do ($dbh, 'UPDATE tagente_estado SET utimestamp = ? WHERE id_agente_modulo = ?', time(), $module->{'id_agente_modulo'});

		return undef;
	}

	# Should not happen
	if ($utimestamp == $data_inc->{'utimestamp'}) {
		logger($pa_config, "Duplicate timestamp for incremental module " . $module->{'nombre'} . "(module id " . $module->{'id_agente_modulo'} . ").", 10);
		return undef;
	}

	# Update inc data
	db_do ($dbh, 'UPDATE tagente_datos_inc SET datos = ?, utimestamp = ? WHERE id_agente_modulo = ?', $data, $utimestamp, $module->{'id_agente_modulo'});

	return ($data - $data_inc->{'datos'}) / ($utimestamp - $data_inc->{'utimestamp'});
}

##########################################################################
# Process data of type *_inc_abs.
##########################################################################
sub process_inc_abs_data ($$$$$) {
	my ($pa_config, $data, $module, $utimestamp, $dbh) = @_;

	my $data_inc = get_db_single_row ($dbh, 'SELECT * FROM tagente_datos_inc WHERE id_agente_modulo = ?', $module->{'id_agente_modulo'});

	# No previous data
	if (! defined ($data_inc)) {
		db_do ($dbh, 'INSERT INTO tagente_datos_inc
				(id_agente_modulo, datos, utimestamp)
				VALUES (?, ?, ?)', $module->{'id_agente_modulo'}, $data, $utimestamp);
		logger($pa_config, "Discarding first data for incremental module " . $module->{'nombre'} . "(module id " . $module->{'id_agente_modulo'} . ").", 10);
		return undef;
	}

	# Negative increment, reset inc data
	if ($data < $data_inc->{'datos'}) {
		db_do ($dbh, 'UPDATE tagente_datos_inc SET datos = ?, utimestamp = ? WHERE id_agente_modulo = ?', $data, $utimestamp, $module->{'id_agente_modulo'});
		logger($pa_config, "Discarding data and resetting counter for incremental module " . $module->{'nombre'} . "(module id " . $module->{'id_agente_modulo'} . ").", 10);

		# Prevent the module from becoming unknown!
		db_do ($dbh, 'UPDATE tagente_estado SET utimestamp = ? WHERE id_agente_modulo = ?', time(), $module->{'id_agente_modulo'});

		return undef;
	}

	# Should not happen
	if ($utimestamp == $data_inc->{'utimestamp'}) {
		logger($pa_config, "Duplicate timestamp for incremental module " . $module->{'nombre'} . "(module id " . $module->{'id_agente_modulo'} . ").", 10);
		return undef;
	}

	# Update inc data
	db_do ($dbh, 'UPDATE tagente_datos_inc SET datos = ?, utimestamp = ? WHERE id_agente_modulo = ?', $data, $utimestamp, $module->{'id_agente_modulo'});

	return ($data - $data_inc->{'datos'});
}

sub log4x_get_severity_num($) {
	my ($data_object) = @_;
	my $data = $data_object->{'severity'};
		
	return undef unless defined ($data);
	# The severity is a word, so we need to translate to numbers
		
	if ($data =~ m/^trace$/i) {
		$data = 10;
	} elsif ($data =~ m/^debug$/i) {
		$data = 20;
	} elsif ($data =~ m/^info$/i) {
		$data = 30;
	} elsif ($data =~ m/^warn$/i) {
		$data = 40;
	} elsif ($data =~ m/^error$/i) {
		$data = 50;
	} elsif ($data =~ m/^fatal$/i) {
		$data = 60;
	} else {
		$data = 10;
	}
	return $data;
}

##########################################################################
# Returns the status of the module: 0 (NORMAL), 1 (CRITICAL), 2 (WARNING).
##########################################################################
sub get_module_status ($$$) {
	my ($data, $module, $module_type) = @_;
	my ($critical_min, $critical_max, $warning_min, $warning_max) =
		($module->{'min_critical'}, $module->{'max_critical'}, $module->{'min_warning'}, $module->{'max_warning'});
	my ($critical_str, $warning_str) = ($module->{'str_critical'}, $module->{'str_warning'});
	my $eval_result;
	
	# Was the module status set in the XML data file?
	if (defined ($module->{'status'})) {
		return 1 if (uc ($module->{'status'}) eq 'CRITICAL');
		return 2 if (uc ($module->{'status'}) eq 'WARNING');
		return 0 if (uc ($module->{'status'}) eq 'NORMAL');
	}

	# Set default critical max/min/str values
	$critical_str = (defined ($critical_str) && valid_regex ($critical_str) == 1) ? safe_output($critical_str) : '';
	$warning_str = (defined ($warning_str) && valid_regex ($warning_str) == 1) ? safe_output($warning_str) : '';
	
	if ($module_type =~ m/_proc$/ && ($critical_min eq $critical_max)) {
		($critical_min, $critical_max) = (0, 1);
	}
	elsif ($module_type =~ m/keep_alive/ && ($critical_min eq $critical_max)) {
		($critical_min, $critical_max) = (0, 1);
	}
	elsif ($module_type eq "log4x") {
		if ($critical_min eq $critical_max) {
			($critical_min, $critical_max) = (50, 61); # ERROR - FATAL
		}
		if ($warning_min eq $warning_max) {
			($warning_min, $warning_max) = (40, 41); # WARN - WARN
		}
	}
	
	# Numeric
	if ($module_type !~ m/_string/) {
			
		# Critical
		if ($critical_min ne $critical_max) {
			# [critical_min, critical_max)
			if ($module->{'critical_inverse'} == 0) {
				return 1 if ($data >= $critical_min && $data < $critical_max);
				return 1 if ($data >= $critical_min && $critical_max < $critical_min);
			}
			# (-inf, critical_min), [critical_max, +inf)
			else {
				return 1 if ($data < $critical_min || $data >= $critical_max);
				return 1 if ($data <= $critical_max && $critical_max < $critical_min);
			}
		}
	
		# Warning
		if ($warning_min ne $warning_max) {
			# [warning_min, warning_max)
			if ($module->{'warning_inverse'} == 0) {
				return 2 if ($data >= $warning_min && $data < $warning_max);
				return 2 if ($data >= $warning_min && $warning_max < $warning_min);
			}
			# (-inf, warning_min), [warning_max, +inf)
			else {
				return 2 if ($data < $warning_min || $data >= $warning_max);
				return 2 if ($data <= $warning_max && $warning_max < $warning_min);
			}
		}
	}
	# String
	else {

		# Critical
		$eval_result = eval {
			if ($module->{'critical_inverse'} == 0) {
				$critical_str ne '' && $data =~ /$critical_str/ ;
			} else {
				$critical_str ne '' && $data !~ /$critical_str/ ;
			}
		};
		return 1 if ($eval_result);

		# Warning
		$eval_result = eval {
			if ($module->{'warning_inverse'} == 0) {
				$warning_str ne '' && $data =~ /$warning_str/ ;
			} else {
				$warning_str ne '' && $data !~ /$warning_str/ ;
			}
		};
		return 2 if ($eval_result);
	}

	# Normal
	return 0;
}

##########################################################################
# Validate event.
# This validates all events pending to ACK for the same id_agent_module
##########################################################################
sub pandora_validate_event ($$$) {
	my ($pa_config, $id_agentmodule, $dbh) = @_;
	if (!defined($id_agentmodule) || $pa_config->{"event_auto_validation"} == 0) {
		return;
	}

	logger($pa_config, "Validating events for id_agentmodule #$id_agentmodule", 10);
	my $now = time();
	db_do ($dbh, 'UPDATE tevento SET estado = 1, ack_utimestamp = ? WHERE estado = 0 AND id_agentmodule = '.$id_agentmodule, $now);
}

##########################################################################
# Generates an event according to the change of status of a module.
##########################################################################
sub generate_status_event ($$$$$$$$) {
	my ($pa_config, $data, $agent, $module, $status, $last_status, $last_known_status, $dbh) = @_;
	my ($event_type, $severity);
	my $description = '';

	# No events when event storm protection is enabled
	if ($EventStormProtection == 1) {
		return;
	}

	# disable event just recovering from 'Unknown' without status change
	if($last_status == 3 && $status == $last_known_status && $module->{'disabled_types_event'} ) {
		my $disabled_types_event;
		eval {
			local $SIG{__DIE__};
			$disabled_types_event = decode_json($module->{'disabled_types_event'});
		};
		
		if ($disabled_types_event->{'going_unknown'}) {
			return;
		}
	}

	# Mark as "validated" any previous event for this module
	pandora_validate_event ($pa_config, $module->{'id_agente_modulo'}, $dbh);
	
	# Normal
	if ($status == 0) {
		
		# Do not generate an event when a module goes from notinit no normal
		if ($last_known_status == 4) {
			return;
		}
		
		($event_type, $severity) = ('going_down_normal', 2);
		$description = $pa_config->{"text_going_down_normal"};
	# Critical
	} elsif ($status == 1) {
		($event_type, $severity) = ('going_up_critical', 4);
		$description = $pa_config->{"text_going_up_critical"};
	# Warning
	} elsif ($status == 2) {
		
		# From critical
		if ($last_known_status == 1) {
			($event_type, $severity) = ('going_down_warning', 3);
			$description = $pa_config->{"text_going_down_warning"};
		}
		# From normal or warning (after becoming unknown)
		else {
			($event_type, $severity) = ('going_up_warning', 3);
			$description = $pa_config->{"text_going_up_warning"};
		}
	} else {
		# Unknown status
		logger($pa_config, "Unknown status $status for module '" . $module->{'nombre'} . "' agent '" . $agent->{'nombre'} . "'.", 10);
		return;
	}

	# Replace macros
	my %macros = (
		_module_ => safe_output($module->{'nombre'}),
		_data_ => safe_output($data),
	);
	load_module_macros ($module->{'module_macros'}, \%macros);
	$description = subst_alert_macros ($description, \%macros);

	# Generate the event
	if ($status != 0){
		pandora_event ($pa_config, $description, $agent->{'id_grupo'}, $module->{'id_agente'},
			$severity, 0, $module->{'id_agente_modulo'}, $event_type, 0, $dbh, 'Pandora', '', '', '', '', $module->{'critical_instructions'}, $module->{'warning_instructions'}, $module->{'unknown_instructions'});
	} else { 
		# Self validate this event if has "normal" status
		pandora_event ($pa_config, $description, $agent->{'id_grupo'}, $module->{'id_agente'},
			$severity, 0, $module->{'id_agente_modulo'}, $event_type, 1, $dbh, 'Pandora', '', '', '', '', $module->{'critical_instructions'}, $module->{'warning_instructions'}, $module->{'unknown_instructions'});
	}

}

##########################################################################
# Saves module data to the DB.
##########################################################################
sub save_module_data ($$$$$) {
	my ($data_object, $module, $module_type, $utimestamp, $dbh) = @_;

	if ($module_type eq "log4x") {
		#<module>
		#	<name></name>
		#	<type>log4x</type>
		#
		#	<severity></severity>
		#	<message></message>
		#	
		#	<stacktrace></stacktrace>
		#</module>

		my $sql = "INSERT INTO tagente_datos_log4x(id_agente_modulo, utimestamp, severity, message, stacktrace) values (?, ?, ?, ?, ?)";

		db_do($dbh, $sql, 
			$module->{'id_agente_modulo'}, $utimestamp,
			$data_object->{'severity'},
			$data_object->{'message'},
			$data_object->{'stacktrace'}
		);
	} else {
		my $data = $data_object->{'data'};
		my $table = ($module_type =~ m/_string/) ? 'tagente_datos_string' : 'tagente_datos';
		
		db_do($dbh, 'INSERT INTO ' . $table . ' (id_agente_modulo, datos, utimestamp)
					 VALUES (?, ?, ?)', $module->{'id_agente_modulo'}, $data, $utimestamp);	
	}
}

##########################################################################
# Export module data.
##########################################################################
sub export_module_data ($$$$$$$) {
	my ($pa_config, $data, $agent, $module, $module_type, $timestamp, $dbh) = @_;

	# TODO: If module is log4x we hope for the best :P
	#return if ($module_type == "log4x");
	
	# Data export is disabled
 	return if ($module->{'id_export'} < 1);

	logger($pa_config, "Exporting data for module '" . $module->{'nombre'} . "' agent '" . $agent->{'nombre'} . "'.", 10);
	db_do($dbh, 'INSERT INTO tserver_export_data 
		(id_export_server, agent_name , module_name, module_type, data, timestamp) VALUES
		(?, ?, ?, ?, ?, ?)', $module->{'id_export'}, $agent->{'nombre'}, $module->{'nombre'}, $module_type, $data, $timestamp);
}

##########################################################################
# Returns 1 if alerts for the given agent should be inhibited, 0 otherwise.
##########################################################################
#sub pandora_inhibit_alerts ($$$$) {
sub pandora_inhibit_alerts {
	my ($pa_config, $agent, $dbh, $depth) = @_;

	return 0 if ($agent->{'cascade_protection'} ne '1' || $agent->{'id_parent'} eq '0' || $depth > 1024);

	# Are any of the parent's critical alerts fired?	
	my $count = get_db_value ($dbh, 'SELECT COUNT(*) FROM tagente_modulo, talert_template_modules, talert_templates
				WHERE tagente_modulo.id_agente = ?
				AND tagente_modulo.id_agente_modulo = talert_template_modules.id_agent_module
				AND talert_template_modules.id_alert_template = talert_templates.id
				AND talert_template_modules.times_fired > 0
				AND talert_templates.priority = 4', $agent->{'id_parent'});
	return 1 if ($count > 0);
	
	

	# Check the parent's parent next
	$agent = get_db_single_row ($dbh, 'SELECT * FROM tagente WHERE id_agente = ?', $agent->{'id_parent'});
	return 0 unless defined ($agent);

	return pandora_inhibit_alerts ($pa_config, $agent, $dbh, $depth + 1);
}

##########################################################################
=head2 C<< save_agent_position (I<$pa_config>, I<$current_longitude>, I<$current_latitude>, 
		 I<$current_altitude>, I<$agent_id>, I<$dbh>, [I<$start_timestamp>], [I<$description>]) >>

Saves a new agent GIS information record in B<tgis_data_status> table. 

=cut
##########################################################################
sub save_agent_position($$$$$$;$$) {
	my ($pa_config, $current_longitude, $current_latitude, $current_altitude, $agent_id, $dbh, $start_timestamp, $description) = @_;
	
	logger($pa_config, "Updating agent position: longitude=$current_longitude, latitude=$current_latitude, altitude=$current_altitude, start_timestamp=$start_timestamp agent_id=$agent_id", 10);

	# Set some default values
	$description = '' if (!defined($description));
	$current_altitude = 0 if (!defined($current_altitude));

	my ($columns, $values) = db_insert_get_values ({ 'tagente_id_agente' => $agent_id,
	                                                 'current_longitude' => $current_longitude,
	                                                 'current_latitude' => $current_latitude,
	                                                 'current_altitude' => $current_altitude,
	                                                 'stored_longitude' => $current_longitude,
	                                                 'stored_latitude' => $current_latitude,
	                                                 'stored_altitude' => $current_altitude,
	                                                 'start_timestamp' => $start_timestamp,
	                                                 'description' => $description
	                                                });
	                                                
	db_do ($dbh, "INSERT INTO tgis_data_status $columns", @{$values});
}

##########################################################################
=head2 C<< update_agent_position (I<$pa_config>, I<$current_longitude>, I<$current_latitude>, I<$current_altitude>,
		I<$agent_id>, I<$dbh>, [I<$stored_longitude>], [I<$stored_latitude>], [I<$stored_altitude>], [I<$start_timestamp>], [I<$description>]) >>

Updates agent GIS information in B<tgis_data_status> table.

=cut
##########################################################################
sub update_agent_position($$$$$$;$$$$$) {
	my ($pa_config, $current_longitude, $current_latitude, $current_altitude,
		 $agent_id, $dbh, $stored_longitude, $stored_latitude, $stored_altitude, $start_timestamp, $description) = @_;

	if (defined($stored_longitude) && defined($stored_latitude) && defined($start_timestamp) ) {
		# Upadate all the position data of the agent
		logger($pa_config, "Updating agent position: current_longitude=$current_longitude, current_latitude=$current_latitude,
						 current_altitude=$current_altitude, stored_longitude=$stored_longitude, stored_latitude=$stored_latitude,
						 stored_altitude=$stored_altitude, start_timestamp=$start_timestamp, agent_id=$agent_id", 10);
		db_do ($dbh, 'UPDATE tgis_data_status SET current_longitude = ?, current_latitude = ?, current_altitude = ?,
				stored_longitude = ?,stored_latitude = ?,stored_altitude = ?, start_timestamp = ?, description = ?,
				number_of_packages = 1 WHERE tagente_id_agente = ?', 
				$current_longitude, $current_latitude, $current_altitude, $stored_longitude, $stored_latitude,
				$stored_altitude, $start_timestamp, $description, $agent_id);
	}
	else {
		logger($pa_config, "Updating agent position: longitude=$current_longitude, latitude=$current_latitude, altitude=$current_altitude, agent_id=$agent_id", 10);
		# Upadate the timestamp of the received agent
		db_do ($dbh, 'UPDATE tgis_data_status SET current_longitude = ?, current_latitude = ?, current_altitude = ?,
				number_of_packages = number_of_packages + 1 WHERE tagente_id_agente = ?', 
				$current_longitude, $current_latitude, $current_altitude, $agent_id);
	}
}

##########################################################################
=head2 C<< archive_agent_position (I<$pa_config>, I<$start_timestamp>, I<$end_timestamp>, I<$longitude>, I<$latitude>, I<$altitude>, I<$description>, 
I<$number_packages>, I<$agent_id>, I<$dbh>) >>
 
Archives the last position of an agent in the B<tgis_data_history> table

=cut
##########################################################################
sub archive_agent_position($$$$$$$$$$) {
	my ($pa_config, $start_timestamp, $end_timestamp, $longitude, $latitude, 
		$altitude, $description, $number_packages, $agent_id, $dbh) = @_;

	logger($pa_config, "Saving new agent position: start_timestamp=$start_timestamp longitude=$longitude latitude=$latitude altitude=$altitude", 10);

	db_do($dbh, 'INSERT INTO tgis_data_history (longitude, latitude, altitude, tagente_id_agente, start_timestamp,
					end_timestamp, description, number_of_packages) VALUES (?, ?, ?, ?, ?, ?, ?, ?)', 
					$longitude, $latitude, $altitude, $agent_id, $start_timestamp, $end_timestamp, $description, $number_packages);

}



##########################################################################
=head2 C<< pandora_server_statistics (I<$pa_config>, I<$dbh>) >>

Process server statistics for statistics table

=cut
##########################################################################
sub pandora_server_statistics ($$) {
	my ($pa_config, $dbh) = @_;

	my $lag_time= 0;
	my $lag_modules = 0;
	my $total_modules_running = 0;
	my $my_modules = 0;
	my $stat_utimestamp = 0;
	my $lag_row;

	# Get all servers with my name (each server only refresh it's own stats)
	my @servers = get_db_rows ($dbh, 'SELECT * FROM tserver WHERE name = ?', $pa_config->{'servername'});

	# For each server, update stats: Simple.
	foreach my $server (@servers) {

		# Inventory server
		if ($server->{"server_type"} == INVENTORYSERVER) {
			# Get modules exported by this server
			$server->{"modules"} = get_db_value ($dbh, "SELECT COUNT(tagent_module_inventory.id_agent_module_inventory) FROM tagente, tagent_module_inventory WHERE tagente.disabled=0 AND tagent_module_inventory.id_agente = tagente.id_agente AND tagente.server_name = ?", $server->{"name"});

			# Get total exported modules
			$server->{"modules_total"} = get_db_value ($dbh, "SELECT COUNT(tagent_module_inventory.id_agent_module_inventory) FROM tagente, tagent_module_inventory WHERE tagente.disabled=0 AND tagent_module_inventory.id_agente = tagente.id_agente");

			# Calculate lag
			$lag_row = get_db_single_row ($dbh, "SELECT COUNT(tagent_module_inventory.id_agent_module_inventory) AS module_lag, AVG(UNIX_TIMESTAMP() - utimestamp - tagent_module_inventory.interval) AS lag 
					FROM tagente, tagent_module_inventory
					WHERE utimestamp > 0
					AND tagent_module_inventory.id_agente = tagente.id_agente
					AND tagent_module_inventory.interval > 0
					AND tagente.server_name = ?
					AND (UNIX_TIMESTAMP() - utimestamp) < (tagent_module_inventory.interval * 10)
					AND (UNIX_TIMESTAMP() - utimestamp) > tagent_module_inventory.interval", $server->{"name"});
			$server->{"module_lag"} = $lag_row->{"module_lag"};
			$server->{"lag"} = $lag_row->{"lag"};
		}
		# Export server
		elsif ($server->{"server_type"} == EXPORTSERVER) {
	
			# Get modules exported by this server
			$server->{"modules"} = get_db_value ($dbh, "SELECT COUNT(tagente_modulo.id_agente_modulo) FROM tagente, tagente_modulo, tserver_export WHERE tagente.disabled=0 AND tagente_modulo.id_agente = tagente.id_agente AND tagente_modulo.id_export = tserver_export.id AND tserver_export.id_export_server = ?", $server->{"id_server"});

			# Get total exported modules
			$server->{"modules_total"} = get_db_value ($dbh, "SELECT COUNT(tagente_modulo.id_agente_modulo) FROM tagente, tagente_modulo WHERE tagente.disabled=0 AND tagente_modulo.id_agente = tagente.id_agente AND tagente_modulo.id_export != 0");
		
			$server->{"lag"} = 0;
			$server->{"module_lag"} = 0;
		# Recon server
		} elsif ($server->{"server_type"} == RECONSERVER) {

				# Total jobs running on this recon server
				$server->{"modules"} = get_db_value ($dbh, "SELECT COUNT(id_rt) FROM trecon_task WHERE id_recon_server = ?", $server->{"id_server"});
		
				# Total recon jobs (all servers)
				$server->{"modules_total"} = get_db_value ($dbh, "SELECT COUNT(status) FROM trecon_task");
		
				# Lag (take average active time of all active tasks)			

				$server->{"lag"} = get_db_value ($dbh, "SELECT UNIX_TIMESTAMP() - utimestamp from trecon_task WHERE UNIX_TIMESTAMP() > (utimestamp + interval_sweep) AND id_recon_server = ?", $server->{"id_server"});

				$server->{"module_lag"} = get_db_value ($dbh, "SELECT COUNT(id_rt) FROM trecon_task WHERE UNIX_TIMESTAMP() > (utimestamp + interval_sweep) AND id_recon_server = ?", $server->{"id_server"});

		}
		else {

			# Get LAG
			$server->{"modules"} = get_db_value ($dbh, "SELECT count(tagente_estado.id_agente_modulo) FROM tagente_estado, tagente_modulo, tagente WHERE tagente.disabled=0 AND tagente_modulo.id_agente = tagente.id_agente AND tagente_modulo.disabled = 0 AND tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo AND tagente_estado.running_by = ?", $server->{"id_server"});

			$server->{"modules_total"} = get_db_value ($dbh,"SELECT count(tagente_estado.id_agente_modulo) FROM tserver, tagente_estado, tagente_modulo, tagente WHERE tagente.disabled=0 AND tagente_modulo.id_agente = tagente.id_agente AND tagente_modulo.disabled = 0 AND tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo AND tagente_estado.running_by = tserver.id_server AND tserver.server_type = ?", $server->{"server_type"});

			# Non-dataserver LAG calculation:
			if ($server->{"server_type"} != DATASERVER){
				
				$lag_row = get_db_single_row ($dbh, "SELECT COUNT(tagente_modulo.id_agente_modulo) AS module_lag, AVG(UNIX_TIMESTAMP() - utimestamp - current_interval) AS lag 
					FROM tagente_estado, tagente_modulo
					WHERE utimestamp > 0
					AND tagente_modulo.disabled = 0
					AND tagente_modulo.id_tipo_modulo < 5 
					AND tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo
					AND current_interval > 0
					AND (UNIX_TIMESTAMP() - utimestamp) < ( current_interval * 10)
					AND running_by = ?
					AND (UNIX_TIMESTAMP() - utimestamp) > (current_interval * 1.1)", $server->{"id_server"});
			}
			# Dataserver LAG calculation:
			else {
				$lag_row = get_db_single_row ($dbh, "SELECT COUNT(tagente_modulo.id_agente_modulo) AS module_lag, AVG(UNIX_TIMESTAMP() - utimestamp - current_interval) AS lag 
					FROM tagente_estado, tagente_modulo
					WHERE utimestamp > 0
					AND tagente_modulo.disabled = 0
					AND tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo
					AND current_interval > 0
					AND running_by = ?
					AND (UNIX_TIMESTAMP() - utimestamp) < ( current_interval * 10)
					AND (UNIX_TIMESTAMP() - utimestamp) > current_interval", $server->{"id_server"});
			}
			
			$server->{"module_lag"} = $lag_row->{'module_lag'};
			$server->{"lag"} = $lag_row->{'lag'};
		}

		# Check that all values are defined and set to 0 if not

		if (!defined($server->{"lag"})){
			$server->{"lag"} = 0;
		}

		if (!defined($server->{"module_lag"})){
			$server->{"module_lag"} = 0;
		}

		if (!defined($server->{"modules_total"})){
			$server->{"modules_total"} = 0;
		}

		if (!defined($server->{"modules"})){
			$server->{"modules"} = 0;
		}

		# Update server record
		db_do ($dbh, "UPDATE tserver SET lag_time = '".$server->{"lag"}."', lag_modules = '".$server->{"module_lag"}."', total_modules_running = '".$server->{"modules_total"}."', my_modules = '".$server->{"modules"}."' , stat_utimestamp = UNIX_TIMESTAMP() WHERE id_server = " . $server->{"id_server"} );
	}
}

##########################################################################
=head2 C<< pandora_process_policy_queue (I<$pa_config>, I<$dbh>) >>

Process groups statistics for statistics table

=cut
##########################################################################
sub pandora_process_event_replication ($) {
	my $pa_config = shift;
	
	my %pa_config = %{$pa_config};

	# Get the console DB connection
	my $dbh = db_connect ($pa_config{'dbengine'}, $pa_config{'dbname'}, $pa_config{'dbhost'}, $pa_config{'dbport'},
						$pa_config{'dbuser'}, $pa_config{'dbpass'});

	my $is_event_replication_enabled = enterprise_hook('get_event_replication_flag', [$dbh]);
	my $replication_interval = enterprise_hook('get_event_replication_interval', [$dbh]);
		
	# If there are not installed the enterprise version,  
	# desactivated the event replication or the replication
	# interval is wrong: abort
	if($is_event_replication_enabled == 0) {
		return;
	}
	
	if($replication_interval <= 0) {
		logger($pa_config, "Replication interval configuration is not a value greater than 0. Event replication thread will be aborted.", 1);
		return;
	}
	
	# Get the metaconsole DB connection
	my $dbh_metaconsole = enterprise_hook('get_metaconsole_dbh', [$pa_config, $dbh]);
	
	if($dbh_metaconsole eq '') {
		logger($pa_config, "Metaconsole DB connection error. Event replication thread will be aborted.", 1);
		return;
	}
	
	# Get server id on metaconsole
	my $server_name = get_first_server_name($dbh);
	my $metaconsole_server_id = -1;
	if($server_name ne '') {
		$metaconsole_server_id = enterprise_hook('get_metaconsole_setup_server_id', [$dbh_metaconsole,$server_name]);
	}

	# If the server name is not found in metaconsole setup: abort
	if($metaconsole_server_id == -1) {
		logger($pa_config, "The server name is not configured in metaconsole. Event replication thread will be aborted.", 1);
		return;
	}
	
	my $replication_mode = enterprise_hook('get_event_replication_mode', [$dbh]);
				
	logger($pa_config, "Starting replication events process.", 1);

	while(1) { 
		# Check the queue each N seconds
		sleep ($replication_interval);
		enterprise_hook('pandora_replicate_copy_events',[$pa_config, $dbh, $dbh_metaconsole, $metaconsole_server_id, $replication_mode]);
	}
}

##########################################################################
=head2 C<< pandora_process_policy_queue (I<$pa_config>, I<$dbh>) >>

Process groups statistics for statistics table

=cut
##########################################################################
sub pandora_process_policy_queue ($) {
	my $pa_config = shift;
	
	my %pa_config = %{$pa_config};
	
	my $dbh = db_connect ($pa_config{'dbengine'}, $pa_config{'dbname'}, $pa_config{'dbhost'}, $pa_config{'dbport'},
						$pa_config{'dbuser'}, $pa_config{'dbpass'});

	logger($pa_config, "Starting policy queue patrol process.", 1);

	while(1) {
		# Check the queue each 5 seconds
		sleep (5);
		
		my $operation = enterprise_hook('get_first_policy_queue', [$dbh]);
		next unless (defined ($operation) && $operation ne '');

		if($operation->{'operation'} eq 'apply' || $operation->{'operation'} eq 'apply_db') {
			enterprise_hook('pandora_apply_policy', [$dbh, $pa_config, $operation->{'id_policy'}, $operation->{'id_agent'}, $operation->{'id'}, $operation->{'operation'}]);
		}
		elsif($operation->{'operation'} eq 'delete') {
			if($operation->{'id_agent'} == 0) {
				enterprise_hook('pandora_purge_policy_agents', [$dbh, $pa_config, $operation->{'id_policy'}]);
			}
			else {
				enterprise_hook('pandora_delete_agent_from_policy', [$dbh, $pa_config, $operation->{'id_policy'}, $operation->{'id_agent'}]);
			}
		}
		
		enterprise_hook('pandora_finish_queue_operation', [$dbh, $operation->{'id'}]);
	}	
}

##########################################################################
=head2 C<< pandora_group_statistics (I<$pa_config>, I<$dbh>) >>

Process groups statistics for statistics table

=cut
##########################################################################
sub pandora_group_statistics ($$) {
	my ($pa_config, $dbh) = @_;
	
	# Variable init
	my $modules = 0;
	my $normal = 0;
	my $critical = 0;
	my $warning = 0;
	my $unknown = 0;
	my $non_init = 0;
	my $alerts = 0;
	my $alerts_fired = 0;
	my $agents = 0;
	my $agents_unknown = 0;
	my $utimestamp = 0;
	my $group = 0;

	# Get all groups
	my @groups = get_db_rows ($dbh, 'SELECT id_grupo FROM tgrupo');

	# For each valid group get the stats: Simple uh?
	foreach my $group_row (@groups) {

		$group = $group_row->{'id_grupo'};

		# NOTICE - Calculations done here MUST BE the same than used in PHP code to have
		# the same criteria. PLEASE, double check any changes here and in functions_groups.php
		$agents_unknown = get_db_value ($dbh, "SELECT COUNT(*) FROM tagente WHERE tagente.disabled=0 AND critical_count=0 AND warning_count=0 AND unknown_count>0 AND id_grupo=?", $group);
		$agents_unknown = 0 unless defined ($agents_unknown);

		$agents = get_db_value ($dbh, "SELECT COUNT(*) FROM tagente WHERE id_grupo = $group AND disabled = 0");
		$agents = 0 unless defined ($agents);

		$modules = get_db_value ($dbh, "SELECT COUNT(tagente_estado.id_agente_estado) FROM tagente_estado, tagente, tagente_modulo WHERE tagente.id_grupo = $group AND tagente.disabled = 0 AND tagente_estado.id_agente = tagente.id_agente AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo AND tagente_modulo.disabled = 0");
		$modules = 0 unless defined ($modules);
		
		$normal = get_db_value ($dbh, "SELECT COUNT(*) FROM tagente WHERE tagente.disabled=0 AND normal_count=total_count AND id_grupo=?", $group);
		$normal = 0 unless defined ($normal);
		
		$critical = get_db_value ($dbh, "SELECT COUNT(*) FROM tagente WHERE tagente.disabled=0 AND critical_count>0 AND id_grupo=?", $group);
		$critical = 0 unless defined ($critical);
		
		$warning = get_db_value ($dbh, "SELECT COUNT(*) FROM tagente WHERE tagente.disabled=0 AND critical_count=0 AND warning_count>0 AND id_grupo=?", $group);
		$warning = 0 unless defined ($warning);
	
		$unknown = get_db_value ($dbh, "SELECT COUNT(*) FROM tagente WHERE tagente.disabled=0 AND critical_count=0 AND warning_count=0 AND unknown_count>0 AND id_grupo=?", $group);	
		$unknown = 0 unless defined ($unknown);
		
		$non_init = get_db_value ($dbh, "SELECT COUNT(*) FROM tagente WHERE disabled=0 AND critical_count=0 AND warning_count=0 AND unknown_count=0 AND notinit_count>0 AND id_grupo=?", $group);
		$non_init = 0 unless defined ($non_init);
		
		$alerts = get_db_value ($dbh, "SELECT COUNT(talert_template_modules.id)
				FROM talert_template_modules, tagente_modulo, tagente
				WHERE tagente.id_grupo = $group AND tagente_modulo.id_agente = tagente.id_agente
					AND tagente_modulo.disabled = 0 AND tagente.disabled = 0  			
					AND	talert_template_modules.disabled = 0 
					AND talert_template_modules.id_agent_module = tagente_modulo.id_agente_modulo");
		$alerts = 0 unless defined ($alerts);
		
		$alerts_fired = get_db_value ($dbh, "SELECT COUNT(talert_template_modules.id)
				FROM talert_template_modules, tagente_modulo, tagente
				WHERE tagente.id_grupo = $group AND tagente_modulo.id_agente = tagente.id_agente
					AND tagente_modulo.disabled = 0 AND tagente.disabled = 0 
					AND talert_template_modules.disabled = 0 
					AND talert_template_modules.id_agent_module = tagente_modulo.id_agente_modulo 
					AND times_fired > 0");
		$alerts_fired = 0 unless defined ($alerts_fired);
		
		# Update the record.
		db_do ($dbh, "DELETE FROM tgroup_stat WHERE id_group = $group");
		db_do ($dbh, "INSERT INTO tgroup_stat (id_group, modules, normal, critical, warning, unknown, " . $PandoraFMS::DB::RDBMS_QUOTE . 'non-init' . $PandoraFMS::DB::RDBMS_QUOTE . ", alerts, alerts_fired, agents, agents_unknown, utimestamp) VALUES ($group, $modules, $normal, $critical, $warning, $unknown, $non_init, $alerts, $alerts_fired, $agents, $agents_unknown, UNIX_TIMESTAMP())");

	}

}


##########################################################################
=head2 C<< pandora_self_monitoring (I<$pa_config>, I<$dbh>) >>

Pandora self monitoring process

=cut
##########################################################################

sub pandora_self_monitoring ($$) {
	my ($pa_config, $dbh) = @_;
	my $timezone_offset = 0; # PENDING (TODO) !
	my $utimestamp = time ();
	my $timestamp = strftime ("%Y-%m-%d %H:%M:%S", localtime());

	my $xml_output = "";
	
	$xml_output = "<agent_data os_name='Linux' os_version='".$pa_config->{'version'}."' agent_name='".$pa_config->{'servername'}."' interval='".$pa_config->{"self_monitoring_interval"}."' timestamp='".$timestamp."' >";
	$xml_output .=" <module>";
	$xml_output .=" <name>Status</name>";
	$xml_output .=" <type>generic_proc</type>";
	$xml_output .=" <data>1</data>";
	$xml_output .=" </module>";

	my $load_average = load_average();
	$load_average = '' unless defined ($load_average);
	my $free_mem = free_mem();
	$free_mem = '' unless defined ($free_mem);
	my $free_disk_spool = disk_free ($pa_config->{"incomingdir"});
	$free_disk_spool = '' unless defined ($free_disk_spool);
	my $my_data_server = get_db_value ($dbh, "SELECT id_server FROM tserver WHERE server_type = ? AND name = '".$pa_config->{"servername"}."'", DATASERVER);

	# Number of unknown agents
	my $agents_unknown = 0;
	if (defined ($my_data_server)) {
		$agents_unknown = get_db_value ($dbh, "SELECT COUNT(DISTINCT tagente_estado.id_agente)
		                                       FROM tagente_estado, tagente, tagente_modulo
		                                       WHERE tagente.disabled = 0 AND tagente.id_agente = tagente_estado.id_agente
		                                       AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
		                                       AND tagente_modulo.disabled = 0
		                                       AND running_by = $my_data_server
		                                       AND estado = 3");
		$agents_unknown = 0 if (!defined($agents_unknown));
	}
	
	my $queued_modules = get_db_value ($dbh, "SELECT SUM(queued_modules) FROM tserver WHERE name = '".$pa_config->{"servername"}."'");
	
	if (!defined($queued_modules)) {
		$queued_modules = 0;
	}
	
	my $dbmaintance;
	if ($RDBMS eq 'postgresql') {
		$dbmaintance = get_db_value ($dbh,
			"SELECT COUNT(*)
			FROM tconfig
			WHERE token = 'db_maintance'
				AND NULLIF(value, '')::int > UNIX_TIMESTAMP() - 86400");
	}
	else {
		$dbmaintance = get_db_value ($dbh,
			"SELECT COUNT(*)
			FROM tconfig
			WHERE token = 'db_maintance' AND value > UNIX_TIMESTAMP() - 86400");
	}
	
	$xml_output .=" <module>";
	$xml_output .=" <name>Database Maintenance</name>";
	$xml_output .=" <type>generic_proc</type>";
	$xml_output .=" <data>$dbmaintance</data>";
	$xml_output .=" </module>";
	
	$xml_output .=" <module>";
	$xml_output .=" <name>Queued_Modules</name>";
	$xml_output .=" <type>generic_data</type>";
	$xml_output .=" <data>$queued_modules</data>";
	$xml_output .=" </module>";
	
	$xml_output .=" <module>";
	$xml_output .=" <name>Agents_Unknown</name>";
	$xml_output .=" <type>generic_data</type>";
	$xml_output .=" <data>$agents_unknown</data>";
	$xml_output .=" </module>";
	
	$xml_output .=" <module>";
	$xml_output .=" <name>System_Load_AVG</name>";
	$xml_output .=" <type>generic_data</type>";
	$xml_output .=" <data>$load_average</data>";
	$xml_output .=" </module>";
	
	$xml_output .=" <module>";
	$xml_output .=" <name>Free_RAM</name>";
	$xml_output .=" <type>generic_data</type>";
	$xml_output .=" <data>$free_mem</data>";
	$xml_output .=" </module>";
	
	$xml_output .=" <module>";
	$xml_output .=" <name>FreeDisk_SpoolDir</name>";
	$xml_output .=" <type>generic_data</type>";
	$xml_output .=" <data>$free_disk_spool</data>";
	$xml_output .=" </module>";
	
	$xml_output .= "</agent_data>";
	
	my $filename = $pa_config->{"incomingdir"}."/".$pa_config->{'servername'}.".self.".$utimestamp.".data";
	
	open (XMLFILE, ">> $filename") or die "[FATAL] Could not open internal monitoring XML file for deploying monitorization at '$filename'";
	print XMLFILE $xml_output;
	close (XMLFILE);
}

##########################################################################
=head2 C<< set_master (I<$pa_config>, I<$dbh>) >> 

Set the current master server.

=cut
##########################################################################
sub pandora_set_master ($$) {
	my ($pa_config, $dbh) = @_;
	
	my $current_master = get_db_value ($dbh, 'SELECT name FROM tserver 
	                                  WHERE master <> 0 AND status = 1
									  ORDER BY master DESC LIMIT 1');
	return unless defined($current_master) and ($current_master ne $Master);

	logger($pa_config, "Server $current_master is the current master.", 1);
	$Master = $current_master;
}

##########################################################################
=head2 C<< is_master (I<$pa_config>) >> 

Returns 1 if this server is the current master, 0 otherwise.

=cut
##########################################################################
sub pandora_is_master ($) {
	my ($pa_config) = @_;

	if ($Master eq $pa_config->{'servername'}) {
		return 1;
	}

	return 0;
}


##########################################################################
=head2 C<< pandora_module_unknown (I<$pa_config>, I<$dbh>) >> 

Set the status of unknown modules.

=cut
##########################################################################
sub pandora_module_unknown ($$) {
	my ($pa_config, $dbh) = @_;
	
	my @modules = get_db_rows ($dbh, 'SELECT tagente_modulo.*,
			tagente_estado.id_agente_estado, tagente_estado.estado
		FROM tagente_modulo, tagente_estado, tagente 
		WHERE tagente.id_agente = tagente_estado.id_agente 
			AND tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo 
			AND tagente.disabled = 0 
			AND tagente_modulo.disabled = 0 
			AND ((tagente_estado.estado <> 3 AND tagente_modulo.id_tipo_modulo NOT IN (21, 22, 23, 100))
				OR (tagente_estado.estado <> 0 AND tagente_modulo.id_tipo_modulo IN (21, 22, 23)))
			AND tagente_estado.utimestamp != 0
			AND (tagente_estado.current_interval * 2) + tagente_estado.utimestamp < UNIX_TIMESTAMP()');
	
	foreach my $module (@modules) {
		
		# Async
		if ($module->{'id_tipo_modulo'} == 21 ||
			$module->{'id_tipo_modulo'} == 22 ||
			$module->{'id_tipo_modulo'} == 23) {

			next if ($pa_config->{"async_recovery"} == 0);
			
			# Set the module state to normal
			logger ($pa_config, "Module " . $module->{'nombre'} . " is going to NORMAL", 10);
			db_do ($dbh, 'UPDATE tagente_estado SET last_status = 0, estado = 0 WHERE id_agente_estado = ?', $module->{'id_agente_estado'});
			
			# Get agent information
			my $agent = get_db_single_row ($dbh, 'SELECT *
				FROM tagente
				WHERE id_agente = ?', $module->{'id_agente'});
			
			if (! defined ($agent)) {
				logger($pa_config, "Agent ID " . $module->{'id_agente'} . " not found while executing unknown alerts for module '" . $module->{'nombre'} . "'.", 3);
				return;
			}
			
			# Update module status count
			pandora_mark_agent_for_module_update ($dbh, $module->{'id_agente'});
			
			# Generate alerts
			if (pandora_inhibit_alerts ($pa_config, $agent, $dbh, 0) == 0) {
				pandora_generate_alerts ($pa_config, 0, 3, $agent, $module, time (), $dbh, undef, undef, 0, 'unknown');
			}
			else {
				logger($pa_config, "Alerts inhibited for agent '" . $agent->{'nombre'} . "'.", 10);
			}
			
			# Generate event with severity minor
			my ($event_type, $severity) = ('going_down_normal', 5);
			my $description = $pa_config->{"text_going_down_normal"};

			# Replace macros
			my %macros = (
				_module_ => safe_output($module->{'nombre'}),
				_data_ => 'N/A',
			);
		        load_module_macros ($module->{'module_macros'}, \%macros);
			$description = subst_alert_macros ($description, \%macros, $pa_config, $dbh, $agent, $module);

			pandora_event ($pa_config, $description, $agent->{'id_grupo'}, $module->{'id_agente'},
				$severity, 0, $module->{'id_agente_modulo'}, $event_type, 0, $dbh, 'Pandora', '', '', '', '', $module->{'critical_instructions'}, $module->{'warning_instructions'}, $module->{'unknown_instructions'});
		}
		# Regular module
		else {
			# Set the module state to unknown
			logger ($pa_config, "Module " . $module->{'nombre'} . " is going to UNKNOWN", 10);
			db_do ($dbh, 'UPDATE tagente_estado SET last_status = 3, estado = 3 WHERE id_agente_estado = ?', $module->{'id_agente_estado'});
			
			# Get agent information
			my $agent = get_db_single_row ($dbh, 'SELECT * FROM tagente WHERE id_agente = ?', $module->{'id_agente'});
			if (! defined ($agent)) {
				logger($pa_config, "Agent ID " . $module->{'id_agente'} . " not found while executing unknown alerts for module '" . $module->{'nombre'} . "'.", 3);
				return;
			}
			
			# Update module status count
			pandora_mark_agent_for_module_update ($dbh, $module->{'id_agente'});
			
			# Generate alerts
			if (pandora_inhibit_alerts ($pa_config, $agent, $dbh, 0) == 0) {
				pandora_generate_alerts ($pa_config, 0, 3, $agent, $module, time (), $dbh, undef, undef, 0, 'unknown');
			}
			else {
				logger($pa_config, "Alerts inhibited for agent '" . $agent->{'nombre'} . "'.", 10);
			}
			
			my $do_event = 0;
			if ($module->{'disabled_types_event'} eq "") {
				$do_event = 1;
			}
			else {
				my $disabled_types_event;
				eval {
					local $SIG{__DIE__};
					$disabled_types_event = decode_json($module->{'disabled_types_event'});
				};
				
				if ($disabled_types_event->{'going_unknown'}) {
					$do_event = 0;
				}
				else {
					$do_event = 1;
				}
			}
			
			# Generate event with severity minor
			if ($do_event) {
				my ($event_type, $severity) = ('going_unknown', 5);
				my $description = $pa_config->{"text_going_unknown"};

		        # Replace macros
		        my %macros = (
		                _module_ => safe_output($module->{'nombre'}),
		        );
		        load_module_macros ($module->{'module_macros'}, \%macros);
		        $description = subst_alert_macros ($description, \%macros, $pa_config, $dbh, $agent, $module);
		        
				pandora_event ($pa_config, $description, $agent->{'id_grupo'}, $module->{'id_agente'},
					$severity, 0, $module->{'id_agente_modulo'}, $event_type, 0, $dbh, 'Pandora', '', '', '', '', $module->{'critical_instructions'}, $module->{'warning_instructions'}, $module->{'unknown_instructions'});
			}
		}
	}
}

##########################################################################
=head2 C<< get_module_tags (I<$pa_config>, I<$dbh>, I<$id_agentmodule>) >> 

Get a list of module tags in the format: |tag|tag| ... |tag|

=cut
##########################################################################
sub pandora_get_module_tags ($$$) {
	my ($pa_config, $dbh, $id_agentmodule) = @_;
	
	#~ my @tags = get_db_rows ($dbh, 'SELECT ' . db_concat('ttag.name', 'ttag.url') . ' name_url FROM ttag, ttag_module
	my @tags = get_db_rows ($dbh, 'SELECT ttag.name FROM ttag, ttag_module
	                               WHERE ttag.id_tag = ttag_module.id_tag
	                               AND ttag_module.id_agente_modulo = ?', $id_agentmodule);
	
	# No tags found
	return '' if ($#tags < 0);

	my $tag_string = '';
	foreach my $tag (@tags) {
		$tag_string .=  $tag->{'name'} . ',';
	}
	
	# Remove the trailing ','
	chop ($tag_string);
	return $tag_string;
}

##########################################################################
=head2 C<< get_module_url_tags (I<$pa_config>, I<$dbh>, I<$id_agentmodule>) >> 

Get a list of module tags in the format: |url|url| ... |url|

=cut
##########################################################################
sub pandora_get_module_url_tags ($$$) {
	my ($pa_config, $dbh, $id_agentmodule) = @_;
	
	my @tags = get_db_rows ($dbh, 'SELECT ttag.name,ttag.url name_url FROM ttag, ttag_module
	                               WHERE ttag.id_tag = ttag_module.id_tag
	                               AND ttag_module.id_agente_modulo = ?', $id_agentmodule);
	
	# No tags found
	return '' if ($#tags < 0);

	my $tag_string = '';
	foreach my $tag (@tags) {
		$tag_string .=  $tag->{'name_url'} . ',';
	}
	
	# Remove the trailing ','
	chop ($tag_string);
	return $tag_string;
}

##########################################################################
=head2 C<< get_module_email_tags (I<$pa_config>, I<$dbh>, I<$id_agentmodule>) >> 

Get a list of email module tags in the format: email,email,...,email

=cut
##########################################################################
sub pandora_get_module_email_tags ($$$) {
	my ($pa_config, $dbh, $id_agentmodule) = @_;
	
	my @email_tags = get_db_rows ($dbh, 'SELECT ttag.email FROM ttag, ttag_module
	                               WHERE ttag.id_tag = ttag_module.id_tag
	                               AND ttag_module.id_agente_modulo = ?', $id_agentmodule);
	
	# No tags found
	return '' if ($#email_tags < 0);

	my $email_tag_string = '';
	foreach my $email_tag (@email_tags) {
		next if ($email_tag->{'email'} eq '');
		$email_tag_string .=  $email_tag->{'email'} . ',';
	}
	
	# Remove the trailing '|'
	chop ($email_tag_string);

	return $email_tag_string;
}

##########################################################################
=head2 C<< get_module_phone_tags (I<$pa_config>, I<$dbh>, I<$id_agentmodule>) >> 

Get a list of phone module tags in the format: phone,phone,...,phone

=cut
##########################################################################
sub pandora_get_module_phone_tags ($$$) {
	my ($pa_config, $dbh, $id_agentmodule) = @_;
	
	my @phone_tags = get_db_rows ($dbh, 'SELECT ttag.phone FROM ttag, ttag_module
	                               WHERE ttag.id_tag = ttag_module.id_tag
	                               AND ttag_module.id_agente_modulo = ?', $id_agentmodule);
	
	# No tags found
	return '' if ($#phone_tags < 0);

	my $phone_tag_string = '';
	foreach my $phone_tag (@phone_tags) {
		next if ($phone_tag->{'phone'} eq '');
		$phone_tag_string .=  $phone_tag->{'phone'} . ',';
	}
	
	# Remove the trailing ','
	chop ($phone_tag_string);
	
	return $phone_tag_string;
}


##########################################################################
# Mark an agent for module status count update.
##########################################################################
sub pandora_mark_agent_for_module_update ($$) {
	my ($dbh, $agent_id) = @_;

	# Update the status count
	db_do ($dbh, "UPDATE tagente SET update_module_count=1 WHERE id_agente=?", $agent_id);
}

##########################################################################
# Mark an agent for fired alert count update.
##########################################################################
sub pandora_mark_agent_for_alert_update ($$) {
	my ($dbh, $agent_id) = @_;

	# Update the status count
	db_do ($dbh, "UPDATE tagente SET update_alert_count=1 WHERE id_agente=?", $agent_id);
}

##########################################################################
# Set or unset silent mode.
##########################################################################
sub pandora_set_event_storm_protection ($) {
	$EventStormProtection = shift;
}

##########################################################################
# Update the module status count of an agent.
##########################################################################
sub pandora_update_agent_count ($$) {
	my ($dbh, $agent_id) = @_;
	
	db_do ($dbh, 'UPDATE tagente SET update_module_count=0,
	normal_count=(SELECT COUNT(*) FROM tagente_modulo, tagente_estado WHERE tagente_modulo.id_agente_modulo=tagente_estado.id_agente_modulo AND tagente_modulo.id_agente=' . $agent_id . ' AND estado=0),
	critical_count=(SELECT COUNT(*) FROM tagente_modulo, tagente_estado WHERE tagente_modulo.id_agente_modulo=tagente_estado.id_agente_modulo AND tagente_modulo.id_agente=' . $agent_id . ' AND estado=1),
	warning_count=(SELECT COUNT(*) FROM tagente_modulo, tagente_estado WHERE tagente_modulo.id_agente_modulo=tagente_estado.id_agente_modulo AND tagente_modulo.id_agente=' . $agent_id . ' AND estado=2),
	unknown_count=(SELECT COUNT(*) FROM tagente_modulo, tagente_estado WHERE tagente_modulo.id_agente_modulo=tagente_estado.id_agente_modulo AND tagente_modulo.id_agente=' . $agent_id . ' AND estado=3),
	notinit_count=(SELECT COUNT(*) FROM tagente_modulo, tagente_estado WHERE tagente_modulo.id_agente_modulo=tagente_estado.id_agente_modulo AND tagente_modulo.id_agente=' . $agent_id . ' AND estado=4),
	total_count=(SELECT COUNT(*) FROM tagente_modulo, tagente_estado WHERE tagente_modulo.id_agente_modulo=tagente_estado.id_agente_modulo AND tagente_modulo.id_agente=' . $agent_id .
	') WHERE id_agente = ' . $agent_id);
}

##########################################################################
# Update the module status count of an agent.
##########################################################################
sub pandora_update_agent_module_count ($$) {
	my ($dbh, $agent_id) = @_;
	
	db_do ($dbh, 'UPDATE tagente SET update_module_count=0,
	normal_count=(SELECT COUNT(*) FROM tagente_modulo, tagente_estado WHERE tagente_modulo.disabled=0 AND tagente_modulo.id_agente_modulo=tagente_estado.id_agente_modulo AND tagente_modulo.id_agente=' . $agent_id . ' AND estado=0),
	critical_count=(SELECT COUNT(*) FROM tagente_modulo, tagente_estado WHERE tagente_modulo.disabled=0 AND tagente_modulo.id_agente_modulo=tagente_estado.id_agente_modulo AND tagente_modulo.id_agente=' . $agent_id . ' AND estado=1),
	warning_count=(SELECT COUNT(*) FROM tagente_modulo, tagente_estado WHERE tagente_modulo.disabled=0 AND tagente_modulo.id_agente_modulo=tagente_estado.id_agente_modulo AND tagente_modulo.id_agente=' . $agent_id . ' AND estado=2),
	unknown_count=(SELECT COUNT(*) FROM tagente_modulo, tagente_estado WHERE tagente_modulo.disabled=0 AND tagente_modulo.id_agente_modulo=tagente_estado.id_agente_modulo AND tagente_modulo.id_agente=' . $agent_id . ' AND estado=3),
	notinit_count=(SELECT COUNT(*) FROM tagente_modulo, tagente_estado WHERE tagente_modulo.disabled=0 AND tagente_modulo.id_agente_modulo=tagente_estado.id_agente_modulo AND tagente_modulo.id_agente=' . $agent_id . ' AND estado=4),
	total_count=(SELECT COUNT(*) FROM tagente_modulo, tagente_estado WHERE tagente_modulo.disabled=0 AND tagente_modulo.id_agente_modulo=tagente_estado.id_agente_modulo AND tagente_modulo.id_agente=' . $agent_id .
	') WHERE id_agente = ' . $agent_id);
}

##########################################################################
# Update the fired alert count of an agent.
##########################################################################
sub pandora_update_agent_alert_count ($$) {
	my ($dbh, $agent_id) = @_;
	
	db_do ($dbh, 'UPDATE tagente SET update_alert_count=0,
	fired_count=(SELECT COUNT(*) FROM tagente_modulo, talert_template_modules WHERE tagente_modulo.disabled=0 AND tagente_modulo.id_agente_modulo=talert_template_modules.id_agent_module AND talert_template_modules.disabled=0 AND times_fired>0 AND id_agente=' . $agent_id .
	') WHERE id_agente = ' . $agent_id);
}

########################################################################
# SUB pandora_get_os (string)
# Detect OS using a string, and return id_os
########################################################################
sub pandora_get_os ($$) {
	my ($dbh, $os) = @_;
	
	if (! defined($os) || $os eq "") {
		# Other OS
		return 10;
	}
	
	if ($os =~ m/Windows/i) {
		return 9;
	}
	if ($os =~ m/Cisco/i) {
		return 7;
	}
	if ($os =~ m/SunOS/i || $os =~ m/Solaris/i) {
		return 2;
	}
	if ($os =~ m/AIX/i) {
		return 3;
	}
	if ($os =~ m/HP\-UX/i) {
		return 5;
	}
	if ($os =~ m/Apple/i || $os =~ m/Darwin/i) {
		return 8;
	}
	if ($os =~ m/Linux/i) {
		return 1;
	}
	if ($os =~ m/Enterasys/i || $os =~ m/3com/i) {
		return 11;
	}
	if ($os =~ m/Octopods/i) {
		return 13;
	}
	if ($os =~ m/embedded/i) {
		return 14;
	}
	if ($os =~ m/android/i) {
		return 15;
	}
	if ($os =~ m/BSD/i) {
		return 4;
	}
		
	# Search for a custom OS
	my $os_id = get_db_value ($dbh, 'SELECT id_os FROM tconfig_os WHERE name LIKE ?', '%' . $os . '%');
	if (defined ($os_id)) {
		return $os_id;
	}

	# Other OS
	return 10;
}

########################################################################
# Load module macros (a base 64 encoded JSON document) into the macro
# hash.
########################################################################
sub load_module_macros ($$) {
	my ($macros, $macro_hash) = @_;
	
	# Decode and parse module macros
	my $decoded_macros = {};
	eval {
		local $SIG{__DIE__};
		$decoded_macros = decode_json (decode_base64 ($macros));
	};
	return if ($@);
	
	# Add module macros to the macro hash
	if(ref($decoded_macros) eq "HASH") {
		while (my ($macro, $value) = each (%{$decoded_macros})) {
			$macro_hash->{$macro} = $value;
		}
	}
}

##########################################################################
# Create a custom graph
##########################################################################
sub pandora_create_custom_graph ($$$$$$$$$$) {
	
	my ($name,$description,$user,$idGroup,$width,$height,$events,$stacked,$period,$dbh) = @_;
	
	my ($columns, $values) = db_insert_get_values ({'name' => safe_input($name),
	                                                'id_user' => $user,
													'description' => $description, 
													'period' => $period,
													'width' => $width,
													'height' => $height,
													'private' => 0,
													'id_group' => $idGroup,
													'events' => $events, 
													'stacked' => $stacked
	                                                });                           
	                                                
	my $graph_id = db_insert ($dbh, 'id_graph', "INSERT INTO tgraph $columns", @{$values});
	
	return $graph_id;
}

##########################################################################
# Insert graph source
##########################################################################
sub pandora_insert_graph_source ($$$$) {
	
	my ($id_graph,$module,$weight,$dbh) = @_;
	
	my ($columns, $values) = db_insert_get_values ({'id_graph' => $id_graph,
													'id_agent_module' => $module, 
													'weight' => $weight
	                                                });                           
	                                                
	my $source_id = db_insert ($dbh, 'id_gs', "INSERT INTO tgraph_source $columns", @{$values});
	
	return $source_id;
}

##########################################################################
# Delete graph source
##########################################################################
sub pandora_delete_graph_source ($$;$) {
	
	my ($id_graph,$dbh,$id_module) = @_;
	
	my $result;
	
	if (defined ($id_module)) {
		$result = db_do ($dbh, 'DELETE FROM tgraph_source 
			WHERE id_graph = ?
			AND id_agent_module = ?', $id_graph, $id_module);
	} else {
		$result = db_do ($dbh, 'DELETE FROM tgraph_source WHERE id_graph = ?', $id_graph);
	}                                                
	
	return $result;
}

##########################################################################
# Delete custom graph
##########################################################################
sub pandora_delete_custom_graph ($$) {

	my ($id_graph,$dbh) = @_;              
	                                                
	my $result = db_do ($dbh, 'DELETE FROM tgraph WHERE id_graph = ?', $id_graph);
	
	return $result;
}

##########################################################################
# Edit a custom graph
##########################################################################

sub pandora_edit_custom_graph ($$$$$$$$$$$) {
	
	my ($id_graph,$name,$description,$user,$idGroup,$width,$height,$events,$stacked,$period,$dbh) = @_;
	
	my $graph = get_db_single_row ($dbh, 'SELECT * FROM tgraph
											WHERE id_graph = ?', $id_graph);
	if ($name eq '') {
		$name = $graph->{'name'};
	}
	if ($description eq '') {
		$description = $graph->{'description'};
	}
	if ($user eq '') {
		$user = $graph->{'id_user'};
	}
	if ($period eq '') {
		$period = $graph->{'period'};
	}
	if ($width eq '') {
		$width = $graph->{'width'};
	}
	if ($height eq '') {
		$height = $graph->{'height'};
	}
	if ($idGroup eq '') {
		$idGroup = $graph->{'id_group'};
	}
	if ($events eq '') {
		$events = $graph->{'events'};
	}
	if ($stacked eq '') {
		$stacked = $graph->{'stacked'};
	}
	
	my $res = db_do ($dbh, 'UPDATE tgraph SET name = ?, id_user = ?, description = ?, period = ?, width = ?,
		height = ?, private = 0, id_group = ?, events = ?, stacked = ?
		WHERE id_graph = ?',$name, $user, $description,$period, $width, $height, $idGroup, $events, $stacked, $id_graph);
		
	return $res;
}

sub pandora_create_integria_ticket ($$$$$$$$) {
	my ($pa_config,$api_path,$api_pass,$integria_user,$ticket_name,$group_id,$ticket_priority,$ticket_description) = @_;
	
	my $data_ticket;
	my $call_api;

	if ($api_path eq "") {
		return 0;
	}
	if ($integria_user eq "") {
		$integria_user = "admin";
	}
	if ($ticket_name eq "") {
		$ticket_name = "Ticket created by Pandora FMS";
	}
	if ($group_id eq "") {
		$group_id = 0;
	}
	if ($ticket_priority eq "") {
		$ticket_priority = 1;
	}
	
	$data_ticket = $ticket_name .
		"|;|" . $group_id .
		"|;|" . $ticket_priority .
		"|;|" . $ticket_description;

	$call_api = $api_path . '?' .
		'user=' . $integria_user . '&' .
		'pass=' . $api_pass . '&' .
		'op=create_incident&' .
		'params=' . $data_ticket .'&' .
		'token=|;|';
	logger($pa_config, "Integria ticket call:" . $call_api . "", 3);
	my $content = get($call_api);
	logger($pa_config, "Integria ticket res:" . $content . "", 3);
	if (is_numeric($content) && $content ne "-1") {
		return $content;
	}
	else {
		return 0;
	}
}

# End of function declaration
# End of defined Code

1;
__END__

=head1 DEPENDENCIES

L<DBI>, L<XML::Simple>, L<HTML::Entities>, L<Time::Local>, L<POSIX>, L<PandoraFMS::DB>, L<PandoraFMS::Config>, L<PandoraFMS::Tools>, L<PandoraFMS::GIS>

=head1 LICENSE

This is released under the GNU Lesser General Public License.

=head1 SEE ALSO

L<DBI>, L<XML::Simple>, L<HTML::Entities>, L<Time::Local>, L<POSIX>, L<PandoraFMS::DB>, L<PandoraFMS::Config>, L<PandoraFMS::Tools>, L<PandoraFMS::GIS>

=head1 COPYRIGHT

Copyright (c) 2005-2011 Artica Soluciones Tecnologicas S.L

=cut
