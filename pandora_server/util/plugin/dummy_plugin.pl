#!/usr/bin/perl

use strict;
use warnings;

use Data::Dumper;

open STDOUT, '>', "/var/log/pandora/pandora_server.log";

print "------------INIT DUMMY PLUGIN------------------\n";
print Dumper(@ARGV) . "\n";
print "------------END DUMMY PLUGIN------------------\n";

close(STDOUT);

exit 1;