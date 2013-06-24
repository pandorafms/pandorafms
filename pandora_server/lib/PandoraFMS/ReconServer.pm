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

# Default lib dir for RPM and DEB packages
use lib '/usr/lib/perl5';

use PandoraFMS::Tools;
use PandoraFMS::DB;
use PandoraFMS::Core;
use PandoraFMS::ProducerConsumerServer;
use PandoraFMS::GIS qw(get_reverse_geoip_sql get_reverse_geoip_file get_random_close_point);

# Patched Nmap::Parser. See http://search.cpan.org/dist/Nmap-Parser/.
use PandoraFMS::NmapParser;

# Inherits from PandoraFMS::ProducerConsumerServer
our @ISA = qw(PandoraFMS::ProducerConsumerServer);

# Global variables
my @TaskQueue :shared;
my %PendingTasks :shared;
my $Sem :shared = Thread::Semaphore->new;
my $TaskSem :shared = Thread::Semaphore->new (0);

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
		logger($pa_config, 'Executing recon script ' . safe_output($task->{'name'}) . '.', 10);
		exec_recon_script ($pa_config, $dbh, $task);
		return;
	} else {
		logger($pa_config, 'Starting recon task for net ' . $task->{'subnet'} . '.', 10);
	}

	# Call nmap
	my $np = new PandoraFMS::NmapParser;
	eval {
		$np->parsescan($pa_config->{'nmap'},'-nsP', ($task->{'subnet'}));
	};
	if ($@) {
		update_recon_task ($dbh, $task_id, -1);
		return;
	}

	# Parse scanned hosts
	my $module_hash;
	my @up_hosts = $np->all_hosts ('up');
	my $total_up = scalar (@up_hosts);
	my $progress = 0;
	my $added_hosts = '';
	foreach my $host (@up_hosts) {
		$progress++;
		
		# Get agent address
		my $addr = $host->addr();
		next unless ($addr ne '0');

		# Update the recon task or break if it does not exist anymore
		last if (update_recon_task ($dbh, $task_id, ceil ($progress / ($total_up / 100))) eq '0E0');

		# Resolve hostnames
		my $host_name = undef;
		if ($task->{'resolve_names'} == 1){
			$host_name = gethostbyaddr (inet_aton($addr), AF_INET);
		}
		$host_name = $addr unless defined ($host_name);

		# Does the host already exist?
		my $agent = get_agent_from_addr ($dbh, $addr);
		if (! defined ($agent)) {
			$agent = get_agent_from_name ($dbh, $host_name);
		}		

		my $agent_id = defined ($agent) ? $agent->{'id_agente'} : 0;
		if ($agent_id > 0) {

			# Skip if not in learning mode
			next if ($agent->{'modo'} != 1);
		}

		# Get the parent host
		my $parent_id = 0;
		if ($task->{'parent_detection'} == 1) {
			$parent_id = get_host_parent ($pa_config, $addr, $dbh, $task->{'id_group'}, $task->{'parent_recursion'}, $task->{'resolve_names'}, $task->{'os_detect'});
		}

		# If the agent already exists update parent and continue
		if ($agent_id > 0) {
			if ($parent_id > 0) {
				db_do ($dbh, 'UPDATE tagente SET id_parent = ? WHERE id_agente = ?', $parent_id, $agent_id );
			}
			next;
		}

		# Filter by TCP port
		if ((defined ($task->{'recon_ports'})) && ($task->{'recon_ports'} ne "")) {
			next unless (tcp_scan ($pa_config, $addr, $task->{'recon_ports'}) > 0);
		}

		# Filter by OS
		my $id_os = 11; # Network by default
		if ($task->{'os_detect'} == 1){
			$id_os = guess_os ($pa_config, $addr);
			next if ($task->{'id_os'} > 0 && $task->{'id_os'} != $id_os);
		}
		
        # GIS Code -----------------------------

		# If GIS is activated try to geolocate the ip address of the agent 
        # and store also it's position.

		if($pa_config->{'activate_gis'} == 1 && $pa_config->{'recon_reverse_geolocation_mode'} !~ m/^disabled$/i) {

			# Try to get aproximated positional information for the Agent.
			my $region_info = undef;
			if ($pa_config->{'recon_reverse_geolocation_mode'} =~ m/^sql$/i) {
				logger($pa_config, "Trying to get gis data of $addr from the SQL database", 8);
				$region_info = get_reverse_geoip_sql($pa_config, $addr, $dbh);	
			}
			elsif ($pa_config->{'recon_reverse_geolocation_mode'} =~ m/^file$/i) {
				logger($pa_config, "Trying to get gis data of $addr from the file database", 8);
				$region_info = get_reverse_geoip_file($pa_config, $addr);	
			}
			else {
				logger($pa_config, "ERROR:Trying to get gis data of $addr. Unknown source", 5);
			}
			if (defined($region_info))  {
				my $location_description = '';
				if (defined($region_info->{'region'})) {
					$location_description .= "$region_info->{'region'}, ";
				}
				if (defined($region_info->{'city'})) {
					$location_description .= "$region_info->{'city'}, ";
				}
				if (defined($region_info->{'country_name'})) {
					$location_description .= "($region_info->{'country_name'})";
				}
				# We store a random offset in the coordinates to avoid all the agents apear on the same place.
				my ($longitude, $latitude) = get_random_close_point ($pa_config, $region_info->{'longitude'}, $region_info->{'latitude'});
				
				logger($pa_config, "Placing agent on random position (Lon,Lat)  =  ($longitude, $latitude)", 8);
				# Crate a new agent adding the positional info (as is unknown we set 0 time_offset, and 0 altitude)
				$agent_id = pandora_create_agent ($pa_config, $pa_config->{'servername'},
					                                  $host_name, $addr, $task->{'id_group'}, 
									  $parent_id, $id_os, '', 300, $dbh, 0, 
								          $longitude, $latitude, 0, $location_description);
			}
			else {
				logger($pa_config,"Id location of '$addr' for host '$host_name' NOT found", 3);
				# Create a new agent
				$agent_id = pandora_create_agent ($pa_config, $pa_config->{'servername'},
					                                  $host_name, $addr, $task->{'id_group'},
									  $parent_id, $id_os, '', 300, $dbh);
			}
		}
		# End of GIS code -----------------------------      
		else {	
			# Create a new agent
			$agent_id = pandora_create_agent ($pa_config, $pa_config->{'servername'},
					                                  $host_name, $addr, $task->{'id_group'},
									  $parent_id, $id_os, '', 300, $dbh);
		}

		# Check agent creation
		if ($agent_id <= 0) {
			logger($pa_config, "Error creating agent '$host_name'.", 3);
			next;
		}

		# Add the new address if it does not exist
		my $addr_id = get_addr_id ($dbh, $addr);
		$addr_id = add_address ($dbh, $addr) unless ($addr_id > 0);
		if ($addr_id <= 0) {
			logger($pa_config, "Could not add address '$addr' for host '$host_name'.", 3);
			next;
		}

		# Assign the new address to the agent
		my $agent_addr_id = get_agent_addr_id ($dbh, $addr_id, $agent_id);
		if ($agent_addr_id <= 0) {
			db_do ($dbh, 'INSERT INTO taddress_agent (`id_a`, `id_agent`)
		                  VALUES (?, ?)', $addr_id, $agent_id);
		}

		# Create network profile modules for the agent
		create_network_profile_modules ($pa_config, $dbh, $agent_id, $task->{'id_network_profile'}, $addr, $task->{'snmp_community'});

		# Generate an event
		pandora_event ($pa_config, "[RECON] New host [$host_name] detected on network [" . $task->{'subnet'} . ']',
		               $task->{'id_group'}, $agent_id, 2, 0, 0, 'recon_host_detected', 0, $dbh);
		
		$added_hosts .= "$addr ";
	}

	# Create an incident with totals
	if ($added_hosts ne '' && $task->{'create_incident'} == 1) {
		my $text = "At " . strftime ("%Y-%m-%d %H:%M:%S", localtime()) . " ($added_hosts) new hosts were detected by Pandora FMS Recon Server running on [" . $pa_config->{'servername'} . "_Recon]. This incident has been automatically created following instructions for this recon task [" . $task->{'id_group'} . "].\n\n";
		if ($task->{'id_network_profile'} > 0) {
			$text .= "Aditionally, and following instruction for this task, agent(s) has been created, with modules assigned to network component profile [" . get_nc_profile_name ($dbh, $task->{'id_network_profile'}) . "]. Please check this agent as soon as possible to verify it.";
		}
		$text .= "\n\nThis is the list of IP addresses found: \n\n$added_hosts";
		pandora_create_incident ($pa_config, $dbh, "[RECON] New hosts detected", $text, 0, 0, 'Pandora FMS Recon Server', $task->{'id_group'});
	}

	logger($pa_config, "Finished recon task for net " . $task->{'subnet'} . ".", 10);

	# Mark recon task as done
	update_recon_task ($dbh, $task_id, -1);
}


##########################################################################
# Returns the ID of the parent of the given host if available.
##########################################################################	
sub get_host_parent {
	my ($pa_config, $host, $dbh, $group, $max_depth, $resolve, $os_detect) = @_;
	
	# Call nmap
	my $np = new PandoraFMS::NmapParser;
	eval {
		$np->parsescan($pa_config->{'nmap'},'-nsP --traceroute', ($host));
	};
	if ($@) {
		return 0;
	}
	
	# Get hops
	my ($h) = $np->all_hosts ();
	return 0 unless defined ($h);
	my @all_hops = $h->all_trace_hops ();
	my @hops;
	
	# Skip target host
	pop (@all_hops);
	
	# Save the last max_depth hosts in reverse order
	for (my $i = 0; $i < $max_depth; $i++) {
		my $hop = pop (@all_hops);
		last unless defined ($hop);
		push (@hops, $hop);
	}
	
	# Parse hops from first to last
	my $parent_id = 0;
	for (my $i = 0; $i < $max_depth; $i++) {
		my $hop = pop (@hops);
		last unless defined ($hop);
		
		# Get host information
		my $host_addr = $hop->ipaddr ();
		
		# Check if the host exists
		my $agent = get_agent_from_addr ($dbh, $host_addr);
		if (defined ($agent)) {
			# Move to the next host
			$parent_id = $agent->{'id_agente'};
			next;
		}
		
		
		# Add the new address if it does not exist
		my $addr_id = get_addr_id ($dbh, $host_addr);
		$addr_id = add_address ($dbh, $host_addr) unless ($addr_id > 0);
	
		# Should not happen
		if ($addr_id <= 0) {
				logger($pa_config, "Could not add address '$host_addr'", 1);
				return 0;
		}
		
		# Get the host's name
		my $host_name = undef;
		if ($resolve == 1){
			$host_name = gethostbyaddr(inet_aton($host_addr), AF_INET);
		}
		$host_name = $host_addr unless defined ($host_name);
		
		# Detect host's OS
		my $id_os = 11;
		if ($os_detect == 1) {
			$id_os = guess_os ($pa_config, $host_addr);
		}
	
		# Create the host
		my $agent_id = pandora_create_agent ($pa_config, $pa_config->{'servername'}, $host_name, $host_addr, $group, $parent_id, $id_os, '', 300, $dbh);
		$agent_id = 0 unless defined ($parent_id);
		db_do ($dbh, 'INSERT INTO taddress_agent (`id_a`, `id_agent`)
			          VALUES (?, ?)', $addr_id, $agent_id);
		
		# Move to the next host
		$parent_id = $agent_id;
	}
	return $parent_id;
}

##############################################################################
# TCP scan the given host/port. Returns 1 if successful, 0 otherwise.
##############################################################################
sub tcp_scan ($$$) {
	my ($pa_config, $host, $portlist) = @_;
	
	my $nmap = $pa_config->{'nmap'};
	my $output = `$nmap -p$portlist $host | grep open | wc -l`;
	return 0 if ($? != 0);
	return $output;
}

##########################################################################
# Guess OS using xprobe2.
##########################################################################
sub guess_os {
    my ($pa_config, $host) = @_;
    
    # Use xprobe2 if available
	my $xprobe = $pa_config->{'xprobe2'};
	if (-e $xprobe){
			my $output = `$xprobe $host 2> /dev/null | grep 'Running OS' | head -1`;
			return 10 if ($? != 0);
			return pandora_get_os ($output);
	}
	
	# Use nmap by default
	my $nmap = $pa_config->{'nmap'};
	my $output = `$nmap -F -O $host 2> /dev/null | grep 'Aggressive OS guesses'`;
	return 10 if ($? != 0);
	return pandora_get_os ($output);
}

##########################################################################
# Update recon task status.
##########################################################################
sub update_recon_task ($$$) {
	my ($dbh, $id_task, $status) = @_;
	
	db_do ($dbh, 'UPDATE trecon_task SET utimestamp = ?, status = ? WHERE id_rt = ?', time (), $status, $id_task);
} 

##########################################################################
# Create network profile modules for the given agent.
##########################################################################
sub create_network_profile_modules {
	my ($pa_config, $dbh, $agent_id, $np_id, $addr, $snmp_community) = @_;
	
	return unless ($np_id > 0);
	
	# Get network components associated to the network profile
	my @np_components = get_db_rows ($dbh, 'SELECT * FROM tnetwork_profile_component WHERE id_np = ?', $np_id);
	
	foreach my $np_component (@np_components) {
		
		# Get network component data
		my $component = get_db_single_row ($dbh, 'SELECT * FROM tnetwork_component wHERE id_nc = ?', $np_component->{'id_nc'});
		if (! defined ($component)) {
			logger($pa_config, "Network component ID " . $np_component->{'id_nc'} . " for agent $addr not found.", 3);
			next;
		}
		
		logger($pa_config, "Processing network component '" . safe_output ($component->{'name'}) . "' for agent $addr.", 10);
		
		# Use snmp_community from network task instead the component snmp_community
		$component->{'snmp_community'} = safe_output ($snmp_community);
		
		# Create the module
		my $module_id = db_insert ($dbh, 'id_agente_modulo', 'INSERT INTO tagente_modulo (id_agente, id_tipo_modulo, descripcion, nombre, max, min, module_interval, tcp_port, tcp_send, tcp_rcv, snmp_community, snmp_oid, ip_target, id_module_group, flag, disabled, plugin_user, plugin_pass, plugin_parameter, max_timeout, id_modulo, min_warning, max_warning, str_warning, min_critical, max_critical, str_critical, min_ff_event, id_plugin, post_process)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 0, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
			$agent_id, $component->{'type'}, $component->{'description'}, $component->{'name'}, $component->{'max'}, $component->{'min'}, $component->{'module_interval'}, $component->{'tcp_port'}, $component->{'tcp_send'}, $component->{'tcp_rcv'}, $component->{'snmp_community'},
			$component->{'snmp_oid'}, $addr, $component->{'id_module_group'}, $component->{'plugin_user'}, $component->{'plugin_pass'}, $component->{'plugin_parameter'}, $component->{'max_timeout'}, $component->{'id_modulo'}, $component->{'min_warning'}, $component->{'max_warning'}, $component->{'str_warning'}, $component->{'min_critical'}, $component->{'max_critical'}, $component->{'str_critical'}, $component->{'min_ff_event'}, $component->{'id_plugin'}, $component->{'post_process'});
		
		# An entry in tagente_estado is necessary for the module to work
			db_do ($dbh, 'INSERT INTO tagente_estado (`id_agente_modulo`, `id_agente`, `last_try`, current_interval) VALUES (?, ?, \'1970-01-01 00:00:00\', ?)', $module_id, $agent_id, $component->{'module_interval'});
		
		logger($pa_config, 'Creating module ' . safe_output ($component->{'name'}) . " for agent $addr from network component.", 10);
	}
}

##########################################################################
# Executes recon scripts
##########################################################################	
sub exec_recon_script ($$$) {
	my ($pa_config, $dbh, $task) = @_;
	
	# Get recon plugin data	
	my $script = get_db_single_row ($dbh, 'SELECT * FROM trecon_script WHERE id_recon_script = ?', $task->{'id_recon_script'});
	return -1 unless defined ($script);
	
	my $command = safe_output($script->{'script'});
	my $field1 = safe_output($task->{'field1'}); 
	my $field2 = safe_output($task->{'field2'});
	my $field3 = safe_output($task->{'field3'}); 
	my $field4 = safe_output($task->{'field4'});
	
	if (-x $command) {
		`$command $task->{'id_rt'} $task->{'id_group'} $task->{'create_incident'} $field1 $field2 $field3 $field4`;
	} else {
		logger ($pa_config, "Cannot execute recon task command $command.");
	}
	
	# Notify this recon task is ended
	update_recon_task ($dbh, $task->{'id_rt'}, -1);
	
	return 0;
}

1;
__END__
