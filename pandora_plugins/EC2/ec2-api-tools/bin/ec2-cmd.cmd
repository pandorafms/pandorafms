@echo off

setlocal

REM Copyright 2006-2009 Amazon.com, Inc. or its affiliates.  All Rights Reserved.  Licensed under the 
REM Amazon Software License (the "License").  You may not use this file except in compliance with the License. A copy of the 
REM License is located at http://aws.amazon.com/asl or in the "license" file accompanying this file.  This file is distributed on an "AS 
REM IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the License for the specific
REM language governing permissions and limitations under the License.

REM Set intermediate env vars because the %VAR:x=y% notation below
REM (which replaces the string x with the string y in VAR)
REM doesn't handle undefined environment variables. This way
REM we're always dealing with defined variables in those tests.
set CHK_JAVA_HOME=_%JAVA_HOME%
set CHK_EC2_HOME=_%EC2_HOME%

if "%CHK_EC2_HOME:"=%" == "_" goto EC2_HOME_MISSING
if "%CHK_JAVA_HOME:"=%" == "_" goto JAVA_HOME_MISSING 

REM If a classpath exists preserve it
SET CP="%CLASSPATH%"

REM Brute force
set CP=%CP%;%EC2_HOME%\lib\AWSEC2JavaClient-1.2ux.jar
set CP=%CP%;%EC2_HOME%\lib\AWSJavaClientRuntime-1.1.jar
set CP=%CP%;%EC2_HOME%\lib\BlockDeviceLib-1.0.jar
set CP=%CP%;%EC2_HOME%\lib\EC2CltJavaClient-1.0.jar
set CP=%CP%;%EC2_HOME%\lib\EC2ConversionLib-1.0.jar
set CP=%CP%;%EC2_HOME%\lib\EC2WsdlJavaClient-1.0.jar
set CP=%CP%;%EC2_HOME%\lib\HttpClientSslContrib-1.0.jar
set CP=%CP%;%EC2_HOME%\lib\XmlSchema-1.4.5.jar
set CP=%CP%;%EC2_HOME%\lib\activation-1.1.jar
set CP=%CP%;%EC2_HOME%\lib\bcprov-jdk15-145.jar
set CP=%CP%;%EC2_HOME%\lib\commons-cli-1.1.jar
set CP=%CP%;%EC2_HOME%\lib\commons-codec-1.4.jar
set CP=%CP%;%EC2_HOME%\lib\commons-discovery.jar
set CP=%CP%;%EC2_HOME%\lib\commons-httpclient-3.1.jar
set CP=%CP%;%EC2_HOME%\lib\commons-logging-adapters-1.1.1.jar
set CP=%CP%;%EC2_HOME%\lib\commons-logging-api-1.1.1.jar
set CP=%CP%;%EC2_HOME%\lib\ec2-api-tools-1.6.7.2.jar
set CP=%CP%;%EC2_HOME%\lib\httpclient-4.2.jar
set CP=%CP%;%EC2_HOME%\lib\httpcore-4.2.jar
set CP=%CP%;%EC2_HOME%\lib\j2ee_mail.jar
set CP=%CP%;%EC2_HOME%\lib\java-xmlbuilder-0.4.jar
set CP=%CP%;%EC2_HOME%\lib\jaxb-api.jar
set CP=%CP%;%EC2_HOME%\lib\jaxb-impl.jar
set CP=%CP%;%EC2_HOME%\lib\jaxws-api.jar
set CP=%CP%;%EC2_HOME%\lib\jdom.jar
set CP=%CP%;%EC2_HOME%\lib\jets3t-0.9.0.jar
set CP=%CP%;%EC2_HOME%\lib\jets3t-gui-0.9.0.jar
set CP=%CP%;%EC2_HOME%\lib\log4j-1.2.14.jar
set CP=%CP%;%EC2_HOME%\lib\serializer.jar
set CP=%CP%;%EC2_HOME%\lib\stax2-api-3.0.1.jar
set CP=%CP%;%EC2_HOME%\lib\woodstox-core-asl-4.0.5.jar
set CP=%CP%;%EC2_HOME%\lib\wsdl4j.jar
set CP=%CP%;%EC2_HOME%\lib\wss4j-1.5.3.jar
set CP=%CP%;%EC2_HOME%\lib\xalan.jar
set CP=%CP%;%EC2_HOME%\lib\xercesImpl.jar
set CP=%CP%;%EC2_HOME%\lib\xfire-all-1.2.6.jar
set CP=%CP%;%EC2_HOME%\lib\xfire-jsr181-api-1.0-M1.jar
set CP=%CP%;%EC2_HOME%\lib\xml-apis.jar
set CP=%CP%;%EC2_HOME%\lib\xmlsec.jar

REM Grab the class name
SET CMD=%1

REM SHIFT doesn't affect %* so we need this clunky hack
SET ARGV=%2
SHIFT
SHIFT
:ARGV_LOOP
IF (%1) == () GOTO ARGV_DONE
REM Get around strange quoting bug
SET ARG=%1
SET ARG=%ARG:"=%
SET ARGV=%ARGV% "%ARG%"
SHIFT
GOTO ARGV_LOOP
:ARGV_DONE

"%JAVA_HOME:"=%"\bin\java %EC2_JVM_ARGS% -classpath "%CP:"=%" "com.amazon.aes.webservices.client.cmd.%CMD%" %EC2_DEFAULT_ARGS% %ARGV%
goto DONE

:JAVA_HOME_MISSING
echo JAVA_HOME is not set
exit /b 1

:EC2_HOME_MISSING
echo EC2_HOME is not set
exit /b 1

:DONE
endlocal
