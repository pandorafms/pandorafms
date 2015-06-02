#!/usr/bin/perl

use strict;
use warnings;

sub get_param($) {
	my $param = shift;
	my $value = undef;

	$param = "-".$param;
	
	for(my $i=0; $i<$#ARGV; $i++) {
		
		if ($ARGV[$i] eq "--") {
			if ($param eq "--") {
				$value = join(' ', @ARGV[$i+1..$#ARGV]);
			}
			last;
		} elsif ($ARGV[$i] eq $param) {
			$value = $ARGV[$i+1];
			last;
		}

	}

	return $value;
}

##########################################################################
# Show help
##########################################################################
sub show_help {
	print "\nSpecific Pandora FMS Intel DCM Discovery\n";
	print "(c) Artica ST 2011 <info\@artica.es>\n\n";
	print "Usage:\n\n";
	print "   $0 -h <host> -u <username> -p <password> -s <sensor_id>\n";
	exit;
}

if ($#ARGV == -1){
	show_help();
}

my $host = get_param("h");
my $user = get_param("u");
my $pass = get_param("p");
my $sensor = get_param("s");
my $extraopts = get_param("-");

my $cmd = "ipmi-sensors -h $host -u $user -p $pass -s $sensor $extraopts --ignore-not-available-sensors --no-header-output --comma-separated-output --output-event-bitmask";
my $res = `$cmd`;

if (defined $res and "$res" ne "") {
	my ($sensor_id, $name, $type, $value, $units, $eventmask) = split(/,/, $res);

	#Output the value
	if ($value eq 'N/A') {
		if ($eventmask =~ /([0-9A-Fa-f]+)h/) {
			print hex $1;
		} else {
			print $eventmask;
		}
	} else {
		print $value;
	}
} else {
	print STDERR "Error processing command: $cmd\n";
}
