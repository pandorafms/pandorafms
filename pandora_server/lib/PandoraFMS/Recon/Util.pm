#!/usr/bin/perl
# (c) Ártica ST 2016 <info@artica.es>
# Utility functions for the network topology discovery modules.

package PandoraFMS::Recon::Util;
use strict;
use warnings;

# Default lib dir for RPM and DEB packages.
use lib '/usr/lib/perl5';

use Socket qw/inet_aton/;

our @ISA = ("Exporter");
our %EXPORT_TAGS = ( 'all' => [ qw( ) ] );
our @EXPORT_OK = ( @{ $EXPORT_TAGS{'all'} } );
our @EXPORT = qw(
	ip_to_long
	mac_matches
	mac_to_dec
	parse_mac
	subnet_matches
);

########################################################################################
# Return the numeric representation of the given IP address.
########################################################################################
sub ip_to_long($) {
	my $ip_address = shift;

	return unpack('N', inet_aton($ip_address));
}

########################################################################################
# Returns 1 if the two given MAC addresses are the same.
########################################################################################
sub mac_matches($$) {
    my ($mac_1, $mac_2) = @_;

    if (parse_mac($mac_1) eq parse_mac($mac_2)) {
        return 1;
    }

    return 0;
}

########################################################################################
# Convert a MAC address to decimal dotted notation.
########################################################################################
sub mac_to_dec($) {
	my $mac = shift;

	my $dec_mac = '';
	my @elements = split(/:/, $mac);
	foreach my $element (@elements) {
        $dec_mac .= unpack('s', pack 's', hex($element)) .  '.'
	}
	chop($dec_mac);

	return $dec_mac;
}

########################################################################################
# Make sure all MAC addresses are in the same format (00 11 22 33 44 55 66).
########################################################################################
sub parse_mac($) {
    my ($mac) = @_;

    # Remove leading and trailing whitespaces.
    $mac =~ s/(^\s+)|(\s+$)//g;

    # Replace whitespaces and dots with colons.
    $mac =~ s/\s+|\./:/g;

    # Convert hex digits to uppercase.
    $mac =~ s/([a-f])/\U$1/g;

    # Add a leading 0 to single digits.
    $mac =~ s/^([0-9A-F]):/0$1:/g;
    $mac =~ s/:([0-9A-F]):/:0$1:/g;
    $mac =~ s/:([0-9A-F])$/:0$1/g;

    return $mac;
}

########################################################################################
# Returns 1 if the given IP address belongs to the given subnet.
########################################################################################
sub subnet_matches($$;$) {
	my ($ipaddr, $subnet, $mask) = @_;
	my ($netaddr, $netmask);

	# Decimal dot notation mask.
	if (defined($mask)) {
		$netaddr = $subnet;
		$netmask = ip_to_long($mask);
	}
	# CIDR notation.
	else {
		($netaddr, $netmask) = split('/', $subnet);
		return 0 unless defined($netmask);

		# Convert the netmask to a numeric format.
		$netmask = -1 << (32 - $netmask);
	}

	if ((ip_to_long($ipaddr) & $netmask) == (ip_to_long($netaddr) & $netmask)) {
		return 1;
	}

	return 0;
}

1;
__END__

