package PandoraFMS::NetworkServer;
##########################################################################
# Pandora FMS Network Server.
# Pandora FMS. the Flexible Monitoring System. http://www.pandorafms.org
##########################################################################
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

use threads;
use threads::shared;
use Thread::Semaphore;

use IO::Socket::INET6;
use HTML::Entities;
use POSIX qw(strftime);
use SNMP;

# Default lib dir for RPM and DEB packages
use lib '/usr/lib/perl5';

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
my $SNMPSem :shared = new Thread::Semaphore (1);

########################################################################################
# Network Server class constructor.
########################################################################################
sub new ($$$) {
	my ($class, $config, $dbh) = @_;

	return undef unless $config->{'networkserver'} == 1;

	if (! -e $config->{'snmpget'}) {
		logger ($config, ' [E] ' . $config->{'snmpget'} . " needed by Pandora FMS Network Server not found.", 1);
		print_message ($config, ' [E] ' . $config->{'snmpget'} . " needed by Pandora FMS Network Server not found.", 1);
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

	print_message ($pa_config, " [*] Starting Pandora FMS Network Server.", 1);
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
	my $network_filter = enterprise_hook ('get_network_filter', [$pa_config]);
	
	if ($pa_config->{'pandora_master'} == 0) {
		@rows = get_db_rows ($dbh, 'SELECT tagente_modulo.id_agente_modulo, tagente_modulo.flag, tagente_estado.current_interval + tagente_estado.last_execution_try AS time_left, last_execution_try
		FROM tagente, tagente_modulo, tagente_estado
		WHERE server_name = ?
		AND tagente_modulo.id_agente = tagente.id_agente
		AND tagente.disabled = 0
		AND tagente_modulo.id_tipo_modulo > 4
		AND tagente_modulo.id_tipo_modulo < 19 '
		. (defined ($network_filter) ? $network_filter : ' ') .
		'AND tagente_modulo.disabled = 0
		AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
		AND (tagente_modulo.flag = 1 OR ((tagente_estado.last_execution_try + tagente_estado.current_interval) < UNIX_TIMESTAMP())) 
		ORDER BY tagente_modulo.flag DESC, time_left ASC, tagente_estado.last_execution_try ASC ', $pa_config->{'servername'});
    } else {
		@rows = get_db_rows ($dbh, 'SELECT DISTINCT(tagente_modulo.id_agente_modulo), tagente_modulo.flag, tagente_estado.last_execution_try, tagente_estado.current_interval + tagente_estado.last_execution_try AS time_left, last_execution_try
		FROM tagente, tagente_modulo, tagente_estado
		WHERE ((server_name = ?) OR (server_name = ANY(SELECT name FROM tserver WHERE status = 0))) 
		AND tagente_modulo.id_agente = tagente.id_agente
		AND tagente.disabled = 0
		AND tagente_modulo.disabled = 0
		AND tagente_modulo.id_tipo_modulo > 4
		AND tagente_modulo.id_tipo_modulo < 19 '
		. (defined ($network_filter) ? $network_filter : ' ') .
		'AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
		AND (tagente_modulo.flag = 1 OR ((tagente_estado.last_execution_try + tagente_estado.current_interval) < UNIX_TIMESTAMP()))
		ORDER BY tagente_modulo.flag DESC, time_left ASC, tagente_estado.last_execution_try ASC', $pa_config->{'servername'});
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
sub pandora_query_tcp ($$$$$$$$) {
	my $pa_config = $_[0];
	my $tcp_port = $_[1];
	my $ip_target = $_[2];
	my $module_result = $_[3];
	my $module_data = $_[4];
	my $tcp_send = $_[5];
	my $tcp_rcv = $_[6];
	my $id_tipo_modulo = $_[7];

    $tcp_send = decode_entities($tcp_send);
    $tcp_rcv = decode_entities($tcp_rcv);

        my $counter; 
        for ($counter =0; $counter < $pa_config->{'tcp_checks'}; $counter++){
	        my $temp; my $temp2;
	        my $tam;
	        my $handle=IO::Socket::INET6->new(
		        Proto=>"tcp",
		        PeerAddr=>$ip_target,
		        Timeout=>$pa_config->{'tcp_timeout'},
		        PeerPort=>$tcp_port,
			Multihomed=>1,
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

###############################################################################
# Set commands for SNMP checks depending on OS type
###############################################################################

sub pandora_snmp_get_command ($$$$$$$$$) {

    my ($snmpget_cmd, $snmp_version, $snmp_retries, $snmp_timeout, $snmp_community, $snmp_target, $snmp_oid, $snmp3_security_level, $snmp3_extra) = @_;

    my $output = "";

    # See codes on http://perldoc.perl.org/perlport.html#PLATFORMS
    my $OSNAME = $^O;


    # This fixes problem in linux snmpget, because v2 must be submitted like 2c
    if ($snmp_version eq "2"){
	$snmp_version = "2c";
    }

    # On windows, we need the snmpget command from net-snmp, already present on win agent
    # the call is the same than in linux
    if (($OSNAME eq "MSWin32") || ($OSNAME eq "MSWin32-x64") || ($OSNAME eq "cygwin")){
        if ($snmp_version ne "3"){
            $output = `$snmpget_cmd -v $snmp_version -r $snmp_retries -t $snmp_timeout -OUevqt -c $snmp_community $snmp_target $snmp_oid 2> NUL`;
        } else {
            $output = `$snmpget_cmd -v $snmp_version -r $snmp_retries -t $snmp_timeout -OUevqt -l $snmp3_security_level $snmp3_extra $snmp_target $snmp_oid 2> NUL`;
        }
    }

    # by default LINUX/FreeBSD/Solaris calls
    else {
        if ($snmp_version ne "3"){
            $output = `$snmpget_cmd -v $snmp_version -r $snmp_retries -t $snmp_timeout -OUevqt -c '$snmp_community' $snmp_target $snmp_oid 2>/dev/null`;
        } else {
            $output = `$snmpget_cmd -v $snmp_version -r $snmp_retries -t $snmp_timeout -OUevqt -l $snmp3_security_level $snmp3_extra $snmp_target $snmp_oid 2>/dev/null`;
        }
    }

    # Remove starting & ending double quotes, to easily parse numeric data on String types
    if ($output =~ /^\"(.*)\"$/){
        return $1;
    }
    else {
        return $output;
    }
}


##########################################################################
# SUB pandora_query_snmp (pa_config, module)
# Makes a call to SNMP modules to get a value,
##########################################################################

sub pandora_query_snmp ($$$) {
	my ($pa_config, $module, $dbh) = @_;

	my $snmp_version = $module->{"tcp_send"}; # (1, 2, 2c or 3)
	my $snmp3_privacy_method = $module->{"custom_string_1"}; # DES/AES
	my $snmp3_privacy_pass = $module->{"custom_string_2"};
	my $snmp3_security_level = $module->{"custom_string_3"}; # noAuthNoPriv|authNoPriv|authPriv
	my $snmp3_auth_user = $module->{"plugin_user"};
	my $snmp3_auth_pass = $module->{"plugin_pass"};
	my $snmp3_auth_method = $module->{"plugin_parameter"}; #MD5/SHA1
	my $snmp_community = $module->{"snmp_community"};
	my $snmp_target = $module->{"ip_target"};
	my $snmp_oid = $module->{"snmp_oid"};
	return (undef, 0) unless ($snmp_oid ne '');
	if ($snmp_oid =~ m/[a-zA-Z]/) {
		$snmp_oid = translate_obj ($dbh, $snmp_oid, $module->{"id_agente_modulo"});
		return (undef, 1) unless ($snmp_oid ne '');
	}

	my $snmp_timeout = $pa_config->{"snmp_timeout"};
	my $snmp_retries = $pa_config->{'snmp_checks'};

	my $module_result = 1; # by default error
	my $module_data = 0; 
	my $output; # Command output

	# If not defined, always snmp v1 (standard)
	if ($snmp_version ne '1' && $snmp_version ne '2' 
		&& $snmp_version ne '2c' && $snmp_version ne '3') {
		$snmp_version = '1';
	}

	my $snmpget_cmd = $pa_config->{"snmpget"};

	# SNMP v1, v2 and v2c call
	if ($snmp_version ne '3'){

		$output = pandora_snmp_get_command ($snmpget_cmd, $snmp_version, $snmp_retries, $snmp_timeout, $snmp_community, $snmp_target, $snmp_oid, "", "");
		if (defined ($output) && $output ne ""){
			$module_result = 0;
			$module_data = $output;
		}
	} else {

		# SNMP v3 has a very different command syntax

		my $snmp3_extra = "";
		my $snmp3_execution;

		# SNMP v3 authentication only
		if ($snmp3_security_level eq "authNoPriv"){
			$snmp3_extra = " -a $snmp3_auth_method -u $snmp3_auth_user -A $snmp3_auth_pass ";
		}

		# SNMP v3 privacy AND authentication
		if ($snmp3_security_level eq "authPriv"){
			$snmp3_extra = " -a $snmp3_auth_method -u $snmp3_auth_user -A $snmp3_auth_pass -x $snmp3_privacy_method -X $snmp3_privacy_pass ";
		}
       
		$output = pandora_snmp_get_command ($snmpget_cmd, $snmp_version, $snmp_retries, $snmp_timeout, $snmp_community, $snmp_target, $snmp_oid, $snmp3_security_level, $snmp3_extra);
		if (defined ($output) && $output ne ""){
			$module_result = 0;
			$module_data = $output;
		}
	}

	chomp ($module_data);
	return ($module_data, $module_result);
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
	my $agent_row = get_db_single_row ($dbh, 'SELECT * FROM tagente WHERE id_agente = ?', $id_agente);
	my $agent_name = $agent_row->{'nombre'};
	my $agent_os_version = $agent_row->{'os_version'};
	my $id_tipo_modulo = $module->{'id_tipo_modulo'};
	my $ip_target = $module->{'ip_target'};
	my $snmp_oid = $module->{'snmp_oid'};
	my $snmp_community = $module->{'snmp_community'};
	my $tcp_port = $module->{'tcp_port'};
	my $tcp_send = $module->{'tcp_send'};
	my $tcp_rcv = $module->{'tcp_rcv'};
	
	if ((defined($ip_target)) && ($ip_target)) {

	    # -------------------------------------------------------
	    # ICMP Modules
	    # -------------------------------------------------------

		if ($id_tipo_modulo == 6){ # ICMP (Connectivity only: Boolean)
			$module_data = pandora_ping ($pa_config, $ip_target);
			$module_result = 0; # Successful
		}
		elsif ($id_tipo_modulo == 7){ # ICMP (data for latency in ms)
			$module_data = pandora_ping_latency ($pa_config, $ip_target);
			$module_result = 0; # Successful
		}

		# -------------------------------------------------------
		# SNMP Modules (Proc=18, inc, data, string)
		# -------------------------------------------------------

		elsif (($id_tipo_modulo == 15) || 
				($id_tipo_modulo == 18) || 
				($id_tipo_modulo == 16) || 
				($id_tipo_modulo == 17)) {

			($module_data, $module_result) = pandora_query_snmp ($pa_config, $module, $dbh);

		    if ($module_result == 0) { # A correct SNMP Query
			    # SNMP_DATA_PROC
			    if ($id_tipo_modulo == 18){ #snmp_data_proc
                            # RFC1213-MIB where it says that: SYNTAX INTEGER { up(1), down(2), testing(3),
                            # unknown(4), dormant(5), notPresent(6), lowerLayerDown(7) } 
				    if ($module_data ne '1'){ # up state is 1, down state in SNMP is 2 ....
					    $module_data = 0;
				    }
			    }
			    # SNMP_DATA and SNMP_DATA_INC
			    elsif (($id_tipo_modulo == 15) || ($id_tipo_modulo == 16) ){ 
				    if (!is_numeric($module_data)){		   
					    $module_result = 1; 
				    } 
			    } 
		    } else { # Failed SNMP-GET
			    $module_data = 0;
			    if ($id_tipo_modulo == 18){ # snmp_proc
				    # Feature from 10Feb08. If snmp_proc_deadresponse = 1 and cannot contact by an error
				    # this is a fail monitor
				    if ($pa_config->{"snmp_proc_deadresponse"} eq "1"){
				            $module_result = 0;
						    $module_data = 0;
				    } 
				}
		    }
		}

		# -------------------------------------------------------
	    # TCP Module
	    # -------------------------------------------------------
	    elsif (($id_tipo_modulo == 8) || 
				($id_tipo_modulo == 9) || 
				($id_tipo_modulo == 10) || 
				($id_tipo_modulo == 11)) { # TCP Module
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
		my %data = ("data" => $module_data);
		pandora_process_module ($pa_config, \%data, '', $module, '', $timestamp, $utimestamp, $server_id, $dbh);

		if ($agent_os_version eq ''){
			$agent_os_version = $pa_config->{'servername'}.'_Net';
		}

		# Update agent last contact using Pandora version as agent version
		pandora_update_agent ($pa_config, $timestamp, $id_agente, $agent_os_version, $pa_config->{'version'}, -1, $dbh);
	
    } else {
		# Modules who cannot connect or something go bad, update last_execution_try field
		pandora_update_module_on_error ($pa_config, $module, $dbh);
	}
}

###############################################################################
# Convert a text obj tag to an OID and update the module configuration.
###############################################################################
sub translate_obj ($$$) {
	my ($dbh, $obj, $module_id) = @_;

	# SNMP is not thread safe
	$SNMPSem->down ();
	my $oid = SNMP::translateObj ($obj);
	$SNMPSem->up ();

	# Could not translate OID, disable the module
	if (! defined ($oid)) {
		db_do ($dbh, 'UPDATE tagente_modulo SET disabled = 1 WHERE id_agente_modulo = ?', $module_id);
		return '';
	}
	
	# Update module configuration
	db_do ($dbh, 'UPDATE tagente_modulo SET snmp_oid = ? WHERE id_agente_modulo = ?', $oid, $module_id);
	
	return $oid;
}

1;
__END__
