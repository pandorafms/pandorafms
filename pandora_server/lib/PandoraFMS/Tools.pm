package PandoraFMS::Tools;
##########################################################################
# Tools Package
# Pandora FMS. the Flexible Monitoring System. http://www.pandorafms.org
##########################################################################
# Copyright (c) 2005-2011 Artica Soluciones Tecnologicas S.L
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
use POSIX;
use PandoraFMS::Sendmail;	
use HTML::Entities;
use Encode;

# New in 3.2. Used to sendmail internally, without external scripts
# use Module::Loaded;

# Used to calculate the MD5 checksum of a string
use constant MOD232 => 2**32;

# UTF-8 flags deletion from multibyte characters when files are opened.
use open OUT => ":utf8";
use open ":std";

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
	disk_free
	load_average
	free_mem
	md5
	md5_init
	pandora_ping
	pandora_ping_latency
	ticks_totime
	safe_input
	safe_output
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
## Convert the $value encode in html entity to clear char string.
##########################################################################
sub safe_input($) {
	my $value = shift;

	$value = encode_entities ($value, "'<>&");
		
	#//Replace the character '\' for the equivalent html entitie
	$value =~ s/\\/&#92;/gi;

	#// First attempt to avoid SQL Injection based on SQL comments
	#// Specific for MySQL.
	$value =~ s/\/\*/&#47;&#42;/gi;
	$value =~ s/\*\//&#42;&#47;/gi;
	
	#//Replace ( for the html entitie
	$value =~ s/\(/&#40;/gi;
	
	#//Replace ( for the html entitie
	$value =~ s/\)/&#41;/gi;	
	
	#//Replace some characteres for html entities
	for (my $i=0;$i<33;$i++) {
		my $pattern = chr($i);
		my $hex = ascii_to_html($i);
		$value =~ s/$pattern/$hex/gi;		
	}
	
	for (my $i=128;$i<191;$i++) {
		my $pattern = chr($i);
		my $hex = ascii_to_html($i);
		$value =~ s/$pattern/$hex/gi;		
	}

	#//Replace characteres for tildes and others
	my $trans = get_html_entities();
	
	foreach(keys(%$trans))
	{
		my $pattern = chr($_);
		$value =~ s/$pattern/$trans->{$_}/gi;
	}
	
	return $value;
}

##########################################################################
## Convert the html entities to value encode to rebuild char string.
##########################################################################
sub safe_output($) {
	my $value = shift;

	$value = decode_entities ($value);
		
	#//Replace the character '\' for the equivalent html entitie
	$value =~ s/&#92;/\\/gi;

	#// First attempt to avoid SQL Injection based on SQL comments
	#// Specific for MySQL.
	$value =~ s/&#47;&#42;/\/\*/gi;
	$value =~ s/&#42;&#47;/\*\//gi;
	
	#//Replace ( for the html entitie
	$value =~ s/&#40;/\(/gi;
	
	#//Replace ( for the html entitie
	$value =~ s/&#41;/\)/gi;	
	
	#//Replace some characteres for html entities
	for (my $i=0;$i<33;$i++) {
		my $pattern = chr($i);
		my $hex = ascii_to_html($i);
		$value =~ s/$hex/$pattern/gi;		
	}
	
	for (my $i=128;$i<191;$i++) {
		my $pattern = chr($i);
		my $hex = ascii_to_html($i);
		$value =~ s/$hex/$pattern/gi;	
	}
		
	#//Replace characteres for tildes and others
	my $trans = get_html_entities();
	
	foreach(keys(%$trans))
	{
		my $pattern = chr($_);
		$value =~ s/$trans->{$_}/$pattern/gi;
	}
	
	return $value;
}

##########################################################################
# SUB get_html_entities
# Returns a hash table with the acute and special html entities
# Usefull for future chars addition:
# http://cpansearch.perl.org/src/GAAS/HTML-Parser-3.68/lib/HTML/Entities.pm
##########################################################################

sub get_html_entities {
	my %trans = (
		225 => '&aacute;',
		233 => '&eacute;', 
		237 => '&iacute;',
		243 => '&oacute;',
		250 => '&uacute;',
		193 => '&Aacute;',
		201 => '&Eacute;', 
		205 => '&Iacute;',
		211 => '&Oacute;',
		218 => '&Uacute;',
		228 => '&auml;',
		235 => '&euml;',
		239 => '&iuml;',
		246 => '&ouml;',
		252 => '&uuml;',
		196 => '&Auml;',
		203 => '&Euml;',
		207 => '&Iuml;',
		214 => '&Ouml;',
		220 => '&Uuml;',
		241 => '&ntilde;',
		209 => '&Ntilde;'
	);
	
	return \%trans;
}
##########################################################################
# SUB ascii_to_html (string)
# Convert an ascii string to hexadecimal
##########################################################################

sub ascii_to_html($) {
	my $ascii = shift;
	
	return "&#x".substr(unpack("H*", pack("N", $ascii)),6,3).";";
}


##########################################################################
# SUB pandora_get_os (string)
# Detect OS using a string, and return id_os
##########################################################################

sub pandora_get_os ($) {
	my $command = $_[0];
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
		elsif ($command =~ m/HP\-UX/i){
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
		elsif ($command =~ m/Octopods/i){
			return 13;
		}
		elsif ($command =~ m/embedded/i){
			return 14;
		}
		elsif ($command =~ m/android/i){
			return 15;
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

	# Store PID of this process in file presented by config token
	if ($pa_config->{'PID'} ne ""){

		if ( -e $pa_config->{'PID'} && open (FILE, $pa_config->{'PID'})) {
			$pid = <FILE> + 0;
			close FILE;

			# check if pandora_server is running
			if (kill (0, $pid)) {
				die "[FATAL] pandora_server already running, pid: $pid.";
			}
			logger ($pa_config, '[W] Stale PID file, overwriting.', 1);
		}
		umask 022;
		open (FILE, "> ".$pa_config->{'PID'}) or die "[FATAL] Cannot open PIDfile at ".$pa_config->{'PID'};
		print FILE "$$";
		close (FILE);
	}
	umask 0;
}


# -------------------------------------------+
# Pandora other General functions |
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
	
	my $pa_config = $_[0];
	my $to_address = $_[1];
	my $subject = $_[2];
	my $message = $_[3];

	$subject = decode_entities ($subject);
	$message = decode_entities ($message);

	my %mail = ( To   => $to_address,
			  Message => $message,
			  Subject => encode('MIME-Header', $subject),
			  'X-Mailer' => "Pandora FMS",
			  Smtp    => $pa_config->{"mta_address"},
			  Port    => $pa_config->{"mta_port"},
			  From    => $pa_config->{"mta_from"},
	);

	# Check if message has non-ascii chars.
	# non-ascii chars should be encoded in UTF-8.
	if ($message =~ /[^[:ascii:]]/o) {
		$mail{Message} = encode("UTF-8", $mail{Message});
		$mail{'Content-Type'} = 'text/plain; charset="UTF-8"';
	}

	if ($pa_config->{"mta_user"} ne ""){
		$mail{auth} = {user=>$pa_config->{"mta_user"}, password=>$pa_config->{"mta_pass"}, method=>$pa_config->{"mta_auth"}, required=>1 };
	}

	if (sendmail %mail) { 
		return;
	} else {
		logger ($pa_config, "[ERROR] Sending email to $to_address with subject $subject", 1);
		if (defined($Mail::Sendmail::error)){
			logger ($pa_config, "ERROR Code: $Mail::Sendmail::error", 5);
		}
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
	
	$level = 1 unless defined ($level);
	return if ($level > $pa_config->{'verbosity'});
	
	my $file = $pa_config->{'logfile'};
	
	# Log rotation
	if (-e $file && (stat($file))[7] > $pa_config->{'max_log_size'}) {
		rename ($file, $file.'.old');
	}
			
	open (FILE, ">> $file") or die "[FATAL] Could not open logfile '$file'";
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
		$micadena =~ s/[^\-\:\;\.\,\_\s\a\*\=\(\)a-zA-Z0-9]//g;
		$micadena =~ s/[\n\l\f]//g;
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
# Elimina comillas y caracteres problematicos y los sustituye por equivalentes
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
	
	# undef is returned only if the enterprise function was not found
	return undef unless (defined (&$func));

	# Try to call the function
	my $output = eval { &$func (@args); };

	# Check for errors
	#return undef if ($@);
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

###############################################################################
# Initialize some variables needed by the MD5 algorithm.
# See http://en.wikipedia.org/wiki/MD5#Pseudocode.
###############################################################################
my (@R, @K);
sub md5_init () {

	# R specifies the per-round shift amounts
	@R = (7, 12, 17, 22,  7, 12, 17, 22,  7, 12, 17, 22,  7, 12, 17, 22,
		  5,  9, 14, 20,  5,  9, 14, 20,  5,  9, 14, 20,  5,  9, 14, 20,
		  4, 11, 16, 23,  4, 11, 16, 23,  4, 11, 16, 23,  4, 11, 16, 23,
		  6, 10, 15, 21,  6, 10, 15, 21,  6, 10, 15, 21,  6, 10, 15, 21);

	# Use binary integer part of the sines of integers (radians) as constants
	for (my $i = 0; $i < 64; $i++) {
		$K[$i] = floor(abs(sin($i + 1)) * MOD232);
	}
}

###############################################################################
# Return the MD5 checksum of the given string. 
# Pseudocode from http://en.wikipedia.org/wiki/MD5#Pseudocode.
###############################################################################
sub md5 ($) {
	my $str = shift;

	if (!defined($str)){
		return "";
	}

	# Note: All variables are unsigned 32 bits and wrap modulo 2^32 when calculating

	# Initialize variables
	my $h0 = 0x67452301;
	my $h1 = 0xEFCDAB89;
	my $h2 = 0x98BADCFE;
	my $h3 = 0x10325476;

	# Pre-processing
	my $msg = unpack ("B*", pack ("A*", $str));
	my $bit_len = length ($msg);

	# Append "1" bit to message
	$msg .= '1';

	# Append "0" bits until message length in bits ≡ 448 (mod 512)
	$msg .= '0' while ((length ($msg) % 512) != 448);

	# Append bit /* bit, not byte */ length of unpadded message as 64-bit little-endian integer to message
	$msg .= unpack ("B64", pack ("VV", $bit_len));

	# Process the message in successive 512-bit chunks
	for (my $i = 0; $i < length ($msg); $i += 512) {

		my @w;
		my $chunk = substr ($msg, $i, 512);

		# Break chunk into sixteen 32-bit little-endian words w[i], 0 <= i <= 15
		for (my $j = 0; $j < length ($chunk); $j += 32) {
			push (@w, unpack ("V", pack ("B32", substr ($chunk, $j, 32))));
		}

		# Initialize hash value for this chunk
		my $a = $h0;
		my $b = $h1;
		my $c = $h2;
		my $d = $h3;
		my $f;
		my $g;

		# Main loop
		for (my $y = 0; $y < 64; $y++) {
			if ($y <= 15) {
				$f = $d ^ ($b & ($c ^ $d));
				$g = $y;
			}
			elsif ($y <= 31) {
				$f = $c ^ ($d & ($b ^ $c));
				$g = (5 * $y + 1) % 16;
			}
			elsif ($y <= 47) {
				$f = $b ^ $c ^ $d;
				$g = (3 * $y + 5) % 16;
			}
			else {
				$f = $c ^ ($b | (0xFFFFFFFF & (~ $d)));
				$g = (7 * $y) % 16;
			}

			my $temp = $d;
			$d = $c;
			$c = $b;
			$b = ($b + leftrotate (($a + $f + $K[$y] + $w[$g]) % MOD232, $R[$y])) % MOD232;
			$a = $temp;
		}

		# Add this chunk's hash to result so far
		$h0 = ($h0 + $a) % MOD232;
		$h1 = ($h1 + $b) % MOD232;
		$h2 = ($h2 + $c) % MOD232;
		$h3 = ($h3 + $d) % MOD232;
	}

	# Digest := h0 append h1 append h2 append h3 #(expressed as little-endian)
	return unpack ("H*", pack ("V", $h0)) . unpack ("H*", pack ("V", $h1)) . unpack ("H*", pack ("V", $h2)) . unpack ("H*", pack ("V", $h3));
}

###############################################################################
# MD5 leftrotate function. See http://en.wikipedia.org/wiki/MD5#Pseudocode.
###############################################################################
sub leftrotate ($$) {
	my ($x, $c) = @_;

	return (0xFFFFFFFF & ($x << $c)) | ($x >> (32 - $c));
}

##########################################################################
## Convert a date (yyy-mm-ddThh:ii:ss) to Timestamp.
##########################################################################
sub dateTimeToTimestamp {
	$_[0] =~ /(\d{4})-(\d{2})-(\d{2})([ |T])(\d{2}):(\d{2}):(\d{2})/;
	my($year, $mon, $day, $GMT, $hour, $min, $sec) = ($1, $2, $3, $4, $5, $6, $7);
	#UTC
	return timegm($sec, $min, $hour, $day, $mon - 1, $year - 1900);
	#BST
	#print "BST\t" . mktime($sec, $min, $hour, $day, $mon - 1, $year - 1900, 0, 0) . "\n";
}

##############################################################################
# Below some "internal" functions for automonitoring feature
# TODO: Implement the same for other systems like Solaris or BSD
##############################################################################

sub disk_free ($) {
	my $target = $_[0];

	# Try to use df command with Posix parameters... 
	my $command = "df -k -P ".$target." | tail -1 | awk '{ print \$4/1024}'";
	my $output = `$command`;
	return $output;
}

sub load_average {
	my $load_average;

	my $OSNAME = $^O;

	if ($OSNAME eq "freebsd"){
		$load_average = ((split(/\s+/, `/sbin/sysctl -n vm.loadavg`))[1]);
	}
	# by default LINUX calls
	else {
		$load_average = `cat /proc/loadavg | awk '{ print \$1 }'`;
	}
	return $load_average;
}

sub free_mem {
	my $free_mem;

	my $OSNAME = $^O;

	if ($OSNAME eq "freebsd"){
		my ($pages_free, $page_size) = `/sbin/sysctl -n vm.stats.vm.v_page_size vm.stats.vm.v_free_count`;
		# in kilobytes
		$free_mem = $pages_free * $page_size / 1024;

	}
	# by default LINUX calls
	else {
		$free_mem = `free | grep Mem | awk '{ print \$4 }'`;
	}
	return $free_mem;
}

##########################################################################
## SUB ticks_totime
	# Transform a snmp timeticks count in a date
##########################################################################

sub ticks_totime ($){

	# Calculate ticks per second, minute, hour, and day
	my $TICKS_PER_SECOND = 100;
	my $TICKS_PER_MINUTE = $TICKS_PER_SECOND * 60;
	my $TICKS_PER_HOUR   = $TICKS_PER_MINUTE * 60;
	my $TICKS_PER_DAY    = $TICKS_PER_HOUR * 24;

	my $ticks   = shift;
	
	if (!defined($ticks)){
			return "";
	}
	
	my $seconds = int($ticks / $TICKS_PER_SECOND) % 60;
	my $minutes = int($ticks / $TICKS_PER_MINUTE) % 60;
	my $hours   = int($ticks / $TICKS_PER_HOUR)   % 24;
	my $days    = int($ticks / $TICKS_PER_DAY);

	return "$days days, $hours hours, $minutes minutes, $seconds seconds";
}

##############################################################################
=head2 C<< pandora_ping (I<$pa_config>, I<$host>) >> 

Ping the given host. 
Returns:
 1 if the host is alive
 0 otherwise.

=cut
##############################################################################
sub pandora_ping ($$) {
	my ($pa_config, $host) = @_;

	my $output = 0;
	my $i;

	# See codes on http://perldoc.perl.org/perlport.html#PLATFORMS
	my $OSNAME = $^O;

	# Windows XP .. Windows 7
	if (($OSNAME eq "MSWin32") || ($OSNAME eq "MSWin32-x64") || ($OSNAME eq "cygwin")){
		my $ms_timeout = $pa_config->{'networktimeout'} * 1000;
		$output = `ping -n $pa_config->{'icmp_checks'} -w $ms_timeout $host`;
		if ($output =~ /TTL/){
			return 1;
		}
		return 0;
	}

	elsif ($OSNAME eq "solaris"){
		my $ping_command = "ping";

		if ($host =~ /\d+:|:\d+/ ) {
			$ping_command = "ping -A inet6"
		}

		# Note: timeout option is not implemented in ping.
		# 'networktimeout' is not used by ping on Solaris.

		# Ping the host
		`$ping_command -s -n $host 56 $pa_config->{'icmp_checks'} >/dev/null 2>&1`;
		if ($? == 0) {
			return 1;
		}
		return 0;
	}

	elsif ($OSNAME eq "freebsd"){
		my $ping_command = "ping -t $pa_config->{'networktimeout'}";

		if ($host =~ /\d+:|:\d+/ ) {
			$ping_command = "ping6";
		}

		# Note: timeout(-t) option is not implemented in ping6.
		# 'networktimeout' is not used by ping6 on FreeBSD.

		# Ping the host
		`$ping_command -q -n -c $pa_config->{'icmp_checks'} $host >/dev/null 2>&1`;
		if ($? == 0) {
			return 1;
		}
		return 0;
	}

	# by default LINUX calls
	else {

		my $ping_command = "ping";

		if ($host =~ /\d+:|:\d+/ ) {
			$ping_command = "ping6";
		}

		# Ping the host
		`$ping_command -q -W $pa_config->{'networktimeout'} -n -c $pa_config->{'icmp_checks'} $host >/dev/null 2>&1`;	
		if ($? == 0) {
			return 1;
		}
		
		return 0;
	}

	return $output;
}

##############################################################################
=head2 C<< pandora_ping_latency (I<$pa_config>, I<$host>) >> 

Ping the given host. Returns the average round-trip time.

=cut
##############################################################################
sub pandora_ping_latency ($$) {
	my ($pa_config, $host) = @_;

	my $output = 0;

	# See codes on http://perldoc.perl.org/perlport.html#PLATFORMS
	my $OSNAME = $^O;

	# Windows XP .. Windows 2008, I assume Win7 is the same
	if (($OSNAME eq "MSWin32") || ($OSNAME eq "MSWin32-x64") || ($OSNAME eq "cygwin")){

		# System ping reports in different languages, but with the same format:
		# Mínimo = xxms, Máximo = xxms, Media = XXms
		# Minimun = xxms, Mamimun = xxms, Average = XXms

		# If this fails, ping can be replaced by fping which also have the same format
		# but always in english

		my $ms_timeout = $pa_config->{'networktimeout'} * 1000;
		$output = `ping -n $pa_config->{'icmp_checks'} -w $ms_timeout $host`;

		if ($output =~ m/\=\s([0-9]*)[a-z][a-z]\r/){
			return $1;
		} else {
			return 0;
		}

	}

	elsif ($OSNAME eq "solaris"){
		my $ping_command = "ping";

		if ($host =~ /\d+:|:\d+/ ) {
			$ping_command = "ping -A inet6";
		}

		# Note: timeout option is not implemented in ping.
		# 'networktimeout' is not used by ping on Solaris.

		# Ping the host
		my @output = `$ping_command -s -n $host 56 $pa_config->{'icmp_checks'} 2>/dev/null`;

		# Something went wrong
		return 0 if ($? != 0);

		# Parse the output
		my $stats = pop (@output);
		return 0 unless ($stats =~ m/([\d\.]+)\/([\d\.]+)\/([\d\.]+)\/([\d\.]+) +ms/);
		return $2;
	}

	elsif ($OSNAME eq "freebsd"){
		my $ping_command = "ping";

		if ($host =~ /\d+:|:\d+/ ) {
			$ping_command = "ping6";
		}

		# Note: timeout(-t) option is not implemented in ping6. 
		# timeout(-t) and waittime(-W) options in ping are not the same as
		# Linux. On latency, there are no way to set timeout.
		# 'networktimeout' is not used on FreeBSD.

		# Ping the host
		my @output = `$ping_command -q -n -c $pa_config->{'icmp_checks'} $host 2>/dev/null`;

		# Something went wrong
		return 0 if ($? != 0);

		# Parse the output
		my $stats = pop (@output);
		return 0 unless ($stats =~ m/([\d\.]+)\/([\d\.]+)\/([\d\.]+)\/([\d\.]+) +ms/);
		return $2;
	}

	# by default LINUX calls
	else {
		my $ping_command = "ping";

		if ($host =~ /\d+:|:\d+/ ) {
			$ping_command = "ping6";
		}


		# Ping the host
		my @output = `$ping_command -q -W $pa_config->{'networktimeout'} -n -c $pa_config->{'icmp_checks'} $host 2>/dev/null`;

		# Something went wrong
		return 0 if ($? != 0);

		# Parse the output
		my $stats = pop (@output);
		return 0 unless ($stats =~ m/([\d\.]+)\/([\d\.]+)\/([\d\.]+)\/([\d\.]+) +ms/);
		return $2;
	}

	# If no valid get values until now, just return with empty value (not valid)
	return $output;
}

# End of function declaration
# End of defined Code

1;
__END__
