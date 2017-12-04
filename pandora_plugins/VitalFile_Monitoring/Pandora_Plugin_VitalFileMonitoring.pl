#!/usr/bin/perl
# Pandora FMS Agent Plugin for Tuxedo
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
my %ls_resultset; # This stores tuxedo results
my $archivo_ls = $ARGV[0];

my $OS_NAME = `uname -s | tr -d "\n"`;
my $hostname = `hostname | tr -d "\n"`;
my $filecounter = 0;
my $conffilecounter = 0;


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
    print "<name><![CDATA[$MODULE_NAME\n";
    print "]]></name>\n";
    print "<type>$MODULE_TYPE</type>\n";
    print "<data><![CDATA[$MODULE_VALUE]]></data>\n";
    print "<description><![CDATA[$MODULE_DESC\n";
    print "]]></description>\n";
    print "</module>\n";

}

# ----------------------------------------------------------------------------
# load_ls_result (DEPRECATED)
#
# Load temporal ls result file containing ls stats
# ----------------------------------------------------------------------------

my $resultfile="/tmp/ls_results.log";

sub load_ls_result ($);

sub load_ls_result ($){
	
	my $ls_result = $_[0];
	my $buffer_line;
	my @results;
	my $parametro ="";
	
	if (! open (CFG, "< $ls_result")) {
		print "[ERROR] Error accessing ls results $ls_result: $!.\n";
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
    
    foreach (@results){
        $parametro = $_;
        
        $ls_resultset{"perms"}[$filecounter] = `echo "$parametro" | awk '{print \$1}' | tr -d "\n"`;
        
        if (($ls_resultset{"perms"}[$filecounter] eq "ls:") || ($parametro =~ m/not found.*/)) {
			
			$ls_resultset{"filename"}[$filecounter] = "";
			$ls_resultset{"filenameerror"}[$filecounter] = `echo "$parametro" | awk -F: '{print \$2}' | awk '{print \$NF}' | tr -d "\n"`;;
			$ls_resultset{"fileowner"}[$filecounter] = "";
			$ls_resultset{"filegroup"}[$filecounter] = "";
			$ls_resultset{"filesize"}[$filecounter] = "";
			$ls_resultset{"modified"}[$filecounter] = "";
			$filecounter++;
        
		} else {
			
			$ls_resultset{"filenameerror"}[$filecounter] = "";
			$ls_resultset{"filename"}[$filecounter] = `echo "$parametro" | awk '{print \$NF}' | tr -d "\n"`;
			$ls_resultset{"fileowner"}[$filecounter] = `echo "$parametro" | awk '{print \$3}' | tr -d "\n"`;
			$ls_resultset{"filegroup"}[$filecounter] = `echo "$parametro" | awk '{print \$4}' | tr -d "\n"`;
			$ls_resultset{"filesize"}[$filecounter] = `echo "$parametro" | awk '{print \$5}' | tr -d "\n"`;
			$ls_resultset{"modified"}[$filecounter] = `echo "$parametro" | awk '{print \$6" "\$7}' | tr -d "\n"`;
			$filecounter++;
			
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

    my $archivo_ls = $_[0];
    my $buffer_line;
    my @config_file;
    my $parametro = "";

    # Collect items from config file and put in an array
    if (! open (CFG, "< $archivo_ls")) {
            print "[ERROR] Error opening list file $archivo_ls: $!.\n";
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
    
    foreach (@config_file){
        $parametro = $_;
        
        $ls_resultset{"conffilename"}[$conffilecounter] = $parametro;
		$conffilecounter++;
			
    }

    # Some plugin setup default options

    $plugin_setup{"ls"}="ls";

}

# ----------------------------------------------------------------------------
# ls_stats 
#
# This function uses ls, greps and awks to get information about each file status
# Given Command (tmadmin or tmconfig), check type and monitored object (optional)
# ----------------------------------------------------------------------------

sub ls_stats {

	# Call to ls
	
	my $ls_call = $plugin_setup{"ls"};
		
#	my $stringlist = `cat $archivo_ls | grep -ve ^# | tr -s "\n" " "`;
	
#	my $lscmd = `$ls_call -l $stringlist 2> $resultfile >> $resultfile`;
#	my $lscmdb = `$ls_call -l $stringlist 2>> $resultfile`;
	
#	load_ls_result ($resultfile);
	
	if ($conffilecounter > 0) {
		my $ax;
		my $filename;
		my $errfilename;
			
		for ($ax=0; $ax < $conffilecounter + 0; $ax++){
			
			$filename = $ls_resultset{"conffilename"}[$ax];
			
			$errfilename = `echo "$filename" | tr -d "\n"`;
			
			my $lscmd = `$ls_call -l $errfilename 2> /dev/null`;
			
			if ((!defined $lscmd) || ($lscmd eq "")) {
				print_module("VitalFileExists_" . $errfilename, "generic_proc", "0", "File $errfilename does not exist at $hostname");
				
			}
			
			else {			
			
				$ls_resultset{"perms"}[$ax] = `echo "$lscmd" | awk '{print \$1}' | tr -d "\n"`;
				$ls_resultset{"filename"}[$ax] = `echo "$lscmd" | awk '{print \$NF}' | tr -d "\n"`;
				$ls_resultset{"fileowner"}[$ax] = `echo "$lscmd" | awk '{print \$3}' | tr -d "\n"`;
				$ls_resultset{"filegroup"}[$ax] = `echo "$lscmd" | awk '{print \$4}' | tr -d "\n"`;
				$ls_resultset{"filesize"}[$ax] = `echo "$lscmd" | awk '{print \$5}' | tr -d "\n"`;
				
				if ($OS_NAME eq "Linux") {
					$ls_resultset{"modified"}[$ax] = `echo "$lscmd" | awk '{print \$6" "\$7}' | tr -d "\n"`;
				}
				elsif (($OS_NAME eq "HP-UX") || ($OS_NAME eq "AIX") || ($OS_NAME eq "SunOS")){
					$ls_resultset{"modified"}[$ax] = `echo "$lscmd" | awk '{print \$6" "\$7" "\$8}' | tr -d "\n"`;
				}
				
				if (($ls_resultset{"filename"}[$ax] ne "") && ($ls_resultset{"filename"}[$ax] =~ m/($errfilename).*/)) {
					print_module("VitalFileExists_" . $ls_resultset{"filename"}[$ax], "generic_proc", "1", "File " . $ls_resultset{"filename"}[$ax] . " exists at $hostname");
				}
				
				if ($ls_resultset{"filesize"}[$ax] ne "") {		
					print_module("VitalFileSize_" . $ls_resultset{"filename"}[$ax], "generic_data", $ls_resultset{"filesize"}[$ax], "Current file size in bytes for " . $ls_resultset{"filename"}[$ax] . " at $hostname");
				}
				
				if (($ls_resultset{"modified"}[$ax] ne "") || (defined $lscmd)) {		
					print_module("VitalFileModificationDate_" . $ls_resultset{"filename"}[$ax], "generic_data_string", $ls_resultset{"modified"}[$ax], "Last modification date for " . $ls_resultset{"filename"}[$ax] . " at $hostname");
				}
				
				if ($ls_resultset{"fileowner"}[$ax] ne "") {		
					print_module("VitalFileOwner_" . $ls_resultset{"filename"}[$ax], "generic_data_string", $ls_resultset{"fileowner"}[$ax], "File owner for " . $ls_resultset{"filename"}[$ax] . " at $hostname");
				}
				
				if ($ls_resultset{"filegroup"}[$ax] ne "") {		
					print_module("VitalFileGroup_" . $ls_resultset{"filename"}[$ax], "generic_data_string", $ls_resultset{"filegroup"}[$ax], "File group for " . $ls_resultset{"filename"}[$ax] . " at $hostname");
				}
				
				if ($ls_resultset{"perms"}[$ax] ne "") {		
					print_module("VitalFilePerms_" . $ls_resultset{"filename"}[$ax], "generic_data_string", $ls_resultset{"perms"}[$ax], "File perms for " . $ls_resultset{"filename"}[$ax] . " at $hostname");
				}
				
			}
			
		}
		
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
        print "I need at least one parameter: Complete path to external list file \n";
        exit;
}

# Check for file
if ( ! -f $archivo_ls ) {
        printf "\n [ERROR] Cannot open list file at $archivo_ls. \n\n";
        exit 1;
}

load_external_setup ($archivo_ls);

# Check file status

ls_stats;
