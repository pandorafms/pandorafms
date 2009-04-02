#!/usr/bin/perl

use strict;
use IO::Socket;

if ($#ARGV != 2) {
	die "Usage: $0 <address> <port> <command>";
}

my $socket = new IO::Socket::INET(Proto => "udp",
                                  PeerAddr => $ARGV[0],
				  PeerPort => $ARGV[1]) || die "[error] Connect error: $@";

$socket->send ($ARGV[2]) || die "[error] Send error: $@";
