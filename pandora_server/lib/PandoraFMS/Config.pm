package PandoraFMS::Config;
##########################################################################
# Configuration Package
# Pandora FMS. the Flexible Monitoring System. http://www.pandorafms.org
##########################################################################
# Copyright (c) 2004-2008 Sancho Lerena, slerena@gmail.com
# Copyright (c) 2005-2008 Artica Soluciones Tecnologicas S.L
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License 
# as published by the Free Software Foundation; at version 2.
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
##########################################################################

use warnings;
use Time::Local;
use Date::Manip;
use PandoraFMS::Tools;
use PandoraFMS::DB;
require Exporter;

our @ISA = ("Exporter");
our %EXPORT_TAGS = ( 'all' => [ qw( ) ] );
our @EXPORT_OK = ( @{ $EXPORT_TAGS{'all'} } );
our @EXPORT = qw( 	
        pandora_help_screen
		pandora_init
		pandora_loadconfig
        pandora_startlog
    );

# There is no global vars, all variables (setup) passed as hash reference

# version: Defines actual version of Pandora Server for this module only
my $pandora_version = "2.0Beta1";
my $pandora_build="PS080822";
our $VERSION = $pandora_version." ".$pandora_build;

# Setup hash
my %pa_config;

# Public functions
##########################################################################
# SUB pandora_help_screen()
#  Show a help screen an exits
##########################################################################

sub help_screen {
	printf "\nSyntax: \n\n  pandora_server [ options ] < fullpathname to configuration file > \n\n";
	printf "Following options are optional : \n";
	printf "      -v        :  Verbose mode activated, give more information in logfile \n";
	printf "      -d        :  Debug mode activated, give extensive information in logfile \n";
	printf "      -D        :  Daemon mode (runs in backgroup)\n";
    printf "      -P <file> :  Store PID to file.\n";
    printf "      -q        :  Quiet startup\n";
	printf "      -h        :  This screen, show a little help screen \n";
	printf " \n";
	exit;
}

##########################################################################
# SUB pandora_init ( %pandora_cfg )
# Makes the initial parameter parsing, initializing and error checking
##########################################################################

sub pandora_init {
	my $pa_config = $_[0];
	my $init_string = $_[1];
	printf "\n$init_string $pandora_version Build $pandora_build Copyright (c) 2004-2008 ArticaST\n";
	printf "This program is OpenSource, licensed under the terms of GPL License version 2.\n";
	printf "You can download latest versions and documentation at http://www.pandorafms.org \n\n";

	# Load config file from command line
	if ($#ARGV == -1 ){
		print "I Need at least one parameter: Complete path to Pandora FMS Server configuration file. \n";
		help_screen;
		exit;
	}
   	$pa_config->{"verbosity"}=1; 	# Verbose 1 by default
	$pa_config->{"daemon"}=0; 	# Daemon 0 by default
    $pa_config->{'PID'}=""; # PID file not exist by default
    $pa_config->{"quiet"}=0;   # Daemon 0 by default

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
        else { 
            ($pa_config->{"pandora_path"} = $parametro); 
        }
    }
	if ($pa_config->{"pandora_path"} eq ""){
		print " [ERROR] I Need at least one parameter: Complete path to Pandora FMS configuration file. \n";
        print "         For example: ./pandora_server /etc/pandora/pandora_server.conf\n\n";
		exit;
	}
}

##########################################################################
# Read external configuration file
##########################################################################

sub pandora_loadconfig {
    my $pa_config = $_[0];
    my $opmode = $_[1]; # 0 dataserver, 1 network server, 2 snmp console
                        # 3 recon srv, 4 plugin srv, 5 prediction srv
                        # 6 WMI server, 7 export server
    my $archivo_cfg = $pa_config->{'pandora_path'};
    my $buffer_line;
    my @command_line;
    my $tbuf;

    # Default values
    $pa_config->{'version'} = $pandora_version;
    $pa_config->{'build'} = $pandora_build;
    $pa_config->{"dbuser"} = "pandora";
    $pa_config->{"dbpass"} = "pandora";
    $pa_config->{"dbhost"} = "localhost";
    $pa_config->{"dbname"} = "pandora";
    $pa_config->{"basepath"} = $pa_config->{'pandora_path'}; # Compatibility with Pandora 1.1
    $pa_config->{"incomingdir"} = "/var/spool/pandora/data_in";
    $pa_config->{"server_threshold"} = 30;
    $pa_config->{"alert_threshold"} = 60;
    $pa_config->{"logfile"} = "/var/log/pandora_server.log";
    $pa_config->{"errorlogfile"} = "/var/log/pandora_server.error";
    $pa_config->{"networktimeout"} = 5; 	# By default, not in config file yet
    $pa_config->{"pandora_master"} = 1; 	# on by default
    $pa_config->{"pandora_check"} = 0; 	# on by default
    $pa_config->{"version"} = $pandora_version;
    $pa_config->{"build"} = $pandora_build;
    $pa_config->{"servername"} = `hostname`;
    $pa_config->{"servername"} =~ s/\s//g; # Replace ' ' chars
    $pa_config->{"dataserver"} = 0;
    $pa_config->{"networkserver"} = 0;
    $pa_config->{"snmpconsole"} = 0;
    $pa_config->{"reconserver"} = 0;
    $pa_config->{"wmiserver"} = 0; # Introduced on 2.0
    $pa_config->{"pluginserver"} = 0; # Introduced on 2.0
    $pa_config->{"predictionserver"} = 0; # Introduced on 2.0
    $pa_config->{"exportserver"} = 0; # 2.0
    $pa_config->{"servermode"} = "";
    $pa_config->{'snmp_logfile'} = "/var/log/pandora_snmptrap.log";
    $pa_config->{"network_threads"} = 5; # Fixed default
    $pa_config->{"keepalive"} = 60; # 60 Seconds initially for server keepalive
    $pa_config->{"keepalive_orig"} = $pa_config->{"keepalive"};
    $pa_config->{"icmp_checks"} = 1; # Introduced on 1.3.1
    $pa_config->{"alert_recovery"} = 0; # Introduced on 1.3.1
    $pa_config->{"snmp_checks"} = 1; # Introduced on 1.3.1
    $pa_config->{"snmp_timeout"} = 8; # Introduced on 1.3.1
    $pa_config->{"tcp_checks"} = 1; # Introduced on 1.3.1
    $pa_config->{"tcp_timeout"} = 20; # Introduced on 1.3.1
    $pa_config->{"snmp_proc_deadresponse"} = 0; # Introduced on 1.3.1 10 Feb08
    $pa_config->{"plugin_threads"} = 3; # Introduced on 2.0
    $pa_config->{"recon_threads"} = 3; # Introduced on 2.0
    $pa_config->{"prediction_threads"} = 3; # Introduced on 2.0
    $pa_config->{"plugin_timeout"} = 5; # Introduced on 2.0
    $pa_config->{"wmi_threads"} = 3; # Introduced on 2.0
    $pa_config->{"wmi_timeout"} = 5; # Introduced on 2.0
    $pa_config->{"compound_max_depth"} = 5; # Maximum nested compound alert depth. Not in config file.
    $pa_config->{"dataserver_threads"} = 3; # Introduced on 2.0

    # Internal MTA for alerts, each server need its own config.
    $pa_config->{"mta_address"} = '127.0.0.1'; # Introduced on 2.0
    $pa_config->{"mta_port"} = '25'; # Introduced on 2.0
    $pa_config->{"mta_user"} = ''; # Introduced on 2.0
    $pa_config->{"mta_pass"} = ''; # Introduced on 2.0
    $pa_config->{"mta_auth"} = 'none'; # Introduced on 2.0  (Support LOGIN PLAIN CRAM-MD5 DIGEST-MD)
    $pa_config->{"mta_from"} = 'pandora@localhost'; # Introduced on 2.0  

	# Xprobe2 for recon OS fingerprinting (optional feature to detect OS)
	$pa_config->{"xprobe2"} = "/usr/bin/xprobe2";
	$pa_config->{'autocreate_group'} = 2;
	$pa_config->{'autocreate'} = 1;
    $pa_config->{'recon_threads'} = 3;

	# max log size (bytes)
	$pa_config->{'max_log_size'} = 1048576; # 1MB by default

	# Check for UID0
    if ($pa_config->{"quiet"} != 0){
	    if ($> == 0){
		    printf " [W] Not all Pandora FMS components need to be executed as root\n";
		    printf "     please consider starting it with a non-privileged user.\n";
	    }
    }

	# Check for file
	if ( ! -e $archivo_cfg ) {
		printf "\n [ERROR] Cannot open configuration file at $archivo_cfg. \n";
		printf "  Please specify a valid Pandora FMS configuration file in command line.\n";
        print  "  Standard configuration file is at /etc/pandora/pandora_server.conf \n";
		exit 1;
	}
	# Collect items from config file and put in an array 
	open (CFG, "< $archivo_cfg");
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
        elsif ($parametro =~ m/^snmp_logfile\s(.*)/i) { 
            $pa_config->{'snmp_logfile'}= clean_blank($1); 
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
  		elsif ($parametro =~ m/^daemon\s([0-9]*)/i) { 
            $pa_config->{'daemon'}= clean_blank($1);
        }
		elsif ($parametro =~ m/^dataserver\s([0-9]*)/i){
			$pa_config->{'dataserver'}= clean_blank($1);
		}
        elsif ($parametro =~ m/^networkserver\s([0-9]*)/i){
            $pa_config->{'networkserver'}= clean_blank($1);
        }
        elsif ($parametro =~ m/^pluginserver\s([0-9]*)/i){
            $pa_config->{'pluginserver'}= clean_blank($1);
        }
        elsif ($parametro =~ m/^predictionserver\s([0-9]*)/i){
            $pa_config->{'predictionserver'}= clean_blank($1);
        }
        elsif ($parametro =~ m/^reconserver\s([0-9]*)/i) {
            $pa_config->{'reconserver'}= clean_blank($1);
        }
        elsif ($parametro =~ m/^reconserver\s([0-9]*)/i) {
            $pa_config->{'reconserver'}= clean_blank($1);
        }
        elsif ($parametro =~ m/^wmiserver\s([0-9]*)/i) {
	        $pa_config->{'wmiserver'}= clean_blank($1);
        }
        elsif ($parametro =~ m/^exportserver\s([0-9]*)/i) {
            $pa_config->{'exportserver'}= clean_blank($1);
        }
        elsif ($parametro =~ m/^servername\s(.*)/i) { 
            $pa_config->{'servername'}= clean_blank($1);
        }
        elsif ($parametro =~ m/^checksum\s([0-9])/i) { 
            $pa_config->{"pandora_check"} = clean_blank($1); 
        }
        elsif ($parametro =~ m/^master\s([0-9])/i) { 
	        $pa_config->{"pandora_master"} = clean_blank($1); 
        }
        elsif ($parametro =~ m/^icmp_checks\s([0-9])/i) { 
	        $pa_config->{"icmp_checks"} = clean_blank($1); 
        }
        elsif ($parametro =~ m/^snmpconsole\s([0-9])/i) {
	        $pa_config->{"snmpconsole"} = clean_blank($1);
        }
        elsif ($parametro =~ m/^alert_recovery\s([0-9])/i) {
	        $pa_config->{"alert_recovery"} = clean_blank($1);
        }
        elsif ($parametro =~ m/^snmp_checks\s([0-9])/i) {
            $pa_config->{"snmp_checks"} = clean_blank($1);
        }
        elsif ($parametro =~ m/^snmp_timeout\s([0-9])/i) {
            $pa_config->{"snmp_timeout"} = clean_blank($1);
        }
        elsif ($parametro =~ m/^tcp_checks\s([0-9])/i) {
            $pa_config->{"tcp_checks"} = clean_blank($1);
        }
        elsif ($parametro =~ m/^tcp_timeout\s([0-9])/i) {
            $pa_config->{"tcp_timeout"} = clean_blank($1);
        }
        elsif ($parametro =~ m/^snmp_proc_deadresponse\s([0-9]*)/i) { 
            $pa_config->{"snmp_proc_deadresponse"} = clean_blank($1);
        }
        elsif ($parametro =~ m/^verbosity\s([0-9]*)/i) {
            $pa_config->{"verbosity"} = clean_blank($1); 
        } 
        elsif ($parametro =~ m/^server_threshold\s([0-9]*)/i) { 
            $pa_config->{"server_threshold"} = clean_blank($1); 
        } 
        elsif ($parametro =~ m/^alert_threshold\s([0-9]*)/i) { 
            $pa_config->{"alert_threshold"} = clean_blank($1); 
        } 
        elsif ($parametro =~ m/^network_timeout\s([0-9]*)/i) {
            $pa_config->{'networktimeout'}= clean_blank($1); 
        }
        elsif ($parametro =~ m/^network_threads\s([0-9]*)/i) {
            $pa_config->{'network_threads'}= clean_blank($1); 
        }
        elsif ($parametro =~ m/^plugin_threads\s([0-9]*)/i) {
            $pa_config->{'plugin_threads'}= clean_blank($1); 
        }
        elsif ($parametro =~ m/^prediction_threads\s([0-9]*)/i) {
            $pa_config->{'prediction_threads'}= clean_blank($1); 
        }
        elsif ($parametro =~ m/^plugin_timeout\s([0-9]*)/i) {
            $pa_config->{'plugin_timeout'}= clean_blank($1); 
        }
        elsif ($parametro =~ m/^dataserver_threads\s([0-9]*)/i) {
            $pa_config->{'dataserver_threads'}= clean_blank($1); 
        }
        elsif ($parametro =~ m/^server_keepalive\s([0-9]*)/i) {
            $pa_config->{"keepalive"} = clean_blank($1);
            $pa_config->{"keepalive_orig"} = clean_blank($1);
        }
        elsif ($parametro =~ m/^xprobe2\s(.*)/i) {
            $pa_config->{'xprobe2'}= clean_blank($1); 
        }
		elsif ($parametro =~ m/^autocreate\s([0-9*]*)/i) {
            $pa_config->{'autocreate'}= clean_blank($1); 
        }
		elsif ($parametro =~ m/^autocreate_group\s([0-9*]*)/i) {
            $pa_config->{'autocreate_group'}= clean_blank($1); 
        }
        elsif ($parametro =~ m/^recon_threads\s([0-9]*)/i) {
            $pa_config->{'recon_threads'}= clean_blank($1); 
        }
		elsif ($parametro =~ m/^max_log_size\s([0-9]*)/i) {
            $pa_config->{'max_log_size'}= clean_blank($1); 
        }
    } # end of loop for parameter #


	if (($pa_config->{"verbosity"} > 0) && ($pa_config->{"quiet"} == 0)){
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
 	if (( $pa_config->{"dbuser"} eq "" ) || ( $pa_config->{"basepath"} eq "" ) || ( $pa_config->{"incomingdir"} eq "" ) || ( $pa_config->{"logfile"} eq "" ) || ( $pa_config->{"dbhost"} eq "")  || ( $pa_config->{"pandora_master"} eq "") || ( $pa_config->{"dbpass"} eq "" ) ) {
		print " [ERROR] Bad Config values. Be sure that $archivo_cfg is a valid setup file. \n\n";
		exit;
	}
    if (($opmode ==0) && ($pa_config->{"dataserver"} ne 1)) {
	    print " [ERROR] You must enable 'dataserver' in setup file to run Pandora FMS Data Server. \n\n";
	    exit;
    } 
    if (($opmode ==1) && ($pa_config->{"networkserver"} ne 1)) {
	    print " [ERROR] You must enable 'networkserver' in setup file to run Pandora FMS Network Server. \n\n";
	    exit;
    }
    if (($opmode ==2) && ($pa_config->{"snmpconsole"} ne 1)) {
	    print " [ERROR] You must enable 'snmpconsole' in setup file to run Pandora FMS SNMP Console. \n\n";
	    exit;
    }
    if (($opmode ==3) && ($pa_config->{"reconserver"} ne 1)) {
	    print " [ERROR] You must enable 'reconserver' in setup file to run Pandora FMS Recon server. \n\n";
	    exit;
    }
    if (($opmode ==4) && ($pa_config->{"pluginserver"} ne 1)) {
        print " [ERROR] You must enable 'pluginserver' in setup file to run Pandora FMS Plugin server. \n\n";
        exit;
    }
    if (($opmode ==5) && ($pa_config->{"predictionserver"} ne 1)) {
        print " [ERROR] You must enable 'predictionserver' in setup file to run Pandora FMS Prediction server. \n\n";
        exit;
    }
    if (($opmode ==6) && ($pa_config->{"wmiserver"} ne 1)) {
        print " [ERROR] You must enable 'wmiserver' in setup file to run Pandora FMS WMI server. \n\n";
        exit;
    }
    if (($opmode ==7) && ($pa_config->{"exportserver"} ne 1)) {
        print " [ERROR] You must enable 'exportserver' in setup file to run Pandora FMS Export server. \n\n";
        exit;
    }
    # Show some config options in startup
    if ($pa_config->{"quiet"} == 0){
	    if ($opmode == 0){
		    print " [*] You are running Pandora FMS Data Server. \n";
		    $parametro ="Pandora FMS Data Server";
		    $pa_config->{"servermode"}="_Data";
	    }
	    if ($opmode == 1){
		    print " [*] You are running Pandora FMS Network Server. \n";
		    $parametro ="Pandora FMS Network Server";
		    $pa_config->{"servermode"}="_Net";
	    }
	    if ($opmode == 2){
		    print " [*] You are running Pandora FMS SNMP Console. \n";
		    $parametro ="Pandora FMS SNMP Console";
		    $pa_config->{"servermode"}="_SNMP";
	    }
	    if ($opmode == 3){
		    print " [*] You are running Pandora FMS Recon Server. \n";
		    $parametro ="Pandora FMS Recon Server";
		    $pa_config->{"servermode"}="_Recon";
	    }
        if ($opmode == 4){
            print " [*] You are running Pandora FMS Plugin Server. \n";
            $parametro ="Pandora FMS Plugin Server";
            $pa_config->{"servermode"}="_Plugin";
        }
        if ($opmode == 5){
            print " [*] You are running Pandora FMS Prediction Server. \n";
            $parametro ="Pandora FMS Prediction Server";
            $pa_config->{"servermode"}="_Prediction";
        }
        if ($opmode == 6){
            print " [*] You are running Pandora FMS WMI Server. \n";
            $parametro ="Pandora FMS WMI Server";
            $pa_config->{"servermode"}="_WMI";
        }
        if ($opmode == 7){
            print " [*] You are running Pandora FMS Export Server. \n";
            $parametro ="Pandora FMS Export Server";
            $pa_config->{"servermode"}="_Export";
        }
	    if ($pa_config->{"pandora_check"} == 1) {
		    print " [*] MD5 Security enabled.\n";
	    }
	    if ($pa_config->{"pandora_master"} == 1) {
		    print " [*] This server is running in MASTER mode.\n";
	    }
    }
	logger ($pa_config, "Launching $parametro $pa_config->{'version'} $pa_config->{'build'}", 0);
	my $config_options = "Logfile at ".$pa_config->{"logfile"}.", Basepath is ".$pa_config->{"basepath"}.", Checksum is ".$pa_config->{"pandora_check"}.", Master is ".$pa_config->{"pandora_master"}.", SNMP Console is ".$pa_config->{"snmpconsole"}.", Server Threshold at ".$pa_config->{"server_threshold"}." sec, verbosity at ".$pa_config->{"verbosity"}.", Alert Threshold at $pa_config->{'alert_threshold'}, ServerName is '".$pa_config->{'servername'}.$pa_config->{"servermode"}."'";
	logger ($pa_config, "Config options: $config_options", 1);
	my $dbh;
	# Check valid Database variables and update server status
	eval {
		$dbh = DBI->connect("DBI:mysql:$pa_config->{'dbname'}:$pa_config->{'dbhost'}:3306", $pa_config->{'dbuser'}, $pa_config->{'dbpass'}, { RaiseError => 1, AutoCommit => 1 });
		pandora_updateserver ($pa_config, $pa_config->{'servername'}, 1, $opmode, $dbh); # Alive status
	};
	if ($@) {
		logger ($pa_config, "Error connecting database in init Phase. Aborting startup.",0);
		print (" [ERROR] Error connecting database in init Phase. Aborting startup. \n\n");
		print $@;
		exit;
	}
    if ($pa_config->{"quiet"} == 0){
	    print " [*] Pandora FMS Server [".$pa_config->{'servername'}.$pa_config->{"servermode"}."] is running and operative \n";
    }
	$pa_config->{'server_id'} = dame_server_id ($pa_config, $pa_config->{'servername'}.$pa_config->{"servermode"}, $dbh);
    pandora_event ($pa_config, $pa_config->{'servername'}.$pa_config->{"servermode"}." going UP", 0,
                                   0, 3, 0, 0, "system", $dbh);
}



sub pandora_startlog ($){
    my $pa_config = $_[0];

    # Dump all errors to errorlog
    open STDERR, ">>$pa_config->{'errorlogfile'}" or die " [ERROR] Pandora FMS can't write to Errorlog. Aborting : \n $! \n";
    my $time_now = &UnixDate("today","%Y/%m/%d %H:%M:%S");
    print STDERR "$time_now - ".$pa_config->{'servername'}.$pa_config->{"servermode"}." Starting Pandora FMS Server. Error logging activated \n";
    # This redirect ANY output to errorlog.
    # open STDOUT, ">>$pa_config->{'errorlogfile'}"
}
# End of function declaration
# End of defined Code

1;
__END__
