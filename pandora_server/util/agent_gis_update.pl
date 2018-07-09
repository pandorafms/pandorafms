#!/usr/bin/perl

###############################################################################
# Pandora FMS DB Management
###############################################################################
# Copyright (c) 2005-2018 Artica Soluciones Tecnologicas S.L
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation;  version 2
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301,USA
###############################################################################

# Includes list
use strict;
use warnings;
use Time::Local;		# DateTime basic manipulation
use DBI;				# DB interface with MySQL
use POSIX qw(strftime);
use Time::HiRes qw(usleep);

# Default lib dir for RPM and DEB packages
use lib '/usr/lib/perl5';

use PandoraFMS::Core;
use PandoraFMS::Config;
use PandoraFMS::DB;
use PandoraFMS::GIS;

# Pandora server configuration
my %conf;

# Pandora db handler
my $dbh;

# FLUSH in each IO 
$| = 1;

sub print_agent_gis_update_help() {
	print "Usage: $0 <path to pandora_server.conf>\n";
}

# Get options
my ($configuration_file) = @ARGV;
if (!defined($configuration_file) || $configuration_file eq '-h' || $configuration_file eq '--help') {
	print_agent_gis_update_help();
	exit defined($configuration_file) ? 0 : 1;
}

# Load configuration file
$conf{'pandora_path'} = $configuration_file;
$conf{'quiet'} = 0;
pandora_load_config(\%conf);

use Data::Dumper;
#print Dumper(\%conf);

if (!$conf{'activate_gis'} || $conf{'recon_reverse_geolocation_file'} eq '') {
	print "Geolocation feature es blocked.\n";
	exit 0;
}

# Connect to the DB
$dbh = db_connect ('mysql', $conf{'dbname'}, $conf{'dbhost'}, $conf{'dbport'}, $conf{'dbuser'}, $conf{'dbpass'});
if (!defined($dbh)){
	print "Cannot connect to database\n";
	exit 1;
}

# Get all agents with IP assigned
my @agents = get_db_rows($dbh,
	"SELECT id_agente, direccion, alias FROM tagente WHERE disabled = 0 AND direccion <> ''"
);

my $c_time = strftime ("%Y/%m/%d %H:%M:%S", localtime());
foreach my $agent (@agents) {
	my $location = get_geoip_info (\%conf, $agent->{'direccion'});
	if (defined($location)) {
		pandora_update_gis_data(\%conf, $dbh, $agent->{'id_agente'}, $agent->{'id_agente'}, $location->{'longitude'}, $location->{'latitude'}, undef, undef, $c_time);
	}
}

print "Successfull relocation\n";

db_disconnect($dbh);
exit 0;