@ REM Helper file to help in restoring parent echo state
@ REM Variable ECHO_STATE stores parent echo state. It will be used as a command in the end of file mon-cmd.cmd to restore the echo state

@ set ECHO_STATE_FILE=%temp%\cwclitemp.txt
@ echo > %ECHO_STATE_FILE%

@ for /F "Tokens=*" %%x in ('type %ECHO_STATE_FILE%') do @set ECHO_STATE=%%x
@ set ECHO_STATE=%ECHO_STATE:ECHO is on.=echo on%
@ set ECHO_STATE=%ECHO_STATE:ECHO is off.=echo off%
@ del %ECHO_STATE_FILE%