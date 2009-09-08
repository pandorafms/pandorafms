#Net-Traceroute-PurePerl Perl Module
#
%define name        perl-net-traceroute-pureperl
%define version	    0.10
Summary:            Net::Traceroute:PurePerl 	traceroute(1) functionality in perl via raw sockets  
Name:               %{name}
Version:            %{version}
Release:            0
License:            Public domain, Freeware
Vendor:             Tom Scanlan <tscanlan@openreach.com>, Andrew Hoying <ahoying@cpan.org>
Source0:            %{name}-%{version}.tar.bz2
URL:                http://search.cpan.org/~ahoying/Net-Traceroute-PurePerl-0.10/
Group:              Development/Libraries/Perl
Packager:           Pablo de la Concepcion <pablo@artica.es>
Prefix:             /usr/share
BuildRoot:          %{_tmppath}/%{name}-%{version}-build
BuildArch: 	    noarch
Requires:	    perl perl-net-traceroute
AutoReq:            1
Provides:           %{name}-%{version}

%description
This module implements traceroute(1) functionality for perl5. It allows
you to trace the path IP packets take to a destination. It is
implemented by using raw sockets to act just like the regular
traceroute.

You must also be root to use the raw sockets.

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
%doc Changes README MANIFEST
%{perl_vendorlib}/Net/Traceroute/PurePerl.pm
%{perl_vendorarch}/auto/Net/Traceroute/PurePerl/.packlist
#%{perl_vendorlib}/x86_64-linux-thread-multi/auto/Net/Traceroute/PurePerl/.packlist
%doc %{_mandir}/man3/*
/var/adm/perl-modules/%{name}
