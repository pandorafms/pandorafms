#! /usr/bin/perl -w
#---------------------------------------------------------------------------
# Check LDAP query server plugin Pandora FMS
#
# Request an LDAP server and count entries returned
# Artica ST 
# Copyright (C) 2013 mario.pulido@artica.es
#
# License: GPLv2+
#---------------------------------------------------------------------------
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# GPL License: http://www.gnu.org/licenses/gpl.txt
#---------------------------------------------------------------------------

my $VERSION          = 'v1r1';
#---------------------------------------------------------------------------
# Modules
#---------------------------------------------------------------------------
use strict;
use Getopt::Long;
&Getopt::Long::config('bundling');
use File::Basename;
use Net::LDAP;

#---------------------------------------------------------------------------
# Options
#---------------------------------------------------------------------------
my $progname = basename($0);
my $help;
my $version;
my $verbose = 0;
my $host;
my $authentication;
my $log_file;
my $name;
my $port;
my $regexp;
my $eregexp;
my $exclude;
my $minute = 60;
my $url;


# For LDAP plugins
my $ldap_binddn;
my $ldap_bindpw;
my $ldap_filter;
my $ldap_base;
my $ldap_scope;

GetOptions(
    'h'          => \$help,
    'help'       => \$help,
    'v+'         => \$verbose,
    'verbose+'   => \$verbose,
    'H:s'        => \$host,
    'host:s'     => \$host,
    'p:i'       => \$port,
    'port:i'    => \$port,
    'u:s'      => \$ldap_binddn,
    'binddn:s' => \$ldap_binddn,
    'P:s'      => \$ldap_bindpw,
    'bindpw:s' => \$ldap_bindpw,
    'Q:s'      => \$ldap_filter,
    'filter:s' => \$ldap_filter,
    'b:s'      => \$ldap_base,
    'base:s'   => \$ldap_base,
    's:s'      => \$ldap_scope,
    'scope:s'  => \$ldap_scope,
);


#---------------------------------------------------------------------------
# Functions
#---------------------------------------------------------------------------

# DEBUG function
sub verbose {
    my $output_code = shift;
    my $text        = shift;
    if ( $verbose >= $output_code ) {
        printf "VERBOSE $output_code ===> %s\n", $text;
    }
}

# check if -H -b and -F is used
sub check_host_param {
    if ( !defined($host)) {
		
    print "-H, --host=STRING\n";
    print "\tIP or name (FQDN) of the directory. You can use URI (ldap://, ldaps://, ldap+tls://)\n";
    print "-p, --port=INTEGER\n";
    print "\tDirectory port to connect to.\n";
    print "-u, --binddn=STRING\n";
    print "\tBind DN. Bind anonymous if not present.\n";
    print "-P, --bindpw=STRING\n";
    print "\tBind passwd. Need the Bind DN option to work.\n";
    print "-Q, --filter=STRING\n";
    print "\tLDAP search filter.\n";
    print "-b, --base=STRING\n";
    print "\tLDAP search base.\n";
    print "-s, --scope=STRING\n";
    print "\tLDAP search scope\n";
    print "\n";
    
    print "Version=$VERSION";
    }
}


# Bind to LDAP server
sub get_ldapconn {
    &verbose( '3', "Enter &get_ldapconn" );
    my ( $server, $binddn, $bindpw ) = @_;
    my ( $useTls, $tlsParam );

    # Manage ldap+tls:// URI
    if ( $server =~ m{^ldap\+tls://([^/]+)/?\??(.*)$} ) {
        $useTls   = 1;
        $server   = $1;
        $tlsParam = $2 || "";
    }
    else {
        $useTls = 0;
    }

    my $ldap = Net::LDAP->new( $server );

    return ('1') unless ($ldap);
    &verbose( '2', "Connected to $server" );

    if ($useTls) {
        my %h = split( /[&=]/, $tlsParam );
        my $message = $ldap->start_tls(%h);
        $message->code
          && &verbose( '1', $message->error )
          && return ( $message->code, $message->error );
        &verbose( '2', "startTLS succeed on $server" );
    }

    if ( $binddn && $bindpw ) {

        # Bind witch credentials
        my $req_bind = $ldap->bind( $binddn, password => $bindpw );
        $req_bind->code
          && &verbose( '1', $req_bind->error )
          && return ( $req_bind->code, $req_bind->error );
        &verbose( '2', "Bind with $binddn" );
    }
    else {
        my $req_bind = $ldap->bind();
        $req_bind->code
          && &verbose( '1', $req_bind->error )
          && return ( $req_bind->code, $req_bind->error );
        &verbose( '2', "Bind anonym" );
    }
    &verbose( '3', "Leave &get_ldapconn" );
    return ( '0', $ldap );
}

# Get the master URI from cn=monitor
sub get_entries {
    &verbose( '3', "Enter &get_entries" );
    my ( $ldapconn, $base, $scope, $filter ) = @_;
    my $message;
    my $count;
    $message = $ldapconn->search(
        base   => $base,
        scope  => $scope,
        filter => $filter,
        attrs  => ['1.1']
    );
    $message->code
      && &verbose( '1', $message->error )
      && return ( $message->code, $message->error );
    $count = $message->count();
    &verbose( '2', "Found $count entries" );
    &verbose( '3', "Leave &get_entries" );
    return ( 0, $count );
}

#---------------------------------------------------------------------------
# Main
#---------------------------------------------------------------------------

# Options checks
&check_host_param();
#&check_base_param();

# Default values
#$ldap_filter ||= "(objectClass=*)";
$ldap_scope  ||= "sub";

my $errorcode;

# Connect to the directory
# If $host is an URI, use it directly
my $ldap_uri;
if ( $host =~ m#ldap(\+tls)?(s)?://.*# ) {
    $ldap_uri = $host;
    $ldap_uri .= ":$port" if ( $port and $host !~ m#:(\d)+# );
}
else {
    $ldap_uri = "ldap://$host";
    $ldap_uri .= ":$port" if $port;
}

my $ldap_server;
( $errorcode, $ldap_server ) =
  &get_ldapconn( $ldap_uri, $ldap_binddn, $ldap_bindpw );
if ($errorcode) {
    print "Can't connect to $ldap_uri.\n";
}

# Request LDAP
my $nb_entries;
( $errorcode, $nb_entries ) =
  &get_entries( $ldap_server, $ldap_base, $ldap_scope, $ldap_filter );
if ($errorcode) {
    print "0\n";
}

#---------------------------------------------------------------------------
# Exit 
#---------------------------------------------------------------------------

# Print $nb_entries and exit
print "$nb_entries\n";
exit;    
 



