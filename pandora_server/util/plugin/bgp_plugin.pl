#!/usr/bin/perl -w
#
# Router bgp (Border Gateway Protocol v4 ) monitor
# look at each router and get the status of all is BGP neighbour
# Adapted version for a Pandora FMS server plugin

# Copyright 2002, Marc Hauswirth, Safe Host SA <marc@safehostnet.com>
# Copyright 2006, Val Glinskiy, <vglinskiy@gmail.com>
# Copyright 2010, Sancho Lerena <slerena@artica.es>

# Some inspiration was taken from Marc Hauswirth's script http://www.kernel.org/pub/software/admin/mon/contrib/monitors/bgp/bgp.monitor
# Modified to get bad status on a non-BGP device.
# License: GNU GPL v2, see http://www.gnu.org/copyleft/gpl.html
#
# This script needs the Net::SNMP  module

my $version=1;

use Net::SNMP qw(:snmp);
use strict;
use Getopt::Long;

sub print_usage () {
	print "Usage: $0 -r <router> -c <community> [-hV]\n";
	print "\n";
    exit;
}

my ($opt_v, $opt_h, $opt_r, $verbose, $router, $community);
my %ERRORS=('OK'=>0,'WARNING'=>1,'CRITICAL'=>2,'UNKNOWN'=>3);

GetOptions
        ("h"   => \$opt_h, "help"          => \$opt_h,
         "r=s" => \$opt_r, "router=s"      => \$opt_r,
	 "c=s" => \$community, "community=s" => \$community,
         "v" => \$opt_v, "verbose"       => \$opt_v);

if ($opt_v) {
        print "\n";
        print "check_bgp_snmp Pandora FMS server plugin version $version\n";
        print "\n";
        print_usage();
} 

if ($opt_h) {
        print_usage();
	}

if ( !defined($opt_r) || !defined($community) ){
	print_usage();
}

# OID's to the SNMP elements that I want to show...
# From Cisco's MIB and RFC's
# http://tools.cisco.com/Support/SNMP/do/BrowseMIB.do?local=en&step=2&mibName=BGP4-MIB

my %oids = ( 
	"SysUptime"			=>	"1.3.6.1.2.1.1.3.0",
	"bgpVersion"			=>	"1.3.6.1.2.1.15.1.0",
	"bgpLocalAs"			=>	"1.3.6.1.2.1.15.2.0",

#	"bgpPeerTable"			=>	"1.3.6.1.2.1.15.3",
	"bgpPeerEntry"			=>	"1.3.6.1.2.1.15.3.1",
	"bgpPeerIdentifier"		=>	"1.3.6.1.2.1.15.3.1.1",
	"bgpPeerState"			=>	"1.3.6.1.2.1.15.3.1.2",
	"bgpPeerAdminStatus"		=>	"1.3.6.1.2.1.15.3.1.3",
	"bgpPeerNegotiatedVersion"	=>	"1.3.6.1.2.1.15.3.1.4",
	"bgpPeerLocalAddr"		=>	"1.3.6.1.2.1.15.3.1.5",
	"bgpPeerLocalPort"		=>	"1.3.6.1.2.1.15.3.1.6",
	"bgpPeerRemoteAddr"		=>	"1.3.6.1.2.1.15.3.1.7",
	"bgpPeerRemotePort"		=>	"1.3.6.1.2.1.15.3.1.8",
	"bgpPeerRemoteAs"		=>	"1.3.6.1.2.1.15.3.1.9",
	"bgpPeerInUpdates"		=>	"1.3.6.1.2.1.15.3.1.10",
	"bgpPeerOutUpdates"		=>	"1.3.6.1.2.1.15.3.1.11",
	"bgpPeerInTotalMessages" 	=>	"1.3.6.1.2.1.15.3.1.12",
	"bgpPeerOutTotalMessages" 	=>	"1.3.6.1.2.1.15.3.1.13",
	"bgpPeerLastError"		=>	"1.3.6.1.2.1.15.3.1.14",
	"bgpPeerFsmEstablishedTransitions" =>	"1.3.6.1.2.1.15.3.1.15",
	"bgpPeerFsmEstablishedTime"	=>	"1.3.6.1.2.1.15.3.1.16",
	"bgpPeerConnectRetryInterval"	=>	"1.3.6.1.2.1.15.3.1.17",
	"bgpPeerHoldTime"		=>	"1.3.6.1.2.1.15.3.1.18",
	"bgpPeerKeepAlive"		=>	"1.3.6.1.2.1.15.3.1.19",
	"bgpPeerHoldTimeConfigured"	=>	"1.3.6.1.2.1.15.3.1.20",
	"bgpPeerKeepAliveConfigured"	=>	"1.3.6.1.2.1.15.3.1.21",
	"bgpPeerMinASOriginationInterval" =>	"1.3.6.1.2.1.15.3.1.22",
	"bgpPeerMinRouteAdvertisementInterval" => "1.3.6.1.2.1.15.3.1.23",
	"bgpPeerInUpdateElapsedTime" 	=>	"1.3.6.1.2.1.15.3.1.24",
	"bgpIdentifier"			=>	"1.3.6.1.2.1.15.4",
	"bgpRcvdPathAttrTable"		=>	"1.3.6.1.2.1.15.5",
	"bgp4PathAttrTable"		=>	"1.3.6.1.2.1.15.6",
	"bgpPathAttrEntry"		=>	"1.3.6.1.2.1.15.5.1",
	"bgpPathAttrPeer"		=>	"1.3.6.1.2.1.15.5.1.1",
	"bgpPathAttrDestNetwork"	=>	"1.3.6.1.2.1.15.5.1.2",
	"bgpPathAttrOrigin"		=>	"1.3.6.1.2.1.15.5.1.3",
	"bgpPathAttrASPath"		=>	"1.3.6.1.2.1.15.5.1.4",
	"bgpPathAttrNextHop"		=>	"1.3.6.1.2.1.15.5.1.5",
	"bgpPathAttrInterASMetric"	=>	"1.3.6.1.2.1.15.5.1.6",
	"bgp4PathAttrEntry"		=>	"1.3.6.1.2.1.15.6.1",
	"bgp4PathAttrPeer"		=>	"1.3.6.1.2.1.15.6.1.1",
	"bgp4PathAttrIpAddrPrefixLen"	=>	"1.3.6.1.2.1.15.6.1.2",
	"bgp4PathAttrIpAddrPrefix"	=>	"1.3.6.1.2.1.15.6.1.3",
	"bgp4PathAttrOrigin"		=>	"1.3.6.1.2.1.15.6.1.4",
	"bgp4PathAttrASPathSegment"	=>	"1.3.6.1.2.1.15.6.1.5",
	"bgp4PathAttrNextHop"		=>	"1.3.6.1.2.1.15.6.1.6",
	"bgp4PathAttrMultiExitDisc"	=>	"1.3.6.1.2.1.15.6.1.7",
	"bgp4PathAttrLocalPref"		=>	"1.3.6.1.2.1.15.6.1.8",
	"bgp4PathAttrAtomicAggregate"	=>	"1.3.6.1.2.1.15.6.1.9",
	"bgp4PathAttrAggregatorAS"	=>	"1.3.6.1.2.1.15.6.1.10",
	"bgp4PathAttrAggregatorAddr"	=>	"1.3.6.1.2.1.15.6.1.11",
	"bgp4PathAttrCalcLocalPref"	=>	"1.3.6.1.2.1.15.6.1.12",
	"bgp4PathAttrBest"		=>	"1.3.6.1.2.1.15.6.1.13",
	"bgp4PathAttrUnknown"		=>	"1.3.6.1.2.1.15.6.1.14",
	);


my %BgpPeerState = (
	1 => "idle",
	2 => "connect",
	3 => "active",
	4 => "opensnet",
	5 => "openconfirm",
	6 => "established"
);

my %BgpPeerAdminState = (
	1 => "stop",
	2 => "start"
);

if ($opt_r) {
	$router= $opt_r;

	# Get some infos about this router
	my ($sess, $error) = Net::SNMP->session ( -hostname => $router, -community => $community, -version => 2 );
	if(!defined($sess)) {
        #print("SESSION ERROR: $error\n");
        print ("0\n");
        exit;
}


# if you get "Message size exceeded buffer maxMsgSize" error, try reducing -maxrepetitions
 
my $results = $sess->get_bulk_request(-varbindlist => [$oids{bgpPeerRemoteAddr}], -maxrepetitions =>20 );
if (!defined($results)){
        # print "ERROR: $oids{bgpPeerRemoteAddr} No results $sess->error \n";
        print ("0\n");
        exit;
}

my $key;
my %vals=%{$results};
my $valid_keys = 0;
foreach $key (keys %vals) {
	if(oid_base_match($oids{bgpPeerRemoteAddr}, $key)) {
		$valid_keys++;
		my $PeerState;
		my $oidPeerStatus=$oids{bgpPeerState}.".".$vals{$key};
		$PeerState = $sess->get_request(-varbindlist => [$oidPeerStatus]);
		my $oidInPrefixes=$oids{bgpPeerInUpdates}.".".$vals{$key};
		my $InPrefixStatus;
		$InPrefixStatus = $sess->get_request(-varbindlist => [$oidInPrefixes]);

		if (!defined($PeerState)){
		        # print "ERROR: $oidPeerStatus  No results $sess->error\n";
		        print ("0\n");
		        exit;
		}

		if (!defined($InPrefixStatus)) {
		        # print "ERROR: $InPrefixStatus No results $sess->error\n";
		        print ("0\n");
		        exit;
		}


    # Let's check neighbor's state. If it's not "established" and not administratively down , send alarm

    if ( $BgpPeerState{$PeerState->{$oidPeerStatus}} ne "established" ) {
        my $oidNeighborAdminStatus= $oids{bgpPeerAdminStatus}.".".$vals{$key};
        my $NeighborAdminStatus = $sess->get_request(-varbindlist => [$oidNeighborAdminStatus]);
        if ( $NeighborAdminStatus -> {$oidNeighborAdminStatus} == 2 ) {
            # print ("ERROR: Neighbor $vals{$key} is $BgpPeerState{$PeerState->{$oidPeerStatus}}\n");
            print ("0\n");
            exit;
	    }
	}
	    else {
	
        if(($InPrefixStatus ->{$oidInPrefixes}) == 0) {
            # print "Neighbor $vals{$key} sends 0 prefixes\n";
            print ("0\n");
            exit;
        }

	    }
   }
}

  # If doesnt have any BGP information, exit. BGP not good (modified from original nagios plugin)

  if ($valid_keys == 0){
                print "0\n";
		exit;
    }
$sess->close;
	# print(" All BGP Neighbors are sending updates\n");
	print ("1\n");
    exit;
}

