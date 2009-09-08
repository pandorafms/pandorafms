#Mail-Sendamail Perl Module
#
%define name        perl-mail-sendmail
%define version	    0.79_16
Summary:            Mail::Sendmail v. 0.79 - Simple platform independent mailer
Name:               %{name}
Version:            %{version}
Release:            0
License:            Public domain, Freeware
Vendor:             Milivoj Ivkovic <mi@alma.ch>
Source0:            %{name}-%{version}.tar.bz2
URL:                http://search.cpan.org/~mivkovic/Mail-Sendmail-0.79/
Group:              Development/Libraries/Perl
Packager:           Pablo de la Concepcion <pablo@artica.es>
Prefix:             /usr/share
BuildRoot:          %{_tmppath}/%{name}-%{version}-build
BuildArch: 	    noarch
Requires:	    perl
AutoReq:            1
Provides:           %{name}-%{version}

%description
Simple platform independent e-mail from your perl script. Only requires Perl 5 and a network connection.

Mail::Sendmail contains mainly &sendmail, which takes a hash with the message to send and sends it. It is intended to be very easy to setup and use. 

FEATURES

Automatic time zone detection, Date: header, MIME quoted-printable encoding (if MIME::QuotedPrint installed), all of which can be overridden.

Bcc: and Cc: support.

Allows real names in From:, To: and Cc: fields

Doesn't send an X-Mailer: header (unless you do), and allows you to send any header(s) you want.

Configurable retries and use of alternate servers if your mail server is down

Good plain text error reporting

LIMITATIONS

Headers are not encoded, even if they have accented characters.

Since the whole message is in memory, it's not suitable for sending very big attached files.

The SMTP server has to be set manually in Sendmail.pm or in your script, unless you have a mail server on localhost.

Doesn't work on OpenVMS, I was told. Cannot test this myself.  

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
%{perl_vendorlib}/Mail/Sendmail.pm
%{perl_vendorarch}/auto/Mail/Sendmail/.packlist
#%{perl_vendorlib}/x86_64-linux-thread-multi/auto/Mail/Sendmail/.packlist
%doc %{_mandir}/man3/*
/var/adm/perl-modules/%{name}
