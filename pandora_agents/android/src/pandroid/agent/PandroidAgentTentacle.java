// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.

package pandroid.agent;

import java.io.*;
import java.net.InetSocketAddress;
import java.net.Socket;
import java.net.UnknownHostException;



class tentacle_client {

	// Return 0 when success, -1 when error
	public int tentacle_client(String args[]) {

		int port = 41121;
		String send = null;
		String serverResponse = null;
		String address = "127.0.0.1";
		byte[] data = null;
		String parameter = "";
		String filePath = null;
		boolean verbose = false;
		File file = new File("");

		for (int i=0;i<args.length;i++) {
			if(i == (args.length - 1)) {
				filePath = args[i];
			} else {
				// Get the param if is -* format or empty string otherwise
				String last_parameter = parameter;
				parameter = isParameter(args[i]);

				// If is not -* param check the previous param
				if(parameter.equals("")) {
					if(last_parameter.equals("-a")) {
						address = args[i];
					}
					else if(last_parameter.equals("-p")) {
						port = Integer.parseInt(args[i]);
					}
				}

				// The solo params are checked otherwise
				if(parameter.equals("-v")) {
					verbose = true;
				}
			}
		}

		if(filePath == null) {
			getError("Incorrect parameters. File path is necessary.");
		}

		getInfo("\n*** Starting tentacle client ***\n",verbose);

		Socket socketCliente = new Socket();

		// TODO Maybe change socket to higher timeout value

		try {
			socketCliente.connect(new InetSocketAddress(address, port), 2000);

		} catch (UnknownHostException e) {
			getError("Host doesn't exist");
			return -1;
		} catch (IOException e) {
			getError("Could not connect: The host is down");
			return -1;
        }


		DataOutputStream serverOutput = null;

		try {
			serverOutput = new DataOutputStream(socketCliente.getOutputStream());
		} catch (IOException e1) {
			getError("Could not get Data output stream");
		}

		BufferedReader serverInput = null;

		try {
			serverInput = new BufferedReader(new InputStreamReader(socketCliente.getInputStream()));
		} catch (IOException e1) {
			getError("Could not get Buffered reader");
		}


        file = new File(filePath);
        int size = (int) file.length();
        data = new byte[size];
        try {
             BufferedInputStream buf = new BufferedInputStream(new FileInputStream(file));
             buf.read(data, 0, data.length);
             buf.close();
        } catch (FileNotFoundException e) {
             getError("File not found");
        } catch (IOException e) {
             getError("Could not read from file");
        }

		getInfo("*** Start of transference ***\n",verbose);
		// Send the file name and length
		try {
			send = "SEND <" + file.getName() + "> SIZE " + Integer.toString(data.length) + '\n';
			getInfo("Client -> Server: " + send, verbose);
			serverOutput.writeBytes(send);
		} catch (IOException e) {
			getError("Could not write on server");
		}
		try {
			serverResponse = serverInput.readLine();
		} catch (IOException e) {
			getError("Could not get server response");
		}

		getInfo("Server -> Client: " + serverResponse + "\n", verbose);
		if (serverResponse != null && serverResponse.equals("SEND OK")) {
			try {
				getInfo("Client -> Server: [file data]\n", verbose);
                serverOutput.write(data);

			} catch (IOException e) {
				getError("Could not write on server");
			}
			try {
				serverResponse = serverInput.readLine();
			} catch (IOException e) {
				getError("Could not get server response");
			}

			getInfo("Server -> Client: " + serverResponse + "\n", verbose);
			if (serverResponse != null && serverResponse.equals("SEND OK")) {
				try {
					send = "QUIT\n";
					getInfo("Client -> Server: " + send, verbose);
					serverOutput.writeBytes("QUIT\n");
				} catch (IOException e) {
					getError("Could not write on server");
				}
				getInfo("*** End of transference ***\n", verbose);
			}
			else {
				getError("Bad server response, execution aborted.\n");
			}
		}
		else {
			getError("Bad server response, execution aborted.\n");
		}

		return 0;
	}

	private String isParameter(String str) {
		if(str.equals("-a") || str.equals("-p") || str.equals("-v")) {
			return str;
		}
		else {
			return "";
		}
	}

	private void getError(String error_str) {
		log("[ERROR] " + error_str + '\n');
	}

	private void getInfo(String error_str, boolean verbose) {
		if(verbose) {
			log("[INFO] " + error_str + '\n');
		}
	}

	private void log (String msg) {
		//Log.e("Tentacle",msg);
		//Context context = getApplicationContext();
		//int duration = Toast.LENGTH_SHORT;

		//Toast toast = Toast.makeText(context, msg, duration);
		//toast.show();
	}
}