package PandoraFMS::Config;
##########################################################################
# Configuration Package
# Pandora FMS. the Flexible Monitoring System. http://www.pandorafms.org
##########################################################################
# Copyright (c) 2005-2023 Pandora FMS
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU Lesser General Public License 
# as published by the Free Software Foundation; version 2.
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
##########################################################################

use warnings;
use POSIX qw(strftime);
use Time::Local;

# Default lib dir for RPM and DEB packages
BEGIN { push @INC, '/usr/lib/perl5'; }

use PandoraFMS::Tools;
use PandoraFMS::DB;

require Exporter;

our @ISA = ("Exporter");
our %EXPORT_TAGS = ( 'all' => [ qw( ) ] );
our @EXPORT_OK = ( @{ $EXPORT_TAGS{'all'} } );
our @EXPORT = qw( 	
	pandora_help_screen
	pandora_init
	pandora_load_config
	pandora_start_log
	pandora_get_sharedconfig
	pandora_get_tconfig_token
	pandora_set_tconfig_token
	pandora_get_initial_product_name
	pandora_get_initial_copyright_notice
	);

# version: Defines actual version of Pandora Server for this module only
my $pandora_version = "7.0NG.775";
my $pandora_build = "240213";
our $VERSION = $pandora_version." ".$pandora_build;

# Setup hash
my %pa_config;

# Public functions
##########################################################################
# SUB pandora_help_screen()
# Shows a help screen and exits
##########################################################################

sub help_screen {
	print "\nSyntax: \n\n pandora_server [ options ] < fullpathname to configuration file > \n\n";
	print "Following options are optional : \n";
	print "	-d        :  Debug mode activated. Writes extensive information in the logfile \n";
	print "	-D        :  Daemon mode (runs in background)\n";
	print "	-P <file> :  Store PID to file.\n";
	print "	-h        :  This screen. Shows a little help screen \n";
	print " \n";
	exit;
}

##########################################################################
# SUB pandora_init ( %pandora_cfg )
# Makes the initial parameter parsing, initializing and error checking
##########################################################################

sub pandora_init {
	my $pa_config = $_[0];
	my $init_string = $_[1];
	print "$init_string v$pandora_version Build $pandora_build\n\n";
	print "This program is OpenSource, licensed under the terms of GPL License version 2.\n";
	print "You can download latest versions and documentation at official web page.\n\n";

	# Load config file from command line
	if ($#ARGV == -1 ){
		print "I need at least one parameter: Complete path to " . pandora_get_initial_product_name() . " Server configuration file \n";
		help_screen;
		exit;
	}
	$pa_config->{"verbosity"}=0;	# Verbose 1 by default
	$pa_config->{"daemon"}=0;	# Daemon 0 by default
	$pa_config->{'PID'}="";	# PID file not exist by default
	$pa_config->{"quiet"}=0;	# Daemon 0 by default

	# If there are not valid parameters
	my $parametro;
	my $ltotal=$#ARGV; my $ax;

	for ($ax=0;$ax<=$ltotal;$ax++){
		$parametro = $ARGV[$ax];
		if (($parametro =~ m/-h\z/i ) || ($parametro =~ m/help\z/i )) {
			help_screen();
		}
		elsif ($parametro =~ m/^-P\z/i) {
			$pa_config->{'PID'}= clean_blank($ARGV[$ax+1]);
		}
		elsif ($parametro =~ m/-d\z/) {
			$pa_config->{"verbosity"}=10;
		}
		elsif ($parametro =~ m/-D\z/) {
			$pa_config->{"daemon"}=1;
		}
		else {
			($pa_config->{"pandora_path"} = $parametro);
		}
	}
	if (!defined ($pa_config->{"pandora_path"}) || $pa_config->{"pandora_path"} eq ""){
		print "[ERROR] I need at least one parameter: Complete path to " . pandora_get_initial_product_name() . " configuration file. \n";
		print "For example: ./pandora_server /etc/pandora/pandora_server.conf \n\n";
		exit;
	}
}

##########################################################################
# Read some config tokens from database set by the console
##########################################################################
sub pandora_get_sharedconfig ($$) {
	my ($pa_config, $dbh) = @_;

	# Agentaccess option

	# Realtimestats 0 disabled, 1 enabled.
	# Master servers will generate all the information (global tactical stats).
	# and each server will generate it's own server stats (lag, etc).
	$pa_config->{"realtimestats"} = pandora_get_tconfig_token ($dbh, 'realtimestats', 0);

	# Stats_interval option
	$pa_config->{"stats_interval"} = pandora_get_tconfig_token ($dbh, 'stats_interval', 300);

	# Netflow configuration options
	$pa_config->{"activate_netflow"} = pandora_get_tconfig_token ($dbh, 'activate_netflow', 0);
	$pa_config->{"netflow_path"} = pandora_get_tconfig_token ($dbh, 'netflow_path', '/var/spool/pandora/data_in/netflow');
	$pa_config->{"netflow_interval"} = pandora_get_tconfig_token ($dbh, 'netflow_interval', 1800);
	$pa_config->{"netflow_daemon"} = pandora_get_tconfig_token ($dbh, 'netflow_daemon', '/usr/bin/nfcapd');

	# Sflow configuration options
	$pa_config->{"activate_sflow"} = pandora_get_tconfig_token ($dbh, 'activate_sflow', 0);
	$pa_config->{"sflow_path"} = pandora_get_tconfig_token ($dbh, 'sflow_path', '/var/spool/pandora/data_in/sflow');
	$pa_config->{"sflow_interval"} = pandora_get_tconfig_token ($dbh, 'sflow_interval', 300);
	$pa_config->{"sflow_daemon"} = pandora_get_tconfig_token ($dbh, 'sflow_daemon', '/usr/bin/nfcapd');

	# Log module configuration
	$pa_config->{"log_dir"} = pandora_get_tconfig_token ($dbh, 'log_dir', '/var/spool/pandora/data_in/log');
	$pa_config->{"log_interval"} = pandora_get_tconfig_token ($dbh, 'log_interval', 3600);

	# Pandora FMS Console's attachment directory
	$pa_config->{"attachment_dir"} = pandora_get_tconfig_token ($dbh, 'attachment_store', '/var/www/pandora_console/attachment');

	#Public url
	$pa_config->{'public_url'} = pandora_get_tconfig_token ($dbh, 'public_url', 'http://localhost/pandora_console');

	# Node with a metaconsole license.
	# NOTE: This must be read when checking license limits!
	#$pa_config->{"node_metaconsole"} = pandora_get_tconfig_token ($dbh, 'node_metaconsole', 0);

	$pa_config->{"provisioning_mode"} = pandora_get_tconfig_token ($dbh, 'provisioning_mode', '');

	$pa_config->{"event_storm_protection"} = pandora_get_tconfig_token ($dbh, 'event_storm_protection', 0);

	$pa_config->{"use_custom_encoding"} = pandora_get_tconfig_token ($dbh, 'use_custom_encoding', 0);

	# PandoraFMS product name
	$pa_config->{'rb_product_name'} = enterprise_hook(
		'pandora_get_product_name',
		[$dbh]
	);
	$pa_config->{'rb_product_name'} = 'Pandora FMS' unless (defined ($pa_config->{'rb_product_name'}) && $pa_config->{'rb_product_name'} ne '');

	# Mail transport agent configuration. Local configuration takes precedence.
	if ($pa_config->{"mta_local"} eq 0) {
		$pa_config->{"mta_address"} = pandora_get_tconfig_token ($dbh, 'email_smtpServer', '');
		$pa_config->{"mta_from"} = '"' . pandora_get_tconfig_token ($dbh, 'email_from_name', 'Pandora FMS') . '" <' . 
		                           pandora_get_tconfig_token ($dbh, 'email_from_dir', 'pandora@pandorafms.org') . '>';
		$pa_config->{"mta_pass"} = pandora_get_tconfig_token ($dbh, 'email_password', '');
		$pa_config->{"mta_port"} = pandora_get_tconfig_token ($dbh, 'email_smtpPort', '');
		$pa_config->{"mta_user"} = pandora_get_tconfig_token ($dbh, 'email_username', '');
		$pa_config->{"mta_encryption"} = pandora_get_tconfig_token ($dbh, 'email_encryption', '');

		# Auto-negotiate the auth mechanism, since it cannot be set from the console.
		# Do not include PLAIN, it generates the following error:
		# 451 4.5.0 SMTP protocol violation, see RFC 2821
		$pa_config->{"mta_auth"} = 'DIGEST-MD5 CRAM-MD5 LOGIN';

		# Fix the format of mta_encryption.
		if ($pa_config->{"mta_encryption"} eq 'tls') {
			$pa_config->{"mta_encryption"} = 'starttls';
		}
		elsif ($pa_config->{"mta_encryption"} =~ m/^ssl/) {
			$pa_config->{"mta_encryption"} = 'ssl';
		}
		else {
			$pa_config->{"mta_encryption"} = 'none';
		}
	}

	# Server identifier
	$pa_config->{'server_unique_identifier'} = pandora_get_tconfig_token ($dbh, 'server_unique_identifier', '');

	# Vulnerability scans
	$pa_config->{'agent_vulnerabilities'} = pandora_get_tconfig_token ($dbh, 'agent_vulnerabilities', 0);
}

##########################################################################
# Read external configuration file
##########################################################################

sub pandora_load_config {
	my $pa_config = $_[0];
	my $archivo_cfg = $pa_config->{'pandora_path'};
	my $buffer_line;
	my @command_line;
	my $tbuf;

	# Default values
	$pa_config->{'version'} = $pandora_version;
	$pa_config->{'build'} = $pandora_build;
	$pa_config->{"dbengine"} = "mysql";
	$pa_config->{"dbuser"} = "pandora";
	$pa_config->{"dbpass"} = "pandora";
	$pa_config->{"dbhost"} = "localhost";
	$pa_config->{'dbport'} = undef; # set to standard port of "dbengine" later
	$pa_config->{"dbname"} = "pandora";
	$pa_config->{"dbssl"} = 0;
	$pa_config->{"dbsslcapath"} = "";
	$pa_config->{"dbsslcafile"} = "";
	$pa_config->{"verify_mysql_ssl_cert"} = "0";
	$pa_config->{"basepath"} = $pa_config->{'pandora_path'}; # Compatibility with Pandora 1.1
	$pa_config->{"incomingdir"} = "/var/spool/pandora/data_in";
	$pa_config->{"user"}  = "pandora"; # environment settings default user owner for files generated
	$pa_config->{"group"} = "apache"; # environment settings default group owner for files generated
	$pa_config->{"umask"} = "0007"; # environment settings umask applied over chmod (A & (not B))
	$pa_config->{"server_threshold"} = 30;
	$pa_config->{"alert_threshold"} = 60;
	$pa_config->{"graph_precision"} = 1;
	$pa_config->{"log_file"} = "/var/log/pandora_server.log";
	$pa_config->{"errorlog_file"} = "/var/log/pandora_server.error";
	$pa_config->{"networktimeout"} = 5;	# By default, not in config file yet
	$pa_config->{"pandora_master"} = 1;	# on by default
	$pa_config->{"pandora_check"} = 0; 	# Deprecated since 2.0
	$pa_config->{"servername"} = `hostname`;
	$pa_config->{"servername"} =~ s/\s//g; # Replace ' ' chars
	$pa_config->{"dataserver"} = 1; # default
	$pa_config->{"networkserver"} = 1; # default
	$pa_config->{"snmpconsole"} = 1; # default
	$pa_config->{"discoveryserver"} = 0; # default
	$pa_config->{"wmiserver"} = 1; # default
	$pa_config->{"pluginserver"} = 1; # default
	$pa_config->{"predictionserver"} = 1; # default
	$pa_config->{"exportserver"} = 1; # default
	$pa_config->{"inventoryserver"} = 1; # default
	$pa_config->{"webserver"} = 1; # 3.0
	$pa_config->{"web_timeout"} = 60; # 6.0SP5
	$pa_config->{"transactional_pool"} = $pa_config->{"incomingdir"} . "/" . "trans"; # Default, introduced on 6.1
	$pa_config->{'snmp_logfile'} = "/var/log/pandora_snmptrap.log";
	$pa_config->{"network_threads"} = 3; # Fixed default
	$pa_config->{"keepalive"} = 60; # 60 Seconds initially for server keepalive
	$pa_config->{"keepalive_orig"} = $pa_config->{"keepalive"};
	$pa_config->{"icmp_checks"} = 1; # Introduced on 1.3.1
	$pa_config->{"icmp_packets"} = 1; # > 5.1SP2
	$pa_config->{"critical_on_error"} = 1; # > 7.0.774
	$pa_config->{"alert_recovery"} = 0; # Introduced on 1.3.1
	$pa_config->{"snmp_checks"} = 1; # Introduced on 1.3.1
	$pa_config->{"snmp_timeout"} = 8; # Introduced on 1.3.1
	$pa_config->{"rcmd_timeout"} = 10; # Introduced on 7.0.740
	$pa_config->{"rcmd_timeout_bin"} = '/usr/bin/timeout'; # Introduced on 7.0.743
	$pa_config->{"snmp_trapd"} = '/usr/sbin/snmptrapd'; # 3.0
	$pa_config->{"tcp_checks"} = 1; # Introduced on 1.3.1
	$pa_config->{"tcp_timeout"} = 20; # Introduced on 1.3.1
	$pa_config->{"snmp_proc_deadresponse"} = 1; # Introduced on 1.3.1 10 Feb08
	$pa_config->{"plugin_threads"} = 2; # Introduced on 2.0
	$pa_config->{"plugin_exec"} = '/usr/bin/timeout'; # 3.0
	$pa_config->{"recon_threads"} = 2; # Introduced on 2.0
	$pa_config->{"discovery_threads"} = 2; # Introduced on 732
	$pa_config->{"prediction_threads"} = 1; # Introduced on 2.0
	$pa_config->{"plugin_timeout"} = 5; # Introduced on 2.0
	$pa_config->{"wmi_threads"} = 2; # Introduced on 2.0
	$pa_config->{"wmi_timeout"} = 5; # Introduced on 2.0
	$pa_config->{"wmi_client"} = 'wmic'; # 3.0
	$pa_config->{"dataserver_threads"} = 2; # Introduced on 2.0
	$pa_config->{"inventory_threads"} = 2; # 2.1
	$pa_config->{"export_threads"} = 1; # 3.0
	$pa_config->{"web_threads"} = 1; # 3.0
	$pa_config->{"web_engine"} = 'curl'; # 5.1
	$pa_config->{"activate_gis"} = 0; # 3.1
	$pa_config->{"location_error"} = 50; # 3.1
	$pa_config->{"recon_reverse_geolocation_file"} = ''; # 3.1
	$pa_config->{"recon_location_scatter_radius"} = 50; # 3.1
	$pa_config->{"update_parent"} = 0; # 3.1
	$pa_config->{"google_maps_description"} = 0;
	$pa_config->{'openstreetmaps_description'} = 0;
	$pa_config->{"eventserver"} = 0; # 4.0
	$pa_config->{"eventserver_threads"} = 1; # 4.0
	$pa_config->{"logserver"} = 0; # 7.774
	$pa_config->{"logserver_threads"} = 1; # 7.774
	$pa_config->{"event_window"} = 3600; # 4.0
	$pa_config->{"log_window"} = 3600; # 7.741
	$pa_config->{"elastic_query_size"} = 10; # 7.754 Elements per request (ELK)
	$pa_config->{"event_server_cache_ttl"} = 10; # 7.754
	$pa_config->{"preload_windows"} = 0; # 7.741
	$pa_config->{"icmpserver"} = 0; # 4.0
	$pa_config->{"icmp_threads"} = 3; # 4.0
	$pa_config->{"snmpserver"} = 0; # 4.0
	$pa_config->{"snmp_threads"} = 3; # 4.0
	$pa_config->{"block_size"} = 15; # 4.0
	$pa_config->{"max_queue_files"} = 500; 
	$pa_config->{"snmp_ignore_authfailure"} = 1; # 5.0
	$pa_config->{"snmp_pdu_address"} = 0; # 5.0
	$pa_config->{"snmp_storm_protection"} = 0; # 5.0
	$pa_config->{"snmp_storm_timeout"} = 600; # 5.0
	$pa_config->{"snmp_storm_silence_period"} = 0; # 7.0
	$pa_config->{"snmp_delay"} = 0; # > 6.0SP3
	$pa_config->{"snmpconsole_threads"} = 1; # 5.1
	$pa_config->{"translate_variable_bindings"} = 0; # 5.1
	$pa_config->{"translate_enterprise_strings"} = 1; # 5.1
	$pa_config->{"syncserver"} = 0; # 7.0
	$pa_config->{"sync_address"} = ''; # 7.0
	$pa_config->{"sync_block_size"} = 65535; # 7.0
	$pa_config->{"sync_ca"} = ''; # 7.0
	$pa_config->{"sync_cert"} = ''; # 7.0
	$pa_config->{"sync_key"} = ''; # 7.0
	$pa_config->{"sync_port"} = '41121'; # 7.0
	$pa_config->{"sync_retries"} = 2; # 7.0
	$pa_config->{"sync_timeout"} = 5; # 7.0
	$pa_config->{"dynamic_updates"} = 5; # 7.0
	$pa_config->{"dynamic_warning"} = 25; # 7.0
	$pa_config->{"dynamic_constant"} = 10; # 7.0
	$pa_config->{"mssql_driver"} = undef; # 745 
	$pa_config->{"snmpconsole_lock"} = 0; # 755.
	$pa_config->{"snmpconsole_period"} = 0; # 755.
	$pa_config->{"snmpconsole_threshold"} = 0; # 755.
	
	# Internal MTA for alerts, each server need its own config.
	$pa_config->{"mta_address"} = ''; # Introduced on 2.0
	$pa_config->{"mta_port"} = ''; # Introduced on 2.0
	$pa_config->{"mta_user"} = ''; # Introduced on 2.0
	$pa_config->{"mta_pass"} = ''; # Introduced on 2.0
	$pa_config->{"mta_auth"} = 'none'; # Introduced on 2.0 (Support LOGIN PLAIN CRAM-MD5 DIGEST-MD)
	$pa_config->{"mta_from"} = 'pandora@localhost'; # Introduced on 2.0 
	$pa_config->{"mta_encryption"} = 'none'; # 7.0 739
	$pa_config->{"mta_local"} = 0; # 7.0 739
	$pa_config->{"mail_in_separate"} = 1; # 1: eMail deliver alert mail in separate mails.
					      # 0: eMail deliver 1 mail with all destination.

	# nmap for recon OS fingerprinting and tcpscan (optional)
	$pa_config->{"nmap"} = "/usr/bin/nmap";
	$pa_config->{"nmap_timing_template"} = 2; # > 5.1
	$pa_config->{"recon_timing_template"} = 3; # > 5.1

	$pa_config->{"fping"} = "/usr/sbin/fping"; # > 5.1SP2

	# Discovery SAP
	$pa_config->{"java"} = "/usr/bin/java";

	# Discovery SAP utils
	$pa_config->{"sap_utils"} = "/usr/share/pandora_server/util/recon_scripts/SAP";
	
	# Discovery SAP Artica environment
	$pa_config->{"sap_artica_test"} = 0;

	# Remote execution modules, option ssh_launcher
	$pa_config->{"ssh_launcher"} = "/usr/bin/ssh_launcher";

	# braa for enterprise snmp server
	$pa_config->{"braa"} = "/usr/bin/braa";

	# SNMP enterprise retries (for braa)
	$pa_config->{"braa_retries"} = 3; # 5.0

	# Winexe allows to exec commands on remote windows systems (optional)
	$pa_config->{"winexe"} = "/usr/bin/winexe";

	# PsExec allows to exec commands on remote windows systems from windows servers (optional)
	$pa_config->{"psexec"} = 'C:\PandoraFMS\Pandora_Server\bin\PsExec.exe';
	
	# plink allows to exec commands on remote linux systems from windows servers (optional)
	$pa_config->{"plink"} = 'C:\PandoraFMS\Pandora_Server\bin\plink.exe';

	# Snmpget for snmpget system command (optional)
	$pa_config->{"snmpget"} = "/usr/bin/snmpget";
	
	$pa_config->{'autocreate_group'} = -1;
	$pa_config->{'autocreate_group_force'} = 1;
	$pa_config->{'autocreate_group_name'} = '';
	$pa_config->{'autocreate'} = 1;

	# max log size (bytes)
	$pa_config->{'max_log_size'} = 1048576;

	# max log generation
	$pa_config->{'max_log_generation'} = 1;

	# Ignore the timestamp in the XML and use the file timestamp instead
	# If 1 => uses timestamp from received XML #5763.
	$pa_config->{'use_xml_timestamp'} = 1;

	# Server restart delay in seconds
	$pa_config->{'restart_delay'} = 60; 

	# Auto restart every x seconds
	$pa_config->{'auto_restart'} = 0; 

	# Restart server on error
	$pa_config->{'restart'} = 0; 

	# Self monitoring
	$pa_config->{'self_monitoring'} = 0; 

	# Self monitoring interval
	$pa_config->{'self_monitoring_interval'} = 300; # 5.1SP1

	# Self monitoring agent name.
	$pa_config->{'self_monitoring_agent_name'} = 'pandora.internals'; # 7.774

	# Process XML data files as a stack
	$pa_config->{"dataserver_lifo"} = 0; # 5.0

	# Patrol process of policies queue
	$pa_config->{"policy_manager"} = 0; # 5.0

	# Event auto-validation
	$pa_config->{"event_auto_validation"} = 1; # 5.0

	# Export events to a text file
	$pa_config->{"event_file"} = ''; # 5.0

	# Default event messages
	$pa_config->{"text_going_down_normal"} = "Module '_module_' is going to NORMAL (_data_)"; # 5.0
	$pa_config->{"text_going_up_critical"} = "Module '_module_' is going to CRITICAL (_data_)"; # 5.0
	$pa_config->{"text_going_up_warning"} = "Module '_module_' is going to WARNING (_data_)"; # 5.0
	$pa_config->{"text_going_down_warning"} = "Module '_module_' is going to WARNING (_data_)"; # 5.0
	$pa_config->{"text_going_unknown"} = "Module '_module_' is going to UNKNOWN"; # 5.0

	# Event auto-expiry time
	$pa_config->{"event_expiry_time"} = 0; # 5.0

	# Event auto-expiry time window
	$pa_config->{"event_expiry_window"} = 86400; # 5.0

	# Event auto-expiry time window
	$pa_config->{"claim_back_snmp_modules"} = 1; # 5.1
	
	# Auto-recovery of asynchronous modules.
	$pa_config->{"async_recovery"} = 1; # 5.1SP1

	# Database password encryption passphrase
	$pa_config->{"encryption_passphrase"} = ''; # 6.0

	# Unknown interval (as a multiple of the module's interval)
	$pa_config->{"unknown_interval"} = 2; # > 5.1SP2

	# -------------------------------------------------------------------------
	# This values are not stored in .conf files. 
	# This values should be stored in database, not in .conf files!
	# Default values are set here because if they are not present in config DB
	# don't get an error later.
	$pa_config->{"realtimestats"} = 0;
	$pa_config->{"stats_interval"} = 300;
	$pa_config->{"event_storm_protection"} = 0; 
	$pa_config->{"use_custom_encoding"} = 0; 
	$pa_config->{"node_metaconsole"} = 0; # > 7.0NG
	# -------------------------------------------------------------------------
	
	#SNMP Forwarding tokens
	$pa_config->{"snmp_forward_trap"}=0;
	$pa_config->{"snmp_forward_secName"}= '';
	$pa_config->{"snmp_forward_engineid"}= '';
	$pa_config->{"snmp_forward_authProtocol"}= '';
	$pa_config->{"snmp_forward_authPassword"}= '';
	$pa_config->{"snmp_forward_community"}= 'public';
	$pa_config->{"snmp_forward_privProtocol"}= '';
	$pa_config->{"snmp_forward_privPassword"}= '';
	$pa_config->{"snmp_forward_secLevel"}= '';
	$pa_config->{"snmp_forward_version"}= 2;
	$pa_config->{"snmp_forward_ip"}= '';
	
	# Global Timeout for Custom Commands Alerts
	$pa_config->{"global_alert_timeout"}= 15; # 6.0
	
	# Server Remote Config
	$pa_config->{"remote_config"}= 0; # 6.0
	                
	# Remote config server address
	$pa_config->{"remote_config_address"} = 'localhost'; # 6.0

	# Remote config server port
	$pa_config->{"remote_config_port"} = 41121; # 6.0

	# Remote config server options
	$pa_config->{"remote_config_opts"} = ''; # 6.0
	
	# Temp path for file sendinn and receiving
	$pa_config->{"temporal"} = '/tmp'; # 6.0

	# Warmup intervals.
	$pa_config->{"warmup_alert_interval"} = 0; # 6.1
	$pa_config->{"warmup_alert_on"} = 0; # 6.1
	$pa_config->{"warmup_event_interval"} = 0; # 6.1
	$pa_config->{"warmup_event_on"} = 0; # 6.1
	$pa_config->{"warmup_unknown_interval"} = 300; # 6.1
	$pa_config->{"warmup_unknown_on"} = 1; # 6.1

	$pa_config->{"wuxserver"} = 1; # 7.0
	$pa_config->{"wux_host"} = undef; # 7.0
	$pa_config->{"wux_port"} = 4444; # 7.0
	$pa_config->{"wux_browser"} = "*firefox"; # 7.0
	$pa_config->{"wux_webagent_timeout"} = 15; # 7.0
	$pa_config->{"clean_wux_sessions"} = 1; # 7.0.746 (only selenium 3)

	# Syslog Server
	$pa_config->{"syslogserver"} = 0; # 7.0.716
	$pa_config->{"syslog_file"} = '/var/log/messages/'; # 7.0.716
	$pa_config->{"syslog_max"} = 65535; # 7.0.716
	$pa_config->{"syslog_threads"} = 4; # 7.0.716
	$pa_config->{"syslog_blacklist"} = undef; # 7.0.773
	$pa_config->{"syslog_whitelist"} = undef; # 7.0 773

	# External .enc files for XML::Parser.
	$pa_config->{"enc_dir"} = ""; # > 6.0SP4

	# Enable (1) or disable (0) events related to the unknown status.
	$pa_config->{"unknown_events"} = 1; # > 6.0SP4

	$pa_config->{"thread_log"} = 0; # 7.0.717

	$pa_config->{"unknown_updates"} = 0; # 7.0.718

	$pa_config->{"provisioningserver"} = 1; # 7.0.720
	$pa_config->{"provisioningserver_threads"} = 1; # 7.0.720
	$pa_config->{"provisioning_cache_interval"} = 300; # 7.0.720
	
	$pa_config->{"autoconfigure_agents"} = 1; # 7.0.725
	$pa_config->{"autoconfigure_agents_threshold"} = 300; #7.0.764
	
	$pa_config->{'snmp_extlog'} = ""; # 7.0.726

	$pa_config->{"fsnmp"} = "/usr/bin/pandorafsnmp"; # 7.0.732

	$pa_config->{"event_inhibit_alerts"} = 0; # 7.0.737

	$pa_config->{"alertserver"} = 0; # 7.0.756
	$pa_config->{"alertserver_threads"} = 1; # 7.0.756
	$pa_config->{"alertserver_warn"} = 180; # 7.0.756
	$pa_config->{"alertserver_queue"} = 0; # 7.0.764

	$pa_config->{'ncmserver'} = 0; # 7.0.758
	$pa_config->{'ncmserver_threads'} = 1; # 7.0.758
	$pa_config->{'ncm_ssh_utility'} = '/usr/share/pandora_server/util/ncm_ssh_extension'; # 7.0.758

	$pa_config->{"pandora_service_cmd"} = 'service pandora_server'; # 7.0.761
	$pa_config->{"tentacle_service_cmd"} = 'service tentacle_serverd'; # 7.0.761
	$pa_config->{"tentacle_service_watchdog"} = 1; # 7.0.761

	$pa_config->{"dataserver_smart_queue"} = 0; # 7.0.765

	$pa_config->{"unknown_block_size"} = 1000; # 7.0.769

	$pa_config->{"netflowserver"} = 0; # 7.0.770
	$pa_config->{"netflowserver_threads"} = 1; # 7.0.770
	$pa_config->{"ha_mode"} = "pacemaker"; # 7.0.770
	$pa_config->{"ha_file"} = undef; # 7.0.770
	$pa_config->{"ha_hosts_file"} = '/var/spool/pandora/data_in/conf/pandora_ha_hosts.conf'; # 7.0.770
	$pa_config->{"ha_connect_retries"} = 2; # 7.0.770
	$pa_config->{"ha_connect_delay"} = 1; # 7.0.770
	$pa_config->{"ha_dbuser"} = undef; # 7.0.770
	$pa_config->{"ha_dbpass"} = undef; # 7.0.770
	$pa_config->{"ha_hosts"} = undef; # 7.0.770
	$pa_config->{"ha_resync"} = '/usr/share/pandora_server/util/pandora_ha_resync_slave.sh'; # 7.0.770
	$pa_config->{"ha_resync_log"} = '/var/log/pandora/pandora_ha_resync.log'; # 7.0.770
	$pa_config->{"ha_sshuser"} = 'pandora'; # 7.0.770
	$pa_config->{"ha_sshport"} = 22; # 7.0.770

	$pa_config->{"ha_max_splitbrain_retries"} = 2;
	$pa_config->{"ha_resync_sleep"} = 10;

	$pa_config->{"repl_dbuser"} = undef; # 7.0.770
	$pa_config->{"repl_dbpass"} = undef; # 7.0.770

	$pa_config->{"ssl_verify"} = 0; # 7.0 774

	$pa_config->{"madeserver"} = 0; # 774.

	$pa_config->{"mail_subject_encoding"} = 'MIME-Header'; # 776.

	# Check for UID0
	if ($pa_config->{"quiet"} != 0){
		if ($> == 0){
			printf " [W] Not all Pandora FMS components need to be executed as root\n";
			printf "	please consider starting it with a non-privileged user.\n";
		}
	}

	# Check for file
	if ( ! -f $archivo_cfg ) {
		printf "\n [ERROR] Cannot open configuration file at $archivo_cfg. \n";
		printf "	Please specify a valid " . pandora_get_initial_product_name() ." configuration file in command line. \n";
		print "	Standard configuration file is at /etc/pandora/pandora_server.conf \n";
		exit 1;
	}

	# Collect items from config file and put in an array 
	if (! open (CFG, "<:encoding(UTF-8)", $archivo_cfg)) {
		print "[ERROR] Error opening configuration file $archivo_cfg: $!.\n";
		exit 1;
	}

	while (<CFG>){
		$buffer_line = $_;
		if ($buffer_line =~ /^[a-zA-Z]/){ # begins with letters
			if ($buffer_line =~ m/([\w\-\_\.]+)\s([0-9\w\-\_\.\/\?\&\=\)\(\_\-\!\*\@\#\%\$\~\"\']+)/){
				push @command_line, $buffer_line;
			}
		}
	}
 	close (CFG);

 	# Process this array with commandline like options 
 	# Process input parameters

 	my @args = @command_line;
 	my $parametro;
 	my $ltotal=$#args; 
	my $ax;

 	# Has read setup file ok ?
 	if ( $ltotal == 0 ) {
		print "[ERROR] No valid setup tokens readed in $archivo_cfg ";
		exit;
 	}
 
 	for ($ax=0;$ax<=$ltotal;$ax++){
		$parametro = $args[$ax];

		if ($parametro =~ m/^incomingdir\s(.*)/i) {
			$tbuf= clean_blank($1); 
			if ($tbuf =~ m/^\.(.*)/){
				$pa_config->{"incomingdir"} =$pa_config->{"basepath"}.$1;
			} else {
				$pa_config->{"incomingdir"} = $tbuf;
			}
		}

		elsif ($parametro =~ m/^log_file\s(.*)/i) { 
			$tbuf= clean_blank($1);	
			if ($tbuf =~ m/^\.(.*)/){
				$pa_config->{"log_file"} = $pa_config->{"basepath"}.$1;
			} else {
				$pa_config->{"log_file"} = $tbuf;
			}
		}

		elsif ($parametro =~ m/^errorlog_file\s(.*)/i) { 
			$tbuf= clean_blank($1); 	
			if ($tbuf =~ m/^\.(.*)/){
				$pa_config->{"errorlog_file"} = $pa_config->{"basepath"}.$1;
			} else {
				$pa_config->{"errorlog_file"} = $tbuf;
			}
		}

	# MTA setup (2.0)
		elsif ($parametro =~ m/^mta_user\s(.*)/i) { 
			$pa_config->{'mta_user'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^mta_pass\s(.*)/i) { 
			$pa_config->{'mta_pass'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^mta_address\s(.*)/i) { 
			$pa_config->{'mta_address'}= clean_blank($1); 
			$pa_config->{'mta_local'}=1;
		}
		elsif ($parametro =~ m/^mta_port\s(.*)/i) { 
			$pa_config->{'mta_port'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^mta_auth\s(.*)/i) { 
			$pa_config->{'mta_auth'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^mta_from\s(.*)/i) { 
			$pa_config->{'mta_from'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^mta_encryption\s(.*)/i) { 
			$pa_config->{'mta_encryption'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^mail_in_separate\s+([0-9]*)/i) { 
			$pa_config->{'mail_in_separate'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^mail_subject_encoding\s(.*)/i) { 
			$pa_config->{'mail_subject_encoding'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^snmp_logfile\s(.*)/i) { 
			$pa_config->{'snmp_logfile'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^snmp_ignore_authfailure\s+([0-1])/i) { 
			$pa_config->{'snmp_ignore_authfailure'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^snmp_pdu_address\s+([0-1])/i) { 
			$pa_config->{'snmp_pdu_address'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^snmp_storm_protection\s+(\d+)/i) { 
			$pa_config->{'snmp_storm_protection'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^snmp_storm_timeout\s+(\d+)/i) { 
			$pa_config->{'snmp_storm_timeout'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^snmp_storm_silence_period\s+(\d+)/i) { 
			$pa_config->{'snmp_storm_silence_period'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^snmp_delay\s+(\d+)/i) { 
			$pa_config->{'snmp_delay'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^snmpconsole_threads\s+(\d+)/i) { 
			$pa_config->{'snmpconsole_threads'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^snmpconsole_lock\s+([0-1])/i) { 
			$pa_config->{'snmpconsole_lock'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^snmpconsole_threshold\s+(\d+(?:\.\d+){0,1})/i) { 
			$pa_config->{'snmpconsole_threshold'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^translate_variable_bindings\s+([0-1])/i) { 
			$pa_config->{'translate_variable_bindings'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^translate_enterprise_strings\s+([0-1])/i) { 
			$pa_config->{'translate_enterprise_strings'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^user\s(.*)/i) { 
			$pa_config->{'user'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^group\s(.*)/i) { 
			$pa_config->{'group'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^umask\s(.*)/i) { 
			$pa_config->{'umask'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^dbengine\s(.*)/i) { 
			$pa_config->{'dbengine'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^dbname\s(.*)/i) { 
			$pa_config->{'dbname'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^dbssl\s+([0-1])/i) { 
			$pa_config->{'dbssl'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^dbsslcapath\s(.*)/i) { 
			$pa_config->{'dbsslcapath'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^dbsslcafile\s(.*)/i) { 
			$pa_config->{'dbsslcafile'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^verify_mysql_ssl_cert\s(.*)/i) { 
			$pa_config->{'verify_mysql_ssl_cert'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^dbuser\s(.*)/i) { 
			$pa_config->{'dbuser'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^dbpass\s(.*)/i) {
			$pa_config->{'dbpass'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^dbhost\s(.*)/i) { 
			$pa_config->{'dbhost'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^dbport\s(.*)/i) { 
			$pa_config->{'dbport'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^daemon\s+([0-9]*)/i) { 
			$pa_config->{'daemon'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^dataserver\s+([0-9]*)/i){
			$pa_config->{'dataserver'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^networkserver\s+([0-9]*)/i){
			$pa_config->{'networkserver'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^pluginserver\s+([0-9]*)/i){
			$pa_config->{'pluginserver'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^predictionserver\s+([0-9]*)/i){
			$pa_config->{'predictionserver'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^discoveryserver\s+([0-9]*)/i) {
			$pa_config->{'discoveryserver'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^reconserver\s+([0-9]*)/i) {
			$pa_config->{'reconserver'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^wmiserver\s+([0-9]*)/i) {
			$pa_config->{'wmiserver'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^exportserver\s+([0-9]*)/i) {
			$pa_config->{'exportserver'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^inventoryserver\s+([0-9]*)/i) {
			$pa_config->{'inventoryserver'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^webserver\s+([0-9]*)/i) {
			$pa_config->{'webserver'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^web_timeout\s+([0-9]*)/i) {
			$pa_config->{'web_timeout'}= clean_blank($1); 
		}
		if ($parametro =~ m/^transactional_pool\s(.*)/i) {
			$tbuf= clean_blank($1); 
			if ($tbuf =~ m/^\.(.*)/){
				$pa_config->{"transactional_pool"} = $pa_config->{"incomingdir"} . "/" . $1;
			} else {
				$pa_config->{"transactional_pool"} = $pa_config->{"incomingdir"} . "/" . $tbuf;
			}
		}
		elsif ($parametro =~ m/^eventserver\s+([0-1])/i) {
			$pa_config->{'eventserver'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^eventserver_threads\s+([0-9]*)/i) {
			$pa_config->{'eventserver_threads'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^logserver\s+([0-1])/i) {
			$pa_config->{'logserver'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^logserver_threads\s+([0-9]*)/i) {
			$pa_config->{'logserver_threads'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^icmpserver\s+([0-9]*)/i) {
			$pa_config->{'icmpserver'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^icmp_threads\s+([0-9]*)/i) {
			$pa_config->{'icmp_threads'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^servername\s(.*)/i) { 
			$pa_config->{'servername'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^checksum\s+([0-9])/i) { 
			$pa_config->{"pandora_check"} = clean_blank($1); 
		}
		elsif ($parametro =~ m/^master\s+([0-9])/i) { 
			$pa_config->{"pandora_master"} = clean_blank($1); 
		}
		elsif ($parametro =~ m/^icmp_checks\s+([0-9]*)/i) { 
			$pa_config->{"icmp_checks"} = clean_blank($1); 
		}
		elsif ($parametro =~ m/^icmp_packets\s+([0-9]*)/i) {
			$pa_config->{"icmp_packets"} = clean_blank($1); 
		}
		elsif ($parametro =~ m/^critical_on_error\s+([0-1])/i) {
			$pa_config->{"critical_on_error"} = clean_blank($1); 
		}
		elsif ($parametro =~ m/^snmpconsole\s+([0-9]*)/i) {
			$pa_config->{"snmpconsole"} = clean_blank($1);
		}
		elsif ($parametro =~ m/^snmpserver\s+([0-9]*)/i) {
			$pa_config->{"snmpserver"} = clean_blank($1);
		}
		elsif ($parametro =~ m/^alert_recovery\s+([0-9]*)/i) {
			$pa_config->{"alert_recovery"} = clean_blank($1);
		}
		elsif ($parametro =~ m/^snmp_checks\s+([0-9]*)/i) {
			$pa_config->{"snmp_checks"} = clean_blank($1);
		}
		elsif ($parametro =~ m/^snmp_timeout\s+([0-9]*)/i) {
			$pa_config->{"snmp_timeout"} = clean_blank($1);
		}
		elsif ($parametro =~ m/^rcmd_timeout\s+([0-9]*)/i) {
			$pa_config->{"rcmd_timeout"} = clean_blank($1);
		}
		elsif ($parametro =~ m/^rcmd_timeout_bin\s(.*)/i) {
			$pa_config->{"rcmd_timeout_bin"} = clean_blank($1);
		}
		elsif ($parametro =~ m/^tcp_checks\s+([0-9]*)/i) {
			$pa_config->{"tcp_checks"} = clean_blank($1);
		}
		elsif ($parametro =~ m/^tcp_timeout\s+([0-9]*)/i) {
			$pa_config->{"tcp_timeout"} = clean_blank($1);
		}
		elsif ($parametro =~ m/^snmp_proc_deadresponse\s+([0-9]*)/i) { 
			$pa_config->{"snmp_proc_deadresponse"} = clean_blank($1);
		}
		elsif ($parametro =~ m/^verbosity\s+([0-9]*)/i) {
			if ($pa_config->{"verbosity"} == 0) {
				$pa_config->{"verbosity"} = clean_blank($1);
			}
		} 
		elsif ($parametro =~ m/^server_threshold\s+([0-9]*)/i) { 
			$pa_config->{"server_threshold"} = clean_blank($1); 
		} 
		elsif ($parametro =~ m/^alert_threshold\s+([0-9]*)/i) { 
			$pa_config->{"alert_threshold"} = clean_blank($1); 
		} 
		elsif ($parametro =~ m/^graph_precision\s+([0-9]*)/i) { 
			$pa_config->{"graph_precision"} = clean_blank($1); 
		} 
		elsif ($parametro =~ m/^network_timeout\s+([0-9]*)/i) {
			$pa_config->{'networktimeout'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^network_threads\s+([0-9]*)/i) {
			$pa_config->{'network_threads'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^plugin_threads\s+([0-9]*)/i) {
			$pa_config->{'plugin_threads'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^prediction_threads\s+([0-9]*)/i) {
			$pa_config->{'prediction_threads'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^plugin_timeout\s+([0-9]*)/i) {
			$pa_config->{'plugin_timeout'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^dataserver_threads\s+([0-9]*)/i) {
			$pa_config->{'dataserver_threads'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^server_keepalive\s+([0-9]*)/i) {
			$pa_config->{"keepalive"} = clean_blank($1);
			$pa_config->{"keepalive_orig"} = clean_blank($1);
		}
		elsif ($parametro =~ m/^nmap\s(.*)/i) {
			$pa_config->{'nmap'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^fping\s(.*)/i) {
			$pa_config->{'fping'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^java\s(.*)/i) {
			$pa_config->{'java'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^sap_utils\s(.*)/i) {
			$pa_config->{'sap_utils'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^sap_artica_test\s(.*)/i) {
			$pa_config->{'sap_artica_test'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^ssh_launcher\s(.*)/i) {
			$pa_config->{'ssh_launcher'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^nmap_timing_template\s+([0-9]*)/i) {
			$pa_config->{'nmap_timing_template'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^recon_timing_template\s+([0-9]*)/i) {
			$pa_config->{'recon_timing_template'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^braa\s(.*)/i) {
			$pa_config->{'braa'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^braa_retries\s+([0-9]*)/i) {
			$pa_config->{"braa_retries"} = clean_blank($1);
		}
		elsif ($parametro =~ m/^winexe\s(.*)/i) {
			$pa_config->{'winexe'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^psexec\s(.*)/i) {
			$pa_config->{'psexec'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^plink\s(.*)/i) {
			$pa_config->{'plink'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^snmpget\s(.*)/i) {
			$pa_config->{'snmpget'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^autocreate\s+([0-9*]*)/i) {
			$pa_config->{'autocreate'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^autocreate_group\s+([0-9*]*)/i) {
			$pa_config->{'autocreate_group'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^autocreate_group_force\s+([0-1])/i) {
			$pa_config->{'autocreate_group_force'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^autocreate_group_name\s(.*)/i) {
			$pa_config->{'autocreate_group_name'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^discovery_threads\s+([0-9]*)/i) {
			$pa_config->{'discovery_threads'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^recon_threads\s+([0-9]*)/i) {
			$pa_config->{'recon_threads'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^max_log_size\s+([0-9]*)/i) {
			$pa_config->{'max_log_size'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^max_log_generation\s+([1-9])/i) {
			$pa_config->{'max_log_generation'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^wmi_threads\s+([0-9]*)/i) {
			$pa_config->{'wmi_threads'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^wmi_client\s(.*)/i) {
			$pa_config->{'wmi_client'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^web_threads\s+([0-9]*)/i) {
			$pa_config->{'web_threads'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^web_engine\s(.*)/i) {
			$pa_config->{'web_engine'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^snmp_trapd\s(.*)/i) {
			$pa_config->{'snmp_trapd'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^plugin_exec\s(.*)/i) {
			$pa_config->{'plugin_exec'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^inventory_threads\s+([0-9]*)/i) {
			$pa_config->{'inventory_threads'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^export_threads\s+([0-9]*)/i) {
			$pa_config->{'export_threads'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^max_queue_files\s+([0-9]*)/i) {
			$pa_config->{'max_queue_files'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^use_xml_timestamp\s+([0-1])/i) {
			$pa_config->{'use_xml_timestamp'} = clean_blank($1);
		}
		elsif ($parametro =~ m/^restart_delay\s+(\d+)/i) {
			$pa_config->{'restart_delay'} = clean_blank($1);
		}
		elsif ($parametro =~ m/^auto_restart\s+(\d+)/i) {
			$pa_config->{'auto_restart'} = clean_blank($1);
		}
		elsif ($parametro =~ m/^restart\s+([0-1])/i) {
			$pa_config->{'restart'} = clean_blank($1);
		}
		elsif ($parametro =~ m/^google_maps_description\s+([0-1])/i) {
			$pa_config->{'google_maps_description'} = clean_blank($1);
		}
		elsif ($parametro =~ m/^openstreetmaps_description\s+([0-1])/i) {
			$pa_config->{'openstreetmaps_description'} = clean_blank($1);
		}
		elsif ($parametro =~ m/^activate_gis\s+([0-1])/i) {
			$pa_config->{'activate_gis'} = clean_blank($1);
		}
		elsif ($parametro =~ m/^location_error\s+(\d+)/i) {
			$pa_config->{'location_error'} = clean_blank($1);
		} #FIXME: Find a better regexp to validate the path
		elsif ($parametro =~ m/^recon_reverse_geolocation_file\s+(.*)/i) {
			$pa_config->{'recon_reverse_geolocation_file'} = clean_blank($1);
			if ( ! -r $pa_config->{'recon_reverse_geolocation_file'} ) {
				print "[WARN] Invalid recon_reverse_geolocation_file.\n";
				$pa_config->{'recon_reverse_geolocation_file'} = '';
			}
		}
		elsif ($parametro =~ m/^recon_location_scatter_radius\s+(\d+)/i) {
			$pa_config->{'recon_location_scatter_radius'} = clean_blank($1);
		}
		elsif ($parametro =~ m/^self_monitoring\s+([0-1])/i) {
			$pa_config->{'self_monitoring'} = clean_blank($1);
		}
		elsif ($parametro =~ m/^self_monitoring_interval\s+([0-9]*)/i) {
			$pa_config->{'self_monitoring_interval'} = clean_blank($1);
		}
		elsif ($parametro =~ m/^self_monitoring_agent_name\s+(.*)/i) {
			$pa_config->{'self_monitoring_agent_name'} = clean_blank($1);
		}
		elsif ($parametro =~ m/^update_parent\s+([0-1])/i) {
			$pa_config->{'update_parent'} = clean_blank($1);
		}
		elsif ($parametro =~ m/^event_window\s+([0-9]*)/i) {
			$pa_config->{'event_window'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^log_window\s+([0-9]*)/i) {
			$pa_config->{'log_window'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^elastic_query_size\s+([0-9]*)/i) {
			$pa_config->{'elastic_query_size'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^preload_windows\s+([0-9]*)/i) {
			$pa_config->{'preload_windows'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^event_server_cache_ttl\s+([0-9]*)/i) {
			$pa_config->{"event_server_cache_ttl"} = clean_blank($1);
		}
		elsif ($parametro =~ m/^snmp_threads\s+([0-9]*)/i) {
			$pa_config->{'snmp_threads'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^block_size\s+([0-9]*)/i) {
			$pa_config->{'block_size'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^dataserver_lifo\s+([0-1])/i) {
			$pa_config->{'dataserver_lifo'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^policy_manager\s+([0-1])/i) {
			$pa_config->{'policy_manager'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^event_auto_validation\s+([0-1])/i) {
			$pa_config->{'event_auto_validation'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^event_file\s+(.*)/i) {
			$pa_config->{'event_file'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^event_inhibit_alerts\s+([0-1])/i) {
			$pa_config->{'event_inhibit_alerts'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^text_going_down_normal\s+(.*)/i) {
			$pa_config->{'text_going_down_normal'} = safe_input ($1);
		}
		elsif ($parametro =~ m/^text_going_up_critical\s+(.*)/i) {
			$pa_config->{'text_going_up_critical'} = safe_input ($1);
		}
		elsif ($parametro =~ m/^text_going_up_warning\s+(.*)/i) {
			$pa_config->{'text_going_up_warning'} = safe_input ($1);
		}
		elsif ($parametro =~ m/^text_going_down_warning\s+(.*)/i) {
			$pa_config->{'text_going_down_warning'} = safe_input ($1);
		}
		elsif ($parametro =~ m/^text_going_unknown\s+(.*)/i) {
			$pa_config->{'text_going_unknown'} = safe_input ($1);
		}
		elsif ($parametro =~ m/^event_expiry_time\s+([0-9]*)/i) {
			$pa_config->{'event_expiry_time'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^event_expiry_window\s+([0-9]*)/i) {
			$pa_config->{'event_expiry_window'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^snmp_forward_trap\s+([0-1])/i) {
			$pa_config->{'snmp_forward_trap'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^snmp_forward_secName\s(.*)/i) {
			$pa_config->{'snmp_forward_secName'}= safe_input(clean_blank($1));
		}
		elsif ($parametro =~ m/^snmp_forward_engineid\s(.*)/i) {
                        $pa_config->{'snmp_forward_engineid'}= safe_input(clean_blank($1));
                }	
		elsif ($parametro =~ m/^snmp_forward_authProtocol\s(.*)/i) {
                        $pa_config->{'snmp_forward_authProtocol'}= safe_input(clean_blank($1));
                }
		elsif ($parametro =~ m/^snmp_forward_authPassword\s(.*)/i) {
                        $pa_config->{'snmp_forward_authPassword'}= safe_input(clean_blank($1));
                }
		elsif ($parametro =~ m/^snmp_forward_community\s(.*)/i) {
                        $pa_config->{'snmp_forward_community'}= safe_input(clean_blank($1));
                }
		elsif ($parametro =~ m/^snmp_forward_privProtocol\s(.*)/i) {
                        $pa_config->{'snmp_forward_privProtocol'}= safe_input(clean_blank($1));
                }
		elsif ($parametro =~ m/^snmp_forward_privPassword\s(.*)/i) {
                        $pa_config->{'snmp_forward_privPassword'}= safe_input(clean_blank($1));
                }
		elsif ($parametro =~ m/^snmp_forward_secLevel\s(.*)/i) {
                        $pa_config->{'snmp_forward_secLevel'}= safe_input(clean_blank($1));
                }
		elsif ($parametro =~ m/^snmp_forward_version\s(.*)/i) {
                        $pa_config->{'snmp_forward_version'}= safe_input(clean_blank($1));
                }
		elsif ($parametro =~ m/^snmp_forward_ip\s(.*)/i) {
			$pa_config->{'snmp_forward_ip'}= safe_input(clean_blank($1));
			if ($pa_config->{'snmp_forward_trap'}==1 && ($pa_config->{'snmp_forward_ip'} eq '127.0.0.1' || $pa_config->{'snmp_forward_ip'} eq 'localhost')) {
				printf "\n [ERROR] Cannot set snmp_forward_ip to localhost or 127.0.0.1 \n";
                		exit 1;

			}
		}
		elsif ($parametro =~ m/^claim_back_snmp_modules\s(.*)/i) {
			$pa_config->{'claim_back_snmp_modules'}= safe_input(clean_blank($1));
		}
		elsif ($parametro =~ m/^async_recovery\s+([0-1])/i) {
			$pa_config->{'async_recovery'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^console_api_url\s(.*)/i) {
			$pa_config->{'console_api_url'}= safe_input(clean_blank($1));
		}
		elsif ($parametro =~ m/^console_api_pass\s(.*)/i) {
			$pa_config->{'console_api_pass'}= safe_input(clean_blank($1));
		}
		elsif ($parametro =~ m/^console_user\s(.*)/i) {
			$pa_config->{'console_user'}= safe_input(clean_blank($1));
		}
		elsif ($parametro =~ m/^console_pass\s(.*)/i) {
			$pa_config->{'console_pass'}= safe_input(clean_blank($1));
		}
		elsif ($parametro =~ m/^encryption_passphrase\s(.*)/i) { # 6.0
			$pa_config->{'encryption_passphrase'} = clean_blank($1);
		}
		elsif ($parametro =~ m/^unknown_interval\s+([0-9]*)/i) { # > 5.1SP2
			$pa_config->{'unknown_interval'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^global_alert_timeout\s+([0-9]*)/i) {
			$pa_config->{'global_alert_timeout'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^remote_config\s+([0-9]*)/i) {
			$pa_config->{'remote_config'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^remote_config_address\s(.*)/i) {
			$pa_config->{'remote_config_address'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^remote_config_port\s+([0-9]*)/i) {
			$pa_config->{'remote_config_port'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^remote_config_opts\s(.*)/i) { 
			$pa_config->{'remote_config_opts'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^temporal\s(.*)/i) {
			$pa_config->{'temporal'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^warmup_event_interval\s+([0-9]*)/i ||
		       $parametro =~ m/^warmup_alert_interval\s+([0-9]*)/i) {
			$pa_config->{'warmup_event_interval'}= clean_blank($1);
			$pa_config->{'warmup_event_on'} = 1 if ($pa_config->{'warmup_event_interval'} > 0); # Off by default.
			# The same interval is used for alerts and events.
			$pa_config->{'warmup_alert_interval'}= clean_blank($1);
			$pa_config->{'warmup_alert_on'} = 1 if ($pa_config->{'warmup_event_interval'} > 0); # Off by default.
		}
		elsif ($parametro =~ m/^warmup_unknown_interval\s+([0-9]*)/i) {
			$pa_config->{'warmup_unknown_interval'}= clean_blank($1);
			$pa_config->{'warmup_unknown_on'} = 0 if ($pa_config->{'warmup_unknown_interval'} == 0); # On by default.
		}
		elsif ($parametro =~ m/^enc_dir\s+(.*)/i) {
			$pa_config->{'enc_dir'} = clean_blank($1);
		}
		elsif ($parametro =~ m/^unknown_events\s+([0-1])/i) {
			$pa_config->{'unknown_events'} = clean_blank($1);
		}
		elsif ($parametro =~ m/^syncserver\s+([0-9]*)/i) {
			$pa_config->{'syncserver'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^sync_address\s+(.*)/i) {
			$pa_config->{'sync_address'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^sync_block_size\s+([0-9]*)/i) {
			$pa_config->{'sync_block_size'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^sync_ca\s+(.*)/i) {
			$pa_config->{'sync_ca'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^sync_cert\s+(.*)/i) {
			$pa_config->{'sync_cert'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^sync_key\s+(.*)/i) {
			$pa_config->{'sync_key'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^sync_port\s+([0-9]*)/i) {
			$pa_config->{'sync_port'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^sync_timeout\s+([0-9]*)/i) {
			$pa_config->{'sync_timeout'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^sync_retries\s+([0-9]*)/i) {
			$pa_config->{'sync_retries'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^dynamic_updates\s+([0-9]*)/i) {
			$pa_config->{'dynamic_updates'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^dynamic_warning\s+([0-9]*)/i) {
			$pa_config->{'dynamic_warning'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^dynamic_constant\s+([0-9]*)/i) {
			$pa_config->{'dynamic_constant'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^mssql_driver\s+(.*)/i) {
			$pa_config->{'mssql_driver'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^wuxserver\s+([0-1]*)/i) {
			$pa_config->{"wuxserver"} = clean_blank($1);
		}
		elsif ($parametro =~ m/^wux_host\s+(.*)/i) {
			$pa_config->{'wux_host'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^wux_port\s+([0-9]*)/i) {
			$pa_config->{'wux_port'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^wux_browser\s+(.*)/i) {
			$pa_config->{'wux_browser'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^wux_webagent_timeout\s+([0-9]*)/i) {
			$pa_config->{'wux_webagent_timeout'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^clean_wux_sessions\s+([0-9]*)/i) {
			$pa_config->{'clean_wux_sessions'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^syslogserver\s+([0-1])/i) {
			$pa_config->{'syslogserver'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^syslog_file\s+(.*)/i) {
			$pa_config->{'syslog_file'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^syslog_max\s+([0-9]*)/i) {
			$pa_config->{'syslog_max'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^syslog_threads\s+([0-9]*)/i) {
			$pa_config->{'syslog_threads'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^syslog_blacklist\s+(.*)/i) {
			$pa_config->{'syslog_blacklist'}= clean_blank($1);
		}		
		elsif ($parametro =~ m/^syslog_whitelist\s+(.*)/i) {
			$pa_config->{'syslog_whitelist'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^thread_log\s+([0-1])/i) {
			$pa_config->{'thread_log'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^unknown_updates\s+([0-1])/i) {
			$pa_config->{'unknown_updates'} = clean_blank($1);
		}
		elsif ($parametro =~ m/^provisioningserver\s+([0-1])/i){
			$pa_config->{'provisioningserver'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^provisioningserver_threads\s+([0-9]*)/i){
			$pa_config->{'provisioningserver_threads'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^provisioning_cache_interval\s+([0-9]*)/i){
			$pa_config->{'provisioning_cache_interval'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^autoconfigure_agents\s+([0-1])/i){
			$pa_config->{'autoconfigure_agents'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^autoconfigure_agents_threshold\s+([0-1])/i){
			$pa_config->{'autoconfigure_agents_threshold'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^snmp_extlog\s(.*)/i) { 
			$pa_config->{'snmp_extlog'} = clean_blank($1); 
		}
		elsif ($parametro =~ m/^fsnmp\s(.*)/i) {
			$pa_config->{'fsnmp'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^alertserver\s+([0-9]*)/i){
			$pa_config->{'alertserver'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^alertserver_threads\s+([0-9]*)/i) {
			$pa_config->{'alertserver_threads'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^alertserver_warn\s+([0-9]*)/i) {
			$pa_config->{'alertserver_warn'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^alertserver_queue\s+([0-1]*)/i) {
			$pa_config->{'alertserver_queue'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^ncmserver\s+([0-9]*)/i){
			$pa_config->{'ncmserver'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^ncmserver_threads\s+([0-9]*)/i) {
			$pa_config->{'ncmserver_threads'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^ncm_ssh_utility\s+(.*)/i) {
			$pa_config->{'ncm_ssh_utility'}= clean_blank($1);
		}

		# Pandora HA extra
		elsif ($parametro =~ m/^ha_mode\s(.*)/i) {
			$pa_config->{'ha_mode'} = clean_blank($1);
		}
		elsif ($parametro =~ m/^ha_file\s(.*)/i) {
			$pa_config->{'ha_file'} = clean_blank($1);
		}
		elsif ($parametro =~ m/^ha_hosts_file\s(.*)/i) {
			$pa_config->{'ha_hosts_file'} = clean_blank($1);
		}
		elsif ($parametro =~ m/^ha_dbuser\s(.*)/i) {
			$pa_config->{'ha_dbuser'} = clean_blank($1);
		}
		elsif ($parametro =~ m/^ha_dbpass\s(.*)/i) {
			$pa_config->{'ha_dbpass'} = clean_blank($1);
		}
		elsif ($parametro =~ m/^ha_sshuser\s(.*)/i) {
			$pa_config->{'ha_sshuser'} = clean_blank($1);
		}
		elsif ($parametro =~ m/^ha_sshport\s(.*)/i) {
			$pa_config->{'ha_sshport'} = clean_blank($1);
		}
		elsif ($parametro =~ m/^ha_hosts\s(.*)/i) {
			$pa_config->{'ha_hosts'} = clean_blank($1);
		}
		elsif ($parametro =~ m/^ha_resync\s(.*)/i) {
			$pa_config->{'ha_resync'} = clean_blank($1);
		}
		elsif ($parametro =~ m/^ha_resync_log\s(.*)/i) {
			$pa_config->{'ha_resync_log'} = clean_blank($1);
		}
		elsif ($parametro =~ m/^ha_pid_file\s(.*)/i) {
			$pa_config->{'ha_pid_file'} = clean_blank($1);
		}
		elsif ($parametro =~ m/^pandora_service_cmd\s(.*)/i) {
			$pa_config->{'pandora_service_cmd'} = clean_blank($1);
		}
		elsif ($parametro =~ m/^tentacle_service_cmd\s(.*)/i) {
			$pa_config->{'tentacle_service_cmd'} = clean_blank($1);
		}
		elsif ($parametro =~ m/^tentacle_service_watchdog\s([0-1])/i) {
			$pa_config->{'tentacle_service_watchdog'} = clean_blank($1);
		}
		elsif ($parametro =~ m/^splitbrain_autofix\s+([0-9]*)/i) {
			$pa_config->{'splitbrain_autofix'} = clean_blank($1);
		}
		elsif ($parametro =~ m/^ha_max_resync_wait_retries\s+([0-9]*)/i) {
			$pa_config->{'ha_max_resync_wait_retries'} = clean_blank($1);
		}
		elsif ($parametro =~ m/^ha_resync_sleep\s+([0-9]*)/i) {
			$pa_config->{'ha_resync_sleep'} = clean_blank($1);
		}
		elsif ($parametro =~ m/^ha_max_splitbrain_retries\s+([0-9]*)/i) {
			$pa_config->{'ha_max_splitbrain_retries'} = clean_blank($1);
		}
		elsif ($parametro =~ m/^dataserver_smart_queue\s([0-1])/i) {
			$pa_config->{'dataserver_smart_queue'} = clean_blank($1);
		}
		elsif ($parametro =~ m/^netflowserver\s([0-1])/i) {
			$pa_config->{'netflowserver'} = clean_blank($1);
		}
		elsif ($parametro =~ m/^netflowserver_threads\s+([0-9]*)/i) {
			$pa_config->{'netflowserver_threads'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^ha_connect_retries\s+([0-9]*)/i) {
			$pa_config->{'ha_connect_retries'} = clean_blank($1);
		}
		elsif ($parametro =~ m/^ha_connect_delay\s+([0-9]*)/i) {
			$pa_config->{'ha_connect_delay'} = clean_blank($1);
		}
		elsif ($parametro =~ m/^repl_dbuser\s(.*)/i) {
			$pa_config->{'repl_dbuser'} = clean_blank($1);
		}
		elsif ($parametro =~ m/^repl_dbpass\s(.*)/i) {
			$pa_config->{'repl_dbpass'} = clean_blank($1);
		}
		elsif ($parametro =~ m/^ssl_verify\s+([0-1])/i) {
			$pa_config->{'ssl_verify'} = clean_blank($1);
		}
		elsif ($parametro =~ m/^madeserver\s+([0-1])/i){
			$pa_config->{'madeserver'}= clean_blank($1);
		}
	} # end of loop for parameter #

	# The DB host was overridden by pandora_ha.
	if (-f $pa_config->{'ha_hosts_file'}) {
		eval {
			open(my $fh, '<', $pa_config->{'ha_hosts_file'}) or return;
			my $dbhost = <$fh>;
			chomp($dbhost);
			if (defined($dbhost) && $dbhost ne '') {
				$pa_config->{'dbhost'} = $dbhost;
			}
			close($fh);
		};
	}
	print " [*] DB Host is " . $pa_config->{'dbhost'} . "\n";

	# ha_dbuser and ha_dbpass default to dbuser and dbpass respectively.
	$pa_config->{'ha_dbuser'} = $pa_config->{'dbuser'} unless defined($pa_config->{'ha_dbuser'});
	$pa_config->{'ha_dbpass'} = $pa_config->{'dbpass'} unless defined($pa_config->{'ha_dbpass'});

	# repl_dbuser and repl_dbpass default to dbuser and dbpass respectively.
	$pa_config->{'repl_dbuser'} = $pa_config->{'dbuser'} unless defined($pa_config->{'repl_dbuser'});
	$pa_config->{'repl_dbpass'} = $pa_config->{'dbpass'} unless defined($pa_config->{'repl_dbpass'});

	# Generate the encryption key after reading the passphrase.
	$pa_config->{"encryption_key"} = enterprise_hook('pandora_get_encryption_key', [$pa_config, $pa_config->{"encryption_passphrase"}]);

	# Set to RDBMS' standard port
	if (!defined($pa_config->{'dbport'})) {
		if ($pa_config->{'dbengine'} eq "mysql") {
			$pa_config->{'dbport'} = 3306;
		}
		elsif ($pa_config->{'dbengine'} eq "postgresql") {
			$pa_config->{'dbport'} = 5432;
		}
		elsif ($pa_config->{'dbengine'} eq "oracle") {
			$pa_config->{'dbport'} = 1521;
		}
	}

	# Configure SSL.
	set_ssl_opts($pa_config);

	if (($pa_config->{"verbosity"} > 4) && ($pa_config->{"quiet"} == 0)){
		if ($pa_config->{"PID"} ne ""){
			print " [*] PID File is written at ".$pa_config->{'PID'}."\n";
		}
		print " [*] Server basepath is ".$pa_config->{'basepath'}."\n";
		print " [*] Server logfile at ".$pa_config->{"log_file"}."\n";
		print " [*] Server errorlogfile at ".$pa_config->{"errorlog_file"}."\n";
		print " [*] Server incoming directory at ".$pa_config->{"incomingdir"}."\n";
		print " [*] Server keepalive ".$pa_config->{"keepalive"}."\n";
		print " [*] Server threshold ".$pa_config->{"server_threshold"}."\n";
	}
 	# Check for valid token token values
 	if (( $pa_config->{"dbuser"} eq "" ) || ( $pa_config->{"basepath"} eq "" ) || ( $pa_config->{"incomingdir"} eq "" ) || ( $pa_config->{"log_file"} eq "" ) || ( $pa_config->{"dbhost"} eq "") || ( $pa_config->{"pandora_master"} eq "") || ( $pa_config->{"dbpass"} eq "" ) ) {
		print " [ERROR] Bad Config values. Be sure that $archivo_cfg is a valid setup file. \n\n";
		exit;
	}

	if (($pa_config->{"quiet"} == 0) && ($pa_config->{"verbosity"} > 4)) {
		if ($pa_config->{"pandora_check"} == 1) {
			print " [*] MD5 Security enabled.\n";
		}
		if ($pa_config->{"pandora_master"} != 0) {
			print " [*] This server is running with MASTER priority " . $pa_config->{"pandora_master"} . "\n";
		}
	}

	logger ($pa_config, "Launching $pa_config->{'version'} $pa_config->{'build'}", 1);
	my $config_options = "Logfile at ".$pa_config->{"log_file"}.", Basepath is ".$pa_config->{"basepath"}.", Checksum is ".$pa_config->{"pandora_check"}.", Master is ".$pa_config->{"pandora_master"}.", SNMP Console is ".$pa_config->{"snmpconsole"}.", Server Threshold at ".$pa_config->{"server_threshold"}." sec, verbosity at ".$pa_config->{"verbosity"}.", Alert Threshold at $pa_config->{'alert_threshold'}, ServerName is '".$pa_config->{'servername'}."'";
	logger ($pa_config, "Config options: $config_options", 1);
}


##########################################################################
# Open the log file and start logging.
##########################################################################
sub pandora_start_log ($){
	my $pa_config = shift;

	# Dump all errors to errorlog
	open (STDERR, ">> " . $pa_config->{'errorlog_file'}) or die " [ERROR] " . pandora_get_initial_product_name() . " can't write to Errorlog. Aborting : \n $! \n";
	
	my $file_mode = (stat($pa_config->{'errorlog_file'}))[2] & 0777;
	my $min_mode = 0664;
	my $mode = $file_mode | $min_mode;

	chmod $mode, $pa_config->{'errorlog_file'};
	
	print STDERR strftime ("%Y-%m-%d %H:%M:%S", localtime()) . ' - ' . $pa_config->{'servername'} . " Starting " . pandora_get_initial_product_name() . " Server. Error logging activated.\n";
}

##########################################################################
# Read the given token from the tconfig table.
##########################################################################
sub pandora_get_tconfig_token ($$$) {
	my ($dbh, $token, $default_value) = @_;
	
	my $token_value = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = ?", $token);
	if (defined ($token_value) && $token_value ne '') {
		return safe_output ($token_value);
	}
	
	return $default_value;
}

##########################################################################
# Write the given token to tconfig table.
##########################################################################
sub pandora_set_tconfig_token ($$$) {
	my ($dbh, $token, $value) = @_;
	
	my $token_value = get_db_value ($dbh,
		"SELECT `value` FROM `tconfig` WHERE `token` = ?", $token
	);
	if (defined ($token_value) && $token_value ne '') {
		db_update($dbh,
			'UPDATE `tconfig` SET `value`=? WHERE `token`= ?',
			safe_input($value),
			$token
		);
	} else {
		db_insert($dbh, 'id_config',
			'INSERT INTO `tconfig`(`token`, `value`) VALUES (?, ?)',
			$token,
			safe_input($value)
		);
	}
	
}

##########################################################################
# Get the product name in previous tasks to read from database.
##########################################################################
sub pandora_get_initial_product_name {
	# PandoraFMS product name
	my $product_name = $ENV{'PANDORA_RB_PRODUCT_NAME'};
	return 'Pandora FMS' unless (defined ($product_name) && $product_name ne '');
	return $product_name;
}

##########################################################################
# Get the copyright notice.
##########################################################################
sub pandora_get_initial_copyright_notice {
	# PandoraFMS product name
	my $name = $ENV{'PANDORA_RB_COPYRIGHT_NOTICE'};
	return 'Pandora FMS' unless (defined ($name) && $name ne '');
	return $name;
}

# End of function declaration
# End of defined Code

1;
__END__
