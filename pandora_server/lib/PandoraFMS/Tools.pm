package PandoraFMS::Tools;
##########################################################################
# Tools Package
# Pandora FMS. the Flexible Monitoring System. http://www.pandorafms.org
##########################################################################
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
use POSIX qw(setsid strftime);
use Mail::Sendmail;	# New in 2.0. Used to sendmail internally, without external scripts
#use Module::Loaded;

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
	print_message
	get_tag_value
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
		elsif ($command =~ m/Linux/i){
			return 1;
		}
		elsif ($command =~ m/Enterasys/i){
			return 11;
		}
		elsif ($command =~ m/3com/i){
			return 11;
		}
		elsif ($command =~ m/Octopus/i){
			return 13;
		}
		elsif ($command =~ m/BSD/i){
			return 4;
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

sub pandora_sendmail {                  

	#WARNING: To use MTA Auth is needed v0.79_16 or higer of Mail:Sendmail
	#http://cpansearch.perl.org/src/MIVKOVIC/Mail-Sendmail-0.79_16/Sendmail.pm
	
	my $pa_config = $_[0];
	my $to_address = $_[1];
	my $subject = $_[2];
	my $message = $_[3];

	my %mail = ( To   => $to_address,
			  Message => $message,
			  Subject => $subject,
			  'X-Mailer' => "Pandora FMS",
			  Smtp    => $pa_config->{"mta_address"},
			  Port    => $pa_config->{"mta_port"},
			  From    => $pa_config->{"mta_from"},
	);

	if ($pa_config->{"mta_user"} ne ""){
		$mail{auth} = {user=>$pa_config->{"mta_user"}, password=>$pa_config->{"mta_pass"}, method=>$pa_config->{"mta_auth"}, required=>1 };
	}

	if (sendmail %mail) { 
		return;
	} else {
		logger ($pa_config, "[ERROR] Sending email to $to_address with subject $subject", 1);
		logger ($pa_config, "ERROR Code: $Mail::Sendmail::error", 5);
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
	# Replace "," for "."
	$val =~ s/\,/\./;
	
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
# SUB logger (pa_config, message, level)
# Log to file
##########################################################################
sub logger ($$;$) {
	my ($pa_config, $message, $level) = @_;

	return if ($level > $pa_config->{'verbosity'});
	
	my $file = $pa_config->{'logfile'};
	
	# Log rotation
	if (-e $file && (stat($file))[7] > $pa_config->{'max_log_size'}) {
		rename ($file, $file.'.old');
	}
		
	open (FILE, ">> $file") or die "[FATAL] Could not open logfile '$fichero'";
	print FILE strftime ("%Y-%m-%d %H:%M:%S", localtime()) . " " . $pa_config->{'servername'} . $pa_config->{'servermode'} . " [V". $level ."] " . $message . "\n";
	close (FILE);
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
# Tries to load the PandoraEnterprise module. Must be called once before
# enterprise_hook ().
##########################################################################
sub enterprise_load ($) {
	my $pa_config = shift;

	# Check dependencies
	eval 'local $SIG{__DIE__}; require IO::Socket::Multicast';
	if ($@) {
		print_message ($pa_config, " [*] Error loading Pandora FMS Enterprise: IO::Socket::Multicast not found.", 1);
		return 0;
	}

	# Already loaded
	#return 1 if (is_loaded ('PandoraFMS::Enterprise'));
	
	# Try to load the module
	# eval 'local $SIG{__DIE__}; require PandoraFMS::Enterprise;';
	eval 'require PandoraFMS::Enterprise;';
	
	# Ops
	return 0 if ($@);
	return 1;
}

##########################################################################
# Tries to call a PandoraEnterprise function. Returns undef if unsuccessful.
##########################################################################
sub enterprise_hook ($$) {
	my $func = shift;
	my @args = @{shift ()};

	# Temporarily disable strict refs
	no strict 'refs';

	# Prepend the package name
	$func = 'PandoraFMS::Enterprise::' . $func;
	return undef unless (defined (&$func));

	# Try to call the function
	my $output = eval { &$func (@args); };

	# Check for errors
	#return undef if ($@);

	# undef is returned only if the enterprise function was not found
	return '' unless defined ($output);

	return $output;
}

##########################################################################
# Prints a message to STDOUT at the given log level.
##########################################################################
sub print_message ($$$) {
	my ($pa_config, $message, $log_level) = @_;

	print STDOUT $message . "\n" if ($pa_config->{'verbosity'} >= $log_level);
}

##########################################################################
# Returns the value of an XML tag from a hash returned by XMLin (one level
# depth).
##########################################################################
sub get_tag_value ($$$) {
	my ($hash_ref, $tag, $def_value) = @_;

	return $def_value unless defined ($hash_ref->{$tag}) and ref ($hash_ref->{$tag});

	# Return the first found value
	foreach my $value (@{$hash_ref->{$tag}}) {
		
		# If the tag is defined but has no value a ref to an empty hash is returned by XML::Simple
		return $value unless ref ($value);
	}

	return $def_value;
}

# End of function declaration
# End of defined Code

1;
__END__
