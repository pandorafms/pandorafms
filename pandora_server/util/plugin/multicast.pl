#!/usr/bin/perl -w
use strict;
use IO::Socket::Multicast;
use Getopt::Long;

# Sample usage: ./multicast.pl -g 239.255.255.255 -p 1234 -t 30
my ($group,$port,$timeout);

GetOptions(
        "h" => sub { help() },
        "help" => sub { help() },
        "g=s" => \$group,
        "p=s" => \$port,
        "t=i" => \$timeout
);

if(!$timeout){
	$timeout=5
};

alarm($timeout);

$SIG{ALRM} = sub {print "0"; exit 1; };

my $sock = IO::Socket::Multicast->new(Proto=>'udp', LocalPort=>$port);
$sock->mcast_add($group) || die "0";

my $data;
next unless $sock->recv($data,1024);
print "1";
exit 0;
 
sub help {
	print "\nPandora FMS Plugin for Check Multicast\n\n";
	print "Syntax: \n\n   ./multicast.pl -g <group> -p <port> -t <timeout> \n\n";
	print "Sample usage: ./multicast.pl -g 239.255.255.255 -p 1234 -t 10 \n\n";
	exit -1;
}
