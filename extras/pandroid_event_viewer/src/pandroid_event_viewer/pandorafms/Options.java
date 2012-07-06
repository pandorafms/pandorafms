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
import java.net.MalformedURLException;
import java.net.URL;

import android.app.Activity;
import android.app.AlertDialog;
import android.app.ProgressDialog;
import android.content.Context;
import android.content.DialogInterface;
import android.content.Intent;
import android.content.SharedPreferences;
import android.media.Ringtone;
import android.media.RingtoneManager;
import android.net.Uri;
import android.os.AsyncTask;
import android.os.Bundle;
import android.os.Handler;
import android.provider.Settings;
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
	private static long DIALOG_TIME = 10000;
	private TextView connectionStatus;
	private ProgressDialog retrievingCertificate;
	private CheckCertificateAsyncTask asyncTask;
	private Context context;

	private PandroidEventviewerActivity object;

	@Override
	public void onCreate(Bundle savedInstanceState) {
		super.onCreate(savedInstanceState);
		this.context = this;
		Intent i = getIntent();

		setContentView(R.layout.options);
		connectionStatus = (TextView) findViewById(R.id.check_connection_status);
		new CheckConnectionAsyncTask().execute();

		SharedPreferences preferences = getSharedPreferences(
				this.getString(R.string.const_string_preferences),
				Activity.MODE_PRIVATE);
		// Connection
		EditText text = (EditText) findViewById(R.id.url);
		text.setText(preferences.getString("url",
				"http://firefly.artica.es/pandora_demo"));
		text = (EditText) findViewById(R.id.user);
		text.setText(preferences.getString("user", "demo"));
		text = (EditText) findViewById(R.id.password);
		text.setText(preferences.getString("password", "demo"));
		text = (EditText) findViewById(R.id.api_password);
		text.setText(preferences.getString("api_password", ""));

		Spinner combo = (Spinner) findViewById(R.id.refresh_combo);
		ArrayAdapter<CharSequence> adapter = ArrayAdapter.createFromResource(
				this, R.array.refresh_combo,
				android.R.layout.simple_spinner_item);
		adapter.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item);
		combo.setAdapter(adapter);
		combo.setSelection(preferences.getInt("refreshTimeKey", 3));

		final Button button = (Button) findViewById(R.id.update_options);
		button.setOnClickListener(new View.OnClickListener() {
			@Override
			public void onClick(View v) {
				save_options();
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
						true);
				intent.putExtra(RingtoneManager.EXTRA_RINGTONE_SHOW_DEFAULT,
						false);
				intent.putExtra(RingtoneManager.EXTRA_RINGTONE_DEFAULT_URI,
						Settings.System.DEFAULT_NOTIFICATION_URI);
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
		String url = ((EditText) findViewById(R.id.url)).getText().toString();
		if (url.contains("https")) {
			try {
				retrievingCertificate = ProgressDialog.show(this, "",
						"Loading...", true);
				asyncTask = new CheckCertificateAsyncTask();
				asyncTask.execute(new URL[] { new URL(url) });
				removeDialog(DIALOG_TIME, retrievingCertificate);
			} catch (MalformedURLException e) {
				Toast.makeText(getApplicationContext(), R.string.url_not_valid,
						Toast.LENGTH_SHORT).show();
				return;
			}
		} else {
			writeChanges();
		}
	}

	private void writeChanges() {
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
		// MainActivity uses this to know if it has to check tags and groups
		// again
		editorPreferences.putBoolean("url_changed", true);
		text = (EditText) findViewById(R.id.user);
		editorPreferences.putString("user", text.getText().toString());
		text = (EditText) findViewById(R.id.password);
		editorPreferences.putString("password", text.getText().toString());
		text = (EditText) findViewById(R.id.api_password);
		editorPreferences.putString("api_password", text.getText().toString());

		Spinner combo = (Spinner) findViewById(R.id.refresh_combo);
		editorPreferences.putInt("refreshTimeKey",
				combo.getSelectedItemPosition());

		// Notification settings
		CheckBox cb = (CheckBox) findViewById(R.id.vibration_on);
		editorPreferences.putBoolean("vibration", cb.isChecked());
		cb = (CheckBox) findViewById(R.id.led_flash_on);
		editorPreferences.putBoolean("led", cb.isChecked());
		editorPreferences.putBoolean("configured", true);

		Context context = this.getApplicationContext();

		if (editorPreferences.commit()) {
			Core.setFetchFrequency(getApplicationContext());
			Log.i(TAG, "Settings saved");
			Toast toast = Toast.makeText(context,
					this.getString(R.string.config_update_succesful_str),
					Toast.LENGTH_SHORT);
			toast.show();
			new CheckConnectionAsyncTask().execute();
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
			if (editorPreferences.commit()) {
				Log.i(TAG, "New ringtone saved.");
			} else {
				Log.e(TAG, "Problem saving new ringtone preference.");
			}
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
	private class CheckConnectionAsyncTask extends
			AsyncTask<Void, Void, Boolean> {

		private String version = "";

		@Override
		protected Boolean doInBackground(Void... arg0) {
			try {
				version = API.getVersion(getApplicationContext());
				SharedPreferences preferences = getSharedPreferences(
						getString(R.string.const_string_preferences),
						Activity.MODE_PRIVATE);
				SharedPreferences.Editor editorPreferences = preferences.edit();
				editorPreferences.putString("api_version", version);
				if (editorPreferences.commit()) {
					Log.i(TAG, "API Version saved");
				}
				if (version.length() > 0) {
					return true;
				} else {
					return false;
				}
			} catch (IOException e) {
				return false;
			}
		}

		/**
		 * Chooses an image (ok or wrong)
		 */
		protected void onPostExecute(Boolean result) {
			if (result) {
				connectionStatus.setText(version);
				connectionStatus.setCompoundDrawablesWithIntrinsicBounds(0, 0,
						0, R.drawable.ok);
			} else {
				connectionStatus.setCompoundDrawablesWithIntrinsicBounds(0, 0,
						0, R.drawable.cross);
			}
		}
	}

	/**
	 * Checks (in background) if the certificate of the site is signed by a CA.
	 * 
	 * @author Santiago Munín González
	 * 
	 */
	private class CheckCertificateAsyncTask extends
			AsyncTask<URL, Void, Boolean> {
		private URL url;
		private boolean online;

		@Override
		protected Boolean doInBackground(URL... arg0) {
			url = arg0[0];
			online = Core.isOnline(url);
			SharedPreferences preferences = getSharedPreferences(
					context.getString(R.string.const_string_preferences),
					Activity.MODE_PRIVATE);
			SharedPreferences.Editor editorPreferences = preferences.edit();
			editorPreferences.putBoolean("online", online);
			editorPreferences.commit();
			return Core.isValidCertificate(arg0[0]);

		}

		@Override
		protected void onPostExecute(Boolean result) {
			retrievingCertificate.cancel();
			if (!online) {
				writeChanges();
			} else {
				if (!result) {
					DialogInterface.OnClickListener dialogClickListener = new DialogInterface.OnClickListener() {
						@Override
						public void onClick(DialogInterface dialog, int which) {
							switch (which) {
							case DialogInterface.BUTTON_NEGATIVE:
								Toast.makeText(getApplicationContext(),
										R.string.options_not_saved,
										Toast.LENGTH_SHORT).show();
								return;
							case DialogInterface.BUTTON_POSITIVE:
								writeChanges();
								return;
							}
						}
					};

					AlertDialog.Builder builder = new AlertDialog.Builder(
							context);
					builder.setMessage(
							getString(R.string.certificate_not_valid))
							.setPositiveButton(getString(android.R.string.yes),
									dialogClickListener)
							.setNegativeButton(getString(android.R.string.no),
									dialogClickListener).show();
				} else {
					writeChanges();
				}
			}
		}
	}

	/**
	 * Removes a dialog after a timeout.
	 * 
	 * @param time
	 * @param dialog
	 */
	public void removeDialog(long time, final ProgressDialog dialog) {
		Handler handler = new Handler();
		handler.postDelayed(new Runnable() {
			public void run() {
				dialog.dismiss();
				if (asyncTask.cancel(false)) {
					Toast.makeText(getApplicationContext(),
							"Connection timeout", Toast.LENGTH_SHORT).show();
				}
			}
		}, time);
	}
}
