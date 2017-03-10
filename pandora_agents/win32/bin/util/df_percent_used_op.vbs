' df_all.vbs
' Returns used space (%) for all drives
' Pandora FMS Plugin, (c) 2014 Sancho Lerena
' ------------------------------------------

Option Explicit
On Error Resume Next

' Variables
Dim objWMIService, objItem, colItems, argc, argv, i, Percent


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
			Percent = round (100 - (objItem.FreeSpace / objItem.Size) * 100, 2)
			Wscript.StdOut.WriteLine "<module>"
			If (objItem.Name = "C:") Then
				Wscript.StdOut.WriteLine "    <name><![CDATA[DiskUsed_/]]></name>"
			Else
				Wscript.StdOut.WriteLine "    <name><![CDATA[DiskUsed_" & objItem.Name & "]]></name>"
			End If
			Wscript.StdOut.WriteLine "    <description><![CDATA[% used space. Filesystem unit:  " & objItem.Name & "]]></description>"
			If (Percent > 99.99) then
				Wscript.StdOut.WriteLine "    <data><![CDATA[" & 100 & "]]></data>"
			Elseif (Percent < 0.01) then
				Wscript.StdOut.WriteLine "    <data><![CDATA[" & 0 & "]]></data>"
			Else
				Wscript.StdOut.WriteLine "    <data><![CDATA[" & Percent & "]]></data>"
			End If
			Wscript.StdOut.WriteLine "    <unit>%</unit>"
			Wscript.StdOut.WriteLine "    <min_warning>90</min_warning>"
			Wscript.StdOut.WriteLine "    <max_warning>0</max_warning>"
			Wscript.StdOut.WriteLine "    <min_critical>95</min_critical>"
			Wscript.StdOut.WriteLine "    <max_critical>0</max_critical>"
			Wscript.StdOut.WriteLine "    <module_group>System</module_group>"
			Wscript.StdOut.WriteLine "</module>"
			Wscript.StdOut.flush
		End If
	End If
Next
