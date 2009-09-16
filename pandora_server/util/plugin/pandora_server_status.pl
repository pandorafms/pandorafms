#!/usr/bin/perl -w

##################################################################################
# Server status Plugin for Pandora FMS 3.0
# (c) Sancho Lerena 2009, slerena@gmail.com
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
##################################################################################


use strict;
use warnings;
use Getopt::Long;
use DBI;

my %pa_config;
my $cfgfile = "/etc/pandora/pandora_server.conf";
my $servername = "";
my $servertype = 0;
my $timeout = 10;
my $queuedmodules = 0;
my $module_result;
my $sql_query;

# -- Subroutine / Functions ------------------------------------

sub die_return {
	print "0";
	exit 1;
}

sub die_return_timeout {
	print "0";
	exit -1;
}
 
sub help {
	print "\nPandora FMS Plugin for Pandora FMS server check\n\n";
	print "Syntax: \n\n\t ./pandora_server_status.pl [-c /etc/pandora/pandora_server.conf] -t <server_type> -n <server_name> [ -q ] \n\n";
	print "\t -q Return queued modules for given server name & type. \n";
	print "\t -c It's optional, by default read Pandora FMS config file at /etc/pandora\n";
	print "\n\t If -q is not given, it reports 1 if server is OK, or 0 if it's down\n\n";
	print "Sample usage:\n\n\t ./pandora_server_status.pl -t 3 -n myserver\n\n";
	print "Server types: 
\t\t--0 dataserver
\t\t--1 network
\t\t--2 snmp trap console
\t\t--3 recon
\t\t--4 plugin
\t\t--5 prediction
\t\t--6 wmi
\t\t--7 export
\t\t--8 inventory 
\t\t--9 web \n\n";

}

sub clean_blank {
	my $input = $_[0];
	$input =~ s/\s//g;
	return $input;
}


sub load_config {
	my $cfgfile = $_[0];
	my $pa_config = $_[1];
	my $buffer_line;
	my @command_line;
	my $tbuf;

	# Collect items from config file and put in an array 
	open (CFG, "< $cfgfile");
	while (<CFG>){
		$buffer_line = $_;
		if ($buffer_line =~ /^[a-zA-Z]/){ # begins with letters
			if ($buffer_line =~ m/([\w\-\_\.]+)\s([0-9\w\-\_\.\/\?\&\=\)\(\_\-\!\*\@\#\%\$\~\"\']+)/){
				push @command_line, $buffer_line;
			}
		}
	}
 	close (CFG);

 	# Process this array with commandline like options 
 	# Process input parameters

 	my @args = @command_line;
 	my $parametro;
 	my $ltotal=$#args; 
	my $ax;

 	# Has read setup file ok ?
 	if ( $ltotal == 0 ) {
  		print "[ERROR] No valid setup tokens readed in $cfgfile";
  		exit;
 	}
 
 	for ($ax=0;$ax<=$ltotal;$ax++){
  		$parametro = $args[$ax];

		if ($parametro =~ m/^incomingdir\s(.*)/i) {  
			$tbuf= clean_blank($1); 
			if ($tbuf =~ m/^\.(.*)/){
				$pa_config->{"incomingdir"} =$pa_config->{"basepath"}.$1;
			} else {
				$pa_config->{"incomingdir"} = $tbuf;
			}
		}

		elsif ($parametro =~ m/^log_file\s(.*)/i) { 
			$tbuf= clean_blank($1);	
			if ($tbuf =~ m/^\.(.*)/){
				$pa_config->{"logfile"} = $pa_config->{"basepath"}.$1;
			} else {
				$pa_config->{"logfile"} = $tbuf;
			}
		}

		elsif ($parametro =~ m/^errorlog_file\s(.*)/i) { 
			$tbuf= clean_blank($1); 	
			if ($tbuf =~ m/^\.(.*)/){
				$pa_config->{"errorlogfile"} = $pa_config->{"basepath"}.$1;
			} else {
				$pa_config->{"errorlogfile"} = $tbuf;
			}
		}

		elsif ($parametro =~ m/^dbname\s(.*)/i) { 
			$pa_config->{'dbname'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^dbuser\s(.*)/i) { 
			$pa_config->{'dbuser'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^dbpass\s(.*)/i) {
			$pa_config->{'dbpass'}= clean_blank($1); 
		}
		elsif ($parametro =~ m/^dbhost\s(.*)/i) { 
			$pa_config->{'dbhost'}= clean_blank($1); 
		}
	} # end of loop for parameter #
}


# -----------------------------------------------------------------------
# Main code -------------------------------------------------------------
# -----------------------------------------------------------------------

if ($#ARGV == -1){
	help();
}

GetOptions(
        "" => sub { help() },
        "h" => sub { help() },
        "help" => sub { help() },
        "c=s" => \$cfgfile,
        "n=s" => \$servername,
	"q+" => \$queuedmodules,
        "t=i" => \$servertype
);

load_config ($cfgfile, \%pa_config);

alarm($timeout);

$SIG{ALRM} = sub { die_return_timeout(); };

eval {

	# Connect to MySQL
	my $dbh = DBI->connect("DBI:mysql:".$pa_config{'dbname'}.":".$pa_config{"dbhost"}.":3306", $pa_config{"dbuser"}, $pa_config{"dbpass"}, { RaiseError => 1, AutoCommit => 1 });
	return undef unless defined ($dbh);

	if ($queuedmodules == 0){
		$sql_query = "SELECT status FROM tserver WHERE server_type = $servertype and name = '$servername'";
	} 
	else {
		$sql_query = "SELECT queued_modules FROM tserver WHERE server_type = $servertype and name = '$servername'";
	}
		
	my $idag = $dbh->prepare($sql_query);
	$idag ->execute;
	my @datarow;
	if ($idag->rows != 0) {
		@datarow = $idag->fetchrow_array();
		print $datarow[0] ."\n";
		exit;
	}
	print "0\n";
};

if ($@){
	die_return_timeout();
}

# -----------------------------------------------------------------------
# End main code ---------------------------------------------------------
# -----------------------------------------------------------------------

