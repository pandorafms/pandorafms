' Pandora FMS Agent Inventory Plugin for Microsoft Windows (All platfforms)
' (c) 2015 Borja Sanchez <fborja.sanchez@artica.es>
' This plugin extends agent inventory feature. Only enterprise version
' --------------------------------------------------------------------------
'WMI domain/workgroup info

strComputer = "."
Set objWMIService = GetObject("winmgmts:\\" & strComputer & "\root\CIMV2")
Set colItems = objWMIService.ExecQuery("SELECT Domain FROM Win32_ComputerSystem")

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
	Wscript.StdOut.WriteLine "<name>Domain</name>"
	Wscript.StdOut.WriteLine "<type><![CDATA[generic_data_string]]></type>"
	Wscript.StdOut.WriteLine "<datalist>"

	For Each objItem In colItems
	  WScript.StdOut.WriteLine "<data><![CDATA[" & objItem.Domain & "]]></data>"
	Next

	Wscript.StdOut.WriteLine "</datalist>"
	Wscript.StdOut.WriteLine "</inventory_module>"
	Wscript.StdOut.WriteLine "</inventory>"
End If