#!/usr/bin/perl
# Pandora FMS Agent Plugin for Monitoring FTP servers
# Mario Pulido (c) Artica Soluciones Tecnologicas  <info@artica.es> 2012
# v1.0,  18 Jun 2012 
# ------------------------------------------------------------------------

use strict;
use warnings;
use Data::Dumper; 
use Net::FTP;
use Time::HiRes qw ( gettimeofday );

my $archivo_cfg = $ARGV[0];

# Hash with this plugin setup
my %plugin_setup; 

# FLUSH in each IO
$| = 1;

my $version = "v1r1";


# ----------------------------------------------------------------------------
# This cleans DOS-like line and cleans ^M character. VERY Important when you process .conf edited from DOS
# ----------------------------------------------------------------------------

sub parse_dosline ($)
{
    my $str = $_[0];

    $str =~ s/\r//g;
    return $str;
}

sub clean_blank($)
{
        my $input = $_[0];
        $input =~ s/[\s\r\n]*//g;
        return $input;
}

# ----------------------------------------------------------------------------
# print_module
#
# This function return a pandora FMS valid module fiven name, type, value, description 
# ----------------------------------------------------------------------------

sub print_module ($$$$)
{
    my $MODULE_NAME = $_[0];
    my $MODULE_TYPE = $_[1];
    my $MODULE_VALUE = $_[2];
    my $MODULE_DESC = $_[3];

    # If not a string type, remove all blank spaces!    
    if ($MODULE_TYPE !~ m/string/)
    {
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
# load_external_setup
#
# Load external file containing configuration
# ----------------------------------------------------------------------------

sub load_external_setup ($); # Declaration due a recursive call to itself on includes
sub load_external_setup ($)
{

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

    foreach (@config_file)
	{
		$parametro = $_;
		 
        if ($parametro =~ m/^conf\_ftp\_user\s(.*)/i) {
            $plugin_setup{"conf_ftp_user"} = $1;
        }
    
        if ($parametro =~ m/^conf\_ftp\_pass\s(.*)/i) {
            $plugin_setup{"conf_ftp_pass"} = $1;
        }

        if ($parametro =~ m/^conf\_ftp\_host\s(.*)/i) {
            $plugin_setup{"conf_ftp_host"} = $1;
        }
        
        if ($parametro =~ m/^conf\_ftp\_putfile\s(.*)/i) {
			$plugin_setup{"conf_ftp_putfile"} = $1;
		}
		
	    if ($parametro =~ m/^conf\_ftp\_getfile\s(.*)/i) {
			$plugin_setup{"conf_ftp_getfile"} = $1;
		}
		
	    if ($parametro =~ m/^conf\_ftp\_putname\s(.*)/i) {
			$plugin_setup{"conf_ftp_putname"} = $1;
		}
		
	    if ($parametro =~ m/^conf\_ftp\_getname\s(.*)/i) {
			$plugin_setup{"conf_ftp_getname"} = $1;
		}
		
	    if ($parametro =~ m/^conf\_ftp\_compare_file\s(.*)/i) {
			$plugin_setup{"conf_ftp_compare_file"} = $1;
		}
		
	    if ($parametro =~ m/^conf\_local\_comp_file\s(.*)/i) {
			$plugin_setup{"conf_local_comp_file"} = $1;
		}
		
		if ($parametro =~ m/^conf\_local\_downcomp_file\s(.*)/i) {
			$plugin_setup{"conf_local_downcomp_file"} = $1;
		}
		
		if ($parametro =~ m/^conf\_operating\_system\s(.*)/i) {
			$plugin_setup{"conf_operating_system"} = $1;
		}
		if ($parametro =~ m/^conf\_ftp\_compare\s(.*)/i) {
			$plugin_setup{"conf_ftp_compare"} = $1;
		}
		
    }
}


#-------------------------------------------------------------------------
# 
# Main function
#
#--------------------------------------------------------------------------

# Parse external configuration file

# Load config file from command line
if ($#ARGV == -1 )
{
        print "I need at least one parameter: Complete path to external configuration file \n";
        print "\n";
        print "Pandora_Plugin_FTP    Version $version\n";
        exit;
}

# Check for file
if ( ! -f $archivo_cfg ) 
{
        printf "\n [ERROR] Cannot open configuration file at $archivo_cfg. \n\n";
        exit 1;
}

load_external_setup ($archivo_cfg);

#-------------------------------------------------------------------------
# Start session in FTP server
#--------------------------------------------------------------------------

my $ftp = Net::FTP->new($plugin_setup{"conf_ftp_host"}) or die("Unable to connect to server: $!");#Connect FTP server
$ftp->login($plugin_setup{"conf_ftp_user"},$plugin_setup{"conf_ftp_pass"}) or die("Failed Login: $!");# Login at FTP server
#print_module ( "Disp_FTP_$plugin_setup{conf_ftp_host}" , "generic_proc", 1, " Determines whether FTP login to $plugin_setup{conf_ftp_host}  has been successful or not" );
#-------------------------------------------------------------------------
# Returns the module that shows the time and transfer rate.(Upload a file)
#--------------------------------------------------------------------------

	my $clock0 = gettimeofday();
	$ftp->put($plugin_setup{"conf_ftp_putfile"},$plugin_setup{"conf_ftp_putname"});# Upload file at FTP server
	my $clock1 = gettimeofday();
	my $clockd = $clock1 - $clock0;# Calculate upload transfer time
	$ftp->size($plugin_setup{"conf_ftp_putname"});# File size
    my $putrate = $ftp->size($plugin_setup{"conf_ftp_putname"})/$clockd;# Calculate rate transfer
    my $time_puftp=sprintf("%.2f",$clockd);
    my $rate_puftp=sprintf("%.2f",$putrate);

    print_module ( "PUT_file_transfer_time_$plugin_setup{conf_ftp_putname}" , "generic_data" , $time_puftp , " Show the time it takes to upload $plugin_setup{conf_ftp_putname} , at FTP server ");
    print_module ( "PUT_file_transfer_rate_$plugin_setup{conf_ftp_putname}" , "generic_data", $rate_puftp , " Show rate transfer to upload $plugin_setup{conf_ftp_putname} , at FTP server in B/s ");
    
#-------------------------------------------------------------------------
# Returns the module that shows the time and transfer rate (Download a file)
#--------------------------------------------------------------------------   
    
    my $clock2 = gettimeofday();
	$ftp->get($plugin_setup{"conf_ftp_getfile"},$plugin_setup{"conf_ftp_getname"});
	my $clock3 = gettimeofday();
	my $clockg = $clock3 - $clock2;
	$ftp->size($plugin_setup{"conf_ftp_getname"});
    my $getrate = $ftp->size($plugin_setup{"conf_ftp_getname"})/$clockg;
    my $time_getftp=sprintf("%.2f",$clockg);
    my $rate_getftp=sprintf("%.2f",$getrate);
    
    print_module ( "GET_file_transfer_time_$plugin_setup{conf_ftp_getfile}" , "generic_data" , $time_getftp , " Show the time it takes to download $plugin_setup{conf_ftp_getfile} , at FTP server " );
    print_module ( "GET_file_transfer_rate_$plugin_setup{conf_ftp_getfile}" , "generic_data", $rate_getftp, " Show rate transfer to download $plugin_setup{conf_ftp_getfile} , at FTP server in B/s " );

#-------------------------------------------------------------------------
# Returns the module that compares file changes between a server_file and local_file
#--------------------------------------------------------------------------  
    
my $compare_unix;
my $compare_wdos;

# Download file server   
$ftp->get($plugin_setup{"conf_ftp_compare_file"},$plugin_setup{"conf_local_downcomp_file"});

# Compare file between server_file and local_file
 if ( $plugin_setup{"conf_operating_system"} eq "Unix"){
		$compare_unix = `cmp $plugin_setup{"conf_local_downcomp_file"} $plugin_setup{"conf_local_comp_file"} ; echo \$?`;
		if ( $compare_unix == 0){
		    print_module ( "FTP_Maching_files" , "generic_proc", 1 , " Compare a server file and a local file " );
		}
		else{
			print_module ( "FTP_Maching_files" , "generic_proc", 0 , " Compare a server file and a local file $plugin_setup{conf_ftp_compare_file} " );
			if ( $plugin_setup{"conf_ftp_compare"} eq "write" ){
				my $write_option = `mv $plugin_setup{conf_local_downcomp_file} $plugin_setup{conf_local_comp_file}`;
	 }	
		}
}
	if ( $plugin_setup{"conf_operating_system"} eq "Windows"){
		my $compare_wdos = `fc $plugin_setup{"conf_local_downcomp_file"} $plugin_setup{"conf_local_comp_file"} > diff.txt`;
		my $archivo = "diff.txt";
		my $peso = -s $archivo;
		if ($peso > 79){
			print_module ( "FTP_Maching_files" , "generic_proc", 0 , " Compare a server file and a local file $plugin_setup{conf_ftp_compare_file} " );
		    if ( $plugin_setup{"conf_ftp_compare"} eq "write" ){
				my $write_option = `move /Y $plugin_setup{conf_local_downcomp_file} $plugin_setup{conf_local_comp_file}`;
			}	
		}else{
			print_module ( "FTP_Maching_files" , "generic_proc", 1 , " Compare a server file and a local file " )
			}
		
		    
		}
	
	
 



