package PandoraFMS::Config;
##########################################################################
# Configuration Package
# Pandora FMS. the Flexible Monitoring System. http://www.pandorafms.org
##########################################################################
# Copyright (c) 2005-2011 Artica Soluciones Tecnologicas S.L
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
use lib '/usr/lib/perl5';

use PandoraFMS::Tools;
use PandoraFMS::DB;
use PandoraFMS::Core;
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
	);

# version: Defines actual version of Pandora Server for this module only
my $pandora_version = "6.0SP5";
my $pandora_build = "170402";
our $VERSION = $pandora_version." ".$pandora_build;

# Setup hash
my %pa_config;

# Public functions
##########################################################################
# SUB pandora_help_screen()
# Shows a help screen and exits
##########################################################################

sub help_screen {
	print "\nSyntax: \n\n pandora_server [ options ] < fullpathname to configuration file (pandora_server.conf) > \n\n";
	print "Following options are optional : \n";
	print "	-v        :  Verbose mode activated. Writes more information in the logfile \n";
	print "	-d        :  Debug mode activated. Writes extensive information in the logfile \n";
	print "	-D        :  Daemon mode (runs in background)\n";
	print "	-P <file> :  Store PID to file.\n";
	print "	-q        :  Quiet startup \n";
	print "	-S <install|uninstall|run>:  Manage the win32 service.\n";
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
	print "\n$init_string $pandora_version Build $pandora_build Copyright (c) 2004-2015 ArticaST\n";
	print "This program is OpenSource, licensed under the terms of GPL License version 2.\n";
	print "You can download latest versions and documentation at http://www.pandorafms.org \n\n";

	# Load config file from command line
	if ($#ARGV == -1 ){
		print "I need at least one parameter: Complete path to Pandora FMS Server configuration file \n";
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
		elsif ($parametro =~ m/-v\z/i) {
			$pa_config->{"verbosity"}=5;
		}
		elsif ($parametro =~ m/^-P\z/i) {
			$pa_config->{'PID'}= clean_blank($ARGV[$ax+1]);
		}
		elsif ($parametro =~ m/-d\z/) {
			$pa_config->{"verbosity"}=10;
		}
		elsif ($parametro =~ m/-q\z/) {
			$pa_config->{"quiet"}=1;
		}
		elsif ($parametro =~ m/-D\z/) {
			$pa_config->{"daemon"}=1;
		}
		elsif ($parametro =~ m/^-S\z/i) {
			$pa_config->{'win32_service'}= clean_blank($ARGV[$ax+1]);
		}
		else {
			($pa_config->{"pandora_path"} = $parametro);
		}
	}
	if ($pa_config->{"pandora_path"} eq ""){
		print " [ERROR] I need at least one parameter: Complete path to Pandora FMS configuration file. \n";
		print "		For example: ./pandora_server /etc/pandora/pandora_server.conf \n\n";
		exit;
	}
}

##########################################################################
# Read some config tokens from database set by the console
##########################################################################
sub pandora_get_sharedconfig ($$) {
	my ($pa_config, $dbh) = @_;

	# Agentaccess option
	$pa_config->{"agentaccess"} = pandora_get_tconfig_token ($dbh, 'agentaccess', 1);

	# Realtimestats 0 disabled, 1 enabled.
	# Master servers will generate all the information (global tactical stats).
	# and each server will generate it's own server stats (lag, etc).
	$pa_config->{"realtimestats"} = pandora_get_tconfig_token ($dbh, 'realtimestats', 0);

	# Stats_interval option
	$pa_config->{"stats_interval"} = pandora_get_tconfig_token ($dbh, 'stats_interval', 300);

	# Netflow configuration options
	$pa_config->{"activate_netflow"} = pandora_get_tconfig_token ($dbh, 'activate_netflow', 0);
	$pa_config->{"netflow_path"} = pandora_get_tconfig_token ($dbh, 'netflow_path', '/var/spool/pandora/data_in/netflow');
	$pa_config->{"netflow_interval"} = pandora_get_tconfig_token ($dbh, 'netflow_interval', 300);
	$pa_config->{"netflow_daemon"} = pandora_get_tconfig_token ($dbh, 'netflow_daemon', '/usr/bin/nfcapd');

	# Log module configuration
	$pa_config->{"log_dir"} = pandora_get_tconfig_token ($dbh, 'log_dir', '/var/spool/pandora/data_in/log');
	$pa_config->{"log_interval"} = pandora_get_tconfig_token ($dbh, 'log_interval', 3600);

	# Pandora FMS Console's attachment directory
	$pa_config->{"attachment_dir"} = pandora_get_tconfig_token ($dbh, 'attachment_store', '/var/www/pandora_console/attachment');

	# Metaconsole agent cache.
	$pa_config->{"metaconsole_agent_cache"} = pandora_get_tconfig_token ($dbh, 'metaconsole_agent_cache', 0);
	
	#Limit of events replicate in metaconsole
	$pa_config->{'replication_limit'} = pandora_get_tconfig_token ($dbh, 'replication_limit', 1000);
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
	$pa_config->{"basepath"} = $pa_config->{'pandora_path'}; # Compatibility with Pandora 1.1
	$pa_config->{"incomingdir"} = "/var/spool/pandora/data_in";
	$pa_config->{"server_threshold"} = 30;
	$pa_config->{"alert_threshold"} = 60;
	$pa_config->{"logfile"} = "/var/log/pandora_server.log";
	$pa_config->{"errorlogfile"} = "/var/log/pandora_server.error";
	$pa_config->{"networktimeout"} = 5;	# By default, not in config file yet
	$pa_config->{"pandora_master"} = 1;	# on by default
	$pa_config->{"pandora_check"} = 0; 	# Deprecated since 2.0
	$pa_config->{"servername"} = `hostname`;
	$pa_config->{"servername"} =~ s/\s//g; # Replace ' ' chars
	$pa_config->{"dataserver"} = 1; # default
	$pa_config->{"networkserver"} = 1; # default
	$pa_config->{"snmpconsole"} = 1; # default
	$pa_config->{"reconserver"} = 1; # default
	$pa_config->{"wmiserver"} = 1; # default
	$pa_config->{"pluginserver"} = 1; # default
	$pa_config->{"predictionserver"} = 1; # default
	$pa_config->{"exportserver"} = 1; # default
	$pa_config->{"inventoryserver"} = 1; # default
	$pa_config->{"webserver"} = 1; # 3.0
	$pa_config->{"servermode"} = "";
	$pa_config->{'snmp_logfile'} = "/var/log/pandora_snmptrap.log";
	$pa_config->{"network_threads"} = 3; # Fixed default
	$pa_config->{"keepalive"} = 60; # 60 Seconds initially for server keepalive
	$pa_config->{"keepalive_orig"} = $pa_config->{"keepalive"};
	$pa_config->{"icmp_checks"} = 1; # Introduced on 1.3.1
	$pa_config->{"icmp_packets"} = 1; # > 5.1SP2
	$pa_config->{"alert_recovery"} = 0; # Introduced on 1.3.1
	$pa_config->{"snmp_checks"} = 1; # Introduced on 1.3.1
	$pa_config->{"snmp_timeout"} = 8; # Introduced on 1.3.1
	$pa_config->{"snmp_trapd"} = '/usr/sbin/snmptrapd'; # 3.0
	$pa_config->{"tcp_checks"} = 1; # Introduced on 1.3.1
	$pa_config->{"tcp_timeout"} = 20; # Introduced on 1.3.1
	$pa_config->{"snmp_proc_deadresponse"} = 1; # Introduced on 1.3.1 10 Feb08
	$pa_config->{"plugin_threads"} = 2; # Introduced on 2.0
	$pa_config->{"plugin_exec"} = '/usr/bin/timeout'; # 3.0
	$pa_config->{"recon_threads"} = 2; # Introduced on 2.0
	$pa_config->{"prediction_threads"} = 1; # Introduced on 2.0
	$pa_config->{"plugin_timeout"} = 5; # Introduced on 2.0
	$pa_config->{"wmi_threads"} = 2; # Introduced on 2.0
	$pa_config->{"wmi_timeout"} = 5; # Introduced on 2.0
	$pa_config->{"wmi_client"} = 'wmic'; # 3.0
	$pa_config->{"dataserver_threads"} = 2; # Introduced on 2.0
	$pa_config->{"inventory_threads"} = 2; # 2.1
	$pa_config->{"export_threads"} = 1; # 3.0
	$pa_config->{"web_threads"} = 1; # 3.0
	$pa_config->{"web_engine"} = 'lwp'; # 5.1
	$pa_config->{"activate_gis"} = 0; # 3.1
	$pa_config->{"location_error"} = 50; # 3.1
	$pa_config->{"recon_reverse_geolocation_mode"} = 'disabled'; # 3.1
	$pa_config->{"recon_reverse_geolocation_file"} = '/usr/local/share/GeoIP/GeoIPCity.dat'; # 3.1
	$pa_config->{"recon_location_scatter_radius"} = 50; # 3.1
	$pa_config->{"update_parent"} = 0; # 3.1
	$pa_config->{"google_maps_description"} = 0;
	$pa_config->{'openstreetmaps_description'} = 0;
	$pa_config->{"eventserver"} = 1; # 4.0
	$pa_config->{"event_window"} = 3600; # 4.0
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
	
	# Internal MTA for alerts, each server need its own config.
	$pa_config->{"mta_address"} = '127.0.0.1'; # Introduced on 2.0
	$pa_config->{"mta_port"} = '25'; # Introduced on 2.0
	$pa_config->{"mta_user"} = ''; # Introduced on 2.0
	$pa_config->{"mta_pass"} = ''; # Introduced on 2.0
	$pa_config->{"mta_auth"} = 'none'; # Introduced on 2.0 (Support LOGIN PLAIN CRAM-MD5 DIGEST-MD)
	$pa_config->{"mta_from"} = 'pandora@localhost'; # Introduced on 2.0 
	$pa_config->{"mail_in_separate"} = 1; # 1: eMail deliver alert mail in separate mails.
					      # 0: eMail deliver 1 mail with all destination.

	# nmap for recon OS fingerprinting and tcpscan (optional)
	$pa_config->{"nmap"} = "/usr/bin/nmap";
	$pa_config->{"nmap_timing_template"} = 2; # > 5.1
	$pa_config->{"recon_timing_template"} = 3; # > 5.1

	$pa_config->{"fping"} = "/usr/sbin/fping"; # > 5.1SP2

	# braa for enterprise snmp server
	$pa_config->{"braa"} = "/usr/bin/braa";

	# SNMP enterprise retries (for braa)
	$pa_config->{"braa_retries"} = 3; # 5.0
	
	# Xprobe2 for recon OS fingerprinting and tcpscan (optional)
	$pa_config->{"xprobe2"} = "/usr/bin/xprobe2";

	
	# Snmpget for snmpget system command (optional)
	$pa_config->{"snmpget"} = "/usr/bin/snmpget";
	
	$pa_config->{'autocreate_group'} = -1;
	$pa_config->{'autocreate'} = 1;

	# max log size (bytes)
	$pa_config->{'max_log_size'} = 1048576;

	# max log generation
	$pa_config->{'max_log_generation'} = 1;

	# Ignore the timestamp in the XML and use the file timestamp instead
	$pa_config->{'use_xml_timestamp'} = 0; 

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

	# Process XML data files as a stack
	$pa_config->{"dataserver_lifo"} = 0; # 5.0

	# Patrol process of policies queue
	$pa_config->{"policy_manager"} = 0; # 5.0

	# Event replication process
	$pa_config->{"event_replication"} = 0; # 5.0

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

	# Console API connection
	$pa_config->{"console_api_url"} = 'http://localhost/pandora_console/include/api.php'; # 6.0
	$pa_config->{"console_api_pass"} = ''; # 6.0
	$pa_config->{"console_user"} = 'admin'; # 6.0
	$pa_config->{"console_pass"} = 'pandora'; # 6.0

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
	$pa_config->{"agentaccess"} = 1; 
	$pa_config->{"event_storm_protection"} = 0; 
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

	# External .enc files for XML::Parser.
	$pa_config->{"enc_dir"} = ""; # > 6.0SP4

	# Enable (1) or disable (0) events related to the unknown status.
	$pa_config->{"unknown_events"} = 1; # > 6.0SP4

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
		printf "	Please specify a valid Pandora FMS configuration file in command line. \n";
		print "	Standard configuration file is at /etc/pandora/pandora_server.conf \n";
		exit 1;
	}

	# Collect items from config file and put in an array 
	if (! open (CFG, "< $archivo_cfg")) {
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
				$pa_config->{"logfile"} = $pa_config->{"basepath"}.$1;
			} else {
				$pa_config->{"logfile"} = $tbuf;
			}
		}

		elsif ($parametro =~ m/^errorlog_file\s(.*)/i) { 
			$tbuf= clean_blank($1); 	
			if ($tbuf =~ m/^\.(.*)/){
				$pa_config->{"errorlogfile"} = $pa_config->{"basepath"}.$1;
			} else {
				$pa_config->{"errorlogfile"} = $tbuf;
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
		elsif ($parametro =~ m/^mail_in_separate\s+([0-9]*)/i) { 
			$pa_config->{'mail_in_separate'}= clean_blank($1); 
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
		elsif ($parametro =~ m/^snmp_delay\s+(\d+)/i) { 
			$pa_config->{'snmp_delay'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^snmpconsole_threads\s+(\d+)/i) { 
			$pa_config->{'snmpconsole_threads'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^translate_variable_bindings\s+([0-1])/i) { 
			$pa_config->{'translate_variable_bindings'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^translate_enterprise_strings\s+([0-1])/i) { 
			$pa_config->{'translate_enterprise_strings'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^dbengine\s(.*)/i) { 
			$pa_config->{'dbengine'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^dbname\s(.*)/i) { 
			$pa_config->{'dbname'}= clean_blank($1); 
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
		elsif ($parametro =~ m/^reconserver\s+([0-9]*)/i) {
			$pa_config->{'reconserver'}= clean_blank($1);
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
		elsif ($parametro =~ m/^eventserver\s+([0-9]*)/i) {
			$pa_config->{'eventserver'}= clean_blank($1);
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
			$pa_config->{"verbosity"} = clean_blank($1); 
		} 
		elsif ($parametro =~ m/^server_threshold\s+([0-9]*)/i) { 
			$pa_config->{"server_threshold"} = clean_blank($1); 
		} 
		elsif ($parametro =~ m/^alert_threshold\s+([0-9]*)/i) { 
			$pa_config->{"alert_threshold"} = clean_blank($1); 
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
		elsif ($parametro =~ m/^xprobe2\s(.*)/i) {
			$pa_config->{'xprobe2'}= clean_blank($1); 
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
		}
		elsif ($parametro =~ m/^recon_reverse_geolocation_mode\s+(\w+)/i) {
			$pa_config->{'recon_reverse_geolocation_mode'} = clean_blank($1);
		} #FIXME: Find a better regexp to validate the path
		elsif ($parametro =~ m/^recon_reverse_geolocation_file\s+(.*)/i) {
			$pa_config->{'recon_reverse_geolocation_file'} = clean_blank($1);
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
		elsif ($parametro =~ m/^update_parent\s+([0-1])/i) {
			$pa_config->{'update_parent'} = clean_blank($1);
		}
		elsif ($parametro =~ m/^event_window\s+([0-9]*)/i) {
			$pa_config->{'event_window'}= clean_blank($1);
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
		elsif ($parametro =~ m/^event_replication\s+([0-1])/i) {
			$pa_config->{'event_replication'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^event_auto_validation\s+([0-1])/i) {
			$pa_config->{'event_auto_validation'}= clean_blank($1);
		}
		elsif ($parametro =~ m/^event_file\s+(.*)/i) {
			$pa_config->{'event_file'}= clean_blank($1);
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
			$pa_config->{'encryption_passphrase'}= safe_input(clean_blank($1));
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
	} # end of loop for parameter #

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

	if (($pa_config->{"verbosity"} > 4) && ($pa_config->{"quiet"} == 0)){
		if ($pa_config->{"PID"} ne ""){
			print " [*] PID File is written at ".$pa_config->{'PID'}."\n";
		}
		print " [*] Server basepath is ".$pa_config->{'basepath'}."\n";
		print " [*] Server logfile at ".$pa_config->{"logfile"}."\n";
		print " [*] Server errorlogfile at ".$pa_config->{"errorlogfile"}."\n";
		print " [*] Server incoming directory at ".$pa_config->{"incomingdir"}."\n";
		print " [*] Server keepalive ".$pa_config->{"keepalive"}."\n";
		print " [*] Server threshold ".$pa_config->{"server_threshold"}."\n";
	}
 	# Check for valid token token values
 	if (( $pa_config->{"dbuser"} eq "" ) || ( $pa_config->{"basepath"} eq "" ) || ( $pa_config->{"incomingdir"} eq "" ) || ( $pa_config->{"logfile"} eq "" ) || ( $pa_config->{"dbhost"} eq "") || ( $pa_config->{"pandora_master"} eq "") || ( $pa_config->{"dbpass"} eq "" ) ) {
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
	my $config_options = "Logfile at ".$pa_config->{"logfile"}.", Basepath is ".$pa_config->{"basepath"}.", Checksum is ".$pa_config->{"pandora_check"}.", Master is ".$pa_config->{"pandora_master"}.", SNMP Console is ".$pa_config->{"snmpconsole"}.", Server Threshold at ".$pa_config->{"server_threshold"}." sec, verbosity at ".$pa_config->{"verbosity"}.", Alert Threshold at $pa_config->{'alert_threshold'}, ServerName is '".$pa_config->{'servername'}.$pa_config->{"servermode"}."'";
	logger ($pa_config, "Config options: $config_options", 1);
}


##########################################################################
# Open the log file and start logging.
##########################################################################
sub pandora_start_log ($){
	my $pa_config = shift;

	# Dump all errors to errorlog
	open (STDERR, ">> " . $pa_config->{'errorlogfile'}) or die " [ERROR] Pandora FMS can't write to Errorlog. Aborting : \n $! \n";
	print STDERR strftime ("%Y-%m-%d %H:%M:%S", localtime()) . ' - ' . $pa_config->{'servername'} . $pa_config->{'servermode'} . " Starting Pandora FMS Server. Error logging activated.\n";
}

##########################################################################
# Read the given token from the tconfig table.
##########################################################################
sub pandora_get_tconfig_token ($$$) {
	my ($dbh, $token, $default_value) = @_;
	
	my $token_value = get_db_value ($dbh, "SELECT value FROM tconfig WHERE token = ?", $token);
	if (defined ($token_value)) {
		return safe_output ($token_value);
	}
	
	return $default_value;
}

# End of function declaration
# End of defined Code

1;
__END__
