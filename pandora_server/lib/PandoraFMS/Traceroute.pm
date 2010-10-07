# Traceroute.pm library
# Repackaged by Pandora FMS 
# 
###
# Copyright 1998, 1999 Massachusetts Institute of Technology
# Copyright 2000-2005 Daniel Hagerty
#
# Permission to use, copy, modify, distribute, and sell this software and its
# documentation for any purpose is hereby granted without fee, provided that
# the above copyright notice appear in all copies and that both that
# copyright notice and this permission notice appear in supporting
# documentation, and that the name of M.I.T. not be used in advertising or
# publicity pertaining to distribution of the software without specific,
# written prior permission.  M.I.T. makes no representations about the
# suitability of this software for any purpose.  It is provided "as is"
# without express or implied warranty.

###
# File:		traceroute.pm
# Author:	Daniel Hagerty, hag@ai.mit.edu
# Date:		Tue Mar 17 13:44:00 1998
# Description:  Perl traceroute module for performing traceroute(1)
#		functionality.
#
# $Id: Traceroute.pm,v 1.25 2007/01/10 02:30:13 hag Exp $

# Currently attempts to parse the output of the system traceroute command,
# which it expects will behave like the standard LBL traceroute program.
# If it doesn't, (Windows, HPUX come to mind) you lose.
#

# Could eventually be broken into several classes that know how to
# deal with various traceroutes; could attempt to auto-recognize the
# particular traceroute and parse it.
#
# Has a couple of random useful hooks for child classes to override.

package PandoraFMS::Traceroute;

use strict;
no strict qw(subs);

#require 5.xxx;			# We'll probably need this

use vars qw(@EXPORT $VERSION @ISA);

use Exporter;
use IO::Pipe;
use IO::Select;
use Socket;
use Symbol qw(qualify_to_ref);
use Time::HiRes qw(time);
use Errno qw(EAGAIN EINTR);
use Data::Dumper;		# Debugging

$VERSION = "1.10";		# Version number is only incremented by
				# hand.

@ISA = qw(Exporter);

@EXPORT = qw(
	     TRACEROUTE_OK
	     TRACEROUTE_TIMEOUT
	     TRACEROUTE_UNKNOWN
	     TRACEROUTE_BSDBUG
	     TRACEROUTE_UNREACH_NET
	     TRACEROUTE_UNREACH_HOST
	     TRACEROUTE_UNREACH_PROTO
	     TRACEROUTE_UNREACH_NEEDFRAG
	     TRACEROUTE_UNREACH_SRCFAIL
	     TRACEROUTE_UNREACH_FILTER_PROHIB
	     TRACEROUTE_UNREACH_ADDR
	     );

###

## Exported functions.

# Perl's facist mode gets very grumbly if a few things aren't declared
# first.

sub TRACEROUTE_OK { 0 }
sub TRACEROUTE_TIMEOUT { 1 }
sub TRACEROUTE_UNKNOWN { 2 }
sub TRACEROUTE_BSDBUG { 3 }
sub TRACEROUTE_UNREACH_NET { 4 }
sub TRACEROUTE_UNREACH_HOST { 5 }
sub TRACEROUTE_UNREACH_PROTO { 6 }
sub TRACEROUTE_UNREACH_NEEDFRAG { 7 }
sub TRACEROUTE_UNREACH_SRCFAIL { 8 }
sub TRACEROUTE_UNREACH_FILTER_PROHIB { 9 }
sub TRACEROUTE_UNREACH_ADDR { 10 }

## Internal data used throughout the module

# Instance variables that are nothing special, and have an obvious
# corresponding accessor/mutator method.
my @public_instance_vars =
    qw(
       base_port
       debug
       host
       max_ttl
       packetlen
       queries
       query_timeout
       source_address
       trace_program
       timeout
       no_fragment
       use_icmp
       );

my @simple_instance_vars = (
			    qw(
			       pathmtu
			       stat
			       ),
			    @public_instance_vars,
			    );

# Field offsets for query info array
use constant query_stat_offset => 0;
use constant query_host_offset => 1;
use constant query_time_offset => 2;

###
# Public methods

# Constructor

sub new ($;%) {
    my $self = shift;
    my $type = ref($self) || $self;

    my %arg = @_;

    # We implement a goofy UI so that all programmers can use
    # Net::Traceroute as a constructor for all types of object.
    if(exists($arg{backend})) {
	my $backend = $arg{backend};
	if($backend ne "Parser") {
	    my $module = "Net::Traceroute::$backend";
	    eval "require $module";

	    # Ignore error on the possibility that they just defined
	    # the module at runtime, rather than an actual module in
	    # the filesystem.
	    my $newref = qualify_to_ref("new", $module);
	    my $newcode = *{$newref}{CODE};
	    if(!defined($newcode)) {
		die "Backend implementation $backend has no new";
	    }
	    return(&{$newcode}($module, @_));
	}
    }

    if(!ref($self)) {
	$self = bless {}, $type;
    }

    $self->init(%arg);
    $self;
}

sub init {
    my $self = shift;
    my %arg = @_;

    # Take our constructer arguments and initialize the attributes with
    # them.
    my $var;
    foreach $var (@public_instance_vars)  {
	if(defined($arg{$var})) {
	    $self->$var($arg{$var});
	}
    }

    # Initialize debug if it isn't already.
    $self->debug(0) if(!defined($self->debug));
    $self->trace_program("traceroute") if(!defined($self->trace_program));

    $self->debug_print(1, "Running in debug mode\n");

    # Initialize status
    $self->stat(TRACEROUTE_UNKNOWN);

    if(defined($self->host)) {
	$self->traceroute;
    }

    $self->debug_print(9, Dumper($self));
}

sub clone ($;%) {
    my $self = shift;
    my $type = ref($self);

    my %arg = @_;

    die "Can't clone a non-object!" unless($type);

    my $clone = bless {}, $type;

    # Does a shallow copy of the hash key/values to the new hash.
    if(ref($self)) {
	my($key, $val);
	while(($key, $val) = each %{$self}) {
	    $clone->{$key} = $val;
	}
    }

    # Take our constructer arguments and initialize the attributes with
    # them.
    my $var;
    foreach $var (@public_instance_vars)  {
	if(defined($arg{$var})) {
	    $clone->$var($arg{$var});
	}
    }

    # Initialize status
    $clone->stat(TRACEROUTE_UNKNOWN);

    if(defined($clone->host)) {
	$clone->traceroute;
    }

    $clone->debug_print(9, Dumper($clone));

    return($clone);
}

##
# Methods

# Do the actual work.  Not really a published interface; completely
# useable from the constructor.
sub traceroute ($) {
    my $self = shift;
    my $host = $self->host();

    $self->debug_print(1, "Performing traceroute\n");

    die "No host provided!" unless $host;

    # Sit in a select loop on the incoming text from traceroute,
    # waiting for a timeout if we need to.  Accumulate the text for
    # parsing later in one fell swoop.

    # Note time.  Time::HiRes will give us floating point.
    my $start_time;
    my $end_time;
    my $total_wait = $self->timeout();
    my @this_wait;
    if(defined($total_wait)) {
	$start_time = time();
	push(@this_wait, $total_wait);
	$end_time = $start_time + $total_wait;
    }

    my $tr_pipe = $self->_make_pipe();
    my $select = new IO::Select($tr_pipe);

    $self->_zero_text_accumulator();
    $self->_zero_hops();

    my @ready;
  out:
    while( @ready = $select->can_read(@this_wait)) {
	my $fh;
	foreach $fh (@ready) {
	    my $buf;
	    my $len = $fh->sysread($buf, 2048);

	    # XXX Linux is fond of returning EAGAIN, which we'll need
	    # to check for here.  Still true for sysread?
	    if(!defined($len)) {
		my $errno = int($!);
		next out if(($errno == EAGAIN) || ($errno == EINTR));
		die "read error: $!";
	    }
	    last out if(!$len);	# EOF

	    $self->_text_accumulator($self->_text_accumulator() . $buf);
	}

	# Adjust select timer if we need to.
	if(defined($total_wait)) {
	    my $now = time();
	    last out if($now >= $end_time);
	    $this_wait[0] = $end_time - $now;
	}
    }
    if(defined($total_wait)) {
	my $now = time();
	$self->stat(TRACEROUTE_TIMEOUT)	if($now >= $end_time);
    }

    $tr_pipe->close();

    my $accum = $self->_text_accumulator();
    die "No output from traceroute.  Exec failure?" if($accum eq "");

    # Do the grunt parsing work
    $self->_parse($accum);

    # XXX are you really sure you want to do it like this??
    if($self->stat() != TRACEROUTE_TIMEOUT) {
	$self->stat(TRACEROUTE_OK);
    }

    $self;
}

##
# Hop and query functions

sub hops ($) {
    my $self = shift;

    my $hop_ary = $self->{"hops"};

    return() unless $hop_ary;

    return(int(@{$hop_ary}));
}

sub hop_queries ($$) {
    my $self = shift;
    my $hop = (shift) - 1;

    $self->{"hops"} && $self->{"hops"}->[$hop] &&
	int(@{$self->{"hops"}->[$hop]});
}

sub found ($) {
    my $self = shift;
    my $hops = $self->hops();

    if($hops) {
	my $last_hop = $self->hop_query_host($hops, 0);
	my $stat = $self->hop_query_stat($hops,  0);

	# Is this the correct thing to be doing?  This gap in
	# semantics missed me, and wasn't caught until post 1.5 It
	# would be a good to audit the semantics here.  It's possible
	# that a prior version change broke this.

	# Getting good regression tests would be nice, but traceroute
	# is an annoying thing to do regression on -- you usually
	# don't have enough control over the network.  If I was good,
	# I would be collecting my bug reports, and saving the
	# traceroute output produced there.
	return(undef) if(!defined($last_hop));

	# Ugh, what to do here?
	# In IPv4, a host may send the port-unreachable ICMP from an
	# address other than the one we sent to. (and in fact, I use
	# this feature quite a bit to map out networks)
	# IIRC, IPv6 mandates that the unreachable comes from the address we
	# sent to, so we don't have this problem.

	# This assumption will that any last hop answer that wasn't an
	# error may bite us.
	if(
	   (($stat == TRACEROUTE_OK) || ($stat == TRACEROUTE_BSDBUG) ||
	    ($stat == TRACEROUTE_UNREACH_PROTO))) {
	    return(1);
	}
    }
    return(undef);
}

sub hop_query_stat ($$) {
    _query_accessor_common(@_,query_stat_offset);
}

sub hop_query_host ($$) {
    _query_accessor_common(@_,query_host_offset);
}

sub hop_query_time ($$) {
    _query_accessor_common(@_,query_time_offset);
}

##
# Accesssor/mutators for ordinary instance variables.  (Read/Write)
# We generate these.

foreach my $name (@simple_instance_vars) {
    my $sym = qualify_to_ref($name);
    my $code = sub {
	my $self = shift;
	my $old = $self->{$name};
	$self->{$name} = $_[0] if @_;
	return $old;
    };
    *{$sym} = $code;
}

###
# Various internal methods

# Many of these would be useful to override in a derived class.

# Build and return the pipe that talks to our child traceroute.
sub _make_pipe ($) {
    my $self = shift;

    my @tr_args;

    push(@tr_args, $self->trace_program());
    push(@tr_args, $self->_tr_cmd_args());
    push(@tr_args, $self->host());
    my @plen = ($self->packetlen) || (); # Sigh.
    push(@tr_args, @plen);

    # XXX we probably shouldn't throw stderr away.
    # XXX we probably shouldn't use named filehandles.
    open(SAVESTDERR, ">&STDERR");
    open(STDERR, ">/dev/null");

    my $pipe = new IO::Pipe;

    # IO::Pipe is very unhelpful about error catching.  It calls die
    # in the child program, but returns a reasonable looking object in
    # the parent.  This is really a standard unix fork/exec issue, but
    # the library doesn't help us.
    my $result = $pipe->reader(@tr_args);

    open(STDERR, ">& SAVESTDERR");
    close(SAVESTDERR);

    # Long standing bug; the pipe needs to be marked non blocking.
    $result->blocking(0);

    $result;
}

# Map some instance variables to command line arguments that take
# arguments.
my %cmdline_valuemap =
    ( "base_port" => "-p",
      "max_ttl" => "-m",
      "queries" => "-q",
      "query_timeout" => "-w",
      "source_address" => "-s",
      );

# Map more instance variables to command line arguments that are
# flags.
my %cmdline_flagmap =
    ( "no_fragment" => "-F",
      "use_icmp" => "-I",
      );

# Build a list of command line arguments
sub _tr_cmd_args ($) {
    my $self = shift;

    my @result;

    push(@result, "-n");

    my($key, $flag);

    while(($key, $flag) = each %cmdline_flagmap) {
	push(@result, $flag) if($self->$key());;
    }

    while(($key, $flag) = each %cmdline_valuemap) {
	my $val = $self->$key();
	if(defined $val) {
	    push(@result, $flag, $val);
	}
    }

    @result;
}

# Map !<Mumble> notation traceroute uses for various icmp packet types
# it may receive.
my %icmp_map = (N => TRACEROUTE_UNREACH_NET,
		H => TRACEROUTE_UNREACH_HOST,
		P => TRACEROUTE_UNREACH_PROTO,
		F => TRACEROUTE_UNREACH_NEEDFRAG,
		S => TRACEROUTE_UNREACH_SRCFAIL,
		A => TRACEROUTE_UNREACH_ADDR,
		X => TRACEROUTE_UNREACH_FILTER_PROHIB);

# Do the grunt work of parsing the output.
sub _parse ($$) {
    my $self = shift;
    my $tr_output = shift;

    # This is a crufty hand coded parser that does its job well
    # enough.  The approach of regular expressions without state is
    # far from perfect, but it gets the job done.
  line:
    foreach $_ (split(/\n/, $tr_output)) {

	# Some traceroutes appear to print informational line to stdout,
	# and we don't care.
	/^traceroute to / && next;

	# AIX 5L has to be different.
	/^trying to get / && next;
	/^source should be / && next;

	# NetBSD's traceroute emits info about path MTU discovery if
	# you want, don't know who else does this.
	/^message too big, trying new MTU = (\d+)/ && do {
	    $self->pathmtu($1);
	    next;
	};

	# For now, discard MPLS label stack information emitted by
	# some vendor's traceroutes.  Once I'm sure I'm sure I
	# understand the semantics offered by both the underlying MPLS
	# and whatever crazy limits the MPLS patch has, I can think
	# about an interface.  My reading of the code is that you will
	# get the label stack of the last query.  If this isn't
	# representative of all of the queries, it sucks to be you.
	# You can still get what you need, but it would be nice if the
	# tool didn't throw information away...
	# possibilities.
	/^\s+MPLS Label=(\d+) CoS=(\d) TTL=(\d+) S=(\d+)/ && next;

	# Each line starts with the hopno (space padded to two characters)
	# and a space.
	/^([0-9 ][0-9]) / || die "Unable to traceroute output: $_";
	my $hopno = $1 + 0;

	my $query = 1;
	my $addr;
	my $time;

	$_ = substr($_,length($&));

      query:
	while($_) {
	    # ip address of a response
	    /^ (\d+\.\d+\.\d+\.\d+)/ && do {
		$addr = $1;
		$_ = substr($_, length($&));
		next query;
	    };
	    # ipv6 address of a response
	    /^ ([0-9a-fA-F:]+)/ && do {
		$addr = $1;
		$_ = substr($_, length($&));
		next query;
	    };
	    # Redhat FC5 traceroute does this; it's redundant.
	    /^ \((\d+\.\d+\.\d+\.\d+)\)/ && do {
		$_ = substr($_, length($&));
		next query;
	    };
	    # round trip time of query
	    /^   ?([0-9.]+) ms/ && do {
		$time = $1 + 0;

		$self->_add_hop_query($hopno, $query,
				     TRACEROUTE_OK, $addr, $time);
		$query++;
		$_ = substr($_, length($&));
		next query;
	    };
	    # query timed out
	    /^ +\*/ && do {
		$self->_add_hop_query($hopno, $query,
				     TRACEROUTE_TIMEOUT,
				     inet_ntoa(INADDR_NONE), 0);
		$query++;
		$_ = substr($_, length($&));
		next query;
	    };

	    # extra information from the probe (random ICMP info
	    # and such).

	    # There was a bug in this regexp prior to 1.09; reorder
	    # the clauses and everything gets better.

	    # Note that this is actually a very subtle DWIM on perl's
	    # part: in "pure" regular expression theory, order of
	    # expression doesn't matter; the resultant DFA has no
	    # order concept.  Without perl DWIMing on our regexp, we'd
	    # write the regexp and code to perform a token lookahead:
	    # the transitions after ! would be < for digits, the keys
	    # of icmp map, and finally whitespace or end of string
	    # indicate a lone "!".

	    /^ (!<\d+>|![NHPFSAX]?)/ && do {
		my $flag = $1;
		my $matchlen = length($&);

		# Flip the counter back one;  this flag only appears
		# optionally and by now we've already incremented the
		# query counter.
		my $query = $query - 1;

		if($flag =~ /^!<\d>$/) {
		    $self->_change_hop_query_stat($hopno, $query,
						 TRACEROUTE_UNKNOWN);
		} elsif($flag =~ /^!$/) {
		    $self->_change_hop_query_stat($hopno, $query,
						 TRACEROUTE_BSDBUG);
		} elsif($flag =~ /^!([NHPFSAX])$/) {
		    my $icmp = $1;

		    # Shouldn't happen
		    die "Unable to traceroute output (flag $icmp)!"
			unless(defined($icmp_map{$icmp}));

		    $self->_change_hop_query_stat($hopno, $query,
						 $icmp_map{$icmp});
		}
		$_ = substr($_, $matchlen);
		next query;
	    };
	    # Nothing left, next line.
	    /^$/ && do {
		next line;
	    };
	    # Some LBL derived traceroutes print ttl stuff
	    /^ \(ttl ?= ?\d+!\)/ && do {
		$_ = substr($_, length($&));
		next query;
	    };

	    die "Unable to parse traceroute output: $_";
	}
    }
}

# I don't understand why this one won't work with the accessor generator.
sub _text_accumulator ($;$) {
    my $self = shift;
    my $name = "_text_accumulator";
    my $old = $self->{$name};
    $self->{$name} = $_[0] if @_;
    return $old;
}

sub _zero_text_accumulator ($) {
    my $self = shift;
    my $elem = "_text_accumulator";

    $self->{$elem} = "";
}

# Hop stuff
sub _zero_hops ($) {
    my $self = shift;

    delete $self->{"hops"};
}

sub _add_hop_query ($$$$$$) {
    my $self = shift;

    my $hop = (shift) - 1;
    my $query = (shift) - 1;

    my $stat = shift;
    my $host = shift;
    my $time = shift;

    $self->{"hops"}->[$hop]->[$query] = [ $stat, $host, $time ];
}

sub _change_hop_query_stat ($$$$) {
    my $self = shift;

    # Zero base these
    my $hop = (shift) - 1;
    my $query = (shift) - 1;

    my $stat = shift;

    $self->{"hops"}->[$hop]->[$query]->[ query_stat_offset ] = $stat;
}

sub _query_accessor_common ($$$) {
    my $self = shift;

    # Zero base these
    my $hop = (shift) - 1;
    my $query = (shift) - 1;

    my $which_one = shift;

    # Deal with wildcard
    if($query == -1) {
	my $query_stat;

	my $aref;
      query:
	foreach $aref (@{$self->{"hops"}->[$hop]}) {
	    $query_stat = $aref->[query_stat_offset];
	    $query_stat == TRACEROUTE_TIMEOUT && do { next query };
	    $query_stat == TRACEROUTE_UNKNOWN && do { next query };
	    do { return $aref->[$which_one] };
	}
	return undef;
    } else {
	$self->{"hops"}->[$hop]->[$query]->[$which_one];
    }
}

sub debug_print ($$$;@) {
    my $self = shift;
    my $level = shift;
    my $fmtstring = shift;

    return unless $self->debug() >= $level;

    my($package, $filename, $line, $subroutine,
       $hasargs, $wantarray, $evaltext, $is_require) = caller(0);

    my $caller_line = $line;
    my $caller_name = $subroutine;
    my $caller_file = $filename;

    my $string = sprintf($fmtstring, @_);

    my $caller = "${caller_file}:${caller_name}:${caller_line}";

    print STDERR "$caller: $string";
}

1;

__END__

=head1 NAME

Net::Traceroute - traceroute(1) functionality in perl

=head1 SYNOPSIS

    use Net::Traceroute;
    $tr = Net::Traceroute->new(host=> "life.ai.mit.edu");
    if($tr->found) {
	my $hops = $tr->hops;
	if($hops > 1) {
	    print "Router was " .
		$tr->hop_query_host($tr->hops - 1, 0) . "\n";
	}
    }

=head1 DESCRIPTION

This module implements traceroute(1) functionality for perl5.  It
allows you to trace the path IP packets take to a destination.  It is
currently implemented as a parser around the system traceroute
command.

=head1 OVERVIEW

A new Net::Traceroute object must be created with the I<new> method.
Depending on exactly how the constructor is invoked, it may perform
the traceroute immediately, or it may return a "template" object that
can be used to set parameters for several subsequent traceroutes.

Methods are available for accessing information about a given
traceroute attempt.  There are also methods that view/modify the
options that are passed to the object's constructor.

To trace a route, UDP packets are sent with a small TTL (time-to-live)
field in an attempt to get intervening routers to generate ICMP
TIME_EXCEEDED messages.

=head1 CONSTRUCTOR AND CLONING

    $obj = Net::Traceroute->new([base_port	=> $base_port,]
				[debug		=> $debuglvl,]
				[max_ttl	=> $max_ttl,]
				[host		=> $host,]
				[queries	=> $queries,]
				[query_timeout	=> $query_timeout,]
				[timeout	=> $timeout,]
				[source_address	=> $srcaddr,]
				[packetlen	=> $packetlen,]
				[trace_program	=> $program,]
				[no_fragment	=> $nofrag,]
				[use_icmp	=> $useicmp,]);
    $frob = $obj->clone([options]);

This is the constructor for a new Net::Traceroute object.  If given
C<host>, it will actually perform the traceroute.  You can call the
traceroute method later.

Given an existing Net::Traceroute object $obj as a template, you can
call $obj->clone() with the usual constructor parameters.  The same
rules apply about defining host; that is, traceroute will be run if it
is defined.  You can always pass host => undef to clone.

Possible options are:

B<host> - A host to traceroute to.  If you don't set this, you get a
Traceroute object with no traceroute data in it.  The module always
uses IP addresses internally and will attempt to lookup host names via
inet_aton.

B<base_port> - Base port number to use for the UDP queries.
Traceroute assumes that nothing is listening to port C<base_port> to
C<base_port + (nhops - 1)>
where nhops is the number of hops required to reach the destination
address.  Default is what the system traceroute uses (normally 33434).
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

B<timeout> - Maximum time, in seconds, to wait for the traceroute to
complete.  If not specified, the traceroute will not return until the
host has been reached, or traceroute counts to infinity (C<max_ttl> *
C<queries> * C<query_timeout>).  Note that this option is implemented
by Net::Traceroute, not the underlying traceroute command.

B<source_address> - Select the source address that traceroute wil use.

B<packetlen> - Length of packets to use.  Traceroute tries to make the
IP packet exactly this long.

B<trace_program> - Name of the traceroute program.  Defaults to traceroute.
You can pass traceroute6 to do IPv6 traceroutes.

B<no_fragment> - Set the IP don't fragment bit.  Some traceroute
programs will perform path mtu discovery with this option.

B<use_icmp> - Request that traceroute perform probes with ICMP echo
packets, rather than UDP.

=head1 METHODS

=over 4

=item traceroute

Run system traceroute, and parse the results.  Will fill in the rest
of the object for informational queries.

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

=item timeout([TIMEOUT])

=item source_address([SRC])

=item packetlen([LEN])

=item trace_program([PROGRAM])

=item no_fragment([PROGRAM])

=back

=head2 Obtaining information about a Trace

These methods return information about a traceroute that has already
been performed.

Any of the methods in this section that return a count of something or
want an I<N>th type count to identify something employ one based
counting.

=over 4

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

=head1 CLONING SUPPORT BEFORE 1.04

Net::Traceroute Versions before 1.04 used new to clone objects.  This
has been deprecated in favor of the clone() method.

If you have code of the form:

 my $template = Net::Traceroute->new();
 my $tr = $template->new(host => "localhost");

You need to change the $template->new to $template->clone.

This behavior was changed because it interfered with subclassing.

=head1 BUGS

Net::Traceroute parses the output of the system traceroute command.
As such, it may not work on your system.  Support for more traceroute
outputs (e.g. Windows, HPUX) could be done, although currently the
code assumes there is "One true traceroute".

The actual functionality of traceroute could also be implemented
natively in perl or linked in from a C library.

Versions prior to 1.04 had some interface issues for subclassing.
These issues have been addressed, but required a public interface
change.  If you were relying on the behavior of new to clone existing
objects, your code needs to be fixed.

There are some suspected issues in how timeout is handled.  I haven't
had time to address this yet.

=head1 SEE ALSO

traceroute(1)

=head1 AUTHOR

Daniel Hagerty <hag@ai.mit.edu>

=head1 COPYRIGHT

Copyright 1998, 1999 Massachusetts Institute of Technology
Copyright 2000, 2001 Daniel Hagerty

Permission to use, copy, modify, distribute, and sell this software
and its documentation for any purpose is hereby granted without fee,
provided that the above copyright notice appear in all copies and that
both that copyright notice and this permission notice appear in
supporting documentation, and that the name of M.I.T. not be used in
advertising or publicity pertaining to distribution of the software
without specific, written prior permission.  M.I.T. makes no
representations about the suitability of this software for any
purpose.  It is provided "as is" without express or implied warranty.

=cut
