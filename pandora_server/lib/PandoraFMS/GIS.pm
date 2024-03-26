package PandoraFMS::GIS;
##########################################################################
# GIS Pandora FMS functions.
# Pandora FMS. the Flexible Monitoring System. http://www.pandorafms.org
##########################################################################
# Copyright (c) 2005-2023 Pandora FMS
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU Lesser General Public License
# as published by the Free Software Foundation; version 2
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
##########################################################################

=head1 NAME 

PandoraFMS::GIS - Geographic Information System functions of Pandora FMS

=head1 VERSION

Version 3.1

=head1 SYNOPSIS

 use PandoraFMS::GIS;

=head1 DESCRIPTION

This module contains the B<GIS> (Geographic Information System) related  functions of B<Pandora FMS>

=head2 Interface
Exported Functions:

=over

=item * C<distance_moved>

=back

=head1 METHODS

=cut

use strict;
use warnings;
use Geo::IP;

# Default lib dir for RPM and DEB packages
BEGIN { push @INC, '/usr/lib/perl5'; }

use PandoraFMS::DB;
use PandoraFMS::Tools;


require Exporter;

our @ISA = ("Exporter");
our %EXPORT_TAGS = ( 'all' => [ qw( ) ] );
our @EXPORT_OK = ( @{ $EXPORT_TAGS{'all'} } );
our @EXPORT = qw( 	
	distance_moved
	);
# Some intenrnal constants

my $earth_radius_in_meters = 6372797.560856;
my $pi =  4*atan2(1,1);
my $to_radians= $pi/180;
my $to_half_radians= $pi/360;
my $to_degrees = 180/$pi;

##########################################################################
=head2 C<< distance_moved (I<$pa_config>, I<$last_longitude>, I<$last_latitude>, I<$last_altitude>, I<$longitude>, I<$latitude>, I<$altitude>) >> 

Measures the distance between the last position and the previous one taking in acount the earth curvature
The distance is based on Havesine formula and so far doesn't take on account the altitude

B<< Refferences (I<Theory>): >>
 * L<http://franchu.net/2007/11/16/gis-calculo-de-distancias-sobre-la-tierra/>
 * L<http://en.wikipedia.org/wiki/Haversine_formula>

B<< References (I<C implementation>): >>
 * L<http://blog.julien.cayzac.name/2008/10/arc-and-distance-between-two-points-on.html>

=cut
##########################################################################
sub distance_moved ($$$$$$$) {
	my ($pa_config, $last_longitude, $last_latitude, $last_altitude,
		$longitude, $latitude, $altitude) = @_;
	
	if (!is_numeric($last_longitude) &&
		!is_numeric($longitude) &&
		!is_numeric($last_latitude) &&
		!is_numeric($latitude)) {
		return 0;
	}
	
	my $long_difference = $last_longitude - $longitude;
	my $lat_difference = $last_latitude - $latitude;
	#my $alt_difference = $last_altitude - $altitude;
	
	
	my $long_aux = sin ($long_difference * $to_half_radians);
	my $lat_aux = sin ($lat_difference * $to_half_radians);
	$long_aux *= $long_aux;
	$lat_aux *= $lat_aux;
	# Temporary value to make sorter the asin formula.
	my $asinaux = sqrt($lat_aux +
		cos($last_latitude*$to_radians) * cos($latitude * $to_radians) * $long_aux );
	# Assure the aux value is not greater than 1 
	if ($asinaux > 1) {
		$asinaux = 1;
	}
	# We use: asin(x)  = atan2(x, sqrt(1-x*x))
	my $dist_in_rad = 2.0 * atan2($asinaux, sqrt (1 - $asinaux * $asinaux));
	my $dist_in_meters = $earth_radius_in_meters * $dist_in_rad;
	
	logger($pa_config,
		"Distance moved:" . $dist_in_meters ." meters", 10);
	
	return $dist_in_meters;
}

##########################################################################
=head2 C<< get_random_close_point(I<$pa_config>, I<$center_longitude>, I<$center_latitude>) >> 

Gets the B<Longitude> and the B<Laitiutde> of a random point in the surroundings of the 
coordintaes received (I<$center_longitude>, I<$center_latitude>).

Returns C<< (I<$longitude>, I<$laitiutde>) >>
=cut
##########################################################################
sub get_random_close_point ($$$) {
	my ($pa_config, $center_longitude, $center_latitude) = @_;

	return ($center_longitude, $center_latitude) if ($pa_config->{'recon_location_scatter_radius'} == 0);

	my $sign = int rand(2);
	my $longitude = ($sign*(-1)+(1-$sign)) * rand($pa_config->{'recon_location_scatter_radius'}/$earth_radius_in_meters)*$to_degrees;
	logger($pa_config,"Longitude random offset '$longitude' ", 8);
	$longitude += $center_longitude;
	logger($pa_config,"Longitude with random offset '$longitude' ", 8);
	$sign = int rand(2);
	my $latitude = ($sign*(-1)+(1-$sign)) * rand($pa_config->{'recon_location_scatter_radius'}/$earth_radius_in_meters)*$to_degrees;
	logger($pa_config,"Longitude random offset '$latitude' ", 8);
	$latitude += $center_latitude;
    logger($pa_config,"Latiitude with random offset '$latitude' ", 8);
	return ($longitude, $latitude);
}

# End of function declaration
# End of defined Code

1;
__END__

=head1 DEPENDENCIES

L<PandoraFMS::DB>, L<PandoraFMS::Tools>, L<Geo::IP>

=head1 LICENSE

This is released under the GNU Lesser General Public License.

=head1 SEE ALSO

L<PandoraFMS::DB>, L<PandoraFMS::Tools>

=head1 COPYRIGHT

Copyright (c) 2005-2023 Pandora FMS

=cut
