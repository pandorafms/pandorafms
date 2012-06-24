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
import java.util.HashMap;
import java.util.Iterator;
import java.util.LinkedList;
import java.util.List;
import java.util.Map;
import java.util.Map.Entry;

import android.app.Activity;
import android.app.AlertDialog;
import android.app.TabActivity;
import android.content.Context;
import android.content.DialogInterface;
import android.content.Intent;
import android.content.SharedPreferences;
import android.content.SharedPreferences.Editor;
import android.os.AsyncTask;
import android.os.Bundle;
import android.util.Log;
import android.view.Menu;
import android.view.MenuInflater;
import android.view.MenuItem;
import android.view.View;
import android.view.View.OnClickListener;
import android.widget.AdapterView;
import android.widget.AdapterView.OnItemSelectedListener;
import android.widget.ArrayAdapter;
import android.widget.Button;
import android.widget.CheckBox;
import android.widget.CompoundButton;
import android.widget.CompoundButton.OnCheckedChangeListener;
import android.widget.EditText;
import android.widget.ImageView;
import android.widget.LinearLayout;
import android.widget.ProgressBar;
import android.widget.Spinner;
import android.widget.Toast;

/**
 * Activity with the filter options.
 * 
 * @author Miguel de Dios Matías
 * 
 */
public class Main extends Activity {
	private static String TAG = "MAIN";
	private static String PROFILE_PREFIX = "profile:";
	private PandroidEventviewerActivity object;
	private HashMap<Integer, String> pandoraGroups;
	private Spinner comboSeverity;
	private List<String> profiles;
	private Context context = this;
	private boolean selectLastProfile = false;

	@Override
	public void onCreate(Bundle savedInstanceState) {
		super.onCreate(savedInstanceState);

		Intent i = getIntent();
		this.object = (PandroidEventviewerActivity) i
				.getSerializableExtra("object");

		this.pandoraGroups = new HashMap<Integer, String>();

		setContentView(R.layout.main);
		final Button buttonReset = (Button) findViewById(R.id.button_reset);
		final Button buttonSearch = (Button) findViewById(R.id.button_send);
		final Button buttonbuttonSetAsFilterWatcher = (Button) findViewById(R.id.button_set_as_filter_watcher);
		final Button buttonSaveProfile = (Button) findViewById(R.id.button_save_profile);
		// Check if the user preferences it is set.
		if (object.user.length() == 0 || object.password.length() == 0
				|| object.url.length() == 0) {
			Toast toast = Toast.makeText(this.getApplicationContext(),
					this.getString(R.string.please_set_preferences_str),
					Toast.LENGTH_SHORT);
			toast.show();

			buttonReset.setEnabled(false);
			buttonSearch.setEnabled(false);
			buttonbuttonSetAsFilterWatcher.setEnabled(false);
		} else if (object.user.equals("demo") || object.password.equals("demo")) {
			Toast toast = Toast.makeText(this.getApplicationContext(),
					this.getString(R.string.preferences_set_demo_pandora_str),
					Toast.LENGTH_LONG);
			toast.show();
		} else {
			buttonSearch.setEnabled(false);
			buttonReset.setEnabled(false);
			buttonbuttonSetAsFilterWatcher.setEnabled(false);

			new GetGroupsAsyncTask().execute();
			new GetTagsAsyncTask().execute();
		}

		final SharedPreferences preferences = getSharedPreferences(
				this.getString(R.string.const_string_preferences),
				Activity.MODE_PRIVATE);

		comboSeverity = (Spinner) findViewById(R.id.severity_combo);
		ArrayAdapter<CharSequence> adapter = ArrayAdapter.createFromResource(
				this, R.array.severity_array_values,
				android.R.layout.simple_spinner_item);
		adapter.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item);
		comboSeverity.setAdapter(adapter);

		Spinner combo;
		combo = (Spinner) findViewById(R.id.status_combo);
		adapter = ArrayAdapter.createFromResource(this,
				R.array.event_status_values,
				android.R.layout.simple_spinner_item);
		adapter.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item);
		combo.setAdapter(adapter);
		combo.setSelection(3);

		loadProfiles();
		combo = (Spinner) findViewById(R.id.profile_combo);
		combo.setOnItemSelectedListener(new OnItemSelectedListener() {

			@Override
			public void onItemSelected(AdapterView<?> parent, View view,
					int pos, long id) {
				String selected = parent.getItemAtPosition(pos).toString();
				setProfile(selected);
			}

			@Override
			public void onNothingSelected(AdapterView<?> arg0) {
			}
		});
		((ImageView) findViewById(R.id.delete_profile))
				.setOnClickListener(new OnClickListener() {

					@Override
					public void onClick(View v) {
						String profileName = ((Spinner) findViewById(R.id.profile_combo))
								.getSelectedItem().toString();
						deleteProfile(profileName);
						loadProfiles();
					}
				});

		combo = (Spinner) findViewById(R.id.max_time_old_event_combo);
		adapter = ArrayAdapter.createFromResource(this,
				R.array.max_time_old_event_values,
				android.R.layout.simple_spinner_item);
		adapter.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item);
		combo.setAdapter(adapter);
		combo.setSelection(preferences.getInt("filterLastTime", 6));

		buttonReset.setOnClickListener(new View.OnClickListener() {
			@Override
			public void onClick(View v) {
				reset_form();
			}
		});

		buttonSearch.setOnClickListener(new View.OnClickListener() {
			@Override
			public void onClick(View v) {
				search_form();
			}
		});

		buttonbuttonSetAsFilterWatcher
				.setOnClickListener(new View.OnClickListener() {

					@Override
					public void onClick(View v) {
						save_filter_watcher();
					}
				});
		buttonSaveProfile.setOnClickListener(new OnClickListener() {

			@Override
			public void onClick(View v) {
				final EditText profileName = new EditText(getBaseContext());
				DialogInterface.OnClickListener dialogClickListener = new DialogInterface.OnClickListener() {
					@Override
					public void onClick(DialogInterface dialog, int which) {
						switch (which) {
						case DialogInterface.BUTTON_POSITIVE:
							/*
							 * if (profiles.contains(profileName.getText()
							 * .toString())) { Toast.makeText(context,
							 * R.string.profile_already_exists,
							 * Toast.LENGTH_SHORT).show(); break; }
							 */
							if (profileName.getText().toString().contains("|")) {
								Toast.makeText(
										context,
										R.string.profile_name_character_not_allowed,
										Toast.LENGTH_SHORT).show();
								break;
							}
							saveProfile(profileName.getText().toString());
							Toast.makeText(context, R.string.profile_saved,
									Toast.LENGTH_SHORT).show();
							loadProfiles();
							dialog.cancel();
							break;

						case DialogInterface.BUTTON_NEGATIVE:
							dialog.cancel();
							break;
						}
					}
				};
				AlertDialog.Builder builder = new AlertDialog.Builder(context);
				builder.setView(profileName);
				builder.setMessage(R.string.profile_name)
						.setPositiveButton(android.R.string.yes,
								dialogClickListener)
						.setNegativeButton(android.R.string.no,
								dialogClickListener).show();
			}
		});
		LinearLayout advancedOptions = (LinearLayout) findViewById(R.id.show_hide_layout);
		// Show advanced options?
		if (preferences.getBoolean("show_advanced", false)) {
			advancedOptions.setVisibility(View.VISIBLE);
		} else {
			advancedOptions.setVisibility(View.GONE);
			setAdvancedOptionsDefaults();
			clearAdvancedOptions();
		}
		CheckBox cb = (CheckBox) findViewById(R.id.checkBox_advanced_options);
		cb.setChecked(preferences.getBoolean("show_advanced", false));
		cb.setOnCheckedChangeListener(new OnCheckedChangeListener() {

			@Override
			public void onCheckedChanged(CompoundButton buttonView,
					boolean isChecked) {
				LinearLayout advancedOptions = (LinearLayout) findViewById(R.id.show_hide_layout);
				Editor preferencesEditor = preferences.edit();
				if (isChecked) {
					advancedOptions.setVisibility(View.VISIBLE);
					preferencesEditor.putBoolean("show_advanced", true);
				} else {
					advancedOptions.setVisibility(View.GONE);
					preferencesEditor.putBoolean("show_advanced", false);
					setAdvancedOptionsDefaults();
					clearAdvancedOptions();
				}
				preferencesEditor.commit();
			}
		});

		if (this.object.show_popup_info) {
			this.object.show_popup_info = false;
			i = new Intent(this, About.class);
			startActivity(i);
		}
	}

	public void onRestart() {
		super.onRestart();
		SharedPreferences preferences = getSharedPreferences(
				this.getString(R.string.const_string_preferences),
				Activity.MODE_PRIVATE);
		SharedPreferences.Editor editorPreferences = preferences.edit();
		if (preferences.getBoolean("url_changed", false)) {
			Log.i(TAG, "Getting groups and tags");
			new GetGroupsAsyncTask().execute();
			new GetTagsAsyncTask().execute();
			editorPreferences.putBoolean("url_changed", false);
			editorPreferences.commit();
		}
	}

	/**
	 * Async task which get groups.
	 * 
	 * @author Miguel de Dios Matías
	 * 
	 */
	private class GetGroupsAsyncTask extends AsyncTask<Void, Void, Void> {
		private List<String> list;

		@Override
		protected Void doInBackground(Void... params) {
			list = new ArrayList<String>();
			list.addAll(API.getGroups(getApplicationContext()).values());
			return null;
		}

		@Override
		protected void onPostExecute(Void unused) {
			Spinner combo = (Spinner) findViewById(R.id.group_combo);

			ArrayAdapter<String> spinnerArrayAdapter = new ArrayAdapter<String>(
					getApplicationContext(),
					android.R.layout.simple_spinner_item, list);
			combo.setAdapter(spinnerArrayAdapter);
			combo.setSelection(0);

			ProgressBar loadingGroup = (ProgressBar) findViewById(R.id.loading_group);

			loadingGroup.setVisibility(ProgressBar.GONE);
			combo.setVisibility(Spinner.VISIBLE);

			Button buttonReset = (Button) findViewById(R.id.button_reset);
			Button buttonSearch = (Button) findViewById(R.id.button_send);
			Button buttonbuttonSetAsFilterWatcher = (Button) findViewById(R.id.button_set_as_filter_watcher);

			buttonReset.setEnabled(true);
			buttonSearch.setEnabled(true);
			buttonbuttonSetAsFilterWatcher.setEnabled(true);
		}
	}

	/**
	 * Async task which get tags.
	 * 
	 * @author Santiago Munín González
	 * 
	 */
	private class GetTagsAsyncTask extends AsyncTask<Void, Void, Void> {
		private List<String> list;

		@Override
		protected Void doInBackground(Void... params) {
			list = API.getTags(getApplicationContext());
			return null;
		}

		@Override
		protected void onPostExecute(Void unused) {
			Spinner combo = (Spinner) findViewById(R.id.tag);

			ArrayAdapter<String> spinnerArrayAdapter = new ArrayAdapter<String>(
					getApplicationContext(),
					android.R.layout.simple_spinner_item, list);
			combo.setAdapter(spinnerArrayAdapter);
			combo.setSelection(0);

			ProgressBar loadingGroup = (ProgressBar) findViewById(R.id.loading_tag);

			loadingGroup.setVisibility(ProgressBar.GONE);
			combo.setVisibility(Spinner.VISIBLE);
		}
	}

	// For options
	@Override
	public boolean onCreateOptionsMenu(Menu menu) {
		MenuInflater inflater = getMenuInflater();
		inflater.inflate(R.menu.options_menu, menu);
		return true;
	}

	@Override
	public boolean onOptionsItemSelected(MenuItem item) {
		Intent i;
		switch (item.getItemId()) {
		case R.id.options_button_menu_options:
			startActivity(new Intent(this, Options.class));
			break;
		case R.id.about_button_menu_options:
			i = new Intent(this, About.class);
			startActivity(i);
			break;
		}
		return true;
	}

	/**
	 * Performs the search choice
	 */
	private void search_form() {
		// Clean the EventList
		this.object.eventList = new ArrayList<EventListItem>();

		this.object.loadInProgress = true;

		int timeKey = 0;
		Spinner combo = (Spinner) findViewById(R.id.max_time_old_event_combo);
		timeKey = combo.getSelectedItemPosition();

		this.object.timestamp = Core.convertMaxTimeOldEventValuesToTimestamp(0,
				timeKey);

		EditText text = (EditText) findViewById(R.id.agent_name);
		this.object.agentNameStr = text.getText().toString();

		this.object.id_group = 0;

		combo = (Spinner) findViewById(R.id.group_combo);
		if (combo.getSelectedItem() != null) {
			String selectedGroup = combo.getSelectedItem().toString();

			Iterator<Entry<Integer, String>> it = pandoraGroups.entrySet()
					.iterator();
			while (it.hasNext()) {
				Map.Entry<Integer, String> e = (Map.Entry<Integer, String>) it
						.next();

				if (e.getValue().equals(selectedGroup)) {
					this.object.id_group = e.getKey();
				}
			}
		}

		combo = (Spinner) findViewById(R.id.severity_combo);
		this.object.severity = combo.getSelectedItemPosition() - 1;

		combo = (Spinner) findViewById(R.id.status_combo);
		this.object.status = combo.getSelectedItemPosition() - 0;

		text = (EditText) findViewById(R.id.event_search_text);
		this.object.eventSearch = text.getText().toString();

		combo = (Spinner) findViewById(R.id.tag);
		if (combo.getSelectedItem() != null) {
			this.object.eventTag = combo.getSelectedItem().toString();
		}
		this.object.getNewListEvents = true;
		this.object.executeBackgroundGetEvents();

		TabActivity ta = (TabActivity) this.getParent();
		ta.getTabHost().setCurrentTab(1);
	}

	/**
	 * Saves filter data
	 */
	private void save_filter_watcher() {
		String filterAgentName = "";
		int filterIDGroup = 0;
		int filterSeverity = -1;
		int filterStatus = -1;
		int filterLastTime = 0;
		String filterEventSearch = "";
		String filterTag = "";

		EditText text = (EditText) findViewById(R.id.agent_name);
		filterAgentName = text.getText().toString();

		Spinner combo;
		combo = (Spinner) findViewById(R.id.group_combo);
		if ((combo != null) && (combo.getSelectedItem() != null)) {
			String selectedGroup = combo.getSelectedItem().toString();

			Iterator<Entry<Integer, String>> it = pandoraGroups.entrySet()
					.iterator();
			while (it.hasNext()) {
				Map.Entry<Integer, String> e = it.next();

				if (e.getValue().equals(selectedGroup)) {
					filterIDGroup = e.getKey();
				}
			}
		}

		combo = (Spinner) findViewById(R.id.severity_combo);
		filterSeverity = combo.getSelectedItemPosition() - 1;

		combo = (Spinner) findViewById(R.id.status_combo);
		filterStatus = combo.getSelectedItemPosition() - 0;

		combo = (Spinner) findViewById(R.id.max_time_old_event_combo);
		filterLastTime = combo.getSelectedItemPosition();

		combo = (Spinner) findViewById(R.id.tag);
		filterTag = combo.getSelectedItem().toString();

		text = (EditText) findViewById(R.id.event_search_text);
		filterEventSearch = text.getText().toString();

		SharedPreferences preferences = getSharedPreferences(
				this.getString(R.string.const_string_preferences),
				Activity.MODE_PRIVATE);
		SharedPreferences.Editor editorPreferences = preferences.edit();

		editorPreferences.putString("filterAgentName", filterAgentName);
		editorPreferences.putInt("filterIDGroup", filterIDGroup);
		editorPreferences.putInt("filterSeverity", filterSeverity);
		editorPreferences.putInt("filterStatus", filterStatus);
		editorPreferences.putString("filterTag", filterTag);
		editorPreferences.putString("filterEventSearch", filterEventSearch);
		editorPreferences.putInt("filterLastTime", filterLastTime);

		if (editorPreferences.commit()) {
			Core.setFetchFrequency(getApplicationContext());
			Toast toast = Toast.makeText(getApplicationContext(),
					this.getString(R.string.filter_update_succesful_str),
					Toast.LENGTH_SHORT);
			toast.show();
		} else {
			Toast toast = Toast.makeText(getApplicationContext(),
					this.getString(R.string.filter_update_fail_str),
					Toast.LENGTH_SHORT);
			toast.show();
		}
	}

	/**
	 * Resets the filter form.
	 * 
	 */
	private void reset_form() {
		Spinner combo = (Spinner) findViewById(R.id.group_combo);
		combo.setSelection(0);
		combo = (Spinner) findViewById(R.id.status_combo);
		combo.setSelection(3);
		combo = (Spinner) findViewById(R.id.tag);
		combo.setSelection(0);

		EditText text = (EditText) findViewById(R.id.agent_name);
		text.setText("");

		combo = (Spinner) findViewById(R.id.severity_combo);
		combo.setSelection(0);

		combo = (Spinner) findViewById(R.id.max_time_old_event_combo);
		combo.setSelection(6);

		text = (EditText) findViewById(R.id.event_search_text);
		text.setText("");

	}

	/**
	 * Clears advanced options.
	 */
	private void clearAdvancedOptions() {

		EditText text = (EditText) findViewById(R.id.agent_name);
		text.setText("");

		Spinner combo = (Spinner) findViewById(R.id.severity_combo);
		combo.setSelection(0);

		combo = (Spinner) findViewById(R.id.max_time_old_event_combo);
		combo.setSelection(6);

		text = (EditText) findViewById(R.id.event_search_text);
		text.setText("");
	}

	/**
	 * Puts advanced options to default values.
	 */
	private void setAdvancedOptionsDefaults() {
		SharedPreferences preferences = getSharedPreferences(
				this.getString(R.string.const_string_preferences),
				Activity.MODE_PRIVATE);
		SharedPreferences.Editor editorPreferences = preferences.edit();

		editorPreferences.putString("filterAgentName", "");
		editorPreferences.putInt("filterIDGroup", 0);
		editorPreferences.putInt("filterSeverity", -1);
		editorPreferences.putString("filterEventSearch", "");
		editorPreferences.putInt("filterLastTime", 6);
		// There were changes
		editorPreferences.putBoolean("filterChanges", true);
		editorPreferences.commit();
	}

	/**
	 * Saves a search profile in SharedPreferences
	 */
	private void saveProfile(String profileName) {
		int group = ((Spinner) findViewById(R.id.group_combo))
				.getSelectedItemPosition();
		int status = ((Spinner) findViewById(R.id.status_combo))
				.getSelectedItemPosition();
		int tag = ((Spinner) findViewById(R.id.tag)).getSelectedItemPosition();
		String agentName = ((EditText) findViewById(R.id.agent_name)).getText()
				.toString();
		String eventSearch = ((EditText) findViewById(R.id.event_search_text))
				.getText().toString();
		int severity = ((Spinner) findViewById(R.id.severity_combo))
				.getSelectedItemPosition();
		int oldestEvent = ((Spinner) findViewById(R.id.max_time_old_event_combo))
				.getSelectedItemPosition();
		SharedPreferences preferences = getSharedPreferences(
				this.getString(R.string.const_string_preferences),
				Activity.MODE_PRIVATE);
		SharedPreferences.Editor editorPreferences = preferences.edit();
		// Profile
		editorPreferences.putString(PROFILE_PREFIX + profileName, group + "|"
				+ status + "|" + tag + "|" + agentName + "|" + eventSearch
				+ "|" + severity + "|" + oldestEvent);
		// Add to profiles
		String allProfiles = preferences.getString("allProfiles", "");
		if (allProfiles.length() > 0) {
			allProfiles = allProfiles + "|";
		}
		allProfiles = allProfiles + profileName;
		editorPreferences.putString("allProfiles", allProfiles);
		if (editorPreferences.commit()) {
			selectLastProfile = true;
			Log.i(TAG, "New search profile saved.");
		}
	}

	/**
	 * Gets the list of search profiles (only names).
	 * 
	 * @return a list with all search profiles.
	 */
	private List<String> getAllProfiles() {
		SharedPreferences preferences = getSharedPreferences(
				this.getString(R.string.const_string_preferences),
				Activity.MODE_PRIVATE);
		List<String> result = new LinkedList<String>();
		String allProfiles = preferences.getString("allProfiles", "");
		if (allProfiles.length() > 0) {
			String[] profiles = allProfiles.split("\\|");
			for (int i = 0; i < profiles.length; i++) {
				result.add(profiles[i]);
			}
		}
		return result;
	}

	/**
	 * Fill all fields with profile's options.
	 * 
	 * @param profileName
	 *            Profile name.
	 */
	private void setProfile(String profileName) {
		SharedPreferences preferences = getSharedPreferences(
				this.getString(R.string.const_string_preferences),
				Activity.MODE_PRIVATE);
		String profileData = preferences.getString(
				PROFILE_PREFIX + profileName, "");
		String options[] = profileData.split("\\|");
		if (options.length == 7) {
			try {
				Spinner spinner = (Spinner) findViewById(R.id.group_combo);
				spinner.setSelection(Integer.valueOf(options[0]));
				spinner = (Spinner) findViewById(R.id.status_combo);
				spinner.setSelection(Integer.valueOf(options[1]));
				spinner = (Spinner) findViewById(R.id.tag);
				spinner.setSelection(Integer.valueOf(options[2]));
				EditText editText = (EditText) findViewById(R.id.agent_name);
				editText.setText(options[3]);
				editText = (EditText) findViewById(R.id.event_search_text);
				editText.setText(options[4]);
				spinner = (Spinner) findViewById(R.id.severity_combo);
				spinner.setSelection(Integer.valueOf(options[5]));
				spinner = (Spinner) findViewById(R.id.max_time_old_event_combo);
				spinner.setSelection(Integer.valueOf(options[6]));
			} catch (NumberFormatException ne) {
				Log.e(TAG, "NumberFormatException parsing profile");
				return;
			} catch (IndexOutOfBoundsException ie) {
				Log.e(TAG,
						"IndexOutOfBoundsException (maybe groups or tags are not correctly loaded)");
				return;
			}
		}
	}

	/**
	 * Removes a profile.
	 * 
	 * @param profileName
	 */
	private void deleteProfile(String profileName) {
		SharedPreferences preferences = getSharedPreferences(
				this.getString(R.string.const_string_preferences),
				Activity.MODE_PRIVATE);
		SharedPreferences.Editor editorPreferences = preferences.edit();
		editorPreferences.remove(PROFILE_PREFIX + profileName);
		String profiles = preferences.getString("allProfiles", "");
		int firstPos = profiles.indexOf(profileName);
		int amount = profileName.length();
		if (firstPos != 0) {
			// Includes the separator char
			firstPos--;
			amount++;
		}
		profiles = profiles.substring(0, firstPos)
				+ profiles.substring(firstPos + amount);
		if (profiles.startsWith("|")) {
			if (profiles.length() > 1) {
				profiles = profiles.substring(1);
			} else {
				profiles = "";
			}
		}
		editorPreferences.putString("allProfiles", profiles);
		if (editorPreferences.commit()) {
			Log.i(TAG, "Removed profile: " + profileName);
		}
	}

	/**
	 * Fetches all profiles and fills the profile combo.
	 */
	private void loadProfiles() {
		profiles = new LinkedList<String>();
		profiles.add("");
		profiles.addAll(getAllProfiles());
		Spinner combo = (Spinner) findViewById(R.id.profile_combo);
		ArrayAdapter<String> adapter = new ArrayAdapter<String>(
				getApplicationContext(), android.R.layout.simple_spinner_item,
				profiles);
		adapter.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item);
		combo.setAdapter(adapter);
		if (selectLastProfile) {
			selectLastProfile = false;
			combo.setSelection(profiles.size() - 1);
		}
	}
}