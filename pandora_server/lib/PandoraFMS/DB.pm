package PandoraFMS::DB;
##########################################################################
# Database Package
# Pandora FMS. the Flexible Monitoring System. http://www.pandorafms.org
##########################################################################
# Copyright (c) 2004-2009 Sancho Lerena, slerena@gmail.com
# Copyright (c) 2005-2009 Artica Soluciones Tecnologicas S.L
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
use Time::Local;
use Time::Format qw(%time %strftime %manip); # For data mangling
use DBI;
use Date::Manip;# Needed to manipulate DateTime formats of input, output and compare
use XML::Simple;
use HTML::Entities;

use POSIX qw(strtod);

use PandoraFMS::Tools;
enterprise_load ();

require Exporter;

our @ISA = ("Exporter");
our %EXPORT_TAGS = ( 'all' => [ qw( ) ] );
our @EXPORT_OK = ( @{ $EXPORT_TAGS{'all'} } );
our @EXPORT = qw( 	
        crea_agente_modulo			
		update_on_error
		dame_server_id				
		dame_agente_id
		dame_agente_modulo_id
		dame_agente_nombre
		dame_comando_alerta
		dame_desactivado
		dame_grupo_agente
		dame_id_tipo_modulo
		dame_intervalo
		dame_learnagente
		dame_modulo_id
		dame_nombreagente_agentemodulo
		dame_nombretipomodulo_idagentemodulo
		dame_ultimo_contacto
		give_networkserver_status
		
		pandora_writedata
		pandora_writestate
		
		pandora_updateserver
		pandora_serverkeepaliver
		pandora_audit
		pandora_lastagentcontact
		pandora_evaluate_alert
		pandora_evaluate_compound_alert
		pandora_generate_alerts
		pandora_generate_compound_alerts
		pandora_process_alert
		pandora_planned_downtime
		pandora_create_agent
		pandora_event
		module_generic_proc
		module_generic_data
		module_generic_data_inc
		module_generic_data_string
		execute_alert
		give_network_component_profile_name
		pandora_create_incident 
		update_keepalive_module
		
		get_db_value
		get_db_free_row
		get_db_all_rows
		get_db_free_field
		db_insert
		db_do
	);

# Spanish translation note:
# 'Crea' in spanish means 'create'
# 'Dame' in spanish means 'give'

##########################################################################
## SUB subst_alert_macros (string, macros) {
## Searches string for macros and substitutes them with their values.
##########################################################################

sub subst_alert_macros ($\%) {
        my $string = $_[0];
        my %macros = %{$_[1]};

        while ((my $macro, my $value) = each (%macros)) {
                $string =~ s/($macro)/$value/ig;
        }

        return $string;
}

##########################################################################
## SUB pandora_generate_alerts
## (paconfig, timestamp, agent_name, $id_agent, id_agent_module,
## id_group, module_data, module_type, dbh)
## Generate alerts for a given module.
##########################################################################

sub pandora_generate_alerts (%$$$$$$$) {
	my $pa_config = $_[0];
	my $timestamp = $_[1];
	my $agent_name = $_[2];
	my $id_agent = $_[3];
	my $id_agent_module = $_[4];
	my $id_group = $_[5];
	my $module_data = $_[6];
	my $dbh = $_[7];

	# Do not generate alerts for disabled groups
	if (give_group_disabled ($pa_config, $id_group, $dbh) == 1) {
		return;
	}

	# Get enabled alerts associated with this module
	my @alerts = get_db_all_rows ("SELECT talert_template_modules.id as id_template_module, talert_template_modules.*, talert_templates.*
	                               FROM talert_template_modules, talert_templates
	                               WHERE talert_template_modules.id_alert_template = talert_templates.id
	                               AND id_agent_module = $id_agent_module
	                               AND disabled = 0", $dbh);

	foreach my $alert_data (@alerts) {
		my $rc = pandora_evaluate_alert($pa_config, $timestamp, $alert_data,
										$module_data, 0, $dbh);
		pandora_process_alert ($pa_config, $timestamp, $rc, $agent_name,
							   $id_agent, $id_group, $alert_data, $module_data, 0,
							   $dbh);

		# Evaluate compound alerts even if the alert status did not change in
		# case the compound alert does not recover
		pandora_generate_compound_alerts ($pa_config, $timestamp,
										  $agent_name, $id_agent,
										  $alert_data->{'id_template_module'},
										  $id_group, 0, $dbh);
	}
}

##########################################################################
## SUB pandora_evaluate_alert
## (paconfig, timestamp, alert_data, module_data, dbh)
## Evaluate trigger conditions for a given alert. Returns:
##  0 Execute the alert.
##  1 Do not execute the alert.
##  2 Do not execute the alert, but increment its internal counter.
##  3 Cease the alert.
##  4 Recover the alert.
##  5 Reset internal counter (alert not fired, interval elapsed).
##########################################################################

sub pandora_evaluate_alert ($$$$$$) {
	my $pa_config = $_[0];
	my $timestamp = $_[1];
	my $alert_data = $_[2];
	my $module_data = $_[3];
	my $compound = $_[4];
	my $dbh = $_[5];

	my $status = 1; # Value returned on valid data
	my $err;

	# Check weekday
	if ($alert_data->{lc(&UnixDate("today","%A"))} != 1) {
		return 1;
	}

	# Check time slot
	my $time = &UnixDate("today","%H:%M");

	if (($alert_data->{'time_to'} ne $alert_data->{'time_from'}) &&
		(($time ge $alert_data->{'time_to'}) ||
		 ($time le $alert_data->{'time_from'}))) {
		return 1;
	}

	# Check time threshold
	my $limit_date = DateCalc (ParseDateString("epoch " . $alert_data->{'last_reference'}), "+ " .
							   $alert_data->{'time_threshold'} . " seconds",
							   \$err);
	my $date = ParseDate($timestamp);

	if ($alert_data->{'times_fired'} > 0) {

		# Reset fired alerts
		if (Date_Cmp ($date, $limit_date) >= 0) {

			# Cease on valid data
			$status = 3;

			# Always reset
			$alert_data->{'internal_counter'} = 0;
			$alert_data->{'times_fired'} = 0;
		}

		# Recover takes precedence over cease
		if ($alert_data->{'recovery_notify'} == 1) {
			$status = 4;
		}
	} elsif (Date_Cmp ($date, $limit_date) >= 0) {
		$status = 5;
	}

	# Check for valid data
	if ($compound == 0) {
		if ($alert_data->{'type'} eq "min" && $module_data >= $alert_data->{'min_value'}) {
			return $status;
		}
		elsif ($alert_data->{'type'} eq "max" && $module_data <= $alert_data->{'max_value'}) {
			return $status;
		}
		elsif ($alert_data->{'type'} eq "max_min") {
			if ($alert_data->{'matches_value'} == 1 &&
				$module_data <= $alert_data->{'min_value'} &&
				$module_data >= $alert_data->{'max_value'}) {
				return $status;
			}

			if ($module_data >= $alert_data->{'min_value'} &&
				$module_data <= $alert_data->{'max_value'}) {
				return $status;
			}
		}
		elsif ($alert_data->{'type'} eq "equal" && $module_data != $alert_data->{'value'}) {
			return $status;
		}
		elsif ($alert_data->{'type'} eq "not_equal" && $module_data == $alert_data->{'value'}) {
			return $status;
		}
		elsif ($alert_data->{'type'} eq "regex") {
			if ($alert_data->{'value'} == 1 && $module_data =~ m/$alert_data->{'value'}/i) {
				return $status;
			}
			
			if ($module_data !~ m/$alert_data->{'value'}/i) {
				return $status;
			}
		}
	}
	elsif (pandora_evaluate_compound_alert($pa_config, $alert_data->{'id'}, $dbh) == 0) {
		return $status
	}

	# Check min and max alert limits
	if (($alert_data->{'internal_counter'} < $alert_data->{'min_alerts'}) ||
		($alert_data->{'times_fired'}  >= $alert_data->{'max_alerts'})) {
		return 2;
	}

	return 0;
}

##########################################################################
## SUB pandora_process_alert
## ($pa_config, $timestamp, $rc, $agent_name, $id_agent, $id_group,
## $alert_data, $module_data, $dbh)
## Process an alert given the status returned by pandora_evaluate_alert.
##########################################################################

sub pandora_process_alert (%$$$$$%$$) {
	my $pa_config = $_[0];
	my $timestamp = $_[1];
	my $rc = $_[2];
	my $agent_name = $_[3];
	my $id_agent = $_[4];
	my $id_group = $_[5];
	my $alert_data = $_[6];
	my $module_data = $_[7];
	my $compound = $_[8];
	my $dbh = $_[9];

	# Do not execute
	if ($rc == 1) {
		return;
	}

	# Compound or simple alert?
	my $table;
	my $id;

	if ($compound == 0) {
		$table = 'talert_template_modules';
		$id = 'id_template_module';
	}
	else {
		$table = 'talert_compound';
		$id = 'id';
	}

	# Cease
	if ($rc == 3) {

		# Update alert status
		db_do("UPDATE $table SET times_fired = 0,
				 internal_counter = 0 WHERE id = " .
				 $alert_data->{$id}, $dbh);

		# Generate an event
		pandora_event ($pa_config, "Alert ceased (" .
					   $alert_data->{'descripcion'} . ")", $id_group,
					   $id_agent, $alert_data->{'priority'}, $alert_data->{'id_template_module'}, $alert_data->{'id_agent_module'}, 
					   "alert_recovered", $dbh);

		return;
	}

	# Recover
	if ($rc == 4) {

		# Update alert status
		db_do("UPDATE $table SET times_fired = 0,
				 internal_counter = 0 WHERE id = " .
				 $alert_data->{$id}, $dbh);

		execute_alert ($pa_config, $alert_data, $id_agent, $id_group, $agent_name,
					   $module_data, 0, $compound, $dbh);
		return;
	}

	# Reset internal counter
	if ($rc == 5) {
		db_do("UPDATE $table SET internal_counter = 0 WHERE id = " .
				 $alert_data->{$id}, $dbh);
		return;
	}

	# Get current date
	my $date_db = &UnixDate("today","%s");

	# Increment internal counter
	if ($rc == 2) {
		my $new_interval = "";

		# Start a new interval
		if ($alert_data->{'internal_counter'} == 0) {
			$new_interval = ", last_reference = $date_db";
		}

		# Update alert status
		$alert_data->{'internal_counter'} += 1;

		# Do not increment times_fired, but set it in case the alert was reset
		db_do("UPDATE $table SET times_fired = " .
				 $alert_data->{'times_fired'} . ", internal_counter = " .
				 $alert_data->{'internal_counter'} . $new_interval .
				 " WHERE id = " . $alert_data->{$id}, $dbh);
		return;
	}

	# Execute
	if ($rc == 0) {
		my $new_interval = "";

		# Start a new interval
		if ($alert_data->{'internal_counter'} == 0) {
			$new_interval .= ", last_reference = $date_db";
		}

		# Update alert status
		$alert_data->{'times_fired'} += 1;
		$alert_data->{'internal_counter'} += 1;

		db_do("UPDATE $table SET times_fired = " .
				 $alert_data->{'times_fired'} . ", last_fired = $date_db, internal_counter = " .
				 $alert_data->{'internal_counter'} . $new_interval . " WHERE id = " . $alert_data->{$id}, $dbh);

		execute_alert ($pa_config, $alert_data, $id_agent, $id_group, $agent_name, 
						$module_data, 1, $compound, $dbh);

		return;
	}
}

##########################################################################
## SUB pandora_evaluate_compound_alert
## (pa_config, id, dbh)
## Evaluate a given compound alert. Returns 1 if the alert should be
## fired, 0 if not.
##########################################################################
sub pandora_evaluate_compound_alert (%$$) {
	my $pa_config = $_[0];
	my $id = $_[1];
	my $dbh = $_[2];

	my @data;

	# Return value
	my $status = 0;

	# Get all the alerts associated with this compound alert
	my $query_compound = "SELECT id_alert_template_module, operation FROM talert_compound_elements
						  WHERE id_alert_compound = '$id' ORDER BY `order`";
	my $handle_compound = $dbh->prepare($query_compound);
	$handle_compound ->execute;

	if ($handle_compound->rows == 0) {
		return 0;
	}

	my $query_alert = "SELECT times_fired FROM
					  talert_template_modules WHERE id = ? AND disabled = 0";
	my $handle_alert = $dbh->prepare($query_alert);

	while (my $data_compound = $handle_compound->fetchrow_hashref()) {

		# Get alert data if enabled
		$handle_alert->execute($data_compound->{'id_alert_template_module'});
		if ($handle_alert->rows == 0) {
			next;
		}

		my $data_alert = $handle_alert->fetchrow_hashref();

		# Check whether the alert was fired
		my $fired = $data_alert->{'times_fired'} > 0 ? 1 : 0;

		my $operation = $data_compound->{'operation'};

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

	$handle_alert->finish();
	$handle_compound->finish();
	return $status;
}

##########################################################################
## SUB pandora_generate_compound_alerts
## (pa_config, timestamp, agent_name, id_agent, id_alert_agent_module, id_group,
## module_data, module_type, depth, dbh)
## Generate compound alerts that depend on a given alert.
##########################################################################

sub pandora_generate_compound_alerts (%$$$$$$$) {
	my $pa_config = $_[0];
	my $timestamp = $_[1];
	my $agent_name = $_[2];
	my $id_agent = $_[3];
	my $id_alert_template_module = $_[4];
	my $id_group = $_[5];
	my $depth = $_[6];
	my $dbh = $_[7];

	# Get all compound alerts that depend on this alert
	my $query_compound = "SELECT id_alert_compound FROM talert_compound_elements WHERE id_alert_template_module = '" .
						 $id_alert_template_module . "'";

	my $handle_compound = $dbh->prepare($query_compound);

	$handle_compound->execute;

	if ($handle_compound->rows == 0) {
		$handle_compound->finish();
		return;
	}

	my $query_alert = "SELECT * FROM talert_compound WHERE id = ?";
	my $handle_alert = $dbh->prepare($query_alert);

	while (my $data_compound = $handle_compound->fetchrow_hashref()) {

		# Get compound alert parameters
		$handle_alert->execute($data_compound->{'id_alert_compound'});
		if ($handle_alert->rows == 0) {
			next;
		}

		my $data_alert = $handle_alert->fetchrow_hashref();

		# Evaluate the alert
		my $rc = pandora_evaluate_alert($pa_config, $timestamp, $data_alert,
										'', 1, $dbh);

		pandora_process_alert ($pa_config, $timestamp, $rc, $agent_name, $id_agent,
							   $id_group, $data_alert, '', 1, $dbh);

		# Evaluate nested compound alerts
		#if ($depth >= $pa_config->{'compound_max_depth'}) {
		#	logger($pa_config, "ERROR: Error in SUB pandora_generate_compound_
		#						alerts(): Maximum nested compound alert depth
		#						reached.", 2);
		#	next;
		#}

		#&pandora_generate_compound_alerts ($pa_config, $timestamp, $agent_name,
		#								   $id_agent, $data_compound->{'id'},
		#								   $id_group, $depth + 1, $dbh);
	}

	$handle_alert->finish();
	$handle_compound->finish();
}

##########################################################################
## SUB execute_alert 
## Do a execution of given alert with this parameters
##########################################################################

sub execute_alert ($$$$$$$$$) {
	my $pa_config = $_[0];
	my $alert = $_[1];
	my $id_agent = $_[2];
	my $id_group = $_[3];
	my $agent = $_[4];
	my $data = $_[5];
	my $alert_mode = $_[6]; # 0 recovery, 1 normal
	my $compound = $_[7];
	my $dbh = $_[8];
	
	# Get active actions/commands
	my @actions;

	if ($compound == 0) {
		@actions = get_db_all_rows ("SELECT * FROM talert_template_module_actions, talert_actions, talert_commands
	    	                            WHERE talert_template_module_actions.id_alert_action = talert_actions.id
	        	                        AND talert_actions.id_alert_command = talert_commands.id
	            	                    AND talert_template_module_actions.id_alert_template_module = " .
	                	                $alert->{'id_template_module'} .
	                    	           " AND ((fires_min = 0 AND fires_max = 0)
	                                      OR (" . $alert->{'times_fired'} . " >= fires_min AND " . $alert->{'times_fired'} . " <= fires_max))", $dbh);	
	}
	else {
		@actions = get_db_all_rows ("SELECT * FROM talert_compound_actions, talert_actions, talert_commands
	    	                            WHERE talert_compound_actions.id_alert_action = talert_actions.id
	        	                        AND talert_actions.id_alert_command = talert_commands.id
	            	                    AND talert_compound_actions.id_alert_compound = " .
	                	                $alert->{'id'} .
	                    	           " AND ((fires_min = 0 AND fires_max = 0)
	                                      OR (" . $alert->{'times_fired'} . " >= fires_min AND " . $alert->{'times_fired'} . " <= fires_max))", $dbh);
	}

	# Get default action
	if ($#actions < 0) {

		# Compound alert don't have a default action
		if ($compound == 1) {
			return;
		}

		@actions = get_db_all_rows ("SELECT * FROM talert_actions, talert_commands
	                                 WHERE talert_actions.id = " . $alert->{'id_alert_action'} .
	                                 " AND talert_actions.id_alert_command = talert_commands.id", $dbh);
		if ($#actions < 0) {
			return;
		}
	}
	
	# Get agent address
	my $address = get_db_value ('direccion',  'tagente', 'id_agente', $id_agent, $dbh);

	# Execute actions
	foreach my $action (@actions) {
		my $field1 =  $action->{'field1'} ne "" ? $action->{'field1'} : $alert->{'field1'};
		my $field2 =  $action->{'field2'} ne "" ? $action->{'field2'} : $alert->{'field2'};
		my $field3 =  $action->{'field3'} ne "" ? $action->{'field3'} : $alert->{'field3'};
		
		# Recovery fields, thanks to Kato Atsushi
		if ($alert_mode == 0){
			$field2 = $alert->{'field2_recovery'} ne "" ? $alert->{'field2_recovery'} : "[RECOVER]" . $field2;
			$field2 = $alert->{'field3_recovery'} ne "" ? $alert->{'field2_recovery'} : "[RECOVER]" . $field3;
		}

		# Alert macros
		my %macros = (_field1_ => $field1,
					  _field2_ => $field2,
					  _field3_ => $field3,
					  _agent_ => $agent,
					  _address_ => $address,
					  _timestamp_ => &UnixDate ("today", "%Y-%m-%d %H:%M:%S"),
					  _data_ => $data,
					  _alert_description_ => $alert->{'description'},
					  _alert_threshold_ => $alert->{'time_threshold'},
					  _alert_times_fired_ => $alert->{'times_fired'},
					 );

		logger($pa_config, "Alert (" . $alert->{'name'} . ") executed for agent $agent", 2);

		# User defined alerts
		if ($action->{'internal'} == 0) {
			my $command = subst_alert_macros ($action->{'command'}, %macros);
			$command = decode_entities($command);
			eval {
				system ($command);
				my $rc = $? >> 8; # Shift 8 bits to get a "classic" errorlevel
				if ($rc != 0) {
					logger($pa_config, "Executed command for alert " . $alert->{'name'} . " returned with errorlevel $rc", 1);
				}
			};

			if ($@){
				logger($pa_config, "Error $@ executing command $command", 1);
			}
		# Internal Audit
		} elsif ($action->{'name'} eq "Internal Audit") {
			logger($pa_config, "Internal audit for agent $agent", 3);
			$field1 = subst_alert_macros ($field1, %macros);
			pandora_audit ($pa_config, $field1, $agent, "Alert (" . $alert->{'description'} . ")", $dbh);
			
			# Return without creating an event
			return;
		# Email
		} elsif ($action->{'name'} eq "eMail") {
			$field2 = subst_alert_macros ($field2, %macros);
			$field3 = subst_alert_macros ($field3, %macros);
			pandora_sendmail ($pa_config, $field1, $field2, $field3);
		# Internal event
		} elsif ($action->{'name'} eq "Pandora FMS Event") {
		# Unknown
		} else {
			logger($pa_config, "Unknown action " . $action->{'name'}, 1);
			return;
		}

		# Create an event
		if ($alert_mode == 0){ 
			pandora_event ($pa_config, "Alert recovered (" . $alert->{'description'} . ")", $id_group, $id_agent, $alert->{'priority'}, $compound == 0 ? $alert->{'id_template_module'} : 0, 
			$alert->{'id_agent_module'}, 'alert_recovered',  $dbh);
		} else {
			pandora_event ($pa_config, "Alert fired (" . $alert->{'description'} . ")", $id_group, $id_agent, $alert->{'priority'}, $compound == 0 ? $alert->{'id_template_module'} : 0, 
			$alert->{'id_agent_module'}, 'alert_fired',  $dbh);
		}
	}
}


##########################################################################
## SUB pandora_writestate (pa_config, nombre_agente,tipo_modulo,
#							nombre_modulo,valor_datos, dbh, needupdate)
## Alter data, chaning status of modules in state table
##########################################################################

sub pandora_writestate (%$$$$$$) {
	# slerena, 05/10/04 : Fixed bug because differences between agent / server time source.
	# now we use only local timestamp to stamp state of modules
	my $pa_config = $_[0];
	my $nombre_agente = $_[1];
	my $tipo_modulo = $_[2]; # passed as string
	my $nombre_modulo = $_[3];
	my $datos = $_[4]; # Careful: This don't reference a hash, only a single value
	my $dbh = $_[5];
	my $needs_update = $_[6];
	
	my $cambio = 0;

	# Get current timestamp / unix numeric time
	my $timestamp = &UnixDate ("today", "%Y-%m-%d %H:%M:%S"); # string timestamp
	my $utimestamp = &UnixDate($timestamp, "%s"); # convert from human to integer

	# Get server id
	my $server_name = $pa_config->{'servername'}.$pa_config->{"servermode"};
	my $id_server = dame_server_id($pa_config, $server_name, $dbh);

	# Get id
	# BE CAREFUL: We don't verify the strings chains
	# TO DO: Verify errors

	my $agent_data = get_db_free_row ("SELECT * FROM tagente WHERE nombre = '$nombre_agente'", $dbh);
	if ($agent_data == -1){
		return -1;
	}
	
	my $id_modulo = dame_modulo_id ($pa_config, $tipo_modulo, $dbh);
	my $id_agente_modulo = dame_agente_modulo_id($pa_config, $agent_data->{'id_agente'}, $id_modulo, $nombre_modulo, $dbh);

	# Valid agent ?
	if ($id_agente_modulo == -1) {
		return -1;
	}

	my $module_data = get_db_free_row ("SELECT * FROM tagente_modulo WHERE id_agente_modulo = $id_agente_modulo", $dbh);

	# Valid string data ? (not null)
	if (($id_modulo == 3) || ($id_modulo == 17) || ($id_modulo == 10) || ($id_modulo == 23)){
			if ($datos eq "") {
				return -1;
			}
	}

	# Take group for this module
	my $id_grupo = dame_grupo_agente($pa_config, $agent_data->{'id_agente'}, $dbh);

	# Postprocess management
	if ( defined($module_data->{'post_process'}) && ($module_data->{'post_process'}> 0)) {
		if (($id_modulo == 1) || ($id_modulo == 7) || ($id_modulo == 15) || ($id_modulo == 22) || ($id_modulo == 4) || ($id_modulo == 8) || ($id_modulo == 16) ){
			$datos = $datos * $module_data->{'post_process'};
		}
	}

	# Status management
	my $estado = 0; # Normal (OK) by default
	

	# Only PROC modules have min_critical/max_critical default
	if ( $tipo_modulo  =~ m/proc/ ){
		if ($module_data->{'min_critical'} eq $module_data->{'max_critical'}){
			$module_data->{'min_critical'} = 0;
			$module_data->{'max_critical'} = 1;
		}
	}
	
	if ($module_data->{'min_warning'} ne $module_data->{'max_warning'}){
		if (($datos >= $module_data->{'min_warning'}) && ($datos < $module_data->{'max_warning'})){ 
			$estado = 2;
		}
		if (($datos >= $module_data->{'min_warning'}) && ($module_data->{'max_warning'} < $module_data->{'min_warning'})){ 
			$estado = 2;
		}
	}

	if ($module_data->{'min_critical'} ne $module_data->{'max_critical'}){
		if (($datos >= $module_data->{'min_critical'}) && ($datos < $module_data->{'max_critical'})){ 
			$estado = 1;
		}
		if (($datos >= $module_data->{'min_critical'}) && ($module_data->{'max_critical'} < $module_data->{'min_critical'})){ 
			$estado = 1;
		}
	}

	# Get module interval or agent interval if module don't defined
	my $module_interval = $module_data->{'module_interval'};
	if ($module_data->{'module_interval'} == 0){
		$module_interval = dame_intervalo ($pa_config, $module_data->{'id_agente'}, $dbh);
 	}

	# Check alert subroutine - Protect execution on an eval block

	eval {
		pandora_generate_alerts ($pa_config, $timestamp, $nombre_agente, $module_data->{'id_agente'}, $id_agente_modulo, $id_grupo, $datos, $dbh);
	};
	if ($@) {
		logger($pa_config, "ERROR: Error in SUB calcula_alerta(). ModuleName: $nombre_modulo ModuleType: $tipo_modulo AgentName: $nombre_agente", 4);
		logger($pa_config, "ERROR Code: $@",10)
	}

	# Let's see if there is any entry at tagente_estado table
	my $data_status = get_db_free_row ("SELECT * from tagente_estado WHERE id_agente_modulo = ". $module_data->{'id_agente_modulo'}, $dbh);
	
	# Apply Mysql quotes to data to prepare for database insertion / update
	$datos = $dbh->quote($datos); # Parse data entry for adecuate SQL representation.

	my $query_act; 
	
	if ($data_status == -1) { # Doesnt exist entry in table, lets make the first entry
		logger($pa_config, "Create entry in tagente_estado for module $nombre_modulo", 4);
		$query_act = "INSERT INTO tagente_estado (id_agente_modulo, datos, timestamp, estado, id_agente, last_try, utimestamp, current_interval, running_by, last_execution_try, last_status, status_changes) VALUES (". $module_data->{'id_agente_modulo'} ." , $datos,'$timestamp',". $estado ." , " . $agent_data->{'id_agente'}. ",'$timestamp',$utimestamp, $module_interval, $id_server, $utimestamp, 0, 0)"; 

	} else { # An entry in table already exists
		$data_status->{'estado'} = $estado;
		if ( $data_status->{'last_execution_try'} == 0){
			$needs_update = 1;
		}

		# Track status change
		if ( $data_status->{'last_status'} == $data_status->{'estado'}){
			$data_status->{'status_changes'} = 0;
		} else {
			$data_status->{'status_changes'} = $data_status->{'status_changes'} + 1 ;
			
			# Raise event ?
			if ($data_status->{'status_changes'} > $module_data->{'min_ff_event'}){
				
				$needs_update = 1;
				my $event_type = "";
				my $status_name = "";
				my $severity = 0;
				
				if (($data_status->{'last_status'} == 0) && ($data_status->{'estado'} == 2)){
					$event_type = "going_up_warning";
					$status_name = "going up to WARNING";
					$severity = 3;
					enterprise_hook('mcast_change_report', [$pa_config, $module_data->{'nombre'}, $module_data->{'custom_id'}, $timestamp, 'WARN', $dbh]);
				} elsif (($data_status->{'last_status'} == 1) && ($data_status->{'estado'} == 2)){
					$event_type = "going_down_warning";
					$status_name = "going down to WARNING";
					$severity = 3;
					enterprise_hook('mcast_change_report', [$pa_config, $module_data->{'nombre'}, $module_data->{'custom_id'}, $timestamp, 'WARN', $dbh]);
				} elsif ($data_status->{'estado'} == 1){
					$event_type = "going_down_critical";
					$status_name = "going down to CRITICAL";
					$severity = 4;
					enterprise_hook('mcast_change_report', [$pa_config, $module_data->{'nombre'}, $module_data->{'custom_id'}, $timestamp, 'ERR', $dbh]);
				} elsif ($data_status->{'estado'} == 0){
					$event_type = "going_up_normal";
					$status_name = "going up to NORMAL";
					$severity = 2;
                    enterprise_hook('mcast_change_report', [$pa_config, $module_data->{'nombre'}, $module_data->{'custom_id'}, $timestamp, 'OK', $dbh]);
				}
				$data_status->{'status_changes'} = 0;
				$data_status->{'last_status'} = $data_status->{'estado'};
				
				my $description = "Module ".$module_data->{'nombre'}." ($datos) is $status_name";
				pandora_event ($pa_config, $description, $id_grupo,
							$module_data->{'id_agente'}, $severity, 0, $module_data->{'id_agente_modulo'}, 
							$event_type, $dbh);
							
				if ($event_type eq "going_up_warning"){
					# Clean up and system mark all active CRITICAL events for this module
					db_do ("UPDATE tevento SET estado=1 WHERE id_agentmodule = ".$module_data->{'id_agente_modulo'}." AND event_type = 'going_down_critical'", $dbh);
				}
				elsif ($event_type eq "going_up_normal"){
					# Clean up and system mark all active WARNING and CRITICAL events for this module 
					db_do ("UPDATE tevento SET estado=1 WHERE id_agentmodule = ".$module_data->{'id_agente_modulo'}." AND (event_type = 'going_up_warning' OR event_type = 'going_down_warning' OR event_type = 'going_down_critical')", $dbh);
				}
			}
		}

		my $needs_update_sql = "";
		if ($needs_update == 1) {
			$needs_update_sql = " , last_try = '$timestamp' ";
		}
		
		$query_act = "UPDATE tagente_estado SET utimestamp = $utimestamp, datos = $datos, last_status = " . $data_status->{'last_status'} . ", status_changes = " . $data_status->{'status_changes'} . ", timestamp = '$timestamp', estado = ".$data_status->{'estado'}.", id_agente = $module_data->{'id_agente'}, current_interval = $module_interval, running_by = $id_server, last_execution_try = $utimestamp " . $needs_update_sql . " WHERE id_agente_modulo = ". $module_data->{'id_agente_modulo'};
	}
	db_do ($query_act, $dbh);
}

##########################################################################
####   MODULOS implementados en Pandora
##########################################################################

# ----------------------------------------+
# Modulos genericos de Pandora			|
# ----------------------------------------+

# Los modulos genericos de pandora son de 4 tipos
#
# generic_data . Almacena numeros enteros largos, util para monitorizar proceos que
#								general valores o sensores que devuelven valores.

# generic_proc . Almacena informacion booleana (cierto/false), util para monitorizar
#				 procesos logicos.

# generic_data_inc . Almacena datos igual que generic_data pero tiene una logica
#								que sirve para las fuentes de datos que alimentan el agente con datos
#								que se incrementan continuamente, por ejemplo, los contadores de valores
#								en las MIB de los adaptadores de red, las entradas de cierto tipo en
#								un log o el nº de segundos que ha pasado desde X momento. Cuando el valor
#								es mejor que el anterior o es 0, se gestiona adecuadamente el cambio.

# generic_data_string. Store a string, max 255 chars.

##########################################################################
## SUB pandora_accessupdate (pa_config, id_agent, dbh)
## Update agent access table
##########################################################################

sub pandora_accessupdate (%$$) {
	my $pa_config = $_[0];
	my $id_agent = $_[1];
	my $dbh = $_[2];
	my $err;

		if ($id_agent != -1){
			my $intervalo = dame_intervalo ($pa_config, $id_agent, $dbh);
			my $utimestamp = &UnixDate("today","%s");
			my $query2 = "INSERT INTO tagent_access (id_agent, utimestamp) VALUES ($id_agent,'$utimestamp')";
			$dbh->do($query2);
		}
}

##########################################################################
## SUB module_generic_proc (param_1, param_2, param_3)
## Procesa datos genericos sobre un proceso
##########################################################################
## param_1 : Nombre de la estructura contenedora de datos (XML)
## paran_2 : Timestamp del paquete de datos
## param_3 : Agent name
## param_4 : Module Type

sub module_generic_proc (%$$$$$) {
	my $pa_config = $_[0];
	my $datos = $_[1];
	my $a_timestamp = $_[2];
	my $agent_name = $_[3];
	my $module_type = $_[4];
	my $dbh = $_[5];
	my $bUpdateDatos = 0; # added, patch submitted by Dassing
	my $estado;
	# Leemos datos de la estructura
	my $a_datos = $datos->{data}->[0];

	if ((ref($a_datos) eq "HASH")){
		$a_datos = 0;	# If get bad data, then this is bad value, not "unknown" (> 1.3 version)
	} else {
		if (!is_numeric($a_datos)){
			$a_datos = 0;
		} else {
			$a_datos = sprintf("%.2f", $a_datos);		# Two decimal float. We cannot store more
		}
	}							# to change this, you need to change mysql structure
	$a_datos =~ s/\,/\./g; 				# replace "," by "." avoiding locale problems
	my $a_name = $datos->{name}->[0];
	my $a_desc = $datos->{description}->[0];
	my $a_max = $datos->{max}->[0];
	my $a_min = $datos->{min}->[0];

	if (ref($a_max) eq "HASH") {
		$a_max = "";
	}
	if (ref($a_min) eq "HASH") {
		$a_min = "";
	}
	if (pandora_writedata ($pa_config, $a_timestamp, $agent_name, $module_type, $a_name, 
						$a_datos, $a_max, $a_min, $a_desc, $dbh, \$bUpdateDatos) != -1){
		pandora_writestate ($pa_config, $agent_name, $module_type, $a_name, $a_datos, $dbh, $bUpdateDatos);
	}
}

##########################################################################
## SUB module_generic_data (param_1, param_2,param_3, param_4)
## Process generated data form numeric data module acquire
##########################################################################
## param_1 : XML name
## paran_2 : Timestamp
## param_3 : Agent name
## param_4 : Module type (generic_data, async_data or network data)

sub module_generic_data (%$$$$$) {
	my $pa_config = $_[0];
	my $datos = $_[1];
	my $m_timestamp = $_[2];
	my $agent_name = $_[3];
	my $module_type = $_[4];
	my $dbh = $_[5];

	# Leemos datos de la estructura
	my $m_name = $datos->{name}->[0];
	my $a_desc = $datos->{description}->[0];
	my $m_data = $datos->{data}->[0];
	
	# Notes to improve module_generic_* functions.
	#
	# #1 checking for correct data should be made before calling writedata or writestate
	# #2 a new procedure called return modulehash should detect if exists that module,
	#	create them, and always return a hash with agent needed information and module needed information
	# #3 this hash should be used as parameter in writedata and writestate in order to have all needed 
	# information and don't need to ask again for the same data. At this time this code is very low and bad
	# written, need to be optimized.
 
	my $bUpdateDatos = 0; # added, patch submitted by Dassing
	if (ref($m_data) ne "HASH"){
		if (!is_numeric($m_data)){
			logger($pa_config, "(data) Invalid data (non-numeric) received from $agent_name, module $m_name", 3);
			return -1;
		}
		if ($m_data =~ /[0-9]*/){
			$m_data =~ s/\,/\./g; # replace "," by "."
			$m_data = sprintf("%.2f", $m_data);# Two decimal float. We cannot store more
		} else {
			$m_data =0;
		}
		$m_data =~ s/\,/\./g; # replace "," by "."
		my $a_max = $datos->{max}->[0];
		my $a_min = $datos->{min}->[0];
	
		if (ref($a_max) eq "HASH") {
			$a_max = "";
		}
		if (ref($a_min) eq "HASH") {
			$a_min = "";
		}
		if (pandora_writedata($pa_config, $m_timestamp,$agent_name,$module_type,$m_name,$m_data,$a_max,$a_min,$a_desc,$dbh,\$bUpdateDatos) != -1){
			pandora_writestate ($pa_config, $agent_name, $module_type, $m_name, $m_data, $dbh, $bUpdateDatos);
		}
	} else {
		logger($pa_config, "(data) Invalid data value received from $agent_name, module $m_name", 3);
	}
}

##########################################################################
## SUB module_generic_data_inc (param_1, param_2,param_3, param_4)
## Process generated data form incremental numeric data module acquire
##########################################################################
## param_1 : XML name
## paran_2 : Timestamp
## param_3 : Agent name
## param_4 : Module type
sub module_generic_data_inc (%$$$$$) {
	my $pa_config = $_[0];
	my $datos = $_[1];
	my $m_timestamp = $_[2];
	my $agent_name = $_[3];
	my $module_type = $_[4];
	my $dbh = $_[5];
	my $bUpdateDatos = 0; # added, patch submitted by Dassing
	# Read structure data
	my $m_name = $datos->{name}->[0];
	my $a_desc = $datos->{description}->[0];
	my $m_data = $datos->{data}->[0];
	my $a_max = $datos->{max}->[0];
	my $a_min = $datos->{min}->[0];
	if (is_numeric($m_data)){
		$m_data =~ s/\,/\./g; # replace "," by "."
		$m_data = sprintf("%.2f", $m_data);# Two decimal float. We cannot store more
							# to change this, you need to change mysql structure
		$m_data =~ s/\,/\./g; # replace "," by "."
	
		if (!is_numeric($a_max)) {
			$a_max = "";
		}
		if (!is_numeric($a_min)) {
			$a_min = "";
		}	
		# my $timestamp = &UnixDate("today","%Y-%m-%d %H:%M:%S");
		# Algorith description:
		# 1) Search prev. value in database
				# 2) If new value is bigger than previous, store in tagente_datos differente between 
		#	last value and actual value, and in aux. table tagente_datos_inc the last real value
		# 3) If new data is lower than previous or no previous value (RESET), store 0 in tagente_datos and store
		#	real value in aux. table, replacing the old one
		
		# Obtemos los ID's a traves del paquete de datos
		my $id_agente = dame_agente_id ($pa_config, $agent_name, $dbh);
		my $id_modulo = dame_modulo_id ($pa_config, $module_type, $dbh); 
		my $id_agente_modulo = dame_agente_modulo_id($pa_config,$id_agente,$id_modulo,$m_name,$dbh);

		# Take last real data from tagente_datos_inc
		# in this table, store the last real data, not the difference who its stored in tagente_datos table and 
		# tagente_estado table

		my $diferencia = 0; 
		my $no_existe = 0;
		my $need_reset = 0;
		my $need_update = 0;
		my $new_data = 0;
		my $data_anterior = 0;
		my $timestamp_diferencia;
		my $timestamp_anterior = 0;
		my $m_utimestamp = &UnixDate ($m_timestamp, "%s");

		# tagente_datos_inc do not store real data (if real data has any post-process, data is compared and
		# stored in tagente_datos_inc with its original value).

		if (($id_agente_modulo == -1) && (dame_learnagente($pa_config, $id_agente, $dbh) eq "1" )) {
			$id_agente_modulo = crea_agente_modulo ($pa_config, $agent_name, $module_type, $m_name, $a_max, $a_min, $a_desc, $dbh);
			$no_existe = 1;
		} else {
			my $query_idag = "SELECT * FROM tagente_datos_inc WHERE id_agente_modulo = $id_agente_modulo";
			my $s_idag = $dbh->prepare($query_idag);
			$s_idag->execute;
			if ($s_idag->rows == 0) {
				# Does not exists entry in tagente_datos_inc yet
				$no_existe = 1;
			} else {
				my @data_row = $s_idag->fetchrow_array();
				if (is_numeric($data_row[2])){
					$data_anterior = $data_row[2];
				} 
				if (is_numeric($data_row[3])){
					$timestamp_anterior = $data_row[3];
				} 
				$diferencia = $m_data - $data_anterior;
				$timestamp_diferencia = $m_utimestamp - $timestamp_anterior;
				# get seconds between last data and this data
				if (($timestamp_diferencia > 0) && ($diferencia > 0)) {
					$diferencia = $diferencia / $timestamp_diferencia;
				}
				if ($diferencia < 0 ){
					$need_reset = 1;
				}
			}
			$s_idag -> finish();
		}
	
		# Update of tagente_datos_inx (AUX TABLE)	
		if ($no_existe == 1){
			my $query = "INSERT INTO tagente_datos_inc (id_agente_modulo,datos, utimestamp) VALUES ($id_agente_modulo, '$m_data', $m_utimestamp)";
			$dbh->do($query);
		} else {
			# Data exists previously	
			if ($diferencia != 0) {
				my $query2 = "UPDATE tagente_datos_inc SET utimestamp = $m_utimestamp, datos = '$m_data' WHERE id_agente_modulo  = $id_agente_modulo";
				$dbh->do($query2);
			}
		}

		if ($diferencia >= 0) {
			$new_data = $diferencia;
		} 

		# Update of tagente_datos and tagente_estado ? (only where there is a difference (or reset))
		if ($no_existe == 0){
			if (pandora_writedata ($pa_config, $m_timestamp, $agent_name, $module_type, $m_name, $new_data, $a_max, $a_min, $a_desc, $dbh, \$bUpdateDatos) != -1){
				# Inc status is always 100 (N/A)
				pandora_writestate ($pa_config, $agent_name, $module_type, $m_name, $new_data, $dbh, $bUpdateDatos);
			}
		}
	} else {
		logger ($pa_config, "(data_inc) Invalid data received from $agent_name, module $m_name", 2);
	}
}


##########################################################################
## SUB module_generic_data (param_1, param_2,param_3, param_4)
## Process generated data form alfanumeric data module acquire
##########################################################################
## param_1 : XML name
## paran_2 : Timestamp
## param_3 : Agent name
## param_4 : Module type

sub module_generic_data_string (%$$$$$) {
	my $pa_config = $_[0];
	my $datos = $_[1];
	my $m_timestamp = $_[2];
	my $agent_name = $_[3];
	my $module_type = $_[4];
	my $dbh = $_[5];
	my $bUpdateDatos = 0; # added, patch submitted by Dassing
	# Read Structure
	my $m_name = $datos->{name}->[0];
	my $m_data = $datos->{data}->[0];
	my $a_desc = $datos->{description}->[0];
	my $a_max = $datos->{max}->[0];
	my $a_min = $datos->{min}->[0];
	if (ref($m_data) eq "HASH") {
		$m_data = XMLout($m_data, RootName=>undef);
 	}
	if (ref($a_max) eq "HASH") {
				$a_max = "";
		}
		if (ref($a_min) eq "HASH") {
				$a_min = "";
		}
	if (pandora_writedata($pa_config, $m_timestamp, $agent_name, $module_type, $m_name, $m_data, $a_max, $a_min, $a_desc, $dbh, \$bUpdateDatos) != -1){
		# String type has no state (100 = N/A)
		pandora_writestate ($pa_config, $agent_name, $module_type, $m_name, $m_data, $dbh, $bUpdateDatos);
	}
}


##########################################################################
## SUB pandora_writedata (pa_config, timestamp,nombre_agente,tipo_modulo, 
#							nombre_modulo, datos, max, min, descripcion, dbh, update)
## Insert data in main table: tagente_datos
	   
##########################################################################

# Optimizations: 
# Pass id_agent, and id_agent_module as parameters
# Separate detection of existance of tagente_modulo before calling this function
# Pass Entire hash reference with tagente_modulo record

sub pandora_writedata (%$$$$$$$$$$){
	my $pa_config = $_[0];
	my $timestamp = $_[1];
	my $nombre_agente = $_[2];
	my $tipo_modulo = $_[3];
	my $nombre_modulo = $_[4];
	my $datos = $_[5];
	my $max = $_[6];
	my $min = $_[7];
	my $descripcion = $_[8];
	my $dbh = $_[9];
	my $Ref_bUpdateDatos = $_[10];

	my @data;

	if (!defined($max)){
		$max = "0";
	}
	if (!defined($min)){
		$min = "0";
	}

	# Obtenemos los identificadores
	my $id_agente = dame_agente_id ($pa_config, $nombre_agente,$dbh);

	# Export module data if necessary
	export_module_data ($id_agente, $nombre_agente, $nombre_modulo, $tipo_modulo, $datos, $timestamp, $dbh);

	# Check if exists module and agent_module reference in DB, 
	# if not, and learn mode activated, insert module in DB
	if ($id_agente eq "-1"){
		return -1;
	}

	my $id_modulo = dame_modulo_id($pa_config, $tipo_modulo,$dbh);
	if (($id_modulo == 3) || ($id_modulo == 17) || ($id_modulo == 10) || ($id_modulo == 23)){
		if ($datos eq "") {
			return -1;
		}
	}

	my $id_agente_modulo = dame_agente_modulo_id($pa_config, $id_agente, $id_modulo, $nombre_modulo,$dbh);
	# Pandora 1.3. Now uses integer to store timestamp in datatables
	# much more faster to do comparations...
	my $utimestamp; # integer version of timestamp
	$utimestamp = &UnixDate($timestamp,"%s"); # convert from human to integer
	if (! defined($utimestamp)){ # If problems getting timestamp data
		$utimestamp = &UnixDate("today","%s");
	}
	my $needscreate = 0;

	# take max and min values for this id_agente_module
	if ($id_agente_modulo != -1){ # ID AgenteModulo does exists
		my $agent_module_row = get_db_free_row ("SELECT * FROM tagente_modulo WHERE id_agente_modulo = $id_agente_modulo", $dbh);

		# Postprocess and get MAX/MIN only for numeric moduletypes

		if (($id_modulo != 3) && ($id_modulo != 17) && ($id_modulo != 10) && ($id_modulo != 23)){
			$max = $agent_module_row->{'max'};
			$min = $agent_module_row->{'min'};

			# Postprocess
			if ((defined($agent_module_row->{'post_process'})) && ($agent_module_row->{'post_process'} != 0) && (is_numeric($agent_module_row->{'post_process'}))){
				$datos = $datos * $agent_module_row->{'post_process'};
			}
		} else {
			$max = "";
			$min = "";
		}

		# history_data detection. If this module don't have history, exit from here now with returncode 0
		
		if ($agent_module_row->{'history_data'} == 0){
                	if ( defined $Ref_bUpdateDatos ) {
	                        $$Ref_bUpdateDatos = 1; # TODO: Make check of status change here. 
	                }
			return 0;
		}

	} else { # Id AgenteModulo DOESNT exist, it could need to be created...
		if (dame_learnagente($pa_config, $id_agente, $dbh) eq "1" ){
			# Try to write a module and agent_module definition for that datablock
			logger( $pa_config, "Pandora_insertdata will create module (learnmode) for agent $nombre_agente",6);
			$id_agente_modulo = crea_agente_modulo ($pa_config, $nombre_agente, $tipo_modulo, $nombre_modulo, $max, $min, $descripcion, $dbh);
			$needscreate = 1; # Really needs to be created
		} else {
			logger( $pa_config, "VERBOSE: pandora_insertdata cannot find module definition ($nombre_modulo / $tipo_modulo )for agent $nombre_agente - Use LEARN MODE for autocreate.", 3);
			return -1;
		}
	} # Module exists or has been created
	
	# Check old value for this data in tagente_data
	# if old value nonequal to new value, needs update
	my $query;
	my $needsupdate =0;
	
	$query = "SELECT * FROM tagente_estado WHERE id_agente_modulo = $id_agente_modulo";
	my $sql_oldvalue = $dbh->prepare($query);
	$sql_oldvalue->execute;
	@data = $sql_oldvalue->fetchrow_array();
	$sql_oldvalue = $dbh->prepare($query);
	$sql_oldvalue->execute;
	if ($sql_oldvalue->rows != 0) {
		@data = $sql_oldvalue->fetchrow_array();
		#$data[2] contains data
		# Transform data (numeric types only)
		if ($tipo_modulo =~ /string/){
			$datos = $datos; # No change
		} else { # Numeric change to real
			$datos =~ s/\,/\./g; # replace "," by "."
			$data[2] =~ s/\,/\./g; # replace "," by "."
			$datos = sprintf("%.2f", $datos);
			if (is_numeric($data[2])){
				$data[2] = sprintf("%.2f", $data[2]);
			}
			if (is_numeric($datos)){
				$datos = sprintf("%.2f", $datos);
			}
			# Two decimal float. We cannot store more
			# to change this, you need to change mysql structure
		}

		# Detect changes between stored data and adquired data.
		if ($data[2] ne $datos){
			$needsupdate = 1;
		} else {
			# Data in DB is the same, but could be older (more than 1
			# day ). Should check this against last_try field, who is
			# updated only when new data is stored or each 24 hours
			my $fecha_datos = $data[7]; # last_try
			my $fecha_mysql = &UnixDate("today","%Y-%m-%d %H:%M:%S");
			my $fecha_actual = ParseDate( $fecha_mysql );
			my $fecha_flag; 
			my $err;
			my $fecha_limite = DateCalc($fecha_actual,"- 1 days",\$err);
			$fecha_flag = Date_Cmp ($fecha_limite, $fecha_datos);
			if ($fecha_flag >= 0) { # write data, 
				logger( $pa_config, "Too old data stored (>24Hr). Updating data for $nombre_modulo",5);
				$needsupdate = 1;
			}
		}
	} else {
		$needsupdate = 1; # There aren't data
		logger( $pa_config, "Updating data for $nombre_modulo, because there are not data in DB ",10);
	}

	$sql_oldvalue->finish();
	if (($needscreate == 1) || ($needsupdate == 1)){
		my $outlimit = 0;
		# Patch submitted by Dassing.
		if ( defined $Ref_bUpdateDatos ) {
			$$Ref_bUpdateDatos = 1; # true
		}
		if ($tipo_modulo =~ /string/) { # String module types
			$datos = $dbh->quote($datos);
			$timestamp = $dbh->quote($timestamp);
			# Parse data entry for adecuate SQL representation.
			$query = "INSERT INTO tagente_datos_string (id_agente_modulo, datos, utimestamp) VALUES ($id_agente_modulo, $datos, $utimestamp)";
		} elsif (is_numeric($datos)){
			if ($max != $min) {
				if (int($datos) > $max) { 
					$datos = $max; 
					$outlimit=1;
					logger($pa_config,"DEBUG: MAX Value reached ($max) for agent $nombre_agente / $nombre_modulo",6);
				}		
				if (int($datos) < $min) { 
					$datos = $min;
					$outlimit = 1;
					logger($pa_config, "DEBUG: MIN Value reached ($min) for agent $nombre_agente / $nombre_modulo",6);
				}
			}
			$datos = $dbh->quote($datos);
			$timestamp = $dbh->quote($timestamp);
			# Parse data entry for adecuate SQL representation.
			$query = "INSERT INTO tagente_datos (id_agente_modulo, datos, utimestamp) VALUES ($id_agente_modulo, $datos, $utimestamp)";
		}
		# If data is out of limits, do not insert into database
		if ($outlimit == 0){
			logger($pa_config, "DEBUG: pandora_insertdata Calculado id_agente_modulo a $id_agente_modulo",6);
			logger($pa_config, "DEBUG: pandora_insertdata SQL : $query",10);
			$dbh->do($query); # Makes insertion in database
		}
	}
	return 0;
}

##########################################################################
## SUB pandora_serverkeepalive (pa_config, status, dbh)
## Update server status
##########################################################################
sub pandora_serverkeepaliver (%$$) {
	my $pa_config= $_[0];
	my $opmode = $_[1]; # 0 dataserver, 1 network server, 2 snmp console
						# 3 recon srv, 4 plugin srv, 5 prediction srv
						# 6 WMI server
	my $dbh = $_[2];
	my $version_data;
	my $pandorasuffix;
	my @data;
	my $err;

	# First of all, update our keepalive
	pandora_updateserver ($pa_config, $pa_config->{'servername'}, 1, $opmode, $dbh);
	
	my $temp = $pa_config->{"keepalive"} - $pa_config->{"server_threshold"};

	if ($temp <= 0){
		my $timestamp = &UnixDate("today","%Y-%m-%d %H:%M:%S");
		$temp = $pa_config->{"keepalive_orig"} * 2; # Down if keepalive x 2 seconds unknown
		my $fecha_limite = DateCalc($timestamp,"- $temp seconds",\$err);
		$fecha_limite = &UnixDate($fecha_limite,"%Y-%m-%d %H:%M:%S");
		
		my $query_idag = "SELECT * FROM tserver WHERE status = 1 AND keepalive < '$fecha_limite'";
		my $s_idag = $dbh->prepare($query_idag);
		$s_idag ->execute;
		if ($s_idag->rows != 0) {
			while (@data = $s_idag->fetchrow_array()){
				if ($data[3] != 0){ # only if it's currently not down
					# Update server data
					$version_data = $pa_config->{"version"}." (P) ".$pa_config->{"build"};
					my $sql_update = "UPDATE tserver SET status = 0, version = '".$version_data."' WHERE id_server = $data[0]";
					$dbh->do($sql_update);

					pandora_event ($pa_config, "Server ".$data[1]." going Down", 0, 0, 4, 0, 0, "system", $dbh);
					logger( $pa_config, "Server ".$data[1]." going Down ", 1);
				}
			}
		}
		$s_idag->finish();
		$pa_config->{"keepalive"} = $pa_config->{"keepalive_orig"};
	} else {
		$pa_config->{"keepalive"} = $pa_config->{"keepalive"} - $pa_config->{"server_threshold"};
	}
}

##########################################################################
## SUB pandora_planned_downtime  (pa_config, dbh)
## Update planned downtimes.
##########################################################################
sub pandora_planned_downtime (%$) {
	my $pa_config= $_[0];
	my $dbh = $_[1];
	
	my $data_ref;
	my $data_ref2;
	my $query_handle;
	my $query_handle2;
	my $query_sql;
	
	my $timestamp = &UnixDate("today","%Y-%m-%d %H:%M:%S");
	my $utimestamp; # integer version of timestamp	
	$utimestamp = &UnixDate($timestamp,"%s"); # convert from 

	# Activate a planned downtime: Set agents as disabled for Planned Downtime

	$query_sql = "SELECT * FROM tplanned_downtime WHERE executed = 0 AND date_from <= $utimestamp AND date_to >= $utimestamp";

	$query_handle = $dbh->prepare($query_sql);
	$query_handle ->execute;
	if ($query_handle->rows != 0) {
		while ($data_ref = $query_handle->fetchrow_hashref()){
			# Raise event in system to notify planned downtime has started.
			$dbh->do("UPDATE tplanned_downtime SET executed=1 WHERE id = ".$data_ref->{'id'});
			pandora_event ($pa_config, "Server ".$pa_config->{'servername'}." started planned downtime: ".$data_ref->{'description'}, 0, 0, 1, 0, 0, "system", $dbh);
			$query_sql = "SELECT * FROM tplanned_downtime_agents WHERE id_downtime = ".$data_ref->{'id'};
			$query_handle2 = $dbh->prepare($query_sql);
			$query_handle2 ->execute;
			if ($query_handle2->rows != 0) {
				while ($data_ref2 = $query_handle2->fetchrow_hashref()){
				$dbh->do("UPDATE tagente SET disabled=1 WHERE id_agente = ".$data_ref2->{'id_agent'});
				}
			}
			$query_handle2->finish();
		}
	}
	$query_handle->finish();


	# Deactivate a planned downtime: Set agents as disabled for Planned Downtime

	$query_sql = "SELECT * FROM tplanned_downtime WHERE executed = 1 AND date_to <= $utimestamp";
	$query_handle = $dbh->prepare($query_sql);
	$query_handle ->execute;
	if ($query_handle->rows != 0) {
		while ($data_ref = $query_handle->fetchrow_hashref()){
			# Raise event in system to notify planned downtime has started.
			$dbh->do("UPDATE tplanned_downtime SET executed=0 WHERE id = ".$data_ref->{'id'});
			pandora_event ($pa_config, "Server ".$pa_config->{'servername'}." stopped planned downtime: ".$data_ref->{'description'}, 0, 0, 1, 0, 0, "system", $dbh);
			$query_sql = "SELECT * FROM tplanned_downtime_agents WHERE id_downtime = ".$data_ref->{'id'};
			$query_handle2 = $dbh->prepare($query_sql);
			$query_handle2 ->execute;
			if ($query_handle2->rows != 0) {
				while ($data_ref2 = $query_handle2->fetchrow_hashref()){
				$dbh->do("UPDATE tagente SET disabled=0 WHERE id_agente = ".$data_ref2->{'id_agent'});
				}
			}
			$query_handle2->finish();
		}
	}
	$query_handle->finish();
}


##########################################################################
## SUB pandora_updateserver (pa_config, status, dbh)
## Update server status
##########################################################################
sub pandora_updateserver (%$$$) {
	my $pa_config= $_[0];
	my $servername = $_[1];
	my $status = $_[2];
	my $opmode = $_[3]; # 0 dataserver, 1 network server, 2 snmp console, 3 recon
						# 4 plugin, 5 prediction, 6 wmi
	my $dbh = $_[4];


	my $sql_update;
	my $sql_update_post;
	my $pandorasuffix;
	my $version_data;

	if ($opmode == 0){
		$pandorasuffix = "_Data";
	} elsif ($opmode == 1){
		$pandorasuffix = "_Net";
	} elsif ($opmode == 2){
		$pandorasuffix = "_SNMP";
	} elsif ($opmode == 3){
		$pandorasuffix = "_Recon";
	} elsif ($opmode == 4){
		$pandorasuffix = "_Plugin";
	} elsif ($opmode == 5){
		$pandorasuffix = "_Prediction";
	} elsif ($opmode == 6){
		$pandorasuffix = "_WMI";
	} elsif ($opmode == 7){
		$pandorasuffix = "_Export";
	} elsif ($opmode == 8){
		$pandorasuffix = "_Inventory";
	} else {
		logger ($pa_config, "Error: received a unknown server type. Aborting startup.",0);
		print (" [ERROR] Received a unknown server type. Aborting startup \n\n");
		exit;
	}

	$sql_update_post = "";

	my $id_server = dame_server_id($pa_config, $servername.$pandorasuffix, $dbh);
	if ($id_server == -1){ 
		# Must create a server entry
		$version_data = $pa_config->{"version"}." (P) ".$pa_config->{"build"};
		my $sql_server = "INSERT INTO tserver (name,description,version) VALUES ('$servername".$pandorasuffix."','Autocreated at startup','$version_data')";
		$dbh->do($sql_server);
		$id_server = dame_server_id($pa_config, $pa_config->{'servername'}.$pandorasuffix, $dbh);
	}
	my @data;
	my $query_idag = "SELECT * FROM tserver WHERE id_server = $id_server";
	my $s_idag = $dbh->prepare($query_idag);
	$s_idag ->execute;
	if ($s_idag->rows != 0) {
		if (@data = $s_idag->fetchrow_array()){
			my $timestamp = &UnixDate("today","%Y-%m-%d %H:%M:%S");
			# Update server data
			$version_data = $pa_config->{"version"}." (P) ".$pa_config->{"build"};
			
			# Some fields of tserver should be updated ONLY when server is going up
			if ($data[3] == 0){ # If down, update to get up the server
				logger( $pa_config, "Server ".$data[1]." going UP ",1);
				$sql_update_post = ", laststart = '$timestamp', version = '$version_data'";
			}
			
			if ($opmode == 0){
				$sql_update = "data_server = 1";
			} elsif ($opmode == 1){
				$sql_update = "network_server = 1";
			} elsif ($opmode == 2) {
				$sql_update = "snmp_server = 1";
			} elsif ($opmode == 3) {
				$sql_update = "recon_server = 1";
			} elsif ($opmode == 4) {
				$sql_update = "plugin_server = 1";
			} elsif ($opmode == 5) {
				$sql_update = "prediction_server = 1";
			} elsif ($opmode == 6) {
				$sql_update = "wmi_server = 1";
			} elsif ($opmode == 7) {
				$sql_update = "export_server = 1";
			} elsif ($opmode == 8) {
				$sql_update = "inventory_server = 1";
			}

			$sql_update = "UPDATE tserver SET $sql_update $sql_update_post , status = $status, keepalive = '$timestamp', master =  $pa_config->{'pandora_master'} WHERE id_server = $id_server";

			$dbh->do($sql_update);
		}
		$s_idag->finish();
	}
}

##########################################################################
## SUB pandora_lastagentcontact (pa_config, timestamp,nombre_agente,os_data, agent_version,interval,dbh)
## Update last contact field in Agent Table
##########################################################################

sub pandora_lastagentcontact (%$$$$$$) {
	my $pa_config= $_[0];
	my $timestamp = $_[1];
	my $time_now = &UnixDate("today","%Y-%m-%d %H:%M:%S");
	my $nombre_agente = $_[2];
	my $os_data = $_[3];
	my $agent_version = $_[4];
	my $interval = $_[5];
	my $dbh = $_[6];

		my $id_agente = dame_agente_id($pa_config, $nombre_agente,$dbh);
		pandora_accessupdate ($pa_config, $id_agente, $dbh);
		my $query = ""; 
		if ($interval == -1){ # no update for interval field (some old agents doest support it) 
		  $query = "UPDATE tagente SET agent_version = '$agent_version', ultimo_contacto_remoto = '$timestamp', ultimo_contacto = '$time_now', os_version = '$os_data' WHERE id_agente = $id_agente";
		} else {
		  $query = "UPDATE tagente SET intervalo = $interval, agent_version = '$agent_version', ultimo_contacto_remoto = '$timestamp', ultimo_contacto = '$time_now', os_version = '$os_data' WHERE id_agente = $id_agente";
		}
		logger( $pa_config, "pandora_lastagentcontact: Updating Agent last contact data for $nombre_agente",6);
		logger( $pa_config, "pandora_lastagentcontact: SQL Query: ".$query, 10);
		db_do ($query, $dbh);
		
}

##########################################################################
## SUB update_keepalive_module
##
## Updates keepalive module from one agent. This only should be called
## when processing an agent, only one time, and not to be used on network
## or other agentless modules
##########################################################################

sub update_keepalive_module (%$) {
	my $pa_config= $_[0];
	my $id_agent = $_[1];
	my $agent_name = $_[2];
	my $dbh = $_[3];
	
	# Update keepalive module 
	# if present, if there is more than one (nosense), only updates first one!
	my $id_agent_module = get_db_free_field ("SELECT id_agente_modulo FROM tagente_modulo WHERE id_agente = $id_agent AND id_tipo_modulo = 100", $dbh);
	if ($id_agent_module ne -1){
		my $module_typename = "keep_alive";
		my $module_name = get_db_free_field ("SELECT nombre FROM tagente_modulo WHERE id_agente_modulo = $id_agent_module", $dbh);
		pandora_writestate ($pa_config, $agent_name, $module_typename, $module_name, 1, $dbh, 1);
	}
}

##########################################################################
## SUB pandora_incident (pa_config, dbh, title, text, priority, status, origin, id_group
## Write in internal incident management system
##########################################################################

sub pandora_create_incident (%$$$$$$$) {
	my $pa_config = $_[0];
	my $dbh = $_[1];
		my $title = $_[2];
		my $text = $_[3];
		my $priority = $_[4];
	my $status = $_[5];
	my $origin = $_[6];
	my $id_group = $_[7];
	my $sql = "INSERT INTO tincidencia (inicio, titulo, descripcion, origen, estado, prioridad, id_grupo) VALUES (NOW(), '$title', '$text', '$origin', $status, $priority, $id_group)";
	$dbh->do($sql);
}


##########################################################################
## SUB pandora_audit (pa_config, escription, name, action, pandora_dbcfg_hash)
## Write in internal audit system an entry.
##########################################################################
sub pandora_audit (%$$$$) {
	my $pa_config = $_[0];
		my $desc = $_[1];
		my $name = $_[2];
		my $action = $_[3];
	my $dbh = $_[4];
	my $local_dbh =0;

	# In startup audit, DBH not passed
	if (! defined($dbh)){
		$local_dbh = 1;
		$dbh = DBI->connect("DBI:mysql:$pa_config->{'dbname'}:$pa_config->{'dbhost'}:3306", $pa_config->{'dbuser'}, $pa_config->{'dbpass'}, { RaiseError => 1, AutoCommit => 1 });
	}
		my $timestamp = &UnixDate("today","%Y-%m-%d %H:%M:%S");
	my $utimestamp; # integer version of timestamp	
	$utimestamp = &UnixDate($timestamp,"%s"); # convert from human to integer

		my $query = "insert into tsesion (ID_usuario, IP_origen, accion, fecha, descripcion, utimestamp) values ('SYSTEM','".$name."','".$action."','".$timestamp."','".$desc."', $utimestamp)";
	eval { # Check for problems in Database, if cannot audit, break execution
			$dbh->do($query);
	};
	if ($@){
		logger ($pa_config,"FATAL: pandora_audit() cannot connect with database",0);
		logger ($pa_config,"FATAL: Error code $@", 0);
	}
	if ($local_dbh == 1){
		$dbh->disconnect();
	}
}

##########################################################################
## SUB dame_agente_id (nombre_agente)
## Return agent ID, use "nombre_agente" as name of agent.
##########################################################################
sub dame_agente_id (%$$) {
	my $pa_config = $_[0];
	my $agent_name = $_[1];
	my $dbh = $_[2];

	if ( (defined($agent_name)) && ($agent_name ne "") ){
		my $id_agente;
		my @data;
		$agent_name = sqlWrap ($agent_name);
		# Calculate agent ID using select by its name
		my $query_idag = "SELECT id_agente FROM tagente WHERE nombre = $agent_name OR direccion = $agent_name"; # Fixed 080108 by anon (used on snmpconsole...).
		my $s_idag = $dbh->prepare($query_idag);
		$s_idag ->execute;
		if ($s_idag->rows == 0) {
			logger ($pa_config, "ERROR dame_agente_id(): Cannot find agent called $agent_name. Returning -1", 5);
			logger ($pa_config, "ERROR: SQL Query is $query_idag ",10);
			$id_agente = -1;
		} else  {
			@data = $s_idag->fetchrow_array();
			$id_agente = $data[0];
		}
		$s_idag->finish();
		return $id_agente;
	} else {
		return -1; 
 	}
}

##########################################################################
## SUB dame_server_id (pa_config, servername, dbh)
## Return serverID, using "nane" as name of server
##########################################################################
sub dame_server_id (%$$) {
	my $pa_config = $_[0];
	my $name = $_[1];
	my $dbh = $_[2];
	
	my $id_server;
	my @data;

	# Get serverid
	my $query_idag = "SELECT * FROM tserver WHERE name = '$name' ";
	my $s_idag = $dbh->prepare($query_idag);
	$s_idag ->execute;

	if ($s_idag->rows == 0) {
		logger ($pa_config, "ERROR dame_server_id(): Cannot find server called $name. Returning -1", 5);
		logger ($pa_config, "ERROR: SQL Query is $query_idag ",10);
		$data[0]=-1;
	} else {
		@data = $s_idag->fetchrow_array();
	}

	$id_server = $data[0];
	$s_idag->finish();
	return $id_server;
}

##########################################################################
## SUB give_networkserver_status (id_server) 
## Return NETWORK server status given its id
##########################################################################

sub give_networkserver_status (%$$) {
	my $pa_config = $_[0];
	my $id_server = $_[1];
	my $dbh = $_[2];

	my $status;
	my @data;
		my $query_idag = "select * from tserver where id_server = $id_server and network_server = 1";
		my $s_idag = $dbh->prepare($query_idag);
		$s_idag ->execute;
		if ($s_idag->rows == 0) {
			$status = -1;
		} else  {
		@data = $s_idag->fetchrow_array();   
		$status = $data[3];
	}
		$s_idag->finish();
	   	return $status;
}

##########################################################################
## SUB dame_grupo_agente (id_agente) 
## Return id_group of an agent given its id
##########################################################################

sub dame_grupo_agente (%$$) {
	my $pa_config = $_[0];
	my $id_agente = $_[1];
	my $dbh = $_[2];

	my $id_grupo;
	my @data;
	# Calculate agent using select by its id
		my $query_idag = "SELECT id_grupo FROM tagente WHERE id_agente = $id_agente";
		my $s_idag = $dbh->prepare($query_idag);
		$s_idag ->execute;
		if ($s_idag->rows == 0) {
			logger ($pa_config, "ERROR dame_grupo_agente(): Cannot find agent with id $id_agente", 5);
			logger ($pa_config, "ERROR: SQL Query is $query_idag ",10);
		} else  {		   @data = $s_idag->fetchrow_array();   }
 	$id_grupo = $data[0];
		$s_idag->finish();
	   	return $id_grupo;
}

##########################################################################
## SUB dame_comando_alerta (id_alerta)
## Return agent ID, use "nombre_agente" as name of agent.
##########################################################################
sub dame_comando_alerta (%$$) {
	my $pa_config = $_[0];
		my $id_alerta = $_[1];
	my $dbh = $_[2];

	my @data;
		# Calculate agent ID using select by its name
		my $query_idag = "select * from talerta where id_alerta = $id_alerta";
		my $s_idag = $dbh->prepare($query_idag);
	my $comando = "";
		$s_idag ->execute;
		if ($s_idag->rows == 0) {
			logger ($pa_config, "ERROR dame_comando_alerta(): Cannot find alert $id_alerta", 5);
			logger ($pa_config, "ERROR: SQL Query is $query_idag ", 10);
		} else  {		   
		@data = $s_idag->fetchrow_array();   
			$comando = $data[2];
	}
		$s_idag->finish();
		return $comando;
}


##########################################################################
## SUB dame_agente_nombre (id_agente)
## Return agent name, given "id_agente"
##########################################################################
sub dame_agente_nombre (%$$) {
	my $pa_config = $_[0];
	my $id_agente = $_[1];
	my $dbh = $_[2];
		
	my $nombre_agente;
	my @data;
		# Calculate agent ID using select by its name
		my $query_idag = "SELECT nombre FROM tagente WHERE id_agente = '$id_agente'";
		my $s_idag = $dbh->prepare($query_idag);
		$s_idag ->execute;
		if ($s_idag->rows == 0) {
			logger($pa_config, "ERROR dame_agente_nombre(): Cannot find agent with id $id_agente",4);
			logger($pa_config, "ERROR: SQL Query is $query_idag ",10);
		} else  {
			@data = $s_idag->fetchrow_array();
		}
		$nombre_agente = $data[0];
		$s_idag->finish();
		return $nombre_agente;
}

##########################################################################
## SUB give_group_disabled (pa_config, id_group, dbh)
## Return disabled field from tgrupo table given a id_grupo
##########################################################################
sub give_group_disabled (%$$) {
	my $pa_config = $_[0];
		my $id_group = $_[1];
	my $dbh = $_[2];

		my $disabled = 0; 
	my @data;
		my $query_idag = "SELECT disabled FROM tgrupo WHERE id_grupo = '$id_group'";
		my $s_idag = $dbh->prepare($query_idag);
		$s_idag ->execute;
		if ($s_idag->rows == 0) {
			logger($pa_config, "ERROR give_group_disabled(): Cannot find group id $id_group",2);
			logger($pa_config, "ERROR: SQL Query is $query_idag ",10);
		} else  {	
			@data = $s_idag->fetchrow_array();
			$disabled = $data[0];
		}
		$s_idag->finish();
		return $disabled;
}

##########################################################################
## SUB dame_modulo_id (nombre_modulo)
## Return module ID, given "nombre_modulo" as module name
##########################################################################
sub dame_modulo_id (%$$) {
	my $pa_config = $_[0];
	my $nombre_modulo = $_[1];
	my $dbh = $_[2];

	my $id_modulo; my @data;
	# Calculate agent ID using select by its name
	my $query_idag = "select * from ttipo_modulo where nombre = '$nombre_modulo'";
	my $s_idag = $dbh->prepare($query_idag);
	$s_idag ->execute;
	if ($s_idag->rows == 0) {
		logger($pa_config, "ERROR dame_modulo_id(): Cannot find module called $nombre_modulo ",1);
		logger($pa_config, "ERROR: SQL Query is $query_idag ",2);
		$id_modulo = 0;
	} else  {	
		@data = $s_idag->fetchrow_array();
		$id_modulo = $data[0];
	}
	$s_idag->finish();
	return $id_modulo;
}

##########################################################################
## SUB dame_agente_modulo_id (id_agente, id_tipomodulo, nombre)
## Return agente_modulo ID, from tabla tagente_modulo,
## given id_agente, id_tipomodulo and name
##########################################################################
sub dame_agente_modulo_id (%$$$$) {
	my $pa_config = $_[0];
	my $id_agente = $_[1];
	my $id_tipomodulo = $_[2];
	my $name = $_[3];
	my $dbh = $_[4];
	my $id_agentemodulo;
	my @data;

	# Sanity checks
	if (!defined($name)){
		return -1;
	}   
	if (!defined($id_agente) || ($id_agente < 0)){
		return -1;
	}
		
		# Calculate agent ID using select by its name
		my $query_idag = "select * from tagente_modulo where id_agente = '$id_agente' and id_tipo_modulo = '$id_tipomodulo' and nombre = '$name'";
		my $s_idag = $dbh->prepare($query_idag);
		$s_idag ->execute;
		if ($s_idag->rows == 0) {
			logger ($pa_config, "ERROR dame_agente_modulo_id(): Cannot find a module called $name", 5);
			logger ($pa_config, "ERROR: SQL Query is $query_idag ", 10);
			$id_agentemodulo = -1;
		} else  {	
			@data = $s_idag->fetchrow_array(); 
			$id_agentemodulo = $data[0];
		}
		$s_idag->finish();
		return $id_agentemodulo;
}

##########################################################################
## SUB dame_nombreagente_agentemodulo (id_agente_modulo)
## Return agent name diven id_agente_modulo
##########################################################################
sub dame_nombreagente_agentemodulo (%$$) {
	my $pa_config = $_[0];
		my $id_agentemodulo = $_[1];
	my $dbh = $_[2];

		my $id_agente; my @data;
		# Calculate agent ID using select by its name
		my $query_idag = "SELECT id_agente FROM tagente_modulo WHERE id_agente_modulo = ".$id_agentemodulo;
		my $s_idag = $dbh->prepare($query_idag);
		$s_idag ->execute;
		if ($s_idag->rows == 0) {
			logger($pa_config, "ERROR dame_nombreagente_agentemodulo(): Cannot find id_agente_modulo $id_agentemodulo",3);
			logger($pa_config, "ERROR: SQL Query is $query_idag ",10);
		$id_agente = -1;
		} else  {   
		@data = $s_idag->fetchrow_array(); 
		$id_agente= $data[0];
	}
		$s_idag->finish();
		my $nombre_agente = dame_agente_nombre ($pa_config, $id_agente, $dbh);
		return $nombre_agente;
}

##########################################################################
## SUB dame_nombretipomodulo_idtipomodulo (id_tipo_modulo)
## Return name of moduletype given id_tipo_modulo
##########################################################################
sub dame_nombretipomodulo_idagentemodulo (%$$) {
	my $pa_config = $_[0];
	my $id_tipomodulo = $_[1]; 
	my $dbh = $_[2];
	my @data;
	# Calculate agent ID using select by its name
	my $query_idag = "select * from ttipo_modulo where id_tipo = ".$id_tipomodulo;
	my $s_idag = $dbh->prepare($query_idag);
	$s_idag ->execute;
	if ($s_idag->rows == 0) {
		logger( $pa_config, "ERROR dame_nombreagente_agentemodulo(): Cannot find module type with ID $id_tipomodulo",1);
		logger( $pa_config, "ERROR: SQL Query is $query_idag ",2);
	} else  {	@data = $s_idag->fetchrow_array(); }
	my $tipo = $data[1];
	$s_idag->finish();
	return $tipo;
}

##########################################################################
## SUB dame_learnagente (id_agente)
## Return 1 if agent is in learn mode, 0 if not
##########################################################################
sub dame_learnagente (%$$) {
	my $pa_config = $_[0];
		my $id_agente = $_[1];
	my $dbh = $_[2];
	my @data;
		
		# Calculate agent ID using select by its name
		my $query = "select * from tagente where id_agente = ".$id_agente;
		my $s_idag = $dbh->prepare($query);
		$s_idag ->execute;
		if ($s_idag->rows == 0) {
			logger( $pa_config, "ERROR dame_learnagente(): Cannot find agente $id_agente",2);
	  		logger( $pa_config, "ERROR: SQL Query is $query ",2);
		return 0;
		} else  {	
		@data = $s_idag->fetchrow_array();
			my $learn= $data[6];
			$s_idag->finish();
			return $learn;
	}
}


##########################################################################
## SUB dame_id_tipo_modulo (id_agente_modulo)
## Return id_tipo of module with id_agente_modulo
##########################################################################
sub dame_id_tipo_modulo (%$$) {
	my $pa_config = $_[0];
		my $id_agente_modulo = $_[1];
	my $dbh = $_[2];
		my $tipo; my @data;
		# Calculate agent ID using select by its name
		my $query_idag = "SELECT * FROM tagente_modulo WHERE id_agente_modulo = ".$id_agente_modulo;
		my $s_idag = $dbh->prepare($query_idag);
		$s_idag ->execute;
		if ($s_idag->rows == 0) {
			logger($pa_config, "ERROR dame_id_tipo_modulo(): Cannot find id_agente_modulo $id_agente_modulo", 4);
	  		logger($pa_config, "ERROR: SQL Query is $query_idag ", 10);
		$tipo = "-1";
		} else  {	
		@data = $s_idag->fetchrow_array(); 
		$tipo= $data[2];
	}
		$s_idag->finish();
		return $tipo;
}
##########################################################################
## SUB give_network_component_profile_name ($pa_config, $dbh, $task_ncprofile)
## Return network component profile name, given it's id
##########################################################################
sub give_network_component_profile_name (%$$) {
	my $pa_config = $_[0];
	my $dbh = $_[1];
		my $id_np = $_[2];
	
		my $tipo; my @data;
		my $query_idag = "SELECT * FROM tnetwork_profile WHERE id_np = ".$id_np;
		my $s_idag = $dbh->prepare($query_idag);
		$s_idag ->execute;
		if ($s_idag->rows == 0) {
			logger($pa_config, "ERROR give_network_component_profile_name(): Cannot find network profile $id_np",1);
	  		logger($pa_config, "ERROR: SQL Query is $query_idag ",2);
		$tipo = 0;
		} else  {	@data = $s_idag->fetchrow_array(); }
		$tipo = $data[1];
		$s_idag->finish();
		return $tipo;
}

##########################################################################
## SUB dame_intervalo (id_agente)
## Return interval for id_agente
##########################################################################
sub dame_intervalo (%$$) {
	my $pa_config = $_[0];
	my $id_agente = $_[1];
	my $dbh = $_[2];

	my $tipo = 0; 
	my @data;
	# Calculate agent ID using select by its name
	my $query_idag = "select * from tagente where id_agente = ".$id_agente;
	my $s_idag = $dbh->prepare($query_idag);
	$s_idag ->execute;
	if ($s_idag->rows == 0) {
		logger($pa_config, "ERROR dame_intervalo(): Cannot find agente $id_agente",1);
		logger($pa_config, "ERROR: SQL Query is $query_idag ",2);
		$tipo = 0;
	} else  {	
		@data = $s_idag->fetchrow_array(); 
	}
	$tipo= $data[7];
	$s_idag->finish();
	return $tipo;
}

##########################################################################
## SUB dame_desactivado (id_agente)
## Return disabled = 1 if disabled, 0 if not disabled
##########################################################################
sub dame_desactivado (%$$) {
	my $pa_config = $_[0];
		my $id_agente = $_[1];
	my $dbh = $_[2];
	my $desactivado;

		my $tipo; my @data;
		# Calculate agent ID using select by its name
		my $query_idag = "select * from tagente where id_agente = ".$id_agente;
	my $s_idag = $dbh->prepare($query_idag);
		$s_idag ->execute;
		if ($s_idag->rows == 0) {
			logger($pa_config, "ERROR dame_desactivado(): Cannot find agente $id_agente",4);
	  	 	logger($pa_config, "ERROR: SQL Query is $query_idag ",10);
		$desactivado = -1;
		} else  {	
		@data = $s_idag->fetchrow_array(); 
		$desactivado= $data[12];
		}

		$s_idag->finish();
		return $desactivado;
}

##########################################################################
## SUB dame_ultimo_contacto (id_agente)
## Return last_contact for id_agente
##########################################################################
sub dame_ultimo_contacto (%$$) {
	my $pa_config = $_[0];
		my $id_agente = $_[1];
	my $dbh = $_[2];

		my $tipo; my @data;
		# Calculate agent ID using select by its name
		my $query_idag = "select * from tagente where id_agente = ".$id_agente;
		my $s_idag = $dbh->prepare($query_idag);
		$s_idag ->execute;
		if ($s_idag->rows == 0) {
			logger($pa_config, "ERROR dame_ultimo_contacto(): Cannot find agente $id_agente", 2);
	  	 	logger($pa_config, "ERROR: SQL Query is $query_idag ", 10);
		} else  {	@data = $s_idag->fetchrow_array(); }
		$tipo= $data[5];
		$s_idag->finish();
		return $tipo;
}

##########################################################################
## SUB crea_agente_modulo(nombre_agente, nombre_tipo_modulo, nombre_modulo)
## create an entry in tagente_modulo, return id of created tagente_modulo
##########################################################################
sub crea_agente_modulo (%$$$$$$$) {
	my $pa_config = $_[0];
	my $nombre_agente = $_[1];
	my $tipo_modulo = $_[2];
	my $nombre_modulo = $_[3];
	my $max = $_[4];
	my $min = $_[5];
	my $descripcion = $_[6];
	my $dbh = $_[7];

	# Sanity checks
	if (!defined($nombre_modulo)){
	logger($pa_config, "ERROR crea_agente_modulo(): Undefined module name", 2);
		return -1;
	}   
   
	my $modulo_id = dame_modulo_id ($pa_config, $tipo_modulo, $dbh);
	my $agente_id = dame_agente_id ($pa_config, $nombre_agente, $dbh);
	if (!defined($agente_id) || ($agente_id < 0)){
		return -1;
	}   
	if ((!defined($max)) || ($max eq "")){
		$max = 0;
	}
	if ((!defined($min)) || ($min eq "")){
		$min = 0;
	}
	if ((!defined($descripcion)) || ($descripcion eq "")){
		$descripcion = "N/A";
	}
	$descripcion = sqlWrap ($descripcion. "(*)" );
	$max = sqlWrap ($max);
	$min = sqlWrap ($min);
	$nombre_modulo = sqlWrap ($nombre_modulo);

	my $query = "INSERT INTO tagente_modulo (id_agente,id_tipo_modulo,nombre,max,min,descripcion, id_modulo) VALUES ($agente_id, $modulo_id, $nombre_modulo, $max, $min, $descripcion, 1)";
	if (($max eq "") and ($min eq "")) {
		$query = "INSERT INTO tagente_modulo (id_agente,id_tipo_modulo,nombre,descripcion, id_modulo) VALUES ($agente_id, $modulo_id, $nombre_modulo, $descripcion, 1)";
	} elsif ($min eq "") {
		$query = "INSERT INTO tagente_modulo (id_agente,id_tipo_modulo,nombre,max,descripcion, id_modulo) VALUES ($agente_id, $modulo_id, $nombre_modulo, $max, $descripcion, 1)";
	} elsif ($min eq "") {
		$query = "INSERT INTO tagente_modulo (id_agente,id_tipo_modulo,nombre,min,descripcion, id_modulo) VALUES 	($agente_id, $modulo_id, $nombre_modulo, $min, $descripcion, 1)";
	}
	$dbh->do($query);
	return $dbh->{'mysql_insertid'};
}

# ---------------------------------------------------------------
# Generic access to a field ($field) given a table
# give_db_value (field_name_to_be_returned, table, field_search, condition_value, dbh)
# ---------------------------------------------------------------
sub get_db_value ($$$$$) {
	my $field = $_[0];
	my $table = $_[1];
	my $field_search = $_[2];
	my $condition_value= $_[3];
	my $dbh = $_[4];
	
	my $query = "SELECT $field FROM $table WHERE $field_search = '$condition_value' ";
	my $s_idag = $dbh->prepare($query);
	$s_idag ->execute;
	if ($s_idag->rows != 0) {
		my @data = $s_idag->fetchrow_array();
		my $result = $data[0];
		$s_idag->finish();
		return $result;
	}
	return -1;
}

# ---------------------------------------------------------------
# Free SQL sentence. Return first field on exit
# ---------------------------------------------------------------

sub get_db_free_field ($$) {
		my $condition = $_[0];
		my $dbh = $_[1];
		my $s_idag = $dbh->prepare($condition);
		$s_idag ->execute;
		if ($s_idag->rows != 0) {
				my @data = $s_idag->fetchrow_array();
				my $result = $data[0];
				$s_idag->finish();
				return $result;
		}
		return -1;
}



# ---------------------------------------------------------------
# Free SQL sentence. Return entire hash in row
# ---------------------------------------------------------------

sub get_db_free_row ($$) {
		my $condition = $_[0];
		my $dbh = $_[1];
		my $rowref;

		my $query = $condition;
		my $s_idag = $dbh->prepare($query);
		$s_idag ->execute;
		if ($s_idag->rows != 0) {
				$rowref = $s_idag->fetchrow_hashref;
				$s_idag->finish();
				return $rowref;
		}
		return -1;
}

# ---------------------------------------------------------------
# Free SQL sentence. Return all rows as a hash array.
# ---------------------------------------------------------------

sub get_db_all_rows ($$) {
		my $condition = $_[0];
		my $dbh = $_[1];
		my @result;

		my $s_idag = $dbh->prepare($condition);
		if (!$s_idag->execute) {
			return @result;
		}

		if ($s_idag->rows == 0) {
			return @result;
		}

		while (my $row = $s_idag->fetchrow_hashref()) {
			push (@result, $row);
		}
		$s_idag->finish();
		return @result;
}

# ---------------------------------------------------------------
# Insert SQL sentence. Returns ID of row inserted
# ---------------------------------------------------------------

sub db_insert ($$) {
	my $query= $_[0];
	my $dbh = $_[1];
	
	$dbh->do($query);
	return $dbh->{'mysql_insertid'};
}

# ---------------------------------------------------------------
# Generic SQL sentence. 
# ---------------------------------------------------------------

sub db_do ($$) {
	my $query= $_[0];
	my $dbh = $_[1];
	
	$dbh->do($query);
}

##########################################################################
# SUB pandora_create_agent (pa_config, dbh, target_ip, target_ip_id,
#				 id_group, network_server_assigned, name, id_os)
# Create agent, and associate address to agent in taddress_agent table.
# it returns created id_agent.
##########################################################################
sub pandora_create_agent {  
	my $pa_config = $_[0];
	my $dbh = $_[1];
	my $target_ip = $_[2];
	my $target_ip_id = $_[3];
	my $id_group = $_[4];
	my $id_server= $_[5];
	my $name = $_[6];
	my $id_parent = $_[7];
	my $id_os = $_[8];

	my $prediction;
	my $wmi;
	my $plugin;

	if ((!is_numeric($id_server)) || ($id_server == 0)){
		$id_server = get_db_free_field ("SELECT id_server FROM tserver WHERE network_server = 1 AND master = 1 LIMIT 1", $dbh);
	}
	
	$prediction = get_db_free_field ("SELECT id_server FROM tserver WHERE prediction_server = 1 AND master = 1 LIMIT 1", $dbh);
	$wmi = get_db_free_field ("SELECT id_server FROM tserver WHERE wmi_server = 1 AND master = 1 LIMIT 1", $dbh);
	$plugin = get_db_free_field ("SELECT id_server FROM tserver WHERE plugin_server = 1 AND master = 1 LIMIT 1", $dbh);

	if ($wmi < 0){
		$wmi = 0;
	}

	if ($plugin < 0){
		$plugin = 0;
	}

	if ($prediction < 0){
		$prediction = 0;
	}

	if ($id_server < 0){
		$id_server = 0;
	}

	my $server = $pa_config->{'servername'}.$pa_config->{"servermode"};
	logger ($pa_config,"$server: Creating agent $name $target_ip ", 1);

	my $query_sql2 = "INSERT INTO tagente (nombre, direccion, comentarios, id_grupo, id_os, id_network_server, intervalo, id_parent, modo, id_prediction_server, id_wmi_server, id_plugin_server) VALUES  ('$name', '$target_ip', 'Created by $server', $id_group, $id_os, $id_server, 300, $id_parent, 1, $prediction, $wmi, $plugin)";

	$dbh->do ($query_sql2);

	my $lastid = $dbh->{'mysql_insertid'};

	pandora_event ($pa_config, "Agent '$name' created by ".$pa_config->{'servername'}.$pa_config->{"servermode"}, $pa_config->{'autocreate_group'}, $lastid, 2, 0, 0, 'new_agent', $dbh);

	if ($target_ip_id > 0){
		my $query_sql3 = "INSERT INTO taddress_agent (id_a, id_agent) values ($target_ip_id, $lastid)";
		$dbh->do($query_sql3);
	}
	return $lastid;
}

##########################################################################
## SUB pandora_event 
## Write in internal audit system an entry.
## Params: config_hash, event_title, group, agent_id, severity, id_alertam
##		 id_agentmodule, event_type (from a set, as string), db_handle
##########################################################################

sub pandora_event (%$$$$$$$$) {
	my $pa_config = $_[0];
	my $evento = $_[1];
	my $id_grupo = $_[2];
	my $id_agente = $_[3];
	my $severity = $_[4]; # new in 2.0
	my $id_alert_am = $_[5]; # new in 2.0
	my $id_agentmodule = $_[6]; # new in 2.0
	my $event_type = $_[7]; # new in 2.0
	my $dbh = $_[8];
	my $timestamp = &UnixDate("today","%Y-%m-%d %H:%M:%S");
	my $utimestamp; # integer version of timestamp	

	$utimestamp = &UnixDate($timestamp,"%s"); # convert from human to integer
	$evento = $dbh->quote($evento);
	$event_type = $dbh->quote($event_type);
	$timestamp = $dbh->quote($timestamp);
	my $query = "INSERT INTO tevento (id_agente, id_grupo, evento, timestamp, estado, utimestamp, event_type, id_agentmodule, id_alert_am, criticity) VALUES ($id_agente, $id_grupo, $evento, $timestamp, 0, $utimestamp, $event_type, $id_agentmodule, $id_alert_am, $severity)";
	$dbh->do($query);
}

##########################################################################
## SUB export_module_data ()
## ($id_agent, $module, $data, $timestamp, $dbh)
## Process module data according to the module type.
##########################################################################

sub export_module_data {
	my $id_agent = $_[0];
	my $agent_name = $_[1];
	my $module_name = $_[2];
	my $module_type = $_[3];
	my $data = $_[4];
	my $timestamp = $_[5];
	my $dbh = $_[6];

	my $tagente_modulo = get_db_free_row ("SELECT id_export, id_agente_modulo
										   FROM tagente_modulo WHERE id_agente = " . $id_agent .
										   " AND nombre = '" . $module_name . "'", $dbh);
	if ($tagente_modulo eq '-1') {
		return;
	}

 	my $id_export = $tagente_modulo->{'id_export'};
	my $id_agente_modulo = $tagente_modulo->{'id_agente_modulo'};
	if ($id_export < 1) {
		return;
	}

	$dbh->do("INSERT INTO tserver_export_data (`id_export_server`, `agent_name` ,
			 `module_name`, `module_type`, `data`, `timestamp`)
			 VALUES ($id_export, '$agent_name', '$module_name', '$module_type',
			 '$data', '$timestamp')");
}


##########################################################################
# SUB update_on_error (pa_config, id_agent_module, dbh )
# Modules who cannot connect or something go bad, update last_execution_try field
##########################################################################
sub update_on_error {
        my $pa_config = $_[0];
        my $id_agent_module = $_[1];
        my $dbh = $_[2];

        my $utimestamp = &UnixDate("today","%s");

        # Modules who cannot connect or something go bad, update last_execution_try field
        logger ($pa_config, "Cannot obtain Module from IdAgentModule $id_agent_module", 3);
        db_do ("UPDATE tagente_estado 
		SET current_interval = 300, last_execution_try = $utimestamp 
		WHERE id_agente_modulo = $id_agent_module", $dbh);
;

}

##########################################################################
## SUB calcula_alerta_snmp($source,$oid,$custom_value,$timestamp);
## Given an SNMP Trap received with this data, execute Alert or not
##########################################################################

sub calcula_alerta_snmp {
	# Parameters passed as arguments
	my $pa_config = $_[0];
	my $trap_agente = $_[1];
	my $trap_oid = $_[2];
	my $trap_oid_text = $_[3];
	my $trap_custom_value = $_[4];
	my $timestamp = $_[5];
	my $dbh = $_[6];
	my $alert_fired = 0;
	
	my $s_idag = $dbh->prepare("SELECT * FROM talert_snmp");
	$s_idag ->execute;
	my @data;
	# Read all alerts and apply to this incoming trap 
	if ($s_idag->rows != 0) {
		while (@data = $s_idag->fetchrow_array()) {
			$alert_fired = 0;		
			my $id_as 			= $data[0];
			my $id_alert 		= $data[1];
			my $field1 			= $data[2];
			my $field2 			= $data[3];
			my $field3 			= $data[4];
			my $description 	= $data[5];
			my $alert_type 		= $data[6];
			my $agent 			= $data[7];
			my $custom_oid 		= $data[8];
			my $oid 			= $data[9];
			my $time_threshold 	= $data[10];
			my $times_fired 	= $data[11];
			my $last_fired 		= $data[12]; # The real fired alarms
			my $max_alerts 		= $data[13];
			my $min_alerts 		= $data[14]; # The real triggered alarms (not really fired, only triggered)
			my $internal_counter = $data[15];
			my $alert_priority = $data[16];

			my $alert_data = "";
				
			if ($alert_type == 0){ # type 0 is OID only
				if ( $trap_oid =~ m/$oid/i || $trap_oid_text =~ m/$oid/i){
					$alert_fired = 1;
					$alert_data = "SNMP/OID:".$oid;
					logger ($pa_config,"SNMP Alert debug (OID) MATCHED",10);
				}
			} elsif ($alert_type == 1){ # type 1 is custom value 
				logger ($pa_config,"SNMP Alert debug (Custom) $custom_oid / $trap_custom_value",10);
				if ( $trap_custom_value =~ m/$custom_oid/i ){
					$alert_fired = 1;
					$alert_data = "SNMP/VALUE:".$custom_oid;
					logger ($pa_config,"SNMP Alert debug (Custom) MATCHED",10);
				}
			} else { # type 2 is agent IP
				if ($trap_agente =~ m/$agent/i ){
					$alert_fired = 1;
					$alert_data = "SNMP/SOURCE:".$agent;
					logger ($pa_config,"SNMP Alert debug (SOURCE) MATCHED",10);
				} 
			}

			if ($alert_fired == 1){ # Exists condition to fire alarm.
				# Verify if under time_threshold
				my $fecha_ultima_alerta = ParseDate($last_fired);
				my $fecha_actual = ParseDate( $timestamp );
				my $ahora_mysql = &UnixDate("today","%Y-%m-%d %H:%M:%S"); # If we need to update MYSQL last_fired will use $ahora_mysql
				my $err; my $flag;
				my $fecha_limite = DateCalc($fecha_ultima_alerta,"+ $time_threshold seconds",\$err);
				# verify if upper min alerts
				# Verify if under min alerts
				$flag = Date_Cmp($fecha_actual,$fecha_limite);
				if ( $flag >= 0 ) { # Out limits !, reset $times_fired, but do not write to
						    # database until a real alarm was fired
					$times_fired = 0;
					$internal_counter=0;
					logger ($pa_config,"SNMP Alarm out of timethreshold limits",10);
				}
				# We are between limits marked by time_threshold or running a new time-alarm-interval 
				# Caution: MIN Limit is related to triggered (in time-threshold limit) alerts
				# but MAX limit is related to executed alerts, not only triggered. Because an alarm to be
				# executed could be triggered X (min value) times to be executed.
				if (($internal_counter+1 >= $min_alerts) && ($times_fired+1 <= $max_alerts)){
					# The new alert is between last valid time + threshold and between max/min limit to alerts in this gap of time.
					$times_fired++;
					$internal_counter++;
					logger($pa_config,"Executing SNMP Trap alert for $agent - $alert_data",2);

                    # Create a hash for passing to execute_alert
                    my %data_alert = (
                    	'name' => '',
                    	'id_agent_module' => 0,
                    	'id_template_module' => 0,
                    	'field1' => $field1,
                    	'field2' => $field2,
                    	'field3' => $field3,
                    	'description' => $description,
						'times_fired' => $times_fired,
						'time_threshold' => 0,
                    	'id_alert_action' => $id_alert,
                    	'priority' => $alert_priority,
					);

                    # Execute alert
					execute_alert ($pa_config, \%data_alert, 0, 0, $agent, $trap_agente, 1, 0, $dbh);

					# Now update the new value for times_fired, alert_fired, internal_counter and last_fired for this alert.
					my $query_idag2 = "update talert_snmp set times_fired = $times_fired, last_fired = '$ahora_mysql', internal_counter = $internal_counter where id_as = $id_as ";
					$dbh->do($query_idag2);

					# Now find record for trap and update "fired" status... 
					# Due DBI doesnt return ID of a new inserted item, we now need to find ourselves 
					# this is a crap :(

					my $query_idag3 = "update ttrap set alerted = 1, priority = $alert_priority where timestamp = '$timestamp' and source = '$trap_agente'";
					$dbh->do($query_idag3);

				} else { # Alert is in valid timegap but has too many alerts or too many little
					$internal_counter++;
					if ($internal_counter < $min_alerts){
						# Now update the new value for times_fired & last_fired if we are below min limit for triggering this alert
						my $query_idag = "update talert_snmp set internal_counter = $internal_counter, times_fired = $times_fired, last_fired = '$ahora_mysql' where id_as = $id_as ";
						$dbh->do($query_idag);
						logger ($pa_config, "SNMP Alarm not fired because is below min limit",8);
					} else { # Too many alerts fired (upper limit)
						my $query_idag = "update talert_snmp set times_fired=$times_fired, internal_counter = $internal_counter where id_as = $id_as ";
						$dbh->do($query_idag);
						logger ($pa_config, "SNMP Alarm not fired because is above max limit",8);
					}
				}
			}
		} # While
	} # if
	$s_idag->finish();
}

# End of function declaration
# End of defined Code

1;
__END__
		# Look updated servers and take down non updated servers
