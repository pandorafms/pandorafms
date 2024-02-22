#!/usr/bin/perl
# (c) Pandora FMS 2014-2023 <info@pandorafms.com>
# Module for network topology discovery.

package PandoraFMS::Recon::Base;
use strict;
use warnings;

# Default lib dir for RPM and DEB packages
use NetAddr::IP;
use IO::Socket::INET;
use POSIX qw/ceil/;
use Socket qw/inet_aton/;

BEGIN { push @INC, '/usr/lib/perl5'; }
use PandoraFMS::Tools;
use PandoraFMS::Recon::NmapParser;
use PandoraFMS::Recon::Util;

# Constants.
use constant {
  STEP_SCANNING => 1,
  STEP_CAPABILITIES => 7,
  STEP_AFT => 2,
  STEP_TRACEROUTE => 3,
  STEP_GATEWAY => 4,
  STEP_MONITORING => 5,
  STEP_PROCESSING => 6,
  STEP_STATISTICS => 1,
  STEP_APP_SCAN => 2,
  STEP_CUSTOM_QUERIES => 3,
  DISCOVERY_HOSTDEVICES => 0,
  DISCOVERY_HOSTDEVICES_CUSTOM => 1,
  DISCOVERY_CLOUD_AWS => 2,
  DISCOVERY_APP_VMWARE => 3,
  DISCOVERY_APP_MYSQL => 4,
  DISCOVERY_APP_ORACLE => 5,
  DISCOVERY_CLOUD_AWS_EC2 => 6,
  DISCOVERY_CLOUD_AWS_RDS => 7,
  DISCOVERY_CLOUD_AZURE_COMPUTE => 8,
  DISCOVERY_CLOUD_GCP_COMPUTE_ENGINE => 13,
  DISCOVERY_DEPLOY_AGENTS => 9,
  DISCOVERY_APP_SAP => 10,
  DISCOVERY_APP_DB2 => 11,
  DISCOVERY_APP_MICROSOFT_SQL_SERVER => 12,
  DISCOVERY_REVIEW => 0,
  DISCOVERY_STANDARD => 1,
  DISCOVERY_RESULTS => 2,
  WMI_UNREACHABLE => 1,
  WMI_BAD_PASSWORD => 2,
  WMI_GENERIC_ERROR => 3,
  WMI_OK => 0,
};

# $DEVNULL
my $DEVNULL = ($^O eq 'MSWin32') ? '/Nul' : '/dev/null';

# Some useful OIDs.
our $ATPHYSADDRESS = ".1.3.6.1.2.1.3.1.1.2";
our $DOT1DBASEBRIDGEADDRESS = ".1.3.6.1.2.1.17.1.1.0";
our $DOT1DBASEPORTIFINDEX = ".1.3.6.1.2.1.17.1.4.1.2";
our $DOT1DTPFDBADDRESS = ".1.3.6.1.2.1.17.4.3.1.1";
our $DOT1DTPFDBPORT = ".1.3.6.1.2.1.17.4.3.1.2";
our $IFDESC = ".1.3.6.1.2.1.2.2.1.2";
our $IFHCINOCTECTS = ".1.3.6.1.2.1.31.1.1.1.6";
our $IFHCOUTOCTECTS = ".1.3.6.1.2.1.31.1.1.1.10";
our $IFINDEX = ".1.3.6.1.2.1.2.2.1.1";
our $IFINOCTECTS = ".1.3.6.1.2.1.2.2.1.10";
our $IFOPERSTATUS = ".1.3.6.1.2.1.2.2.1.8";
our $IFOUTOCTECTS = ".1.3.6.1.2.1.2.2.1.16";
our $IFTYPE = ".1.3.6.1.2.1.2.2.1.3";
our $IPENTADDR = ".1.3.6.1.2.1.4.20.1.1";
our $IFNAME = ".1.3.6.1.2.1.31.1.1.1.1";
our $IFPHYSADDRESS = ".1.3.6.1.2.1.2.2.1.6";
our $IPADENTIFINDEX = ".1.3.6.1.2.1.4.20.1.2";
our $IPNETTOMEDIAPHYSADDRESS = ".1.3.6.1.2.1.4.22.1.2";
our $IPROUTEIFINDEX = ".1.3.6.1.2.1.4.21.1.2";
our $IPROUTENEXTHOP = ".1.3.6.1.2.1.4.21.1.7";
our $IPROUTETYPE = ".1.3.6.1.2.1.4.21.1.8";
our $PRTMARKERINDEX = ".1.3.6.1.2.1.43.10.2.1.1";
our $SYSDESCR = ".1.3.6.1.2.1.1.1.0";
our $SYSSERVICES = ".1.3.6.1.2.1.1.7";
our $SYSUPTIME = ".1.3.6.1.2.1.1.3";
our $VTPVLANIFINDEX = ".1.3.6.1.4.1.9.9.46.1.3.1.1.18.1";
our $PEN_OID = ".1.3.6.1.2.1.1.2.0";

our @ISA = ("Exporter");
our %EXPORT_TAGS = ( 'all' => [qw( )] );
our @EXPORT_OK = ( @{ $EXPORT_TAGS{'all'} } );
our @EXPORT = qw(
  $DOT1DBASEBRIDGEADDRESS
  $DOT1DBASEPORTIFINDEX
  $DOT1DTPFDBADDRESS
  $DOT1DTPFDBPORT
  $IFDESC
  $IFHCINOCTECTS
  $IFHCOUTOCTECTS
  $IFINDEX
  $IFINOCTECTS
  $IFOPERSTATUS
  $IFOUTOCTECTS
  $IPADENTIFINDEX
  $IPENTADDR
  $IFNAME
  $IPNETTOMEDIAPHYSADDRESS
  $IFPHYSADDRESS
  $IPADENTIFINDEX
  $IPROUTEIFINDEX
  $IPROUTENEXTHOP
  $IPROUTETYPE
  $PRTMARKERINDEX
  $SYSDESCR
  $SYSSERVICES
  $SYSUPTIME
);

################################################################################
# Create a new ReconTask object.
################################################################################
sub new {
  my $class = shift;

  my $self = {

    # Known aliases (multiple IP addresses for the same host.
    aliases => {},

    # Keep our own ARP cache to connect hosts to switches/routers.
    arp_cache => {},

    # Found children.
    children => {},

    # Working SNMP community for each device.
    community_cache => {},

    # Cache of deviced discovered.
    dicovered_cache => {},

    # Connections between devices.
    connections => {},

    # Devices by type.
    hosts => [],
    routers => [],
    switches => [],

    # Found interfaces.
    ifaces => {},

    # Found parents.
    parents => {},

    # Port counts for each switch.
    ports => {},

    # Route cache.
    routes => [],
    default_gw => undef,

    # SNMP query cache.
    snmp_cache => {},

    # Globally enable/disable SNMP scans.
    snmp_enabled => 1,

    # Globally enable/disable WMI scans.
    wmi_enabled => 0,

    # Globally enable/disable RCMD scans.
    rcmd_enabled => 0,
    rcmd_timeout => 4,
    rcmd_timeout_bin => '/usr/bin/timeout',

    auth_strings_array => [],
    wmi_timeout => 3,
    timeout_cmd => '',

    # Switch to switch connections. Used to properly connect hosts
    # that are connected to a switch wich is in turn connected to another switch,
    # since the hosts will show up in the latter's switch AFT too.
    switch_to_switch => {},

    # Visited devices (initially empty).
    visited_devices => {},

    # Inverse relationship for visited devices (initially empty).
    addresses => {},

    # Per device VLAN cache.
    vlan_cache => {},
    vlan_cache_enabled => 1,     # User configuration. Globally disables the VLAN cache.
    __vlan_cache_enabled__ => 0, # Internal state. Allows us to enable/disable the VLAN cache on a per SNMP query basis.

    # Configuration parameters.
    all_ifaces => 0,
    communities => [],
    icmp_checks => 2,
    icmp_timeout => 2,
    id_os => 0,
    id_network_profile => 0,
    nmap => '/usr/bin/nmap',
    parent_detection => 1,
    parent_recursion => 5,
    os_detection => 0,
    recon_timing_template => 3,
    recon_ports => '',
    resolve_names => 0,
    snmp_auth_user => '',
    snmp_auth_pass => '',
    snmp_auth_method => '',
    snmp_checks => 2,
    snmp_privacy_method => '',
    snmp_privacy_pass => '',
    snmp_security_level => '',
    snmp_timeout => 2,
    snmp_version => 1,
    snmp_skip_non_enabled_ifs => 1,
    subnets => [],
    autoconfiguration_enabled => 0,

    # Store progress summary - Discovery progress view.
    step => 0,
    c_network_name => '',
    c_network_percent => 0.0,
    summary => {
      SNMP => 0,
      WMI => 0,
      discovered => 0,
      alive => 0,
      not_alive => 0
    },
    @_,

  };

  # Perform some sanity checks.
  die("No subnet was specified.") unless defined($self->{'subnets'});

  $self = bless($self, $class);

  # Check SNMP params id SNMP is enabled
  if ($self->{'snmp_enabled'}) {

    # Check SNMP version
    if (   $self->{'snmp_version'} ne '1'
      && $self->{'snmp_version'} ne '2'
      && $self->{'snmp_version'} ne '2c'
      && $self->{'snmp_version'} ne '3') {
      $self->{'snmp_enabled'} = 0;
      $self->call('message', "SNMP version " . $self->{'snmp_version'} . " not supported (only 1, 2, 2c and 3).", 5);
    }

    # Check the version 3 parameters
    if ($self->{'snmp_version'} eq '3') {

      # Fixed some vars
      $self->{'communities'} = [];

      # SNMP v3 checks
      if (  $self->{'snmp_security_level'} ne 'noAuthNoPriv'
        &&$self->{'snmp_security_level'} ne 'authNoPriv'
        &&$self->{'snmp_security_level'} ne 'authPriv') {
        $self->{'snmp_enabled'} = 0;
        $self->call('message', "Invalid SNMP security level " . $self->{'snmp_security_level'} . ".", 5);
      }
      if ($self->{'snmp_privacy_method'} ne 'DES' && $self->{'snmp_privacy_method'} ne 'AES') {
        $self->{'snmp_enabled'} = 0;
        $self->call('message', "Invalid SNMP privacy method " . $self->{'snmp_privacy_method'} . ".", 5);
      }
      if ($self->{'snmp_auth_method'} ne 'MD5' && $self->{'snmp_auth_method'} ne 'SHA') {
        $self->{'snmp_enabled'} = 0;
        $self->call('message', "Invalid SNMP authentication method " . $self->{'snmp_auth_method'} . ".", 5);
      }
    } else {

      # Fixed some vars
      $self->{'snmp_auth_user'} = '';
      $self->{'snmp_auth_pass'} = '';
      $self->{'snmp_auth_method'} = '';
      $self->{'snmp_privacy_method'} = '';
      $self->{'snmp_privacy_pass'} = '';
      $self->{'snmp_security_level'} = '';

      # Disable SNMP scans if no community was given.
      if (ref($self->{'communities'}) ne "ARRAY" || scalar(@{$self->{'communities'}}) == 0) {
        $self->{'snmp_enabled'} = 0;
        $self->call('message', "There is no SNMP community configured.", 5);

      }
    }
  }

  # Prepare auth array.
  # WMI could be launched with '-N' - no pass - argument.
  if ($self->{'wmi_enabled'} == 1){
    if (defined($self->{'auth_strings_str'})) {
      @{$self->{'auth_strings_array'}} = split(',', $self->{'auth_strings_str'});
    }

    # Timeout available only in linux environments.
    if ($^O =~ /lin/i && defined($self->{'plugin_exec'}) && defined($self->{'wmi_timeout'})) {
      $self->{'timeout_cmd'} = $self->{'plugin_exec'}.' '.$self->{'wmi_timeout'}.' ';
    }
  }

  # Remove all snmp related values if disabled
  if (!$self->{'snmp_enabled'}) {
    $self->{'communities'} = [];
    $self->{'snmp_auth_user'} = '';
    $self->{'snmp_auth_pass'} = '';
    $self->{'snmp_auth_method'} = '';
    $self->{'snmp_privacy_method'} = '';
    $self->{'snmp_privacy_pass'} = '';
    $self->{'snmp_security_level'} = '';
    $self->{'snmp_skip_non_enabled_ifs'} = '';
  }

  return $self;
}

################################################################################
# Add an address to a device.
################################################################################
sub add_addresses($$$) {
  my ($self, $device, $ip_address) = @_;

  $self->{'visited_devices'}->{$device}->{'addr'}->{$ip_address} = '';

  # Inverse relationship.
  $self->{'addresses'}{$ip_address} = $device;

  # Update IP references.
  if (ref($self->{'agents_found'}{$device}) eq 'HASH') {
    my @addresses = $self->get_addresses($device);
    $self->{'agents_found'}{$device}{'other_ips'} = \@addresses;
    $self->call('message', 'New IP detected for '.$device.': '.$ip_address, 5);
  }

}

################################################################################
# Get main address from given address (multi addressed devices).
################################################################################
sub get_main_address($$) {
  my ($self, $addr) = @_;

  return $self->{'addresses'}{$addr};
}

################################################################################
# Add a MAC/IP address to the ARP cache.
################################################################################
sub add_mac($$$) {
  my ($self, $mac, $ip_addr) = @_;

  $mac = parse_mac($mac);
  $self->{'arp_cache'}->{$mac} = $ip_addr;
}

################################################################################
# Add an interface/MAC to the interface cache.
################################################################################
sub add_iface($$$) {
  my ($self, $iface, $mac) = @_;

  $iface =~ s/"//g;
  $self->{'ifaces'}->{$mac} = $iface;
}

################################################################################
# Discover connectivity from address forwarding tables.
################################################################################
sub aft_connectivity($$$) {
  my ($self, $switch, $single_port) = @_;
  my (%mac_temp, @aft_temp);

  return unless ($self->is_snmp_discovered($switch));

  $self->enable_vlan_cache();

  # Fill port counts if needed.
  $self->fill_port_counts($switch);

  # Get the address forwarding table (AFT) of each switch.
  my @aft;
  foreach my $mac ($self->snmp_get_value_array($switch, $DOT1DTPFDBADDRESS)) {
    push(@aft, parse_mac($mac));
  }

  # Search for matching entries.
  foreach my $aft_mac (@aft) {

    # Do we know who this is?
    my $host = $self->get_ip_from_mac($aft_mac);
    next unless defined($host) and $host ne '';

    # Get the name of the host interface if available.
    my $host_if_name = $self->get_iface($aft_mac);
    $host_if_name = defined($host_if_name) ? $host_if_name : 'Host Alive';

    # Get the interface associated to the port were we found the MAC address.
    my $switch_if_name = $self->get_if_from_aft($switch, $aft_mac, $single_port);
    next unless defined($switch_if_name) and ($switch_if_name ne '');

    # Do not connect a host to a switch twice using the same interface.
    # The switch is probably connected to another switch.
    next if ($self->is_switch_connected($host, $host_if_name));
    $self->mark_switch_connected($host, $host_if_name);

    # The switch and the host are already connected.
    next if ($self->are_connected($switch, $switch_if_name, $host, $host_if_name));

    # Connect!
    $self->mark_connected($switch, $switch_if_name, $host, $host_if_name);
    $self->call('message', "Switch $switch (if $switch_if_name) is connected to host $host (if $host_if_name).", 5);
  }

  $self->disable_vlan_cache();
}


################################################################################
# Return 1 if the given devices are connected to each other, 0 otherwise.
################################################################################
sub are_connected($$$$$) {
  my ($self, $dev_1, $if_1, $dev_2, $if_2) = @_;

  # Check for aliases!
  $dev_1 = $self->{'aliases'}->{$dev_1} if defined($self->{'aliases'}->{$dev_1});
  $dev_2 = $self->{'aliases'}->{$dev_2} if defined($self->{'aliases'}->{$dev_2});

  # Use Host Alive modules when interfaces are unknown.
  $if_1 = "Host Alive" if $if_1 eq '';
  $if_2 = "Host Alive" if $if_2 eq '';

  if (  defined($self->{'connections'}->{"${dev_1}\t${if_1}\t${dev_2}\t${if_2}"})
    ||defined($self->{'connections'}->{"${dev_2}\t${if_2}\t${dev_1}\t${if_1}"})) {
    return 1;
  }

  return 0;
}

################################################################################
# Initialize tmp pool for addr.
# Already discovered by scan_subnet. Registration only.
################################################################################
sub icmp_discovery($$) {
  my ($self, $addr) = @_;

  # Create an agent for the device and add it to the list of known hosts.
  push(@{$self->{'hosts'}}, $addr);

  # Create an agent for the device and add it to the list of known hosts.
  $self->add_agent($addr);

  $self->add_module($addr,
    {
      'ip_target' => $addr,
      'name' => "Host Alive",
      'description' => '',
      'type' => 'remote_icmp_proc',
      'id_modulo' => 2,
    }
  );

}

################################################################################
# Discover as much information as possible from the given device using SNMP.
################################################################################
sub snmp_discovery($$) {
  my ($self, $device) = @_;

  # Have we already visited this device?
  return if ($self->is_visited($device));

  # Mark the device as visited.
  $self->mark_visited($device);

  # Are SNMP scans enabled?
  if ($self->{'snmp_enabled'} == 1) {

    # Try to find the MAC with an ARP request.
    $self->get_mac_from_ip($device);

    # Check if the device responds to SNMP.
    if ($self->snmp_responds($device)) {
      $self->{'summary'}->{'SNMP'} += 1;

      # Fill the VLAN cache.
      $self->find_vlans($device);

      # Guess the device type.
      $self->guess_device_type($device);

      # Find aliases for the device.
      $self->find_aliases($device);

      # Find interfaces for the device.
      $self->find_ifaces($device);

      # Check remote ARP caches.
      $self->remote_arp($device);

      # Get PEN.
      $self->snmp_pen($device);
    }
  }
}

################################################################################
# Try to call the given function on the given object.
################################################################################
sub call {
  my $self = shift;
  my $func = shift;
  my @params = @_;

  if ($self->can($func)) {
    $self->$func(@params);
  }
}

################################################################################
# Disable the VLAN cache.
################################################################################
sub disable_vlan_cache($$) {
  my ($self, $device) = @_;

  $self->{'__vlan_cache_enabled__'} = 0;
}

################################################################################
# Enable the VLAN cache.
################################################################################
sub enable_vlan_cache($$) {
  my ($self, $device) = @_;

  return if ($self->{'vlan_cache_enabled'} == 0);
  $self->{'__vlan_cache_enabled__'} = 1;
}

################################################################################
# Connect the given hosts to its gateway.
################################################################################
sub gateway_connectivity($$) {
  my ($self, $host) = @_;

  my $gw = $self->get_gateway($host);
  return unless defined($gw);

  # Check for aliases!
  $host = $self->{'aliases'}->{$host} if defined($self->{'aliases'}->{$host});
  $gw = $self->{'aliases'}->{$gw} if defined($self->{'aliases'}->{$gw});

  # Same host, different IP addresses.
  return if ($host eq $gw);

  $self->call('message', "Host $host is reached via gateway $gw.", 5);
  $self->mark_connected($gw, '', $host, '');
}

################################################################################
# Retrieve OS version via SNMP.
################################################################################
sub get_os_version($$) {
  my ($self, $device) = @_;

  # OS detection disabled.
  return '' if ($self->{'os_detection'} == 0);

  # Does the device respond to SNMP?
  return '' unless ($self->is_snmp_discovered($device));

  # Retrieve the system description, which should contain the OS version.
  my $os_version = $self->snmp_get_value($device, "$PandoraFMS::Recon::Base::SYSDESCR");
  
  # Remove leading and trailing quotes.
  $os_version = $1 if ($os_version =~ /^"(.*)"$/);

  return defined($os_version) ? $os_version : '';
}

################################################################################
# Find IP address aliases for the given device.
################################################################################
sub find_aliases($$) {
  my ($self, $device) = @_;

  # Get ARP cache.
  my @ip_addresses = $self->snmp_get_value_array($device, $IPENTADDR);
  foreach my $ip_address (@ip_addresses) {

    # Skip broadcast and localhost addresses.
    next if ($ip_address =~ m/\.255$|\.0$|127\.0\.0\.1$/);

    # Sometimes we find the same IP address we had.
    next if ($ip_address eq $device);

    $self->add_addresses($device, $ip_address);

    # Try to find the MAC with an ARP request.
    $self->get_mac_from_ip($ip_address);

    $self->call('message', "Found address $ip_address for host $device.", 5);

    # Is this address an alias itself?
    $device = $self->{'aliases'}->{$device} if defined($self->{'aliases'}->{$device});
    next if ($ip_address eq $device);

    # Link the two addresses.
    $self->{'aliases'}->{$ip_address} = $device;
  }
}

################################################################################
# Find all the interfaces for the given host.
################################################################################
sub find_ifaces($$) {
  my ($self, $device) = @_;

  # Does it respond to SNMP?
  return unless ($self->is_snmp_discovered($device));

  my @output = $self->snmp_get_value_array($device, $PandoraFMS::Recon::Base::IFINDEX);
  foreach my $if_index (@output) {

    next unless ($if_index =~ /^[0-9]+$/);

    # Ignore virtual interfaces.
    next if ($self->get_if_type($device, $if_index) eq '53');

    # Get the MAC.
    my $mac = $self->get_if_mac($device, $if_index);
    next unless (defined($mac) && $mac ne '');

    # Save it.
    $self->add_mac($mac, $device);

    # Get the name of the network interface.
    my $if_name = $self->snmp_get_value($device, "$PandoraFMS::Recon::Base::IFNAME.$if_index");
    next unless defined($if_name);

    # Save it.
    $self->add_iface($if_name, $mac);

    $self->call('message', "Found interface $if_name MAC $mac for host $device.", 5);
  }
}

################################################################################
# Find the device's VLANs and fill the VLAN cache.
################################################################################
sub find_vlans ($$) {
  my ($self, $device) = @_;
  my %vlan_hash;

  foreach my $vlan ($self->snmp_get_value_array($device, $VTPVLANIFINDEX)) {
    next if $vlan eq '0';
    $vlan_hash{$vlan} = 1;
  }
  my @vlans = keys(%vlan_hash);

  $self->{'vlan_cache'}->{$device} = [];
  push(@{$self->{'vlan_cache'}->{$device}}, @vlans) if (scalar(@vlans) > 0);
}

################################################################################
# Return the addresses of the given device as an array.
################################################################################
sub get_addresses($$) {
  my ($self, $device) = @_;

  if (defined($self->{'visited_devices'}->{$device})) {
    return keys(%{$self->{'visited_devices'}->{$device}->{'addr'}});
  }

  # By default return the given address.
  return ($device);
}

################################################################################
# Return a device structure from an IP address.
################################################################################
sub get_device($$) {
  my ($self, $addr) = @_;

  if (defined($self->{'visited_devices'}->{$addr})) {
    return $self->{'visited_devices'}->{$addr};
  }

  return undef;
}

################################################################################
# Get the SNMP community of the given device. Returns undef if no community was found.
################################################################################
sub get_community($$) {
  my ($self, $device) = @_;

  return '' if ($self->{'snmp_version'} eq "3");

  if (defined($self->{'community_cache'}->{$device})) {
    return $self->{'community_cache'}->{$device};
  }

  return '';
}

################################################################################
# Return the connection hash.
################################################################################
sub get_connections($) {
  my ($self) = @_;

  return $self->{'connections'};
}

################################################################################
# Return the PEN associated to target host.
################################################################################
sub get_pen($$) {
  my ($self, $host) = @_;

  return undef unless ref($self->{'pen'}) eq 'HASH';

  return $self->{'pen'}->{$host};
}

################################################################################
# Return the parent relationship hash.
################################################################################
sub get_parents($) {
  my ($self) = @_;

  return $self->{'parents'};
}

################################################################################
# Get the type of the given device.
################################################################################
sub get_device_type($$) {
  my ($self, $device) = @_;

  if (defined($self->{'visited_devices'}->{$device})) {
    if (defined($self->{'visited_devices'}->{$device}->{'type'})) {
      return $self->{'visited_devices'}->{$device}->{'type'};
    } else {
      $self->{'visited_devices'}->{$device}->{'type'} = 'host';
    }
  }

  # Assume 'host' by default.
  return 'host';
}

################################################################################
# Return all known hosts that are not switches or routers.
################################################################################
sub get_hosts($) {
  my ($self) = @_;

  return $self->{'hosts'};
}

################################################################################
# Add an interface/MAC to the interface cache.
################################################################################
sub get_iface($$) {
  my ($self, $mac) = @_;

  return undef unless defined($self->{'ifaces'}->{$mac});

  return $self->{'ifaces'}->{$mac};
}

################################################################################
# Get an interface name from an AFT entry. Returns undef on error.
################################################################################
sub get_if_from_aft($$$$) {
  my ($self, $switch, $mac, $single_port) = @_;

  # Get the port associated to the MAC.
  my $port = $self->snmp_get_value($switch, "$DOT1DTPFDBPORT." . mac_to_dec($mac));
  return '' unless defined($port);

  # Are we looking for interfaces with a single port entry?
  if ($single_port == 1 &&
      defined($self->{'ports'}->{$switch}) &&
      defined($self->{'ports'}->{$switch}->{$port}) &&
      $self->{'ports'}->{$switch}->{$port} > 1) {
    return '';
  }

  # Are we looking for interfaces with multiple port entries?
  if ($single_port == 0 &&
      defined($self->{'ports'}->{$switch}) &&
      defined($self->{'ports'}->{$switch}->{$port}) &&
      $self->{'ports'}->{$switch}->{$port} <= 1) {
    return '';
  }

  # Get the interface index associated to the port.
  my $if_index = $self->snmp_get_value($switch, "$DOT1DBASEPORTIFINDEX.$port");
  return '' unless defined($if_index);

  # Get the interface name.
  my $if_name = $self->snmp_get_value($switch, "$IFNAME.$if_index");
  return "if$if_index" unless defined($if_name);

  $if_name =~ s/"//g;
  return $if_name;

}

################################################################################
# Get an interface name from an IP address.
################################################################################
sub get_if_from_ip($$$) {
  my ($self, $device, $ip_addr) = @_;

  # Get the port associated to the IP address.
  my $if_index = $self->snmp_get_value($device, "$IPROUTEIFINDEX.$ip_addr");
  return '' unless defined($if_index);

  # Get the name of the interface associated to the port.
  my $if_name = $self->snmp_get_value($device, "$IFNAME.$if_index");
  return '' unless defined($if_name);

  $if_name =~ s/"//g;
  return $if_name;
}

################################################################################
# Get an interface name from a MAC address.
################################################################################
sub get_if_from_mac($$$) {
  my ($self, $device, $mac) = @_;

  # Get the port associated to the IP address.
  my @output = $self->snmp_get($device, $IFPHYSADDRESS);
  foreach my $line (@output) {
    chomp($line);
    next unless $line =~ /^IFPHYSADDRESS.(\S+)\s+=\s+\S+:\s+(.*)$/;
    my ($if_index, $if_mac) = ($1, $2);

    # Make sure the MAC addresses match.
    next unless (mac_matches($mac, $if_mac) == 1);

    # Pupulate the ARP cache.
    $self->add_mac($mac, $device);

    # Get the name of the interface associated to the port.
    my $if_name = $self->snmp_get_value($device, "$IFNAME.$if_index");
    return '' unless defined($if_name);

    $if_name =~ s/"//g;
    return $if_name;
  }

  return '';
}

################################################################################
# Get an interface name from a port number. Returns '' on error.
################################################################################
sub get_if_from_port($$$) {
  my ($self, $switch, $port) = @_;

  # Get the interface index associated to the port.
  my $if_index = $self->snmp_get_value($switch, "$DOT1DBASEPORTIFINDEX.$port");
  return '' unless defined($if_index);

  # Get the interface name.
  my $if_name = $self->snmp_get_value($switch, "$IFNAME.$if_index");
  return "if$if_index" unless defined($if_name);

  $if_name =~ s/"//g;
  return $if_name;
}

################################################################################
# Returns the IP address of the given interface (by index).
################################################################################
sub get_if_ip($$$) {
  my ($self, $device, $if_index) = @_;

  my @output = $self->snmp_get($device, $IPADENTIFINDEX);
  foreach my $line (@output) {
    chomp($line);
    return $1 if ($line =~ m/^$IPADENTIFINDEX.(\S+)\s+=\s+\S+:\s+$if_index$/);
  }

  return '';
}

################################################################################
# Returns the MAC address of the given interface (by index).
################################################################################
sub get_if_mac($$$) {
  my ($self, $device, $if_index) = @_;

  my $mac = $self->snmp_get_value($device, "$IFPHYSADDRESS.$if_index");
  return '' unless defined($mac);

  # Clean-up the MAC address.
  $mac = parse_mac($mac);

  return $mac;
}

################################################################################
# Returns the type of the given interface (by index).
################################################################################
sub get_if_type($$$) {
  my ($self, $device, $if_index) = @_;

  my $type = $self->snmp_get_value($device, "$IFTYPE.$if_index");
  return '' unless defined($type);

  return $type;
}

################################################################################
# Get an IP address from the ARP cache given the MAC address.
################################################################################
sub get_ip_from_mac($$) {
  my ($self, $mac_addr) = @_;

  if (defined($self->{'arp_cache'}->{$mac_addr})) {
    return $self->{'arp_cache'}->{$mac_addr};
  }

  return undef;
}

################################################################################
# Attemtps to find
################################################################################
sub get_mac_from_ip($$) {
  my ($self, $host) = @_;
  my $mac = undef;

  eval {
    $mac = `arping -c 1 $host 2>$DEVNULL`;
    $mac = undef unless ($? == 0);
  };

  return unless defined($mac);

  ($mac) = $mac =~ /\[(.*?)\]/ if defined($mac);

  # Clean-up the MAC address.
  chomp($mac);
  $mac = parse_mac($mac);
  $self->add_mac($mac, $host);

  $self->call('message', "Found MAC $mac for host $host in the local ARP cache.", 5);
}

################################################################################
# Find out the number of entries for each port on the given switch.
################################################################################
sub fill_port_counts($$) {
  my ($self, $switch) = @_;

  return if (defined($self->{'ports'}->{$switch}));

  # List all the ports.
  foreach my $port ($self->snmp_get_value_array($switch, $DOT1DTPFDBPORT)) {
    if (!defined($self->{'ports'}->{$switch}->{$port})) {
      $self->{'ports'}->{$switch}->{$port} = 1;
    } else {
      $self->{'ports'}->{$switch}->{$port} += 1;
    }
  }
}

################################################################################
# Get a port number from an AFT entry. Returns undef on error.
################################################################################
sub get_port_from_aft($$$) {
  my ($self, $switch, $mac) = @_;

  # Get the port associated to the MAC.
  my $port = $self->snmp_get_value($switch, "$DOT1DTPFDBPORT." . mac_to_dec($mac));
  return '' unless defined($port);

  return $port;
}

################################################################################
# Fill the route cache.
################################################################################
sub get_routes($) {
  my ($self) = @_;

  # Empty the current route cache.
  $self->{'routes'} = [];

  # Parse route's output.
  my @output = `route -n 2>$DEVNULL`;
  foreach my $line (@output) {
    chomp($line);
    if ($line =~ /^0\.0\.0\.0\s+(\d+\.\d+\.\d+\.\d+).*/) {
      $self->{'default_gw'} = $1;
    } elsif ($line =~ /^(\d+\.\d+\.\d+\.\d+)\s+(\d+\.\d+\.\d+\.\d+)\s+(\d+\.\d+\.\d+\.\d+).*/) {
      push(@{$self->{'routes'}}, { dest => $1, gw => $2, mask => $3 });
    }
  }

  # Replace 0.0.0.0 with the default gateway's IP.
  return unless defined($self->{'default_gw'});
  foreach my $route (@{$self->{'routes'}}) {
    $route->{gw} = $self->{'default_gw'} if ($route->{'gw'} eq '0.0.0.0');
  }
}

################################################################################
# Get the gateway to reach the given host.
################################################################################
sub get_gateway($) {
  my ($self, $host) = @_;

  # Look for a specific route to the given host.
  foreach my $route (@{$self->{'routes'}}) {
    if (subnet_matches($host, $route->{'dest'}, $route->{'mask'})) {
      return $route->{'gw'};
    }
  }

  # Return the default gateway.
  return $self->{'default_gw'} if defined($self->{'default_gw'});

  # Ops!
  return undef;
}

################################################################################
# Return a pointer to an array containing configured subnets.
################################################################################
sub get_subnets($) {
  my ($self) = @_;

  return $self->{'subnets'};
}

################################################################################
# Get an array of all the visited devices.
# NOTE: This functions returns the whole device structures, not just address
# like get_hosts, get_switches, get_routers and get_all_devices.
################################################################################
sub get_visited_devices($) {
  my ($self) = @_;

  return $self->{'visited_devices'};
}

################################################################################
# Returns an array of found VLAN IDs.
################################################################################
sub get_vlans($$) {
  my ($self, $device) = @_;

  # Disabled in verison 3
  return () if ($self->{'snmp_version'} eq "3");

  # Is the VLAN cache disabled?
  return () unless ($self->{'__vlan_cache_enabled__'} == 1);

  return () unless defined($self->{'vlan_cache'}->{$device});

  return @{$self->{'vlan_cache'}->{$device}};
}

################################################################################
# Guess the type of the given device.
################################################################################
sub guess_device_type($$) {
  my ($self, $device) = @_;

  # Get the value of sysServices.
  my $services = $self->snmp_get_value($device, "$SYSSERVICES.0");
  return unless defined($services);

  # Check the individual bits.
  my @service_bits = split('', unpack('b8', pack('C', $services)));

  # Check for layer 2 connectivity support.
  my $bridge_mib = $self->snmp_get_value($device, $DOT1DBASEBRIDGEADDRESS);

  # L2?
  my $device_type;
  if ($service_bits[1] == 1) {

    # L3?
    if ($service_bits[2] == 1) {

      # Bridge MIB?
      if (defined($bridge_mib)) {
        $device_type = 'switch';
      } else {

        # L7?
        if ($service_bits[6] == 1) {
          $device_type = 'host';
        } else {
          $device_type = 'router';
        }
      }
    }else {

      # Bridge MIB?
      if (defined($bridge_mib)) {
        $device_type = 'switch';
      } else {
        $device_type = 'host';
      }
    }
  }else {

    # L3?
    if ($service_bits[2] == 1) {

      # L4?
      if ($service_bits[3] == 1) {
        $device_type = 'switch';
      } else {

        # L7?
        if ($service_bits[6] == 1) {
          $device_type = 'host';
        } else {
          $device_type = 'router';
        }
      }
    }else {

      # Printer MIB?
      my $printer_mib = $self->snmp_get_value($device, $PRTMARKERINDEX);
      if (defined($printer_mib)) {
        $device_type = 'printer';
      } else {
        $device_type = 'host';
      }
    }
  }

  # Set the type of the device.
  $self->set_device_type($device, $device_type);
}

################################################################################
# Return 1 if the given device has children.
################################################################################
sub has_children($$) {
  my ($self, $device) = @_;

  # Check for aliases!
  $device = $self->{'aliases'}->{$device} if defined($self->{'aliases'}->{$device});

  return 1 if (defined($self->{'children'}->{$device}));

  return 0;
}

################################################################################
# Return 1 if the given device has a parent.
################################################################################
sub has_parent($$) {
  my ($self, $device) = @_;

  # Check for aliases!
  $device = $self->{'aliases'}->{$device} if defined($self->{'aliases'}->{$device});

  return 1 if (defined($self->{'parents'}->{$device}));

  return 0;
}

################################################################################
# Returns 1 if the device belongs to one of the scanned subnets.
################################################################################
sub in_subnet($$) {
  my ($self, $device) = @_;
  $device = ip_to_long($device);

  # No subnets specified.
  return 1 if (scalar(@{$self->{'subnets'}}) <= 0);

  foreach my $subnet (@{$self->{'subnets'}}) {
    if (subnet_matches($device, $subnet)) {
      return 1;
    }
  }

  return 0;
}

################################################################################
# Check for switches that are connected to other switches/routers and show
# up in a switch/router's port.
################################################################################
sub is_switch_connected($$$) {
  my ($self, $device, $iface) = @_;

  # Check for aliases!
  $device = $self->{'aliases'}->{$device} if defined($self->{'aliases'}->{$device});

  return 1 if defined($self->{'switch_to_switch'}->{"${device}\t${iface}"});

  return 0;
}

################################################################################
# Returns 1 if the given device has already been visited, 0 otherwise.
################################################################################
sub is_visited($$) {
  my ($self, $device) = @_;

  # Check for aliases!
  $device = $self->{'aliases'}->{$device} if defined($self->{'aliases'}->{$device});

  if (defined($self->{'visited_devices'}->{$device})) {
    return 1;
  }

  return 0;
}

################################################################################
# Returns 1 if the given device has responded successfully to a snmp request
# Returns 0 otherwise.
################################################################################
sub is_snmp_discovered($$) {
  my ($self, $device) = @_;

  # Check if device is into discovered cache
  return (defined($self->{'discovered_cache'}->{$device})) ? 1 : 0;
}

################################################################################
# Mark the given devices as connected to each other on the given interfaces.
################################################################################
sub mark_connected($$;$$$) {
  my ($self, $parent, $parent_if, $child, $child_if) = @_;

  # Check for aliases!
  $parent = $self->{'aliases'}->{$parent} if defined($self->{'aliases'}->{$parent});
  $child = $self->{'aliases'}->{$child} if defined($self->{'aliases'}->{$child});

  # Use ping modules when interfaces are unknown.
  $parent_if = "Host Alive" if $parent_if eq '';
  $child_if = "Host Alive" if $child_if eq '';

  # Do not connect devices using ping modules. A parent-child relationship is enough.
  if ($parent_if ne "Host Alive" || $child_if ne "Host Alive") {
    $self->{'connections'}->{"${parent}\t${parent_if}\t${child}\t${child_if}"} = 1;
    $self->call('connect_agents', $parent, $parent_if, $child, $child_if);
  }

  # Prevent parent-child loops.
  if (!defined($self->{'parents'}->{$parent})
    ||$self->{'parents'}->{$parent} ne $child) {

    # A parent-child relationship is always created to help complete the map with
    # layer 3 information.
    $self->{'parents'}->{$child} = $parent;
    $self->{'children'}->{$parent} = $child;
    $self->call('set_parent', $child, $parent);
  }
}

################################################################################
# Mark the given switch as having a connection on the given interface.
################################################################################
sub mark_switch_connected($$$) {
  my ($self, $device, $iface) = @_;

  # Check for aliases!
  $device = $self->{'aliases'}->{$device} if defined($self->{'aliases'}->{$device});
  $self->{'switch_to_switch'}->{"${device}\t${iface}"} = 1;
}

################################################################################
# Mark the given device as visited.
################################################################################
sub mark_visited($$) {
  my ($self, $device) = @_;

  $self->{'visited_devices'}->{$device} = {
    'addr' => { $device => '' },
    'type' => 'host'
  };
}

################################################################################
# Mark the given device as snmp discovered.
################################################################################
sub mark_discovered($$) {
  my ($self, $device) = @_;

  $self->{'discovered_cache'}->{$device} = 1;
}

################################################################################
# Validate the configuration for the given device.
# Returns 1 if successfull snmp contact, 0 otherwise.
# Updates the SNMP community cache on v1, v2 and v2c.
################################################################################
sub snmp_responds($$) {
  my ($self, $device) = @_;

  return 1 if($self->is_snmp_discovered($device));

  return ($self->{'snmp_version'} eq "3")
    ? $self->snmp_responds_v3($device)
    : $self->snmp_responds_v122c($device);
}

################################################################################
# Looks for a working SNMP community for the given device. Returns 1 if one is
# found, 0 otherwise. Updates the SNMP community cache.
################################################################################
sub snmp_responds_v122c($$) {
  my ($self, $device) = @_;

  foreach my $community (@{$self->{'communities'}}) {

    # Clean blanks.
    $community =~ s/\s+//g;

    my $command = $self->snmp_get_command($device, ".0", $community);
    `$command`;
    if ($? == 0) {
      $self->set_community($device, $community);
      $self->mark_discovered($device);
      return 1;
    }
  }

  return 0;
}


################################################################################
# Validate the SNMP v3 configuration for a device.
# Returns 1 if successfull snmp contact, 0 otherwise.
################################################################################
sub snmp_responds_v3($$) {
  my ($self, $device) = @_;

  $self->snmp3_credentials_calculation($device);

  if ($self->snmp3_credentials_calculation($device)) {
    $self->mark_discovered($device);
    return 1;
  }

  return 0;
}

################################################################################
# Get SNMP3 credentials info in HASH
################################################################################
sub snmp3_credentials {
  my ($self, $key) = @_;

  my $cred = $self->call('get_credentials', $key, 'SNMP');
  return undef if !defined($cred);
  return undef if ref($cred) ne 'HASH';

  my $extra1 = {};
  eval {
    local $SIG{__DIE__};
    $extra1 = p_decode_json($self->{'pa_config'}, $cred->{'extra_1'});
  };
  if ($@) {
    $self->call('message', "[".$key."] Credentials ERROR JSON: $@", 10);
    return undef;
  }

  return undef if $extra1->{'version'} ne '3';

  return {
    'snmp_security_level' => $extra1->{'securityLevelV3'},
    'snmp_privacy_method' => $extra1->{'privacyMethodV3'},
    'snmp_privacy_pass' => $extra1->{'privacyPassV3'},
    'snmp_auth_method' => $extra1->{'authMethodV3'},
    'snmp_auth_user' => $extra1->{'authUserV3'},
    'snmp_auth_pass' => $extra1->{'authPassV3'},
    'community' => $extra1->{'community'}
  };
}

################################################################################
# Calculate WMI credentials for target, 1 if calculated, undef if cannot
# connect to target. Credentials could be empty (-N)
################################################################################
sub snmp3_credentials_calculation {
  my ($self, $target) = @_;

  # Test all credentials selected.
  foreach my $key_index (@{$self->{'auth_strings_array'}}) {
    my $cred = snmp3_credentials($key_index);
    next if !defined($cred);
    next if ref($cred) ne 'HASH';

    my $auth = '';
    if ($cred->{'community'}) { # Context
      $auth .= " -N \'$cred->{'community'}\' ";
    }
    $auth .= " -l$cred->{'snmp_security_level'} ";
    if ($cred->{'snmp_security_level'} ne "noAuthNoPriv") {
      $auth .= " -u$cred->{'snmp_auth_user'} -a $cred->{'snmp_auth_method'} -A \'$cred->{'snmp_auth_pass'}\' ";
    }
    if ($cred->{'snmp_security_level'} eq "authPriv") {
      $auth .= " -x$cred->{'snmp_privacy_method'} -X \'$cred->{'snmp_privacy_pass'}\' ";
    }

    $self->{'snmp3_auth'}{$target} = $auth;
    $self->{'snmp3_auth_key'}{$target} = $key_index;

    my $command = $self->snmp_get_command($target, ".0");
    `$command`;

    if ($? == 0) {
      return 1;
    }
  }

  delete($self->{'snmp3_auth'}{$target});
  delete($self->{'snmp3_auth_key'}{$target});

  return 0;
}

################################################################################
# Parse the local ARP cache.
################################################################################
sub local_arp($) {
  my ($self) = @_;

  my @output = `arp -an 2>$DEVNULL`;
  foreach my $line (@output) {
    next unless ($line =~ m/\((\S+)\) at ([0-9a-f]+:[0-9a-f]+:[0-9a-f]+:[0-9a-f]+:[0-9a-f]+:[0-9a-f]+)/);
    $self->add_mac(parse_mac($2), $1);
  }
}

################################################################################
# Parse remote SNMP ARP caches.
################################################################################
sub remote_arp($$) {
  my ($self, $device) = @_;

  # Try to learn more MAC addresses from the device's ARP cache.
  my @output = $self->snmp_get($device, $IPNETTOMEDIAPHYSADDRESS);
  foreach my $line (@output) {
    next unless ($line =~ /^$IPNETTOMEDIAPHYSADDRESS\.\d+\.(\S+)\s+=\s+\S+:\s+(.*)$/);
    my ($ip_addr, $mac_addr) = ($1, $2);

    # Skip broadcast, net and local addresses.
    next if ($ip_addr =~ m/\.255$|\.0$|127\.0\.0\.1$/);

    $mac_addr = parse_mac($mac_addr);
    $self->add_mac($mac_addr, $ip_addr);
    $self->call('message', "Found MAC $mac_addr for host $ip_addr in the ARP cache of host $device.", 5);
  }

  # Look in atPhysAddress for MAC addresses too.
  @output = $self->snmp_get($device, $ATPHYSADDRESS);
  foreach my $line (@output) {
    next unless ($line =~ m/^$ATPHYSADDRESS\.\d+\.\d+\.(\S+)\s+=\s+\S+:\s+(.*)$/);
    my ($ip_addr, $mac_addr) = ($1, $2);

    # Skip broadcast, net and local addresses.
    next if ($ip_addr =~ m/\.255$|\.0$|127\.0\.0\.1$/);

    $mac_addr = parse_mac($mac_addr);
    $self->add_mac($mac_addr, $ip_addr);
    $self->call('message', "Found MAC $mac_addr for host $ip_addr in the ARP cache (atPhysAddress) of host $device.", 5);
  }
}

################################################################################
# Add agent to pool (will be registered at the end of the scan).
################################################################################
sub prepare_agent($$) {
  my ($self, $addr) = @_;

  # Avoid multi-ip agent. No reference, is first encounter.
  my $main_address = $self->get_main_address($addr);
  return unless is_empty($main_address);

  # Resolve hostnames.
  my $host_name = (($self->{'resolve_names'} == 1) ? gethostbyaddr(inet_aton($addr), AF_INET) : $addr);

  # Fallback to device IP if host name could not be resolved.
  $host_name = $addr if (!defined($host_name) || $host_name eq '');
  
  $self->{'agents_found'} = {} if ref($self->{'agents_found'}) ne 'HASH';

  # Already initialized.
  return if ref($self->{'agents_found'}->{$addr}) eq 'HASH';

  my @addresses = $self->get_addresses($addr);
  $self->{'agents_found'}->{$addr} = {
    'agent' => {
      'nombre' => $host_name,
      'direccion' => $addr,
      'alias' => $host_name,
    },
    'other_ips' => \@addresses,
    'pen' => $self->{'pen'}{$addr},
    'modules' => [],
  };
}

################################################################################
# Add agent to pool (will be registered at the end of the scan).
################################################################################
sub add_agent($$) {
  my ($self, $addr) = @_;

  # Avoid create empty agents.
  return if is_empty($addr);

  $self->prepare_agent($addr);
}

################################################################################
# Add module to agent (tmp pool) (will be registered at the end of the scan).
################################################################################
sub add_module($$$) {
  my ($self, $agent, $data) = @_;

  $self->prepare_agent($agent);

  $self->{'agents_found'}->{$agent}->{'modules'} = {}
    unless ref($self->{'agents_found'}->{$agent}->{'modules'}) eq 'HASH';

  # Test module. Is it well defined?
  return unless ref($data) eq 'HASH' && defined($data->{'name'})
    && $data->{'name'} ne '';

  # Test module. Is it success? Some components have MIB name instead OID.
  $self->{'translate_snmp'} = 1;
  my $rs = $self->call('test_module', $agent, $data);
  $self->{'translate_snmp'} = 0;

  return unless is_enabled($rs);

  $self->{'agents_found'}->{$agent}->{'modules'}{$data->{'name'}} = $data;
  
}

################################################################################
# Test target address (methods).
################################################################################
sub test_capabilities($$) {
  my ($self, $addr) = @_;

  $self->icmp_discovery($addr);

  if (is_enabled($self->{'snmp_enabled'})) {
    # SNMP discovery.
    $self->snmp_discovery($addr);
  }

  # WMI discovery.
  if (is_enabled($self->{'wmi_enabled'})) {
    # Add wmi scan if enabled.
    $self->wmi_discovery($addr);
  }

  # RCMD discovery.
  if (is_enabled($self->{'rcmd_enabled'})) {
    # Add wmi scan if enabled.
    $self->rcmd_discovery($addr);
  }
}

################################################################################
# Scan the given subnet.
################################################################################
sub scan_subnet($) {
  my ($self) = @_;
  my $progress = 1;

  my @subnets = @{$self->get_subnets()};
  foreach my $subnet (@subnets) {
    $self->{'c_network_percent'} = 0;
    $self->{'c_network_name'} = $subnet;
    $self->call('update_progress', ceil($progress));

    # Clean blanks.
    $subnet =~ s/\s+//g;

    my $net_addr = new NetAddr::IP($subnet);
    if (!defined($net_addr)) {
      $self->call('message', "Invalid network: $subnet", 3);
      next;
    }

    # Save the network and broadcast addresses.
    my $network = $net_addr->network();
    my $broadcast = $net_addr->broadcast();

    my @hosts = map { (split('/', $_))[0] } $net_addr->hostenum;
    my $total_hosts = scalar(@hosts);
    my %hosts_alive = ();

    # By default 200, (20 * 10)
    my $host_block_size = $self->{'block_size'};

    $host_block_size = 50 unless defined($self->{'block_size'});

    # The first 50% of the recon task approx.
    my $step = 25.0 / scalar(@subnets) / (($total_hosts / $host_block_size)+1);
    my $subnet_step = 50.0 / (($total_hosts / $host_block_size)+1);

    for (my $block_index=0;
      $block_index < $total_hosts;
      $block_index += $host_block_size
    ) {
      # Update the recon task
      # Increase self summary.alive hosts.
      $self->call('message', "Searching for hosts (".$block_index." / ".$total_hosts.")", 5);
      my $to = $host_block_size + $block_index;
      $to = $total_hosts if $to >= $total_hosts;

      my $c_block_size = $to - $block_index;
      my @block = pandora_block_ping(
        {
          'fping' => $self->{'fping'},
          'networktimeout' => 0.5 # use fping defaults
        },
        @hosts[$block_index .. $to - 1]
      );

      # check alive hosts in current block
      %hosts_alive = (
        %hosts_alive,
        map {chomp; $_ => 1} @block
      );

      $self->{'summary'}->{'not_alive'} += $c_block_size - (scalar @block);
      $self->{'summary'}->{'alive'} += scalar @block;

      # Update progress.
      $progress += $step;
      $self->{'c_network_percent'} += $subnet_step;

      # Populate.
      $self->call('update_progress', ceil($progress));
    }

    # Update progress.
    $self->call('message', "Searching for hosts (".$total_hosts." / ".$total_hosts.")", 5);
    $progress = ceil($progress);
    $self->{'c_network_percent'} = 50;

    # Populate.
    $self->call('update_progress', ceil($progress));

    $total_hosts = scalar keys %hosts_alive;
    if ($total_hosts == 0) {
      # Populate.
      $self->{'c_network_percent'} += 50;
      $self->call('update_progress', ceil($progress)+25);
      next;
    }
    $step = 25.0 / scalar(@subnets) / $total_hosts;
    $subnet_step = 50.0 / $total_hosts;

    $self->{'step'} = STEP_CAPABILITIES;
    foreach my $addr (keys %hosts_alive) {
      # Increase self summary.alive hosts.
      $self->call('message', "Scanning host: $addr", 5);
      $self->{'c_network_name'} = $addr;

      # Update progress.
      $progress += $step;
      $self->{'c_network_percent'} += $subnet_step;

      # Populate.
      $self->call('update_progress', ceil($progress));

      # Filter by port (if enabled).
      if (!is_empty($self->{'recon_ports'})) {
        next unless $self->call("tcp_scan", $addr) > 0;
      }

      # Enable/ disable capabilities.
      $self->test_capabilities($addr);
    }
  }
}

################################################################################
# Perform a Cloud scan
################################################################################
sub cloud_scan($) {
  my $self = shift;
  my ($progress, $step);

  my $type = '';

  if ( $self->{'task_data'}->{'type'} == DISCOVERY_CLOUD_AWS_EC2
    || $self->{'task_data'}->{'type'} == DISCOVERY_CLOUD_AWS_RDS) {
    $type = 'Aws';
  } else {

    # Unrecognized task type.
    $self->call('message', 'Unrecognized task type', 1);
    $self->call('update_progress', -1);
    return;
  }

  # Initialize cloud object.
  my $cloudObj = PandoraFMS::Recon::Util::enterprise_new(
    'PandoraFMS::Recon::Cloud::'.$type,
    [
      task_data => $self->{'task_data'},
      aws_access_key_id => $self->{'aws_access_key_id'},
      aws_secret_access_key => $self->{'aws_secret_access_key'},
      cloud_util_path => $self->{'cloud_util_path'},
      creds_file => $self->{'creds_file'},
      parent => $self
    ]

  );

  if (!$cloudObj) {

    # Failed to initialize, check Cloud credentials or anything.
    $self->call('message', 'Unable to initialize PandoraFMS::Recon::Cloud::'.$type, 3);
  } else {

    # Let Cloud object manage scan.
    $cloudObj->scan();
  }

  # Update progress.
  # Done!
  $self->{'step'} = '';
  $self->call('update_progress', -1);
}


################################################################################
# Performs a database scan.
################################################################################
sub database_scan($$$) {
  my ($self, $type, $obj, $global_percent, $targets) = @_;

  my @data;
  my @modules;

  my $dbObjCfg = $obj->get_config();

  $self->{'summary'}->{'discovered'} += 1;
  $self->{'summary'}->{'alive'} += 1;

  my $name = $type . ' connection';
  if (defined $obj->{'prefix_module_name'} && $obj->{'prefix_module_name'} ne '') {
    $name = $obj->{'prefix_module_name'} . $type . ' connection';
  }

  push @modules,
    {
    name => $name,
    type => 'generic_proc',
    data => 1,
    description => $type . ' availability'
    };

  # Analyze.
  $self->{'step'} = STEP_STATISTICS;
  $self->{'c_network_percent'} = 30;
  $self->call('update_progress', $global_percent + (30 / (scalar @$targets)));
  $self->{'c_network_name'} = $obj->get_host();

  # Retrieve connection statistics.
  # Retrieve uptime statistics
  # Retrieve query stats
  # Retrieve connections
  # Retrieve innodb
  # Retrieve cache
  $self->{'c_network_percent'} = 50;
  $self->call('update_progress', $global_percent + (50 / (scalar @$targets)));
  push @modules, $obj->get_statistics();

  # Custom queries.
  $self->{'step'} = STEP_CUSTOM_QUERIES;
  $self->{'c_network_percent'} = 80;
  $self->call('update_progress', $global_percent + (80 / (scalar @$targets)));
  push @modules, $obj->execute_custom_queries();

  if (defined($dbObjCfg->{'scan_databases'})
    && "$dbObjCfg->{'scan_databases'}" eq "1") {

    # Skip database scan in Oracle tasks
    next if defined($self->{'type'}) && $self->{'type'} == DISCOVERY_APP_ORACLE;

    # Skip database scan in DB2 tasks
    next if defined($self->{'type'}) && $self->{'type'} == DISCOVERY_APP_DB2;

    my $__data = $obj->scan_databases();

    if (ref($__data) eq "ARRAY") {
      if (defined($dbObjCfg->{'agent_per_database'})
        && $dbObjCfg->{'agent_per_database'} == 1) {

        # Agent per database detected.
        push @data, @{$__data};

      } else {

        # Merge modules into engine agent.
        my @_modules = map {
          map { $_ }
            @{$_->{'module_data'}}
        } @{$__data};

        push @modules, @_modules;
      }
    }
  }

  return {
    'modules' => \@modules,
    'data' => \@data
  };
}


################################################################################
# Perform an Application scan.
################################################################################
sub app_scan($) {
  my ($self) = @_;
  my ($progress, $step);

  my $type = '';
  my $db_scan = 0;

  # APP object initialization.
  if ($self->{'task_data'}->{'type'} == DISCOVERY_APP_MYSQL) {
    $type = 'MySQL';
  } elsif ($self->{'task_data'}->{'type'} == DISCOVERY_APP_ORACLE) {
    $type = 'Oracle';
  } elsif ($self->{'task_data'}->{'type'} == DISCOVERY_APP_DB2) {
    $type = 'DB2';
  } elsif ($self->{'task_data'}->{'type'} == DISCOVERY_APP_MICROSOFT_SQL_SERVER) {
    $type = 'MSSQL';
  } elsif ($self->{'task_data'}->{'type'} == DISCOVERY_APP_SAP) {
    $type = 'SAP';
  } else {
    # Unrecognized task type.
    $self->call('message', 'Unrecognized task type', 1);
    $self->call('update_progress', -1);
    return;
  }

  my @targets = split /,/, $self->{'task_data'}->{'subnet'};

  my $global_step = 100 / (scalar @targets);
  my $global_percent = 0;
  my $i = 0;
  foreach my $target (@targets) {
    if (   !defined($target)
      || $target eq ''
      || $target =~ /^#/) {
      # Ignore empty target or commented one.
      next;
    }

    my @data;
    my @modules;

    $self->{'step'} = STEP_APP_SCAN;
    $self->{'c_network_name'} = $target;
    $self->{'c_network_percent'} = 0;

    # Send message
    $self->call('message', 'Checking target ' . $target, 10);

    # Force target acquirement.
    $self->{'task_data'}->{'dbhost'} = $target;
    $self->{'task_data'}->{'target_index'} = $i++;

    # Update progress
    $self->{'c_network_percent'} = 10;
    $self->call('update_progress', $global_percent + (10 / (scalar @targets)));

    # Connect to target.
    my $obj = PandoraFMS::Recon::Util::enterprise_new(
      'PandoraFMS::Recon::Applications::'.$type,
      {
        %{$self->{'task_data'}},
        'target' => $target,
        'pa_config' => $self->{'pa_config'},
        'parent' => $self
      },
    );

    if (defined($obj)) {

      # Verify if object is connected. If cannot connect to current target
      # return with module.
      if (!$obj->is_connected()) {
        $self->call('message', 'Cannot connect to target ' . $target, 3);
        $global_percent += $global_step;
        $self->{'c_network_percent'} = 90;

        # Update progress
        $self->call('update_progress', $global_percent + (90 / (scalar @targets)));
        $self->{'summary'}->{'not_alive'} += 1;

        my $name = $type . ' connection';
        if (defined $obj->{'prefix_module_name'} && $obj->{'prefix_module_name'} ne '') {
          $name = $obj->{'prefix_module_name'} . $type . ' connection';
        }

        push @modules, {
          name => $name,
          type => 'generic_proc',
          data => 0,
          description => $type . ' availability'
        };

      } else {
        #
        # $results is always a hash with:
        #   @modules => 'global' modules.
        #   @data => {
        #	    'agent_data' => {}
        #     'module_data' => []
        #   }
        my $results;

        # Scan connected obj.
        if (   $self->{'task_data'}->{'type'} == DISCOVERY_APP_MYSQL
          || $self->{'task_data'}->{'type'} == DISCOVERY_APP_ORACLE
          || $self->{'task_data'}->{'type'} == DISCOVERY_APP_DB2
          || $self->{'task_data'}->{'type'} == DISCOVERY_APP_MICROSOFT_SQL_SERVER
        ) {

          # Database.
          $results = $self->database_scan($type, $obj, $global_percent, \@targets);

        } elsif ($self->{'task_data'}->{'type'} == DISCOVERY_APP_SAP) {

          # SAP scan
          $results = $obj->scan();

        }

        # Add results.
        if (ref($results) eq 'HASH') {
          if (defined($results->{'modules'})) {
            push @modules, @{$results->{'modules'}};
          }

          if (defined($results->{'data'})) {
            push @data, @{$results->{'data'}};
          }
        }
      }

      # Put engine agent at the beginning of the list.
      my $version = $obj->get_version();
      unshift @data, {
        'agent_data' => {
          'agent_name' => $obj->get_agent_name(),
          'os' => $type,
          'os_version' => (defined($version) ? $version : 'Discovery'),
          'interval' => $self->{'task_data'}->{'interval_sweep'},
          'id_group' => $self->{'task_data'}->{'id_group'},
          'address' => $obj->get_host(),
          'description' => '',
        },
        'module_data' => \@modules,
      };

      $self->call('create_agents', \@data);

      # Destroy item.
      undef($obj);
    }

    $global_percent += $global_step;
    $self->{'c_network_percent'} = 100;
    $self->call('update_progress', $global_percent);
  }

  # Update progress.
  # Done!
  $self->{'step'} = '';
  $self->call('update_progress', -1);

}


################################################################################
# Perform a deployment scan.
################################################################################
sub deploy_scan($) {
  my $self = shift;
  my ($progress, $step);

  my $type = '';

  # Initialize deployer object.
  my $deployer = PandoraFMS::Recon::Util::enterprise_new(
    'PandoraFMS::Recon::Deployer',
    [
      task_data => $self->{'task_data'},
      parent => $self
    ]

  );

  if (!$deployer) {

    # Failed to initialize, check Cloud credentials or anything.
    $self->call('message', 'Unable to initialize PandoraFMS::Recon::Deployer', 3);
  } else {

    # Let deployer object manage scan.
    $deployer->scan();
  }

  # Update progress.
  # Done!
  $self->{'step'} = '';
  $self->call('update_progress', -1);
}


################################################################################
# Perform a network scan.
################################################################################
sub scan($) {
  my ($self) = @_;
  my ($progress, $step) = 1, 0;

  # 1%
  $self->call('update_progress', 1);

  if (defined($self->{'task_data'})) {
    if (    $self->{'task_data'}->{'type'} == DISCOVERY_APP_MYSQL
      ||  $self->{'task_data'}->{'type'} == DISCOVERY_APP_ORACLE
      ||  $self->{'task_data'}->{'type'} == DISCOVERY_APP_DB2
      ||  $self->{'task_data'}->{'type'} == DISCOVERY_APP_MICROSOFT_SQL_SERVER
      ||  $self->{'task_data'}->{'type'} == DISCOVERY_APP_SAP) {
      # Application scan.
      $self->call('message', "Scanning application ...", 6);
      return $self->app_scan();
    }

    if ($self->{'task_data'}->{'type'} == DISCOVERY_CLOUD_AWS_RDS) {

      # Cloud scan.
      return $self->cloud_scan();
    }

    if($self->{'task_data'}->{'type'} == DISCOVERY_DEPLOY_AGENTS) {
      return $self->deploy_scan();
    }
  }

  if(defined($self->{'task_data'}{'review_mode'})
    && $self->{'task_data'}{'review_mode'} == DISCOVERY_RESULTS
  ) {
    # Use Cached results.
    $self->{'step'} = STEP_PROCESSING;
    $self->call('report_scanned_agents');

    # Done!
    $self->{'step'} = '';
    $self->call('update_progress', -1);
    return;
  }

  # Find devices.
  $self->call('message', "[1/6] Scanning the network...", 3);
  $self->{'c_network_name'} = '';
  $self->{'step'} = STEP_SCANNING;
  $self->call('update_progress', $progress);

  $self->scan_subnet();
  # Read the local ARP cache.
  $self->local_arp();

  # Get a list of found hosts.
  my @hosts = @{$self->get_hosts()};
  if (scalar(@hosts) > 0 && $self->{'parent_detection'} == 1) {

    # Delete previous connections.
    $self->call('delete_connections');

    # Connectivity from address forwarding tables.
    $self->call('message', "[2/6] Finding address forwarding table connectivity...", 3);
    $self->{'c_network_name'} = '';
    $self->{'step'} = STEP_AFT;
    ($progress, $step) = (50, (10.0 / scalar(@hosts)) / 2.0); # From 50% to 60%.

    # Connect hosts on ports where there are no other hosts.
    for (my $i = 0; defined($hosts[$i]); $i++) {
      $self->call('update_progress', $progress);
      $progress += $step;
      $self->aft_connectivity($hosts[$i], 1);
    }

    # Connect hosts on ports even if they're shared by other hosts.
    for (my $i = 0; defined($hosts[$i]); $i++) {
      $self->call('update_progress', $progress);
      $progress += $step;
      $self->aft_connectivity($hosts[$i], 0);
    }

    # Connect hosts that are still unconnected using traceroute.
    $self->call('message', "[3/6] Finding traceroute connectivity.", 3);
    $self->{'c_network_name'} = '';
    $self->{'step'} = STEP_TRACEROUTE;
    ($progress, $step) = (60, 10.0 / scalar(@hosts)); # From 60% to 70%.
    foreach my $host (@hosts) {
      $self->call('update_progress', $progress);
      $progress += $step;
      next if ($self->has_parent($host) || $self->has_children($host));
      $self->traceroute_connectivity($host);
    }

    # Connect hosts that are still unconnected using known gateways.
    $self->call('message', "[4/6] Finding host to gateway connectivity.", 3);
    $self->{'c_network_name'} = '';
    $self->{'step'} = STEP_GATEWAY;
    ($progress, $step) = (70, 10.0 / scalar(@hosts)); # From 70% to 80%.
    $self->get_routes(); # Update the route cache.
    foreach my $host (@hosts) {
      $self->call('update_progress', $progress);
      $progress += $step;
      next if ($self->has_parent($host));
      $self->gateway_connectivity($host);
    }
  }

  # Apply monitoring templates
  $self->call('message', "[5/6] Applying monitoring.", 3);
  $self->{'step'} = STEP_MONITORING;
  $self->call('apply_monitoring', $self);

  # Print debug information on found devices.
  $self->call('message', "[Summary]", 3);
  foreach my $host (@hosts) {
    my $device = $self->get_device($host);
    next unless defined($device);

    # Print device information.
    my $dev_info = "Device: " . $device->{'type'} . " (";
    foreach my $ip_address ($self->get_addresses($host)) {
      $dev_info .= "$ip_address,";
    }
    chop($dev_info);
    $dev_info .= ')';
    $self->call('message', $dev_info, 3);
  }

  # Apply monitoring templates
  $self->call('message', "[6/6] Processing results.", 3);
  $self->{'step'} = STEP_PROCESSING;
  # Send agent information to Database (Discovery) or XML (satellite.).
  $self->call('report_scanned_agents');

  if(defined($self->{'task_data'}{'review_mode'})
    && $self->{'task_data'}{'review_mode'} == DISCOVERY_STANDARD
  ) {
    # Send agent information to Database (Discovery) or XML (satellite.).
    $self->call('report_scanned_agents', 1);
  }

  # Done!
  $self->{'step'} = '';
  $self->call('update_progress', -1);

}

################################################################################
# Set an SNMP community for the given device.
################################################################################
sub set_community($$$) {
  my ($self, $device, $community) = @_;

  $self->{'community_cache'}->{$device} = $community;
}

################################################################################
# Set the type of the given device.
################################################################################
sub set_device_type($$$) {
  my ($self, $device, $type) = @_;

  $self->{'visited_devices'}->{$device}->{'type'} = $type;
}

################################################################################
# Calculate 
################################################################################
sub snmp_pen($$) {
  my ($self, $addr) = @_;

  $self->{'pen'} = {} if ref($self->{'pen'}) ne 'HASH';

  $self->{'pen'}{$addr} = $self->snmp_get_value($addr, $PEN_OID);

  if(defined($self->{'pen'}{$addr})) {
    ($self->{'pen'}{$addr}) = $self->{'pen'}{$addr} =~ /\.\d+\.\d+\.\d+\.\d+\.\d+\.\d+\.(\d+?)\./
  }

}

################################################################################
# Performs an SNMP WALK and returns the response as an array.
################################################################################
sub snmp_get($$$) {
  my ($self, $device, $oid) = @_;
  my @output;

  return () unless defined $self->is_snmp_discovered($device);
  my $community = $self->get_community($device);

  # Check the SNMP query cache first.
  if (defined($self->{'snmp_cache'}->{"${device}_${oid}"})) {
    return @{$self->{'snmp_cache'}->{"${device}_${oid}"}};
  }

  # Check VLANS.
  my @vlans = $self->get_vlans($device);
  if (scalar(@vlans) == 0) {
    my $command = $self->snmp_get_command($device, $oid, $community);
    @output = `$command`;
  }else {

    # Handle duplicate lines.
    my %output_hash;
    foreach my $vlan (@vlans) {
      my $command = $self->snmp_get_command($device, $oid, $community, $vlan);
      foreach my $line (`$command`) {
        $output_hash{$line} = 1;
      }
    }
    push(@output, keys(%output_hash));
  }

  # Update the SNMP query cache.
  $self->{'snmp_cache'}->{"${device}_${oid}"} = [@output];

  return @output;
}

################################################################################
# Get the snmpwalk command seing version 1, 2, 2c or 3.
################################################################################
sub snmp_get_command {
  my ($self, $device, $oid, $community, $vlan) = @_;
  $vlan = defined($vlan) ? "\@" . $vlan : '';

  my $command = "snmpwalk -M$DEVNULL -r$self->{'snmp_checks'} -t$self->{'snmp_timeout'} -v$self->{'snmp_version'} -On -Oe ";
  if ($self->{'snmp_version'} eq "3") {
    $command .= " $self->{'snmp3_auth'}{$device} ";
  } else {
    $command .= " -c\'$community\'$vlan ";
  }

  return "$command $device $oid 2>$DEVNULL";

}

################################################################################
# Performs an SNMP WALK and returns the value of the given OID. Returns undef
# on error.
################################################################################
sub snmp_get_value($$$) {
  my ($self, $device, $oid) = @_;

  my $effective_oid = $oid;
  if (is_enabled($self->{'translate_snmp'}) && $oid !~ /^[\.\d]+$/) {
    $effective_oid = `snmptranslate $oid -On 2>$DEVNULL`;
    $effective_oid =~ s/[\r\n]//g;
  }

  my @output = $self->snmp_get($device, $effective_oid);

  foreach my $line (@output) {
    $line =~ s/[\r\n]//g;
    return $1 if ($line =~ /^\.{0,1}$effective_oid\s+=\s+\S+:\s+(.*)/);
  }

  return undef;
}

################################################################################
# Performs an SNMP WALK and returns an array of values.
################################################################################
sub snmp_get_value_array($$$) {
  my ($self, $device, $oid) = @_;
  my @values;

  my @output = $self->snmp_get($device, $oid);
  foreach my $line (@output) {
    chomp($line);
    push(@values, $1) if ($line =~ /^\.{0,1}$oid\S*\s+=\s+\S+:\s+(.*)$/);
  }

  return @values;
}

################################################################################
# Performs an SNMP WALK and returns a hash of values.
################################################################################
sub snmp_get_value_hash($$$) {
  my ($self, $device, $oid) = @_;
  my %values;

  my @output = $self->snmp_get_value_array($device, $oid);
  foreach my $line (@output) {
    $values{$line} = '';
  }

  return %values;
}

################################################################################
# Connect the given host to its parent using traceroute.
################################################################################
sub traceroute_connectivity($$) {
  my ($self, $host) = @_;

  # Perform a traceroute.
  my $nmap_args  = '-nsP -PE --traceroute --max-retries '.$self->{'icmp_checks'}.' --host-timeout '.$self->{'icmp_timeout'}.'s -T'.$self->{'recon_timing_template'};
  my $np = PandoraFMS::Recon::NmapParser->new();
  eval {$np->parsescan($self->{'nmap'}, $nmap_args, ($host));};
  return if ($@);

  # Get hops to the host.
  my ($h) = $np->all_hosts();
  return unless defined($h);
  my @hops = $h->all_trace_hops();

  # Skip the target host.
  pop(@hops);

  # Reverse the host order (closest hosts first).
  @hops = reverse(@hops);

  # Look for parents.
  my $device = $host;
  for (my $i = 0; $i < $self->{'parent_recursion'}; $i++) {
    next if is_empty($hops[$i]);
    my $parent = $hops[$i]->ipaddr();

    # Create an agent for the parent.
    $self->add_agent($parent);

    $self->call('message', "Host $device is one hop away from host $parent.", 5);
    $self->mark_connected($parent, '', $device, '');

    # Move on to the next hop.
    $device = $parent;
  }
}

################################################################################
# Returns the credentials with which the host responds to WMI queries or
# undef if it does not respond to WMI.
################################################################################
sub wmi_credentials {
  my ($self, $target) = @_;
  return $self->{'wmi_auth'}{$target};
}

################################################################################
# Returns the credentials KEY with which the host responds to WMI queries or
# undef if it does not respond to WMI.
################################################################################
sub wmi_credentials_key {
  my ($self, $target) = @_;
  return $self->{'wmi_auth_key'}{$target};
}

################################################################################
# Calculate WMI credentials for target, 1 if calculated, undef if cannot
# connect to target. Credentials could be empty (-N)
################################################################################
sub wmi_credentials_calculation {
  my ($self, $target) = @_;

  # Test empty credentials.
  my @output = `$self->{'timeout_cmd'}$self->{'wmi_client'} -N //$target "SELECT * FROM Win32_ComputerSystem" 2>$DEVNULL`;
  my $rs = $self->wmi_output_check($?, @output);

  if ($rs == WMI_OK) {
    $self->{'wmi_auth'}{$target} = '';
    $self->{'wmi_auth_key'}{$target} = '';
    return 1;
  }

  if ($rs == WMI_UNREACHABLE) {
    # Target does not respond.
    $self->{'wmi'}{$target} = 0;
    return undef;
  }

  # Test all credentials selected.
  foreach my $key_index (@{$self->{'auth_strings_array'}}) {
    my $cred = $self->call('get_credentials', $key_index, 'WMI');
    next if !defined($cred);
    next if ref($cred) ne 'HASH';

    my $auth = $cred->{'username'}.'%'.$cred->{'password'};
    next if $auth eq '%';

    @output = `$self->{'timeout_cmd'}$self->{'wmi_client'} -U $auth //$target "SELECT * FROM Win32_ComputerSystem" 2>$DEVNULL`;

    my $rs = $self->wmi_output_check($?, @output);

    if ($rs == WMI_OK) {
      $self->{'wmi_auth'}{$target} = $auth;
      $self->{'wmi_auth_key'}{$target} = $key_index;
      $self->{'wmi'}{$target} = 1;
      $self->{'summary'}->{'WMI'} += 1;
      $self->call('message', "[".$target."] WMI available.", 10);
      return 1;
    }

    if ($rs == WMI_UNREACHABLE) {
      # Target does not respond.
      $self->call('message', "[".$target."] WMI unreachable.", 10);
      $self->{'wmi'}{$target} = 0;
      return undef;
    }
  }

  return undef;
}

################################################################################
# Returns the credentials with which the host responds to WMI queries or
# undef if it does not respond to WMI.
################################################################################
sub rcmd_credentials {
  my ($self, $target) = @_;
  return $self->{'rcmd_auth'}{$target};
}

################################################################################
# Returns the credentials KEY with which the host responds to WMI queries or
# undef if it does not respond to WMI.
################################################################################
sub rcmd_credentials_key {
  my ($self, $target) = @_;
  return $self->{'rcmd_auth_key'}{$target};
}

################################################################################
# Calculate WMI credentials for target, 1 if calculated, undef if cannot
# connect to target. Credentials could be empty (-N)
################################################################################
sub rcmd_credentials_calculation {
  my ($self, $target) = @_;

  my $rcmd = PandoraFMS::Recon::Util::enterprise_new(
    'PandoraFMS::RemoteCmd',[{
      'psexec' => $self->{'parent'}->{'pa_config'}->{'psexec'},
      'winexe' => $self->{'parent'}->{'pa_config'}->{'winexe'},
      'plink' => $self->{'parent'}->{'pa_config'}->{'plink'}
    }]
  );

  if (!$rcmd) {
    # Library not available.
    $self->call('message', "PandoraFMS::RemoteCmd library not available", 10);
    return undef;
  }

  my $os = $self->{'os_cache'}{$target};
  $os = $self->call('guess_os', $target, 1) if is_empty($os);
  $rcmd->set_host($target);
  $rcmd->set_os($os);

  $self->{'os_cache'}{$target} = $os;

  # Test all credentials selected.
  foreach my $key_index (@{$self->{'auth_strings_array'}}) {
    my $cred = $self->call('get_credentials', $key_index, 'CUSTOM');
    next if !defined($cred);
    next if ref($cred) ne 'HASH';
    $rcmd->clean_ssh_lib();

    my $username;
    my $domain;

    if($cred->{'username'} =~ /^(.*?)\\(.*)$/) {
      $domain = $1;
      $username = $2;
    } else {
      $username = $cred->{'username'};
    }

    $rcmd->set_credentials(
      {
        'user' => $username,
        'pass' => $cred->{'password'},
        'domain' => $domain
      }
    );

    $rcmd->set_timeout(
      $self->{'rcmd_timeout_bin'},
      $self->{'rcmd_timeout'}
    );

    my $result;
    eval {
      $result = $rcmd->rcmd('echo 1');
      chomp($result);
      my $out = '';
      $out = $result if !is_empty($result);
      $self->call('message', "Trying [".$key_index."] in [". $target."] [".$os."]: [$out]", 10);
    };
    if ($@) {
      $self->call('message', "Failed while trying [".$key_index."] in [". $target."] [".$os."]:" . @_, 10);
    }

    if (!is_empty($result) && $result == "1") {
      $self->{'rcmd_auth'}{$target} = $cred;
      $self->{'rcmd_auth_key'}{$target} = $key_index;
      $self->{'rcmd'}{$target} = 1;
      $self->{'summary'}->{'RCMD'} += 1;
      $self->call('message', "RCMD available for $target", 10);
      return 1;
    } else {
      $self->call('message', "Last error ($target|$os|$result) was [".$rcmd->get_last_error()."]", 10);
    }

  }

  # Not found.
  return 0;
}

################################################################################
# Tests wmi capability for addr.
################################################################################
sub wmi_discovery {
  my ($self, $addr) = @_;

  # Initialization.
  $self->{'wmi'} = {} unless ref($self->{'wmi'}) eq 'HASH';

  # Calculate credentials.
  $self->wmi_credentials_calculation($addr);

}

################################################################################
# Tests credentials against addr.
################################################################################
sub rcmd_discovery {
  my ($self, $addr) = @_;

  # Initialization.
  $self->{'rcmd'} = {} unless ref($self->{'rcmd'}) eq 'HASH';

  # Calculate credentials.
  $self->rcmd_credentials_calculation($addr);

}

################################################################################
# Extra: WMI imported methods. DO NOT EXPORT TO AVOID DOUBLE DEF.
################################################################################

################################################################################
# Validate wmi output. (err code and messages).
################################################################################
sub wmi_output_check {
  my ($self, $rc, @output) = @_;
  if ($? != 0) {
    # Something went wrong.
    if (defined($output[-1]) && $output[-1] =~ /NTSTATUS: (.*)/) {
      my $err = $1;
      $self->{'last_wmi_error'} = $err;

      if ($err =~ /NT_STATUS_IO_TIMEOUT/
        || $err =~ /NT_STATUS_CONNECTION_REFUSED/
      ) {
        # Fail.
        return WMI_UNREACHABLE;
      }

      if ($err =~ /NT_STATUS_ACCESS_DENIED/) {
        return WMI_BAD_PASSWORD;
      }
    }

    # Fail.
    return WMI_GENERIC_ERROR;
  }

  # Ok.
  return WMI_OK;
}

################################################################################
# Performs a wmi get requests and returns the response as an array.
################################################################################
sub wmi_get {
  my ($self, $target, $query) = @_;

  return () unless $self->wmi_responds($target);

  return $self->wmi_get_command($target, $self->{'wmi_auth'}{$target}, $query);
}

################################################################################
# Performs a wmi get requests and returns the response as an array.
################################################################################
sub wmi_get_command {
  my ($self, $target, $auth, $query) = @_;

  return () if is_empty($target);

  my @output;
  if (defined($auth) && $auth ne '') {
    $auth =~ s/'/\'/g;
    @output = `$self->{'timeout_cmd'}"$self->{'wmi_client'}" -U '$auth' //$target "$query" 2>$DEVNULL`;
  }else {
    @output = `$self->{'timeout_cmd'}"$self->{'wmi_client'}" -N //$target "$query" 2>$DEVNULL`;
  }

  my $rs = $self->wmi_output_check($?, @output);

  if ($rs == WMI_OK) {
    return @output;
  }

  my $err = $self->{'last_wmi_error'};
  $err = 'Not OK, empty error' if is_empty($err);

  $self->call(
    'message',
    "[".$target."] WMI error: ".$err,
    10
  );

  return ();
}

################################################################################
# Checks if target is reachable using wmi.
################################################################################
sub wmi_responds {
  my ($self, $target) = @_;
  return 1 if is_enabled($self->{'wmi'}{$target});
  return 0;
}

################################################################################
# Checks if target is reachable using rcmd.
################################################################################
sub rcmd_responds {
  my ($self, $target) = @_;
  return 1 if is_enabled($self->{'rcmd'}{$target});
  return 0;
}

################################################################################
# Performs a WMI request and returns the requested column of the first row.
# Returns undef on error.
################################################################################
sub wmi_get_value {
  my ($self, $target, $query, $column) = @_;
  my @result;

  my @output = $self->wmi_get($target, $query);
  return undef unless defined($output[2]);

  my $line = $output[2];
  chomp($line);
  my @columns = split(/\|/, $line);
  return undef unless defined($columns[$column]);

  return $columns[$column];
}

################################################################################
# Performs a WMI request and returns row values for the requested column
# in an array.
################################################################################
sub wmi_get_value_array {
  my ($self, $target, $query, $column) = @_;
  my @result;

  my @output = $self->wmi_get($target, $query);
  foreach (my $i = 2; defined($output[$i]); $i++) {
    my $line = $output[$i];
    chomp($line);
    my @columns = split(/\|/, $line);
    next unless defined($columns[$column]);
    push(@result, $columns[$column]);
  }

  return @result;
}

################################################################################
# END: WMI imported methods.
################################################################################

1;
__END__

