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

my $host = get_param("h");
my $user = get_param("u");
my $pass = get_param("p");
my $sensor = get_param("s");

my $res = `ipmi-sensors -h $host -u $user -p $pass -s $sensor`;

my @aux = split(/:/, $res);

my $value = $aux[2];

$value =~ s/\n//;

if ($value =~ / (\S+) .+/) {
	$value = $1;
}

#Clean value
$value =~ s/^\s+//;
$value =~ s/\s+$//;

#Output the value
print $value;
