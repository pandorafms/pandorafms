#!/usr/bin/perl -w

##################################################################################
# Fake data generator Plugin for Pandora FMS 3.0
# (c) Sancho Lerena 2009, slerena@gmail.com
# idea from Miguel de Dios, sorry dude, I made it first ! ;-))
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

my $max = 10;
my $min = 0;
my $alphanumeric = 0;

# -- Subroutine / Functions ------------------------------------

sub help {
	print "\nFake data generator plugin for Pandora FMS\n\n";
	print "Syntax: \n\n\t ./pandora_loadgen.pl [-string] -max <max_value> -min <min_value> \n\n";
	print "Sample usage:\n\n\t ./pandora_loadgen.pl -t 0 -max 100 -min 0\n\n";
	print "\tIf -string provided, it generates an alphanumeric string with min-max lenght\n";
	print "\totherwise it generates a random integer from min to max\n\n";

}

sub pandora_trash_ascii {
	my $config_depth = $_[0];
	my $a;
	my $output = "";

	for ($a=0;$a<$config_depth;$a++){
		$output = $output.chr(int(rand(25)+97));
	}
	return $output
}

sub pandora_random {
	my $min = $_[0];
	my $max = $_[1];

	return int(rand($max+1) + $min);
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
        "string+" => \$alphanumeric,
        "max=i" => \$max,
	"min=i" => \$min
);

if ($alphanumeric == 1){
	print pandora_trash_ascii (pandora_random($min,$max));
} 
else {
	print pandora_random ($min, $max);
}
exit 0;

# -----------------------------------------------------------------------
# End main code ---------------------------------------------------------
# -----------------------------------------------------------------------

