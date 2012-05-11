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

import android.app.Activity;
import android.content.Context;
import android.content.Intent;
import android.content.SharedPreferences;
import android.media.Ringtone;
import android.media.RingtoneManager;
import android.net.Uri;
import android.os.AsyncTask;
import android.os.Bundle;
import android.util.Log;
import android.view.View;
import android.view.View.OnClickListener;
import android.widget.ArrayAdapter;
import android.widget.Button;
import android.widget.CheckBox;
import android.widget.EditText;
import android.widget.Spinner;
import android.widget.TextView;
import android.widget.Toast;

/**
 * Options activity.
 * 
 * @author Miguel de Dios Matías
 * 
 */
public class Options extends Activity {
	private static String TAG = "Options";
	private static int RINGTONE_PICK_CODE = 999;
	private String url;
	private String user;
	private String password;
	private int refreshTimeKey;
	private TextView connectionStatus;

	private Core core;
	private PandroidEventviewerActivity object;

	@Override
	public void onCreate(Bundle savedInstanceState) {
		super.onCreate(savedInstanceState);

		Intent i = getIntent();
		this.core = (Core) i.getSerializableExtra("core");

		setContentView(R.layout.options);
		connectionStatus = (TextView) findViewById(R.id.check_connection_status);
		new CheckConnectionAsyncTask().execute();

		SharedPreferences preferences = getSharedPreferences(
				this.getString(R.string.const_string_preferences),
				Activity.MODE_PRIVATE);
		// Connection
		url = preferences.getString("url",
				"http://firefly.artica.es/pandora_demo");
		user = preferences.getString("user", "demo");
		password = preferences.getString("password", "demo");
		refreshTimeKey = preferences.getInt("refreshTimeKey", 3);

		EditText text = (EditText) findViewById(R.id.url);
		text.setText(url);
		text = (EditText) findViewById(R.id.user);
		text.setText(user);
		text = (EditText) findViewById(R.id.password);
		text.setText(password);

		Spinner combo = (Spinner) findViewById(R.id.refresh_combo);
		ArrayAdapter<CharSequence> adapter = ArrayAdapter.createFromResource(
				this, R.array.refresh_combo,
				android.R.layout.simple_spinner_item);
		adapter.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item);
		combo.setAdapter(adapter);
		combo.setSelection(refreshTimeKey);

		final Button buttonSearch = (Button) findViewById(R.id.update_options);
		buttonSearch.setOnClickListener(new View.OnClickListener() {
			@Override
			public void onClick(View v) {
				save_options();
				new CheckConnectionAsyncTask().execute();
			}
		});

		if (this.object != null && this.object.show_popup_info) {
			this.object.show_popup_info = false;
			i = new Intent(this, About.class);
			startActivity(i);
		}

		// Notification
		boolean vibration = preferences.getBoolean("vibration", true);
		CheckBox cb = (CheckBox) findViewById(R.id.vibration_on);
		cb.setChecked(vibration);
		boolean led = preferences.getBoolean("led", false);
		cb = (CheckBox) findViewById(R.id.led_flash_on);
		cb.setChecked(led);

		Button notificationSound = (Button) findViewById(R.id.sound_button);
		Uri defaultSoundUri = Uri.parse(preferences.getString(
				"notification_sound_uri",
				RingtoneManager
						.getDefaultUri(RingtoneManager.TYPE_NOTIFICATION)
						.toString()));
		this.setNewRingtone(notificationSound, defaultSoundUri);

		notificationSound.setOnClickListener(new OnClickListener() {
			@Override
			public void onClick(View v) {
				Log.i(TAG, "Selecting ringtone");
				Intent intent = new Intent(
						RingtoneManager.ACTION_RINGTONE_PICKER);
				intent.putExtra(RingtoneManager.EXTRA_RINGTONE_TITLE,
						getString(R.string.select_sound));
				intent.putExtra(RingtoneManager.EXTRA_RINGTONE_SHOW_SILENT,
						false);
				intent.putExtra(RingtoneManager.EXTRA_RINGTONE_SHOW_DEFAULT,
						true);
				intent.putExtra(RingtoneManager.EXTRA_RINGTONE_TYPE,
						RingtoneManager.TYPE_NOTIFICATION);
				startActivityForResult(intent, RINGTONE_PICK_CODE);
			}
		});

	}

	// Gets sound selected
	@Override
	protected void onActivityResult(int requestCode, int resultCode, Intent data) {
		super.onActivityResult(requestCode, resultCode, data);
		if (requestCode == RINGTONE_PICK_CODE) {
			if (data != null) {
				Uri uri = data
						.getParcelableExtra(RingtoneManager.EXTRA_RINGTONE_PICKED_URI);
				this.setNewRingtone(((Button) findViewById(R.id.sound_button)),
						uri);
			}
		}
	}

	/**
	 * Saves all options
	 */
	private void save_options() {
		SharedPreferences preferences = getSharedPreferences(
				this.getString(R.string.const_string_preferences),
				Activity.MODE_PRIVATE);
		SharedPreferences.Editor editorPreferences = preferences.edit();
		// Connection settings
		EditText text = (EditText) findViewById(R.id.url);
		String url = text.getText().toString();
		if (url.charAt(url.length() - 1) == '/') {
			url = url.substring(0, url.length() - 1);
		}

		editorPreferences.putString("url", url);
		text = (EditText) findViewById(R.id.user);
		editorPreferences.putString("user", text.getText().toString());
		text = (EditText) findViewById(R.id.password);
		editorPreferences.putString("password", text.getText().toString());

		Spinner combo = (Spinner) findViewById(R.id.refresh_combo);
		editorPreferences.putInt("refreshTimeKey",
				combo.getSelectedItemPosition());

		// Notification settings
		CheckBox cb = (CheckBox) findViewById(R.id.vibration_on);
		editorPreferences.putBoolean("vibration", cb.isChecked());
		cb = (CheckBox) findViewById(R.id.led_flash_on);
		editorPreferences.putBoolean("led", cb.isChecked());

		Context context = this.getApplicationContext();

		if (editorPreferences.commit()) {
			if (this.core != null) {
				this.core.stopServiceEventWatcher(getApplicationContext());
				this.core.startServiceEventWatcher(getApplicationContext());
			}
			Log.i(TAG, "Settings saved");
			Toast toast = Toast.makeText(context,
					this.getString(R.string.config_update_succesful_str),
					Toast.LENGTH_SHORT);
			toast.show();
		} else {
			Toast toast = Toast.makeText(context,
					this.getString(R.string.config_update_fail_str),
					Toast.LENGTH_LONG);
			toast.show();
		}
	}

	/**
	 * Sets ringtone's title (shortens it if necessary) on the button and
	 * changes ringtone's uri in options. If there is a problem with the given
	 * Uri, just put "Silence".
	 * 
	 * @param button
	 *            Target button.
	 * @param uri
	 *            Ringtone's uri.
	 */
	private void setNewRingtone(Button button, Uri uri) {
		if (uri != null) {
			Log.i(TAG, "New ringtone selected: " + uri.toString());
			SharedPreferences preferences = getSharedPreferences(
					this.getString(R.string.const_string_preferences),
					Activity.MODE_PRIVATE);
			SharedPreferences.Editor editorPreferences = preferences.edit();
			editorPreferences.putString("notification_sound_uri",
					uri.toString());
			Ringtone r = RingtoneManager.getRingtone(getApplicationContext(),
					uri);

			if (r != null) {
				String text = r.getTitle(getApplicationContext());
				if (text.length() > 15) {
					button.setText(text.substring(0, 15) + "...");
				} else {
					button.setText(text);
				}
			} else {
				Log.e(TAG, "Sound setting problem (null ringtone)");
				button.setText(getString(R.string.silence));
			}
		} else {
			Log.e(TAG, "Sound setting problem (null uri)");
			button.setText(getString(R.string.silence));
		}
	}

	/**
	 * Checks if connection parameters are ok.
	 * 
	 * @author Santiago Munín González
	 * 
	 */
	private class CheckConnectionAsyncTask extends AsyncTask<Void, Void, Void> {

		private boolean connectionOk = false;

		@Override
		protected Void doInBackground(Void... arg0) {
			// TODO implement check
			this.connectionOk = false;
			return null;
		}

		/**
		 * Choose an image (ok or wrong)
		 */
		protected void onPostExecute(Void v) {
			if (this.connectionOk) {
				connectionStatus.setCompoundDrawablesWithIntrinsicBounds(0, 0,
						0, R.drawable.ok);
			} else {
				connectionStatus.setCompoundDrawablesWithIntrinsicBounds(0, 0,
						0, R.drawable.cross);
			}
		}
	}
}
