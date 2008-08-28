#!/usr/bin/perl
use strict;
#
# pandora2ast.pl - Original file was allpage.agi by Rob Thomas 2005.
#               With parts of allcall.agi Original file by John Baker
# Modified by Jeremy Betts 2006
# Parts of the file (the telnet code) have been taken over and improved
# from the above files by Evi Vanoost (vanooste@rcbi.rochester.edu) 
# for a module to Pandora FMS in 2008 
#
# This file is part of Pandora2Asterisk which is an external module to the
# Pandora Flexible Monitoring System.

# Pandora2Asterisk is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.

# Pandora2Asterisk is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.

# You should have received a copy of the GNU General Public License
# along with Pandora2Asterisk.  If not, see <http://www.gnu.org/licenses/>.
#
# Check for Telnet
# turn $class name into $path
my $class = 'Net::Telnet';
my $path  =  $class;
$path     =~ s/::/\//g;
$path    .=  '.pm';

# check if $path already loaded
if ( not exists $INC{$path} ) {
	    # do block eval on $path
	        eval { require $path };
		    die "Can't load required class $class: $@" if $@;
}

#Pandora should send info as follows: _agent_ _data_ _field1_ _field2_
#Which maps as                        $agent $page $phone $callerid
my $agent = @ARGV[0];
my $data = @ARGV[1];
my $destphone = @ARGV[2];
my $callerid = @ARGV[3];

my $page = "This is a message from the Pandora FMS alert system. Agent $agent. $data";

# You need to configure this: Your manager API username and password. This
# is the information from /etc/asterisk/manager.conf. You need something like
# this in it:
# [youruser]
# secret = yoursecret
# deny=0.0.0.0/0.0.0.0
# permit=127.0.0.0/255.0.0.0
# read = system,call,log,verbose,command,agent,user
# write = system,call,log,verbose,command,agent,user
# IF that's what you have in your conf file, this is what you should have here:

my $mgruser = "youruser";
my $mgrpass = "yoursecret";
my $mgrport = 5038;
my $asthost = "127.0.0.1";

# Open connection to AGI   
my $tn = new Net::Telnet ( 
		Port => $mgrport,
		Prompt => '/.*[\$%#>] $/',
		Output_record_separator => '',
                Input_Log=> "/tmp/input.log",
                Output_Log=> "/tmp/output.log",
		Errmode    => 'die', );

$tn->open($asthost);
$tn->waitfor('/\n$/');
$tn->print("Action: Login\n");
$tn->print("Username: $mgruser\n");
$tn->print("Secret: $mgrpass\n");
$tn->print("Events: off\n\n");
my ($pm, $m) = $tn->waitfor('/Authentication (.+)\n\n/');
if ($m =~ /Authentication failed/) {
	print "VERBOSE \"Incorrect USER or PASS - unable to connect to manager interface\" 0\n";
	exit;
}
$tn->print("Action: Setvar\n");
$tn->print("Variable: MSGTEXT\n");
$tn->print("Value: $page\n\n");

$tn->print("Action: Originate\n");
$tn->print("Channel: $destphone\n");
$tn->print("Context: text2speech\n");
$tn->print("Callerid: $callerid\n"); 
$tn->print("Priority: 1\n\n");

$tn->print("Action: Logoff\n\n");
$tn->dump_log();
$tn->close;
