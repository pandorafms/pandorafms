on error resume next
' Lista todos los usuarios del equipo que han iniciado procesos

dim loggedUsers(),i
i=0
Sub insertIfNotExists(sDomain,sUser)
	For Each lUser in loggedUsers
		If StrComp(sDomain & "\" & sUser, lUser) = 0 Then
			Exit Sub
		End If
	Next
	redim preserve loggedUsers(i)
	i=i+1
	loggedUsers (i-1)=sDomain & "\" & sUser
End Sub

Function CheckRealUser(sHost, sUser, sDomain)
	Dim oWMI,realUsers
	Set oWmi = GetObject("winmgmts:{impersonationLevel=impersonate,(debug)}!\\" _
		& sHost & "\root\cimv2")
	Set realUsers = oWmi.ExecQuery("SELECT * FROM Win32_UserAccount WHERE Domain='" & sDomain & "' OR Name='" & sUser & "'")
	If (realUsers.count=0) Then
		CheckRealUser = False
	Else
		CheckRealUser = True
	End If
	
End Function

Function ConsoleUser(sHost)
	Dim oWMI, colProc, oProcess, strUser, strDomain
	Set oWmi = GetObject("winmgmts:" _
	& "{impersonationLevel=impersonate,(debug)}!\\" _
	& sHost & "\root\cimv2")

	Set colProc = oWmi.ExecQuery("Select sessionID from Win32_Process")

	ConsoleUser = ""
	For Each oProcess In colProc
		lRet = oProcess.getOwner(strOwner, strDomain)
		If (lRet = 0) AND (CheckRealUser(sHost,strOwner,strDomain)) Then
			insertIfNotExists strDomain,strOwner
			ConsoleUser = sUser
		End If
	Next
End Function

' MAIN

sUser = ConsoleUser(".") ' use "." for local computer

Wscript.StdOut.WriteLine "<inventory>"
Wscript.StdOut.WriteLine "<inventory_module>"
Wscript.StdOut.WriteLine "<name>Users</name>"
Wscript.StdOut.WriteLine "<type><![CDATA[generic_data_string]]></type>"
Wscript.StdOut.WriteLine "<datalist>"


For Each usuario in loggedUsers
	Wscript.StdOut.WriteLine "<data><![CDATA[" & usuario _ 
	& "]]></data>"
next


Wscript.StdOut.WriteLine "</datalist>"
Wscript.StdOut.WriteLine "</inventory_module>"
Wscript.StdOut.WriteLine "</inventory>"


