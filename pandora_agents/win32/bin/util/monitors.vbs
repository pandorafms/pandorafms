on error resume next
'WMI monitorsinfo

Wscript.StdOut.WriteLine "<inventory>"
Wscript.StdOut.WriteLine "<inventory_module>"
Wscript.StdOut.WriteLine "<name>Monitors</name>"
Wscript.StdOut.WriteLine "<type><![CDATA[generic_data_string]]></type>"
Wscript.StdOut.WriteLine "<datalist>"

strComputer = "."
Set objWMIService = GetObject("winmgmts:" & "{impersonationLevel=impersonate}!\\" & strComputer & "\root\cimv2")
Set colDisplays = objWMIService.ExecQuery("Select caption,pnpdeviceid from win32_desktopmonitor")

For Each display In colDisplays
  if (NOT isNull(display.pnpdeviceid)) then
    Wscript.StdOut.WriteLine "<data><![CDATA[" & display.caption _ 
		& ";" & display.pnpdeviceid _
		& "]]></data>"
  end if
Next

Wscript.StdOut.WriteLine "</datalist>"
Wscript.StdOut.WriteLine "</inventory_module>"
Wscript.StdOut.WriteLine "</inventory>"

