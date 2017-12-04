@echo off

setlocal

REM Set intermediate env vars because the %VAR:x=y% notation below
REM (which replaces the string x with the string y in VAR)
REM doesn't handle undefined environment variables. This way
REM we're always dealing with defined variables in those tests.
SET CHK_SERVICE_HOME=_%AWS_CLOUDWATCH_HOME%
SET SERVICE_HOME=%AWS_CLOUDWATCH_HOME%

if "%CHK_SERVICE_HOME:"=%" == "_" goto SERVICE_HOME_MISSING

SET SERVICE_HOME="%SERVICE_HOME:"=%"

:ARGV_LOOP
IF (%1) == () GOTO ARGV_DONE
REM Get around strange quoting bug
SET ARGV=%ARGV% %1
SHIFT
GOTO ARGV_LOOP
:ARGV_DONE

REM run
call %SERVICE_HOME%\bin\service.cmd %ARGV%
goto DONE

:SERVICE_HOME_MISSING
echo AWS_CLOUDWATCH_HOME is not set
exit /b 1

:DONE
endlocal

REM Restore original echo state
%ECHO_STATE%