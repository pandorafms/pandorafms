#!/usr/bin/perl

###############################################################################
# Pandora FMS DB Management
###############################################################################
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
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301,USA
###############################################################################

# Includes list
use strict;
use Time::Local;			# DateTime basic manipulation
use DBI;				# DB interface with MySQL
use Date::Manip;			# Date/Time manipulation

# version: define la version actual del programa
my $version = "1.3 PS080327";

# Setup variables
my $dirname="";
my $dbname = 'pandora';
my $dbhost ='';
my $dbuser ='';
my $verbosity =0;
my $dbpass='';
my $server_threshold='';
my $log_file="";
my $pandora_path="";
my $config_days_compact;
my $config_days_purge;
my $config_step_compact;# Step compact variable defines "how-fine" is thecompact algorithm. 1 Hour its very fine, 24 hours is bad value

# FLUSH in each IO
$| = 1;

pandora_init();

# Read config file for Global variables
pandora_loadconfig ($pandora_path);

# Begin pandora_server
pandoradb_main();

###############################################################################
###############################################################################
###############################################################################
###############################################################################
###############################################################################

###############################################################################
## SUB pandora_purgedb (days, dbname, dbuser, dbpass, dbhost)
###############################################################################
sub pandora_purgedb {

	# 1) Obtain last value for date limit
	# 2) Delete all elements below date limit
	# 3) Insert last value in date_limit position

	my $days = $_[0];
	my $dbname = $_[1];
	my $dbuser = $_[2];
	my $dbpass = $_[3];
	my $dbhost = $_[4];
 	my @query;
 	my $counter;
	my $buffer; my $buffer2; my $buffer3;
	my $err; # error code in datecalc function
 	my $dbh = DBI->connect("DBI:mysql:$dbname:$dbhost:3306",$dbuser, $dbpass,{RaiseError => 1, AutoCommit => 1 });
 	# Calculate limit for deletion, today - $days
 	my $limit_timestamp = DateCalc("today","-$days days",\$err);
	my $limit_timestamp2 = DateCalc($limit_timestamp,"+1 minute",\$err);
 	$limit_timestamp = &UnixDate($limit_timestamp,"%Y-%m-%d %H:%M:%S");
	my $timestamp = &UnixDate("today","%Y-%m-%d %H:%M:%S");
	my $limit_access = DateCalc("today","-24 hours",\$err);
	$limit_access = &UnixDate($limit_access,"%Y-%m-%d %H:%M:%S");
	print "[PURGE] Deleting old access data... \n";
	$dbh->do("DELETE FROM tagent_access WHERE timestamp < '$limit_access'");

	print "[PURGE] Deleting old data... \n";
	# Lets insert the last value on $limit_timestamp + 1 minute for each id_agente_modulo
	my $query_idag = "select count(distinct(id_agente_modulo)) from tagente_datos where timestamp < '$limit_timestamp'";
	my $idag = $dbh->prepare($query_idag);
	$idag ->execute;
	my @datarow;
	@datarow = $idag->fetchrow_array();
	if ($verbosity > 0){
		print "[PURGE] Total different Modules delete: ".$datarow[0]."\n";
	}
	my $different_modules = $datarow[0];
	$idag->finish;

	my $query_idag = "select distinct(id_agente_modulo) from tagente_datos WHERE timestamp < '$limit_timestamp'";
	my $idag = $dbh->prepare($query_idag);
	$idag ->execute;
	my @datarow;
	if ($idag->rows != 0) {
		my $counter =0;
		while (@datarow = $idag->fetchrow_array()) {
			$counter++;
			if ($verbosity > 0){
				print "\r[PURGE] ".$counter / ($different_modules / 100)."% Deleted";
			}
			$buffer = $datarow[0];
			my $query_idag2 = "select * from tagente_datos where timestamp < '$limit_timestamp' and id_agente_modulo = $buffer order by timestamp desc limit 1";

			my $idag2 = $dbh->prepare($query_idag2);
			$idag2 ->execute;
			my @datarow2;
			if ($idag2->rows != 0) {
				while (@datarow2 = $idag2->fetchrow_array()) {
					# Create Insert SQL for this data 
					$buffer3 = "insert into tagente_datos (id_agente_modulo,datos,timestamp,id_agente) values ($buffer,$datarow2[2],'$limit_timestamp',$datarow2[4])";
				}
			}
			# Execute DELETE
			my $query_idag3 = "delete from tagente_datos where timestamp < '$limit_timestamp' and id_agente_modulo = $buffer ";
			my $idag3 = $dbh->prepare($query_idag3);
			$idag3->execute;
			$idag3->finish();

			# Execute INSERT with last data
			my $idag3 = $dbh->prepare($buffer3);
			$idag3->execute;
			$idag3->finish();

			$idag2->finish();
		}
	}
	$idag->finish();
	if ($verbosity > 0){	
		print "[PURGE] Deleting static data until $limit_timestamp \n";
	}
 	$dbh->do ("delete from tagente_datos_string where timestamp < '$limit_timestamp'");
        $dbh->disconnect();
}

###############################################################################
## SUB pandora_compactdb (days, dbname, dbuser, dbpass, dbhost)
###############################################################################
sub pandora_compactdb {
	my $days = $_[0];
	my $dbname = $_[1];
	my $dbuser = $_[2];
	my $dbpass = $_[3];
	my $dbhost = $_[4];
 	my @data_item; # Array to get data from each record of DB
 	my %data_list; # Hash to store values (sum) for each id
 	my %data_list_items; # Hash to store total values for each id
	my $err; # error code in datecalc function

 	my $rows_selected = 0; # Calculate how many rows are selected in this loop
 	my $dbh; # database handler
 	my $query;
 	my $query_ready;
 	my $limit_timestamp; # Define the high limit for timestamp query
	my $limit_timestamp_numeric;
 	my $low_limit; # Define the low limit for timestamp query
 	my $key; # Used by foreach-loop
	my $low_limit_timestamp; # temporal variable to store low limit timestamp
	my $low_limit_timestamp_numeric;
	my $oldest_timestamp;
	my $oldest_timestamp_numeric;
	my $flag; #temporal value to store diff between dats
	
	# Begin procedure (SQL open initizalizacion and initial timestamp calculation)
	$dbh = DBI->connect("DBI:mysql:$dbname:$dbhost:3306",$dbuser, $dbpass,{ RaiseError => 1, AutoCommit => 1 });
	
	
	# Get the first (oldest) timestamp in database to make it the marker for the end of query
	$query = "SELECT min(timestamp) FROM tagente_datos ";
	$query_ready = $dbh->prepare($query);
	$query_ready ->execute();
	@data_item = $query_ready->fetchrow_array();
	$oldest_timestamp = @data_item[0];
	$query_ready->finish;
	$oldest_timestamp_numeric = &UnixDate($oldest_timestamp,"%s");

	# If no data, skip this step
	if ($oldest_timestamp ne ""){
		# We need to determine data ranges
		# Calculate start limit for compactation
		$limit_timestamp = DateCalc("today","-$days days",\$err);
		$limit_timestamp = &UnixDate($limit_timestamp,"%Y-%m-%d %H:%M:%S");
		$limit_timestamp_numeric = &UnixDate($limit_timestamp,"%s");
		print "[COMPACT] Packing data from $limit_timestamp to $oldest_timestamp \n";
		
		# Main loop
		do { # Makes a query for each days from $limit_timestamp (now is set $days from today)
			# To get actual low limit, minus step_compact hours
			$low_limit_timestamp = DateCalc("$limit_timestamp","-$config_step_compact hours",\$err);
			$low_limit_timestamp = &UnixDate($low_limit_timestamp,"%Y-%m-%d %H:%M:%S");
			$low_limit_timestamp_numeric = &UnixDate($low_limit_timestamp,"%s");
			if ($verbosity > 0){
				print "[COMPACT] Working at interval: $limit_timestamp -$low_limit_timestamp \n";
			}

			# DB Query to get data from DB based on timestamp limits
			$query = "SELECT * FROM tagente_datos WHERE utimestamp < $limit_timestamp_numeric AND utimestamp >= $low_limit_timestamp_numeric";
			$query_ready = $dbh->prepare($query);
			$query_ready ->execute();
			$rows_selected = $query_ready->rows;

			if ($rows_selected > 0) {
				# Init hashes
				%data_list=();
				%data_list_items=();
				# Create a hash data_list with id_agente_mopulo as index
				# and creating a summatory of values for this inverval
				# storing the total number of dif. values in data_list_items hash
				while (@data_item = $query_ready->fetchrow_array()) {
					$data_list{$data_item[1]}= $data_list{$data_item[1]} + $data_item[2];
					$data_list_items{$data_item[1]}=$data_list_items{$data_item[1]} + 1;
				}
				# Once we has filled the hast, let's delete records processed for this
				# interval. Later we could insert the new record, and initialize hash for 
				# reuse it in the next loop.
				
				$query = "DELETE FROM tagente_datos WHERE utimestamp < $limit_timestamp _numeric AND utimestamp >= $low_limit_timestamp_numeric";
				$dbh->do($query);
				# print "DEBUG: Purge query $query \n";

				my $value; my $value_timestamp;
				foreach $key (keys (%data_list)) {
					$value = int($data_list{$key} / $data_list_items{$key}); # Media aritmetica :-)
					$query="INSERT INTO tagente_datos (id_agente_modulo, datos, timestamp, utimestamp) VALUES ($key, $value, '$limit_timestamp', $limit_timestamp_numeric)";
					$dbh->do($query);
					#if ($verbosity > 0){
print "[DEBUG]: Datos para el id_agente_modulo # $key : Numero de datos ( $data_list_items{$key} ) valor total ( $data_list{$key} media ($value)) \n";
					#}
					# Purge hash
					delete $data_list{$key};
					delete $data_list_items{$key};
				}
			}
			$limit_timestamp = $low_limit_timestamp; # To the next day !!
			$flag = Date_Cmp($oldest_timestamp,$limit_timestamp);
		} until ($flag >= 0);
	} else {
		print "[COMPACT] No data to pack ! \n";
	}
	$query_ready->finish();
	$dbh->disconnect();
}

##############################################################################
# SUB pandora_init ()
# Makes the initial parameter parsing, initializing and error checking
##############################################################################

sub pandora_init {
	print "\nPandora FMS DB Tool $version Copyright (c) 2004-2007 Sancho Lerena\n";
	print "This program is Free Software, licensed under the terms of GPL License v2\n";
	print "You can download latest versions and documentation at http://pandora.sf.net\n";

	# Load config file from command line
	if ($#ARGV == -1 ){
		print "FATAL ERROR: I Need at least one parameter: Complete path to pandora_server.conf file !!\n\n";
		exit;
	}
   
	# If there are not valid parameters
	my $parametro;
	my $ltotal=$#ARGV; my $ax;
	for ($ax=0;$ax<=$ltotal;$ax++){
		$parametro = $ARGV[$ax];
		if ($parametro =~ m/-h\z/i ) { help_screen();  }
			elsif ($parametro =~ m/-help\z/i ) { help_screen();  }
			elsif ($parametro =~ m/--help\z/i ) { help_screen();  }
		elsif ($parametro =~ m/-v\z/i) { $verbosity=5; }
		elsif ($parametro =~ m/-d\z/i) { $verbosity=10; }
		elsif ($parametro =~ m/-d\z/i) { $verbosity=0; }
		else { ($pandora_path = $parametro); }
	}
	if ($pandora_path eq ""){
		print "FATAL ERROR: I Need complete path to pandora_server.conf file !!\n\n";
		exit;
	}
}


##############################################################################
# Read external configuration file
##############################################################################

sub pandora_loadconfig {
	my $archivo_cfg = @_[0];
	my $buffer_line;
	my @command_line;
	# Check for file
	if ( ! -e $archivo_cfg ) {
		printf "[ERROR] Cannot open configuration file. Please specify a valid one in command line \n";
		exit 1;
	}

	# Collect items from config file and put in an array 
	open (CFG, "< $archivo_cfg");
	while (<CFG>){
		$buffer_line = $_;
		if ($buffer_line =~ m/([\w-_\.]+)\s([0-9\w-_\.\/\?\&\=\)\(\_\-\\*\@\#\%\$\~\"\']+)/){
			push @command_line,$1;
		push @command_line,$2;
		}
	}
 
 
 	close (CFG);
 	# Process this array with commandline like options 
 	# Process input parameters
 	my @args = @command_line;
 	my $parametro;
 	my $ltotal=$#args; my $ax;

 	# Has read setup file ok ?
 	if ( $ltotal == 0 ) {
  		print "[ERROR] No valid setup tokens readed in $archivo_cfg ";
  		exit;
 	}
 
 	for ($ax=0;$ax<=$ltotal;$ax++){
  		$parametro = $args[$ax];
  		if ($parametro =~ m/dirname\z/) {  $dirname = $args[$ax+1]; $ax++; } 
  		elsif ($parametro =~ m/dbuser\z/) { $dbuser  = $args[$ax+1]; $ax++; } 
  		elsif ($parametro =~ m/dbpass\z/) { $dbpass = $args[$ax+1]; $ax++; }
  		elsif ($parametro =~ m/dbname\z/) { $dbname = $args[$ax+1]; $ax++; }
  		elsif ($parametro =~ m/dbhost\z/) { $dbhost  = $args[$ax+1]; $ax++; } 
  		elsif ($parametro =~ m/log_file\z/) { $log_file = $args[$ax+1]; $ax++; } 
  		elsif ($parametro =~ m/server_threshold\z/) { $server_threshold = $args[$ax+1]; $ax++; }
 	}
 
 	# Check for valid token token values
 	if (( $dbuser eq "" ) || ( $log_file eq "" ) || ( $dbhost eq "")  || ($dbpass eq "" ) ) {
  		print "[ERROR] Bad Config values. Be sure that $archivo_cfg is a valid setup file";
		print "\n\n";
  		exit;
 	}
	
	# Open database to get days_purge days_compact values
	my $query; my $query_ready; my @data; my $rows_selected;
	my $dbh = DBI->connect("DBI:mysql:pandora:$dbhost:3306",$dbuser, $dbpass, {RaiseError => 1, AutoCommit => 1 });
	$query = "select * from tconfig where token = 'days_purge'";
	$query_ready = $dbh->prepare($query);
	$query_ready ->execute();
	$rows_selected = $query_ready->rows;
	if ($query_ready->rows > 0) {
		@data = $query_ready->fetchrow_array();
		$config_days_purge = $data[2]; # value
	} else {
		print "[ERROR] I cannot find in database a config item (DAYS_PURGE)\n";
		exit(-1);
	}
	$query_ready->finish();
	
	$query = "select * from tconfig where token = 'days_compact'";
	$query_ready = $dbh->prepare($query);
	$query_ready ->execute();
	$rows_selected = $query_ready->rows;
	if ($query_ready->rows > 0) {
		@data = $query_ready->fetchrow_array();
		$config_days_compact = $data[2]; # value
	} else {
		print "[ERROR] I cannot find in database a config item (DAYS_COMPACT)\n";
		exit(-1);
	}
	$query_ready->finish();
	
	$query = "select * from tconfig where token = 'step_compact'";
	$query_ready = $dbh->prepare($query);
	$query_ready ->execute();
	$rows_selected = $query_ready->rows;
	if ($query_ready->rows > 0) {
		@data = $query_ready->fetchrow_array();
		$config_step_compact = $data[2]; # value
	} else {
		print "[ERROR] I cannot find in database a config item (CONFIG_STEP_COMPACT)\n";
		exit(-1);
	}
		
	$query_ready->finish();
	$dbh->disconnect;
	
  	printf "Pandora DB now initialized and running (PURGE=$config_days_purge days, COMPACT=$config_days_compact days, STEP=$config_step_compact) ... \n\n";
}
	
###############################################################################
## SUB pandora_checkdb_consistency (dbname, dbuser, dbpass, dbhost)
###############################################################################
sub pandora_checkdb_consistency {

	# 1. Check for modules that do not have tagente_estado but have tagente_module
	
    my $dbname = $_[0];
    my $dbuser = $_[1];
    my $dbpass = $_[2];
    my $dbhost = $_[3];
 	my @query;
 	my $counter;
	my $err; # error code in datecalc function
 	my $dbh = DBI->connect("DBI:mysql:$dbname:$dbhost:3306",$dbuser, $dbpass,{RaiseError => 1, AutoCommit => 1 });

	print "[CHECKDB] Checking database consistency (step1)... \n";
	my $query1 = "SELECT * FROM tagente_modulo";
	my $prep1 = $dbh->prepare($query1);
	$prep1 ->execute;
	my @datarow1;
	if ($prep1->rows != 0) {
		# for each record in tagente_modulo
		while (@datarow1 = $prep1->fetchrow_array()) {
			my $id_agente_modulo = $datarow1[0];
			# check if exist in tagente_estado and create if not
			my $query2 = "SELECT * FROM tagente_estado WHERE id_agente_modulo = $id_agente_modulo";
			my $prep2 = $dbh->prepare($query2);
			$prep2->execute;
			# If have 0 items, we need to create tagente_estado record
			if ($prep2->rows == 0) {
				my $id_agente = $datarow1[1];
				my $query3 = "INSERT INTO tagente_estado (id_agente_modulo, datos, timestamp, cambio, estado, id_agente, last_try, utimestamp, current_interval, running_by, last_execution_try) VALUE ($id_agente_modulo, 0, '0000-00-00 00:00:00', 0, 100, $id_agente, '0000-00-00 00:00:00', 0, 0, 0, 0)";
				print "[CHECKDB] Inserting module $id_agente_modulo in state table \n";
				my $prep3 = $dbh->prepare($query3);
				$prep3->execute;
				$prep3->finish();
			}
			$prep2->finish();
		}
	}
	$prep1->finish();
	
	print "[CHECKDB] Checking database consistency (step2)... \n";
	# 2. Check for modules in tagente_estado that do not have tagente_modulo, if there is any, delete it
	my $query1 = "SELECT * FROM tagente_estado";
	my $prep1 = $dbh->prepare($query1);
	$prep1 ->execute;
	my @datarow1;
	if ($prep1->rows != 0) {
		# for each record in tagente_modulo
		while (@datarow1 = $prep1->fetchrow_array()) {
			my $id_agente_modulo = $datarow1[1];
			# check if exist in tagente_estado and create if not
			my $query2 = "SELECT * FROM tagente_modulo WHERE id_agente_modulo = $id_agente_modulo";
			my $prep2 = $dbh->prepare($query2);
			$prep2->execute;
			# If have 0 items, we need to create tagente_estado record
			if ($prep2->rows == 0) {
				my $id_agente = $datarow1[1];
				my $query3 = "DELETE FROM tagente_estado WHERE id_agente_modulo = $id_agente_modulo";
				print "[CHECKDB] Deleting non-existing module $id_agente_modulo in state table \n";
				my $prep3 = $dbh->prepare($query3);
				$prep3->execute;
				$prep3->finish();
			}
			$prep2->finish();
		}
	}
	$prep1->finish();
	
	print "[CHECKDB] Deleting non-init data... \n";
	my $query4 = "SELECT * FROM tagente_estado WHERE utimestamp = 0";
	my $prep4 = $dbh->prepare($query4);
	$prep4 ->execute;
	my @datarow4;
	if ($prep4->rows != 0) {
		# for each record in tagente_modulo
		while (@datarow4 = $prep4->fetchrow_array()) {
			my $id_agente_modulo = $datarow4[1];
			my $query0 = "DELETE FROM tagente_modulo WHERE id_agente_modulo = $id_agente_modulo";
			my $prep0 = $dbh->prepare($query0);
			$prep0 ->execute;
			$prep0->finish();
		}
	}
	$prep4->finish();
	
	# Delete from tagente_estado	
	my $query0 = "DELETE FROM tagente_estado WHERE utimestamp = 0";
	my $prep0 = $dbh->prepare($query0);
	$prep0 ->execute;
	$prep0->finish();

	print "[CHECKDB] Deleting agentless data... \n";
	# Delete from tagente_datos with id_agente = 0
	$dbh->do ("DELETE FROM tagente_datos WHERE id_agente = 0");
	$dbh->do ("DELETE FROM tagente_datos_string WHERE id_agente = 0");
}

##############################################################################
# SUB help_screen()
#  Show a help screen an exits
##############################################################################

sub help_screen{
	printf "\n\nSintax: \n pandora_db.pl  fullpathname_to_pandora_server.conf \n\n";
	print "             -d   Debug output (very verbose) \n";
	print "             -v   Verbose output \n";
	print "             -q   Quiet output \n";
	exit;
}

#
###############################################################################
# Program main begin 
#
###############################################################################
sub pandoradb_main {
	pandora_purgedb ($config_days_purge, $dbname, $dbuser, $dbpass, $dbhost);
	pandora_checkdb_consistency ($dbname, $dbuser, $dbpass, $dbhost);
	# pandora_compactdb ($config_days_compact, $dbname, $dbuser, $dbpass, $dbhost);
	print "\n";
	exit;
}
