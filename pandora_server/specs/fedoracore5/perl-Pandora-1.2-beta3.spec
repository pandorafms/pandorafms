%define TarBall Pandora 

Summary: Pandora PERL library
Name: perl-%{TarBall}
Version: 1.2
Release: beta3
License: GPL
Group: System Development/Languages
Distribution: Fedora Core 5
Source: file://usr/src/redhat/SOURCES/%{TarBall}-%{version}.tar.gz
Vendor: Sancho Lerena <slerena@gmail.com>
Packager: Jose Angel de Bustos Perez <jadebustos@linuxmail.org>, Manuel Arostegui <marostegui@artica.es>
BuildRequires: perl make
Requires: perl perl-DateManip perl-XML-Simple
%description
PerlPersonalLibrary is my own set of PERL functions.

%prep
mkdir /usr/lib/perl5/site_perl/5.8.5/Pandora/
cp /usr/src/redhat/SOURCES/Pandora-1.2/lib/Pandora/pandora_config.pm /usr/lib/perl5/site_perl/5.8.5/Pandora/
cp /usr/src/redhat/SOURCES/Pandora-1.2/lib/Pandora/pandora_db.pm /usr/lib/perl5/site_perl/5.8.5/Pandora/
cp /usr/src/redhat/SOURCES/Pandora-1.2/lib/Pandora/pandora_tools.pm /usr/lib/perl5/site_perl/5.8.5/Pandora/

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
