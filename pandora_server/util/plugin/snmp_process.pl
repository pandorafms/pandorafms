#!/usr/bin/perl
##################################################################################
# SNMP Plugin for Pandora FMS
# (c) Sergio Martin 2010, sergio.martin@artica.es
# (c) 2010 Artica Soluciones Tecnologicas S.L
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; version 2
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
##################################################################################

my $cfg_remote_host = ""; 
my $cfg_password = "";
my $cfg_process = ""; 
my $cfg_type = "";
my $cfg_quiet = "";
my $OID;

use strict;
use Getopt::Std;

# ------------------------------------------------------------------------------------------
# This function show a brief doc.
# ------------------------------------------------------------------------------------------
sub help {
    print "SNMP Plugin for Pandora FMS (c) Artica ST 2008-2010 \n";
    print "Syntax: \n\n";
    print "\t -i <device_ip>\n\t -c <password/snmp_community>\n\t -p <process name>\n\t -t <query type: status/cpu/mem>\n\n";
    print "\tCPU must be defined as generic_data_inc type \n";
    print "\tMEM must be defined as generic_data type \n";
    print "\tStatus must be defined as generic_proc type \n";
    print "\n";
}

# ------------------------------------------------------------------------------------------
# Print an error and exit the program.
# ------------------------------------------------------------------------------------------
sub error {
    if ($cfg_quiet == 0) {
        print (STDERR "[err] $_[0]\n");
    }
    exit 1;
}


# ------------------------------------------------------------------------------------------
# Read configuration from commandline parameters
# ------------------------------------------------------------------------------------------
sub config {
    my %opts;
    my $tmp;

    # Get options
    if (getopts ('i:c:p:t:hq', \%opts) == 0 || defined ($opts{'h'})) {
        help ();
        exit 1;
    }

    # Address
    if (defined ($opts{'i'})) {
        $cfg_remote_host  = $opts{'i'};
        if ($cfg_remote_host !~ /^[a-zA-Z\.]+$/ && ($cfg_remote_host  !~ /^(\d+)\.(\d+)\.(\d+)\.(\d+)$/
            || $1 < 0 || $1 > 255 || $2 < 0 || $2 > 255
            || $3 < 0 || $3 > 255 || $4 < 0 || $4 > 255)) {
            error ("Address $cfg_remote_host  is not valid.");
        }
    }

    # Password
    if (defined ($opts{'c'})) {
        $cfg_password = $opts{'c'};
    }

    # Process name
    if (defined ($opts{'p'})) {
        $cfg_process = $opts{'p'};
    }

    # Type of query
    if (defined ($opts{'t'})) {
        $cfg_type  = $opts{'t'};
        if (($cfg_type ne "status") && ($cfg_type ne "cpu") && ($cfg_type ne "mem")){
            error ("Type $cfg_type is not valid.");
        }
    }

    # Quiet mode
    if (defined ($opts{'q'})) {
        $cfg_quiet = 1;
    }

    if ($cfg_remote_host eq ""){
        print "You need to define remote host to use this plugin";
        help();
        exit;
    }
    
	my $snmpoid_execution = "snmpwalk -Os -c $cfg_password -v 1 $cfg_remote_host hrSWRunName | grep $cfg_process | awk '{print \$1}' | awk -F. '{print \$2}' | tail -1";
        
    $OID = `$snmpoid_execution`;
    
    chomp($OID);
}

# ------------------------------------------------------------------------------------------
# This function get process status
# ------------------------------------------------------------------------------------------

sub get_status {
    my $output;  
    eval {
        my $snmpstatus_execution = "snmpwalk -Os -c $cfg_password -v 1 $cfg_remote_host hrSWRunStatus.$OID 2>/dev/null | awk '{print \$4}' | awk -F'(' '{print \$2}' | awk -F')' '{print \$1}'";

		$output = `$snmpstatus_execution`;
		
		chomp($output);
		
		if($output eq '1') {
			$output = "1\n";
		}
		else {
			$output = "0\n";
		}
    };
    return $output;
}

# ------------------------------------------------------------------------------------------
# This function get CPU consumption
# ------------------------------------------------------------------------------------------

sub get_cpu {
    my $output;  
    eval {    
        my $snmpcpu_execution = "snmpwalk -Os -c $cfg_password -v 1 $cfg_remote_host hrSWRunPerfCPU.$OID 2>/dev/null | awk '{print \$4}'";

		$output = `$snmpcpu_execution`;
		
		if($output eq "") {
			$output = "0\n";
		}
    };
    return $output;
}

# ------------------------------------------------------------------------------------------
# This function get Memory consumption
# ------------------------------------------------------------------------------------------

sub get_memory {
    my $output;  
    eval { 
        my $snmpmemory_execution = "snmpwalk -Os -c $cfg_password -v 1 $cfg_remote_host hrSWRunPerfMem.$OID 2>/dev/null | awk '{print \$4}'";
		
		my $mem = `$snmpmemory_execution`;
		
		chomp($mem);
		$output = $mem;
		
		if($output eq " ") {
			$output = "0\n";
		}
    };
    return $output;
}
# ------------------------------------------------------------------------------------------
# Main program
# ------------------------------------------------------------------------------------------

    config();
    if ($cfg_type eq "status")	{
		print get_status();
	}
    if ($cfg_type eq "mem") {
		print get_memory();
	}
    if ($cfg_type eq "cpu") {
		print get_cpu();
	}
    exit;   
