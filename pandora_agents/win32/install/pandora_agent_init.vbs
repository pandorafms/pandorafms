' +---------------------------------------------------------------
' | Agente Windows para Pandora | Pandora Agent for Windows
' | Este codigo ha sido desarrollado por:
' | This code has beed coded by:
' |   2004, Sancho Lerena <slerena@iname.com>
' |   2004, Sergio Iglesias <sergio@genterara.com>
' | Este codigo esta distribuido y protegido bajo la licencia GPL.
' | This code is distributed and protected under GPL licence.
' ----------------------------------------------------------------

version = "1.1a_050413"
' ====================================
' Configuracion del agente
' ====================================
' Global vars

dim PANDORA_HOME 
dim CONFIG_FILE 
dim fichero_log 
dim debug_mode

PANDORA_HOME = "c:\pandora\"
CONFIG_FILE= PANDORA_HOME & "pandora_agent.conf"
fichero_log = "c:\pandora\pandora_agent.log"
debug_mode = 0

' ======================================================
' Comprobacion de version de WSH y existencia de md5.exe
' ======================================================
check_init(CONFIG_FILE)
Randomize	' Generamos un numero de serie pseudoaleatorio con la funcion rand

'===================================================================================
'lectura del fichero de configuración, para valores BASE de configuracion unicamente
'===================================================================================
'Declarar variables.        
Dim fs 
Dim ts  
Set fs = CreateObject("Scripting.FileSystemObject")

'Open file.
Set ts = fs.OpenTextFile(CONFIG_FILE)
'Loop while not at the end of the file.
Do While Not ts.AtEndOfStream          
	linea = ts.ReadLine
	if Not (linea = Empty) Then	'validamos que no es una linea en blanco
		arrContents = Split(linea, " ") 'dividimos la linea en trozos
		'====================================================================
		'validamos si es una linea de configuracion, y cogemos su informacion
		'====================================================================
		if (arrContents(0) = "server_ip") Then
			SERVER = trim(cstr(arrContents(1)))
		end if
		if (arrContents(0) = "server_path") Then
			SERVER_IN = trim(cstr(arrContents(1)))
		end if
		if (arrContents(0) = "temporal") Then
			TEMPORAL = trim(cstr(arrContents(1)))
		end if
		if (arrContents(0) = "interval") Then
			INTERVALO = trim(cstr(arrContents(1)))
		end if
		if (arrContents(0) = "host_name") Then
			NOMBRE_HOST = trim(cstr(arrContents(1)))
		end if		
		if (arrContents(0) = "private_key") Then
			PRIVATE_KEY = trim(cstr(arrContents(1)))
		end if		
		if (arrContents(0) = "debug") Then
			debug_mode = trim(cstr(arrContents(1)))
		end if		
	end if
Loop	
ts.Close
	
	' Inicializacion de los nombres de ficheros de datos y checksum
	fichero_prueba = TEMPORAL&"/"&NOMBRE_HOST&".test"
	Set fs = CreateObject("Scripting.FileSystemObject")
	Set a = fs.OpenTextFile(fichero_prueba,8, true)
	a.WriteLine("Agent " & NOMBRE_HOST &" test upload")
	a.Close
	Set WshShell = WScript.CreateObject("WScript.Shell")
	
	' First, check if entry in registry exists for SERVER, if not, make an special connection to create this one, send KEYS interactively
	salida = ""
	on error resume next
	hostkey_reg = "HKEY_CURRENT_USER\Software\SimonTatham\PuTTY\SshHostKeys\rsa2@22:" & SERVER
	salida = wshShell.regread(hostkey_reg)
	on error goto 0 
	if salida = "" then
		debug_log fichero_log, "Creating hostkey in registry"
		wScript.echo "Creating hostkey in registry"
		WshShell.Run PANDORA_HOME & "util\pscp.exe -q -2 -l pandora -i " & PRIVATE_KEY & " " & fichero_prueba & " pandora@" & SERVER & ":" &SERVER_IN, 8, 0
		WScript.Sleep 1500 ' wait 1.5 sec
		WshShell.AppActivate "pscp"   
		WshShell.SendKeys "yes" ' send YES if first time CONNECTION (hostkey accept)
		WshShell.SendKeys "{ENTER}"
	end if
	
	'Checks for pscp proccess hang, and wait untill its done
	counter = 0
	do while proceso ("pscp.exe")
		wscript.sleep 1000 ' wait 1 secs
		counter = counter + 1
		if counter > 15 then ' 15 seconds timeout for pscp shutdown
			debug_log fichero_log, "PSCP Timeout creating hostkey"
			wScript.echo "PSCP Timeout creating hostkey"
			wscript.quit
		end if
	loop

	wScript.echo "Hostkey can be readed in registry. Instalation successful"
	
' =============================================================
' FUNCTION check_init () - Check initial dependencies
' =============================================================
Function check_init (config_file)
	' Check pandora_agent.conf
	dim fso
	Set fso = CreateObject("Scripting.FileSystemObject")
	If Not fso.FileExists(config_file) Then
		wScript.Echo "ERROR: Cannot find " & config_file
		wScript.Quit
	end if
	
	dim oFileSys
	Set oFileSys=CreateObject("Scripting.FileSystemObject")
	If NOT oFileSys.FileExists(PANDORA_HOME & "util\md5.exe") then
		wScript.Echo "ERROR FATAL"& vbcrlf & "Cannot find md5.exe" & vbcrlf 
		wScript.Quit
	End If
	
	'Comprobar que ejecutamos con  v5.6 de WSH
	If CDbl(wScript.Version) < CDbl("5.6") then
		text = " \n ERROR: Windows Scripting Host Incorrect version \n\n Your actual version is  " & wScript.Version & "\n \n Please download a latest version from http://msdn.microsoft.com/downloads/default.asp \n"
		wScript.Echo Text
		wScript.Quit
	End if
End Function

' =============================================================
' FUNCTION debug_log (file_output, line_output)
' =============================================================
Sub debug_log(file_output, line_output)
	if debug_mode Then
		Set fs = CreateObject("Scripting.FileSystemObject")
		Set a = fs.OpenTextFile(file_output,8, true)
		a.WriteLine(line_output)
		a.Close
	End if
End Sub

' ================================================================
' FUNCTION proceso (nombre) - Return 1 if process given is running
' ================================================================
Function proceso (nombre)
	proceso = 0
	for each Process in GetObject("winmgmts:{impersonationLevel=impersonate}").InstancesOf ("Win32_process")
		if ( lcase(Process.Name) = lcase(nombre) ) then 
			proceso = 1
		end if
		'numeroProc = numeroProc + 1
	Next
End Function
