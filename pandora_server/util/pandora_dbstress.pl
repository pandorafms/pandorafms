#!/usr/bin/perl
################################################################################
# Pandora DB Stress tool
################################################################################
# Copyright (c) 2005-2008 Artica Soluciones Tecnologicas S.L
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

# Configure here target (AGENT_ID for Stress)

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

# Pandora Modules
use PandoraFMS::Config;
use PandoraFMS::Tools;
use PandoraFMS::DB;
use PandoraFMS::Core;

################################################################################
################################################################################

my $version = "2.0 PS080903";

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
my $dbh = DBI->connect("DBI:mysql:$pa_config{'dbname'}:$pa_config{'dbhost'}:3306",$pa_config{'dbuser'}, $pa_config{'dbpass'},	{ RaiseError => 1, AutoCommit => 1 });

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

	my $factor;

	my $valor = 0; # value storage for data generation
	my $a; # loopcounter
	my $b; # counter
	print " [*] Processing module $target_name \n";
	my $agent_name = get_agent_name ($dbh, $target_agent);
	my $err; # not used
	# Init start time to now - target_days 
	my $utimestamp = time () - (86400 * $target_days);
	my $timestamp = strftime ("%Y-%m-%d %H:%M:%S", localtime($utimestamp));

	# Calculate how many iterations need to fill data range
	# $target_days*min*sec / $target_interval 

	my $iterations = ($target_days * 24 * 60 * 60) / $target_interval;

	print " [D] ID_AgenteMoludo $id_agentemodulo Interval $target_interval ModuleName $target_name Days $target_days Agent $target_agent \n";

	open (LOG,">> pandora_dbstress.log");
	# Generate MATH/Curve data for beautiful Drawings
	if ( $target_name =~ /curve/i ){
		# COS function to draw curves in a regular way
		$b = 0;
		$factor=rand(20);
		for ($a=1;$a<$iterations;$a++){
        		$valor = 1 + cos(deg2rad($b));
			$b = $b + $factor/10;
			if ($b > 180){
				$b =0;
			}
			$utimestamp += $target_interval;
			my $timestamp = strftime ("%Y-%m-%d %H:%M:%S", localtime($utimestamp));
			$valor = $valor * $b * 10;
			$valor = sprintf("%.2f", $valor);
			$valor =~ s/\,/\./g;
        		if (($a % 20) == 0) {
				print "\r   -> ".int($a / ($iterations / 100))."% generated for ($target_name)                                                     ";
			}
			pandora_update_agent($pa_config, $timestamp, $target_agent, "none","1.2", $target_interval, $dbh);
			# print LOG $mysql_date, $target_name, $valor, "\n";
			pandora_process_module ($pa_config, $valor, '', $module, '', '', $utimestamp, $dbh);
			#pandora_writedata($pa_config,$mysql_date,$agent_name,$target_type,$target_name,$valor,0,0,"",$dbh,\$bUpdateDatos);			
			#pandora_writestate ($pa_config,$agent_name,$target_type,$target_name,$valor,100,$dbh,$bUpdateDatos);
			}
	}

	# Generate pseudo-random data for changing drawings
	if ( $target_name =~ /random/i ){
		# Random values over line a static line
		for ($a=1;$a<$iterations;$a++){
        		$valor = rand(15) + rand(15) + rand(15) + rand(15) + rand(15) + rand(15);
			$valor = sprintf("%.2f", $valor);
			$valor =~ s/\,/\./g; 
			$utimestamp += $target_interval;
			my $timestamp = strftime ("%Y-%m-%d %H:%M:%S", localtime($utimestamp));
			if ($a % 20 == 0) {
				print "\r   -> ".int($a / ($iterations / 100))."% generated for ($target_name)                                                     ";
			}
			pandora_update_agent($pa_config, $timestamp, $target_agent, "none","1.2", $target_interval, $dbh);
			#print LOG $mysql_date, $target_name, $valor, "\n";
			pandora_process_module ($pa_config, $valor, '', $module, '', '', $utimestamp, $dbh);
			#pandora_writedata($pa_config,$mysql_date,$agent_name,$target_type,$target_name,$valor,0,0,"",$dbh,\$bUpdateDatos);			
			#pandora_writestate ($pa_config,$agent_name,$target_type,$target_name,$valor,100,$dbh,$bUpdateDatos);
		}

	}

	# Generate pseudo-random data for boolean data
	if ( $target_name =~ /boolean/i ){
		for ($a=1;$a<$iterations;$a++){
        		$valor = rand(50);
			if ($valor > 2){ 
				$valor = 1;
			} else {
				$valor = 0;
			}
			$utimestamp += $target_interval;
			my $timestamp = strftime ("%Y-%m-%d %H:%M:%S", localtime($utimestamp));
			if ($a % 20 eq 0) {
				print "\r   -> ".int($a / ($iterations / 100))."% generated for ($target_name)                                                     ";
			}
			pandora_update_agent($pa_config, $timestamp, $target_agent, "none","1.2", $target_interval, $dbh);
			#print LOG $mysql_date, $target_name, $valor, "\n";
			pandora_process_module ($pa_config, $valor, '', $module, '', '', $utimestamp, $dbh);
			#pandora_writedata($pa_config,$mysql_date,$agent_name,$target_type,$target_name,$valor,0,0,"",$dbh,\$bUpdateDatos);
			#pandora_writestate ($pa_config,$agent_name,$target_type,$target_name,$valor,$valor,$dbh,$bUpdateDatos);
		}

	}
	
	# Generate pseudo-random data for boolean data
	if ( $target_name =~ /text/i ){
		for ($a=1;$a<$iterations;$a++){
			$valor = pandora_trash_ascii (rand(100)+50);
			$utimestamp += $target_interval;
			my $timestamp = strftime ("%Y-%m-%d %H:%M:%S", localtime($utimestamp));
			if ($a % 20 eq 0) {
				print "\r   -> ".int($a / ($iterations / 100))."% generated for ($target_name)                                                     ";
			}
			pandora_update_agent($pa_config, $timestamp, $target_agent, "none","1.2", $target_interval, $dbh);
			#print LOG $mysql_date, $target_name, $valor, "\n";
			pandora_process_module ($pa_config, $valor, '', $module, '', '', $utimestamp, $dbh);
			#pandora_writedata($pa_config,$mysql_date,$agent_name,$target_type,$target_name,$valor,0,0,"",$dbh,\$bUpdateDatos);
			#pandora_writestate ($pa_config,$agent_name,$target_type,$target_name,$valor,100,$dbh,$bUpdateDatos);
		}
	}

	close (LOG);
	print "\n";
}
