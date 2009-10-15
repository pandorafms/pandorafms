#!/usr/bin/perl
# Multicast client
# Copyright (c) 2007 Artica Soluciones Tecnologicas S.L.

use strict;
use warnings;
use POSIX qw(strftime);
use IO::Socket::Multicast;

if ($#ARGV != 3) {
	print "Usage: $0 <group> <port> <agent_name> <alert_name>\n";
	exit 1;
}

my $group = $ARGV[0];
my $port = $ARGV[1];
my $agent_name = $ARGV[2];
my $alert_name = $ARGV[3];

my $status_report = "<status_report>\n";

$status_report .= "<element id='$agent_name' name='$alert_name' status='ALRM' timestamp='" . strftime ("%Y/%m/%d %H:%M:%S", localtime()) . "'></element>\n";

$status_report .= "</status_report>\n";

my $socket = IO::Socket::Multicast->new(Proto => 'udp',
                                            PeerAddr => $group . ':' . $port);
return unless defined ($socket);
$socket->send($status_report);

# print $status_report;


