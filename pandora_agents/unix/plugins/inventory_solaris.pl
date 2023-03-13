#!/usr/bin/perl -w
#

use strict;
use Data::Dumper;

#print header
print "<inventory>\n";

#get pakahes
my @pkg_list = `/usr/bin/pkginfo -l 2> /dev/null`;

print " <inventory_module>\n";
print "  <name><![CDATA[Software]]></name>\n";
print "   <datalist>\n";

my $pkg;
foreach my $line (@pkg_list) {

    chomp $line;

    my $match = ( $line =~ /PKGINST:/ .. $line =~ /^$/ );

    if ( $match && $match !~ /E0/ ) {

        if ( $line =~ /^\s+([A-Z]+):\s+(.*)$/ ) {
            my ($key, $val) = ($1, $2);
            if ( $key eq 'FILES' ) {
                if ( $val =~ /^(\d+) (.*)$/ ) {
                    $pkg->{FILES}->{$2} = $1;
                }
            }
            else {
                $pkg->{$1} = $2;
            }
        }
        elsif ( $line =~ /^\s+([0-9]+) (.*)$/ ) {
            $pkg->{FILES}->{$2} = $1;
        }
        else {
            print "Unrecognized output: [$line]\n";
        }

    }
    else {

        #
        # Blank line between packages
        #
        print "<data><![CDATA[";
        print $pkg->{PKGINST} . ';'; 
        print $pkg->{VERSION} . ';'; 
        print $pkg->{NAME} . ';'; 
        print "]]></data>\n";

    }
}
print "   </datalist>\n";
print " </inventory_module>\n";
#close software module


#CPU module
print " <inventory_module>\n";
print "  <name><![CDATA[CPU]]></name>\n";
print "   <datalist>\n";

my $cpu_model =`kstat cpu_info 2> /dev/null | grep brand | uniq  | sed 's/.*brand//g' | tr -d ' '`;
my $cpu_clock = `kstat cpu_info 2> /dev/null | grep clock_MHz | uniq | awk '{print \$NF " Mhz"}'`;
my $cpu_brand = `kstat cpu_info 2> /dev/null | grep vendor_id | uniq | awk '{print \$NF}'`;

chomp $cpu_brand;
chomp $cpu_clock;
chomp $cpu_model;

print "<data><![CDATA[" . $cpu_model . ';' . $cpu_brand . ';' . $cpu_clock . "]]></data>\n";

print "   </datalist>\n";
print " </inventory_module>\n";
#close cpu module


#RAM module
print " <inventory_module>\n";
print "  <name><![CDATA[RAM]]></name>\n";
print "   <datalist>\n";

my $memory_size =`prtconf 2> /dev/null | grep Memory | cut -d ':' -f 2`;

chomp $memory_size;

print "<data><![CDATA[System Memory;" . $memory_size . "]]></data>\n";

print "   </datalist>\n";
print " </inventory_module>\n";
#close RAM module

#NIC module
print " <inventory_module>\n";
print "  <name><![CDATA[NIC]]></name>\n";
print "   <datalist>\n";

my @nic =`dladm show-link 2> /dev/null| grep -v STATE | awk '{print \$1}'`;

foreach my $nic (@nic){
    chomp $nic;

    my $nic_mac = `dladm show-linkprop $nic -p mac-address 2> /dev/null |grep -v LINK| awk '{print \$4}'`;
    my $nic_speed = `dladm show-linkprop $nic -p speed 2> /dev/null |grep -v LINK| awk '{print \$4}'`;

    chomp $nic_mac;
    chomp $nic_speed;
    print "<data><![CDATA[" . $nic . ';' . $nic_mac . ';'. $nic_speed . "]]></data>\n";
}

print "   </datalist>\n";
print " </inventory_module>\n";
#close NIC module

#close inventory
print "</inventory>\n";