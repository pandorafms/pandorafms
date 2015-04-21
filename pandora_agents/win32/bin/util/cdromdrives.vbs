on error resume next
'WMI CD_ROM_drives_info

Wscript.StdOut.WriteLine "<inventory>"
Wscript.StdOut.WriteLine "<inventory_module>"
Wscript.StdOut.WriteLine "<name>CDROMs</name>"
Wscript.StdOut.WriteLine "<type><![CDATA[generic_data_string]]></type>"
Wscript.StdOut.WriteLine "<datalist>"

strComputer = "."
Set objWMIService = GetObject("winmgmts:" & "{impersonationLevel=impersonate}!\\" & strComputer & "\root\cimv2")
Set colCDROMDrives = objWMIService.ExecQuery("Select caption,description,drive,deviceid from win32_CDROMDrive")

For Each cdromd In colCDROMDrives
  Wscript.StdOut.WriteLine "<data><![CDATA[" & cdromd.caption _ 
    & ";" & cdromd.description _
	& ";" & cdromd.drive _
	& ";" & cdromd.deviceid _
	& "]]></data>"
Next

Wscript.StdOut.WriteLine "</datalist>"
Wscript.StdOut.WriteLine "</inventory_module>"
Wscript.StdOut.WriteLine "</inventory>"

