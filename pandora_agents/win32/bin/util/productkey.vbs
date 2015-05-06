' windows_product_key.vbs
' Pandora FMS Agent Inventory Plugin for Microsoft Windows (All platfforms)
' (c) 2015 Sancho Lerena <slerena@artica.es>
' This plugin extends agent inventory feature. Only enterprise version
' ----------------------------------------------------------------

on error resume next

Wscript.StdOut.WriteLine "<inventory>"
Wscript.StdOut.WriteLine "<inventory_module>"
Wscript.StdOut.WriteLine "<name>product_key</name>"
Wscript.StdOut.WriteLine "<type><![CDATA[generic_data_string]]></type>"
Wscript.StdOut.WriteLine "<datalist>"

strComputer = "."
Set objWMIService = GetObject("winmgmts:" & "{impersonationLevel=impersonate}!\\" & strComputer & "\root\cimv2")
Set colProducts = objWMIService.ExecQuery("Select OA3xOriginalProductKey from SoftwareLicensingService")

For Each product In colProducts
  Wscript.StdOut.WriteLine "<data><![CDATA["  & product.OA3xOriginalProductKey & "]]></data>"
Next

Wscript.StdOut.WriteLine "</datalist>"
Wscript.StdOut.WriteLine "</inventory_module>"
Wscript.StdOut.WriteLine "</inventory>"
