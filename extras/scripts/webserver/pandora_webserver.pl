#!/usr/bin/perl
# Pandora FMS Agent Plugin for SunONE
# (c) Artica Soluciones Tecnologicas  <info@artica.es> 2011
# v2, 1 Sep 2011 
# ------------------------------------------------------------------------

use strict;
use warnings; 

use IO::Socket::INET;

# OS and OS version
my $OS = $^O;


# Load on Win32 only
if ($OS eq "MSWin32"){

	# Check dependencies
	eval 'local $SIG{__DIE__}; use Win32::OLE("in");';
	if ($@) {
		print "Error loading Win32::Ole library. Cannot continue\n";
		exit;
	}

    use constant wbemFlagReturnImmediately => 0x10;
    use constant wbemFlagForwardOnly => 0x20;
}

my %plugin_setup; # This stores plugin parameters
my $archivo_cfg = $ARGV[0];

my $volume_items = 0;
my $log_items = 0;
my $webcheck_items = 0;
my $process_items = 0;


# FLUSH in each IO
$| = 1;

# ----------------------------------------------------------------------------
# This cleans DOS-like line and cleans ^M character. VERY Important when you process .conf edited from DOS
# ----------------------------------------------------------------------------

sub parse_dosline ($){
    my $str = $_[0];

    $str =~ s/\r//g;
    return $str;
}

# ----------------------------------------------------------------------------
# Strips blank likes
# ----------------------------------------------------------------------------

sub trim ($){
	my $string = shift;
	$string =~ s/^\s+//;
	$string =~ s/\s+$//;
	return $string;
}


# ----------------------------------------------------------------------------
# clean_blank 
#
# This function return a string without blankspaces, given a simple text string
# ----------------------------------------------------------------------------

sub clean_blank($){
        my $input = $_[0];
        $input =~ s/[\s\r\n]*//g;
        return $input;
}

# ----------------------------------------------------------------------------
# print_module
#
# This function return a pandora FMS valid module fiven name, type, value, description 
# ----------------------------------------------------------------------------

sub print_module ($$$$){
    my $MODULE_NAME = $_[0];
    my $MODULE_TYPE = $_[1];
    my $MODULE_VALUE = $_[2];
    my $MODULE_DESC = $_[3];

    # If not a string type, remove all blank spaces!    
    if ($MODULE_TYPE !~ m/string/){
        $MODULE_VALUE =  clean_blank($MODULE_VALUE);
    }    

    print "<module>\n";
    print "<name>$MODULE_NAME</name>\n";
    print "<type>$MODULE_TYPE</type>\n";
    print "<data><![CDATA[$MODULE_VALUE]]></data>\n";
    print "<description><![CDATA[$MODULE_DESC]]></description>\n";
    print "</module>\n";

}


# ----------------------------------------------------------------------------
# load_external_setup
#
# Load external file containing configuration
# ----------------------------------------------------------------------------
sub load_external_setup ($); # Declaration due a recursive call to itself on includes
sub load_external_setup ($){

    my $archivo_cfg = $_[0];
    my $buffer_line;
    my @config_file;
    my $parametro = "";

    # Collect items from config file and put in an array
    if (! open (CFG, "< $archivo_cfg")) {
            print "[ERROR] Error opening configuration file $archivo_cfg: $!.\n";
            exit 1;
    }

    while (<CFG>){
            $buffer_line = parse_dosline ($_);
            # Parse configuration file, this is specially difficult because can contain SQL code, with many things
            if ($buffer_line !~ /^\#/){  # begins with anything except # (for commenting)
                    if ($buffer_line =~ m/(.+)\s(.*)/){
                            push @config_file, $buffer_line;
                    }
            }
    }
    close (CFG);

    # Some plugin setup default options

    $plugin_setup{"logparser"}="/etc/pandora/plugins/grep_log";
    $plugin_setup{"timeout"} = 5;
    $plugin_setup{"apache_stats"} = "";

    foreach (@config_file){
        $parametro = $_;

        if ($parametro =~ m/^include\s(.*)/i) {
            load_external_setup ($1);
        }

        if ($parametro =~ m/^logparser\s(.*)/i) {
            $plugin_setup{"logparser"}=$1;

        }

        if ($parametro =~ m/^timeout\s(.*)/i) {
            $plugin_setup{"timeout"}=clean_blank($1);

        }

        # Log check
        if ($parametro =~ m/^log\s(.*)/i) { 
            $plugin_setup{"log"}->[$log_items]=$1;
            $log_items++;
        }

        # Volume check
        if ($parametro =~ m/^volume\s(.*)/i) {
            $plugin_setup{"volume"}->[$volume_items]=$1;
            $volume_items++;
        }

        # Webcheck
        if ($parametro =~ m/^webcheck\s(.*)/i) {
            $plugin_setup{"webcheck"}->[$webcheck_items]=$1;
            $webcheck_items++;
        }

        # Processcheck
        if ($parametro =~ m/^process\s(.*)/i) {
            $plugin_setup{"process"}->[$process_items]=$1;
            $process_items++;
        }
    
        # Apachestats
        if ($parametro =~ m/^apache_stats\s(.*)/i) {
            $plugin_setup{"apache_stats"}=$1;
        }

    }
}


# ----------------------------------------------------------------------------
# http_check 
#
# This function recives something like 0.0.0.0:80 / 200 OK
# to check a HTTP response, given Host:PORT, URL and Search string
# Return 0 if not, and 1 if found
# ----------------------------------------------------------------------------

sub http_check ($$$$$){
    my $name = $_[0];
    my $host = $_[1];
    my $port = $_[2];
    my $query_string = $_[3];
    my $search_string = $_[4];

	
    my $tcp_send = "GET $query_string HTTP/1.0\n\n";
	my $temp; my $match = 0;
	
    my $sock = new IO::Socket::INET (
        PeerAddr => $host,
        PeerPort => $port,
        Proto => 'tcp',
        Timeout=> $plugin_setup{"timeout"},
        Blocking=>1 ); # Non block gives non-accurate results. We need to be SURE about this results :(

    if (!$sock){
        print_module("web_$name", "generic_proc", 0, "HTTP Check on $host for $query_string");
        return;
    }

    # Send data
    $sock->autoflush(1);

    $tcp_send =~ s/\^M/\r\n/g;
    # Replace Carriage return and line feed
    
	print $sock $tcp_send;
	my @buffer = <$sock>;
	
	# Search on buffer
	foreach (@buffer) {
		if ($_ =~ /$search_string/){
			$match = 1;
			last;
		}	
	}
	$sock->close;

    print_module ("web_$name", "generic_proc", $match, "HTTP Check on $host for $query_string");

    return;
	
}


# ----------------------------------------------------------------------------
# apache_stats 
#
# This function uses mod_status from apache to get information
# Given Instance, Host:PORT, URL (usually should be /server-status)
# ----------------------------------------------------------------------------

sub apache_stats ($$$$){
    my $name = $_[0];
    my $host = $_[1];
    my $port = $_[2];
    my $query_string = $_[3];
	
    if ($query_string eq ""){
        $query_string = "/";
    }

    my $tcp_send = "GET $query_string HTTP/1.0\n\n";
	my $temp; my $match = 0;

    # First at all, check response on apache (200 OK)

    http_check ("Apache_Status_$name", $host, $port, $query_string, "200 OK");
	
    my $sock = new IO::Socket::INET (
        PeerAddr => $host,
        PeerPort => $port,
        Proto => 'tcp',
        Timeout=> $plugin_setup{"timeout"},
        Blocking=>1 ); # Non block gives non-accurate results. We need to be SURE about this results :(

    if (!$sock){
        return;
    }

    # Send data
    $sock->autoflush(1);

    $tcp_send =~ s/\^M/\r\n/g;
    # Replace Carriage return and line feed
    
	print $sock $tcp_send;
	my @buffer = <$sock>;
	
	# Search on buffer
	foreach (@buffer) {

        if ($_ =~ /Restart Time: ([aA-zZ]+\,\s[0-9]{2}\-[aA-zZ]{3}\-[0-9]{4}\s[0-9]{2}\:[0-9]{2}\:[0-9]{2}\s[aA-zZ]+)/ ) {
            print_module ("apache_restart_time_$name", "generic_data_string", $1, "" );
        }			

    	if ($_ =~ /Server uptime: ([aA-zZ 0-9]+)/) {
            print_module ("apache_server_uptime_$name", "generic_data_string", $1, "" );
        }
        
	    if ($_ =~ /Total accesses: ([0-9]+)/ ) {
                print_module ("apache_accesses_$name", "generic_data_inc", $1, "" );
        }

        if ($_ =~ /Total Traffic: ([0-9]+)/ ) {
	        print_module ("apache_total_traffic_$name", "generic_data_inc", $1, "" );
		}

        if ($_ =~ /([0-9]+\.[0-9]+)\%\sCPU\sload/ ){
            print_module ("apache_CPU_Load_$name", "generic_data", $1, "" );  
        }

        if ($_ =~ /CPU Usage\: u([\.0-9]*)/ ){
            print_module ("apache_CPU_User_Load_$name", "generic_data", $1, "" );  
        }

        if ($_ =~ /CPU Usage\: u[\.0-9]* s([\.0-9]*)/ ){
            print_module ("apache_CPU_System_Load_$name", "generic_data", $1, "" );  
        }

        if ($_ =~ /([\.0-9]+)\srequests\/sec/){
            print_module ("apache_Req/Sec_$name", "generic_data", $1, "" );  
        }

        if ($_ =~ /([0-9]+)\sB\/second/) {
    		print_module ("apache_B/Sec_$name", "generic_data_inc", $1, "" );  	
		}

		if ($_ =~ /([0-9]+)\skB\/request/) {
            print_module ("apache_KB/Request_$name", "generic_data_inc", $1, "" );  			
		}

		if ($_ =~ /([0-9]+)\srequests\scurrently/) {
            print_module ("apache_request_currently_$name", "generic_data", $1, "" );  			
		}

		if ($_ =~ /([0-9]+)\sidle\sworkers/) {
            print_module ("apache_idle_workers_$name", "generic_data", $1, "" );  			
		}

	}
	$sock->close;

    return;
	
}


# ----------------------------------------------------------------------------
# alert_log
#
# Do a call to alertlog plugin and output the result
# Receives logfile, and module name
# ----------------------------------------------------------------------------

sub alert_log($$$){
    my $alertlog = $_[0];
    my $module_name = $_[1];
    my $log_expression = $_[2];

    my $plugin_call = "";
    # Call to logparser
    
    if ($OS eq "MSWin32") { 
        $plugin_call = $plugin_setup{"logparser"}. " $alertlog $module_name $log_expression";
    } else {
        $plugin_call = $plugin_setup{"logparser"}. " $alertlog $module_name $log_expression 2> /dev/null";
    }

    my $output = `$plugin_call`;

    if ($output ne ""){
        print $output;
    } else {
        print_module($module_name, "async_string", "", "Alertlog for $alertlog ($log_expression)");
    }
    
}

# ----------------------------------------------------------------------------
# spare_system_disk_win
#
# This function return % free disk on Windows, using WMI call
# ----------------------------------------------------------------------------

sub spare_system_disk_win ($$){

	my $name = $_[0];
	my $volume = $_[1];
	

	my $computer = "localhost";
	my $objWMIService = Win32::OLE->GetObject("winmgmts:\\\\$computer\\root\\CIMV2") or return;
	my $colItems = $objWMIService->ExecQuery("SELECT * from CIM_LogicalDisk WHERE Name = '$volume'", "WQL", wbemFlagReturnImmediately | wbemFlagForwardOnly);

	foreach my $objItem (in $colItems) {
        my $data = ($objItem->{"FreeSpace"} / $objItem->{"Size"}) * 100;
        print_module("Volume_$volume" . "_" . "$name", "generic_data", "$data", "Free disk on $volume");
        return;        
    }
}

# ----------------------------------------------------------------------------
# spare_system_disk
#
# Check free space on volume
# Receives volume name and instance
# ----------------------------------------------------------------------------

sub spare_system_disk ($$) {
    my $name = $_[0];
    my $vol = $_[1];


    if ($vol eq ""){
        return;
    }

    # This is a posix call, should be the same on all systems !
    my $output = `df -kP | grep "$vol\$" | awk '{ print \$5 }' | tr -d "%"`;
    my $disk_space = 100 - $output;
    print_module("Volume_$vol" . "_" . "$name", "generic_data", $disk_space, "% of volume free");
}

# ----------------------------------------------------------------------------
# process_status_unix
#
# Generates a pandora module about the running status of a given process
# ----------------------------------------------------------------------------

sub process_status_unix ($$){
    my $proc = $_[0];
    my $proc_name = $_[1];

    if ($proc eq ""){
        return;
    }

    my $data = trim (`ps aux | grep "$proc" | grep -v grep | wc -l`);
    print_module("Process_$proc_name", "generic_proc", $data, "Status of process $proc");
}


# ----------------------------------------------------------------------------
# process_status_win
#
# Generates a pandora module about the running status of a given process
# ----------------------------------------------------------------------------

sub process_status_win ($$){
    my $proc = $_[0];
    my $proc_name = $_[1];

    if ($proc eq ""){
        return;
    }

	my $computer = "localhost";
	my $objWMIService = Win32::OLE->GetObject("winmgmts:\\\\$computer\\root\\CIMV2") or return;
	my $colItems = $objWMIService->ExecQuery("SELECT * FROM Win32_Process WHERE Caption = '$proc'", "WQL", wbemFlagReturnImmediately | wbemFlagForwardOnly);

	foreach my $objItem (in $colItems) {
	
		if ($objItem->{"Caption"} eq $proc){
            print_module("Process_$proc_name", "generic_proc", 1, "Status of process $proc");
            return;
        } else {
            print_module("Process_$proc_name", "generic_proc", 0, "Status of process $proc");
            return;
        }
    }

    # no matches, process is not running
    print_module("Process_$proc_name", "generic_proc", 0, "Status of process $proc");

}

# ----------------------------------------------------------------------------
# process_mem_win
#
# Generates a Pandora FMS about memory usage of a given process "pepito.exe"
# only works with EXACT names.
# ----------------------------------------------------------------------------

sub process_mem_win ($$){
    my $proc = $_[0];
    my $proc_name = $_[1];

    if ($proc eq ""){
        return;
    }

	my $computer = "localhost";
	my $objWMIService = Win32::OLE->GetObject("winmgmts:\\\\$computer\\root\\CIMV2") or return;
	my $colItems = $objWMIService->ExecQuery("SELECT * FROM Win32_Process WHERE Caption = '$proc'", "WQL", wbemFlagReturnImmediately | wbemFlagForwardOnly);

	foreach my $objItem (in $colItems) {
	
		if ($objItem->{"Caption"} eq $proc){
            print_module("Process_MEM_$proc_name", "generic_data", $objItem->{"WorkingSetSize"}, "Memory in bytes of process $proc");
        } else {
            return;
        }
    }
}

# ----------------------------------------------------------------------------
# process_mem_unix
#
# Generates a Pandora FMS about memory usage of a given process
# ----------------------------------------------------------------------------

sub process_mem_unix ($$){
    my $vol = $_[0];
    my $proc_name = $_[1];

    if ($vol eq ""){
        return;
    }

    my $data = `ps aux | grep "$vol" | grep -v grep | awk '{ print \$6 }'`;
    my @data2 = split ("\n", $data),
    my $tot = 0;

    foreach (@data2){
        $tot = $tot + $_;
    }
    print_module("Proc_MEM_$proc_name", "generic_data", $tot, "Memory used (in bytes) for process $vol");
}

# ----------------------------------------------------------------------------
# process_cpu_unix
#
# Generates a Pandora FMS about memory usage of a given process
# ----------------------------------------------------------------------------
sub process_cpu_unix ($$) {
    my $vol = $_[0];
    my $proc_name = $_[1];

    if ($vol eq ""){
        return;
    }

    my $data = `ps aux | grep "$vol" | grep -v grep | awk '{ print \$3 }'`;
    my @data2 = split ("\n", $data),
    my $tot = 0;

    foreach (@data2){
        $tot = $tot + $_;
    }
    print_module("Proc_CPU_$proc_name", "generic_data", $tot, "CPU (%) used for process $vol");
}

#--------------------------------------------------------------------------------
#--------------------------------------------------------------------------------
# MAIN PROGRAM
# -------------------------------------------------------------------------------
#--------------------------------------------------------------------------------

# Parse external configuration file

# Load config file from command line
if ($#ARGV == -1 ){
        print "I need at least one parameter: Complete path to external configuration file \n";
        exit;
}

# Check for file
if ( ! -f $archivo_cfg ) {
        printf "\n [ERROR] Cannot open configuration file at $archivo_cfg. \n\n";
        exit 1;
}

load_external_setup ($archivo_cfg);

# Check for logparser, if not ready, skip all log check
if ( ! -f $plugin_setup{"logparser"} ) {
	# Create a dummy check module with and advise warning
	if ($log_items > 0) {
		print_module("Error: Log parser not found", "async_string", 0, "Log parser not found, please check your configuration file and set it");
	}
        $log_items =0;
}




# Webchecks 
if ($webcheck_items > 0){
    my $ax;

    for ($ax=0; $ax < $webcheck_items; $ax++){

        my ($name, $host, $port, $url, $string) = split (";",$plugin_setup{"webcheck"}[$ax]);
        http_check ($name, $host, $port, $url, $string);
    }
}

# Check individual defined volumes
if ($volume_items > 0){
    my $ax;

    for ($ax=0; $ax < $volume_items; $ax++){
	my ($name, $volume) = split (";",$plugin_setup{"volume"}[$ax]);
        if ($OS eq "MSWin32"){
            spare_system_disk_win ($name, $volume);
        } else {
            spare_system_disk ($name, $volume);
        }
    }
}

# Check individual defined logs
if ($log_items > 0){
    my $ax;

    for ($ax=0; $ax < $log_items; $ax++){
        my ($logfile, $name, $expression) = split (";",$plugin_setup{"log"}[$ax]);
    
        # Verify proper valid values here or skip
        if (!defined($logfile)){
            next;
        }

        if (!defined($name)){
            next;
        }

        if (!defined($expression)){
            next;
        }

        alert_log ($logfile, $name, $expression);
    }
}


# Check individual defined process
if ($process_items > 0){
    my $ax;

    for ($ax=0; $ax < $process_items; $ax++){

        my ($name, $process) = split (";",$plugin_setup{"process"}[$ax]);

        if ($OS eq "MSWin32") {
            process_status_win ($process, $name);
            process_mem_win    ($process, $name);
        } else {
            process_status_unix ($process, $name);
            process_mem_unix ($process, $name);
            process_cpu_unix ($process, $name);
        }
    }
}

# Apache stats

if ($plugin_setup{"apache_stats"} ne "") {
    my ($name, $host, $port, $url) = split (";",$plugin_setup{"apache_stats"});
    apache_stats ($name, $host, $port, $url);
}


