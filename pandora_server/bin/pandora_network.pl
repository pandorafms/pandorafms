#!/usr/bin/perl
##################################################################################
# Pandora Network Server
##################################################################################
# Copyright (c) 2004-2006 Sancho Lerena, slerena@gmail.com
# Copyright (c) 2005-2006 Artica Soluciones Tecnologicas S.L
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
##################################################################################

# Includes list
use strict;
use warnings;

use Date::Manip;                	# Needed to manipulate DateTime formats of input, output and compare
use Time::Local;                	# DateTime basic manipulation
use Net::Ping::External qw(ping);	# For ICMP conectivity
use Net::Ping;				# For ICMP latency
use Time::HiRes;			# For high precission timedate functions (Net::Ping)
use IO::Socket;				# For TCP/UDP access
use SNMP;				# For SNMP access (libnet-snmp-perl package!

# Pandora Modules
use pandora_config;
use pandora_tools;
use pandora_db;

# FLUSH in each IO (only for debug, very slooow)
# ENABLED in DEBUGMODE
# DISABLE FOR PRODUCTION
$| = 1;

my %pa_config;

# Inicio del bucle principal de programa
pandora_init(\%pa_config, "Pandora Network Server");
# Read config file for Global variables
pandora_loadconfig (\%pa_config,1);
# Audit server starting

pandora_audit (\%pa_config, "Pandora Network Daemon starting", "SYSTEM", "System");

# Daemonize of configured
if ( $pa_config{"daemon"} eq "1" ) {
	print " [*] Backgrounding...\n";
	&daemonize;
}

# Runs main program (have a infinite loop inside)
pandora_network_subsystem(\%pa_config);

#------------------------------------------------------------------------------------
#------------------------------------------------------------------------------------
#------------------------------------------------------------------------------------
#---------------------  Main Perl Code below this line-------------------------------
#------------------------------------------------------------------------------------
#------------------------------------------------------------------------------------
#------------------------------------------------------------------------------------

##########################################################################################
# SUB pandora_network_subsystem
# Subsystem to process network modules
# This module runs each X seconds (server threshold) checking for network modules status
##########################################################################################

sub pandora_network_subsystem {
        # Init vars
	my $pa_config = $_[0];
	# Connect ONCE to Database, we pass DBI handler to all subprocess.
	my $dbh = DBI->connect("DBI:mysql:pandora:$pa_config->{'dbhost'}:3306", $pa_config->{'dbuser'}, $pa_config->{'dbpass'}, { RaiseError => 1, AutoCommit => 1 });

	my $id_agente;
	my $id_agente_modulo;
	my $id_tipo_modulo;
	my $max; my $min; my $module_interval;
	my $nombre; my $tcp_port; my $tcp_rcv; my $tcp_send; my $snmp_oid;
	my $snmp_community; my $ip_target; my $id_module_group;
	my $timestamp_viejo; # Almacena el timestamp del campo de la tabla tagente_estado
	my $id_agente_estado; # ID de la tabla de tagente_estado (para hacer el update mas fino)
	my $estado_cambio; # store tagente_estado cambio field
	my $estado_estado; # Store tagente_estado estado field
	my $agent_name; # Agent name
	my $agent_interval; # Agent interval
	my $agent_disabled; # Contains disabled field of tagente
	my $module_result; # Result of module exec.
	my $module_data; # data for modulestado and dbInsert
	my $agent_osdata; # Agent os data
	my $server_id; # My server id
	my $flag;
	my @sql_data2;
	my @sql_data;
	my @sql_data3;
	my $query_sql; my $query_sql2; my $query_sql3;
	my $exec_sql; my $exec_sql2;  my $exec_sql3;
	my $buffer;

	while ( 1 ) {
		logger ($pa_config,"Loop in Network Module Subsystem",10);
		# For each element
		# -Leo un modulo de tipo red (tipo 5, 6 o 7) o categoria grupo 2
		# -Leo su ultima entrada en la tabla tagente_modulo
		# -si timestamp  de tagente_estado + module_interval <= timestamp actual
		#     ejecuto el modulo, le doy 15 sec y contino.
		#     si ejecuta bien, grabo datos y estado
		# siguiente elemento
		# Calculate ID Agent from a select where module_type (id_tipo_modulo) > 4 (network modules)
		# Check for MASTER SERVERS only: check another agents if their servers are gone
		$server_id = dame_server_id($pa_config, $pa_config->{'servername'}, $dbh);
		$buffer = "";		
		if ($pa_config->{"pandora_master"} == 1){ 
			my $id_server;
			# I am the master, we need to check another agents
			# if their server is down
			# So look for servers down and keep their id_server
			$query_sql2 = "select * from tagente where disabled = 0 and id_server != $server_id ";
			$exec_sql2 = $dbh->prepare($query_sql2);
			$exec_sql2 ->execute;
			while (@sql_data2 = $exec_sql2->fetchrow_array()) {
				$id_agente = $sql_data2[0];
				$id_server = $sql_data2[14];
				# Check if Network Server of that agent is down
				if (give_networkserver_status($pa_config, $id_server, $dbh) == 0) {
					# I'm the master server, and there is an agent
					# with its agent down, so ADD to list
					$buffer = $buffer." OR id_agente = $id_agente ";
					logger ($pa_config, "Added id_agente $id_agente for Master Network Server ".$pa_config->{"servername"}." agent pool",5);
				}
			}
			$exec_sql2->finish();
		}	
		# First: Checkout for enabled agents owned by this server
		$query_sql2 = "select * from tagente where ( disabled = 0 and id_server = $server_id ) ".$buffer;
		$exec_sql2 = $dbh->prepare($query_sql2);
		$exec_sql2 ->execute;
		while (@sql_data2 = $exec_sql2->fetchrow_array()) {
			$id_agente = $sql_data2[0];
			$agent_name = $sql_data2[1];
			$agent_interval = $sql_data2[7];
			$agent_disabled = $sql_data2[12];
			$agent_osdata =$sql_data2[8];

			# Second: Checkout for agent_modules with type > 4 (network modules) and 
			# owned by our selected agent
			$query_sql = "select * from tagente_modulo where id_tipo_modulo > 4 and id_agente = $id_agente";
			$exec_sql = $dbh->prepare($query_sql);
			$exec_sql ->execute; 
			while (@sql_data = $exec_sql->fetchrow_array()) {
				$id_agente_modulo = $sql_data[0];
				$id_agente= $sql_data[1];
				$id_tipo_modulo = $sql_data[2];
				$nombre = $sql_data[4];
				$min = $sql_data[6];
				$max = $sql_data[5];
				$module_interval = $sql_data[7];
				$tcp_port = $sql_data[8];
				$tcp_send = $sql_data[9];
				$tcp_rcv = $sql_data[10];
				$snmp_community = $sql_data[11];
				$snmp_oid = $sql_data[12];
				$ip_target = $sql_data[13];
				$id_module_group = $sql_data[14];
				$flag = $sql_data[15];
				if ($module_interval == 0) { # If module interval not defined, get value for agent interval instead
					$module_interval = $agent_interval;
				}
				# Look for an entry in tagente_estado 
				$query_sql3 = "select * from tagente_estado where id_agente_modulo = $id_agente_modulo";
				$exec_sql3 = $dbh->prepare($query_sql3);
				$exec_sql3 ->execute;
				if ($exec_sql3->rows > 0) { # Exist entry in tagente_estado
					@sql_data3 = $exec_sql3->fetchrow_array();
					$timestamp_viejo = $sql_data3[7]; # Now use last_try (for network agents)
					$id_agente_estado = $sql_data3[0];
					$estado_cambio = $sql_data3[4];
					$estado_estado = $sql_data3[5];
				} else {
					$id_agente_estado = -1;
					$estado_estado = -1;
				}
				$exec_sql3->finish();
				# if timestamp of tagente_modulo + module_interval <= timestamp actual, exec module
				my $fecha_estatus = ParseDate($timestamp_viejo);
				my $fecha_mysql = &UnixDate("today","%Y-%m-%d %H:%M:%S");       # If we need to updat	
				my $fecha_actual = ParseDate( $fecha_mysql );
				my $err; my $fecha_flag;
				my $fecha_limite = DateCalc($fecha_estatus,"+ $module_interval seconds",\$err);
				# Comprobar que est�por encima (sumando esta) del minimo de alertas
				# Comprobar que est�por debajo (sumando esta) del m�imo de alertas
				$fecha_flag = Date_Cmp($fecha_actual,$fecha_limite);
				if (( $fecha_flag >= 0) || ($flag == 1)) { # Exec module, we are out time limit !
					# thread
					# my $threadid = threads->new( \&exec_network_module, $id_agente, $id_agente_estado, $id_tipo_modulo, $fecha_mysql, $nombre, $min, $max, $agent_interval, $tcp_port, $tcp_send, $tcp_rcv, $snmp_community, $snmp_oid, $ip_target, $module_result, $module_data, $estado_cambio, $estado_estado, $agent_name, $agent_osdata, $id_agente_modulo, $pa_config, $dbh);
					# $threadid->detach;
					if ($flag == 1){ # Reset flag to 0
						$query_sql3 = "update tagente_modulo set flag=0 where id_agente_modulo = $id_agente_modulo";
						$exec_sql3 = $dbh->prepare($query_sql3);
						$exec_sql3 ->execute;
						$exec_sql3->finish();
					}
					logger ($pa_config, "Network Module Subsystem (Single): Exec Netmodule '$nombre'",5);
					exec_network_module( $id_agente, $id_agente_estado, $id_tipo_modulo, $fecha_mysql, $nombre, $min, $max, $agent_interval, $tcp_port, $tcp_send, $tcp_rcv, $snmp_community, $snmp_oid, $ip_target, $module_result, $module_data, $estado_cambio, $estado_estado, $agent_name, $agent_osdata, $id_agente_modulo, $pa_config, $dbh);
					
				} # Timelimit if
			} # while
			$exec_sql->finish();
		}	
		$exec_sql2->finish();
		pandora_serverkeepaliver($pa_config,$dbh);
	sleep($pa_config->{"server_threshold"});
	}
	$dbh->disconnect();
}

##########################################################################################
# SUB pandora_query_snmp (pa_config, oid, community, target, error, dbh)
# Makes a call to SNMP modules to get a value,
##########################################################################################
sub pandora_query_snmp {
	my $pa_config = $_[0];
	my $snmp_oid = $_[1];
	my $snmp_community =$_[2];
	my $snmp_target = $_[3];
	# $_[4] contains error var.
	my $dbh = $_[5];
	my $output ="";
	$ENV{'MIBS'}="ALL";  #Load all available MIBs
	my $SESSION = new SNMP::Session (DestHost =>  $snmp_target, 
                                Community => $snmp_community,
                                Version => 1);
   	if ((!defined($SESSION))&& ($snmp_community != "") && ($snmp_oid != "")) {
      		logger($pa_config, "SNMP ERROR SESSION", 4);
		$_[4]="1";
   	} else {
		my $OIDLIST =  new SNMP::VarList([$snmp_oid]);
		# Pass the VarList to getnext building an array of the output
		my @OIDINFO = $SESSION->getnext($OIDLIST);	
		$output = $OIDINFO[0];
		if ((!defined($output)) || ($output eq "")){
			$_[4]="1";
		} else {
			$_[4]="0";
		}
	}
	# Too much DEBUG for me :-)
	# logger($pa_config, "SNMP RESULT $snmp_oid $snmp_target - > $output \n",10);
	return $output;
}

######################################################################################
# SUB exec_network_module (many parameters...)
# Execute network module task in separated thread
######################################################################################
sub exec_network_module {
	my $id_agente = $_[0];
	my $id_agente_estado = $_[1];
	my $id_tipo_modulo= $_[2];
	my $fecha_mysql= $_[3];
	my $nombre= $_[4];
	my $min= $_[5];
	my $max= $_[6];
	my $agent_interval= $_[7];
	my $tcp_port = $_[8];
	my $tcp_send = $_[9];
	my $tcp_rcv = $_[10];
	my $mysnmp_community = $_[11];
	my $mysnmp_oid = $_[12];
	my $ip_target = $_[13];
	my $module_result = $_[14];
	my $module_data = $_[15];
	my $estado_cambio = $_[16];
	my $estado_estado = $_[17];
	my $agent_name = $_[18];
	my $agent_osdata = $_[19];
	my $id_agente_modulo = $_[20];
	my $pa_config = $_[21];
	my $dbh = $_[22];
	my $error = "1";
	my $query_sql2;
	my $temp=0; my $tam; my $temp2;
	$module_result = 1; # Fail by default

	# ICMP Modules
	# ------------
	if ($id_tipo_modulo == 6){ # ICMP (Connectivity only: Boolean)
		$temp = ping(hostname => $ip_target, timeout => $pa_config->{'networktimeout'});
		if ($temp eq "1" ){
			$module_result = 0; # Successful
			$module_data = 1;
		} else {
			$module_result = 0; # Error, cannot connect
			$module_data = 0;
		}
		
	} elsif ($id_tipo_modulo == 7){ # ICMP (data for latency in ms)
		# This module only could be executed if executed as root
		if ($> == 0){
			my $nm = Net::Ping->new("icmp");
			my $icmp_return;
			my $icmp_reply;
			my $icmp_ip;
			$nm->hires();
			($icmp_return, $icmp_reply, $icmp_ip) = $nm->ping ($ip_target,$pa_config->{"networktimeout"});
			if ($icmp_return) {
				$module_data = $icmp_reply * 1000; # milliseconds
				$module_result = 0; # Successful		
			} else {
				$module_result = 1; # Error.
				$module_data = 0;
			}
			$nm->close();
		} else {
			$module_result = 0; # Done but, with zero value
			$module_data = 0;
		}
	# SNMP Modules (Proc, inc, data, string)
	# ------------
	} elsif (($id_tipo_modulo == 15) || ($id_tipo_modulo == 18) || ($id_tipo_modulo == 16) || ($id_tipo_modulo == 17)) { # SNMP module
		if ($mysnmp_oid ne ""){
			$temp2 = pandora_query_snmp ($pa_config, $mysnmp_oid, $mysnmp_community, $ip_target, $error, $dbh);
		} else {
			 $error = 1
		}
		
		# SUB pandora_query_snmp (pa_config, oid, community, target, error, dbh)
		if ($error == 0) { # A correct SNMP Query
			$module_result = 0;
			if (($id_tipo_modulo == 15) ||  ($id_tipo_modulo == 18) || ($id_tipo_modulo == 16) ){ # Numeric SNMP modules and PROC
				if ($temp2 =~ /[A-Za-z\.\,\-\/\\\(\)\[\]]/){
					$module_result = 1; # Alphanumeric dada, not numeric
				} else {
					$module_data = int($temp2);
					$module_result = 0; # Successful
				} 
			} else { # String SNMP
				$module_data = $temp2;
				$module_result=0;
			}
		} else { # Failed SNMP-GET
			$module_result = 1; # No data, cannot connect
		}
	# TCP Module
	# ----------
	} elsif (($id_tipo_modulo == 8) || ($id_tipo_modulo == 9) || ($id_tipo_modulo == 10) || ($id_tipo_modulo == 11)) { # TCP Module
		if (($tcp_port < 65536) && ($tcp_port > 0)){ # Port check
			my $handle=IO::Socket::INET->new(
				Proto=>"tcp",
				PeerAddr=>$ip_target,
				Timeout=>$pa_config->{'networktimeout'},
				PeerPort=>$tcp_port,
				Blocking=>0 );
			if (defined($handle)){
				if ($tcp_send ne ""){ # its Expected to sending data ?
					# Send data
					$handle->autoflush(1);
					$tcp_send =~ s/\^M/\r\n/g; # Replace Carriage rerturn and line feed de los guevos
					$handle->send($tcp_send);
				}
				if (($tcp_rcv ne "") || ($id_tipo_modulo == 10) || ($id_tipo_modulo ==8) || ($id_tipo_modulo == 11)) { # its Expected to receive data ?
					# Receive data, non-blocking !!!! (VERY IMPORTANT!)
					for ($tam=0; $tam<($pa_config->{'networktimeout'}/2); $tam++){
						$handle->recv($temp,16000,0x40);
			    			$temp2 = $temp2.$temp;
						if ($temp ne ""){
							$tam++; # If doesnt receive data, increase counter
						}
						sleep(1);
					}	
					if ($id_tipo_modulo == 9){ # only for TCP Proc
						if ($temp2 =~ /$tcp_rcv/i){ # String match !
							$module_data = 1;
							$module_result =0;
						} else {
							$module_data = 0;
							$module_result =0;
						}
						
					} elsif ($id_tipo_modulo == 10 ){ # TCP String (no int conversion)!
						$module_data = $temp2;
						$module_result =0;
					} else { # TCP Data numeric (inc or data)
						if ($temp2 ne ""){ # COMO OSTIAS PUEDO SABER EN PERL
								   # EL TIPO DEL CONTENIDO DE UNA VARIABLE
								   #  ODIO EL PUTO PERL !!!
							if ($temp2 =~ /[A-Za-z\.\,\-\/\\\(\)\[\]]/){
 								$module_result=1; # Pequeña ñapita
								$module_data = 0; # Datos invalidos
							} else {
								$module_data = int($temp2); 
								$module_result = 0; # Successful	
							}
						}
						$module_result = 0; # Successful
					}
				} else { # No expected data to receive, if connected and tcp_proc type successful
					if ($id_tipo_modulo == 9){ # TCP Proc
						$module_result = 0; 
						$module_data = 1; 
					}
				}
				$handle->close();
			} else { # Cannot connect (open sock failed)
				$module_result = 1; # Fail
				if ($id_tipo_modulo == 9){ # TCP Proc
					$module_result = 0; 
					$module_data = 0; # Failed, but data exists
				}
			}
		} else { 
			$module_result = 1; 
		}
   	} elsif ($id_tipo_modulo == 12){ # UDP Proc 
		if (($tcp_port < 65536) && ($tcp_port > 0)){
			my $p = Net::Ping->new("udp", $pa_config->{"networktimeout"});
			my $icmp_return;
			my $icmp_reply;
			my $icmp_ip;
			($icmp_return, $icmp_reply, $icmp_ip) = $p->ping ($ip_target,$pa_config->{"networktimeout"});
			if ($icmp_return) {
				# Return value	
				$module_result = 0; 
				$module_data = 1;
			} else {
				$module_result = 0; # Cannot connect
				$module_data = 0;
			}
   			$p->close();
		} else {
 			$module_result = 1;  
		}
	}
	# module_generic_data_inc (part, timestamp, agent_name)
	# recreate hash for module_generic functions
	if ($module_result == 0) {
		my %part;
		$part{'name'}[0]=$nombre;
		$part{'description'}[0]="";
		$part{'data'}[0]=$module_data;
		$part{'max'}[0]=$max;
		$part{'min'}[0]=$min;

		my $tipo_modulo = dame_nombretipomodulo_idagentemodulo($pa_config, $id_tipo_modulo,$dbh);
		if (($tipo_modulo eq 'remote_snmp') || ($tipo_modulo eq 'remote_icmp') || ($tipo_modulo eq 'remote_tcp') || ($tipo_modulo eq 'remote_udp'))  {
			module_generic_data($pa_config, \%part,$fecha_mysql,$agent_name,$tipo_modulo,$dbh);
		}
		elsif ($tipo_modulo =~ /\_inc/ ) {
			module_generic_data_inc($pa_config, \%part,$fecha_mysql,$agent_name,$tipo_modulo,$dbh);
		}
		elsif ($tipo_modulo =~ /\_string/) {
			module_generic_data_string($pa_config, \%part,$fecha_mysql,$agent_name,$tipo_modulo,$dbh);
		}
		elsif ($tipo_modulo =~ /\_proc/){
			module_generic_proc($pa_config, \%part,$fecha_mysql,$agent_name,$tipo_modulo,$dbh);
		}
		else {
			logger ($pa_config, "Problem with unknown module type '$tipo_modulo'",0);
			goto skipdb_execmod;
		}
		# Update agent last contact
		# Insert Pandora version as agent version
		pandora_lastagentcontact ($pa_config,$fecha_mysql,$agent_name,$agent_osdata,$pa_config->{'version'},$agent_interval,$dbh);
	} else {
		# Modules who cannot connect or something go bad, update last_try field
		my $timestamp = &UnixDate("today","%Y-%m-%d %H:%M:%S");
		my $query_act = "update tagente_estado set last_try = '$timestamp' where id_agente_estado = $id_agente_estado ";
		my $a_idages = $dbh->prepare($query_act);
		$a_idages->execute;
		$a_idages->finish();
	}

skipdb_execmod:
	#$dbh->disconnect();
}
