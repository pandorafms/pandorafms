' ps.vbs
' Returns the status of the given processes.
' -----------------------------------------

Option Explicit
'On Error Resume Next

' Variables
Dim objWMIService, objItem, colItems, argc, ps, i

' Get and hash process information
Set objWMIService = GetObject ("winmgmts:\\.\root\cimv2")
Set colItems = objWMIService.ExecQuery ("Select * from Win32_Process")
Set ps = CreateObject("Scripting.Dictionary")
For Each objItem in colItems
	if Not ps.Exists(objItem.Name) Then
		ps.Add objItem.Name, 1
	End If
Next

' Parse command line parameters and check each process
argc = Wscript.Arguments.Count
For i = 0 To argc - 1
	Wscript.StdOut.WriteLine "<module>"
	Wscript.StdOut.WriteLine "    <name><![CDATA[" & Wscript.Arguments(i) & "]]></name>"
	Wscript.StdOut.WriteLine "    <description><![CDATA[Process " & Wscript.Arguments(i) & " status]]></description>"
	If argc = 0 Or ps.Exists(Wscript.Arguments(i)) Then
		Wscript.StdOut.WriteLine "    <data><![CDATA[" & 1 & "]]></data>"
	Else
		Wscript.StdOut.WriteLine "    <data><![CDATA[" & 0 & "]]></data>"
	End If
	Wscript.StdOut.WriteLine "</module>"
    Wscript.StdOut.flush
Next
