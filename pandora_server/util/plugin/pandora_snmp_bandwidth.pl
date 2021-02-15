#!/usr/bin/perl
#
################################################################################
#
# Bandwith plugin
# 
# Requirements:
#   snmpget
#   snmpwalk
# 
# (c) Fco de Borja Sanchez <fborja.sanchez@artica.es>
#
# 2018/06/27
#   Changes:
#       First version
#
################################################################################

use strict;
use warnings;

use POSIX qw(strftime);

use lib '/usr/lib/perl5';
use PandoraFMS::PluginTools;

use Data::Dumper;
$Data::Dumper::Sortkeys = 1;

# version: Defines actual version of Pandora FMS
my $pandora_version = "7.0NG.752";
my $pandora_build = "210212";
our $VERSION = $pandora_version." ".$pandora_build;

my $HELP=<<EO_HELP;

Pandora FMS Server plugin for bandwidth monitoring $VERSION

Where OPTIONS could be:

[SNMP]
    -community       community
    -version         SNMP version (1,2c,3)
    -host            target host
    -port            target port (161)

[SNMPv3]
    -securityName    
    -context         
    -securityLevel   
    -authProtocol    
    -authKey         
    -privProtocol    
    -privKey

[EXTRA]
    -ifIndex         Target interface to retrieve, if not specified, total
                     bandwith will be reported.
    -uniqid          Use custom temporary file name.

Note: You can also use snmpget/snmpwalk argument notation,
e.g. -v is equal to -version, -c to -community, etc.

EO_HELP

use constant {
  UNKNOWN_DUPLEX => 0,
  HALF_DUPLEX => 2,
  FULL_DUPLEX => 3,
};

################################################################################
# Translate argument to config hash key
################################################################################
sub update_config_key ($) {
  my $arg = shift;
  if ($arg eq "c"){
    return "community";
  }
  if ($arg eq "v"){
    return "version";
  }
  if ($arg eq "h"){
    return "host";
  }
  if ($arg eq "p"){
    return "port";
  }
  if ($arg eq "o"){
    return "oid_base";
  }
  if ($arg eq "d"){
    return "datatype";
  }
  if ($arg eq "u"){
    return "securityName";
  }
  if ($arg eq "n"){
    return "context";
  }
  if ($arg eq "l"){
    return "securityLevel";
  }
  if ($arg eq "a"){
    return "authProtocol";
  }
  if ($arg eq "A"){
    return "authKey";
  }
  if ($arg eq "x"){
    return "privProtocol";
  }
  if ($arg eq "X"){
    return "privKey";
  }
  if ($arg eq "agent") {
    return "agent_name";
  }
  if ($arg eq "names") {
    return "names";
  }
  if ($arg eq "branches") {
    return "branches";
  }
  if ($arg eq 'ifIndex') {
    return "ifIndex";
  }
if ($arg eq 'uniqid') {
	return "uniqid";
}
}

################################################################################
# Prepare analysis tree
################################################################################
sub prepare_tree {
  my ($config) = @_;
  my $tree;

  my %snmp_call = %{$config};
  my $ifIndex = $config->{'ifIndex'};
  $ifIndex = '' if empty($ifIndex);
  if (!empty($ifIndex) && $ifIndex !~ /^\./) {
    $ifIndex = '.'.$ifIndex;
  }

  if (is_enabled($config->{'use_x64'})) {
    $snmp_call{'oid'} = $config->{'oid_base'} . $config->{'x64_indexes'}{'__idx__'}.$ifIndex;
  } else {
    $snmp_call{'oid'} = $config->{'oid_base'} . $config->{'x86_indexes'}{'__idx__'}.$ifIndex;
  }

  my $raw = snmp_walk(\%snmp_call);
  return $raw if (ref($raw) eq "HASH");
  
  my @data = split /\n/, $raw;
  foreach my $it (@data) {
    my ($key, $value) = split /=/, $it;
    $value = trim($value);
    $key = trim($key);
    $value =~ s/^.*:\ {0,1}//;

    if ($value =~ /No such instance/i) {
      return {};
    }

    $ifIndex = $value;
    if ($ifIndex !~ /^\./) {
      $ifIndex = '.'.$ifIndex;
    }

    my %inOctets_call = %{$config};
    if (is_enabled($config->{'use_x64'})) {
      $inOctets_call{'oid'} = $config->{'oid_base'};
      $inOctets_call{'oid'} .= $config->{'x64_indexes'}{'inOctets'}.$ifIndex;
    } else {
      $inOctets_call{'oid'} = $config->{'oid_base'};
      $inOctets_call{'oid'} .= $config->{'x86_indexes'}{'inOctets'}.$ifIndex;
    }

    my $inOctets = snmp_get(\%inOctets_call);
    if (ref($inOctets) eq "HASH") {
      if ($inOctets->{'data'} eq '') {
        $inOctets = 0;
      } else {
        $inOctets = int $inOctets->{'data'};
      }
    } else {
      # Ignore, cannot retrieve inOctets.
      next;
    }

		my %outOctets_call = %{$config};
		if (is_enabled($config->{'use_x64'})) {
			$outOctets_call{'oid'} = $config->{'oid_base'};
			$outOctets_call{'oid'} .= $config->{'x64_indexes'}{'outOctets'}.$ifIndex;
		} else {
			$outOctets_call{'oid'} = $config->{'oid_base'};
			$outOctets_call{'oid'} .= $config->{'x86_indexes'}{'outOctets'}.$ifIndex;
		}

    my $outOctets = snmp_get(\%outOctets_call);
    if (ref($outOctets) eq "HASH") {
      if ($outOctets->{'data'} eq '') {
        $outOctets = 0;
      } else {
        $outOctets = int $outOctets->{'data'};
      }
    } else {
      # Ignore, cannot retrieve inOctets.
      next;
    }

    my %duplex_call = %{$config};
		if (is_enabled($config->{'use_x64'})) {
			$duplex_call{'oid'} = $config->{'oid_base'};
			$duplex_call{'oid'} .= $config->{'x64_indexes'}{'duplex'}.$ifIndex;
		} else {
			$duplex_call{'oid'} = $config->{'oid_base'};
			$duplex_call{'oid'} .= $config->{'x86_indexes'}{'duplex'}.$ifIndex;
		}

    my $duplex = snmp_get(\%duplex_call);
    if (ref($duplex) eq "HASH") {
      if ($duplex->{'data'} eq '') {
        $duplex = 0;
      } else {
        $duplex = int $duplex->{'data'};
      }
      
    } else {
      # Ignore, cannot retrieve inOctets.
      next;
    }

     my %speed = %{$config};
    if (is_enabled($config->{'use_x64'})) {
      $speed{'oid'} = $config->{'oid_base'};
      $speed{'oid'} .= $config->{'x64_indexes'}{'ifSpeed'}.$ifIndex;
    } else {
      $speed{'oid'} = $config->{'oid_base'};
      $speed{'oid'} .= $config->{'x86_indexes'}{'ifSpeed'}.$ifIndex;
    }

    my $speed = snmp_get(\%speed);
    if (ref($speed) eq "HASH") {
      $speed = int $speed->{'data'};
    } else {
      # Ignore, cannot retrieve inOctets.
      next;
    }

    {
      no warnings "uninitialized";
      $tree->{$value} = {
        'duplex' => int $duplex,
        'speed'  => int $speed,
        'now'    => {
          'timestamp' => time(),
          'inOctets'  => int $inOctets,
          'outOctets' => int $outOctets,
        },
      };
    };
  }

  load_data($config, $tree);
  save_data($config, $tree);

  return $tree;
}

################################################################################
# Load previous metrics from temporal file.
################################################################################
sub load_data {
  my ($config, $tree) = @_;

  my $_f;
  eval {
    open($_f, "<$config->{'tmp_file'}") or die('Cannot open ' . $config->{'tmp_file'});
  };
  if( $@ ) {
    foreach my $iface (keys %{$tree}) {
      $tree->{$iface}{'old'} = {
        'timestamp' => int $tree->{$iface}{'now'}{'timestamp'},
        'inOctets'  => int $tree->{$iface}{'now'}{'inOctets'},
        'outOctets'  => int $tree->{$iface}{'now'}{'outOctets'},
      };
    }
    return;
  }

  # File opened, load previous values.
  while (my $line =<$_f>) {
    $line = trim($line);
    my ($timestamp, $iface, $inOctets, $outOctets) = split /$config->{'tmp_separator'}/, $line;

    next if (!defined($tree->{trim($iface)}));

    $tree->{trim($iface)}{'old'} = {
      'timestamp' => int trim($timestamp),
      'inOctets'  => int trim($inOctets),
      'outOctets'  => int trim($outOctets),
    };
  }

  close($_f);

  foreach my $iface (keys %{$tree}) {
    if (empty($tree->{trim($iface)}{'old'}{'timestamp'})) {
      $tree->{$iface}{'old'} = {
        'timestamp' => int $tree->{$iface}{'now'}{'timestamp'},
        'inOctets'  => int $tree->{$iface}{'now'}{'inOctets'},
        'outOctets'  => int $tree->{$iface}{'now'}{'outOctets'},
      };
    }
  }
}

################################################################################
# Save metrics to temporal file.
################################################################################
sub save_data {
  my ($config, $tree) = @_;

  my $_f;
  eval {
    open($_f, ">$config->{'tmp_file'}") or die('Cannot open ' . $config->{'tmp_file'});
  };
  if( $@ ) {
    logger($config, 'info', "Cannot save stats, please check writting permissions on [" . $config->{'tmp_file'} . "]");
    return;
  }

  # File not available, reset old data.
  my $target_oids = 'x86_indexes';
  $target_oids = 'x64_indexes' if is_enabled($config->{'use_x64'});

  foreach my $iface (keys %{$tree}) {
    # Timestamp.
    print $_f $tree->{$iface}{'now'}{'timestamp'} . $config->{'tmp_separator'};

    # Iface.
    print $_f $iface . $config->{'tmp_separator'};
    
    # InOctets.
    print $_f $tree->{$iface}{'now'}{'inOctets'} . $config->{'tmp_separator'};

    # OutOctets.
    print $_f $tree->{$iface}{'now'}{'outOctets'} . $config->{'tmp_separator'};

    # End.
    print $_f "\n";
  }

  close($_f);

}

################################################################################
# Calculate bandwidth
################################################################################
sub get_bandwidth {
  my ($config, $tree) = @_;

  foreach my $iface (keys %{$tree}) {
    my $ifIndex = $iface;
    if ($ifIndex !~ /^\./) {
      $ifIndex = '.'.$ifIndex;
    }

    my $speed = $tree->{$iface}{'speed'};
    my $input = $tree->{$iface}{'now'}{'inOctets'} - $tree->{$iface}{'old'}{'inOctets'};
    my $output = $tree->{$iface}{'now'}{'outOctets'} - $tree->{$iface}{'old'}{'outOctets'};
    my $delta = $tree->{$iface}{'now'}{'timestamp'} - $tree->{$iface}{'old'}{'timestamp'};
    my $bandwidth = 0;

    $tree->{$iface}->{'delta'} = {
      'inOctets'  => $input,
      'outOctets' => $output,
      'seconds'   => $delta,
    };

    $tree->{$iface}->{'speed'} = $speed;

    if (($speed > 0) && ($delta > 0)) {
      # Information about bandwidth calculation: https://www.cisco.com/c/en/us/support/docs/ip/simple-network-management-protocol-snmp/8141-calculate-bandwidth-snmp.html
      if ($tree->{$iface}{'duplex'} == HALF_DUPLEX
        || $tree->{$iface}{'duplex'} == UNKNOWN_DUPLEX
      ) {
        $bandwidth = (($input + $output) * 8) / ($delta * $speed);
      }
      elsif ($tree->{$iface}{'duplex'} == FULL_DUPLEX) {
        my $input_bandwidth  = ($input * 8) / ($delta * $speed);
        my $output_bandwidth = ($output * 8) / ($delta * $speed);
        $bandwidth = ($input_bandwidth + $output_bandwidth) / 2;
      }
      else {
        no warnings "uninitialized";
        logger($config, 'info', "Failed to calculate bandwidth, unknown duplex mode: [" . $tree->{$iface}{'duplex_mode'} . "]");
      }
    }
    else {
      logger($config, 'info', "Failed to calculate bandwidth, interface [" . $iface . "] speed is 0");
    }

    $tree->{$iface}->{'bandwidth'} = 100 * $bandwidth;   
  }

}



################################################################################
#
# MAIN
#
################################################################################

if ($#ARGV < 0) {
  print $HELP;
  exit 0;
}

# Base config definition
my $_config = {
  'oid_base' => ".1.3.6.1.2.1",
  'as_agent_plugin' => 1,
  'x86_indexes' => {
    '__idx__'   => ".2.2.1.1",
    'duplex'    => ".10.7.2.1.19",
    'inOctets'  => ".2.2.1.16",
    'outOctets' => ".2.2.1.10",
    'ifSpeed'   => ".2.2.1.5",
  },
  'x64_indexes' => {
    # In x64 there is no 'index' branch. Uses latest 'id' in OID as ID.
    '__idx__'   => ".2.2.1.1",
    'duplex'    => ".10.7.2.1.19",
    'inOctets'  => ".31.1.1.1.6",
    'outOctets' => ".31.1.1.1.10",
    'ifSpeed'   => ".2.2.1.5",
  },
};

$_config = read_configuration($_config);

if (check_lib_version($pandora_version) == 0){
  print_stderror($_config, "Incorrect PluginTools library version " . get_lib_version() . " != " . $VERSION . " functionality could be affected.");
}

my $config;

foreach my $pk (keys %{$_config}) {
  my $k = update_config_key($pk);
  if (!empty($k)) {
    $config->{$k} = $_config->{$pk};
  }
  else {
    $config->{$pk} = $_config->{$pk};
  }
}

$config->{'host'} = '127.0.0.1'   if empty($config->{'host'});
$config->{'port'} = '161'         if empty($config->{'port'});
$config->{'tmp_separator'} = ';'  if empty($config->{'tmp_separator'});
$config->{'tmp'}           = (($^O =~ /win/)?$ENV{'TMP'}:'/tmp')  if empty($config->{'tmp'});

if(snmp_walk({
    %{$config},
    'oid' => '.1.3.6.1.2.1.31.1.1.1.6'
  })
) {
  # x64 counters available.
  $config->{'use_x64'} = 1;
} else {
  # x64 counters not available.
  $config->{'use_x64'} = 1;
}

# Create unique name for tmp and log file for host
my $filename = $config->{'tmp'}.'/pandora_bandwith_'.$config->{'host'};

if (!empty($config->{'uniqid'})) {
  $filename = $config->{'tmp'}.'/pandora_bandwith_'.$config->{'uniqid'};
}

# Replace every dot for underscore
$filename =~ tr/./_/;
$config->{'tmp_file'} = $filename.'.idx' if empty($config->{'tmp_file'});
$config->{'log'}      = $filename.'.log' if empty($config->{'log'});

my @int_exc = split /,/, trim($config->{'interface_exceptions'}) if (!empty($config->{'interface_exceptions'}));
if ($#int_exc >= 0) {
  $config->{'interface_exceptions'} = \@int_exc;
}
my @only_int = split /,/, trim($config->{'only_interfaces'}) if (!empty($config->{'only_interfaces'}));
if ($#only_int >= 0) {
  $config->{'only_interfaces'} = \@only_int;
}

logger($config, 'info', "Plugin starts");
if (is_enabled($config->{'debug'})) {
  eval {
    eval "use Data::Dumper;1;";if($@) {}
    logger($config, Dumper($config));
  };
  if($@) {}
}

my $analysis_tree = prepare_tree($config);

if (!empty($analysis_tree->{'error'})) {
  logger($config, 'info', "Failed: " . $analysis_tree->{'error'});
  exit 0;
}
else {
  get_bandwidth($config, $analysis_tree);
}

# Report data
my @modules;
my $bandwidth = 0;
my $i = 0;
foreach my $iface (keys %{$analysis_tree}) {
  # Calculate summary;
  if (is_enabled($analysis_tree->{$iface}{'bandwidth'})) {
    $bandwidth = $analysis_tree->{$iface}{'bandwidth'};
    $i++;
  }

}

if ($i > 0) {
  $bandwidth /= $i;
  print sprintf("%.9f\n", $bandwidth);
}

logger($config, 'info', "Plugin ends");

