# Pandora FMS Agent WMI library.
package PandoraWMI;
use strict;
use warnings;
use DBI;
use DBD::WMI;

########################################################################################
# PandoraWMI class constructor.
########################################################################################
sub new ($) {
    my $class = shift;

	my $self = {};
    bless $self, $class;
    return $self;
}

# Return a WQL safe string
sub safe_wql_string ($) {
	my $string = shift;
	
	# Remove characters that may break the query
	$string =~s/'//g;
	
	return $string;
}

# Return an array of values from a WMI query
sub get_row ($@) {
	my ($query, @values) = @_;
		
	my $dbh = DBI->connect('dbi:WMI:');
	return undef unless defined ($dbh);
		
	my $sth = $dbh->prepare($query);
	return undef unless defined ($sth);
	
	$sth->execute(@values);
	return undef unless defined ($sth);
		
	while (my @row = $sth->fetchrow()) {
		return @row;
	}

	$sth->finish();
	return undef;
}

# Get CPU load percentage
sub get_cpu_usage ($) {
	my $self = shift;

	my ($load_percentage) = get_row ('SELECT LoadPercentage FROM Win32_Processor');
	return $load_percentage;
}

# Get memory usage in MBytes
sub get_free_memory ($) {
	my $self = shift;

	my ($available_mbytes) = get_row ('SELECT AvailableMBytes FROM Win32_PerfRawData_PerfOS_Memory');
	return $available_mbytes;
}

# Get memory usage percentage
sub get_free_memory_percentage ($) {
	my $self = shift;

	my ($free_memory, $total_memory) = get_row ('SELECT FreePhysicalMemory, TotalVisibleMemorySize FROM Win32_OperatingSystem');
	return undef unless (defined ($free_memory) && defined ($total_memory));

	return 0 unless ($total_memory > 0);

	return $free_memory * 100.0 / $total_memory;
}

# Get process status (1 running, 0 not running)
sub get_process_status ($$) {
	my ($self, $process_name) = @_;

	my ($name) = get_row ("SELECT Name FROM Win32_Process WHERE Name='" . safe_wql_string ($process_name) . "'");
	return 0 unless (defined ($name));
	
	return 1;
}

# Get service status (1 running, 0 not running)
sub get_service_status ($$) {
	my ($self, $service_name) = @_;

	my ($state) = get_row ("SELECT State FROM Win32_Service WHERE Name='" . safe_wql_string ($service_name) . "'");
	return 0 unless (defined ($state));
	
	# Running!
	return 1 if ($state eq 'Running');
	
	return 0;
}

# Get free disk space in MBytes
sub get_free_disk_space ($$) {
	my ($self, $device_id) = @_;

	my ($free_space) = get_row ("SELECT FreeSpace FROM Win32_LogicalDisk WHERE DeviceID='" . safe_wql_string ($device_id) . "'");
	return 0 unless (defined ($free_space));
		
	# Convert to MBytes (1048576 = 1024 * 1024)
	return $free_space / 1048576.0;
}

# Get free disk space percentage
sub get_free_disk_space_percentage ($$) {
	my ($self, $device_id) = @_;

	my ($size, $free_space) = get_row ("SELECT Size, FreeSpace FROM Win32_LogicalDisk WHERE DeviceID='" . safe_wql_string ($device_id) . "'");
	return 0 unless (defined ($size) && defined ($free_space));
		
	return 0 unless ($size > 0);
	
	return $free_space * 100.0 / $size;
}

1;
__END__
