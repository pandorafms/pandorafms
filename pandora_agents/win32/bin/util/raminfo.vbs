on error resume next
'WMI raminfo

Wscript.StdOut.WriteLine "<inventory>"
Wscript.StdOut.WriteLine "<inventory_module>"
Wscript.StdOut.WriteLine "<name>RAM</name>"
Wscript.StdOut.WriteLine "<type><![CDATA[generic_data_string]]></type>"
Wscript.StdOut.WriteLine "<datalist>"

strComputer = "."
Set objWMIService = GetObject("winmgmts:" & "{impersonationLevel=impersonate}!\\" & strComputer & "\root\cimv2")
Set colRAMs = objWMIService.ExecQuery("Select deviceLocator,capacity,speed from Win32_PhysicalMemory")

For Each ram In colRAMs
  Wscript.StdOut.WriteLine "<data><![CDATA[" & ram.deviceLocator _ 
	& ";" & (ram.capacity/(1024*1024)) _
	& ";" & ram.speed _
	& "]]></data>"
Next

Wscript.StdOut.WriteLine "</datalist>"
Wscript.StdOut.WriteLine "</inventory_module>"
Wscript.StdOut.WriteLine "</inventory>"

