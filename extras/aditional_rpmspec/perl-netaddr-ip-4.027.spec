#NetAddr-IP Perl Module
#
%define name        perl-netaddr-ip
%define version	    4.027
Summary:            Manages IPv4 and IPv6 addresses and subnets
Name:               %{name}
Version:            %{version}
Release:            0
License:            Perl Artistic Licence
Vendor:             Luis E. Muñoz <luismunoz@cpan.org>
Source0:            %{name}-%{version}.tar.bz2
URL:                http://search.cpan.org/~miker/NetAddr-IP-4.027/
Group:              Development/Libraries/Perl
Packager:           Pablo de la Concepcion <pablo@artica.es>
Prefix:             /usr/share
BuildRoot:          %{_tmppath}/%{name}-%{version}-build
BuildArch: 	    noarch
Requires:	    perl
AutoReq:            1
Provides:           %{name}-%{version}

%description
NetAddr::IP - Manage IP addresses and subnets

This distribution  is designed as a  help for managing  (ranges of) IP
addresses. It includes efficient implementations for most common tasks
done  to subnets or  ranges of  IP addresses,  namely verifying  if an
address is within a subnet, comparing, looping, splitting subnets into
longer prefixes, compacting addresses to the shortest prefixes, etc.

LICENSE AND WARRANTY

This software is (c) Luis E. Muñoz and Michael A. Robinton.  It can be
used under the terms of the perl artistic license provided that proper
credit for the work of the authors is  preserved in  the form  of this
copyright  notice and license for this module.

No warranty of any kind is  expressed or implied. This code might make
your computer go up in a puff of black smoke.

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
%{perl_vendorlib}/NetAddr/IP/Util_IS.pm
%{perl_vendorlib}/NetAddr/IP/UtilPP.pm
%{perl_vendorlib}/NetAddr/IP/Lite.pm
%{perl_vendorlib}/NetAddr/IP/Util.pm
%{perl_vendorlib}/NetAddr/IP.pm

%{perl_vendorarch}/auto/NetAddr/IP/.packlist
%{perl_vendorlib}/auto/NetAddr/IP/Util/autosplit.ix
%{perl_vendorlib}/auto/NetAddr/IP/Util/inet_any2n.al
%{perl_vendorlib}/auto/NetAddr/IP/Util/inet_n2ad.al
%{perl_vendorlib}/auto/NetAddr/IP/Util/inet_n2dx.al
%{perl_vendorlib}/auto/NetAddr/IP/Util/ipv6_aton.al
%{perl_vendorlib}/auto/NetAddr/IP/Util/ipv6_n2d.al
%{perl_vendorlib}/auto/NetAddr/IP/Util/ipv6_n2x.al
%{perl_vendorlib}/auto/NetAddr/IP/UtilPP/_128x10.al
%{perl_vendorlib}/auto/NetAddr/IP/UtilPP/_128x2.al
%{perl_vendorlib}/auto/NetAddr/IP/UtilPP/_bcd2bin.al
%{perl_vendorlib}/auto/NetAddr/IP/UtilPP/_bcdcheck.al
%{perl_vendorlib}/auto/NetAddr/IP/UtilPP/_bin2bcdn.al
%{perl_vendorlib}/auto/NetAddr/IP/UtilPP/_deadlen.al
%{perl_vendorlib}/auto/NetAddr/IP/UtilPP/_sa128.al
%{perl_vendorlib}/auto/NetAddr/IP/UtilPP/add128.al
%{perl_vendorlib}/auto/NetAddr/IP/UtilPP/addconst.al
%{perl_vendorlib}/auto/NetAddr/IP/UtilPP/autosplit.ix
%{perl_vendorlib}/auto/NetAddr/IP/UtilPP/bcd2bin.al
%{perl_vendorlib}/auto/NetAddr/IP/UtilPP/bcdn2bin.al
%{perl_vendorlib}/auto/NetAddr/IP/UtilPP/bcdn2txt.al
%{perl_vendorlib}/auto/NetAddr/IP/UtilPP/bin2bcd.al
%{perl_vendorlib}/auto/NetAddr/IP/UtilPP/bin2bcdn.al
%{perl_vendorlib}/auto/NetAddr/IP/UtilPP/comp128.al
%{perl_vendorlib}/auto/NetAddr/IP/UtilPP/hasbits.al
%{perl_vendorlib}/auto/NetAddr/IP/UtilPP/ipanyto6.al
%{perl_vendorlib}/auto/NetAddr/IP/UtilPP/ipv4to6.al
%{perl_vendorlib}/auto/NetAddr/IP/UtilPP/ipv6to4.al
%{perl_vendorlib}/auto/NetAddr/IP/UtilPP/isIPv4.al
%{perl_vendorlib}/auto/NetAddr/IP/UtilPP/mask4to6.al
%{perl_vendorlib}/auto/NetAddr/IP/UtilPP/maskanyto6.al
%{perl_vendorlib}/auto/NetAddr/IP/UtilPP/notcontiguous.al
%{perl_vendorlib}/auto/NetAddr/IP/UtilPP/shiftleft.al
%{perl_vendorlib}/auto/NetAddr/IP/UtilPP/simple_pack.al
%{perl_vendorlib}/auto/NetAddr/IP/UtilPP/slowadd128.al
%{perl_vendorlib}/auto/NetAddr/IP/UtilPP/sub128.al
%{perl_vendorlib}/auto/NetAddr/IP/_compV6.al
%{perl_vendorlib}/auto/NetAddr/IP/_compact_v6.al
%{perl_vendorlib}/auto/NetAddr/IP/_splitplan.al
%{perl_vendorlib}/auto/NetAddr/IP/_splitref.al
%{perl_vendorlib}/auto/NetAddr/IP/autosplit.ix
%{perl_vendorlib}/auto/NetAddr/IP/coalesce.al
%{perl_vendorlib}/auto/NetAddr/IP/compactref.al
%{perl_vendorlib}/auto/NetAddr/IP/do_prefix.al
%{perl_vendorlib}/auto/NetAddr/IP/full.al
%{perl_vendorlib}/auto/NetAddr/IP/full6.al
%{perl_vendorlib}/auto/NetAddr/IP/hostenum.al
%{perl_vendorlib}/auto/NetAddr/IP/mod_version.al
%{perl_vendorlib}/auto/NetAddr/IP/nprefix.al
%{perl_vendorlib}/auto/NetAddr/IP/prefix.al
%{perl_vendorlib}/auto/NetAddr/IP/re.al
%{perl_vendorlib}/auto/NetAddr/IP/re6.al
%{perl_vendorlib}/auto/NetAddr/IP/short.al
%{perl_vendorlib}/auto/NetAddr/IP/wildcard.al


%doc %{_mandir}/man3/*
/var/adm/perl-modules/%{name}
