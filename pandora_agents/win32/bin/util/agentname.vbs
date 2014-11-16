' agentname.vbs
'
' Pandora FMS external command sample for 'agent_name_cmd' token.
' This script returns agent name like 'hostname_IPaddress'.
' (c) 2014 Junichi Satoh <junichi@rworks.jp>
' ------------------------------------------

Option Explicit

Dim objNetWork
Dim oClassSet
Dim oClass
Dim oLocator
Dim oService

Set objNetWork = WScript.CreateObject("WScript.Network")

Set oLocator = WScript.CreateObject("WbemScripting.SWbemLocator")
Set oService = oLocator.ConnectServer
Set oClassSet = oService.ExecQuery("Select * From Win32_NetworkAdapterConfiguration")

For Each oClass In oClassSet

If oClass.IPEnabled = True Then

Wscript.StdOut.WriteLine objNetWork.ComputerName & "_" & oClass.IPAddress(0)
Exit For

End If

Next
