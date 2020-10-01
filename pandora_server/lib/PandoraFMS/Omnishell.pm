package PandoraFMS::Omnishell;
################################################################################
# Pandora FMS Omnishell common functions.
#
# (c) Fco de Borja Sánchez <fborja.sanchez@pandorafms.com>
#
################################################################################
use strict;
use warnings;

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


################################################################################
# return last error.
################################################################################
sub get_last_error {
  my ($self) = @_;

  if (!is_empty($self->{'last_error'})) {
    return $self->{'last_error'};
  }

  return '';
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
	my ($class,$args) = @_;

  if (ref($args) ne 'HASH') {
    return undef;
  }

  my $self = {
    'last_error' => undef,
	  %{$args}
  };


  $self = bless($self, $class);

  return $self;
}

################################################################################
# Run process.
################################################################################
sub run {
	my ($self) = @_;

	if($self->load_libraries()) {
		$self->prepare_commands();
	}
}

################################################################################
# Check for remote commands defined.
################################################################################
sub prepare_commands {
  my ($self) = @_;

	if ($YAML == 0) {
		log_message(
			'error',
			'Cannot use commands without YAML dependency, please install it.'
		);
		return;
	}

	# Force configuration file read.
	my @commands = read_config('cmd_file');

	if (empty(\@commands)) {
		$self->{'commands'} = {};
	} else {
		foreach my $rcmd (@commands) {
			$self->{'commands'}->{trim($rcmd)} = {};
		}
	}

	# Cleanup old commands. Not registered.
	cleanup_old_commands();

	foreach my $ref (keys %{$self->{'commands'}}) {
		my $file_content;
		my $download = 0;
		my $rcmd_file = $self->{'ConfDir'}.'/commands/'.$ref.'.rcmd';

		# Check for local .rcmd.done files
		if (-e $rcmd_file.'.done') {
			# Ignore.
			delete $self->{'commands'}->{$ref};
			next;
		}

		# Search for local .rcmd file
		if (-e $rcmd_file) {
			my $remote_md5_file = $self->{'temporal'}.'/'.$ref.'.md5';

			$file_content = read_file($rcmd_file);
			if (recv_file($ref.'.md5', $remote_md5_file) != 0) {
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
			if (recv_file($ref.'.rcmd') != 0) {
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
			log_message('error', 'Failed to decode command. ' . "\n".$@);
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
		log_message('error', 'Failed to report command output. ' . $@);
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
	my ($cmd, $timeout) = @_;

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
		log_message('error', '[command] Failed to fork.');
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
	my ($commands, $std_files, $timeout, $retry) = @_;

	return 0 unless defined($commands);

	my $retries = $retry;

	$retries = 1 unless looks_like_number($retries) && $retries > 0;

	my $err_level = 0;
	$std_files = '' unless defined ($std_files);

	if (ref($commands) ne "ARRAY") {
		return 0 if $commands eq '';

		do {
			$err_level = execute_command_timeout(
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
				$err_level = execute_command_timeout(
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
	return unless defined $self->{'commands'}->{$ref};

	# Already completed.
	return if (-e $self->{'ConfDir'}.'/commands/'.$ref.'.rcmd.done');

	# [0] because how library works.
	my $cmd = $self->{'commands'}->{$ref}->[0];

	my $std_files = ' >> '.$self->{'temporal'}.'/'.$ref.'.stdout ';
	$std_files .= ' 2>> '.$self->{'temporal'}.'/'.$ref.'.stderr ';

	# Check preconditions
	my $err_level;
	
	$err_level = execute_command_block(
		$cmd->{'preconditions'},
		$std_files,
		$cmd->{'timeout'}
	);

	# Precondition not satisfied.
	return report_command($ref, $err_level) unless ($err_level == 0);

	# Main run.
	$err_level = execute_command_block(
		$cmd->{'script'},
		$std_files,
		$cmd->{'timeout'}
	);

	# Script not success.
	return report_command($ref, $err_level) unless ($err_level == 0);

	# Check postconditions
	$err_level = execute_command_block(
		$cmd->{'postconditions'},
		$std_files,
		$cmd->{'timeout'}
	);

	# Return results.
	return report_command($ref, $err_level);
}

1;