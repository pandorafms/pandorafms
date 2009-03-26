package PandoraFMS::Tools;
##########################################################################
# Tools Package
# Pandora FMS. the Flexible Monitoring System. http://www.pandorafms.org
##########################################################################
# Copyright (c) 2004-2009 Sancho Lerena, slerena@gmail.com
# Copyright (c) 2005-2009 Artica Soluciones Tecnologicas S.L
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU Lesser General Public License
# as published by the Free Software Foundation; version 2
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
##########################################################################

use warnings;
use Time::Local;
use Date::Manip;	# Needed to manipulate DateTime formats of input, output and compare
use POSIX qw(setsid);
use Mail::Sendmail;	# New in 2.0. Used to sendmail internally, without external scripts

require Exporter;

our @ISA = ("Exporter");
our %EXPORT_TAGS = ( 'all' => [ qw( ) ] );
our @EXPORT_OK = ( @{ $EXPORT_TAGS{'all'} } );
our @EXPORT = qw( 	
	pandora_daemonize
	logger
	limpia_cadena
	md5check
	float_equal
	sqlWrap
	is_numeric
	clean_blank
	pandora_sendmail
	pandora_get_os
	pandora_trash_ascii
	enterprise_hook
	enterprise_load
);

##########################################################################
## SUB pandora_trash_ascii 
# Generate random ascii strings with variable lenght
##########################################################################

sub pandora_trash_ascii {
	my $config_depth = $_[0];
	my $a;
	my $output;

	for ($a=0;$a<$config_depth;$a++){
		$output = $output.chr(int(rand(25)+97));
	}
	return $output
}


##########################################################################
# SUB pandora_get_os (string)
# Detect OS using a string, and return id_os
##########################################################################

sub pandora_get_os ($) {
	$command = $_[0];
	if (defined($command) && $command ne ""){
		if ($command =~ m/Windows/i){
			return 9;
		}
		elsif ($command =~ m/Linux/i){
			return 1;
		}
		elsif ($command =~ m/BSD/i){
			return 4;
		}
		elsif ($command =~ m/Cisco/i){
			return 7;
		}
		elsif ($command =~ m/SunOS/i){
			return 2;
		}
		elsif ($command =~ m/Solaris/i){
			return 2;
		}
		elsif ($command =~ m/AIX/i){
			return 3;
		}
		elsif ($command =~ m/HP-UX/i){
			return 5;
		}
		elsif ($command =~ m/Apple/i){
			return 8;
		}
		else {
			return 10; # Unknown / Other
		}
	} else {
		return 10;
	}
}

##########################################################################
# Sub daemonize ()
# Put program in background (for daemon mode)
##########################################################################

sub pandora_daemonize {
	my $pa_config = $_[0];
	open STDIN, '/dev/null'     or die "Can't read /dev/null: $!";
	open STDOUT, '>>/dev/null'  or die "Can't write to /dev/null: $!";
	open STDERR, '>>/dev/null'  or die "Can't write to /dev/null: $!";
	chdir '/tmp'                or die "Can't chdir to /tmp: $!";
	defined(my $pid = fork)     or die "Can't fork: $!";
	exit if $pid;
	setsid                      or die "Can't start a new session: $!";
	umask 0;

	# Store PID of this process in file presented by config token
	if ($pa_config->{'PID'} ne ""){
		open (FILE, "> ".$pa_config->{'PID'}) or die "[FATAL] Cannot open PIDfile at ".$pa_config->{'PID'};
		print FILE "$$";
		close (FILE);
	}
}


# -------------------------------------------+
# Pandora other General functions  |
# -------------------------------------------+


##########################################################################
# SUB pandora_sendmail
# Send a mail, connecting directly to MTA
# param1 - config hash
# param2 - Destination email addres
# param3 - Email subject
# param4 - Email Message body
##########################################################################

sub pandora_sendmail {                  # added in 2.0 version
	my $pa_config = $_[0];
	my $to_address = $_[1];
	my $subject = $_[2];
	my $message = $_[3];

	my %mail = ( To   => $to_address,
			  Message => $message,
			  Subject => $subject,
			  Smtp    => $pa_config->{"mta_address"},
			  Port    => $pa_config->{"mta_port"},
			  From    => $pa_config->{"mta_from"},
	);

	if ($pa_config->{"mta_user"} ne ""){
		$mail{auth} = {user=>$config->{"mta_user"}, password=>$config->{"mta_pass"}, method=>$config->{"mta_auth"}, required=>0 }
	}
	eval {
		sendmail(%mail);
	};
	if ($@){
		logger ($pa_config, "[ERROR] Sending email to $to_address with subject $subject", 1);
		logger ($pa_config, "ERROR Code: $@", 4);
	}
}

##########################################################################
# SUB is_numeric
# Return TRUE if given argument is numeric
##########################################################################

sub is_numeric {
	my $val = $_[0];
	
	if (!defined($val)){
		return 0;
	}
	
    my $DIGITS = qr{ \d+ (?: [.] \d*)? | [.] \d+ }xms;
    my $SIGN   = qr{ [+-] }xms;
    my $NUMBER = qr{ ($SIGN?) ($DIGITS) }xms;
    if ( $val !~ /^${NUMBER}$/ ) {
    	return 0;   #Non-numeric
    } else {
        return 1;   #Numeric
    }
}

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
	my $param2= $_[2];
	my $verbose_level = 2; # if parameter not passed, verbosity is 2 

	if (!defined $pa_config->{"verbosity"}){
		$pa_config->{"verbosity"} = 0;
	}

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
		if (-e $fichero){
			my $filesize = (stat($fichero))[7];
			if ( $filesize > $pa_config->{'max_log_size'}) {
				rename ($fichero, $fichero.".old");
			}	
		}
		open (FILE, ">> $fichero") or die "[FATAL] Cannot open logfile at $fichero";
		my $server_name = $pa_config->{'servername'}.$pa_config->{"servermode"};
		print FILE "$time_now $server_name $datos \n";
		close (FILE);
	}
}

##########################################################################
# limpia_cadena (string) - Purge a string for any forbidden characters (esc, etc)
##########################################################################
sub limpia_cadena {
    my $micadena;
    $micadena = $_[0];
	if (defined($micadena)){
    	$micadena =~ s/[^\-\:\;\.\,\_\s\a\*\=\(\)a-zA-Z0-9]/ /g;
    	$micadena =~ s/[\n\l\f]/ /g;
    	return $micadena;
	} else {
		return "";
	}
}

##########################################################################
# clean_blank (string) - Purge a string for any blank spaces in it
##########################################################################
sub clean_blank {
	my $input = $_[0];
	$input =~ s/\s//g;
	return $input;
}

########################################################################################
# sub sqlWrap(texto)
# Elimina comillas  y caracteres problematicos y los sustituye por equivalentes
########################################################################################

sub sqlWrap {
	my $toBeWrapped = shift(@_);
	if (defined $toBeWrapped){
			$toBeWrapped =~ s/\'/\\\'/g;
			$toBeWrapped =~ s/\"/\\\'/g; # " This is for highlighters that don't understand escaped quotes
			return "'".$toBeWrapped."'";
	}
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

##########################################################################
# sub enterprise_load ()
# Tries to load the PandoraEnterprise module. Must be called once before
# enterprise_hook ().
##########################################################################
sub enterprise_load () {
        eval { 
                require PandoraFMS::Enterprise; 
        };
}


##########################################################################
# sub enterprise_hook ($function_name, \@arguments)
# Tries to call a PandoraEnterprise function. Returns undef if unsuccessful.
##########################################################################
sub enterprise_hook ($$) {
	my $func = $_[0];
	my @args = @{$_[1]};
	my $output;

	# Temporarily disable strict refs
	no strict 'refs';

	# Prepend the package name
	$func = 'PandoraFMS::Enterprise::' . $func;

	# Try to call the function
	$output = eval { &$func (@args); };

	# Check for errors
	if ($@) {
		return undef;
	}
	else {
		return $output;
	}
}

# End of function declaration
# End of defined Code

1;
__END__
