' windows_product_key.vbs
' Pandora FMS Agent Inventory Plugin for Microsoft Windows (All platfforms)
' (c) 2015 Sancho Lerena <slerena@artica.es>
' This plugin extends agent inventory feature. Only enterprise version
' ----------------------------------------------------------------


Wscript.StdOut.WriteLine "<inventory>"
Wscript.StdOut.WriteLine "<inventory_module>"
Wscript.StdOut.WriteLine "<name>product_ID</name>"
Wscript.StdOut.WriteLine "<type><![CDATA[generic_data_string]]></type>"
Wscript.StdOut.WriteLine "<datalist>"

Set oShell = WScript.CreateObject ("WScript.Shell")
Set objExec = oShell.Exec("cmd.exe /C wmic os get ""SerialNumber"" | find /v ""SerialNumber"" ")
Do
    line = objExec.StdOut.ReadLine()
    s = s & line 
Loop While Not objExec.Stdout.atEndOfStream

Wscript.StdOut.WriteLine "<data><![CDATA[" & Replace(Replace(s, chr(013), ""), chr(010), "") & "]]></data>"
Wscript.StdOut.WriteLine "</datalist>"
Wscript.StdOut.WriteLine "</inventory_module>"
Wscript.StdOut.WriteLine "</inventory>"
