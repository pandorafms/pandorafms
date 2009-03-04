#!/usr/bin/perl -w
use strict;
use IO::Socket::Multicast;
use Getopt::Long;

# Sample usage: ./multicast.pl -g 239.255.255.255 -p 1234 -t 30
my ($group,$port,$timeout);

$timeout = 10;

GetOptions(
        "h" => sub { help() },
        "help" => sub { help() },
        "g=s" => \$group,
        "p=s" => \$port,
        "t=i" => \$timeout
);

alarm($timeout);

$SIG{ALRM} = sub { die_return_timeout(); };

#die_return(); };

sub die_return {
	print "0";
	exit 1;
}

sub die_return_timeout {
	print "0";
	exit -1;
}

my $sock;
eval {
	while (!defined($sock)){
		$sock = IO::Socket::Multicast->new(Proto=>'udp', LocalPort=>$port);
	}

	$sock->mcast_add($group) || die_return();

	my $data;
	next unless $sock->recv($data,1);
	print "1";
	exit 0;
};
if ($@){
	die_return_timeout();
}
 
sub help {
	print "\nPandora FMS Plugin for Check Multicast\n\n";
	print "Syntax: \n\n   ./multicast.pl -g <group> -p <port> -t <timeout> \n\n";
	print "Sample usage: ./multicast.pl -g 239.255.255.255 -p 1234 -t 10 \n\n";
	exit -1;
}

