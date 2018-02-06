@echo off

setlocal

REM Copyright 2006-2010 Amazon.com, Inc. or its affiliates.  All Rights Reserved.  Licensed under the
REM Amazon Software License (the "License").  You may not use this file except in compliance with the License. A copy of the
REM License is located at http://aws.amazon.com/asl or in the "license" file accompanying this file.  This file is distributed on an "AS
REM IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the License for the specific
REM language governing permissions and limitations under the License.

REM Set intermediate env vars because the %VAR:x=y% notation below
REM (which replaces the string x with the string y in VAR)
REM doesn't handle undefined environment variables. This way
REM we're always dealing with defined variables in those tests.
set CHK_HOME=_%EC2_HOME%

if "%CHK_HOME:"=%" == "_" goto HOME_MISSING

"%EC2_HOME:"=%\bin\ec2-cmd" CreateNetworkInterface %*
goto DONE
:HOME_MISSING
echo EC2_HOME is not set
exit /b 1

:DONE
