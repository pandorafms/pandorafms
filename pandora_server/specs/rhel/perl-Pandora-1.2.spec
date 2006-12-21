%define TarBall Pandora 

Summary: Pandora PERL library
Name: perl-%{TarBall}
Version: 1.2
Release: 1
Copyright: GPL
Group: System Development/Languages
Distribution: RHEL 4 AS
Source: file://usr/src/redhat/SOURCES/%{TarBall}-%{version}.tar.gz
Vendor: Sancho Lerena <slerena@gmail.com>
Packager: Jose Angel de Bustos Perez <jadebustos@linuxmail.org>
BuildRequires: perl make
Requires: perl perl-DateManip perl-XML-Simple

%description
PerlPersonalLibrary is my own set of PERL functions.

%prep

%setup -n %{TarBall}-%{version}
perl Makefile.PL

%install
make install

%files

%defattr(-,root,root)

/usr/lib/perl5/site_perl/5.8.5/Pandora/pandora_config.pm
/usr/lib/perl5/site_perl/5.8.5/Pandora/pandora_db.pm
/usr/lib/perl5/site_perl/5.8.5/Pandora/pandora_tools.pm

%clean
rm -Rf $RPM_BUILD_DIR/%{name}-%{version}

%changelog
* Wed Nov 22 2006 - Jose Angel de Bustos Perez <jadebustos@linuxmail.org>
- Initial package of version 1.2

