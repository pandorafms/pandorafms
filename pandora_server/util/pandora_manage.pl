#!/usr/bin/perl

###############################################################################
# Pandora FMS General Management Tool
###############################################################################
# Copyright (c) 2010 Artica Soluciones Tecnologicas S.L
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License version 2
###############################################################################

# Includes list
use strict;
use Time::Local;		# DateTime basic manipulation
use DBI;				# DB interface with MySQL
use POSIX qw(strftime);
use POSIX;
use HTML::Entities;		# Encode or decode strings with HTML entities

# Default lib dir for RPM and DEB packages
use lib '/usr/lib/perl5';

use PandoraFMS::Tools;
use PandoraFMS::DB;
use PandoraFMS::Core;
use PandoraFMS::Config;

# version: define current version
my $version = "4.0.1 PS111213";

# Parameter
my $param;

# Initialize MD5 variables
md5_init ();

# Pandora server configuration
my %conf;

# FLUSH in each IO
$| = 0;

# Init
pandora_manage_init(\%conf);

# Read config file
pandora_load_config (\%conf);

# Load enterprise module
if (enterprise_load (\%conf) == 0) {
	print "[*] Pandora FMS Enterprise module not available.\n\n";
} else {
	print "[*] Pandora FMS Enterprise module loaded.\n\n";
}

# Connect to the DB
my $dbh = db_connect ('mysql', $conf{'dbname'}, $conf{'dbhost'}, '3306', $conf{'dbuser'}, $conf{'dbpass'});
my $history_dbh = ($conf{'_history_db_enabled'} eq '1') ? db_connect ('mysql', $conf{'_history_db_name'},
		$conf{'_history_db_host'}, '3306', $conf{'_history_db_user'}, $conf{'_history_db_pass'}) : undef;

my $conf = \%conf;

# Main
pandora_manage_main(\%conf, $dbh, $history_dbh);

# Cleanup and exit
db_disconnect ($history_dbh) if defined ($history_dbh);
db_disconnect ($dbh);
exit;

###############################################################################
###############################################################################
# GENERAL FUNCTIONS
###############################################################################
###############################################################################

##############################################################################
# Print a help screen and exit.
##############################################################################
sub help_screen{
	print "Usage: $0 <path to pandora_server.conf> [options] \n\n" unless $param ne '';
	print "Available options by category:\n\n" unless $param ne '';
	print "Available options for $param:\n\n" unless $param eq '';
	print "AGENTS:\n\n" unless $param ne '';
   	help_screen_line('--create_agent', '<agent_name> <operating_system> <group> <server_name> [<address> <description> <interval>]', 'Create agent');
    help_screen_line('--update_agent', '<agent_name> <field_to_change> <new_value>', 'Update an agent field. The fields can be the following: agent_name, address, description, group_name, interval, os_name, disabled (0-1), parent_name, cascade_protection (0-1), icon_path, update_gis_data (0-1), custom_id');
	help_screen_line('--delete_agent', '<agent_name>', 'Delete agent');
	help_screen_line('--disable_group', '<group_name>', 'Disable agents from an entire group');
   	help_screen_line('--enable_group', '<group_name>', 'Enable agents from an entire group');
    help_screen_line('--create_group', '<group_name> [<parent_group_name> <icon>]', 'Create an agent group');
	help_screen_line('--stop_downtime', '<downtime_name>', 'Stop a planned downtime');
	help_screen_line('--get_agent_group', '<agent_name>', 'Get the group name of an agent');
	help_screen_line('--get_agent_modules', '<agent_name>', 'Get the modules of an agent');
	help_screen_line('--get_agents', '[<group_name> <os_name> <status> <max_modules> <filter_substring> <policy_name>]', 'Get list of agents with optative filter parameters');
	help_screen_line('--delete_conf_file', '<agent_name>', 'Delete a local conf of a given agent');
	help_screen_line('--clean_conf_file', '<agent_name>', 'Clean a local conf of a given agent deleting all modules, policies, file collections and comments');
	help_screen_line('--get_bad_conf_files', '', 'Get the files bad configured (without essential tokens)');
	print "MODULES:\n\n" unless $param ne '';
	help_screen_line('--create_data_module', '<module_name> <module_type> <agent_name> [<description> <module_group> <min> <max> <post_process> <interval> <warning_min> <warning_max> <critical_min> <critical_max> <history_data> <definition_file> <warning_str> <critical_str>]', 'Add data server module to agent');
	help_screen_line('--create_network_module', '<module_name> <module_type> <agent_name> <module_address> [<module_port> <description> <module_group> <min> <max> <post_process> <interval> <warning_min> <warning_max> <critical_min> <critical_max> <history_data> <ff_threshold> <warning_str> <critical_str>]', 'Add not snmp network module to agent');
	help_screen_line('--create_snmp_module', '<module_name> <module_type> <agent_name> <module_address> <module_port> <version> [<community> <oid> <description> <module_group> <min> <max> <post_process> <interval> <warning_min> <warning_max> <critical_min> <critical_max> <history_data> <snmp3_priv_method> <snmp3_priv_pass> <snmp3_sec_level> <snmp3_auth_method> <snmp3_auth_user> <snmp3_priv_pass> <ff_threshold> <warning_str> <critical_str>]', 'Add snmp network module to agent');
	help_screen_line('--create_plugin_module', '<module_name> <module_type> <agent_name> <module_address> <module_port> <plugin_name> <user> <password> <parameters> [<description> <module_group> <min> <max> <post_process> <interval> <warning_min> <warning_max> <critical_min> <critical_max> <history_data> <ff_threshold> <warning_str> <critical_str>]', 'Add plug-in module to agent');
    help_screen_line('--delete_module', 'Delete module from agent', '<module_name> <agent_name>');
    help_screen_line('--data_module', '<server_name> <agent_name> <module_name> <module_type> [<datetime>]', 'Insert data to module');
    help_screen_line('--get_module_data', '<agent_name> <module_name> <interval> [<csv_separator>]', 'Show the data of a module in the last X seconds (interval) in CSV format');
    help_screen_line('--delete_data', '-m <module_name> <agent_name> | -a <agent_name> | -g <group_name>', 'Delete historic data of a module, the modules of an agent or the modules of the agents of a group');
	help_screen_line('--update_module', '<module_name> <agent_name> <field_to_change> <new_value>', 'Update a module field');
    help_screen_line('--get_agents_module_current_data', '<module_name>', 'Get the agent and current data of all the modules with a given name');
	print "ALERTS:\n\n" unless $param ne '';
    help_screen_line('--create_template_module', '<template_name> <module_name> <agent_name>', 'Add alert template to module');
    help_screen_line('--delete_template_module', '<template_name> <module_name> <agent_name>', 'Delete alert template from module');
    help_screen_line('--create_template_action', '<action_name> <template_name> <module_name> <agent_name> [<fires_min> <fires_max>]', 'Add alert action to module-template');
    help_screen_line('--delete_template_action', '<action_name> <template_name> <module_name> <agent_name>', 'Delete alert action from module-template');
	help_screen_line('--disable_alerts', '', 'Disable alerts in all groups (system wide)');
	help_screen_line('--enable_alerts', '', 'Enable alerts in all groups (system wide)');
	help_screen_line('--create_alert_template', '<template_name> <condition_type_serialized> <time_from> <time_to> [<description> <group_name> <field1> <field2> <field3> <priority> <default_action> <days> <time_threshold> <min_alerts> <max_alerts> <alert_recovery> <field2_recovery> <field3_recovery> <condition_type_separator>]', 'Create alert template');
	help_screen_line('--delete_alert_template', '<template_name>', 'Delete alert template');
	help_screen_line('--update_alert_template', '<template_name> <field_to_change> <new_value>', 'Update a field of an alert template');
	help_screen_line('--validate_all_alerts', '', 'Validate all the alerts');
	print "USERS:\n\n" unless $param ne '';
    help_screen_line('--create_user', '<user_name> <user_password> <is_admin> [<comments>]', 'Create user');
    help_screen_line('--delete_user', '<user_name>', 'Delete user');
    help_screen_line('--update_user', '<user_id> <field_to_change> <new_value>', 'Update a user field. The fields can be the following: email, phone, is_admin (0-1), language, id_skin, flash_chart (0-1), comments, fullname, password');
    help_screen_line('--enable_user', '<user_id>', 'Enable a given user');
    help_screen_line('--disable_user', '<user_id>', 'Disable a given user');
    help_screen_line('--create_profile', '<user_name> <profile_name> <group_name>', 'Add perfil to user');
    help_screen_line('--delete_profile', '<user_name> <profile_name> <group_name>', 'Delete perfil from user');
    help_screen_line('--add_profile_to_user', '<user_id> <profile_name> [<group_name>]', 'Add a profile in group to a user');
	help_screen_line('--disable_eacl', '', 'Disable enterprise ACL system');
	help_screen_line('--enable_eacl', '', 'Enable enterprise ACL system');
	print "EVENTS:\n\n" unless $param ne '';
	help_screen_line('--create_event', '<event> <event_type> <group_name> [<agent_name> <module_name> <event_status> <severity> <template_name> <user_name> <comment> <source> <id_extra> <tags>]', 'Add event');
    help_screen_line('--validate_event', '<agent_name> <module_name> <datetime_min> <datetime_max> <user_name> <criticity> <template_name>', 'Validate events'); 
    help_screen_line('--validate_event_id', '<event_id>', 'Validate event given a event id'); 
    help_screen_line('--get_event_info', '<event_id>[<csv_separator>]', 'Show info about a event given a event id'); 
	print "INCIDENTS:\n\n" unless $param ne '';
    help_screen_line('--create_incident', '<title> <description> <origin> <status> <priority 0 for Informative, 1 for Low, 2 for Medium, 3 for Serious, 4 for Very serious or 5 for Maintenance> <group> [<owner>]', 'Create incidents');
	print "POLICIES:\n\n" unless $param ne '';
    help_screen_line('--apply_policy', '<policy_name>', 'Force apply a policy');
    help_screen_line('--apply_all_policies', '', 'Force apply to all the policies');
    help_screen_line('--add_agent_to_policy', '<agent_name> <policy_name>', 'Add an agent to a policy');
    help_screen_line('--delete_not_policy_modules', '', 'Delete all modules without policy from configuration file');
    help_screen_line('--disable_policy_alerts', '<policy_name>', 'Disable all the alerts of a policy');
	help_screen_line('--create_policy_data_module', '<policy_name> <module_name> <module_type> [<description> <module_group> <min> <max> <post_process> <interval> <warning_min> <warning_max> <critical_min> <critical_max> <history_data> <data_configuration> <warning_str> <critical_str>]', 'Add data server module to policy');
	help_screen_line('--create_policy_network_module', '<policy_name> <module_name> <module_type> [<module_port> <description> <module_group> <min> <max> <post_process> <interval> <warning_min> <warning_max> <critical_min> <critical_max> <history_data> <ff_threshold> <warning_str> <critical_str>]', 'Add not snmp network module to policy');
	help_screen_line('--create_policy_snmp_module', '<policy_name> <module_name> <module_type> <module_port> <version> [<community> <oid> <description> <module_group> <min> <max> <post_process> <interval> <warning_min> <warning_max> <critical_min> <critical_max> <history_data> <snmp3_priv_method> <snmp3_priv_pass> <snmp3_sec_level> <snmp3_auth_method> <snmp3_auth_user> <snmp3_priv_pass> <ff_threshold> <warning_str> <critical_str>]', 'Add snmp network module to policy');
	help_screen_line('--create_policy_plugin_module', '<policy_name> <module_name> <module_type> <module_port> <plugin_name> <user> <password> <parameters> [<description> <module_group> <min> <max> <post_process> <interval> <warning_min> <warning_max> <critical_min> <critical_max> <history_data> <ff_threshold> <warning_str> <critical_str>]', 'Add plug-in module to policy');
	help_screen_line('--validate_policy_alerts', '<policy_name>', 'Validate the alerts of a given policy');
	help_screen_line('--get_policy_modules', '<policy_name>', 'Get the modules of a policy');
	help_screen_line('--get_policies', '[<agent_name>]', 'Get all the policies (without parameters) or the policies of a given agent (agent name as parameter)');
	print "TOOLS:\n\n" unless $param ne '';
	help_screen_line('--exec_from_file', '<file_path> <option_to_execute> <option_params>', 'Execute any CLI option with macros from CSV file');
	
    print "\n";
	exit;
}

###############################################################################
# Disable a entire group
###############################################################################
sub pandora_disable_group ($$$) {
    my ($conf, $dbh, $group) = @_;

	if ($group == 0){
		db_do ($dbh, "UPDATE tagente SET disabled = 1");
	}
	else {
		db_do ($dbh, "UPDATE tagente SET disabled = 1 WHERE id_grupo = $group");
	}
    exit;
}

###############################################################################
# Enable a entire group
###############################################################################
sub pandora_enable_group ($$$) {
    my ($conf, $dbh, $group) = @_;

	if ($group == 0){
			db_do ($dbh, "UPDATE tagente SET disabled = 0");
	}
	else {  
			db_do ($dbh, "UPDATE tagente SET disabled = 0 WHERE id_grupo = $group");
	}
    exit;
}

##############################################################################
# Init screen
##############################################################################
sub pandora_manage_init ($) {
    my $conf = shift; 
    
    $conf->{"verbosity"}=0;	# Verbose 1 by default
	$conf->{"daemon"}=0;	# Daemon 0 by default
	$conf->{'PID'}="";	# PID file not exist by default
	$conf->{"quiet"}=0;	# Daemon 0 by default
   

	print "\nPandora FMS Manage tool $version Copyright (c) 2010 Artica ST\n";
	print "This program is Free Software, licensed under the terms of GPL License v2\n";
	print "You can download latest versions and documentation at http://www.pandorafms.org\n\n";

	# Load config file from command line
	help_screen () if ($#ARGV < 0);
   
        $conf->{'pandora_path'} = $ARGV[0];

	help_screen () if ($conf->{'pandora_path'} =~ m/--*h\w*\z/i );
}

##########################################################################
## Delete a template module.
##########################################################################
sub pandora_delete_template_module ($$) {
	my ($dbh, $template_module_id) = @_;

	# Delete the template module
	db_do ($dbh, 'DELETE FROM talert_template_modules WHERE id = ?', $template_module_id);
	
	# 
	db_do ($dbh, 'DELETE FROM talert_template_module_actions WHERE id_alert_template_module = ?', $template_module_id);
}

##########################################################################
## Delete a policy template module action.
##########################################################################
sub pandora_delete_template_module_action ($$$) {
        my ($dbh, $template_module_id, $action_id) = @_;

        return db_do ($dbh, 'DELETE FROM talert_template_module_actions WHERE id_alert_template_module = ? AND id_alert_action = ?', $template_module_id, $action_id);
}

##########################################################################
## Create an alert template from hash
##########################################################################
sub pandora_create_alert_template_from_hash ($$$) {
	my ($pa_config, $parameters, $dbh) = @_;

 	logger($pa_config, "Creating alert_template '$parameters->{'name'}'", 10);

	my $template_id = db_process_insert($dbh, 'id', 'talert_templates', $parameters);

	return $template_id;
}

##########################################################################
# Assign a profile in a group to user 
##########################################################################
sub pandora_add_profile_to_user ($$$;$) {
	my ($dbh, $user_id, $profile_id, $group_id) = @_;
	
	$group_id = 0 unless defined($group_id);
	
	db_do ($dbh, 'INSERT INTO tusuario_perfil (`id_usuario`, `id_perfil`, `id_grupo`)
				  VALUES (?, ?, ?)', safe_input($user_id), $profile_id, $group_id);
}

##########################################################################
## Create a user.
##########################################################################
sub pandora_create_user ($$$$$) {
my ($dbh, $name, $password, $is_admin, $comments) = @_;


return db_insert ($dbh, 'id_user', 'INSERT INTO tusuario (id_user, fullname, password, comments, is_admin)
                         VALUES (?, ?, ?, ?, ?)', safe_input($name), safe_input($name), $password, safe_input($comments), $is_admin);
}

##########################################################################
## Delete a user.
##########################################################################
sub pandora_delete_user ($$) {
my ($dbh, $name) = @_;

# Delete user profiles
db_do ($dbh, 'DELETE FROM tusuario_perfil WHERE id_usuario = ?', $name);

# Delete the user
my $return = db_do ($dbh, 'DELETE FROM tusuario WHERE id_user = ?', $name);

if($return eq '0E0') {
	return -1;
}
else {
	return 0;
}
}

##########################################################################
## Delete an alert template.
##########################################################################
sub pandora_delete_alert_template ($$) {
my ($dbh, $template_name) = @_;

# Delete the alert_template
my $return = db_do ($dbh, 'DELETE FROM talert_templates WHERE name = ?', safe_input($template_name));

if($return eq '0E0') {
	return -1;
}
else {
	return 0;
}
}

##########################################################################
## Assign a profile to the given user/group.
##########################################################################
sub pandora_create_user_profile ($$$$) {
        my ($dbh, $user_id, $profile_id, $group_id) = @_;
        
        return db_insert ($dbh, 'id_up', 'INSERT INTO tusuario_perfil (id_usuario, id_perfil, id_grupo) VALUES (?, ?, ?)', $user_id, $profile_id, $group_id);
}

##########################################################################
## Delete a profile from the given user/group.
##########################################################################
sub pandora_delete_user_profile ($$$$) {
        my ($dbh, $user_id, $profile_id, $group_id) = @_;
        
        return db_do ($dbh, 'DELETE FROM tusuario_perfil WHERE id_usuario=? AND id_perfil=? AND id_grupo=?', $user_id, $profile_id, $group_id);
}

##########################################################################
## Delete all the data of module, agent's modules or group's agent's modules
##########################################################################
sub pandora_delete_data ($$$) {
        my ($dbh, $type, $id) = @_;
        
        if($type eq 'group') {
			my @delete_agents = get_db_rows ($dbh, 'SELECT id_agente FROM tagente WHERE id_grupo = ?', $id);
			foreach my $agent (@delete_agents) {
				my @delete_modules = get_db_rows ($dbh, 'SELECT id_agente_modulo FROM tagente_modulo WHERE id_agente = ?', $agent->{'id_agente'});
				foreach my $module (@delete_modules) {
					pandora_delete_module_data($dbh, $module->{'id_agente_modulo'});
				}
			}
		}
        elsif ($type eq 'agent') {
			my @delete_modules = get_db_rows ($dbh, 'SELECT id_agente_modulo FROM tagente_modulo WHERE id_agente = ?', $id);
			foreach my $module (@delete_modules) {
				pandora_delete_module_data($dbh, $module->{'id_agente_modulo'});
			}
		}
		elsif ($type eq 'module'){
			pandora_delete_module_data($dbh, $id);
		}
		else {
			return 0;
		}
}

##########################################################################
## Delete all the data of module
##########################################################################
sub pandora_delete_module_data ($$) {
        my ($dbh, $id_module) = @_;
        my $buffer = 1000;
        
		while(1) {
			my $nd = get_db_value ($dbh, 'SELECT count(id_agente_modulo) FROM tagente_datos_string WHERE id_agente_modulo=?', $id_module);
			my $ndinc = get_db_value ($dbh, 'SELECT count(id_agente_modulo) FROM tagente_datos_string WHERE id_agente_modulo=?', $id_module);
			my $ndlog4x = get_db_value ($dbh, 'SELECT count(id_agente_modulo) FROM tagente_datos_string WHERE id_agente_modulo=?', $id_module);
			my $ndstring = get_db_value ($dbh, 'SELECT count(id_agente_modulo) FROM tagente_datos_string WHERE id_agente_modulo=?', $id_module);
			
			my $ntot = $nd + $ndinc + $ndlog4x + $ndstring;

			if($ntot == 0) {
				last;
			}
			
			if($nd > 0) {
				db_do ($dbh, 'DELETE FROM tagente_datos WHERE id_agente_modulo=? LIMIT ?', $id_module, $buffer);
			}
			
			if($ndinc > 0) {
				db_do ($dbh, 'DELETE FROM tagente_datos_inc WHERE id_agente_modulo=? LIMIT ?', $id_module, $buffer);
			}
		
			if($ndlog4x > 0) {
				db_do ($dbh, 'DELETE FROM tagente_datos_log4x WHERE id_agente_modulo=? LIMIT ?', $id_module, $buffer);
			}
			
			if($ndstring > 0) {
				db_do ($dbh, 'DELETE FROM tagente_datos_string WHERE id_agente_modulo=? LIMIT ?', $id_module, $buffer);
			}
		}
			
		return 1;
}

##########################################################################
# Validate event.
# This validates all events pending to ACK for the same id_agent_module
##########################################################################
sub pandora_validate_event_filter ($$$$$$$$$) {
	my ($pa_config, $id_agentmodule, $id_agent, $timestamp_min, $timestamp_max, $id_user, $id_alert_agent_module, $criticity, $dbh) = @_;
	my $filter = '';
		
	if ($id_agentmodule ne ''){
		$filter .= " AND id_agentmodule = $id_agentmodule";
	}
	if ($id_agent ne ''){
		$filter .= " AND id_agente = $id_agent";
	}
	if ($timestamp_min ne ''){
		$filter .= " AND timestamp >= '$timestamp_min'";
	}
	if ($timestamp_max ne ''){
		$filter .= " AND timestamp <= '$timestamp_max'";
	}
	if ($id_user ne ''){
		$filter .= " AND id_usuario = '$id_user'";
	}
	
	if ($id_alert_agent_module ne ''){
		$filter .= " AND id_alert_am = $id_alert_agent_module";
	}	
	
	if ($criticity ne ''){
		$filter .= " AND criticity = $criticity";
	}

	logger($pa_config, "Validating events", 10);
	db_do ($dbh, "UPDATE tevento SET estado = 1 WHERE estado = 0".$filter);
}

##########################################################################
# Validate event given a event id
##########################################################################
sub pandora_validate_event_id ($$$) {
	my ($pa_config, $id_event, $dbh) = @_;
	my $filter = '';
	
	if ($id_event ne ''){
		$filter .= " AND id_evento = $id_event";
	}	

	logger($pa_config, "Validating events", 10);
	db_do ($dbh, "UPDATE tevento SET estado = 1 WHERE estado = 0".$filter);
}

##########################################################################
## Update a user from hash
##########################################################################
sub pandora_update_user_from_hash ($$$$) {
	my ($parameters, $where_column, $where_value, $dbh) = @_;
	
	my $user_id = db_process_update($dbh, 'tusuario', $parameters, $where_column, $where_value);
	return $user_id;
}

##########################################################################
## Update an alert template from hash
##########################################################################
sub pandora_update_alert_template_from_hash ($$$$) {
	my ($parameters, $where_column, $where_value, $dbh) = @_;
	
	my $template_id = db_process_update($dbh, 'talert_templates', $parameters, $where_column, $where_value);
	return $template_id;
}

###############################################################################
# Get list of all downed agents
###############################################################################
sub pandora_get_downed_agents () {    	
	my @downed_agents = get_db_rows ($dbh, "SELECT tagente.id_agente, tagente.nombre, truncate((NOW() - tagente.ultimo_contacto/60),0) as downed_time, tagente.server_name from tagente
where  UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(tagente.ultimo_contacto)>(tagente.intervalo*2)
OR tagente.ultimo_contacto=0");
	
	return \@downed_agents;
}

###############################################################################
# Get the agent (id of agent and module and agent name) list with a given module
###############################################################################
sub pandora_get_module_agents ($$) {
	my ($dbh,$module_name) = @_;
	    	
	my @agents = get_db_rows ($dbh, "SELECT tagente_modulo.id_agente_modulo, tagente.id_agente, tagente.nombre FROM tagente, tagente_modulo 
	WHERE tagente.id_agente = tagente_modulo.id_agente AND tagente_modulo.nombre = ?", safe_input($module_name));
	
	return \@agents;
}

###############################################################################
# Get agent status (critical, warning, unknown or normal)
###############################################################################
sub pandora_get_agent_status ($$) {
	my ($dbh,$agent_id) = @_;
	
	my $critical = get_db_value ($dbh, "SELECT COUNT(tagente_estado.id_agente_estado) FROM tagente_estado, tagente, tagente_modulo WHERE tagente.id_agente = $agent_id AND tagente.disabled = 0 AND tagente_estado.id_agente = tagente.id_agente AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo AND tagente_modulo.disabled = 0 AND estado = 1 AND (utimestamp >= ( UNIX_TIMESTAMP() - (current_interval * 2)) OR tagente_modulo.id_tipo_modulo IN (21,22,23,100))");
	return 'critical' unless $critical == 0;
	
	my $warning = get_db_value ($dbh, "SELECT COUNT(tagente_estado.id_agente_estado) FROM tagente_estado, tagente, tagente_modulo WHERE tagente.id_agente = $agent_id AND tagente.disabled = 0 AND tagente_estado.id_agente = tagente.id_agente AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo AND tagente_modulo.disabled = 0 AND estado = 2 AND (utimestamp >= ( UNIX_TIMESTAMP() - (current_interval * 2)) OR tagente_modulo.id_tipo_modulo IN (21,22,23,100))");
	return 'warning' unless $warning == 0;
	
	my $unknown = get_db_value ($dbh, "SELECT COUNT(tagente_estado.id_agente_estado) FROM tagente_estado, tagente, tagente_modulo WHERE tagente.id_agente = $agent_id AND tagente.disabled = 0 AND tagente.id_agente = tagente_estado.id_agente AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo AND tagente_modulo.disabled = 0 AND tagente_modulo.id_tipo_modulo NOT IN (21,22,23,100) AND utimestamp < ( UNIX_TIMESTAMP() - (current_interval * 2)) AND utimestamp != 0");
	return 'unknown' unless $unknown == 0;
	
	my $normal = get_db_value ($dbh, "SELECT COUNT(tagente_estado.id_agente_estado) FROM tagente_estado, tagente, tagente_modulo WHERE tagente.id_agente = $agent_id AND tagente.disabled = 0 AND tagente_estado.id_agente = tagente.id_agente AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo AND tagente_modulo.disabled = 0 AND estado = 0 AND (utimestamp != 0 OR tagente_modulo.id_tipo_modulo IN (21,22,23)) AND (utimestamp >= ( UNIX_TIMESTAMP() - (current_interval * 2)) OR tagente_modulo.id_tipo_modulo IN (21,22,23,100))");
	return 'normal' unless $normal == 0;
	return 'normal' unless $normal == 0;
		
	return '';
}

##########################################################################
## Return the modules of a given agent
##########################################################################
sub pandora_get_agent_modules ($$) {
	my ($dbh, $agent_id) = @_;
	
	my @modules = get_db_rows ($dbh, "SELECT id_agente_modulo, nombre FROM tagente_modulo WHERE delete_pending = 0 AND id_agente = ?", $agent_id);

	return \@modules;
}

###############################################################################
# Get module current data
###############################################################################
sub pandora_get_module_current_data ($$) {
	my ($dbh,$id_agent_module) = @_;
	    	
	my $current_data = get_db_value ($dbh, "SELECT datos FROM tagente_estado WHERE id_agente_modulo = ?", $id_agent_module);
	
	return $current_data;
}

##########################################################################
## SUB get_alert_template_id(id)
## Return the alert template id, given "template_name"
##########################################################################
sub pandora_get_alert_template_id ($$) {
	my ($dbh, $template_name) = @_;
	
	my $template_id = get_db_value ($dbh, "SELECT id FROM talert_templates WHERE name = ?", safe_input($template_name));

	return defined ($template_id) ? $template_id : -1;
}

##########################################################################
## SUB get_planned_downtime_id(id)
## Return the planned downtime id, given "downtime_name"
##########################################################################
sub pandora_get_planned_downtime_id ($$) {
	my ($dbh, $downtime_name) = @_;
	
	my $downtime_id = get_db_value ($dbh, "SELECT id FROM tplanned_downtime WHERE name = ?", safe_input($downtime_name));

	return defined ($downtime_id) ? $downtime_id : -1;
}

###############################################################################
###############################################################################
# PRINT HELP AND CHECK ERRORS FUNCTIONS
###############################################################################
###############################################################################

###############################################################################
# Print a parameter error and exit the program.
###############################################################################
sub param_error ($$) {
    print (STDERR "[ERROR] Parameters error: $_[1] received | $_[0] necessary.\n\n");
    
    help_screen ();
    exit 1;
}

###############################################################################
# Print a 'not exists' error and exit the program.
###############################################################################
sub notexists_error ($$) {
    print (STDERR "[ERROR] Error: The $_[0] '$_[1]' not exists.\n\n");
    exit 1;
}

###############################################################################
# Print a 'exists' error and exit the program.
###############################################################################
sub exists_error ($$) {
    print (STDERR "[ERROR] Error: The $_[0] '$_[1]' already exists.\n\n");
    exit 1;
}

###############################################################################
# Check the return of 'get id' and call the error if its equal to -1.
###############################################################################
sub exist_check ($$$) {
    if($_[0] == -1) {
		notexists_error($_[1],$_[2]);
	}
}

###############################################################################
# Check the return of 'get id' and call the error if its not equal to -1.
###############################################################################
sub non_exist_check ($$$) {
    if($_[0] != -1) {
		exists_error($_[1],$_[2]);
	}
}

###############################################################################
# Check the parameters.
# Param 0: # of received parameters
# Param 1: # of acceptable parameters
# Param 2: # of optional parameters
###############################################################################
sub param_check ($$;$) {
	my ($ltotal, $laccept, $lopt) = @_;
	$ltotal = $ltotal - 1;
	
	if(!defined($lopt)){
		$lopt = 0;
	}

	if( $ltotal < $laccept - $lopt || $ltotal > $laccept) {
		if( $lopt == 0 ) {
			param_error ($laccept, $ltotal);
		}
		else {
			param_error (($laccept-$lopt)."-".$laccept, $ltotal);
		}
	}
}

##############################################################################
# Print a help line.
##############################################################################
sub help_screen_line($$$){
	my ($option, $parameters, $help) = @_;
	print "\t$option $parameters\t$help.\n" unless ($param ne '' && $param ne $option);
}

###############################################################################
###############################################################################
# CLI FUNCTIONS
###############################################################################
###############################################################################

##############################################################################
# Disable group
# Related option: --disable_group
##############################################################################

sub cli_disable_group() {
	my $group_name = @ARGV[2];
	my $id_group;
	
	if($group_name eq "All") {
		print "[INFO] Disabling all groups\n\n";
		$id_group = 0;
	}
	else {
		$id_group = get_group_id($dbh, $group_name);
		exist_check($id_group,'group',$group_name);
		print "[INFO] Disabling group '$group_name'\n\n";
	}
	
	pandora_disable_group ($conf, $dbh, $id_group);
}

##############################################################################
# Enable group
# Related option: --enable_group
##############################################################################

sub cli_enable_group() {
	my $group_name = @ARGV[2];
	my $id_group;
	
	if($group_name eq "All") {
		$id_group = 0;
		print "[INFO] Enabling all groups\n\n";
	}
	else {
		$id_group = get_group_id($dbh, $group_name);
		exist_check($id_group,'group',$group_name);
		print "[INFO] Enabling group '$group_name'\n\n";
	}
	
	pandora_enable_group ($conf, $dbh, $id_group);
}

##############################################################################
# Create an agent.
# Related option: --created_agent
##############################################################################

sub cli_create_agent() {
	my ($agent_name,$os_name,$group_name,$server_name,$address,$description,$interval) = @ARGV[2..8];
	
	print "[INFO] Creating agent '$agent_name'\n\n";
	
	$address = '' unless defined ($address);
	$description = (defined ($description) ? safe_input($description)  : '' );	# safe_input() might be better at pandora_create_agent() (when passing 'description' to db_insert())
	$interval = 300 unless defined ($interval);
	
	my $id_group = get_group_id($dbh,$group_name);
	exist_check($id_group,'group',$group_name);
	my $os_id = get_os_id($dbh,$os_name);
	exist_check($id_group,'operating system',$group_name);
	my $agent_exists = get_agent_id($dbh,$agent_name);
	non_exist_check($agent_exists, 'agent name', $agent_name);
	pandora_create_agent ($conf, $server_name, $agent_name, $address, $id_group, 0, $os_id, $description, $interval, $dbh);
}

##############################################################################
# Delete an agent.
# Related option: --delete_agent
##############################################################################

sub cli_delete_agent() {
	my $agent_name = @ARGV[2];
	
	$agent_name = decode_entities($agent_name);
	print "[INFO] Deleting agent '$agent_name'\n\n";
	
	my $id_agent = get_agent_id($dbh,$agent_name);
	exist_check($id_agent,'agent',$agent_name);
	
	pandora_delete_agent($dbh,$id_agent,$conf);
}


##############################################################################
# Create alert template
# Related option: --create_alert_template
##############################################################################

sub cli_create_alert_template() {
	my ($template_name, $condition_type_serialized, $time_from, $time_to, 
		$description,$group_name,$field1, $field2, $field3, $priority, $default_action, $days, $time_threshold, 
		$min_alerts, $max_alerts, $alert_recovery, $field2_recovery, $field3_recovery, $condition_type_separator) = @ARGV[2..20];
	
	my $template_exists = pandora_get_alert_template_id ($dbh, $template_name);
	non_exist_check($template_exists,'alert template',$template_name);

	my $id_alert_action = 0;
	
	$id_alert_action = get_action_id ($dbh, safe_input($default_action)) unless $default_action eq '';

	my $group_id = 0;
	
	# If group name is not defined, we assign group All (0)
	if(defined($group_name)) {
		$group_id = get_group_id($dbh, $group_name);
		exist_check($group_id,'group',$group_name);
	}
	else {
		$group_name = 'All';
	}
	
	$condition_type_separator = ';' unless defined $condition_type_separator;
	
	my %parameters;
	
	my @condition_array = split($condition_type_separator, $condition_type_serialized);
	
	my $type = $condition_array[0];
	
	if($type eq 'regex') {
		$parameters{'matches_value'} = $condition_array[1];
		$parameters{'value'} = $condition_array[1];
	}
	elsif($type eq 'max_min') {
		$parameters{'matches_value'} = $condition_array[1];
		$parameters{'min_value'} = $condition_array[2];
		$parameters{'max_value'} = $condition_array[3];
	}
	elsif($type eq 'max') {
		$parameters{'max_value'} = $condition_array[1];
	}
	elsif($type eq 'min') {
		$parameters{'min_value'} = $condition_array[1];
	}
	elsif($type eq 'equal') {
		$parameters{'value'} = $condition_array[1];
	}
	elsif($type eq 'not_equal') {
		$parameters{'value'} = $condition_array[1];
	}
	elsif($type eq 'onchange') {
		$parameters{'matches_value'} = $condition_array[1];
	}
	elsif($type eq 'warning' || $type eq 'critical' || $type eq 'unknown' || $type eq 'always') {
		# Only type is stored
	}
	else {
		$type = 'always';
	}
	
	$parameters{'name'} = $template_name;
	$parameters{'type'} = $type;
	$parameters{'time_from'} = $time_from;
	$parameters{'time_to'} = $time_to;
	
	$parameters{'id_alert_action'} = $id_alert_action unless $id_alert_action <= 0;
	
	$parameters{'id_group'} = $group_id;
	$parameters{'field1'} = defined ($field1) ? safe_input($field1) : '';
	$parameters{'field2'} = defined ($field2) ? safe_input($field2) : '';
	$parameters{'field3'} = defined ($field3) ? safe_input($field3) : '';
	$parameters{'priority'} = defined ($priority) ? $priority : 1; # Informational by default
	$parameters{'description'} = defined ($description) ? safe_input($description) : '';
	$parameters{'time_threshold'} = defined ($time_threshold) ? $time_threshold : 86400;
	$parameters{'min_alerts'} = defined ($min_alerts) ? $min_alerts : 0;
	$parameters{'max_alerts'} = defined ($max_alerts) ? $max_alerts : 1;
	$parameters{'recovery_notify'} = defined ($alert_recovery) ? $alert_recovery : 0;
	$parameters{'field2_recovery'} = defined ($field2_recovery) ? safe_input($field2_recovery) : '';
	$parameters{'field3_recovery'} = defined ($field3_recovery) ? safe_input($field3_recovery) : '';
	
	$days = '1111111' unless defined($days); # Al days actived by default
	
	my @days_array = split('',$days);
	
	$parameters{'monday'} = $days_array[0];
	$parameters{'tuesday'} = $days_array[1];
	$parameters{'wednesday'} = $days_array[2];
	$parameters{'thursday'} = $days_array[3];
	$parameters{'friday'} = $days_array[4];
	$parameters{'saturday'} = $days_array[5];
	$parameters{'sunday'} = $days_array[6];

	pandora_create_alert_template_from_hash ($conf, \%parameters, $dbh);
}

##############################################################################
# Create data module.
# Related option: --create_data_module
##############################################################################

sub cli_create_data_module($) {
	my $in_policy = shift;
	my ($policy_name, $module_name, $module_type, $agent_name, $description, $module_group, 
		$min,$max,$post_process, $interval, $warning_min, $warning_max, $critical_min,
		$critical_max, $history_data, $definition_file, $configuration_data, $warning_str, $critical_str);
		
	if($in_policy == 0) {
		($module_name, $module_type, $agent_name, $description, $module_group, 
		$min,$max,$post_process, $interval, $warning_min, $warning_max, $critical_min,
		$critical_max, $history_data, $definition_file, $warning_str, $critical_str) = @ARGV[2..18];
	}
	else {
		($policy_name, $module_name, $module_type, $description, $module_group, 
		$min,$max,$post_process, $interval, $warning_min, $warning_max, $critical_min,
		$critical_max, $history_data, $configuration_data, $warning_str, $critical_str) = @ARGV[2..18];
	}
	
	my $module_name_def;
	my $module_type_def;
	
	my $agent_id;
	my $policy_id;
	
	if($in_policy == 0) {
		$agent_id = get_agent_id($dbh,$agent_name);
		exist_check($agent_id,'agent',$agent_name);
	
		my $module_exists = get_agent_module_id($dbh, $module_name, $agent_id);
		non_exist_check($module_exists, 'module name', $module_name);
		
		print "[INFO] Adding module '$module_name' to agent '$agent_name'\n\n";
	}
	else {
		$policy_id = enterprise_hook('get_policy_id',[$dbh, safe_input($policy_name)]);
		exist_check($policy_id,'policy',$policy_name);
	
		my $policy_module_exist = enterprise_hook('get_policy_module_id',[$dbh, $policy_id, $module_name]);
		non_exist_check($policy_module_exist,'policy module',$module_name);
		
		print "[INFO] Adding module '$module_name' to policy '$policy_name'\n\n";
	}

	# If the module is local and is not to policy, we add it to the conf file
	if(defined($definition_file) && (-e $definition_file) && (-e $conf->{incomingdir}.'/conf/'.md5($agent_name).'.conf')){
		open (FILE, $definition_file);
		my @file = <FILE>;
		my $definition = join("", @file);
		close (FILE);

		# If the parameter name or type and the definition file name or type 
		# dont match will be set the file definitions
		open (FILE, $definition_file);
		while (<FILE>) {
			chomp;
			my ($key, $val) = split / /;
			if ($key eq 'module_name') {
				$module_name_def =  $val;
			}
			if ($key eq 'module_type') {
				$module_type_def =  $val;
			}
		}
		close (FILE);
		
		open (FILE, $conf->{incomingdir}.'/conf/'.md5($agent_name).'.conf');
		my @file = <FILE>;
		my $conf_file = join("", @file);
		close(FILE);
		
		open FILE, "> ".$conf->{incomingdir}.'/conf/'.md5($agent_name).'.conf';
		print FILE "$conf_file\n$definition";
		close(FILE);
		
		enterprise_hook('pandora_update_md5_file', [$conf, $agent_name]);
	}

	if(defined($definition_file) && $module_type ne $module_type_def) {
		$module_type = $module_type_def;
		print "[INFO] The module type has been forced to '$module_type' by the definition file\n\n";
	}
	
	if(defined($definition_file) && $module_name ne $module_name_def) {
		$module_name = $module_name_def;
		print "[INFO] The module name has been forced to '$module_name' by the definition file\n\n";
	}
	
	# The get_module_id has wrong name. Change in future
	my $module_type_id = get_module_id($dbh,$module_type);
	exist_check($module_type_id,'module type',$module_type);

	if ($module_type !~ m/.?generic.?/ && $module_type !~ m/.?async.?/ && $module_type ne 'log4x' && $module_type ne 'keep_alive') {
			print "[ERROR] '$module_type' is not valid type for data modules. Try with generic, asyncronous, keep alive or log4x types\n\n";
			exit;
	}
		
	my $module_group_id = 0;
	
	if(defined($module_group)) {
		$module_group_id = get_module_group_id($dbh,$module_group);
		exist_check($module_group_id,'module group',$module_group);
	}
	
	my %parameters;
	
	$parameters{'id_tipo_modulo'} = $module_type_id;
	
	if($in_policy == 0) {
		$parameters{'nombre'} = safe_input($module_name);
		$parameters{'id_agente'} = $agent_id;
	}
	else {
		$parameters{'name'} = safe_input($module_name);
		$parameters{'id_policy'} = $policy_id;
	}

	# Optional parameters
	$parameters{'id_module_group'} = $module_group_id unless !defined ($module_group);
	$parameters{'min_warning'} = $warning_min unless !defined ($warning_min);
	$parameters{'max_warning'} = $warning_max unless !defined ($warning_max);
	$parameters{'min_critical'} = $critical_min unless !defined ($critical_min);
	$parameters{'max_critical'} = $critical_max unless !defined ($critical_max);
	$parameters{'history_data'} = $history_data unless !defined ($history_data);
	if($in_policy == 0) {
		$parameters{'descripcion'} = safe_input($description) unless !defined ($description);
		$parameters{'id_modulo'} = 1;	
	}
	else {
		$parameters{'description'} = safe_input($description) unless !defined ($description);
		$parameters{'id_module'} = 1;
		$configuration_data !~ s/\\n/\n/g;
		$parameters{'configuration_data'} = safe_input($configuration_data);	
	}
	$parameters{'min'} = $min unless !defined ($min);
	$parameters{'max'} = $max unless !defined ($max);
	$parameters{'post_process'} = $post_process unless !defined ($post_process);
	$parameters{'module_interval'} = $interval unless !defined ($interval);
	$parameters{'str_warning'}  = safe_input($warning_str)  unless !defined ($warning_str);
	$parameters{'str_critical'} = safe_input($critical_str) unless !defined ($critical_str);
	
	if($in_policy == 0) {
		pandora_create_module_from_hash ($conf, \%parameters, $dbh);
	}
	else {
		enterprise_hook('pandora_create_policy_module_from_hash', [$conf, \%parameters, $dbh]);
	}
}

##############################################################################
# Create network module.
# Related option: --create_network_module
##############################################################################

sub cli_create_network_module($) {
	my $in_policy = shift;
	my ($policy_name, $module_name, $module_type, $agent_name, $module_address, $module_port, $description, 
	$module_group, $min, $max, $post_process, $interval, $warning_min, $warning_max, $critical_min,
	$critical_max, $history_data, $ff_threshold, $warning_str, $critical_str);
		
	if($in_policy == 0) {
		($module_name, $module_type, $agent_name, $module_address, $module_port, $description, 
		$module_group, $min, $max, $post_process, $interval, $warning_min, $warning_max, $critical_min,
		$critical_max, $history_data, $ff_threshold, $warning_str, $critical_str) = @ARGV[2..20];
	}
	else {
		($policy_name, $module_name, $module_type, $module_port, $description, 
		$module_group, $min, $max, $post_process, $interval, $warning_min, $warning_max, $critical_min,
		$critical_max, $history_data, $ff_threshold, $warning_str, $critical_str) = @ARGV[2..19];
	}
	
	my $module_name_def;
	my $module_type_def;
	my $agent_id;
	my $policy_id;
	
	if($in_policy == 0) {
		my $agent_id = get_agent_id($dbh,$agent_name);
		exist_check($agent_id,'agent',$agent_name);
		
		my $module_exists = get_agent_module_id($dbh, $module_name, $agent_id);
		non_exist_check($module_exists, 'module name', $module_name);
		
		print "[INFO] Adding module '$module_name' to agent '$agent_name'\n\n";
	}
	else {
		$policy_id = enterprise_hook('get_policy_id',[$dbh, safe_input($policy_name)]);
		exist_check($policy_id,'policy',$policy_name);
	
		my $policy_module_exist = enterprise_hook('get_policy_module_id',[$dbh, $policy_id, $module_name]);
		non_exist_check($policy_module_exist,'policy module',$module_name);
		
		print "[INFO] Adding module '$module_name' to policy '$policy_name'\n\n";
	}

	if ($module_type =~ m/.?snmp.?/) {
		print "[ERROR] '$module_type' is not a valid type. For snmp modules use --create_snmp_module parameter\n\n";
		$param = '--create_snmp_module';
		help_screen ();
		exit 1;
	}
	if ($module_type !~ m/.?icmp.?/ && $module_type !~ m/.?tcp.?/) {
			print "[ERROR] '$module_type' is not valid type for (not snmp) network modules. Try with icmp or tcp types\n\n";
			exit;
	}
	
	# The get_module_id has wrong name. Change in future
	my $module_type_id = get_module_id($dbh,$module_type);
	exist_check($module_type_id,'module type',$module_type);
		
	my $module_group_id = 0;
	
	if(defined($module_group)) {
		$module_group_id = get_module_group_id($dbh,$module_group);
		exist_check($module_group_id,'module group',$module_group);
	}
	
	if ($module_type !~ m/.?icmp.?/) {
		if (not defined($module_port)) {
			print "[ERROR] Port error. Agents of type distinct of icmp need port\n\n";
			exit;
		}
		if ($module_port > 65535 || $module_port < 1) {
			print "[ERROR] Port error. Port must into [1-65535]\n\n";
			exit;
		}
	}
	my %parameters;
	
	$parameters{'id_tipo_modulo'} = $module_type_id;

	if($in_policy == 0) {
		$parameters{'nombre'} = safe_input($module_name);
		$parameters{'id_agente'} = $agent_id;
		$parameters{'ip_target'} = $module_address;
	}
	else {
		$parameters{'name'} = safe_input($module_name);
		$parameters{'id_policy'} = $policy_id;
	}

	# Optional parameters
	$parameters{'id_module_group'} = $module_group_id unless !defined ($module_group);
	$parameters{'min_warning'} = $warning_min unless !defined ($warning_min);
	$parameters{'max_warning'} = $warning_max unless !defined ($warning_max);
	$parameters{'min_critical'} = $critical_min unless !defined ($critical_min);
	$parameters{'max_critical'} = $critical_max unless !defined ($critical_max);
	$parameters{'history_data'} = $history_data unless !defined ($history_data);
	$parameters{'tcp_port'} = $module_port unless !defined ($module_port);
	if($in_policy == 0) {
		$parameters{'descripcion'} = safe_input($description) unless !defined ($description);
		$parameters{'id_modulo'} = 2;	
	}
	else {
		$parameters{'description'} = safe_input($description) unless !defined ($description);
		$parameters{'id_module'} = 2;
	}
	$parameters{'min'} = $min unless !defined ($min);
	$parameters{'max'} = $max unless !defined ($max);
	$parameters{'post_process'} = $post_process unless !defined ($post_process);
	$parameters{'module_interval'} = $interval unless !defined ($interval);	
	$parameters{'min_ff_event'} = $ff_threshold unless !defined ($ff_threshold);	
	$parameters{'str_warning'}  = safe_input($warning_str)  unless !defined ($warning_str);
	$parameters{'str_critical'} = safe_input($critical_str) unless !defined ($critical_str);
		
	if($in_policy == 0) {
		pandora_create_module_from_hash ($conf, \%parameters, $dbh);
	}
	else {
		enterprise_hook('pandora_create_policy_module_from_hash', [$conf, \%parameters, $dbh]);
	}
}

##############################################################################
# Create snmp module.
# Related option: --create_snmp_module
##############################################################################

sub cli_create_snmp_module($) {
	my $in_policy = shift;
	my ($policy_name, $module_name, $module_type, $agent_name, $module_address, $module_port, $version, $community, 
		$oid, $description, $module_group, $min, $max, $post_process, $interval, $warning_min, 
		$warning_max, $critical_min, $critical_max, $history_data, $snmp3_priv_method, $snmp3_priv_pass,
		$snmp3_sec_level, $snmp3_auth_method, $snmp3_auth_user, $snmp3_auth_pass, $ff_threshold, $warning_str, $critical_str);
		
	if($in_policy == 0) {
		($module_name, $module_type, $agent_name, $module_address, $module_port, $version, $community, 
		$oid, $description, $module_group, $min, $max, $post_process, $interval, $warning_min, 
		$warning_max, $critical_min, $critical_max, $history_data, $snmp3_priv_method, $snmp3_priv_pass,
		$snmp3_sec_level, $snmp3_auth_method, $snmp3_auth_user, $snmp3_auth_pass, $ff_threshold, $warning_str, $critical_str) = @ARGV[2..29];
	}
	else {
		($policy_name, $module_name, $module_type, $module_port, $version, $community, 
		$oid, $description, $module_group, $min, $max, $post_process, $interval, $warning_min, 
		$warning_max, $critical_min, $critical_max, $history_data, $snmp3_priv_method, $snmp3_priv_pass,
		$snmp3_sec_level, $snmp3_auth_method, $snmp3_auth_user, $snmp3_auth_pass, $ff_threshold, $warning_str, $critical_str) = @ARGV[2..28];
	}

	my $module_name_def;
	my $module_type_def;
	my $agent_id;
	my $policy_id;
	
	if($in_policy == 0) {
		my $agent_id = get_agent_id($dbh,$agent_name);
		exist_check($agent_id,'agent',$agent_name);
		
		my $module_exists = get_agent_module_id($dbh, $module_name, $agent_id);
		non_exist_check($module_exists, 'module name', $module_name);
		
		print "[INFO] Adding module '$module_name' to agent '$agent_name'\n\n";
	}
	else {
		$policy_id = enterprise_hook('get_policy_id',[$dbh, safe_input($policy_name)]);
		exist_check($policy_id,'policy',$policy_name);
	
		my $policy_module_exist = enterprise_hook('get_policy_module_id',[$dbh, $policy_id, $module_name]);
		non_exist_check($policy_module_exist,'policy module',$module_name);
		
		print "[INFO] Adding module '$module_name' to policy '$policy_name'\n\n";
	}	
	
	# The get_module_id has wrong name. Change in future
	my $module_type_id = get_module_id($dbh,$module_type);
	exist_check($module_type_id,'module type',$module_type);
	
	my $module_group_id = 0;

	if(defined($module_group)) {
		$module_group_id = get_module_group_id($dbh,$module_group);
		exist_check($module_group_id,'module group',$module_group);
	}
	
	if ($module_type !~ m/.?snmp.?/) {
		print "[ERROR] '$module_type' is not a valid snmp type\n\n";
		exit;
	}
	
	if ($module_port > 65535 || $module_port < 1) {
		print "[ERROR] Port error. Port must into [1-65535]\n\n";
		exit;
	}
	
	my %parameters;
	
	$parameters{'id_tipo_modulo'} = $module_type_id;
	
	if($in_policy == 0) {
		$parameters{'nombre'} = safe_input($module_name);
		$parameters{'id_agente'} = $agent_id;
		$parameters{'ip_target'} = $module_address;
	}
	else {
		$parameters{'name'} = safe_input($module_name);
		$parameters{'id_policy'} = $policy_id;
	}

	$parameters{'tcp_port'} = $module_port;
	$parameters{'tcp_send'} = $version;

	# Optional parameters
	$parameters{'id_module_group'} = $module_group_id unless !defined ($module_group);
	$parameters{'min_warning'} = $warning_min unless !defined ($warning_min);
	$parameters{'max_warning'} = $warning_max unless !defined ($warning_max);
	$parameters{'min_critical'} = $critical_min unless !defined ($critical_min);
	$parameters{'max_critical'} = $critical_max unless !defined ($critical_max);
	$parameters{'history_data'} = $history_data unless !defined ($history_data);
	if($in_policy == 0) {
		$parameters{'descripcion'} = safe_input($description) unless !defined ($description);
		#2 for snmp modules
		$parameters{'id_modulo'} = 2;	
	}
	else {
		$parameters{'description'} = safe_input($description) unless !defined ($description);
		#2 for snmp modules
		$parameters{'id_module'} = 2;
	}
	$parameters{'min'} = $min unless !defined ($min);
	$parameters{'max'} = $max unless !defined ($max);
	$parameters{'post_process'} = $post_process unless !defined ($post_process);
	$parameters{'module_interval'} = $interval unless !defined ($interval);
	$parameters{'snmp_community'} = $community unless !defined ($community);
	$parameters{'snmp_oid'} = $oid unless !defined ($oid);
	$parameters{'min_ff_event'} = $ff_threshold unless !defined ($ff_threshold);	
	$parameters{'str_warning'}  = safe_input($warning_str)  unless !defined ($warning_str);
	$parameters{'str_critical'} = safe_input($critical_str) unless !defined ($critical_str);
	
	if($version == 3) {
		$parameters{'custom_string_1'} = $snmp3_priv_method;
		$parameters{'custom_string_2'} = $snmp3_priv_pass;
		$parameters{'custom_string_3'} = $snmp3_sec_level;
		$parameters{'plugin_parameter'} = $snmp3_auth_method;
		$parameters{'plugin_user'} = $snmp3_auth_user; 
		$parameters{'plugin_pass'} = $snmp3_auth_pass;
	}
	
	if($in_policy == 0) {
		pandora_create_module_from_hash ($conf, \%parameters, $dbh);
	}
	else {
		enterprise_hook('pandora_create_policy_module_from_hash', [$conf, \%parameters, $dbh]);
	}
}

##############################################################################
# Create plugin module.
# Related option: --create_plugin_module
##############################################################################

sub cli_create_plugin_module($) {
	my $in_policy = shift;
	my ($policy_name, $module_name, $module_type, $agent_name, $module_address, $module_port, $plugin_name,
	$user, $password, $params, $description, $module_group, $min, $max, $post_process, 
	$interval, $warning_min, $warning_max, $critical_min, $critical_max, $history_data, 
	$ff_threshold, $warning_str, $critical_str);
	
	if($in_policy == 0) {
		($module_name, $module_type, $agent_name, $module_address, $module_port, $plugin_name,
			$user, $password, $params, $description, $module_group, $min, $max, $post_process, 
			$interval, $warning_min, $warning_max, $critical_min, $critical_max, $history_data, 
			$ff_threshold, $warning_str, $critical_str) = @ARGV[2..24];
	}
	else {
		($policy_name, $module_name, $module_type, $module_port, $plugin_name,
			$user, $password, $params, $description, $module_group, $min, $max, $post_process, 
			$interval, $warning_min, $warning_max, $critical_min, $critical_max, $history_data, 
			$ff_threshold, $warning_str, $critical_str) = @ARGV[2..23];
	}
		
	my $module_name_def;
	my $module_type_def;
	my $agent_id;
	my $policy_id;

	if($in_policy == 0) {
		my $agent_id = get_agent_id($dbh,$agent_name);
		exist_check($agent_id,'agent',$agent_name);
		
		my $module_exists = get_agent_module_id($dbh, $module_name, $agent_id);
		non_exist_check($module_exists, 'module name', $module_name);
		
		print "[INFO] Adding module '$module_name' to agent '$agent_name'\n\n";
	}
	else {
		$policy_id = enterprise_hook('get_policy_id',[$dbh, safe_input($policy_name)]);
		exist_check($policy_id,'policy',$policy_name);
	
		my $policy_module_exist = enterprise_hook('get_policy_module_id',[$dbh, $policy_id, $module_name]);
		non_exist_check($policy_module_exist,'policy module',$module_name);
		
		print "[INFO] Adding module '$module_name' to policy '$policy_name'\n\n";
	}
	
	# The get_module_id has wrong name. Change in future
	my $module_type_id = get_module_id($dbh,$module_type);
	exist_check($module_type_id,'module type',$module_type);

	if ($module_type !~ m/.?generic.?/ && $module_type ne 'log4x') {
			print "[ERROR] '$module_type' is not valid type for plugin modules. Try with generic or log4x types\n\n";
			exit;
	}
		
	my $module_group_id = get_module_group_id($dbh,$module_group);
	exist_check($module_group_id,'module group',$module_group);
			
	my $plugin_id = get_plugin_id($dbh,$plugin_name);
	exist_check($plugin_id,'plugin',$plugin_name);
	
	if ($module_port > 65535 || $module_port < 1) {
		print "[ERROR] Port error. Port must into [1-65535]\n\n";
		exit;
	}

	my %parameters;
	
	$parameters{'id_tipo_modulo'} = $module_type_id;

	if($in_policy == 0) {
		$parameters{'nombre'} = safe_input($module_name);
		$parameters{'id_agente'} = $agent_id;
		$parameters{'ip_target'} = $module_address;
	}
	else {
		$parameters{'name'} = safe_input($module_name);
		$parameters{'id_policy'} = $policy_id;
	}
	
	$parameters{'tcp_port'} = $module_port;
	$parameters{'id_plugin'} = $plugin_id;
	$parameters{'plugin_user'} = $user;
	$parameters{'plugin_pass'} = $password;
	$parameters{'plugin_parameter'} = safe_input($params);

	# Optional parameters
	$parameters{'id_module_group'} = $module_group_id unless !defined ($module_group);
	$parameters{'min_warning'} = $warning_min unless !defined ($warning_min);
	$parameters{'max_warning'} = $warning_max unless !defined ($warning_max);
	$parameters{'min_critical'} = $critical_min unless !defined ($critical_min);
	$parameters{'max_critical'} = $critical_max unless !defined ($critical_max);
	$parameters{'history_data'} = $history_data unless !defined ($history_data);
	if($in_policy == 0) {
		$parameters{'descripcion'} = safe_input($description) unless !defined ($description);
		#4 for plugin modules
		$parameters{'id_modulo'} = 4;	
	}
	else {
		$parameters{'description'} = safe_input($description) unless !defined ($description);
		#4 for plugin modules
		$parameters{'id_module'} = 4;
	}
	$parameters{'min'} = $min unless !defined ($min);
	$parameters{'max'} = $max unless !defined ($max);
	$parameters{'post_process'} = $post_process unless !defined ($post_process);
	$parameters{'module_interval'} = $interval unless !defined ($interval);	
	$parameters{'min_ff_event'} = $ff_threshold unless !defined ($ff_threshold);	
	$parameters{'str_warning'}  = safe_input($warning_str)  unless !defined ($warning_str);
	$parameters{'str_critical'} = safe_input($critical_str) unless !defined ($critical_str);
		
	if($in_policy == 0) {
		pandora_create_module_from_hash ($conf, \%parameters, $dbh);
	}
	else {
		enterprise_hook('pandora_create_policy_module_from_hash', [$conf, \%parameters, $dbh]);
	}
}

##############################################################################
# Create delete module.
# Related option: --delete_module
##############################################################################

sub cli_delete_module() {
	my ($module_name,$agent_name) = @ARGV[2..3];
	
	print "[INFO] Deleting module '$module_name' from agent '$agent_name' \n\n";
	
	my $id_agent = get_agent_id($dbh,$agent_name);
	exist_check($id_agent,'agent',$agent_name);
	my $id_module = get_agent_module_id($dbh,$module_name,$id_agent);
	exist_check($id_module,'module',$module_name);
	
	pandora_delete_module($dbh,$id_module,$conf);
}

##############################################################################
# Create delete not policy modules.
# Related option: --delete_not_policy_modules
##############################################################################

sub cli_delete_not_policy_modules() {
	my $incomingdir;

	$incomingdir = $conf->{incomingdir}.'/conf/';

	# Open the folder
	opendir FOLDER, $incomingdir || die "[ERROR] Opening incoming directory";
	
	# Store the list of files
	my @files = readdir(FOLDER);
	my $file;
	
	print "[INFO] Deleting modules without policy from conf files \n\n";
	foreach $file (@files)
	{
		if($file ne '.' && $file ne '..') {
			my $ret = enterprise_hook('pandora_delete_not_policy_modules', [$incomingdir.$file]);
		}
	}
	
	my @local_modules_without_policies = get_db_rows ($dbh, 'SELECT * FROM tagente_modulo WHERE id_policy_module = 0 AND id_tipo_modulo = 1');
	
	
	foreach my $module (@local_modules_without_policies) {
		pandora_delete_module ($dbh, $module->{'id_agente_modulo'});
	}
}

##############################################################################
# Create template module.
# Related option: --create_template_module
##############################################################################

sub cli_create_template_module() {
	my ($template_name,$module_name,$agent_name) = @ARGV[2..4];
	
	print "[INFO] Adding template '$template_name' to module '$module_name' from agent '$agent_name' \n\n";
	
	my $id_agent = get_agent_id($dbh,$agent_name);
	exist_check($id_agent,'agent',$agent_name);
	my $module_id = get_agent_module_id($dbh,$module_name,$id_agent);
	exist_check($module_id,'module',$module_name);
	my $template_id = get_template_id($dbh,$template_name);
	exist_check($template_id,'template',$template_name);
	
	pandora_create_template_module ($conf, $dbh, $module_id, $template_id);
}

##############################################################################
# Delete template module.
# Related option: --delete_template_module
##############################################################################

sub cli_delete_template_module() {
	my ($template_name,$module_name,$agent_name) = @ARGV[2..4];

	print "[INFO] Delete template '$template_name' from module '$module_name' from agent '$agent_name' \n\n";

	my $id_agent = get_agent_id($dbh,$agent_name);
	exist_check($id_agent,'agent',$agent_name);
	my $module_id = get_agent_module_id($dbh,$module_name,$id_agent);
	exist_check($module_id,'module',$module_name);
	my $template_id = get_template_id($dbh,$template_name);
	exist_check($template_id,'template',$template_name);

	my $template_module_id = get_template_module_id($dbh, $module_id, $template_id);
	exist_check($template_module_id,"template '$template_name' on module",$module_name);
		
	pandora_delete_template_module ($dbh, $template_module_id);
}

##############################################################################
# Create template action.
# Related option: --create_template_action
##############################################################################

sub cli_create_template_action() {
	my ($action_name,$template_name,$module_name,$agent_name,$fires_min,$fires_max) = @ARGV[2..7];
	
	print "[INFO] Adding action '$action_name' to template '$template_name' in module '$module_name' from agent '$agent_name' with $fires_min min. fires and $fires_max max. fires\n\n";
	
	my $id_agent = get_agent_id($dbh,$agent_name);
	exist_check($id_agent,'agent',$agent_name);
	my $module_id = get_agent_module_id($dbh,$module_name,$id_agent);
	exist_check($module_id,'module',$module_name);
	my $template_id = get_template_id($dbh,$template_name);
	exist_check($template_id,'template',$template_name);
	my $template_module_id = get_template_module_id($dbh,$module_id,$template_id);
	exist_check($template_module_id,'template module',$template_name);
	my $action_id = get_action_id($dbh,$action_name);
	exist_check($action_id,'action',$action_name);
	
	$fires_min = 0 unless defined ($fires_min);
	$fires_max = 0 unless defined ($fires_max);
	
	my %parameters;						
	
	$parameters{'id_alert_template_module'} = $template_module_id;
	$parameters{'id_alert_action'} = $action_id;
	$parameters{'fires_min'} = $fires_min;
	$parameters{'fires_max'} = $fires_max;
	
	pandora_create_template_module_action ($conf, \%parameters, $dbh);
}

##############################################################################
# Delete template action.
# Related option: --delete_template_action
##############################################################################

sub cli_delete_template_action() {
	my ($action_name,$template_name,$module_name,$agent_name) = @ARGV[2..5];
	
	print "[INFO] Deleting action '$action_name' from template '$template_name' in module '$module_name' from agent '$agent_name')\n\n";

	my $id_agent = get_agent_id($dbh,$agent_name);
	exist_check($id_agent,'agent',$agent_name);
	my $module_id = get_agent_module_id($dbh,$module_name,$id_agent);
	exist_check($module_id,'module',$module_name);
	my $template_id = get_template_id($dbh,$template_name);
	exist_check($template_id,'template',$template_name);
	my $template_module_id = get_template_module_id($dbh,$module_id,$template_id);
	exist_check($template_module_id,'template module',$template_name);
	my $action_id = get_action_id($dbh,$action_name);
	exist_check($action_id,'action',$action_name);

	pandora_delete_template_module_action ($dbh, $template_module_id, $action_id);
}

##############################################################################
# Insert data to module.
# Related option: --data_module
##############################################################################

sub cli_data_module() {
	my ($server_name,$agent_name,$module_name,$module_type,$datetime) = @ARGV[2..6];
	my $utimestamp;
	
	if(defined($datetime)) {
		if ($datetime !~ /([0-9]{2,4})\-([0-1][0-9])\-([0-3][0-9]) +([0-2][0-9]):([0-5][0-9])/) {
			print "[ERROR] Invalid datetime $datetime. (Correct format: YYYY-MM-DD HH:mm)\n";
			exit;
		}
		# Add the seconds
		$datetime .= ":00";
		$utimestamp = dateTimeToTimestamp($datetime);
	}
	else {
		$utimestamp = time();
	}

	# The get_module_id has wrong name. Change in future
	my $module_type_id = get_module_id($dbh,$module_type);
	exist_check($module_type_id,'module type',$module_type);
	
	my $id_agent = get_agent_id($dbh,$agent_name);
	exist_check($id_agent,'agent',$agent_name);
	
	# Server_type 0 is dataserver
	my $server_id = get_server_id($dbh,$server_name,0);
	exist_check($server_id,'data server',$server_name);
	
	my $module = get_db_single_row ($dbh, 'SELECT * FROM tagente_modulo WHERE id_agente = ? AND id_tipo_modulo = ?', $id_agent, $module_type_id);

	if(not defined($module->{'module_interval'})) {
		print "[ERROR] No module data finded. \n\n";
		exit;
	}

	my %data = ('data' => 1);
	pandora_process_module ($conf, \%data, '', $module, $module_type, '', $utimestamp, $server_id, $dbh);
	
	print "[INFO] Inserting data to module '$module_name'\n\n";
}

##############################################################################
# Create a user.
# Related option: --create_user
##############################################################################

sub cli_create_user() {
	my ($user_name,$password,$is_admin,$comments) = @ARGV[2..5];
	
	$comments = (defined ($comments) ? safe_input($comments)  : '' );
	
	my $user_exists = get_user_exists ($dbh, $user_name);
	non_exist_check($user_exists,'user',$user_name);
	
	print "[INFO] Creating user '$user_name'\n\n";
	
	pandora_create_user ($dbh, $user_name, md5($password), $is_admin, $comments);
}

##############################################################################
# Update a user.
# Related option: --update_user
##############################################################################

sub cli_user_update() {
	my ($user_id,$field,$new_value) = @ARGV[2..4];
	
	my $user_exists = get_user_exists ($dbh, $user_id);
	exist_check($user_exists,'user',$user_id);
	
	if($field eq 'email' || $field eq 'phone' || $field eq 'is_admin' || $field eq 'language' || $field eq 'id_skin' || $field eq 'flash_chart') {
		# Fields admited, no changes
	}
	elsif($field eq 'comments' || $field eq 'fullname') {
		$new_value = safe_input($new_value);
	}
	elsif($field eq 'password') {
		if($new_value eq '') {
			print "[ERROR] Field '$field' cannot be empty\n\n";
			exit;
		}
		
		$new_value = md5($new_value);
	}
	else {
		print "[ERROR] Field '$field' doesnt exist\n\n";
		exit;
	}
		
	print "[INFO] Updating field '$field' in user '$user_id'\n\n";
	
	my $update;
	
	$update->{$field} = $new_value;
	
	pandora_update_user_from_hash ($update, 'id_user', safe_input($user_id), $dbh);
}

##############################################################################
# Update an agent field.
# Related option: --update_agent
##############################################################################

sub cli_agent_update() {
	my ($agent_name,$field,$new_value) = @ARGV[2..4];
	
	my $id_agent = get_agent_id($dbh,$agent_name);
	exist_check($id_agent,'agent',$agent_name);
	
	# agent_name, address, description, group_name, interval, os_name, disabled, parent_name, cascade_protection, icon_path, update_gis_data, custom_id
	
	if($field eq 'disabled' || $field eq 'cascade_protection' || $field eq 'icon_path' || 
	$field eq 'update_gis_data' || $field eq 'custom_id') {
		# Fields admited, no changes
	}
	elsif($field eq 'interval') {
		$field = 'intervalo';
	}
	elsif($field eq 'description') {
		$field = 'comentarios';
		$new_value = safe_input($new_value);
	}
	elsif($field eq 'parent_name') {
		my $id_parent = get_agent_id($dbh,$new_value);
		exist_check($id_parent,'agent',$new_value);
		$field = 'id_parent';
		$new_value = $id_parent;
	}
	elsif($field eq 'agent_name') {
		my $agent_exists = get_agent_id($dbh,$new_value);
		non_exist_check($agent_exists,'agent',$new_value);
		$field = 'nombre';
	}
	elsif($field eq 'group_name') {
		my $id_group = get_group_id($dbh, $new_value);
		exist_check($id_group,'group',$new_value);
		$new_value = $id_group;
		$field = 'id_grupo';
	}
	elsif($field eq 'os_name') {
		my $id_os = get_os_id($dbh, $new_value);
		exist_check($id_os,'operating system',$new_value);
		$new_value = $id_os;
		$field = 'id_os';
	}
	elsif($field eq 'address') {
		# Check if the address already exist
		my $address_id = get_addr_id($dbh,$new_value);
		
		# If the addres doesnt exist, we add it to the addresses list
		if($address_id == -1) {
			$address_id = add_address($dbh,$new_value);
		}
		
		# Add the address to the agent
		add_new_address_agent ($dbh, $address_id, $id_agent);
		
		$field = 'direccion';
	}
	else {
		print "[ERROR] Field '$field' doesnt exist\n\n";
		exit;
	}
		
	print "[INFO] Updating field '$field' in agent '$agent_name'\n\n";
	
	my $update;
	
	$update->{$field} = $new_value;

	pandora_update_table_from_hash ($conf, $update, 'id_agente', safe_input($id_agent), 'tagente', $dbh);
}

##############################################################################
# Update an alert template.
# Related option: --update_alert_template
##############################################################################

sub cli_alert_template_update() {
	my ($template_name,$field,$new_value) = @ARGV[2..4];
	
	my $template_id = pandora_get_alert_template_id ($dbh, $template_name);
	exist_check($template_id,'alert template',$template_name);
	
	if($field eq 'matches_value' || $field eq 'value' || $field eq 'min_value' || 
		$field eq 'max_value' || $field eq 'type' || $field eq 'time_threshold' || 
		$field eq 'time_from' || $field eq 'time_to' || $field eq 'monday' || 
		$field eq 'tuesday' || $field eq 'wednesday' || $field eq 'thursday' || 
		$field eq 'friday' || $field eq 'saturday' || $field eq 'sunday' || 
		$field eq 'min_alerts' || $field eq 'max_alerts' || $field eq 'recovery_notify') {
		# Fields admited, no changes
	}
	elsif($field eq 'name' || $field eq 'description' || $field eq 'field1' || $field eq 'field2' || $field eq 'field3' || $field eq 'recovery_field2' || $field eq 'recovery_field3') {
		$new_value = safe_input($new_value);
	}
	elsif($field eq 'priority') {
		if($new_value < 0 || $new_value > 4) {
			print "[ERROR] Field '$field' is out of interval (0-4)\n\n";
			exit;
		}
	}
	elsif($field eq 'default_action') {
		# Check if exist
		my $id_alert_action = get_action_id ($dbh, safe_input($new_value));
		exist_check($id_alert_action,'alert action',$new_value);
		$new_value = $id_alert_action;
		$field = 'id_alert_action';
	}
	elsif($field eq 'group_name') {
		# Check if exist
		my $id_group = get_group_id($dbh, $new_value);
		exist_check($id_group,'group',$new_value);
		$new_value = $id_group;
		$field = 'id_group';
	}
	else {
		print "[ERROR] Field '$field' doesnt exist\n\n";
		exit;
	}
		
	print "[INFO] Updating field '$field' in alert template '$template_name'\n\n";
	
	my $update;
	
	$update->{$field} = $new_value;
	
	pandora_update_alert_template_from_hash ($update, 'id', $template_id, $dbh);
}

##############################################################################
# Check the specific fields of data module when update
##############################################################################

sub pandora_check_data_module_fields($) {
	my $field_value = shift;
	
	print "[ERROR] The field '".$field_value->{'field'}."' is not available for data modules\n\n";
	
	exit;
}

##############################################################################
# Check the specific fields of network module when update
##############################################################################

sub pandora_check_network_module_fields($) {
	my $field_value = shift;
		
	if($field_value->{'field'} eq 'ff_threshold') {
		$field_value->{'field'} = 'min_ff_event';
	}
	elsif($field_value->{'field'} eq 'module_address') {
		my $agent_name = @ARGV[3];
		
		my $id_agent = get_agent_id($dbh,$agent_name);
	
		$field_value->{'field'} = 'ip_target';
		
		# Check if the address already exist
		my $address_id = get_addr_id($dbh,$field_value->{'new_value'});
		
		# If the addres doesnt exist, we add it to the addresses list
		if($address_id == -1) {
			$address_id = add_address($dbh,$field_value->{'new_value'});
		}
		
		# Add the address to the agent
		add_new_address_agent ($dbh, $address_id, $id_agent);
		
		# Only pending set as main address (Will be done at the end of the function)
	}
	elsif($field_value->{'field'} eq 'module_port') {
		if ($field_value->{'new_value'} > 65535 || $field_value->{'new_value'} < 1) {
			print "[ERROR] Port error. Port must into [1-65535]\n\n";
			exit;
		}
		$field_value->{'field'} = 'tcp_port';
	}
	else {
		print "[ERROR] The field '".$field_value->{'field'}."' is not available for network modules\n\n";
		exit;
	}
}

##############################################################################
# Check the specific fields of snmp module when update
##############################################################################

sub pandora_check_snmp_module_fields($) {
	my $field_value = shift;
		
	if($field_value->{'field'} eq 'version') {
		$field_value->{'field'} = 'tcp_send';
	}
	elsif($field_value->{'field'} eq 'ff_threshold') {
		$field_value->{'field'} = 'min_ff_event';
	}
	elsif($field_value->{'field'} eq 'community') {
		$field_value->{'field'} = 'snmp_community';
	}
	elsif($field_value->{'field'} eq 'oid') {
		$field_value->{'field'} = 'snmp_oid';
	}
	elsif($field_value->{'field'} eq 'snmp3_priv_method') {
		$field_value->{'field'} = 'custom_string_1';
	}
	elsif($field_value->{'field'} eq 'snmp3_priv_pass') {
		$field_value->{'field'} = 'custom_string_2';
	}
	elsif($field_value->{'field'} eq 'snmp3_sec_level') {
		$field_value->{'field'} = 'custom_string_3';
	}
	elsif($field_value->{'field'} eq 'snmp3_auth_method') {
		$field_value->{'field'} = 'plugin_parameter';
	}
	elsif($field_value->{'field'} eq 'snmp3_auth_user') {
		$field_value->{'field'} = 'plugin_user';
	}
	elsif($field_value->{'field'} eq 'snmp3_auth_pass') {
		$field_value->{'field'} = 'plugin_pass';
	}
	elsif($field_value->{'field'} eq 'module_address') {
		my $agent_name = @ARGV[3];
		
		my $id_agent = get_agent_id($dbh,$agent_name);
	
		$field_value->{'field'} = 'ip_target';
		
		# Check if the address already exist
		my $address_id = get_addr_id($dbh,$field_value->{'new_value'});
		
		# If the addres doesnt exist, we add it to the addresses list
		if($address_id == -1) {
			$address_id = add_address($dbh,$field_value->{'new_value'});
		}
		
		# Add the address to the agent
		add_new_address_agent ($dbh, $address_id, $id_agent);
		
		# Only pending set as main address (Will be done at the end of the function)
	}
	elsif($field_value->{'field'} eq 'module_port') {
		if ($field_value->{'new_value'} > 65535 || $field_value->{'new_value'} < 1) {
			print "[ERROR] Port error. Port must into [1-65535]\n\n";
			exit;
		}
		$field_value->{'field'} = 'tcp_port';
	}
	else {
		print "[ERROR] The field '".$field_value->{'field'}."' is not available for SNMP modules\n\n";
		exit;
	}
}

##############################################################################
# Check the specific fields of plugin module when update
##############################################################################

sub pandora_check_plugin_module_fields($) {
	my $field_value = shift;
		
	if($field_value->{'field'} eq 'plugin_name') {
		my $plugin_id = get_plugin_id($dbh,$field_value->{'new_value'});
		exist_check($plugin_id,'plugin',$field_value->{'new_value'});
		
		$field_value->{'new_value'} = $plugin_id;
		$field_value->{'field'} = 'id_plugin';
	}
	elsif($field_value->{'field'} eq 'user') {
		$field_value->{'field'} = 'plugin_user';
	}
	elsif($field_value->{'field'} eq 'password') {
		$field_value->{'field'} = 'plugin_pass';
	}
	elsif($field_value->{'field'} eq 'parameters') {
		$field_value->{'field'} = 'plugin_parameter';
		$field_value->{'new_value'} = safe_input($field_value->{'new_value'});
	}
	elsif($field_value->{'field'} eq 'ff_threshold') {
		$field_value->{'field'} = 'min_ff_event';
	}
	elsif($field_value->{'field'} eq 'module_address') {
		my $agent_name = @ARGV[3];
		
		my $id_agent = get_agent_id($dbh,$agent_name);
		
		$field_value->{'field'} = 'ip_target';
		
		# Check if the address already exist
		my $address_id = get_addr_id($dbh,$field_value->{'new_value'});
		
		# If the addres doesnt exist, we add it to the addresses list
		if($address_id == -1) {
			$address_id = add_address($dbh,$field_value->{'new_value'});
		}
		
		# Add the address to the agent
		add_new_address_agent ($dbh, $address_id, $id_agent);
		
		# Only pending set as main address (Will be done at the end of the function)
	}
	elsif($field_value->{'field'} eq 'module_port') {
		$field_value->{'field'} = 'tcp_port';
	}
	else {
		print "[ERROR] The field '".$field_value->{'field'}."' is not available for plugin modules\n\n";
		exit;
	}
}

##############################################################################
# Add a profile to a User in a Group
# Related option: --update_module
##############################################################################

sub cli_module_update() {
	my ($module_name,$agent_name,$field,$new_value) = @ARGV[2..5];
	
	my $id_agent = get_agent_id($dbh,$agent_name);
	exist_check($id_agent,'agent',$agent_name);
	my $id_agent_module = get_agent_module_id ($dbh, $module_name, $id_agent);
	exist_check($id_agent_module,'agent module',$module_name);
	
	# Check and adjust parameters in common values
	
	if($field eq 'min' || $field eq 'max' || $field eq 'post_process' || $field eq 'history_data') {
		# Fields admited, no changes
	}
	elsif($field eq 'interval') {
		$field = 'module_interval';
	}
	elsif($field eq 'warning_min') {
		$field = 'min_warning';
	}
	elsif($field eq 'warning_max') {
		$field = 'max_warning';
	}
	elsif($field eq 'critical_min') {
		$field = 'min_critical';
	}
	elsif($field eq 'critical_max') {
		$field = 'max_critical';
	}
	elsif($field eq 'warning_str') {
		$field = 'str_warning';
		$new_value = safe_input($new_value);
	}
	elsif($field eq 'critical_str') {
		$field = 'str_critical';
		$new_value = safe_input($new_value);
	}
	elsif($field eq 'agent_name') {
		my $id_agent_change = get_agent_id($dbh,$new_value);
		exist_check($id_agent_change,'agent',$new_value);
		my $id_agent_module_exist = get_agent_module_id ($dbh, $module_name, $id_agent_change);
		if($id_agent_module_exist != -1) {
			print "[ERROR] A module called '$module_name' already exist in the agent '$new_value'\n\n";
			exit;
		}
		$field = 'id_agente';
		$new_value = $id_agent_change;
	}
	elsif($field eq 'module_name') {
		my $id_agent_module_change = get_agent_module_id ($dbh, $new_value, $id_agent);
		if($id_agent_module_change != -1) {
			print "[ERROR] A module called '$new_value' already exist in the agent '$agent_name'\n\n";
			exit;
		}
		$field = 'nombre';
		$new_value = safe_input($new_value);
	}
	elsif($field eq 'description') {
		$field = 'descripcion';
		$new_value = safe_input($new_value);
	}
	elsif($field eq 'module_group') {
		my $module_group_id = get_module_group_id($dbh,$new_value);

		if($module_group_id == -1) {
			print "[ERROR] Module group '$new_value' doesnt exist\n\n";
			exit;
		}
		$field = 'id_module_group';
		$new_value = $module_group_id;
	}
	else {
		# If is not a common value, check type and call type update funtion
		my $type = pandora_get_module_type($dbh,$id_agent_module);
		
		my %field_value;
		$field_value{'field'} = $field;
		$field_value{'new_value'} = $new_value;

		if($type eq 'data') {
			pandora_check_data_module_fields(\%field_value);
		}
		elsif($type eq 'network') {
			pandora_check_network_module_fields(\%field_value);
		}
		elsif($type eq 'snmp') {
			pandora_check_snmp_module_fields(\%field_value);
		}
		elsif($type eq 'plugin') {
			pandora_check_plugin_module_fields(\%field_value);
		}
		else {
			print "[ERROR] The field '$field' is not available for this type of module\n\n";
		}
		
		$field = $field_value{'field'};
		$new_value = $field_value{'new_value'};
	}
	
	print "[INFO] Updating field '$field' in module '$module_name' of agent '$agent_name' with new value '$new_value'\n\n";
	
	my $update;
	
	$update->{$field} = $new_value;
	
	pandora_update_module_from_hash ($conf, $update, 'id_agente_modulo', $id_agent_module, $dbh);
}

##############################################################################
# Exec a CLI option from file
# Related option: --exec_from_file
##############################################################################

sub cli_exec_from_file() {
	my $c = 0;
	my $command = $0;
	my $file;
	foreach my $opt (@ARGV) {
		$c++;

		# First and second are the script and conf paths
		if($c < 2) {
			$command = "$command $opt";
		}	
		# Third param is ignored, because is --exec_from_file
		# Fourth param is the file path
		elsif($c == 3) {
			$file = $opt;
			if(!(-e $file)) {
				print "[ERROR] File '$file' not exists or cannot be opened\n\n";
				exit;
			}
		}
		# Fifth parameter is the option (we add -- before it)
		elsif($c == 4) {
			$command = "$command --$opt";
		}
		# Next parameters are the option params, we add quotes to them to avoid errors
		elsif($c > 4) {
			if($opt =~ m/\s/g) {
				$command = $command . ' "' . $opt .'"';
			}
			else {
				$command = $command . ' ' . $opt;
			}
		}
	}
	
	open (FILE, $file);
	while (<FILE>) {
		my $command_tr = $command;
		chomp;
		my @fields = split(',',$_);
		$c = 0;
		foreach my $field (@fields) {
			$c++;
			my $macro_name = "__FIELD".$c."__";
			if($field =~ m/\s/g && $field !~ m/^"/) {
				$field = '"'.$field.'"';
			}
			$command_tr !~ s/$macro_name/$field/g;
		}
		print `./$command_tr`;
	}
	close (FILE);
	
	exit;
}

##############################################################################
# Return the type of given module (data, network, snmp or plugin)
##############################################################################

sub pandora_get_module_type($$) {
	my ($dbh,$id_agent_module) = @_;
	
	my $id_modulo = get_db_value($dbh, 'SELECT id_modulo FROM tagente_modulo WHERE id_agente_modulo = ?',$id_agent_module);
	
	if($id_modulo == 1) {
		return 'data';
	}
	if($id_modulo == 2) {
		my $id_module_type = get_db_value($dbh, 'SELECT id_tipo_modulo FROM tagente_modulo WHERE id_agente_modulo = ?',$id_agent_module);
		if($id_module_type >= 15 && $id_module_type <= 18) {
			return 'snmp';
		}
		else {
			return 'network';
		}
	}
	elsif($id_modulo == 4) {
		return 'plugin';
	}
	elsif($id_modulo == 6) {
		return 'wmi';
	}
	elsif($id_modulo == 7) {
		return 'web';
	}
	else {
		return 'unknown';
	}
}

##############################################################################
# Add a profile to a User in a Group
# Related option: --add_profile_to_user
##############################################################################

sub cli_user_add_profile() {
	my ($user_id,$profile_name,$group_name) = @ARGV[2..4];
	
	my $user_exists = get_user_exists ($dbh, $user_id);
	exist_check($user_exists,'user',$user_id);
	
	my $profile_id = get_profile_id($dbh, $profile_name);
	exist_check($profile_id,'profile',$profile_name);
	
	my $group_id = 0;
	
	# If group name is not defined, we assign group All (0)
	if(defined($group_name)) {
		$group_id = get_group_id($dbh, $group_name);
		exist_check($group_id,'group',$group_name);
	}
	else {
		$group_name = 'All';
	}
		
	print "[INFO] Adding profile '$profile_name' to user '$user_id' in group '$group_name'\n\n";
		
	pandora_add_profile_to_user ($dbh, $user_id, $profile_id, $group_id);
}

##############################################################################
# Delete a user.
# Related option: --delete_user
##############################################################################

sub cli_delete_user() {
	my $user_name = @ARGV[2];
	
	print "[INFO] Deleting user '$user_name' \n\n";
	
	my $result = pandora_delete_user($dbh,$user_name);
	exist_check($result,'user',$user_name);
}

##############################################################################
# Delete an alert_template.
# Related option: --delete_user
##############################################################################

sub cli_delete_alert_template() {
	my $template_name = @ARGV[2];
	
	print "[INFO] Deleting template '$template_name' \n\n";
	
	my $result = pandora_delete_alert_template($dbh,$template_name);
	exist_check($result,'alert template',$template_name);
}

##############################################################################
# Create profile.
# Related option: --create_profile
##############################################################################

sub cli_create_profile() {
	my ($user_name,$profile_name,$group_name) = @ARGV[2..4];
	
	my $id_profile = get_profile_id($dbh,$profile_name);
	exist_check($id_profile,'profile',$profile_name);
	
	my $id_group;
	
	if($group_name eq "All") {
		$id_group = 0;
		print "[INFO] Adding profile '$profile_name' to all groups for user '$user_name') \n\n";
	}
	else {
		$id_group = get_group_id($dbh,$group_name);
		exist_check($id_group,'group',$group_name);
		print "[INFO] Adding profile '$profile_name' to group '$group_name' for user '$user_name') \n\n";
	}
	
	pandora_create_user_profile ($dbh, $user_name, $id_profile, $id_group);
}

##############################################################################
# Delete profile.
# Related option: --delete_profile
##############################################################################

sub cli_delete_profile() {
	my ($user_name,$profile_name,$group_name) = @ARGV[2..4];
	
	my $id_profile = get_profile_id($dbh,$profile_name);
	exist_check($id_profile,'profile',$profile_name);
	
	my $id_group;
	
	if($group_name eq "All") {
		$id_group = 0;
		print "[INFO] Deleting profile '$profile_name' from all groups for user '$user_name') \n\n";
	}
	else {
		$id_group = get_group_id($dbh,$group_name);
		exist_check($id_group,'group',$group_name);
		print "[INFO] Deleting profile '$profile_name' from group '$group_name' for user '$user_name') \n\n";
	}
	
	pandora_delete_user_profile ($dbh, $user_name, $id_profile, $id_group);
}

##############################################################################
# Create event
# Related option: --create_event
##############################################################################

sub cli_create_event() {
	my ($event,$event_type,$group_name,$agent_name,$module_name,$event_status,$severity,$template_name, $user_name, $comment, $source, $id_extra, $tags) = @ARGV[2..14];

	$event_status = 0 unless defined($event_status);
	$severity = 0 unless defined($severity);

	my $id_user;
	
	if (!defined($user_name)) {
		$id_user = 0;
	}
	else {
		$id_user = pandora_get_user_id($dbh,$user_name);
		exist_check($id_user,'user',$user_name);
	}
	
	my $id_group;
	
	if (!defined($group_name) || $group_name eq "All") {
		$id_group = 0;
	}
	else {
		$id_group = get_group_id($dbh,$group_name);
		exist_check($id_group,'group',$group_name);
	}
	
	my $id_agent;
	
	if (!defined($agent_name)) {
		$id_agent = 0;
	}
	else {
		$id_agent = get_agent_id($dbh,$agent_name);
		exist_check($id_agent,'agent',$agent_name);
	}
	
	my $id_agentmodule;
	
	if (!defined($module_name)) {
		$id_agentmodule = 0;
	}
	else {
		$id_agentmodule = get_agent_module_id($dbh,$module_name,$id_agent);
		exist_check($id_agentmodule,'module',$module_name);
	}
	
	my $id_alert_agent_module;
				
	if(defined($template_name) && $template_name ne '') {
		my $id_template = get_template_id($dbh,$template_name);
		exist_check($id_template,'template',$template_name);
		$id_alert_agent_module = get_template_module_id($dbh,$id_agentmodule,$id_template);
		exist_check($id_alert_agent_module,'alert template module',$template_name);
	}
	else {
		$id_alert_agent_module = 0;
	}
	
	print "[INFO] Adding event '$event' for agent '$agent_name' \n\n";

	pandora_event ($conf, $event, $id_group, $id_agent, $severity,
		$id_alert_agent_module, $id_agentmodule, $event_type, $event_status, $dbh, $source, $user_name, $comment, $id_extra, $tags);
}

##############################################################################
# Validate event.
# Related option: --validate_event
##############################################################################

sub cli_validate_event() {
	my ($agent_name, $module_name, $datetime_min, $datetime_max, $user_name, $criticity, $template_name) = @ARGV[2..8];
	my $id_agent = '';
	my $id_agentmodule = '';

	if(defined($agent_name) && $agent_name ne '') {
		$id_agent = get_agent_id($dbh,$agent_name);
		exist_check($id_agent,'agent',$agent_name);
		
		if($module_name ne '') {
			$id_agentmodule = get_agent_module_id($dbh, $module_name, $id_agent);
			exist_check($id_agentmodule,'module',$module_name);
		}
	}

	if(defined($datetime_min) && $datetime_min ne '') {
		if ($datetime_min !~ /([0-9]{2,4})\-([0-1][0-9])\-([0-3][0-9]) +([0-2][0-9]):([0-5][0-9])/) {
			print "[ERROR] Invalid datetime_min format. (Correct format: YYYY-MM-DD HH:mm)\n";
			exit;
		}
		# Add the seconds
		$datetime_min .= ":00";
	}
	
	if(defined($datetime_max) && $datetime_max ne '') {
		if ($datetime_max !~ /([0-9]{2,4})\-([0-1][0-9])\-([0-3][0-9]) +([0-2][0-9]):([0-5][0-9])/) {
			print "[ERROR] Invalid datetime_max $datetime_max. (Correct format: YYYY-MM-DD HH:mm)\n";
			exit;
		}
		# Add the seconds
		$datetime_max .= ":00";
	}

	my $id_alert_agent_module = '';
	
	if(defined($template_name) && $template_name ne '') {
		my $id_template = get_template_id($dbh,$template_name);
		exist_check($id_template,'template',$template_name);
		$id_alert_agent_module = get_template_module_id($dbh,$id_agentmodule,$id_template);
		exist_check($id_alert_agent_module,'template module',$template_name);
	}
				
	pandora_validate_event_filter ($conf, $id_agentmodule, $id_agent, $datetime_min, $datetime_max, $user_name, $id_alert_agent_module, $criticity, $dbh);
	print "[INFO] Validating event for agent '$agent_name'\n\n";
}

##############################################################################
# Validate event.
# Related option: --validate_event_id
##############################################################################

sub cli_validate_event_id() {
	my $id_event = @ARGV[2];

	my $event_name = pandora_get_event_name($dbh, $id_event);
	exist_check($event_name,'event',$id_event);
	
	print "[INFO] Validating event '$id_event'\n\n";
				
	my $result = pandora_validate_event_id ($conf, $id_event, $dbh);
	exist_check($result,'event',$id_event);
	
}

###############################################################################
# Get event info
# Related option: --get_event_info
###############################################################################
sub cli_get_event_info () {
	my ($id_event,$csv_separator) = @ARGV[2..3];
	
	my $event_name = pandora_get_event_name($dbh, $id_event);
	exist_check($event_name,'event',$id_event);
	
	$csv_separator = '|' unless defined($csv_separator);
	
	my $query = "SELECT * FROM tevento where id_evento=".$id_event;

	my $header = "Event ID".$csv_separator."Event name".$csv_separator."Agent ID".$csv_separator."User ID".$csv_separator.
				"Group ID".$csv_separator."Status".$csv_separator."Timestamp".$csv_separator."Event type".$csv_separator.
				"Agent module ID".$csv_separator."Alert module ID".$csv_separator."Criticity".$csv_separator.
				"Comment".$csv_separator."Tags".$csv_separator."Source".$csv_separator."Extra ID"."\n";
	print $header;
	
	my @result = get_db_single_row($dbh, $query);
	foreach my $event_data (@result) {
		print $event_data->{'id_evento'};
		print $csv_separator;
		print $event_data->{'evento'};
		print $csv_separator;
		print $event_data->{'id_agente'};
		print $csv_separator;
		print $event_data->{'id_usuario'};
		print $csv_separator;
		print $event_data->{'id_grupo'};
		print $csv_separator;
		print $event_data->{'estado'};
		print $csv_separator;
		print $event_data->{'timestamp'};
		print $csv_separator;
		print $event_data->{'event_type'};
		print $csv_separator;
		print $event_data->{'id_agentmodule'};
		print $csv_separator;
		print $event_data->{'id_alert_am'};
		print $csv_separator;
		print $event_data->{'criticity'};
		print $csv_separator;
		print $event_data->{'user_comment'};
		print $csv_separator;
		print $event_data->{'tags'};
		print $csv_separator;
		print $event_data->{'source'};
		print $csv_separator;
		print $event_data->{'id_extra'};
		print "\n";
	}
	
    exit;
}
##############################################################################
# Create incident.
# Related option: --create_incident
##############################################################################

sub cli_create_incident() {
	my ($title, $description, $origin, $status, $priority, $group_name, $owner) = @ARGV[2..8];
	
	my $id_group = get_group_id($dbh,$group_name);
	exist_check($id_group,'group',$group_name);
				
	pandora_create_incident ($conf, $dbh, $title, $description, $priority, $status, $origin, $id_group, $owner);
	print "[INFO] Creating incident '$title'\n\n";
}

##############################################################################
# Delete data.
# Related option: --delete_data
##############################################################################

sub cli_delete_data($) {
	my $ltotal = shift;
	my ($opt, $name, $name2) = @ARGV[2..4];

	if($opt eq '-m' || $opt eq '--m') {
		# Delete module data
		param_check($ltotal, 3) unless ($name2 ne '');
		my $id_agent = get_agent_id($dbh,$name2);
		exist_check($id_agent,'agent',$name2);
		
		my $id_module = get_agent_module_id($dbh,$name,$id_agent);
		exist_check($id_module,'module',$name);
	
		print "DELETING THE DATA OF THE MODULE $name OF THE AGENT $name2\n\n";
		
		pandora_delete_data($dbh, 'module', $id_module);
	}
	elsif($opt eq '-a' || $opt eq '--a') {
		# Delete agent's modules data
		my $id_agent = get_agent_id($dbh,$name);
		exist_check($id_agent,'agent',$name);
		
		print "DELETING THE DATA OF THE AGENT $name\n\n";
		
		pandora_delete_data($dbh, 'module', $id_agent);
	}
	elsif($opt eq '-g' || $opt eq '--g') {
		# Delete group's modules data
		my $id_group = get_group_id($dbh,$name);
		exist_check($id_group,'group',$name);
		
		print "DELETING THE DATA OF THE GROUP $name\n\n";
		
		pandora_delete_data($dbh, 'group', $id_group);
	}
	else {
		print "[ERROR] Invalid parameter '$opt'.\n\n";
		help_screen ();
		exit;
	}
}

##############################################################################
# Add policy to apply queue.
# Related option: --apply_policy
##############################################################################

sub cli_apply_policy() {
	my $policy_name = @ARGV[2];
	
	my $policy_id = enterprise_hook('get_policy_id',[$dbh, safe_input($policy_name)]);
	exist_check($policy_id,'policy',$policy_name);
	
	my $ret = enterprise_hook('pandora_add_policy_queue', [$dbh, $conf, $policy_id, 'apply']);
	
	if($ret == -1) {
		print "[ERROR] Operation 'apply' cannot be added to policy '$policy_name' because is duplicated in queue or incompatible with others operations\n\n";
		exit;
	}
	
	print "[INFO] Added operation 'apply' to policy '$policy_name'\n\n";
}

##############################################################################
# Add all policies to apply queue.
# Related option: --apply_all_policies
##############################################################################

sub cli_apply_all_policies() {
	my $policies = enterprise_hook('get_policies', [$dbh, 0]);
	
	my $npolicies = scalar(@{$policies});
	
	print "[INFO] $npolicies policies found\n\n";
	
	my $added = 0;
	foreach my $policy (@{$policies}) {
		my $ret = enterprise_hook('pandora_add_policy_queue', [$dbh, $conf, $policy->{'id'}, 'apply']);
		if($ret != -1) {
			$added++;
			print "[INFO] Added operation 'apply' to policy '".safe_output($policy->{'name'})."'\n";
		}
	}
		
	if($npolicies > $added) {
		my $failed = $npolicies - $added;
		print "[ERROR] $failed policies cannot be added to apply queue. Maybe the queue already contains these operations.\n";
	}
}

##############################################################################
# Validate all the alerts
# Related option: --validate_all_alerts
##############################################################################

sub cli_validate_all_alerts() {
	print "[INFO] Validating all the alerts\n\n";
		
	my $res = db_update ($dbh, "UPDATE talert_template_modules SET times_fired = 0, internal_counter = 0");
	
	if($res == -1) {
		print "[ERROR] Alerts cannot be validated\n\n";
	}
}

##############################################################################
# Validate the alerts of a given policy
# Related option: --validate_policy_alerts
##############################################################################

sub cli_validate_policy_alerts() {
	my $policy_name = @ARGV[2];
	
	my $policy_id = enterprise_hook('get_policy_id',[$dbh, safe_input($policy_name)]);
	exist_check($policy_id,'policy',$policy_name);
	
	my $policy_alerts = enterprise_hook('get_policy_alerts',[$dbh, $policy_id]);
	
	my @policy_alerts_id_array;
	my $policy_alerts_id = '';
	
	my $cont = 0;
	foreach my $alert (@{$policy_alerts}) {
		$policy_alerts_id_array[$cont] = $alert->{'id'};
		$cont++;
	}
	
	if($#policy_alerts_id_array == -1) {
		print "[INFO] No alerts found in the policy '$policy_name'\n\n";
	}
	
	$policy_alerts_id = join(',',@policy_alerts_id_array);
	
	print "[INFO] Validating the alerts of the policy '$policy_name'\n\n";
		
	my $res = db_update ($dbh, "UPDATE talert_template_modules SET times_fired = 0, internal_counter = 0 WHERE id_policy_alerts IN (?)", $policy_alerts_id);
	
	if($res == -1) {
		print "[ERROR] Alerts cannot be validated\n\n";
	}
}

##############################################################################
# Show the group name where is a given agent
# Related option: --get_agent_group
##############################################################################

sub cli_get_agent_group() {
	my $agent_name = @ARGV[2];
	
	my $id_agent = get_agent_id($dbh,$agent_name);
	exist_check($id_agent,'agent',$agent_name);
	
	my $id_group = get_agent_group ($dbh, $id_agent);
	
	my $group_name = get_group_name ($dbh, $id_group);

	print $group_name;
}

##############################################################################
# Show the agent and current data of all the modules with the same name
# Related option: --get_agents_module_current_data
##############################################################################

sub cli_get_agents_module_current_data() {
	my $module_name = @ARGV[2];
	
	my $agents = pandora_get_module_agents($dbh, $module_name);
	exist_check(scalar(@{$agents})-1,'data of module',$module_name);
	
	print "id_agent,agent_name,module_data\n";
	foreach my $agent (@{$agents}) {
		my $current_data = pandora_get_module_current_data($dbh, $agent->{'id_agente_modulo'});
		print $agent->{'id_agente'}.",".$agent->{'nombre'}.",$current_data\n";
	}
}

##############################################################################
# Show all the modules of an agent
# Related option: --get_agent_modules
##############################################################################

sub cli_get_agent_modules() {
	my $agent_name = @ARGV[2];
	
	my $id_agent = get_agent_id($dbh,$agent_name);
	exist_check($id_agent,'agent',$agent_name);
	
	my $modules = pandora_get_agent_modules ($dbh, $id_agent);

	if(scalar(@{$modules}) == 0) {
		print "[INFO] The agent '$agent_name' have not modules\n\n";
	}
	
	print "id_module, module_name\n";
	foreach my $module (@{$modules}) {
		print $module->{'id_agente_modulo'}.",".safe_output($module->{'nombre'})."\n";
	}
}

##############################################################################
# Show all the modules of a policy
# Related option: --get_policy_modules
##############################################################################

sub cli_get_policy_modules() {
	my $policy_name = @ARGV[2];
	
	my $policy_id = enterprise_hook('get_policy_id',[$dbh, safe_input($policy_name)]);
	exist_check($policy_id,'policy',$policy_name);
	
	my $policy_modules = enterprise_hook('get_policy_modules',[$dbh, $policy_id]);
	exist_check(scalar(@{$policy_modules})-1,'modules in policy',$policy_name);
	
	print "id_policy_module, module_name\n";
	foreach my $module (@{$policy_modules}) {
		print $module->{'id'}.",".safe_output($module->{'name'})."\n";
	}
}

##############################################################################
# Show all the policies (without parameters) or the policies of given agent
# Related option: --get_policies
##############################################################################

sub cli_get_policies() {
	my $agent_name = @ARGV[2];
	my $policies;

	if(defined($agent_name)) {
		my $id_agent = get_agent_id($dbh,$agent_name);
		exist_check($id_agent,'agent',$agent_name);
		
		$policies = enterprise_hook('get_agent_policies',[$dbh,$id_agent]);

		if(scalar(@{$policies}) == 0) {
			print "[INFO] No policies found on agent '$agent_name'\n\n";
			exit;
		}
	}
	else {
		$policies = enterprise_hook('get_policies',[$dbh]);
		if(scalar(@{$policies}) == 0) {
			print "[INFO] No policies found\n\n";
			exit;
		}
	}
	
	print "id_policy, policy_name\n";
	foreach my $module (@{$policies}) {
		print $module->{'id'}.",".safe_output($module->{'name'})."\n";
	}
}

##############################################################################
# Show all the agents (without parameters) or the agents with a filter parameters
# Related option: --get_agents
##############################################################################

sub cli_get_agents() {
	my ($group_name, $os_name, $status, $max_modules, $filter_substring, $policy_name) = @ARGV[2..7];
	
	my $condition = ' 1=1';
	
	my $id_group;
	my $id_os;
	my $policy_id;

	if($group_name ne '') {
		$id_group = get_group_id($dbh, $group_name);
		exist_check($id_group,'group',$group_name);
		
		$condition .= " AND id_grupo = $id_group ";
	}
	
	if($os_name ne '') {
		$id_os = get_os_id($dbh, $os_name);
		exist_check($id_os,'operative system',$os_name);
		
		$condition .= " AND id_os = $id_os ";
	}
	
	if($policy_name ne '') {
		$policy_id = enterprise_hook('get_policy_id',[$dbh, safe_input($policy_name)]);
		exist_check($policy_id,'policy',$policy_name);
		
		$condition .= " AND id_agente IN (SELECT id_agent FROM tpolicy_agents 
		WHERE id_policy = $policy_id )";
	}
	
	if($max_modules ne '') {	
		$condition .= " AND id_agente NOT IN (SELECT id_agente FROM tagente_modulo t1 
		WHERE (SELECT count(*) FROM tagente_modulo WHERE id_agente = t1.id_agente) > $max_modules)";
	}
	
	if($filter_substring ne '') {
		$condition .= " AND nombre LIKE '%".safe_input($filter_substring)."%'";
	}
		
	my @agents = get_db_rows ($dbh, "SELECT * FROM tagente WHERE $condition");	

	if(scalar(@agents) == 0) {
		print "[INFO] No agents found\n\n";
		exit;
	}
	
	my $agent_status;
	
	my $head_print = 0;
	foreach my $agent (@agents) {
		if($status ne '') {
			$agent_status = pandora_get_agent_status($dbh,$agent->{'id_agente'});
			if($status ne $agent_status || $agent_status eq '') {
				next;
			}
		}
		if($head_print == 0) {
			$head_print = 1;
			print "id_agent, agent_name\n";
		}
		print $agent->{'id_agente'}.",".safe_output($agent->{'nombre'})."\n";
	}
	
	if($head_print == 0) {
		print "[INFO] No agents found\n\n";
	}
}

##############################################################################
# Delete agent conf.
# Related option: --delete_conf_file
##############################################################################

sub cli_delete_conf_file() {
	my $agent_name = @ARGV[2];
	
	my $conf_deleted = 0;
	my $md5_deleted = 0;
	
	if (-e $conf->{incomingdir}.'/conf/'.md5($agent_name).'.conf') {
		unlink($conf->{incomingdir}.'/conf/'.md5($agent_name).'.conf');
		$conf_deleted = 1;
	}
	if (-e $conf->{incomingdir}.'/md5/'.md5($agent_name).'.md5') {
		unlink($conf->{incomingdir}.'/md5/'.md5($agent_name).'.md5');
		$md5_deleted = 1;
	}
	
	if($conf_deleted == 1 || $md5_deleted == 1) {
		print "[INFO] Local conf files of the agent '$agent_name' has been deleted succesfully\n\n";
	}
	else {
		print "[ERROR] Local conf file of the agent '$agent_name' didn't found\n\n";
		exit;
	}
}

##############################################################################
# Delete modules from all conf files (without parameters) or of the conf file of the given agent.
# Related option: --clean_conf_file
##############################################################################

sub cli_clean_conf_file() {
	my $agent_name = @ARGV[2];
	my $result;
	
	if(defined($agent_name)) {
		if (-e $conf->{incomingdir}.'/conf/'.md5($agent_name).'.conf') {
			$result = enterprise_hook('pandora_clean_conf_file',[$conf, md5($agent_name)]);
			if($result != -1) {
				print "[INFO] Conf file '".$conf->{incomingdir}.'/conf/'.md5($agent_name).".conf has been cleaned'\n\n";
			}
		}
	}
	else {
		my $list_command = 'ls '.$conf->{incomingdir}.'/conf/';
		my $out = `$list_command`;
		my @files = split('\n',$out);
		# TODO: FINISH OPTION! NOW ONLY SHOW FILES
		foreach my $file (@files) {
			# Get the md5 hash
			my @filesplit = split('.',$file);
			$result = enterprise_hook('pandora_clean_conf_file',[$conf,$filesplit[0]]);
			if($result != -1) {
				print "[INFO] Conf file '".$conf->{incomingdir}.'/conf/'.$filesplit[0].".conf has been cleaned'\n\n";
			}
		}
	}
}

##############################################################################
# Get the files bad configured (without essential tokens)
# Related option: --get_bad_conf_files
##############################################################################

sub cli_get_bad_conf_files() {
	my $list_command = 'ls '.$conf->{incomingdir}.'/conf/';
	my $out = `$list_command`;
	my @files = split('\n',$out);
	my $bad_files = 0;

	foreach my $file (@files) {
		# Check important tokens
		my $missings = 0;
		my @tokens = ("server_ip","server_path","temporal","logfile");
		
		foreach my $token (@tokens) {
			if(enterprise_hook('pandora_check_conf_token',[$conf->{incomingdir}.'/conf/'.$file, $token]) == 0) {
				$missings++;
			}
		}
		
		# If any token of checked is missed we print the file path
		if($missings > 0) {
			print $conf->{incomingdir}.'/conf/'.$file."\n";
			$bad_files++;
		}
	}
	
	if($bad_files == 0) {
		print "[INFO] No bad files found\n\n";
	}
}

##############################################################################
# Disable policy alerts.
# Related option: --disable_policy_alerts
##############################################################################

sub cli_disable_policy_alerts() {
	my $policy_name = @ARGV[2];
	
	my $policy_id = enterprise_hook('get_policy_id',[$dbh, safe_input($policy_name)]);
	exist_check($policy_id,'policy',$policy_name);
	
	# Flag as disabled the policy alerts
	my $array_pointer_ag = enterprise_hook('pandora_disable_policy_alerts',[$dbh, $policy_id]);
}

##############################################################################
# Add an agent to a policy
# Related option: --add_agent_to_policy
##############################################################################

sub cli_policy_add_agent() {
	my ($agent_name, $policy_name) = @ARGV[2..3];
	
	my $agent_id = get_agent_id($dbh,$agent_name);
	exist_check($agent_id,'agent',$agent_name);
	
	my $policy_id = enterprise_hook('get_policy_id',[$dbh, safe_input($policy_name)]);
	exist_check($policy_id,'policy',$policy_name);
		
	# Add the agent to policy
	my $policy_agent_id = enterprise_hook('pandora_policy_add_agent',[$policy_id, $agent_id, $dbh]);
	
	if($policy_agent_id == -1) {
		print "[ERROR] A problem has been ocurred adding agent '$agent_name' to policy '$policy_name'\n\n";
	}
	else {
		print "[INFO] Added agent '$agent_name' to policy '$policy_name'. Is necessary to apply the policy in order to changes take effect.\n\n";
	}
}

##############################################################################
# Create group
# Related option: --create_group
##############################################################################

sub cli_create_group() {
	my ($group_name,$parent_group_name,$icon) = @ARGV[2..4];
		
	my $group_id = get_group_id($dbh,$group_name);
	non_exist_check($group_id, 'group name', $group_name);
	
	my $parent_group_id = 0;
	
	if(defined($parent_group_name) && $parent_group_name ne 'All') {
		$parent_group_id = get_group_id($dbh,$parent_group_name);
		exist_check($parent_group_id, 'group name', $parent_group_name);
	}

	$icon = '' unless defined($icon);

	$group_id = pandora_create_group ($group_name, $icon, $parent_group_id, 0, 0, '', 0, $dbh);

	if($group_id == -1) {
		print "[ERROR] A problem has been ocurred creating group '$group_name'\n\n";
	}
	else {
		print "[INFO] Created group '$group_name'\n\n";
	}
}

###############################################################################
# Disable alert system globally
# Related option: --disable_alerts
###############################################################################
sub cli_disable_alerts ($$) {
	my ($conf, $dbh) = @_;

	print "[INFO] Disabling all alerts \n\n";

	# This works by disabling alerts in each defined group
    # If you have previously a group with alert disabled, and you disable 
    # alerts globally, when enabled it again, it will enabled also !

	db_do ($dbh, "UPDATE tgrupo SET disabled = 1");

    exit;
}

###############################################################################
# Enable alert system globally
# Related option: --enable_alerts
###############################################################################
sub cli_enable_alerts ($$) {
	my ($conf, $dbh) = @_;

	print "[INFO] Enabling all alerts \n\n";

	db_do ($dbh, "UPDATE tgrupo SET disabled = 0");

    exit;
}

###############################################################################
# Disable enterprise ACL
# Related option: --disable_eacl
###############################################################################
sub cli_disable_eacl ($$) {
	my ($conf, $dbh) = @_;
			
	print "[INFO] Disabling Enterprise ACL system (system wide)\n\n";

	db_do ($dbh, "UPDATE tconfig SET `value` ='0' WHERE `token` = 'acl_enterprise'");

    exit;
}

###############################################################################
# Enable enterprise ACL
# Related option: --enable_eacl
###############################################################################
sub cli_enable_eacl ($$) {
	my ($conf, $dbh) = @_;

	print "[INFO] Enabling Enterprise ACL system (system wide)\n\n";

    db_do ($dbh, "UPDATE tconfig SET `value` ='1' WHERE `token` = 'acl_enterprise'");
    	
    exit;
}

###############################################################################
# Enable user
# Related option: --enable_user
###############################################################################
sub cli_user_enable () {
	my $user_id = @ARGV[2];

	my $user_disabled = get_user_disabled ($dbh, $user_id);
	
	exist_check($user_disabled,'user',$user_id);

	if($user_disabled == 0) {
		print "[INFO] The user '$user_id' is already enabled. Nothing to do.\n\n";
		exit;
	}
	
	print "[INFO] Enabling user '$user_id'\n\n";

	$user_id = safe_input($user_id);

    db_do ($dbh, "UPDATE tusuario SET `disabled` = '0' WHERE `id_user` = '$user_id'");
    	
    exit;
}

###############################################################################
# Disable user
# Related option: --disable_user
###############################################################################
sub cli_user_disable () {
	my $user_id = @ARGV[2];

	my $user_disabled = get_user_disabled ($dbh, $user_id);
	
	exist_check($user_disabled,'user',$user_id);

	if($user_disabled == 1) {
		print "[INFO] The user '$user_id' is already disabled. Nothing to do.\n\n";
		exit;
	}
	
	print "[INFO] Disabling user '$user_id'\n\n";

	$user_id = safe_input($user_id);
	
    db_do ($dbh, "UPDATE tusuario SET `disabled` = '1' WHERE `id_user` = '$user_id'");
    	
    exit;
}

###############################################################################
# Stop Planned downtime
# Related option: --stop_downtime
###############################################################################
sub cli_stop_downtime () {
	my $downtime_name = @ARGV[2];

	my $downtime_id = pandora_get_planned_downtime_id ($dbh, $downtime_name);
	exist_check($downtime_id,'planned downtime',$downtime_id);
	
	my $current_time = time;
	my $downtime_date_to = get_db_value ($dbh, 'SELECT date_to FROM tplanned_downtime WHERE id=?', $downtime_id);
	
	if($current_time >= $downtime_date_to) {
		print "[INFO] Planned_downtime '$downtime_name' is already stopped\n\n";
		exit;
	}

	print "[INFO] Stopping planned downtime '$downtime_name'\n\n";
		
	my $parameters->{'date_to'} = time;
		
	db_process_update($dbh, 'tplanned_downtime', $parameters, 'id', $downtime_id);
}

###############################################################################
# Get module data
# Related option: --get_module_data
###############################################################################
sub cli_module_get_data () {
	my ($agent_name,$module_name,$interval,$csv_separator) = @ARGV[2..5];
	
	my $agent_id = get_agent_id($dbh,$agent_name);
	exist_check($agent_id, 'agent name', $agent_name);
	
	my $module_id = get_agent_module_id($dbh, $module_name, $agent_id);
	exist_check($module_id, 'module name', $module_name);
	
	if($interval <= 0) {
		print "[ERROR] Interval must be a possitive value\n\n";
	}
	
	$csv_separator = '|' unless defined($csv_separator);

	my $id_agent_module = get_agent_module_id ($dbh, $module_name, $agent_id);
	
	my @data = get_db_rows ($dbh, "SELECT utimestamp, datos 
		FROM tagente_datos 
		WHERE id_agente_modulo = $id_agent_module 
		AND utimestamp > (UNIX_TIMESTAMP(NOW()) - $interval) 
		ORDER BY utimestamp DESC");

	foreach my $data_timestamp (@data) {
		print $data_timestamp->{'utimestamp'};
		print $csv_separator;
		print $data_timestamp->{'datos'};
		print "\n";
	}
	
    exit;
}

##############################################################################
# Return event name given a event id
##############################################################################

sub pandora_get_event_name($$) {
	my ($dbh,$id_event) = @_;
	
	my $event_name = get_db_value($dbh, 'SELECT evento FROM tevento WHERE id_evento = ?',$id_event);
	
	return defined ($event_name) ? $event_name : -1;
}

##############################################################################
# Return user id given a user name
##############################################################################

sub pandora_get_user_id($$) {
	my ($dbh,$user_name) = @_;
	
	my $user_id = get_db_value($dbh, 'SELECT id_user FROM tusuario WHERE id_user = ? or fullname = ?',$user_name, $user_name);
	
	return defined ($user_id) ? $user_id : -1;
}

###############################################################################
###############################################################################
# MAIN
###############################################################################
###############################################################################

sub pandora_manage_main ($$$) {
	my ($conf, $dbh, $history_dbh) = @_;

	my @args = @ARGV;
 	my $ltotal=$#args; 
	my $ax;

 	# Has read setup file ok ?
 	if ( $ltotal == 0 ) {
		print "[ERROR] No valid arguments\n\n";
		help_screen();
		exit;
 	}
	else {
		$param = $args[1];

		# help!
		if ($param =~ m/--*h\w*\z/i ) {
			$param = '';
			help_screen () ;
			exit;
		}
		elsif ($param eq '--disable_alerts') {
			param_check($ltotal, 0);
	        cli_disable_alerts ($conf, $dbh);
	    }
		elsif ($param eq '--enable_alerts') {
			param_check($ltotal, 0);
	        cli_enable_alerts ($conf, $dbh);
		} 
		elsif ($param eq '--disable_eacl') {
			param_check($ltotal, 0);
	        cli_disable_eacl ($conf, $dbh);
		} 
		elsif ($param eq '--enable_eacl') {
			param_check($ltotal, 0);
            pandora_enable_eacl ($conf, $dbh);
		} 
        elsif ($param eq '--disable_group') {
			param_check($ltotal, 1);
			cli_disable_group();
		}
		elsif ($param eq '--enable_group') {
			param_check($ltotal, 1);
			cli_enable_group();
		}
		elsif ($param eq '--create_agent') {
			param_check($ltotal, 7, 3);
			cli_create_agent();
		}
		elsif ($param eq '--delete_agent') {
			param_check($ltotal, 1);
			cli_delete_agent();
		}
		elsif ($param eq '--create_data_module') {
			param_check($ltotal, 17, 14);
			cli_create_data_module(0);
		}
		elsif ($param eq '--create_network_module') {
			param_check($ltotal, 19, 15);
			cli_create_network_module(0);
		}
		elsif ($param eq '--create_snmp_module') {
			param_check($ltotal, 28, 22);
			cli_create_snmp_module(0);
		}
		elsif ($param eq '--create_plugin_module') {
			param_check($ltotal, 23, 14);
			cli_create_plugin_module(0);
		}
		elsif ($param eq '--delete_module') {
			param_check($ltotal, 2);
			cli_delete_module();
		}
		elsif ($param eq '--delete_not_policy_modules') {
			param_check($ltotal, 0);
			cli_delete_not_policy_modules();
		}
		elsif ($param eq '--create_template_module') {
			param_check($ltotal, 3);
			cli_create_template_module();
		}
		elsif ($param eq '--delete_template_module') {
			param_check($ltotal, 3);
			cli_delete_template_module();
		}
		elsif ($param eq '--create_template_action') {
			param_check($ltotal, 6, 2);
			cli_create_template_action();
		}
		elsif ($param eq '--delete_template_action') {
			param_check($ltotal, 4);
			cli_delete_template_action();
		}
		elsif ($param eq '--data_module') {
			param_check($ltotal, 5, 1);
			cli_data_module();
		}
		elsif ($param eq '--create_user') {
			param_check($ltotal, 4, 1);
			cli_create_user();
		}
		elsif ($param eq '--delete_user') {
			param_check($ltotal, 1);
			cli_delete_user();
		}
		elsif ($param eq '--create_profile') {
			param_check($ltotal, 3);
			cli_create_profile();
		}
		elsif ($param eq '--delete_profile') {
			param_check($ltotal, 3);
			cli_delete_profile();
		}
		elsif ($param eq '--create_event') {
			param_check($ltotal, 13, 10);
			cli_create_event();
		}		
		elsif ($param eq '--validate_event') {
			param_check($ltotal, 7, 6);
			cli_validate_event();
		}
		elsif ($param eq '--validate_event_id') {
			param_check($ltotal, 1);
			cli_validate_event_id();
		}
		elsif ($param eq '--get_event_info') {
			param_check($ltotal, 2,1);
			cli_get_event_info();
		}
		elsif ($param eq '--create_incident') {
			param_check($ltotal, 7, 1);
			cli_create_incident();
		}
		elsif ($param eq '--delete_data') {
			param_check($ltotal, 3, 1);
			cli_delete_data($ltotal);
		}
		elsif ($param eq '--apply_policy') {
			param_check($ltotal, 1);
			cli_apply_policy();
		}
		elsif ($param eq '--disable_policy_alerts') {
			param_check($ltotal, 1);
			cli_disable_policy_alerts();
		}
		elsif ($param eq '--create_group') {
			param_check($ltotal, 3, 2);
			cli_create_group();
		}
		elsif ($param eq '--add_agent_to_policy') {
			param_check($ltotal, 2);
			cli_policy_add_agent();
		}
		elsif ($param eq '--enable_user') {
			param_check($ltotal, 1);
			cli_user_enable();
		}
		elsif ($param eq '--disable_user') {
			param_check($ltotal, 1);
			cli_user_disable();
		}
		elsif ($param eq '--update_user') {
			param_check($ltotal, 3);
			cli_user_update();
		}
		elsif ($param eq '--add_profile_to_user') {
			param_check($ltotal, 3, 1);
			cli_user_add_profile();
		}
		elsif ($param eq '--get_module_data') {
			param_check($ltotal, 4, 1);
			cli_module_get_data();
		}
		elsif ($param eq '--create_policy_data_module') {
			param_check($ltotal, 17, 14);
			cli_create_data_module(1);
		}
		elsif ($param eq '--create_policy_network_module') {
			param_check($ltotal, 18, 15);
			cli_create_network_module(1);
		}
		elsif ($param eq '--create_policy_snmp_module') {
			param_check($ltotal, 27, 22);
			cli_create_snmp_module(1);
		}
		elsif ($param eq '--create_policy_plugin_module') {
			param_check($ltotal, 22, 14);
			cli_create_plugin_module(1);
		}
		elsif ($param eq '--create_alert_template') {
			param_check($ltotal, 19, 15);
			cli_create_alert_template();
		}
		elsif ($param eq '--delete_alert_template') {
			param_check($ltotal, 1);
			cli_delete_alert_template();
		}
		elsif ($param eq '--update_alert_template') {
			param_check($ltotal, 3);
			cli_alert_template_update();
		}
		elsif ($param eq '--update_module') {
			param_check($ltotal, 4);
			cli_module_update();
		}
		elsif ($param eq '--exec_from_file') {
			cli_exec_from_file();
		}
		elsif ($param eq '--stop_downtime') {
			cli_stop_downtime();
		}
		elsif ($param eq '--apply_all_policies') {
			param_check($ltotal, 0);
			cli_apply_all_policies();
		}
		elsif ($param eq '--validate_all_alerts') {
			param_check($ltotal, 0);
			cli_validate_all_alerts();
		}
		elsif ($param eq '--validate_policy_alerts') {
			param_check($ltotal, 1);
			cli_validate_policy_alerts();
		}
		elsif ($param eq '--get_agent_group') {
			param_check($ltotal, 1);
			cli_get_agent_group();
		}
		elsif ($param eq '--get_agents_module_current_data') {
			param_check($ltotal, 1);
			cli_get_agents_module_current_data();
		}
		elsif ($param eq '--get_agent_modules') {
			param_check($ltotal, 1);
			cli_get_agent_modules();
		}
		elsif ($param eq '--get_policy_modules') {
			param_check($ltotal, 1);
			cli_get_policy_modules();
		}
		elsif ($param eq '--get_policies') {
			param_check($ltotal, 1, 1);
			cli_get_policies();
		}
		elsif ($param eq '--get_agents') {
			param_check($ltotal, 6, 6);
			cli_get_agents();
		}
		elsif ($param eq '--delete_conf_file') {
			param_check($ltotal, 1);
			cli_delete_conf_file();
		}
		elsif ($param eq '--clean_conf_file') {
			param_check($ltotal, 1, 1);
			cli_clean_conf_file();
		}
		elsif ($param eq '--update_agent') {
			param_check($ltotal, 3);
			cli_agent_update();
		}
		elsif ($param eq '--get_bad_conf_files') {
			param_check($ltotal, 0);
			cli_get_bad_conf_files();
		}
		else {
			print "[ERROR] Invalid option '$param'.\n\n";
			$param = '';
			help_screen ();
			exit;
		}
	}

     print "\n[*] Successful execution. Exiting !\n\n";

    exit;
}
