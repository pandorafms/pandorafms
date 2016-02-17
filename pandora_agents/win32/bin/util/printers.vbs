' Pandora FMS Agent Inventory Plugin for Microsoft Windows (All platfforms)
' (c) 2015 Borja Sanchez <fborja.sanchez@artica.es>
' This plugin extends agent inventory feature. Only enterprise version
' --------------------------------------------------------------------------
'WMI printers attached

function validPort(port)
	if strComp(port,"SHRFAX:") = 0 then
		validPort = false
	elseif strComp(port,"nul:") = 0 then
		validPort = false
	elseif strComp(port,"PORTPROMPT:") = 0 then
		validPort = false
	elseif strComp(port,"XPSPort:") = 0 then
		validPort = false
	elseif strComp(port,"PDF:") = 0 then
		validPort = false
	else
		validPort = true
	end if
end function

Set oWMI = GetObject("winmgmts:\\" & "." & "\root\cimv2")
Set colPrinters = oWMI.ExecQuery("Select * from Win32_Printer",,48)

on error resume next
flag = colPrinters.Count
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
	Wscript.StdOut.WriteLine "<name>Printers</name>"
	Wscript.StdOut.WriteLine "<type><![CDATA[generic_data_string]]></type>"
	Wscript.StdOut.WriteLine "<datalist>"

	For Each objPrinter in colPrinters
		if validPort (objPrinter.PortName) then
			set tcp_port_exists = 0
			WScript.stdOut.Write "<data><![CDATA[" & _
				objPrinter.DeviceID & ";" & _
				objPrinter.DriverName & ";"
			If (objPrinter.Local) Then
				Set colPorts = oWMI.ExecQuery("Select HostAddress from Win32_TCPIPPrinterPort where Name like '" & objPrinter.PortName & "'",,48)
				on error resume next
				flag = colPorts.Count
				If (err.number <> 0) Then
				  flag = true
				Else
				  flag = false
				End If
				on error goto 0 

				'Print only when there's results
				If (NOT flag) Then
					For Each objPort in colPorts
						tcp_port_exists = 1
						Wscript.stdOut.Write objPort.HostAddress
					Next
				End If
				If (tcp_port_exists = 0) Then
					Wscript.stdOut.Write objPrinter.PortName
				End If
			Else
				Wscript.stdOut.Write objPrinter.ServerName
			End If
			wscript.stdOut.WriteLine "]]></data>"
		end if
	Next

	Wscript.StdOut.WriteLine "</datalist>"
	Wscript.StdOut.WriteLine "</inventory_module>"
	Wscript.StdOut.WriteLine "</inventory>"
End If
