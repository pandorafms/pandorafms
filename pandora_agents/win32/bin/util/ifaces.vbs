' Pandora FMS Agent Inventory Plugin for Microsoft Windows (All platfforms)
' (c) 2015 Borja Sanchez <fborja.sanchez@artica.es>
' This plugin extends agent inventory feature. Only enterprise version
' --------------------------------------------------------------------------
'WMI real interfaces info
'  exlusions: 
'     VBox network interfaces
'     VMWare network interfaces
'
'nic where "guid is not null and not PNPDeviceID like 'ROOT%'"

strComputer = "."
Set objWMIService = GetObject("winmgmts:" & "{impersonationLevel=impersonate}!\\" & strComputer & "\root\cimv2")
Set colAdapters = objWMIService.ExecQuery("Select * from Win32_NetworkAdapter " & _
						"Where not PNPDeviceID like 'ROOT%%' " & _
						"and not PNPDeviceID like 'SW%%' " & _
						"and not ServiceName is null " & _
						"and not ServiceName like 'vwifimp' ")

on error resume next
flag = colAdapters.Count
If (err.number <> 0) Then
  flag = true
Else
  flag = false
End If
on error goto 0 

'Print only when there's results
If (NOT flag) Then
  Wscript.StdOut.WriteLine "<inventory>"
  Wscript.StdOut.WriteLine "<inventory_module>"
  Wscript.StdOut.WriteLine "<name>NIC</name>"
  Wscript.StdOut.WriteLine "<type><![CDATA[generic_data_string]]></type>"
  Wscript.StdOut.WriteLine "<datalist>"

  For Each iface In colAdapters 
  ' return model MACAddress IPAddress

    set ifaces_cfg = objWMIService.ExecQuery("Select ipaddress from Win32_NetworkAdapterConfiguration Where Caption='" & iface.caption & "'")
    Wscript.StdOut.Write "<data><![CDATA[" & iface.ProductName & ";" & iface.MACAddress & ";"
    for each iface_cfg in ifaces_cfg
      On Error Resume Next
      if (iface_cfg.IPAddress(0) <> "" ) then
        Wscript.StdOut.Write trim(iface_cfg.IPAddress(0))
      end if
    next
    Wscript.StdOut.WriteLine "]]></data>"
  Next

  Wscript.StdOut.WriteLine "</datalist>"
  Wscript.StdOut.WriteLine "</inventory_module>"
  Wscript.StdOut.WriteLine "</inventory>"
End If
