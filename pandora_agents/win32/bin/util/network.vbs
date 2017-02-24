' Agent Plugin to get detailed network information per network interface
' Execute as module_plugin cscript //B network.vbs

Option Explicit

Dim colAdapters, objAdapter, NicDescription, strFileName, objFS, objTS, colAdapters2, objAdapter2

Dim totalNetworkUsage

totalNetworkUsage=0

Set colAdapters2 = GetObject("winmgmts:{impersonationLevel=impersonate}").ExecQuery("SELECT * FROM Win32_PerfRawData_Tcpip_NetworkInterface WHERE Name !=  'isatap.localdomain'")
For Each objAdapter2 in colAdapters2      
	totalNetworkUsage = totalNetworkUsage + objAdapter2.BytesTotalPersec
Next 
 
    Wscript.StdOut.WriteLine "<module>"
	Wscript.StdOut.WriteLine "    <name>Network_Usage_Bytes</name>"
	Wscript.StdOut.WriteLine "    <description>Total bytes/sec transfered in this system</description>"
	Wscript.StdOut.WriteLine "    <type>generic_data_inc</type>"
	Wscript.StdOut.WriteLine "    <data>" & totalNetworkUsage  & "</data>"
	Wscript.StdOut.WriteLine "    <unit>bytes/sec</unit>"
	Wscript.StdOut.WriteLine "    <module_group>Networking</module_group>"
	Wscript.StdOut.WriteLine "</module>"

WScript.Quit
