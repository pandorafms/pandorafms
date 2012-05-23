@echo off

if "%1" == "" GOTO HELP

"%ProgramFiles%\pandora_agent\util\snmpget" -M "%ProgramFiles%\pandora_agent\util\mibs" -Oveqtu -v 1 -c %1 %2 %3
goto EXIT0

:HELP
echo I need parameters.
echo getsnmp.bat community ip_address OID
goto EXIT0


:EXIT0