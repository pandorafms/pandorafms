#Time-Format Perl Module
#
%define name        perl-time-format
%define version	    1.11
Summary:            Easy-to-use date/time formatting.
Name:               %{name}
Version:            %{version}
Release:            0
License:            Other License(s), see package
Vendor:             Eric J. Roode, <roode@cpan.org>
Source0:            %{name}-%{version}.tar.bz2
URL:                http://search.cpan.org/~roode/Time-Format-1.11/
Group:              Development/Libraries/Perl
Packager:           Pablo de la Concepcion <pablo@artica.es>
Prefix:             /usr/share
BuildRoot:          %{_tmppath}/%{name}-%{version}-build
BuildArch: 	    noarch
Requires:	    perl
AutoReq:            1
Provides:           %{name}-%{version}

%description
Time::Format provides a very easy way to format dates and times.  The
formatting functions are tied to hash variables, so they can be used
inside strings as well as in ordinary expressions.  The formatting
codes used are meant to be easy to remember, use, and read.  They
follow a simple, consistent pattern.  If I've done my job right, once
you learn the codes, you should never have to refer to the
documentation again.

AUTHOR / COPYRIGHT

Copyright (c) 2003-2009 by Eric J. Roode, ROODE I<-at-> cpan I<-dot-> org

All rights reserved.

To avoid my spam filter, please include "Perl", "module", or this
module's name in the message's subject line, and/or GPG-sign your
message.

This module is copyrighted only to ensure proper attribution of
authorship and to ensure that it remains available to all.  This
module is free, open-source software.  This module may be freely used
for any purpose, commercial, public, or private, provided that proper
credit is given, and that no more-restrictive license is applied to
derivative (not dependent) works.

Substantial efforts have been made to ensure that this software meets
high quality standards; however, no guarantee can be made that there
are no undiscovered bugs, and no warranty is made as to suitability to
any given use, including merchantability.  Should this module cause
your house to burn down, your dog to collapse, your heart-lung machine
to fail, your spouse to desert you, or George Bush to be re-elected, I
can offer only my sincere sympathy and apologies, and promise to
endeavor to improve the software.

%prep
rm -rf $RPM_BUILD_ROOT

# Unconpress quietly (-q) 
%setup -q 

%build
perl Makefile.PL -noxs
make
make test

%install
%perl_make_install
%perl_process_packlist

%clean
rm -rf $RPM_BUILD_ROOT

%files
%defattr(-, root, root)
%doc Changes README MANIFEST
%{perl_vendorlib}/Time/Format.pm

%{perl_vendorarch}/auto/Time/Format/.packlist

%doc %{_mandir}/man3/*
/var/adm/perl-modules/%{name}
