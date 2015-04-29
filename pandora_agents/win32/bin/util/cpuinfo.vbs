on error resume next
'WMI cpuinfo

Wscript.StdOut.WriteLine "<inventory>"
Wscript.StdOut.WriteLine "<inventory_module>"
Wscript.StdOut.WriteLine "<name>CPU</name>"
Wscript.StdOut.WriteLine "<type><![CDATA[generic_data_string]]></type>"
Wscript.StdOut.WriteLine "<datalist>"

strComputer = "."
Set objWMIService = GetObject("winmgmts:" & "{impersonationLevel=impersonate}!\\" & strComputer & "\root\cimv2")
Set colCPUs = objWMIService.ExecQuery("Select name,maxclockspeed,caption from Win32_Processor")

For Each cpu In colCPUs
  Wscript.StdOut.WriteLine "<data><![CDATA[" & cpu.name & ";" & cpu.maxclockspeed & " MHz;" & cpu.caption & "]]></data>"
Next

Wscript.StdOut.WriteLine "</datalist>"
Wscript.StdOut.WriteLine "</inventory_module>"
Wscript.StdOut.WriteLine "</inventory>"

