/* Class to abstract an FTP client. It uses libcurl.
   
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
#include <iostream>
using namespace std;

#include <sys/stat.h>
#include <fcntl.h>
#include "pandora_ftp_client.h"
#include "../misc/pandora_file.h"
#include "../pandora_strutils.h"

using namespace std;
using namespace FTP;
using namespace Pandora;

/**
 * Creates a FTP client object and initialize its attributes.
 */
Pandora_Ftp_Client::Pandora_Ftp_Client ()
{
	curl = NULL;
	
	return;
}

/**
 * Destroy a FTP client object.
 *
 * It also disconnect the client from the host if connected.
 *
 * @see disconnect
 */
Pandora_Ftp_Client::~Pandora_Ftp_Client ()
{
	this->disconnect ();
	
	return;
}

/**
 * Disconnects from remote host.
 *
 * It will close all open connections and channels.
 */
void
Pandora_Ftp_Client::disconnect ()
{
	if (curl != NULL) {
		curl_easy_cleanup (curl);
		curl = NULL;
	}
}

/**
 * Connects to specified host and port using a username and a
 * password.
 *
 * @param host Host to connect to.
 * @param port Port of FTP server in host
 * @param username FTP username in server.
 * @param password Username's password in server
 */
void
Pandora_Ftp_Client::connect (const string host,
			     const int    port,
			     const string username,
			     const string password)
{
	this->username = username;
	this->password = password;
	this->host = host;
}

size_t
read_func(void *ptr, size_t size, size_t nmemb, FILE *stream)
{
	return fread (ptr, size, nmemb, stream);
}

/**
 * Copy a file using a FTP connection.
 *
 * The function receives a filename in the local filesystem and copies all
 * its content to the remote host. The remote filename will be the 
 * basename of the local file and will be copied in the remote actual
 * directory.
 *
 * @param remote_filename Remote path to copy the local file in.
 * @param filename Path to the local file.
 */
int
Pandora_Ftp_Client::ftpFileFilename (const string remote_filename,
				     const string filepath)
{
	FILE              *fd;
	string             operation1;
	string             operation2;
	struct stat        file_info;
	int                file;
	struct curl_slist *headerlist = NULL;
	string             filename;
	string             url;

	if (this->host == "")
		return UNKNOWN_HOST;
	
	filename = Pandora_File::fileName (filepath);
	
	url = "ftp://";
	url += username;
	url += ':';
	url += password;
	url += '@';
	url += host;
	url += '/';
	url += filename;
	
	file = open (filepath.c_str (), O_RDONLY);
	fstat (file, &file_info);
	close (file);
	
	fd = fopen (filepath.c_str (), "rb");

	curl_global_init (CURL_GLOBAL_ALL);
	
	this->curl = curl_easy_init ();
	if (this->curl) {

		pandoraDebug ("Copying %s to %s%s", filepath.c_str (), this->host.c_str (),
			      remote_filename.c_str ());
		
		operation1 = "RNFR " + filename;
		headerlist = curl_slist_append (headerlist, operation1.c_str ());

		operation2 = "RNTO " + remote_filename;
		headerlist = curl_slist_append (headerlist, operation2.c_str ());
		
		curl_easy_setopt (this->curl, CURLOPT_UPLOAD, 1) ;
		curl_easy_setopt (this->curl, CURLOPT_URL, url.c_str ());
		curl_easy_setopt (this->curl, CURLOPT_POSTQUOTE, headerlist);
		curl_easy_setopt (this->curl, CURLOPT_TIMEOUT, 240);
		curl_easy_setopt (this->curl, CURLOPT_FTP_RESPONSE_TIMEOUT, 60);
		curl_easy_setopt (this->curl, CURLOPT_READFUNCTION, read_func);
		curl_easy_setopt (this->curl, CURLOPT_READDATA, fd);
		curl_easy_setopt (curl, CURLOPT_INFILESIZE_LARGE,
				  (curl_off_t) file_info.st_size);
		
		this->result = curl_easy_perform (this->curl);
		
		curl_slist_free_all (headerlist);
		curl_easy_cleanup (this->curl);

		this->curl = NULL;
	}
	
	curl_global_cleanup ();
	fclose (fd);

	switch (this->result) {
	case CURLE_OK:
	case CURLE_FTP_QUOTE_ERROR: /* These error happens when FTP is in a jail.
				       Transfer was OK, moving wasn't. */
		break;
	case CURLE_COULDNT_CONNECT:
		return UNKNOWN_HOST;
		
		break;
	case CURLE_FTP_ACCESS_DENIED:
		return AUTHENTICATION_FAILED;
		
		break;
	default:
		return FTP_EXCEPTION;
	}
}

string
Pandora_Ftp_Client::getError ()
{
	string error (curl_easy_strerror (this->result));
	
	return error;
}
