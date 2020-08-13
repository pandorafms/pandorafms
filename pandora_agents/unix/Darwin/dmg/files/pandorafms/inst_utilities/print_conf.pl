#!/usr/bin/env perl

use strict;

my ($CONF_FILE, $token, $value) = @ARGV;
exit unless defined ($value);
exit unless -e $CONF_FILE;

open(my $fh, '<', $CONF_FILE) or die($!);
my @lines = <$fh>;
close ($fh);

# Set the new value for the configuration token.
my $found = 0;
for(my $i = 0; $i < $#lines; $i++) {
	if ($lines[$i] =~ m/[#\s]*$token/) {
		$lines[$i] = "$token $value\n";
		$found = 1;
		last;
	}
}

# Append the token to the end if it was not found in the file.
if ($found == 0) {
	push(@lines, "$token $value\n");
}

# Write the changes to the configuration file.
open($fh, '>', $CONF_FILE) or die($!);
print $fh @lines;
close($fh);
