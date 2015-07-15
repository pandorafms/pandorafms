' Pandora FMS Agent Inventory Plugin for Microsoft Windows (All platfforms)
' (c) 2015 Sancho Lerena <slerena@artica.es>
' (c) 2015 Borja Sanchez <fborja.sanchez@artica.es>
' This plugin extends agent inventory feature. Only enterprise version
' Warning: If the system has the WMI corrupted, call this script with nowmi argument
' ------------------------------------------------------------------------------------
on error resume next

Class ObjectList
  Public List

  Sub Class_Initialize()
    Set List = CreateObject("Scripting.Dictionary")
  End Sub

  Sub Class_Terminate()
    Set List = Nothing
  End Sub

  Function Append(Anything) 
    List.Add CStr(List.Count + 1), Anything 
    Set Append = Anything
  End Function

  Function Item(id) 
    If List.Exists(CStr(id)) Then
      Set Item = List(CStr(id))
    Else
      Set Item = Nothing
    End If
  End Function
End Class

class AppClass 
  dim InstallDate,Caption,Version,Vendor
end class

' Print the XML structure
Wscript.StdOut.WriteLine "<inventory>"
Wscript.StdOut.WriteLine "<inventory_module>"
Wscript.StdOut.WriteLine "<name>Software</name>"
Wscript.StdOut.WriteLine "<type><![CDATA[generic_data_string]]></type>"
Wscript.StdOut.WriteLine "<datalist>"

'------ Checks if an item exists on the main collection
function isItemInArray(objeto,coleccion)
  for each id in coleccion.List
    if (strComp(objeto,coleccion.List(id).caption) = 0) then
      isItemInArray=true
      exit function
    end if
  next
  isItemInArray=false
end function

'------ main collection definition
dim colObjSW : set colObjSW = new ObjectList
strComputer = "."

' Disable by arguments WMI queries - corrupted WMI host

If (not WScript.Arguments(0) = "nowmi") Then
  '------ Retrieve the WMI registers first
  Set objWMIService = GetObject("winmgmts:" & "{impersonationLevel=impersonate}!\\" & strComputer & "\root\cimv2")
  Set colSoftware = objWMIService.ExecQuery ("SELECT installstate,caption,installdate,Version,vendor FROM Win32_Product",,48)


  '------ Check all
  '-- first) add all unique WMI (unique) entries to main collector
  '-- second) add all unique REGISTRY items to main collector

  for each objSoftware in colSoftware
    if ( objSoftware.installstate = 5 ) then
      if ( isItemInArray(objSoftware.caption, colObjSW) = false ) then
        ' It doesn't exists, added.
        With colObjSW.Append(New AppClass)
          .caption = objSoftware.caption
          .InstallDate = objSoftware.InstallDate
          .version = objSoftware.version
          .vendor = objSoftware.vendor
        End with
        ' Add to XML the verified ones
        Wscript.StdOut.WriteLine "<data><![CDATA[" _
          & objSoftware.caption & ";" _ 
          & objSoftware.version _ 
          & "]]></data>"
      end if
    end if
  next
End If

' ------ Getting the REGISTRY
Const HKLM = &H80000002 'HKEY_LOCAL_MACHINE 

strKey = "SOFTWARE\Microsoft\Windows\CurrentVersion\Uninstall\" 
strEntry1a = "DisplayName" 
strEntry1b = "QuietDisplayName" 
strEntry2 = "InstallDate" 
strEntry3 = "DisplayVersion" 
 
Set objReg = GetObject("winmgmts://" & strComputer & "/root/default:StdRegProv") 
objReg.EnumKey HKLM, strKey, arrSubkeys 

For Each strSubkey In arrSubkeys 
  appname = ""
  appsize = ""
  appversion = ""
  appdate = ""

  intRet1 = objReg.GetStringValue(HKLM, strKey & strSubkey, strEntry1a, strValue1) 
  If intRet1 <> 0 Then 
    objReg.GetStringValue HKLM, strKey & strSubkey, strEntry1b, strValue1 
  End If 
  If strValue1 <> "" Then
    appname = strValue1 
  End If 
  objReg.GetStringValue HKLM, strKey & strSubkey, strEntry2, strValue2 
  If strValue2 <> "" Then 
    appdate = strValue2 
  End If 
  
  objReg.GetStringValue HKLM, strKey & strSubkey, strEntry3, intValue3 
  If intValue3 <> "" Then 
    appversion = intValue3
  End If 

  If appname <> "" Then 
    ' foreach registry item, check if exists in the main collector
    ' it it exists, it doesn't be added.
    if ( isItemInArray(appname, colObjSW) = false ) then
    ' as item doesn't exist, we add it to main collector and to XML
       With colObjSW.Append(New AppClass)
      .caption = appname
      .version = appversion
    End with
    Wscript.StdOut.WriteLine "<data><![CDATA[" & appname & ";" & appversion & "]]></data>"
    end if
  end if
next

' Closing the XML structure
Wscript.StdOut.WriteLine "</datalist>"
Wscript.StdOut.WriteLine "</inventory_module>"
Wscript.StdOut.WriteLine "</inventory>"


