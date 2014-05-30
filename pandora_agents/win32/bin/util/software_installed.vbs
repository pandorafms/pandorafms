' software_inventory.vbs
' Pandora FMS Agent Inventory Plugin for Microsoft Windows (All platfforms)
' (c) 2014 Sancho Lerena <slerena@artica.es>
' This plugin extends agent inventory feature. Only enterprise version
' ----------------------------------------------------------------
' usage: cscript //B software_inventory.vbs


Wscript.StdOut.WriteLine "<inventory>"
Wscript.StdOut.WriteLine"<inventory_module>"
Wscript.StdOut.WriteLine "<name>software</name>"
Wscript.StdOut.WriteLine "<type><![CDATA[generic_data_string]]></type>"
Wscript.StdOut.WriteLine "<datalist>"

Const HKLM = &H80000002 'HKEY_LOCAL_MACHINE 
strComputer = "." 
strKey = "SOFTWARE\Microsoft\Windows\CurrentVersion\Uninstall\" 
strEntry1a = "DisplayName" 
strEntry1b = "QuietDisplayName" 
strEntry2 = "InstallDate" 
strEntry3 = "VersionMajor" 
strEntry4 = "VersionMinor" 
strEntry5 = "EstimatedSize" 
 
Set objReg = GetObject("winmgmts://" & strComputer & _ 
 "/root/default:StdRegProv") 
objReg.EnumKey HKLM, strKey, arrSubkeys 

For Each strSubkey In arrSubkeys 

  appname = ""
  appsize = ""
  appversion = ""
  appdate = ""

  intRet1 = objReg.GetStringValue(HKLM, strKey & strSubkey, _ 
   strEntry1a, strValue1) 
  If intRet1 <> 0 Then 
    objReg.GetStringValue HKLM, strKey & strSubkey, _ 
     strEntry1b, strValue1 
  End If 
  If strValue1 <> "" Then
    appname = strValue1 
  End If 
  objReg.GetStringValue HKLM, strKey & strSubkey, _ 
   strEntry2, strValue2 
  If strValue2 <> "" Then 
    appdate = strValue2 
  End If 
  objReg.GetDWORDValue HKLM, strKey & strSubkey, _ 
   strEntry3, intValue3 
  objReg.GetDWORDValue HKLM, strKey & strSubkey, _ 
   strEntry4, intValue4 
  If intValue3 <> "" Then 
     appversion = intValue3 & "." & intValue4 
  End If 
  objReg.GetDWORDValue HKLM, strKey & strSubkey, _ 
   strEntry5, intValue5 
  If intValue5 <> "" Then 
    appsize = Round(intValue5/1024, 3) & " megabytes" 
  End If

  If appname <> "" Then 
     Wscript.StdOut.WriteLine "<data>" & appname & ";" & appversion & ";" & appdate & ";" & appsize & "</data>"
  end if
 
Next 

Wscript.StdOut.WriteLine "</datalist>"
Wscript.StdOut.WriteLine "</inventory_module>"
Wscript.StdOut.WriteLine "</inventory>"

