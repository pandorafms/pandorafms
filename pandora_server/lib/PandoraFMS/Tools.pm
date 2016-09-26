package PandoraFMS::Tools;
########################################################################
# Tools Package
# Pandora FMS. the Flexible Monitoring System. http://www.pandorafms.org
########################################################################
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
use Socket qw(inet_ntoa inet_aton);
use Sys::Syslog;

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
	DATASERVER
	NETWORKSERVER
	SNMPCONSOLE
	RECONSERVER
	PLUGINSERVER
	PREDICTIONSERVER
	WMISERVER
	EXPORTSERVER
	INVENTORYSERVER
	WEBSERVER
	EVENTSERVER
	ICMPSERVER
	SNMPSERVER
	SATELLITESERVER
	METACONSOLE_LICENSE
	$DEVNULL
	$OS
	$OS_VERSION
	RECOVERED_ALERT
	FIRED_ALERT
	STATUS_NORMAL
	STATUS_CRITICAL
	STATUS_WARNING
	STATUS_UNKNOWN
	STATUS_NOTINIT
	cron_get_closest_in_range
	cron_next_execution
	cron_next_execution_date
	cron_check_syntax
	pandora_daemonize
	logger
	pandora_rotate_logfile
	limpia_cadena
	md5check
	float_equal
	sqlWrap
	is_numeric
	is_metaconsole
	clean_blank
	pandora_sendmail
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
	resolve_hostname
	ticks_totime
	safe_input
	safe_output
	month_have_days
	translate_obj
	valid_regex
);

# ID of the different servers
use constant DATASERVER => 0;
use constant NETWORKSERVER => 1;
use constant SNMPCONSOLE => 2;
use constant RECONSERVER => 3;
use constant PLUGINSERVER => 4;
use constant PREDICTIONSERVER => 5;
use constant WMISERVER => 6;
use constant EXPORTSERVER => 7;
use constant INVENTORYSERVER => 8;
use constant WEBSERVER => 9;
use constant EVENTSERVER => 10;
use constant ICMPSERVER => 11;
use constant SNMPSERVER => 12;
use constant SATELLITESERVER => 13;

# Module statuses.
use constant STATUS_NORMAL => 0;
use constant STATUS_CRITICAL => 1;
use constant STATUS_WARNING => 2;
use constant STATUS_UNKNOWN => 3;
use constant STATUS_NOTINIT => 4;

# Value for a metaconsole license type
use constant METACONSOLE_LICENSE => 0x01;

# Alert modes
use constant RECOVERED_ALERT => 0;
use constant FIRED_ALERT => 1;

# Set OS, OS version and /dev/null
our $OS = $^O;
our $OS_VERSION = "unknown";
our $DEVNULL = '/dev/null';
if ($OS eq 'linux') {
	$OS_VERSION = `lsb_release -sd 2>/dev/null`;
} elsif ($OS eq 'aix') {
	$OS_VERSION = "$2.$1" if (`uname -rv` =~ /\s*(\d)\s+(\d)\s*/);
} elsif ($OS =~ /win/i) {
	$OS = "windows";
	$OS_VERSION = `ver`;
	$DEVNULL = '/Nul';
} elsif ($OS eq 'freebsd') {
	$OS_VERSION = `uname -r`;
}
chomp($OS_VERSION);

########################################################################
## SUB pandora_trash_ascii 
# Generate random ascii strings with variable lenght
########################################################################

sub pandora_trash_ascii {
	my $config_depth = $_[0];
	my $a;
	my $output;
	
	for ($a=0;$a<$config_depth;$a++){
		$output = $output.chr(int(rand(25)+97));
	}
	return $output
}

########################################################################
## Convert the $value encode in html entity to clear char string.
########################################################################
sub safe_input($) {
	my $value = shift;
	
	$value = encode_entities ($value, "<>&");
	
	#//Replace the character '\' for the equivalent html entitie
	$value =~ s/\\/&#92;/gi;
	
	#// First attempt to avoid SQL Injection based on SQL comments
	#// Specific for MySQL.
	$value =~ s/\/\*/&#47;&#42;/gi;
	$value =~ s/\*\//&#42;&#47;/gi;
	
	#//Replace ' for the html entitie
	$value =~ s/\"/&quot;/gi;
	
	#//Replace ' for the html entitie
	$value =~ s/\'/&#039;/gi;
	
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
		$value =~ s/$pattern/$trans->{$_}/g;
	}
	
	return $value;
}

########################################################################
## Convert the html entities to value encode to rebuild char string.
########################################################################
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
	
	#//Replace ' for the html entitie
	$value =~ s/&#039;/')/gi;	
	
	#//Replace " for the html entitie
	$value =~ s/&quot;/")/gi;	
	
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
		$value =~ s/$trans->{$_}/$pattern/g;
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
########################################################################
# SUB ascii_to_html (string)
# Convert an ascii string to hexadecimal
########################################################################

sub ascii_to_html($) {
	my $ascii = shift;
	
	return "&#x".substr(unpack("H*", pack("N", $ascii)),6,3).";";
}

########################################################################
# Sub daemonize ()
# Put program in background (for daemon mode)
########################################################################

sub pandora_daemonize {
	my $pa_config = $_[0];
	open STDIN, "$DEVNULL"		or die "Can't read $DEVNULL: $!";
	open STDOUT, ">>$DEVNULL"	or die "Can't write to $DEVNULL: $!";
	open STDERR, ">>$DEVNULL"	or die "Can't write to $DEVNULL: $!";
	chdir '/tmp'					or die "Can't chdir to /tmp: $!";
	defined(my $pid = fork)		or die "Can't fork: $!";
	exit if $pid;
	setsid							or die "Can't start a new session: $!";
	
	# Store PID of this process in file presented by config token
	if ($pa_config->{'PID'} ne "") {
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


########################################################################
# SUB pandora_sendmail
# Send a mail, connecting directly to MTA
# param1 - config hash
# param2 - Destination email addres
# param3 - Email subject
# param4 - Email Message body
# param4 - Email content type
########################################################################

sub pandora_sendmail {
	
	my $pa_config = $_[0];
	my $to_address = $_[1];
	my $subject = $_[2];
	my $message = $_[3];
	my $content_type = $_[4];
	
	$subject = decode_entities ($subject);

	# If content type is defined, the message will be custom
	if (! defined($content_type)) {
		$message = decode_entities ($message);
	}
	
	my %mail = ( To	=> $to_address,
		Message		=> $message,
		Subject		=> encode('MIME-Header', $subject),
		'X-Mailer'	=> "Pandora FMS",
		Smtp		=> $pa_config->{"mta_address"},
		Port		=> $pa_config->{"mta_port"},
		From		=> $pa_config->{"mta_from"},
	);
	
	if (defined($content_type)) {
		$mail{'Content-Type'} = $content_type;
	}

	# Check if message has non-ascii chars.
	# non-ascii chars should be encoded in UTF-8.
	if ($message =~ /[^[:ascii:]]/o && !defined($content_type)) {
		$mail{Message} = encode("UTF-8", $mail{Message});
		$mail{'Content-Type'} = 'text/plain; charset="UTF-8"';
	}
	
	if ($pa_config->{"mta_user"} ne ""){
		$mail{auth} = {user=>$pa_config->{"mta_user"}, password=>$pa_config->{"mta_pass"}, method=>$pa_config->{"mta_auth"}, required=>1 };
	}

	if (sendmail %mail) { 
		return;
	}
	else {
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
	}
	else {
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
	}
	else {
		#print "MD5 Incorrect";
		return 0;
	}
}

########################################################################
# SUB logger (pa_config, message, level)
# Log to file
########################################################################
sub logger ($$;$) {
	my ($pa_config, $message, $level) = @_;

	# Clean any string and ready to be printed in screen/file
	$message = safe_output ($message);

	$level = 1 unless defined ($level);
	return if ($level > $pa_config->{'verbosity'});

	if (!defined($pa_config->{'logfile'})) {
		print strftime ("%Y-%m-%d %H:%M:%S", localtime()) . " [V". $level ."] " . $message . "\n";
		return;
	}

	# Get the log file (can be a regular file or 'syslog')
	my $file = $pa_config->{'logfile'};

	# Syslog
	if ($file eq 'syslog') {
		
		# Set the security level
		my $security_level = 'info';
		if ($level < 2) {
			$security = 'crit';
		} elsif ($level < 5) {
			$security = 'warn';
		}

		openlog('pandora_server', 'ndelay', 'daemon');
		syslog($security_level, $message);
		closelog();
	} else {
		open (FILE, ">> $file") or die "[FATAL] Could not open logfile '$file'";
		# Get an exclusive lock on the file (LOCK_EX)
		flock (FILE, 2);
		print FILE strftime ("%Y-%m-%d %H:%M:%S", localtime()) . " " . $pa_config->{'servername'} . $pa_config->{'servermode'} . " [V". $level ."] " . $message . "\n";
		close (FILE);
	}
}

########################################################################
# SUB pandora_rotate_log (pa_config)
# Log to file
########################################################################
sub pandora_rotate_logfile ($) {
	my ($pa_config) = @_;

	my $file = $pa_config->{'logfile'};

	# Log File Rotation
	if ($file ne 'syslog' && -e $file && (stat($file))[7] > $pa_config->{'max_log_size'}) {
		foreach my $i (reverse 1..$pa_config->{'max_log_generation'}) {
			rename ($file . "." . ($i - 1), $file . "." . $i);
		}
		rename ($file, "$file.0");
	
	}
}

########################################################################
# limpia_cadena (string) - Purge a string for any forbidden characters (esc, etc)
########################################################################
sub limpia_cadena {
	my $micadena;
	$micadena = $_[0];
	if (defined($micadena)){
		$micadena =~ s/[^\-\:\;\.\,\_\s\a\*\=\(\)a-zA-Z0-9]//g;
		$micadena =~ s/[\n\l\f]//g;
		return $micadena;
	}
	else {
		return "";
	}
}

########################################################################
# clean_blank (string) - Remove leading and trailing blanks
########################################################################
sub clean_blank {
	my $input = $_[0];
	$input =~ s/^\s+//g;
	$input =~ s/\s+$//g;
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
	if ($^O eq 'MSWin32') {
		# If the Windows service dies the service is stopped, even inside an eval ($RUN is set to 0)!
		eval 'local $SIG{__DIE__}; require PandoraFMS::Enterprise;';
	}
	else {
		eval 'require PandoraFMS::Enterprise;';
	}


	
	# Ops
	if ($@) {
		# Enterprise.pm not found.
		return 0 if ($@ =~ m/PandoraFMS\/Enterprise\.pm.*\@INC/);

		open (STDERR, ">> " . $pa_config->{'errorlogfile'});
		print STDERR $@;
		close (STDERR);
		return 0;
	}
	
	# Initialize the enterprise module.
	PandoraFMS::Enterprise::init($pa_config);
	
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

########################################################################
# Prints a message to STDOUT at the given log level.
########################################################################
sub print_message ($$$) {
	my ($pa_config, $message, $log_level) = @_;
	
	print STDOUT $message . "\n" if ($pa_config->{'verbosity'} >= $log_level);
}

##########################################################################
# Returns the value of an XML tag from a hash returned by XMLin (one level
# depth).
##########################################################################
sub get_tag_value ($$$;$) {
	my ($hash_ref, $tag, $def_value, $all_array) = @_;
	$all_array = 0 unless defined ($all_array);
	
	return $def_value unless defined ($hash_ref->{$tag}) and ref ($hash_ref->{$tag});
	
	# If all array is required, returns the array
	return $hash_ref->{$tag} if ($all_array == 1);
	# Return the first found value
	foreach my $value (@{$hash_ref->{$tag}}) {
		
		# If the tag is defined but has no value a ref to an empty hash is returned by XML::Simple
		return $value unless ref ($value);
	}
	
	return $def_value;
}

########################################################################
# Initialize some variables needed by the MD5 algorithm.
# See http://en.wikipedia.org/wiki/MD5#Pseudocode.
########################################################################
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

	# Initialize once.
	md5_init() if (!defined($R[0]));

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
	elsif ($OSNAME eq "netbsd"){
		$free_mem = `cat /proc/meminfo | grep MemFree | awk '{ print \$2 }'`;
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
sub pandora_ping ($$$$) {
	my ($pa_config, $host, $timeout, $retries) = @_;
	
	# Adjust timeout and retry values
	if ($timeout == 0) {
		$timeout = $pa_config->{'networktimeout'};
	}
	if ($retries == 0) {
		$retries = $pa_config->{'icmp_checks'};
	}
	my $packets = defined($pa_config->{'icmp_packets'}) ? $pa_config->{'icmp_packets'} : 1;
	 
	my $output = 0;
	my $i;
	
	# See codes on http://perldoc.perl.org/perlport.html#PLATFORMS
	my $OSNAME = $^O;
	
	# Windows XP .. Windows 7
	if (($OSNAME eq "MSWin32") || ($OSNAME eq "MSWin32-x64") || ($OSNAME eq "cygwin")){
		my $ms_timeout = $timeout * 1000;
		for ($i=0; $i < $retries; $i++) {
			$output = `ping -n $packets -w $ms_timeout $host`;
			if ($output =~ /TTL/){
				return 1;
			}
			sleep 1;
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
		for ($i=0; $i < $retries; $i++) {
			`$ping_command -s -n $host 56 $packets >$DEVNULL 2>&1`;
			if ($? == 0) {
				return 1;
			}
			sleep 1;
		}
		return 0;
	}
	
	elsif ($OSNAME eq "freebsd"){
		my $ping_command = "ping -t $timeout";
		
		if ($host =~ /\d+:|:\d+/ ) {
			$ping_command = "ping6";
		}
		
		# Note: timeout(-t) option is not implemented in ping6.
		# 'networktimeout' is not used by ping6 on FreeBSD.
		
		# Ping the host
		for ($i=0; $i < $retries; $i++) {
			`$ping_command -q -n -c $packets $host >$DEVNULL 2>&1`;
			if ($? == 0) {
				return 1;
			}
			sleep 1;
		}
		return 0;
	}

        elsif ($OSNAME eq "netbsd"){                      
		my $ping_command = "ping -w $timeout";

		if ($host =~ /\d+:|:\d+/ ) {
			$ping_command = "ping6";
		}

		# Note: timeout(-w) option is not implemented in ping6.
		# 'networktimeout' is not used by ping6 on NetBSD.

		# Ping the host
		for ($i=0; $i < $retries; $i++) {
			`$ping_command -q -n -c $packets $host >$DEVNULL 2>&1`;
			if ($? == 0) {
				return 1;
			}
			sleep 1;
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
		for ($i=0; $i < $retries; $i++) {
			`$ping_command -q -W $timeout -n -c $packets $host >$DEVNULL 2>&1`;	
			if ($? == 0) {
				return 1;
			}
			sleep 1;
		}
		return 0;
	}
	
	return $output;
}

########################################################################
=head2 C<< pandora_ping_latency (I<$pa_config>, I<$host>) >> 

Ping the given host. Returns the average round-trip time. Returns undef if fails.

=cut
########################################################################
sub pandora_ping_latency ($$$$) {
	my ($pa_config, $host, $timeout, $retries) = @_;

	# Adjust timeout and retry values
	if ($timeout == 0) {
		$timeout = $pa_config->{'networktimeout'};
	}
	if ($retries == 0) {
		$retries = $pa_config->{'icmp_checks'};
	}
	
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
		
		my $ms_timeout = $timeout * 1000;
		$output = `ping -n $retries -w $ms_timeout $host`;
		
		if ($output =~ m/\=\s([0-9]+)ms$/){
			return $1;
		} else {
			return undef;
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
		my @output = `$ping_command -s -n $host 56 $retries 2>$DEVNULL`;
		
		# Something went wrong
		return undef if ($? != 0);
		
		# Parse the output
		my $stats = pop (@output);
		return undef unless ($stats =~ m/([\d\.]+)\/([\d\.]+)\/([\d\.]+)\/([\d\.]+) +ms/);
		return $2;
	}
	
	elsif ($OSNAME eq "freebsd"){
		my $ping_command = "ping -t $timeout";
		
		if ($host =~ /\d+:|:\d+/ ) {
			$ping_command = "ping6";
		}
		
		# Note: timeout(-t) option is not implemented in ping6. 
		# timeout(-t) and waittime(-W) options in ping are not the same as
		# Linux. On latency, there are no way to set timeout.
		# 'networktimeout' is not used on FreeBSD.
		
		# Ping the host
		my @output = `$ping_command -q -n -c $retries $host 2>$DEVNULL`;
		
		# Something went wrong
		return undef if ($? != 0);
		
		# Parse the output
		my $stats = pop (@output);
		return undef unless ($stats =~ m/([\d\.]+)\/([\d\.]+)\/([\d\.]+)\/([\d\.]+) +ms/);
		return $2;
	}

        elsif ($OSNAME eq "netbsd"){
		my $ping_command = "ping -w $timeout";

		if ($host =~ /\d+:|:\d+/ ) {
			$ping_command = "ping6";
		}
              
		# Note: timeout(-w) option is not implemented in ping6.
		# timeout(-w) and waittime(-W) options in ping are not the same as
		# Linux. On latency, there are no way to set timeout.
		# 'networktimeout' is not used on NetBSD.

		# Ping the host
		my @output = `$ping_command -q -n -c $retries $host >$DEVNULL 2>&1`;
               
		# Something went wrong
		return undef in ($? != 0);
              
		# Parse the output
		my $stats = pop (@output);
		return undef unless ($stats =~ m/([\d\.]+)\/([\d\.]+)\/([\d\.]+)\/([\d\.]+) +ms/);
		return $2;
	}
	
	# by default LINUX calls
	else {
		my $ping_command = "ping";
		
		if ($host =~ /\d+:|:\d+/ ) {
			$ping_command = "ping6";
		}
		
		
		# Ping the host
		my @output = `$ping_command -q -W $timeout -n -c $retries $host 2>$DEVNULL`;
		
		# Something went wrong
		return undef if ($? != 0);
		
		# Parse the output
		my $stats = pop (@output);
		return undef unless ($stats =~ m/([\d\.]+)\/([\d\.]+)\/([\d\.]+)\/([\d\.]+) +ms/);
		return $2;
	}
	
	# If no valid get values until now, just return with empty value (not valid)
	return $output;
}

########################################################################
=head2 C<< month_have_days (I<$month>, I<$year>) >> 

Pass a $month (as january 0 number and each month with numbers) and the year
as number (for example 1981). And return the days of this month.

=cut
########################################################################
sub month_have_days($$) {
	my $month= shift(@_);
	my $year= @_ ? shift(@_) : (1900 + (localtime())[5]);
	
	my @monthDays= qw( 31 28 31 30 31 30 31 31 30 31 30 31 );
	
	if (  $year <= 1752  ) {
		# Note:  Although September 1752 only had 19 days,
		# they were numbered 1,2,14..30!
		if (1752 == $year  &&  9 == $month) {
			return 19;
		}
		if (2 == $month  &&  0 == $year % 4) {
			return 29;
		}
	}
	else {
		#Check if Leap year
		if (2 == $month && 0 == $year % 4 && 0 == $year%100
			|| 0 == $year%400) {
			return 29;
		}
	}
	
	return $monthDays[$month];
}

###############################################################################
# Convert a text obj tag to an OID and update the module configuration.
###############################################################################
sub translate_obj ($$$) {
	my ($pa_config, $dbh, $obj) = @_;

	# Pandora FMS's console MIB directory
	my $mib_dir = $pa_config->{'attachment_dir'} . '/mibs';

	# Translate!
	my $oid = `snmptranslate -On -mALL -M+"$mib_dir" $obj 2>$DEVNULL`;

	if ($? != 0) {
		return undef;
	}
	chomp($oid);
	
	return $oid;
}

###############################################################################
# Get the number of seconds left to the next execution of the given cron entry.
###############################################################################
sub cron_next_execution ($) {
	my ($cron) = @_;

	# Check cron conf format
	if ($cron !~ /^((\*|(\d+(-\d+){0,1}))\s*){5}$/) {
		return 300;
	}

	# Get day of the week and month from cron config
	my ($mday, $wday) = (split (/\s/, $cron))[2, 4];

	# Get current time and day of the week
	my $cur_time = time();
	my $cur_wday = (localtime ($cur_time))[6];

	# Any day of the week
	if ($wday eq '*') {
		my $nex_time = cron_next_execution_date ($cron,  $cur_time);
		return $nex_time - time();
	}
	# A range?
	else {
		$wday = cron_get_closest_in_range ($cur_wday, $wday);
	}

	# A specific day of the week
	my $count = 0;
	my $nex_time = $cur_time;
	do {
		$nex_time = cron_next_execution_date ($cron, $nex_time);
		my $nex_time_wd = $nex_time;
		my ($nex_mon, $nex_wday) = (localtime ($nex_time_wd))[4, 6];
		my $nex_mon_wd;
		do {
			# Check the day of the week
			if ($nex_wday == $wday) {
				return $nex_time_wd - time();
			}
			
			# Move to the next day of the month
			$nex_time_wd += 86400;
			($nex_mon_wd, $nex_wday) = (localtime ($nex_time_wd))[4, 6];
		} while ($mday eq '*' && $nex_mon_wd == $nex_mon);
		$count++;
	} while ($count < 60);

	# Something went wrong, default to 5 minutes
	return 300;
}
###############################################################################
# Get the number of seconds left to the next execution of the given cron entry.
###############################################################################
sub cron_check_syntax ($) {
	my ($cron) = @_;
	
	return 0 if !defined ($cron);
	return ($cron =~ m/^(\d|\*|-)+ (\d|\*|-)+ (\d|\*|-)+ (\d|\*|-)+ (\d|\*|-)+$/);
}
###############################################################################
# Get the next execution date for the given cron entry in seconds since epoch.
###############################################################################
sub cron_next_execution_date ($$) {
	my ($cron, $cur_time) = @_;

	# Get cron configuration
	my ($min, $hour, $mday, $mon, $wday) = split (/\s/, $cron);

	# Months start from 0
	if($mon ne '*') {
		$mon -= 1;
	}

	# Get current time
	if (! defined ($cur_time)) {
		$cur_time = time();
	}
	my ($cur_min, $cur_hour, $cur_mday, $cur_mon, $cur_year) = (localtime ($cur_time))[1, 2, 3, 4, 5];
	
	# Parse intervals
	$min = cron_get_closest_in_range ($cur_min, $min);
	$hour = cron_get_closest_in_range ($cur_hour, $hour);
	$mday = cron_get_closest_in_range ($cur_mday, $mday);
	$mon = cron_get_closest_in_range ($cur_mon, $mon);

	# Get first next date candidate from cron configuration
	my ($nex_min, $nex_hour, $nex_mday, $nex_mon, $nex_year) = ($min, $hour, $mday, $mon, $cur_year);

	# Replace wildcards
	if ($min eq '*') {
		if ($hour ne '*' || $mday ne '*' || $wday ne '*' || $mon ne '*') {
			$nex_min = 0;
		}
		else {
			$nex_min = $cur_min;
		}
	}
	if ($hour eq '*') {
		if ($mday ne '*' || $wday ne '*' ||$mon ne '*') {
			$nex_hour = 0;
		}
		else {
			$nex_hour = $cur_hour;
		}
	}
	if ($mday eq '*') {
		if ($mon ne '*') {
			$nex_mday = 1;
		}
		else {
			$nex_mday = $cur_mday;
		}
	}
	if ($mon eq '*') {
		$nex_mon = $cur_mon;
	}

	# Find the next execution date
	my $count = 0;
	do {
		my $next_time = timelocal(0, $nex_min, $nex_hour, $nex_mday, $nex_mon, $nex_year);
		if ($next_time > $cur_time) {
			return $next_time;
		}
		if ($min eq '*' && $hour eq '*' && $wday eq '*' && $mday eq '*' && $mon eq '*') {
			($nex_min, $nex_hour, $nex_mday, $nex_mon, $nex_year) = (localtime ($next_time + 60))[1, 2, 3, 4, 5];
		}
		elsif ($hour eq '*' && $wday eq '*' && $mday eq '*' && $mon eq '*') {
			($nex_min, $nex_hour, $nex_mday, $nex_mon, $nex_year) = (localtime ($next_time + 3600))[1, 2, 3, 4, 5];
		}
		elsif ($mday eq '*' && $mon eq '*') {
			($nex_min, $nex_hour, $nex_mday, $nex_mon, $nex_year) = (localtime ($next_time + 86400))[1, 2, 3, 4, 5];
		}
		elsif ($mon eq '*') {
			$nex_mon = $nex_mon + 1;
			if ($nex_mon > 11) {
				$nex_mon = 0;
				$nex_year++;
			}
		}
		else {
			$nex_year++;
		}
		$count++;
	} while ($count < 60);
	
	# Something went wrong, default to 5 minutes
	return $cur_time + 300;
}

###############################################################################
# Returns the closest number to the target inside the given range (including
# the target itself).
###############################################################################
sub cron_get_closest_in_range ($$) {
	my ($target, $range) = @_;

	# Not a range
	if ($range !~ /(\d+)\-(\d+)/) {
		return $range;
	}
	
	# Search the closes number to the target in the given range
	my $range_start = $1;
	my $range_end = $2;
	
	# Outside the range
	if ($target <= $range_start || $target > $range_end) {
		return $range_start;
	}
	
	# Inside the range
	return $target;
}

###############################################################################
# Attempt to resolve the given hostname.
###############################################################################
sub resolve_hostname ($) {
	my ($hostname) = @_;
	
	$resolved_hostname = inet_aton($hostname);
	return $hostname if (! defined ($resolved_hostname));
	
	return inet_ntoa($resolved_hostname);
}

###############################################################################
# Returns 1 if the given regular expression is valid, 0 otherwise.
###############################################################################
sub valid_regex ($) {
	my $regex = shift;
	
	eval {
		local $SIG{'__DIE__'};
		qr/$regex/
	};
	
	# Invalid regex
	return 0 if ($@);
	
	# Valid regex
	return 1;
}

###############################################################################
# Returns 1 if a valid metaconsole license is configured, 0 otherwise.
###############################################################################
sub is_metaconsole ($) {
	my ($pa_config) = @_;

	if (defined($pa_config->{"license_type"}) && $pa_config->{"license_type"} == METACONSOLE_LICENSE) {
		return 1;
	}

	return 0;
}

# End of function declaration
# End of defined Code

1;
__END__

