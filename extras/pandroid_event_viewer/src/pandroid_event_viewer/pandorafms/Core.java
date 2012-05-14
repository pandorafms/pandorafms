/*
Pandora FMS - http://pandorafms.com

==================================================
Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
Please see http://pandorafms.org for full contribution list

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU Lesser General Public License
as published by the Free Software Foundation; version 2

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 */
package pandroid_event_viewer.pandorafms;

import java.io.BufferedReader;
import java.io.IOException;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.io.Serializable;
import java.util.Calendar;

import android.content.Context;
import android.content.Intent;

/**
 * This class provides basic functions to manage services and some received
 * data.
 * 
 * @author Miguel de Dios Matías
 * 
 */
public class Core implements Serializable {

	private static final long serialVersionUID = 7071445033114548174L;
	public Intent intent_service;

	public Core() {
		intent_service = null;
	}

	/**
	 * Starts PandroidEventviewerService.
	 * 
	 * @param context
	 */
	public void startServiceEventWatcher(Context context) {
		if (intent_service == null) {

			intent_service = new Intent(context,
					PandroidEventviewerService.class);
		}

		context.startService(intent_service);
	}

	/**
	 * Stops PandroidEventviewerService.
	 * 
	 * @param context
	 */
	public void stopServiceEventWatcher(Context context) {
		if (intent_service == null) {

			intent_service = new Intent(context,
					PandroidEventviewerService.class);
		}

		context.stopService(this.intent_service);
	}

	/**
	 * Reads from the input stream and returns a string.
	 * 
	 * @param is
	 * @return A string with all data read.
	 */
	public static String convertStreamToString(InputStream is) {
		BufferedReader reader = new BufferedReader(new InputStreamReader(is),
				8 * 1024);
		StringBuilder sb = new StringBuilder();

		String line = null;
		try {
			while ((line = reader.readLine()) != null) {
				sb.append(line + "\n");
			}
		} catch (IOException e) {
			e.printStackTrace();
		} finally {
			try {
				is.close();
			} catch (IOException e) {
				e.printStackTrace();
			}
		}

		return sb.toString();
	}

	/**
	 * Converts the maximum time setted to filter events to a timestamp.
	 * 
	 * @param timestamp
	 * @param arrayKey
	 * @return Time in milliseconds.
	 */
	public long convertMaxTimeOldEventValuesToTimestamp(long time, int arrayKey) {
		long return_var = 0;

		if (time == 0) {
			Calendar c = Calendar.getInstance();
			time = c.getTimeInMillis() / 1000;
		}

		switch (arrayKey) {
		case 0:
			return_var = time - 30 * 60;
			break;
		case 1:
			return_var = time - 60 * 60;
			break;
		case 2:
			return_var = time - 2 * (60 * 60);
			break;
		case 3:
			return_var = time - 3 * (60 * 60);
			break;
		case 4:
			return_var = time - 4 * (60 * 60);
			break;
		case 5:
			return_var = time - 5 * (60 * 60);
			break;
		case 6:
			return_var = time - 8 * (60 * 60);
			break;
		case 7:
			return_var = time - 10 * (60 * 60);
			break;
		case 8:
			return_var = time - 12 * (60 * 60);
			break;
		case 9:
			return_var = time - 24 * (60 * 60);
			break;
		case 10:
			return_var = time - 2 * (24 * 60 * 60);
			break;
		case 11:
			return_var = time - 3 * (24 * 60 * 60);
			break;
		case 12:
			return_var = time - 4 * (24 * 60 * 60);
			break;
		case 13:
			return_var = time - 5 * (24 * 60 * 60);
			break;
		case 14:
			return_var = time - 7 * (24 * 60 * 60);
			break;
		case 15:
			return_var = time - 2 * (7 * 24 * 60 * 60);
			break;
		case 16:
			return_var = time - 30 * (24 * 60 * 60);
			break;
		}

		return return_var;
	}
}
