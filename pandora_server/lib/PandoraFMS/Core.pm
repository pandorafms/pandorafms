package PandoraFMS::Core;
##########################################################################
# Core Pandora FMS functions.
# Pandora FMS. the Flexible Monitoring System. http://www.pandorafms.org
##########################################################################
# Copyright (c) 2005-2021 Artica Soluciones Tecnologicas S.L
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

=item * C<pandora_create_module>

=item * C<pandora_disable_autodisable_agents>

=item * C<pandora_evaluate_alert>

=item * C<pandora_evaluate_snmp_alerts>

=item * C<pandora_event>

=item * C<pandora_execute_alert>

=item * C<pandora_execute_action>

=item * C<pandora_exec_forced_alerts>

=item * C<pandora_generate_alerts>

=item * C<pandora_input_password>

=item * C<pandora_module_keep_alive>

=item * C<pandora_module_keep_alive_nd>

=item * C<pandora_output_password>

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

=item * C<pandora_update_secondary_groups_cache>

=item * C<pandora_group_statistics>

=item * C<pandora_server_statistics>

=item * C<pandora_self_monitoring>

=item * C<pandora_sample_agent>

=back

=head1 METHODS

=cut

use strict;
use warnings;

use DBI;
use Encode;
use Encode::CN;
use XML::Simple;
use HTML::Entities;
use Time::Local;
use Time::HiRes qw(time);
eval "use POSIX::strftime::GNU;1" if ($^O =~ /win/i);
use POSIX qw(strftime);
use threads;
use threads::shared;
use JSON qw(decode_json encode_json);
use MIME::Base64;
use Text::ParseWords;
use Math::Trig;			# Math functions

# Debugging
#use Data::Dumper;

# Force XML::Simple to use XML::Parser instead SAX to manage XML
# due a bug processing some XML with blank spaces.
# See http://www.perlmonks.org/?node_id=706838

eval {
	local $SIG{__DIE__};
	eval "use XML::SAX::ExpatXS;1" or die "XML::SAX::ExpatXS not available";
};
if (!$@) {
	# Force best option available.
	$XML::Simple::PREFERRED_PARSER='XML::SAX::ExpatXS';
} else {
	# Use classic parser.
	$XML::Simple::PREFERRED_PARSER='XML::Parser';
}

# Default lib dir for RPM and DEB packages
BEGIN { push @INC, '/usr/lib/perl5'; }

use PandoraFMS::DB;
use PandoraFMS::Config;
use PandoraFMS::Tools;
use PandoraFMS::GIS qw(distance_moved);

# For Reverse Geocoding
use LWP::Simple;

# For api calls
use IO::Socket::INET6;
use LWP::UserAgent;
use HTTP::Request::Common;
use URI::URL;
use LWP::UserAgent;
use JSON;

# For IPv6 support in Net::HTTP.
BEGIN {
	$Net::HTTP::SOCKET_CLASS = 'IO::Socket::INET6';
	require Net::HTTP;
}

require Exporter;

our @ISA = ("Exporter");
our %EXPORT_TAGS = ( 'all' => [ qw( ) ] );
our @EXPORT_OK = ( @{ $EXPORT_TAGS{'all'} } );
our @EXPORT = qw(
	pandora_add_agent_address
	pandora_audit
	pandora_create_agent
	pandora_create_alert_command
	pandora_create_group
	pandora_create_module
	pandora_create_module_from_hash
	pandora_create_module_from_network_component
	pandora_create_module_tags
	pandora_create_template_module
	pandora_create_template_module_action
	pandora_delete_agent
	pandora_delete_all_template_module_actions
	pandora_delete_module
	pandora_disable_autodisable_agents
	pandora_evaluate_alert
	pandora_evaluate_snmp_alerts
	pandora_event
	pandora_extended_event
	pandora_execute_alert
	pandora_execute_action
	pandora_exec_forced_alerts
	pandora_generate_alerts
	pandora_get_agent_group
	pandora_get_config_value
	pandora_get_credential
	pandora_get_module_tags
	pandora_get_module_url_tags
	pandora_get_module_phone_tags
	pandora_get_module_email_tags
	pandora_get_os
	pandora_get_os_by_id
	pandora_input_password
	pandora_is_master
	pandora_mark_agent_for_alert_update
	pandora_mark_agent_for_module_update
	pandora_module_keep_alive
	pandora_module_keep_alive_nd
	pandora_module_unknown
	pandora_output_password
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
	pandora_safe_mode_modules_update
	pandora_server_keep_alive
	pandora_set_event_storm_protection
	pandora_set_master
	pandora_update_agent
	pandora_update_agent_address
	pandora_update_agent_alert_count
	pandora_update_agent_module_count
	pandora_update_config_token
	pandora_update_agent_custom_field
	pandora_select_id_custom_field
	pandora_select_combo_custom_field
	pandora_update_gis_data
	pandora_update_module_on_error
	pandora_update_module_from_hash
	pandora_update_secondary_groups_cache
	pandora_update_server
	pandora_update_table_from_hash
	pandora_update_template_module
	pandora_mark_transactional_agent
	pandora_group_statistics
	pandora_server_statistics
	pandora_self_monitoring
	pandora_sample_agent
	pandora_process_policy_queue
	pandora_sync_agents_integria
	pandora_get_integria_ticket_types
	subst_alert_macros
	subst_column_macros
	locate_agent
	get_agent
	get_agent_from_alias
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
	notification_set_targets
	notification_get_users
	notification_get_groups
);

# Some global variables
our @DayNames = qw(sunday monday tuesday wednesday thursday friday saturday);
our @ServerTypes = qw (
	dataserver
	networkserver
	snmpconsole
	reconserver
	pluginserver
	predictionserver
	wmiserver
	exportserver
	inventoryserver
	webserver
	eventserver
	icmpserver
	snmpserver
	satelliteserver
	transactionalserver
	mfserver
	syncserver
	wuxserver
	syslogserver
	provisioningserver
	migrationserver
	alertserver
	correlationserver
);
our @AlertStatus = ('Execute the alert', 'Do not execute the alert', 'Do not execute the alert, but increment its internal counter', 'Cease the alert', 'Recover the alert', 'Reset internal counter');

# Event storm protection (no alerts or events)
our $EventStormProtection :shared = 0;

# Current master server
my $Master :shared = 0;

##########################################################################
# Return the agent given the agent name or alias or address.
##########################################################################
sub locate_agent {
	my ($pa_config, $dbh, $field, $relative) = @_;

	if (is_metaconsole($pa_config)) {
		# Locate agent first in tmetaconsole_agent
		return undef if (! defined ($field) || $field eq '');

		my $rs = enterprise_hook('get_metaconsole_agent_from_id', [$dbh, $field]);
		return $rs if defined($rs) && (ref($rs)); # defined and not a scalar

		$rs = enterprise_hook('get_metaconsole_agent_from_alias', [$dbh, $field, $relative]);
		return $rs if defined($rs) && (ref($rs)); # defined and not a scalar

		$rs = enterprise_hook('get_metaconsole_agent_from_addr', [$dbh, $field, $relative]);
		return $rs if defined($rs) && (ref($rs)); # defined and not a scalar

		$rs = enterprise_hook('get_metaconsole_agent_from_name', [$dbh, $field, $relative]);
		return $rs if defined($rs) && (ref($rs)); # defined and not a scalar

	} else {
		return get_agent($dbh, $field, $relative);
	}

	return undef;
}


##########################################################################
# Return the agent given the agent name or alias or address.
##########################################################################
sub get_agent {
    my ($dbh, $field, $relative) = @_;

    return undef if (! defined ($field) || $field eq '');

    my $rs = get_agent_from_id($dbh, $field);
    return $rs if defined($rs) && (ref($rs)); # defined and not a scalar

    $rs = get_agent_from_alias($dbh, $field, $relative);
    return $rs if defined($rs) && (ref($rs)); # defined and not a scalar

    $rs = get_agent_from_addr($dbh, $field);
    return $rs if defined($rs) && (ref($rs)); # defined and not a scalar

    $rs = get_agent_from_name($dbh, $field, $relative);
    return $rs if defined($rs) && (ref($rs)); # defined and not a scalar

    return undef;
}

##########################################################################
# Return the agent given the agent name.
##########################################################################
sub get_agent_from_alias ($$;$) {
	my ($dbh, $alias, $relative) = @_;
	
	return undef if (! defined ($alias) || $alias eq '');
	if ($relative) {
		return get_db_single_row($dbh, 'SELECT * FROM tagente WHERE tagente.alias like ?', safe_input($alias));
	}
	
	return get_db_single_row ($dbh, 'SELECT * FROM tagente WHERE tagente.alias = ?', safe_input($alias));
}

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
sub get_agent_from_name ($$;$) {
	my ($dbh, $name, $relative) = @_;
	
	return undef if (! defined ($name) || $name eq '');

	if ($relative) {
		return get_db_single_row($dbh, 'SELECT * FROM tagente WHERE tagente.nombre like ?', safe_input($name));
	}
	
	return get_db_single_row ($dbh, 'SELECT * FROM tagente WHERE tagente.nombre = ?', safe_input($name));
}

##########################################################################
# Return the agent given the agent id.
##########################################################################
sub get_agent_from_id ($$) {
	my ($dbh, $id) = @_;
	
	return undef if (! defined ($id) || $id eq '');
	
	return get_db_single_row ($dbh, 'SELECT * FROM tagente WHERE tagente.id_agente = ?', $id);
}

##########################################################################
=head2 C<< pandora_generate_alerts (I<$pa_config> I<$data> I<$status> I<$agent> I<$module> I<$utimestamp> I<$dbh>  I<$timestamp> I<$extra_macros> I<$last_data_value>) >>

Generate alerts for a given I<$module>.

=cut
##########################################################################
sub pandora_generate_alerts ($$$$$$$$;$$$) {
	my ($pa_config, $data, $status, $agent, $module, $utimestamp, $dbh, $timestamp, $extra_macros, $last_data_value, $alert_type) = @_;

	# No alerts when event storm protection is enabled
	
	if ($EventStormProtection == 1)	{
		
		return;
	}
 

	# Warmup interval for alerts.
	if ($pa_config->{'warmup_alert_on'} == 1) {

		# No alerts.
		return if (time() < $pa_config->{'__start_utimestamp__'} + $pa_config->{'warmup_alert_interval'});

		$pa_config->{'warmup_alert_on'} = 0;
		logger($pa_config, "Warmup mode for alerts ended.", 10);
		pandora_event ($pa_config, "Warmup mode for alerts ended.", 0, 0, 0, 0, 0, 'system', 0, $dbh);
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
	my $alert_type_filter = '';
	if (defined($alert_type)) {
		# not_normal includes unknown!
		$alert_type_filter = $alert_type eq 'unknown' ? " AND (type = 'unknown' OR type = 'not_normal')" : " AND type = '$alert_type'"; 
	}
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
sub pandora_evaluate_alert ($$$$$$$;$$$$) {
	my ($pa_config, $agent, $data, $last_status, $alert, $utimestamp, $dbh,
	  $last_data_value, $correlatedItems, $event, $log) = @_;
	
	if (defined ($agent)) {
		logger ($pa_config, "Evaluating alert '" . safe_output($alert->{'name'}) . "' for agent '" . safe_output ($agent->{'nombre'}) . "'.", 10);
	}
	else {
		logger ($pa_config, "Evaluating alert '" . safe_output($alert->{'name'}) . "'.", 10);
	}
	
	# Value returned on valid data
	my $status = 1;

	if ($alert->{'min_alerts_reset_counter'}) {
		$status = 5;
	}
	
	# Get current time
	my ($sec, $min, $hour, $mday, $mon, $year, $wday, $yday, $isdst) = localtime(time());
	
	# Check weekday
	if ($alert->{'special_day'}) {
		logger ($pa_config, "Checking special days '" . $alert->{'name'} . "'.", 10);
		my $date = sprintf("%4d%02d%02d", $year + 1900, $mon + 1, $mday);
		# '0001' means every year.
		my $date_every_year = sprintf("0001%02d%02d", $mon + 1, $mday);
		my $special_day = get_db_value ($dbh, 'SELECT day_code FROM talert_special_days WHERE (date = ? OR date = ?) AND (id_group = 0 OR id_group = ?) AND (id_calendar = ?) ORDER BY date DESC', $date, $date_every_year, $alert->{'id_group'}, $alert->{'special_day'});

		if (!defined($special_day)) {
			$special_day = 0;
		}

		my @weeks = ( 'none', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday', 'holiday');
		if ($special_day != 0) {
			logger ($pa_config, $date . " is a special day for " . $alert->{'name'} . ". (as a " . $weeks[$special_day] . ")", 10);
			return $status if (!defined($alert->{$weeks[$special_day]}) || $alert->{$weeks[$special_day]} == 0);
		}
		else {
			logger ($pa_config, $date . " is *NOT* a special day for " . $alert->{'name'}, 10);
			return $status if ($alert->{$DayNames[$wday]} != 1);
		}
	}
	else {
		return $status if ($alert->{$DayNames[$wday]} != 1);
	}

	my $schedule;
	if (defined($alert->{'schedule'}) && $alert->{'schedule'} ne '') {
		$schedule = PandoraFMS::Tools::p_decode_json($pa_config, $alert->{'schedule'});
	}

	if (defined($schedule)) {
		# New behaviour.
		return $status unless defined($schedule) && ref $schedule eq "HASH";

		return $status unless defined($schedule->{$DayNames[$wday]});

		return $status unless ref($schedule->{$DayNames[$wday]}) eq "ARRAY";

		my $time = sprintf ("%.2d:%.2d:%.2d", $hour, $min, $sec);

		#
		# Check time slots
		#
		my $inSlot = 0;
		foreach my $timeBlock (@{$schedule->{$DayNames[$wday]}}) {
			if ($timeBlock->{'start'} eq $timeBlock->{'end'}) {
				# All day.
				$inSlot = 1;
			} elsif ($timeBlock->{'start'} le $time && $timeBlock->{'end'} ge $time) {
				# In range.
				$inSlot = 1;
			}
		}

		return $status if $inSlot eq 0;
	} else {
		# Old behaviour.
		# Check time slot
		my $time = sprintf ("%.2d:%.2d:%.2d", $hour, $min, $sec);
		if (($alert->{'time_from'} ne $alert->{'time_to'})) {
			if ($alert->{'time_from'} lt $alert->{'time_to'}) {
				return $status if (($time le $alert->{'time_from'}) || ($time ge $alert->{'time_to'}));
			} else {
				return $status if (($time le $alert->{'time_from'}) && ($time ge $alert->{'time_to'}));
			}
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
		return $status if ($last_status == 0 && $alert->{'type'} eq 'not_normal');
	}
	# Correlated alert
	else {
		my $rc = enterprise_hook (
			'evaluate_correlated_alert',
			[
				$pa_config,
				$dbh,
				$alert,
				$correlatedItems,
				$event,
				$log
			]
		);

		return $status unless !PandoraFMS::Tools::is_empty($rc) && $rc == 1;
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
sub pandora_process_alert ($$$$$$$$;$$) {
	my ($pa_config, $data, $agent, $module, $alert, $rc, $dbh, $timestamp,
			$extra_macros, $is_correlated_alert) = @_;

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
		$alert->{'unknown_instructions'} = $unknown_instructions;

		# Generate event only if not quieted by module or agent.
		return if ((ref($module) eq 'HASH' && $module->{'quiet'} != "0")
			|| (ref($agent) eq 'HASH' && $agent->{'quiet'} != "0")
			|| (ref($alert) eq 'HASH' && $alert->{'disable_event'} != "0"));

		# Generate an event
		if ($table eq 'tevent_alert') {
			pandora_event ($pa_config, "Correlated alert ceased (" .
				safe_output($alert->{'name'}) . ")", 0, 0, $alert->{'priority'}, $id,
				(defined ($alert->{'id_agent_module'}) ? $alert->{'id_agent_module'} : 0), 
				"alert_ceased", 0, $dbh, 'monitoring_server', '', '', '', '', $critical_instructions, $warning_instructions, $unknown_instructions);
		}  else {
			pandora_event ($pa_config, "Alert ceased (" .
					safe_output($alert->{'name'}) . ")", $agent->{'id_grupo'},
					$agent->{'id_agente'}, $alert->{'priority'}, $id,
					(defined ($alert->{'id_agent_module'}) ? $alert->{'id_agent_module'} : 0),
					"alert_ceased", 0, $dbh, 'monitoring_server', '', '', '', '', $critical_instructions, $warning_instructions, $unknown_instructions);
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

		if ($pa_config->{'alertserver'} == 1) {
			pandora_queue_alert($pa_config, $dbh, [$data, $agent, $module,
				$alert, 0, $timestamp, 0, $extra_macros, $is_correlated_alert]);
		} else {
			pandora_execute_alert ($pa_config, $data, $agent, $module, $alert, 0, $dbh,
				$timestamp, 0, $extra_macros, $is_correlated_alert);
		}
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
		
		if ($pa_config->{'alertserver'} == 1) {
			pandora_queue_alert($pa_config, $dbh, [$data, $agent, $module,
				$alert, 1, $timestamp, 0, $extra_macros, $is_correlated_alert]);
		} else {
			pandora_execute_alert ($pa_config, $data, $agent, $module, $alert, 1,
				$dbh, $timestamp, 0, $extra_macros, $is_correlated_alert);
		}
		return;
	}
}

##########################################################################
=head2 C<< pandora_execute_alert (I<$pa_config>, I<$data>, I<$agent>, I<$module>, I<$alert>, I<$alert_mode>, I<$dbh>, I<$timestamp>, I<$forced_alert>) >> 

Execute the given alert.

=cut
##########################################################################
sub pandora_execute_alert {
	my ($pa_config, $data, $agent, $module,
		$alert, $alert_mode, $dbh, $timestamp, $forced_alert,
		$extra_macros, $is_correlated_alert) = @_;
	
	# 'in-process' events can inhibit alers too.
	if ($pa_config->{'event_inhibit_alerts'} == 1 && $alert_mode != RECOVERED_ALERT) {
		my $status = get_db_value($dbh, 'SELECT estado FROM tevento WHERE id_alert_am = ? ORDER BY utimestamp DESC LIMIT 1', $alert->{'id_template_module'});
		if (defined($status) && $status == 2) {
			logger ($pa_config, "Alert '" . safe_output($alert->{'name'}) . "' inhibited by in-process events.", 10);
			return;
		}
	}
	
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
		# Avoid the use of something like "SELECT *, <column that exists in the *>" cause
		# it will make an error on oracle databases. It's better to filter the wildcards
		# by table and add (one by one) all the columns of the table which will have columns
		# that will be modified with an alias or something.
		
		if ($alert_mode == RECOVERED_ALERT) {
			# Avoid the use of alias bigger than 30 characters.
			@actions = get_db_rows ($dbh,
				'SELECT taa.name as action_name, taa.*, tac.*, tatma.id AS id_alert_templ_module_actions,
					tatma.id_alert_template_module, tatma.id_alert_action, tatma.fires_min,
					tatma.fires_max, tatma.module_action_threshold, tatma.last_execution
				FROM talert_template_module_actions tatma, talert_actions taa, talert_commands tac
				WHERE tatma.id_alert_action = taa.id
					AND taa.id_alert_command = tac.id
					AND tatma.id_alert_template_module = ?
					AND ((fires_min = 0 AND fires_max = 0)
						OR ? >= fires_min)',
				$alert->{'id_template_module'}, $alert->{'times_fired'});	
		} else {
			# Avoid the use of alias bigger than 30 characters.
			if ($forced_alert){
				@actions = get_db_rows ($dbh, 
					'SELECT taa.name as action_name, taa.*, tac.*, tatma.id AS id_alert_templ_module_actions,
						tatma.id_alert_template_module, tatma.id_alert_action, tatma.fires_min,
						tatma.fires_max, tatma.module_action_threshold, tatma.last_execution
					FROM talert_template_module_actions tatma, talert_actions taa, talert_commands tac
					WHERE tatma.id_alert_action = taa.id
						AND taa.id_alert_command = tac.id
						AND tatma.id_alert_template_module = ?', 
					$alert->{'id_template_module'});	
	
			} else {		
				@actions = get_db_rows ($dbh, 
					'SELECT taa.name as action_name, taa.*, tac.*, tatma.id AS id_alert_templ_module_actions,
						tatma.id_alert_template_module, tatma.id_alert_action, tatma.fires_min,
						tatma.fires_max, tatma.module_action_threshold, tatma.last_execution
					FROM talert_template_module_actions tatma, talert_actions taa, talert_commands tac
					WHERE tatma.id_alert_action = taa.id
						AND taa.id_alert_command = tac.id
						AND tatma.id_alert_template_module = ?
						AND ((fires_min = 0 AND fires_max = 0)
							OR (fires_min <= fires_max AND ? >= fires_min AND ? <= fires_max)
							OR (fires_min > fires_max AND ? >= fires_min))', 
					$alert->{'id_template_module'}, $alert->{'times_fired'}, $alert->{'times_fired'}, $alert->{'times_fired'});			
			}
		}

		# Get default action
		if ($#actions < 0) {
			@actions = get_db_rows ($dbh, 'SELECT talert_actions.name as action_name, talert_actions.*, talert_commands.*
						FROM talert_actions, talert_commands
						WHERE talert_actions.id = ?
						AND talert_actions.id_alert_command = talert_commands.id',
						$alert->{'id_alert_action'});
		}
	}
	# Event alert
	else {
		if ($alert_mode == RECOVERED_ALERT) {
			@actions = get_db_rows ($dbh, 'SELECT talert_actions.name as action_name, tevent_alert_action.*, talert_actions.*, talert_commands.*
						FROM tevent_alert_action, talert_actions, talert_commands
						WHERE tevent_alert_action.id_alert_action = talert_actions.id
						AND talert_actions.id_alert_command = talert_commands.id
						AND tevent_alert_action.id_event_alert = ?
						AND ((fires_min = 0 AND fires_max = 0)
						OR ? >= fires_min)',
						$alert->{'id'}, $alert->{'times_fired'});
		} else {
			@actions = get_db_rows ($dbh, 'SELECT talert_actions.name as action_name, tevent_alert_action.*, talert_actions.*, talert_commands.*
						FROM tevent_alert_action, talert_actions, talert_commands
						WHERE tevent_alert_action.id_alert_action = talert_actions.id
						AND talert_actions.id_alert_command = talert_commands.id
						AND tevent_alert_action.id_event_alert = ?
						AND ((fires_min = 0 AND fires_max = 0)
						OR (fires_min <= fires_max AND ? >= fires_min AND ? <= fires_max)
						OR (fires_min > fires_max AND ? >= fires_min))', 
						$alert->{'id'}, $alert->{'times_fired'}, $alert->{'times_fired'}, $alert->{'times_fired'});
		}

		# Get default action
		if ($#actions < 0) {
			@actions = get_db_rows ($dbh, 'SELECT talert_actions.name as action_name, talert_actions.*, talert_commands.*
						FROM talert_actions, talert_commands
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

	# Additional execution information for the console.
	my $custom_data = {
		'actions'	=> [],
		'forced'	=> $forced_alert ? 1 : 0,
		'recovered'	=> $alert_mode == RECOVERED_ALERT ? 1 : 0
	};

	# Critical_instructions, warning_instructions, unknown_instructions
	my $critical_instructions = get_db_value ($dbh, 'SELECT critical_instructions FROM tagente_modulo WHERE id_agente_modulo = ?', $alert->{'id_agent_module'});
	my $warning_instructions = get_db_value ($dbh, 'SELECT warning_instructions FROM tagente_modulo WHERE id_agente_modulo = ?', $alert->{'id_agent_module'});
	my $unknown_instructions = get_db_value ($dbh, 'SELECT unknown_instructions FROM tagente_modulo WHERE id_agente_modulo = ?', $alert->{'id_agent_module'});

	$alert->{'critical_instructions'} = $critical_instructions;
	$alert->{'warning_instructions'} = $warning_instructions;
	$alert->{'unknown_instructions'} = $unknown_instructions;

	# Execute actions
	my $event_generated = 0;
	foreach my $action (@actions) {
		
		# Check the action threshold (template_action_threshold takes precedence over action_threshold)
		my $threshold = 0;
		$action->{'last_execution'} = 0 unless defined ($action->{'last_execution'});
		$threshold = $action->{'action_threshold'} if (defined ($action->{'action_threshold'}) && $action->{'action_threshold'} > 0);
		$threshold = $action->{'module_action_threshold'} if (defined ($action->{'module_action_threshold'}) && $action->{'module_action_threshold'} > 0);
		if (time () >= ($action->{'last_execution'} + $threshold)) {
			my $monitoring_event_custom_data = '';

			push(@{$custom_data->{'actions'}}, safe_output($action->{'action_name'}));

			# Does the action generate an event?
			if (safe_output($action->{'name'}) eq "Monitoring Event") {
				$event_generated = 1;
				$monitoring_event_custom_data = $custom_data;
			}

			pandora_execute_action ($pa_config, $data, $agent, $alert, $alert_mode, $action, $module, $dbh, $timestamp, $extra_macros, $monitoring_event_custom_data);
		} else {
			if (defined ($module)) {
				logger ($pa_config, "Skipping action " . safe_output($action->{'name'}) . " for alert '" . safe_output($alert->{'name'}) . "' module '" . safe_output($module->{'nombre'}) . "'.", 10);
			} else {
				logger ($pa_config, "Skipping action " . safe_output($action->{'name'}) . " for alert '" . safe_output($alert->{'name'}) . "'.", 10);
			}
		}
	}
	
	# Generate an event	only if an event has not already been generated by an alert action
	if ($event_generated == 0 && (! defined ($alert->{'disable_event'}) || (defined ($alert->{'disable_event'}) && $alert->{'disable_event'} == 0))) {
		#If we've spotted an alert recovered, we set the new event's severity to 2 (NORMAL), otherwise the original value is maintained.
		my ($text, $event, $severity) = ($alert_mode == RECOVERED_ALERT) ? ('recovered', 'alert_recovered', 2) : ('fired', 'alert_fired', $alert->{'priority'});

		if (defined($is_correlated_alert) && $is_correlated_alert == 1) {
			$text = "Correlated alert $text";
			pandora_event (
				$pa_config,
				"$text (" . safe_output($alert->{'name'}) . ") ",
				(defined ($agent) ? $agent->{'id_grupo'} : 0),
				# id agent.
				0,
				$severity,
				(defined ($alert->{'id_template_module'}) ? $alert->{'id_template_module'} : 0),
				# id agent module.
				0,
				$event,
				0,
				$dbh,
				'monitoring_server',
				'',
				'',
				'',
				'',
				$critical_instructions,
				$warning_instructions,
				$unknown_instructions,
				p_encode_json($pa_config, $custom_data)
			);
		} else {
			pandora_event (
				$pa_config,
				"$text (" . safe_output($alert->{'name'}) . ") " . (defined ($module) ? 'assigned to ('. safe_output($module->{'nombre'}) . ")" : ""),
				(defined ($agent) ? $agent->{'id_grupo'} : 0),
				(defined ($agent) ? $agent->{'id_agente'} : 0),
				$severity,
				(defined ($alert->{'id_template_module'}) ? $alert->{'id_template_module'} : 0),
				(defined ($alert->{'id_agent_module'}) ? $alert->{'id_agent_module'} : 0),
				$event,
				0,
				$dbh,
				'monitoring_server',
				'',
				'',
				'',
				'',
				$critical_instructions,
				$warning_instructions,
				$unknown_instructions,
				p_encode_json($pa_config, $custom_data)
			);
		}
	}
}

##########################################################################
=head2 C<< pandora_queue_alert (I<$pa_config>, I<$dbh>, I<$data>, I<$alert>, I<$extra_macros> >> 

Queue the given alert for execution.

=cut
##########################################################################
sub pandora_queue_alert ($$$) {
	my ($pa_config, $dbh, $arguments) = @_;

	my $json_arguments = PandoraFMS::Tools::p_encode_json($pa_config, $arguments);

	$json_arguments = encode_base64($json_arguments);

	db_do ($dbh, "INSERT INTO talert_execution_queue (data, utimestamp)
		VALUES (?, ?)", $json_arguments, time());
}

##########################################################################
=head2 C<< pandora_execute_action (I<$pa_config>, I<$data>, I<$agent>, I<$alert>, I<$alert_mode>, I<$action>, I<$module>, I<$dbh>, I<$timestamp>) >> 

Execute the given action.

=cut
##########################################################################
sub pandora_execute_action ($$$$$$$$$;$$) {
	my ($pa_config, $data, $agent, $alert,
		$alert_mode, $action, $module, $dbh, $timestamp, $extra_macros, $custom_data) = @_;

	logger($pa_config, "Executing action '" . safe_output($action->{'name'}) . "' for alert '". safe_output($alert->{'name'}) . "' agent '" . (defined ($agent) ? safe_output($agent->{'nombre'}) : 'N/A') . "'.", 10);

	my $clean_name = safe_output($action->{'name'});

	my ($field1, $field2, $field3, $field4, $field5, $field6, $field7, $field8, $field9, $field10);
	my ($field11, $field12, $field13, $field14, $field15, $field16, $field17, $field18, $field19, $field20);

	# Check for empty alert fields and assign command field.
	my $index = 1;
	my @command_fields = split(/,|\[|\]/, $action->{'fields_values'});
	foreach my $field (@command_fields) {
		unless (defined($action->{'field'.$index}) && $action->{'field'.$index} ne "") {
			$action->{'field'.$index}  = defined($field) ? $field : "" ;
		}
	}

	if (!defined($alert->{'snmp_alert'})) {
		# Regular alerts
		$field1  = defined($action->{'field1'})  && $action->{'field1'}  ne ""  ? $action->{'field1'}  : $alert->{'field1'};
		$field2  = defined($action->{'field2'})  && $action->{'field2'}  ne ""  ? $action->{'field2'}  : $alert->{'field2'};
		$field3  = defined($action->{'field3'})  && $action->{'field3'}  ne ""  ? $action->{'field3'}  : $alert->{'field3'};
		$field4  = defined($action->{'field4'})  && $action->{'field4'}  ne ""  ? $action->{'field4'}  : $alert->{'field4'};
		$field5  = defined($action->{'field5'})  && $action->{'field5'}  ne ""  ? $action->{'field5'}  : $alert->{'field5'};
		$field6  = defined($action->{'field6'})  && $action->{'field6'}  ne ""  ? $action->{'field6'}  : $alert->{'field6'};
		$field7  = defined($action->{'field7'})  && $action->{'field7'}  ne ""  ? $action->{'field7'}  : $alert->{'field7'};
		$field8  = defined($action->{'field8'})  && $action->{'field8'}  ne ""  ? $action->{'field8'}  : $alert->{'field8'};
		$field9  = defined($action->{'field9'})  && $action->{'field9'}  ne ""  ? $action->{'field9'}  : $alert->{'field9'};
		$field10 = defined($action->{'field10'}) && $action->{'field10'} ne ""  ? $action->{'field10'} : $alert->{'field10'};
		$field11 = defined($action->{'field11'}) && $action->{'field11'} ne ""  ? $action->{'field11'} : $alert->{'field11'};
		$field12 = defined($action->{'field12'}) && $action->{'field12'} ne ""  ? $action->{'field12'} : $alert->{'field12'};
		$field13 = defined($action->{'field13'}) && $action->{'field13'} ne ""  ? $action->{'field13'} : $alert->{'field13'};
		$field14 = defined($action->{'field14'}) && $action->{'field14'} ne ""  ? $action->{'field14'} : $alert->{'field14'};
		$field15 = defined($action->{'field15'}) && $action->{'field15'} ne ""  ? $action->{'field15'} : $alert->{'field15'};
		$field16 = defined($action->{'field16'}) && $action->{'field16'} ne ""  ? $action->{'field16'} : $alert->{'field16'};
		$field17 = defined($action->{'field17'}) && $action->{'field17'} ne ""  ? $action->{'field17'} : $alert->{'field17'};
		$field18 = defined($action->{'field18'}) && $action->{'field18'} ne ""  ? $action->{'field18'} : $alert->{'field18'};
		$field19 = defined($action->{'field19'}) && $action->{'field19'} ne ""  ? $action->{'field19'} : $alert->{'field19'};
		$field20 = defined($action->{'field20'}) && $action->{'field20'} ne ""  ? $action->{'field20'} : $alert->{'field20'};
	}
	else {
		$field1  = defined($alert->{'field1'})   && $alert->{'field1'}  ne "" ? $alert->{'field1'}  : $action->{'field1'};
		$field2  = defined($alert->{'field2'})   && $alert->{'field2'}  ne "" ? $alert->{'field2'}  : $action->{'field2'};
		$field3  = defined($alert->{'field3'})   && $alert->{'field3'}  ne "" ? $alert->{'field3'}  : $action->{'field3'};
		$field4  = defined($alert->{'field4'})   && $alert->{'field4'}  ne "" ? $alert->{'field4'}  : $action->{'field4'};
		$field5  = defined($alert->{'field5'})   && $alert->{'field5'}  ne "" ? $alert->{'field5'}  : $action->{'field5'};
		$field6  = defined($alert->{'field6'})   && $alert->{'field6'}  ne "" ? $alert->{'field6'}  : $action->{'field6'};
		$field7  = defined($alert->{'field7'})   && $alert->{'field7'}  ne "" ? $alert->{'field7'}  : $action->{'field7'};
		$field8  = defined($alert->{'field8'})   && $alert->{'field8'}  ne "" ? $alert->{'field8'}  : $action->{'field8'};
		$field9  = defined($alert->{'field9'})   && $alert->{'field9'}  ne "" ? $alert->{'field9'}  : $action->{'field9'};
		$field10 = defined($alert->{'field10'})  && $alert->{'field10'} ne "" ? $alert->{'field10'} : $action->{'field10'};
		$field11 = defined($alert->{'field11'})  && $alert->{'field11'} ne "" ? $alert->{'field11'} : $action->{'field11'};
		$field12 = defined($alert->{'field12'})  && $alert->{'field12'} ne "" ? $alert->{'field12'} : $action->{'field12'};
		$field13 = defined($alert->{'field13'})  && $alert->{'field13'} ne "" ? $alert->{'field13'} : $action->{'field13'};
		$field14 = defined($alert->{'field14'})  && $alert->{'field14'} ne "" ? $alert->{'field14'} : $action->{'field14'};
		$field15 = defined($alert->{'field15'})  && $alert->{'field15'} ne "" ? $alert->{'field15'} : $action->{'field15'};
		$field16 = defined($alert->{'field16'})  && $alert->{'field16'} ne "" ? $alert->{'field16'} : $action->{'field16'};
		$field17 = defined($alert->{'field17'})  && $alert->{'field17'} ne "" ? $alert->{'field17'} : $action->{'field17'};
		$field18 = defined($alert->{'field18'})  && $alert->{'field18'} ne "" ? $alert->{'field18'} : $action->{'field18'};
		$field19 = defined($alert->{'field19'})  && $alert->{'field19'} ne "" ? $alert->{'field19'} : $action->{'field19'};
		$field20 = defined($alert->{'field20'})  && $alert->{'field20'} ne "" ? $alert->{'field20'} : $action->{'field20'};
	}
	
		
	# Recovery fields, thanks to Kato Atsushi
	if ($alert_mode == RECOVERED_ALERT) {
		# Field 1 is a special case where [RECOVER] prefix is not added even when it is defined
		$field1  = defined($alert->{'field1_recovery'})   && $alert->{'field1_recovery'}   ne "" ? $alert->{'field1_recovery'}   : $field1;
		$field1  = defined($action->{'field1_recovery'})  && $action->{'field1_recovery'}  ne "" ? $action->{'field1_recovery'}  : $field1;

		$field2  = defined($field2)                       && $field2                       ne "" ? "[RECOVER]" . $field2         : "";
		$field2  = defined($alert->{'field2_recovery'})   && $alert->{'field2_recovery'}   ne "" ? $alert->{'field2_recovery'}   : $field2;
		$field2  = defined($action->{'field2_recovery'})  && $action->{'field2_recovery'}  ne "" ? $action->{'field2_recovery'}  : $field2;
		
		$field3  = defined($field3)                       && $field3                       ne "" ? "[RECOVER]" . $field3         : "";
		$field3  = defined($alert->{'field3_recovery'})   && $alert->{'field3_recovery'}   ne "" ? $alert->{'field3_recovery'}   : $field3;
		$field3  = defined($action->{'field3_recovery'})  && $action->{'field3_recovery'}  ne "" ? $action->{'field3_recovery'}  : $field3;
		
		$field4  = defined($field4)                       && $field4                       ne "" ? "[RECOVER]" . $field4         : "";
		$field4  = defined($alert->{'field4_recovery'})   && $alert->{'field4_recovery'}   ne "" ? $alert->{'field4_recovery'}   : $field4;
		$field4  = defined($action->{'field4_recovery'})  && $action->{'field4_recovery'}  ne "" ? $action->{'field4_recovery'}  : $field4;
		
		$field5  = defined($field5)                       && $field5                       ne "" ? "[RECOVER]" . $field5         : "";
		$field5  = defined($alert->{'field5_recovery'})   && $alert->{'field5_recovery'}   ne "" ? $alert->{'field5_recovery'}   : $field5;
		$field5  = defined($action->{'field5_recovery'})  && $action->{'field5_recovery'}  ne "" ? $action->{'field5_recovery'}  : $field5;
		
		$field6  = defined($field6)                       && $field6                       ne "" ? "[RECOVER]" . $field6         : "";
		$field6  = defined($alert->{'field6_recovery'})   && $alert->{'field6_recovery'}   ne "" ? $alert->{'field6_recovery'}   : $field6;
		$field6  = defined($action->{'field6_recovery'})  && $action->{'field6_recovery'}  ne "" ? $action->{'field6_recovery'}  : $field6;
		
		$field7  = defined($field7)                       && $field7                       ne "" ? "[RECOVER]" . $field7         : "";
		$field7  = defined($alert->{'field7_recovery'})   && $alert->{'field7_recovery'}   ne "" ? $alert->{'field7_recovery'}   : $field7;
		$field7  = defined($action->{'field7_recovery'})  && $action->{'field7_recovery'}  ne "" ? $action->{'field7_recovery'}  : $field7;
		
		$field8  = defined($field8)                       && $field8                       ne "" ? "[RECOVER]" . $field8         : "";
		$field8  = defined($alert->{'field8_recovery'})   && $alert->{'field8_recovery'}   ne "" ? $alert->{'field8_recovery'}   : $field8;
		$field8  = defined($action->{'field8_recovery'})  && $action->{'field8_recovery'}  ne "" ? $action->{'field8_recovery'}  : $field8;
		
		$field9  = defined($field9)                       && $field9                       ne "" ? "[RECOVER]" . $field9         : "";
		$field9  = defined($alert->{'field9_recovery'})   && $alert->{'field9_recovery'}   ne "" ? $alert->{'field9_recovery'}   : $field9;
		$field9  = defined($action->{'field9_recovery'})  && $action->{'field9_recovery'}  ne "" ? $action->{'field9_recovery'}  : $field9;
		
		$field10 = defined($field10)                      && $field10                      ne "" ? "[RECOVER]" . $field10        : "";
		$field10 = defined($alert->{'field10_recovery'})  && $alert->{'field10_recovery'}  ne "" ? $alert->{'field10_recovery'}  : $field10;
		$field10 = defined($action->{'field10_recovery'}) && $action->{'field10_recovery'} ne "" ? $action->{'field10_recovery'} : $field10;
		
		$field11 = defined($field11)                      && $field11                      ne "" ? "[RECOVER]" . $field11        : "";
		$field11 = defined($alert->{'field11_recovery'})  && $alert->{'field11_recovery'}  ne "" ? $alert->{'field11_recovery'}  : $field11;
		$field11 = defined($action->{'field11_recovery'}) && $action->{'field11_recovery'} ne "" ? $action->{'field11_recovery'} : $field11;
		
		$field12 = defined($field12)                      && $field12                      ne "" ? "[RECOVER]" . $field12        : "";
		$field12 = defined($alert->{'field12_recovery'})  && $alert->{'field12_recovery'}  ne "" ? $alert->{'field12_recovery'}  : $field12;
		$field12 = defined($action->{'field12_recovery'}) && $action->{'field12_recovery'} ne "" ? $action->{'field12_recovery'} : $field12;
		
		$field13 = defined($field13)                      && $field13                      ne "" ? "[RECOVER]" . $field13        : "";
		$field13 = defined($alert->{'field13_recovery'})  && $alert->{'field13_recovery'}  ne "" ? $alert->{'field13_recovery'}  : $field13;
		$field13 = defined($action->{'field13_recovery'}) && $action->{'field13_recovery'} ne "" ? $action->{'field13_recovery'} : $field13;
		
		$field14 = defined($field14)                      && $field14                      ne "" ? "[RECOVER]" . $field14        : "";
		$field14 = defined($alert->{'field14_recovery'})  && $alert->{'field14_recovery'}  ne "" ? $alert->{'field14_recovery'}  : $field14;
		$field14 = defined($action->{'field14_recovery'}) && $action->{'field14_recovery'} ne "" ? $action->{'field14_recovery'} : $field14;
		
		$field15 = defined($field15)                      && $field15                      ne "" ? "[RECOVER]" . $field15        : "";
		$field15 = defined($alert->{'field15_recovery'})  && $alert->{'field15_recovery'}  ne "" ? $alert->{'field15_recovery'}  : $field15;
		$field15 = defined($action->{'field15_recovery'}) && $action->{'field15_recovery'} ne "" ? $action->{'field15_recovery'} : $field15;

		$field16 = defined($field16)                      && $field16                      ne "" ? "[RECOVER]" . $field16       : "";
		$field16 = defined($alert->{'field16_recovery'})  && $alert->{'field16_recovery'}  ne "" ? $alert->{'field16_recovery'}  : $field16;
		$field16 = defined($action->{'field16_recovery'}) && $action->{'field16_recovery'} ne "" ? $action->{'field16_recovery'} : $field16;

		$field17 = defined($field17)                      && $field17                      ne "" ? "[RECOVER]" . $field17        : "";
		$field17 = defined($alert->{'field17_recovery'})  && $alert->{'field17_recovery'}  ne "" ? $alert->{'field17_recovery'}  : $field17;
		$field17 = defined($action->{'field17_recovery'}) && $action->{'field17_recovery'} ne "" ? $action->{'field17_recovery'} : $field17;

		$field18 = defined($field18)                      && $field18                      ne "" ? "[RECOVER]" . $field18        : "";
		$field18 = defined($alert->{'field18_recovery'})  && $alert->{'field18_recovery'}  ne "" ? $alert->{'field18_recovery'}  : $field18;
		$field18 = defined($action->{'field18_recovery'}) && $action->{'field18_recovery'} ne "" ? $action->{'field18_recovery'} : $field18;

		$field19 = defined($field19)                      && $field19                      ne "" ? "[RECOVER]" . $field19        : "";
		$field19 = defined($alert->{'field19_recovery'})  && $alert->{'field19_recovery'}  ne "" ? $alert->{'field19_recovery'}  : $field19;
		$field19 = defined($action->{'field19_recovery'}) && $action->{'field19_recovery'} ne "" ? $action->{'field19_recovery'} : $field19;

		$field20 = defined($field20)                      && $field20                      ne "" ? "[RECOVER]" . $field20        : "";
		$field20 = defined($alert->{'field20_recovery'})  && $alert->{'field20_recovery'}  ne "" ? $alert->{'field20_recovery'}  : $field20;
		$field20 = defined($action->{'field20_recovery'}) && $action->{'field20_recovery'} ne "" ? $action->{'field20_recovery'} : $field20;
	}

	$field1  = defined($field1)  && $field1  ne "" ? decode_entities($field1)  : "";
	$field2  = defined($field2)  && $field2  ne "" ? decode_entities($field2)  : "";
	$field3  = defined($field3)  && $field3  ne "" ? decode_entities($field3)  : "";
	$field4  = defined($field4)  && $field4  ne "" ? decode_entities($field4)  : "";
	$field5  = defined($field5)  && $field5  ne "" ? decode_entities($field5)  : "";
	$field6  = defined($field6)  && $field6  ne "" ? decode_entities($field6)  : "";
	$field7  = defined($field7)  && $field7  ne "" ? decode_entities($field7)  : "";
	$field8  = defined($field8)  && $field8  ne "" ? decode_entities($field8)  : "";
	$field9  = defined($field9)  && $field9  ne "" ? decode_entities($field9)  : "";
	$field10 = defined($field10) && $field10 ne "" ? decode_entities($field10) : "";
	$field11 = defined($field11) && $field11 ne "" ? decode_entities($field11) : "";
	$field12 = defined($field12) && $field12 ne "" ? decode_entities($field12) : "";
	$field13 = defined($field13) && $field13 ne "" ? decode_entities($field13) : "";
	$field14 = defined($field14) && $field14 ne "" ? decode_entities($field14) : "";
	$field15 = defined($field15) && $field15 ne "" ? decode_entities($field15) : "";
	$field16 = defined($field16) && $field16 ne "" ? decode_entities($field16) : "";
	$field17 = defined($field17) && $field17 ne "" ? decode_entities($field17) : "";
	$field18 = defined($field18) && $field18 ne "" ? decode_entities($field18) : "";
	$field19 = defined($field19) && $field19 ne "" ? decode_entities($field19) : "";
	$field20 = defined($field20) && $field20 ne "" ? decode_entities($field20) : "";

	# Get group info
	my $group = undef;
	if (defined ($agent)) {
		$group = get_db_single_row ($dbh, 'SELECT * FROM tgrupo WHERE id_grupo = ?', $agent->{'id_grupo'});
	}

	my $agent_status;
	if(ref ($module) eq "HASH") {
		$agent_status = get_db_single_row ($dbh, 'SELECT * FROM tagente_estado WHERE id_agente_modulo = ?', $module->{'id_agente_modulo'});
	}

	my $time_down = (defined ($agent_status)) ? (time() - $agent_status->{'last_status_change'}) : undef;

	if (is_numeric($data)) {
		my $data_precision = $pa_config->{'graph_precision'};
		$data = sprintf("%.$data_precision" . "f", $data);
		$data =~ s/0+$//;
		$data =~ s/\.+$//;
	}

	# Thanks to people of Cordoba univ. for the patch for adding module and
	# id_agent macros to the alert.

	# TODO: Reuse queries. For example, tag data can be extracted with a single query.
	# Alert macros
	my %macros = (
		_field1_ => $field1,
		_field2_ => $field2,
		_field3_ => $field3,
		_field4_ => $field4,
		_field5_ => $field5,
		_field6_ => $field6,
		_field7_ => $field7,
		_field8_ => $field8,
		_field9_ => $field9,
		_field10_ => $field10,
		_field11_ => $field11,
		_field12_ => $field12,
		_field13_ => $field13,
		_field14_ => $field14,
		_field15_ => $field15,
		_field16_ => $field16,
		_field17_ => $field17,
		_field18_ => $field18,
		_field19_ => $field19,
		_field20_ => $field20,
		_agentname_ => (defined ($agent)) ? $agent->{'nombre'} : '',
		_agentalias_ => (defined ($agent)) ? $agent->{'alias'} : '',
		_agent_ => (defined ($agent)) ? ($agent->{'alias'} ? $agent->{'alias'} : $agent->{'nombre'}) : '',
		_agentcustomid_ => (defined ($agent)) ? $agent->{'custom_id'} : '',
		'_agentcustomfield_\d+_'  => undef,
		_agentdescription_ => (defined ($agent)) ? $agent->{'comentarios'} : '',
		_agentgroup_ => (defined ($group)) ? $group->{'nombre'} : '',
		_agentstatus_ => undef,
		_agentos_ => (defined ($agent)) ? get_os_name($dbh, $agent->{'id_os'}) : '',
		_address_ => (defined ($agent)) ? $agent->{'direccion'} : '',
		_timestamp_ => (defined($timestamp)) ? $timestamp : strftime ("%Y-%m-%d %H:%M:%S", localtime()),
		_timezone_ => strftime ("%Z", localtime()),
		_data_ => $data,
		_prevdata_ => undef,
		_homeurl_ => $pa_config->{'public_url'},
		_alert_name_ => $alert->{'name'},
		_alert_description_ => $alert->{'description'},
		_alert_threshold_ => $alert->{'time_threshold'},
		_alert_times_fired_ => $alert->{'times_fired'},
		_alert_priority_ => $alert->{'priority'},
		_alert_text_severity_ => get_priority_name($alert->{'priority'}),
		_alert_critical_instructions_ => $alert->{'critical_instructions'},
		_alert_warning_instructions_ => $alert->{'warning_instructions'},
		_alert_unknown_instructions_ => $alert->{'unknown_instructions'},
		_groupcontact_ => (defined ($group)) ? $group->{'contact'} : '',
		_groupcustomid_ => (defined ($group)) ? $group->{'custom_id'} : '',
		_groupother_ => (defined ($group)) ? $group->{'other'} : '',
		_module_ => (defined ($module)) ? $module->{'nombre'} : '',
		_modulecustomid_ => (defined ($module)) ? $module->{'custom_id'} : '',
		_modulegroup_ => undef,
		_moduledescription_ => (defined ($module)) ? $module->{'descripcion'} : '',
		_modulestatus_ => undef,
		_moduletags_ => undef,
		'_moduledata_\S+_' => undef,
		_id_agent_ => (defined ($module)) ? $module->{'id_agente'} : '',
		_id_module_ => (defined ($module)) ? $module->{'id_agente_modulo'} : '',
		_id_group_ => (defined ($group)) ? $group->{'id_grupo'} : '',
		_id_alert_ => (defined ($alert->{'id_template_module'})) ? $alert->{'id_template_module'} : '',
		_interval_ => (defined ($module) && $module->{'module_interval'} != 0) ? $module->{'module_interval'} : (defined ($agent)) ? $agent->{'intervalo'} : '',
		_server_ip_ => (defined ($agent)) ? get_db_value($dbh, "SELECT ip_address FROM tserver WHERE name = ?", $agent->{'server_name'}) : '',
		_server_name_ => (defined ($agent)) ? $agent->{'server_name'} : '',
		_target_ip_ => (defined ($module)) ? $module->{'ip_target'} : '',
		_target_port_ => (defined ($module)) ? $module->{'tcp_port'} : '',
		_policy_ => (defined ($module)) ? get_db_value ($dbh, "SELECT name FROM tpolicies WHERE id = ?", get_db_value ($dbh, "SELECT id_policy FROM tpolicy_modules WHERE id = ?", $module->{'id_policy_module'})) : '',
		_plugin_parameters_ => (defined ($module)) ? $module->{'plugin_parameter'} : '',
		_email_tag_ => undef,
		_phone_tag_ => undef,
		_name_tag_ => undef,
		_all_address_ => undef,
		'_addressn_\d+_' => undef,
		_secondarygroups_ => undef,
		_time_down_seconds_ => (defined ($time_down)) ? int($time_down) : '',
		_time_down_human_ => seconds_totime($time_down),
		_warning_threshold_min_ => (defined ($module->{'min_warning'})) ? $module->{'min_warning'} : '',
		_warning_threshold_max_ => (defined ($module->{'max_warning'})) ? $module->{'max_warning'} : '',
		_critical_threshold_min_ => (defined ($module->{'min_critical'})) ? $module->{'min_critical'} : '',
		_critical_threshold_max_ => (defined ($module->{'max_critical'})) ? $module->{'max_critical'} : '',
	);

	if ((defined ($extra_macros)) && (ref($extra_macros) eq "HASH")) {
		while ((my $macro, my $value) = each (%{$extra_macros})) {
			$macros{$macro} = $value;
		}
	}

	if (defined ($module)) {
		load_module_macros ($module->{'module_macros'}, \%macros);
	}

	#logger($pa_config, "Clean name ".$clean_name, 10);
	# User defined alert
	if ($action->{'internal'} == 0) {
		$macros{_field1_} = subst_alert_macros ($field1, \%macros, $pa_config, $dbh, $agent, $module, $alert);
		$macros{_field2_} = subst_alert_macros ($field2, \%macros, $pa_config, $dbh, $agent, $module, $alert);
		$macros{_field3_} = subst_alert_macros ($field3, \%macros, $pa_config, $dbh, $agent, $module, $alert);
		$macros{_field4_} = subst_alert_macros ($field4, \%macros, $pa_config, $dbh, $agent, $module, $alert);
		$macros{_field5_} = subst_alert_macros ($field5, \%macros, $pa_config, $dbh, $agent, $module, $alert);
		$macros{_field6_} = subst_alert_macros ($field6, \%macros, $pa_config, $dbh, $agent, $module, $alert);
		$macros{_field7_} = subst_alert_macros ($field7, \%macros, $pa_config, $dbh, $agent, $module, $alert);
		$macros{_field8_} = subst_alert_macros ($field8, \%macros, $pa_config, $dbh, $agent, $module, $alert);
		$macros{_field9_} = subst_alert_macros ($field9, \%macros, $pa_config, $dbh, $agent, $module, $alert);
		$macros{_field10_} = subst_alert_macros ($field10, \%macros, $pa_config, $dbh, $agent, $module, $alert);
		$macros{_field11_} = subst_alert_macros ($field11, \%macros, $pa_config, $dbh, $agent, $module, $alert);
		$macros{_field12_} = subst_alert_macros ($field12, \%macros, $pa_config, $dbh, $agent, $module, $alert);
		$macros{_field13_} = subst_alert_macros ($field13, \%macros, $pa_config, $dbh, $agent, $module, $alert);
		$macros{_field14_} = subst_alert_macros ($field14, \%macros, $pa_config, $dbh, $agent, $module, $alert);
		$macros{_field15_} = subst_alert_macros ($field15, \%macros, $pa_config, $dbh, $agent, $module, $alert);
		$macros{_field16_} = subst_alert_macros ($field16, \%macros, $pa_config, $dbh, $agent, $module, $alert);
		$macros{_field17_} = subst_alert_macros ($field17, \%macros, $pa_config, $dbh, $agent, $module, $alert);
		$macros{_field18_} = subst_alert_macros ($field18, \%macros, $pa_config, $dbh, $agent, $module, $alert);
		$macros{_field19_} = subst_alert_macros ($field19, \%macros, $pa_config, $dbh, $agent, $module, $alert);
		$macros{_field20_} = subst_alert_macros ($field20, \%macros, $pa_config, $dbh, $agent, $module, $alert);
		
		my @command_args = ();
		# divide command into words based on quotes and whitespaces
		foreach my $word (quotewords('\s+', 1, (decode_entities($action->{'command'})))) {
			push @command_args, subst_alert_macros($word, \%macros, $pa_config, $dbh, $agent, $module);
		}
		my $command = join(' ', @command_args);
		logger($pa_config, "Executing command '$command' for action '" . safe_output($action->{'name'}) . "' alert '". safe_output($alert->{'name'}) . "' agent '" . (defined ($agent) ? safe_output($agent->{'alias'}) : 'N/A') . "'.", 8);
		
		eval {
			if ($pa_config->{'global_alert_timeout'} == 0){
				system ($command);
				logger($pa_config, "Command '$command' for action '" . safe_output($action->{'name'}) . "' alert '". safe_output($alert->{'name'}) . "' agent '" . (defined ($agent) ? safe_output($agent->{'alias'}) : 'N/A') . "' returned with errorlevel " . ($? >> 8), 8);
			} else {
				my $command_timeout = safe_output($pa_config->{'plugin_exec'}) . " " . $pa_config->{'global_alert_timeout'} . " " . $command;
				system ($command_timeout);
				my $return_code = ($? >> 8) & 0xff;
				logger($pa_config, "Command '$command_timeout' for action '" . safe_output($action->{'name'}) . "' alert '". safe_output($alert->{'name'}) . "' agent '" . (defined ($agent) ? safe_output($agent->{'alias'}) : 'N/A') . "' returned with errorlevel " . $return_code, 8);
				if ($return_code != 0) {
					logger ($pa_config, "Action '" . safe_output($action->{'name'}) . "' alert '" . safe_output($alert->{'name'}) . "' agent '" . (defined ($agent) ? safe_output($agent->{'alias'}) : 'N/A') . "' exceeded the global alert timeout " . $pa_config->{'global_alert_timeout'} . " seconds" , 3);
				}
			}	
		};
		
		if ($@){
			logger($pa_config, "Error $@ executing command '$command' for action '" . safe_output($action->{'name'}) . "' alert '". safe_output($alert->{'name'}) . "' agent '" . (defined ($agent) ? safe_output($agent->{'alias'}) : 'N/A') ."'.", 8);
		}
	
	# Internal Audit
	} elsif ($clean_name eq "Internal Audit") {
		$field1 = subst_alert_macros ($field1, \%macros, $pa_config, $dbh, $agent, $module, $alert);
		pandora_audit ($pa_config, $field1, defined ($agent) ? safe_output($agent->{'alias'}) : 'N/A', 'Alert (' . safe_output($alert->{'description'}) . ')', $dbh);
	
	# Email
	} elsif ($clean_name eq "eMail") {

		my $attach_data_as_image = 0;

		my $cid_data = "CID_IMAGE";
		my $dataname = "CID_IMAGE.png";

		# Decode ampersand. Used for macros with encoded names.
		$field3 =~ s/&amp;/&/g;

		if (defined($data) && $data =~ /^data:image\/png;base64, /) {
			# macro _data_ substitution in case is image.
			$attach_data_as_image = 1;
			my $_cid = '<img style="height: 150px;" src="cid:' . $cid_data . '"/>';

			$field3 =~ s/_data_/$_cid/g;
		}


		# Address
		$field1 = subst_alert_macros ($field1, \%macros, $pa_config, $dbh, $agent, $module, $alert);

		# Simple email address validation. Prevents connections to the SMTP server when no address is provided.
		if (index($field1, '@') == -1) {
			logger($pa_config, "No valid email address provided for action '" . $action->{'name'} . "' alert '". $alert->{'name'} . "' agent '" . (defined ($agent) ? $agent->{'alias'} : 'N/A') . "'.", 10);
			return;
		}

		# Subject
		$field2 = subst_alert_macros ($field2, \%macros, $pa_config, $dbh, $agent, $module, $alert);
		# Message
		$field3 = subst_alert_macros ($field3, \%macros, $pa_config, $dbh, $agent, $module, $alert);
		# Content
		$field4 = subst_alert_macros ($field4, \%macros, $pa_config, $dbh, $agent, $module, $alert);

		if($field4 eq ""){
			$field4 = "text/html";
		}
		
		# Check for _module_graph_Xh_ macros
		# Check for _module_graph_Xh_ macros and _module_graphth_Xh_ 
		my $module_graph_list = {};
		my $macro_regexp = "_modulegraph_(\\d+)h_";
		my $macro_regexp2 = "_modulegraphth_(\\d+)h_";
		
		# API connection
		my $ua = new LWP::UserAgent;
		eval {
			$ua->ssl_opts( 'verify_hostname' => 0 );
			$ua->ssl_opts( 'SSL_verify_mode' => 0x00 );
		};
		if ( $@ ) {
			logger($pa_config, "Failed to limit ssl security on console link: " . $@, 10);
		}


		my $url ||= $pa_config->{"console_api_url"};
		
		my $params = {};
		$params->{"apipass"} = $pa_config->{"console_api_pass"};
		$params->{"user"} ||= $pa_config->{"console_user"};
		$params->{"pass"} ||= $pa_config->{"console_pass"};
		$params->{"op"} = "get";
		$params->{"op2"} = "module_graph";
		$params->{"id"} = $module->{'id_agente_modulo'};
		my $cid ='';
		my $subst_func = sub {
			my $hours = shift;
			my $threshold = shift;
			my $period = $hours * 3600; # Hours to seconds
			if($threshold == 0){
				$params->{"other"} = $period . '%7C1%7C0%7C225%7C""%7C14';
				$cid = 'module_graph_' . $hours . 'h';
			}
			else{
				$params->{"other"} = $period . '%7C1%7C1%7C225%7C""%7C14';
				$cid = 'module_graphth_' . $hours . 'h';
			}

			$params->{"other_mode"} = 'url_encode_separator_%7C';
			
			if (! exists($module_graph_list->{$cid}) && defined $url) {
				# Get the module graph image in base 64
				my $response = $ua->post($url, $params);
				
				if ($response->is_success) {
					$module_graph_list->{$cid} = $response->decoded_content();
					
					return '<img src="cid:'.$cid.'">';
				}
			}
		
			return '';
		};

		# Macro data may contain HTML entities
		eval {
			no warnings;
			local $SIG{__DIE__};
			$field3 =~ s/$macro_regexp/$subst_func->($1, 0)/ige;
			$field3 =~ s/$macro_regexp2/$subst_func->($1, 1)/ige;
		};
		
		# Default content type
		my $content_type = $field4 . '; charset="iso-8859-1"';
		
		# Check if message has non-ascii chars.
		# non-ascii chars should be encoded in UTF-8.
		if ($field3 =~ /[^[:ascii:]]/o) {
			$field3 = encode("UTF-8", $field3);
			$content_type = $field4 . '; charset="UTF-8"';
		}
		

		my $boundary = "====" . time() . "====";
		my $html_content_type = $content_type;

		# Build the mail with attached content
		if ((keys(%{$module_graph_list}) > 0) && ($attach_data_as_image == 0)) {
			# module_graph only available if data is NOT an image

			$content_type = 'multipart/related; boundary="'.$boundary.'"';
			$boundary = "--" . $boundary;
			
			$field3 = $boundary . "\n"
					. "Content-Type: " . $html_content_type . "\n\n"
					#. "Content-Transfer-Encoding: quoted-printable\n\n"
					. $field3 . "\n";

			
			foreach my $cid (keys %{$module_graph_list}) {
				my $filename = $cid . ".png";
				
				$field3 .= $boundary . "\n"
						. "Content-Type: image/png; name=\"" . $filename . "\"\n"
						. "Content-Disposition: inline; filename=\"" . $filename . "\"\n"
						. "Content-Transfer-Encoding: base64\n"
						. "Content-ID: <" . $cid . ">\n"
						. "Content-Location: " . $filename . "\n\n"
						. $module_graph_list->{$cid} . "\n";

				delete $module_graph_list->{$cid};
			}
			undef %{$module_graph_list};
			
			$field3 .= $boundary . "--\n";
		}

		if ($attach_data_as_image == 1) {
			# it's an image in base64!

			$content_type = 'multipart/related; boundary="'.$boundary.'"';
			$boundary = "--" . $boundary;

			my $base64_data = substr($data, 23); # remove first 23 characters: 'data:image/png;base64, '

			$field3 = $boundary . "\n"
					. "Content-Type: " . $html_content_type . "\n\n"
					#. "Content-Transfer-Encoding: quoted-printable\n\n"
					. $field3 . "\n";

			$field3 .= $boundary . "\n"
			. "Content-Type: image/png; name=\"" . $dataname . "\"\n"
			. "Content-Disposition: inline; filename=\"" . $dataname . "\"\n"
			. "Content-Transfer-Encoding: base64\n"
			. "Content-ID: <" . $cid_data . ">\n"
			. "Content-Location: " . $dataname . "\n\n"
			. $base64_data . "\n";
		}
		
		if ($pa_config->{"mail_in_separate"} != 0){
			foreach my $address (split (',', $field1)) {
				# Remove blanks
				$address =~ s/ +//g;
				pandora_sendmail ($pa_config, $address, $field2, $field3, $content_type);
			}
		}
		else {
			pandora_sendmail ($pa_config, $field1, $field2, $field3, $content_type);
		}
	
	# Pandora FMS Event
	} elsif ($clean_name eq "Monitoring Event") {
		$field1 = subst_alert_macros ($field1, \%macros, $pa_config, $dbh, $agent, $module, $alert);
		$field3 = subst_alert_macros ($field3, \%macros, $pa_config, $dbh, $agent, $module, $alert);
		$field4 = subst_alert_macros ($field4, \%macros, $pa_config, $dbh, $agent, $module, $alert);
		$field6 = subst_alert_macros ($field6, \%macros, $pa_config, $dbh, $agent, $module, $alert);
		$field7 = subst_alert_macros ($field7, \%macros, $pa_config, $dbh, $agent, $module, $alert);
		$field8 = subst_alert_macros ($field8, \%macros, $pa_config, $dbh, $agent, $module, $alert);
		
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
		if( ! $fullagent && $macros{'_agentname_'} ) {
			$fullagent = get_agent_from_name ($dbh, $macros{'_agentname_'} );
		}
		
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

		if ((! defined($alert->{'disable_event'})) || (defined($alert->{'disable_event'}) && $alert->{'disable_event'} == 0)) {
			pandora_event(
				$pa_config,
				$event_text,
				(defined ($agent) ? $agent->{'id_grupo'} : 0),
				(defined ($fullagent) ? $fullagent->{'id_agente'} : 0),
				$priority,
				(defined($alert)
					? defined($alert->{'id_template_module'})
						? $alert->{'id_template_module'}
						: $alert->{'id'}
					: 0),
				(defined($alert) ? $alert->{'id_agent_module'} : 0),
				$event_type,
				0,
				$dbh,
				$source,
				'',
				$comment,
				$id_extra,
				$tags,
				'',
				'',
				'',
				p_encode_json($pa_config, $custom_data)
			);
			# Validate event (field1: agent name; field2: module name)
		}
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
		my $config_integria_enabled = pandora_get_tconfig_token ($dbh, 'integria_enabled', '');

		if (!$config_integria_enabled) {
			return;
		}

		my $config_api_path = pandora_get_tconfig_token ($dbh, 'integria_hostname', '');
		my $config_api_pass = pandora_get_tconfig_token ($dbh, 'integria_api_pass', '');
		my $config_integria_user = pandora_get_tconfig_token ($dbh, 'integria_user', '');
		my $config_integria_user_pass = pandora_get_tconfig_token ($dbh, 'integria_pass', '');
		$field1 = subst_alert_macros ($field1, \%macros, $pa_config, $dbh, $agent, $module, $alert);
		$field2 = subst_alert_macros ($field2, \%macros, $pa_config, $dbh, $agent, $module, $alert);
		$field3 = subst_alert_macros ($field3, \%macros, $pa_config, $dbh, $agent, $module, $alert);
		$field4 = subst_alert_macros ($field4, \%macros, $pa_config, $dbh, $agent, $module, $alert);
		$field5 = subst_alert_macros ($field5, \%macros, $pa_config, $dbh, $agent, $module, $alert);
		$field6 = subst_alert_macros ($field6, \%macros, $pa_config, $dbh, $agent, $module, $alert);
		$field7 = subst_alert_macros ($field7, \%macros, $pa_config, $dbh, $agent, $module, $alert);
		$field8 = subst_alert_macros ($field8, \%macros, $pa_config, $dbh, $agent, $module, $alert);
		$field9 = subst_alert_macros ($field9, \%macros, $pa_config, $dbh, $agent, $module, $alert);
		$field10 = subst_alert_macros ($field10, \%macros, $pa_config, $dbh, $agent, $module, $alert);
		$field11 = subst_alert_macros ($field11, \%macros, $pa_config, $dbh, $agent, $module, $alert);
		$field12 = subst_alert_macros ($field12, \%macros, $pa_config, $dbh, $agent, $module, $alert);
		$field13 = subst_alert_macros ($field13, \%macros, $pa_config, $dbh, $agent, $module, $alert);
		$field14 = subst_alert_macros ($field14, \%macros, $pa_config, $dbh, $agent, $module, $alert);
		$field15 = subst_alert_macros ($field15, \%macros, $pa_config, $dbh, $agent, $module, $alert);
		$field16 = subst_alert_macros ($field16, \%macros, $pa_config, $dbh, $agent, $module, $alert);
		$field17 = subst_alert_macros ($field17, \%macros, $pa_config, $dbh, $agent, $module, $alert);
		$field18 = subst_alert_macros ($field18, \%macros, $pa_config, $dbh, $agent, $module, $alert);
		$field19 = subst_alert_macros ($field19, \%macros, $pa_config, $dbh, $agent, $module, $alert);
		$field20 = subst_alert_macros ($field20, \%macros, $pa_config, $dbh, $agent, $module, $alert);

		# Field 1 (Integria IMS API path)
		my $api_path = $config_api_path . "/include/api.php";
		
		# Field 2 (Integria IMS API pass)
		my $api_pass = $config_api_pass;
		
		# Field 3 (Integria IMS user)
		my $integria_user = $config_integria_user;
		
		# Field 4 (Integria IMS user password)
		my $integria_user_pass = $config_integria_user_pass;
		
		# Field 1 (Ticket name)
		my $ticket_name = safe_output($field1);
		if ($ticket_name eq "") {
			$ticket_name = $pa_config->{'rb_product_name'} . " alert action created by API";
		}
		
		# Field 2 (Ticket group ID)
		my $ticket_group_id = $field2;
		if ($ticket_group_id eq '') {
			$ticket_group_id = 0;
		}
		
		# Field 3 (Ticket priority);
		my $ticket_priority = $field3;
		if ($ticket_priority eq '0') {
			$ticket_priority = 1;
		}

		# Field 4 (Ticket owner)
		my $ticket_owner = $field4;
		if ($ticket_owner eq '') {
			$ticket_owner = 'admin';
		}
		
		# Field 5 (Ticket type)
		my $ticket_type = $field5;
		if ($ticket_type eq '') {
			$ticket_type = 0;
		}

		# Field 6 (Ticket status)
		my $ticket_status = $field6;
		if ($ticket_status eq '0') {
			$ticket_status = 1;
		}

		# Field 7 (Ticket description);
		my $ticket_description = safe_output($field7);

		my $create_wu_on_close_recovery = 0;

		if ($alert_mode == RECOVERED_ALERT && $action->{'create_wu_integria'} == '1') {
			$create_wu_on_close_recovery = 1;
		}

		# Ticket type custom fields
		my $ticket_custom_field1 = $field8;
		my $ticket_custom_field2 = $field9;
		my $ticket_custom_field3 = $field10;
		my $ticket_custom_field4 = $field11;
		my $ticket_custom_field5 = $field12;
		my $ticket_custom_field6 = $field13;
		my $ticket_custom_field7 = $field14;
		my $ticket_custom_field8 = $field15;
		my $ticket_custom_field9 = $field16;
		my $ticket_custom_field10 = $field17;
		my $ticket_custom_field11 = $field18;
		my $ticket_custom_field12 = $field19;
		my $ticket_custom_field13 = $field20;

		pandora_create_integria_ticket($pa_config, $api_path, $api_pass, $integria_user, $integria_user_pass, $agent->{'nombre'}, $agent->{'alias'}, $agent->{'id_os'}, $agent->{'direccion'}, $agent->{'id_agente'}, $agent->{'id_grupo'}, $ticket_name, $ticket_group_id, $ticket_priority, $ticket_owner, $ticket_type, $ticket_status, $ticket_description, $create_wu_on_close_recovery, $ticket_custom_field1, $ticket_custom_field2, $ticket_custom_field3, $ticket_custom_field4, $ticket_custom_field5, $ticket_custom_field6, $ticket_custom_field7, $ticket_custom_field8, $ticket_custom_field9, $ticket_custom_field10, $ticket_custom_field11, $ticket_custom_field12, $ticket_custom_field13);

	# Generate notification
	} elsif ($clean_name eq "Generate Notification") {

		# Translate macros
		$field3 = subst_alert_macros($field3, \%macros, $pa_config, $dbh, $agent, $module, $alert);

		# If no targets ignore notification
		if (defined($field1) && defined($field2) && ($field1 ne "" || $field2 ne "")) {
			my @user_list = map {clean_blank($_)} split /,/, $field1;
			my @group_list = map {clean_blank($_)} split /,/, $field2;

			my $notification = {};
			$notification->{'subject'} = safe_input($field3);
			$notification->{'mensaje'} = safe_input($field4);
			$notification->{'id_source'} = get_db_value($dbh, 'SELECT id FROM tnotification_source WHERE description = ?', safe_input('System status'));

			# Create message
			my $notification_id = db_process_insert($dbh,'id_mensaje','tmensajes',$notification);
			if (!$notification_id) {
				logger($pa_config, "Failed action '" . $action->{'name'} . "' for alert '". $alert->{'name'} . "' agent '" . (defined($agent) ? $agent->{'alias'} : 'N/A') . "'.", 3);
			} else {
				notification_set_targets($pa_config, $dbh, $notification_id, \@user_list, \@group_list);
			}
		} else {
			logger($pa_config, "Failed action '" . $action->{'name'} . "' for alert '". $alert->{'name'} . "' agent '" . (defined($agent) ? $agent->{'alias'} : 'N/A') . "' Empty targets. Ignored.", 3);
		}

	# Unknown
	} else {
		logger($pa_config, "Unknown action '" . $action->{'name'} . "' for alert '". $alert->{'name'} . "' agent '" . (defined ($agent) ? $agent->{'alias'} : 'N/A') . "'.", 3);
	}
	
	# Update action last execution date
	if (defined ($action->{'last_execution'}) && defined ($action->{'id_alert_templ_module_actions'})) {
		db_do ($dbh, 'UPDATE talert_template_module_actions SET last_execution = ?
 			WHERE id = ?', int(time ()), $action->{'id_alert_templ_module_actions'});
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

	# Ensure default values.
	$module->{'min_ff_event'} = 0 unless defined($module->{'min_ff_event'});
	$module->{'ff_timeout'} = 0 unless defined($module->{'ff_timeout'});
	$module->{'module_interval'} = 0 unless defined($module->{'module_interval'});
	
	if (ref($agent) eq 'HASH') {
		if (!defined($agent->{'interval'}) && defined($agent->{'interval'})) {
			$agent->{'intervalo'} = $agent->{'interval'};
		}
	}
	
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
	my $known_status = $agent_status->{'known_status'};
	my $status_changes = $agent_status->{'status_changes'};
	my $last_data_value = $agent_status->{'datos'};
	my $last_known_status = $agent_status->{'last_known_status'};
	my $last_error = defined ($module->{'last_error'}) ? $module->{'last_error'} : $agent_status->{'last_error'};
	my $ff_start_utimestamp = $agent_status->{'ff_start_utimestamp'};
	my $mark_for_update = 0;
	
	# tagente_estado.last_try defaults to NULL, should default to '1970-01-01 00:00:00'
	$agent_status->{'last_try'} = '1970-01-01 00:00:00' unless defined ($agent_status->{'last_try'});
	$agent_status->{'datos'} = "" unless defined($agent_status->{'datos'});
	
	# Do we have to save module data?
	if ($agent_status->{'last_try'} !~ /(\d+)\-(\d+)\-(\d+) +(\d+):(\d+):(\d+)/) {
		logger($pa_config, "Invalid last try timestamp '" . $agent_status->{'last_try'} . "' for agent '" . $agent->{'nombre'} . "' not found while processing module '" . $module->{'nombre'} . "'.", 3);
		pandora_update_module_on_error ($pa_config, $module, $dbh);
		return;
	}

	my $last_try = ($1 == 0) ? 0 : strftime("%s", $6, $5, $4, $3, $2 - 1, $1 - 1900);

	my $save = ($module->{'history_data'} == 1 && ($agent_status->{'datos'} ne $processed_data || $last_try < ($utimestamp - 86400))) ? 1 : 0;
	
	# Received stale data. Save module data if needed and return.
	if ($pa_config->{'dataserver_lifo'} == 1 && $utimestamp <= $agent_status->{'utimestamp'}) {
		logger($pa_config, "Received stale data from agent " . (defined ($agent) ? "'" . $agent->{'nombre'} . "'" : 'ID ' . $module->{'id_agente'}) . ".", 10);
		
		# Save module data. Compression does not work for stale data.
		if ($module->{'history_data'} == 1) {
			save_module_data ($data_object, $module, $module_type, $utimestamp, $dbh);
		}

		return;
	}

	# Get new status
	my $new_status = get_module_status ($processed_data, $module, $module_type, $last_data_value);
	my $last_status_change = $agent_status->{'last_status_change'};

	# Set the last status change macro. Even if its value changes later, whe want the original value.
	$extra_macros->{'_modulelaststatuschange_'} = $last_status_change;
	
	# Calculate the current interval
	my $current_interval;
	if (defined ($module->{'cron_interval'}) && $module->{'cron_interval'} ne '' && $module->{'cron_interval'} ne '* * * * *') {
		$current_interval = cron_next_execution (
			$module->{'cron_interval'},
			$module->{'module_interval'} == 0 ? $agent->{'intervalo'} : $module->{'module_interval'}
		);
	}
	elsif ($module->{'module_interval'} == 0) {
		$current_interval = $agent->{'intervalo'};
	}
	else {
		$current_interval = $module->{'module_interval'};
	}

	# Update module status.
	my $min_ff_event = $module->{'min_ff_event'};
	my $current_utimestamp = time ();
	my $ff_timeout = $module->{'ff_timeout'};

	# Counters.
	my $ff_warning = $agent_status->{'ff_warning'};
	my $ff_critical = $agent_status->{'ff_critical'};
	my $ff_normal = $agent_status->{'ff_normal'};

	if ($module->{'each_ff'}) {
		$min_ff_event = $module->{'min_ff_event_normal'} if ($new_status == 0);
		$min_ff_event = $module->{'min_ff_event_critical'} if ($new_status == 1);
		$min_ff_event = $module->{'min_ff_event_warning'} if ($new_status == 2);
	}

	# Avoid warning if not initialized.
	$min_ff_event = 0 unless defined($min_ff_event);
	$module->{'ff_type'} = 0 unless defined($module->{'ff_type'});
	$module->{'module_ff_interval'} = 0 unless defined($module->{'module_ff_interval'});

	if ($last_known_status == $new_status) {
		# Avoid overflows
		$status_changes = $min_ff_event if ($status_changes > $min_ff_event && $module->{'ff_type'} == 0);
		
		$status_changes++;
		if ($module_type =~ m/async/ && $min_ff_event != 0 && $ff_timeout != 0 && ($utimestamp - $ff_start_utimestamp) > $ff_timeout) {
			# Only type ff with counters.
			$status_changes = 0 if ($module->{'ff_type'} == 0);
			
			$ff_start_utimestamp = $utimestamp;

			# Reset counters because expired timeout.
			$ff_normal = 0;
			$ff_critical = 0;
			$ff_warning = 0;
		}
	}
	else {
		# Only type ff with counters. 
		$status_changes = 0 if ($module->{'ff_type'} == 0);
		
		$ff_start_utimestamp = $utimestamp if ($module_type =~ m/async/);
	}
	
	if ($module->{'ff_type'} == 0) {
		# Active ff interval.
		if ($module->{'module_ff_interval'} != 0 && $status_changes < $min_ff_event) {
			$current_interval = $module->{'module_ff_interval'};
		}
		
		# Change status.
		if ($status_changes >= $min_ff_event && $known_status != $new_status) {
			generate_status_event ($pa_config, $processed_data, $agent, $module, $new_status, $status, $known_status, $dbh);
			$status = $new_status;

			# Update the change of status timestamp.
			$last_status_change = $utimestamp;

			# Update module status count.
			$mark_for_update = 1;

			# Safe mode execution.
			if ($agent->{'safe_mode_module'} == $module->{'id_agente_modulo'}) {
				safe_mode($pa_config, $agent, $module, $new_status, $known_status, $dbh);
			}
		}
	} else {
		# Increase counters.
		$ff_critical++ if ($new_status == 1);
		$ff_warning++  if ($new_status == 2);
		$ff_normal++   if ($new_status == 0);

		# Generate event for 'going_normal' only if status is previously different from 
		# Normal.
		if ( ($new_status != $status && ($new_status == 0 && $ff_normal > $min_ff_event))
			|| ($new_status == 1 && $ff_critical > $min_ff_event)
			|| ($new_status == 2 && $ff_warning > $min_ff_event)
		) {
			# Change status generate event.
			generate_status_event ($pa_config, $processed_data, $agent, $module, $new_status, $status, $known_status, $dbh);
			$status = $new_status;

			# Update the change of status timestamp.
			$last_status_change = $utimestamp;

			# Update module status count.
			$mark_for_update = 1;

			# Safe mode execution.
			if ($agent->{'safe_mode_module'} == $module->{'id_agente_modulo'}) {
				safe_mode($pa_config, $agent, $module, $new_status, $known_status, $dbh);
			}

			# After launch an event, counters are reset.
			$ff_normal = 0;
			$ff_critical = 0;
			$ff_warning = 0;

		} else {
			if($new_status == 0 && $ff_normal > $min_ff_event) {
				# Reached normal FF but status have not changed, reset counters.
				$ff_normal = 0;
				$ff_critical = 0;
				$ff_warning = 0;
			}

			# Active ff interval
			if ($module->{'module_ff_interval'} != 0) {
				$current_interval = $module->{'module_ff_interval'};
			}
		}
	}

	# Set not-init modules to normal status even if min_ff_event is not matched the first time they receive data.
	# if critical or warning status, just pass through here and wait the time min_ff_event will be matched.
	if ($status == 4) {
		generate_status_event ($pa_config, $processed_data, $agent, $module, 0, $status, $known_status, $dbh);
		$status = 0;

		# Update the change of status timestamp.
		$last_status_change = $utimestamp;

		# Update module status count.
		$mark_for_update = 1;
	}
	# If unknown modules receive data, restore status even if min_ff_event is not matched.
	elsif ($status == 3) {
		generate_status_event ($pa_config, $processed_data, $agent, $module, $known_status, $status, $known_status, $dbh);
		$status = $known_status;

		# Update the change of status timestamp.
		$last_status_change = $utimestamp;

		# reset counters because change status.
		$ff_normal = 0;
		$ff_critical = 0;
		$ff_warning = 0;

		# Update module status count.
		$mark_for_update = 1;
	}
		
	
	# Never update tagente_estado when processing out-of-order data.
	if ($utimestamp >= $last_try) {
		db_do ($dbh, 'UPDATE tagente_estado
			SET datos = ?, estado = ?, known_status = ?, last_status = ?, last_known_status = ?,
				status_changes = ?, utimestamp = ?, timestamp = ?,
				id_agente = ?, current_interval = ?, running_by = ?,
				last_execution_try = ?, last_try = ?, last_error = ?,
				ff_start_utimestamp = ?, ff_normal = ?, ff_warning = ?, ff_critical = ?,
				last_status_change = ?
			WHERE id_agente_modulo = ?', $processed_data, $status, $status, $new_status, $new_status, $status_changes,
			$current_utimestamp, $timestamp, $module->{'id_agente'}, $current_interval, $server_id,
			$utimestamp, ($save == 1) ? $timestamp : $agent_status->{'last_try'}, $last_error, $ff_start_utimestamp,
			$ff_normal, $ff_warning, $ff_critical, $last_status_change, $module->{'id_agente_modulo'});
	}

	# Save module data. Async and log4x modules are not compressed.
	if ($module_type =~ m/(async)|(log4x)/ || $save == 1) {
		save_module_data ($data_object, $module, $module_type, $utimestamp, $dbh);
	}

	# Generate alerts
	if (pandora_inhibit_alerts ($pa_config, $agent, $dbh, 0) == 0 && pandora_cps_enabled($agent, $module) == 0) {
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
		WHERE type_downtime != ?
			AND type_execution = ?
			AND executed = 1
			AND date_to <= ?', 'quiet', 'once', $utimestamp);
	
	foreach my $downtime (@downtimes) {
		
		logger($pa_config, "Ending planned downtime '" . $downtime->{'name'} . "'.", 10);
		
		db_do($dbh, 'UPDATE tplanned_downtime
			SET executed = 0
			WHERE id = ?', $downtime->{'id'});
		pandora_event ($pa_config,
			'(Created by ' . $downtime->{'id_user'} . ') Server ' . $pa_config->{'servername'} . ' stopped planned downtime: ' . safe_output($downtime->{'name'}), 0, 0, 1, 0, 0, 'system', 0, $dbh);
		
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
		WHERE type_downtime != ?
			AND type_execution = ?
			AND executed = 0 AND date_from <= ?
			AND date_to >= ?', 'quiet', 'once', $utimestamp, $utimestamp);
	
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
			"(Created by " . $downtime->{'id_user'} . ") Server ".$pa_config->{'servername'}." started planned downtime: ".safe_output($downtime->{'name'}), 0, 0, 1, 0, 0, 'system', 0, $dbh);
		
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
	
	my $only_alerts = 0;
		
	if ($downtime->{'only_alerts'} == 0) {
		if ($downtime->{'type_downtime'} eq 'disable_agents_alerts') {
			$only_alerts = 1;
		}
	}
		
	if ($only_alerts == 0) {
		db_do($dbh,'UPDATE tplanned_downtime_agents tp, tagente ta
			SET tp.manually_disabled = ta.disabled
			WHERE tp.id_agent = ta.id_agente AND tp.id_downtime = ?',$downtime->{'id'});
		
		db_do($dbh,'UPDATE tagente ta, tplanned_downtime_agents tpa
			SET ta.disabled = 1, ta.update_module_count = 1
			WHERE tpa.id_agent = ta.id_agente AND
			tpa.id_downtime = ?',$downtime->{'id'});
			
	} else {
		my @downtime_agents = get_db_rows($dbh, 'SELECT *
			FROM tplanned_downtime_agents
			WHERE id_downtime = ' . $downtime->{'id'});
			
		foreach my $downtime_agent (@downtime_agents) {
			db_do ($dbh, 'UPDATE talert_template_modules tat, tagente_modulo tam
				SET tat.disabled = 1
				WHERE tat.id_agent_module = tam.id_agente_modulo 
				AND tam.id_agente = ?', $downtime_agent->{'id_agent'});
		}
	}
}

########################################################################
=head2 C<< pandora_planned_downtime_unset_disabled_elements (I<$pa_config>, I<$dbh>, <$id_downtime>) >> 

Start the planned downtime, the once type. 

=cut
########################################################################
sub pandora_planned_downtime_unset_disabled_elements($$$) {
	my ($pa_config, $dbh, $downtime) = @_;
	
	my $only_alerts = 0;
		
	if ($downtime->{'only_alerts'} == 0) {
		if ($downtime->{'type_downtime'} eq 'disable_agents_alerts') {
			$only_alerts = 1;
		}
	}
		
	if ($only_alerts == 0) {
		db_do($dbh,'UPDATE tagente ta, tplanned_downtime_agents tpa
			set ta.disabled = 0, ta.update_module_count = 1
			WHERE tpa.id_agent = ta.id_agente AND
			tpa.manually_disabled = 0 AND tpa.id_downtime = ?',$downtime->{'id'});
	} else {
		my @downtime_agents = get_db_rows($dbh, 'SELECT *
			FROM tplanned_downtime_agents
			WHERE id_downtime = ' . $downtime->{'id'});
			
		foreach my $downtime_agent (@downtime_agents) {
			db_do ($dbh, 'UPDATE talert_template_modules tat, tagente_modulo tam
				SET tat.disabled = 0
				WHERE tat.id_agent_module = tam.id_agente_modulo 
				AND tam.id_agente = ?', $downtime_agent->{'id_agent'});
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

Stop the planned downtime, the once type. 

=cut
########################################################################
sub pandora_planned_downtime_quiet_once_stop($$) {
	my ($pa_config, $dbh) = @_;
	my $utimestamp = time();
	
	# Stop executed downtimes
	my @downtimes = get_db_rows($dbh, 'SELECT *
		FROM tplanned_downtime
		WHERE type_downtime = ?
			AND type_execution = ?
			AND executed = 1 AND date_to <= ?', 'quiet', 'once', $utimestamp);
	
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
			"(Created by " . $downtime->{'id_user'} . ") Server ".$pa_config->{'servername'}." stopped planned downtime: ".safe_output($downtime->{'name'}), 0, 0, 1, 0, 0, 'system', 0, $dbh);
		
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
		WHERE type_downtime = ?
			AND type_execution = ?
			AND executed = 0 AND date_from <= ?
			AND date_to >= ?', 'quiet', 'once', $utimestamp, $utimestamp);
	
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
		print"pandora_planned_downtime_quiet_once_start\n";
		pandora_event ($pa_config,
			"(Created by " . $downtime->{'id_user'} . ") Server ".$pa_config->{'servername'}." started planned downtime: ".safe_output($downtime->{'name'}), 0, 0, 1, 0, 0, 'system', 0, $dbh);
		
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
		WHERE type_periodicity = ?
			AND executed = 0
			AND type_execution <> ' . $RDBMS_QUOTE_STRING . 'once' . $RDBMS_QUOTE_STRING . '
			AND ((periodically_day_from = ? AND periodically_time_from <= ?) OR (periodically_day_from < ?))
			AND ((periodically_day_to = ? AND periodically_time_to >= ?) OR (periodically_day_to > ?))',
			'monthly',
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
		print"pandora_planned_downtime_monthly_start\n";
		pandora_event ($pa_config,
			"Server ".$pa_config->{'servername'}." started planned downtime: ".safe_output($downtime->{'name'}), 0, 0, 1, 0, 0, 'system', 0, $dbh);
		
		
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

Stop the planned downtime, the monthly type. 

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
	
	# Stop executed downtimes
	my @downtimes = get_db_rows($dbh, 'SELECT *
		FROM tplanned_downtime
		WHERE type_periodicity = ?
			AND executed = 1
			AND type_execution <> ?
			AND (((periodically_day_from = ? AND periodically_time_from > ?) OR (periodically_day_from > ?))
				OR ((periodically_day_to = ? AND periodically_time_to < ?) OR (periodically_day_to < ?)))',
			'monthly', 'once',
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
		print"pandora_planned_downtime_monthly_stop\n";
		pandora_event ($pa_config,
			"Server ".$pa_config->{'servername'}." stopped planned downtime: ".safe_output($downtime->{'name'}), 0, 0, 1, 0, 0, 'system', 0, $dbh);
		
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

Start the planned downtime, the weekly type. 

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
		WHERE type_periodicity = ? 
			AND type_execution <> ?
			AND executed = 0', 'weekly', 'once');
	
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
				"Server ".$pa_config->{'servername'}." started planned downtime: ".safe_output($downtime->{'name'}), 0, 0, 1, 0, 0, 'system', 0, $dbh);
				
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

Stop the planned downtime, the weekly type. 

=cut
########################################################################
sub pandora_planned_downtime_weekly_stop($$) {
	my ($pa_config, $dbh) = @_;
	
	my @var_localtime = localtime(time);
	
	my $number_day_week = $var_localtime[6];
	
	my $time = sprintf("%02d:%02d:%02d", $var_localtime[2], $var_localtime[1], $var_localtime[0]);
	
	my $found = 0;
	my $stop_downtime = 0;
	
	# Stop executed downtimes
	my @downtimes = get_db_rows($dbh, 'SELECT *
		FROM tplanned_downtime
		WHERE type_periodicity = ?
			AND type_execution <> ?
			AND executed = 1', 'weekly', 'once');
	
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
				"Server ".$pa_config->{'servername'}." stopped planned downtime: ".safe_output($downtime->{'name'}), 0, 0, 1, 0, 0, 'system', 0, $dbh);
			
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
		WHERE BINARY name = ?', $pa_config->{'servername'});
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
sub pandora_update_server ($$$$$$;$$$$) {
	my ($pa_config, $dbh, $server_name, $server_id, $status,
		$server_type, $num_threads, $queue_size, $version, $keepalive) = @_;
	
	$num_threads = 0 unless defined ($num_threads);
	$queue_size = 0 unless defined ($queue_size);
	$keepalive = $pa_config->{'keepalive'} unless defined ($keepalive);

	my $timestamp = strftime ("%Y-%m-%d %H:%M:%S", localtime());
	$version = $pa_config->{'version'} . ' (P) ' . $pa_config->{'build'} unless defined($version);
	
	my $master = ($server_type == SATELLITESERVER) ? 0 : $pa_config->{'pandora_master'};
	
	# First run
	if ($server_id == 0) { 
		
		# Create an entry in tserver if needed
		my $server = get_db_single_row ($dbh, 'SELECT id_server FROM tserver WHERE BINARY name = ? AND server_type = ?', $server_name, $server_type);
		if (! defined ($server)) {
			$server_id = db_insert ($dbh, 'id_server', 'INSERT INTO tserver (name, server_type, description, version, threads, queued_modules, server_keepalive)
						VALUES (?, ?, ?, ?, ?, ?, ?)', $server_name, $server_type,
						'Autocreated at startup', $version, $num_threads, $queue_size, $keepalive);
		
			$server = get_db_single_row ($dbh, 'SELECT status FROM tserver WHERE id_server = ?', $server_id);
			if (! defined ($server)) {
				logger($pa_config, "Server '" . $pa_config->{'servername'} . "' not found.", 3);
				return;
			}
		} else {
			$server_id = $server->{'id_server'};
		}

		db_do ($dbh, 'UPDATE tserver SET status = ?, keepalive = ?, master = ?, laststart = ?, version = ?, threads = ?, queued_modules = ?, server_keepalive = ?
				WHERE id_server = ?',
				1, $timestamp, $master, $timestamp, $version, $num_threads, $queue_size, $keepalive, $server_id);
		return;
	}
	
	db_do ($dbh, 'UPDATE tserver SET status = ?, keepalive = ?, master = ?, version = ?, threads = ?, queued_modules = ?, server_keepalive = ?
			WHERE id_server = ?', $status, $timestamp, $master, $version, $num_threads, $queue_size, $keepalive, $server_id);
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
=head2 C<< pandora_mark_transactional_agent (I<$id_agente>) >>

Set an agent as transactional agent

=cut
##########################################################################
sub pandora_mark_transactional_agent($$) {
	my ($dbh, $id_agente) = @_;

	if ( (!(defined($id_agente))) || (!(defined($dbh))) ) {
		return;
	}

	db_do ($dbh, "UPDATE tagente SET transactional_agent=1 WHERE id_agente = ?", $id_agente);
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
		"INSERT INTO talert_template_modules(id_agent_module,
		                                     id_alert_template,
		                                     id_policy_alerts,
		                                     disabled,
		                                     standby,
		                                     last_reference)
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
		SET id_policy_alerts = ?,
			disabled =  ?,
			standby = ?
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
=head2 C<< pandora_create_alert_command(I<$pa_config>, I<$parameters>, I<$dbh>) >>

Create a alert command.

=cut
########################################################################
sub pandora_create_alert_command ($$$) {
	my ($pa_config, $parameters, $dbh) = @_;
	
	logger($pa_config, "Creating alert command '$parameters->{'name'}'.", 10);
	
	my $command_id = db_process_insert($dbh, 'id', 'talert_commands', $parameters);
	
	return $command_id;
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
	db_do ($dbh, 'INSERT INTO tagente_estado (id_agente_modulo, id_agente, estado, known_status, last_status, last_known_status, last_try, datos)
		VALUES (?, ?, ?, ?, ?, ?, \'1970-01-01 00:00:00\', \'\')',
		$module_id, $agent_id, $status, $status, $status, $status);
	
	# Update the module status count. When the module is created disabled dont do it
	pandora_mark_agent_for_module_update ($dbh, $agent_id);
	
	return $module_id;
}

##########################################################################
=head2 C<< pandora_delete_module (I<$dbh>, I<$module_id>, I<$conf>) >> 

Delete a module given its id.

=cut
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
	
	if ((defined($conf)) && (-e $conf->{incomingdir}.'/conf/'.md5(encode_utf8(safe_output($agent_name))).'.conf')) {
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

	return $module_id;
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
	delete $parameters->{'manufacturer_id'};
	delete $parameters->{'enabled'};
	delete $parameters->{'scan_type'};
	delete $parameters->{'execution_type'};
	delete $parameters->{'query_filters'};
	delete $parameters->{'query_class'};
	delete $parameters->{'protocol'};
	delete $parameters->{'value_operations'};
	delete $parameters->{'value'};
	delete $parameters->{'module_enabled'};
	delete $parameters->{'scan_filters'};
	delete $parameters->{'query_key_field'};
	delete $parameters->{'name_oid'};
	delete $parameters->{'module_type'};

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
	if (defined $parameters->{'timestamp'}) {
		delete $parameters->{'timestamp'};
	}

	# Encrypt plug-in passwords.
	if (defined($parameters->{'plugin_pass'})) {
		$parameters->{'plugin_pass'} = pandora_input_password($pa_config, $parameters->{'plugin_pass'});
	}

	# Encrypt SNMP v3 passwords.
	if (defined($parameters->{'tcp_send'})
		&& $parameters->{'tcp_send'} eq '3'
		&& defined($parameters->{'id_tipo_modulo'})
		&& $parameters->{'id_tipo_modulo'} >= 15
		&& $parameters->{'id_tipo_modulo'} <= 18
	) {
		$parameters->{'custom_string_2'} = pandora_input_password($pa_config, $parameters->{'custom_string_2'});
	}

	my $module_id = db_process_insert($dbh, 'id_agente_modulo',
		'tagente_modulo', $parameters);
	
	my $status = 4;
	if (defined ($parameters->{'id_tipo_modulo'})
		&& ($parameters->{'id_tipo_modulo'} == 21
			|| $parameters->{'id_tipo_modulo'} == 22
			|| $parameters->{'id_tipo_modulo'} == 23)
	) {
		$status = 0;
	}
	
	db_do ($dbh, 'INSERT INTO tagente_estado (id_agente_modulo, id_agente, estado, known_status, last_status, last_known_status, last_try, datos) VALUES (?, ?, ?, ?, ?, ?, \'1970-01-01 00:00:00\', \'\')', $module_id, $parameters->{'id_agente'}, $status, $status, $status, $status);
	
	# Update the module status count. When the module is created disabled dont do it
	pandora_mark_agent_for_module_update ($dbh, $parameters->{'id_agente'});
	
	return $module_id;
}

##########################################################################
## Update an agent module from hash
##########################################################################
sub pandora_update_module_from_hash ($$$$$) {
	my ($pa_config, $parameters, $where_column, $where_value, $dbh) = @_;
	
	my $module_id = db_process_update($dbh, 'tagente_modulo', $parameters, {$where_column => $where_value});
	return $module_id;
}

##########################################################################
## Update a table from hash
##########################################################################
sub pandora_update_table_from_hash ($$$$$$) {
	my ($pa_config, $parameters, $where_column, $where_value, $table, $dbh) = @_;
	
	my $module_id = db_process_update($dbh, $table, $parameters, {$where_column => $where_value});
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
## Select custom field id by name tagent_custom_field 
##########################################################################
sub pandora_select_id_custom_field ($$) {
	my ($dbh, $field) = @_;
	my $result = undef;

	$result = get_db_single_row ($dbh, 'SELECT id_field FROM tagent_custom_fields WHERE name = ? ', safe_input($field));

	return $result->{'id_field'};
}

##########################################################################
## Select custom field id by name tagent_custom_field 
##########################################################################
sub pandora_select_combo_custom_field ($$) {
	my ($dbh, $field) = @_;
	my $result = undef;

	$result = get_db_single_row ($dbh, 'SELECT combo_values FROM tagent_custom_fields WHERE id_field = ? ', $field);

	return $result->{'combo_values'};
}

##########################################################################
## Update a custom field from agent of tagent_custom_data 
##########################################################################
sub pandora_update_agent_custom_field ($$$$) {
	my ($dbh, $token, $field, $id_agent) = @_;
	my $result = undef;
	$result = db_update ($dbh, 'UPDATE tagent_custom_data SET description = ? WHERE id_field = ? AND id_agent = ?', safe_input($token), $field, $id_agent);

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
=head2 C<< pandora_get_credential (I<$dbh>, I<$identifier>) >>

 Get credential from credential store

=cut
##########################################################################
sub pandora_get_credential ($$$) {
	my ($pa_config, $dbh, $identifier) = @_;

	my $key = get_db_single_row($dbh, 'SELECT * FROM tcredential_store WHERE identifier = ?', $identifier);

	$key->{'username'} = pandora_output_password(
		$pa_config,
		safe_output($key->{'username'})
	);
	$key->{'password'} = pandora_output_password(
		$pa_config,
		safe_output($key->{'password'})
	);
	$key->{'extra_1'} =  pandora_output_password(
		$pa_config,
		safe_output($key->{'extra_1'})
	);
	$key->{'extra_2'} =  pandora_output_password(
		$pa_config,
		safe_output($key->{'extra_2'})
	);

	return $key;
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
			"INSERT INTO ttag_module(id_tag, id_agente_modulo)
			VALUES (?, ?)",
			$tag_id, $id_agent_module);
	}
}

##########################################################################
=head2 C<< pandora_create_agent (I<$pa_config>, I<$server_name>, I<$agent_name>, I<$address>, I<$group_id>, I<$parent_id>, I<$os_id>, I<$description>, I<$interval>, I<$dbh>, [I<$timezone_offset>], [I<$longitude>], [I<$latitude>], [I<$altitude>], [I<$position_description>], [I<$custom_id>], [I<$url_address>]) >>

Create a new entry in B<tagente> optionaly with position information

=cut
##########################################################################
sub pandora_create_agent ($$$$$$$$$$;$$$$$$$$$$) {
	# If parameter event_id is not undef, then create an extended event
	# related to it instead launch new event.
	my ($pa_config, $server_name, $agent_name, $address,
		$group_id, $parent_id, $os_id,
		$description, $interval, $dbh, $timezone_offset,
		$longitude, $latitude, $altitude, $position_description,
		$custom_id, $url_address, $agent_mode, $alias, $event_id) = @_;
	
	logger ($pa_config, "Server '$server_name' creating agent '$agent_name' address '$address'.", 10);
	
	if (!defined($group_id)) {
		$group_id = pandora_get_agent_group($pa_config, $dbh, $agent_name);
		if ($group_id <= 0) {
			logger($pa_config, "Unable to create agent '" . safe_output($agent_name) . "': No valid group found.", 3);
			return;
		}
	}
	
	$agent_mode = 1 unless defined($agent_mode);
	$alias = $agent_name unless defined($alias);

	$description = "Created by $server_name" unless (defined($description) && $description ne '');	
	my ($columns, $values) = db_insert_get_values ({ 'nombre' => safe_input($agent_name),
	                                                 'direccion' => $address,
	                                                 'comentarios' => $description,
	                                                 'id_grupo' => $group_id,
	                                                 'id_os' => $os_id,
	                                                 'server_name' => $server_name,
	                                                 'intervalo' => $interval,
	                                                 'id_parent' => $parent_id,
	                                                 'modo' => $agent_mode,
	                                                 'custom_id' => $custom_id,
	                                                 'url_address' => $url_address,
	                                                 'timezone_offset' => $timezone_offset,
	                                                 'alias' => safe_input($alias),
													 'update_module_count' => 1, # Force to replicate in metaconsole
	                                                });                           
	                                                
	my $agent_id = db_insert ($dbh, 'id_agente', "INSERT INTO tagente $columns", @{$values});

	# Save GIS data
	if (defined ($longitude) && defined ($latitude ) && $pa_config->{'activate_gis'} == 1 ) {

		# Save the first position
		my $timestamp = strftime ("%Y-%m-%d %H:%M:%S", localtime (time ()));
		save_agent_position($pa_config, $longitude, $latitude, $altitude, $agent_id, $dbh, $timestamp, $position_description) ;
	}
	
	logger ($pa_config, "Server '$server_name' CREATED agent '$agent_name' address '$address'.", 10);
	if (!defined($event_id)) {
		pandora_event ($pa_config, "Agent [" . safe_output($alias) . "] created by $server_name", $group_id, $agent_id, 2, 0, 0, 'new_agent', 0, $dbh);
	} else {
		pandora_extended_event($pa_config, $dbh, $event_id, "Agent [" . safe_output($alias) . "][#".$agent_id."] created by $server_name");
	}
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
		my $conf_fname = $conf->{incomingdir}.'/conf/'.md5(encode_utf8(safe_output($agent_name))).'.conf';
		unlink($conf_fname) if (-f $conf_fname);
		
		my $md5_fname = $conf->{incomingdir}.'/md5/'.md5(encode_utf8(safe_output($agent_name))).'.md5';
		unlink($md5_fname) if (-f $md5_fname);
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
sub pandora_event ($$$$$$$$$$;$$$$$$$$$$$$) {
	my ($pa_config, $evento, $id_grupo, $id_agente, $severity,
		$id_alert_am, $id_agentmodule, $event_type, $event_status, $dbh,
		$source, $user_name, $comment, $id_extra, $tags,
		$critical_instructions, $warning_instructions, $unknown_instructions, $custom_data,
		$module_data, $module_status, $server_id) = @_;
	my $event_table = is_metaconsole($pa_config) ? 'tmetaconsole_event' : 'tevento';

	my $agent = undef;
	if (defined($id_agente) && $id_agente != 0) {
		$agent = get_db_single_row ($dbh, 'SELECT *	FROM tagente WHERE id_agente = ?', $id_agente);
		if (defined ($agent) && $agent->{'quiet'} == 1) {
			logger($pa_config, "Generate Event. The agent '" . $agent->{'nombre'} . "' is in quiet mode.", 10);
			return;
		}
	}

	my $module = undef;
	if (defined($id_agentmodule) && $id_agentmodule != 0) {
		$module = get_db_single_row ($dbh, 'SELECT *, tagente_estado.datos, tagente_estado.estado
		                                    FROM tagente_modulo, tagente_estado
                                            WHERE tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo
											AND tagente_modulo.id_agente_modulo = ?', $id_agentmodule);
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
	$source = 'monitoring_server' unless defined ($source);
	$comment = '' unless defined ($comment);
	$id_extra = '' unless defined ($id_extra);
	$user_name = '' unless defined ($user_name);
	$critical_instructions = '' unless defined ($critical_instructions);
	$warning_instructions = '' unless defined ($warning_instructions);
	$unknown_instructions = '' unless defined ($unknown_instructions);
	$custom_data = '' unless defined ($custom_data);
	$server_id = 0 unless defined ($server_id);
	$module_data = defined($module) ? $module->{'datos'} : '' unless defined ($module_data);
	$module_status = defined($module) ? $module->{'estado'} : '' unless defined ($module_status);
	
	# If the event is created with validated status, assign ack_utimestamp
	my $ack_utimestamp = $event_status == 1 ? time() : 0;
	
	my $utimestamp = time ();
	my $timestamp = strftime ("%Y-%m-%d %H:%M:%S", localtime ($utimestamp));
	$id_agentmodule = 0 unless defined ($id_agentmodule);
	
	if($comment ne '') {
		my @comment_data = ({ comment => $comment, action => "Added comment", id_user => $user_name, utimestamp => $utimestamp});
		$comment = encode_json \@comment_data;
	}
	
	# Validate events with the same event id
	if (defined ($id_extra) && $id_extra ne '') {
		logger($pa_config, "Updating events with extended id '$id_extra'.", 10);
		db_do ($dbh, 'UPDATE ' . $event_table . ' SET estado = 1, ack_utimestamp = ? WHERE estado IN (0,2) AND id_extra=?', $utimestamp, $id_extra);
	}
	
	my $event_id = undef;

	# Create the event
	logger($pa_config, "Generating event '$evento' for agent ID $id_agente module ID $id_agentmodule.", 10);
	if (is_metaconsole($pa_config)) {
			$event_id = db_insert ($dbh, 'id_evento','INSERT INTO ' . $event_table . ' (id_agente, id_grupo, evento, timestamp, estado, utimestamp, event_type, id_agentmodule, id_alert_am, criticity, user_comment, tags, source, id_extra, id_usuario, critical_instructions, warning_instructions, unknown_instructions, ack_utimestamp, server_id, custom_data, data, module_status)
	              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', $id_agente, $id_grupo, safe_input ($evento), $timestamp, $event_status, $utimestamp, $event_type, $id_agentmodule, $id_alert_am, $severity, $comment, $module_tags, $source, $id_extra, $user_name, $critical_instructions, $warning_instructions, $unknown_instructions, $ack_utimestamp, $server_id, $custom_data, safe_input($module_data), $module_status);
	} else {
			$event_id = db_insert ($dbh, 'id_evento','INSERT INTO ' . $event_table . ' (id_agente, id_grupo, evento, timestamp, estado, utimestamp, event_type, id_agentmodule, id_alert_am, criticity, user_comment, tags, source, id_extra, id_usuario, critical_instructions, warning_instructions, unknown_instructions, ack_utimestamp, custom_data, data, module_status)
	              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', $id_agente, $id_grupo, safe_input ($evento), $timestamp, $event_status, $utimestamp, $event_type, $id_agentmodule, $id_alert_am, $severity, $comment, $module_tags, $source, $id_extra, $user_name, $critical_instructions, $warning_instructions, $unknown_instructions, $ack_utimestamp, $custom_data, safe_input($module_data), $module_status);
	}

	# Do not write to the event file
	return $event_id if ($pa_config->{'event_file'} eq '');

	# Add a header when the event file is created
	my $header = undef;
	if (! -f $pa_config->{'event_file'}) {
		$header = "agent_name,group_name,evento,timestamp,estado,utimestamp,event_type,module_name,alert_name,criticity,user_comment,tags,source,id_extra,id_usuario,critical_instructions,warning_instructions,unknown_instructions,ack_utimestamp";
	}
	
	# Open the event file for writing
	if (! open (EVENT_FILE, '>>' . $pa_config->{'event_file'})) {
		logger($pa_config, "Error opening event file " . $pa_config->{'event_file'} . ": $!", 10);
		return $event_id;
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

	return $event_id;
}

##########################################################################
=head2 C<< pandora_extended_event (I<$pa_config>, I<$dbh>, I<$event_id>, I<$description>) >> 

Creates an extended event linked to an existing main event id.

=cut
##########################################################################
sub pandora_extended_event($$$$) {
	my ($pa_config, $dbh, $event_id, $description) = @_;

	return unless defined($event_id) && "$event_id" ne "" && $event_id > 0;

	return db_do(
		$dbh,
		'INSERT INTO tevent_extended (id_evento, utimestamp, description) VALUES (?,?,?)',
		$event_id,
		time(),
		safe_input($description)
	);
}

##########################################################################
# Returns a valid group ID to place an agent on success, -1 on error.
##########################################################################
sub pandora_get_agent_group {
	my ($pa_config, $dbh, $agent_name, $agent_group, $agent_group_password) = @_;

	my $group_id;
	my $auto_group = $pa_config->{'autocreate_group_name'} ne '' ? $pa_config->{'autocreate_group_name'} : $pa_config->{'autocreate_group'};
	my @groups = $pa_config->{'autocreate_group_force'} == 1 ? ($auto_group, $agent_group) : ($agent_group, $auto_group);
	foreach my $group (@groups) {
		next unless defined($group);

		# Does the group exist?
		if ($group eq $pa_config->{'autocreate_group'}) {
			next if ($group <= 0);
			$group_id = $group;
			if (!defined(get_group_name ($dbh, $group_id))) {
				logger($pa_config, "Group ID " . $group_id . " does not exist.", 10);
				next;
			}
		} else {
			next if ($group eq '');
			$group_id = get_group_id ($dbh, $group);
			if ($group_id <= 0) {
				logger($pa_config, "Group " . $group . " does not exist.", 10);
				next;
			}
		}

		# Check the group password.
		my $rc = enterprise_hook('check_group_password', [$dbh, $group_id, $agent_group_password]);
		if (defined($rc) && $rc != 1) {
			logger($pa_config, "Agent " . safe_output($agent_name) . " did not send a valid password for group ID $group_id.", 10);
			next;
		}

		return $group_id;
	}

	return -1;
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
	if (defined($module->{'cron_interval'}) && $module->{'cron_interval'} ne '' && $module->{'cron_interval'} ne '* * * * *') {
		$current_interval = cron_next_execution (
			$module->{'cron_interval'},
			$module->{'module_interval'} == 0 ? 300 : $module->{'module_interval'}
		);
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

		pandora_execute_alert ($pa_config, 'N/A', $agent, $module, $alert, 1, $dbh, undef, 1, undef);

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

	# Warmup interval for keepalive modules.
	if ($pa_config->{'warmup_unknown_on'} == 1) {

		return if (time() < $pa_config->{'__start_utimestamp__'} + $pa_config->{'warmup_unknown_interval'});

		# Disabled from pandora_module_unknown.
	}

	my @modules = get_db_rows ($dbh, 'SELECT tagente_modulo.*
					FROM tagente_modulo, tagente_estado, tagente 
					WHERE tagente.id_agente = tagente_estado.id_agente 
					AND tagente.disabled = 0 
					AND tagente_modulo.id_tipo_modulo = 100 
					AND tagente_modulo.disabled = 0 
					AND (tagente_modulo.flag = 1 OR ((tagente_estado.last_execution_try + tagente_estado.current_interval) < UNIX_TIMESTAMP()))
					AND tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo 
					AND ( tagente_estado.utimestamp + (tagente.intervalo * ?) < UNIX_TIMESTAMP())', $pa_config->{'unknown_interval'});

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
	my $fired_position;

	# Find those that apply to the given SNMP trap
	foreach my $alert (@snmp_alerts) {

		my $alert_data = '';
		
		# Check if one alert has been thrown. If there is another with same position, tries to throw it. 
		if (defined($fired_position)) {
			last if ($fired_position != $alert->{'position'});
		}
		
		my ($times_fired, $internal_counter, $alert_type) =
			($alert->{'times_fired'}, $alert->{'internal_counter'}, $alert->{'alert_type'});

		# OID
		# Decode first, could be a complex regexp !
		$alert->{'oid'} = decode_entities($alert->{'oid'});
		my $oid = $alert->{'oid'};
		if ($oid ne '') {
			my $term = substr($oid, -1);
			# Strict match.
			if ($term eq '$') {
				chop($oid);
				next if ($trap_oid ne $oid && $trap_oid_text ne $oid);
			}
			# Partial match.
			else {
				next if (index ($trap_oid, $oid) == -1 && index ($trap_oid_text, $oid) == -1);
			}
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
		$macros{'_trap_id_'} = $trap_id;
		$macros{'_snmp_oid_'} = $trap_oid;
		$macros{'_snmp_value_'} = $trap_value;
		
		# Custom OID/value
		# Decode first, this could be a complex regexp !
		my $custom_oid = decode_entities($alert->{'custom_oid'});
		if ($custom_oid ne '') {
			
			# No match
			next if (valid_regex ($custom_oid) == 0 || $trap_custom_oid !~ m/^$custom_oid$/i);
			$alert_data .= " Custom: $trap_custom_oid";
		}

		# Parse variables data.
		my @custom_values = split("\t", $trap_custom_oid);

		# Evaluate variable filters
		my $filter_match = 1;
		for (my $i = 1; $i <= 20; $i++) {
			my $order_field = $alert->{'order_'.$i} - 1;

			# Only values greater than 0 allowed.
			next if $order_field < 0;

			my $filter_name = '_snmp_f' . $i . '_';
			my $filter_regex = safe_output ($alert->{$filter_name});
			my $field_value = $custom_values[$order_field];

			# No filter for the current binding var
			next if ($filter_regex eq '');
			
			# The referenced binding var does not exist
			if (! defined ($field_value)) {
				$filter_match = 0;
				last;
			}
			
			# Evaluate the filter
			eval {
				local $SIG{__DIE__};
				if ($field_value !~ m/$filter_regex/) {
					$filter_match = 0;
				}
			};

			# Probably an invalid regexp
			if ($@) {
				# Filter is ignored.
				logger($pa_config, "Invalid regex in SNMP alert #".$alert->{'id_as'}.": [".$filter_regex."]", 3);
				# Invalid regex are ignored, test next variables.
				next;
			}
			
			# The filter did not match
			last if ($filter_match == 0);
		}
		
		# A filter did not match
		next if ($filter_match == 0);

		# Assign values to _snmp_fx_ macros.
		my $count;
		for ($count = 0; defined ($custom_values[$count]); $count++) {
			my $macro_name = '_snmp_f' . ($count+1) . '_';
			my $target = $custom_values[$count];

			if (!defined($target)) {
				# Ignore emtpy data.
				$macros{$macro_name} = '';
				next;
			}

			if ($target =~ m/= \S+: (.*)/) {
				my $value = $1;
			
				# Strip leading and trailing double quotes
				$value =~ s/^"//;
				$value =~ s/"$//;
				
				$macros{$macro_name} = $value;
			} else {
				# Empty variable.
				$macros{$macro_name} = '';
			}
		}
		$count--;
		
		# Number of variables
		$macros{'_snmp_argc_'} = $count;

		# All variables
		$macros{'_snmp_argv_'} = $trap_custom_oid;
		
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
		$alert->{'al_field11'} = subst_alert_macros ($alert->{'al_field11'}, \%macros);
		$alert->{'al_field12'} = subst_alert_macros ($alert->{'al_field12'}, \%macros);
		$alert->{'al_field13'} = subst_alert_macros ($alert->{'al_field13'}, \%macros);
		$alert->{'al_field14'} = subst_alert_macros ($alert->{'al_field14'}, \%macros);
		$alert->{'al_field15'} = subst_alert_macros ($alert->{'al_field15'}, \%macros);
		$alert->{'al_field16'} = subst_alert_macros ($alert->{'al_field16'}, \%macros);
		$alert->{'al_field17'} = subst_alert_macros ($alert->{'al_field17'}, \%macros);
		$alert->{'al_field18'} = subst_alert_macros ($alert->{'al_field18'}, \%macros);
		$alert->{'al_field19'} = subst_alert_macros ($alert->{'al_field19'}, \%macros);
		$alert->{'al_field20'} = subst_alert_macros ($alert->{'al_field20'}, \%macros);
		

		# Check time threshold
		$alert->{'last_fired'} = '1970-01-01 00:00:00' unless defined ($alert->{'last_fired'});
		return unless ($alert->{'last_fired'} =~ /(\d+)\-(\d+)\-(\d+) +(\d+):(\d+):(\d+)/);
		my $last_fired = ($1 > 0) ? strftime("%s", $6, $5, $4, $3, $2 - 1, $1 - 1900) : 0;

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
				'field11' => $alert->{'al_field11'},
				'field12' => $alert->{'al_field12'},
				'field13' => $alert->{'al_field13'},
				'field14' => $alert->{'al_field14'},
				'field15' => $alert->{'al_field15'},
				'field16' => $alert->{'al_field16'},
				'field17' => $alert->{'al_field17'},
				'field18' => $alert->{'al_field18'},
				'field19' => $alert->{'al_field19'},
				'field20' => $alert->{'al_field20'},

				

				'description' => $alert->{'description'},
				'times_fired' => $times_fired,
				'time_threshold' => 0,
				'id' => $alert->{'id_alert'},
				'priority' => $alert->{'priority'},
				'disable_event' => $alert->{'disable_event'}
			);

			my %agent;

			my $this_agent = get_agent_from_addr ($dbh, $trap_agent);
			if (defined($this_agent)){
				%agent = ( 
					'nombre' => $this_agent->{'nombre'},
					'alias'  => $this_agent->{'alias'},
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
					'id_grupo' => $alert->{'id_group'}
				);
			}
			
			# Execute alert
			my $action = get_db_single_row ($dbh, 'SELECT talert_actions.name as action_name, talert_actions.*, talert_commands.*
							FROM talert_actions, talert_commands
							WHERE talert_actions.id_alert_command = talert_commands.id
							AND talert_actions.id = ?', $alert->{'id_alert'});

			my $trap_rcv_full = $trap_oid . " " . $trap_value. " ". $trap_type. " " . $trap_custom_oid;

			# Additional execution information for the console.
			my $custom_data = {
				'actions'	=> [],
			};

			pandora_execute_action ($pa_config, $trap_rcv_full, \%agent, \%alert, 1, $action, undef, $dbh, $timestamp, \%macros) if (defined ($action));
			push(@{$custom_data->{'actions'}}, safe_output($action->{'action_name'}));

			# Generate an event, ONLY if our alert action is different from generate an event.
			if ($action->{'id_alert_command'} != 3 && $alert->{'disable_event'} == 0){
				pandora_event (
					$pa_config,
					"SNMP alert fired (" . safe_output($alert->{'description'}) . ")",
					0,
					0,
					$alert->{'priority'},
					0,
					0,
					'alert_fired',
					0,
					$dbh,
					undef,
					undef,
					undef,
					undef,
					undef,
					undef,
					undef,
					undef,
					p_encode_json($pa_config, $custom_data));
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
				my $other_action = get_db_single_row ($dbh, 'SELECT talert_actions.name as action_name, talert_actions.*, talert_commands.*
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
					'field11' => $other_alert->{'al_field11'},
					'field12' => $other_alert->{'al_field12'},
					'field13' => $other_alert->{'al_field13'},
					'field14' => $other_alert->{'al_field14'},
					'field15' => $other_alert->{'al_field15'},
					'field16' => $other_alert->{'al_field16'},
					'field17' => $other_alert->{'al_field17'},
					'field18' => $other_alert->{'al_field18'},
					'field19' => $other_alert->{'al_field19'},
					'field20' => $other_alert->{'al_field20'},
					
					'description' => '',
					'times_fired' => $times_fired,
					'time_threshold' => 0,
					'id' => $other_alert->{'alert_type'},
					'priority' => $alert->{'priority'},
					'disable_event' => $alert->{'disable_event'}
				);

				# Additional execution information for the console.
				my $custom_data = {
					'actions'	=> [],
				};

				pandora_execute_action ($pa_config, $trap_rcv_full, \%agent, \%alert_action, 1, $other_action, undef, $dbh, $timestamp, \%macros) if (defined ($other_action));
				push(@{$custom_data->{'actions'}}, safe_output($other_action->{'action_name'}));
					
				# Generate an event, ONLY if our alert action is different from generate an event.
				if ($other_action->{'id_alert_command'} != 3 && $alert->{'disable_event'} == 0){
					pandora_event (
						$pa_config,
						"SNMP alert fired (" . safe_output($alert->{'description'}) . ")",
						0,
						0,
						$alert->{'priority'},
						0,
						0,
						'alert_fired',
						0,
						$dbh,
						undef,
						undef,
						undef,
						undef,
						undef,
						undef,
						undef,
						undef,
						p_encode_json($pa_config, $custom_data));
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
		
		$fired_position = $alert->{'position'};

	}
}

##########################################################################
# Search string for macros and substitutes them with their values.
##########################################################################
sub subst_alert_macros ($$;$$$$$) {
	my ($string, $macros, $pa_config, $dbh, $agent, $module, $alert) = @_;

	my $macro_regexp = join('|', keys %{$macros});

	my $subst_func;
	if (defined($string) && $string =~ m/^(?:(")(?:.*)"|(')(?:.*)')$/) {
		my $quote = $1 ? $1 : $2;
		$subst_func = sub {
			my $macro = on_demand_macro($pa_config, $dbh, shift, $macros, $agent, $module,$alert);
			$macro =~ s/'/'\\''/g; # close, escape, open
			return decode_entities($quote . "'" . $macro . "'" . $quote); # close, quote, open
		};
	}
	else {
		$subst_func = sub {
			my $macro = on_demand_macro($pa_config, $dbh, shift, $macros, $agent, $module, $alert);
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
# Substitute macros if the string begins with an underscore.
##########################################################################
sub subst_column_macros ($$;$$$$) {
	my ($string, $macros, $pa_config, $dbh, $agent, $module) = @_;

	# Avoid to manipulate null strings
	return $string unless defined($string);	

	# Do not attempt to substitute macros unless the string
	# begins with an underscore.
	return $string unless substr($string, 0, 1) eq '_';

	return subst_alert_macros($string, $macros, $pa_config, $dbh, $agent, $module);
}

##########################################################################
# Load macros that access the database on demand.
##########################################################################
sub on_demand_macro($$$$$$;$) {
	my ($pa_config, $dbh, $macro, $macros, $agent, $module,$alert) = @_;

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
		return (defined ($alert)) ? enterprise_hook('get_policy_name_policy_alerts_id', [$dbh, $alert->{'id_policy_alerts'}]) : '';
	} elsif ($macro eq '_email_tag_') {
		return (defined ($module)) ? pandora_get_module_email_tags ($pa_config, $dbh, $module->{'id_agente_modulo'}) : '';
	} elsif ($macro eq '_phone_tag_') {
		return (defined ($module)) ? pandora_get_module_phone_tags ($pa_config, $dbh, $module->{'id_agente_modulo'}) : '';
	} elsif ($macro eq '_name_tag_') {
		return (defined ($module)) ? pandora_get_module_tags ($pa_config, $dbh, $module->{'id_agente_modulo'}) : '';
	} elsif ($macro =~ /_agentcustomfield_(\d+)_/) {
		my $agent_id = undef;
		if (defined($module)) {
			$agent_id = $module->{'id_agente'};
		} elsif (defined($agent)) {
			$agent_id = $agent->{'id_agente'};
		} else {
			return '';
		}
		my $field_number = $1;
		my $field_value = get_db_value($dbh, 'SELECT description FROM tagent_custom_data WHERE id_field=? AND id_agent=?', $field_number, $agent_id);
		return (defined($field_value)) ? $field_value : '';	
	} elsif ($macro eq '_prevdata_') {
		return '' unless defined ($module);
		if ($module->{'id_tipo_modulo'} eq 3){
			my $field_value = get_db_value($dbh, 'SELECT datos FROM tagente_datos_string where id_agente_modulo = ? order by utimestamp desc limit 1 offset 1', $module->{'id_agente_modulo'});
		}
		else{
			my $field_value = get_db_value($dbh, 'SELECT datos FROM tagente_datos where id_agente_modulo = ? order by utimestamp desc limit 1 offset 1', $module->{'id_agente_modulo'});
		}
	}elsif ($macro eq '_all_address_') {
		return '' unless defined ($module);
		my @rows = get_db_rows ($dbh, 'SELECT ip FROM taddress_agent taag, taddress ta WHERE ta.id_a = taag.id_a AND id_agent = ?', $module->{'id_agente'});

		my $field_value = "<pre>";
		my $count=1;
		foreach my $element (@rows) {
			$field_value .= $count.": " . $element->{'ip'} . "\n";
			$count++;
		}
		$field_value .= "</pre>";
		return(defined($field_value)) ? $field_value : '';
	} elsif ($macro =~ /_addressn_(\d+)_/) {
		return '' unless defined ($module);
		my $field_number = $1 - 1;
		my @rows = get_db_rows ($dbh, 'SELECT ip FROM taddress_agent taag, taddress ta WHERE ta.id_a = taag.id_a AND id_agent = ? ORDER BY ip ASC', $module->{'id_agente'});
		
		my $field_value = $rows[$field_number]->{'ip'};
		return(defined($field_value)) ? $field_value : '';
	} elsif ($macro =~ /_moduledata_(\S+)_/) {
		my $field_number = $1;

		my $id_mod = get_db_value ($dbh, 'SELECT id_agente_modulo FROM tagente_modulo WHERE id_agente = ? AND nombre = ?', $module->{'id_agente'}, $field_number);
		my $module_data = get_db_single_row ($dbh, 'SELECT id_tipo_modulo, unit FROM tagente_modulo WHERE id_agente_modulo = ?', $id_mod);
		my $type_mod = $module_data->{'id_tipo_modulo'};
		my $unit_mod = $module_data->{'unit'};

		my $field_value = "";
		if (defined($type_mod)
			&& ($type_mod eq 3 || $type_mod eq 10 || $type_mod eq 17 || $type_mod eq 23 || $type_mod eq 33 || $type_mod eq 36)
		) {
			$field_value = get_db_value($dbh, 'SELECT datos FROM tagente_estado WHERE id_agente_modulo = ?', $id_mod);
		}
		else{
			$field_value = get_db_value($dbh, 'SELECT datos FROM tagente_estado WHERE id_agente_modulo = ?', $id_mod);

			my $data_precision = $pa_config->{'graph_precision'};
			$field_value = sprintf("%.$data_precision" . "f", $field_value);
			$field_value =~ s/0+$//;
			$field_value =~ s/\.+$//;
		}

		if ($field_value eq ''){
			$field_value = 'Module ' . $field_number . " not found";
		}
		elsif (defined($unit_mod) && $unit_mod ne '') {
			$field_value .= $unit_mod;
		}
		
		return(defined($field_value)) ? $field_value : '';
	} elsif ($macro eq '_secondarygroups_') {
		my $field_value = '';

		my @groups = get_db_rows ($dbh, 'SELECT tg.nombre from tagent_secondary_group as tsg INNER JOIN tgrupo tg ON tsg.id_group = tg.id_grupo WHERE tsg.id_agent = ?', $module->{'id_agente'});
		foreach my $element (@groups) {
			$field_value .= $element->{'nombre'} .",";
		}
		chop($field_value);
		return(defined($field_value)) ? '('.$field_value.')' : '';
	}
}

##########################################################################
# Utility functions, not to be exported.
##########################################################################

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
		my $d = $data_object->{'data'};
		$d = '' unless defined ($data_object->{'data'});
		logger($pa_config, "Received invalid data '" . $d . "' from agent '" . $agent->{'nombre'} . "' module '" . $module->{'nombre'} . "' agent " . (defined ($agent) ? "'" . $agent->{'nombre'} . "'" : 'ID ' . $module->{'id_agente'}) . ".", 3);
		return undef;
	}

	# If is a number, we need to replace "," for "."
	$data =~ s/\,/\./;

	# Process INC modules
	if ($module_type =~ m/_inc$/) {
		$data = process_inc_data ($pa_config, $data, $module, $agent, $utimestamp, $dbh);
		
		# No previous data or error.
		return undef unless defined ($data);
	}
	# Process absolute INC modules
	elsif ($module_type =~ m/_inc_abs$/) {
		$data = process_inc_abs_data ($pa_config, $data, $module, $agent, $utimestamp, $dbh);
		
		# No previous data or error.
		return undef unless defined ($data);
	}
	# Process the rest of modules
	else {
		$data = post_process($data, $module);
		return undef unless check_min_max($pa_config, $data, $module, $agent);
	}

	# TODO: Float precission should be adjusted here in the future with a global
	# config parameter
	# Format data
	$data = sprintf("%.5f", $data);

	$data_object->{'data'} = $data;
	return $data;
}

##########################################################################
# Apply post processing to the given data.
##########################################################################
sub post_process ($$) {
	my ($data, $module) = @_;

	return (is_numeric ($module->{'post_process'}) && $module->{'post_process'} != 0) ? $data * $module->{'post_process'} : $data;
}

##########################################################################
# Return 1 if the data is whithin the module's boundaries, 0 if not.
##########################################################################
sub check_min_max ($$$$) {
	my ($pa_config, $data, $module, $agent) = @_;

	# Out of bounds
	if (($module->{'max'} != $module->{'min'}) && ($data > $module->{'max'} || $data < $module->{'min'})) {
		if($module->{'max'} < $module->{'min'}) {
			# Compare if there is only setted min or max.
			return 1 unless (($module->{'max'} == 0 && $data < $module->{'min'}) || ($module->{'min'} == 0 && $data > $module->{'max'}));
			
		}  

		logger($pa_config, "Received invalid data '" . $data . "' from agent '" . $agent->{'nombre'} . "' module '" . $module->{'nombre'} . "' agent " . (defined ($agent) ? "'" . $agent->{'nombre'} . "'" : 'ID ' . $module->{'id_agente'}) . ".", 3);
		return 0;
	}

	return 1;
}

##########################################################################
# Process data of type *_inc.
##########################################################################
sub process_inc_data ($$$$$$) {
	my ($pa_config, $data, $module, $agent, $utimestamp, $dbh) = @_;

	my $data_inc = get_db_single_row ($dbh, 'SELECT * FROM tagente_datos_inc WHERE id_agente_modulo = ?', $module->{'id_agente_modulo'});

	# No previous data
	if (! defined ($data_inc)) {
		db_do ($dbh, 'INSERT INTO tagente_datos_inc
				(id_agente_modulo, datos, utimestamp)
				VALUES (?, ?, ?)', $module->{'id_agente_modulo'}, $data, $utimestamp);
		logger($pa_config, "Discarding first data for incremental module " . $module->{'nombre'} . "(module id " . $module->{'id_agente_modulo'} . ").", 10);
		return undef;
	}

	# Out of order data
	if ($utimestamp < $data_inc->{'utimestamp'}) {
		logger($pa_config, "Received old data for incremental module " . $module->{'nombre'} . "(module id " . $module->{'id_agente_modulo'} . ").", 3);
		return undef;
	}

	# Should not happen
	if ($utimestamp == $data_inc->{'utimestamp'}) {
		logger($pa_config, "Duplicate timestamp for incremental module " . $module->{'nombre'} . "(module id " . $module->{'id_agente_modulo'} . ").", 3);
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

	# Compute the rate, apply post processing and check module boundaries.
	my $rate = ($data - $data_inc->{'datos'}) / ($utimestamp - $data_inc->{'utimestamp'});
	$rate = post_process($rate, $module);
	if (!check_min_max($pa_config, $rate, $module, $agent)) {
		db_do ($dbh, 'UPDATE tagente_datos_inc SET datos = ?, utimestamp = ? WHERE id_agente_modulo = ?', $data, $utimestamp, $module->{'id_agente_modulo'});
		return undef;
	}

	# Update inc data
	db_do ($dbh, 'UPDATE tagente_datos_inc SET datos = ?, utimestamp = ? WHERE id_agente_modulo = ?', $data, $utimestamp, $module->{'id_agente_modulo'});

	return $rate;
}

##########################################################################
# Process data of type *_inc_abs.
##########################################################################
sub process_inc_abs_data ($$$$$$) {
	my ($pa_config, $data, $module, $agent, $utimestamp, $dbh) = @_;

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

	# Compute the diff, apply post processing and check module boundaries.
	my $diff = ($data - $data_inc->{'datos'});
	$diff = post_process($diff, $module);
	if (!check_min_max($pa_config, $diff, $module, $agent)) {
		db_do ($dbh, 'UPDATE tagente_datos_inc SET datos = ?, utimestamp = ? WHERE id_agente_modulo = ?', $data, $utimestamp, $module->{'id_agente_modulo'});
		return undef;
	}

	# Update inc data
	db_do ($dbh, 'UPDATE tagente_datos_inc SET datos = ?, utimestamp = ? WHERE id_agente_modulo = ?', $data, $utimestamp, $module->{'id_agente_modulo'});

	return $diff;
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
sub get_module_status ($$$$) {
	my ($data, $module, $module_type, $last_data_value) = @_;
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
	
	# Adjust percentage max/min values.
	if ($module->{'percentage_critical'} == 1) {
		if ($critical_max != 0 && $critical_min != 0) {
			$critical_max = $last_data_value * (1 +  $critical_max / 100.0);
			$critical_min = $last_data_value * (1 -  $critical_min / 100.0);
			$module->{'critical_inverse'} = 1;
		}
		elsif ($critical_min != 0) {
			$critical_max = $last_data_value * (1 -  $critical_min / 100.0);
			$critical_min = 0;
			$module->{'critical_inverse'} = 0;
		}
		elsif ($critical_max != 0) {
			$critical_min = $last_data_value * (1 +  $critical_max / 100.0);
			$critical_max = 0;
			$module->{'critical_inverse'} = 0;
		}
	}
	if ($module->{'percentage_warning'} == 1) {
		if ($warning_max != 0 && $warning_min != 0) {
			$warning_max = $last_data_value * (1 +  $warning_max / 100.0);
			$warning_min = $last_data_value * (1 -  $warning_min / 100.0);
			$module->{'warning_inverse'} = 1;
		}
		elsif ($warning_min != 0) {
			$warning_max = $last_data_value * (1 -  $warning_min / 100.0);
			$warning_min = 0;
			$module->{'warning_inverse'} = 0;
		}
		elsif ($warning_max != 0) {
			$warning_min = $last_data_value * (1 +  $warning_max / 100.0);
			$warning_max = 0;
			$module->{'warning_inverse'} = 0;
		}
	}

	if (($module_type =~ m/_proc$/ || $module_type =~ /web_analysis/) && ($critical_min eq $critical_max)) {
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
				if ($critical_min == 0) {
					return 1 if ($data > $critical_max);
				}elsif ($critical_max == 0) {
					return 1 if ($data <= $critical_min);
				} else {
					return 1 if ($data < $critical_min || $data >= $critical_max);
					return 1 if ($data <= $critical_max && $critical_max < $critical_min);
				}
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
				if ($warning_min == 0) {
					return 2 if ($data > $warning_max);
				}elsif ($warning_max == 0) {
					return 2 if ($data <= $warning_min);
				} else {
					return 2 if ($data < $warning_min || $data >= $warning_max);
					return 2 if ($data <= $warning_max && $warning_max < $warning_min);
				}
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
	my ($pa_config, $data, $agent, $module, $status, $last_status, $known_status, $dbh) = @_;
	my ($event_type, $severity);
	my $description = '';

	# No events when event storm protection is enabled
	if ($EventStormProtection == 1) {
		return;
	}

	# Warmup interval for status events.
	if ($pa_config->{'warmup_event_on'} == 1) {

		# No status events.
		return if (time() < $pa_config->{'__start_utimestamp__'} + $pa_config->{'warmup_event_interval'});

		$pa_config->{'warmup_event_on'} = 0;
		logger($pa_config, "Warmup mode for events ended.", 10);
		pandora_event ($pa_config, "Warmup mode for events ended.", 0, 0, 0, 0, 0, 'system', 0, $dbh);
	}

	# Disable events related to the unknown status.
	if ($pa_config->{'unknown_events'} == 0 && ($last_status == 3 || $status == 3)) {
		return;
	}

	# disable event just recovering from 'Unknown' without status change
	if($last_status == 3 && $status == $known_status && $module->{'disabled_types_event'} ) {
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
		if ($known_status == 4) {
			return;
		}
		
		($event_type, $severity) = ('going_down_normal', 2);
		$description = safe_output($pa_config->{"text_going_down_normal"});
	# Critical
	} elsif ($status == 1) {
		($event_type, $severity) = ('going_up_critical', 4);
		$description = safe_output($pa_config->{"text_going_up_critical"});
	# Warning
	} elsif ($status == 2) {
		
		# From critical
		if ($known_status == 1) {
			($event_type, $severity) = ('going_down_warning', 3);
			$description = safe_output($pa_config->{"text_going_down_warning"});
		}
		# From normal or warning (after becoming unknown)
		else {
			($event_type, $severity) = ('going_up_warning', 3);
			$description = safe_output($pa_config->{"text_going_up_warning"});
		}
	} else {
		# Unknown status
		logger($pa_config, "Unknown status $status for module '" . $module->{'nombre'} . "' agent '" . $agent->{'nombre'} . "'.", 10);
		return;
	}

	if (is_numeric($data)) {
		my $data_precision = $pa_config->{'graph_precision'};
		$data = sprintf("%.$data_precision" . "f", $data);
		$data =~ s/0+$//;
		$data =~ s/\.+$//;
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
			$severity, 0, $module->{'id_agente_modulo'}, $event_type, 0, $dbh, 'monitoring_server', '', '', '', '', $module->{'critical_instructions'}, $module->{'warning_instructions'}, $module->{'unknown_instructions'}, undef, $data, $status);
	} else { 
		# Self validate this event if has "normal" status
		pandora_event ($pa_config, $description, $agent->{'id_grupo'}, $module->{'id_agente'},
			$severity, 0, $module->{'id_agente_modulo'}, $event_type, 1, $dbh, 'monitoring_server', '', '', '', '', $module->{'critical_instructions'}, $module->{'warning_instructions'}, $module->{'unknown_instructions'}, undef, $data, $status);
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

	logger($pa_config, "Exporting data for module '" . $module->{'nombre'} . "' agent '" . $agent->{'alias'} . "'.", 10);
	db_do($dbh, 'INSERT INTO tserver_export_data 
		(id_export_server, agent_name , module_name, module_type, data, timestamp) VALUES
		(?, ?, ?, ?, ?, ?)', $module->{'id_export'}, $agent->{'alias'}, $module->{'nombre'}, $module_type, $data, $timestamp);
}

##########################################################################
# Returns 1 if alerts for the given agent should be inhibited, 0 otherwise.
##########################################################################
#sub pandora_inhibit_alerts ($$$$) {
sub pandora_inhibit_alerts {
	my ($pa_config, $agent, $dbh, $depth) = @_;

	return 0 if ($agent->{'cascade_protection'} ne '1' || $agent->{'id_parent'} eq '0' || $depth > 1024);

	# Are any of the parent's critical alerts fired?	
	my $count = 0;
	if ($agent->{'cascade_protection_module'} != 0) {
		$count = get_db_value ($dbh, 'SELECT COUNT(*) FROM tagente_modulo, talert_template_modules, talert_templates
				WHERE tagente_modulo.id_agente = ?
				AND tagente_modulo.id_agente_modulo = ?
				AND tagente_modulo.id_agente_modulo = talert_template_modules.id_agent_module
				AND tagente_modulo.disabled = 0
				AND talert_template_modules.id_alert_template = talert_templates.id
				AND talert_template_modules.times_fired > 0
				AND talert_templates.priority = 4', $agent->{'id_parent'}, $agent->{'cascade_protection_module'});
	}
	else {
		$count = get_db_value ($dbh, 'SELECT COUNT(*) FROM tagente_modulo, talert_template_modules, talert_templates
				WHERE tagente_modulo.id_agente = ?
				AND tagente_modulo.id_agente_modulo = talert_template_modules.id_agent_module
				AND tagente_modulo.disabled = 0
				AND talert_template_modules.id_alert_template = talert_templates.id
				AND talert_template_modules.times_fired > 0
				AND talert_templates.priority = 4', $agent->{'id_parent'});
	}

	return 1 if (defined($count) && $count > 0);
	
	# Check the parent's parent next
	$agent = get_db_single_row ($dbh, 'SELECT * FROM tagente WHERE id_agente = ?', $agent->{'id_parent'});
	return 0 unless defined ($agent);

	return pandora_inhibit_alerts ($pa_config, $agent, $dbh, $depth + 1);
}

##########################################################################
# Returns 1 if service cascade protection is enabled for the given
# agent/module, 0 otherwise.
##########################################################################
sub pandora_cps_enabled($$) {
	my ($agent, $module) = @_;

	return 1 if ($agent->{'cps'} > 0);

	return 1 if ($module->{'cps'} > 0);

	return 0;
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
	my @servers = get_db_rows ($dbh, 'SELECT * FROM tserver WHERE BINARY name = ?', $pa_config->{'servername'});

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
		} elsif ($server->{"server_type"} == DISCOVERYSERVER) {

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
=head2 C<< pandora_process_event_replication (I<$pa_config>) >>

Process groups statistics for statistics table

=cut
##########################################################################
sub pandora_process_event_replication ($) {
	my $pa_config = shift;
	my $dbh_metaconsole;
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
		db_disconnect($dbh);
		return;
	}
	
	if($replication_interval <= 0) {
		logger($pa_config, "The event replication interval must be greater than 0. Event replication aborted.", 1);
		db_disconnect($dbh);
		return;
	}
	
	logger($pa_config, "Started event replication thread.", 1);

	while($THRRUN == 1) { 
		eval {{
			local $SIG{__DIE__};
			
			# Get the metaconsole DB connection
			$dbh_metaconsole = enterprise_hook('get_metaconsole_dbh', [$pa_config, $dbh]);
			$dbh_metaconsole = undef if $dbh_metaconsole eq '';
			if (!defined($dbh_metaconsole)) {
				logger($pa_config, "Metaconsole DB connection error. Event replication postponed.", 5);
				next;
			}
			
			# Get server id on metaconsole
			my $metaconsole_server_id = enterprise_hook('get_metaconsole_setup_server_id', [$dbh]);
		
			# If the server name is not found in metaconsole setup: abort
			if($metaconsole_server_id == -1) {
				logger($pa_config, "The server name is not configured in metaconsole. Event replication postponed.", 5);
				db_disconnect($dbh_metaconsole);
				next;
			}
			
			my $replication_mode = enterprise_hook('get_event_replication_mode', [$dbh]);
						
			while($THRRUN == 1) { 
		
				# If we are not the master server sleep and check again.
				if (pandora_is_master($pa_config) == 0) {
					sleep ($pa_config->{'server_threshold'});
					next;
				}
		
				# Check the queue each N seconds
				enterprise_hook('pandora_replicate_copy_events',[$pa_config, $dbh, $dbh_metaconsole, $metaconsole_server_id, $replication_mode]);
				sleep ($replication_interval);
			}
		}};
		db_disconnect($dbh_metaconsole) if defined($dbh_metaconsole);
		sleep ($replication_interval);
	}

	db_disconnect($dbh);
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

	my $dbh_metaconsole;
	
	logger($pa_config, "Starting policy queue patrol process.", 1);

	while($THRRUN == 1) {
		eval {{
			local $SIG{__DIE__};

			# If we are not the master server sleep and check again.
			if (pandora_is_master($pa_config) == 0) {
				sleep ($pa_config->{'server_threshold'});
				next;
			}

			# Refresh policy agents.
			enterprise_hook('pandora_apply_policy_groups', [$pa_config, $dbh]);

			my $operation = enterprise_hook('get_first_policy_queue', [$dbh]);
			next unless (defined ($operation) && $operation ne '');

			$pa_config->{"node_metaconsole"} = pandora_get_tconfig_token(
				$dbh, 'node_metaconsole', 0
			);

			# Only for nodes connected to a MC in centralised environment
			# tsync_queue will have elements ONLY if env is centralised on MC.
			if (!is_metaconsole($pa_config)
				&& $pa_config->{"node_metaconsole"}
			) {

				if (!defined($dbh_metaconsole)) {
					$dbh_metaconsole = enterprise_hook(
						'get_metaconsole_dbh',
						[$pa_config, $dbh]
					);
				}

				$pa_config->{"metaconsole_node_id"} = pandora_get_tconfig_token(
					$dbh, 'metaconsole_node_id', 0
				);

				if (!defined($dbh_metaconsole)) {
					logger($pa_config,
						"Node has no access to metaconsole, this is required in centralised environments.",
						3
					);

					sleep($pa_config->{'server_threshold'});

					# Skip.
					next;
				}

				my $policies_updated = PandoraFMS::DB::get_db_value(
					$dbh_metaconsole,
					'SELECT count(*) as N FROM `tsync_queue` WHERE `table` IN ( "tpolicies", "tpolicy_alerts", "tpolicy_alerts_actions", "tpolicy_collections", "tpolicy_modules", "tpolicy_modules_inventory", "tpolicy_plugins" ) AND `target` = ?',
						$pa_config->{"metaconsole_node_id"}
				);

				if (!defined($policies_updated) || "$policies_updated" ne "0") {
					$policies_updated = 'unknown' unless defined($policies_updated);
					logger($pa_config,
						"Policy definitions are not up to date (missing changes - $policies_updated - from MC) waiting synchronizer.",
						3
					);

					sleep($pa_config->{'server_threshold'});
					# Skip.
					next;
				}
			}

			if($operation->{'operation'} eq 'apply' || $operation->{'operation'} eq 'apply_db') {
				my $policy_applied = enterprise_hook(
					'pandora_apply_policy',
					[
						$dbh,
						$pa_config,
						$operation->{'id_policy'},
						$operation->{'id_agent'},
						$operation->{'id'},
						$operation->{'operation'}
					]
				);

				if($policy_applied == 0) {
					sleep($pa_config->{'server_threshold'});
					# Skip.
					next;
				}
				
			}
			elsif($operation->{'operation'} eq 'apply_group') {
				my $array_pointer_gr = enterprise_hook(
					'get_policy_groups',
					[
						$dbh,
						$operation->{'id_policy'}
					]
				);

				my $policy_name = enterprise_hook(
					'get_policy_name',
					[
						$dbh,
						$operation->{'id_policy'}
					]
				);

				foreach my $group (@{$array_pointer_gr}) {
					my $group_name = get_group_name($dbh, $group->{'id_group'});
					if ($group->{'pending_delete'} == 1) {
						logger($pa_config,
							"[INFO] Deleting pending group " . $group_name . " from policy ".$policy_name, 10);

						enterprise_hook(
							'pandora_delete_group_from_policy',
							[
								$dbh,
								$pa_config,
								$group->{'id_policy'},
								$group->{'id_group'}
							]
						);
						next;
					}
				}

				enterprise_hook(
					'pandora_apply_group_policy',
					[
						$operation->{'id_policy'},
						$operation->{'id_agent'},
						$dbh
					]
				);
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
		}};

		# Check the queue each server_threshold seconds
		sleep($pa_config->{'server_threshold'});
		
	}

	db_disconnect($dbh);
}

##########################################################################
=head2 C<< pandora_group_statistics (I<$pa_config>, I<$dbh>) >>

Process groups statistics for statistics table

=cut
##########################################################################
sub pandora_group_statistics ($$) {
	my ($pa_config, $dbh) = @_;
	my $is_meta = is_metaconsole($pa_config);

	logger($pa_config, "Updating no realtime group stats.", 10);

	my $total_alerts_condition = $is_meta
		? "0"
		: "COUNT(tatm.id)";
	my $joins_alerts = $is_meta
		? ""
		: "LEFT JOIN tagente_modulo tam
					ON tam.id_agente = ta.id_agente
				INNER JOIN talert_template_modules tatm
					ON tatm.id_agent_module = tam.id_agente_modulo";
	my $agent_table = $is_meta
		? "tmetaconsole_agent"
		: "tagente";
	my $agent_seconsary_table = $is_meta
		? "tmetaconsole_agent_secondary_group"
		: "tagent_secondary_group";

	# Update the record.
	db_do ($dbh, "REPLACE INTO tgroup_stat(
			`id_group`, `modules`, `normal`, `critical`, `warning`, `unknown`,
			`non-init`, `alerts`, `alerts_fired`, `agents`,
			`agents_unknown`, `utimestamp`
		)
		SELECT
			tg.id_grupo AS id_group,
			IF (SUM(modules_total) IS NULL,0,SUM(modules_total)) AS modules,
			IF (SUM(modules_ok) IS NULL,0,SUM(modules_ok)) AS normal,
			IF (SUM(modules_critical) IS NULL,0,SUM(modules_critical)) AS critical,
			IF (SUM(modules_warning) IS NULL,0,SUM(modules_warning)) AS warning,
			IF (SUM(modules_unknown) IS NULL,0,SUM(modules_unknown)) AS unknown,
			IF (SUM(modules_not_init) IS NULL,0,SUM(modules_not_init)) AS `non-init`,
			IF (SUM(alerts_total) IS NULL,0,SUM(alerts_total)) AS alerts,
			IF (SUM(alerts_fired) IS NULL,0,SUM(alerts_fired)) AS alerts_fired,
			IF (SUM(agents_total) IS NULL,0,SUM(agents_total)) AS agents,
			IF (SUM(agents_unknown) IS NULL,0,SUM(agents_unknown)) AS agents_unknown,
			UNIX_TIMESTAMP() AS utimestamp
		FROM
			(
				SELECT SUM(ta.normal_count) AS modules_ok,
					SUM(ta.critical_count) AS modules_critical,
					SUM(ta.warning_count) AS modules_warning,
					SUM(ta.unknown_count) AS modules_unknown,
					SUM(ta.notinit_count) AS modules_not_init,
					SUM(ta.total_count) AS modules_total,
					SUM(ta.fired_count) AS alerts_fired,
					$total_alerts_condition AS alerts_total,
					SUM(IF(ta.critical_count > 0, 1, 0)) AS agents_critical,
					SUM(IF(ta.critical_count = 0 AND ta.warning_count = 0 AND ta.unknown_count > 0, 1, 0)) AS agents_unknown,
					SUM(IF(ta.total_count = ta.notinit_count, 1, 0)) AS agents_not_init,
					COUNT(ta.id_agente) AS agents_total,
					ta.id_grupo AS g
				FROM $agent_table ta
				$joins_alerts
				WHERE ta.disabled = 0
				GROUP BY g

				UNION ALL

				SELECT SUM(ta.normal_count) AS modules_ok,
					SUM(ta.critical_count) AS modules_critical,
					SUM(ta.warning_count) AS modules_warning,
					SUM(ta.unknown_count) AS modules_unknown,
					SUM(ta.notinit_count) AS modules_not_init,
					SUM(ta.total_count) AS modules_total,
					SUM(ta.fired_count) AS alerts_fired,
					$total_alerts_condition AS alerts_total,
					SUM(IF(ta.critical_count > 0, 1, 0)) AS agents_critical,
					SUM(IF(ta.critical_count = 0 AND ta.warning_count = 0 AND ta.unknown_count > 0, 1, 0)) AS agents_unknown,
					SUM(IF(ta.total_count = ta.notinit_count, 1, 0)) AS agents_not_init,
					COUNT(ta.id_agente) AS agents_total,
					tasg.id_group AS g
				FROM $agent_table ta
				LEFT JOIN $agent_seconsary_table tasg
					ON ta.id_agente = tasg.id_agent
				$joins_alerts
				WHERE ta.disabled = 0
				GROUP BY g
			) counters
		RIGHT JOIN tgrupo tg
			ON counters.g = tg.id_grupo
		GROUP BY tg.id_grupo"
	);

	logger($pa_config, "No realtime group stats updated.", 6);
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
	
	$xml_output = "<agent_data os_name='$OS' os_version='$OS_VERSION' version='" . $pa_config->{'version'} . "' description='" . $pa_config->{'rb_product_name'} . " Server version " . $pa_config->{'version'} . "' agent_name='".$pa_config->{'servername'} . "' agent_alias='".$pa_config->{'servername'} . "' interval='".$pa_config->{"self_monitoring_interval"}."' timestamp='".$timestamp."' >";
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
	
	my $queued_modules = get_db_value ($dbh, "SELECT SUM(queued_modules) FROM tserver WHERE BINARY name = '".$pa_config->{"servername"}."'");
	
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
	elsif ($RDBMS eq 'oracle') {
		$dbmaintance = get_db_value ($dbh,
			"SELECT COUNT(*)
			FROM tconfig
			WHERE token = 'db_maintance' AND DBMS_LOB.substr(value, 100, 1) > UNIX_TIMESTAMP() - 86400");
	}
	else {
		$dbmaintance = get_db_value ($dbh,
			"SELECT COUNT(*)
			FROM tconfig
			WHERE token = 'db_maintance' AND value > UNIX_TIMESTAMP() - 86400");
	}

	my $start_performance = time;
	get_db_value($dbh, "SELECT COUNT(*) FROM tagente_datos");
	my $read_speed = int((time - $start_performance) * 1e6);

	my $elasticsearch_perfomance = enterprise_hook("elasticsearch_performance", [$pa_config, $dbh]);

	$xml_output .= $elasticsearch_perfomance if defined($elasticsearch_perfomance);
	
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
	
	if (defined($load_average)) {
		$xml_output .=" <module>";
		$xml_output .=" <name>System_Load_AVG</name>";
		$xml_output .=" <type>generic_data</type>";
		$xml_output .=" <data>$load_average</data>";
		$xml_output .=" </module>";
	}
	
	if (defined($free_mem)) {
		$xml_output .=" <module>";
		$xml_output .=" <name>Free_RAM</name>";
		$xml_output .=" <type>generic_data</type>";
		$xml_output .=" <data>$free_mem</data>";
		$xml_output .=" </module>";
	}
	
	if (defined($free_disk_spool)) {
		$xml_output .=" <module>";
		$xml_output .=" <name>FreeDisk_SpoolDir</name>";
		$xml_output .=" <type>generic_data</type>";
		$xml_output .=" <data>$free_disk_spool</data>";
		$xml_output .=" </module>";
	}

	$xml_output .=" <module>";
	$xml_output .=" <name>Execution_Time</name>";
	$xml_output .=" <type>generic_data</type>";
	$xml_output .=" <unit>us</unit>";
	$xml_output .=" <data>$read_speed</data>";
	$xml_output .=" </module>";

	$xml_output .= "</agent_data>";

	my $filename = $pa_config->{"incomingdir"}."/".$pa_config->{'servername'}.".self.".$utimestamp.".data";
	
	open (XMLFILE, ">", $filename) or die "[FATAL] Could not open internal monitoring XML file for deploying monitorization at '$filename'";
	print XMLFILE $xml_output;
	close (XMLFILE);
}
##########################################################################
=head2 C<< xml_module_template (I<$module_name>, I<$module_type>, I<$module_data>) >>

Module template for sample agent

=cut
##########################################################################
sub xml_module_template ($$$) {
	my ($module_name, $module_type, $module_data) = @_;
	my $output = "<module>\n";
	
	$module_name = "<![CDATA[".$module_name."]]>" if $module_name =~ /[\s+.]+/;
	$module_data = "<![CDATA[".$module_data."]]>" if $module_data =~ /[\s+.]+/;

	$output .= "\t<name>".$module_name."</name>\n";
	$output .= "\t<type>".$module_type."</type>\n";
	$output .= "\t<data>".$module_data."</data>\n";
	$output .= "</module>\n";

	return $output;
}
##########################################################################
=head2 C<< pandora_sample_agent (I<$pa_config>) >>

Pandora agent for make sample data

=cut
##########################################################################
sub pandora_sample_agent ($) {
	
	my ($pa_config) = @_;

	my $utimestamp = time ();
	my $timestamp = strftime ("%Y-%m-%d %H:%M:%S", localtime());
	# First line	
	my $xml_output = "<?xml version='1.0' encoding='UTF-8'?>\n";
	# Header
	$xml_output = "<agent_data agent_name='Sample_Agent' agent_alias='Sample_Agent' description='Agent for sample generation purposes' group='Servers' os_name='$OS' os_version='$OS_VERSION' interval='".$pa_config->{'sample_agent_interval'}."' version='" . $pa_config->{'version'} . "' timestamp='".$timestamp."'>\n";
	# Boolean ever return TRUE
	$xml_output .= xml_module_template ("Boolean ever true", "generic_proc","1");
	# Boolean return TRUE at 80% of times
	my $sample_boolean_mostly_true = 1;
	$sample_boolean_mostly_true = 0 if rand(9) > 7;
	$xml_output .= xml_module_template ("Boolean mostly true", "generic_proc",$sample_boolean_mostly_true);
	# Boolean return false at 80% of times
	my $sample_boolean_mostly_false = 0;
	$sample_boolean_mostly_false = 1 if rand(9) > 7;
	$xml_output .= xml_module_template ("Boolean mostly false", "generic_proc", $sample_boolean_mostly_false);
	# Boolean ever return FALSE
	$xml_output .= xml_module_template ("Boolean ever false", "generic_proc","0");
	# Random integer between 0 and 100
	$xml_output .= xml_module_template ("Random integer values", "generic_data",int(rand(100)));
	# Random values obtained with sinusoidal curves between 0 and 100 values
	my $b = 1;
	my $sample_serie_curve = 1 + cos(deg2rad($b));
	$b = $b + rand(20)/10;
	$b = 0 if ($b > 180);
	$sample_serie_curve = $sample_serie_curve * $b * 10;
	$sample_serie_curve =~ s/\,/\./g;
	$xml_output .= xml_module_template ("Random serie curve", "generic_data", $sample_serie_curve);
	# String with 10 random characters
	my $sample_random_text = "";
	my @characters = ('a'..'z','A'..'Z');
	for (1...10){
		$sample_random_text .= $characters[int(rand(@characters))];
	}
	$xml_output .= xml_module_template ("Random text", "generic_data_string", $sample_random_text);
	# End of xml
	$xml_output .= "</agent_data>";
	# File path definition
	my $filename = $pa_config->{"incomingdir"}."/".$pa_config->{'servername'}.".sample.".$utimestamp.".data";
	# Opening, Writing and closing of XML
	open (my $xmlfile, ">", $filename) or die "[FATAL] Could not open sample XML file for deploying monitorization at '$filename'";
	print $xmlfile $xml_output;
	close ($xmlfile);

}

##########################################################################
=head2 C<< pandora_set_master (I<$pa_config>, I<$dbh>) >> 

Set the current master server.

=cut
##########################################################################
sub pandora_set_master ($$) {
	my ($pa_config, $dbh) = @_;
	
	my $current_master = get_db_value_limit ($dbh, 'SELECT name FROM tserver 
	                                  WHERE master <> 0 AND status = 1
									  ORDER BY master DESC', 1);
	return unless defined($current_master) and ($current_master ne $Master);

	logger($pa_config, "Server $current_master is the current master.", 1);
	$Master = $current_master;
}

##########################################################################
=head2 C<< pandora_is_master (I<$pa_config>) >> 

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
	
	my $timestamp = strftime ("%Y-%m-%d %H:%M:%S", localtime(time()));

	# Warmup interval for unknown modules.
	if ($pa_config->{'warmup_unknown_on'} == 1) {

		# No status events.
		return if (time() < $pa_config->{'__start_utimestamp__'} + $pa_config->{'warmup_unknown_interval'});

		$pa_config->{'warmup_unknown_on'} = 0;
		logger($pa_config, "Warmup mode for unknown modules ended.", 10);
		pandora_event ($pa_config, "Warmup mode for unknown modules ended.", 0, 0, 0, 0, 0, 'system', 0, $dbh);
	}

	my @modules = get_db_rows ($dbh, 'SELECT tagente_modulo.*,
			tagente_estado.id_agente_estado, tagente_estado.estado, tagente_estado.last_status_change
		FROM tagente_modulo, tagente_estado, tagente 
		WHERE tagente.id_agente = tagente_estado.id_agente 
			AND tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo 
			AND tagente.disabled = 0 
			AND tagente_modulo.disabled = 0 
			AND ((tagente_modulo.id_tipo_modulo IN (21, 22, 23) AND tagente_estado.estado <> 0)
				OR (' .
				($pa_config->{'unknown_updates'} == 0 ? 
					'tagente_estado.estado <> 3 AND tagente_modulo.id_tipo_modulo NOT IN (21, 22, 23, 100)' :
					'tagente_modulo.id_tipo_modulo NOT IN (21, 22, 23, 100) AND tagente_estado.last_unknown_update + tagente_estado.current_interval < UNIX_TIMESTAMP()') .
				')
			)
			AND tagente_estado.utimestamp != 0
			AND (tagente_estado.current_interval * ?) + tagente_estado.utimestamp < UNIX_TIMESTAMP()', $pa_config->{'unknown_interval'});
	
	foreach my $module (@modules) {
		
		# Async
		if ($module->{'id_tipo_modulo'} == 21 ||
			$module->{'id_tipo_modulo'} == 22 ||
			$module->{'id_tipo_modulo'} == 23) {

			next if ($pa_config->{"async_recovery"} == 0);
			
			# Set the module state to normal
			logger ($pa_config, "Module " . $module->{'nombre'} . " is going to NORMAL", 10);
			db_do ($dbh, 'UPDATE tagente_estado SET last_status = 0, estado = 0, known_status = 0, last_known_status = 0, last_status_change = ? WHERE id_agente_estado = ?', time(), $module->{'id_agente_estado'});
			
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
			if (pandora_inhibit_alerts ($pa_config, $agent, $dbh, 0) == 0 && pandora_cps_enabled($agent, $module) == 0) {
				my $extra_macros = { _modulelaststatuschange_ => $module->{'last_status_change'}};
				pandora_generate_alerts ($pa_config, 0, 3, $agent, $module, time (), $dbh, $timestamp, $extra_macros, 0, 'unknown');
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
				_modulelaststatuschange_ => $module->{'last_status_change'},
				_data_ => 'N/A',
			);
		        load_module_macros ($module->{'module_macros'}, \%macros);
			$description = subst_alert_macros ($description, \%macros, $pa_config, $dbh, $agent, $module);

			# Are unknown events enabled?
			if ($pa_config->{'unknown_events'} == 1) {
				pandora_event ($pa_config, $description, $agent->{'id_grupo'}, $module->{'id_agente'},
					$severity, 0, $module->{'id_agente_modulo'}, $event_type, 0, $dbh, 'monitoring_server', '', '', '', '', $module->{'critical_instructions'}, $module->{'warning_instructions'}, $module->{'unknown_instructions'});
			}
		}
		# Regular module
		else {
			# Set the module status to unknown (the module can already be unknown if unknown_updates is enabled).
			if ($module->{'estado'} != 3) {
				logger ($pa_config, "Module " . $module->{'nombre'} . " is going to UNKNOWN", 10);
				my $utimestamp = time();
				db_do ($dbh, 'UPDATE tagente_estado SET last_status = 3, estado = 3, last_unknown_update = ?, last_status_change = ? WHERE id_agente_estado = ?', $utimestamp, $utimestamp, , $module->{'id_agente_estado'});
			}
			
			# Get agent information
			my $agent = get_db_single_row ($dbh, 'SELECT * FROM tagente WHERE id_agente = ?', $module->{'id_agente'});
			if (! defined ($agent)) {
				logger($pa_config, "Agent ID " . $module->{'id_agente'} . " not found while executing unknown alerts for module '" . $module->{'nombre'} . "'.", 3);
				return;
			}
			
			# Update module status count
			pandora_mark_agent_for_module_update ($dbh, $module->{'id_agente'});
			
			# Generate alerts
			if (pandora_inhibit_alerts ($pa_config, $agent, $dbh, 0) == 0 && pandora_cps_enabled($agent, $module) == 0) {
				my $extra_macros = { _modulelaststatuschange_ => $module->{'last_status_change'}};
				pandora_generate_alerts ($pa_config, 0, 3, $agent, $module, time (), $dbh, $timestamp, $extra_macros, 0, 'unknown');
			}
			else {
				logger($pa_config, "Alerts inhibited for agent '" . $agent->{'nombre'} . "'.", 10);
			}
			
			my $do_event;
			# Are unknown events enabled?
			if ($pa_config->{'unknown_events'} == 0 ||
				$module->{'estado'} == 3) { # Already in unknown status (unknown_updates is enabled).
				$do_event = 0;
			}
			elsif (!defined($module->{'disabled_types_event'}) || $module->{'disabled_types_event'} eq "") {
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
						_modulelaststatuschange_ => $module->{'last_status_change'},
		        );
		        load_module_macros ($module->{'module_macros'}, \%macros);
		        $description = subst_alert_macros ($description, \%macros, $pa_config, $dbh, $agent, $module);
		        
				pandora_event ($pa_config, $description, $agent->{'id_grupo'}, $module->{'id_agente'},
					$severity, 0, $module->{'id_agente_modulo'}, $event_type, 0, $dbh, 'monitoring_server', '', '', '', '', $module->{'critical_instructions'}, $module->{'warning_instructions'}, $module->{'unknown_instructions'});
			}
		}
	}
}

##########################################################################
=head2 C<< pandora_disable_autodisable_agents (I<$pa_config>, I<$dbh>) >> 

Puts all autodisable agents with all modules unknown on disabled mode

=cut
##########################################################################
sub pandora_disable_autodisable_agents ($$) {
	my ($pa_config, $dbh) = @_;
	
	my $sql = 'SELECT id_agente FROM tagente
			WHERE disabled=0 AND 
			tagente.unknown_count>0 AND 
			tagente.modo=2';
	my @agents_autodisabled = get_db_rows ($dbh, $sql);
	return if ($#agents_autodisabled < 0);
	
	my $disable_agents = '';
	foreach my $agent (@agents_autodisabled) {
		if (get_agent_status ($pa_config, $dbh, $agent->{'id_agente'}) == 3) {
			$disable_agents .= $agent->{'id_agente'} . ',';
		}
	}
	return if ($disable_agents eq '');
	
	# Remove the last quote
	$disable_agents =~ s/,$//ig;	
	logger($pa_config, "Autodisable agents ($disable_agents) will be disabled", 9);
	
	db_do ($dbh, 'UPDATE tagente SET disabled=1 
			WHERE id_agente IN ('.$disable_agents.')');
}

##########################################################################
=head2 C<< pandora_get_module_tags (I<$pa_config>, I<$dbh>, I<$id_agentmodule>) >> 

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
=head2 C<< pandora_get_module_url_tags (I<$pa_config>, I<$dbh>, I<$id_agentmodule>) >> 

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
=head2 C<< pandora_get_module_email_tags (I<$pa_config>, I<$dbh>, I<$id_agentmodule>) >> 

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
=head2 C<< pandora_get_module_phone_tags (I<$pa_config>, I<$dbh>, I<$id_agentmodule>) >> 

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
=head2 C<< pandora_mark_agent_for_module_update (I<$dbh>, I<$agent_id>) >>

Mark an agent for module status count update.

=cut
##########################################################################
sub pandora_mark_agent_for_module_update ($$) {
	my ($dbh, $agent_id) = @_;

	# Update the status count
	db_do ($dbh, "UPDATE tagente SET update_module_count=1 WHERE id_agente=?", $agent_id);
}

##########################################################################
=head2 C<< pandora_mark_agent_for_alert_update (I<$dbh>, I<$agent_id>) >>

Mark an agent for fired alert count update.

=cut
##########################################################################
sub pandora_mark_agent_for_alert_update ($$) {
	my ($dbh, $agent_id) = @_;

	# Update the status count
	db_do ($dbh, "UPDATE tagente SET update_alert_count=1 WHERE id_agente=?", $agent_id);
}

##########################################################################
=head2 C<< pandora_set_event_storm_protection (I<$EventStormProtection>) >>

Set or unset silent mode.

=cut
##########################################################################
sub pandora_set_event_storm_protection ($) {
	$EventStormProtection = shift;
}

##########################################################################
=head2 C<< pandora_update_agent_module_count (I<$pa_config>, I<$dbh>, I<$agent_id>) >>

Update the module status count of an agent.

=cut
##########################################################################
sub pandora_update_agent_module_count ($$$) {
	my ($pa_config, $dbh, $agent_id) = @_;
	my $total = 0;
	my $counts = {
		'0' => 0,
		'1' => 0,
		'2' => 0,
		'3' => 0,
		'4' => 0,
	}; # Module counts by status.

	# Retrieve and hash module status counts.
	my @rows = get_db_rows ($dbh,
		'SELECT `estado`, COUNT(*) AS total 
     FROM `tagente_modulo`, `tagente_estado` 
     WHERE `tagente_modulo`.`disabled`=0
       AND `tagente_modulo`.`id_modulo`<>0
       AND `tagente_modulo`.`id_agente_modulo`=`tagente_estado`.`id_agente_modulo`
       AND `tagente_modulo`.`id_agente`=? GROUP BY `estado`',
		$agent_id
	);
	foreach my $row (@rows) {
		$counts->{$row->{'estado'}} = $row->{'total'};
		$total += $row->{'total'};
	}

	# Update the agent.
	db_do ($dbh, 'UPDATE tagente
		SET update_module_count=0, normal_count=?, critical_count=?, warning_count=?, unknown_count=?, notinit_count=?, total_count=?
		WHERE id_agente = ?', $counts->{'0'}, $counts->{'1'}, $counts->{'2'}, $counts->{'3'}, $counts->{'4'}, $total, $agent_id);

	# Sync the agent cache every time the module count is updated.
	enterprise_hook('update_agent_cache', [$pa_config, $dbh, $agent_id]) if ($pa_config->{'node_metaconsole'} == 1);
}

##########################################################################
=head2 C<< pandora_update_agent_alert_count (I<$pa_config>, I<$dbh>, I<$agent_id>) >>

Update the fired alert count of an agent.

=cut
##########################################################################
sub pandora_update_agent_alert_count ($$$) {
	my ($pa_config, $dbh, $agent_id) = @_;
	
	db_do ($dbh, 'UPDATE tagente SET update_alert_count=0,
	fired_count=(SELECT COUNT(*) FROM tagente_modulo, talert_template_modules WHERE tagente_modulo.disabled=0 AND tagente_modulo.id_agente_modulo=talert_template_modules.id_agent_module AND talert_template_modules.disabled=0 AND times_fired>0 AND id_agente=' . $agent_id .
	') WHERE id_agente = ' . $agent_id);
	
	# Sync the agent cache every time the module count is updated.
	enterprise_hook('update_agent_cache', [$pa_config, $dbh, $agent_id]) if ($pa_config->{'node_metaconsole'} == 1);
}

##########################################################################
=head2 C<< pandora_update_secondary_groups_cache (I<$pa_config>, I<$dbh>, I<$agent_id>) >>

Update the secondary group cache.

=cut
##########################################################################
sub pandora_update_secondary_groups_cache ($$$) {
	my ($pa_config, $dbh, $agent_id) = @_;

	db_do ($dbh, 'UPDATE tagente SET update_secondary_groups=0 WHERE id_agente = ' . $agent_id);

	# Sync the agent cache every time the module count is updated.
	enterprise_hook('update_agent_cache', [$pa_config, $dbh, $agent_id]) if ($pa_config->{'node_metaconsole'} == 1);
}

########################################################################
=head2 C<< pandora_get_os (I<$dbh>, I<$os>) >>

SUB pandora_get_os (string)
Detect OS using a string, and return id_os

=cut
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
# SUB pandora_get_os_by_id (integer)
# Returns a chain with the name associated to target id_os.
########################################################################
sub pandora_get_os_by_id ($$) {
	my ($dbh, $os_id) = @_;
	
	if (! defined($os_id) || !is_numeric($os_id)) {
		# Other OS
		return 'Other';
	}
	
	if ($os_id eq 9) {
		return 'Windows';
	}
	if ($os_id eq 7 ) {
		return 'Cisco';
	}
	if ($os_id eq 2 ) {
		return 'Solaris';
	}
	if ($os_id eq 3 ) {
		return 'AIX';
	}
	if ($os_id eq 5) {
		return 'HP-UX';
	}
	if ($os_id eq 8 ) {
		return 'Apple';
	}
	if ($os_id eq 1 ) {
		return 'Linux';
	}
	if ($os_id eq  1) {
		return 'Enterasys';
	}
	if ($os_id eq  3) {
		return 'Octopods';
	}
	if ($os_id eq  4) {
		return 'embedded';
	}
	if ($os_id eq  5) {
		return 'android';
	}
	if ($os_id eq 4 ) {
		return 'BSD';
	}
		
	# Search for a custom OS
	my $os_name = get_db_value ($dbh, 'SELECT name FROM tconfig_os WHERE id_os = ?', $os_id);
	if (defined ($os_name)) {
		return $os_name;
	}

	# Other OS
	return 'Other';
}


########################################################################
# Load module macros (a base 64 encoded JSON document) into the macro
# hash.
########################################################################
sub load_module_macros ($$) {
	my ($macros, $macro_hash) = @_;
	
	return if (!defined($macros));

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

sub pandora_create_integria_ticket ($$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$) {
	my ($pa_config,$api_path,$api_pass,$integria_user,$user_pass,$agent_name,$agent_alias,$agent_os,$agent_addr,$agent_id,$agent_group,$ticket_name,$ticket_group_id,$ticket_priority,$ticket_owner,$ticket_type,$ticket_status,$ticket_description, $create_wu_on_close_recovery, $ticket_custom_field1, $ticket_custom_field2, $ticket_custom_field3, $ticket_custom_field4, $ticket_custom_field5, $ticket_custom_field6, $ticket_custom_field7, $ticket_custom_field8, $ticket_custom_field9, $ticket_custom_field10, $ticket_custom_field11, $ticket_custom_field12, $ticket_custom_field13) = @_;

	use URI::URL;
	use URI::Escape;
	use HTML::Entities;

	my $data_ticket;
	my $call_api;

	my $uri = URI->new($api_path);

	if ($uri->scheme eq "") {
		$api_path = "http://" . $api_path;
	}

	my $ticket_create_wu = 0;

	if ($create_wu_on_close_recovery == 1 && $ticket_status eq '7') {
		$ticket_create_wu = 1;
	}

	$data_ticket = $agent_name .
		"|;|" .	uri_escape(decode_entities($agent_alias)) .
		"|;|" .	$agent_os .
		"|;|" .	$agent_addr .
		"|;|" .	$agent_id .
		"|;|" .	$agent_group .
		"|;|" .	$ticket_name .
		"|;|" . $ticket_group_id .
		"|;|" . $ticket_priority .
		"|;|" . $ticket_description .
		"|;|" . $ticket_type .
		"|;|" . $ticket_owner .
		"|;|" . $ticket_status .
		"|;|" . $ticket_create_wu .
		"|;|" . $ticket_custom_field1 .
		"|;|" . $ticket_custom_field2 .
		"|;|" . $ticket_custom_field3 .
		"|;|" . $ticket_custom_field4 .
		"|;|" . $ticket_custom_field5 .
		"|;|" . $ticket_custom_field6 .
		"|;|" . $ticket_custom_field7 .
		"|;|" . $ticket_custom_field8 .
		"|;|" . $ticket_custom_field9 .
		"|;|" . $ticket_custom_field10 .
		"|;|" . $ticket_custom_field11 .
		"|;|" . $ticket_custom_field12 .
		"|;|" . $ticket_custom_field13;

	$call_api = $api_path . '?' .
		'user=' . $integria_user . '&' .
		'user_pass=' . $user_pass . '&' .
		'pass=' . $api_pass . '&' .
		'op=create_pandora_ticket&' .
		'params=' . $data_ticket .'&' .
		'token=|;|';

	my $content = get($call_api);

	if (is_numeric($content) && $content ne "-1") {
		return $content;
	}
	else {
		return 0;
	}
}

sub pandora_sync_agents_integria ($) {
	my ($dbh) = @_;

	my $config_integria_enabled = pandora_get_tconfig_token ($dbh, 'integria_enabled', '');

	if (!$config_integria_enabled) {
		return;
	}

	my $config_api_path = pandora_get_tconfig_token ($dbh, 'integria_hostname', '');
	my $config_api_pass = pandora_get_tconfig_token ($dbh, 'integria_api_pass', '');
	my $config_integria_user = pandora_get_tconfig_token ($dbh, 'integria_user', '');
	my $config_integria_user_pass = pandora_get_tconfig_token ($dbh, 'integria_pass', '');

	my $api_path = $config_api_path . "/include/api.php";

	my @agents_string = '';
	my @agents = get_db_rows ($dbh, 'SELECT * FROM tagente');
	
	my @agents_array = ();
	my $agents_string = '';

	foreach my $agent (@agents) {
		push @agents_array, $agent->{'nombre'} .
			"|;|" .
			$agent->{'alias'} .
			"|;|" .
			$agent->{'id_os'} .
			"|;|" .
			$agent->{'direccion'} .
			"|;|" .
			$agent->{'id_grupo'};
	}

	my $ua       = LWP::UserAgent->new();
	my $response = $ua->post( $api_path, {
			'user' 		=> $config_integria_user,
			'user_pass' => $config_integria_user_pass,
			'pass' 		=> $config_api_pass,
			'op' 		=> 'sync_pandora_agents_inventory',
			'params[]' 	=> [@agents_array],
			'token' 	=> '|;|'
		});

	my $content = $response->decoded_content();

	if (defined $content && is_numeric($content) && $content ne "-1") {
		return $content;
	}
	else {
		return 0;
	}
}

sub pandora_get_integria_ticket_types($) {
	my ($dbh) = @_;

	my $config_api_path = pandora_get_tconfig_token ($dbh, 'integria_hostname', '');
	my $config_api_pass = pandora_get_tconfig_token ($dbh, 'integria_api_pass', '');
	my $config_integria_user = pandora_get_tconfig_token ($dbh, 'integria_user', '');
	my $config_integria_user_pass = pandora_get_tconfig_token ($dbh, 'integria_pass', '');

	my $api_path = $config_api_path . "/include/api.php";

	my $call_api = $api_path . '?' .
		'user=' . $config_integria_user . '&' .
		'user_pass=' . $config_integria_user_pass . '&' .
		'pass=' . $config_api_pass . '&' .
		'op=get_types&' .
		'return_type=json';

	my $content = get($call_api);

    my @decoded_json;
    @decoded_json = @{decode_json($content)} if (defined $content && $content ne "");

	return @decoded_json;

}

##########################################################################
=head2 C<< pandora_input_password (I<$pa_config>, I<$password>) >> 

Process a password to be stored in the Pandora FMS Database (encrypting it if
necessary).

=cut
##########################################################################
sub pandora_input_password($$) {
	my ($pa_config, $password) = @_;

	# Do not attemp to encrypt empty passwords.
	return '' if ($password eq '');

	# Encryption disabled.
	return $password if (! defined($pa_config->{'encryption_key'}) || $pa_config->{'encryption_key'} eq '');

	# Encrypt the password.
	my $encrypted_password = enterprise_hook ('pandora_encrypt', [$pa_config, $password, $pa_config->{'encryption_key'}]);
	return $password unless defined($encrypted_password);

	return $encrypted_password;
}

##########################################################################
=head2 C<< pandora_output_password (I<$pa_config>, I<$password>) >> 

Process a password retrieved from the Pandora FMS Database (decrypting it if
necessary).

=cut
##########################################################################
sub pandora_output_password($$) {
	my ($pa_config, $password) = @_;

	# Do not attemp to decrypt empty passwords.
	return '' if (! defined($password) || $password eq '');

	# Encryption disabled.
	return $password if (! defined($pa_config->{'encryption_key'}) || $pa_config->{'encryption_key'} eq '');

	# Decrypt the password.
	my $decrypted_password = enterprise_hook ('pandora_decrypt', [$pa_config, $password, $pa_config->{'encryption_key'}]);
	return $password unless defined($decrypted_password);

	return $decrypted_password;
}

##########################################################################
=head2 C<< safe_mode (I<$pa_config>, I<$agent>, I<$module>, I<$new_status>, I<$known_status>, I<$dbh>) >> 

Execute safe mode for the given agent based on the status of the given module.

=cut
##########################################################################
sub safe_mode($$$$$$) {
	my ($pa_config, $agent, $module, $new_status, $known_status, $dbh) = @_;

	return unless $agent->{'safe_mode_module'} > 0;

	# Going to critical. Disable the rest of the modules.
	if ($new_status == MODULE_CRITICAL) {
		logger($pa_config, "Enabling safe mode for agent " . $agent->{'nombre'}, 10);
		db_do($dbh, 'UPDATE tagente_modulo SET disabled=1 WHERE id_agente=? AND id_agente_modulo!=?', $agent->{'id_agente'}, $module->{'id_agente_modulo'});
	}
	# Coming back from critical. Enable the rest of the modules.
	elsif ($known_status == MODULE_CRITICAL) {
		logger($pa_config, "Disabling safe mode for agent " . $agent->{'nombre'}, 10);
		db_do($dbh, 'UPDATE tagente_modulo SET disabled=0 WHERE id_agente=? AND id_agente_modulo!=?', $agent->{'id_agente'}, $module->{'id_agente_modulo'});
	}
}

##########################################################################
=head2 C<< pandora_safe_mode_modules_update (I<$pa_config>, I<$agent>, I<$dbh>) >> 

Check if agent safe module is critical and turn all modules to disabled.

=cut
##########################################################################
sub pandora_safe_mode_modules_update {
	my ($pa_config, $agent_id, $dbh) = @_;

	my $agent = get_db_single_row ($dbh, 'SELECT alias, safe_mode_module FROM tagente WHERE id_agente = ?', $agent_id);
	# Does nothing if safe_mode is disabled
	return unless $agent->{'safe_mode_module'} > 0;

	my $status = get_agentmodule_status($pa_config, $dbh, $agent->{'safe_mode_module'});

	# If status is critical, disable the rest of the modules.
	if ($status == MODULE_CRITICAL) {
		logger($pa_config, "Update modules for safe mode agent with alias:" . $agent->{'alias'} . ".", 10);
		db_do($dbh, 'UPDATE tagente_modulo SET disabled=1 WHERE id_agente=? AND id_agente_modulo!=?', $agent_id, $agent->{'safe_mode_module'});
	}
}

##########################################################################

=head2 C<< notification_set_targets (I<$dbh>, I<$pa_config>, I<$notification_id>, I<$users>, I<$groups>) >>

Set targets for given message (users and groups in hash ref)

=cut

##########################################################################
sub notification_set_targets {
	my ($pa_config, $dbh, $notification_id, $users, $groups) = @_;
	my $ret = undef;

	if (!defined($pa_config)) {
		return undef;
	}

	if (!defined($notification_id)) {
		return undef;
	}

	if (ref($users) eq "ARRAY") {
		my $values = {};
		foreach my $user (@{$users}) {
			if (defined($user) && $user eq "") {
				next;
			}

			$values->{'id_mensaje'} = $notification_id;
			$values->{'id_user'} = $user;
		}

		$ret = db_process_insert($dbh, '', 'tnotification_user', $values);
		if (!$ret) {
			return undef;
		}
	}

	if (ref($groups) eq "ARRAY") {
		my $values = {};
		foreach my $group (@{$groups}) {
			if ($group != 0 && empty($group)) {
				next;
			}

			$values->{'id_mensaje'} = $notification_id;
			$values->{'id_group'} = $group;
		}

		$ret = db_process_insert($dbh, '', 'tnotification_group', $values);
		if (!$ret) {
			return undef;
		}
	}

	return 1;
}

##########################################################################

=head2 C<< notification_get_users (I<$dbh>, I<$source>) >>
Get targets for given sources
=cut

##########################################################################
sub notification_get_users {
	my ($dbh, $source) = @_;

	my @results = get_db_rows(
		$dbh,
		'SELECT id_user
		 FROM tnotification_source_user nsu
		   INNER JOIN tnotification_source ns ON nsu.id_source=ns.id
		 WHERE ns.description = ?
		',
		safe_input($source)
	);

	@results = map {
		if(ref($_) eq 'HASH') { $_->{'id_user'} }
		else {}
	} @results;

	return @results;
}

##########################################################################

=head2 C<< notification_get_groups (I<$dbh>, I<$source>) >>
Get targets for given sources
=cut

##########################################################################
sub notification_get_groups {
	my ($dbh, $source) = @_;

	my @results = get_db_rows(
		$dbh,
		'SELECT id_group
		 FROM tnotification_source_group nsg
		   INNER JOIN tnotification_source ns ON nsg.id_source=ns.id
		 WHERE ns.description = ?
		',
		safe_input($source)
	);

	@results = map {
		if(ref($_) eq 'HASH') { $_->{'id_group'} }
		else {}
	} @results;

	return @results;
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

Copyright (c) 2005-2021 Artica Soluciones Tecnologicas S.L

=cut

