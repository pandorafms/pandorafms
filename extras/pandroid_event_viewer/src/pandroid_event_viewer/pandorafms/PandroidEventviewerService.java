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

import java.io.IOException;
import java.util.Calendar;

import android.app.Activity;
import android.app.IntentService;
import android.app.Notification;
import android.app.NotificationManager;
import android.app.PendingIntent;
import android.content.Context;
import android.content.Intent;
import android.content.SharedPreferences;
import android.content.SharedPreferences.Editor;
import android.media.RingtoneManager;
import android.net.Uri;
import android.os.IBinder;
import android.util.Log;

/**
 * This service will launch AlarmReceiver periodically.
 * 
 * @author Miguel de Dios Matías
 * 
 */
public class PandroidEventviewerService extends IntentService {

	private static String TAG = "PandroidEventviewerService";
	private static final int NOTIFICATION_PANDROID_EVENT_VIEWER = 666;
	public long count_events;
	public int more_criticity;

	public PandroidEventviewerService() {
		super(TAG);
	}

	@Override
	public IBinder onBind(Intent intent) {
		return null;
	}

	@Override
	protected void onHandleIntent(Intent intent) {
		try {
			checkNewEvents(getApplicationContext());
		} catch (IOException e) {
			Log.e(TAG, "OnHandleIntent: " + e.getMessage());
		}

	}

	/**
	 * Checks if there are new events and, in that case, throw a notification.
	 * 
	 * @param context
	 * @throws IOException
	 *             If there is any connection problem.
	 */
	public void checkNewEvents(Context context) throws IOException {
		Log.d(TAG, "Checking events at "
				+ Calendar.getInstance().getTime().toGMTString());
		SharedPreferences preferences = context.getSharedPreferences(
				context.getString(R.string.const_string_preferences),
				Activity.MODE_PRIVATE);
		Calendar c = Calendar.getInstance();
		long now = (c.getTimeInMillis() / 1000);
		long old_previous_filterTimestamp = preferences.getLong(
				"previous_filterTimestamp", now);
		String filterAgentName = preferences.getString("filterAgentName", "");

		int idGroup = preferences.getInt("filterIDGroup", 0);
		int filterSeverity = preferences.getInt("filterSeverity", -1);
		int filterStatus = preferences.getInt("filterStatus", 3);
		String filterEventSearch = preferences.getString("filterEventSearch",
				"");
		String filterTag = preferences.getString("filterTag", "");

		c = Calendar.getInstance();
		now = (c.getTimeInMillis() / 1000);
		long filterTimestamp = preferences.getLong("filterTimestamp", now);
		String return_api = API.getEvents(this, filterAgentName, idGroup,
				filterSeverity, filterStatus, filterEventSearch, filterTag,
				filterTimestamp, 20, 0, true, false);
		return_api = return_api.replace("\n", "");
		try {
			this.count_events = Long.valueOf(return_api);
		} catch (NumberFormatException e) {
			Log.e(TAG, e.getMessage());
			return;
		}

		// Check the more critical level
		if (this.count_events != 0) {
			Log.i(TAG, "There are new events");
			return_api = API.getEvents(this, filterAgentName, idGroup,
					filterSeverity, filterStatus, filterEventSearch, filterTag,
					filterTimestamp, 20, 0, false, true);
			return_api = return_api.replace("\n", "");
			try {
				this.more_criticity = Integer.valueOf(return_api).intValue();
			} catch (NumberFormatException e) {
				Log.e(TAG, e.getMessage());
				return;
			}
			long lastCountEvents = preferences.getLong("last_count_events", 0);
			// Don't repeat the same notification
			if (lastCountEvents != count_events) {
				notificationEvent(context);
				Editor editor = preferences.edit();
				editor.putLong("last_count_events", count_events);
				editor.commit();
			}

		} else {
			this.more_criticity = -1;

			// Restore timestamp
			SharedPreferences.Editor editorPreferences = preferences.edit();
			editorPreferences.putLong("previous_filterTimestamp",
					old_previous_filterTimestamp);
			editorPreferences.commit();
		}
		Log.d(TAG, "Check finished at "
				+ Calendar.getInstance().getTime().toGMTString());
	}

	/**
	 * Launches a notification
	 * 
	 * @param context
	 */
	public void notificationEvent(Context context) {
		String ns = Context.NOTIFICATION_SERVICE;
		NotificationManager mNotificationManager = (NotificationManager) context
				.getSystemService(ns);

		mNotificationManager.cancel(NOTIFICATION_PANDROID_EVENT_VIEWER);

		int icon;
		CharSequence tickerText;

		switch (this.more_criticity) {
		case 0:
			icon = R.drawable.criticity_0;
			tickerText = context.getString(
					R.string.notification_criticity_0_str).replace("%s",
					Long.valueOf(this.count_events).toString());
			break;
		case 1:
			icon = R.drawable.criticity_1;
			tickerText = context.getString(
					R.string.notification_criticity_1_str).replace("%s",
					Long.valueOf(this.count_events).toString());
			break;
		case 2:
			icon = R.drawable.criticity_2;
			tickerText = context.getString(
					R.string.notification_criticity_2_str).replace("%s",
					Long.valueOf(this.count_events).toString());
			break;
		case 3:
			icon = R.drawable.criticity_3;
			tickerText = context.getString(
					R.string.notification_criticity_3_str).replace("%s",
					Long.valueOf(this.count_events).toString());
			break;
		case 4:
			icon = R.drawable.criticity_4;
			tickerText = context.getString(
					R.string.notification_criticity_4_str).replace("%s",
					Long.valueOf(this.count_events).toString());
			break;
		default:
			icon = R.drawable.criticity_default;
			tickerText = context.getString(
					R.string.notification_criticity_2_str).replace("%s",
					Long.valueOf(this.count_events).toString());
			break;
		}

		long when = System.currentTimeMillis();
		Notification notification = new Notification(icon, tickerText, when);
		notification.flags |= Notification.FLAG_AUTO_CANCEL;
		// Notification options
		SharedPreferences preferences = context.getSharedPreferences(
				context.getString(R.string.const_string_preferences),
				Activity.MODE_PRIVATE);
		if (preferences.getBoolean("vibration", true)) {
			Log.d(TAG, "Vibration");
			notification.defaults |= Notification.DEFAULT_VIBRATE;
		} else {
			Log.d(TAG, "No vibration");
			notification.vibrate = new long[] { 0, 0, 0, 0 };
		}
		if (preferences.getBoolean("led", false)) {
			Log.d(TAG, "Led flash");
			notification.defaults |= Notification.DEFAULT_LIGHTS;
		}
		Uri notificationSoundUri = Uri.parse(preferences.getString(
				"notification_sound_uri",
				RingtoneManager
						.getDefaultUri(RingtoneManager.TYPE_NOTIFICATION)
						.toString()));
		if (notificationSoundUri != null) {
			Log.i(TAG, "Setting sound: " + notificationSoundUri.toString());
			notification.sound = notificationSoundUri;
		} else {
			Log.e(TAG, "Ringtone's uri problem (NULL)");
		}

		Intent notificationIntent = new Intent(context,
				PandroidEventviewerActivity.class);
		notificationIntent.addFlags(Intent.FLAG_ACTIVITY_CLEAR_TOP);
		notificationIntent.addFlags(Intent.FLAG_ACTIVITY_SINGLE_TOP);
		notificationIntent.putExtra("more_criticity", this.more_criticity);

		PendingIntent contentIntent = PendingIntent.getActivity(context, 0,
				notificationIntent, 0);

		CharSequence title = context
				.getString(R.string.pandroid_event_viewer_str);

		notification.setLatestEventInfo(context, title, tickerText,
				contentIntent);
		Log.i(TAG, "Launching notification, number of events: " + count_events);
		mNotificationManager.notify(NOTIFICATION_PANDROID_EVENT_VIEWER,
				notification);
	}
}
