On Error Resume Next

'WMI printers attached

Wscript.StdOut.WriteLine "<inventory>"
Wscript.StdOut.WriteLine "<inventory_module>"
Wscript.StdOut.WriteLine "<name>Printers</name>"
Wscript.StdOut.WriteLine "<type><![CDATA[generic_data_string]]></type>"
Wscript.StdOut.WriteLine "<datalist>"

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

For Each objPrinter in colPrinters
	if validPort (objPrinter.PortName) then
		set tcp_port_exists = 0
		WScript.stdOut.Write "<data><![CDATA[" & _
			objPrinter.DeviceID & ";" & _
			objPrinter.DriverName & ";"
		Set colPorts = oWMI.ExecQuery("Select HostAddress from Win32_TCPIPPrinterPort where Name like '" & objPrinter.PortName & "'",,48)
		For Each objPort in colPorts
			tcp_port_exists = 1
			Wscript.stdOut.Write objPort.HostAddress
		Next
		If (tcp_port_exists = 0) Then
			Wscript.stdOut.Write objPrinter.PortName
		End If
		wscript.stdOut.WriteLine "]]></data>"
	end if
Next


Wscript.StdOut.WriteLine "</datalist>"
Wscript.StdOut.WriteLine "</inventory_module>"
Wscript.StdOut.WriteLine "</inventory>"
