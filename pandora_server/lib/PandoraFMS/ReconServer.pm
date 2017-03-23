package PandoraFMS::ReconServer;
##########################################################################
# Pandora FMS Recon Server.
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

use IO::Socket::INET;
use POSIX qw(strftime ceil);
use JSON qw(decode_json encode_json);
use Encode qw(encode_utf8);

# Default lib dir for RPM and DEB packages
use lib '/usr/lib/perl5';

use PandoraFMS::Tools;
use PandoraFMS::DB;
use PandoraFMS::Core;
use PandoraFMS::ProducerConsumerServer;
use PandoraFMS::GIS qw(get_reverse_geoip_sql get_reverse_geoip_file get_random_close_point);
use PandoraFMS::Recon::Base;

# Patched Nmap::Parser. See http://search.cpan.org/dist/Nmap-Parser/.
use PandoraFMS::NmapParser;

# Inherits from PandoraFMS::ProducerConsumerServer
our @ISA = qw(PandoraFMS::ProducerConsumerServer);

# Global variables
my @TaskQueue :shared;
my %PendingTasks :shared;
my $Sem :shared;
my $TaskSem :shared;

# IDs from tconfig_os.
use constant OS_OTHER => 10;
use constant OS_ROUTER => 17;
use constant OS_SWITCH => 18;

########################################################################################
# Recon Server class constructor.
########################################################################################
sub new ($$$$$$) {
	my ($class, $config, $dbh) = @_;
	
	return undef unless $config->{'reconserver'} == 1;
	
	if (! -e $config->{'nmap'}) {
		logger ($config, ' [E] ' . $config->{'nmap'} . " needed by Pandora FMS Recon Server not found.", 1);
		print_message ($config, ' [E] ' . $config->{'nmap'} . " needed by Pandora FMS Recon Server not found.", 1);
		return undef;
	}

	# Initialize semaphores and queues
	@TaskQueue = ();
	%PendingTasks = ();
	$Sem = Thread::Semaphore->new;
	$TaskSem = Thread::Semaphore->new (0);
	
	db_do ($dbh, 'UPDATE trecon_task  SET utimestamp = 0 WHERE id_recon_server = ? AND status <> -1',
	       get_server_id ($dbh, $config->{'servername'}, RECONSERVER));

	# Call the constructor of the parent class
	my $self = $class->SUPER::new($config, RECONSERVER, \&PandoraFMS::ReconServer::data_producer, \&PandoraFMS::ReconServer::data_consumer, $dbh);
	
	bless $self, $class;
	return $self;
}

###############################################################################
# Run.
###############################################################################
sub run ($) {
	my $self = shift;
	my $pa_config = $self->getConfig ();
	
	print_message ($pa_config, " [*] Starting Pandora FMS Recon Server.", 1);
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
	
	# Manual tasks have interval_sweep = 0
	# Manual tasks are "forced" like the other, setting the utimestamp to 1
	# By default, after create a tasks it takes the utimestamp to 0
	# Status -1 means "done".
	
	my @rows = get_db_rows ($dbh, 'SELECT * FROM trecon_task 
                                   WHERE id_recon_server = ?
                                   AND disabled = 0
                                   AND utimestamp = 0 OR (status = -1 AND interval_sweep > 0 AND (utimestamp + interval_sweep) < UNIX_TIMESTAMP())', $server_id);
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

	# Is it a recon script?
	if (defined ($task->{'id_recon_script'}) && ($task->{'id_recon_script'} != 0)) {
		exec_recon_script ($pa_config, $dbh, $task);
		return;
	} else {
		logger($pa_config, 'Starting recon task for net ' . $task->{'subnet'} . '.', 10);
	}

	# Call nmap
	my $nmap_args  = '-nsP -PE --max-retries '.$pa_config->{'icmp_checks'}.' --host-timeout '.$pa_config->{'networktimeout'}.'s -T'.$pa_config->{'recon_timing_template'};
	my $np = new PandoraFMS::NmapParser;
	eval {
		my @subnets = split(/,/, safe_output($task->{'subnet'}));
		my @communities = split(/,/, safe_output($task->{'snmp_community'}));

		my $recon = new PandoraFMS::Recon::Base(
			communities => \@communities,
			dbh => $dbh,
			group_id => $task->{'id_group'},
			id_os => $task->{'id_os'},
			id_network_profile => $task->{'id_network_profile'},
			os_detection => $task->{'os_detect'},
			parent_detection => $task->{'parent_detection'},
			parent_recursion => $task->{'parent_recursion'},
			pa_config => $pa_config,
			recon_ports => $task->{'recon_ports'},
			resolve_names => $task->{'resolve_names'},
			snmp_enabled => $task->{'snmp_enabled'},
			subnets => \@subnets,
			task_id => $task->{'id_rt'},
			vlan_cache_enabled => $task->{'vlan_enabled'},
			%{$pa_config}
		);

		$recon->scan();
	};
	if ($@) {
		update_recon_task ($dbh, $task_id, -1);
		return;
	}
}

##########################################################################
# Update recon task status.
##########################################################################
sub update_recon_task ($$$) {
	my ($dbh, $id_task, $status) = @_;
	
	db_do ($dbh, 'UPDATE trecon_task SET utimestamp = ?, status = ? WHERE id_rt = ?', time (), $status, $id_task);
} 

##########################################################################
# Executes recon scripts
##########################################################################	
sub exec_recon_script ($$$) {
	my ($pa_config, $dbh, $task) = @_;
	
	# Get recon plugin data	
	my $script = get_db_single_row ($dbh, 'SELECT * FROM trecon_script WHERE id_recon_script = ?', $task->{'id_recon_script'});
	return -1 unless defined ($script);
	
	logger($pa_config, 'Executing recon script ' . safe_output($script->{'name'}), 10);
	
	my $command = safe_output($script->{'script'});
	
	my $macros = safe_output($task->{'macros'});

	# \r and \n should be escaped for decode_json().
	$macros =~ s/\n/\\n/g;
	$macros =~ s/\r/\\r/g;
	my $decoded_macros = decode_json (encode_utf8($macros));
	
	my $macros_parameters = '';
	
	# Add module macros as parameter
	if(ref($decoded_macros) eq "HASH") {
		# Convert the hash to a sorted array
		my @sorted_macros;
		while (my ($i, $m) = each (%{$decoded_macros})) {
			$sorted_macros[$i] = $m;
		}

		# Remove the 0 position		
		shift @sorted_macros;

		foreach my $m (@sorted_macros) {
			$macros_parameters = $macros_parameters . ' "' . $m->{"value"} . '"';
		}
	}
	
	if (-x $command) {
		`$command $task->{'id_rt'} $task->{'id_group'} $task->{'create_incident'} $macros_parameters`;
	} else {
		logger ($pa_config, "Cannot execute recon task command $command.");
	}
	
	# Only update the timestamp in case something went wrong. The script should set the status.
	db_do ($dbh, 'UPDATE trecon_task SET utimestamp = ? WHERE id_rt = ?', time (), $task->{'id_rt'});
	
	logger($pa_config, 'Done executing recon script ' . safe_output($script->{'name'}), 10);
	return 0;
}

##########################################################################
# Guess the OS using xprobe2 or nmap.
##########################################################################
sub PandoraFMS::Recon::Base::guess_os($$) {
	my ($self, $device) = @_;

	# OS detection disabled. Use the device type.
	if ($self->{'os_detection'} == 0) {
		my $device_type = $self->get_device_type($device);
		return OS_OTHER unless defined($device_type);

		return OS_ROUTER if ($device_type eq 'router');
		return OS_SWITCH if ($device_type eq 'switch');
		return OS_OTHER;
	}

	# Use xprobe2 if available
	if (-e $self->{pa_config}->{xprobe2}) {
			my $output = `"$self->{pa_config}->{xprobe2}" $device 2>$DEVNULL | grep 'Running OS' | head -1`;
			return OS_OTHER if ($? != 0);
			return pandora_get_os($self->{'dbh'}, $output);
	}
	
	# Use nmap by default
	if (-e $self->{pa_config}->{nmap}) {
		my $output = `"$self->{pa_config}->{nmap}" -F -O $device 2>$DEVNULL | grep 'Aggressive OS guesses'`;
		return OS_OTHER if ($? != 0);
		return pandora_get_os($self->{'dbh'}, $output);
	}

	return OS_OTHER;
}

##############################################################################
# Returns the number of open ports from the given list.
##############################################################################
sub PandoraFMS::Recon::Base::tcp_scan ($$) {
	my ($self, $host) = @_;

	my $open_ports = `"$self->{pa_config}->{nmap}" -p$self->{recon_ports} $host | grep open | wc -l`;
	return $open_ports;
}

##########################################################################
# Create network profile modules for the given agent.
##########################################################################
sub PandoraFMS::Recon::Base::create_network_profile_modules($$$) {
	my ($self, $agent_id, $device) = @_;
	
	return unless ($self->{'id_network_profile'} > 0);
	
	# Get network components associated to the network profile.
	my @np_components = get_db_rows($self->{'dbh'}, 'SELECT * FROM tnetwork_profile_component WHERE id_np = ?', $self->{'id_network_profile'});
	foreach my $np_component (@np_components) {

		# Get network component data
		my $component = get_db_single_row($self->{'dbh'}, 'SELECT * FROM tnetwork_component WHERE id_nc = ?', $np_component->{'id_nc'});
		if (!defined ($component)) {
			$self->call('message', "Network component ID " . $np_component->{'id_nc'} . " not found.", 5);
			next;
		}
		
		# Use snmp_community from network task instead the component snmp_community
		$component->{'snmp_community'} = safe_output($self->get_community($device));
	
		pandora_create_module_from_network_component($self->{'pa_config'}, $component, $agent_id, $self->{'dbh'});
	}
}

##########################################################################
# Connect the given devices in the Pandora FMS database.
##########################################################################
sub PandoraFMS::Recon::Base::connect_agents($$$$$) {
	my ($self, $dev_1, $if_1, $dev_2, $if_2) = @_;

	# Get the agent for the first device.
	my $agent_1 = get_agent_from_addr($self->{'dbh'}, $dev_1);
	if (!defined($agent_1)) {
		$agent_1 = get_agent_from_name($self->{'dbh'}, $dev_1);
	}
	return unless defined($agent_1);

	# Get the agent for the second device.
	my $agent_2 = get_agent_from_addr($self->{'dbh'}, $dev_2);
	if (!defined($agent_2)) {
		$agent_2 = get_agent_from_name($self->{'dbh'}, $dev_2);
	}
	return unless defined($agent_2);

	# Use ping modules by default.
	$if_1 = 'ping' if ($if_1 eq '');
	$if_2 = 'ping' if ($if_2 eq '');

	# Check whether the modules exists.
	my $module_name_1 = $if_1 eq 'ping' ? 'ping' : "${if_1}_ifOperStatus";
	my $module_name_2 = $if_2 eq 'ping' ? 'ping' : "${if_2}_ifOperStatus";
	my $module_id_1 = get_agent_module_id($self->{'dbh'}, $module_name_1, $agent_1->{'id_agente'});
	if ($module_id_1 <= 0) {
		$self->call('message', "ERROR: Module " . safe_output($module_name_1) . " does not exist for agent $dev_1.", 5);
		return;
	}
	my $module_id_2 = get_agent_module_id($self->{'dbh'}, $module_name_2, $agent_2->{'id_agente'});
	if ($module_id_2 <= 0) {
		$self->call('message', "ERROR: Module " . safe_output($module_name_2) . " does not exist for agent $dev_2.", 5);
		return;
	}

	# Connect the modules if they are not already connected.
	my $connection_id = get_db_value($self->{'dbh'}, 'SELECT id FROM tmodule_relationship WHERE (module_a = ? AND module_b = ?) OR (module_b = ? AND module_a = ?)', $module_id_1, $module_id_2, $module_id_1, $module_id_2);
	if (! defined($connection_id)) {
		db_do($self->{'dbh'}, 'INSERT INTO tmodule_relationship (`module_a`, `module_b`, `id_rt`) VALUES(?, ?, ?)', $module_id_1, $module_id_2, $self->{'task_id'});
	}
}

##########################################################################
# Create an agent for the given device. Returns the ID of the new (or
# existing) agent, undef on error.
##########################################################################
sub PandoraFMS::Recon::Base::create_agent($$) {
	my ($self, $device) = @_;

	my @agents = get_db_rows($self->{'dbh'},
		'SELECT * FROM taddress, taddress_agent, tagente
		 WHERE tagente.id_agente = taddress_agent.id_agent
			AND taddress_agent.id_a = taddress.id_a
			AND ip = ?', $device
	);

	# Does the host already exist?
	my $agent;
	foreach my $candidate (@agents) {
	  $agent = {map {$_} %$candidate}; # copy contents, do not use shallow copy
	  # exclude $device itself, because it handle corner case when target includes NAT
	  my @registered = map {$_->{ip}} get_db_rows($self->{'dbh'},
	  	'SELECT ip FROM taddress, taddress_agent, tagente
	  	 WHERE tagente.id_agente = taddress_agent.id_agent
	  		AND taddress_agent.id_a = taddress.id_a
	  		AND tagente.id_agente = ?
			AND taddress.ip != ?', $agent->{id_agente}, $device
	  );
	  foreach my $ip_addr (@registered) {
		my @matched = grep { $_ =~ /^$ip_addr$/ } $self->get_addresses($device);
		if (scalar(@matched) == 0) {
			$agent = undef;
			last;
		}
	  }
	  last if(defined($agent)); # exit loop if match all ip_addr
	}

	if (!defined($agent)) {
		$agent = get_agent_from_name($self->{'dbh'}, $device);
	}

	my ($agent_id, $agent_learning);
	if (!defined($agent)) {

		# Resolve hostnames.
		my $host_name = $self->{'resolve_names'} == 1 ? gethostbyaddr (inet_aton($device), AF_INET) : $device;
		$host_name = $device unless defined ($host_name);

		# Guess the OS.
		my $id_os = $self->guess_os($device);

		# Are we filtering hosts by OS?
		return if ($self->{'id_os'} > 0 && $id_os != $self->{'id_os'});

		# Are we filtering hosts by TCP port?
		return if ($self->{'recon_ports'} ne '' && $self->tcp_scan($device) == 0);

		$agent_id = pandora_create_agent($self->{'pa_config'}, $self->{'pa_config'}->{'servername'}, $host_name, $device, $self->{'group_id'}, 0, $id_os, '', 300, $self->{'dbh'});
		return undef unless defined ($agent_id) and ($agent_id > 0);
		pandora_event($self->{'pa_config'}, "[RECON] New " . $self->get_device_type($device) . " found (" . join(',', $self->get_addresses($device)) . ").", $self->{'group_id'}, $agent_id, 2, 0, 0, 'recon_host_detected', 0, $self->{'dbh'});
		$agent_learning = 1;

		# Create network profile modules for the agent
		$self->create_network_profile_modules($agent_id, $device);
	}
	else {
		$agent_id = $agent->{'id_agente'};
		$agent_learning = $agent->{'modo'};
	}

	# Do not create any modules if the agent is not in learning mode.
	return unless ($agent_learning == 1);

	# Add found IP addresses to the agent.
	foreach my $ip_addr ($self->get_addresses($device)) {
		my $addr_id = get_addr_id($self->{'dbh'}, $ip_addr);
		$addr_id = add_address($self->{'dbh'}, $ip_addr) unless ($addr_id > 0);
		next unless ($addr_id > 0);

		# Assign the new address to the agent
		my $agent_addr_id = get_agent_addr_id($self->{'dbh'}, $addr_id, $agent_id);
		if ($agent_addr_id <= 0) {
			db_do($self->{'dbh'}, 'INSERT INTO taddress_agent (`id_a`, `id_agent`)
						  VALUES (?, ?)', $addr_id, $agent_id);
		}
	}

	# Create a ping module.
	my $module_id = get_agent_module_id($self->{'dbh'}, "ping", $agent_id);
	if ($module_id <= 0) {
		my %module = ('id_tipo_modulo' => 6,
				   'id_modulo' => 2,
				   'nombre' => "ping",
				   'descripcion' => '',
				   'id_agente' => $agent_id,
				   'ip_target' => $device);
		pandora_create_module_from_hash ($self->{'pa_config'}, \%module, $self->{'dbh'});
	}

	# Add interfaces to the agent if it responds to SNMP.
	my $community = $self->get_community($device);
	return $agent_id unless defined($community);

	my @output = $self->snmp_get_value_array($device, $PandoraFMS::Recon::Base::IFINDEX);
	foreach my $if_index (@output) {
		next unless ($if_index =~ /^[0-9]+$/);

		# Check the status of the interface.
		if ($self->{'all_ifaces'} == 0) {
			my $if_status = $self->snmp_get_value($device, "$PandoraFMS::Recon::Base::IFOPERSTATUS.$if_index");
			next unless $if_status == 1;
		}

		# Fill the module description with the IP and MAC addresses.
		my $mac = $self->get_if_mac($device, $if_index);
		my $ip = $self->get_if_ip($device, $if_index);
		my $if_desc = ($mac ne '' ? "MAC $mac " : '') . ($ip ne '' ? "IP $ip" : '');

		# Get the name of the network interface.
		my $if_name = $self->snmp_get_value($device, "$PandoraFMS::Recon::Base::IFNAME.$if_index");
		$if_name = "if$if_index" unless defined ($if_name);
		$if_name =~ s/"//g;

		# Check whether the module already exists.
		my $module_id = get_agent_module_id($self->{'dbh'}, "${if_name}_ifOperStatus", $agent_id);
		next if ($module_id > 0 && !$agent_learning);
	
		# Encode problematic characters.
		$if_name = safe_input($if_name);
		$if_desc = safe_input($if_desc);

		# Interface status module.
		$module_id = get_agent_module_id($self->{'dbh'}, "${if_name}_ifOperStatus", $agent_id);
		if ($module_id <= 0) {
			my %module = ('id_tipo_modulo' => 18,
				'id_modulo' => 2,
				'nombre' => "${if_name}_ifOperStatus",
				'descripcion' => $if_desc,
				'id_agente' => $agent_id,
				'ip_target' => $device,
				'tcp_send' => 1,
				'snmp_community' => $community,
				'snmp_oid' => "$PandoraFMS::Recon::Base::IFOPERSTATUS.$if_index"
			);
			pandora_create_module_from_hash ($self->{'pa_config'}, \%module, $self->{'dbh'});
		} else {
			my %module = (
				'descripcion' => $if_desc,
				'ip_target' => $device,
				'snmp_community' => $community,
			);
			pandora_update_module_from_hash ($self->{'pa_config'}, \%module, 'id_agente_modulo', $module_id, $self->{'dbh'});
		}

		# Incoming traffic module.
		my $if_hc_in_octets = $self->snmp_get_value($device, "$PandoraFMS::Recon::Base::IFHCINOCTECTS.$if_index");
		if (defined($if_hc_in_octets)) {
			$module_id = get_agent_module_id($self->{'dbh'}, "${if_name}_ifHCInOctets", $agent_id);
			if ($module_id <= 0) {
				my %module = ('id_tipo_modulo' => 16,
						   'id_modulo' => 2,
						   'nombre' => "${if_name}_ifHCInOctets",
						   'descripcion' => 'The total number of octets received on the interface, including framing characters. This object is a 64-bit version of ifInOctets.',
						   'id_agente' => $agent_id,
						   'ip_target' => $device,
						   'tcp_send' => 1,
						   'snmp_community' => $community,
						   'snmp_oid' => "$PandoraFMS::Recon::Base::IFHCINOCTECTS.$if_index");
				pandora_create_module_from_hash ($self->{'pa_config'}, \%module, $self->{'dbh'});
			} else {
				my %module = (
					'ip_target' => $device,
					'snmp_community' => $community,
				);
				pandora_update_module_from_hash ($self->{'pa_config'}, \%module, 'id_agente_modulo', $module_id, $self->{'dbh'});
			}
		}
		# ifInOctets
		else {
			$module_id = get_agent_module_id($self->{'dbh'}, "${if_name}_ifInOctets", $agent_id);
			if ($module_id <= 0) {
				my %module = ('id_tipo_modulo' => 16,
						   'id_modulo' => 2,
						   'nombre' => "${if_name}_ifInOctets",
						   'descripcion' => 'The total number of octets received on the interface, including framing characters.',
						   'id_agente' => $agent_id,
						   'ip_target' => $device,
						   'tcp_send' => 1,
						   'snmp_community' => $community,
						   'snmp_oid' => "$PandoraFMS::Recon::Base::IFINOCTECTS.$if_index");
				pandora_create_module_from_hash ($self->{'pa_config'}, \%module, $self->{'dbh'});
			} else {
				my %module = (
					'ip_target' => $device,
					'snmp_community' => $community,
				);
				pandora_update_module_from_hash ($self->{'pa_config'}, \%module, 'id_agente_modulo', $module_id, $self->{'dbh'});
			}
		}

		# Outgoing traffic module.
		my $if_hc_out_octets = $self->snmp_get_value($device, "$PandoraFMS::Recon::Base::IFHCOUTOCTECTS.$if_index");
		if (defined($if_hc_out_octets)) {
			$module_id = get_agent_module_id($self->{'dbh'}, "${if_name}_ifHCOutOctets", $agent_id);
			if ($module_id <= 0) {
				my %module = ('id_tipo_modulo' => 16,
						   'id_modulo' => 2,
						   'nombre' => "${if_name}_ifHCOutOctets",
						   'descripcion' => 'The total number of octets received on the interface, including framing characters. This object is a 64-bit version of ifOutOctets.',
						   'id_agente' => $agent_id,
						   'ip_target' => $device,
						   'tcp_send' => 1,
						   'snmp_community' => $community,
						   'snmp_oid' => "$PandoraFMS::Recon::Base::IFHCOUTOCTECTS.$if_index");
				pandora_create_module_from_hash ($self->{'pa_config'}, \%module, $self->{'dbh'});
			} else {
				my %module = (
					'ip_target' => $device,
					'snmp_community' => $community,
				);
				pandora_update_module_from_hash ($self->{'pa_config'}, \%module, 'id_agente_modulo', $module_id, $self->{'dbh'});
			}
		}
		# ifOutOctets
		else {
			$module_id = get_agent_module_id($self->{'dbh'}, "${if_name}_ifOutOctets", $agent_id);
			if ($module_id <= 0) {
				my %module = ('id_tipo_modulo' => 16,
						   'id_modulo' => 2,
						   'nombre' => "${if_name}_ifOutOctets",
						   'descripcion' => 'The total number of octets received on the interface, including framing characters.',
						   'id_agente' => $agent_id,
						   'ip_target' => $device,
						   'tcp_send' => 1,
						   'snmp_community' => $community,
						   'snmp_oid' => "$PandoraFMS::Recon::Base::IFOUTOCTECTS.$if_index");
				pandora_create_module_from_hash ($self->{'pa_config'}, \%module, $self->{'dbh'});
			} else {
				my %module = (
					'ip_target' => $device,
					'snmp_community' => $community,
				);
				pandora_update_module_from_hash ($self->{'pa_config'}, \%module, 'id_agente_modulo', $module_id, $self->{'dbh'});
			}
		}
	}

	return $agent_id;
}

##########################################################################
# Delete already existing connections.
##########################################################################
sub PandoraFMS::Recon::Base::delete_connections($) {
	my ($self) = @_;

	$self->call('message', "Deleting connections...", 10);
	db_do($self->{'dbh'}, 'DELETE FROM tmodule_relationship WHERE id_rt=?', $self->{'task_id'});
}

#######################################################################
# Print log messages.
#######################################################################
sub PandoraFMS::Recon::Base::message($$$) {
	my ($self, $message, $verbosity) = @_;
	
	logger($self->{'pa_config'}, "[Recon task " . $self->{'task_id'} . "] $message", $verbosity);
}

##########################################################################
# Connect the given hosts to its parent.
##########################################################################
sub PandoraFMS::Recon::Base::set_parent($$$) {
	my ($self, $host, $parent) = @_;

	return unless ($self->{'parent_detection'} == 1);

	# Get the agent for the host.
	my $agent = get_agent_from_addr($self->{'dbh'}, $host);
	if (!defined($agent)) {
		$agent = get_agent_from_name($self->{'dbh'}, $host);
	}
	return unless defined($agent);

	# Check if the parent agent exists.
	my $agent_parent = get_agent_from_addr($self->{'dbh'}, $parent);
	if (!defined($agent_parent)) {
		$agent_parent = get_agent_from_name($self->{'dbh'}, $parent);
	}
	return unless (defined ($agent_parent));

	# Is the agent in learning mode?
	return unless ($agent_parent->{'modo'} == 1);

	# Connect the host to its parent.
	db_do($self->{'dbh'}, 'UPDATE tagente SET id_parent=? WHERE id_agente=?', $agent_parent->{'id_agente'}, $agent->{'id_agente'});
}

##########################################################################
# Update recon task status.
##########################################################################
sub PandoraFMS::Recon::Base::update_progress ($$) {
	my ($self, $progress) = @_;

	db_do ($self->{'dbh'}, 'UPDATE trecon_task SET utimestamp = ?, status = ? WHERE id_rt = ?', time (), $progress, $self->{'task_id'});
}

1;
__END__
