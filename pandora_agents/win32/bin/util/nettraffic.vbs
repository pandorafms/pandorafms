' nettraffic.vbs
' Pandora FMS Agent Plugin for Microsoft Windows (All platfforms)
' (c) 2014 Sancho Lerena <slerena@artica.es>
' Returns total bytes in network since bootup and % of network use
' ----------------------------------------------------------------
' usage: cscript //B nettraffic.vbs

strComputer = "."
Set objWMIService = GetObject("winmgmts:" _
& "{impersonationLevel=impersonate}!\\" & strComputer & "\Root\CIMV2")
Set colItems = objWMIService.ExecQuery _
("select * from Win32_PerfRawData_Tcpip_NetworkInterface ") 

BytesSUM = 0

For Each objItem in colItems
	bytesTotal = objitem.BytesTotalPersec * 8
	BytesSUM = BytesSUM + bytesTotal

Next

Wscript.StdOut.WriteLine "<module>"
Wscript.StdOut.WriteLine "    <name><![CDATA[Network_Usage_Bytes]]></name>"
Wscript.StdOut.WriteLine "    <description><![CDATA[Total network usage in bytes]]></description>"
Wscript.StdOut.WriteLine "    <unit>bytes/sec</unit>"
Wscript.StdOut.WriteLine "    <type>generic_data_inc</type>"
Wscript.StdOut.WriteLine "    <data><![CDATA[" & BytesSUM & "]]></data>"
Wscript.StdOut.WriteLine "</module>"

Wscript.StdOut.flush
' End script