#HTML-Parser Perl Module
#
%define name        perl-html-parser
%define version	    3.61
Summary:            Collection of modules that parse and extract information from HTML documents
Name:               %{name}
Version:            %{version}
Release:            0
License:            perl
Vendor:             Gisle Aas <gisle@ActiveState.com>
Source0:            %{name}-%{version}.tar.bz2
URL:                http://search.cpan.org/dist/HTML-Parser/
Group:              Development/Libraries/Perl
Packager:           Pablo de la Concepcion <pablo@artica.es>
Prefix:             /usr/share
BuildRoot:          %{_tmppath}/%{name}-%{version}-build
BuildArch: 	    noarch
Requires:	    perl 
AutoReq:            1
Provides:           %{name}-%{version}

%description


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

%{perl_vendorarch}/auto/HTML/Parser/Parser.bs
%{perl_vendorarch}/auto/HTML/Parser/.packlist
%{perl_vendorarch}/auto/HTML/Parser/Parser.so
%{perl_vendorarch}/HTML/Parser.pm
%{perl_vendorarch}/HTML/Entities.pm
%{perl_vendorarch}/HTML/LinkExtor.pm
%{perl_vendorarch}/HTML/Filter.pm
%{perl_vendorarch}/HTML/PullParser.pm
%{perl_vendorarch}/HTML/TokeParser.pm
%{perl_vendorarch}/HTML/HeadParser.pm
%doc %{_mandir}/man3/*
/var/adm/perl-modules/%{name}

