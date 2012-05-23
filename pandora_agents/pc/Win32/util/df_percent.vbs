' df_all.vbs
' Returns free space (%) for all drives
' Pandora FMS Plugin, (c) 2010 Sancho Lerena
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
		        Percent = round ((objItem.FreeSpace / objItem.Size) * 100, 2)

			Wscript.StdOut.WriteLine "<module>"
			Wscript.StdOut.WriteLine "    <name><![CDATA[DiskFree%_" & objItem.Name & "]]></name>"
			Wscript.StdOut.WriteLine "    <description><![CDATA[Drive " & objItem.Name & " % free space ]]></description>"
			Wscript.StdOut.WriteLine "    <data><![CDATA[" & Percent & "]]></data>"
			Wscript.StdOut.WriteLine "</module>"
			Wscript.StdOut.flush
		End If
	End If
Next
