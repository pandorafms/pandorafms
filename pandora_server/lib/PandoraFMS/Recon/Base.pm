#!/usr/bin/perl
# (c) Ártica ST 2014 <info@artica.es>
# Module for network topology discovery.

package PandoraFMS::Recon::Base;
use strict;
use warnings;

# Default lib dir for RPM and DEB packages
use lib '/usr/lib/perl5';

use NetAddr::IP;
use POSIX qw/ceil/;
use PandoraFMS::Recon::NmapParser;
use PandoraFMS::Recon::Util;
use Socket qw/inet_aton/;

# /dev/null
my $DEVNULL = ($^O eq 'MSWin32') ? '/Nul' : '/dev/null';

# Some useful OIDs.
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
our $IPENTADDR = ".1.3.6.1.2.1.4.20.1.1";
our $IFNAME = ".1.3.6.1.2.1.31.1.1.1.1";
our $IFPHYSADDRESS = ".1.3.6.1.2.1.2.2.1.6";
our $IPNETTOMEDIAPHYSADDRESS = ".1.3.6.1.2.1.4.22.1.2";
our $IPADENTIFINDEX = ".1.3.6.1.2.1.4.20.1.2";
our $IPROUTEIFINDEX = ".1.3.6.1.2.1.4.21.1.2";
our $IPROUTENEXTHOP = ".1.3.6.1.2.1.4.21.1.7";
our $IPROUTETYPE = ".1.3.6.1.2.1.4.21.1.8";
our $PRTMARKERINDEX = ".1.3.6.1.2.1.43.10.2.1.1";
our $SYSDESCR = ".1.3.6.1.2.1.1.1";
our $SYSSERVICES = ".1.3.6.1.2.1.1.7";
our $SYSUPTIME = ".1.3.6.1.2.1.1.3";
our $VTPVLANIFINDEX = ".1.3.6.1.4.1.9.9.46.1.3.1.1.18.1";

our @ISA = ("Exporter");
our %EXPORT_TAGS = ( 'all' => [ qw( ) ] );
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

#######################################################################
# Create a new ReconTask object.
#######################################################################
sub new {
	my $class = shift;

	my $self = {

		# Known aliases (multiple IP addresses for the same host.
		aliases => {},

		# Keep our own ARP cache to connect hosts to switches/routers.
		arp_cache => {},

		# Working SNMP community for each device.
		community_cache => {},

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

		# Route cache.
		routes => [],
		default_gw => undef,

		# SNMP query cache.
		snmp_cache => {},

		# Globally enable/disable SNMP scans.
		snmp_enabled => 1,

		# Switch to switch connections. Used to properly connect hosts
		# that are connected to a switch wich is in turn connected to another switch,
		# since the hosts will show up in the latter's switch AFT too.
		switch_to_switch => {},

		# Visited devices (initially empty).
		visited_devices => {},

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
		snmp_checks => 2,
		snmp_timeout => 2,
		subnets => [],
		@_,

	};

	# Perform some sanity checks.
	die("No subnet was specified.") unless defined($self->{'subnets'});

	# Disable SNMP scans if no community was given.
	$self->{'snmp_enabled'} = 0 if (scalar(@{$self->{'communities'}}) == 0);

	return bless($self, $class);
}

########################################################################################
# Add an address to a device.
########################################################################################
sub add_addresses($$$) {
	my ($self, $device, $ip_address) = @_;

	$self->{'visited_devices'}->{$device}->{'addr'}->{$ip_address} = '';
}

########################################################################################
# Add a MAC/IP address to the ARP cache.
########################################################################################
sub add_mac($$$) {
	my ($self, $mac, $ip_addr) = @_;

	$mac = parse_mac($mac);
	$self->{'arp_cache'}->{$mac} = $ip_addr;
}

########################################################################################
# Add an interface/MAC to the interface cache.
########################################################################################
sub add_iface($$$) {
	my ($self, $iface, $mac) = @_;

	$iface =~ s/"//g;
	$self->{'ifaces'}->{$mac} = $iface;
}

########################################################################################
# Discover connectivity from address forwarding tables.
########################################################################################
sub aft_connectivity($$) {
	my ($self, $switch) = @_;
	my (%mac_temp, @aft_temp);

	return unless defined($self->get_community($switch));

	$self->enable_vlan_cache();

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
		$host_if_name = defined($host_if_name) ? $host_if_name : 'ping';

		# Get the interface associated to the port were we found the MAC address.
		my $switch_if_name = $self->get_if_from_aft($switch, $aft_mac);
		next unless defined ($switch_if_name) and ($switch_if_name ne '');

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


########################################################################################
# Return 1 if the given devices are connected to each other, 0 otherwise.
########################################################################################
sub are_connected($$$$$) {
	my ($self, $dev_1, $if_1, $dev_2, $if_2) = @_;

	# Check for aliases!
	$dev_1 = $self->{'aliases'}->{$dev_1} if defined($self->{'aliases'}->{$dev_1});
	$dev_2 = $self->{'aliases'}->{$dev_2} if defined($self->{'aliases'}->{$dev_2});

	# Use ping modules when interfaces are unknown.
	$if_1 = "ping" if $if_1 eq '';
	$if_2 = "ping" if $if_2 eq '';

	if (defined($self->{'connections'}->{"${dev_1}\t${if_1}\t${dev_2}\t{$if_2}"}) ||
	    defined($self->{'connections'}->{"${dev_2}\t${if_2}\t${dev_1}\t{$if_1}"})) {
		return 1;
	}

	return 0;
}

########################################################################################
# Discover as much information as possible from the given device using SNMP.
########################################################################################
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
	
			# Fill the VLAN cache.
			$self->find_vlans($device);
	
			# Guess the device type.
			$self->guess_device_type($device);
	
			# Find aliases for the device.
			$self->find_aliases($device);
			
			# Find interfaces for the device.
			$self->find_ifaces($device);
	
			# Try to learn more MAC addresses from the device's ARP cache.
			my @output = $self->snmp_get($device, $IPNETTOMEDIAPHYSADDRESS);
			foreach my $line (@output) {
				next unless ($line =~ /^$IPNETTOMEDIAPHYSADDRESS.\d+.(\S+)\s+=\s+\S+:\s+(.*)$/);
				my ($ip_addr, $mac_addr) = ($1, $2);
	
				# Skip broadcast, net and local addresses.
				next if ($ip_addr =~ m/\.255$|\.0$|127\.0\.0\.1$/);
	
				$mac_addr = parse_mac($mac_addr);
				$self->add_mac($mac_addr, $ip_addr);
				$self->call('message', "Found MAC $mac_addr for host $ip_addr in the ARP cache of host $device.", 5);
			}
		}
	}

	# Create an agent for the device and add it to the list of known hosts.
	push(@{$self->{'hosts'}}, $device);

	$self->call('create_agent', $device);
}

#######################################################################
# Try to call the given function on the given object.
#######################################################################
sub call {
	my $self = shift;
	my $func = shift;
	my @params = @_;

	if ($self->can($func)) {
		$self->$func(@params);
	}
}

########################################################################################
# Disable the VLAN cache.
########################################################################################
sub disable_vlan_cache($$) {
	my ($self, $device) = @_;

	$self->{'__vlan_cache_enabled__'} = 0;
}

########################################################################################
# Enable the VLAN cache.
########################################################################################
sub enable_vlan_cache($$) {
	my ($self, $device) = @_;

	return if ($self->{'vlan_cache_enabled'} == 0);
	$self->{'__vlan_cache_enabled__'} = 1;
}

##########################################################################
# Connect the given hosts to its gateway.
##########################################################################
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

########################################################################################
# Find IP address aliases for the given device.
########################################################################################
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

########################################################################################
# Find all the interfaces for the given host.
########################################################################################
sub find_ifaces($$) {
	my ($self, $device) = @_;

	# Does it respond to SNMP?
	my $community = $self->get_community($device);
	return unless defined($community);

	my @output = $self->snmp_get_value_array($device, $PandoraFMS::Recon::Base::IFINDEX);
	foreach my $if_index (@output) {

		next unless ($if_index =~ /^[0-9]+$/);

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

########################################################################################
# Find the device's VLANs and fill the VLAN cache.
########################################################################################
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

########################################################################################
# Return the addresses of the given device as an array.
########################################################################################
sub get_addresses($$) {
	my ($self, $device) = @_;

	if (defined($self->{'visited_devices'}->{$device})) {
		return keys(%{$self->{'visited_devices'}->{$device}->{'addr'}});
	}

	# By default return the given address.
	return ($device);
}

########################################################################################
# Return a device structure from an IP address.
########################################################################################
sub get_device($$) {
	my ($self, $addr) = @_;

	if (defined($self->{'visited_devices'}->{$addr})) {
		return $self->{'visited_devices'}->{$addr};
	}

	return undef;
}

########################################################################################
# Get the SNMP community of the given device. Returns undef if no community was found.
########################################################################################
sub get_community($$) {
	my ($self, $device) = @_;

	if (defined($self->{'community_cache'}->{$device})) {
		return $self->{'community_cache'}->{$device};
	}

	return undef;
}

########################################################################################
# Return the connection hash.
########################################################################################
sub get_connections($) {
	my ($self) = @_;

	return $self->{'connections'};
}

########################################################################################
# Return the parent relationship hash.
########################################################################################
sub get_parents($) {
	my ($self) = @_;

	return $self->{'parents'};
}

########################################################################################
# Get the type of the given device.
########################################################################################
sub get_device_type($$) {
	my ($self, $device) = @_;

	if (defined($self->{'visited_devices'}->{$device})) {
		return $self->{'visited_devices'}->{$device}->{'type'};
	}

	# Assume 'host' by default.
	return 'host';
}

########################################################################################
# Return all known hosts that are not switches or routers.
########################################################################################
sub get_hosts($) {
	my ($self) = @_;

	return $self->{'hosts'};
}

########################################################################################
# Add an interface/MAC to the interface cache.
########################################################################################
sub get_iface($$) {
	my ($self, $mac) = @_;

	return undef unless defined($self->{'ifaces'}->{$mac});

	return $self->{'ifaces'}->{$mac};
}

########################################################################################
# Get an interface name from an AFT entry. Returns undef on error.
########################################################################################
sub get_if_from_aft($$$) {
	my ($self, $switch, $mac) = @_;

	# Get the port associated to the MAC.
	my $port = $self->snmp_get_value($switch, "$DOT1DTPFDBPORT." . mac_to_dec($mac));
	return '' unless defined($port);

	# Get the interface index associated to the port.
	my $if_index = $self->snmp_get_value($switch, "$DOT1DBASEPORTIFINDEX.$port");
	return '' unless defined($if_index);

	# Get the interface name.
	my $if_name = $self->snmp_get_value($switch, "$IFNAME.$if_index");
	return "if$if_index" unless defined($if_name);

	$if_name =~ s/"//g;
	return $if_name;

}

########################################################################################
# Get an interface name from an IP address.
########################################################################################
sub get_if_from_ip($$$) {
	my ($self, $device, $ip_addr) = @_;

	# Get the port associated to the IP address.
	my $if_index = $self->snmp_get_value($device, "$IPROUTEIFINDEX.$ip_addr");
	return '' unless defined ($if_index);

	# Get the name of the interface associated to the port.
	my $if_name = $self->snmp_get_value($device, "$IFNAME.$if_index");
	return '' unless defined ($if_name);
	
	$if_name =~ s/"//g;
	return $if_name;
}

########################################################################################
# Get an interface name from a MAC address.
########################################################################################
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
		return '' unless defined ($if_name);
		
		$if_name =~ s/"//g;
		return $if_name;
	}
	
	return '';
}

########################################################################################
# Get an interface name from a port number. Returns '' on error.
########################################################################################
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

########################################################################################
# Returns the IP address of the given interface (by index).
########################################################################################
sub get_if_ip($$$) {
	my ($self, $device, $if_index) = @_;
	
	my @output = $self->snmp_get($device, $IPADENTIFINDEX);
	foreach my $line (@output) {
		chomp ($line);
		return $1 if ($line =~ m/^IPADENTIFINDEX.(\S+)\s+=\s+\S+:\s+$if_index$/);
	}
	
	return '';
}

########################################################################################
# Returns the MAC address of the given interface (by index).
########################################################################################
sub get_if_mac($$$) {
	my ($self, $device, $if_index) = @_;

	my $mac = $self->snmp_get_value($device, "$IFPHYSADDRESS.$if_index");
	return '' unless defined($mac);

	# Clean-up the MAC address.
	$mac = parse_mac($mac);

	return $mac;
}

########################################################################################
# Get an IP address from the ARP cache given the MAC address.
########################################################################################
sub get_ip_from_mac($$) {
	my ($self, $mac_addr) = @_;

	if (defined($self->{'arp_cache'}->{$mac_addr})) {
		return $self->{'arp_cache'}->{$mac_addr};
	}

	return undef;
}

########################################################################################
# Attemtps to find 
########################################################################################
sub get_mac_from_ip($$) {
	my ($self, $host) = @_;
	my $mac = undef;

	eval {
		$mac = `arping -c 1 -r $host 2>$DEVNULL`;
		$mac = undef unless ($? == 0);
	};

	return unless defined($mac);

	# Clean-up the MAC address.
	chomp($mac);
	$mac = parse_mac($mac);
	$self->add_mac($mac, $host);

	$self->call('message', "Found MAC $mac for host $host in the local ARP cache.", 5);
}

########################################################################################
# Get a port number from an AFT entry. Returns undef on error.
########################################################################################
sub get_port_from_aft($$$) {
	my ($self, $switch, $mac) = @_;

	# Get the port associated to the MAC.
	my $port = $self->snmp_get_value($switch, "$DOT1DTPFDBPORT." . mac_to_dec($mac));
	return '' unless defined($port);

	return $port;
}

########################################################################################
# Fill the route cache.
########################################################################################
sub get_routes($) {
	my ($self) = @_;

	# Empty the current route cache.
	$self->{'routes'} = [];

	# Parse route's output.
	my @output = `route -n 2>/dev/null`;
	foreach my $line (@output) {
		chomp($line);
		if ($line =~ /^0\.0\.0\.0\s+(\d+\.\d+\.\d+\.\d+).*/) {
			$self->{'default_gw'} = $1;
		} elsif ($line =~ /^(\d+\.\d+\.\d+\.\d+)\s+(\d+\.\d+\.\d+\.\d+)\s+(\d+\.\d+\.\d+\.\d+).*/) {
			push(@{$self->{'routes'}}, { dest => $1, gw => $2, mask => $3 });
		}
	}

	# Replace 0.0.0.0 with the default gateway's IP.
	return unless defined ($self->{'default_gw'});
	foreach my $route (@{$self->{'routes'}}) {
		$route->{gw} = $self->{'default_gw'} if ($route->{'gw'} eq '0.0.0.0');
	}
}

########################################################################################
# Get the gateway to reach the given host.
########################################################################################
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

########################################################################################
# Return a pointer to an array containing configured subnets.
########################################################################################
sub get_subnets($) {
	my ($self) = @_;

	return $self->{'subnets'};
}

########################################################################################
# Get an array of all the visited devices.
# NOTE: This functions returns the whole device structures, not just address
# like get_hosts, get_switches, get_routers and get_all_devices.
########################################################################################
sub get_visited_devices($) {
	my ($self) = @_;

	return $self->{'visited_devices'};
}

########################################################################################
# Returns an array of found VLAN IDs.
########################################################################################
sub get_vlans($$) {
	my ($self, $device) = @_;

	# Is the VLAN cache disabled?
	return () unless ($self->{'__vlan_cache_enabled__'} == 1);

	return () unless defined($self->{'vlan_cache'}->{$device});

	return @{$self->{'vlan_cache'}->{$device}};
}

########################################################################################
# Guess the type of the given device.
########################################################################################
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
		}
		else {
			# Bridge MIB?
			if (defined($bridge_mib)) {
				$device_type = 'switch';
			} else {
				$device_type = 'host';
			}
		}
	}
	else {
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
		}
		else {
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

########################################################################################
# Return 1 if the given device has a parent.
########################################################################################
sub has_parent($$) {
	my ($self, $device) = @_;

	# Check for aliases!
	$device = $self->{'aliases'}->{$device} if defined($self->{'aliases'}->{$device});

	return 1 if (defined($self->{'parents'}->{$device}));

	return 0;
}

########################################################################################
# Returns 1 if the device belongs to one of the scanned subnets.
########################################################################################
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

##########################################################################
# Check for switches that are connected to other switches/routers and show
# up in a switch/router's port.
##########################################################################
sub is_switch_connected($$$) {
	my ($self, $device, $iface) = @_;

	# Check for aliases!
	$device = $self->{'aliases'}->{$device} if defined($self->{'aliases'}->{$device});

	return 1 if defined ($self->{'switch_to_switch'}->{"${device}\t${iface}"});

	return 0;
}

########################################################################################
# Returns 1 if the given device has already been visited, 0 otherwise.
########################################################################################
sub is_visited($$) {
	my ($self, $device) = @_;

	# Check for aliases!
	$device = $self->{'aliases'}->{$device} if defined($self->{'aliases'}->{$device});

	if (defined($self->{'visited_devices'}->{$device})) {
		return 1;
	}

	return 0;
}

########################################################################################
# Mark the given devices as connected to each other on the given interfaces.
########################################################################################
sub mark_connected($$;$$$) {
	my ($self, $parent, $parent_if, $child, $child_if) = @_;

	# Check for aliases!
	$parent = $self->{'aliases'}->{$parent} if defined($self->{'aliases'}->{$parent});
	$child = $self->{'aliases'}->{$child} if defined($self->{'aliases'}->{$child});

	# Use ping modules when interfaces are unknown.
	$parent_if = "ping" if $parent_if eq '';
	$child_if = "ping" if $child_if eq '';

	# Do not connect devices using ping modules. A parent-child relationship is enough.
	if ($parent_if ne "ping" || $child_if ne "ping") {
		$self->{'connections'}->{"${parent}\t${parent_if}\t${child}\t${child_if}"} = 1;
		$self->call('connect_agents', $parent, $parent_if, $child, $child_if);
	}

	# Prevent parent-child loops.
	if (!defined($self->{'parents'}->{$parent}) ||
		$self->{'parents'}->{$parent} ne $child) {

		# A parent-child relationship is always created to help complete the map with
		# layer 3 information.
		$self->{'parents'}->{$child} = $parent;
		$self->call('set_parent', $child, $parent);
	}
}

########################################################################################
# Mark the given switch as having a connection on the given interface.
########################################################################################
sub mark_switch_connected($$$) {
	my ($self, $device, $iface) = @_;

	# Check for aliases!
	$device = $self->{'aliases'}->{$device} if defined($self->{'aliases'}->{$device});
	$self->{'switch_to_switch'}->{"${device}\t${iface}"} = 1;
}

########################################################################################
# Mark the given device as visited.
########################################################################################
sub mark_visited($$) {
	my ($self, $device) = @_;

	$self->{'visited_devices'}->{$device} = { 'addr' => { $device => '' },
	                                          'type' => 'host' };
}

########################################################################################
# Looks for a working SNMP community for the given device. Returns 1 if one is
# found, 0 otherwise. Updates the SNMP community cache.
########################################################################################
sub snmp_responds($$) {
	my ($self, $device) = @_;

	# We already have a working SNMP community for this device.
	return 1 if (defined($self->get_community($device)));

	foreach my $community (@{$self->{'communities'}}) {

		# Clean blanks.
		$community =~ s/\s+//g;

		`snmpwalk -M/dev/null -r$self->{'snmp_checks'} -t$self->{'snmp_timeout'} -v1 -On -Oe -c $community $device .0 2>/dev/null`;
		if ($? == 0) {
			$self->set_community($device, $community);
			return 1;
		}
	}

	return 0;
}

##############################################################################
# Ping the given host. Returns 1 if the host is alive, 0 otherwise.
##############################################################################
sub ping ($$$) {
	my ($self, $host) = @_;
	my ($timeout, $retries, $packets) = (
		$self->{'icmp_timeout'},
		$self->{'icmp_checks'},
		1,
	);

	# Windows
	if (($^O eq "MSWin32") || ($^O eq "MSWin32-x64") || ($^O eq "cygwin")){
		$timeout *= 1000; # Convert the timeout to milliseconds.
		for (my $i = 0; $i < $retries; $i++) {
			my $output = `ping -n $packets -w $timeout $host`;
			return 1 if ($output =~ /TTL/);
		}

		return 0;
	}
	
	# Solaris
	if ($^O eq "solaris"){
		my $ping_command = $host =~ /\d+:|:\d+/ ? "ping -A inet6" : "ping";
		for (my $i = 0; $i < $retries; $i++) {
			# Note: There is no timeout option.
			`$ping_command -s -n $host 56 $packets >/dev/null 2>&1`;
			return 1 if ($? == 0);
		}

		return 0;
	}
	
	# FreeBSD
	if ($^O eq "freebsd"){
		my $ping_command = $host =~ /\d+:|:\d+/ ? "ping6" : "ping -t $timeout";
		for (my $i = 0; $i < $retries; $i++) {
			# Note: There is no timeout option for ping6.
			`$ping_command -q -n -c $packets $host >/dev/null 2>&1`;
			return 1 if ($? == 0);
		}

		return 0;
	}

	# NetBSD
	if ($^O eq "netbsd"){                      
		my $ping_command = $host =~ /\d+:|:\d+/ ? "ping6" : "ping -w $timeout";
		for (my $i = 0; $i < $retries; $i++) {
			# Note: There is no timeout option for ping6.
			`$ping_command -q -n -c $packets $host >/dev/null 2>&1`;
			if ($? == 0) {
				return 1;
			}
		}

		return 0;
	}
	
	# Assume Linux by default.
	my $ping_command = $host =~ /\d+:|:\d+/ ? "ping6" : "ping";
	for (my $i = 0; $i < $retries; $i++) {
		`$ping_command -q -W $timeout -n -c $packets $host >/dev/null 2>&1`;	
		return 1 if ($? == 0);
	}

	return 0;
}

##########################################################################
# Scan the given subnet.
##########################################################################
sub scan_subnet($) {
	my ($self) = @_;
	my $progress = 1;

	my @subnets = @{$self->get_subnets()};
	foreach my $subnet (@subnets) {

		# Clean blanks.
		$subnet =~ s/\s+//g;

		my $net_addr = new NetAddr::IP ($subnet);
		if (!defined($net_addr)) {
			$self->call('message', "Invalid network: $subnet", 3);
			next;
		}

		# Save the network and broadcast addresses.
		my $network = $net_addr->network();
		my $broadcast = $net_addr->broadcast();

		# fping scan.
		if (-x $self->{'fping'} && $net_addr->num() > 1) {
			$self->call('message', "Calling fping...", 5);
	
			my @hosts = `$self->{'fping'} -ga "$subnet" 2>DEVNULL`;
			next if (scalar(@hosts) == 0);
		
			my $step = 50.0 / scalar(@subnets) / scalar(@hosts); # The first 50% of the recon task approx.
			foreach my $line (@hosts) {
				chomp($line);

				my @temp = split(/ /, $line);
				next if (scalar(@temp) != 1); # Junk is shown for broadcast addresses.
				my $host = $temp[0];

				# Skip network and broadcast addresses.
				next if ($host eq $network->addr() || $host eq $broadcast->addr());
				
				$self->call('message', "Scanning host: $host", 5);
				$self->call('update_progress', ceil($progress));
				$progress += $step;
		
				$self->snmp_discovery($host);
			}
		}
		# ping scan.
		else {
			my @hosts = map { (split('/', $_))[0] } $net_addr->hostenum;
			next if (scalar(@hosts) == 0);
		
			my $step = 50.0 / scalar(@subnets) / scalar(@hosts); # The first 50% of the recon task approx.
			foreach my $host (@hosts) {
		
				$self->call('message', "Scanning host: $host", 5);
				$self->call('update_progress', ceil($progress));
				$progress += $step;
		
				# Check if the host is up.
				next if ($self->ping($host) == 0);
		
				$self->snmp_discovery($host);
			}
		}
	}
}

##########################################################################
# Perform a network scan.
##########################################################################
sub scan($) {
	my ($self) = @_;
	my ($progress, $step);

	# 1%
	$self->call('update_progress', 1);

	# Find devices.
	$self->call('message', "[1/5] Scanning the network...", 3);
	$self->scan_subnet();

	# Get a list of found hosts.
	my @hosts = @{$self->get_hosts()};
	if (scalar(@hosts) > 0 && $self->{'parent_detection'} == 1) {
		# Delete previous connections. 
		$self->call('delete_connections');
	
		# Connectivity from address forwarding tables.
		$self->call('message', "[1/4] Finding address forwarding table connectivity...", 3);
		($progress, $step) = (50, 20.0 / scalar(@hosts)); # From 50% to 70%.
		for (my $i = 0; defined($hosts[$i]); $i++) {
			$self->call('update_progress', $progress);
			$progress += $step;
			$self->aft_connectivity($hosts[$i]);
		}
	
		# Connect hosts that are still unconnected using traceroute.
		$self->call('message', "[3/4] Finding traceroute connectivity.", 3);
		($progress, $step) = (70, 20.0 / scalar(@hosts)); # From 70% to 90%.
		foreach my $host (@hosts) {
			$self->call('update_progress', $progress);
			$progress += $step;
			next if ($self->has_parent($host));
			$self->traceroute_connectivity($host);
		}
	
		# Connect hosts that are still unconnected using known gateways.
		$self->call('message', "[4/4] Finding host to gateway connectivity.", 3);
		($progress, $step) = (90, 10.0 / scalar(@hosts)); # From 70% to 90%.
		$self->get_routes(); # Update the route cache.
		foreach my $host (@hosts) {
			$self->call('update_progress', $progress);
			$progress += $step;
			next if ($self->has_parent($host));
			$self->gateway_connectivity($host);
		}
	}

	# Done!
	$self->call('update_progress', -1);
	
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
}

########################################################################################
# Set an SNMP community for the given device.
########################################################################################
sub set_community($$$) {
	my ($self, $device, $community) = @_;

	$self->{'community_cache'}->{$device} = $community;
}

########################################################################################
# Set the type of the given device.
########################################################################################
sub set_device_type($$$) {
	my ($self, $device, $type) = @_;

	$self->{'visited_devices'}->{$device}->{'type'} = $type;
}

########################################################################################
# Performs an SNMP WALK and returns the response as an array.
########################################################################################
sub snmp_get($$$) {
	my ($self, $device, $oid) = @_;
	my @output;

	my $community = $self->get_community($device);
	return () unless defined ($community);

	# Check the SNMP query cache first.
	if (defined($self->{'snmp_cache'}->{"${device}_${oid}"})) {
		return @{$self->{'snmp_cache'}->{"${device}_${oid}"}};
	}

	# Check VLANS.
	my @vlans = $self->get_vlans($device);
	if (scalar(@vlans) == 0) {
		@output = `snmpwalk -M/dev/null -r$self->{'snmp_checks'} -t$self->{'snmp_timeout'}  -v1 -On -Oe -c $community $device $oid 2>/dev/null`;
	}
	else {
		# Handle duplicate lines.
		my %output_hash;
		foreach my $vlan (@vlans) {
			foreach my $line (`snmpwalk -M/dev/null -r$self->{'snmp_checks'} -t$self->{'snmp_timeout'}  -v1 -On -Oe -c $community\@$vlan $device $oid 2>/dev/null`) {
				$output_hash{$line} = 1;
			}
		}
		push(@output, keys(%output_hash));
	}

	# Update the SNMP query cache.
	$self->{'snmp_cache'}->{"${device}_${oid}"} = [@output];

	return @output;
}

########################################################################################
# Performs an SNMP WALK and returns the value of the given OID. Returns undef
# on error.
########################################################################################
sub snmp_get_value($$$) {
	my ($self, $device, $oid) = @_;

	my @output = $self->snmp_get($device, $oid);
	foreach my $line (@output) {
		chomp ($line);
		return $1 if ($line =~ /^$oid\s+=\s+\S+:\s+(.*)$/);
	}

	return undef;
}

########################################################################################
# Performs an SNMP WALK and returns an array of values.
########################################################################################
sub snmp_get_value_array($$$) {
	my ($self, $device, $oid) = @_;
	my @values;

	my @output = $self->snmp_get($device, $oid);
	foreach my $line (@output) {
		chomp ($line);
		push(@values, $1) if ($line =~ /^$oid\S*\s+=\s+\S+:\s+(.*)$/);
	}

	return @values;
}

########################################################################################
# Performs an SNMP WALK and returns a hash of values.
########################################################################################
sub snmp_get_value_hash($$$) {
	my ($self, $device, $oid) = @_;
	my %values;

	my @output = $self->snmp_get_value_array($device, $oid);
	foreach my $line (@output) {
		$values{$line} = '';
	}

	return %values;
}

##########################################################################
# Connect the given host to its parent using traceroute.
##########################################################################
sub traceroute_connectivity($$) {
	my ($self, $host) = @_;

	# Perform a traceroute.
	my $nmap_args  = '-nsP -PE --traceroute --max-retries '.$self->{'icmp_checks'}.' --host-timeout '.$self->{'icmp_timeout'}.'s -T'.$self->{'recon_timing_template'};
	my $np = PandoraFMS::Recon::NmapParser->new();
	eval {
		$np->parsescan($self->{'nmap'}, $nmap_args, ($host));
	};
	return if ($@);
	
	# Get hops to the host.
	my ($h) = $np->all_hosts ();
	return unless defined ($h);
	my @hops = $h->all_trace_hops ();

	# Skip the target host.
	pop(@hops);
	
	# Reverse the host order (closest hosts first).
	@hops = reverse(@hops);
	
	# Look for parents.
	my $device = $host;
	for (my $i = 0; $i < $self->{'parent_recursion'}; $i++) {
		next unless defined($hops[$i]);
		my $parent = $hops[$i]->ipaddr();

		# Create an agent for the parent.
		$self->call('create_agent', $parent);

		$self->call('message', "Host $device is one hop away from host $parent.", 5);
		$self->mark_connected($parent, '', $device, ''); 

		# Move on to the next hop.
		$device = $parent;
	}
}

1;
__END__

