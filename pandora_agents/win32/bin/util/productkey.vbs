' windows_product_key.vbs
' Pandora FMS Agent Inventory Plugin for Microsoft Windows (All platfforms)
' (c) 2015 Sancho Lerena <slerena@artica.es>
' This plugin extends agent inventory feature. Only enterprise version
' ----------------------------------------------------------------

strComputer = "."
Set objWMIService = GetObject("winmgmts:" & "{impersonationLevel=impersonate}!\\" & strComputer & "\root\cimv2")
Set colProducts = objWMIService.ExecQuery("Select OA3xOriginalProductKey from SoftwareLicensingService")

on error resume next
flag = colProducts.Count
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
	Wscript.StdOut.WriteLine "<name>product_key</name>"
	Wscript.StdOut.WriteLine "<type><![CDATA[generic_data_string]]></type>"
	Wscript.StdOut.WriteLine "<datalist>"

	For Each product In colProducts
	  Wscript.StdOut.WriteLine "<data><![CDATA["  & product.OA3xOriginalProductKey & "]]></data>"
	Next

	Wscript.StdOut.WriteLine "</datalist>"
	Wscript.StdOut.WriteLine "</inventory_module>"
	Wscript.StdOut.WriteLine "</inventory>"
End If