package PandoraFMS::DB;
##########################################################################
# Database Package
# Pandora FMS. the Flexible Monitoring System. http://www.pandorafms.org
##########################################################################
# Copyright (c) 2004-2008 Sancho Lerena, slerena@gmail.com
# Copyright (c) 2005-2008 Artica Soluciones Tecnologicas S.L
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; version 2
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
##########################################################################

use warnings;
use Time::Local;
use Time::Format qw(%time %strftime %manip); # For data mangling
use DBI;
use Date::Manip;# Needed to manipulate DateTime formats of input, output and compare
use XML::Simple;
use HTML::Entities;

use POSIX qw(strtod);

use PandoraFMS::Tools;

require Exporter;

our @ISA = ("Exporter");
our %EXPORT_TAGS = ( 'all' => [ qw( ) ] );
our @EXPORT_OK = ( @{ $EXPORT_TAGS{'all'} } );
our @EXPORT = qw( 	
		crea_agente_modulo			
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
		pandora_updateserver
		pandora_serverkeepaliver
		pandora_audit
		pandora_lastagentcontact
		pandora_writedata
		pandora_writestate
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
		get_db_value
		get_db_free_row
		get_db_free_field
	);

# Spanish translation note:
# 'Crea' in spanish means 'create'
# 'Dame' in spanish means 'give'

##########################################################################
## SUB pandora_generate_alerts
## (paconfig, timestamp, agent_name, $id_agent, id_agent_module,
## id_module_type, id_group, module_data, module_type, dbh)
## Generate alerts for a given module.
##########################################################################

sub pandora_generate_alerts (%$$$$$$$$) {
	my $pa_config = $_[0];
	my $timestamp = $_[1];
	my $agent_name = $_[2];
	my $id_agent = $_[3];
	my $id_agent_module = $_[4];
	my $id_module_type = $_[5];
	my $id_group = $_[6];
	my $module_data = $_[7];
	my $dbh = $_[8];

	# Do not generate alerts for disabled groups
	if (give_group_disabled ($pa_config, $id_group, $dbh) == 1) {
		return;
	}

	# Get enabled alerts associated with this module
	my $query_alert = "SELECT * FROM talerta_agente_modulo WHERE
					   id_agente_modulo = '$id_agent_module' AND disable = 0";
	my $handle_alert = $dbh->prepare($query_alert);

	$handle_alert->execute;
	if ($handle_alert->rows == 0) {
		return;
	}

	while (my $alert_data = $handle_alert->fetchrow_hashref()) {

		my $rc = pandora_evaluate_alert($pa_config, $timestamp, $alert_data,
										$module_data, $id_module_type, $dbh);
		pandora_process_alert ($pa_config, $timestamp, $rc, $agent_name,
							   $id_agent, $id_group, $alert_data, $module_data,
							   $dbh);

		# Evaluate compound alerts even if the alert status did not change in
		# case the compound alert does not recover
		pandora_generate_compound_alerts ($pa_config, $timestamp,
										  $agent_name, $id_agent,
										  $alert_data->{'id_aam'},
										  $id_group, 0, $dbh);
	}

	$handle_alert->finish();
}

##########################################################################
## SUB pandora_evaluate_alert
## (paconfig, timestamp, alert_data, module_data, id_module_type, dbh)
## Evaluate trigger conditions for a given alert. Returns:
##  0 Execute the alert.
##  1 Do not execute the alert.
##  2 Do not execute the alert, but increment its internal counter.
##  3 Cease the alert.
##  4 Recover the alert.
##########################################################################

sub pandora_evaluate_alert (%$%$$$) {
	my $pa_config = $_[0];
	my $timestamp = $_[1];
	my $alert_data = $_[2];
	my $module_data = $_[3];
	my $id_module_type = $_[4];
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
	my $last_fired_date = ParseDate($alert_data->{'last_fired'});
	my $limit_date = DateCalc ($last_fired_date, "+ " .
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
	}

	# Check for valid data
	if ($id_module_type == 3 ||
		$id_module_type == 10 ||
		$id_module_type == 17) {
		if ($module_data !~ m/$alert_data->{'alert_text'}/i) {
			return $status;
		}
	}
	elsif ($id_module_type == -1) {
		if (pandora_evaluate_compound_alert($pa_config,
											 $alert_data->{'id_aam'},
											 $dbh) == 0) {
			return $status
		}
	}
	else {
		if ($module_data <= $alert_data->{'dis_max'} &&
			$module_data >= $alert_data->{'dis_min'}) {
				return $status;
		}
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
	my $dbh = $_[8];

	# Do not execute
	if ($rc == 1) {
		return;
	}

	# Cease
	if ($rc == 3) {

		# Update alert status
		$dbh->do("UPDATE talerta_agente_modulo SET times_fired = 0,
				 internal_counter = 0 WHERE id_aam = " .
				 $alert_data->{'id_aam'});

		# Generate an event
		pandora_event ($pa_config, "Alert ceased (" .
					   $alert_data->{'descripcion'} . ")", $id_group,
					   $id_agent, $alert_data->{'priority'}, $alert_data->{'id_aam'}, $alert_data->{'id_agente_modulo'}, 
					   "alert_recovered", $dbh);
		return;
	}

	# Recover
	if ($rc == 4) {

		# Update alert status
		$dbh->do("UPDATE talerta_agente_modulo SET times_fired = 0,
				 internal_counter = 0 WHERE id_aam = " .
				 $alert_data->{'id_aam'});

		execute_alert ($pa_config, $alert_data, $id_agent, $id_group, $agent_name,
					   $module_data, 0, $dbh);
		return;
	}

	# Increment internal counter
	if ($rc == 2) {

		# Update alert status
		$alert_data->{'internal_counter'} += 1;

		# Do not increment times_fired, but set it in case the alert was reset
		$dbh->do("UPDATE talerta_agente_modulo SET times_fired = " .
				 $alert_data->{'times_fired'} . ", internal_counter = " .
				 $alert_data->{'internal_counter'} . " WHERE id_aam = " .
				 $alert_data->{'id_aam'});
		return;
	}

	# Execute
	if ($rc == 0) {

		# Get current date
		my $date_db = &UnixDate("today","%Y-%m-%d %H:%M:%S");

		# Update alert status

		$alert_data->{'times_fired'} += 1;
		$alert_data->{'internal_counter'} += 1;
		$dbh->do("UPDATE talerta_agente_modulo SET times_fired = " .
				 $alert_data->{'times_fired'} . ", last_fired =
				 '$date_db', internal_counter = " .
				 $alert_data->{'internal_counter'} . " WHERE id_aam = " .
				 $alert_data->{'id_aam'});

		execute_alert ($pa_config, $alert_data, $id_agent, $id_group, $agent_name, 
						$module_data, 1, $dbh);
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
	my $query_compound = "SELECT id_aam, operation FROM tcompound_alert
						  WHERE id = '$id' ORDER BY operation";
	my $handle_compound = $dbh->prepare($query_compound);
	$handle_compound ->execute;

	if ($handle_compound->rows == 0) {
		return 0;
	}

	my $query_alert = "SELECT disable, times_fired FROM
					  talerta_agente_modulo WHERE id_aam = ? AND disable = 0";
	my $handle_alert = $dbh->prepare($query_alert);

	while (my $data_compound = $handle_compound->fetchrow_hashref()) {

		# Get alert data if enabled
		$handle_alert->execute($data_compound->{'id_aam'});
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
	my $id_alert_agent_module = $_[4];
	my $id_group = $_[5];
	my $depth = $_[6];
	my $dbh = $_[7];

	# Get all compound alerts that depend on this alert
	my $query_compound = "SELECT id FROM tcompound_alert WHERE id_aam = '" .
						 $id_alert_agent_module . "'";

	my $handle_compound = $dbh->prepare($query_compound);

	$handle_compound->execute;

	if ($handle_compound->rows == 0) {
		$handle_compound->finish();
		return;
	}

	my $query_alert = "SELECT * FROM talerta_agente_modulo WHERE id_aam = ?";
	my $handle_alert = $dbh->prepare($query_alert);

	while (my $data_compound = $handle_compound->fetchrow_hashref()) {

		# Get compound alert parameters
		$handle_alert->execute($data_compound->{'id'});
		if ($handle_alert->rows == 0) {
			next;
		}

		my $data_alert = $handle_alert->fetchrow_hashref();

		# Evaluate the alert
		my $rc = pandora_evaluate_alert($pa_config, $timestamp, $data_alert,
										'', -1, $dbh);

		pandora_process_alert ($pa_config, $timestamp, $rc, $agent_name, $id_agent,
							   $id_group, $data_alert, '', $dbh);

		# Evaluate nested compound alerts
		if ($depth >= $pa_config->{'compound_max_depth'}) {
			logger($pa_config, "ERROR: Error in SUB pandora_generate_compound_
								alerts(): Maximum nested compound alert depth
								reached.", 2);
			next;
		}

		&pandora_generate_compound_alerts ($pa_config, $timestamp, $agent_name,
										   $id_agent, $data_compound->{'id'},
										   $id_group, $depth + 1, $dbh);
	}

	$handle_alert->finish();
	$handle_compound->finish();
}

##########################################################################
## SUB execute_alert 
## Do a execution of given alert with this parameters
##########################################################################

sub execute_alert (%$$$$$$$$$$$$$$$) {
	my $pa_config = $_[0];
	my $data_alert = $_[1];
	my $id_agent = $_[2];
	my $id_group = $_[3];
	my $agent = $_[4];
	my $data = $_[5];
	my $alert_mode = $_[6]; # 0 is recovery, 1 is normal
	my $dbh = $_[7];

	# Some variable init

	my $create_event = 1;
	my $command = "";
	my $alert_name = "";
	my $field1;
	my $field2;
	my $field3;
	my $id_alert = $data_alert->{'id_alerta'};
	my $id_agent_module = $data_alert->{'id_agente_modulo'};
	my $timestamp = &UnixDate ("today", "%Y-%m-%d %H:%M:%S"); # string timestamp
	my $alert_description = $data_alert->{'descripcion'};
	
	# Compound only
	if ($id_alert == 1){
		return;
	}
	
	if ($alert_mode == 1){
		$field1 = $data_alert->{'al_campo1'};
		$field2 = $data_alert->{'al_campo2'};
		$field3 = $data_alert->{'al_campo3'};
	} else {
		$field1 = $data_alert->{'al_campo1'};
		# Patch for adding [RECOVER] on f2/f3 if blank. Submitted by Kato Atsushi
		$field2 = $data_alert->{'al_f2_recovery'} || "[RECOVER]" . $data_alert->{'al_campo2'};
		$field3 = $data_alert->{'al_f3_recovery'} || "[RECOVER]" . $data_alert->{'al_campo3'};
		# End of patch
	}

	# Get values for commandline, reading from talerta.
	my $query_idag = "SELECT * FROM talerta WHERE id_alerta = '$id_alert'";
	my $idag = $dbh->prepare($query_idag);
	$idag ->execute;
	my @datarow;
	if ($idag->rows != 0) {
		while (@datarow = $idag->fetchrow_array()) {
			$command = $datarow[2];
			$alert_name = $datarow[1];
		}
	}
	$idag->finish();
	
	logger($pa_config, "Alert ($alert_name) TRIGGERED for $agent",2);
	if ($id_alert > 4) { # Skip internal alerts
		$command =~ s/_field1_/"$field1"/ig;
		$command =~ s/_field2_/"$field2"/ig;
		$command =~ s/_field3_/"$field3"/ig;
		$command =~ s/_agent_/$agent/ig;
		$command =~ s/_timestamp_/$timestamp/ig;
		$command =~ s/_data_/$data/ig;
		# Clean up some "tricky" characters
		$command = decode_entities($command);
		# EXECUTING COMMAND !!!
		eval {
			my $exit_value = system ($command);
			$exit_value  = $? >> 8; # Shift 8 bits to get a "classic" errorlevel
			if ($exit_value != 0) {
				logger($pa_config, "Executed command for triggered alert '$alert_name' had errors (errorlevel =! 0) ",1);
				logger($pa_config, "Executed command was $command ",5);
			}
		};
		if ($@){
			logger($pa_config, "WARNING: Alert command don't return from execution. ( $command )", 0 );
			logger($pa_config, "ERROR Code: $@",2);
		}
	} elsif ($id_alert == 3) { # id_alerta = 3, is a internal system audit
		logger($pa_config, "Internal audit lauch for agent name $agent",3);
		$field1 =~ s/_agent_/$agent/ig;
		$field1 =~ s/_timestamp_/$timestamp/ig;
		$field1 =~ s/_data_/$data/ig;
		pandora_audit ($pa_config, $field1, $agent, "Alert ($alert_description)", $dbh);
		$create_event = 0;
	} elsif ($id_alert == 2) { # email

		$field2 =~ s/_agent_/$agent/ig;
		$field2 =~ s/_timestamp_/$timestamp/ig;
		$field2 =~ s/_data_/$data/ig;

		$field3 =~ s/_agent_/$agent/ig;
		$field3 =~ s/_timestamp_/$timestamp/ig;
		$field3 =~ s/_data_/$data/ig;

		pandora_sendmail ( $pa_config, $field1, $field2, $field3);
	} elsif ($id_alert == 4) { # internal event
		$create_event = 1;
	}

	if ($create_event == 1){
		my $evt_descripcion = "Alert fired ($alert_description)";
		if ($alert_mode == 0){ # recovery
			pandora_event ($pa_config, $evt_descripcion, $id_group, $id_agent, $data_alert->{'priority'}, $data_alert->{'id_aam'}, 
			$data_alert->{'id_agente_modulo'}, 'alert_recovered',  $dbh);
		} else {
			pandora_event ($pa_config, $evt_descripcion, $id_group, $id_agent, $data_alert->{'priority'}, $data_alert->{'id_aam'}, 
			$data_alert->{'id_agente_modulo'}, 'alert_fired',  $dbh);
		}
	}
}


##########################################################################
## SUB pandora_writestate (pa_config, nombre_agente,tipo_modulo,
#							nombre_modulo,valor_datos, estado, dbh, needupdate)
## Alter data, chaning status of modules in state table
##########################################################################

sub pandora_writestate (%$$$$$$$) {
	# slerena, 05/10/04 : Fixed bug because differences between agent / server time source.
	# now we use only local timestamp to stamp state of modules
	my $pa_config = $_[0];
	my $nombre_agente = $_[1];
	my $tipo_modulo = $_[2]; # passed as string
	my $nombre_modulo = $_[3];
	my $datos = $_[4]; # Careful: This don't reference a hash, only a single value
	my $estado = $_[5];
	my $dbh = $_[6];
	my $needs_update = $_[7];
	
	my @data;
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
	my $id_agente = dame_agente_id ($pa_config, $nombre_agente, $dbh);
	my $id_modulo = dame_modulo_id ($pa_config, $tipo_modulo, $dbh);
	my $id_agente_modulo = dame_agente_modulo_id($pa_config, $id_agente, $id_modulo, $nombre_modulo, $dbh);

	# Valid agent ?
	if (($id_agente ==  -1) || ($id_agente_modulo == -1)) {
		return -1;
	}

	# Valid string data ? (not null)
	if (($id_modulo == 3) || ($id_modulo == 17) || ($id_modulo == 10) || ($id_modulo == 23)){
			if ($datos eq "") {
				return -1;
			}
	}

	# Take group for this module

	my $id_grupo = dame_grupo_agente($pa_config, $id_agente,$dbh);

	# Get data for this module from tagent_module table

	my $query_idag = "SELECT * FROM tagente_modulo WHERE id_agente = $id_agente AND id_agente_modulo = " . $id_agente_modulo;
	my $s_idag = $dbh->prepare($query_idag);
	$s_idag ->execute;
	if ($s_idag->rows == 0) {
		logger( $pa_config, "ERROR Cannot find agenteModulo $id_agente_modulo",4);
		logger( $pa_config, "ERROR: SQL Query is $query_idag ",10);
		return -1;
	} else  {
		@data = $s_idag->fetchrow_array();
	}

	# Postprocess management

	if ((defined($data[23])) && ($data[23] != 0) && (is_numeric($data[23]))){
		if (($id_modulo == 1) || ($id_modulo == 7) || ($id_modulo == 15) || ($id_modulo == 22) || ($id_modulo == 4) || ($id_modulo == 8) || ($id_modulo == 16) ){
			$datos = $datos * $data[23];
		}
	}

	$s_idag->finish();

	# Get module interval or agent interval if module don't defined
	my $id_module_type	= $data[2];
	my $module_interval = $data[7];
	if ($module_interval == 0){
		$module_interval = dame_intervalo ($pa_config, $id_agente, $dbh);
 	}
	$s_idag->finish();

	# Check alert subroutine - Protect execution on an eval block

	eval {
		pandora_generate_alerts ($pa_config, $timestamp, $nombre_agente, $id_agente, $id_agente_modulo, $id_module_type, $id_grupo, $datos, $dbh);
	};
	if ($@) {
		logger($pa_config, "ERROR: Error in SUB calcula_alerta(). ModuleName: $nombre_modulo ModuleType: $tipo_modulo AgentName: $nombre_agente", 4);
		logger($pa_config, "ERROR Code: $@",10)
	}

	# $id_agente is agent ID to update ".dame_nombreagente_agentemodulo ($id_agente_modulo)."
	# Let's see if there is any entry at tagente_estado table

	my $idages = "SELECT * from tagente_estado WHERE id_agente_modulo = $id_agente_modulo";
	my $s_idages = $dbh->prepare($idages);
	$s_idages ->execute;

	# Apply Mysql quotes to data to prepare for database insertion / update
	$datos = $dbh->quote($datos); # Parse data entry for adecuate SQL representation.

	my $query_act; # OJO que dentro de una llave solo tiene existencia en esa llave !!
	if ($s_idages->rows == 0) { # Doesnt exist entry in table, lets make the first entry
		logger($pa_config, "Create entry in tagente_estado for module $nombre_modulo",4);

		$query_act = "INSERT INTO tagente_estado (id_agente_modulo, datos, timestamp, estado, cambio, id_agente, last_try, utimestamp, current_interval, running_by, last_execution_try) VALUES ($id_agente_modulo,$datos,'$timestamp','$estado','1',$id_agente,'$timestamp',$utimestamp, $module_interval, $id_server, $utimestamp)"; # Cuando se hace un insert, siempre hay un cambio de estado

	} else { # There are an entry in table already
		@data = $s_idages->fetchrow_array();
		if ( $data[11] == 0){
			$needs_update = 1;
		}

		# $data[5](status, should give us prev. status)
		# For xxxx_PROC type (boolean / monitor), create an event if state has changed
		if (( $data[5] != $estado) && (($tipo_modulo =~/keep_alive/) || ($tipo_modulo =~ /proc/))) {
			# Cambio de estado detectado !
			$cambio = 1;
			$needs_update = 1;

			# Makes an event entry, only if previous state changes, if new state, doesnt give any alert
			my $description;

			if ( $estado == 0) {
				$description = "Monitor ($nombre_modulo) goes up ";
				pandora_event ($pa_config, $description, $id_grupo,
							$id_agente, 2, 0, $id_agente_modulo, 
							"monitor_up", $dbh);
			}
			if ( $estado == 1) {
				$description = "Monitor ($nombre_modulo) goes down";
				pandora_event ($pa_config, $description, $id_grupo,
							$id_agente, 3, 0, $id_agente_modulo, 
							"monitor_down", $dbh);
			}
		}

		if ($needs_update == 1) {

			$query_act = "UPDATE tagente_estado SET 
							utimestamp = $utimestamp, datos = $datos, cambio = '$cambio', 
							timestamp = '$timestamp', estado = '$estado', id_agente = $id_agente, 
							last_try = '$timestamp', current_interval = '$module_interval', 
							running_by = $id_server, last_execution_try = $utimestamp 
							WHERE id_agente_modulo = $id_agente_modulo";
		} else { 

			# dont update last_try field, that it's the field
			# we use to check last update time in database

			$query_act = "UPDATE tagente_estado SET 
						utimestamp = $utimestamp, datos = $datos, cambio = '$cambio', 
						timestamp = '$timestamp', estado = '$estado', id_agente = $id_agente, 
						current_interval = '$module_interval', running_by = $id_server, 
						last_execution_try = $utimestamp WHERE id_agente_modulo = $id_agente_modulo";
		}
	}

	my $a_idages = $dbh->prepare($query_act);
	$a_idages->execute;
	$a_idages->finish();
   	$s_idages->finish();
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
			my $timestamp = &UnixDate("today","%Y-%m-%d %H:%M:%S");
			my $temp = $intervalo / 2;
			my $fecha_limite = DateCalc($timestamp,"- $temp seconds",\$err);
			$fecha_limite = &UnixDate($fecha_limite,"%Y-%m-%d %H:%M:%S");
			# Fecha limite has limit date, if there are records below this date
			# we cannot insert any data in Database. We use a limit based on agent_interval / 2
			# So if an agent has interval 300, could have a max of 24 records per hour in access_table
			# This is to do not saturate database with access records (because if you hace a network module with interval 30, you have
			# a new record each 30 seconds !
			# Compare with tagente.ultimo_contacto (tagent_lastcontact in english), so this will have
			# the latest update for this agent
			
			my $query = "select count(*) from tagent_access where id_agent = $id_agent and timestamp > '$fecha_limite'";
			my $query_exec = $dbh->prepare($query);
			my @data_row;
			$query_exec ->execute;
			@data_row = $query_exec->fetchrow_array();
			$temp = $data_row[0];
			$query_exec->finish();
			if ( $temp == 0) { # We need update access time
				my $query2 = "insert into tagent_access (id_agent, timestamp) VALUES ($id_agent,'$timestamp')";
				$dbh->do($query2);
				logger($pa_config,"Updating tagent_access for agent id $id_agent",9);
			}

			# Update keepalive module (if present, if there is more than one, only updates first one!).
			my $id_agent_module = get_db_free_field ("SELECT id_agente_modulo FROM tagente_modulo WHERE id_agente = $id_agent AND id_tipo_modulo = 100", $dbh);
			if ($id_agent_module ne -1){
					my $agent_name = get_db_free_field ("SELECT nombre FROM tagente WHERE id_agente = $id_agent", $dbh);
					my $module_typename = "keep_alive";
					my $module_name = get_db_free_field ("SELECT nombre FROM tagente_modulo WHERE id_agente_modulo = $id_agent_module", $dbh);
					pandora_writestate ($pa_config, $agent_name, $module_typename, $module_name, 1, 0, $dbh, 1);
			}
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
		$a_datos = 0;# If get bad data, then this is bad value, not "unknown" (> 1.3 version)
	} else {
		$a_datos = sprintf("%.2f", $a_datos);# Two decimal float. We cannot store more
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
		# Check for status: <1 state 1 (Bad), >= 1 state 0 (Good)
		# Calculamos su estado
		if ( $a_datos >= 1 ) { 
			$estado = 0;
		} else { 
			$estado = 1;
		}
		pandora_writestate ($pa_config, $agent_name, $module_type, $a_name, $a_datos, $estado, $dbh, $bUpdateDatos);
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
			# Numeric data has status N/A (100) always
			pandora_writestate ($pa_config, $agent_name, $module_type, $m_name, $m_data, 100, $dbh, $bUpdateDatos);
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
				if (is_numeric($data_row[4])){
					$timestamp_anterior = $data_row[4];
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
			my $query = "INSERT INTO tagente_datos_inc (id_agente_modulo,datos, timestamp, utimestamp) VALUES ($id_agente_modulo, '$m_data', '$m_timestamp', $m_utimestamp)";
			$dbh->do($query);
		} else {
			# Data exists previously	
			if ($diferencia != 0) {
				my $query2 = "UPDATE tagente_datos_inc SET timestamp='$m_timestamp', utimestamp = $m_utimestamp, datos = '$m_data' WHERE id_agente_modulo  = $id_agente_modulo";
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
				pandora_writestate ($pa_config, $agent_name, $module_type, $m_name, $new_data, 100, $dbh, $bUpdateDatos);
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
		pandora_writestate ($pa_config, $agent_name, $module_type, $m_name, $m_data, 100, $dbh, $bUpdateDatos);
	}
}


##########################################################################
## SUB pandora_writedata (pa_config, timestamp,nombre_agente,tipo_modulo, 
#							nombre_modulo, datos, max, min, descripcion, dbh, update)
## Insert data in main table: tagente_datos
	   
##########################################################################

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

		# Postprocess and get MAX/MIN only for numeric moduletypes

		if (($id_modulo != 3) && ($id_modulo != 17) && ($id_modulo != 10) && ($id_modulo != 23)){
			my $query_idag = "SELECT * FROM tagente_modulo WHERE id_agente = $id_agente AND id_agente_modulo = ".$id_agente_modulo;
			my $s_idag = $dbh->prepare($query_idag);
			$s_idag ->execute;
			if ($s_idag->rows == 0) {
				logger( $pa_config, "ERROR Cannot find agenteModulo $id_agente_modulo",6);
				logger( $pa_config, "ERROR: SQL Query is $query_idag ",10);
			} else  {	
				@data = $s_idag->fetchrow_array(); 
			}
			$max = $data[5];
			$min = $data[6];

			# Postprocess
			if ((defined($data[23])) && ($data[23] != 0) && (is_numeric($data[23]))){
				$datos = $datos * $data[23];
			}
			$s_idag->finish();
		} else {
			$max = "";
			$min = "";
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
			$query = "INSERT INTO tagente_datos_string (id_agente_modulo, datos, timestamp, utimestamp, id_agente) VALUES ($id_agente_modulo, $datos, $timestamp, $utimestamp, $id_agente)";
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
			$query = "INSERT INTO tagente_datos (id_agente_modulo, datos, timestamp, utimestamp, id_agente) VALUES ($id_agente_modulo, $datos, $timestamp, $utimestamp, $id_agente)";
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
		$query = "update tagente set agent_version = '$agent_version', ultimo_contacto_remoto = '$timestamp', ultimo_contacto = '$time_now', os_version = '$os_data' where id_agente = $id_agente";
		} else {
		$query = "update tagente set intervalo = $interval, agent_version = '$agent_version', ultimo_contacto_remoto = '$timestamp', ultimo_contacto = '$time_now', os_version = '$os_data' where id_agente = $id_agente";
		}
		logger( $pa_config, "pandora_lastagentcontact: Updating Agent last contact data for $nombre_agente",6);
		logger( $pa_config, "pandora_lastagentcontact: SQL Query: ".$query,10);
		my $sag = $dbh->prepare($query);
		$sag ->execute;
		$sag ->finish();
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
	my $my_timestamp = &UnixDate("today","%Y-%m-%d %H:%M:%S");
	my $sql = "INSERT INTO tincidencia (inicio, actualizacion, titulo, descripcion, origen, estado, prioridad, id_grupo) VALUES ('$my_timestamp', '$my_timestamp', '$title', '$text', '$origin', $status, $priority, $id_group)";
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

# End of function declaration
# End of defined Code

1;
__END__
		# Look updated servers and take down non updated servers
