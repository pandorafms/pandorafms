#!/usr/bin/perl

###############################################################################
# Pandora FMS Plugins migrate tool
###############################################################################
# Copyright (c) 2010 Artica Soluciones Tecnologicas S.L
#
# This program is Free Software, licensed under the terms of GPL License v2
###############################################################################

# Includes list
use strict;
use DBI;				# DB interface with MySQL
use POSIX qw(strftime);
use POSIX;
use HTML::Entities;		# Encode or decode strings with HTML entities
use Data::Dumper;
use JSON qw(encode_json);

# Default lib dir for RPM and DEB packages
use lib '/usr/lib/perl5';

use PandoraFMS::Tools;
use PandoraFMS::DB;
use PandoraFMS::Core;
use PandoraFMS::Config;

# migrate plugin tool version
my $version = "1.0";

# Names of the Description fields on the migrated macros
my $ip_desc = 'Target IP';
my $port_desc = 'Port';
my $user_desc = 'Username';
my $pass_desc = 'Password';
my $parameters_desc = 'Plug-in Parameters';

# Pandora server configuration
my %conf;

# Read databases credentials
pandora_load_credentials (\%conf);

# FLUSH in each IO
$| = 0;

# Connect to the DBs
my $dbh = db_connect ('mysql', $conf{'dbname'}, $conf{'dbhost'}, $conf{'dbport'}, $conf{'dbuser'}, $conf{'dbpass'});

# Main
pandora_migrate_plugins_main($dbh);

# Cleanup and exit
db_disconnect ($dbh);
exit;

###############################################################################
###############################################################################
# GENERAL FUNCTIONS
###############################################################################
###############################################################################

##############################################################################
# Get the values of the received macros from a module and return it in the macros format
##############################################################################
sub pandora_get_macros_values($$) {
	my ($module, $macros) = @_;
	
	foreach my $macro_key ( keys %{$macros} ) {
		if($macros->{$macro_key}{'desc'} eq $ip_desc) {
			$macros->{$macro_key}{'value'} = $module->{'ip_target'}
		}
		elsif($macros->{$macro_key}{'desc'} eq $port_desc) {
			$macros->{$macro_key}{'value'} = $module->{'tcp_port'}
		}
		elsif($macros->{$macro_key}{'desc'} eq $user_desc) {
			$macros->{$macro_key}{'value'} = $module->{'plugin_user'}
		}
		elsif($macros->{$macro_key}{'desc'} eq $pass_desc) {
			$macros->{$macro_key}{'value'} = $module->{'plugin_pass'}
		}
		elsif($macros->{$macro_key}{'desc'} eq $parameters_desc) {
			$macros->{$macro_key}{'value'} = $module->{'plugin_parameter'}
		}
	}
	
	return encode_json($macros);
}

##############################################################################
# Init screen
##############################################################################
sub pandora_load_credentials ($) {
    my $conf = shift; 
    
    $conf->{"verbosity"}=0;	# Verbose 1 by default
	$conf->{"daemon"}=0;	# Daemon 0 by default
	$conf->{'PID'}="";	# PID file not exist by default
	$conf->{"quiet"}=0;	# Daemon 0 by default


	print "\nPandora FMS Plugins migrate tool $version Copyright (c) 2010 Artica ST\n";
	print "This program is Free Software, licensed under the terms of GPL License v2\n";

	# Load config file from command line
	help_screen () if ($#ARGV < 3);
   
    $conf{'dbname'} = $ARGV[0];
    $conf{'dbhost'} = $ARGV[1];
    $conf{'dbuser'} = $ARGV[2];
    $conf{'dbpass'} = $ARGV[3];
    $conf{'dbport'} = 0;
    
}

##############################################################################
# Print a help screen and exit.
##############################################################################
sub help_screen{
	print "\n[ERROR] No valid arguments\n\n";

	print "Usage: \n\n$0 <dbname> <dbhost> <dbuser> <dbpass> \n\n";
		
	exit;
}

###############################################################################
###############################################################################
# MAIN
###############################################################################
###############################################################################

sub pandora_migrate_plugins_main ($) {

	my ($dbh) = @_;
	
	$|++;
	
	my $migrated_plugins = 0;
	my $migrated_modules = 0;
	my $migrated_components = 0;
	
	print "\n[*] Migrating plugins.\n\n";
	
	my @plugins = get_db_rows ($dbh, "SELECT * FROM tplugin WHERE macros = ''");

	$migrated_plugins = $#plugins + 1;
	
	foreach my $plugin (@plugins) {
		my $macros;

		my $macro_cont = 1;
		my $parameters = '';

		# Check the old static parameters to build the new parameters field with macros
		if($plugin->{'net_dst_opt'} ne '') {
			my $macro;
			
			$macros->{$macro_cont}{'macro'} = '_field'.$macro_cont.'_';
			$macros->{$macro_cont}{'desc'} = $ip_desc;
			$macros->{$macro_cont}{'help'} = '';
			$macros->{$macro_cont}{'value'} = '';
						
			$parameters .= $plugin->{'net_dst_opt'}.' _field'.$macro_cont.'_';
			$macro_cont ++;
		}
		
		if($plugin->{'net_port_opt'} ne '') {
			my $macro;
			
			$macros->{$macro_cont}{'macro'} = '_field'.$macro_cont.'_';
			$macros->{$macro_cont}{'desc'} = $port_desc;
			$macros->{$macro_cont}{'help'} = '';
			$macros->{$macro_cont}{'value'} = '';
						
			$parameters .= $plugin->{'net_port_opt'}.' _field'.$macro_cont.'_';
			$macro_cont ++;
		}
		
		if($plugin->{'user_opt'} ne '') {
			my $macro;
			
			$macros->{$macro_cont}{'macro'} = '_field'.$macro_cont.'_';
			$macros->{$macro_cont}{'desc'} = $user_desc;
			$macros->{$macro_cont}{'help'} = '';
			$macros->{$macro_cont}{'value'} = '';
						
			$parameters .= $plugin->{'user_opt'}.' _field'.$macro_cont.'_';
			$macro_cont ++;
		}
		
		if($plugin->{'pass_opt'} ne '') {
			my $macro;
			
			$macros->{$macro_cont}{'macro'} = '_field'.$macro_cont.'_';
			$macros->{$macro_cont}{'desc'} = $pass_desc;
			$macros->{$macro_cont}{'help'} = '';
			$macros->{$macro_cont}{'value'} = '';
			
			$parameters .= $plugin->{'pass_opt'}.' _field'.$macro_cont.'_';
			$macro_cont ++;
		}
		
		# A last parameter is defined always to add the old "Plug-in parameters" in the side of the module
		my $macro;
		
		$macros->{$macro_cont}{'macro'} = '_field'.$macro_cont.'_';
		$macros->{$macro_cont}{'desc'} = $parameters_desc;
		$macros->{$macro_cont}{'help'} = '';
		$macros->{$macro_cont}{'value'} = '';
					
		$parameters .= ' _field'.$macro_cont.'_';
				
		my $macros_json = encode_json($macros);

		# Insert macros and parameters in the plugin
		db_update ($dbh, "UPDATE tplugin SET `macros` = '$macros_json', `parameters` = '$parameters' WHERE `id` = '".$plugin->{'id'}."'");
		
		# Get the modules that use this plugin
		my @modules = get_db_rows ($dbh, "SELECT * FROM tagente_modulo WHERE delete_pending = 0 AND id_plugin = ".$plugin->{'id'});

		$migrated_modules = $#modules + 1;
		
		foreach my $module (@modules) {
			my $macros_json = pandora_get_macros_values($module, $macros);
			
			# Insert macros in the module
			db_update ($dbh, "UPDATE tagente_modulo SET `macros` = '$macros_json' WHERE `id_agente_modulo` = '".$module->{'id_agente_modulo'}."'");
		}

		# Get the network components that use this plugin
		my @network_components = get_db_rows ($dbh, "SELECT * FROM tnetwork_component WHERE id_plugin = ".$plugin->{'id'});

		$migrated_components = $#network_components + 1;

		foreach my $network_component (@network_components) {
			my $macros_json = pandora_get_macros_values($network_component, $macros);
			
			# Insert macros in the module
			db_update ($dbh, "UPDATE tnetwork_component SET `macros` = '$macros_json' WHERE `id_nc` = '".$network_component->{'id_nc'}."'");
		}
	}
	
	print "\n[*] $migrated_plugins plugins migrated.\n";
	print "\n[*] $migrated_modules modules migrated.\n";
	print "\n[*] $migrated_components components migrated.\n";
	
    exit;
}
