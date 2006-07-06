#!/usr/bin/perl
##################################################################################
# DBI Memory Leak Tester
##################################################################################
# Copyright (c) 2004-2006 Sancho Lerena, slerena@gmail.com
# Copyright (c) 2005-2006 Artica Soluciones Tecnológicas S.L
#
#This program is free software; you can redistribute it and/or
#modify it under the terms of the GNU General Public License
#as published by the Free Software Foundation; either version 2
#of the License, or (at your option) any later version.
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program; if not, write to the Free Software
#Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
##################################################################################

use DBI();     # DB interface with MySQL

#$dbh = DBI->connect("DBI:mysql:pandora:localhost:3306","pandora","pandora",{ RaiseError => 1 });

while (1){
	dbd_open_test();
	#dbd_select_test($dbh);
}

sub dbd_select_test {
	my $dbh = shift;
	my $query = "select * from tagente";
	my $result = $dbh->prepare($query);
	$result ->execute;
	$result = "";
	$query = "";
	$dbh = "";
	undef $dbh;
	undef $query;
	undef $result;
}

sub dbd_open_test {
	$dbh = DBI->connect("DBI:mysql:pandora:localhost:3306","pandora","pandora",{ RaiseError => 1 });
	$dbh->disconnect;
}
