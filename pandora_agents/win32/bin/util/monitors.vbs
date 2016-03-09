' Pandora FMS Agent Inventory Plugin for Microsoft Windows (All platfforms)
' (c) 2015 Borja Sanchez <fborja.sanchez@artica.es>
' This plugin extends agent inventory feature. Only enterprise version
' --------------------------------------------------------------------------
'WMI monitorsinfo

strComputer = "."
Set objWMIService = GetObject("winmgmts:" & "{impersonationLevel=impersonate}!\\" & strComputer & "\root\cimv2")
Set colDisplays = objWMIService.ExecQuery("Select caption,pnpdeviceid from win32_desktopmonitor")

on error resume next
flag = colDisplays.Count
If (err.number <> 0) Then
  flag = true
Else
  flag = false
End If
on error goto 0 

'Print only when there's results
If (NOT flag) Then
	Wscript.StdOut.WriteLine "<inventory>"
	Wscript.StdOut.WriteLine "<inventory_module>"
	Wscript.StdOut.WriteLine "<name>Monitors</name>"
	Wscript.StdOut.WriteLine "<type><![CDATA[generic_data_string]]></type>"
	Wscript.StdOut.WriteLine "<datalist>"

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
End If
