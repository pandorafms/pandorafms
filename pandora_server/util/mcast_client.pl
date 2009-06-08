#!/usr/bin/perl
# Multicast client
# Copyright (c) 2007 Artica Soluciones Tecnologicas S.L.

use strict;
use IO::Socket::Multicast;

if ($#ARGV != 1) {
	print "Usage: $0 <group> <port>\n";
	exit 1;
}

my $group = $ARGV[0];
my $port = $ARGV[1];

my $sock = IO::Socket::Multicast->new(Proto=>'udp',LocalPort=>$port);
$sock->mcast_add($group) || die "Couldn't set group: $!\n";

print "Press ctr-c to quit\n";

while (1) {
  my $data;
  next unless $sock->recv($data,1024);
  print $data;
}
