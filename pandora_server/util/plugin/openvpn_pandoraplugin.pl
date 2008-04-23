#!/usr/bin/perl
##################################################################################
# OpenVPN Plugin for Pandora FMS 2.0
# (c) Sancho Lerena 2008, slerena@gmail.com
# This is the first plugin for Pandora FMS 2.0 plugin server
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; version 2
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
##################################################################################

my $cfg_remote_host = ""; 
my $cfg_remote_port = ""; 
my $cfg_password = "";
my $cfg_timeout = 10; 
my $cfg_quiet = 0;

use Net::Telnet;
use Getopt::Std;
use strict;

# ------------------------------------------------------------------------------------------
# This function show a brief doc.
# ------------------------------------------------------------------------------------------
sub help {
    print "OpenVPN Plugin for Pandora FMS 2.0, (c) Sancho Lerena 2008 \n";
    print "Syntax: \n\n";
    print "\t -a <host>\n\t -w <pass>\n\t -p <port>\n\t -t <timeout>\n\t -q\n";
    print "\n";
}

# ------------------------------------------------------------------------------------------
# Print an error and exit the program.
# ------------------------------------------------------------------------------------------
sub error {
    if ($cfg_quiet == 0) {
        print (STDERR "[err] $_[0]\n");
    }
    exit 1;
}


# ------------------------------------------------------------------------------------------
# Read configuration from commandline parameters
# ------------------------------------------------------------------------------------------
sub config {
    my %opts;
    my $tmp;

    # Get options
    if (getopts ('a:w:p:t:hq', \%opts) == 0 || defined ($opts{'h'})) {
        help ();
        exit 1;
    }

    # Address
    if (defined ($opts{'a'})) {
        $cfg_remote_host  = $opts{'a'};
        if ($cfg_remote_host !~ /^[a-zA-Z\.]+$/ && ($cfg_remote_host  !~ /^(\d+)\.(\d+)\.(\d+)\.(\d+)$/
            || $1 < 0 || $1 > 255 || $2 < 0 || $2 > 255
            || $3 < 0 || $3 > 255 || $4 < 0 || $4 > 255)) {
            error ("Address $cfg_remote_host  is not valid.");
        }
    }

    # Password
    if (defined ($opts{'w'})) {
        $cfg_password = $opts{'w'};
    }

    # Timeout
    if (defined ($opts{'t'})) {
        $cfg_timeout = $opts{'t'};
    }

    # Port
    if (defined ($opts{'p'})) {
        $cfg_remote_port  = $opts{'p'};
        if (($cfg_remote_port > 65550) || ($cfg_remote_port < 1)){
            error ("Port $cfg_remote_port is not valid.");
        }
    }

    # Quiet mode
    if (defined ($opts{'q'})) {
        $cfg_quiet = 1;
    }

    if (($cfg_remote_host eq "") || ($cfg_remote_port eq "")){
        error ("You need to define remote host and remote port to use this plugin");
    }
}

# ------------------------------------------------------------------------------------------
# This function connects and get number of users currently connected to OpenVPN
# ------------------------------------------------------------------------------------------

sub get_users {
    my $line;  
    my $exit = 0;
    my $clients = 0;
    eval {
        my $telnet = new Net::Telnet ( Timeout=>$cfg_timeout, Errmode=>'die', Port => $cfg_remote_port);
        $telnet->open($cfg_remote_host);
        $telnet->waitfor('/ENTER PASSWORD/i');
        $telnet->print($cfg_password);
        $telnet->waitfor('/OpenVPN Management Interface/i');
        $telnet->print("status 2");
        while ($exit == 0) {
            $line = $telnet->getline;
            if ($line =~ m/END/i){
                $exit = 1;
            }
            if ($line =~ m/^CLIENT_LIST/i){
                $clients++;
            }
        }
        $telnet->print("quit");
        $telnet->close();
    };
    return $clients;
}

# ------------------------------------------------------------------------------------------
# Main program
# ------------------------------------------------------------------------------------------

    config();
    print get_users();
    exit;   
