#!/usr/bin/perl
##########################################################################
# Pandora Recon Server
##########################################################################
# Copyright (c) 2007 Sancho Lerena, slerena@gmail.com
# Copyright (c) 2007 Artica Soluciones Tecnologicas S.L
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; version 2
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
##########################################################################

# Includes list
use strict;
use warnings;

use Date::Manip;                	# Needed to manipulate DateTime formats
					# of input, output and compare
use Time::Local;                	# DateTime basic manipulation
use Net::Ping;				# ICMP
use NetAddr::IP;			# To manage IP Addresses
#use IO::Socket;				# For TCP/UDP access
use threads;

# Pandora Modules
use pandora_config;
use pandora_tools;
use pandora_db;

# FLUSH in each IO (only for debug, very slooow)
# ENABLED in DEBUGMODE
# DISABLE FOR PRODUCTION
$| = 1;

my %pa_config;

# Inicio del bucle principal de programa
pandora_init(\%pa_config, "Pandora FMS Recon server");
# Read config file for Global variables
pandora_loadconfig (\%pa_config,3);
# Audit server starting
pandora_audit (\%pa_config, "Pandora FMS Recon Daemon starting", "SYSTEM", "System");

# Daemonize of configured
if ( $pa_config{"daemon"} eq "1" ) {
	print " [*] Backgrounding...\n";
	&daemonize;
}

# Runs main program (have a infinite loop inside)

threads->new( \&pandora_recon_subsystem, \%pa_config, 1);
sleep(1);
#threads->new( \&pandora_network_subsystem, \%pa_config, 2);
#sleep(1);
#threads->new( \&pandora_network_subsystem, \%pa_config, 3);

while ( 1 ){
	sleep(3600);
 	threads->yield;
}

#------------------------------------------------------------------------------------
#------------------------------------------------------------------------------------
#------------------------------------------------------------------------------------
#---------------------  Main Perl Code below this line-----------------------
#------------------------------------------------------------------------------------
#------------------------------------------------------------------------------------
#------------------------------------------------------------------------------------

##########################################################################
# SUB pandora_recon_subsystem
# This module runs each X seconds (server threshold) checking for new
# recon tasks pending to do
##########################################################################

sub pandora_recon_subsystem {
        # Init vars
	my $pa_config = $_[0];
	my $nettype = $_[1]; # 1 for ICMP, 2 for TCP/UDO, 3 for SNMP
	# Connect ONCE to Database, we pass DBI handler to all subprocess.
	my $dbh = DBI->connect("DBI:mysql:pandora:$pa_config->{'dbhost'}:3306", $pa_config->{'dbuser'}, $pa_config->{'dbpass'}, { RaiseError => 1, AutoCommit => 1 });


	my $server_id = dame_server_id($pa_config, $pa_config->{'servername'}."_Net", $dbh);
	my $target_network;		# Network range defined in database task
	my $target_mode; 		# 1 for netmask/bit, 2 for range of IP separated by -
	my $target_ip; 			# Real ip to check
	my @ip2; 			# temp array for NetAddr::IP
	my $space;			# temp var to store space of ip's for netaddr::ip
	my $query_sql; 			# for use in SQL
	my $exec_sql; 			# for use in SQL
	my @sql_data;			# for use in SQL 
	
	while ( 1 ) {
		logger ($pa_config, "Loop in Recon Module Subsystem", 10);
		$query_sql = "SELECT * FROM trecon_task WHERE id_network_server = $server_id ";
		$exec_sql = $dbh->prepare($query_sql);
		$exec_sql ->execute;
		while (@sql_data = $exec_sql->fetchrow_array()) {
			my $my_timestamp = &UnixDate("today","%Y-%m-%d %H:%M:%S");
			my $my_utimestamp = &UnixDate($my_timestamp, "%s"); # convert from human to integer
			my $utimestamp = $sql_data[9];
			my $status = $sql_data[10];
			my $interval = $sql_data[11];
			$interval = $interval * 60; # Interval is stored in MINUTES !
    			$target_network = $sql_data[4];
    			my $id_task = $sql_data[0];
    			my $position = 0;
			# Need to exec this task ?
			if (($utimestamp + $interval) < $my_utimestamp){
   				# EXEC TASK and mark as "in progress" != -1
   				pandora_update_reconstatus ($pa_config, $dbh, $id_task, 0);
				
    		  		if ( $target_network =~ /[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+\/[0-9]+\z/){
    		  			$target_mode=1; # Netmask w/bit
    		  		} 
	  			elsif ( $target_network =~ 	/[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+\-[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+\z/){
	  				$target_mode=2; # Range of iPs
	  			}
				# Asign target dir to netaddr object "space"
				$space = new NetAddr::IP $target_network;
				my $total_hosts= $space->num +1 ;
				# Begin scanning main loop
				do {
					@ip2 = split(/\//,$space);
					$target_ip = $ip2[0];
					$space++;
					$position++;
					if (scan_icmp ($target_ip, $pa_config->{'networktimeout'}) == 1){
						printf ("IP $target_ip VIVA !!! \n");
						if (pandora_check_ip ($pa_config, $dbh, $target_ip) == 0){
							printf ("	IP $target_ip NO MONITORIZADA !!! \n");
						} else {
							printf ("	IP $target_ip monitorizada\n");
						}
					} else {
						printf ("IP $target_ip no contesta \n");
					}
					#my $progress = ceil($position / ($total_hosts / 100));
					#pandora_update_reconstatus ($pa_config, $dbh, $id_task, $progress);
				} while ($space < $space->broadcast); # fin del buclie principal de iteracion de Ips
				
			}
			# Mark RECON TASK as done (-1)
			pandora_update_reconstatus ($pa_config, $dbh, $id_task, -1);
      		}
      		$exec_sql->finish();
	}
}

##############################################################################
# escaneo_icmp (destination, timeout) - Do a ICMP scan 
##############################################################################
 
sub scan_icmp {
	my $p;
	my $dest = $_[0];
	my $l_timeout = $_[1];

	$p = Net::Ping->new("icmp",$l_timeout);
	if ($p->ping($dest)) {
		$p->close();  
		return 1;
	} else {
		$p->close();  
	     	return 0;
	}
}

##########################################################################
# SUB pandora_check_ip (pa_config, dbh, ip_address)
# Return 1 if this IP exists, 0 if not
##########################################################################
sub pandora_check_ip {
	my $pa_config = $_[0];
	my $dbh = $_[1];
	my $ip_address = $_[2];

	my $query_sql = "SELECT * FROM taddress WHERE ip = '$ip_address' ";
	my $exec_sql = $dbh->prepare($query_sql);
	$exec_sql ->execute;
	if ($exec_sql->rows != 0) {
		$exec_sql->finish();
		return 1;
	} else {
		$exec_sql->finish();
		return 0;
	}
}

##########################################################################
# SUB pandora_update_reconstatus (pa_config, dbh, ip_address)
# Update recontask
##########################################################################
sub pandora_update_reconstatus {
	my $pa_config = $_[0];
	my $dbh = $_[1];
	my $id_task = $_[2];
	my $status  = $_[3];

	my $query_sql2 = "UPDATE trecon_task SET status = $status WHERE id_rt = $id_task";
	my $exec_sql2 = $dbh->prepare($query_sql2);
	$exec_sql2 -> execute;
	$exec_sql2 -> finish();
}
