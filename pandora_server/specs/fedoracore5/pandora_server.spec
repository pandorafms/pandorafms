#
# Pandora Server 
#
%define name        pandora_server
%define version	    1.3
Summary:            Pandora Server
Name:               %{name}
Version:            %{version}
Release:            1
License:            GPL
Vendor:             Sancho Lerena <sancho.lerena@artica.es>
Source0:            %{name}-%{version}.tar.gz
URL:                http://pandora.sf.net
Group:              System/Monitoring
Packager:           Manuel Arostegui <marostegui@artica.es>
Prefix:             /usr/share
BuildRoot:          %{_tmppath}/%{name}-buildroot
BuildArchitectures: noarch
Requires:	    openssh-server net-snmp perl-XML-Simple perl-DBI perl-TimeDate perl-DateManip perl-Net-Ping perl-IO-Socket-SSL
Requires:	    perl-Net-SNMP perl-Digest-MD2
AutoReq:            0
Provides:           %{name}-%{version}

%description
Pandora watchs your systems and applications, and allows to know the status of any element of that systems. Pandora could detect a network interface down, a defacementin your website, memory leak in one of your server app, or the movement of any value of the NASDAQ new technology market. If you want, Pandora could sent a SMS messagewhen your systems fails... or when Google value low below US$ 33

%prep
rm -rf $RPM_BUILD_ROOT

#Evaluate perl version:
export perl_version=`rpm -q --queryformat='%{VERSION}' perl`

%setup -q -n pandora_server

%build

%install
rm -rf $RPM_BUILD_ROOT
mkdir -p $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}
mkdir -p $RPM_BUILD_ROOT/usr/
mkdir -p $RPM_BUILD_ROOT/usr/share/
mkdir -p $RPM_BUILD_ROOT/usr/share/man
mkdir -p $RPM_BUILD_ROOT/usr/share/man/man1
mkdir -p $RPM_BUILD_ROOT/usr/bin/
mkdir -p $RPM_BUILD_ROOT/etc/
mkdir -p $RPM_BUILD_ROOT/etc/init.d/
mkdir -p $RPM_BUILD_ROOT/etc/pandora/
mkdir -p $RPM_BUILD_ROOT/var/spool/pandora/
mkdir -p $RPM_BUILD_ROOT/var/spool/pandora/data_in
mkdir -p $RPM_BUILD_ROOT/var/log/pandora/
mkdir -p $RPM_BUILD_ROOT/usr/share/pandora_server/
mkdir -p $RPM_BUILD_ROOT/usr/share/pandora_server/conf/
mkdir -p $RPM_BUILD_ROOT/usr/share/pandora_server/bin/
mkdir -p $RPM_BUILD_ROOT/usr/lib/
mkdir -p $RPM_BUILD_ROOT/usr/lib/perl5
mkdir -p $RPM_BUILD_ROOT/usr/lib/perl5/site_perl/
mkdir -p $RPM_BUILD_ROOT/usr/lib/perl5/site_perl/PandoraFMS
#mkdir -p $RPM_BUILD_ROOT/usr/lib/perl5/site_perl/`rpm -q --queryformat='%{VERSION}' perl`
cp -aRf * $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}
#mv $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/bin/pandora_server.pl $RPM_BUILD_ROOT/usr/bin/pandora_server
cp $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/bin/pandora_server.pl $RPM_BUILD_ROOT/usr/bin/pandora_server
cp $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/bin/pandora_network.pl $RPM_BUILD_ROOT/usr/bin/pandora_network
cp $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/bin/pandora_recon.pl $RPM_BUILD_ROOT/usr/bin/pandora_recon
cp $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/bin/pandora_server.pl $RPM_BUILD_ROOT/usr/share/pandora_server/bin/
cp $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/bin/pandora_network.pl $RPM_BUILD_ROOT/usr/share/pandora_server/bin/
cp $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/bin/pandora_recon.pl $RPM_BUILD_ROOT/usr/share/pandora_server/bin/
cp $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/bin/pandora_snmpconsole.pl $RPM_BUILD_ROOT/usr/bin/pandora_snmpconsole
mv $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/conf/pandora_server.conf $RPM_BUILD_ROOT/usr/share/pandora_server/conf/
mv $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/util/ $RPM_BUILD_ROOT/usr/share/pandora_server/
#mv $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/bin/pandora_config.pm $RPM_BUILD_ROOT/usr/share/pandora_server/util/
#mv $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/bin/pandora_db.pm $RPM_BUILD_ROOT/usr/share/pandora_server/util/
#mv $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/bin/pandora_tools.pm $RPM_BUILD_ROOT/usr/share/pandora_server/util/
cp $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/bin/PandoraFMS/DB.pm $RPM_BUILD_ROOT/usr/lib/perl5/site_perl/PandoraFMS
cp $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/bin/PandoraFMS/PingExternal.pm $RPM_BUILD_ROOT/usr/lib/perl5/site_perl/PandoraFMS
cp $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/bin/PandoraFMS/Tools.pm $RPM_BUILD_ROOT/usr/lib/perl5/site_perl/PandoraFMS
cp $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/bin/PandoraFMS/Config.pm $RPM_BUILD_ROOT/usr/lib/perl5/site_perl/PandoraFMS
#cp $RPM_BUILD_ROOT/usr/share/pandora_server/util/pandora_config.pm $RPM_BUILD_ROOT/usr/lib/perl5/site_perl/5.8.5/
#cp $RPM_BUILD_ROOT/usr/share/pandora_server/util/pandora_db.pm $RPM_BUILD_ROOT/usr/lib/perl5/site_perl/5.8.5/
#cp $RPM_BUILD_ROOT/usr/share/pandora_server/util/pandora_tools.pm $RPM_BUILD_ROOT/usr/lib/perl5/site_perl/5.8.5/
cp $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/pandora_server $RPM_BUILD_ROOT/etc/init.d/pandora_server
cp $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/pandora_network $RPM_BUILD_ROOT/etc/init.d/pandora_network
cp $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/pandora_recon $RPM_BUILD_ROOT/etc/init.d/pandora_recon
#cp $RPM_BUILD_ROOT/usr/share/pandora_server/pandora_server $RPM_BUILD_ROOT/etc/init.d/pandora_server
#cp $RPM_BUILD_ROOT/usr/share/pandora_server/pandora_recon $RPM_BUILD_ROOT/etc/init.d/pandora_network
#cp $RPM_BUILD_ROOT/usr/share/pandora_server/bin/pandora_recon.pl $RPM_BUILD_ROOT/usr/bin/pandora_recon
cp pandora.1 $RPM_BUILD_ROOT/usr/share/man/man1/
cp pandora_server.1 $RPM_BUILD_ROOT/usr/share/man/man1/
if [ -f $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/%{name}.spec ] ; then
    rm $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/%{name}.spec
fi

%clean
rm -fr $RPM_BUILD_ROOT
rm -fr $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}
%post
if [ "$1" = "0" ]; then
       /usr/sbin/userdel pandora
       /usr/sbin/groupdel pandora
fi
mkdir -p /etc/pandora
ln -s /usr/share/pandora_server/conf/pandora_server.conf /etc/pandora/pandora_server.conf

mkdir -p /usr/share/pandora_server/
mkdir -p /usr/share/pandora_server/log/
rm -fr /usr/share/pandora_server-1.3-1
echo "Pandora Server configuration is /etc/pandora/pandora_server.conf"
echo "Pandora Server data has been placed under /var/spool/pandora/data_in/"
echo "Pandora Server logs has benn placed under /var/log/"
echo "Pandora Server main directory is /usr/share/pandora_server/"
echo "To start Pandora Server: /etc/init.d/pandora_server start"
echo "To start Pandora Network Server: /etc/init.d/pandora_network start"
echo "To start Pandora Recon Server: /etc/init.d/pandora_recon start"
echo "The manual can be reached at: man pandora or man pandora_server"
echo "Pandora Documentation is in: http://pandora.sourceforge.net/en/index.php?sec=docs"
%files
%defattr(700,pandora,pandora)
/usr/bin/pandora_server
/usr/bin/pandora_network
/usr/bin/pandora_recon
/usr/bin/pandora_snmpconsole
/var/spool/pandora/
/etc/init.d/pandora_recon
/etc/init.d/pandora_server
/etc/init.d/pandora_network
%defattr(755,pandora,pandora)

#/etc/pandora/conf/pandora_server.conf
/usr/share/pandora_server/util/agent_creator.php
/usr/share/pandora_server/util/crea_modulos_ping.php
/usr/share/pandora_server/util/lista_ip.txt
/usr/share/pandora_server/util/pandora_db.pl
/usr/share/pandora_server/util/pandora_dbstress.log
/usr/share/pandora_server/util/pandora_dbstress.pl
/usr/share/pandora_server/util/snmptrapd
/usr/share/pandora_server/bin/pandora_network.pl
/usr/share/pandora_server/bin/pandora_recon.pl
/usr/share/pandora_server/bin/pandora_server.pl
/usr/share/pandora_server/util/PandoraFMS
#/usr/share/pandora_server/util/pandora_config.pm
#/usr/share/pandora_server/util/pandora_db.pm
#/usr/share/pandora_server/util/pandora_tools.pm
#/usr/share/pandora_server/util/pandora_db.pl
#/usr/share/pandora_server/util/pandora_dbstress.pl
#/usr/share/pandora_server/util/snmptrapd
#/usr/lib/perl5/site_perl/5.8.5/pandora_config.pm
#/usr/lib/perl5/site_perl/5.8.5/pandora_db.pm
#/usr/lib/perl5/site_perl/5.8.5/pandora_tools.pm
#/usr/share/pandora_server/bin/PandoraFMS/Config.pm
#/usr/share/pandora_server/bin/PandoraFMS/DB.pm
#/usr/share/pandora_server/bin/PandoraFMS/PingExternal.pm
#/usr/share/pandora_server/bin/PandoraFMS/Tools.pm
/usr/lib/perl5/site_perl/PandoraFMS/DB.pm
/usr/lib/perl5/site_perl/PandoraFMS/Tools.pm
/usr/lib/perl5/site_perl/PandoraFMS/PingExternal.pm
/usr/lib/perl5/site_perl/PandoraFMS/Config.pm
/usr/share/pandora_server/conf/pandora_server.conf
/var/log/pandora

%docdir %{prefix}/%{name}-%{version}-%{release}/docs
%{prefix}/%{name}-%{version}-%{release}
%{_mandir}/man1/pandora.1.gz
%{_mandir}/man1/pandora_server.1.gz
