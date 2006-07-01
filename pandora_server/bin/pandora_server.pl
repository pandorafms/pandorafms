#!/usr/bin/perl
##################################################################################
# Pandora Data Server
##################################################################################
# Copyright (c) 2004-2006 Sancho Lerena, slerena@gmail.com
# Copyright (c) 2005-2006 Artica Soluciones Tecnol�icas S.L
#
#This program is free software; you can redistribute it and/or
#modify it under the terms of the GNU General Public License
#as published by the Free Software Foundation; either version 2
#of the License, or (at your option) any later version.
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program; if not, write to the Free Software
#Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
##################################################################################

# Includes list
use strict;
use warnings;

use XML::Simple;                	# Useful XML functions
use Digest::MD5;                	# MD5 generation
use Time::Local;                	# DateTime basic manipulation
use DBI;                               	# DB interface with MySQL
use Date::Manip;                	# Needed to manipulate DateTime formats of input, output and compare
use File::Copy;                          # Needed to manipulate files
use threads;
use threads::shared;

# Librerias / Modulos de pandora
use pandora_config;
use pandora_tools;
use pandora_db;

# FLUSH in each IO, only for DEBUG, very slow !
$| = 1;

my %pa_config; 

# Inicio del bucle principal de programa
pandora_init(\%pa_config,"Pandora Server");

# Read config file for Global variables
pandora_loadconfig (\%pa_config,0);

# Audit server starting
pandora_audit (\%pa_config, "Pandora Daemon starting", "SYSTEM", "System");

# KeepAlive checks for Agents, only for master servers, in separate thread
threads->new( \&pandora_keepalived, \%pa_config);


if ($pa_config{"daemon"} eq "1" ){
	&daemonize;
}

# Module processor subsystem
pandora_dataserver(\%pa_config);


#------------------------------------------------------------------------------------
#------------------------------------------------------------------------------------
#------------------------------------------------------------------------------------
#---------------------  Main Perl Code below this line-------------------------------
#------------------------------------------------------------------------------------
#------------------------------------------------------------------------------------
#------------------------------------------------------------------------------------




##############################################################################
# Main loop
##############################################################################

sub pandora_dataserver {
	my $pa_config = $_[0];
	my $file_data;
	my $file_md5;
	my @file_list;
	my $onefile; # Each item of incoming directory 
	my $agent_filename;
	my $dbh = DBI->connect("DBI:mysql:pandora:$pa_config->{'dbhost'}:3306",$pa_config->{"dbuser"}, $pa_config->{"dbpass"},{ RaiseError => 1, AutoCommit => 1 });

	while ( 1 ) { # Pandora module processor main loop
		opendir(DIR, $pa_config->{'incomingdir'} ) or die "[FATAL] Cannot open Incoming data directory at $pa_config->{'incomingdir'}: $!";
 		while (defined($onefile = readdir(DIR))){
   			push @file_list,$onefile; 	# Push in a stack all directory entries for this loop
 		}
        	while (defined($onefile = pop @file_list)) {	# Begin to process files
			threads->yield;
                	$file_data = "$pa_config->{'incomingdir'}/$onefile";
                	next if $onefile =~ /^\.\.?$/;     # Skip . and .. directory
                	if ( $onefile =~ /([\-\:\;\.\,\_\s\a\*\=\(\)\/a-zA-Z0-9]*).data/ ) {  # First filter any file that doesnt like ".data"
   				$agent_filename = $1;
   				$file_md5 = "$pa_config->{'incomingdir'}/$agent_filename.checksum";
				if (( -e $file_md5 ) or ($pa_config->{'pandora_check'} == 0)){ # If check is disabled, ignore if file_md5 exists
    					# Comprobamos integridad
    					my $check_result;
					$check_result = md5check ($file_data,$file_md5);
					if (($pa_config->{'pandora_check'} == 0) || ($check_result == 1)){
						# PERL cannot "free" memory on user demmand, so 
						# we are declaring $config hash reference in inner loop
						# to force PERL system to realloc memory in each loop.
						# In Pandora 1.1 in "standard" PERL Implementations, we could
						# have a memory leak problem. This is solved now :-)
						# Source : http://www.rocketaware.com/perl/perlfaq3/
                                 		# Procesa_Datos its the main function to process datafile
						my $config; # Hash Reference, used to store XML data
                                        	# But first we needed to verify integrity of data file
                                        	if ($pa_config->{'pandora_check'} == 1){
							logger ($pa_config, "Integrity of Datafile using MD5 is verified: $file_data",3);
						}
     						eval { # XML Processing error catching procedure. Critical due XML was no validated
                                  			logger ($pa_config, "Ready to parse $file_data",4);
                                  			$config = XMLin($file_data, forcearray=>'module');
      							procesa_datos($pa_config, $config, $dbh); 
                          			};
                          			if ($@) {
                                   			logger ($pa_config, "[ERROR] Error processing XML contents in $file_data",0);
                                   			copy ($file_data,$file_data."_BAD");
                                   			if (($pa_config->{'pandora_check'} == 1) && ( -e $file_md5 )) {
								copy ($file_md5,$file_md5."_BAD");
							}
                          			}
						undef $config;
                                        	# If _everything_ its ok..
						# delete files
                                        	unlink ($file_data);
                                        	if ( -e $file_md5 ) {
							unlink ($file_md5);
						}
                                	} else { # md5 check fails
     						logger ( $pa_config, "[ERROR] MD5 Checksum failed! for $file_data",0);
						# delete files
                                        	unlink ($file_data);
                                        	if ( -e $file_md5 ) {
							unlink ($file_md5);
						}
    					}
   				} # No existe fichero de checksum, ignoramos el archivo
                	}
        	}
        	closedir(DIR);
		threads->yield;
        	sleep $pa_config->{"server_threshold"};
	}
} # End of main loop function

#################################################################################
## SUB pandora_keepalived
## Pandora Keepalive alert daemon subsystem
##################################################################################

sub pandora_keepalived {
	my $pa_config = $_[0];
	my $dbh = DBI->connect("DBI:mysql:pandora:$pa_config->{'dbhost'}:3306",$pa_config->{"dbuser"}, $pa_config->{"dbpass"},{ RaiseError => 1, AutoCommit => 1 });
	while (1){
		sleep $pa_config->{"server_threshold"};
		threads->yield;
		keep_alive_check($pa_config,$dbh);
		pandora_serverkeepaliver($pa_config,0,$dbh); # 0 for dataserver
	}
}


#################################################################################
## SUB keep_alive_check  ()
## Calculate a global keep alive check for agents without data and an alert defined 
##################################################################################

sub keep_alive_check {
        # Buscamos si existe una alerta definida para cada item de la tablacombinacion agente/modulo
	my $pa_config = $_[0];
	my $dbh = $_[1];
    	
    	my $query_idag = "select * from talerta_agente_modulo";
    	my $s_idag = $dbh->prepare($query_idag);
        $s_idag ->execute;
        my @data; my $err; my $flag;
	
	if ($s_idag->rows != 0) {
	while (@data = $s_idag->fetchrow_array()) {
		threads->yield;
		my $id_aam = $data[0];
		my $id_alerta = $data[2];
		my $id_agente_modulo = $data[1];
		# Only checks keep_alive special modules (-1 on type)
		if (dame_id_tipo_modulo($pa_config, $id_agente_modulo, $dbh) == -1) {
			my $campo1 = $data[3];
			my $campo2 = $data[4];
			my $campo3 = $data[5];
			my $dis_max = $data[7];
			my $dis_min = $data[8];
			my $threshold = $data[9];
			my $last_fired = $data[10];
			my $max_alerts = $data[11];
			my $times_fired = $data[12];
			my $alert_fired = 0;
			my $fecha_ultima_alerta = ParseDate($last_fired);
			my $ahora_mysql = &UnixDate("today","%Y-%m-%d %H:%M:%S");
			my $timestamp = $ahora_mysql;
			my $fecha_actual = ParseDate( $ahora_mysql );
			# If we need to update MYSQL last_fired will use $ahora_mysql

			# Calculate if INTERVAL x2 for this agent is bigger than sub last contact date with actual date
			my $nombre_agente = dame_nombreagente_agentemodulo($pa_config, $id_agente_modulo, $dbh);
			my $id_agente = dame_agente_id($pa_config, $nombre_agente, $dbh);
			if (dame_desactivado($pa_config, $id_agente, $dbh) == 0){
				my $fecha_ultimocontacto = dame_ultimo_contacto($pa_config,$id_agente,$dbh);
				my $intervalo = dame_intervalo($pa_config, $id_agente, $dbh); # Seconds
				my $intervalo_2 = $intervalo*2;
				$fecha_ultimocontacto = ParseDate($fecha_ultimocontacto);
				my $fecha_limite = DateCalc($fecha_ultimocontacto,"+ $intervalo_2 seconds",\$err);
				$flag = Date_Cmp($fecha_actual,$fecha_limite);
				if ( $flag >= 0) { $alert_fired = 1 } else { $alert_fired=0 } ;
				if (( $flag >= 0 ) && ($max_alerts >= $times_fired)){ # Calculate if max_alerts for this time is exhausted
					# Alert Trigger is ON !
					# Check if alert is fired by event-success
					my $time_threshold = $threshold; # from defined alert 
					$fecha_limite = DateCalc($fecha_ultima_alerta,"+ $time_threshold seconds",\$err);
					$flag = Date_Cmp($fecha_actual,$fecha_limite);
					if ( $flag >= 0 ) {
						# Alert Trigger is fired by time-threshold
						# Get "command" string from Alert Definition in DB
						my $comando = dame_comando_alerta($pa_config, $id_alerta, $dbh);
						$times_fired = $times_fired + 1;
						$query_idag = "update talerta_agente_modulo set times_fired = $times_fired, last_fired = '$ahora_mysql' where id_aam = $id_aam ";
						my $s3_idag = $dbh->prepare($query_idag);
						$s3_idag ->execute;
						$s3_idag->finish();
						my $nombre_agente = dame_nombreagente_agentemodulo($pa_config,$id_agente_modulo,$dbh);
						logger($pa_config, "Alert (KeepAlive) TRIGGERED for $nombre_agente ! ",1);
						my $id_grupo = dame_grupo_agente($pa_config,$id_agente,$dbh);
						my $descripcion = "Agent down";
						pandora_event($pa_config, $descripcion,$id_grupo,$id_agente,$dbh);
						if ($id_alerta > 0){ # id_alerta 0 is reserved for internal audit system
							$comando =~ s/_field1_/"$campo1"/gi;
							$comando =~ s/_field2_/"$campo2"/gi;
							$comando =~ s/_field3_/"$campo3"/gi;
							$comando =~ s/_agent_/$nombre_agente/gi;
							$comando =~ s/_timestamp_/$timestamp/gi;
							$comando =~ s/\^M/\r\n/g; # Replace Carriage rerturn and line feed
							# Clean up some "tricky" characters
							$comando =~ s/&gt;/>/g;
							# EXECUTING COMMAND !!!
							eval {
								my $exit_value = system ($comando);
								$exit_value  = $? >> 8; # Shift 8 bits to get a "classic" errorlevel
								if ($exit_value != 0) {
									logger( $pa_config,"Executed command for triggered alert had errors (errorlevel =! 0) ",0);
								}
							};
							if ($@){
								logger($pa_config, "ERROR: Error executing alert command  ( $comando )",1);
								logger($pa_config, "ERROR Code: $@",2);
							}
						} else { # id_alerta = 0, is a internal system audit
								logger($pa_config, "Internal audit lauch for agent name $nombre_agente",2);
								$campo1 =~ s/_timestamp_/$timestamp/g;
								pandora_audit ($pa_config, $campo1, $nombre_agente, "User Alert",$dbh);
						}
					} # if $flag >=0 (for time-firing calculation)
					} elsif (($alert_fired == 0) && ($times_fired != 0)){ # If alert doesnt fired and fired counter isnt zero, lets reset counter
						$query_idag = "update talerta_agente_modulo set times_fired = 0 where id_aam = $id_aam ";
						my $s3_idag = $dbh->prepare($query_idag);
						$s3_idag ->execute;
						$s3_idag ->finish();
						my $id_grupo = dame_grupo_agente($pa_config, $id_agente, $dbh);
						my $descripcion = "Agent up";
						pandora_event($pa_config, $descripcion,$id_grupo,$id_agente, $dbh);
					} # if $flag >= 0 (for time-threshold)
				} # Disabled agent
			} #if (dame_id_tipo_modulo($id_agente_modulo) == -1)
		} # While
	} # if ($s_idag->rows != 0) 
	$s_idag->finish();
}

#################################################################################
## SUB procesa_datos (par1)
## Procesa un paquete de datos (XML preprocesado)
##################################################################################
## param_1 : Nombre de la estructura contenedora de datos (XML)

sub procesa_datos {
   	my $pa_config = $_[0];
    	my $datos = $_[1]; 
	my $dbh = $_[2];

	my $tipo_modulo; my $agent_name; 
	my $timestamp; my $interval; 
	my $os_version; my $agent_version;
    	my $id_agente; my $module_name;
	$agent_name = $datos->{'agent_name'};
	$timestamp = $datos->{'timestamp'};
	$agent_version = $datos->{'version'};
	$interval = $datos->{'interval'};
	$os_version = $datos->{'os_version'};
	# Check for parameteres, not all version agentes gives the same parameters ! 
	if (length($interval) == 0){ $interval = -1; } # No update for interval !      
	if (length($os_version) == 0){ $os_version = "N/A"; } # No update for interval ! 
	if (defined $agent_name){
		$id_agente = dame_agente_id($pa_config,$agent_name,$dbh);
		if ($id_agente > 0) {
			pandora_lastagentcontact($pa_config, $timestamp, $agent_name, $os_version, $agent_version, $interval, $dbh);
			foreach my $part(@{$datos->{module}}) {
				$tipo_modulo = $part->{type}->[0];
				$module_name = $part->{name}->[0];
				logger($pa_config, "Processing packet Name ( ".$module_name." ) type ( $tipo_modulo ) for agent ( $agent_name )",5);
				if ($tipo_modulo eq 'generic_data') {
					module_generic_data($pa_config, $part,$timestamp,$agent_name,"generic_data", $dbh);
				}
				elsif ($tipo_modulo eq 'generic_data_inc') {
					module_generic_data_inc($pa_config, $part,$timestamp,$agent_name,"generic_data_inc", $dbh);
				}
				elsif ($tipo_modulo eq 'generic_data_string') {
					module_generic_data_string($pa_config,$part,$timestamp,$agent_name,"generic_data_string", $dbh);
				}
				elsif ($tipo_modulo eq 'generic_proc') {
					module_generic_proc($pa_config,$part,$timestamp,$agent_name,"generic_proc", $dbh);
				}
				else {
					logger($pa_config,"ERROR: Received data from an unknown module ($tipo_modulo)",2);
				}
			}
		} else {
			logger($pa_config,"ERROR: There is no agent defined with name $agent_name ($id_agente)",2);
		}
	} else {
		logger($pa_config,"ERROR: Received data from an unnamed agent",1);
	}
}
