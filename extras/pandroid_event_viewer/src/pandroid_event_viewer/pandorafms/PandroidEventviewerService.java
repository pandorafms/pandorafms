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

import java.util.ArrayList;
import java.util.Calendar;
import java.util.List;

import org.apache.http.HttpEntity;
import org.apache.http.HttpResponse;
import org.apache.http.NameValuePair;
import org.apache.http.client.entity.UrlEncodedFormEntity;
import org.apache.http.client.methods.HttpPost;
import org.apache.http.impl.client.DefaultHttpClient;
import org.apache.http.message.BasicNameValuePair;

import android.app.Activity;
import android.app.IntentService;
import android.app.Notification;
import android.app.NotificationManager;
import android.app.PendingIntent;
import android.content.Context;
import android.content.Intent;
import android.content.SharedPreferences;
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
	public String url;
	public String user;
	public String password;

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
		checkNewEvents(getApplicationContext());

	}

	/**
	 * Checks if there are new events and, in that case, throw a notification.
	 * 
	 * @param context
	 */
	public void checkNewEvents(Context context) {
		Log.d(TAG, "Checking events at "
				+ Calendar.getInstance().getTime().toGMTString());
		if (this.url == null) {
			SharedPreferences preferences = context.getSharedPreferences(
					context.getString(R.string.const_string_preferences),
					Activity.MODE_PRIVATE);

			this.url = preferences.getString("url", "");
			this.user = preferences.getString("user", "");
			this.password = preferences.getString("password", "");
			Calendar c = Calendar.getInstance();
			long now = (c.getTimeInMillis() / 1000);
			long old_previous_filterTimestamp = preferences.getLong(
					"previous_filterTimestamp", now);

			if ((user.length() == 0) && (password.length() == 0)
					&& (url.length() == 0)) {
				return;
			}
			try {

				DefaultHttpClient httpClient = new DefaultHttpClient();
				UrlEncodedFormEntity entity;
				HttpPost httpPost;
				List<NameValuePair> parameters;
				HttpResponse response;
				HttpEntity entityResponse;
				String return_api;

				httpPost = new HttpPost(this.url + "/include/api.php");

				String parametersAPI = serializeParams2Api(context, true, false, false);
				Log.d(TAG, "Parameters checking new events: " + parametersAPI);

				// Get total count.
				parameters = new ArrayList<NameValuePair>();
				parameters.add(new BasicNameValuePair("user", this.user));
				parameters.add(new BasicNameValuePair("pass", this.password));
				parameters.add(new BasicNameValuePair("op", "get"));
				parameters.add(new BasicNameValuePair("op2", "events"));
				parameters.add(new BasicNameValuePair("other_mode",
						"url_encode_separator_|"));
				parameters.add(new BasicNameValuePair("return_type", "csv"));
				parameters.add(new BasicNameValuePair("other", parametersAPI));
				entity = new UrlEncodedFormEntity(parameters);
				httpPost.setEntity(entity);
				response = httpClient.execute(httpPost);
				entityResponse = response.getEntity();
				return_api = Core.convertStreamToString(entityResponse
						.getContent());

				return_api = return_api.replace("\n", "");
				Log.i(TAG + " checkNewEvents", return_api);
				this.count_events = new Long(return_api).longValue();

				// Check the event more critical
				if (this.count_events != 0) {
					Log.i(TAG, "There are new events");
					parameters = new ArrayList<NameValuePair>();
					parameters.add(new BasicNameValuePair("user", this.user));
					parameters
							.add(new BasicNameValuePair("pass", this.password));
					parameters.add(new BasicNameValuePair("op", "get"));
					parameters.add(new BasicNameValuePair("op2", "events"));
					parameters.add(new BasicNameValuePair("other_mode",
							"url_encode_separator_|"));
					parameters
							.add(new BasicNameValuePair("return_type", "csv"));
					parameters.add(new BasicNameValuePair("other",
							serializeParams2Api(context, false, true, true)));
					entity = new UrlEncodedFormEntity(parameters);
					httpPost.setEntity(entity);
					response = httpClient.execute(httpPost);
					entityResponse = response.getEntity();
					return_api = Core.convertStreamToString(entityResponse
							.getContent());
					return_api = return_api.replace("\n", "");
					this.more_criticity = new Integer(return_api).intValue();
					notificationEvent(context);
				} else {
					this.more_criticity = -1;

					// Restore timestamp
					SharedPreferences.Editor editorPreferences = preferences
							.edit();
					editorPreferences.putLong("previous_filterTimestamp",
							old_previous_filterTimestamp);
					editorPreferences.commit();
				}

			} catch (Exception e) {
				Log.e(TAG + " EXCEPTION checkNewEvents", e.getMessage());
				return;
			}
		}
		Log.d(TAG, "Check finished at "
				+ Calendar.getInstance().getTime().toGMTString());
	}

	/**
	 * Builds an api call from all filter parameters
	 * 
	 * @param context
	 * @param total
	 *            Activate if you want don't want to get details, but a count.
	 * @param more_criticity
	 *            Activate if you want to get the more critical level of events.
	 *            <b>NOTE:</b> Only one can be activated at once.
	 * @return
	 */
	public String serializeParams2Api(Context context, boolean total,
			boolean more_criticity, boolean updateTime) {
		SharedPreferences preferences = context.getSharedPreferences(
				context.getString(R.string.const_string_preferences),
				Activity.MODE_PRIVATE);

		String filterAgentName = preferences.getString("filterAgentName", "");

		int idGroup = preferences.getInt("filterIDGroup", 0);
		Log.d(TAG, "idGroup: " + idGroup);
		int filterSeverity = preferences.getInt("filterSeverity", -1);
		int filterStatus = preferences.getInt("filterStatus", 3);
		String filterEventSearch = preferences.getString("filterEventSearch",
				"");

		Calendar c = Calendar.getInstance();
		long now = (c.getTimeInMillis() / 1000);
		long filterTimestamp = preferences.getLong("filterTimestamp", now);
		if (updateTime) {
			SharedPreferences.Editor editorPreferences = preferences.edit();
			// Save for the next execution
			editorPreferences.putLong("filterTimestamp", now);
			// Save the previous for the list.
			editorPreferences.putLong("previous_filterTimestamp",
					filterTimestamp);
			if (editorPreferences.commit()) {
				Log.i(TAG + " (filter options)",
						"Configuration changes commited (timestamp)");
			} else {
				Log.e(TAG + " (filter options)",
						"Configuration changes not commited");
			}
		}
		String totalStr = (total) ? "total" : "-1";
		if (more_criticity) {
			totalStr = "more_criticity";
		}
		return Core.serializeParams2Api(new String[] { ";", // Separator for the
															// csv
				Integer.toString(filterSeverity), // Criticity or severity
				filterAgentName, // The agent name
				"", // Name of module
				"", // Name of alert template
				"", // Id user
				Long.toString(filterTimestamp), // The minimun timestamp
				"", // The maximum timestamp
				String.valueOf(filterStatus), // The status
				filterEventSearch, // The free search in the text event
									// description.
				Integer.toString(20), // The pagination of list events
				Long.toString(0), // The offset of list events
				totalStr, // Count or show
				Integer.toString(idGroup), // Group ID
		});
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
					new Long(this.count_events).toString());
			break;
		case 1:
			icon = R.drawable.criticity_1;
			tickerText = context.getString(
					R.string.notification_criticity_1_str).replace("%s",
					new Long(this.count_events).toString());
			break;
		case 2:
			icon = R.drawable.criticity_2;
			tickerText = context.getString(
					R.string.notification_criticity_2_str).replace("%s",
					new Long(this.count_events).toString());
			break;
		case 3:
			icon = R.drawable.criticity_3;
			tickerText = context.getString(
					R.string.notification_criticity_3_str).replace("%s",
					new Long(this.count_events).toString());
			break;
		case 4:
			icon = R.drawable.criticity_4;
			tickerText = context.getString(
					R.string.notification_criticity_4_str).replace("%s",
					new Long(this.count_events).toString());
			break;
		default:
			icon = R.drawable.criticity_default;
			tickerText = context.getString(
					R.string.notification_criticity_2_str).replace("%s",
					new Long(this.count_events).toString());
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
		notificationIntent.putExtra("count_events", this.count_events);
		notificationIntent.putExtra("more_criticity", this.more_criticity);

		PendingIntent contentIntent = PendingIntent.getActivity(context, 0,
				notificationIntent, 0);

		CharSequence title = context
				.getString(R.string.pandroid_event_viewer_str);

		notification.setLatestEventInfo(context, title, tickerText,
				contentIntent);
		Log.i(TAG, "Launching notification");
		mNotificationManager.notify(NOTIFICATION_PANDROID_EVENT_VIEWER,
				notification);
	}

}
