' df.vbs
' Returns free space for avaible drives.
' --------------------------------------

Option Explicit
On Error Resume Next

' Variables
Dim objWMIService, objItem, colItems, argc, argv, i

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
		If objItem.FreeSpace <> "" Then
			Wscript.StdOut.WriteLine "<module>"
			Wscript.StdOut.WriteLine "    <name><![CDATA[" & objItem.Name & "]]></name>"
			Wscript.StdOut.WriteLine "    <description><![CDATA[Drive " & objItem.Name & " free space in MB]]></description>"
			Wscript.StdOut.WriteLine "    <data><![CDATA[" & Int(objItem.FreeSpace /1048576) & "]]></data>"
			Wscript.StdOut.WriteLine "</module>"
            Wscript.StdOut.flush
		End If
	End If
Next
