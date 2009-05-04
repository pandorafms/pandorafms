package PandoraFMS::NetworkServer;
##########################################################################
# Pandora FMS Network Server.
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

use threads;
use threads::shared;
use Thread::Semaphore;

use IO::Socket::INET;
use Net::Ping;
use POSIX qw(strftime);

use PandoraFMS::Tools;
use PandoraFMS::DB;
use PandoraFMS::Core;
use PandoraFMS::ProducerConsumerServer;

# Inherits from PandoraFMS::ProducerConsumerServer
our @ISA = qw(PandoraFMS::ProducerConsumerServer);

# Global variables
my @TaskQueue :shared;
my %PendingTasks :shared;
my $Sem :shared = new Thread::Semaphore;
my $TaskSem :shared = new Thread::Semaphore (0);

########################################################################################
# Network Server class constructor.
########################################################################################
sub new ($$$) {
	my ($class, $config, $dbh) = @_;

	return undef unless $config->{'networkserver'} == 1;

	if (! -e $config->{'snmpget'}) {
		logger ($config, ' [E] ' . $config->{'snmpget'} . " needed by Pandora FMS Network Server not found.", 0);
		print ' [E] ' . $config->{'snmpget'} . " needed by Pandora FMS Network Server not found.\n\n";
		return undef;
	}

	# Call the constructor of the parent class
	my $self = $class->SUPER::new($config, 1, \&PandoraFMS::NetworkServer::data_producer, \&PandoraFMS::NetworkServer::data_consumer, $dbh);

    bless $self, $class;
    return $self;
}

###############################################################################
# Run.
###############################################################################
sub run ($) {
	my $self = shift;
	my $pa_config = $self->getConfig ();

	print " [*] Starting Pandora FMS Network Server. \n";
	$self->setNumThreads ($pa_config->{'network_threads'});
	$self->SUPER::run (\@TaskQueue, \%PendingTasks, $Sem, $TaskSem);
}

###############################################################################
# Data producer.
###############################################################################
sub data_producer ($) {
	my $self = shift;
	my ($pa_config, $dbh) = ($self->getConfig (), $self->getDBH ());

	my @tasks;
	my @rows;

	if ($pa_config->{'pandora_master'} == 0) {
		@rows = get_db_rows ($dbh, 'SELECT tagente_modulo.id_agente_modulo, tagente_modulo.flag, UNIX_TIMESTAMP() - tagente_estado.current_interval - tagente_estado.last_execution_try AS time_left  
		FROM tagente, tagente_modulo, tagente_estado
		WHERE server_name = ?
		AND tagente_modulo.id_agente = tagente.id_agente
		AND tagente.disabled = 0
		AND tagente_modulo.id_tipo_modulo > 4
		AND tagente_modulo.id_tipo_modulo < 19
		AND tagente_modulo.disabled = 0
		AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
		AND (tagente_modulo.flag = 1 OR ((tagente_estado.last_execution_try + tagente_estado.current_interval) < UNIX_TIMESTAMP())) 
		ORDER BY tagente_modulo.flag DESC, time_left DESC, last_execution_try ASC ', $pa_config->{'servername'});
    } else {
		@rows = get_db_rows ($dbh, 'SELECT DISTINCT(tagente_modulo.id_agente_modulo), tagente_modulo.flag, tagente_estado.last_execution_try, UNIX_TIMESTAMP() - tagente_estado.current_interval - tagente_estado.last_execution_try  AS time_left  
		FROM tagente, tagente_modulo, tagente_estado
		WHERE ((server_name = ?) OR (server_name = ANY(SELECT name FROM tserver WHERE status = 0))) 
		AND tagente_modulo.id_agente = tagente.id_agente
		AND tagente.disabled = 0
		AND tagente_modulo.disabled = 0
		AND tagente_modulo.id_tipo_modulo > 4
		AND tagente_modulo.id_tipo_modulo < 19
		AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
		AND (tagente_modulo.flag = 1 OR ((tagente_estado.last_execution_try + tagente_estado.current_interval) < UNIX_TIMESTAMP()))
		ORDER BY tagente_modulo.flag DESC, time_left DESC, last_execution_try ASC', $pa_config->{'servername'});
	}

	foreach my $row (@rows) {
		
		# Reset forced execution flag
		if ($row->{'flag'} == 1) {
			db_do ($dbh, 'UPDATE tagente_modulo SET flag = 0 WHERE id_agente_modulo = ?', $row->{'id_agente_modulo'});
		}

		push (@tasks, $row->{'id_agente_modulo'});
	}

	return @tasks;
}

###############################################################################
# Data consumer.
###############################################################################
sub data_consumer ($$) {
	my ($self, $task) = @_;

	exec_network_module ($self->getConfig (), $task, $self->getServerID (), $self->getDBH ());
}

##########################################################################
# SUB pandora_query_tcp (pa_config, tcp_port. ip_target, result, data, tcp_send,
#						 tcp_rcv, id_tipo_module, dbh)
# Makes a call to TCP modules to get a value.
##########################################################################
sub pandora_query_tcp (%$$$$$$$) {
	my $pa_config = $_[0];
	my $tcp_port = $_[1];
	my $ip_target = $_[2];
	my $module_result = $_[3];
	my $module_data = $_[4];
	my $tcp_send = $_[5];
	my $tcp_rcv = $_[6];
	my $id_tipo_modulo = $_[7];

        my $counter; 
        for ($counter =0; $counter < $pa_config->{'tcp_checks'}; $counter++){
	        my $temp; my $temp2;
	        my $tam;
	        my $handle=IO::Socket::INET->new(
		        Proto=>"tcp",
		        PeerAddr=>$ip_target,
		        Timeout=>$pa_config->{'tcp_timeout'},
		        PeerPort=>$tcp_port,
		        Blocking=>0 ); # Non blocking !!, very important !
		        
	        if (defined ($handle)){
			# Multi request patch, submitted by Glen Eustace (new zealand)
			my @tcp_send = split( /\|/, $tcp_send );
			my @tcp_rcv  = split( /\|/, $tcp_rcv );

next_pair:
			$tcp_send = shift( @tcp_send );
			$tcp_rcv  = shift( @tcp_rcv );

		        if  ((defined ($tcp_send)) && ($tcp_send ne "")){ # its Expected to sending data ?
			        # Send data
			        $handle->autoflush(1);
			        $tcp_send =~ s/\^M/\r\n/g;
			        # Replace Carriage rerturn and line feed
			        $handle->send($tcp_send);
		        }
		        # we expect to receive data ? (non proc types)
		        if ((defined ($tcp_rcv)) && (($tcp_rcv ne "") || ($id_tipo_modulo == 10) || ($id_tipo_modulo ==8) || ($id_tipo_modulo == 11))) {
			        # Receive data, non-blocking !!!! (VERY IMPORTANT!)
			        $temp2 = "";
			        for ($tam=0; $tam<($pa_config->{'tcp_timeout'}); $tam++){
				        $handle->recv($temp,16000,0x40);
				        $temp2 = $temp2.$temp;
				        if ($temp ne ""){
					        $tam++; # If doesnt receive data, increase counter
				        }
				        sleep(1);
			        }
			        if ($id_tipo_modulo == 9){ # only for TCP Proc
				        if ($temp2 =~ /$tcp_rcv/i){ # String match !
						if ( @tcp_send ) { # still more pairs
							goto next_pair;
						}
 					        $$module_data = 1;
					        $$module_result = 0;
                                                $counter = $pa_config->{'tcp_checks'};
				        } else {
					        $$module_data = 0;
					        $$module_result = 0;
                                                $counter = $pa_config->{'tcp_checks'};
				        }
			        } elsif ($id_tipo_modulo == 10 ){ # TCP String (no int conversion)!
				        $$module_data = $temp2;
				        $$module_result =0;
			        } else { # TCP Data numeric (inc or data)
				        if ($temp2 ne ""){
					        if ($temp2 =~ /[A-Za-z\.\,\-\/\\\(\)\[\]]/){
						        $$module_result = 1;
						        $$module_data = 0; # invalid data
                                                        $counter = $pa_config->{'tcp_checks'};
					        } else {
						        $$module_data = int($temp2);
						        $$module_result = 0; # Successful
                                                        $counter = $pa_config->{'tcp_checks'};
					        }
				        } else {
						        $$module_result = 1; 
                                                        $$module_data = 0; # invalid data
                                                        $counter = $pa_config->{'tcp_checks'};
					        }
			        }
		        } else { # No expected data to receive, if connected and tcp_proc type successful
			        if ($id_tipo_modulo == 9){ # TCP Proc
				        $$module_result = 0;
				        $$module_data = 1;
                                        $counter = $pa_config->{'tcp_checks'};
			        }
		        }
		        $handle->close();
		        undef ($handle);
	        } else { # Cannot connect (open sock failed)
		        $$module_result = 1; # Fail
		        if ($id_tipo_modulo == 9){ # TCP Proc
			        $$module_result = 0;
			        $$module_data = 0; # Failed, but data exists
                                $counter = $pa_config->{'tcp_checks'};
		        }
	        }
        }
}

##########################################################################
# SUB pandora_query_snmp (pa_config, oid, community, target, version, error, dbh)
# Makes a call to SNMP modules to get a value,
##########################################################################
sub pandora_query_snmp (%$$$$$) {
	my $pa_config = $_[0];
	my $snmp_oid = $_[1];
	my $snmp_community =$_[2];
	my $snmp_target = $_[3];
	my $snmp_version = $_[4];
	# $_[5] contains error var.

	if ($snmp_version ne '1' && $snmp_version ne '2' 
		&& $snmp_version ne '2c' && $snmp_version ne '3') {
		$snmp_version = '1';
	}

	my $snmp_timeout = $pa_config->{"snmp_timeout"};
	my $snmp_retries = $pa_config->{'snmp_checks'};

	# TODO: Alternative version if command is not available or user select a new switch
	# with "use internal SNMP" option. At this moment, due to locks() external snmp is much faster

	$_[5] = 0;
	my $snmpget_cmd = $pa_config->{"snmpget"};
	my $output = `$snmpget_cmd -v $snmp_version -r $snmp_retries -t $snmp_timeout -OUevqt -c $snmp_community $snmp_target $snmp_oid 2>/dev/null`;
	if ($output eq ""){
		$_[5] = 1;
	}

	return $output;
}

##########################################################################
# SUB exec_network_module (paconfig, id_agente_modulo, dbh )
# Execute network module task 
##########################################################################
sub exec_network_module ($$$$) {
	my ($pa_config, $id_agente_modulo, $server_id, $dbh) = @_;
	# Init variables

	my @sql_data;
	if ((!defined($id_agente_modulo)) || ($id_agente_modulo eq "")){
		return 0;
	}
	my $module = get_db_single_row ($dbh, 'SELECT * FROM tagente_modulo WHERE id_agente_modulo = ?', $id_agente_modulo);
	if (! defined ($module)) {
		logger ($pa_config,"[ERROR] Processing data for invalid module", 0);
		return 0;
	}

	my $error = "1";
	my $query_sql2;
	my $temp=0; my $tam; my $temp2;
	my $module_result = 1; # Fail by default
	my $module_data = 0;
	my $id_agente = $module->{'id_agente'};
	my $agent_name = get_agent_name ($dbh, $id_agente);
	my $id_tipo_modulo = $module->{'id_tipo_modulo'};
	my $ip_target = $module->{'ip_target'};
	my $snmp_oid = $module->{'snmp_oid'};
	my $snmp_community = $module->{'snmp_community'};
	my $tcp_port = $module->{'tcp_port'};
	my $tcp_send = $module->{'tcp_send'};
	my $tcp_rcv = $module->{'tcp_rcv'};
	
	if ((defined($ip_target)) && ($ip_target)) {

	    # ICMP Modules
	    # ------------
	    if ($id_tipo_modulo == 6){ # ICMP (Connectivity only: Boolean)
		    $module_data = pandora_ping ($pa_config, $ip_target);
		    $module_result = 0; # Successful
	    } elsif ($id_tipo_modulo == 7){ # ICMP (data for latency in ms)
		    $module_data = pandora_ping_latency ($pa_config, $ip_target);
			$module_result = 0; # Successful
	    # SNMP Modules (Proc=18, inc, data, string)
	    # ------------
	    } elsif (($id_tipo_modulo == 15) || ($id_tipo_modulo == 18) || ($id_tipo_modulo == 16) || ($id_tipo_modulo == 17)) { # SNMP module
            if ((defined($snmp_oid)) && ($snmp_oid ne "") && (defined($snmp_community)) && ($snmp_community ne "")) { # Port check
		    	    $temp2 = pandora_query_snmp ($pa_config, $snmp_oid, $snmp_community, $ip_target, $tcp_send, $error);
		    } else {
			    $error = 1
		    }
		    if ($error == 0) { # A correct SNMP Query
			    $module_result = 0;
			    # SNMP_DATA_PROC
			    if ($id_tipo_modulo == 18){ #snmp_data_proc
                            # RFC1213-MIB where it says that: SYNTAX INTEGER { up(1), down(2), testing(3),
                            # unknown(4), dormant(5), notPresent(6), lowerLayerDown(7) } 
				    if ($temp2 != 1){ # up state is 1, down state in SNMP is 2 ....
					    $temp2 = 0;
				    }
				    $module_data = $temp2;
			    }
			    # SNMP_DATA and SNMP_DATA_INC
			    elsif (($id_tipo_modulo == 15) || ($id_tipo_modulo == 16) ){ 
				    if (!is_numeric($temp2)){		   
					    $module_result = 1; 
				    } else {
					    $module_data = $temp2;
				    } 
			    } else { # String SNMP
				    $module_data = $temp2;
			    }
		    } else { # Failed SNMP-GET
			    $module_data = 0;
			    if ($id_tipo_modulo == 18){ # snmp_proc
				    # Feature from 10Feb08. If snmp_proc_deadresponse = 1 and cannot contact by an error
				    # this is a fail monitor
				    if ($pa_config->{"snmp_proc_deadresponse"} eq "1"){
				            $module_result = 0;
				    } else {
					    $module_result = 1;
				    }
			    } else {
			            $module_result = 1; # No data, cannot connect
			    }
		    }
	    # TCP Module
	    # ----------
	    } elsif (($id_tipo_modulo == 8) || ($id_tipo_modulo == 9) || ($id_tipo_modulo == 10) || ($id_tipo_modulo == 11)) { # TCP Module
            if ((defined($tcp_port)) && ($tcp_port < 65536) && ($tcp_port > 0)) { # Port check
			    pandora_query_tcp ($pa_config, $tcp_port, $ip_target, \$module_result, \$module_data, $tcp_send, $tcp_rcv, $id_tipo_modulo);
		    } else { 
			    # Invalid port, get no check
			    $module_result = 1;
		    }
        }
    }

	# Write data section
	my $utimestamp = time ();
	my $timestamp = strftime ("%Y-%m-%d %H:%M:%S", localtime($utimestamp));

    # Is everything goes ok
	if ($module_result == 0) {
		pandora_process_module ($pa_config, $module_data, '', $module, '', $timestamp, $utimestamp, $server_id, $dbh);

		# Update agent last contact using Pandora version as agent version
		pandora_update_agent ($pa_config, $timestamp, $id_agente, $pa_config->{'servername'}.'_Net', $pa_config->{'version'}, -1, $dbh);
	
    } else {
		# Modules who cannot connect or something go bad, update last_execution_try field
		pandora_update_module_on_error ($pa_config, $id_agente_modulo, $dbh);
	}
}

1;
__END__
