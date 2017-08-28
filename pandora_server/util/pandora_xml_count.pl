#!/usr/bin/perl
################################################################################
# Pandora XML count tool.
################################################################################
# Copyright (c) 2017 Artica Soluciones Tecnologicas S.L.
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
use strict;
use warnings;

# Check command line arguments.
if (!defined($ARGV[0])) {
	die("Usage: $0 <path to Pandora FMS's spool directory>\n\n");
}
my $spool_dir = $ARGV[0];

# Open Pandora's spool directory.
opendir(my $dh, $spool_dir) || die("Error opening directory $spool_dir: $!\n\n");

# Count files by agent.
my %totals;
while (my $file = readdir($dh)) {
	
	# Skip . and ..
	next if ($file eq '.') or ($file eq '..');

	# Skip files unknown to the Data Server.
	next if ($file !~  /^(.*)[\._]\d+\.data$/);

	# Update the totals.
	my $agent = $1;
	if (!defined($totals{$agent})) {
		$totals{$agent} = 1;
	} else {
		$totals{$agent} += 1;
	}
}
closedir($dh);

# Print the totals.
print "Number of .data files\t\tAgent name\n";
print "---------------------\t\t----------\n";
foreach my $agent (sort { $totals{$a} <=> $totals{$b}} keys(%totals)) {	
	print "$totals{$agent}\t\t\t\t$agent\n";
}
