' Pandora FMS Agent Inventory Plugin for Microsoft Windows (All platfforms)
' (c) 2015 Borja Sanchez <fborja.sanchez@artica.es>
' This plugin extends agent inventory feature. Only enterprise version
' --------------------------------------------------------------------------
'WMI mobo info

strComputer = "."
Set objWMIService = GetObject("winmgmts:" & "{impersonationLevel=impersonate}!\\" & strComputer & "\root\cimv2")
'Set colMobos = objWMIService.ExecQuery("Select name,product,manufacturer from Win32_baseboard")
Set colMobos2 = objWMIService.ExecQuery("Select manufacturer,model,OEMStringArray from Win32_computersystem")

on error resume next
flag = colItems.Count
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
	Wscript.StdOut.WriteLine "<name>Motherboard</name>"
	Wscript.StdOut.WriteLine "<type><![CDATA[generic_data_string]]></type>"
	Wscript.StdOut.WriteLine "<datalist>"

	For Each mobo In colMobos2
	  Wscript.StdOut.WriteLine "<data><![CDATA[" & mobo.manufacturer & ";" & mobo.model & ";" & mobo.OEMStringArray(0) & "]]></data>"
	Next

	Wscript.StdOut.WriteLine "</datalist>"
	Wscript.StdOut.WriteLine "</inventory_module>"
	Wscript.StdOut.WriteLine "</inventory>"
End If
