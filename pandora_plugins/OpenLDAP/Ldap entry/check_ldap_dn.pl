#!/usr/bin/perl -w
#--------------------------------------------------------------------
# Plugin server designed for PandoraFMS (www.pandorafms.org)
# Checks if a DN is in an LDAP Server
#
# Copyright (C) 2013 mario.pulido@artica.es
#--------------------------------------------------------------------

use strict;
use Net::LDAP;
use Getopt::Std;

#--------------------------------------------------------------------
# Global parameters
#--------------------------------------------------------------------
my ( $host, $port, $binddn, $bindpw, $dn ) = &options;
my $timeout = 5;
my $version = 3;

#--------------------------------------------------------------------
# Main program
#--------------------------------------------------------------------

main();

sub main {

    # LDAP Connection

    my $ldap = Net::LDAP->new(
        $host,
        port    => $port,
        version => $version,
        timeout => $timeout
    );

    unless ($ldap) {
        print "LDAP Critical : Pb with LDAP connection\n";
    }

    # Bind

    if ( $binddn && $bindpw ) {

        # Bind witch credentials

        my $req_bind = $ldap->bind( $binddn, password => $bindpw );

        if ( $req_bind->code ) {
            print "LDAP Unknown : Bind Error "
              . $req_bind->code . " : "
              . $req_bind->error . "\n";
        }
    }

    else {

        # Bind anonymous

        my $req_bind = $ldap->bind();

        if ( $req_bind->code ) {
            print "LDAP Unknown : Bind Error "
              . $req_bind->code . " : "
              . $req_bind->error . "\n";
        }
    }

    # Base Search

    my $req_search = $ldap->search(
        base   => $dn,
        scope  => 'base',
        filter => 'objectClass=*',
        attrs  => ['1.1']
    );

    if ( $req_search->code == 32 ) {

        # No such object Error
        print "LDAP Critical : $dn not present\n";
        $ldap->unbind();
    }

    elsif ( $req_search->code ) {
        print "LDAP Unknown : Search Error "
          . $req_search->code . " : "
          . $req_search->error . "\n";
        $ldap->unbind();
    }

    else {
        print "OK\n";
        $ldap->unbind();
    }

}

sub options {

    # Get and check args
    my %opts;
    getopt( 'HpDWb', \%opts );
    &usage unless ( exists( $opts{"H"} ) );
    &usage unless ( exists( $opts{"b"} ) );
    $opts{"p"} = 389 unless ( exists( $opts{"p"} ) );
    $opts{"D"} = 0   unless ( exists( $opts{"D"} ) );
    $opts{"w"} = 0   unless ( exists( $opts{"W"} ) );
    return ( $opts{"H"}, $opts{"p"}, $opts{"D"}, $opts{"W"}, $opts{"b"} );
}

sub usage {

    # Print Help/Error message
    print
"LDAP Unknown : Usage :\n$0 -H hostname [-p port] [-D binddn -W bindpw] -b dn\n";
}
