#!/usr/bin/perl
# (c) Ártica Soluciones Tecnológicas 2014 <info@artica.es>
# WMI Recon script.

use IO::Socket::INET;
use POSIX qw(setsid strftime strftime ceil);

use strict;
use warnings;

# Default lib dir for RPM and DEB packages
use lib '/usr/lib/perl5';

use PandoraFMS::Tools;
use PandoraFMS::DB;
use PandoraFMS::Core;
use PandoraFMS::Config;
use PandoraFMS::NmapParser;

# Pandora FMS configuration hash.
my $OSNAME = $^O;
my %CONF;

if ($OSNAME eq "freebsd") {
	%CONF = ('quiet' => 0,
			'verbosity' => 1,
			'daemon' => 0,
			'PID' => '',
			'pandora_path' => '/usr/local/etc/pandora/pandora_server.conf',
			'networktimeout' => 2,
			'icmp_checks' => 1,
			'nmap_timing_template' => 2,
			'wmi_client' => '/usr/local/bin/wmic');
} else {
	%CONF = ('quiet' => 0,
			'verbosity' => 1,
			'daemon' => 0,
			'PID' => '',
			'pandora_path' => '/etc/pandora/pandora_server.conf',
			'networktimeout' => 2,
			'icmp_checks' => 1,
			'nmap_timing_template' => 2,
			'wmi_client' => '/usr/bin/wmic');
}

# If set to 1 incidents will be created in the Pandora FMS Console.
my $CREATE_INCIDENT;

# Database connection handler.
my $DBH;

# ID of the group where new agents will be placed.
my $GROUP_ID;

# Comma separated list of target networks.
my $NETWORKS;

# ID of the recon task.
my $TASK_ID;

# Comma separated list of username%password tokens.
my $WMI_AUTH;

##########################################################################
# Update recon task status.
##########################################################################
sub update_recon_task($$$) {
	my ($DBH, $id_task, $status) = @_;

	db_do ($DBH, 'UPDATE trecon_task SET utimestamp = ?, status = ? WHERE id_rt = ?', time (), $status, $id_task);
}

##########################################################################
# Show help
##########################################################################
sub show_help {
	print "\nPandora FMS WMI Recon Script.\n";
	print "(c) Artica ST 2014 <info\@artica.es>\n\n";
	print "Usage:\n\n";
	print "   $0 <task_id> <group_id> <create_incident_flag> <network> <wmi auth>\n\n";
	print " * network: network to scan (e.g. 192.168.100.0/24)\n";
	print " * wmi auth: comma separated list of WMI authentication tokens in the format username%password (e.g. Administrador%pass)\n";
	print "\n The other parameters are automatically filled by the Pandora FMS Server.\n\n\n";
	exit;
}

##########################################################################
# Get SNMP response.
##########################################################################
sub get_snmp_response($$$) {
	my ($target_timeout, $target_community, $addr) = @_;

	# The OID used is the SysUptime OID
	my $buffer = `/usr/bin/snmpget -v 1 -r0 -t$target_timeout -OUevqt -c '$target_community' $addr .1.3.6.1.2.1.1.3.0 2>/dev/null`;

	# Remove forbidden caracters
	$buffer =~ s/\l|\r|\"|\n|\<|\>|\&|\[|\]//g;
	
	return $buffer;
}

##########################################################################
# Scan target networks for hosts and execute the given function on each
# host.
##########################################################################
my @ADDED_HOSTS;
sub recon_scan($$) {
	my ($task, $function) = @_;

	# Timeout in ms.
	my $timeout = $CONF{'networktimeout'} * 1000;

	# Added -PE to make nmap behave like ping and avoid confusion if ICMP traffic is blocked.
	my $nmap_args = '-nsP -PE --max-retries ' . $CONF{'icmp_checks'} . ' --host-timeout '.$timeout.' -T'.$CONF{'nmap_timing_template'};

	# Scan the network.
	my $np = new PandoraFMS::NmapParser;
	$np->parsescan($CONF{'nmap'}, $nmap_args, split(',', $task->{'subnet'}));

	my @up_hosts = $np->all_hosts ('up');
	my $total_up = scalar (@up_hosts);
	my $progress = 0;
	foreach my $host (@up_hosts) {
		 $progress++;
		
		# Update the recon task or break if it does not exist anymore.
		last if (update_recon_task ($DBH, $task->{'id_rt'}, ceil ($progress / ($total_up / 101))) eq '0E0');

		# Get the host address.
		my $addr = $host->addr();
		next unless ($addr ne '0');

		# Execute the given function on the agent.
		$function->($task, $addr);
	}

	# Mark the recon task as done.
	update_recon_task ($DBH, $task->{'id_rt'}, -1);

	# Create an incident.
	if (defined($ADDED_HOSTS[0]) && $task->{'create_incident'} == 1) {
		my $text = "At " . strftime ("%Y-%m-%d %H:%M:%S", localtime()) . " (". scalar(@ADDED_HOSTS) . ") new hosts were detected by Pandora FMS WMI Recon Script running on [" . $CONF{'servername'} . "_Recon]. This incident has been automatically created following instructions for this recon task [" . $task->{'id_group'} . "].\n\n";
		$text .= "\n\nThis is the list of IP addresses found: \n\n" . join(',', @ADDED_HOSTS);
		pandora_create_incident (\%CONF, $DBH, "[RECON] New hosts detected", $text, 0, 0, 'Pandora FMS Recon Server', $task->{'id_group'});
	}
}

##########################################################################
# Create a Pandora FMS agent for the given address if it does not exist.
##########################################################################
sub create_pandora_agent($$) {
	my ($task, $addr) = @_;

	# Does the agent already exist?
	my $agent = get_agent_from_addr ($DBH, $addr);
	if (! defined ($agent)) {
		$agent = get_agent_from_name ($DBH, $addr);
	}

	# Create the agent.
	my $agent_id = defined($agent) ? $agent->{'id_agente'} : 0;
	if ($agent_id <= 0) {
		$agent_id = pandora_create_agent(\%CONF, $CONF{'servername'}, $addr, $addr, $GROUP_ID, 0, 9, '', 300, $DBH, 0);
		if ($agent_id <= 0) {
			logger(\%CONF, "Error creating agent '$addr'.", 3);
			return undef;
		}

		# Generate an event
		pandora_event (\%CONF, "[WMI RECON SCRIPT] New host [$addr] detected on network [" . $task->{'subnet'} . ']', $GROUP_ID, $agent_id, 2, 0, 0, 'recon_host_detected', 0, $DBH);

		push(@ADDED_HOSTS, $addr);
		
		# Get the created agent.
		$agent = get_agent_from_name ($DBH, $addr);
		return undef unless defined($agent);
	}

	# Add the new address if it does not exist
	my $addr_id = get_addr_id ($DBH, $addr);
	$addr_id = add_address ($DBH, $addr) unless ($addr_id > 0);
	if ($addr_id <= 0) {
		logger(\%CONF, "Could not add address '$addr' for host '$addr'.", 3);
		return $agent;
	}

	# Assign the new address to the agent
	my $agent_addr_id = get_agent_addr_id ($DBH, $addr_id, $agent_id);
	if ($agent_addr_id <= 0) {
		db_do ($DBH, 'INSERT INTO taddress_agent (`id_a`, `id_agent`) VALUES (?, ?)', $addr_id, $agent_id);
	}

	return $agent;
}


########################################################################################
# Returns the credentials with which the host responds to WMI queries or undef if it
# does not respond to WMI.
########################################################################################
sub responds_to_wmi($) {
	my ($target) = @_;

	my @auth_array = defined($WMI_AUTH) ? split(',', $WMI_AUTH) : ('');
	foreach my $auth (@auth_array) {
		my @output;
		if ($auth ne '') {
			@output = `$CONF{'wmi_client'} -U $auth //$target "SELECT * FROM Win32_ComputerSystem" 2>&1`;
		}
		else {
			@output = `$CONF{'wmi_client'} -N //$target "SELECT * FROM Win32_ComputerSystem" 2>&1`;
		}
	
		foreach my $line (@output) {
			chomp ($line);
			return $auth if ($line =~ m/^CLASS: Win32_ComputerSystem$/);
		}
	}

	return undef;
}

########################################################################################
# Performs a wmi get requests and returns the response as an array.
########################################################################################
sub wmi_get($$$) {
	my ($target, $auth, $query) = @_;

	my @output;
	if (defined($auth) && $auth ne '') {
		@output = `$CONF{'wmi_client'} -U $auth //$target "$query" 2>&1`;
	}
	else {
		@output = `$CONF{'wmi_client'} -N //$target "$query" 2>&1`;
	}
	
	# Something went wrong.
	return () if ($? != 0);

	return @output;
}

########################################################################################
# Performs a WMI request and returns the requested column of the first row. Returns
# undef on error.
########################################################################################
sub wmi_get_value($$$$) {
	my ($target, $auth, $query, $column) = @_;
	my @result;

	my @output = wmi_get($target, $auth, $query);
	return undef unless defined($output[2]);

	my $line = $output[2];
	chomp($line);
	my @columns = split(/\|/, $line);
	return undef unless defined($columns[$column]);

	return $columns[$column];
}

########################################################################################
# Performs a WMI request and returns row values for the requested column in an array.
########################################################################################
sub wmi_get_value_array($$$$) {
	my ($target, $auth, $query, $column) = @_;
	my @result;

	my @output = wmi_get($target, $auth, $query);
	foreach (my $i = 2; defined($output[$i]); $i++) {
		my $line = $output[$i];
		chomp($line);
		my @columns = split(/\|/, $line);
		next unless defined($columns[$column]);
		push(@result, $columns[$column]);
	}

	return @result;
}

##########################################################################
# Create a WMI module for the given agent.
##########################################################################
sub wmi_module($$$$$$$$;$) {
	my ($agent_id, $target, $wmi_query, $wmi_auth,
	    $column, $module_name, $module_description, $module_type,
		$unit) = @_;

	# Check whether the module already exists.
	my $module_id = get_agent_module_id($DBH, $module_name, $agent_id);
	return if ($module_id > 0);

	my ($user, $pass) = ($wmi_auth ne '') ? split('%', $wmi_auth) : (undef, undef);
	my %module = ('descripcion' => safe_input($module_description),
	              'id_agente' => $agent_id,
	              'id_modulo' => 6,
	              'id_tipo_modulo' => get_module_id($DBH, $module_type),
	              'ip_target' => $target,
	              'nombre' => safe_input($module_name),
	              'plugin_pass' => defined($pass) ? $pass : '',
	              'plugin_user' => defined($user) ? $user : '',
	              'snmp_oid' => $wmi_query,
	              'tcp_port' => $column,
	              'unit' => defined($unit) ? $unit : '');
	pandora_create_module_from_hash(\%CONF, \%module, $DBH);
}

##########################################################################
# Add wmi modules to the given host.
##########################################################################
sub wmi_scan() {
	my ($task, $target) = @_;

	my $auth = responds_to_wmi($target);
	return unless defined($auth);
			
	# Create the agent if it does not exist.
	my $agent = create_pandora_agent($task, $target);
	next unless defined($agent);

	# CPU.
	my @cpus = wmi_get_value_array($target, $auth, 'SELECT DeviceId FROM Win32_Processor', 0);
	foreach my $cpu (@cpus) {
		wmi_module($agent->{'id_agente'}, $target, "SELECT LoadPercentage FROM Win32_Processor WHERE DeviceId='$cpu'", $auth, 1, "CPU Load $cpu", "Load for $cpu (%)", 'generic_data');
	}

	# Memory.
	my $mem = wmi_get_value($target, $auth, 'SELECT FreePhysicalMemory FROM Win32_OperatingSystem', 0);
	if (defined($mem)) {
		wmi_module($agent->{'id_agente'}, $target, "SELECT FreePhysicalMemory, TotalVisibleMemorySize FROM Win32_OperatingSystem", $auth, 0, 'FreeMemory', 'Free memory', 'generic_data', 'KB');
	}

	# Disk.
	my @units = wmi_get_value_array($target, $auth, 'SELECT DeviceID FROM Win32_LogicalDisk', 0);
	foreach my $unit (@units) {
		wmi_module($agent->{'id_agente'}, $target, "SELECT FreeSpace FROM Win32_LogicalDisk WHERE DeviceID='$unit'", $auth, 1, "FreeDisk $unit", 'Available disk space in kilobytes', 'generic_data', 'KB');
	}
}

##########################################################################
##########################################################################
## Main.
##########################################################################
##########################################################################
if ($#ARGV < 4){
	show_help();
}

# Passed by the server.
$TASK_ID = $ARGV[0];
$GROUP_ID = $ARGV[1];
$CREATE_INCIDENT = $ARGV[2];

# User defined parameters.
$NETWORKS = $ARGV[3];
$WMI_AUTH = $ARGV[4];

# Read the configuration file.
pandora_load_config(\%CONF);
pandora_start_log(\%CONF);

# Connect to the DB.
$DBH = db_connect ($CONF{'dbengine'}, $CONF{'dbname'}, $CONF{'dbhost'}, $CONF{'dbport'}, $CONF{'dbuser'}, $CONF{'dbpass'});

# Get the recon task from the database.
my $task = get_db_single_row ($DBH, 'SELECT * FROM trecon_task WHERE id_rt = ?', $TASK_ID);
die("Error retrieving recon task ID $TASK_ID\n") unless defined($task);

# Scan!
$task->{'subnet'} = $NETWORKS;
$task->{'id_group'} = $GROUP_ID;
$task->{'create_incident'} = $CREATE_INCIDENT;
recon_scan($task, \&wmi_scan);

