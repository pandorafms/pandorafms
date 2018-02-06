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

sub getParam($) {
	my $param = shift;

	$param = "--".$param;

	my $value_aux = undef;
	my $i;
	for($i=0; $i<$#ARGV; $i++) {
		if ($param eq $ARGV[$i]) {
			$value_aux = $ARGV[$i+1];
		}
	}
	
	return $value_aux;
}

##########################################################################
# Global variables so set behaviour here:

my $target_timeout = 5; # Fixed to 5 secs by default

##########################################################################
# Code begins here, do not touch
##########################################################################
my $pandora_conf = "/etc/pandora/pandora_server.conf";
my $task_id = $ARGV[0]; # Passed automatically by the server
my $target_group = $ARGV[1]; # Defined by user
my $create_incident = $ARGV[2]; # Defined by user

# Used Custom Fields in this script
my $target_network = getParam("net"); # Filed1 defined by user
my $username = getParam("ipmi_user"); # Field2 defined by user
my $password = getParam("ipmi_pass"); # Field3 defined by user
my $dcm_server = getParam("dcm_server"); 
my $dcm_port = getParam("dcm_port");
my $derated_power = getParam("derated_power");

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
	print "   $0 <task_id> <group_id> <create_incident_flag> more params, see doc\n\n";
	exit;
}

sub ipmi_ping ($$$$) {
	my ($conf, $addr, $user, $pass) = @_;
	
	my $cmd = "ipmiping $addr -c 3";
	
	my $res = `$cmd`;	

	if ($res =~ /100\.0% packet loss/) {

		return 0;
	}
		
	#If we have credentials we must check it
	if (defined($user) && defined($pass) && $user && $pass) {
		
		# Check if the credentials are valid		
		$cmd = "bmc-info -h $addr -u $user -p $pass 2>&1";

		$res = `$cmd`;	

		if ($? != 0) {

			logger ($conf, "[Intel DCM Discovery] Host $addr reports a BMC error", 1);
			return 0;
		}
	}

	return 1;
}

sub create_custom_fields ($) {
	my $dbh = shift;
	
	my $id_entity = get_db_value($dbh, 'SELECT id_field FROM tagent_custom_fields WHERE name = "DCM_Entity_Id"');
	
	if (!$id_entity) {
		db_insert ($dbh, 'id_field', 'INSERT INTO tagent_custom_fields (name, display_on_front) VALUES ("DCM_Entity_Id", 0)');
		
	}
	
	my $id_derated = get_db_value($dbh, 'SELECT id_field FROM tagent_custom_fields WHERE name = "DCM_Entity_Derated_Power"');
	
	if (!$id_derated) {
		db_insert ($dbh, 'id_field', 'INSERT INTO tagent_custom_fields (name, display_on_front) VALUES ("DCM_Entity_Derated_Power", 0)');	
		
	}
	
	($id_derated, $id_entity)
}

sub set_dcm_derated_power ($$$$) {
	my ($dbh, $id_agent, $id_field, $derated_power) = @_;
	
	db_insert ($dbh, 'id_field', 'INSERT INTO tagent_custom_data (id_field, id_agent, description) VALUES (?, ? ,?)', $id_field, $id_agent, $derated_power);	
}

sub set_dcm_id ($$$$) {
	my ($dbh, $id_agent, $id_field, $dcm_id) = @_;

	db_insert ($dbh, 'id_field', 'INSERT INTO tagent_custom_data (id_field, id_agent, description) VALUES (?, ? ,?)', $id_field, $id_agent, $dcm_id);		
}

sub create_dcm_entity ($$$$$$$$) {
	my ($dbh, $dcm_server, $dcm_port, $agent_name, $agent_address, $derated_power, $bmc_user, $bmc_pass)= @_;
	
	my $plugin_command = get_db_value($dbh, 'SELECT execute FROM tplugin WHERE name = "'.safe_input("Intel DCM Plugin").'"');	
	
	
	my $command = safe_output($plugin_command)." --server \"".$dcm_server."\" --port ".$dcm_port;
		
	$command .= " --action 'add_entity' --type 'NODE' --value '$agent_name'";
	
	$command .= " --address '$agent_address' --derated_power '$derated_power'";
	
	$command .= " --connector 'com.intel.dcm.plugin.Nm15Plugin'";

	$command .= " --bmc_user '$bmc_user' --bmc_pass '$bmc_pass'";
		
	my $res = `$command`;

	return $res;
}

sub create_metric_modules($$$$$$) {
	my ($conf, $dbh, $id_agent, $dcm_id, $dcm_server, $dcm_port) = @_;
	
	my @modules_array = (
		{"name" => "Managed Nodes Energy",
		"desc" => "The total energy consumed by all managed nodes in the specified entity, in Wh",
		"value" => "mnged_nodes_energy"},										
		{"name" => "Managed Nodes Energy Bill",
		"desc" => "The total power bill for all energy consumed by all managed nodes in the specified entity",
		"value" => "mnged_nodes_energy_bill"},			
		{"name" => "IT Equipment Energy",
		"desc" => "The total energy consumed by IT equipment, including managed nodes, unmanaged nodes and other IT equipment in the selected entity, in Wh",
		"value" => "it_eqpmnt_energy"},
		{"name" => "IT Equipment Energy Bill",
		"desc" => "The calculated power bill for IT equipment, including managed nodes, unmanaged nodes and other IT equipment in the selected entity",
		"value" => "it_eqpmnt_energy_bill"},					
		{"name" => "Calculated Cooling Energy",
		"desc" => "The energy needed to cool the selected entity, in Wh",
		"value" => "calc_cooling_energy"},			
		{"name" => "Calculated Cooling Energy Bill",
		"desc" => "The calculated power bill for the energy needed to cool the selected entity",
		"value" => "calc_cooling_energy_bill"},				
		{"name" => "Managed Nodes Power",
		"desc" => "The total average power consumption by the managed nodes in the selected entity, in watts",
		"value" => "mnged_nodes_pwr"},	
		{"name" => "IT Equipment Power",
		"desc" => "Provides the total average power consumption by IT equipment, including managed nodes, unmanaged nodes and other IT equipment in the selected entity in watts",
		"value" => "it_eqpmnt_pwr"},	
		{"name" => "Calculated Cooling Power",
		"desc" => "Provides the average cooling power based on the IT_EQPMNT_PWR multiplied by COOLING_MULT in watts",
		"value" => "calc_cooling_pwr"},	
		{"name" => "Avg. Power Per Dimension",
		"desc" => "The average power consumption per dimension",
		"value" => "avg_pwr_per_dimension"},	
		{"name" => "Derated power",
		"desc" => "Adds the de-rated values of all the nodes in the entity to the nameplate power value of all unmanaged nodes and equipment associated with the entity, as defined by NAMEPLATE_PWR_UNMNGD_EQPMNT",
		"value" => "derated_pwr"},		
		{"name" => "Inlet Temperature Span",
		"desc" => "The average inlet temperature differential between the highest and lowest node temperature in a group (degC/degF)",
		"value" => "inlet_temperature_span"});


	my $plugin_action = "--action \'metric_data\' --entity_id \'".$dcm_id."\'";
	
	my $id_plugin = get_db_value($dbh, 'SELECT id FROM tplugin WHERE name = "'.safe_input("Intel DCM Plugin").'"');	


	foreach my $mod (@modules_array) {

		my %aux_mod = %{$mod};

		my $aux_params = $plugin_action." --value \'".$aux_mod{'value'}."\'";
				
		my %parameters;

		$parameters{"nombre"} = safe_input($aux_mod{'name'});
		$parameters{"id_tipo_modulo"} = 1;		
		$parameters{"id_agente"} = $id_agent;
		$parameters{"id_plugin"} = $id_plugin;
		$parameters{"ip_target"} = $dcm_server;
		$parameters{"tcp_port"} = $dcm_port;
		$parameters{"id_modulo"} = 4;
		$parameters{"max_timeout"} = 300;
		$parameters{"descripcion"} = $aux_mod{'desc'};
		$parameters{"plugin_parameter"} = $aux_params;

		pandora_create_module_from_hash ($conf, \%parameters, $dbh);				

	}				
}

sub create_query_modules($$$$$$) {
	my ($conf, $dbh, $id_agent, $dcm_id, $dcm_server, $dcm_port) = @_;
	
	my @modules_array = (
		{"name" => "Max. Power",
		"desc" => "The maximum power consumed by any single node/enclosure",
		"value" => "max_pwr"},
		{"name" => "Avg. Power",
		"desc" => "The average power consumption across all nodes/enclosures",
		"value" => "avg_pwr"},
		{"name" => "Min. Power",
		"desc" => "The minimum power consumed by any single node/enclosure",
		"value" => "min_pwr"},												
		{"name" => "Max. Avg. Power",
		"desc" => "The maximum of group sampling (in a monitoring cycle) power in specified aggregation period for the sum of average power measurement in a group of nodes/enclosures within the specified entity",
		"value" => "max_avg_pwr"},
		{"name" => "Total Max. Power",
		"desc" => "The maximum of group sampling (in a monitoring cycle) power in specified aggregation period for sum of maximum power measurement in a group of nodes/enclosures within the specified entity",
		"value" => "total_max_pwr"},
		{"name" => "Total Avg. Power",
		"desc" => "The average (in specified aggregation period) of group power for sum of average power measurement in a group of nodes/enclosures within the specified entity",
		"value" => "total_avg_pwr"},													
		{"name" => "Max. Avg. Power Capping",
		"desc" => "The maximum of group sampling (in a monitoring cycle) power in specified aggregation period for the sum of average power measurement in a group of nodes/enclosures with power capping capability",
		"value" => "max_avg_pwr_cap"},
		{"name" => "Total Max. Power Capping",
		"desc" => "The maximum group sampling (in a monitoring cycle) power in specified aggregation period for sum of maximum power measurement in a group of nodes/enclosures with power capping capability",
		"value" => "total_max_pwr_cap"},																						
		{"name" => "Total Avg. Power Capping",
		"desc" => "The average (in specified aggregation period) of group power for sum of average power measurement in a group of nodes/enclosures with power capping capability",
		"value" => "total_avg_pwr_cap"},											
		{"name" => "Total Min. Power",
		"desc" => "The minimal group sampling (in a monitoring cycle) power in specified aggregation period for sum of minimum power measurement in a group of nodes/enclosures within the specified entity",
		"value" => "total_min_pwr"},											
		{"name" => "Min. Avg. Power",
		"desc" => "The minimal group sampling (in a monitoring cycle) power in specified aggregation period for sum of average power measurement in a group of nodes/enclosures within the specified entity",
		"value" => "min_avg_pwr"},											
		{"name" => "Max. Inlet Temperature",
		"desc" => "The maximum temperature for any single node within the specified entity",
		"value" => "max_inlet_temp"},											
		{"name" => "Avg. Inlet Temperature",
		"desc" => "The average temperature for any single node within the specified entity",
		"value" => "avg_inlet_temp"},											
		{"name" => "Min. Inlet Temperature",
		"desc" => "The minimum temperature for any single node within the specified entity",
		"value" => "min_inlet_temp"},											
		{"name" => "Instantaneous Power",
		"desc" => "The instantaneous power consumption of a specified node/enclosure or the sum of the instantaneous power of the nodes/enclosures within the specified entity",
		"value" => "ins_pwr"});	


	my $plugin_action = "--action \'query_data\' --entity_id \'".$dcm_id."\'";
	
	my $id_plugin = get_db_value($dbh, 'SELECT id FROM tplugin WHERE name = "'.safe_input("Intel DCM Plugin").'"');	


	foreach my $mod (@modules_array) {

		my %aux_mod = %{$mod};

		my $aux_params = $plugin_action." --value \'".$aux_mod{'value'}."\'";
				
		my %parameters;

		$parameters{"nombre"} = safe_input($aux_mod{'name'});
		$parameters{"id_tipo_modulo"} = 1;		
		$parameters{"id_agente"} = $id_agent;
		$parameters{"id_plugin"} = $id_plugin;
		$parameters{"ip_target"} = $dcm_server;
		$parameters{"tcp_port"} = $dcm_port;
		$parameters{"id_modulo"} = 4;
		$parameters{"max_timeout"} = 300;
		$parameters{"descripcion"} = $aux_mod{'desc'};
		$parameters{"plugin_parameter"} = $aux_params;

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

#Get id of custom fields
my ($id_derated_cf, $id_entity_cf) = create_custom_fields($dbh);

# Scan the network for hosts
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
	if (ipmi_ping (\%conf, $addr, $username, $password) == 1) {
		$alive = 1;
	}

	next unless ($alive > 0);

	# Resolve the address
	my $host_name = gethostbyaddr(inet_aton($addr), AF_INET);
	$host_name = $addr unless defined ($host_name);
	
	logger(\%conf, "Intel DCM Device found host $host_name.", 10);

	# Check if the agent exists
	my $agent_id = get_agent_id($dbh, $host_name);
	
	# If the agent exists go for the next
	if($agent_id != -1) {
		next;
	}
	
	# Create DCM Entity
	my $dcm_id = create_dcm_entity ($dbh, $dcm_server, $dcm_port, $host_name, $addr, $derated_power, $username, $password);
	
	# Create a new agent
	$agent_id = pandora_create_agent (\%conf, $conf{'servername'}, $host_name, $addr, $target_group, 0, 11, 'Created by Intel DCM Discovery', 300, $dbh);
		
	# Create modules
	create_query_modules(\%conf, $dbh, $agent_id, $dcm_id, $dcm_server, $dcm_port);
	create_metric_modules(\%conf, $dbh, $agent_id, $dcm_id, $dcm_server, $dcm_port);

	# Set custom fields
	set_dcm_derated_power ($dbh, $agent_id, $id_derated_cf, $derated_power);
	set_dcm_id ($dbh, $agent_id, $id_entity_cf, $dcm_id);

	# Generate an event
	pandora_event (\%conf, "[RECON] New Intel DCM host [$host_name] detected on network [" . $target_network . ']', $target_group, $agent_id, 2, 0, 0, 'recon_host_detected', 0, $dbh);
}	
	
# Mark recon task as done
update_recon_task ($dbh, $task_id, -1);

# End of code
