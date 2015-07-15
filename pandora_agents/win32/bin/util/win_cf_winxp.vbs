' Pandora FMS Agent Custom Field Plugin for Microsoft Windows Xp
' (c) 2015 Borja Sanchez <fborja.sanchez@artica.es>
' This plugin extends agent inventory feature. Only enterprise version
' -------------------------------------------
' Custom fields information generator
'
' Basic structure:
' <custom_fields>
'	<field>
'		<name><![CDATA[]></name>
'		<value><![CDATA[]></value>
'	</field>
' </custom_fields>
'
' --------------------------------------------------------------------------
' Custom Fields: Windows Agent

' SET CORRECT BASE_DIR!!
Set WshShell = WScript.CreateObject("WScript.Shell")
AGENT_HOME_DIR = wshShell.ExpandEnvironmentStrings("%PROGRAMFILES%") & "\pandora_agent\"

Function BASE_DIR (subitem)
	BASE_DIR = chr(34) & AGENT_HOME_DIR & subitem & chr(34)
End Function

randomize

strComputer = "."
Set objWMIService = GetObject("winmgmts:" & "{impersonationLevel=impersonate}!\\" & strComputer & "\root\cimv2")

'---------------------------------------------------------------------
'  Parses the pandora_agent configuration file to extract the
' fields agent_name, parent_agent_name and group
'---------------------------------------------------------------------
Sub parse_conf_file ()
	
	If WScript.Arguments.Count = 0 Then
		pandora_agent_base_path = AGENT_HOME_DIR
	Else
		pandora_agent_base_path = WScript.Arguments(0)
	End If
	
	Set objFSO  = CreateObject("Scripting.FileSystemObject")

	If (Not objFSO.FileExists(pandora_agent_base_path & "pandora_agent.conf" ) ) Then
		Exit Sub
	End If
	
	Set objFile = objFSO.OpenTextFile(pandora_agent_base_path & "pandora_agent.conf", 1)

	name_flag = 1
	do until objFile.AtEndOfStream
	
		'"agent_name", "agent_name"
		'"parent", "parent_agent_name"
		'"group", "group"
		line = objFile.ReadLine
		If (Not "#" = Left(line, 1) ) Then
		
			lc = InStr(4, line," ")
			If (lc > 0 ) Then
				field = Left(line, lc -1)
				value = Mid(line, lc +1, Len(line))

				Select Case field
					Case "agent_name"
						If (Not value = "") Then
							Wscript.StdOut.WriteLine "<field>"
							Wscript.StdOut.WriteLine "<name><![CDATA[" & field & "]]></name>"
							WScript.StdOut.WriteLine "<value><![CDATA[" & replace (value,"""", "") &"]]></value>"
							Wscript.StdOut.WriteLine "</field>"	
							name_flag = 0
						End If

					Case "parent_agent_name","group"
						Wscript.StdOut.WriteLine "<field>"
						Wscript.StdOut.WriteLine "<name><![CDATA[" & field & "]]></name>"
						WScript.StdOut.WriteLine "<value><![CDATA[" & replace (value,"""", "") &"]]></value>"
						Wscript.StdOut.WriteLine "</field>"	
				End Select
			End If
		End If
	loop
	
	If (name_flag = 1) Then

		Wscript.StdOut.WriteLine "<field>"
		Wscript.StdOut.WriteLine "<name><![CDATA[agent_name]]></name>"
		Set cols = objWMIService.ExecQuery("SELECT caption FROM Win32_ComputerSystem")

		on error resume next
		flag = cols.Count
		If (err.number <> 0) Then
		  flag = true
		Else
		  flag = false
		End If
		on error goto 0

		If (NOT flag) Then
		  For Each data In cols
			Wscript.StdOut.WriteLine "<value><![CDATA[" & data.caption & "]]></value>"
		  Next
		End If
		Wscript.StdOut.WriteLine "</field>"
	End If
	
	objFile.Close

	If objFSO.FileExists(OUT_FILE) Then 
		objFSO.DeleteFile OUT_FILE
	End If 
	
End Sub



' FILE STARTS
WScript.StdOut.WriteLine "<custom_fields>"

'--------------------------------
' Custom Field: os_version
'--------------------------------
Wscript.StdOut.WriteLine "<field>"
Wscript.StdOut.WriteLine "<name><![CDATA[os_version]]></name>"

Set cols = objWMIService.ExecQuery("SELECT version from win32_operatingsystem")

For Each data In cols
  Wscript.StdOut.WriteLine "<value><![CDATA[" & data.version & "]]></value>"
Next

Wscript.StdOut.WriteLine "</field>"
'--------------------------------


'--------------------------------
' Custom Field: Domain
'--------------------------------
Wscript.StdOut.WriteLine "<field>"
Wscript.StdOut.WriteLine "<name><![CDATA[Domain]]></name>"

Set cols = objWMIService.ExecQuery("SELECT Domain FROM Win32_ComputerSystem")

For Each data In cols
  Wscript.StdOut.WriteLine "<value><![CDATA[" & data.Domain & "]]></value>"
Next

Wscript.StdOut.WriteLine "</field>"
'--------------------------------



'--------------------------------
' Custom Field: Architecture
'--------------------------------
Wscript.StdOut.WriteLine "<field>"
Wscript.StdOut.WriteLine "<name><![CDATA[Architecture]]></name>"

Set cols = objWMIService.ExecQuery("SELECT osarchitecture FROM Win32_OperatingSystem")

on error resume next
flag = cols.Count
If (err.number <> 0) Then
  flag = true
Else
  flag = false
End If
on error goto 0


If flag Then
  Wscript.StdOut.WriteLine "<value><![CDATA[32 bits]]></value>"
Else
  For Each data In cols
    If ( NOT IsNull(data.osarchitecture) ) Then
      Wscript.StdOut.WriteLine "<value><![CDATA[" & data.osarchitecture & "]]></value>"
    Else
      Wscript.StdOut.WriteLine "<value><![CDATA[32 bits]]></value>"
    End If
  Next
End If


Wscript.StdOut.WriteLine "</field>"
'--------------------------------


'--------------------------------
' Extract info
'--------------------------------
parse_conf_file
'--------------------------------


'----------------------------------------------------
' Custom Field: IP, IPv6 AND MAC -> XXX First found.
'----------------------------------------------------
Set cols = objWMIService.ExecQuery("Select * from Win32_NetworkAdapter " & _
						"Where not PNPDeviceID like 'ROOT%%' " & _
						"and not PNPDeviceID like 'SW%%' " & _
						"and not ServiceName is null " & _
						"and not ServiceName like 'vwifimp' ")
on error resume next
flag = cols.Count
If (err.number <> 0) Then
  flag = true
Else
  flag = false
End If
on error goto 0


If (NOT flag) Then
  For Each iface In cols 
  ' return model MACAddress IPAddress
    set ifaces_cfg = objWMIService.ExecQuery("Select IPAddress from Win32_NetworkAdapterConfiguration Where Caption='" & iface.caption & "'")
    for each iface_cfg in ifaces_cfg
      if ( NOT IsNull(iface_cfg.IPAddress) ) then
        on error resume next
        IP   = trim(iface_cfg.IPAddress(0))
        If ( err.number <> 0 ) Then
          IP = NULL
        End If
        MAC  = iface.MACAddress
        If ( err.number <> 0 ) Then
          MAC = NULL
        End If
        on error goto 0
      end if
    next
  Next
End If
If (NOT IsNull(IP)) Then
	WScript.StdOut.WriteLine "<field>"
	WScript.StdOut.WriteLine "<name><![CDATA[IP]]></name>"
	WScript.StdOut.WriteLine "<value><![CDATA[" & IP & "]]></value>"
	WScript.StdOut.WriteLine "</field>"
End If

If (NOT IsNull(MAC)) Then
	WScript.StdOut.WriteLine "<field>"
	WScript.StdOut.WriteLine "<name><![CDATA[MAC]]></name>"
	WScript.StdOut.WriteLine "<value><![CDATA[" & MAC & "]]></value>"
	WScript.StdOut.WriteLine "</field>"
End If

'--------------------------------


'--------------------------------
' Custom Field: Hostname
'--------------------------------
Wscript.StdOut.WriteLine "<field>"
Wscript.StdOut.WriteLine "<name><![CDATA[hostname]]></name>"

Set cols = objWMIService.ExecQuery("SELECT caption FROM Win32_ComputerSystem")

on error resume next
flag = cols.Count
If (err.number <> 0) Then
  flag = true
Else
  flag = false
End If
on error goto 0

If (NOT flag) Then
  For Each data In cols
    Wscript.StdOut.WriteLine "<value><![CDATA[" & data.caption & "]]></value>"
  Next
End If

Wscript.StdOut.WriteLine "</field>"


'--------------------------------

WScript.StdOut.WriteLine "</custom_fields>"

' FILE ENDS
