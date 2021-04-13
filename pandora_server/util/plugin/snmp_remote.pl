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
# Revised by Kevin Rojas 2020 kevin.rojas@pandorafms.com
# - Added check for Module options
# - Optimized SNMP v3 code
# - Improved Help
#-----------------------------------------------------------------------

use strict;
use Getopt::Std;


my $VERSION = 'v1r2';

#-----------------------------------------------------------------------
# HELP
#-----------------------------------------------------------------------

if ($#ARGV == -1 ) {
	print "SNMP remote plugin ($VERSION). Retrieves information via SNMP for:\n";
	print "\t- Memory Usage (%)\n";
	print "\t- CPU Usage (%)\n";
	print "\t- Disk Usage (%)\n";
	print "\t- Whether a process is running (1) or not (0)\n";
	print "\n";

	print "Arguments:\n\n";

	print "-H, --host=STRING\n";
	print "\tHost IP\n";
	
	print "-c, --community=STRING\n";
	print "\tSNMP Community\n";
	
	print "-m, --module=STRING\n";
	print "\tDefine module (memuse|diskuse|process|cpuload) \n";
	
	print "-d, --disk=STRING\n";
	print "\t[Only for diskuse module] Define disk name (C:, D: in Windows) or mount point (Linux). Default: /\n";

	print "-p, --process=STRING\n";
	print "\t[Only for process module] Process or service name\n\n";
	
	print "SNMP v3 options:\n";
	print "\n";

	print "-v, --version=STRING\n";
	print "\tSNMP version (Default 2c)\n";
	
	print "-u, --user=STRING\n";
	print "\tAuth user\n";

	print "-l, --level=STRING\n";
	print "\tSecurity level\n";
	
	print "-a STRING\n";
	print "\tAuth method\n";
	
	print "-A, --auth=STRING\n";
	print "\tAuth pass\n";

	print "-x STRING\n";
	print "\tPrivacy method\n";
	
	print "-X STRING\n";
	print "\tPrivacy pass\n";
	
	print "\n";
	print "Usage: \n";
	print "SNMP v1|2|2c:\n" ;
	print "\tperl $0 -H host -c community -m (memuse|diskuse|process|cpuload) [-p process -d disk] \n" ;
	print "SNMP v3:\n" ;
	print "\tperl $0 -H host -v 3 -u user -l seclevel -a authMethod -A authPass -x privMethod -X privPass -m (memuse|diskuse|process|cpuload) [-p process -d disk] \n" ;
	print "\n" ;
	exit;
}


my (
	$host,				# $opts{"H"}
	$community,			# $opts{"c"}
	$module,			# $opts{"m"}
	$disk,				# $opts{"d"}
	$process,			# $opts{"p"}
	$version,			# $opts{"v"}
	$user,				# $opts{"u"}
	$pass,				# $opts{"A"}
	$security_level,	# $opts{"l"}
	$auth_method,		# $opts{"a"}
	$privacy_method,	# $opts{"x"}
	$privacy_pass		# $opts{"X"}
	) = &options;

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
	
	# host
	$opts{"H"} = 0				unless ( exists( $opts{"H"} ) );
	# community
	$opts{"c"} = 0				unless ( exists( $opts{"c"} ) );
	# module
	$opts{"m"} = 0				unless ( exists( $opts{"m"} ) );
	# disk
	$opts{"d"} = "/"			unless ( exists( $opts{"d"} ) );
	# process
	$opts{"p"} = 0				unless ( exists( $opts{"p"} ) );
	# version
	$opts{"v"} = "2c"			unless ( exists( $opts{"v"} ) );
	# user
	$opts{"u"} = ""				unless ( exists( $opts{"u"} ) );
	# auth_pass
	$opts{"A"} = ""				unless ( exists( $opts{"A"} ) );
	# security level
	$opts{"l"} = "noAuthNoPriv"	unless ( exists( $opts{"l"} ) );
	# auth method
	$opts{"a"} = ""				unless ( exists( $opts{"a"} ) );
	# privacy method
	$opts{"x"} = ""				unless ( exists( $opts{"x"} ) );
	# privacy pass
	$opts{"X"} = ""				unless ( exists( $opts{"X"} ) );
	
	return (
		$opts{"H"},
		$opts{"c"},
		$opts{"m"},
		$opts{"d"},
		$opts{"p"},
		$opts{"v"},
		$opts{"u"},
		$opts{"A"},
		$opts{"l"},
		$opts{"a"},
		$opts{"x"},
		$opts{"X"});
}

unless ( $module ~~ ["memuse","cpuload","process","diskuse"] ){
	print "Error: Invalid or missing argument (-m).\n";
	print "Available options: memuse | diskuse | process | cpuload \n\n";
	exit;
}

unless ($host){
	print "Error: missing host address.\n";
	exit;
}

#-----------------------------------------------------------------------
# SNMP Version parameters
#-----------------------------------------------------------------------
my $command_line_parameters;
if ($version == "3") {
		if (lc($security_level) eq lc('authNoPriv')) {
			$command_line_parameters = "-v $version -u $user -a $auth_method -A '$pass' -l $security_level $host";
		}
		elsif (lc($security_level) eq lc("AuthPriv")) {
			$command_line_parameters = "-v $version -u $user -a $auth_method -A '$pass' -l $security_level -x $privacy_method -X '$privacy_pass' $host";
		}
		else {
			$command_line_parameters = "-v $version -u $user -l $security_level $host";
		}
	}
else {
	$command_line_parameters = "-v $version -c $community $host";
}

#-----------------------------------------------------------------------
# Memory use % module
#-----------------------------------------------------------------------
if ($module eq "memuse") {
	my $memuse = 0;
	
	my $memid = `snmpwalk -On $command_line_parameters .1.3.6.1.2.1.25.2.3.1.3 | grep Physical | head -1 | gawk '{print \$1}' | gawk -F "." '{print \$13}' | tr -d "\r"`;
	my @memtot = split(/\s/, `snmpget $command_line_parameters .1.3.6.1.2.1.25.2.3.1.5.$memid `) ;
	my @memfree = split(/\s/, `snmpget $command_line_parameters .1.3.6.1.2.1.25.2.3.1.6.$memid `) ;

	if ($memid){
	$memuse = ($memfree[-1]) * 100 / $memtot[-1];
	printf("%.2f", $memuse);
	}
	else {
		print STDOUT "-1";
		print STDERR "Error: Memory OID not found";
	}
}

#-----------------------------------------------------------------------
# Disk use % module 
#-----------------------------------------------------------------------
if ($module eq "diskuse") {
	my $diskuse = 0;
	
	unless ($disk){
		print "Error: Invalid or missing argument (-d).\n";
		exit;
	}

	if ($disk =~ /\\ /) {
        	$disk =~ s/\\/\\\\/g;
        }
	my $diskid = `snmpwalk -r 2 -On $command_line_parameters .1.3.6.1.2.1.25.2.3.1.3 | grep -F '$disk' | head -1 | gawk '{print \$1}' | gawk -F "." '{print \$13}' | tr -d "\r"`;
	my @disktot= split /\s/, `snmpget -r 2 $command_line_parameters .1.3.6.1.2.1.25.2.3.1.5.$diskid` ;

	if ($diskid == ""){
		print STDOUT "-1";
		print STDERR "Error: Disk or partition not found\n";
		exit;
	}
	if ($disktot[-1] == 0) {
		$diskuse = 0;
	}
	else {
		# hrStorageAllocationUnits
		my @diskUsed = split (/\s/, `snmpget -r 2 $command_line_parameters .1.3.6.1.2.1.25.2.3.1.6.$diskid`) ;

		$diskuse = ($diskUsed[-1] * 100) / $disktot[-1];
	}
	
	printf("%.2f", $diskuse);
}

#-----------------------------------------------------------------------
# Process Status module
#-----------------------------------------------------------------------
if ($module eq "process") {
	my $status = 0;
	
	$status = `snmpwalk $command_line_parameters  .1.3.6.1.2.1.25.4.2.1.2 2>/dev/null`;

	if ($? == 0) {
		print (($status =~ m/$process/mi)?1:0);
	}
}

#-----------------------------------------------------------------------
# CPU Load % module
#-----------------------------------------------------------------------
if ($module eq "cpuload") {
	my $cputotal = 0;
	
	my $cpuload = `snmpwalk $command_line_parameters .1.3.6.1.2.1.25.3.3.1.2 | gawk '{print \$4}' `;
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

