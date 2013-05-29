#!/usr/bin/perl
#---------------------------------------------------------------------------
# SNMP remote plugin 
# Depending on the configuration returns the result of these modules:
# - % Memory Use
# - % CPU Use
# - % Disk Use
# - Show if a process is running or not
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
		print "-c, --community=STRING\n";
		print "\tSnmp Community\n";
		print "-m, --module=STRING\n";
		print "\tDefine module (memuse|diskuse|process|cpuload) \n";
		print "-d, --disk=STRING\n";
		print "\tDefine disk name (C:, D: in Windows) or mount point (Linux)(only in diskuse module)\n";
		print "-p, --process=STRING\n";
		print "\tProcess or service name (only in process module)\n";
		print "\n";
        print "Example of use \n";
        print "perl snmp_remoto.pl -H host -c community -m (memuse|diskuse|process|cpuload) [-p process -d disk] \n";
        print "Version=$VERSION";
        exit;
}

my ( $host, $community, $module, $disk, $process ) = &options;

#-------------------------------------------------------------------------------------
# OPTIONS
#-------------------------------------------------------------------------------------

sub options {

    # Get and check args
    my %opts;
    getopt( 'Hcmdp', \%opts );
	$opts{"H"} = 0   unless ( exists( $opts{"H"} ) );
	$opts{"c"} = 0   unless ( exists( $opts{"c"} ) );
	$opts{"m"} = 0   unless ( exists( $opts{"m"} ) );
	$opts{"d"} = "/"   unless ( exists( $opts{"d"} ) );
	$opts{"p"} = 0   unless ( exists( $opts{"p"} ) );
    return ( $opts{"H"}, $opts{"c"}, $opts{"m"}, $opts{"d"}, $opts {"p"});
}

#--------------------------------------------------------------------------------------------------
# Module % Memory use
#--------------------------------------------------------------------------------------------------

if ($module eq "memuse"){ 
						my $memid = `snmpwalk -On -v 1 -c $community $host .1.3.6.1.2.1.25.2.3.1.3 | grep Physical | head -1 | gawk '{print \$1}' | gawk -F "." '{print \$13}' | tr -d "\r"`;
						my $memtot = `snmpget -v 1 -c $community $host .1.3.6.1.2.1.25.2.3.1.5.$memid ` ;
						my $memtot2 = `echo "$memtot" | gawk '{print \$4}'`;
						my $memfree = `snmpget -v 1 -c $community $host .1.3.6.1.2.1.25.2.3.1.6.$memid` ;
						my $memfree2 = `echo "$memfree" | gawk '{print \$4}'`;
						my $memuse = ($memfree2)*100/$memtot2;
						printf("%.2f", $memuse);
					    }
#--------------------------------------------------------------------------------------------------
# Module % Disk use
#-------------------------------------------------------------------------------------------------- 					  
 					    
if ($module eq "diskuse"){
						my $diskid = `snmpwalk -On -v 1 -c $community $host .1.3.6.1.2.1.25.2.3.1.3 | grep $disk | head -1 | gawk '{print \$1}' | gawk -F "." '{print \$13}' | tr -d "\r"`;
						my $disktot = `snmpget -v 1 -c $community $host .1.3.6.1.2.1.25.2.3.1.5.$diskid ` ;
						my $disktot2 = `echo "$disktot" | gawk '{print \$4}'`;
						my $diskfree = `snmpget -v 1 -c $community $host .1.3.6.1.2.1.25.2.3.1.6.$diskid` ;
						my $diskfree2 = `echo "$diskfree" | gawk '{print \$4}'`;
						my $diskuse = ($disktot2 - $diskfree2)*100/$disktot2;
						printf("%.2f", $diskuse);
					    }
					    
#--------------------------------------------------------------------------------------------------
# Module Process Status
#--------------------------------------------------------------------------------------------------	
				    
if ($module eq "process"){
						my $status = `snmpwalk -v 2c -c $community $host  1.3.6.1.2.1.25.4.2.1.2 | grep $process | head -1 | wc -l`;
						print $status;
					    }
#--------------------------------------------------------------------------------------------------
# Module % Cpu Load
#--------------------------------------------------------------------------------------------------

if ($module eq "cpuload"){
						my $cpuload = `snmpwalk -v 1 -c $community $host .1.3.6.1.2.1.25.3.3.1.2 | gawk '{print \$4}' `;
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

