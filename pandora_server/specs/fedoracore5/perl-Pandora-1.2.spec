%define TarBall Pandora 

Summary: Pandora PERL library
Name: perl-%{TarBall}
Version: 1.2
Release: 1
License: GPL
Group: System Development/Languages
Distribution: Fedora Core 5
Source: file://usr/src/redhat/SOURCES/%{TarBall}-%{version}.tar.gz
Vendor: Sancho Lerena <slerena@artica.es>
Packager: Manuel Arostegui <marostegui@artica.es>
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

%post
cp /usr/src/redhat/SOURCES/PandoraFMS_Server-1.2.0.tar.gz /opt
cd /opt/ && tar -zxvf PandoraFMS_Server-1.2.0.tar.gz
useradd -m -s /bin/bash pandora
chown pandora /opt/pandora_server/data_in
chown pandora /opt/pandora_server/log
chown pandora /opt/pandora_server/var
su pandora -c "mkdir /home/pandora/.ssh"
su pandora -c "touch /home/pandora/.ssh/authorized_keys"
su pandora -c "chmod 600 /home/pandora/.ssh/authorized_keys"
/etc/init.d/sshd restart
clear
echo "You are required to generate ssh keys by using ssh-keygen. For further information read documentation at: http://pandora.sourceforge.net/en/index.php?sec=docs"
echo "You HAVE TO give a password to the pandora user which has been created during the installation of this package. Use "passwd pandora"  " 
echo "Enjoy Pandora. We remind you to point your browser to http://www.openideas.info/phpbb/ if you have any question, idea..."
echo "Pandora Team"


%files

%defattr(-,root,root)

/usr/lib/perl5/site_perl/5.8.5/Pandora/pandora_config.pm
/usr/lib/perl5/site_perl/5.8.5/Pandora/pandora_db.pm
/usr/lib/perl5/site_perl/5.8.5/Pandora/pandora_tools.pm

%clean
rm -Rf $RPM_BUILD_DIR/%{name}-%{version}

%changelog
* Fri Dec 22 2006 - Manuel Arostegui Ramirez <marostegui@artica.es>
- Pandora 1.2 spec file. Added Server Files
* Wed Nov 22 2006 - Manuel Arostegui Ramirez <marostegui@artica.es>
- Added Fedora PATHS
* Wed Nov 22 2006 - Jose Angel de Bustos Perez <jadebustos@linuxmail.org>
- Initial package of version 1.2
