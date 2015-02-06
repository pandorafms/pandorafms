#!/usr/bin/perl

use strict;
use warnings;

sub get_param($) {
	my $param = shift;
	my $value = undef;

	$param = "-".$param;
	
	for(my $i=0; $i<$#ARGV; $i++) {
		
		if ($ARGV[$i] eq $param) {
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

my $res = `ipmi-sensors -h $host -u $user -p $pass -s $sensor | tail -1`;

my @aux = split(/\|/, $res);

my $value = $aux[3];

$value =~ s/\n//;
$value =~ s/^\s+//;
$value =~ s/\s+$//;

#Output the value
print $value;
