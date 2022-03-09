#!/usr/bin/perl
################################################################################
# Pandora FMS Omnishell client.
#
# (c) Fco de Borja Sánchez <fborja.sanchez@pandorafms.com>
#
# Usage: omnishell_client "C:\Program Files\pandora_agent\pandora_agent.conf"
#
################################################################################
use strict;
use warnings;

use File::Basename;
BEGIN { push @INC, '/usr/lib/perl5'; }
use PandoraFMS::PluginTools;
use PandoraFMS::Omnishell;

################################################################################
# Definitions
################################################################################
my $HELP=<<EO_H;
Pandora FMS Omnishell client.

Usage:

  $0 <configuration_file> [-debug 1]

  Where <configuration_file> is your Pandora FMS Agent configuration.
  *Recommended: use full path.

  Use -debug 1 to get information extra in STDERR

EO_H

################################################################################
# Parse commands.
################################################################################
sub read_commands {
  my ($config, $exp, $line, $file) = @_;

  if (empty($config->{'commands'})) {
    $config->{'commands'} = {};
  }

  my ($ref) = $line =~ /$exp\s+(.*)/;
  $config->{'commands'}->{trim($ref)} = {};

  return $config;
}


################################################################################
# MAIN
################################################################################
my $ConfFile = $ARGV[0];

my $config = read_configuration({},' ', [
  {
  	'exp'    => 'cmd_file',
  	'target' => \&read_commands
  },
]);

if (!defined($ConfFile) || !-e $ConfFile) {
  print $HELP;
  exit 0;
}

if(!-d dirname($ConfFile).'\commands') {
	mkdir(dirname($ConfFile).'\commands');
}

eval {
  # Check scheduled commands	
  my $omnishell = new PandoraFMS::Omnishell(
    {
      %{$config},
      'ConfDir' => dirname($ConfFile)
    }
  );

  if (empty($omnishell->run('xml')) && is_enabled($config->{'debug'}) ) {
    print STDERR $omnishell->get_last_error()."\n";
  }
};
if ($@) {
  if (is_enabled($config->{'debug'})) {
    print STDERR $@."\n";
  }
  exit 0;
}

exit 0;