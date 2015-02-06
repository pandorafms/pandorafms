#!/usr/bin/perl -w

##################################################################################
# Inventory Change detector Plugin for Pandora FMS 3.0
# (c) Sancho Lerena 2011, slerena@gmail.com
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
my $temporal_hashdir = "/tmp";

my $cfg_file = "/etc/pandora/pandora_server.conf";
my $servername = "";
my $agentname = "";
my $modulename = "";
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
	print "\nPandora FMS Inventory Change detector for Pandora FMS\n\n";
	print "Syntax: \n\n\t ./pandora_inventory_change.pl [-c /etc/pandora/pandora_server.conf] -a agent_name -m module_name [ -q ] \n\n";

	print "\t -c It's optional, by default read Pandora FMS config file at /etc/pandora\n\n";
	print "Sample usage:\n\n\t ./pandora_inventory_change.pl -a myagent -m Cisco_Config\n\n";
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
	open (CFG, "$cfgfile") or die "Cannot open $cfgfile. Aborting \n\n";
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
		elsif ($parametro =~ m/^dbport\s(.*)/i) { 
			$pa_config->{'dbport'}= clean_blank($1); 
		}
	} # end of loop for parameter #
	$pa_config->{'dbport'} = 3306 unless exists $pa_config->{'dbport'};
}

sub simple_sql ($$){
    my $dbh = $_[0];
    my $sql_query = $_[1];

    my $value = "";
	my $idag = $dbh->prepare($sql_query);
	$idag ->execute;
	my @datarow;
	if ($idag->rows != 0) {
		@datarow = $idag->fetchrow_array();
		$value = $datarow[0];
	}
    return $value;
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
        "c=s" => \$cfg_file,
        "a=s" => \$agentname,
    	"m=s" => \$modulename
);

load_config ($cfg_file, \%pa_config);

alarm($timeout);

$SIG{ALRM} = sub { die_return_timeout(); };

eval {

	# Connect to MySQL
	my $dbh = DBI->connect("DBI:mysql:".$pa_config{'dbname'}.":".$pa_config{"dbhost"}.":".$pa_config{"dbport"}, $pa_config{"dbuser"}, $pa_config{"dbpass"}, { RaiseError => 1, AutoCommit => 1 });
	return undef unless defined ($dbh);

	my $sql_query = "SELECT md5(data) FROM tagente, tmodule_inventory, tagent_module_inventory 
WHERE tagente.nombre = '$agentname' AND tagente.id_agente = tagent_module_inventory.id_agente 
AND tmodule_inventory.name = '$modulename';";
    my $result = simple_sql ($dbh, $sql_query);

    # No valid data
    if ($result eq ""){
        exit;
    }

    my $full_filename = $temporal_hashdir . "/inv_".$agentname."_".$modulename;

    if ( ! -f $full_filename){
        open (FX, ">$full_filename");
        print FX $result;
        close (FX);
        print "1\n";
        exit

    } else {
        open (FX, "$full_filename");
        my $old_value = <FX>;
        close (FX);
    
        if ($old_value eq $result){
            print "1\n";
            exit;
        } else {
            open (FX, ">$full_filename");
            print FX $result;
            close (FX);
            print "0\n";
            exit
        }
    }
};

if ($@){
	die_return_timeout();
}

# -----------------------------------------------------------------------
# End main code ---------------------------------------------------------
# -----------------------------------------------------------------------

