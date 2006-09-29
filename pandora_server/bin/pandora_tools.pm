package pandora_tools;
##########################################################################
# Pandora Tools Package
##########################################################################
# Copyright (c) 2004-2006 Sancho Lerena, slerena@gmail.com
# Copyright (c) 2005-2006 Artica Soluciones Tecnologicas S.L
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
##########################################################################

use warnings;
use Time::Local;
use Date::Manip;                	# Needed to manipulate DateTime formats of input, output and compare
use POSIX qw(setsid);

require Exporter;

our @ISA = ("Exporter");
our %EXPORT_TAGS = ( 'all' => [ qw( ) ] );
our @EXPORT_OK = ( @{ $EXPORT_TAGS{'all'} } );
our @EXPORT = qw( 	daemonize
			logger
			limpia_cadena
			md5check
			float_equal
			is_numeric
		);


##########################################################################
# Sub daemonize ()
# Put program in background (for daemon mode)
##########################################################################

sub daemonize {
    chdir '/tmp'                 or die "Can't chdir to /tmp: $!";
    open STDIN, '/dev/null'   or die "Can't read /dev/null: $!";
    open STDOUT, '>>/dev/null' or die "Can't write to /dev/null: $!";
    open STDERR, '>>/dev/null' or die "Can't write to /dev/null: $!";
    defined(my $pid = fork)   or die "Can't fork: $!";
    exit if $pid;
    setsid                    or die "Can't start a new session: $!";
    umask 0;
}


# -------------------------------------------+
# Pandora other General functions  |
# -------------------------------------------+

##########################################################################
# SUB is_numeric
# Return TRUE if given argument is numeric
##########################################################################

sub getnum {
        use POSIX qw(strtod);
        my $str = shift;
        $str =~ s/^\s+//;
        $str =~ s/\s+$//;
        $! = 0;
        my($num, $unparsed) = strtod($str);
        if (($str eq '') || ($unparsed != 0) || $!) {
            return undef;
        } else {
            return $num;
        } 
    } 

sub is_numeric { defined getnum($_[0]) }   

##########################################################################
# SUB md5check (param_1, param_2)
# Verify MD5 file .checksum
##########################################################################
# param_1 : Name of data file
# param_2 : Name of md5 file

sub md5check {
	my $buf;
	my $buf2;
	my $file = $_[0];
	my $md5file = $_[1];
	open(FILE, $file) or return 0;
	binmode(FILE);
	my $md5 = Digest::MD5->new;
	while (<FILE>) {
		$md5->add($_);
	}
	close(FILE);
	$buf2 = $md5->hexdigest;
	open(FILE,$md5file) or return 0;
	while (<FILE>) {
		$buf = $_;
	}
	close (FILE);
	$buf=uc($buf);
	$buf2=uc($buf2);
	if ($buf =~ /$buf2/ ) {
		#print "MD5 Correct";
		return 1;
	} else {
		#print "MD5 Incorrect";
		return 0;
	}
}

##########################################################################
# SUB logger (pa_config, param_1, param_2)
# Log to file
##########################################################################
# param_1 : Data file
# param_2 : Data

sub logger {
	my $pa_config = $_[0];
	my $fichero = $pa_config->{"logfile"};
	my $datos = $_[1];
	my $verbose_level = 2; # if parameter not passed, verbosity is 5 (DEBUG)
	my $param2= $_[2];
	if (defined $param2){
		if (is_numeric($param2)){
			$verbose_level = $param2;
		} 
	}
	
	if ($verbose_level <= $pa_config->{"verbosity"}) {
		if ($verbose_level > 0) {
			$datos = "[V".$verbose_level."] ".$datos;
		}
	
		my $time_now = &UnixDate("today","%Y/%m/%d %H:%M:%S");
		open (FILE, ">> $fichero") or die "[FATAL] Cannot open logfile at $fichero";	
		print FILE "$time_now $datos \n";
		close (FILE);
	}
}

##########################################################################
# limpia_cadena (string) - Purge a string for any forbidden characters (esc, etc)
##########################################################################
sub limpia_cadena {
    my $micadena;
    $micadena = $_[0];
    $micadena =~ s/[^\-\:\;\.\,\_\s\a\*\=\(\)a-zA-Z0-9]/ /g;
    $micadena =~ s/[\n\l\f]/ /g;
    return $micadena;
}

##########################################################################
# sub float_equal (num1, num2, decimals)
# This function make possible to compare two float numbers, using only x decimals
# in comparation.
# Taken from Perl Cookbook, O'Reilly. Thanks, guys.
##########################################################################
sub float_equal {
    my ($A, $B, $dp) = @_;
    return sprintf("%.${dp}g", $A) eq sprintf("%.${dp}g", $B);
}

# End of function declaration
# End of defined Code

1;
__END__
