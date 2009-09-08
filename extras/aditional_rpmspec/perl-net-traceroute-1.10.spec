#Net-Traceroute Perl Module
#
%define name        perl-net-traceroute
%define version	    1.10
Summary:            Net::Traceroute   	 traceroute(1) functionality in perl  
Name:               %{name}
Version:            %{version}
Release:            0
License:            Other License(s), see package
Vendor:             Daniel Hagerty <hag@ai.mit.edu>
Source0:            %{name}-%{version}.tar.bz2
URL:                http://search.cpan.org/~hag/Net-Traceroute-1.10/
Group:              Development/Libraries/Perl
Packager:           Pablo de la Concepcion <pablo@artica.es>
Prefix:             /usr/share
BuildRoot:          %{_tmppath}/%{name}-%{version}-build
BuildArch: 	    noarch
Requires:	    perl
AutoReq:            1
Provides:           %{name}-%{version}

%description

Currently attempts to parse the output of the system traceroute command, which it expects will behave like the standard LBL traceroute program. If it doesn't, (Windows, HPUX come to mind) you lose.

Could eventually be broken into several classes that know how to deal with various traceroutes; could attempt to auto-recognize the particular traceroute and parse it.

Has a couple of random useful hooks for child classes to override.

LICENCE:

Copyright 1998, 1999 Massachusetts Institute of Technology
Copyright 2000-2005 Daniel Hagerty

Permission to use, copy, modify, distribute, and sell this software and its
documentation for any purpose is hereby granted without fee, provided that
the above copyright notice appear in all copies and that both that
copyright notice and this permission notice appear in supporting
documentation, and that the name of M.I.T. not be used in advertising or
publicity pertaining to distribution of the software without specific,
written prior permission.  M.I.T. makes no representations about the
suitability of this software for any purpose.  It is provided "as is"
without express or implied warranty.

%prep
rm -rf $RPM_BUILD_ROOT

# Unconpress quietly (-q) 
%setup -q 

%build
perl Makefile.PL
make
make test

%install
%perl_make_install
%perl_process_packlist

%clean
rm -rf $RPM_BUILD_ROOT

%files
%defattr(-, root, root)
%doc ChangeLog README MANIFEST

%{perl_vendorlib}/Net/Traceroute.pm
%{perl_vendorarch}/auto/Net/Traceroute/.packlist
#%{perl_vendorlib}/x86_64-linux-thread-multi/auto/Net/Traceroute/.packlist

%doc %{_mandir}/man3/*
/var/adm/perl-modules/%{name}
