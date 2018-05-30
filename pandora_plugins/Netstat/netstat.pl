#!/usr/bin/env perl
# Copyright (c) 2018 Artica Soluciones Tecnologicas S.L.
use strict;
use warnings;
use Scalar::Util qw(looks_like_number);

# Call netstat.
my @out = `netstat -as 2>/dev/null`;
return if ($? != 0 || $#out < 0);

my $section = "[Unknown]";
foreach my $line (@out) {
	chomp($line);

	# New section.
	if ($line =~ m/\s*(.*):$/) {
		$section = $1;
		next;
	}
	
	# Parse the data.
	my ($module_name, $data) = ('', '');
	if ($line =~ m/(\d+)\s+(.+)$/) {
		($module_name, $data) = ($2, $1);
	}
	elsif ($line =~ m/\s*(.+):\s+(\d+)$/) {
		($module_name, $data) = ($1, $2);
	}

	# No data or non-numeric data.
	next unless looks_like_number($data);

	print "<module>\n";
	print "	<name><![CDATA[[$section] $module_name]]></name>\n";
	print "	<type>generic_data_inc</type>\n";
	print "	<module_group>Networking</module_group>\n";
	print "	<data>$data</data>\n";
	print "</module>\n";
}
