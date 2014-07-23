#!/usr/bin/perl
#-----------------------------------------------------------------------
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
#-----------------------------------------------------------------------
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
#-----------------------------------------------------------------------

use strict;
use Getopt::Std;

my $VERSION = 'v1r1';

#-----------------------------------------------------------------------
# HELP
#-----------------------------------------------------------------------

if ($#ARGV == -1 ) {
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
	
	print "-v, --version=NUMBER\n";
	print "\tVersion of protocol\n";
	
	print "-u, --user=STRING\n";
	print "\tAuth user\n";
	
	print "-A, --auth=STRING\n";
	print "\tAuth pass\n";
	
	print "-l, --level=STRING\n";
	print "\tSecurity level\n";
	
	print "-a STRING\n";
	print "\tAuth method\n";
	
	print "-x STRING\n";
	print "\tPrivacy method\n";
	
	print "-X STRING\n";
	print "\tPrivacy pass\n";
	
	print "\n";
	print "Example of use \n";
	print "perl snmp_remoto.pl -H host -c community -m (memuse|diskuse|process|cpuload) [-p process -d disk] \n";
	print "Version=$VERSION";
	exit;
}

my ($host,
	$community,
	$module,
	$disk,
	$process,
	$version,
	$user,
	$pass,
	$auth_method,
	$privacy_method,
	$privacy_pass ) = &options;

#-----------------------------------------------------------------------
# OPTIONS
#-----------------------------------------------------------------------
sub options {
	
	# Get and check args
	my %opts;
	getopt( 'HcmdpvuAlaxX', \%opts );
	
	#~ ' -u ' . $snmp3_auth_user .
	#~ ' -A ' . $snmp3_auth_pass .
	#~ ' -l ' . $snmp3_security_level .
	#~ ' -a ' . $snmp3_auth_method .
	#~ ' -x ' . $snmp3_privacy_method .
	#~ ' -X' $snmp3_privacy_pass;
	
	$opts{"H"} = 0		unless ( exists( $opts{"H"} ) );
	$opts{"c"} = 0		unless ( exists( $opts{"c"} ) );
	$opts{"m"} = 0		unless ( exists( $opts{"m"} ) );
	$opts{"d"} = "/"	unless ( exists( $opts{"d"} ) );
	$opts{"p"} = 0		unless ( exists( $opts{"p"} ) );
	$opts{"p"} = 0		unless ( exists( $opts{"p"} ) );
	$opts{"v"} = 2		unless ( exists( $opts{"v"} ) );
	$opts{"u"} = ""		unless ( exists( $opts{"u"} ) );
	$opts{"A"} = ""		unless ( exists( $opts{"A"} ) );
	$opts{"l"} = "noAuthNoPriv"		unless ( exists( $opts{"l"} ) );
	$opts{"a"} = ""		unless ( exists( $opts{"a"} ) );
	$opts{"x"} = ""		unless ( exists( $opts{"x"} ) );
	$opts{"X"} = ""		unless ( exists( $opts{"X"} ) );
	
	return ( $opts{"H"},
		$opts{"c"},
		$opts{"m"},
		$opts{"d"},
		$opts {"p"},
		$opts{"v"},
		$opts{"u"},
		$opts{"A"},
		$opts{"l"},
		$opts{"a"},
		$opts{"x"},
		$opts{"X"});
}

#-----------------------------------------------------------------------
# Module % Memory use
#-----------------------------------------------------------------------
if ($module eq "memuse") {
	my $memuse = 0;
	my $command_line_parammeters;
	
	if ($version == 3) {
		if ($auth_method eq 'authNoPriv') {
			$command_line_parammeters = "-v 3 -u $user -a $privacy_method -A $pass -l $auth_method $host";
		}
		elsif  ($auth_method eq "noAuthNoPriv") {
			$command_line_parammeters = "-v 3 -u $user -l $auth_method $host";
		}
		else {
			$command_line_parammeters = "-v 3 -u $user -a $privacy_method -A $pass -l $auth_method -x $privacy_method -X $privacy_pass $host";
		}
	}
	else {
		$command_line_parammeters = "-v 1 -c $community $host";
	}
	
	my $memid = `snmpwalk -On $command_line_parammeters  .1.3.6.1.2.1.25.2.3.1.3 | grep Physical | head -1 | gawk '{print \$1}' | gawk -F "." '{print \$13}' | tr -d "\r"`;
	my $memtot = `snmpget $command_line_parammeters  .1.3.6.1.2.1.25.2.3.1.5.$memid ` ;
	my $memtot2 = `echo "$memtot" | gawk '{print \$4}'`;
	my $memfree = `snmpget $command_line_parammeters  .1.3.6.1.2.1.25.2.3.1.6.$memid` ;
	my $memfree2 = `echo "$memfree" | gawk '{print \$4}'`;
	
	$memuse = ($memfree2) * 100 / $memtot2;
	
	printf("%.2f", $memuse);
}

#-----------------------------------------------------------------------
# Module % Disk use
#-----------------------------------------------------------------------
if ($module eq "diskuse") {
	my $diskuse = 0;
	my $command_line_parammeters;
	
	if ($version == 3) {
		if ($auth_method eq 'authNoPriv') {
			$command_line_parammeters = "-v 3 -u $user -a $privacy_method -A $pass -l $auth_method $host";
		}
		elsif  ($auth_method eq "noAuthNoPriv") {
			$command_line_parammeters = "-v 3 -u $user -l $auth_method $host";
		}
		else {
			$command_line_parammeters = "-v 3 -u $user -a $privacy_method -A $pass -l $auth_method -x $privacy_method -X $privacy_pass $host";
		}
	}
	else {
		$command_line_parammeters = "-v 1 -c $community $host";
	}
	
	my $diskid = `snmpwalk -On $command_line_parammeters .1.3.6.1.2.1.25.2.3.1.3 | grep "$disk" | head -1 | gawk '{print \$1}' | gawk -F "." '{print \$13}' | tr -d "\r"`;
	my $disktot = `snmpget $command_line_parammeters .1.3.6.1.2.1.25.2.3.1.5.$diskid ` ;
	my $disktot2 = `echo "$disktot" | gawk '{print \$4}'`;
	
	if ($disktot2 == 0) {
		$diskuse = 0;
	}
	else {
		my $diskfree = `snmpget $command_line_parammeters .1.3.6.1.2.1.25.2.3.1.6.$diskid` ;
		my $diskfree2 = `echo "$diskfree" | gawk '{print \$4}'`;
		
		$diskuse = ($disktot2 - $diskfree2) * 100 / $disktot2;
	}
	
	printf("%.2f", $diskuse);
}

#-----------------------------------------------------------------------
# Module Process Status
#-----------------------------------------------------------------------
if ($module eq "process") {
	my $status = 0;
	my $command_line_parammeters;
	
	if ($version == 3) {
		if ($auth_method eq 'authNoPriv') {
			$command_line_parammeters = "-v 3 -u $user -a $privacy_method -A $pass -l $auth_method $host";
		}
		elsif  ($auth_method eq "noAuthNoPriv") {
			$command_line_parammeters = "-v 3 -u $user -l $auth_method $host";
		}
		else {
			$command_line_parammeters = "-v 3 -u $user -a $privacy_method -A $pass -l $auth_method -x $privacy_method -X $privacy_pass $host";
		}
	}
	else {
		$command_line_parammeters = "-v 2c -c $community $host";
	}
	
	$status = `snmpwalk $command_line_parammeters  1.3.6.1.2.1.25.4.2.1.2 | grep "$process" | head -1 | wc -l`;
	
	print $status;
}

#-----------------------------------------------------------------------
# Module % Cpu Load
#-----------------------------------------------------------------------
if ($module eq "cpuload") {
	my $cputotal = 0;
	my $command_line_parammeters;
	
	if ($version == 3) {
		if ($auth_method eq 'authNoPriv') {
			$command_line_parammeters = "-v 3 -u $user -a $privacy_method -A $pass -l $auth_method $host";
		}
		elsif  ($auth_method eq "noAuthNoPriv") {
			$command_line_parammeters = "-v 3 -u $user -l $auth_method $host";
		}
		else {
			$command_line_parammeters = "-v 3 -u $user -a $privacy_method -A $pass -l $auth_method -x $privacy_method -X $privacy_pass $host";
		}
	}
	else {
		$command_line_parammeters = "-v 1 -c $community $host";
	}
	
	my $cpuload = `snmpwalk $command_line_parammeters .1.3.6.1.2.1.25.3.3.1.2 | gawk '{print \$4}' `;
	my @cpuload = split(/\n/, $cpuload);
	my $sum;
	my $counter = 0;
	foreach my $val(@cpuload) {
		$sum = $sum + $val;
		$counter++;
	}
	
	$cputotal = $sum/$counter;
	
	print $cputotal;
}

