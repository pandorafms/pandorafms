use 5.000;
use ExtUtils::MakeMaker;

my %ARGV = map { my @r = split /=/,$_; defined $r[1] or $r[1]=1; @r } @ARGV;
my @exe_files = qw(bin/pandora_server bin/pandora_exec);
$ARGV{WITHOUT_TENTACLE} or push @exe_files, 'bin/tentacle_server';

WriteMakefile(
	INSTALLSITELIB  => '/usr/lib/perl5',
	(($^O eq 'freebsd')
		? (INSTALLSITELIB => '') : ()
	),
	($^O eq 'netbsd') ? (
		INSTALLSITELIB => '',
		INSTALLSITESCRIPT => '/usr/local/bin',
	) :(),

	NAME            => 'PandoraFMS',
	VERSION_FROM    => 'lib/PandoraFMS/Config.pm',
	AUTHOR 			=> 'Pandora FMS <info@pandorafms.com>',
	PREREQ_PM 	=> {
		NetAddr::IP => 0,
		DBI => 0,
		threads::shared => 0,
		IO::Socket => 0,
		Socket6 => 0,
		Time::Local => 0,
		XML::Simple => 0,
		XML::Twig => 0,
		Time::HiRes => 0,
		HTML::Entities => 0,
		IO::Socket::INET6 => 0,
		JSON => 0,
	},
	EXE_FILES 		=> [ @exe_files ],
	PMLIBDIRS  		=> [ 'lib' ],
	'dist'		=> { 'TAR' => 'tar', 'TARFLAGS' => 'cvfz', 'SUFFIX' => '.gz', 'COMPRESS' => 'gzip'}
);
