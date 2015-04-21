on error resume next
'WMI disksinfo

Wscript.StdOut.WriteLine "<inventory>"
Wscript.StdOut.WriteLine "<inventory_module>"
Wscript.StdOut.WriteLine "<name>Disks</name>"
Wscript.StdOut.WriteLine "<type><![CDATA[generic_data_string]]></type>"
Wscript.StdOut.WriteLine "<datalist>"

strComputer = "."
Set objWMIService = GetObject("winmgmts:" & "{impersonationLevel=impersonate}!\\" & strComputer & "\root\cimv2")
Set colHDDs = objWMIService.ExecQuery("Select caption,size,serialnumber from win32_diskdrive")

For Each disco In colHDDs
  Wscript.StdOut.WriteLine "<data><![CDATA[" & disco.caption _ 
	& ";" & (disco.size/(1024*1024)) _
	& ";" & disco.serialnumber _
	& "]]></data>"
Next

Wscript.StdOut.WriteLine "</datalist>"
Wscript.StdOut.WriteLine "</inventory_module>"
Wscript.StdOut.WriteLine "</inventory>"

