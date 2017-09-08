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

import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;


public class PandroidEventviewerActivity extends TabActivity implements Serializable {
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

		// Check if the preferences are set, if not show the option activity.
		if ((user.length() == 0) && (password.length() == 0)
				&& (url.length() == 0)) {
			startActivity(new Intent(this, Options.class));
			this.showOptionsFirstTime = true;
		}
		else {
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

		tabHost.getTabWidget().getChildAt(0).getLayoutParams().height = 0;
		tabHost.getTabWidget().getChildAt(1).getLayoutParams().height = 0;
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
		//TODO retrieve time
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

//		this.getTabHost().setCurrentTab(1);

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
	 * Parses a JSON object and returns a valid event list item.
	 * For >= v7.
	 *
	 * @param item Object with the event columns.
	 *
	 * @return EventListItem
	 * @throws JSONException, NumberFormatException
	 */
	private EventListItem parseEvent (JSONObject item) throws JSONException, NumberFormatException {
		EventListItem event = new EventListItem();

		// Event id
		event.id_event = item.optInt("id_evento");
		// Agent id
		event.id_agent = item.optInt("id_agente");
		// User id
		event.id_user = item.getString("id_usuario");
		// Group id
		event.id_group = item.optInt("id_grupo");
		// Status
		event.status = item.optInt("estado");
		// Timestamp (Y-M-d H:m:s)
		event.timestamp = item.getString("timestamp");
		// Event text
		event.event = item.getString("evento");
		// Unix timestamp
		event.utimestamp = item.optInt("utimestamp");
		// Event type
		event.event_type = item.getString("event_type");
		// Module id
		event.id_agentmodule = item.optInt("id_agentmodule");
		// Alert id
		event.id_alert_am = item.optInt("id_alert_am");
		// Event priority
		event.criticity = item.optInt("criticity");
		// User comments
		event.user_comment = item.getString("user_comment");
		// Tags
		event.tags = item.getString("tags");
		// Agent name (try to use the alias)
		event.agent_name = item.optString("agent_alias", item.optString("agent_name"));
		// Group name
		event.group_name = item.optString("group_name");
		// Group icon
		event.group_icon = item.optString("group_icon");
		// Event description
		event.description_event = item.optString("description_event");
		// Event description image
		event.description_image = item.optString("img_description");
		// Event priority name
		event.criticity_name = item.optString("criticity_name");
		// Event priority image
		event.criticity_image = item.optString("img_criticy");

		event.opened = false;

		return event;
	}

	/**
	 * Parses a string array and returns a valid event list item.
	 *
	 * THIS IS AN HORRIBLE WAY TO DO THIS.
	 * Any change on the columns order on the tevento's table from the database
	 * or any change to the public API can break this in multiple ways.
	 * CSV is bad for a maintainable and scalable APIs, but someone could almost map the
	 * head (nonexistent) with the line columns indexes... /rage
	 *
	 * For <= v6.
	 *
	 * @param columns String array with the event columns.
	 * @param old Whether to use the csv parsing for the <=v4 or the >=v5 && <= v6.
	 *
	 * @return EventListItem
	 * @throws NumberFormatException
	 */
	private EventListItem parseEvent (String[] columns, boolean old) throws NumberFormatException {
		EventListItem event = new EventListItem();

		// Event id
		event.id_event = (columns[0].length() > 0) ? Integer.parseInt(columns[0]) : 0;
		// Agent id
		event.id_agent = (columns[1].length() > 0) ? Integer.parseInt(columns[1]) : 0;
		// User id
		event.id_user = columns[2];
		//Get id group
		event.id_group = (columns[3].length() > 0) ? Integer.parseInt(columns[3]) : 0;
		// Status
		event.status = (columns[4].length() > 0) ? Integer.parseInt(columns[4]) : 0;
		// Timestamp (format Y-M-d H:m:s)
		event.timestamp = columns[5];
		//Get event as text
		event.event = columns[6];
		// Unix timestamp
		event.utimestamp = (columns[7].length() > 0) ? Integer.parseInt(columns[7]) : 0;
		// Event type
		event.event_type = columns[8];
		// Module id
		event.id_agentmodule = (columns[9].length() > 0) ? Integer.parseInt(columns[9]) : 0;
		// Alert id
		event.id_alert_am = (columns[10].length() > 0) ? Integer.parseInt(columns[10]) : 0;
		// Priority
		event.criticity = (columns[11].length() > 0) ? Integer.parseInt(columns[11]) : 0;
		// User comment
		event.user_comment = columns[12];
		// Tags
		event.tags = columns[13];

		// For <= v4
		if (old) {
			// Agent name
			event.agent_name = (columns.length >= 15) ? columns[14] : "";
			// Group name
			event.group_name = (columns.length >= 16) ? columns[15] : "";
			// Group icon
			event.group_icon = (columns.length >= 17) ? columns[16] : "";
			// Event description
			event.description_event = (columns.length >= 18) ? columns[17] : "";
			// Event description image
			event.description_image = (columns.length >= 19) ? columns[18] : "";
			// Priority image
			event.criticity_image = (columns.length >= 20) ? columns[19] : "";
			// Priority name
			event.criticity_name = (columns.length >= 21) ? columns[20] : "";
		}
		// For v5 and v6
		else {
			// Agent name
			event.agent_name = (columns.length >= 23) ? columns[22] : "";
			// Group name
			event.group_name = (columns.length >= 24) ? columns[23] : "";
			// Group icon
			event.group_icon = (columns.length >= 25) ? columns[24] : "";
			// Event description
			event.description_event = (columns.length >= 26) ? columns[25] : "";
			// Event description image
			event.description_image = (columns.length >= 27) ? columns[26] : "";
			// Priority image
			event.criticity_image = (columns.length >= 28) ? columns[27] : "";
			// Priority name
			event.criticity_name = (columns.length >= 29) ? columns[28] : "";
		}

		event.opened = false;

		return event;
	}

	/**
	 * Process a JSON response from the api and save the new events.
	 * For >= v7.
	 *
	 * @param items JSONObject array with the event rows.
	 */
	private void saveEvents (JSONArray items) {
		if (items.length() == 0) {
			Log.d("WORKS?", "NEWEVENTS = FALSE");
			this.newEvents = false;
			return;
		}
		this.newEvents = true;

		// Iterate the JSON Objects
		for (int i = 0; i < items.length(); i++) {
			try {
				JSONObject item = items.getJSONObject(i);
				EventListItem event = this.parseEvent(item);
				this.eventList.add(event);
			}
			catch (NumberFormatException nfe) {
				launchProblemParsingNotification();
			}
			catch (JSONException e) {
				launchProblemParsingNotification();
			}
		}
	}

	/**
	 * Process a CSV response from the api and save the new events.
	 * For <= v6.
	 *
	 * @param lines String array with the event lines in CSV format.
	 * @param old Whether to use the csv parsing for the <=v4 or the >=v5 && <= v6.
	 */
	private void saveEvents (String[] lines, boolean old) {
		if (lines.length == 0) {
			Log.d("WORKS?", "NEWEVENTS = FALSE");
			this.newEvents = false;
			return;
		}
		this.newEvents = true;

		// Iterate the CSV lines
		for (int i = 0; i < lines.length; i++) {
			try {
				String[] columns = lines[i].split(";");
				EventListItem event = this.parseEvent(columns, old);
				this.eventList.add(event);
			}
			catch (NumberFormatException nfe) {
				launchProblemParsingNotification();
			}
		}
	}

	/**
	 * Get events from pandora console.
	 * 
	 * @throws IOException If there is any connection problem.
	 */
	private void getEvents () throws IOException {
		// Get total count.
		String return_api = API.getEvents(getApplicationContext(),
				agentNameStr, id_group, severity, status, eventSearch,
				eventTag, timestamp, pagination, offset, true, false);
		return_api = return_api.replace("\n", "");
		
		try {
			this.count_events = Long.valueOf(return_api);
		}
		catch (NumberFormatException e) {
			Log.e(TAG, e.getMessage());
			return;
		}

		if (this.count_events == 0) {
			return;
		}

		// Try to get the API version number
		int apiVerNumber = 0;
		try {
			apiVerNumber = Core.getMajorVersion(getApplicationContext());
		}
		catch (Exception e) {}

		// Get the list of events.
		String returnType = (apiVerNumber < 7) ? "csv" : "json";
		return_api = API.getEvents(getApplicationContext(),
				agentNameStr, id_group, severity, status, eventSearch,
				eventTag, timestamp, pagination, offset, returnType);

		Log.d(TAG, "List of events: " + return_api);
		Log.i(TAG + " getEvents - return_api", return_api);

		try {
			if (apiVerNumber >= 7) {
				JSONObject response = new JSONObject(return_api);
				if (!response.optString("type").equals("array")) {
					throw new Exception("Invalid API return type");
				}

				JSONArray rows = response.getJSONArray("data");
				this.saveEvents(rows);
			}
			else {
				String[] lines = return_api.split("\n");

				// The Console API changed in the v5
				boolean old = apiVerNumber < 5;
				this.saveEvents(lines, old);
			}
		}
		catch (Exception e) {
			this.newEvents = false;
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
			}
			catch (IOException e) {
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
			}
			else {
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