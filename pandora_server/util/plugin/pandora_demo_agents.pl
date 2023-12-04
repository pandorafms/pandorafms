#!/usr/bin/perl
################################################################################
# Author:     Enrique Martin Garcia
# Copyright:  2023, PandoraFMS
# Maintainer: Operations department
# Version:    1.0
################################################################################

use strict;
use warnings;

use lib '/usr/lib/perl5';

use POSIX qw(strftime);
use Scalar::Util qw(looks_like_number);
use Digest::MD5 qw(md5_hex);
use PandoraFMS::PluginTools qw(empty trim print_agent);

##
# GLOBALS
##################

my $timestamp = strftime '%Y-%m-%d %H:%M:%S', localtime;
my ($sec,$min,$hour,$mday,$mon,$year)=localtime(time);
$year += 1900;
$mon += 1;

my @sorted_ini;
my @agents_indexes;
my @block_agents_created_count;
my $current_ini = 0;

my $result = 1;

my $errors = '';

##
# FUNCTIONS
##################

sub help() {
  print STDERR "$0 <agents_files_folder_path> <total_agents> [agents_seconds_interval] [traps_ip] [traps_community] [tentacle_ip] [tentacle_port] [tentacle_extra_opts]\n";
}

sub result($) {
  my ($res) = @_;

  if($errors ne '') {
    print STDERR $errors;
  }

  print $res . "\n";
}

sub add_error($) {
  my ($err) = @_;

  $errors .= '[ERROR] ' . $err . "\n";
}

sub error($) {
  my ($msg) = @_;

  print STDERR '[ERROR] ' . $msg . "\n";
  result(0);
  exit;
}

sub alphanumerically {
  my ($num_a, $num_b) = map { /(\d+)[^\d]*$/ ? $1 : 0 } ($a, $b);
  $num_a <=> $num_b;
}

sub parse_ini_file {
  my ($file_path) = @_;

  open my $fh, '<', $file_path or return undef;
  
  my %ini_data;
  my %ini_indexes;
  my $current_section = '';

  while (my $line = <$fh>) {
    chomp $line;

    # Skip comments and empty lines
    next if $line =~ /^\s*#/ || $line =~ /^\s*$/;

    # Match section headers
    if ($line =~ /^\s*\[(\w+)\]\s*$/) {
      $current_section = $1;
      next;
    }

    my $key;
    my $index;
    my $value;

    # Match key-value pairs
    # key=value
    if ($line =~ /^\s*(\w+)\s*=\s*[\"\']?\s*(.+?)\s*[\"\']?\s*$/) {
      $key = $1;
      $value = $2;

      # Store in the hash
      $ini_data{$current_section}{$key} = $value;
    
    # Match key-array values pairs
    # key[]=value
    } elsif ($line =~ /^\s*(\w+)\[\]\s*=\s*[\"\']?\s*(.+?)\s*[\"\']?\s*$/) {
      $key = $1;
      $value = $2;

      # Create HASH if not defined or key is not HASH
      if (!defined($ini_data{$current_section}{$key}) || ref($ini_data{$current_section}{$key}) ne "HASH") {
        $ini_data{$current_section}{$key} = {};
        $ini_indexes{$current_section}{$key} = 0;
      }

      # Get dynamic index
      $index = $ini_indexes{$current_section}{$key};

      # Store in the hash
      $ini_data{$current_section}{$key}{$index} = $value;

      # Set new dynamic index
      while(defined($ini_data{$current_section}{$key}{$ini_indexes{$current_section}{$key}})) {
        $ini_indexes{$current_section}{$key}++;
      }

    # Match indexed key-array values pairs
    # key[index]=value
    } elsif ($line =~ /^\s*(\w+)\[([\w\d]+)\]\s*=\s*[\"\']?\s*(.+?)\s*[\"\']?\s*$/) {
      $key = $1;
      $index = $2;
      $value = $3;

      # Create HASH if not defined or key is not HASH
      if (!defined($ini_data{$current_section}{$key}) || ref($ini_data{$current_section}{$key}) ne "HASH") {
        $ini_data{$current_section}{$key} = {};
        $ini_indexes{$current_section}{$key} = 0;
      }

      # Store in the hash
      $ini_data{$current_section}{$key}{$index} = $value;

      # Set new dynamic index
      if(looks_like_number($index) && $index == $ini_indexes{$current_section}{$key}) {
        $ini_indexes{$current_section}{$key}++;
      }

    # Not a valid line, INI bad format
    } else {
      return undef;
    }
  }

  close $fh;

  # Verify ini content
  if(!defined($ini_data{'agent_data'})) {
    return undef;
  }

  if(!defined($ini_data{'agent_data'}{'agent_name'})) {
    return undef;
  }

  # Initialize agent_data keys
  if(!defined($ini_data{'agent_data'}{'agents_number'}) || !looks_like_number($ini_data{'agent_data'}{'agents_number'})) {
    $ini_data{'agent_data'}{'agents_number'} = 1;
  }

  if(!defined($ini_data{'agent_data'}{'group'})) {
    $ini_data{'agent_data'}{'group'} = '';
  }

  # Initialize modules keys
  if(!defined($ini_data{'modules'})) {
    $ini_data{'modules'} = {};
  }

  if(!defined($ini_data{'modules'}{'name'})) {
    $ini_data{'modules'}{'name'} = {};
  }

  # Initialize inventory keys
  if(!defined($ini_data{'inventory'})) {
    $ini_data{'inventory'} = {};
  }

  if(!defined($ini_data{'inventory'}{'name'})) {
    $ini_data{'inventory'}{'name'} = {};
  }

  if(!defined($ini_data{'inventory_values'})) {
    $ini_data{'inventory_values'} = {};
  }

  # Initialize log modules keys
  if(!defined($ini_data{'log_modules'})) {
    $ini_data{'log_modules'} = {};
  }

  if(!defined($ini_data{'log_modules'}{'source'})) {
    $ini_data{'log_modules'}{'source'} = {};
  }

  if(!defined($ini_data{'log_modules'}{'data'})) {
    $ini_data{'log_modules'}{'data'} = {};
  }

  # Initialize traps keys
  if(!defined($ini_data{'traps'})) {
    $ini_data{'traps'} = {};
  }

  if(!defined($ini_data{'traps'}{'oid'})) {
    $ini_data{'traps'}{'oid'} = {};
  }

  return %ini_data;
}

sub get_bool($) {
  my ($false_chance) = @_;

  $false_chance = 0 if $false_chance < 0;
  $false_chance = 100 if $false_chance > 100;

  return int(rand(100)) + 1 <= $false_chance ? 0 : 1;
}

sub get_value($) {
  my ($value_rule) = @_;

  my $value = 1;

  my @rule_parts = split(/;/, $value_rule);

  if($rule_parts[0] eq 'RANDOM') {
    my $min = 0;
    if(defined($rule_parts[1])) {
      $min = $rule_parts[1];
    }
    my $max = 100;
    if(defined($rule_parts[2])) {
      $max = $rule_parts[2];
    }

    $value = $min + rand($max - $min);

  } elsif($rule_parts[0] eq 'PROC') {
    my $error_percent = 0;
    if(defined($rule_parts[1])) {
      $error_percent = $rule_parts[1];
    }

    $value = get_bool($error_percent);
  }
  
  return int($value);
}

sub daily_match($$$) {
  my ($conf, $m_hour, $m_min) = @_;
  my $match = 0;

  if($hour == $m_hour && $min - ($conf->{'agents_interval'} / 60) < $m_min) {
    $match = 1;
  }

  return $match;
}

sub ip_to_long {
  my $ip = shift;
  my @ip_parts = split(/\./, $ip);
  return ($ip_parts[0] << 24) + ($ip_parts[1] << 16) + ($ip_parts[2] << 8) + $ip_parts[3];
}

sub long_to_ip {
  my $long = shift;
  return join('.', ($long >> 24) & 255, ($long >> 16) & 255, ($long >> 8) & 255, $long & 255);
}

sub get_broadcast_ip_long {
  my ($base_ip, $subnet_mask) = @_;
  my $ip_long = ip_to_long($base_ip);
  my $broadcast_ip_long = ($ip_long | (2**(32 - $subnet_mask) - 1));
  return $broadcast_ip_long;
}

sub get_network_ip_long {
  my ($base_ip, $subnet_mask) = @_;
  return ip_to_long($base_ip);
}

sub next_ip($$) {
  my ($network , $counter) = @_;

  # Get base IP and subnet mask
  my ($base_ip, $subnet_mask) = split('/', $network);
  
  # If subnet mask is 32 there is only 1 IP
  if($subnet_mask == 32){
    return $base_ip;
  }

  # Get broadcast and network IPs long
  my $broadcast_ip = get_broadcast_ip_long($base_ip, $subnet_mask);
  my $network_ip = get_network_ip_long($base_ip, $subnet_mask);

  # Get next IP
  my $next_ip = $network_ip + $counter;

  # Keep rotating until next IP is below broadcast
  while ($next_ip >= $broadcast_ip) {
    $counter = $next_ip - $broadcast_ip + 1;
    $next_ip = $network_ip + $counter;
  }

  return long_to_ip($next_ip);
}

sub transfer_xml($$$) {
  my ($conf, $xml, $name) = @_;
  my $file_name;
  my $short_filename;
  my $file_path;

  if ($xml =~ /\n/ || ! -f $xml) {
    # Not a file, it's content.
    if (! (empty ($name))) {
      $file_name = $name . "." . sprintf("%d",time()) . ".data";
    }
    else {
      # Inherit file name
      ($file_name) = $xml =~ /\s+agent_name='(.*?)'\s+.*$/m;
      if (empty($file_name)){
        ($file_name) = $xml =~ /\s+agent_name="(.*?)"\s+.*$/m;
      }
      if (empty($file_name)){
        $file_name = trim(`hostname`);
      }
      
      # Tentacle server does not allow files with symbols in theirs name.
      $file_name =~ s/[^a-zA-Z0-9_-]//g;
      $short_filename = $file_name;
      $file_name .=  "." . sprintf("%d",time()) . ".data";
    }

    if (empty($file_name)) {
      return (0, "Failed to generate file name");
    }

    $conf->{temp} = $conf->{tmp}             if (empty($conf->{temp}) && defined($conf->{tmp}));
    $conf->{temp} = $conf->{temporal}        if (empty($conf->{temp}) && defined($conf->{temporal}));
    $conf->{temp} = $conf->{__system}->{tmp} if (empty($conf->{temp}) && defined($conf->{__system})) && (ref($conf->{__system}) eq "HASH");
    $conf->{temp} = '/tmp'                   if empty($conf->{temp});

    $file_path = $conf->{temp} . "/" . $file_name;
    
    #Creating XML file in temp directory
    
    while ( -e $file_path ) {
      sleep (1);
      $file_name = $short_filename . "." . sprintf("%d",time()) . ".data";
      $file_path = $conf->{temp} . "/" . $file_name;
    }

    my $r = open (my $FD, ">>", $file_path);

    if (empty($r)) {
      return (0, "Cannot write XML [" . $file_path . "]");
    }
    
    my $bin_opts = ':raw:encoding(UTF8)';
    
    if ($^O eq "Windows") {
      $bin_opts .= ':crlf';
    }
    
    binmode($FD, $bin_opts);

    print $FD $xml;

    close ($FD);

  } else {
    $file_path = $xml;
  }

  # Reassign default values if not present
  $conf->{tentacle_port}   = "41121"     if empty($conf->{tentacle_port});
  $conf->{tentacle_opts}   = ""          if empty($conf->{tentacle_opts});

  #Send using tentacle
  my $msg = `tentacle_client -v -a $conf->{tentacle_ip} -p $conf->{tentacle_port} $conf->{tentacle_opts} "$file_path" 2>&1`;
  my $r = $?;

  unlink ($file_path);

  if ($r != 0) {
    return (0, trim($msg));
  }

  return (1, "");
}

sub send_snmp_trap($$) {
  my ($conf, $trap) = @_;

  # Check trap chance
  if (get_bool($trap->{'chance_percent'}) == 1) {
    my $value = get_value($trap->{'value'});
    my $msg = `snmptrap -v 1 -c $conf->{'traps_community'} $conf->{'traps_ip'} $trap->{'oid'} $trap->{'address'} $trap->{'snmp_type'} 1 0 $trap->{'oid'} s "$value" 2>&1`;
    my $r = $?;

    if ($r != 0) {
      return (0, trim($msg));
    }
  }

  return (1, "");
}

sub generate_agent($) {
  my ($cfg) = @_;

  # Set current ini
  if($block_agents_created_count[$current_ini] >= $sorted_ini[$current_ini]->{'agent_data'}->{'agents_number'}) {
    $block_agents_created_count[$current_ini] = 0;
    $current_ini++;
  }

  if($current_ini >= @sorted_ini) {
    $current_ini = 0;
  }

  # Get agent info
  my $agent;
  $agent->{'agent_name'}  = $sorted_ini[$current_ini]->{'agent_data'}->{'agent_name'}.'-'.$agents_indexes[$current_ini];
  $agent->{'agent_alias'} = $sorted_ini[$current_ini]->{'agent_data'}->{'agent_name'}.'-'.$agents_indexes[$current_ini];
  $agent->{'group'}       = $sorted_ini[$current_ini]->{'agent_data'}->{'group'};
  $agent->{'interval'}    = $cfg->{'agents_interval'};
  $agent->{'timestamp'}   = $timestamp;

  # Get modules info
  my @modules;
  foreach my $k (sort keys %{$sorted_ini[$current_ini]->{'modules'}->{'name'}}) {
    # Set default type if not defined
    if(!defined($sorted_ini[$current_ini]->{'modules'}->{'type'}->{$k})) {
      $sorted_ini[$current_ini]->{'modules'}->{'type'}->{$k} = 'generic_data_string';
    }
    
    # Set default value if not defined
    if(!defined($sorted_ini[$current_ini]->{'modules'}->{'values'}->{$k})) {
      $sorted_ini[$current_ini]->{'modules'}->{'values'}->{$k} = 'RANDOM;0;100';
    }

    push (@modules, {
      'name'  => $sorted_ini[$current_ini]->{'modules'}->{'name'}->{$k},
      'type'  => $sorted_ini[$current_ini]->{'modules'}->{'type'}->{$k},
      'value' => get_value($sorted_ini[$current_ini]->{'modules'}->{'values'}->{$k})
    });
  }

  # Create XML
  my $xml = print_agent({},$agent,\@modules);

  # Append inventory data to XML (only once a day at 00:00)
  if (!empty($sorted_ini[$current_ini]->{'inventory'}->{'name'}) && daily_match($cfg, 0, 0)) {

    # Remove agent_data closing tag
    $xml =~ s/<\/agent_data>//i;

    $xml .= "<inventory>\n";

    # Add inventory for each module
    foreach my $k (sort keys %{$sorted_ini[$current_ini]->{'inventory'}->{'name'}}) {
      # Only if values are defined
      if(defined($sorted_ini[$current_ini]->{'inventory'}->{'values'}->{$k})) {
        # Get inventory module name
        my $inventory_name = $sorted_ini[$current_ini]->{'inventory'}->{'name'}->{$k};

        $xml .= "\t<inventory_module>\n";
        $xml .= "\t\t<name><![CDATA[$inventory_name]]></name>\n";

        # Get inventory values keys
        my @values_keys  = split(/;/, $sorted_ini[$current_ini]->{'inventory'}->{'values'}->{$k});
        
        my %inventory_values;

        # Parse each inventory values key for indexes
        foreach my $key (@values_keys) {
          # Get indexes from each key
          foreach my $i (sort keys %{$sorted_ini[$current_ini]->{'inventory_values'}->{$key}}) {
            $inventory_values{$i} = ();
          }
        }

        # Parse each inventory values key for values
        foreach my $key (@values_keys) {
          # Get values from each key
          foreach my $v (sort keys %inventory_values) {
            if (defined($sorted_ini[$current_ini]->{'inventory_values'}->{$key}->{$v})) {
              push(@{$inventory_values{$v}}, $sorted_ini[$current_ini]->{'inventory_values'}->{$key}->{$v});
            } else {
              push(@{$inventory_values{$v}}, '');
            }
          }
        }

        $xml .= "\t\t<datalist>\n";

        # Get each value string
        foreach my $r (sort keys %inventory_values) {
          my $inv_value = join(';', @{$inventory_values{$r}});
          $xml .= "\t\t\t<data><![CDATA[$inv_value]]></data>\n";
        }

        $xml .= "\t\t</datalist>\n";
        $xml .= "\t</inventory_module>\n\n";
      }
    }

    $xml .= "</inventory>\n";

    # Close agent_data tag again
    $xml .= "</agent_data>\n";
  }

  # Append log module data to XML (only once a day at 00:00)
  if (!empty($sorted_ini[$current_ini]->{'log_modules'}->{'source'}) && !empty($sorted_ini[$current_ini]->{'log_modules'}->{'data'})) {
    
    # Remove agent_data closing tag
    $xml =~ s/<\/agent_data>//i;

    # Add log modules for each source
    foreach my $log_key (sort keys %{$sorted_ini[$current_ini]->{'log_modules'}->{'source'}}) {
      # Only if data is defined
      if(defined($sorted_ini[$current_ini]->{'log_modules'}->{'data'}->{$log_key})) {
        # Add log module 50% of times
        if(get_bool(50)) {
          my $log_source = $sorted_ini[$current_ini]->{'log_modules'}->{'source'}->{$log_key};
          my $log_data = $sorted_ini[$current_ini]->{'log_modules'}->{'data'}->{$log_key};

          $xml .= "<log_module>\n";
          $xml .= "\t<source><![CDATA[$log_source]]></source>\n";
          $xml .= "\t<data><![CDATA[$log_data]]></data>\n";
          $xml .= "</log_module>\n";
        }
      }
    }

    # Close agent_data tag again
    $xml .= "</agent_data>\n";
  }

  # Get file name MD5
  my $file_md5 = md5_hex($agent->{'agent_name'});

  # Send XML
  my ($transfer_res, $transfer_err) = transfer_xml($cfg, $xml, $file_md5);
  if ($transfer_res == 0) {
    add_error("Failed to transfer XML for agent: " . $agent->{'agent_name'} . "\n\t" . $transfer_err);
    $result = 0;
  }

  # Get traps source address
  my $traps_addr = '127.0.0.1';
  if(defined($sorted_ini[$current_ini]->{'agent_data'}->{'address_network'})) {
    $traps_addr = next_ip($sorted_ini[$current_ini]->{'agent_data'}->{'address_network'}, $agents_indexes[$current_ini]);
  }
  
  # Generate SNMP traps
  foreach my $k (sort keys %{$sorted_ini[$current_ini]->{'traps'}->{'oid'}}) {
    # Set default type if not defined
    if(!defined($sorted_ini[$current_ini]->{'traps'}->{'snmp_type'}->{$k})) {
      $sorted_ini[$current_ini]->{'traps'}->{'snmp_type'}->{$k} = '6';
    }
    
    # Set default value if not defined
    if(!defined($sorted_ini[$current_ini]->{'traps'}->{'value'}->{$k})) {
      $sorted_ini[$current_ini]->{'traps'}->{'value'}->{$k} = 'RANDOM;0;100';
    }

    # Set default chance if not defined
    if(!defined($sorted_ini[$current_ini]->{'traps'}->{'chance_percent'}->{$k})) {
      $sorted_ini[$current_ini]->{'traps'}->{'chance_percent'}->{$k} = '5';
    }

    my $trap;
    $trap->{'oid'}            = $sorted_ini[$current_ini]->{'traps'}->{'oid'}->{$k};
    $trap->{'snmp_type'}      = $sorted_ini[$current_ini]->{'traps'}->{'snmp_type'}->{$k};
    $trap->{'value'}          = $sorted_ini[$current_ini]->{'traps'}->{'value'}->{$k};
    $trap->{'chance_percent'} = $sorted_ini[$current_ini]->{'traps'}->{'chance_percent'}->{$k};
    $trap->{'address'}        = $traps_addr;

    my ($trap_res, $trap_err) = send_snmp_trap($cfg, $trap);
    if ($trap_res == 0) {
      add_error("Failed to send SNMP trap for agent: " . $agent->{'agent_name'} . "\n\t" . $trap_err);
      $result = 0;
    }

    undef($trap);
  }

  # Increase agents indexes
  $agents_indexes[$current_ini]++;
}

##
# ARGUMENTS
##################

my $agents_files_path = $ARGV[0];
my $total_agents = $ARGV[1];
my $agents_interval = (defined($ARGV[2]) && $ARGV[2] ne '' ? $ARGV[2] : 300);

my $traps_ip = (defined($ARGV[3]) && $ARGV[3] ne '' ? $ARGV[3] : '127.0.0.1');
my $traps_community = (defined($ARGV[4]) && $ARGV[4] ne '' ? $ARGV[4] : 'public');

my $tentacle_ip = (defined($ARGV[5]) && $ARGV[5] ne '' ? $ARGV[5] : '127.0.0.1');
my $tentacle_port = (defined($ARGV[6]) && $ARGV[6] ne '' ? $ARGV[6] : 41121);

my $tentacle_opts = join(' ', @ARGV[7..$#ARGV]);

# Verify parameters

if(!defined($agents_files_path)) {
  help();
  error("Agents definition folder must be defined");
}

if(!defined($total_agents) || !looks_like_number($total_agents) || $total_agents <= 0) {
  help();
  error("Total number of agents must be defined and a number greater than 0");
}

if(!-d $agents_files_path || !-r $agents_files_path) {
  error("Can't access agents definition folder: " . $agents_files_path);
}

if(!looks_like_number($agents_interval) || $agents_interval <= 0) {
  $agents_interval = 300;
}

if(!looks_like_number($tentacle_port) || $tentacle_port <= 0) {
  $tentacle_port = 41121;
}

##
# MAIN
##################

# Set config
my $config;
$config->{'tentacle_ip'}     = $tentacle_ip;
$config->{'tentacle_port'}   = $tentacle_port;
$config->{'tentacle_opts'}   = $tentacle_opts;
$config->{'agents_interval'} = $agents_interval;
$config->{'traps_ip'}        = $traps_ip;
$config->{'traps_community'} = $traps_community;

# Open the directory
opendir(my $dh, $agents_files_path) or error("Could not open directory '$agents_files_path': $!");

# Read the directory and sort the filenames numerically
my @files = sort alphanumerically map { "$agents_files_path/$_" } grep { -f "$agents_files_path/$_" } readdir($dh);

# Close the directory
closedir($dh);

# Parse each ini file and sort them
foreach my $file (@files) {
  if (-f $file && -r $file) {
    my %ini_data = parse_ini_file($file);
    if (defined(\%ini_data)) {
      push(@sorted_ini, \%ini_data);
      push(@agents_indexes, 1);
      push(@block_agents_created_count, 0);
    }
  }
}

# Error if not agents definitions loaded
if (empty(@sorted_ini)) {
  error("Unable to load agents definitions from folder '$agents_files_path'");
}

# Generate all requested agents
my $generated_agents = 0;
while ($generated_agents < $total_agents) {
  generate_agent($config);
  $block_agents_created_count[$current_ini]++;
  $generated_agents++;
}

# Print result
result($result);