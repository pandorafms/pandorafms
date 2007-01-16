#
# Pandora Agents
#
%define name        pandora_server
%define version	    1.2.0
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
Prefix:             /opt
BuildRoot:          %{_tmppath}/%{name}-%{version}-buildroot
BuildArchitectures: noarch
Requires:	    openssh-server net-snmp perl-XML-Simple perl-DBI perl-TimeDate perl-DateManip perl-Net-Ping perl-Net-Ping-External perl-IO-Socket-SSL
Requires:	    perl-Net-SNMP perl-Digest-MD2
AutoReq:            0
Provides:           %{name}-%{version}

%description
Pandora watchs your systems and applications, and allows to know the status of any element of that systems. Pandora could detect a network interface down, a defacementin your website, memory leak in one of your server app, or the movement of any value of the NASDAQ new technology market. If you want, Pandora could sent a SMS messagewhen your systems fails... or when Google value low below US$ 33

%prep
rm -rf $RPM_BUILD_ROOT

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
mkdir -p $RPM_BUILD_ROOT/etc/pandora/
mkdir -p $RPM_BUILD_ROOT/etc/pandora/conf/
mkdir -p $RPM_BUILD_ROOT/usr/local/
mkdir -p $RPM_BUILD_ROOT/var/spool/pandora/
mkdir -p $RPM_BUILD_ROOT/var/spool/pandora/data_in
mkdir -p $RPM_BUILD_ROOT/var/log/pandora/
mkdir -p $RPM_BUILD_ROOT/usr/share/pandora/
mkdir -p $RPM_BUILD_ROOT/usr/lib/
mkdir -p $RPM_BUILD_ROOT/usr/lib/perl5
mkdir -p $RPM_BUILD_ROOT/usr/lib/perl5/site_perl/
mkdir -p $RPM_BUILD_ROOT/usr/lib/perl5/site_perl/5.8.5
mkdir -p $RPM_BUILD_ROOT/usr/lib/perl5/site_perl/5.8.5/Pandora

cp -aRf * $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}
mv $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/bin/pandora_server.pl $RPM_BUILD_ROOT/usr/bin/pandora_server
mv $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/bin/pandora_network.pl $RPM_BUILD_ROOT/usr/bin/pandora_network
mv $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/bin/pandora_snmpconsole.pl $RPM_BUILD_ROOT/usr/bin/pandora_snmpconsole
mv $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/conf/pandora_server.conf $RPM_BUILD_ROOT/etc/pandora/conf/pandora_server.conf
mv $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/util/ $RPM_BUILD_ROOT/usr/share/pandora/
mv $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/bin/pandora_config.pm $RPM_BUILD_ROOT/usr/share/pandora/util/
mv $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/bin/pandora_db.pm $RPM_BUILD_ROOT/usr/share/pandora/util/
mv $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/bin/pandora_tools.pm $RPM_BUILD_ROOT/usr/share/pandora/util/
cp $RPM_BUILD_ROOT/usr/share/pandora/util/pandora_config.pm $RPM_BUILD_ROOT/usr/lib/perl5/site_perl/5.8.5/Pandora/
cp $RPM_BUILD_ROOT/usr/share/pandora/util/pandora_db.pm $RPM_BUILD_ROOT/usr/lib/perl5/site_perl/5.8.5/Pandora/
cp  $RPM_BUILD_ROOT/usr/share/pandora/util/pandora_tools.pm $RPM_BUILD_ROOT/usr/lib/perl5/site_perl/5.8.5/Pandora/


cp pandora.1 $RPM_BUILD_ROOT/usr/share/man/man1/
cp pandora_server.1 $RPM_BUILD_ROOT/usr/share/man/man1/
if [ -f $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/%{name}.spec ] ; then
    rm $RPM_BUILD_ROOT%{prefix}/%{name}-%{version}-%{release}/%{name}.spec
fi

%clean
rm -rf $RPM_BUILD_ROOT
%post
if [ "$1" = "0" ]; then
       /usr/sbin/userdel pandora
       /usr/sbin/groupdel pandora
fi

echo "Pandora Server binarys has been placed under /usr/bin/"
echo "Pandora Server configuration is /etc/pandora/conf"
echo "Pandora Server data has been placed under /var/spool/data_in/"
echo "Pandora Server logs has benn placed under /var/log/pandora"
echo "For further information please: man pandora or man pandora_server"
%files
%defattr(700,pandora,pandora)
/usr/bin/pandora_server
/usr/bin/pandora_network
/usr/bin/pandora_snmpconsole
/var/spool/pandora/
%defattr(755,pandora,pandora)
/etc/pandora/conf/pandora_server.conf
/usr/share/pandora/util/pandora_config.pm
/usr/share/pandora/util/pandora_db.pm
/usr/share/pandora/util/pandora_tools.pm
/usr/share/pandora/util/pandora_db.pl
/usr/share/pandora/util/pandora_dbstress.pl
/usr/share/pandora/util/snmptrapd
/usr/lib/perl5/site_perl/5.8.5/Pandora/pandora_config.pm
/usr/lib/perl5/site_perl/5.8.5/Pandora/pandora_db.pm
/usr/lib/perl5/site_perl/5.8.5/Pandora/pandora_tools.pm

%docdir %{prefix}/%{name}-%{version}-%{release}/docs
%{prefix}/%{name}-%{version}-%{release}
%{_mandir}/man1/pandora.1.gz
%{_mandir}/man1/pandora_server.1.gz
