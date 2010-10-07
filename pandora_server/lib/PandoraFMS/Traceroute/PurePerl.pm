# Repackaged by Pandora FMS
# Original lib at Net::Traceroute::PurePerl;

package PandoraFMS::Traceroute::PurePerl;

use vars qw(@ISA $VERSION $AUTOLOAD %net_traceroute_native_var %protocols);
use strict;
use warnings;
use PandoraFMS::Traceroute;
use Socket;
use FileHandle;
use Carp qw(carp croak);
use Time::HiRes qw(time);

@ISA = qw(PandoraFMS::Traceroute);
$VERSION = '0.10';

# Constants from header files or RFCs

use constant SO_BINDTODEVICE        => 25;   # from asm/socket.h
use constant IPPROTO_IP             => 0;    # from netinet/in.h

# Windows winsock2 uses 4 for IP_TTL instead of 2
use constant IP_TTL                 => ($^O eq "MSWin32") ? 4 : 2;

use constant IP_HEADERS             => 20;   # Length of IP headers
use constant ICMP_HEADERS           => 8;    # Length of ICMP headers
use constant UDP_HEADERS            => 8;    # Length of UDP headers

use constant IP_PROTOCOL            => 9;    # Position of protocol number

use constant UDP_DATA               => IP_HEADERS + UDP_HEADERS;
use constant ICMP_DATA              => IP_HEADERS + ICMP_HEADERS;

use constant UDP_SPORT              => IP_HEADERS + 0; # Position of sport
use constant UDP_DPORT              => IP_HEADERS + 2; # Position of dport

use constant ICMP_TYPE              => IP_HEADERS + 0; # Position of type
use constant ICMP_CODE              => IP_HEADERS + 2; # Position of code
use constant ICMP_ID                => IP_HEADERS + 4; # Position of ID
use constant ICMP_SEQ               => IP_HEADERS + 6; # Position of seq

use constant ICMP_PORT              => 0;    # ICMP has no port

use constant ICMP_TYPE_TIMEEXCEED   => 11;   # ICMP Type
use constant ICMP_TYPE_ECHO         => 8;    # ICMP Type
use constant ICMP_TYPE_UNREACHABLE  => 3;    # ICMP Type
use constant ICMP_TYPE_ECHOREPLY    => 0;    # ICMP Type

use constant ICMP_CODE_ECHO         => 0;    # ICMP Echo has no code

# Perl 5.8.6 under Windows has a bug in the socket code, this env variable
# works around the bug. It may effect other versions as well, and they should
# be added here
BEGIN
{
   if ($^O eq "MSWin32" and $^V eq v5.8.6)
   {
      $ENV{PERL_ALLOW_NON_IFS_LSP} = 1;
   }
}

# The list of currently accepted protocols
%protocols = 
(
               'icmp'      => 1,
               'udp'       => 1,
);

my @icmp_unreach_code = 
(     
               TRACEROUTE_UNREACH_NET,
               TRACEROUTE_UNREACH_HOST,
               TRACEROUTE_UNREACH_PROTO,
               0,
               TRACEROUTE_UNREACH_NEEDFRAG,
               TRACEROUTE_UNREACH_SRCFAIL, 
);

# set up allowed autoload attributes we need
my @net_traceroute_native_vars = qw(use_alarm concurrent_hops protocol 
      first_hop device);

@net_traceroute_native_var{@net_traceroute_native_vars} = ();

# Methods

# AUTOLOAD (perl internal)
# Used to create the methods for the object dynamically from 
# net_traceroute_naive_vars.
sub AUTOLOAD 
{
   my $self = shift;
   my $attr = $AUTOLOAD;
   $attr =~ s/.*:://;
   return unless $attr =~ /[^A-Z]/;  # skip DESTROY and all-cap methods
   carp "invalid attribute method: ->$attr()" 
      unless exists $net_traceroute_native_var{$attr};
   $self->{$attr} = shift if @_;
   return $self->{$attr};
}

# new (public method)
# Creates a new blessed object of type Net::Traceroute::PurePerl.
# Accepts many options as arguments, and initilizes the new object with
# their values.
# Croaks on bad arguments.
sub new 
{
   my $self = shift;
   my $type = ref($self) || $self;
   my %arg = @_;

   $self = bless {}, $type;

   # keep a loop from happening when calling super::new
   my $backend = delete $arg{'backend'};

   # used to get around the real traceroute running the trace
   my $host = delete $arg{'host'};

   # Old method to use ICMP for traceroutes, using `protocol' is preferred
   my $useicmp = delete $arg{'useicmp'};

   $self->debug_print(1, 
      "The useicmp parameter is depreciated, use `protocol'\n") if ($useicmp);

   # Initialize blessed hash with passed arguments
   $self->_init(%arg);

   # Set protocol to ICMP if useicmp was set;
   if ($useicmp)
   {
      carp ("Protocol already set, useicmp is overriding")
         if (defined $self->protocol  and $self->protocol ne "icmp");
      $self->protocol('icmp') if ($useicmp);
   }

   # put our host back in and set defaults for undefined options
   $self->host($host)         if (defined $host);
   $self->max_ttl(30)         unless (defined $self->max_ttl); 
   $self->queries(3)          unless (defined $self->queries);
   $self->base_port(33434)    unless (defined $self->base_port); 
   $self->query_timeout(5)    unless (defined $self->query_timeout); 
   $self->packetlen(40)       unless (defined $self->packetlen); 
   $self->first_hop(1)        unless (defined $self->first_hop);
   $self->concurrent_hops(6)  unless (defined $self->concurrent_hops);
   
   # UDP is the UNIX default for traceroute
   $self->protocol('udp')     unless (defined $self->protocol);

   # Depreciated: we no longer use libpcap, so the alarm is no longer
   # required. Kept for backwards compatibility but not used.
   $self->use_alarm(0)        unless (defined $self->use_alarm); 

   # Validates all of the parameters.
   $self->_validate();
   
   return $self;
}

# _init (private initialization method)
# Overrides Net::Traceroutes init to set PurePerl specific parameters.
sub _init
{
   my $self = shift;
   my %arg  = @_;

   foreach my $var (@net_traceroute_native_vars)
   {
      if(defined($arg{$var})) {
         $self->$var($arg{$var});
      }
   }

   $self->SUPER::init(@_);
}

# pretty_print (public method)
# The output of pretty_print tries to match the output of traceroute(1) as 
# close as possible, with two excpetions. First, I cleaned up the columns to
# make it easier to read, and second, I start a new line if the host IP changes
# instead of printing the new IP inline. The first column stays the same hop 
# number, only the host changes.
sub pretty_print 
{
   my $self    = shift;
   my $resolve = shift;

   print "traceroute to " . $self->host;
   print " (" . inet_ntoa($self->{'_destination'}) . "), ";
   print  $self->max_ttl . " hops max, " . $self->packetlen ." byte packets\n";

   my $lasthop = $self->first_hop + $self->hops - 1;
   
   for (my $hop=$self->first_hop; $hop <= $lasthop; $hop++)
   {
      my $lasthost = '';

      printf '%2s ', $hop;

      if (not $self->hop_queries($hop))
      {
         print "error: no responses\n";
         next;
      }

      for (my $query=1; $query <= $self->hop_queries($hop); $query++) {
         my $host = $self->hop_query_host($hop,$query);
         if ($host and $resolve)
         {
            my $ip = $host;
            $host = (gethostbyaddr(inet_aton($ip),AF_INET))[0] || $ip;
         }
         if ($host and ( not $lasthost or $host ne $lasthost ))
         {
            printf "\n%2s ", $hop if ($lasthost and $host ne $lasthost);
            printf '%-15s ', $host;
            $lasthost = $host;
         }
         my $time = $self->hop_query_time($hop, $query);
         if (defined $time and $time > 0)
         {
            printf '%7s ms ', $time;
         }
         else
         {
            print "* ";
         }
      }

      print "\n";
   }

   return;
}

# traceroute (public method)
# Starts a new traceroute. This is a blocking call and it will either croak on
# error, or return 0 if the host wasn't reached, or 1 if it was.
sub traceroute 
{
   my $self = shift;

   # Revalidate parameters incase they were changed by calling $t->parameter()
   # since the object was created.
   $self->_validate();

   carp "No host provided!" && return undef unless (defined $self->host);
   
   $self->debug_print(1, "Performing traceroute\n");

   # Lookup the destination IP inside of a local scope
   {
      my $destination = inet_aton($self->host);
      
      croak "Could not resolve host " . $self->host 
         unless (defined $destination);

      $self->{_destination} = $destination;
   }
    
   # release any old hop structure
   $self->_zero_hops();

   # Create the ICMP socket, used to send ICMP messages and receive ICMP errors
   # Under windows, the ICMP socket doesn't get the ICMP errors unless the
   # sending socket was ICMP, or the interface is in promiscuous mode, which 
   # is why ICMP is the only supported protocol under windows.
   my $icmpsocket = FileHandle->new();

   socket($icmpsocket, PF_INET, SOCK_RAW, getprotobyname('icmp')) ||
      croak("ICMP Socket error - $!");

   $self->debug_print(2, "Created ICMP socket to receive errors\n");

   $self->{'_icmp_socket'}    = $icmpsocket;
   $self->{'_trace_socket'}   = $self->_create_tracert_socket();

   # _run_traceroute is the event loop that actually does the work.
   my $success = $self->_run_traceroute();

   return $success;
}

# Private methods

# _validate (private method)
# Normalizes and validates all parameters, croaks on error
sub _validate
{
   my $self = shift;

   # Normalize values;

   $self->protocol(           lc $self->protocol);

   $self->max_ttl(            sprintf('%i',$self->max_ttl));
   $self->queries(            sprintf('%i',$self->queries));
   $self->base_port(          sprintf('%i',$self->base_port));
   $self->query_timeout(      sprintf('%i',$self->query_timeout));
   $self->packetlen(          sprintf('%i',$self->packetlen));
   $self->first_hop(          sprintf('%i',$self->first_hop));
   $self->concurrent_hops(    sprintf('%i',$self->concurrent_hops));

   # Check to see if values are sane

   croak "Parameter `protocol' value is not supported : " . $self->protocol 
      if (not exists $protocols{$self->protocol});

   croak "Parameter `first_hop' must be an integer between 1 and 255"
      if ($self->first_hop < 1 or $self->first_hop > 255);

   croak "Parameter `max_ttl' must be an integer between 1 and 255"
      if ($self->max_ttl < 1 or $self->max_ttl > 255);

   croak "Parameter `base_port' must be an integer between 1 and 65280"
      if ($self->base_port < 1 or $self->base_port > 65280);

   croak "Parameter `packetlen' must be an integer between 40 and 1492"
      if ($self->packetlen < 40 or $self->packetlen > 1492);

   croak "Parameter `first_hop' must be less than or equal to `max_ttl'"
      if ($self->first_hop > $self->max_ttl);

   croak "parameter `queries' must be an interger between 1 and 255"
      if ($self->queries < 1 or $self->queries > 255);
   
   croak "parameter `concurrent_hops' must be an interger between 1 and 255"
      if ($self->concurrent_hops < 1 or $self->concurrent_hops > 255);

   croak "protocol " . $self->protocol . " not supported under Windows"
      if ($self->protocol ne 'icmp' and $^O eq 'MSWin32');

   return;
}

# _run_traceroute (private method)
# The heart of the traceroute method. Sends out packets with incrementing
# ttls per hop. Recieves responses, validates them, and updates the hops
# hash with the time and host. Processes timeouts and returns when the host
# is reached, or the last packet on the last hop sent has been received
# or has timed out. Returns 1 if the host was reached, or 0.
sub _run_traceroute
{
   my $self = shift;

   my (  $end,          # Counter for endhop to wait until all queries return
         $endhop,       # The hop that the host was reached on
         $stop,         # Tells the main loop to exit
         $sentpackets,  # Number of packets sent
         $currenthop,   # Current hop
         $currentquery, # Current query within the hop
         $nexttimeout,  # Next time a packet will timeout
         $rbits,        # select() bits
         $nfound,       # Number of ready sockets from select()
         %packets,      # Hash of packets sent but without a response
         %pktids,       # Hash of packet port or seq numbers to packet ids
      );

   $stop = $end = $endhop = $sentpackets = 0;

   %packets = ();
   %pktids  = ();

   $currenthop    = $self->first_hop;
   $currentquery  = 0;

   $rbits   = "";
   vec($rbits,$self->{'_icmp_socket'}->fileno(), 1) = 1;

   while (not $stop)
   {
      # Reset the variable
      $nfound = 0;

      # Send packets so long as there are packets to send, there is less than
      # conncurrent_hops packets currently outstanding, there is no packets
      # waiting to be read on the socket and we haven't reached the host yet.
      while (scalar keys %packets < $self->concurrent_hops and 
            $currenthop <= $self->max_ttl and
            (not $endhop or $currenthop <= $endhop) and
            not $nfound = select((my $rout = $rbits),undef,undef,0))
      {
         # sentpackets is used as an uid in the %packets hash.
         $sentpackets++;

         $self->debug_print(1,"Sending packet $currenthop $currentquery\n");
         my $start_time = $self->_send_packet($currenthop,$currentquery);
         my $id         = $self->{'_last_id'};
         my $localport  = $self->{'_local_port'};

         $packets{$sentpackets} =  
         {
               'id'        => $id,
               'hop'       => $currenthop,
               'query'     => $currentquery,
               'localport' => $localport,
               'starttime' => $start_time,
               'timeout'   => $start_time+$self->query_timeout,
         };

         $pktids{$id} = $sentpackets;

         $nexttimeout = $packets{$sentpackets}{'timeout'} 
            unless ($nexttimeout);

         # Current query and current hop increments
         $currentquery = ($currentquery + 1) % $self->queries;
         if ($currentquery == 0)
         {
            $currenthop++;
         }
      }

      # If $nfound is nonzero than data is waiting to be read, no need to
      # call select again.
      if (not $nfound) # No data waiting to be read yet
      {
         # This sets the timeout for select to no more than .1 seconds
         my $timeout = $nexttimeout - time;
         $timeout    = .1 if ($timeout > .1);
         $nfound     = select((my $rout = $rbits),undef,undef,$timeout);
      }

      # While data is waiting to be read, read it.
      while ($nfound and keys %packets)
      {
         my (  $recv_msg,     # The packet read by recv()
               $from_saddr,   # The saddr returned by recv()
               $from_port,    # The port the packet came from
               $from_ip,      # The IP the packet came from
               $from_id,      # The dport / seq of the received packet
               $from_proto,   # The protocol of the packet
               $from_type,    # The ICMP type of the packet
               $from_code,    # The ICMP code of the packet
               $icmp_data,    # The data portion of the ICMP packet
               $local_port,   # The local port the packet is a reply to
               $end_time,     # The time the packet arrived
               $last_hop,     # Set to 1 if this packet came from the host
            );

         $end_time   = time;

         $from_saddr = recv($self->{'_icmp_socket'},$recv_msg,1500,0);
         if (defined $from_saddr)
         {
            ($from_port,$from_ip)   = sockaddr_in($from_saddr);
            $from_ip                = inet_ntoa($from_ip);
            $self->debug_print(1,"Received packet from $from_ip\n");
         }
         else
         {
            $self->debug_print(1,"No packet?\n");
            $nfound = 0;
            last;
         }

         $from_proto = unpack('C',substr($recv_msg,IP_PROTOCOL,1));

         if ($from_proto != getprotobyname('icmp'))
         {
            my $protoname = getprotobynumber($from_proto);
            $self->debug_print(1,"Packet not ICMP $from_proto($protoname)\n");
            last;
         }

         ($from_type,$from_code) = unpack('CC',substr($recv_msg,ICMP_TYPE,2));
         $icmp_data              = substr($recv_msg,ICMP_DATA);

         if (not $icmp_data)
         {
            $self->debug_print(1,
                  "No data in packet ($from_type,$from_code)\n");
            last;
         }

# TODO This code does not decode ICMP codes, only ICMP types, which can lead
# to false results if a router sends, for instance, a Network Unreachable 
# or Fragmentation Needed packet.
         if (  $from_type == ICMP_TYPE_TIMEEXCEED or
               $from_type == ICMP_TYPE_UNREACHABLE or
               ($self->protocol eq "icmp" and 
                  $from_type == ICMP_TYPE_ECHOREPLY) )
         {

            if ($self->protocol eq 'udp')
            {
               # The local port is used to verify the packet was sent from
               # This process.
               $local_port    = unpack('n',substr($icmp_data,UDP_SPORT,2));

               # The ID for UDP is the destination port number of the packet
               $from_id       = unpack('n',substr($icmp_data,UDP_DPORT,2));

               # The target system will send ICMP port unreachable, routers
               # along the path will send ICMP Time Exceeded messages.
               $last_hop      = ($from_type == ICMP_TYPE_UNREACHABLE) ? 1 : 0;
            }
            elsif ($self->protocol eq 'icmp')
            {
               if ($from_type == ICMP_TYPE_ECHOREPLY)
               {
                  # The ICMP ID is used to verify the packet was sent from
                  # this process.
                  my $icmp_id = unpack('n',substr($recv_msg,ICMP_ID,2));
                  last unless ($icmp_id == $$);

                  my $seq     = unpack('n',substr($recv_msg,ICMP_SEQ,2));
                  $from_id    = $seq; # The ID for ICMP is the seq number
                  $last_hop   = 1;;
               }
               else
               {
                  # The ICMP ID is used to verify the packet was sent from
                  # this process.
                  my $icmp_id = unpack('n',substr($icmp_data,ICMP_ID,2));
                  last unless ($icmp_id == $$);

                  my $ptype   = unpack('C',substr($icmp_data,ICMP_TYPE,1));
                  my $pseq    = unpack('n',substr($icmp_data,ICMP_SEQ,2));
                  if ($ptype eq ICMP_TYPE_ECHO)
                  {
                     $from_id = $pseq; # The ID for ICMP is the seq number
                  }
               }
            }
         }

         # If we got and decoded the packet to get an ID, process it.
         if ($from_ip and $from_id)
         {
            my $id = $pktids{$from_id};
            if (not $id)
            {
               $self->debug_print(1,"No packet sent matches the reply\n");
               last;
            }
            if (not exists $packets{$id})
            {
               $self->debug_print(1,"Packet $id received after ID deleted");
               last;
            }
            if ($packets{$id}{'id'} == $from_id)
            {
               last if ($self->protocol eq 'udp' and 
                     $packets{$id}{'localport'} != $local_port);

               my $total_time = $end_time - $packets{$id}{'starttime'};
               my $hop        = $packets{$id}{'hop'};
               my $query      = $packets{$id}{'query'};

               if (not $endhop or $hop <= $endhop)
               {
                  $self->debug_print(1,"Recieved response for $hop $query\n");
                  $self->_add_hop_query($hop, $query+1, TRACEROUTE_OK, 
                        $from_ip, sprintf("%.2f", 1000 * $total_time) );

                  # Sometimes a route will change and last_hop won't be set
                  # causing the traceroute to hang. Therefore if hop = endhop
                  # we set $end to the number of query responses for the
                  # hop recieved so far.

                  if ($last_hop or ($endhop and $hop == $endhop))
                  {
                     $end     = $self->hop_queries($hop);
                     $endhop  = $hop;
                  }
               }

               # No longer waiting for this packet
               delete $packets{$id};
            }
         }
         # Check if more data is waiting to be read, if so keep reading
         $nfound  = select((my $rout = $rbits),undef,undef,0);
      }

      # Process timed out packets
      if (keys %packets and $nexttimeout < time)
      {
         undef $nexttimeout;
         
         foreach my $id (sort keys %packets)
         {
            my $hop        = $packets{$id}{'hop'};
         
            if ($packets{$id}{'timeout'} < time)
            {
               my $query      = $packets{$id}{'query'};

               $self->debug_print(1,"Timeout for $hop $query\n");
               $self->_add_hop_query($hop, $query+1, TRACEROUTE_TIMEOUT, 
                     "", 0 );

               if ($endhop and $hop == $endhop)
               {
                  # Sometimes a route will change and last_hop won't be set
                  # causing the traceroute to hang. Therefore if hop = endhop
                  # we set $end to the number of query responses for the
                  # hop recieved so far.

                  $end = $self->hop_queries($hop);
               }
               
               # No longer waiting for this packet
               delete $packets{$id};
            }
            elsif (not defined $nexttimeout)
            {
               # Reset next timeout to the next packet
               $nexttimeout = $packets{$id}{'timeout'};
               last;
            }
         }
      }

      # Check if it is time to stop the looping
      if ($currenthop > $self->max_ttl and not keys %packets)
      {
         $self->debug_print(1,"No more packets, reached max_ttl\n");
         $stop = 1;
      }
      elsif ($end >= $self->queries)
      {
         # Delete packets for hops after $endhop
         foreach my $id (sort keys %packets)
         {
            my $hop        = $packets{$id}{'hop'};
            if (not $hop or ( $endhop and $hop > $endhop) )
            {
               # No longer care about this packet
               delete $packets{$id};
            }
         }
         if (not keys %packets)
         {
            $self->debug_print(1,"Reached host on $endhop hop\n");
            $end  = 1;
            $stop = 1;
         }
      }

      # Looping
   }

   return $end;
}

# _create_tracert_socket (private method)
# Reuses the ICMP socket already created for icmp traceroutes, or creates a 
# new socket. It then binds the socket to the user defined device and/or 
# source address if provided and returns the created socket.
sub _create_tracert_socket
{
   my $self = shift;
   my $socket;
   
   if ($self->protocol eq "icmp")
   {
      $socket = $self->{'_icmp_socket'};
   }
   elsif ($self->protocol eq "udp")
   {
      $socket     = FileHandle->new();
      
      socket($socket, PF_INET, SOCK_DGRAM, getprotobyname('udp')) or
         croak "UDP Socket creation error - $!";

      $self->debug_print(2,"Created UDP socket");
   }

   if ($self->device)
   {
      setsockopt($socket, SOL_SOCKET, SO_BINDTODEVICE, 
         pack('Z*', $self->device)) or 
            croak "error binding to ". $self->device ." - $!";

      $self->debug_print(2,"Bound socket to ". $self->device ."\n");
   }

   if ($self->source_address and $self->source_address ne '0.0.0.0')
   {
      $self->_bind($socket);
   }

   return $socket;
}

# _bind (private method)
# binds a sockets to a local address so all packets originate from that IP.
sub _bind
{
   my $self    = shift;
   my $socket  = shift;

   my $ip = inet_aton($self->source_address);

   croak "Nonexistant local address ". $self->source_address 
      unless (defined $ip);

   CORE::bind($socket, sockaddr_in(0,$ip)) or
      croak "Error binding to ".$self->source_address.", $!";

   $self->debug_print(2,"Bound socket to " . $self->source_address . "\n");

   return;
}

# _send_packet (private method)
# Sends the packet for $hop, $query to the destination. Actually calls 
# submethods for the different protocols which create and send the packet.
sub _send_packet
{
   my $self          = shift;
   my ($hop,$query)  = @_;

   if ($self->protocol eq "icmp")
   {
      # Sequence ID for the ICMP echo request
      my $seq = ($hop-1) * $self->queries + $query + 1;
      $self->_send_icmp_packet($seq,$hop);
      $self->{'_last_id'} = $seq;
   }
   elsif ($self->protocol eq "udp")
   {
      # Destination port for the UDP packet
      my $dport = $self->base_port + ($hop-1) * $self->queries + $query;
      $self->_send_udp_packet($dport,$hop);
      $self->{'_last_id'} = $dport;
   }

   return time;
}

# _send_icmp_packet (private method)
# Sends an ICMP packet with the given sequence number. The PID is used as
# the packet ID and $seq is the sequence number.
sub _send_icmp_packet
{
   my $self             = shift;
   my ($seq,$hop)       = @_;
   
   # Set TTL of socket to $hop.
   my $saddr            = $self->_connect(ICMP_PORT,$hop);
   my $data             = 'a' x ($self->packetlen - ICMP_DATA);

   my ($pkt, $chksum)   = (0,0);

   # Create packet twice, once without checksum, once with it
   foreach (1 .. 2)
   {
      $pkt     = pack('CC n3 A*',
                        ICMP_TYPE_ECHO,   # Type
                        ICMP_CODE_ECHO,   # Code
                        $chksum,          # Checksum
                        $$,               # ID (pid)
                        $seq,             # Sequence
                        $data,            # Data
                     );
      
      $chksum  = $self->_checksum($pkt) unless ($chksum);
   }

   send($self->{'_trace_socket'}, $pkt, 0, $saddr);

   return;
}

# _send_udp_packet (private method)
# Sends a udp packet to the given destination port.
sub _send_udp_packet
{
   my $self          = shift;
   my ($dport,$hop)  = @_;
   
   # Connect socket to destination port and set TTL
   my $saddr         = $self->_connect($dport,$hop);
   my $data          = 'a' x ($self->packetlen - UDP_DATA);

   $self->_connect($dport,$hop);

   send($self->{'_trace_socket'}, $data, 0);

   return;
}

# _connect (private method)
# Connects the socket unless the protocol is ICMP and sets the TTL.
sub _connect
{
   my $self          = shift;
   my ($port,$hop)   = @_;

   my $socket_addr   = sockaddr_in($port,$self->{_destination});
   
   if ($self->protocol eq 'udp')
   {
      CORE::connect($self->{'_trace_socket'},$socket_addr);
      $self->debug_print(2,"Connected to " . $self->host . "\n");
   }

   setsockopt($self->{'_trace_socket'}, IPPROTO_IP, IP_TTL, pack('C',$hop));
   $self->debug_print(2,"Set TTL to $hop\n");

   if ($self->protocol eq 'udp')
   {
      my $localaddr                    = getsockname($self->{'_trace_socket'});
      my ($lport,undef)                = sockaddr_in($localaddr);
      $self->{'_local_port'}           = $lport;
   }

   return ($self->protocol eq 'icmp') ? $socket_addr : undef;
}

# _checksum (private method)
# Lifted verbatum from Net::Ping 2.31
# Description:  Do a checksum on the message.  Basically sum all of
# the short words and fold the high order bits into the low order bits.
sub _checksum
{
   my $self = shift;
   my $msg = shift;

   my (  $len_msg,       # Length of the message
         $num_short,     # The number of short words in the message
         $short,         # One short word
         $chk            # The checksum
      );

   $len_msg    = length($msg);
   $num_short  = int($len_msg / 2);
   $chk        = 0;
   foreach $short (unpack("n$num_short", $msg))
   {
      $chk += $short;
   }                                           # Add the odd byte in
   $chk += (unpack("C", substr($msg, $len_msg - 1, 1)) << 8) if $len_msg % 2;
   $chk = ($chk >> 16) + ($chk & 0xffff);      # Fold high into low
   return(~(($chk >> 16) + $chk) & 0xffff);    # Again and complement
}

1;

__END__

=head1 NAME

Net::Traceroute:PurePerl - traceroute(1) functionality in perl via raw sockets

=head1 VERSION

This document describes version 0.10 of Net::Traceroute::PurePerl.

=head1 SYNOPSIS

    use Net::Traceroute::PurePerl;

    my $t = new Net::Traceroute::PurePerl(
         backend        => 'PurePerl', # this optional
         host           => 'www.openreach.com',
         debug          => 0,
         max_ttl        => 12,
         query_timeout  => 2,
         packetlen      => 40,
         protocol       => 'udp', # Or icmp
    );
    $t->traceroute;
    $t->pretty_print;


=head1 DESCRIPTION

This module implements traceroute(1) functionality for perl5.  
It allows you to trace the path IP packets take to a destination.  
It is implemented by using raw sockets to act just like the regular traceroute.

You must also be root to use the raw sockets.

=head1 INSTALLATION

=head2 Basic Installation

Net::Traceroute::PurePerl may be installed through the CPAN shell
in the usual CPAN shell manner. This typically is:
    
   $ perl -MCPAN -e 'install Net::Traceroute::PurePerl'

You can also read this README from the CPAN shell:

   $ perl -MCPAN -e shell
   cpan> readme Net::Traceroute::PurePerl

And you can install the module from the CPAN prompt as well:

   cpan> install Net::Traceroute::PurePerl

=head2 Manual Installation

Net::Traceroute::PurePerl can also be installed manually.
L<ftp://ftp-mirror.internap.com/pub/CPAN/authors/id/A/AH/AHOYING/> or a 
similarly named directory at your favorite CPAN mirror should hold the 
latest version.

Downloading and unpacking the distribution are left up to the reader.

To build and test it:

   perl Makefile.PL
   make
   make test

The test program, t/01_trace.t, makes an excellent sample program. It was
adapted from the code used to test and develop this module. There may be
additional sample programs in the examples folder.

When you are ready to install the module:

   make install

It should now be ready to use.

=head1 OVERVIEW

A new Net::Traceroute::PurePerl object must be created with the I<new> method.
This will not perform the traceroute immediately, unlike Net::Traceroute.
It will return a "template" object that can be used to set parameters for 
several subsequent traceroutes.

Methods are available for accessing information about a given
traceroute attempt.  There are also methods that view/modify the
options that are passed to the object's constructor.

To trace a route, UDP or ICMP packets are sent with a small TTL (time-to-live)
field in an attempt to get intervening routers to generate ICMP
TIME_EXCEEDED messages.

=head1 VERSION CHANGES

This version of Net::Traceroute::PurePerl is a complete rewrite of the internal
traceroute code used in the 0.02 release. As such a number of new capabilities
have been introduced, and probably a number of bugs as well.

The public methods have remained unchanged, and this should be a drop in
replacement for the older version.

This version no longer resolves router IPs to host names in the traceroute 
code. If you need the IP resolved you have to do it from your code, or use
the pretty_print method with a positive value passed as an argument.

The current version does not correctly detect network unreachable and
other nonstandard ICMP errors. This can lead to problems on networks where
these errors are sent instead of a port unreachable or ttl exceeded packet.

=head1 CONSTRUCTOR

    $obj = Net::Traceroute::PurePerl->new(
            [base_port        => $base_port,]
            [debug            => $debuglvl,]
            [max_ttl          => $max_ttl,]
            [host             => $host,]
            [queries          => $queries,]
            [query_timeout    => $query_timeout,]
            [source_address   => $srcaddr,]
            [packetlen        => $packetlen,]
            [concurrent_hops  => $concurrent,]
            [first_hop        => $first_hop,]
            [device           => $device,]
            [protocol         => $protocol,]
    );
            

This is the constructor for a new Net::Traceroute object.  
If given C<host>, it will NOT actually perform the traceroute.  
You MUST call the traceroute method later.

Possible options are:

B<host> - A host to traceroute to.  If you don't set this, you get a
Traceroute object with no traceroute data in it.  The module always
uses IP addresses internally and will attempt to lookup host names via
inet_aton.

B<base_port> - Base port number to use for the UDP queries.
Traceroute assumes that nothing is listening to port C<base_port> to
C<base_port + (nhops * nqueries - 1)>
where nhops is the number of hops required to reach the destination
address and nqueries is the number of queries per hop.  
Default is what the system traceroute uses (normally 33434)  
C<Traceroute>'s C<-p> option.

B<debuglvl> - A number indicating how verbose debug information should
be.  Please include debug=>9 output in bug reports.

B<max_ttl> - Maximum number of hops to try before giving up.  Default
is what the system traceroute uses (normally 30).  C<Traceroute>'s
C<-m> option.

B<queries> - Number of times to send a query for a given hop.
Defaults to whatever the system traceroute uses (3 for most
traceroutes).  C<Traceroute>'s C<-q> option.

B<query_timeout> - How many seconds to wait for a response to each
query sent.  Uses the system traceroute's default value of 5 if
unspecified.  C<Traceroute>'s C<-w> option.

B<timeout> - unused here

B<source_address> - Select the source address that traceroute will use.
C<Traceroute>'s C<-S> option.

B<packetlen> - Length of packets to use.  Traceroute tries to make the
IP packet exactly this long.

B<trace_program> - unused here

B<no_fragment> - unused at the moment

B<use_alarm> - unused in this version

B<protocol> - Either ICMP or UDP. ICMP uses ICMP echo packets with incrementing 
sequence numbers, while UDP uses USP packets with incrementing ports. It 
defaults to udp.

B<concurrent_hops> - This is the maximum number of outstanding packets sent
at one time. Setting this to a high number may overflow your socket receive
buffer and slightly delay the processing of response packets, making the
round trip time reported slightly higher, however it will significantly
decrease the amount of time it takes to run a traceroute. Defaults to 6.
 C<Traceroute>'s C<-N> option.

B<first_hop> - This is the lowest TTL to use. Setting this will skip the
first x routers in the path, especially useful if they never change. Defaults
to 1.  C<Traceroute>'s C<-f> option.

B<device> - The device to send the packet from. Normally this is determined
by the system's routing table, but it can be overridden. It defaults to undef.
 C<Traceroute>'s C<-I> option.

=head1 METHODS

=over 4

=item traceroute

Run the traceroute.  
Will fill in the rest of the object for informational queries.

The traceroute method is a blocking call. It will not return until the max_ttl
is reached or the host is reached. As such, if your program is time dependent
the call should be wrapped in an eval with an ALARM set.

  eval {
    local $SIG{ALRM} = sub { die "alarm" };
    alarm $timeout;
    $success = $t->traceroute();
    alarm 0;
  }
  warn "Traceroute timed out\n" if ($@ and $@ eq "alarm");

Returns 1 if the host was reached, or 0 if it wasn't.

=back

=head2 Controlling traceroute invocation

Each of these methods return the current value of the option specified
by the corresponding constructor option.  They will set the object's
instance variable to the given value if one is provided.

Changing an instance variable will only affect newly performed
traceroutes.  Setting a different value on a traceroute object that
has already performed a trace has no effect.

See the constructor documentation for information about methods that
aren't documented here.

=over 4

=item base_port([PORT])

=item max_ttl([PORT])

=item queries([QUERIES])

=item query_timeout([TIMEOUT])

=item host([HOST])

=item source_address([SRC])

=item packetlen([LEN])

=item use_alarm([0|1])

=item protocl([PROTOCOL])

=item concurrent_hops([CONCURRENT])

=item first_hop([FIRST_HOP])

=item device([DEVICE])

=back

=head2 Obtaining information about a Trace

These methods return information about a traceroute that has already
been performed.

Any of the methods in this section that return a count of something or
want an I<N>th type count to identify something employ one based
counting.

=over 4

=item pretty_print

Prints to stdout a traceroute-like text. Tries to mimic traceroute(1)'s
output as close as possible with a few exceptions.  First, the columns are
easier to read, and second, a new line is started if the host IP changes
instead of printing the new IP inline. The first column stays the same hop 
number, only the host changes.

Passing in an argument of 1 will make pretty_print resolve the names of the
router ips, otherwise they are printed as raw ip addresses, like 
C<Traceroute>'s C<-n> option.

=item stat

Returns the status of a given traceroute object.  One of
TRACEROUTE_OK, TRACEROUTE_TIMEOUT, or TRACEROUTE_UNKNOWN (each defined
as an integer).  TRACEROUTE_OK will only be returned if the host was
actually reachable.

=item found

Returns 1 if the host was found, undef otherwise.

=item pathmtu

If your traceroute supports MTU discovery, this method will return the
MTU in some circumstances.  You must set no_fragment, and must use a
packetlen larger than the path mtu for this to be set.

NOTE: This doesn't work with this version.

=item hops

Returns the number of hops that it took to reach the host.

=item hop_queries(HOP)

Returns the number of queries that were sent for a given hop.  This
should normally be the same for every query.

=item hop_query_stat(HOP, QUERY)

Return the status of the given HOP's QUERY.  The return status can be
one of the following (each of these is actually an integer constant
function defined in Net::Traceroute's export list):

QUERY can be zero, in which case the first succesful query will be
returned.

=over 4

=item TRACEROUTE_OK

Reached the host, no problems.

=item TRACEROUTE_TIMEOUT

This query timed out.

=item TRACEROUTE_UNKNOWN

Your guess is as good as mine.  Shouldn't happen too often.

=item TRACEROUTE_UNREACH_NET

This hop returned an ICMP Network Unreachable.

=item TRACEROUTE_UNREACH_HOST

This hop returned an ICMP Host Unreachable.

=item TRACEROUTE_UNREACH_PROTO

This hop returned an ICMP Protocol unreachable.

=item TRACEROUTE_UNREACH_NEEDFRAG

Indicates that you can't reach this host without fragmenting your
packet further.  Shouldn't happen in regular use.

=item TRACEROUTE_UNREACH_SRCFAIL

A source routed packet was rejected for some reason.  Shouldn't happen.

=item TRACEROUTE_UNREACH_FILTER_PROHIB

A firewall or similar device has decreed that your traffic is
disallowed by administrative action.  Suspect sheer, raving paranoia.

=item TRACEROUTE_BSDBUG

The destination machine appears to exhibit the 4.[23]BSD time exceeded
bug.

=back

=item hop_query_host(HOP, QUERY)

Return the dotted quad IP address of the host that responded to HOP's
QUERY.

QUERY can be zero, in which case the first succesful query will be
returned.

=item hop_query_time(HOP, QUERY)

Return the round trip time associated with the given HOP's query.  If
your system's traceroute supports fractional second timing, so
will Net::Traceroute.

QUERY can be zero, in which case the first succesful query will be
returned.

=back

=head1 BUGS and LIMITATIONS

I have not tested the cloning functions of Net::Traceroute::PurePerl.
It ought to work, but if not, BUG me.

This module requires root or administrative privileges to run. It opens a raw 
socket to listen for TTL exceeded messages. Take appropriate precautions.

Windows only supports ICMP traceroutes. This may change in a future release,
but it is a real pain since Windows doesn't send ICMP error messages to 
applications for other protocols unless the socket is in promiscous mode. :(

The current version does not correctly detect network unreachable and
other nonstandard ICMP errors. This can lead to problems on networks where
these errors are sent instead of a port unreachable or ttl exceeded packet.

The current version does not support Net::Traceroute's clone method.
Calling clone will create an object that is unusable at this point.

=head1 TODO

=over 2

=item *

Implement IPv6 capability.

=item *

Implement TCP traceroute.

=item *

Fix bugs listed above.

=back

=head1 SEE ALSO

traceroute(1)

This module's traceroute code was heavily influenced by C<Net::Ping>.

See the examples folder and the test programs for more examples of this module
in action.

=head1 AUTHOR

Tom Scanlan <tscanlan@openreach.com> owner Net::Traceroute::PurePerl

Andrew Hoying <ahoying@cpan.org> current co-maintainer of 
Net::Traceroute::PurePerl. Any bugs in this release are mine, please send me
the bug reports.

Daniel Hagerty <hag@ai.mit.edu> owner of Net::Traceroute and input on this fella

=head1 COPYRIGHT

Go right ahead and copy it.  2002 Tom Scanlan. Copyright 2006 by Andrew Hoying.
Don't blame me for damages, just the bugs.

Net::Traceroute::PurePerl is free software; you may redistribute it and or modify it under the same terms as Perl itself.

=cut
