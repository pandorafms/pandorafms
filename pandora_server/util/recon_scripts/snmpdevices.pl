#!/usr/bin/perl
# (c) Sancho Lerena 2010 <slerena@artica.es>
# SNMP Recon App script

use POSIX qw(setsid strftime strftime ceil);

use strict;
use warnings;

use IO::Socket::INET;
use NetAddr::IP;

# Default lib dir for RPM and DEB packages

use lib '/usr/lib/perl5';

use PandoraFMS::Tools;
use PandoraFMS::DB;
use PandoraFMS::Core;
use PandoraFMS::Config;
##########################################################################
# Global variables so set behaviour here:

my $target_timeout = 5; # Fixed to 5 secs by default
my $target_interval = 600;

##########################################################################
# Code begins here, do not touch
##########################################################################
my $pandora_conf = "/etc/pandora/pandora_server.conf";
my $task_id = $ARGV[0]; # Passed automatically by the server
my $target_group = $ARGV[1]; # Defined by user
my $create_incident = $ARGV[2]; # Defined by user

# Used Custom Fields in this script
my $target_network = $ARGV[3]; # Filed1 defined by user
my $target_community = $ARGV[4]; # Field2 defined by user
my $all_mode = $ARGV[5]; # Field3 defined by user

$all_mode = '' unless defined($all_mode);

# Unused Custom Fields in this script
# my $field4 = $ARGV[6]; # Defined by user

##########################################################################
# Update recon task status.
##########################################################################
sub update_recon_task ($$$) {
	my ($dbh, $id_task, $status) = @_;

	db_do ($dbh, 'UPDATE trecon_task SET utimestamp = ?, status = ? WHERE id_rt = ?', time (), $status, $id_task);
}

##########################################################################
# Show help
##########################################################################
sub show_help {
	print "\nSpecific Pandora FMS SNMP Recon Plugin for SNMP device autodiscovery\n";
	print "(c) Artica ST 2011 <info\@artica.es>\n\n";
	print "Usage:\n\n";
	print "   $0 <task_id> <group_id> <create_incident_flag> <custom_field1> <custom_field2> <custom_field3>\n\n";
	print " * custom_field1 = network. i.e.: 192.168.100.0/24\n";
	print " * custom_field2 = snmp_community. \n";
	print " * custom_field3 = optative parameter to force process downed interfaces (use: '-a'). Only up interfaces are processed by default \n\n";
	print " Additional information:\nWhen the script is called from a recon task, 'task_id' parameter is automatically filled, ";
	print "group_id and create_incident_flag are passed from interface form combos and custom fields manually filled.\n\n\n";
	exit;
}

##########################################################################
# Get SNMP response.
##########################################################################
sub get_snmp_response ($$$) {
	my ($target_timeout, $target_community, $addr) = @_;

	# The OID used is the SysUptime OID
	my $buffer = `/usr/bin/snmpget -v 1 -r0 -t$target_timeout -OUevqt -c '$target_community' $addr .1.3.6.1.2.1.1.3.0 2>/dev/null`;

	# Remove forbidden caracters
	$buffer =~ s/\l|\r|\"|\n|\<|\>|\&|\[|\]//g;
	
	return $buffer;
}

##########################################################################
# Process a SNMP requestr and create the module XML
##########################################################################
sub process_module_snmp ($$$$$$$$$){
	
	my ($dbh, $target_community, $addr, $oid, $type, $module_name, $module_type_name, $module_description, $conf) = @_;
	
	my %parameters;
	
	# Obtain the type id from the type name
	$parameters{'id_tipo_modulo'} = get_module_id ($dbh,$module_type_name);
	$parameters{'nombre'} = safe_input($module_name);
	$parameters{'descripcion'} = $module_description;
	
	my $agent = get_agent_from_addr ($dbh, $addr);

	$parameters{'id_agente'} = $agent->{'id_agente'};
	$parameters{'ip_target'} = $addr;
	$parameters{'tcp_send'} = 1;
	$parameters{'snmp_community'} = $target_community;
	$parameters{'snmp_oid'} = $oid;

	# id_modulo = 2 for snmp modules
	$parameters{'id_modulo'} = 2;	

	#get_agent_module_id uses safe_input for module name so don't pass this variable using safe input!!!
	my $module_id = get_agent_module_id($dbh, $module_name, $parameters{'id_agente'});

	if($module_id == -1) {
		pandora_create_module_from_hash ($conf, \%parameters, $dbh);
	}
	else {
		pandora_update_module_from_hash ($conf, \%parameters, 'id_agente_modulo', $module_id, $dbh);
	}
}

##########################################################################
##########################################################################
# M A I N   C O D E
##########################################################################
##########################################################################


if ($#ARGV == -1){
	show_help();
}

# Pandora server configuration
my %conf;
$conf{"quiet"} = 0;
$conf{"verbosity"} = 1;	# Verbose 1 by default
$conf{"daemon"}=0;	# Daemon 0 by default
$conf{'PID'}="";	# PID file not exist by default
$conf{'pandora_path'} = $pandora_conf;

# Read config file
pandora_load_config (\%conf);

# Connect to the DB
my $dbh = db_connect ('mysql', $conf{'dbname'}, $conf{'dbhost'}, $conf{'dbport'}, $conf{'dbuser'}, $conf{'dbpass'});


# Start the network sweep
# Get a NetAddr::IP object for the target network
my $net_addr = new NetAddr::IP ($target_network);
if (! defined ($net_addr)) {
	logger (\%conf, "Invalid network " . $target_network . " for SNMP Recon App task", 1);
	update_recon_task ($dbh, $task_id, -1);
	return -1;
}

# Scan the network for hosts
my ($total_hosts, $hosts_found, $addr_found) = ($net_addr->num, 0, '');

my $last = 0;
for (my $i = 1; $net_addr <= $net_addr->broadcast; $i++, $net_addr++) {
	if($last == 1) {
		last;
	}
	
	my $net_addr_temp = $net_addr + 1;
	if($net_addr->broadcast eq $net_addr_temp) {
		$last = 1;
	}
	
	if ($net_addr =~ /\b\d{1,3}\.\d{1,3}\.\d{1,3}\.(\d{1,3})\b/) {
		if($1 eq '0' || $1 eq '255') {
			next;
		}
	}
	
	my $addr = (split(/\//, $net_addr))[0];
	$hosts_found ++;
	
	# Update the recon task 
	update_recon_task ($dbh, $task_id, ceil ($i / ($total_hosts / 100)));
      
	my $alive = 0;
	if (pandora_ping (\%conf, $addr) == 1) {
		$alive = 1;
	}

	next unless ($alive > 0);

	# Resolve the address
	my $host_name = gethostbyaddr(inet_aton($addr), AF_INET);
	$host_name = $addr unless defined ($host_name);
	#/usr/bin/snmpwalk -OUevqt -c 'public' -v 1 192.168.50.100 SNMPv2-MIB::sysName.0
	logger(\%conf, "SNMP Recon App found host $host_name.", 10);

	# Add the new address if it does not exist
	my $addr_id = get_addr_id ($dbh, $addr);

	my $resp;
	my $oid;
	my $module_type;
	my $module_description;
	my $module_name;
	my $xml = "";
	my $ax; # Counter
	my $conf = \%conf;
	
	$resp = "";
	
	# Obtain SNMP response
	$resp = get_snmp_response ($target_timeout, $target_community, $addr);

	# No valid SNMP response.
	if ($resp eq ""){
		next;
	}

	# Create agent if really has SNMP information
	$addr_id = add_address ($dbh, $addr) unless ($addr_id > 0);
	if ($addr_id <= 0) {
		logger (\%conf, "Could not add address '$addr' for host '$host_name'", 3);
		next;
	}

	# Check if the agent exists
	my $agent_id = get_agent_id($dbh, $host_name);
	
	# If the agent doesnt exist we create it
	if($agent_id == -1) {
		# Create a new agent
		$agent_id = pandora_create_agent (\%conf, $conf{'servername'}, $host_name, $addr, $target_group, 0, 11, '', 300, $dbh);
	}

	# Assign the new address to the agent
	db_do ($dbh, 'INSERT INTO taddress_agent (`id_a`, `id_agent`) VALUES (?, ?)', $addr_id, $agent_id);
	
	# Generate an event
	pandora_event (\%conf, "[RECON] New SNMP host [$host_name] detected on network [" . $target_network . ']', $target_group, $agent_id, 2, 0, 0, 'recon_host_detected', 0, $dbh);

	# SysUptime
	process_module_snmp ($dbh, $target_community, $addr, ".1.3.6.1.2.1.1.3.0", "ticks", "SysUptime", "remote_snmp_string", "System uptime reported by SNMP", $conf);

	# SysName
	process_module_snmp ($dbh, $target_community, $addr, ".1.3.6.1.2.1.1.5.0", "", "SysName", "remote_snmp_string", "System name reported by SNMP", $conf);

	# Local system total traffic 
	
	process_module_snmp ($dbh, $target_community, $addr, ".1.3.6.1.2.1.4.3.0", "", "Local InReceives", "remote_snmp_inc", "System local incoming traffic (bytes)", $conf);
	
	process_module_snmp ($dbh, $target_community, $addr, ".1.3.6.1.2.1.4.10.0", "", "Local OutRequests", "remote_snmp_inc", "System local outgoing traffic (bytes)", $conf);

	# Process interface list
	# Get interface indexes
		
	my $interface_indexes = `/usr/bin/snmpwalk -Ouvq -c '$target_community' -v 1 $addr ifIndex 2>/dev/null`;
	
	my @ids = split("\n", $interface_indexes);

	foreach my $ax (@ids) {
		my $oper_status = `/usr/bin/snmpwalk -OUevqt -c '$target_community' -v 1 $addr .1.3.6.1.2.1.2.2.1.8.$ax 2>/dev/null`;

		# If switch_mode is active and the interface is not up, we avoid it
		if($all_mode ne '-a' && $oper_status != 1) {
			next;
		}
		
		my $interface = `/usr/bin/snmpget -v 1 -r0 -t$target_timeout -OUevqt -c '$target_community' $addr RFC1213-MIB::ifDescr.$ax 2>/dev/null`;
				
		my $ip_address = `/usr/bin/snmpwalk -OnQ -c '$target_community' -v 1 $addr .1.3.6.1.2.1.4.20.1.2 | sed 's/.1.3.6.1.2.1.4.20.1.2.//' | grep "= $ax" | awk '{print \$1}'`;
		
		if($ip_address eq '') {
			$ip_address = 'N/A';
		}
		else {
			chomp($ip_address);
			$ip_address =~ s/\n/,/g;
		}
		
		# Remove forbidden caracters
		$interface =~ s/\"|\n|\<|\>|\&|\[|\]//g;
		 
		process_module_snmp ($dbh, $target_community, $addr, ".1.3.6.1.2.1.2.2.1.8.$ax", "interface", "$interface Status", "remote_snmp_proc", "Operative status for $interface at position $ax. IP Address: $ip_address", $conf);
			
		process_module_snmp ($dbh, $target_community, $addr, ".1.3.6.1.2.1.2.2.1.10.$ax", "", "$interface Inbound bps", "remote_snmp_inc", "Incoming traffic for $interface", $conf);
		
		process_module_snmp ($dbh, $target_community, $addr, ".1.3.6.1.2.1.2.2.1.16.$ax", "", "$interface Outbound bps", "remote_snmp_inc", "Outgoing traffic for $interface", $conf);
						
		# Do a grace sleep to avoid destination server ban me
		sleep 1;
		
	}
	
}	
	
# Mark recon task as done
update_recon_task ($dbh, $task_id, -1);

# End of code
