#!/usr/bin/perl

###############################################################################
# Pandora FMS Schema comparison
###############################################################################
# Copyright (c) 2015 Artica Soluciones Tecnologicas S.L
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
use strict;
use warnings;

################################################################################
# Parse the given schema file and store the found schema in a hash.
################################################################################
sub parse_schema($) {
	my ($schema_file) = @_;
	open(SCHEMA, $schema_file) or die("Error opening schema file $schema_file: $!\n");

	my $schema_hash = {};
	while(my $line = <SCHEMA>) {
		if($line =~ m/^\s*CREATE\s+TABLE[^a-z]+([a-z_]+)/) {
			my $table = $1;
			while(my $line = <SCHEMA>) {
				next if ($line =~ m/^\s*--/); # Skip comments.
				last if ($line =~ m/;\s*$/); # End of the definition.
				if ($line =~ m/^["`'\s]+([a-z_][^"`'\s]+)["`'\s]+/) {
					$schema_hash->{$table}->{$1} = '';
				}
			}
		}
	}

	close(SCHEMA);
	return $schema_hash;
}

################################################################################
# Show tables and columns present in schema 1 but not in schema 2.
################################################################################
sub diff_schemas($$$$) {
	my ($schema_file_1, $schema_file_2, $schema_1, $schema_2) = @_;

	# Look for differences.
	while (my ($table, $columns) = each(%{$schema_1})) {

		# Check tables.
		if (!defined($schema_2->{$table})) {
			print "> Table $table defined in $schema_file_1 but not in $schema_file_2.\n";
			next;
		}

		# Check columns.
		foreach my $column (keys(%{$columns})) {
			if (!defined($schema_2->{$table}->{$column})) {
				print "> Column $column on table $table defined in $schema_file_1 but not in $schema_file_2.\n";
				next;
			}
		}
	}
}

################################################################################
################################################################################
# Main.
################################################################################
################################################################################

# Check command line parameters.
if ($#ARGV != 1) {
	die("Usage: $0 <SQL file 1> <SQL file 2>\n\n");
}
my ($sql_file_1, $sql_file_2) = @ARGV;

# Parse the schema files.
my $schema_1 = parse_schema($sql_file_1);
my $schema_2 = parse_schema($sql_file_2);

# Diff the schemas.
diff_schemas($sql_file_1, $sql_file_2, $schema_1, $schema_2);
diff_schemas($sql_file_2, $sql_file_1, $schema_2, $schema_1);

