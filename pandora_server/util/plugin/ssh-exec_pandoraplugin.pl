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
my $cfg_remote_port = "22"; 
my $cfg_password = "";
my $cfg_user = "";
my $cfg_command = "";
my $cfg_timeout = 10; 
my $cfg_quiet = 0;

use Net::SSH::Perl;
use Getopt::Std;
use strict;

# ------------------------------------------------------------------------------------------
# This function show a brief doc.
# ------------------------------------------------------------------------------------------
sub help {
    print "SSH-Exec Plugin for Pandora FMS 2.0, (c) Sancho Lerena 2008 \n";
    print "Syntax: \n\n";
    print "\t -a <host>\n\t -u <user>\n\t -w <pass>\n\t -p <port>\n\t -c <command>\n\t -t <timeout>\n\t -q\n";
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
    if (getopts ('u:c:a:w:p:t:hq', \%opts) == 0 || defined ($opts{'h'})) {
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

    # Command
    if (defined ($opts{'c'})) {
        $cfg_command = $opts{'c'};
    }

    # User
    if (defined ($opts{'u'})) {
        $cfg_user = $opts{'u'};
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
# This function exec a remote command using SSH
# ------------------------------------------------------------------------------------------
sub ssh_exec {
    my $out;
    my $err;
    my $exit;
    my $ssh = Net::SSH::Perl->new($cfg_remote_host, options => [ "Port $cfg_remote_port", 
        "BatchMode yes" ]);
    $ssh->login($cfg_user, $cfg_password);
    ($out, $err, $exit) = $ssh->cmd($cfg_command);
    return $out;
}

# ------------------------------------------------------------------------------------------
# Main program
# ------------------------------------------------------------------------------------------

    config();
    print ssh_exec();
    exit;

