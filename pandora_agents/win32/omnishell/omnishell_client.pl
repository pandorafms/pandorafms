#!/usr/bin/perl
################################################################################
# Pandora FMS Omnishell client.
#
# (c) Fco de Borja Sánchez <fborja.sanchez@pandorafms.com>
#
################################################################################
use strict;
use warnings;

use lib '/usr/lib/perl5';
use PandoraFMS::Tools;
use PandoraFMS::Omnishell;

my %Conf;

if ($Conf{'debug'} ne '1') {
  # Check scheduled commands	
  my $omni = new PandoraFMS::Omnishell(\%Conf);
  $omni->prepare_commands();
}