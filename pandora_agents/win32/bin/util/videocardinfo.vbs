' Pandora FMS Agent Inventory Plugin for Microsoft Windows (All platfforms)
' (c) 2015 Borja Sanchez <fborja.sanchez@artica.es>
' This plugin extends agent inventory feature. Only enterprise version
' --------------------------------------------------------------------------
on error resume next
'WMI video_card_info

Wscript.StdOut.WriteLine "<inventory>"
Wscript.StdOut.WriteLine "<inventory_module>"
Wscript.StdOut.WriteLine "<name>Video</name>"
Wscript.StdOut.WriteLine "<type><![CDATA[generic_data_string]]></type>"
Wscript.StdOut.WriteLine "<datalist>"

strComputer = "."
Set objWMIService = GetObject("winmgmts:" & "{impersonationLevel=impersonate}!\\" & strComputer & "\root\cimv2")
Set colVideoCards = objWMIService.ExecQuery("Select caption,AdapterRAM,PNPDeviceID from win32_videocontroller")

For Each vcard In colVideoCards
  Wscript.StdOut.WriteLine "<data><![CDATA["  & vcard.caption _ 
	& ";" & Abs(Round(vcard.AdapterRAM/(1024*1024)),2) & " MB" _
	& ";" & vcard.PNPDeviceID _
	& "]]></data>"
Next

Wscript.StdOut.WriteLine "</datalist>"
Wscript.StdOut.WriteLine "</inventory_module>"
Wscript.StdOut.WriteLine "</inventory>"

