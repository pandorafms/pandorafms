#!/usr/bin/perl
################################################################################
# Pandora DB Stress tool
################################################################################
# Copyright (c) 2005-20011 Artica Soluciones Tecnologicas S.L
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
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
################################################################################

use POSIX qw(strftime);

# Configure here your targets for stress testing

my $target_module = -1; # -1 for all modules of that agent
my $target_agent = -1;
my $target_interval = 300;
my $target_days = 30;

################################################################################
################################################################################

# Includes list
use strict;
use DBI;			# DB interface with MySQL
use Math::Trig;			# Math functions
use Time::HiRes qw ( clock_gettime CLOCK_REALTIME);

# Default lib dir for RPM and DEB packages
use lib '/usr/lib/perl5';

# Pandora Modules
use PandoraFMS::Config;
use PandoraFMS::Tools;
use PandoraFMS::DB;
use PandoraFMS::Core;

################################################################################
################################################################################

my $version = "4.0 PS110923";

# FLUSH in each IO (only for debug, very slooow)
# ENABLED in DEBUGMODE
# DISABLE FOR PRODUCTION
$| = 0;

my %pa_config;

# Inicio del bucle principal de programa
pandora_init(\%pa_config,"Pandora DB Stress tool");

# Read config file for Global variables
pandora_load_config (\%pa_config,0); #Start like a data server

# open database, only ONCE. We pass reference to DBI handler ($dbh) to all subprocess
my $dbh = DBI->connect("DBI:mysql:$pa_config{'dbname'}:$pa_config{'dbhost'}:$pa_config{'dbport'}",$pa_config{'dbuser'}, $pa_config{'dbpass'},	{ RaiseError => 1, AutoCommit => 1 });

print " [*] Working for agent ID $target_agent \n";
print " [*] Generating data of $target_days days ago \n";
print " [*] Interval for this workload is $target_interval \n";

# For each module of $target_agent_id
my $query_idag;

if ($target_agent ne -1){
	if ($target_module ne -1){
		$query_idag = "select * from tagente_modulo where id_agente = $target_agent AND id_agente_modulo = $target_module";
	} else {
		$query_idag = "select * from tagente_modulo where id_agente = $target_agent";
	}
} else {
	$query_idag = "select * from tagente_modulo";
}

my $s_idag = $dbh->prepare($query_idag);
$s_idag ->execute;
if ($s_idag->rows != 0) {
	while (my $module = $s_idag->fetchrow_hashref()) {
		# Fill this module with data !
		process_module (\%pa_config, $module, $target_interval, $target_days, $dbh);
	}
}
$s_idag->finish();
$dbh->disconnect();
print " [*] All work done\n\n";
# END of main proc


##############################################################################
# SUB process_module ()
# Create a full range set of Pseudo random data for id_agente_modulo passed
# as second parameter. Depends on module_name to generate a random value
# (random) or periodic curve (cuve) values.
##############################################################################

sub process_module($$$$$){
	my ($pa_config, $module, $target_interval, $target_days, $dbh) = @_;

	my $id_agentemodulo = $module->{'id_agente_modulo'};
	my $target_name = $module->{'nombre'};
	my $target_agent = $module->{'id_agente'};
	my %data_object;

	my $factor;

	my $valor = 0; # value storage for data generation
	my $a; # loopcounter
	my $b; # counter
	print " [*] Processing module $target_name \n";
	
	my $agent = get_db_single_row ($dbh, 'SELECT * FROM tagente WHERE id_agente = ?', $module->{'id_agente'});
	my $agent_name = $agent->{"nombre"};

	my $err; # not used
	# Init start time to now - target_days 
	my $utimestamp = time () - (86400 * $target_days);
	my $timestamp = strftime ("%Y-%m-%d %H:%M:%S", localtime($utimestamp));

	# Calculate how many iterations need to fill data range
	# $target_days*min*sec / $target_interval 

	my $iterations = ($target_days * 24 * 60 * 60) / $target_interval;

	print " [D] ID_AgenteMoludo $id_agentemodulo Interval $target_interval ModuleName $target_name Days $target_days Agent $agent->{'nombre'} \n";

	my $modules_processed=0;
	my $modules_processed_total=0;
	my $ttime0 = clock_gettime(CLOCK_REALTIME);
	my $ttime1 = clock_gettime(CLOCK_REALTIME);
	my $ttime2;
	my $ttime3;

	$factor=rand(20);
	$b = 0;
	for ($a=1;$a<$iterations;$a++){

		$utimestamp += $target_interval;
		my $timestamp = strftime ("%Y-%m-%d %H:%M:%S", localtime($utimestamp));

		# Generate MATH/Curve data for beautiful Drawings
		if ( $target_name =~ /curve/i ){
			# COS function to draw curves in a regular way

			$valor = 1 + cos(deg2rad($b));
			$b = $b + $factor/10;
			if ($b > 180){
				$b =0;
			}
		
			$valor = $valor * $b * 10;
			$valor = sprintf("%.2f", $valor);
			$valor =~ s/\,/\./g;
		} #end_curve


		# Generate pseudo-random data for boolean data
		elsif ( $target_name =~ /boolean/i ){
			$valor = rand(50);
			if ($valor > 2){ 
				$valor = 1;
			} else {
				$valor = 0;
			}
		}
	
		# Generate pseudo-random data for boolean data
		elsif ( $target_name =~ /text/i ){
			$valor = pandora_trash_ascii (rand(100)+50);	
		}

		# Generate pseudo-random on other module name
		else {
			$valor = rand(15) + rand(15) + rand(15) + rand(15) + rand(15) + rand(15);
			$valor = sprintf("%.2f", $valor);
			$valor =~ s/\,/\./g; 
			$utimestamp += $target_interval;
		}


		$data_object{"data"} = $valor;

		pandora_process_module ($pa_config, \%data_object, $agent, $module, '', $timestamp, $utimestamp, 1, $dbh, "");
		pandora_update_agent($pa_config, $timestamp, $target_agent, $pa_config->{'servername'}.'_Data', $pa_config->{'version'}, -1, $dbh);

		$modules_processed++;
		$modules_processed_total++;
		$ttime2 = clock_gettime(CLOCK_REALTIME);
		$ttime3 = $ttime2 - $ttime1;
		if ($ttime3 > 1){
			$ttime3 = $modules_processed / $ttime3;
			$ttime3 = sprintf("%.2f", $ttime3);
			print "  -> Current rate: $ttime3 modules/sec \n";
			$ttime1 = $ttime2;
			$modules_processed=0;
		}
	}

	$ttime3 = $ttime2 - $ttime0;
	$ttime3 = $modules_processed_total / $ttime3;
	$ttime3 = sprintf("%.2f", $ttime3);
	print "  <> Final rate: $ttime3 modules/sec \n";
}
