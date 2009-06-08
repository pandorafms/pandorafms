#!/usr/bin/perl

# Copyright (c) 2007 Artica Soluciones Tecnologicas S.L.

use Date::Manip;            # Needed to manipulate DateTime formats of input, output and compare
use Time::Local;            # DateTime basic manipulation
use Net::Ping;				# For ICMP latency
use Time::HiRes;			# For high precission timedate functions (Net::Ping)
use IO::Socket;				# For TCP/UDP access
use SNMP;					# For SNMP access (libsnmp-perl PACKAGE!)
use threads; 
use NetAddr::IP;		# To manage IP Addresses
use POSIX;				# to use ceil() function
use Socket;				# to resolve address
use XML::Simple;                	# Useful XML functions
use Digest::MD5;   
use DBI; 
use File::Copy;

print "All dependencies tested and OK\n\n";

