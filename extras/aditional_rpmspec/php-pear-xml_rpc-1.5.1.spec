#XML_RPC PHP PEAR  Module
#
%define name        php-pear-xml_rpc
%define version     1.5.1

%define peardir %(pear config-get php_dir 2> /dev/null || echo %{_datadir}/pear)
%define xmldir  /var/lib/pear

Summary: 	PEAR: PHP implementation of the XML-RPC protocol
Name:           %{name}
Version:        %{version}
Release:        0
License:        PHP License
Group:          Development/Libraries
Source0: 	XML_RPC-%{version}.tar.gz
BuildRoot: 	%{_tmppath}/%{name}-%{version}-root-%(%{__id_u} -n)
URL: 		http://pear.php.net/package/XML_RPC
#BuildRequires: PEAR::PEAR >= 1.4.7
BuildRequires:  php5-pear
#Requires:      PEAR::PEAR >= 1.4.0b1
Requires:       php5-pear
Packager:       Pablo de la Concepcion <pablo@artica.es>
BuildArch: noarch

%description
A PEAR-ified version of Useful Incs XML-RPC for PHP.

It has support for HTTP/HTTPS transport, proxies and authentication.

%prep
%setup -c -T
pear -v -c pearrc \
        -d php_dir=%{peardir} \
        -d doc_dir=/docs \
        -d bin_dir=%{_bindir} \
        -d data_dir=%{peardir}/data \
        -d test_dir=%{peardir}/tests \
        -d ext_dir=%{_libdir} \
        -s

%build

%install
rm -rf %{buildroot}
pear -c pearrc install --nodeps --packagingroot %{buildroot} %{SOURCE0}
        
# Clean up unnecessary files
rm pearrc
rm %{buildroot}/%{peardir}/.filemap
rm %{buildroot}/%{peardir}/.lock
rm -rf %{buildroot}/%{peardir}/.registry
rm -rf %{buildroot}%{peardir}/.channels
rm %{buildroot}%{peardir}/.depdb
rm %{buildroot}%{peardir}/.depdblock



# Install XML package description
mkdir -p %{buildroot}%{xmldir}
tar -xzf %{SOURCE0} package2.xml
cp -p package2.xml %{buildroot}%{xmldir}/XML_RPC.xml

%clean
rm -rf %{buildroot}

%post
pear install --nodeps --soft --force --register-only %{xmldir}/XML_RPC.xml

%postun
if [ "$1" -eq "0" ]; then
    pear uninstall --nodeps --ignore-errors --register-only pear.php.net/XML_RPC
fi

%files
%defattr(-,root,root)

%{peardir}/*
%{xmldir}/XML_RPC.xml
