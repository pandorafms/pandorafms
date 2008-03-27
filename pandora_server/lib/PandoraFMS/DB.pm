package PandoraFMS::DB;
##########################################################################
# Pandora FMS Database Package
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
use Date::Manip;	# Needed to manipulate DateTime formats of input, output and compare
use XML::Simple;

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
		pandora_event
		pandora_lastagentcontact
		pandora_writedata
		pandora_writestate
		pandora_calcula_alerta
		pandora_evaluate_compound_alert
		pandora_evaluate_compound_alerts
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
## SUB pandora_calcula_alerta 
## (paconfig, timestamp,nombre_agente,tipo_modulo,nombre_modulo,datos,dbh)
## Given a datamodule, generate alert if needed
##########################################################################

sub pandora_calcula_alerta (%$$$$$$) {
	my $pa_config = $_[0];
	my $timestamp = $_[1];
	my $nombre_agente = $_[2];
	my $tipo_modulo = $_[3];
	my $nombre_modulo = $_[4];
	my $datos = $_[5];
	my $dbh = $_[6];

	my $id_modulo;
	my $id_agente;
	my $id_agente_modulo;
	my $alert_name;
	my $max;
	my $min; # for calculate max & min to generate ALERTS
	my $alert_text="";
	
	# Get IDs from data packet
	$id_agente = dame_agente_id($pa_config, $nombre_agente, $dbh);
	my $id_group = dame_grupo_agente ($pa_config, $id_agente, $dbh);

	# If this group is disabled (not in production, alert will not be checked)
	if (give_group_disabled ($pa_config, $id_group, $dbh) == 1){
		return;
	}	
	$id_modulo = dame_modulo_id($pa_config, $tipo_modulo, $dbh);
	$id_agente_modulo = dame_agente_modulo_id ($pa_config, $id_agente, $id_modulo, $nombre_modulo, $dbh);
	logger($pa_config, "DEBUG: calcula_alerta() Calculado id_agente_modulo a $id_agente_modulo", 6);

	# If any alert from this combinatio of agent/module
	my $query_idag1 = "SELECT * FROM talerta_agente_modulo WHERE id_agente_modulo = '$id_agente_modulo' AND disable = 0";
	my $s_idag = $dbh->prepare($query_idag1);
	$s_idag ->execute;
	my @data;
	# If exists a defined alert for this module then continue
	if ($s_idag->rows != 0) {
		while (@data = $s_idag->fetchrow_array()) {
			my $id_aam = $data[0];
			my $id_alerta = $data[2];
			$id_agente_modulo = $data[1];
			$id_agente = dame_agente_id ($pa_config, dame_nombreagente_agentemodulo ($pa_config,  $id_agente_modulo, $dbh), $dbh);
			my $id_grupo = dame_grupo_agente ($pa_config, $id_agente, $dbh);
			my $campo1 = $data[3];
			my $campo2 = $data[4];
			my $campo3 = $data[5];
			my $descripcion = $data[6];
			my $dis_max = $data[7];
			my $dis_min = $data[8];
			my $threshold = $data[9];
			my $last_fired = $data[10];
			my $max_alerts = $data[11];
			my $times_fired = $data[12];
			my $min_alerts = $data[14];
			my $internal_counter = $data[15];
			my $alert_text = $data[16];
			my $alert_disable = $data[17];
			my $alert_timefrom = $data[18];
			my $alert_timeto = $data[19];
			my $ahora_hour = &UnixDate("today","%H");
			my $ahora_min = &UnixDate("today","%M");
			my $ahora_time = $ahora_hour.":".$ahora_min;

			# time check !
			if ((($ahora_time le $alert_timeto) && ($ahora_time ge $alert_timefrom)) || ($alert_timefrom eq $alert_timeto)){
				my $comando ="";
				logger($pa_config, "Found an alert defined for $nombre_modulo, its ID $id_alerta",4);
				# Here we process alert if conditions are ok
				# Get data for defined alert given as $id_alerta
				my $query_idag2 = "select * from talerta where id_alerta = '$id_alerta'";
				my $s2_idag = $dbh->prepare($query_idag2);
				$s2_idag ->execute;
				my @data2;
				if ($s2_idag->rows != 0) {
					while (@data2 = $s2_idag->fetchrow_array()) {
						$comando = $data2[2];
						$alert_name = $data2[1];
					}
				}
				$s2_idag->finish();
						# Get MAX and MIN value for this Alert. Only generate alerts if value is ABOVE MIN and BELOW MAX.
				my @data_max; 
				my $query_idag_max = "select * from tagente_modulo where id_agente_modulo = ".$id_agente_modulo;
				my $s_idag_max = $dbh->prepare($query_idag_max);
				$s_idag_max ->execute;
				if ($s_idag_max->rows == 0) {
					logger($pa_config, "ERROR Cannot find agenteModulo $id_agente_modulo",3);
					logger($pa_config, "ERROR: SQL Query is $query_idag_max ",10);
				} else  {
					@data = $s_idag_max->fetchrow_array();
				}
				$max = $data_max[5];
				$min = $data_max[6];
				$s_idag_max->finish();
				# Init values for alerts
				my $alert_prefired = 0;
				my $alert_fired = 0;
				my $update_counter =0;
				my $should_check_alert = 0;
				my $id_tipo_modulo = dame_id_tipo_modulo ($pa_config, $id_agente_modulo, $dbh);
				if (($id_tipo_modulo == 3) || ($id_tipo_modulo == 10) || ($id_tipo_modulo == 17)){
					if ( $datos =~ m/$alert_text/i ){
						$should_check_alert = 1;
					}
				} elsif (($datos > $dis_max) || ($datos < $dis_min)) {
					$should_check_alert = 1;
				}
				if ($should_check_alert == 1){
					# Check timegap
					my $fecha_ultima_alerta = ParseDate($last_fired);
					my $fecha_actual = ParseDate( $timestamp );
					my $ahora_mysql = &UnixDate("today","%Y-%m-%d %H:%M:%S");  # If we need to update MYSQL ast_fired will use $ahora_mysql
					my $time_threshold = $threshold;
					my $err; my $flag;
					my $fecha_limite = DateCalc ($fecha_ultima_alerta, "+ $time_threshold seconds", \$err);
					$flag = Date_Cmp ($fecha_actual, $fecha_limite);
					# Check timer threshold for this alert
					if ( $flag >= 0 ) { # Out limits !, reset $times_fired, but do not write to
								# database until a real alarm was fired
						if ($times_fired > 0){ 
							$times_fired = 0;
							$internal_counter=0;
						}
						logger ($pa_config, "Alarm out of timethreshold limits, resetting counters", 10);
					}
					# We are between limits marked by time_threshold or running a new time-alarm-interval 
					# Caution: MIN Limit is related to triggered (in time-threshold limit) alerts
					# but MAX limit is related to executed alerts, not only triggered. Because an alarm to be
					# executed could be triggered X (min value) times to be executed.
					if (($internal_counter >= $min_alerts) && ($times_fired  < $max_alerts)){
						# The new alert is between last valid time + threshold and between max/min limit to alerts in this gap of time.
						$times_fired++;
						if ($internal_counter == 0){
							$internal_counter++; 
						}
						$dbh->do("UPDATE talerta_agente_modulo SET times_fired = $times_fired, last_fired = '$ahora_mysql', internal_counter = $internal_counter WHERE id_aam = $id_aam");
						my $nombre_agente = dame_nombreagente_agentemodulo ($pa_config, $id_agente_modulo, $dbh);
						# --------------------------------------
						# Now call to execute_alert to real exec
						execute_alert ($pa_config, $id_alerta, $campo1, $campo2, $campo3, 
$nombre_agente, $timestamp, $datos, $comando, $alert_name, $descripcion, 1, $dbh);
						# --------------------------------------
						
						# Evaluate compound alerts, since an alert has changed its status.
						pandora_evaluate_compound_alerts ($pa_config, $timestamp, $id_aam, $nombre_agente, 0, $dbh);
					} else {
						# Alert is in valid timegap but has too many alerts
						# or too many little
						if ($internal_counter < $min_alerts){
							$internal_counter++;
							# Now update new value for times_fired & last_fired
							# if we are below minlimit for triggering this alert
							logger ($pa_config, "Alarm not fired because is below min limit",6);
						} else { # Too many alerts fired (upper limit)
							logger ($pa_config, "Alarm not fired because is above max limit",6);
						}
						$dbh->do("UPDATE talerta_agente_modulo SET times_fired = $times_fired, internal_counter = $internal_counter WHERE id_aam = $id_aam");

						# Evaluate compound alerts, since an alert has changed its status.
						pandora_evaluate_compound_alerts ($pa_config, $timestamp, $id_aam, $nombre_agente, 0, $dbh);
					}
				} 
				else {  # This block is executed because actual data is OUTSIDE
						# limits that trigger alert (so, it is valid data)
					# Check timegap
					my $fecha_ultima_alerta = ParseDate($last_fired);
					my $fecha_actual = ParseDate( $timestamp );
					my $ahora_mysql = &UnixDate("today","%Y-%m-%d %H:%M:%S");
					# If we need to update MYSQL ast_fired will use $ahora_mysql
					my $time_threshold = $threshold;
					my $err; my $flag;
					my $fecha_limite = DateCalc($fecha_ultima_alerta,"+ $time_threshold seconds",\$err);
					$flag = Date_Cmp ($fecha_actual, $fecha_limite);
					# Check timer threshold for this alert
					if ( $flag >= 0 ) {
						# This is late, we need to reset alert NOW
						# Create event for alert ceased only if has been fired.
						# If not, simply restore counters to 0
						if ($times_fired > 0){
							my $evt_descripcion = "Alert ceased - Expired ($descripcion)";
							pandora_event ($pa_config, $evt_descripcion, $id_grupo, $id_agente, $dbh);
						}
					} else {
						# We're running on timegap, so check if we're above
						# limit or below. If we don't have any alert fired,
						# skip other checks
						if ($times_fired > 0){
							my $evt_descripcion = "Alert ceased - Recovered ($descripcion)";
							pandora_event ($pa_config, $evt_descripcion, $id_grupo, $id_agente, $dbh);
							# Specific patch for F. Corona
							# This enable alert recovery notification by using the same alert definition but
							# inserting WORD "RECOVERED" in second and third field of 
							# alert. To activate setup your .conf with new token
 							# "alert_recovery" and set to 1 (disabled by default) 
							if ($pa_config->{"alert_recovery"} eq "1"){
							        execute_alert ($pa_config, $id_alerta, $campo1, 
"[RECOVERED ] - ".$campo2, "[ALERT CEASED - RECOVERED] - ".$campo3, $nombre_agente, $timestamp, $datos, $comando, 
$alert_name, $descripcion, 0, $dbh);
							}
						}
										}
					if (($times_fired > 0) || ($internal_counter > 0)){
						$dbh->do("UPDATE talerta_agente_modulo SET internal_counter = 0, times_fired =0 WHERE id_aam = $id_aam");

						# Evaluate compound alerts, since an alert has changed its status.
						pandora_evaluate_compound_alerts ($pa_config, $timestamp, $id_aam, $nombre_agente, 0, $dbh);
					}
				}
			} # timecheck (outside time limits for this alert)
			else { # Outside operative alert timeslot
				if ($times_fired > 0){
					my $evt_descripcion2 = "Alert ceased - Run out of valid alert timegap ($descripcion)";
					pandora_event ($pa_config, $evt_descripcion2, $id_grupo, $id_agente, $dbh);
				}
				$dbh->do("UPDATE talerta_agente_modulo SET internal_counter = 0, times_fired =0 WHERE id_aam = $id_aam");

				# Evaluate compound alerts, since an alert has changed its status.
				pandora_evaluate_compound_alerts ($pa_config, $timestamp, $id_aam, $nombre_agente, 0, $dbh);
			}
		} # While principal
	} # if there are valid records
	$s_idag->finish();
}

##########################################################################
## SUB pandora_evaluate_compound_alert
## (paconfig,id,dbh)
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
	my $query_id_aam = "SELECT id_aam, operation FROM tcompound_alert
	                    WHERE id = '$id' ORDER BY operation";
	my $s_id_aam = $dbh->prepare($query_id_aam);
	$s_id_aam ->execute;

	if ($s_id_aam->rows == 0) {
		return 0;
	}
	
	while (@data = $s_id_aam->fetchrow_array()) {

		# Alert ID
		my $id_aam = $data[0];

		# Logical operation to perform
		my $operation = $data[1];

		# Get alert data
		my $query_times_fired = "SELECT disable, times_fired FROM
		                         talerta_agente_modulo WHERE id_aam =
					 '$id_aam'";
		my $s_times_fired = $dbh->prepare($query_times_fired);
		$s_times_fired ->execute;
		if ($s_id_aam->rows == 0) {
			next;
		}	
	
		my @data2 = $s_times_fired->fetchrow_array();
		my $disable = $data2[0];

		# Check whether the alert was fired
		my $fired = $data2[1] > 0 ? 1 : 0;

		$s_times_fired->finish();
	
		# Skip disabled alerts
		if ($disable == 1) {
			next;
		}

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

	$s_id_aam->finish();
	return $status;
}

##########################################################################
## SUB pandora_evaluate_compound_alerts
## (paconfig,timestamp,id_aam,nombre_agente,depth,dbh)
## Evaluate compound alerts that depend on a given alert.
##########################################################################

sub pandora_evaluate_compound_alerts (%$$$$$) {
	my $pa_config = $_[0];
	my $timestamp = $_[1];
	my $id_aam = $_[2];
	my $nombre_agente = $_[3];
	my $depth = $_[4];
	my $dbh = $_[5];
	
	# Get all compound alerts that depend on this alert
	my $query_id = "SELECT id FROM tcompound_alert WHERE id_aam = '$id_aam'";
	my $s_id = $dbh->prepare($query_id);

	$s_id ->execute;
	if ($s_id->rows == 0) {
		$s_id->finish();
		return;
	}

	while (my @data = $s_id->fetchrow_array()) {
		my $id = $data[0];

		# Get compound alert parameters
		my $query_data = "SELECT al_campo1, al_campo2, al_campo3, descripcion, alert_text, disable FROM talerta_agente_modulo WHERE id_aam = '$id'";
		my $s_data = $dbh->prepare($query_data);

		$s_data ->execute;
		if ($s_data->rows == 0) {
			next;
		}

		@data = $s_data->fetchrow_array();

		my $field1 = $data[0];
		my $field2 = $data[1];
		my $field3 = $data[2];
		my $description = $data[3];
		my $text = $data[4];
		my $disable = $data[5];

		# Skip disabled alerts
		if ($disable == 1) {
			next;
		}

		# Evaluate the alert
		my $status = pandora_evaluate_compound_alert($pa_config, $id, $dbh);
		if ($status != 0) {
			# Update the alert status
			$dbh->do("UPDATE talerta_agente_modulo SET times_fired = 1 WHERE id_aam = $id");
			my $command = dame_comando_alerta ($pa_config, $id, $dbh);

			execute_alert ($pa_config, $id, $field1, $field2, $field3, $nombre_agente, $timestamp, $text, $command, '', $description, 1, $dbh);
		}
		else {
			# Update the alert status
			$dbh->do("UPDATE talerta_agente_modulo SET times_fired = 0 WHERE id_aam = $id");
		}

		# Evaluate nested compound alerts
		if ($depth < $pa_config->{"compound_max_depth"}) {
			&pandora_evaluate_compound_alerts ($pa_config, $timestamp, $id, $nombre_agente, $depth + 1, $dbh);
		}
		else {
			logger($pa_config, "ERROR: Error in SUB pandora_evaluate_compound_alerts(): Maximum nested compound alert depth reached.", 2);
		}
	}

	$s_id->finish();
}

##########################################################################
## SUB execute_alert (id_alert, field1, field2, field3, agent, timestamp, data, 
## command, $alert_name, $alert_description, create_event, dbh)
## Do a execution of given alert with this parameters
##########################################################################

sub execute_alert (%$$$$$$$$$$$$) {
	my $pa_config = $_[0];
	my $id_alert = $_[1];
	my $field1 = $_[2];
	my $field2 = $_[3];
	my $field3 = $_[4];
	my $agent = $_[5];
	my $timestamp = $_[6];
	my $data = $_[7];
	my $command = $_[8];
	my $alert_name = $_[9];
	my $alert_description = $_[10];
	my $create_event = $_[11];
	my $dbh = $_[12];

	# Compound only
	if ($id_alert == 0){
		return;
	}

	if (($command eq "") && ($alert_name eq "")){
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
	}
	
	logger($pa_config, "Alert ($alert_name) TRIGGERED for $agent",2);
	if ($id_alert != 3){ # id_alerta 3 is reserved for internal audit system
		$command =~ s/_field1_/"$field1"/ig;
		$command =~ s/_field2_/"$field2"/ig;
		$command =~ s/_field3_/"$field3"/ig;
		$command=~ s/_agent_/$agent/ig;
		$command =~ s/_timestamp_/$timestamp/ig;
		$command =~ s/_data_/$data/ig;
		# Clean up some "tricky" characters
		$command =~ s/&gt;/>/g;
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
			logger($pa_config, "WARNING: Alert command don't retun from execution. ( $command )", 0 );
			logger($pa_config, "ERROR Code: $@",1);
		}
	} else { # id_alerta = 3, is a internal system audit
		logger($pa_config, "Internal audit lauch for agent name $agent",3);
		$field1 =~ s/_agent_/$agent/ig;
		$field1 =~ s/_timestamp_/$timestamp/ig;
		$field1 =~ s/_data_/$data/ig;
		pandora_audit ($pa_config, $field1, $agent, "Alert ($alert_description)", $dbh);
	}
	if ($create_event == 1){
		my $evt_descripcion = "Alert fired ($alert_description)";
		my $id_agente = dame_agente_id ($pa_config, $agent, $dbh);
		pandora_event ($pa_config, $evt_descripcion, dame_grupo_agente($pa_config, $id_agente, $dbh), 
$id_agente, $dbh);
	}
}


##########################################################################
## SUB pandora_writestate (pa_config, nombre_agente,tipo_modulo,nombre_modulo,valor_datos, estado)
## Alter data, chaning status of modules in state table
##########################################################################

sub pandora_writestate (%$$$$$$$) {
	# my $timestamp = $_[0];
	# slerena, 05/10/04 : Fixed bug because differences between agent / server time source.
	# now we use only local timestamp to stamp state of modules
	my $pa_config = $_[0];
	my $nombre_agente = $_[1];
	my $tipo_modulo = $_[2]; # passed as string
	my $nombre_modulo = $_[3];
	my $datos = $_[4]; # Careful: Dont pass a hash, only a single value
	my $estado = $_[5];
	my $dbh = $_[6];
	my $needs_update = $_[7];
	
	my @data;
	my $cambio = 0; 
	my $id_grupo;

    # Get current timestamp / unix numeric time
    my $timestamp = &UnixDate ("today", "%Y-%m-%d %H:%M:%S"); # string timestamp
    my $utimestamp = &UnixDate($timestamp,"%s"); # convert from human to integer

    # Get server id
	my $server_name = $pa_config->{'servername'}.$pa_config->{"servermode"};
	my $id_server = dame_server_id($pa_config, $server_name, $dbh);

	# Get id
	# BE CAREFUL: We don't verify the strings chains
	# TO DO: Verify errors
	my $id_agente = dame_agente_id ($pa_config, $nombre_agente, $dbh);
	my $id_modulo = dame_modulo_id ($pa_config, $tipo_modulo, $dbh);
	my $id_agente_modulo = dame_agente_modulo_id($pa_config, $id_agente, $id_modulo, $nombre_modulo, $dbh);

	if (($id_agente ==  -1) || ($id_agente_modulo == -1)) {
		return 0;
	}

	# Seek for agent_interval or module_interval
	my $query_idag = "SELECT * FROM tagente_modulo WHERE id_agente = $id_agente AND id_agente_modulo = " . $id_agente_modulo;;
	my $s_idag = $dbh->prepare($query_idag);
	$s_idag ->execute;
	if ($s_idag->rows == 0) {
		logger( $pa_config, "ERROR Cannot find agenteModulo $id_agente_modulo",4);
		logger( $pa_config, "ERROR: SQL Query is $query_idag ",10);
	} else  {    
		@data = $s_idag->fetchrow_array(); 
	}
	my $module_interval = $data[7];
	if ($module_interval == 0){
		$module_interval = dame_intervalo ($pa_config, $id_agente, $dbh);
 	}
	$s_idag->finish();
	# Check alert subroutine
	eval {
		pandora_calcula_alerta ($pa_config, $timestamp, $nombre_agente, $tipo_modulo, $nombre_modulo, $datos, $dbh);
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
	$datos = $dbh->quote($datos); # Parse data entry for adecuate SQL representation.
	my $query_act; # OJO que dentro de una llave solo tiene existencia en esa llave !!
	if ($s_idages->rows == 0) { # Doesnt exist entry in table, lets make the first entry
		logger($pa_config, "Create entry in tagente_estado for module $nombre_modulo",4);
        $query_act = "INSERT INTO tagente_estado (id_agente_modulo, datos, timestamp, estado, cambio, id_agente, last_try, utimestamp, current_interval, running_by, last_execution_try) VALUES ($id_agente_modulo,$datos,'$timestamp','$estado','1',$id_agente,'$timestamp',$utimestamp, $module_interval, $id_server, $utimestamp)"; # Cuando se hace un insert, siempre hay un cambio de estado
	} else { # There are an entry in table already
	    @data = $s_idages->fetchrow_array();
	    # Se supone que $data[5](estado) ( nos daria el estado ANTERIOR
	# For xxxx_PROC type (boolean / monitor), create an event if state has changed
	    if (( $data[5] != $estado) && ( ($tipo_modulo =~/keep_alive/) || ($tipo_modulo =~ /proc/)) ) {
	        # Cambio de estado detectado !
	        $cambio = 1;
	        # Este seria el momento oportuno de probar a saltar la alerta si estuviera definida
		# Makes an event entry, only if previous state changes, if new state, doesnt give any alert
		$id_grupo = dame_grupo_agente($pa_config, $id_agente,$dbh);
		my $descripcion;
        if ( $estado == 0) {
            $descripcion = "Monitor ($nombre_modulo) goes up ";
        }
		if ( $estado == 1) {
			$descripcion = "Monitor ($nombre_modulo) goes down";
		}
		pandora_event ($pa_config, $descripcion, $id_grupo, $id_agente, $dbh);
	    }
	    if ($needs_update == 1) {
            $query_act = "UPDATE tagente_estado SET utimestamp = $utimestamp, datos = $datos, cambio = '$cambio', timestamp = '$timestamp', estado = '$estado', id_agente = $id_agente, last_try = '$timestamp', current_interval = '$module_interval', running_by = $id_server, last_execution_try = $utimestamp WHERE id_agente_modulo = $id_agente_modulo";
        } else { # dont update last_try field, that it's the field
                # we use to check last update time in database
            $query_act = "UPDATE tagente_estado SET utimestamp = $utimestamp, datos = $datos, cambio = '$cambio', timestamp = '$timestamp', estado = '$estado', id_agente = $id_agente, current_interval = '$module_interval', running_by = $id_server, last_execution_try = $utimestamp WHERE id_agente_modulo = $id_agente_modulo";
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
# Modulos genericos de Pandora            |
# ----------------------------------------+

# Los modulos genericos de pandora son de 4 tipos
#
# generic_data . Almacena numeros enteros largos, util para monitorizar proceos que
#                                general valores o sensores que devuelven valores.

# generic_proc . Almacena informacion booleana (cierto/false), util para monitorizar
#                 procesos logicos.

# generic_data_inc . Almacena datos igual que generic_data pero tiene una logica
#                                que sirve para las fuentes de datos que alimentan el agente con datos
#                                que se incrementan continuamente, por ejemplo, los contadores de valores
#                                en las MIB de los adaptadores de red, las entradas de cierto tipo en
#                                un log o el nº de segundos que ha pasado desde X momento. Cuando el valor
#                                es mejor que el anterior o es 0, se gestiona adecuadamente el cambio.

# generic_data_string. Store a string, max 255 chars.

##########################################################################
## SUB pandora_accessupdate (pa_config, id_agent, dbh)
## Update agent access table
##########################################################################

sub pandora_accessupdate (%$$) {
	my $pa_config = $_[0];
	my $id_agent = $_[1];
	my $dbh = $_[2];
	
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
		$a_datos = 0;	# If get bad data, then this is bad value, not "unknown" (> 1.3 version)
	} else {
		$a_datos = sprintf("%.2f", $a_datos);		# Two decimal float. We cannot store more
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
	pandora_writedata($pa_config, $a_timestamp,$agent_name,$module_type,$a_name,$a_datos,$a_max,$a_min,$a_desc,$dbh, \$bUpdateDatos);

	# Check for status: <1 state 1 (Bad), >= 1 state 0 (Good)
	# Calculamos su estado
	if ( $a_datos >= 1 ) { 
		$estado = 0;
	} else { 
		$estado = 1;
	}
	pandora_writestate ($pa_config, $agent_name, $module_type, $a_name, $a_datos, $estado, $dbh, $bUpdateDatos);
}

##########################################################################
## SUB module_generic_data (param_1, param_2,param_3, param_4)
## Process generated data form numeric data module acquire
##########################################################################
## param_1 : XML name
## paran_2 : Timestamp
## param_3 : Agent name
## param_4 : Module type

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
    
	my $bUpdateDatos = 0; # added, patch submitted by Dassing
	if (ref($m_data) ne "HASH"){
        if (!is_numeric($m_data)){
            logger($pa_config, "(data) Invalid data (non-numeric) received from $agent_name, module $m_name", 1);
            return -1;
        }
		if ($m_data =~ /[0-9]*/){
			$m_data =~ s/\,/\./g; # replace "," by "."
			$m_data = sprintf("%.2f", $m_data);	# Two decimal float. We cannot store more
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
		pandora_writedata($pa_config, $m_timestamp,$agent_name,$module_type,$m_name,$m_data,$a_max,$a_min,$a_desc,$dbh,\$bUpdateDatos);
		# Numeric data has status N/A (100) always
		pandora_writestate ($pa_config, $agent_name, $module_type, $m_name, $m_data, 100, $dbh, $bUpdateDatos);
	} else {
		logger($pa_config, "(data) Invalid data value received from $agent_name, module $m_name", 2);
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
		$m_data = sprintf("%.2f", $m_data);	# Two decimal float. We cannot store more
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
		#    last value and actual value, and in aux. table tagente_datos_inc the last real value
		# 3) If new data is lower than previous or no previous value (RESET), store 0 in tagente_datos and store
		#    real value in aux. table, replacing the old one
		
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
			pandora_writedata ($pa_config, $m_timestamp, $agent_name, $module_type, $m_name, $new_data, $a_max, $a_min, $a_desc, $dbh, \$bUpdateDatos);
			# Inc status is always 100 (N/A)
			pandora_writestate ($pa_config, $agent_name, $module_type, $m_name, $new_data, 100, $dbh, $bUpdateDatos);
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
	pandora_writedata($pa_config, $m_timestamp, $agent_name, $module_type, $m_name, $m_data, $a_max, $a_min, $a_desc, $dbh, \$bUpdateDatos);
    	# String type has no state (100 = N/A)
	pandora_writestate ($pa_config, $agent_name, $module_type, $m_name, $m_data, 100, $dbh, $bUpdateDatos);
}


##########################################################################
## SUB pandora_writedata (pa_config, timestamp,nombre_agente,tipo_modulo,nombre_modulo,datos)
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
	# Check if exists module and agent_module reference in DB, if not, and learn mode activated, insert module in DB
	if ($id_agente eq "-1"){
		goto fin_DB_insert_datos;
	}
	my $id_modulo = dame_modulo_id($pa_config, $tipo_modulo,$dbh);
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
		my $query_idag = "SELECT * FROM tagente_modulo WHERE id_agente = $id_agente AND id_agente_modulo = ".$id_agente_modulo;
		my $s_idag = $dbh->prepare($query_idag);
		$s_idag ->execute;
		if ($s_idag->rows == 0) {
			logger( $pa_config, "ERROR Cannot find agenteModulo $id_agente_modulo",6);
			logger( $pa_config, "ERROR: SQL Query is $query_idag ",10);
		} else  {    @data = $s_idag->fetchrow_array(); }
		$max = $data[5];
		$min = $data[6];
		$s_idag->finish();
	} else { # Id AgenteModulo DOESNT exist, it could need to be created...
		if (dame_learnagente($pa_config, $id_agente, $dbh) eq "1" ){
			# Try to write a module and agent_module definition for that datablock
			logger( $pa_config, "Pandora_insertdata will create module (learnmode) for agent $nombre_agente",6);
			$id_agente_modulo = crea_agente_modulo ($pa_config, $nombre_agente, $tipo_modulo, $nombre_modulo, $max, $min, $descripcion, $dbh);
			$needscreate = 1; # Really needs to be created
		} else {
			logger( $pa_config, "VERBOSE: pandora_insertdata cannot find module definition ($nombre_modulo / $tipo_modulo )for agent $nombre_agente - Use LEARN MODE for autocreate.",2);
			goto fin_DB_insert_datos;
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
			$needsupdate=1;
			logger( $pa_config, "Updating data for $nombre_modulo after compare with tagente_data: new($datos) ne old($data[2])",5);
		} else {
			# Data in DB is the same, but could be older (more than 1
			# day ). Should check this against last_try field, who is
			# updated only when new data is stored or each 24 hours
			my $fecha_datos = $data[7]; # last_try
			my $fecha_mysql = &UnixDate("today","%Y-%m-%d %H:%M:%S");    
			my $fecha_actual = ParseDate( $fecha_mysql );
			my $fecha_flag; my $err;
			my $fecha_limite = DateCalc($fecha_actual,"- 1 days",\$err);
			$fecha_flag = Date_Cmp ($fecha_limite, $fecha_datos);
			if ($fecha_flag >= 0) { # write data, 
				logger( $pa_config, "Too old data stored (>24Hr). Updating data for $nombre_modulo",5);
				$needsupdate = 1;
			}
		}
    	} else {
    		$needsupdate=1; # There aren't data
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
			$query = "INSERT INTO tagente_datos (id_agente_modulo,  datos, timestamp, utimestamp, id_agente) VALUES ($id_agente_modulo, $datos, $timestamp, $utimestamp, $id_agente)";
		} # If data is out of limits, do not insert into database
		if ($outlimit == 0){
			logger($pa_config, "DEBUG: pandora_insertdata Calculado id_agente_modulo a $id_agente_modulo",6);
			logger($pa_config, "DEBUG: pandora_insertdata SQL : $query",10);
			$dbh->do($query); # Makes insertion in database
		}
	}
fin_DB_insert_datos:
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
	my $temp = $pa_config->{"keepalive"} - $pa_config->{"server_threshold"};
	if ($temp <= 0){
		my $timestamp = &UnixDate("today","%Y-%m-%d %H:%M:%S");
		$temp = $pa_config->{"keepalive_orig"} * 2; # Down if keepalive x 2 seconds unknown
		my $fecha_limite = DateCalc($timestamp,"- $temp seconds",\$err);
		$fecha_limite = &UnixDate($fecha_limite,"%Y-%m-%d %H:%M:%S");		
		# Look updated servers and take down non updated servers
		my $query_idag = "select * from tserver where keepalive < '$fecha_limite'";
		my $s_idag = $dbh->prepare($query_idag);
		$s_idag ->execute;
		if ($s_idag->rows != 0) {
			while (@data = $s_idag->fetchrow_array()){
				if ($data[3] != 0){ # only if it's currently not down
					# Update server data
					$version_data = $pa_config->{"version"}." (P) ".$pa_config->{"build"};
					my $sql_update = "UPDATE tserver SET status = 0, version = '".$version_data."' WHERE id_server = $data[0]";
					$dbh->do($sql_update);
					pandora_event($pa_config, "Server ".$data[1]." going Down", 0, 0, $dbh);
					logger( $pa_config, "Server ".$data[1]." going Down ",1);
				}
			}
		}
		$s_idag->finish();
		# Update my server
		pandora_updateserver ($pa_config, $pa_config->{'servername'}, 1, $opmode, $dbh);
		$pa_config->{"keepalive"} = $pa_config->{"keepalive_orig"};
	}
	$pa_config->{"keepalive"} = $pa_config->{"keepalive"} - $pa_config->{"server_threshold"};
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
    } else {
        logger ($pa_config, "Error: received a unknown server type. Aborting startup.",0);
        print (" [ERROR] Received a unknown server type. Aborting startup \n\n");
        exit;
    }

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
			if ($data[3] == 0){ # If down, update to get up the server
				pandora_event($pa_config, "Server ".$data[1]." going UP", 0, 0, $dbh);
				logger( $pa_config, "Server ".$data[1]." going UP ",1);
			}
			# Update server data
			my $timestamp = &UnixDate("today","%Y-%m-%d %H:%M:%S");
			$version_data = $pa_config->{"version"}." (P) ".$pa_config->{"build"};
			if ($opmode == 0){
				$sql_update = "update tserver set version = '$version_data', status = 1, laststart = '$timestamp', keepalive = '$timestamp', recon_server = 0, snmp_server = 0, network_server = 0, data_server = 1, master = $pa_config->{'pandora_master'}, checksum = $pa_config->{'pandora_check'} where id_server = $id_server";
			} elsif ($opmode == 1){
				$sql_update = "update tserver set version = '$version_data', status = 1, laststart = '$timestamp', keepalive = '$timestamp', recon_server = 0, snmp_server = 0, network_server = 1, data_server = 0, master = $pa_config->{'pandora_master'}, checksum = 0 where id_server = $id_server";
			} elsif ($opmode == 2) {
				$sql_update = "update tserver set version = '$version_data', status = 1, laststart = '$timestamp', keepalive = '$timestamp', recon_server = 0, snmp_server = 1, network_server = 0, data_server = 0, master = $pa_config->{'pandora_master'}, checksum = 0 where id_server = $id_server";
			} elsif ($opmode == 3) {
				$sql_update = "update tserver set version = '$version_data', status = 1, laststart = '$timestamp', keepalive = '$timestamp', recon_server = 1, snmp_server = 0, network_server = 0, data_server = 0, master =  $pa_config->{'pandora_master'}, checksum = 0 where id_server = $id_server";
			} elsif ($opmode == 4) {
                $sql_update = "update tserver set version = '$version_data', status = 1, laststart = '$timestamp', keepalive = '$timestamp', plugin_server = 1, master =  $pa_config->{'pandora_master'}, checksum = 0 where id_server = $id_server";
            } elsif ($opmode == 5) {
                $sql_update = "update tserver set version = '$version_data', status = 1, laststart = '$timestamp', keepalive = '$timestamp', prediction_server = 1, master =  $pa_config->{'pandora_master'}, checksum = 0 where id_server = $id_server";
            } elsif ($opmode == 6) {
                $sql_update = "update tserver set version = '$version_data', status = 1, laststart = '$timestamp', keepalive = '$timestamp', wmi_server = 1, master =  $pa_config->{'pandora_master'}, checksum = 0 where id_server = $id_server";
            }
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
## SUB pandora_event (pa_config, evento, id_grupo, id_agente, dbh)
## Write in internal audit system an entry.
##########################################################################

sub pandora_event (%$$$$) {
	my $pa_config = $_[0];
        my $evento = $_[1];
        my $id_grupo = $_[2];
        my $id_agente = $_[3];
	my $dbh = $_[4];
        my $timestamp = &UnixDate("today","%Y-%m-%d %H:%M:%S");
	my $utimestamp; # integer version of timestamp	

	$utimestamp = &UnixDate($timestamp,"%s"); # convert from human to integer
        $evento = $dbh->quote($evento);
       	$timestamp = $dbh->quote($timestamp);
	my $query = "INSERT INTO tevento (id_agente, id_grupo, evento, timestamp, estado, utimestamp) VALUES ($id_agente, $id_grupo, $evento, $timestamp, 0, $utimestamp)";
	logger ($pa_config,"EVENT Insertion: $query", 5);
        $dbh->do($query);	
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
		logger ($pa_config,"FATAL: Error code $@",2);
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
			logger ($pa_config, "ERROR dame_agente_id(): Cannot find agent called $agent_name. Returning -1", 1);
			logger ($pa_config, "ERROR: SQL Query is $query_idag ",2);
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

        my $id_server;my @data;
        # Get serverid
        my $query_idag = "SELECT * FROM tserver WHERE name = '$name' ";
       	my $s_idag = $dbh->prepare($query_idag);
        $s_idag ->execute;
    	if ($s_idag->rows == 0) {
        	logger ($pa_config, "ERROR dame_server_id(): Cannot find server called $name. Returning -1",4);
        	logger ($pa_config, "ERROR: SQL Query is $query_idag ",10);
		$data[0]=-1;
    	} else  {           @data = $s_idag->fetchrow_array();   }
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
        	logger ($pa_config, "ERROR dame_grupo_agente(): Cannot find agent with id $id_agente",1);
        	logger ($pa_config, "ERROR: SQL Query is $query_idag ",2);
    	} else  {           @data = $s_idag->fetchrow_array();   }
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
        	logger ($pa_config, "ERROR dame_comando_alerta(): Cannot find alert $id_alerta",1);
        	logger ($pa_config, "ERROR: SQL Query is $query_idag ",2);
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
        	logger($pa_config, "ERROR dame_agente_modulo_id(): Cannot find a module called $name", 2);
        	logger($pa_config, "ERROR: SQL Query is $query_idag ",10);
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
	} else  {    @data = $s_idag->fetchrow_array(); }
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
        	logger($pa_config, "ERROR give_network_component_profile_name(): Cannot find network profile $id_nc",1);
      		logger($pa_config, "ERROR: SQL Query is $query_idag ",2);
		$tipo = 0;
    	} else  {    @data = $s_idag->fetchrow_array(); }
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
    	} else  {    @data = $s_idag->fetchrow_array(); }
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

	my $query = "INSERT INTO tagente_modulo (id_agente,id_tipo_modulo,nombre,max,min,descripcion) VALUES ($agente_id, $modulo_id, $nombre_modulo, $max, $min, $descripcion)";
	if (($max eq "") and ($min eq "")) {
		$query = "INSERT INTO tagente_modulo (id_agente,id_tipo_modulo,nombre,descripcion) VALUES ($agente_id, $modulo_id, $nombre_modulo, $descripcion)";
	} elsif ($min eq "") {
		$query = "INSERT INTO tagente_modulo (id_agente,id_tipo_modulo,nombre,max,descripcion) VALUES ($agente_id, $modulo_id, $nombre_modulo, $max, $descripcion)";
	} elsif ($min eq "") {
		$query = "INSERT INTO tagente_modulo (id_agente,id_tipo_modulo,nombre,min,descripcion) VALUES 	($agente_id, $modulo_id, $nombre_modulo, $min, $descripcion)";
	}
	logger( $pa_config, "DEBUG: Query for autocreate : $query ", 10);	
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
        
        my $query = $condition;
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

# End of function declaration
# End of defined Code

1;
__END__
