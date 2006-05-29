package pandora_config;

use 5.008004;

use warnings;
use Time::Local;
use Date::Manip;
use pandora_tools;
use pandora_db;
require Exporter;

our @ISA = ("Exporter");
our %EXPORT_TAGS = ( 'all' => [ qw( ) ] );
our @EXPORT_OK = ( @{ $EXPORT_TAGS{'all'} } );
our @EXPORT = qw( 	pandora_help_screen
			pandora_init
			pandora_loadconfig  );

# There is no global vars, all variables (setup) passed as hash reference

# version: Defines actual version of Pandora Server for this module only
my $pandora_version = "1.2beta";
my $pandora_build="PS060103";

# Setup hash
my %pa_config;

# Delaracion de funciones publicas 
##############################################################################
# SUB pandora_help_screen()
#  Show a help screen an exits
##############################################################################

sub help_screen {
	printf "Permission is granted to copy, distribute and/or modify this document \n";
    	printf "under the terms of the GNU Free Documentation License, Version 2.0 \n";
    	printf "or any later version published by the Free Software Foundation at www.gnu.org \n\n";
	printf "\n\nSyntax: \n  pandora_xxxxxxx.pl <fullpathname to PANDORA HOME directory> [ options ] \n\n";
	printf "Following options are optional : \n";
	printf "            -v  :  Verbose mode activated, give more information in logfile \n";
	printf "            -d  :  Debug mode activated, give extensive information in logfile \n";
	printf "            -D  :  Daemon mode (runs in backgroup)\n";
	printf "            -h  :  This screen, show a little help screen \n";
	printf " \n";
	exit;
}

##############################################################################
# SUB pandora_init ( %pandora_cfg )
# Makes the initial parameter parsing, initializing and error checking
##############################################################################

sub pandora_init {
	my $pa_config = $_[0];
	my $init_string = $_[1];
	printf "\n$init_string $pandora_version Build $pandora_build Copyright (c) 2004-2006 <slerena\@gmail.com>\n";
	printf "You can download latest versions and documentation at http://pandora.sourceforge.net. \n\n";

	# Check we are running Linux
	die "[ERROR] This isn't Linux. Pandora Server its only OFFICIALLY supported in Linux\nContact us if you require assistance running Pandora Server over other OS\n\n" unless ($^O =~ m/linux/i);

	# Load config file from command line
	if ($#ARGV == -1 ){
		print "I Need at least one parameter: Complete path to Pandora HOME Directory. \n";
		help_screen;
		exit;
	}
   	$pa_config->{"verbosity"}=1; 	# Verbose 1 by default
	$pa_config->{"daemon"}=0; 	# Daemon 0 by default

	# If there are not valid parameters
	my $parametro;
	my $ltotal=$#ARGV; my $ax;
	for ($ax=0;$ax<=$ltotal;$ax++){
		$parametro = $ARGV[$ax];
		if ($parametro =~ m/-h\z/i ) { help_screen();  }
		elsif ($parametro =~ m/-help\z/i ) { help_screen();  }
		elsif ($parametro =~ m/-help\z/i ) { help_screen();  }
		elsif ($parametro =~ m/-v\z/i) { $pa_config->{"verbosity"}=5; }
		elsif ($parametro =~ m/-d\z/) { $pa_config->{"verbosity"}=10; }
		elsif ($parametro =~ m/-D\z/) { $pa_config->{"daemon"}=1; }
		else { ($pa_config->{"pandora_path"} = $parametro); }
	}
	if ($pa_config->{"pandora_path"} eq ""){
		print "I Need at least one parameter: Complete path to Pandora HOME Directory. \n";
		exit;
	}
}

##############################################################################
# Read external configuration file
##############################################################################

sub pandora_loadconfig {
	my $pa_config = $_[0];
	my $opmode = $_[1]; # 0 dataserver, 1 network server, 2 snmp console
	my $archivo_cfg = $pa_config->{'pandora_path'}."/conf/pandora_server.conf";
	my $buffer_line;
	my @command_line;

	# Default values
	$pa_config->{'version'} = $pandora_version;
	$pa_config->{'build'} = $pandora_build;
	$pa_config->{"dbuser"} ="";
	$pa_config->{"dbpass"} = "";
	$pa_config->{"dbhost"} = "";
	$pa_config->{"basepath"}=$pa_config->{'pandora_path'}; # Compatibility with Pandora 1.1
	$pa_config->{"incomingdir"}="";
	$pa_config->{"server_threshold"}=30;
	$pa_config->{"alert_threshold"}=60;
	$pa_config->{"logfile"}=$pa_config->{'pandora_path'}."/log/pandora_server.log";
	$pa_config->{"errorlogfile"}=$pa_config->{'pandora_path'}."/log/pandora_server.error";
	$pa_config->{"networktimeout"}=15; 	# By default, not in config file yet
	$pa_config->{"pandora_master"}=1; 	# on by default
	$pa_config->{"pandora_check"}=1; 	# on by default
	$pa_config->{"snmpconsole"}=0; 	# off by default
	$pa_config->{"version"}=$pandora_version;
	$pa_config->{"build"}=$pandora_build;
	$pa_config->{"servername"}=`hostname`;
	$pa_config->{"servername"}=~ s/\s//g; # Replace ' ' chars
	$pa_config->{"networkserver"}=0;
	$pa_config->{"dataserver"}=0;
	$pa_config->{"network_threads"}=10; # Fixed default
	$pa_config->{"keepalive"}=200; # 200 Seconds initially for server keepalive

	# Check for UID0
	if ($> == 0){
		printf " [W] It is not a good idea running Pandora Server as root user, please DON'T DO IT!\n";
	}
	# Check for file
	if ( ! -e $archivo_cfg ) {
		printf "\n[ERROR] Cannot open configuration file at $archivo_cfg. \nPlease specify a valid Pandora Home Directory in command line. \n";
		exit 1;
	}
	# Collect items from config file and put in an array 
	open (CFG, "< $archivo_cfg");
	while (<CFG>){
		$buffer_line = $_;
		if ($buffer_line =~ /^[a-zA-Z]/){ # begins with letters
			if ($buffer_line =~ m/([\w\-\_\.]+)\s([0-9\w\-\_\.\/\?\&\=\)\(\_\-\!\*\@\#\%\$\~\"\']+)/){
				push @command_line,$buffer_line;
			}
		}
	}
 	close (CFG);
 	# Process this array with commandline like options 
 	# Process input parameters
 	my @args = @command_line;
 	my $parametro;
 	my $ltotal=$#args; my $ax;

 	# Has read setup file ok ?
 	if ( $ltotal == 0 ) {
  		print "[ERROR] No valid setup tokens readed in $archivo_cfg ";
  		exit;
 	}
 
 	for ($ax=0;$ax<=$ltotal;$ax++){
  		$parametro = $args[$ax];
		if ($parametro =~ m/^incomingdir\s(.*)/i) {  $pa_config->{"incomingdir"} = $1; }
		elsif ($parametro =~ m/^dbuser\s(.*)/i) { $pa_config->{'dbuser'}= $1; }
  		elsif ($parametro =~ m/^dbpass\s(.*)/i) { $pa_config->{'dbpass'}= $1; }
  		elsif ($parametro =~ m/^dbhost\s(.*)/i) { $pa_config->{'dbhost'}= $1; }
  		elsif ($parametro =~ m/^daemon\s([0-9]*)/i) { $pa_config->{'daemon'}= $1;}
		elsif ($parametro =~ m/^dataserver\s([0-9]*)/i) { $pa_config->{'dataserver'}= $1; }
		elsif ($parametro =~ m/^networkserver\s([0-9]*)/i) { $pa_config->{'networkserver'}= $1;}
		elsif ($parametro =~ m/^network_threads\s([0-9]*)/i) { $pa_config->{'network_threads'}= $1;}
		elsif ($parametro =~ m/^servername\s(.*)/i) { $pa_config->{'servername'}= $1; }
  		elsif ($parametro =~ m/^log_file\s(.*)/i) { $pa_config->{"logfile"} = $1;}
  		elsif ($parametro =~ m/^errorlog_file\s(.*)/i) { $pa_config->{"errorlogfile"} = $1; }
		elsif ($parametro =~ m/^checksum\s([0-9])/i) { $pa_config->{"pandora_check"} = $1; }
		elsif ($parametro =~ m/^master\s([0-9])/i) { $pa_config->{"pandora_master"} = $1; }
		elsif ($parametro =~ m/^snmpconsole\s([0-9])/i) { $pa_config->{"snmpconsole"} = $1;}
  		elsif ($parametro =~ m/^verbosity\s([0-9]*)/i) { $pa_config->{"verbosity"} = $1; } 
  		elsif ($parametro =~ m/^server_threshold\s([0-9]*)/i) { $pa_config->{"server_threshold"}  = $1; } 
		elsif ($parametro =~ m/^alert_threshold\s([0-9]*)/i) { $pa_config->{"alert_threshold"} = $1; } 
		elsif ($parametro =~ m/^network_timeout\s([0-9]*)/i) { $pa_config->{'networktimeout'}= $1; }
 	}
 
 	# Check for valid token token values
 	if (( $pa_config->{"dbuser"} eq "" ) || ( $pa_config->{"basepath"} eq "" ) || ( $pa_config->{"incomingdir"} eq "" ) || ( $pa_config->{"logfile"} eq "" ) || ( $pa_config->{"dbhost"} eq "")  || ( $pa_config->{"pandora_master"} eq "") || ( $pa_config->{"dbpass"} eq "" ) ) {
		print "[ERROR] Bad Config values. Be sure that $archivo_cfg is a valid setup file. \n\n";
		exit;
	}
	if (($opmode ==0) && ($pa_config->{"dataserver"} ne 1)) {
		print " [ERROR] You must enable Dataserver in setup file to run Pandora Server. \n\n";
		exit;
	} 
	if (($opmode ==1) && ($pa_config->{"networkserver"} ne 1)) {
		print " [ERROR] You must enable NetworkServer in setup file to run Pandora Network Server. \n\n";
		exit;
	}

	if (($opmode ==3) && ($pa_config->{"snmpconsole"} ne 1)) {
		print " [ERROR] You must enable SnmpConsole in setup file to run Pandora SNMP Console. \n\n";
		exit;
	}
	if ($opmode == 0){
		print " [*] You are running Pandora Data Server. \n";
		$parametro ="Pandora Data Server";
	}
	if ($opmode == 1){
		print " [*] You are running Pandora Network Server. \n";
		$parametro ="Pandora Network Server";
	}
	if ($opmode == 2){
		print " [*] You are running Pandora SNMP Console. \n";
		$parametro ="Pandora SNMP Console";
	}
	if ($pa_config->{"pandora_check"} == 1) {
		print " [*] MD5 Security enabled.\n";
	}
	if ($pa_config->{"pandora_master"} == 1) {
		print " [*] This server is running in MASTER mode.\n";
	}
	if ($pa_config->{"daemon"} == 1) {
		print " [*] This server is running in DAEMON mode.\n";
	}
	# Abrimos el directorio de datos y leemos cada fichero
	logger ($pa_config, "Launching $parametro $pa_config->{'version'} $pa_config->{'build'}", 0);
	my $config_options = "Logfile at ".$pa_config->{"logfile"}.", Basepath is ".$pa_config->{"basepath"}.", Checksum is ".$pa_config->{"pandora_check"}.", Master is ".$pa_config->{"pandora_master"}.", SNMP Console is ".$pa_config->{"snmpconsole"}.", Server Threshold at ".$pa_config->{"server_threshold"}." sec, verbosity at ".$pa_config->{"verbosity"}.", Alert Threshold at $pa_config->{'alert_threshold'}";
	logger ($pa_config, "Config options: $config_options");
	
	# Check valid Database variables and update server status
	eval {
		my $dbh = DBI->connect("DBI:mysql:pandora:$pa_config->{'dbhost'}:3306", $pa_config->{'dbuser'}, $pa_config->{'dbpass'}, { RaiseError => 1, AutoCommit => 1 });
		pandora_updateserver ($pa_config, $pa_config->{'servername'},1, $dbh); # Alive status
	};
	if ($@) {

		logger ($pa_config, "Error connecting database in init Phase. Aborting startup.",0);
		print (" [E] Error connecting database in init Phase. Aborting startup. \n\n");
		exit;
	}

	# Dump all errors to errorlog
	# DISABLED in DEBUGMODE
	# ENABLE FOR PRODUCTION
	# open STDERR, ">>$pa_config->{'errorlogfile'}" or die "Can't write to Errorlog : $!";
}


# End of function declaration
# End of defined Code

1;
__END__
