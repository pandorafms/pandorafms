' ====================================
' Agent install as win Service
' ====================================
dim PANDORA_HOME 
PANDORA_HOME = "c:\pandora\"

' ==============================================================================
' Dont touch below this line
' ==============================================================================

' Check if exists instsrv.exe and srvany.exe
	dim fso
	Set fso = CreateObject("Scripting.FileSystemObject")
	If Not fso.FileExists(PANDORA_HOME & "util\srvany.exe") Then
		wScript.Echo "ERROR: Cannot find SRVANY.EXE, please read documentation and FAQ"
		wScript.Quit
	end if
	If Not fso.FileExists(PANDORA_HOME & "util\instsrv.exe") Then
		wScript.Echo "ERROR: Cannot find INSTSRV.EXE, please read documentation and FAQ"
		wScript.Quit
	end if
	
runCmd PANDORA_HOME & "util\instsrv.exe PandoraAgent "& PANDORA_HOME& "util\srvany.exe"

' Modificacion del registro
Set WshShell = WScript.CreateObject("WScript.Shell")

WshShell.RegWrite "HKLM\SYSTEM\CurrentControlSet\Services\PandoraAgent\","Parameters"
WshShell.RegWrite "HKLM\SYSTEM\CurrentControlSet\Services\PandoraAgent\Parameters","Application", "REG_SZ"
WshShell.RegWrite "HKLM\SYSTEM\CurrentControlSet\Services\PandoraAgent\Parameters\Application",PANDORA_HOME & "pandora_agent.bat", "REG_SZ"
WshShell.RegWrite "HKLM\SYSTEM\CurrentControlSet\Services\PandoraAgent","Description", "REG_SZ"
WshShell.RegWrite "HKLM\SYSTEM\CurrentControlSet\Services\PandoraAgent\Description","Pandora Agent for Windows", "REG_SZ"

wScript.Echo "Instalation sucessful. Please start service now or restart"

' ======================================================
' Librerias externas
' ======================================================

Function Run (ByVal cmd)  ' Author: Christian d''Heureuse (www.source-code.biz)
   Dim sh: Set sh = CreateObject("WScript.Shell")
   Dim wsx: Set wsx = Sh.Exec(cmd)
   If wsx.ProcessID = 0 And wsx.Status = 1 Then
      ' (The Win98 version of VBScript does not detect WshShell.Exec errors)
      Err.Raise vbObjectError,,"WshShell.Exec failed."
      End If
   Do
      Dim Status: Status = wsx.Status
      'WScript.StdOut.Write wsx.StdOut.ReadAll()
      'WScript.StdErr.Write wsx.StdErr.ReadAll()
      If Status <> 0 Then Exit Do
      WScript.Sleep 10
      Loop
   Run = wsx.ExitCode
   End Function

' Runs an internal command interpreter command. Author: Christian d''Heureuse (www.source-code.biz)
Function RunCmd (ByVal cmd)
   RunCmd = Run("%ComSpec% /c " & cmd)
End Function
