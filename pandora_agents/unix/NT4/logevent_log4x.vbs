' --------------------------------------------------------------
' WMI Log Event Parser for Windows
' Used as Plugin in Pandora FMS Monitoring System
' Written by Sancho Lerena <slerena@gmail.com> 2010
' Licensed under BSD Licence
' --------------------------------------------------------------

' This plugin uses three parameters:
'
' module_name : Module name to be reported at pandora, p.e: Event_Application
' logfile : Windows event logfile: Application, System, Security...
' interval: Should be the same interval agent has, p.e: 300 (seconds)

' Code begins here

' Take args from command line
if (Wscript.Arguments.Count = 0) then
	WScript.Quit	
end if

On Error Resume Next
cfg_module_name = Wscript.Arguments(0)
cfg_logfile = Wscript.Arguments(1)
cfg_interval = Wscript.Arguments(2)
strComputer = "."

MyDate = dateAdd("s", -cfg_interval, Now) ' Latest X seconds

Set dtmStartDate = CreateObject("WbemScripting.SWbemDateTime")

CONVERT_TO_LOCAL_TIME = TRUE

DateToCheck = CDate(MyDate)
dtmStartDate.SetVarDate DateToCheck, CONVERT_TO_LOCAL_TIME

WMI_QUERY = "Select * from Win32_NTLogEvent Where Logfile = '" & cfg_logfile & "' AND TimeWritten >= '" & dtmStartDate & "'"

' DEBUG
'wscript.StdOut.WriteLine dtmStartDate
'wscript.StdOut.WriteLine WMI_QUERY

Set objWMIService = GetObject("winmgmts:" _
    & "{impersonationLevel=impersonate}!\\" & strComputer & "\root\cimv2")
Set colEvents = objWMIService.ExecQuery (WMI_QUERY) 

'The XML files need the have the fields SEVERITY, MESSAGE and
'STACKTRACE. These are the fields that are often used when logging with
'log4j. Just in case, the severity field can have the following values:
'TRACE, DEBUG, INFO, WARN, ERROR, FATAL. The "message" field is just

For Each objEvent in colEvents

    if (objEvent.Type = "0") then
		severity = "FATAL"
	end if
	
	if (objEvent.Type = "1") then
		severity = "ERROR"
	end if
	
	if (objEvent.Type = "2") then
		severity = "WARN"
	end if
	
	if (objEvent.Type >= "3") then
		severity = "INFO"
	end if
	
	stacktrace = "Category: " & objEvent.CategoryString & ", Event Code: " & objEvent.EventCode & ", Source Name: " & objEvent.SourceName & ", LogFile: " & cfg_logfile

    event_message = objEvent.Message
	Wscript.StdOut.Write "<module>"
	Wscript.StdOut.Write "<name><![CDATA[" & cfg_module_name & "]]></name>"
	Wscript.StdOut.Write "<type>log4x</type>"
	Wscript.StdOut.Write "<severity>" & severity & "</severity>"

        if (event_message = "") then
            Wscript.StdOut.Write "<message></message>"
        else
	    Wscript.StdOut.Write "<message><![CDATA[" & event_message & "]]></message>"
	end if

        if (stacktrace = "") then
            Wscript.StdOut.Write "<stacktrace></stacktrace>"
        else
	    Wscript.StdOut.Write "<stacktrace><![CDATA[" & stacktrace & "]]></stacktrace>"
	end if

	Wscript.StdOut.WriteLine "</module>"        
        Wscript.StdOut.flush
Next

' Code ends here
