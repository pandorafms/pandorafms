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
import java.util.Calendar;

import android.app.Activity;
import android.app.AlarmManager;
import android.app.PendingIntent;
import android.content.Context;
import android.content.Intent;
import android.content.SharedPreferences;
import android.util.Log;

/**
 * This class provides basic functions to manage services and some received
 * data.
 * 
 * @author Miguel de Dios Matías
 * 
 */
public class Core {
	private static String TAG = "Core";

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
	 * Sets fetch frequency.
	 * 
	 * @param ctx
	 *            Application context.
	 */
	public static void setFetchFrequency(Context ctx) {
		Log.i(TAG, "Setting events fetching frequency");
		// Stops the service (if it's running)
		ctx.stopService(new Intent(ctx, PandroidEventviewerService.class));
		// Sets the launch frequency
		AlarmManager alarmM = (AlarmManager) ctx
				.getSystemService(Context.ALARM_SERVICE);

		PendingIntent pandroidService = PendingIntent.getService(ctx, 0,
				new Intent(ctx, PandroidEventviewerService.class), 0);

		int sleepTimeAlarm = convertRefreshTimeKeyToTime(ctx);

		Log.i(TAG, "sleepTimeAlarm = " + sleepTimeAlarm);

		alarmM.setRepeating(AlarmManager.RTC_WAKEUP,
				System.currentTimeMillis(), sleepTimeAlarm, pandroidService);
	}

	/**
	 * Converts the maximum time setted to filter events to a timestamp.
	 * 
	 * @param timestamp
	 * @param arrayKey
	 * @return Time in milliseconds.
	 */
	public static long convertMaxTimeOldEventValuesToTimestamp(long time,
			int arrayKey) {
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

	/**
	 * Converts chosen time from spinner to seconds (either are seconds or not)
	 * 
	 * @return
	 */
	private static int convertRefreshTimeKeyToTime(Context ctx) {
		int returnvar = 60 * 10;

		SharedPreferences preferences = ctx.getSharedPreferences(
				ctx.getString(R.string.const_string_preferences),
				Activity.MODE_PRIVATE);

		int refreshTimeKey = preferences.getInt("refreshTimeKey", 3);

		switch (refreshTimeKey) {
		case 0:
			returnvar = 30; // 30 seconds
			break;
		case 1:
			returnvar = 60; // 1 minute
			break;
		case 2:
			returnvar = 60 * 5; // 5 minutes
			break;
		case 3:
			returnvar = 60 * 10; // 10 minutes
			break;
		case 4:
			returnvar = 60 * 15; // 15 minutes
			break;
		case 5:
			returnvar = 60 * 30; // 30 minutes
			break;
		case 6:
			returnvar = 60 * 45; // 45 minutes
			break;
		case 7:
			returnvar = 3600; // 1 hour
			break;
		case 8:
			returnvar = 3600 + (60 * 30); // 1 hour and 30 minutes
			break;
		case 9:
			returnvar = 3600 * 2; // 2 hours
			break;
		case 10:
			returnvar = 3600 * 3; // 3 hours
			break;
		case 11:
			returnvar = 3600 * 4; // 4 hours
			break;
		case 12:
			returnvar = 3600 * 6; // 6 hours
			break;
		case 13:
			returnvar = 3600 * 8; // 8 hours
			break;
		case 14:
			returnvar = 3600 * 10; // 10 hours
			break;
		case 15:
			returnvar = 3600 * 12; // 12 hours
			break;
		case 16:
			returnvar = 3600 * 24; // 24 hours
			break;
		case 17:
			returnvar = 3600 * 36; // 36 hours
			break;
		case 18:
			returnvar = 3600 * 48; // 48 hours
			break;
		}

		return returnvar * 1000;
	}
}
