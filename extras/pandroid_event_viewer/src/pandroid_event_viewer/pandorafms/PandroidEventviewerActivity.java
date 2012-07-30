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
import java.io.Serializable;
import java.util.ArrayList;
import java.util.Calendar;
import java.util.Date;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

import android.app.Activity;
import android.app.Notification;
import android.app.NotificationManager;
import android.app.PendingIntent;
import android.app.TabActivity;
import android.content.Context;
import android.content.Intent;
import android.content.SharedPreferences;
import android.content.res.Configuration;
import android.os.AsyncTask;
import android.os.Bundle;
import android.util.Log;
import android.widget.BaseAdapter;
import android.widget.TabHost;
import android.widget.Toast;

public class PandroidEventviewerActivity extends TabActivity implements
		Serializable {
	private static String TAG = "PandroidEventviewerActivity";
	private static final int PROBLEM_NOTIFICATION_ID = 1;
	private static final long serialVersionUID = 1L;

	// Data aplication
	public ArrayList<EventListItem> eventList;
	public BaseAdapter adapter;
	public long count_events;

	// Flags
	public boolean loadInProgress;
	public boolean getNewListEvents;
	public boolean newEvents;

	// Configuration
	public boolean show_popup_info;
	public String url;
	public String user;
	public String password;

	// Parameters to search in the API
	public String agentNameStr;
	public int id_group = -1;
	public long timestamp;
	public int severity;
	public int pagination;
	public long offset;
	public int status;
	public String eventTag;
	public String eventSearch;
	public int filterLastTime;

	public boolean showOptionsFirstTime;
	public boolean showTabListFirstTime;

	@Override
	public void onCreate(Bundle savedInstanceState) {
		super.onCreate(savedInstanceState);

		SharedPreferences preferences = getSharedPreferences(
				this.getString(R.string.const_string_preferences),
				Activity.MODE_PRIVATE);

		this.show_popup_info = preferences.getBoolean("show_popup_info", true);
		this.url = preferences.getString("url", "");
		this.user = preferences.getString("user", "");
		this.password = preferences.getString("password", "");

		final TabHost tabHost = getTabHost();

		this.loadInProgress = false;

		// Check if the preferences is setted, if not show the option activity.
		if ((user.length() == 0) && (password.length() == 0)
				&& (url.length() == 0)) {
			startActivity(new Intent(this, Options.class));
			this.showOptionsFirstTime = true;
		} else {
			this.loadInProgress = true;

			this.showOptionsFirstTime = false;
			this.showTabListFirstTime = true;
		}

		this.pagination = 20;
		this.offset = 0;
		this.agentNameStr = preferences.getString("filterAgentName", "");
		this.severity = preferences.getInt("filterSeverity", -1);
		this.status = preferences.getInt("filterStatus", 3);
		this.eventTag = preferences.getString("filterTag", "");
		this.eventSearch = preferences.getString("filterEventSearch", "");
		this.filterLastTime = preferences.getInt("filterLastTime", 6);
		this.timestamp = Core.convertMaxTimeOldEventValuesToTimestamp(0,
				this.filterLastTime);

		this.eventList = new ArrayList<EventListItem>();
		this.getNewListEvents = true;

		if (!this.showOptionsFirstTime) {
			// Start the background service for the notifications
			Core.setBackgroundServiceFetchFrequency(getApplicationContext());
		}

		Intent i_main = new Intent(this, Main.class);
		i_main.putExtra("object", this);

		tabHost.addTab(tabHost
				.newTabSpec(
						getResources().getString(R.string.item_tab_main_text))
				.setIndicator(
						getResources().getString(R.string.item_tab_main_text))
				.setContent(i_main));

		Intent i_event_list = new Intent(this, EventList.class);
		i_event_list.putExtra("object", this);

		tabHost.addTab(tabHost
				.newTabSpec(
						getResources().getString(
								R.string.item_tab_event_list_text))
				.setIndicator(
						getResources().getString(
								R.string.item_tab_event_list_text))
				.setContent(i_event_list));

		tabHost.getTabWidget().getChildAt(0).getLayoutParams().height = 45;
		tabHost.getTabWidget().getChildAt(1).getLayoutParams().height = 45;
	}

	public void onResume() {
		super.onResume();

		this.getTabHost().setCurrentTab(1);

		Intent i = getIntent();
		long count_events = i.getLongExtra("count_events", 0);
		SharedPreferences preferences = getSharedPreferences(
				this.getString(R.string.const_string_preferences),
				Activity.MODE_PRIVATE);
		boolean changes = false;

		// Checks if there are filter changes
		if (!preferences.getBoolean("filterChanges", false)) {
			SharedPreferences.Editor editorPreferences = preferences.edit();
			editorPreferences.putBoolean("filterChanges", false);
			editorPreferences.commit();
			changes = true;
		}
		if (count_events > 0) {
			process_notification(i);
		}
		if (changes || this.showTabListFirstTime) {
			executeBackgroundGetEvents(false);
			this.showTabListFirstTime = false;
		}

	}

	public void onConfigurationChanged(Configuration newConfig) {
		super.onConfigurationChanged(newConfig);
	}

	public void onNewIntent(Intent intent) {
		super.onNewIntent(intent);

		process_notification(intent);
	}

	/**
	 * Processes status bar notifications' clicks.
	 * 
	 * @param intent
	 */
	private void process_notification(Intent intent) {
		int more_criticity = intent.getIntExtra("more_criticity", -1);

		CharSequence text;

		// From the notification
		switch (more_criticity) {
		case 0:
			text = getString(R.string.loading_events_criticity_0_str);
			break;
		case 1:
			text = getString(R.string.loading_events_criticity_1_str);
			break;
		case 2:
			text = getString(R.string.loading_events_criticity_2_str);
			break;
		case 3:
			text = getString(R.string.loading_events_criticity_3_str);
			break;
		case 4:
			text = getString(R.string.loading_events_criticity_4_str);
			break;
		default:
			text = getString(R.string.loading_events_criticity_2_str);
			break;
		}

		Toast toast = Toast.makeText(getApplicationContext(), text,
				Toast.LENGTH_SHORT);
		toast.show();

		// Set the same parameters to extract the events of the
		// notification.
		SharedPreferences preferences = getSharedPreferences(
				getString(R.string.const_string_preferences),
				Activity.MODE_PRIVATE);
		long timestamp_notification = preferences.getLong(
				"new_events_last_timestamp", (new Date().getTime() / 1000));
		Log.i(TAG + " process_notification_timestamp", ""
				+ timestamp_notification);
		this.timestamp = timestamp_notification;
		this.agentNameStr = preferences.getString("filterAgentName", "");
		this.id_group = preferences.getInt("filterIDGroup", 0);
		this.severity = preferences.getInt("filterSeverity", -1);
		this.status = preferences.getInt("filterStatus", 3);
		this.eventSearch = preferences.getString("filterEventSearch", "");

		this.getTabHost().setCurrentTab(1);

		this.loadInProgress = true;
		this.getNewListEvents = true;
		this.eventList = new ArrayList<EventListItem>();
		executeBackgroundGetEvents(true);
		Calendar c = Calendar.getInstance();
		long now = (c.getTimeInMillis() / 1000);
		SharedPreferences.Editor editorPreferences = preferences.edit();

		editorPreferences.putLong("new_events_last_timestamp",
				now);
		if (editorPreferences.commit()) {
			Log.i(TAG + " (filter options)",
					"Configuration changes commited (timestamp)");
		}
	}

	/**
	 * Get events from pandora console.
	 * 
	 * @throws IOException
	 *             If there is any connection problem.
	 * 
	 */
	private void getEvents() throws IOException {
		// Get total count.
		String return_api = API.getEvents(getApplicationContext(),
				agentNameStr, id_group, severity, status, eventSearch,
				eventTag, timestamp, pagination, offset, true, false);
		return_api = return_api.replace("\n", "");

		try {
			this.count_events = Long.valueOf(return_api);
		} catch (NumberFormatException e) {
			Log.e(TAG, e.getMessage());
			return;
		}

		if (this.count_events == 0) {
			return;
		}

		// Get the list of events.
		return_api = API.getEvents(getApplicationContext(), agentNameStr,
				id_group, severity, status, eventSearch, eventTag, timestamp,
				pagination, offset, false, false);
		Log.d(TAG, "List of events: " + return_api);
		Pattern pattern = Pattern
				.compile("Unable to process XML data file '(.*)'");
		Matcher matcher;
		String filename;

		boolean endReplace = false;
		int i22 = 0;
		while (!endReplace) {
			Log.i(TAG + " getEvents - loop", i22 + "");
			i22++;
			matcher = pattern.matcher(return_api);

			if (matcher.find()) {
				filename = matcher.group(1);
				return_api = return_api
						.replaceFirst(
								"Unable to process XML data file[^\n]*\n[^\n]*line 187 thread .*\n",
								"Bad XML: " + filename);
			} else {
				endReplace = true;
			}
		}

		Log.i(TAG + " getEvents - return_api", return_api);

		String[] lines = return_api.split("\n");
		newEvents = true;
		if (return_api.length() == 0) {
			Log.d("WORKS?", "NEWEVENTS = FALSE");
			newEvents = false;
			return;
		}

		for (int i = 0; i < lines.length; i++) {
			String[] items = lines[i].split(";");

			EventListItem event = new EventListItem();
			try {
				if (items[0].length() == 0) {
					event.id_event = 0;
				} else {
					event.id_event = Integer.parseInt(items[0]);
				}
				if (items[1].length() == 0) {
					event.id_agent = 0;
				} else {
					event.id_agent = Integer.parseInt(items[1]);
				}
				event.id_user = items[2];
				if (items[3].length() == 0) {
					event.id_group = 0;
				} else {
					event.id_group = Integer.parseInt(items[3]);
				}
				if (items[4].length() == 0) {
					event.status = 0;
				} else {
					event.status = Integer.parseInt(items[4]);
				}
				event.timestamp = items[5];
				event.event = items[6];
				if (items[7].length() == 0) {
					event.utimestamp = 0;
				} else {
					event.utimestamp = Integer.parseInt(items[7]);
				}
				event.event_type = items[8];
				if (items[9].length() == 0) {
					event.id_agentmodule = 0;
				} else {
					event.id_agentmodule = Integer.parseInt(items[9]);
				}
				if (items[10].length() == 0) {
					event.id_alert_am = 0;
				} else {
					event.id_alert_am = Integer.parseInt(items[10]);
				}
				if (items[11].length() == 0) {
					event.criticity = 0;
				} else {
					event.criticity = Integer.parseInt(items[11]);
				}
				event.user_comment = items[12];
				event.tags = items[13];
				event.agent_name = items[14];
				event.group_name = items[15];
				event.group_icon = items[16];
				event.description_event = items[17];
				event.description_image = items[18];
				event.criticity_name = items[19];
				event.criticity_image = items[20];

				event.opened = false;
			} catch (NumberFormatException nfe) {
				event.event = getApplication().getString(
						R.string.unknown_event_str);
				launchProblemParsingNotification();
			}
			this.eventList.add(event);
		}
	}

	/**
	 * Executes the async task of getting events.
	 * 
	 * @param underDemand
	 *            <b>true</b> if the petition was under demand.
	 */
	public void executeBackgroundGetEvents(boolean underDemand) {
		new GetEventsAsyncTask(underDemand).execute();
	}

	/**
	 * Get events from pandora console (async task)
	 * 
	 * @author Miguel de Dios Matías
	 * 
	 */
	public class GetEventsAsyncTask extends AsyncTask<Void, Void, Void> {

		private boolean underDemand;
		private boolean connectionProblem = false;

		public GetEventsAsyncTask(Boolean underDemand) {
			this.underDemand = underDemand;
		}

		@Override
		protected Void doInBackground(Void... params) {
			Log.i(TAG + " GetEventsAsyncTask", "doInBackground");
			try {
				getEvents();
			} catch (IOException e) {
				connectionProblem = true;
			}
			return null;
		}

		@Override
		protected void onPostExecute(Void result) {
			if (connectionProblem) {
				Core.showConnectionProblemToast(getApplicationContext(),
						underDemand);
			}
			Intent i = new Intent("eventlist.java");

			if (getNewListEvents) {
				loadInProgress = false;
				getNewListEvents = false;

				i.putExtra("load_more", 0);
			} else {
				i.putExtra("load_more", 1);
			}

			// adapter.notifyDataSetChanged();
			getApplicationContext().sendBroadcast(i);

		}
	}

	/**
	 * Notifies the user when there is a problem retrieving server's data.
	 */
	private void launchProblemParsingNotification() {
		String ns = Context.NOTIFICATION_SERVICE;
		NotificationManager mNotificationManager = (NotificationManager) getSystemService(ns);
		int icon = R.drawable.pandorafms_logo;
		String tickerText = getString(R.string.notification_error_parsing);
		String title = getString(R.string.pandroid_event_viewer_str);

		long when = System.currentTimeMillis();

		Notification notification = new Notification(icon, tickerText, when);
		notification.flags |= Notification.FLAG_AUTO_CANCEL;

		Context context = getApplicationContext();
		Intent notificationIntent = new Intent(this, Options.class);
		PendingIntent contentIntent = PendingIntent.getActivity(this, 0,
				notificationIntent, 0);

		notification.setLatestEventInfo(context, title, tickerText,
				contentIntent);
		mNotificationManager.notify(PROBLEM_NOTIFICATION_ID, notification);
	}
}