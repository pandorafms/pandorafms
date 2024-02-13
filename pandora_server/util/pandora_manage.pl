#!/usr/bin/perl

###############################################################################
# Pandora FMS General Management Tool
###############################################################################
# Copyright (c) 2015-2023 Pandora FMS
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
use File::Basename;
use JSON qw(decode_json encode_json);
use MIME::Base64;
use Encode qw(decode encode_utf8);
use LWP::Simple;
use Data::Dumper;

# Default lib dir for RPM and DEB packages
BEGIN { push @INC, '/usr/lib/perl5'; }

use PandoraFMS::Tools;
use PandoraFMS::DB;
use PandoraFMS::Core;
use PandoraFMS::Config;

# To handle 'UTF-8' encoded string in command like arguments (similar to "-CA" option for perl)
use Encode::Locale;
Encode::Locale::decode_argv;

# version: define current version
my $version = "7.0NG.775 Build 240213";

# save program name for logging
my $progname = basename($0);

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
my $enterprise_msg;
if (enterprise_load (\%conf) == 0) {
	$enterprise_msg = "[*] Pandora FMS Enterprise module not available.";
} else {
	$enterprise_msg = "[*] Pandora FMS Enterprise module loaded.";
}

# Connect to the DB
my $dbh = db_connect ($conf{'dbengine'}, $conf{'dbname'}, $conf{'dbhost'}, $conf{'dbport'}, $conf{'dbuser'}, $conf{'dbpass'});
my $history_dbh = ($conf{'_history_db_enabled'} eq '1') ? db_connect ('mysql', $conf{'_history_db_name'},
		$conf{'_history_db_host'}, '3306', $conf{'_history_db_user'}, $conf{'_history_db_pass'}) : undef;

# Read shared config file
pandora_get_sharedconfig (\%conf, $dbh);

my $conf = \%conf;

# Main
pandora_manage_main(\%conf, $dbh, $history_dbh);

# Cleanup and exit
db_disconnect ($history_dbh) if defined ($history_dbh);
db_disconnect ($dbh);
exit;

########################################################################
########################################################################
# GENERAL FUNCTIONS
########################################################################
########################################################################

########################################################################
# Print a help screen and exit.
########################################################################
sub help_screen{
	print "\nPandora FMS CLI $version Copyright (c) 2013-2023 Pandora FMS\n";
	print "This program is Free Software, licensed under the terms of GPL License v2\n";
	print "You can download latest versions and documentation at http://www.pandorafms.org\n\n";
	print "$enterprise_msg\n\n";
	print "Usage: $0 <path to pandora_server.conf> [options] \n\n" unless $param ne '';
	print "Available options by category:\n\n" unless $param ne '';
	print "Available options for $param:\n\n" unless $param eq '';
	print "AGENTS:\n\n" unless $param ne '';
	help_screen_line('--create_agent', "<agent_name> <operating_system> <group> <server_name> \n\t  [<address> <description> <interval> <alias_as_name>]", 'Create agent');
	help_screen_line('--update_agent', '<agent_name> <field_to_change> <new_value> [<use_alias>]', "Update an agent field. The fields can be \n\t  the following: agent_name, address, description, group_name, interval, os_name, disabled (0-1), \n\t  parent_name, cascade_protection (0-1), icon_path, update_gis_data (0-1), custom_id");
	help_screen_line('--delete_agent', '<agent_name> [<use_alias>]', 'Delete agent');
	help_screen_line('--disable_group', '<group_name>', 'Disable agents from an entire group');
	help_screen_line('--enable_group', '<group_name>', 'Enable agents from an entire group');
	help_screen_line('--create_group', '<group_name> [<parent_group_name> <icon> <description>]', 'Create an agent group');
	help_screen_line('--delete_group', '<group_name>', 'Delete an agent group');
	help_screen_line('--update_group', '<group_id>','[<group_name> <parent_group_name> <icon> <description>]', 'Update an agent group');
	help_screen_line('--stop_downtime', '<downtime_name>', 'Stop a planned downtime');
	help_screen_line('--create_downtime', "<downtime_name> <description> <date_from> <date_to> <id_group> <monday> <tuesday>\n\t <wednesday> <thursday> <friday> <saturday> <sunday> <periodically_time_from>\n\t <periodically_time_to> <periodically_day_from> <periodically_day_to> <type_downtime> <type_execution> <type_periodicity> <id_user>", 'Create a planned downtime');
	help_screen_line('--add_item_planned_downtime', "<id_downtime> <id_agente1,id_agente2,id_agente3...id_agenteN> <name_module1,name_module2,name_module3...name_moduleN> ", 'Add a items planned downtime');
	help_screen_line('--get_all_planned_downtimes', '<name> [<id_group> <type_downtime> <type_execution> <type_periodicity>]', 'Get all planned downtime');
	help_screen_line('--get_planned_downtimes_items', '<name> [<id_group> <type_downtime> <type_execution> <type_periodicity>]', 'Get all items of planned downtimes');
	help_screen_line('--set_planned_downtimes_deleted', '<name> ', 'Deleted a planned downtime');
	help_screen_line('--get_module_id', '<agent_id> <module_name>', 'Get the id of an module');
	help_screen_line('--get_module_custom_id', '<agentmodule_id>', 'Get the custom_id of given module');
	help_screen_line('--set_module_custom_id', '<agentmodule_id> [<custom_id>]', 'Set (or erase if empty) the custom_id of given module');
	help_screen_line('--get_agent_group', '<agent_name> [<use_alias>]', 'Get the group name of an agent');
	help_screen_line('--get_agent_group_id', '<agent_name> [<use_alias>]', 'Get the group ID of an agent');
	help_screen_line('--get_agent_modules', '<agent_name> [<use_alias>]', 'Get the modules of an agent');
	help_screen_line('--get_agent_status', '<agent_name> [<use_alias>]', 'Get the status of an agent');
	help_screen_line('--get_agents_id_name_by_alias', '<agent_alias>', '[<strict>]', 'List id and alias of agents mathing given alias');
	help_screen_line('--get_agents', '[<group_name> <os_name> <status> <max_modules> <filter_substring> <policy_name> <use_alias>]', "Get \n\t  list of agents with optative filter parameters");
	help_screen_line('--delete_conf_file', '<agent_name> [<use_alias>]', 'Delete a local conf of a given agent');
	help_screen_line('--clean_conf_file', '<agent_name> [<use_alias>]', "Clean a local conf of a given agent deleting all modules, \n\t  policies, file collections and comments");
	help_screen_line('--get_bad_conf_files', '', 'Get the files bad configured (without essential tokens)');
	help_screen_line('--locate_agent', '<agent_name> [<use_alias>]', 'Search a agent into of nodes of metaconsole. Only Enterprise.');
	help_screen_line('--migration_agent_queue', '<id_node> <source_node_name> <target_node_name> [<db_only>]', 'Migrate agent only metaconsole');
	help_screen_line('--migration_agent', '<id_node> ', 'Is migrating the agent only metaconsole');
	help_screen_line('--apply_module_template', '<id_template> <id_agent>', 'Apply module template to agent');	
	help_screen_line('--new_cluster', '<cluster_name> <cluster_type> <description> <group_id> ', 'Creating a new cluster');
	help_screen_line('--add_cluster_agent', '<json_data:[{"id":5,"id_agent":2},{"id":5,"id_agent":3}]>', 'Adding agent to cluster');
	help_screen_line('--add_cluster_item', '<json_data:[{"name":"Swap_Used","id_cluster":5,"type":"AA","critical_limit":80,"warning_limit":60},{"name":"TCP_Connections","id_cluster":5,"type":"AA","critical_limit":80,"warning_limit":60}]> ', 'Adding item to cluster');
	help_screen_line('--delete_cluster', '<id_cluster>', 'Deleting cluster');
	help_screen_line('--delete_cluster_agent', '<id_agent> <id_cluster>', 'Deleting cluster agent');
	help_screen_line('--delete_cluster_item', '<id_item>', 'Deleting cluster item');
	help_screen_line('--get_cluster_status', '<id_cluster>', 'Getting cluster status');
	help_screen_line('--set_disabled_and_standby', '<id_agent> <id_node> <value>', 'Overwrite and disable and standby status');
	help_screen_line('--reset_agent_counts', '<id_agent>', 'Resets module counts and alert counts in the agents');
	help_screen_line('--agent_update_custom_fields', '<id_agent> <type_field> <field_to_change> <new_value>', "Update an agent custom field. The fields can be \n\t  the following: Serial number, Department ... and types can be 0 text and 1 combo ");

	print "\nMODULES:\n\n" unless $param ne '';
	help_screen_line('--create_data_module', "<module_name> <module_type> <agent_name> [<description> <module_group> \n\t  <min> <max> <post_process> <interval> <warning_min> <warning_max> <critical_min> <critical_max> \n\t <history_data> <definition_file> <warning_str> <critical_str>\n\t  <unknown_events> <ff_threshold> <each_ff> <ff_threshold_normal>\n\t  <ff_threshold_warning> <ff_threshold_critical> <ff_timeout> <warning_inverse> <critical_inverse>\n\t <critical_instructions> <warning_instructions> <unknown_instructions> <use_alias> <ignore_unknown> <warning_time>]", 'Add data server module to agent');
	help_screen_line('--create_web_module', "<module_name> <module_type> <agent_name> [<description> <module_group> \n\t  <min> <max> <post_process> <interval> <warning_min> <warning_max> <critical_min> <critical_max> \n\t <history_data> <retries> <requests> <agent_browser_id> <auth_server> <auth_realm> <definition_file>\n\t <proxy_url> <proxy_auth_login> <proxy_auth_password> <warning_str> <critical_str>\n\t  <unknown_events> <ff_threshold> <each_ff> <ff_threshold_normal>\n\t  <ff_threshold_warning> <ff_threshold_critical> <ff_timeout> <warning_inverse> <critical_inverse>\n\t <critical_instructions> <warning_instructions> <unknown_instructions> <use_alias> <ignore_unknown> <warning_time>].\n\t The valid data types are web_data, web_proc, web_content_data or web_content_string", 'Add web server module to agent');
	help_screen_line('--create_network_module', "<module_name> <module_type> <agent_name> <module_address> \n\t  [<module_port> <description> <module_group> <min> <max> <post_process> <interval> \n\t  <warning_min> <warning_max> <critical_min> <critical_max> <history_data> <ff_threshold>\n\t  <warning_str> <critical_str> <unknown_events> <each_ff>\n\t  <ff_threshold_normal> <ff_threshold_warning> <ff_threshold_critical> <timeout> <retries>\n\t <critical_instructions> <warning_instructions> <unknown_instructions>\n\t <warning_inverse> <critical_inverse> <use_alias> <ignore_unknown> <warning_time>]", 'Add not snmp network module to agent');
	help_screen_line('--create_snmp_module', "<module_name> <module_type> <agent_name> <module_address> <module_port>\n\t  <version> [<community> <oid> <description> <module_group> <min> <max> <post_process> <interval>\n\t   <warning_min> <warning_max> <critical_min> <critical_max> <history_data> \n\t  <snmp3_priv_method> <snmp3_priv_pass> <snmp3_sec_level> <snmp3_auth_method> \n\t  <snmp3_auth_user> <snmp3_auth_pass> <ff_threshold> <warning_str> \n\t  <critical_str> <unknown_events> <each_ff> <ff_threshold_normal>\n\t  <ff_threshold_warning> <ff_threshold_critical> <timeout> <retries> <use_alias> <ignore_unknown>]
	\n\t <critical_instructions> <warning_instructions> <unknown_instructions>\n\t <warning_inverse> <critical_inverse> <warning_time>]", 'Add snmp network module to agent');
	help_screen_line('--create_plugin_module', "<module_name> <module_type> <agent_name> <module_address> \n\t  <module_port> <plugin_name> <user> <password> <parameters> [<description> \n\t  <module_group> <min> <max> <post_process> <interval> <warning_min> <warning_max> <critical_min> \n\t  <critical_max> <history_data> <ff_threshold> <warning_str> <critical_str>\n\t  <unknown_events> <each_ff> <ff_threshold_normal> <ff_threshold_warning>\n\t  <ff_threshold_critical> <timeout> \n\t <critical_instructions> <warning_instructions> <unknown_instructions>\n\t <warning_inverse> <critical_inverse> <use_alias> <ignore_unknown> <warning_time>]", 'Add plug-in module to agent');
    help_screen_line('--get_module_group', '[<module_group_name>]', 'Dysplay all module groups');
    help_screen_line('--create_module_group', '<module_group_name>');
    help_screen_line('--module_group_synch', "<server_name1|server_name2|server_name3...> [<return_type>]", 'Synchronize metaconsole module groups');
	help_screen_line('--delete_module', 'Delete module from agent', '<module_name> <agent_name> [<use_alias>]');
    help_screen_line('--data_module', "<server_name> <agent_name> <module_name> \n\t  <module_type> <module_new_data> [<datetime> <use_alias>]", 'Insert data to module');
    help_screen_line('--get_module_data', "<agent_name> <module_name> <interval> [<csv_separator> <use_alias>]", "\n\t  Show the data of a module in the last X seconds (interval) in CSV format");
    help_screen_line('--delete_data', '-m <module_name> <agent_name> | -a <agent_name> | -g <group_name> [<use_alias>]', "Delete historic \n\t  data of a module, the modules of an agent or the modules of the agents of a group");
	help_screen_line('--update_module', '<module_name> <agent_name> <field_to_change> <new_value> [<use_alias>]', 'Update a module field');
    help_screen_line('--get_agents_module_current_data', '<module_name>', "Get the agent and current data \n\t  of all the modules with a given name");
	help_screen_line('--create_network_module_from_component', '<agent_name> <component_name> [<use_alias>]', "Create a new network \n\t  module from a network component");
	help_screen_line('--create_network_component', "<network_component_name> <network_component_group> <network_component_type> \n\t [<description> <module_interval> <max_value> <min_value> \n\t <snmp_community> <id_module_group> <max_timeout> \n\t <history_data> <min_warning> <max_warning> \n\t <str_warning> <min_critical> <max_critical> \n\t <str_critical> <min_ff_event> <post_process> \n\t <disabled_types_event> <each_ff> <min_ff_event_normal> \n\t <min_ff_event_warning> <min_ff_event_critical>]", "Create a new network component");	
	help_screen_line('--create_synthetic', "<module_name> <synthetic_type> <agent_name> <source_agent1>,<operation>,<source_module1>|<source_agent1>,<source_module1> \n\t [ <operation>,<fixed_value> | <source agent2>,<operation>,<source_module2> <use_alias>]", "Create a new Synthetic module");
	print "\nALERTS:\n\n" unless $param ne '';
    help_screen_line('--create_template_module', '<template_name> <module_name> <agent_name> [<use_alias>]', 'Add alert template to module');
    help_screen_line('--delete_template_module', '<template_name> <module_name> <agent_name> [<use_alias>]', 'Delete alert template from module');
    help_screen_line('--create_template_action', "<action_name> <template_name> <module_name> \n\t  <agent_name> [<fires_min> <fires_max> <use_alias>]', 'Add alert action to module-template");
    help_screen_line('--delete_template_action', "<action_name> <template_name> <module_name> \n\t  <agent_name> [<use_alias>]", 'Delete alert action from module-template');
	help_screen_line('--disable_alerts', '', 'Disable alerts in all groups (system wide)');
	help_screen_line('--enable_alerts', '', 'Enable alerts in all groups (system wide)');
	help_screen_line('--create_alert_template', "<template_name> <condition_type_serialized>\n\t   <time_from> <time_to> [<description> <group_name> <field1> <field2> \n\t  <field3> <priority>  <default_action> <days> <time_threshold> <min_alerts> \n\t  <max_alerts> <alert_recovery> <field2_recovery> <field3_recovery> \n\t  <condition_type_separator>]", 'Create alert template');
	help_screen_line('--delete_alert_template', '<template_name>', 'Delete alert template');
	help_screen_line('--create_alert_command', "<command_name> <comand> [<id_group> <description> \n\t <internal> <fields_descriptions> <fields_values>", 'Create alert command');
	help_screen_line('--get_alert_commands', "[<command_name> <comand> <id_group> <description> \n\t <internal>]", 'Displays all alert commands');
	help_screen_line('--get_alert_actions', '[<action_name> <separator> <return_type>]', 'get all alert actions');
	help_screen_line('--get_alert_actions_meta', '[<server_name> <action_name> <separator> <return_type>]', 'get all alert actions in nodes');
	help_screen_line('--update_alert_template', "<template_name> <field_to_change> \n\t  <new_value>", 'Update a field of an alert template');
	help_screen_line('--validate_all_alerts', '', 'Validate all the alerts');
	help_screen_line('--validate_alert', '<template_name> <agent_id> <module_id> [<use_alias>]', 'Validate alert given angent, module and alert');
	help_screen_line('--create_special_day', "<special_day> <calendar_name> <same_day> <description> <group>", 'Create special day');
	help_screen_line('--delete_special_day', '<special_day>', 'Delete special day');
	help_screen_line('--update_special_day', "<special_day> <field_to_change> <new_value>", 'Update a field of a special day');
	help_screen_line('--create_data_module_from_local_component', '<agent_name> <component_name> [<use_alias>]', "Create a new data \n\t  module from a local component");
	help_screen_line('--create_local_component', "<component_name> <data> [<description> <id_os> <os_version> \n\t  <id_network_component_group> <type> <min> <max> <module_interval> <id_module_group> <history_data> <min_warning> \n\t <max_warning> <str_warning> <min_critical> <max_critical>\n\t  <str_critical> <min_ff_event> <post_process> <unit>\n\t  <wizard_level> <critical_instructions>\n\t  <warning_instructions> <unknown_instructions> <critical_inverse>\n\t  <warning_inverse> <id_category> <disabled_types_event>\n\t  <tags> <min_ff_event_normal> <min_ff_event_warning>\n\t  <min_ff_event_critical> <each_ff> <ff_timeout>]", 'Create local component');
	
	print "\nUSERS:\n\n" unless $param ne '';
	help_screen_line('--create_user', '<user_name> <user_password> <is_admin> [<comments>]', 'Create user');
	help_screen_line('--delete_user', '<user_name>', 'Delete user');
	help_screen_line('--update_user', '<user_id> <field_to_change> <new_value>', "Update a user field. The fields\n\t   can be the following: email, phone, is_admin (0-1), language, id_skin, comments, fullname, password");
	help_screen_line('--enable_user', '<user_id>', 'Enable a given user');
	help_screen_line('--disable_user', '<user_id>', 'Disable a given user');
	help_screen_line('--add_profile', '<user_name> <profile_name> <group_name>', 'Add perfil to user');
	help_screen_line('--delete_profile', '<user_name> <profile_name> <group_name>', 'Delete perfil from user');
	help_screen_line('--add_profile_to_user', '<user_id> <profile_name> [<group_name>]', 'Add a profile in group to a user');
	help_screen_line('--create_profile', "<profile_name> <agent_view>\n\t   <agent_edit> <agent_disable> <alert_edit> <alert_management> <user_management> <db_management>\n\t   <event_view> <event_edit> <event_management> <report_view> <report_edit> <report_management>\n\t   <map_view> <map_edit> <map_management> <vconsole_view> <vconsole_edit> <vconsole_management>\n\t   <pandora_management>", 'Create profile');
	help_screen_line('--update_profile', "<profile_name> <agent_view>\n\t   <agent_edit> <agent_disable> <alert_edit> <alert_management> <user_management> <db_management>\n\t   <event_view> <event_edit> <event_management> <report_view> <report_edit> <report_management>\n\t   <map_view> <map_edit> <map_management> <vconsole_view> <vconsole_edit> <vconsole_management>\n\t   <pandora_management>", 'Modify profile');
	help_screen_line('--disable_eacl', '', 'Disable enterprise ACL system');
	help_screen_line('--enable_eacl', '', 'Enable enterprise ACL system');
	help_screen_line('--disable_double_auth', '<user_name>', 'Disable the double authentication for the specified user');
	print "\nEVENTS:\n\n" unless $param ne '';
	help_screen_line('--create_event', "<event> <event_type> <group_name> [<agent_name> <module_name>\n\t   <event_status> <severity> <template_name> <user_name> <comment> <source> \n\t <id_extra> <tags> <custom_data_json> <force_create_agent> <critical_instructions> \n\t <warning_instructions> <unknown_instructions> <use_alias> <event_custom_id>]", 'Add event');
	help_screen_line('--update_event_custom_id', "<event> <event_custom_id>", 'Update Event Custom ID');
  	help_screen_line('--validate_event', "<agent_name> <module_name> <datetime_min> <datetime_max>\n\t   <user_name> <criticity> <template_name> [<use_alias>]", 'Validate events');
 	help_screen_line('--validate_event_id', '<event_id>', 'Validate event given a event id');
  	help_screen_line('--get_event_info', '<event_id>[<csv_separator>]', 'Show info about a event given a event id');
  	help_screen_line('--add_event_comment', '<event_id> <user_name> <comment>', 'Add event\'s comment');
	print "\nPOLICIES:\n\n" unless $param ne '';
	help_screen_line('--apply_policy', '<id_policy> [<id_agent> <name(boolean)> <id_server>]', 'Force apply a policy in an agent');
	help_screen_line('--apply_all_policies', '', 'Force apply to all the policies');
	help_screen_line('--add_agent_to_policy', '<agent_name> <policy_name> [<use_alias>]', 'Add an agent to a policy');
	help_screen_line('--remove_agent_from_policy', '<policy_id> <agent_id>', 'Delete an agent to a policy');
	help_screen_line('--delete_not_policy_modules', '', 'Delete all modules without policy from configuration file');
	help_screen_line('--disable_policy_alerts', '<policy_name>', 'Disable all the alerts of a policy');
	help_screen_line('--create_policy', '<policy_name> <group_name> <description>');
	help_screen_line('--create_policy_data_module', "<policy_name> <module_name> <module_type> [<description> \n\t  <module_group> <min> <max> <post_process> <interval> <warning_min> <warning_max> \n\t  <critical_min> <critical_max> <history_data> <data_configuration> <warning_str> \n\t  <critical_str> <unknown_events> <ff_threshold> <each_ff>\n\t  <ff_threshold_normal> <ff_threshold_warning> <ff_threshold_critical>\n\t  <ff_timeout> <critical_instructions> <warning_instructions> <unknown_instructions>\n\t <warning_inverse> <critical_inverse> <ignore_unknown>]", 'Add data server module to policy');
	help_screen_line('--create_policy_web_module', "<policy_name> <module_name> <module_type> [<description> \n\t  <module_group> <min> <max> <post_process> <interval> <warning_min> <warning_max> \n\t  <critical_min> <critical_max> <history_data> <retries> <requests> <agent_browser_id> <auth_server> <auth_realm> <data_configuration> <proxy_url> <proxy_auth_login> <proxy_auth_password> <warning_str> \n\t  <critical_str> <unknown_events> <ff_threshold> <each_ff>\n\t  <ff_threshold_normal> <ff_threshold_warning> <ff_threshold_critical>\n\t  <ff_timeout> <warning_inverse> <critical_inverse> <critical_instructions> <warning_instructions> <unknown_instructions> <ignore_unknown>].\n\t The valid data types are web_data, web_proc, web_content_data or web_content_string", 'Add web server module to policy');
	help_screen_line('--create_policy_network_module', "<policy_name> <module_name> <module_type> [<module_port> \n\t  <description> <module_group> <min> <max> <post_process> <interval> \n\t  <warning_min> <warning_max> <critical_min> <critical_max> <history_data> <ff_threshold> \n\t  <warning_str> <critical_str> <unknown_events> <each_ff>\n\t  <ff_threshold_normal> <ff_threshold_warning> <ff_threshold_critical>\n\t <critical_instructions> <warning_instructions> <unknown_instructions>\n\t <warning_inverse> <critical_inverse> <ignore_unknown>]", "Add not snmp network module to policy");
	help_screen_line('--create_policy_snmp_module', "<policy_name> <module_name> <module_type> <module_port> \n\t  <version> [<community> <oid> <description> <module_group> <min> <max> \n\t  <post_process> <interval> <warning_min> <warning_max> <critical_min> <critical_max> <history_data>\n\t   <snmp3_priv_method> <snmp3_priv_pass> <snmp3_sec_level> <snmp3_auth_method> <snmp3_auth_user> \n\t  <snmp3_priv_pass> <ff_threshold> <warning_str> <critical_str>\n\t  <unknown_events> <each_ff> <ff_threshold_normal>\n\t  <ff_threshold_warning> <ff_threshold_critical>\n\t 
	<critical_instructions> <warning_instructions> <unknown_instructions>\n\t <warning_inverse> <critical_inverse> <ignore_unknown>]", 'Add snmp network module to policy');
	help_screen_line('--create_policy_plugin_module', "<policy_name> <module_name> <module_type> \n\t  <module_port> <plugin_name> <user> <password> <parameters> [<description> <module_group> <min> \n\t  <max> <post_process> <interval> <warning_min> <warning_max> <critical_min> <critical_max>\n\t  <history_data> <ff_threshold> <warning_str> <critical_str>\n\t  <unknown_events> <each_ff> <ff_threshold_normal>\n\t  <ff_threshold_warning> <ff_threshold_critical>\n\t <critical_instructions> <warning_instructions> <unknown_instructions>\n\t <warning_inverse> <critical_inverse> <ignore_unknown>]", 'Add plug-in module to policy');
	help_screen_line('--create_policy_data_module_from_local_component', '<policy_name> <component_name>');
	help_screen_line('--add_collection_to_policy', "<policy_name> <collection_name>");
	help_screen_line('--validate_policy_alerts', '<policy_name>', 'Validate the alerts of a given policy');
	help_screen_line('--get_policy_modules', '<policy_name>', 'Get the modules of a policy');
	help_screen_line('--get_policies', '[<agent_name> <use_alias>]', "Get all the policies (without parameters) or \n\tthe policies of a given agent (agent name as parameter)");
	help_screen_line('--recreate_collection', '<collection_id>', 'Recreate the files of a collection');
	
	print "\nNETFLOW:\n\n" unless $param ne '';
	help_screen_line('--create_netflow_filter', "<filter_name> <group_name> <filter> \n\t  <aggregate_by dstip|dstport|none|proto|srcip|srcport> <output_format kilobytes|kilobytespersecond|\n\t  megabytes|megabytespersecond>", "Create a new netflow filter");
	print "\nTOOLS:\n\n" unless $param ne '';
	help_screen_line('--exec_from_file', '<file_path> <option_to_execute> <option_params>', "Execute any CLI option \n\t  with macros from CSV file");
    help_screen_line('--create_snmp_trap', '<name> <oid> <description> <severity>', "Create a new trap definition. \n\tSeverity 0 (Maintenance), 1(Info) , 2 (Normal), 3 (Warning), 4 (Critical), 5 (Minor) and 6 (Major)");
    help_screen_line('--start_snmptrapd', '[no parameters needed]', "Start the snmptrap process or restart if it is running");
    print "\nSETUP:\n\n" unless $param ne '';
	help_screen_line('--set_event_storm_protection', '<value>', "Enable (1) or disable (0) event \n\t  storm protection");
	
    print "\nTAGS\n\n" unless $param ne '';
    help_screen_line('--create_tag', '<tag_name> <tag_description> [<tag_url>] [<tag_email>]', 'Create a new tag');
    help_screen_line('--add_tag_to_user_profile', '<user_id> <tag_name> <group_name> <profile_name>', 'Add a tag to the given user profile');
    help_screen_line('--add_tag_to_module', '<agent_name> <module_name> <tag_name>', 'Add a tag to the given module');

	print "\nVISUAL CONSOLES\n\n" unless $param ne '';
	help_screen_line('--create_visual_console', '<name> <background> <width> <height> <group> <mode> [<position_to_locate_elements>] [<background_color>] [<elements>]', 'Create a new visual console');
	help_screen_line('--edit_visual_console', '<id> [<name>] [<background>] [<width>] [<height>] [<group>] [<mode>] [<position_to_locate_elements>] [<background_color>] [<elements>]', 'Edit a visual console');
	help_screen_line('--delete_visual_console', '<id>', 'Delete a visual console');
	help_screen_line('--delete_visual_console_objects', '<id> <mode> <id_mode>', 'Delete a visual console elements');
	help_screen_line('--duplicate_visual_console', '<id> <times> [<prefix>]', 'Duplicate a visual console');
	help_screen_line('--export_json_visual_console', '<id> [<path>] [<with_element_id>]', 'Creates a json with the visual console elements information');

	print "\nEVENTS\n\n" unless $param ne '';
	help_screen_line('--event_in_progress', '<id_event> ', 'Set event in progress');

	print "\nGIS\n\n" unless $param ne '';
	help_screen_line('--get_gis_agent', '<agent_id> ', 'Gets agent GIS information');
	help_screen_line('--insert_gis_data', '<agent_id> [<latitude>] [<longitude>] [<altitude>]', 'Sets new GIS data for specified agent');


	print "\n";
	exit;
}

########################################################################
# 
########################################################################
sub manage_api_call($$$;$$$$) {
	my ($pa_config, $op, $op2, $id, $id2, $other, $return_type) = @_;
	my $content = undef;

	eval {
		# Set the parameters for the POST request.
		my $params = {};
		$params->{"apipass"} = $pa_config->{"console_api_pass"};
		$params->{"user"} = $pa_config->{"console_user"};
		$params->{"pass"} = $pa_config->{"console_pass"};
		$params->{"op"} = $op;
		$params->{"op2"} = $op2;
		$params->{"id"} = $id;
		$params->{"id2"} = $id2;
		$params->{"other"} = $other;
		$params->{"return_type"} = $return_type;
		$params->{"other_mode"} = "url_encode_separator_|";

		# Call the API.
		my $ua = new LWP::UserAgent;
		my $url = $pa_config->{"console_api_url"};

		my $response = $ua->post($url, $params);

		if ($response->is_success) {
			$content = $response->decoded_content();
		}
		else {
			$content = $response->decoded_content();
		}
	};

	return $content;
}


###############################################################################
# Update token conf file agent
###############################################################################
sub update_conf_txt ($$$$) {
	my ($conf, $agent_name, $token, $value) = @_;

	# Read the conf of each agent.
	my $conf_file_txt = enterprise_hook(
		'read_agent_conf_file',
		[
			$conf,
			$agent_name
		]
	);

	# Check if there is agent conf.
	if(!$conf_file_txt){
		return 0;
	}

	my $updated = 0;
	my $txt_content = "";

	my @lines = split /\n/, $conf_file_txt;

	foreach my $line (@lines) {
		if ($line =~ /^\s*$token\s/ || $line =~ /^#$token\s/ || $line =~ /^#\s$token\s/) {
			$txt_content .= $token.' '.$value."\n";
			$updated = 1;
		} else {
			$txt_content .= $line."\n";
		}
	}

	if ($updated == 0) {
		$txt_content .= "\n$token $value\n";
	}

	# Write the conf.
	my $result = enterprise_hook(
		'write_agent_conf_file',
		[
			$conf,
			$agent_name,
			$txt_content
		]
	);

	return $result;
}

###############################################################################
###############################################################################
# PRINT HELP AND CHECK ERRORS FUNCTIONS
###############################################################################
###############################################################################

###############################################################################
# log wrapper
###############################################################################
sub print_log ($) {
	my ($msg) = @_;

	print $msg;					# show message

	$msg =~ s/\n+$//;
	logger( $conf, "($progname) $msg", 10);		# save to logging file
}

###############################################################################
# Disable a entire group
###############################################################################
sub pandora_disable_group ($$$) {
    my ($conf, $dbh, $group) = @_;

	my @agents_bd = [];
	my $result = 0;

	if(is_metaconsole($conf) != 1 && pandora_get_tconfig_token ($dbh, 'centralized_management', '')) {
		print_log "[ERROR] This node is configured with centralized mode. To disable a group go to metaconsole. \n\n";
		exit;
	}

	if(is_metaconsole($conf) == 1) {
			my $servers = enterprise_hook('get_metaconsole_setup_servers',[$dbh]);
			my @servers_id = split(',',$servers);
			foreach my $server (@servers_id) {
					my $dbh_metaconsole = enterprise_hook('get_node_dbh',[$conf, $server, $dbh]);

					if ($group == 0){
						# Extract all the names of the pandora agents if it is for all = 0.
						@agents_bd = get_db_rows ($dbh_metaconsole, 'SELECT id_agente FROM tagente');
					}
					else {
						# Extract all the names of the pandora agents if it is for group.
						@agents_bd = get_db_rows ($dbh_metaconsole, 'SELECT id_agente FROM tagente WHERE id_grupo = ?', $group);
					}

					foreach my $id_agent (@agents_bd) {
							# Call the API.
							$result += manage_api_call(
								$conf, 'set', 'disabled_and_standby', $id_agent->{'id_agente'}, $server, '1|1' 
							);
					}
			}
	} else {
			if ($group == 0){
				# Extract all the names of the pandora agents if it is for all = 0.
				@agents_bd = get_db_rows ($dbh, 'SELECT nombre FROM tagente');

				# Update bbdd.
				$result = db_update ($dbh, "UPDATE tagente SET disabled = 1");
		}
		else {
				# Extract all the names of the pandora agents if it is for group.
				@agents_bd = get_db_rows ($dbh, 'SELECT nombre FROM tagente WHERE id_grupo = ?', $group);

				# Update bbdd.
				$result = db_update ($dbh, "UPDATE tagente SET disabled = 1 WHERE id_grupo = $group");
		}

		foreach my $name_agent (@agents_bd) {
			# Check the standby field I put it to 0.
			my $new_conf = update_conf_txt(
				$conf,
				$name_agent->{'nombre'},
				'standby',
				'1'
			);
		}
	}

    return $result;
}

###############################################################################
# Enable a entire group
###############################################################################
sub pandora_enable_group ($$$) {
    my ($conf, $dbh, $group) = @_;

	my @agents_bd = [];
	my $result = 0;

	if(is_metaconsole($conf) != 1 && pandora_get_tconfig_token ($dbh, 'centralized_management', '')) {
		print_log "[ERROR] This node is configured with centralized mode. To enable a group go to metaconsole. \n\n";
		exit;
	}

	if ($group == 0){
		# Extract all the names of the pandora agents if it is for all = 0.
		@agents_bd = get_db_rows ($dbh, 'SELECT nombre FROM tagente');

		# Update bbdd.
		$result = db_do ($dbh, "UPDATE tagente SET disabled = 0");
	}
	else {
		# Extract all the names of the pandora agents if it is for group.
		@agents_bd = get_db_rows ($dbh, 'SELECT nombre FROM tagente WHERE id_grupo = ?', $group);

		# Update bbdd.
		$result = db_do ($dbh, "UPDATE tagente SET disabled = 0 WHERE id_grupo = $group");
	}

	foreach my $name_agent (@agents_bd) {
		# Check the standby field I put it to 0.
		my $new_conf = update_conf_txt(
			$conf,
			$name_agent->{'nombre'},
			'standby',
			'0'
		);
	}

    return $result;
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
	
	my $centralized = pandora_get_tconfig_token ($dbh, 'centralized_management', '');

	if(is_metaconsole($conf) != 1 && $centralized) {
		print_log "[ERROR] This node is configured with centralized mode. To create a user go to metaconsole. \n\n";
		exit;
	}
	
  my $query = 'INSERT INTO tusuario_perfil (id_usuario, id_perfil, id_grupo) VALUES (?, ?, ?)';
	my @values = (
		safe_input($user_id),
		$profile_id,
		$group_id
	);

	my $res = db_do ($dbh, $query, @values);

	if(is_metaconsole($conf) == 1 && $centralized) {
		db_synch_insert($dbh, $conf, 'tusuario_perfil', $query, $res, @values);
	}
	
	return $res;
}


##########################################################################
## Create a SNMP trap, given OID name description
##########################################################################

sub cli_create_snmp_trap ($$) {
	my ($conf, $dbh) = @_;

	my ($name, $oid, $description, $severity);

	($name, $oid, $description, $severity) = @ARGV[2..5];

    db_do ($dbh, 'INSERT INTO ttrap_custom_values (`oid`, `text`, `description`, `severity`)
				  VALUES (?, ?, ?, ?)', $oid, $name, $description, $severity);

	print "Creando $name $oid $description $severity \n";
	exit;
}

##########################################################################
## Create a user.
##########################################################################
sub pandora_create_user ($$$$$) {
	my ($dbh, $name, $password, $is_admin, $comments) = @_;

	my $centralized = pandora_get_tconfig_token ($dbh, 'centralized_management', '');

	if(is_metaconsole($conf) != 1 && $centralized) {
		print_log "[ERROR] This node is configured with centralized mode. To create a user go to metaconsole. \n\n";
		exit;
	}
	
  my $query = 'INSERT INTO tusuario (id_user, fullname, password, comments, is_admin) VALUES (?, ?, ?, ?, ?)';
	my @values = (
		safe_input($name),
		safe_input($name),
		$password,
		decode_entities($comments),
		$is_admin ? '1' : '0'
	);

	my $res = db_insert($dbh, 'id_user', $query, @values);

	if(is_metaconsole($conf) == 1 && $centralized) {
		db_synch_insert($dbh, $conf, 'tusuario', $query, $res, @values);
	}
	
	return $res;
}

##########################################################################
## Delete a user.
##########################################################################
sub pandora_delete_user ($$) {
my ($dbh, $name) = @_;

	my $centralized = pandora_get_tconfig_token ($dbh, 'centralized_management', '');

	if(is_metaconsole($conf) != 1 && $centralized) {
		print_log "[ERROR] This node is configured with centralized mode. To delete a user go to metaconsole. \n\n";
		exit;
	}

	# Delete user profiles
	my $result_profile = db_do ($dbh, 'DELETE FROM tusuario_perfil WHERE id_usuario = ?', $name);

	if(is_metaconsole($conf) == 1 && $centralized) {
		db_synch_delete($dbh, $conf, 'tusuario_perfil', $result_profile, $name);
	}

	# Delete the user
	my $return = db_do ($dbh, 'DELETE FROM tusuario WHERE id_user = ?', $name);

	if(is_metaconsole($conf) == 1 && $centralized) {
		db_synch_delete($dbh, $conf, 'tusuario', $return, $name);
	}

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

	my $centralized = pandora_get_tconfig_token ($dbh, 'centralized_management', '');

	if(is_metaconsole($conf) != 1 && $centralized) {
		print_log "[ERROR] This node is configured with centralized mode. To create a user go to metaconsole. \n\n";
		exit;
	}
	
  my $query = 'INSERT INTO tusuario_perfil (id_usuario, id_perfil, id_grupo) VALUES (?, ?, ?)';
	my @values = (
		safe_input($user_id),
		$profile_id,
		$group_id
	);

	my $res = db_insert ($dbh, 'id_up', $query, @values);

	if(is_metaconsole($conf) == 1 && $centralized) {
		db_synch_insert($dbh, $conf, 'tusuario_perfil', $query, $res, @values);
	}
	
	return $res;
}

##########################################################################
## Create profile.
##########################################################################
sub pandora_create_profile ($$$$$$$$$$$$$$$$$$$$$$) {
    my ($dbh, $profile_name, $agent_view,
		$agent_edit, $agent_disable, $alert_edit, $alert_management, $user_management, $db_management,
		$event_view, $event_edit, $event_management, $report_view, $report_edit, $report_management,
		$map_view, $map_edit, $map_management, $vconsole_view, $vconsole_edit, $vconsole_management, $pandora_management) = @_;
	
		my $centralized = pandora_get_tconfig_token ($dbh, 'centralized_management', '');

		if(is_metaconsole($conf) != 1 && $centralized) {
			print_log "[ERROR] This node is configured with centralized mode. To create a user go to metaconsole. \n\n";
			exit;
		}
		
		my $query = 'INSERT INTO tperfil (name,agent_view,agent_edit,agent_disable,alert_edit,alert_management,user_management,db_management,event_view,event_edit,event_management,report_view,report_edit,report_management,map_view,map_edit,map_management,vconsole_view,vconsole_edit,vconsole_management,pandora_management) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);';
		my @values = (
			safe_input($profile_name),
			$agent_view,
			$agent_edit,
			$agent_disable,
			$alert_edit,
			$alert_management,
			$user_management,
			$db_management,
			$event_view,
			$event_edit,
			$event_management,
			$report_view,
			$report_edit,
			$report_management,
			$map_view,
			$map_edit,
			$map_management,
			$vconsole_view,
			$vconsole_edit,
			$vconsole_management,
			$pandora_management
		);

		my $res = db_insert ($dbh, 'id_perfil', $query, @values);

		if(is_metaconsole($conf) == 1 && $centralized) {
			db_synch_insert($dbh, $conf, 'tperfil', $query, $res, @values);
		}
		
		return $res;
}

##########################################################################
#### Update profile.
###########################################################################
sub pandora_update_profile ($$$$$$$$$$$$$$$$$$$$$$) {
	my ($dbh, $profile_name, $agent_view,
		$agent_edit, $agent_disable, $alert_edit, $alert_management, $user_management, $db_management,
		$event_view, $event_edit, $event_management, $report_view, $report_edit, $report_management,
		$map_view, $map_edit, $map_management, $vconsole_view, $vconsole_edit, $vconsole_management, $pandora_management) = @_;

	my $centralized = pandora_get_tconfig_token ($dbh, 'centralized_management', '');

	if(is_metaconsole($conf) != 1 && $centralized) {
		print_log "[ERROR] This node is configured with centralized mode. To create a user go to metaconsole. \n\n";
		exit;
	}

	my @parameters = (
		$agent_view, $agent_edit, $agent_disable,
		$alert_edit, $alert_management,
		$user_management, $db_management,
		$event_view, $event_edit, $event_management,
		$report_view, $report_edit, $report_management,
		$map_view, $map_edit, $map_management,
		$vconsole_view, $vconsole_edit, $vconsole_management,
		$pandora_management, safe_input($profile_name)
	);
	
	my $query = 'UPDATE tperfil SET agent_view = ?, agent_edit = ?, agent_disable = ?, alert_edit = ?, alert_management = ?, user_management = ?, db_management = ?, event_view = ?, event_edit = ?, event_management = ?, report_view = ?, report_edit = ?, report_management = ?, map_view = ?, map_edit = ?, map_management = ?, vconsole_view = ?, vconsole_edit = ?, vconsole_management = ?, pandora_management = ? WHERE name=?;';
	
	my $result = db_update ($dbh, $query, @parameters);

	if(is_metaconsole($conf) == 1 && $centralized) {
		db_synch_update($dbh, $conf, 'tperfil', $query, $result, @parameters);
	}

	return $result;
}

##########################################################################
## Delete a profile from the given user/group.
##########################################################################
sub pandora_delete_user_profile ($$$$) {
	my ($dbh, $user_id, $profile_id, $group_id) = @_;

	my $centralized = pandora_get_tconfig_token ($dbh, 'centralized_management', '');

	if(is_metaconsole($conf) != 1 && $centralized) {
		print_log "[ERROR] This node is configured with centralized mode. To delete a user go to metaconsole. \n\n";
		exit;
	}

	my @parameters = (
		$user_id,
		$profile_id,
		$group_id
	);

	# Delete the user
	my $return = db_do ($dbh, 'DELETE FROM tusuario_perfil WHERE id_usuario=? AND id_perfil=? AND id_grupo=?', @parameters);

	if(is_metaconsole($conf) == 1 && $centralized) {
		db_synch_delete($dbh, $conf, 'tusuario_perfil', $return, @parameters);
	}
	
	return $return;
}

##########################################################################
## Delete a planned downtime
##########################################################################
sub pandora_delete_planned_downtime ($$) {
	my ($dbh, $id_downtime) = @_;
	
	my $execute = get_db_single_row($dbh, 'SELECT executed FROM tplanned_downtime WHERE id = ? ', $id_downtime);
	
	if ( !$execute->{'executed'} ) {
		my $result = db_do ($dbh, 'DELETE FROM tplanned_downtime WHERE id = ? ', $id_downtime);
		
		if ($result) {
			return "This planned downtime is deleted";
		}	
		else {
			return "Problems with this planned downtime";
		}
	}
	else {
		return "The scheduled downtime is still being executed";
	}
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
		my $nd = get_db_value ($dbh, 'SELECT count(id_agente_modulo) FROM tagente_datos WHERE id_agente_modulo=?', $id_module);
		my $ndinc = get_db_value ($dbh, 'SELECT count(id_agente_modulo) FROM tagente_datos_inc WHERE id_agente_modulo=?', $id_module);
		my $ndlog4x = get_db_value ($dbh, 'SELECT count(id_agente_modulo) FROM tagente_datos_log4x WHERE id_agente_modulo=?', $id_module);
		my $ndstring = get_db_value ($dbh, 'SELECT count(id_agente_modulo) FROM tagente_datos_string WHERE id_agente_modulo=?', $id_module);
		
		my $ntot = $nd + $ndinc + $ndlog4x + $ndstring;

		if($ntot == 0) {
			last;
		}
		
		if($nd > 0) {
			db_delete_limit($dbh, 'tagente_datos', 'id_agente_modulo='.$id_module, $buffer);
		}
		
		if($ndinc > 0) {
			db_delete_limit($dbh, 'tagente_datos_inc', 'id_agente_modulo='.$id_module, $buffer);
		}
	
		if($ndlog4x > 0) {
			db_delete_limit($dbh, 'tagente_datos_log4x', 'id_agente_modulo='.$id_module, $buffer);
		}
		
		if($ndstring > 0) {
			db_delete_limit($dbh, 'tagente_datos_string', 'id_agente_modulo='.$id_module, $buffer);
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
	my $centralized = pandora_get_tconfig_token ($dbh, 'centralized_management', '');
	my $result = db_process_update($dbh, 'tusuario', $parameters, {$where_column => $where_value});
	if(is_metaconsole($conf) == 1 && $centralized) {
		my @values = (
			values %$parameters,
			$where_value
		);

		db_synch_update($dbh, $conf, 'tusuario', $dbh->{Statement}, $result, @values);
	}
	
	return $result;
}

##########################################################################
## Update an alert template from hash
##########################################################################
sub pandora_update_alert_template_from_hash ($$$$) {
	my ($parameters, $where_column, $where_value, $dbh) = @_;
	
	my $template_id = db_process_update($dbh, 'talert_templates', $parameters, {$where_column => $where_value});
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
		
	return 'not_init';
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

##########################################################################
## SUB get_all_planned_downtime
## Return the planned downtime id, given "downtime_name"
##########################################################################
sub pandora_get_all_planned_downtime ($$$$$$) {
	my ($dbh, $downtime_name, $id_group, $type_downtime, $type_execution, $type_periodicity) = @_;
	my $sql = "SELECT * FROM tplanned_downtime WHERE  name LIKE '%".safe_input($downtime_name)."%' ";
	my $text_sql = '';
	
	if (defined($id_group) && $id_group ne '') {
		$text_sql .= " id_group = $id_group ";
	}
	if ( defined($type_downtime) && $type_downtime ne '' ) {
		$text_sql .= " type_downtime = $type_downtime ";
	}
	if (defined($type_execution) && $type_execution ne '') {
		$text_sql .= " type_execution = $type_execution ";
	}
	if (defined($type_periodicity) && $type_periodicity ne '') {
		$text_sql .= " type_periodicity = $type_periodicity ";
	}
	
	if ($text_sql eq '') {
		$text_sql = '';
	}
	
	$sql .= $text_sql;
	my @downtimes = get_db_rows ($dbh, $sql);
	
	return @downtimes;
}

##########################################################################
## SUB get_planned_downtimes_items
## Return the planned downtime id, given "downtime_name"
##########################################################################
sub pandora_get_planned_downtimes_items ($$) {
	my ($dbh, $downtime) = @_;
	my $sql = "SELECT * FROM tplanned_downtime_agents WHERE id_downtime = ?";
	my @agents_items = get_db_rows ($dbh, $sql, $downtime->{"id"});
	my @modules_downtime;
	my @return;
	my $text_modules;
	foreach my $agents_item (@agents_items) {
		
		if ( $downtime->{"type_downtime"} eq 'quiet' ) {
			if ( !$agents_item->{'all_modules'} ) {
				$sql = "SELECT id_agent_module FROM tplanned_downtime_modules WHERE id_downtime = ? AND id_agent = ?";
				my @modules_items = get_db_rows ($dbh, $sql, $downtime->{"id"}, $agents_item->{"id_agent"});
				foreach my $modules_item (@modules_items) {
					push(@modules_downtime,$modules_item->{"id_agent_module"});
				}
			}
		}
		
		if ( @modules_downtime != undef ) {
			$text_modules = join(",", @modules_downtime);
			$agents_item->{"modules"} = $text_modules;
			@modules_downtime = undef;
			
		}
		push (@return,$agents_item);
	}
	return @return;
}

##########################################################################
## Create a special day from hash
##########################################################################
sub pandora_create_special_day_from_hash ($$$) {
	my ($pa_config, $parameters, $dbh) = @_;

 	logger($pa_config, "Creating special_day '$parameters->{'date'}'", 10);

	my $template_id = db_process_insert($dbh, 'id', 'talert_special_days', $parameters);

	return $template_id;
}

##########################################################################
## Update a special day from hash
##########################################################################
sub pandora_update_special_day_from_hash ($$$$) {
	my ($parameters, $where_column, $where_value, $dbh) = @_;
	
	my $special_day_id = db_process_update($dbh, 'talert_special_days', $parameters, {$where_column => $where_value});
	return $special_day_id;
}

##########################################################################
## SUB get_special_day_id(id)
## Return the special day id, given "special_day"
##########################################################################
sub pandora_get_special_day_id ($$) {
	my ($dbh, $special_day) = @_;
	
	my $special_day_id = get_db_value ($dbh, "SELECT id FROM talert_special_days WHERE ${RDBMS_QUOTE}date${RDBMS_QUOTE} = ?", $special_day);

	return defined ($special_day_id) ? $special_day_id : -1;
}


##########################################################################
## SUB pandora_get_calendar_id(id)
## Return calendar id, given "calendar_name"
##########################################################################
sub pandora_get_calendar_id ($$) {
	my ($dbh, $calendar_name) = @_;

	my $calendar_id = get_db_value ($dbh, "SELECT id FROM talert_calendar WHERE ${RDBMS_QUOTE}name${RDBMS_QUOTE} = ?", $calendar_name);

	return defined ($calendar_id) ? $calendar_id : -1;
}

##########################################################################
## SUB pandora_get_same_day_id(id)
## Return same day id, given "same_day"
##########################################################################
sub pandora_get_same_day_id ($$) {
	my ($dbh, $same_day) = @_;

	my $weeks = { 'monday' => 1, 'tuesday' => 2, 'wednesday' => 3, 'thursday' => 4, 'friday' => 5, 'saturday' => 6, 'sunday' => 7, 'holiday' => 8 };

	return defined ($weeks->{$same_day}) ? $weeks->{$same_day} : -1;
}

##########################################################################
## Delete a special day.
##########################################################################
sub pandora_delete_special_day ($$) {
	my ($dbh, $date) = @_;

	# Delete the special_day
	my $return = db_do ($dbh, 'DELETE FROM talert_special_days WHERE date = ?', safe_input($date));
        
	if($return eq '0E0') {
		return -1;
	}
	else {
		return 0;
	}
}

###############################################################################
# Print a parameter error and exit the program.
###############################################################################
sub param_error ($$) {
    print (STDERR "[ERROR] Parameters error: $_[1] received | $_[0] necessary.\n\n");
    logger( $conf, "($progname) [ERROR] Parameters error: $_[1] received | $_[0] necessary.", 10);
    
    help_screen ();
    exit 1;
}

###############################################################################
# Print a 'not exists' error and exit the program.
###############################################################################
sub notexists_error ($$) {
    print (STDERR "[ERROR] Error: The $_[0] '$_[1]' not exists.\n\n");
    logger( $conf, "($progname) [ERROR] Error: The $_[0] '$_[1]' not exists.", 10);
    exit 1;
}

###############################################################################
# Print a 'exists' error and exit the program.
###############################################################################
sub exists_error ($$) {
    print (STDERR "[ERROR] Error: The $_[0] '$_[1]' already exists.\n\n");
    logger( $conf, "($progname) [ERROR] Error: The $_[0] '$_[1]' already exists.", 10);
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
	print "\n\t$option $parameters : $help.\n" unless ($param ne '' && $param ne $option);
}

sub check_values($) {
	my ($check) = @_;

	my $arg_cont = 2;
	my $cont = 0;
	my @args = @ARGV;
	my $total = $#args;

	while ($arg_cont <= $total) {
		# Check type.
		if ($check->[$cont]->{'type'} eq 'json') {
			my $json_out = eval { decode_json($args[$arg_cont]) };
			if ($@)
			{
					print "\nValue `$args[$arg_cont]` is an invalid json. \nError:$@\n";
					exit;
			}
		}

		# Check values.
		if (defined($check->[$cont]->{'values'})) {
			if (!(is_in_array($check->[$cont]->{'values'}, $args[$arg_cont]))) {
				print "\nError: value `$args[$arg_cont]` is not valid for $check->[$cont]->{'name'}\n";
				print "\tAvailable options: \t$check->[$cont]->{'values'}->[0]";
				if (defined($check->[$cont]->{'text_extra'}->[0])) {
					print " $check->[$cont]->{'text_extra'}->[0]";
				}
				print "\n";

				my $cont_aux = 1;
				my $while = 'false';
				while ($while eq 'false') {
					if (defined($check->[$cont]->{'values'}->[$cont_aux])) {
						print "\t\t\t\t$check->[$cont]->{'values'}->[$cont_aux]";
						if (defined($check->[$cont]->{'text_extra'}->[$cont_aux])) {
							print " $check->[$cont]->{'text_extra'}->[$cont_aux]";
						}
						print "\n";
					} else {
						exit;
					}
					$cont_aux++;
				}
			}
		}

		$cont++;
		$arg_cont++;
	}
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
		print_log "[INFO] Disabling all groups\n\n";
		$id_group = 0;
	}
	else {
		$id_group = get_group_id($dbh, $group_name);
		exist_check($id_group,'group',$group_name);
		print_log "[INFO] Disabling group '$group_name'\n\n";
	}
	
	my $result = pandora_disable_group ($conf, $dbh, $id_group);

	if ($result != 0){
	print_log "[INFO] Disabled ".$result." agents from group ".$group_name."\n\n";
	} else {
	print_log "[INFO] Disabled 0 agents from group ".$group_name."\n\n";
	}
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
		print_log "[INFO] Enabling all groups\n\n";
	}
	else {
		$id_group = get_group_id($dbh, $group_name);
		exist_check($id_group,'group',$group_name);
		print_log "[INFO] Enabling group '$group_name'\n\n";
	}
	
	pandora_enable_group ($conf, $dbh, $id_group);
}

##############################################################################
# Start snmptrap process.
# Related option: --start_snmptrapd
##############################################################################
sub cli_start_snmptrapd() {
	use PandoraFMS::SNMPServer;
	print_log "[INFO] Starting snmptrap process. \n";
	PandoraFMS::SNMPServer::start_snmptrapd(\%conf);
}

##############################################################################
# Create an agent.
# Related option: --created_agent
##############################################################################

sub cli_create_agent() {
	my ($agent_name,$os_name,$group_name,$server_name,$address,$description,$interval, $alias_as_name) = @ARGV[2..9];
	
	print_log "[INFO] Creating agent '$agent_name'\n\n";
	
	$address = '' unless defined ($address);
	$description = (defined ($description) ? safe_input($description)  : '' );	# safe_input() might be better at pandora_create_agent() (when passing 'description' to db_insert())
	$interval = 300 unless defined ($interval);
	$alias_as_name = 1 unless defined ($alias_as_name);
	my $agent_alias = undef;

	if (!$alias_as_name) {
		$agent_alias = $agent_name;
		$agent_name = generate_agent_name_hash($agent_alias, $conf{'dbhost'});
	}

	my $id_group = get_group_id($dbh,$group_name);
	exist_check($id_group,'group',$group_name);
	my $os_id = get_os_id($dbh,$os_name);
	exist_check($id_group,'operating system',$group_name);
	my $agent_exists = get_agent_id($dbh,$agent_name);
	non_exist_check($agent_exists, 'agent name', $agent_name);
	my $agent_id = pandora_create_agent ($conf, $server_name, $agent_name, $address, $id_group, 0, $os_id, $description, $interval, $dbh,
		undef, undef, undef, undef, undef, undef, undef, undef, $agent_alias);

	# Create address for this agent in taddress.
  if (defined($address)) {
      pandora_add_agent_address($conf, $agent_id, $agent_name, $address, $dbh);
  }
}

##############################################################################
# Delete an agent.
# Related option: --delete_agent
##############################################################################

sub cli_delete_agent() {
	my ($agent_name,$use_alias) = @ARGV[2..3];

	my @id_agents;
	my $id_agent;
	
	$agent_name = decode_entities($agent_name);

	if(is_metaconsole($conf) != 1 and pandora_get_tconfig_token ($dbh, 'centralized_management', '')) {
		print_log "[ERROR] This node is configured with centralized mode. To delete an agent go to metaconsole. \n\n";
		exit;
	}

	if (is_metaconsole($conf) == 1) {
		if (not defined $use_alias) {
			my $agents_groups = enterprise_hook('get_metaconsole_agent',[$dbh, $agent_name]);

			if (scalar(@{$agents_groups}) != 0) {
				foreach my $agent (@{$agents_groups}) {
					my $return = enterprise_hook('delete_metaconsole_agent',[$dbh,$agent->{'id_agente'}]);
					print_log "[INFO] Deleting agent '$agent_name' \n\n";
				}
			}
		}


		my $servers = enterprise_hook('get_metaconsole_setup_servers',[$dbh]);
		my @servers_id = split(',',$servers);
		my @list_servers;
		my $list_names_servers;
		foreach my $server (@servers_id) {
			my $dbh_metaconsole = enterprise_hook('get_node_dbh',[$conf, $server, $dbh]);
			
			my @id_agents;
			my $id_agent;

			if (defined $use_alias and $use_alias eq 'use_alias') {
				@id_agents = get_agent_ids_from_alias($dbh_metaconsole,$agent_name);

				foreach my $id (@id_agents) {

					if ($id->{'id_agente'} == -1) {
						next;
					}
					else {
						print_log "[INFO] Deleting agent '$id->{'nombre'}' in ID server: '$server'\n\n";
						pandora_delete_agent($dbh_metaconsole,$id->{'id_agente'},$conf);
					}
				}
			} else {
				$id_agent = get_agent_id($dbh_metaconsole,$agent_name);

				if ($id_agent == -1) {
					next;
				}
				else {
					print_log "[INFO] Deleting agent '$agent_name' in ID server: '$server'\n\n";
					pandora_delete_agent($dbh_metaconsole,$id_agent,$conf);
				}
			}
		
			
		}
	}
	else {
		my @id_agents;
		my $id_agent;

		if (defined $use_alias and $use_alias eq 'use_alias') {
			@id_agents = get_agent_ids_from_alias($dbh,$agent_name);
		} else {
			$id_agent = get_agent_id($dbh,$agent_name);
			exist_check($id_agent,'agent',$agent_name);
		}

		if (defined $use_alias and $use_alias eq 'use_alias') {
			foreach my $id (@id_agents) {
				exist_check($id->{'id_agente'},'agent',$agent_name);
				print_log "[INFO] Deleting agent '$id->{'nombre'}'\n\n";
				pandora_delete_agent($dbh,$id->{'id_agente'},$conf);
			}
		} else {
			print_log "[INFO] Deleting agent '$agent_name'\n\n";
			pandora_delete_agent($dbh,$id_agent,$conf);
		}
	}
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
		$critical_max, $history_data, $definition_file, $configuration_data, $warning_str, $critical_str, $enable_unknown_events,
	    $ff_threshold, $each_ff, $ff_threshold_normal, $ff_threshold_warning, $ff_threshold_critical, $ff_timeout, 
	    $warning_inverse, $critical_inverse, $critical_instructions, $warning_instructions, $unknown_instructions, $use_alias, $ignore_unknown, $warning_time);
	
	if ($in_policy == 0) {
		($module_name, $module_type, $agent_name, $description, $module_group, 
		$min,$max,$post_process, $interval, $warning_min, $warning_max, $critical_min,
		$critical_max, $history_data, $definition_file, $warning_str, $critical_str, $enable_unknown_events, $ff_threshold,
		$each_ff, $ff_threshold_normal, $ff_threshold_warning, $ff_threshold_critical, $ff_timeout,$warning_inverse, $critical_inverse,  
	    $critical_instructions, $warning_instructions, $unknown_instructions, $use_alias, $ignore_unknown, $warning_time) = @ARGV[2..33];
	}
	else {
		($policy_name, $module_name, $module_type, $description, $module_group, 
		$min,$max,$post_process, $interval, $warning_min, $warning_max, $critical_min,
		$critical_max, $history_data, $configuration_data, $warning_str, $critical_str, $enable_unknown_events, $ff_threshold,
		$each_ff, $ff_threshold_normal, $ff_threshold_warning, $ff_threshold_critical, $ff_timeout, 
	    $warning_inverse, $critical_inverse, $critical_instructions, $warning_instructions, $unknown_instructions, $ignore_unknown, $warning_time) = @ARGV[2..33];
	}
 
	my $module_name_def;
	my $module_type_def;
	
	my $agent_id;
	my @id_agents;
	my $policy_id;
	
	my $disabled_types_event = {};
	if ($enable_unknown_events) {
		$disabled_types_event->{'going_unknown'} = 0;
	}
	else {
		$disabled_types_event->{'going_unknown'} = 1;
	}
	my $disabled_types_event_json = encode_json($disabled_types_event);
	
	if ($in_policy == 0) {
		if (defined $use_alias and $use_alias eq 'use_alias') {
			@id_agents = get_agent_ids_from_alias($dbh,$agent_name);

			foreach my $id (@id_agents) {
				exist_check($id->{'id_agente'},'agent',$agent_name);

				my $module_exists = get_agent_module_id($dbh, $module_name, $id->{'id_agente'});
				non_exist_check($module_exists, 'module name', $module_name);
			}
		} else {
			$agent_id = get_agent_id($dbh,$agent_name);
			exist_check($agent_id,'agent',$agent_name);
			
			my $module_exists = get_agent_module_id($dbh, $module_name, $agent_id);
			non_exist_check($module_exists, 'module name', $module_name);
		}
	}
	else {
		$policy_id = enterprise_hook('get_policy_id',[$dbh, safe_input($policy_name)]);
		exist_check($policy_id,'policy',$policy_name);
		
		my $policy_module_exist = enterprise_hook('get_policy_module_id',[$dbh, $policy_id, $module_name]);
		non_exist_check($policy_module_exist,'policy module',$module_name);
		
		#~ print_log "[INFO] Adding module '$module_name' to policy '$policy_name'\n\n";
	}
	
	$module_name_def = $module_name;
	$module_type_def = $module_type;

	if (defined $use_alias and $use_alias eq 'use_alias') {
		foreach my $id (@id_agents) {
			# If the module is local and is not to policy, we add it to the conf file
			if (defined($definition_file) && (-e $definition_file) && (-e $conf->{incomingdir}.'/conf/'.md5($id->{'nombre'}).'.conf')){
				open (FILE, $definition_file);
				my @file = <FILE>;
				my $definition = join("", @file);
				close (FILE);
				
				# If the parameter name or type and the definition file name or type 
				# dont match will be set the file definitions
				open (FILE, $definition_file);
				while (<FILE>) {
					chomp;
					my ($key, $val) = split / /,2;
					if ($key eq 'module_name') {
						$module_name_def =  $val;
					}
					if ($key eq 'module_type') {
						$module_type_def =  $val;
					}
				}
				close (FILE);
				
				open (FILE, $conf->{incomingdir}.'/conf/'.md5($id->{'nombre'}).'.conf');
				my @file = <FILE>;
				my $conf_file = join("", @file);
				close(FILE);
				
				open FILE, "> ".$conf->{incomingdir}.'/conf/'.md5($id->{'nombre'}).'.conf';
				print FILE "$conf_file\n$definition";
				close(FILE);
				
				enterprise_hook('pandora_update_md5_file', [$conf, $id->{'nombre'}]);
			}
		}
	} else {
		# If the module is local and is not to policy, we add it to the conf file
		if (defined($definition_file) && (-e $definition_file) && (-e $conf->{incomingdir}.'/conf/'.md5($agent_name).'.conf')){
			open (FILE, $definition_file);
			my @file = <FILE>;
			my $definition = join("", @file);
			close (FILE);
			
			# If the parameter name or type and the definition file name or type 
			# dont match will be set the file definitions
			open (FILE, $definition_file);
			while (<FILE>) {
				chomp;
				my ($key, $val) = split / /,2;
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
	}
 
	if ($in_policy == 0) {
		if (defined $use_alias and $use_alias eq 'use_alias') {
			foreach my $id (@id_agents) {
				my $module_exists = get_agent_module_id($dbh, $module_name_def, $id->{'id_agente'});
				non_exist_check($module_exists, 'module name', $module_name_def);
				print_log "[INFO] Adding module '$module_name' to agent '$id->{'nombre'}'\n\n";
			}
		} else {
			my $module_exists = get_agent_module_id($dbh, $module_name_def, $agent_id);
			non_exist_check($module_exists, 'module name', $module_name_def);
			print_log "[INFO] Adding module '$module_name' to agent '$agent_name'\n\n";
		}
	}
	else {
		my $policy_module_exist = enterprise_hook('get_policy_module_id',[$dbh, $policy_id, $module_name_def]);
		non_exist_check($policy_module_exist,'policy module',$module_name_def);
		print_log "[INFO] Adding module '$module_name' to policy '$policy_name'\n\n";
	}
	
	if (defined($definition_file) && $module_type ne $module_type_def) {
		$module_type = $module_type_def;
		print_log "[INFO] The module type has been forced to '$module_type' by the definition file\n\n";
	}
	
	if (defined($definition_file) && $module_name ne $module_name_def) {
		$module_name = $module_name_def;
		print_log "[INFO] The module name has been forced to '$module_name' by the definition file\n\n";
	}
	
	my $module_type_id = get_module_id($dbh,$module_type);
	exist_check($module_type_id,'module type',$module_type);
	
	if ($module_type !~ m/.?generic.?/ && $module_type !~ m/.?async.?/ && $module_type ne 'log4x' && $module_type ne 'keep_alive') {
			print_log "[ERROR] '$module_type' is not valid type for data modules. Try with generic, asyncronous, keep alive or log4x types\n\n";
			exit;
	}
	
	my $module_group_id = 0;
	
	if (defined($module_group)) {
		$module_group_id = get_module_group_id($dbh,$module_group);
		exist_check($module_group_id,'module group',$module_group);
	}
	
	my %parameters;
	
	$parameters{'id_tipo_modulo'} = $module_type_id;

	if ($in_policy == 0) {
		$parameters{'nombre'} = safe_input($module_name);

		if (not defined $use_alias) {
			$parameters{'id_agente'} = $agent_id;
		}
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
	if ($in_policy == 0) {
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
	$parameters{'disabled_types_event'} = $disabled_types_event_json;
	$parameters{'min_ff_event'} = $ff_threshold unless !defined ($ff_threshold);
	$parameters{'each_ff'} = $each_ff unless !defined ($each_ff);
	$parameters{'min_ff_event_normal'} = $ff_threshold_normal unless !defined ($ff_threshold_normal);
	$parameters{'min_ff_event_warning'} = $ff_threshold_warning unless !defined ($ff_threshold_warning);
	$parameters{'min_ff_event_critical'} = $ff_threshold_critical unless !defined ($ff_threshold_critical);
	$parameters{'ff_timeout'} = $ff_timeout unless !defined ($ff_timeout);
	$parameters{'critical_inverse'} = $critical_inverse unless !defined ($critical_inverse);
	$parameters{'warning_inverse'} = $warning_inverse unless !defined ($warning_inverse);
	$parameters{'critical_instructions'} = $critical_instructions unless !defined ($critical_instructions);
	$parameters{'warning_instructions'} = $warning_instructions unless !defined ($warning_instructions);
	$parameters{'unknown_instructions'} = $unknown_instructions unless !defined ($unknown_instructions);
	$parameters{'ignore_unknown'} = $ignore_unknown unless !defined ($ignore_unknown);
	$parameters{'warning_time'} = $warning_time unless !defined ($warning_time);

	if ($in_policy == 0) {
		if (defined $use_alias and $use_alias eq 'use_alias') {
			foreach my $id (@id_agents) {
				$parameters{'id_agente'} = $id->{'id_agente'};
				pandora_create_module_from_hash ($conf, \%parameters, $dbh);
			}
		} else {
			if ($parameters{'id_agente'} eq '') {
		  	$parameters{'id_agente'} = $agent_id;
			}
			pandora_create_module_from_hash ($conf, \%parameters, $dbh);
		}
	}
	else {
		enterprise_hook('pandora_create_policy_module_from_hash', [$conf, \%parameters, $dbh]);
	}
}

##############################################################################
# Create web module.
# Related option: --create_web_module
##############################################################################

sub cli_create_web_module($) {
	my $in_policy = shift;
	my ($policy_name, $module_name, $module_type, $agent_name, $description, $module_group, 
		$min,$max,$post_process, $interval, $warning_min, $warning_max, $critical_min,
		$critical_max, $history_data, $retries, $requests, $agent_browser_id, $auth_server, $auth_realm, 
		$definition_file, $proxy_url, $proxy_auth_login, $proxy_auth_password, $configuration_data, $warning_str, $critical_str, $enable_unknown_events,
	    $ff_threshold, $each_ff, $ff_threshold_normal, $ff_threshold_warning, $ff_threshold_critical, $ff_timeout, 
	    $warning_inverse, $critical_inverse, $critical_instructions, $warning_instructions, $unknown_instructions, $use_alias, $ignore_unknown, $warning_time);
	
	if ($in_policy == 0) {
		($module_name, $module_type, $agent_name, $description, $module_group, 
		$min,$max,$post_process, $interval, $warning_min, $warning_max, $critical_min,
		$critical_max, $history_data, $retries, $requests, $agent_browser_id, $auth_server, $auth_realm, 
		$definition_file, $proxy_url, $proxy_auth_login, $proxy_auth_password, $warning_str, $critical_str, 
		$enable_unknown_events, $ff_threshold, $each_ff, $ff_threshold_normal, $ff_threshold_warning, $ff_threshold_critical, $ff_timeout, 
	    $warning_inverse, $critical_inverse, $critical_instructions, $warning_instructions, $unknown_instructions, $use_alias, $ignore_unknown, $warning_time) = @ARGV[2..41];
	}
	else {
		($policy_name, $module_name, $module_type, $description, $module_group, 
		$min,$max,$post_process, $interval, $warning_min, $warning_max, $critical_min,
		$critical_max, $history_data, $retries, $requests, $agent_browser_id, $auth_server, $auth_realm, $configuration_data, $proxy_url,
		 $proxy_auth_login, $proxy_auth_password, $warning_str, $critical_str, 
		$enable_unknown_events, $ff_threshold, $each_ff, $ff_threshold_normal, $ff_threshold_warning, $ff_threshold_critical, $ff_timeout, 
	    $warning_inverse, $critical_inverse, $critical_instructions, $warning_instructions, $unknown_instructions, $ignore_unknown, $warning_time) = @ARGV[2..40];
	}
	
	my $module_name_def;
	my $module_type_def;
	
	my $agent_id;
	my @id_agents;
	my $policy_id;
	
	my $disabled_types_event = {};
	if ($enable_unknown_events) {
		$disabled_types_event->{'going_unknown'} = 0;
	}
	else {
		$disabled_types_event->{'going_unknown'} = 1;
	}
	my $disabled_types_event_json = encode_json($disabled_types_event);
	
	if ($in_policy == 0) {
		if (defined $use_alias and $use_alias eq 'use_alias') {
			@id_agents = get_agent_ids_from_alias($dbh,$agent_name);

			foreach my $id (@id_agents) {
				exist_check($id->{'id_agente'},'agent',$agent_name);

				my $module_exists = get_agent_module_id($dbh, $module_name, $id->{'id_agente'});
				non_exist_check($module_exists, 'module name', $module_name);
			}
		} else {
			$agent_id = get_agent_id($dbh,$agent_name);
			exist_check($agent_id,'agent',$agent_name);
			
			my $module_exists = get_agent_module_id($dbh, $module_name, $agent_id);
			non_exist_check($module_exists, 'module name', $module_name);
		}
	}
	else {
		$policy_id = enterprise_hook('get_policy_id',[$dbh, safe_input($policy_name)]);
		exist_check($policy_id,'policy',$policy_name);
		
		my $policy_module_exist = enterprise_hook('get_policy_module_id',[$dbh, $policy_id, $module_name]);
		non_exist_check($policy_module_exist,'policy module',$module_name);
		
		#~ print_log "[INFO] Adding module '$module_name' to policy '$policy_name'\n\n";
	}
	
	$module_name_def = $module_name;
	$module_type_def = $module_type;

	# If the module is local and is not to policy, we add it to the conf file
	if (defined $use_alias and $use_alias eq 'use_alias') {
		foreach my $id (@id_agents) {
			if (defined($definition_file) && (-e $definition_file) && (-e $conf->{incomingdir}.'/conf/'.md5($agent_name).'.conf')){
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
				
				#open (FILE, $conf->{incomingdir}.'/conf/'.md5($agent_name).'.conf');
				#my @file = <FILE>;
				#my $conf_file = join("", @file);
				#close(FILE);
				
				#open FILE, "> ".$conf->{incomingdir}.'/conf/'.md5($agent_name).'.conf';
				#print FILE "$conf_file\n$definition";
				#close(FILE);
				
				enterprise_hook('pandora_update_md5_file', [$conf, $agent_name]);
			}
		}
	} else {
		if (defined($definition_file) && (-e $definition_file) && (-e $conf->{incomingdir}.'/conf/'.md5($agent_name).'.conf')){
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
			
			#open (FILE, $conf->{incomingdir}.'/conf/'.md5($agent_name).'.conf');
			#my @file = <FILE>;
			#my $conf_file = join("", @file);
			#close(FILE);
			
			#open FILE, "> ".$conf->{incomingdir}.'/conf/'.md5($agent_name).'.conf';
			#print FILE "$conf_file\n$definition";
			#close(FILE);
			
			enterprise_hook('pandora_update_md5_file', [$conf, $agent_name]);
		}
	}

	if ($in_policy == 0) {
		if (defined $use_alias and $use_alias eq 'use_alias') {
			foreach my $id (@id_agents) {
				my $module_exists = get_agent_module_id($dbh, $module_name_def, $id->{'id_agente'});
				non_exist_check($module_exists, 'module name', $module_name_def);
				print_log "[INFO] Adding module '$module_name' to agent '$id->{'nombre'}'\n\n";
			}
		} else {
			my $module_exists = get_agent_module_id($dbh, $module_name_def, $agent_id);
			non_exist_check($module_exists, 'module name', $module_name_def);
			print_log "[INFO] Adding module '$module_name' to agent '$agent_name'\n\n";
		}
	}
	else {
		my $policy_module_exist = enterprise_hook('get_policy_module_id',[$dbh, $policy_id, $module_name_def]);
		non_exist_check($policy_module_exist,'policy module',$module_name_def);
		print_log "[INFO] Adding module '$module_name' to policy '$policy_name'\n\n";
	}
	
	if (defined($definition_file) && $module_type ne $module_type_def) {
		$module_type = $module_type_def;
		print_log "[INFO] The module type has been forced to '$module_type' by the definition file\n\n";
	}
	
	if (defined($definition_file) && $module_name ne $module_name_def) {
		$module_name = $module_name_def;
		print_log "[INFO] The module name has been forced to '$module_name' by the definition file\n\n";
	}
	
	# The get_module_id has wrong name. Change in future
	my $module_type_id = get_module_id($dbh,$module_type);
	exist_check($module_type_id,'module type',$module_type);
	
	if ($module_type !~ m/.?web.?/) {
			print_log "[ERROR] '$module_type' is not valid type for web modules. Try with web_data, web_proc, web_content_data or web_content_string types\n\n";
			exit;
	}
	
	my $module_group_id = 0;
	
	if (defined($module_group)) {
		$module_group_id = get_module_group_id($dbh,$module_group);
		exist_check($module_group_id,'module group',$module_group);
	}
	
	my %parameters;
	
	$parameters{'id_tipo_modulo'} = $module_type_id;
	
	if ($in_policy == 0) {
		$parameters{'nombre'} = safe_input($module_name);

		if (not defined $use_alias) {
			$parameters{'id_agente'} = $agent_id;
		}
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
	if ($in_policy == 0) {
		$parameters{'descripcion'} = safe_input($description) unless !defined ($description);
		$parameters{'id_modulo'} = 7;	
	}
	else {
		$parameters{'description'} = safe_input($description) unless !defined ($description);
		$parameters{'id_module'} = 7;
		$configuration_data !~ s/\\n/\n/g;
		$parameters{'configuration_data'} = safe_input($configuration_data);	
	}
	$parameters{'min'} = $min unless !defined ($min);
	$parameters{'max'} = $max unless !defined ($max);
	$parameters{'post_process'} = $post_process unless !defined ($post_process);
	$parameters{'module_interval'} = $interval unless !defined ($interval);
	$parameters{'str_warning'}  = safe_input($warning_str)  unless !defined ($warning_str);
	$parameters{'str_critical'} = safe_input($critical_str) unless !defined ($critical_str);
	$parameters{'disabled_types_event'} = $disabled_types_event_json;
	$parameters{'min_ff_event'} = $ff_threshold unless !defined ($ff_threshold);
	$parameters{'each_ff'} = $each_ff unless !defined ($each_ff);
	$parameters{'min_ff_event_normal'} = $ff_threshold_normal unless !defined ($ff_threshold_normal);
	$parameters{'min_ff_event_warning'} = $ff_threshold_warning unless !defined ($ff_threshold_warning);
	$parameters{'min_ff_event_critical'} = $ff_threshold_critical unless !defined ($ff_threshold_critical);
	$parameters{'ff_timeout'} = $ff_timeout unless !defined ($ff_timeout);
	$parameters{'critical_inverse'} = $critical_inverse unless !defined ($critical_inverse);
	$parameters{'warning_inverse'} = $warning_inverse unless !defined ($warning_inverse);
	$parameters{'critical_instructions'} = $critical_instructions unless !defined ($critical_instructions);
	$parameters{'warning_instructions'} = $warning_instructions unless !defined ($warning_instructions);
	$parameters{'unknown_instructions'} = $unknown_instructions unless !defined ($unknown_instructions);
	
	$parameters{'max_retries'} = $retries unless !defined ($retries);
	$parameters{'plugin_pass'} = $requests unless !defined ($requests);
	$parameters{'plugin_user'} = $agent_browser_id unless !defined ($agent_browser_id);
	# $parameters{'http_user'} = $http_auth_login unless !defined ($http_auth_login);
	# $parameters{'http_pass'} = $http_auth_password unless !defined ($http_auth_password);
	$parameters{'snmp_oid'} = defined ($proxy_url) ? $proxy_url : '';
	$parameters{'tcp_send'} = $proxy_auth_login unless !defined ($proxy_auth_login);
	$parameters{'tcp_rcv'} = $proxy_auth_password unless !defined ($proxy_auth_password);
	$parameters{'ip_target'} = $auth_server unless !defined ($auth_server);
	$parameters{'snmp_community'} = $auth_realm unless !defined ($auth_realm);
	$parameters{'ignore_unknown'} = $ignore_unknown unless !defined ($ignore_unknown);
	$parameters{'warning_time'} = $warning_time unless !defined ($warning_time);
	
	
	if ($in_policy == 0) {
		if (defined $use_alias and $use_alias eq 'use_alias') {
			foreach my $id (@id_agents) {
				$parameters{'id_agente'} = $id->{'id_agente'};
				pandora_create_module_from_hash ($conf, \%parameters, $dbh);
			}
		} else {
			if ($parameters{'id_agente'} eq '') {
		  	$parameters{'id_agente'} = $agent_id;
			}
			pandora_create_module_from_hash ($conf, \%parameters, $dbh);
		}
	}
	else {
		enterprise_hook('pandora_create_policy_module_from_hash', [$conf, \%parameters, $dbh]);
	}
	
	#Begin Insert module definition from file_definition in bd
	if (defined($definition_file)){
		
				open(my $fh, '<', $definition_file) or die($!);
				my @lines = <$fh>;
				close ($fh);
		
				my $sql = get_db_value ($dbh, "SELECT MAX(id_agente_modulo) FROM tagente_modulo");
				my $sql2 = "UPDATE tagente_modulo SET plugin_parameter = '".join("",@lines)."' WHERE id_agente_modulo = ".$sql;
				my $create = $dbh->do($sql2);
				if($create){
				print "Success";
				}
				else{
					print "Failure<br/>$DBI::errstr";
				}
			}
		#End Insert module definition from file_definition in bd
		
}

##############################################################################
# Create module group.
# Related option: --create_module_group
##############################################################################
sub cli_create_module_group () {
	my $module_group_name = @ARGV[2];
	
	my $id_module_group = get_module_group_id($dbh, $module_group_name);
	non_exist_check($id_module_group,'group',$module_group_name);
	
	db_insert ($dbh, 'id_mg', 'INSERT INTO tmodule_group (name) VALUES (?)', safe_input($module_group_name));
}

##############################################################################
# Show all the module group (without parameters) or the module groups with a filter parameters
# Related option: --get_module_group
##############################################################################

sub cli_get_module_group() {
	my ($module_group_name) = @ARGV[2..2];

	my $condition = ' 1=1 ';

	if($module_group_name ne '') {
		$condition .= " AND name LIKE '%$module_group_name%' ";
	}

	my @module_group = get_db_rows ($dbh, "SELECT * FROM tmodule_group WHERE $condition");	

	if(scalar(@module_group) == 0) {
		print_log "[INFO] No groups found\n\n";
		exit;
	}

	my $head_print = 0;
	foreach my $groups (@module_group) {

		if($head_print == 0) {
			$head_print = 1;
			print "id_module_group, group_name\n";
		}
		print $groups->{'id_mg'}.",".safe_output($groups->{'name'})."\n";
	}

	if($head_print == 0) {
		print_log "[INFO] No groups found\n\n";
	}

}


sub cli_module_group_synch() {
	my $other = @ARGV[2];
	my $return_type = @ARGV[3];
	if ($return_type eq '') {
		$return_type = 'csv';
	}
	my $result = manage_api_call(\%conf,'set', 'module_group_synch', undef, undef, "$other", $return_type);
	print "$result \n\n ";
}

##############################################################################
# Create network module from component.
# Related option: --create_network_module_from_component
##############################################################################

sub cli_create_network_module_from_component() {
	my ($agent_name, $component_name, $use_alias) = @ARGV[2..4];

	if (defined $use_alias and $use_alias eq 'use_alias') {
		my @id_agents = get_agent_ids_from_alias($dbh,$agent_name);
		my $agent_id;
		my $module_exists;
		my $component;
		my $nc_id;

		foreach my $id (@id_agents) {
			$agent_id = $id->{'id_agente'};
			exist_check($agent_id,'agent',$agent_name);

			$nc_id = pandora_get_network_component_id($dbh, $component_name);
			exist_check($nc_id,'network component',$component_name);

			# Get network component data
			$component = get_db_single_row ($dbh, 'SELECT * FROM tnetwork_component WHERE id_nc = ?', $nc_id);

			my $module_exists = get_agent_module_id($dbh, $component_name, $agent_id);
			non_exist_check($module_exists, 'module name', $component_name);

			print_log "[INFO] Creating module from component '$component_name'\n\n";

			pandora_create_module_from_network_component ($conf, $component, $agent_id, $dbh);
		}
	} else {
			my $nc_id = pandora_get_network_component_id($dbh, $component_name);
			exist_check($nc_id,'network component',$component_name);

			# Get network component data
			my $component = get_db_single_row ($dbh, 'SELECT * FROM tnetwork_component WHERE id_nc = ?', $nc_id);

			my $agent_id = get_agent_id($dbh,$agent_name);
			exist_check($agent_id,'agent',$agent_name);

			my $module_exists = get_agent_module_id($dbh, $component_name, $agent_id);
			non_exist_check($module_exists, 'module name', $component_name);


			print_log "[INFO] Creating module from component '$component_name'\n\n";

			pandora_create_module_from_network_component ($conf, $component, $agent_id, $dbh);
	}
}

##############################################################################
# Create a network component.
# Related option: --create_network_component
##############################################################################
sub cli_create_network_component() {
	my ($c_name, $c_group, $c_type) = @ARGV[2..4];
	my @todo = @ARGV[5..20];
	my $other = join('|', @todo);
	my @todo2 = @ARGV[22..26];
	my $other2 = join('|', @todo2);

	# Call the API.
	my $result = manage_api_call( $conf, 'set', 'new_network_component', $c_name, undef, "$c_type|$other|$c_group|$other2");
	
	print "$result \n\n ";
}

##############################################################################
# Create netflow filter
# Related option: --create_netflow_filter
##############################################################################

sub cli_create_netflow_filter() {
	my ($filter_name, $group_name, $filter, $aggregate_by, $output_format) = @ARGV[2..6];
	
	my $group_id = get_group_id($dbh, $group_name);
	exist_check($group_id,'group',$group_name);
	
	logger($conf, 'Creating netflow filter "' . $filter_name . '"', 10);

	# Create the module
	my $module_id = db_insert ($dbh, 'id_sg', 'INSERT INTO tnetflow_filter (id_name, id_group, advanced_filter, filter_args, aggregate, output)
												VALUES (?, ?, ?, ?, ?, ?)',
												safe_input($filter_name), $group_id, safe_input($filter), 
												'"(' . $filter . ')"', $aggregate_by, $output_format);
}

##############################################################################
# Create network module.
# Related option: --create_network_module
##############################################################################

sub cli_create_network_module($) {
	my $in_policy = shift;
	my ($policy_name, $module_name, $module_type, $agent_name, $module_address, $module_port, $description, 
	$module_group, $min, $max, $post_process, $interval, $warning_min, $warning_max, $critical_min,
	$critical_max, $history_data, $ff_threshold, $warning_str, $critical_str, $enable_unknown_events, $each_ff,
	$ff_threshold_normal, $ff_threshold_warning, $ff_threshold_critical, $timeout, $retries, $critical_instructions, 
	$warning_instructions, $unknown_instructions, $warning_inverse, $critical_inverse, $use_alias, $ignore_unknown, $warning_time);
	
	if ($in_policy == 0) {
		($module_name, $module_type, $agent_name, $module_address, $module_port, $description, 
		$module_group, $min, $max, $post_process, $interval, $warning_min, $warning_max, $critical_min,
		$critical_max, $history_data, $ff_threshold, $warning_str, $critical_str, $enable_unknown_events,
		$each_ff, $ff_threshold_normal, $ff_threshold_warning,
		$ff_threshold_critical, $timeout, $retries,$critical_instructions, $warning_instructions, $unknown_instructions,
		$warning_inverse, $critical_inverse, $use_alias, $ignore_unknown, $warning_time) = @ARGV[2..35];
	}
	else {
		($policy_name, $module_name, $module_type, $module_port, $description, 
		$module_group, $min, $max, $post_process, $interval, $warning_min, $warning_max, $critical_min,
		$critical_max, $history_data, $ff_threshold, $warning_str, $critical_str, $enable_unknown_events,
		$each_ff, $ff_threshold_normal, $ff_threshold_warning,
		$ff_threshold_critical, $timeout, $retries, $critical_instructions, $warning_instructions, $unknown_instructions,
		$warning_inverse, $critical_inverse, $ignore_unknown, $warning_time) = @ARGV[2..35];
	}

	my $module_name_def;
	my $module_type_def;

	my $agent_id;
	my @id_agents;
	my $policy_id;
	
	my $disabled_types_event = {};
	if ($enable_unknown_events) {
		$disabled_types_event->{'going_unknown'} = 0;
	}
	else {
		$disabled_types_event->{'going_unknown'} = 1;
	}
	my $disabled_types_event_json = encode_json($disabled_types_event);
	
	if ($in_policy == 0) {
		if (defined $use_alias and $use_alias eq 'use_alias') {
			@id_agents = get_agent_ids_from_alias($dbh,$agent_name);

			foreach my $id (@id_agents) {
				exist_check($id->{'id_agente'},'agent',$agent_name);

				my $module_exists = get_agent_module_id($dbh, $module_name, $id->{'id_agente'});
				non_exist_check($module_exists, 'module name', $module_name);

				print_log "[INFO] Adding module '$module_name' to agent '$id->{'nombre'}'\n\n";
			}
		} else {
			$agent_id = get_agent_id($dbh,$agent_name);
			exist_check($agent_id,'agent',$agent_name);
			
			my $module_exists = get_agent_module_id($dbh, $module_name, $agent_id);
			non_exist_check($module_exists, 'module name', $module_name);

			print_log "[INFO] Adding module '$module_name' to agent '$agent_name'\n\n";
		}
	}
	else {
		$policy_id = enterprise_hook('get_policy_id',[$dbh, safe_input($policy_name)]);
		exist_check($policy_id,'policy',$policy_name);

		my $policy_module_exist = enterprise_hook('get_policy_module_id',[$dbh, $policy_id, $module_name]);
		non_exist_check($policy_module_exist,'policy module',$module_name);
		
		print_log "[INFO] Adding module '$module_name' to policy '$policy_name'\n\n";
	}

	if ($module_type =~ m/.?snmp.?/) {
		print_log "[ERROR] '$module_type' is not a valid type. For snmp modules use --create_snmp_module parameter\n\n";
		$param = '--create_snmp_module';
		help_screen ();
		exit 1;
	}
	if ($module_type !~ m/.?icmp.?/ && $module_type !~ m/.?tcp.?/) {
			print_log "[ERROR] '$module_type' is not valid type for (not snmp) network modules. Try with icmp or tcp types\n\n";
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
			print_log "[ERROR] Port error. Agents of type distinct of icmp need port\n\n";
			exit;
		}
		if ($module_port > 65535 || $module_port < 1) {
			print_log "[ERROR] Port error. Port must into [1-65535]\n\n";
			exit;
		}
	}
	my %parameters;
	
	$parameters{'id_tipo_modulo'} = $module_type_id;
	
	if ($in_policy == 0) {
		$parameters{'nombre'} = safe_input($module_name);

		if (not defined $use_alias) {
			$parameters{'id_agente'} = $agent_id;
		}

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
	if ($in_policy == 0) {
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
	$parameters{'disabled_types_event'} = $disabled_types_event_json;
	$parameters{'each_ff'} = $each_ff unless !defined ($each_ff);
	$parameters{'min_ff_event_normal'} = $ff_threshold_normal unless !defined ($ff_threshold_normal);
	$parameters{'min_ff_event_warning'} = $ff_threshold_warning unless !defined ($ff_threshold_warning);
	$parameters{'min_ff_event_critical'} = $ff_threshold_critical unless !defined ($ff_threshold_critical);
	$parameters{'max_timeout'} = $timeout unless !defined ($timeout);
	$parameters{'max_retries'} = $retries unless !defined ($retries);
	$parameters{'critical_instructions'} = $critical_instructions unless !defined ($critical_instructions);
	$parameters{'warning_instructions'} = $warning_instructions unless !defined ($warning_instructions);
	$parameters{'unknown_instructions'} = $unknown_instructions unless !defined ($unknown_instructions);
	$parameters{'critical_inverse'} = $critical_inverse unless !defined ($critical_inverse);
	$parameters{'warning_inverse'} = $warning_inverse unless !defined ($warning_inverse);
	$parameters{'ignore_unknown'} = $ignore_unknown unless !defined ($ignore_unknown);
	$parameters{'warning_time'} = $warning_time unless !defined ($warning_time);
	
	if ($in_policy == 0) {
		if (defined $use_alias and $use_alias eq 'use_alias') {
			foreach my $id (@id_agents) {
				$parameters{'id_agente'} = $id->{'id_agente'};
				pandora_create_module_from_hash ($conf, \%parameters, $dbh);
			}
		} else {
			if ($parameters{'id_agente'} eq '') {
		  	$parameters{'id_agente'} = $agent_id;
			}
			pandora_create_module_from_hash ($conf, \%parameters, $dbh);
		}
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
		$snmp3_sec_level, $snmp3_auth_method, $snmp3_auth_user, $snmp3_auth_pass, $ff_threshold, $warning_str, $critical_str, $enable_unknown_events,
	    $each_ff, $ff_threshold_normal, $ff_threshold_warning, $ff_threshold_critical, $timeout, $retries,
		$critical_instructions, $warning_instructions, $unknown_instructions, $warning_inverse, $critical_inverse, $use_alias, $ignore_unknown, $warning_time);
	
	if ($in_policy == 0) {
		($module_name, $module_type, $agent_name, $module_address, $module_port, $version, $community, 
		$oid, $description, $module_group, $min, $max, $post_process, $interval, $warning_min, 
		$warning_max, $critical_min, $critical_max, $history_data, $snmp3_priv_method, $snmp3_priv_pass,
		$snmp3_sec_level, $snmp3_auth_method, $snmp3_auth_user, $snmp3_auth_pass, $ff_threshold, $warning_str, $critical_str, $enable_unknown_events,
		$each_ff, $ff_threshold_normal, $ff_threshold_warning, $ff_threshold_critical, $timeout, $retries,
		$critical_instructions, $warning_instructions, $unknown_instructions, $warning_inverse, $critical_inverse, $use_alias, $ignore_unknown, $warning_time) = @ARGV[2..44];
	}
	else {
		($policy_name, $module_name, $module_type, $module_port, $version, $community, 
		$oid, $description, $module_group, $min, $max, $post_process, $interval, $warning_min, 
		$warning_max, $critical_min, $critical_max, $history_data, $snmp3_priv_method, $snmp3_priv_pass,
		$snmp3_sec_level, $snmp3_auth_method, $snmp3_auth_user, $snmp3_auth_pass, $ff_threshold, $warning_str, $critical_str, $enable_unknown_events,
		$each_ff, $ff_threshold_normal, $ff_threshold_warning, $ff_threshold_critical, $timeout, $retries,
		$critical_instructions, $warning_instructions, $unknown_instructions, $warning_inverse, $critical_inverse, $ignore_unknown, $warning_time) = @ARGV[2..42];
	}
	
	my $module_name_def;
	my $module_type_def;

	my $agent_id;
	my @id_agents;
	my $policy_id;
	
	my $disabled_types_event = {};
	if ($enable_unknown_events) {
		$disabled_types_event->{'going_unknown'} = 0;
	}
	else {
		$disabled_types_event->{'going_unknown'} = 1;
	}
	my $disabled_types_event_json = encode_json($disabled_types_event);
	
	if ($in_policy == 0) {
		if (defined $use_alias and $use_alias eq 'use_alias') {
			@id_agents = get_agent_ids_from_alias($dbh,$agent_name);

			foreach my $id (@id_agents) {
				exist_check($id->{'id_agente'},'agent',$agent_name);

				my $module_exists = get_agent_module_id($dbh, $module_name, $id->{'id_agente'});
				non_exist_check($module_exists, 'module name', $module_name);

				print_log "[INFO] Adding module '$module_name' to agent '$id->{'nombre'}'\n\n";
			}
		} else {
			$agent_id = get_agent_id($dbh,$agent_name);
			exist_check($agent_id,'agent',$agent_name);
			
			my $module_exists = get_agent_module_id($dbh, $module_name, $agent_id);
			non_exist_check($module_exists, 'module name', $module_name);

			print_log "[INFO] Adding module '$module_name' to agent '$agent_name'\n\n";
		}
	}
	else {
		$policy_id = enterprise_hook('get_policy_id',[$dbh, safe_input($policy_name)]);
		exist_check($policy_id,'policy',$policy_name);
	
		my $policy_module_exist = enterprise_hook('get_policy_module_id',[$dbh, $policy_id, $module_name]);
		non_exist_check($policy_module_exist,'policy module',$module_name);
		
		print_log "[INFO] Adding module '$module_name' to policy '$policy_name'\n\n";
	}
	
	# The get_module_id has wrong name. Change in future
	my $module_type_id = get_module_id($dbh,$module_type);
	exist_check($module_type_id,'module type',$module_type);
	
	my $module_group_id = 0;
	
	if (defined($module_group)) {
		$module_group_id = get_module_group_id($dbh,$module_group);
		exist_check($module_group_id,'module group',$module_group);
	}
	
	if ($module_type !~ m/.?snmp.?/) {
		print_log "[ERROR] '$module_type' is not a valid snmp type\n\n";
		exit;
	}
	
	if ($module_port > 65535 || $module_port < 1) {
		print_log "[ERROR] Port error. Port must into [1-65535]\n\n";
		exit;
	}
	
	my %parameters;
	
	$parameters{'id_tipo_modulo'} = $module_type_id;
	
	if ($in_policy == 0) {
		$parameters{'nombre'} = safe_input($module_name);

		if (not defined $use_alias) {
			$parameters{'id_agente'} = $agent_id;
		}

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
	if ($in_policy == 0) {
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
	
	if ($version == 3) {
		$parameters{'custom_string_1'} = $snmp3_priv_method;
		$parameters{'custom_string_2'} = $snmp3_priv_pass;
		$parameters{'custom_string_3'} = $snmp3_sec_level;
		$parameters{'plugin_parameter'} = $snmp3_auth_method;
		$parameters{'plugin_user'} = $snmp3_auth_user; 
		$parameters{'plugin_pass'} = $snmp3_auth_pass;
	}
	
	$parameters{'disabled_types_event'} = $disabled_types_event_json;
	$parameters{'each_ff'} = $each_ff unless !defined ($each_ff);
	$parameters{'min_ff_event_normal'} = $ff_threshold_normal unless !defined ($ff_threshold_normal);
	$parameters{'min_ff_event_warning'} = $ff_threshold_warning unless !defined ($ff_threshold_warning);
	$parameters{'min_ff_event_critical'} = $ff_threshold_critical unless !defined ($ff_threshold_critical);
	$parameters{'max_timeout'} = $timeout unless !defined ($timeout);
	$parameters{'max_retries'} = $retries unless !defined ($retries);
	$parameters{'critical_instructions'} = $critical_instructions unless !defined ($critical_instructions);
	$parameters{'warning_instructions'} = $warning_instructions unless !defined ($warning_instructions);
	$parameters{'unknown_instructions'} = $unknown_instructions unless !defined ($unknown_instructions);
	$parameters{'critical_inverse'} = $critical_inverse unless !defined ($critical_inverse);
	$parameters{'warning_inverse'} = $warning_inverse unless !defined ($warning_inverse);
	$parameters{'ignore_unknown'} = $ignore_unknown unless !defined ($ignore_unknown);
	$parameters{'warning_time'} = $warning_time unless !defined ($warning_time);

	if ($in_policy == 0) {
		if (defined $use_alias and $use_alias eq 'use_alias') {
			foreach my $id (@id_agents) {
				$parameters{'id_agente'} = $id->{'id_agente'};
				pandora_create_module_from_hash ($conf, \%parameters, $dbh);
			}
		} else {
			pandora_create_module_from_hash ($conf, \%parameters, $dbh);
		}
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
		$ff_threshold, $warning_str, $critical_str, $enable_unknown_events,
	    $each_ff, $ff_threshold_normal, $ff_threshold_warning, $ff_threshold_critical, $timeout,
		$critical_instructions, $warning_instructions, $unknown_instructions, $warning_inverse, $critical_inverse, $use_alias, $ignore_unknown, $warning_time);
	
	if ($in_policy == 0) {
		($module_name, $module_type, $agent_name, $module_address, $module_port, $plugin_name,
			$user, $password, $params, $description, $module_group, $min, $max, $post_process, 
			$interval, $warning_min, $warning_max, $critical_min, $critical_max, $history_data, 
			$ff_threshold, $warning_str, $critical_str, $enable_unknown_events,
		$each_ff, $ff_threshold_normal, $ff_threshold_warning, $ff_threshold_critical, $timeout,
		$critical_instructions, $warning_instructions, $unknown_instructions, $warning_inverse, $critical_inverse, $use_alias, $ignore_unknown, $warning_time) = @ARGV[2..38];
	}
	else {
		($policy_name, $module_name, $module_type, $module_port, $plugin_name,
			$user, $password, $params, $description, $module_group, $min, $max, $post_process, 
			$interval, $warning_min, $warning_max, $critical_min, $critical_max, $history_data, 
			$ff_threshold, $warning_str, $critical_str, $enable_unknown_events,
		$each_ff, $ff_threshold_normal, $ff_threshold_warning, $ff_threshold_critical, $timeout,
		$critical_instructions, $warning_instructions, $unknown_instructions, $warning_inverse, $critical_inverse, $ignore_unknown, $warning_time) = @ARGV[2..36];
	}

	my $module_name_def;
	my $module_type_def;

	my $agent_id;
	my @id_agents;
	my $policy_id;
	
	my $disabled_types_event = {};
	if ($enable_unknown_events) {
		$disabled_types_event->{'going_unknown'} = 0;
	}
	else {
		$disabled_types_event->{'going_unknown'} = 1;
	}
	my $disabled_types_event_json = encode_json($disabled_types_event);
	
	if ($in_policy == 0) {
		if (defined $use_alias and $use_alias eq 'use_alias') {
			@id_agents = get_agent_ids_from_alias($dbh,$agent_name);

			foreach my $id (@id_agents) {
				exist_check($id->{'id_agente'},'agent',$agent_name);

				my $module_exists = get_agent_module_id($dbh, $module_name, $id->{'id_agente'});
				non_exist_check($module_exists, 'module name', $module_name);

				print_log "[INFO] Adding module '$module_name' to agent '$id->{'nombre'}'\n\n";
			}
		} else {
			$agent_id = get_agent_id($dbh,$agent_name);
			exist_check($agent_id,'agent',$agent_name);
			
			my $module_exists = get_agent_module_id($dbh, $module_name, $agent_id);
			non_exist_check($module_exists, 'module name', $module_name);

			print_log "[INFO] Adding module '$module_name' to agent '$agent_name'\n\n";
		}
	}
	else {
		$policy_id = enterprise_hook('get_policy_id',[$dbh, safe_input($policy_name)]);
		exist_check($policy_id,'policy',$policy_name);
	
		my $policy_module_exist = enterprise_hook('get_policy_module_id',[$dbh, $policy_id, $module_name]);
		non_exist_check($policy_module_exist,'policy module',$module_name);
		
		print_log "[INFO] Adding module '$module_name' to policy '$policy_name'\n\n";
	}
	
	# The get_module_id has wrong name. Change in future
	my $module_type_id = get_module_id($dbh,$module_type);
	exist_check($module_type_id,'module type',$module_type);
	
	if ($module_type !~ m/.?generic.?/ && $module_type ne 'log4x') {
			print_log "[ERROR] '$module_type' is not valid type for plugin modules. Try with generic or log4x types\n\n";
			exit;
	}
	
	my $module_group_id = get_module_group_id($dbh,$module_group);
	exist_check($module_group_id,'module group',$module_group);
	
	my $plugin_id = get_plugin_id($dbh,$plugin_name);
	exist_check($plugin_id,'plugin',$plugin_name);
	
	if ($module_port > 65535 || $module_port < 1) {
		print_log "[ERROR] Port error. Port must into [1-65535]\n\n";
		exit;
	}
	
	my %parameters;
	
	$parameters{'id_tipo_modulo'} = $module_type_id;
	
	if ($in_policy == 0) {
		$parameters{'nombre'} = safe_input($module_name);

		if (not defined $use_alias) {
			$parameters{'id_agente'} = $agent_id;
		}

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
	#$parameters{'plugin_parameter'} = safe_input($params);

	my @user_params = split(/\s+/, $params);

	my $plug_params = get_db_value ($dbh, 'SELECT macros FROM tplugin WHERE id =?', $plugin_id);

	if ($plug_params eq undef) {
   		print "[ERROR] Error to create module\n\n";
  		help_screen();
	}
	
	if (${RDBMS} eq 'oracle') {
		$plug_params =~ s/\\//g;
	}
	
	my $decode_params = decode_json($plug_params);

	my $user_params_size = scalar(@user_params);

	foreach (my $i=1; $i <= $user_params_size; $i++){
		$decode_params->{$i}->{'value'} = $user_params[$i-1];
	}

	my $p_params = encode_json($decode_params);

	$parameters{'macros'} = $p_params;

	# Optional parameters
	$parameters{'id_module_group'} = $module_group_id unless !defined ($module_group);
	$parameters{'min_warning'} = $warning_min unless !defined ($warning_min);
	$parameters{'max_warning'} = $warning_max unless !defined ($warning_max);
	$parameters{'min_critical'} = $critical_min unless !defined ($critical_min);
	$parameters{'max_critical'} = $critical_max unless !defined ($critical_max);
	$parameters{'history_data'} = $history_data unless !defined ($history_data);
	if ($in_policy == 0) {
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
	
	$parameters{'disabled_types_event'} = $disabled_types_event_json;
	$parameters{'each_ff'} = $each_ff unless !defined ($each_ff);
	$parameters{'min_ff_event_normal'} = $ff_threshold_normal unless !defined ($ff_threshold_normal);
	$parameters{'min_ff_event_warning'} = $ff_threshold_warning unless !defined ($ff_threshold_warning);
	$parameters{'min_ff_event_critical'} = $ff_threshold_critical unless !defined ($ff_threshold_critical);
	$parameters{'max_timeout'} = $timeout unless !defined ($timeout);
	$parameters{'critical_instructions'} = $critical_instructions unless !defined ($critical_instructions);
	$parameters{'warning_instructions'} = $warning_instructions unless !defined ($warning_instructions);
	$parameters{'unknown_instructions'} = $unknown_instructions unless !defined ($unknown_instructions);
	$parameters{'critical_inverse'} = $critical_inverse unless !defined ($critical_inverse);
	$parameters{'warning_inverse'} = $warning_inverse unless !defined ($warning_inverse);
	$parameters{'ignore_unknown'} = $ignore_unknown unless !defined ($ignore_unknown);
	$parameters{'warning_time'} = $warning_time unless !defined ($warning_time);

	if ($in_policy == 0) {
		if (defined $use_alias and $use_alias eq 'use_alias') {
			foreach my $id (@id_agents) {
				$parameters{'id_agente'} = $id->{'id_agente'};
				pandora_create_module_from_hash ($conf, \%parameters, $dbh);
			}
		} else {
			pandora_create_module_from_hash ($conf, \%parameters, $dbh);
		}
	}
	else {
		enterprise_hook('pandora_create_policy_module_from_hash', [$conf, \%parameters, $dbh]);
	}
}

##############################################################################
# Delete module.
# Related option: --delete_module
##############################################################################

sub cli_delete_module() {
	my ($module_name,$agent_name, $use_alias) = @ARGV[2..4];
	
	my @id_agents;

	if (defined $use_alias and $use_alias eq 'use_alias') {
		@id_agents = get_agent_ids_from_alias($dbh,$agent_name);
		my $id_agent;

		foreach my $id (@id_agents) {
			print_log "[INFO] Deleting module '$module_name' from agent '$id->{'nombre'}' \n\n";

			$id_agent = $id->{'id_agente'};
			exist_check($id_agent,'agent',$agent_name);
			my $id_module = get_agent_module_id($dbh,$module_name,$id_agent);
			if ($id_module == -1) {
				next;
			}
		
			pandora_delete_module($dbh, $id_module, $conf, 1);
		}
	} else {
		print_log "[INFO] Deleting module '$module_name' from agent '$agent_name' \n\n";

		my $id_agent = get_agent_id($dbh,$agent_name);
		exist_check($id_agent,'agent',$agent_name);
		my $id_module = get_agent_module_id($dbh,$module_name,$id_agent);
		exist_check($id_module,'module',$module_name);
		
		pandora_delete_module($dbh, $id_module, $conf, 1);
	}
}

##############################################################################
# Delete not policy modules.
# Related option: --delete_not_policy_modules
##############################################################################

sub cli_delete_not_policy_modules() {
	my $incomingdir;
	my $incomingdirmd5;

	$incomingdir = $conf->{incomingdir}.'/conf/';
	$incomingdirmd5 = $conf->{incomingdir}.'/md5/';

	# Open the folder
	opendir FOLDER, $incomingdir || die "[ERROR] Opening incoming directory";
	
	# Store the list of files
	my @files = readdir(FOLDER);
	my $file;
	my $filemd5;
	
	print_log "[INFO] Deleting modules without policy from conf files \n\n";
	foreach $file (@files)
	{
		if($file ne '.' && $file ne '..') {
			# Creates md5 filename of agent
			$filemd5 = $file;
			$filemd5 =~ s/\.conf/\.md5/g;

			my $ret = enterprise_hook('pandora_delete_not_policy_modules', [$incomingdir.$file, $incomingdirmd5.$filemd5]);
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
	my ($template_name,$module_name,$agent_name, $use_alias) = @ARGV[2..5];
	
	my @id_agents;

	if (defined $use_alias and $use_alias eq 'use_alias') {
		@id_agents = get_agent_ids_from_alias($dbh,$agent_name);

		foreach my $id (@id_agents) {
			print_log "[INFO] Adding template '$template_name' to module '$module_name' from agent '$agent_name' \n\n";
			
			my $id_agent = $id->{'id_agente'};
			exist_check($id_agent,'agent',$agent_name);
			my $module_id = get_agent_module_id($dbh,$module_name,$id_agent);
			if ($module_id == -1) {
				print_log "[ERROR] Error: The module '$module_name' does not exist. \n\n";
				next;
			}

			my $template_id = get_template_id($dbh,$template_name);
			exist_check($template_id,'template',$template_name);
			
			pandora_create_template_module ($conf, $dbh, $module_id, $template_id);
		}
	} else {
			print_log "[INFO] Adding template '$template_name' to module '$module_name' from agent '$agent_name' \n\n";
			
			my $id_agent = get_agent_id($dbh,$agent_name);
			exist_check($id_agent,'agent',$agent_name);
			my $module_id = get_agent_module_id($dbh,$module_name,$id_agent);
			exist_check($module_id,'module',$module_name);
			my $template_id = get_template_id($dbh,$template_name);
			exist_check($template_id,'template',$template_name);
			
			pandora_create_template_module ($conf, $dbh, $module_id, $template_id);
	}
}

##############################################################################
# Delete template module.
# Related option: --delete_template_module
##############################################################################

sub cli_delete_template_module() {
	my ($template_name,$module_name,$agent_name, $use_alias) = @ARGV[2..5];

	if (defined $use_alias and $use_alias eq 'use_alias') {
		my @id_agents = get_agent_ids_from_alias($dbh,$agent_name);

		my $id_agent;

		foreach my $id (@id_agents) {
			print_log "[INFO] Delete template '$template_name' from module '$module_name' from agent '$agent_name' \n\n";

			$id_agent = $id->{'id_agente'};
			exist_check($id_agent,'agent',$agent_name);
			my $module_id = get_agent_module_id($dbh,$module_name,$id_agent);
			if ($module_id eq -1) {
				print_log "[ERROR] Error: The module '$module_name' does not exist. \n\n";
				next;
			}
			my $template_id = get_template_id($dbh,$template_name);
			exist_check($template_id,'template',$template_name);

			my $template_module_id = get_template_module_id($dbh, $module_id, $template_id);
			exist_check($template_module_id,"template '$template_name' on module",$module_name);
		
			pandora_delete_template_module ($dbh, $template_module_id);
		}
	} else {
		print_log "[INFO] Delete template '$template_name' from module '$module_name' from agent '$agent_name' \n\n";

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
}

##############################################################################
# Create template action.
# Related option: --create_template_action
##############################################################################

sub cli_create_template_action() {
	my ($action_name,$template_name,$module_name,$agent_name,$fires_min,$fires_max, $use_alias) = @ARGV[2..8];
	
	my @id_agents;

	if (defined $use_alias and $use_alias eq 'use_alias') {
		@id_agents = get_agent_ids_from_alias($dbh,$agent_name);

		foreach my $id (@id_agents) {
			print_log "[INFO] Adding action '$action_name' to template '$template_name' in module '$module_name' from agent '$agent_name' with $fires_min min. fires and $fires_max max. fires\n\n";
			
			my $id_agent = $id->{'id_agente'};
			exist_check($id_agent,'agent',$agent_name);
			my $module_id = get_agent_module_id($dbh,$module_name,$id_agent);
			if ($module_id eq -1) {
				print_log "[ERROR] Error: The module '$module_name' does not exist. \n\n";
				next;
			}
			my $template_id = get_template_id($dbh,$template_name);
			exist_check($template_id,'template',$template_name);
			my $template_module_id = get_template_module_id($dbh,$module_id,$template_id);
			exist_check($template_module_id,'template module',$template_name);
			my $action_id = get_action_id($dbh,safe_input($action_name));
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
	} else {
		print_log "[INFO] Adding action '$action_name' to template '$template_name' in module '$module_name' from agent '$agent_name' with $fires_min min. fires and $fires_max max. fires\n\n";

		my $id_agent = get_agent_id($dbh,$agent_name);
		exist_check($id_agent,'agent',$agent_name);
		my $module_id = get_agent_module_id($dbh,$module_name,$id_agent);
		exist_check($module_id,'module',$module_name);
		my $template_id = get_template_id($dbh,$template_name);
		exist_check($template_id,'template',$template_name);
		my $template_module_id = get_template_module_id($dbh,$module_id,$template_id);
		exist_check($template_module_id,'template module',$template_name);
		my $action_id = get_action_id($dbh,safe_input($action_name));
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
}

##############################################################################
# Delete template action.
# Related option: --delete_template_action
##############################################################################

sub cli_delete_template_action() {
	my ($action_name,$template_name,$module_name,$agent_name, $use_alias) = @ARGV[2..6];
	
	if (defined $use_alias and $use_alias eq 'use_alias') {
		my @id_agents = get_agent_ids_from_alias($dbh,$agent_name);

		foreach my $id (@id_agents) {
			print_log "[INFO] Deleting action '$action_name' from template '$template_name' in module '$module_name' from agent '$agent_name')\n\n";

			my $id_agent = $id->{'id_agente'};
			exist_check($id_agent,'agent',$agent_name);
			my $module_id = get_agent_module_id($dbh,$module_name,$id_agent);
			if ($module_id eq -1) {
				print_log "[ERROR] Error: The module '$module_name' does not exist. \n\n";
				next;
			}
			my $template_id = get_template_id($dbh,$template_name);
			exist_check($template_id,'template',$template_name);
			my $template_module_id = get_template_module_id($dbh,$module_id,$template_id);
			exist_check($template_module_id,'template module',$template_name);
			my $action_id = get_action_id($dbh,safe_input($action_name));
			exist_check($action_id,'action',$action_name);

			pandora_delete_template_module_action ($dbh, $template_module_id, $action_id);
		}
	} else {
		print_log "[INFO] Deleting action '$action_name' from template '$template_name' in module '$module_name' from agent '$agent_name')\n\n";

		my $id_agent = get_agent_id($dbh,$agent_name);
		exist_check($id_agent,'agent',$agent_name);
		my $module_id = get_agent_module_id($dbh,$module_name,$id_agent);
		exist_check($module_id,'module',$module_name);
		my $template_id = get_template_id($dbh,$template_name);
		exist_check($template_id,'template',$template_name);
		my $template_module_id = get_template_module_id($dbh,$module_id,$template_id);
		exist_check($template_module_id,'template module',$template_name);
		my $action_id = get_action_id($dbh,safe_input($action_name));
		exist_check($action_id,'action',$action_name);

		pandora_delete_template_module_action ($dbh, $template_module_id, $action_id);
	}
}

##############################################################################
# Insert data to module.
# Related option: --data_module
##############################################################################

sub cli_data_module() {
	my ($server_name,$agent_name,$module_name,$module_type,$module_new_data,$datetime,$use_alias) = @ARGV[2..8];
	my $utimestamp;
	
	my @id_agents;

	if(defined($datetime)) {
		if ($datetime !~ /([0-9]{2,4})\-([0-1][0-9])\-([0-3][0-9]) +([0-2][0-9]):([0-5][0-9])/) {
			print_log "[ERROR] Invalid datetime $datetime. (Correct format: YYYY-MM-DD HH:mm)\n";
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

	# Server_type 0 is dataserver
	my $server_id = get_server_id($dbh,$server_name,0);
	exist_check($server_id,'data server',$server_name);

	if (defined $use_alias and $use_alias eq 'use_alias') {
		@id_agents = get_agent_ids_from_alias($dbh,$agent_name);

		my $id_agent;

		foreach my $id (@id_agents) {
			$id_agent = $id->{'id_agente'};
			exist_check($id_agent,'agent',$agent_name);
			
			my $id_module = get_agent_module_id($dbh, $module_name, $id_agent);
			if ($id_module == -1) {
				next;
			}

			my $module = get_db_single_row ($dbh, 'SELECT * FROM tagente_modulo WHERE id_agente_modulo = ? AND id_tipo_modulo = ?', $id_module, $module_type_id);
			
			if(not defined($module->{'module_interval'})) {
				print_log "[ERROR] No module found with this type. \n\n";
				exit;
			}
			
			my %data = ('data' => $module_new_data);
			
			pandora_process_module ($conf, \%data, '', $module, $module_type, '', $utimestamp, $server_id, $dbh);
			
			print_log "[INFO] Inserting data to module '$module_name'\n\n";
		}
	} else {
		my $id_agent = get_agent_id($dbh,$agent_name);
		exist_check($id_agent,'agent',$agent_name);
		
		my $id_module = get_agent_module_id($dbh, $module_name, $id_agent);
		exist_check($id_module, 'module name', $module_name);

		my $module = get_db_single_row ($dbh, 'SELECT * FROM tagente_modulo WHERE id_agente_modulo = ? AND id_tipo_modulo = ?', $id_module, $module_type_id);
		
		if(not defined($module->{'module_interval'})) {
			print_log "[ERROR] No module found with this type. \n\n";
			exit;
		}
		
		my %data = ('data' => $module_new_data);
		
		pandora_process_module ($conf, \%data, '', $module, $module_type, '', $utimestamp, $server_id, $dbh);
		
		print_log "[INFO] Inserting data to module '$module_name'\n\n";
	}
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
	
	print_log "[INFO] Creating user '$user_name'\n\n";
	
	pandora_create_user ($dbh, $user_name, md5($password), $is_admin, $comments);
}

##############################################################################
# Update a user.
# Related option: --update_user
##############################################################################

sub cli_user_update() {
	my ($user_id,$field,$new_value) = @ARGV[2..4];

	if(is_metaconsole($conf) != 1 && pandora_get_tconfig_token ($dbh, 'centralized_management', '')) {
		print_log "[ERROR] This node is configured with centralized mode. To update a user go to metaconsole. \n\n";
		exit;
	}

	my $user_exists = get_user_exists ($dbh, $user_id);
	exist_check($user_exists,'user',$user_id);

	if($field eq 'email' || $field eq 'phone' || $field eq 'is_admin' || $field eq 'language' || $field eq 'id_skin') {
		# Fields admited, no changes
	}
	elsif($field eq 'comments' || $field eq 'fullname') {
		$new_value = safe_input($new_value);
	}
	elsif($field eq 'password') {
		if($new_value eq '') {
			print_log "[ERROR] Field '$field' cannot be empty\n\n";
			exit;
		}
		
		$new_value = md5($new_value);
	}
	else {
		print_log "[ERROR] Field '$field' doesn't exist\n\n";
		exit;
	}
		
	print_log "[INFO] Updating field '$field' in user '$user_id'\n\n";
	
	my $update;
	
	$update->{$field} = $new_value;
	
	pandora_update_user_from_hash ($update, 'id_user', safe_input($user_id), $dbh);
}


##############################################################################
# Update an agent customs field.
# Related option: --agent_update_custom_fields
##############################################################################

sub cli_agent_update_custom_fields() {
	my ($id_agent,$type,$field,$new_value) = @ARGV[2..5];

	my $agent_name = get_agent_name($dbh, $id_agent);

	my $id_field;

	my $found = 0;

	if($agent_name eq '') {
		print_log "[ERROR] Agent '$id_agent' doesn't exist\n\n";
		print "--agent_update_custom_fields, <id_agent> <type_field> <field_to_change> <new_value>, Updates an agent custom field. The fields can be \n\t  the following: Serial number, Department ... and types can be 0 text and 1 combo )\n\n";
		exit;
	}

	# Department, Serial number ...
	my $custom_field = pandora_select_id_custom_field ($dbh, $field);


	if($custom_field eq '') {
			print_log "[ERROR] Field '$field' doesn't exist\n\n";
			print "--agent_update_custom_fields, <id_agent> <type_field> <field_to_change> <new_value>, Updates an agent custom field. The fields can be \n\t  the following: Serial number, Department ... and types can be 0 text and 1 combo )\n\n";
			exit;
	}

	if($type == 1) {
		my $exist_option = pandora_select_combo_custom_field ($dbh, $custom_field);

		my @fields = split(',',$exist_option);
		foreach my $combo (@fields) {
			if($combo eq safe_input($new_value)) {
				$found = 1;
			}
		}
		if($found == 0) {
			print_log "\n[ERROR] Field '$new_value' doesn't match with combo option values\n\n";
			exit
		}
	}

	print_log "\n[INFO] Updating field '$field' in agent with ID '$id_agent'\n\n";

	my $result = 	pandora_agent_update_custom_field ($dbh, $new_value, $custom_field, $id_agent);

	if($result == "0E0"){
			print_log "[ERROR] Error updating field '$field'\n\n";
	} else {
			print_log "[INFO] Field '$field' updated successfully!\n\n";
	}

	exit;
	
}

##############################################################################
# Update an agent field.
# Related option: --update_agent
##############################################################################

sub cli_agent_update() {
	my ($agent_name,$field,$new_value,$use_alias) = @ARGV[2..5];

	my @id_agents;
	my $id_agent;

	if (defined $use_alias and $use_alias eq 'use_alias') {
		@id_agents = get_agent_ids_from_alias($dbh,$agent_name);
		foreach my $id (@id_agents) {
			exist_check($id->{'id_agente'},'agent',$agent_name);
		}
	} else {
		$id_agent = get_agent_id($dbh,$agent_name);
		exist_check($id_agent,'agent',$agent_name);
	}
	
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
		
		# If the addres doesn't exist, we add it to the addresses list
		if($address_id == -1) {
			$address_id = add_address($dbh,$new_value);
		}
		
		# Add the address to the agent
		if (defined $use_alias and $use_alias eq 'use_alias') {
			foreach my $id (@id_agents) {
				my $ag_addr_id = get_agent_addr_id($dbh, $address_id, $id->{'id_agente'});
				if($ag_addr_id == -1) {
					add_new_address_agent ($dbh, $address_id, $id->{'id_agente'});
				}
			}
		} else {
				my $ag_addr_id = get_agent_addr_id($dbh, $address_id, $id_agent);
				if($ag_addr_id == -1) {
					add_new_address_agent ($dbh, $address_id, $id_agent);
				}
		}
		
		$field = 'direccion';
	}
	else {
		print_log "[ERROR] Field '$field' doesn't exist\n\n";
		exit;
	}
	
	if (defined $use_alias and $use_alias eq 'use_alias') {
		print_log "[INFO] Updating field '$field' in agents with alias '$agent_name'\n\n";
	} else {
		print_log "[INFO] Updating field '$field' in agent '$agent_name'\n\n";
	}
	
	my $update;
	
	$update->{$field} = $new_value;

	if (defined $use_alias and $use_alias eq 'use_alias') {
		foreach my $id (@id_agents) {
			pandora_update_table_from_hash ($conf, $update, 'id_agente', safe_input($id->{'id_agente'}), 'tagente', $dbh);
		}
	} else {
		pandora_update_table_from_hash ($conf, $update, 'id_agente', safe_input($id_agent), 'tagente', $dbh);
	}

	enterprise_hook('update_agent_cache', [$conf, $dbh, $id_agent]) if ($conf->{'node_metaconsole'} == 1);
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
			print_log "[ERROR] Field '$field' is out of interval (0-4)\n\n";
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
		print_log "[ERROR] Field '$field' doesn't exist\n\n";
		exit;
	}
		
	print_log "[INFO] Updating field '$field' in alert template '$template_name'\n\n";
	
	my $update;
	
	$update->{$field} = $new_value;
	
	pandora_update_alert_template_from_hash ($update, 'id', $template_id, $dbh);
}

##############################################################################
# Check the specific fields of data module when update
##############################################################################

sub pandora_check_data_module_fields($) {
	my $field_value = shift;

	if($field_value->{'field'} eq 'ff_timeout') {
		$field_value->{'field'} = 'ff_timeout';
	}
	else {
		print_log "[ERROR] The field '".$field_value->{'field'}."' is not available for data modules\n\n";
		exit;
	}

}

##############################################################################
# Check the specific fields of network module when update
##############################################################################

sub pandora_check_network_module_fields($) {
	my $field_value = shift;
		
	if($field_value->{'field'} eq 'module_address') {
		my $agent_name = @ARGV[3];
		
		my $id_agent = get_agent_id($dbh,$agent_name);
	
		$field_value->{'field'} = 'ip_target';
		
		# Check if the address already exist
		my $address_id = get_addr_id($dbh,$field_value->{'new_value'});
		
		# If the addres doesn't exist, we add it to the addresses list
		if($address_id == -1) {
			$address_id = add_address($dbh,$field_value->{'new_value'});
		}
		
		# Add the address to the agent
		add_new_address_agent ($dbh, $address_id, $id_agent);
		
		# Only pending set as main address (Will be done at the end of the function)
	}
	elsif($field_value->{'field'} eq 'module_port') {
		if ($field_value->{'new_value'} > 65535 || $field_value->{'new_value'} < 1) {
			print_log "[ERROR] Port error. Port must into [1-65535]\n\n";
			exit;
		}
		$field_value->{'field'} = 'tcp_port';
	}
	elsif($field_value->{'field'} eq 'timeout') {
		$field_value->{'field'} = 'max_timeout';
	}
	elsif($field_value->{'field'} eq 'retries') {
		$field_value->{'field'} = 'max_retries';
	}
	else {
		print_log "[ERROR] The field '".$field_value->{'field'}."' is not available for network modules\n\n";
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
		
		# If the addres doesn't exist, we add it to the addresses list
		if($address_id == -1) {
			$address_id = add_address($dbh,$field_value->{'new_value'});
		}
		
		# Add the address to the agent
		add_new_address_agent ($dbh, $address_id, $id_agent);
		
		# Only pending set as main address (Will be done at the end of the function)
	}
	elsif($field_value->{'field'} eq 'module_port') {
		if ($field_value->{'new_value'} > 65535 || $field_value->{'new_value'} < 1) {
			print_log "[ERROR] Port error. Port must into [1-65535]\n\n";
			exit;
		}
		$field_value->{'field'} = 'tcp_port';
	}
	elsif($field_value->{'field'} eq 'timeout') {
		$field_value->{'field'} = 'max_timeout';
	}
	elsif($field_value->{'field'} eq 'retries') {
		$field_value->{'field'} = 'max_retries';
	}
	else {
		print_log "[ERROR] The field '".$field_value->{'field'}."' is not available for SNMP modules\n\n";
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
	elsif($field_value->{'field'} eq 'module_address') {
		my $agent_name = @ARGV[3];
		
		my $id_agent = get_agent_id($dbh,$agent_name);
		
		$field_value->{'field'} = 'ip_target';
		
		# Check if the address already exist
		my $address_id = get_addr_id($dbh,$field_value->{'new_value'});
		
		# If the addres doesn't exist, we add it to the addresses list
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
	elsif($field_value->{'field'} eq 'timeout') {
		$field_value->{'field'} = 'max_timeout';
	}
	else {
		print_log "[ERROR] The field '".$field_value->{'field'}."' is not available for plugin modules\n\n";
		exit;
	}
}

##############################################################################
# Add a profile to a User in a Group
# Related option: --update_module
##############################################################################

sub cli_module_update() {
	my ($module_name,$agent_name,$field,$new_value, $use_alias) = @ARGV[2..6];

	if (defined $use_alias and $use_alias eq 'use_alias') {
		my @id_agents = get_agent_ids_from_alias($dbh,$agent_name);

		my $save_initial_field = $field;
		my $save_new_value = $new_value;

		foreach my $id (@id_agents) {
			$field = $save_initial_field;
			$new_value = $save_new_value;
			my $id_agent = $id->{'id_agente'};
			exist_check($id_agent,'agent',$agent_name);
			my $id_agent_module = get_agent_module_id ($dbh, $module_name, $id_agent);
			if ($id_agent_module == -1) {
				next;
			}
			
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
					print_log "[ERROR] A module called '$module_name' already exist in the agent '$new_value'\n\n";
					exit;
				}
				$field = 'id_agente';
				$new_value = $id_agent_change;
			}
			elsif ($field eq 'module_name') {
				my $id_agent_module_change = get_agent_module_id ($dbh, $new_value, $id_agent);
				if ($id_agent_module_change != -1) {
					print_log "[ERROR] A module called '$new_value' already exist in the agent '$agent_name'\n\n";
					exit;
				}
				$field = 'nombre';
				$new_value = safe_input($new_value);
			}
			elsif ($field eq 'description') {
				$field = 'descripcion';
				$new_value = safe_input($new_value);
			}
			elsif ($field eq 'module_group') {
				my $module_group_id = get_module_group_id($dbh,$new_value);
				
				if ($module_group_id == -1) {
					print_log "[ERROR] Module group '$new_value' doesn't exist\n\n";
					exit;
				}
				$field = 'id_module_group';
				$new_value = $module_group_id;
			}
			elsif ($field eq 'enable_unknown_events') {
				my $disabled_types_event = {};
				if ($new_value) {
					$disabled_types_event->{'going_unknown'} = 0;
				}
				else {
					$disabled_types_event->{'going_unknown'} = 1;
				}
				$field = 'disabled_types_event';
				$new_value = encode_json($disabled_types_event);
			}
			elsif ($field eq 'ff_threshold') {
				$field = 'min_ff_event';
			}
			elsif ($field eq 'each_ff') {
				$field = 'each_ff';
			}
			elsif ($field eq 'ff_threshold_normal') {
				$field = 'min_ff_event_normal';
			}
			elsif ($field eq 'ff_threshold_warning') {
				$field = 'min_ff_event_warning';
			}
			elsif ($field eq 'ff_threshold_critical') {
				$field = 'min_ff_event_critical';
			}
			elsif ($field eq 'critical_instructions') {
				$field = 'critical_instructions';
			}
			elsif ($field eq 'warning_instructions') {
				$field = 'warning_instructions';
			}
			elsif ($field eq 'unknown_instructions') {
				$field = 'unknown_instructions';
			}
			else {
				# If is not a common value, check type and call type update funtion
				my $type = pandora_get_module_type($dbh,$id_agent_module);
				print("TYPE EN ELSE".$type);
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
					print_log "[ERROR] The field '$field' is not available for this type of module\n\n";
				}
				
				$field = $field_value{'field'};
				$new_value = $field_value{'new_value'};
			}
			
			print_log "[INFO] Updating field '$field' in module '$module_name' of agent '$agent_name' with new value '$new_value'\n\n";
			
			my $update;
			
			$update->{$field} = $new_value;

			my $policy_id = enterprise_hook('get_id_policy_module_agent_module',[$dbh, safe_input($id_agent_module)]);
			if ( $policy_id > 0) {
				$update->{policy_linked} = 0;
			}
			
			pandora_update_module_from_hash ($conf, $update, 'id_agente_modulo', $id_agent_module, $dbh);
		}
	} else {
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
				print_log "[ERROR] A module called '$module_name' already exist in the agent '$new_value'\n\n";
				exit;
			}
			$field = 'id_agente';
			$new_value = $id_agent_change;
		}
		elsif ($field eq 'module_name') {
			my $id_agent_module_change = get_agent_module_id ($dbh, $new_value, $id_agent);
			if ($id_agent_module_change != -1) {
				print_log "[ERROR] A module called '$new_value' already exist in the agent '$agent_name'\n\n";
				exit;
			}
			$field = 'nombre';
			$new_value = safe_input($new_value);
		}
		elsif ($field eq 'description') {
			$field = 'descripcion';
			$new_value = safe_input($new_value);
		}
		elsif ($field eq 'module_group') {
			my $module_group_id = get_module_group_id($dbh,$new_value);
			
			if ($module_group_id == -1) {
				print_log "[ERROR] Module group '$new_value' doesn't exist\n\n";
				exit;
			}
			$field = 'id_module_group';
			$new_value = $module_group_id;
		}
		elsif ($field eq 'enable_unknown_events') {
			my $disabled_types_event = {};
			if ($new_value) {
				$disabled_types_event->{'going_unknown'} = 0;
			}
			else {
				$disabled_types_event->{'going_unknown'} = 1;
			}
			$field = 'disabled_types_event';
			$new_value = encode_json($disabled_types_event);
		}
		elsif ($field eq 'ff_threshold') {
			$field = 'min_ff_event';
		}
		elsif ($field eq 'each_ff') {
			$field = 'each_ff';
		}
		elsif ($field eq 'ff_threshold_normal') {
			$field = 'min_ff_event_normal';
		}
		elsif ($field eq 'ff_threshold_warning') {
			$field = 'min_ff_event_warning';
		}
		elsif ($field eq 'ff_threshold_critical') {
			$field = 'min_ff_event_critical';
		}
		elsif ($field eq 'critical_instructions') {
			$field = 'critical_instructions';
		}
		elsif ($field eq 'warning_instructions') {
			$field = 'warning_instructions';
		}
		elsif ($field eq 'unknown_instructions') {
			$field = 'unknown_instructions';
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
				print_log "[ERROR] The field '$field' is not available for this type of module\n\n";
			}
			
			$field = $field_value{'field'};
			$new_value = $field_value{'new_value'};
		}
		
		print_log "[INFO] Updating field '$field' in module '$module_name' of agent '$agent_name' with new value '$new_value'\n\n";
		
		my $update;
		
		$update->{$field} = $new_value;

		my $policy_id = enterprise_hook('get_id_policy_module_agent_module',[$dbh, safe_input($id_agent_module)]);
		if ( $policy_id > 0) {
			$update->{policy_linked} = 0;
		}
		
		pandora_update_module_from_hash ($conf, $update, 'id_agente_modulo', $id_agent_module, $dbh);
	}
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
				print_log "[ERROR] File '$file' not exists or cannot be opened\n\n";
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
		
	print_log "[INFO] Adding profile '$profile_name' to user '$user_id' in group '$group_name'\n\n";
		
	pandora_add_profile_to_user ($dbh, $user_id, $profile_id, $group_id);
}

##############################################################################
# Delete a user.
# Related option: --delete_user
##############################################################################

sub cli_delete_user() {
	my $user_name = @ARGV[2];
	
	print_log "[INFO] Deleting user '$user_name' \n\n";
	
	my $result = pandora_delete_user($dbh,$user_name);
	exist_check($result,'user',$user_name);
}

##############################################################################
# Delete an alert_template.
# Related option: --delete_user
##############################################################################

sub cli_delete_alert_template() {
	my $template_name = @ARGV[2];
	
	print_log "[INFO] Deleting template '$template_name' \n\n";
	
	my $result = pandora_delete_alert_template($dbh,$template_name);
	exist_check($result,'alert template',$template_name);
}

##############################################################################
# Add alert command.
# Related option: --create_alert_command
##############################################################################

sub cli_create_alert_command() {
	my ($command_name,$command,$group_name,$description,$internal,$fields_descriptions,$fields_values) = @ARGV[2..8];
	
	print_log "[INFO] Adding command '$command_name'\n\n";
	
	my $command_id = get_command_id($dbh,$command_name);
	non_exist_check($command_id,'command',$command_name);
	
	my $id_group;
	
	if (! $group_name || $group_name eq "All") {
		$id_group = 0;
	}
	else {
		$id_group = get_group_id($dbh,$group_name);
		exist_check($id_group,'group',$group_name);
	}
	
	my %parameters;						
	
	$parameters{'name'} = $command_name;
	$parameters{'command'} = $command;
	$parameters{'id_group'} = $id_group;
	$parameters{'description'} = $description;
	$parameters{'internal'} = $internal;
	$parameters{'fields_descriptions'} = $fields_descriptions;
	$parameters{'fields_values'} = $fields_values;
	
	pandora_create_alert_command ($conf, \%parameters, $dbh);
}

##############################################################################
# Show all the alert commands (without parameters) or the alert commands with a filter parameters
# Related option: --get_alert_commands
##############################################################################

sub cli_get_alert_commands() {
	my ($command_name, $command, $group_name, $description, $internal) = @ARGV[2..6];

	my $id_group;
	my $condition = ' 1=1 ';

	if($command_name ne '') {
		my $name = safe_input ($command_name);		
		$condition .= " AND name LIKE '%$name%' ";
	}
	
	if($command ne '') {
		$condition .= " AND command LIKE '%$command%' ";
	}

	if($group_name ne '') {
		$id_group = get_group_id($dbh, $group_name);
		exist_check($id_group,'group',$group_name);
		
		$condition .= " AND id_group = $id_group ";
	}
	
	if($description ne '') {
		$condition .= " AND description LIKE '%$description%' ";
	}

	if($internal ne '') {
		$condition .= " AND internal = $internal ";
	}

	my @alert_command = get_db_rows ($dbh, "SELECT * FROM talert_commands WHERE $condition");	

	if(scalar(@alert_command) == 0) {
		print_log "[INFO] No commands found\n\n";
		exit;
	}

	my $head_print = 0;
	foreach my $commands (@alert_command) {

		if($head_print == 0) {
			$head_print = 1;
			print "id_command, command_name\n";
		}
		print $commands->{'id'}.",".safe_output($commands->{'name'})."\n";
	}

	if($head_print == 0) {
		print_log "[INFO] No commands found\n\n";
	}
}

##############################################################################
# Get alert actions.
# Related option: --get_alert_actions
##############################################################################

sub cli_get_alert_actions() {
	my ($action_name,$separator,$return_type) = @ARGV[2..4];
	if ($return_type eq '') {
		$return_type = 'csv';
	}
	my $result = manage_api_call(\%conf,'get', 'alert_actions', undef, undef, "$action_name|$separator",$return_type);
	print "$result \n\n ";
}

##############################################################################
# Get alert actions in nodes.
# Related option: --get_alert_actions_meta
##############################################################################

sub cli_get_alert_actions_meta() {
	my ($server_name,$action_name,$separator,$return_type) = @ARGV[2..5];
	if ($return_type eq '') {
		$return_type = 'csv';
	}

	my $result = manage_api_call(\%conf,'get', 'alert_actions_meta', undef, undef, "$server_name|$action_name|$separator",$return_type);
	print "$result \n\n ";
}

##############################################################################
# Add profile.
# Related option: --add_profile
##############################################################################

sub cli_add_profile() {
	my ($user_name,$profile_name,$group_name) = @ARGV[2..4];
	
	my $id_profile = get_profile_id($dbh,$profile_name);
	exist_check($id_profile,'profile',$profile_name);
	
	my $id_group;
	
	if($group_name eq "All") {
		$id_group = 0;
		print_log "[INFO] Adding profile '$profile_name' to all groups for user '$user_name') \n\n";
	}
	else {
		$id_group = get_group_id($dbh,$group_name);
		exist_check($id_group,'group',$group_name);
		print_log "[INFO] Adding profile '$profile_name' to group '$group_name' for user '$user_name') \n\n";
	}
	
	pandora_create_user_profile ($dbh, $user_name, $id_profile, $id_group);
}

##############################################################################
# Create profile.
# Related option: --create_profile
##############################################################################

sub cli_create_profile() {
	my ($profile_name,$agent_view,
	$agent_edit,$agent_disable,$alert_edit,$alert_management,$user_management,$db_management,
	$event_view,$event_edit,$event_management,$report_view,$report_edit,$report_management,
	$map_view,$map_edit,$map_management,$vconsole_view,$vconsole_edit,$vconsole_management,$pandora_management) = @ARGV[2..25];

	my $id_profile = get_profile_id($dbh,$profile_name);
	non_exist_check($id_profile,'profile',$profile_name);

	pandora_create_profile ($dbh, $profile_name, $agent_view,
	$agent_edit, $agent_disable, $alert_edit, $alert_management, $user_management, $db_management,
	$event_view, $event_edit, $event_management, $report_view, $report_edit, $report_management,
	$map_view, $map_edit, $map_management, $vconsole_view, $vconsole_edit, $vconsole_management, $pandora_management);
}

##############################################################################
## Update profile.
## Related option: --update_profile
##############################################################################
#
sub cli_update_profile() {
	my ($profile_name,$agent_view,
	$agent_edit,$agent_disable,$alert_edit,$alert_management,$user_management,$db_management,
	$event_view,$event_edit,$event_management,$report_view,$report_edit,$report_management,
	$map_view,$map_edit,$map_management,$vconsole_view,$vconsole_edit,$vconsole_management,$pandora_management) = @ARGV[2..25];

	my $id_profile = get_profile_id($dbh,$profile_name);
	exist_check($id_profile,'profile',$profile_name);

	pandora_update_profile ($dbh, $profile_name, $agent_view,
	$agent_edit, $agent_disable, $alert_edit, $alert_management, $user_management, $db_management,
	$event_view, $event_edit, $event_management, $report_view, $report_edit, $report_management,
	$map_view, $map_edit, $map_management, $vconsole_view, $vconsole_edit, $vconsole_management, $pandora_management);
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
		print_log "[INFO] Deleting profile '$profile_name' from all groups for user '$user_name') \n\n";
	}
	else {
		$id_group = get_group_id($dbh,$group_name);
		exist_check($id_group,'group',$group_name);
		print_log "[INFO] Deleting profile '$profile_name' from group '$group_name' for user '$user_name') \n\n";
	}
	
	pandora_delete_user_profile ($dbh, $user_name, $id_profile, $id_group);
}

##############################################################################
# Create event
# Related option: --create_event
##############################################################################

sub cli_create_event() {
	my ($event,$event_type,$group_name,$agent_name,$module_name,$event_status,$severity,$template_name, $user_name, $comment, $source, $id_extra, $tags, $custom_data,$force_create_agent,$c_instructions,$w_instructions,$u_instructions,$use_alias,$server_id,$event_custom_id) = @ARGV[2..22];

	$event_status = 0 unless defined($event_status);
	$severity = 0 unless defined($severity);

	my $id_user;
	
	if (!defined($user_name) || $user_name eq '') {
		$id_user = 0;
	}
	else {
		$id_user = pandora_get_user_id($dbh,$user_name);
		exist_check($id_user,'user',$user_name);
	}
	
	my $id_group = get_group_id($dbh,$group_name);
	exist_check($id_group,'group',$group_name);

	my $id_agent;

	if (defined $use_alias and $use_alias eq 'use_alias') {

		my @id_agents = get_agent_ids_from_alias($dbh,$agent_name);

		foreach my $id (@id_agents) {
			if (! $agent_name) {
				$id_agent = 0;
			}
			else {
				$id_agent = $id->{'id_agente'};
			}
			
			my $id_agentmodule;
			
			if (! $module_name) {
				$id_agentmodule = 0;
			}
			else {
				$id_agentmodule = get_agent_module_id($dbh,$module_name,$id_agent);
				if ($id_agentmodule eq -1) {
					next;
				}
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
			
			print_log "[INFO] Adding event '$event' for agent '$agent_name' \n\n";

			pandora_event ($conf, $event, $id_group, $id_agent, $severity,
				$id_alert_agent_module, $id_agentmodule, $event_type, $event_status, $dbh, safe_input($source), $user_name, safe_input($comment), safe_input($id_extra), safe_input($tags), safe_input($c_instructions), safe_input($w_instructions), safe_input($u_instructions), $custom_data, undef, undef, $server_id, safe_input($event_custom_id));
		}
	} else {
		if (! $agent_name) {
			$id_agent = 0;
		}
		else {
			$id_agent = get_agent_id($dbh,$agent_name);
			# exist_check($id_agent,'agent',$agent_name);
			if($id_agent == -1){
				if($force_create_agent == 1){
					my $target_os = pandora_get_os($dbh, 'other');
					my $target_server = $conf{'servername'};
					pandora_create_agent ($conf, $target_server, $agent_name, '', $id_group, '', $target_os, 'Created by cli_create_event', '300', $dbh);
					print_log "[INFO] Adding agent '$agent_name' \n\n";
					$id_agent = get_agent_id($dbh,$agent_name);
				}
				else{
					exist_check($id_agent,'agent',$agent_name);
				}
			}

		}
		
		my $id_agentmodule;
		
		if (! $module_name) {
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
		
		print_log "[INFO] Adding event '$event' for agent '$agent_name' \n\n";

		pandora_event ($conf, $event, $id_group, $id_agent, $severity,
			$id_alert_agent_module, $id_agentmodule, $event_type, $event_status, $dbh, safe_input($source), $user_name, $comment, safe_input($id_extra), safe_input($tags), safe_input($c_instructions), safe_input($w_instructions), safe_input($u_instructions), $custom_data, undef, undef, $server_id, safe_input($event_custom_id));

	}
}

##############################################################################
# Update event custom id
# Related option: --update_event_custom_id
##############################################################################

sub cli_update_event_custom_id() {
	my ($id_event, $event_custom_id) = @ARGV[2..3];
	my $result = manage_api_call(\%conf, 'set', 'event_custom_id', $id_event, $event_custom_id);
	print "\n$result\n";
}

##############################################################################
# Validate event.
# Related option: --validate_event
##############################################################################

sub cli_validate_event() {
	my ($agent_name, $module_name, $datetime_min, $datetime_max, $user_name, $criticity, $template_name, $use_alias) = @ARGV[2..9];
	my $id_agent = '';
	my $id_agentmodule = '';

	if(defined($datetime_min) && $datetime_min ne '') {
		if ($datetime_min !~ /([0-9]{2,4})\-([0-1][0-9])\-([0-3][0-9]) +([0-2][0-9]):([0-5][0-9])/) {
			print_log "[ERROR] Invalid datetime_min format. (Correct format: YYYY-MM-DD HH:mm)\n";
			exit;
		}
		# Add the seconds
		$datetime_min .= ":00";
	}
	
	if(defined($datetime_max) && $datetime_max ne '') {
		if ($datetime_max !~ /([0-9]{2,4})\-([0-1][0-9])\-([0-3][0-9]) +([0-2][0-9]):([0-5][0-9])/) {
			print_log "[ERROR] Invalid datetime_max $datetime_max. (Correct format: YYYY-MM-DD HH:mm)\n";
			exit;
		}
		# Add the seconds
		$datetime_max .= ":00";
	}

	if (defined $use_alias and $use_alias eq 'use_alias') {
		my @id_agents = get_agent_ids_from_alias($dbh,$agent_name);

		foreach my $id (@id_agents) {
			if(defined($agent_name) && $agent_name ne '') {
				$id_agent = $id->{'id_agente'};
				exist_check($id_agent,'agent',$agent_name);
				
				if($module_name ne '') {
					$id_agentmodule = get_agent_module_id($dbh, $module_name, $id_agent);
					if ($id_agentmodule eq -1) {
						next;
					}
				}
			}

			my $id_alert_agent_module = '';
			
			if(defined($template_name) && $template_name ne '') {
				my $id_template = get_template_id($dbh,$template_name);
				exist_check($id_template,'template',$template_name);
				$id_alert_agent_module = get_template_module_id($dbh,$id_agentmodule,$id_template);
				exist_check($id_alert_agent_module,'template module',$template_name);
			}
						
			pandora_validate_event_filter ($conf, $id_agentmodule, $id_agent, $datetime_min, $datetime_max, $user_name, $id_alert_agent_module, $criticity, $dbh);
			print_log "[INFO] Validating event for agent '$id->{'nombre'}'\n\n";
		}
	} else {
		if(defined($agent_name) && $agent_name ne '') {
			$id_agent = get_agent_id($dbh,$agent_name);
			exist_check($id_agent,'agent',$agent_name);
			
			if($module_name ne '') {
				$id_agentmodule = get_agent_module_id($dbh, $module_name, $id_agent);
				exist_check($id_agentmodule,'module',$module_name);
			}
		}

		my $id_alert_agent_module = '';
		
		if(defined($template_name) && $template_name ne '') {
			my $id_template = get_template_id($dbh,$template_name);
			exist_check($id_template,'template',$template_name);
			$id_alert_agent_module = get_template_module_id($dbh,$id_agentmodule,$id_template);
			exist_check($id_alert_agent_module,'template module',$template_name);
		}
					
		pandora_validate_event_filter ($conf, $id_agentmodule, $id_agent, $datetime_min, $datetime_max, $user_name, $id_alert_agent_module, $criticity, $dbh);
		print_log "[INFO] Validating event for agent '$agent_name'\n\n";
	}
}

##############################################################################
# Validate event.
# Related option: --validate_event_id
##############################################################################

sub cli_validate_event_id() {
	my $id_event = @ARGV[2];

	my $event_name = pandora_get_event_name($dbh, $id_event);
	exist_check($event_name,'event',$id_event);
	
	print_log "[INFO] Validating event '$id_event'\n\n";
				
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

	my $query = "SELECT * FROM tevento WHERE id_evento=" . $id_event;

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
		print $event_data->{'tags'};
		print $csv_separator;
		print $event_data->{'source'};
		print $csv_separator;
		print $event_data->{'id_extra'};
		print "\n";
	}
	
    exit;
}

###############################################################################
# Add event comment
# Related option: --add_event_comment
###############################################################################
sub cli_add_event_comment() {
	my ($id_event, $user_name, $comment) = @ARGV[2..4];
	
	my $id_user;
	if (!defined($user_name) || $user_name eq '') {
		$id_user = 'admin';
	}
	else {
		$id_user = pandora_get_user_id($dbh,safe_input($user_name));
		exist_check($id_user,'user',$user_name);
	}
	
	my $event_name = pandora_get_event_name($dbh, $id_event);
	exist_check($event_name,'event',$id_event);

	print_log "[INFO] Adding event comment for event '$id_event'. \n\n";
	
	my $parameters;
	$parameters->{'id_event'} = $id_event;
	$parameters->{'id_user'} = $user_name;
	$parameters->{'utimestamp'} = time();
	$parameters->{'action'} = "Added comment";
	$parameters->{'comment'} = safe_input($comment);

	my $comment_id = db_process_insert($dbh, 'id', 'tevent_comment', $parameters);
	return $comment_id;
}

##############################################################################
# Delete data.
# Related option: --delete_data
##############################################################################

sub cli_delete_data($) {
	my $ltotal = shift;
	my ($opt, $name, $name2, $use_alias) = @ARGV[2..5];


	if($opt eq '-m' || $opt eq '--m') {
		if (defined $use_alias and $use_alias eq 'use_alias') {
			my @id_agents = get_agent_ids_from_alias($dbh,$name2);

			foreach my $id (@id_agents) {
				# Delete module data
				param_check($ltotal, 3) unless ($name2 ne '');
				my $id_agent = $id->{'id_agente'};
				exist_check($id_agent,'agent',$name2);
				
				my $id_module = get_agent_module_id($dbh,$name,$id_agent);
				exist_check($id_module,'module',$name);
			
				print_log "DELETING THE DATA OF THE MODULE $name OF THE AGENT $name2\n\n";
				
				pandora_delete_data($dbh, 'module', $id_module);
			}
		} else {
			# Delete module data
			param_check($ltotal, 3) unless ($name2 ne '');
			my $id_agent = get_agent_id($dbh,$name2);
			exist_check($id_agent,'agent',$name2);
			
			my $id_module = get_agent_module_id($dbh,$name,$id_agent);
			exist_check($id_module,'module',$name);
		
			print_log "DELETING THE DATA OF THE MODULE $name OF THE AGENT $name2\n\n";
			
			pandora_delete_data($dbh, 'module', $id_module);
		}

	}
	elsif($opt eq '-a' || $opt eq '--a') {
		if (defined $use_alias and $use_alias eq 'use_alias') {
			my @id_agents = get_agent_ids_from_alias($dbh,$name);
			foreach my $id (@id_agents) {
				# Delete agent's modules data
				my $id_agent = $id->{'id_agente'};
				exist_check($id_agent,'agent',$name);
		
				print_log "DELETING THE DATA OF THE AGENT $name\n\n";
		
				pandora_delete_data($dbh, 'agent', $id_agent);
			}
		} else {
			my $id_agent = get_agent_id($dbh,$name);
			exist_check($id_agent,'agent',$name);
		
			print_log "DELETING THE DATA OF THE AGENT $name\n\n";
		
			pandora_delete_data($dbh, 'agent', $id_agent);
		}
	}
	elsif($opt eq '-g' || $opt eq '--g') {
		if (defined $use_alias and $use_alias eq 'use_alias') {
			my @id_agents = get_agent_ids_from_alias($dbh,$name);

			foreach my $id (@id_agents) {
				# Delete group's modules data
				my $id_group = $id->{'id_agente'};
				exist_check($id_group,'group',$name);
				
				print_log "DELETING THE DATA OF THE GROUP $name\n\n";
				
				pandora_delete_data($dbh, 'group', $id_group);
			}
		} else {
			# Delete group's modules data
			my $id_group = get_group_id($dbh,$name);
			exist_check($id_group,'group',$name);
				
			print_log "DELETING THE DATA OF THE GROUP $name\n\n";
				
			pandora_delete_data($dbh, 'group', $id_group);
		}
	}
	else {
		print_log "[ERROR] Invalid parameter '$opt'.\n\n";
		help_screen ();
		exit;
	}
}

##############################################################################
# Add policy to apply queue.
# Related option: --apply_policy
##############################################################################

sub cli_apply_policy() {
	my ($id_policy, $id_agent, $name, $id_server) = @ARGV[2..5];

	# Call the API.
	my $result = manage_api_call(\%conf, 'set', 'apply_policy', $id_policy, $id_agent, "$name|$id_server");
	print "\n$result\n";
}

##############################################################################
# Add all policies to apply queue.
# Related option: --apply_all_policies
##############################################################################

sub cli_apply_all_policies() {
	my $policies = enterprise_hook('get_policies', [$dbh, 0]);
	
	my $npolicies = scalar(@{$policies});
	
	print_log "[INFO] $npolicies policies found\n\n";
	
	my $added = 0;
	foreach my $policy (@{$policies}) {
		my $ret = enterprise_hook('pandora_add_policy_queue', [$dbh, $conf, $policy->{'id'}, 'apply', 0, 1]);
		if($ret != -1) {
			$added++;
			print_log "[INFO] Added operation 'apply' to policy '".safe_output($policy->{'name'})."'\n";
		}
	}
		
	if($npolicies > $added) {
		my $failed = $npolicies - $added;
		print_log "[ERROR] $failed policies cannot be added to apply queue. Maybe the queue already contains these operations.\n";
	}
}

##############################################################################
# Recreate the files of a collection.
# Related option: --recreate_collection
##############################################################################

sub cli_recreate_collection () {
	my $collection_id = @ARGV[2];

	my $result = enterprise_hook('pandora_recreate_collection', [$conf, $collection_id, $dbh]);
	
	if ($result == 1) {
		print_log "[INFO] Collection recreated successfully.\n";
	}
	elsif ($result == 0) {
		print_log "[ERROR] Collection not recreated.\n";
	}
}

##############################################################################
# Validate all the alerts
# Related option: --validate_all_alerts
##############################################################################

sub cli_validate_all_alerts() {
	print_log "[INFO] Validating all the alerts\n\n";
		
	my $res = db_update ($dbh, "UPDATE talert_template_modules SET times_fired = 0, internal_counter = 0");
	
	if($res == -1) {
		print_log "[ERROR] Alerts cannot be validated\n\n";
	}
	else {
		# Update fired alerts count in agents
		db_update ($dbh, "UPDATE tagente SET fired_count = 0");
	}
}

##############################################################################
# Validate all the alerts
# Related option: --validate_alert
##############################################################################

sub cli_validate_alert() {
		my ($template_name, $agent_id, $module_id, $use_alias) = @ARGV[2..6];
	my $id_agent = '';
	my $id_agentmodule = '';

	my $result = 0;

	if (defined $use_alias and $use_alias eq 'use_alias') {
		my @id_agents = get_agent_ids_from_alias($dbh,$agent_id);
			if(!@id_agents) {
				print (STDERR "[ERROR] Error: The agent '$agent_id' not exists.\n\n");
		}

		foreach my $id (@id_agents) {
			if(defined($agent_id) && $agent_id ne '') {
				$id_agent = $id->{'id_agente'};
				exist_check($id_agent,'agent',$agent_id);
				
				if($module_id ne '') {
					$module_id = get_agent_module_id($dbh, $module_id, $id_agent);
					if ($module_id eq -1) {
						next;
					}
				}
			}


			my $id_alert_agent_module = '';
			
			if(defined($template_name) && $template_name ne '') {
				my $id_template = get_template_id($dbh,$template_name);
				exist_check($id_template,'template',$template_name);
				$id_alert_agent_module = get_template_module_id($dbh,$module_id,$id_template);
				exist_check($id_alert_agent_module,'template module',$template_name);
			}


			$result = pandora_validate_alert_id($id_alert_agent_module, $id, $module_id, $template_name);
			print_log "[INFO] Validating alert for agent '$id->{'nombre'}'\n\n";
		}
	} else {
		if(defined($agent_id) && $agent_id ne '') {
			my $agent_name = get_agent_name($dbh,$agent_id);
			exist_check($agent_id,'agent',$agent_name);
			
			if($module_id ne '') {
				my $module_name = get_module_name($dbh, $module_id);
				exist_check($module_id,'module',$module_name);
			}
		}

		my $id_alert_agent_module = '';
		
		if(defined($template_name) && $template_name ne '') {
			my $id_template = get_template_id($dbh,$template_name);
			exist_check($id_template,'template',$template_name);
			$id_alert_agent_module = get_template_module_id($dbh,$module_id,$id_template);
			exist_check($id_alert_agent_module,'template module',$template_name);
		}
					
			$result = pandora_validate_alert_id($id_alert_agent_module, $id_agent, $module_id, $template_name);
			print_log "[INFO] Validating alert for agent '$agent_id'\n\n";
	}

if($result == 0) {
		print_log "[ERROR] Alert could not be validated\n\n";
	}
	else {
			print_log "[INFO] Alert successfully validated\n\n";
;
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
		print_log "[INFO] No alerts found in the policy '$policy_name'\n\n";
		return;
	}
	
	$policy_alerts_id = join(',',@policy_alerts_id_array);
	
	#Get the fired alerts that match with the filter to update counts after validate it
	my @fired_alerts = get_db_rows ($dbh, "SELECT id_agent_module, count(id) alerts FROM talert_template_modules WHERE id_policy_alerts IN (?) AND times_fired > 0 GROUP BY id_agent_module", $policy_alerts_id);

	print_log "[INFO] Validating the alerts of the policy '$policy_name'\n\n";
		
	my $res = db_update ($dbh, "UPDATE talert_template_modules SET times_fired = 0, internal_counter = 0 WHERE id_policy_alerts IN (?)", $policy_alerts_id);
		
	if($res == -1) {
		print_log "[ERROR] Alerts cannot be validated\n\n";
	}
	else {
		# Update fired alerts count in agents if necessary
		if($#fired_alerts > -1) {
			foreach my $fired_alert (@fired_alerts) {
				my $id_agent = get_module_agent_id($dbh,  $fired_alert->{'id_agent_module'});
				db_update ($dbh, 'UPDATE tagente SET fired_count=fired_count-? WHERE id_agente=?', $fired_alert->{'alerts'}, $id_agent);
			}
		}
	}
}


##############################################################################
# Show the module id where is a given agent
# Related option: --get_module_id
# perl pandora_manage.pl /etc/pandora/pandora_server.conf --get_module_id 4 'host alive'
##############################################################################

sub cli_get_module_id() {
	(my $agent_id,my $module_name) = @ARGV[2..3];

	exist_check($agent_id,'agent',$agent_id);

	my $module_id = get_agent_module_id($dbh, $module_name, $agent_id);
	exist_check($module_id, 'module name', $module_name);

	print $module_id;

}

##############################################################################
# Retrieves the module custom_id given id_agente_modulo.
# Related option: --get_module_custom_id
# perl pandora_manage.pl /etc/pandora/pandora_server.conf --get_module_custom_id 4
##############################################################################

sub cli_get_module_custom_id {
	my $module_id = $ARGV[2];

	my $custom_id = get_agentmodule_custom_id($dbh, $module_id);
	
	if (defined($custom_id)) {
		print $custom_id;
	} else {
		print "\n";
	}
}

##############################################################################
# Update sor erases the module custom_id given id_agente_modulo.
# Related option: --get_module_custom_id
# perl pandora_manage.pl /etc/pandora/pandora_server.conf --get_module_custom_id 4 test
##############################################################################

sub cli_set_module_custom_id {
	my ($module_id, $custom_id) = @ARGV[2..3];

	my $rs = set_agentmodule_custom_id($dbh, $module_id, $custom_id);
	
	if ($rs > 0) {
		print $custom_id;
	} else {
		print "[ERROR] No changes.";
	}
}

##############################################################################
# Show the group name where a given agent is
# Related option: --get_agent_group
##############################################################################

sub cli_get_agent_group() {
	my ($agent_name,$use_alias) = @ARGV[2..3];

	if (is_metaconsole($conf) == 1) {
		
		my $agents_groups = enterprise_hook('get_metaconsole_agent',[$dbh, $agent_name]);
		
		if (not defined $use_alias and scalar(@{$agents_groups}) != 0) {
			foreach my $agent (@{$agents_groups}) {
				my @test =  $agent;
				my $group_name = get_group_name ($dbh, $agent->{'id_grupo'});
				print "Server: $agent->{'server_name'} Agent: $agent->{'nombre'} Name Group: $group_name \n\n";
			}
		}
		else {
			my $servers = enterprise_hook('get_metaconsole_setup_servers',[$dbh]);
			my @servers_id = split(',',$servers);
			my @list_servers;
			my $list_names_servers;
			foreach my $server (@servers_id) {
				my $dbh_metaconsole = enterprise_hook('get_node_dbh',[$conf, $server, $dbh]);
				
				my @id_agents;
				my $id_agent;

				if (defined $use_alias and $use_alias eq 'use_alias') {
					@id_agents = get_agent_ids_from_alias($dbh_metaconsole,$agent_name);

					foreach my $id (@id_agents) {

						if ($id->{'id_agente'} == -1) {
							next;
						}
						else {
							my $id_group = get_agent_group ($dbh_metaconsole, $id->{'id_agente'});
							my $group_name = get_group_name ($dbh_metaconsole, $id_group);
							$agent_name = safe_output($agent_name);
							print "[INFO] Agent: $id->{'nombre'} Name Group: $group_name\n\n";
						}
					}
				} else {
					$id_agent = get_agent_id($dbh_metaconsole,$agent_name);
					
					if ($id_agent == -1) {
						next;
					}
					else {
						my $id_group = get_agent_group ($dbh_metaconsole, $id_agent);
						my $group_name = get_group_name ($dbh_metaconsole, $id_group);
						$agent_name = safe_output($agent_name);
						print "[INFO] Agent: $agent_name Name Group: $group_name\n\n";
					}
				}
			}
		}
	}
	else {
		my @id_agents;
		my $id_agent;
		my $id_group;
		my $group_name;

		if (defined $use_alias and $use_alias eq 'use_alias') {
			@id_agents = get_agent_ids_from_alias($dbh,$agent_name);

			foreach my $id (@id_agents) {
				exist_check($id->{'id_agente'},'agent',$agent_name);

				$id_group = get_agent_group ($dbh, $id->{'id_agente'});
		
				$group_name = get_group_name ($dbh, $id_group);
				print $group_name."\n";
			}
		} else {
			$id_agent = get_agent_id($dbh,$agent_name);
			exist_check($id_agent,'agent',$agent_name);

			$id_group = get_agent_group ($dbh, $id_agent);
		
			$group_name = get_group_name ($dbh, $id_group);
			print $group_name;
		}
		

	}
}

##############################################################################
# Show the group id where is a given agent
# Related option: --get_agent_group_id
##############################################################################
sub cli_get_agent_group_id() {
	my ($agent_name,$use_alias) = @ARGV[2..3];
	
	if (is_metaconsole($conf) == 1) {

		my $agents_groups = enterprise_hook('get_metaconsole_agent',[$dbh, $agent_name]);
		
		if (not defined $use_alias and scalar(@{$agents_groups}) != 0) {
			
			foreach my $agent (@{$agents_groups}) {
				print "Server: $agent->{'server_name'} Agent: $agent->{'nombre'} ID Group: $agent->{'id_grupo'}\n\n";
			}
		}
		else {
			my $servers = enterprise_hook('get_metaconsole_setup_servers',[$dbh]);
			my @servers_id = split(',',$servers);
			my @list_servers;
			my $list_names_servers;
			foreach my $server (@servers_id) {
				my $dbh_metaconsole = enterprise_hook('get_node_dbh',[$conf, $server, $dbh]);
				
				my @id_agents;
				my $id_agent;

				if (defined $use_alias and $use_alias eq 'use_alias') {
					@id_agents = get_agent_ids_from_alias($dbh_metaconsole,$agent_name);

					foreach my $id (@id_agents) {

						if ($id->{'id_agente'} == -1) {
							next;
						}
						else {
							my $id_group = get_agent_group ($dbh_metaconsole, $id->{'id_agente'});
							$agent_name = safe_output($agent_name);
							print "Agent: $id->{'nombre'} ID Group: $id_group\n\n";
						}
					}
				} else {
					$id_agent = get_agent_id($dbh_metaconsole,$agent_name);
					
					if ($id_agent == -1) {
						next;
					}
					else {
						my $id_group = get_agent_group ($dbh_metaconsole, $id_agent);
						$agent_name = safe_output($agent_name);
						print "Agent: $agent_name ID Group: $id_group\n\n";
					}
				}
			}
		}
	}
	else {
		my @id_agents;
		my $id_agent;
		my $id_group;
		my $group_name;

		if (defined $use_alias and $use_alias eq 'use_alias') {
			@id_agents = get_agent_ids_from_alias($dbh,$agent_name);

			foreach my $id (@id_agents) {
				exist_check($id->{'id_agente'},'agent',$agent_name);

				$id_group = get_agent_group ($dbh, $id->{'id_agente'});
		
				print $id_group."\n";
			}
		} else {
			$id_agent = get_agent_id($dbh,$agent_name);
			exist_check($id_agent,'agent',$agent_name);

			$id_group = get_agent_group ($dbh, $id_agent);
		
			print $id_group;
		}
	}
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
	my ($agent_name,$use_alias) = @ARGV[2..3];
	
	my @id_agents;
	my $id_agent;

	if (defined $use_alias and $use_alias eq 'use_alias') {
		@id_agents = get_agent_ids_from_alias($dbh,$agent_name);

		foreach my $id (@id_agents) {
			exist_check($id->{'id_agente'},'agent',$agent_name);

			my $modules = pandora_get_agent_modules ($dbh, $id->{'id_agente'});

			if(scalar(@{$modules}) == 0) {
				print_log "[INFO] The agent '$agent_name' have no modules\n\n";
			}

			print "\n".$id->{'nombre'}."\n";
			print "id_module, module_name\n";
			foreach my $module (@{$modules}) {
				print $module->{'id_agente_modulo'}.",".safe_output($module->{'nombre'})."\n";
			}
		}
	} else {
		$id_agent = get_agent_id($dbh,$agent_name);
		exist_check($id_agent,'agent',$agent_name);
	
		my $modules = pandora_get_agent_modules ($dbh, $id_agent);

		if(scalar(@{$modules}) == 0) {
			print_log "[INFO] The agent '$agent_name' have no modules\n\n";
		}
	
		print "id_module, module_name\n";
		foreach my $module (@{$modules}) {
			print $module->{'id_agente_modulo'}.",".safe_output($module->{'nombre'})."\n";
		}
	}
}

##############################################################################
# Show the status of an agent
# Related option: --get_agent_status
##############################################################################

sub cli_get_agent_status() {
	my ($agent_name,$use_alias) = @ARGV[2..3];

	my @id_agents;
	my $id_agent;

	if (defined $use_alias and $use_alias eq 'use_alias') {
		@id_agents = get_agent_ids_from_alias($dbh,$agent_name);

		foreach my $id (@id_agents) {
			exist_check($id->{'id_agente'},'agent',$agent_name);

			my $agent_status = pandora_get_agent_status($dbh,$id->{'id_agente'});

			print pandora_get_agent_status($dbh,$id->{'id_agente'})."\n";
		}
	} else {
		$id_agent = get_agent_id($dbh,$agent_name);
		exist_check($id_agent,'agent',$agent_name);

		print pandora_get_agent_status($dbh,$id_agent)."\n";
	}
}


##############################################################################
# Show id, name and id_server of an agent given alias
# Related option: --get_agents_id_name_by_alias
##############################################################################

sub cli_get_agents_id_name_by_alias() {
	my $agent_alias = safe_input(@ARGV[2]);
	my $strict = @ARGV[3];
	my @agents;
	my $where_value;

	if($strict eq 'strict') {
		$where_value = $agent_alias;
	} else {
		$where_value = "%".$agent_alias."%";
	}

	if(is_metaconsole($conf) == 1) {
		@agents = get_db_rows($dbh,"SELECT alias, id_agente, id_tagente, id_tmetaconsole_setup as 'id_server', server_name FROM tmetaconsole_agent WHERE UPPER(alias) LIKE UPPER(?)", $where_value);
	} else {
		@agents = get_db_rows($dbh,"SELECT alias, id_agente FROM tagente WHERE UPPER(alias) LIKE UPPER(?)", $where_value);
	}
	if(scalar(@agents) == 0) {
		print "[ERROR] No agents retrieved.\n\n";
	} else {
		if(is_metaconsole($conf) == 1) {
			print "id_agente, alias, id_tagente, id_server, server_name\n";

				foreach my $agent (@agents) {
					print $agent->{'id_agente'}.", ".safe_output($agent->{'alias'}).", ".$agent->{'id_tagente'}.", ".$agent->{'id_server'}.", ".$agent->{'server_name'}."\n";
			}
		} else {
			print "id_agente, alias\n";

			foreach my $agent (@agents) {
				print $agent->{'id_agente'}.",".safe_output($agent->{'alias'})."\n";
			}
		}
	}	
}


sub cli_create_synthetic() {
	my $name_module = @ARGV[2];
	my $synthetic_type = @ARGV[3];
	
	my $agent_name = @ARGV[4];

	my @module_data;

	if (@ARGV[$#ARGV] eq "use_alias") {
		@module_data = @ARGV[5..$#ARGV-1];
	} else {
		@module_data = @ARGV[5..$#ARGV];
	}

	my $module;
	my (@filterdata,@data_module);
	
	if ($synthetic_type ne 'arithmetic' && $synthetic_type ne 'average') {
		print("[ERROR] Type of syntethic module doesn't exists \n\n");
		exit 1;
	}
	if (scalar(@{module_data}) == 0) {
		print("[ERROR] No modules data \n\n");
		exit 1;
	}
	if ($name_module eq '') {
		print("[ERROR] No module name \n\n");
		exit 1;
	}
	
	$module->{'custom_integer_1'} = 0;
	$module->{'custom_integer_2'} = 0;
	$module->{'prediction_module'} = 3; # Synthetic code is 3
	$module->{'flag'} = 1;

	my @id_agents;
	my $id_agent;

	if (@ARGV[$#ARGV] eq 'use_alias') {
		@id_agents = get_agent_ids_from_alias($dbh,$agent_name);

		foreach my $id (@id_agents) {
			@filterdata = ();
			$id_agent = $id->{'id_agente'};

			if ($id_agent > 0) {

				foreach my $i (0 .. $#module_data) {
					my @split_data = split(',',$module_data[$i]);
					if (@split_data[0] =~ m/(x|\/|\+|\*|\-)/ && length(@split_data[0]) == 1 ) {
						if ( @split_data[0] =~ m/(\/|\+|\*|\-)/ && $synthetic_type eq 'average' ) {
							print("[ERROR] With this type: $synthetic_type only be allow use this operator: 'x' \n\n");
							exit 1;
						}
						if (is_numeric(@split_data[1]) == 0) {
							next;
						}
						@data_module = ("",@split_data[0],@split_data[1]);
						my $text_data = join(',',@data_module);
						push (@filterdata,$text_data);
					}
					else {
						if (scalar(@split_data) == 2) {
							@data_module = (safe_output(@split_data[0]),'',safe_output(@split_data[1]));
							my $text_data = join(',',@data_module);
							push (@filterdata,$text_data);
						}
						else {
							if (length(@split_data[1]) > 1 ) {
								print("[ERROR] You can only use +, -, *, / or x, and you use this: @split_data[1] \n\n");
								exit 1;
							}
							if ( @split_data[1] =~ m/(\/|\+|\*|\-)/ && $synthetic_type eq 'average' ) {
								print("[ERROR] With this type: $synthetic_type only be allow use this operator: 'x' \n\n");
								exit 1;
							}
							if ( $synthetic_type eq 'arithmetic' && $i == 0) {
								@data_module = (safe_output(@split_data[0]),'',safe_output(@split_data[2]));
							}
							else {
								@data_module = (safe_output(@split_data[0]),@split_data[1],safe_output(@split_data[2]));
							}
							my $text_data = join(',',@data_module);
							push (@filterdata,$text_data);
						}
					}
				}

				my $module_exists = get_agent_module_id($dbh, $name_module, $id_agent);
				non_exist_check($module_exists, 'module name', $name_module);

				$module->{'id_agente'} = $id_agent;
				$module->{'nombre'} = safe_input($name_module);
				my $id_tipo_modulo = get_db_value ($dbh, "SELECT id_tipo FROM ttipo_modulo WHERE nombre = ?", "generic_data");
				$module->{'id_modulo'} = 5;
				$module->{'id_tipo_modulo'} = $id_tipo_modulo;

				my $id_module = db_process_insert($dbh, 'id_agente_modulo', 'tagente_modulo', $module);

				if ($id_module) {
					my $result = enterprise_hook('create_synthetic_operations_by_alias',
						[$dbh,int($id_module), @filterdata]);

					if ($result) {

						db_do ($dbh, 'INSERT INTO tagente_estado (id_agente_modulo, id_agente, estado,
						 known_status, last_status, last_known_status, last_try, datos) 
						 VALUES (?, ?, ?, ?, ?, ?, \'1970-01-01 00:00:00\', \'\')', $id_module, $id_agent, 4, 4, 4, 4);
						# Update the module status count. When the module is created disabled dont do it
						pandora_mark_agent_for_module_update ($dbh, $id_agent);
						print("[OK] Created module ID: $id_module \n\n");
					}
					else {
						#db_do ($dbh, 'DELETE FROM tagente_modulo WHERE id_agente_modulo = ?', $id_module);
						print("[ERROR] Problems with creating data module. \n\n");
					}
				}
				else {
					db_do ($dbh, 'DELETE FROM tagente_modulo WHERE nombre = ? AND id_agente = ?', $name_module, $id_agent);
					print("[INFO] Problems with creating module \n\n");
				}
			}
			else {
				print( "[INFO] The agent '$id->{'nombre'}' doesn't exist\n\n");
			}
		}
	} else {
		my $id_agent = int(get_agent_id($dbh,$agent_name));

		if ($id_agent > 0) {
			foreach my $i (0 .. $#module_data) {
				my @split_data = split(',',$module_data[$i]);
				if (@split_data[0] =~ m/(x|\/|\+|\*|\-)/ && length(@split_data[0]) == 1 ) {
					if ( @split_data[0] =~ m/(\/|\+|\*|\-)/ && $synthetic_type eq 'average' ) {
						print("[ERROR] With this type: $synthetic_type only be allow use this operator: 'x' \n\n");
						exit 1;
					}
					if (is_numeric(@split_data[1]) == 0) {
						next;
					}
					@data_module = ("",@split_data[0],@split_data[1]);
					my $text_data = join(',',@data_module);
					push (@filterdata,$text_data);
				}
				else {
					if (scalar(@split_data) == 2) {
						@data_module = (safe_output(@split_data[0]),'',safe_output(@split_data[1]));
						my $text_data = join(',',@data_module);
						push (@filterdata,$text_data);
					}
					else {
						if (length(@split_data[1]) > 1 ) {
							print("[ERROR] You can only use +, -, *, / or x, and you use this: @split_data[1] \n\n");
							exit 1;
						}
						if ( @split_data[1] =~ m/(\/|\+|\*|\-)/ && $synthetic_type eq 'average' ) {
							print("[ERROR] With this type: $synthetic_type only be allow use this operator: 'x' \n\n");
							exit 1;
						}
						if ( $synthetic_type eq 'arithmetic' && $i == 0) {
							@data_module = (safe_output(@split_data[0]),'',safe_output(@split_data[2]));
						}
						else {
							@data_module = (safe_output(@split_data[0]),@split_data[1],safe_output(@split_data[2]));
						}
						
						my $text_data = join(',',@data_module);
						push (@filterdata,$text_data);
					}
				}
			}

			my $module_exists = get_agent_module_id($dbh, $name_module, $id_agent);
			non_exist_check($module_exists, 'module name', $name_module);
			
			$module->{'id_agente'} = $id_agent;
			$module->{'nombre'} = safe_input($name_module);
			my $id_tipo_modulo = get_db_value ($dbh, "SELECT id_tipo FROM ttipo_modulo WHERE nombre = ?", "generic_data");
			$module->{'id_modulo'} = 5;
			$module->{'id_tipo_modulo'} = $id_tipo_modulo;
			
			my $id_module = db_process_insert($dbh, 'id_agente_modulo', 'tagente_modulo', $module);
			
			if ($id_module) {
				my $result = enterprise_hook('create_synthetic_operations',
					[$dbh,int($id_module), @filterdata]);
				if ($result) {
					db_do ($dbh, 'INSERT INTO tagente_estado (id_agente_modulo, id_agente, estado,
					 known_status, last_status, last_known_status, last_try, datos) 
					 VALUES (?, ?, ?, ?, ?, ?, \'1970-01-01 00:00:00\', \'\')', $id_module, $id_agent, 4, 4, 4, 4);
					# Update the module status count. When the module is created disabled dont do it
					pandora_mark_agent_for_module_update ($dbh, $id_agent);
					print("[OK] Created module ID: $id_module \n\n");
				}
				else {
					db_do ($dbh, 'DELETE FROM tagente_modulo WHERE id_agente_modulo = ?', $id_module);
					print("[ERROR] Problems with creating data module. \n\n");
				}
			}
			else {
				db_do ($dbh, 'DELETE FROM tagente_modulo WHERE nombre = ? AND id_agente = ?', $name_module, $id_agent);
				print("[INFO] Problems with creating module \n\n");
			}
		}
		else { 
			print( "[INFO] The agent '$agent_name' doesn't exist\n\n");
		}
	}


}


########################################################################
# Show all the modules of a policy
# Related option: --get_policy_modules
########################################################################

sub cli_get_policy_modules() {
	my $policy_name = @ARGV[2];
	
	my $policy_id = enterprise_hook('get_policy_id',
		[$dbh, safe_input($policy_name)]);
	exist_check($policy_id, 'policy', $policy_name);
	
	my $policy_modules = enterprise_hook(
		'get_policy_modules', [$dbh, $policy_id]);
	
	if (defined($policy_modules)) {
		exist_check(scalar(@{$policy_modules}) - 1, 'modules in policy',
			$policy_name);
	}
	
	print "id_policy_module, module_name\n";
	foreach my $module (@{$policy_modules}) {
		print $module->{'id'} . "," . safe_output($module->{'name'}) . "\n";
	}
}

########################################################################
# Show all the policies (without parameters) or the policies of given
# agent.
# Related option: --get_policies
########################################################################

sub cli_get_policies() {
	my ($agent_name, $use_alias) = @ARGV[2..3];
	my $policies;
	
	if (defined $use_alias and $use_alias eq 'use_alias') {
		my @id_agents = get_agent_ids_from_alias($dbh,$agent_name);

		foreach my $id (@id_agents) {
			if (defined($agent_name)) {
				my $id_agent = $id->{'id_agente'};
				exist_check($id_agent,'agent',$agent_name);
				
				$policies = enterprise_hook('get_agent_policies', [$dbh,$id_agent]);
				
				if (scalar(@{$policies}) == 0) {
					print_log "[INFO] No policies found on agent $id->{'nombre'}\n\n";
					exit;
				}
			}
			else {
				$policies = enterprise_hook('get_policies', [$dbh]);
				if (scalar(@{$policies}) == 0) {
					print_log "[INFO] No policies found\n\n";
					exit;
				}
			}
			
			print "agent_name, id_policy, policy_name\n";
			foreach my $module (@{$policies}) {
				print $id->{'nombre'}.",".$module->{'id'}.",".safe_output($module->{'name'})."\n";
			}	
		}
	} else {
		if (defined($agent_name)) {
			my $id_agent = get_agent_id($dbh,$agent_name);
			exist_check($id_agent,'agent',$agent_name);
			
			$policies = enterprise_hook('get_agent_policies', [$dbh,$id_agent]);
			
			if (scalar(@{$policies}) == 0) {
				print_log "[INFO] No policies found on agent '$agent_name'\n\n";
				exit;
			}
		}
		else {
			$policies = enterprise_hook('get_policies', [$dbh]);
			if (scalar(@{$policies}) == 0) {
				print_log "[INFO] No policies found\n\n";
				exit;
			}
		}
		
		print "id_policy, policy_name\n";
		foreach my $module (@{$policies}) {
			print $module->{'id'}.",".safe_output($module->{'name'})."\n";
		}
	}

}

##############################################################################
# Show all the agents (without parameters) or the agents with a filter parameters
# Related option: --get_agents
##############################################################################

sub cli_get_agents() {
	my ($group_name, $os_name, $status, $max_modules, $filter_substring, $policy_name, $use_alias) = @ARGV[2..8];

	my $condition = ' disabled=0';

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
		if (defined $use_alias and $use_alias eq 'use_alias') {
			$condition .= " AND alias LIKE '%".safe_input($filter_substring)."%'";
		} else {
			$condition .= " AND nombre LIKE '%".safe_input($filter_substring)."%'";
		}
	}
		
	my @agents = get_db_rows ($dbh, "SELECT * FROM tagente WHERE $condition");	

	if(scalar(@agents) == 0) {
		print_log "[INFO] No agents found\n\n";
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
		print_log "[INFO] No agents found\n\n";
	}
}

##############################################################################
# Delete agent conf.
# Related option: --delete_conf_file
##############################################################################

sub cli_delete_conf_file() {
	my ($agent_name,$use_alias) = @ARGV[2..3];

	my $conf_deleted = 0;
	my $md5_deleted = 0;

	if (defined $use_alias and $use_alias eq 'use_alias') {
		my @id_agents = get_agent_ids_from_alias($dbh,$agent_name);

		foreach my $id (@id_agents) {
			$agent_name = $id->{'nombre'};

			if (-e $conf->{incomingdir}.'/conf/'.md5($agent_name).'.conf') {
				unlink($conf->{incomingdir}.'/conf/'.md5($agent_name).'.conf');
				$conf_deleted = 1;
			}
			if (-e $conf->{incomingdir}.'/md5/'.md5($agent_name).'.md5') {
				unlink($conf->{incomingdir}.'/md5/'.md5($agent_name).'.md5');
				$md5_deleted = 1;
			}
			
			if($conf_deleted == 1 || $md5_deleted == 1) {
				print_log "[INFO] Local conf files of the agent '$agent_name' has been deleted successfully\n\n";
			}
			else {
				print_log "[ERROR] Local conf file of the agent '$agent_name' was not found\n\n";
				exit;
			}
		}
	} else {
		if (-e $conf->{incomingdir}.'/conf/'.md5($agent_name).'.conf') {
			unlink($conf->{incomingdir}.'/conf/'.md5($agent_name).'.conf');
			$conf_deleted = 1;
		}
		if (-e $conf->{incomingdir}.'/md5/'.md5($agent_name).'.md5') {
			unlink($conf->{incomingdir}.'/md5/'.md5($agent_name).'.md5');
			$md5_deleted = 1;
		}
		
		if($conf_deleted == 1 || $md5_deleted == 1) {
			print_log "[INFO] Local conf files of the agent '$agent_name' has been deleted successfully\n\n";
		}
		else {
			print_log "[ERROR] Local conf file of the agent '$agent_name' was not found\n\n";
			exit;
		}
	}
}

##############################################################################
# Delete modules from all conf files (without parameters) or of the conf file of the given agent.
# Related option: --clean_conf_file
##############################################################################

sub cli_clean_conf_file() {
	my ($agent_name,$use_alias) = @ARGV[2..3];
	my $result;
	
	if(defined($agent_name)) {
		if (defined $use_alias and $use_alias eq 'use_alias') {
			my @id_agents = get_agent_ids_from_alias($dbh,$agent_name);

			foreach my $id (@id_agents) {
				$agent_name = $id->{'nombre'};

				if (-e $conf->{incomingdir}.'/conf/'.md5($agent_name).'.conf') {
					$result = enterprise_hook('pandora_clean_conf_file',[$conf, md5($agent_name)]);
					if($result != -1) {
						print_log "[INFO] Conf file '".$conf->{incomingdir}.'/conf/'.md5($agent_name).".conf has been cleaned'\n\n";
					}
				}
			}
		} else {
			if (-e $conf->{incomingdir}.'/conf/'.md5($agent_name).'.conf') {
				$result = enterprise_hook('pandora_clean_conf_file',[$conf, md5($agent_name)]);
				if($result != -1) {
					print_log "[INFO] Conf file '".$conf->{incomingdir}.'/conf/'.md5($agent_name).".conf has been cleaned'\n\n";
				}
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
				print_log "[INFO] Conf file '".$conf->{incomingdir}.'/conf/'.$filesplit[0].".conf has been cleaned'\n\n";
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
		
		if ($file !~ /.srv./) {
			foreach my $token (@tokens) {
				my $result = enterprise_hook('pandora_check_conf_token', [$conf->{incomingdir}.'/conf/'.$file, $token]);
				
				if($result  == 0) {
					$missings++;
				}
				elsif ($result  == -1) {
					print_log "[WARN] File not exists /conf/".$file."\n\n";
					$bad_files++;
					last;
				}
				elsif(!defined $result) {
					print_log "[WARN] Can't open file /conf/".$file."\n\n";
					$bad_files++;
					last;
				}
			}
			
			# If any token of checked is missed we print the file path
			if($missings > 0) {
				print $conf->{incomingdir}.'/conf/'.$file."\n";
				$bad_files++;
			}
		}
	}
	
	if($bad_files == 0) {
		print_log "[INFO] No bad files found\n\n";
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
	my ($agent_name, $policy_name, $use_alias) = @ARGV[2..4];
	
	if (defined $use_alias and $use_alias eq 'use_alias') {
		my @id_agents = get_agent_ids_from_alias($dbh,$agent_name);

		foreach my $id (@id_agents) {
			my $agent_id = $id->{'id_agente'};
			exist_check($agent_id,'agent',$agent_name);
			
			my $policy_id = enterprise_hook('get_policy_id',[$dbh, safe_input($policy_name)]);
			exist_check($policy_id,'policy',$policy_name);
				
			# Add the agent to policy
			my $policy_agent_id = enterprise_hook('pandora_policy_add_agent',[$policy_id, $agent_id, $dbh]);
			
			if($policy_agent_id == -1) {
				print_log "[ERROR] A problem has been ocurred adding agent $id->{'nombre'} to policy '$policy_name'\n\n";
			}
			else {
				print_log "[INFO] Added agent $id->{'nombre'} to policy $policy_name. Is necessary to apply the policy in order to changes take effect.\n\n";
			}
		}
	} else {
		my $agent_id = get_agent_id($dbh,$agent_name);
		exist_check($agent_id,'agent',$agent_name);
		
		my $policy_id = enterprise_hook('get_policy_id',[$dbh, safe_input($policy_name)]);
		exist_check($policy_id,'policy',$policy_name);
			
		# Add the agent to policy
		my $policy_agent_id = enterprise_hook('pandora_policy_add_agent',[$policy_id, $agent_id, $dbh]);
		
		if($policy_agent_id == -1) {
			print_log "[ERROR] A problem has been ocurred adding agent '$agent_name' to policy '$policy_name'\n\n";
		}
		else {
			print_log "[INFO] Added agent '$agent_name' to policy '$policy_name'. Is necessary to apply the policy in order to changes take effect.\n\n";
		}
	}

}

##############################################################################
# delete an agent to a policy
# Related option: --remove_agent_from_policy
##############################################################################

sub cli_policy_delete_agent() {
	my ($policy_id, $agent_id) = @ARGV[2..3];
	
	my $result = manage_api_call(\%conf,'set', 'remove_agent_from_policy', $policy_id, $agent_id);
	print "$result \n\n ";

}

sub cli_create_planned_downtime() {
	my $name = @ARGV[2];
	my @todo = @ARGV[3..21];
	my $other = join('|', @todo);
	
	my $result = manage_api_call(\%conf,'set', 'planned_downtimes_created', $name, undef, "$other");
	print "$result \n\n ";
}

sub cli_add_item_planned_downtime() {
	my $id = @ARGV[2];
	my $agent = @ARGV[3];
	my $moduls = @ARGV[4];
	my @agents = split /,/, $agent;
	my @modules = split /,/, $moduls;
	my $other_agents = join(';', @agents);
	my $other_modules = join(';', @modules);
	my $other = $other_agents . "|" . $other_modules;
	
	my $result = manage_api_call(\%conf,'set', 'planned_downtimes_additem', $id, undef, "$other");
	print_log "$result \n\n";
}

sub cli_set_delete_planned_downtime() {
	my $name_downtime = @ARGV[2];
	my $id_downtime = pandora_get_planned_downtime_id($dbh,$name_downtime);
	
	my $result = pandora_delete_planned_downtime ($dbh,$id_downtime);
	
	print_log "$result \n\n";
}

sub cli_get_all_planned_downtime() {
	my $name_downtime = @ARGV[2];
	my ($id_group, $type_downtime, $type_execution, $type_periodicity) = @ARGV[3..6];
	
	my @results = pandora_get_all_planned_downtime($dbh, $name_downtime, $id_group, $type_downtime, $type_execution, $type_periodicity);
	
	if (!defined($results[0])) {
		print_log "[ERROR] No data found with this parameters. Please check and launch again\n\n";
	}	
	else {
		foreach my $result (@results) {
			print("\nID: " . $result->{'id'} . ", NAME: " . $result->{'name'} . ", DESC: " . safe_output($result->{'description'}) . ", DATE FROM: " .
						localtime($result->{'date_from'}) . " DATE TO: " . localtime($result->{'date_to'}) .
						" \nID GROUP: " .  $result->{'id_group'} . ", MONDAY:  " . $result->{'monday'} . ", TUESDAY: " . $result->{'tuesday'}  .
						", WEDNESDAY: " .  $result->{'wednesday'} . ", THURSDAY: " .  $result->{'thursday'} . ", FRIDAY: " . $result->{'friday'}  .
						", SATURDAY: " . $result->{'saturday'} .", SUNDAY: " . $result->{'sunday'} .", PEDIODICALLY TIME FROM: " . $result->{'periodically_time_from'} .
						" \nPEDIODICALLY TIME TO: " . $result->{'periodically_time_to'} . ", PEDIODICALLY DAY FROM: " . $result->{'periodically_day_from'} .
						"PEDIODICALLY DAY TO: " . $result->{'periodically_day_to'} . ", TYPE DOWNTIME: " . $result->{'type_downtime'} .
						", TYPE OF EXECUTION: " . $result->{'type_execution'} . "\nTYPE OF PERIODICITY:  " . $result->{'type_periodicity'} .
						", USER: " . $result->{'id_user'} ."\n\n");
		}
	}
}

sub cli_get_planned_downtimes_items() {
	my $name_downtime = @ARGV[2];
	my ($id_group, $type_downtime, $type_execution, $type_periodicity) = @ARGV[3..6];
	my $text;
	my @results = pandora_get_all_planned_downtime($dbh, $name_downtime, $id_group, $type_downtime, $type_execution, $type_periodicity);
	
	if (!defined($results[0])) {
		print_log "[ERROR] No data found with this parameters. Please check and launch again\n\n";
	}	
	else {
		my @items;
		foreach my $result (@results) {
			print(" ITEMS OF $result->{'name'} \n ");
			@items = pandora_get_planned_downtimes_items($dbh,$result);
			foreach my $item (@items) {
				if ( $item->{'modules'} != '' ){
					$text = " This Agent have this MODULES ID: " . $item->{"modules"};
				}else{
					$text = " All modules quiet of this agent";
				}
				print("AGENT ID: " . $item->{"id_agent"} . $text ."\n ");
			}
		}
	}
}


##############################################################################
# Create group
# Related option: --create_group
##############################################################################

sub cli_create_group() {
	my ($group_name,$parent_group_name,$icon,$description) = @ARGV[2..5];

	if(is_metaconsole($conf) != 1 && pandora_get_tconfig_token ($dbh, 'centralized_management', '')) {
		print_log "[ERROR] This node is configured with centralized mode. To create a group go to metaconsole. \n\n";
		exit;
	}

	my $group_id = get_group_id($dbh,$group_name);
	non_exist_check($group_id, 'group name', $group_name);
	
	my $parent_group_id = 0;
	
	if(defined($parent_group_name) && $parent_group_name ne 'All') {
		$parent_group_id = get_group_id($dbh,$parent_group_name);
		exist_check($parent_group_id, 'group name', $parent_group_name);
	}

	$icon = '' unless defined($icon);
	$description = '' unless defined($description);

	$group_id = pandora_create_group ($group_name, $icon, $parent_group_id, 0, 0, '', 0, $description, $dbh);
	
	if($group_id == -1) {
		print_log "[ERROR] A problem has been ocurred creating group '$group_name'\n\n";
	}
	else {		
		if (is_metaconsole($conf) == 1) {
			my $servers = enterprise_hook('get_metaconsole_setup_servers',[$dbh]);
			my @servers_id = split(',',$servers);
			my $count_error = 0;
			my $count_success = 0;
			foreach my $server (@servers_id) {
				my $dbh_metaconsole = enterprise_hook('get_node_dbh',[$conf, $server, $dbh]);
				my $group_id_nodo;
				
				my $group_id = get_group_id($dbh_metaconsole,$group_name);
				
				if ($group_id != -1) {
					$count_error++;
					next;
				}
				
				eval {
					$group_id_nodo = db_insert ($dbh_metaconsole, 'id_grupo', 'INSERT INTO tgrupo (id_grupo, nombre, icon, parent, propagate, disabled,
							custom_id, id_skin, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', $group_name, safe_input($group_name), $icon, 
							$parent_group_id, 0, 0, '', 0, $description);
				};
				if ($@) {
					print_log "[ERROR] Problems with IDS and doesn't created group\n\n";
					$count_error++;
					next;
				}
				
				if ($group_id_nodo == -1) {
					$count_error++;
				}
				else {
					$count_success++;
				}
			}
			
			print_log "[INFO] Created group success: $count_success error: $count_error\n\n";
		}
		else {
			print_log "[INFO] Created group '$group_name'\n\n";
		}
	}
}


##############################################################################
# Delete group
# Related option: --delete_group
##############################################################################

sub cli_delete_group() {
	my ($group_name) = @ARGV[2];

	if(is_metaconsole($conf) != 1 && pandora_get_tconfig_token ($dbh, 'centralized_management', '')) {
		print_log "[ERROR] This node is configured with centralized mode. To delete a group go to metaconsole. \n\n";
		exit;
	}

	my $group_id = get_group_id($dbh,$group_name);
	exist_check($group_id, 'group name', $group_name);

	$group_id = db_do ($dbh, 'DELETE FROM tgrupo WHERE nombre=?', safe_input($group_name));

	# Delete on nodes too if metaconsole.
	if(is_metaconsole($conf) == 1 && pandora_get_tconfig_token ($dbh, 'centralized_management', '')) {
		my $servers = enterprise_hook('get_metaconsole_setup_servers',[$dbh]);
		my @servers_id = split(',',$servers);

	foreach my $server (@servers_id) {

		my $dbh_node = enterprise_hook('get_node_dbh',[$conf, $server, $dbh]);

		my $group_id = get_group_id($dbh_node,$group_name);
		exist_check($group_id, 'group name', $group_name);

		$group_id = db_do ($dbh_node, 'DELETE FROM tgrupo WHERE nombre=?', safe_input($group_name));

		}
	}


	if($group_id == -1) {
		print_log "[ERROR] A problem has been ocurred deleting group '$group_name'\n\n";
	}else{
		print_log "[INFO] Deleted group '$group_name'\n\n";
	}


}


##############################################################################
# Update group
# Related option: --update_group
##############################################################################

sub cli_update_group() {
	my ($group_id,$group_name,$parent_group_name,$icon,$description) = @ARGV[2..6];
	my $result;

	if(is_metaconsole($conf) != 1 && pandora_get_tconfig_token ($dbh, 'centralized_management', '')) {
		print_log "[ERROR] This node is configured with centralized mode. To update a group go to metaconsole. \n\n";
		exit;
	}

	$result = get_db_value ($dbh, 'SELECT * FROM tgrupo WHERE id_grupo=?', $group_id);

	if($result == "0E0"){
		print_log "[ERROR] Group '$group_id' doesn`t exist \n\n";
	}else{
		if(defined($group_name)){
			if(defined($parent_group_name)){

				my $parent_group_id = 0;

				if($parent_group_name ne 'All') {
						$parent_group_id = get_group_id($dbh,$parent_group_name);
						exist_check($parent_group_id, 'group name', $parent_group_name);				
				} 
					
				if(defined($icon)){
					if(defined($description)){
						db_do ($dbh,'UPDATE tgrupo SET nombre=? , parent=? , icon=? , description=? WHERE id_grupo=?',$group_name,$parent_group_id,$icon,$description,$group_id);
					}else{
						db_do ($dbh,'UPDATE tgrupo SET nombre=? , parent=? , icon=? WHERE id_grupo=?',$group_name,$parent_group_id,$icon,$group_id);
					}
				}else{
					db_do ($dbh,'UPDATE tgrupo SET nombre=? , parent=? WHERE id_grupo=?',$group_name,$parent_group_id,$group_id);
				}
			}else{
				db_do ($dbh,'UPDATE tgrupo SET nombre=? WHERE id_grupo=?',$group_name,$group_id);
			}
			print_log "[INFO] Updated group '$group_id'\n\n";
		}
	}
}


###############################################################################
# Returns the Nodes ID where the agent is defined (Metaconsole only)
# Related option: --locate_agent
###############################################################################
sub cli_locate_agent () {
	my ($agent_name, $use_alias) = @ARGV[2..3];

	if (is_metaconsole($conf) == 1) {

		if (defined $use_alias and $use_alias eq 'use_alias') {
			my $servers = enterprise_hook('get_metaconsole_setup_servers',[$dbh]);
			my @servers_id = split(',',$servers);
			my @list_servers;
			my $list_names_servers;
			my @id_agents;
			foreach my $server (@servers_id) {
				my $dbh_metaconsole = enterprise_hook('get_node_dbh',[$conf, $server, $dbh]);
				
				@id_agents = get_agent_ids_from_alias($dbh_metaconsole,$agent_name);
				
				foreach my $id (@id_agents) {
					if ($id->{'id_agente'} == -1) {
						next;
					}
					else {
						push @list_servers,$server;
						last;
					}
				}
			}
			
			if (scalar(@list_servers) > 0) {
				$list_names_servers = join(',',@list_servers);
				print_log "[INFO] One or more agents with the alias '$agent_name' were found in server with IDS: $list_names_servers\n\n";
			}
			else {
				print_log "[ERROR] No agent with alias '$agent_name' found in any node\n\n";
			}
		} else {
			my $agents_server = enterprise_hook('get_metaconsole_agent',[$dbh, $agent_name]);

			if (scalar(@{$agents_server}) != 0) {
				foreach my $agent (@{$agents_server}) {
					#my $server = enterprise_hook('get_metaconsole_setup_server_id',[$dbh, $agent->{'server_name'}]);
					print $agent->{'id_tmetaconsole_setup'} . "\n";
				}
			}
			else {
				my $servers = enterprise_hook('get_metaconsole_setup_servers',[$dbh]);
				my @servers_id = split(',',$servers);
				my @list_servers;
				my $list_names_servers;
				foreach my $server (@servers_id) {
					my $dbh_metaconsole = enterprise_hook('get_node_dbh',[$conf, $server, $dbh]);
					
					my $agent_id = get_agent_id($dbh_metaconsole,$agent_name);
					
					if ($agent_id == -1) {
						next;
					}
					else {
						push @list_servers,$server;
					}
				}
				
				if (scalar(@list_servers) > 0) {
					$list_names_servers = join(',',@list_servers);
					print_log "[INFO] Agent '$agent_name' found in server with IDS: $list_names_servers\n\n";
				}
				else {
					print_log "[ERROR] Agent '$agent_name' not found in any node\n\n";
				}
			}
		}

	}
	else {
		print_log "[ERROR] This function can only be used in metaconsole\n\n";
	}
}

###############################################################################
# Disable alert system globally
# Related option: --disable_alerts
###############################################################################
sub cli_disable_alerts ($$) {
	my ($conf, $dbh) = @_;

	print_log "[INFO] Disabling all alerts \n\n";

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

	print_log "[INFO] Enabling all alerts \n\n";

	db_do ($dbh, "UPDATE tgrupo SET disabled = 0");

    exit;
}

###############################################################################
# Disable enterprise ACL
# Related option: --disable_eacl
###############################################################################
sub cli_disable_eacl ($$) {
	my ($conf, $dbh) = @_;
			
	print_log "[INFO] Disabling Enterprise ACL system (system wide)\n\n";

	db_do ($dbh, "UPDATE tconfig SET `value` ='0' WHERE `token` = 'acl_enterprise'");

    exit;
}

###############################################################################
# Enable enterprise ACL
# Related option: --enable_eacl
###############################################################################
sub cli_enable_eacl ($$) {
	my ($conf, $dbh) = @_;

	print_log "[INFO] Enabling Enterprise ACL system (system wide)\n\n";

    db_do ($dbh, "UPDATE tconfig SET `value` ='1' WHERE `token` = 'acl_enterprise'");
    	
    exit;
}

###############################################################################
# Disable double authentication
# Related option: --disable_double_auth
###############################################################################
sub cli_disable_double_auth () {
	my $user_id = @ARGV[2];

	print_log "[INFO] Disabling double authentication for the user '$user_id'\n\n";

	$user_id = safe_input($user_id);
	
	# Delete the user secret
	my $result = db_do ($dbh, 'DELETE FROM tuser_double_auth WHERE id_user = ?', $user_id);
	
	exit;
}

###############################################################################
# Enable user
# Related option: --enable_user
###############################################################################
sub cli_user_enable () {
	my $user_id = @ARGV[2];

	my $centralized = pandora_get_tconfig_token ($dbh, 'centralized_management', '');

	if(is_metaconsole($conf) != 1 && $centralized) {
		print_log "[ERROR] This node is configured with centralized mode. To create a user go to metaconsole. \n\n";
		exit;
	}

	my $user_disabled = get_user_disabled ($dbh, $user_id);
	
	exist_check($user_disabled,'user',$user_id);

	if($user_disabled == 0) {
		print_log "[INFO] The user '$user_id' is already enabled. Nothing to do.\n\n";
		exit;
	}
	
	print_log "[INFO] Enabling user '$user_id'\n\n";

	$user_id = safe_input($user_id);

  my $result = db_do ($dbh, "UPDATE tusuario SET disabled = '0' WHERE id_user = '$user_id'");  	

	if(is_metaconsole($conf) == 1 && $centralized) {
		my @values;
		db_synch_update($dbh, $conf, 'tusuario', $dbh->{Statement}, $result, @values);
	}

  exit;
}

###############################################################################
# Disable user
# Related option: --disable_user
###############################################################################
sub cli_user_disable () {
	my $user_id = @ARGV[2];

	my $centralized = pandora_get_tconfig_token ($dbh, 'centralized_management', '');

	if(is_metaconsole($conf) != 1 && $centralized) {
		print_log "[ERROR] This node is configured with centralized mode. To create a user go to metaconsole. \n\n";
		exit;
	}

	my $user_disabled = get_user_disabled ($dbh, $user_id);
	
	exist_check($user_disabled,'user',$user_id);

	if($user_disabled == 1) {
		print_log "[INFO] The user '$user_id' is already disabled. Nothing to do.\n\n";
		exit;
	}
	
	print_log "[INFO] Disabling user '$user_id'\n\n";

	$user_id = safe_input($user_id);
	
  my $result = db_do ($dbh, "UPDATE tusuario SET disabled = '1' WHERE id_user = '$user_id'");

	if(is_metaconsole($conf) == 1 && $centralized) {
		my @values;
		db_synch_update($dbh, $conf, 'tusuario', $dbh->{Statement}, $result, @values);
	}
    	
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
	
	my $data = get_db_single_row ($dbh, 'SELECT  date_to, type_execution, executed FROM tplanned_downtime WHERE id=?', $downtime_id);

	if( $data->{'type_execution'} eq 'periodically' && $data->{'executed'} == 1){
		print_log "[ERROR] Planned_downtime '$downtime_name' cannot be stopped.\n";
		print_log "[INFO] Periodical and running planned downtime cannot be stopped.\n\n";
		exit;
	}
	
	if($current_time >= $data->{'date_to'}) {
		print_log "[INFO] Planned_downtime '$downtime_name' is already stopped\n\n";
		exit;
	}

	print_log "[INFO] Stopping planned downtime '$downtime_name'\n\n";
		
	my $parameters->{'date_to'} = time;
		
	db_process_update($dbh, 'tplanned_downtime', $parameters, {'id' => $downtime_id});
}

###############################################################################
# Get module data
# Related option: --get_module_data
###############################################################################
sub cli_module_get_data () {
	my ($agent_name, $module_name, $interval, $csv_separator, $use_alias) = @ARGV[2..6];
	
	$csv_separator = '|' unless defined($csv_separator);
	
	if ($interval <= 0) {
		print_log "[ERROR] Interval must be a possitive value\n\n";
		exit;
	}

	my @id_agents;
	
	if (defined $use_alias and $use_alias eq 'use_alias') {
		@id_agents = get_agent_ids_from_alias($dbh,$agent_name);

		my $agent_id;

		foreach my $id (@id_agents) {
			$agent_id = $id->{'id_agente'}; # se hace para cada agente
			exist_check($agent_id, 'agent name', $agent_name);
			
			my $module_id = get_agent_module_id($dbh, $module_name, $agent_id); # se hace para ada agente
			if ($module_id == -1) {
				next;
			}
			
			my $id_agent_module = get_agent_module_id ($dbh, $module_name, $agent_id); # 6
			
			my $module_type_id = get_db_value($dbh,
				"SELECT id_tipo_modulo FROM tagente_modulo WHERE id_agente_modulo = ?",
				$id_agent_module); # se hace para cada agente
			
			my $module_type = get_db_value($dbh,
				"SELECT nombre FROM ttipo_modulo WHERE id_tipo = ?",
				$module_type_id); # se hace para cada agente
			
			my @data = NULL;
			if ($module_type eq "log4x") {
				@data = get_db_rows ($dbh, "SELECT utimestamp, datos 
					FROM tagente_datos_log4x 
					WHERE id_agente_modulo = $id_agent_module 
					AND utimestamp > (UNIX_TIMESTAMP(NOW()) - $interval) 
					ORDER BY utimestamp DESC");
			}
			elsif ($module_type =~ m/_string/) {
				@data = get_db_rows ($dbh, "SELECT utimestamp, datos 
					FROM tagente_datos_string 
					WHERE id_agente_modulo = $id_agent_module 
					AND utimestamp > (UNIX_TIMESTAMP(NOW()) - $interval) 
					ORDER BY utimestamp DESC");
			}
			else {
				@data = get_db_rows ($dbh, "SELECT utimestamp, datos 
					FROM tagente_datos 
					WHERE id_agente_modulo = $id_agent_module 
					AND utimestamp > (UNIX_TIMESTAMP(NOW()) - $interval) 
					ORDER BY utimestamp DESC");
			}
			
			foreach my $data_timestamp (@data) {
				print $data_timestamp->{'utimestamp'};
				print $csv_separator;
				print $data_timestamp->{'datos'};
				print "\n";
			}
		}

	} else {
		my $agent_id = get_agent_id($dbh,$agent_name); # se hace para cada agente
		exist_check($agent_id, 'agent name', $agent_name);
		
		my $module_id = get_agent_module_id($dbh, $module_name, $agent_id); # se hace para ada agente
		exist_check($module_id, 'module name', $module_name);
		
		my $id_agent_module = get_agent_module_id ($dbh, $module_name, $agent_id); # 6
		
		my $module_type_id = get_db_value($dbh,
			"SELECT id_tipo_modulo FROM tagente_modulo WHERE id_agente_modulo = ?",
			$id_agent_module); # se hace para cada agente
		
		my $module_type = get_db_value($dbh,
			"SELECT nombre FROM ttipo_modulo WHERE id_tipo = ?",
			$module_type_id); # se hace para cada agente
		
		my @data = NULL;
		if ($module_type eq "log4x") {
			@data = get_db_rows ($dbh, "SELECT utimestamp, datos 
				FROM tagente_datos_log4x 
				WHERE id_agente_modulo = $id_agent_module 
				AND utimestamp > (UNIX_TIMESTAMP(NOW()) - $interval) 
				ORDER BY utimestamp DESC");
		}
		elsif ($module_type =~ m/_string/) {
			@data = get_db_rows ($dbh, "SELECT utimestamp, datos 
				FROM tagente_datos_string 
				WHERE id_agente_modulo = $id_agent_module 
				AND utimestamp > (UNIX_TIMESTAMP(NOW()) - $interval) 
				ORDER BY utimestamp DESC");
		}
		else {
			@data = get_db_rows ($dbh, "SELECT utimestamp, datos 
				FROM tagente_datos 
				WHERE id_agente_modulo = $id_agent_module 
				AND utimestamp > (UNIX_TIMESTAMP(NOW()) - $interval) 
				ORDER BY utimestamp DESC");
		}
		
		foreach my $data_timestamp (@data) {
			print $data_timestamp->{'utimestamp'};
			print $csv_separator;
			print $data_timestamp->{'datos'};
			print "\n";
		}
	}

	
	exit;
}

##############################################################################
# Enable or disable event flow protection
# Related option: --create_netflow_filter
##############################################################################
sub cli_set_event_storm_protection () {
	my $value = @ARGV[2];
	
	# Check for a valid value
	if ($value != 0 && $value != 1) {
		print_log "[ERROR] Invalid value: $value. Value must be either 0 or 1\n\n";
		return;
	}

	# Set the value of event
	db_do ($dbh, 'UPDATE tconfig SET value=? WHERE token=?', $value, 'event_storm_protection');
}

##############################################################################
# Set existing OS and OS version for a specific agent
# Related option: --agent_set_os
##############################################################################
sub cli_agent_set_os() {
	my ($id_agente,$id_os,$os_version) = @ARGV[2..4];

	my $os_name = get_db_value($dbh, 'SELECT name FROM tconfig_os WHERE id_os = ?',$id_os);
	exist_check($id_os,'tconfig_os',$os_name);

	db_process_update($dbh, 'tagente', {'id_os' => $id_os, 'os_version' => $os_version}, {'id_agente' => $id_agente});
}

##############################################################################
# Return event name given a event id
##############################################################################

sub pandora_get_event_name($$) {
	my ($dbh,$id_event) = @_;
	
	my $event_name = get_db_value($dbh, 'SELECT evento FROM tevento WHERE id_evento = ?',$id_event);
	
	return defined ($event_name) ? $event_name : -1;
}

##########################################################################
## Update event from hash
##########################################################################
sub pandora_update_event_from_hash ($$$$) {
	my ($parameters, $where_column, $where_value, $dbh) = @_;
	
	my $event_id = db_process_update($dbh, 'tevento', $parameters, {$where_column => $where_value});
	return $event_id;
}

##############################################################################
# Return event comment given a event id
##############################################################################

sub pandora_get_event_comments($$) {
	my ($dbh,$id_event) = @_;

	my @comments = get_db_rows($dbh, 'SELECT * FROM tevent_comment WHERE id_evento = ?',$id_event);

	return \@comments;
}

##############################################################################
# Return user id given a user name
##############################################################################

sub pandora_get_user_id($$) {
	my ($dbh,$user_name) = @_;
	
	my $user_id = get_db_value($dbh, 'SELECT id_user FROM tusuario WHERE id_user = ? or fullname = ?',$user_name, $user_name);
	
	return defined ($user_id) ? $user_id : -1;
}

##############################################################################
# Return network component id given the name
##############################################################################

sub pandora_get_network_component_id($$) {
	my ($dbh,$name) = @_;
	
	my $nc_id = get_db_value($dbh, 'SELECT id_nc FROM tnetwork_component WHERE name = ?',safe_input($name));
	
	return defined ($nc_id) ? $nc_id : -1;
}

##############################################################################
# Create special day
# Related option: --create_special_day
##############################################################################

sub cli_create_special_day() {
	my ($special_day, $calendar_name, $same_day, $description, $group_name) = @ARGV[2..5];
	my $calendar_name_exists = pandora_get_calendar_id ($dbh, $calendar_name);
	my $same_day_exists = pandora_get_same_day_id ($dbh, $same_day);
	my $special_day_exists = pandora_get_special_day_id ($dbh, $special_day);
	non_exist_check($special_day_exists,'special day',$special_day);
	non_exist_check($calendar_name_exists,'calendar name',$calendar_name);
	non_exist_check($same_day_exists,'same day',$same_day);

	my $group_id = 0;

	# If group name is not defined, we assign group All (0)
	if(defined($group_name)) {
		$group_id = get_group_id($dbh, decode('UTF-8', $group_name));
		exist_check($group_id,'group',$group_name);
	}
	else {
		$group_name = 'All';
	}

	if ($special_day !~ /^[0-9]{4}-[0-9]{2}-[0-9]{2}$/) {
		print_log "[ERROR] '$special_day' is invalid date format.\n\n";
		$param = '--create_special_day';
		help_screen ();
		exit 1;
	}

	if ($same_day !~ /monday|tuesday|wednesday|thursday|friday|saturday|sunday|holiday/) {
		print_log "[ERROR] '$same_day' is invalid day.\n\n";
		$param = '--create_special_day';
		help_screen ();
		exit 1;
	}

	my %parameters;

	$parameters{"${RDBMS_QUOTE}date${RDBMS_QUOTE}"} = $special_day;
	$parameters{'same_day'} = $same_day;
	$parameters{'description'} = decode('UTF-8', $description);
	$parameters{'id_group'} = $group_id;
	$parameters{'calendar_name'} = $calendar_name;

	pandora_create_special_day_from_hash ($conf, \%parameters, $dbh);
}

##############################################################################
# Update a special day.
# Related option: --update_special_day
##############################################################################

sub cli_update_special_day() {
	my ($special_day,$field,$new_value) = @ARGV[2..4];
	
	my $special_day_id = pandora_get_special_day_id ($dbh, $special_day);
	exist_check($special_day_id,'special day',$special_day);
	
	if($field eq 'date') {
		if ($new_value !~ /^[0-9]{4}-[0-9]{2}-[0-9]{2}$/) {
			print_log "[ERROR] '$new_value' is invalid date format.\n\n";
			$param = '--update_special_day';
			help_screen ();
			exit 1;
		}
	}
	elsif($field eq 'same_day') {
		if ($new_value !~ /monday|tuesday|wednesday|thursday|friday|saturday|sunday/) {
			print_log "[ERROR] '$new_value' is invalid day.\n\n";
			$param = '--update_special_day';
			help_screen ();
			exit 1;
		}
	}
	elsif($field eq 'description') {
		$new_value = decode('UTF-8', $new_value);
	}
	elsif($field eq 'group') {
		my $group_id = 0;

		$group_id = get_group_id($dbh, decode('UTF-8', $new_value));
		exist_check($group_id,'group',$new_value);

		$new_value = $group_id;
		$field = 'id_group';
	}
	else {
		print_log "[ERROR] Field '$field' doesn't exist\n\n";
		exit;
	}
		
	print_log "[INFO] Updating field '$field' in special day '$special_day'\n\n";
	
	my $update;
	
	$update->{$field} = $new_value;
	
	pandora_update_special_day_from_hash ($update, 'id', $special_day_id, $dbh);
}

##############################################################################
# Delete a special_day.
# Related option: --delete_special_day
##############################################################################

sub cli_delete_special_day() {
	my $special_day = @ARGV[2];
	
	print_log "[INFO] Deleting special day '$special_day' \n\n";
	
	my $result = pandora_delete_special_day($dbh,$special_day);
	exist_check($result,'special day',$special_day);
}

##############################################################################
# Creates a new visual console.
# Related option: --create_visual_console
##############################################################################

sub cli_create_visual_console() {
	my ($name,$background,$width,$height,$group,$mode,$element_square_positions,$background_color,$elements) = @ARGV[2..10];

	if($name eq '') {
		print_log "[ERROR] Name field cannot be empty.\n\n";
		exit 1;
	}
	elsif ($background eq '') {
		print_log "[ERROR] Background field cannot be empty.\n\n";
		exit 1;
	}
	elsif (($width eq '') || ($height eq '')) {
		print_log "[ERROR] Please specify size.\n\n";
		exit 1;
	}
	elsif ($group eq '') {
		print_log "[ERROR] Group field cannot be empty.\n\n";
		exit 1;
	}
	elsif ($mode eq '') {
		print_log "[ERROR] Mode parameter must be 'static_objects' or 'auto_creation'.\n\n";
		exit 1;
	}

	if ($background_color eq '') {
		$background_color = '#FFF';
	}

	print_log "[INFO] Creating visual console '$name' \n\n";

	my $vc_id = db_insert ($dbh, 'id', 'INSERT INTO tlayout (name, id_group, background, width, height, background_color)
                         VALUES (?, ?, ?, ?, ?, ?)', safe_input($name), $group, $background, $width, $height, $background_color);

	print_log "[INFO] The visual console id is '$vc_id' \n\n";

	if ($elements ne '') {
		my $elements_in_array = decode_json($elements);

		if ($mode eq 'static_objects') {
			my $elem_count = 1;

			foreach my $elem (@$elements_in_array) {
				my $pos_x = $elem->{'pos_x'};
				my $pos_y = $elem->{'pos_y'};
				my $width = $elem->{'width'};
				my $height = $elem->{'height'};
				my $label = $elem->{'label'};
				my $image = $elem->{'image'};
				my $type = $elem->{'type'};
				my $period = $elem->{'period'};
				my $id_agente_modulo = $elem->{'id_agente_modulo'};
				my $id_agent = $elem->{'id_agent'};
				my $id_layout_linked = $elem->{'id_layout_linked'};
				my $parent_item = 0;
				my $enable_link = $elem->{'enable_link'};
				my $id_metaconsole = $elem->{'id_metaconsole'};
				my $id_group = $elem->{'id_group'};
				my $id_custom_graph = $elem->{'id_custom_graph'};
				my $border_width = $elem->{'border_width'};
				my $type_graph = $elem->{'type_graph'};
				my $label_position = $elem->{'label_position'};
				my $border_color = $elem->{'border_color'};
				my $fill_color = $elem->{'fill_color'};
				my $show_statistics = $elem->{'fill_color'};
				my $id_layout_linked_weight = $elem->{'id_layout_linked_weight'};
				my $element_group = $elem->{'element_group'};
				my $show_on_top = $elem->{'show_on_top'};

				my $elem_id = db_insert ($dbh, 'id', 'INSERT INTO tlayout_data (id_layout, pos_x, pos_y, height, width, label, image, type, period, id_agente_modulo, id_agent, id_layout_linked, parent_item, enable_link, id_metaconsole, id_group, id_custom_graph, border_width, type_graph, label_position, border_color, fill_color, show_statistics, id_layout_linked_weight, element_group, show_on_top)
							VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', $vc_id, $pos_x, $pos_y, $height, $width, $label, $image, $type, $period, $id_agente_modulo, $id_agent, $id_layout_linked, $parent_item, $enable_link, $id_metaconsole, $id_group, $id_custom_graph, $border_width, $type_graph, $label_position, $border_color, $fill_color, 0, $id_layout_linked_weight, $element_group, $show_on_top);

				print_log "[INFO] The element id in position $elem_count is '$elem_id' \n\n";

				$elem_count++;
			}
		}
		elsif ($mode eq 'auto_creation') {
			if ($element_square_positions eq '') {
				print_log "[ERROR] With this mode, square positions is obligatory'.\n\n";
				exit 1;
			}
			else {
				my $positions = decode_json($element_square_positions);

				my $pos1X = $positions->{'pos1x'};
				my $pos1Y = $positions->{'pos1y'};
				my $pos2X = $positions->{'pos2x'};
				my $pos2Y = $positions->{'pos2y'};

				my $number_of_elements = scalar(@$elements_in_array);
				
				my $x_divider = 8;
				my $y_divider = 1;

				for (my $i = 1; $i <= 1000; $i++) {
					if (($i * 8) < $number_of_elements) {
						$y_divider++;
					}
					else {
						last;
					}
				}

				my $elem_width = ($pos2X - $pos1X) / $x_divider;
				my $elem_height = ($pos2Y - $pos1Y) / $y_divider;

				if ($number_of_elements <= 8) {
					$elem_height = ($pos2Y - $pos1Y) / 4;
				}

				my $elem_count = 1;
				my $pos_aux_count = 0;
				my $pos_helper_x = $pos1X;
				my $pos_helper_y = $pos1Y;
				foreach my $elem (@$elements_in_array) {
					my $pos_x = $pos_helper_x;
					my $pos_y = $pos_helper_y;
					my $width = $elem_width;
					my $height = $elem_height;
					my $label = $elem->{'label'};
					my $image = $elem->{'image'};
					my $type = $elem->{'type'};
					my $period = $elem->{'period'};
					my $id_agente_modulo = $elem->{'id_agente_modulo'};
					my $id_agent = $elem->{'id_agent'};
					my $id_layout_linked = $elem->{'id_layout_linked'};
					my $parent_item = $elem->{'parent_item'};
					my $enable_link = $elem->{'enable_link'};
					my $id_metaconsole = $elem->{'id_metaconsole'};
					my $id_group = $elem->{'id_group'};
					my $id_custom_graph = $elem->{'id_custom_graph'};
					my $border_width = $elem->{'border_width'};
					my $type_graph = $elem->{'type_graph'};
					my $label_position = $elem->{'label_position'};
					my $border_color = $elem->{'border_color'};
					my $fill_color = $elem->{'fill_color'};
					my $id_layout_linked_weight = $elem->{'id_layout_linked_weight'};
					my $element_group = $elem->{'element_group'};
					my $show_on_top = $elem->{'show_on_top'};
					

					my $elem_id = db_insert ($dbh, 'id', 'INSERT INTO tlayout_data (id_layout, pos_x, pos_y, height, width, label, image, type, period, id_agente_modulo, id_agent, id_layout_linked, parent_item, enable_link, id_metaconsole, id_group, id_custom_graph, border_width, type_graph, label_position, border_color, fill_color, show_statistics, id_layout_linked_weight, element_group, show_on_top)
								VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', $vc_id, $pos_x, $pos_y, $height, $width, $label, $image, $type, $period, $id_agente_modulo, $id_agent, $id_layout_linked, $parent_item, $enable_link, $id_metaconsole, $id_group, $id_custom_graph, $border_width, $type_graph, $label_position, $border_color, $fill_color, 0, $id_layout_linked_weight, $element_group, $show_on_top);

					print_log "[INFO] The element id in position $elem_count is '$elem_id' \n\n";

					$elem_count++;

					if ($pos_aux_count == 7) {
						$pos_helper_x = $pos1X;
						$pos_helper_y += $elem_height;
						$pos_aux_count = 0;
					}
					else {
						$pos_aux_count++;
						$pos_helper_x += $elem_width;
					}
				}
			}
		}
		else {
			print_log "[ERROR] Mode parameter must be 'static_objects' or 'auto_creation'.\n\n";
			exit 1;
		}
	}
}

##############################################################################
# Edit a visual console.
# Related option: --edit_visual_console
##############################################################################

sub cli_edit_visual_console() {
	my ($id,$name,$background,$width,$height,$group,$mode,$element_square_positions,$background_color,$elements) = @ARGV[2..11];

	if($id eq '') {
		print_log "[ERROR] ID field cannot be empty.\n\n";
		exit 1;
	}

	my $console = get_db_single_row ($dbh, "SELECT * 
			FROM tlayout 
			WHERE id = $id");

	my $new_name = $console->{'name'};
	my $new_background = $console->{'background'};
	my $new_console_width = $console->{'width'};
	my $new_console_height = $console->{'height'};
	my $new_console_id_group = $console->{'id_group'};
	my $new_background_color = $console->{'background_color'};

	if($name ne '') {
		$new_name = $name;
	}
	if ($background ne '') {
		$new_background = $background;
	}
	if ($width ne '') {
		$new_console_width = $width;
	}
	if ($height ne '') {
		$new_console_height = $height;
	}
	if ($group ne '') {
		$new_console_id_group = $group;
	}
	if ($background_color ne '') {
		$new_background_color = $background_color;
	}

	print_log "[INFO] The visual console with id $id is updated \n\n";

	db_update ($dbh, "UPDATE tlayout SET name = '" . $new_name . "', background = '" . $new_background . "', width = " . $new_console_width . ", height = " . $new_console_height . ", id_group = " . $new_console_id_group . ", background_color = '" . $new_background_color . "' WHERE id = " . $id);

	if ($elements ne '') {
		my $elements_in_array = decode_json($elements);

		if ($mode eq 'static_objects') {
			foreach my $elem (@$elements_in_array) {
				if (defined($elem->{'id'})) {

					print_log "[INFO] Edit element with id " . $elem->{'id'} . " \n\n";

					my $element_in_db = get_db_single_row ($dbh, "SELECT * 
						FROM tlayout_data 
						WHERE id = " . $elem->{'id'});

					my $new_pos_x = $element_in_db->{'pos_x'};
					my $new_pos_y = $element_in_db->{'pos_y'};
					my $new_width = $element_in_db->{'width'};
					my $new_height = $element_in_db->{'height'};
					my $new_label = $element_in_db->{'label'};
					my $new_image = $element_in_db->{'image'};
					my $new_type = $element_in_db->{'type'};
					my $new_period = $element_in_db->{'period'};
					my $new_id_agente_modulo = $element_in_db->{'id_agente_modulo'};
					my $new_id_agent = $element_in_db->{'id_agent'};
					my $new_id_layout_linked = $element_in_db->{'id_layout_linked'};
					my $new_parent_item = $element_in_db->{'parent_item'};
					my $new_enable_link = $element_in_db->{'enable_link'};
					my $new_id_metaconsole = $element_in_db->{'id_metaconsole'};
					my $new_id_group = $element_in_db->{'id_group'};
					my $new_id_custom_graph = $element_in_db->{'id_custom_graph'};
					my $new_border_width = $element_in_db->{'border_width'};
					my $new_type_graph = $element_in_db->{'type_graph'};
					my $new_label_position = $element_in_db->{'label_position'};
					my $new_border_color = $element_in_db->{'border_color'};
					my $new_fill_color = $element_in_db->{'fill_color'};
					my $new_id_layout_linked_weight = $elem->{'id_layout_linked_weight'};
					my $new_element_group = $elem->{'element_group'};
					my $new_show_on_top = $elem->{'show_on_top'};

					if(defined($elem->{'pos_x'})) {
						$new_pos_x = $elem->{'pos_x'};
					}
					if(defined($elem->{'pos_y'})) {
						$new_pos_y = $elem->{'pos_y'};
					}
					if(defined($elem->{'width'})) {
						$new_width = $elem->{'width'};
					}
					if(defined($elem->{'height'})) {
						$new_height = $elem->{'height'};
					}
					if(defined($elem->{'label'})) {
						$new_label = $elem->{'label'};
					}
					if(defined($elem->{'image'})) {
						$new_image = $elem->{'image'};
					}
					if(defined($elem->{'type'})) {
						$new_type = $elem->{'type'};
					}
					if(defined($elem->{'period'})) {
						$new_period = $elem->{'period'};
					}
					if(defined($elem->{'id_agente_modulo'})) {
						$new_id_agente_modulo = $elem->{'id_agente_modulo'};
					}
					if(defined($elem->{'id_agent'})) {
						$new_id_agent = $elem->{'id_agent'};
					}
					if(defined($elem->{'id_layout_linked'})) {
						$new_id_layout_linked = $elem->{'id_layout_linked'};
					}
					if(defined($elem->{'parent_item'})) {
						$new_parent_item = $elem->{'parent_item'};
					}
					if(defined($elem->{'enable_link'})) {
						$new_enable_link = $elem->{'enable_link'};
					}
					if(defined($elem->{'id_metaconsole'})) {
						$new_id_metaconsole = $elem->{'id_metaconsole'};
					}
					if(defined($elem->{'id_group'})) {
						$new_id_group = $elem->{'id_group'};
					}
					if(defined($elem->{'id_custom_graph'})) {
						$new_id_custom_graph = $elem->{'id_custom_graph'};
					}
					if(defined($elem->{'border_width'})) {
						$new_border_width = $elem->{'border_width'};
					}
					if(defined($elem->{'type_graph'})) {
						$new_type_graph = $elem->{'type_graph'};
					}
					if(defined($elem->{'label_position'})) {
						$new_label_position = $elem->{'label_position'};
					}
					if(defined($elem->{'border_color'})) {
						$new_border_color = $elem->{'border_color'};
					}
					if(defined($elem->{'fill_color'})) {
						$new_fill_color = $elem->{'fill_color'};
					}
					if(defined($elem->{'id_layout_linked_weight'})) {
						$new_id_layout_linked_weight = $elem->{'id_layout_linked_weight'};
					}
					if(defined($elem->{'element_group'})) {
						$new_element_group = $elem->{'element_group'};
					}
					if(defined($elem->{'show_on_top'})) {
						$new_show_on_top = $elem->{'show_on_top'};
					}

					db_update ($dbh, "UPDATE tlayout_data SET pos_x = " . $new_pos_x . ", pos_y = " . $new_pos_y . ", width = " . $new_width . 
						", height = " . $new_height . ", label = '" . $new_label . "', image = '" . $new_image . 
						"', type = " . $new_type . ", period = " . $new_period . ", id_agente_modulo = " . $new_id_agente_modulo . 
						", id_agent = " . $new_id_agent . ", id_layout_linked = " . $new_id_layout_linked . ", parent_item = " . $new_parent_item . 
						", enable_link = " . $new_enable_link . ", id_metaconsole = " . $new_id_metaconsole . ", id_group = " . $new_id_group . 
						", id_custom_graph = " . $new_id_custom_graph . ", border_width = " . $new_border_width . ", type_graph = '" . $new_type_graph . 
						"', label_position = '" . $new_label_position . "', border_color = '" . $new_border_color . "', fill_color = '" . $new_fill_color . 
						"', id_layout_linked_weight = '" . $new_id_layout_linked_weight . "', element_group = '" . $new_element_group . "', show_on_top = '" . $new_show_on_top . 
						"' WHERE id = " . $elem->{'id'});
					
					print_log "[INFO] Element with id " . $elem->{'id'} . " has been updated \n\n";
				}
				else {
					my $pos_x = $elem->{'pos_x'};
					my $pos_y = $elem->{'pos_y'};
					my $width = $elem->{'width'};
					my $height = $elem->{'height'};
					my $label = $elem->{'label'};
					my $image = $elem->{'image'};
					my $type = $elem->{'type'};
					my $period = $elem->{'period'};
					my $id_agente_modulo = $elem->{'id_agente_modulo'};
					my $id_agent = $elem->{'id_agent'};
					my $id_layout_linked = $elem->{'id_layout_linked'};
					my $parent_item = $elem->{'parent_item'};
					my $enable_link = $elem->{'enable_link'};
					my $id_metaconsole = $elem->{'id_metaconsole'};
					my $id_group = $elem->{'id_group'};
					my $id_custom_graph = $elem->{'id_custom_graph'};
					my $border_width = $elem->{'border_width'};
					my $type_graph = $elem->{'type_graph'};
					my $label_position = $elem->{'label_position'};
					my $border_color = $elem->{'border_color'};
					my $fill_color = $elem->{'fill_color'};
					my $id_layout_linked_weight = $elem->{'id_layout_linked_weight'};
					my $element_group = $elem->{'element_group'};
					my $show_on_top = $elem->{'show_on_top'};

					my $new_elem_id = db_insert ($dbh, 'id', 'INSERT INTO tlayout_data (id_layout, pos_x, pos_y, height, width, label, image, type, period, id_agente_modulo, id_agent, id_layout_linked, parent_item, enable_link, id_metaconsole, id_group, id_custom_graph, border_width, type_graph, label_position, border_color, fill_color, show_statistics, id_layout_linked_weight, element_group, show_on_top)
						VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', $id, $pos_x, $pos_y, $height, $width, $label, $image, $type, $period, $id_agente_modulo, $id_agent, $id_layout_linked, $parent_item, $enable_link, $id_metaconsole, $id_group, $id_custom_graph, $border_width, $type_graph, $label_position, $border_color, $fill_color, 0, $id_layout_linked_weight, $element_group, $show_on_top);
				
					print_log "[INFO] New element with id $new_elem_id has been created \n\n";
				}
			}
		}
		elsif ($mode eq 'auto_creation') {
			if ($element_square_positions eq '') {
				print_log "[ERROR] With this mode, square positions is obligatory'.\n\n";
				exit 1;
			}
			else {
				foreach my $elem (@$elements_in_array) {
					if (defined($elem->{'id'})) {
						print_log "[INFO] Edit element with id " . $elem->{'id'} . " \n\n";

						my $element_in_db = get_db_single_row ($dbh, "SELECT * 
							FROM tlayout_data 
							WHERE id = " . $elem->{'id'});

						my $new_pos_x = $element_in_db->{'pos_x'};
						my $new_pos_y = $element_in_db->{'pos_y'};
						my $new_width = $element_in_db->{'width'};
						my $new_height = $element_in_db->{'height'};
						my $new_label = $element_in_db->{'label'};
						my $new_image = $element_in_db->{'image'};
						my $new_type = $element_in_db->{'type'};
						my $new_period = $element_in_db->{'period'};
						my $new_id_agente_modulo = $element_in_db->{'id_agente_modulo'};
						my $new_id_agent = $element_in_db->{'id_agent'};
						my $new_id_layout_linked = $element_in_db->{'id_layout_linked'};
						my $new_parent_item = $element_in_db->{'parent_item'};
						my $new_enable_link = $element_in_db->{'enable_link'};
						my $new_id_metaconsole = $element_in_db->{'id_metaconsole'};
						my $new_id_group = $element_in_db->{'id_group'};
						my $new_id_custom_graph = $element_in_db->{'id_custom_graph'};
						my $new_border_width = $element_in_db->{'border_width'};
						my $new_type_graph = $element_in_db->{'type_graph'};
						my $new_label_position = $element_in_db->{'label_position'};
						my $new_border_color = $element_in_db->{'border_color'};
						my $new_fill_color = $element_in_db->{'fill_color'};
						my $new_id_layout_linked_weight = $elem->{'id_layout_linked_weight'};
						my $new_element_group = $elem->{'element_group'};
						my $new_show_on_top = $elem->{'show_on_top'};

						if(defined($elem->{'width'})) {
							$new_width = $elem->{'width'};
						}
						if(defined($elem->{'height'})) {
							$new_height = $elem->{'height'};
						}
						if(defined($elem->{'label'})) {
							$new_label = $elem->{'label'};
						}
						if(defined($elem->{'image'})) {
							$new_image = $elem->{'image'};
						}
						if(defined($elem->{'type'})) {
							$new_type = $elem->{'type'};
						}
						if(defined($elem->{'period'})) {
							$new_period = $elem->{'period'};
						}
						if(defined($elem->{'id_agente_modulo'})) {
							$new_id_agente_modulo = $elem->{'id_agente_modulo'};
						}
						if(defined($elem->{'id_agent'})) {
							$new_id_agent = $elem->{'id_agent'};
						}
						if(defined($elem->{'id_layout_linked'})) {
							$new_id_layout_linked = $elem->{'id_layout_linked'};
						}
						if(defined($elem->{'parent_item'})) {
							$new_parent_item = $elem->{'parent_item'};
						}
						if(defined($elem->{'enable_link'})) {
							$new_enable_link = $elem->{'enable_link'};
						}
						if(defined($elem->{'id_metaconsole'})) {
							$new_id_metaconsole = $elem->{'id_metaconsole'};
						}
						if(defined($elem->{'id_group'})) {
							$new_id_group = $elem->{'id_group'};
						}
						if(defined($elem->{'id_custom_graph'})) {
							$new_id_custom_graph = $elem->{'id_custom_graph'};
						}
						if(defined($elem->{'border_width'})) {
							$new_border_width = $elem->{'border_width'};
						}
						if(defined($elem->{'type_graph'})) {
							$new_type_graph = $elem->{'type_graph'};
						}
						if(defined($elem->{'label_position'})) {
							$new_label_position = $elem->{'label_position'};
						}
						if(defined($elem->{'border_color'})) {
							$new_border_color = $elem->{'border_color'};
						}
						if(defined($elem->{'fill_color'})) {
							$new_fill_color = $elem->{'fill_color'};
						}
						if(defined($elem->{'id_layout_linked_weight'})) {
							$new_id_layout_linked_weight = $elem->{'id_layout_linked_weight'};
						}
						if(defined($elem->{'element_group'})) {
							$new_element_group = $elem->{'element_group'};
						}
						if(defined($elem->{'show_on_top'})) {
							$new_show_on_top = $elem->{'show_on_top'};
						}

						db_update ($dbh, "UPDATE tlayout_data SET pos_x = " . $new_pos_x . ", pos_y = " . $new_pos_y . ", width = " . $new_width . 
							", height = " . $new_height . ", label = '" . $new_label . "', image = '" . $new_image . 
							"', type = " . $new_type . ", period = " . $new_period . ", id_agente_modulo = " . $new_id_agente_modulo . 
							", id_agent = " . $new_id_agent . ", id_layout_linked = " . $new_id_layout_linked . ", parent_item = " . $new_parent_item . 
							", enable_link = " . $new_enable_link . ", id_metaconsole = " . $new_id_metaconsole . ", id_group = " . $new_id_group . 
							", id_custom_graph = " . $new_id_custom_graph . ", border_width = " . $new_border_width . ", type_graph = '" . $new_type_graph . 
							"', label_position = '" . $new_label_position . "', border_color = '" . $new_border_color . "', fill_color = '" . $new_fill_color . 
							"', id_layout_linked_weight = '" . $new_id_layout_linked_weight . "', element_group = '" . $new_element_group . "', show_on_top = '" . $new_show_on_top . 
							"' WHERE id = " . $elem->{'id'});
						
						print_log "[INFO] Element with id " . $elem->{'id'} . " has been updated \n\n";
					}
					else {
						my $pos_x = 0;
						my $pos_y = 0;
						my $width = $elem->{'width'};
						my $height = $elem->{'height'};
						my $label = $elem->{'label'};
						my $image = $elem->{'image'};
						my $type = $elem->{'type'};
						my $period = $elem->{'period'};
						my $id_agente_modulo = $elem->{'id_agente_modulo'};
						my $id_agent = $elem->{'id_agent'};
						my $id_layout_linked = $elem->{'id_layout_linked'};
						my $parent_item = $elem->{'parent_item'};
						my $enable_link = $elem->{'enable_link'};
						my $id_metaconsole = $elem->{'id_metaconsole'};
						my $id_group = $elem->{'id_group'};
						my $id_custom_graph = $elem->{'id_custom_graph'};
						my $border_width = $elem->{'border_width'};
						my $type_graph = $elem->{'type_graph'};
						my $label_position = $elem->{'label_position'};
						my $border_color = $elem->{'border_color'};
						my $fill_color = $elem->{'fill_color'};
						my $id_layout_linked_weight = $elem->{'id_layout_linked_weight'};
						my $element_group = $elem->{'element_group'};
						my $show_on_top = $elem->{'show_on_top'};

						my $new_elem_id = db_insert ($dbh, 'id', 'INSERT INTO tlayout_data (id_layout, pos_x, pos_y, height, width, label, image, type, period, id_agente_modulo, id_agent, id_layout_linked, parent_item, enable_link, id_metaconsole, id_group, id_custom_graph, border_width, type_graph, label_position, border_color, fill_color, show_statistics, id_layout_linked_weight, element_group, show_on_top)
							VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', $id, $pos_x, $pos_y, $height, $width, $label, $image, $type, $period, $id_agente_modulo, $id_agent, $id_layout_linked, $parent_item, $enable_link, $id_metaconsole, $id_group, $id_custom_graph, $border_width, $type_graph, $label_position, $border_color, $fill_color, 0, $id_layout_linked_weight, $element_group, $show_on_top);
					
						print_log "[INFO] New element with id $new_elem_id has been created \n\n";
					}
				}

				my $positions = decode_json($element_square_positions);

				my $pos1X = $positions->{'pos1x'};
				my $pos1Y = $positions->{'pos1y'};
				my $pos2X = $positions->{'pos2x'};
				my $pos2Y = $positions->{'pos2y'};

				my @console_elements = get_db_rows ($dbh, "SELECT * 
						FROM tlayout_data 
						WHERE id_layout = $id");

				my $number_of_elements = scalar(@console_elements);

				my $x_divider = 4;
				my $y_divider = 1;

				for (my $i = 1; $i <= 1000; $i++) {
					if (($i * 4) < $number_of_elements) {
						$y_divider++;
					}
					else {
						last;
					}
				}

				my $elem_width = ($pos2X - $pos1X) / $x_divider;
				my $elem_height = ($pos2Y - $pos1Y) / $y_divider;

				if ($number_of_elements < 4) {
					$elem_height = ($pos2Y - $pos1Y) / 3;
				}

				my $elem_count = 1;
				my $pos_helper_x = 0;
				my $pos_helper_y = 0;
				foreach my $elem (@console_elements) {
					my $new_pos_x = $pos_helper_x * $elem_width;
					my $new_pos_y = $pos_helper_y * $elem_height;
					my $new_elem_width = $elem_width;
					my $new_elem_height = $elem_height;

					db_update ($dbh, "UPDATE tlayout_data SET pos_x = " . $new_pos_x . ", pos_y = " . $new_pos_y . 
							", width = " . $new_elem_width . ", height = " . $new_elem_height . 
							" WHERE id = " . $elem->{'id'});

					print_log "[INFO] Recolocate element with id " . $elem->{'id'} . " \n\n";

					$elem_count++;

					if ($pos_helper_x == 3) {
						$pos_helper_x = 0;
						$pos_helper_y++;
					}
					else {
						$pos_helper_x++;
					}
				}
			}
		}
		else {
			print_log "[ERROR] Mode parameter must be 'static_objects' or 'auto_creation'.\n\n";
			exit 1;
		}
	}
}

##############################################################################
# Delete a visual console.
# Related option: --delete_visual_console
##############################################################################

sub cli_delete_visual_console() {
	my ($id) = @ARGV[2];

	if($id eq '') {
		print_log "[ERROR] ID field cannot be empty.\n\n";
		exit 1;
	}

	print_log "[INFO] Delete visual console with ID '$id' \n\n";

	my $delete_layout = db_do($dbh, 'DELETE FROM tlayout WHERE id = ?', $id);

	if ($delete_layout eq 1) {
		db_do($dbh, 'DELETE FROM tlayout_data WHERE id_layout = ?', $id);

		print_log "[INFO] Delete visual console elements with console ID '$id' \n\n";
	}
	else {
		print_log "[ERROR] Error at remove the visual console.\n\n";
		exit 1;
	}
}

##############################################################################
# Delete a visual console objects.
# Related option: --delete_visual_console_objects
##############################################################################

sub cli_delete_visual_console_objects() {
	my ($id_console,$mode,$id_mode) = @ARGV[2..4];

	if($id_console eq '') {
		print_log "[ERROR] Console ID field cannot be empty.\n\n";
		exit 1;
	}
	elsif ($mode eq '') {
		print_log "[ERROR] Mode field cannot be empty.\n\n";
		exit 1;
	}
	elsif ($id_mode eq '') {
		print_log "[ERROR] Mode index field cannot be empty.\n\n";
		exit 1;
	}

	if (($mode eq 'type') || ($mode eq 'image') || ($mode eq 'id_agent') || 
		($mode eq 'id_agente_modulo') || ($mode eq 'id_group') || ($mode eq 'type_graph')) {
		print_log "[INFO] Removind objects with mode '$mode' and id '$id_mode' \n\n";
		
		db_do($dbh, 'DELETE FROM tlayout_data WHERE id_layout = ' . $id_console . ' AND ' . $mode . ' = "' . $id_mode . '"');
	}
	else {
		print_log "[ERROR] Mode is not correct.\n\n";
		exit 1;
	}
}

##############################################################################
# Duplicate a visual console.
# Related option: --duplicate_visual_console
##############################################################################

sub cli_duplicate_visual_console () {
	my ($id_console,$times,$prefix) = @ARGV[2..4];

	if($id_console eq '') {
		print_log "[ERROR] Console ID field cannot be empty.\n\n";
		exit 1;
	}

	my $console = get_db_single_row ($dbh, "SELECT * 
			FROM tlayout 
			WHERE id = $id_console");

	my $name_to_compare = $console->{'name'};
	my $new_name = $console->{'name'} . "_1";
	my $name_count = 2;

	if ($prefix ne '') {
		$new_name = $prefix;
		$name_to_compare = $prefix;
		$name_count = 1;
	}

	for (my $iteration = 0; $iteration < $times; $iteration++) {
		my $exist = 1;
		while ($exist == 1) {
			my $name_in_db = get_db_single_row ($dbh, "SELECT name FROM tlayout WHERE name = '$new_name'");
			
			if (defined($name_in_db->{'name'}) && ($name_in_db->{'name'} eq $new_name)) {
				$new_name = $name_to_compare . "_" . $name_count;
				$name_count++;
			}
			else {
				$exist = 0;
			}
		}

		my $new_console_id = db_insert ($dbh, 'id', 'INSERT INTO tlayout (name, id_group, background, width, height, background_color)
							VALUES (?, ?, ?, ?, ?, ?)', $new_name, $console->{'id_group'}, $console->{'background'}, $console->{'width'}, $console->{'height'}, $console->{'background_color'});
		
		print_log "[INFO] The new visual console '$new_name' has been created. The new ID is '$new_console_id' \n\n";

		my @console_elements = get_db_rows ($dbh, "SELECT * 
				FROM tlayout_data 
				WHERE id_layout = $id_console");

		foreach my $element (@console_elements) {
			my $pos_x = $element->{'pos_x'};
			my $pos_y = $element->{'pos_y'};
			my $width = $element->{'width'};
			my $height = $element->{'height'};
			my $label = $element->{'label'};
			my $image = $element->{'image'};
			my $type = $element->{'type'};
			my $period = $element->{'period'};
			my $id_agente_modulo = $element->{'id_agente_modulo'};
			my $id_agent = $element->{'id_agent'};
			my $id_layout_linked = $element->{'id_layout_linked'};
			my $parent_item = $element->{'parent_item'};
			my $enable_link = $element->{'enable_link'};
			my $id_metaconsole = $element->{'id_metaconsole'};
			my $id_group = $element->{'id_group'};
			my $id_custom_graph = $element->{'id_custom_graph'};
			my $border_width = $element->{'border_width'};
			my $type_graph = $element->{'type_graph'};
			my $label_position = $element->{'label_position'};
			my $border_color = $element->{'border_color'};
			my $fill_color = $element->{'fill_color'};
			my $id_layout_linked_weight = $element->{'id_layout_linked_weight'};
			my $element_group = $element->{'element_group'};
			my $show_on_top = $element->{'show_on_top'};

			my $element_id = db_insert ($dbh, 'id', 'INSERT INTO tlayout_data (id_layout, pos_x, pos_y, height, width, label, image, type, period, id_agente_modulo, id_agent, id_layout_linked, parent_item, enable_link, id_metaconsole, id_group, id_custom_graph, border_width, type_graph, label_position, border_color, fill_color, show_statistics, id_layout_linked_weight, element_group, show_on_top)
						VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', $new_console_id, $pos_x, $pos_y, $height, $width, $label, $image, $type, $period, $id_agente_modulo, $id_agent, $id_layout_linked, $parent_item, $enable_link, $id_metaconsole, $id_group, $id_custom_graph, $border_width, $type_graph, $label_position, $border_color, $fill_color, 0, $id_layout_linked_weight, $element_group, $show_on_top);
		
			print_log "[INFO] Element with ID " . $element->{"id"} . " has been duplicated to the new console \n\n";
		}
	}
}

##############################################################################
# Export a visual console elements to json.
# Related option: --export_json_visual_console
##############################################################################

sub cli_export_visual_console() {
	my ($id,$path,$with_id) = @ARGV[2..4];

	if($id eq '') {
		print_log "[ERROR] ID field cannot be empty.\n\n";
		exit 1;
	}

	my $data_to_json = '';
	my $first = 1;

	print_log "[INFO] Exporting visual console elements with ID '$id' \n\n";

	my $console = get_db_single_row ($dbh, "SELECT * 
			FROM tlayout 
			WHERE id = $id");

	$data_to_json .= '"' . safe_output($console->{'name'}) . '"';
	$data_to_json .= ' "' . $console->{'background'} . '"';
	$data_to_json .= ' ' . $console->{'width'};
	$data_to_json .= ' ' . $console->{'height'};
	$data_to_json .= ' ' . $console->{'id_group'};
	$data_to_json .= ' "static_objects"';
	$data_to_json .= ' ""';
	$data_to_json .= ' "' . $console->{'background_color'} . '" ';

	my @console_elements = get_db_rows ($dbh, "SELECT * 
			FROM tlayout_data 
			WHERE id_layout = $id");

	$data_to_json .= "'[";
	foreach my $element (@console_elements) {
		my $id_layout_data = $element->{'id'};
		my $pos_x = $element->{'pos_x'};
		my $pos_y = $element->{'pos_y'};
		my $width = $element->{'width'};
		my $height = $element->{'height'};
		my $label = $element->{'label'};
		my $image = $element->{'image'};
		my $type = $element->{'type'};
		my $period = $element->{'period'};
		my $id_agente_modulo = $element->{'id_agente_modulo'};
		my $id_agent = $element->{'id_agent'};
		my $id_layout_linked = $element->{'id_layout_linked'};
		my $parent_item = $element->{'parent_item'};
		my $enable_link = $element->{'enable_link'};
		my $id_metaconsole = $element->{'id_metaconsole'};
		my $id_group = $element->{'id_group'};
		my $id_custom_graph = $element->{'id_custom_graph'};
		my $border_width = $element->{'border_width'};
		my $type_graph = $element->{'type_graph'};
		my $label_position = $element->{'label_position'};
		my $border_color = $element->{'border_color'};
		my $fill_color = $element->{'fill_color'};
		my $id_layout_linked_weight = $element->{'id_layout_linked_weight'};
		my $element_group = $element->{'element_group'};
		my $show_on_top = $element->{'show_on_top'};

		if ($first == 0) {
			$data_to_json .= ','
		}
		else {
			$first = 0;
		}

		$label =~ s/"/\\"/g;

		if ($with_id == 1) {
			$data_to_json .= '{"id":' . $id_layout_data;
			$data_to_json .= ',"image":"' . $image . '"';
		}
		else {
			$data_to_json .= '{"image":"' . $image . '"';
		}
		$data_to_json .= ',"pos_y":' . $pos_y;
		$data_to_json .= ',"pos_x":' . $pos_x;
		$data_to_json .= ',"width":' . $width;
		$data_to_json .= ',"height":' . $height;
		$data_to_json .= ',"label":"' . $label . '"';
		$data_to_json .= ',"type":' . $type;
		$data_to_json .= ',"period":' . $period;
		$data_to_json .= ',"id_agente_modulo":' . $id_agente_modulo;
		$data_to_json .= ',"id_agent":' . $id_agent;
		$data_to_json .= ',"id_layout_linked":' . $id_layout_linked;
		$data_to_json .= ',"parent_item":' . $parent_item;
		$data_to_json .= ',"enable_link":' . $enable_link;
		$data_to_json .= ',"id_metaconsole":' . $id_metaconsole;
		$data_to_json .= ',"id_group":' . $id_group;
		$data_to_json .= ',"id_custom_graph":' . $id_custom_graph;
		$data_to_json .= ',"border_width":' . $border_width;
		$data_to_json .= ',"type_graph":"' . $type_graph . '"';
		$data_to_json .= ',"label_position":"' . $label_position . '"';
		$data_to_json .= ',"border_color":"' . $border_color . '"';
		$data_to_json .= ',"fill_color":"' . $fill_color . '"';
		$data_to_json .= ',"id_layout_linked_weight":' . $id_layout_linked_weight;
		$data_to_json .= ',"element_group":' . $element_group;
		$data_to_json .= ',"show_on_top":' . $show_on_top;
		$data_to_json .= '}';
	}

	$data_to_json .= "]'";

	if ($path eq '') {
		open(FicheroJSON, ">console_" . $id . "_elements");
	}
	else {
		open(FicheroJSON, ">" . $path . "/console_" . $id . "_elements");
	}

	print FicheroJSON $data_to_json;

	print_log "[INFO] JSON file now contents: \n" . $data_to_json . "\n\n";
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
		print_log "[ERROR] No valid arguments\n\n";
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
			cli_enable_eacl ($conf, $dbh);
		}
		elsif ($param eq '--disable_double_auth') {
			param_check($ltotal, 1);
			cli_disable_double_auth();
		}
		elsif ($param eq '--disable_group') {
			param_check($ltotal, 1);
			cli_disable_group();
		}
		elsif ($param eq '--enable_group') {
			param_check($ltotal, 1);
			cli_enable_group();
		}
		elsif ($param eq '--start_snmptrapd') {
			#param_check($ltotal, 0);
			cli_start_snmptrapd();
		}
		elsif ($param eq '--create_agent') {
			param_check($ltotal, 8, 4);
			cli_create_agent();
		}
		elsif ($param eq '--delete_agent') {
			param_check($ltotal, 2, 1);
			cli_delete_agent();
		}
		elsif ($param eq '--create_data_module') {
			param_check($ltotal, 32, 25);
			cli_create_data_module(0);
		}
		elsif ($param eq '--create_web_module') {
			param_check($ltotal, 40, 36);
			cli_create_web_module(0);
		}

		elsif ($param eq '--get_module_group') {
			param_check($ltotal, 1, 1);
			cli_get_module_group();
		}
		elsif ($param eq '--create_module_group') {
			param_check($ltotal, 1, 1);
			cli_create_module_group();
		}
		elsif ($param eq '--module_group_synch') {
			param_check($ltotal, 2, 1);
			cli_module_group_synch();
		}
		elsif ($param eq '--create_network_module') {
			param_check($ltotal, 34, 21);
			cli_create_network_module(0);
		}
		elsif ($param eq '--create_snmp_module') {
			param_check($ltotal, 43, 29);
			cli_create_snmp_module(0);
		}
		elsif ($param eq '--create_plugin_module') {
			param_check($ltotal, 38, 21);
			cli_create_plugin_module(0);
		}
		elsif ($param eq '--delete_module') {
			param_check($ltotal, 3, 1);
			cli_delete_module();
		}
		elsif ($param eq '--delete_not_policy_modules') {
			param_check($ltotal, 0);
			cli_delete_not_policy_modules();
		}
		elsif ($param eq '--create_template_module') {
			param_check($ltotal, 4, 1);
			cli_create_template_module();
		}
		elsif ($param eq '--delete_template_module') {
			param_check($ltotal, 4, 1);
			cli_delete_template_module();
		}
		elsif ($param eq '--create_template_action') {
			param_check($ltotal, 7, 3);
			cli_create_template_action();
		}
		elsif ($param eq '--delete_template_action') {
			param_check($ltotal,5, 1);
			cli_delete_template_action();
		}
		elsif ($param eq '--data_module') {
			param_check($ltotal, 7, 2);
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
		elsif ($param eq '--add_profile') {
			param_check($ltotal, 3);
			cli_add_profile();
		}
		elsif ($param eq '--create_profile') {
			param_check($ltotal, 24);
			cli_create_profile();
		}
		elsif ($param eq '--update_profile') {
			param_check($ltotal, 24);
			cli_update_profile();
		}
		elsif ($param eq '--delete_profile') {
			param_check($ltotal, 3);
			cli_delete_profile();
		}
		elsif ($param eq '--create_event') {
			my @fields = (
				{'name' => 'event'},
				{
					'name' => 'event_type',
					'values' => [
						'unknown','alert_fired','alert_recovered','alert_ceased',
						'alert_manual_validation','recon_host_detected','system',
						'error','new_agent','going_up_warning','going_up_critical','going_down_warning',
						'going_down_normal','going_down_critical','going_up_normal','configuration_change'
					]
				},
				{'name' => 'group_name'},
				{'name' => 'agent_name'},
				{'name' => 'module_name'},
				{
					'name' => 'event_status',
					'values' => ['0', '1', '2'],
					'text_extra' => ['(New)', '(Validated)', '(In process)']
				},
				{
					'name' => 'severity',
					'values' => ['0', '1', '2', '3', '4', '5', '6'],
					'text_extra' => [
						'(Maintenance)', '(Informational)', '(Normal)',
						'(Warning)', '(Critical)', '(Minor)', '(Major)'
					]
				},
				{'name' => 'template_name'},
				{'name' => 'user_name'},
				{'name' => 'comment'},
				{'name' => 'source'},
				{'name' => 'id_extra'},
				{'name' => 'tags'},
				{'type' => 'json', 'name' => 'custom_data_json'},
				{
					'name' => 'force_create_agent',
					'values' => ['0', '1']
				},
				{'name' => 'critical_instructions'},
				{'name' => 'warning_instructions'},
				{'name' => 'unknown_instructions'},
				{'name' => 'use_alias'},
				{'name' => 'metaconsole'},
				{'name' => 'event_custom_id'}
			);

			param_check($ltotal, 21, 18);

			check_values(\@fields);

			cli_create_event();
		}
		elsif ($param eq '--validate_event') {
			param_check($ltotal, 8, 7);
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
		elsif ($param eq '--add_event_comment') {
			param_check($ltotal, 3);
			cli_add_event_comment();
		}
		elsif ($param eq '--delete_data') {
			param_check($ltotal, 4, 2);
			cli_delete_data($ltotal);
		}
		elsif ($param eq '--apply_policy') {
			param_check($ltotal, 4, 3);
			cli_apply_policy();
		}
		elsif ($param eq '--disable_policy_alerts') {
			param_check($ltotal, 1);
			cli_disable_policy_alerts();
		}
		elsif ($param eq '--create_group') {
			param_check($ltotal, 4, 3);
			cli_create_group();
		}
		elsif ($param eq '--delete_group') {
			param_check($ltotal, 1);
			cli_delete_group();
		}
		elsif ($param eq '--update_group') {
			param_check($ltotal, 5,4);
			cli_update_group();
		}
		elsif ($param eq '--add_agent_to_policy') {
			param_check($ltotal, 3, 1);
			cli_policy_add_agent();
		}
		elsif ($param eq '--remove_agent_from_policy') {
			param_check($ltotal, 2);
			cli_policy_delete_agent();
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
			param_check($ltotal, 5, 2);
			cli_module_get_data();
		}
		elsif ($param eq '--add_collection_to_policy') {
			param_check($ltotal, 2, 2);
			cli_add_collection_to_policy();
		}
		elsif ($param eq '--create_policy_data_module_from_local_component') {
			param_check($ltotal, 2, 2);
			cli_create_policy_data_module_from_local_component();
		}
		elsif ($param eq '--create_policy') {
			param_check($ltotal, 3, 2);
			cli_create_policy();
		}
		elsif ($param eq '--create_policy_data_module') {
			param_check($ltotal, 31, 22);
			cli_create_data_module(1);
		}
		elsif ($param eq '--create_policy_web_module') {
			param_check($ltotal, 38, 33);
			cli_create_web_module(1);
		}
		elsif ($param eq '--create_policy_network_module') {
			param_check($ltotal, 34, 20);
			cli_create_network_module(1);
		}
		elsif ($param eq '--create_policy_snmp_module') {
			param_check($ltotal, 41, 27);
			cli_create_snmp_module(1);
		}
		elsif ($param eq '--create_policy_plugin_module') {
			param_check($ltotal, 36, 20);
			cli_create_plugin_module(1);
		}
		elsif ($param eq '--create_alert_template') {
			param_check($ltotal, 19, 15);
			cli_create_alert_template();
		}
		elsif ($param eq '--delete_alert_template') {
			param_check($ltotal, 7);
			cli_delete_alert_template();
		}
		elsif ($param eq '--create_alert_command') {
			param_check($ltotal, 7, 2);
			cli_create_alert_command();
		}
		elsif ($param eq '--get_alert_commands') {
			param_check($ltotal, 5, 5);
			cli_get_alert_commands();
		}
		elsif ($param eq '--get_alert_actions') {
			param_check($ltotal, 3, 3);
			cli_get_alert_actions();
		}
		elsif ($param eq '--get_alert_actions_meta') {
			param_check($ltotal, 4, 4);
			cli_get_alert_actions_meta();
		}
		elsif ($param eq '--update_alert_template') {
			param_check($ltotal, 3);
			cli_alert_template_update();
		}
		elsif ($param eq '--update_module') {
			param_check($ltotal, 6, 2);
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
		elsif ($param eq '--validate_alert') {
			param_check($ltotal, 5,4);
			cli_validate_alert();
		}
		elsif ($param eq '--validate_policy_alerts') {
			param_check($ltotal, 1);
			cli_validate_policy_alerts();
		}
		elsif ($param eq '--get_module_id') {
			param_check($ltotal, 2);
			cli_get_module_id();
		}
		elsif ($param eq '--get_module_custom_id') {
			param_check($ltotal, 1);
			cli_get_module_custom_id();
		}
		elsif ($param eq '--set_module_custom_id') {
			param_check($ltotal, 2);
			cli_set_module_custom_id();
		}
		elsif ($param eq '--get_agent_group') {
			param_check($ltotal, 2, 1);
			cli_get_agent_group();
		}
		elsif ($param eq '--get_agent_group_id') {
			param_check($ltotal, 2, 1);
			cli_get_agent_group_id();
		}
		elsif ($param eq '--get_agents_module_current_data') {
			param_check($ltotal, 1);
			cli_get_agents_module_current_data();
		}
		elsif ($param eq '--get_agent_modules') {
			param_check($ltotal, 2, 1);
			cli_get_agent_modules();
		}
		elsif ($param eq '--get_agent_status') {
			param_check($ltotal, 2, 1);
			cli_get_agent_status();
		}
		elsif ($param eq '--get_agents_id_name_by_alias') {
			param_check($ltotal, 2,1);
			cli_get_agents_id_name_by_alias();
		}
		elsif ($param eq '--get_policy_modules') {
			param_check($ltotal, 1);
			cli_get_policy_modules();
		}
		elsif ($param eq '--get_policies') {
			param_check($ltotal, 2, 2);
			cli_get_policies();
		}
		elsif ($param eq '--get_agents') {
			param_check($ltotal, 7, 7);
			cli_get_agents();
		}
		elsif ($param eq '--delete_conf_file') {
			param_check($ltotal, 2, 1);
			cli_delete_conf_file();
		}
		elsif ($param eq '--clean_conf_file') {
			param_check($ltotal, 2, 1);
			cli_clean_conf_file();
		}
		elsif ($param eq '--update_agent') {
			param_check($ltotal, 4, 1);
			cli_agent_update();
		}
		elsif ($param eq '--get_bad_conf_files') {
			param_check($ltotal, 0);
			cli_get_bad_conf_files();
		}
		elsif ($param eq '--create_network_module_from_component') {
			param_check($ltotal, 3, 1);
			cli_create_network_module_from_component();
		}
		elsif ($param eq '--create_network_component') {
			param_check($ltotal, 24, 21);
			cli_create_network_component();
		}
		elsif ($param eq '--create_netflow_filter') {
			param_check($ltotal, 5);
			cli_create_netflow_filter();
		}
		elsif ($param eq '--create_snmp_trap') {
			param_check($ltotal, 4);
			cli_create_snmp_trap ($conf, $dbh);
		}
		elsif ($param eq '--set_event_storm_protection') {
			param_check($ltotal, 1);
			cli_set_event_storm_protection();
		}
		elsif ($param eq '--agent_set_os') {
			param_check($ltotal, 3, 1);
			cli_agent_set_os();
		}
		elsif ($param eq '--create_custom_graph') {
			param_check($ltotal, 11);
			cli_create_custom_graph();
		}
		elsif ($param eq '--delete_custom_graph') {
			param_check($ltotal, 1);
			cli_delete_custom_graph();
		}
		elsif ($param eq '--edit_custom_graph') {
			param_check($ltotal, 10);
			cli_edit_custom_graph();
		}
		elsif ($param eq '--add_modules_to_graph') {
			param_check($ltotal, 3);
			cli_add_modules_to_graph();
		}
		elsif ($param eq '--delete_modules_to_graph') {
			param_check($ltotal, 3);
			cli_delete_modules_to_graph();
		}
		elsif ($param eq '--create_special_day') {
			param_check($ltotal, 4);
			cli_create_special_day();
		}
		elsif ($param eq '--update_special_day') {
			param_check($ltotal, 3);
			cli_update_special_day();
		}
		elsif ($param eq '--delete_special_day') {
			param_check($ltotal, 1);
			cli_delete_special_day();
		}
		elsif ($param eq '--create_data_module_from_local_component') {
			param_check($ltotal, 3, 1);
			cli_create_data_module_from_local_component();
		}
		elsif ($param eq '--create_local_component') {
			param_check($ltotal, 35, 33);
			cli_create_local_component();
		}
		elsif ($param eq '--recreate_collection') {
			param_check($ltotal, 1);
			cli_recreate_collection();
		}
		elsif ($param eq '--create_tag') {
			param_check($ltotal, 4, 2);
			cli_create_tag();
		} 
		elsif ($param eq '--add_tag_to_user_profile') {
			param_check($ltotal, 4);
			cli_add_tag_to_user_profile();
		} 
		elsif ($param eq '--add_tag_to_module') {
			param_check($ltotal, 3);
			cli_add_tag_to_module();
		} 
		elsif ($param eq '--create_downtime') {
			param_check($ltotal, 20);
			cli_create_planned_downtime();
		}
		elsif ($param eq '--add_item_downtime') {
			param_check($ltotal, 3);
			cli_add_item_planned_downtime();
		}
		elsif ($param eq '--get_all_planned_downtimes') {
			param_check($ltotal, 5, 4);
			cli_get_all_planned_downtime();
		}
		elsif ($param eq '--get_planned_downtimes_items') {
			param_check($ltotal, 5, 4);
			cli_get_planned_downtimes_items();
		}
		elsif ($param eq '--create_synthetic') {
			#aram_check($ltotal, 1);
			cli_create_synthetic();
		}
		elsif ($param eq '--set_planned_downtimes_deleted') {
			param_check($ltotal, 1);
			cli_set_delete_planned_downtime();
		}
		elsif ($param eq '--locate_agent') {
			param_check($ltotal, 2, 1);
			cli_locate_agent();
		}
		elsif ($param eq '--create_visual_console') {
			param_check($ltotal, 9, 3);
			cli_create_visual_console();
		}
		elsif ($param eq '--edit_visual_console') {
			param_check($ltotal, 10, 9);
			cli_edit_visual_console();
		}
		elsif ($param eq '--delete_visual_console') {
			param_check($ltotal, 1);
			cli_delete_visual_console();
		}
		elsif ($param eq '--delete_visual_console_objects') {
			param_check($ltotal, 3);
			cli_delete_visual_console_objects();
		}
		elsif ($param eq '--duplicate_visual_console') {
			param_check($ltotal, 3, 2);
			cli_duplicate_visual_console();
		}
		elsif ($param eq '--export_json_visual_console') {
			param_check($ltotal, 3, 2);
			cli_export_visual_console();
		}
		elsif ($param eq '--apply_module_template') {
			param_check($ltotal, 2, 2);
			cli_apply_module_template();
		}
		elsif ($param eq '--new_cluster') {
			param_check($ltotal, 4, 0);
			cli_new_cluster();
		}
		elsif ($param eq '--add_cluster_agent') {
			param_check($ltotal, 1, 0);
			cli_add_cluster_agent();
		}
		elsif ($param eq '--add_cluster_item') {
			param_check($ltotal, 1, 0);
			cli_add_cluster_item();
		}
		elsif ($param eq '--delete_cluster') {
			param_check($ltotal, 1, 0);
			cli_delete_cluster();
		}
		elsif ($param eq '--delete_cluster_agent') {
			param_check($ltotal, 2, 0);
			cli_delete_cluster_agent();
		}
		elsif ($param eq '--delete_cluster_item') {
			param_check($ltotal, 1, 0);
			cli_delete_cluster_item();
		}
		elsif ($param eq '--get_cluster_status') {
			param_check($ltotal, 1, 0);
			cli_cluster_status();
		}
		elsif ($param eq '--migration_agent_queue') {
			param_check($ltotal, 4, 1);
			cli_migration_agent_queue();
		}
		elsif ($param eq '--migration_agent') {
			param_check($ltotal, 1, 0);
			cli_migration_agent();
		}
		elsif ($param eq '--set_disabled_and_standby') {
			param_check($ltotal, 3, 1);
			cli_set_disabled_and_standby();
		}
		elsif ($param eq '--reset_agent_counts') {
			param_check($ltotal, 1, 0);
			cli_reset_agent_counts();
		}elsif ($param eq '--event_in_progress') {
			param_check($ltotal, 1, 0);
			cli_event_in_progress();
		}		elsif ($param eq '--agent_update_custom_fields') {
			param_check($ltotal, 4, 5);
			cli_agent_update_custom_fields();
		}
		elsif ($param eq '--get_gis_agent') {
			param_check($ltotal, 1, 0);
			cli_get_gis_agent();
		}
		elsif ($param eq '--insert_gis_data'){
			param_check($ltotal, 4, 0);
			cli_insert_gis_data();
		}
		elsif ($param eq '--update_event_custom_id'){
			param_check($ltotal, 2);
			cli_update_event_custom_id();
		}
		else {
			print_log "[ERROR] Invalid option '$param'.\n\n";
			$param = '';
			help_screen ();
			exit;
		}
	}

    exit;
}

##############################################################################
# Create a custom graph.
# Related option: --create_custom_graph
##############################################################################

sub cli_create_custom_graph() {
	
	my ($name,$description,$user,$idGroup,$width,$height,$events,$stacked,$period,$modules,$separator) = @ARGV[2..12];
	
	$separator = ($separator ne '') ? $separator : ';';
	
	my @module_array = split($separator, $modules);
	
	$description = ($description ne '') ? safe_input($description) : '';
	$width = ($width ne '') ? $width : 550;
	$height = ($height ne '') ? $height : 210;
	$period = ($period ne '') ? $period : 86400;
	$events = ($events ne '') ? $events : 0;
	$stacked = ($stacked ne '') ? $stacked : 0;
	$idGroup = ($idGroup ne '') ? $idGroup : 0;
	
	my $id_graph = pandora_create_custom_graph($name,$description,$user,$idGroup,$width,$height,$events,$stacked,$period,$dbh);
	
	if ($id_graph != 0) { #~ insert source
		if ($modules ne '') {
			foreach my $module (@module_array) {
				pandora_insert_graph_source($id_graph,$module,1,$dbh);
			}
		}
	}
}

##############################################################################
# Delete a custom graph.
# Related option: --delete_custom_graph
##############################################################################
sub cli_delete_custom_graph () {
	
	my ($id_graph) = @ARGV[2];
	
	my $result = pandora_delete_graph_source($id_graph, $dbh);
	
	pandora_delete_custom_graph($id_graph, $dbh);
}

##############################################################################
# Edit a custom graph.
# Related option: --edit_custom_graph
##############################################################################
sub cli_edit_custom_graph() {
	
	my ($id_graph,$name,$description,$user,$idGroup,$width,$height,$events,$stacked,$period) = @ARGV[2..12];

	pandora_edit_custom_graph($id_graph,$name,$description,$user,$idGroup,$width,$height,$events,$stacked,$period,$dbh);
	
}

sub cli_add_modules_to_graph () {
	
	my ($id_graph,$modules,$separator) = @ARGV[2..4];
	
	$separator = ($separator ne '') ? $separator : ';';
	
	my @module_array = split($separator, $modules);
	
	foreach my $module (@module_array) {
		pandora_insert_graph_source($id_graph,$module,1,$dbh);
	}
}

sub cli_delete_modules_to_graph () {
	
	my ($id_graph,$modules,$separator) = @ARGV[2..4];
	
	$separator = ($separator ne '') ? $separator : ';';
	
	my @module_array = split($separator, $modules);
	
	foreach my $module (@module_array) {
		pandora_delete_graph_source($id_graph, $dbh, $module);
	}
}

##############################################################################
# Return local component id given the name
##############################################################################

sub pandora_get_local_component_id($$) {
	my ($dbh,$name) = @_;
	
	my $lc_id = get_db_value($dbh, 'SELECT id FROM tlocal_component WHERE name = ?',safe_input($name));
	
	return defined ($lc_id) ? $lc_id : -1;
}

##############################################################################
# Create policy
# Related option: --create_policy
##############################################################################
sub cli_create_policy () {
	my ($policy_name, $group_name, $description) = @ARGV[2..4];
	
	my $policy_id = enterprise_hook('get_policy_id',[$dbh, safe_input($policy_name)]);
	
	non_exist_check($policy_id,'policy',$policy_name);
	
	my $id_group = get_group_id($dbh,$group_name);
	exist_check($id_group,'group',$group_name);
	
	my $id = enterprise_hook('create_policy',[$dbh, $policy_name, $description, $id_group]);
	
	return $id;
}

##############################################################################
# Add collection to a policy
# Related option: --add_collection_to_policy
##############################################################################
sub cli_add_collection_to_policy () {
	my ($policy_name, $collection_name) = @ARGV[2..3];
	
	my $policy_id = enterprise_hook('get_policy_id',[$dbh, safe_input($policy_name)]);
	exist_check($policy_id,'policy',$policy_name);
	
	my $collection_id = enterprise_hook('get_collection_id',[$dbh, safe_input($collection_name)]);
	exist_check($collection_id,'group',$collection_name);
	
	my $id = enterprise_hook('add_collection_to_policy_db',[$dbh, $policy_id, $collection_id]);
	
	return $id;
}

##############################################################################
# Create data module from local component.
# Related option: --create_data_module_from_local_component
##############################################################################

sub cli_create_data_module_from_local_component() {
	my ($agent_name, $component_name, $use_alias) = @ARGV[2..4];

	my @id_agents;	

	if (defined $use_alias and $use_alias eq 'use_alias') {
		@id_agents = get_agent_ids_from_alias($dbh,$agent_name);

		my $agent_id;

		foreach my $id (@id_agents) {
			$agent_id = $id->{'id_agente'};
			exist_check($agent_id,'agent',$agent_name);
				
			my $lc_id = pandora_get_local_component_id($dbh, $component_name);
			exist_check($lc_id,'local component',$component_name);
			
			my $module_exists = get_agent_module_id($dbh, $component_name, $agent_id);
			if ($module_exists ne -1) {
				next;
			}
			
			# Get local component data
			my $component = get_db_single_row ($dbh, 'SELECT * FROM tlocal_component WHERE id = ?', $lc_id);
			
			print_log "[INFO] Creating module from local component '$component_name'\n\n";

			#~ pandora_create_module_from_local_component ($conf, $component, $agent_id, $dbh);
			enterprise_hook('pandora_create_module_from_local_component',[$conf, $component, $agent_id, $dbh]);
		}
	} else {
		my $agent_id = get_agent_id($dbh,$agent_name);
		exist_check($agent_id,'agent',$agent_name);
			
		my $lc_id = pandora_get_local_component_id($dbh, $component_name);
		exist_check($lc_id,'local component',$component_name);
		
		my $module_exists = get_agent_module_id($dbh, $component_name, $agent_id);
		non_exist_check($module_exists, 'module name', $component_name);
		
		# Get local component data
		my $component = get_db_single_row ($dbh, 'SELECT * FROM tlocal_component WHERE id = ?', $lc_id);
		
		print_log "[INFO] Creating module from local component '$component_name'\n\n";

		#~ pandora_create_module_from_local_component ($conf, $component, $agent_id, $dbh);
		enterprise_hook('pandora_create_module_from_local_component',[$conf, $component, $agent_id, $dbh]);
	}
}
##############################################################################
# Create policy data module from local component.
# Related option: --create_policy_data_module_from_local_component
##############################################################################
sub cli_create_policy_data_module_from_local_component() {
	my ($policy_name, $component_name) = @ARGV[2..3];
	
	my $policy_id = enterprise_hook('get_policy_id',[$dbh, safe_input($policy_name)]);
	exist_check($policy_id,'policy',$policy_name);
	
	my $lc_id = pandora_get_local_component_id($dbh, $component_name);
	exist_check($lc_id,'local component',$component_name);
	
	# Get local component data
	my $component = get_db_single_row ($dbh, 'SELECT * FROM tlocal_component WHERE id = ?', $lc_id);
	
	enterprise_hook('pandora_create_policy_data_module_from_local_component',[$conf, $component, $policy_id, $dbh]);
}

##############################################################################
# Create local component.
# Related option: --create_local_component
##############################################################################

sub cli_create_local_component() {

	my ($component_name, $data, $description, $id_os, $os_version, $id_network_component_group, $type,
		$min,$max,$module_interval, $id_module_group, $history_data, $min_warning, $max_warning, $str_warning,
		$min_critical, $max_critical, $str_critical, $min_ff_event, $post_process, $unit, $wizard_level,
	    $critical_instructions, $warning_instructions, $unknown_instructions, $critical_inverse, $warning_inverse,
	    $id_category, $tags, $disabled_types_event, $min_ff_event_normal, $min_ff_event_warning, $min_ff_event_critical,
	    $each_ff, $ff_timeout) = @ARGV[2..37];
	
	my %parameters;
	
	$parameters{'name'} = safe_input($component_name);
	my $data_aux = safe_input($data);
	$data_aux =~ s/&#92;n/&#x0a;/g;
	$parameters{'data'} = $data_aux;
	$parameters{'description'} = safe_input($description) unless !defined ($description);
	$parameters{'id_os'} = $id_os unless !defined ($id_os);
	$parameters{'type'} = $type unless !defined ($type);
	if (defined $id_network_component_group) {
		$parameters{'id_network_component_group'} = $id_network_component_group;
	} else {
		$parameters{'id_network_component_group'} = 1;
	}
	$parameters{'max'} = $max unless !defined ($max);
	$parameters{'min'} = $min unless !defined ($min);
	$parameters{'module_interval'} = $module_interval unless !defined ($module_interval);
	$parameters{'id_module_group'} = $id_module_group unless !defined ($id_module_group);
	$parameters{'history_data'} = safe_input($history_data) unless !defined ($history_data);
	$parameters{'min_warning'} = $min_warning unless !defined ($min_warning);
	$parameters{'max_warning'} = $max_warning unless !defined ($max_warning);
	$parameters{'str_warning'} = $str_warning unless !defined ($str_warning);
	$parameters{'min_critical'} = $min_critical unless !defined ($min_critical);
	$parameters{'max_critical'} = $max_critical unless !defined ($max_critical);
	$parameters{'str_critical'} = $str_critical unless !defined ($str_critical);
	$parameters{'min_ff_event'} = $min_ff_event unless !defined ($min_ff_event);
	$parameters{'post_process'} = $post_process unless !defined ($post_process);
	$parameters{'unit'} = $unit  unless !defined ($unit);
	$parameters{'wizard_level'} = $wizard_level unless !defined ($wizard_level);
	$parameters{'critical_instructions'} = safe_input($critical_instructions) unless !defined ($critical_instructions);
	$parameters{'warning_instructions'} = safe_input($warning_instructions) unless !defined ($warning_instructions);
	$parameters{'unknown_instructions'} = safe_input($unknown_instructions) unless !defined ($unknown_instructions);
	$parameters{'critical_inverse'} = $critical_inverse unless !defined ($critical_inverse);
	$parameters{'warning_inverse'} = $warning_inverse unless !defined ($warning_inverse);
	$parameters{'id_category'} = $id_category unless !defined ($id_category);
	$parameters{'tags'} = safe_input($tags) unless !defined ($tags);

	my $disabled_types_event_hash = {};
	if ($disabled_types_event) {
		$disabled_types_event_hash->{'going_unknown'} = 0;
	}
	else {
		$disabled_types_event_hash->{'going_unknown'} = 1;
	}
	my $disabled_types_event_json = encode_json($disabled_types_event_hash);
	$parameters{'disabled_types_event'} = $disabled_types_event_json unless !defined ($disabled_types_event);
	
	$parameters{'min_ff_event_normal'} = $min_ff_event_normal unless !defined ($min_ff_event_normal);
	$parameters{'min_ff_event_warning'} = $min_ff_event_warning unless !defined ($min_ff_event_warning);
	$parameters{'min_ff_event_critical'} = $min_ff_event_critical unless !defined ($min_ff_event_critical);
	$parameters{'each_ff'} = $each_ff unless !defined ($each_ff);
	$parameters{'ff_timeout'} = $ff_timeout unless !defined ($ff_timeout);
	
	my $component_id = enterprise_hook('pandora_create_local_component_from_hash',[$conf, \%parameters, $dbh]);
}

##############################################################################
# Create a new tag.
##############################################################################

sub cli_create_tag() {
	my ($tag_name, $tag_description, $tag_url, $tag_email) = @ARGV[2..5];

	# Call the API.
	my $result = manage_api_call(\%conf, 'set', 'create_tag', undef, undef, "$tag_name|$tag_description|$tag_url|$tag_email");
	print "\n$result\n";
}

##############################################################################
# Add a tag to the specified profile and group. 
##############################################################################

sub cli_add_tag_to_user_profile() {
	my ($user_id, $tag_name, $group_name, $profile_name) = @ARGV[2..5];

	# Check the user.
	my $user_exists = get_user_exists($dbh, $user_id);
	exist_check($user_exists, 'user', $user_id);

	# Check the group.
	my $group_id;
	if ($group_name eq 'All') {
		$group_id = 0;
	} else {
		$group_id = get_group_id($dbh, $group_name);
		exist_check($group_id, 'group', $group_name);
	}

	# Check the profile.
	my $profile_id = get_profile_id($dbh, $profile_name);
	exist_check($profile_id, 'profile', $profile_name);

	# Make sure the tag exists.
	my $tag_id = get_tag_id($dbh, $tag_name);
	exist_check($tag_id, 'tag', $tag_name);

	# Make sure the profile is associated to the user.
	my $user_profile_id = get_user_profile_id($dbh, $user_id, $profile_id, $group_id);
	exist_check($user_profile_id, 'given profile and group combination for user', $user_id);

	# Call the API.
	my $result = manage_api_call(\%conf, 'set', 'tag_user_profile', $user_id, $tag_id, "$group_id|$profile_id");
	print "\n$result\n";
}

##############################################################################
# Add a tag to the specified profile and group. 
##############################################################################

sub cli_add_tag_to_module() {
	my ($agent_name, $module_name, $tag_name) = @ARGV[2..4];

	# Check the tag.
	my $tag_id = get_tag_id($dbh, $tag_name);
	exist_check($tag_id, 'tag', $tag_name);

	# Check the agent.
	my $agent_id = get_agent_id($dbh, $agent_name);
	exist_check($agent_id, 'agent', $agent_name);

	# Check the module.
	my $module_id = get_agent_module_id($dbh, $module_name, $agent_id);
	exist_check($module_id, 'module name', $module_name);

	# Call the API.
	my $result = manage_api_call(\%conf, 'set', 'add_tag_module', $module_id, $tag_id);
	print "\n$result\n";
}

##############################################################################
# Only meta migrate agent
##############################################################################
sub cli_migration_agent_queue() {
	my ($id_agent, $source_name, $target_name, $only_db) = @ARGV[2..5];

	if( !defined($id_agent) || !defined($source_name) || !defined($target_name) ){
		print "\n0\n";
	}

	if(!defined($only_db)){
		$only_db = 0;
	}

	# Call the API.
	my $result = manage_api_call( $conf, 'set', 'migrate_agent', $id_agent, 0, "$source_name|$target_name|$only_db" );
	print "\n$result\n";
}

##############################################################################
# Only meta is migrate agent
##############################################################################
sub cli_migration_agent() {
	my ($id_agent) = @ARGV[2];

	if( !defined($id_agent) ){
		print "\n0\n";
	}

	# Call the API.
	my $result = manage_api_call( $conf, 'get', 'migrate_agent', $id_agent);

	if( defined($result) && "$result" ne "" ){
		print "\n1\n";
	}
	else{
		print "\n0\n";
	}
}

sub cli_apply_module_template() {
	my ($id_template, $id_agent) = @ARGV[2..3];
	
	my @row = get_db_rows ($dbh,"select * from tagente where id_agente = ".$id_agent);
	
	return if (scalar (@row) == 0);
	
	my $name_template = get_db_value ($dbh,'select name from tnetwork_profile where id_np = '.$id_template);
	
	my @npc = get_db_rows($dbh,"select * from tnetwork_profile_component where id_np = ".$id_template);
		
	foreach my $component (@npc) {
		
		my @template_values = get_db_rows ($dbh,"SELECT * FROM tnetwork_component where id_nc = ".$component->{'id_nc'});
		
		return if (scalar (@template_values) == 0);
		
		foreach my $element (@template_values) {
			my $agent_values;
			$agent_values->{'id_agente'} = $id_agent;
			$agent_values->{'id_tipo_modulo'} = $element->{"type"};
			$agent_values->{'descripcion'} = 'Created by template '.$name_template.' '.$element->{"description"};
			$agent_values->{'max'} = $element->{"max"};
			$agent_values->{'min'} = $element->{"min"};
			$agent_values->{'module_interval'} = $element->{"module_interval"};
			$agent_values->{'tcp_port'} = $element->{"tcp_port"};
			$agent_values->{'tcp_send'} = $element->{"tcp_send"};
			$agent_values->{'tcp_rcv'} = $element->{"tcp_rcv"};
			$agent_values->{'snmp_community'} = $element->{"snmp_community"};
			$agent_values->{'snmp_oid'} = $element->{"snmp_oid"};
			$agent_values->{'ip_target'} = $row[0]->{"direccion"};
			$agent_values->{'id_module_group'} = $element->{"id_module_group"};
			$agent_values->{'id_modulo'} = $element->{"id_modulo"};
			$agent_values->{'plugin_user'} = $element->{"plugin_user"};
			$agent_values->{'plugin_pass'} = $element->{"plugin_pass"};
			$agent_values->{'plugin_parameter'} = $element->{"plugin_parameter"};
			$agent_values->{'unit'} = $element->{"unit"};
			$agent_values->{'max_timeout'} = $element->{"max_timeout"};
			$agent_values->{'max_retries'} = $element->{"max_retries"};
			$agent_values->{'id_plugin'} = $element->{"id_plugin"};
			$agent_values->{'post_process'} = $element->{"post_process"};
			$agent_values->{'dynamic_interval'} = $element->{"dynamic_interval"};
			$agent_values->{'dynamic_max'} = $element->{"dynamic_max"};
			$agent_values->{'dynamic_min'} = $element->{"dynamic_min"};
			$agent_values->{'dynamic_two_tailed'} = $element->{"dynamic_two_tailed"};
			$agent_values->{'min_warning'} = $element->{"min_warning"};
			$agent_values->{'max_warning'} = $element->{"max_warning"};
			$agent_values->{'str_warning'} = $element->{"str_warning"};
			$agent_values->{'min_critical'} = $element->{"min_critical"};
			$agent_values->{'max_critical'} = $element->{"max_critical"};
			$agent_values->{'str_critical'} = $element->{"str_critical"};
			$agent_values->{'critical_inverse'} = $element->{"critical_inverse"};
			$agent_values->{'warning_inverse'} = $element->{"warning_inverse"};
			$agent_values->{'critical_instructions'} = $element->{"critical_instructions"};
			$agent_values->{'warning_instructions'} = $element->{"warning_instructions"};
			$agent_values->{'unknown_instructions'} = $element->{"unknown_instructions"};
			$agent_values->{'id_category'} = $element->{"id_category"};
			$agent_values->{'macros'} = $element->{"macros"};
			$agent_values->{'each_ff'} = $element->{"each_ff"};
			$agent_values->{'min_ff_event'} = $element->{"min_ff_event"};
			$agent_values->{'min_ff_event_normal'} = $element->{"min_ff_event_normal"};
			$agent_values->{'min_ff_event_warning'} = $element->{"min_ff_event_warning"};
			$agent_values->{'min_ff_event_critical'} = $element->{"min_ff_event_critical"};
			$agent_values->{'nombre'} = $element->{"name"};
						
			my @tags;
			if($element->{"tags"} ne '') {
				@tags = split(',', $element->{"tags"});
			}
			
			my $module_name_check = get_db_value ($dbh,'select id_agente_modulo from tagente_modulo where delete_pending = 0 and nombre ="'.$agent_values->{'nombre'}.'" and id_agente = '.$id_agent);
				
			if (!defined($module_name_check)) {
				
				my $id_agente_modulo = pandora_create_module_from_hash(\%conf,$agent_values,$dbh);
				 
				if ($id_agente_modulo != -1) {
					
					foreach my $tag_name (@tags) {
						
						my $tag_id = get_db_value($dbh,'select id_tag from ttag where name = "'.$tag_name.'"');
							
						db_do($dbh,'insert into ttag_module (id_tag,id_agente_modulo) values ("'.$tag_id.'","'.$id_agente_modulo.'")');
					
					}
				}
			}
		}
	}
}

##############################################################################
# Create an cluster.
# Related option: --new_cluster
##############################################################################
sub cli_new_cluster() {
	my ($cluster_name,$cluster_type,$description,$group_id) = @ARGV[2..5];
	
	# Call the API.
	my $result = manage_api_call( $conf, 'set', 'new_cluster', undef, undef, "$cluster_name|$cluster_type|$description|$group_id");
	
	if( defined($result) && "$result" ne "" ){
		print "\n1\n";
	}
	else{
		print "\n0\n";
	}
}

##############################################################################
# Assign an agent to cluster.
# Related option: --add_cluster_agent
##############################################################################
sub cli_add_cluster_agent() {
	my ($other) = @ARGV[2..2];
	
	# Call the API.
	my $result = manage_api_call( $conf, 'set', 'add_cluster_agent', undef, undef, $other);
	
	if( defined($result) && "$result" ne "" ){
		print "\n1\n";
	}
	else{
		print "\n0\n";
	}
}

##############################################################################
# Add item to cluster.
# Related option: --add_cluster_item
##############################################################################
sub cli_add_cluster_item() {
	my ($other) = @ARGV[2..2];
	
	# Call the API.
	my $result = manage_api_call( $conf, 'set', 'add_cluster_item', undef, undef, $other);
	
	if( defined($result) && "$result" ne "" ){
		print "\n1\n";
	}
	else{
		print "\n0\n";
	}
}

##############################################################################
# Delete cluster.
# Related option: --delete_cluster
##############################################################################
sub cli_delete_cluster() {
	my ($id) = @ARGV[2..2];
	
	# Call the API.
	my $result = manage_api_call( $conf, 'set', 'delete_cluster', $id);
	
	if( defined($result) && "$result" ne "" ){
		print "\n1\n";
	}
	else{
		print "\n0\n";
	}
}

##############################################################################
# Delete cluster item.
# Related option: --delete_cluster_item
##############################################################################
sub cli_delete_cluster_agent() {
	my ($id_agent,$id_cluster) = @ARGV[2..3];
	
	# Call the API.
	my $result = manage_api_call( $conf, 'set', 'delete_cluster_agent', undef, undef, "$id_agent|$id_cluster");
	
	if( defined($result) && "$result" ne "" ){
		print "\n1\n";
	}
	else{
		print "\n0\n";
	}
}

##############################################################################
# Delete cluster item.
# Related option: --delete_cluster_item
##############################################################################
sub cli_delete_cluster_item() {
	my ($id) = @ARGV[2..2];
	
	# Call the API.
	my $result = manage_api_call( $conf, 'set', 'delete_cluster_item', $id);
	
	if( defined($result) && "$result" ne "" ){
		print "\n1\n";
	}
	else{
		print "\n0\n";
	}
}

##############################################################################
# get cluster status.
# Related option: --get_cluster_status
##############################################################################

sub cli_get_cluster_status() {
	my ($id) = @ARGV[2..2];
	
	# Call the API.
	my $result = manage_api_call( $conf, 'get', 'cluster_status', $id);
	
	if( defined($result) && "$result" ne "" ){
		print "\n1\n";
	}
	else{
		print "\n0\n";
	}
}

##############################################################################
# Set an agent disabled and with standby.
# Related option: --set_disabled_and_standby
##############################################################################

sub cli_set_disabled_and_standby() {
	my ($id, $id_node, $value) = @ARGV[2..4];
	$id_node = 0 unless defined($id_node);
	$value = 1 unless defined($value); #Set to disabled by default

	# Call the API.
	my $result = manage_api_call(
		$conf, 'set', 'disabled_and_standby', $id, $id_node, $value
	);

	my $exit_code =  (defined($result) && "$result" eq "1") ? "1" : "0";
	print "\n$exit_code\n";
}

##############################################################################
# Resets module counts and alert counts in the agents.
# Related option: --reset_agent_counts
##############################################################################

sub cli_reset_agent_counts() {
	my $agent_id = @ARGV[2];

	my $result = manage_api_call(\%conf,'set', 'reset_agent_counts', $agent_id);
	print "$result \n\n ";

}


##############################################################################
# Set an event in progress.
# Related option: --event_in_progress
##############################################################################

sub cli_event_in_progress() {
	my $event_id = @ARGV[2];

	# Call the API.
	my $result = manage_api_call(
		$conf, 'set', 'event_in_progress', $event_id
	);

	print "\n$result\n";
}

##############################################################################
# Validates an alert given id alert, id module, id angent and template name.
##############################################################################
sub pandora_validate_alert_id($$$$) {
	my ($id_alert_agent_module, $agent_id, $id_agent_module, $template_name) = @_;


  my $group_id = get_agent_group($dbh, $agent_id);

	my $critical_instructions = get_db_value($dbh, 'SELECT critical_instructions from tagente_modulo WHERE id_agente_modulo = ?', $agent_id);
	my $warning_instructions = get_db_value($dbh, 'SELECT warning_instructions from tagente_modulo WHERE id_agente_modulo = ?', $agent_id);
	my $unknown_instructions = get_db_value($dbh, 'SELECT unknown_instructions from tagente_modulo WHERE id_agente_modulo = ?', $agent_id);

	my $parameters = {
		'times_fired' => 0,
		'internal_counter' => 0,
		};

	my $result = db_process_update($dbh, 'talert_template_modules', $parameters,{'id' => $id_alert_agent_module});

	return 0 unless $result != 0;

  my $module_name = safe_output(get_db_value($dbh, 'SELECT nombre FROM tagente_modulo WHERE id_agente_modulo = ?', $id_agent_module));


		# Update fired alert count on the agent
		db_process_update($dbh, 'tagente', {'update_alert_count' => 1}, {'id_agente' => $agent_id});

		my $event = 'Manual validation of alert '.$template_name.' assigned to '.$module_name.'';

		pandora_event(
			$conf,
			$event,
			$group_id, 
			$agent_id,
			0,
			$id_alert_agent_module,
			$id_agent_module,
			'alert_manual_validation',
			1,
			$dbh,
			0,
			'',
			'',
			'',
			'',
			$critical_instructions,
			$warning_instructions,
			$unknown_instructions,
			''
		);

    return 1;
}

##############################################################################
# Get GIS data from agent
##############################################################################

sub cli_get_gis_agent(){

	my $agent_id = @ARGV[2];

	my $result = manage_api_call(\%conf,'get', 'gis_agent', $agent_id);
	print "$result \n\n ";

}

##############################################################################
# Set GIS data for specified agent
##############################################################################

sub cli_insert_gis_data(){

	my ($agent_id, $latitude, $longitude, $altitude) = @ARGV[2..5];
	my $agent_id = @ARGV[2];
	my @position = @ARGV[3..5];
	my $other = join('|', @position);

	my $result = manage_api_call(\%conf,'set', 'gis_agent_only_position', $agent_id, undef, "$other");
	print "$result \n\n ";

}
