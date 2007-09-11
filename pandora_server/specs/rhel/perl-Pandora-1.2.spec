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
Packager: Jose Angel de Bustos Perez <jadebustos@linuxmail.org>, Manuel Arostegui <marostegui@artica.es>
BuildRequires: perl make
Requires: perl perl-DateManip perl-XML-Simple

%description
PerlPersonalLibrary is my own set of PERL functions.

%prep

%setup -n %{TarBall}-%{version}
perl Makefile.PL

%install
make install

%post
cp /usr/src/redhat/SOURCES/PandoraFMS_Server-1.2.0.tar.gz /opt
cd /opt/ && tar -zxvf PandoraFMS_Server-1.2.0.tar.gz
useradd -m -s /bin/false -d /opt/pandora_server pandora
chown pandora /opt/pandora_server/data_in
chown pandora /opt/pandora_server/log
chown pandora /opt/pandora_server/var
su pandora -c "mkdir /opt/pandora_server/.ssh"
su pandora -c "touch /opt/pandora_server/.ssh/authorized_keys"
su pandora -c "chmod 600 /opt/pandora_server/.ssh/authorized_keys"
/etc/init.d/sshd restart
clear
echo "You are required to generate ssh keys by using ssh-keygen. For further information read documentation at: http://pandora.sourceforge.net/en/index.php?sec=docs"
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
* Wed Nov 22 2006 - Jose Angel de Bustos Perez <jadebustos@linuxmail.org>
- Initial package of version 1.2

