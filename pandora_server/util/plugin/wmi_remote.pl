#!/usr/bin/perl
#---------------------------------------------------------------------------
# WMI remote plugin 
# Depending on the configuration returns the result of these modules:
# - % Memory Use
# - % CPU Use
# - % Disk Use
#
# Artica ST 
# Copyright (C) 2013 mario.pulido@artica.es
#
# License: GPLv2+
#---------------------------------------------------------------------------
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# GPL License: http://www.gnu.org/licenses/gpl.txt
#---------------------------------------------------------------------------

use strict;
use Getopt::Std;

my $VERSION = 'v1r1';

#-----------------------------------------------------------------------------
# HELP
#-----------------------------------------------------------------------------

if ($#ARGV == -1 )
{
		print "-H, --host=STRING\n";
		print "\tHost IP\n";
		print "-U, --user=STRING\n";
		print "\tWMI User\n";
		print "-P, --password=STRING\n";
		print "\tWMI Password\n";
		print "-m, --module=STRING\n";
		print "\tDefine module (memuse|diskuse|cpuload) \n";
		print "-d, --disk=STRING\n";
		print "\tDefine disk name (C:, D: in Windows) or mount point (Linux)(only in diskuse module)\n";
		print "\n";
        print "Example of use \n";
        print "perl swmi_remoto.pl -H host -U user -P password -m (memuse|diskuse|cpuload) [-p process -d disk] \n";
        print "Version=$VERSION";
        exit;
}

my ( $host, $user, $pass, $module, $disk ) = &options;

#-------------------------------------------------------------------------------------
# OPTIONS
#-------------------------------------------------------------------------------------

sub options {

    # Get and check args
    my %opts;
    getopt( 'HUPmd', \%opts );
	$opts{"H"} = 0   unless ( exists( $opts{"H"} ) );
	$opts{"U"} = 0   unless ( exists( $opts{"U"} ) );
	$opts{"P"} = 0   unless ( exists( $opts{"P"} ) );
	$opts{"m"} = 0   unless ( exists( $opts{"m"} ) );
	$opts{"d"} = "C:"   unless ( exists( $opts{"d"} ) );
    return ( $opts{"H"}, $opts{"U"}, $opts{"P"}, $opts{"m"}, $opts {"d"});
}

					    
#-------------------------------------------------------------------------------------------------
# Module % Cpu Load
#--------------------------------------------------------------------------------------------------

if($module eq "cpuload"){
	
						my $cpuload = `wmic -U '$user'\%'$pass' //$host "select LoadPercentage from Win32_Processor" | grep -v CLASS | grep -v LoadPercentage | gawk -F "|" '{print \$2}'`;
						my @cpuload = split(/\n/, $cpuload);
						my $sum;
						my $counter = 0;
						foreach my $val(@cpuload){
												$sum = $sum+$val;
												$counter ++;
					                             }
						my $cputotal = $sum/$counter;
						print $cputotal;
						
					    }

#--------------------------------------------------------------------------------------------------
# Module % Disk use
#-------------------------------------------------------------------------------------------------- 					  
 					    
if ($module eq "diskuse"){
						my $disktot = `wmic -U '$user'\%'$pass' //$host "select Size from Win32_LogicalDisk" | grep $disk | gawk -F "|" '{print \$2}' `;
						my $diskfree = `wmic -U '$user'%'$pass' //$host "select FreeSpace from Win32_LogicalDisk" | grep $disk | gawk -F "|" '{print \$2}'`;
						my $diskuse = ($diskfree*100)/$disktot;
						printf("%.2f", $diskuse);
					    }
#--------------------------------------------------------------------------------------------------
# Module % Memory use
#--------------------------------------------------------------------------------------------------

if ($module eq "memuse"){ 
						my $memtot = `wmic -U '$user'%'$pass' //$host "select TotalPhysicalMemory from Win32_ComputerSystem"  | grep -v TotalPhysicalMemory | grep -v CLASS | gawk -F "|" '{print \$2}' ` ;
						my $memfree = `wmic -U '$user'%'$pass' //$host "SELECT AvailableBytes from Win32_PerfRawData_PerfOS_Memory" | grep -v AvailableBytes | grep -v CLASS ` ;
						my $memuse = ($memtot - $memfree)*100/$memtot;
						printf("%.2f", $memuse);
					    }   
					    
					  
