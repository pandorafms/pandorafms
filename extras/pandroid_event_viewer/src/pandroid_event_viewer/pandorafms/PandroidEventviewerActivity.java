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

import java.io.Serializable;
import java.util.ArrayList;
import java.util.Date;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

import android.app.Activity;
import android.app.TabActivity;
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
			Core.setFetchFrequency(getApplicationContext());
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
			executeBackgroundGetEvents();
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
		long count_events = intent.getLongExtra("count_events", 0);
		int more_criticity = intent.getIntExtra("more_criticity", -1);

		CharSequence text;

		if (count_events > 0) {
			// From the notificy
			switch (more_criticity) {
			case 0:
				text = getString(R.string.loading_events_criticity_0_str)
						.replace("%s", new Long(count_events).toString());
				break;
			case 1:
				text = getString(R.string.loading_events_criticity_1_str)
						.replace("%s", new Long(count_events).toString());
				break;
			case 2:
				text = getString(R.string.loading_events_criticity_2_str)
						.replace("%s", new Long(count_events).toString());
				break;
			case 3:
				text = getString(R.string.loading_events_criticity_3_str)
						.replace("%s", new Long(count_events).toString());
				break;
			case 4:
				text = getString(R.string.loading_events_criticity_4_str)
						.replace("%s", new Long(count_events).toString());
				break;
			default:
				text = getString(R.string.loading_events_criticity_2_str)
						.replace("%s", new Long(count_events).toString());
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
					"previous_filterTimestamp", (new Date().getTime() / 1000));
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
			executeBackgroundGetEvents();
		}
	}

	/**
	 * Serializes all parameters.
	 * 
	 * @param total
	 *            True if only want the count
	 * @return All parameters in a string.
	 */
	private String serializeParams2Api(boolean total) {
		String totalStr = (total) ? "total" : "-1";

		return Core.serializeParams2Api(new String[] { ";", // Separator
				Integer.toString(this.severity), // Severity
				this.agentNameStr, // Agent name
				"", // Module
				"", // Alert template
				"", // Id user
				Long.toString(this.timestamp), // Minimum timestamp,
				"", // Maximum timestamp
				String.valueOf(this.status), // Status
				this.eventSearch, // Search in event description
				String.valueOf(this.pagination), // Pagination
				String.valueOf(this.offset), // Event list offset
				totalStr, // Count or show
				String.valueOf(this.id_group), // Group id
				this.eventTag });
	}

	/**
	 * Get events from pandora console.
	 * 
	 */
	private void getEvents() {
		// Get total count.
		String return_api = API.getEvents(getApplicationContext(),
				serializeParams2Api(true));
		return_api = return_api.replace("\n", "");
		try {
			this.count_events = new Long(return_api).longValue();
		} catch (NumberFormatException e) {
			Log.e(TAG, e.getMessage());
			return;
		}

		if (this.count_events == 0) {
			return;
		}

		// Get the list of events.
		return_api = API.getEvents(getApplicationContext(),
				serializeParams2Api(false));
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
			String[] items = lines[i].split(";", 23);

			EventListItem event = new EventListItem();

			if (items.length != 23) {
				event.event = getApplication().getString(
						R.string.unknown_event_str);
			} else {
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
				event.agent_name = items[16];
				event.group_name = items[17];
				event.group_icon = items[18];
				event.description_event = items[19];
				event.description_image = items[20];
				event.criticity_name = items[21];
				event.criticity_image = items[22];

				event.opened = false;
			}
			this.eventList.add(event);
		}
	}

	/**
	 * Executes the async task of getting events.
	 */
	public void executeBackgroundGetEvents() {
		new GetEventsAsyncTask(adapter).execute();
	}

	/**
	 * Get events from pandora console (async task)
	 * 
	 * @author Miguel de Dios Matías
	 * 
	 */
	public class GetEventsAsyncTask extends AsyncTask<Void, Void, Void> {

		private BaseAdapter adapter;

		public GetEventsAsyncTask(BaseAdapter adapter) {
			this.adapter = adapter;
		}

		@Override
		protected Void doInBackground(Void... params) {
			Log.i(TAG + " GetEventsAsyncTask", "doInBackground");
			getEvents();

			return null;
		}

		@Override
		protected void onPostExecute(Void unused) {
			Intent i = new Intent("eventlist.java");

			if (getNewListEvents) {
				loadInProgress = false;
				getNewListEvents = false;

				i.putExtra("load_more", 0);
			} else {
				i.putExtra("load_more", 1);
			}

			adapter.notifyDataSetChanged();
			getApplicationContext().sendBroadcast(i);

		}
	}
}