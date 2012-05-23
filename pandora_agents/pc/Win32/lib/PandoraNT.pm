# Pandora FMS Agent NT library
# Retrieve system information using the NT library whenever possible.
package PandoraNT;
use strict;
use warnings;
use Win32::SystemInfo;
use Win32::SystemInfo::CpuUsage;
use Win32::Service;
use Win32::DriveInfo;
use Win32::Process::List;

########################################################################################
# PandoraNT class constructor.
########################################################################################
sub new ($) {
    my $class = shift;
	
	my $self = {};	
    bless $self, $class;
    return $self;
}

# Get CPU load percentage
sub get_cpu_usage ($) {
	my $self = shift;

	return Win32::SystemInfo::CpuUsage::getCpuUsage(500);
}

# Get memory usage in MBytes
sub get_free_memory ($) {
	my $self = shift;

	my %mem = ('TotalPhys' => 0, 'AvailPhys' => 0);
	if (! Win32::SystemInfo::MemoryStatus(%mem, "MB")) {
		return undef;
	}
	
	return $mem{'AvailPhys'};
}

# Get memory usage percentage
sub get_free_memory_percentage ($) {
	my $self = shift;

	my %mem = ('TotalPhys' => 0, 'AvailPhys' => 0);
	if (! Win32::SystemInfo::MemoryStatus(%mem, "MB")) {
		return undef;
	}
	
	return 0 unless ($mem{'TotalPhys'} > 0);
	
	return $mem{'AvailPhys'} * 100.0 / $mem{'TotalPhys'};
}

# Get process status (1 running, 0 not running)
sub get_process_status ($$) {
	my ($self, $process_name) = @_;
	
	# Retrieve process information
	# TODO: reuse the process list between modules
	my $p = Win32::Process::List->new();
	my %list = $p->GetProcesses();
	my $pid = $p->GetProcessPid("$process_name");
	
	return 0 unless (defined ($pid));
	
	return 1;
}

# Get service status (1 running, 0 not running)
sub get_service_status ($$) {
	my ($self, $service_name) = @_;
	
	my %status;
	Win32::Service::GetStatus('localhost', $service_name, \%status);
	
	# Service does not exist
	return 0 unless (defined ($status{'CurrentState'}));
	
	# Service is not running
	# See http://msdn.microsoft.com/en-us/library/windows/desktop/ms685996(v=vs.85).aspx
	return 0 unless ($status{'CurrentState'} == 4);
		
	return 1;
}

# Get free disk space in MBytes
sub get_free_disk_space ($$) {
	my ($self, $device_id) = @_;

	my ($SectorsPerCluster,
	    $BytesPerSector,
	    $NumberOfFreeClusters,
	    $TotalNumberOfClusters,
	    $FreeBytesAvailableToCaller,
	    $TotalNumberOfBytes,
		$TotalNumberOfFreeBytes) = Win32::DriveInfo::DriveSpace($device_id);
	
	return 0 unless (defined ($TotalNumberOfFreeBytes));
	
	# Convert to MBytes (1048576 = 1024 * 1024)
	return $TotalNumberOfFreeBytes / 1048576.0;
}

# Get free disk space percentage
sub get_free_disk_space_percentage ($$) {
	my ($self, $device_id) = @_;

	my ($SectorsPerCluster,
	    $BytesPerSector,
	    $NumberOfFreeClusters,
	    $TotalNumberOfClusters,
	    $FreeBytesAvailableToCaller,
	    $TotalNumberOfBytes,
		$TotalNumberOfFreeBytes) = Win32::DriveInfo::DriveSpace($device_id);
	
	return 0 unless (defined ($TotalNumberOfFreeBytes) && defined ($TotalNumberOfBytes));
	
	return $TotalNumberOfFreeBytes * 100.0 / $TotalNumberOfBytes;
}

1;
__END__
