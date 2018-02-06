#!/usr/bin/perl
# Pandora FMS Agent Plugin for MongoDB
# (c) Artica Soluciones Tecnologicas  <info@artica.es> 2012
# v1.0, 18 Apr 2012 
# ------------------------------------------------------------------------

use strict;
use warnings;
use Data::Dumper; 

use IO::Socket::INET;

# OS and OS version
my $OS = $^O;

# Store original PATH
my $ORIGINAL_PATH = $ENV{'PATH'};

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
my %mongo_resultset; # This stores mongo results
my $archivo_cfg = $ARGV[0];

my $volume_items = 0;
my $log_items = 0;
my $process_items = 0;
my $mongo_items = 0;
my $stats_items = 0;
my $OS_NAME = `uname -s`;
my $hostname = `hostname | tr -d "\n"`;


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
    print "<name><![CDATA[$MODULE_NAME]]></name>\n";
    print "<type>$MODULE_TYPE</type>\n";
    print "<data><![CDATA[$MODULE_VALUE]]></data>\n";
    print "<description><![CDATA[$MODULE_DESC]]></description>\n";
    print "</module>\n";

}

# ----------------------------------------------------------------------------
# load_mongostat_result
#
# Load temporal mongostat result file containing mongostat stats
# ----------------------------------------------------------------------------

my $resultfile="/tmp/mongo_results.log";
my $resultfilestats="/tmp/mongo_resultstats.log";

sub load_mongo_result ($);

sub load_mongo_result ($){
	
	my $mongo_result = $_[0];
	my $buffer_line;
	my @results;
	my $parametro ="";
	
	if (! open (CFG, "< $mongo_result")) {
		print "[ERROR] Error accessing mongo results $mongo_result: $!.\n";
		exit 1;
	}
	
	while (<CFG>){
        $buffer_line = parse_dosline ($_);
        # Parse configuration file, this is specially difficult because can contain SQL code, with many things
        if ($buffer_line !~ /^\#/){  # begins with anything except # (for commenting)
			if ($buffer_line =~ m/(.+)\s(.*)/){
				push @results, $buffer_line;
            }
        }
    }
    
    close (CFG);
    
    if ($mongo_result eq $resultfilestats) {
		
		foreach (@results){
			$parametro = $_;

			$mongo_resultset{"checker"}->[$stats_items] = `echo "$parametro" | awk '{print \$15}' | tr -d "\n"`;

			my $checker = $mongo_resultset{"checker"}->[$stats_items];

			if (!$checker) {

				$mongo_resultset{"dbinserts"}->[$stats_items] = `echo "$parametro" | awk '{print \$1}' | tr -d "\n"`;
				$mongo_resultset{"dbqueries"}->[$stats_items] = `echo "$parametro" | awk '{print \$2}' | tr -d "\n"`;
				$mongo_resultset{"dbupdates"}->[$stats_items] = `echo "$parametro" | awk '{print \$3}' | tr -d "\n"`;
				$mongo_resultset{"dbdeletes"}->[$stats_items] = `echo "$parametro" | awk '{print \$4}' | tr -d "\n"`;
				$mongo_resultset{"dbgetmores"}->[$stats_items] = `echo "$parametro" | awk '{print \$5}' | tr -d "\n"`;
				$mongo_resultset{"dbcommands"}->[$stats_items] = `echo "$parametro" | awk '{print \$6}' | tr -d "\n"`;
				$mongo_resultset{"dbpagefaults"}->[$stats_items] = `echo "$parametro" | awk '{print \$9}' | tr -d "\n"`;
				$mongo_resultset{"dbnetworktrafficinbits"}->[$stats_items] = `echo "$parametro" | awk '{print \$10}' | rev | cut -c2- | rev | tr -d "\n"`;
				$mongo_resultset{"dbnetworktrafficinbitunit"}->[$stats_items] = `echo "$parametro" | awk '{print \$10}' | rev | cut -c1 | tr -d "\n"`;
				$mongo_resultset{"dbnetworktrafficoutbits"}->[$stats_items] = `echo "$parametro" | awk '{print \$11}' | rev | cut -c2- | rev | tr -d "\n"`;
				$mongo_resultset{"dbnetworktrafficoutbitunit"}->[$stats_items] = `echo "$parametro" | awk '{print \$11}' | rev | cut -c1 | tr -d "\n"`;
				$mongo_resultset{"dbopenconnections"}->[$stats_items] = `echo "$parametro" | awk '{print \$12}' | tr -d "\n"`;

			}

			else {
			
				$mongo_resultset{"dbinserts"}->[$stats_items] = `echo "$parametro" | awk '{print \$1}' | tr -d "\n"`;
				$mongo_resultset{"dbqueries"}->[$stats_items] = `echo "$parametro" | awk '{print \$2}' | tr -d "\n"`;
				$mongo_resultset{"dbupdates"}->[$stats_items] = `echo "$parametro" | awk '{print \$3}' | tr -d "\n"`;
				$mongo_resultset{"dbdeletes"}->[$stats_items] = `echo "$parametro" | awk '{print \$4}' | tr -d "\n"`;
				$mongo_resultset{"dbgetmores"}->[$stats_items] = `echo "$parametro" | awk '{print \$5}' | tr -d "\n"`;
				$mongo_resultset{"dbcommands"}->[$stats_items] = `echo "$parametro" | awk '{print \$6}' | tr -d "\n"`;
				$mongo_resultset{"dbflushes"}->[$stats_items] = `echo "$parametro" | awk '{print \$7}' | tr -d "\n"`;
				$mongo_resultset{"dbpagefaults"}->[$stats_items] = `echo "$parametro" | awk '{print \$12}' | tr -d "\n"`;
				$mongo_resultset{"dblockpercent"}->[$stats_items] = `echo "$parametro" | awk '{print \$13}' | awk -F"\:" '{print \$2}' | tr -d "\%" | tr -d "\n"`;
				$mongo_resultset{"dbbttreepagemissedpercent"}->[$stats_items] = `echo "$parametro" | awk '{print \$14}' | tr -d "\%" | tr -d "\n"`;
				$mongo_resultset{"dbclientreadqueuelength"}->[$stats_items] = `echo "$parametro" | awk '{print \$15}' | awk -F"\|" '{print \$1}' | tr -d "\n"`;
				$mongo_resultset{"dbclientwritequeuelength"}->[$stats_items] = `echo "$parametro" | awk '{print \$15}' | awk -F"\|" '{print \$2}' | tr -d "\n"`;
				$mongo_resultset{"dbactivereadingclients"}->[$stats_items] = `echo "$parametro" | awk '{print \$16}' | awk -F"\|" '{print \$1}' | tr -d "\n"`;
				$mongo_resultset{"dbactivewritingclients"}->[$stats_items] = `echo "$parametro" | awk '{print \$16}' | awk -F"\|" '{print \$1}' | tr -d "\n"`;
				$mongo_resultset{"dbnetworktrafficinbits"}->[$stats_items] = `echo "$parametro" | awk '{print \$17}' | rev | cut -c2- | rev | tr -d "\n"`;
				$mongo_resultset{"dbnetworktrafficinbitunit"}->[$stats_items] = `echo "$parametro" | awk '{print \$17}' | rev | cut -c1 | tr -d "\n"`;
				$mongo_resultset{"dbnetworktrafficoutbits"}->[$stats_items] = `echo "$parametro" | awk '{print \$18}' | rev | cut -c2- | rev | tr -d "\n"`;
				$mongo_resultset{"dbnetworktrafficoutbitunit"}->[$stats_items] = `echo "$parametro" | awk '{print \$18}' | rev | cut -c1 | tr -d "\n"`;
				$mongo_resultset{"dbopenconnections"}->[$stats_items] = `echo "$parametro" | awk '{print \$19}' | tr -d "\n"`;

			}
			
			$stats_items++;
			
		}
		
	}

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

    $plugin_setup{"mongostat"}="mongostat";
    $plugin_setup{"tmconfig"}="tmconfig";
    $plugin_setup{"logparser"}="grep_log";
#	$plugin_setup{"mongostat"} = ""; NOT USED ANYMORE

    foreach (@config_file){
        $parametro = $_;
        
        if ($parametro =~ m/^instance\s(.*)/i) {
			$plugin_setup{"mongoinstance"}=$1;
		}
        
        if ($parametro =~ m/export PATH=(.*)/i) {
			$ENV{'PATH'} = $1;
		}
		
		if ($parametro =~ m/export LD_LIBRARY_PATH=(.*)/i) {
			$ENV{'LD_LIBRARY_PATH'} = $1;
		}
		
		if ($parametro =~ m/export MONGOCONFIG=(.*)/i) {
			$ENV{'MONGOCONFIG'} = $1;
		}
		
		if ($parametro =~ m/export NLSPATH=(.*)/i) {
			$ENV{'NLSPATH'} = $1;
		}
		
		if ($parametro =~ m/export MONGODIR=(.*)/i) {
			$ENV{'MONGODIR'} = $1;
		}
		
		if ($parametro =~ m/export APPDIR=(.*)/i) {
			$ENV{'APPDIR'} = $1;
		}
		
		if ($parametro =~ m/export LANG=(.*)/i) {
			$ENV{'LANG'} = $1;
		}
        
        if ($parametro =~ m/^include\s(.*)/i) {
            load_external_setup ($1);
        }

        if ($parametro =~ m/^logparser\s(.*)/i) {
            $plugin_setup{"logparser"}=$1;

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
        
         # Processcheck
        if ($parametro =~ m/^process\s(.*)/i) {
            $plugin_setup{"process"}->[$process_items]=$1;
            $process_items++;
        }

		# mongodb stats
		if ($parametro =~ m/^mongodb_stats\s(.*)/i) {
			$plugin_setup{"mongodb_stats"}->[$mongo_items]=$1;
			$mongo_items++;
		}
    }
}

# ----------------------------------------------------------------------------
# mongostat 
#
# This function uses mongostat from Mongo to get information
# Given Command (mongostat), check type and monitored object (optional)
# ----------------------------------------------------------------------------

sub mongostat ($$$$$){
	my $cmdname = $_[0];
	my $checktype = $_[1];
	my $objname = $_[2];
	my $host = $_[3];
	my $port = $_[4];
	
	#Mongo instance
	
	my $mongoinstance = $plugin_setup{"mongoinstance"};
	
	if (!defined $mongoinstance) {
		
		$mongoinstance = $hostname;
		
	}
	
	# Call to mongostat
	
	my $mongostat_call;
	
	if ($cmdname) {
		
		$mongostat_call = $cmdname;
			
	}
	
	else {
	
		$mongostat_call = $plugin_setup{"mongostat"};
		
	}
	
	if ($checktype eq "check_dbstats") {
		
		$stats_items = 0;
		my $tmadmpqc_cmd = `$mongostat_call -n1 --host $host --port $port --all | tail -1 > "$resultfilestats"`;
		
		load_mongo_result ($resultfilestats);
		
		if ($stats_items > 0) {
			my $ax;
			my $cx;
			my $value = 0;
			my $novalue = 0;
			my $realchecker;
	
			my $dbinserts = 0;
			my $dbqueries = 0;
			my $dbupdates = 0;
			my $dbdeletes = 0;
			my $dbgetmores = 0;
			my $dbcommands = 0;
			my $dbflushes = 0;
			my $dbpagefaults = 0;
			my $dblockpercent = 0;
			my $dbbttreepagemissedpercent = 0;
			my $dbclientreadqueuelength = 0;
			my $dbclientwritequeuelength = 0;
			my $dbactivereadingclients = 0;
			my $dbactivewritingclients = 0;
			my $dbnetworktrafficinbits = 0;
			my $dbnetworktrafficinbitunit;
			my $dbnetworktrafficoutbits = 0;
			my $dbnetworktrafficoutbitunit;
			my $dbopenconnections = 0;
	
			for ($ax=0; $ax < $stats_items + 0; $ax++){
								
				my $bx = $ax - 1;

				$realchecker = $mongo_resultset{"checker"}[$ax];

				$dbinserts = $mongo_resultset{"dbinserts"}[$ax];
				$dbqueries = $mongo_resultset{"dbqueries"}[$ax];
				$dbupdates = $mongo_resultset{"dbupdates"}[$ax];
				$dbdeletes = $mongo_resultset{"dbdeletes"}[$ax];
				$dbgetmores = $mongo_resultset{"dbgetmores"}[$ax];
				$dbcommands = $mongo_resultset{"dbcommands"}[$ax];
				$dbflushes = $mongo_resultset{"dbflushes"}[$ax];
				$dbpagefaults = $mongo_resultset{"dbpagefaults"}[$ax];
				$dbbttreepagemissedpercent = $mongo_resultset{"dbbttreepagemissedpercent"}[$ax];
				$dbclientreadqueuelength = $mongo_resultset{"dbclientreadqueuelength"}[$ax];
				$dbclientwritequeuelength = $mongo_resultset{"dbclientwritequeuelength"}[$ax];
				$dbactivereadingclients = $mongo_resultset{"dbactivereadingclients"}[$ax];
				$dbactivewritingclients = $mongo_resultset{"dbactivewritingclients"}[$ax];
				$dbnetworktrafficinbits = $mongo_resultset{"dbnetworktrafficinbits"}[$ax];
				$dbnetworktrafficinbitunit = $mongo_resultset{"dbnetworktrafficinbitunit"}[$ax];
				$dbnetworktrafficoutbits = $mongo_resultset{"dbnetworktrafficoutbits"}[$ax];
				$dbnetworktrafficoutbitunit = $mongo_resultset{"dbnetworktrafficoutbitunit"}[$ax];
				$dbopenconnections = $mongo_resultset{"dbopenconnections"}[$ax];

				if ($dbnetworktrafficinbitunit eq "k") {

					$dbnetworktrafficinbits = $dbnetworktrafficinbits * 1024;

				}

				elsif ($dbnetworktrafficinbitunit eq "m") {

					$dbnetworktrafficinbits = $dbnetworktrafficinbits * 1024 * 1024;

				}

				elsif ($dbnetworktrafficinbitunit eq "g") {

					$dbnetworktrafficinbits = $dbnetworktrafficinbits * 1024 * 1024 * 1024;

				}

				elsif ($dbnetworktrafficinbitunit eq "t") {

					$dbnetworktrafficinbits = $dbnetworktrafficinbits * 1024 * 1024 * 1024 * 1024;

				}

				if ($dbnetworktrafficoutbitunit eq "k") {

					$dbnetworktrafficoutbits = $dbnetworktrafficoutbits * 1024;

				}

				elsif ($dbnetworktrafficoutbitunit eq "m") {

					$dbnetworktrafficoutbits = $dbnetworktrafficoutbits * 1024 * 1024;

				}

				elsif ($dbnetworktrafficoutbitunit eq "g") {

					$dbnetworktrafficoutbits = $dbnetworktrafficoutbits * 1024 * 1024 * 1024;

				}

				elsif ($dbnetworktrafficoutbitunit eq "t") {

					$dbnetworktrafficoutbits = $dbnetworktrafficoutbits * 1024 * 1024 * 1024 * 1024;

				}
				
			}
		
		if ($objname eq "") {
	
			print_module("MongoDB_Inserts_" . "$host" . ":" . "$port", "generic_data", $dbinserts, "DB Inserts per second for $host" . ":" . "$port");
			
			print_module("MongoDB_Queries_" . "$host" . ":" . "$port", "generic_data", $dbqueries, "DB Queries per second for $host" . ":" . "$port");
				
			print_module("MongoDB_Updates_" . "$host" . ":" . "$port", "generic_data", $dbupdates, "DB Updates per second for $host" . ":" . "$port");

			print_module("MongoDB_Deletes_" . "$host" . ":" . "$port", "generic_data", $dbdeletes, "DB Deletes per second for $host" . ":" . "$port");
			
			print_module("MongoDB_Getmores_" . "$host" . ":" . "$port", "generic_data", $dbgetmores, "DB Getmore operations per second for $host" . ":" . "$port");
				
			print_module("MongoDB_Commands_" . "$host" . ":" . "$port", "generic_data", $dbcommands, "DB Command operations per second for $host" . ":" . "$port");

			print_module("MongoDB_Flushes_" . "$host" . ":" . "$port", "generic_data", $dbflushes, "DB Fsync Flushes per second for $host" . ":" . "$port");
			
			print_module("MongoDB_PageFaults_" . "$host" . ":" . "$port", "generic_data", $dbpagefaults, "DB Page faults per second for $host" . ":" . "$port");
				
			print_module("MongoDB_IdxMiss_" . "$host" . ":" . "$port", "generic_data", $dbbttreepagemissedpercent, "DB bttree page missed percentage for $host" . ":" . "$port");

			print_module("MongoDB_ClientReadQueueLength_" . "$host" . ":" . "$port", "generic_data", $dbclientreadqueuelength, "DB Client read queue length for $host" . ":" . "$port");
				
			print_module("MongoDB_ClientWriteQueueLength_" . "$host" . ":" . "$port", "generic_data", $dbclientwritequeuelength, "DB Client write queue length for $host" . ":" . "$port");

			print_module("MongoDB_ActiveClientsReading_" . "$host" . ":" . "$port", "generic_data", $dbactivereadingclients, "DB Active clients reading for $host" . ":" . "$port");
				
			print_module("MongoDB_ActiveClientsWriting_" . "$host" . ":" . "$port", "generic_data", $dbactivewritingclients, "DB Active clients writing for $host" . ":" . "$port");

			print_module("MongoDB_NetworkTrafficInBits_" . "$host" . ":" . "$port", "generic_data", $dbnetworktrafficinbits, "DB Network traffic in bits for $host" . ":" . "$port");
				
			print_module("MongoDB_NetworkTrafficOutBits_" . "$host" . ":" . "$port", "generic_data", $dbnetworktrafficoutbits, "DB Network traffic out bits for $host" . ":" . "$port");

			print_module("MongoDB_OpenConns_" . "$host" . ":" . "$port", "generic_data", $dbopenconnections, "DB Open connections for $host" . ":" . "$port");
				
			}

			elsif ($objname eq "shardctrl") {
	
			print_module("MongoDB_Inserts_" . "$host" . ":" . "$port", "generic_data", $dbinserts, "DB Inserts per second for $host" . ":" . "$port");
			
			print_module("MongoDB_Queries_" . "$host" . ":" . "$port", "generic_data", $dbqueries, "DB Queries per second for $host" . ":" . "$port");
				
			print_module("MongoDB_Updates_" . "$host" . ":" . "$port", "generic_data", $dbupdates, "DB Updates per second for $host" . ":" . "$port");

			print_module("MongoDB_Deletes_" . "$host" . ":" . "$port", "generic_data", $dbdeletes, "DB Deletes per second for $host" . ":" . "$port");
			
			print_module("MongoDB_Getmores_" . "$host" . ":" . "$port", "generic_data", $dbgetmores, "DB Getmore operations per second for $host" . ":" . "$port");
				
			print_module("MongoDB_Commands_" . "$host" . ":" . "$port", "generic_data", $dbcommands, "DB Command operations per second for $host" . ":" . "$port");
			
			print_module("MongoDB_PageFaults_" . "$host" . ":" . "$port", "generic_data", $dbpagefaults, "DB Page faults per second for $host" . ":" . "$port");

			print_module("MongoDB_NetworkTrafficInBits_" . "$host" . ":" . "$port", "generic_data", $dbnetworktrafficinbits, "DB Network traffic in bits for $host" . ":" . "$port");
				
			print_module("MongoDB_NetworkTrafficOutBits_" . "$host" . ":" . "$port", "generic_data", $dbnetworktrafficoutbits, "DB Network traffic out bits for $host" . ":" . "$port");

			print_module("MongoDB_OpenConns_" . "$host" . ":" . "$port", "generic_data", $dbopenconnections, "DB Open connections for $host" . ":" . "$port");
				
			}
			
		}
		
	}

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
        print_module("Mongo_" . "$name" . "_Volume_" . "$volume", "generic_data", "$data", "Free disk on $volume (%)");
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
    
    if ( $OS_NAME eq "SunOS\n" ) {
		my $output = `/usr/xpg4/bin/df -kP | grep "$vol\$" | awk '{ print \$5 }' | tr -d "%"`;
		my $disk_space = $output - 0;
		print_module("Mongo_" . "$name" . "_Volume_" . "$vol", "generic_data", $disk_space, "% of volume occupied on $vol (%)");
	} else {
		my $output = `df -kP | grep "$vol\$" | awk '{ print \$5 }' | tr -d "%"`;
		my $disk_space = $output - 0;
		print_module("Mongo_" . "$name" . "_Volume_" . "$vol", "generic_data", $disk_space, "% of volume occupied on $vol (%)");
	}
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

	if ( $OS_NAME eq "SunOS\n" ) {
		my $data = trim (`/usr/ucb/ps -aguxwwww | grep "$proc" | grep -v grep | wc -l | awk '{print \$1}`);
		print_module("Mongo_" . "$proc_name" . "_Process_" . "$proc", "generic_proc", $data, "Status of process $proc");
	} else {
		my $data = trim (`ps aux | grep "$proc" | grep -v grep | wc -l`);
		print_module("Mongo_" . "$proc_name" . "_Process_" . "$proc", "generic_proc", $data, "Status of process $proc");
	}
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
            print_module("Mongo_" . "$proc_name" . "_Process_" . "$proc", "generic_proc", 1, "Status of process $proc");
            return;
        } else {
            print_module("Mongo_" . "$proc_name" . "_Process_" . "$proc", "generic_proc", 0, "Status of process $proc");
            return;
        }
    }

    # no matches, process is not running
    print_module("Mongo_" . "$proc_name" . "_Process_" . "$proc", "generic_proc", 0, "Status of process $proc");

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
            print_module("Mongo_" . "$proc_name" . "_Proc_MEM_" . "$proc", "generic_data", $objItem->{"WorkingSetSize"}, "Memory in bytes of process $proc (B)");
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

	if ( $OS_NAME eq "SunOS\n" ) {
		my $data = `/usr/ucb/ps -aguxwwww | grep "$vol" | grep -v grep | awk '{ print \$4 }'`;
		my @data2 = split ("\n", $data),
		my $tot = 0;

		foreach (@data2){
			$tot = $tot + $_;
		}
		print_module("Mongo_" . "$proc_name" . "_Proc_MEM_" . "$vol", "generic_data", $tot, "Memory used (%) for process $vol (%)");
	} else {
		my $data = `ps aux | grep "$vol" | grep -v grep | awk '{ print \$6 }'`;
		my @data2 = split ("\n", $data),
		my $tot = 0;

		foreach (@data2){
			$tot = $tot + $_;
		}
		print_module("Mongo_" . "$proc_name" . "_Proc_MEM_" . "$vol", "generic_data", $tot, "Memory used (in bytes) for process $vol (%)");
	}
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
	if ( $OS_NAME eq "SunOS\n" ) {
		my $data = `/usr/ucb/ps -aguxwwww | grep "$vol" | grep -v grep | awk '{ print \$3 }'`;
		my @data2 = split ("\n", $data),
		my $tot = 0;

		foreach (@data2){
			$tot = $tot + $_;
		}
		print_module("Mongo_" . "$proc_name" . "_Proc_CPU_" . "$vol", "generic_data", $tot, "CPU (%) used for process $vol (%)");
	} else {
		my $data = `ps aux | grep "$vol" | grep -v grep | awk '{ print \$3 }'`;
		my @data2 = split ("\n", $data),
		my $tot = 0;

		foreach (@data2){
			$tot = $tot + $_;
		}
		print_module("Mongo_" . "$proc_name" . "_Proc_CPU_" . "$vol", "generic_data", $tot, "CPU (%) used for process $vol (%)");
	}
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

# Mongo stats

if ($mongo_items > 0) {
	my $ax;
	
	for ($ax=0; $ax < $mongo_items; $ax++){
		my ($cmdname, $checktype, $objname, $host, $port) = split (";",$plugin_setup{"mongodb_stats"}[$ax]);
		mongostat ($cmdname, $checktype, $objname, $host, $port);
	}
}
