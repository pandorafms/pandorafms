
Sub PrintPerf(item)
	Set objShell = CreateObject("WScript.Shell")
	Set objExec = objShell.Exec("powershell Get-Process | Sort-Object " & item & " -desc | Select-Object -first 1 | Format-Table " & item & ",ProcessName -hidetableheader")
	Do
		line = Trim(objExec.StdOut.ReadLine())
		If Len(line) > 0 Then
			value   = Left (line, InStr (line, " ")-1)
			process = Mid (line, InStr (line, " ")+1, Len (line) ) 
			WScript.StdOut.WriteLine "<data><![CDATA[" & value & "]]></data>"
			WScript.StdOut.WriteLine "<description>process name: " & process & " </description>"
		End If
	Loop While Not objExec.Stdout.atEndOfStream
End Sub



' Generate module: CPU ussage
WScript.StdOut.WriteLine "<module>"
WScript.StdOut.WriteLine "<name><![CDATA[CPU top process usage]]></name>"
WScript.StdOut.WriteLine "<type><![CDATA[generic_data]]></type>"
PrintPerf "CPU"
WScript.StdOut.WriteLine "</module>"

' Generate module: MEM ussage
WScript.StdOut.WriteLine "<module>"
WScript.StdOut.WriteLine "<name><![CDATA[MEM top process usage]]></name>"
WScript.StdOut.WriteLine "<type><![CDATA[generic_data]]></type>"
PrintPerf "WS"
WScript.StdOut.WriteLine "</module>"

