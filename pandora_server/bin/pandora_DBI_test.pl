#!/usr/bin/perl
use strict;
use warnings;

use DBI;     # DB interface with MySQL

while (1){
	keep_alive_check();
}

sub keep_alive_check {
	my $dbh = DBI->connect("DBI:mysql:pandora:localhost:3306","pandora","pandora",{ RaiseError => 1, AutoCommit => 1 });
	$dbh->disconnect;
}
