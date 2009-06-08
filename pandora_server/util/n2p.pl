#!/usr/bin/perl -s
################################################################################
#
# Copyright (c) 2007 Artica Soluciones Tecnologicas S.L.
#
# n2p.pl	Reads Nagios 2.x configuration files and replicates the setup
# 		using an installed Pandora FMS 1.3.
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; version 2 of the License.
# 
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.	
#
################################################################################

package n2p;

# All variables have to be declared
use strict 'vars';

use DBI;

# DB connection info
my $dbh;
my $db_name = "pandora";
my $db_host = "localhost";
my $db_port = "3306";
my $db_user = "pandora";
my $db_pass = "pandora";

# Program version
my $n2p_version = "0.1";
my $n2p_build = "071214";

# Pandora FMS version
my $pandora_version = "1.3";

# Report file
my $report_file = "n2p.html";

# Found hosts
my %hosts;

# Found host templates
my %host_templates;

# Found groups
my %groups;

# Found services
my %services;

# Found service templates
my %service_templates;

# Found commands
my %commands;

################################################################################
## SUB write_log
## Write log messages.
################################################################################
sub write_log {

	if (defined ($::v)) {
		print ("$_[0]\n");
	}
}

################################################################################
## SUB print_help
## Print help screen.
################################################################################
sub print_help {

	print ("Usage: $0 [options] <nagios_cfg_file> [nagios_cfg_file] ...\n\n");
	print ("Reads Nagios 2.x configuration files and replicates the setup using an installed Pandora FMS 1.3.\n\n");
	print ("Options:\n\t-a Enable alerts.\n");
	print ("\t-h Generate an HTML report.\n");
	print ("\t-s Simulate, do not change Pandora FMS's database.\n");
	print ("\t-u Undo any changes done to Pandora FMS's database.\n");
	print ("\t-v Be verbose.\n\n");
}

################################################################################
## SUB write_report
## Write a report containing all the information collected from Nagios
## configuration files.
## TODO: Write Pandora FMS agent and module information.
################################################################################
sub write_report {
	my $i;
	my $name;
	my $object;
	my $class = 1;

	open (FILE_HANDLER, ">$report_file") || die ("Error opening file $report_file for writing.");

	print FILE_HANDLER 
'<html>
	<style> 
		<!--
			* {font-family: verdana, sans-serif; font-size: 8pt;}
			body {text-align: left; background-color: #f9f9f9;}
			h1, h2 {font: bold 1em Arial, Sans-serif; text-transform: uppercase; color: #786; padding-bottom: 4px; padding-top: 7px;}
			h1 {font-size: 18px; text-align: center; font-weight: bold;}
			h2 {font-size: 15px;}
			a {color: #486787;text-decoration: none;}
			a:hover {color: #003a3a; text-decoration: underline;}
			th {color: #fff; background-color: #786;}
			td.data1 {background-color: #f9f9f9;}
			td.data2 {background-color: #efefef;}
			ul {list-style: none; line-height: 24px; padding: 0px 0px 0px 0px; margin: 0px 0px 0px 0px;}
			.bold {font-weight: bold;}
		-->
	</style>

	<head>
		<title>n2p Report</title>
	</head>
	
	<body>
		<h1>n2p Report</h1>
		<h2><a name="index"></a>Index</h2>
		<p>
			<ul class="bold">
				<li><a href="#host_templates">Host Templates</a></li>
				<li><a href="#hosts">Hosts</a></li>
				<li><a href="#groups">Groups</a></li>
				<li><a href="#service_templates">Service Templates</a></li>
				<li><a href="#services">Services</a></li>
				<li><a href="#commands">Commands</a></li>
			</ul>
		</p>
		<h2><a name="host_templates"></a>Host Templates</h2>
		<p>
			<table border="0">
				<tr>
					<th>Template Name</th>
					<th>Inherits from</th>
					<th>Check Command</th>
					<th>Check Interval (s)</th>
					<th>Notification</th>
					<th>Notification Interval (s)</th>
				</tr>';
	# Host templates
	while (($name, $object) = each (%host_templates)){
		$class = $class == 1 ? 2 : 1;
		print FILE_HANDLER "
				<tr>
					<td class=\"data$class\"><a name=\"host_template_$name\"></a>$name</td>
					<td class=\"data$class\">$object->{'use'}</td>
					<td class=\"data$class\">$object->{'check_command'}</td>
					<td class=\"data$class\">$object->{'check_interval'}</td>
					<td class=\"data$class\">$object->{'notification'}</td>
					<td class=\"data$class\">$object->{'notification_interval'}</td>
				<tr>";
	}

	print FILE_HANDLER
			'</table>
			<a href="#index" class="bold">Index</a></li>
		</p>
		<h2><a name="hosts"></a>Hosts</h2>
		<p>
			<table border="0">
				<tr>
					<th>Host Name</th>
					<th>Alias</th>
					<th>Address</th>
					<th>Inherits from</th>
					<th>Check Command</th>
					<th>Check Interval (s)</th>
					<th>Group</th>
					<th>Network Server</th>
					<th>Notification</th>
					<th>Notification Interval (s)</th>
				</tr>';
	# Hosts
	while (($name, $object) = each (%hosts)){
		$class = $class == 1 ? 2 : 1;
		print FILE_HANDLER "
				<tr>
					<td class=\"data$class\"><a name=\"host_$name\"></a>$name</td>
					<td class=\"data$class\">$object->{'alias'}</td>
					<td class=\"data$class\">$object->{'address'}</td>
					<td class=\"data$class\"><a href=\"#host_template_$object->{'use'}\">$object->{'use'}</a></td>
					<td class=\"data$class\"><a href=\"#command_$object->{'check_command'}\">$object->{'check_command'}</a></td>
					<td class=\"data$class\">$object->{'check_interval'}</td>
					<td class=\"data$class\"><a href=\"#group_$object->{'group_name'}\">$object->{'group_name'}</a></td>
					<td class=\"data$class\">$object->{'network_server_id'}</td>
					<td class=\"data$class\">$object->{'notification'}</td>
					<td class=\"data$class\">$object->{'notification_interval'}</td>
				<tr>";
	}
		
	print FILE_HANDLER
			'</table>
			<a href="#index" class="bold">Index</a></li>
		</p>
		<h2><a name="groups"></a>Groups</h2>
		<p>
			<table border="0">
				<tr>
					<th>Group Name</th>
					<th>Alias</th>
					<th>Members</th>
				</tr>';
	# Groups
	while (($name, $object) = each (%groups)){
		$class = $class == 1 ? 2 : 1;
		print FILE_HANDLER "
				<tr>
					<td class=\"data$class\"><a name=\"group_$name\"></a>$name</td>
					<td class=\"data$class\">$object->{'alias'}</td>
					<td class=\"data$class\">$object->{'members'}</td>
				<tr>";
	}

	print FILE_HANDLER
			'</table>
			<a href="#index" class="bold">Index</a></li>
		</p>
		<h2><a name="service_templates"></a>Service Templates</h2>
		<p>
			<table border="0">
				<tr>
					<th>Template Name</th>
					<th>Inherits from</th>
					<th>Notification</th>
					<th>Notification Interval (s)</th>
				</tr>';
	# Service templates
	while (($name, $object) = each (%service_templates)){
		$class = $class == 1 ? 2 : 1;
		print FILE_HANDLER "
				<tr>
					<td class=\"data$class\"><a name=\"service_template_$name\"></a>$name</td>
					<td class=\"data$class\">$object->{'use'}</td>
					<td class=\"data$class\">$object->{'notification'}</td>
					<td class=\"data$class\">$object->{'notification_interval'}</td>
				<tr>";
	}

	print FILE_HANDLER
			'</table>
			<a href="#index" class="bold">Index</a></li>
		</p>
		<h2><a name="services"></a>Services</h2>
		<p>
			<table border="0">
				<tr>
					<th>Command Name</th>
					<th>Inherits from</th>
					<th>Arguments</th>
					<th>Hosts</th>
					<th>Notification</th>
					<th>Notification Interval (s)</th>
				</tr>';
	# Services
	while (($name, $object) = each (%services)){
		$class = $class == 1 ? 2 : 1;
		print FILE_HANDLER "
				<tr>
					<td class=\"data$class\"><a href=\"#command_$name\">$name</a></td>
					<td class=\"data$class\"><a href=\"#service_template_$object->{'use'}\">$object->{'use'}</a></td>
					<td class=\"data$class\">$object->{'arguments'}</td>
					<td class=\"data$class\">$object->{'host_name'}</td>
					<td class=\"data$class\">$object->{'notification'}</td>
					<td class=\"data$class\">$object->{'notification_interval'}</td>
				<tr>";
	}

	print FILE_HANDLER
			'</table>
			<a href="#index" class="bold">Index</a></li>
		</p>
		<h2><a name="commands"></a>Commands</h2>
		<p>
			<table border="0">
				<tr>
					<th>Command Name</th>
					<th>Command Line</th>
				</tr>';
	# Commands
	while (($name, $object) = each (%commands)){
		$class = $class == 1 ? 2 : 1;
		print FILE_HANDLER "
				<tr>
					<td class=\"data$class\"><a name=\"command_$name\"></a>$name</td>
					<td class=\"data$class\">$object->{'command_line'}</td>
				<tr>";
	}

	print FILE_HANDLER
			'</table>
			<a href="#index" class="bold">Index</a></li>
		</p>
	</body>
</html>';

}

################################################################################
## SUB read_config
## Parse a Nagios configuration file.
################################################################################
sub read_config {
	my $line;
	my $var;

	open (FILE_HANDLER, $_[0]) || die ("Error opening file " . $_[0] . "for reading.");

	# Search for object definitions
	while ($line = <FILE_HANDLER>) {

		# Comment
		if ($line =~ /^[ \t]*#.*/) {
			next;
		}

		# Definition
		if ($line =~ /[ \t]*define[ \t]+(\w+)[ \t]*{.*/i) {

			$var = uc ($1);

			# Host definition
			if ($var eq 'HOST') {
				write_log ("\tReading host definition...");
				read_host ();
			}

			# Host group definition
			if ($var eq 'HOSTGROUP') {
				write_log ("\tReading group definition...");
				read_group ();
			}

			# Service definition
			if ($var eq 'SERVICE') {
				write_log ("\tReading service definition...");
				read_service ();
			}

			# Command definition
			if ($var eq 'COMMAND') {
				write_log ("\tReading command definition...");
				read_command ();
			}
		}
	}

	close (FILE_HANDLER);
}

################################################################################
## SUB read_host
## Read and store a host definition.
################################################################################
sub read_host {
	my $line;
	my $var;
	my $address = '';
	my $alias = '';
	my $check_command = '';
	# Default interval for Host Alive modules will be 5 minutes
	my $check_interval = 300;
	my $host_name = '';
	my $name = '';
	my $notification = '';
	# Default interval for alerts will be 5 minutes
	my $notification_interval = 300;
	my $use = '';

	while ($line = <FILE_HANDLER>) {

		# End of host definition
		if ($line =~ /[ \t]*}.*/) {

			# A host template
			if ($name ne '') {

				write_log ("\t\tFound host template $name" . ($use eq '' ? '' : " (inherits from $use)"));

				$host_templates{$name} = {
					"address" => $address,
					"alias" => $alias,
					"check_command" => $check_command,
					"check_interval" => $check_interval,
					"notification" => $notification,
					"notification_interval" => $notification_interval,
					"use" => $use,
				};
			}
			# A host
			else{
				write_log ("\t\tFound host $host_name" . ($use eq '' ? '' : " (inherits from $use)"));
			
				$hosts{$host_name} = {
					"address" => $address,
					"alias" => $alias,
					"check_command" => $check_command,
					"check_interval" => $check_interval,
					# Group All will be used by default
					"group_name" => 'All',
					"id" => -1,
					# Network servers will be assigned later
					"network_server_id" => -1,
					"notification" => $notification,
					"notification_interval" => $notification_interval,
					"use" => $use,
				};
			}

			return;
		} 
		
		# Host variables
		if ($line =~ /[ \t]*(\S+)[ \t]+(\S+).*/) {

			$var = uc ($1);

			if ($var eq "ADDRESS") {
				$address = $2;
			}
			elsif ($var eq "ALIAS") {
				$alias = $2;
			}
			elsif ($var eq "CHECK_COMMAND") {
				$check_command = $2;
			}
			elsif ($var eq "CHECK_INTERVAL") {
				# Pandora FMS needs seconds
				$check_interval = 60 * $2;
			}
			elsif ($var eq "HOST_NAME") {
				$host_name = $2;
			}
			elsif ($var eq "NAME") {
				$name = $2;
			}
			elsif ($var eq "NOTIFICATIONS_ENABLED") {
				$notification = $2;
			}
			elsif ($var eq "NOTIFICATION_INTERVAL") {
				# Pandora FMS needs seconds
				$notification_interval = 60 * $2;
			}
			# Inheritance has to be resolved now to allow
			# value overriding
			elsif ($var eq "USE") {
				$use = $2;

				$check_command = $host_templates{$use}->{'check_command'};
				$check_interval = $host_templates{$use}->{'check_interval'};
				$notification = $host_templates{$use}->{'notification'};
				$notification_interval = $host_templates{$use}->{'notification_interval'};
			}
		}
	}
}

################################################################################
## SUB read_group
## Read and store a group definition.
################################################################################
sub read_group {
	my $line;
	my $var;
	my $alias = '';
	my $hostgroup_name = '';
	my $members = '';

	while ($line = <FILE_HANDLER>) {

		# End of group definition
		if ($line =~ /[ \t]*}.*/) {

			write_log ("\t\tFound group $hostgroup_name");

			$groups{$hostgroup_name} = {
				"alias" => $alias,
				"id" => -1,
				"members" => $members,
			};

			return;
		} 
		
		# Host group variables
		if ($line =~ /[ \t]*(\S+)[ \t]+(\S+).*/) {

			$var = uc ($1);
			
			if ($var eq "ALIAS") {
				$alias = $2;
			}
			elsif ($var eq "HOSTGROUP_NAME") {
				$hostgroup_name = $2;
			}
			elsif ($var eq "MEMBERS") {
				$members = $2;
			}
		}
	}
}

################################################################################
## SUB read_service
## Read and store a service definition.
################################################################################
sub read_service {
	my $line;
	my $var;
	my $arguments = '';
	my @argument_array;
	my $check_command = '';
	my $description = '';
	my $host_name = '';
	my $name = '';
	my $notification = '';
	# Default interval for alerts will be 5 minutes
	my $notification_interval = 300;
	my $use = '';

	while ($line = <FILE_HANDLER>) {

		# End of service definition
		if ($line =~ /[ \t]*}.*/) {

			# A service template
			if ($name ne '') {

				write_log ("\t\tFound service template $name" . ($use eq '' ? '' : " (inherits from $use)"));

				$service_templates{$name} = {
					"notification" => $notification,
					"notification_interval" => $notification_interval,
					"use" => $use,
				};
			}
			# A service
			else{
				write_log ("\t\tFound service $check_command for hosts $host_name " . ($use eq '' ? '' : " (inherits from $use)"));
			
				$services{$check_command} = {
					"arguments" => $arguments,
					"description" => $description,
					"host_name" => $host_name,
					"notification" => $notification,
					"notification_interval" => $notification_interval,
					"use" => $use,
				};
			}

			return;
		} 
		
		# Service variables
		if ($line =~ /[ \t]*(\S+)[ \t]+(\S+).*/) {

			$var = uc ($1);

			if ($var eq "CHECK_COMMAND") {
				@argument_array = split (/!/, $2);
				$check_command = shift (@argument_array);
				$arguments = join (/!/, @argument_array);
			}
			elsif ($var eq "HOST_NAME") {
				$host_name = $2;
			}
			elsif ($var eq "NAME") {
				$name = $2;
			}
			elsif ($var eq "NOTIFICATIONS_ENABLED") {
				$notification = $2;
			}
			elsif ($var eq "NOTIFICATION_INTERVAL") {
				# Pandora FMS needs seconds
				$notification_interval = 60 * $2;
			}
			elsif ($var eq "SERVICE_DESCRIPTION") {
				$description = $2;
			}
			# Inheritance has to be resolved now to allow
			# value overriding
			elsif ($var eq "USE") {
				$use = $2;

				$notification = $service_templates{$use}->{'notification'};
				$notification_interval = $service_templates{$use}->{'notification_interval'};
			}
		}
	}
}

################################################################################
## SUB read_command
## Read and store a command definition.
################################################################################
sub read_command {
	my $line;
	my $var;
	my $command_line = '';
	my $command_name = '';

	while ($line = <FILE_HANDLER>) {

		# End of command definition
		if ($line =~ /[ \t]*}.*/) {

			write_log ("\t\tFound command $command_name");

			$commands{$command_name} = {
				"command_line" => $command_line,
			};

			return;
		} 
		
		# Command variables
		if ($line =~ /[ \t]*(\S+)[ \t]+(.*)/) {

			$var = uc ($1);
			
			if ($var eq "COMMAND_LINE") {
				$command_line = $2;
			}
			elsif ($var eq "COMMAND_NAME") {
				$command_name = $2;
			}
		}
	}
}

################################################################################
## SUB configure_db
## Configure Pandora FMS's database.
################################################################################
sub configure_db {

	# Configure groups first
	write_log ("Configuring groups...");
	configure_db_groups ();

	# Update host with the corresponding group information
	update_host_group_name ();

	# Update host information with a Pandora FMS network server
	write_log ("Assigning network servers...");
	update_host_network_server_id ();

	# Configure hosts
	write_log ("Configuring hosts...");
	configure_db_hosts ();

	# Configure modules and alerts
	write_log ("Configuring modules...");
	configure_db_modules ();
}

################################################################################
## SUB configure_db_hosts
## Configure Pandora FMS network agents.
################################################################################
sub configure_db_hosts {
	my $host;
	my $host_name;
	my $host_id;
	my $group_id;
	my $sth_insert;
	my @data;

	# SQL Insert agent
	$sth_insert = $dbh->prepare ("INSERT INTO tagente (nombre, direccion, comentarios, id_grupo, modo, intervalo, id_os, os_version, agent_version, disabled, agent_type, id_server) VALUES (?, ?, ?, ?, 0, ?, 11, 'Net', '$pandora_version', 0, 1, 2)") || die ("Error preparing statement: " . $sth_insert->errstr);

	# Parse hosts
	while (($host_name, $host) = each (%hosts)) {

		# Check that the host does not exist
		if (get_host_id ($host_name) != -1) {
			die ("Error: host $host_name already exists");
		}

		# Create the host
		write_log ("\tCreating host $host_name...");
	
		# Group does not exist, should not happen
		$group_id = get_group_id ($host->{'group_name'});
		if ($group_id == -1) {
			die ("Error: Group " . $host->{'group_name'} . " does not exists");
		}

		$sth_insert->execute ($host_name, $host->{'address'}, $host->{'alias'}, $group_id, $host->{'check_interval'}) || die ("Error executing statement: " . $sth_insert->errstr);

		# Read and store the host id for later use
		$host_id = get_host_id ($host_name);
		$hosts{$host_name}->{'id'} = $host_id;
	}
}

################################################################################
## SUB configure_db_groups
## Configure groups.
################################################################################
sub configure_db_groups {
	my $group;
	my $group_id;
	my $group_name;
	my $sth_insert;
	my $sth_select;
	my @data;

	# SQL Insert group
	$sth_insert = $dbh->prepare ("INSERT INTO tgrupo (nombre, icon, parent, disabled) VALUES (?, 'computer', '0', '0')") || die ("Error preparing statement: " . $sth_insert->errstr);

	# Parse groups
	while (($group_name, $group) = each (%groups)) {

		# Check that the group does not exist
		if (get_group_id ($group_name) != -1) {
			die ("\tError: group $group_name already exists");
		}

		# Create the group
		write_log ("\tCreating group $group_name...");
		$sth_insert->execute ($group_name) || die ("Error executing statement: " . $sth_insert->errstr);

		# Read and store the group id for later use
		$group_id = get_group_id ($group_name);
		$groups{$group_name}->{'id'} = $group_id;
	}
}

################################################################################
## SUB configure_db_modules
## Configure modules for each host.
################################################################################
sub configure_db_modules {
	my $host;
	my $host_name;
	my @host_array;
	my $service;
	my $service_name;

	# Configure check_command service module for each host
	while (($host_name, $host) = each (%hosts)) {
		
		configure_db_guess_module ($host->{'check_command'}, $host_name, $host->{'notification'}, $host->{'notification_interval'});
	}

	# Configure other services
	while (($service_name, $service) = each (%services)) {
		
		# Configure modules for all hosts defined in service
		@host_array = split (/,/, $service->{'host_name'});

		foreach $host_name (@host_array) {
			configure_db_guess_module ($service_name, $host_name, $service->{'notification'}, $service->{'notification_interval'});
		}
	}
}

################################################################################
## SUB configure_db_guess_module
## Guess and configure the appropiate Pandora FMS module given a Nagios service.
################################################################################
sub configure_db_guess_module {
	my $service = $_[0];
	my $agent_address;
	my $host_id;
	my $host_name = $_[1];
	my $notification = $_[2];
	my $alert_interval = $_[3];
	my $module_id;
	my $sth_mod;
	my $sth_state;

	# Host does not exist, should not happen
	if (! defined ($hosts{$host_name})) {
		die ("Agent $host_name does not exist");
	}

	# Command does not exist
	if (! defined ($commands{$service})) {
		write_log ("\tCommand $service does not exist, skipping...");
		return;
	}

	$agent_address = $hosts{$host_name}->{'address'};
	$host_id = $hosts{$host_name}->{'id'};
	
	# SQL Insert module
	$sth_mod = $dbh->prepare ("INSERT INTO tagente_modulo (id_agente, id_tipo_modulo, descripcion, nombre, max, min, module_interval, tcp_port, tcp_send, tcp_rcv, ip_target, id_module_group, flag, id_modulo) VALUES (?, ?, ?, ?, 0, 0, ?, ?, ?, ?, ?, ?, 0, 0)") || die ("Error preparing statement: " . $sth_mod->errstr);
	
	# SQL Without an entry in tagente_estado a module will not work
	$sth_state = $dbh->prepare ("INSERT INTO tagente_estado (id_agente_modulo) VALUES (?)") || die ("Error preparing statement: " . $sth_state->errstr);

	$service = uc ($service);
	if ($service eq "CHECK-HOST-ALIVE") {

		# Host Alive
		write_log ("\tAdding Host Alive module to host $host_name...");

		$sth_mod->execute ($host_id, 6, 'Check if host is alive using ICMP ping check.', 'Host Alive',  120, 0, '', '', $agent_address, 2) || die ("Error executing statement: " . $sth_mod->errstr);

		# Get the module id
		$module_id = get_module_id ('Host Alive', $host_id);

		# Module not found, should not happen
		if ($module_id == -1) {
			die ("Module Host Alive for agent $host_id not found");
		}

		# Create an entry for the module in tagente_estado
		$sth_state->execute ($module_id) || die ("Error executing statement: " . $sth_mod->errstr);

		# Create an alert if necessary
		if ($notification == 1 && defined ($::a)) {
			write_log ("\t\tAdding alert (type 3) to module...");
			configure_db_alert ($module_id, "Host seems to be down", "Host seems to be down", $alert_interval);
		}
	}
	elsif ($service eq "CHECK_HTTP") {

		# Check HTTP Server
		write_log ("\tAdding Check HTTP Server module to host $host_name...");

		$sth_mod->execute ($host_id, 9, 'Test APACHE2 HTTP service remotely (Protocol response, not only openport)', 'Check HTTP Server',  120, 80, 'GET / HTTP/1.0^M^M', 'HTTP/1.1 200 OK', $agent_address, 3) || die ("Error executing statement: " . $sth_mod->errstr);

		# Get the module id
		$module_id = get_module_id ('Check HTTP Server', $host_id);

		# Module not found, should not happen
		if ($module_id == -1) {
			die ("Module Check HTTP Server for agent $host_id not found");
		}

		# Create an entry for the module in tagente_estado
		$sth_state->execute ($module_id) || die ("Error executing statement: " . $sth_mod->errstr);

		# Create an alert if necessary
		if ($notification == 1 && defined ($::a)) {
			write_log ("\t\tAdding alert (type 3) to module...");
			configure_db_alert ($module_id, "HTTP Server seems to be down", "HTTP Server seems to be down", $alert_interval);
		}
	}
	elsif ($service eq "CHECK_FTP") {

		# Check FTP Server
		write_log ("\tAdding Check FTP Server module to host $host_name...");

		$sth_mod->execute ($host_id, 9, 'Check FTP protocol, not only check port.', 'Check FTP Server',  120, 21, 'QUIT', '221', $agent_address, 3) || die ("Error executing statement: " . $sth_mod->errstr);

		# Get the module id
		$module_id = get_module_id ('Check FTP Server', $host_id);

		# Module not found, should not happen
		if ($module_id == -1) {
			die ("Module Check FTP Server for agent $host_id not found");
		}

		# Create an entry for the module in tagente_estado
		$sth_state->execute ($module_id) || die ("Error executing statement: " . $sth_mod->errstr);

		# Create an alert if necessary
		if ($notification == 1 && defined ($::a)) {
			write_log ("\t\tAdding alert (type 3) to module...");
			configure_db_alert ($module_id, "FTP Server seems to be down", "FTP Server seems to be down", $alert_interval);
		}
	}
	else {
		write_log ("\tUnknown service $service for host $host_name...");

		# Do not generate an alert for an unknown service
		$notification = 0;
	}

}

################################################################################
## SUB configure_db_alert
## Configure an alert for a given module.
## TODO: Add more alert types.
################################################################################
sub configure_db_alert {
	my $module_id = $_[0];
	my $alert_desc = $_[1];
	my $alert_text = $_[2];
	my $alert_interval = $_[3];
	my $sth_alert;

	# SQL Insert alert
 	$sth_alert = $dbh->prepare ("INSERT INTO talerta_agente_modulo (id_agente_modulo, id_alerta, descripcion, dis_max, dis_min, time_threshold, max_alerts, alert_text) VALUES (?, ?, ?, ?, ?, ?, ?, ?)") || die ("Error preparing statement: " . $sth_alert->errstr);

	# Insert alert
	$sth_alert->execute ($module_id, 3, $alert_desc, 1, 1, $alert_interval, 1, $alert_text) || die ("Error executing statement: " . $sth_alert->errstr);
}

################################################################################
## SUB update_host_group_name
## Assigns to each host the corresponding group information.
################################################################################
sub update_host_group_name {
	my $group;
	my $group_name;
	my $member;
	my @members;

	while (($group_name, $group) = each (%groups)) {
		
		# Read members
		@members = split (/,/, $group->{'members'});

		# Assume we are parsing a valid Nagios config file,
		# no need to check whether a group member really exists or not 
		foreach $member (@members) {
			$hosts{$member}->{'group_name'} = $group_name;
		}
	}
}

################################################################################
## SUB update_host_network_server_id
## Assigns a Pandora FMS network server to each group.
################################################################################
sub update_host_network_server_id {
	my $i;
	my $host;
	my $host_name;
	my $group;
	my $group_name;
	my $member;
	my @members;
	my @servers;
	my @data;
	my $sth;
	
	# SQL Get a list of Pandora FMS network servers
	$sth = $dbh->prepare ("SELECT id_server FROM tserver WHERE network_server = 1") || die ("Error preparing statement: " . $sth->errstr);

	
	$sth->execute () || die ("Error executing statement: " . $sth->errstr);
	while (@data = $sth->fetchrow_array ()) {
		push (@servers, $data[0]);
	}
	
	# No network server found, this should not happen
	if ($#servers == -1) {
		die ("Error: no network servers found");
	}
	
	# Assign a network server to each group
	$i = 0;
	while (($group_name, $group) = each (%groups)) {
		
		# Read members
		@members = split (/,/, $group->{'members'});
		
		write_log ("\tGroup $group_name assigned to network server $servers[$i]");

		# Assume we are parsing a valid Nagios config file,
		# no need to check whether a group member really exists or not 
		foreach $member (@members) {
			$hosts{$member}->{'network_server_id'} = $servers[$i];
		}

		# Balance network server load
		$i++;
		if ($i > $#servers) {
			$i = $#servers > 0 ? $i % $#servers : 0;
		}
	}

	# Assign a network server to hosts with no assigned group
	while (($host_name, $host) =  each (%hosts)) {

		if ($host->{'network_server_id'} == -1) {
			write_log ("\tHost $host_name assigned to network server $servers[$i]");
			$host->{'network_server_id'} = $servers[$i];
		}
	}
}

################################################################################
## SUB roll_back
## Undo any changes done to the DB.
################################################################################
sub roll_back {
	my $host;
	my $host_id;
	my $host_name;
	my $group;
	my $group_name;
	my $sth_del_host;
	my $sth_del_group;
	my $sth_del_module;
	my $sth_del_state;
	my $sth_del_alert;
	my $sth_sel;
	my @data;

	write_log("Rolling back...");

	# SQL Delete hosts
	$sth_del_host = $dbh->prepare ("DELETE FROM tagente WHERE nombre = ?") || die ("Error preparing statement: " . $sth_del_host->errstr);

	# SQL Delete groups
	$sth_del_group = $dbh->prepare ("DELETE FROM tgrupo WHERE nombre = ?") || die ("Error preparing statement: " . $sth_del_group->errstr);

	# SQL Delete modules 
	$sth_del_module = $dbh->prepare ("DELETE FROM tagente_modulo WHERE id_agente_modulo = ?") || die ("Error preparing statement: " . $sth_del_module->errstr);
	$sth_del_state = $dbh->prepare ("DELETE FROM tagente_estado WHERE id_agente_modulo = ?") || die ("Error preparing statement: " . $sth_del_state->errstr);

	# SQL Delete alerts associated with a module
	$sth_del_alert = $dbh->prepare ("DELETE FROM talerta_agente_modulo WHERE id_agente_modulo = ?") || die ("Error preparing statement: " . $sth_del_alert->errstr);
	
	# SQL Get all modules associated with an agent
	$sth_sel = $dbh->prepare ("SELECT id_agente_modulo FROM tagente_modulo WHERE id_agente = ?") || die ("Error preparing statement: " . $sth_sel->errstr);

	# Delete hosts
	while (($host_name, $host) = each (%hosts)) {

		write_log("\tDeleting host $host_name...");

		# Get all modules associated with the host
		$host_id = get_host_id ($host_name);
		if ($host_id == -1) {
			next;
		}

		$sth_sel->execute ($host_id) || die ("Error executing statement: " . $sth_sel->errstr);

		# Delete alerts and modules
		write_log("\t\tDeleting alerts and modules associated with host...");

		while (@data = $sth_sel->fetchrow_array ()) {
			$sth_del_alert->execute ($data[0]) || die ("Error executing statement: " . $sth_del_alert->errstr);
			$sth_del_module->execute ($data[0]) || die ("Error executing statement: " . $sth_del_module->errstr);
			$sth_del_state->execute ($data[0]) || die ("Error executing statement: " . $sth_del_state->errstr);
		}
		
		# Delete host
		$sth_del_host->execute ($host_name) || die ("Error executing statement: " . $sth_del_host->errstr);
	}

	# Delete groups
	while (($group_name, $group) = each (%groups)) {

		write_log("\tDeleting group $group_name...");

		$sth_del_group->execute ($group_name) || die ("Error executing statement: " . $sth_del_group->errstr);

	}

}

################################################################################
## SUB get_host_id
## Get the id of a host given its name.
################################################################################
sub get_host_id {
	my $name = $_[0];
	my $sth;
	my @data;
	
	# SQL Get host id
	$sth = $dbh->prepare ("SELECT id_agente FROM tagente WHERE nombre = ?") || die ("Error preparing statement: " . $sth->errstr);
	
	$sth->execute ($name) || die ("Error executing statement: " . $sth->errstr);
	
	# Agent not found
	if ($sth->rows == 0) {
		return -1;
	}

	@data = $sth->fetchrow_array ();
	
	# Return id
	return $data[0];
}

################################################################################
## SUB get_group_id
## Get the id of a group given its name.
################################################################################
sub get_group_id {
	my $name = $_[0];
	my $sth;
	my @data;
	
	# SQL Get group id
	$sth = $dbh->prepare ("SELECT id_grupo FROM tgrupo WHERE nombre = ?") || die ("Error preparing statement: " . $sth->errstr);
	
	$sth->execute ($name) || die ("Error executing statement: " . $sth->errstr);
	
	# Group not found
	if ($sth->rows == 0) {
		return -1;
	}

	@data = $sth->fetchrow_array ();
	
	# Return id
	return $data[0];
}

################################################################################
## SUB get_module_id
## Get the id of a module given its name and the id of the host.
################################################################################
sub get_module_id {
	my $name = $_[0];
	my $host_id = $_[1];
	my $sth;
	my @data;
	
	# SQL Get module id
	$sth = $dbh->prepare ("SELECT id_agente_modulo FROM tagente_modulo WHERE id_agente = ? AND nombre = ?") || die ("Error preparing statement: " . $sth->errstr);
	
	$sth->execute ($host_id, $name) || die ("Error executing statement: " . $sth->errstr);
	
	# Module not found
	if ($sth->rows == 0) {
		return -1;
	}

	@data = $sth->fetchrow_array ();
	
	# Return id
	return $data[0];
}

################################################################################
# Main
################################################################################

my $file;

# Check command line arguments
if ($#ARGV < 0) {
	print_help ();
	exit;
}

# Check that all files exist
foreach $file (@ARGV) {
	if (! -f $file) {
		die "File \'$file\' does not exist.";
	}
}

# Check connection to the DB
$dbh = DBI->connect ("DBI:mysql:$db_name:$db_host:$db_port", $db_user, $db_pass) || die "Error connecting to the database.";

# Parse Nagios configuration files
foreach $file (@ARGV) {
	write_log ("Reading file $file...");
	read_config ($file);
}

# Simulation, do now write any changes to the DB
if (defined ($::s)) {
	$dbh->disconnect ();
	write_log ("Done.");
	exit;
}

# Roll back and undo any changes
if (defined ($::u)) {
	roll_back ();
}
# Configure Pandora's DB
else {
	configure_db ();
	if (defined ($::h)) {
		write_log ("Generating report...");
		write_report ();
	}
}

$dbh->disconnect ();
write_log ("Done.");
