#!/usr/bin/perl
# (c) Dario Rodriguez 2011 <dario.rodriguez@artica.es>
# Intel DCM Discovery

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

my $pkg_count = 3; #Number of ping pkgs
my $pkg_timeout = 3; #Pkg ping timeout wait

##########################################################################
# Code begins here, do not touch
##########################################################################
my $pandora_conf = "/etc/pandora/pandora_server.conf";
my $task_id = $ARGV[0]; # Passed automatically by the server
my $target_group = $ARGV[1]; # Defined by user
my $create_incident = $ARGV[2]; # Defined by user

# Used Custom Fields in this script
my $target_network = $ARGV[3]; # Filed1 defined by user
my $username = $ARGV[4]; # Field2 defined by user
my $password = $ARGV[5]; # Field3 defined by user


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
	print "\nSpecific Pandora FMS Intel DCM Discovery\n";
	print "(c) Artica ST 2011 <info\@artica.es>\n\n";
	print "Usage:\n\n";
	print "   $0 <task_id> <group_id> <create_incident_flag> <custom_field1> <custom_field2> <custom_field3>\n\n";
	print " * custom_field1 = network. i.e.: 192.168.100.0/24\n";
	print " * custom_field2 = username \n";
	print " * custom_fiedl3 = password \n";
	exit;
}

##########################################################################
# Get SNMP response.
##########################################################################
sub ipmi_ping ($$$) {
	my $addr = shift;
	my $pkg_count = shift;
	my $pkg_timeout = shift;
	
	my $cmd = "ipmiping $addr -c $pkg_count -t $pkg_timeout";
	
	my $res = `$cmd`;	

	if ($res =~ /100\.0% packet loss/) {
		return 0;
	}	
	
	return 1;
}

sub create_ipmi_modules($$$$$$) {
	my ($conf, $dbh, $addr, $user, $pass, $id_agent) = @_;

        my $cmd = "ipmi-sensors -h $addr -u $user -p $pass";

        my $res = `$cmd`;

	my @lines = split(/\n/, $res);
	
	my $ipmi_plugin_id = get_db_value($dbh, "SELECT id FROM tplugin WHERE name = '".safe_input("IPMI Plugin")."'");

	
	for(my $i=1; $i < $#lines; $i++) {
		
		my $line = $lines[$i];
		
		my @aux = split(/\|/, $line);
		
		my $name = $aux[1];

		#Trim name
		$name =~ s/^\s+//;
		$name =~ s/\s+$//;
		
		my $module_type = "generic_data_string";
		
		my $value_read = $aux[3];
		
		#Trim name
		$value_read =~ s/^\s+//;
		$value_read =~ s/\s+$//;
		
		#Check if value read is integer or boolean
		if ($value_read =~ m/^\d+.\d+$/ || $value_read =~ m/^\d+$/) {
			$module_type = "generic_data";	
		} 
		
		my $id_module_type = get_module_id($dbh, $module_type);

		my $params = "-s $aux[0]";
			
		my %parameters;

		$parameters{"nombre"} = safe_input($name);
		$parameters{"id_tipo_modulo"} = $id_module_type;		
		$parameters{"id_agente"} = $id_agent;
		$parameters{"id_plugin"} = $ipmi_plugin_id;
		$parameters{"ip_target"} = $addr;
		$parameters{"plugin_user"} = $user;
		$parameters{"plugin_pass"} = $pass;
		$parameters{"id_modulo"} = 4;
		$parameters{"plugin_parameter"} = $params;

		pandora_create_module_from_hash ($conf, \%parameters, $dbh);	
		
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
my $dbh = db_connect ('mysql', $conf{'dbname'}, $conf{'dbhost'}, '3306', $conf{'dbuser'}, $conf{'dbpass'});


# Start the network sweep
# Get a NetAddr::IP object for the target network
my $net_addr = new NetAddr::IP ($target_network);
if (! defined ($net_addr)) {
	logger (\%conf, "Invalid network " . $target_network . " for Intel DCM Discovery task", 1);
	update_recon_task ($dbh, $task_id, -1);
	return -1;
}

# Scan the network for host
my ($total_hosts, $hosts_found, $addr_found) = ($net_addr->num, 0, '');

my $last = 0;
for (my $i = 1; $net_addr <= $net_addr->broadcast; $i++, $net_addr++) {
	if($last == 1) {
		last;
	}
	
	my $net_addr_temp = $net_addr + 1;
	if($net_addr eq $net_addr_temp) {
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
	if (ipmi_ping ($addr, $pkg_count, $pkg_timeout) == 1) {
		$alive = 1;
	}
	
	next unless ($alive > 0);

	# Resolve the address
	my $host_name = gethostbyaddr(inet_aton($addr), AF_INET);
	$host_name = $addr unless defined ($host_name);
	
	logger(\%conf, "Intel DCM Device found host $host_name.", 10);

	# Check if the agent exists
	my $agent_id = get_agent_id($dbh, $host_name);
	
	# If the agent doesnt exist we create it
	if($agent_id == -1) {
		# Create a new agent
		$agent_id = pandora_create_agent (\%conf, $conf{'servername'}, $host_name, $addr, $target_group, 0, 11, '', 300, $dbh);

		create_ipmi_modules(\%conf, $dbh, $addr, $username, $password, $agent_id);
	}

	# Generate an event
	pandora_event (\%conf, "[RECON] New Intel DCM host [$host_name] detected on network [" . $target_network . ']', $target_group, $agent_id, 2, 0, 0, 'recon_host_detected', 0, $dbh);
}	
	
# Mark recon task as done
update_recon_task ($dbh, $task_id, -1);

# End of code
