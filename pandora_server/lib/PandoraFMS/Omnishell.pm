package PandoraFMS::Omnishell;
################################################################################
# Pandora FMS Omnishell common functions.
#
# (c) Fco de Borja Sánchez <fborja.sanchez@pandorafms.com>
#
################################################################################
use strict;
use warnings;

use File::Copy;
use Scalar::Util qw(looks_like_number);
use lib '/usr/lib/perl5';
use PandoraFMS::PluginTools qw/init read_configuration read_file empty trim/;

my $YAML = 0;
# Dynamic load. Avoid unwanted behaviour.
eval {
  eval 'require YAML::Tiny;1' or die('YAML::Tiny lib not found, commands feature won\'t be available');
};
if ($@) {
  $YAML = 0;
} else {
  $YAML = 1;
}

use lib '/usr/lib/perl5';

our @ISA = ("Exporter");
our %EXPORT_TAGS = ( 'all' => [ qw( ) ] );
our @EXPORT_OK = ( @{ $EXPORT_TAGS{'all'} } );
our @EXPORT = qw();

# 2 to the power of 32.
use constant POW232 => 2**32;

################################################################################
# Return the MD5 checksum of the given string as a hex string.
# Pseudocode from: http://en.wikipedia.org/wiki/MD5#Pseudocode
################################################################################
my @S = (
	7, 12, 17, 22,  7, 12, 17, 22,  7, 12, 17, 22,  7, 12, 17, 22,
	5,  9, 14, 20,  5,  9, 14, 20,  5,  9, 14, 20,  5,  9, 14, 20,
	4, 11, 16, 23,  4, 11, 16, 23,  4, 11, 16, 23,  4, 11, 16, 23,
	6, 10, 15, 21,  6, 10, 15, 21,  6, 10, 15, 21,  6, 10, 15, 21
);
my @K = (
	0xd76aa478, 0xe8c7b756, 0x242070db, 0xc1bdceee,
	0xf57c0faf, 0x4787c62a, 0xa8304613, 0xfd469501,
	0x698098d8, 0x8b44f7af, 0xffff5bb1, 0x895cd7be,
	0x6b901122, 0xfd987193, 0xa679438e, 0x49b40821,
	0xf61e2562, 0xc040b340, 0x265e5a51, 0xe9b6c7aa,
	0xd62f105d, 0x02441453, 0xd8a1e681, 0xe7d3fbc8,
	0x21e1cde6, 0xc33707d6, 0xf4d50d87, 0x455a14ed,
	0xa9e3e905, 0xfcefa3f8, 0x676f02d9, 0x8d2a4c8a,
	0xfffa3942, 0x8771f681, 0x6d9d6122, 0xfde5380c,
	0xa4beea44, 0x4bdecfa9, 0xf6bb4b60, 0xbebfbc70,
	0x289b7ec6, 0xeaa127fa, 0xd4ef3085, 0x04881d05,
	0xd9d4d039, 0xe6db99e5, 0x1fa27cf8, 0xc4ac5665,
	0xf4292244, 0x432aff97, 0xab9423a7, 0xfc93a039,
	0x655b59c3, 0x8f0ccc92, 0xffeff47d, 0x85845dd1,
	0x6fa87e4f, 0xfe2ce6e0, 0xa3014314, 0x4e0811a1,
	0xf7537e82, 0xbd3af235, 0x2ad7d2bb, 0xeb86d391
);
sub md5 {
	my $str = shift;

	# No input!
	if (!defined($str)) {
		return "";
	}

	# Note: All variables are unsigned 32 bits and wrap modulo 2^32 when
	# calculating.

	# Initialize variables.
	my $h0 = 0x67452301;
	my $h1 = 0xEFCDAB89;
	my $h2 = 0x98BADCFE;
	my $h3 = 0x10325476;

	# Pre-processing.
	my $msg = unpack ("B*", pack ("A*", $str));
	my $bit_len = length ($msg);

	# Append "1" bit to message.
	$msg .= '1';

	# Append "0" bits until message length in bits ≡ 448 (mod 512).
	$msg .= '0' while ((length ($msg) % 512) != 448);

	# Append bit /* bit, not byte */ length of unpadded message as 64-bit
	# little-endian integer to message.
	$msg .= unpack ("B32", pack ("V", $bit_len));
	$msg .= unpack ("B32", pack ("V", ($bit_len >> 16) >> 16));

	# Process the message in successive 512-bit chunks.
	for (my $i = 0; $i < length ($msg); $i += 512) {

		my @w;
		my $chunk = substr ($msg, $i, 512);

		# Break chunk into sixteen 32-bit little-endian words w[i], 0 <= i <=
		# 15.
		for (my $j = 0; $j < length ($chunk); $j += 32) {
			push (@w, unpack ("V", pack ("B32", substr ($chunk, $j, 32))));
		}

		# Initialize hash value for this chunk.
		my $a = $h0;
		my $b = $h1;
		my $c = $h2;
		my $d = $h3;
		my $f;
		my $g;

		# Main loop.
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
			$b = ($b + leftrotate (($a + $f + $K[$y] + $w[$g]) % POW232, $S[$y])) % POW232;
			$a = $temp;
		}

		# Add this chunk's hash to result so far.
		$h0 = ($h0 + $a) % POW232;
		$h1 = ($h1 + $b) % POW232;
		$h2 = ($h2 + $c) % POW232;
		$h3 = ($h3 + $d) % POW232;
	}

	# Digest := h0 append h1 append h2 append h3 #(expressed as little-endian)
	return unpack ("H*", pack ("V", $h0)) .
	       unpack ("H*", pack ("V", $h1)) .
	       unpack ("H*", pack ("V", $h2)) .
	       unpack ("H*", pack ("V", $h3));
}

################################################################################
# MD5 leftrotate function. See: http://en.wikipedia.org/wiki/MD5#Pseudocode
################################################################################
sub leftrotate {
	my ($x, $c) = @_;

	return (0xFFFFFFFF & ($x << $c)) | ($x >> (32 - $c));
}

################################################################################
# return last error.
################################################################################
sub get_last_error {
  my ($self) = @_;

  if (!empty($self->{'last_error'})) {
    return $self->{'last_error'};
  }

  return '';
}

################################################################################
# Update last error.
################################################################################
sub set_last_error {
	my ($self, $error) = @_;

  $self->{'last_error'} = $error;
}

################################################################################
# Try to load extra libraries.c
################################################################################
sub load_libraries {
  my $self = shift;

  # Dynamic load. Avoid unwanted behaviour.
  eval {eval 'require YAML::Tiny;1' or die('YAML::Tiny lib not found, commands feature won\'t be available');};
  if ($@) {
    $self->set_last_error($@);
    return 0;
  } else {
    return 1;
  }
}

################################################################################
# Create new omnishell handler.
################################################################################
sub new {
  my ($class, $args) = @_;

  if (ref($args) ne 'HASH') {
    return undef;
  }

  my $system = init();
  my $self = {
	'server_ip' => 'localhost',
	  'server_path' => '/var/spool/pandora/data_in',
	  'server_port' => 41121,
	  'transfer_mode' => 'tentacle',
	  'transfer_mode_user' => 'apache',
	  'transfer_timeout' => 30,
	  'server_user' => 'pandora',
	  'server_pwd' => '',
	  'server_ssl' => '0',
	  'server_opts' => '',
	  'delayed_startup' => 0,
	  'pandora_nice' => 10,
	  'cron_mode' => 0,
    'last_error' => undef,
    %{$system},
    %{$args},
  };

  $self = bless($self, $class);
  $self->prepare_commands();

  return $self;
}

################################################################################
# Run all, output mode 'xml'  will dump to STDOUT and return an array of strings
#, any other option will return an array with all results.
################################################################################
sub run {
  my ($self, $output_mode) = @_;

  my @results;

  foreach my $ref (keys %{$self->{'commands'}}) {
    my $rs = $self->runCommand($ref, $output_mode);
    if ($rs) {
      push @results, $rs;
    }
  }

  if ($output_mode eq 'xml') {
    print join("\n",@results);
  }

  return \@results;
}

################################################################################
# Run command, output mode 'xml'  will dump to STDOUT, other will return a hash
# with all results.
################################################################################
sub runCommand {
  my ($self, $ref, $output_mode) = @_;

  if($self->load_libraries()) {
    # Functionality possible.
    my $command = $self->{'commands'}->{$ref};
    my $result = $self->evaluate_command($ref);
    if (ref($result) eq "HASH") {
      # Process command result.
      if (defined($output_mode) && $output_mode eq 'xml') {
        my $output = '';
        $output .= "<cmd_report>\n";
        $output .= "  <cmd_response>\n";
        $output .= "    <cmd_name><![CDATA[".$result->{'name'}."]]></cmd_name>\n";
        $output .= "    <cmd_key><![CDATA[".$ref."]]></cmd_key>\n";
        $output .= "    <cmd_errorlevel><![CDATA[".$result->{'error_level'}."]]></cmd_errorlevel>\n";
        $output .= "    <cmd_stdout><![CDATA[".$result->{'stdout'}."]]></cmd_stdout>\n";
        $output .= "    <cmd_stderr><![CDATA[".$result->{'stderr'}."]]></cmd_stderr>\n";
        $output .= "  </cmd_response>\n";
        $output .= "</cmd_report>\n";

        return $output;
      }
      return $result;
    } else {
      $self->set_last_error('Failed to process ['.$ref.']: '.$result);
    }
  }

  return undef;
}

################################################################################
# Check for remote commands defined.
################################################################################
sub prepare_commands {
  my ($self) = @_;

  if ($YAML == 0) {
    $self->set_last_error('Cannot use commands without YAML dependency, please install it.');
    return;
  }

  # Force configuration file read.
  my $commands = $self->{'commands'};  

  if (empty($commands)) {
    $self->{'commands'} = {};
  } else {
    foreach my $rcmd (keys %{$commands}) {
      $self->{'commands'}->{trim($rcmd)} = {};
    }
  }

  # Cleanup old commands. Not registered.
  $self->cleanup_old_commands();

  foreach my $ref (keys %{$self->{'commands'}}) {
    my $file_content;
    my $download = 0;
    my $rcmd_file = $self->{'ConfDir'}.'/commands/'.$ref.'.rcmd';

    # Search for local .rcmd file
    if (-e $rcmd_file) {
      my $remote_md5_file = $self->{'temporal'}.'/'.$ref.'.md5';

      $file_content = read_file($rcmd_file);
      if ($self->recv_file($ref.'.md5', $remote_md5_file) != 0) {
        # Remote file could not be retrieved, skip.
        delete $self->{'commands'}->{$ref};
        next;
      }

      my $local_md5 = md5($file_content);
      my $remote_md5 = md5(read_file($remote_md5_file));

      if ($local_md5 ne $remote_md5) {
        # Must be downloaded again.
        $download = 1;
      }
    } else {
      $download = 1;
    }
    
    # Search for remote .rcmd file
    if ($download == 1) {
      # Download .rcmd file
      if ($self->recv_file($ref.'.rcmd') != 0) {
        # Remote file could not be retrieved, skip.
        delete $self->{'commands'}->{$ref};
        next;
      } else {
        # Success
        move($self->{'temporal'}.'/'.$ref.'.rcmd', $rcmd_file);
      }
    }
    
    # Parse and prepare in memory skel.
    eval {
      $self->{'commands'}->{$ref} = YAML::Tiny->read($rcmd_file);
    };
    if ($@) {
      # Failed.
      $self->set_last_error('Failed to decode command. ' . "\n".$@);
      delete $self->{'commands'}->{$ref};
      next;
    }

  }
}

################################################################################
# Command report.
################################################################################
sub report_command {
  my ($self, $ref, $err_level) = @_;

  # Retrieve content from .stdout and .stderr
  my $stdout_file = $self->{'temporal'}.'/'.$ref.'.stdout';
  my $stderr_file = $self->{'temporal'}.'/'.$ref.'.stderr';

  my $return;
  eval {
    $return = {
      'error_level' => $err_level,
      'stdout' => read_file($stdout_file),
      'stderr' => read_file($stderr_file),
    };

    $return->{'name'} = $self->{'commands'}->{$ref}->[0]->{'name'};
  };
  if ($@) {
    $self->set_last_error('Failed to report command output. ' . $@);
  }

  # Cleanup
  unlink($stdout_file) if (-e $stdout_file);
  unlink($stderr_file) if (-e $stderr_file);

  # Mark command as done.
  open (my $R_FILE, '> '.$self->{'ConfDir'}.'/commands/'.$ref.'.rcmd.done');
  print $R_FILE $err_level;
  close($R_FILE);


  $return->{'stdout'} = '' unless defined ($return->{'stdout'});
  $return->{'stderr'} = '' unless defined ($return->{'stderr'});

  return $return;
}

################################################################################
# Cleanup unreferenced rcmd and rcmd.done files.
################################################################################
sub cleanup_old_commands {
  my ($self) = @_;

  # Cleanup old .rcmd and .rcmd.done files.
  my %registered = map { $_.'.rcmd' => 1 } keys %{$self->{'commands'}};
  if(opendir(my $dir, $self->{'ConfDir'}.'/commands/')) {
    while (my $item = readdir($dir)) {

      # Skip other files.
      next if ($item !~ /\.rcmd$/);

      # Clean .rcmd.done file if its command is not referenced in conf.
      if (!defined($registered{$item})) {
        if (-e $self->{'ConfDir'}.'/commands/'.$item) {
          unlink($self->{'ConfDir'}.'/commands/'.$item);
        }
        if (-e $self->{'ConfDir'}.'/commands/'.$item.'.done') {
          unlink($self->{'ConfDir'}.'/commands/'.$item.'.done');
        }
      }
    }

    # Close dir.
    closedir($dir);
  }

}

################################################################################
# Executes a command using defined timeout.
################################################################################
sub execute_command_timeout {
  my ($self, $cmd, $timeout) = @_;

  if (!defined($timeout)
    || !looks_like_number($timeout)
    || $timeout <= 0
  ) {
    `$cmd`;
    return $?>>8;
  }

  my $remaining_timeout = $timeout;

  my $RET;
  my $output;

  my $pid = open ($RET, "-|");
  if (!defined($pid)) {
    # Failed to fork.
    $self->set_last_error('[command] Failed to fork.');
    return undef;
  }
  if ($pid == 0) {
    # Child.
    my $ret;
    eval {
      local $SIG{ALRM} = sub { die "timeout\n" };
      alarm $timeout;
      `$cmd`;
      alarm 0;
    };

    my $result = ($?>>8);
    return $result;

    # Exit child.
    # Child finishes.
    exit;

  } else {
    # Parent waiting.
    while( --$remaining_timeout > 0 ){
      if (wait == -1) {
        last;
      }
      # Wait child up to timeout seconds.
      sleep 1;
    }
  }

  if ($remaining_timeout > 0) {
    # Retrieve output from child.
    $output = do { local $/; <$RET> };
    $output = $output>>8;
  }
  else {
    # Timeout expired.
    return 124;
  }

  close($RET);

  return $output;
}

################################################################################
# Executes a block of commands, returns error level, leaves output in 
# redirection set by $std_files. E.g:
# $std_files = ' >>  /tmp/stdout 2>> /tmp/stderr
################################################################################
sub execute_command_block {
  my ($self, $commands, $std_files, $timeout, $retry) = @_;

  return 0 unless defined($commands);

  my $retries = $retry;

  $retries = 1 unless looks_like_number($retries) && $retries > 0;

  my $err_level = 0;
  $std_files = '' unless defined ($std_files);

  if (ref($commands) ne "ARRAY") {
    return 0 if $commands eq '';

    do {
      $err_level = $self->execute_command_timeout(
        "($commands) $std_files",
        $timeout
      );

      # Do not retry if success.
      last if looks_like_number($err_level) && $err_level == 0;
    } while ((--$retries) > 0);

  } else {
    foreach my $comm (@{$commands}) {
      next unless defined($comm);
      $retries = $retry;
      $retries = 1 unless looks_like_number($retries) && $retries > 0;

      do {
        $err_level = $self->execute_command_timeout(
          "($comm) $std_files",
          $timeout
        );

        # Do not retry if success.
        $retries = 0 if looks_like_number($err_level) && $err_level == 0;

      } while ((--$retries) > 0);

      # Do not continue evaluating block if failed.
      last unless ($err_level == 0);
    }
  }

  return $err_level;
}

################################################################################
# Evalate given command.
################################################################################
sub evaluate_command {
  my ($self, $ref) = @_;

  # Not found.
  return "undefined command" unless defined $self->{'commands'}->{$ref};

  # Already completed.
  return "already executed" if (-e $self->{'ConfDir'}.'/commands/'.$ref.'.rcmd.done');

  # [0] because how library works.
  my $cmd = $self->{'commands'}->{$ref}->[0];

  my $std_files = ' >> '.$self->{'temporal'}.'/'.$ref.'.stdout ';
  $std_files .= ' 2>> '.$self->{'temporal'}.'/'.$ref.'.stderr ';

  # Check preconditions
  my $err_level;
  
  $err_level = $self->execute_command_block(
    $cmd->{'preconditions'},
    $std_files,
    $cmd->{'timeout'}
  );

  # Precondition not satisfied.
  return $self->report_command($ref, $err_level) unless ($err_level == 0);

  # Main run.
  $err_level = $self->execute_command_block(
    $cmd->{'script'},
    $std_files,
    $cmd->{'timeout'}
  );

  # Script not success.
  return $self->report_command($ref, $err_level) unless ($err_level == 0);

  # Check postconditions
  $err_level = $self->execute_command_block(
    $cmd->{'postconditions'},
    $std_files,
    $cmd->{'timeout'}
  );

  # Return results.
  return $self->report_command($ref, $err_level);
}

################################################################################
# File transference and imported methods
################################################################################
################################################################################
## Remove any trailing / from directory names.
################################################################################
sub fix_directory ($) {
	my $dir = shift;

	my $char = chop($dir);
	return $dir if ($char eq '/');
	return $dir . $char;
}

################################################################################
# Receive a file from the server.
################################################################################
sub recv_file {
  my ($self, $file, $relative) = @_;
  my $output;

  my $DevNull = $self->{'__system'}->{'devnull'};
  my $CmdSep = $self->{'__system'}->{'cmdsep'};
  
  my $pid = fork();
    return 1 unless defined $pid;

  # Fix remote dir to some transfer mode
  my $remote_dir = $self->{'server_path'};
  $remote_dir .= "/" . fix_directory($relative) if defined($relative);

  if ($pid == 0) {
    # execute the transfer program by child process.
    eval {
      local $SIG{'ALRM'} = sub {die};
      alarm ($self->{'transfer_timeout'});
      if ($self->{'transfer_mode'} eq 'tentacle') {
         $output = `cd "$self->{'temporal'}"$CmdSep tentacle_client -v -g -a $self->{'server_ip'} -p $self->{'server_port'} $self->{'server_opts'} $file 2>&1 >$DevNull`
      } elsif ($self->{'transfer_mode'} eq 'ssh') {
         $output = `scp -P $self->{'server_port'} pandora@"$self->{'server_ip'}:$self->{'server_path'}/$file" $self->{'temporal'} 2>&1 >$DevNull`;
      } elsif ($self->{'transfer_mode'} eq 'ftp') {
        my $base = basename ($file);
        my $dir = dirname ($file);

        $output = `ftp -n $self->{'server_opts'} $self->{'server_ip'} $self->{'server_port'} 2>&1 >$DevNull <<FEOF1
        quote USER $self->{'server_user'}
        quote PASS $self->{'server_pwd'}
        lcd "$self->{'temporal'}"
        cd "$self->{'server_path'}"
        get "$file"
        quit
        FEOF1`
      } elsif ($self->{'transfer_mode'} eq 'local') {
        $output = `cp "$remote_dir/$file" $self->{'temporal'} 2>&1 >$DevNull`;
      }
      alarm (0);
    };

    if ($@) {
      $self->set_last_error("Error retrieving file: '.$file.' File transfer command is not responding.");
      exit 1;
    }

    # Get the errorlevel
    my $rc = $? >> 8;
    if ($rc != 0) {
      $self->set_last_error("Error retrieving file: '$file' $output");
    }
    exit $rc;
  }

  # Wait the child process termination and get the errorlevel
  waitpid ($pid, 0);
  my $rc = $? >> 8;

  return $rc;
}


1;