' Pandora FMS Agent Inventory Plugin for Microsoft Windows (All platfforms)
' (c) 2015 Borja Sanchez <fborja.sanchez@artica.es>
' This plugin extends agent inventory feature. Only enterprise version
' --------------------------------------------------------------------------
'WMI video_card_info

strComputer = "."
Set objWMIService = GetObject("winmgmts:" & "{impersonationLevel=impersonate}!\\" & strComputer & "\root\cimv2")
Set colVideoCards = objWMIService.ExecQuery("Select caption,AdapterRAM,PNPDeviceID from win32_videocontroller")

on error resume next
flag = colVideoCards.Count
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
	Wscript.StdOut.WriteLine "<name>Video</name>"
	Wscript.StdOut.WriteLine "<type><![CDATA[generic_data_string]]></type>"
	Wscript.StdOut.WriteLine "<datalist>"

	For Each vcard In colVideoCards
	  Wscript.StdOut.Write "<data><![CDATA["  & vcard.caption & ";"
	  on error resume next
	  Wscript.StdOut.Write Round(Abs(vcard.AdapterRAM/(1024*1024)),2) & " MB"
	  on error goto 0
	  Wscript.StdOut.Write ";" & vcard.PNPDeviceID
	  Wscript.StdOut.WriteLine "]]></data>"
	Next

	Wscript.StdOut.WriteLine "</datalist>"
	Wscript.StdOut.WriteLine "</inventory_module>"
	Wscript.StdOut.WriteLine "</inventory>"
End If
