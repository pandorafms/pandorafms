#DB PHP PEAR  Module
#
%define name        php-pear-db
%define version     1.7.14RC1

%define peardir %(pear config-get php_dir 2> /dev/null || echo %{_datadir}/pear)
%define xmldir  /var/lib/pear

Summary: 	PEAR: Database Abstraction Layer
Name:           %{name}
Version:        %{version}
Release: 	0
License: 	PHP License
Group: 		Development/Libraries
Source0: 	DB-%{version}.tar.gz
BuildRoot: 	%{_tmppath}/%{name}-%{version}-root-%(%{__id_u} -n)
URL: 		http://pear.php.net/package/DB
#BuildRequires: PEAR::PEAR >= 1.4.7
BuildRequires: 	php5-pear
#Requires: 	PEAR::PEAR >= 1.4.0b1
Requires: 	php5-pear

BuildArch: 	noarch
Packager:       Pablo de la Concepcion <pablo@artica.es>


%description
DB is a database abstraction layer providing:
* an OO-style query API
* portability features that make programs written for one DBMS work with
other DBMSs
* a DSN (data source name) format for specifying database servers
* prepare/execute (bind) emulation for databases that dont support it
natively
* a result object for each query response
* portable error codes
* sequence emulation
* sequential and non-sequential row fetching as well as bulk fetching
* formats fetched rows as associative arrays, ordered arrays or objects
* row limit support
* transactions support
* table information interface
* DocBook and phpDocumentor API documentation

DB layers itself on top of PHPs existing
database extensions.

Drivers for the following extensions pass
the complete test suite and provide
interchangeability when all of DBs
portability options are enabled:

  fbsql, ibase, informix, msql, mssql,
  mysql, mysqli, oci8, odbc, pgsql,
  sqlite and sybase.

There is also a driver for the dbase
extension, but it cant be used
interchangeably because dbase doesnt
support many standard DBMS features.

DB is compatible with both PHP 4 and PHP 5.

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

mv %{buildroot}/docs .


# Install XML package description
mkdir -p %{buildroot}%{xmldir}
tar -xzf %{SOURCE0} package.xml
cp -p package.xml %{buildroot}%{xmldir}/DB.xml

%clean
rm -rf %{buildroot}

%post
pear install --nodeps --soft --force --register-only %{xmldir}/DB.xml

%postun
if [ "$1" -eq "0" ]; then
    pear uninstall --nodeps --ignore-errors --register-only pear.php.net/DB
fi

%files
%defattr(-,root,root)
%doc docs/DB/*
%{peardir}/*
%{xmldir}/DB.xml
