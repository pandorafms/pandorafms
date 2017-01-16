/* Pandora exec module. These modules exec a command.

   Copyright (C) 2006 Artica ST.
   Written by Esteban Sanchez.

   This program is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 2, or (at your option)
   any later version.

   This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License along
   with this program; if not, write to the Free Software Foundation,
   Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
*/

#include "pandora_module_exec.h"
#include "../pandora_strutils.h"
#include <windows.h> 
#include <fstream>
#include <map>

#define BUFSIZE 4096 

using namespace Pandora;
using namespace Pandora_Strutils;
using namespace Pandora_Modules;

/** 
 * Creates a Pandora_Module_Exec object.
 * 
 * @param name Module name
 * @param exec Command to be executed.
 */
Pandora_Module_Exec::Pandora_Module_Exec (string name, string exec)
					 : Pandora_Module (name) {
	this->module_exec = "cmd.exe /c \"" + exec + "\"";
	this->proc = 0;
	this->setKind (module_exec_str);
	this->native_encoding = -1;
}

/** 
 * Creates a Pandora_Module_Exec object.
 * 
 * @param name Module name
 * @param exec Command to be executed.
 * @param native indicates an output conversion
 */
Pandora_Module_Exec::Pandora_Module_Exec (string name, string exec, string native)
					 : Pandora_Module (name) {
	this->module_exec = "cmd.exe /c \"" + exec + "\"";
	this->proc = 0;
	this->setKind (module_exec_str);
	
	if (native.c_str () != ""){
		getOutputEncoding();		
		while (native[0] == ' ') { //remove begin whitespaces
			native = native.substr( 1, native.length ());
		}
		
		if (!native.compare ("ANSI")){
			this->native_encoding = GetACP ();
		} else if (!native.compare ("OEM")){
			this->native_encoding = GetOEMCP ();
		} else if (!native.compare ("UTFLE")){
			this->native_encoding = 1200; //UTF-16 little-endian code page
		} else if (!native.compare ("UTFBE")){
			this->native_encoding = 1201; //UTF-16 big-endian code page
		} else {
			this->native_encoding = -1;
			pandoraDebug("module_native %s in %s module is not a properly encoding", 
				native.c_str (), name.c_str ());
		}
		
	} else {
		this->output_encoding = "";
	}

	/*allways change input encoding from UTF-8 to ANSI*/
	changeInputEncoding();
}

void
Pandora_Module_Exec::run () {
	STARTUPINFO         si;
	PROCESS_INFORMATION pi;
	DWORD               retval, dwRet;
	SECURITY_ATTRIBUTES attributes;
	HANDLE              out, new_stdout, out_read, job;
	string              working_dir;

	try {
		Pandora_Module::run ();
	} catch (Interval_Not_Fulfilled e) {
		this->has_output = false;
		return;
	}

	/* Set the bInheritHandle flag so pipe handles are inherited. */
	attributes.nLength = sizeof (SECURITY_ATTRIBUTES); 
	attributes.bInheritHandle = TRUE; 
	attributes.lpSecurityDescriptor = NULL; 

	/* Create a job to kill the child tree if it become zombie */
	/* CAUTION: In order to compile this, WINVER should be defined to 0x0500.
	   This may need no change, since it was redefined by the 
	   program, but if needed, the macro is defined 
	   in <windef.h> */
	job = CreateJobObject (&attributes, this->module_name.c_str ());
	if (job == NULL) {
		pandoraLog ("CreateJobObject bad. Err: %d", GetLastError ());
		this->has_output = false;
		return;
	}

	/* Get the handle to the current STDOUT. */
	out = GetStdHandle (STD_OUTPUT_HANDLE); 

	if (! CreatePipe (&out_read, &new_stdout, &attributes, 0)) {
		pandoraLog ("CreatePipe failed. Err: %d", GetLastError ());
		this->has_output = false;

		CloseHandle (job);
		return;
	}

	/* Ensure the read handle to the pipe for STDOUT is not inherited */
	SetHandleInformation (out_read, HANDLE_FLAG_INHERIT, 0);

	/* Set up members of the STARTUPINFO structure. */
	ZeroMemory (&si, sizeof (si));
	GetStartupInfo (&si);

	si.cb = sizeof (si);
	si.dwFlags     = STARTF_USESTDHANDLES | STARTF_USESHOWWINDOW;
	si.wShowWindow = SW_HIDE;
	si.hStdError   = new_stdout;
	si.hStdOutput  = new_stdout;

	/* Set up members of the PROCESS_INFORMATION structure. */
	ZeroMemory (&pi, sizeof (pi));
	pandoraDebug ("Executing: %s", this->module_exec.c_str ());
	
	/* Set the working directory of the process. It's "utils" directory
	   to find the GNU W32 tools */
	working_dir = getPandoraInstallDir () + "util\\";
	
	/* Create the child process. */
	if (! CreateProcess (NULL, (CHAR *) this->module_exec.c_str (), NULL,
			     NULL, TRUE, CREATE_SUSPENDED | CREATE_NO_WINDOW, NULL,
			     working_dir.c_str (), &si, &pi)) {
		pandoraLog ("Pandora_Module_Exec: %s CreateProcess failed. Err: %d",
			    this->module_name.c_str (), GetLastError ());
		this->has_output = false;

		/* Close job */
		CloseHandle (job);
	} else {
		char          buffer[BUFSIZE + 1];
		unsigned long read, avail;
	
		if (! AssignProcessToJobObject (job, pi.hProcess)) {
			pandoraLog ("Could not assigned proccess to job (error %d)",
				    GetLastError ());
		}
		ResumeThread (pi.hThread);
	
		string output;
		int tickbase = GetTickCount();
		while ( (dwRet = WaitForSingleObject (pi.hProcess, 500)) != WAIT_ABANDONED ) {
			PeekNamedPipe (out_read, buffer, BUFSIZE, &read, &avail, NULL);
			if (avail > 0) {
				ReadFile (out_read, buffer, BUFSIZE, &read, NULL);
				buffer[read] = '\0';
				output += (char *) buffer;
			}

			/* Change the output encoding */
			if (this->native_encoding != -1){
				changeOutputEncoding(&output);
			}

			if (dwRet == WAIT_OBJECT_0) { 
				break;
			} else if(this->getTimeout() < GetTickCount() - tickbase) {
				/* STILL_ACTIVE */
				TerminateProcess(pi.hProcess, STILL_ACTIVE);
				pandoraLog ("Pandora_Module_Exec: %s timed out (retcode: %d)", this->module_name.c_str (), STILL_ACTIVE);
				break;
			}
		}

		GetExitCodeProcess (pi.hProcess, &retval);

		if (retval != 0) {
			if (! TerminateJobObject (job, 0)) {
				pandoraLog ("TerminateJobObject failed. (error %d)",
					    GetLastError ());
			}
			if (retval != STILL_ACTIVE && this->proc == 0) {
				pandoraLog ("Pandora_Module_Exec: %s did not executed well (retcode: %d)",
				this->module_name.c_str (), retval);
			}
			this->has_output = false;
		}

		// Proc mode
		if (this->proc == 1) {
			if (retval == 0) {
				this->setOutput ("1");
			} else {
				this->setOutput ("0");
				this->has_output = true;
			}
		}
		// Command output mode
		else if (!output.empty()) {
			this->setOutput (output);
		} else {
			this->setOutput ("");
		}
	
		/* Close job, process and thread handles. */
		CloseHandle (job);
		CloseHandle (pi.hProcess);
		CloseHandle (pi.hThread);
	}

	CloseHandle (new_stdout);
	CloseHandle (out_read);
}

UINT Pandora_Module_Exec::getNumberEncoding (string encoding){
	
	map<string,UINT> code_pages;
	
	//Code page from https://msdn.microsoft.com/en-us/library/windows/desktop/dd317756(v=vs.85).aspx
	//The key has been copied from .NET Name column
	
	code_pages["IBM037"] 				= 37;
	code_pages["IBM437"] 				= 437;
	code_pages["IBM500"]	 			= 500;
	code_pages["ASMO-708"]	 			= 708;
	code_pages["ASMO-449+"] 			= 709; //Name of Information column
	//710 not .NET name
	code_pages["COD-720"] 				= 720;
	code_pages["IBM737"] 				= 737;
	code_pages["IBM775"] 				= 775;
	code_pages["IBM850"] 				= 850;
	code_pages["IBM852"] 				= 850;
	code_pages["IBM855"] 				= 855;
	code_pages["IBM00858"] 				= 858;
	code_pages["IBM860"] 				= 860;
	code_pages["IBM861"] 				= 861;
	code_pages["DOS-862"] 				= 862;
	code_pages["IBM863"]		 		= 863;
	code_pages["IBM864"]		 		= 864;
	code_pages["IBM865"]		 		= 865;
	code_pages["CP866"]		 			= 866;
	code_pages["IBM869"]		 		= 869;
	code_pages["IBM870"]		 		= 870;
	code_pages["WINDOWS-874"]	 		= 874;
	code_pages["CP875"]		 			= 875;
	code_pages["SHIFT_JIS"]		 		= 932;
	code_pages["GB2312"]		 		= 936;
	code_pages["KS_C_5601-1987"] 		= 949;
	code_pages["BIG5"]			 		= 950;
	code_pages["IBM1026"]		 		= 1026;
	code_pages["IBM1140"]		 		= 1140;
	code_pages["IBM1141"]		 		= 1141;
	code_pages["IBM1142"]		 		= 1142;
	code_pages["IBM1143"]		 		= 1143;
	code_pages["IBM1144"]		 		= 1144;
	code_pages["IBM1145"]		 		= 1145;
	code_pages["IBM1146"]		 		= 1146;
	code_pages["IBM1147"]		 		= 1147;
	code_pages["IBM1148"]		 		= 1148;
	code_pages["IBM1149"]		 		= 1149;
	code_pages["UTF-16"]		 		= 1200;
	code_pages["UNICODEFFFE"]	 		= 1201;
	code_pages["WINDOWS-1250"]	 		= 1250;
	code_pages["WINDOWS-1251"]	 		= 1251;
	code_pages["WINDOWS-1252"]	 		= 1252;
	code_pages["WINDOWS-1253"]	 		= 1253;
	code_pages["WINDOWS-1254"]	 		= 1254;
	code_pages["WINDOWS-1255"]	 		= 1255;
	code_pages["WINDOWS-1256"]	 		= 1256;
	code_pages["WINDOWS-1257"]	 		= 1257;
	code_pages["WINDOWS-1258"]	 		= 1258;
	code_pages["JOHAB"]			 		= 1361;
	code_pages["MACINTOSH"]	 			= 10000;
	code_pages["X-MAC-JAPANESE"] 		= 10001;
	code_pages["X-MAC-CHINESETRAD"]		= 10002;
	code_pages["X-MAC-KOREAN"]	 		= 10003;
	code_pages["X-MAC-ARABIC"]	 		= 10004;
	code_pages["X-MAC-HEBREW"]	 		= 10005;
	code_pages["X-MAC-GREEK"] 			= 10006;
	code_pages["X-MAC-CYRILLIC"] 		= 10007;
	code_pages["X-MAC-CHINESESIMP"] 	= 10008;
	code_pages["X-MAC-ROMANIAN"] 		= 10010;
	code_pages["X-MAC-UKRANIAN"] 		= 10017;
	code_pages["X-MAC-THAI"]	 		= 10021;
	code_pages["X-MAC-CE"]		 		= 10029;
	code_pages["X-MAC-ICELANDIC"] 		= 10079;
	code_pages["X-MAC-TURKISH"] 		= 10081;
	code_pages["X-MAC-CROATIAN"] 		= 10082;
	code_pages["UTF-32"]		 		= 12000;
	code_pages["UTF-32BE"]		 		= 12001;
	code_pages["X-CHINESE_CNS"]	 		= 20000;
	code_pages["X-CP20001"]		 		= 20001;
	code_pages["X-CHINESE-ETEN"] 		= 20002;
	code_pages["X-CP20003"]		 		= 20003;
	code_pages["X-CP20004"]		 		= 20004;
	code_pages["X-CP20005"]		 		= 20005;
	code_pages["X-IA5"]			 		= 20105;
	code_pages["X-IA5-GERMAN"]	 		= 20106;
	code_pages["X-IA5-SWEDISH"]	 		= 20107;
	code_pages["X-IA5-NORWEGIAN"] 		= 20108;
	code_pages["US-ASCII"]		 		= 20127;
	code_pages["X-IA5-GERMAN"]	 		= 20106;
	code_pages["X-CP20261"]		 		= 20261;
	code_pages["X-CP20259"]		 		= 20269;
	code_pages["IBM273"]		 		= 20273;
	code_pages["IBM277"]		 		= 20277;
	code_pages["IBM278"]		 		= 20278;
	code_pages["IBM280"]		 		= 20280;
	code_pages["IBM284"]		 		= 20284;
	code_pages["IBM285"]		 		= 20285;
	code_pages["IBM290"]		 		= 20290;
	code_pages["IBM297"]		 		= 20297;
	code_pages["IBM420"]		 		= 20420;
	code_pages["IBM423"]		 		= 20423;
	code_pages["IBM424"]		 		= 20424;
	code_pages["X-EBCDIC-KOREANEXTENDED"]= 20833;
	code_pages["IBM-THAI"]		 		= 20838;
	code_pages["KOI8-R"]		 		= 20866;
	code_pages["IBM871"]		 		= 20871;
	code_pages["IBM880"]		 		= 20880;
	code_pages["IBM905"]		 		= 20905;
	code_pages["IBM00924"]		 		= 20924;
	code_pages["EUC-JP"]		 		= 20932;
	code_pages["X-CP20936"]		 		= 20936;
	code_pages["X-CP20949"]		 		= 20949;
	code_pages["CP1025"]		 		= 21025;
	//21027 code page is deprecated
	code_pages["KOI8-U"]		 		= 21866;
	code_pages["ISO-8859-1"] 		= 28591;
	code_pages["ISO-8859-2"] 		= 28592;
	code_pages["ISO-8859-3"] 		= 28593;
	code_pages["ISO-8859-4"] 		= 28594;
	code_pages["ISO-8859-5"] 		= 28595;
	code_pages["ISO-8859-6"] 		= 28596;
	code_pages["ISO-8859-7"] 		= 28597;
	code_pages["ISO-8859-8"] 		= 28598;
	code_pages["ISO-8859-9"] 		= 28599;
	code_pages["ISO-8859-13"] 		= 28603;
	code_pages["ISO-8859-15"] 		= 28605;
	code_pages["X-EUROPA"]	 		= 29001;
	code_pages["ISO-8859-8-I"] 		= 39598;
	code_pages["ISO-2022-JP"] 		= 50220;
	code_pages["CSISO2022JP"] 		= 50221;
	code_pages["ISO-2022-JP"] 		= 50222;
	code_pages["ISO-2022-KR"] 		= 50225;
	code_pages["X-CP50227"] 		= 50227;
	//50229 not .NET name
	//50930 not .NET name
	//50933 not .NET name
	//50931 not .NET name
	//50935 not .NET name
	//50936 not .NET name
	//50937 not .NET name
	//50939 not .NET name
	code_pages["EUC-JP"]	 		= 51932;
	code_pages["EUC-CN"]	 		= 51936;
	code_pages["EUC-KR"]	 		= 51949;
	//51950 not .NET name
	code_pages["HZ-GB-2312"] 		= 52936;
	code_pages["X-ISCII-DE"] 		= 57002;
	code_pages["X-ISCII-BE"] 		= 57003;
	code_pages["X-ISCII-TA"] 		= 57004;
	code_pages["X-ISCII-TE"] 		= 57005;
	code_pages["X-ISCII-AS"] 		= 57006;
	code_pages["X-ISCII-OR"] 		= 57007;
	code_pages["X-ISCII-KA"] 		= 57008;
	code_pages["X-ISCII-MA"] 		= 57009;
	code_pages["X-ISCII-GU"] 		= 57010;
	code_pages["X-ISCII-PA"] 		= 57010;
	code_pages["UTF-7"] 			= 65000;
	code_pages["UTF-8"] 			= 65001;
	
	for (int i = 0; i < encoding.length(); i++){
		encoding[i] = toupper(encoding[i]);
	}
	if (code_pages.count(encoding)){
		return code_pages[encoding];
	} else{
		return 0;
	}
}

void Pandora_Module_Exec::getOutputEncoding(){
	
	string       buffer, filename;
	int pos;
	filename = Pandora::getPandoraInstallDir ();
	filename += "pandora_agent.conf";

	ifstream file;
	file.open (filename.c_str ());
	this->output_encoding = "ISO-8859-1"; //by default
	bool token_found = false;
	
		while (!file.eof () && !token_found) {
		/* Set the value from each line */
			getline (file, buffer);
		
		/* Ignore blank or commented lines */
			if (buffer[0] != '#' && buffer[0] != '\n' && buffer[0] != '\0' && buffer[0] != 'm') {
				/*Check if is the encoding line*/
				pos = buffer.find("encoding");
				if (pos != string::npos){
					this->output_encoding = "";		
					this->output_encoding = buffer.substr(pos+9);
					token_found = true;
				}
			}
		}
	file.close();
}

void Pandora_Module_Exec::changeInputEncoding(){
	
	int size_wchar = MultiByteToWideChar( CP_UTF8 , 0 , this->module_exec.c_str () , -1, NULL , 0 );
	wchar_t* wstr = new wchar_t[size_wchar];
	if (size_wchar != 0){
		MultiByteToWideChar( CP_UTF8 , 0 , this->module_exec.c_str () , -1, wstr , size_wchar );
		char buf[BUFSIZE + 1];
		wcstombs(buf, wstr, size_wchar);
		buf[size_wchar] = '\0';
		this->module_exec = buf;
	}
	delete[] wstr;
}

void Pandora_Module_Exec::changeOutputEncoding(string * string_change){
	
	//first change: from native encoding to system encoding
	UINT cp_output = getNumberEncoding(this->output_encoding);
	if (cp_output != 0) {
		int size_wchar = MultiByteToWideChar( this->native_encoding , 0 , string_change->c_str() , -1, NULL , 0 );
		wchar_t* wstr = new wchar_t[size_wchar];
		if (size_wchar != 0){
			MultiByteToWideChar( this->native_encoding , 0 , string_change->c_str() , -1, wstr , size_wchar );
			//second change: from system encoding to output encoding
			int size_schar = WideCharToMultiByte( cp_output, 0, wstr, -1, NULL, 0, NULL, NULL);
			char* sstr = new char[size_schar];
			if (size_schar != 0){
				WideCharToMultiByte( cp_output, 0, wstr, -1, sstr, size_schar, NULL, NULL);
				* string_change = sstr;
			}
			delete[] sstr;
		}
		delete[] wstr;
	} else {
		pandoraDebug ("Cannot find code page of encoding: %s", this->output_encoding.c_str ());
	}
}			


