package PandoraFMS::ReconServer;
##########################################################################
# Pandora FMS Recon Server.
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
use NetAddr::IP;
use POSIX qw(strftime ceil);

use PandoraFMS::Tools;
use PandoraFMS::DB;
use PandoraFMS::Core;
use PandoraFMS::ProducerConsumerServer;

# Inherits from PandoraFMS::ProducerConsumerServer
our @ISA = qw(PandoraFMS::ProducerConsumerServer);

# Global variables
my @TaskQueue :shared;
my %PendingTasks :shared;
my $Sem :shared = Thread::Semaphore->new;
my $TaskSem :shared = Thread::Semaphore->new (0);
my $TracerouteAvailable = (eval 'use Net::Traceroute::PurePerl; 1') ? 1 : 0;

########################################################################################
# Recon Server class constructor.
########################################################################################
sub new ($$$$$$) {
	my ($class, $config, $dbh) = @_;

	return undef unless $config->{'reconserver'} == 1;

	# Call the constructor of the parent class
	my $self = $class->SUPER::new($config, 3, \&PandoraFMS::ReconServer::data_producer, \&PandoraFMS::ReconServer::data_consumer, $dbh);

    bless $self, $class;
    return $self;
}

###############################################################################
# Run.
###############################################################################
sub run ($) {
	my $self = shift;
	my $pa_config = $self->getConfig ();

	print " [*] Starting Pandora FMS Recon Server. \n";
	$self->setNumThreads ($pa_config->{'recon_threads'});
	$self->SUPER::run (\@TaskQueue, \%PendingTasks, $Sem, $TaskSem);
}

###############################################################################
# Data producer.
###############################################################################
sub data_producer ($) {
	my $self = shift;
	my ($pa_config, $dbh) = ($self->getConfig (), $self->getDBH ());

	my @tasks;

	my $server_id = get_server_id ($dbh, $pa_config->{'servername'}, $self->getServerType ());
	return @tasks unless defined ($server_id);

	my @rows = get_db_rows ($dbh, 'SELECT * FROM trecon_task 
                                   WHERE id_recon_server = ? 
                                   AND (utimestamp = 0 OR (utimestamp + interval_sweep) < UNIX_TIMESTAMP())', $server_id);
	foreach my $row (@rows) {

		# Update task status
		update_recon_task ($dbh, $row->{'id_rt'}, 1);

		push (@tasks, $row->{'id_rt'});
	}

	return @tasks;
}

###############################################################################
# Data consumer.
###############################################################################
sub data_consumer ($$) {
	my ($self, $task_id) = @_;
	my ($pa_config, $dbh) = ($self->getConfig (), $self->getDBH ());

	# Get recon task data	
	my $task = get_db_single_row ($dbh, 'SELECT * FROM trecon_task WHERE id_rt = ?', $task_id);	
	return -1 unless defined ($task);
	
	# Get a NetAddr::IP object for the target network
	my $net_addr = new NetAddr::IP ($task->{'subnet'});
	if (! defined ($net_addr)) {
		logger ($pa_config, 'Invalid network ' . $task->{'subnet'} . ' for task ' . $task->{'name'}, 2);
		update_recon_task ($dbh, $task_id, -1);
		return -1;
	}

	# Scan the network for hosts
	my ($total_hosts, $hosts_found, $addr_found) = ($net_addr->num, 0, '');
	for (my $i = 1, $net_addr++; $net_addr < $net_addr->broadcast; $i++, $net_addr++) {

		my $addr = (split(/\//, $net_addr))[0];
		
		# Update the recon task or break if it does not exist anymore
		last if (update_recon_task ($dbh, $task_id, ceil ($i / ($total_hosts / 100))) eq '0E0');

		# Does the host already exist?
        next if (get_agent_from_addr ($dbh, $addr) > 0);
       
		# Is the host alive? (thanks to Evi for the TCP scans)
		my $alive = 0;
		if (pandora_ping ($pa_config, $addr) == 1) {
			$alive = 1;
		#Check for Remote Desktop & VNC (Desktop & Server machines)
		#} elsif (tcp_scan ($addr, $pa_config->{'networktimeout'}, 3389) == 1 ||
		#         tcp_scan ($addr, $pa_config->{'networktimeout'}, 5900) == 1) {
		#	$alive = 1;
		#Check for management ports 10000 = Webmin, 161 = SNMP (Most embedded devices)
		#} elsif (tcp_scan ($addr, $pa_config->{'networktimeout'}, 10000) == 1 ||
		#         tcp_scan ($addr, $pa_config->{'networktimeout'}, 161) == 1) {
		#	$alive = 1;
		#Check for SSH & Mail (Servers and Unix machines)
		#} elsif (tcp_scan ($addr, $pa_config->{'networktimeout'}, 22) == 1 ||
		#         tcp_scan ($addr, $pa_config->{'networktimeout'}, 25) == 1) {
		#	$alive = 1;
		#Check for WWW & MySQL (Webservers and systems in a DMZ)
		#} elsif (tcp_scan ($addr, $pa_config->{'networktimeout'}, 80) == 1 ||
		#         tcp_scan ($addr, $pa_config->{'networktimeout'}, 3306) == 1) {
		#	$alive = 1;
		}

		next unless ($alive == 1);

		# Guess the OS and filter
		my $id_os = guess_os ($pa_config, $addr);
		next if ($task->{'id_os'} > 0 && $task->{'id_os'} != $id_os);

		$hosts_found++;
		$addr_found .= $addr . " ";

		# Resolve the address
		my $host_name = gethostbyaddr(inet_aton($addr), AF_INET);
		$host_name = $addr unless defined ($host_name);
		
		# Get the parent host
		my $parent_id = get_host_parent ($pa_config, $addr, $dbh);
				
		# Add the new address if it does not exist
		my $addr_id = get_addr_id ($dbh, $addr);
		$addr_id = add_address ($dbh, $addr) unless ($addr_id > 0);
		next unless ($addr_id > 0);

		# Crate a new agent
		my $agent_id = pandora_create_agent ($pa_config, $pa_config->{'servername'},
					                                  $host_name, $addr, $addr_id,
					                                  $task->{'id_group'}, $parent_id, $id_os, $dbh);
		# Assign the new address to the agent
		db_insert ($dbh, 'INSERT INTO taddress_agent (`id_a`, `id_agent`)
		                  VALUES (?, ?)', $addr_id, $agent_id);

		# Crate network profile modules for the agent
		create_network_profile_modules ($pa_config, $dbh, $agent_id, $task->{'id_network_profile'}, $addr);

		# Generate an event
        pandora_event ($pa_config, "[RECON] New host [$host_name] detected on network [" . $task->{'subnet'} . ']',
                       $task->{'id_group'}, $agent_id, 2, 0, 0, 'recon_host_detected', $dbh);
	}

	# Create an incident
	if ($hosts_found > 0 && $task->{'create_incident'} == 1){
		my $text = "At " . strftime ("%Y-%m-%d %H:%M:%S", localtime()) . " ($hosts_found) new hosts were detected by Pandora FMS Recon Server running on [" . $pa_config->{'servername'} . "_Recon]. This incident has been automatically created following instructions for this recon task [" . $task->{'id_group'} . "].\n\n";
		if ($task->{'id_network_profile'} > 0) {
			$text .= "Aditionally, and following instruction for this task, agent(s) has been created, with modules assigned to network component profile [" . get_nc_profile_name ($dbh, $task->{'id_network_profile'}) . "]. Please check this agent as soon as possible to verify it.";
		}
		$text .= "\n\nThis is the list of IP addresses found: \n\n$addr_found";
		pandora_create_incident ($pa_config, $dbh, "[RECON] New hosts detected", $text, 0, 0, 'Pandora FMS Recon Server', $task->{'id_group'});
	}

	# Mark recon task as done
	update_recon_task ($dbh, $task_id, -1);
}

##############################################################################
# TCP scan the given host/port. Returns 1 if successful, 0 otherwise.
##############################################################################
sub tcp_scan ($$$) {
	my ($host, $timeout, $port) = @_;
	my $rc = 0;

	eval {
		local $SIG{'ALRM'} = sub { return 0; };
		alarm ($timeout);
		my $handle=IO::Socket::INET->new(
				Proto => 'tcp',
				PeerAddr => $host,
				PeerPort => $port);
		$rc = 1 if ($handle);
		alarm (0);
	};

    return 0 if ($@);
	return $rc;
}

##########################################################################
# Guess OS using xprobe2.
##########################################################################
sub guess_os {
    my ($pa_config, $host) = @_;

    my $xprobe2 = $pa_config->{'xprobe2'};
    
    # Other OS
    return 10 if (! -e $xprobe2);

	# Execute xprobe2
    my $output = '';
    eval {
		$output = `$xprobe2 $host 2> /dev/null | grep "Running OS" 2> /dev/null | head -1 2> /dev/null`;
    };

	# Check for errors
    return 10 if ($@);

	return pandora_get_os ($output);
}

##########################################################################
# Return the ID of the given address, 0 if it does not exist.
##########################################################################
sub get_addr_id ($$) {
	my ($dbh, $addr) = @_;

	my $addr_id = get_db_value ($dbh, 'SELECT id_a FROM taddress WHERE ip = ?', $addr);
	return (defined ($addr_id) ? $addr_id : -1);
}

##########################################################################
# Return the ID of the agent with the given IP.
##########################################################################
sub get_agent_from_addr ($$) {
	my ($dbh, $ip_address) = @_;

	return 0 if (! defined ($ip_address) || $ip_address eq '');

	my $agent_id = get_db_value ($dbh, 'SELECT id_agent FROM taddress, taddress_agent WHERE taddress_agent.id_a = taddress.id_a AND ip = ?', $ip_address);
	return (defined ($agent_id)) ? $agent_id : -1;
}

##########################################################################
# Update recon task status.
##########################################################################
sub update_recon_task ($$$) {
	my ($dbh, $id_task, $status) = @_;

	db_do ($dbh, 'UPDATE trecon_task SET utimestamp = ?, status = ? WHERE id_rt = ?', time (), $status, $id_task);
}

##########################################################################
# Add the given address to taddress.
##########################################################################
sub add_address ($$) {
	my ($dbh, $ip_address) = @_;

	return db_insert ($dbh, 'INSERT INTO taddress (ip) VALUES (?)', $ip_address);
}

##########################################################################
# Create network profile modules for the given agent.
##########################################################################
sub create_network_profile_modules {
	my ($pa_config, $dbh, $agent_id, $np_id, $addr) = @_;
	
	return unless ($np_id > 0);

	# Get network components associated to the network profile
	my @np_components = get_db_rows ($dbh, 'SELECT * FROM tnetwork_profile_component WHERE id_np = ?', $np_id);

	foreach my $np_component (@np_components) {
		
		# Get network component data
		my $component = get_db_single_row ($dbh, 'SELECT * FROM tnetwork_component wHERE id_nc = ?', $np_component->{'id_nc'});
		next unless defined ($component);

		# Create the module
		my $module_id = db_insert ($dbh, 'INSERT INTO tagente_modulo (id_agente, id_tipo_modulo, descripcion, nombre, max, min, module_interval, tcp_port, tcp_send, tcp_rcv, snmp_community, snmp_oid, ip_target, id_module_group, flag, disabled, plugin_user, plugin_pass, plugin_parameter, max_timeout, id_modulo )
		                                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 0, ?, ?, ?, ?, ?)',
		                                             $agent_id, $component->{'type'}, $component->{'description'}, $component->{'name'}, $component->{'max'}, $component->{'min'}, $component->{'module_interval'}, $component->{'tcp_port'}, $component->{'tcp_send'}, $component->{'tcp_rcv'}, $component->{'snmp_community'},
		                                             $component->{'snmp_oid'}, $addr, $component->{'id_module_group'}, $component->{'plugin_user'}, $component->{'plugin_pass'}, $component->{'plugin_parameter'}, $component->{'max_timeout'}, $component->{'id_modulo'});

		logger($pa_config, 'Recon Server: Creating module ' . $component->{'name'} . " for agent $addr", 3);

		# An entry in tagente_estado is necessary for the module to work
		db_insert ($dbh, 'INSERT INTO tagente_estado (id_agente_modulo, datos, timestamp, estado, id_agente, last_try, utimestamp, current_interval, running_by)
		                  VALUES (?, \'\', \'0000-00-00 00:00:00\', 1, ?, \'0000-00-00 00:00:00\', 0, ?, 0)', 
		                   $module_id, $agent_id, $component->{'module_interval'});

	}
}

##########################################################################
# Returns the ID of the parent of the given host if available.
##########################################################################	
sub get_host_parent ($$){
	my ($pa_config, $host, $dbh) = @_;

	# Traceroute not available
    return 0 unless ($TracerouteAvailable != 0);

    my $tr = Net::Traceroute::PurePerl->new (
		 backend        => 'PurePerl',
         host           => $host,
         debug          => 0,
         max_ttl        => 15,
         query_timeout  => $pa_config->{'networktimeout'},
         packetlen      => 40,
         protocol       => 'udp', # udp or icmp
    );

	my $success = 0;

	# Call traceroute
	eval {
		local $SIG{'ALRM'} = sub { return 0; };
		alarm($pa_config->{'networktimeout'});
		$success = $tr->traceroute();
		alarm(0);
	};

	# Error or timeout
	return 0 if ($@);

	# Traceroute was not successful
	return 0 if ($tr->hops < 2 || $success == 0);

	my $parent_addr = $tr->hop_query_host($tr->hops - 1, 0);
	return get_agent_from_addr ($dbh, $parent_addr);
}

1;
__END__
