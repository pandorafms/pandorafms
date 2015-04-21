on error resume next
'WMI mobo info

Wscript.StdOut.WriteLine "<inventory>"
Wscript.StdOut.WriteLine "<inventory_module>"
Wscript.StdOut.WriteLine "<name>Motherboard</name>"
Wscript.StdOut.WriteLine "<type><![CDATA[generic_data_string]]></type>"
Wscript.StdOut.WriteLine "<datalist>"

strComputer = "."
Set objWMIService = GetObject("winmgmts:" & "{impersonationLevel=impersonate}!\\" & strComputer & "\root\cimv2")
'Set colMobos = objWMIService.ExecQuery("Select name,product,manufacturer from Win32_baseboard")
Set colMobos2 = objWMIService.ExecQuery("Select manufacturer,model,OEMStringArray from Win32_computersystem")

For Each mobo In colMobos2
  Wscript.StdOut.WriteLine "<data><![CDATA[" & mobo.manufacturer & ";" & mobo.model & ";" & mobo.OEMStringArray(0) & "]]></data>"
Next

Wscript.StdOut.WriteLine "</datalist>"
Wscript.StdOut.WriteLine "</inventory_module>"
Wscript.StdOut.WriteLine "</inventory>"

