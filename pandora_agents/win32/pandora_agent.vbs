' +---------------------------------------------------------------
' | Agente Windows para Pandora | Pandora Agent for Windows
' | Este codigo ha sido desarrollado por:
' | This code has beed coded by:
' |   2004-2006, Sancho Lerena <slerena@gmail.com>
' |   2004-2005, Sergio Iglesias <sergio@genterara.com>
' | Este codigo esta distribuido y protegido bajo la licencia GPL.
' | This code is distributed and protected under GPL licence.
' ----------------------------------------------------------------
version = "1.2a for Windows"

' ====================================
' Configuracion del agente
' ====================================
' Global vars

dim PANDORA_HOME 
dim CONFIG_FILE 
dim fichero_log 
dim debug_mode

PANDORA_HOME = "c:\pandora\"
CONFIG_FILE = PANDORA_HOME & "pandora_agent.conf"
fichero_log = PANDORA_HOME & "pandora_agent.log"
debug_mode = 0

' ======================================================
' Comprobacion de version de WSH y existencia de md5.exe
' ======================================================
check_init(CONFIG_FILE)
Randomize ' Generamos un numero de serie pseudoaleatorio con la funcion rand

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
 if Not (linea = Empty) Then 'validamos que no es una linea en blanco
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
' Debug mode init
if debug_mode Then
	Set fs = CreateObject("Scripting.FileSystemObject")
	Set a = fs.OpenTextFile(fichero_log,8, true)
	texto = "DEBUG Mode: Starting Pandora Agent " & version & " execution " & vbcrlf
	texto = texto & "DEBUG Mode: Reading startup variables..."  &vbcrlf 
	texto = texto & "Home Path : " & PANDORA_HOME & vbcrlf 
	texto = texto &"Hostname  : " & NOMBRE_HOST & vbcrlf 
	texto = texto &"Server    : " & SERVER & vbcrlf 
	texto = texto &"ServerPath: " & SERVER_IN & vbcrlf 
	texto = texto &"TempPath  : " & TEMPORAL & vbcrlf 
	texto = texto &"Interval  : " & INTERVALO & vbcrlf 
	texto = texto & "PrivateKey: " & PRIVATE_KEY & vbcrlf 
	wScript.echo texto
	a.WriteLine("DEBUG Starting Pandora Agent " & version)
	a.WriteLine("Home Path : " & PANDORA_HOME)
	a.WriteLine("Hostname  : " & NOMBRE_HOST)
	a.WriteLine("Server    : " & SERVER)
	a.WriteLine("ServerPath: " & SERVER_IN)
	a.WriteLine("TempPath  : " & TEMPORAL)
	a.WriteLine("Interval  : " & INTERVAL)
	a.WriteLine("PrivateKey: " & PRIVATE_KEY)
	a.Close
End if
	
do ' Main loop
	' Checks for pscp proccess hang
	existe = proceso ("pscp.exe")
	if existe then
		wScript.echo "Another instance of PSCP detected or delayed PSCP in memory, aborting"
		debug_mode = 1 ' In this case, debug force to log this error
		debug_log fichero_log, "Another instance of PSCP detected or delayed PSCP in memory "
		wScript.Quit
	end if
	
	' =================================================================
	' Preparacion Entorno: Variables de ficheros, temporales, etc
	' =================================================================
	' Definicion de algunas variables, es VB, no haria falta, pero somos muy buenos programadores :-P
	Dim s, datos, memoria_fisica, memoria_virtual
	Dim fichero_datos, fichero_md5
	Dim anio, mes, dia, hora
	numeroProc = 0	
	serie= Int((100000 - 1 + 1) * Rnd + 1)
	' Construimos fecha y hora
	anio = Year(Now())
	mes = Month (Now())
	dia = Day (Now())
	hora = Time()

	' Inicializacion de los nombres de ficheros de datos y checksum
	fichero_datos = TEMPORAL&"/"&NOMBRE_HOST&"."&serie&".data"
    fichero_md5 = TEMPORAL&"/"&NOMBRE_HOST&"."&serie&".checksum"
    fichero_wild = TEMPORAL&"/"&NOMBRE_HOST&"."&serie&".*"

	' ====================================
	' OS Info collection
	' ====================================	
	' Obtencion de la propia plataforma Windows
	Set WshShell = WScript.CreateObject("WScript.Shell")
	Set WshSysEnv = WshShell.Environment("SYSTEM")
	strOS = WshSysEnv("OS")
	strVersionNumber = WshShell.RegRead("HKLM\Software\Microsoft\"  & "Windows NT\CurrentVersion\CurrentVersion")
	strServicePack = WshShell.RegRead("HKLM\Software\Microsoft\" & "Windows NT\CurrentVersion\CSDVersion")
	strActualOS = strOS & ", " & strVersionNumber & ",  " & strServicePack

	' ========================================================================
	' Begin XML construction (agent_data header)
	' ========================================================================
	' Cabecera del XML, conteniendo version, timestamp y otros datos generales del sistema
	' Como el intervalo, la version SO windows, la version del agente, etv
	s = "<agent_data os_name='"&strOS&"' os_version='"&strActualOS&"' intervalo='"&INTERVALO&"' version='"&version&"' timestamp='" & anio & "/" & mes & "/" & dia & " " & hora & "' agent_name='"&NOMBRE_HOST&"'>" & vbcrlf
	
	' =====================================================================
	' Module parser
	' =====================================================================
	debug_log fichero_log, "*BEGIN PARSING MODULES"
	Set ts = fs.OpenTextFile(CONFIG_FILE)
    	'Loop while not at the end of the file.
    	Do While Not ts.AtEndOfStream          
		linea = ts.ReadLine
		if Not (linea = Empty) Then	'validamos que no es una linea en blanco	
			arrContents = Split(linea, " ") 'dividimos la linea en trozos
			'=============================================================			
			'Module analyzer
			'=============================================================
			' Parse 1th line (module_begin) token
			if (lcase(arrContents(0)) = "module_begin") Then
				es_servicio = 0
				es_proceso = 0
				'Parse 2th line (could contain spaces between words!)
				'2th linea contains NAME of module
				linea_2 = ts.ReadLine
				contenidos_2 = Split(linea_2, " ")
				tamanio=UBound(contenidos_2)
				i = 1
				nombre = ""
				do while tamanio >= i
					if (nombre = Empty) then
						nombre = contenidos_2(i)
					else
						nombre = nombre & " " & contenidos_2(i) 
					end if
					i = i +1
				loop
				debug_log fichero_log, chr(9) & "--" & vbcrlf & chr(9) & "Module name: " & nombre

				' Parse 3th line (module TYPE)
				linea_3 = ts.ReadLine
				contenidos_3 = Split(linea_3, " ")
				tipo = contenidos_3(1)
				'debug_mode introduzco el tipo de modulo
				debug_log fichero_log, chr(9) & "Module type : " & tipo

				' Parse 4th linea (could contain spaces between words!)
				' 4th line contains process/service names
				linea_4 = ts.ReadLine
				contenidos_4 = Split (linea_4, " ")
				tamanio=UBound(contenidos_4)
				i = 1
				temp4 = ""
				do while tamanio >= i
					if (temp4 = Empty) then
						temp4 = contenidos_4(i)
					else
						temp4 = temp4 & " " & contenidos_4(i) 
					end if
					i = i +1
				loop
				contenidos_4(1)=temp4
				debug_log fichero_log, chr(9) & "Content search for : " & temp4

				' ================================================
				' module_service
				' ================================================
				if (lcase(contenidos_4(0)) = "module_service") Then
					es_servicio = 1
					nombre_servicio = contenidos_4(1)
					debug_log fichero_log, chr(9) & "Service Module: " & nombre_servicio
					existe = servicio (nombre_servicio) ' Check service function
					s = render_output (s, nombre, "generic_proc", existe)
				end if

				' ================================================
				' module_process
				' ================================================	
				if (lcase(contenidos_4(0)) = "module_process") Then
					es_proceso = 1
					nombre_proceso = contenidos_4(1)
					existe = proceso (nombre_proceso)
					debug_log fichero_log, chr(9) & "Process module: " & nombre_proceso
					s = render_output (s, nombre, "generic_proc", existe)
				end if

				' ==============================================================
				' module_system mem_free | proc_total | disk_free 
				' ==============================================================
				if (lcase(contenidos_4(0)) = "module_system") Then
					es_sistema = 1
					dato_sistema = contenidos_4(1)
					debug_log fichero_log, chr(9) & "Internal system module " & dato_sistema
					'==================================================
					' Internal module: Freemem 
					'==================================================	
					if (dato_sistema = "mem_free") Then
							For Each objOS in GetObject("winmgmts:{impersonationLevel=impersonate}").InstancesOf ("Win32_OperatingSystem")
								mem_free = objOS.FreeVirtualMemory
							Next
							s = render_output (s, nombre, tipo, mem_free)
					end if
					'==================================================
					' Internal module: total process
					'==================================================	
					if (lcase(dato_sistema) = "proc_total") Then
						for each Process in GetObject("winmgmts:{impersonationLevel=impersonate}").InstancesOf ("Win32_process")	
							numeroProc = numeroProc + 1
						Next
						s = render_output (s, nombre, tipo, numeroProc)
					end if
					'==================================================
					' Internal module: free disk 
					'==================================================	
					if (lcase(dato_sistema) = "disk_free") Then
						Set objWMIService = GetObject("winmgmts:{impersonationLevel=impersonate}!\\.\root\cimv2")
						Set colDisks = objWMIService.ExecQuery ("Select * from Win32_LogicalDisk Where DriveType = " & "3" & "")
						For Each objDisk in colDisks
							s = render_output (s, nombre & "_" & objDisk.DeviceID , tipo, objDisk.FreeSpace)
						Next
					end if
				end if	' End of module_system

				' ================================================
				' module_file
				' ================================================
				if (lcase(contenidos_4(0)) = "module_file") Then
					fichero = contenidos_4(1)
					' Parse 5th line (module FILE)
					linea_5 = ts.ReadLine
					contenidos_5 = Split(linea_5, " ") ' 5th line is word to search
					busqueda = contenidos_5(1)						
					Set fso = CreateObject("Scripting.FileSystemObject")
					debug_log fichero_log, chr(9) & "File module: " & fichero & " token " & busqueda
					linea_encontrada = 0
					If Not fso.FileExists(fichero) Then ' If file doesnt exists		
						debug_log fichero_log, chr(9) & "Doesn't exist file " & fichero & " returning 0\n"
					else
						Set str_file = fs.OpenTextFile(fichero) ' File exists and
						Do While Not str_file.AtEndOfStream          
							linea = str_file.ReadLine
							If InStr(linea, busqueda) <> 0 Then	' Word founded !
								linea_encontrada = 1
							End If
						Loop
						str_file.Close
   					End If
					s = render_output (s, nombre, tipo, linea_encontrada)
				end if ' Fin de busqueda de array
		
				' ================================================
				' module_exec
				' ================================================
				if (lcase(contenidos_4(0)) = "module_exec") Then
					ejecucion = contenidos_4(1)			
					debug_log fichero_log, chr(9) & "Exec module: " & ejecucion
					Set objFSO = CreateObject("Scripting.FileSystemObject")
					strFileName = objFSO.GetTempName
					strFullName = objFSO.BuildPath(temporal, strFileName)
					runCmd ejecucion & " >> " & strFullName
					Set objFile = objFSO.OpenTextFile(strFullName)
					salida = objFile.ReadLine ' Only read first line !!, be careful !
					objFile.Close
					objFSO.DeleteFile(strFullName)
					s = render_output( s, nombre, tipo, salida) 			
				end if

				' ================================================
				' module_registry
				' ================================================
				if (lcase(contenidos_4(0)) = "module_registry") Then
					entrada_registro = contenidos_4(1)
					debug_log fichero_log, chr(9) & "Registry module: " & entrada_registro
					salida = ""
					on error resume next
					salida = wshShell.regread(entrada_registro)
					on error goto 0 
					if salida = "" then
						debug_log fichero_log, chr(9) & "Error reading Registry module: " & entrada_registro
					end if
					s = render_output ( s, nombre, tipo, salida)
				end if ' end registry module

				' ================================================
				' module_eventid
				' ================================================
				if (lcase(contenidos_4(0)) = "module_eventid") Then
					id_event_log = contenidos_4(1)
					debug_log fichero_log, chr(9) & "EventLog module: " & id_event_log
					Set objWMIService = GetObject("winmgmts:{impersonationLevel=impersonate}!\\.\root\cimv2")
					Set colLoggedEvents = objWMIService.ExecQuery ("Select * from Win32_NTLogEvent Where Logfile = 'Application' and Eventcode = '" & id_event_log & "'")
					eventos = 0
					For Each objEvent in colLoggedEvents
						eventos = eventos + 1
						eventos = eventos +1
					Next 
					s = render_output (s, nombre, tipo, eventos)
				end if ' finalizo lectura de event log
			end if  	' Comienzo bucle de busqueda de modulos
		end if			' Si la linea no es linea vacia
	Loop        
	'Close the file.        
    ts.Close
	debug_log fichero_log, "*END PARSING MODULES"
	
	' Creamos el filehandle y escribimos en el archivo
	' ================================================
	'cierro el xml existente desde el principio
	s = s & "</agent_data>" & vbcrlf	
	Set fs = CreateObject("Scripting.FileSystemObject")
	Set a = fs.CreateTextFile(fichero_datos, True)
	a.WriteLine(s)
	a.Close
	
	' Creamos el MD5 utilizando una llamada a md5.exe
	' ===============================================
	runCmd PANDORA_HOME & "util\md5.exe " & fichero_datos & " > " & fichero_md5
	wscript.sleep 2000 ' espero 2 segundos
	
	' Send using SSH data file
	' ====================================
	
	' First, check if entry in registry exists for SERVER, if not, break
	salida = ""
	on error resume next
	hostkey_reg = "HKEY_CURRENT_USER\Software\SimonTatham\PuTTY\SshHostKeys\rsa2@22:" & SERVER
	salida = wshShell.regread(hostkey_reg)
	on error goto 0 
	if salida = "" then
		debug_log fichero_log, "Cannot read hostkey in registry. Please create manually using plink or read documentation"
		wScript.echo "Cannot create hostkey in registry. Please create manually using plink or read documentation"
		wScript.quit
	end if
	
	' if here, hostkey must exists, so simply connect
	set WshShell = WScript.CreateObject("WScript.Shell")
	WshShell.Run PANDORA_HOME & "util\pscp.exe -q -2 -l pandora -i " & PRIVATE_KEY & " " & fichero_wild & " pandora@" & SERVER & ":" &SERVER_IN, 0, 1
	
	' If debug mode, terminate here
	if debug_mode Then
		Set fs = CreateObject("Scripting.FileSystemObject")
		Set a = fs.OpenTextFile(fichero_log,8, true)
		texto = "DEBUG Mode: Terminating execution"
		texto = texto & vbcrlf & "Writing output to "&fichero_datos
		wScript.echo texto
		a.WriteLine("DEBUG Terminating pandora agent")
		a.Close
		wScript.quit
	End if
	
	' Delete data files
	' ==============================
	Set objFSO = CreateObject("Scripting.FileSystemObject")
	objFSO.DeleteFile(fichero_datos)
	objFSO.DeleteFile(fichero_md5)
	pausa = INTERVALO * 1000
	WScript.Sleep pausa ' sleep get value in miliseconds, not seconds
loop while debug_mode = 0  ' Forever loop 

' ====================================================================
' FUNCTION RunCmd(cmd) - Runs an internal command interpreter command. 
' ====================================================================
Function RunCmd (ByVal cmd)
	Dim sh: Set sh = CreateObject("WScript.Shell")
	sh.Run "%ComSpec% /c " & cmd ,0,1
End Function

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

' =============================================================
' FUNCTION servicio (nombre) - Return 1 if SERVICE is running
' =============================================================
Function servicio (nombre)
	servicio = 0
	for each Service in GetObject("winmgmts:{impersonationLevel=impersonate}!").InstancesOf ("Win32_service")
		if ( lcase(Service.Name) = lcase(nombre) ) then 
			if (Service.State = "Running" ) then
				servicio = 1
			end if
		end if
	Next
End Function

' =============================================================
' FUNCTION output render_output (output, name, type, data)
' =============================================================
function render_output(output, name, tipo, data)
	s = output
	s = s & "<module>" & vbcrlf
	s = s & "<name>"& name & "</name>" & vbcrlf
 s = s & "<type>" & tipo & "</type>" & vbcrlf
 s = s & "<data>" & data & "</data>" & vbcrlf
 s = s & "</module>" & vbcrlf
 render_output=s
end function

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
  text = " \n ERROR: Windows Scripting Host Incorrect version \n\n Your current version is " & wScript.Version & "\n \n Please download a latest version from http://msdn.microsoft.com/downloads/default.asp \n"
  wScript.Echo Text
  wScript.Quit
 End if
End Function
' ======================================================
' End program
' ======================================================