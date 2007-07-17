#!/usr/bin/perl
##########################################################################
# Pandora FMS Network Server
##########################################################################
# Copyright (c) 2006-2007 Sancho Lerena, slerena@gmail.com
#           (c) 2006-2007 Artica Soluciones Tecnologicas S.L
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

# Includes list
use strict;
use warnings;

use Date::Manip;            # Needed to manipulate DateTime formats of input, output and compare
use Time::Local;            # DateTime basic manipulation
use Net::Ping;				# For ICMP latency
use Time::HiRes;			# For high precission timedate functions (Net::Ping)
use IO::Socket;				# For TCP/UDP access
use SNMP;					# For SNMP access (libsnmp-perl PACKAGE!)
use threads;
use threads::shared;

# Pandora Modules
use PandoraFMS::Config;
use PandoraFMS::Tools;
use PandoraFMS::DB;

# FLUSH in each IO (only for debug, very slooow)
# ENABLED in DEBUGMODE
# DISABLE FOR PRODUCTION
$| = 1;

my %pa_config;
my @pending_task : shared;
my %pending_task_hash : shared;
my %current_task_hash : shared;

$SIG{'TERM'} = 'pandora_shutdown';
$SIG{'INT'} = 'pandora_shutdown';

# Inicio del bucle principal de programa
pandora_init(\%pa_config, "Pandora FMS Network Server");

# Read config file for Global variables
pandora_loadconfig (\%pa_config,1);

# Audit server starting
pandora_audit (\%pa_config, "Pandora FMS Network Daemon starting", "SYSTEM", "System");

print " [*] Starting up network threads\n";

if ( $pa_config{"daemon"} eq "1" ) {
	print " [*] Backgrounding Pandora FMS Network Server process.\n";
	&daemonize;
}

# Launch now all network threads
# $ax is local thread id for this server
for (my $ax=0; $ax < $pa_config{'network_threads'}; $ax++){
	threads->new( \&pandora_network_consumer, \%pa_config, $ax);
}

# Launch now the network producer thread
threads->new( \&pandora_network_producer, \%pa_config);

print " [*] All threads loaded and running \n";
# Last thread is the main process (this process)

my $dbhost = $pa_config{'dbhost'};
my $dbname = $pa_config{'dbname'};
my $dbh = DBI->connect("DBI:mysql:$dbname:$dbhost:3306",
						$pa_config{'dbuser'},
						$pa_config{'dbpass'},
						{ RaiseError => 1, AutoCommit => 1 });


while (1) {
	pandora_serverkeepaliver (\%pa_config, 1, $dbh);
	threads->yield;
	sleep ($pa_config{"server_threshold"});
}

#------------------------------------------------------------------------------------
#------------------------------------------------------------------------------------
#------------------------------------------------------------------------------------
#---------------------  Main Perl Code below this line-----------------------
#------------------------------------------------------------------------------------
#------------------------------------------------------------------------------------
#------------------------------------------------------------------------------------

########################################################################################
# pandora_shutdown ()
# Close system
########################################################################################
sub pandora_shutdown {
	logger (\%pa_config,"Pandora FMS Network Server Shutdown by signal ",0);
	print " [*] Shutting down Pandora FMS Network Server (received signal)...\n";
	exit;
}

##########################################################################
# SUB pandora_network_subsystem
# Subsystem to process network modules
# This module runs each X seconds (server threshold) checking for network modules status
##########################################################################
sub pandora_network_consumer ($$) {
	my $pa_config = $_[0];
	my $thread_id = $_[1];

	print " [*] Starting up Network Consumer Thread # $thread_id \n";
	
	my $data_id_agent_module;
	# Create Database handler
	my $dbh = DBI->connect("DBI:mysql:$pa_config->{'dbname'}:$pa_config->{'dbhost'}:3306", $pa_config->{'dbuser'}, $pa_config->{'dbpass'}, { RaiseError => 1, AutoCommit => 1 });
	my $counter =0;
	
	while (1) {
		if ($counter > $pa_config->{'server_threshold'}) {
			sleep (1);
			$counter = 0;
		}
		
		# Take the first element on the shared queue
		# Insert this element on the current task hash
		if (scalar(@pending_task) > 0){
			{
				lock @pending_task;
				$data_id_agent_module = shift(@pending_task);
				lock %pending_task_hash;
				delete($pending_task_hash{$data_id_agent_module});
				lock %current_task_hash;
				$current_task_hash{$data_id_agent_module}=1;
			}
			# Call network execution process
#print "[D] EXECUTING $data_id_agent_module MODULE FROM CONSUMER $thread_id \n";
			exec_network_module ( $pa_config, $data_id_agent_module, $dbh);
			{
				lock %current_task_hash;
				delete($current_task_hash{$data_id_agent_module});
			}
			threads->yield;
			$counter = 0;
		} else {
			$counter ++;
		}
	}
}

sub pandora_network_producer ($) {
	my $pa_config = $_[0];
	print " [*] Starting up Network Producer Thread ...\n";

	my $dbh = DBI->connect("DBI:mysql:$pa_config->{'dbname'}:$pa_config->{'dbhost'}:3306", $pa_config->{'dbuser'}, $pa_config->{'dbpass'}, { RaiseError => 1, AutoCommit => 1 });
	
	my $server_id = $pa_config->{'server_id'};

	# Initialize variables for posterior usage
	my $query1;
	my @sql_data1;
	my $data_id_agente_modulo;
	my $data_flag;
	my $exec_sql1;
	
	while (1) {
		if ($pa_config->{"pandora_master"} != 666) {
			# Query for normal server, not MASTER server
			$query1 = "SELECT
				tagente_modulo.id_agente_modulo,
				tagente_modulo.flag
			FROM
				tagente, tagente_modulo, tagente_estado
			WHERE
				id_server = $server_id
			AND 
				tagente_modulo.id_agente = tagente.id_agente
			AND
				tagente.disabled = 0
			AND
				tagente_modulo.id_tipo_modulo > 4
			AND
				tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
			AND (
					tagente_estado.last_execution_try < (UNIX_TIMESTAMP() - tagente_estado.current_interval)
				OR 
					tagente_modulo.flag = 1
			)				
			ORDER BY
					last_execution_try ASC  ";
		} else {
			# Query for master server
			# PENDING TODO !
		}
		$exec_sql1 = $dbh->prepare($query1);
		$exec_sql1 ->execute;
		
		while (@sql_data1 = $exec_sql1->fetchrow_array()) {			
			$data_id_agente_modulo = $sql_data1[0];
			$data_flag = $sql_data1[1];
			if ((!defined($pending_task_hash{$data_id_agente_modulo})) &&
				(!defined($current_task_hash{$data_id_agente_modulo}))) {
#print "[D] PRODUCER IS INSERTING MODULE $data_id_agente_modulo \n";
				if ($data_flag == 1){
					$dbh->do("UPDATE tagente_modulo SET flag = 0 WHERE id_agente_modulo = $data_id_agente_modulo")
				}
				# Locking scope, do not remove redundant { }
				{
					lock @pending_task;
					push (@pending_task, $data_id_agente_modulo);
					lock %pending_task_hash;
					$pending_task_hash {$data_id_agente_modulo}=1;
				}
			}
		}
		$exec_sql1->finish();
		threads->yield;
		sleep($pa_config->{"server_threshold"});
	} # Main loop
}

##############################################################################
# pandora_ping_icmp (destination, timeout) - Do a ICMP scan, 1 if alive, 0 if not
##############################################################################
sub pandora_ping_icmp { 
	my $dest = $_[0];
	my $l_timeout = $_[1];
 	# temporal vars.
 	my $result = 0;
	my $result2 = 0;
 	my $p;
 
 	# Check for valid destination
 	if (!defined($dest)) {
 		return 0;
 	}
 	# Some hosts don't accept ICMP with too small payload. Use 16 Bytes
 	$p = Net::Ping->new("icmp",$l_timeout,16);
	$p->source_verify(1);

 	$result = $p->ping($dest);
	$result2 = $p->ping($dest);
 
 	# Check for valid result
 	if ((!defined($result)) || (!defined($result2))) {
 		return 0;
 	}
 
 	# Lets see the result
 	if (($result == 1) && ($result2 == 1)) {
 		$p->close();
 		return 1;
 	} else {
 		$p->close();
 		return 0;
 	}
}

##########################################################################
# SUB pandora_query_tcp (pa_config, tcp_port. ip_target, result, data, tcp_send,
#						 tcp_rcv, id_tipo_module, dbh)
# Makes a call to TCP modules to get a value.
##########################################################################
sub pandora_query_tcp (%$$$$$$$$) {
	my $pa_config = $_[0];
	my $tcp_port = $_[1];
	my $ip_target = $_[2];
	my $module_result = $_[3];
	my $module_data = $_[4];
	my $tcp_send = $_[5];
	my $tcp_rcv = $_[6];
	my $id_tipo_modulo = $_[7];
	my $dbh = $_[8];
	
	my $temp; my $temp2;
	my $tam;

	my $handle=IO::Socket::INET->new(
		Proto=>"tcp",
		PeerAddr=>$ip_target,
		Timeout=>$pa_config->{'networktimeout'},
		PeerPort=>$tcp_port,
		Blocking=>0 ); # Non blocking !!, very important !
		
	if (defined($handle)){
		if ($tcp_send ne ""){ # its Expected to sending data ?
			# Send data
			$handle->autoflush(1);
			$tcp_send =~ s/\^M/\r\n/g;
			# Replace Carriage rerturn and line feed
			$handle->send($tcp_send);
		}
		# we expect to receive data ? (non proc types)
		if (($tcp_rcv ne "") || ($id_tipo_modulo == 10) || ($id_tipo_modulo ==8) || ($id_tipo_modulo == 11)) {
			# Receive data, non-blocking !!!! (VERY IMPORTANT!)
			$temp2 = "";
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
					$$module_data = 1;
					$$module_result = 0;
				} else {
					$$module_data = 0;
					$$module_result = 0;
				}
			} elsif ($id_tipo_modulo == 10 ){ # TCP String (no int conversion)!
				$$module_data = $temp2;
				$$module_result =0;
			} else { # TCP Data numeric (inc or data)
				if ($temp2 ne ""){
					if ($temp2 =~ /[A-Za-z\.\,\-\/\\\(\)\[\]]/){
						$$module_result = 1;
						$$module_data = 0; # invalid data
					} else {
						$$module_data = int($temp2);
						$$module_result = 0; # Successful
					}
				} else {
						$$module_result = 1; 
                                                $$module_data = 0; # invalid data
					}
			}
		} else { # No expected data to receive, if connected and tcp_proc type successful
			if ($id_tipo_modulo == 9){ # TCP Proc
				$$module_result = 0;
				$$module_data = 1;
			}
		}
		$handle->close();
	} else { # Cannot connect (open sock failed)
		$$module_result = 1; # Fail
		if ($id_tipo_modulo == 9){ # TCP Proc
			$$module_result = 0;
			$$module_data = 0; # Failed, but data exists
		}
	}
}

##########################################################################
# SUB pandora_query_snmp (pa_config, oid, community, target, error, dbh)
# Makes a call to SNMP modules to get a value,
##########################################################################
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
      		logger($pa_config, "SNMP ERROR SESSION for Target $snmp_target ", 4);
		$_[4] = "1";
   	} else {
		# Perl uses different OID syntax than SNMPWALK or PHP's SNMP
		# for example:
		# SNMPv2-MIB::sysDescr for PERL SNMP
		# is equivalent to SNMPv2-MIB::sysDescr.0 in SNMP and PHP/SNMP
		# So we parse last byte and cut off if = 0 and delete 1 if != 0
		my $perl_oid = $snmp_oid;
		if ($perl_oid =~ m/(.*)\.([0-9]*)\z/){
			my $local_oid = $1;
			my $local_oid_idx = $2;
			if ($local_oid_idx == 0){
				$perl_oid = $local_oid; # Strip .0 from orig. OID
			} else {
				$local_oid_idx--;
				$perl_oid = $local_oid.".".$local_oid_idx;
			}
		}
		my $OIDLIST =  new SNMP::VarList([$perl_oid]);
		# Pass the VarList to getnext building an array of the output
		my @OIDINFO = $SESSION->getnext($OIDLIST);
		$output = $OIDINFO[0];
		if ((!defined($output)) || ($output eq "")){
			$_[4] = "1";
			return 0;
		} else {
			$_[4] = "0";
		}
	}
	return $output;
}

##########################################################################
# SUB exec_network_module (paconfig, id_agente_modulo, dbh )
# Execute network module task 
##########################################################################
sub exec_network_module {
	my $pa_config = $_[0];
	my $id_agente_modulo = $_[1];
	my $dbh = $_[2];
	# Init variables
	my $id_agente;
	my $id_tipo_modulo;
	my $nombre;
	my $min;
	my $max;
	my $module_interval;
	my $tcp_port;
	my $tcp_send;
	my $tcp_rcv;
	my $snmp_community;
	my $snmp_oid;
	my $ip_target;
	my $id_module_group;
	my $flag;
	my @sql_data;

	my $query_sql = "SELECT * FROM tagente_modulo WHERE id_agente_modulo = $id_agente_modulo";
	my $exec_sql = $dbh->prepare($query_sql);
	$exec_sql ->execute;
	if (@sql_data = $exec_sql->fetchrow_array()){
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
	}
	my $agent_name = dame_agente_nombre ($pa_config, $id_agente, $dbh);
	my $error = "1";
	my $query_sql2;
	my $temp=0; my $tam; my $temp2;
	my $module_result = 1; # Fail by default
	my $module_data = 0;

	# ICMP Modules
	# ------------
	if ($id_tipo_modulo == 6){ # ICMP (Connectivity only: Boolean)
		$temp = pandora_ping_icmp ($ip_target, $pa_config->{'networktimeout'});
		if ($temp == 1 ){
			$module_result = 0; # Successful
			$module_data = 1;
		} else {
			$module_result = 0; # If cannot connect, its down.
			$module_data = 0;
		}
	} elsif ($id_tipo_modulo == 7){ # ICMP (data for latency in ms)
		# This module only could be executed if executed as root
		if ($> == 0){
			my $nm = Net::Ping->new("icmp", $pa_config->{'networktimeout'}, 32);
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
			$module_data = 0; # This should don't happen
		}
	# SNMP Modules (Proc=18, inc, data, string)
	# ------------
	} elsif (($id_tipo_modulo == 15) || ($id_tipo_modulo == 18) || ($id_tipo_modulo == 16) || ($id_tipo_modulo == 17)) { # SNMP module
		if ($snmp_oid ne ""){
			$temp2 = pandora_query_snmp ($pa_config, $snmp_oid, $snmp_community, $ip_target, $error, $dbh);
		} else {
			 $error = 1
		}
		# SUB pandora_query_snmp (pa_config, oid, community, target, error, dbh)
		if ($error == 0) { # A correct SNMP Query
			$module_result = 0;
			# SNMP_DATA_PROC
			if ($id_tipo_modulo == 18){ #snmp_data_proc
				if ($temp2 != 1){ # up state is 1, down state in SNMP is 2 ....
					$temp2 = 0;
				}
				$module_data = $temp2;
				$module_result = 0; # Successful
			}
			# SNMP_DATA and SNMP_DATA_INC
			elsif (($id_tipo_modulo == 15) || ($id_tipo_modulo == 16) ){ 
				if ($temp2 =~ /[A-Za-z\.\,\-\/\\\(\)\[\]]/){
					$module_result = 1; # Alphanumeric data, not numeric
				} else {
					$module_data = int($temp2);
					$module_result = 0; # Successful
				} 
			} else { # String SNMP
				$module_data = $temp2;
				$module_result=0;
			}
		} else { # Failed SNMP-GET
			$module_data = 0;
			$module_result = 1; # No data, cannot connect
		}
	# TCP Module
	# ----------
	} elsif (($id_tipo_modulo == 8) || ($id_tipo_modulo == 9) || ($id_tipo_modulo == 10) || ($id_tipo_modulo == 11)) { # TCP Module
		if (($tcp_port < 65536) && ($tcp_port > 0)){ # Port check
			pandora_query_tcp ($pa_config, $tcp_port, $ip_target, \$module_result, \$module_data, $tcp_send, $tcp_rcv, $id_tipo_modulo, $dbh);
		} else { 
			$module_result = 1;
		}
   	}

	# --------------------------------------------------------
	if ($module_result == 0) {
		my %part;
		$part{'name'}[0]=$nombre;
		$part{'description'}[0]="";
		$part{'data'}[0] = $module_data;
		$part{'max'}[0] = $max;
		$part{'min'}[0] = $min;
		my $timestamp = &UnixDate("today","%Y-%m-%d %H:%M:%S");
		my $tipo_modulo = dame_nombretipomodulo_idagentemodulo ($pa_config, $id_tipo_modulo, $dbh);
		if (($tipo_modulo eq 'remote_snmp') || ($tipo_modulo eq 'remote_icmp') || ($tipo_modulo eq 'remote_tcp') || ($tipo_modulo eq 'remote_udp'))  {
			module_generic_data ($pa_config, \%part, $timestamp, $agent_name, $tipo_modulo, $dbh);
		}
		elsif ($tipo_modulo =~ /\_inc/ ) {
			module_generic_data_inc ($pa_config, \%part, $timestamp, $agent_name, $tipo_modulo, $dbh);
		}
		elsif ($tipo_modulo =~ /\_string/) {
			module_generic_data_string ($pa_config, \%part, $timestamp, $agent_name, $tipo_modulo, $dbh);
		}
		elsif ($tipo_modulo =~ /\_proc/){
			module_generic_proc ($pa_config, \%part, $timestamp, $agent_name, $tipo_modulo, $dbh);
		}
		else {
			logger ($pa_config, "Problem with unknown module type '$tipo_modulo'", 0);
			goto skipdb_execmod;
		}
		# Update agent last contact
		# Insert Pandora version as agent version
		pandora_lastagentcontact ($pa_config, $timestamp, $agent_name,  $pa_config->{'servername'}.$pa_config->{"servermode"}, $pa_config->{'version'}, -1, $dbh);
	} else { 
		# $module_result != 0)
		# Modules who cannot connect or something go bad, update last_try field
		logger ($pa_config, "Cannot obtain exec Network Module $nombre from agent $agent_name", 4);
		my $timestamp = &UnixDate("today","%Y-%m-%d %H:%M:%S");
		my $utimestamp = &UnixDate("today","%s");
		#my $query_act = "UPDATE tagente_estado SET utimestamp = $utimestamp, timestamp = '$timestamp', last_try = '$timestamp' WHERE id_agente_estado = $id_agente_estado ";
		my $query_act = "UPDATE tagente_estado SET last_execution_try = $utimestamp WHERE id_agente_modulo = $id_agente_modulo ";
		my $a_idages = $dbh->prepare($query_act);
		$a_idages->execute;
		$a_idages->finish();
	}
	
skipdb_execmod:
	#$dbh->disconnect();
}
