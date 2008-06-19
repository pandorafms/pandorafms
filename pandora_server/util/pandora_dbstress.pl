#!/usr/bin/perl
################################################################################
# Pandora DB Stress tool
################################################################################
# Copyright (c) 2004-2006 Sancho Lerena, slerena@gmail.com
# Copyright (c) 2005-2006 Artica Soluciones Tecnologicas S.L
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

# Configure here target (AGENT_ID for Stress)

my $target_module = -1; # -1 for all modules of that agent
my $target_agent = -1;
my $target_interval = 300;
my $target_days = 30;

################################################################################
################################################################################

# Includes list
use strict;
use Time::Local;		# DateTime basic manipulation
use DBI;			# DB interface with MySQL
use Date::Manip;		# Date/Time manipulation
use Math::Trig;			# Math functions

# Pandora Modules
use PandoraFMS::Config;
use PandoraFMS::Tools;
use PandoraFMS::DB;

################################################################################
################################################################################

my $version = "1.3-dev 070526";

# FLUSH in each IO (only for debug, very slooow)
# ENABLED in DEBUGMODE
# DISABLE FOR PRODUCTION
$| = 0;

my %pa_config;

# Inicio del bucle principal de programa
pandora_init(\%pa_config,"Pandora DB Stress tool");

# Read config file for Global variables
pandora_loadconfig (\%pa_config,0); #Start like a data server

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
my @data;
if ($s_idag->rows != 0) {
	while (@data = $s_idag->fetchrow_array()) {
		# Fill this module with data !
		process_module (\%pa_config, $data[0], $target_interval, $data[4], $target_days, $data[2], $data[1], $dbh);
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

sub process_module(){
	my $pa_config = $_[0];
	my $id_agentemodulo = $_[1];
	my $target_interval = $_[2];
	my $target_name = $_[3];
	my $target_days = $_[4];
	my $target_type = $_[5];
	my $target_agent = $_[6];
	my $dbh = $_[7];

	my $factor;
	$target_type = dame_nombretipomodulo_idagentemodulo ($pa_config, $target_type, $dbh);
	my $valor = 0; # value storage for data generation
	my $a; # loopcounter
	my $b; # counter
	print " [*] Processing module $target_name \n";
	my $agent_name = dame_agente_nombre ($pa_config, $target_agent, $dbh);
	my $err; # not used
	# Init start time to now - target_days 
	my $fecha_actual = &UnixDate("today","%Y-%m-%d %H:%M:%S");      
	my $m_timestamp = DateCalc($fecha_actual,"- $target_days days",\$err);
	my $mysql_date;
	my $bUpdateDatos;
	# Calculate how many iterations need to fill data range
	# $target_days*min*sec / $target_interval 

	my $iterations = ($target_days * 24 * 60 * 60) / $target_interval;

	print " [D] ID_AgenteMoludo $id_agentemodulo Interval $target_interval ModuleName $target_name Days $target_days Type $target_type Agent $target_agent \n";

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
			$m_timestamp = DateCalc($m_timestamp,"+ $target_interval seconds",\$err);
			$mysql_date = &UnixDate($m_timestamp,"%Y-%m-%d %H:%M:%S");
			$valor = $valor * $b * 10;
			$valor = sprintf("%.2f", $valor);
			$valor =~ s/\,/\./g; 
        		if (($a % 20) == 0) {
				print "\r   -> ".int($a / ($iterations / 100))."% generated for ($target_name)                                                     ";
			}
			pandora_lastagentcontact($pa_config, $mysql_date, $agent_name, "none","1.2", $target_interval, $dbh);
			# print LOG $mysql_date, $target_name, $valor, "\n";
			pandora_writedata($pa_config,$mysql_date,$agent_name,$target_type,$target_name,$valor,0,0,"",$dbh,\$bUpdateDatos);			
			pandora_writestate ($pa_config,$agent_name,$target_type,$target_name,$valor,100,$dbh,$bUpdateDatos);
			}
	}

	# Generate pseudo-random data for changing drawings
	if ( $target_name =~ /random/i ){
		# Random values over line a static line
		for ($a=1;$a<$iterations;$a++){
        		$valor = rand(15) + rand(15) + rand(15) + rand(15) + rand(15) + rand(15);
			$valor = sprintf("%.2f", $valor);
			$valor =~ s/\,/\./g; 
			$m_timestamp = DateCalc($m_timestamp,"+ $target_interval seconds",\$err);
			$mysql_date = &UnixDate($m_timestamp,"%Y-%m-%d %H:%M:%S");
			if ($a % 20 == 0) {
				print "\r   -> ".int($a / ($iterations / 100))."% generated for ($target_name)                                                     ";
			}
			pandora_lastagentcontact($pa_config, $mysql_date, $agent_name, "none","1.2", $target_interval, $dbh);
			#print LOG $mysql_date, $target_name, $valor, "\n";
			pandora_writedata($pa_config,$mysql_date,$agent_name,$target_type,$target_name,$valor,0,0,"",$dbh,\$bUpdateDatos);			
			pandora_writestate ($pa_config,$agent_name,$target_type,$target_name,$valor,100,$dbh,$bUpdateDatos);
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
			$m_timestamp = DateCalc($m_timestamp,"+ $target_interval seconds",\$err);
			$mysql_date = &UnixDate($m_timestamp,"%Y-%m-%d %H:%M:%S");
			if ($a % 20 eq 0) {
				print "\r   -> ".int($a / ($iterations / 100))."% generated for ($target_name)                                                     ";
			}
			pandora_lastagentcontact($pa_config, $mysql_date, $agent_name, "none","1.2", $target_interval, $dbh);
			#print LOG $mysql_date, $target_name, $valor, "\n";
			pandora_writedata($pa_config,$mysql_date,$agent_name,$target_type,$target_name,$valor,0,0,"",$dbh,\$bUpdateDatos);
			pandora_writestate ($pa_config,$agent_name,$target_type,$target_name,$valor,$valor,$dbh,$bUpdateDatos);
		}

	}
	
	# Generate pseudo-random data for boolean data
	if ( $target_name =~ /text/i ){
		for ($a=1;$a<$iterations;$a++){
			$valor = pandora_trash_ascii (rand(100)+50);
			$m_timestamp = DateCalc($m_timestamp,"+ $target_interval seconds",\$err);
			$mysql_date = &UnixDate($m_timestamp,"%Y-%m-%d %H:%M:%S");
			if ($a % 20 eq 0) {
				print "\r   -> ".int($a / ($iterations / 100))."% generated for ($target_name)                                                     ";
			}
			pandora_lastagentcontact($pa_config, $mysql_date, $agent_name, "none","1.2", $target_interval, $dbh);
			#print LOG $mysql_date, $target_name, $valor, "\n";
			pandora_writedata($pa_config,$mysql_date,$agent_name,$target_type,$target_name,$valor,0,0,"",$dbh,\$bUpdateDatos);
			pandora_writestate ($pa_config,$agent_name,$target_type,$target_name,$valor,100,$dbh,$bUpdateDatos);
		}
	}

	close (LOG);
	print "\n";
}
