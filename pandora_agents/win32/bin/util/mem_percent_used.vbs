' mem_percent_used.vbs
' Returns used RAM (%)
' Pandora FMS Plugin, (c) 2017 Fermin Hernandez
' ------------------------------------------

Dim usedMEM, totalMEM, Percent

strComputer = "."
Set objWMIService = GetObject("winmgmts:" & "{impersonationLevel=impersonate}!\\" & strComputer & "\root\cimv2")

Set colRAMs = objWMIService.ExecQuery("Select capacity from Win32_PhysicalMemory")
For Each total in colRAMs
	totalMEM = total.capacity
Next

Set colUSEDs = objWMIService.ExecQuery("Select freePhysicalMemory from Win32_OperatingSystem")
For Each used in colUSEDs
	usedMEM = used.freePhysicalMemory * 1024
Next

on error resume next
flag = colRAMs.Count
If (err.number <> 0) Then
  flag = true
Else
  flag = false
End If
on error goto 0 

on error resume next
flag = colUSEDs.Count
If (err.number <> 0) Then
  flag = true
Else
  flag = false
End If
on error goto 0 

'Print only when there's results
If (NOT flag) Then
	Percent = round (100 - (usedMEM / totalMEM) * 100, 2)
	Wscript.StdOut.WriteLine "<module>"
	Wscript.StdOut.WriteLine "    <name><![CDATA[Memory_Used]]></name>"
	Wscript.StdOut.WriteLine "    <description><![CDATA[Used memory %]]></description>"
	If (Percent > 99.99) then
		Wscript.StdOut.WriteLine "    <data><![CDATA[" & 100 & "]]></data>"
	Elseif (Percent < 0.01) then
		Wscript.StdOut.WriteLine "    <data><![CDATA[" & 0 & "]]></data>"
	Else
		Wscript.StdOut.WriteLine "    <data><![CDATA[" & Percent & "]]></data>"
	End If
	Wscript.StdOut.WriteLine "    <unit>%</unit>"
	Wscript.StdOut.WriteLine "    <min_critical>95</min_critical>"
	Wscript.StdOut.WriteLine "    <max_critical>100</max_critical>"
	Wscript.StdOut.WriteLine "    <module_group>System</module_group>"
	Wscript.StdOut.WriteLine "</module>"
End If
