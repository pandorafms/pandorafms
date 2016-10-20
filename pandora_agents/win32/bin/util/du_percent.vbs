' df_all.vbs
' Returns free space (%) for all drives
' Pandora FMS Plugin, (c) 2014 Sancho Lerena
' ------------------------------------------

Option Explicit
On Error Resume Next

' Variables
Dim objWMIService, objItem, colItems, argc, argv, i, Percent, Percentused


' Parse command line parameters
argc = Wscript.Arguments.Count
Set argv = CreateObject("Scripting.Dictionary")
For i = 0 To argc - 1
    argv.Add Wscript.Arguments(i), i
Next

' Get drive information
Set objWMIService = GetObject ("winmgmts:\\.\root\cimv2")
Set colItems = objWMIService.ExecQuery ("Select * from Win32_LogicalDisk")

For Each objItem in colItems
	If argc = 0 Or argv.Exists(objItem.Name) Then
		' Include only harddrivers (type 3)
		If (objItem.FreeSpace <> "") AND (objItem.DriveType =3) Then
		        Percent = round ((objItem.FreeSpace / objItem.Size) * 100, 2)
		        Percentused = 100 - (Percent)

			Wscript.StdOut.WriteLine "<module>"
			Wscript.StdOut.WriteLine "    <name><![CDATA[DiskUsed%_" & objItem.Name & "]]></name>"
			Wscript.StdOut.WriteLine "    <description><![CDATA[Drive " & objItem.Name & " % used space ]]></description>"
				if (Percentused > 99.99) then
				Wscript.StdOut.WriteLine "    <data><![CDATA[" & 100 & "]]></data>"
				elseif (Percentused < 0.01) then
				Wscript.StdOut.WriteLine "    <data><![CDATA[" & 0 & "]]></data>"
				else
				Wscript.StdOut.WriteLine "    <data><![CDATA[" & Percentused & "]]></data>"
				End If
				Wscript.StdOut.WriteLine "    <unit>%</unit>"
				Wscript.StdOut.WriteLine "    <min_warning>90</min_warning>"
				Wscript.StdOut.WriteLine "    <min_critical>95</min_critical>"
				Wscript.StdOut.WriteLine "</module>"
				Wscript.StdOut.flush
		End If
	End If
Next
