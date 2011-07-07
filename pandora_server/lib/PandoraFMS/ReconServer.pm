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
		exec_recon_script ($pa_config, $dbh, $task);
		return;
	}

	# Call nmap
	my $nmap = $pa_config->{'nmap'};
	my $subnet = $task->{'subnet'};
	my @output = `$nmap -nsP $subnet`;
	if ($? != 0) {
		update_recon_task ($dbh, $task_id, -1);
		return;
	}

	# Parse nmap output
	my $addr = '';
	my $found_hosts = {};
	foreach my $line (@output) {
		chomp ($line);
		
		if ($line =~ m/Nmap scan report for (\S+).*/) {
			$addr = $1;
		} elsif ($line =~ m/Host is up \((\S+)s.*/) {
			next unless ($addr ne '');
			$found_hosts->{$addr} = 1;	
			$addr = '';
		}
	}

	# Process found hosts
	my $progress = 0;
	my $added = '';
	my $total_hosts = scalar (keys (%{$found_hosts}));
	foreach my $addr (keys (%{$found_hosts})) {
		$progress++;
		
		# Update the recon task or break if it does not exist anymore
		last if (update_recon_task ($dbh, $task_id, ceil ($progress / ($total_hosts / 100))) eq '0E0');
       
		# Does the host already exist?
		my $agent = get_agent_from_addr ($dbh, $addr);
		my $agent_id = defined ($agent) ? $agent->{'id_agente'} : 0;
		if ($agent_id > 0) {

			# Skip if not in learning mode or parent detection is disabled
			next if ($agent->{'modo'} != 1 || $task->{'parent_detection'} == 0);
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

		# Resolve hostnames
		my $host_name = undef;
		if ($task->{'resolve_names'} == 1){
			$host_name = gethostbyaddr (inet_aton($addr), AF_INET);
		}
		$host_name = $addr unless defined ($host_name);

		# Add the new address if it does not exist
		my $addr_id = get_addr_id ($dbh, $addr);
		$addr_id = add_address ($dbh, $addr) unless ($addr_id > 0);
		if ($addr_id <= 0) {
			logger($pa_config, "Could not add address '$addr' for host '$host_name'.", 3);
			next;
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

		# Assign the new address to the agent
		db_do ($dbh, 'INSERT INTO taddress_agent (`id_a`, `id_agent`)
		                  VALUES (?, ?)', $addr_id, $agent_id);

		# Create network profile modules for the agent
		create_network_profile_modules ($pa_config, $dbh, $agent_id, $task->{'id_network_profile'}, $addr, $task->{'snmp_community'});

		# Generate an event
        pandora_event ($pa_config, "[RECON] New host [$host_name] detected on network [" . $task->{'subnet'} . ']',
                       $task->{'id_group'}, $agent_id, 2, 0, 0, 'recon_host_detected', 0, $dbh);
		
        $added .= $addr . ' ';
	}

	# Create an incident with totals
	if ($total_hosts > 0 && $task->{'create_incident'} == 1) {
		my $text = "At " . strftime ("%Y-%m-%d %H:%M:%S", localtime()) . " ($total_hosts) new hosts were detected by Pandora FMS Recon Server running on [" . $pa_config->{'servername'} . "_Recon]. This incident has been automatically created following instructions for this recon task [" . $task->{'id_group'} . "].\n\n";
		if ($task->{'id_network_profile'} > 0) {
			$text .= "Aditionally, and following instruction for this task, agent(s) has been created, with modules assigned to network component profile [" . get_nc_profile_name ($dbh, $task->{'id_network_profile'}) . "]. Please check this agent as soon as possible to verify it.";
		}
		$text .= "\n\nThis is the list of IP addresses found: \n\n$added";
		pandora_create_incident ($pa_config, $dbh, "[RECON] New hosts detected", $text, 0, 0, 'Pandora FMS Recon Server', $task->{'id_group'});
	}

	# Mark recon task as done
	update_recon_task ($dbh, $task_id, -1);
}


##########################################################################
# Returns the ID of the parent of the given host if available.
##########################################################################	
sub get_host_parent {
	my ($pa_config, $host, $dbh, $group, $max_depth, $resolve, $os_detect) = @_;

	# Recursive exit condition
	return 0 if ($max_depth == 0);

	# Call nmap
	my $nmap = $pa_config->{'nmap'};
	#my $traceroutetimeout = $pa_config->{'networktimeout'};
	my @output = `$nmap --traceroute -nsP $host`;
	return 0 if ($? != 0);

	# Parse nmap output
	my $parent_addr = '';
	foreach my $line (@output) {
		chomp ($line);
		
		if ($line =~ m/\d+\s+\S+\s+ms\s+(\S+)/) {
			next if ($1 eq '*' || $1 eq $host);
			$parent_addr = $1;
		}
	}
	
	# No parent found
	return 0 if ($parent_addr eq '');
	
	# Check if the parent host exists
	my $parent = get_agent_from_addr ($dbh, $parent_addr);
	my $parent_id = defined ($parent) ? $parent->{'id_agente'} : 0;
	return $parent_id if ($parent_id > 0);

	# Add the new address if it does not exist
	my $addr_id = get_addr_id ($dbh, $parent_addr);
	$addr_id = add_address ($dbh, $parent_addr) unless ($addr_id > 0);
	
	# Should not happen
	if ($addr_id <= 0) {		
			logger($pa_config, "Could not add address '$parent_addr'", 1);
			return 0;
	}

	# Get the parent's name
	my $parent_name = undef;
	if ($resolve == 1){
		$parent_name = gethostbyaddr(inet_aton($parent_addr), AF_INET);
	}
	$parent_name = $parent_addr unless defined ($parent_name);
	
	# Detect parent's OS
	my $id_os = 11;
	if ($os_detect == 1) {
		$id_os = guess_os ($pa_config, $parent_addr);
	}

	# Get the parent's parent
	my $parent_parent = get_host_parent ($pa_config, $parent_addr, $dbh, $group, $max_depth-1, $resolve, $os_detect);

	# Create the parent
	my $agent_id = pandora_create_agent ($pa_config, $pa_config->{'servername'}, $parent_name, $parent_addr, $group, $parent_parent, $id_os, '', 300, $dbh);
	db_do ($dbh, 'INSERT INTO taddress_agent (`id_a`, `id_agent`)
		          VALUES (?, ?)', $addr_id, $agent_id);

	return $agent_id;
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
# Return the agent given the IP address.
##########################################################################
sub get_agent_from_addr ($$) {
	my ($dbh, $ip_address) = @_;

	return 0 if (! defined ($ip_address) || $ip_address eq '');

	my $agent = get_db_single_row ($dbh, 'SELECT * FROM taddress, taddress_agent, tagente
	                                    WHERE tagente.id_agente = taddress_agent.id_agent
	                                    AND taddress_agent.id_a = taddress.id_a
	                                    AND ip = ?', $ip_address);
	return $agent
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

		logger($pa_config, "Processing network component '" . $component->{'name'} . "' for agent $addr.", 10);

        # Use snmp_community from network task instead the component snmp_community
        $component->{'snmp_community'} = $snmp_community;

		# Create the module
		my $module_id = db_insert ($dbh, 'id_agente_modulo', 'INSERT INTO tagente_modulo (id_agente, id_tipo_modulo, descripcion, nombre, max, min, module_interval, tcp_port, tcp_send, tcp_rcv, snmp_community, snmp_oid, ip_target, id_module_group, flag, disabled, plugin_user, plugin_pass, plugin_parameter, max_timeout, id_modulo )
		                                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 0, ?, ?, ?, ?, ?)',
		                                             $agent_id, $component->{'type'}, $component->{'description'}, safe_input($component->{'name'}), $component->{'max'}, $component->{'min'}, $component->{'module_interval'}, $component->{'tcp_port'}, $component->{'tcp_send'}, $component->{'tcp_rcv'}, $component->{'snmp_community'},
		                                             $component->{'snmp_oid'}, $addr, $component->{'id_module_group'}, $component->{'plugin_user'}, $component->{'plugin_pass'}, $component->{'plugin_parameter'}, $component->{'max_timeout'}, $component->{'id_modulo'});

		# An entry in tagente_estado is necessary for the module to work
        	db_do ($dbh, 'INSERT INTO tagente_estado (`id_agente_modulo`, `id_agente`, `last_try`, current_interval) VALUES (?, ?, \'1970-01-01 00:00:00\', ?)', $module_id, $agent_id, $component->{'module_interval'});

		logger($pa_config, 'Creating module ' . $component->{'name'} . " for agent $addr from network component '" . $component->{'name'} . "'.", 10);
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

	logger($pa_config, 'Executing recon script ' . safe_output($script->{'name'}), 10);

	my $command = safe_output($script->{'script'});
	my $field1 = safe_output($task->{'field1'}); 
	my $field2 = safe_output($task->{'field2'});
	my $field3 = safe_output($task->{'field3'}); 
	my $field4 = safe_output($task->{'field4'});

	`$command $task->{'id_rt'} $task->{'id_group'} $task->{'create_incident'} $field1 $field2 $field3 $field4`;

	# Notify this recon task is ended
	update_recon_task ($dbh, $task->{'id_rt'}, -1);

	return 0;
}

1;
__END__
